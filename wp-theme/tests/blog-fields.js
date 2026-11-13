/**
 * Шаг 2.2: поля статей, рубрик и тегов; просмотры, лайки, главная статья.
 *
 * Запуск: node wp-theme/tests/blog-fields.js (стенд запущен, демо-контент импортирован).
 *
 * Тест создаёт свои статьи, рубрики и сотрудника (помечены метаполем _trust_test) и удаляет их
 * в конце. Демо-рубрики только открываются, не сохраняются — иначе импорт счёл бы их изменёнными.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, wp, clearLog, checkLog, login, ensureUser } = require( './lib' );

const MARK = 'ТестГлавной';

/** Выполняет PHP и разбирает JSON-ответ. */
function php( code ) {
	return JSON.parse( wp( 'eval', code ) );
}

/** Создаёт статью и помечает её как тестовую. */
function createPost( title, extra = [] ) {
	const id = wp( 'post', 'create', '--post_type=post', `--post_title=${ title }`, '--post_status=publish', '--porcelain', ...extra );
	wp( 'post', 'meta', 'update', id, '_trust_test', '1' );
	return id;
}

/** Создаёт рубрику и помечает её как тестовую. */
function createCategory( name ) {
	const id = wp( 'term', 'create', 'category', name, '--porcelain' );
	wp( 'term', 'meta', 'update', id, '_trust_test', '1' );
	return id;
}

/** ID главной статьи (Blog::featured). */
function featured( category = 0 ) {
	return wp( 'eval', `$p = Trust\\Blog::featured( ${ Number( category ) } ); echo $p ? $p->ID : 0;` );
}

/** Отмечает статью «Главной» так же, как это делает поле SCF. */
function setFeatured( id, on ) {
	wp( 'eval', `update_field( Trust\\Fields::key( 'post', Trust\\Blog::FEATURED ), ${ on ? 1 : 0 }, ${ Number( id ) } );` );
}

