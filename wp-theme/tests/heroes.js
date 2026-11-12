/**
 * Тест первых экранов (шаг 4.1): «Первый экран услуги» и «Первый экран раздела».
 *
 * Запуск: node wp-theme/tests/heroes.js (стенд запущен, демо-контент импортирован).
 *
 * Проверяет:
 * - /seo/audit/ и другие услуги: крошки, один H1 из SEO-поля или заголовка, вводный текст,
 *   кнопки (основная и контурная), #callback-modal открывает окно звонка, стандартной шапки
 *   страницы нет;
 * - Портфолио, О компании, Команда, Карьера: крошки над карточкой, плашка, H1, текст,
 *   4 цифры (значение над подписью), {vacancies} — число открытых вакансий, кнопки «Карьеры»
 *   (белая со стрелкой вниз и прозрачная);
 * - пустые поля не выводятся (своя тестовая страница); второй такой блок вставить нельзя;
 * - ширины 1280–280 px без прокрутки вбок, зоны нажатия, axe-core, консоль и журнал ошибок;
 * - совпадение с макетами seo-stranitsa/index.html, portfolio-blog-404/portfolio.html,
 *   o-nas/o-kompanii.html, o-nas/komanda.html, o-nas/karera.html на 1280, 768 и 390 px;
 * - редактор: блоки в категории «Первые экраны», превью с крошками и H1 страницы;
 * - демо-импорт: страница, сохранённая в редакторе без правок, обновляется; с ручной правкой —
 *   пропускается, а с --force=<адрес> — обновляется.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, wp, clearLog, checkLog, login, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle } = require( './lib' );

const MARK = 'ТестПервыйЭкран';

/**
 * Прямоугольник первого экрана: в теме — блок, в макете — от крошек до конца секции.
 */
async function heroBox( page, theme ) {
	return page.evaluate( ( isTheme ) => {
		const rect = ( el ) => el.getBoundingClientRect();
		let top;
		let bottom;
		if ( isTheme ) {
			const el = document.querySelector( '.b-hero-service, .b-hero-section' );
			top = rect( el ).top;
			bottom = rect( el ).bottom;
		} else {
			const main = document.querySelector( 'main' );
			const section = main.querySelector( 'section' );
			top = rect( main.firstElementChild ).top;
			bottom = rect( section ).bottom;
		}
		return { x: 0, y: Math.round( top + scrollY ), width: innerWidth, height: Math.round( bottom - top ) };
	}, theme );
}

