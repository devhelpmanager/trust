/**
 * Тест шага 5.1 — список статей блога (home.php, макет seo-blog/blog.html).
 *
 * Запуск: node wp-theme/tests/blog.js
 *
 * Демо: «Главная» — главная страница сайта, «Блог» — страница записей (Настройки → Чтение), 21 статья
 * из макета (даты по порядку, рубрики, лайки, обложки). Проверяет крошки, H1 и подзаголовок, главную
 * статью (плашки, H2, анонс, автор, дата, время чтения, лайки, комментарии, «Читать полностью» в правом нижнем углу; в сетке не
 * повторяется), «Все статьи» и «Страница N из M», карточки (первые две крупные с анонсом, остальные —
 * 3 в ряд; рубрика, H3, дата, лайки, комментарии), статей на странице из «Настроек сайта → Блог»
 * (пагинация, страницы 2+ продолжают сетку, за пределами — 404), «Тексты шаблонов», «Показывать
 * лайки», флажок «Главная статья», CollectionPage + ItemList, раскладку 3 / 2 / 1 и телефонные карточки
 * (обложка слева), совпадение с макетом на 1280 / 768 / 390 px, ширины 1440–280, axe-core, консоль,
 * поле «Подзаголовок» в редакторе только у страницы блога.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp } = require( './lib' );

const BLOG = `${ URL }/blog/`;
const SETTING = ( name ) => `Trust\\Fields::key( Trust\\Settings::PREFIX, '${ name }' )`;

// Тексты макета для сверки (у демо-статей анонсы — из других макетов).
const MOCK = {
	featured: 'Одна неверная строка в robots.txt может закрыть от поисковых роботов весь сайт. Разбираем, как устроен файл, как его проверить и какие ошибки встречаются чаще всего.',
	excerpts: [
		'Разбираем частые причины — дубли, ошибки сервера, закрытые разделы и малополезный контент — и что делать в каждом случае.',
		'Что влияет на то, попадёт ли ваш сайт в ответ ChatGPT, Алисы или Perplexity, и с чего начать работу.',
	],
	dates: [ '2 ноября 2026', '29 октября 2026', '27 октября 2026', '24 октября 2026', '21 октября 2026' ],
	comments: [ '9', '21', '14', '5', '6' ],
};

/**
 * Сравнение снимков темы и макета.
 *
 * @param {import('playwright').Page} page Страница.
 * @param {Object}                    o    name, width, theme, mock, before(page), mockBefore(page).
 */
