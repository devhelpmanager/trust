/**
 * Шаг 2.5: типы страниц — шаблоны, окно выбора, наборы блоков, разрешённые блоки.
 *
 * Запуск: node wp-theme/tests/page-types.js (стенд запущен, демо-контент импортирован).
 *
 * Тест создаёт свои страницы (заголовки начинаются с «ТестТипов») и удаляет их в конце.
 */

const { chromium } = require( 'playwright' );
const fs = require( 'fs' );
const path = require( 'path' );
const { URL, OUT, THEME, check, finish, wp, clearLog, checkLog, login } = require( './lib' );

const MARK = 'ТестТипов';

/** Выполняет PHP и разбирает JSON-ответ. */
function php( code ) {
	return JSON.parse( wp( 'eval', code ) );
}

/** Открывает редактор и ждёт его готовности. */
async function openEditor( page, url ) {
	await page.goto( url );
	await page.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ) && wp.data.select( 'core/editor' ).getCurrentPostId(), null, { timeout: 60000 } );
	await page.evaluate( () => {
		try {
			wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
		} catch ( e ) {}
	} );
	await page.waitForTimeout( 1500 );
}

/** Состояние редактора: шаблон, блоки, разрешённые блоки. */
async function state( page ) {
	return page.evaluate( () => ( {
		template: wp.data.select( 'core/editor' ).getEditedPostAttribute( 'template' ) || '',
		blocks: wp.data.select( 'core/block-editor' ).getBlocks().map( ( b ) => b.name ),
		allowed: wp.data.select( 'core/block-editor' ).getSettings().allowedBlockTypes,
	} ) );
}

/** Сохраняет запись из редактора блоков и ждёт окончания. */
async function save( page ) {
	await page.evaluate( () => wp.data.dispatch( 'core/editor' ).savePost() );
	await page.waitForFunction( () => ! wp.data.select( 'core/editor' ).isSavingPost(), null, { timeout: 30000 } );
	await page.waitForTimeout( 500 );
}

