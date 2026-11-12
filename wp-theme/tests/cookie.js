/**
 * Тест плашки cookie и согласия (шаг 3.7) на локальном стенде с демо-контентом.
 *
 * Запуск: node wp-theme/tests/cookie.js
 *
 * Браузер запускается без заранее сделанного выбора (в остальных тестах выбор «только
 * обязательные» ставит launch() из lib.js). На время теста в настройки подставляется код
 * счётчика (встроенный скрипт) и пикселя (внешний скрипт https://counter.example/pixel.js,
 * ответ подменяется), потом прежние значения возвращаются.
 *
 * Проверяет:
 * - первый визит: плашка внизу, тексты и ссылка на политику из настроек, фокус не уводится,
 *   код счётчиков не выполняется и внешний скрипт не запрашивается;
 * - «Принять»: код аналитики и пикселей выполняется сразу, без перезагрузки; cookie trust_consent
 *   (категории, вариант, дата, версия, срок); видимой подсказки после выбора нет (правка заказчика),
 *   скринридер слышит «Все cookie приняты»; при следующем визите код выполняется сразу, плашки нет;
 * - «Изменить» → «Принять только обязательные»: страница перезагружается, код не выполняется,
 *   cookie счётчиков удалены; «Отклонить всё» — без перезагрузки и без кода;
 * - ссылка #cookie-settings открывает плашку; window.trustConsent и событие trust:consent;
 * - новая версия согласия и срок хранения из настроек; без JavaScript плашки нет;
 * - ширины 1280–280 px, зоны нажатия, клавиатура, axe-core; совпадение плашки
 *   с макетом seo-bloki/17-cookie.html на 1280 и 390 px.
 */

const { chromium } = require( 'playwright' );
const path = require( 'path' );
const { URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, getSettings, setSettings, routeMockupFonts, mockupUrl, comparePng, settle } = require( './lib' );

const SETTINGS = [ 'cookie_title', 'cookie_text', 'cookie_days', 'cookie_version', 'cookie_code_analytics', 'cookie_code_marketing', 'cookie_policy' ];
const ANALYTICS = '<script>window.__analytics = ( window.__analytics || 0 ) + 1;</script><div id="analytics-marker" hidden></div>';
const MARKETING = '<script src="https://counter.example/pixel.js" async></script>';
const PAGE = `${ URL }/seo/audit/`;

const banner = ( page ) => page.locator( '.cookie-banner' );
const status = ( page ) => page.locator( '[data-trust-cookie-saved]' );
const consent = async ( page ) => {
	const cookie = ( await page.context().cookies( URL ) ).find( ( c ) => 'trust_consent' === c.name );
	return cookie ? { ...JSON.parse( decodeURIComponent( cookie.value ) ), expires: cookie.expires } : null;
};
const days = ( c ) => Math.round( ( c.expires * 1000 - Date.now() ) / 86400000 );

