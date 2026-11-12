/**
 * Тест блока «Вопрос-ответ» (шаг 1.7) на локальном стенде с демо-контентом (страница /seo/audit/).
 *
 * Запуск: node wp-theme/tests/faq.js
 *
 * Сайт:
 * - разметка: секция с H2, надзаголовок, вводный текст, ссылка, 6 вопросов H3 с номерами 01–06;
 * - ответы закрыты; раскрываются по нажатию (несколько одновременно), закрываются повторным;
 *   клавиатура (Enter, пробел); aria-expanded и hidden; вид открытого пункта как в макете;
 * - без JavaScript ответы видны; на странице один H1;
 * - стили и скрипт блока подключаются только на страницах с блоком;
 * - совпадение с макетом seo-bloki/10-vopros-otvet.html на 1280 и 390 px — закрыто и с первым
 *   открытым ответом (≤ 2 %); ширины 280–1440 px без прокрутки вбок; axe-core.
 * Редактор:
 * - блок в категории «Услуги», есть превью; в документе показывается во всю ширину, ответы раскрыты;
 * - в боковой панели — поля блока.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp } = require( './lib' );

const PAGE = `${ URL }/seo/audit/`;
const QUESTIONS = [
	'Сколько времени занимает технический аудит?',
	'Какие доступы вам понадобятся?',
	'Вы сами исправляете найденные ошибки?',
	'В каком виде я получу отчёт?',
	'Можно заказать только одну проверку из списка?',
	'Гарантируете ли вы рост позиций?',
];

/**
 * Снимки блока темы и макета на ширине и их сравнение.
 *
 * @param {import('playwright').Page} page  Страница.
 * @param {number}                    width Ширина.
 * @param {boolean}                   open  Раскрыть первый ответ.
 */
