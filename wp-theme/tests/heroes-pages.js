/**
 * Тест первых экранов шага 4.2: «Контакты», «Отзывы», «Документ», «Вопросы SEO».
 *
 * Запуск: node wp-theme/tests/heroes-pages.js (стенд запущен, демо-контент импортирован).
 *
 * Проверяет:
 * - контакты: каналы из «Настроек сайта → Контакты» (ссылки tel:, mailto:, t.me, wa.me),
 *   подписи по умолчанию и свои, порядок, пропуск незаполненного канала;
 * - отзывы: площадки, оценка со звёздами по оценке, ссылка в новой вкладке, без ссылки — без стрелки;
 * - документ: даты редакции, «Распечатать» (только с JavaScript), «Настроить cookie» открывает
 *   выбор cookie, «Скачать PDF» с загруженным файлом, вид при печати, дата редакции согласия
 *   уходит в заявки;
 * - вопросы: раскладка (H1 над колонками, с 1000 px форма справа), проверка полей и подсказки,
 *   счётчик символов, отправка — вопрос «На утверждении» с полями и заявка со ссылкой на него,
 *   «Вопрос отправлен» с адресом, «Задать ещё вопрос», заголовок вопроса из первых слов;
 * - ширины 1280–280 px, зоны нажатия, axe-core, консоль и журнал ошибок;
 * - совпадение с макетами o-nas/kontakty.html, otzyvy.html, oferta.html, politika-cookie.html,
 *   seo-vopros/vopros-seo.html на 1280, 768 и 390 px (с согласованными правками).
 */

const fs = require( 'fs' );
const path = require( 'path' );
const { launch, URL, OUT, check, finish, wp, clearLog, checkLog, login, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, getSettings, setSettings, resetFormLimits } = require( './lib' );

const MARK = 'ТестПервыйЭкран42';
const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );

/** Прямоугольник первого экрана: от крошек до конца карточки. */
async function heroBox( page, theme ) {
	return page.evaluate( ( isTheme ) => {
		const rect = ( el ) => el.getBoundingClientRect();
		const top = isTheme ? document.querySelector( '[class^="b-hero-"]' ) : document.querySelector( 'main' ).firstElementChild;
		const bottom = isTheme ? top : document.querySelector( 'main section' );
		return { x: 0, y: Math.round( rect( top ).top + scrollY ), width: innerWidth, height: Math.round( rect( bottom ).bottom - rect( top ).top ) };
	}, theme );
}

