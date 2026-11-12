/**
 * Тест мобильной шапки (шаг 1.4) на локальном стенде с демо-контентом.
 *
 * Запуск: node wp-theme/tests/header-mobile.js
 *
 * Проверяет:
 * - бургер виден уже 1180 px и скрыт от 1180 px; aria-expanded, смена иконки;
 * - мобильное меню: пункты меню, телефон, email, кнопка звонка;
 * - подменю раскрываются по одному, текущий пункт подменю отмечен;
 * - Esc закрывает меню и возвращает фокус на бургер; переход на 1180 px закрывает меню;
 * - без JavaScript меню показано целиком, бургера нет;
 * - открытое меню на 280–1179 px: без прокрутки вбок, зоны нажатия ≥ 44 px;
 * - совпадение с макетом на 390 px: меню закрыто, открыто, раскрыт раздел SEO (≤ 2 %);
 * - axe-core с открытым меню, консоль и журнал ошибок.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle } = require( './lib' );

const MENU = [ 'Дизайн', 'Создание сайтов', 'Поддержка сайтов', 'SEO', 'GEO', 'Контекстная реклама', 'AI', 'Портфолио', 'Блог', 'О нас' ];

/**
 * Снимок шапки (вместе с открытым меню) темы и макета и их сравнение.
 *
 * @param {import('playwright').Page} page   Страница.
 * @param {string}                    name   Имя состояния для файлов.
 * @param {Function}                  themeAction  Что сделать на странице темы.
 * @param {Function}                  mockAction   Что сделать на макете.
 */
async function compareState( page, name, themeAction, mockAction ) {
	await page.goto( `${ URL }/portfolio/`, { waitUntil: 'networkidle' } );
	await settle( page );
	await themeAction();
	await settle( page );
	const height = Math.ceil( await page.evaluate( () => document.querySelector( '.site-header' ).getBoundingClientRect().height ) );
	await page.screenshot( { path: path.join( OUT, `mobile-${ name }-theme.png` ), clip: { x: 0, y: 0, width: 390, height } } );

	await page.goto( mockupUrl( 'portfolio-blog-404/portfolio.html' ), { waitUntil: 'networkidle' } );
	await settle( page );
	await mockAction();
	await settle( page );
	await page.screenshot( { path: path.join( OUT, `mobile-${ name }-mockup.png` ), clip: { x: 0, y: 0, width: 390, height } } );

	const diff = comparePng( path.join( OUT, `mobile-${ name }-mockup.png` ), path.join( OUT, `mobile-${ name }-theme.png` ), path.join( OUT, `mobile-${ name }-diff.png` ) );
	check( `Макет 390 px, ${ name }: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %, высота ${ height }px` );
}

