/**
 * Тест блоков страницы услуги (шаг 4.3): «Что включает услуга» и «Этапы работы»
 * на локальном стенде с демо-контентом (страница /seo/audit/).
 *
 * Запуск: node wp-theme/tests/service-blocks.js
 *
 * «Что включает услуга»:
 * - секция с H2, надзаголовком и вводным текстом; 8 работ H3 с номерами 01–08 и своими иконками;
 * - все работы закрыты; по нажатию раскрываются описание, цена, ссылка «Подробнее об услуге»
 *   и «Что проверяем»; открыты могут быть несколько; повторное нажатие закрывает; клавиатура;
 * - пустые цена, ссылка и «Что проверяем» не выводятся; свой текст ссылки; подпись над списком.
 * «Этапы работы»:
 * - 5 этапов с номерами, линии со стрелками между этапами, итоговый этап «Результат» выделен;
 * - вместо картинки и короткого текста — подробный (с клавиатуры несколько сразу, повторное
 *   нажатие возвращает картинку); стрелки вниз только в одну колонку;
 * - компьютер: подробности при наведении мыши, щелчок мышью их не закрывает; телефон: номер и
 *   стрелка в строку, картинка — узкая полоса, по нажатию короткий текст сменяется подробным;
 * - колонки: 3 этапа — 3, 6 — 3, 7 — 4; своя картинка: alt из блока, «Декоративная», из медиатеки,
 *   предупреждение в редакторе без alt; «Иконка» — список с превью (правки и ТЗ, п. 8.1).
 * Общее: без JavaScript подробности видны; стили и скрипты только на страницах с блоками;
 * совпадение с макетами seo-bloki/04-chto-vklyuchaet-usluga.html (1280, 768, 390 px),
 * seo-stranitsa/index.html (Stages) и seo-bloki/06-etapy-raboty.html (1280, 768 px; на телефоне
 * этапы по правкам заказчика) с одобренными отступами секций (≤ 2 %); ширины 1440–280 px без
 * прокрутки вбок, зоны нажатия ≥ 44 px; axe-core; консоль и журнал ошибок; редактор — категория
 * «Услуги», превью, поля в боковой панели.
 */

const path = require( 'path' );
const { launch, URL, OUT, THEME, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp } = require( './lib' );

const PAGE = `${ URL }/seo/audit/`;
const MARK = 'ТестБлоковУслуги';
const WORKS = [
	'Проверка robots.txt',
	'Анализ карты сайта',
	'Коды ответа сервера',
	'Поиск дублей страниц',
	'Скорость загрузки',
	'Мобильная версия',
	'Анализ индексации',
	'Микроразметка',
];
const STAGES = [ 'Заявка', 'Анализ и предложение', 'Договор', 'Выполнение работ', 'Отчёт' ];

/**
 * Снимки блока темы и макета и их сравнение.
 *
 * @param {import('playwright').Page} page   Страница.
 * @param {Object}                    o      Что сравнивать.
 * @param {string}                    o.name Имя снимков.
 * @param {number}                    o.width Ширина.
 * @param {string}                    o.theme Селектор блока темы.
 * @param {string}                    o.themeClick Селектор кнопок темы для раскрытия.
 * @param {string}                    o.mockup Файл макета.
 * @param {string}                    o.title Заголовок H2 секции в макете.
 * @param {number[]}                  o.open Номера кнопок (с 0), которые раскрыть.
 * @param {boolean}                   o.keyboard В теме раскрывать с клавиатуры (Enter).
 */
