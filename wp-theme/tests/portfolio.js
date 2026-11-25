/**
 * Тест блока шага 4.16 — «Кейсы» на странице «Портфолио» (trust/cases-grid).
 *
 * Запуск: node wp-theme/tests/portfolio.js
 *
 * Демо на /portfolio/ (макет portfolio-blog-404/portfolio.html): кнопки услуг с числом кейсов
 * в порядке макета, список отраслей, «Все проекты · 9 кейсов», главный кейс крупно (отрасль,
 * услуги, 4 результата), сетка из 8 карточек (новые первыми, услуги — в порядке, в каком их
 * отметили), «Показать ещё» скрыта (показаны все 9). Фильтры без перезагрузки: числа на кнопках
 * по выбранной отрасли, «Найдено: N», сброс, пустой результат, адрес ?service=…&industry=… (и
 * открытие страницы по такому адресу). «Показать ещё» через REST (фокус на первую новую карточку,
 * без повторов, главный кейс не повторяется), без JavaScript — ?cases=all. REST: фильтры, лимит,
 * черновики и кейсы под паролем не попадают. Свои данные: кейс без обложки, услуг и результатов,
 * без главного кейса. ItemList в микроразметке. Сверка с макетом на 1280 / 768 / 390 px, ширины
 * 1440–280, раскладка 3 / 2 / 1, кнопки услуг на телефоне листаются строкой; правки заказчика:
 * «Смотреть кейс» — фиолетовая кнопка в правом нижнем углу (у главного кейса крупнее), список
 * «Отрасль» на телефоне по ширине пунктов. axe-core, консоль, редактор.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp } = require( './lib' );

const PAGE = `${ URL }/portfolio/`;
const MOCKUP = 'portfolio-blog-404/portfolio.html';
const MARK = 'ТестПортфолио';

/** PHP: блок по имени и полям (PHP-массив). */
const block = ( name, fields ) => `Trust\\Blocks::serialize( '${ name }', ${ fields } )`;

/**
 * Опубликованная тестовая страница «Портфолио» с блоками.
 *
 * @param {string} title  Заголовок.
 * @param {string} blocks PHP-выражение: разметка блоков.
 * @return {string} ID.
 */
function createPage( title, blocks ) {
	const content = wp( 'eval', `echo ${ blocks };` );
	const id = wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK } ${ title }`, '--post_status=publish', `--post_content=${ content }`, '--porcelain' );
	wp( 'post', 'meta', 'update', id, '_wp_page_template', 'templates/portfolio.php' );
	return id;
}

/**
 * Состояние блока: кнопки, отрасль, «Найдено», главный кейс, карточки, «Показать ещё».
 *
 * @param {import('playwright').Page} page Страница.
 */
function state( page ) {
	return page.evaluate( () => {
		const root = document.querySelector( '.b-cases' );
		const more = root.querySelector( '[data-cases-more-wrap]' );
		return {
			chips: [ ...root.querySelectorAll( '.filter-chip' ) ].map( ( c ) => `${ c.firstChild.textContent }:${ c.querySelector( '[data-count]' ).textContent }${ 'true' === c.getAttribute( 'aria-pressed' ) ? '*' : '' }` ),
			industry: root.querySelector( '.b-cases__select' ).value,
			found: root.querySelector( '[data-cases-found]' ).textContent.trim(),
			reset: ! root.querySelector( '.b-cases__reset' ).hidden,
			lead: !! root.querySelector( '[data-cases-lead]' ) && ! root.querySelector( '[data-cases-lead]' ).hidden,
			cards: [ ...root.querySelectorAll( '.b-cases__card' ) ].map( ( a ) => a.getAttribute( 'href' ).replace( /^.*\/portfolio\/|\/$/g, '' ) ),
			list: ! root.querySelector( '[data-cases-list]' ).hidden,
			empty: ! root.querySelector( '[data-cases-empty]' ).hidden,
			more: more.hidden ? '' : more.querySelector( '[data-cases-shown]' ).textContent.trim(),
			search: location.search,
		};
	} );
}

/**
 * Снимки блока темы и секции макета и их сравнение. Обложки в теме — из исходных файлов (как в
 * макете), чтобы сравнивалось оформление, а не уменьшенные копии; в макете ленивые картинки
 * загружаются сразу. «Показать ещё» в макете скрыта: в демо все 9 кейсов на первой странице.
 *
 * @param {import('playwright').Page} page  Страница.
 * @param {number}                    width Ширина окна.
 */
async function compare( page, width ) {
	const base = path.join( OUT, `portfolio-cases-${ width }` );
	await page.setViewportSize( { width, height: 900 } );
	await page.goto( PAGE, { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	await isolate( page, '.b-cases' );
	await page.evaluate( () => Promise.all( [ ...document.querySelectorAll( '.b-cases img' ) ].map( ( img ) => {
		img.removeAttribute( 'srcset' );
		img.loading = 'eager';
		img.src = img.src.replace( /-\d+x\d+(\.\w+)$/, '$1' );
		return img.decode().catch( () => {} );
	} ) ) );
	await page.waitForTimeout( 300 );
	await page.locator( '.b-cases' ).screenshot( { path: `${ base }-theme.png` } );
	await page.goto( mockupUrl( MOCKUP ), { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	await isolate( page, '#cases' );
	await page.addStyleTag( { content: '#cases > div > div:last-child { display: none !important; }' } );
	await page.evaluate( () => Promise.all( [ ...document.querySelectorAll( '#cases img' ) ].map( ( img ) => {
		img.loading = 'eager';
		return img.decode().catch( () => {} );
	} ) ) );
	await page.waitForTimeout( 300 );
	await page.locator( '#cases' ).screenshot( { path: `${ base }-mockup.png` } );
	const diff = comparePng( `${ base }-mockup.png`, `${ base }-theme.png`, `${ base }-diff.png` );
	check( `Макет ${ MOCKUP }, «Кейсы», ${ width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
}