( async () => {
	clearLog();
	ensureUser( 'editor', 'editor' );
	ensureUser( 'author', 'author' );

	const created = { posts: [], terms: [] };
	const browser = await launch();

	try {
		// --- Регистрация ---
		const reg = php( `
			$post = acf_get_fields( 'group_trust_post' );
			$cat  = acf_get_fields( 'group_trust_category' );
			$group = acf_get_field_group( 'group_trust_post' );
			$meta = get_registered_meta_keys( 'post', 'post' );
			echo wp_json_encode( array(
				'post'      => array_map( fn( $f ) => array( $f['name'], $f['type'], $f['trust_cap'] ?? '' ), $post ),
				'position'  => $group['position'],
				'cat'       => array_map( fn( $f ) => array( $f['name'], $f['type'], $f['post_type'] ?? '' ), $cat ),
				'views'     => $meta['trust_views'] ?? null,
				'likes'     => $meta['trust_likes'] ?? null,
				'protected' => is_protected_meta( 'trust_views', 'post' ) && is_protected_meta( 'trust_likes', 'post' ),
				'catDesc'   => get_taxonomy( 'category' )->labels->desc_field_description,
				'tagDesc'   => get_taxonomy( 'post_tag' )->labels->desc_field_description,
			) );` );
		check( 'Статья: поле «Главная статья» (флажок, в боковой панели, только редактор и выше)',
			JSON.stringify( reg.post ) === JSON.stringify( [ [ 'trust_featured', 'true_false', 'edit_others_posts' ] ] ) && reg.position === 'side', JSON.stringify( reg.post ) );
		check( 'Рубрика: «Ведёт рубрику» (сотрудник) и «О рубрике» (текст)',
			JSON.stringify( reg.cat ) === JSON.stringify( [ [ 'trust_rubric_lead', 'post_object', [ 'team' ] ], [ 'trust_rubric_about', 'wysiwyg', '' ] ] ), JSON.stringify( reg.cat ) );
		check( 'Счётчики trust_views и trust_likes: целые, по умолчанию 0',
			reg.views?.type === 'integer' && reg.views.default === 0 && reg.likes?.type === 'integer' && reg.likes.default === 0 );
		check( 'Счётчики скрыты из «Произвольных полей»', reg.protected === true );
		check( 'Подсказки к описанию рубрики и тега: где выводится', reg.catDesc.includes( 'Подзаголовок под H1 на странице рубрики' ) && reg.tagDesc.includes( 'Подзаголовок под H1 на странице тега' ) );

		// --- Главная статья ---
		const c1 = createCategory( `${ MARK } рубрика 1` );
		const c2 = createCategory( `${ MARK } рубрика 2` );
		created.terms.push( c1, c2 );
		const p1 = createPost( `${ MARK } первая`, [ `--post_category=${ c1 }`, '--post_date=2026-11-10 10:00:00' ] );
		const p2 = createPost( `${ MARK } вторая`, [ `--post_category=${ c1 }`, '--post_date=2026-11-10 11:00:00' ] );
		const p3 = createPost( `${ MARK } третья`, [ `--post_category=${ c2 }`, '--post_date=2026-11-10 12:00:00' ] );
		created.posts.push( p1, p2, p3 );
		const latest = wp( 'post', 'list', '--post_type=post', '--post_status=publish', '--orderby=date', '--order=DESC', '--posts_per_page=1', '--field=ID' );

		check( 'Без отмеченных: главная рубрики — самая новая статья рубрики', featured( c1 ) === p2, `получено ${ featured( c1 ) }, ожидалось ${ p2 }` );
		check( 'Без отмеченных: главная блога — самая новая статья', featured() === latest, `получено ${ featured() }, ожидалось ${ latest }` );
		setFeatured( p1, true );
		check( 'Отмеченная «Главной» статья важнее более новой', featured( c1 ) === p1 && featured() === p1 );
		setFeatured( p3, true );
		check( 'Несколько отмеченных: в блоге — самая новая из них, в рубрике — своя', featured() === p3 && featured( c1 ) === p1 && featured( c2 ) === p3 );
		setFeatured( p3, false );
		setFeatured( p1, false );

		// --- Админка: администратор ---
		const page = await browser.newPage( { viewport: { width: 1440, height: 1000 } } );
		const errors = [];
		page.on( 'pageerror', ( e ) => errors.push( e.message ) );
		await login( page );

		// Флажок в редакторе блоков и сохранение.
		await page.goto( `${ URL }/wp-admin/post.php?post=${ p2 }&action=edit` );
		await page.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		await page.evaluate( () => {
			try {
				wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
			} catch ( e ) {}
		} );
		const toggle = page.locator( '.acf-field[data-name="trust_featured"]' );
		await toggle.waitFor( { timeout: 30000 } );
		const toggleText = await toggle.innerText();
		check( 'Редактор статьи: «Главная статья» с пояснением', toggleText.includes( 'Главная статья' ) && toggleText.includes( 'Новая статья' ), toggleText.replace( /\s+/g, ' ' ).slice( 0, 120 ) );
		await toggle.locator( '.acf-switch' ).click();
		await page.evaluate( () => wp.data.dispatch( 'core/editor' ).savePost() );
		await page.waitForFunction( () => {
			const editor = wp.data.select( 'core/editor' );
			const post = wp.data.select( 'core/edit-post' );
			return ! editor.isSavingPost() && ! post.isSavingMetaBoxes();
		}, null, { timeout: 30000 } );
		await page.waitForTimeout( 1000 );
		await page.screenshot( { path: path.join( OUT, 'blog-fields-post.png' ) } );
		check( 'Сохранение из редактора: статья отмечена «Главной»', wp( 'post', 'meta', 'get', p2, 'trust_featured' ) === '1' && featured( c1 ) === p2 );

		// Список статей: колонки и сортировка.
		wp( 'post', 'meta', 'update', p1, 'trust_views', '5' );
		wp( 'post', 'meta', 'update', p2, 'trust_views', '50' );
		wp( 'post', 'meta', 'update', p2, 'trust_likes', '7' );
		await page.goto( `${ URL }/wp-admin/edit.php?s=${ encodeURIComponent( MARK ) }&orderby=trust_views&order=desc` );
		const heads = ( await page.locator( '.wp-list-table thead th' ).allInnerTexts() ).map( ( s ) => s.trim().split( '\n' )[ 0 ] );
		check( 'Список статей: колонки «Главная», «Просмотры», «Лайки»', [ 'Главная', 'Просмотры', 'Лайки' ].every( ( h ) => heads.includes( h ) ), heads.join( ', ' ) );
		const rows = await page.locator( '#the-list tr' ).evaluateAll( ( trs ) => trs.map( ( tr ) => ( {
			title: tr.querySelector( '.row-title' )?.textContent.trim(),
			featured: tr.querySelector( '.column-trust_featured' )?.textContent.trim(),
			views: tr.querySelector( '.column-trust_views' )?.textContent.trim(),
			likes: tr.querySelector( '.column-trust_likes' )?.textContent.trim(),
		} ) ) );
		check( 'Сортировка по просмотрам: 50, 5, без просмотров — статья без счётчика не пропадает',
			rows.length === 3 && rows[ 0 ].title.endsWith( 'вторая' ) && rows[ 1 ].title.endsWith( 'первая' ) && rows[ 2 ].title.endsWith( 'третья' ),
			rows.map( ( r ) => `${ r.title }:${ r.views }` ).join( ', ' ) );
		check( 'Значения колонок: ★ у главной, просмотры и лайки', rows[ 0 ].featured.includes( '★' ) && rows[ 0 ].views === '50' && rows[ 0 ].likes === '7' && rows[ 2 ].views === '0' && ! rows[ 1 ].featured.includes( '★' ), JSON.stringify( rows[ 0 ] ) );
		await page.screenshot( { path: path.join( OUT, 'blog-fields-list.png' ) } );

		// Демо-рубрика: поля и подсказки (только просмотр).
		const auditId = wp( 'term', 'list', 'category', '--slug=tehnicheskiy-audit', '--field=term_id' );
		await page.goto( `${ URL }/wp-admin/term.php?taxonomy=category&tag_ID=${ auditId }` );
		const form = await page.locator( '#edittag' ).innerText();
		check( 'Экран рубрики: «Ведёт рубрику» и «О рубрике»', form.includes( 'Ведёт рубрику' ) && form.includes( 'О рубрике' ) );
		check( 'Экран рубрики: подсказки к названию и описанию', form.includes( 'Заголовок H1 страницы рубрики' ) && form.includes( 'Подзаголовок под H1 на странице рубрики' ) );
		const about = await page.locator( '.acf-field[data-name="trust_rubric_about"] textarea' ).inputValue();
		check( 'Демо «О рубрике» из макета, ссылка на страницу аудита', about.includes( 'Технический аудит — проверка' ) && about.includes( `href="${ URL }/seo/audit/"` ), about.slice( 0, 80 ) );
		await page.screenshot( { path: path.join( OUT, 'blog-fields-category.png' ), fullPage: true } );

		// Своя рубрика: выбор ведущего через интерфейс и сохранение.
		const leadId = wp( 'post', 'create', '--post_type=team', `--post_title=${ MARK } Анна`, '--post_status=publish', '--porcelain' );
		created.posts.push( leadId );
		await page.goto( `${ URL }/wp-admin/term.php?taxonomy=category&tag_ID=${ c1 }` );
		await page.locator( '.acf-field[data-name="trust_rubric_lead"] .select2-selection' ).click();
		await page.locator( '.select2-search__field' ).fill( 'Анна' );
		await page.locator( '.select2-results__option', { hasText: `${ MARK } Анна` } ).click();
		await Promise.all( [ page.waitForLoadState( 'networkidle' ), page.locator( '.edit-tag-actions [type="submit"]' ).click() ] );
		await page.waitForTimeout( 500 );
		const lead = wp( 'eval', `echo (int) get_field( 'trust_rubric_lead', 'term_${ c1 }' );` );
		check( 'Рубрика: «Ведёт рубрику» выбирается из сотрудников и сохраняется', lead === leadId, `получено ${ lead }, ожидалось ${ leadId }` );

		// Тег: подсказки, без полей рубрики.
		const robotsId = wp( 'term', 'list', 'post_tag', '--slug=robots-txt', '--field=term_id' );
		await page.goto( `${ URL }/wp-admin/term.php?taxonomy=post_tag&tag_ID=${ robotsId }` );
		const tagForm = await page.locator( '#edittag' ).innerText();
		check( 'Экран тега: подсказка к описанию, полей рубрики нет', tagForm.includes( 'Подзаголовок под H1 на странице тега' ) && ! tagForm.includes( 'Ведёт рубрику' ) );
		check( 'Админка без ошибок JavaScript', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );

		// --- Автор: флажка нет ---
		const authorId = wp( 'user', 'get', 'author', '--field=ID' );
		const own = createPost( `${ MARK } авторская`, [ `--post_author=${ authorId }`, '--post_status=draft' ] );
		created.posts.push( own );
		await login( page, 'author', 'author' );
		await page.goto( `${ URL }/wp-admin/post.php?post=${ own }&action=edit` );
		await page.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		await page.waitForTimeout( 2000 );
		check( 'Автор не видит флажок «Главная статья»', ( await page.locator( '.acf-field[data-name="trust_featured"]' ).count() ) === 0 );

		// --- Демо-рубрики и теги ---
		const cats = wp( 'term', 'list', 'category', '--meta_key=_trust_demo_id', '--field=slug' ).split( '\n' ).sort();
		check( 'Демо-рубрики из макета блога (7) и «Отчёты» из «Нашего блога» на странице услуги', cats.join() === [ 'ai', 'geo-prodvizhenie', 'indeksaciya', 'kontekstnaya-reklama', 'otchety', 'sayty', 'seo', 'tehnicheskiy-audit' ].sort().join(), cats.join( ', ' ) );
		const tags = wp( 'term', 'list', 'post_tag', '--meta_key=_trust_demo_id', '--field=name' ).split( '\n' ).sort();
		check( 'Демо-теги из макета рубрики (6)', tags.length === 6 && tags.includes( 'robots.txt' ) && tags.includes( 'коды ответа' ), tags.join( ', ' ) );
		const robotsDesc = wp( 'term', 'get', 'post_tag', robotsId, '--field=description' );
		check( 'Описание тега robots.txt из макета', robotsDesc.startsWith( 'Всё о файле robots.txt' ) );
		const res = await page.goto( `${ URL }/blog/rubrika/tehnicheskiy-audit/` );
		check( 'Страница демо-рубрики открывается', res.status() === 200 );
	} finally {
		for ( const id of created.posts ) {
			try {
				wp( 'post', 'delete', id, '--force' );
			} catch ( e ) {}
		}
		for ( const id of created.terms ) {
			try {
				wp( 'term', 'delete', 'category', id );
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
