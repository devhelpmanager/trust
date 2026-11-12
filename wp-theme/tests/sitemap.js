/**
 * Шаг 3.3: карта сайта и robots.txt.
 *
 * Запуск: node wp-theme/tests/sitemap.js (стенд запущен, демо-контент импортирован).
 *
 * Тест на время меняет настройки «SEO», флажки noindex и «Настройки → Чтение» и всё возвращает.
 */

const { URL, check, finish, wp, clearLog, checkLog, getSettings, setSettings } = require( './lib' );

/** Ответ сервера: код, тип, текст. */
async function get( url ) {
	const res = await fetch( url.startsWith( 'http' ) ? url : URL + url, { redirect: 'manual' } );
	return { status: res.status, type: res.headers.get( 'content-type' ) || '', text: await res.text() };
}

/** Адреса <loc> карты. */
function locs( xml ) {
	return [ ...xml.matchAll( /<loc>([^<]+)<\/loc>/g ) ].map( ( m ) => m[ 1 ] );
}

/** Карты из индекса (имя без префикса и номера: posts-page, taxonomies-post_tag…). */
async function sitemaps() {
	const index = await get( '/wp-sitemap.xml' );
	return locs( index.text ).map( ( url ) => url.replace( `${ URL }/wp-sitemap-`, '' ).replace( /-\d+\.xml$/, '' ) );
}

/** Удаляет метаполе группы «SEO» у записи или термина (вернуть как было). */
function clearMeta( kind, id, key ) {
	wp( 'eval', `delete_${ kind }_meta( ${ Number( id ) }, '${ key }' ); delete_${ kind }_meta( ${ Number( id ) }, '_${ key }' );` );
}

