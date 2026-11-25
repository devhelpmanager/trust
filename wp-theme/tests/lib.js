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
// Тексты, согласованные с заказчиком: [ было, стало, только в этих макетах (необязательно) ].
// Все цены сайта — в долларах (решение заказчика): в макетах услуг, блоков и портфолио ₽ → $.
const MOCKUP_TEXTS = [
	[ 'Сопровождение сайтов', 'Поддержка сайтов' ],
	[ '₽', '$', [ 'seo-bloki/', 'seo-stranitsa/', 'portfolio-blog-404/', 'o-nas/' ] ],
	// Демо-телефон и WhatsApp — с кодом +1 (правка заказчика).
	[ '+7 (000) ', '+1 (000) ' ],
	// Отдел «Менеджеры» в «Команде» — тот же термин «Менеджмент», что в «Карьере».
	[ 'Менеджеры', 'Менеджмент', [ 'o-nas/komanda.html' ] ],
	// Кнопка окна звонка — «Связаться с нами» (правка заказчика к окну «Заказать обратный звонок»).
	[ 'Заказать обратный звонок', 'Связаться с нами' ],
	// Автор видеоотзыва: должность и компания — отдельные поля отзыва.
	[ '[должность, компания]', '[Должность], [Компания]', [ 'o-nas/otzyvy.html' ] ],
];
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
	[style*="padding: 96px clamp(20px, 4vw, 48px)"], [style*="padding: 88px clamp(20px, 4vw, 48px)"] { padding-top: 64px !important; padding-bottom: 64px !important; }
	@media (max-width: 1100px) {
		[style*="padding: 96px clamp(20px, 4vw, 48px)"], [style*="padding: 88px clamp(20px, 4vw, 48px)"] { padding-top: 56px !important; padding-bottom: 56px !important; }
	}
	@media (max-width: 760px) {
		[style*="padding: 96px clamp(20px, 4vw, 48px)"], [style*="padding: 88px clamp(20px, 4vw, 48px)"] { padding-top: 48px !important; padding-bottom: 48px !important; }
	}
	/* «Главная мысль» (112 px) и «Картинка» (64 px) — отступ секции, как у всех блоков */
	[style*="padding: 112px clamp(20px, 4vw, 48px)"] { padding-top: 64px !important; padding-bottom: 64px !important; }
	@media (max-width: 1100px) {
		[style*="padding: 112px clamp(20px, 4vw, 48px)"] { padding-top: 56px !important; padding-bottom: 56px !important; }
		[style*="padding: 64px clamp(20px, 4vw, 48px)"] { padding-top: 56px !important; padding-bottom: 56px !important; }
	}
	@media (max-width: 760px) {
		[style*="padding: 112px clamp(20px, 4vw, 48px)"] { padding-top: 48px !important; padding-bottom: 48px !important; }
		[style*="padding: 64px clamp(20px, 4vw, 48px)"] { padding-top: 48px !important; padding-bottom: 48px !important; }
	}
	/* «Обсудим ваш проект» и «Задать вопрос» (в макетах 40–56 px) — тоже 64 / 56 / 48 */
	[style*="padding: clamp(40px, 6vw, 56px) clamp(20px, 4vw, 48px)"] { padding-top: 64px !important; padding-bottom: 64px !important; }
	@media (max-width: 1100px) {
		[style*="padding: clamp(40px, 6vw, 56px) clamp(20px, 4vw, 48px)"] { padding-top: 56px !important; padding-bottom: 56px !important; }
	}
	@media (max-width: 760px) {
		[style*="padding: clamp(40px, 6vw, 56px) clamp(20px, 4vw, 48px)"] { padding-top: 48px !important; padding-bottom: 48px !important; }
	}
	/* Подвал: сверху 40 px, на телефоне 32 (в макетах 72) */
	footer.dc-foot { padding-top: 40px !important; }
	@media (max-width: 760px) {
		footer.dc-foot { padding-top: 32px !important; }
	}