( async () => {
	clearLog();
	const browser = await chromium.launch();
	const savedBlog = wp( 'option', 'get', 'page_for_posts' );

	try {
		// --- Шаблоны и типы ---
		const reg = php( `
			echo wp_json_encode( array(
				'templates' => wp_get_theme()->get_page_templates( null, 'page' ),
				'types'     => array_map( static fn( $t ) => array( $t['label'], $t['template'], count( $t['blocks'] ) ), Trust\\Page_Types::all() ),
				'exclusive' => Trust\\Page_Types::exclusive(),
			) );` );
		const names = Object.values( reg.templates ).sort();
		check( 'Шаблоны типов страниц: 10 с русскими названиями',
			names.length === 10 && [ 'Услуга', 'Главная / посадочная', 'Портфолио', 'О компании', 'Команда', 'Отзывы', 'Карьера', 'Контакты', 'Документ', 'Вопросы SEO' ].every( ( n ) => names.includes( n ) ), names.join( ', ' ) );
		check( 'Типы: 11 (10 шаблонов и «Обычная страница»), у каждого файл шаблона и набор блоков',
			Object.keys( reg.types ).length === 11 &&
			Object.values( reg.types ).every( ( [ , template, blocks ] ) => blocks > 0 && ( '' === template || fs.existsSync( path.join( THEME, template ) ) ) ) );
		check( 'Набор «Услуги» — по макету страницы услуги, 7 блоков', reg.types.service[ 2 ] === 7 );
		check( 'Блоки только своего типа: «Кейсы» — портфолио, «Ответы» — вопросы', reg.exclusive[ 'trust/cases-grid' ] === 'portfolio' && reg.exclusive[ 'trust/answers-list' ] === 'questions' );
		check( 'Миниатюры типов в теме', Object.keys( reg.types ).every( ( key ) => fs.existsSync( path.join( THEME, 'assets/img/page-types', `${ key }.jpg` ) ) ) );

		// --- Демо-страницы ---
		const demo = php( `
			$out = array();
			foreach ( get_posts( array( 'post_type' => 'page', 'numberposts' => -1, 'meta_key' => '_trust_demo_id' ) ) as $p ) {
				$out[ get_page_uri( $p ) ] = Trust\\Page_Types::of( $p );
			}
			echo wp_json_encode( $out );` );
		check( 'Демо: типы страниц по макетам',
			demo[ 'seo/audit' ] === 'service' && demo.dizayn === 'service' && demo[ 'seo/voprosy' ] === 'questions' && demo.portfolio === 'portfolio' &&
			demo.kontakty === 'contacts' && demo[ 'o-kompanii' ] === 'about' && demo.komanda === 'team' && demo.otzyvy === 'reviews' &&
			demo.karera === 'career' && demo.oferta === 'document' && demo[ 'politika-cookie' ] === 'document' && demo.blog === 'default',
			JSON.stringify( demo ) );
		check( 'Демо: у всех страниц, кроме блога, есть тип', Object.entries( demo ).filter( ( [ , type ] ) => 'default' === type ).map( ( [ p ] ) => p ).join() === 'blog' );

		// --- Сайт: шаблоны и запасная шапка ---
		const page = await browser.newPage( { viewport: { width: 1440, height: 1000 } } );
		const errors = [];
		page.on( 'pageerror', ( e ) => errors.push( e.message ) );
		for ( const [ url, type ] of [ [ '/seo/audit/', 'service' ], [ '/kontakty/', 'contacts' ], [ '/oferta/', 'document' ], [ '/portfolio/', 'portfolio' ], [ '/blog/', 'default' ] ] ) {
			const res = await page.goto( URL + url );
			const body = await page.getAttribute( 'body', 'class' );
			const h1 = await page.locator( 'h1' ).count();
			check( `Страница ${ url }: шаблон типа «${ type }», один H1`, res.status() === 200 && body.includes( `page-type-${ type }` ) && h1 === 1, `${ res.status() } ${ h1 }` );
		}
		const orphan = wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK } без первого экрана`, '--post_status=publish', '--post_content=<!-- wp:trust/hero-section {"data":{}} /--><!-- wp:paragraph --><p>Текст</p><!-- /wp:paragraph -->', '--porcelain' );
		await page.goto( `${ URL }/?page_id=${ orphan }` );
		check( 'Блок первого экрана, которого нет в теме, не оставляет страницу без H1', ( await page.locator( 'h1' ).innerText() ).includes( 'без первого экрана' ) );

		// --- Редактор: новая страница ---
		await login( page );
		await openEditor( page, `${ URL }/wp-admin/post-new.php?post_type=page` );
		const modal = page.locator( '.trust-page-types' );
		check( 'Новая страница: окно «Выберите тип страницы»', ( await modal.count() ) === 1 && ( await modal.locator( 'h1' ).innerText() ).includes( 'Выберите тип страницы' ) );
		const cards = await modal.locator( '.trust-page-types__card' ).evaluateAll( ( els ) => els.map( ( el ) => ( {
			type: el.dataset.type,
			title: el.querySelector( '.trust-page-types__title' ).textContent,
			thumb: el.querySelector( 'img' ).complete && el.querySelector( 'img' ).naturalWidth > 0,
		} ) ) );
		await page.waitForFunction( () => [ ...document.querySelectorAll( '.trust-page-types__thumb' ) ].every( ( img ) => img.complete && img.naturalWidth > 0 ), null, { timeout: 15000 } ).catch( () => {} );
		const thumbs = await modal.locator( '.trust-page-types__thumb' ).evaluateAll( ( imgs ) => imgs.filter( ( img ) => img.naturalWidth > 0 ).length );
		check( 'Окно: 11 типов с названиями и миниатюрами, первым — «Услуга»', cards.length === 11 && cards[ 0 ].title === 'Услуга' && thumbs === 11, `${ cards.length }, миниатюр ${ thumbs }` );
		await page.screenshot( { path: path.join( OUT, 'page-types-modal.png' ) } );

		await page.keyboard.press( 'Escape' );
		await page.waitForTimeout( 300 );
		let s = await state( page );
		check( 'Esc закрывает окно без изменений', ( await modal.count() ) === 0 && '' === s.template && 0 === s.blocks.length, JSON.stringify( s.blocks ) );

		// Шаблон сменили вручную на пустой странице — подсказка вставить набор.
		await page.evaluate( () => wp.data.dispatch( 'core/editor' ).editPost( { template: 'templates/service.php' } ) );
		await page.waitForTimeout( 500 );
		const offer = page.locator( '.components-notice', { hasText: 'Вставить набор блоков типа «Услуга»' } );
		check( 'Смена шаблона на пустой странице: предложение вставить набор', ( await offer.count() ) === 1 );
		await offer.locator( 'button', { hasText: 'Вставить набор' } ).click();
		await page.waitForTimeout( 500 );
		s = await state( page );
		check( 'Набор вставлен по кнопке: блоки, которые уже есть в теме («Вопрос-ответ»)', JSON.stringify( s.blocks ) === JSON.stringify( [ 'trust/faq' ] ), JSON.stringify( s.blocks ) );
		check( 'На «Услуге» блоки чужих типов скрыты («Кейсы», «Ответы»)', Array.isArray( s.allowed ) && s.allowed.includes( 'trust/faq' ) && ! s.allowed.includes( 'trust/cases-grid' ) && ! s.allowed.includes( 'trust/answers-list' ) );
		check( 'Страницам доступны стандартные блоки по ТЗ, но не «Обложка» и «Колонки»',
			[ 'core/paragraph', 'core/heading', 'core/list', 'core/quote', 'core/image', 'core/table', 'core/separator', 'core/buttons', 'core/block' ].every( ( b ) => s.allowed.includes( b ) ) &&
			! s.allowed.includes( 'core/cover' ) && ! s.allowed.includes( 'core/columns' ) );

		// Панель «Тип страницы» → «Портфолио»: на странице с блоками блоки не пересоздаются.
		await page.evaluate( ( name ) => {
			if ( ! wp.data.select( 'core/editor' ).isEditorPanelOpened( name ) ) {
				wp.data.dispatch( 'core/editor' ).toggleEditorPanelOpened( name );
			}
		}, 'trust-page-types/trust-page-type' );
		await page.locator( '.trust-page-type-panel button', { hasText: 'Сменить тип' } ).click();
		await page.locator( '.trust-page-types__card[data-type="portfolio"]' ).click();
		await page.waitForTimeout( 500 );
		s = await state( page );
		check( 'Смена типа на странице с блоками: шаблон сменён, блоки не тронуты', s.template === 'templates/portfolio.php' && JSON.stringify( s.blocks ) === JSON.stringify( [ 'trust/faq' ] ), JSON.stringify( s ) );
		check( 'На «Портфолио» доступны «Кейсы», но не «Ответы»', s.allowed.includes( 'trust/cases-grid' ) && ! s.allowed.includes( 'trust/answers-list' ) );
		const panel = await page.locator( '.trust-page-type-panel' ).innerText();
		check( 'Панель «Тип страницы» показывает текущий тип', panel.includes( 'Портфолио' ) && panel.includes( 'Сменить тип' ) );

		await page.evaluate( ( title ) => wp.data.dispatch( 'core/editor' ).editPost( { title } ), `${ MARK } портфолио` );
		await save( page );
		const savedId = await page.evaluate( () => wp.data.select( 'core/editor' ).getCurrentPostId() );
		check( 'Шаблон сохраняется вместе со страницей', wp( 'eval', `echo get_page_template_slug( ${ savedId } );` ) === 'templates/portfolio.php' );

		// Новая страница → «Документ»: набор вставлен сразу.
		await openEditor( page, `${ URL }/wp-admin/post-new.php?post_type=page` );
		await page.locator( '.trust-page-types__card[data-type="document"]' ).click();
		await page.waitForTimeout( 800 );
		s = await state( page );
		check( 'Выбор «Документ» на новой странице: шаблон и набор (заголовок раздела и абзац текста)',
			s.template === 'templates/document.php' && JSON.stringify( s.blocks ) === JSON.stringify( [ 'core/heading', 'core/paragraph' ] ), JSON.stringify( s ) );
		check( 'После вставки блок не выделен — справа панель страницы', await page.evaluate( () => ! wp.data.select( 'core/block-editor' ).getSelectedBlockClientId() ) );
		await page.screenshot( { path: path.join( OUT, 'page-types-document.png' ) } );
		await page.evaluate( ( title ) => wp.data.dispatch( 'core/editor' ).editPost( { title } ), `${ MARK } документ` );
		await save( page );

		// Страница блога: подсказка, окно не открывается.
		const blogId = wp( 'eval', 'echo get_page_by_path( "blog" )->ID;' );
		wp( 'option', 'update', 'page_for_posts', blogId );
		await openEditor( page, `${ URL }/wp-admin/post.php?post=${ blogId }&action=edit` );
		await page.evaluate( ( name ) => {
			if ( ! wp.data.select( 'core/editor' ).isEditorPanelOpened( name ) ) {
				wp.data.dispatch( 'core/editor' ).toggleEditorPanelOpened( name );
			}
		}, 'trust-page-types/trust-page-type' );
		await page.waitForTimeout( 300 );
		const blogPanel = await page.locator( '.trust-page-type-panel' ).innerText().catch( () => '' );
		check( 'Страница блога: подсказка в панели «Тип страницы»', blogPanel.includes( 'Это страница блога' ) && ( await modal.count() ) === 0 );
		check( 'Редактор страниц без ошибок JavaScript', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );

		// --- Разрешённые блоки других типов записей ---
		const lists = php( `
			$list = static function ( $type ) {
				$post = new WP_Post( (object) array( 'ID' => 0, 'post_type' => $type ) );
				return apply_filters( 'allowed_block_types_all', true, new WP_Block_Editor_Context( array( 'post' => $post ) ) );
			};
			echo wp_json_encode( array( 'post' => $list( 'post' ), 'question' => $list( 'question' ), 'case' => $list( 'case' ), 'page' => $list( 'page' ) ) );` );
		check( 'Статьи: текстовые блоки, «Видео», «Картинка», «Оповещение», «Статьи из блога»; без блоков страниц',
			[ 'core/paragraph', 'core/table', 'trust/video', 'trust/image', 'trust/notice', 'trust/posts', 'core/block' ].every( ( b ) => lists.post.includes( b ) ) &&
			! lists.post.includes( 'trust/faq' ) && ! lists.post.includes( 'trust/how-i-would' ) );
		check( 'Ответы: то же и «Как бы поступил я»', lists.question.includes( 'trust/how-i-would' ) && lists.question.includes( 'trust/video' ) );
		check( 'Кейсы: блоки кейса и текстовые', lists.case.includes( 'trust/case-task' ) && lists.case.includes( 'core/paragraph' ) && ! lists.case.includes( 'trust/faq' ) );
		check( 'Блоки кейса и ответа не попадают на страницы', ! lists.page.includes( 'trust/case-task' ) && ! lists.page.includes( 'trust/how-i-would' ) );
	} finally {
		if ( savedBlog ) {
			wp( 'option', 'update', 'page_for_posts', savedBlog );
		} else {
			wp( 'option', 'delete', 'page_for_posts' );
		}
		const leftovers = wp( 'eval', `echo implode( ' ', wp_list_pluck( array_filter( get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1 ) ), static fn( $p ) => str_starts_with( $p->post_title, '${ MARK }' ) ), 'ID' ) );` );
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
