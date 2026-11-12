/**
 * Общие помощники автотестов темы Trust.
 */

const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const URL = process.env.TRUST_URL || 'http://localhost:8080';
const DEV_DIR = process.env.TRUST_DEV_DIR || path.join( os.homedir(), 'wp-dev' );
const LOG = path.join( DEV_DIR, 'debug.log' );
const LOCAL = path.join( __dirname, '..', 'bin', 'local.sh' );
const OUT = path.join( __dirname, 'output' );

fs.mkdirSync( OUT, { recursive: true } );

/** Результаты проверок текущего теста. */
const results = [];

/**
 * Записывает результат проверки и печатает его.
 *
 * @param {string}  name    Что проверяем.
 * @param {boolean} ok      Пройдено ли.
 * @param {string}  details Подробности (необязательно).
 */
function check( name, ok, details = '' ) {
	results.push( { name, ok: !! ok, details } );
	console.log( `${ ok ? '✓' : '✗' } ${ name }${ details ? ' — ' + details : '' }` );
}

/**
 * Печатает итог и завершает процесс с кодом 1, если есть проваленные проверки.
 */
function finish() {
	const failed = results.filter( ( r ) => ! r.ok ).length;
	console.log( `\n${ results.length - failed } из ${ results.length } проверок пройдено` );
	process.exit( failed ? 1 : 0 );
}

/**
 * Команда WP-CLI на стенде.
 *
 * @param {...string} args Аргументы команды.
 * @return {string} Вывод команды.
 */
function wp( ...args ) {
	return execFileSync( LOCAL, [ 'wp', ...args ], {
		encoding: 'utf8',
		env: { ...process.env, TRUST_DEV_DIR: DEV_DIR },
		stdio: [ 'ignore', 'pipe', 'pipe' ],
	} ).trim();
}

/** Очищает журнал ошибок WordPress. */
function clearLog() {
	if ( fs.existsSync( LOG ) ) {
		fs.writeFileSync( LOG, '' );
	}
}

/**
 * Проверяет, что журнал ошибок WordPress пуст.
 */
function checkLog() {
	const log = fs.existsSync( LOG ) ? fs.readFileSync( LOG, 'utf8' ).trim() : '';
	check( 'Журнал ошибок WordPress пуст', log === '', log.split( '\n' ).slice( 0, 3 ).join( ' | ' ) );
}

/**
 * Входит в админку.
 *
 * @param {import('playwright').Page} page     Страница.
 * @param {string}                    user     Логин.
 * @param {string}                    password Пароль.
 */
async function login( page, user = 'admin', password = 'admin' ) {
	await page.context().clearCookies();
	await page.goto( `${ URL }/wp-login.php` );
	await page.fill( '#user_login', user );
	await page.fill( '#user_pass', password );
	await Promise.all( [ page.waitForNavigation(), page.click( '#wp-submit' ) ] );
}

/**
 * Собирает ошибки консоли и необработанные исключения страницы.
 *
 * @param {import('playwright').Page} page Страница.
 * @return {string[]} Массив, который пополняется по ходу теста.
 */
function collectConsoleErrors( page ) {
	const errors = [];
	page.on( 'console', ( msg ) => msg.type() === 'error' && errors.push( `${ page.url() }: ${ msg.text() }` ) );
	page.on( 'pageerror', ( err ) => errors.push( `${ page.url() }: ${ err.message }` ) );
	return errors;
}

/**
 * Создаёт пользователя на стенде, если его ещё нет.
 *
 * @param {string} login Логин (он же пароль).
 * @param {string} role  Роль.
 */
function ensureUser( login, role ) {
	try {
		wp( 'user', 'get', login, '--field=ID' );
	} catch ( e ) {
		wp( 'user', 'create', login, `${ login }@example.test`, `--role=${ role }`, `--user_pass=${ login }`, '--quiet' );
	}
}

const REPO = path.join( __dirname, '..', '..' );
const THEME = path.join( REPO, 'wp-theme', 'trust' );

/**
 * Подменяет Google Fonts в HTML-экспортах макетов локальными шрифтами темы —
 * эталон и тема рисуются одними и теми же файлами шрифтов.
 *
 * @param {import('playwright').BrowserContext} context Контекст браузера.
 */
