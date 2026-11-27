/**
 * Тест шага 5.2 — статья блога (single-post.php, макет seo-blog/statya.html), просмотры и лайки.
 *
 * Запуск: node wp-theme/tests/post.js
 *
 * Демо-статья «Robots.txt: как проверить файл…» — полный текст из макета. Проверяет крошки и H1,
 * первый экран (H1 и рисунок напротив него, линия, дата, время чтения, просмотры, лайки,
 * комментарии с русскими склонениями, ключевые слова; автора нет — правка заказчика), содержание (пункты из H2, якоря транслитом,
 * текущий раздел, «Прочитано N %», переход по пункту под шапку), текст (обложка, анонс, таблица
 * с заголовками строк, шаги, картинка с подписью, цитата с инициалами, видео, «Коротко: чек-лист»),
 * «Ключевые слова», в одну строку «Понравилось? Лайкни!» и автор (ссылка на страницу сотрудника,
 * тёмная карточка; на узком экране — друг под другом), обложка и картинки текста на весь экран,
 * «Читайте также», просмотры (раз в сутки с браузера
 * и с IP, боты не считаются), лайки (поставить и снять, все кнопки вместе, после перезагрузки,
 * лимит с IP), REST (404, no-store), настройки «Показывать просмотры / лайки», «Тексты шаблонов»,
 * мобильную полосу содержания, BlogPosting, совпадение с макетом на 1280 / 768 / 390 px, ширины
 * 1440–280, axe-core, консоль, редактор (блок «Как бы поступил я» доступен, блоки статьи без ошибок).
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp, getSettings, setSettings } = require( './lib' );

const POST = `${ URL }/blog/robots-txt-proverka/`;
const TITLE = 'Robots.txt: как проверить файл и не закрыть сайт от поиска';
// Обычный браузер: у Playwright по умолчанию в User-Agent «HeadlessChrome» — такие просмотры не считаются.
const BROWSER_UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
const H2 = [
	'Что такое robots.txt и где он лежит',
	'Основные директивы',
	'Как проверить robots.txt: 5 шагов',
	'Что закрывать, а что оставлять открытым',
	'Частые ошибки',
	'Видеоразбор',
	'Коротко: чек-лист',
];
const SETTINGS = [ 'blog_show_views', 'blog_show_likes', 'tpl_post_toc', 'tpl_post_tags', 'tpl_post_useful', 'tpl_post_related_label', 'tpl_post_related', 'tpl_post_related_more' ];

// Вернуть настройки как были: несохранённые (null) — удалить, иначе флажки «по умолчанию да» станут «нет».
const restoreSettings = ( values ) => {
	const b64 = Buffer.from( JSON.stringify( values ) ).toString( 'base64' );
	wp( 'eval', `foreach ( json_decode( base64_decode( '${ b64 }' ), true ) as $n => $v ) { $k = Trust\\Fields::key( Trust\\Settings::PREFIX, $n ); if ( null === $v ) { delete_field( $k, 'option' ); } else { update_field( $k, $v, 'option' ); } }` );
};
const postId = () => wp( 'eval', "echo (int) get_page_by_path( 'robots-txt-proverka', OBJECT, 'post' )->ID;" );
const meta = ( id, key ) => wp( 'eval', `echo (int) get_post_meta( ${ id }, '${ key }', true );` );
const clearStatsLimits = () => wp( 'eval', "global $wpdb; foreach ( $wpdb->get_col( \"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_trust_view_%' OR option_name LIKE '_transient_trust_like_ip_%'\" ) as $o ) { delete_transient( substr( $o, strlen( '_transient_' ) ) ); }" );

/** Состояние страницы статьи. */
function state( page ) {
	return page.evaluate( () => {
		const text = ( el ) => ( el ? el.textContent.replace( /\s+/g, ' ' ).trim() : null );
		const hero = document.querySelector( '.post-hero' );
		const author = document.querySelector( '.post-foot .post-author-card' );
		return {
			crumbs: [ ...document.querySelectorAll( '.breadcrumbs__item' ) ].map( text ),
			h1: [ ...document.querySelectorAll( 'h1' ) ].map( text ),
			author: author && {
				tag: author.tagName,
				href: author.href ? new URL( author.href ).pathname : null,
				name: text( author.querySelector( '.post-author-card__name' ) ),
				role: text( author.querySelector( '.post-author-card__role' ) ),
				photo: !! author.querySelector( 'img.post-author-card__avatar[alt=""]' ),
				bg: getComputedStyle( author ).backgroundColor,
			},
			meta: [ ...hero.querySelectorAll( '.post-hero__meta > li' ) ].map( text ),
			heroAuthor: !! hero.querySelector( '.post-author-card, [class*="author"]' ),
			heroBg: getComputedStyle( hero.querySelector( '.post-hero__card' ) ).backgroundColor,
			metaLabel: hero.querySelector( '.post-hero__meta' ).getAttribute( 'aria-label' ),
			datetime: hero.querySelector( '.post-hero__meta time' ).getAttribute( 'datetime' ),
			heroTags: [ ...hero.querySelectorAll( '.post-hero__tag' ) ].map( ( a ) => [ text( a ), getComputedStyle( a ).textTransform, new URL( a.href ).pathname ] ),
			heroTagsLabel: hero.querySelector( '.post-hero__tags' ) && hero.querySelector( '.post-hero__tags' ).getAttribute( 'aria-label' ),
			toc: [ ...document.querySelectorAll( '.post-side .post-toc__link' ) ].map( ( a ) => [ text( a.querySelector( '.post-toc__num' ) ), a.lastChild.textContent.trim(), a.hash.slice( 1 ) ] ),
			tocLabel: text( document.querySelector( '.post-side .post-toc__label' ) ),
			h2: [ ...document.querySelectorAll( '#post-article h2' ) ].map( ( h ) => [ text( h ), h.id ] ),
			cover: ( () => {
				const img = document.querySelector( '.post-text__cover img' );
				return img && [ img.alt, img.getAttribute( 'fetchpriority' ), img.getAttribute( 'loading' ) ];
			} )(),
			lead: text( document.querySelector( '.post-text__lead' ) ),
			table: ( () => {
				const t = document.querySelector( '#post-article .wp-block-table' );
				return t && {
					rows: t.querySelectorAll( 'tbody th[scope="row"]' ).length,
					labels: [ ...t.querySelectorAll( 'tbody tr:first-child td' ) ].map( ( td ) => td.dataset.label ),
					caption: text( t.querySelector( 'figcaption' ) ),
				};
			} )(),
			steps: [ ...document.querySelectorAll( '#post-article > ol > li > strong:first-child' ) ].map( text ),
			figure: ( () => {
				const f = document.querySelector( '#post-article .wp-block-image' );
				return f && [ !! f.querySelector( 'img' ).alt, text( f.querySelector( 'figcaption' ) ) ];
			} )(),
			quote: ( () => {
				const cite = document.querySelector( '#post-article .is-style-author cite' );
				return cite && [ cite.dataset.initials, getComputedStyle( cite, '::before' ).content ];
			} )(),
			video: !! document.querySelector( '#post-article .b-video' ),
			how: ( () => {
				const how = document.querySelector( '#post-article .b-how' );
				return how && [ how.getAttribute( 'aria-labelledby' ), how.querySelector( 'h2' ).id, how.querySelectorAll( 'li' ).length, text( how.querySelector( '.b-how__cta' ) ) ];
			} )(),
			foot: ( () => {
				const f = document.querySelector( '.post-foot' );
				return f && {
					label: text( f.querySelector( '.post-foot__label' ) ),
					tags: [ ...f.querySelectorAll( '.post-tag' ) ].map( text ),
					question: text( f.querySelector( '.post-foot__question' ) ),
					like: !! f.querySelector( '[data-post-like]' ),
				};
			} )(),
			likes: [ ...document.querySelectorAll( '[data-post-like]' ) ].map( ( b ) => [ b.getAttribute( 'aria-pressed' ), text( b ) ] ),
			heroLiked: !! document.querySelector( '.post-hero__count--likes.is-liked' ),
			related: ( () => {
				const r = document.querySelector( '.post-related' );
				return r && {
					eyebrow: text( r.querySelector( '.eyebrow' ) ),
					h2: text( r.querySelector( 'h2' ) ),
					all: [ text( r.querySelector( '.post-related__all' ) ), new URL( r.querySelector( '.post-related__all' ).href ).pathname ],
					cards: [ ...r.querySelectorAll( '.post-card' ) ].map( ( a ) => [ text( a.querySelector( 'h3' ) ), new URL( a.href ).pathname ] ),
				};
			} )(),
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
	const base = path.join( OUT, `post-${ o.name }-${ o.width }` );
	await page.setViewportSize( { width: o.width, height: 900 } );
	await page.goto( POST, { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	await o.before( page );
	await isolate( page, o.theme );
	await page.waitForTimeout( 200 );
	await page.locator( o.theme ).first().screenshot( { path: `${ base }-theme.png` } );
	await page.goto( mockupUrl( 'seo-blog/statya.html' ), { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	await o.mockBefore( page );
	await isolate( page, o.mock );
	await page.waitForTimeout( 200 );
	await page.locator( o.mock ).first().screenshot( { path: `${ base }-mockup.png` } );
	const diff = comparePng( `${ base }-mockup.png`, `${ base }-theme.png`, `${ base }-diff.png` );
	check( `Макет seo-blog/statya.html, ${ o.name }, ${ o.width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
}

( async () => {
	clearLog();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const saved = getSettings( SETTINGS );
	const id = postId();
	const savedViews = parseInt( meta( id, 'trust_views' ), 10 );
	const savedLikes = parseInt( meta( id, 'trust_likes' ), 10 );
	// На время теста — числа демо-статьи (1 248 просмотров, 86 лайков), после — как были.
	const DEMO_VIEWS = 1248;
	const DEMO_LIKES = 86;
	wp( 'post', 'meta', 'update', id, 'trust_views', String( DEMO_VIEWS ) );
	wp( 'post', 'meta', 'update', id, 'trust_likes', String( DEMO_LIKES ) );
	clearStatsLimits();

	try {
		// ===== Страница статьи (браузер Playwright — «бот»: просмотр не считается) =====
		const res = await page.goto( POST, { waitUntil: 'networkidle' } );
		const s = await state( page );
		const member = JSON.parse( wp( 'eval', "$m = Trust\\Team::for_user( (int) get_post_field( 'post_author', get_page_by_path( 'robots-txt-proverka', OBJECT, 'post' ) ) ); echo wp_json_encode( array( wp_parse_url( get_permalink( $m ), PHP_URL_PATH ), Trust\\Team::experience_line( $m->ID ) ) );" ) );
		check( 'Статья — 200; крошки «Главная / Блог / Технический аудит / заголовок», один H1 — заголовок статьи',
			200 === res.status() && JSON.stringify( s.crumbs ) === JSON.stringify( [ 'Главная', 'Блог', 'Технический аудит', TITLE ] ) && JSON.stringify( s.h1 ) === JSON.stringify( [ TITLE ] ),
			JSON.stringify( [ s.crumbs, s.h1 ] ) );
		check( 'Правка заказчика: автора в первом экране нет; под статьёй — карточка автора на фоне первого экрана, ссылка на страницу сотрудника: фото, имя, «должность · опыт в SEO — N лет»',
			! s.heroAuthor && s.author && s.author.bg === s.heroBg && 'A' === s.author.tag && member[ 0 ] === s.author.href && '[Имя Фамилия]' === s.author.name && `Старший SEO-специалист · ${ member[ 1 ] }` === s.author.role && s.author.photo,
			JSON.stringify( [ s.heroAuthor, s.heroBg, s.author, member ] ) );
		check( '«О статье»: дата по-русски (datetime), «N мин чтения», «1 248 просмотров», «86 лайков», «N комментариев» (склонения)',
			'О статье' === s.metaLabel && /^\d{1,2} [а-я]+ \d{4}$/.test( s.meta[ 0 ] ) && /^\d{4}-\d\d-\d\d$/.test( s.datetime ) && /^\d+ мин чтения$/.test( s.meta[ 1 ] ) &&
			'1 248 просмотров' === s.meta[ 2 ].replace( / /g, ' ' ) && '86 лайков' === s.meta[ 3 ].replace( / /g, ' ' ) && /^0 комментариев$/.test( s.meta[ 4 ].replace( / /g, ' ' ) ),
			JSON.stringify( s.meta ) );
		const tagNames = s.heroTags.map( ( t ) => t[ 0 ] ).sort();
		check( 'Ключевые слова в первом экране: 5 тегов ссылками /blog/teg/…, заглавными; «robots.txt» — как написано',
			'Ключевые слова' === s.heroTagsLabel && 5 === s.heroTags.length && JSON.stringify( tagNames ) === JSON.stringify( [ 'Google Search Console', 'robots.txt', 'Вебмастер', 'индексация', 'технический аудит' ].sort() ) &&
			s.heroTags.every( ( t ) => t[ 2 ].startsWith( '/blog/teg/' ) && ( 'robots.txt' === t[ 0 ] ? 'none' === t[ 1 ] : 'uppercase' === t[ 1 ] ) ),
			JSON.stringify( s.heroTags ) );
		check( 'Содержание: «Содержание», 7 пунктов 01–07 — H2 текста по порядку (и «Коротко: чек-лист» блока), якоря транслитом',
			'Содержание' === s.tocLabel && 7 === s.toc.length && s.toc.every( ( t, i ) => t[ 0 ] === String( i + 1 ).padStart( 2, '0' ) && t[ 1 ] === H2[ i ] && t[ 2 ] === s.h2[ i ][ 1 ] ) &&
			JSON.stringify( s.h2.map( ( h ) => h[ 0 ] ) ) === JSON.stringify( H2 ) && 'chto-takoe-robots-txt-i-gde-on-lezhit' === s.h2[ 0 ][ 1 ] && 'korotko-chek-list' === s.h2[ 6 ][ 1 ],
			JSON.stringify( s.toc ) );
		check( 'Текст: обложка с alt (загружается сразу, fetchpriority=high), анонс — вводный абзац',
			s.cover && '' !== s.cover[ 0 ] && 'high' === s.cover[ 1 ] && 'eager' === s.cover[ 2 ] && s.lead.startsWith( 'Одна неверная строка в robots.txt' ), JSON.stringify( [ s.cover, s.lead ] ) );
		check( 'Таблица: 5 строк с заголовками строк (th scope=row), подписи колонок для телефона, подпись таблицы; шаги — 5 пунктов с заголовками; картинка с alt и подписью',
			s.table && 5 === s.table.rows && JSON.stringify( s.table.labels ) === JSON.stringify( [ 'Что делает', 'Пример' ] ) && s.table.caption.startsWith( 'Директивы, которые понимают' ) &&
			5 === s.steps.length && 'Откройте файл в браузере' === s.steps[ 0 ] && s.figure && s.figure[ 0 ] && s.figure[ 1 ].includes( 'закрывает от сканирования весь сайт' ),
			JSON.stringify( [ s.table, s.steps, s.figure ] ) );
		check( 'Цитата «С автором» — инициалы в круге; «Видео»; «Коротко: чек-лист» — блок «Как бы поступил я» (якорь, 5 пунктов, кнопка)',
			s.quote && 'ИФ' === s.quote[ 0 ] && '"ИФ"' === s.quote[ 1 ] && s.video && s.how && 'korotko-chek-list' === s.how[ 0 ] && 'korotko-chek-list' === s.how[ 1 ] && 5 === s.how[ 2 ] && 'Заказать технический аудит' === s.how[ 3 ],
			JSON.stringify( [ s.quote, s.video, s.how ] ) );
		check( 'Под текстом: «Ключевые слова» с 5 тегами, «Понравилось? Лайкни!» (правка заказчика) с кнопкой «Нравится»; две кнопки «Нравится» (содержание и низ), обе не нажаты',
			s.foot && 'Ключевые слова' === s.foot.label && 5 === s.foot.tags.length && 'Понравилось? Лайкни!' === s.foot.question && s.foot.like &&
			2 === s.likes.length && s.likes.every( ( l ) => 'false' === l[ 0 ] && 'Нравится: 86' === l[ 1 ] ),
			JSON.stringify( [ s.foot, s.likes ] ) );
		const relatedTags = JSON.parse( wp( 'eval', `$ids = array(); foreach ( Trust\\Article::related( get_post( ${ id } ), 3 ) as $p ) { $ids[] = array( wp_parse_url( get_permalink( $p ), PHP_URL_PATH ), (bool) array_intersect( wp_list_pluck( Trust\\Article::tags( $p ), 'term_id' ), wp_list_pluck( Trust\\Article::tags( get_post( ${ id } ) ), 'term_id' ) ), Trust\\Blog::rubric( $p ) && Trust\\Blog::rubric( get_post( ${ id } ) ) && Trust\\Blog::rubric( $p )->term_id === Trust\\Blog::rubric( get_post( ${ id } ) )->term_id ); } echo wp_json_encode( $ids );` ) );
		check( '«Читайте также»: «Блог», H2, «Все статьи» → /blog/, 3 карточки статей (общие теги, затем рубрика), без текущей',
			s.related && 'Блог' === s.related.eyebrow && 'Читайте также' === s.related.h2 && 'Все статьи' === s.related.all[ 0 ] && '/blog/' === s.related.all[ 1 ] && 3 === s.related.cards.length &&
			! s.related.cards.some( ( c ) => '/blog/robots-txt-proverka/' === c[ 1 ] ) && JSON.stringify( s.related.cards.map( ( c ) => c[ 1 ] ) ) === JSON.stringify( relatedTags.map( ( r ) => r[ 0 ] ) ) &&
			relatedTags.every( ( r ) => r[ 1 ] || r[ 2 ] ),
			JSON.stringify( [ s.related, relatedTags ] ) );
		check( 'Просмотр браузера без окна (бот) не засчитан', String( DEMO_VIEWS ) === meta( id, 'trust_views' ), meta( id, 'trust_views' ) );

		const ld = await page.evaluate( () => {
			const graph = [ ...document.querySelectorAll( 'script[type="application/ld+json"]' ) ].flatMap( ( x ) => JSON.parse( x.textContent )[ '@graph' ] || [] );
			const a = graph.find( ( n ) => 'BlogPosting' === n[ '@type' ] );
			return a && [ a.headline, a.author && a.author[ '@type' ], a.author && a.author.name, a.author && a.author.jobTitle, !! ( a.author && a.author.url ), !! a.image, !! a.datePublished, a.articleSection, a.keywords.split( ', ' ).length, a.wordCount > 300, !! a.publisher, !! a.mainEntityOfPage, a.inLanguage ];
		} );
		check( 'Микроразметка BlogPosting: заголовок, автор Person (имя, должность, страница), обложка, даты, рубрика, ключевые слова, число слов, издатель',
			JSON.stringify( ld ) === JSON.stringify( [ TITLE, 'Person', '[Имя Фамилия]', 'Старший SEO-специалист', true, true, true, 'Технический аудит', 5, true, true, true, 'ru' ] ), JSON.stringify( ld ) );

		// ===== Содержание: текущий раздел, прогресс, переход по пункту =====
		const toc0 = await page.evaluate( () => [ document.querySelector( '.post-side [data-post-progress]' ).textContent, document.querySelector( '.post-side .post-toc__link[aria-current]' ).hash ] );
		await page.locator( '.post-side .post-toc__link' ).nth( 2 ).click();
		await page.waitForTimeout( 1200 );
		const toc3 = await page.evaluate( () => {
			const h = document.getElementById( 'kak-proverit-robots-txt-5-shagov' ).getBoundingClientRect();
			const header = document.querySelector( '.site-header' ).getBoundingClientRect();
			const side = document.querySelector( '.post-side' ).getBoundingClientRect();
			return { cur: document.querySelector( '.post-side .post-toc__link[aria-current]' ).hash, progress: +document.querySelector( '.post-side [data-post-progress]' ).textContent, below: h.top >= header.bottom, gap: Math.round( h.top - header.bottom ), side: Math.round( side.top - header.bottom ), fill: getComputedStyle( document.querySelector( '.post-side .post-toc__fill' ) ).width };
		} );
		await page.evaluate( () => window.scrollTo( 0, document.getElementById( 'post-article' ).getBoundingClientRect().bottom + window.scrollY - 100 ) );
		await page.waitForTimeout( 500 );
		const tocEnd = await page.evaluate( () => [ +document.querySelector( '.post-side [data-post-progress]' ).textContent, document.querySelector( '.post-side .post-toc__link[aria-current]' ).hash ] );
		check( 'Содержание: вначале «Прочитано 0%» и текущий — первый раздел; по пункту 03 — раздел под шапкой, текущий 03, прогресс растёт; в конце статьи — 100% и «Коротко: чек-лист»',
			'0' === toc0[ 0 ] && '#chto-takoe-robots-txt-i-gde-on-lezhit' === toc0[ 1 ] && '#kak-proverit-robots-txt-5-shagov' === toc3.cur && toc3.below && toc3.gap >= 16 && toc3.progress > 0 && toc3.progress < 100 && '0px' !== toc3.fill &&
			100 === tocEnd[ 0 ] && '#korotko-chek-list' === tocEnd[ 1 ],
			JSON.stringify( [ toc0, toc3, tocEnd ] ) );
		check( 'Колонка содержания закреплена под шапкой (24 px)', Math.abs( toc3.side - 24 ) <= 2, String( toc3.side ) );

		// ===== Просмотры =====
		clearStatsLimits();
		const human = await browser.newContext( { viewport: { width: 1280, height: 900 }, userAgent: BROWSER_UA } );
		const hp = await human.newPage();
		const viewRequests = [];
		hp.on( 'request', ( r ) => r.url().includes( '/trust/v1/posts/' ) && viewRequests.push( r.method() + ' ' + r.url().replace( /^.*\/posts\/\d+/, '' ) ) );
		await hp.goto( POST, { waitUntil: 'networkidle' } );
		await hp.waitForTimeout( 500 );
		const v1 = [ await hp.locator( '[data-post-count="views"]' ).textContent(), meta( id, 'trust_views' ) ];
		await hp.reload( { waitUntil: 'networkidle' } );
		await hp.waitForTimeout( 500 );
		const v2 = [ await hp.locator( '[data-post-count="views"]' ).textContent(), meta( id, 'trust_views' ) ];
		const other = await browser.newContext( { userAgent: BROWSER_UA } );
		const op = await other.newPage();
		await op.goto( POST, { waitUntil: 'networkidle' } );
		await op.waitForTimeout( 500 );
		const v3 = meta( id, 'trust_views' );
		await other.close();
		check( 'Просмотры: первый заход +1 (число на странице обновилось), повторный с того же браузера — без запроса +1, с другого браузера того же IP за сутки — не считается',
			'1 249 просмотров' === v1[ 0 ].replace( / /g, ' ' ) && String( DEMO_VIEWS + 1 ) === v1[ 1 ] && '1 249 просмотров' === v2[ 0 ].replace( / /g, ' ' ) && String( DEMO_VIEWS + 1 ) === v2[ 1 ] && String( DEMO_VIEWS + 1 ) === v3 &&
			JSON.stringify( viewRequests ) === JSON.stringify( [ 'POST /view', 'GET ' ] ),
			JSON.stringify( [ v1, v2, v3, viewRequests ] ) );

		// ===== Лайки =====
		await hp.locator( '.post-side [data-post-like]' ).click();
		await hp.waitForTimeout( 600 );
		const l1 = await state( hp );
		const cookie = ( await human.cookies() ).find( ( c ) => 'trust_likes' === c.name );
		const m1 = meta( id, 'trust_likes' );
		await hp.reload( { waitUntil: 'networkidle' } );
		await hp.waitForTimeout( 500 );
		const l2 = await state( hp );
		await hp.locator( '.post-foot [data-post-like]' ).click();
		await hp.waitForTimeout( 600 );
		const l3 = await state( hp );
		const m3 = meta( id, 'trust_likes' );
		check( 'Лайк: обе кнопки нажаты, «Нравится: 87», в первом экране «87 лайков» с залитым сердцем; сохранён на сервере и в cookie trust_likes',
			l1.likes.every( ( l ) => 'true' === l[ 0 ] && 'Нравится: 87' === l[ 1 ] ) && '87 лайков' === l1.meta[ 3 ].replace( / /g, ' ' ) && l1.heroLiked && String( DEMO_LIKES + 1 ) === m1 && cookie && cookie.value.split( '.' ).includes( id ),
			JSON.stringify( [ l1.likes, l1.meta[ 3 ], m1, cookie && cookie.value ] ) );
		check( 'После перезагрузки лайк помнится (кнопки нажаты, 87)', l2.likes.every( ( l ) => 'true' === l[ 0 ] && 'Нравится: 87' === l[ 1 ] ) && l2.heroLiked, JSON.stringify( l2.likes ) );
		check( 'Повторное нажатие снимает лайк: кнопки не нажаты, 86 на странице и на сервере',
			l3.likes.every( ( l ) => 'false' === l[ 0 ] && 'Нравится: 86' === l[ 1 ] ) && ! l3.heroLiked && '86 лайков' === l3.meta[ 3 ].replace( / /g, ' ' ) && String( DEMO_LIKES ) === m3,
			JSON.stringify( [ l3.likes, m3 ] ) );
		await human.close();

		// ===== REST =====
		const api = `${ URL }/wp-json/trust/v1/posts/`;
		const get = await page.request.get( api + id );
		const getJson = await get.json();
		const none = await page.request.get( api + '999999' );
		const draft = wp( 'post', 'create', '--post_type=post', '--post_status=draft', '--post_title=Черновик теста', '--porcelain' );
		const draftRes = await page.request.post( `${ api }${ draft }/like`, { data: { liked: true } } );
		wp( 'post', 'delete', draft, '--force' );
		const page404 = await page.request.post( `${ api }${ id }/like`, { data: {} } );
		check( 'REST: GET — числа без кеширования (no-store); нет статьи или черновик — 404; без liked — 400',
			200 === get.status() && 'no-store' === get.headers()[ 'cache-control' ] && 'number' === typeof getJson.views && 'number' === typeof getJson.likes && false === getJson.liked &&
			404 === none.status() && 404 === draftRes.status() && 400 === page404.status(),
			JSON.stringify( [ get.status(), get.headers()[ 'cache-control' ], getJson, none.status(), draftRes.status(), page404.status() ] ) );
		clearStatsLimits();
		const statuses = [];
		for ( let i = 0; i < 31; i++ ) {
			statuses.push( ( await page.request.post( `${ api }${ id }/like`, { data: { liked: 0 === i % 2 } } ) ).status() );
		}
		check( 'Защита от накрутки: не больше 30 нажатий «Нравится» с IP в час (31-е — 429)', statuses.slice( 0, 30 ).every( ( x ) => 200 === x ) && 429 === statuses[ 30 ], statuses.join( ',' ) );
		clearStatsLimits();
		wp( 'post', 'meta', 'update', id, 'trust_likes', String( DEMO_LIKES ) );
		wp( 'post', 'meta', 'update', id, 'trust_views', String( DEMO_VIEWS ) );

		// ===== Настройки: просмотры и лайки выключены, свои тексты =====
		setSettings( {
			blog_show_views: 0,
			blog_show_likes: 0,
			tpl_post_toc: 'Оглавление',
			tpl_post_tags: 'Теги',
			tpl_post_useful: 'Как вам статья?',
			tpl_post_related_label: 'Ещё',
			tpl_post_related: 'Похожие статьи',
			tpl_post_related_more: 'Весь блог',
		} );
		await page.goto( POST, { waitUntil: 'networkidle' } );
		const off = await state( page );
		restoreSettings( saved );
		check( '«Показывать просмотры» и «Показывать лайки» выключены — в первом экране только дата, время чтения, комментарии; кнопок «Нравится» нет',
			3 === off.meta.length && /комментари/.test( off.meta[ 2 ] ) && 0 === off.likes.length && null === off.foot.question, JSON.stringify( [ off.meta, off.likes, off.foot ] ) );
		check( '«Тексты шаблонов»: «Содержание», «Ключевые слова», «Блог», «Читайте также», «Все статьи» — свои',
			'Оглавление' === off.tocLabel && 'Теги' === off.foot.label && 'Теги' === off.heroTagsLabel && 'Ещё' === off.related.eyebrow && 'Похожие статьи' === off.related.h2 && 'Весь блог' === off.related.all[ 0 ],
			JSON.stringify( [ off.tocLabel, off.foot.label, off.related ] ) );
		await page.goto( POST, { waitUntil: 'networkidle' } );
		const useful = await page.evaluate( () => document.querySelector( '.post-foot__question' ).textContent.trim() );
		check( '«Тексты шаблонов» вернулись — «Понравилось? Лайкни!»', 'Понравилось? Лайкни!' === useful, useful );

		// ===== Правки заказчика: первый экран, низ статьи, картинки на весь экран =====
		const layout = {};
		for ( const width of [ 1280, 1024, 390 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( POST, { waitUntil: 'networkidle' } );
			layout[ width ] = await page.evaluate( () => {
				const r = ( el ) => el.getBoundingClientRect();
				const title = r( document.querySelector( '.post-hero__title' ) );
				const artEl = document.querySelector( '.post-hero__art' );
				const art = 'none' === getComputedStyle( artEl ).display ? null : r( artEl );
				const meta = document.querySelector( '.post-hero__meta' );
				const line = r( meta ).top;
				const tags = r( document.querySelector( '.post-foot__tags' ) );
				const like = r( document.querySelector( '.post-foot__useful' ) );
				const author = r( document.querySelector( '.post-author-card' ) );
				const row = r( document.querySelector( '.post-foot__row' ) );
				return {
					// Рисунок справа от заголовка, по центру его высоты; линия (верх «О статье») — под обоими.
					art: art && [ art.left > title.right, Math.round( Math.abs( ( art.top + art.bottom ) / 2 - ( title.top + title.bottom ) / 2 ) ), line >= art.bottom && line >= title.bottom ],
					lineBelowTitle: line >= title.bottom && '1px' === getComputedStyle( meta ).borderTopWidth,
					// Низ: ключевые слова сверху; лайки и автор — в одну строку или друг под другом.
					oneRow: author.top < like.bottom && like.top < author.bottom && author.left > like.right,
					order: tags.bottom <= like.top && like.bottom <= author.top,
					authorFull: Math.round( row.width - author.width ),
				};
			} );
		}
		check( 'Первый экран (1280 px): рисунок справа напротив заголовка (по центру его высоты), под ними линия, ниже «О статье» и теги; уже 1280 px — без рисунка, линия под заголовком',
			layout[ 1280 ].art && layout[ 1280 ].art[ 0 ] && layout[ 1280 ].art[ 1 ] <= 2 && layout[ 1280 ].art[ 2 ] && null === layout[ 1024 ].art && null === layout[ 390 ].art && Object.values( layout ).every( ( l ) => l.lineBelowTitle ),
			JSON.stringify( layout ) );
		check( 'Под статьёй: 1280 px — «Понравилось? Лайкни!» и автор в одну строку под ключевыми словами; не помещаются (1024, 390 px) — ключевые слова, лайки, автор друг под другом; на телефоне карточка автора во всю ширину',
			layout[ 1280 ].oneRow && ! layout[ 1024 ].oneRow && layout[ 1024 ].order && layout[ 390 ].order && 0 === layout[ 390 ].authorFull,
			JSON.stringify( Object.fromEntries( Object.entries( layout ).map( ( [ w, l ] ) => [ w, [ l.oneRow, l.order, l.authorFull ] ] ) ) ) );

		const zoom = {};
		for ( const width of [ 1280, 390 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( POST, { waitUntil: 'networkidle' } );
			const marks = await page.evaluate( () => [ ...document.querySelectorAll( '.post-text img' ) ].map( ( i ) => [ i.closest( '.b-video' ) ? 'video' : ( i.closest( '.post-text__cover' ) ? 'cover' : 'text' ), i.classList.contains( 'is-zoomable' ), i.getAttribute( 'role' ) ] ) );
			const opened = [];
			for ( const sel of [ '.post-text__cover img', '#post-article .wp-block-image img' ] ) {
				await page.locator( sel ).scrollIntoViewIfNeeded();
				await page.locator( sel ).click();
				await page.waitForTimeout( 400 );
				const st = await page.evaluate( () => {
					const d = document.querySelector( 'dialog.lightbox' );
					const im = d && d.querySelector( '.lightbox__img' );
					const box = im && im.getBoundingClientRect();
					return d && [ d.open, im.getAttribute( 'src' ).split( '/' ).pop(), Math.round( box.width ), ( d.querySelector( '.lightbox__caption' ).hidden ? '' : d.querySelector( '.lightbox__caption' ).textContent.trim() ).slice( 0, 30 ) ];
				} );
				await page.keyboard.press( 'Escape' );
				await page.waitForTimeout( 200 );
				opened.push( [ ...( st || [] ), await page.evaluate( () => ! document.querySelector( 'dialog.lightbox' ).open ) ] );
			}
			zoom[ width ] = { marks, opened };
		}
		check( 'Обложка и картинка в тексте открываются на весь экран (компьютер и телефон): окно, картинка в полном размере, подпись картинки; Esc закрывает; обложка видео — не открывается (у неё своя кнопка)',
			[ 1280, 390 ].every( ( w ) => zoom[ w ].marks.every( ( m ) => ( 'video' === m[ 0 ] ) !== m[ 1 ] ) && zoom[ w ].marks.some( ( m ) => 'cover' === m[ 0 ] && m[ 1 ] && 'button' === m[ 2 ] ) &&
				zoom[ w ].opened.length === 2 && zoom[ w ].opened.every( ( o ) => true === o[ 0 ] && ! /-\d+x\d+\./.test( o[ 1 ] ) && o[ 2 ] > 300 && true === o[ 4 ] ) && '' === zoom[ w ].opened[ 0 ][ 3 ] && zoom[ w ].opened[ 1 ][ 3 ].startsWith( 'Строка «Disallow' ) ),
			JSON.stringify( zoom ) );
		await page.setViewportSize( { width: 1280, height: 900 } );

		// ===== Телефон: полоса содержания =====
		const phone = await browser.newPage( { viewport: { width: 390, height: 844 } } );
		await phone.goto( POST, { waitUntil: 'networkidle' } );
		const m0 = await phone.evaluate( () => [ getComputedStyle( document.querySelector( '.post-side' ) ).display, getComputedStyle( document.querySelector( '.post-tocbar' ) ).display, document.querySelector( '.post-tocbar__toggle' ).getAttribute( 'aria-expanded' ), document.querySelector( '.post-tocbar__panel' ).hidden ] );
		await phone.evaluate( () => window.scrollTo( 0, document.getElementById( 'osnovnye-direktivy' ).getBoundingClientRect().top + window.scrollY - 200 ) );
		await phone.waitForTimeout( 500 );
		const m1p = await phone.evaluate( () => {
			const bar = document.querySelector( '.post-tocbar' ).getBoundingClientRect();
			const header = document.querySelector( '.site-header' ).getBoundingClientRect();
			return [ Math.round( bar.top - header.bottom ), document.querySelector( '[data-post-current]' ).textContent.trim(), +document.querySelector( '.post-tocbar [data-post-progress]' ).textContent ];
		} );
		await phone.locator( '.post-tocbar__toggle' ).click();
		const m2 = await phone.evaluate( () => [ document.querySelector( '.post-tocbar__toggle' ).getAttribute( 'aria-expanded' ), document.querySelector( '.post-tocbar__panel' ).hidden, document.querySelectorAll( '.post-tocbar .post-toc__link' ).length, document.querySelector( '.post-tocbar .post-toc__link[aria-current]' ).hash ] );
		await phone.locator( '.post-tocbar .post-toc__link' ).nth( 4 ).click();
		await phone.waitForTimeout( 1200 );
		const m3p = await phone.evaluate( () => {
			const h = document.getElementById( 'chastye-oshibki' ).getBoundingClientRect();
			const bar = document.querySelector( '.post-tocbar' ).getBoundingClientRect();
			return [ document.querySelector( '.post-tocbar__panel' ).hidden, h.top >= bar.bottom, document.querySelector( '[data-post-current]' ).textContent.trim() ];
		} );
		await phone.locator( '.post-tocbar__toggle' ).click();
		await phone.keyboard.press( 'Escape' );
		const m4 = await phone.evaluate( () => [ document.querySelector( '.post-tocbar__panel' ).hidden, document.activeElement.classList.contains( 'post-tocbar__toggle' ) ] );
		await phone.close();
		check( 'Телефон: колонки содержания нет, вверху — полоса «Содержание»: закреплена под шапкой, текущий раздел и %; раскрывается списком (текущий отмечен)',
			'none' === m0[ 0 ] && 'block' === m0[ 1 ] && 'false' === m0[ 2 ] && true === m0[ 3 ] && 0 === m1p[ 0 ] && 'Основные директивы' === m1p[ 1 ] && m1p[ 2 ] > 0 &&
			'true' === m2[ 0 ] && false === m2[ 1 ] && 7 === m2[ 2 ] && '#osnovnye-direktivy' === m2[ 3 ],
			JSON.stringify( [ m0, m1p, m2 ] ) );
		check( 'Телефон: выбор пункта закрывает список, раздел — под полосой; Esc закрывает список и возвращает фокус на кнопку',
			true === m3p[ 0 ] && m3p[ 1 ] && 'Частые ошибки' === m3p[ 2 ] && true === m4[ 0 ] && m4[ 1 ], JSON.stringify( [ m3p, m4 ] ) );

		// ===== Сверка с макетом =====
		// Даты, время чтения, комментарии, фото автора и порядок тегов в демо — свои: при сверке — как в макете.
		const fixHero = ( p ) => p.evaluate( () => {
			const meta = document.querySelectorAll( '.post-hero__meta > li' );
			meta[ 0 ].querySelector( 'time' ).textContent = '5 ноября 2026';
			const last = ( el, value ) => {
				const nodes = [ ...el.childNodes ].filter( ( n ) => 3 === n.nodeType && n.textContent.trim() );
				nodes[ nodes.length - 1 ].textContent = value;
			};
			last( meta[ 1 ], '9 мин чтения' );
			meta[ 2 ].querySelector( 'span' ).textContent = '1 248 просмотров';
			meta[ 4 ].querySelector( 'span' ).textContent = '12 комментариев';
			const tags = [ 'Технический аудит', 'robots.txt', 'Индексация', 'Яндекс Вебмастер', 'Google Search Console' ];
			document.querySelectorAll( '.post-hero__tag' ).forEach( ( a, i ) => ( a.textContent = tags[ i ] ) );
			document.querySelectorAll( '.post-hero__tag' ).forEach( ( a ) => a.classList.toggle( 'is-raw', 'robots.txt' === a.textContent ) );
		} );
		const fixText = ( p ) => p.addStyleTag( { content: '#post-article img { object-position: -99999px 0; }' } ).then( () => p.evaluate( () => {
			const cite = document.querySelector( '#post-article .is-style-author cite' );
			cite.innerHTML = '[Имя Фамилия]<br>[Должность]';
		} ) );
		// Правка заказчика: автора в первом экране нет (в макете убран при сверке); от 1280 px рисунок —
		// напротив заголовка (раскладка проверяется отдельно, сверка — на 768 и 390 px, где рисунка нет).
		for ( const width of [ 768, 390 ] ) {
			await compare( page, {
				name: 'hero', width, theme: '.post-hero', mock: 'section[aria-labelledby="post-title"]',
				before: fixHero,
				mockBefore: ( p ) => p.evaluate( () => document.querySelector( 'section[aria-labelledby="post-title"] a[href="avtor.html"]' ).remove() ),
			} );
		}
		for ( const width of [ 1280, 390 ] ) {
			await compare( page, {
				name: 'text', width, theme: '#post-article', mock: '#post-article',
				before: fixText,
				mockBefore: ( p ) => p.addStyleTag( { content: '#post-article img { object-position: -99999px 0; }' } ),
			} );
		}

		// ===== Ширины, доступность, консоль =====
		const overflow = [];
		for ( const w of [ 1440, 1280, 1024, 768, 600, 390, 320, 280 ] ) {
			await page.setViewportSize( { width: w, height: 800 } );
			await page.goto( POST, { waitUntil: 'networkidle' } );
			if ( await page.evaluate( () => document.documentElement.scrollWidth > window.innerWidth ) ) {
				overflow.push( w );
			}
		}
		check( 'Ширины 1440–280: без прокрутки вбок (карточки «Читайте также» листаются внутри списка)', 0 === overflow.length, overflow.join( ', ' ) );
		for ( const w of [ 1280, 390 ] ) {
			await page.setViewportSize( { width: w, height: 900 } );
			await page.goto( POST, { waitUntil: 'networkidle' } );
			await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
			const axe = await page.evaluate( async () => ( await window.axe.run( document, { resultTypes: [ 'violations' ] } ) ).violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => v.id ) );
			check( `axe, ${ w }px: нет нарушений critical/serious`, 0 === axe.length, axe.join( ', ' ) );
		}
		check( 'Консоль браузера без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1400, height: 1000 } } );
		const editorErrors = collectConsoleErrors( editor );
		await login( editor );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ id }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/editor' ) && wp.data.select( 'core/editor' ).getCurrentPostId() && wp.data.select( 'core/block-editor' ).getBlocks().length, null, { timeout: 60000 } );
		await editor.waitForTimeout( 2500 );
		const ed = await editor.evaluate( () => {
			const all = [];
			const walk = ( blocks ) => blocks.forEach( ( b ) => {
				all.push( b );
				walk( b.innerBlocks );
			} );
			walk( wp.data.select( 'core/block-editor' ).getBlocks() );
			const allowed = wp.data.select( 'core/block-editor' ).getSettings().allowedBlockTypes;
			return [ all.filter( ( b ) => ! b.isValid ).map( ( b ) => b.name ), [ ...new Set( all.map( ( b ) => b.name ) ) ].sort(), Array.isArray( allowed ) && allowed.includes( 'trust/how-i-would' ) ];
		} );
		check( 'Редактор: все блоки статьи без ошибок (таблица, шаги, картинка, цитата, «Видео», «Как бы поступил я»); «Как бы поступил я» доступен в статьях',
			0 === ed[ 0 ].length && [ 'core/table', 'core/image', 'core/quote', 'core/list', 'trust/video', 'trust/how-i-would' ].every( ( b ) => ed[ 1 ].includes( b ) ) && true === ed[ 2 ],
			JSON.stringify( ed ) );
		check( 'Редактор без ошибок JavaScript', 0 === editorErrors.length, editorErrors.slice( 0, 3 ).join( ' | ' ) );
		await editor.close();
	} finally {
		restoreSettings( saved );
		wp( 'post', 'meta', 'update', id, 'trust_likes', String( savedLikes ) );
		wp( 'post', 'meta', 'update', id, 'trust_views', String( savedViews ) );
		clearStatsLimits();
	}
	checkLog();
	await browser.close();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
