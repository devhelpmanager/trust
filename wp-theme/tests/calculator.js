/**
 * Тест блока «Калькулятор стоимости» (шаг 4.8, ТЗ п. 8.3.1) на стенде с демо-контентом
 * (страница /seo/audit/ — калькулятор с вариантами из макета).
 *
 * Запуск: node wp-theme/tests/calculator.js
 *
 * Разметка: вкладки (role=tab, aria-selected, aria-controls), шаги (role=tabpanel), варианты —
 * настоящие флажки и радиокнопки с подписями, поля шага «О сайте».
 * Поведение: «Далее» / «Назад», шкала «Шаг N из 7 · название», галочки у пройденных вкладок,
 * клавиши ← → Home End на вкладках, Enter в поле — следующий шаг, AI-системы только при
 * «Адаптируем», экран результата, «Изменить параметры», кнопка результата — окно звонка.
 * Формула ТЗ на своей странице с ценами: надбавки, запросы × цена, наибольший коэффициент
 * регионов, свой регион, округление вверх, минимальная цена, текст цены с {price}.
 * Форма контактов (по флажку): ошибки, заявка с ответами и ценой.
 * Общее: кнопка первого экрана «Рассчитать стоимость» ведёт к калькулятору, вид без JavaScript,
 * телефон (шкала над кнопками, вкладки прокручиваются к текущей), совпадение с макетом
 * seo-bloki/13-kalkulyator.html (шаги 1, 3 и 6, экран результата), axe-core, редактор.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp, resetFormLimits, formToken } = require( './lib' );

const PAGE = `${ URL }/seo/audit/`;
const MARK = 'ТестКалькулятора';
const TABS = [ 'Регион', 'Запросы', 'Контент', 'Внешняя оптимизация', 'Репутация', 'GEO', 'О сайте' ];

/** Пробелы любого вида — обычные (в цене — неразрывные из Intl). */
const plain = ( text ) => String( text ).replace( /[\s  ]+/g, ' ' ).trim();

/** Заявки по имени: форма, поля (подпись => значение). */
function leadsBy( name ) {
	return JSON.parse( wp( 'eval', `echo wp_json_encode( array_map( static fn( $p ) => array( 'id' => $p->ID, 'form' => get_post_meta( $p->ID, 'trust_form', true ), 'fields' => array_column( (array) get_post_meta( $p->ID, 'trust_fields', true ), 'value', 'label' ) ), get_posts( array( 'post_type' => 'lead', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => 'trust_name', 'meta_value' => '${ name }' ) ) ) );` ) );
}

/** Просчёты стоимости новее ID: цена, регион, страница, поля (подпись => значение). */
function estimatesAfter( id ) {
	return JSON.parse( wp( 'eval', `echo wp_json_encode( array_map( static fn( $p ) => array( 'id' => $p->ID, 'title' => $p->post_title, 'price' => get_post_meta( $p->ID, 'trust_price', true ), 'region' => get_post_meta( $p->ID, 'trust_region', true ), 'page' => get_post_meta( $p->ID, 'trust_page_title', true ), 'source' => get_post_meta( $p->ID, 'trust_source', true ), 'fields' => array_column( (array) get_post_meta( $p->ID, 'trust_fields', true ), 'value', 'label' ) ), array_values( array_filter( get_posts( array( 'post_type' => 'estimate', 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) ), static fn( $p ) => $p->ID > ${ Number( id ) } ) ) ) );` ) )
		.map( ( e ) => ( { ...e, title: plain( e.title ), price: plain( e.price ), region: plain( e.region ), fields: Object.fromEntries( Object.entries( e.fields ).map( ( [ k, v ] ) => [ k, plain( v ) ] ) ) } ) );
}

/** Ждёт, пока просчётов новее ID станет count (сохранение идёт в фоне, ≥ 3 с от первого касания). */
async function waitEstimates( page, id, count ) {
	for ( let i = 0; i < 40; i++ ) {
		const list = estimatesAfter( id );
		if ( list.length >= count ) {
			return list;
		}
		await page.waitForTimeout( 300 );
	}
	return estimatesAfter( id );
}

/**
 * Снимки блока темы и макета и их сравнение.
 *
 * @param {import('playwright').Page} page Страница.
 * @param {Object}                    o    name, width, url, before (в теме), mockBefore (в макете).
 */