( async () => {
	clearLog();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const created = [];
	const restore = [];
	const slug = ( name ) => wp( 'eval', `echo get_page_by_path( '${ name }', OBJECT, 'case' )->ID ?? 0;` );
	const repair = slug( 'novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir' );

	try {
		// ===== Демо: состояние после загрузки =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const order = await page.evaluate( () => [ ...document.querySelectorAll( '.page-entry__content > section, .page-entry__content > div > section, main section' ) ].map( ( s ) => s.className.split( ' ' )[ 0 ] ).filter( ( c ) => /^b-|hero/.test( c ) ) );
		const iHero = order.findIndex( ( c ) => /hero/.test( c ) );
		check( 'Демо /portfolio/: «Кейсы» после первого экрана и перед отзывами', iHero >= 0 && order[ iHero + 1 ] === 'b-cases' && order[ iHero + 2 ] === 'b-reviews', order.join( ', ' ) );
		const start = await state( page );
		check( 'Кнопки услуг в порядке макета, с числом кейсов, «Все» нажата',
			JSON.stringify( start.chips ) === JSON.stringify( [ 'Все:9*', 'SEO:6', 'GEO:2', 'Сайты:4', 'Контекстная реклама:3', 'Сопровождение:2' ] ), JSON.stringify( start.chips ) );
		const options = await page.locator( '.b-cases__select option' ).allTextContents();
		check( 'Список «Отрасль»: «Все отрасли» и 8 отраслей кейсов', 9 === options.length && 'Все отрасли' === options[ 0 ] && options.includes( 'Медицина' ) && options.includes( 'Интернет-магазины' ), options.join( ', ' ) );
		check( '«Все проекты · 9 кейсов», сброса нет, главный кейс крупно, 8 карточек, «Показать ещё» скрыта (показаны все)',
			'Все проекты · 9 кейсов' === start.found && ! start.reset && start.lead && 8 === start.cards.length && ! start.cards.includes( 'novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir' ) && '' === start.more && '' === start.search, JSON.stringify( start ) );
		const feat = await page.evaluate( () => {
			const f = document.querySelector( '.b-cases__feat' );
			return {
				href: f.getAttribute( 'href' ).replace( /^.*\/portfolio\//, '' ),
				badge: f.querySelector( '.b-cases__badge' ).textContent.trim(),
				pills: [ ...f.querySelectorAll( '.b-cases__pill' ) ].map( ( p ) => p.textContent + ( p.classList.contains( 'b-cases__pill--niche' ) ? '*' : '' ) ),
				h3: f.querySelector( 'h3' ).textContent,
				stats: [ ...f.querySelectorAll( '.b-cases__feat-stats > span' ) ].map( ( s ) => s.textContent ),
				more: f.querySelector( '.b-cases__feat-more' ).textContent.trim(),
				img: [ f.querySelector( 'img' ).alt, f.querySelector( 'img' ).loading ],
			};
		} );
		check( 'Главный кейс: ссылка на кейс, плашка «Главный кейс», отрасль и услуги (SEO, Сайты), H3, 4 результата, «Смотреть кейс», картинка без отложенной загрузки',
			'novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir/' === feat.href && 'Главный кейс' === feat.badge && JSON.stringify( feat.pills ) === JSON.stringify( [ 'Ремонт и строительство*', 'SEO', 'Сайты' ] ) &&
			'Новый сайт и SEO для компании по ремонту квартир' === feat.h3 && 4 === feat.stats.length && '+[N]%визитов из поиска' === feat.stats[ 0 ] && 'Смотреть кейс' === feat.more && 'eager' === feat.img[ 1 ] && feat.img[ 0 ], JSON.stringify( feat ) );
		const cards = await page.evaluate( () => [ ...document.querySelectorAll( '.b-cases__card' ) ].map( ( a ) => ( {
			niche: a.querySelector( '.b-cases__niche' ) ? a.querySelector( '.b-cases__niche' ).textContent : '',
			tags: [ ...a.querySelectorAll( '.b-cases__tags > span' ) ].map( ( t ) => t.textContent ).join( ',' ),
			h3: a.querySelector( 'h3' ).textContent,
			desc: !! a.querySelector( '.b-cases__desc' ),
			stats: a.querySelectorAll( '.b-cases__stats > span' ).length,
			go: a.querySelector( '.b-cases__go' ).textContent.trim(),
			img: [ !! a.querySelector( 'img' ).alt, a.querySelector( 'img' ).loading, !! a.querySelector( 'img' ).getAttribute( 'srcset' ) ],
		} ) ) );
		check( 'Карточки: новые первыми (стоматология, мебель, автосервис…), отрасль на обложке, H3, анонс, 2 результата, «Смотреть кейс», alt, srcset, отложенная загрузка',
			'Локальное SEO и карты для стоматологии' === cards[ 0 ].h3 && 'Медицина' === cards[ 0 ].niche && cards.every( ( c ) => c.niche && c.desc && 2 === c.stats && 'Смотреть кейс' === c.go && c.img[ 0 ] && 'lazy' === c.img[ 1 ] && c.img[ 2 ] ), JSON.stringify( cards.slice( 0, 2 ) ) );
		check( 'Услуги карточки — в порядке, в каком их отметили (основная первой): «SEO,GEO», «Сайты,Контекстная реклама», «Сайты,SEO», «Сопровождение,SEO»',
			'SEO,GEO' === cards[ 0 ].tags && 'Сайты,Контекстная реклама' === cards[ 1 ].tags && 'Сайты,SEO' === cards[ 5 ].tags && 'Сопровождение,SEO' === cards[ 6 ].tags, cards.map( ( c ) => c.tags ).join( ' | ' ) );
		const a11y = await page.evaluate( () => {
			const root = document.querySelector( '.b-cases' );
			const h2 = root.querySelector( 'h2' );
			return [ root.id, h2.textContent, h2.classList.contains( 'screen-reader-text' ), root.getAttribute( 'aria-labelledby' ) === h2.id, root.querySelector( '.filter-chips' ).getAttribute( 'role' ), !! document.getElementById( root.querySelector( '.filter-chips' ).getAttribute( 'aria-labelledby' ) ),
				root.querySelector( 'label' ).htmlFor === root.querySelector( 'select' ).id, root.querySelector( '[data-cases-found]' ).getAttribute( 'aria-live' ), root.querySelector( '[data-cases-empty]' ).getAttribute( 'role' ) ];
		} );
		check( 'Доступность: якорь #cases, H2 «Кейсы» для скринридеров, группа кнопок с подписью «Услуга», подпись списка, «Найдено» — aria-live, пустой результат — role=status',
			JSON.stringify( a11y ) === JSON.stringify( [ 'cases', 'Кейсы', true, true, 'group', true, true, 'polite', 'status' ] ), JSON.stringify( a11y ) );

		// ===== Фильтры =====
		await page.locator( '.b-cases .filter-chip', { hasText: 'SEO' } ).first().click();
		await page.waitForFunction( () => ! document.querySelector( '[data-cases-list]' ).hasAttribute( 'aria-busy' ) && 6 === document.querySelectorAll( '.b-cases__card' ).length );
		const seo = await state( page );
		check( 'Нажата «SEO»: «Найдено: 6 кейсов», сброс виден, главный кейс скрыт — он в сетке, 6 карточек, адрес ?service=seo',
			'SEO:6*' === seo.chips[ 1 ] && 'Найдено: 6 кейсов' === seo.found && seo.reset && ! seo.lead && 6 === seo.cards.length && 'novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir' === seo.cards[ 0 ] && '?service=seo' === seo.search && '' === seo.more, JSON.stringify( seo ) );
		await page.selectOption( '.b-cases__select', 'internet-magaziny' );
		await page.waitForFunction( () => 1 === document.querySelectorAll( '.b-cases__card' ).length );
		const shop = await state( page );
		check( 'Плюс «Интернет-магазины»: числа на кнопках — по отрасли (Все 2, SEO 1, GEO 0…), «Найдено: 1 кейс», адрес с обоими фильтрами',
			JSON.stringify( shop.chips ) === JSON.stringify( [ 'Все:2', 'SEO:1*', 'GEO:0', 'Сайты:1', 'Контекстная реклама:1', 'Сопровождение:1' ] ) && 'Найдено: 1 кейс' === shop.found && 'seo-dlya-cvetochnogo-internet-magazina' === shop.cards[ 0 ] && '?service=seo&industry=internet-magaziny' === shop.search, JSON.stringify( shop ) );
		await page.locator( '.b-cases .filter-chip', { hasText: 'GEO' } ).click();
		await page.waitForFunction( () => ! document.querySelector( '[data-cases-empty]' ).hidden && ! document.querySelector( '[data-cases-list]' ).hasAttribute( 'aria-busy' ) );
		const none = await state( page );
		const emptyText = await page.locator( '.b-cases__empty' ).innerText();
		check( 'GEO + «Интернет-магазины»: «Найдено: 0 кейсов», «Таких кейсов пока нет», список скрыт',
			'Найдено: 0 кейсов' === none.found && none.empty && ! none.list && 0 === none.cards.length && emptyText.includes( 'Таких кейсов пока нет' ) && emptyText.includes( 'Показать все кейсы' ), JSON.stringify( none ) );
		await page.locator( '.b-cases__empty-btn' ).click();
		await page.waitForFunction( () => 8 === document.querySelectorAll( '.b-cases__card' ).length );
		const back = await state( page );
		const focused = await page.evaluate( () => [ document.activeElement.classList.contains( 'filter-chip' ), document.activeElement.dataset.service ] );
		check( '«Показать все кейсы»: фильтры сброшены, снова главный кейс и 8 карточек, адрес без фильтров, фокус на «Все»',
			JSON.stringify( back.chips ) === JSON.stringify( start.chips ) && '' === back.industry && back.lead && JSON.stringify( back.cards ) === JSON.stringify( start.cards ) && ! back.reset && ! back.empty && '' === back.search && focused[ 0 ] && '' === focused[ 1 ], JSON.stringify( [ back, focused ] ) );
		await page.selectOption( '.b-cases__select', 'medicina' );
		await page.waitForFunction( () => 1 === document.querySelectorAll( '.b-cases__card' ).length );
		await page.locator( '.b-cases__reset' ).click();
		await page.waitForFunction( () => 8 === document.querySelectorAll( '.b-cases__card' ).length );
		const resetTop = await state( page );
		check( '«Сбросить фильтры» над списком — то же, сброс исчезает', '' === resetTop.industry && resetTop.lead && ! resetTop.reset && '' === resetTop.search, JSON.stringify( resetTop ) );

		// Открытие по ссылке с фильтрами — список сразу отфильтрован (без JavaScript тоже).
		await page.goto( `${ PAGE }?service=seo&industry=medicina`, { waitUntil: 'networkidle' } );
		const linked = await state( page );
		check( 'Адрес ?service=seo&industry=medicina: выбраны SEO и «Медицина», «Найдено: 1 кейс», одна карточка, сброс виден',
			'SEO:1*' === linked.chips[ 1 ] && 'medicina' === linked.industry && 'Найдено: 1 кейс' === linked.found && JSON.stringify( linked.cards ) === JSON.stringify( [ 'lokalnoe-seo-i-karty-dlya-stomatologii' ] ) && ! linked.lead && linked.reset, JSON.stringify( linked ) );
		await page.goto( `${ PAGE }?service=net-takoy&industry=<b>`, { waitUntil: 'networkidle' } );
		const bad = await state( page );
		check( 'Несуществующие услуга и отрасль в адресе не учитываются', 'Все:9*' === bad.chips[ 0 ] && '' === bad.industry && bad.lead && 8 === bad.cards.length, JSON.stringify( bad ) );

		// ===== «Показать ещё» (REST) =====
		const paged = createPage( 'По три', block( 'cases-grid', "array( 'per_page' => 3 )" ) );
		created.push( paged );
		const pagedUrl = `${ URL }/?page_id=${ paged }`;
		await page.goto( pagedUrl, { waitUntil: 'networkidle' } );
		const p1 = await state( page );
		check( 'По 3: главный кейс и 2 карточки, «Показано 3 из 9»', p1.lead && 2 === p1.cards.length && 'Показано 3 из 9' === p1.more, JSON.stringify( p1 ) );
		await page.locator( '[data-cases-more]' ).click();
		await page.waitForFunction( () => 5 === document.querySelectorAll( '.b-cases__card' ).length );
		const p2 = await state( page );
		const focus2 = await page.evaluate( () => document.activeElement === document.querySelectorAll( '.b-cases__card' )[ 2 ] );
		check( '«Показать ещё кейсы»: ещё 3 без перезагрузки, «Показано 6 из 9», фокус на первой новой карточке', 'Показано 6 из 9' === p2.more && focus2 && JSON.stringify( p2.cards.slice( 0, 2 ) ) === JSON.stringify( p1.cards ), JSON.stringify( p2 ) );
		await page.locator( '[data-cases-more]' ).click();
		await page.waitForFunction( () => 8 === document.querySelectorAll( '.b-cases__card' ).length );
		const p3 = await state( page );
		check( 'Ещё раз — все 9 (главный + 8), без повторов, главного кейса в сетке нет, кнопка скрыта',
			'' === p3.more && 8 === new Set( p3.cards ).size && ! p3.cards.includes( 'novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir' ) && JSON.stringify( p3.cards ) === JSON.stringify( start.cards ), JSON.stringify( p3 ) );
		await page.locator( '.b-cases .filter-chip', { hasText: 'SEO' } ).first().click();
		await page.waitForFunction( () => 3 === document.querySelectorAll( '.b-cases__card' ).length );
		const f1 = await state( page );
		await page.locator( '[data-cases-more]' ).click();
		await page.waitForFunction( () => 6 === document.querySelectorAll( '.b-cases__card' ).length );
		const f2 = await state( page );
		check( 'С фильтром SEO: 3 карточки и «Показано 3 из 6», затем все 6 без повторов, кнопка скрыта',
			'Показано 3 из 6' === f1.more && '' === f2.more && 6 === new Set( f2.cards ).size && f2.cards.includes( 'novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir' ), JSON.stringify( [ f1, f2 ] ) );

		// ===== REST =====
		const rest = async ( query ) => {
			const res = await page.request.get( `${ URL }/wp-json/trust/v1/cases?${ query }` );
			return [ res.status(), res.ok() ? await res.json() : null ];
		};
		const r1 = await rest( 'service=seo&offset=0&per=4' );
		const r2 = await rest( 'service=seo&offset=4&per=4' );
		const r3 = await rest( `per=24&skip=${ repair }` );
		const r4 = await rest( 'per=100' );
		const r5 = await rest( 'industry=net-takoy' );
		check( 'REST trust/v1/cases: SEO по 4 (4 + 2 из 6), skip убирает главный кейс (8), больше 24 за раз нельзя, пустой результат',
			200 === r1[ 0 ] && 4 === r1[ 1 ].count && 6 === r1[ 1 ].total && 2 === r2[ 1 ].count && 8 === r3[ 1 ].total && ! r3[ 1 ].html.includes( 'novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir' ) && 400 === r4[ 0 ] && 0 === r5[ 1 ].total && '' === r5[ 1 ].html,
			JSON.stringify( [ r1[ 1 ] && [ r1[ 1 ].count, r1[ 1 ].total ], r2[ 1 ] && r2[ 1 ].count, r3[ 1 ] && r3[ 1 ].total, r4[ 0 ], r5[ 1 ] ] ) );
		check( 'REST: карточка — та же разметка, что на странице (li > a.b-cases__card)', /^<li class="b-cases__item"><a class="b-cases__card" href="[^"]+\/portfolio\/[^"]+\/">/.test( r1[ 1 ].html ) );

		// ===== Без JavaScript =====
		const noJs = await browser.newContext( { javaScriptEnabled: false, viewport: { width: 1280, height: 900 } } );
		const plain = await noJs.newPage();
		await plain.goto( pagedUrl, { waitUntil: 'domcontentloaded' } );
		const nj = await plain.evaluate( () => [ getComputedStyle( document.querySelector( '.b-cases .filter-chips' ) ).display, getComputedStyle( document.querySelector( '.b-cases__filters' ) ).display, new URL( document.querySelector( '[data-cases-more]' ).href ).search + new URL( document.querySelector( '[data-cases-more]' ).href ).hash ] );
		await plain.goto( `${ pagedUrl }&cases=all`, { waitUntil: 'domcontentloaded' } );
		const all = await plain.evaluate( () => [ document.querySelectorAll( '.b-cases__card' ).length, !! document.querySelector( '.b-cases__feat' ), document.querySelector( '[data-cases-more-wrap]' ).hidden ] );
		await plain.goto( `${ PAGE }?service=sayty`, { waitUntil: 'domcontentloaded' } );
		const njFiltered = await plain.evaluate( () => [ document.querySelectorAll( '.b-cases__card' ).length, document.querySelector( '[data-cases-found]' ).textContent ] );
		await noJs.close();
		check( 'Без JavaScript: фильтров нет, «Показать ещё» ведёт на ?cases=all#cases — там все кейсы; ссылка с ?service= открывает отфильтрованный список',
			'none' === nj[ 1 ] && '?cases=all#cases' === nj[ 2 ] && 8 === all[ 0 ] && all[ 1 ] && all[ 2 ] && 4 === njFiltered[ 0 ] && 'Найдено: 4 кейса' === njFiltered[ 1 ], JSON.stringify( [ nj, all, njFiltered ] ) );

		// ===== Микроразметка =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const ld = await page.evaluate( () => {
			const graph = [ ...document.querySelectorAll( 'script[type="application/ld+json"]' ) ].flatMap( ( s ) => JSON.parse( s.textContent )[ '@graph' ] || [] );
			const webpage = graph.find( ( n ) => 'CollectionPage' === n[ '@type' ] );
			const list = graph.find( ( n ) => 'ItemList' === n[ '@type' ] );
			return [ webpage && webpage.mainEntity && webpage.mainEntity[ '@id' ], list && list[ '@id' ], list && list.numberOfItems, list && list.itemListElement[ 0 ].name, list && list.itemListElement.every( ( i, n ) => 'ListItem' === i[ '@type' ] && n + 1 === i.position && i.url.includes( '/portfolio/' ) ) ];
		} );
		check( 'Микроразметка: CollectionPage → ItemList из 9 кейсов (главный первым, адреса и названия)',
			JSON.stringify( ld ) === JSON.stringify( [ `${ PAGE }#items`, `${ PAGE }#items`, 9, 'Новый сайт и SEO для компании по ремонту квартир', true ] ), JSON.stringify( ld ) );

		// ===== Сверка с макетом =====
		for ( const width of [ 1280, 768, 390 ] ) {
			await compare( page, width );
		}

		// ===== Свои данные =====
		const bare = wp( 'eval', `echo wp_insert_post( array( 'post_type' => 'case', 'post_status' => 'publish', 'post_title' => '${ MARK } без всего', 'post_date' => '2020-01-01 10:00:00' ) );` );
		const hidden = [
			wp( 'eval', `echo wp_insert_post( array( 'post_type' => 'case', 'post_status' => 'draft', 'post_title' => '${ MARK } черновик' ) );` ),
			wp( 'eval', `echo wp_insert_post( array( 'post_type' => 'case', 'post_status' => 'publish', 'post_title' => '${ MARK } под паролем', 'post_password' => 'secret' ) );` ),
		];
		created.push( bare, ...hidden );
		await page.goto( `${ pagedUrl }&cases=all`, { waitUntil: 'networkidle' } );
		const own = await page.evaluate( () => {
			const last = [ ...document.querySelectorAll( '.b-cases__card' ) ].pop();
			return {
				all: document.querySelector( '.filter-chip [data-count]' ).textContent,
				cards: document.querySelectorAll( '.b-cases__card' ).length,
				titles: [ ...document.querySelectorAll( '.b-cases__card h3' ) ].map( ( h ) => h.textContent ),
				last: [ last.querySelector( 'h3' ).textContent, !! last.querySelector( '.b-cases__img--empty' ), !! last.querySelector( 'img' ), !! last.querySelector( '.b-cases__niche' ), !! last.querySelector( '.b-cases__tags' ), !! last.querySelector( '.b-cases__stats' ), !! last.querySelector( '.b-cases__desc' ), last.querySelector( '.b-cases__go' ).textContent.trim() ],
			};
		} );
		check( 'Кейс без обложки, отрасли, услуг, анонса и результатов — в конце (старый): пустая обложка, только заголовок и «Смотреть кейс»; черновик и кейс под паролем не показаны, «Все 10»',
			'10' === own.all && 9 === own.cards && JSON.stringify( own.last ) === JSON.stringify( [ `${ MARK } без всего`, true, false, false, false, false, false, 'Смотреть кейс' ] ) && ! own.titles.some( ( t ) => t.includes( 'черновик' ) || t.includes( 'паролем' ) ), JSON.stringify( own ) );
		const featuredMeta = wp( 'eval', `echo get_post_meta( ${ repair }, Trust\\Blog::FEATURED, true );` );
		restore.push( () => wp( 'eval', `update_post_meta( ${ repair }, Trust\\Blog::FEATURED, '${ featuredMeta }' );` ) );
		wp( 'eval', `delete_post_meta( ${ repair }, Trust\\Blog::FEATURED );` );
		await page.goto( pagedUrl, { waitUntil: 'networkidle' } );
		const noLead = await state( page );
		check( 'Без главного кейса: крупной карточки нет, в сетке 3 карточки (новые первыми), «Показано 3 из 10»',
			! noLead.lead && 0 === await page.locator( '.b-cases__feat' ).count() && 3 === noLead.cards.length && 'novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir' === noLead.cards[ 0 ] && 'Показано 3 из 10' === noLead.more, JSON.stringify( noLead ) );
		await page.locator( '[data-cases-more]' ).click();
		await page.waitForFunction( () => 6 === document.querySelectorAll( '.b-cases__card' ).length );
		const noLead2 = await state( page );
		check( 'Без главного кейса «Показать ещё» догружает следующие 3 без повторов', 6 === new Set( noLead2.cards ).size && 'Показано 6 из 10' === noLead2.more, JSON.stringify( noLead2 ) );
		restore.pop()();
		[ bare, ...hidden ].forEach( ( id ) => wp( 'post', 'delete', String( id ), '--force' ) );
		created.splice( created.indexOf( bare ), 3 );

		// ===== Ширины, раскладка, доступность =====
		const wide = [];
		for ( const width of [ 1440, 1024, 768, 390, 320, 280 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( PAGE, { waitUntil: 'domcontentloaded' } );
			if ( await page.evaluate( () => document.documentElement.scrollWidth > innerWidth ) ) {
				wide.push( width );
			}
		}
		check( 'Ширины 1440–280: без прокрутки вбок', 0 === wide.length, wide.join( ', ' ) );
		const columns = {};
		for ( const width of [ 1280, 768, 390 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( PAGE, { waitUntil: 'domcontentloaded' } );
			columns[ width ] = await page.evaluate( () => {
				const lefts = new Set( [ ...document.querySelectorAll( '.b-cases__item' ) ].map( ( li ) => Math.round( li.getBoundingClientRect().left ) ) );
				const chips = document.querySelector( '.b-cases__chips' );
				const tops = new Set( [ ...chips.children ].map( ( c ) => Math.round( c.getBoundingClientRect().top ) ) );
				return [ lefts.size, tops.size, chips.scrollWidth > chips.clientWidth ];
			} );
		}
		check( 'Сетка 3 / 2 / 1 колонки (1280 / 768 / 390); на телефоне кнопки услуг одной строкой с прокруткой',
			3 === columns[ 1280 ][ 0 ] && 2 === columns[ 768 ][ 0 ] && 1 === columns[ 390 ][ 0 ] && 1 === columns[ 390 ][ 1 ] && columns[ 390 ][ 2 ], JSON.stringify( columns ) );
		// Правки заказчика: «Смотреть кейс» — фиолетовая кнопка в правом нижнем углу карточки, у
		// главного кейса крупнее; на телефоне список «Отрасль» — по ширине пунктов, а не панели.
		const buttons = {};
		for ( const width of [ 1280, 390 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( PAGE, { waitUntil: 'domcontentloaded' } );
			buttons[ width ] = await page.evaluate( () => {
				const place = ( body, btn ) => {
					const c = body.getBoundingClientRect();
					const b = btn.getBoundingClientRect();
					const cs = getComputedStyle( body );
					const st = getComputedStyle( btn );
					return [ Math.abs( c.right - parseFloat( cs.paddingRight ) - b.right ) < 1 && Math.abs( c.bottom - parseFloat( cs.paddingBottom ) - b.bottom ) < 1, Math.round( b.height ), st.backgroundColor, st.color ];
				};
				const feat = document.querySelector( '.b-cases__feat-body' );
				const cards = [ ...document.querySelectorAll( '.b-cases__body' ) ];
				const select = document.querySelector( '.b-cases__select' ).getBoundingClientRect();
				const panel = document.querySelector( '.b-cases__filters' ).getBoundingClientRect();
				return {
					feat: place( feat, feat.querySelector( '.b-cases__feat-more' ) ),
					cards: [ ...new Set( cards.map( ( body ) => JSON.stringify( place( body, body.querySelector( '.b-cases__go' ) ) ) ) ) ],
					select: [ Math.round( select.width ), Math.round( panel.width ) ],
				};
			} );
		}
		const violet = [ 'rgb(109, 40, 217)', 'rgb(255, 255, 255)' ];
		check( '«Смотреть кейс»: фиолетовые кнопки в правом нижнем углу (1280 и 390 px), у главного кейса 52 px, у карточек 40 px (правка заказчика)',
			[ 1280, 390 ].every( ( w ) => JSON.stringify( buttons[ w ].feat ) === JSON.stringify( [ true, 52, ...violet ] ) && 1 === buttons[ w ].cards.length && buttons[ w ].cards[ 0 ] === JSON.stringify( [ true, 40, ...violet ] ) ), JSON.stringify( buttons ) );
		check( 'Телефон 390: список «Отрасль» по ширине пунктов (раскрытый список не шире названий), на компьютере — 280 px (правка заказчика)',
			buttons[ 390 ].select[ 0 ] >= 200 && buttons[ 390 ].select[ 0 ] < buttons[ 390 ].select[ 1 ] - 60 && 280 === buttons[ 1280 ].select[ 0 ], JSON.stringify( [ buttons[ 390 ].select, buttons[ 1280 ].select ] ) );
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
		const axe = await page.evaluate( async () => {
			const r = await window.axe.run( '.b-cases', { resultTypes: [ 'violations' ] } );
			return r.violations.filter( ( x ) => [ 'critical', 'serious' ].includes( x.impact ) ).map( ( x ) => `${ x.id } (${ x.nodes.length })` );
		} );
		check( 'axe: нет нарушений critical/serious в блоке «Кейсы»', 0 === axe.length, axe.join( ', ' ) );
		check( 'Консоль браузера на сайте без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1400, height: 1000 } } );
		const editorErrors = collectConsoleErrors( editor );
		await login( editor );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ paged }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		const reg = await editor.evaluate( () => {
			const type = wp.blocks.getBlockType( 'trust/cases-grid' );
			return [ type && type.title, type && type.category, !! ( type && type.example ), type && type.supports && type.supports.multiple ].join( ':' );
		} );
		check( 'Редактор: блок «Кейсы» в «Списках», с превью, один на странице', 'Кейсы:trust-lists:true:false' === reg, reg );
		const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-cases' ).first().waitFor( { timeout: 30000 } );
		const preview = await canvas.locator( '.b-cases' ).evaluate( ( root ) => [ !! root.querySelector( '.b-cases__feat' ), root.querySelectorAll( '.b-cases__card' ).length, root.querySelector( '.filter-chip' ).tabIndex ] );
		check( 'Редактор: превью — как на сайте (по 3: главный кейс и 2 карточки; больше 4 кейсов превью не показывает), кнопки не в порядке табуляции', JSON.stringify( preview ) === JSON.stringify( [ true, 2, -1 ] ), JSON.stringify( preview ) );
		check( 'Редактор без ошибок JavaScript', 0 === editorErrors.length, editorErrors.slice( 0, 3 ).join( ' | ' ) );
		await editor.close();
	} finally {
		restore.forEach( ( fn ) => fn() );
		created.forEach( ( id ) => wp( 'post', 'delete', String( id ), '--force' ) );
	}

	await browser.close();
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
