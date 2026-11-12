/**
 * Шаг 2.4: поля сотрудников, вакансий и вопросов, колонки списков, счётчики в меню.
 *
 * Запуск: node wp-theme/tests/team-questions.js (стенд запущен, демо-контент импортирован).
 *
 * Тест создаёт свои записи (заголовки начинаются с «ТестКоманды») и удаляет их в конце.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, wp, clearLog, checkLog, login, ensureUser, publishPost } = require( './lib' );

const MARK = 'ТестКоманды';

/** Выполняет PHP и разбирает JSON-ответ. */
function php( code ) {
	return JSON.parse( wp( 'eval', code ) );
}

/** Создаёт запись. */
function createPost( type, title, extra = [] ) {
	return wp( 'post', 'create', `--post_type=${ type }`, `--post_title=${ MARK } ${ title }`, '--porcelain', ...extra );
}

/** Записывает поле SCF записи. */
function setField( type, id, name, value ) {
	wp( 'eval', `update_field( Trust\\Fields::key( '${ type }', '${ name }' ), ${ JSON.stringify( value ) }, ${ Number( id ) } );` );
}

/** Заголовки колонок списка. */
async function heads( page ) {
	return ( await page.locator( '.wp-list-table thead th' ).allInnerTexts() ).map( ( s ) => s.trim().split( '\n' )[ 0 ] );
}

/** Выбирает значение в поле SCF с поиском (select2). */
async function pick( page, field, search, option ) {
	await page.locator( `.acf-field[data-name="${ field }"] .select2-selection` ).click();
	await page.locator( '.select2-dropdown .select2-search__field' ).fill( search );
	await page.locator( '.select2-results__option', { hasText: option } ).first().click();
}

