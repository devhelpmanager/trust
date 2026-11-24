/**
 * Тест блоков шага 4.10 — страница «О компании»: «Чем мы занимаемся», «Как мы работаем», «Как мы
 * росли», «Слово руководителя», «Сертификаты и рейтинги», «Нам доверяют», «Где мы работаем».
 *
 * Запуск: node wp-theme/tests/about-blocks.js
 *
 * Демо на /o-kompanii/ (порядок блоков и тексты из макета o-nas/o-kompanii.html), ссылки
 * направлений на страницы, номера принципов и значок вместо номера, годы и выделенное последнее
 * событие, руководитель из «Сотрудников» (фото, имя, должность; кнопка «Написать руководителю»
 * открывает окно с формой, сообщение — в «Заявки → Обращения к руководителю», не в «Все заявки»),
 * сертификаты (скан — на весь экран, PDF — в новой вкладке, без картинки — значок; на телефоне —
 * строки), логотипы (картинка с alt или название, ссылка на сайт клиента, «Смотреть кейсы» →
 * «Портфолио»), галерея офиса (первое фото крупно, подписи, на весь экран, адрес из настроек),
 * пустые блоки, сверка с макетом на 1280 / 768 / 390 px, ширины 1440–280, axe-core, консоль,
 * редактор — категория «Компания» и превью.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp, resetFormLimits } = require( './lib' );

const PAGE = `${ URL }/o-kompanii/`;
const MOCKUP = 'o-nas/o-kompanii.html';
const MARK = 'ТестОКомпании';

/** PHP: блок по имени и полям (PHP-массив). */
const block = ( name, fields ) => `Trust\\Blocks::serialize( '${ name }', ${ fields } )`;

/**
 * Опубликованная тестовая страница «О компании» с блоками.
 *
 * @param {string} title  Заголовок.
 * @param {string} blocks PHP-выражение: разметка блоков.
 * @return {string} ID.
 */
