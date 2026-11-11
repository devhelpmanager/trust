/**
 * Тест страницы «Настройки сайта» (шаг 1.2) на локальном стенде.
 *
 * Запуск: node wp-theme/tests/settings.js (стенд запущен, демо-контент импортирован).
 *
 * Проверяет:
 * - пункт меню и страница настроек у администратора, 5 вкладок;
 * - на вкладках есть нужные поля, демо-значения подставлены;
 * - поле «Код интерактивной карты» видно администратору и скрыто от редактора;
 * - редактор открывает страницу настроек, автор — нет;
 * - сохранение без изменений проходит, значения не теряются, повторители без дублей;
 * - консоль браузера и журнал ошибок WordPress чистые.
 */

const { chromium } = require( 'playwright' );
const path = require( 'path' );
const { URL, OUT, check, finish, wp, clearLog, checkLog, login, collectConsoleErrors, ensureUser } = require( './lib' );

const PAGE = `${ URL }/wp-admin/admin.php?page=trust-settings`;
const TABS = [ 'Компания', 'Контакты', 'Соцсети', 'Подвал', 'Блоки по умолчанию', 'Адреса' ];

( async () => {
	clearLog();
	ensureUser( 'editor', 'editor' );
	ensureUser( 'author', 'author' );

	const browser = await chromium.launch();
	const page = await browser.newPage( { viewport: { width: 1280, height: 900 } } );
	const errors = collectConsoleErrors( page );

	// --- Администратор ---
	await login( page );
	await page.goto( `${ URL }/wp-admin/` );
	check( 'Пункт меню «Настройки сайта»', ( await page.locator( '#adminmenu a[href="admin.php?page=trust-settings"]' ).count() ) === 1 );

	await page.goto( PAGE );
	check( 'Заголовок страницы', ( await page.locator( 'h1' ).first().textContent() ).includes( 'Настройки сайта' ) );

	const tabs = ( await page.locator( '.acf-tab-wrap .acf-tab-button' ).allTextContents() ).map( ( t ) => t.trim() );
	check( 'Вкладки: ' + TABS.join( ', ' ), JSON.stringify( tabs ) === JSON.stringify( TABS ), tabs.join( ', ' ) );

	const value = ( name ) => page.locator( `[data-name="${ name }"] input[type="text"], [data-name="${ name }"] input[type="email"], [data-name="${ name }"] input[type="url"], [data-name="${ name }"] textarea` ).first().inputValue();
	check( 'Бренд из демо', ( await value( 'company_brand' ) ) === '[Бренд]' );
	check( 'Подпись из демо', ( await value( 'company_tagline' ) ) === 'SEO · сайты · реклама' );
	await page.screenshot( { path: path.join( OUT, 'settings-company.png' ), fullPage: true } );

	for ( const [ i, tab ] of TABS.entries() ) {
		await page.locator( '.acf-tab-wrap .acf-tab-button', { hasText: tab } ).click();
		if ( i > 0 ) {
			await page.screenshot( { path: path.join( OUT, `settings-tab-${ i + 1 }.png` ), fullPage: true } );
		}
	}

	await page.locator( '.acf-tab-wrap .acf-tab-button', { hasText: 'Контакты' } ).click();
	const phoneRows = await page.locator( '[data-name="phones"] .acf-row:not(.acf-clone)' ).count();
	check( 'Телефоны: 1 строка (без дублей после повторного импорта)', phoneRows === 1, String( phoneRows ) );
	check( 'Часы работы из демо', ( await value( 'hours' ) ) === '[Пн–Пт, 10:00–19:00]' );
	check( 'Администратор видит «Код интерактивной карты»', ( await page.locator( '[data-name="map_embed"]' ).count() ) === 1 );

	await page.locator( '.acf-tab-wrap .acf-tab-button', { hasText: 'Подвал' } ).click();
	const tags = await page.locator( '[data-name="footer_tags"] .acf-row:not(.acf-clone)' ).count();
	check( 'Подвал: 5 плашек', tags === 5, String( tags ) );

	// Сохранение без изменений.
	await Promise.all( [ page.waitForNavigation(), page.locator( '#publish, input[type="submit"][name="publish"], .acf-settings-wrap input[type="submit"]' ).first().click() ] );
	const saved = await page.locator( '.notice-success, #message.updated' ).first().textContent().catch( () => '' );
	check( 'Сохранение: сообщение «Настройки сохранены.»', ( saved || '' ).includes( 'Настройки сохранены' ), ( saved || '' ).trim() );
	const after = wp( 'eval', 'echo Trust\\Settings::brand(), "|", count( Trust\\Contacts::phones() ), "|", count( Trust\\Settings::get( "footer_tags", array() ) );' );
	check( 'После сохранения значения на месте', after === '[Бренд]|1|5', after );
	const frozen = wp( 'eval', 'echo count( array_filter( array_map( static fn( $k ) => (string) get_field( "field_trust_settings_callback_" . $k, "option" ), array_keys( Trust\\Callback::defaults() ) ) ) );' );
	check( 'Сохранение не записывает тексты окна звонка по умолчанию в базу', frozen === '0', `записано: ${ frozen }` );

	// --- Редактор ---
	await login( page, 'editor', 'editor' );
	const editorResponse = await page.goto( PAGE );
	check( 'Редактор открывает «Настройки сайта»', editorResponse.status() === 200 && ( await page.locator( '.acf-tab-wrap' ).count() ) === 1 );
	const editorTabs = ( await page.locator( '.acf-tab-wrap .acf-tab-button' ).allTextContents() ).map( ( t ) => t.trim() );
	check( 'Редактор не видит техническую вкладку «Адреса»', JSON.stringify( editorTabs ) === JSON.stringify( TABS.filter( ( t ) => t !== 'Адреса' ) ), editorTabs.join( ', ' ) );
	await page.locator( '.acf-tab-wrap .acf-tab-button', { hasText: 'Контакты' } ).click();
	check( 'Редактор не видит «Код интерактивной карты»', ( await page.locator( '[data-name="map_embed"]' ).count() ) === 0 );
	check( 'Редактор видит телефоны', ( await page.locator( '[data-name="phones"]' ).count() ) === 1 );

	// Ошибки консоли считаем до проверки автора: отказ ему (403) — ожидаемый ответ.
	const realErrors = [ ...errors ];

	// --- Автор ---
	await login( page, 'author', 'author' );
	const authorResponse = await page.goto( PAGE );
	check( 'Автору страница настроек недоступна (403)', authorResponse.status() === 403 && ( await page.locator( '.acf-tab-wrap' ).count() ) === 0, String( authorResponse.status() ) );

	await browser.close();

	check( 'Консоль браузера без ошибок', realErrors.length === 0, realErrors.slice( 0, 3 ).join( ' | ' ) );
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