async function routeMockupFonts( context ) {
	const css = fs.readFileSync( path.join( THEME, 'assets/css/main.css' ), 'utf8' )
		.split( '\n' )
		.filter( ( line ) => line.startsWith( '@font-face' ) )
		.map( ( line ) => line.replace( /url\(\.\.\/fonts\//g, 'url(https://fonts.gstatic.com/local/' ) )
		.join( '\n' );
	const cors = { 'access-control-allow-origin': '*' };
	await context.route( 'https://fonts.googleapis.com/**', ( route ) => route.fulfill( { status: 200, contentType: 'text/css', headers: cors, body: css } ) );
	await context.route( 'https://fonts.gstatic.com/local/**', ( route ) => {
		const file = path.join( THEME, 'assets/fonts', path.basename( new globalThis.URL( route.request().url() ).pathname ) );
		return route.fulfill( { status: 200, contentType: 'font/woff2', headers: cors, body: fs.readFileSync( file ) } );
	} );
}

/**
 * Адрес HTML-экспорта макета (file://).
 *
 * @param {string} relative Путь от корня репозитория, например portfolio-blog-404/portfolio.html.
 */
function mockupUrl( relative ) {
	return 'file://' + path.join( REPO, relative );
}

/**
 * Сравнивает два PNG попиксельно, сохраняет картинку разницы.
 *
 * @param {string} a        Эталон.
 * @param {string} b        Тема.
 * @param {string} diffPath Куда сохранить разницу.
 * @return {number} Доля отличающихся пикселей, %.
 */
function comparePng( a, b, diffPath ) {
	const { PNG } = require( 'pngjs' );
	const pixelmatch = require( 'pixelmatch' );
	const imgA = PNG.sync.read( fs.readFileSync( a ) );
	const imgB = PNG.sync.read( fs.readFileSync( b ) );
	if ( imgA.width !== imgB.width || imgA.height !== imgB.height ) {
		return 100;
	}
	const diff = new PNG( { width: imgA.width, height: imgA.height } );
	const count = pixelmatch( imgA.data, imgB.data, diff.data, imgA.width, imgA.height, { threshold: 0.1 } );
	fs.writeFileSync( diffPath, PNG.sync.write( diff ) );
	return ( 100 * count ) / ( imgA.width * imgA.height );
}

/**
 * Ждёт загрузки шрифтов и небольшой паузы для анимаций.
 *
 * @param {import('playwright').Page} page Страница.
 */
async function settle( page ) {
	await page.evaluate( () => document.fonts.ready );
	await page.waitForTimeout( 300 );
}

/**
 * Скрывает всё, что стоит на странице перед элементом (на всех уровнях вложенности),
 * чтобы элемент начинался с верха страницы. Так снимки темы и макета не расходятся
 * из-за дробных координат и сглаживания.
 *
 * @param {import('playwright').Page} page     Страница.
 * @param {string}                    selector Элемент.
 */
async function isolate( page, selector ) {
	await page.evaluate( ( sel ) => {
		let el = document.querySelector( sel );
		while ( el && el !== document.body ) {
			let prev = el.previousElementSibling;
			while ( prev ) {
				prev.style.setProperty( 'display', 'none', 'important' );
				prev = prev.previousElementSibling;
			}
			el = el.parentElement;
		}
		window.scrollTo( 0, 0 );
	}, selector );
}

/**
 * Текущие значения настроек сайта (в виде, как их отдаёт SCF).
 *
 * @param {string[]} names Имена полей.
 * @return {Object} Имя поля => значение.
 */
function getSettings( names ) {
	const list = names.map( ( n ) => `'${ n }'` ).join( ',' );
	return JSON.parse( wp( 'eval', `$o = array(); foreach ( array( ${ list } ) as $n ) { $o[ $n ] = get_field( Trust\\Fields::key( Trust\\Settings::PREFIX, $n ), 'option' ); } echo wp_json_encode( $o );` ) );
}

/**
 * Записывает настройки сайта (для тестов: подставить данные и потом вернуть прежние).
 *
 * @param {Object} values Имя поля => значение.
 */
function setSettings( values ) {
	const b64 = Buffer.from( JSON.stringify( values ) ).toString( 'base64' );
	wp( 'eval', `foreach ( json_decode( base64_decode( '${ b64 }' ), true ) as $n => $v ) { update_field( Trust\\Fields::key( Trust\\Settings::PREFIX, $n ), $v, 'option' ); }` );
}

/**
 * Нажимает «Опубликовать» на классическом экране и ждёт перехода на страницу записи.
 * Если SCF не пропустил форму — ошибка с его сообщениями.
 *
 * @return {Promise<string>} ID сохранённой записи.
 */
async function publishPost( page ) {
	// Пока WordPress автосохраняет черновик, кнопка помечена disabled и нажатие игнорируется.
	for ( let attempt = 0; attempt < 3; attempt++ ) {
		await page.waitForFunction( () => ! document.querySelector( '#publish' ).classList.contains( 'disabled' ), null, { timeout: 15000 } );
		await page.click( '#publish' );
		try {
			await page.waitForURL( /post\.php\?post=\d+/, { timeout: 10000 } );
			await page.waitForLoadState( 'load' );
			return new globalThis.URL( page.url() ).searchParams.get( 'post' );
		} catch ( e ) {
			const messages = await page.locator( '.acf-notice.-error, .acf-error-message, .notice-error' ).allInnerTexts();
			if ( messages.length ) {
				throw new Error( `Запись не сохранилась: ${ messages.join( ' | ' ) }` );
			}
		}
	}
	throw new Error( 'Запись не сохранилась: нажатие «Опубликовать» не сработало три раза' );
}

/**
 * Сбрасывает лимит частоты отправки форм (все тесты идут с одного адреса 127.0.0.1).
 */
function resetFormLimits() {
	wp( 'eval', "global $wpdb; foreach ( $wpdb->get_col( \"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_trust_form_rate_%'\" ) as $o ) { delete_transient( substr( $o, strlen( '_transient_' ) ) ); }" );
}

module.exports = {
	URL, DEV_DIR, LOG, OUT, REPO, THEME,
	check, finish, wp, clearLog, checkLog, login, collectConsoleErrors, ensureUser,
	routeMockupFonts, mockupUrl, comparePng, settle, isolate, getSettings, setSettings, publishPost, resetFormLimits,
};
