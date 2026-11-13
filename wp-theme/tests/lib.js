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
 * Cookie выбора «только обязательные» (как после нажатия на плашке cookie, шаг 3.7): в тестах
 * плашка не закрывает низ страницы. Тест плашки (cookie.js) запускает браузер без неё.
 */
function consentCookie() {
	const value = { v: '1', necessary: true, analytics: false, marketing: false, choice: 'necessary', date: '2026-01-01T00:00:00.000Z' };
	return { name: 'trust_consent', value: encodeURIComponent( JSON.stringify( value ) ), url: URL };
}

/**
 * Запускает Chromium: у каждого нового контекста (и страницы browser.newPage) уже сделан выбор
 * cookie — consentCookie(). login() сохраняет этот выбор.
 *
 * @param {Object} options Параметры chromium.launch.
 */
async function launch( options = {} ) {
	const { chromium } = require( 'playwright' );
	const browser = await chromium.launch( options );
	const newContext = browser.newContext.bind( browser );
	browser.newContext = async ( opts ) => {
		const context = await newContext( opts );
		context.trustConsent = true;
		await context.addCookies( [ consentCookie() ] );
		return context;
	};
	browser.newPage = async ( opts ) => {
		const context = await browser.newContext( opts );
		const page = await context.newPage();
		page.on( 'close', () => context.close().catch( () => {} ) );
		return page;
	};
	return browser;
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
	if ( page.context().trustConsent ) {
		await page.context().addCookies( [ consentCookie() ] );
	}
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
 * Правки макетов, согласованные с заказчиком после их экспорта (docs/inventory.md, раздел 12):
 * перед сравнением с темой они вносятся и в HTML-экспорт макета.
 * - Тексты: пункт меню «Сопровождение сайтов» переименован в «Поддержка сайтов».
 * - CSS по файлу макета: у «Вопрос-ответ» заголовок над вопросами на всех ширинах; у цифр
 *   первого экрана раздела значения прижаты к верху (на одной линии при подписи в две строки);
 *   H1 первых экранов — на всю ширину (у услуги и документа — отдельной строкой над текстом
 *   и кнопками, у вопросов — над текстом и формой).
 */
const MOCKUP_TEXTS = [ [ 'Сопровождение сайтов', 'Поддержка сайтов' ] ];
const HERO_SECTION_CSS = `
	.dc-hero-box dl > div { justify-content: flex-end !important; }
	.dc-hero-in > div:first-child { max-width: none !important; }
	.dc-hero-in > div:first-child > p { max-width: 820px !important; }
`;
const HERO_DOCUMENT_CSS = `
	.dc-hero-in > div:first-child { display: contents !important; }
	.dc-hero-in > div:first-child > span { flex: 0 0 100% !important; max-width: max-content !important; margin-bottom: -12px !important; }
	.dc-hero-in > div:first-child > h1 { flex: 0 0 100% !important; margin-bottom: -12px !important; }
	.dc-hero-in > div:first-child > p { flex: 1 1 520px !important; min-width: 0 !important; }
`;
// Правка заказчика для всех макетов: отступы секций 64 / 56 / 48 px вместо 96 (--section-y в main.css).
const SECTION_SPACING_CSS = `
	[style*="padding: 96px clamp(20px, 4vw, 48px)"] { padding-top: 64px !important; padding-bottom: 64px !important; }
	@media (max-width: 1100px) {
		[style*="padding: 96px clamp(20px, 4vw, 48px)"] { padding-top: 56px !important; padding-bottom: 56px !important; }
	}
	@media (max-width: 760px) {
		[style*="padding: 96px clamp(20px, 4vw, 48px)"] { padding-top: 48px !important; padding-bottom: 48px !important; }
	}
	/* Подвал: сверху 40 px, на телефоне 32 (в макетах 72) */
	footer.dc-foot { padding-top: 40px !important; }
	@media (max-width: 760px) {
		footer.dc-foot { padding-top: 32px !important; }
	}
`;
// Правки заказчика в «Этапах работы»: номер — в скруглённом прямоугольнике на всю ширину карточки
// (без линий и стрелок), от вводного текста до этапов 32 px (на телефоне 24), у раскрытого этапа
// другой фон карточки.
const STAGES_OPEN_CSS = `
	ol.dc-steps > li > div:first-child { justify-content: center !important; gap: 10px !important; height: 36px !important; box-sizing: border-box !important; border: 2px solid #6D28D9 !important; border-radius: 10px !important; background: #FFFFFF !important; }
	ol.dc-steps > li > div:first-child > span:first-child { width: auto !important; height: auto !important; border: 0 !important; border-radius: 0 !important; background: transparent !important; }
	ol.dc-steps > li:not(:last-child) > div:first-child > :not(:first-child), ol.dc-steps .dc-step-down { display: none !important; }
	ol.dc-steps > li:last-child > div:first-child { border-color: #5B21B6 !important; background: #5B21B6 !important; }
	ol.dc-steps > li:last-child > div:first-child > span { color: #FFFFFF !important; }
	section:has(> div > ol.dc-steps) > div { gap: 32px !important; }
	@media (max-width: 760px) { section:has(> div > ol.dc-steps) > div { gap: 24px !important; } }
	ol.dc-steps > li > button[aria-expanded="true"] { background: #F8F5FF !important; border-color: #CDBDFF !important; }
	ol.dc-steps > li:last-child > button[aria-expanded="true"] { background: #4C1D95 !important; border-color: #4C1D95 !important; }
`;
const MOCKUP_CSS = {
	'seo-bloki/06-etapy-raboty.html': STAGES_OPEN_CSS,
	'portfolio-blog-404/portfolio.html': HERO_SECTION_CSS,
	'o-nas/o-kompanii.html': HERO_SECTION_CSS,
	'o-nas/komanda.html': HERO_SECTION_CSS,
	'o-nas/karera.html': HERO_SECTION_CSS,
	'o-nas/kontakty.html': `
		.dc-hero-in > div:first-child { max-width: none !important; }
		.dc-hero-in > div:first-child > p { max-width: 760px !important; }
	`,
	'o-nas/otzyvy.html': HERO_SECTION_CSS,
	'o-nas/oferta.html': HERO_DOCUMENT_CSS,
	'o-nas/politika-cookie.html': HERO_DOCUMENT_CSS,
	'o-nas/soglasie-pd.html': HERO_DOCUMENT_CSS,
	'seo-vopros/vopros-seo.html': `
		.dc-ask-intro { display: contents !important; }
		.dc-ask-intro > span { grid-area: badge; justify-self: start; margin-bottom: -6px !important; }
		.dc-ask-intro > h1 { grid-area: title; margin-bottom: -6px !important; }
		.dc-ask-intro > p { grid-area: intro; }
		.dc-ask-in { grid-template-areas: "badge" "title" "intro" "form" "extra" !important; }
		@media (min-width: 1000px) {
			.dc-ask-in { grid-template-areas: "badge badge" "title title" "intro form" "extra form" !important; grid-template-rows: auto auto auto 1fr !important; }
			.dc-ask-intro > span, .dc-ask-intro > h1 { margin-bottom: 0 !important; }
		}
		@media (max-width: 640px) {
			.dc-ask-intro > span, .dc-ask-intro > h1 { margin-bottom: -8px !important; }
		}
	`,
	'seo-stranitsa/index.html': STAGES_OPEN_CSS + `
		main > section:first-of-type > div > div > div:first-child { display: contents !important; }
		main > section:first-of-type h1 { flex: 0 0 100% !important; margin: 0 0 -8px !important; }
		main > section:first-of-type h1 + p { flex: 1 1 520px !important; max-width: 780px !important; min-width: 0 !important; }
		main > section:first-of-type { padding-bottom: 48px !important; }
		@media (max-width: 1100px) {
			main > section:first-of-type { padding-bottom: 40px !important; }
		}
		@media (max-width: 760px) {
			main > section:first-of-type { padding-bottom: 32px !important; }
		}
		@media (max-width: 480px) {
			main > section:first-of-type h1 + p { font-size: 17px !important; }
			main > section:first-of-type div:has(> a[href="#order"]) { gap: 8px !important; }
			main > section:first-of-type a[href="#order"], main > section:first-of-type a[href="#calculator"] {
				min-height: 48px !important; padding: 0 14px !important; font-size: 14px !important; white-space: nowrap !important; box-sizing: border-box !important;
			}
		}
	`,
	'seo-bloki/10-vopros-otvet.html': `
		section > div { flex-direction: column !important; flex-wrap: nowrap !important; align-items: stretch !important; gap: 40px !important; }
		section > div > div:first-child { flex: none !important; max-width: 760px !important; }
		section > div > div:last-child { flex: none !important; }
		section > div > div:last-child p { max-width: 860px !important; box-sizing: border-box !important; }
	`,
};

/**
 * Вносит согласованные правки в открытый макет (только страницы file://, тему не трогает).
 * Повторный вызов безопасен; после перерисовки макета (клик по кнопке) — вызвать снова.
 *
 * @param {import('playwright').Page} page Страница.
 */
async function approveMockup( page ) {
	if ( ! page.url().startsWith( 'file://' ) ) {
		return;
	}
	await page.evaluate( ( { texts, css, common } ) => {
		const walker = document.createTreeWalker( document.body, NodeFilter.SHOW_TEXT );
		for ( let node = walker.nextNode(); node; node = walker.nextNode() ) {
			for ( const [ from, to ] of texts ) {
				if ( node.nodeValue.includes( from ) ) {
					node.nodeValue = node.nodeValue.split( from ).join( to );
				}
			}
		}
		const file = decodeURIComponent( location.pathname );
		if ( ! document.getElementById( 'trust-approved-css' ) ) {
			const style = document.createElement( 'style' );
			style.id = 'trust-approved-css';
			style.textContent = common + Object.entries( css ).filter( ( [ key ] ) => file.endsWith( key ) ).map( ( [ , rules ] ) => rules ).join( '\n' );
			document.head.appendChild( style );
		}
	}, { texts: MOCKUP_TEXTS, css: MOCKUP_CSS, common: SECTION_SPACING_CSS } );
	await page.evaluate( () => document.fonts.ready );
}

/**
 * Ждёт загрузки шрифтов и небольшой паузы для анимаций; в макете — согласованные правки.
 *
 * @param {import('playwright').Page} page Страница.
 */
async function settle( page ) {
	await page.evaluate( () => document.fonts.ready );
	await approveMockup( page );
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
	await approveMockup( page );
	await page.evaluate( ( sel ) => {
		let el = document.querySelector( sel );
		// Блок — первым среди соседей: снимок блока сам по себе, без правила «два блока одного
		// фона подряд — у второго нет верхнего отступа» (оно проверяется отдельно).
		if ( el && el.previousElementSibling ) {
			el.parentElement.prepend( el );
		}
		// Рисунки справа от шапки секций — добавлены по просьбе заказчика, в макетах их нет: при
		// сверке не видны (место остаётся, шапка не сдвигается; сами рисунки проверяются отдельно).
		if ( 'file:' !== location.protocol ) {
			document.querySelectorAll( '.section-art' ).forEach( ( art ) => art.style.setProperty( 'visibility', 'hidden', 'important' ) );
		}
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

/**
 * Свежие токен и nonce форм (REST).
 *
 * @param {Object} headers Заголовки запроса.
 * @return {Promise<Object>} { token, nonce, cache }.
 */
async function formToken( headers = {} ) {
	const res = await fetch( `${ URL }/wp-json/trust/v1/forms/token`, { headers } );
	return { ...( await res.json() ), cache: res.headers.get( 'cache-control' ) || '' };
}

/**
 * Отправляет форму через REST.
 *
 * @param {string} type    Тип формы.
 * @param {Object} fields  Поля.
 * @param {Object} headers Заголовки запроса.
 * @return {Promise<Object>} { status, body, text }.
 */
async function postForm( type, fields, headers = {} ) {
	const data = new FormData();
	for ( const [ key, value ] of Object.entries( fields ) ) {
		data.append( key, value );
	}
	const res = await fetch( `${ URL }/wp-json/trust/v1/forms/${ type }`, { method: 'POST', body: data, headers } );
	const text = await res.text();
	let body = null;
	try {
		body = JSON.parse( text );
	} catch ( e ) {}
	return { status: res.status, body, text };
}

module.exports = {
	URL, DEV_DIR, LOG, OUT, REPO, THEME,
	launch, consentCookie,
	check, finish, wp, clearLog, checkLog, login, collectConsoleErrors, ensureUser,
	routeMockupFonts, mockupUrl, approveMockup, comparePng, settle, isolate, getSettings, setSettings, publishPost, resetFormLimits, formToken, postForm,
};
