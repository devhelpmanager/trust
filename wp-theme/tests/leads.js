/**
 * Тест основы форм и заявок (шаг 3.5) на локальном стенде с демо-контентом.
 *
 * Запуск: node wp-theme/tests/leads.js
 *
 * На время теста в mu-plugins стенда копируется fixtures/trust-test-forms.php: тестовая форма
 * со всеми типами полей и капчей (/?trust_test_form=1), подмена серверов проверки капчи,
 * управляемый лимит частоты. В конце файл, созданные тестом заявки и настройки убираются.
 *
 * Проверяет:
 * - REST: токен и nonce без кэша; неизвестная форма — 404; слишком быстро — 400; поддельный,
 *   просроченный токен и чужой nonce — 403; ловушка — «успех» без заявки; поля и согласия —
 *   те же сообщения, что в разметке; HTML вырезается; лимит с одного IP — 429, адрес из
 *   CF-Connecting-IP считается отдельно;
 * - заявка: заголовок, поля (у выбора — подпись варианта), страница и её название (только свой
 *   сайт), UTM и источник (адреса с %-кодами не портятся, время — в часовом поясе сайта),
 *   статус «Новая», согласия с документом и датой редакции; счётчик новых заявок в меню;
 * - капча: без ключей форма работает; reCAPTCHA и SmartCaptcha — ответ проверяется на сервере
 *   с секретным ключом, секрет не попадает на страницу; скрипт капчи — только после касания формы;
 * - браузер: проверка полей на клиенте, ответ без капчи не уходит, «Заявка отправлена» и
 *   «Отправить ещё»; устаревший nonce — скрипт берёт новый и повторяет; ошибки поля от сервера;
 *   нет связи — понятное сообщение; без JavaScript — отправка на admin-post.php и сообщение;
 * - админка: колонки, фильтры по форме и статусу, экран заявки, смена статуса (на экране и
 *   массово), выгрузка CSV (BOM, «;», формулы экранированы); вкладка «Формы» только у
 *   администратора, поля ключей по выбранной капче, секрет — поле пароля;
 * - срок хранения: задача WP-Cron, старые заявки удаляются, без срока — не удаляются.
 */

const fs = require( 'fs' );
const path = require( 'path' );
const { launch, URL, DEV_DIR, OUT, check, finish, wp, clearLog, checkLog, login, collectConsoleErrors, ensureUser, getSettings, setSettings, resetFormLimits, formToken: token, postForm: post } = require( './lib' );

const MU_PLUGIN = path.join( DEV_DIR, 'wordpress/wp-content/mu-plugins/trust-test-forms.php' );
const SETTINGS = [ 'forms_captcha', 'recaptcha_site_key', 'recaptcha_secret_key', 'smartcaptcha_client_key', 'smartcaptcha_server_key', 'forms_retention_days' ];
const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );

const leadIds = () => new Set( wp( 'post', 'list', '--post_type=lead', '--post_status=any', '--field=ID', '--format=ids' ).split( ' ' ).filter( Boolean ) );
const meta = ( id, key ) => {
	try {
		return JSON.parse( wp( 'post', 'meta', 'get', id, key, '--format=json' ) || '""' );
	} catch ( e ) {
		return ''; // Нет такого поля.
	}
};

