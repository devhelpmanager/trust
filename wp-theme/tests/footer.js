/**
 * Тест подвала (шаг 1.5) на локальном стенде с демо-контентом.
 *
 * Запуск: node wp-theme/tests/footer.js
 *
 * Проверяет:
 * - логотип, описание, плашки, юридическую строку с текущим годом — из «Настроек сайта → Подвал»;
 * - колонки «Услуги» и «Компания» из меню (заголовок — название меню), меню документов;
 * - контакты, заголовок «Контакты» со ссылкой, кнопку звонка и её наведение;
 * - значки соцсетей: нет, пока ссылки не заполнены; есть, когда заполнены;
 * - наведение на ссылки, ширины 1280–280 px без прокрутки вбок, зоны нажатия ≥ 44 px уже 1180 px;
 * - совпадение с макетом на 1280 и 390 px (≤ 2 %), с заглушками адреса, часов и года из макета подвала;
 * - axe-core, консоль и журнал ошибок.
 */

const { chromium } = require( 'playwright' );
const path = require( 'path' );
const { URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, getSettings, setSettings } = require( './lib' );

const SERVICES = [ 'Дизайн', 'Создание сайтов', 'Поддержка сайтов', 'SEO-продвижение', 'Задать вопрос SEO-шнику', 'GEO-продвижение', 'Контекстная реклама', 'AI-решения' ];
const COMPANY = [ 'О компании', 'Наша команда', 'Портфолио', 'Отзывы о нас', 'Блог', 'Карьера' ];
const DOCS = [ 'Публичная оферта', 'Политика конфиденциальности', 'Согласие на обработку данных', 'Политика cookie' ];