function createPage( title, blocks ) {
	const content = wp( 'eval', `echo ${ blocks };` );
	const id = wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK } ${ title }`, '--post_status=publish', `--post_content=${ content }`, '--porcelain' );
	wp( 'post', 'meta', 'update', id, '_wp_page_template', 'templates/about.php' );
	return id;
}

/**
 * Снимки блока темы и секции макета и их сравнение.
 *
 * @param {import('playwright').Page} page  Страница.
 * @param {string}                    name  Имя снимка.
 * @param {number}                    width Ширина окна.
 * @param {string}                    theme Блок в теме.
 * @param {string}                    mock  Секция в макете.
 */
async function compare( page, name, width, theme, mock ) {
	const base = path.join( OUT, `about-${ name }-${ width }` );
	await page.setViewportSize( { width, height: 900 } );
	await page.goto( PAGE, { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	await isolate( page, theme );
	await page.waitForTimeout( 300 );
	await page.locator( theme ).first().screenshot( { path: `${ base }-theme.png` } );
	await page.goto( mockupUrl( MOCKUP ), { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	await isolate( page, mock );
	await page.waitForTimeout( 300 );
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
		// ===== Демо на «О компании» =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const order = await page.evaluate( () => [ ...document.querySelectorAll( '.page-entry__content > *' ) ].map( ( s ) => s.className.split( ' ' )[ 0 ] ) );
		check( 'Порядок блоков как в макете: первый экран, направления, принципы, история, руководитель, сертификаты, клиенты, офис, «Обсудим ваш проект»',
			JSON.stringify( order ) === JSON.stringify( [ 'b-hero-section', 'b-dirs', 'b-principles', 'b-history', 'b-leader', 'b-certs', 'b-clients', 'b-office', 'b-cta' ] ), order.join( ', ' ) );

		const d = await page.locator( '.b-dirs' ).evaluate( ( el ) => ( {
			labelled: el.getAttribute( 'aria-labelledby' ) === el.querySelector( 'h2' ).id,
			head: [ el.querySelector( '.eyebrow' ).textContent, el.querySelector( 'h2' ).textContent ],
			cards: [ ...el.querySelectorAll( '.b-dirs__card' ) ].map( ( c ) => [ c.tagName, c.querySelector( 'h3' ).textContent.trim(), c.tagName === 'A' ? new URL( c.href ).pathname : '', !! c.querySelector( '.b-dirs__icon svg' ), ( c.querySelector( '.b-dirs__more' ) || { textContent: '' } ).textContent.trim() ] ),
		} ) );
		check( '«Чем мы занимаемся»: надзаголовок «Направления», H2, 6 карточек со значком и «Подробнее»',
			d.labelled && 'Направления|Чем мы занимаемся' === d.head.join( '|' ) && 6 === d.cards.length && d.cards.every( ( c ) => 'A' === c[ 0 ] && c[ 3 ] && 'Подробнее' === c[ 4 ] ), JSON.stringify( d ) );
		check( '«Чем мы занимаемся»: карточка целиком ведёт на страницу направления (Поддержка сайтов → /site-support/)',
			JSON.stringify( d.cards.map( ( c ) => c[ 2 ] ) ) === JSON.stringify( [ '/seo/', '/geo/', '/sozdanie-saytov/', '/site-support/', '/kontekstnaya-reklama/', '/ai/' ] ) && 'Поддержка сайтов' === d.cards[ 3 ][ 1 ], JSON.stringify( d.cards.map( ( c ) => c.slice( 1, 3 ) ) ) );
		const row = await page.locator( '.b-dirs__card' ).evaluateAll( ( cards ) => cards.every( ( c ) => {
			const icon = c.querySelector( '.b-dirs__icon' ).getBoundingClientRect();
			const title = c.querySelector( 'h3' ).getBoundingClientRect();
			const text = c.querySelector( '.b-dirs__text' ).getBoundingClientRect();
			return title.left >= icon.right + 12 && title.top < icon.bottom && title.bottom > icon.top && text.top >= icon.bottom && Math.abs( text.left - icon.left ) < 1;
		} ) );
		check( '«Чем мы занимаемся»: название справа от значка, текст под ними на всю ширину карточки (правка заказчика)', row );
		await page.locator( '.b-dirs__card' ).first().hover();
		await page.waitForTimeout( 300 );
		const hover = await page.locator( '.b-dirs__card' ).first().evaluate( ( el ) => [ getComputedStyle( el ).borderColor, getComputedStyle( el ).boxShadow !== 'none' ] );
		check( 'Наведение на карточку направления: фиолетовая рамка и тень', 'rgb(185, 162, 255)' === hover[ 0 ] && hover[ 1 ], JSON.stringify( hover ) );

		const p = await page.locator( '.b-principles' ).evaluate( ( el ) => ( {
			bg: getComputedStyle( el ).backgroundColor,
			ol: 'OL' === el.querySelector( '.b-principles__list' ).tagName,
			nums: [ ...el.querySelectorAll( '.b-principles__num' ) ].map( ( n ) => n.textContent ),
			titles: [ ...el.querySelectorAll( 'h3' ) ].map( ( h ) => h.textContent ),
			line: getComputedStyle( el.querySelector( '.b-principles__list > li' ) ).borderTopColor,
		} ) );
		check( '«Как мы работаем»: фиолетовый фон, нумерованный список 01–06, заголовки H3, белая линия над пунктом',
			'rgb(91, 33, 182)' === p.bg && p.ol && '01,02,03,04,05,06' === p.nums.join() && 'Прозрачный состав работ' === p.titles[ 0 ] && 6 === p.titles.length && 'rgb(255, 255, 255)' === p.line, JSON.stringify( p ) );

		const h = await page.locator( '.b-history' ).evaluate( ( el ) => ( {
			years: [ ...el.querySelectorAll( '.b-history__year' ) ].map( ( y ) => y.textContent ),
			texts: el.querySelectorAll( '.b-history__text' ).length,
			lines: [ ...el.querySelectorAll( '.b-history__list > li' ) ].map( ( li ) => getComputedStyle( li ).borderTopColor ),
			dots: el.querySelectorAll( '.b-history__dot[aria-hidden="true"]' ).length,
		} ) );
		check( '«Как мы росли»: 5 событий с годом и текстом, точки на линии, у последнего линия фиолетовая',
			5 === h.years.length && 5 === h.texts && 5 === h.dots && 'rgb(224, 215, 245)' === h.lines[ 0 ] && 'rgb(109, 40, 217)' === h.lines[ 4 ], JSON.stringify( h ) );

		const l = await page.locator( '.b-leader' ).evaluate( ( el ) => ( {
			bg: getComputedStyle( el ).backgroundColor,
			h2: el.querySelector( 'h2' ).textContent + ' ' + ( el.getAttribute( 'aria-labelledby' ) === el.querySelector( 'h2' ).id ),
			photo: [ el.querySelector( 'img.b-leader__photo' ).getAttribute( 'alt' ), el.querySelector( 'img.b-leader__photo' ).getAttribute( 'loading' ) ],
			quote: el.querySelector( 'blockquote' ).textContent.trim(),
			name: el.querySelector( '.b-leader__name' ).textContent,
			role: el.querySelector( '.b-leader__role' ).textContent,
			buttons: [ ...el.querySelectorAll( '.b-leader__btn' ) ].map( ( b ) => [ b.tagName, b.textContent.trim(), b.getAttribute( 'aria-haspopup' ), !! el.querySelector( `dialog#${ b.dataset.trustModal }.b-leader__modal` ) ].join( ' ' ) ),
		} ) );
		check( '«Слово руководителя»: лавандовый фон, H2 «Слово руководителя», фото с alt, цитата',
			'rgb(248, 245, 255)' === l.bg && 'Слово руководителя true' === l.h2 && '[Имя Фамилия], основатель агентства' === l.photo[ 0 ] && 'lazy' === l.photo[ 1 ] && l.quote.startsWith( '[Цитата руководителя:' ), JSON.stringify( l ) );
		check( '«Слово руководителя»: имя и должность из карточки сотрудника, одна кнопка «Написать руководителю» — открывает окно (без «Познакомиться с командой» — правка заказчика)',
			'[Имя Фамилия]' === l.name && 'Основатель и генеральный директор' === l.role && JSON.stringify( l.buttons ) === JSON.stringify( [ 'BUTTON Написать руководителю dialog true' ] ), JSON.stringify( l ) );
		// Правка заказчика: подпись и кнопка — строкой под цитатой, кнопка у правого края цитаты;
		// на телефоне кнопка под подписью, справа. figcaption — последний в figure (валидный HTML).
		const leaderLayout = () => page.locator( '.b-leader' ).evaluate( ( el ) => {
			const r = ( s ) => el.querySelector( s ).getBoundingClientRect();
			const [ quote, who, btn, fig, foot ] = [ r( 'blockquote' ), r( '.b-leader__who' ), r( '.b-leader__btn' ), r( '.b-leader__figure' ), r( '.b-leader__foot' ) ];
			return {
				last: el.querySelector( 'figure' ).lastElementChild.tagName,
				right: Math.round( fig.right - btn.right ),
				below: Math.round( foot.top - quote.bottom ),
				sameRow: btn.top < who.bottom && btn.bottom > who.top && Math.abs( ( btn.top + btn.bottom ) / 2 - ( who.top + who.bottom ) / 2 ) < 2,
				whoLeft: Math.round( who.left - quote.left ),
				btnBelowWho: Math.round( btn.top - who.bottom ),
			};
		} );
		const ld = await leaderLayout();
		check( '«Слово руководителя», 1280px: имя и должность слева, кнопка у правого края цитаты, в одной строке; строка ниже цитаты на 36 px',
			'FIGCAPTION' === ld.last && 0 === ld.right && ld.sameRow && 0 === ld.whoLeft && 36 === ld.below, JSON.stringify( ld ) );
		await page.setViewportSize( { width: 390, height: 900 } );
		const lm = await leaderLayout();
		check( '«Слово руководителя», 390px: кнопка под именем и должностью, у правого края блока',
			0 === lm.right && 16 === lm.btnBelowWho && 0 === lm.whoLeft && 36 === lm.below, JSON.stringify( lm ) );
		await page.setViewportSize( { width: 1280, height: 900 } );

		// ===== Окно «Написать руководителю» (правка заказчика) =====
		resetFormLimits();
		const btn = page.locator( '.b-leader__btn' );
		const modal = page.locator( 'dialog.b-leader__modal' );
		await btn.click();
		await page.waitForTimeout( 300 );
		const m = await modal.evaluate( ( d ) => ( {
			open: d.open,
			focus: document.activeElement.name,
			title: d.querySelector( 'h2' ).textContent,
			labelled: d.getAttribute( 'aria-labelledby' ) === d.querySelector( 'h2' ).id,
			to: d.querySelector( '.b-leader__to' ).textContent.replace( /\s+/g, ' ' ).trim(),
			photo: d.querySelector( '.b-leader__to-photo' ).getAttribute( 'alt' ),
			fields: [ ...d.querySelectorAll( 'form .field [name]' ) ].map( ( f ) => f.name ),
			form: d.querySelector( 'form' ).dataset.trustForm,
		} ) );
		check( 'Окно «Написать руководителю»: открывается кнопкой, фокус на «Имени», кому — фото, имя и должность руководителя',
			m.open && 'name' === m.focus && 'Написать руководителю' === m.title && m.labelled && '[Имя Фамилия] Основатель и генеральный директор' === m.to && '' === m.photo && 'leader' === m.form,
			JSON.stringify( m ) );
		check( 'Окно: поля «Имя», «Телефон или email», «Сообщение» и согласие', JSON.stringify( m.fields ) === JSON.stringify( [ 'name', 'contact', 'message', 'consent' ] ), JSON.stringify( m.fields ) );
		await modal.locator( '[type="submit"]' ).click();
		const errs = await modal.locator( '.field__error:not([hidden])' ).allTextContents();
		check( 'Окно: пустая форма — 4 сообщения об ошибке', 4 === errs.length && errs[ 2 ].startsWith( 'Напишите сообщение' ), JSON.stringify( errs ) );
		await modal.locator( '[name="name"]' ).fill( 'Тест Руководителю' );
		await modal.locator( '[name="contact"]' ).fill( 'leader-test@example.com' );
		await modal.locator( '[name="message"]' ).fill( 'Спасибо команде за работу над сайтом — всё сделали в срок.' );
		await modal.locator( '[name="consent"]' ).check();
		await modal.locator( '[type="submit"]' ).click();
		const sent = await modal.locator( '.callback-modal__success' ).waitFor( { state: 'visible', timeout: 20000 } ).then( () => true ).catch( () => false );
		check( 'Окно: после отправки — «Сообщение отправлено»', sent && 'Сообщение отправлено' === ( await modal.locator( '.form-success__title' ).textContent() ) );
		// Правка заказчика: «Сообщение отправлено» справа от значка, текст и «Закрыть» — под ними.
		const sentLayout = () => modal.locator( '.callback-modal__success' ).evaluate( ( el ) => {
			const r = ( s ) => el.querySelector( s ).getBoundingClientRect();
			const [ icon, title, text, close ] = [ r( '.form-success__icon' ), r( '.form-success__title' ), r( '.form-success__text' ), r( 'button' ) ];
			const x = r( '.form-success__icon' ).left;
			return [ title.left - icon.right >= 12, Math.abs( ( title.top + title.bottom ) / 2 - ( icon.top + icon.bottom ) / 2 ) < 2, text.top >= Math.max( icon.bottom, title.bottom ), Math.round( text.left - x ), Math.round( close.left - x ), close.top > text.bottom ];
		} );
		const sentDesktop = await sentLayout();
		await page.setViewportSize( { width: 390, height: 900 } );
		const sentMobile = await sentLayout();
		await page.setViewportSize( { width: 1280, height: 900 } );
		const sentOk = ( v ) => JSON.stringify( v ) === JSON.stringify( [ true, true, true, 0, 0, true ] );
		check( '«Сообщение отправлено» справа от значка, текст и «Закрыть» — под ними (1280 и 390 px)', sentOk( sentDesktop ) && sentOk( sentMobile ), JSON.stringify( [ sentDesktop, sentMobile ] ) );
		const leadId = wp( 'eval', 'echo get_posts( array( "post_type" => "lead", "numberposts" => 1, "fields" => "ids", "meta_key" => "trust_contact", "meta_value" => "leader-test@example.com" ) )[0] ?? 0;' );
		if ( '0' !== leadId ) {
			created.push( leadId );
		}
		const lead = JSON.parse( wp( 'eval', `echo wp_json_encode( array( get_post_meta( ${ leadId }, 'trust_form', true ), get_post_meta( ${ leadId }, 'trust_name', true ), get_post_meta( ${ leadId }, 'trust_fields', true )['message']['value'] ?? '', wp_parse_url( get_post_meta( ${ leadId }, 'trust_page', true ), PHP_URL_PATH ) ), JSON_UNESCAPED_UNICODE );` ) );
		check( 'Сообщение сохранено заявкой формы «Обращение к руководителю»: имя, текст, страница',
			JSON.stringify( lead ) === JSON.stringify( [ 'leader', 'Тест Руководителю', 'Спасибо команде за работу над сайтом — всё сделали в срок.', '/o-kompanii/' ] ), JSON.stringify( lead ) );
		await modal.locator( '.callback-modal__success [data-callback-close]' ).click();
		const closed = await page.evaluate( () => [ document.querySelector( 'dialog.b-leader__modal' ).open, document.activeElement.classList.contains( 'b-leader__btn' ) ] );
		await btn.click();
		const again = await modal.evaluate( ( d ) => [ d.querySelector( 'form' ).closest( '[hidden]' ) === null, d.querySelector( '[name="message"]' ).value ] );
		await page.keyboard.press( 'Escape' );
		check( 'Окно: «Закрыть» возвращает фокус на кнопку, при новом открытии — пустая форма', ! closed[ 0 ] && closed[ 1 ] && again[ 0 ] && '' === again[ 1 ], JSON.stringify( [ closed, again ] ) );

		// ===== Админка: «Заявки → Обращения к руководителю» =====
		const adminContext = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
		const admin = await adminContext.newPage();
		await login( admin );
		await admin.goto( `${ URL }/wp-admin/edit.php?post_type=lead` );
		const sub = await admin.locator( '#menu-posts-lead .wp-submenu li:not(.wp-submenu-head) a' ).evaluateAll( ( links ) => links.map( ( a ) => [ a.childNodes[ 0 ].textContent.trim(), a.querySelector( '.pending-count' )?.textContent || '' ] ) );
		const allRows = await admin.locator( '#the-list tr' ).allInnerTexts();
		const formOptions = await admin.locator( 'select[name="trust_form"] option' ).allTextContents();
		check( 'Админка: подпункт «Обращения к руководителю» сразу после «Все заявки», с числом новых',
			'Все заявки' === sub[ 0 ][ 0 ] && 'Обращения к руководителю' === sub[ 1 ][ 0 ] && Number( sub[ 1 ][ 1 ] ) >= 1, JSON.stringify( sub ) );
		check( 'Админка: в «Все заявки» обращений нет, в фильтре форм — тоже',
			! allRows.some( ( r ) => r.includes( 'Тест Руководителю' ) ) && ! formOptions.includes( 'Обращение к руководителю' ) && formOptions.includes( 'Обратный звонок' ), JSON.stringify( formOptions ) );
		await admin.locator( '#menu-posts-lead .wp-submenu a', { hasText: 'Обращения к руководителю' } ).click();
		await admin.waitForLoadState();
		const list = await admin.evaluate( () => ( {
			h1: document.querySelector( 'h1' ).textContent.trim(),
			current: document.querySelector( '#menu-posts-lead .wp-submenu li.current a' )?.childNodes[ 0 ].textContent.trim(),
			cols: [ ...document.querySelectorAll( '#posts-filter thead th' ) ].map( ( th ) => th.id ),
			row: [ ...document.querySelectorAll( '#the-list tr' ) ].map( ( tr ) => tr.querySelector( '.column-trust_leader_message' )?.textContent.trim() ),
			hidden: document.querySelector( '#posts-filter input[type="hidden"][name="trust_form"]' )?.value,
			views: document.querySelector( '.subsubsub' ).textContent.replace( /\s+/g, ' ' ).trim(),
		} ) );
		check( 'Админка: экран «Обращения к руководителю» — заголовок, подсвечен подпункт, колонка «Сообщение» вместо «Формы»',
			'Обращения к руководителю' === list.h1 && 'Обращения к руководителю' === list.current && list.cols.includes( 'trust_leader_message' ) && ! list.cols.includes( 'trust_form' ) && 'leader' === list.hidden,
			JSON.stringify( list ) );
		check( 'Админка: в списке обращений — отправленное сообщение, число в «Все (N)» — только обращения',
			list.row.includes( 'Спасибо команде за работу над сайтом — всё сделали в срок.' ) && list.views.includes( `(${ list.row.length })` ), JSON.stringify( [ list.row, list.views ] ) );
		await admin.goto( `${ URL }/wp-admin/post.php?post=${ leadId }&action=edit` );
		const single = await admin.locator( '#menu-posts-lead .wp-submenu li.current a' ).evaluate( ( a ) => a.childNodes[ 0 ].textContent.trim() ).catch( () => '' );
		check( 'Админка: на экране обращения подсвечен подпункт «Обращения к руководителю»', 'Обращения к руководителю' === single, single );
		await adminContext.close();

		const c = await page.locator( '.b-certs' ).evaluate( ( el ) => ( {
			lead: el.querySelector( '.section-lead' ).textContent,
			cards: [ ...el.querySelectorAll( '.b-certs__card' ) ].map( ( a ) => [ a.querySelector( '.b-certs__title' ).textContent, a.querySelector( '.b-certs__meta' ).textContent, a.getAttribute( 'target' ), !! a.querySelector( '.b-certs__media--empty svg' ) ] ),
		} ) );
		check( '«Сертификаты и рейтинги»: вводный текст, 6 документов «[ГГГГ] · смотреть документ / диплом», без картинки — значок награды',
			'Каждый документ можно открыть и проверить.' === c.lead && 6 === c.cards.length && '[ГГГГ] · смотреть документ' === c.cards[ 0 ][ 1 ] && '[ГГГГ] · смотреть диплом' === c.cards[ 3 ][ 1 ] && c.cards.every( ( x ) => x[ 3 ] && null === x[ 2 ] ), JSON.stringify( c ) );

		const cl = await page.locator( '.b-clients' ).evaluate( ( el ) => ( {
			list: el.querySelector( 'ul' ).getAttribute( 'aria-label' ),
			tiles: el.querySelectorAll( '.b-clients__tile' ).length,
			name: el.querySelector( '.b-clients__name' ).textContent,
			more: el.querySelector( '.b-clients__more' ).textContent.trim() + ' ' + new URL( el.querySelector( '.b-clients__more' ).href ).pathname,
		} ) );
		check( '«Нам доверяют»: список «Логотипы клиентов», 12 плиток, без логотипа — название, «Смотреть кейсы» → /portfolio/',
			'Логотипы клиентов' === cl.list && 12 === cl.tiles && '[Логотип]' === cl.name && 'Смотреть кейсы /portfolio/' === cl.more, JSON.stringify( cl ) );

		const o = await page.locator( '.b-office' ).evaluate( ( el ) => {
			const items = [ ...el.querySelectorAll( '.b-office__item' ) ];
			const r = ( i ) => items[ i ].getBoundingClientRect();
			return {
				lead: el.querySelector( '.section-lead' ).textContent.replace( /\s+/g, ' ' ).trim(),
				link: new URL( el.querySelector( '.section-lead a' ).href ).pathname,
				captions: items.map( ( f ) => f.querySelector( 'figcaption' ).textContent ),
				big: Math.round( r( 0 ).width ) === Math.round( r( 1 ).width * 2 + 16 ) && Math.round( r( 0 ).height ) === Math.round( r( 1 ).height * 2 + 16 ),
			};
		} );
		check( '«Где мы работаем»: текст со ссылкой на контакты, 5 подписей, первое фото — 2 × 2',
			'Приезжайте познакомиться — адрес и схема проезда на странице контактов.' === o.lead && '/kontakty/' === o.link && 5 === o.captions.length && '[Фото: команда в офисе]' === o.captions[ 0 ] && o.big, JSON.stringify( o ) );

		// ===== Свои данные =====
		const png = wp( 'eval', 'echo get_posts( array( "post_type" => "attachment", "post_status" => "any", "numberposts" => 1, "fields" => "ids", "meta_key" => "_trust_demo_id", "meta_value" => "attachment:leader-photo.jpg" ) )[0] ?? 0;' );
		const pdf = wp( 'eval', `$f = wp_upload_bits( '${ MARK }.pdf', null, '%PDF-1.4' ); echo wp_insert_attachment( array( 'post_title' => '${ MARK } pdf', 'post_mime_type' => 'application/pdf', 'post_status' => 'inherit' ), $f['file'] );` );
		created.push( pdf );
		const own = createPage( 'Блоки', [
			block( 'directions', "array( 'title' => 'Без ссылок', 'items' => array( array( 'icon' => 'bot', 'title' => 'Только текст', 'text' => 'Без страницы' ) ) )" ),
			block( 'principles', "array( 'title' => 'Со значком', 'items' => array( array( 'icon' => 'shield', 'title' => 'Первый' ), array( 'title' => 'Второй' ) ) )" ),
			block( 'history', "array( 'title' => 'С заголовком', 'items' => array( array( 'year' => '2020', 'title' => 'Старт', 'text' => 'Текст' ) ) )" ),
			block( 'leader-word', "array( 'quote' => 'Без сотрудника', 'role' => 'Своя подпись' )" ),
			block( 'certificates', `array( 'title' => 'Документы', 'items' => array( array( 'title' => 'Скан', 'image' => ${ png }, 'file' => ${ png }, 'issuer' => 'Яндекс', 'year' => '2025' ), array( 'title' => 'PDF', 'file' => ${ pdf }, 'link_label' => 'смотреть диплом' ), array( 'title' => 'Без ссылки', 'year' => '2024' ) ) )` ),
			block( 'clients', `array( 'title' => 'Логотипы', 'button' => 0, 'logos' => array( array( 'logo' => ${ png }, 'name' => 'Клиент', 'url' => 'https://example.com/' ) ) )` ),
			block( 'office', `array( 'title' => 'Офис', 'address' => 1, 'photos' => array( array( 'image' => ${ png }, 'caption' => 'Фото' ), array( 'caption' => 'Заглушка' ) ) )` ),
			block( 'directions', "array( 'title' => 'Пусто', 'items' => array() )" ),
		].join( ' . ' ) );
		created.push( own );
		await page.goto( `${ URL }/?page_id=${ own }`, { waitUntil: 'networkidle' } );
		const x = await page.evaluate( () => {
			const q = ( s ) => document.querySelector( s );
			return {
				dirs: document.querySelectorAll( '.b-dirs' ).length + ':' + q( '.b-dirs__card' ).tagName + ':' + document.querySelectorAll( '.b-dirs__more' ).length,
				principles: [ !! q( '.b-principles__icon svg' ), q( '.b-principles__num' ).textContent ],
				history: q( '.b-history h3' ).textContent,
				leader: [ !! q( '.b-leader__photo--empty' ), q( '.b-leader__role' ).textContent, document.querySelectorAll( '.b-leader__btn' ).length, !! q( '.b-leader__name' ), !! q( 'dialog.b-leader__modal' ), !! q( '.b-leader__to' ) ],
				certs: [ ...document.querySelectorAll( '.b-certs__card' ) ].map( ( a ) => [ a.tagName, a.hasAttribute( 'data-trust-lightbox' ), a.getAttribute( 'target' ), a.querySelector( '.b-certs__meta' ).textContent, a.querySelector( 'img' ) ? a.querySelector( 'img' ).alt : '' ] ),
				clients: [ q( '.b-clients__logo' ).alt, q( '.b-clients__tile' ).getAttribute( 'href' ), q( '.b-clients__tile' ).target, !! q( '.b-clients__more' ) ],
				office: [ q( '.b-office__address' ) ? q( '.b-office__address' ).textContent : '', !! q( '.b-office__open[data-trust-lightbox]' ), q( '.b-office__img' ).alt, document.querySelectorAll( '.b-office__placeholder' ).length ],
			};
		} );
		check( '«Чем мы занимаемся» без страницы — карточка без ссылки и «Подробнее»; пустой блок на сайт не выводится', '1:DIV:0' === x.dirs, x.dirs );
		check( '«Как мы работаем»: значок вместо номера, у следующего — номер 02', x.principles[ 0 ] && '02' === x.principles[ 1 ], JSON.stringify( x.principles ) );
		check( '«Как мы росли»: заголовок события — H3', 'Старт' === x.history, x.history );
		check( '«Слово руководителя» без сотрудника: силуэт, своя подпись, без имени; кнопка и окно есть, в окне без строки «кому»', JSON.stringify( x.leader ) === JSON.stringify( [ true, 'Своя подпись', 1, false, true, false ] ), JSON.stringify( x.leader ) );
		check( 'Сертификаты: скан — на весь экран, PDF — в новой вкладке, без документа — карточка без ссылки; «кто выдал · год · подпись»; alt — из медиатеки',
			JSON.stringify( x.certs ) === JSON.stringify( [ [ 'A', true, null, 'Яндекс · 2025 · смотреть документ', '[Имя Фамилия], основатель агентства' ], [ 'A', false, '_blank', 'смотреть диплом', '' ], [ 'DIV', false, null, '2024', '' ] ] ), JSON.stringify( x.certs ) );
		check( 'Логотип: alt — название, ссылка на сайт клиента в новой вкладке; кнопку можно выключить',
			JSON.stringify( x.clients ) === JSON.stringify( [ 'Клиент', 'https://example.com/', '_blank', false ] ), JSON.stringify( x.clients ) );
		check( 'Офис: адрес из настроек (флажок), фото открывается на весь экран, alt из медиатеки, заглушка только у первого',
			'' !== x.office[ 0 ] && x.office[ 1 ] && '[Имя Фамилия], основатель агентства' === x.office[ 2 ] && 0 === x.office[ 3 ], JSON.stringify( x.office ) );
		await page.locator( '.b-certs__card[data-trust-lightbox]' ).click();
		const box = await page.locator( '.lightbox, [data-lightbox], dialog[open]' ).first().isVisible().catch( () => false );
		await page.keyboard.press( 'Escape' );
		check( 'Скан сертификата открывается на весь экран', box );

		// ===== Телефон, ширины, доступность =====
		await page.setViewportSize( { width: 390, height: 900 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const mob = await page.evaluate( () => {
			const card = document.querySelector( '.b-certs__card' );
			const media = card.querySelector( '.b-certs__media' ).getBoundingClientRect();
			const title = card.querySelector( '.b-certs__title' ).getBoundingClientRect();
			const tiles = [ ...document.querySelectorAll( '.b-clients__tile' ) ].slice( 0, 2 ).map( ( t ) => t.getBoundingClientRect() );
			const g = [ ...document.querySelectorAll( '.b-office__item' ) ].slice( 0, 3 ).map( ( f ) => f.getBoundingClientRect() );
			const dIcon = document.querySelector( '.b-dirs__icon' ).getBoundingClientRect();
			const dTitle = document.querySelector( '.b-dirs__title' ).getBoundingClientRect();
			return {
				dirsRow: dTitle.left > dIcon.right && dTitle.top < dIcon.bottom,
				certRow: Math.round( media.width ) === 76 && title.left > media.right,
				logos: Math.round( tiles[ 0 ].height ) + ':' + ( Math.abs( tiles[ 0 ].top - tiles[ 1 ].top ) < 1 ),
				gallery: Math.round( g[ 0 ].height ) + ':' + ( Math.abs( g[ 1 ].top - g[ 2 ].top ) < 1 && g[ 2 ].left > g[ 1 ].left ),
			};
		} );
		check( 'Телефон 390: направления — название справа от значка; сертификаты — строки (картинка 76 px слева), логотипы по 2 в ряд высотой 74 px, галерея в 2 колонки',
			mob.dirsRow && mob.certRow && '74:true' === mob.logos && '252:true' === mob.gallery, JSON.stringify( mob ) );
		const wide = [];
		for ( const width of [ 1440, 1024, 768, 390, 320, 280 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( PAGE, { waitUntil: 'domcontentloaded' } );
			if ( await page.evaluate( () => document.documentElement.scrollWidth > innerWidth ) ) {
				wide.push( width );
			}
		}
		check( 'Ширины 1440–280: без прокрутки вбок', 0 === wide.length, wide.join( ', ' ) );
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
		const axe = await page.evaluate( async () => {
			const r = await window.axe.run( '.page-entry__content', { resultTypes: [ 'violations' ] } );
			return r.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length })` );
		} );
		check( 'axe: нет нарушений critical/serious на странице «О компании»', 0 === axe.length, axe.join( ', ' ) );
		check( 'Консоль браузера на сайте без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Сверка с макетом =====
		const sections = [
			[ 'directions', '.b-dirs', 'section[aria-labelledby="services-title"]' ],
			[ 'principles', '.b-principles', 'section[aria-labelledby="principles-title"]' ],
			[ 'history', '.b-history', 'section[aria-labelledby="history-title"]' ],
			[ 'leader', '.b-leader', 'section[aria-labelledby="ceo-title"]' ],
			[ 'certificates', '.b-certs', 'section[aria-labelledby="cert-title"]' ],
			[ 'clients', '.b-clients', 'section[aria-labelledby="clients-title"]' ],
			[ 'office', '.b-office', 'section[aria-labelledby="office-title"]' ],
		];
		for ( const width of [ 1280, 768, 390 ] ) {
			for ( const [ name, theme, mock ] of sections ) {
				await compare( page, name, width, theme, mock );
			}
		}

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1400, height: 1000 } } );
		const editorErrors = collectConsoleErrors( editor );
		await login( editor );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ own }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		const reg = await editor.evaluate( () => [ 'trust/directions', 'trust/principles', 'trust/history', 'trust/leader-word', 'trust/certificates', 'trust/clients', 'trust/office' ].map( ( name ) => {
			const type = wp.blocks.getBlockType( name );
			return [ type && type.title, type && type.category, !! ( type && type.example ) ].join( ':' );
		} ) );
		check( 'Редактор: семь блоков в категории «Компания», у всех превью',
			JSON.stringify( reg ) === JSON.stringify( [ 'Чем мы занимаемся', 'Как мы работаем', 'Как мы росли', 'Слово руководителя', 'Сертификаты и рейтинги', 'Нам доверяют', 'Где мы работаем' ].map( ( t ) => `${ t }:trust-company:true` ) ), JSON.stringify( reg ) );
		const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-office' ).first().waitFor( { timeout: 30000 } );
		check( 'Редактор: превью блоков на странице', 1 === await canvas.locator( '.b-leader' ).count() && 1 <= await canvas.locator( '.b-certs__card' ).count() );
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
