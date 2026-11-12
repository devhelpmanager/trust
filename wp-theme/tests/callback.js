/**
 * Тест окна «Заказать обратный звонок» (шаг 1.6) на локальном стенде с демо-контентом.
 *
 * Запуск: node wp-theme/tests/callback.js
 *
 * Проверяет:
 * - окно открывают кнопки шапки (текст и иконка), мобильного меню и подвала; фокус — в поле «Имя»;
 * - закрытие: Esc, крестик, щелчок по фону; щелчок внутри окна не закрывает; фокус возвращается на кнопку;
 * - фокус не уходит из окна по Tab; страница под окном не прокручивается;
 * - проверку полей: пустые, неверный телефон, нет согласия; ошибки снимаются по мере ввода;
 * - при верных данных — заявка уходит (шаг 3.5), окно показывает «Заявка отправлена»,
 *   при следующем открытии — снова пустая форма; созданная заявка удаляется в конце;
 * - ссылки на согласие и политику конфиденциальности; тексты окна из «Настроек сайта»;
 * - ширины 280–1280 px без прокрутки вбок, зоны нажатия ≥ 44 px; axe-core; консоль и журнал ошибок.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, wp, clearLog, checkLog, collectConsoleErrors, getSettings, setSettings, resetFormLimits } = require( './lib' );

( async () => {
	clearLog();
	resetFormLimits();
	const leadsBefore = wp( 'post', 'list', '--post_type=lead', '--post_status=any', '--field=ID', '--format=ids' );
	const browser = await launch();
	const page = await browser.newPage( { viewport: { width: 1280, height: 800 } } );
	const errors = collectConsoleErrors( page );
	const dialog = page.locator( '#callback-modal' );
	const isOpen = () => dialog.evaluate( ( el ) => el.open );
	const active = () => page.evaluate( () => ( document.activeElement.id || document.activeElement.className ) );

	// --- Кнопки, которые открывают окно ---
	const triggers = [
		{ name: 'кнопка в шапке', width: 1280, selector: '.header-callback' },
		{ name: 'кнопка в подвале', width: 1280, selector: '.site-footer__callback' },
		{ name: 'иконка в шапке (телефон)', width: 390, selector: '.header-callback-icon' },
		{ name: 'кнопка в мобильном меню', width: 390, selector: '.mobile-nav__callback', before: '.header-burger' },
	];
	for ( const t of triggers ) {
		await page.setViewportSize( { width: t.width, height: 800 } );
		await page.goto( `${ URL }/kontakty/`, { waitUntil: 'networkidle' } );
		if ( t.before ) {
			await page.click( t.before );
		}
		await page.click( t.selector );
		const focus = await active();
		check( `Открывает: ${ t.name }; фокус в поле «Имя»`, ( await isOpen() ) && focus === 'callback-name', focus );
		await page.keyboard.press( 'Escape' );
		const back = await page.evaluate( ( sel ) => document.activeElement.matches( sel ), t.selector );
		check( `Esc закрывает, фокус возвращается: ${ t.name }`, ! ( await isOpen() ) && back );
	}

	// --- Закрытие ---
	await page.setViewportSize( { width: 1280, height: 800 } );
	await page.goto( `${ URL }/kontakty/`, { waitUntil: 'networkidle' } );
	await page.click( '.header-callback' );
	check( 'Заголовок окна', ( await page.textContent( '#callback-modal-title' ) ).trim() === 'Заказать обратный звонок' );
	check( 'Подсказка в поле «Телефон»: +1 000 000 00 00', ( await page.getAttribute( '#callback-phone', 'placeholder' ) ) === '+1 000 000 00 00' );
	check( 'Пример номера в тексте ошибки', ( await page.getAttribute( '#callback-phone', 'data-error' ) ) === 'Проверьте номер телефона, например +1 000 000 00 00' );
	check( 'Страница под окном не прокручивается', await page.evaluate( () => document.documentElement.classList.contains( 'has-modal' ) && getComputedStyle( document.documentElement ).overflow === 'hidden' ) );
	await page.click( '.callback-modal__close' );
	check( 'Крестик закрывает окно', ! ( await isOpen() ) && ! ( await page.evaluate( () => document.documentElement.classList.contains( 'has-modal' ) ) ) );

	await page.click( '.header-callback' );
	await page.click( '#callback-modal-text' );
	check( 'Щелчок внутри окна не закрывает', await isOpen() );
	await page.mouse.click( 20, 780 );
	check( 'Щелчок по фону закрывает', ! ( await isOpen() ) );

	// --- Фокус не уходит из окна ---
	await page.click( '.header-callback' );
	let outside = 0;
	for ( let i = 0; i < 12; i++ ) {
		await page.keyboard.press( 'Tab' );
		const inside = await page.evaluate( () => document.getElementById( 'callback-modal' ).contains( document.activeElement ) || document.activeElement === document.body );
		outside += inside ? 0 : 1;
	}
	check( 'Tab не выводит фокус из окна', outside === 0, `вне окна: ${ outside }` );

	// --- Проверка полей ---
	await page.keyboard.press( 'Escape' );
	await page.click( '.header-callback' );
	await page.click( '.form__submit' );
	const shownErrors = await page.locator( '#callback-modal .field__error:visible' ).allTextContents();
	check( 'Пустая форма: 3 ошибки', shownErrors.length === 3, shownErrors.join( ' | ' ) );
	check( 'Пустая форма: фокус в поле «Имя», aria-invalid', ( await active() ) === 'callback-name' && ( await page.getAttribute( '#callback-name', 'aria-invalid' ) ) === 'true' );

	await page.fill( '#callback-name', 'Анна' );
	check( 'Ошибка имени снимается при вводе', await page.locator( '#callback-name-error' ).isHidden() );
	await page.fill( '#callback-phone', '123' );
	await page.click( '.form__submit' );
	check( 'Короткий телефон — ошибка', ( await page.locator( '#callback-phone-error' ).isVisible() ) && ( await active() ) === 'callback-phone' );
	await page.fill( '#callback-phone', '+7 (900) 123-45-67' );
	await page.click( '.form__submit' );
	check( 'Без согласия — ошибка согласия', ( await page.locator( '#callback-consent-error' ).isVisible() ) && ( await page.locator( '#callback-phone-error' ).isHidden() ) );
	await page.check( '#callback-modal .check__input' );
	check( 'Ошибка согласия снимается при отметке', await page.locator( '#callback-consent-error' ).isHidden() );

	// --- Ссылки ---
	check( 'Ссылка на согласие', ( await page.getAttribute( '#callback-modal .check a', 'href' ) ) === `${ URL }/soglasie-na-obrabotku-dannyh/` );
	check( 'Ссылка на политику конфиденциальности', ( await page.getAttribute( '#callback-modal .form__note a', 'href' ) ) === `${ URL }/politika-konfidencialnosti/` );
	await page.screenshot( { path: path.join( OUT, 'callback-1280.png' ) } );

	// --- Отправка ---
	await page.click( '.form__submit' );
	check( 'Пока заявка уходит, кнопка недоступна', await page.locator( '#callback-modal .form__submit' ).isDisabled() );
	await page.waitForSelector( '#callback-modal .callback-modal__success:not([hidden])', { timeout: 15000 } );
	check( 'Верные данные: «Заявка отправлена», фокус на «Закрыть»', ( await page.textContent( '#callback-modal .form-success__title' ) ).trim() === 'Заявка отправлена' && await page.evaluate( () => document.activeElement.matches( '.callback-modal__success [data-callback-close]' ) ) );
	await page.screenshot( { path: path.join( OUT, 'callback-sent-1280.png' ) } );
	const lead = wp( 'post', 'list', '--post_type=lead', '--post_status=any', '--meta_key=trust_name', '--meta_value=Анна', '--field=ID', '--orderby=ID', '--order=DESC', '--posts_per_page=1' );
	check( 'Заявка сохранена: «Обратный звонок: Анна»', !! lead && wp( 'post', 'get', lead, '--field=post_title' ) === 'Обратный звонок: Анна' );
	await page.click( '#callback-modal .callback-modal__success [data-callback-close]' );
	await page.click( '.header-callback' );
	check( 'Повторное открытие: пустая форма', ( await page.locator( '#callback-modal .callback-modal__form-view' ).isVisible() ) && ( await page.inputValue( '#callback-name' ) ) === '' && ! ( await page.isChecked( '#callback-modal .check__input' ) ) );

	// --- Доступность (окно открыто) ---
	// Пауза: переходы цвета кнопок (0,2 с) после повторного открытия — иначе axe иногда
	// меряет контраст на середине перехода.
	await page.mouse.move( 0, 0 );
	await page.waitForTimeout( 500 );
	await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
	const axe = await page.evaluate( async () => {
		const r = await window.axe.run( document, { resultTypes: [ 'violations' ] } );
		return r.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length }: ${ v.nodes.map( ( n ) => n.target.join( ' ' ) ).join( '; ' ) })` );
	} );
	check( 'axe с открытым окном: нет critical/serious', axe.length === 0, axe.join( ', ' ) );

	// --- Тексты из настроек ---
	const saved = getSettings( [ 'callback_title', 'callback_button' ] );
	try {
		setSettings( { callback_title: 'Перезвоним за 15 минут', callback_button: 'Позвоните мне' } );
		await page.goto( `${ URL }/kontakty/`, { waitUntil: 'networkidle' } );
		const header = ( await page.textContent( '.header-callback' ) ).trim();
		const footer = ( await page.textContent( '.site-footer__callback' ) ).trim();
		check( 'Текст кнопок из настроек (шапка и подвал)', header === 'Позвоните мне' && footer === 'Позвоните мне', `${ header } / ${ footer }` );
		check( 'Заголовок окна из настроек', ( await page.textContent( '#callback-modal-title' ) ).trim() === 'Перезвоним за 15 минут' );
	} finally {
		setSettings( { callback_title: saved.callback_title || '', callback_button: saved.callback_button || '' } );
	}

	// --- Ширины ---
	for ( const w of [ 1280, 768, 390, 320, 280 ] ) {
		await page.setViewportSize( { width: w, height: 700 } );
		await page.goto( `${ URL }/kontakty/`, { waitUntil: 'networkidle' } );
		await page.evaluate( () => document.querySelector( '[data-trust-callback]' ).click() );
		await page.click( '.form__submit' );
		const res = await page.evaluate( () => {
			const d = document.getElementById( 'callback-modal' );
			const r = d.getBoundingClientRect();
			const small = [ ...d.querySelectorAll( 'button, input[type="checkbox"]' ) ]
				.filter( ( el ) => el.offsetParent !== null )
				.map( ( el ) => {
					const box = ( el.type === 'checkbox' ? el.closest( 'label' ) : el ).getBoundingClientRect();
					return { t: el.textContent.trim() || el.getAttribute( 'aria-label' ) || el.type, h: Math.round( box.height ), w: Math.round( box.width ) };
				} )
				.filter( ( x ) => x.h < 44 || x.w < 44 );
			return { fits: r.left >= 0 && r.right <= innerWidth, scroll: document.documentElement.scrollWidth > innerWidth, small };
		} );
		check( `Ширина ${ w }px: окно в экране, без прокрутки вбок, зоны ≥ 44 px`, res.fits && ! res.scroll && res.small.length === 0, JSON.stringify( res ) );
		if ( w === 390 ) {
			await page.screenshot( { path: path.join( OUT, 'callback-390.png' ) } );
		}
	}

	await browser.close();

	// Заявки, созданные тестом.
	const before = new Set( leadsBefore.split( ' ' ).filter( Boolean ) );
	wp( 'post', 'list', '--post_type=lead', '--post_status=any', '--field=ID', '--format=ids' ).split( ' ' ).filter( ( id ) => id && ! before.has( id ) ).forEach( ( id ) => wp( 'post', 'delete', id, '--force' ) );
	resetFormLimits();

	check( 'Консоль браузера без ошибок', errors.length === 0, errors.slice( 0, 3 ).join( ' | ' ) );
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
