/**
 * Тест блоков шага 4.5: «Выбор тарифа», «Блок с текстом», «Картинка», «Главная мысль» (в макете —
 * «Почему мы открыто пишем про всё») на локальном стенде с демо-контентом (страница /seo/).
 *
 * Запуск: node wp-theme/tests/content-blocks.js
 *
 * «Выбор тарифа»: H2, три тарифа H3, «Рекомендуем» — тёмная карточка, состав (входит / не входит
 * с подписями для скринридеров), старая цена, скидка, цена и период, кнопка открывает окно звонка;
 * активный тариф — наведение мышью и Tab (на сенсорном экране нет, без подъёма при «Уменьшить движение»);
 * микроразметка — цены только из поля «Цена числом»: один тариф — Offer, несколько — AggregateOffer.
 * «Блок с текстом»: H2/H3, списки, цитата «С автором», «Важно» — <aside>, ссылки; колонка 960 px (правка заказчика);
 * в редакторе внутри только текстовые блоки, заголовки H2–H4.
 * «Картинка»: alt, srcset, картинка для телефона (<picture>, до 600 px), «Рис. N» по порядку
 * (0 — без подписи, свой номер, одинаковые блоки), ширина по тексту, декоративная, без картинки —
 * блока нет; в редакторе — предупреждение без alt.
 * «Главная мысль»: цитата, автор из «Команды» (должность — своя или из карточки),
 * вручную, без автора.
 * Общее: совпадение с макетами seo-bloki/01, 03, 05, 08 на 1280, 768 и 390 px (≤ 2 %); ширины
 * 1440–280 px; зоны нажатия; axe-core; консоль и журнал ошибок; редактор — категории и превью.
 */

const path = require( 'path' );
const { launch, URL, OUT, THEME, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp } = require( './lib' );

const PAGE = `${ URL }/seo/`;
const MARK = 'ТестБлоковКонтента';
const WORKS = [ 'Технический аудит сайта', 'Сбор семантического ядра', 'Оптимизация мета-тегов', 'Настройка Яндекс Вебмастера и Google Search Console', 'Внутренняя перелинковка', 'Тексты для продвигаемых страниц', 'Работа с внешними ссылками', 'Ежемесячный отчёт с рекомендациями' ];

/**
 * Создаёт опубликованную тестовую страницу «Услуга» с блоками.
 *
 * @param {string} title  Заголовок.
 * @param {string} blocks PHP-выражение: разметка блоков.
 * @return {string} ID страницы.
 */
function createPage( title, blocks ) {
	const content = wp( 'eval', `echo ${ blocks };` );
	const id = wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK } ${ title }`, '--post_status=publish', `--post_content=${ content }`, '--porcelain' );
	wp( 'post', 'meta', 'update', id, '_wp_page_template', 'templates/service.php' );
	return id;
}

/** PHP: блок по имени и полям (PHP-массив). */
const block = ( name, fields ) => `Trust\\Blocks::serialize( '${ name }', ${ fields } )`;

/** PHP: несколько блоков подряд. */
const blocks = ( ...list ) => list.join( ' . "\\n\\n" . ' );

/** Узлы JSON-LD страницы. */
async function graphOf( page, url ) {
	await page.goto( url, { waitUntil: 'networkidle' } );
	const scripts = await page.locator( 'script[type="application/ld+json"]' ).allTextContents();
	return scripts.length ? JSON.parse( scripts[ 0 ] )[ '@graph' ] : [];
}

/**
 * Снимки блока темы и макета и их сравнение.
 *
 * @param {import('playwright').Page} page Страница.
 * @param {Object}                    o    name, width, url, theme, mockup, mock, before (в теме), mockBefore (в макете, получает результат before).
 */
async function compare( page, o ) {
	const name = `${ o.name }-${ o.width }`;
	await page.setViewportSize( { width: o.width, height: 900 } );
	await page.goto( o.url || PAGE, { waitUntil: 'networkidle' } );
	await settle( page );
	const arg = o.before ? await page.evaluate( o.before ) : null;
	await page.mouse.move( 0, 0 ); // без наведения на карточки
	await isolate( page, o.theme );
	await page.locator( o.theme ).screenshot( { path: path.join( OUT, `${ name }-theme.png` ) } );

	await page.goto( mockupUrl( o.mockup ), { waitUntil: 'networkidle' } );
	await settle( page );
	if ( o.mockBefore ) {
		await page.evaluate( o.mockBefore, arg );
	}
	await isolate( page, o.mock );
	await page.locator( o.mock ).first().screenshot( { path: path.join( OUT, `${ name }-mockup.png` ) } );
	const diff = comparePng( path.join( OUT, `${ name }-mockup.png` ), path.join( OUT, `${ name }-theme.png` ), path.join( OUT, `${ name }-diff.png` ) );
	check( `Макет ${ o.mockup } ${ o.width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
}