( async () => {
	clearLog();
	resetFormLimits();
	const before = leadIds();
	const saved = getSettings( SETTINGS );
	fs.copyFileSync( path.join( __dirname, 'fixtures', 'trust-test-forms.php' ), MU_PLUGIN );
	const browser = await launch();

	try {
		setSettings( { forms_captcha: 'none', forms_retention_days: '' } );
		const newLeads = () => [ ...leadIds() ].filter( ( id ) => ! before.has( id ) );

		// --- REST: токен, ошибки, защита ---
		const t = await token();
		check( 'Токен и nonce: GET /wp-json/trust/v1/forms/token без кэша', /^\d{10}-[a-f0-9]{64}$/.test( t.token ) && /^[a-f0-9]{10}$/.test( t.nonce ) && t.cache.includes( 'no-cache' ), t.cache );
		const callback = { _trust_token: t.token, _trust_nonce: t.nonce, name: 'Анна', phone: '+1 000 000 00 00', consent: '1' };

		let r = await post( 'nope', callback );
		check( 'Неизвестная форма — 404', r.status === 404 && r.body?.code === 'not_found' );
		r = await post( 'callback', callback );
		check( 'Отправка раньше 3 секунд — 400 «слишком быстро», заявки нет', r.status === 400 && r.body?.code === 'too_fast' && newLeads().length === 0, r.text );
		await sleep( 3100 );

		r = await post( 'callback', { ...callback, _trust_nonce: 'abcdef1234' } );
		check( 'Чужой nonce — 403 «Страница устарела»', r.status === 403 && r.body?.code === 'stale' && r.body.message.startsWith( 'Страница устарела' ) );
		r = await post( 'callback', { ...callback, _trust_token: t.token.replace( /.$/, ( c ) => ( c === '0' ? '1' : '0' ) ) } );
		check( 'Поддельная подпись токена — 403', r.status === 403 && r.body?.code === 'stale' );
		const old = wp( 'eval', '$t = (string) ( time() - 13 * HOUR_IN_SECONDS ); echo $t . "-" . hash_hmac( "sha256", $t . "|trust_form", wp_salt( "nonce" ) );' );
		r = await post( 'callback', { ...callback, _trust_token: old } );
		check( 'Токен старше 12 часов — 403', r.status === 403 && r.body?.code === 'stale' );

		r = await post( 'callback', { ...callback, trust_website: 'http://spam.example' } );
		check( 'Заполнена ловушка — ответ «успешно», заявка не создана', r.status === 200 && r.body?.ok === true && ! r.body.lead && newLeads().length === 0 );

		r = await post( 'callback', { _trust_token: t.token, _trust_nonce: t.nonce, name: '  ', phone: '12345' } );
		const errors = r.body?.errors || {};
		check( 'Пустые и неверные поля — 400 с сообщениями из разметки окна', r.status === 400 && r.body.code === 'invalid' &&
			errors.name === 'Укажите, как к вам обращаться' &&
			errors.phone === 'Проверьте номер телефона, например +1 000 000 00 00' &&
			errors.consent === 'Отметьте согласие — без него мы не сможем ответить на заявку', JSON.stringify( errors ) );
		r = await post( 'callback', { ...callback, phone: '+1 000 abc 00 00 00' } );
		check( 'Буквы в телефоне — ошибка', r.status === 400 && !! r.body.errors.phone );

		const source = { utm_source: 'yandex', utm_medium: 'cpc', utm_campaign: 'seo-audit', referrer: 'https://yandex.ru/search/?text=%D1%81%D0%B5%D0%BE', landing: `${ URL }/seo/?utm_source=yandex`, at: '2026-11-12T08:00:00.000Z', evil: 'x' };
		r = await post( 'callback', { ...callback, name: '<b>Анна</b> <script>x</script>', page: `${ URL }/kontakty/`, source: JSON.stringify( source ) } );
		const leadId = String( r.body?.lead || '' );
		check( 'Верные данные — 200, заявка создана', r.status === 200 && r.body.ok === true && !! leadId && newLeads().includes( leadId ), r.text );
		if ( leadId ) {
			const fields = meta( leadId, 'trust_fields' );
			const src = meta( leadId, 'trust_source' );
			const consent = meta( leadId, 'trust_consent' );
			const revision = wp( 'eval', 'echo get_post_modified_time( "Y-m-d", false, Trust\\Settings::get( "consent_page" ) );' );
			check( 'Заявка: заголовок «Обратный звонок: Анна», HTML вырезан', wp( 'post', 'get', leadId, '--field=post_title' ) === 'Обратный звонок: Анна' && meta( leadId, 'trust_name' ) === 'Анна' );
			check( 'Заявка: поля с подписями', fields?.name?.label === 'Имя' && fields?.phone?.value === '+1 000 000 00 00' && meta( leadId, 'trust_contact' ) === '+1 000 000 00 00', JSON.stringify( fields ) );
			check( 'Заявка: форма callback, статус «Новая»', meta( leadId, 'trust_form' ) === 'callback' && meta( leadId, 'trust_status' ) === 'new' );
			check( 'Заявка: страница отправки и её название', meta( leadId, 'trust_page' ) === `${ URL }/kontakty/` && meta( leadId, 'trust_page_title' ) === 'Контакты' );
			check( 'Заявка: UTM и источник; %-коды целы; лишние ключи отброшены', src?.utm_source === 'yandex' && src?.utm_campaign === 'seo-audit' && src?.referrer === source.referrer && src?.landing === source.landing && ! src?.evil, JSON.stringify( src ) );
			check( 'Заявка: время первого визита — в часовом поясе сайта', src?.at === wp( 'eval', 'echo wp_date( "Y-m-d H:i:s", strtotime( "2026-11-12T08:00:00Z" ) );' ), src?.at );
			check( 'Заявка: согласие — галочка, время, документ и дата его редакции', consent?.items?.[ 0 ] === 'Согласие на обработку персональных данных' && /^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$/.test( consent?.at ) && consent?.document === `${ URL }/soglasie-na-obrabotku-dannyh/` && consent?.revision === revision, JSON.stringify( consent ) );
		}
		r = await post( 'callback', { ...callback, page: 'https://evil.example/phish' } );
		check( 'Страница чужого сайта не сохраняется', r.status === 200 && meta( String( r.body.lead ), 'trust_page' ) === '' );

		// Лимит частоты.
		resetFormLimits();
		const limit = { 'X-Trust-Limit': '8' };
		const statuses = [];
		for ( let i = 0; i < 9; i++ ) {
			statuses.push( ( await post( 'callback', { ...callback, trust_website: 'spam' }, limit ) ).status );
		}
		check( 'Лимит: 8 отправок с одного IP за 10 минут, девятая — 429', statuses.slice( 0, 8 ).every( ( s ) => s === 200 ) && statuses[ 8 ] === 429, statuses.join( ',' ) );
		r = await post( 'callback', { ...callback, trust_website: 'spam' }, { ...limit, 'CF-Connecting-IP': '203.0.113.7' } );
		check( 'Адрес посетителя за Cloudflare (CF-Connecting-IP) считается отдельно', r.status === 200 );
		check( 'Лимит по умолчанию — 8 за 10 минут', wp( 'eval', 'echo Trust\\Forms\\Protection::RATE_LIMIT, "/", Trust\\Forms\\Protection::RATE_WINDOW;' ) === '8/600' );
		resetFormLimits();

		// --- Тестовая форма: типы полей, два согласия, капча ---
		const test = { _trust_token: t.token, _trust_nonce: t.nonce, site: 'example.com', contact: 'anna@example.com', mode: 'audit', text: 'Нужен аудит сайта', consent: '1', publish: '1' };
		r = await post( 'test', { ...test, site: 'example', contact: 'abc', email: 'bad@', mode: 'other', text: 'коротко', publish: '' } );
		check( 'Типы полей: адрес, контакт, email, выбор, длина текста, второе согласие', r.status === 400 && [ 'site', 'contact', 'email', 'mode', 'text', 'publish' ].every( ( k ) => r.body.errors[ k ] ) && ! r.body.errors.consent, JSON.stringify( r.body?.errors ) );
		r = await post( 'test', { ...test, text: 'x'.repeat( 51 ) } );
		check( 'Длиннее max — ошибка', r.status === 400 && !! r.body.errors.text );
		r = await post( 'test', { ...test, share: '1' } );
		const testLead = String( r.body?.lead || '' );
		const testFields = testLead ? meta( testLead, 'trust_fields' ) : {};
		check( 'Капча выбрана, но ключей нет — форма работает без капчи', r.status === 200 && !! testLead, r.text );
		check( 'Выбор хранится подписью варианта, флажок — «да», контакт — email', testFields?.mode?.value === 'Аудит' && testFields?.share?.value === 'да' && meta( testLead, 'trust_contact' ) === 'anna@example.com', JSON.stringify( testFields ) );
		check( 'Без имени — заголовок по контакту, оба согласия записаны', wp( 'post', 'get', testLead, '--field=post_title' ) === 'Тестовая форма: anna@example.com' && meta( testLead, 'trust_consent' )?.items?.length === 2 );

		setSettings( { forms_captcha: 'recaptcha', recaptcha_site_key: 'test-site-key', recaptcha_secret_key: 'test-secret-key' } );
		r = await post( 'test', test );
		check( 'reCAPTCHA: без ответа капчи — 400', r.status === 400 && r.body.code === 'captcha' && r.body.errors.captcha === 'Подтвердите, что вы не робот' );
		r = await post( 'test', { ...test, captcha: 'bad-token' } );
		check( 'reCAPTCHA: неверный ответ — 400', r.status === 400 && r.body.code === 'captcha' );
		r = await post( 'test', { ...test, captcha: 'good-token' } );
		const sent = JSON.parse( wp( 'option', 'get', 'trust_test_captcha', '--format=json' ) );
		check( 'reCAPTCHA: верный ответ — заявка; на проверку ушли секрет и ответ', r.status === 200 && sent.provider === 'recaptcha' && sent.secret === 'test-secret-key' && sent.response === 'good-token' );
		r = await post( 'callback', { ...callback } );
		check( 'Окно звонка — без капчи, даже когда она настроена', r.status === 200 );
		const html = await ( await fetch( `${ URL }/?trust_test_form=1` ) ).text();
		check( 'На странице — ключ сайта и место для капчи, секрета нет', html.includes( '"siteKey":"test-site-key"' ) && html.includes( 'data-trust-captcha' ) && ! html.includes( 'test-secret-key' ) );
		check( 'Окно звонка без места для капчи', ! /id="form-callback"[\s\S]*?data-trust-captcha[\s\S]*?<\/form>/.test( html.slice( html.indexOf( 'id="form-callback"' ), html.indexOf( '</form>', html.indexOf( 'id="form-callback"' ) ) + 7 ) ) );

		setSettings( { forms_captcha: 'smartcaptcha', smartcaptcha_client_key: 'ya-client', smartcaptcha_server_key: 'ya-server' } );
		r = await post( 'test', { ...test, captcha: 'bad-token' } );
		const r2 = await post( 'test', { ...test, captcha: 'good-token' } );
		const sentYa = JSON.parse( wp( 'option', 'get', 'trust_test_captcha', '--format=json' ) );
		check( 'SmartCaptcha: неверный ответ — 400, верный — заявка; ушёл ключ сервера', r.status === 400 && r2.status === 200 && sentYa.provider === 'smartcaptcha' && sentYa.secret === 'ya-server' && sentYa.token === 'good-token' );
		resetFormLimits();

		// --- Браузер: тестовая форма с reCAPTCHA (скрипт Google подменён) ---
		setSettings( { forms_captcha: 'recaptcha' } );
		const page = await browser.newPage( { viewport: { width: 1280, height: 900 } } );
		const consoleErrors = collectConsoleErrors( page );
		const captchaLoads = [];
		const posts = [];
		await page.route( 'https://www.google.com/recaptcha/api.js**', ( route ) => {
			captchaLoads.push( route.request().url() );
			route.fulfill( {
				contentType: 'application/javascript',
				body: `window.grecaptcha = {
					render( el, o ) { el.innerHTML = '<button type="button" class="fake-captcha">Я не робот</button>'; el.querySelector( 'button' ).onclick = () => { window.__captcha = 'good-token'; o.callback( 'good-token' ); }; window.__sitekey = o.sitekey; return 0; },
					getResponse() { return window.__captcha || ''; },
					reset() { window.__captcha = ''; },
				}; window.trustCaptchaReady();`,
			} );
		} );
		page.on( 'request', ( req ) => req.method() === 'POST' && req.url().includes( '/trust/v1/forms/' ) && posts.push( req.url() ) );

		await page.goto( `${ URL }/?trust_test_form=1`, { waitUntil: 'networkidle' } );
		check( 'Скрипт капчи не грузится, пока форму не трогали', captchaLoads.length === 0 );
		const hp = await page.evaluate( () => {
			const box = document.querySelector( '#form-test .form__hp' );
			const r = box.getBoundingClientRect();
			return { off: r.right <= 0, aria: box.getAttribute( 'aria-hidden' ), tab: box.querySelector( 'input' ).tabIndex };
		} );
		check( 'Ловушка: за пределами экрана, aria-hidden, tabindex -1', hp.off && hp.aria === 'true' && hp.tab === -1, JSON.stringify( hp ) );

		await page.click( '#form-test .form__submit' );
		const shown = await page.locator( '#form-test .field__error:visible' ).count();
		check( 'Пустая форма: 6 ошибок (email необязателен), фокус на первом поле, запрос не ушёл', shown === 6 && await page.evaluate( () => document.activeElement.id === 't-site' ) && posts.length === 0, String( shown ) );
		await page.waitForSelector( '#form-test .fake-captcha' );
		check( 'После касания формы загружен виджет капчи с ключом сайта', captchaLoads.length === 1 && captchaLoads[ 0 ].includes( 'render=explicit' ) && await page.evaluate( () => window.__sitekey === 'test-site-key' ) );

		await page.fill( '#t-site', 'example' );
		await page.fill( '#t-contact', 'abc' );
		await page.fill( '#t-email', 'bad@' );
		await page.selectOption( '#t-mode', 'audit' );
		await page.fill( '#t-text', 'коротко' );
		await page.click( '#form-test .form__submit' );
		const bad = await page.locator( '#form-test [aria-invalid="true"]' ).evaluateAll( ( els ) => els.map( ( e ) => e.name ) );
		check( 'Клиент: адрес без точки, неверный контакт, email, короткий текст', JSON.stringify( bad ) === JSON.stringify( [ 'site', 'contact', 'email', 'text', 'consent', 'publish' ] ), bad.join( ',' ) );
		await page.fill( '#t-site', 'example.com' );
		check( 'Ошибка снимается при исправлении', await page.locator( '#t-site-error' ).isHidden() );
		await page.fill( '#t-contact', '+1 000 000 00 00' );
		await page.fill( '#t-email', '' );
		await page.fill( '#t-text', 'Нужен аудит сайта' );
		await page.check( '#t-consent' );
		await page.check( '#t-publish' );
		await page.click( '#form-test .form__submit' );
		await page.waitForSelector( '#form-test [data-trust-captcha][aria-invalid="true"]' );
		check( 'Без ответа капчи — сообщение под капчей, запрос не ушёл', ( await page.textContent( '#form-test-captcha-error' ) ) === 'Подтвердите, что вы не робот' && posts.length === 0 );
		await page.click( '#form-test .fake-captcha' );
		check( 'Ответ капчи снимает ошибку', await page.locator( '#form-test-captcha-error' ).isHidden() );
		await page.click( '#form-test .form__submit' );
		await page.waitForSelector( '#test-success:not([hidden])', { timeout: 15000 } );
		check( 'Отправлено: форма скрыта, экран успеха, фокус на «Отправить ещё»', await page.locator( '#form-test' ).isHidden() && await page.evaluate( () => document.activeElement.matches( '[data-form-again]' ) ) && posts.length === 1 );
		const uiLead = newLeads().find( ( id ) => meta( id, 'trust_form' ) === 'test' && meta( id, 'trust_contact' ) === '+1 000 000 00 00' );
		const uiSource = uiLead ? meta( uiLead, 'trust_source' ) : null;
		check( 'Заявка из браузера: страница и первая страница визита из cookie trust_source', !! uiLead && meta( uiLead, 'trust_page' ) === `${ URL }/?trust_test_form=1` && uiSource?.landing === `${ URL }/?trust_test_form=1` && !! uiSource?.at, JSON.stringify( uiSource ) );
		check( 'Капча сброшена после отправки', await page.evaluate( () => ! window.__captcha ) );
		await page.click( '[data-form-again]' );
		check( '«Отправить ещё»: пустая форма, фокус на первом поле', await page.locator( '#form-test' ).isVisible() && ( await page.inputValue( '#t-site' ) ) === '' && await page.evaluate( () => document.activeElement.id === 't-site' ) );
		await page.screenshot( { path: path.join( OUT, 'leads-test-form.png' ), fullPage: true } );
		await page.close();
		setSettings( { forms_captcha: 'none' } );
		resetFormLimits();

		// --- Браузер: окно звонка — устаревший nonce, ошибки сервера, нет связи ---
		const p2 = await browser.newPage( { viewport: { width: 1280, height: 800 } } );
		const errors2 = collectConsoleErrors( p2 );
		const callbackPosts = [];
		p2.on( 'request', ( req ) => req.method() === 'POST' && req.url().includes( '/trust/v1/forms/callback' ) && callbackPosts.push( req.url() ) );
		const fillCallback = async () => {
			await p2.click( '.header-callback' );
			await p2.fill( '#callback-name', 'Борис' );
			await p2.fill( '#callback-phone', '+1 000 000 00 01' );
			await p2.check( '#callback-modal .check__input' );
		};
		await p2.goto( `${ URL }/kontakty/`, { waitUntil: 'networkidle' } );
		await fillCallback();
		await p2.waitForFunction( () => document.querySelector( '#form-callback [name="_trust_nonce"]' ).value.length === 10 );
		await sleep( 500 );
		await p2.evaluate( () => {
			document.querySelector( '#form-callback [name="_trust_nonce"]' ).value = 'stalenonce';
		} );
		await p2.click( '#callback-modal .form__submit' );
		await p2.waitForSelector( '#callback-modal .callback-modal__success:not([hidden])', { timeout: 20000 } );
		check( 'Устаревший nonce: скрипт берёт новый и повторяет — заявка ушла', callbackPosts.length === 2, String( callbackPosts.length ) );

		await p2.route( '**/trust/v1/forms/callback', ( route ) => route.fulfill( { status: 400, contentType: 'application/json', body: JSON.stringify( { ok: false, code: 'invalid', message: 'Проверьте поля формы.', errors: { phone: 'Такой номер не обслуживается' } } ) } ) );
		await p2.goto( `${ URL }/kontakty/`, { waitUntil: 'networkidle' } );
		await fillCallback();
		await p2.click( '#callback-modal .form__submit' );
		await p2.waitForSelector( '#callback-phone[aria-invalid="true"]', { timeout: 15000 } );
		check( 'Ошибка поля от сервера — под полем, фокус на нём', ( await p2.textContent( '#callback-phone-error' ) ) === 'Такой номер не обслуживается' && await p2.evaluate( () => document.activeElement.id === 'callback-phone' ) );

		await p2.unroute( '**/trust/v1/forms/callback' );
		await p2.route( '**/trust/v1/forms/callback', ( route ) => route.abort() );
		await p2.goto( `${ URL }/kontakty/`, { waitUntil: 'networkidle' } );
		await fillCallback();
		await p2.click( '#callback-modal .form__submit' );
		await p2.waitForSelector( '#callback-modal .form__status:not([hidden])', { timeout: 15000 } );
		const offline = ( await p2.textContent( '#callback-modal .form__status' ) ).trim();
		check( 'Нет связи с сайтом — сообщение в окне, кнопка снова доступна', offline.startsWith( 'Не удалось отправить заявку: нет связи с сайтом' ) && await p2.locator( '#callback-modal .form__submit' ).isEnabled(), offline );
		await p2.close();
		const blockedErrors = errors2.filter( ( e ) => ! e.includes( 'Failed to load resource' ) && ! e.includes( 'ERR_FAILED' ) );
		check( 'Консоль браузера без ошибок (кроме подстроенных)', consoleErrors.length === 0 && blockedErrors.length === 0, [ ...consoleErrors, ...blockedErrors ].slice( 0, 3 ).join( ' | ' ) );
		resetFormLimits();

		// --- Без JavaScript: отправка на admin-post.php ---
		const noJs = await browser.newContext( { javaScriptEnabled: false } );
		const p3 = await noJs.newPage();
		await p3.goto( `${ URL }/?trust_test_form=1` );
		check( 'Без JavaScript: форма ведёт на admin-post.php', ( await p3.getAttribute( '#form-test', 'action' ) ) === `${ URL }/wp-admin/admin-post.php` );
		await p3.fill( '#t-site', 'nojs.example' );
		await p3.fill( '#t-contact', 'nojs@example.com' );
		await p3.selectOption( '#t-mode', 'full' );
		await p3.fill( '#t-text', 'Отправка без скриптов' );
		await p3.check( '#t-consent', { force: true } );
		await p3.check( '#t-publish', { force: true } );
		await sleep( 3100 );
		await Promise.all( [ p3.waitForNavigation(), p3.click( '#form-test .form__submit' ) ] );
		const back = new globalThis.URL( p3.url() );
		check( 'Без JavaScript: возврат на страницу с ?trust_form=sent', back.searchParams.get( 'trust_form' ) === 'sent' && back.searchParams.get( 'trust_form_type' ) === 'test' && back.searchParams.get( 'trust_test_form' ) === '1' && back.hash === '#form-test', p3.url() );
		check( 'Без JavaScript: сообщение «Заявка отправлена»', ( await p3.textContent( '#form-test .form__status--ok' ) || '' ).startsWith( 'Заявка отправлена' ) );
		check( 'Без JavaScript: заявка сохранена', newLeads().some( ( id ) => meta( id, 'trust_contact' ) === 'nojs@example.com' ) );
		await noJs.close();
		const fallback = await fetch( `${ URL }/wp-admin/admin-post.php`, {
			method: 'POST',
			redirect: 'manual',
			headers: { Referer: `${ URL }/kontakty/` },
			body: new URLSearchParams( { action: 'trust_form', trust_form_type: 'callback', _trust_token: t.token, _trust_nonce: t.nonce, name: 'Без согласия', phone: '+1 000 000 00 00' } ),
		} );
		await fallback.text();
		check( 'Без JavaScript, ошибка: возврат с ?trust_form=error', fallback.status === 302 && fallback.headers.get( 'location' ) === `${ URL }/kontakty/?trust_form=error&trust_form_type=callback#form-callback`, fallback.headers.get( 'location' ) );

		// --- Админка ---
		ensureUser( 'editor', 'editor' );
		const admin = await browser.newPage( { viewport: { width: 1400, height: 900 } } );
		const adminErrors = collectConsoleErrors( admin );
		await login( admin );
		const counter = wp( 'eval', 'echo Trust\\Admin::counts()["lead"];' );
		await admin.goto( `${ URL }/wp-admin/edit.php?post_type=lead` );
		const menuCount = ( await admin.locator( '#menu-posts-lead .awaiting-mod .pending-count' ).textContent().catch( () => '' ) ).trim();
		check( 'Меню: счётчик новых заявок', menuCount === counter && Number( counter ) >= 5, `${ menuCount } / ${ counter }` );
		const heads = ( await admin.locator( '.wp-list-table thead th' ).allTextContents() ).map( ( s ) => s.trim().replace( /\s*Сортировать.*$/, '' ) ).filter( Boolean );
		check( 'Колонки: Заявка, Форма, Имя, Контакт, Статус, Дата', JSON.stringify( heads.map( ( h ) => h.replace( /^Date/, 'Дата' ).replace( /^(Заявка|Форма|Имя|Контакт|Статус|Дата).*$/, '$1' ) ) ) === JSON.stringify( [ 'Заявка', 'Форма', 'Имя', 'Контакт', 'Статус', 'Дата' ] ), heads.join( ' | ' ) );
		const row = admin.locator( `#post-${ leadId }` );
		check( 'Строка заявки: форма, имя, контакт, статус «Новая»', ( await row.locator( '.column-trust_form' ).textContent() ).trim() === 'Обратный звонок' && ( await row.locator( '.column-trust_name' ).textContent() ).trim() === 'Анна' && ( await row.locator( '.column-trust_contact' ).textContent() ).trim() === '+1 000 000 00 00' && ( await row.locator( '.column-trust_status' ).textContent() ).trim() === 'Новая' );
		await admin.screenshot( { path: path.join( OUT, 'leads-list.png' ) } );

		await admin.selectOption( 'select[name="trust_form"]', 'test' );
		await Promise.all( [ admin.waitForNavigation(), admin.click( '#post-query-submit' ) ] );
		const forms = ( await admin.locator( '.wp-list-table tbody .column-trust_form' ).allTextContents() ).map( ( s ) => s.trim() );
		check( 'Фильтр по форме', forms.length >= 3 && forms.every( ( f ) => f === 'Тестовая форма' ), forms.join( ', ' ) );

		await admin.goto( `${ URL }/wp-admin/post.php?post=${ leadId }&action=edit` );
		const box = ( await admin.locator( '#trust-lead-box' ).textContent() ).replace( /\s+/g, ' ' );
		check( 'Экран заявки: поля, страница, UTM, источник, согласие и документ', [ 'Обратный звонок', 'Имя', 'Анна', 'Телефон', 'Контакты', 'yandex', 'seo-audit', 'Откуда пришёл', 'Первая страница визита', 'Согласие на обработку персональных данных', 'Документ согласия', 'редакция от' ].every( ( s ) => box.includes( s ) ), box.slice( 0, 300 ) );
		check( 'Экран заявки: ссылка на страницу отправки', ( await admin.getAttribute( '#trust-lead-box a[target="_blank"]', 'href' ) ) === `${ URL }/kontakty/` );
		await admin.screenshot( { path: path.join( OUT, 'leads-screen.png' ), fullPage: true } );
		await admin.selectOption( '#trust-lead-status', 'work' );
		await Promise.all( [ admin.waitForNavigation(), admin.click( '#publish' ) ] );
		check( 'Статус на экране заявки: «В работе» сохранён', meta( leadId, 'trust_status' ) === 'work' && ( await admin.inputValue( '#trust-lead-status' ) ) === 'work' );

		await admin.goto( `${ URL }/wp-admin/edit.php?post_type=lead&trust_status=work` );
		const works = await admin.locator( '.wp-list-table tbody tr:not(.no-items)' ).count();
		check( 'Фильтр по статусу «В работе»', works === 1 && ( await admin.locator( `#post-${ leadId }` ).count() ) === 1, String( works ) );

		// Массовая смена статуса и CSV.
		const csvLead = String( ( await post( 'callback', { ...callback, name: '=HYPERLINK("http://evil")', page: `${ URL }/` } ) ).body?.lead || '' );
		await admin.goto( `${ URL }/wp-admin/edit.php?post_type=lead&trust_form=callback` );
		await admin.check( `#cb-select-${ leadId }` );
		await admin.check( `#cb-select-${ csvLead }` );
		await admin.selectOption( '#bulk-action-selector-top', 'trust_status_closed' );
		await Promise.all( [ admin.waitForNavigation(), admin.click( '#doaction' ) ] );
		check( 'Массово: статус «Закрыта» и сообщение', meta( leadId, 'trust_status' ) === 'closed' && meta( csvLead, 'trust_status' ) === 'closed' && ( await admin.locator( '.notice-success' ).first().textContent() ).includes( 'Статус изменён у заявок: 2' ) );
		await admin.check( `#cb-select-${ leadId }` );
		await admin.check( `#cb-select-${ csvLead }` );
		await admin.selectOption( '#bulk-action-selector-top', 'trust_export_csv' );
		const [ download ] = await Promise.all( [ admin.waitForEvent( 'download' ), admin.click( '#doaction' ) ] );
		const csv = fs.readFileSync( await download.path(), 'utf8' );
		const lines = csv.replace( /^\uFEFF/, '' ).trim().split( /\r?\n/ );
		check( 'CSV: файл leads-ДАТА.csv, UTF-8 с BOM, разделитель «;»', /^leads-\d{4}-\d\d-\d\d\.csv$/.test( download.suggestedFilename() ) && csv.startsWith( '\uFEFF' ) && lines[ 0 ].startsWith( 'ID;Дата;Форма;Статус;Имя;Контакт;Поля;Страница;utm_source' ), lines[ 0 ] );
		check( 'CSV: две заявки, статус, UTM, согласие', lines.length === 3 && csv.includes( ';Закрыта;Анна;' ) && csv.includes( ';yandex;cpc;seo-audit;' ) && csv.includes( 'Согласие на обработку персональных данных' ) );
		check( 'CSV: формулы экранированы', csv.includes( `"'=HYPERLINK(""http://evil"")"` ) && ! /;=HYPERLINK/.test( csv ), lines.find( ( l ) => l.includes( 'HYPERLINK' ) ) );

		// Вкладка «Формы».
		await admin.goto( `${ URL }/wp-admin/admin.php?page=trust-settings` );
		await admin.locator( '.acf-tab-wrap .acf-tab-button', { hasText: 'Формы' } ).click();
		await admin.selectOption( '[data-name="forms_captcha"] select', 'recaptcha' );
		const visible = async ( name ) => admin.locator( `[data-name="${ name }"]` ).isVisible();
		check( 'Формы: reCAPTCHA — видны её ключи, ключи SmartCaptcha скрыты', await visible( 'recaptcha_site_key' ) && await visible( 'recaptcha_secret_key' ) && ! ( await visible( 'smartcaptcha_client_key' ) ) );
		check( 'Формы: секретный ключ — поле пароля', ( await admin.getAttribute( '[data-name="recaptcha_secret_key"] input', 'type' ) ) === 'password' );
		await admin.selectOption( '[data-name="forms_captcha"] select', 'smartcaptcha' );
		check( 'Формы: SmartCaptcha — видны её ключи', await visible( 'smartcaptcha_server_key' ) && ! ( await visible( 'recaptcha_site_key' ) ) );
		check( 'Формы: срок хранения заявок', await visible( 'forms_retention_days' ) );
		await admin.screenshot( { path: path.join( OUT, 'settings-forms.png' ), fullPage: true } );
		await login( admin, 'editor', 'editor' );
		await admin.goto( `${ URL }/wp-admin/edit.php?post_type=lead` );
		check( 'Редактор видит заявки', ( await admin.locator( `#post-${ leadId }` ).count() ) === 1 );
		check( 'Консоль админки без ошибок', adminErrors.length === 0, adminErrors.slice( 0, 3 ).join( ' | ' ) );
		await admin.close();

		// --- Срок хранения ---
		const oldLead = wp( 'eval', 'echo wp_insert_post( array( "post_type" => "lead", "post_status" => "publish", "post_title" => "Старая заявка", "post_date" => wp_date( "Y-m-d H:i:s", time() - 40 * DAY_IN_SECONDS ) ) );' );
		const events = JSON.parse( wp( 'cron', 'event', 'list', '--format=json', '--fields=hook,recurrence' ) );
		check( 'WP-Cron: ежедневная задача trust_leads_cleanup', events.some( ( e ) => e.hook === 'trust_leads_cleanup' && /1 day|день|Once Daily/i.test( e.recurrence ) ), JSON.stringify( events.filter( ( e ) => e.hook === 'trust_leads_cleanup' ) ) );
		wp( 'eval', 'Trust\\Forms\\Leads::cleanup();' );
		check( 'Срок не задан — старые заявки не удаляются', wp( 'post', 'list', '--post_type=lead', `--post__in=${ oldLead }`, '--format=count' ) === '1' );
		setSettings( { forms_retention_days: '30' } );
		const removed = wp( 'eval', 'echo Trust\\Forms\\Leads::cleanup();' );
		check( 'Срок 30 дней — заявка 40-дневной давности удалена, свежие на месте', removed === '1' && wp( 'post', 'list', '--post_type=lead', `--post__in=${ oldLead }`, '--format=count' ) === '0' && meta( leadId, 'trust_form' ) === 'callback' );
		const unscheduled = wp( 'eval', 'do_action( "switch_theme", "x", new WP_Theme( "x", "" ), new WP_Theme( "y", "" ) ); echo wp_next_scheduled( "trust_leads_cleanup" ) ? "есть" : "нет";' );
		check( 'Смена темы снимает задачу', unscheduled === 'нет' );
	} finally {
		await browser.close();
		fs.rmSync( MU_PLUGIN, { force: true } );
		[ ...leadIds() ].filter( ( id ) => ! before.has( id ) ).forEach( ( id ) => wp( 'post', 'delete', id, '--force' ) );
		wp( 'eval', 'delete_option( "trust_test_captcha" );' );
		setSettings( Object.fromEntries( SETTINGS.map( ( k ) => [ k, saved[ k ] ?? '' ] ) ) );
		wp( 'eval', 'Trust\\Forms\\Leads::schedule();' );
		resetFormLimits();
	}

	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
