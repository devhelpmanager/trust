/**
 * Шаг 3.4: микроразметка schema.org — общий граф, BreadcrumbList, Service, FAQPage.
 *
 * Запуск: node wp-theme/tests/schema.js (стенд запущен, демо-контент импортирован).
 *
 * Тест на время меняет настройки (соцсети, логотип, тип организации, география, координаты),
 * создаёт страницу с двумя блоками «Вопрос-ответ» и mu-плагин для проверки фильтра —
 * и всё убирает в конце.
 */

const fs = require( 'fs' );
const path = require( 'path' );
const { URL, DEV_DIR, THEME, check, finish, wp, clearLog, checkLog, getSettings, setSettings } = require( './lib' );

const MARK = 'ТестРазметки';
const MU_PLUGIN = path.join( DEV_DIR, 'wordpress/wp-content/mu-plugins/trust-test-schema.php' );

/** Разметка страницы: скрипты, граф, сущности по типу. */
async function schema( url ) {
	const res = await fetch( url.startsWith( 'http' ) ? url : URL + url );
	const html = await res.text();
	const scripts = [ ...html.matchAll( /<script type="application\/ld\+json">(.*?)<\/script>/gs ) ].map( ( m ) => m[ 1 ] );
	let graph = [];
	let valid = true;
	try {
		graph = scripts.length ? JSON.parse( scripts[ 0 ] )[ '@graph' ] : [];
	} catch ( e ) {
		valid = false;
	}
	const types = ( node ) => [].concat( node[ '@type' ] );
	return {
		status: res.status,
		html,
		scripts,
		valid,
		graph,
		byType: ( type ) => graph.find( ( node ) => types( node ).includes( type ) ),
		types: graph.map( ( node ) => types( node ).join( '+' ) ),
	};
}

/** Все ссылки {"@id": …} внутри узлов, кроме собственных @id. */
function references( value, out = [] ) {
	if ( Array.isArray( value ) ) {
		value.forEach( ( v ) => references( v, out ) );
	} else if ( value && typeof value === 'object' ) {
		const keys = Object.keys( value );
		if ( keys.length === 1 && keys[ 0 ] === '@id' ) {
			out.push( value[ '@id' ] );
		} else {
			keys.filter( ( k ) => k !== '@id' ).forEach( ( k ) => references( value[ k ], out ) );
		}
	}
	return out;
}

