/**
 * Шаг 3.2: хлебные крошки и подсветка меню.
 *
 * Запуск: node wp-theme/tests/breadcrumbs.js (стенд запущен, демо-контент импортирован).
 *
 * Тест создаёт свою статью (заголовок начинается с «ТестКрошек») и удаляет её в конце.
 */

const { chromium } = require( 'playwright' );
const path = require( 'path' );
const { URL, OUT, check, finish, wp, clearLog, checkLog, login, routeMockupFonts, mockupUrl, comparePng, settle } = require( './lib' );

const MARK = 'ТестКрошек';

/** Крошки страницы: пункты, ссылки, текущий пункт. */
async function crumbs( page, url ) {
	const res = await page.goto( url.startsWith( 'http' ) ? url : URL + url );
	const nav = page.locator( 'nav.breadcrumbs' );
	if ( ! ( await nav.count() ) ) {
		return { status: res.status(), items: [], links: [], current: null };
	}
	return {
		status: res.status(),
		...( await nav.evaluate( ( el ) => ( {
			items: [ ...el.querySelectorAll( '.breadcrumbs__item' ) ].map( ( li ) => li.textContent.trim() ),
			links: [ ...el.querySelectorAll( 'a' ) ].map( ( a ) => a.getAttribute( 'href' ) ),
			current: el.querySelector( '[aria-current="page"]' )?.textContent.trim() ?? null,
			label: el.getAttribute( 'aria-label' ),
			separators: [ ...el.querySelectorAll( '.breadcrumbs__sep' ) ].every( ( s ) => s.getAttribute( 'aria-hidden' ) === 'true' ),
			linkHeights: [ ...el.querySelectorAll( 'a' ) ].map( ( a ) => a.getBoundingClientRect().height ),
		} ) ) ),
	};
}

/** Пункты меню, подсвеченные как текущий раздел. */
async function activeMenu( page ) {
	return page.locator( '.main-nav__link.is-active' ).evaluateAll( ( els ) => els.map( ( el ) => el.textContent.trim() ) );
}

/**
 * Снимок пунктов крошек: прямоугольник от первого до последнего пункта
 * (у темы и макета разные отступы вокруг, сравниваются сами пункты).
 */
async function shootCrumbs( page, selector, file ) {
	const box = await page.locator( selector ).first().evaluate( ( ol ) => {
		const rects = [ ...ol.children ].map( ( li ) => li.getBoundingClientRect() );
		const left = Math.min( ...rects.map( ( r ) => r.left ) );
		const top = Math.min( ...rects.map( ( r ) => r.top ) );
		return { x: left + window.scrollX, y: top + window.scrollY, width: Math.max( ...rects.map( ( r ) => r.right ) ) - left, height: Math.max( ...rects.map( ( r ) => r.bottom ) ) - top };
	} );
	await page.screenshot( { path: file, clip: { x: Math.floor( box.x ), y: Math.floor( box.y ), width: Math.ceil( box.width ), height: Math.ceil( box.height ) } } );
}

