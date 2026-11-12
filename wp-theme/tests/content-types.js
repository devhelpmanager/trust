/**
 * Шаг 2.1: типы записей, таксономии, адреса, транслитерация.
 *
 * Запуск: node wp-theme/tests/content-types.js (стенд запущен, демо-контент импортирован).
 *
 * Тест создаёт свои записи, термины и файл (помечены метаполем _trust_test) и удаляет их в конце.
 * Вкладку «Адреса» меняет через админку и возвращает прежнее значение.
 */

const { chromium } = require( 'playwright' );
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );
const { URL, OUT, check, finish, wp, clearLog, checkLog, login, collectConsoleErrors, ensureUser, getSettings, setSettings } = require( './lib' );

/** Код ответа без перехода по редиректам. */
async function status( url ) {
	const res = await fetch( url.startsWith( 'http' ) ? url : URL + url, { redirect: 'manual' } );
	return res.status;
}

/** Создаёт запись и помечает её как тестовую. */
function createPost( type, title, extra = [] ) {
	const id = wp( 'post', 'create', `--post_type=${ type }`, `--post_title=${ title }`, '--post_status=publish', '--porcelain', ...extra );
	wp( 'post', 'meta', 'update', id, '_trust_test', '1' );
	return id;
}

/** Создаёт термин и помечает его как тестовый. */
function createTerm( taxonomy, name ) {
	const id = wp( 'term', 'create', taxonomy, name, '--porcelain' );
	wp( 'term', 'meta', 'update', id, '_trust_test', '1' );
	return id;
}

/** Данные о регистрации типов записей и таксономий. */
function registry() {
	const php = `
		$out = array();
		foreach ( array( 'case', 'review', 'team', 'vacancy', 'question', 'lead' ) as $t ) {
			$o = get_post_type_object( $t );
			$out['types'][ $t ] = $o ? array(
				'label'   => $o->label,
				'public'  => (bool) $o->public,
				'query'   => (bool) $o->publicly_queryable,
				'archive' => (bool) $o->has_archive,
				'rest'    => (bool) $o->show_in_rest,
				'slug'    => $o->rewrite ? $o->rewrite['slug'] : false,
				'front'   => $o->rewrite ? (bool) $o->rewrite['with_front'] : null,
				'cap'     => $o->cap->edit_posts,
				'create'  => $o->cap->create_posts,
				'icon'    => $o->menu_icon,
			) : null;
		}
		foreach ( array( 'category', 'post_tag', 'service_type', 'industry', 'department', 'question_topic' ) as $t ) {
			$o = get_taxonomy( $t );
			$out['tax'][ $t ] = $o ? array(
				'label'   => $o->label,
				'objects' => $o->object_type,
				'public'  => (bool) $o->public,
				'rest'    => (bool) $o->show_in_rest,
				'slug'    => $o->rewrite ? $o->rewrite['slug'] : false,
			) : null;
		}
		$out['post'] = get_post_type_object( 'post' )->labels->name;
		$out['post_add'] = get_post_type_object( 'post' )->labels->add_new_item;
		echo wp_json_encode( $out );`;
	return JSON.parse( wp( 'eval', php ) );
}

