/**
 * Тест шапки (шаг 1.3) на локальном стенде с демо-контентом.
 *
 * Запуск: node wp-theme/tests/header.js
 *
 * Проверяет:
 * - логотип, подпись, телефон с часами, email, кнопку звонка — из «Настроек сайта»;
 * - меню из области «Шапка»: порядок пунктов, подменю с описаниями, разделитель;
 * - подсветку: текущий пункт, раздел текущей страницы, текущий пункт подменю;
 * - подменю: открытие наведением, клавиатурой, закрытие по Esc (фокус на пункт), клику вне, уходу мыши;
 * - раскладку на ширинах 1180/1179/760/520, отсутствие прокрутки вбок, зоны нажатия ≥ 44 px;
 * - совпадение с макетом на 1280 px (холст портфолио, до 2 % пикселей);
 * - axe-core без critical/serious, консоль и журнал ошибок.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle } = require( './lib' );

const MENU = [ 'Дизайн', 'Создание сайтов', 'Поддержка сайтов', 'SEO', 'GEO', 'Контекстная реклама', 'AI', 'Портфолио', 'Блог', 'О нас' ];

( async () => {
	clearLog();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const visible = ( selector ) => page.locator( selector ).first().isVisible();

	// --- Содержимое шапки ---
	await page.goto( `${ URL }/portfolio/`, { waitUntil: 'networkidle' } );
	await settle( page );

	check( 'Логотип ведёт на главную', ( await page.getAttribute( '.site-header .brand', 'href' ) ) === `${ URL }/` );
	check( 'Название бренда', ( await page.textContent( '.brand__name' ) ).trim() === '[Бренд]' );
	check( 'Подпись под названием', ( await page.textContent( '.brand__tagline' ) ).trim() === 'SEO · сайты · реклама' );
	check( 'Телефон: ссылка tel:', ( await page.getAttribute( '.header-phone', 'href' ) ) === 'tel:+70000000000' );
	check( 'Телефон: часы работы', ( await page.textContent( '.header-phone__hours' ) ).trim() === '[Пн–Пт, 10:00–19:00]' );
	check( 'Email: ссылка mailto:', ( await page.getAttribute( '.header-email', 'href' ) ) === 'mailto:hello@example.ru' );
	check( 'Кнопка «Заказать обратный звонок»', ( await page.textContent( '.header-callback' ) ).trim() === 'Заказать обратный звонок' );

	// --- Меню ---
	const top = ( await page.locator( '.main-nav__list > .main-nav__item > .main-nav__link' ).allTextContents() ).map( ( t ) => t.trim() );
	check( 'Пункты меню по порядку', JSON.stringify( top ) === JSON.stringify( MENU ), top.join( ' | ' ) );
	check( '4 пункта с подменю — кнопки', ( await page.locator( 'button.main-nav__toggle[aria-expanded="false"]' ).count() ) === 4 );
	check( 'Подменю закрыты', ( await page.locator( '.main-nav__dropdown:visible' ).count() ) === 0 );

	const seoSub = page.locator( '.main-nav__item--parent', { hasText: 'SEO' } ).first();
	check( 'SEO: 4 пункта с описаниями', ( await seoSub.locator( '.main-nav__sub-desc' ).count() ) === 4 );
	check( 'SEO: разделитель над «Задать вопрос SEO-шнику»', ( await seoSub.locator( 'li.separator-before' ).textContent() ).includes( 'Задать вопрос SEO-шнику' ) );

	// --- Подсветка ---
	check( 'Портфолио: текущий пункт (aria-current)', ( await page.locator( '.main-nav__link[aria-current="page"]' ).textContent() ).trim() === 'Портфолио' );
	check( 'Портфолио: подсвечен', await page.locator( '.main-nav__link.is-active', { hasText: 'Портфолио' } ).isVisible() );

	await page.goto( `${ URL }/kontakty/`, { waitUntil: 'networkidle' } );
	check( 'Контакты: подсвечен раздел «О нас»', ( await page.locator( '.main-nav__toggle.is-active' ).textContent() ).trim() === 'О нас' );
	check( 'Контакты: отмечен пункт подменю', ( await page.locator( '.main-nav__sub-link[aria-current="page"]' ).textContent() ).trim() === 'Контакты' );

	await page.goto( `${ URL }/seo/voprosy/`, { waitUntil: 'networkidle' } );
	check( 'Вопросы: подсвечен раздел «SEO»', ( await page.locator( '.main-nav__toggle.is-active' ).textContent() ).trim() === 'SEO' );

	await page.goto( `${ URL }/`, { waitUntil: 'networkidle' } );
	check( 'Главная: ничего не подсвечено', ( await page.locator( '.main-nav .is-active, .main-nav [aria-current]' ).count() ) === 0 );

	// --- Подменю: мышь ---
	const seo = page.locator( '.main-nav__item--parent', { hasText: 'SEO' } ).first();
	await seo.hover();
	check( 'Наведение открывает подменю', await seo.locator( '.main-nav__dropdown' ).isVisible() );
	check( 'aria-expanded="true" при открытии', ( await seo.locator( '.main-nav__toggle' ).getAttribute( 'aria-expanded' ) ) === 'true' );
	await seo.locator( '.main-nav__toggle' ).click();
	check( 'Клик сразу после наведения не закрывает подменю', await seo.locator( '.main-nav__dropdown' ).isVisible() );
	await page.mouse.move( 640, 600 );
	check( 'Уход мыши закрывает подменю', ! ( await seo.locator( '.main-nav__dropdown' ).isVisible() ) );

	// --- Подменю: клавиатура ---
	const about = page.locator( '.main-nav__item--parent', { hasText: 'О нас' } ).first();
	await about.locator( '.main-nav__toggle' ).focus();
	await page.keyboard.press( 'Enter' );
	check( 'Enter открывает подменю', await about.locator( '.main-nav__dropdown' ).isVisible() );
	const box = await about.locator( '.main-nav__sub' ).boundingBox();
	check( 'Подменю «О нас» не выходит за правый край', box.x + box.width <= 1280, `правый край ${ Math.round( box.x + box.width ) }` );
	await page.keyboard.press( 'Tab' );
	check( 'Tab переходит в подменю', await page.evaluate( () => document.activeElement.classList.contains( 'main-nav__sub-link' ) ) );
	await page.keyboard.press( 'Escape' );
	check( 'Esc закрывает подменю', ! ( await about.locator( '.main-nav__dropdown' ).isVisible() ) );
	check( 'После Esc фокус на пункте «О нас»', await page.evaluate( () => document.activeElement.classList.contains( 'main-nav__toggle' ) && document.activeElement.textContent.trim() === 'О нас' ) );

	await page.keyboard.press( 'Enter' );
	await page.mouse.click( 640, 600 );
	check( 'Клик вне меню закрывает подменю', ! ( await about.locator( '.main-nav__dropdown' ).isVisible() ) );

	// --- Сверка с макетом на 1280 ---
	await page.goto( `${ URL }/portfolio/`, { waitUntil: 'networkidle' } );
	await settle( page );
	const height = await page.evaluate( () => document.querySelector( '.site-header' ).getBoundingClientRect().height );
	await page.screenshot( { path: path.join( OUT, 'header-theme-1280.png' ), clip: { x: 0, y: 0, width: 1280, height } } );
	await page.goto( mockupUrl( 'portfolio-blog-404/portfolio.html' ), { waitUntil: 'networkidle' } );
	await settle( page );
	await page.screenshot( { path: path.join( OUT, 'header-mockup-1280.png' ), clip: { x: 0, y: 0, width: 1280, height } } );
	const diff = comparePng( path.join( OUT, 'header-mockup-1280.png' ), path.join( OUT, 'header-theme-1280.png' ), path.join( OUT, 'header-diff-1280.png' ) );
	check( 'Совпадение с макетом на 1280 px (≤ 2 %)', diff <= 2, `${ diff.toFixed( 2 ) } % пикселей, высота ${ height }px` );

	// --- Ширины экрана ---
	const widths = [
		{ w: 1180, nav: true, email: true, contacts: true, text: true },
		{ w: 1179, nav: false, email: false, contacts: true, text: true },
		{ w: 761, nav: false, email: false, contacts: true, text: true },
		{ w: 760, nav: false, email: false, contacts: false, text: true },
		{ w: 520, nav: false, email: false, contacts: false, text: false },
		{ w: 390, nav: false, email: false, contacts: false, text: false },
		{ w: 280, nav: false, email: false, contacts: false, text: false },
	];
	for ( const s of widths ) {
		await page.setViewportSize( { width: s.w, height: 800 } );
		await page.goto( `${ URL }/portfolio/`, { waitUntil: 'networkidle' } );
		const state = {
			nav: await visible( '.main-nav' ),
			email: await visible( '.header-email' ),
			contacts: await visible( '.site-header__contacts' ),
			text: await visible( '.header-callback' ),
			icon: await visible( '.header-callback-icon' ),
			scroll: await page.evaluate( () => document.documentElement.scrollWidth > window.innerWidth ),
		};
		const ok = state.nav === s.nav && state.email === s.email && state.contacts === s.contacts && state.text === s.text && state.icon === ! s.text && ! state.scroll;
		check( `Ширина ${ s.w }px: раскладка шапки`, ok, JSON.stringify( state ) );

		if ( s.w < 1024 ) {
			const small = await page.evaluate( () => [ ...document.querySelectorAll( '.site-header a, .site-header button' ) ]
				.filter( ( el ) => el.offsetParent !== null )
				.map( ( el ) => ( { t: el.textContent.trim() || el.getAttribute( 'aria-label' ), h: el.getBoundingClientRect().height, w: el.getBoundingClientRect().width } ) )
				.filter( ( r ) => r.h < 44 || r.w < 44 ) );
			check( `Ширина ${ s.w }px: зоны нажатия ≥ 44 px`, small.length === 0, JSON.stringify( small ) );
		}
		if ( s.w === 390 ) {
			await page.screenshot( { path: path.join( OUT, 'header-theme-390.png' ), clip: { x: 0, y: 0, width: 390, height: 100 } } );
		}
	}

	// --- Доступность ---
	await page.setViewportSize( { width: 1280, height: 900 } );
	await page.goto( `${ URL }/kontakty/`, { waitUntil: 'networkidle' } );
	await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
	const axe = await page.evaluate( async () => {
		const r = await window.axe.run( document, { resultTypes: [ 'violations' ] } );
		return r.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length })` );
	} );
	check( 'axe: нет нарушений critical/serious', axe.length === 0, axe.join( ', ' ) );

	await browser.close();
	check( 'Консоль браузера без ошибок', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
