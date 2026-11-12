/**
 * Шаг 2.3: поля кейсов и отзывов.
 *
 * Запуск: node wp-theme/tests/cases-reviews.js (стенд запущен, демо-контент импортирован).
 *
 * Тест создаёт свои кейс и отзывы (помечены метаполем _trust_test) и удаляет их в конце.
 * Демо-записи только открываются, не сохраняются — иначе импорт счёл бы их изменёнными.
 */

const { chromium } = require( 'playwright' );
const path = require( 'path' );
const { URL, OUT, check, finish, wp, clearLog, checkLog, login } = require( './lib' );

const MARK = 'ТестОтзывов';

/** Выполняет PHP и разбирает JSON-ответ. */
function php( code ) {
	return JSON.parse( wp( 'eval', code ) );
}

/** Создаёт запись и помечает её как тестовую. */
function createPost( type, title, extra = [] ) {
	const id = wp( 'post', 'create', `--post_type=${ type }`, `--post_title=${ title }`, '--porcelain', ...extra );
	wp( 'post', 'meta', 'update', id, '_trust_test', '1' );
	return id;
}

/** Видимые поля SCF на странице (имена). */
async function visibleFields( page ) {
	return page.locator( '.acf-field[data-name]' ).evaluateAll( ( els ) => els
		.filter( ( el ) => ! el.classList.contains( 'acf-hidden' ) && el.offsetParent !== null )
		.map( ( el ) => el.dataset.name ) );
}

/** Нажимает «Опубликовать» и ждёт, пока SCF проверит поля. */
async function publish( page ) {
	await page.click( '#publish' );
	await page.waitForFunction( () => ! document.querySelector( '#publish' ) || ! document.body.classList.contains( 'acf-loading' ), null, { timeout: 15000 } ).catch( () => {} );
	await page.waitForTimeout( 2000 );
}