async function compare( page, o ) {
	const name = `${ o.name }-${ o.open.length ? 'open' : 'closed' }-${ o.width }`;
	await page.setViewportSize( { width: o.width, height: 900 } );

	const clickAll = async ( selector ) => {
		for ( const i of o.open ) {
			await page.locator( selector ).nth( i ).click();
		}
		await page.mouse.move( 0, 0 );
		await page.waitForTimeout( 300 );
	};

	await page.goto( PAGE, { waitUntil: 'networkidle' } );
	await settle( page );
	if ( o.keyboard ) {
		// Этапы на компьютере мышью раскрываются наведением и держат высоту карточки (правка
		// заказчика); как в макете (по нажатию) они раскрываются с клавиатуры.
		for ( const i of o.open ) {
			await page.locator( o.themeClick ).nth( i ).focus();
			await page.keyboard.press( 'Enter' );
		}
		await page.evaluate( () => document.activeElement.blur() );
		await page.mouse.move( 0, 0 );
		await page.waitForTimeout( 300 );
	} else {
		await clickAll( o.themeClick );
	}
	await isolate( page, o.theme );
	await page.locator( o.theme ).screenshot( { path: path.join( OUT, `${ name }-theme.png` ) } );

	await page.goto( mockupUrl( o.mockup ), { waitUntil: 'networkidle' } );
	await settle( page );
	await page.evaluate( ( title ) => {
		const h2 = [ ...document.querySelectorAll( 'h2' ) ].find( ( h ) => h.textContent.trim() === title );
		h2.closest( 'section' ).id = 'mockup-block';
	}, o.title );
	await clickAll( '#mockup-block ol > li button' );
	await isolate( page, '#mockup-block' );
	await page.locator( '#mockup-block' ).screenshot( { path: path.join( OUT, `${ name }-mockup.png` ) } );

	const diff = comparePng( path.join( OUT, `${ name }-mockup.png` ), path.join( OUT, `${ name }-theme.png` ), path.join( OUT, `${ name }-diff.png` ) );
	check( `Макет ${ o.mockup } ${ o.width }px, ${ o.open.length ? 'раскрыто' : 'всё закрыто' }: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
}

/**
 * Создаёт опубликованную тестовую страницу «Услуга» с блоками.
 *
 * @param {string} title  Заголовок.
 * @param {string} blocks PHP-выражение: разметка блоков.
 * @return {string} ID страницы.
 */
function createPage( title, blocks ) {
	const content = wp( 'eval', `echo ${ blocks };` );
	const id = wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK } ${ title }`, '--post_status=publish', `--post_content=${ content }`, '--porcelain' );
	wp( 'post', 'meta', 'update', id, '_wp_page_template', 'templates/service.php' );
	return id;
}

/** PHP-выражение: этапы для Blocks::serialize( 'stages', … ) — n штук, последний итоговый. */
function stagesPhp( n, extra = '' ) {
	const rows = [];
	for ( let i = 1; i <= n; i++ ) {
		rows.push( `array( 'picture' => 'work', 'title' => 'Этап ${ i }', 'short' => 'Коротко ${ i }', 'detail' => 'Подробно ${ i }'${ i === n ? ", 'result' => 1" : '' }${ 1 === i ? extra : '' } )` );
	}
	return `Trust\\Blocks::serialize( 'stages', array( 'title' => 'Этапы ${ n }', 'stages' => array( ${ rows.join( ', ' ) } ) ) )`;
}