/** Скрывает шапку сайта (у темы и макета разная высота строки меню не важна для сверки). */
async function hideHeader( page ) {
	await page.evaluate( () => {
		document.querySelectorAll( 'header.site-header, body > header, .dc-root > header, #dc-root header:first-of-type, .skip-link' ).forEach( ( el ) => {
			if ( ! el.closest( 'main' ) ) {
				el.style.setProperty( 'display', 'none', 'important' );
			}
		} );
		window.scrollTo( 0, 0 );
	} );
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
		// --- Первый экран услуги ---
		await page.goto( `${ URL }/seo/audit/`, { waitUntil: 'networkidle' } );
		const service = page.locator( '.b-hero-service' );
		const crumbs = ( await service.locator( '.breadcrumbs__item' ).allTextContents() ).map( ( s ) => s.trim() );
		check( 'Услуга: крошки «Главная / SEO / Технический аудит» внутри первого экрана', JSON.stringify( crumbs ) === '["Главная","SEO","Технический аудит"]', crumbs.join( ' / ' ) );
		check( 'Услуга: один H1 «Технический аудит сайта», связан с секцией', ( await page.locator( 'h1' ).count() ) === 1 &&
			( await service.locator( 'h1' ).textContent() ) === 'Технический аудит сайта' &&
			( await service.getAttribute( 'aria-labelledby' ) ) === ( await service.locator( 'h1' ).getAttribute( 'id' ) ) );
		check( 'Услуга: стандартной шапки страницы нет', ( await page.locator( '.page-default' ).count() ) === 0 );
		check( 'Услуга: вводный текст', ( await service.locator( '.b-hero-service__lead' ).textContent() ).startsWith( 'Найдём ошибки, которые мешают сайту расти' ) );
		const buttons = await service.locator( '.b-hero-service__buttons a' ).evaluateAll( ( els ) => els.map( ( a ) => ( { text: a.textContent.trim(), href: a.getAttribute( 'href' ), cls: a.className } ) ) );
		check( 'Услуга: кнопки «Заказать аудит» (основная) и «Рассчитать стоимость» (контурная)', buttons.length === 2 &&
			buttons[ 0 ].text === 'Заказать аудит' && buttons[ 0 ].href === '#callback-modal' && buttons[ 0 ].cls === 'btn' &&
			buttons[ 1 ].text === 'Рассчитать стоимость' && buttons[ 1 ].cls === 'btn btn--outline', JSON.stringify( buttons ) );
		await service.locator( 'a', { hasText: 'Заказать аудит' } ).click();
		check( 'Ссылка #callback-modal открывает окно «Заказать обратный звонок»', await page.evaluate( () => document.getElementById( 'callback-modal' ).open ) && ! page.url().includes( '#callback-modal' ) );
		await page.keyboard.press( 'Escape' );

		await page.goto( `${ URL }/dizayn/`, { waitUntil: 'networkidle' } );
		check( 'Другие услуги: первый экран с H1 страницы и кнопкой «Оставить заявку»', ( await page.locator( '.b-hero-service h1' ).textContent() ) === 'Дизайн' &&
			( await page.locator( '.b-hero-service__buttons a' ).allTextContents() ).map( ( s ) => s.trim() ).join() === 'Оставить заявку' );

		// --- Первые экраны разделов ---
		const vacancies = Number( wp( 'eval', 'echo Trust\\Vacancies::open_count();' ) );
		const sections = [
			{ url: '/portfolio/', crumbs: 'Главная / Портфолио', badge: 'Портфолио', h1: 'Кейсы и проекты', stat: 'завершённых проектов: [N]+' },
			{ url: '/o-kompanii/', crumbs: 'Главная / О компании', badge: 'О нас', h1: 'О компании', stat: 'работаем на рынке: с [ГГГГ]' },
			{ url: '/komanda/', crumbs: 'Главная / О нас / Наша команда', badge: 'О нас', h1: 'Наша команда', stat: 'специалистов в штате: [N]' },
			{ url: '/karera/', crumbs: 'Главная / О нас / Карьера', badge: 'Карьера', h1: 'Работа в [Бренд]', stat: 'человек в команде: [N]' },
		];
		for ( const s of sections ) {
			await page.goto( URL + s.url, { waitUntil: 'networkidle' } );
			const hero = page.locator( '.b-hero-section' );
			const got = {
				crumbs: ( await hero.locator( '.b-hero-section__crumbs .breadcrumbs__item' ).allTextContents() ).map( ( t ) => t.trim() ).join( ' / ' ),
				badge: ( await hero.locator( '.b-hero-section__badge' ).textContent() ).trim(),
				h1: await hero.locator( 'h1' ).textContent(),
				h1s: await page.locator( 'h1' ).count(),
				stats: await hero.locator( '.b-hero-section__stat' ).evaluateAll( ( els ) => els.map( ( el ) => `${ el.querySelector( 'dt' ).textContent }: ${ el.querySelector( 'dd' ).textContent }` ) ),
				valueAbove: await hero.locator( '.b-hero-section__stat' ).first().evaluate( ( el ) => el.querySelector( 'dd' ).getBoundingClientRect().top < el.querySelector( 'dt' ).getBoundingClientRect().top ),
				defaultHeader: await page.locator( '.page-default' ).count(),
			};
			check( `${ s.url }: крошки, плашка «${ s.badge }», H1 «${ s.h1 }», 4 цифры, значение над подписью`, got.crumbs === s.crumbs && got.badge === s.badge && got.h1 === s.h1 && got.h1s === 1 &&
				got.stats.length === 4 && got.stats[ 0 ] === s.stat && got.valueAbove && got.defaultHeader === 0, JSON.stringify( got ) );
		}
		const career = await page.locator( '.b-hero-section' ).evaluate( ( el ) => ( {
			vac: [ ...el.querySelectorAll( '.b-hero-section__stat' ) ][ 1 ].querySelector( 'dd' ).textContent,
			buttons: [ ...el.querySelectorAll( '.b-hero-section__buttons a' ) ].map( ( a ) => ( { text: a.textContent.trim(), href: a.getAttribute( 'href' ), icon: !! a.querySelector( 'svg' ), white: a.classList.contains( 'btn--white' ) } ) ),
		} ) );
		check( `Карьера: {vacancies} → число открытых вакансий (${ vacancies })`, career.vac === String( vacancies ) && vacancies > 0, career.vac );
		check( 'Карьера: «Смотреть вакансии» (белая, стрелка вниз) и «Отправить резюме»', JSON.stringify( career.buttons ) === JSON.stringify( [
			{ text: 'Смотреть вакансии', href: '#vacancies', icon: true, white: true },
			{ text: 'Отправить резюме', href: '#resume', icon: false, white: false },
		] ), JSON.stringify( career.buttons ) );

		// --- Пустые поля, посадочная страница ---
		const content = wp( 'eval', `echo Trust\\Blocks::serialize( 'hero-section', array( 'badge' => '' ) );` );
		const landing = wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK } посадочная`, '--post_status=publish', `--post_content=${ content }`, '--porcelain' );
		created.push( landing );
		wp( 'post', 'meta', 'update', landing, '_wp_page_template', 'templates/landing.php' );
		await page.goto( `${ URL }/?page_id=${ landing }`, { waitUntil: 'networkidle' } );
		const empty = await page.locator( '.b-hero-section' ).first().evaluate( ( el ) => ( {
			badge: el.querySelectorAll( '.b-hero-section__badge' ).length,
			lead: el.querySelectorAll( '.b-hero-section__lead' ).length,
			buttons: el.querySelectorAll( '.b-hero-section__buttons' ).length,
			stats: el.querySelectorAll( 'dl' ).length,
			h1: el.querySelector( 'h1' ).textContent,
		} ) );
		check( 'Пустые поля не выводятся: только крошки и H1 из заголовка', empty.badge + empty.lead + empty.buttons + empty.stats === 0 && empty.h1 === `${ MARK } посадочная`, JSON.stringify( empty ) );

		// --- Ширины ---
		for ( const url of [ '/seo/audit/', '/karera/' ] ) {
			for ( const w of [ 1280, 1024, 768, 640, 390, 320, 280 ] ) {
				await page.setViewportSize( { width: w, height: 800 } );
				await page.goto( URL + url, { waitUntil: 'networkidle' } );
				const res = await page.evaluate( () => {
					const hero = document.querySelector( '.b-hero-service, .b-hero-section' );
					const r = hero.getBoundingClientRect();
					const small = [ ...hero.querySelectorAll( 'a, button' ) ].filter( ( el ) => el.getBoundingClientRect().height < 44 ).map( ( el ) => el.textContent.trim() );
					return { inside: r.left >= 0 && r.right <= innerWidth, scroll: document.documentElement.scrollWidth > innerWidth, small };
				} );
				check( `${ url } ${ w }px: без прокрутки вбок, зоны нажатия ≥ 44 px`, res.inside && ! res.scroll && res.small.length === 0, JSON.stringify( res ) );
			}
		}

		// --- Доступность ---
		await page.setViewportSize( { width: 1280, height: 900 } );
		for ( const url of [ '/seo/audit/', '/karera/' ] ) {
			await page.goto( URL + url, { waitUntil: 'networkidle' } );
			await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
			const axe = await page.evaluate( async () => {
				const r = await window.axe.run( document.querySelector( '.b-hero-service, .b-hero-section' ), { resultTypes: [ 'violations' ] } );
				return r.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length })` );
			} );
			check( `axe ${ url }: первый экран без critical/serious`, axe.length === 0, axe.join( ', ' ) );
		}

		// --- Сверка с макетами ---
		const compares = [
			{ url: '/seo/audit/', mock: 'seo-stranitsa/index.html', name: 'service' },
			{ url: '/portfolio/', mock: 'portfolio-blog-404/portfolio.html', name: 'portfolio' },
			{ url: '/o-kompanii/', mock: 'o-nas/o-kompanii.html', name: 'about' },
			{ url: '/komanda/', mock: 'o-nas/komanda.html', name: 'team' },
			{ url: '/karera/', mock: 'o-nas/karera.html', name: 'career' },
		];
		for ( const c of compares ) {
			for ( const w of [ 1280, 768, 390 ] ) {
				await page.setViewportSize( { width: w, height: 900 } );
				const base = path.join( OUT, `hero-${ c.name }-${ w }` );
				await page.goto( URL + c.url, { waitUntil: 'networkidle' } );
				await settle( page );
				await hideHeader( page );
				await page.screenshot( { path: `${ base }-theme.png`, clip: await heroBox( page, true ), fullPage: true } );
				await page.goto( mockupUrl( c.mock ), { waitUntil: 'networkidle' } );
				await settle( page );
				// Число открытых вакансий в макете — из его списка; на стенде — из демо-вакансий.
				if ( 'career' === c.name ) {
					await page.evaluate( ( n ) => {
						const dd = [ ...document.querySelectorAll( 'main dl dd' ) ][ 1 ];
						dd.textContent = String( n );
					}, vacancies );
				}
				await hideHeader( page );
				await page.screenshot( { path: `${ base }-mockup.png`, clip: await heroBox( page, false ), fullPage: true } );
				const diff = comparePng( `${ base }-mockup.png`, `${ base }-theme.png`, `${ base }-diff.png` );
				check( `Макет ${ c.mock }, ${ w }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
			}
		}

		// --- Редактор ---
		await login( page );
		await page.setViewportSize( { width: 1440, height: 1000 } );
		await page.goto( `${ URL }/wp-admin/post.php?post=${ landing }&action=edit` );
		await page.waitForFunction( () => window.wp && wp.blocks && wp.blocks.getBlockType( 'trust/hero-section' ), null, { timeout: 30000 } );
		const types = await page.evaluate( () => [ 'trust/hero-service', 'trust/hero-section' ].map( ( n ) => {
			const t = wp.blocks.getBlockType( n );
			return { category: t.category, multiple: t.supports.multiple };
		} ) );
		check( 'Редактор: оба блока в категории «Первые экраны», второй такой блок вставить нельзя', types.every( ( t ) => 'trust-heroes' === t.category && false === t.multiple ), JSON.stringify( types ) );
		const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-hero-section' ).first().waitFor( { timeout: 30000 } );
		const preview = await canvas.locator( '.b-hero-section' ).first().evaluate( ( el ) => ( {
			crumbs: [ ...el.querySelectorAll( '.breadcrumbs__item' ) ].map( ( li ) => li.textContent.trim() ).join( ' / ' ),
			h1: el.querySelector( 'h1' ).textContent,
			links: el.querySelectorAll( '.breadcrumbs a' ).length,
		} ) );
		check( 'Превью в редакторе: крошки без ссылок и H1 — заголовок страницы', preview.crumbs === `Главная / ${ MARK } посадочная` && preview.h1 === `${ MARK } посадочная` && 0 === preview.links, JSON.stringify( preview ) );
		await page.screenshot( { path: path.join( OUT, 'hero-editor.png' ) } );

		// --- Демо-импорт после сохранения страницы в редакторе ---
		// Открыли «Карьеру» и нажали «Обновить» без правок: SCF записывает пустые SEO-поля —
		// это не ручная правка, импорт продолжает обновлять страницу.
		const careerId = wp( 'eval', 'echo get_page_by_path( "karera" )->ID;' );
		await page.goto( `${ URL }/wp-admin/post.php?post=${ careerId }&action=edit` );
		await page.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/editor' ).getCurrentPostId(), null, { timeout: 30000 } );
		await page.waitForTimeout( 2000 );
		await page.evaluate( () => wp.data.dispatch( 'core/editor' ).savePost() );
		await page.waitForFunction( () => ! wp.data.select( 'core/editor' ).isSavingPost(), null, { timeout: 30000 } );
		await page.waitForTimeout( 1000 );
		let log = wp( 'trust', 'demo', 'import' );
		check( 'Страница сохранена в редакторе без правок — демо-импорт её обновляет', ! /Пропущены[^\n]*karera/.test( log ), log.split( '\n' ).slice( 0, 2 ).join( ' | ' ) );
		const original = wp( 'post', 'get', careerId, '--field=post_content' );
		wp( 'post', 'update', careerId, `--post_content=${ original }<!-- wp:paragraph --><p>Ручная правка</p><!-- /wp:paragraph -->` );
		let skipped = false;
		try {
			log = wp( 'trust', 'demo', 'import' );
			skipped = /Пропущены[^\n]*karera/.test( log );
		} finally {
			log = wp( 'trust', 'demo', 'import', '--force=karera' );
		}
		check( 'Ручная правка: импорт пропускает страницу; --force=karera возвращает демо-контент', skipped && /Обновлены принудительно[^\n]*karera/.test( log ) && ! wp( 'post', 'get', careerId, '--field=post_content' ).includes( 'Ручная правка' ), log.split( '\n' ).slice( 0, 3 ).join( ' | ' ) );
	} finally {
		created.forEach( ( id ) => wp( 'post', 'delete', id, '--force' ) );
		await browser.close();
	}

	check( 'Консоль браузера без ошибок', errors.filter( ( e ) => ! e.includes( 'wp-admin' ) ).length === 0, errors.slice( 0, 3 ).join( ' | ' ) );
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
