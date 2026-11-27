/**
 * Тест блоков шага 4.17 — разделы страницы кейса: «С чем пришёл клиент», «Что мешало росту»,
 * «Что сделали», «Результаты», «Слово клиенту», «Кто работал над проектом».
 *
 * Запуск: node wp-theme/tests/case-blocks.js
 *
 * Демо — кейс «Ремонт квартир» (тексты макета portfolio-blog-404/keys.html): шесть разделов по
 * порядку с номерами «01 · Задача» … «06 · Команда» (счётчик CSS; номера проверяет сверка с
 * макетом), проблемы с крестиками, карточки аудита 2 → 1 колонка, шкала этапов (последний —
 * пустой кружок), графики во вкладках (роли tablist / tab / tabpanel, мышь, стрелки, Home, End;
 * без JavaScript видны все графики), таблица «до / после» (подпись, th, листается с клавиатуры,
 * если не помещается), отзыв из поля кейса с «Благодарственным письмом» на весь экран, команда
 * из поля кейса (роль — или должность), якорь #case-team. Свои данные: один график — без вкладок,
 * свой надзаголовок и пустой (только номер), отзыв вручную с PDF, выбранный отзыв со ссылкой на
 * оригинал, свой состав команды, пустые разделы не выводятся. Редактор: блоки только у кейсов,
 * новый кейс — все шесть по порядку. Сверка с макетом на 1280 / 768 / 390 px, ширины 1440–280,
 * axe-core, консоль.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp } = require( './lib' );

const CASE = `${ URL }/portfolio/novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir/`;
const MOCKUP = 'portfolio-blog-404/keys.html';
const MARK = 'ТестРазделыКейса';

/** PHP: блок по имени и полям (PHP-массив). */
const block = ( name, fields ) => `Trust\\Blocks::serialize( '${ name }', ${ fields } )`;

/**
 * Опубликованный тестовый кейс с блоками.
 *
 * @param {string} title  Заголовок.
 * @param {string} blocks PHP-выражение: разметка блоков.
 * @param {string} extra  PHP: код после создания ($id — кейс).
 * @return {string} ID.
 */
function createCase( title, blocks, extra = '' ) {
	return wp( 'eval', `$id = wp_insert_post( array( 'post_type' => 'case', 'post_status' => 'publish', 'post_title' => '${ MARK } ${ title }', 'post_content' => wp_slash( ${ blocks } ) ) ); ${ extra } echo $id;` );
}

/**
 * Сравнение раздела кейса с макетом. В теме предыдущие разделы свёрнуты, а не скрыты — счётчик
 * номеров («04 · Результат») идёт как на странице; фото сотрудников и автора отзыва скрыты (в
 * макете — заглушки), картинки графиков — из исходных файлов.
 *
 * @param {import('playwright').Page} page  Страница.
 * @param {string}                    name  Имя снимка.
 * @param {number}                    width Ширина окна.
 * @param {string}                    theme Блок в теме.
 * @param {string}                    mock  Раздел в макете.
 */
