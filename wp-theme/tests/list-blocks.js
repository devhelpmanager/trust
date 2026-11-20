/**
 * Тест блоков шага 4.7: «Наши проекты в топе», «Карусель кейсов», «Отзывы клиентов».
 *
 * Запуск: node wp-theme/tests/list-blocks.js
 *
 * «Проекты в топе»: карточки из кейсов (скриншот с alt, чем занимается клиент, название, таблица
 * «Запрос — Позиция в Я / G» с подписями для скринридеров, позиции 1–3 выделены, регион и дата),
 * кейсы вручную (по порядку, без позиций — пропуск) и автоматически (только с позициями,
 * количество), пустой блок на сайт не выводится.
 * «Карусель кейсов»: роли и подписи (карусель, слайды «1 из 4»), счётчик, стрелки по кругу,
 * клавиши ← →, листание пальцем, соседние слайды недоступны (inert), ссылка на кейс и строка
 * «чем занимается клиент» из кейса, «Уменьшить движение», вид без JavaScript, один слайд — без стрелок.
 * «Отзывы клиентов»: «3 карточки» (фото или силуэт, имя, должность и компания, «Оригинал» — ссылка
 * в новой вкладке или скан письма на весь экран) и «2 карточки» (строка с отраслью кейса), фон
 * белый и лавандовый, «Все отзывы» на страницу «Отзывы», последние / по услуге / вручную, только
 * текстовые отзывы.
 * Общее: сверка с макетами seo-bloki/12, 14, 15 и «Что говорят клиенты» из portfolio.html
 * (≤ 2 %), колонки на 1280 / 768 / 390 px, ширины 1440–280 без прокрутки вбок, axe-core, консоль
 * и журнал ошибок, редактор — категория «Списки», превью.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp } = require( './lib' );

const SEO = `${ URL }/seo/`;
const PORTFOLIO = `${ URL }/portfolio/`;
const MARK = 'ТестСписков';

/**
 * Опубликованная тестовая страница «Услуга» с блоками.
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

/** PHP: ID демо-записи по ключу. */
const demoId = ( type, key ) => wp( 'eval', `echo get_posts( array( 'post_type' => '${ type }', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_trust_demo_id', 'meta_value' => '${ type }:${ key }' ) )[0] ?? 0;` );

/**
 * Снимки блока темы и макета и их сравнение.
 *
 * @param {import('playwright').Page} page Страница.
 * @param {Object}                    o    name, width, url, theme, mockup, mock, hide (в теме), before (в теме), mockBefore (в макете).
 */