( async () => {
	clearLog();
	const saved = getSettings( [ 'social_links', 'company_logo_light', 'seo_org_type', 'seo_area_served', 'geo', 'address' ] );
	let pageId = null;
	let logoId = null;

	try {
		// --- Общие правила на всех видах страниц ---
		const urls = [ '/seo/audit/', '/kontakty/', '/o-kompanii/', '/portfolio/', '/komanda/', '/otzyvy/', '/seo/voprosy/', '/karera/', '/oferta/', '/blog/rubrika/tehnicheskiy-audit/', '/blog/teg/robots-txt/', `/?s=${ encodeURIComponent( 'аудит' ) }`, '/', '/portfolio/novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir/', '/komanda/seo-senior/' ];
		const problems = [];
		for ( const url of urls ) {
			const s = await schema( url );
			const ids = s.graph.map( ( n ) => n[ '@id' ] ).filter( Boolean );
			const missing = references( s.graph ).filter( ( id ) => ! ids.includes( id ) );
			if ( s.scripts.length !== 1 || ! s.valid ) {
				problems.push( `${ url }: скриптов ${ s.scripts.length }, JSON ${ s.valid ? 'ок' : 'ошибка' }` );
			}
			if ( new Set( ids ).size !== ids.length ) {
				problems.push( `${ url }: повторяются @id` );
			}
			if ( missing.length ) {
				problems.push( `${ url }: ссылки на несуществующие @id ${ missing.join( ', ' ) }` );
			}
			if ( ! s.byType( 'Organization' ) || ! s.byType( 'WebSite' ) ) {
				problems.push( `${ url }: нет Organization или WebSite` );
			}
			if ( /"@type":"(Review|AggregateRating|HowTo|JobPosting)"/.test( s.scripts[ 0 ] || '' ) ) {
				problems.push( `${ url }: запрещённый тип` );
			}
			if ( /<\//.test( s.scripts[ 0 ] || '' ) ) {
				problems.push( `${ url }: «</» внутри JSON` );
			}
		}
		check( `Все страницы (${ urls.length }): один JSON-LD с @graph, валидный JSON, без повторов @id и висячих ссылок, без Review/AggregateRating`, problems.length === 0, problems.join( ' | ' ) );

		// --- Организация и сайт ---
		let s = await schema( '/seo/audit/' );
		const org = s.byType( 'Organization' );
		check( 'Organization: имя, адрес сайта, телефон, email, PostalAddress, contactPoint',
			org.name === '[Бренд]' && org.url === `${ URL }/` && org.telephone && org.email && org.address?.[ '@type' ] === 'PostalAddress' && org.contactPoint?.[ 0 ]?.[ '@type' ] === 'ContactPoint',
			JSON.stringify( org ) );
		check( 'Пустых свойств нет: без соцсетей и логотипа нет sameAs и logo', ! ( 'sameAs' in org ) && ! ( 'logo' in org ) );
		const site = s.byType( 'WebSite' );
		check( 'WebSite: адрес, имя, язык ru, publisher → организация', site.inLanguage === 'ru' && site.publisher[ '@id' ] === `${ URL }/#organization` );

		logoId = wp( 'media', 'import', path.join( THEME, 'screenshot.png' ), `--title=${ MARK } логотип`, '--porcelain' );
		setSettings( {
			social_links: [ { network: 'telegram', url: 'https://t.me/example' }, { network: 'youtube', url: 'https://youtube.com/@example' } ],
			company_logo_light: Number( logoId ),
		} );
		s = await schema( '/seo/audit/' );
		const org2 = s.byType( 'Organization' );
		check( 'Соцсети → sameAs, логотип → ImageObject с размерами',
			JSON.stringify( org2.sameAs ) === JSON.stringify( [ 'https://t.me/example', 'https://youtube.com/@example' ] ) && org2.logo?.[ '@type' ] === 'ImageObject' && org2.logo.width > 0 && org2.logo.height > 0,
			JSON.stringify( [ org2.sameAs, org2.logo ] ) );

		setSettings( { seo_org_type: 'ProfessionalService' } );
		s = await schema( '/seo/audit/' );
		check( 'Тип «Компания с офисом» при заполненном адресе → ProfessionalService', !! s.byType( 'ProfessionalService' ) && ! s.byType( 'Organization' ) );
		setSettings( { address: { city: '', street: '', office: '', postal_code: '' } } );
		s = await schema( '/seo/audit/' );
		check( 'Без адреса — всё равно Organization и без address', !! s.byType( 'Organization' ) && ! ( 'address' in s.byType( 'Organization' ) ) );
		setSettings( { address: saved.address, seo_org_type: saved.seo_org_type || 'Organization' } );

		// --- Страница услуги: WebPage + FAQPage, крошки, Service ---
		s = await schema( '/seo/audit/' );
		const page = s.graph.find( ( n ) => [].concat( n[ '@type' ] ).includes( 'FAQPage' ) );
		check( 'Услуга с блоком «Вопрос-ответ»: страница — ["WebPage","FAQPage"], имя = title, язык, isPartOf, даты',
			JSON.stringify( page?.[ '@type' ] ) === '["WebPage","FAQPage"]' && page.name === 'Технический аудит сайта — [Бренд]' && page.inLanguage === 'ru' && page.isPartOf[ '@id' ] === `${ URL }/#website` && !! page.datePublished && !! page.dateModified,
			JSON.stringify( page && { type: page[ '@type' ], name: page.name } ) );
		const visible = await fetch( `${ URL }/seo/audit/` ).then( ( r ) => r.text() ).then( ( html ) => [ ...html.matchAll( /<span class="b-faq__num">\d+<\/span>\s*<span>([^<]+)<\/span>/g ) ].map( ( m ) => m[ 1 ].trim() ) );
		const names = page.mainEntity.map( ( q ) => q.name );
		check( 'FAQPage: те же вопросы, что видны на странице, у каждого Answer',
			names.length === 6 && JSON.stringify( names ) === JSON.stringify( visible ) && page.mainEntity.every( ( q ) => q[ '@type' ] === 'Question' && q.acceptedAnswer[ '@type' ] === 'Answer' && q.acceptedAnswer.text ),
			names.join( ' | ' ) );
		const crumbs = s.byType( 'BreadcrumbList' );
		const items = crumbs.itemListElement;
		check( 'BreadcrumbList = видимые крошки: позиции, названия, item у всех, кроме последнего',
			items.map( ( i ) => i.name ).join( ' / ' ) === 'Главная / SEO / Технический аудит' && items.every( ( i, n ) => i.position === n + 1 ) &&
			items[ 0 ].item === `${ URL }/` && items[ 1 ].item === `${ URL }/seo/` && ! ( 'item' in items[ 2 ] ) && page.breadcrumb[ '@id' ] === crumbs[ '@id' ],
			JSON.stringify( items ) );
		const service = s.byType( 'Service' );
		check( 'Service: имя (H1), вид услуги, адрес, provider → организация; без географии, пока не заполнена',
			service?.name === 'Технический аудит сайта' && service.serviceType === 'Технический аудит' && service.url === `${ URL }/seo/audit/` && service.provider[ '@id' ].endsWith( '#organization' ) && ! ( 'areaServed' in service ),
			JSON.stringify( service ) );
		setSettings( { seo_area_served: 'Россия' } );
		s = await schema( '/seo/audit/' );
		check( 'География услуг из настроек → areaServed', s.byType( 'Service' ).areaServed === 'Россия' );
		setSettings( { seo_area_served: saved.seo_area_served || '' } );

		// --- Типы страниц ---
		const typeOf = async ( url ) => ( await schema( url ) ).graph.map( ( n ) => [].concat( n[ '@type' ] ) ).find( ( t ) => /Page$/.test( t[ 0 ] ) )?.join( '+' );
		const kinds = {
			about: await typeOf( '/o-kompanii/' ),
			contacts: await typeOf( '/kontakty/' ),
			portfolio: await typeOf( '/portfolio/' ),
			team: await typeOf( '/komanda/' ),
			reviews: await typeOf( '/otzyvy/' ),
			questions: await typeOf( '/seo/voprosy/' ),
			career: await typeOf( '/karera/' ),
			document: await typeOf( '/oferta/' ),
			category: await typeOf( '/blog/rubrika/tehnicheskiy-audit/' ),
			tag: await typeOf( '/blog/teg/robots-txt/' ),
			search: await typeOf( `/?s=${ encodeURIComponent( 'аудит' ) }` ),
		};
		check( 'Типы страниц: AboutPage, ContactPage, CollectionPage (портфолио, команда, отзывы, вопросы, рубрика, тег), WebPage (карьера, документ), SearchResultsPage',
			kinds.about === 'AboutPage' && kinds.contacts === 'ContactPage' && [ kinds.portfolio, kinds.team, kinds.reviews, kinds.questions, kinds.category, kinds.tag ].every( ( k ) => k === 'CollectionPage' ) &&
			kinds.career === 'WebPage' && kinds.document === 'WebPage' && kinds.search === 'SearchResultsPage', JSON.stringify( kinds ) );

		setSettings( { geo: { lat: '55.751244', lng: '37.618423' } } );
		s = await schema( '/kontakty/' );
		const contactOrg = s.byType( 'Organization' );
		check( 'Контакты: Organization с координатами и ссылкой на карту, страница — about организации',
			contactOrg.geo?.[ '@type' ] === 'GeoCoordinates' && contactOrg.geo.latitude === 55.751244 && /yandex/.test( contactOrg.hasMap || '' ) && s.byType( 'ContactPage' ).about[ '@id' ].endsWith( '#organization' ),
			JSON.stringify( [ contactOrg.geo, contactOrg.hasMap ] ) );
		s = await schema( '/seo/audit/' );
		check( 'Координаты и карта — только на странице контактов', ! ( 'geo' in s.byType( 'Organization' ) ) && ! ( 'hasMap' in s.byType( 'Organization' ) ) );
		setSettings( { geo: saved.geo || { lat: '', lng: '' } } );

		s = await schema( '/' );
		check( 'Главная: без BreadcrumbList', ! s.byType( 'BreadcrumbList' ) );
		s = await schema( '/net-takoy-stranicy/' );
		check( '404: только Organization и WebSite, без пустых свойств', s.status === 404 && s.types.join() === 'Organization,WebSite' && ! /null|\[\]/.test( s.scripts[ 0 ] ), s.types.join() );

		// --- Два блока «Вопрос-ответ» — один FAQPage ---
		const content = wp( 'eval', `echo Trust\\Blocks::serialize( 'faq', array( 'title' => 'Первый', 'questions' => array( array( 'question' => 'Вопрос один?', 'answer' => '<p>Ответ <strong>один</strong>.</p><div>лишний тег</div><script>alert(1)</script>' ), array( 'question' => 'Общий вопрос?', 'answer' => '<p>Да.</p>' ) ) ) ) . "\\n\\n" . Trust\\Blocks::serialize( 'faq', array( 'title' => 'Второй', 'questions' => array( array( 'question' => 'Вопрос два?', 'answer' => '<p>Ответ два.</p>' ), array( 'question' => 'Общий вопрос?', 'answer' => '<p>Да.</p>' ) ) ) );` );
		const contentFile = path.join( DEV_DIR, 'trust-test-schema-content.html' );
		fs.writeFileSync( contentFile, content );
		pageId = wp( 'eval', `echo wp_insert_post( wp_slash( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => '${ MARK } два FAQ', 'post_content' => file_get_contents( '${ contentFile }' ) ) ) );` );
		fs.rmSync( contentFile, { force: true } );
		s = await schema( wp( 'post', 'list', '--post_type=page', `--post__in=${ pageId }`, '--field=url' ) );
		const faqPages = s.graph.filter( ( n ) => [].concat( n[ '@type' ] ).includes( 'FAQPage' ) );
		const q = faqPages[ 0 ]?.mainEntity || [];
		check( 'Два блока «Вопрос-ответ» — один FAQPage со всеми вопросами без повторов', faqPages.length === 1 && q.map( ( x ) => x.name ).join( ' | ' ) === 'Вопрос один? | Общий вопрос? | Вопрос два?', q.map( ( x ) => x.name ).join( ' | ' ) );
		const answer = q[ 0 ]?.acceptedAnswer.text || '';
		check( 'Ответ в разметке — только простые теги (p, strong…), без div и script',
			answer.includes( '<p>Ответ <strong>один</strong>.</p>' ) && ! /<(div|script)/i.test( answer ) && ( answer.match( /<\/?([a-z0-9]+)/gi ) || [] ).every( ( t ) => /^<\/?(p|br|ul|ol|li|a|b|strong|i|em)$/i.test( t ) ),
			answer.replace( /\n/g, ' ' ) );

		// --- Фильтр trust_schema_graph ---
		fs.writeFileSync( MU_PLUGIN, "<?php\nadd_filter( 'trust_schema_graph', static function ( $graph ) {\n\tif ( isset( $_GET['trust_no_schema'] ) ) {\n\t\treturn array();\n\t}\n\t$graph[] = array( '@type' => 'Thing', '@id' => home_url( '/#extra' ), 'name' => 'Дополнение' );\n\treturn $graph;\n} );\n" );
		s = await schema( '/seo/audit/' );
		const extra = s.graph.find( ( n ) => n[ '@id' ] === `${ URL }/#extra` );
		s = await schema( '/seo/audit/?trust_no_schema=1' );
		check( 'Фильтр trust_schema_graph: дополнить граф или отключить разметку', !! extra && s.scripts.length === 0 );
	} finally {
		fs.rmSync( MU_PLUGIN, { force: true } );
		setSettings( {
			social_links: saved.social_links || [],
			company_logo_light: saved.company_logo_light || '',
			seo_org_type: saved.seo_org_type || 'Organization',
			seo_area_served: saved.seo_area_served || '',
			geo: saved.geo || { lat: '', lng: '' },
			address: saved.address,
		} );
		for ( const id of [ pageId, logoId ].filter( Boolean ) ) {
			try {
				wp( 'post', 'delete', id, '--force' );
			} catch ( e ) {}
		}
	}

	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
