/**
 * Тест шага 5.4 — рубрика блога (category.php, макет portfolio-blog-404/rubrika.html).
 *
 * Запуск: node wp-theme/tests/rubric.js
 *
 * Демо: рубрика «Технический аудит» — 5 статей (robots.txt — главная, ещё 4 в списке), ведущий
 * рубрики, описание, «О рубрике». Проверяет первый экран (крошки, плашка, H1, описание, «N статей ·
 * обновлено …», «Ведёт рубрику»), полосу рубрик (порядок, числа, текущая, без рубрики по умолчанию),
 * теги рубрики, главную статью (теги, без автора, в списке не повторяется, флажок «Главная статья»),
 * «Статьи рубрики» (карточки: теги, H3, анонс, дата, время чтения, лайки, комментарии), сортировку
 * «Новые / Популярные» без перезагрузки (REST, адрес, «Назад»), прямой адрес ?sort=popular и canonical,
 * REST (ошибки), пагинацию с сортировкой (временная рубрика), пустую рубрику, «Не нашли ответ?»,
 * «О рубрике», тексты из настроек, CollectionPage + ItemList, раскладку 1280 / 390, ширины 1440–280,
 * совпадение с макетом на 1280 / 768 / 390 px, axe-core, консоль и журнал WordPress.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, mockupUrl, comparePng, settle, isolate, wp, getSettings, setSettings } = require( './lib' );

const RUBRIC = `${ URL }/blog/rubrika/tehnicheskiy-audit/`;
const SETTINGS = [ 'blog_per_page', 'tpl_rubric_list', 'tpl_rubric_about', 'teaser_title' ];
const MOCK = 'portfolio-blog-404/rubrika.html';

const evalJson = ( php ) => JSON.parse( wp( 'eval', php ) );
const postId = ( slug ) => Number( wp( 'eval', `echo (int) get_page_by_path( '${ slug }', OBJECT, 'post' )->ID;` ) );

/** Состояние страницы рубрики. */
function state( page ) {
	return page.evaluate( () => {
		const text = ( el ) => ( el ? el.textContent.replace( /\s+/g, ' ' ).trim() : null );
		const lead = document.querySelector( '.rubric-lead' );
		const feature = document.querySelector( '.post-feature' );
		const teaser = document.querySelector( '.ask-teaser' );
		const about = document.querySelector( '.rubric-about' );
		const stat = ( card, i ) => {
			const el = card.querySelectorAll( '.post-stat' )[ i ];
			return el ? [ ...el.childNodes ].filter( ( n ) => 3 === n.nodeType ).map( ( n ) => n.textContent.trim() ).join( '' ) : null;
		};
		return {
			crumbs: [ ...document.querySelectorAll( '.archive-hero__crumbs li' ) ].map( text ).filter( ( t ) => '/' !== t ),
			label: text( document.querySelector( '.rubric-hero__label' ) ),
			h1: [ ...document.querySelectorAll( 'h1' ) ].map( text ),
			desc: text( document.querySelector( '.rubric-hero .archive-hero__lead' ) ),
			meta: text( document.querySelector( '.rubric-hero__meta' ) ),
			lead: lead ? {
				tag: lead.tagName,
				href: lead.getAttribute( 'href' ),
				label: text( lead.querySelector( '.rubric-lead__label' ) ),
				name: text( lead.querySelector( '.rubric-lead__name' ) ),
				role: text( lead.querySelector( '.rubric-lead__role' ) ),
			} : null,
			strip: [ ...document.querySelectorAll( '.rubric-strip__item' ) ].map( ( a ) => [ text( a.firstChild ), text( a.querySelector( '.rubric-strip__count' ) ), a.getAttribute( 'aria-current' ), new URL( a.href ).pathname ] ),
			stripLabel: document.querySelector( '.rubric-strip' ) ? document.querySelector( '.rubric-strip' ).getAttribute( 'aria-label' ) : null,
			tagsLabel: text( document.querySelector( '.rubric-tags__label' ) ),
			tags: [ ...document.querySelectorAll( '.rubric-tags__tag' ) ].map( ( a ) => [ text( a ), new URL( a.href ).pathname ] ),
			feature: feature ? {
				href: new URL( feature.href ).pathname,
				badge: text( feature.querySelector( '.post-feature__badge' ) ),
				tags: [ ...feature.querySelectorAll( '.post-tags__tag' ) ].map( text ),
				rubric: !! feature.querySelector( '.post-rubric' ),
				author: !! feature.querySelector( '.post-author' ),
				title: text( feature.querySelector( 'h2' ) ),
				btn: text( feature.querySelector( '.post-feature__btn' ) ),
			} : null,
			listTitle: text( document.getElementById( 'posts-title' ) ),
			sortLabel: document.querySelector( '.rubric-sort' ) ? document.querySelector( '.rubric-sort' ).getAttribute( 'aria-label' ) : null,
			sorts: [ ...document.querySelectorAll( '[data-rubric-sort]' ) ].map( ( a ) => [ text( a ), a.getAttribute( 'aria-current' ), new URL( a.href ).pathname + new URL( a.href ).search ] ),
			cards: [ ...document.querySelectorAll( '.post-cards--rubric .post-card' ) ].map( ( card ) => ( {
				href: new URL( card.href ).pathname,
				tags: [ ...card.querySelectorAll( '.post-tags__tag' ) ].map( text ),
				rubric: !! card.querySelector( '.post-rubric' ),
				title: text( card.querySelector( 'h3' ) ),
				excerpt: text( card.querySelector( '.post-card__excerpt' ) ),
				date: text( card.querySelector( 'time' ) ),
				read: text( card.querySelector( '.post-card__read' ) ),
				likes: stat( card, 0 ),
				comments: stat( card, 1 ),
			} ) ),
			empty: text( document.querySelector( '.post-list__empty' ) ),
			pager: [ ...document.querySelectorAll( '.pager a' ) ].map( ( a ) => new URL( a.href ).pathname + new URL( a.href ).search + new URL( a.href ).hash ),
			teaser: teaser ? {
				title: text( teaser.querySelector( '.ask-teaser__title' ) ),
				text: text( teaser.querySelector( '.ask-teaser__text' ) ),
				btn: text( teaser.querySelector( '.ask-teaser__btn' ) ),
				href: teaser.querySelector( '.ask-teaser__btn' ).href,
			} : null,
			about: about ? {
				title: text( about.querySelector( 'summary h2' ) ),
				open: about.querySelector( 'details' ).open,
				text: text( about.querySelector( '.rubric-about__text' ) ),
				link: about.querySelector( '.rubric-about__text a' ) ? new URL( about.querySelector( '.rubric-about__text a' ).href ).pathname : null,
			} : null,
			canonical: document.querySelector( 'link[rel="canonical"]' ) ? document.querySelector( 'link[rel="canonical"]' ).href : null,
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
	const base = path.join( OUT, `rubric-${ o.name }-${ o.width }` );
	await page.setViewportSize( { width: o.width, height: 900 } );
	await page.goto( RUBRIC, { waitUntil: 'networkidle' } );
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
	const audit = Number( wp( 'eval', "echo (int) get_term_by( 'slug', 'tehnicheskiy-audit', 'category' )->term_id;" ) );
	const temp = { posts: [], terms: [] };
	try {
		// ===== Первый экран =====
		const robots = postId( 'robots-txt-proverka' );
		const expect = evalJson( `
			$robots = get_post( ${ robots } );
			$member = get_post( (int) get_field( 'trust_rubric_lead', get_term( ${ audit } ) ) );
			echo wp_json_encode( array(
				'updated' => Trust\\Blog::date( $robots ),
				'member'  => get_the_title( $member ),
				'role'    => get_post_meta( $member->ID, 'trust_position', true ),
				'url'     => is_post_type_viewable( 'team' ) ? wp_parse_url( get_permalink( $member ), PHP_URL_PATH ) : null,
				'all'     => (int) wp_count_posts( 'post' )->publish,
				'blog'    => wp_parse_url( Trust\\Blog::index_url(), PHP_URL_PATH ),
				'ask'     => Trust\\Block_Texts::page_url( 'ask_page', 'questions' ),
				'robots'  => wp_parse_url( get_term_link( get_term_by( 'name', 'robots.txt', 'post_tag' ) ), PHP_URL_PATH ),
				'reads'   => array_map( static fn( $s ) => Trust\\Blog::reading_time( get_page_by_path( $s, OBJECT, 'post' ) ), array( 'kak-chitat-otchet-audita', 'karta-sayta-sitemap', 'dubli-stranic', 'kody-otveta-servera' ) ),
			) );
		` );
		await page.goto( RUBRIC, { waitUntil: 'networkidle' } );
		const s = await state( page );
		check( 'Первый экран: крошки «Главная / Блог / Технический аудит», плашка «Рубрика», H1 (один), описание рубрики, «5 статей · обновлено <дата самой новой>»',
			JSON.stringify( s.crumbs ) === JSON.stringify( [ 'Главная', 'Блог', 'Технический аудит' ] ) && 'Рубрика' === s.label && JSON.stringify( s.h1 ) === JSON.stringify( [ 'Технический аудит' ] ) &&
				/^Как найти и исправить технические ошибки сайта/.test( s.desc ) && `5 статей · обновлено ${ expect.updated }` === s.meta,
			JSON.stringify( [ s.crumbs, s.label, s.h1, s.desc, s.meta ] ) );
		check( '«Ведёт рубрику»: сотрудник из поля рубрики — имя, должность; ссылка на его страницу (если страницы сотрудников открыты)',
			s.lead && 'Ведёт рубрику' === s.lead.label && expect.member === s.lead.name && expect.role === s.lead.role && ( expect.url ? 'A' === s.lead.tag && new globalThis.URL( s.lead.href ).pathname === expect.url : 'DIV' === s.lead.tag ),
			JSON.stringify( [ s.lead, expect ] ) );
		const names = s.strip.map( ( i ) => i[ 0 ] );
		check( 'Полоса рубрик: «Все статьи 21» → блог, дальше непустые рубрики в порядке создания с числом статей; текущая — aria-current; без «Без рубрики»',
			JSON.stringify( names ) === JSON.stringify( [ 'Все статьи', 'SEO', 'Технический аудит', 'Индексация', 'GEO-продвижение', 'Сайты', 'Контекстная реклама', 'AI', 'Отчёты' ] ) &&
				String( expect.all ) === s.strip[ 0 ][ 1 ] && expect.blog === s.strip[ 0 ][ 3 ] && JSON.stringify( s.strip.map( ( i ) => i[ 1 ] ).slice( 1 ) ) === JSON.stringify( [ '5', '5', '1', '2', '3', '4', '1', '1' ] ) &&
				'page' === s.strip[ 2 ][ 2 ] && 1 === s.strip.filter( ( i ) => i[ 2 ] ).length && 'Рубрики блога' === s.stripLabel,
			JSON.stringify( s.strip ) );
		check( 'Теги рубрики: «Теги рубрики:», теги статей рубрики со ссылками — сначала частые (robots.txt, Вебмастер, индексация — по 2 статьи), всего 9',
			'Теги рубрики:' === s.tagsLabel && 9 === s.tags.length && JSON.stringify( s.tags.slice( 0, 3 ).map( ( t ) => t[ 0 ] ) ) === JSON.stringify( [ '#robots.txt', '#Вебмастер', '#индексация' ] ) && expect.robots === s.tags[ 0 ][ 1 ],
			JSON.stringify( s.tags ) );
		check( 'Главная статья рубрики — самая новая (robots.txt): «Новая статья», два тега вместо рубрики, без автора, «Читать полностью»; в списке не повторяется',
			s.feature && '/blog/robots-txt-proverka/' === s.feature.href && 'Новая статья' === s.feature.badge && 2 === s.feature.tags.length && ! s.feature.rubric && ! s.feature.author &&
				/^Читать полностью/.test( s.feature.btn ) && ! s.cards.some( ( c ) => c.href === s.feature.href ),
			JSON.stringify( s.feature ) );

		// ===== Статьи рубрики =====
		const NEW = [ 'Как читать отчёт по техническому аудиту', 'Карта сайта sitemap.xml: что в неё включать', 'Дубли страниц: где они появляются и как их найти', 'Коды ответа сервера: 301, 302, 404 и 410 простыми словами' ];
		const POPULAR = [ NEW[ 3 ], NEW[ 2 ], NEW[ 1 ], NEW[ 0 ] ];
		check( '«Статьи рубрики»: H2, сортировка «Новые» (нажата) / «Популярные» (?sort=popular); 4 карточки — новые сверху',
			'Статьи рубрики' === s.listTitle && 'Сортировка' === s.sortLabel && JSON.stringify( s.sorts ) === JSON.stringify( [ [ 'Новые', 'true', '/blog/rubrika/tehnicheskiy-audit/' ], [ 'Популярные', null, '/blog/rubrika/tehnicheskiy-audit/?sort=popular' ] ] ) &&
				JSON.stringify( s.cards.map( ( c ) => c.title ) ) === JSON.stringify( NEW ),
			JSON.stringify( [ s.listTitle, s.sorts, s.cards.map( ( c ) => c.title ) ] ) );
		const c0 = s.cards[ 0 ];
		check( 'Карточка: теги (сначала редкие — «#отчёт #Вебмастер»), без плашки рубрики, H3, анонс, дата, «N мин чтения», лайки, комментарии',
			JSON.stringify( c0.tags ) === JSON.stringify( [ '#отчёт', '#Вебмастер' ] ) && JSON.stringify( s.cards[ 1 ].tags ) === JSON.stringify( [ '#sitemap', '#индексация' ] ) && ! c0.rubric &&
				!! c0.excerpt && !! c0.date && JSON.stringify( s.cards.map( ( c ) => c.read ) ) === JSON.stringify( expect.reads.map( ( n ) => `${ n } мин чтения` ) ) && '39' === c0.likes && null !== c0.comments,
			JSON.stringify( s.cards ) );
		check( 'Одна страница — пагинации нет', 0 === s.pager.length, JSON.stringify( s.pager ) );

		// ===== Сортировка без перезагрузки =====
		await page.evaluate( () => {
			window.trustNoReload = true;
		} );
		const restCalls = [];
		page.on( 'request', ( r ) => r.url().includes( '/trust/v1/rubrics/' ) && restCalls.push( r.url() ) );
		await page.click( '[data-rubric-sort="popular"]' );
		await page.waitForFunction( () => location.search.includes( 'sort=popular' ) && ! document.querySelector( '[data-rubric-list]' ).hasAttribute( 'aria-busy' ) );
		const pop = await state( page );
		const kept = await page.evaluate( () => true === window.trustNoReload );
		check( '«Популярные»: список меняется без перезагрузки (REST), по лайкам (53, 47, 45, 39); адрес — ?sort=popular, нажата «Популярные», главная статья на месте',
			kept && 1 === restCalls.length && /rubrics\/\d+\?sort=popular&page=1$/.test( restCalls[ 0 ] ) && JSON.stringify( pop.cards.map( ( c ) => c.title ) ) === JSON.stringify( POPULAR ) &&
				JSON.stringify( pop.cards.map( ( c ) => c.likes ) ) === JSON.stringify( [ '53', '47', '45', '39' ] ) && null === pop.sorts[ 0 ][ 1 ] && 'true' === pop.sorts[ 1 ][ 1 ] && !! pop.feature,
			JSON.stringify( [ kept, restCalls, pop.cards.map( ( c ) => c.likes ), pop.sorts ] ) );
		await page.goBack();
		await page.waitForFunction( () => ! location.search && 'true' === document.querySelector( '[data-rubric-sort="new"]' ).getAttribute( 'aria-current' ) && ! document.querySelector( '[data-rubric-list]' ).hasAttribute( 'aria-busy' ) );
		const back = await state( page );
		check( '«Назад» в браузере возвращает «Новые» (без перезагрузки)', JSON.stringify( back.cards.map( ( c ) => c.title ) ) === JSON.stringify( NEW ) && await page.evaluate( () => true === window.trustNoReload ), JSON.stringify( back.cards.map( ( c ) => c.title ) ) );

		await page.goto( `${ RUBRIC }?sort=popular`, { waitUntil: 'networkidle' } );
		const direct = await state( page );
		check( 'Прямой адрес ?sort=popular: популярные сверху, нажата «Популярные»; canonical — рубрика без параметра',
			JSON.stringify( direct.cards.map( ( c ) => c.title ) ) === JSON.stringify( POPULAR ) && 'true' === direct.sorts[ 1 ][ 1 ] && RUBRIC === direct.canonical,
			JSON.stringify( [ direct.cards.map( ( c ) => c.title ), direct.canonical ] ) );

		// ===== REST =====
		const rest = async ( q ) => {
			const r = await fetch( `${ URL }/wp-json/trust/v1/rubrics/${ q }` );
			return [ r.status, await r.json() ];
		};
		const [ okStatus, ok ] = await rest( `${ audit }?sort=popular` );
		const [ badSort ] = await rest( `${ audit }?sort=random` );
		const [ noTerm ] = await rest( '999999' );
		const [ noPage ] = await rest( `${ audit }?page=2` );
		check( 'REST /trust/v1/rubrics/{id}: html, page, pages, url (?sort=popular); неверная сортировка — 400, нет рубрики — 404, нет страницы — 404',
			200 === okStatus && `${ RUBRIC }?sort=popular` === ok.url && 1 === ok.pages && ok.html.includes( 'post-cards--rubric' ) && 400 === badSort && 404 === noTerm && 404 === noPage,
			JSON.stringify( [ okStatus, ok.url, ok.pages, badSort, noTerm, noPage ] ) );

		// ===== «Не нашли ответ?» и «О рубрике» =====
		await page.goto( RUBRIC, { waitUntil: 'networkidle' } );
		check( '«Не нашли ответ?»: заголовок, текст, «Задать вопрос» → страница вопросов',
			s.teaser && 'Не нашли ответ?' === s.teaser.title && /^Задайте вопрос SEO-специалисту/.test( s.teaser.text ) && 'Задать вопрос' === s.teaser.btn && expect.ask === s.teaser.href,
			JSON.stringify( [ s.teaser, expect.ask ] ) );
		await page.click( '.rubric-about summary' );
		const opened = await page.evaluate( () => [ document.querySelector( '.rubric-about details' ).open, null !== document.querySelector( '.rubric-about__text' ).offsetParent ] );
		check( '«О рубрике «Технический аудит»» (H2 в summary) свёрнут, раскрывается по нажатию; текст из поля рубрики со ссылкой на аудит',
			s.about && 'О рубрике «Технический аудит»' === s.about.title && false === s.about.open && JSON.stringify( opened ) === JSON.stringify( [ true, true ] ) && /^Технический аудит — проверка/.test( s.about.text ) && '/seo/audit/' === s.about.link,
			JSON.stringify( [ s.about, opened ] ) );

		// ===== Микроразметка =====
		const ld = await page.evaluate( () => [ ...document.querySelectorAll( 'script[type="application/ld+json"]' ) ].flatMap( ( el ) => {
			const data = JSON.parse( el.textContent );
			return data[ '@graph' ] || [ data ];
		} ) );
		const collection = ld.find( ( n ) => 'CollectionPage' === n[ '@type' ] );
		const list = ld.find( ( n ) => 'ItemList' === n[ '@type' ] );
		const items = list && list.itemListElement ? list.itemListElement : [];
		check( 'Микроразметка: CollectionPage, ItemList — главная статья и 4 карточки, главное содержимое страницы',
			!! collection && collection.mainEntity && collection.mainEntity[ '@id' ] === list[ '@id' ] && 5 === list.numberOfItems && 'Robots.txt: как проверить файл и не закрыть сайт от поиска' === items[ 0 ].name,
			JSON.stringify( [ list && list.numberOfItems, items.map( ( i ) => i.name ) ] ) );

		// ===== Флажок «Главная статья» =====
		const sitemap = postId( 'karta-sayta-sitemap' );
		wp( 'eval', `update_post_meta( ${ sitemap }, 'trust_featured', 1 );` );
		await page.goto( RUBRIC, { waitUntil: 'networkidle' } );
		const flagged = await state( page );
		wp( 'eval', `delete_post_meta( ${ sitemap }, 'trust_featured' );` );
		check( 'Флажок «Главная статья» у статьи рубрики: она — крупной карточкой, robots.txt — в списке',
			'/blog/karta-sayta-sitemap/' === flagged.feature.href && flagged.cards.some( ( c ) => '/blog/robots-txt-proverka/' === c.href ) && ! flagged.cards.some( ( c ) => '/blog/karta-sayta-sitemap/' === c.href ),
			JSON.stringify( [ flagged.feature.href, flagged.cards.map( ( c ) => c.href ) ] ) );

		// ===== Тексты из настроек =====
		setSettings( { tpl_rubric_list: 'Все статьи рубрики', tpl_rubric_about: 'Подробнее о рубрике', teaser_title: 'Остались вопросы?' } );
		await page.goto( RUBRIC, { waitUntil: 'networkidle' } );
		const texts = await state( page );
		setSettings( { tpl_rubric_list: '', tpl_rubric_about: '', teaser_title: '' } );
		check( '«Тексты шаблонов» (заголовок списка, «О рубрике») и «Блоки по умолчанию» («Не нашли ответ?») меняют тексты',
			'Все статьи рубрики' === texts.listTitle && 'Подробнее о рубрике «Технический аудит»' === texts.about.title && 'Остались вопросы?' === texts.teaser.title,
			JSON.stringify( [ texts.listTitle, texts.about.title, texts.teaser.title ] ) );

		// ===== Пагинация с сортировкой и пустые поля (временная рубрика) =====
		const made = evalJson( `
			$term = wp_insert_term( 'Тестовая рубрика', 'category', array( 'slug' => 'test-rubrika' ) );
			$empty = wp_insert_term( 'Пустая рубрика', 'category', array( 'slug' => 'pustaya-rubrika' ) );
			$posts = array();
			for ( $i = 1; $i <= 9; $i++ ) {
				$id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Тестовая статья ' . $i, 'post_name' => 'test-rubrika-' . $i, 'post_content' => 'Текст.', 'post_date' => gmdate( 'Y-m-d H:i:s', strtotime( '2020-01-01' ) - $i * DAY_IN_SECONDS ), 'post_category' => array( $term['term_id'] ) ) );
				update_post_meta( $id, 'trust_likes', $i * 10 );
				$posts[] = $id;
			}
			echo wp_json_encode( array( 'term' => $term['term_id'], 'empty' => $empty['term_id'], 'posts' => $posts ) );
		` );
		temp.posts = made.posts;
		temp.terms = [ made.term, made.empty ];
		setSettings( { blog_per_page: 6 } );
		const TEST = `${ URL }/blog/rubrika/test-rubrika/`;
		await page.goto( TEST, { waitUntil: 'networkidle' } );
		const p1 = await state( page );
		await page.goto( `${ TEST }?sort=popular`, { waitUntil: 'networkidle' } );
		const p1pop = await state( page );
		await page.goto( `${ TEST }page/2/?sort=popular`, { waitUntil: 'networkidle' } );
		const p2pop = await state( page );
		check( 'Пагинация: статей на странице — из «Настроек сайта → Блог» (6), главная статья не повторяется (8 в списке → 2 страницы); ссылки страниц сохраняют ?sort=popular',
			6 === p1.cards.length && p1.pager.includes( '/blog/rubrika/test-rubrika/page/2/#posts' ) && p1pop.pager.includes( '/blog/rubrika/test-rubrika/page/2/?sort=popular#posts' ) &&
				2 === p2pop.cards.length && null === p2pop.feature && JSON.stringify( p1pop.cards.map( ( c ) => c.likes ) ) === JSON.stringify( [ '90', '80', '70', '60', '50', '40' ] ) && JSON.stringify( p2pop.cards.map( ( c ) => c.likes ) ) === JSON.stringify( [ '30', '20' ] ),
			JSON.stringify( [ p1.cards.length, p1.pager, p1pop.pager, p2pop.cards.map( ( c ) => c.likes ), p1pop.cards.map( ( c ) => c.likes ) ] ) );
		await page.evaluate( () => {
			window.trustNoReload = true;
		} );
		await Promise.all( [ page.waitForURL( /test-rubrika\/(#posts)?$/ ), page.click( '[data-rubric-sort="new"]' ) ] );
		await page.waitForLoadState( 'networkidle' );
		const reloaded = await page.evaluate( () => undefined === window.trustNoReload );
		const [ , restPage ] = await rest( `${ made.term }?sort=popular&page=2` );
		check( 'Со страницы 2 переключатель — обычный переход на первую страницу; REST страницы 2 отдаёт пагинацию с ?sort=popular',
			reloaded && restPage.html.includes( 'test-rubrika/?sort=popular#posts' ) && 2 === restPage.page, JSON.stringify( [ reloaded, restPage.url ] ) );
		check( 'Поля рубрики пустые: нет описания, «Ведёт рубрику», «О рубрике»', null === p1.desc && null === p1.lead && null === p1.about && '9 статей' === p1.meta.split( ' · ' )[ 0 ], JSON.stringify( [ p1.desc, p1.lead, p1.about, p1.meta ] ) );
		const emptyResp = await page.goto( `${ URL }/blog/rubrika/pustaya-rubrika/`, { waitUntil: 'networkidle' } );
		const empty = await state( page );
		check( 'Пустая рубрика: страница открывается, «Статей пока нет.», без сортировки, главной статьи и «N статей»; в полосе рубрик её нет',
			200 === emptyResp.status() && 'Статей пока нет.' === empty.empty && 0 === empty.sorts.length && null === empty.feature && null === empty.meta && ! empty.strip.some( ( i ) => 'Пустая рубрика' === i[ 0 ] ),
			JSON.stringify( [ emptyResp.status(), empty.empty, empty.sorts, empty.meta ] ) );
		setSettings( { blog_per_page: saved.blog_per_page || '' } );
		wp( 'eval', `foreach ( array( ${ temp.posts.join( ',' ) } ) as $id ) { wp_delete_post( $id, true ); } foreach ( array( ${ temp.terms.join( ',' ) } ) as $t ) { wp_delete_term( $t, 'category' ); }` );
		temp.posts = [];
		temp.terms = [];

		// ===== Раскладка =====
		const layout = {};
		for ( const width of [ 1280, 390 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( RUBRIC, { waitUntil: 'networkidle' } );
			layout[ width ] = await page.evaluate( () => {
				const r = ( sel ) => document.querySelector( sel ).getBoundingClientRect();
				const strip = document.querySelector( '.rubric-strip' );
				const cur = strip.querySelector( '[aria-current]' ).getBoundingClientRect();
				const sr = strip.getBoundingClientRect();
				const cards = [ ...document.querySelectorAll( '.post-cards--rubric > li' ) ].map( ( li ) => Math.round( li.getBoundingClientRect().top ) );
				return {
					cols: cards.filter( ( t ) => t === cards[ 0 ] ).length,
					lead: r( '.rubric-lead' ).left > r( '.rubric-hero__head' ).right ? 'right' : 'below',
					leadFull: Math.round( r( '.archive-hero__inner' ).width - r( '.rubric-lead' ).width ),
					stripRow: 'nowrap' === getComputedStyle( strip ).flexWrap && strip.scrollWidth > strip.clientWidth,
					current: cur.left >= sr.left - 1 && cur.right <= sr.right + 1,
					sortBelow: r( '.rubric-sort' ).top > r( '#posts-title' ).bottom - 1,
					overflow: document.documentElement.scrollWidth > innerWidth,
				};
			} );
		}
		check( 'Компьютер 1280: карточки по 2 в ряд, «Ведёт рубрику» справа от заголовка, полоса рубрик переносится, сортировка справа от H2',
			2 === layout[ 1280 ].cols && 'right' === layout[ 1280 ].lead && ! layout[ 1280 ].stripRow && ! layout[ 1280 ].sortBelow, JSON.stringify( layout[ 1280 ] ) );
		check( 'Телефон 390: карточки в колонку, «Ведёт рубрику» под заголовком во всю ширину, полоса рубрик — строкой с прокруткой, текущая рубрика видна; сортировка под H2; без прокрутки вбок',
			1 === layout[ 390 ].cols && 'below' === layout[ 390 ].lead && 0 === layout[ 390 ].leadFull && layout[ 390 ].stripRow && layout[ 390 ].current && layout[ 390 ].sortBelow && ! layout[ 390 ].overflow,
			JSON.stringify( layout[ 390 ] ) );

		// ===== Сверка с макетом =====
		// Обложки и даты в демо — другие, у статей другие анонсы, время чтения и число комментариев;
		// в полосе рубрик у демо есть «Отчёты», тегов рубрики больше: при сверке — как в макете.
		await page.goto( mockupUrl( MOCK ), { waitUntil: 'networkidle' } );
		await settle( page );
		const mock = await page.evaluate( () => {
			const text = ( el ) => el.textContent.replace( /\s+/g, ' ' ).trim();
			const list = document.querySelector( 'section[aria-labelledby="list-title"]' );
			return {
				tags: [ ...document.querySelectorAll( '.dc-tagl' ) ].map( text ),
				cards: [ ...list.querySelectorAll( 'ul > li' ) ].map( ( li ) => ( {
					tags: [ ...li.querySelectorAll( '.dc-post-body > span:first-child > span' ) ].map( text ),
					excerpt: text( li.querySelector( 'p' ) ),
					date: text( li.querySelector( 'time' ) ),
					read: text( li.querySelector( 'time' ).nextElementSibling ),
					comments: text( li.querySelectorAll( 'time ~ span > span' )[ 1 ] ),
				} ) ),
			};
		} );
		const lastText = `const nodes = [ ...el.childNodes ].filter( ( n ) => 3 === n.nodeType && n.textContent.trim() ); nodes[ nodes.length - 1 ].textContent = value;`;
		const fixHero = ( p ) => p.evaluate( ( [ m, fn ] ) => {
			const last = new Function( 'el', 'value', fn ); // eslint-disable-line no-new-func
			document.querySelector( '.rubric-hero__meta' ).textContent = '[N] статей · обновлено 5 ноября 2026';
			const photo = document.querySelector( '.rubric-lead__photo' );
			if ( 'IMG' === photo.tagName ) {
				const span = document.createElement( 'span' );
				span.className = 'rubric-lead__photo';
				span.textContent = 'ИФ';
				photo.replaceWith( span );
			}
			document.querySelector( '.rubric-lead__role' ).textContent = '[Должность]';
			const strip = document.querySelector( '.rubric-strip' );
			[ ...strip.children ].slice( 8 ).forEach( ( a ) => a.remove() );
			strip.scrollLeft = 0;
			const items = [ ...document.querySelectorAll( '.rubric-tags__list > li' ) ];
			items.forEach( ( li, i ) => ( i < m.tags.length ? ( li.firstElementChild.textContent = m.tags[ i ] ) : li.remove() ) );
			document.querySelector( '.post-feature__img' ).style.visibility = 'hidden';
			document.querySelectorAll( '.post-feature .post-tags__tag' ).forEach( ( t, i ) => ( t.textContent = [ '#robots.txt', '#индексация' ][ i ] ) );
			document.querySelector( '.post-feature time' ).textContent = '5 ноября 2026';
			last( document.querySelectorAll( '.post-feature .post-stat' )[ 0 ], '9 мин чтения' );
		}, [ mock, lastText ] );
		const fixList = ( p ) => p.evaluate( ( [ m, fn ] ) => {
			const last = new Function( 'el', 'value', fn ); // eslint-disable-line no-new-func
			document.querySelectorAll( '.post-cards--rubric .post-card' ).forEach( ( card, i ) => {
				const c = m.cards[ i ];
				card.querySelector( '.post-card__img' ).style.visibility = 'hidden';
				card.querySelector( '.post-card__cover' ).style.background = 'none';
				card.querySelectorAll( '.post-tags__tag' ).forEach( ( t, j ) => ( t.textContent = c.tags[ j ] ) );
				card.querySelector( '.post-card__excerpt' ).textContent = c.excerpt;
				card.querySelector( 'time' ).textContent = c.date;
				card.querySelector( '.post-card__read' ).textContent = c.read;
				last( card.querySelectorAll( '.post-stat' )[ 1 ], c.comments );
			} );
		}, [ mock, lastText ] );
		for ( const width of [ 1280, 768, 390 ] ) {
			await compare( page, {
				name: 'hero', width, theme: '.rubric-hero', mock: 'main > section',
				before: fixHero,
				mockBefore: async ( p ) => {
					await p.addStyleTag( { content: 'main > section:first-of-type img { visibility: hidden; }' } );
					// Правка заказчика к главной статье блога: текст кнопки «Читать полностью».
					await p.evaluate( () => {
						const span = document.querySelector( 'main > section a.dc-card > div:last-child > span:last-child' );
						[ ...span.childNodes ].find( ( n ) => 3 === n.nodeType && n.textContent.trim() ).textContent = 'Читать полностью';
					} );
				},
			} );
			await compare( page, {
				name: 'list', width, theme: '.rubric-list', mock: 'section[aria-labelledby="list-title"]',
				before: fixList,
				mockBefore: ( p ) => p.addStyleTag( { content: 'section[aria-labelledby="list-title"] img { visibility: hidden; } nav.dc-pg { display: none !important; }' } ),
			} );
			await compare( page, {
				name: 'about', width, theme: '.rubric-about', mock: 'section[aria-labelledby="about-title"]',
				before: ( p ) => p.evaluate( () => ( document.querySelector( '.rubric-about details' ).open = true ) ),
				mockBefore: ( p ) => p.evaluate( () => ( document.querySelector( 'section[aria-labelledby="about-title"] details' ).open = true ) ),
			} );
		}

		// ===== Ширины, доступность, консоль =====
		const overflow = [];
		for ( const w of [ 1440, 1280, 1024, 768, 600, 390, 320, 280 ] ) {
			await page.setViewportSize( { width: w, height: 800 } );
			await page.goto( RUBRIC, { waitUntil: 'networkidle' } );
			if ( await page.evaluate( () => document.documentElement.scrollWidth > window.innerWidth ) ) {
				overflow.push( w );
			}
		}
		check( 'Ширины 1440–280: без прокрутки вбок', 0 === overflow.length, overflow.join( ', ' ) );
		for ( const width of [ 1280, 390 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( RUBRIC, { waitUntil: 'networkidle' } );
			await page.click( '.rubric-about summary' );
			await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
			const axe = await page.evaluate( async () => ( await window.axe.run( document, { resultTypes: [ 'violations' ] } ) ).violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id }: ${ v.nodes.map( ( n ) => n.target.join( ' ' ) ).slice( 0, 3 ).join( ', ' ) }` ) );
			check( `axe, ${ width }px: нет нарушений critical/serious`, 0 === axe.length, axe.join( ' | ' ) );
		}
		check( 'Консоль браузера без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );
	} finally {
		if ( temp.posts.length || temp.terms.length ) {
			wp( 'eval', `foreach ( array( ${ temp.posts.join( ',' ) } ) as $id ) { wp_delete_post( $id, true ); } foreach ( array( ${ temp.terms.join( ',' ) } ) as $t ) { wp_delete_term( $t, 'category' ); }` );
		}
		wp( 'eval', `delete_post_meta( ${ postId( 'karta-sayta-sitemap' ) }, 'trust_featured' );` );
		setSettings( Object.fromEntries( SETTINGS.map( ( n ) => [ n, saved[ n ] || '' ] ) ) );
	}
	checkLog();
	await browser.close();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