( async () => {
	clearLog();
	const saved = getSettings( SETTINGS );
	const browser = await chromium.launch();
	const pixelRequests = [];

	/** Новый посетитель: свой контекст без cookie, внешний пиксель подменён. */
	async function visitor( options = {} ) {
		const context = await browser.newContext( { viewport: { width: 1280, height: 800 }, ...options } );
		await context.route( 'https://counter.example/**', ( route ) => {
			pixelRequests.push( route.request().url() );
			route.fulfill( { contentType: 'application/javascript', body: 'window.__marketing = ( window.__marketing || 0 ) + 1;' } );
		} );
		const page = await context.newPage();
		return { context, page, errors: collectConsoleErrors( page ) };
	}

	try {
		setSettings( { cookie_title: '', cookie_text: '', cookie_days: '', cookie_version: '', cookie_code_analytics: ANALYTICS, cookie_code_marketing: MARKETING } );

		// --- Первый визит ---
		let v = await visitor();
		let page = v.page;
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		check( 'Первый визит: плашка видна, сообщения о выборе нет', await banner( page ).isVisible() && '' === await status( page ).textContent() );
		check( 'Плашка: role=dialog, заголовок и текст из макета', ( await banner( page ).getAttribute( 'role' ) ) === 'dialog' &&
			( await page.textContent( '#cookie-banner-title' ) ).trim() === 'Мы используем файлы cookie' &&
			( await page.textContent( '#cookie-banner-text' ) ).trim() === 'Обязательные cookie нужны для работы сайта, аналитические помогают понять, какие страницы полезны посетителям. Подробнее — в политике использования cookie.' );
		check( 'Ссылка на политику cookie из настроек', ( await page.getAttribute( '#cookie-banner-text a', 'href' ) ) === `${ URL }/politika-cookie/` && ( await page.textContent( '#cookie-banner-text a' ) ) === 'политике использования cookie' );
		const buttons = ( await banner( page ).locator( 'button' ).allTextContents() ).map( ( s ) => s.trim() );
		check( 'Кнопки: Принять, Принять только обязательные, Отклонить всё', JSON.stringify( buttons ) === JSON.stringify( [ 'Принять', 'Принять только обязательные', 'Отклонить всё' ] ) );
		check( 'Фокус не уводится на плашку', await page.evaluate( () => document.activeElement === document.body ) );
		check( 'До согласия код счётчиков не выполняется, пиксель не запрашивается', await page.evaluate( () => ! window.__analytics && ! window.__marketing && ! document.getElementById( 'analytics-marker' ) ) && pixelRequests.length === 0 );
		check( 'Код лежит в <template> до согласия', ( await page.locator( 'template[data-trust-consent="analytics"]' ).count() ) === 1 && ( await page.locator( 'template[data-trust-consent="marketing"]' ).count() ) === 1 );
		await page.keyboard.press( 'Tab' );
		let tabs = 0;
		while ( tabs < 80 && ! ( await page.evaluate( () => document.activeElement.closest( '.cookie-banner' ) !== null ) ) ) {
			await page.keyboard.press( 'Tab' );
			tabs++;
		}
		check( 'Кнопки плашки доступны с клавиатуры (Tab)', tabs < 80 );
		await page.screenshot( { path: path.join( OUT, 'cookie-banner-1280.png' ) } );

		// --- «Принять» ---
		await banner( page ).locator( 'button', { hasText: /^Принять$/ } ).click();
		await page.waitForFunction( () => window.__marketing === 1 );
		let c = await consent( page );
		check( '«Принять»: код аналитики и пикселя выполнен сразу, без перезагрузки', await page.evaluate( () => window.__analytics === 1 && window.__marketing === 1 && !! document.getElementById( 'analytics-marker' ) ) && pixelRequests.length === 1 );
		check( 'Cookie trust_consent: все категории, вариант «all», дата, версия 1, срок 365 дней', c?.necessary === true && c.analytics === true && c.marketing === true && c.choice === 'all' && c.v === '1' && !! Date.parse( c.date ) && days( c ) === 365, JSON.stringify( c ) );
		const after = await page.evaluate( () => {
			const el = document.querySelector( '[data-trust-cookie-saved]' );
			const r = el.getBoundingClientRect();
			return { role: el.getAttribute( 'role' ), text: el.textContent, size: r.width * r.height, focus: document.activeElement === document.body, visible: [ ...document.querySelectorAll( 'body *' ) ].some( ( n ) => /Изменить/.test( n.textContent ) && n.offsetParent && 'BUTTON' === n.tagName ) };
		} );
		check( 'Плашка скрыта, видимой подсказки «Изменить» нет (правка заказчика)', await banner( page ).isHidden() && ! after.visible && after.size <= 1, JSON.stringify( after ) );
		check( 'Скринридер слышит «Все cookie приняты» (role=status), фокус не остался на скрытой кнопке', after.role === 'status' && after.text === 'Все cookie приняты' && after.focus, JSON.stringify( after ) );
		await page.screenshot( { path: path.join( OUT, 'cookie-saved-1280.png' ) } );
		await page.waitForTimeout( 5500 );
		check( 'Сообщение для скринридера очищается через 5 секунд', '' === await status( page ).textContent() );

		await page.reload( { waitUntil: 'networkidle' } );
		check( 'Следующий визит: плашки нет, код выполнен сразу один раз', await banner( page ).isHidden() && await page.evaluate( () => window.__analytics === 1 && window.__marketing === 1 ) );

		// --- «Изменить» → «Только обязательные»: перезагрузка, cookie счётчиков удалены ---
		await page.evaluate( () => {
			document.cookie = '_ym_uid=123; path=/';
			document.cookie = '_ga=GA1.1.1; path=/';
		} );
		await page.evaluate( () => {
			const a = document.createElement( 'a' );
			a.href = '#cookie-settings';
			a.id = 'test-cookie-link';
			a.textContent = 'Настройки cookie';
			document.querySelector( 'main' ).prepend( a );
		} );
		await page.click( '#test-cookie-link' );
		check( 'Ссылка #cookie-settings открывает плашку, фокус на текущем выборе «Принять»', await banner( page ).isVisible() && await page.evaluate( () => document.activeElement.dataset.trustConsentChoice === 'all' ) && ! page.url().includes( '#cookie-settings' ) );
		await Promise.all( [ page.waitForEvent( 'load' ), banner( page ).locator( 'button', { hasText: 'Принять только обязательные' } ).click() ] );
		await page.waitForLoadState( 'networkidle' );
		c = await consent( page );
		const trackers = await page.evaluate( () => document.cookie );
		check( 'Отзыв согласия: страница перезагружена, код не выполняется', await page.evaluate( () => ! window.__analytics && ! window.__marketing ) && await banner( page ).isHidden() );
		check( 'Отзыв согласия: выбор «necessary», аналитика и маркетинг выключены', c?.choice === 'necessary' && c.analytics === false && c.marketing === false );
		check( 'Отзыв согласия: cookie счётчиков (_ym_uid, _ga) удалены', ! /_ym_uid|_ga=/.test( trackers ), trackers );
		check( 'Консоль без ошибок', v.errors.length === 0, v.errors.slice( 0, 3 ).join( ' | ' ) );
		await v.context.close();

		// --- «Отклонить всё»: без перезагрузки и без кода; API ---
		v = await visitor();
		page = v.page;
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await page.evaluate( () => {
			window.__events = [];
			window.__loaded = true;
			window.addEventListener( 'trust:consent', ( e ) => window.__events.push( e.detail.choice ) );
		} );
		await banner( page ).locator( 'button', { hasText: 'Отклонить всё' } ).click();
		c = await consent( page );
		check( '«Отклонить всё»: без перезагрузки, без кода, выбор «none»', await page.evaluate( () => window.__loaded === true && ! window.__analytics ) && c?.choice === 'none' && c.analytics === false && ( await status( page ).textContent() ).includes( 'Необязательные cookie отклонены' ) );
		const api = await page.evaluate( () => {
			const before = window.trustConsent.has( 'analytics' );
			window.trustConsent.set( { analytics: true }, 'custom' );
			return { before, after: window.trustConsent.has( 'analytics' ), marketing: window.trustConsent.has( 'marketing' ), necessary: window.trustConsent.has( 'necessary' ), analytics: window.__analytics, pixel: window.__marketing, events: window.__events, get: window.trustConsent.get().choice };
		} );
		check( 'window.trustConsent: set() включает только выбранную категорию, событие trust:consent', ! api.before && api.after && ! api.marketing && api.necessary && api.analytics === 1 && ! api.pixel && api.get === 'custom' && JSON.stringify( api.events ) === '["none","custom"]', JSON.stringify( api ) );
		await v.context.close();

		// --- Версия согласия и срок из настроек ---
		v = await visitor();
		page = v.page;
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await banner( page ).locator( 'button', { hasText: /^Принять$/ } ).click();
		setSettings( { cookie_version: '2', cookie_days: '30' } );
		await page.reload( { waitUntil: 'networkidle' } );
		check( 'Новая версия согласия: плашка снова, код не выполняется', await banner( page ).isVisible() && await page.evaluate( () => ! window.__analytics ) );
		await banner( page ).locator( 'button', { hasText: /^Принять$/ } ).click();
		c = await consent( page );
		check( 'Версия 2 и срок 30 дней из настроек', c?.v === '2' && days( c ) === 30, JSON.stringify( c ) );
		await v.context.close();

		// --- Тексты из настроек ---
		setSettings( { cookie_version: '', cookie_days: '', cookie_title: 'Про cookie', cookie_text: 'Считаем визиты. Читайте {правила} сайта.' } );
		v = await visitor();
		page = v.page;
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		check( 'Заголовок и текст из настроек, {…} — ссылка на политику', ( await page.textContent( '#cookie-banner-title' ) ).trim() === 'Про cookie' &&
			( await page.innerHTML( '#cookie-banner-text' ) ).trim() === `Считаем визиты. Читайте <a href="${ URL }/politika-cookie/">правила</a> сайта.` );
		setSettings( { cookie_policy: '' } );
		await page.reload( { waitUntil: 'networkidle' } );
		check( 'Без страницы политики — текст без ссылки', ( await page.innerHTML( '#cookie-banner-text' ) ).trim() === 'Считаем визиты. Читайте правила сайта.' );
		setSettings( { cookie_title: '', cookie_text: '', cookie_policy: saved.cookie_policy || '' } );
		await v.context.close();

		// --- Без JavaScript ---
		v = await visitor( { javaScriptEnabled: false } );
		await v.page.goto( PAGE );
		check( 'Без JavaScript плашки нет', await banner( v.page ).isHidden() );
		await v.context.close();

		// --- Ширины, зоны нажатия, axe ---
		v = await visitor();
		page = v.page;
		for ( const w of [ 1280, 768, 640, 390, 320, 280 ] ) {
			await page.setViewportSize( { width: w, height: 800 } );
			await page.goto( PAGE, { waitUntil: 'networkidle' } );
			const res = await page.evaluate( () => {
				const b = document.querySelector( '.cookie-banner' ).getBoundingClientRect();
				const btns = [ ...document.querySelectorAll( '.cookie-banner button' ) ].map( ( el ) => el.getBoundingClientRect() );
				return {
					inside: b.left >= 0 && b.right <= innerWidth && b.bottom <= innerHeight,
					scroll: document.documentElement.scrollWidth > innerWidth,
					small: btns.filter( ( r ) => r.height < 44 ).length,
					full: btns.every( ( r ) => Math.abs( r.width - btns[ 0 ].width ) < 1 ),
				};
			} );
			const fullOk = w > 640 || res.full;
			check( `Ширина ${ w }px: плашка в экране, без прокрутки вбок, кнопки ≥ 44 px${ w <= 640 ? ', на всю ширину' : '' }`, res.inside && ! res.scroll && res.small === 0 && fullOk, JSON.stringify( res ) );
			if ( 390 === w ) {
				await page.screenshot( { path: path.join( OUT, 'cookie-banner-390.png' ) } );
			}
		}
		await page.setViewportSize( { width: 1280, height: 800 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
		let axe = await page.evaluate( async () => {
			const r = await window.axe.run( document.querySelector( '.cookie-banner' ), { resultTypes: [ 'violations' ] } );
			return r.violations.filter( ( x ) => [ 'critical', 'serious' ].includes( x.impact ) ).map( ( x ) => `${ x.id } (${ x.nodes.length })` );
		} );
		check( 'axe: плашка без critical/serious', axe.length === 0, axe.join( ', ' ) );
		await banner( page ).locator( 'button', { hasText: 'Принять только обязательные' } ).click();
		check( '«Принять только обязательные»: плашка скрыта, скринридер слышит выбор', await banner( page ).isHidden() && 'Приняты только обязательные cookie' === await status( page ).textContent() );
		check( 'Консоль без ошибок', v.errors.length === 0, v.errors.slice( 0, 3 ).join( ' | ' ) );
		await v.context.close();

		// --- Сверка с макетом ---
		const mockContext = await browser.newContext();
		await routeMockupFonts( mockContext );
		const mock = await mockContext.newPage();
		v = await visitor();
		page = v.page;
		// Позади плашки — одинаковый фон: в макете демо-страница, в теме — сайт.
		const backdrop = ( p, keep ) => p.evaluate( ( sel ) => {
			document.documentElement.style.background = '#F8F5FF';
			document.body.style.background = '#F8F5FF';
			document.querySelectorAll( 'body *' ).forEach( ( el ) => {
				if ( ! el.closest( sel ) && ! el.querySelector( sel ) ) {
					el.style.setProperty( 'visibility', 'hidden', 'important' );
				}
			} );
			document.querySelectorAll( '[aria-hidden="true"]' ).forEach( ( el ) => {
				if ( ! el.closest( sel ) ) {
					el.style.setProperty( 'visibility', 'hidden', 'important' );
				}
			} );
		}, keep );
		for ( const w of [ 1280, 390 ] ) {
			await page.setViewportSize( { width: w, height: 800 } );
			await mock.setViewportSize( { width: w, height: 800 } );
			for ( const state of [ 'banner' ] ) {
				await v.context.clearCookies();
				await page.goto( PAGE, { waitUntil: 'networkidle' } );
				await mock.goto( mockupUrl( 'seo-bloki/17-cookie.html' ), { waitUntil: 'networkidle' } );
				await settle( page );
				await settle( mock );
				const themeSel = '.cookie-banner';
				const mockSel = '[role="dialog"]';
				await backdrop( page, themeSel );
				await backdrop( mock, mockSel );
				await page.waitForTimeout( 200 );
				const name = `cookie-${ state }-${ w }`;
				await page.locator( themeSel ).screenshot( { path: path.join( OUT, `${ name }-theme.png` ) } );
				await mock.locator( mockSel ).screenshot( { path: path.join( OUT, `${ name }-mockup.png` ) } );
				const diff = comparePng( path.join( OUT, `${ name }-mockup.png` ), path.join( OUT, `${ name }-theme.png` ), path.join( OUT, `${ name }-diff.png` ) );
				check( `Макет ${ w }px, плашка: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
			}
		}
		await mockContext.close();
		await v.context.close();
	} finally {
		setSettings( Object.fromEntries( SETTINGS.map( ( k ) => [ k, saved[ k ] ?? '' ] ) ) );
		await browser.close();
	}

	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