`;
// «Обсудим ваш проект» и «Задать вопрос»: раскладка на планшете и телефоне (правки заказчика) — как
// в dark-card.css: иконка и заголовок в строку, текст и пункты во всю ширину, примечание слева от кнопки;
// уже 560 px — кнопка во всю ширину; уже 370 px у «Задать вопрос» нет иконки.
const DARK_CARD_CSS = `
	section > div { container: dark-card / inline-size; }
	@container dark-card (max-width: 900px) {
		section > div > div > div:first-child { display: grid !important; flex: 1 1 100% !important; grid-template-columns: auto minmax(0, 1fr); align-items: center !important; gap: 12px 16px !important; }
		section > div > div > div:first-child > div { display: contents !important; }
		section > div > div > div:first-child > div > :not(h2) { grid-column: 1 / -1; }
		section > div > div > div:first-child p { max-width: none !important; }
		section h2 { text-wrap: wrap !important; }
		section > div > div > div:last-child { display: flex !important; flex: 1 1 100% !important; flex-direction: row !important; align-items: center !important; justify-content: space-between !important; gap: 12px 24px !important; max-width: none !important; }
		section > div > div > div:last-child > a { flex: 0 1 340px !important; order: 2; }
		section > div > div > div:last-child > p { flex: 1 1 auto !important; order: 1; max-width: 420px !important; }
	}
	@container dark-card (max-width: 560px) {
		section > div > div > div:last-child { flex-direction: column !important; align-items: stretch !important; }
		section > div > div > div:last-child > a, section > div > div > div:last-child > p { flex: none !important; order: 0; max-width: none !important; }
	}
`;
// «Что говорят клиенты» в портфолио — блок «Отзывы клиентов» (2 карточки): заголовок секции и
// шапка — как у всех блоков (в макете H2 26–40 px и 14 px от надзаголовка).
const PORTFOLIO_REVIEWS_CSS = `
	section[aria-labelledby="rev-title"] header { gap: 16px !important; max-width: none !important; }
	#rev-title { font-size: clamp(28px, 3.4vw, 44px) !important; text-wrap: wrap !important; }
