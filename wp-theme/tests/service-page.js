/**
 * Тест блоков страницы услуги (шаг 4.4): «Наши преимущества», «Кнопка связи», «Статьи из блога»
 * на локальном стенде с демо-контентом (страница /seo/audit/ — страница «Услуга» целиком).
 *
 * Запуск: node wp-theme/tests/service-page.js
 *
 * Страница: блоки по макету — первый экран, «Что включает», «Этапы», «Преимущества»,
 * «Вопрос-ответ», «Кнопка связи», «Наш блог».
 * «Наши преимущества»: H2, 6 пунктов H3 с номерами 01–06 и иконками.
 * «Кнопка связи»:
 * - форма «Обратный звонок»: поля без подписей (aria-label), галочка согласия вместо фразы
 *   «Отправляя заявку…» (решение заказчика), свайп-кнопка;
 * - короткий свайп — ручка возвращается; пустые поля и без галочки — ошибки под полями;
 * - свайп до конца, Enter на ручке, Enter в поле — заявка уходит (в «Заявках» — «Обратный
 *   звонок» с адресом страницы), «Заявка отправлена», «Отправить ещё одну заявку»;
 * - без JavaScript — обычная кнопка, после отправки возврат к этой форме с сообщением.
 * «Статьи из блога»: вид «Наш блог» (5 последних статей: рубрика, заголовок H3, дата по-русски),
 * вид «3 карточки с обложками» (обложка, рубрика, время чтения, анонс), рубрика, вручную,
 * популярные, количество, кнопка «Все статьи» и без неё; нет статей — блока нет; время чтения
 * по скорости из настроек; статья без обложки — заглушка.
 * Общее: совпадение с макетами seo-bloki/02, seo-bloki/09, seo-stranitsa/index.html («Наш блог»)
 * и seo-bloki/16 на 1280, 768 и 390 px (≤ 2 %; даты и время чтения в макетах — заглушки,
 * в теме подменяются перед сравнением); ширины 1440–280 px; зоны нажатия; axe-core; консоль
 * и журнал ошибок; редактор — категории блоков, превью.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp, resetFormLimits } = require( './lib' );

const PAGE = `${ URL }/seo/audit/`;
const MARK = 'ТестСтраницыУслуги';
const ADVANTAGES = [ 'Прозрачный состав работ', 'Фиксированная стоимость', 'Отчёт после каждого этапа', 'Один ответственный специалист', 'SEO, сайты и реклама в одной команде', 'Работаем по договору' ];
const POSTS = [ 'Robots.txt: как проверить файл и не закрыть сайт от поиска', 'Почему страницы выпадают из индекса и как это исправить', 'GEO: как подготовить сайт к ответам нейросетей', 'Яндекс Директ или SEO: что выбрать для нового сайта', 'Как читать отчёт по техническому аудиту' ];
const sleep = ( ms ) => new Promise( ( resolve ) => setTimeout( resolve, ms ) );

/** PHP: разметка блока «Статьи из блога» с полями. */
const postsBlock = ( fields ) => `Trust\\Blocks::serialize( 'posts', ${ fields } )`;