( async () => {
	clearLog();
	const created = [];
	const browser = await chromium.launch();

	try {
		// --- Регистрация ---
		const reg = php( `
			$names = static fn( $g ) => array_values( array_filter( wp_list_pluck( acf_get_fields( $g ), 'name' ) ) );
			echo wp_json_encode( array(
				'case'     => $names( 'group_trust_case' ),
				'review'   => $names( 'group_trust_review' ),
				'casePos'  => acf_get_field_group( 'group_trust_case' )['position'],
				'kinds'    => Trust\\Reviews::KINDS,
				'classic'  => array( use_block_editor_for_post_type( 'review' ), use_block_editor_for_post_type( 'case' ), use_block_editor_for_post_type( 'team' ), use_block_editor_for_post_type( 'vacancy' ) ),
			) );` );
		check( 'Кейс: поля из ТЗ и макетов',
			[ 'trust_featured', 'trust_client', 'trust_niche', 'trust_region', 'trust_start', 'trust_end', 'trust_site', 'trust_lead', 'trust_screenshot', 'trust_results', 'trust_positions', 'trust_positions_region', 'trust_positions_date', 'trust_team', 'trust_review' ].every( ( n ) => reg.case.includes( n ) ),
			reg.case.join( ', ' ) );
		check( 'Кейс: поля в боковой панели редактора блоков', reg.casePos === 'side' && reg.classic[ 1 ] === true );
		check( 'Отзыв: поля из ТЗ и макета, включая служебные из формы',
			[ 'trust_kind', 'trust_name', 'trust_position', 'trust_company', 'trust_photo', 'trust_text', 'trust_results', 'trust_date', 'trust_source', 'trust_letter', 'trust_video_url', 'trust_video_file', 'trust_video_cover', 'trust_video_title', 'trust_video_duration', 'trust_email', 'trust_phone', 'trust_rating', 'trust_consent_at', 'trust_publish_consent_at' ].every( ( n ) => reg.review.includes( n ) ),
			reg.review.join( ', ' ) );
		check( 'Отзыв: три вида', JSON.stringify( Object.values( reg.kinds ) ) === JSON.stringify( [ 'Текстовый', 'Видео', 'Благодарственное письмо' ] ) );
		check( 'Отзывы, сотрудники, вакансии — классический экран (только поля)', reg.classic[ 0 ] === false && reg.classic[ 2 ] === false && reg.classic[ 3 ] === false );

		const page = await browser.newPage( { viewport: { width: 1440, height: 1100 } } );
		const errors = [];
		page.on( 'pageerror', ( e ) => errors.push( e.message ) );
		await login( page );

		// --- Отзыв: вид меняет набор полей ---
		await page.goto( `${ URL }/wp-admin/post-new.php?post_type=review` );
		await page.locator( '.acf-field[data-name="trust_kind"]' ).waitFor();
		const placeholder = await page.locator( '#title-prompt-text' ).innerText();
		check( 'Отзыв: подсказка в заголовке «можно не заполнять»', ( placeholder || '' ).includes( 'можно не заполнять' ), placeholder );
		const kind = async ( label ) => {
			await page.locator( '.acf-field[data-name="trust_kind"] label', { hasText: label } ).click();
			await page.waitForTimeout( 300 );
			return visibleFields( page );
		};
		const asText = await visibleFields( page );
		check( 'Текстовый: текст, результаты, скан письма; полей видео нет',
			[ 'trust_text', 'trust_results', 'trust_letter' ].every( ( n ) => asText.includes( n ) ) && ! asText.includes( 'trust_video_url' ), asText.join( ', ' ) );
		const asVideo = await kind( 'Видео' );
		check( 'Видео: ссылка, файл, обложка, «О чём видео», длительность; текста и письма нет',
			[ 'trust_video_url', 'trust_video_file', 'trust_video_cover', 'trust_video_title', 'trust_video_duration' ].every( ( n ) => asVideo.includes( n ) ) &&
			! asVideo.includes( 'trust_text' ) && ! asVideo.includes( 'trust_letter' ), asVideo.join( ', ' ) );
		const asLetter = await kind( 'Благодарственное письмо' );
		check( 'Письмо: скан и текст; результатов и видео нет',
			asLetter.includes( 'trust_letter' ) && asLetter.includes( 'trust_text' ) && ! asLetter.includes( 'trust_results' ) && ! asLetter.includes( 'trust_video_url' ), asLetter.join( ', ' ) );

		// --- Отзыв: обязательные поля по виду ---
		await page.fill( '.acf-field[data-name="trust_name"] input', `${ MARK } Анна` );
		await page.fill( '.acf-field[data-name="trust_company"] input', 'ООО Проверка' );
		const error = async () => ( await page.locator( '.acf-notice.-error, .acf-error-message' ).allInnerTexts() ).join( ' ' );

		await publish( page );
		check( 'Письмо без скана не публикуется', ( await error() ).includes( 'Загрузите скан письма' ) && page.url().includes( 'post-new.php' ), await error() );
		await kind( 'Видео' );
		await publish( page );
		check( 'Видео без ссылки и файла не публикуется', ( await error() ).includes( 'ссылку на видео' ) && page.url().includes( 'post-new.php' ), await error() );
		await kind( 'Текстовый' );
		check( 'Смена вида убирает сообщения прошлой проверки', ( await error() ) === '', await error() );
		await publish( page );
		check( 'Текстовый отзыв без текста не публикуется', ( await error() ).includes( 'Напишите текст отзыва' ) && ! ( await error() ).includes( 'скан письма' ) && page.url().includes( 'post-new.php' ), await error() );

		await page.fill( '.acf-field[data-name="trust_text"] textarea', 'Работали полгода, заявок стало вдвое больше. Отчёты понятные, сроки соблюдали.' );
		await page.locator( '.acf-field[data-name="trust_results"] .acf-button[data-event="add-row"]' ).first().click();
		await page.locator( '.acf-field[data-name="trust_results"] .acf-row:not(.acf-clone) input[type="text"]' ).first().fill( '+100% заявок' );
		await page.screenshot( { path: path.join( OUT, 'cases-reviews-review.png' ), fullPage: true } );
		await page.click( '#publish' );
		// SCF сначала проверяет поля запросом, потом отправляет форму.
		await page.waitForURL( /post\.php\?post=\d+/, { timeout: 30000 } );
		await page.waitForLoadState( 'load' );
		const reviewId = new globalThis.URL( page.url() ).searchParams.get( 'post' );
		created.push( reviewId );
		const saved = php( `echo wp_json_encode( array( get_the_title( ${ Number( reviewId ) } ), get_post_status( ${ Number( reviewId ) } ), get_field( 'trust_kind', ${ Number( reviewId ) } ), get_field( 'trust_results', ${ Number( reviewId ) } ) ) );` );
		check( 'Текстовый отзыв сохранён: вид, результаты', saved[ 1 ] === 'publish' && saved[ 2 ] === 'text' && saved[ 3 ]?.[ 0 ]?.text === '+100% заявок', JSON.stringify( saved ) );
		check( 'Пустой заголовок заполнен именем и компанией', saved[ 0 ] === `${ MARK } Анна, ООО Проверка`, saved[ 0 ] );

		// --- Отзыв: колонки списка ---
		const pendingId = createPost( 'review', `${ MARK } на проверке`, [ '--post_status=pending' ] );
		created.push( pendingId );
		wp( 'eval', `update_field( Trust\\Fields::key( 'review', 'trust_kind' ), 'video', ${ Number( pendingId ) } ); update_field( Trust\\Fields::key( 'review', 'trust_name' ), 'Борис', ${ Number( pendingId ) } ); update_field( Trust\\Fields::key( 'review', 'trust_position' ), 'Директор', ${ Number( pendingId ) } );` );
		await page.goto( `${ URL }/wp-admin/edit.php?post_type=review&s=${ encodeURIComponent( MARK ) }&post_status=all` );
		const heads = ( await page.locator( '.wp-list-table thead th' ).allInnerTexts() ).map( ( s ) => s.trim().split( '\n' )[ 0 ] );
		check( 'Список отзывов: колонки «Вид», «Автор отзыва», «Услуги», «Статус»', [ 'Вид', 'Автор отзыва', 'Услуги', 'Статус' ].every( ( h ) => heads.includes( h ) ), heads.join( ', ' ) );
		const row = async ( id ) => page.locator( `#post-${ id }` ).evaluate( ( tr ) => ( {
			kind: tr.querySelector( '.column-trust_kind' ).textContent.trim(),
			author: tr.querySelector( '.column-trust_author' ).textContent.trim(),
			status: tr.querySelector( '.column-trust_status' ).textContent.trim(),
		} ) );
		const r1 = await row( reviewId );
		const r2 = await row( pendingId );
		check( 'Колонки: вид, автор с компанией, статус', r1.kind === 'Текстовый' && r1.author === `${ MARK } Анна, ООО Проверка` && r1.status === 'Опубликован', JSON.stringify( r1 ) );
		check( 'Отзыв на проверке: «Видео», «Борис, Директор», «На проверке»', r2.kind === 'Видео' && r2.author === 'Борис, Директор' && r2.status === 'На проверке', JSON.stringify( r2 ) );

		// --- Кейс: боковая панель редактора блоков ---
		const repairId = wp( 'post', 'list', '--post_type=case', '--name=novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir', '--field=ID' );
		await page.goto( `${ URL }/wp-admin/post.php?post=${ repairId }&action=edit` );
		await page.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		await page.evaluate( () => {
			try {
				wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
			} catch ( e ) {}
		} );
		const client = page.locator( '.acf-field[data-name="trust_client"] input' );
		await client.waitFor( { timeout: 30000 } );
		check( 'Демо-кейс в редакторе: клиент и 4 результата', ( await client.inputValue() ) === '[Ремонт&Дизайн]' &&
			( await page.locator( '.acf-field[data-name="trust_results"] .acf-row:not(.acf-clone)' ).count() ) === 4 );
		const sidebar = await page.locator( '.interface-complementary-area' ).innerText();
		check( 'Боковая панель кейса: «Данные кейса», «Позиции в поиске», «Команда и отзыв», «Услуги», «Отрасли»',
			[ 'Данные кейса', 'Главный кейс', 'Чем занимается клиент', 'Позиции в поиске', 'Команда и отзыв', 'Услуги', 'Отрасли' ].every( ( s ) => sidebar.includes( s ) ) );
		await page.screenshot( { path: path.join( OUT, 'cases-reviews-case.png' ) } );

		// --- Кейс: сохранение из редактора и колонки списка ---
		const caseId = createPost( 'case', `${ MARK } кейс`, [ '--post_status=publish' ] );
		created.push( caseId );
		await page.goto( `${ URL }/wp-admin/post.php?post=${ caseId }&action=edit` );
		await page.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		await page.locator( '.acf-field[data-name="trust_client"] input' ).fill( 'ООО Тест-клиент' );
		await page.locator( '.acf-field[data-name="trust_featured"] .acf-switch' ).click();
		await page.evaluate( () => wp.data.dispatch( 'core/editor' ).savePost() );
		await page.waitForFunction( () => ! wp.data.select( 'core/editor' ).isSavingPost() && ! wp.data.select( 'core/edit-post' ).isSavingMetaBoxes(), null, { timeout: 30000 } );
		await page.waitForTimeout( 1000 );
		check( 'Кейс: клиент и «Главный» сохраняются из редактора',
			wp( 'post', 'meta', 'get', caseId, 'trust_client' ) === 'ООО Тест-клиент' && wp( 'post', 'meta', 'get', caseId, 'trust_featured' ) === '1' );

		await page.goto( `${ URL }/wp-admin/edit.php?post_type=case` );
		const caseHeads = ( await page.locator( '.wp-list-table thead th' ).allInnerTexts() ).map( ( s ) => s.trim().split( '\n' )[ 0 ] );
		check( 'Список кейсов: «Главный», «Клиент», «Услуги», «Отрасли»', [ 'Главный', 'Клиент', 'Услуги', 'Отрасли' ].every( ( h ) => caseHeads.includes( h ) ), caseHeads.join( ', ' ) );
		const repairRow = await page.locator( `#post-${ repairId }` ).evaluate( ( tr ) => [ tr.querySelector( '.column-trust_featured' ).textContent, tr.querySelector( '.column-trust_client' ).textContent.trim() ] );
		check( 'Демо-кейс «Ремонт квартир» отмечен главным', repairRow[ 0 ].includes( '★' ) && repairRow[ 1 ] === '[Ремонт&Дизайн]', JSON.stringify( repairRow ) );
		check( 'Админка без ошибок JavaScript', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );

		// --- Демо ---
		const demo = php( `
			$cases = get_posts( array( 'post_type' => 'case', 'numberposts' => -1, 'meta_key' => '_trust_demo_id' ) );
			$repair = get_post( ${ Number( repairId ) } );
			$review = (int) get_field( 'trust_review', $repair->ID );
			echo wp_json_encode( array(
				'cases'     => wp_list_pluck( $cases, 'post_title' ),
				'reviews'   => count( get_posts( array( 'post_type' => 'review', 'numberposts' => -1, 'meta_key' => '_trust_demo_id' ) ) ),
				'positions' => count( (array) get_field( 'trust_positions', $repair->ID ) ),
				'review'    => get_field( 'trust_company', $review ),
				'services'  => wp_get_post_terms( $repair->ID, 'service_type', array( 'fields' => 'names' ) ),
				'industry'  => wp_get_post_terms( $repair->ID, 'industry', array( 'fields' => 'names' ) ),
				'withNiche' => count( array_filter( $cases, static fn( $c ) => (bool) get_field( 'trust_niche', $c->ID ) ) ),
				'twoResults' => count( array_filter( $cases, static fn( $c ) => count( (array) get_field( 'trust_results', $c->ID ) ) >= 2 ) ),
			) );` );
		check( 'Демо: 9 кейсов из портфолио, первым — «Ремонт квартир»', demo.cases.length === 9 && demo.cases[ 0 ].startsWith( 'Новый сайт и SEO' ), demo.cases.slice( 0, 2 ).join( ' | ' ) );
		check( 'Демо: у всех кейсов «Чем занимается клиент» и два результата для карточек', demo.withNiche === 9 && demo.twoResults === 9 );
		check( 'Демо «Ремонт квартир»: 4 позиции, услуги SEO и Сайты, отрасль, отзыв клиента',
			demo.positions === 4 && demo.services.join() === 'SEO,Сайты' && demo.industry.join() === 'Ремонт и строительство' && demo.review === '[Ремонт&Дизайн]', JSON.stringify( demo ) );
		check( 'Демо: 9 отзывов', demo.reviews === 9 );
		const res = await page.goto( `${ URL }/portfolio/novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir/` );
		check( 'Страница демо-кейса открывается', res.status() === 200 );
	} finally {
		// И записи, созданные через интерфейс, если тест оборвался до того, как узнал их ID.
		const leftovers = wp( 'eval', `echo implode( ' ', wp_list_pluck( array_filter( get_posts( array( 'post_type' => array( 'review', 'case' ), 'post_status' => 'any', 'numberposts' => -1 ) ), static fn( $p ) => str_starts_with( $p->post_title, '${ MARK }' ) ), 'ID' ) );` );
		for ( const id of new Set( [ ...created, ...leftovers.split( ' ' ).filter( Boolean ) ] ) ) {
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