( async () => {
	clearLog();
	const browser = await chromium.launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = [];
	page.on( 'pageerror', ( e ) => errors.push( e.message ) );

	const categoryId = wp( 'term', 'list', 'category', '--slug=tehnicheskiy-audit', '--field=term_id' );
	const postId = wp( 'post', 'create', '--post_type=post', `--post_title=${ MARK } статья`, '--post_status=publish', `--post_category=${ categoryId }`, '--porcelain' );
	const postUrl = wp( 'post', 'list', `--post__in=${ postId }`, '--field=url' );

	try {
		// --- Цепочки ---
		const cases = [
			[ '/otzyvy/', [ 'Главная', 'О нас', 'Отзывы о нас' ], 'Страница раздела «О нас»: «Раздел в хлебных крошках»', [ '/', '/o-kompanii/' ] ],
			[ '/o-kompanii/', [ 'Главная', 'О компании' ], 'Страница без родителя и раздела' ],
			[ '/seo/audit/', [ 'Главная', 'SEO', 'Технический аудит' ], 'Услуга: иерархия страниц и короткие названия', [ '/', '/seo/' ] ],
			[ '/seo/voprosy/', [ 'Главная', 'SEO', 'Вопросы по SEO' ], 'Страница вопросов: «Главная / SEO / Вопросы по SEO»' ],
			[ '/seo/voprosy/pochemu-yandeks-ne-indeksiruet-novye-stranicy-kataloga/', [ 'Главная', 'SEO', 'Вопросы по SEO', 'Почему Яндекс не индексирует новые страницы каталога?' ], 'Ответ: «Главная / SEO / Вопросы по SEO / Вопрос»', [ '/', '/seo/', '/seo/voprosy/' ] ],
			[ '/portfolio/novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir/', [ 'Главная', 'Портфолио', 'Ремонт квартир под ключ' ], 'Кейс: «Главная / Портфолио / Кейс» с коротким названием', [ '/', '/portfolio/' ] ],
			[ '/komanda/seo-senior/', [ 'Главная', 'Сотрудники', '[Имя Фамилия]' ], 'Сотрудник: «Главная / Сотрудники / Имя»', [ '/', '/komanda/' ] ],
			[ '/blog/rubrika/tehnicheskiy-audit/', [ 'Главная', 'Блог', 'Технический аудит' ], 'Рубрика: «Главная / Блог / Рубрика»', [ '/', '/blog/' ] ],
			[ '/blog/teg/robots-txt/', [ 'Главная', 'Блог', '#robots.txt' ], 'Тег: «Главная / Блог / #тег»' ],
			[ postUrl, [ 'Главная', 'Блог', 'Технический аудит', `${ MARK } статья` ], 'Статья: «Главная / Блог / Рубрика / Статья»', [ '/', '/blog/', '/blog/rubrika/tehnicheskiy-audit/' ] ],
			[ `/?s=${ encodeURIComponent( 'аудит' ) }`, [ 'Главная', 'Результаты поиска' ], 'Поиск: «Главная / Результаты поиска»' ],
		];
		for ( const [ url, expected, title, links ] of cases ) {
			const c = await crumbs( page, url );
			const linkPaths = c.links.map( ( href ) => href.replace( URL, '' ) );
			check( title, JSON.stringify( c.items ) === JSON.stringify( expected ) && c.current === expected[ expected.length - 1 ] && ( ! links || JSON.stringify( linkPaths ) === JSON.stringify( links ) ),
				`${ c.items.join( ' / ' ) } [${ linkPaths.join( ', ' ) }]` );
		}
		check( 'На главной крошек нет', ( await crumbs( page, '/' ) ).items.length === 0 );
		const notFound = await crumbs( page, '/net-takoy-stranicy/' );
		check( 'На 404 крошек нет', notFound.status === 404 && notFound.items.length === 0 );

		// --- Разметка и доступность ---
		const a11y = await crumbs( page, '/otzyvy/' );
		check( 'Разметка: nav «Хлебные крошки», разделители скрыты от дикторов, текущий — aria-current', a11y.label === 'Хлебные крошки' && a11y.separators && a11y.current === 'Отзывы о нас' );
		check( 'Ссылки крошек не ниже 44 px', a11y.linkHeights.every( ( h ) => h >= 44 ), a11y.linkHeights.join( ', ' ) );
		const h1s = [];
		for ( const url of [ '/blog/rubrika/tehnicheskiy-audit/', '/blog/teg/robots-txt/', `/?s=${ encodeURIComponent( 'аудит' ) }`, postUrl ] ) {
			await page.goto( url.startsWith( 'http' ) ? url : URL + url );
			h1s.push( await page.locator( 'h1' ).count() );
		}
		check( 'Рубрика, тег, поиск, статья: по одному H1', h1s.every( ( n ) => n === 1 ), h1s.join( ', ' ) );

		// --- Сверка с макетами ---
		for ( const [ url, mock, name ] of [
			[ '/otzyvy/', 'o-nas/otzyvy.html', 'Отзывы' ],
			[ '/komanda/seo-senior/', 'seo-blog/avtor.html', 'Сотрудник' ],
			[ '/seo/voprosy/pochemu-yandeks-ne-indeksiruet-novye-stranicy-kataloga/', 'seo-vopros/otvet.html', 'Ответ' ],
		] ) {
			const base = path.join( OUT, `breadcrumbs-${ name }` );
			await page.goto( URL + url, { waitUntil: 'networkidle' } );
			await settle( page );
			await shootCrumbs( page, 'nav.breadcrumbs ol', `${ base }-theme.png` );
			await page.goto( mockupUrl( mock ), { waitUntil: 'networkidle' } );
			await settle( page );
			await shootCrumbs( page, 'nav[aria-label="Хлебные крошки"] ol', `${ base }-mockup.png` );
			const diff = comparePng( `${ base }-mockup.png`, `${ base }-theme.png`, `${ base }-diff.png` );
			check( `Крошки как в макете: ${ name } (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
		}

		// --- Подсветка меню ---
		const menus = {};
		for ( const [ key, url ] of [
			[ 'case', '/portfolio/novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir/' ],
			[ 'question', '/seo/voprosy/pochemu-yandeks-ne-indeksiruet-novye-stranicy-kataloga/' ],
			[ 'category', '/blog/rubrika/tehnicheskiy-audit/' ],
			[ 'post', postUrl ],
			[ 'team', '/komanda/seo-senior/' ],
		] ) {
			await page.goto( url.startsWith( 'http' ) ? url : URL + url );
			menus[ key ] = await activeMenu( page );
		}
		check( 'Меню: у кейса подсвечено «Портфолио»', JSON.stringify( menus.case ) === '["Портфолио"]', menus.case.join() );
		check( 'Меню: у ответа — «SEO»', JSON.stringify( menus.question ) === '["SEO"]', menus.question.join() );
		check( 'Меню: у рубрики и статьи — «Блог»', JSON.stringify( menus.category ) === '["Блог"]' && JSON.stringify( menus.post ) === '["Блог"]', `${ menus.category } / ${ menus.post }` );
		check( 'Меню: у сотрудника — «О нас» (страница «Команда» в разделе)', JSON.stringify( menus.team ) === '["О нас"]', menus.team.join() );
		await page.goto( URL + '/seo/voprosy/pochemu-yandeks-ne-indeksiruet-novye-stranicy-kataloga/' );
		await page.locator( '.site-header' ).screenshot( { path: path.join( OUT, 'breadcrumbs-menu.png' ) } );
		await page.locator( '.page-default' ).screenshot( { path: path.join( OUT, 'breadcrumbs-page.png' ) } );

		// --- Поля в админке ---
		await login( page );
		const fieldsOn = async ( url ) => {
			await page.goto( url );
			await page.waitForTimeout( 2500 );
			return page.locator( '.acf-field[data-name]' ).evaluateAll( ( els ) => els.map( ( el ) => el.dataset.name ) );
		};
		const pageId = wp( 'eval', 'echo get_page_by_path( "otzyvy" )->ID;' );
		const onPage = await fieldsOn( `${ URL }/wp-admin/post.php?post=${ pageId }&action=edit` );
		check( 'Страница: «Название в хлебных крошках» и «Раздел в хлебных крошках»', onPage.includes( 'trust_crumb_title' ) && onPage.includes( 'trust_crumb_section' ) );
		const caseId = wp( 'post', 'list', '--post_type=case', '--name=novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir', '--field=ID' );
		const onCase = await fieldsOn( `${ URL }/wp-admin/post.php?post=${ caseId }&action=edit` );
		check( 'Кейс: только «Название в хлебных крошках» (раздел — у страниц)', onCase.includes( 'trust_crumb_title' ) && ! onCase.includes( 'trust_crumb_section' ) );
		const onTerm = await fieldsOn( `${ URL }/wp-admin/term.php?taxonomy=category&tag_ID=${ categoryId }` );
		check( 'Рубрика: полей крошек нет, SEO-поля есть', ! onTerm.includes( 'trust_crumb_title' ) && ! onTerm.includes( 'trust_crumb_section' ) && onTerm.includes( 'trust_seo_title' ) );
		check( 'Без ошибок JavaScript', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );
	} finally {
		wp( 'post', 'delete', postId, '--force' );
		await browser.close();
	}

	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