/** Создаёт опубликованную тестовую страницу с блоками (PHP-выражение разметки). */
function createPage( title, blocks ) {
	const content = wp( 'eval', `echo ${ blocks };` );
	return wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK } ${ title }`, '--post_status=publish', `--post_content=${ content }`, '--porcelain' );
}

/** Последние заявки «Обратный звонок» от имени. */
function leadsBy( name ) {
	return JSON.parse( wp( 'eval', `echo wp_json_encode( array_map( static fn( $p ) => array( 'id' => $p->ID, 'page' => get_post_meta( $p->ID, 'trust_page', true ), 'form' => get_post_meta( $p->ID, 'trust_form', true ) ), get_posts( array( 'post_type' => 'lead', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => 'trust_name', 'meta_value' => '${ name }' ) ) ) );` ) );
}

/**
 * Снимки блока темы и макета и их сравнение.
 *
 * @param {import('playwright').Page} page Страница.
 * @param {Object}                    o    name, width, url, theme (селектор), mockup, mock (селектор или h2:Заголовок), before (правки темы перед снимком), mockBefore.
 */
async function compare( page, o ) {
	const name = `${ o.name }-${ o.width }`;
	await page.setViewportSize( { width: o.width, height: 900 } );
	await page.goto( o.url || PAGE, { waitUntil: 'networkidle' } );
	await settle( page );
	if ( o.before ) {
		await page.evaluate( o.before );
	}
	await isolate( page, o.theme );
	await page.locator( o.theme ).screenshot( { path: path.join( OUT, `${ name }-theme.png` ) } );

	await page.goto( mockupUrl( o.mockup ), { waitUntil: 'networkidle' } );
	await settle( page );
	let sel = o.mock;
	if ( sel.startsWith( 'h2:' ) ) {
		await page.evaluate( ( t ) => {
			[ ...document.querySelectorAll( 'h2' ) ].find( ( h ) => h.textContent.trim() === t ).closest( 'section' ).id = 'mockup-block';
		}, sel.slice( 3 ) );
		sel = '#mockup-block';
	}
	if ( o.mockBefore ) {
		await page.evaluate( o.mockBefore );
	}
	await isolate( page, sel );
	await page.locator( sel ).first().screenshot( { path: path.join( OUT, `${ name }-mockup.png` ) } );
	const diff = comparePng( path.join( OUT, `${ name }-mockup.png` ), path.join( OUT, `${ name }-theme.png` ), path.join( OUT, `${ name }-diff.png` ) );
	check( `Макет ${ o.mockup } ${ o.width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
}