( async () => {
	clearLog();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const created = [];
	let media = [];
	let imagesPage = '';

	try {
		// ===== «Что включает услуга» =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const inc = page.locator( '.b-includes' );
		const incTitle = await inc.getAttribute( 'aria-labelledby' );
		check( 'Состав: секция подписана H2 «Что включает технический аудит», якорь #includes',
			( await page.locator( `h2#${ incTitle }` ).textContent() ).trim() === 'Что включает технический аудит' && ( await inc.getAttribute( 'id' ) ) === 'includes' );
		check( 'Состав: надзаголовок и вводный текст', ( await inc.locator( '.eyebrow' ).textContent() ).trim() === 'Состав услуги' &&
			( await inc.locator( '.section-lead' ).textContent() ).startsWith( 'Нажмите на работу, чтобы узнать подробности' ) );
		const works = ( await inc.locator( 'h3 .b-includes__title' ).allTextContents() ).map( ( t ) => t.trim() );
		check( 'Состав: 8 работ H3 по порядку', JSON.stringify( works ) === JSON.stringify( WORKS ), works.join( ' | ' ) );
		check( 'Состав: номера 01–08', ( await inc.locator( '.b-includes__num' ).allTextContents() ).join( ',' ) === '01,02,03,04,05,06,07,08' );
		const icons = await inc.locator( '.b-includes__icon svg' ).evaluateAll( ( list ) => list.map( ( svg ) => svg.innerHTML ) );
		check( 'Состав: у каждой работы своя иконка', icons.length === 8 && new Set( icons ).size === 8 );
		check( 'Состав: все работы закрыты', ( await inc.locator( '.b-includes__panel:visible' ).count() ) === 0 && ( await inc.locator( '[aria-expanded="true"]' ).count() ) === 0 );

		const work = ( i ) => inc.locator( '.b-includes__item' ).nth( i );
		await work( 0 ).locator( '.b-includes__toggle' ).click();
		const first = await work( 0 ).evaluate( ( el ) => ( {
			expanded: el.querySelector( 'button' ).getAttribute( 'aria-expanded' ),
			visible: ! el.querySelector( '.b-includes__panel' ).hidden,
			desc: el.querySelector( '.b-includes__desc' ).textContent.trim(),
			price: el.querySelector( '.b-includes__price' ).textContent.trim(),
			more: el.querySelector( '.b-includes__more' ).textContent.trim(),
			href: el.querySelector( '.b-includes__more' ).getAttribute( 'href' ),
			label: el.querySelector( '.b-includes__checks-title' ).textContent.trim(),
			checks: [ ...el.querySelectorAll( '.b-includes__checks li' ) ].map( ( li ) => li.textContent.trim() ),
		} ) );
		check( 'Состав: нажатие раскрывает работу (aria-expanded, подробности видны)', first.expanded === 'true' && first.visible, JSON.stringify( first ).slice( 0, 80 ) );
		check( 'Состав: описание, цена «от X XXX ₽», ссылка «Подробнее об услуге»',
			first.desc.startsWith( 'Файл robots.txt подсказывает' ) && first.price === 'от X XXX ₽' && first.more === 'Подробнее об услуге' && first.href === '#robots', JSON.stringify( first ) );
		check( 'Состав: «Что проверяем» — 4 строки', first.label === 'Что проверяем' && first.checks.length === 4 && first.checks[ 0 ] === 'Синтаксис и корректность директив', first.checks.join( ' | ' ) );
		await page.waitForTimeout( 300 );
		const arrow = await work( 0 ).locator( '.b-includes__arrow' ).evaluate( ( el ) => getComputedStyle( el ).backgroundColor + ' ' + getComputedStyle( el.querySelector( 'svg' ) ).transform );
		check( 'Состав: у открытой работы кнопка фиолетовая, стрелка повёрнута', arrow.startsWith( 'rgb(109, 40, 217) matrix(-1' ), arrow );
		await work( 3 ).locator( '.b-includes__toggle' ).click();
		check( 'Состав: открыты несколько работ одновременно', ( await inc.locator( '.b-includes__item.is-open' ).count() ) === 2 );
		await work( 0 ).locator( '.b-includes__toggle' ).click();
		check( 'Состав: повторное нажатие закрывает работу', ! ( await work( 0 ).locator( '.b-includes__panel' ).isVisible() ) && ( await work( 3 ).locator( '.b-includes__panel' ).isVisible() ) );
		await work( 5 ).locator( '.b-includes__toggle' ).focus();
		await page.keyboard.press( 'Enter' );
		const enterOpen = await work( 5 ).locator( '.b-includes__panel' ).isVisible();
		await page.keyboard.press( 'Space' );
		check( 'Состав: Enter раскрывает, пробел закрывает', enterOpen && ! ( await work( 5 ).locator( '.b-includes__panel' ).isVisible() ) );

		// ===== «Этапы работы» =====
		const st = page.locator( '.b-stages' );
		const stTitle = await st.getAttribute( 'aria-labelledby' );
		check( 'Этапы: секция подписана H2 «Этапы работы», надзаголовок, вводный текст',
			( await page.locator( `h2#${ stTitle }` ).textContent() ).trim() === 'Этапы работы' &&
			( await st.locator( '.eyebrow' ).textContent() ).trim() === 'Как мы работаем' &&
			( await st.locator( '.section-lead' ).textContent() ).startsWith( 'Пять шагов от заявки до отчёта' ) );
		const stages = await st.locator( '.b-stages__item' ).evaluateAll( ( list ) => list.map( ( li ) => ( {
			num: li.querySelector( '.b-stages__num' ).textContent.trim(),
			title: li.querySelector( '.b-stages__title' ).textContent.trim(),
			result: li.classList.contains( 'b-stages__item--result' ),
			label: li.querySelector( '.b-stages__label' ) ? li.querySelector( '.b-stages__label' ).textContent.trim() : '',
			line: !! li.querySelector( '.b-stages__line' ) && !! li.querySelector( '.b-stages__arrow' ),
			down: !! li.querySelector( '.b-stages__down' ),
			pic: !! li.querySelector( '.b-stages__pic svg' ),
		} ) ) );
		check( 'Этапы: 5 этапов 01–05 по порядку', JSON.stringify( stages.map( ( s ) => s.num + ' ' + s.title ) ) === JSON.stringify( STAGES.map( ( t, i ) => `0${ i + 1 } ${ t }` ) ), stages.map( ( s ) => s.title ).join( ' | ' ) );
		check( 'Этапы: рисунки из макета у всех этапов', stages.every( ( s ) => s.pic ) );
		check( 'Этапы: линии со стрелками у 1–4, у итогового — подпись «Результат»',
			stages.slice( 0, 4 ).every( ( s ) => s.line && ! s.result ) && stages[ 4 ].result && ! stages[ 4 ].line && stages[ 4 ].label === 'Результат' );
		const resultLook = await st.locator( '.b-stages__item--result .b-stages__card' ).evaluate( ( el ) => getComputedStyle( el ).backgroundColor + ' ' + getComputedStyle( el ).color );
		check( 'Этапы: итоговая карточка фиолетовая (#5B21B6), текст белый', resultLook === 'rgb(91, 33, 182) rgb(255, 255, 255)', resultLook );
		check( 'Этапы: стрелки вниз есть у 1–4, на широком экране скрыты', stages.slice( 0, 4 ).every( ( s ) => s.down ) && ! stages[ 4 ].down && ( await st.locator( '.b-stages__down:visible' ).count() ) === 0 );
		check( 'Этапы: подробности закрыты, видны картинки и короткие тексты', ( await st.locator( '.b-stages__detail:visible' ).count() ) === 0 &&
			( await st.locator( '.b-stages__pic:visible' ).count() ) === 5 && ( await st.locator( '.b-stages__short:visible' ).count() ) === 5 );

		const stage = ( i ) => st.locator( '.b-stages__item' ).nth( i );
		const view = ( i ) => stage( i ).evaluate( ( el ) => {
			const shown = ( sel ) => 'none' !== getComputedStyle( el.querySelector( sel ) ).display;
			return {
				expanded: el.querySelector( '.b-stages__card' ).getAttribute( 'aria-expanded' ),
				pic: shown( '.b-stages__pic' ),
				short: shown( '.b-stages__short' ),
				detail: shown( '.b-stages__detail' ) ? el.querySelector( '.b-stages__detail' ).textContent.trim() : '',
				picHeight: el.querySelector( '.b-stages__pic' ).getBoundingClientRect().height,
				picWidth: el.querySelector( '.b-stages__pic' ).getBoundingClientRect().width,
			};
		} );

		// Компьютер: подробности при наведении мыши (правка заказчика).
		await stage( 1 ).locator( '.b-stages__card' ).scrollIntoViewIfNeeded();
		await stage( 1 ).locator( '.b-stages__card' ).hover();
		await page.waitForTimeout( 300 );
		let v = await view( 1 );
		check( 'Этапы, компьютер: наведение мыши показывает подробный текст вместо картинки и короткого текста',
			v.expanded === 'true' && ! v.pic && ! v.short && v.detail.startsWith( 'Смотрим сайт, нишу и конкурентов' ), JSON.stringify( v ) );
		const plus = await stage( 1 ).locator( '.b-stages__plus' ).evaluate( ( el ) => getComputedStyle( el ).backgroundColor + ' ' + getComputedStyle( el.querySelector( 'svg' ) ).transform );
		check( 'Этапы: у открытого этапа кнопка «+» фиолетовая и повёрнута в «×»', plus.startsWith( 'rgb(109, 40, 217) matrix(0.7' ), plus );
		await stage( 1 ).locator( '.b-stages__card' ).click();
		check( 'Этапы, компьютер: щелчок мышью по открытому наведением этапу его не закрывает', ( await view( 1 ) ).expanded === 'true' );
		const cardBox = await stage( 1 ).locator( '.b-stages__card' ).boundingBox();
		await page.mouse.move( cardBox.x + cardBox.width / 2, cardBox.y + cardBox.height - 3 );
		await page.waitForTimeout( 300 );
		check( 'Этапы, компьютер: у нижнего края карточки этап не мигает (остаётся открытым)', ( await view( 1 ) ).expanded === 'true' );
		await page.mouse.move( 5, 5 );
		await page.waitForTimeout( 300 );
		v = await view( 1 );
		check( 'Этапы, компьютер: мышь ушла — снова картинка и короткий текст', v.expanded === 'false' && v.pic && v.short && ! v.detail, JSON.stringify( v ) );

		await stage( 2 ).locator( '.b-stages__card' ).focus();
		await page.keyboard.press( 'Enter' );
		const stEnter = await stage( 2 ).locator( '.b-stages__detail' ).isVisible();
		await stage( 4 ).locator( '.b-stages__card' ).focus();
		await page.keyboard.press( 'Enter' );
		check( 'Этапы: с клавиатуры открыты несколько этапов одновременно', stEnter && ( await st.locator( '.b-stages__item.is-open' ).count() ) === 2 );
		await page.keyboard.press( 'Space' );
		await stage( 2 ).locator( '.b-stages__card' ).focus();
		await page.keyboard.press( 'Space' );
		check( 'Этапы: Enter раскрывает, пробел закрывает', ( await st.locator( '.b-stages__item.is-open' ).count() ) === 0 );

		// Колонки и стрелки вниз по ширинам.
		for ( const [ w, cols ] of [ [ 1280, 5 ], [ 1100, 3 ], [ 760, 2 ], [ 480, 1 ] ] ) {
			await page.setViewportSize( { width: w, height: 900 } );
			const res = await st.evaluate( ( el ) => {
				const shown = ( sel ) => [ ...el.querySelectorAll( sel ) ].filter( ( d ) => 'none' !== getComputedStyle( d ).display ).length;
				return {
					cols: getComputedStyle( el.querySelector( '.b-stages__list' ) ).gridTemplateColumns.split( ' ' ).length,
					down: shown( '.b-stages__down' ),
					lines: shown( '.b-stages__line' ),
				};
			} );
			check( `Этапы ${ w }px: ${ cols } кол.${ 1 === cols ? ', стрелки вниз вместо линий' : '' }`, res.cols === cols && ( 1 === cols ? res.down === 4 && res.lines === 0 : res.down === 0 && res.lines === 4 ), JSON.stringify( res ) );
		}

		// Телефон (правки заказчика): номер и стрелка в строку, картинка — узкая полоса и видна всегда.
		await page.setViewportSize( { width: 390, height: 900 } );
		await page.reload( { waitUntil: 'networkidle' } );
		const row = await stage( 0 ).evaluate( ( el ) => {
			const num = el.querySelector( '.b-stages__num' ).getBoundingClientRect();
			const down = el.querySelector( '.b-stages__down' ).getBoundingClientRect();
			return { same: Math.abs( ( num.top + num.bottom ) / 2 - ( down.top + down.bottom ) / 2 ) < 2, right: down.left > num.right };
		} );
		check( 'Этапы, телефон: номер и стрелка вниз в одну строку', row.same && row.right, JSON.stringify( row ) );
		v = await view( 1 );
		const ratio = v.picHeight / ( v.picWidth * 10 / 16 );
		check( 'Этапы, телефон: свёрнуто — картинка узкой полосой (15–25 % высоты), название и короткий текст',
			v.expanded === 'false' && v.pic && v.short && ! v.detail && ratio >= 0.15 && ratio <= 0.25, `${ JSON.stringify( v ) } ${ ratio.toFixed( 2 ) }` );
		await stage( 1 ).locator( '.b-stages__card' ).click();
		await page.mouse.move( 0, 0 );
		v = await view( 1 );
		check( 'Этапы, телефон: нажатие — картинка-полоса, название и подробный текст', v.expanded === 'true' && v.pic && ! v.short && v.detail.startsWith( 'Смотрим сайт' ), JSON.stringify( v ) );
		await stage( 1 ).locator( '.b-stages__card' ).click();
		v = await view( 1 );
		check( 'Этапы, телефон: повторное нажатие сворачивает', v.expanded === 'false' && v.short && ! v.detail, JSON.stringify( v ) );
		await page.waitForTimeout( 200 );
		await st.screenshot( { path: path.join( OUT, 'stages-phone-390.png' ) } );
		await page.setViewportSize( { width: 1280, height: 900 } );

		// --- Стили и скрипты только на страницах с блоками ---
		const html = await page.content();
		check( 'На странице с блоками — их стили и скрипты', [ 'service-includes/style.css', 'service-includes/view.js', 'stages/style.css', 'stages/view.js' ].every( ( f ) => html.includes( `blocks/${ f }` ) || html.includes( 'trust-' + f.split( '/' )[ 0 ] + '-style' ) ) );
		await page.goto( `${ URL }/kontakty/`, { waitUntil: 'networkidle' } );
		const other = await page.content();
		check( 'На странице без блоков их стилей и скриптов нет', ! /blocks\/(service-includes|stages)\//.test( other ) && ! /trust-(service-includes|stages)-style/.test( other ) );

		// --- Без JavaScript ---
		const noJs = await browser.newPage( { javaScriptEnabled: false } );
		await noJs.goto( PAGE );
		check( 'Без JavaScript: подробности всех работ и этапов видны',
			( await noJs.locator( '.b-includes__panel:visible' ).count() ) === 8 && ( await noJs.locator( '.b-stages__detail:visible' ).count() ) === 5 );
		await noJs.close();

		// --- Свои значения и пустые поля (тестовые страницы) ---
		const custom = createPage( 'состав', `Trust\\Blocks::serialize( 'service-includes', array(
			'eyebrow' => '', 'lead' => '', 'checks_label' => '', 'more_label' => 'Узнать больше',
			'items' => array(
				array( 'icon' => 'bolt', 'title' => 'Только описание', 'description' => 'Без цены, ссылки и списка.' ),
				array( 'icon' => 'нет-такой', 'title' => 'Со ссылкой', 'description' => 'Ссылка со своим текстом.', 'price' => 'от 5 000 ₽',
					'link' => array( 'title' => '', 'url' => '/kontakty/', 'target' => '_blank' ), 'checks' => array( array( 'text' => 'Строка' ) ) ),
				array( 'title' => 'Без описания — не выводится', 'description' => '' ),
			),
		) )` );
		created.push( custom );
		await page.goto( `${ URL }/?page_id=${ custom }`, { waitUntil: 'networkidle' } );
		const c = await page.locator( '.b-includes' ).evaluate( ( el ) => {
			el.querySelectorAll( '.b-includes__panel' ).forEach( ( p ) => ( p.hidden = false ) );
			const items = [ ...el.querySelectorAll( '.b-includes__item' ) ];
			return {
				count: items.length,
				eyebrow: el.querySelectorAll( '.eyebrow' ).length,
				lead: el.querySelectorAll( '.section-lead' ).length,
				first: [ '.b-includes__row', '.b-includes__checks' ].map( ( s ) => items[ 0 ].querySelectorAll( s ).length ).join( ',' ),
				icon: !! items[ 1 ].querySelector( '.b-includes__icon svg' ),
				more: items[ 1 ].querySelector( '.b-includes__more' ).textContent.trim(),
				target: items[ 1 ].querySelector( '.b-includes__more' ).getAttribute( 'target' ),
				label: items[ 1 ].querySelectorAll( '.b-includes__checks-title' ).length,
			};
		} );
		check( 'Состав: работа без описания не выводится; пустые надзаголовок и вводный текст не выводятся', c.count === 2 && c.eyebrow === 0 && c.lead === 0, JSON.stringify( c ) );
		check( 'Состав: без цены, ссылки и строк — только описание', c.first === '0,0', c.first );
		check( 'Состав: неизвестная иконка заменяется галочкой', c.icon );
		check( 'Состав: текст ссылки по умолчанию из поля блока, новая вкладка', c.more === 'Узнать больше' && c.target === '_blank', JSON.stringify( c ) );
		check( 'Состав: очищенная подпись «Что проверяем» не выводится', c.label === 0 );

		const jpg = path.join( THEME, 'assets', 'img', 'page-types', 'service.jpg' );
		media = [
			wp( 'media', 'import', jpg, '--title=Тестовая картинка этапа', '--alt=Схема этапа', '--porcelain' ),
			wp( 'media', 'import', jpg, '--title=Тестовая картинка без alt', '--porcelain' ),
		];
		const pictures = `array( 'picture' => 'image', 'image' => ${ media[ 0 ] }, 'alt' => 'Своё описание', 'title' => 'Этап 1', 'short' => 'Коротко', 'detail' => 'Подробно' ),
			array( 'picture' => 'image', 'image' => ${ media[ 0 ] }, 'decorative' => 1, 'title' => 'Этап 2', 'short' => 'Коротко', 'detail' => 'Подробно' ),
			array( 'picture' => 'image', 'image' => ${ media[ 1 ] }, 'title' => 'Без описания', 'short' => 'Коротко', 'detail' => 'Подробно' ),
			array( 'picture' => 'image', 'image' => ${ media[ 0 ] }, 'title' => 'Этап 4', 'short' => 'Коротко', 'detail' => 'Подробно' ),
			array( 'picture' => 'none', 'title' => 'Этап 5', 'short' => 'Коротко', 'detail' => 'Подробно', 'result' => 1 )`;
		imagesPage = createPage( 'картинки этапов', `Trust\\Blocks::serialize( 'stages', array( 'title' => 'Картинки', 'stages' => array( ${ pictures } ) ) )` );
		created.push( imagesPage );
		await page.goto( `${ URL }/?page_id=${ imagesPage }`, { waitUntil: 'networkidle' } );
		const alts = await page.locator( '.b-stages__item' ).evaluateAll( ( list ) => list.map( ( li ) => {
			const img = li.querySelector( '.b-stages__pic img' );
			return img ? img.getAttribute( 'alt' ) + ( img.getAttribute( 'srcset' ) ? '+srcset' : '' ) : ( li.querySelector( '.b-stages__pic' ) ? 'pic' : 'нет' );
		} ) );
		check( 'Этапы: alt своей картинки — из поля блока, у декоративной пустой, иначе из медиатеки; srcset; «Без картинки»',
			JSON.stringify( alts ) === JSON.stringify( [ 'Своё описание+srcset', '+srcset', '+srcset', 'Схема этапа+srcset', 'нет' ] ), JSON.stringify( alts ) );
		check( 'Этапы: на сайте предупреждения об alt нет', ( await page.locator( '.trust-block-warning' ).count() ) === 0 );

		for ( const [ n, cols ] of [ [ 3, 3 ], [ 6, 3 ], [ 7, 4 ] ] ) {
			const id = createPage( `этапы ${ n }`, stagesPhp( n ) );
			created.push( id );
			await page.goto( `${ URL }/?page_id=${ id }`, { waitUntil: 'networkidle' } );
			const res = await page.locator( '.b-stages' ).evaluate( ( el ) => ( {
				cols: getComputedStyle( el.querySelector( '.b-stages__list' ) ).gridTemplateColumns.split( ' ' ).length,
				items: el.querySelectorAll( '.b-stages__item' ).length,
				lastLine: !! el.querySelector( '.b-stages__item:last-child .b-stages__line' ),
			} ) );
			check( `Этапы: ${ n } шт. — ${ cols } колонки на широком экране`, res.items === n && res.cols === cols && ! res.lastLine, JSON.stringify( res ) );
		}

		// --- Сверка с макетами ---
		for ( const width of [ 1280, 768, 390 ] ) {
			for ( const open of [ [], [ 0 ] ] ) {
				await compare( page, { name: 'includes', width, theme: '.b-includes', themeClick: '.b-includes__toggle', mockup: 'seo-bloki/04-chto-vklyuchaet-usluga.html', title: 'Что включает технический аудит', open } );
			}
			// На телефоне «Этапы» свёрстаны по правкам заказчика (полоса картинки, стрелка у номера) —
			// с макетом не сравниваются, вид проверен выше (снимок stages-phone-390.png).
			if ( 390 === width ) {
				continue;
			}
			for ( const open of [ [], [ 1, 4 ] ] ) {
				await compare( page, { name: 'stages', width, theme: '.b-stages', themeClick: '.b-stages__card', mockup: 'seo-stranitsa/index.html', title: 'Этапы работы', open, keyboard: true } );
			}
		}
		for ( const width of [ 1280, 768 ] ) {
			await compare( page, { name: 'stages-06', width, theme: '.b-stages', themeClick: '.b-stages__card', mockup: 'seo-bloki/06-etapy-raboty.html', title: 'Этапы работы', open: [] } );
		}

		// --- Ширины ---
		for ( const w of [ 1440, 1024, 768, 600, 390, 320, 280 ] ) {
			await page.setViewportSize( { width: w, height: 800 } );
			await page.goto( PAGE, { waitUntil: 'networkidle' } );
			await page.locator( '.b-includes__toggle' ).first().click();
			const res = await page.evaluate( () => ( {
				scroll: document.documentElement.scrollWidth > innerWidth,
				small: [ ...document.querySelectorAll( '.b-includes a, .b-includes button, .b-stages button' ) ].filter( ( el ) => el.offsetParent && el.getBoundingClientRect().height < 44 ).length,
			} ) );
			check( `Ширина ${ w }px: без прокрутки вбок, зоны нажатия ≥ 44 px`, ! res.scroll && 0 === res.small, JSON.stringify( res ) );
		}

		// --- Доступность ---
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await page.locator( '.b-includes__toggle' ).first().click();
		await page.locator( '.b-stages__card' ).nth( 4 ).focus();
		await page.keyboard.press( 'Enter' );
		await page.waitForTimeout( 300 );
		await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
		const axe = await page.evaluate( async () => {
			const r = await window.axe.run( { include: [ '.b-includes', '.b-stages' ] }, { resultTypes: [ 'violations' ] } );
			return r.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length })` );
		} );
		check( 'axe: нет нарушений critical/serious', axe.length === 0, axe.join( ', ' ) );
		check( 'Консоль браузера на сайте без ошибок', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );

		// --- Редактор ---
		const editor = await browser.newPage( { viewport: { width: 1440, height: 1000 } } );
		const editorErrors = [];
		editor.on( 'pageerror', ( e ) => editorErrors.push( e.message ) );
		await login( editor );
		const id = wp( 'eval', 'echo get_page_by_path( "seo/audit" )->ID;' );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ id }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		await editor.evaluate( () => {
			try {
				wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false );
			} catch ( e ) {}
		} );
		const reg = await editor.evaluate( () => [ 'trust/service-includes', 'trust/stages' ].map( ( name ) => {
			const type = wp.blocks.getBlockType( name );
			const inserter = wp.data.select( 'core/block-editor' ).getInserterItems().some( ( i ) => name === i.name );
			return { name, title: type && type.title, category: type && type.category, example: !! ( type && type.example ), inserter };
		} ) );
		check( 'Редактор: «Что включает услуга» и «Этапы работы» в категории «Услуги», с превью, доступны для вставки',
			reg.every( ( r ) => 'trust-services' === r.category && r.example && r.inserter ) && reg[ 0 ].title === 'Что включает услуга' && reg[ 1 ].title === 'Этапы работы', JSON.stringify( reg ) );

		const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-stages' ).first().waitFor( { timeout: 30000 } );
		check( 'Редактор: в превью работы раскрыты, у этапов видны картинка, короткий и подробный текст',
			( await canvas.locator( '.b-includes__item.is-open' ).count() ) === 8 && ( await canvas.locator( '.b-stages__pic svg' ).count() ) === 5 &&
			( await canvas.locator( '.b-stages__detail:visible' ).count() ) === 5 );

		for ( const [ name, labels ] of [
			[ 'trust/service-includes', [ 'Подпись над списком', 'Текст ссылки на страницу работы', 'Работы' ] ],
			[ 'trust/stages', [ 'Надзаголовок', 'Заголовок (H2)', 'Этапы' ] ],
		] ) {
			await editor.evaluate( ( n ) => {
				const block = wp.data.select( 'core/block-editor' ).getBlocks().find( ( b ) => n === b.name );
				wp.data.dispatch( 'core/block-editor' ).selectBlock( block.clientId );
			}, name );
			await editor.waitForTimeout( 1500 );
			const sidebar = await editor.locator( '.interface-complementary-area' ).innerText().catch( () => '' );
			check( `Редактор: поля блока ${ name } в боковой панели`, labels.every( ( label ) => sidebar.includes( label ) ), sidebar.slice( 0, 120 ).replace( /\s+/g, ' ' ) );
			if ( 'trust/service-includes' === name ) {
				const icons = await editor.evaluate( () => [ ...document.querySelectorAll( '.interface-complementary-area .trust-icon-select .select2-selection__rendered' ) ].map( ( el ) => ( el.querySelector( 'svg' ) ? 'svg ' : '' ) + el.textContent.trim() ) );
				check( 'Редактор: «Иконка» — список с превью значков (8 работ, у выбранного значения — иконка)', icons.length === 8 && icons.every( ( t ) => t.startsWith( 'svg ' ) ) && icons[ 0 ] === 'svg Робот (robots.txt)', JSON.stringify( icons.slice( 0, 2 ) ) );
				await editor.locator( '.interface-complementary-area .trust-icon-select .select2-selection' ).first().click();
				const options = await editor.evaluate( () => [ ...document.querySelectorAll( '.select2-results__option' ) ].map( ( o ) => ( o.querySelector( 'svg' ) ? 'svg ' : '' ) + o.textContent.trim() ) );
				check( 'Редактор: в раскрытом списке у каждой иконки превью', options.length === 13 && options.every( ( t ) => t.startsWith( 'svg ' ) ), JSON.stringify( options.slice( 0, 3 ) ) );
				await editor.screenshot( { path: path.join( OUT, 'service-blocks-icons.png' ) } );
				await editor.keyboard.press( 'Escape' );
			}
		}
		await editor.screenshot( { path: path.join( OUT, 'service-blocks-editor.png' ) } );

		// Своя картинка этапа без описания — предупреждение в превью редактора.
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ imagesPage }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		const warnCanvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await warnCanvas.locator( '.b-stages' ).first().waitFor( { timeout: 30000 } );
		const warnings = ( await warnCanvas.locator( '.trust-block-warning' ).allTextContents() ).map( ( t ) => t.trim() );
		check( 'Редактор: предупреждение у картинки этапа без alt (только у неё)', warnings.length === 1 && warnings[ 0 ].startsWith( 'У картинки этапа «Без описания» нет описания (alt)' ), warnings.join( ' | ' ) );
		check( 'Редактор без ошибок JavaScript', editorErrors.length === 0, editorErrors.slice( 0, 3 ).join( ' | ' ) );
	} finally {
		created.forEach( ( id ) => wp( 'post', 'delete', id, '--force' ) );
		if ( media.length ) {
			media.forEach( ( m ) => wp( 'post', 'delete', m, '--force' ) );
		}
	}

	await browser.close();
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
