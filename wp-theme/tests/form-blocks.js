/**
 * Тест блоков шага 4.6 с формами: «С какими проблемами вы можете столкнуться» (форма «Заявка на
 * аудит»), «Бриф — полный» (форма «Бриф»), «Бриф — короткий» (форма «Бриф (короткий)»).
 *
 * Запуск: node wp-theme/tests/form-blocks.js
 *
 * Разметка: шапка, проблемы (H3, иконки), поля форм с подписями, галочка согласия вместо фразы
 * «Нажимая кнопку…» (решение заказчика), в брифах — «Имя» и «Как с вами связаться?» (решение
 * заказчика), переключатель «Продвижение / Бесплатный аудит», варианты-«таблетки» (несколько),
 * карточка «Расшарить доступ» с email.
 * Отправка: пустая форма — ошибки под полями, фокус на первом; неверный контакт — ошибка;
 * заполненная — экран «Заявка отправлена», в «Заявках» — форма, поля (варианты списком), страница;
 * «Отправить ещё» возвращает пустую форму; без JavaScript — обычная отправка и возврат к форме;
 * REST: чужие варианты длиннее 100 символов — ошибка, лишние поля не сохраняются.
 * Общее: совпадение с макетами seo-bloki/07, 11a, 11b на 1280, 768 и 390 px (≤ 2 %; добавленные
 * поля и галочка согласия в теме, «Я не робот» и фраза в макете скрываются); ширины 1440–280 px;
 * зоны нажатия; axe-core; консоль и журнал ошибок; редактор — категория «Формы», превью.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp, resetFormLimits, formToken, postForm } = require( './lib' );

const SEO = `${ URL }/seo/`;
const QUICK = `${ URL }/geo/audit-vidimosti/`;

/** Заявки по имени: форма, поля (подпись => значение), страница. */
function leadsBy( name ) {
	return JSON.parse( wp( 'eval', `echo wp_json_encode( array_map( static fn( $p ) => array( 'id' => $p->ID, 'form' => get_post_meta( $p->ID, 'trust_form', true ), 'page' => get_post_meta( $p->ID, 'trust_page', true ), 'fields' => array_column( (array) get_post_meta( $p->ID, 'trust_fields', true ), 'value', 'label' ) ), get_posts( array( 'post_type' => 'lead', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => 'trust_name', 'meta_value' => '${ name }' ) ) ) );` ) );
}

/**
 * Снимки блока темы и макета и их сравнение.
 *
 * @param {import('playwright').Page} page Страница.
 * @param {Object}                    o    name, width, url, theme, mockup, hide (селекторы, скрываемые в теме), before (в теме), mockBefore (в макете).
 */
