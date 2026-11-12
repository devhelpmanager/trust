/**
 * Тест уведомлений о заявках (шаг 3.6) на локальном стенде с демо-контентом.
 *
 * Запуск: node wp-theme/tests/notifications.js
 *
 * На время теста в mu-plugins стенда копируются fixtures/trust-test-forms.php (тестовая форма)
 * и fixtures/trust-test-notify.php (перехват почты, подмена Telegram и CRM). В конце файлы,
 * заявки, журналы и настройки убираются.
 *
 * Проверяет:
 * - письмо: получатели (администратор → по умолчанию → у формы), тема, текст (поля, UTM,
 *   откуда пришёл, страница, ссылка на заявку), Reply-To, если контакт — email;
 * - Telegram: chat_id, разметка HTML, экранирование, ссылка на заявку; неверный токен — ошибка
 *   записана, заявка сохранена;
 * - вебхук: JSON (событие, заявка, поля, страница, источник, согласие), подпись
 *   X-Trust-Signature (HMAC-SHA256), без секрета — без подписи; 500 и внутренний адрес — ошибка;
 * - сбой почты не мешает заявке; итоги на экране заявки; «Отправить уведомления ещё раз»;
 * - вкладка «Формы»: поля получателей у каждой формы, проверка адресов при сохранении,
 *   «Проверить уведомления» (тестовая заявка не сохраняется, подсказка chat_id из getUpdates),
 *   сообщение после отправки из настроек.
 */

const { chromium } = require( 'playwright' );
const crypto = require( 'crypto' );
const fs = require( 'fs' );
const path = require( 'path' );
const { URL, DEV_DIR, OUT, check, finish, wp, clearLog, checkLog, login, collectConsoleErrors, getSettings, setSettings, resetFormLimits, formToken, postForm } = require( './lib' );

const MU = path.join( DEV_DIR, 'wordpress/wp-content/mu-plugins' );
const FIXTURES = [ 'trust-test-forms.php', 'trust-test-notify.php' ];
const LOGS = [ 'trust_test_mails', 'trust_test_telegram', 'trust_test_webhook', 'trust_test_mail_fail' ];
const SETTINGS = [ 'forms_emails', 'forms_emails_callback', 'forms_emails_test', 'telegram_bot_token', 'telegram_chat_id', 'webhook_url', 'webhook_secret', 'forms_success_text' ];
const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );

const leadIds = () => new Set( wp( 'post', 'list', '--post_type=lead', '--post_status=any', '--field=ID', '--format=ids' ).split( ' ' ).filter( Boolean ) );
const option = ( name ) => JSON.parse( wp( 'eval', `echo wp_json_encode( get_option( '${ name }', array() ) );` ) );
const clearLogs = () => wp( 'eval', `foreach ( ${ JSON.stringify( LOGS ).replace( /\[/, 'array(' ).replace( /\]$/, ')' ) } as $o ) { delete_option( $o ); }` );
const notify = ( id ) => JSON.parse( wp( 'eval', `echo wp_json_encode( get_post_meta( ${ id }, 'trust_notify', true ) );` ) || 'null' );

/** Итог уведомлений заявки (ждёт, пока он появится: уведомления уходят в конце запроса). */
async function results( id ) {
	for ( let i = 0; i < 20; i++ ) {
		const r = notify( id );
		if ( r ) {
			return r;
		}
		await sleep( 250 );
	}
	return {};
}