( async () => {
	clearLog();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const created = [];
	let media = [];

	try {
		// ===== Страница целиком =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const order = await page.evaluate( () => [ ...document.querySelectorAll( '.page-entry__content > section' ) ].map( ( s ) => s.className.split( ' ' )[ 0 ] ) );
		check( 'Страница /seo/: первый экран, тарифы, текст, картинка, «Проблемы», «Главная мысль», бриф',
			JSON.stringify( order ) === JSON.stringify( [ 'b-hero-service', 'b-tariffs', 'b-text', 'b-image', 'b-problems', 'b-idea', 'b-brief' ] ), order.join( ', ' ) );

		// ===== «Выбор тарифа» =====
		const tar = page.locator( '.b-tariffs' );
		check( 'Тарифы: H2 «Выберите тариф SEO-продвижения», надзаголовок «Тарифы», якорь #tariffs',
			( await tar.locator( 'h2' ).textContent() ).trim() === 'Выберите тариф SEO-продвижения' && ( await tar.locator( '.eyebrow' ).textContent() ).trim() === 'Тарифы' &&
			( await tar.getAttribute( 'id' ) ) === 'tariffs' && ( await tar.getAttribute( 'aria-labelledby' ) ) === ( await tar.locator( 'h2' ).getAttribute( 'id' ) ) );
		const cards = await tar.locator( '.b-tariffs__card' ).evaluateAll( ( list ) => list.map( ( c ) => ( {
			name: c.querySelector( 'h3' ).textContent.trim(),
			rec: c.classList.contains( 'b-tariffs__card--rec' ),
			badge: c.querySelector( '.b-tariffs__badge' ) ? c.querySelector( '.b-tariffs__badge' ).textContent.trim() : '',
			old: c.querySelector( '.b-tariffs__old s' ) ? c.querySelector( '.b-tariffs__old s' ).textContent.trim() : '',
			discount: c.querySelector( '.b-tariffs__discount' ) ? c.querySelector( '.b-tariffs__discount' ).textContent.trim() : '',
			price: c.querySelector( '.b-tariffs__price' ).textContent.trim(),
			period: c.querySelector( '.b-tariffs__period' ).textContent.trim(),
			items: [ ...c.querySelectorAll( '.b-tariffs__item' ) ].map( ( li ) => [ li.textContent.trim(), li.querySelector( 'svg' ).getAttribute( 'aria-label' ), li.classList.contains( 'b-tariffs__item--no' ) ] ),
			btn: [ c.querySelector( '.b-tariffs__btn' ).textContent.trim(), c.querySelector( '.b-tariffs__btn' ).getAttribute( 'href' ) ],
			bg: getComputedStyle( c ).backgroundColor,
		} ) ) );
		check( 'Тарифы: «Старт», «Бизнес», «Максимум» (H3)', cards.map( ( c ) => c.name ).join() === 'Старт,Бизнес,Максимум' );
		check( 'Тарифы: «Рекомендуем» — только у «Бизнеса», тёмная карточка', cards.map( ( c ) => c.rec + ':' + c.badge ).join() === 'false:,true:Рекомендуем,false:' &&
			cards[ 1 ].bg === 'rgb(14, 11, 20)' && cards[ 0 ].bg === 'rgb(255, 255, 255)', cards.map( ( c ) => c.bg ).join( ' | ' ) );
		check( 'Тарифы: старая цена зачёркнута (для скринридеров «Старая цена:»), скидка, цена и «/ месяц»',
			cards.every( ( c ) => c.old === 'Старая цена: XX XXX ₽' && c.discount === '−XX%' && c.price === 'XX XXX ₽' && c.period === '/ месяц' ), JSON.stringify( cards[ 0 ] ).slice( 0, 200 ) );
		const included = cards.map( ( c ) => c.items.filter( ( i ) => ! i[ 2 ] ).length );
		check( 'Тарифы: по 8 работ, входит 5 / 7 / 8 (как в макете)', cards.every( ( c ) => JSON.stringify( c.items.map( ( i ) => i[ 0 ] ) ) === JSON.stringify( WORKS ) ) && included.join() === '5,7,8', included.join() );
		check( 'Тарифы: значки «Включено» / «Не включено» подписаны для скринридеров',
			cards.every( ( c ) => c.items.every( ( i ) => i[ 1 ] === ( i[ 2 ] ? 'Не включено' : 'Включено' ) ) ) );
		check( 'Тарифы: кнопки «Выбрать «…»» открывают окно звонка', cards.every( ( c ) => c.btn[ 0 ] === `Выбрать «${ c.name }»` && c.btn[ 1 ] === '#callback-modal' ), JSON.stringify( cards.map( ( c ) => c.btn ) ) );
		await tar.locator( '.b-tariffs__btn' ).nth( 1 ).click();
		await page.waitForTimeout( 400 );
		check( 'Тарифы: по кнопке открывается окно «Заказать обратный звонок»', await page.locator( '#callback-modal' ).isVisible() );
		await page.keyboard.press( 'Escape' );

		// Активный тариф (правка заказчика, вариант D): наведение мышью и Tab.
		const cardState = ( i ) => tar.locator( '.b-tariffs__card' ).nth( i ).evaluate( ( c ) => ( {
			lift: new DOMMatrixReadOnly( getComputedStyle( c ).transform ).m42,
			border: getComputedStyle( c ).borderTopColor,
			shadow: 'none' !== getComputedStyle( c ).boxShadow,
			btn: getComputedStyle( c.querySelector( '.b-tariffs__btn' ) ).backgroundColor,
		} ) );
		const hoverCard = async ( i ) => {
			const box = await tar.locator( '.b-tariffs__card' ).nth( i ).boundingBox();
			await page.mouse.move( box.x + box.width / 2, box.y + 150 );
			await page.waitForTimeout( 400 );
		};
		await page.mouse.move( 0, 0 );
		await tar.locator( '.b-tariffs__card' ).first().scrollIntoViewIfNeeded();
		await page.waitForTimeout( 400 );
		const idle = await cardState( 0 );
		await hoverCard( 0 );
		const start = [ await cardState( 0 ), await cardState( 2 ) ];
		check( 'Тарифы: без наведения карточка на месте, без тени, кнопка контурная', 0 === idle.lift && ! idle.shadow && 'rgba(0, 0, 0, 0)' === idle.btn, JSON.stringify( idle ) );
		check( 'Тарифы: наведение на «Старт» — подъём на 8 px, тень, фиолетовая рамка, кнопка фиолетовая; другие без изменений',
			-8 === start[ 0 ].lift && start[ 0 ].shadow && 'rgb(109, 40, 217)' === start[ 0 ].border && 'rgb(109, 40, 217)' === start[ 0 ].btn && 0 === start[ 1 ].lift && ! start[ 1 ].shadow, JSON.stringify( start ) );
		await hoverCard( 1 );
		const rec = await cardState( 1 );
		check( 'Тарифы: наведение на «Бизнес» — подъём, фиолетовая тень и рамка', -8 === rec.lift && rec.shadow && 'rgb(124, 58, 237)' === rec.border && 'rgb(124, 58, 237)' === rec.btn, JSON.stringify( rec ) );
		await page.mouse.move( 0, 0 );
		await page.waitForTimeout( 400 );
		await tar.locator( '.b-tariffs__btn' ).nth( 2 ).focus();
		await page.keyboard.press( 'Shift+Tab' );
		await page.keyboard.press( 'Tab' );
		await page.waitForTimeout( 400 );
		const kb = await cardState( 2 );
		check( 'Тарифы: Tab до кнопки — карточка тоже становится активной', -8 === kb.lift && kb.shadow && 'rgb(109, 40, 217)' === kb.btn, JSON.stringify( kb ) );
		await page.evaluate( () => document.activeElement.blur() );
		await page.emulateMedia( { reducedMotion: 'reduce' } );
		await hoverCard( 0 );
		const calm = await cardState( 0 );
		check( 'Тарифы: «Уменьшить движение» — карточка не поднимается, рамка и кнопка выделяются', 0 === calm.lift && 'rgb(109, 40, 217)' === calm.border && 'rgb(109, 40, 217)' === calm.btn, JSON.stringify( calm ) );
		await page.emulateMedia( { reducedMotion: 'no-preference' } );
		// Планшет с сенсорным экраном: наведения нет (hover: none), карточка как в макете.
		const tablet = await browser.newContext( { viewport: { width: 1024, height: 900 }, isMobile: true, hasTouch: true } );
		const tp = await tablet.newPage();
		await tp.goto( PAGE, { waitUntil: 'networkidle' } );
		const tCard = tp.locator( '.b-tariffs__card' ).first();
		await tCard.scrollIntoViewIfNeeded();
		const tBox = await tCard.boundingBox();
		await tp.mouse.move( tBox.x + tBox.width / 2, tBox.y + 150 );
		await tp.waitForTimeout( 400 );
		const touch = await tCard.evaluate( ( c ) => ( {
			hover: matchMedia( '(hover: hover)' ).matches,
			lift: new DOMMatrixReadOnly( getComputedStyle( c ).transform ).m42,
			shadow: 'none' !== getComputedStyle( c ).boxShadow,
			btn: getComputedStyle( c.querySelector( '.b-tariffs__btn' ) ).backgroundColor,
		} ) );
		await tablet.close();
		check( 'Тарифы: на сенсорном экране наведения нет — карточка как в макете', ! touch.hover && 0 === touch.lift && ! touch.shadow && 'rgba(0, 0, 0, 0)' === touch.btn, JSON.stringify( touch ) );
		await page.mouse.move( 0, 0 );

		let graph = await graphOf( page, PAGE );
		let service = graph.find( ( n ) => 'Service' === n[ '@type' ] );
		check( 'Микроразметка: у демо-тарифов цена «XX XXX ₽» без числа — offers нет', service && ! service.offers, JSON.stringify( service && service.offers ) );

		const prices = createPage( 'цены', block( 'tariffs', `array( 'title' => 'Тарифы', 'tariffs' => array(
			array( 'name' => 'Старт', 'who' => 'Для небольших сайтов', 'price' => '15 000 ₽', 'price_value' => 15000, 'items' => array( array( 'text' => 'Аудит', 'included' => 1 ) ) ),
			array( 'name' => 'Бизнес', 'recommended' => 1, 'price' => '30 000 ₽', 'price_value' => 30000 ),
			array( 'name' => 'Максимум', 'price' => '60 000 ₽', 'price_value' => 60000, 'old_price' => '', 'discount' => '' ),
			array( 'name' => 'По запросу', 'price' => 'по договорённости' ) ) )` ) );
		created.push( prices );
		graph = await graphOf( page, `${ URL }/?page_id=${ prices }` );
		service = graph.find( ( n ) => 'Service' === n[ '@type' ] );
		const agg = service && service.offers;
		check( 'Микроразметка: несколько тарифов с «Ценой числом» — AggregateOffer 15000–60000 ₽, 3 предложения',
			agg && 'AggregateOffer' === agg[ '@type' ] && 15000 === agg.lowPrice && 60000 === agg.highPrice && 3 === agg.offerCount && 'RUB' === agg.priceCurrency, JSON.stringify( agg ).slice( 0, 300 ) );
		check( 'Микроразметка: у каждого Offer — название, цена, валюта, адрес страницы; описание — «кому подходит»',
			agg && agg.offers.map( ( o ) => `${ o[ '@type' ] }:${ o.name }:${ o.price }:${ o.priceCurrency }` ).join() === 'Offer:Старт:15000:RUB,Offer:Бизнес:30000:RUB,Offer:Максимум:60000:RUB' &&
			agg.offers[ 0 ].description === 'Для небольших сайтов' && agg.offers.every( ( o ) => o.url === service.url ) && ! ( 'description' in agg.offers[ 1 ] ) );
		const rows = await page.locator( '.b-tariffs__card' ).evaluateAll( ( list ) => list.map( ( c ) => [ !! c.querySelector( '.b-tariffs__old' ), c.querySelectorAll( '.b-tariffs__item' ).length, !! c.querySelector( '.b-tariffs__btn' ) ] ) );
		check( 'Тарифы: без старой цены и скидки — строки нет, без состава — списка нет, без кнопки — кнопки нет', JSON.stringify( rows ) === JSON.stringify( [ [ false, 1, false ], [ false, 0, false ], [ false, 0, false ], [ false, 0, false ] ] ), JSON.stringify( rows ) );
		const one = createPage( 'одна цена', block( 'tariffs', "array( 'title' => 'Тариф', 'tariffs' => array( array( 'name' => 'Поддержка', 'price' => '25 000 ₽', 'price_value' => 25000 ) ) )" ) );
		created.push( one );
		graph = await graphOf( page, `${ URL }/?page_id=${ one }` );
		service = graph.find( ( n ) => 'Service' === n[ '@type' ] );
		check( 'Микроразметка: один тариф — Offer с ценой 25000 RUB', service && service.offers && 'Offer' === service.offers[ '@type' ] && 25000 === service.offers.price && 'RUB' === service.offers.priceCurrency, JSON.stringify( service && service.offers ) );

		// ===== «Блок с текстом» =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const text = await page.locator( '.b-text' ).evaluate( ( el ) => {
			const body = el.querySelector( '.b-text__body' );
			return {
				prose: body.classList.contains( 'prose' ),
				headings: [ ...body.querySelectorAll( 'h2, h3, h4' ) ].map( ( h ) => h.tagName + ' ' + h.textContent.trim() ),
				ul: body.querySelectorAll( 'ul > li' ).length,
				ol: body.querySelectorAll( 'ol > li' ).length,
				author: !! body.querySelector( 'blockquote.is-style-author cite' ) && body.querySelector( 'blockquote.is-style-author cite' ).innerText.replace( /\s+/g, ' ' ).trim(),
				important: body.querySelector( 'aside.is-style-important' ) ? body.querySelector( 'aside.is-style-important' ).textContent.replace( /\s+/g, ' ' ).trim() : '',
				quotes: body.querySelectorAll( 'blockquote' ).length,
				links: [ ...body.querySelectorAll( 'a' ) ].map( ( a ) => a.getAttribute( 'href' ) ),
				width: body.getBoundingClientRect().width,
				bg: getComputedStyle( el ).backgroundColor,
			};
		} );
		check( 'Текст: оформление .prose, белый фон, колонка 960 px (правка заказчика)', text.prose && text.bg === 'rgb(255, 255, 255)' && 960 === text.width, JSON.stringify( [ text.bg, text.width ] ) );
		check( 'Текст: H2 и два H3 по макету', JSON.stringify( text.headings ) === JSON.stringify( [ 'H2 Зачем нужен технический аудит сайта', 'H3 Что проверяем в первую очередь', 'H3 Как проходит аудит' ] ), text.headings.join( ' | ' ) );
		check( 'Текст: маркированный и нумерованный списки по 4 пункта', 4 === text.ul && 4 === text.ol );
		check( 'Текст: цитата «С автором» — имя и должность', '[Имя Фамилия] SEO-специалист' === text.author, text.author );
		check( 'Текст: «Важно» — боковая плашка <aside> с подписью, не цитата', text.important.startsWith( 'Важно Одна строка в robots.txt' ) && 1 === text.quotes, text.important );
		check( 'Текст: ссылки — на «С какими проблемами…» этой страницы и разделы страницы аудита', text.links.join() === [ '#problems', ...[ '#includes', '#includes', '#includes' ].map( ( h ) => `${ URL }/seo/audit/${ h }` ) ].join(), text.links.join( ' ' ) );
		// Заголовок на телефоне — строки заполняют колонку (правка заказчика; было «узко» из-за balance).
		await page.setViewportSize( { width: 390, height: 844 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const h2 = await page.locator( '.b-text h2' ).evaluate( ( h ) => {
			const range = document.createRange();
			range.selectNodeContents( h );
			const left = h.getBoundingClientRect().left;
			const widest = Math.max( ...[ ...range.getClientRects() ].map( ( r ) => r.right - left ) );
			return { wrap: getComputedStyle( h ).textWrap, share: Math.round( widest / h.getBoundingClientRect().width * 100 ) };
		} );
		check( 'Текст: на телефоне строки заголовка заполняют ширину колонки (≥ 85 %)', 'wrap' === h2.wrap && h2.share >= 85, JSON.stringify( h2 ) );
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );

		// ===== «Картинка» =====
		const img = await page.locator( '.b-image' ).evaluate( ( el ) => {
			const i = el.querySelector( 'img' );
			const source = el.querySelector( 'picture source' );
			return {
				alt: i.getAttribute( 'alt' ),
				srcset: ( i.getAttribute( 'srcset' ) || '' ).split( ',' ).length,
				sizes: i.getAttribute( 'sizes' ),
				lazy: i.getAttribute( 'loading' ),
				source: source && [ source.getAttribute( 'media' ), /-mobile/.test( source.getAttribute( 'srcset' ) ), source.getAttribute( 'width' ), source.getAttribute( 'height' ) ].join( ' ' ),
				num: el.querySelector( '.b-image__num' ).textContent.trim(),
				caption: el.querySelector( '.b-image__text' ).textContent.trim(),
				width: el.querySelector( '.b-image__figure' ).getBoundingClientRect().width,
				radius: getComputedStyle( el.querySelector( '.b-image__frame' ) ).borderRadius,
			};
		} );
		check( 'Картинка: alt из поля блока, srcset, sizes, отложенная загрузка', img.alt === 'Фрагмент отчёта по техническому аудиту: найденные ошибки сгруппированы по приоритету' && img.srcset >= 4 && /1200px$/.test( img.sizes ) && 'lazy' === img.lazy, JSON.stringify( img ) );
		check( 'Картинка: для телефона — своя картинка (<source> до 600 px с размерами)', '(max-width: 600px) true 1000 1400' === img.source, img.source );
		check( 'Картинка: «Рис. 1» и описание, по ширине контента 1200 px, скругление 20 px',
			'Рис. 1' === img.num && img.caption.startsWith( 'Фрагмент отчёта по техническому аудиту.' ) && 1184 === img.width && '20px' === img.radius, JSON.stringify( [ img.num, img.width, img.radius ] ) );
		const shown = async ( w ) => {
			await page.setViewportSize( { width: w, height: 900 } );
			await page.goto( PAGE, { waitUntil: 'networkidle' } );
			const el = page.locator( '.b-image__img' );
			await el.scrollIntoViewIfNeeded();
			await page.waitForFunction( () => document.querySelector( '.b-image__img' ).complete && document.querySelector( '.b-image__img' ).naturalWidth > 0 );
			return el.evaluate( ( i ) => [ /-mobile/.test( i.currentSrc ), Math.round( i.getBoundingClientRect().height / i.getBoundingClientRect().width * 100 ) / 100 ] );
		};
		const at390 = await shown( 390 );
		const at1280 = await shown( 1280 );
		check( 'Картинка: на телефоне загружается вертикальная картинка (пропорции 5:7), на компьютере — широкая (16:9)',
			at390[ 0 ] && 1.4 === at390[ 1 ] && ! at1280[ 0 ] && 0.56 === at1280[ 1 ], JSON.stringify( [ at390, at1280 ] ) );

		const jpg = path.join( THEME, 'assets', 'img', 'page-types', 'service.jpg' );
		media = [
			wp( 'media', 'import', jpg, '--title=Тестовая картинка блока', '--alt=Описание из медиатеки', '--porcelain' ),
			wp( 'media', 'import', jpg, '--title=Тестовая картинка без alt', '--porcelain' ),
		];
		const figures = createPage( 'рисунки', blocks(
			block( 'image', `array( 'image' => ${ media[ 0 ] }, 'alt' => 'Своё описание', 'caption' => 'Первая' )` ),
			block( 'image', `array( 'image' => ${ media[ 0 ] }, 'number' => '0', 'caption' => 'Без номера', 'decorative' => 1 )` ),
			block( 'image', `array( 'image' => ${ media[ 0 ] }, 'width' => 'text' )` ),
			block( 'image', `array( 'image' => ${ media[ 0 ] }, 'number' => '7', 'caption' => 'Свой номер' )` ),
			block( 'image', `array( 'image' => ${ media[ 0 ] }, 'alt' => 'Своё описание', 'caption' => 'Первая' )` ),
			block( 'image', "array( 'caption' => 'Без картинки' )" ),
			block( 'image', `array( 'image' => ${ media[ 1 ] }, 'number' => '0' )` ),
		) );
		created.push( figures );
		await page.goto( `${ URL }/?page_id=${ figures }`, { waitUntil: 'networkidle' } );
		const list = await page.locator( '.b-image' ).evaluateAll( ( all ) => all.map( ( el ) => ( {
			num: el.querySelector( '.b-image__num' ) ? el.querySelector( '.b-image__num' ).textContent.trim() : '',
			caption: !! el.querySelector( 'figcaption' ),
			alt: el.querySelector( 'img' ).getAttribute( 'alt' ),
			width: Math.round( el.querySelector( '.b-image__figure' ).getBoundingClientRect().width ),
			picture: !! el.querySelector( 'picture source' ),
		} ) ) );
		check( 'Картинка: «Рис. N» по порядку, «0» — без номера, свой номер, одинаковые блоки — разные номера',
			list.map( ( f ) => f.num ).join() === 'Рис. 1,,Рис. 2,Рис. 7,Рис. 4,', list.map( ( f ) => f.num ).join() );
		check( 'Картинка: без картинки — блока на сайте нет; без номера и описания — без подписи', 6 === list.length && ! list[ 5 ].caption, `${ list.length } блоков` );
		check( 'Картинка: alt — из поля, у декоративной пустой, иначе из медиатеки', list.slice( 0, 3 ).map( ( f ) => f.alt ).join( '|' ) === 'Своё описание||Описание из медиатеки', list.map( ( f ) => f.alt ).join( '|' ) );
		check( 'Картинка: «По ширине текста» — 960 px, как «Блок с текстом»; без картинки для телефона — без <source>', 960 === list[ 2 ].width && 1184 === list[ 0 ].width && ! list[ 0 ].picture, JSON.stringify( list.map( ( f ) => f.width ) ) );
		check( 'Картинка: на сайте предупреждения об alt нет', ( await page.locator( '.trust-block-warning' ).count() ) === 0 );

		// ===== Картинки на весь экран (правка заказчика) =====
		const lbState = () => page.evaluate( () => {
			const d = document.querySelector( '.lightbox' );
			const i = d && d.querySelector( 'img' );
			return d ? {
				open: d.open,
				src: i.getAttribute( 'src' ) ? i.getAttribute( 'src' ).split( '/' ).pop() : '',
				alt: i.alt,
				caption: d.querySelector( 'figcaption' ).hidden ? '' : d.querySelector( 'figcaption' ).textContent.replace( /\s+/g, ' ' ).trim(),
				focus: document.activeElement.className,
				lock: document.documentElement.classList.contains( 'lightbox-open' ),
				fits: i.getBoundingClientRect().width <= innerWidth && i.getBoundingClientRect().height <= innerHeight,
			} : { open: false };
		} );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const zoom = page.locator( '.b-image__open' );
		const zoomAttr = await zoom.evaluate( ( a ) => [ a.getAttribute( 'href' ).split( '/' ).pop(), ( a.dataset.fullMobile || '' ).split( '/' ).pop(), a.getAttribute( 'aria-label' ) ] );
		check( 'На весь экран: картинка — ссылка на оригинал (без JavaScript откроется файл), у ссылки подпись для скринридеров',
			'image-audit-report.jpg' === zoomAttr[ 0 ] && 'image-audit-report-mobile.jpg' === zoomAttr[ 1 ] && zoomAttr[ 2 ].startsWith( 'Открыть на весь экран: Фрагмент отчёта' ), JSON.stringify( zoomAttr ) );
		await zoom.scrollIntoViewIfNeeded();
		await zoom.click();
		await page.waitForTimeout( 500 );
		let lb = await lbState();
		check( 'На весь экран: по клику — окно с оригиналом, «Рис. 1» и описанием, помещается в экран, фокус на «Закрыть», страница не прокручивается',
			lb.open && 'image-audit-report.jpg' === lb.src && lb.caption.startsWith( 'Рис. 1 Фрагмент отчёта' ) && lb.fits && 'lightbox__close' === lb.focus && lb.lock && lb.alt.startsWith( 'Фрагмент отчёта' ), JSON.stringify( lb ) );
		await page.keyboard.press( 'Escape' );
		await page.waitForTimeout( 300 );
		lb = await lbState();
		check( 'На весь экран: Esc закрывает, фокус возвращается на картинку', ! lb.open && 'b-image__open' === lb.focus && ! lb.lock, JSON.stringify( lb ) );
		await zoom.click();
		await page.waitForTimeout( 400 );
		await page.mouse.click( 20, 880 );
		await page.waitForTimeout( 300 );
		const byBackdrop = ! ( await lbState() ).open;
		await zoom.click();
		await page.waitForTimeout( 400 );
		await page.locator( '.lightbox__close' ).click();
		await page.waitForTimeout( 300 );
		check( 'На весь экран: закрывается нажатием мимо картинки и кнопкой «Закрыть»', byBackdrop && ! ( await lbState() ).open );
		const phone = await browser.newContext( { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true } );
		const pp = await phone.newPage();
		await pp.goto( PAGE, { waitUntil: 'networkidle' } );
		await pp.locator( '.b-image__open' ).scrollIntoViewIfNeeded();
		const hint = await pp.locator( '.b-image__zoom' ).evaluate( ( el ) => getComputedStyle( el ).opacity );
		await pp.locator( '.b-image__open' ).tap();
		await pp.waitForTimeout( 600 );
		const ph = await pp.evaluate( () => {
			const i = document.querySelector( '.lightbox img' );
			const r = i.getBoundingClientRect();
			return { open: document.querySelector( '.lightbox' ).open, src: i.getAttribute( 'src' ).split( '/' ).pop(), w: Math.round( r.width ), h: Math.round( r.height ), vw: innerWidth, vh: innerHeight };
		} );
		await phone.close();
		check( 'На весь экран на телефоне: значок «развернуть» виден, по нажатию — картинка для телефона во всю ширину экрана',
			'1' === hint && ph.open && 'image-audit-report-mobile.jpg' === ph.src && ph.w >= ph.vw - 24 && ph.h <= ph.vh, JSON.stringify( { hint, ...ph } ) );
		const prosePage = createPage( 'картинка в тексте', `Trust\\Blocks::serialize( 'text', array(), '<!-- wp:paragraph --><p>Текст.</p><!-- /wp:paragraph --><!-- wp:image {"id":${ media[ 0 ] },"sizeSlug":"medium"} --><figure class="wp-block-image size-medium"><img src="' . wp_get_attachment_image_url( ${ media[ 0 ] }, 'medium' ) . '" alt="Схема в тексте" class="wp-image-${ media[ 0 ] }"/><figcaption class="wp-element-caption">Подпись в тексте</figcaption></figure><!-- /wp:image -->' )` );
		created.push( prosePage );
		await page.goto( `${ URL }/?page_id=${ prosePage }`, { waitUntil: 'networkidle' } );
		const proseImg = page.locator( '.b-text .wp-block-image img' );
		const pa = await proseImg.evaluate( ( i ) => [ i.getAttribute( 'role' ), i.tabIndex, i.getAttribute( 'aria-label' ), getComputedStyle( i ).cursor ] );
		await proseImg.focus();
		await page.keyboard.press( 'Enter' );
		await page.waitForTimeout( 500 );
		lb = await lbState();
		check( 'На весь экран: картинка в «Блоке с текстом» — с клавиатуры (Enter) открывается самый большой вариант с подписью',
			'button' === pa[ 0 ] && 0 === pa[ 1 ] && 'Открыть на весь экран: Схема в тексте' === pa[ 2 ] && 'zoom-in' === pa[ 3 ] && lb.open && ! /-\d+x\d+\./.test( lb.src ) && 'Подпись в тексте' === lb.caption, JSON.stringify( { pa, lb } ) );
		await page.keyboard.press( 'Escape' );
		await page.waitForTimeout( 300 );

		// ===== «Главная мысль» (в макете — «Почему мы открыто пишем про всё») =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const open = await page.locator( '.b-idea' ).evaluate( ( el ) => ( {
			h2: el.querySelector( 'h2' ).textContent.trim(),
			label: el.getAttribute( 'aria-labelledby' ) === el.querySelector( 'h2' ).id,
			eyebrow: el.querySelector( '.eyebrow' ).textContent.trim(),
			quote: [ ...el.querySelectorAll( 'blockquote p' ) ].map( ( p ) => p.textContent.trim().slice( 0, 30 ) ),
			author: el.querySelector( 'figcaption' ) ? el.querySelector( 'figcaption' ).innerText.replace( /\s+/g, ' ' ).trim() : '',
			bg: getComputedStyle( el ).backgroundColor,
			dots: getComputedStyle( el ).backgroundImage.startsWith( 'radial-gradient' ),
			pad: getComputedStyle( el ).paddingTop,
		} ) );
		check( 'Главная мысль: H2, надзаголовок «Главная мысль», фиолетовый фон с точками, отступ 64 px — как у всех',
			'Почему мы открыто пишем про всё' === open.h2 && open.label && 'Главная мысль' === open.eyebrow && 'rgb(91, 33, 182)' === open.bg && open.dots && '64px' === open.pad, JSON.stringify( open ) );
		check( 'Главная мысль: цитата — крупная фраза и продолжение', 2 === open.quote.length && open.quote[ 0 ].startsWith( 'Мы открыто пишем' ) && open.quote[ 1 ].startsWith( 'Прочитать «проверка' ), open.quote.join( ' | ' ) );
		check( 'Главная мысль: автор из «Команды», должность из поля блока', '[Имя Фамилия] руководитель агентства' === open.author, open.author );
		const authors = createPage( 'авторы', blocks(
			block( 'key-idea', "array( 'title' => 'А', 'quote' => 'Цитата', 'author_source' => 'team', 'author' => (int) get_page_by_path( 'ceo', OBJECT, 'team' )->ID )" ),
			block( 'key-idea', "array( 'title' => 'Б', 'quote' => 'Цитата', 'author_source' => 'manual', 'author_name' => 'Иван Петров', 'author_role' => 'SEO-специалист' )" ),
			block( 'key-idea', "array( 'title' => 'В', 'quote' => 'Цитата', 'author_source' => 'none', 'author_role' => 'Не выводится' )" ),
		) );
		created.push( authors );
		await page.goto( `${ URL }/?page_id=${ authors }`, { waitUntil: 'networkidle' } );
		const signs = await page.locator( '.b-idea' ).evaluateAll( ( all ) => all.map( ( el ) => ( el.querySelector( 'figcaption' ) ? el.querySelector( 'figcaption' ).innerText.replace( /\s+/g, ' ' ).trim() : '' ) ) );
		check( 'Главная мысль: без своей должности — из карточки сотрудника; вручную — имя и должность; «Без автора» — без подписи',
			JSON.stringify( signs ) === JSON.stringify( [ '[Имя Фамилия] Основатель и генеральный директор', 'Иван Петров SEO-специалист', '' ] ), JSON.stringify( signs ) );

		// ===== Вводный текст первого экрана — до 1000 символов (правка заказчика) =====
		const leadMax = wp( 'eval', "foreach ( Trust\\Blocks::fields( 'hero-service' ) as $f ) { if ( 'lead' === $f['name'] ) { echo $f['maxlength']; } }" );
		const longLead = 'Вводный текст. '.repeat( 67 ).slice( 0, 999 ) + '.';
		const heroPage = createPage( 'длинный вводный текст', `Trust\\Blocks::serialize( 'hero-service', array( 'lead' => '${ longLead }' ) )` );
		created.push( heroPage );
		await page.goto( `${ URL }/?page_id=${ heroPage }`, { waitUntil: 'networkidle' } );
		const leadLen = await page.locator( '.b-hero-service__lead' ).evaluate( ( p ) => p.textContent.length );
		check( 'Первый экран услуги: «Вводный текст» — до 1000 символов, выводится целиком', '1000' === leadMax && 1000 === leadLen, `${ leadMax } / ${ leadLen }` );

		// Иконка справа над кнопкой и ширина кнопки (правка заказчика).
		const arts = JSON.parse( wp( 'eval', 'echo wp_json_encode( array_map( static fn( $k ) => strlen( Trust\\Hero_Art::svg( $k ) ), array_keys( Trust\\Hero_Art::LIST ) ) );' ) );
		check( 'Иконки первого экрана: 13 рисунков (аналитика … поисковый робот), все файлы на месте', 13 === arts.length && arts.every( ( n ) => n > 500 ), JSON.stringify( arts ) );
		const button = "array( array( 'link' => array( 'title' => 'Оставить заявку', 'url' => '#callback-modal', 'target' => '' ) ) )";
		const heroArt = createPage( 'иконка', `Trust\\Blocks::serialize( 'hero-service', array( 'lead' => '${ longLead }', 'art' => 'analytics', 'buttons' => ${ button } ) )` );
		const heroShort = createPage( 'иконка, короткий текст', `Trust\\Blocks::serialize( 'hero-service', array( 'lead' => '${ longLead.slice( 0, 379 ) }', 'art' => 'analytics', 'buttons' => ${ button } ) )` );
		created.push( heroArt, heroShort );
		const heroState = () => page.evaluate( () => {
			const box = ( el ) => el && el.getBoundingClientRect();
			const art = document.querySelector( '.b-hero-service__art' );
			const btn = box( document.querySelector( '.b-hero-service__buttons .btn' ) );
			const lead = box( document.querySelector( '.b-hero-service__lead' ) );
			const side = box( document.querySelector( '.b-hero-service__side' ) );
			const svg = box( art && art.querySelector( 'svg' ) );
			return {
				art: !! art && 'none' !== getComputedStyle( art ).display,
				svgH: svg ? Math.round( svg.height ) : 0,
				above: svg && btn ? svg.bottom <= btn.top : false,
				btnW: Math.round( btn.width ),
				sideH: side ? Math.round( side.height ) : 0,
				leadH: Math.round( lead.height ),
				scroll: document.documentElement.scrollWidth > innerWidth,
			};
		} );
		await page.goto( `${ URL }/?page_id=${ heroArt }`, { waitUntil: 'networkidle' } );
		let hs = await heroState();
		check( 'Иконка: при тексте от 380 символов на компьютере — справа над кнопкой, высота экрана — по тексту, кнопка во всю колонку (340 px)',
			hs.art && hs.above && hs.svgH > 100 && hs.svgH <= 260 && Math.abs( hs.sideH - hs.leadH ) <= 1 && 340 === hs.btnW, JSON.stringify( hs ) );
		await page.goto( `${ URL }/?page_id=${ heroShort }`, { waitUntil: 'networkidle' } );
		hs = await heroState();
		check( 'Иконка: при тексте короче 380 символов не выводится; одна кнопка — шире (от 280 px)', ! hs.art && hs.btnW >= 280, JSON.stringify( hs ) );
		// Две кнопки (как на /seo/audit/) — одного размера; иконка над ними не меньше 180 px.
		const twoButtons = "array( array( 'link' => array( 'title' => 'Заказать аудит', 'url' => '#callback-modal', 'target' => '' ) ), array( 'link' => array( 'title' => 'Рассчитать стоимость', 'url' => '#calculator', 'target' => '' ) ) )";
		const heroTwo = createPage( 'иконка, две кнопки', `Trust\\Blocks::serialize( 'hero-service', array( 'lead' => '${ longLead.slice( 0, 480 ) }', 'art' => 'code', 'buttons' => ${ twoButtons } ) )` );
		created.push( heroTwo );
		const twoState = () => page.evaluate( () => ( {
			btns: [ ...document.querySelectorAll( '.b-hero-service__buttons .btn' ) ].map( ( b ) => [ Math.round( b.getBoundingClientRect().width ), Math.round( b.getBoundingClientRect().height ), Math.round( b.getBoundingClientRect().top ) ] ),
			art: document.querySelector( '.b-hero-service__art' ) && 'none' !== getComputedStyle( document.querySelector( '.b-hero-service__art' ) ).display ? Math.round( document.querySelector( '.b-hero-service__art svg' ).getBoundingClientRect().height ) : 0,
			scroll: document.documentElement.scrollWidth > innerWidth,
		} ) );
		for ( const w of [ 1280, 1024, 768 ] ) {
			await page.setViewportSize( { width: w, height: 900 } );
			await page.goto( `${ URL }/?page_id=${ heroTwo }`, { waitUntil: 'networkidle' } );
			const two = await twoState();
			const same = two.btns[ 0 ][ 0 ] === two.btns[ 1 ][ 0 ] && two.btns[ 0 ][ 1 ] === two.btns[ 1 ][ 1 ] && two.btns[ 0 ][ 2 ] === two.btns[ 1 ][ 2 ];
			check( `Две кнопки ${ w }px: одного размера и в один ряд${ 1280 === w ? ', иконка над ними от 180 px' : ', иконки нет' }`, same && ( 1280 === w ? two.art >= 180 : 0 === two.art ) && ! two.scroll, JSON.stringify( two ) );
		}
		await page.setViewportSize( { width: 390, height: 844 } );
		await page.goto( `${ URL }/?page_id=${ heroTwo }`, { waitUntil: 'networkidle' } );
		const twoPhone = await twoState();
		check( 'Две кнопки 390px: в один ряд во всю ширину, одной высоты', twoPhone.btns[ 0 ][ 0 ] + twoPhone.btns[ 1 ][ 0 ] + 8 >= 349 && twoPhone.btns[ 0 ][ 1 ] === twoPhone.btns[ 1 ][ 1 ] && twoPhone.btns[ 0 ][ 2 ] === twoPhone.btns[ 1 ][ 2 ], JSON.stringify( twoPhone ) );
		for ( const [ w, min ] of [ [ 768, 280 ], [ 390, 350 ] ] ) {
			await page.setViewportSize( { width: w, height: 900 } );
			await page.goto( `${ URL }/?page_id=${ heroArt }`, { waitUntil: 'networkidle' } );
			hs = await heroState();
			check( `Иконка: на ${ w } px скрыта, кнопка ${ 390 === w ? 'во всю ширину' : 'от 280 px' }, без прокрутки вбок`, ! hs.art && hs.btnW >= min && ! hs.scroll, JSON.stringify( hs ) );
		}
		await page.setViewportSize( { width: 1280, height: 900 } );

		// ===== Отступы: у всех блоков одинаковые (правка заказчика) =====
		for ( const w of [ 1280, 768, 390 ] ) {
			const expected = { 1280: 64, 768: 56, 390: 48 }[ w ];
			await page.setViewportSize( { width: w, height: 900 } );
			const wrong = [];
			for ( const url of [ PAGE, `${ URL }/seo/audit/` ] ) {
				await page.goto( url, { waitUntil: 'networkidle' } );
				wrong.push( ...await page.evaluate( ( y ) => {
					const list = [ ...document.querySelectorAll( '.page-entry__content > section' ) ].filter( ( s ) => ! /^b-hero-/.test( s.className ) );
					const bg = ( s ) => ( s.className.match( /trust-bg-\w+/ ) || [ '' ] )[ 0 ];
					return list.filter( ( s ) => {
						const cs = getComputedStyle( s );
						const prev = s.previousElementSibling;
						const top = prev && bg( prev ) && bg( prev ) === bg( s ) ? 0 : y; // два блока одного фона подряд — один отступ
						return parseFloat( cs.paddingTop ) !== top || parseFloat( cs.paddingBottom ) !== y;
					} ).map( ( s ) => `${ location.pathname } ${ s.className.split( ' ' )[ 0 ] } ${ getComputedStyle( s ).paddingTop }/${ getComputedStyle( s ).paddingBottom }` );
				}, expected ) );
			}
			check( `Отступы ${ w }px: у всех блоков /seo/ и /seo/audit/ — ${ expected } px сверху и снизу (одного фона подряд — один)`, 0 === wrong.length, wrong.join( ' | ' ) );
		}

		// ===== Совпадение с макетами =====
		for ( const width of [ 1280, 768, 390 ] ) {
			await compare( page, { name: 'tariffs', width, theme: '.b-tariffs', mockup: 'seo-bloki/01-tarify.html', mock: 'section' } );
			await compare( page, { name: 'text', width, theme: '.b-text', mockup: 'seo-bloki/03-tekst.html', mock: 'section' } );
			// В макете — заглушка картинки: в неё подставляется картинка темы (на телефоне — вертикальная).
			await compare( page, {
				name: 'image', width, theme: '.b-image', mockup: 'seo-bloki/08-kartinka.html', mock: 'section',
				before: async () => {
					const i = document.querySelector( '.b-image__img' );
					i.loading = 'eager';
					await i.decode().catch( () => {} );
					return document.querySelector( '.b-image__frame picture' ).outerHTML;
				},
				mockBefore: async ( html ) => {
					const frame = document.querySelector( 'figure > div' );
					frame.setAttribute( 'style', 'box-sizing: border-box; width: 100%; border-radius: 20px; overflow: hidden; border: 1px solid #E0D7F5; background: #F3EEFF' );
					frame.innerHTML = html.replace( / loading="lazy"/g, '' );
					const i = frame.querySelector( 'img' );
					i.style.cssText = 'display: block; width: 100%; height: auto';
					await i.decode().catch( () => {} );
				},
			} );
			// Надзаголовок «Главная мысль» (правка заказчика, в макете «Открытость»).
			await compare( page, {
				name: 'idea', width, theme: '.b-idea', mockup: 'seo-bloki/05-otkryto-pishem.html', mock: 'section',
				mockBefore: () => {
					[ ...document.querySelectorAll( 'span' ) ].find( ( el ) => 'Открытость' === el.textContent.trim() ).textContent = 'Главная мысль';
				},
			} );
		}

		// ===== Ширины, зоны нажатия, доступность =====
		for ( const w of [ 1440, 1024, 768, 600, 390, 320, 280 ] ) {
			await page.setViewportSize( { width: w, height: 800 } );
			await page.goto( PAGE, { waitUntil: 'networkidle' } );
			const res = await page.evaluate( () => ( {
				scroll: document.documentElement.scrollWidth > innerWidth,
				small: [ ...document.querySelectorAll( '.b-tariffs a, .b-tariffs button' ) ].filter( ( el ) => el.offsetParent && el.getBoundingClientRect().height < 44 ).map( ( el ) => el.className || el.textContent.trim() ),
			} ) );
			check( `Ширина ${ w }px: без прокрутки вбок, зоны нажатия ≥ 44 px`, ! res.scroll && 0 === res.small.length, JSON.stringify( res ) );
		}
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
		const axe = await page.evaluate( async () => {
			const r = await window.axe.run( { include: [ '.b-tariffs', '.b-text', '.b-image', '.b-idea' ] }, { resultTypes: [ 'violations' ] } );
			return r.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length })` );
		} );
		check( 'axe: нет нарушений critical/serious', axe.length === 0, axe.join( ', ' ) );
		check( 'Консоль браузера на сайте без ошибок', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1440, height: 1000 } } );
		const editorErrors = [];
		editor.on( 'pageerror', ( e ) => editorErrors.push( e.message ) );
		await login( editor );
		const id = wp( 'eval', 'echo get_page_by_path( "seo" )->ID;' );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ id }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		const reg = await editor.evaluate( () => [ 'trust/tariffs', 'trust/text', 'trust/image', 'trust/key-idea' ].map( ( name ) => {
			const type = wp.blocks.getBlockType( name );
			return [ name, type && type.title, type && type.category, !! ( type && type.example ) ];
		} ) );
		check( 'Редактор: «Выбор тарифа» и «Главная мысль» — «Услуги», «Блок с текстом» и «Картинка» — «Текст и медиа», у всех превью',
			JSON.stringify( reg ) === JSON.stringify( [
				[ 'trust/tariffs', 'Выбор тарифа', 'trust-services', true ],
				[ 'trust/text', 'Блок с текстом', 'trust-content', true ],
				[ 'trust/image', 'Картинка', 'trust-content', true ],
				[ 'trust/key-idea', 'Главная мысль', 'trust-services', true ],
			] ), JSON.stringify( reg ) );
		const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-idea' ).first().waitFor( { timeout: 30000 } );
		await canvas.locator( '.b-text p' ).first().waitFor( { timeout: 30000 } );
		check( 'Редактор: превью блоков на странице (3 тарифа, текст, «Рис. 1», цитата)',
			3 === await canvas.locator( '.b-tariffs__card' ).count() && 3 === await canvas.locator( '.b-text h2, .b-text h3' ).count() &&
			'Рис. 1' === ( await canvas.locator( '.b-image__num' ).textContent() ).trim() && 1 === await canvas.locator( '.b-idea__quote' ).count() );
		const inner = await editor.evaluate( () => {
			const sel = wp.data.select( 'core/block-editor' );
			const textBlock = sel.getBlocks().find( ( b ) => 'trust/text' === b.name );
			return {
				inside: textBlock.innerBlocks.length,
				can: [ 'core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/table', 'core/image', 'core/columns', 'core/embed', 'trust/faq' ].map( ( n ) => n + ':' + sel.canInsertBlockType( n, textBlock.clientId ) ),
				levels: wp.blocks.getBlockType( 'core/heading' ).attributes.levelOptions.default,
				styles: wp.data.select( 'core/blocks' ).getBlockStyles( 'core/quote' ).map( ( s ) => s.name + ':' + s.label ),
			};
		} );
		check( 'Редактор: «Блок с текстом» — вложенные блоки ядра (10 шт.)', 10 === inner.inside, String( inner.inside ) );
		check( 'Редактор: внутри «Блока с текстом» — только абзац, заголовок, список, цитата, таблица, картинка',
			inner.can.join() === 'core/paragraph:true,core/heading:true,core/list:true,core/quote:true,core/table:true,core/image:true,core/columns:false,core/embed:false,trust/faq:false', inner.can.join() );
		check( 'Редактор: заголовки только H2–H4, у цитаты стили «С автором» и «Важно»',
			JSON.stringify( inner.levels ) === '[2,3,4]' && inner.styles.includes( 'author:С автором' ) && inner.styles.includes( 'important:Важно' ), JSON.stringify( inner ) );
		// «Иконка справа» первого экрана — список с превью рисунков.
		await editor.evaluate( () => {
			const hero = wp.data.select( 'core/block-editor' ).getBlocks().find( ( b ) => 'trust/hero-service' === b.name );
			wp.data.dispatch( 'core/block-editor' ).selectBlock( hero.clientId );
		} );
		await editor.waitForTimeout( 1500 );
		await editor.locator( '.interface-complementary-area .trust-art-select .select2-selection' ).first().click();
		const artOptions = await editor.evaluate( () => [ ...document.querySelectorAll( '.select2-results__option' ) ].map( ( o ) => ( o.querySelector( 'svg' ) ? 'svg ' : '' ) + ( o.querySelector( 'svg' ) ? o.lastChild.textContent : o.textContent ).trim() ) );
		await editor.keyboard.press( 'Escape' );
		check( 'Редактор: «Иконка справа» — «Без иконки» и 13 рисунков с превью', 14 === artOptions.length && 'Без иконки' === artOptions[ 0 ] && artOptions.slice( 1 ).every( ( t ) => t.startsWith( 'svg ' ) ) && artOptions.includes( 'svg Поисковый робот' ), JSON.stringify( artOptions.slice( 0, 3 ) ) );
		await editor.screenshot( { path: path.join( OUT, 'content-blocks-editor.png' ) } );

		// Картинка без описания — предупреждение в превью; номера — как на сайте.
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ figures }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		const figCanvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await figCanvas.locator( '.b-image' ).nth( 6 ).waitFor( { timeout: 30000 } );
		await editor.waitForTimeout( 1500 );
		const warnings = ( await figCanvas.locator( '.trust-block-warning' ).allTextContents() ).map( ( t ) => t.trim() );
		check( 'Редактор: в превью картинка не ссылка (просмотр на весь экран — только на сайте)', 0 === await figCanvas.locator( '.b-image__open' ).count() );
		check( 'Редактор: предупреждение только у картинки без alt', 1 === warnings.length && warnings[ 0 ].startsWith( 'У картинки нет описания (alt)' ), warnings.join( ' | ' ) );
		const nums = await figCanvas.locator( '.b-image' ).evaluateAll( ( all ) => all.map( ( el ) => ( el.querySelector( '.b-image__num' ) ? el.querySelector( '.b-image__num' ).textContent.trim() : '' ) ) );
		check( 'Редактор: «Рис. N» как на сайте (у одинаковых блоков — номер первого), без картинки — заглушка',
			nums.slice( 0, 4 ).join() === 'Рис. 1,,Рис. 2,Рис. 7' && 1 === await figCanvas.locator( '.b-image__placeholder' ).count(), nums.join() );
		check( 'Редактор без ошибок JavaScript', editorErrors.length === 0, editorErrors.slice( 0, 3 ).join( ' | ' ) );
	} finally {
		created.forEach( ( p ) => wp( 'post', 'delete', p, '--force' ) );
		media.forEach( ( m ) => wp( 'post', 'delete', m, '--force' ) );
	}

	await browser.close();
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