( async () => {
	clearLog();
	const saved = getSettings( [ 'address', 'hours', 'footer_legal', 'social_links' ] );
	const browser = await chromium.launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const footer = page.locator( '.site-footer' );
	const texts = async ( selector ) => ( await footer.locator( selector ).allTextContents() ).map( ( t ) => t.replace( /\s+/g, ' ' ).trim() );

	try {
		// --- Содержимое ---
		await page.goto( `${ URL }/`, { waitUntil: 'networkidle' } );
		check( 'Логотип без подписи', ( await footer.locator( '.brand__name' ).textContent() ).trim() === '[Бренд]' && ( await footer.locator( '.brand__tagline' ).count() ) === 0 );
		check( 'Описание компании', ( await footer.locator( '.site-footer__desc' ).textContent() ).startsWith( 'Продвигаем сайты в Яндексе' ) );
		check( 'Плашки-направления', JSON.stringify( await texts( '.site-footer__tag' ) ) === JSON.stringify( [ 'SEO', 'GEO', 'Сайты', 'Контекстная реклама', 'AI' ] ) );

		const headings = await texts( '.site-footer__col .site-footer__heading' );
		check( 'Заголовки колонок — названия меню', JSON.stringify( headings ) === JSON.stringify( [ 'Услуги', 'Компания' ] ), headings.join( ', ' ) );
		const cols = footer.locator( '.site-footer__col' );
		const services = ( await cols.nth( 0 ).locator( 'a' ).allTextContents() ).map( ( t ) => t.trim() );
		const company = ( await cols.nth( 1 ).locator( 'a' ).allTextContents() ).map( ( t ) => t.trim() );
		check( 'Колонка «Услуги»: 8 ссылок по порядку', JSON.stringify( services ) === JSON.stringify( SERVICES ), services.join( ' | ' ) );
		check( 'Колонка «Компания»: 6 ссылок по порядку', JSON.stringify( company ) === JSON.stringify( COMPANY ), company.join( ' | ' ) );
		check( 'Ссылка «Портфолио» ведёт на страницу', ( await cols.nth( 1 ).locator( 'a', { hasText: 'Портфолио' } ).getAttribute( 'href' ) ) === `${ URL }/portfolio/` );

		check( 'Заголовок «Контакты» — ссылка на контакты', ( await footer.locator( '.site-footer__contacts a.site-footer__heading' ).getAttribute( 'href' ) ) === `${ URL }/kontakty/` );
		check( 'Телефон', ( await footer.locator( '.site-footer__phone' ).getAttribute( 'href' ) ) === 'tel:+70000000000' );
		check( 'Email', ( await footer.locator( '.site-footer__email' ).getAttribute( 'href' ) ) === 'mailto:hello@example.ru' );
		const address = ( await footer.locator( '.site-footer__address' ).innerText() ).split( '\n' ).map( ( s ) => s.trim() );
		check( 'Адрес и часы — двумя строками', address[ 0 ] === '[Город], [улица, дом], [этаж, офис]' && address[ 1 ] === '[Пн–Пт, 10:00–19:00]', address.join( ' / ' ) );
		check( 'Кнопка звонка', ( await footer.locator( '.site-footer__callback' ).textContent() ).trim() === 'Заказать обратный звонок' );
		check( 'Соцсетей нет, пока ссылки не заполнены', ( await footer.locator( '.site-footer__social' ).count() ) === 0 );

		const legal = ( await footer.locator( '.site-footer__bottom > span' ).textContent() ).trim();
		check( 'Юридическая строка с текущим годом', legal.startsWith( `© ${ new Date().getFullYear() } [Юридическое название]` ), legal );
		const docs = ( await footer.locator( '.site-footer__docs a' ).allTextContents() ).map( ( t ) => t.trim() );
		check( 'Документы: 4 ссылки', JSON.stringify( docs ) === JSON.stringify( DOCS ), docs.join( ' | ' ) );

		// --- Наведение ---
		const link = cols.nth( 0 ).locator( 'a' ).first();
		await link.hover();
		await page.waitForTimeout( 300 );
		check( 'Наведение на ссылку: белый цвет', ( await link.evaluate( ( el ) => getComputedStyle( el ).color ) ) === 'rgb(255, 255, 255)' );
		const callback = footer.locator( '.site-footer__callback' );
		await callback.hover();
		await page.waitForTimeout( 300 );
		const cbStyle = await callback.evaluate( ( el ) => getComputedStyle( el ).backgroundColor + ' ' + getComputedStyle( el ).color );
		check( 'Наведение на кнопку звонка: текст виден (лавандовый фон, тёмный текст)', cbStyle === 'rgb(243, 238, 255) rgb(76, 29, 149)', cbStyle );

		// --- Соцсети ---
		setSettings( { social_links: [ { network: 'telegram', url: 'https://t.me/example' }, { network: 'youtube', url: 'https://youtube.com/@example' } ] } );
		await page.goto( `${ URL }/`, { waitUntil: 'networkidle' } );
		const socials = footer.locator( '.site-footer__social-link' );
		const labels = await socials.evaluateAll( ( els ) => els.map( ( el ) => `${ el.getAttribute( 'aria-label' ) }|${ el.target }|${ el.rel }|${ el.querySelector( 'svg' ).getAttribute( 'fill' ) }` ) );
		check( 'Соцсети появляются: значки, подписи, новая вкладка', JSON.stringify( labels ) === JSON.stringify( [ 'Telegram|_blank|noopener|currentColor', 'YouTube|_blank|noopener|currentColor' ] ), labels.join( ', ' ) );
		const socialBox = await socials.first().boundingBox();
		check( 'Значок соцсети 44 × 44 px', Math.round( socialBox.width ) === 44 && Math.round( socialBox.height ) === 44 );
		await page.screenshot( { path: path.join( OUT, 'footer-socials-1280.png' ), clip: await footer.boundingBox() } );
		setSettings( { social_links: saved.social_links || [] } );

		// --- Ширины ---
		for ( const w of [ 1280, 1180, 1179, 1024, 768, 600, 390, 320, 280 ] ) {
			await page.setViewportSize( { width: w, height: 800 } );
			await page.goto( `${ URL }/`, { waitUntil: 'networkidle' } );
			const scroll = await page.evaluate( () => document.documentElement.scrollWidth > window.innerWidth );
			let small = [];
			if ( w < 1180 ) {
				small = await page.evaluate( () => [ ...document.querySelectorAll( '.site-footer a' ) ]
					.filter( ( el ) => el.offsetParent !== null )
					.map( ( el ) => ( { t: el.textContent.trim(), h: Math.round( el.getBoundingClientRect().height ) } ) )
					.filter( ( r ) => r.h < 44 ) );
			}
			check( `Ширина ${ w }px: без прокрутки вбок${ w < 1180 ? ', ссылки ≥ 44 px' : '' }`, ! scroll && small.length === 0, scroll ? 'прокрутка вбок' : JSON.stringify( small ) );
		}

		// --- Сверка с макетом ---
		setSettings( { address: { city: '[Адрес офиса]', street: '', office: '', postal_code: '' }, hours: '[Часы работы]', footer_legal: saved.footer_legal.replace( '{год}', '[год]' ) } );
		for ( const w of [ 1280, 390 ] ) {
			await page.setViewportSize( { width: w, height: 900 } );
			await page.goto( `${ URL }/`, { waitUntil: 'networkidle' } );
			await settle( page );
			await isolate( page, '.site-footer' );
			await footer.screenshot( { path: path.join( OUT, `footer-theme-${ w }.png` ) } );
			const themeH = ( await footer.boundingBox() ).height;
			await page.goto( mockupUrl( 'portfolio-blog-404/portfolio.html' ), { waitUntil: 'networkidle' } );
			await settle( page );
			await isolate( page, 'footer.dc-foot' );
			const mock = page.locator( 'footer.dc-foot' );
			await mock.screenshot( { path: path.join( OUT, `footer-mockup-${ w }.png` ) } );
			const mockH = ( await mock.boundingBox() ).height;
			const diff = comparePng( path.join( OUT, `footer-mockup-${ w }.png` ), path.join( OUT, `footer-theme-${ w }.png` ), path.join( OUT, `footer-diff-${ w }.png` ) );
			check( `Совпадение с макетом на ${ w }px (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %, высота: тема ${ themeH }, макет ${ mockH }` );
		}
	} finally {
		setSettings( saved );
	}

	// --- Доступность ---
	await page.setViewportSize( { width: 1280, height: 900 } );
	await page.goto( `${ URL }/`, { waitUntil: 'networkidle' } );
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