( async () => {
	clearLog();
	ensureUser( 'editor', 'editor' );
	ensureUser( 'author', 'author' );
	ensureUser( 'contributor', 'contributor' );

	const savedUrls = getSettings( [ 'urls_case' ] );
	const created = { posts: [], terms: [] };

	const browser = await chromium.launch();

	try {
		// --- Регистрация ---
		const r = registry();
		const t = r.types;
		check( 'Типы записей зарегистрированы с русскими названиями',
			t.case?.label === 'Кейсы' && t.review?.label === 'Отзывы' && t.team?.label === 'Сотрудники' &&
			t.vacancy?.label === 'Вакансии' && t.question?.label === 'Вопросы SEO' && t.lead?.label === 'Заявки',
			Object.entries( t ).map( ( [ k, v ] ) => `${ k }=${ v && v.label }` ).join( ', ' ) );
		check( 'Публичные: кейсы, сотрудники, вопросы; без архивов, в редакторе блоков',
			[ 'case', 'team', 'question' ].every( ( k ) => t[ k ].public && ! t[ k ].archive && t[ k ].rest ) );
		check( 'Непубличные: отзывы, вакансии, заявки (нет адресов на сайте)',
			[ 'review', 'vacancy', 'lead' ].every( ( k ) => ! t[ k ].public && ! t[ k ].query && false === t[ k ].slug ) );
		check( 'Ярлыки: portfolio, komanda, seo/voprosy, без префикса /blog/',
			t.case.slug === 'portfolio' && t.team.slug === 'komanda' && t.question.slug === 'seo/voprosy' &&
			[ 'case', 'team', 'question' ].every( ( k ) => false === t[ k ].front ) );
		check( 'Права: кейсы, отзывы, сотрудники, вакансии — как у страниц; вопросы — как у статей',
			[ 'case', 'review', 'team', 'vacancy' ].every( ( k ) => t[ k ].cap === 'edit_pages' ) && t.question.cap === 'edit_posts' );
		check( 'Заявки: только редактор и администратор, создать вручную нельзя', t.lead.cap === 'edit_others_posts' && t.lead.create === 'do_not_allow' );
		check( 'У каждого типа своя иконка в меню', new Set( Object.values( t ).map( ( v ) => v.icon ) ).size === 6 );

		const x = r.tax;
		check( 'Таксономии и их типы записей',
			x.service_type?.label === 'Услуги' && x.service_type.objects.join() === 'case,review' &&
			x.industry?.label === 'Отрасли' && x.industry.objects.join() === 'case' &&
			x.department?.label === 'Отделы' && x.department.objects.join() === 'team,vacancy' &&
			x.question_topic?.label === 'Темы вопросов' && x.question_topic.objects.join() === 'question',
			JSON.stringify( Object.fromEntries( Object.entries( x ).map( ( [ k, v ] ) => [ k, v && v.objects ] ) ) ) );
		check( 'Новые таксономии без публичных страниц, с панелью в редакторе',
			[ 'service_type', 'industry', 'department', 'question_topic' ].every( ( k ) => ! x[ k ].public && x[ k ].rest && false === x[ k ].slug ) );
		check( 'Теги общие для статей и вопросов', x.post_tag.objects.includes( 'post' ) && x.post_tag.objects.includes( 'question' ) );
		check( 'Рубрики /blog/rubrika/, теги /blog/teg/', x.category.slug === 'blog/rubrika' && x.post_tag.slug === 'blog/teg', `${ x.category.slug }, ${ x.post_tag.slug }` );
		check( '«Записи» → «Статьи», «Метки» → «Теги»', r.post === 'Статьи' && r.post_add === 'Добавить статью' && x.post_tag.label === 'Теги', `${ r.post }, ${ r.post_add }, ${ x.post_tag.label }` );

		// --- Транслитерация ---
		const caseId = createPost( 'case', 'Ремонт квартир: тест' );
		const teamId = createPost( 'team', 'Иван Петров (тест)' );
		const questionId = createPost( 'question', 'Почему страница выпала из индекса? Тест' );
		const postId = createPost( 'post', 'Щука съела ёжика: тест' );
		const reviewId = createPost( 'review', 'Отзыв тест' );
		const vacancyId = createPost( 'vacancy', 'Вакансия тест' );
		const leadId = createPost( 'lead', 'Заявка тест' );
		created.posts.push( caseId, teamId, questionId, postId, reviewId, vacancyId, leadId );
		const slug = ( id ) => wp( 'post', 'get', id, '--field=post_name' );

		check( 'Транслитерация ярлыка записи: «Ремонт квартир: тест» → remont-kvartir-test', slug( caseId ) === 'remont-kvartir-test', slug( caseId ) );
		check( 'Транслитерация: щ, ъ, ё — «Щука съела ёжика» → schuka-sela-ezhika-test', slug( postId ) === 'schuka-sela-ezhika-test', slug( postId ) );
		check( 'Транслитерация: «Почему страница выпала из индекса?» → pochemu-stranica-vypala-iz-indeksa-test', slug( questionId ) === 'pochemu-stranica-vypala-iz-indeksa-test', slug( questionId ) );

		const catId = createTerm( 'category', 'Тестовая рубрика' );
		const tagId = createTerm( 'post_tag', 'Тестовый тег' );
		created.terms.push( [ 'category', catId ], [ 'post_tag', tagId ] );
		const catSlug = wp( 'term', 'get', 'category', catId, '--field=slug' );
		const tagSlug = wp( 'term', 'get', 'post_tag', tagId, '--field=slug' );
		check( 'Транслитерация ярлыков рубрик и тегов', catSlug === 'testovaya-rubrika' && tagSlug === 'testovyy-teg', `${ catSlug }, ${ tagSlug }` );
		check( 'Демо-термины: латинские ярлыки из названий',
			wp( 'term', 'list', 'question_topic', '--name=Индексация', '--field=slug' ) === 'indeksaciya' &&
			wp( 'term', 'list', 'service_type', '--name=Контекстная реклама', '--field=slug' ) === 'kontekstnaya-reklama' );

		// Кириллический ярлык, созданный до темы (WordPress хранит его как %d1%81…).
		const oldSlug = encodeURIComponent( 'старая-запись' ).toLowerCase();
		const oldId = createPost( 'post', 'Старая запись', [ `--post_name=${ oldSlug }` ] );
		created.posts.push( oldId );
		wp( 'post', 'update', oldId, '--post_title=Старая запись (изменена)' );
		check( 'Существующий кириллический ярлык не меняется при сохранении', slug( oldId ) === oldSlug, slug( oldId ) );
		check( 'Запись со старым кириллическим адресом открывается', await status( `/blog/${ oldSlug }/` ) === 200 );

		// Имя загружаемого файла.
		const tmp = fs.mkdtempSync( path.join( os.tmpdir(), 'trust-' ) );
		const file = path.join( tmp, 'Логотип Компании.png' );
		fs.copyFileSync( path.join( __dirname, '..', 'trust', 'screenshot.png' ), file );
		const mediaId = wp( 'media', 'import', file, '--porcelain' );
		created.posts.push( mediaId );
		const attached = wp( 'post', 'meta', 'get', mediaId, '_wp_attached_file' );
		fs.rmSync( tmp, { recursive: true, force: true } );
		check( 'Транслитерация имени файла: «Логотип Компании.png» → logotip-kompanii.png', attached.endsWith( '/logotip-kompanii.png' ), attached );

		// --- Адреса ---
		const link = ( id ) => wp( 'post', 'list', `--post__in=${ id }`, '--post_type=any', '--field=url' );
		check( 'Адрес кейса /portfolio/<кейс>/', link( caseId ) === `${ URL }/portfolio/remont-kvartir-test/`, link( caseId ) );
		check( 'Адрес сотрудника /komanda/<сотрудник>/', link( teamId ) === `${ URL }/komanda/ivan-petrov-test/`, link( teamId ) );
		check( 'Адрес вопроса /seo/voprosy/<вопрос>/', link( questionId ) === `${ URL }/seo/voprosy/pochemu-stranica-vypala-iz-indeksa-test/`, link( questionId ) );

		const page = await browser.newPage();
		const errors = collectConsoleErrors( page );
		for ( const [ name, url ] of [ [ 'кейс', link( caseId ) ], [ 'сотрудник', link( teamId ) ], [ 'вопрос', link( questionId ) ] ] ) {
			const res = await page.goto( url );
			const h1 = await page.locator( 'h1' ).first().innerText().catch( () => '' );
			check( `Страница записи открывается: ${ name }`, res.status() === 200 && h1.toLowerCase().includes( 'тест' ), `${ res.status() } ${ h1 }` );
		}
		check( 'Рубрика /blog/rubrika/<рубрика>/ открывается', await status( '/blog/rubrika/testovaya-rubrika/' ) === 200 );
		check( 'Тег /blog/teg/<тег>/ открывается', await status( '/blog/teg/testovyy-teg/' ) === 200 );
		check( 'Старый адрес рубрики /blog/category/… не работает', await status( '/blog/category/testovaya-rubrika/' ) === 404 );

		const hidden = [];
		for ( const id of [ reviewId, vacancyId, leadId ] ) {
			hidden.push( await status( `/?p=${ id }` ) );
		}
		check( 'Отзывы, вакансии и заявки не открываются на сайте (?p=ID → 404)', hidden.every( ( s ) => s === 404 ), hidden.join( ', ' ) );
		check( 'Заявки не отдаются через REST API', await status( `/wp-json/wp/v2/lead/${ leadId }` ) === 404 );

		check( 'Страницы-списки открываются: /portfolio/, /komanda/, /seo/voprosy/',
			( await Promise.all( [ '/portfolio/', '/komanda/', '/seo/voprosy/' ].map( status ) ) ).every( ( s ) => s === 200 ) );
		check( 'Вторая страница списка /portfolio/page/2/ — страница «Портфолио», а не 404', await status( '/portfolio/page/2/' ) === 200 );
		const pageUrls = wp( 'post', 'list', '--post_type=page', '--post_status=publish', '--field=url', '--posts_per_page=-1' ).split( '\n' );
		const pageCodes = await Promise.all( pageUrls.map( status ) );
		const broken = pageUrls.filter( ( u, i ) => pageCodes[ i ] !== 200 );
		check( `Все страницы сайта открываются (${ pageUrls.length })`, broken.length === 0, broken.join( ', ' ) );
		check( 'Консоль без ошибок на страницах записей', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );

		// --- Админка ---
		const admin = await browser.newPage( { viewport: { width: 1440, height: 1000 } } );
		const adminErrors = [];
		admin.on( 'pageerror', ( e ) => adminErrors.push( e.message ) );
		// Название пункта без счётчика «на утверждении» (он внутри .awaiting-mod).
		const menu = async () => admin.locator( '#adminmenu .wp-menu-name' ).evaluateAll( ( els ) => els.map( ( el ) => {
			const copy = el.cloneNode( true );
			copy.querySelectorAll( '.awaiting-mod, .update-plugins' ).forEach( ( bubble ) => bubble.remove() );
			return copy.textContent.trim();
		} ) );
		const ALL = [ 'Заявки', 'Статьи', 'Вопросы SEO', 'Кейсы', 'Отзывы', 'Сотрудники', 'Вакансии' ];

		await login( admin );
		await admin.goto( `${ URL }/wp-admin/` );
		const adminMenu = await menu();
		check( 'Меню администратора: Заявки, Статьи, Вопросы SEO, Кейсы, Отзывы, Сотрудники, Вакансии', ALL.every( ( m ) => adminMenu.includes( m ) ), adminMenu.join( ', ' ) );
		const order = ALL.map( ( m ) => adminMenu.indexOf( m ) );
		check( 'Порядок в меню: Заявки вверху, вопросы рядом со статьями, кейсы и остальные после страниц', order.every( ( v, i ) => i === 0 || v > order[ i - 1 ] ), order.join( ', ' ) );

		await admin.goto( `${ URL }/wp-admin/edit.php?post_type=lead` );
		check( 'Заявки: в списке нет кнопки «Добавить»', ( await admin.locator( '.page-title-action' ).count() ) === 0 );
		const newLead = await admin.goto( `${ URL }/wp-admin/post-new.php?post_type=lead` );
		check( 'Заявки: создать вручную нельзя даже администратору', newLead.status() === 403 );

		await admin.goto( `${ URL }/wp-admin/edit-tags.php?taxonomy=industry&post_type=case` );
		const industries = await admin.locator( '#the-list tr .row-title' ).allInnerTexts();
		check( 'Отрасли в админке: 8 демо-терминов из макета портфолио', industries.length === 8 && industries.includes( 'Медицина' ), industries.join( ', ' ) );

		await admin.goto( `${ URL }/wp-admin/edit.php?post_type=case` );
		const columns = await admin.locator( '.wp-list-table thead th' ).allInnerTexts();
		check( 'Список кейсов: колонки «Услуги» и «Отрасли»', columns.includes( 'Услуги' ) && columns.includes( 'Отрасли' ), columns.join( ', ' ) );

		await admin.goto( `${ URL }/wp-admin/options-permalink.php` );
		check( 'Постоянные ссылки: подсказка, что адреса задаются в «Настройки сайта → Адреса»', ( await admin.locator( '.notice-info', { hasText: 'Настройки сайта → Адреса' } ).count() ) === 1 );

		// Редактор блоков у кейса: панели таксономий.
		await admin.goto( `${ URL }/wp-admin/post-new.php?post_type=case` );
		await admin.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		await admin.evaluate( () => {
			try {
				wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
			} catch ( e ) {}
		} );
		await admin.waitForTimeout( 1500 );
		const sidebar = await admin.locator( '.interface-complementary-area' ).innerText().catch( () => '' );
		check( 'Редактор кейса: панели «Услуги» и «Отрасли», кнопка «Выбрать обложку»', [ 'Услуги', 'Отрасли', 'Выбрать обложку' ].every( ( s ) => sidebar.includes( s ) ), sidebar.slice( 0, 200 ).replace( /\s+/g, ' ' ) );
		await admin.screenshot( { path: path.join( OUT, 'content-types-case-editor.png' ) } );

		// Вкладка «Адреса»: только администратор, смена ярлыка работает сразу.
		await admin.goto( `${ URL }/wp-admin/admin.php?page=trust-settings` );
		const urlsTab = admin.locator( '.acf-tab-wrap .acf-tab-button', { hasText: 'Адреса' } );
		check( 'Настройки: вкладка «Адреса» у администратора', ( await urlsTab.count() ) === 1 );
		await urlsTab.click();
		await admin.screenshot( { path: path.join( OUT, 'content-types-urls.png' ), fullPage: true } );
		const field = admin.locator( '[data-name="urls_case"] input' );
		await field.fill( ' Мои Кейсы ' );
		await Promise.all( [ admin.waitForNavigation(), admin.locator( '#publish, input[type="submit"][name="publish"], .acf-settings-wrap input[type="submit"]' ).first().click() ] );
		const stored = wp( 'option', 'get', 'options_urls_case' );
		check( 'Ярлык при сохранении переводится в латиницу: « Мои Кейсы » → moi-keysy', stored === 'moi-keysy', stored );
		check( 'Новый адрес кейса работает сразу, без сброса правил вручную', await status( '/moi-keysy/remont-kvartir-test/' ) === 200 && link( caseId ) === `${ URL }/moi-keysy/remont-kvartir-test/` );
		check( 'Старый адрес кейса больше не работает', await status( '/portfolio/remont-kvartir-test/' ) === 404 );
		setSettings( { urls_case: savedUrls.urls_case || '' } );
		check( 'Пустое поле — снова /portfolio/', await status( '/portfolio/remont-kvartir-test/' ) === 200 );
		check( 'Админка без ошибок JavaScript', adminErrors.length === 0, adminErrors.slice( 0, 3 ).join( ' | ' ) );

		await login( admin, 'editor', 'editor' );
		await admin.goto( `${ URL }/wp-admin/` );
		const editorMenu = await menu();
		check( 'Меню редактора: все разделы, включая заявки', ALL.every( ( m ) => editorMenu.includes( m ) ), editorMenu.join( ', ' ) );
		await admin.goto( `${ URL }/wp-admin/admin.php?page=trust-settings` );
		check( 'Настройки: у редактора вкладки «Адреса» нет', ( await admin.locator( '.acf-tab-wrap .acf-tab-button', { hasText: 'Адреса' } ).count() ) === 0 );

		await login( admin, 'author', 'author' );
		await admin.goto( `${ URL }/wp-admin/` );
		const authorMenu = await menu();
		check( 'Меню автора: статьи и вопросы; нет заявок, кейсов, отзывов, сотрудников, вакансий',
			authorMenu.includes( 'Статьи' ) && authorMenu.includes( 'Вопросы SEO' ) &&
			[ 'Заявки', 'Кейсы', 'Отзывы', 'Сотрудники', 'Вакансии' ].every( ( m ) => ! authorMenu.includes( m ) ), authorMenu.join( ', ' ) );
		const authorLeads = await admin.goto( `${ URL }/wp-admin/edit.php?post_type=lead` );
		check( 'Автор не может открыть список заявок', authorLeads.status() === 403 );

		await login( admin, 'contributor', 'contributor' );
		await admin.goto( `${ URL }/wp-admin/` );
		check( 'Участник не видит заявки', ! ( await menu() ).includes( 'Заявки' ) );
	} finally {
		setSettings( { urls_case: savedUrls.urls_case || '' } );
		for ( const id of created.posts ) {
			try {
				wp( 'post', 'delete', id, '--force' );
			} catch ( e ) {}
		}
		for ( const [ taxonomy, id ] of created.terms ) {
			try {
				wp( 'term', 'delete', taxonomy, id );
			} catch ( e ) {}
		}
		await browser.close();
	}

	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