async function compare( page, name, width, theme, mock ) {
	const base = path.join( OUT, `case-${ name }-${ width }` );
	const photos = () => document.querySelectorAll( '.b-case-quote__photo, .b-case-team__photo, section[aria-labelledby="quote-title"] img, #case-team img' ).forEach( ( i ) => i.style.setProperty( 'visibility', 'hidden', 'important' ) );
	await page.setViewportSize( { width, height: 900 } );
	await page.goto( CASE, { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	// В макете справа от разделов — карточка «О проекте» (шаг 5.8): колонка той же ширины.
	if ( width >= 1024 ) {
		await page.addStyleTag( { content: '.page-default__content { max-width: calc(100% - 396px) !important; }' } );
	}
	await page.evaluate( ( sel ) => {
		const el = document.querySelector( sel );
		for ( let prev = el.previousElementSibling; prev; prev = prev.previousElementSibling ) {
			prev.style.cssText += ';visibility:hidden!important;height:0!important;margin:0!important;padding:0!important;overflow:hidden!important;border:0!important';
		}
		for ( let node = el.parentElement; node && node !== document.body; node = node.parentElement ) {
			for ( let p = node.previousElementSibling; p; p = p.previousElementSibling ) {
				p.style.setProperty( 'display', 'none', 'important' );
			}
		}
		scrollTo( 0, 0 );
	}, theme );
	await page.evaluate( photos );
	await page.evaluate( () => Promise.all( [ ...document.querySelectorAll( '.b-case img' ) ].map( ( img ) => {
		img.removeAttribute( 'srcset' );
		img.loading = 'eager';
		img.src = img.src.replace( /-\d+x\d+(\.\w+)$/, '$1' );
		return img.decode().catch( () => {} );
	} ) ) );
	await page.waitForTimeout( 200 );
	await page.locator( theme ).screenshot( { path: `${ base }-theme.png` } );
	await page.goto( mockupUrl( MOCKUP ), { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	await isolate( page, mock );
	await page.evaluate( photos );
	await page.evaluate( () => Promise.all( [ ...document.querySelectorAll( 'img' ) ].map( ( img ) => {
		img.loading = 'eager';
		return img.decode().catch( () => {} );
	} ) ) );
	await page.waitForTimeout( 200 );
	await page.locator( mock ).first().screenshot( { path: `${ base }-mockup.png` } );
	const diff = comparePng( `${ base }-mockup.png`, `${ base }-theme.png`, `${ base }-diff.png` );
	check( `Макет ${ MOCKUP }, ${ name }, ${ width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
}

( async () => {
	clearLog();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const created = [];

	try {
		// ===== Демо: порядок, нумерация, шапки =====
		await page.goto( CASE, { waitUntil: 'networkidle' } );
		const heads = await page.evaluate( () => [ ...document.querySelectorAll( '.b-case' ) ].map( ( s ) => [
			s.classList[ 1 ],
			s.querySelector( '.b-case__eyebrow' ).textContent.trim(),
			s.querySelector( 'h2' ).textContent,
			s.getAttribute( 'aria-labelledby' ) === s.querySelector( 'h2' ).id,
			getComputedStyle( s ).counterIncrement,
		] ) );
		check( 'Кейс «Ремонт квартир»: шесть разделов по порядку, у каждого надзаголовок, H2 и aria-labelledby',
			JSON.stringify( heads.map( ( h ) => h.slice( 0, 4 ) ) ) === JSON.stringify( [
				[ 'b-case-task', 'Задача', 'С чем пришёл клиент', true ],
				[ 'b-case-obstacles', 'Аудит', 'Что мешало росту', true ],
				[ 'b-case-work', 'Работы', 'Что сделали', true ],
				[ 'b-case-results', 'Результат', 'Что получилось за [N] месяцев', true ],
				[ 'b-case-quote', 'Отзыв', 'Слово клиенту', true ],
				[ 'b-case-team', 'Команда', 'Кто работал над проектом', true ],
			] ), JSON.stringify( heads ) );
		const counter = await page.evaluate( () => [ getComputedStyle( document.body ).counterReset, getComputedStyle( document.querySelector( '.b-case__num' ), '::before' ).content, getComputedStyle( document.querySelector( '.b-case__num' ) ).getPropertyValue( 'display' ), document.querySelector( '.b-case__num' ).getAttribute( 'aria-hidden' ) ] );
		check( 'Номер раздела — счётчик CSS по порядку блоков («01 · …», ведущий ноль), скрыт от скринридеров',
			'case-section 0' === counter[ 0 ] && heads.every( ( h ) => 'case-section 1' === h[ 4 ] ) && /counter\(case-section, decimal-leading-zero\) " · "/.test( counter[ 1 ] ) && 'true' === counter[ 3 ], JSON.stringify( counter ) );

		// ===== Задача, аудит, работы =====
		const body = await page.evaluate( () => ( {
			text: document.querySelector( '.b-case-task__text' ).textContent.trim().slice( 0, 30 ),
			problems: [ ...document.querySelectorAll( '.b-case-task__item' ) ].map( ( li ) => [ !! li.querySelector( '.b-case-task__icon[aria-hidden="true"] svg' ), li.textContent.trim().slice( 0, 20 ) ] ),
			cards: [ ...document.querySelectorAll( '.b-case-obstacles__card' ) ].map( ( c ) => c.querySelector( '.b-case-obstacles__number' ).textContent + '|' + c.querySelector( '.b-case-obstacles__label' ).textContent.slice( 0, 12 ) ),
			steps: [ ...document.querySelectorAll( '.b-case-work__step' ) ].map( ( s ) => [ s.querySelector( '.b-case-work__term' ).textContent, s.querySelector( 'h3' ).textContent ] ),
			list: document.querySelector( '.b-case-work__list' ).tagName,
			last: ( () => {
				const steps = [ ...document.querySelectorAll( '.b-case-work__step' ) ];
				const dot = ( s ) => getComputedStyle( s.querySelector( '.b-case-work__dot' ) );
				return [ dot( steps[ 0 ] ).backgroundColor, dot( steps.at( -1 ) ).backgroundColor, dot( steps.at( -1 ) ).borderTopWidth, getComputedStyle( steps.at( -1 ).querySelector( '.b-case-work__line' ) ).display, getComputedStyle( steps[ 0 ].querySelector( '.b-case-work__line' ) ).display ];
			} )(),
		} ) );
		check( '«С чем пришёл клиент»: текст и 3 проблемы с красным крестиком', body.text.startsWith( '[Описание клиента' ) && 3 === body.problems.length && body.problems.every( ( p ) => p[ 0 ] ) && body.problems[ 0 ][ 1 ].startsWith( 'Сайт на конструкторе' ), JSON.stringify( body.problems ) );
		check( '«Что мешало росту»: 4 карточки — число и подпись', JSON.stringify( body.cards ) === JSON.stringify( [ '[N]|важных запро', '[N]|ошибок в Веб', '[N] с|загружалась ', '0|целей в Метр' ] ), JSON.stringify( body.cards ) );
		check( '«Что сделали»: 5 этапов в нумерованном списке — срок и H3; у последнего пустой кружок без линии',
			'OL' === body.list && 5 === body.steps.length && 'Месяц 1,Аудит и семантика' === body.steps[ 0 ].join() && 'Каждый месяц,Аналитика и отчёты' === body.steps[ 4 ].join() &&
			JSON.stringify( body.last ) === JSON.stringify( [ 'rgb(109, 40, 217)', 'rgb(255, 255, 255)', '3px', 'none', 'block' ] ), JSON.stringify( [ body.steps, body.last ] ) );

		// ===== Результаты: вкладки и таблица =====
		const tabsState = () => page.evaluate( () => {
			const tabs = [ ...document.querySelectorAll( '.b-case-results [role="tab"]' ) ];
			return {
				selected: tabs.findIndex( ( t ) => 'true' === t.getAttribute( 'aria-selected' ) ),
				tabindex: tabs.map( ( t ) => t.tabIndex ).join(),
				visible: [ ...document.querySelectorAll( '.b-case-results__panel' ) ].map( ( p ) => 'none' !== getComputedStyle( p ).display ),
				focus: tabs.indexOf( document.activeElement ),
			};
		} );
		const res = await page.evaluate( () => {
			const list = document.querySelector( '.b-case-results [role="tablist"]' );
			const tabs = [ ...list.querySelectorAll( '[role="tab"]' ) ];
			const panels = [ ...document.querySelectorAll( '.b-case-results__panel' ) ];
			return {
				label: list.getAttribute( 'aria-label' ),
				tabs: tabs.map( ( t ) => t.textContent ),
				links: tabs.every( ( t, i ) => panels[ i ].id === t.getAttribute( 'aria-controls' ) && panels[ i ].getAttribute( 'aria-labelledby' ) === t.id && 'tabpanel' === panels[ i ].getAttribute( 'role' ) ),
				alts: panels.map( ( p ) => p.querySelector( 'img' ).alt ),
				caption: panels[ 0 ].querySelector( 'figcaption' ).textContent,
				lazy: panels.every( ( p ) => 'lazy' === p.querySelector( 'img' ).loading && p.querySelector( 'img' ).getAttribute( 'srcset' ) ),
			};
		} );
		check( '«Результаты»: 4 вкладки (tablist «Графики результатов», tab ↔ tabpanel), alt графиков, подпись «Демонстрационный график…», srcset и отложенная загрузка',
			'Графики результатов' === res.label && 'Визиты из поиска,Позиции,Заявки,Видимость' === res.tabs.join() && res.links && 'График визитов из поисковых систем по месяцам' === res.alts[ 0 ] && res.alts.every( Boolean ) && res.caption.startsWith( 'Демонстрационный график.' ) && res.lazy, JSON.stringify( res ) );
		const t0 = await tabsState();
		await page.locator( '.b-case-results [role="tab"]', { hasText: 'Заявки' } ).click();
		const t1 = await tabsState();
		await page.keyboard.press( 'ArrowRight' );
		const t2 = await tabsState();
		await page.keyboard.press( 'ArrowRight' );
		const t3 = await tabsState();
		await page.keyboard.press( 'End' );
		await page.keyboard.press( 'Home' );
		const t4 = await tabsState();
		await page.keyboard.press( 'ArrowLeft' );
		const t5 = await tabsState();
		check( 'Вкладки: сначала первая (виден один график), нажатие — «Заявки»; стрелки → (по кругу), Home, ← — с выбором и фокусом, tabindex только у выбранной',
			0 === t0.selected && '0,-1,-1,-1' === t0.tabindex && 1 === t0.visible.filter( Boolean ).length && t0.visible[ 0 ] &&
			2 === t1.selected && t1.visible[ 2 ] && 3 === t2.selected && 3 === t2.focus && 0 === t3.selected && 0 === t4.selected && 3 === t5.selected && 3 === t5.focus && '-1,-1,-1,0' === t5.tabindex && 1 === t5.visible.filter( Boolean ).length,
			JSON.stringify( [ t0, t1, t2, t3, t4, t5 ] ) );
		// Выбрана «Видимость». График открывается на весь экран (правка заказчика, как скриншоты в «Карусели кейсов»).
		const zoom = await page.evaluate( () => {
			const a = document.querySelector( '.b-case-results__panel.is-active .b-case-results__open' );
			return a ? [ a.hasAttribute( 'data-trust-lightbox' ), /case-visibility\.jpg$/.test( a.getAttribute( 'href' ) ), a.getAttribute( 'aria-label' ), !! a.querySelector( '.b-case-results__zoom[aria-hidden="true"] svg' ), getComputedStyle( a ).cursor ] : null;
		} );
		await page.locator( '.b-case-results__panel.is-active .b-case-results__open' ).click();
		await page.waitForSelector( 'dialog.lightbox[open]', { timeout: 5000 } ).catch( () => {} );
		const zoomBox = await page.evaluate( () => {
			const d = document.querySelector( 'dialog.lightbox[open]' );
			return d ? [ d.querySelector( '.lightbox__caption' ).textContent, /case-visibility\.jpg$/.test( d.querySelector( '.lightbox__img' ).src ), d.querySelector( '.lightbox__img' ).alt ] : null;
		} );
		await page.keyboard.press( 'Escape' );
		await page.waitForTimeout( 200 );
		const zoomBack = await page.evaluate( () => [ ! document.querySelector( 'dialog.lightbox[open]' ), document.activeElement.classList.contains( 'b-case-results__open' ) ] );
		check( 'График по нажатию открывается на весь экран: оригинал картинки, подпись — название вкладки, alt; Esc закрывает, фокус возвращается (правка заказчика)',
			JSON.stringify( zoom ) === JSON.stringify( [ true, true, 'Открыть на весь экран: График видимости сайта в поиске', true, 'zoom-in' ] ) &&
			JSON.stringify( zoomBox ) === JSON.stringify( [ 'Видимость', true, 'График видимости сайта в поиске' ] ) && JSON.stringify( zoomBack ) === JSON.stringify( [ true, true ] ),
			JSON.stringify( [ zoom, zoomBox, zoomBack ] ) );
		const table = await page.evaluate( () => {
			const wrap = document.querySelector( '.b-case-results__table' );
			const t = wrap.querySelector( 'table' );
			return {
				region: [ wrap.getAttribute( 'role' ), document.getElementById( wrap.getAttribute( 'aria-labelledby' ) ).textContent ],
				caption: [ t.caption.textContent, t.caption.classList.contains( 'screen-reader-text' ) ],
				head: [ ...t.tHead.rows[ 0 ].cells ].map( ( c ) => c.getAttribute( 'scope' ) + ':' + c.textContent ),
				rows: [ ...t.tBodies[ 0 ].rows ].map( ( r ) => [ r.cells[ 0 ].tagName, r.cells[ 0 ].getAttribute( 'scope' ), r.cells[ 0 ].textContent, r.cells[ 2 ].className ].join( '|' ) ),
				after: getComputedStyle( t.tBodies[ 0 ].rows[ 0 ].cells[ 2 ] ).color,
				tabindex: wrap.getAttribute( 'tabindex' ),
			};
		} );
		check( 'Таблица «до / после»: подпись для скринридеров, столбцы «Показатель / До / Через [N] мес.», 4 строки с th scope=row, «после» фиолетовым; на 1280 не листается — не в порядке табуляции',
			'region,Показатели до и после работ' === table.region.join() && table.caption[ 1 ] && 'col:Показатель,col:До,col:Через [N] мес.' === table.head.join() && 4 === table.rows.length && 'TH|row|Визиты из поиска в месяц|is-after' === table.rows[ 0 ] && 'rgb(76, 29, 149)' === table.after && null === table.tabindex,
			JSON.stringify( table ) );

		// ===== Отзыв и команда =====
		const quote = await page.evaluate( () => {
			const q = document.querySelector( '.b-case-quote' );
			const link = q.querySelector( '.b-case-quote__link' );
			return [ q.querySelector( 'figure blockquote' ).textContent.trim().slice( 0, 30 ), q.querySelector( '.b-case-quote__name' ).textContent, q.querySelector( '.b-case-quote__role' ).textContent, link.textContent.trim(), link.hasAttribute( 'data-trust-lightbox' ), /\.jpg$/.test( link.getAttribute( 'href' ) ), getComputedStyle( q.querySelector( '.b-case-quote__card' ) ).backgroundColor ];
		} );
		check( '«Слово клиенту»: отзыв из поля кейса — цитата, имя, «Директор, [Ремонт&Дизайн]», «Благодарственное письмо» (скан на весь экран), тёмная карточка',
			JSON.stringify( quote ) === JSON.stringify( [ '[Отзыв клиента о проекте: что ', '[Имя Фамилия]', 'Директор, [Ремонт&Дизайн]', 'Благодарственное письмо', true, true, 'rgb(30, 27, 75)' ] ), JSON.stringify( quote ) );
		await page.locator( '.b-case-quote__link' ).click();
		await page.waitForSelector( 'dialog.lightbox[open]', { timeout: 5000 } ).catch( () => {} );
		const box = await page.evaluate( () => {
			const d = document.querySelector( 'dialog.lightbox[open]' );
			return d ? [ d.querySelector( '.lightbox__caption' ).textContent, /\.jpg$/.test( d.querySelector( '.lightbox__img' ).src ) ] : null;
		} );
		await page.keyboard.press( 'Escape' );
		check( '«Благодарственное письмо» открывает скан на весь экран с подписью', box && 'Благодарственное письмо — [Имя Фамилия]' === box[ 0 ] && box[ 1 ], JSON.stringify( box ) );
		const repair = wp( 'eval', 'echo get_page_by_path( "novyy-sayt-i-seo-dlya-kompanii-po-remontu-kvartir", OBJECT, "case" )->ID;' );
		const expected = JSON.parse( wp( 'eval', `echo wp_json_encode( array_map( static fn( $r ) => get_the_title( $r['member'] ) . '|' . ( '' !== $r['role'] ? $r['role'] : get_post_meta( $r['member'], 'trust_position', true ) ), Trust\\Cases::team( ${ repair } ) ) );` ) );
		const team = await page.evaluate( () => ( {
			id: document.querySelector( '.b-case-team' ).id,
			cards: [ ...document.querySelectorAll( '.b-case-team__card' ) ].map( ( c ) => c.querySelector( '.b-case-team__name' ).textContent + '|' + c.querySelector( '.b-case-team__role' ).textContent ),
			alt: [ ...document.querySelectorAll( '.b-case-team__photo' ) ].map( ( i ) => ( 'IMG' === i.tagName ? i.alt : 'нет' ) ),
		} ) );
		check( '«Кто работал над проектом»: команда из поля кейса по порядку (роль в проекте, пусто — должность: «Frontend-разработчик»), фото декоративные, якорь #case-team',
			'case-team' === team.id && JSON.stringify( team.cards ) === JSON.stringify( expected ) && 3 === team.cards.length && team.cards[ 0 ].endsWith( '|SEO-специалист, руководитель проекта' ) && team.cards[ 2 ].endsWith( '|Frontend-разработчик' ) && team.alt.every( ( a ) => '' === a ), JSON.stringify( [ team, expected ] ) );

		// ===== Раскладка на разных ширинах =====
		const layout = {};
		for ( const width of [ 1280, 1100, 768, 390 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( CASE, { waitUntil: 'domcontentloaded' } );
			layout[ width ] = await page.evaluate( () => {
				const cols = ( sel ) => new Set( [ ...document.querySelectorAll( sel ) ].map( ( e ) => Math.round( e.getBoundingClientRect().left ) ) ).size;
				return [ Math.round( document.querySelector( '.page-default__content' ).getBoundingClientRect().width ), cols( '.b-case-obstacles__card' ), cols( '.b-case-team__card' ) ];
			} );
		}
		const narrow = await page.evaluate( async () => {
			document.querySelector( '.page-default__content' ).style.maxWidth = '600px';
			await new Promise( ( r ) => requestAnimationFrame( r ) );
			return new Set( [ ...document.querySelectorAll( '.b-case-team__card' ) ].map( ( e ) => Math.round( e.getBoundingClientRect().left ) ) ).size;
		} );
		check( 'Разделы — на всю ширину страницы до карточки «О проекте» (решение заказчика; 1280: 1184 px); аудит 2 → 1 колонка (уже 520 px), команда 3 в ряд, в блоке уже 640 px — в колонку',
			1184 === layout[ 1280 ][ 0 ] && JSON.stringify( [ layout[ 1280 ].slice( 1 ), layout[ 1100 ].slice( 1 ), layout[ 768 ].slice( 1 ), layout[ 390 ].slice( 1 ) ] ) === JSON.stringify( [ [ 2, 3 ], [ 2, 3 ], [ 2, 3 ], [ 1, 1 ] ] ) && 1 === narrow, JSON.stringify( [ layout, narrow ] ) );
		const wide = [];
		let scroll = null;
		for ( const width of [ 1440, 1024, 768, 390, 320, 280 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( CASE, { waitUntil: 'networkidle' } );
			if ( await page.evaluate( () => document.documentElement.scrollWidth > innerWidth ) ) {
				wide.push( width );
			}
			if ( 280 === width ) {
				// Таблица с длинными значениями: не помещается — листается в рамке, получает tabindex.
				scroll = await page.evaluate( async () => {
					const w = document.querySelector( '.b-case-results__table' );
					const before = w.getAttribute( 'tabindex' );
					w.querySelector( 'td' ).textContent = '1 000 000 000 000';
					window.dispatchEvent( new Event( 'resize' ) );
					await new Promise( ( r ) => setTimeout( r, 50 ) );
					return [ before, w.scrollWidth > w.clientWidth, w.getAttribute( 'tabindex' ), document.documentElement.scrollWidth <= innerWidth ];
				} );
			}
		}
		check( 'Ширины 1440–280: без прокрутки вбок; таблица, которая не помещается, листается внутри рамки и доступна с клавиатуры (tabindex=0), иначе — не в порядке табуляции',
			0 === wide.length && scroll && null === scroll[ 0 ] && scroll[ 1 ] && '0' === scroll[ 2 ] && scroll[ 3 ], JSON.stringify( [ wide, scroll ] ) );

		// ===== Без JavaScript =====
		const noJs = await browser.newContext( { javaScriptEnabled: false, viewport: { width: 1280, height: 900 } } );
		const plain = await noJs.newPage();
		await plain.goto( CASE, { waitUntil: 'domcontentloaded' } );
		const nj = await plain.evaluate( () => [ getComputedStyle( document.querySelector( '.b-case-results__tabs' ) ).display, [ ...document.querySelectorAll( '.b-case-results__panel' ) ].filter( ( p ) => 'none' !== getComputedStyle( p ).display ).length ] );
		await noJs.close();
		check( 'Без JavaScript: вкладок нет, видны все 4 графика с подписями', 'none' === nj[ 0 ] && 4 === nj[ 1 ], JSON.stringify( nj ) );

		// ===== Сверка с макетом =====
		const sections = [
			[ 'task', '.b-case-task', 'section[aria-labelledby="task-title"]' ],
			[ 'obstacles', '.b-case-obstacles', 'section[aria-labelledby="issues-title"]' ],
			[ 'work', '.b-case-work', 'section[aria-labelledby="work-title"]' ],
			[ 'results', '.b-case-results', 'section[aria-labelledby="result-title"]' ],
			[ 'quote', '.b-case-quote', 'section[aria-labelledby="quote-title"]' ],
			[ 'team', '.b-case-team', '#case-team' ],
		];
		for ( const width of [ 1280, 768, 390 ] ) {
			for ( const [ name, theme, mock ] of sections ) {
				await compare( page, name, width, theme, mock );
			}
		}

		// ===== Свои данные =====
		const chart = wp( 'eval', 'echo get_posts( array( "post_type" => "attachment", "post_status" => "any", "numberposts" => 1, "fields" => "ids", "meta_key" => "_trust_demo_id", "meta_value" => "attachment:case-leads.jpg" ) )[0] ?? 0;' );
		const pdf = wp( 'eval', `$f = wp_upload_bits( '${ MARK }.pdf', null, '%PDF-1.4' ); echo wp_insert_attachment( array( 'post_title' => '${ MARK } pdf', 'post_mime_type' => 'application/pdf', 'post_status' => 'inherit' ), $f['file'] );` );
		created.push( pdf );
		const review = wp( 'eval', `$id = wp_insert_post( array( 'post_type' => 'review', 'post_status' => 'publish', 'post_title' => '${ MARK } отзыв' ) ); foreach ( array( 'trust_kind' => 'text', 'trust_name' => 'Ольга', 'trust_company' => 'Компания', 'trust_text' => 'Текст выбранного отзыва.', 'trust_source' => 'https://example.com/review' ) as $k => $v ) { update_post_meta( $id, $k, $v ); } echo $id;` );
		created.push( review );
		const member = wp( 'eval', 'echo get_posts( array( "post_type" => "team", "numberposts" => 1, "fields" => "ids", "orderby" => "ID", "order" => "ASC" ) )[0];' );
		const own = createCase( 'свой', [
			block( 'case-task', "array( 'eyebrow' => 'Клиент', 'title' => 'Свой заголовок', 'text' => '<p>Только текст.</p>' )" ),
			block( 'case-quote', "array( 'source' => 'case' )" ),
			block( 'case-results', `array( 'eyebrow' => '', 'tabs' => array( array( 'label' => 'Один', 'image' => ${ Number( chart ) } ) ) )` ),
			block( 'case-quote', `array( 'source' => 'manual', 'text' => 'Цитата вручную.', 'name' => 'Иван', 'role' => 'Директор', 'letter' => ${ Number( pdf ) } )` ),
			block( 'case-quote', `array( 'source' => 'review', 'review' => ${ Number( review ) } )` ),
			block( 'case-team', `array( 'members' => array( array( 'member' => ${ Number( member ) }, 'role' => 'Своя роль' ) ) )` ),
			block( 'case-obstacles', 'array()' ),
		].join( ' . "\\n\\n" . ' ) );
		created.push( own );
		const empty = createCase( 'пустой', block( 'case-team', 'array()' ) + ' . ' + block( 'case-quote', 'array()' ) );
		created.push( empty );
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( `${ URL }/?p=${ own }`, { waitUntil: 'networkidle' } );
		const mine = await page.evaluate( () => ( {
			sections: [ ...document.querySelectorAll( '.b-case' ) ].map( ( s ) => s.classList[ 1 ] + ':' + s.querySelector( '.b-case__eyebrow' ).textContent.trim() + ':' + s.querySelector( 'h2' ).textContent ),
			problems: document.querySelectorAll( '.b-case-task__item' ).length,
			tabs: !! document.querySelector( '.b-case-results [role="tablist"]' ),
			panel: [ document.querySelectorAll( '.b-case-results__panel' ).length, document.querySelector( '.b-case-results__panel' ).hasAttribute( 'role' ), ! document.querySelector( '.b-case-results__panel figcaption' ), document.querySelector( '.b-case-results__panel img' ).alt ],
			table: !! document.querySelector( '.b-case-results__table' ),
			numOnly: getComputedStyle( document.querySelector( '.b-case-results .b-case__num' ), '::before' ).content,
			quotes: [ ...document.querySelectorAll( '.b-case-quote' ) ].map( ( q ) => {
				const a = q.querySelector( '.b-case-quote__link' );
				return [ q.querySelector( 'blockquote' ).textContent.trim(), q.querySelector( '.b-case-quote__name' ).textContent, q.querySelector( '.b-case-quote__role' ) ? q.querySelector( '.b-case-quote__role' ).textContent : '', !! q.querySelector( '.b-case-quote__photo--empty' ), a.textContent.trim(), a.getAttribute( 'target' ), a.hasAttribute( 'data-trust-lightbox' ) ].join( '|' );
			} ),
			team: [ ...document.querySelectorAll( '.b-case-team__card' ) ].map( ( c ) => c.querySelector( '.b-case-team__role' ).textContent ),
		} ) );
		check( 'Свои данные: свой надзаголовок и заголовок, текст без проблем; отзыв из кейса без отзыва и пустой «Что мешало росту» не выводятся',
			JSON.stringify( mine.sections.map( ( s ) => s.split( ':' )[ 0 ] ) ) === JSON.stringify( [ 'b-case-task', 'b-case-results', 'b-case-quote', 'b-case-quote', 'b-case-team' ] ) && 'b-case-task:Клиент:Свой заголовок' === mine.sections[ 0 ] && 0 === mine.problems, JSON.stringify( mine.sections ) );
		check( 'Один график — без вкладок (обычная картинка с подписью-заглушкой нет), alt по названию из медиатеки; без строк — таблицы нет; пустой надзаголовок — только номер',
			! mine.tabs && JSON.stringify( mine.panel.slice( 0, 3 ) ) === JSON.stringify( [ 1, false, true ] ) && mine.panel[ 3 ] && ! mine.table && /^counter\(case-section, decimal-leading-zero\)$/.test( mine.numOnly ), JSON.stringify( [ mine.panel, mine.table, mine.numOnly ] ) );
		check( 'Отзыв вручную: цитата, имя, должность, силуэт без фото, письмо PDF — в новой вкладке; выбранный отзыв без письма — «Оригинал отзыва» в новой вкладке',
			JSON.stringify( mine.quotes ) === JSON.stringify( [ 'Цитата вручную.|Иван|Директор|true|Благодарственное письмо (PDF, откроется в новой вкладке)|_blank|false', 'Текст выбранного отзыва.|Ольга|Компания|true|Оригинал отзыва (откроется в новой вкладке)|_blank|false' ] ), JSON.stringify( mine.quotes ) );
		check( 'Свой состав команды — вместо команды кейса, со своей ролью', JSON.stringify( mine.team ) === JSON.stringify( [ 'Своя роль' ] ), JSON.stringify( mine.team ) );
		await page.goto( `${ URL }/?p=${ empty }`, { waitUntil: 'networkidle' } );
		check( 'Кейс без команды и отзыва: эти разделы на сайте не выводятся', 0 === await page.locator( '.b-case' ).count() );

		// ===== Доступность =====
		await page.goto( CASE, { waitUntil: 'networkidle' } );
		await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
		const axe = await page.evaluate( async () => {
			const r = await window.axe.run( '.page-default__content', { resultTypes: [ 'violations' ] } );
			return r.violations.filter( ( x ) => [ 'critical', 'serious' ].includes( x.impact ) ).map( ( x ) => `${ x.id } (${ x.nodes.length })` );
		} );
		check( 'axe: нет нарушений critical/serious в разделах кейса', 0 === axe.length, axe.join( ', ' ) );
		check( 'Консоль браузера на сайте без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1400, height: 1000 } } );
		const editorErrors = collectConsoleErrors( editor );
		await login( editor );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ repair }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ) && wp.data.select( 'core/block-editor' ).getBlocks().length, null, { timeout: 60000 } );
		const names = [ 'trust/case-task', 'trust/case-obstacles', 'trust/case-work', 'trust/case-results', 'trust/case-quote', 'trust/case-team' ];
		const reg = await editor.evaluate( ( list ) => {
			const allowed = wp.data.select( 'core/block-editor' ).getSettings().allowedBlockTypes;
			return list.map( ( name ) => {
				const type = wp.blocks.getBlockType( name );
				return [ type && type.title, type && type.category, !! ( type && type.example ), Array.isArray( allowed ) && allowed.includes( name ) ].join( ':' );
			} );
		}, names );
		check( 'Редактор кейса: шесть блоков в категории «Кейс», с превью, доступны',
			JSON.stringify( reg ) === JSON.stringify( [ 'С чем пришёл клиент', 'Что мешало росту', 'Что сделали', 'Результаты', 'Слово клиенту', 'Кто работал над проектом' ].map( ( t ) => `${ t }:trust-case:true:true` ) ), JSON.stringify( reg ) );
		const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-case-team' ).first().waitFor( { timeout: 60000 } );
		const preview = await canvas.locator( 'body' ).evaluate( () => [ document.querySelectorAll( '.b-case' ).length, document.querySelectorAll( '.b-case-results__panel' ).length, [ ...document.querySelectorAll( '.b-case-results__panel' ) ].filter( ( p ) => 'none' !== getComputedStyle( p ).display ).length, 'none' !== getComputedStyle( document.querySelector( '.b-case-results__tabs' ) ).display ] );
		check( 'Редактор: превью шести разделов; у «Результатов» видны вкладки и первый график', JSON.stringify( preview ) === JSON.stringify( [ 6, 4, 1, true ] ), JSON.stringify( preview ) );
		await editor.goto( `${ URL }/wp-admin/post-new.php?post_type=case` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ) && wp.data.select( 'core/block-editor' ).getBlocks().length, null, { timeout: 60000 } );
		const fresh = await editor.evaluate( () => wp.data.select( 'core/block-editor' ).getBlocks().map( ( b ) => b.name ) );
		check( 'Новый кейс: в содержимом сразу шесть разделов по порядку', JSON.stringify( fresh ) === JSON.stringify( names ), JSON.stringify( fresh ) );
		const pageId = wp( 'eval', 'echo get_page_by_path( "portfolio" )->ID;' );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ pageId }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ) && Array.isArray( wp.data.select( 'core/block-editor' ).getSettings().allowedBlockTypes ), null, { timeout: 60000 } );
		const onPage = await editor.evaluate( ( list ) => list.filter( ( name ) => wp.data.select( 'core/block-editor' ).getSettings().allowedBlockTypes.includes( name ) ), names );
		check( 'На страницах блоков кейса нет', 0 === onPage.length, JSON.stringify( onPage ) );
		check( 'Редактор без ошибок JavaScript', 0 === editorErrors.length, editorErrors.slice( 0, 3 ).join( ' | ' ) );
		await editor.close();
	} finally {
		created.forEach( ( id ) => wp( 'post', 'delete', String( id ), '--force' ) );
	}

	await browser.close();
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