/** Скрывает шапку сайта и всё лишнее (у макета и темы разная шапка). */
async function hideHeader( page ) {
	await page.evaluate( () => {
		document.querySelectorAll( 'header.site-header, body > header, #dc-root header:first-of-type, .skip-link' ).forEach( ( el ) => {
			if ( ! el.closest( 'main' ) ) {
				el.style.setProperty( 'display', 'none', 'important' );
			}
		} );
		window.scrollTo( 0, 0 );
	} );
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
	const savedTelegram = getSettings( [ 'telegram' ] ).telegram;
	const questionsBefore = wp( 'post', 'list', '--post_type=question', '--post_status=any', '--format=ids' ).split( ' ' ).filter( Boolean );
	const leadsBefore = wp( 'post', 'list', '--post_type=lead', '--post_status=any', '--format=ids' ).split( ' ' ).filter( Boolean );
	const testPage = ( title, block, values, template ) => {
		const content = wp( 'eval', `echo Trust\\Blocks::serialize( '${ block }', json_decode( base64_decode( '${ Buffer.from( JSON.stringify( values ) ).toString( 'base64' ) }' ), true ) );` );
		const id = wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK } ${ title }`, '--post_status=publish', `--post_content=${ content }`, '--porcelain' );
		created.push( id );
		if ( template ) {
			wp( 'post', 'meta', 'update', id, '_wp_page_template', template );
		}
		return id;
	};

	try {
		// --- Контакты ---
		await page.goto( `${ URL }/kontakty/`, { waitUntil: 'networkidle' } );
		const contacts = await page.locator( '.b-hero-contacts' ).evaluate( ( el ) => ( {
			crumbs: [ ...el.querySelectorAll( '.breadcrumbs__item' ) ].map( ( li ) => li.textContent.trim() ).join( ' / ' ),
			badge: el.querySelector( '.hero-badge' ).textContent.trim(),
			h1: el.querySelector( 'h1' ).textContent,
			cards: [ ...el.querySelectorAll( '.b-hero-contacts__card' ) ].map( ( a ) => ( {
				label: a.querySelector( '.b-hero-contacts__label' ).textContent,
				value: a.querySelector( '.b-hero-contacts__value' ).textContent,
				note: a.querySelector( '.b-hero-contacts__note' )?.textContent,
				href: a.getAttribute( 'href' ),
				blank: a.target === '_blank',
			} ) ),
		} ) );
		check( 'Контакты: крошки, плашка, один H1', contacts.crumbs === 'Главная / О нас / Контакты' && contacts.badge === 'О нас' && contacts.h1 === 'Контакты' && ( await page.locator( 'h1' ).count() ) === 1 && ( await page.locator( '.page-default' ).count() ) === 0 );
		check( 'Контакты: 4 карточки из настроек — телефон с часами, email, Telegram и WhatsApp в новой вкладке', JSON.stringify( contacts.cards ) === JSON.stringify( [
			{ label: 'Телефон', value: '+7 (000) 000-00-00', note: '[Пн–Пт, 10:00–19:00]', href: 'tel:+70000000000', blank: false },
			{ label: 'Email', value: 'hello@example.ru', note: 'Для вопросов и заявок', href: 'mailto:hello@example.ru', blank: false },
			{ label: 'Telegram', value: '@[brand]', note: 'Написать в Telegram', href: 'https://t.me/%5Bbrand%5D', blank: true },
			{ label: 'WhatsApp', value: '+7 (000) 000-00-00', note: 'Написать в WhatsApp', href: 'https://wa.me/70000000000', blank: true },
		] ), JSON.stringify( contacts.cards ) );
		const own = testPage( 'контакты', 'hero-contacts', { badge: '', lead: '', channels: [ { channel: 'whatsapp', label: 'Ватсап', note: 'Ответим за 5 минут' }, { channel: 'telegram', label: '', note: '' }, { channel: 'email', label: '', note: '' } ] }, 'templates/contacts.php' );
		setSettings( { telegram: '' } );
		await page.goto( `${ URL }/?page_id=${ own }`, { waitUntil: 'networkidle' } );
		const ownCards = await page.locator( '.b-hero-contacts__card' ).evaluateAll( ( els ) => els.map( ( a ) => `${ a.querySelector( '.b-hero-contacts__label' ).textContent }|${ a.querySelector( '.b-hero-contacts__note' ).textContent }` ) );
		check( 'Контакты: свои порядок и подписи; Telegram не заполнен в настройках — карточки нет; без плашки и текста', JSON.stringify( ownCards ) === JSON.stringify( [ 'Ватсап|Ответим за 5 минут', 'Email|Для вопросов и заявок' ] ) && ( await page.locator( '.b-hero-contacts .hero-badge, .b-hero-contacts .hero-lead' ).count() ) === 0, JSON.stringify( ownCards ) );
		setSettings( { telegram: savedTelegram } );

		// --- Отзывы ---
		await page.goto( `${ URL }/otzyvy/`, { waitUntil: 'networkidle' } );
		const reviews = await page.locator( '.b-hero-reviews__card' ).evaluateAll( ( els ) => els.map( ( a ) => ( {
			name: a.querySelector( '.b-hero-reviews__name' ).textContent,
			rating: a.querySelector( '.b-hero-reviews__value' ).textContent,
			stars: a.querySelectorAll( '.b-hero-reviews__stars svg:not(.is-empty)' ).length,
			count: a.querySelector( '.b-hero-reviews__count' ).textContent.trim(),
			href: a.getAttribute( 'href' ),
			blank: a.target === '_blank',
		} ) ) );
		check( 'Отзывы: 4 площадки — название, оценка «из 5» для скринридера, 5 звёзд, число отзывов, ссылка в новой вкладке', reviews.length === 4 &&
			reviews[ 0 ].name === 'Яндекс Карты' && reviews[ 0 ].rating === '[X,X] из 5' && reviews[ 0 ].stars === 5 && reviews[ 0 ].count === '[N] отзывов' && reviews[ 0 ].href === 'https://yandex.ru/maps/' && reviews[ 0 ].blank &&
			reviews.map( ( r ) => r.name ).join() === 'Яндекс Карты,2ГИС,Google Карты,Workspace', JSON.stringify( reviews[ 0 ] ) );
		const rated = testPage( 'отзывы', 'hero-reviews', { badge: 'О нас', lead: '', platforms: [ { name: 'Площадка', rating: '4,4', count: '12 отзывов', url: '' } ] }, 'templates/reviews.php' );
		await page.goto( `${ URL }/?page_id=${ rated }`, { waitUntil: 'networkidle' } );
		const card = await page.locator( '.b-hero-reviews__card' ).evaluate( ( el ) => ( { tag: el.tagName, filled: el.querySelectorAll( '.b-hero-reviews__stars svg:not(.is-empty)' ).length, empty: el.querySelectorAll( '.is-empty' ).length, arrow: el.querySelectorAll( '.b-hero-reviews__count svg' ).length } ) );
		check( 'Отзывы: оценка 4,4 — 4 звезды из 5; без ссылки — карточка без стрелки', card.tag === 'DIV' && card.filled === 4 && card.empty === 1 && card.arrow === 0, JSON.stringify( card ) );

		// --- Документ ---
		await page.goto( `${ URL }/oferta/`, { waitUntil: 'networkidle' } );
		const doc = await page.locator( '.b-hero-document' ).evaluate( ( el ) => ( {
			crumbs: [ ...el.querySelectorAll( '.breadcrumbs__item' ) ].map( ( li ) => li.textContent.trim() ).join( ' / ' ),
			badge: el.querySelector( '.hero-badge' ).textContent,
			h1: el.querySelector( 'h1' ).textContent,
			dates: el.querySelector( '.b-hero-document__dates' ).textContent,
			buttons: [ ...el.querySelectorAll( '.b-hero-document__buttons a, .b-hero-document__buttons button' ) ].filter( ( b ) => ! b.hidden ).map( ( b ) => b.textContent.trim() ),
		} ) );
		check( 'Документ: крошки, плашка, H1 из SEO-поля, даты «Редакция от … · действует с …»', doc.crumbs === 'Главная / О нас / Публичная оферта' && doc.badge === 'Документы' && doc.h1 === 'Публичная оферта на оказание услуг' && doc.dates === 'Редакция от 01.11.2026 · действует с 01.11.2026', JSON.stringify( doc ) );
		check( 'Документ без PDF: только «Распечатать»', JSON.stringify( doc.buttons ) === '["Распечатать"]', JSON.stringify( doc.buttons ) );
		await page.evaluate( () => {
			window.__printed = 0;
			window.print = () => window.__printed++;
		} );
		await page.click( '[data-trust-print]' );
		check( '«Распечатать» вызывает печать', await page.evaluate( () => window.__printed === 1 ) );
		await page.emulateMedia( { media: 'print' } );
		const print = await page.evaluate( () => ( {
			crumbs: getComputedStyle( document.querySelector( '.b-hero-document .hero-crumbs' ) ).display,
			buttons: getComputedStyle( document.querySelector( '.b-hero-document__buttons' ) ).display,
			badge: getComputedStyle( document.querySelector( '.b-hero-document .hero-badge' ) ).display,
			bg: getComputedStyle( document.querySelector( '.b-hero-document .hero-box' ) ).backgroundColor,
			color: getComputedStyle( document.querySelector( '.b-hero-document h1' ) ).color,
		} ) );
		check( 'Печать: без крошек, плашки и кнопок; карточка белая, текст тёмный', print.crumbs === 'none' && print.buttons === 'none' && print.badge === 'none' && print.bg === 'rgb(255, 255, 255)' && print.color === 'rgb(14, 11, 20)', JSON.stringify( print ) );
		await page.emulateMedia( { media: 'screen' } );
		const noJs = await browser.newContext( { javaScriptEnabled: false } );
		const noJsPage = await noJs.newPage();
		await noJsPage.goto( `${ URL }/oferta/` );
		check( 'Без JavaScript кнопки «Распечатать» нет', await noJsPage.locator( '[data-trust-print]' ).isHidden() );
		await noJs.close();

		await page.goto( `${ URL }/politika-cookie/`, { waitUntil: 'networkidle' } );
		check( 'Политика cookie: «Настроить cookie» (белая) и «Распечатать»; PDF без файла не показывается', ( await page.locator( '.b-hero-document__buttons > :not([hidden])' ).allTextContents() ).map( ( t ) => t.trim() ).join( '|' ) === 'Настроить cookie|Распечатать' );
		await page.click( '.b-hero-document a[href="#cookie-settings"]' );
		check( '«Настроить cookie» открывает выбор cookie', await page.locator( '.cookie-banner' ).isVisible() );
		await page.keyboard.press( 'Escape' );
		await page.reload();

		const pdfFile = path.join( OUT, 'hero-test.pdf' );
		fs.writeFileSync( pdfFile, '%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[]/Count 0>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n' );
		const pdf = wp( 'media', 'import', pdfFile, '--porcelain' );
		created.push( pdf );
		const pdfPage = testPage( 'документ', 'hero-document', { badge: 'Документы', revision: '20260315', effective: '', pdf, pdf_label: '', print: 0, cookie: 0 }, 'templates/document.php' );
		await page.goto( `${ URL }/?page_id=${ pdfPage }`, { waitUntil: 'networkidle' } );
		const pdfLink = await page.locator( '.b-hero-document__buttons a' ).evaluate( ( a ) => ( { text: a.textContent.trim(), href: a.getAttribute( 'href' ), download: a.hasAttribute( 'download' ) } ) );
		check( 'Документ с PDF: «Скачать PDF» на файл; без «Распечатать»; только «Редакция от»', pdfLink.text === 'Скачать PDF' && /hero-test.*\.pdf$/.test( pdfLink.href ) && pdfLink.download &&
			( await page.locator( '[data-trust-print]' ).count() ) === 0 && ( await page.textContent( '.b-hero-document__dates' ) ) === 'Редакция от 15.03.2026', JSON.stringify( pdfLink ) );
		check( 'Дата редакции согласия для заявок — из первого экрана страницы согласия', wp( 'eval', 'echo Trust\\Forms::consent_revision();' ) === '2026-11-01' );

		// --- Вопросы: раскладка ---
		await page.goto( `${ URL }/seo/voprosy/`, { waitUntil: 'networkidle' } );
		const layout = async () => page.evaluate( () => {
			const r = ( s ) => document.querySelector( `.b-hero-questions ${ s }` ).getBoundingClientRect();
			return { h1: r( 'h1' ), lead: r( '.b-hero-questions__lead' ), card: r( '.b-hero-questions__card' ), extra: r( '.b-hero-questions__extra' ) };
		} );
		let l = await layout();
		check( 'Вопросы 1280px: H1 над колонками, форма справа от текста, пункты под текстом', l.h1.width > l.lead.width + 100 && l.card.left > l.lead.right && Math.abs( l.card.top - l.lead.top ) < 2 && l.extra.top > l.lead.bottom && l.extra.right < l.card.left, JSON.stringify( { h1: l.h1.width, lead: [ l.lead.left, l.lead.right ], card: [ l.card.left, l.card.top ] } ) );
		check( 'Вопросы: крошки «Главная / SEO / Вопросы по SEO», плашка «Бесплатно», 3 пункта, ссылка на ответы', ( await page.locator( '.b-hero-questions .breadcrumbs__item' ).allTextContents() ).map( ( t ) => t.trim() ).join( ' / ' ) === 'Главная / SEO / Вопросы по SEO' &&
			( await page.textContent( '.b-hero-questions__badge' ) ).trim() === 'Бесплатно' && ( await page.locator( '.b-hero-questions__points li' ).count() ) === 3 && ( await page.getAttribute( '.b-hero-questions__link', 'href' ) ) === '#answers' );
		await page.setViewportSize( { width: 390, height: 900 } );
		l = await layout();
		check( 'Вопросы 390px: плашка, H1, текст, форма, пункты — по порядку', l.h1.bottom <= l.lead.top && l.lead.bottom <= l.card.top && l.card.bottom <= l.extra.top );
		await page.setViewportSize( { width: 1280, height: 900 } );

		// --- Вопросы: форма ---
		await page.goto( `${ URL }/seo/voprosy/`, { waitUntil: 'networkidle' } );
		const form = page.locator( '#form-question' );
		await form.locator( '.form__submit' ).click();
		const errs = await form.locator( '.field__error:visible' ).allTextContents();
		const notesHidden = await form.locator( '[name="name"] ~ .field__note' ).isHidden();
		check( 'Пустая форма: 4 ошибки (имя, email, вопрос, согласие), подсказки под полями с ошибкой скрыты', errs.length === 4 && errs[ 0 ] === 'Укажите, как к вам обращаться' && errs[ 1 ] === 'Проверьте email, например name@example.ru' && errs[ 2 ] === 'Опишите вопрос подробнее — хотя бы пару предложений' && notesHidden, errs.join( ' | ' ) );
		await form.locator( '[name="name"]' ).fill( 'Анна' );
		await form.locator( '[name="email"]' ).fill( 'anna@example.com' );
		await form.locator( 'textarea' ).fill( 'Коротко' );
		check( 'Счётчик символов: 7 / 2000', ( await form.locator( '.b-hero-questions__count' ).textContent() ).replace( /\s+/g, ' ' ).trim() === '7 / 2000' );
		await form.locator( '.form__submit' ).click();
		check( 'Вопрос короче 10 знаков — ошибка', await form.locator( 'textarea' ).getAttribute( 'aria-invalid' ) === 'true' && await form.locator( '[name="name"] ~ .field__note' ).isVisible() );
		const text = 'После переезда на новый домен трафик из Яндекса упал вдвое, а Google почти не изменился. С чего начать поиск причины и что проверить в первую очередь?';
		await form.locator( 'textarea' ).fill( text );
		await form.locator( '[name="consent"]' ).check();
		await form.locator( '.form__submit' ).click();
		await page.waitForSelector( '.b-hero-questions__sent:not([hidden])', { timeout: 15000 } );
		const sent = await page.locator( '.b-hero-questions__sent' ).evaluate( ( el ) => ( { title: el.querySelector( 'h2' ).textContent, email: el.querySelector( '[data-trust-sent-email]' ).textContent, focus: document.activeElement.matches( '[data-form-again]' ) } ) );
		check( '«Вопрос отправлен»: адрес в тексте, фокус на «Задать ещё вопрос»', sent.title === 'Вопрос отправлен' && sent.email === 'anna@example.com' && sent.focus && await form.isHidden(), JSON.stringify( sent ) );
		const question = wp( 'post', 'list', '--post_type=question', '--post_status=pending', '--orderby=ID', '--order=DESC', '--posts_per_page=1', '--field=ID' );
		const q = JSON.parse( wp( 'eval', `$id = ${ question }; echo wp_json_encode( array( get_the_title( $id ), get_post_status( $id ), get_field( 'trust_asker', $id ), get_field( 'trust_asker_email', $id ), get_field( 'trust_question', $id, false ), get_post_meta( $id, 'trust_asked_at', true ) ) );` ) );
		check( 'Создан вопрос «На утверждении»: заголовок из первых слов, имя, email, полный текст, дата', ! questionsBefore.includes( question ) && q[ 0 ] === 'После переезда на новый домен трафик из Яндекса упал вдвое, а Google…' && q[ 1 ] === 'pending' && q[ 2 ] === 'Анна' && q[ 3 ] === 'anna@example.com' && q[ 4 ] === text && q[ 5 ] === wp( 'eval', 'echo current_time( "Ymd" );' ), JSON.stringify( q ) );
		const lead = wp( 'post', 'list', '--post_type=lead', '--post_status=any', '--orderby=ID', '--order=DESC', '--posts_per_page=1', '--field=ID' );
		check( 'Создана заявка «Вопрос SEO-специалисту» со ссылкой на вопрос', ! leadsBefore.includes( lead ) && wp( 'post', 'meta', 'get', lead, 'trust_form' ) === 'question' && wp( 'post', 'meta', 'get', lead, 'trust_related' ) === question && wp( 'post', 'meta', 'get', lead, 'trust_contact' ) === 'anna@example.com' );
		await page.click( '[data-form-again]' );
		check( '«Задать ещё вопрос»: пустая форма, счётчик 0', await form.isVisible() && ( await form.locator( 'textarea' ).inputValue() ) === '' && ( await form.locator( '[data-trust-count]' ).textContent() ) === '0' );
		check( 'Заголовок вопроса: короткий текст — целиком, без многоточия', wp( 'eval', 'echo Trust\\Questions::title_from( "Как выбрать CMS?" );' ) === 'Как выбрать CMS?' );

		// Экран заявки — ссылка на вопрос.
		await login( page );
		await page.goto( `${ URL }/wp-admin/post.php?post=${ lead }&action=edit` );
		const relatedRow = ( await page.locator( '#trust-lead-box tr', { hasText: 'Создана запись' } ).textContent() ).replace( /\s+/g, ' ' );
		check( 'Экран заявки: «Создана запись» — вопрос, на утверждении, ссылка на редактирование', relatedRow.includes( 'Вопрос' ) && /(Pending|На утверждении)/.test( relatedRow ) && ( await page.getAttribute( '#trust-lead-box tr:has-text("Создана запись") a', 'href' ) ).includes( `post=${ question }` ), relatedRow );
		await page.context().clearCookies();
		await page.context().addCookies( [ require( './lib' ).consentCookie() ] );

		// --- Ширины, зоны нажатия, axe ---
		for ( const url of [ '/kontakty/', '/otzyvy/', '/politika-cookie/', '/seo/voprosy/' ] ) {
			const bad = [];
			for ( const w of [ 1280, 1024, 768, 640, 390, 320, 280 ] ) {
				await page.setViewportSize( { width: w, height: 800 } );
				await page.goto( URL + url, { waitUntil: 'networkidle' } );
				const res = await page.evaluate( () => {
					const hero = document.querySelector( '[class^="b-hero-"]' );
					// Ссылки внутри предложений (согласие, политика) — исключение WCAG 2.5.8.
					const small = [ ...hero.querySelectorAll( 'a, button, input:not([type="hidden"]):not([tabindex="-1"])' ) ]
						.filter( ( el ) => el.offsetParent !== null && ! el.closest( '.check span, .form__note' ) )
						.map( ( el ) => ( { el, r: ( el.type === 'checkbox' ? el.closest( 'label' ) : el ).getBoundingClientRect() } ) )
						.filter( ( x ) => x.r.height < 44 ).map( ( x ) => x.el.textContent.trim() || x.el.name );
					return { scroll: document.documentElement.scrollWidth > innerWidth, small };
				} );
				if ( res.scroll || res.small.length ) {
					bad.push( `${ w }: ${ JSON.stringify( res ) }` );
				}
			}
			check( `${ url }: 1280–280 px без прокрутки вбок, зоны нажатия ≥ 44 px`, bad.length === 0, bad.join( '; ' ) );
			await page.setViewportSize( { width: 1280, height: 900 } );
			await page.goto( URL + url, { waitUntil: 'networkidle' } );
			await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
			const axe = await page.evaluate( async () => {
				const r = await window.axe.run( document.querySelector( '[class^="b-hero-"]' ), { resultTypes: [ 'violations' ] } );
				return r.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length })` );
			} );
			check( `axe ${ url }: без critical/serious`, axe.length === 0, axe.join( ', ' ) );
		}

		// --- Сверка с макетами ---
		const compares = [
			{ url: '/kontakty/', mock: 'o-nas/kontakty.html', name: 'contacts' },
			{ url: '/otzyvy/', mock: 'o-nas/otzyvy.html', name: 'reviews' },
			{ url: '/oferta/', mock: 'o-nas/oferta.html', name: 'offer' },
			{ url: '/politika-cookie/', mock: 'o-nas/politika-cookie.html', name: 'cookie-policy' },
			{ url: '/seo/voprosy/', mock: 'seo-vopros/vopros-seo.html', name: 'questions' },
		];
		for ( const c of compares ) {
			for ( const w of [ 1280, 768, 390 ] ) {
				await page.setViewportSize( { width: w, height: 900 } );
				const base = path.join( OUT, `hero-${ c.name }-${ w }` );
				await page.goto( URL + c.url, { waitUntil: 'networkidle' } );
				await settle( page );
				// Согласованные отличия формы вопроса: галочка согласия и строка о политике (в макете —
				// «Нажимая кнопку…» и макетная капча) — скрываются с обеих сторон.
				await page.evaluate( () => document.querySelectorAll( '.b-hero-questions .check, .b-hero-questions .form__note' ).forEach( ( el ) => el.closest( '.field, .form__note' ).style.setProperty( 'display', 'none', 'important' ) ) );
				const dates = await page.locator( '.b-hero-document__dates' ).textContent().catch( () => '' );
				await hideHeader( page );
				await page.screenshot( { path: `${ base }-theme.png`, clip: await heroBox( page, true ), fullPage: true } );
				await page.goto( mockupUrl( c.mock ), { waitUntil: 'networkidle' } );
				await settle( page );
				await page.evaluate( ( d ) => {
					// В демо — настоящие даты вместо «[ДД.ММ.ГГГГ]»; PDF в демо не загружен.
					const p = document.querySelector( '.dc-hero-in > div:first-child > p' );
					if ( d && p ) {
						p.textContent = d;
					}
					document.querySelectorAll( 'a[href$="-pdf"]' ).forEach( ( a ) => a.style.setProperty( 'display', 'none', 'important' ) );
					const captcha = document.querySelector( '.dc-captcha' );
					if ( captcha ) {
						captcha.parentElement.style.setProperty( 'display', 'none', 'important' );
						document.querySelector( '.dc-submit' ).nextElementSibling.style.setProperty( 'display', 'none', 'important' );
					}
				}, dates );
				await hideHeader( page );
				await page.screenshot( { path: `${ base }-mockup.png`, clip: await heroBox( page, false ), fullPage: true } );
				const diff = comparePng( `${ base }-mockup.png`, `${ base }-theme.png`, `${ base }-diff.png` );
				check( `Макет ${ c.mock }, ${ w }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
			}
		}
	} finally {
		setSettings( { telegram: savedTelegram } );
		created.forEach( ( id ) => wp( 'post', 'delete', id, '--force' ) );
		wp( 'post', 'list', '--post_type=question', '--post_status=any', '--format=ids' ).split( ' ' ).filter( ( id ) => id && ! questionsBefore.includes( id ) ).forEach( ( id ) => wp( 'post', 'delete', id, '--force' ) );
		wp( 'post', 'list', '--post_type=lead', '--post_status=any', '--format=ids' ).split( ' ' ).filter( ( id ) => id && ! leadsBefore.includes( id ) ).forEach( ( id ) => wp( 'post', 'delete', id, '--force' ) );
		resetFormLimits();
		await browser.close();
	}

	check( 'Консоль браузера без ошибок', errors.filter( ( e ) => ! e.includes( 'wp-admin' ) ).length === 0, errors.slice( 0, 3 ).join( ' | ' ) );
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