( async () => {
	clearLog();
	resetFormLimits();
	const before = leadIds();
	const saved = getSettings( SETTINGS );
	const adminEmail = wp( 'option', 'get', 'admin_email' );
	FIXTURES.forEach( ( f ) => fs.copyFileSync( path.join( __dirname, 'fixtures', f ), path.join( MU, f ) ) );
	const browser = await chromium.launch();

	try {
		setSettings( Object.fromEntries( SETTINGS.map( ( k ) => [ k, '' ] ) ) );
		clearLogs();
		const t = await formToken();
		await sleep( 3100 );
		const source = { utm_source: 'yandex', utm_medium: 'cpc', utm_campaign: 'seo-audit', referrer: 'https://yandex.ru/search/?text=%D1%81%D0%B5%D0%BE', landing: `${ URL }/seo/`, at: '2026-11-12T08:00:00.000Z' };
		const callback = { _trust_token: t.token, _trust_nonce: t.nonce, name: 'Анна <3', phone: '+1 000 000 00 00', consent: '1', page: `${ URL }/kontakty/`, source: JSON.stringify( source ) };
		const test = { _trust_token: t.token, _trust_nonce: t.nonce, site: 'example.com', contact: 'client@example.com', mode: 'audit', text: 'Цена < 1000 & быстро', consent: '1', publish: '1' };
		const send = async ( type, fields ) => {
			const r = await postForm( type, fields );
			return { ...r, id: String( r.body?.lead || '' ) };
		};

		// --- Письмо ---
		let r = await send( 'callback', callback );
		let res = await results( r.id );
		let mails = option( 'trust_test_mails' );
		const mail = mails[ 0 ] || {};
		check( 'Получателей нет — письмо администратору сайта', r.status === 200 && mails.length === 1 && JSON.stringify( mail.to ) === JSON.stringify( [ adminEmail ] ), JSON.stringify( mail.to ) );
		check( 'Тема: «Новая заявка: Обратный звонок — Анна <3»', mail.subject === 'Новая заявка: Обратный звонок — Анна <3', mail.subject );
		check( 'Текст: поля, UTM, откуда пришёл (раскодировано), страница, ссылка на заявку', [
			'Обратный звонок\n\n',
			'Имя: Анна <3\n',
			'Телефон: +1 000 000 00 00\n',
			'Реклама (UTM): yandex / cpc / seo-audit\n',
			'Откуда пришёл: https://yandex.ru/search/?text=сео\n',
			`Страница: Контакты ${ URL }/kontakty/\n`,
			`Заявка в админке: ${ URL }/wp-admin/post.php?post=${ r.id }&action=edit`,
		].every( ( s ) => ( mail.message || '' ).includes( s ) ), mail.message );
		check( 'Простой текст UTF-8, без Reply-To (контакт — телефон)', mail.headers?.includes( 'Content-Type: text/plain; charset=UTF-8' ) && ! mail.headers.some( ( h ) => h.startsWith( 'Reply-To' ) ) );
		check( 'Итог в заявке: письмо отправлено; Telegram и вебхук не настроены — не записаны', res.email?.ok === true && res.email.info === adminEmail && ! res.telegram && ! res.webhook, JSON.stringify( res ) );

		setSettings( { forms_emails: 'sales@example.com, boss@example.com', forms_emails_callback: 'callback@example.com' } );
		clearLogs();
		await send( 'callback', callback );
		r = await send( 'test', test );
		await results( r.id );
		mails = option( 'trust_test_mails' );
		check( 'Свои получатели у формы «Обратный звонок»', JSON.stringify( mails[ 0 ]?.to ) === '["callback@example.com"]', JSON.stringify( mails[ 0 ]?.to ) );
		check( 'Другая форма — получатели по умолчанию (оба адреса)', JSON.stringify( mails[ 1 ]?.to ) === '["sales@example.com","boss@example.com"]', JSON.stringify( mails[ 1 ]?.to ) );
		check( 'Контакт — email: Reply-To на клиента; тема по контакту', mails[ 1 ]?.headers.includes( 'Reply-To: client@example.com' ) && mails[ 1 ]?.subject === 'Новая заявка: Тестовая форма — client@example.com', JSON.stringify( mails[ 1 ]?.headers ) );
		check( '«<» в тексте клиента сохраняется символом', ( mails[ 1 ]?.message || '' ).includes( 'Задача: Цена < 1000 & быстро' ) );

		// --- Telegram ---
		setSettings( { telegram_bot_token: 'good-token', telegram_chat_id: '-100222' } );
		clearLogs();
		r = await send( 'callback', callback );
		res = await results( r.id );
		const tg = option( 'trust_test_telegram' )[ 0 ] || {};
		check( 'Telegram: сообщение от бота в chat_id, разметка HTML, без превью ссылок', tg.token === 'good-token' && tg.chat_id === '-100222' && tg.parse_mode === 'HTML' && tg.disable_web_page_preview === 'true', JSON.stringify( { ...tg, text: undefined } ) );
		check( 'Telegram: заголовок, поля, «<» экранирован, ссылки на страницу и заявку', ( tg.text || '' ).startsWith( '<b>Новая заявка: Обратный звонок</b>\n' ) &&
			tg.text.includes( 'Имя: Анна &lt;3\n' ) && tg.text.includes( `Страница: <a href="${ URL }/kontakty/">Контакты</a>` ) &&
			tg.text.includes( `<a href="${ URL }/wp-admin/post.php?post=${ r.id }&#038;action=edit">Открыть заявку в админке</a>` ), tg.text );
		check( 'Итог в заявке: Telegram отправлено', res.telegram?.ok === true && res.email?.ok === true );

		setSettings( { telegram_bot_token: 'bad-token' } );
		r = await send( 'callback', callback );
		res = await results( r.id );
		check( 'Неверный токен: заявка сохранена, ошибка Telegram записана, письмо ушло', r.status === 200 && res.telegram?.ok === false && res.telegram.info === 'HTTP 401 Unauthorized' && res.email?.ok === true, JSON.stringify( res ) );
		const failedLead = r.id;

		// --- Вебхук ---
		setSettings( { telegram_bot_token: '', webhook_url: 'https://crm.example/hook', webhook_secret: 's3cret-key' } );
		clearLogs();
		r = await send( 'callback', callback );
		res = await results( r.id );
		const hook = option( 'trust_test_webhook' )[ 0 ] || {};
		let payload = {};
		try {
			payload = JSON.parse( hook.body );
		} catch ( e ) {}
		const lead = payload.lead || {};
		check( 'Вебхук: JSON с событием lead.created и адресом сайта', hook.headers?.[ 'Content-Type' ] === 'application/json; charset=utf-8' && hook.headers[ 'X-Trust-Event' ] === 'lead.created' && payload.event === 'lead.created' && payload.site === `${ URL }/` && !! payload.sent_at, JSON.stringify( hook.headers ) );
		check( 'Вебхук: заявка — id, форма, статус, имя, контакт, поля, страница, источник, согласие, ссылка', String( lead.id ) === r.id && lead.form === 'callback' && lead.title === 'Обратный звонок: Анна <3' && lead.form_label === 'Обратный звонок' && lead.status === 'new' &&
			lead.name === 'Анна <3' && lead.contact === '+1 000 000 00 00' && lead.fields?.phone?.value === '+1 000 000 00 00' &&
			lead.page?.url === `${ URL }/kontakty/` && lead.page?.title === 'Контакты' && lead.source?.utm_campaign === 'seo-audit' &&
			lead.consent?.items?.[ 0 ] === 'Согласие на обработку персональных данных' && lead.admin_url === `${ URL }/wp-admin/post.php?post=${ r.id }&action=edit` && /^\d{4}-\d\d-\d\dT/.test( lead.created_at ), hook.body );
		const signature = 'sha256=' + crypto.createHmac( 'sha256', 's3cret-key' ).update( hook.body || '' ).digest( 'hex' );
		check( 'Вебхук: подпись X-Trust-Signature = HMAC-SHA256 тела секретом', hook.headers?.[ 'X-Trust-Signature' ] === signature, hook.headers?.[ 'X-Trust-Signature' ] );
		check( 'Итог в заявке: вебхук HTTP 200', res.webhook?.ok === true && res.webhook.info === 'HTTP 200' );

		setSettings( { webhook_secret: '' } );
		clearLogs();
		r = await send( 'callback', callback );
		await results( r.id );
		check( 'Без секрета — без подписи', ! ( 'X-Trust-Signature' in ( option( 'trust_test_webhook' )[ 0 ]?.headers || { 'X-Trust-Signature': 1 } ) ) );

		setSettings( { webhook_url: 'https://crm.example/fail' } );
		r = await send( 'callback', callback );
		res = await results( r.id );
		check( 'CRM ответила 500 — ошибка записана, заявка сохранена', r.status === 200 && res.webhook?.ok === false && res.webhook.info === 'HTTP 500' );
		setSettings( { webhook_url: 'http://127.0.0.1:8080/wp-json/' } );
		r = await send( 'callback', callback );
		res = await results( r.id );
		check( 'Внутренний адрес сервера (127.0.0.1) — запрос не отправляется', res.webhook?.ok === false && res.webhook.info === 'A valid URL was not provided.', JSON.stringify( res.webhook ) );

		// --- Сбой почты ---
		setSettings( { webhook_url: '' } );
		wp( 'option', 'update', 'trust_test_mail_fail', '1' );
		r = await send( 'callback', callback );
		res = await results( r.id );
		check( 'Почта не отправила письмо — заявка сохранена, ошибка записана', r.status === 200 && res.email?.ok === false && !! res.email.info, JSON.stringify( res.email ) );
		wp( 'option', 'delete', 'trust_test_mail_fail' );

		// --- Экран заявки и повторная отправка ---
		const admin = await browser.newPage( { viewport: { width: 1400, height: 900 } } );
		const consoleErrors = collectConsoleErrors( admin );
		await login( admin );
		await admin.goto( `${ URL }/wp-admin/post.php?post=${ failedLead }&action=edit` );
		const row = ( await admin.locator( '#trust-lead-box tr', { hasText: 'Уведомления' } ).textContent() ).replace( /\s+/g, ' ' );
		check( 'Экран заявки: «Уведомления» — письмо отправлено, Telegram ошибка, ссылка «ещё раз»', row.includes( 'Письмо: отправлено — callback@example.com' ) && row.includes( 'Telegram: ошибка — HTTP 401 Unauthorized' ) && row.includes( 'Отправить уведомления ещё раз' ), row );
		await admin.locator( '#trust-lead-box tr', { hasText: 'Уведомления' } ).screenshot( { path: path.join( OUT, 'notifications-lead.png' ) } );
		setSettings( { telegram_bot_token: 'good-token' } );
		clearLogs();
		await Promise.all( [ admin.waitForNavigation(), admin.click( '.trust-notify-resend' ) ] );
		res = notify( failedLead );
		check( '«Отправить уведомления ещё раз»: сообщение, Telegram теперь отправлено', ( await admin.locator( '.notice-success' ).first().textContent() ).includes( 'Уведомления отправлены ещё раз' ) && res.telegram?.ok === true && option( 'trust_test_telegram' ).length === 1 );

		// --- Вкладка «Формы» ---
		const settingsUrl = `${ URL }/wp-admin/admin.php?page=trust-settings`;
		await admin.goto( settingsUrl );
		await admin.locator( '.acf-tab-wrap .acf-tab-button', { hasText: 'Формы' } ).click();
		const labels = ( await admin.locator( '[data-name^="forms_emails"] .acf-label label' ).allTextContents() ).map( ( s ) => s.trim() );
		check( 'Получатели: по умолчанию и у каждой формы', JSON.stringify( labels ) === JSON.stringify( [ 'Получатели писем по умолчанию', 'Получатели: Обратный звонок', 'Получатели: Тестовая форма' ] ), labels.join( ' | ' ) );
		check( 'Подсказка: адрес администратора', ( await admin.getAttribute( '[data-name="forms_emails"] input', 'placeholder' ) ) === adminEmail );
		check( 'Токен бота и секрет вебхука — поля пароля', ( await admin.getAttribute( '[data-name="telegram_bot_token"] input', 'type' ) ) === 'password' && ( await admin.getAttribute( '[data-name="webhook_secret"] input', 'type' ) ) === 'password' );
		await admin.fill( '[data-name="forms_emails"] input', 'sales@example.com, not-an-email' );
		await admin.locator( '#publish, .acf-settings-wrap input[type="submit"]' ).first().click();
		await admin.waitForSelector( '[data-name="forms_emails"] .acf-notice.-error, .acf-error-message', { timeout: 15000 } );
		const error = ( await admin.locator( '[data-name="forms_emails"] .acf-notice' ).first().textContent().catch( () => '' ) ).trim();
		check( 'Неверный адрес — ошибка при сохранении, настройка не меняется', error.includes( 'Проверьте адреса: not-an-email' ) && getSettings( [ 'forms_emails' ] ).forms_emails === 'sales@example.com, boss@example.com', error );

		// «Проверить уведомления»: токен есть, chat_id нет — подсказка chat_id.
		setSettings( { telegram_chat_id: '', webhook_url: 'https://crm.example/hook' } );
		clearLogs();
		const leadsBeforeTest = leadIds().size;
		await admin.goto( settingsUrl );
		await admin.locator( '.acf-tab-wrap .acf-tab-button', { hasText: 'Формы' } ).click();
		await Promise.all( [ admin.waitForNavigation(), admin.click( '[data-name="forms_notify_test"] a.button' ) ] );
		const notice = ( await admin.locator( '.trust-notify-test' ).textContent().catch( () => '' ) ).replace( /\s+/g, ' ' );
		check( 'Проверка: письмо, вебхук отправлены; Telegram без chat_id — не настроен', notice.includes( 'Письмо: отправлено — sales@example.com, boss@example.com' ) && notice.includes( 'Вебхук CRM: отправлено' ) && notice.includes( 'Telegram: не настроен' ), notice );
		check( 'Проверка: подсказка chat_id из чатов бота', notice.includes( 'Анна Петрова (chat_id: 111)' ) && notice.includes( 'Заявки (chat_id: -100222)' ), notice );
		const testHook = JSON.parse( option( 'trust_test_webhook' )[ 0 ]?.body || '{}' );
		check( 'Проверка: тема письма «Тестовая заявка», вебхук lead.test, заявка не сохранена', option( 'trust_test_mails' )[ 0 ]?.subject === 'Новая заявка: Тестовая заявка — Проверка уведомлений' && testHook.event === 'lead.test' && leadIds().size === leadsBeforeTest );
		await admin.screenshot( { path: path.join( OUT, 'notifications-test.png' ) } );

		setSettings( { telegram_bot_token: 'empty-token' } );
		await admin.goto( settingsUrl );
		await Promise.all( [ admin.waitForNavigation(), admin.click( '[data-name="forms_notify_test"] a.button' ) ] );
		check( 'Боту никто не писал — подсказка, что сделать', ( await admin.locator( '.trust-notify-test' ).textContent() ).includes( 'боту пока никто не писал' ) );
		setSettings( { telegram_chat_id: '-100222', telegram_bot_token: 'good-token' } );
		clearLogs();
		await admin.goto( settingsUrl );
		await Promise.all( [ admin.waitForNavigation(), admin.click( '[data-name="forms_notify_test"] a.button' ) ] );
		check( 'Проверка с chat_id: Telegram отправлено', ( await admin.locator( '.trust-notify-test' ).textContent() ).includes( 'Telegram: отправлено' ) && option( 'trust_test_telegram' )[ 0 ]?.text.includes( 'Тестовая заявка' ) );
		check( 'Консоль админки без ошибок', consoleErrors.length === 0, consoleErrors.slice( 0, 3 ).join( ' | ' ) );
		await admin.close();

		// --- Сообщение после отправки ---
		setSettings( { forms_success_text: 'Спасибо! Ответим в течение часа.' } );
		const html = await ( await fetch( `${ URL }/?trust_test_form=1&trust_form=sent&trust_form_type=test` ) ).text();
		check( 'Сообщение после отправки из настроек (скрипт и возврат без JavaScript)', html.includes( '"sent":"Спасибо! Ответим в течение часа."' ) && html.includes( 'form__status--ok" role="alert">Спасибо! Ответим в течение часа.</p>' ) );
	} finally {
		await browser.close();
		FIXTURES.forEach( ( f ) => fs.rmSync( path.join( MU, f ), { force: true } ) );
		[ ...leadIds() ].filter( ( id ) => ! before.has( id ) ).forEach( ( id ) => wp( 'post', 'delete', id, '--force' ) );
		clearLogs();
		setSettings( Object.fromEntries( SETTINGS.map( ( k ) => [ k, saved[ k ] ?? '' ] ) ) );
		resetFormLimits();
	}

	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