( async () => {
	clearLog();
	const saved = getSettings( [ 'seo_robots', 'seo_tag_threshold' ] );
	const aboutId = wp( 'eval', 'echo get_page_by_path( "o-kompanii" )->ID;' );
	const dupesId = wp( 'term', 'list', 'post_tag', '--slug=dubli', '--field=term_id' );

	try {
		// --- robots.txt ---
		let robots = await get( '/robots.txt' );
		check( 'robots.txt: текст по умолчанию — закрыты админка (кроме admin-ajax.php) и поиск',
			robots.status === 200 && [ 'User-agent: *', 'Disallow: /wp-admin/', 'Allow: /wp-admin/admin-ajax.php', 'Disallow: /?s=', 'Disallow: /search/' ].every( ( line ) => robots.text.includes( line ) ),
			robots.text.replace( /\n/g, ' | ' ) );
		check( 'robots.txt: строка Sitemap с адресом карты', robots.text.trim().endsWith( `Sitemap: ${ URL }/wp-sitemap.xml` ) );

		setSettings( { seo_robots: 'User-agent: Yandex\nDisallow: /private/\nSitemap: https://example.com/old.xml' } );
		robots = await get( '/robots.txt' );
		check( 'robots.txt: свой текст из настроек, своя строка Sitemap заменена на верную',
			robots.text.includes( 'User-agent: Yandex' ) && robots.text.includes( 'Disallow: /private/' ) && ! robots.text.includes( 'example.com/old.xml' ) && robots.text.trim().endsWith( `Sitemap: ${ URL }/wp-sitemap.xml` ),
			robots.text.replace( /\n/g, ' | ' ) );
		setSettings( { seo_robots: saved.seo_robots || '' } );

		// --- Индекс карты ---
		const index = await get( '/wp-sitemap.xml' );
		check( 'Карта сайта /wp-sitemap.xml отдаётся как XML', index.status === 200 && /xml/.test( index.type ) );
		const maps = await sitemaps();
		check( 'В карте: страницы, статьи, ответы, кейсы, сотрудники, рубрики, теги',
			[ 'posts-page', 'posts-post', 'posts-question', 'posts-case', 'posts-team', 'taxonomies-category', 'taxonomies-post_tag' ].every( ( m ) => maps.includes( m ) ), maps.join( ', ' ) );
		check( 'Нет пользователей, отзывов, вакансий, заявок, служебных типов',
			! maps.some( ( m ) => /users|review|vacancy|lead|wp_|nav_menu|attachment/.test( m ) ), maps.join( ', ' ) );

		// --- Записи ---
		const pages = await get( '/wp-sitemap-posts-page-1.xml' );
		const pageUrls = locs( pages.text );
		check( 'Страницы в карте, у каждой — дата изменения (lastmod)',
			pageUrls.includes( `${ URL }/seo/audit/` ) && pageUrls.includes( `${ URL }/o-kompanii/` ) && ( pages.text.match( /<lastmod>/g ) || [] ).length === pageUrls.length, `${ pageUrls.length } страниц` );
		const counts = JSON.parse( wp( 'eval', `echo wp_json_encode( array( 'case' => (int) wp_count_posts( 'case' )->publish, 'question' => (int) wp_count_posts( 'question' )->publish, 'team' => (int) wp_count_posts( 'team' )->publish ) );` ) );
		const caseUrls = locs( ( await get( '/wp-sitemap-posts-case-1.xml' ) ).text );
		const questionUrls = locs( ( await get( '/wp-sitemap-posts-question-1.xml' ) ).text );
		const teamUrls = locs( ( await get( '/wp-sitemap-posts-team-1.xml' ) ).text );
		check( 'Кейсы, ответы (только опубликованные), сотрудники — все в карте',
			caseUrls.length === counts.case && questionUrls.length === counts.question && teamUrls.length === counts.team && caseUrls.every( ( u ) => u.startsWith( `${ URL }/portfolio/` ) ),
			`кейсов ${ caseUrls.length }/${ counts.case }, ответов ${ questionUrls.length }/${ counts.question }, сотрудников ${ teamUrls.length }/${ counts.team }` );

		wp( 'eval', `update_field( Trust\\Fields::key( 'seo', 'trust_seo_noindex' ), 1, ${ Number( aboutId ) } );` );
		const withoutAbout = locs( ( await get( '/wp-sitemap-posts-page-1.xml' ) ).text );
		check( 'Страница с флажком «Не показывать в поиске» убрана из карты', ! withoutAbout.includes( `${ URL }/o-kompanii/` ) && withoutAbout.length === pageUrls.length - 1 );
		clearMeta( 'post', aboutId, 'trust_seo_noindex' );

		// --- Теги и порог ---
		const tags = JSON.parse( wp( 'eval', `echo wp_json_encode( array_map( static fn( $t ) => array( get_term_link( $t ), (int) $t->count ), get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) ) ) );` ) );
		const threshold = saved.seo_tag_threshold === null || saved.seo_tag_threshold === undefined || saved.seo_tag_threshold === '' ? 3 : Number( saved.seo_tag_threshold );
		const expected = tags.filter( ( [ , count ] ) => count >= threshold && count > 0 ).map( ( [ url ] ) => url ).sort();
		let tagUrls = locs( ( await get( '/wp-sitemap-taxonomies-post_tag-1.xml' ) ).text ).sort();
		check( `Теги: только с материалами от порога (${ threshold })`, JSON.stringify( tagUrls ) === JSON.stringify( expected ) && tagUrls.length > 0, tagUrls.join( ', ' ) );

		setSettings( { seo_tag_threshold: 0 } );
		tagUrls = locs( ( await get( '/wp-sitemap-taxonomies-post_tag-1.xml' ) ).text ).sort();
		check( 'Порог 0 — в карте все непустые теги', JSON.stringify( tagUrls ) === JSON.stringify( tags.filter( ( [ , c ] ) => c > 0 ).map( ( [ u ] ) => u ).sort() ), tagUrls.join( ', ' ) );
		setSettings( { seo_tag_threshold: saved.seo_tag_threshold ?? '' } );

		wp( 'eval', `update_field( Trust\\Fields::key( 'seo', 'trust_seo_noindex' ), 1, 'term_${ dupesId }' );` );
		tagUrls = locs( ( await get( '/wp-sitemap-taxonomies-post_tag-1.xml' ) ).text );
		check( 'Тег с флажком noindex убран из карты', ! tagUrls.some( ( u ) => u.endsWith( '/blog/teg/dubli/' ) ) );
		clearMeta( 'term', dupesId, 'trust_seo_noindex' );

		// --- Сайт закрыт от поисковиков ---
		wp( 'option', 'update', 'blog_public', '0' );
		robots = await get( '/robots.txt' );
		const closedIndex = await get( '/wp-sitemap.xml' );
		const closedPage = await get( '/o-kompanii/' );
		check( 'Сайт закрыт в «Настройки → Чтение»: robots.txt WordPress без Sitemap, карты нет, страницы с noindex',
			! robots.text.includes( 'Sitemap:' ) && closedIndex.status === 404 && /<meta name=.robots. content=.[^>]*noindex/.test( closedPage.text ),
			`${ robots.text.replace( /\n/g, ' | ' ) } / карта ${ closedIndex.status }` );
	} finally {
		wp( 'option', 'update', 'blog_public', '1' );
		setSettings( {
			seo_robots: saved.seo_robots || '',
			seo_tag_threshold: saved.seo_tag_threshold ?? '',
		} );
		clearMeta( 'post', aboutId, 'trust_seo_noindex' );
		clearMeta( 'term', dupesId, 'trust_seo_noindex' );
	}

	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
