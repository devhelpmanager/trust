/**
 * Шаг 3.1: SEO-поля и вывод в <head> — title, description, canonical, robots, Open Graph, H1;
 * счётчики и предпросмотр в админке, напоминание об alt.
 *
 * Запуск: node wp-theme/tests/seo.js (стенд запущен, демо-контент импортирован).
 *
 * Тест создаёт свои записи и картинку (заголовки начинаются с «ТестСЕО»), меняет настройки
 * «SEO» и всё возвращает в конце.
 */

const { chromium } = require( 'playwright' );
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );
const { URL, OUT, THEME, check, finish, wp, clearLog, checkLog, login, ensureUser, getSettings, setSettings } = require( './lib' );

const MARK = 'ТестСЕО';

/** Выполняет PHP и разбирает JSON-ответ. */
function php( code ) {
	return JSON.parse( wp( 'eval', code ) );
}

/** Записывает поле группы «SEO» записи или термина ('term_ID'). */
function seoField( target, name, value ) {
	wp( 'eval', `update_field( Trust\\Fields::key( 'seo', '${ name }' ), ${ JSON.stringify( value ) }, ${ JSON.stringify( target ) } );` );
}

/** Загружает страницу и разбирает <head>. */
async function head( url ) {
	const res = await fetch( url.startsWith( 'http' ) ? url : URL + url, { redirect: 'manual' } );
	const html = await res.text();
	const headHtml = html.split( '</head>' )[ 0 ];
	const meta = {};
	for ( const m of headHtml.matchAll( /<meta (name|property)=["']([^"']+)["'] content=["']([^"']*)["']/g ) ) {
		( meta[ m[ 2 ] ] = meta[ m[ 2 ] ] || [] ).push( m[ 3 ].replace( /&quot;/g, '"' ).replace( /&#039;/g, "'" ).replace( /&amp;/g, '&' ) );
	}
	const one = ( key ) => ( meta[ key ] || [] )[ 0 ];
	return {
		status: res.status,
		title: ( headHtml.match( /<title>([^<]*)<\/title>/ ) || [] )[ 1 ],
		titles: ( headHtml.match( /<title>/g ) || [] ).length,
		canonicals: [ ...headHtml.matchAll( /<link rel=["']canonical["'] href=["']([^"']+)["']/g ) ].map( ( m ) => m[ 1 ] ),
		descriptions: ( meta.description || [] ).length,
		description: one( 'description' ),
		robots: one( 'robots' ) || '',
		og: ( key ) => one( `og:${ key }` ),
		meta: one,
		h1: ( html.match( /<h1[^>]*>([^<]*)</ ) || [] )[ 1 ],
	};
}

( async () => {
	clearLog();
	ensureUser( 'editor', 'editor' );
	const saved = getSettings( [ 'seo_title_post', 'seo_separator', 'seo_tag_threshold' ] );
	const browser = await chromium.launch();

	try {
		// --- Поля и настройки ---
		const reg = php( `
			$group = acf_get_field_group( 'group_trust_seo' );
			echo wp_json_encode( array(
				'fields'    => array_values( array_filter( wp_list_pluck( acf_get_fields( 'group_trust_seo' ), 'name' ) ) ),
				'keys'      => wp_list_pluck( acf_get_fields( 'group_trust_seo' ), 'key' ),
				'locations' => array_map( static fn( $rule ) => $rule[0]['param'] . ':' . $rule[0]['value'], $group['location'] ),
				'trim'      => array( Trust\\Seo::trim_text( str_repeat( 'Проверка поисковых сниппетов рр ', 12 ) ), Trust\\Seo::trim_text( 'Короткий текст.' ) ),
				'fill'      => Trust\\Seo::fill( '{title} {sep} {site}', array( 'title' => '' ) ),
			) );` );
		check( 'Группа «SEO»: title, description, H1, canonical, noindex, nofollow, Open Graph, предпросмотр',
			[ 'trust_seo_title', 'trust_seo_description', 'trust_seo_h1', 'trust_seo_canonical', 'trust_seo_noindex', 'trust_seo_nofollow', 'trust_og_title', 'trust_og_description', 'trust_og_image' ].every( ( n ) => reg.fields.includes( n ) ) &&
			reg.keys.includes( 'field_trust_seo_trust_seo_preview' ), reg.fields.join( ', ' ) );
		check( 'Группа «SEO» у страниц, статей, кейсов, ответов, сотрудников, рубрик, тегов',
			[ 'post_type:page', 'post_type:post', 'post_type:case', 'post_type:question', 'post_type:team', 'taxonomy:category', 'taxonomy:post_tag' ].every( ( l ) => reg.locations.includes( l ) ), reg.locations.join( ', ' ) );
		check( 'Обрезка описания: до 160 символов по слову, с многоточием, кириллица цела',
			[ ...reg.trim[ 0 ] ].length <= 160 && reg.trim[ 0 ].endsWith( '…' ) && ! reg.trim[ 0 ].includes( '�' ) && reg.trim[ 1 ] === 'Короткий текст.', reg.trim[ 0 ] );
		check( 'Пустой {title}: лишний разделитель по краю убирается', reg.fill === '[Бренд]', reg.fill );

		// --- Страница: шаблон по умолчанию, Open Graph, без описания ---
		let h = await head( '/seo/audit/' );
		check( 'Страница: title по шаблону «{title} — [Бренд]»', h.title === 'Технический аудит сайта — [Бренд]', h.title );
		check( 'В <head> по одному title и canonical, canonical — адрес страницы', h.titles === 1 && h.canonicals.length === 1 && h.canonicals[ 0 ] === `${ URL }/seo/audit/`, h.canonicals.join( ' ' ) );
		check( 'Open Graph: locale, type, title, url, site_name; twitter:card',
			h.og( 'locale' ) === 'ru_RU' && h.og( 'type' ) === 'website' && h.og( 'title' ) === h.title && h.og( 'url' ) === `${ URL }/seo/audit/` && h.og( 'site_name' ) === '[Бренд]' && h.meta( 'twitter:card' ) === 'summary',
			JSON.stringify( [ h.og( 'locale' ), h.og( 'type' ), h.og( 'site_name' ), h.meta( 'twitter:card' ) ] ) );

		// --- Статья: анонс, обложка, поля SEO ---
		const tmp = fs.mkdtempSync( path.join( os.tmpdir(), 'trust-' ) );
		const file = path.join( tmp, 'seo-cover.png' );
		fs.copyFileSync( path.join( THEME, 'screenshot.png' ), file );
		const imageId = wp( 'media', 'import', file, `--title=${ MARK } обложка`, '--porcelain' );
		fs.rmSync( tmp, { recursive: true, force: true } );
		const postId = wp( 'post', 'create', '--post_type=post', `--post_title=${ MARK } статья`, '--post_status=publish', '--post_excerpt=Короткий анонс статьи для поиска.', '--post_content=<!-- wp:paragraph --><p>Текст статьи.</p><!-- /wp:paragraph -->', '--porcelain' );
		wp( 'post', 'meta', 'update', postId, '_thumbnail_id', imageId );
		wp( 'post', 'meta', 'update', imageId, '_wp_attachment_image_alt', 'Обложка тестовой статьи' );
		const postUrl = wp( 'post', 'list', `--post__in=${ postId }`, '--field=url' );

		h = await head( postUrl );
		check( 'Статья: description из анонса', h.description === 'Короткий анонс статьи для поиска.', h.description );
		check( 'Статья: og:type article, даты публикации', h.og( 'type' ) === 'article' && !! h.meta( 'article:published_time' ) && !! h.meta( 'article:modified_time' ) );
		check( 'og:image из обложки с размерами и alt, twitter:card summary_large_image',
			/seo-cover/.test( h.og( 'image' ) || '' ) && Number( h.og( 'image:width' ) ) > 0 && Number( h.og( 'image:height' ) ) > 0 && h.og( 'image:alt' ) === 'Обложка тестовой статьи' && h.meta( 'twitter:card' ) === 'summary_large_image',
			JSON.stringify( [ h.og( 'image' ), h.og( 'image:width' ), h.og( 'image:alt' ) ] ) );

		seoField( postId, 'trust_seo_title', 'Свой заголовок {sep} {site}' );
		seoField( postId, 'trust_seo_description', 'Своё описание для поиска.' );
		seoField( postId, 'trust_og_title', 'Заголовок для соцсетей' );
		seoField( postId, 'trust_seo_canonical', `${ URL }/seo/audit/` );
		h = await head( postUrl );
		check( 'Поля SEO: свой title с {sep} и {site}, description, og:title, canonical',
			h.title === 'Свой заголовок — [Бренд]' && h.description === 'Своё описание для поиска.' && h.og( 'title' ) === 'Заголовок для соцсетей' && h.canonicals[ 0 ] === `${ URL }/seo/audit/`,
			JSON.stringify( [ h.title, h.description, h.og( 'title' ), h.canonicals[ 0 ] ] ) );
		seoField( postId, 'trust_seo_noindex', 1 );
		h = await head( postUrl );
		check( 'noindex: noindex, follow', /noindex/.test( h.robots ) && /follow/.test( h.robots ) && ! /nofollow/.test( h.robots ), h.robots );
		seoField( postId, 'trust_seo_nofollow', 1 );
		h = await head( postUrl );
		check( 'noindex + nofollow', /noindex/.test( h.robots ) && /nofollow/.test( h.robots ), h.robots );

		// --- Настройки: шаблон и разделитель ---
		seoField( postId, 'trust_seo_title', '' );
		setSettings( {
			seo_title_post: '{title} {sep} статья блога {site}',
			seo_separator: '|',
		} );
		h = await head( postUrl );
		check( 'Шаблон и разделитель из «Настроек сайта → SEO»', h.title === `${ MARK } статья | статья блога [Бренд]`, h.title );
		setSettings( {
			seo_title_post: saved.seo_title_post || '',
			seo_separator: saved.seo_separator || '—',
		} );

		// --- Страница: H1 из поля, H1 по умолчанию ---
		const pageId = wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK } страница`, '--post_status=publish', '--porcelain' );
		const pageUrl = wp( 'post', 'list', '--post_type=page', `--post__in=${ pageId }`, '--field=url' );
		h = await head( pageUrl );
		check( 'H1 по умолчанию — заголовок страницы', h.h1.trim() === `${ MARK } страница`, h.h1 );
		seoField( pageId, 'trust_seo_h1', 'Свой H1 {site}' );
		h = await head( pageUrl );
		check( 'H1 из поля «H1» (с {site})', h.h1.trim() === 'Свой H1 [Бренд]', h.h1 );

		// --- Рубрика, тег, порог ---
		h = await head( '/blog/rubrika/tehnicheskiy-audit/' );
		check( 'Рубрика: «… — блог [Бренд]», description из описания рубрики, canonical',
			h.title === 'Технический аудит — блог [Бренд]' && h.description.startsWith( 'Как найти и исправить' ) && h.canonicals[ 0 ] === `${ URL }/blog/rubrika/tehnicheskiy-audit/` && ! /noindex/.test( h.robots ) );
		h = await head( '/blog/rubrika/tehnicheskiy-audit/?sort=popular' );
		check( 'Рубрика с ?sort=popular: canonical без параметра', h.canonicals[ 0 ] === `${ URL }/blog/rubrika/tehnicheskiy-audit/`, h.canonicals[ 0 ] );
		const auditId = wp( 'term', 'list', 'category', '--slug=tehnicheskiy-audit', '--field=term_id' );
		const auditTitle = wp( 'eval', `echo get_term_meta( ${ auditId }, 'trust_seo_title', true );` );
		seoField( `term_${ auditId }`, 'trust_seo_title', 'Рубрика: свой title' );
		h = await head( '/blog/rubrika/tehnicheskiy-audit/' );
		check( 'Рубрика: свой SEO title из поля термина', h.title === 'Рубрика: свой title', h.title );
		// Вернуть как было: пустое значение — удалить метаполе (иначе импорт сочтёт рубрику изменённой).
		if ( auditTitle ) {
			seoField( `term_${ auditId }`, 'trust_seo_title', auditTitle );
		} else {
			wp( 'eval', `delete_term_meta( ${ auditId }, 'trust_seo_title' ); delete_term_meta( ${ auditId }, '_trust_seo_title' );` );
		}

		h = await head( '/blog/teg/robots-txt/' );
		check( 'Тег: «#… — блог [Бренд]»; материалов меньше порога 3 — noindex, follow', h.title === '#robots.txt — блог [Бренд]' && /noindex/.test( h.robots ) && /follow/.test( h.robots ), `${ h.title } / ${ h.robots }` );
		setSettings( { seo_tag_threshold: 0 } );
		h = await head( '/blog/teg/robots-txt/' );
		check( 'Порог 0 — тег индексируется', ! /noindex/.test( h.robots ), h.robots );
		setSettings( { seo_tag_threshold: saved.seo_tag_threshold ?? '' } );

		// --- Поиск, 404, пагинация ---
		h = await head( `/?s=${ encodeURIComponent( 'аудит' ) }` );
		check( 'Поиск: «Поиск: «аудит» — [Бренд]», noindex, без canonical', h.title === 'Поиск: «аудит» — [Бренд]' && /noindex/.test( h.robots ) && h.canonicals.length === 0, `${ h.title } / ${ h.robots }` );
		h = await head( '/net-takoy-stranicy/' );
		check( '404: код 404, «Страница не найдена — [Бренд]», noindex', h.status === 404 && h.title === 'Страница не найдена — [Бренд]' && /noindex/.test( h.robots ), `${ h.status } ${ h.title }` );
		h = await head( '/portfolio/page/2/' );
		check( 'Пагинация: « — страница 2» в title, свой canonical', h.title === 'Портфолио — [Бренд] — страница 2' && h.canonicals[ 0 ] === `${ URL }/portfolio/page/2/`, `${ h.title } / ${ h.canonicals[ 0 ] }` );

		// --- Демо: title и H1 из макетов ---
		h = await head( '/karera/' );
		const karera = h;
		h = await head( '/portfolio/' );
		check( 'Демо: «Карьера в [Бренд] — вакансии и стажировка», H1 «Работа в [Бренд]»; H1 портфолио «Кейсы и проекты»',
			karera.title === 'Карьера в [Бренд] — вакансии и стажировка' && karera.h1.trim() === 'Работа в [Бренд]' && h.h1.trim() === 'Кейсы и проекты',
			JSON.stringify( [ karera.title, karera.h1, h.h1 ] ) );

		// --- Админка: счётчики и предпросмотр ---
		const page = await browser.newPage( { viewport: { width: 1440, height: 1100 } } );
		const errors = [];
		page.on( 'pageerror', ( e ) => errors.push( e.message ) );
		await login( page );
		await page.goto( `${ URL }/wp-admin/post.php?post=${ postId }&action=edit` );
		await page.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/editor' ).getCurrentPostId(), null, { timeout: 60000 } );
		await page.evaluate( () => {
			try {
				wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
			} catch ( e ) {}
		} );
		const preview = page.locator( '.trust-seo-preview' );
		await preview.locator( '.trust-seo-preview__title' ).waitFor( { timeout: 30000 } );
		check( 'Предпросмотр: адрес, title по шаблону, описание',
			( await preview.locator( '.trust-seo-preview__title' ).innerText() ) === `${ MARK } статья — [Бренд]` &&
			( await preview.locator( '.trust-seo-preview__url' ).innerText() ).startsWith( 'localhost:8080 › blog' ) &&
			( await preview.locator( '.trust-seo-preview__desc' ).innerText() ) === 'Своё описание для поиска.' );
		await page.evaluate( ( title ) => wp.data.dispatch( 'core/editor' ).editPost( { title } ), `${ MARK } новый заголовок` );
		await page.waitForTimeout( 300 );
		check( 'Предпросмотр меняется вместе с заголовком записи', ( await preview.locator( '.trust-seo-preview__title' ).innerText() ) === `${ MARK } новый заголовок — [Бренд]` );
		const titleInput = page.locator( '.acf-field[data-name="trust_seo_title"] input' );
		await titleInput.fill( 'Очень длинный заголовок страницы для проверки подсветки счётчика {site}' );
		const count = page.locator( '.acf-field[data-name="trust_seo_title"] .trust-seo-count' );
		check( 'Счётчик title: число символов и подсветка после 60', ( await count.innerText() ).endsWith( '/ 60' ) && ( await count.getAttribute( 'class' ) ).includes( 'is-over' ) );
		check( 'Предпросмотр: свой title с {site}', ( await preview.locator( '.trust-seo-preview__title' ).innerText() ).endsWith( 'счётчика [Бренд]' ) );
		await titleInput.fill( 'Короткий' );
		check( 'Счётчик description есть, подсветка снимается при коротком title',
			( await page.locator( '.acf-field[data-name="trust_seo_description"] .trust-seo-count' ).innerText() ).endsWith( '/ 160' ) && ! ( await count.getAttribute( 'class' ) ).includes( 'is-over' ) );
		await page.locator( '.interface-complementary-area' ).screenshot( { path: path.join( OUT, 'seo-sidebar.png' ) } );

		await page.goto( `${ URL }/wp-admin/term.php?taxonomy=category&tag_ID=${ auditId }` );
		await page.locator( '.trust-seo-preview__title' ).waitFor();
		check( 'Экран рубрики: группа «SEO» и предпросмотр', ( await page.locator( '.trust-seo-preview__title' ).innerText() ) === 'Технический аудит — блог [Бренд]' );
		await page.fill( '#name', 'Технический аудит 2' );
		check( 'Предпросмотр рубрики меняется вместе с названием', ( await page.locator( '.trust-seo-preview__title' ).innerText() ) === 'Технический аудит 2 — блог [Бренд]' );

		// --- Вкладка «SEO» — только администратору ---
		await page.goto( `${ URL }/wp-admin/admin.php?page=trust-settings` );
		const tab = page.locator( '.acf-tab-wrap .acf-tab-button', { hasText: 'SEO' } );
		check( 'Настройки: вкладка «SEO» у администратора', ( await tab.count() ) === 1 );
		await tab.click();
		const seoTab = await page.locator( 'body' ).innerText();
		check( 'Вкладка «SEO»: шаблоны, разделитель, порог тегов, картинка для соцсетей',
			[ 'Шаблоны заголовков', 'Рубрики', 'Разделитель', 'Порог индексации тегов', 'Картинка для соцсетей по умолчанию' ].every( ( s ) => seoTab.includes( s ) ) );
		await page.screenshot( { path: path.join( OUT, 'seo-settings.png' ), fullPage: true } );

		// --- Медиатека: alt ---
		const noAlt = wp( 'media', 'import', path.join( THEME, 'screenshot.png' ), `--title=${ MARK } без alt`, '--porcelain' );
		await page.goto( `${ URL }/wp-admin/upload.php?mode=list&s=${ encodeURIComponent( MARK ) }` );
		const altCells = await page.locator( '#the-list tr' ).evaluateAll( ( trs ) => Object.fromEntries( trs.map( ( tr ) => [ tr.id.replace( 'post-', '' ), tr.querySelector( '.column-trust_alt' )?.textContent.trim() ] ) ) );
		check( 'Медиатека: колонка Alt — текст или «Нет — заполните»', altCells[ imageId ] === 'Обложка тестовой статьи' && altCells[ noAlt ] === 'Нет — заполните', JSON.stringify( altCells ) );
		await page.goto( `${ URL }/wp-admin/post.php?post=${ noAlt }&action=edit` );
		check( 'Картинка без alt: предупреждение на экране файла', ( await page.locator( '.trust-alt-missing' ).count() ) === 1 );
		check( 'Админка без ошибок JavaScript', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );

		await login( page, 'editor', 'editor' );
		await page.goto( `${ URL }/wp-admin/admin.php?page=trust-settings` );
		check( 'Редактор не видит вкладку «SEO»', ( await page.locator( '.acf-tab-wrap .acf-tab-button', { hasText: 'SEO' } ).count() ) === 0 );
	} finally {
		setSettings( {
			seo_title_post: saved.seo_title_post || '',
			seo_separator: saved.seo_separator || '—',
			seo_tag_threshold: saved.seo_tag_threshold ?? '',
		} );
		const leftovers = wp( 'eval', `echo implode( ' ', wp_list_pluck( array_filter( get_posts( array( 'post_type' => array( 'post', 'page', 'attachment' ), 'post_status' => 'any', 'numberposts' => -1 ) ), static fn( $p ) => str_starts_with( $p->post_title, '${ MARK }' ) ), 'ID' ) );` );
		for ( const id of leftovers.split( ' ' ).filter( Boolean ) ) {
			try {
				wp( 'post', 'delete', id, '--force' );
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