async function compare( page, o ) {
	const name = `${ o.name }-${ o.width }`;
	await page.setViewportSize( { width: o.width, height: 900 } );
	await page.goto( o.url, { waitUntil: 'networkidle' } );
	await settle( page );
	if ( o.hide ) {
		await page.evaluate( ( list ) => list.forEach( ( s ) => document.querySelectorAll( s ).forEach( ( el ) => ( el.style.display = 'none' ) ) ), o.hide );
	}
	if ( o.before ) {
		await page.evaluate( o.before );
	}
	await page.mouse.move( 0, 0 );
	await isolate( page, o.theme );
	await page.waitForTimeout( 400 );
	await page.locator( o.theme ).screenshot( { path: path.join( OUT, `${ name }-theme.png` ) } );

	await page.goto( mockupUrl( o.mockup ), { waitUntil: 'networkidle' } );
	await settle( page );
	if ( o.mockBefore ) {
		await page.evaluate( o.mockBefore );
	}
	await isolate( page, o.mock );
	await page.waitForTimeout( 400 );
	await page.locator( o.mock ).first().screenshot( { path: path.join( OUT, `${ name }-mockup.png` ) } );
	const diff = comparePng( path.join( OUT, `${ name }-mockup.png` ), path.join( OUT, `${ name }-theme.png` ), path.join( OUT, `${ name }-diff.png` ) );
	check( `Макет ${ o.mockup } ${ o.width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
}

/** Число колонок сетки: карточки с одинаковым верхом в первом ряду. */
const columns = ( page, sel ) => page.$$eval( sel, ( list ) => list.filter( ( el ) => Math.abs( el.getBoundingClientRect().top - list[ 0 ].getBoundingClientRect().top ) < 2 ).length );

( async () => {
	clearLog();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const created = [];

	try {
		// ===== Страница /seo/ =====
		await page.goto( SEO, { waitUntil: 'networkidle' } );
		const order = await page.evaluate( () => [ ...document.querySelectorAll( '.page-entry__content > section' ) ].map( ( s ) => s.className.split( ' ' )[ 0 ] ) );
		check( 'Страница /seo/: карусель после тарифов, «Проекты в топе» и отзывы — после «Проблем»',
			JSON.stringify( order ) === JSON.stringify( [ 'b-hero-service', 'b-tariffs', 'b-carousel', 'b-text', 'b-image', 'b-problems', 'b-projects', 'b-reviews', 'b-idea', 'b-brief' ] ), order.join( ', ' ) );

		// ===== «Наши проекты в топе» =====
		const pr = await page.locator( '.b-projects' ).evaluate( ( el ) => ( {
			id: el.id,
			labelled: el.getAttribute( 'aria-labelledby' ) === el.querySelector( 'h2' ).id,
			eyebrow: el.querySelector( '.eyebrow' ).textContent.trim(),
			h2: el.querySelector( 'h2' ).textContent.trim(),
			bg: getComputedStyle( el ).backgroundColor,
			cards: [ ...el.querySelectorAll( '.b-projects__card' ) ].map( ( c ) => ( {
				niche: c.querySelector( '.b-projects__niche' ).textContent.trim(),
				name: c.querySelector( 'h3' ).textContent.trim(),
				alt: c.querySelector( 'img' ).getAttribute( 'alt' ),
				srcset: !! c.querySelector( 'img' ).getAttribute( 'srcset' ),
				lazy: 'lazy' === c.querySelector( 'img' ).getAttribute( 'loading' ),
				ratio: Math.round( c.querySelector( 'img' ).getBoundingClientRect().width / c.querySelector( 'img' ).getBoundingClientRect().height * 100 ) / 100,
				heads: [ ...c.querySelectorAll( 'th[scope=col]' ) ].map( ( th ) => th.textContent.replace( /\s+/g, ' ' ).trim() ),
				engines: [ ...c.querySelectorAll( '[role=img]' ) ].map( ( e ) => e.getAttribute( 'aria-label' ) ),
				caption: c.querySelector( 'caption' ).textContent.trim(),
				rows: [ ...c.querySelectorAll( 'tbody tr' ) ].map( ( tr ) => [ ...tr.cells ].map( ( td ) => td.textContent.trim() ).join( '|' ) ),
				top: [ ...c.querySelectorAll( '.b-projects__badge' ) ].map( ( b ) => b.classList.contains( 'is-top' ) ? 1 : 0 ).join( '' ),
				colors: [ ...c.querySelectorAll( '.b-projects__badge' ) ].slice( 0, 2 ).map( ( b ) => getComputedStyle( b ).backgroundColor ), // 1-я строка: Я и G
				meta: c.querySelector( '.b-projects__meta' ).textContent.trim(),
			} ) ),
		} ) );
		check( 'Проекты в топе: якорь #projects, надзаголовок «Кейсы», H2, фиолетовый фон',
			'projects' === pr.id && pr.labelled && 'Кейсы' === pr.eyebrow && 'Наши проекты в топе' === pr.h2 && 'rgb(91, 33, 182)' === pr.bg, JSON.stringify( pr ).slice( 0, 200 ) );
		check( 'Проекты в топе: 3 кейса в порядке выбора — чем занимается клиент и название из кейса',
			3 === pr.cards.length && 'Интернет-магазин мебели,Стоматологическая клиника,Ремонт квартир' === pr.cards.map( ( c ) => c.niche ).join() &&
			'[Название проекта],[Название проекта],[Ремонт&Дизайн]' === pr.cards.map( ( c ) => c.name ).join(), JSON.stringify( pr.cards.map( ( c ) => [ c.niche, c.name ] ) ) );
		check( 'Проекты в топе: скриншот сайта 16:10 с alt из медиатеки, srcset, ленивая загрузка',
			pr.cards.every( ( c ) => c.srcset && c.lazy && 1.6 === c.ratio ) && 'Главная страница сайта интернет-магазина мебели' === pr.cards[ 0 ].alt, JSON.stringify( pr.cards.map( ( c ) => [ c.alt, c.ratio ] ) ) );
		check( 'Проекты в топе: таблица — «Запрос», «Позиция в Я / G» (значки — «Яндекс», «Google» для скринридеров), подпись таблицы',
			pr.cards.every( ( c ) => 'Запрос,Позиция в Я,Позиция в G' === c.heads.join() && 'Яндекс,Google' === c.engines.join() ) && 'Позиции сайта «[Название проекта]» в поиске' === pr.cards[ 0 ].caption,
			JSON.stringify( [ pr.cards[ 0 ].heads, pr.cards[ 0 ].engines, pr.cards[ 0 ].caption ] ) );
		check( 'Проекты в топе: строки позиций из кейса',
			'купить диван-кровать|2|3,угловой диван недорого|4|6,диван с ортопедическим матрасом|1|2,мебель для гостиной|7|5' === pr.cards[ 0 ].rows.join(), pr.cards[ 0 ].rows.join() );
		check( 'Проекты в топе: позиции 1–3 — фиолетовые, ниже — светлые',
			'11001100,10110100,11011000' === pr.cards.map( ( c ) => c.top ).join() && 'rgb(109, 40, 217)' === pr.cards[ 0 ].colors[ 0 ] && 'rgb(243, 238, 255)' === pr.cards[ 1 ].colors[ 1 ],
			JSON.stringify( [ pr.cards.map( ( c ) => c.top ), pr.cards.map( ( c ) => c.colors ) ] ) );
		check( 'Проекты в топе: «[Регион] · данные на 01.11.2026»', pr.cards.every( ( c ) => '[Регион] · данные на 01.11.2026' === c.meta ), pr.cards[ 0 ].meta );

		// ===== «Карусель кейсов» =====
		const car = page.locator( '.b-carousel' );
		const state = () => car.evaluate( ( el ) => {
			const track = el.querySelector( '[data-carousel-track]' );
			return {
				counter: el.querySelector( '.b-carousel__counter' ).textContent.replace( /\s+/g, ' ' ).trim(),
				active: [ ...el.querySelectorAll( '.b-carousel__slide' ) ].findIndex( ( s ) => s.classList.contains( 'is-active' ) ),
				inert: [ ...el.querySelectorAll( '.b-carousel__slide' ) ].map( ( s ) => s.inert ? 1 : 0 ).join( '' ),
				shift: Math.round( new DOMMatrix( getComputedStyle( track ).transform ).m41 ),
				slideW: Math.round( el.querySelector( '.b-carousel__slide' ).getBoundingClientRect().width ),
				trackW: Math.round( track.getBoundingClientRect().width ),
			};
		} );
		const cr = await car.evaluate( ( el ) => ( {
			role: el.getAttribute( 'aria-roledescription' ),
			labelled: el.getAttribute( 'aria-labelledby' ) === el.querySelector( 'h2' ).id,
			h2: el.querySelector( 'h2' ).textContent.trim(),
			slides: [ ...el.querySelectorAll( '.b-carousel__slide' ) ].map( ( s ) => [ s.getAttribute( 'role' ), s.getAttribute( 'aria-roledescription' ), s.getAttribute( 'aria-label' ) ].join( ':' ) ),
			buttons: [ ...el.querySelectorAll( '.b-carousel__btn' ) ].map( ( b ) => b.getAttribute( 'aria-label' ) + ( b.getAttribute( 'aria-controls' ) === el.querySelector( '[data-carousel-track]' ).id ? '+' : '-' ) ),
			live: el.querySelector( '.b-carousel__counter' ).getAttribute( 'aria-live' ),
			niches: [ ...el.querySelectorAll( '.b-carousel__niche' ) ].map( ( n ) => n.textContent.trim() ),
			links: [ ...el.querySelectorAll( '.b-carousel__title' ) ].map( ( t ) => ( t.querySelector( 'a' ) ? t.querySelector( 'a' ).getAttribute( 'href' ) : '' ) ),
			alts: [ ...el.querySelectorAll( '.b-carousel__img' ) ].map( ( i ) => i.getAttribute( 'alt' ) ),
		} ) );
		check( 'Карусель: роль «карусель», подписана H2; слайды — группы «слайд», «1 из 4»…; стрелки с подписями управляют лентой; счётчик объявляется',
			'карусель' === cr.role && cr.labelled && 'Результаты наших клиентов' === cr.h2 && 'group:слайд:1 из 4,group:слайд:2 из 4,group:слайд:3 из 4,group:слайд:4 из 4' === cr.slides.join() &&
			'Предыдущий кейс+,Следующий кейс+' === cr.buttons.join() && 'polite' === cr.live, JSON.stringify( cr ).slice( 0, 400 ) );
		check( 'Карусель: «чем занимается клиент» — из кейса, заголовок — ссылка на кейс, alt скриншотов из медиатеки',
			'Интернет-магазин мебели,Ремонт квартир,Стоматологическая клиника,Юридическая компания' === cr.niches.join() && cr.links.every( ( l ) => l.includes( '/portfolio/' ) || l.includes( 'case' ) ) && cr.links.every( Boolean ) &&
			cr.alts.every( ( a ) => a && a.length > 20 ), JSON.stringify( [ cr.niches, cr.links ] ) );
		let st = await state();
		const hidden = await car.evaluate( ( el ) => [ getComputedStyle( el.querySelectorAll( '.b-carousel__text' )[ 1 ] ).opacity, getComputedStyle( el.querySelectorAll( '.b-carousel__img' )[ 1 ] ).opacity, getComputedStyle( el.querySelectorAll( '.b-carousel__text' )[ 0 ] ).opacity ] );
		check( 'Карусель: сначала «01 / 04», первый слайд; у соседних скриншот бледнее, текст скрыт, они недоступны (inert)',
			'01 / 04' === st.counter && 0 === st.active && '0111' === st.inert && 0 === st.shift && '0,0.45,1' === hidden.join(), JSON.stringify( [ st, hidden ] ) );
		check( 'Карусель: слайд — 60 % ленты', Math.abs( st.slideW - st.trackW * 0.6 ) <= 1, JSON.stringify( st ) );
		await car.locator( '[data-carousel-next]' ).click();
		await page.waitForTimeout( 700 );
		st = await state();
		check( 'Карусель: «Следующий кейс» — «02 / 04», лента сдвинута на слайд + промежуток', '02 / 04' === st.counter && 1 === st.active && '1011' === st.inert && Math.abs( st.shift + st.slideW + 24 ) <= 1, JSON.stringify( st ) );
		await car.locator( '[data-carousel-prev]' ).click();
		await car.locator( '[data-carousel-prev]' ).click();
		await page.waitForTimeout( 700 );
		st = await state();
		check( 'Карусель: «Предыдущий кейс» с первого — на последний («04 / 04»)', '04 / 04' === st.counter && 3 === st.active, JSON.stringify( st ) );
		await car.locator( '[data-carousel-next]' ).focus();
		await page.keyboard.press( 'ArrowRight' );
		await page.keyboard.press( 'ArrowRight' );
		st = await state();
		check( 'Карусель: клавиши → на стрелках листают (по кругу)', '02 / 04' === st.counter, JSON.stringify( st ) );
		await page.keyboard.press( 'ArrowLeft' );
		st = await state();
		check( 'Карусель: клавиша ← — назад', '01 / 04' === st.counter, JSON.stringify( st ) );
		const swipe = async ( dx, dy = 0 ) => car.locator( '[data-carousel-track]' ).evaluate( ( track, d ) => {
			const r = track.getBoundingClientRect();
			const x = r.left + 300;
			const y = r.top + 100;
			track.dispatchEvent( new PointerEvent( 'pointerdown', { bubbles: true, pointerType: 'touch', clientX: x, clientY: y } ) );
			track.dispatchEvent( new PointerEvent( 'pointerup', { bubbles: true, pointerType: 'touch', clientX: x + d[ 0 ], clientY: y + d[ 1 ] } ) );
		}, [ dx, dy ] );
		await swipe( -120 );
		const afterLeft = ( await state() ).counter;
		await swipe( 90 );
		const afterRight = ( await state() ).counter;
		await swipe( -20 );
		await swipe( -50, 120 );
		const afterSmall = ( await state() ).counter;
		check( 'Карусель: пальцем влево — следующий, вправо — предыдущий; короткий и вертикальный жест не листает',
			'02 / 04' === afterLeft && '01 / 04' === afterRight && '01 / 04' === afterSmall, JSON.stringify( [ afterLeft, afterRight, afterSmall ] ) );
		const linkFocusable = await car.evaluate( ( el ) => {
			const links = [ ...el.querySelectorAll( '.b-carousel__link' ) ];
			links[ 1 ].focus();
			return [ document.activeElement === links[ 1 ], ( links[ 0 ].focus(), document.activeElement === links[ 0 ] ) ];
		} );
		check( 'Карусель: ссылка соседнего слайда недоступна с клавиатуры, текущего — доступна', false === linkFocusable[ 0 ] && true === linkFocusable[ 1 ], JSON.stringify( linkFocusable ) );

		// ===== «Отзывы клиентов», «3 карточки» =====
		const reviewsUrl = wp( 'eval', 'echo Trust\\Page_Types::url( "reviews" );' );
		const rv = await page.locator( '.b-reviews' ).evaluate( ( el ) => ( {
			id: el.id,
			bg: getComputedStyle( el ).backgroundColor,
			h2: el.querySelector( 'h2' ).textContent.trim(),
			all: el.querySelector( '.b-reviews__all' ) ? [ el.querySelector( '.b-reviews__all' ).textContent.trim(), el.querySelector( '.b-reviews__all' ).getAttribute( 'href' ) ] : null,
			cards: [ ...el.querySelectorAll( '.b-reviews__card' ) ].map( ( c ) => ( {
				tag: c.tagName + '>' + c.querySelector( 'blockquote' ).tagName,
				quote: c.querySelector( 'blockquote' ).textContent.trim(),
				avatar: !! c.querySelector( '.b-reviews__avatar[aria-hidden="true"] svg' ),
				name: c.querySelector( '.b-reviews__name' ).textContent.trim(),
				role: c.querySelector( '.b-reviews__role' ).textContent.trim(),
				orig: c.querySelector( '.b-reviews__original' ) ? [ c.querySelector( '.b-reviews__original' ).getAttribute( 'href' ), c.querySelector( '.b-reviews__original' ).getAttribute( 'target' ), c.querySelector( '.b-reviews__original' ).getAttribute( 'rel' ), c.querySelector( '.b-reviews__original' ).textContent.replace( /\s+/g, ' ' ).trim() ] : null,
				bg: getComputedStyle( c ).backgroundColor,
				border: getComputedStyle( c ).borderTopWidth,
			} ) ),
		} ) );
		check( 'Отзывы: якорь #reviews, H2 «Что говорят клиенты», белый фон, «Все отзывы» → страница «Отзывы»',
			'reviews' === rv.id && 'Что говорят клиенты' === rv.h2 && 'rgb(255, 255, 255)' === rv.bg && rv.all && 'Все отзывы' === rv.all[ 0 ] && reviewsUrl === rv.all[ 1 ] && reviewsUrl.includes( '/otzyvy/' ), JSON.stringify( [ rv.all, reviewsUrl ] ) );
		check( 'Отзывы «3 карточки»: 3 отзыва по порядку выбора — цитата (figure > blockquote), силуэт вместо фото, имя, должность и компания',
			3 === rv.cards.length && rv.cards.every( ( c ) => 'FIGURE>BLOCKQUOTE' === c.tag && c.avatar && '[Имя Фамилия]' === c.name && '[Должность], [Компания]' === c.role ) &&
			rv.cards[ 0 ].quote.startsWith( '[Отзыв о результате' ) && rv.cards[ 1 ].quote.startsWith( '[Отзыв о процессе' ) && rv.cards[ 2 ].quote.startsWith( '[Отзыв о команде' ), JSON.stringify( rv.cards.map( ( c ) => [ c.quote.slice( 0, 20 ), c.role ] ) ) );
		check( 'Отзывы: «Оригинал» — ссылка на оригинал в новой вкладке, для скринридеров — чей отзыв',
			rv.cards.every( ( c ) => c.orig && c.orig[ 0 ].startsWith( 'https://example.com/review-' ) && '_blank' === c.orig[ 1 ] && 'noopener' === c.orig[ 2 ] && c.orig[ 3 ].startsWith( 'Оригинал отзыва: [Имя Фамилия]' ) ),
			JSON.stringify( rv.cards[ 0 ].orig ) );
		check( 'Отзывы: на белом фоне карточки лавандовые, без рамки', rv.cards.every( ( c ) => 'rgb(248, 245, 255)' === c.bg && '0px' === c.border ), rv.cards[ 0 ].bg );

		// ===== Колонки и ширины =====
		for ( const [ w, cols ] of [ [ 1280, 3 ], [ 768, 2 ], [ 390, 1 ] ] ) {
			await page.setViewportSize( { width: w, height: 900 } );
			await page.waitForTimeout( 300 );
			const got = [ await columns( page, '.b-projects__card' ), await columns( page, '.b-reviews__item' ) ];
			check( `${ w }px: «Проекты в топе» и «Отзывы» — по ${ cols } в ряд`, got.every( ( c ) => cols === c ), JSON.stringify( got ) );
		}
		await page.setViewportSize( { width: 390, height: 900 } );
		st = await state();
		check( '390px: слайд карусели — 86 % ленты', Math.abs( st.slideW - st.trackW * 0.86 ) <= 1, JSON.stringify( st ) );
		for ( const w of [ 1440, 1024, 600, 360, 320, 280 ] ) {
			await page.setViewportSize( { width: w, height: 900 } );
			await page.waitForTimeout( 200 );
			const over = await page.evaluate( () => [ document.documentElement.scrollWidth > innerWidth, [ ...document.querySelectorAll( '.b-projects *, .b-reviews *, .b-carousel__top *, .b-carousel__slide.is-active *' ) ].filter( ( n ) => n.getBoundingClientRect().right > innerWidth + 1 ).map( ( n ) => n.className ).slice( 0, 3 ) ] );
			check( `${ w }px: без прокрутки вбок, содержимое блоков в экране`, ! over[ 0 ] && ! over[ 1 ].length, JSON.stringify( over ) );
		}
		await page.setViewportSize( { width: 390, height: 900 } );
		const small = await page.evaluate( () => [ ...document.querySelectorAll( '.b-carousel__btn, .b-reviews__all, .b-reviews__original' ) ].map( ( el ) => {
			const r = el.getBoundingClientRect();
			const s = getComputedStyle( el );
			return [ el.className, Math.round( r.width ), Math.round( r.height + parseFloat( s.marginTop ) * -2 ) ];
		} ).filter( ( r ) => r[ 1 ] < 24 || r[ 2 ] < 24 ) );
		check( '390px: зоны нажатия стрелок, «Все отзывы», «Оригинал» — не меньше 24 px', 0 === small.length, JSON.stringify( small ) );

		// ===== «2 карточки» в портфолио =====
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( PORTFOLIO, { waitUntil: 'networkidle' } );
		const two = await page.locator( '.b-reviews' ).evaluate( ( el ) => ( {
			bg: getComputedStyle( el ).backgroundColor,
			cls: el.className,
			cards: [ ...el.querySelectorAll( '.b-reviews__card' ) ].map( ( c ) => ( {
				name: c.querySelector( '.b-reviews__name' ).textContent.trim(),
				role: c.querySelector( '.b-reviews__role' ).textContent.trim(),
				avatar: !! c.querySelector( '.b-reviews__avatar' ),
				orig: !! c.querySelector( '.b-reviews__original' ),
				bg: getComputedStyle( c ).backgroundColor,
				border: getComputedStyle( c ).borderTopWidth,
				mark: getComputedStyle( c.querySelector( '.b-reviews__mark' ) ).fill,
			} ) ),
		} ) );
		check( 'Портфолио: «Что говорят клиенты» — 2 карточки на лавандовом фоне, белые с рамкой',
			'rgb(248, 245, 255)' === two.bg && two.cls.includes( 'b-reviews--two' ) && 2 === two.cards.length && two.cards.every( ( c ) => 'rgb(255, 255, 255)' === c.bg && '1px' === c.border && 'rgb(201, 188, 235)' === c.mark ), JSON.stringify( two ).slice( 0, 300 ) );
		check( 'Портфолио: в карточке — имя и «должность, компания · отрасль кейса», без фото и «Оригинала»',
			'Директор, [Ремонт&Дизайн] · Ремонт и строительство' === two.cards[ 0 ].role && '[Должность], [Компания] · Медицина' === two.cards[ 1 ].role && two.cards.every( ( c ) => ! c.avatar && ! c.orig ), JSON.stringify( two.cards.map( ( c ) => c.role ) ) );
		check( 'Портфолио 1280px: 2 в ряд', 2 === await columns( page, '.b-reviews__item' ) );

		// ===== Режимы выбора и пустые блоки =====
		const caseNoPos = demoId( 'case', 'auto' );
		const caseRepair = demoId( 'case', 'repair' );
		const reviewList = demoId( 'review', 'list-1' );
		const reviewDental = demoId( 'review', 'case-dental' );
		const video = wp( 'post', 'create', '--post_type=review', `--post_title=${ MARK } видео`, '--post_status=publish', '--porcelain' );
		created.push( video );
		wp( 'post', 'meta', 'update', video, 'trust_kind', 'video' );
		wp( 'post', 'meta', 'update', video, 'trust_text', 'Видеоотзыв с текстом — в карточки не попадает' );
		wp( 'post', 'meta', 'update', video, 'trust_name', 'Видео Автор' );
		const letterImage = wp( 'eval', `echo (int) get_post_meta( ${ caseRepair }, 'trust_screenshot', true );` );
		const letter = wp( 'post', 'create', '--post_type=review', `--post_title=${ MARK } письмо`, '--post_status=publish', '--porcelain' );
		created.push( letter );
		wp( 'post', 'meta', 'update', letter, 'trust_kind', 'text' );
		wp( 'post', 'meta', 'update', letter, 'trust_text', 'Отзыв со сканом письма.' );
		wp( 'post', 'meta', 'update', letter, 'trust_name', 'Автор Письма' );
		wp( 'post', 'meta', 'update', letter, 'trust_company', 'ООО Тест' );
		wp( 'post', 'meta', 'update', letter, 'trust_letter', letterImage );

		const modes = createPage( 'режимы', [
			block( 'projects-top', "array( 'title' => 'Авто', 'source' => 'auto', 'count' => 2 )" ),
			block( 'projects-top', "array( 'title' => 'Все с позициями', 'source' => 'auto', 'count' => 6 )" ),
			block( 'projects-top', `array( 'title' => 'Вручную', 'source' => 'manual', 'cases' => array( ${ caseNoPos }, ${ caseRepair } ) )` ),
			block( 'projects-top', `array( 'title' => 'Пусто', 'source' => 'manual', 'cases' => array( ${ caseNoPos } ) )` ),
			block( 'reviews', "array( 'title' => 'Последние', 'source' => 'latest', 'count' => 9, 'more_label' => '' )" ),
			block( 'reviews', `array( 'title' => 'GEO', 'source' => 'service', 'service' => ${ wp( 'eval', 'echo get_term_by( "slug", "geo", "service_type" )->term_id ?? 0;' ) } )` ),
			block( 'reviews', `array( 'title' => 'Письмо', 'source' => 'manual', 'reviews' => array( ${ letter }, ${ video }, ${ reviewList } ) )` ),
			block( 'reviews', `array( 'title' => 'Нет отзывов', 'source' => 'manual', 'reviews' => array( ${ video } ) )` ),
			block( 'cases-carousel', `array( 'title' => 'Один слайд', 'slides' => array( array( 'image' => ${ letterImage }, 'title' => 'Единственный', 'niche' => 'Своя строка', 'case' => ${ caseRepair } ), array( 'title' => 'Без картинки' ) ) )` ),
		].join( ' . "\\n\\n" . ' ) );
		created.push( modes );
		await page.goto( `${ URL }/?page_id=${ modes }`, { waitUntil: 'networkidle' } );
		const md = await page.evaluate( () => {
			const by = ( sel ) => Object.fromEntries( [ ...document.querySelectorAll( sel ) ].map( ( s ) => [ s.querySelector( 'h2' ).textContent.trim(), s ] ) );
			const pts = by( '.b-projects' );
			const rvs = by( '.b-reviews' );
			const names = ( s, q ) => ( s ? [ ...s.querySelectorAll( q ) ].map( ( n ) => n.textContent.trim() ) : null );
			const car = document.querySelector( '.b-carousel' );
			return {
				auto: names( pts[ 'Авто' ], '.b-projects__niche' ),
				all: names( pts[ 'Все с позициями' ], '.b-projects__niche' ),
				manual: names( pts[ 'Вручную' ], '.b-projects__niche' ),
				empty: ! pts[ 'Пусто' ],
				latest: names( rvs[ 'Последние' ], '.b-reviews__name' ),
				latestAll: !! ( rvs[ 'Последние' ] && rvs[ 'Последние' ].querySelector( '.b-reviews__all' ) ),
				geo: names( rvs.GEO, '.b-reviews__quote' ),
				letter: names( rvs[ 'Письмо' ], '.b-reviews__name' ),
				letterLink: rvs[ 'Письмо' ] ? [ rvs[ 'Письмо' ].querySelector( '.b-reviews__original' ).hasAttribute( 'data-trust-lightbox' ), rvs[ 'Письмо' ].querySelector( '.b-reviews__original' ).getAttribute( 'target' ) ] : null,
				noReviews: ! rvs[ 'Нет отзывов' ],
				carousel: car ? [ car.querySelectorAll( '.b-carousel__slide' ).length, !! car.querySelector( '.b-carousel__nav' ), car.querySelector( '.b-carousel__niche' ).textContent.trim() ] : null,
			};
		} );
		check( 'Проекты в топе, автоматически: последние кейсы только с позициями, по количеству (2 и все 3)',
			2 === md.auto.length && 3 === md.all.length && md.all.every( ( n ) => [ 'Интернет-магазин мебели', 'Стоматологическая клиника', 'Ремонт квартир' ].includes( n ) ), JSON.stringify( [ md.auto, md.all ] ) );
		check( 'Проекты в топе, вручную: кейс без позиций пропущен; только такие — блока на сайте нет', 'Ремонт квартир' === md.manual.join() && md.empty, JSON.stringify( [ md.manual, md.empty ] ) );
		check( 'Отзывы, последние: только текстовые (видеоотзыв не попадает), без кнопки при пустой подписи',
			md.latest.length >= 9 && ! md.latest.includes( 'Видео Автор' ) && ! md.latestAll, JSON.stringify( md.latest ) );
		check( 'Отзывы по услуге GEO: только отзывы с этой услугой', 1 === md.geo.length && md.geo[ 0 ].includes( 'о работе команды' ), JSON.stringify( md.geo ) );
		check( 'Отзывы вручную: видеоотзыв пропущен; только он — блока на сайте нет', 'Автор Письма,[Имя Фамилия]' === md.letter.join() && md.noReviews, JSON.stringify( [ md.letter, md.noReviews ] ) );
		check( 'Отзывы: «Оригинал» у отзыва со сканом письма — на весь экран, не в новой вкладке', md.letterLink && md.letterLink[ 0 ] && null === md.letterLink[ 1 ], JSON.stringify( md.letterLink ) );
		check( 'Карусель: один слайд (слайд без картинки пропущен) — без стрелок и счётчика; своя строка вместо строки кейса',
			JSON.stringify( [ 1, false, 'Своя строка' ] ) === JSON.stringify( md.carousel ), JSON.stringify( md.carousel ) );
		await page.locator( '.b-reviews:has(h2:text-is("Письмо")) .b-reviews__original' ).first().click();
		await page.locator( 'dialog.lightbox[open]' ).waitFor( { timeout: 5000 } );
		const lb = await page.evaluate( () => [ document.querySelector( '.lightbox__img' ).getAttribute( 'src' ), document.querySelector( '.lightbox__caption' ).textContent.trim() ] );
		check( 'Отзывы: скан письма открывается на весь экран с подписью «Благодарственное письмо — автор»', lb[ 0 ].includes( 'project-repair' ) && 'Благодарственное письмо — Автор Письма, ООО Тест' === lb[ 1 ], JSON.stringify( lb ) );
		await page.keyboard.press( 'Escape' );

		// ===== «Уменьшить движение» и вид без JavaScript =====
		const reduced = await browser.newContext( { viewport: { width: 1280, height: 900 }, reducedMotion: 'reduce' } );
		const rp = await reduced.newPage();
		await rp.goto( SEO, { waitUntil: 'networkidle' } );
		const motion = await rp.evaluate( () => [ '.b-carousel__track', '.b-carousel__img', '.b-carousel__text' ].map( ( s ) => getComputedStyle( document.querySelector( s ) ).transitionDuration ) );
		check( 'Карусель: при «Уменьшить движение» — без анимации', motion.every( ( d ) => parseFloat( d ) < 0.001 ), motion.join() );
		await reduced.close();
		const nojs = await browser.newContext( { viewport: { width: 1280, height: 900 }, javaScriptEnabled: false } );
		const np = await nojs.newPage();
		await np.goto( SEO, { waitUntil: 'networkidle' } );
		const noScript = await np.evaluate( () => {
			const el = document.querySelector( '.b-carousel' );
			return {
				nav: getComputedStyle( el.querySelector( '.b-carousel__nav' ) ).display,
				scroll: getComputedStyle( el.querySelector( '.b-carousel__track' ) ).overflowX,
				texts: [ ...el.querySelectorAll( '.b-carousel__text' ) ].map( ( t ) => getComputedStyle( t ).opacity ).join(),
			};
		} );
		check( 'Карусель без JavaScript: стрелок нет, лента прокручивается вбок, все тексты видны', 'none' === noScript.nav && 'auto' === noScript.scroll && '1,1,1,1' === noScript.texts, JSON.stringify( noScript ) );
		await nojs.close();

		// ===== Сверка с макетами =====
		for ( const width of [ 1280, 768, 390 ] ) {
			await compare( page, {
				name: 'list-projects', width, url: SEO, theme: '.b-projects', mockup: 'seo-bloki/12-proekty-v-tope.html', mock: 'section',
				// Демо-данные: у кейса «Ремонт квартир» — клиент из макета кейса, дата проверки заполнена.
				mockBefore: () => {
					document.querySelectorAll( 'h3' )[ 2 ].textContent = '[Ремонт&Дизайн]';
					document.querySelectorAll( 'article span' ).forEach( ( s ) => {
						if ( ! s.children.length && s.textContent.includes( '[дата]' ) ) {
							s.textContent = s.textContent.replace( '[дата]', '01.11.2026' );
						}
					} );
				},
			} );
			await compare( page, { name: 'list-carousel', width, url: SEO, theme: '.b-carousel', mockup: 'seo-bloki/14-karusel-keysov.html', mock: 'section' } );
			await compare( page, {
				name: 'list-reviews', width, url: SEO, theme: '.b-reviews', mockup: 'seo-bloki/15-otzyvy.html', mock: 'section',
				// «Все отзывы» по ТЗ (в макете блока нет); должность и компания — отдельные поля отзыва.
				hide: [ '.b-reviews__all' ],
				mockBefore: () => document.querySelectorAll( 'figcaption span span:last-child' ).forEach( ( s ) => ( s.textContent = '[Должность], [Компания]' ) ),
			} );
		}
		for ( const width of [ 1280, 390 ] ) {
			await compare( page, {
				name: 'list-reviews-two', width, url: PORTFOLIO, theme: '.b-reviews', mockup: 'portfolio-blog-404/portfolio.html', mock: 'section[aria-labelledby="rev-title"]',
				// Первая карточка — отзыв к кейсу «Ремонт квартир» (текст и должность из макета кейса).
				before: () => {
					const card = document.querySelector( '.b-reviews__card' );
					card.querySelector( 'blockquote p' ).textContent = '[Отзыв клиента о результате: какая была задача, что изменилось после работы — 2–3 предложения.]';
					card.querySelector( '.b-reviews__role' ).textContent = '[Должность], [Компания] · Ремонт и строительство';
				},
			} );
		}

		// ===== Доступность =====
		await page.setViewportSize( { width: 1280, height: 900 } );
		for ( const url of [ SEO, PORTFOLIO ] ) {
			await page.goto( url, { waitUntil: 'networkidle' } );
			await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
			const axe = await page.evaluate( async () => {
				const r = await window.axe.run( { include: [ [ '.b-projects' ], [ '.b-carousel' ], [ '.b-reviews' ] ].filter( ( s ) => document.querySelector( s[ 0 ] ) ) }, { resultTypes: [ 'violations' ] } );
				return r.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length })` );
			} );
			check( `axe ${ url.replace( URL, '' ) }: нет нарушений critical/serious`, 0 === axe.length, axe.join( ', ' ) );
		}
		check( 'Консоль браузера на сайте без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1440, height: 1000 } } );
		const editorErrors = [];
		editor.on( 'pageerror', ( e ) => editorErrors.push( e.message ) );
		await login( editor );
		const id = wp( 'eval', 'echo get_page_by_path( "seo" )->ID;' );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ id }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		const reg = await editor.evaluate( () => [ 'trust/projects-top', 'trust/cases-carousel', 'trust/reviews' ].map( ( name ) => {
			const type = wp.blocks.getBlockType( name );
			return [ name, type && type.title, type && type.category, !! ( type && type.example ) ];
		} ) );
		check( 'Редактор: три блока в категории «Списки», у всех превью',
			JSON.stringify( reg ) === JSON.stringify( [
				[ 'trust/projects-top', 'Наши проекты в топе', 'trust-lists', true ],
				[ 'trust/cases-carousel', 'Карусель кейсов', 'trust-lists', true ],
				[ 'trust/reviews', 'Отзывы клиентов', 'trust-lists', true ],
			] ), JSON.stringify( reg ) );
		const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-reviews' ).first().waitFor( { timeout: 30000 } );
		check( 'Редактор: превью — 3 проекта, 4 слайда, 3 отзыва',
			3 === await canvas.locator( '.b-projects__card' ).count() && 4 === await canvas.locator( '.b-carousel__slide' ).count() && 3 === await canvas.locator( '.b-reviews__card' ).count() );
		await editor.screenshot( { path: path.join( OUT, 'list-blocks-editor.png' ) } );
		check( 'Редактор без ошибок JavaScript', 0 === editorErrors.length, editorErrors.slice( 0, 3 ).join( ' | ' ) );
	} finally {
		created.forEach( ( id ) => wp( 'post', 'delete', String( id ), '--force' ) );
	}

	await browser.close();
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