async function compare( page, o ) {
	const name = `${ o.name }-${ o.width }`;
	await page.setViewportSize( { width: o.width, height: 900 } );
	await page.goto( o.url || PAGE, { waitUntil: 'networkidle' } );
	await settle( page );
	if ( o.before ) {
		await o.before( page );
	}
	await page.mouse.move( 0, 0 );
	await isolate( page, '.b-calc' );
	await page.waitForTimeout( 400 );
	await page.locator( '.b-calc' ).screenshot( { path: path.join( OUT, `${ name }-theme.png` ) } );

	await page.goto( mockupUrl( 'seo-bloki/13-kalkulyator.html' ), { waitUntil: 'networkidle' } );
	await settle( page );
	if ( o.mockBefore ) {
		await o.mockBefore( page );
		await settle( page );
	}
	await page.mouse.move( 0, 0 );
	await isolate( page, 'section' );
	await page.waitForTimeout( 400 );
	await page.locator( 'section' ).first().screenshot( { path: path.join( OUT, `${ name }-mockup.png` ) } );
	const diff = comparePng( path.join( OUT, `${ name }-mockup.png` ), path.join( OUT, `${ name }-theme.png` ), path.join( OUT, `${ name }-diff.png` ) );
	check( `Макет 13-kalkulyator ${ o.label || '' } ${ o.width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
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
	const lastEstimate = Number( wp( 'eval', "global $wpdb; echo (int) $wpdb->get_var( \"SELECT MAX(ID) FROM {$wpdb->posts}\" );" ) ) || 0;

	const calc = page.locator( '.b-calc' ).first();
	const state = () => calc.evaluate( ( el ) => ( {
		tabs: [ ...el.querySelectorAll( '[role=tab]' ) ].map( ( t ) => ( t.classList.contains( 'is-active' ) ? 'A' : '' ) + ( t.classList.contains( 'is-done' ) ? 'D' : '' ) + ( 'true' === t.getAttribute( 'aria-selected' ) ? 'S' : '' ) || '-' ).join( ' ' ),
		panel: el.querySelector( '[data-calc-body]' ).hidden ? -1 : [ ...el.querySelectorAll( '[role=tabpanel]' ) ].findIndex( ( p ) => ! p.hidden ),
		nav: ! el.querySelector( '[data-calc-nav]' ).hidden,
		label: el.querySelector( '[data-calc-label]' ).textContent.trim(),
		bar: el.querySelector( '[data-calc-bar]' ).style.width,
		back: el.querySelector( '[data-calc-prev]' ).disabled,
		next: ! el.querySelector( '[data-calc-next]' ).hidden,
		go: ! el.querySelector( '[data-calc-go]' ).hidden,
		result: ! el.querySelector( '[data-calc-result]' ).hidden,
	} ) );
	const tab = ( i ) => calc.locator( '[role=tab]' ).nth( i );
	const price = async () => plain( await calc.locator( '.b-calc__price' ).innerText() );

	try {
		// ===== Разметка (демо на /seo/audit/) =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const mk = await calc.evaluate( ( el ) => ( {
			id: el.id,
			labelled: el.getAttribute( 'aria-labelledby' ) === el.querySelector( 'h2' ).id,
			eyebrow: el.querySelector( '.eyebrow' ).textContent.trim(),
			h2: el.querySelector( 'h2' ).textContent.trim(),
			bg: getComputedStyle( el ).backgroundColor,
			tablist: el.querySelector( '[role=tablist]' ).getAttribute( 'aria-label' ),
			tabs: [ ...el.querySelectorAll( '[role=tab]' ) ].map( ( t ) => t.querySelector( '.b-calc__tab-name' ).textContent.trim() ),
			links: [ ...el.querySelectorAll( '[role=tab]' ) ].every( ( t ) => {
				const panel = document.getElementById( t.getAttribute( 'aria-controls' ) );
				return panel && 'tabpanel' === panel.getAttribute( 'role' ) && panel.getAttribute( 'aria-labelledby' ) === t.id;
			} ),
			titles: [ ...el.querySelectorAll( '.b-calc__step-title' ) ].map( ( h ) => h.textContent.trim() ),
			regions: [ ...el.querySelectorAll( 'input[name="step_region[]"]' ) ].map( ( i ) => ( i.checked ? '+' : '' ) + i.value ),
			other: !! el.querySelector( 'label[for] + input[name="step_region_other"]' ),
			content: [ ...el.querySelectorAll( 'input[name="step_content"]' ) ].map( ( i ) => ( i.checked ? '+' : '' ) + i.value ),
			external: [ ...el.querySelectorAll( 'input[name="step_external"]' ) ].map( ( i ) => ( i.checked ? '+' : '' ) + i.value ).join( '|' ),
			reputation: [ ...el.querySelectorAll( 'input[name="step_reputation"]' ) ].map( ( i ) => ( i.checked ? '+' : '' ) + i.value ).join( '|' ),
			geo: [ ...el.querySelectorAll( 'input[name="step_geo"]' ) ].map( ( i ) => ( i.checked ? '+' : '' ) + i.dataset.title ),
			ai: [ ...el.querySelectorAll( 'input[name="step_ai[]"]' ) ].map( ( i ) => ( i.checked ? '+' : '' ) + i.value ),
			about: [ ...el.querySelectorAll( '.b-calc__about .field__label' ) ].map( ( l ) => l.textContent.trim() + ( document.getElementById( l.htmlFor ) ? '' : '!' ) ),
			ages: [ ...el.querySelectorAll( 'input[name="step_age"]' ) ].map( ( i ) => i.value ),
			traffic: [ ...el.querySelectorAll( 'input[name="step_traffic"]' ) ].map( ( i ) => i.value ),
			form: !! el.querySelector( 'form' ),
			cta: el.querySelector( '.b-calc__cta' ).getAttribute( 'href' ),
		} ) );
		check( 'Калькулятор: якорь #calculator, надзаголовок «Калькулятор», H2, лавандовый фон',
			'calculator' === mk.id && mk.labelled && 'Калькулятор' === mk.eyebrow && 'Рассчитайте стоимость продвижения' === mk.h2 && 'rgb(248, 245, 255)' === mk.bg, JSON.stringify( mk ).slice( 0, 200 ) );
		check( 'Калькулятор: 7 вкладок «Этапы расчёта», каждая связана со своим шагом (aria-controls / aria-labelledby)',
			'Этапы расчёта' === mk.tablist && JSON.stringify( TABS ) === JSON.stringify( mk.tabs ) && mk.links, JSON.stringify( mk.tabs ) );
		check( 'Калькулятор: заголовки шагов из макета',
			'Регион продвижения|Ключевые запросы|Кто будет создавать контент|Внешняя оптимизация|Работа с репутацией|GEO-продвижение|О сайте' === mk.titles.join( '|' ), mk.titles.join( '|' ) );
		check( 'Шаг «Регион»: 8 регионов-флажков (выбрана Москва), поле «Другой регион» с подписью',
			'+Москва,Санкт-Петербург,Новосибирск,Екатеринбург,Казань,Нижний Новгород,Краснодар,Вся Россия' === mk.regions.join() && mk.other, mk.regions.join() );
		check( 'Шаги «Контент», «Внешняя оптимизация», «Репутация»: варианты из макета, выбраны как в макете',
			'Ваш специалист,+Специалист с биржи,Наш специалист' === mk.content.join() &&
			'+Минимально необходимые работы|Весь комплекс работ|Без внешней оптимизации' === mk.external &&
			'Не следим за репутацией|+Только отзывы|Все упоминания бренда|Репутация в соцсетях' === mk.reputation, JSON.stringify( [ mk.content, mk.external, mk.reputation ] ) );
		check( 'Шаг GEO: «Не адаптируем / Адаптируем» (выбрано «Адаптируем»), 4 AI-системы отмечены',
			'Не адаптируем под AI-системы,+Адаптируем под AI-системы' === mk.geo.join() && '+Claude,+Gemini,+ChatGPT,+Алиса' === mk.ai.join(), JSON.stringify( [ mk.geo, mk.ai ] ) );
		check( 'Шаг «О сайте»: 4 поля с подписями, возраст сайта (4 варианта), трафик (Да / Нет) — без готового ответа',
			'Адрес сайта,Тематика сайта,Количество продвигаемых страниц,Цель по трафику или заказам' === mk.about.join() && 4 === mk.ages.length && 'Да,Нет' === mk.traffic.join() &&
			0 === await calc.locator( 'input[name="step_age"]:checked, input[name="step_traffic"]:checked' ).count(), JSON.stringify( [ mk.about, mk.ages ] ) );
		check( 'По умолчанию без формы контактов (ТЗ), кнопка результата — окно «Заказать обратный звонок»', ! mk.form && mk.cta.endsWith( '#callback-modal' ), mk.cta );

		// ===== Кнопка первого экрана =====
		await page.locator( '.b-hero-service a[href$="#calculator"]' ).click();
		await page.waitForTimeout( 1200 );
		const top = await calc.evaluate( ( el ) => Math.round( el.getBoundingClientRect().top ) );
		check( 'Кнопка «Рассчитать стоимость» первого экрана ведёт к калькулятору (начало не под шапкой)', top >= 50 && top <= 90, String( top ) );

		// ===== Шаги =====
		let st = await state();
		check( 'Сначала шаг 1: вкладка выбрана, «Назад» неактивна, шкала «Шаг 1 из 7 · Регион» (14 %)',
			'AS - - - - - -' === st.tabs && 0 === st.panel && st.back && st.next && ! st.go && 'Шаг 1 из 7 · Регион' === st.label && '14%' === st.bar, JSON.stringify( st ) );
		const chip = await calc.locator( '.b-calc__chip' ).first().evaluate( ( c ) => [ getComputedStyle( c ).backgroundColor, getComputedStyle( c.querySelector( '.b-calc__chip-check' ) ).display, getComputedStyle( c.nextElementSibling.querySelector( '.b-calc__chip-plus' ) ).display ] );
		check( 'Регионы: выбранный — фиолетовый с галочкой, остальные — с плюсом', 'rgb(109, 40, 217)' === chip[ 0 ] && 'block' === chip[ 1 ] && 'block' === chip[ 2 ], JSON.stringify( chip ) );
		await calc.locator( '[data-calc-next]' ).click();
		await calc.locator( '[data-calc-next]' ).click();
		st = await state();
		const tick = await tab( 0 ).evaluate( ( t ) => getComputedStyle( t.querySelector( '.b-calc__tick' ) ).display );
		check( '«Далее» ×2 — шаг 3: у пройденных вкладок галочки, шкала «Шаг 3 из 7 · Контент» (43 %)',
			'D D AS - - - -' === st.tabs && 2 === st.panel && ! st.back && 'Шаг 3 из 7 · Контент' === st.label && '43%' === st.bar && 'block' === tick, JSON.stringify( st ) );
		await calc.locator( '[data-calc-prev]' ).click();
		st = await state();
		check( '«Назад» — шаг 2', 1 === st.panel && 'D AS - - - - -' === st.tabs, JSON.stringify( st ) );
		await tab( 6 ).click();
		st = await state();
		check( 'Вкладка 7 — последний шаг: вместо «Далее» — «Рассчитать стоимость», шкала 100 %',
			6 === st.panel && ! st.next && st.go && '100%' === st.bar && 'Шаг 7 из 7 · О сайте' === st.label, JSON.stringify( st ) );

		// Клавиатура на вкладках (WAI-ARIA «Вкладки»).
		await tab( 6 ).focus();
		await page.keyboard.press( 'ArrowRight' );
		const k1 = [ ( await state() ).panel, await page.evaluate( () => document.activeElement.textContent.trim() ) ];
		await page.keyboard.press( 'End' );
		const k2 = ( await state() ).panel;
		await page.keyboard.press( 'ArrowLeft' );
		const k3 = ( await state() ).panel;
		await page.keyboard.press( 'Home' );
		const k4 = [ ( await state() ).panel, await calc.evaluate( ( el ) => [ ...el.querySelectorAll( '[role=tab]' ) ].map( ( t ) => t.tabIndex ).join( '' ) ) ];
		check( 'Вкладки с клавиатуры: → с последней — на первую, End, ←, Home; Tab попадает только на выбранную',
			0 === k1[ 0 ] && k1[ 1 ].endsWith( 'Регион' ) && 6 === k2 && 5 === k3 && 0 === k4[ 0 ] && '0-1-1-1-1-1-1' === k4[ 1 ], JSON.stringify( [ k1, k2, k3, k4 ] ) );
		await calc.locator( 'input[name="step_region_other"]' ).focus();
		await page.keyboard.press( 'Enter' );
		check( 'Enter в поле «Другой регион» — следующий шаг', 1 === ( await state() ).panel );

		// GEO: AI-системы только при «Адаптируем».
		await tab( 5 ).click();
		const aiOn = await calc.locator( '[data-calc-ai]' ).isVisible();
		await calc.locator( 'label', { hasText: 'Не адаптируем под AI-системы' } ).click();
		const aiOff = await calc.locator( '[data-calc-ai]' ).isVisible();
		await calc.locator( 'label', { hasText: 'Адаптируем под AI-системы' } ).last().click();
		check( 'GEO: список AI-систем виден только при «Адаптируем»', aiOn && ! aiOff && await calc.locator( '[data-calc-ai]' ).isVisible() );

		// ===== Результат (цены демо: база 20 000, надбавки из блока, Москва × 1,5) =====
		await tab( 6 ).click();
		await calc.locator( '[data-calc-go]' ).click();
		st = await state();
		const res = await calc.evaluate( ( el ) => [ document.activeElement === el.querySelector( '[data-calc-result]' ), el.querySelector( '.b-calc__result-eyebrow' ).textContent.trim(), el.querySelector( '.b-calc__note' ).textContent.trim() ] );
		check( 'Экран результата: у всех вкладок галочки, шагов и шкалы нет, фокус на результате',
			st.result && -1 === st.panel && ! st.nav && 'D D D D D D D' === st.tabs && res[ 0 ] && 'Предварительный расчёт' === res[ 1 ], JSON.stringify( [ st, res ] ) );
		check( 'Цена по умолчанию в долларах: (300 + 60 + 150 + 60 + 150 + 4 × 40) × 1,5 = 1 320 — «от 1 320 $ / месяц»', 'от 1 320 $ / месяц' === await price(), await price() );
		let est = await waitEstimates( page, lastEstimate, 1 );
		check( 'Просчёт сохранён: «Заявки → Просчёт стоимости работ» — цена, регион, страница, все ответы',
			1 === est.length && 'от 1 320 $ / месяц' === est[ 0 ].price && 'Москва' === est[ 0 ].region && 'от 1 320 $ / месяц — Москва' === est[ 0 ].title &&
			'Технический аудит сайта' === est[ 0 ].page && 'Специалист с биржи' === est[ 0 ].fields[ 'Контент' ] && 'Адаптируем под AI-системы: Claude, Gemini, ChatGPT, Алиса' === est[ 0 ].fields.GEO &&
			! ( 'Имя' in est[ 0 ].fields ), JSON.stringify( est ) );
		await calc.locator( '[data-calc-edit]' ).click();
		st = await state();
		check( '«Изменить параметры» — назад к шагу 7', 6 === st.panel && ! st.result, JSON.stringify( st ) );
		await calc.locator( '[data-calc-go]' ).click();
		await tab( 2 ).click();
		check( 'С экрана результата вкладка открывает свой шаг', 2 === ( await state() ).panel );
		await tab( 6 ).click();
		await calc.locator( '[data-calc-go]' ).click();
		await page.waitForTimeout( 1000 );
		est = estimatesAfter( lastEstimate );
		check( 'Тот же расчёт ещё раз («Изменить параметры» → «Рассчитать») — второй записи нет', 1 === est.length, String( est.length ) );
		await calc.locator( '.b-calc__cta' ).click();
		await page.locator( '#callback-modal[open]' ).waitFor( { timeout: 5000 } );
		check( 'Кнопка «Получить точный расчёт» открывает окно «Заказать обратный звонок»', true );
		await page.keyboard.press( 'Escape' );

		// ===== Формула на своей странице =====
		const custom = wp( 'eval', `echo Trust\\Blocks::serialize( 'calculator', array( 'title' => 'Калькулятор теста', 'base_price' => 10000, 'min_price' => 15000, 'round_step' => 500, 'query_price' => 250, 'other_coef' => 1.6, 'result_template' => 'Итого {price} $ / в месяц', 'contact_form' => 1, 'regions' => array( array( 'region' => 'Альфа', 'coef' => 1.2, 'selected' => 1 ), array( 'region' => 'Бета', 'coef' => 2, 'selected' => 0 ) ), 'content' => array( array( 'variant' => 'К1', 'about' => '', 'extra' => 0, 'selected' => 0 ), array( 'variant' => 'К2', 'about' => 'Второй', 'extra' => 3000, 'selected' => 1 ) ), 'external' => array( array( 'variant' => 'В1', 'about' => '', 'extra' => 2000, 'selected' => 0 ) ), 'reputation' => array( array( 'variant' => 'Р1', 'about' => '', 'extra' => 0, 'selected' => 0 ), array( 'variant' => 'Р2', 'about' => '', 'extra' => 1500, 'selected' => 0 ) ), 'geo_default' => 'off', 'geo_on_price' => 2000, 'ai' => array( array( 'system' => 'S1', 'extra' => 500 ), array( 'system' => 'S2', 'extra' => 700 ) ) ) );` );
		const pageId = wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK }`, '--post_status=publish', `--post_content=${ custom }`, '--porcelain' );
		created.push( pageId );
		wp( 'post', 'meta', 'update', pageId, '_wp_page_template', 'templates/service.php' );
		await page.goto( `${ URL }/?page_id=${ pageId }`, { waitUntil: 'networkidle' } );
		const run = async () => {
			await tab( 6 ).click();
			await calc.locator( '[data-calc-go]' ).click();
			return price();
		};
		const setChip = async ( name, on ) => {
			await tab( 0 ).click();
			const box = calc.locator( '.b-calc__chip', { hasText: name } ).locator( 'input' );
			if ( on !== await box.isChecked() ) {
				await calc.locator( '.b-calc__chip', { hasText: name } ).click();
			}
		};
		const p1 = await run();
		check( 'Формула: база 10 000 + К2 3 000 + В1 2 000 (выбран первый) + Р1 0, GEO выкл. = 15 000 × 1,2 = 18 000; свой текст цены', 'Итого 18 000 $ / в месяц' === p1, p1 );
		await setChip( 'Бета', true );
		await tab( 1 ).click();
		await calc.locator( 'textarea[name="step_queries"]' ).fill( 'запрос а\nзапрос б\n\nзапрос в' );
		await tab( 5 ).click();
		await calc.locator( 'label', { hasText: 'Адаптируем под AI-системы' } ).last().click();
		await calc.locator( '.b-calc__check', { hasText: 'S2' } ).click();
		const p2 = await run();
		check( 'Формула: + 3 запроса × 250 + GEO 2 000 + S1 500 (S2 снята) = 18 250; регионы Альфа 1,2 и Бета 2 — × 2 = 36 500', 'Итого 36 500 $ / в месяц' === p2, p2 );
		await setChip( 'Альфа', false );
		await setChip( 'Бета', false );
		await calc.locator( 'input[name="step_region_other"]' ).fill( 'Тверь' );
		const p3 = await run();
		check( 'Формула: только свой регион — коэффициент «другого региона» 1,6: 18 250 × 1,6 = 29 200 → вверх до 500 = 29 500', 'Итого 29 500 $ / в месяц' === p3, p3 );
		await tab( 0 ).click();
		await calc.locator( 'input[name="step_region_other"]' ).fill( '' );
		await tab( 1 ).click();
		await calc.locator( 'textarea[name="step_queries"]' ).fill( '' );
		await tab( 2 ).click();
		await calc.locator( 'label', { hasText: 'К1' } ).click();
		await tab( 5 ).click();
		await calc.locator( 'label', { hasText: 'Не адаптируем под AI-системы' } ).click();
		const p4 = await run();
		check( 'Формула: регион не выбран — коэффициент 1; 12 000 меньше минимальной цены — 15 000', 'Итого 15 000 $ / в месяц' === p4, p4 );

		// ===== Форма контактов и заявка =====
		await tab( 0 ).click();
		await calc.locator( 'input[name="step_region_other"]' ).fill( 'Тверь' );
		await setChip( 'Альфа', true );
		await tab( 6 ).click();
		await calc.locator( 'input[name="step_url"]' ).fill( 'https://shop.example.ru' );
		await calc.locator( 'input[name="step_topic"]' ).fill( 'Интернет-магазин' );
		await calc.locator( 'input[name="step_pages"]' ).fill( '40' );
		await calc.locator( 'input[name="step_goal"]' ).fill( '100 заявок' );
		await calc.locator( '.b-calc__pill', { hasText: 'До 1 года' } ).click();
		await calc.locator( '.b-calc__pill', { hasText: 'Нет' } ).click();
		const p5 = await run();
		check( 'Формула: Альфа 1,2 и свой регион 1,6 — наибольший: 12 000 × 1,6 = 19 200 → 19 500', 'Итого 19 500 $ / в месяц' === p5, p5 );
		const form = calc.locator( 'form[data-trust-form="calculator"]' );
		check( 'Форма контактов на экране результата: подпись, имя, телефон, галочка согласия',
			await form.isVisible() && 2 === await form.locator( '.field__input' ).count() && 1 === await form.locator( 'input[name=consent][required]' ).count() );
		await form.locator( '[type=submit]' ).click();
		const errs = await form.locator( '.field__error:not([hidden])' ).allTextContents();
		check( 'Форма контактов: пустая — ошибки у имени, телефона и согласия', 3 === errs.length, errs.join( ' | ' ) );
		await form.locator( '[name=name]' ).fill( 'Тест Калькулятор' );
		await form.locator( '[name=phone]' ).fill( '+7 900 123-45-67' );
		await form.locator( '[name=consent]' ).check();
		await form.locator( '[type=submit]' ).click();
		await calc.locator( '.form-sent' ).waitFor( { state: 'visible', timeout: 15000 } );
		const leads = leadsBy( 'Тест Калькулятор' );
		const f = leads[ 0 ] ? Object.fromEntries( Object.entries( leads[ 0 ].fields ).map( ( [ k, v ] ) => [ k, plain( v ) ] ) ) : {};
		check( 'Заявка «Калькулятор»: цена, регионы, запросы, варианты, GEO и ответы шага «О сайте»',
			1 === leads.length && 'calculator' === leads[ 0 ].form && 'Итого 19 500 $ / в месяц' === f[ 'Предварительная стоимость' ] && 'Альфа, Тверь' === f[ 'Регион' ] &&
			'К1' === f[ 'Контент' ] && 'В1' === f[ 'Внешняя оптимизация' ] && 'Р1' === f[ 'Репутация' ] && 'Не адаптируем под AI-системы' === f.GEO &&
			'https://shop.example.ru' === f[ 'Адрес сайта' ] && 'Интернет-магазин' === f[ 'Тематика сайта' ] && '40' === f[ 'Количество продвигаемых страниц' ] &&
			'100 заявок' === f[ 'Цель по трафику или заказам' ] && 'До 1 года' === f[ 'Возраст сайта' ] && 'Нет' === f[ 'Есть ли трафик или заказы' ] && f[ 'Телефон' ], JSON.stringify( leads ) );
		leads.forEach( ( l ) => wp( 'post', 'delete', String( l.id ), '--force' ) );
		est = await waitEstimates( page, lastEstimate, 6 );
		const last = est.find( ( e ) => 'Итого 19 500 $ / в месяц' === e.price ) || { fields: {} };
		check( 'Каждый новый расчёт — своя запись: 18 000, 36 500, 29 500, 15 000, 19 500; в последней — ответы шага «О сайте», без имени и телефона',
			JSON.stringify( [ 'Итого 15 000 $ / в месяц', 'Итого 18 000 $ / в месяц', 'Итого 19 500 $ / в месяц', 'Итого 29 500 $ / в месяц', 'Итого 36 500 $ / в месяц' ] ) === JSON.stringify( est.slice( 1 ).map( ( e ) => e.price ).sort() ) &&
			'Альфа, Тверь' === last.region && MARK === last.page && 'https://shop.example.ru' === last.fields[ 'Адрес сайта' ] && 'До 1 года' === last.fields[ 'Возраст сайта' ] && ! ( 'Телефон' in last.fields ),
			JSON.stringify( est.map( ( e ) => [ e.price, e.region, e.page ] ) ) );

		// ===== Просчёты: защита REST =====
		const estPost = async ( fields ) => {
			const data = new FormData();
			Object.entries( fields ).forEach( ( [ k, v ] ) => data.append( k, v ) );
			const res = await fetch( `${ URL }/wp-json/trust/v1/estimates`, { method: 'POST', body: data } );
			return [ res.status, ( await res.json() ).code || 'ok' ];
		};
		const pass = await formToken();
		const r1 = await estPost( { calc_price: 'от 1 $', page: PAGE } );
		const r2 = await estPost( { calc_price: 'от 1 $', _trust_token: pass.token, _trust_nonce: pass.nonce } );
		await page.waitForTimeout( 3100 );
		const r3 = await estPost( { calc_region: 'Москва', _trust_token: pass.token, _trust_nonce: pass.nonce } );
		wp( 'eval', "set_transient( 'trust_estimate_rate_' . md5( '127.0.0.1' ), array( 'count' => 30, 'start' => time() ), 600 );" );
		const r4 = await estPost( { calc_price: 'от 1 $', _trust_token: pass.token, _trust_nonce: pass.nonce } );
		resetFormLimits();
		const r5 = await estPost( { calc_price: 'от 1 $', calc_region: '<b>Тест</b>', _trust_token: pass.token, _trust_nonce: pass.nonce, page: 'https://evil.example/' } );
		est = estimatesAfter( lastEstimate );
		const injected = est[ est.length - 1 ] || {};
		check( 'REST просчётов: без токена — 403 stale, сразу после токена — too_fast, без цены — invalid, после 30 за 10 минут — 429',
			'403 stale|400 too_fast|400 invalid|429 rate_limited' === [ r1, r2, r3, r4 ].map( ( r ) => r.join( ' ' ) ).join( '|' ), JSON.stringify( [ r1, r2, r3, r4 ] ) );
		check( 'REST просчётов: теги вырезаются, чужой адрес страницы не сохраняется', 200 === r5[ 0 ] && 'Тест' === injected.region && '' === injected.page, JSON.stringify( [ r5, injected ] ) );

		// ===== Просчёты в админке =====
		const admin = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
		const ap = await admin.newPage();
		const adminErrors = collectConsoleErrors( ap );
		await login( ap );
		await ap.goto( `${ URL }/wp-admin/edit.php?post_type=lead`, { waitUntil: 'domcontentloaded' } );
		const menu = await ap.evaluate( () => [ ...document.querySelectorAll( '#menu-posts-lead .wp-submenu a' ) ].map( ( a ) => a.textContent.trim() + '=' + new URL( a.href ).search ) );
		check( 'Админка: в меню «Заявки» — пункт «Просчёт стоимости работ»', menu.some( ( m ) => m.startsWith( 'Просчёт стоимости работ' ) && m.endsWith( '?post_type=estimate' ) ), menu.join( ' | ' ) );
		await ap.goto( `${ URL }/wp-admin/edit.php?post_type=estimate`, { waitUntil: 'domcontentloaded' } );
		const list = await ap.evaluate( ( id ) => ( {
			h1: document.querySelector( '.wp-heading-inline' ).textContent.trim(),
			add: !! document.querySelector( '.page-title-action' ),
			cols: [ ...document.querySelectorAll( '.wp-list-table thead th' ) ].map( ( th ) => ( th.querySelector( 'a > span:first-child' ) || th ).textContent.trim() ).filter( Boolean ),
			row: [ ...( document.querySelector( `#post-${ id }` )?.querySelectorAll( 'td' ) || [] ) ].map( ( td ) => td.textContent.replace( /[\s\u00a0\u202f]+/g, ' ' ).trim() ),
			actions: [ ...( document.querySelector( `#post-${ id } .row-actions` )?.querySelectorAll( 'a' ) || [] ) ].map( ( a ) => a.textContent.trim() ),
			bulk: [ ...document.querySelectorAll( '#bulk-action-selector-top option' ) ].map( ( o ) => o.textContent.trim() ),
			current: document.querySelector( '#menu-posts-lead .wp-submenu .current' )?.textContent.trim(),
		} ), last.id );
		check( 'Админка: список просчётов — «Цена», «Регион», «Страница», «Дата», без «Добавить»',
			'Просчёт стоимости работ' === list.h1 && ! list.add && JSON.stringify( [ 'Просчёт', 'Цена', 'Регион', 'Страница', 'Дата' ] ) === JSON.stringify( list.cols.slice( 0, 5 ) ) &&
			String( list.row[ 0 ] ).includes( 'Итого 19 500 $ / в месяц — Альфа, Тверь' ) && 'Итого 19 500 $ / в месяц' === list.row[ 1 ] && 'Альфа, Тверь' === list.row[ 2 ] && MARK === list.row[ 3 ] && 'Просчёт стоимости работ' === list.current, JSON.stringify( list ) );
		check( 'Админка: в строке — «Посмотреть», массовая «Выгрузить в CSV»', list.actions.includes( 'Посмотреть' ) && ! list.actions.some( ( a ) => /Свойства/.test( a ) ) && list.bulk.includes( 'Выгрузить в CSV' ), JSON.stringify( [ list.actions, list.bulk ] ) );
		await ap.goto( `${ URL }/wp-admin/post.php?post=${ last.id }&action=edit`, { waitUntil: 'domcontentloaded' } );
		const box = await ap.evaluate( () => Object.fromEntries( [ ...document.querySelectorAll( '#trust-estimate-box tr' ) ].map( ( tr ) => [ tr.querySelector( 'th' ).textContent.trim(), tr.querySelector( 'td' ).textContent.replace( /[\s\u00a0\u202f]+/g, ' ' ).trim() ] ) ) );
		check( 'Админка: просмотр просчёта — таблица ответов, цена, страница', 'Итого 19 500 $ / в месяц' === box[ 'Предварительная стоимость' ] && 'Альфа, Тверь' === box[ 'Регион' ] && MARK === box[ 'Страница' ] && 'Интернет-магазин' === box[ 'Тематика сайта' ], JSON.stringify( box ) );
		await ap.screenshot( { path: path.join( OUT, 'calculator-estimate-admin.png' ) } );
		await ap.goto( `${ URL }/wp-admin/edit.php?post_type=estimate`, { waitUntil: 'domcontentloaded' } );
		await ap.screenshot( { path: path.join( OUT, 'calculator-estimates-list.png' ) } );
		const csv = JSON.parse( wp( 'eval', `echo wp_json_encode( Trust\\Forms\\Estimates::csv_rows( array( ${ last.id }, ${ injected.id || 0 } ) ) );` ) );
		check( 'CSV просчётов: столбцы ответов, значения последнего расчёта',
			'Предварительная стоимость' === csv[ 0 ][ 7 ] && 'Итого 19 500 $ / в месяц' === plain( csv[ 1 ][ 7 ] ) && 'Альфа, Тверь' === csv[ 1 ][ 8 ] && csv[ 0 ].length === csv[ 1 ].length && 3 === csv.length, JSON.stringify( csv.slice( 0, 2 ) ) );
		check( 'Админка без ошибок JavaScript', 0 === adminErrors.length, adminErrors.slice( 0, 3 ).join( ' | ' ) );
		await admin.close();

		// ===== Телефон =====
		await page.setViewportSize( { width: 390, height: 844 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await calc.scrollIntoViewIfNeeded();
		const order = await calc.evaluate( ( el ) => {
			const nav = el.querySelector( '[data-calc-nav]' );
			const r = ( s ) => nav.querySelector( s ).getBoundingClientRect();
			return [ r( '.b-calc__progress' ).bottom <= r( '[data-calc-next]' ).top, Math.round( r( '.b-calc__progress' ).width ) ];
		} );
		check( '390px: шкала над кнопками «Назад» / «Далее» во всю ширину', order[ 0 ] && order[ 1 ] > 290, JSON.stringify( order ) );
		for ( let i = 0; i < 4; i++ ) {
			await calc.locator( '[data-calc-next]' ).click();
			await page.waitForTimeout( 400 ); // Плавная прокрутка к началу карточки.
		}
		await page.waitForTimeout( 600 );
		const seen = await calc.evaluate( ( el ) => {
			const list = el.querySelector( '[role=tablist]' ).getBoundingClientRect();
			const t = el.querySelector( '[role=tab].is-active' ).getBoundingClientRect();
			return [ t.left >= list.left - 1 && t.right <= list.right + 1, Math.round( el.querySelector( '.b-calc__card' ).getBoundingClientRect().top ) ];
		} );
		check( '390px: после «Далее» вкладка текущего шага прокручена в полосу вкладок, начало карточки на экране', seen[ 0 ] && seen[ 1 ] > -1, JSON.stringify( seen ) );
		const over = await page.evaluate( () => document.documentElement.scrollWidth > innerWidth );
		check( '390px: без прокрутки страницы вбок', ! over );
		await calc.locator( '[data-calc-next]' ).click();
		await calc.locator( '[data-calc-next]' ).click();
		const row = () => calc.evaluate( ( el ) => {
			const back = el.querySelector( '[data-calc-prev]' );
			const go = el.querySelector( '[data-calc-go]' );
			const b = back.getBoundingClientRect();
			const g = go.getBoundingClientRect();
			const nav = el.querySelector( '[data-calc-nav]' ).getBoundingClientRect();
			return {
				line: Math.abs( b.top - g.top ) < 1 && b.right < g.left && g.right <= nav.right,
				go: go.innerText.trim(),
				back: back.querySelector( '.b-calc__back-text' ).getBoundingClientRect().width > 2 ? back.innerText.trim() : '',
				name: go.getAttribute( 'aria-label' ),
				over: document.documentElement.scrollWidth > innerWidth,
			};
		} );
		const navRows = {};
		for ( const width of [ 280, 320, 390, 480 ] ) {
			await page.setViewportSize( { width, height: 844 } );
			navRows[ width ] = await row();
		}
		await page.screenshot( { path: path.join( OUT, 'calculator-step7-480.png' ) } );
		await page.setViewportSize( { width: 390, height: 844 } );
		await calc.locator( '[data-calc-go]' ).scrollIntoViewIfNeeded();
		await page.screenshot( { path: path.join( OUT, 'calculator-step7-390.png' ) } );
		check( 'Шаг 7 на телефоне: «Назад» и «Рассчитать» в одну строку (280, 320, 390, 480 px), без прокрутки вбок',
			Object.values( navRows ).every( ( r ) => r.line && ! r.over ), JSON.stringify( navRows ) );
		check( 'Узкий экран: на кнопке «Рассчитать» (полное название «Рассчитать стоимость» — для скринридеров), на 480px — «Рассчитать стоимость»',
			'Рассчитать' === navRows[ 390 ].go && 'Рассчитать стоимость' === navRows[ 390 ].name && 'Рассчитать стоимость' === navRows[ 480 ].go, JSON.stringify( navRows ) );
		check( 'Совсем узкий экран (280, 320px): у «Назад» — стрелка, текст «Назад» для скринридеров; с 390px — «Назад»',
			'' === navRows[ 280 ].back && '' === navRows[ 320 ].back && 'Назад' === navRows[ 390 ].back && 'Назад' === await calc.locator( '[data-calc-prev]' ).evaluate( ( b ) => b.textContent.trim() ), JSON.stringify( navRows ) );

		// ===== Без JavaScript =====
		const nojs = await browser.newContext( { viewport: { width: 1280, height: 900 }, javaScriptEnabled: false } );
		const np = await nojs.newPage();
		await np.goto( PAGE, { waitUntil: 'networkidle' } );
		const nj = await np.evaluate( () => {
			const el = document.querySelector( '.b-calc' );
			return {
				panels: [ ...el.querySelectorAll( '[role=tabpanel]' ) ].filter( ( p ) => 'none' !== getComputedStyle( p ).display ).length,
				tabs: getComputedStyle( el.querySelector( '[role=tablist]' ) ).display,
				nav: getComputedStyle( el.querySelector( '[data-calc-nav]' ) ).display,
				note: getComputedStyle( el.querySelector( '.b-calc__nojs' ) ).display,
			};
		} );
		check( 'Без JavaScript: все 7 шагов подряд, без вкладок и кнопок, подсказка и кнопка связи', 7 === nj.panels && 'none' === nj.tabs && 'none' === nj.nav && 'block' === nj.note, JSON.stringify( nj ) );
		await nojs.close();

		// ===== Сверка с макетом =====
		for ( const width of [ 1280, 768, 390 ] ) {
			await compare( page, { name: 'calc-step1', label: 'шаг 1', width } );
		}
		for ( const [ n, label ] of [ [ 3, 'шаг 3' ], [ 6, 'шаг 6' ] ] ) {
			await compare( page, {
				name: `calc-step${ n }`, label, width: 1280,
				before: async ( p ) => p.locator( '.b-calc [role=tab]' ).nth( n - 1 ).click(),
				mockBefore: async ( p ) => p.locator( '[role=tab]' ).nth( n - 1 ).click(),
			} );
		}
		await compare( page, {
			name: 'calc-result', label: 'результат', width: 1280,
			// Цена в теме посчитана, в макете — заглушка «от XX XXX ₽» (в сверке ₽ → $).
			before: async ( p ) => {
				await p.locator( '.b-calc [role=tab]' ).nth( 6 ).click();
				await p.locator( '.b-calc [data-calc-go]' ).click();
				await p.evaluate( () => ( document.querySelector( '.b-calc [data-calc-price]' ).textContent = 'от XX XXX $' ) );
			},
			mockBefore: async ( p ) => {
				await p.locator( '[role=tab]' ).nth( 6 ).click();
				await p.locator( 'button', { hasText: 'Рассчитать стоимость' } ).click();
			},
		} );

		// ===== Доступность =====
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
		const axeRun = () => page.evaluate( async () => {
			const r = await window.axe.run( '.b-calc', { resultTypes: [ 'violations' ] } );
			return r.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length })` );
		} );
		let axe = await axeRun();
		check( 'axe, шаг 1: нет нарушений critical/serious', 0 === axe.length, axe.join( ', ' ) );
		await tab( 6 ).click();
		await page.waitForTimeout( 400 ); // «Назад» становится активной с анимацией прозрачности.
		axe = await axeRun();
		check( 'axe, шаг 7: нет нарушений critical/serious', 0 === axe.length, axe.join( ', ' ) );
		await calc.locator( '[data-calc-go]' ).click();
		axe = await axeRun();
		check( 'axe, результат: нет нарушений critical/serious', 0 === axe.length, axe.join( ', ' ) );
		check( 'Консоль браузера на сайте без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1440, height: 1000 } } );
		const editorErrors = [];
		editor.on( 'pageerror', ( e ) => editorErrors.push( e.message ) );
		await login( editor );
		const id = wp( 'eval', 'echo get_page_by_path( "seo/audit" )->ID;' );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ id }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		const type = await editor.evaluate( () => {
			const t = wp.blocks.getBlockType( 'trust/calculator' );
			return [ t && t.title, t && t.category, !! ( t && t.example ) ];
		} );
		check( 'Редактор: «Калькулятор стоимости» в категории «Формы», есть превью', JSON.stringify( [ 'Калькулятор стоимости', 'trust-forms', true ] ) === JSON.stringify( type ), JSON.stringify( type ) );
		await editor.waitForTimeout( 2500 );
		await editor.evaluate( () => wp.data.dispatch( 'core/block-editor' ).selectBlock( wp.data.select( 'core/block-editor' ).getBlocks().find( ( b ) => 'trust/calculator' === b.name ).clientId ) );
		await editor.waitForTimeout( 1500 );
		const rows = await editor.evaluate( () => [ 'regions', 'content', 'external', 'reputation', 'ai' ].map( ( n ) => document.querySelectorAll( `.interface-complementary-area [data-name="${ n }"] .acf-row:not(.acf-clone)` ).length ) );
		check( 'Редактор: в настройках блока сразу варианты из макета — 8 регионов, 3 + 3 + 4 варианта, 4 AI-системы', '8,3,3,4,4' === rows.join(), rows.join() );
		const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		check( 'Редактор: превью калькулятора — все шаги', 7 === await canvas.locator( '.b-calc [role=tabpanel]' ).count() );
		await editor.screenshot( { path: path.join( OUT, 'calculator-editor.png' ) } );
		check( 'Редактор без ошибок JavaScript', 0 === editorErrors.length, editorErrors.slice( 0, 3 ).join( ' | ' ) );
	} finally {
		created.forEach( ( id ) => wp( 'post', 'delete', String( id ), '--force' ) );
		leadsBy( 'Тест Калькулятор' ).forEach( ( l ) => wp( 'post', 'delete', String( l.id ), '--force' ) );
		estimatesAfter( lastEstimate ).forEach( ( e ) => wp( 'post', 'delete', String( e.id ), '--force' ) );
		resetFormLimits();
	}

	await browser.close();
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
