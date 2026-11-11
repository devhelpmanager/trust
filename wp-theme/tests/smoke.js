/**
 * Дымовой тест темы на локальном стенде (wp-theme/bin/local.sh).
 *
 * Запуск: node wp-theme/tests/smoke.js
 * Переменные: TRUST_URL (по умолчанию http://localhost:8080), TRUST_DEV_DIR (~/wp-dev).
 *
 * Проверяет:
 * - главная отвечает 200, <html lang="ru-RU">, подключён main.css и предзагрузка шрифтов;
 * - нет emoji, jQuery, generator, RSD, shortlink, oEmbed-ссылок;
 * - шрифты темы загружаются, в консоли нет ошибок;
 * - ссылка «Перейти к содержимому» появляется при фокусе;
 * - axe-core: нет нарушений уровней critical и serious;
 * - админка с SCF — без уведомления темы, без SCF — уведомление с кнопкой, сайт открывается;
 * - журнал ошибок WordPress пуст.
 */

const { chromium } = require( 'playwright' );
const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const URL = process.env.TRUST_URL || 'http://localhost:8080';
const DEV_DIR = process.env.TRUST_DEV_DIR || path.join( os.homedir(), 'wp-dev' );
const LOG = path.join( DEV_DIR, 'debug.log' );
const LOCAL = path.join( __dirname, '..', 'bin', 'local.sh' );
const OUT = path.join( __dirname, 'output' );

const results = [];
function check( name, ok, details = '' ) {
	results.push( { name, ok: !! ok, details } );
	console.log( `${ ok ? '✓' : '✗' } ${ name }${ details ? ' — ' + details : '' }` );
}

function wp( ...args ) {
	return execFileSync( LOCAL, [ 'wp', ...args ], { encoding: 'utf8' } ).trim();
}

async function login( page ) {
	await page.goto( `${ URL }/wp-login.php` );
	await page.fill( '#user_login', 'admin' );
	await page.fill( '#user_pass', 'admin' );
	await Promise.all( [ page.waitForNavigation(), page.click( '#wp-submit' ) ] );
}

( async () => {
	fs.mkdirSync( OUT, { recursive: true } );
	if ( fs.existsSync( LOG ) ) {
		fs.writeFileSync( LOG, '' );
	}

	const browser = await chromium.launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 800 } } );
	const page = await context.newPage();
	const consoleErrors = [];
	page.on( 'console', ( msg ) => msg.type() === 'error' && consoleErrors.push( msg.text() ) );
	page.on( 'pageerror', ( err ) => consoleErrors.push( err.message ) );

	// --- Главная ---
	const response = await page.goto( `${ URL }/`, { waitUntil: 'networkidle' } );
	check( 'Главная отвечает 200', response.status() === 200, String( response.status() ) );

	const html = await page.content();
	check( 'Язык страницы ru-RU', ( await page.getAttribute( 'html', 'lang' ) ) === 'ru-RU' );
	check( 'Подключён main.css', /id=['"]trust-main-css['"]/.test( html ) );
	check( 'Предзагрузка 2 шрифтов', ( html.match( /rel="preload"[^>]+as="font"/g ) || [] ).length === 2 );

	const forbidden = {
		emoji: /wp-emoji|emoji-release|_wpemojiSettings/,
		jQuery: /jquery(\.min)?\.js/,
		generator: /<meta name="generator"/,
		RSD: /rel="EditURI"/,
		shortlink: /rel=['"]shortlink['"]/,
		oEmbed: /application\/json\+oembed/,
	};
	for ( const [ name, re ] of Object.entries( forbidden ) ) {
		check( `Нет ${ name }`, ! re.test( html ) );
	}

	const fonts = await page.evaluate( async () => {
		await document.fonts.ready;
		const probe = document.createElement( 'div' );
		probe.innerHTML = '<span style="font:600 20px Unbounded">Аа</span><span style="font:400 16px Onest">Аа</span><span style="font:500 13px \'JetBrains Mono\'">Аа</span>';
		document.body.appendChild( probe );
		await Promise.all( [ '600 20px Unbounded', '400 16px Onest', '500 13px "JetBrains Mono"' ].map( ( f ) => document.fonts.load( f, 'Аа' ) ) );
		const loaded = [ ...document.fonts ].filter( ( f ) => f.status === 'loaded' ).map( ( f ) => `${ f.family } ${ f.weight }` );
		probe.remove();
		return [ ...new Set( loaded ) ];
	} );
	check( 'Шрифты темы загружаются', [ 'Unbounded 600', 'Onest 400', 'JetBrains Mono 500' ].every( ( f ) => fonts.includes( f ) ), fonts.join( ', ' ) );

	await page.keyboard.press( 'Tab' );
	const skip = await page.evaluate( () => {
		const el = document.activeElement;
		const r = el.getBoundingClientRect();
		return { cls: el.className, text: el.textContent.trim(), visible: r.top >= 0 && r.height > 0 };
	} );
	check( 'Ссылка «Перейти к содержимому» первая по Tab и видна', skip.cls === 'skip-link' && skip.visible, skip.text );

	await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
	const axe = await page.evaluate( async () => {
		const r = await window.axe.run( document, { resultTypes: [ 'violations' ] } );
		return r.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length })` );
	} );
	check( 'axe: нет нарушений critical/serious', axe.length === 0, axe.join( ', ' ) );

	await page.screenshot( { path: path.join( OUT, 'smoke-home.png' ) } );

	// --- Админка с SCF ---
	await login( page );
	await page.goto( `${ URL }/wp-admin/` );
	check( 'Админка с SCF: уведомления темы нет', ( await page.locator( '.notice-error:has-text("Тема Trust")' ).count() ) === 0 );

	// --- Без SCF ---
	wp( 'plugin', 'deactivate', 'secure-custom-fields', '--quiet' );
	try {
		await page.goto( `${ URL }/wp-admin/` );
		const notice = page.locator( '.notice-error:has-text("Тема Trust")' );
		const button = notice.locator( 'a.button' );
		check( 'Без SCF: уведомление в админке', ( await notice.count() ) === 1 );
		check( 'Без SCF: кнопка «Активировать Secure Custom Fields»', ( await button.textContent() || '' ).includes( 'Активировать Secure Custom Fields' ) );
		await page.screenshot( { path: path.join( OUT, 'smoke-admin-no-scf.png' ) } );
		const front = await page.goto( `${ URL }/` );
		check( 'Без SCF: сайт открывается', front.status() === 200 );
	} finally {
		wp( 'plugin', 'activate', 'secure-custom-fields', '--quiet' );
	}

	await browser.close();

	check( 'Консоль браузера без ошибок', consoleErrors.length === 0, consoleErrors.slice( 0, 3 ).join( ' | ' ) );
	const log = fs.existsSync( LOG ) ? fs.readFileSync( LOG, 'utf8' ).trim() : '';
	check( 'Журнал ошибок WordPress пуст', log === '', log.split( '\n' ).slice( 0, 3 ).join( ' | ' ) );

	const failed = results.filter( ( r ) => ! r.ok ).length;
	console.log( `\n${ results.length - failed } из ${ results.length } проверок пройдено` );
	process.exit( failed ? 1 : 0 );
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