( async () => {
	clearLog();
	resetFormLimits();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const created = [];
	const savedSpeed = wp( 'eval', "echo (string) get_field( Trust\\Fields::key( Trust\\Settings::PREFIX, 'blog_read_speed' ), 'option' );" );

	try {
		// ===== Страница целиком =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const order = await page.evaluate( () => [ ...document.querySelectorAll( '.page-entry__content > section' ) ].map( ( s ) => s.className.split( ' ' )[ 0 ] ) );
		check( 'Страница «Услуга» по макету: 7 блоков по порядку',
			JSON.stringify( order ) === JSON.stringify( [ 'b-hero-service', 'b-includes', 'b-stages', 'b-advantages', 'b-faq', 'b-swipe', 'b-posts' ] ), order.join( ', ' ) );

		// ===== «Наши преимущества» =====
		const adv = page.locator( '.b-advantages' );
		check( 'Преимущества: H2 «Наши преимущества», надзаголовок «Почему мы»',
			( await adv.locator( 'h2' ).textContent() ).trim() === 'Наши преимущества' && ( await adv.locator( '.eyebrow' ).textContent() ).trim() === 'Почему мы' &&
			( await adv.getAttribute( 'aria-labelledby' ) ) === ( await adv.locator( 'h2' ).getAttribute( 'id' ) ) );
		const advItems = await adv.locator( '.b-advantages__item' ).evaluateAll( ( list ) => list.map( ( li ) => ( {
			num: li.querySelector( '.b-advantages__num' ).textContent.trim(),
			title: li.querySelector( 'h3' ).textContent.trim(),
			icon: li.querySelector( '.b-advantages__icon svg' ) ? li.querySelector( '.b-advantages__icon svg' ).innerHTML : '',
			text: !! li.querySelector( '.b-advantages__text' ),
		} ) ) );
		check( 'Преимущества: 6 пунктов H3 с номерами 01–06', JSON.stringify( advItems.map( ( i ) => i.num + ' ' + i.title ) ) === JSON.stringify( ADVANTAGES.map( ( t, i ) => `0${ i + 1 } ${ t }` ) ), advItems.map( ( i ) => i.title ).join( ' | ' ) );
		check( 'Преимущества: у каждого пункта своя иконка и текст', new Set( advItems.map( ( i ) => i.icon ) ).size === 6 && advItems.every( ( i ) => i.icon && i.text ) );
		const advBg = await adv.evaluate( ( el ) => getComputedStyle( el ).backgroundColor );
		check( 'Преимущества: фиолетовый фон #5B21B6', 'rgb(91, 33, 182)' === advBg, advBg );

		// ===== «Кнопка связи» =====
		const sw = page.locator( '.b-swipe' );
		check( 'Кнопка связи: якорь #callback, H2 «Перезвоним и ответим на ваши вопросы»',
			( await sw.getAttribute( 'id' ) ) === 'callback' && ( await sw.locator( 'h2' ).textContent() ).trim() === 'Перезвоним и ответим на ваши вопросы' );
		const formInfo = await sw.locator( 'form' ).evaluate( ( f ) => ( {
			type: f.dataset.trustForm,
			labels: [ ...f.querySelectorAll( 'input[name=name], input[name=phone]' ) ].map( ( i ) => i.getAttribute( 'aria-label' ) ),
			consent: !! f.querySelector( 'input[type=checkbox][name=consent][required]' ) && ! f.querySelector( 'input[name=consent]' ).checked,
			phrase: /Отправляя заявку/.test( f.textContent ),
			fallback: getComputedStyle( f.querySelector( '.b-swipe__fallback' ) ).display,
			knob: f.querySelector( '.b-swipe__knob' ).getAttribute( 'aria-label' ),
		} ) );
		check( 'Кнопка связи: форма «Обратный звонок», поля с aria-label, галочка согласия не отмечена, фразы «Отправляя заявку…» нет',
			formInfo.type === 'callback' && formInfo.labels.join() === 'Имя,Телефон' && formInfo.consent && ! formInfo.phrase, JSON.stringify( formInfo ) );
		check( 'Кнопка связи: свайп-кнопка подписана для скринридеров, обычной кнопки отправки со скриптом нет', /потяните вправо или нажмите Enter/.test( formInfo.knob ) && 'none' === formInfo.fallback );

		const knob = sw.locator( '.b-swipe__knob' );
		const dx = () => sw.locator( '.b-swipe__track' ).evaluate( ( t ) => t.style.getPropertyValue( '--b-swipe-dx' ) || '0px' );
		const drag = async ( distance ) => {
			await knob.scrollIntoViewIfNeeded();
			const b = await knob.boundingBox();
			await page.mouse.move( b.x + 20, b.y + 20 );
			await page.mouse.down();
			await page.mouse.move( b.x + 20 + distance, b.y + 20, { steps: 10 } );
			const during = await dx();
			await page.mouse.up();
			await page.waitForTimeout( 400 );
			return during;
		};
		const errorsShown = () => sw.locator( '.field__error:not([hidden])' ).allTextContents();

		const during = await drag( 70 );
		check( 'Свайп: ручка тянется за пальцем, короткий свайп — возвращается', parseFloat( during ) > 50 && '0px' === await dx(), `${ during } → ${ await dx() }` );
		await drag( 260 );
		let shown = await errorsShown();
		check( 'Свайп до конца с пустой формой: ошибки под полями и у галочки, фокус на имени, ручка вернулась',
			shown.length === 3 && await page.evaluate( () => document.activeElement.name === 'name' ) && '0px' === await dx(), shown.join( ' | ' ) );
		await sw.locator( '[name=name]' ).fill( 'Тест Свайп' );
		await sw.locator( '[name=phone]' ).fill( '+1 000 000 00 00' );
		await drag( 260 );
		shown = await errorsShown();
		check( 'Без галочки согласия заявка не уходит (ошибка у галочки)', shown.length === 1 && /согласие/.test( shown[ 0 ] ) && await sw.locator( '.b-swipe__sent' ).isHidden(), shown.join( ' | ' ) );
		await sw.locator( '[name=consent]' ).check();
		check( 'Ошибка у галочки пропадает, когда её отметили', ( await errorsShown() ).length === 0 );
		await drag( 260 );
		await sw.locator( '.b-swipe__sent' ).waitFor( { state: 'visible', timeout: 15000 } );
		const sent = await sw.evaluate( ( el ) => ( {
			text: el.querySelector( '.b-swipe__sent' ).innerText.replace( /\s+/g, ' ' ).trim(),
			role: el.querySelector( '.b-swipe__sent' ).getAttribute( 'role' ),
			track: el.querySelector( '.b-swipe__track' ).hidden,
			focus: document.activeElement.classList.contains( 'b-swipe__again' ),
			cleared: '' === el.querySelector( '[name=name]' ).value && ! el.querySelector( '[name=consent]' ).checked,
		} ) );
		check( 'Свайп до конца: «Заявка отправлена · Мы перезвоним…» (role=status), форма очищена, фокус на «Отправить ещё одну заявку»',
			sent.text === 'Заявка отправлена Мы перезвоним вам в ближайшее время' && 'status' === sent.role && sent.track && sent.focus && sent.cleared, JSON.stringify( sent ) );
		let leads = leadsBy( 'Тест Свайп' );
		check( 'Заявка в «Заявках»: форма «Обратный звонок», адрес страницы', 1 === leads.length && 'callback' === leads[ 0 ].form && `${ URL }/seo/audit/` === leads[ 0 ].page, JSON.stringify( leads ) );

		await sw.locator( '.b-swipe__again' ).click();
		check( '«Отправить ещё одну заявку» возвращает свайп, фокус на имени', await sw.locator( '.b-swipe__track' ).isVisible() && await page.evaluate( () => document.activeElement.name === 'name' ) );
		await sw.locator( '[name=name]' ).fill( 'Тест Свайп' );
		await sw.locator( '[name=phone]' ).fill( '+1 000 000 00 01' );
		await sw.locator( '[name=consent]' ).check();
		await knob.focus();
		await page.keyboard.press( 'Enter' );
		await sw.locator( '.b-swipe__sent' ).waitFor( { state: 'visible', timeout: 15000 } );
		await sw.locator( '.b-swipe__again' ).click();
		await sw.locator( '[name=name]' ).fill( 'Тест Свайп' );
		await sw.locator( '[name=phone]' ).fill( '+1 000 000 00 02' );
		await sw.locator( '[name=consent]' ).check();
		await sw.locator( '[name=phone]' ).press( 'Enter' );
		await sw.locator( '.b-swipe__sent' ).waitFor( { state: 'visible', timeout: 15000 } );
		leads = leadsBy( 'Тест Свайп' );
		check( 'Enter на ручке и Enter в поле тоже отправляют заявку', 3 === leads.length, `${ leads.length } заявок` );

		// Без JavaScript: обычная кнопка, возврат к этой форме.
		resetFormLimits();
		const noJs = await browser.newContext( { javaScriptEnabled: false } );
		const p2 = await noJs.newPage();
		await p2.goto( PAGE );
		const formId = await p2.locator( '.b-swipe__form' ).getAttribute( 'id' );
		check( 'Без JavaScript: свайпа нет, видна кнопка «Отправить заявку»', await p2.locator( '.b-swipe__track' ).isHidden() && await p2.locator( '.b-swipe__fallback' ).isVisible() );
		await p2.fill( `#${ formId } [name=name]`, 'Тест Свайп' );
		await p2.fill( `#${ formId } [name=phone]`, '+1 000 000 00 03' );
		await p2.check( `#${ formId } [name=consent]`, { force: true } );
		await sleep( 3300 );
		await Promise.all( [ p2.waitForNavigation(), p2.click( `#${ formId } .b-swipe__fallback` ) ] );
		const back = new globalThis.URL( p2.url() );
		check( 'Без JavaScript: заявка принята, возврат к этой форме (а не к окну звонка) с сообщением',
			'sent' === back.searchParams.get( 'trust_form' ) && back.hash === `#${ formId }` && ( await p2.locator( `#${ formId } .form__status--ok` ).textContent() || '' ).startsWith( 'Заявка отправлена' ), p2.url() );
		await noJs.close();
		leadsBy( 'Тест Свайп' ).forEach( ( l ) => wp( 'post', 'delete', String( l.id ), '--force' ) );

		// ===== «Статьи из блога», вид «Наш блог» =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const blog = page.locator( '.b-posts' );
		const cards = await blog.locator( '.b-posts__card' ).evaluateAll( ( list ) => list.map( ( a ) => ( {
			rubric: a.querySelector( '.b-posts__rubric' ) ? a.querySelector( '.b-posts__rubric' ).textContent.trim() : '',
			title: a.querySelector( 'h3' ).textContent.trim(),
			date: a.querySelector( 'time' ).textContent.trim(),
			iso: a.querySelector( 'time' ).getAttribute( 'datetime' ),
			cover: !! a.querySelector( '.b-posts__cover' ),
			href: a.getAttribute( 'href' ),
		} ) ) );
		check( '«Наш блог»: 5 последних статей по порядку, без обложек', JSON.stringify( cards.map( ( c ) => c.title ) ) === JSON.stringify( POSTS ) && cards.every( ( c ) => ! c.cover ), cards.map( ( c ) => c.title ).join( ' | ' ) );
		check( '«Наш блог»: рубрики-плашки', JSON.stringify( cards.map( ( c ) => c.rubric ) ) === JSON.stringify( [ 'Технический аудит', 'Индексация', 'GEO-продвижение', 'Контекстная реклама', 'Отчёты' ] ), cards.map( ( c ) => c.rubric ).join( ', ' ) );
		check( '«Наш блог»: дата по-русски («12 ноября 2026»), datetime, ссылка на статью',
			cards.every( ( c ) => /^\d{1,2} (января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря) \d{4}$/.test( c.date ) && c.iso && c.href.startsWith( `${ URL }/blog/` ) ), cards[ 0 ].date + ' ' + cards[ 0 ].href );
		check( '«Наш блог»: кнопка «Все статьи» ведёт на блог', ( await blog.locator( '.b-posts__all' ).textContent() ).trim() === 'Все статьи' && ( await blog.locator( '.b-posts__all' ).getAttribute( 'href' ) ) === `${ URL }/blog/` );

		// --- Источники, вид с обложками, время чтения ---
		const views = JSON.parse( wp( 'eval', "echo wp_json_encode( array_map( static fn( $t ) => array( $t, Trust\\Blog::reading_time( get_posts( array( 'post_type' => 'post', 'title' => $t ) )[0] ) ), array( 'Robots.txt: как проверить файл и не закрыть сайт от поиска' ) ) );" ) );
		const audit = wp( 'eval', "echo get_term_by( 'name', 'Индексация', 'category' )->term_id;" );
		const geo = wp( 'eval', "echo get_posts( array( 'post_type' => 'post', 'title' => 'GEO: как подготовить сайт к ответам нейросетей' ) )[0]->ID;" );
		const ppc = wp( 'eval', "echo get_posts( array( 'post_type' => 'post', 'title' => 'Яндекс Директ или SEO: что выбрать для нового сайта' ) )[0]->ID;" );
		const testPage = createPage( 'блог', [
			postsBlock( "array( 'title' => 'Карточки', 'view' => 'cards', 'more_label' => '' )" ),
			postsBlock( `array( 'title' => 'Рубрика', 'source' => 'category', 'category' => ${ audit } )` ),
			postsBlock( `array( 'title' => 'Вручную', 'source' => 'manual', 'posts' => array( ${ ppc }, ${ geo } ) )` ),
			postsBlock( "array( 'title' => 'Две', 'count' => 2 )" ),
			postsBlock( "array( 'title' => 'Пусто', 'source' => 'category', 'category' => 999999 )" ),
		].join( " . \"\\n\\n\" . " ) );
		created.push( testPage );
		wp( 'post', 'meta', 'update', ppc, 'trust_views', '50' );
		const popular = createPage( 'популярные', postsBlock( "array( 'title' => 'Популярные', 'source' => 'popular', 'count' => 1 )" ) );
		created.push( popular );
		await page.goto( `${ URL }/?page_id=${ testPage }`, { waitUntil: 'networkidle' } );
		const sections = await page.evaluate( () => [ ...document.querySelectorAll( '.b-posts' ) ].map( ( s ) => ( {
			title: s.querySelector( 'h2' ).textContent.trim(),
			view: s.classList.contains( 'b-posts--cards' ) ? 'cards' : 'blog',
			titles: [ ...s.querySelectorAll( 'h3' ) ].map( ( h ) => h.textContent.trim() ),
			more: !! s.querySelector( '.b-posts__all' ),
			covers: [ ...s.querySelectorAll( '.b-posts__cover' ) ].map( ( c ) => ( c.querySelector( 'img' ) ? 'img' : '' ) + ( c.querySelector( '.b-posts__placeholder' ) ? 'заглушка' : '' ) ),
			times: [ ...s.querySelectorAll( '.b-posts__time' ) ].map( ( t ) => t.textContent.trim() ),
			excerpts: s.querySelectorAll( '.b-posts__excerpt' ).length,
		} ) ) );
		const byTitle = Object.fromEntries( sections.map( ( s ) => [ s.title, s ] ) );
		check( 'Вид «3 карточки с обложками»: 3 последние статьи, обложки из медиатеки, анонсы, время чтения; без кнопки «Все статьи»',
			byTitle[ 'Карточки' ] && 'cards' === byTitle[ 'Карточки' ].view && 3 === byTitle[ 'Карточки' ].titles.length && byTitle[ 'Карточки' ].covers.join() === 'img,img,img' &&
			3 === byTitle[ 'Карточки' ].excerpts && byTitle[ 'Карточки' ].times[ 0 ] === `${ views[ 0 ][ 1 ] } мин чтения` && ! byTitle[ 'Карточки' ].more, JSON.stringify( byTitle[ 'Карточки' ] ) );
		const sizes = JSON.parse( wp( 'eval', "$s = wp_get_registered_image_subsizes(); echo wp_json_encode( array_map( static fn( $n ) => $s[ $n ] ?? null, array( 'trust-cover-sm', 'trust-cover', 'trust-cover-lg' ) ) );" ) );
		check( 'Размеры обложек 16:9 зарегистрированы (ТЗ, п. 8.1): 480×270, 768×432, 1200×675 с обрезкой',
			JSON.stringify( sizes.map( ( x ) => x && `${ x.width }x${ x.height }${ x.crop ? ' crop' : '' }` ) ) === JSON.stringify( [ '480x270 crop', '768x432 crop', '1200x675 crop' ] ), JSON.stringify( sizes ) );
		const img = await page.locator( '.b-posts--cards .b-posts__img' ).first().evaluate( ( el ) => ( {
			src: el.getAttribute( 'src' ),
			srcset: el.getAttribute( 'srcset' ) || '',
			sizes: el.getAttribute( 'sizes' ) || '',
			loading: el.getAttribute( 'loading' ),
			alt: el.getAttribute( 'alt' ),
			ratio: Math.round( el.naturalWidth / el.naturalHeight * 100 ) / 100,
		} ) );
		check( 'Обложка: размер trust-cover (768×432), srcset 480/768/1200, sizes по колонкам, лениво, alt="" (украшение в ссылке)',
			/-768x432\./.test( img.src ) && [ '480w', '768w', '1200w' ].every( ( w ) => img.srcset.includes( w ) ) && img.sizes.includes( '384px' ) &&
			'lazy' === img.loading && '' === img.alt && 1.78 === img.ratio, JSON.stringify( img ) );
		check( 'Источник «Рубрика»: только статьи рубрики', byTitle[ 'Рубрика' ] && JSON.stringify( byTitle[ 'Рубрика' ].titles ) === JSON.stringify( [ POSTS[ 1 ] ] ), JSON.stringify( byTitle[ 'Рубрика' ] ) );
		check( 'Источник «Вручную»: выбранные статьи в заданном порядке', byTitle[ 'Вручную' ] && JSON.stringify( byTitle[ 'Вручную' ].titles ) === JSON.stringify( [ POSTS[ 3 ], POSTS[ 2 ] ] ), JSON.stringify( byTitle[ 'Вручную' ] ) );
		check( 'Количество: 2 статьи', byTitle[ 'Две' ] && 2 === byTitle[ 'Две' ].titles.length );
		check( 'Нет подходящих статей — блока на сайте нет', ! byTitle[ 'Пусто' ] );
		await page.goto( `${ URL }/?page_id=${ popular }`, { waitUntil: 'networkidle' } );
		check( 'Источник «Популярные»: статья с наибольшим числом просмотров', ( await page.locator( '.b-posts h3' ).allTextContents() ).join() === POSTS[ 3 ] );
		wp( 'post', 'meta', 'update', ppc, 'trust_views', '0' );

		const robots = wp( 'eval', "echo get_posts( array( 'post_type' => 'post', 'title' => 'Robots.txt: как проверить файл и не закрыть сайт от поиска' ) )[0]->ID;" );
		const words = Number( wp( 'eval', `echo preg_match_all( '/[\\\\p{L}\\\\p{N}]+/u', wp_strip_all_tags( get_post( ${ robots } )->post_content ) );` ) );
		wp( 'eval', "update_field( Trust\\Fields::key( Trust\\Settings::PREFIX, 'blog_read_speed' ), 60, 'option' );" );
		const slow = Number( wp( 'eval', `echo Trust\\Blog::reading_time( ${ robots } );` ) );
		check( 'Время чтения: слова / скорость из настроек, вверх; по умолчанию 180 слов в минуту', slow === Math.ceil( words / 60 ) && views[ 0 ][ 1 ] === Math.max( 1, Math.ceil( words / 180 ) ), `${ words } слов: ${ views[ 0 ][ 1 ] } и ${ slow } мин` );
		wp( 'eval', `update_field( Trust\\Fields::key( Trust\\Settings::PREFIX, 'blog_read_speed' ), '${ savedSpeed }', 'option' );` );

		const noThumb = createPage( 'без обложки', postsBlock( `array( 'title' => 'Без обложки', 'view' => 'cards', 'source' => 'manual', 'posts' => array( ${ ppc } ) )` ) );
		created.push( noThumb );
		await page.goto( `${ URL }/?page_id=${ noThumb }`, { waitUntil: 'networkidle' } );
		check( 'Статья без обложки — заглушка в цветах сайта', ( await page.locator( '.b-posts__cover .b-posts__placeholder' ).count() ) === 1 );

		// ===== Сверка с макетами =====
		const fake = () => {
			document.querySelectorAll( '.b-posts time' ).forEach( ( t ) => ( t.textContent = '[дата]' ) );
			[ '6 мин чтения', '8 мин чтения', '7 мин чтения' ].forEach( ( v, i ) => {
				const t = document.querySelectorAll( '.b-posts__time' )[ i ];
				if ( t ) {
					t.textContent = v;
				}
			} );
		};
		const cardsOnly = createPage( 'карточки', postsBlock( "array( 'eyebrow' => 'Блог', 'title' => 'Статьи из блога', 'view' => 'cards', 'more_label' => 'Все статьи' )" ) );
		created.push( cardsOnly );
		for ( const width of [ 1280, 768, 390 ] ) {
			await compare( page, { name: 'advantages', width, theme: '.b-advantages', mockup: 'seo-bloki/02-preimushchestva.html', mock: 'section' } );
			// Галочка согласия — вместо фразы «Отправляя заявку…» (решение заказчика): в сравнении
			// обе не видны; пример номера — из настроек форм.
			await compare( page, {
				name: 'contact-swipe', width, theme: '.b-swipe', mockup: 'seo-bloki/09-knopka-svyazi.html', mock: 'section',
				before: () => ( document.querySelector( '.b-swipe__consent' ).style.display = 'none' ),
				mockBefore: () => {
					document.querySelector( 'form' ).lastElementChild.style.display = 'none';
					document.querySelector( 'input[type=tel]' ).placeholder = '+1 000 000 00 00';
				},
			} );
			await compare( page, { name: 'posts-blog', width, theme: '.b-posts', mockup: 'seo-stranitsa/index.html', mock: 'h2:Наш блог', before: fake } );
			await compare( page, { name: 'posts-cards', width, url: `${ URL }/?page_id=${ cardsOnly }`, theme: '.b-posts', mockup: 'seo-bloki/16-blog.html', mock: 'section', before: fake } );
		}

		// ===== Ширины, зоны нажатия, доступность =====
		for ( const w of [ 1440, 1024, 768, 600, 390, 320, 280 ] ) {
			await page.setViewportSize( { width: w, height: 800 } );
			await page.goto( PAGE, { waitUntil: 'networkidle' } );
			const res = await page.evaluate( () => ( {
				scroll: document.documentElement.scrollWidth > innerWidth,
				small: [ ...document.querySelectorAll( '.b-swipe a, .b-swipe button, .b-posts a' ) ].filter( ( el ) => {
					if ( ! el.offsetParent ) {
						return false;
					}
					const r = el.getBoundingClientRect();
					const extra = el.classList.contains( 'b-swipe__knob' ) ? 4 : 0; // ::before на 2 px с каждой стороны
					return r.height + extra < 44 && ! el.closest( '.check' );
				} ).map( ( el ) => el.className || el.textContent.trim() ),
			} ) );
			check( `Ширина ${ w }px: без прокрутки вбок, зоны нажатия ≥ 44 px`, ! res.scroll && 0 === res.small.length, JSON.stringify( res ) );
		}
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
		const axe = await page.evaluate( async () => {
			const r = await window.axe.run( { include: [ '.b-advantages', '.b-swipe', '.b-posts' ] }, { resultTypes: [ 'violations' ] } );
			return r.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length })` );
		} );
		check( 'axe: нет нарушений critical/serious', axe.length === 0, axe.join( ', ' ) );
		check( 'Консоль браузера на сайте без ошибок', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1440, height: 1000 } } );
		const editorErrors = [];
		editor.on( 'pageerror', ( e ) => editorErrors.push( e.message ) );
		await login( editor );
		const id = wp( 'eval', 'echo get_page_by_path( "seo/audit" )->ID;' );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ id }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		const reg = await editor.evaluate( () => [ 'trust/advantages', 'trust/contact-swipe', 'trust/posts' ].map( ( name ) => {
			const type = wp.blocks.getBlockType( name );
			return [ name, type && type.title, type && type.category, !! ( type && type.example ) ];
		} ) );
		check( 'Редактор: «Наши преимущества» — «Услуги», «Кнопка связи» — «Формы», «Статьи из блога» — «Списки», у всех превью',
			JSON.stringify( reg ) === JSON.stringify( [
				[ 'trust/advantages', 'Наши преимущества', 'trust-services', true ],
				[ 'trust/contact-swipe', 'Кнопка связи', 'trust-forms', true ],
				[ 'trust/posts', 'Статьи из блога', 'trust-lists', true ],
			] ), JSON.stringify( reg ) );
		const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-posts' ).first().waitFor( { timeout: 30000 } );
		check( 'Редактор: превью блоков на странице', ( await canvas.locator( '.b-advantages__item' ).count() ) === 6 && ( await canvas.locator( '.b-swipe__form' ).count() ) === 1 && ( await canvas.locator( '.b-posts__card' ).count() ) === 5 );
		check( 'Редактор без ошибок JavaScript', editorErrors.length === 0, editorErrors.slice( 0, 3 ).join( ' | ' ) );
	} finally {
		created.forEach( ( p ) => wp( 'post', 'delete', p, '--force' ) );
		leadsBy( 'Тест Свайп' ).forEach( ( l ) => wp( 'post', 'delete', String( l.id ), '--force' ) );
	}

	await browser.close();
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
