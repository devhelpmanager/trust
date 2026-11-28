/**
 * Тест шага 5.5 — тег блога (tag.php, макет portfolio-blog-404/teg.html).
 *
 * Запуск: node wp-theme/tests/tag.js
 *
 * Демо: тег robots.txt — 4 статьи и 4 ответа на вопросы (в макете 3 ответа: у демо-ответа «Почему
 * Яндекс не индексирует…» тег из макета ответа), описание из макета. Проверяет первый экран (крошки,
 * плашка, H1 «#тег», описание, «N статей и M ответов на вопросы»), фильтр «Все / Статьи / Ответы на
 * вопросы» без перезагрузки (REST, адрес, «Назад»), прямой адрес ?type= и canonical, REST (ошибки),
 * карточки статьи и ответа, боковую колонку (рубрика, похожие теги, «Остался вопрос про …?»), тег
 * только со статьями и только с ответами, пагинацию с фильтром, noindex тега ниже порога, тексты из
 * настроек, CollectionPage + ItemList, раскладку 1280 / 390, ширины 1440–280, совпадение с макетом
 * на 1280 / 768 / 390 px, axe-core, консоль и журнал WordPress.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, mockupUrl, comparePng, settle, isolate, wp, getSettings, setSettings } = require( './lib' );

const TAG = `${ URL }/blog/teg/robots-txt/`;
const SETTINGS = [ 'blog_per_page', 'tpl_tag_label', 'tpl_tag_ask_title' ];
const MOCK = 'portfolio-blog-404/teg.html';

const evalJson = ( php ) => JSON.parse( wp( 'eval', php ) );

/** Состояние страницы тега. */
function state( page ) {
	return page.evaluate( () => {
		const text = ( el ) => ( el ? el.textContent.replace( /\s+/g, ' ' ).trim() : null );
		const side = document.querySelector( '.tag-side' );
		const card = ( el ) => ( {
			type: el.classList.contains( 'material--answer' ) ? 'answer' : 'post',
			href: new URL( el.href ).pathname,
			badge: text( el.querySelector( '.material__badge' ) ),
			rubric: text( el.querySelector( '.material__rubric' ) ),
			icon: !! el.querySelector( '.material__icon' ),
			title: text( el.querySelector( 'h3' ) ),
			excerpt: text( el.querySelector( '.material__text' ) ),
			meta: [ ...el.querySelectorAll( '.material__meta > *' ) ].map( text ),
			img: !! el.querySelector( 'img' ),
		} );
		return {
			crumbs: [ ...document.querySelectorAll( '.archive-hero__crumbs li' ) ].map( text ).filter( ( t ) => '/' !== t ),
			label: text( document.querySelector( '.tag-hero__label' ) ),
			h1: [ ...document.querySelectorAll( 'h1' ) ].map( text ),
			hash: document.querySelector( '.tag-hero__hash' ) ? document.querySelector( '.tag-hero__hash' ).getAttribute( 'aria-hidden' ) : null,
			desc: text( document.querySelector( '.tag-hero__lead' ) ),
			meta: text( document.querySelector( '.tag-hero__meta' ) ),
			listTitle: text( document.getElementById( 'tag-list-title' ) ),
			filterLabel: document.querySelector( '.tag-filter' ) ? document.querySelector( '.tag-filter' ).getAttribute( 'aria-label' ) : null,
			filters: [ ...document.querySelectorAll( '[data-tag-type]' ) ].map( ( a ) => [ text( a.firstChild ), text( a.querySelector( '.tag-filter__count' ) ), a.getAttribute( 'aria-current' ), new URL( a.href ).pathname + new URL( a.href ).search ] ),
			cards: [ ...document.querySelectorAll( '.materials .material' ) ].map( card ),
			empty: text( document.querySelector( '.tag-main .post-list__empty' ) ),
			pager: [ ...document.querySelectorAll( '.tag-main .pager a' ) ].map( ( a ) => new URL( a.href ).pathname + new URL( a.href ).search + new URL( a.href ).hash ),
			side: side ? {
				label: side.getAttribute( 'aria-label' ),
				rubric: side.querySelector( '.tag-side__rubric' ) ? [ text( side.querySelector( '.tag-side__rubric' ) ), new URL( side.querySelector( '.tag-side__rubric' ).href ).pathname, text( side.querySelector( '.tag-side__note' ) ) ] : null,
				labels: [ ...side.querySelectorAll( '.tag-side__label' ) ].map( text ),
				similar: [ ...side.querySelectorAll( '.tag-side__tag' ) ].map( ( a ) => [ text( a ), new URL( a.href ).pathname ] ),
				ask: side.querySelector( '.tag-side__ask' ) ? [ text( side.querySelector( '.tag-side__ask-title' ) ), text( side.querySelector( '.tag-side__ask-text' ) ), text( side.querySelector( '.tag-side__ask-btn' ) ), side.querySelector( '.tag-side__ask-btn' ).href ] : null,
			} : null,
			canonical: document.querySelector( 'link[rel="canonical"]' ) ? document.querySelector( 'link[rel="canonical"]' ).href : null,
			robots: document.querySelector( 'meta[name="robots"]' ) ? document.querySelector( 'meta[name="robots"]' ).content : '',
		};
	} );
}