( async () => {
	clearLog();
	ensureUser( 'editor', 'editor' );
	ensureUser( 'author', 'author' );
	// Остатки прерванного прогона.
	wp( 'eval', `foreach ( get_posts( array( 'post_type' => array( 'team', 'vacancy', 'question', 'review', 'lead' ), 'post_status' => 'any', 'numberposts' => -1 ) ) as $p ) { if ( str_starts_with( $p->post_title, '${ MARK }' ) ) { wp_delete_post( $p->ID, true ); } }` );
	const browser = await launch();

	try {
		// --- Регистрация и помощники ---
		const reg = php( `
			$names = static fn( $g ) => array_values( array_filter( wp_list_pluck( acf_get_fields( $g ), 'name' ) ) );
			echo wp_json_encode( array(
				'team'     => $names( 'group_trust_team' ),
				'vacancy'  => $names( 'group_trust_vacancy' ),
				'question' => $names( 'group_trust_question' ),
				'qPos'     => acf_get_field_group( 'group_trust_question' )['position'],
				'words'    => array_map( array( 'Trust\\\\Team', 'years_word' ), array( 1, 2, 5, 11, 12, 21, 22, 25, 101, 111 ) ),
				'items'    => Trust\\Vacancies::items( "  первый \\n\\n второй\\r\\nтретий " ),
			) );` );
		check( 'Сотрудник: поля из ТЗ и макетов',
			[ 'trust_position', 'trust_leader', 'trust_career_start', 'trust_experience_area', 'trust_summary', 'trust_about', 'trust_topic_tags', 'trust_topics', 'trust_socials', 'trust_email', 'trust_user' ].every( ( n ) => reg.team.includes( n ) ), reg.team.join( ', ' ) );
		check( 'Вакансия: поля из ТЗ и макета',
			[ 'trust_open', 'trust_salary', 'trust_format', 'trust_experience', 'trust_city', 'trust_summary', 'trust_sections' ].every( ( n ) => reg.vacancy.includes( n ) ), reg.vacancy.join( ', ' ) );
		check( 'Вопрос: поля из ТЗ, в боковой панели редактора',
			[ 'trust_question', 'trust_asker', 'trust_asker_email', 'trust_asked_at', 'trust_expert', 'trust_answered_at', 'trust_related_posts', 'trust_answer_sent_at' ].every( ( n ) => reg.question.includes( n ) ) && reg.qPos === 'side', reg.question.join( ', ' ) );
		check( 'Склонение: год, года, лет', reg.words.join() === 'год,года,лет,лет,лет,год,года,лет,год,лет', reg.words.join() );
		check( 'Пункты раздела вакансии: строки без пустых', JSON.stringify( reg.items ) === JSON.stringify( [ 'первый', 'второй', 'третий' ] ), JSON.stringify( reg.items ) );

		const memberId = createPost( 'team', 'Ирина', [ '--post_status=publish' ] );
		setField( 'team', memberId, 'trust_position', 'Тестовый специалист' );
		setField( 'team', memberId, 'trust_career_start', new Date().getFullYear() - 3 );
		setField( 'team', memberId, 'trust_experience_area', 'в SEO' );
		check( 'Стаж считается от года: «опыт в SEO — 3 года»', wp( 'eval', `echo Trust\\Team::experience_line( ${ memberId } );` ) === 'опыт в SEO — 3 года' );
		const authorId = wp( 'user', 'get', 'author', '--field=ID' );
		const editorId = wp( 'user', 'get', 'editor', '--field=ID' );
		setField( 'team', memberId, 'trust_user', Number( authorId ) );
		check( 'Сотрудник по пользователю WordPress (Team::for_user)', wp( 'eval', `echo Trust\\Team::for_user( ${ authorId } )->ID ?? 0;` ) === memberId );

		const page = await browser.newPage( { viewport: { width: 1440, height: 1100 } } );
		const errors = [];
		page.on( 'pageerror', ( e ) => errors.push( e.message ) );
		await login( page );

		// --- Сотрудник: классический экран, один пользователь — один сотрудник ---
		await page.goto( `${ URL }/wp-admin/post-new.php?post_type=team` );
		check( 'Сотрудник: подсказка заголовка «Имя и фамилия»', ( await page.locator( '#title-prompt-text' ).innerText() ) === 'Имя и фамилия' );
		const screen = await page.locator( '#poststuff' ).innerText();
		check( 'Экран сотрудника: поля, «Фото», «Отделы», «Порядок»',
			[ 'Должность', 'Руководство', 'В профессии с (год)', 'Об авторе', 'Пишет о: теги', 'Соцсети', 'Пользователь WordPress', 'Фото', 'Отделы', 'Порядок' ].every( ( s ) => screen.includes( s ) ) );
		await page.fill( '#title', `${ MARK } Олег` );
		await page.fill( '.acf-field[data-name="trust_position"] input', 'Аналитик' );
		await pick( page, 'trust_user', 'author', 'author' );
		await page.click( '#publish' );
		await page.waitForTimeout( 2500 );
		const userError = ( await page.locator( '.acf-field[data-name="trust_user"] .acf-error-message' ).allInnerTexts() ).join( ' ' );
		check( 'Пользователь, уже связанный с другим сотрудником, не сохраняется', userError.includes( `уже связан с сотрудником «${ MARK } Ирина»` ), userError );
		await pick( page, 'trust_user', 'editor', 'editor' );
		await page.screenshot( { path: path.join( OUT, 'team-questions-team.png' ), fullPage: true } );
		const olegId = await publishPost( page );
		check( 'Сотрудник с другим пользователем сохранён', wp( 'eval', `echo Trust\\Team::for_user( ${ editorId } )->ID ?? 0;` ) === olegId );

		// --- Вакансия: разделы по умолчанию ---
		await page.goto( `${ URL }/wp-admin/post-new.php?post_type=vacancy` );
		const sections = await page.locator( '.acf-field[data-name="trust_sections"] .acf-row:not(.acf-clone) .acf-field[data-name="title"] input' ).evaluateAll( ( els ) => els.map( ( el ) => el.value ) );
		check( 'Новая вакансия: три раздела из макета уже добавлены', sections.join( ' / ' ) === 'Чем предстоит заниматься / Что ждём от кандидата / Что предлагаем', sections.join( ' / ' ) );
		check( 'Новая вакансия: «Принимаем резюме» включено', await page.locator( '.acf-field[data-name="trust_open"] input[type="checkbox"]' ).isChecked() );
		await page.fill( '#title', `${ MARK } Вакансия` );
		await page.fill( '.acf-field[data-name="trust_salary"] input', 'от 100 000 ₽' );
		const items = page.locator( '.acf-field[data-name="trust_sections"] .acf-row:not(.acf-clone) .acf-field[data-name="items"] textarea' );
		for ( let i = 0; i < 3; i++ ) {
			await items.nth( i ).fill( `Пункт ${ i + 1 }.1\nПункт ${ i + 1 }.2` );
		}
		await page.screenshot( { path: path.join( OUT, 'team-questions-vacancy.png' ), fullPage: true } );
		const vacancyId = await publishPost( page );
		const savedSections = php( `echo wp_json_encode( get_field( 'trust_sections', ${ Number( vacancyId ) } ) );` );
		check( 'Вакансия сохранена с тремя разделами и пунктами', savedSections.length === 3 && savedSections[ 2 ].items.split( '\n' ).length === 2 );

		await page.goto( `${ URL }/wp-admin/edit.php?post_type=vacancy` );
		const vHeads = await heads( page );
		check( 'Список вакансий: «Зарплата», «Формат», «Отделы», «Принимаем резюме»', [ 'Зарплата', 'Формат', 'Отделы', 'Принимаем резюме' ].every( ( h ) => vHeads.includes( h ) ), vHeads.join( ', ' ) );
		setField( 'vacancy', vacancyId, 'trust_open', 0 );
		await page.reload();
		const openCell = await page.locator( `#post-${ vacancyId } .column-trust_open` ).innerText();
		check( 'Закрытая вакансия: «Нет — скрыта»', openCell.trim() === 'Нет — скрыта', openCell );

		// --- Списки сотрудников и вопросов ---
		await page.goto( `${ URL }/wp-admin/edit.php?post_type=team` );
		const tHeads = await heads( page );
		check( 'Колонки сотрудников: Фото, Должность, Опыт, Отделы, Руководство, Пользователь, Порядок',
			[ 'Фото', 'Должность', 'Опыт', 'Отделы', 'Руководство', 'Пользователь', 'Порядок' ].every( ( h ) => tHeads.includes( h ) ), tHeads.join( ', ' ) );
		const ceo = wp( 'post', 'list', '--post_type=team', '--name=ceo', '--field=ID' );
		const ceoRow = await page.locator( `#post-${ ceo }` ).evaluate( ( tr ) => [ tr.querySelector( '.column-trust_position' ).textContent.trim(), tr.querySelector( '.column-trust_leader' ).textContent ] );
		check( 'Демо-руководитель: должность и ★', ceoRow[ 0 ] === 'Основатель и генеральный директор' && ceoRow[ 1 ].includes( '★' ), JSON.stringify( ceoRow ) );
		await page.screenshot( { path: path.join( OUT, 'team-questions-team-list.png' ) } );

		const pendingId = createPost( 'question', 'на утверждении', [ '--post_status=pending' ] );
		setField( 'question', pendingId, 'trust_asker', 'Вера' );
		await page.goto( `${ URL }/wp-admin/edit.php?post_type=question&post_status=all` );
		const qHeads = await heads( page );
		check( 'Колонки вопросов: Спрашивает, Специалист, Темы вопросов, Статус', [ 'Спрашивает', 'Специалист', 'Темы вопросов', 'Статус' ].every( ( h ) => qHeads.includes( h ) ), qHeads.join( ', ' ) );
		const qRow = await page.locator( `#post-${ pendingId }` ).evaluate( ( tr ) => [ tr.querySelector( '.column-trust_asker' ).textContent.trim(), tr.querySelector( '.column-trust_status' ).textContent.trim() ] );
		check( 'Вопрос из формы: «Вера», «На утверждении»', qRow[ 0 ] === 'Вера' && qRow[ 1 ] === 'На утверждении', JSON.stringify( qRow ) );

		// --- Счётчики в меню ---
		const reviewId = createPost( 'review', 'отзыв', [ '--post_status=pending' ] );
		const leadId = createPost( 'lead', 'заявка', [ '--post_status=publish' ] );
		wp( 'post', 'meta', 'update', leadId, 'trust_status', 'new' );
		const expected = php( 'echo wp_json_encode( Trust\\Admin::counts() );' );
		await page.goto( `${ URL }/wp-admin/` );
		const bubble = async ( type ) => page.locator( `#adminmenu a[href="edit.php?post_type=${ type }"] .awaiting-mod .pending-count` ).first().innerText().catch( () => '' );
		const bubbles = { lead: await bubble( 'lead' ), question: await bubble( 'question' ), review: await bubble( 'review' ) };
		check( 'Счётчики в меню: новые заявки, вопросы и отзывы на утверждении',
			bubbles.lead === String( expected.lead ) && bubbles.question === String( expected.question ) && bubbles.review === String( expected.review ) && expected.lead >= 1 && expected.question >= 2 && expected.review >= 1,
			`${ JSON.stringify( bubbles ) } / ${ JSON.stringify( expected ) }` );
		await page.locator( '#adminmenu' ).screenshot( { path: path.join( OUT, 'team-questions-menu.png' ) } );
		wp( 'post', 'delete', leadId, '--force' );
		wp( 'post', 'delete', reviewId, '--force' );
		await page.goto( `${ URL }/wp-admin/` );
		check( 'Без новых заявок счётчика у «Заявок» нет', ( await page.locator( '#adminmenu a[href="edit.php?post_type=lead"] .awaiting-mod' ).count() ) === 0 );

		// --- Вопрос: боковая панель редактора блоков ---
		const q1 = wp( 'post', 'list', '--post_type=question', '--meta_key=_trust_demo_id', '--meta_value=question:q01', '--field=ID' );
		await page.goto( `${ URL }/wp-admin/post.php?post=${ q1 }&action=edit` );
		await page.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		await page.evaluate( () => {
			try {
				wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
			} catch ( e ) {}
		} );
		await page.locator( '.acf-field[data-name="trust_question"] textarea' ).waitFor( { timeout: 30000 } );
		const sidebar = await page.locator( '.interface-complementary-area' ).innerText();
		check( 'Редактор вопроса: поля в боковой панели, «Темы вопросов», «Теги»',
			[ 'Текст вопроса', 'Имя спросившего', 'Email спросившего', 'Отвечает', 'Статьи по теме из блога', 'Ссылка на ответ отправлена', 'Темы вопросов', 'Теги' ].every( ( s ) => sidebar.includes( s ) ) );
		check( 'Демо-вопрос: текст из макета, «Отвечает» выбран', ( await page.locator( '.acf-field[data-name="trust_question"] textarea' ).inputValue() ).startsWith( 'Месяц назад добавили' ) &&
			( await page.locator( '.acf-field[data-name="trust_expert"] .select2-selection__rendered' ).innerText() ).includes( '[Имя Фамилия]' ) );
		check( '«Ссылка на ответ отправлена» — только для чтения', await page.locator( '.acf-field[data-name="trust_answer_sent_at"] input' ).getAttribute( 'readonly' ) !== null );
		const blocks = await page.evaluate( () => wp.data.select( 'core/block-editor' ).getBlocks().map( ( b ) => b.name ) );
		check( 'Ответ — стандартные блоки (абзацы, заголовки, списки)', blocks.includes( 'core/paragraph' ) && blocks.includes( 'core/heading' ) && blocks.includes( 'core/list' ) && ! blocks.includes( 'core/missing' ), blocks.join( ', ' ) );
		await page.screenshot( { path: path.join( OUT, 'team-questions-question.png' ) } );
		check( 'Админка без ошибок JavaScript', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );

		// --- Демо ---
		const demo = php( `
			$demo = static fn( $type, $status = 'publish' ) => get_posts( array( 'post_type' => $type, 'post_status' => $status, 'numberposts' => -1, 'meta_key' => '_trust_demo_id', 'orderby' => 'menu_order', 'order' => 'ASC' ) );
			$team = $demo( 'team' );
			$repair = get_posts( array( 'post_type' => 'case', 'name' => 'novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir' ) )[0];
			$audit = get_term_by( 'slug', 'tehnicheskiy-audit', 'category' );
			echo wp_json_encode( array(
				'team'      => count( $team ),
				'first'     => $team[0]->post_name,
				'leaders'   => count( array_filter( $team, static fn( $p ) => get_field( 'trust_leader', $p->ID ) ) ),
				'vacancies' => count( $demo( 'vacancy' ) ),
				'sections'  => count( array_filter( $demo( 'vacancy' ), static fn( $p ) => 3 === count( (array) get_field( 'trust_sections', $p->ID ) ) ) ),
				'questions' => count( $demo( 'question' ) ),
				'pending'   => count( $demo( 'question', 'pending' ) ),
				'experts'   => count( array_filter( $demo( 'question' ), static fn( $p ) => get_field( 'trust_expert', $p->ID ) ) ),
				'caseTeam'  => array_map( static fn( $r ) => get_post_field( 'post_name', $r['member'] ), (array) get_field( 'trust_team', $repair->ID ) ),
				'rubric'    => get_post_field( 'post_name', get_field( 'trust_rubric_lead', 'term_' . $audit->term_id ) ),
			) );` );
		check( 'Демо: 15 сотрудников по порядку макета, 3 руководителя', demo.team === 15 && demo.first === 'ceo' && demo.leaders === 3, JSON.stringify( demo ) );
		check( 'Демо: 6 вакансий, у каждой три раздела', demo.vacancies === 6 && demo.sections === 6 );
		check( 'Демо: 20 опубликованных вопросов со специалистом и 1 на утверждении', demo.questions === 20 && demo.experts === 20 && demo.pending === 1 );
		check( 'Демо: команда кейса «Ремонт квартир» и ведущий рубрики «Технический аудит»',
			demo.caseTeam.join() === 'seo-specialist,designer,frontend' && demo.rubric === 'seo-senior', JSON.stringify( [ demo.caseTeam, demo.rubric ] ) );
		const pages = [ '/komanda/seo-senior/', wp( 'post', 'list', `--post__in=${ q1 }`, '--post_type=question', '--field=url' ).replace( URL, '' ) ];
		const codes = [];
		for ( const u of pages ) {
			const res = await fetch( URL + u, { redirect: 'manual' } );
			await res.arrayBuffer();
			codes.push( res.status );
		}
		check( 'Страницы демо-сотрудника и демо-вопроса открываются', codes.every( ( c ) => c === 200 ), `${ pages.join( ', ' ) }: ${ codes.join( ', ' ) }` );
	} finally {
		const leftovers = wp( 'eval', `echo implode( ' ', wp_list_pluck( array_filter( get_posts( array( 'post_type' => array( 'team', 'vacancy', 'question', 'review', 'lead' ), 'post_status' => 'any', 'numberposts' => -1 ) ), static fn( $p ) => str_starts_with( $p->post_title, '${ MARK }' ) ), 'ID' ) );` );
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