( async () => {
	clearLog();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 390, height: 844 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );

	const burger = page.locator( '.header-burger' );
	const menu = page.locator( '#mobile-menu' );

	// --- Бургер и меню ---
	await page.goto( `${ URL }/kontakty/`, { waitUntil: 'networkidle' } );
	check( 'Бургер виден на 390 px', await burger.isVisible() );
	check( 'Меню закрыто, aria-expanded="false"', ! ( await menu.isVisible() ) && ( await burger.getAttribute( 'aria-expanded' ) ) === 'false' );
	check( 'Иконка «меню», крестик скрыт', ( await page.locator( '.header-burger__open' ).isVisible() ) && ! ( await page.locator( '.header-burger__close' ).isVisible() ) );

	await burger.click();
	check( 'Бургер открывает меню', await menu.isVisible() );
	check( 'aria-expanded="true", иконка — крестик', ( await burger.getAttribute( 'aria-expanded' ) ) === 'true' && ( await page.locator( '.header-burger__close' ).isVisible() ) );

	const items = ( await menu.locator( '.mobile-nav__list > .mobile-nav__item > .mobile-nav__link' ).allTextContents() ).map( ( t ) => t.trim() );
	check( 'Пункты меню по порядку', JSON.stringify( items ) === JSON.stringify( MENU ), items.join( ' | ' ) );
	check( 'Телефон в меню', ( await menu.locator( '.mobile-nav__phone' ).getAttribute( 'href' ) ) === 'tel:+70000000000' );
	check( 'Email в меню', ( await menu.locator( '.mobile-nav__email' ).getAttribute( 'href' ) ) === 'mailto:hello@example.ru' );
	check( 'Кнопка звонка в меню', ( await menu.locator( '.mobile-nav__callback' ).textContent() ).trim() === 'Заказать обратный звонок' );

	// --- Подменю ---
	const section = ( title ) => menu.locator( '.mobile-nav__item', { has: page.locator( '.mobile-nav__toggle', { hasText: title } ) } );
	check( 'Подменю закрыты', ( await menu.locator( '.mobile-nav__sub:visible' ).count() ) === 0 );
	await section( 'SEO' ).locator( '.mobile-nav__toggle' ).click();
	check( 'SEO раскрывается: 4 пункта', ( await section( 'SEO' ).locator( '.mobile-nav__sub-link:visible' ).count() ) === 4 );
	check( 'SEO: aria-expanded="true"', ( await section( 'SEO' ).locator( '.mobile-nav__toggle' ).getAttribute( 'aria-expanded' ) ) === 'true' );
	await section( 'О нас' ).locator( '.mobile-nav__toggle' ).click();
	check( 'Раскрыт один раздел за раз (SEO закрылся)', ! ( await section( 'SEO' ).locator( '.mobile-nav__sub' ).isVisible() ) && ( await section( 'О нас' ).locator( '.mobile-nav__sub' ).isVisible() ) );
	const current = section( 'О нас' ).locator( '.mobile-nav__sub-link[aria-current="page"]' );
	const currentStyle = await current.evaluate( ( el ) => getComputedStyle( el ).fontWeight + ' ' + getComputedStyle( el ).color );
	check( 'Текущий пункт «Контакты» отмечен (белый, 600)', ( await current.textContent() ).trim() === 'Контакты' && currentStyle === '600 rgb(255, 255, 255)', currentStyle );
	await section( 'О нас' ).locator( '.mobile-nav__toggle' ).click();
	check( 'Повторное нажатие сворачивает раздел', ! ( await section( 'О нас' ).locator( '.mobile-nav__sub' ).isVisible() ) );

	// --- Доступность с открытым меню ---
	// Проверяется шапка с меню: страница под открытым меню перекрыта им, и axe принимал бы фон
	// меню за фон текста страницы (крошки первого экрана «Контактов»).
	await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
	const axe = await page.evaluate( async () => {
		const r = await window.axe.run( document.querySelector( '.site-header' ), { resultTypes: [ 'violations' ] } );
		return r.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length }: ${ v.nodes.map( ( n ) => n.target.join( ' ' ) + ' ' + ( n.any[ 0 ]?.message || '' ).slice( 0, 120 ) ).join( '; ' ) })` );
	} );
	check( 'axe с открытым меню: нет critical/serious', axe.length === 0, axe.join( ', ' ) );

	// --- Esc и широкий экран ---
	await menu.locator( '.mobile-nav__phone' ).focus();
	await page.keyboard.press( 'Escape' );
	check( 'Esc закрывает меню', ! ( await menu.isVisible() ) && ( await burger.getAttribute( 'aria-expanded' ) ) === 'false' );
	check( 'После Esc фокус на бургере', await page.evaluate( () => document.activeElement.classList.contains( 'header-burger' ) ) );

	await burger.click();
	await page.setViewportSize( { width: 1280, height: 800 } );
	await page.waitForTimeout( 100 );
	check( 'На 1280 px бургер и мобильное меню скрыты', ! ( await burger.isVisible() ) && ! ( await menu.isVisible() ) );
	check( 'Переход на широкий экран закрывает меню', ( await burger.getAttribute( 'aria-expanded' ) ) === 'false' );
	await page.setViewportSize( { width: 1180, height: 800 } );
	check( 'На 1180 px бургера нет', ! ( await burger.isVisible() ) );
	await page.setViewportSize( { width: 1179, height: 800 } );
	check( 'На 1179 px бургер есть', await burger.isVisible() );

	// --- Ширины с открытым меню ---
	for ( const w of [ 280, 320, 360, 390, 600, 768, 1024, 1179 ] ) {
		await page.setViewportSize( { width: w, height: 800 } );
		await page.goto( `${ URL }/kontakty/`, { waitUntil: 'networkidle' } );
		await burger.click();
		await section( 'Контекстная реклама' ).locator( '.mobile-nav__toggle' ).click();
		const scroll = await page.evaluate( () => document.documentElement.scrollWidth > window.innerWidth );
		const small = await page.evaluate( () => [ ...document.querySelectorAll( '.site-header a, .site-header button' ) ]
			.filter( ( el ) => el.offsetParent !== null )
			.map( ( el ) => ( { t: el.textContent.trim() || el.getAttribute( 'aria-label' ), h: Math.round( el.getBoundingClientRect().height ), w: Math.round( el.getBoundingClientRect().width ) } ) )
			.filter( ( r ) => r.h < 44 || r.w < 44 ) );
		check( `Открытое меню на ${ w }px: без прокрутки вбок, зоны ≥ 44 px`, ! scroll && small.length === 0, scroll ? 'прокрутка вбок' : JSON.stringify( small ) );
	}

	// --- Логотип на самом узком экране ---
	await page.setViewportSize( { width: 280, height: 653 } );
	await page.goto( `${ URL }/`, { waitUntil: 'networkidle' } );
	await settle( page );
	const fits = await page.evaluate( () => {
		const name = document.querySelector( '.brand__name' );
		return name.scrollWidth <= name.clientWidth;
	} );
	check( '280 px: название бренда помещается целиком', fits );
	await page.evaluate( () => {
		document.querySelector( '.brand__name' ).textContent = 'Очень длинное название агентства';
	} );
	const longOk = await page.evaluate( () => document.documentElement.scrollWidth <= window.innerWidth
		&& document.querySelector( '.header-burger' ).getBoundingClientRect().right <= window.innerWidth );
	check( '280 px: длинное название не выталкивает кнопки за край', longOk );

	// --- Сверка с макетом на 390 ---
	await page.setViewportSize( { width: 390, height: 844 } );
	await compareState( page, 'closed', async () => {}, async () => {} );
	await compareState(
		page,
		'open',
		async () => page.click( '.header-burger' ),
		async () => page.click( '.dc-burger' )
	);
	await compareState(
		page,
		'seo',
		async () => {
			await page.click( '.header-burger' );
			await page.locator( '.mobile-nav__toggle', { hasText: 'SEO' } ).click();
		},
		async () => {
			await page.click( '.dc-burger' );
			await page.locator( '.dc-mobile-menu button', { hasText: 'SEO' } ).click();
		}
	);

	await browser.close();
	check( 'Консоль браузера без ошибок', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );

	// --- Без JavaScript ---
	const noJsBrowser = await launch();
	const noJs = await noJsBrowser.newPage( { viewport: { width: 390, height: 844 }, javaScriptEnabled: false } );
	await noJs.goto( `${ URL }/kontakty/` );
	check( 'Без JS: бургера нет', ! ( await noJs.locator( '.header-burger' ).isVisible() ) );
	check( 'Без JS: меню показано целиком с подменю', ( await noJs.locator( '#mobile-menu' ).isVisible() ) && ( await noJs.locator( '.mobile-nav__sub-link:visible' ).count() ) === 14 );
	await noJs.setViewportSize( { width: 1280, height: 800 } );
	check( 'Без JS на 1280 px мобильного меню нет', ! ( await noJs.locator( '#mobile-menu' ).isVisible() ) );
	await noJsBrowser.close();

	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