async function compare( page, o ) {
	const base = path.join( OUT, `blog-${ o.name }-${ o.width }` );
	await page.setViewportSize( { width: o.width, height: 900 } );
	await page.goto( BLOG, { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	await o.before( page );
	await isolate( page, o.theme );
	await page.waitForTimeout( 200 );
	await page.locator( o.theme ).first().screenshot( { path: `${ base }-theme.png` } );
	await page.goto( mockupUrl( 'seo-blog/blog.html' ), { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	await o.mockBefore( page );
	await isolate( page, o.mock );
	await page.waitForTimeout( 200 );
	await page.locator( o.mock ).first().screenshot( { path: `${ base }-mockup.png` } );
	const diff = comparePng( `${ base }-mockup.png`, `${ base }-theme.png`, `${ base }-diff.png` );
	check( `Макет seo-blog/blog.html, ${ o.name }, ${ o.width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
}

/** Состояние страницы блога. */
function state( page ) {
	return page.evaluate( () => {
		const text = ( el ) => ( el ? el.textContent.replace( /\s+/g, ' ' ).trim() : null );
		const stat = ( el ) => [ ...el.querySelectorAll( '.post-stat' ) ].map( ( s ) => text( s ) );
		const f = document.querySelector( '.post-feature' );
		return {
			crumbs: [ ...document.querySelectorAll( '.breadcrumbs__item' ) ].map( text ),
			h1: [ ...document.querySelectorAll( 'h1' ) ].map( text ),
			lead: text( document.querySelector( '.archive-hero__lead' ) ),
			featured: f && {
				href: new URL( f.href ).pathname,
				badges: [ ...f.querySelectorAll( '.post-feature__badges > span' ) ].map( text ),
				h2: text( f.querySelector( 'h2' ) ),
				excerpt: text( f.querySelector( '.post-feature__excerpt' ) ),
				author: [ text( f.querySelector( '.post-author__name' ) ), text( f.querySelector( '.post-author__role' ) ) ],
				date: [ text( f.querySelector( 'time' ) ), f.querySelector( 'time' ).getAttribute( 'datetime' ) ],
				stats: stat( f ),
				button: text( f.querySelector( '.post-feature__btn' ) ),
			},
			all: text( document.querySelector( '#posts-title' ) ),
			page: text( document.querySelector( '.post-list__page' ) ),
			lists: [ ...document.querySelectorAll( '.post-cards' ) ].map( ( ul ) => [ ul.classList.contains( 'post-cards--lg' ) ? 'lg' : 'sm', ul.children.length ] ),
			cards: [ ...document.querySelectorAll( '.post-card' ) ].map( ( a ) => ( {
				href: new URL( a.href ).pathname,
				rubric: text( a.querySelector( '.post-rubric' ) ),
				h3: text( a.querySelector( 'h3' ) ),
				excerpt: text( a.querySelector( '.post-card__excerpt' ) ),
				date: text( a.querySelector( 'time' ) ),
				stats: stat( a ),
				img: !! a.querySelector( 'img.post-card__img[alt=""]' ),
			} ) ),
			// Видимые элементы пагинации («…» у края показывается только на узких экранах).
			pager: document.querySelector( '.pager' ) ? [ document.querySelector( '.pager' ).getAttribute( 'aria-label' ), [ ...document.querySelector( '.pager' ).children ].filter( ( el ) => 'none' !== getComputedStyle( el ).display ).map( text ).join( ' ' ) ] : null,
		};
	} );
}

( async () => {
	clearLog();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const saved = JSON.parse( wp( 'eval', `echo wp_json_encode( array( get_field( ${ SETTING( 'blog_per_page' ) }, 'option' ), get_field( ${ SETTING( 'blog_show_likes' ) }, 'option' ), get_field( ${ SETTING( 'tpl_blog_new' ) }, 'option' ), get_field( ${ SETTING( 'tpl_blog_read' ) }, 'option' ), get_field( ${ SETTING( 'tpl_blog_all' ) }, 'option' ) ) );` ) );
	const setField = ( name, value ) => wp( 'eval', `update_field( ${ SETTING( name ) }, ${ JSON.stringify( value ) }, 'option' );` );
	const demoId = ( key ) => wp( 'eval', `echo (int) ( get_posts( array( 'post_type' => 'post', 'meta_key' => '_trust_demo_id', 'meta_value' => 'post:${ key }', 'fields' => 'ids', 'post_status' => 'any' ) )[0] ?? 0 );` );

	try {
		// ===== «Настройки → Чтение» и демо-главная =====
		const reading = JSON.parse( wp( 'eval', 'echo wp_json_encode( array( get_option( "show_on_front" ), get_post_field( "post_name", (int) get_option( "page_on_front" ) ), get_post_field( "post_name", (int) get_option( "page_for_posts" ) ) ) );' ) );
		await page.goto( `${ URL }/`, { waitUntil: 'networkidle' } );
		const home = await page.evaluate( () => [ document.querySelector( 'h1' ).textContent.trim(), [ ...document.querySelectorAll( 'main section' ) ].map( ( s ) => s.className.split( ' ' )[ 0 ] ).filter( ( c ) => /^b-|hero/.test( c ) ) ] );
		check( 'Демо: «Главная» — главная страница (первый экран, «Чем мы занимаемся», отзывы, «Наш блог», «Обсудим ваш проект»), «Блог» — страница записей',
			JSON.stringify( reading ) === JSON.stringify( [ 'page', 'glavnaya', 'blog' ] ) && 'Продвигаем сайты в Яндексе, Google и нейросетях' === home[ 0 ] && [ 'b-dirs', 'b-reviews', 'b-posts', 'b-cta' ].every( ( c ) => home[ 1 ].includes( c ) ),
			JSON.stringify( [ reading, home ] ) );

		// ===== Страница блога =====
		const res = await page.goto( BLOG, { waitUntil: 'networkidle' } );
		const s = await state( page );
		check( '/blog/ — 200; крошки «Главная / Блог», один H1 «Блог», подзаголовок из поля страницы',
			200 === res.status() && JSON.stringify( s.crumbs ) === JSON.stringify( [ 'Главная', 'Блог' ] ) && JSON.stringify( s.h1 ) === JSON.stringify( [ 'Блог' ] ) && s.lead.startsWith( 'Разборы и инструкции о SEO' ), JSON.stringify( [ s.crumbs, s.h1, s.lead ] ) );
		check( 'Главная статья — самая новая: «Новая статья» и рубрика, H2, анонс, автор и должность, дата по-русски, «N мин чтения», лайки 86, комментарии, «Читать полностью»; ссылка на статью',
			s.featured && JSON.stringify( s.featured.badges ) === JSON.stringify( [ 'Новая статья', 'Технический аудит' ] ) && 'Robots.txt: как проверить файл и не закрыть сайт от поиска' === s.featured.h2 && '' !== s.featured.excerpt &&
			'[Имя Фамилия]' === s.featured.author[ 0 ] && !! s.featured.author[ 1 ] && /^\d+ [а-я]+ \d{4}$/.test( s.featured.date[ 0 ] ) && /^\d{4}-\d\d-\d\d$/.test( s.featured.date[ 1 ] ) &&
			/^\d+ мин чтения$/.test( s.featured.stats[ 0 ] ) && 'Лайки: 86' === s.featured.stats[ 1 ] && /^Комментарии: \d+$/.test( s.featured.stats[ 2 ] ) && 'Читать полностью' === s.featured.button && '/blog/robots-txt-proverka/' === s.featured.href,
			JSON.stringify( s.featured ) );
		check( '«Все статьи» (H2) и «Страница 1 из 1»; 20 статей: 2 крупные + 18 по 3 в ряд, новые первыми, главная статья в сетке не повторяется; без пагинации',
			'Все статьи' === s.all && 'Страница 1 из 1' === s.page && JSON.stringify( s.lists ) === JSON.stringify( [ [ 'lg', 2 ], [ 'sm', 18 ] ] ) && 20 === s.cards.length &&
			'Почему страницы выпадают из индекса и как это исправить' === s.cards[ 0 ].h3 && 'Сквозная аналитика: как связать заявки с рекламой' === s.cards[ 19 ].h3 && ! s.cards.some( ( c ) => c.href === s.featured.href ) && null === s.pager,
			JSON.stringify( [ s.all, s.page, s.lists, s.cards.length, s.pager ] ) );
		check( 'Карточка: рубрика, H3, анонс только у крупных, дата, лайки и комментарии (подписи для скринридеров), обложка с пустым alt; вся карточка — ссылка',
			'Индексация' === s.cards[ 0 ].rubric && null !== s.cards[ 0 ].excerpt && null === s.cards[ 2 ].excerpt && 'Контекстная реклама' === s.cards[ 2 ].rubric && 'Лайки: 64' === s.cards[ 0 ].stats[ 0 ] && /^Комментарии: \d+$/.test( s.cards[ 0 ].stats[ 1 ] ) &&
			s.cards.every( ( c ) => c.img && c.date && c.h3 ) && s.cards[ 0 ].href.startsWith( '/blog/' ), JSON.stringify( s.cards.slice( 0, 3 ) ) );
		check( 'Страница блога 2 при одной странице — 404', 404 === ( await page.request.get( `${ BLOG }page/2/` ) ).status() );

		const ld = await page.evaluate( () => {
			const graph = [ ...document.querySelectorAll( 'script[type="application/ld+json"]' ) ].flatMap( ( x ) => JSON.parse( x.textContent )[ '@graph' ] || [] );
			const list = graph.find( ( n ) => 'ItemList' === n[ '@type' ] );
			const pg = graph.find( ( n ) => 'CollectionPage' === n[ '@type' ] );
			return [ !! pg, list && list.numberOfItems, list && list.itemListElement[ 0 ].name, pg && pg.mainEntity && list && pg.mainEntity[ '@id' ] === list[ '@id' ] ];
		} );
		check( 'Микроразметка: CollectionPage, ItemList показанных статей (главная + 20), главное содержимое страницы', JSON.stringify( ld ) === JSON.stringify( [ true, 21, 'Robots.txt: как проверить файл и не закрыть сайт от поиска', true ] ), JSON.stringify( ld ) );

		// ===== Статей на странице: 6 → 4 страницы =====
		setField( 'blog_per_page', 6 );
		await page.goto( BLOG, { waitUntil: 'networkidle' } );
		const p1 = await state( page );
		const seen = p1.cards.map( ( c ) => c.href );
		const pages = [ p1 ];
		for ( const n of [ 2, 3, 4 ] ) {
			await page.goto( `${ BLOG }page/${ n }/`, { waitUntil: 'networkidle' } );
			const sn = await state( page );
			pages.push( sn );
			seen.push( ...sn.cards.map( ( c ) => c.href ) );
		}
		check( '«Статей на странице» = 6: «Страница 1 из 4», пагинация «Страницы блога» «Назад 1 2 3 4 Вперёд», ссылки /blog/page/N/#posts',
			'Страница 1 из 4' === p1.page && 6 === p1.cards.length && p1.pager && 'Страницы блога' === p1.pager[ 0 ] && 'Назад1234Вперёд' === p1.pager[ 1 ].replace( /\s/g, '' ) &&
			( await page.evaluate( () => [ ...document.querySelectorAll( '.pager a' ) ].some( ( a ) => a.href.endsWith( '/blog/page/3/#posts' ) ) ) ), JSON.stringify( [ p1.page, p1.pager ] ) );
		check( 'Страницы 2–4: без главной статьи, «Страница N из 4», сетка продолжается без повторов (20 статей, главная — только сверху на первой)',
			pages.slice( 1 ).every( ( x, i ) => null === x.featured && `Страница ${ i + 2 } из 4` === x.page ) && 20 === seen.length && 20 === new Set( seen ).size && ! seen.includes( p1.featured.href ),
			JSON.stringify( pages.map( ( x ) => [ x.page, !! x.featured, x.cards.length ] ) ) );
		check( 'Страница 5 из 4 — 404', 404 === ( await page.request.get( `${ BLOG }page/5/` ) ).status() );
		setField( 'blog_per_page', saved[ 0 ] || '' );

		// ===== Тексты шаблонов, лайки, флажок «Главная статья» =====
		setField( 'tpl_blog_new', 'Свежее' );
		setField( 'tpl_blog_read', 'Открыть' );
		setField( 'tpl_blog_all', 'Все материалы' );
		setField( 'blog_show_likes', 0 );
		const geo = demoId( 'geo' );
		wp( 'post', 'meta', 'update', geo, 'trust_featured', '1' );
		await page.goto( BLOG, { waitUntil: 'networkidle' } );
		const custom = await state( page );
		wp( 'post', 'meta', 'delete', geo, 'trust_featured' );
		setField( 'tpl_blog_new', saved[ 2 ] || '' );
		setField( 'tpl_blog_read', saved[ 3 ] || '' );
		setField( 'tpl_blog_all', saved[ 4 ] || '' );
		setField( 'blog_show_likes', null === saved[ 1 ] ? 1 : saved[ 1 ] );
		check( '«Тексты шаблонов»: своя плашка, кнопка и заголовок списка', 'Свежее' === custom.featured.badges[ 0 ] && 'Открыть' === custom.featured.button && 'Все материалы' === custom.all, JSON.stringify( [ custom.featured.badges, custom.featured.button, custom.all ] ) );
		check( '«Показывать лайки» выключено — лайков нет ни в главной статье, ни в карточках', ! custom.featured.stats.some( ( x ) => x.startsWith( 'Лайки' ) ) && custom.cards.every( ( c ) => ! c.stats.some( ( x ) => x.startsWith( 'Лайки' ) ) ), JSON.stringify( custom.cards[ 0 ].stats ) );
		check( 'Флажок «Главная статья» у другой статьи — она сверху, а самая новая — первой в сетке', 'GEO: как подготовить сайт к ответам нейросетей' === custom.featured.h2 && 'Robots.txt: как проверить файл и не закрыть сайт от поиска' === custom.cards[ 0 ].h3, JSON.stringify( [ custom.featured.h2, custom.cards[ 0 ].h3 ] ) );

		// ===== Раскладка =====
		const layout = {};
		for ( const width of [ 1280, 1000, 700, 390, 360 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( BLOG, { waitUntil: 'domcontentloaded' } );
			layout[ width ] = await page.evaluate( () => {
				const cols = ( sel ) => new Set( [ ...document.querySelectorAll( sel ) ].map( ( li ) => Math.round( li.getBoundingClientRect().left ) ) ).size;
				const sm = document.querySelector( '.post-card--sm' );
				const cover = sm.querySelector( '.post-card__cover' ).getBoundingClientRect();
				const body = sm.querySelector( '.post-card__body' ).getBoundingClientRect();
				const f = document.querySelector( '.post-feature' );
				return {
					lg: cols( '.post-cards--lg > li' ),
					sm: cols( '.post-cards--sm > li' ),
					row: cover.right <= body.left + 1 ? Math.round( cover.width ) : 0, // обложка слева от текста — её ширина
					coverFull: Math.abs( cover.height - sm.getBoundingClientRect().height + 2 ) <= 1, // обложка на всю высоту карточки
					feature: f.querySelector( '.post-feature__media' ).getBoundingClientRect().top < f.querySelector( '.post-feature__body' ).getBoundingClientRect().top - 50 ? 'column' : 'row',
					// Правки заказчика: подзаголовок во всю ширину, кнопка — в правом нижнем углу карточки,
					// «Все статьи» — ближе к началу блока.
					lead: Math.round( document.querySelector( '.archive-hero__inner' ).getBoundingClientRect().width - document.querySelector( '.archive-hero__lead' ).getBoundingClientRect().width ),
					btn: ( () => {
						const b = f.querySelector( '.post-feature__btn' ).getBoundingClientRect();
						const body = f.querySelector( '.post-feature__body' );
						const r = body.getBoundingClientRect();
						const cs = getComputedStyle( body );
						return [ Math.round( r.right - parseFloat( cs.paddingRight ) - b.right ), Math.round( r.bottom - parseFloat( cs.paddingBottom ) - b.bottom ) ];
					} )(),
					h2: Math.round( document.querySelector( '#posts-title' ).getBoundingClientRect().top - document.querySelector( '.post-list' ).getBoundingClientRect().top ),
				};
			} );
		}
		check( 'Раскладка: крупные 2 → 1 (уже 760 px), обычные 3 → 2 (уже 1024) → 1 (уже 640); главная статья — обложка слева, уже ~1000 px — сверху',
			2 === layout[ 1280 ].lg && 3 === layout[ 1280 ].sm && 2 === layout[ 1000 ].sm && 1 === layout[ 700 ].lg && 2 === layout[ 700 ].sm && 1 === layout[ 390 ].sm && 'row' === layout[ 1280 ].feature && 'column' === layout[ 390 ].feature,
			JSON.stringify( layout ) );
		check( 'Правки заказчика: подзаголовок во всю ширину блока, «Читать полностью» — в правом нижнем углу главной статьи, «Все статьи» — 40 px от начала блока (на телефоне 32)',
			Object.values( layout ).every( ( l ) => 0 === l.lead && 0 === l.btn[ 0 ] && 0 === l.btn[ 1 ] ) && 40 === layout[ 1280 ].h2 && 32 === layout[ 390 ].h2, JSON.stringify( Object.fromEntries( Object.entries( layout ).map( ( [ w, l ] ) => [ w, [ l.lead, l.btn, l.h2 ] ] ) ) ) );
		check( 'Телефон: обычная карточка — обложка слева (112 px, уже 380 — 88 px) на всю высоту, текст справа',
			112 === layout[ 390 ].row && 88 === layout[ 360 ].row && layout[ 390 ].coverFull && 0 === layout[ 700 ].row, JSON.stringify( [ layout[ 390 ], layout[ 360 ] ] ) );

		// ===== Сверка с макетом =====
		// Обложки в демо — другие файлы, тексты анонсов, даты и число комментариев — из демо: при
		// сверке картинки скрыты, тексты — как в макете.
		const fixHero = ( p ) => p.evaluate( ( [ mock, fn ] ) => {
			const last = new Function( 'el', 'value', fn ); // eslint-disable-line no-new-func
			document.querySelector( '.post-feature__img' ).style.visibility = 'hidden';
			document.querySelector( '.post-feature__excerpt' ).textContent = mock.featured;
			document.querySelector( '.post-feature time' ).textContent = '5 ноября 2026';
			const stats = document.querySelectorAll( '.post-feature .post-stat' );
			last( stats[ 0 ], '9 мин чтения' );
			last( stats[ 2 ], '12' );
			const photo = document.querySelector( '.post-author__photo' );
			if ( 'IMG' === photo.tagName ) {
				const span = document.createElement( 'span' );
				span.className = 'post-author__photo';
				span.textContent = 'ИФ';
				photo.replaceWith( span );
			}
			document.querySelector( '.post-author__role' ).textContent = '[Должность]';
		}, [ MOCK, `const nodes = [ ...el.childNodes ].filter( ( n ) => 3 === n.nodeType && n.textContent.trim() ); nodes[ nodes.length - 1 ].textContent = value;` ] );
		const fixList = ( p ) => p.evaluate( ( [ mock, fn ] ) => {
			const last = new Function( 'el', 'value', fn ); // eslint-disable-line no-new-func
			const style = document.createElement( 'style' );
			style.textContent = '.post-card__img { visibility: hidden; } .post-cards--sm > li:nth-child(n+4) { display: none; } .pager { display: none; }';
			document.head.append( style );
			document.querySelector( '.post-list__page' ).textContent = 'Страница 1 из 9';
			document.querySelectorAll( '.post-card' ).forEach( ( card, i ) => {
				if ( i > 4 ) {
					return;
				}
				card.querySelector( 'time' ).textContent = mock.dates[ i ];
				last( card.querySelectorAll( '.post-stat' )[ 1 ], mock.comments[ i ] );
				if ( mock.excerpts[ i ] ) {
					card.querySelector( '.post-card__excerpt' ).textContent = mock.excerpts[ i ];
				}
			} );
			// У «Как читать отчёт…» в демо рубрика «Отчёты» (из «Нашего блога»), в этом макете — «Технический аудит».
			document.querySelectorAll( '.post-card .post-rubric' )[ 3 ].textContent = 'Технический аудит';
		}, [ MOCK, `const nodes = [ ...el.childNodes ].filter( ( n ) => 3 === n.nodeType && n.textContent.trim() ); nodes[ nodes.length - 1 ].textContent = value;` ] );
		for ( const width of [ 1280, 768, 390 ] ) {
			await compare( page, {
				name: 'hero', width, theme: '.archive-hero', mock: 'main > section',
				before: fixHero,
				mockBefore: async ( p ) => {
					await p.addStyleTag( { content: '.dc-feat-img { visibility: hidden; }' } );
					// Правка заказчика: текст кнопки «Читать полностью».
					await p.evaluate( () => {
						const span = document.querySelector( 'main > section a.dc-card > div:last-child > span:last-child' );
						[ ...span.childNodes ].find( ( n ) => 3 === n.nodeType && n.textContent.trim() ).textContent = 'Читать полностью';
					} );
				},
			} );
			await compare( page, {
				name: 'list', width, theme: '.post-list', mock: 'section[aria-labelledby="all-title"]',
				before: fixList,
				mockBefore: ( p ) => p.addStyleTag( { content: 'section[aria-labelledby="all-title"] img { visibility: hidden; } .dc-grid3 > li:nth-child(n+4), nav.dc-pg { display: none !important; }' } ),
			} );
		}

		// ===== Ширины, доступность, консоль =====
		const overflow = [];
		for ( const w of [ 1440, 1280, 1024, 768, 600, 390, 320, 280 ] ) {
			await page.setViewportSize( { width: w, height: 800 } );
			await page.goto( BLOG, { waitUntil: 'networkidle' } );
			if ( await page.evaluate( () => document.documentElement.scrollWidth > window.innerWidth ) ) {
				overflow.push( w );
			}
		}
		check( 'Ширины 1440–280: без прокрутки вбок', 0 === overflow.length, overflow.join( ', ' ) );
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( BLOG, { waitUntil: 'networkidle' } );
		await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
		const axe = await page.evaluate( async () => ( await window.axe.run( document, { resultTypes: [ 'violations' ] } ) ).violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => v.id ) );
		check( 'axe: нет нарушений critical/serious', 0 === axe.length, axe.join( ', ' ) );
		check( 'Консоль браузера без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Редактор: поле «Подзаголовок» только у страницы блога =====
		const editor = await browser.newPage( { viewport: { width: 1400, height: 1000 } } );
		const editorErrors = collectConsoleErrors( editor );
		await login( editor );
		const fieldOn = async ( slug ) => {
			const id = wp( 'eval', `echo get_page_by_path( '${ slug }' )->ID;` );
			await editor.goto( `${ URL }/wp-admin/post.php?post=${ id }&action=edit` );
			await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/editor' ) && wp.data.select( 'core/editor' ).getCurrentPostId(), null, { timeout: 60000 } );
			await editor.waitForTimeout( 2500 );
			return editor.locator( '.acf-field[data-name="trust_blog_lead"]' ).count();
		};
		const onBlog = await fieldOn( 'blog' );
		const onHome = await fieldOn( 'glavnaya' );
		check( 'Редактор: «Страница блога → Подзаголовок» — у страницы записей, у других страниц нет', 1 === onBlog && 0 === onHome, `${ onBlog } / ${ onHome }` );
		check( 'Редактор без ошибок JavaScript', 0 === editorErrors.length, editorErrors.slice( 0, 3 ).join( ' | ' ) );
		await editor.close();
	} finally {
		setField( 'blog_per_page', saved[ 0 ] || '' );
		setField( 'blog_show_likes', null === saved[ 1 ] ? 1 : saved[ 1 ] );
		setField( 'tpl_blog_new', saved[ 2 ] || '' );
		setField( 'tpl_blog_read', saved[ 3 ] || '' );
		setField( 'tpl_blog_all', saved[ 4 ] || '' );
	}
	checkLog();
	await browser.close();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