/**
 * Сравнение снимков темы и макета.
 *
 * @param {import('playwright').Page} page Страница.
 * @param {Object}                    o    name, width, theme, mock, before(page), mockBefore(page).
 */
async function compare( page, o ) {
	const base = path.join( OUT, `tag-${ o.name }-${ o.width }` );
	await page.setViewportSize( { width: o.width, height: 900 } );
	await page.goto( TAG, { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	await o.before( page );
	await isolate( page, o.theme );
	await page.waitForTimeout( 200 );
	await page.locator( o.theme ).first().screenshot( { path: `${ base }-theme.png` } );
	await page.goto( mockupUrl( MOCK ), { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	await o.mockBefore( page );
	await isolate( page, o.mock );
	await page.waitForTimeout( 200 );
	await page.locator( o.mock ).first().screenshot( { path: `${ base }-mockup.png` } );
	const diff = comparePng( `${ base }-mockup.png`, `${ base }-theme.png`, `${ base }-diff.png` );
	check( `Макет ${ MOCK }, ${ o.name }, ${ o.width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
}

( async () => {
	clearLog();
	const browser = await launch();
	const page = await browser.newPage( { viewport: { width: 1280, height: 900 } } );
	const errors = collectConsoleErrors( page );
	const saved = getSettings( SETTINGS );
	const robotsTag = Number( wp( 'eval', "echo (int) get_term_by( 'slug', 'robots-txt', 'post_tag' )->term_id;" ) );
	const temp = { posts: [], terms: [] };
	try {
		const expect = evalJson( `
			$audit = get_term_by( 'slug', 'tehnicheskiy-audit', 'category' );
			echo wp_json_encode( array(
				'audit' => wp_parse_url( get_term_link( $audit ), PHP_URL_PATH ),
				'count' => (int) $audit->count,
				'ask'   => Trust\\Block_Texts::page_url( 'ask_page', 'questions' ),
				'read'  => Trust\\Blog::reading_time( get_page_by_path( 'robots-txt-proverka', OBJECT, 'post' ) ),
			) );
		` );

		// ===== Первый экран =====
		await page.goto( TAG, { waitUntil: 'networkidle' } );
		const s = await state( page );
		check( 'Первый экран: крошки «Главная / Блог / #robots.txt», плашка «Тег», один H1 «#robots.txt» (# скрыт от скринридеров), описание тега, «4 статьи и 4 ответа на вопросы»',
			JSON.stringify( s.crumbs ) === JSON.stringify( [ 'Главная', 'Блог', '#robots.txt' ] ) && 'Тег' === s.label && JSON.stringify( s.h1 ) === JSON.stringify( [ '#robots.txt' ] ) && 'true' === s.hash &&
				/^Всё о файле robots\.txt/.test( s.desc ) && '4 статьи и 4 ответа на вопросы' === s.meta,
			JSON.stringify( [ s.crumbs, s.label, s.h1, s.desc, s.meta ] ) );

		// ===== Материалы =====
		check( 'Фильтр: «Все 8» (выбран), «Статьи 4» (?type=posts), «Ответы на вопросы 4» (?type=answers); скрытый H2 «Материалы с тегом robots.txt»',
			'Тип материалов' === s.filterLabel && 'Материалы с тегом robots.txt' === s.listTitle &&
				JSON.stringify( s.filters ) === JSON.stringify( [ [ 'Все', '8', 'true', '/blog/teg/robots-txt/' ], [ 'Статьи', '4', null, '/blog/teg/robots-txt/?type=posts' ], [ 'Ответы на вопросы', '4', null, '/blog/teg/robots-txt/?type=answers' ] ] ),
			JSON.stringify( s.filters ) );
		const types = s.cards.map( ( c ) => c.type );
		check( 'Список: статьи и ответы вместе, новые сверху (8 карточек)',
			8 === s.cards.length && JSON.stringify( types ) === JSON.stringify( [ 'post', 'post', 'post', 'answer', 'answer', 'post', 'answer', 'answer' ] ) && '/blog/robots-txt-proverka/' === s.cards[ 0 ].href,
			JSON.stringify( s.cards.map( ( c ) => [ c.type, c.title ] ) ) );
		const post = s.cards[ 0 ];
		const answer = s.cards.find( ( c ) => 'answer' === c.type );
		check( 'Карточка статьи: обложка, плашка «Статья» и рубрика, H3, анонс, дата и «N мин чтения»',
			post.img && 'Статья' === post.badge && 'Технический аудит' === post.rubric && post.title.startsWith( 'Robots.txt: как проверить файл' ) && post.excerpt.startsWith( 'Одна неверная строка' ) &&
				2 === post.meta.length && `${ expect.read } мин чтения` === post.meta[ 1 ],
			JSON.stringify( post ) );
		check( 'Карточка ответа: знак «?», плашка «Ответ SEO-специалиста», вопрос (H3), начало ответа, дата ответа и «Отвечает Имя»; ссылка на ответ',
			answer.icon && 'Ответ SEO-специалиста' === answer.badge && ! answer.img && 'Почему Яндекс не индексирует новые страницы каталога?' === answer.title && answer.excerpt.startsWith( 'Начните с «Исключённых страниц»' ) &&
				JSON.stringify( answer.meta ) === JSON.stringify( [ '6 ноября 2026', 'Отвечает [Имя Фамилия]' ] ) && answer.href.startsWith( '/seo/voprosy/' ),
			JSON.stringify( answer ) );

		// ===== Фильтр без перезагрузки =====
		await page.evaluate( () => {
			window.trustNoReload = true;
		} );
		const restCalls = [];
		page.on( 'request', ( r ) => r.url().includes( '/trust/v1/tags/' ) && restCalls.push( r.url() ) );
		await page.click( '[data-tag-type="posts"]' );
		await page.waitForFunction( () => location.search.includes( 'type=posts' ) && ! document.querySelector( '[data-tag-list]' ).hasAttribute( 'aria-busy' ) );
		const onlyPosts = await state( page );
		await page.click( '[data-tag-type="answers"]' );
		await page.waitForFunction( () => location.search.includes( 'type=answers' ) && ! document.querySelector( '[data-tag-list]' ).hasAttribute( 'aria-busy' ) );
		const onlyAnswers = await state( page );
		const kept = await page.evaluate( () => true === window.trustNoReload );
		check( '«Статьи» и «Ответы на вопросы»: список меняется без перезагрузки (REST), адрес — ?type=…, выбранный пункт отмечен',
			kept && 2 === restCalls.length && /tags\/\d+\?type=posts&page=1$/.test( restCalls[ 0 ] ) && 4 === onlyPosts.cards.length && onlyPosts.cards.every( ( c ) => 'post' === c.type ) &&
				'true' === onlyPosts.filters[ 1 ][ 2 ] && 4 === onlyAnswers.cards.length && onlyAnswers.cards.every( ( c ) => 'answer' === c.type ) && 'true' === onlyAnswers.filters[ 2 ][ 2 ] && null === onlyAnswers.filters[ 0 ][ 2 ],
			JSON.stringify( [ kept, restCalls, onlyPosts.cards.length, onlyAnswers.cards.length ] ) );
		await page.goBack();
		await page.waitForFunction( () => location.search.includes( 'type=posts' ) && ! document.querySelector( '[data-tag-list]' ).hasAttribute( 'aria-busy' ) );
		const back = await state( page );
		check( '«Назад» в браузере возвращает прежний фильтр без перезагрузки', back.cards.every( ( c ) => 'post' === c.type ) && 'true' === back.filters[ 1 ][ 2 ] && await page.evaluate( () => true === window.trustNoReload ), JSON.stringify( back.filters ) );
		await page.goto( `${ TAG }?type=answers`, { waitUntil: 'networkidle' } );
		const direct = await state( page );
		check( 'Прямой адрес ?type=answers: только ответы, выбран «Ответы на вопросы»; canonical — тег без параметра',
			4 === direct.cards.length && direct.cards.every( ( c ) => 'answer' === c.type ) && 'true' === direct.filters[ 2 ][ 2 ] && TAG === direct.canonical,
			JSON.stringify( [ direct.cards.length, direct.canonical ] ) );

		// ===== REST =====
		const rest = async ( q ) => {
			const r = await fetch( `${ URL }/wp-json/trust/v1/tags/${ q }` );
			return [ r.status, await r.json() ];
		};
		const [ okStatus, ok ] = await rest( `${ robotsTag }?type=posts` );
		const [ badType ] = await rest( `${ robotsTag }?type=video` );
		const [ noTerm ] = await rest( '999999' );
		const [ noPage ] = await rest( `${ robotsTag }?page=2` );
		check( 'REST /trust/v1/tags/{id}: html, pages, url (?type=posts); неверный фильтр — 400, нет тега — 404, нет страницы — 404',
			200 === okStatus && `${ TAG }?type=posts` === ok.url && 1 === ok.pages && ok.html.includes( 'material--post' ) && ! ok.html.includes( 'material--answer' ) && 400 === badType && 404 === noTerm && 404 === noPage,
			JSON.stringify( [ okStatus, ok.url, ok.pages, badType, noTerm, noPage ] ) );

		// ===== Боковая колонка =====
		check( 'Сбоку: «Рубрика» — рубрика, где больше всего статей тега («Технический аудит», ссылка, число статей рубрики)',
			s.side && 'Ещё по теме' === s.side.label && 'Рубрика' === s.side.labels[ 0 ] && JSON.stringify( s.side.rubric ) === JSON.stringify( [ 'Технический аудит', expect.audit, `${ expect.count } статей` ] ),
			JSON.stringify( s.side ) );
		check( '«Похожие теги» — теги, которые стоят вместе с этим (сначала частые), со ссылками; сам тег не повторяется',
			'Похожие теги' === s.side.labels[ 1 ] && s.side.similar.length >= 5 && '#дубли' === s.side.similar[ 0 ][ 0 ] && s.side.similar[ 0 ][ 1 ].startsWith( '/blog/teg/' ) && ! s.side.similar.some( ( t ) => '#robots.txt' === t[ 0 ] ),
			JSON.stringify( s.side.similar ) );
		check( '«Остался вопрос про robots.txt?» — текст и «Задать вопрос» на страницу вопросов',
			s.side.ask && 'Остался вопрос про robots.txt?' === s.side.ask[ 0 ] && /^Задайте его SEO-специалисту/.test( s.side.ask[ 1 ] ) && 'Задать вопрос' === s.side.ask[ 2 ] && expect.ask === s.side.ask[ 3 ],
			JSON.stringify( s.side.ask ) );

		// ===== Микроразметка и индексация =====
		await page.goto( TAG, { waitUntil: 'networkidle' } );
		const ld = await page.evaluate( () => {
			const graph = [ ...document.querySelectorAll( 'script[type="application/ld+json"]' ) ].flatMap( ( x ) => JSON.parse( x.textContent )[ '@graph' ] || [] );
			const list = graph.find( ( n ) => 'ItemList' === n[ '@type' ] );
			const pg = graph.find( ( n ) => 'CollectionPage' === n[ '@type' ] );
			return [ !! pg, list && list.numberOfItems, pg && list && pg.mainEntity && pg.mainEntity[ '@id' ] === list[ '@id' ] ];
		} );
		check( 'Микроразметка: CollectionPage, ItemList показанных материалов (8), главное содержимое страницы', JSON.stringify( ld ) === JSON.stringify( [ true, 8, true ] ), JSON.stringify( ld ) );
		await page.goto( `${ URL }/blog/teg/google-search-console/`, { waitUntil: 'networkidle' } );
		const gsc = await state( page );
		check( 'Тег только со статьями (Google Search Console, 1 статья): без фильтра, «1 статья»; материалов меньше порога — noindex, follow',
			0 === gsc.filters.length && '1 статья' === gsc.meta && 1 === gsc.cards.length && /noindex/.test( gsc.robots ) && /follow/.test( gsc.robots ) && null !== gsc.side.rubric,
			JSON.stringify( [ gsc.filters, gsc.meta, gsc.cards.length, gsc.robots ] ) );

		// ===== Тексты из настроек =====
		setSettings( { tpl_tag_label: 'Метка', tpl_tag_ask_title: 'Вопрос про {tag}?' } );
		await page.goto( TAG, { waitUntil: 'networkidle' } );
		const texts = await state( page );
		setSettings( { tpl_tag_label: '', tpl_tag_ask_title: '' } );
		check( '«Тексты шаблонов»: плашка и заголовок «Остался вопрос…» ({tag} — название тега)', 'Метка' === texts.label && 'Вопрос про robots.txt?' === texts.side.ask[ 0 ], JSON.stringify( [ texts.label, texts.side.ask[ 0 ] ] ) );

		// ===== Временный тег: пагинация с фильтром, тег только с ответами =====
		const made = evalJson( `
			$tag = wp_insert_term( 'тестовый тег', 'post_tag', array( 'slug' => 'testovyy-teg-5-5' ) );
			$only = wp_insert_term( 'только ответы', 'post_tag', array( 'slug' => 'tolko-otvety-5-5' ) );
			$posts = array();
			for ( $i = 1; $i <= 9; $i++ ) {
				$posts[] = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Тестовая статья тега ' . $i, 'post_name' => 'test-tag-5-5-' . $i, 'post_content' => 'Текст.', 'post_date' => gmdate( 'Y-m-d H:i:s', strtotime( '2020-01-01' ) - $i * DAY_IN_SECONDS ), 'tags_input' => array( 'тестовый тег' ) ) );
			}
			$posts[] = wp_insert_post( array( 'post_type' => 'question', 'post_status' => 'publish', 'post_title' => 'Тестовый вопрос тега', 'post_name' => 'test-tag-5-5-q', 'post_content' => '<!-- wp:paragraph --><p>Ответ.</p><!-- /wp:paragraph -->', 'post_date' => '2019-01-01 10:00:00' ) );
			wp_set_object_terms( end( $posts ), array( $tag['term_id'], $only['term_id'] ), 'post_tag' );
			echo wp_json_encode( array( 'tag' => $tag['term_id'], 'only' => $only['term_id'], 'posts' => $posts ) );
		` );
		temp.posts = made.posts;
		temp.terms = [ made.tag, made.only ];
		setSettings( { blog_per_page: 6 } );
		const TEST = `${ URL }/blog/teg/testovyy-teg-5-5/`;
		await page.goto( `${ TEST }?type=posts`, { waitUntil: 'networkidle' } );
		const p1 = await state( page );
		await page.goto( `${ TEST }page/2/?type=posts`, { waitUntil: 'networkidle' } );
		const p2 = await state( page );
		check( 'Пагинация: статей на странице — из «Настроек сайта → Блог» (6); ссылки страниц сохраняют ?type=posts; на странице 2 — остальные 3 статьи',
			6 === p1.cards.length && p1.pager.includes( '/blog/teg/testovyy-teg-5-5/page/2/?type=posts#materials' ) && 3 === p2.cards.length && p2.cards.every( ( c ) => 'post' === c.type ) && 'Все' === p1.filters[ 0 ][ 0 ] && '10' === p1.filters[ 0 ][ 1 ],
			JSON.stringify( [ p1.cards.length, p1.pager, p2.cards.length, p1.filters ] ) );
		await page.evaluate( () => {
			window.trustNoReload = true;
		} );
		await Promise.all( [ page.waitForURL( /testovyy-teg-5-5\/\?type=answers(#materials)?$/ ), page.click( '[data-tag-type="answers"]' ) ] );
		await page.waitForLoadState( 'networkidle' );
		const reloaded = await page.evaluate( () => undefined === window.trustNoReload );
		const answersOnly = await state( page );
		check( 'Со страницы 2 фильтр — обычный переход на первую страницу', reloaded && 1 === answersOnly.cards.length && 'answer' === answersOnly.cards[ 0 ].type, JSON.stringify( [ reloaded, answersOnly.cards.length ] ) );
		await page.goto( `${ URL }/blog/teg/tolko-otvety-5-5/`, { waitUntil: 'networkidle' } );
		const only = await state( page );
		check( 'Тег только с ответами: без фильтра, «1 ответ на вопрос», без описания и без «Рубрики» сбоку',
			0 === only.filters.length && '1 ответ на вопрос' === only.meta && null === only.desc && null === only.side.rubric && 1 === only.cards.length,
			JSON.stringify( [ only.filters, only.meta, only.desc, only.side.rubric ] ) );
		setSettings( { blog_per_page: saved.blog_per_page || '' } );
		wp( 'eval', `foreach ( array( ${ temp.posts.join( ',' ) } ) as $id ) { wp_delete_post( $id, true ); } foreach ( array( ${ temp.terms.join( ',' ) } ) as $t ) { wp_delete_term( $t, 'post_tag' ); }` );
		temp.posts = [];
		temp.terms = [];

		// ===== Раскладка =====
		const layout = {};
		for ( const width of [ 1280, 390 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( TAG, { waitUntil: 'networkidle' } );
			layout[ width ] = await page.evaluate( () => {
				const r = ( sel ) => document.querySelector( sel ).getBoundingClientRect();
				const cover = r( '.material--post .material__cover' );
				const body = r( '.material--post .material__body' );
				const icon = document.querySelector( '.material__icon' );
				return {
					side: r( '.tag-side' ).left >= r( '.tag-main' ).right ? 'right' : ( r( '.tag-side' ).top >= r( '.tag-main' ).bottom ? 'below' : 'other' ),
					sticky: getComputedStyle( document.querySelector( '.tag-side' ) ).position,
					cover: cover.right <= body.left + 1 ? Math.round( cover.width ) : ( cover.bottom <= body.top + 1 ? 'top' : 'other' ),
					coverFull: Math.abs( cover.height - r( '.material--post' ).height + 2 ) <= 1,
					icon: null !== icon.offsetParent,
					full: Math.round( r( '.tag-hero__inner' ).width - r( '.tag-hero__lead' ).width ),
					overflow: document.documentElement.scrollWidth > innerWidth,
				};
			} );
		}
		check( 'Компьютер 1280: боковая колонка справа и едет вместе со страницей; обложка статьи слева (240 px) на всю высоту карточки; знак «?» у ответа; описание во всю ширину',
			'right' === layout[ 1280 ].side && 'sticky' === layout[ 1280 ].sticky && 240 === layout[ 1280 ].cover && layout[ 1280 ].coverFull && layout[ 1280 ].icon && 0 === layout[ 1280 ].full,
			JSON.stringify( layout[ 1280 ] ) );
		check( 'Телефон 390: боковая колонка под материалами, обложка сверху, без знака «?» (как в макете); без прокрутки вбок',
			'below' === layout[ 390 ].side && 'static' === layout[ 390 ].sticky && 'top' === layout[ 390 ].cover && ! layout[ 390 ].icon && 0 === layout[ 390 ].full && ! layout[ 390 ].overflow,
			JSON.stringify( layout[ 390 ] ) );

		// ===== Сверка с макетом =====
		// В демо у тега 4 ответа (в макете 3), другие даты, анонсы, время чтения и похожие теги, у
		// рубрики — число статей: при сверке — как в макете (порядок карточек, тексты, обложки скрыты).
		await page.goto( mockupUrl( MOCK ), { waitUntil: 'networkidle' } );
		await settle( page );
		const mock = await page.evaluate( () => {
			const text = ( el ) => ( el ? el.textContent.replace( /\s+/g, ' ' ).trim() : '' );
			return {
				items: [ ...document.querySelectorAll( '.dc-tag-main ul > li' ) ].map( ( li ) => ( {
					title: text( li.querySelector( 'h3' ) ),
					text: text( li.querySelector( 'p' ) ),
					meta: [ ...li.querySelectorAll( 'h3 ~ span:last-child > *, h3 ~ span:last-of-type > *' ) ].map( text ),
					rubric: text( li.querySelector( '.dc-item-body > span:first-child > span:last-child' ) ),
				} ) ),
				similar: [ ...document.querySelectorAll( '.dc-tag-side .dc-tagl' ) ].map( text ),
				note: text( document.querySelector( '.dc-tag-side .dc-side-card span:last-child' ) ),
			};
		} );
		const fixHero = ( p ) => p.evaluate( () => {
			document.querySelector( '.tag-hero__meta' ).textContent = '4 статьи и 3 ответа на вопросы';
		} );
		const fixBody = ( p ) => p.evaluate( ( m ) => {
			const style = document.createElement( 'style' );
			// Колонка «прилипает» под закреплённой шапкой; при сверке шапки нет — без прилипания.
			style.textContent = '.material__img { visibility: hidden; } .material__cover { background: none !important; } .tag-side { position: static !important; }';
			document.head.append( style );
			const counts = document.querySelectorAll( '.tag-filter__count' );
			counts[ 0 ].textContent = '7';
			counts[ 2 ].textContent = '3';
			const list = document.querySelector( '.materials' );
			const items = [ ...list.children ];
			const byTitle = ( t ) => items.find( ( li ) => li.querySelector( 'h3' ).textContent.trim() === t );
			items.forEach( ( li ) => li.remove() );
			m.items.forEach( ( it ) => {
				const li = byTitle( it.title );
				if ( ! li ) {
					return;
				}
				li.querySelector( '.material__text' ).textContent = it.text;
				const meta = li.querySelectorAll( '.material__meta > *' );
				it.meta.forEach( ( v, i ) => meta[ i ] && ( meta[ i ].textContent = v ) );
				if ( it.rubric && li.querySelector( '.material__rubric' ) ) {
					li.querySelector( '.material__rubric' ).textContent = it.rubric;
				}
				list.append( li );
			} );
			document.querySelector( '.tag-side__note' ).textContent = m.note;
			const tags = [ ...document.querySelectorAll( '.tag-side__tags > li' ) ];
			tags.forEach( ( li, i ) => ( i < m.similar.length ? ( li.firstElementChild.textContent = m.similar[ i ] ) : li.remove() ) );
		}, mock );
		for ( const width of [ 1280, 768, 390 ] ) {
			await compare( page, {
				name: 'hero', width, theme: '.tag-hero', mock: 'main > section',
				before: fixHero,
				mockBefore: async () => {},
			} );
			await compare( page, {
				name: 'body', width, theme: '.tag-body', mock: 'main > div',
				before: fixBody,
				mockBefore: ( p ) => p.addStyleTag( { content: '.dc-tag-main img { visibility: hidden; }' } ),
			} );
		}

		// ===== Ширины, доступность, консоль =====
		const overflow = [];
		for ( const w of [ 1440, 1280, 1024, 768, 600, 390, 320, 280 ] ) {
			await page.setViewportSize( { width: w, height: 800 } );
			await page.goto( TAG, { waitUntil: 'networkidle' } );
			if ( await page.evaluate( () => document.documentElement.scrollWidth > window.innerWidth ) ) {
				overflow.push( w );
			}
		}
		check( 'Ширины 1440–280: без прокрутки вбок', 0 === overflow.length, overflow.join( ', ' ) );
		for ( const width of [ 1280, 390 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( TAG, { waitUntil: 'networkidle' } );
			await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
			const axe = await page.evaluate( async () => ( await window.axe.run( document, { resultTypes: [ 'violations' ] } ) ).violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id }: ${ v.nodes.map( ( n ) => n.target.join( ' ' ) ).slice( 0, 3 ).join( ', ' ) }` ) );
			check( `axe, ${ width }px: нет нарушений critical/serious`, 0 === axe.length, axe.join( ' | ' ) );
		}
		check( 'Консоль браузера без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );
	} finally {
		if ( temp.posts.length || temp.terms.length ) {
			wp( 'eval', `foreach ( array( ${ temp.posts.join( ',' ) } ) as $id ) { wp_delete_post( $id, true ); } foreach ( array( ${ temp.terms.join( ',' ) } ) as $t ) { wp_delete_term( $t, 'post_tag' ); }` );
		}
		setSettings( Object.fromEntries( SETTINGS.map( ( n ) => [ n, saved[ n ] || '' ] ) ) );
	}
	checkLog();
	await browser.close();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