async function compare( page, width, open ) {
	const name = `faq-${ open ? 'open' : 'closed' }-${ width }`;
	await page.setViewportSize( { width, height: 900 } );

	await page.goto( PAGE, { waitUntil: 'networkidle' } );
	await settle( page );
	if ( open ) {
		await page.locator( '.b-faq__toggle' ).first().click();
		await page.mouse.move( 0, 0 );
		await page.waitForTimeout( 300 );
	}
	await isolate( page, '.b-faq' );
	await page.locator( '.b-faq' ).screenshot( { path: path.join( OUT, `${ name }-theme.png` ) } );

	await page.goto( mockupUrl( 'seo-bloki/10-vopros-otvet.html' ), { waitUntil: 'networkidle' } );
	await settle( page );
	if ( open ) {
		await page.locator( 'section button' ).first().click();
		await page.mouse.move( 0, 0 );
		await page.waitForTimeout( 300 );
	}
	await isolate( page, 'section' );
	await page.locator( 'section' ).first().screenshot( { path: path.join( OUT, `${ name }-mockup.png` ) } );

	const diff = comparePng( path.join( OUT, `${ name }-mockup.png` ), path.join( OUT, `${ name }-theme.png` ), path.join( OUT, `${ name }-diff.png` ) );
	check( `Макет ${ width }px, ${ open ? 'первый ответ открыт' : 'всё закрыто' }: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
}

( async () => {
	clearLog();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );

	// --- Разметка ---
	await page.goto( PAGE, { waitUntil: 'networkidle' } );
	const faq = page.locator( '.b-faq' );
	const titleId = await faq.getAttribute( 'aria-labelledby' );
	check( 'Секция подписана заголовком H2 «Частые вопросы»', ( await page.locator( `h2#${ titleId }` ).textContent() ).trim() === 'Частые вопросы' );
	check( 'Надзаголовок и вводный текст', ( await faq.locator( '.eyebrow' ).textContent() ).trim() === 'Вопрос — ответ' && ( await faq.locator( '.section-lead' ).textContent() ).startsWith( 'Собрали ответы' ) );
	check( 'Ссылка «Не нашли ответ?» ведёт на страницу вопросов', ( await faq.locator( '.b-faq__more' ).getAttribute( 'href' ) ) === `${ URL }/seo/voprosy/` );
	const questions = ( await faq.locator( 'h3 .b-faq__label > span:last-child' ).allTextContents() ).map( ( t ) => t.trim() );
	check( '6 вопросов H3 по порядку', JSON.stringify( questions ) === JSON.stringify( QUESTIONS ), questions.join( ' | ' ) );
	const numbers = await faq.locator( '.b-faq__num' ).allTextContents();
	check( 'Номера 01–06', numbers.join( ',' ) === '01,02,03,04,05,06' );
	check( 'Один H1 на странице', ( await page.locator( 'h1' ).count() ) === 1 );
	check( 'Все ответы закрыты', ( await faq.locator( '.b-faq__answer:visible' ).count() ) === 0 && ( await faq.locator( '[aria-expanded="true"]' ).count() ) === 0 );

	// --- Раскрытие ---
	const item = ( i ) => faq.locator( '.b-faq__item' ).nth( i );
	await item( 0 ).locator( '.b-faq__toggle' ).click();
	check( 'Нажатие раскрывает ответ', await item( 0 ).locator( '.b-faq__answer' ).isVisible() && ( await item( 0 ).locator( '.b-faq__toggle' ).getAttribute( 'aria-expanded' ) ) === 'true' );
	check( 'Текст ответа без искажений', ( await item( 0 ).locator( '.b-faq__answer' ).textContent() ).trim().startsWith( 'Срок зависит от количества страниц' ) );
	await page.waitForTimeout( 300 );
	const look = await item( 0 ).evaluate( ( el ) => {
		const icon = el.querySelector( '.b-faq__icon' );
		return [ getComputedStyle( el ).backgroundColor, getComputedStyle( el ).borderTopColor, getComputedStyle( icon ).backgroundColor ].join( ' ' );
	} );
	check( 'Открытый пункт: лавандовый фон, рамка #CDBDFF, фиолетовая кнопка', look === 'rgb(248, 245, 255) rgb(205, 189, 255) rgb(109, 40, 217)', look );
	await item( 1 ).locator( '.b-faq__toggle' ).click();
	check( 'Открыты несколько ответов одновременно', ( await faq.locator( '.b-faq__item.is-open' ).count() ) === 2 );
	await item( 0 ).locator( '.b-faq__toggle' ).click();
	check( 'Повторное нажатие закрывает ответ', ! ( await item( 0 ).locator( '.b-faq__answer' ).isVisible() ) && ( await item( 1 ).locator( '.b-faq__answer' ).isVisible() ) );

	await item( 2 ).locator( '.b-faq__toggle' ).focus();
	await page.keyboard.press( 'Enter' );
	check( 'Enter раскрывает ответ', await item( 2 ).locator( '.b-faq__answer' ).isVisible() );
	await page.keyboard.press( 'Space' );
	check( 'Пробел закрывает ответ', ! ( await item( 2 ).locator( '.b-faq__answer' ).isVisible() ) );

	// --- Подключение стилей и скрипта ---
	const html = await page.content();
	check( 'На странице с блоком: стили и скрипт блока', /blocks\/faq\/style\.css|trust-faq-style/.test( html ) && /blocks\/faq\/view\.js/.test( html ) );
	await page.goto( `${ URL }/kontakty/`, { waitUntil: 'networkidle' } );
	const other = await page.content();
	check( 'На странице без блока стилей и скрипта блока нет', ! /blocks\/faq\//.test( other ) && ! /trust-faq-style/.test( other ) );

	// --- Сверка с макетом ---
	for ( const width of [ 1280, 390 ] ) {
		await compare( page, width, false );
		await compare( page, width, true );
	}

	// --- Ширины ---
	for ( const w of [ 1440, 1024, 768, 600, 390, 320, 280 ] ) {
		await page.setViewportSize( { width: w, height: 800 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const res = await page.evaluate( () => ( {
			scroll: document.documentElement.scrollWidth > innerWidth,
			small: [ ...document.querySelectorAll( '.b-faq a, .b-faq button' ) ].filter( ( el ) => el.getBoundingClientRect().height < 44 ).length,
		} ) );
		check( `Ширина ${ w }px: без прокрутки вбок, зоны ≥ 44 px`, ! res.scroll && 0 === res.small, JSON.stringify( res ) );
	}

	// --- Доступность ---
	await page.setViewportSize( { width: 1280, height: 900 } );
	await page.goto( PAGE, { waitUntil: 'networkidle' } );
	await page.locator( '.b-faq__toggle' ).first().click();
	await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
	const axe = await page.evaluate( async () => {
		const r = await window.axe.run( document, { resultTypes: [ 'violations' ] } );
		return r.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length })` );
	} );
	check( 'axe: нет нарушений critical/serious', axe.length === 0, axe.join( ', ' ) );
	check( 'Консоль браузера на сайте без ошибок', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );

	// --- Без JavaScript ---
	const noJs = await browser.newPage( { javaScriptEnabled: false } );
	await noJs.goto( PAGE );
	check( 'Без JavaScript ответы видны', ( await noJs.locator( '.b-faq__answer:visible' ).count() ) === 6 );
	await noJs.close();

	// --- Редактор ---
	const editor = await browser.newPage( { viewport: { width: 1440, height: 1000 } } );
	const editorErrors = [];
	editor.on( 'pageerror', ( e ) => editorErrors.push( e.message ) );
	await login( editor );
	const id = wp( 'eval', 'echo get_page_by_path( "seo/audit" )->ID;' );
	await editor.goto( `${ URL }/wp-admin/post.php?post=${ id }&action=edit` );
	await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
	await editor.evaluate( () => {
		try {
			wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
		} catch ( e ) {}
	} );
	const reg = await editor.evaluate( () => {
		const type = wp.blocks.getBlockType( 'trust/faq' );
		const inserter = wp.data.select( 'core/block-editor' ).getInserterItems().some( ( i ) => 'trust/faq' === i.name );
		return { title: type && type.title, category: type && type.category, example: !! ( type && type.example ), inserter, firstCategory: wp.blocks.getCategories()[ 0 ].title };
	} );
	check( 'Редактор: блок «Вопрос-ответ» в категории «Услуги», с превью, доступен для вставки', 'Вопрос-ответ' === reg.title && 'trust-services' === reg.category && reg.example && reg.inserter, JSON.stringify( reg ) );
	check( 'Редактор: категории темы первыми («Первые экраны»)', 'Первые экраны' === reg.firstCategory );

	const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
	await canvas.locator( '.b-faq' ).first().waitFor( { timeout: 30000 } );
	const box = await canvas.locator( '.b-faq' ).first().evaluate( ( el ) => ( { w: el.getBoundingClientRect().width, doc: el.ownerDocument.documentElement.clientWidth } ) );
	check( 'Редактор: блок во всю ширину документа, как на сайте', Math.abs( box.w - box.doc ) < 2, JSON.stringify( box ) );
	check( 'Редактор: в превью все ответы раскрыты', ( await canvas.locator( '.b-faq__item.is-open' ).count() ) === 6 );

	await editor.evaluate( () => {
		const block = wp.data.select( 'core/block-editor' ).getBlocks().find( ( b ) => 'trust/faq' === b.name );
		wp.data.dispatch( 'core/block-editor' ).selectBlock( block.clientId );
	} );
	await editor.waitForTimeout( 1500 );
	const sidebar = await editor.locator( '.interface-complementary-area' ).innerText().catch( () => '' );
	check( 'Редактор: поля блока в боковой панели', [ 'Надзаголовок', 'Заголовок (H2)', 'Вводный текст', 'Вопросы' ].every( ( label ) => sidebar.includes( label ) ), sidebar.slice( 0, 120 ).replace( /\s+/g, ' ' ) );
	await editor.screenshot( { path: path.join( OUT, 'faq-editor.png' ) } );
	check( 'Редактор без ошибок JavaScript', editorErrors.length === 0, editorErrors.slice( 0, 3 ).join( ' | ' ) );

	await browser.close();
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