async function compare( page, o ) {
	const name = `${ o.name }-${ o.width }`;
	await page.setViewportSize( { width: o.width, height: 900 } );
	await page.goto( o.url, { waitUntil: 'networkidle' } );
	await settle( page );
	if ( o.hide ) {
		await page.evaluate( ( list ) => list.forEach( ( s ) => document.querySelectorAll( s ).forEach( ( el ) => ( el.style.display = 'none' ) ) ), o.hide );
	}
	if ( o.before ) {
		await page.evaluate( o.before );
	}
	await page.mouse.move( 0, 0 );
	await isolate( page, o.theme );
	await page.locator( o.theme ).screenshot( { path: path.join( OUT, `${ name }-theme.png` ) } );

	await page.goto( mockupUrl( o.mockup ), { waitUntil: 'networkidle' } );
	await settle( page );
	if ( o.mockBefore ) {
		await page.evaluate( o.mockBefore );
	}
	await isolate( page, 'section' );
	await page.locator( 'section' ).first().screenshot( { path: path.join( OUT, `${ name }-mockup.png` ) } );
	const diff = comparePng( path.join( OUT, `${ name }-mockup.png` ), path.join( OUT, `${ name }-theme.png` ), path.join( OUT, `${ name }-diff.png` ) );
	check( `Макет ${ o.mockup } ${ o.width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
}

/** Видимые ошибки полей формы. */
const errorsOf = ( form ) => form.locator( '.field__error:not([hidden])' ).allTextContents();

( async () => {
	clearLog();
	resetFormLimits();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const names = [ 'Тест Аудит', 'Тест Бриф', 'Тест Короткий', 'Тест БезСкрипта', 'Тест REST' ];

	try {
		// ===== «С какими проблемами вы можете столкнуться» =====
		await page.goto( SEO, { waitUntil: 'networkidle' } );
		const order = await page.evaluate( () => [ ...document.querySelectorAll( '.page-entry__content > section' ) ].map( ( s ) => s.className.split( ' ' )[ 0 ] ) );
		check( 'Страница /seo/: «Проблемы» после картинки, полный бриф в конце',
			JSON.stringify( order ) === JSON.stringify( [ 'b-hero-service', 'b-tariffs', 'b-text', 'b-image', 'b-problems', 'b-idea', 'b-brief' ] ), order.join( ', ' ) );
		const pr = await page.locator( '.b-problems' ).evaluate( ( el ) => ( {
			id: el.id,
			h2: el.querySelector( 'h2' ).textContent.trim(),
			label: el.getAttribute( 'aria-labelledby' ) === el.querySelector( 'h2' ).id,
			eyebrow: el.querySelector( '.eyebrow' ).textContent.trim(),
			items: [ ...el.querySelectorAll( '.b-problems__item' ) ].map( ( li ) => [ li.querySelector( 'h3' ).textContent.trim(), !! li.querySelector( '.b-problems__icon svg' ), getComputedStyle( li.querySelector( '.b-problems__icon' ) ).color ] ),
			inputs: [ ...el.querySelectorAll( 'form .field__input' ) ].map( ( i ) => `${ i.name }:${ i.getAttribute( 'aria-label' ) }` ),
			consent: !! el.querySelector( 'form input[name=consent][required]' ) && ! /Нажимая кнопку/.test( el.textContent ),
			submit: el.querySelector( 'form [type=submit]' ).textContent.trim(),
			formLabel: el.querySelector( 'form' ).getAttribute( 'aria-labelledby' ) && document.getElementById( el.querySelector( 'form' ).getAttribute( 'aria-labelledby' ) ).textContent.trim(),
		} ) );
		check( 'Проблемы: якорь #problems, H2, надзаголовок «Признаки»', 'problems' === pr.id && 'С какими проблемами вы можете столкнуться' === pr.h2 && pr.label && 'Признаки' === pr.eyebrow, JSON.stringify( pr ).slice( 0, 200 ) );
		check( 'Проблемы: 6 пунктов H3 с красными иконками', 6 === pr.items.length && pr.items.every( ( i ) => i[ 1 ] && 'rgb(214, 58, 47)' === i[ 2 ] ) && 'Трафик из поиска не растёт' === pr.items[ 0 ][ 0 ], pr.items.map( ( i ) => i[ 0 ] ).join( ' | ' ) );
		check( 'Проблемы: форма «Заявка на аудит» — адрес сайта, имя, телефон или email, галочка согласия (без фразы «Нажимая кнопку…»)',
			'url:Адрес сайта,name:Имя,contact:Телефон или email' === pr.inputs.join() && pr.consent && 'Проверить точки роста на моём сайте' === pr.submit && 'Заявка на аудит' === pr.formLabel, JSON.stringify( pr.inputs ) );
		const linkToProblems = await page.locator( '.b-text a[href="#problems"]' ).count();
		check( 'Текст: ссылка «С какими проблемами вы можете столкнуться» ведёт на блок этой страницы', 1 === linkToProblems );

		const audit = page.locator( '.b-problems form' );
		await audit.locator( '[type=submit]' ).click();
		let shown = await errorsOf( audit );
		check( 'Заявка на аудит: пустая — ошибки у адреса, имени, контакта и галочки, фокус на адресе',
			4 === shown.length && await page.evaluate( () => 'url' === document.activeElement.name ), shown.join( ' | ' ) );
		await audit.locator( '[name=url]' ).fill( 'example' );
		await audit.locator( '[name=name]' ).fill( 'Тест Аудит' );
		await audit.locator( '[name=contact]' ).fill( 'не контакт' );
		await audit.locator( '[name=consent]' ).check();
		await audit.locator( '[type=submit]' ).click();
		shown = await errorsOf( audit );
		check( 'Заявка на аудит: адрес без домена и контакт не телефон и не email — ошибки с примерами', 2 === shown.length && /example\.ru/.test( shown[ 0 ] ) && /name@example\.ru/.test( shown[ 1 ] ), shown.join( ' | ' ) );
		await audit.locator( '[name=url]' ).fill( 'example.ru' );
		await audit.locator( '[name=contact]' ).fill( 'test@example.ru' );
		await audit.locator( '[type=submit]' ).click();
		await page.locator( '.b-problems .form-sent' ).waitFor( { state: 'visible', timeout: 15000 } );
		const sent = await page.locator( '.b-problems' ).evaluate( ( el ) => ( {
			form: el.querySelector( 'form' ).hidden,
			text: el.querySelector( '.form-sent' ).innerText.replace( /\s+/g, ' ' ).trim(),
			focus: document.activeElement.classList.contains( 'form-sent__again' ),
		} ) );
		check( 'Заявка на аудит: отправлена — «Заявка отправлена», форма скрыта, фокус на «Отправить ещё одну заявку»', sent.form && sent.text.startsWith( 'Заявка отправлена' ) && sent.focus, JSON.stringify( sent ) );
		let leads = leadsBy( 'Тест Аудит' );
		check( 'Заявка на аудит: в «Заявках» — форма «audit», адрес сайта, имя, контакт, страница /seo/',
			1 === leads.length && 'audit' === leads[ 0 ].form && 'example.ru' === leads[ 0 ].fields[ 'Адрес сайта' ] && 'test@example.ru' === leads[ 0 ].fields[ 'Телефон или email' ] && SEO === leads[ 0 ].page, JSON.stringify( leads ) );
		await page.locator( '.b-problems .form-sent__again' ).click();
		const again = await audit.evaluate( ( f ) => ! f.hidden && '' === f.querySelector( '[name=url]' ).value && document.activeElement.name === 'url' );
		check( 'Заявка на аудит: «Отправить ещё одну заявку» — снова пустая форма, фокус на первом поле', again );

		// ===== «Бриф — полный» =====
		const brief = page.locator( '.b-brief form' );
		const bf = await page.locator( '.b-brief' ).evaluate( ( el ) => ( {
			id: el.id,
			h2: el.querySelector( 'h2' ).textContent.trim(),
			modes: [ ...el.querySelectorAll( 'input[name=mode]' ) ].map( ( i ) => `${ i.value }:${ i.checked }:${ i.closest( 'label' ).textContent.trim() }` ),
			labels: [ ...el.querySelectorAll( '.field__label' ) ].map( ( l ) => l.textContent.trim() + ( document.getElementById( l.htmlFor ) ? '' : ' (нет поля)' ) ),
			chips: [ ...el.querySelectorAll( 'input[name="wants[]"]' ) ].map( ( i ) => i.value ),
			legend: el.querySelector( '.b-brief__legend' ).textContent.replace( /\s+/g, ' ' ).trim(),
			consent: !! el.querySelector( 'input[name=consent][required]' ) && ! /Нажимая кнопку/.test( el.textContent ),
			submit: el.querySelector( '[type=submit]' ).textContent.trim(),
		} ) );
		check( 'Бриф: якорь #brief, H2 «Заполните бриф», переключатель «Продвижение / Бесплатный аудит» (радиокнопки, выбрано «Продвижение»)',
			'brief' === bf.id && 'Заполните бриф' === bf.h2 && 'promo:true:Продвижение,audit:false:Бесплатный аудит' === bf.modes.join(), JSON.stringify( bf.modes ) );
		check( 'Бриф: подписи полей, «Имя» и «Как с вами связаться?» (решение заказчика)',
			JSON.stringify( bf.labels ) === JSON.stringify( [ 'Адрес сайта', 'Тематика сайта', 'Опишите проблему или задачу', 'Имя', 'Как с вами связаться?' ] ), JSON.stringify( bf.labels ) );
		check( 'Бриф: 8 вариантов из макета, «можно выбрать несколько», галочка согласия, кнопка «Получить»',
			8 === bf.chips.length && 'Вывести сайт в топ' === bf.chips[ 0 ] && 'Другое' === bf.chips[ 7 ] && bf.legend.endsWith( '· можно выбрать несколько' ) && bf.consent && 'Получить' === bf.submit, JSON.stringify( bf ) );
		await brief.locator( '.b-brief__mode', { hasText: 'Бесплатный аудит' } ).click();
		await brief.locator( '.b-brief__chip', { hasText: 'Вывести сайт в топ' } ).click();
		await brief.locator( '.b-brief__chip', { hasText: 'Упал трафик' } ).click();
		await page.mouse.move( 0, 0 );
		await page.waitForTimeout( 400 );
		const chipStyle = await brief.locator( '.b-brief__chip' ).first().evaluate( ( c ) => [ getComputedStyle( c ).backgroundColor, getComputedStyle( c.querySelector( '.b-brief__chip-check' ) ).display ] );
		check( 'Бриф: отмеченный вариант — фиолетовая «таблетка» с галочкой', 'rgb(109, 40, 217)' === chipStyle[ 0 ] && 'flex' === chipStyle[ 1 ], JSON.stringify( chipStyle ) );
		await brief.locator( '[type=submit]' ).click();
		shown = await errorsOf( brief );
		check( 'Бриф: без адреса, имени, контакта и галочки — 4 ошибки (тематика и задача необязательны)', 4 === shown.length, shown.join( ' | ' ) );
		await brief.locator( '[name=url]' ).fill( 'https://shop.example.ru' );
		await brief.locator( '[name=topic]' ).fill( 'Интернет-магазин' );
		await brief.locator( '[name=problem]' ).fill( 'После переезда упал трафик' );
		await brief.locator( '[name=name]' ).fill( 'Тест Бриф' );
		await brief.locator( '[name=contact]' ).fill( '+1 000 000 00 00' );
		await brief.locator( '[name=consent]' ).check();
		await brief.locator( '[type=submit]' ).click();
		await page.locator( '.b-brief .form-sent' ).waitFor( { state: 'visible', timeout: 15000 } );
		leads = leadsBy( 'Тест Бриф' );
		const bfFields = leads[ 0 ] && leads[ 0 ].fields;
		check( 'Бриф: в «Заявках» — тип «Бесплатный аудит», сайт, тематика, задача, варианты списком, контакт',
			1 === leads.length && 'brief' === leads[ 0 ].form && 'Бесплатный аудит' === bfFields[ 'Тип заявки' ] && 'https://shop.example.ru' === bfFields[ 'Адрес сайта' ] &&
			'Интернет-магазин' === bfFields[ 'Тематика сайта' ] && 'Вывести сайт в топ, Упал трафик' === bfFields[ 'Что хотите получить' ] && '+1 000 000 00 00' === bfFields[ 'Как с вами связаться' ], JSON.stringify( leads ) );
		await page.locator( '.b-brief .form-sent__again' ).click();
		const reset = await brief.evaluate( ( f ) => ( { mode: f.querySelector( 'input[name=mode]:checked' ).value, chips: f.querySelectorAll( 'input[name="wants[]"]:checked' ).length } ) );
		check( 'Бриф: «Заполнить бриф ещё раз» — форма сброшена («Продвижение», варианты не отмечены)', 'promo' === reset.mode && 0 === reset.chips, JSON.stringify( reset ) );

		// ===== «Бриф — короткий» =====
		await page.goto( QUICK, { waitUntil: 'networkidle' } );
		const quick = page.locator( '.b-quick form' );
		const qf = await page.locator( '.b-quick' ).evaluate( ( el ) => ( {
			id: el.id,
			h2: el.querySelector( 'h2' ).textContent.trim(),
			inputs: [ ...el.querySelectorAll( '.b-quick__bar .field__input' ) ].map( ( i ) => `${ i.name }:${ i.placeholder }` ),
			share: el.querySelector( 'input[name=share]' ) ? el.querySelector( '.b-quick__share-title' ).textContent.trim() : '',
			note: getComputedStyle( el.querySelector( '.b-quick__share-note' ) ).display,
			email: el.querySelector( '.b-quick__share-note a' ).getAttribute( 'href' ),
			divider: el.querySelector( '.b-quick__divider-text' ).textContent.trim(),
		} ) );
		check( 'Короткий бриф: якорь #free-audit, H2, поля адрес, имя, «Как с вами связаться?» (решение заказчика), «Также советуем»',
			'free-audit' === qf.id && 'Получите бесплатный аудит сайта' === qf.h2 && 'url:https://example.ru,name:Ваше имя,contact:Как с вами связаться?' === qf.inputs.join() && 'Также советуем' === qf.divider, JSON.stringify( qf ) );
		await quick.locator( '.b-quick__share-label' ).click();
		await page.waitForTimeout( 400 );
		const opened = await page.locator( '.b-quick' ).evaluate( ( el ) => [ getComputedStyle( el.querySelector( '.b-quick__share-note' ) ).display, getComputedStyle( el.querySelector( '.b-quick__share' ) ).borderTopColor ] );
		check( 'Короткий бриф: «Расшарить доступ» — по флажку раскрывается email для доступа (основной email сайта), рамка фиолетовая',
			'none' === qf.note && 'flex' === opened[ 0 ] && 'rgb(109, 40, 217)' === opened[ 1 ] && /^mailto:.+@/.test( qf.email ), JSON.stringify( { qf: qf.email, opened } ) );
		await quick.locator( '[name=url]' ).fill( 'example.ru' );
		await quick.locator( '[name=name]' ).fill( 'Тест Короткий' );
		await quick.locator( '[name=contact]' ).fill( 'quick@example.ru' );
		await quick.locator( '[type=submit]' ).click();
		shown = await errorsOf( quick );
		check( 'Короткий бриф: без галочки согласия — ошибка у галочки', 1 === shown.length && /согласие/.test( shown[ 0 ] ), shown.join( ' | ' ) );
		await quick.locator( '[name=consent]' ).check();
		await quick.locator( '[type=submit]' ).click();
		await page.locator( '.b-quick .form-sent' ).waitFor( { state: 'visible', timeout: 15000 } );
		leads = leadsBy( 'Тест Короткий' );
		check( 'Короткий бриф: в «Заявках» — сайт, контакт, «Расшарить доступ к статистике: да»',
			1 === leads.length && 'brief-quick' === leads[ 0 ].form && 'example.ru' === leads[ 0 ].fields[ 'Адрес сайта' ] && 'да' === leads[ 0 ].fields[ 'Расшарить доступ к статистике' ], JSON.stringify( leads ) );

		// Без JavaScript: обычная отправка и возврат к форме с сообщением.
		resetFormLimits();
		const noJs = await browser.newContext( { javaScriptEnabled: false } );
		const p2 = await noJs.newPage();
		await p2.goto( SEO );
		const f2 = p2.locator( '.b-problems form' );
		await f2.locator( '[name=url]' ).fill( 'example.ru' );
		await f2.locator( '[name=name]' ).fill( 'Тест БезСкрипта' );
		await f2.locator( '[name=contact]' ).fill( 'nojs@example.ru' );
		await f2.locator( '[name=consent]' ).check( { force: true } );
		await p2.waitForTimeout( 3500 );
		await f2.locator( '[type=submit]' ).click();
		await p2.waitForLoadState( 'load' );
		const back = await p2.evaluate( () => ( { hash: location.hash, status: document.querySelector( '.b-problems .form__status' ) ? document.querySelector( '.b-problems .form__status' ).textContent.trim() : '' } ) );
		await noJs.close();
		check( 'Без JavaScript: заявка уходит, возврат к этой форме с сообщением', /-form$/.test( back.hash ) && back.status.length > 10 && 1 === leadsBy( 'Тест БезСкрипта' ).length, JSON.stringify( back ) );

		// REST: варианты — подписи, не длиннее 100 символов; лишние поля не сохраняются.
		resetFormLimits();
		const t = await formToken();
		await new Promise( ( r ) => setTimeout( r, 3100 ) );
		const base = { _trust_token: t.token, _trust_nonce: t.nonce, mode: 'promo', url: 'example.ru', name: 'Тест REST', contact: 'rest@example.ru', consent: '1', extra: 'лишнее' };
		const bad = await postForm( 'brief', { ...base, 'wants[]': 'а'.repeat( 101 ) } );
		const badMode = await postForm( 'brief', { ...base, mode: 'other' } );
		const ok = await postForm( 'brief', { ...base, 'wants[]': 'Свой вариант' } );
		leads = leadsBy( 'Тест REST' );
		check( 'REST: вариант длиннее 100 символов и неизвестный тип заявки — ошибка полей; свой вариант принимается, лишнее поле не сохраняется',
			400 === bad.status && bad.body.errors.wants && 400 === badMode.status && badMode.body.errors.mode && 200 === ok.status && 1 === leads.length && 'Свой вариант' === leads[ 0 ].fields[ 'Что хотите получить' ] && ! JSON.stringify( leads[ 0 ].fields ).includes( 'лишнее' ),
			JSON.stringify( { bad: bad.status, badMode: badMode.status, ok: ok.status, leads } ) );
		const types = JSON.parse( wp( 'eval', "echo wp_json_encode( array_map( static fn( $t ) => array( $t['label'], (bool) $t['captcha'] ), array_intersect_key( Trust\\Forms::types(), array_flip( array( 'audit', 'brief', 'brief-quick' ) ) ) ) );" ) );
		check( 'Формы: «Заявка на аудит» (с капчей, если она настроена), «Бриф», «Бриф (короткий)» — в настройках получателей и фильтре заявок',
			JSON.stringify( types ) === JSON.stringify( { audit: [ 'Заявка на аудит', true ], brief: [ 'Бриф', false ], 'brief-quick': [ 'Бриф (короткий)', false ] } ), JSON.stringify( types ) );

		// ===== Совпадение с макетами =====
		// В теме — галочка согласия (вместо фразы «Нажимая кнопку…») и поля для связи в брифах (решения
		// заказчика): при сверке они скрыты; в макете скрыты фраза и место «Я не робот» (капча на стенде не настроена).
		for ( const width of [ 1280, 768, 390 ] ) {
			await compare( page, {
				name: 'problems', width, url: SEO, theme: '.b-problems', mockup: 'seo-bloki/07-problemy.html',
				hide: [ '.b-problems .form__consent' ],
				mockBefore: () => {
					document.querySelector( 'form label:has(input[name=captcha])' ).style.display = 'none';
					document.querySelector( 'form > p' ).style.display = 'none';
				},
			} );
			await compare( page, {
				name: 'brief', width, url: SEO, theme: '.b-brief', mockup: 'seo-bloki/11a-brif-polnyy.html',
				before: () => {
					const form = document.querySelector( '.b-brief form' );
					[ ...form.querySelectorAll( '.b-brief__row' ) ][ 1 ].style.display = 'none';
					form.querySelector( '.form__consent' ).style.display = 'none';
					[ 0, 2 ].forEach( ( i ) => ( form.querySelectorAll( 'input[name="wants[]"]' )[ i ].checked = true ) );
				},
				mockBefore: () => ( document.querySelector( 'form p' ).style.display = 'none' ),
			} );
			await compare( page, {
				name: 'brief-quick', width, url: QUICK, theme: '.b-quick', mockup: 'seo-bloki/11b-brif-korotkiy.html',
				hide: [ '.b-quick__field--name', '.b-quick__field--contact', '.b-quick .form__consent' ],
			} );
		}

		// ===== Ширины, зоны нажатия, доступность =====
		for ( const w of [ 1440, 1024, 768, 600, 390, 320, 280 ] ) {
			await page.setViewportSize( { width: w, height: 800 } );
			const res = { scroll: [], small: [] };
			for ( const url of [ SEO, QUICK ] ) {
				await page.goto( url, { waitUntil: 'networkidle' } );
				const r = await page.evaluate( () => ( {
					scroll: document.documentElement.scrollWidth > innerWidth,
					small: [ ...document.querySelectorAll( '.b-problems button, .b-brief button, .b-brief label.b-brief__mode, .b-brief label.b-brief__chip, .b-quick button, .b-quick .field__input, .b-problems .field__input' ) ].filter( ( el ) => el.offsetParent && el.getBoundingClientRect().height < 44 ).map( ( el ) => el.className || el.textContent.trim() ),
				} ) );
				if ( r.scroll ) {
					res.scroll.push( url );
				}
				res.small.push( ...r.small );
			}
			check( `Ширина ${ w }px: без прокрутки вбок, зоны нажатия ≥ 44 px`, 0 === res.scroll.length && 0 === res.small.length, JSON.stringify( res ) );
		}
		await page.setViewportSize( { width: 1280, height: 900 } );
		for ( const [ url, include ] of [ [ SEO, [ '.b-problems', '.b-brief' ] ], [ QUICK, [ '.b-quick' ] ] ] ) {
			await page.goto( url, { waitUntil: 'networkidle' } );
			await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
			const axe = await page.evaluate( async ( inc ) => {
				const r = await window.axe.run( { include: inc }, { resultTypes: [ 'violations' ] } );
				return r.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length })` );
			}, include );
			check( `axe ${ include.join( ', ' ) }: нет нарушений critical/serious`, axe.length === 0, axe.join( ', ' ) );
		}
		check( 'Консоль браузера на сайте без ошибок', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1440, height: 1000 } } );
		const editorErrors = [];
		editor.on( 'pageerror', ( e ) => editorErrors.push( e.message ) );
		await login( editor );
		const id = wp( 'eval', 'echo get_page_by_path( "seo" )->ID;' );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ id }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		const reg = await editor.evaluate( () => [ 'trust/problems', 'trust/brief', 'trust/brief-quick' ].map( ( name ) => {
			const type = wp.blocks.getBlockType( name );
			return [ name, type && type.title, type && type.category, !! ( type && type.example ) ];
		} ) );
		check( 'Редактор: три блока в категории «Формы», у всех превью',
			JSON.stringify( reg ) === JSON.stringify( [
				[ 'trust/problems', 'С какими проблемами вы можете столкнуться', 'trust-forms', true ],
				[ 'trust/brief', 'Бриф — полный', 'trust-forms', true ],
				[ 'trust/brief-quick', 'Бриф — короткий', 'trust-forms', true ],
			] ), JSON.stringify( reg ) );
		const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-brief' ).first().waitFor( { timeout: 30000 } );
		check( 'Редактор: превью — 6 проблем с формой, бриф с 8 вариантами',
			6 === await canvas.locator( '.b-problems__item' ).count() && 1 === await canvas.locator( '.b-problems form' ).count() && 8 === await canvas.locator( '.b-brief__chip' ).count() );
		await editor.screenshot( { path: path.join( OUT, 'form-blocks-editor.png' ) } );
		check( 'Редактор без ошибок JavaScript', editorErrors.length === 0, editorErrors.slice( 0, 3 ).join( ' | ' ) );
	} finally {
		names.forEach( ( n ) => leadsBy( n ).forEach( ( l ) => wp( 'post', 'delete', String( l.id ), '--force' ) ) );
		resetFormLimits();
	}

	await browser.close();
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