`;
const MOCKUP_CSS = {
	'portfolio-blog-404/portfolio.html': HERO_SECTION_CSS + PORTFOLIO_REVIEWS_CSS,
	// Блоки «О компании» (шаг 4.10): шапка секции — как у всех блоков (H2 28–44 px, 16 px от
	// надзаголовка; в макете 26–40 px и 14 px).
	'o-nas/o-kompanii.html': HERO_SECTION_CSS + `
		main section:not(.dc-hero-sec) header { gap: 16px !important; }
		main section:not(.dc-hero-sec) header h2 { font-size: clamp(28px, 3.4vw, 44px) !important; }
		/* «Слово руководителя»: без «Познакомиться с командой»; подпись и кнопка строкой чуть ниже,
		   кнопка справа, на узком экране — под подписью, справа (правки заказчика) */
		section[aria-labelledby="ceo-title"] a[href="komanda.html"] { display: none !important; }
		section[aria-labelledby="ceo-title"] figure { display: grid !important; grid-template-columns: minmax(0, 1fr) auto; align-items: center; column-gap: 24px; }
		section[aria-labelledby="ceo-title"] figure > :not(figcaption, div) { grid-column: 1 / -1; justify-self: stretch; }
		section[aria-labelledby="ceo-title"] figure > svg { justify-self: start; }
		section[aria-labelledby="ceo-title"] figure > :is(figcaption, div) { margin-top: 14px; }
		section[aria-labelledby="ceo-title"] figure > div { justify-self: end; }
		@media (max-width: 600px) {
			section[aria-labelledby="ceo-title"] figure { grid-template-columns: minmax(0, 1fr); }
			section[aria-labelledby="ceo-title"] figure > div { grid-column: 1; margin-top: -6px; }
		}
		/* «Чем мы занимаемся»: значок и название в строку, текст и «Подробнее» под ними (правка заказчика) */
		.dc-svc { display: grid !important; grid-template-columns: 48px minmax(0, 1fr); grid-template-rows: auto auto 1fr; align-items: center; column-gap: 16px; }
		.dc-svc > h3 { margin: 0 !important; }
		.dc-svc > p { grid-column: 1 / -1; grid-row: 2; }
		.dc-svc > .dc-more { grid-column: 1 / -1; grid-row: 3; align-self: end; justify-self: start; margin-top: 0 !important; }
		@media (max-width: 560px) {
			.dc-svc { column-gap: 14px !important; }
		}
	`,
	'o-nas/komanda.html': HERO_SECTION_CSS + `
		main section:not(.dc-hero-sec) header { gap: 16px !important; }
		main section:not(.dc-hero-sec) header h2, #join-title { font-size: clamp(28px, 3.4vw, 44px) !important; }
		/* «Хотите в команду?» — отступы секции как у всех блоков (в макете 72 px) */
		section[aria-labelledby="join-title"] { padding-top: 64px !important; padding-bottom: 64px !important; }
		section[aria-labelledby="join-title"] > div > div:first-child { gap: 16px !important; }
		/* «Кто работает над проектом»: шапка во всю ширину; в главной карточке сверху «Ваш контакт»,
		   под ним значок и справа название (правки заказчика). «Команда по направлениям»: шапка во
		   всю ширину (правка заказчика) */
		section[aria-labelledby="roles-title"] header, section[aria-labelledby="staff-title"] header { max-width: none !important; }
		section[aria-labelledby="roles-title"] > div > div > div:first-child { display: grid !important; grid-template-columns: 52px minmax(0, 1fr); align-content: start; align-items: center; column-gap: 16px; }
		section[aria-labelledby="roles-title"] > div > div > div:first-child > span:nth-child(3) { grid-column: 1 / -1; grid-row: 1; }
		section[aria-labelledby="roles-title"] > div > div > div:first-child > span:nth-child(2) { grid-column: 1; grid-row: 2; }
		section[aria-labelledby="roles-title"] > div > div > div:first-child > h3 { grid-column: 2; grid-row: 2; }
		section[aria-labelledby="roles-title"] > div > div > div:first-child > p,
		section[aria-labelledby="roles-title"] > div > div > div:first-child > ul { grid-column: 1 / -1; }
		/* «Кто работает над проектом»: название роли справа от значка (правка заказчика) */
		section[aria-labelledby="roles-title"] > div > div > ul > li { display: grid !important; grid-template-columns: 44px minmax(0, 1fr); align-content: start; align-items: center; gap: 10px 14px !important; }
		section[aria-labelledby="roles-title"] > div > div > ul > li > h3 { margin: 0 !important; }
		section[aria-labelledby="roles-title"] > div > div > ul > li > p { grid-column: 1 / -1; }
		/* «Хотите в команду?»: на телефоне кнопки в одну строку (правка заказчика) */
		@media (max-width: 560px) {
			section[aria-labelledby="join-title"] > div > div:last-child { flex: 1 1 100% !important; flex-wrap: nowrap !important; gap: 8px !important; }
			section[aria-labelledby="join-title"] > div > div:last-child > a { flex: 1 1 auto; gap: 6px !important; min-width: 0; padding: 0 12px !important; font-size: 14px !important; justify-content: center; }
		}
		@media (max-width: 1100px) {
			section[aria-labelledby="join-title"] { padding-top: 56px !important; padding-bottom: 56px !important; }
		}
		@media (max-width: 760px) {
			section[aria-labelledby="join-title"] { padding-top: 48px !important; padding-bottom: 48px !important; }
		}
	`,
	// Блоки «Карьеры» (шаг 4.13): шапка секции — как у всех блоков (H2 28–44 px, 16 px от
	// надзаголовка; в макете 26–40 px и 14 px); у «Стажировки» отступы — как у всех секций (в
	// макете сверху 0: она идёт за белой секцией «Отбор»; при сверке блок снимается отдельно).
	'o-nas/karera.html': HERO_SECTION_CSS + `
		main section:not(.dc-hero-sec) header { gap: 16px !important; }
		main section:not(.dc-hero-sec) header h2, #resume-title { font-size: clamp(28px, 3.4vw, 44px) !important; }
		section[aria-labelledby="intern-title"] { padding-top: 64px !important; padding-bottom: 64px !important; }
		@media (max-width: 1100px) {
			section[aria-labelledby="intern-title"] { padding-top: 56px !important; padding-bottom: 56px !important; }
		}
		@media (max-width: 760px) {
			section[aria-labelledby="intern-title"] { padding-top: 48px !important; padding-bottom: 48px !important; }
		}
		/* Правки заказчика к 4.13. Первый экран: кнопки в одну строку, на узком экране — «Вакансии» */
		.dc-hero-in a[href="#vacancies"], .dc-hero-in a[href="#resume"] { white-space: nowrap; }
		@media (max-width: 560px) {
			.dc-hero-in div:has(> a[href="#vacancies"]) { gap: 8px !important; }
			.dc-hero-in a[href="#vacancies"], .dc-hero-in a[href="#resume"] { gap: 8px !important; padding: 0 16px !important; font-size: 15px !important; }
		}
		@media (max-width: 423px) {
			.dc-hero-in a[href="#vacancies"] { font-size: 0 !important; }
			.dc-hero-in a[href="#vacancies"]::before { content: "Вакансии"; font-size: 15px; }
		}
		/* «Условия»: шапка во всю ширину, значок и заголовок в строку, текст под ними */
		section[aria-labelledby="why-title"] header { max-width: none !important; }
		section[aria-labelledby="why-title"] li { display: grid !important; grid-template-columns: 48px minmax(0, 1fr); align-content: start; align-items: center; gap: 14px 16px !important; }
		section[aria-labelledby="why-title"] li > h3 { margin: 0 !important; }
		section[aria-labelledby="why-title"] li > p { grid-column: 1 / -1; }
		/* Вакансии: «Откликнуться» (46 px) у правого края, email под кнопкой */
		.dc-acc-panel > div:last-child { flex-direction: column !important; align-items: flex-end !important; gap: 10px !important; text-align: right; }
		.dc-acc-panel > div:last-child > a { gap: 8px !important; min-height: 46px !important; padding: 0 18px !important; border-radius: 12px !important; font-size: 15px !important; }
		.dc-acc-panel > div:last-child > span { line-height: 1.5; }
		/* Телефон: зарплата и стрелка — на одном уровне под названием и метками */
		@media (max-width: 640px) {
			.dc-acc-btn { display: grid !important; grid-template-columns: minmax(0, 1fr) auto; align-items: center !important; gap: 12px 16px !important; }
			.dc-acc-btn > span:first-child { display: contents !important; }
			.dc-acc-btn > span:first-child > span:first-child { grid-column: 1 / -1; }
			.dc-acc-btn > span:first-child > span:last-child { grid-column: 1; white-space: normal !important; }
			.dc-acc-btn > span:last-child { grid-column: 2; grid-row: 2; }
		}
		/* «Стажировка»: «Подать заявку» у правого края и под текстом (телефон, планшет) */
		section[aria-labelledby="intern-title"] a[href="#resume"] { margin-left: auto; }
		/* «Изнутри»: на узком экране «Вся команда» — после цитат, у правого края */
		@media (max-width: 760px) {
			section[aria-labelledby="voices-title"] > div > div:first-child { display: contents !important; }
			section[aria-labelledby="voices-title"] > div > ul { order: 1; }
			section[aria-labelledby="voices-title"] > div > div:first-child > a { order: 2; align-self: flex-end; }
		}
	`,
	'o-nas/kontakty.html': `
		.dc-hero-in > div:first-child { max-width: none !important; }
		.dc-hero-in > div:first-child > p { max-width: 760px !important; }
	`,
	'o-nas/otzyvy.html': HERO_SECTION_CSS + `
		main section:not(.dc-hero-sec) header { gap: 16px !important; }
		main section:not(.dc-hero-sec) h2 { font-size: clamp(28px, 3.4vw, 44px) !important; }
		/* «Кейс проекта» в макете у каждой карточки, в демо — только у отзывов, привязанных к кейсам */
		section[aria-labelledby="list-title"] a[href="#portfolio"] { display: none !important; }
	`,
	'o-nas/oferta.html': HERO_DOCUMENT_CSS,
	'o-nas/politika-cookie.html': HERO_DOCUMENT_CSS,
	'o-nas/soglasie-pd.html': HERO_DOCUMENT_CSS,
	'seo-vopros/vopros-seo.html': `
		/* Форма под текстом (уже 1000 px) — текст во всю ширину карточки (правка заказчика) */
		@media (max-width: 999px) {
			.dc-ask-intro > p { max-width: none !important; }
		}
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
	'seo-stranitsa/index.html': `
		main > section:has(h1) > div > div > div:first-child { display: contents !important; }
		main > section:has(h1) h1 { flex: 0 0 100% !important; margin: 0 0 -8px !important; }
		main > section:has(h1) h1 + p { flex: 1 1 520px !important; max-width: 780px !important; min-width: 0 !important; }
		main > section:has(h1) { padding-bottom: 48px !important; }
		@media (max-width: 1100px) {
			main > section:has(h1) { padding-bottom: 40px !important; }
		}
		@media (max-width: 760px) {
			main > section:has(h1) { padding-bottom: 32px !important; }
		}
		@media (max-width: 480px) {
			main > section:has(h1) h1 + p { font-size: 17px !important; }
			main > section:has(h1) div:has(> a[href="#order"]) { gap: 8px !important; }
			main > section:has(h1) a[href="#order"], main > section:has(h1) a[href="#calculator"] {
				min-height: 48px !important; padding: 0 14px !important; font-size: 14px !important; white-space: nowrap !important; box-sizing: border-box !important;
			}
		}
	`,
	// «С какими проблемами…»: шапка по левому краю во всю ширину, строки заголовка заполняют ширину (правки заказчика).
	'seo-bloki/07-problemy.html': `
		section > div > header { align-items: flex-start !important; max-width: none !important; text-align: left !important; margin: 0 !important; }
		section > div > header h2 { text-wrap: wrap !important; }
	`,
	// «Бриф — полный»: шапка по левому краю во всю ширину, от текста до формы 28 / 24 px, «таблетки»
	// одной ширины в обоих состояниях (правки заказчика).
	'seo-bloki/11a-brif-polnyy.html': `
		section > div { gap: 28px !important; }
		section > div > header { align-items: flex-start !important; text-align: left !important; }
		section > div > header h2 { text-wrap: wrap !important; }
		section > div > header p { max-width: none !important; }
		fieldset button[aria-pressed="false"] { padding: 0 28px !important; }
		@media (max-width: 760px) {
			section > div { gap: 24px !important; }
		}
	`,
	// «Калькулятор стоимости»: заголовок и вводный текст во всю ширину блока (как правки заказчика в других блоках).
	'seo-bloki/13-kalkulyator.html': `
		section > div > header { max-width: none !important; }
		section > div > header h2 { text-wrap: wrap !important; }
	`,
	// «Выбор тарифа»: заголовок и вводный текст во всю ширину блока, без рисунка справа (правка заказчика).
	'seo-bloki/01-tarify.html': `
		section > div > header { max-width: none !important; }
		section > div > header h2 { text-wrap: wrap !important; }
	`,
	// «Наши проекты в топе»: заголовок и вводный текст во всю ширину блока (правка заказчика).
	'seo-bloki/12-proekty-v-tope.html': `
		section > div > header { max-width: none !important; }
		section > div > header h2 { text-wrap: wrap !important; }
	`,
	// «Карусель кейсов»: счётчик крупнее — 20 px (правка заказчика, в макете 14); на телефоне счётчика и стрелок нет.
	'seo-bloki/14-karusel-keysov.html': `
		span[aria-live] { min-width: 96px !important; font-size: 20px !important; }
		/* Заголовок и вводный текст — во всю ширину блока, до счётчика и стрелок (правка заказчика) */
		section header { max-width: none !important; }
		section header h2 { text-wrap: wrap !important; }
		/* На телефоне счётчика и стрелок нет (правка заказчика) */
		@media (max-width: 640px) {
			div:has(> span[aria-live]) { display: none !important; }
		}
	`,
	// «Отзывы клиентов»: заголовок и вводный текст во всю ширину блока (правка заказчика).
	'seo-bloki/15-otzyvy.html': `
		section > div > header { max-width: none !important; }
		section > div > header h2 { text-wrap: wrap !important; }
	`,
	'o-nas/blok-cta.html': DARK_CARD_CSS,
	'seo-vopros/blok-vopros.html': DARK_CARD_CSS + `
		@container dark-card (max-width: 370px) {
			section > div > div > div:first-child > span { display: none !important; }
			section > div > div > div:first-child h2 { grid-column: 1 / -1; }
		}
	`,
	// «Блок с текстом»: колонка 960 px (правка заказчика, в макете 760), строки заголовка заполняют колонку.
	'seo-bloki/03-tekst.html': `
		section > article { max-width: 960px !important; }
		section > article > h2 { text-wrap: wrap !important; }
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
		const file = decodeURIComponent( location.pathname );
		const walker = document.createTreeWalker( document.body, NodeFilter.SHOW_TEXT );
		for ( let node = walker.nextNode(); node; node = walker.nextNode() ) {
			for ( const [ from, to, only ] of texts ) {
				if ( only && ! only.some( ( part ) => file.includes( part ) ) ) {
					continue;
				}
				if ( node.nodeValue.includes( from ) ) {
					node.nodeValue = node.nodeValue.split( from ).join( to );
				}
			}
		}
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
 * Сбрасывает лимиты частоты форм и просчётов стоимости (все тесты идут с одного адреса 127.0.0.1).
 */
function resetFormLimits() {
	wp( 'eval', "global $wpdb; foreach ( $wpdb->get_col( \"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_trust_form_rate_%' OR option_name LIKE '_transient_trust_estimate_rate_%'\" ) as $o ) { delete_transient( substr( $o, strlen( '_transient_' ) ) ); }" );
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
