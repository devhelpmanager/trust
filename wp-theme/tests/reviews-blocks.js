/**
 * Тест блоков шага 4.12 — страница «Отзывы о нас»: «Что говорят клиенты», «Видеоотзывы»,
 * «Благодарственные письма», «Оставьте отзыв».
 *
 * Запуск: node wp-theme/tests/reviews-blocks.js
 *
 * Демо на /otzyvy/ (порядок и тексты из макета o-nas/otzyvy.html): кнопки услуг с числом
 * отзывов, карточки (автор, оценка, услуга, текст, результаты, дата, «Оригинал письма» —
 * на весь экран, «Кейс проекта»), «Показать ещё» и фильтр через REST без перезагрузки (фокус
 * на первую новую карточку), без JavaScript — ?reviews=all; REST не отдаёт контакты автора;
 * видеоотзывы (плеер по нажатию, VideoObject при обложке), письма (скан крупно в окне), форма
 * отзыва (ошибки как в макете, оценка звёздами, отзыв «На утверждении» + заявка, «Написать ещё
 * один отзыв»). Сверка с макетом на 1280 / 768 / 390 px, ширины 1440–280, axe-core, консоль,
 * редактор.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp, resetFormLimits } = require( './lib' );

const PAGE = `${ URL }/otzyvy/`;
const MOCKUP = 'o-nas/otzyvy.html';
const MARK = 'ТестОтзывы';

/** PHP: блок по имени и полям (PHP-массив). */
const block = ( name, fields ) => `Trust\\Blocks::serialize( '${ name }', ${ fields } )`;

/**
 * Опубликованная тестовая страница «Отзывы» с блоками.
 *
 * @param {string} title  Заголовок.
 * @param {string} blocks PHP-выражение: разметка блоков.
 * @return {string} ID.
 */
function createPage( title, blocks ) {
	const content = wp( 'eval', `echo ${ blocks };` );
	const id = wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK } ${ title }`, '--post_status=publish', `--post_content=${ content }`, '--porcelain' );
	wp( 'post', 'meta', 'update', id, '_wp_page_template', 'templates/reviews.php' );
	return id;
}

/**
 * Демо-данные списка отзывов отличаются от макета (13 отзывов вместо 8, названия услуг как в
 * портфолио, даты — настоящие): перед снимком они приводятся к макету, сравнивается оформление.
 *
 * @param {import('playwright').Page} page Страница.
 */
async function normalizeList( page ) {
	await page.evaluate( () => {
		const chips = [ [ 'Все', 8 ], [ 'SEO-продвижение', 3 ], [ 'Создание сайтов', 2 ], [ 'Контекстная реклама', 2 ], [ 'Сопровождение', 1 ] ];
		const buttons = [ ...document.querySelectorAll( '.b-rlist .filter-chip' ) ];
		buttons.forEach( ( b, i ) => {
			if ( i >= chips.length ) {
				b.remove();
				return;
			}
			b.firstChild.textContent = chips[ i ][ 0 ];
			b.querySelector( '.filter-chip__count' ).textContent = chips[ i ][ 1 ];
		} );
		document.querySelectorAll( '.b-rlist__service' ).forEach( ( s ) => {
			s.textContent = { SEO: 'SEO-продвижение', 'Сайты': 'Создание сайтов' }[ s.textContent ] || s.textContent;
		} );
		document.querySelectorAll( '.b-rlist__date' ).forEach( ( d ) => {
			d.textContent = '[ДД.ММ.ГГГГ]';
		} );
		const more = document.querySelector( '[data-reviews-more-text]' );
		if ( more ) {
			more.textContent = 'Показать ещё 2';
		}
	} );
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
	const base = path.join( OUT, `reviews-${ name }-${ width }` );
	await page.setViewportSize( { width, height: 900 } );
	await page.goto( PAGE, { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	await normalizeList( page );
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
	resetFormLimits();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const created = [];

	try {
		// ===== Демо на «Отзывах» =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const order = await page.evaluate( () => [ ...document.querySelectorAll( '.page-entry__content > *' ) ].map( ( s ) => s.className.split( ' ' )[ 0 ] ) );
		check( 'Порядок блоков как в макете: первый экран, отзывы, видео, письма, форма, «Обсудим ваш проект»',
			JSON.stringify( order ) === JSON.stringify( [ 'b-hero-reviews', 'b-rlist', 'b-vrev', 'b-letters', 'b-rform', 'b-cta' ] ), order.join( ', ' ) );

		const counts = JSON.parse( wp( 'eval', 'echo wp_json_encode( Trust\\Reviews_List::counts() );' ) );
		const slugOf = ( name ) => Object.keys( counts.services ).find( ( slug ) => counts.services[ slug ].name === name );
		const [ ppcSlug, seoSlug ] = [ slugOf( 'Контекстная реклама' ), slugOf( 'SEO' ) ];
		const l = await page.locator( '.b-rlist' ).evaluate( ( el ) => ( {
			head: [ el.querySelector( '.eyebrow' ).textContent, el.querySelector( 'h2' ).textContent, el.getAttribute( 'aria-labelledby' ) === el.querySelector( 'h2' ).id ],
			leave: el.querySelector( '.b-rlist__leave' ).textContent.trim() + ' ' + el.querySelector( '.b-rlist__leave' ).getAttribute( 'href' ),
			chips: [ ...el.querySelectorAll( '.filter-chip' ) ].map( ( b ) => [ b.firstChild.textContent, Number( b.querySelector( '.filter-chip__count' ).textContent ), b.getAttribute( 'aria-pressed' ) ] ),
			cards: el.querySelectorAll( '.b-rlist__card' ).length,
			more: el.querySelector( '.b-rlist__more' ).textContent.trim(),
		} ) );
		check( '«Что говорят клиенты»: надзаголовок, H2, «Оставить отзыв» → #leave-review',
			'Отзывы клиентов|Что говорят клиенты|true' === l.head.join( '|' ) && 'Оставить отзыв #leave-review' === l.leave, JSON.stringify( l.head ) );
		check( 'Кнопки услуг с числом текстовых отзывов, «Все» первая и нажата, 6 карточек и «Показать ещё N»',
			'Все' === l.chips[ 0 ][ 0 ] && counts.total === l.chips[ 0 ][ 1 ] && 'true' === l.chips[ 0 ][ 2 ] && l.chips.length === Object.keys( counts.services ).length + 1
			&& l.chips.slice( 1 ).every( ( c ) => Object.values( counts.services ).some( ( s ) => s.name === c[ 0 ] && s.count === c[ 1 ] ) )
			&& 6 === l.cards && `Показать ещё ${ counts.total - 6 }` === l.more, JSON.stringify( [ l.chips, l.cards, l.more ] ) );

		const card = await page.locator( '.b-rlist__card' ).first().evaluate( ( c ) => ( {
			name: c.querySelector( '.b-rlist__name' ).textContent,
			role: c.querySelector( '.b-rlist__role' ).textContent,
			stars: c.querySelector( '.b-rlist__stars' ).getAttribute( 'aria-label' ) + ':' + c.querySelectorAll( '.b-rlist__stars .is-on' ).length,
			service: c.querySelector( '.b-rlist__service' ).textContent,
			text: c.querySelector( '.b-rlist__text' ).textContent.trim().startsWith( '[Отзыв о результате' ),
			results: [ ...c.querySelectorAll( '.b-rlist__results li' ) ].map( ( li ) => li.textContent ),
			date: /^\d\d\.\d\d\.\d{4}$/.test( c.querySelector( '.b-rlist__date' ).textContent ) && /^\d{4}-\d\d-\d\d$/.test( c.querySelector( '.b-rlist__date' ).getAttribute( 'datetime' ) ),
			letter: c.querySelector( '.b-rlist__link[data-trust-lightbox]' ) ? c.querySelector( '.b-rlist__link[data-trust-lightbox]' ).textContent.trim() : '',
		} ) );
		check( 'Карточка: имя, должность и компания, оценка «5 из 5», услуга, текст, 2 результата, дата <time>, «Оригинал письма» — скан на весь экран',
			'[Имя Фамилия]' === card.name && '[Должность], [Компания]' === card.role && 'Оценка: 5 из 5:5' === card.stars && 'SEO' === card.service && card.text
			&& JSON.stringify( card.results ) === JSON.stringify( [ '[+N% трафика]', '[за N месяцев]' ] ) && card.date && card.letter.startsWith( 'Оригинал письма' ), JSON.stringify( card ) );

		// «Показать ещё» — без перезагрузки, фокус на первую новую карточку.
		const url = page.url();
		await page.locator( '.b-rlist__more' ).click();
		await page.waitForFunction( () => document.querySelectorAll( '.b-rlist__card' ).length > 6 );
		const more1 = await page.evaluate( () => [ document.querySelectorAll( '.b-rlist__card' ).length, document.activeElement === document.querySelectorAll( '.b-rlist__card' )[ 6 ], document.querySelector( '.b-rlist__more' ).hidden, document.querySelector( '[data-reviews-more-text]' ).textContent, document.querySelector( '[data-reviews-status]' ).textContent ] );
		check( '«Показать ещё»: ещё 6 отзывов из REST без перезагрузки, фокус на первой новой карточке, счётчик кнопки и строка для скринридера обновились',
			12 === more1[ 0 ] && more1[ 1 ] && ( counts.total > 12 ? ! more1[ 2 ] && `Показать ещё ${ counts.total - 12 }` === more1[ 3 ] : more1[ 2 ] ) && more1[ 4 ] === `Показано отзывов: 12 из ${ counts.total }` && url === page.url(), JSON.stringify( more1 ) );
		const caseLinks = await page.locator( '.b-rlist__link', { hasText: 'Кейс проекта' } ).evaluateAll( ( a ) => a.map( ( x ) => new URL( x.href ).pathname ) );
		await page.locator( '.filter-chip', { hasText: 'Контекстная реклама' } ).click();
		await page.waitForFunction( () => document.querySelectorAll( '.b-rlist__card' ).length < 6 );
		const ppc = await page.evaluate( () => [ [ ...document.querySelectorAll( '.b-rlist__service' ) ].map( ( s ) => s.textContent ), document.querySelector( '.filter-chip[aria-pressed="true"]' ).firstChild.textContent, document.querySelector( '.b-rlist__more' ).hidden ] );
		check( 'Фильтр «Контекстная реклама»: только отзывы этой услуги, нажата одна кнопка, «Показать ещё» нет',
			ppc[ 0 ].length === counts.services[ ppcSlug ].count && ppc[ 0 ].every( ( s ) => 'Контекстная реклама' === s ) && 'Контекстная реклама' === ppc[ 1 ] && ppc[ 2 ], JSON.stringify( ppc ) );
		await page.locator( '.filter-chip', { hasText: 'Все' } ).click();
		await page.waitForFunction( () => 6 === document.querySelectorAll( '.b-rlist__card' ).length );
		check( '«Все» — снова первые 6 отзывов', 6 === await page.locator( '.b-rlist__card' ).count() );
		const geo = JSON.parse( wp( 'eval', '$r = Trust\\Reviews_List::query( "' + slugOf( 'GEO' ) + '", 0, 6 ); echo wp_json_encode( array( count( $r["ids"] ), Trust\\Reviews_List::card( $r["ids"][0] ) ) );' ) );
		check( '«Кейс проекта» — у отзыва, привязанного к кейсу (ссылка на кейс)',
			1 === geo[ 0 ] && /Кейс проекта/.test( geo[ 1 ] ) && /\/portfolio\//.test( geo[ 1 ] ), String( geo[ 0 ] ) + ' ' + JSON.stringify( caseLinks ) );

		// REST: параметры и приватные поля.
		const ok = await ( await fetch( `${ URL }/wp-json/trust/v1/reviews?service=${ seoSlug }&offset=0&per=2` ) ).json();
		const big = await fetch( `${ URL }/wp-json/trust/v1/reviews?per=100` );
		await big.text();
		const rest = [ ok.count, ok.total, big.status ];
		check( 'REST /trust/v1/reviews: страница по услуге (per, total), больше 24 за раз — ошибка 400', 2 === rest[ 0 ] && counts.services[ seoSlug ].count === rest[ 1 ] && 400 === rest[ 2 ], JSON.stringify( rest ) );
		const secret = wp( 'eval', `$id = wp_insert_post( array( 'post_type' => 'review', 'post_status' => 'publish', 'post_title' => '${ MARK }' ) ); update_post_meta( $id, 'trust_kind', 'text' ); update_post_meta( $id, 'trust_name', '${ MARK }' ); update_post_meta( $id, 'trust_text', 'Текст' ); update_post_meta( $id, 'trust_email', 'secret-mail@example.com' ); update_post_meta( $id, 'trust_phone', '+1 555 000 11 22' ); echo $id;` );
		created.push( secret );
		const leak = ( await ( await fetch( `${ URL }/wp-json/trust/v1/reviews?offset=0&per=1` ) ).json() ).html;
		check( 'REST и карточка не выводят email и телефон автора (служебные поля формы)', leak.includes( 'ТестОтзывы' ) && ! leak.includes( 'secret-mail' ) && ! leak.includes( '555 000' ) );
		wp( 'post', 'delete', secret, '--force' );
		created.splice( created.indexOf( secret ), 1 );

		// ===== Видеоотзывы =====
		const v = await page.locator( '.b-vrev' ).evaluate( ( el ) => ( {
			head: el.querySelector( '.eyebrow' ).textContent + '|' + el.querySelector( 'h2' ).textContent,
			cards: [ ...el.querySelectorAll( '.b-vrev__card' ) ].map( ( c ) => [ ( c.dataset.videoEmbed || '' ).replace( /\?.*/, '' ), /autoplay=1/.test( c.dataset.videoEmbed || '' ), c.querySelector( '.b-vrev__play' ).getAttribute( 'aria-label' ), c.querySelector( '.b-vrev__duration' ).textContent.replace( /\s+/g, ' ' ).trim(), c.querySelector( '.b-vrev__who' ).textContent ] ),
		} ) );
		check( '«Видеоотзывы»: 3 карточки — плеер youtube-nocookie с автозапуском после нажатия, «Смотреть видео: …», длительность, автор',
			'Видео|Видеоотзывы' === v.head && 3 === v.cards.length && v.cards.every( ( c ) => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ' === c[ 0 ] && c[ 1 ] && 'Смотреть видео: [Название видео: о чём рассказывает клиент]' === c[ 2 ] && 'Длительность: [м:сс]' === c[ 3 ] && '[Имя Фамилия], [Должность], [Компания]' === c[ 4 ] ),
			JSON.stringify( v.cards[ 0 ] ) );
		check( 'До нажатия на странице нет iframe видео (ни одного запроса к площадкам)', 0 === await page.locator( '.b-vrev iframe' ).count() );

		// ===== Письма =====
		const lt = await page.locator( '.b-letters' ).evaluate( ( el ) => ( {
			head: [ el.querySelector( '.eyebrow' ).textContent, el.querySelector( 'h2' ).textContent, el.querySelector( '.section-lead' ).textContent ],
			cards: [ ...el.querySelectorAll( '.b-letters__card' ) ].map( ( a ) => [ a.tagName, a.hasAttribute( 'data-trust-lightbox' ), /\.jpg$/.test( a.getAttribute( 'href' ) || '' ), a.querySelector( 'img' ).alt, a.querySelector( '.b-letters__company' ).textContent ] ),
		} ) );
		check( '«Благодарственные письма»: шапка с вводным текстом, 6 сканов — ссылка на скан (окно просмотра), alt «Благодарственное письмо — компания», название компании',
			'Документы|Благодарственные письма' === lt.head.slice( 0, 2 ).join( '|' ) && 6 === lt.cards.length
			&& lt.cards.every( ( c ) => 'A' === c[ 0 ] && c[ 1 ] && c[ 2 ] && 'Благодарственное письмо — [Компания]' === c[ 3 ] && '[Компания]' === c[ 4 ] ), JSON.stringify( lt.cards[ 0 ] ) );
		await page.locator( '.b-letters__card' ).first().click();
		await page.waitForTimeout( 300 );
		const box = await page.evaluate( () => {
			const d = document.querySelector( 'dialog.lightbox[open]' );
			return d ? [ d.querySelector( '.lightbox__caption' ).textContent, /\.jpg$/.test( d.querySelector( '.lightbox__img' ).src ) ] : null;
		} );
		await page.keyboard.press( 'Escape' );
		const focusBack = await page.evaluate( () => document.activeElement.classList.contains( 'b-letters__card' ) );
		check( 'Письмо открывается крупно в окне просмотра с подписью, Esc закрывает, фокус возвращается',
			box && 'Благодарственное письмо — [Компания]' === box[ 0 ] && box[ 1 ] && focusBack, JSON.stringify( [ box, focusBack ] ) );

		// ===== Форма отзыва =====
		resetFormLimits();
		const form = page.locator( '.b-rform__form' );
		const f = await page.locator( '.b-rform' ).evaluate( ( el ) => ( {
			id: el.id,
			head: el.querySelector( '.eyebrow' ).textContent + '|' + el.querySelector( 'h2' ).textContent,
			platforms: [ ...el.querySelectorAll( '.b-rform__platform' ) ].map( ( a ) => a.firstChild.textContent.trim() + ':' + a.target ),
			services: [ ...el.querySelectorAll( 'select[name="service"] option' ) ].map( ( o ) => o.textContent ),
			stars: el.querySelectorAll( 'input[name="rating"]' ).length,
			publish: new URL( el.querySelector( 'input[name="publish"]' ).closest( 'label' ).querySelector( 'a' ).href ).pathname,
		} ) );
		check( '«Оставьте отзыв»: якорь #leave-review, шапка, 4 площадки (новая вкладка), услуги из «Услуг» и «Другое», 5 звёзд, ссылка на согласие на распространение',
			'leave-review' === f.id && 'Обратная связь|Оставьте отзыв' === f.head && 4 === f.platforms.length && f.platforms.every( ( p ) => p.endsWith( ':_blank' ) )
			&& 'Выберите услугу' === f.services[ 0 ] && 'Другое' === f.services[ f.services.length - 1 ] && f.services.includes( 'SEO' ) && 5 === f.stars && '/soglasie-na-rasprostranenie-dannyh/' === f.publish,
			JSON.stringify( f ) );
		await form.locator( '[type="submit"]' ).click();
		const errs = await form.locator( '.field__error:not([hidden])' ).allTextContents();
		const focus = await page.evaluate( () => document.activeElement.name );
		check( 'Пустая форма: 7 сообщений как в макете, фокус на «Имя и фамилия»',
			JSON.stringify( errs ) === JSON.stringify( [ 'Укажите имя и фамилию', 'Выберите услугу', 'Оставьте телефон или email для подтверждения', 'Поставьте оценку от 1 до 5', 'Напишите хотя бы пару предложений — от 30 символов', 'Отметьте согласие, чтобы отправить отзыв', 'Без этого согласия мы не сможем опубликовать отзыв' ] ) && 'name' === focus,
			JSON.stringify( [ errs, focus ] ) );
		await form.locator( '[name="name"]' ).fill( `${ MARK } Анна` );
		await form.locator( '[name="company"]' ).fill( 'ООО Пример, директор' );
		await form.locator( '[name="service"]' ).selectOption( { label: 'Контекстная реклама' } );
		await form.locator( '[name="contact"]' ).fill( 'anna-review@example.com' );
		await form.locator( '.b-rform__star' ).nth( 3 ).click();
		const ratingError = await page.evaluate( () => [ document.querySelector( '.b-rform__rating .field__error' ).hidden, document.querySelector( 'input[name="rating"][data-error]' ).hasAttribute( 'aria-invalid' ) ] );
		check( 'Выбор звезды убирает сообщение «Поставьте оценку от 1 до 5»', JSON.stringify( ratingError ) === JSON.stringify( [ true, false ] ), JSON.stringify( ratingError ) );
		const rating = await page.evaluate( () => [ document.querySelector( '[data-rating-word]' ).textContent, [ ...document.querySelectorAll( '.b-rform__star svg' ) ].map( ( s ) => getComputedStyle( s ).fill ).filter( ( c ) => 'rgb(245, 179, 1)' === c ).length ] );
		check( 'Оценка: 4-я звезда — «Хорошо», закрашены 4 звезды', 'Хорошо' === rating[ 0 ] && 4 === rating[ 1 ], JSON.stringify( rating ) );
		await form.locator( '[name="text"]' ).fill( 'Отличная команда: сайт сделали в срок, реклама окупилась за первый месяц.' );
		await form.locator( '[name="consent"]' ).check();
		await form.locator( '[name="publish"]' ).check();
		const formTop = () => page.evaluate( () => Math.round( document.querySelector( '.b-rform__form' ).getBoundingClientRect().top + scrollY ) );
		const topBefore = await formTop();
		await form.locator( '[type="submit"]' ).click();
		const sent = await page.locator( '.b-rform .form-sent' ).waitFor( { state: 'visible', timeout: 20000 } ).then( () => true ).catch( () => false );
		check( 'После отправки — «Спасибо за отзыв!» и «Написать ещё один отзыв»', sent && 'Спасибо за отзыв!' === ( await page.locator( '.b-rform .form-success__title' ).textContent() ).replace( /\u00a0/g, ' ' ) && 'Написать ещё один отзыв' === ( await page.locator( '.b-rform .form-sent__again' ).textContent() ).trim() );
		// Строки заголовка: буквы, сгруппированные по вертикали.
		const titleLines = () => page.evaluate( () => {
			const node = document.querySelector( '.b-rform .form-success__title' ).firstChild;
			const range = document.createRange();
			const lines = [];
			let top = null;
			for ( let i = 0; i < node.length; i++ ) {
				range.setStart( node, i );
				range.setEnd( node, i + 1 );
				const r = range.getClientRects()[ 0 ];
				if ( ! r ) {
					continue;
				}
				if ( null === top || r.top - top > 4 ) {
					lines.push( '' );
					top = r.top;
				}
				lines[ lines.length - 1 ] += node.data[ i ];
			}
			return lines.map( ( l ) => l.replace( /\u00a0/g, ' ' ).trim() );
		} );
		const overlay = await page.evaluate( () => {
			const card = document.querySelector( '.b-rform__card' ).getBoundingClientRect();
			const sentBox = document.querySelector( '.b-rform .form-sent' ).getBoundingClientRect();
			const icon = document.querySelector( '.b-rform .form-success__icon' ).getBoundingClientRect();
			const title = document.querySelector( '.b-rform .form-success__title' ).getBoundingClientRect();
			const formEl = document.querySelector( '.b-rform__form' );
			return {
				form: [ formEl.hidden, formEl.inert ],
				cover: [ sentBox.left - card.left, sentBox.top - card.top, sentBox.width - card.width, sentBox.height - card.height ].map( Math.round ),
				row: title.left > icon.right && Math.abs( ( title.top + title.bottom ) / 2 - ( icon.top + icon.bottom ) / 2 ) < 2,
			};
		} );
		const topAfter = await formTop();
		const oneLine = await titleLines();
		check( 'Экран «Спасибо» — поверх формы: форма на месте (не скрыта, недоступна), слой закрывает всю карточку (внутри рамки 1 px), блок не сдвигается',
			JSON.stringify( overlay.form ) === JSON.stringify( [ false, true ] ) && overlay.cover.every( ( v ) => Math.abs( v ) <= 2 ) && topBefore === topAfter, JSON.stringify( [ overlay, topBefore, topAfter ] ) );
		await page.setViewportSize( { width: 390, height: 900 } );
		const narrow = await titleLines();
		const narrowRow = await page.evaluate( () => {
			const icon = document.querySelector( '.b-rform .form-success__icon' ).getBoundingClientRect();
			const title = document.querySelector( '.b-rform .form-success__title' ).getBoundingClientRect();
			return title.left > icon.right && title.top < icon.bottom && title.bottom > icon.top;
		} );
		await page.setViewportSize( { width: 1280, height: 900 } );
		check( 'Заголовок справа от значка: 1280 px — в одну строку, 390 px — «Спасибо» / «за отзыв!»',
			overlay.row && narrowRow && JSON.stringify( oneLine ) === JSON.stringify( [ 'Спасибо за отзыв!' ] ) && JSON.stringify( narrow ) === JSON.stringify( [ 'Спасибо', 'за отзыв!' ] ), JSON.stringify( [ overlay.row, narrowRow, oneLine, narrow ] ) );
		const saved = JSON.parse( wp( 'eval', `$r = get_posts( array( 'post_type' => 'review', 'post_status' => 'pending', 'numberposts' => 1, 'meta_key' => 'trust_name', 'meta_value' => '${ MARK } Анна' ) ); $id = $r ? $r[0]->ID : 0; $l = get_posts( array( 'post_type' => 'lead', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => 'trust_related', 'meta_value' => $id ) ); echo wp_json_encode( array( $id, $id ? array( get_post_status( $id ), get_post_meta( $id, 'trust_kind', true ), get_post_meta( $id, 'trust_company', true ), get_post_meta( $id, 'trust_rating', true ), get_post_meta( $id, 'trust_email', true ), get_post_meta( $id, 'trust_phone', true ), (bool) get_post_meta( $id, 'trust_consent_at', true ), (bool) get_post_meta( $id, 'trust_publish_consent_at', true ), wp_get_post_terms( $id, 'service_type', array( 'fields' => 'slugs' ) ), get_the_title( $id ) ) : null, $l ? array( (int) $l[0], get_post_meta( $l[0], 'trust_form', true ), count( get_post_meta( $l[0], 'trust_consent', true )['items'] ?? array() ) ) : null ) );` ) );
		if ( saved[ 0 ] ) {
			created.push( String( saved[ 0 ] ) );
		}
		if ( saved[ 2 ] ) {
			created.push( String( saved[ 2 ][ 0 ] ) );
		}
		check( 'Отзыв сохранён «На утверждении»: вид, компания, оценка 4, email (телефона нет), даты обоих согласий, услуга, заголовок «имя, компания»',
			JSON.stringify( saved[ 1 ] ) === JSON.stringify( [ 'pending', 'text', 'ООО Пример, директор', '4', 'anna-review@example.com', '', true, true, [ ppcSlug ], `${ MARK } Анна, ООО Пример, директор` ] ), JSON.stringify( saved[ 1 ] ) );
		check( 'Заявка «Отзыв с сайта» связана с отзывом, в ней два согласия', saved[ 2 ] && 'review' === saved[ 2 ][ 1 ] && 2 === saved[ 2 ][ 2 ], JSON.stringify( saved[ 2 ] ) );
		await page.locator( '.b-rform .form-sent__again' ).click();
		// Подпись оценки обновляется после события reset (setTimeout в view.js).
		await page.waitForFunction( () => ! document.querySelector( 'input[name="rating"]:checked' ) && 'Выберите от 1 до 5' === document.querySelector( '[data-rating-word]' ).textContent, null, { timeout: 2000 } ).catch( () => {} );
		const reset = await page.evaluate( () => [ document.querySelector( '.b-rform__form' ).hidden, document.querySelector( '.b-rform__form' ).inert, document.querySelector( '.b-rform .form-sent' ).hidden, document.querySelector( '[name="name"]' ).value, document.querySelector( '[data-rating-word]' ).textContent, document.activeElement.name ] );
		check( '«Написать ещё один отзыв» — слой убран, снова пустая форма, оценка сброшена', JSON.stringify( reset ) === JSON.stringify( [ false, false, true, '', 'Выберите от 1 до 5', 'name' ] ), JSON.stringify( reset ) );

		// ===== Без JavaScript =====
		const noJs = await browser.newContext( { javaScriptEnabled: false, viewport: { width: 1280, height: 900 } } );
		const plain = await noJs.newPage();
		await plain.goto( PAGE, { waitUntil: 'domcontentloaded' } );
		const nj = await plain.evaluate( () => [ getComputedStyle( document.querySelector( '.b-rlist .filter-chips' ) ).display, new URL( document.querySelector( '.b-rlist__more' ).href ).search ] );
		await plain.goto( `${ PAGE }?reviews=all`, { waitUntil: 'domcontentloaded' } );
		const all = await plain.evaluate( () => [ document.querySelectorAll( '.b-rlist__card' ).length, !! document.querySelector( '.b-rlist__more' ) ] );
		await noJs.close();
		check( 'Без JavaScript: кнопок услуг нет, «Показать ещё» ведёт на ?reviews=all — там все отзывы', 'none' === nj[ 0 ] && '?reviews=all' === nj[ 1 ] && counts.total === all[ 0 ] && ! all[ 1 ], JSON.stringify( [ nj, all ] ) );

		// ===== Сверка с макетом =====
		const sections = [
			[ 'list', '.b-rlist', 'section[aria-labelledby="list-title"]' ],
			[ 'video', '.b-vrev', 'section[aria-labelledby="video-title"]' ],
			[ 'letters', '.b-letters', 'section[aria-labelledby="letters-title"]' ],
			[ 'form', '.b-rform', 'section[aria-labelledby="leave-title"]' ],
		];
		for ( const width of [ 1280, 768, 390 ] ) {
			for ( const [ name, theme, mock ] of sections ) {
				await compare( page, name, width, theme, mock );
			}
		}

		// ===== Свои данные: видео-файл с обложкой, письма вручную =====
		const poster = wp( 'eval', 'echo get_posts( array( "post_type" => "attachment", "post_status" => "any", "numberposts" => 1, "fields" => "ids", "meta_key" => "_trust_demo_id", "meta_value" => "attachment:video-poster.jpg" ) )[0] ?? 0;' );
		const mp4 = wp( 'eval', `$f = wp_upload_bits( '${ MARK }.mp4', null, str_repeat( "\\0", 64 ) ); echo wp_insert_attachment( array( 'post_title' => '${ MARK } mp4', 'post_mime_type' => 'video/mp4', 'post_status' => 'inherit' ), $f['file'] );` );
		created.push( mp4 );
		const vid = wp( 'eval', `$id = wp_insert_post( array( 'post_type' => 'review', 'post_status' => 'publish', 'post_title' => '${ MARK } видео' ) ); foreach ( array( 'trust_kind' => 'video', 'trust_name' => 'Иван', 'trust_company' => 'Компания', 'trust_video_file' => ${ mp4 }, 'trust_video_cover' => ${ Number( poster ) }, 'trust_video_title' => 'Как мы выросли', 'trust_video_duration' => '2:15' ) as $k => $v ) { update_post_meta( $id, $k, $v ); } echo $id;` );
		created.push( vid );
		const scan = wp( 'eval', 'echo get_posts( array( "post_type" => "attachment", "post_status" => "any", "numberposts" => 1, "fields" => "ids", "meta_key" => "_trust_demo_id", "meta_value" => "attachment:letter-scan.jpg" ) )[0] ?? 0;' );
		const letterIds = [ 'Альфа', 'Бета' ].map( ( company ) => wp( 'eval', `$id = wp_insert_post( array( 'post_type' => 'review', 'post_status' => 'publish', 'post_title' => '${ MARK } ${ company }' ) ); update_post_meta( $id, 'trust_kind', 'letter' ); update_post_meta( $id, 'trust_company', '${ company }' ); update_post_meta( $id, 'trust_letter', ${ Number( scan ) } ); echo $id;` ) );
		created.push( ...letterIds );
		const own = createPage( 'Блоки', [
			block( 'video-reviews', `array( 'title' => 'Видео вручную', 'source' => 'manual', 'reviews' => array( ${ vid } ) )` ),
			block( 'letters', `array( 'title' => 'Письма вручную', 'source' => 'manual', 'reviews' => array( ${ letterIds[ 1 ] }, ${ letterIds[ 0 ] } ) )` ),
		].join( ' . ' ) );
		created.push( own );
		await page.goto( `${ URL }/?page_id=${ own }`, { waitUntil: 'networkidle' } );
		const ownData = await page.evaluate( () => {
			const graph = [ ...document.querySelectorAll( 'script[type="application/ld+json"]' ) ].flatMap( ( s ) => JSON.parse( s.textContent )[ '@graph' ] || [] );
			const video = graph.find( ( n ) => 'VideoObject' === n[ '@type' ] );
			return {
				cards: document.querySelectorAll( '.b-vrev__card' ).length,
				file: !! document.querySelector( '.b-vrev__card' ).dataset.videoFile,
				cover: !! document.querySelector( '.b-vrev__cover' ),
				video: video ? [ video.name, video.duration, !! video.thumbnailUrl, !! video.contentUrl ] : null,
				letters: [ ...document.querySelectorAll( '.b-letters__card' ) ].map( ( a ) => a.querySelector( '.b-letters__company' ).textContent ),
			};
		} );
		check( 'Видеоотзыв с файлом и обложкой: обложка, микроразметка VideoObject (название, PT2M15S, обложка, файл)',
			1 === ownData.cards && ownData.file && ownData.cover && JSON.stringify( ownData.video ) === JSON.stringify( [ 'Как мы выросли', 'PT2M15S', true, true ] ), JSON.stringify( ownData ) );
		await page.locator( '.b-vrev__card' ).click();
		const played = await page.locator( '.b-vrev__card' ).evaluate( ( el ) => {
			const video = el.querySelector( 'video' );
			const r = video ? video.getBoundingClientRect() : null;
			const top = r ? document.elementFromPoint( r.left + r.width / 2, r.top + r.height / 2 ) : null;
			return video ? [ el.classList.contains( 'is-playing' ), video.controls, video.autoplay, document.activeElement === video, ! el.querySelector( '.b-vrev__play' ), top === video, getComputedStyle( el.querySelector( '.b-vrev__media' ) ).overflow ] : null;
		} );
		check( 'Нажатие в любом месте карточки запускает плеер: <video controls autoplay>, фокус на плеере; кнопка удалена, сверху — сам плеер, рамка его не обрезает (касания в Safari на iPhone)',
			JSON.stringify( played ) === JSON.stringify( [ true, true, true, true, true, true, 'visible' ] ), JSON.stringify( played ) );
		check( 'Письма вручную — в выбранном порядке (Бета, Альфа)', JSON.stringify( ownData.letters ) === JSON.stringify( [ 'Бета', 'Альфа' ] ), JSON.stringify( ownData.letters ) );

		// ===== Ширины и доступность =====
		const wide = [];
		for ( const width of [ 1440, 1024, 768, 390, 320, 280 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( PAGE, { waitUntil: 'domcontentloaded' } );
			if ( await page.evaluate( () => document.documentElement.scrollWidth > innerWidth ) ) {
				wide.push( width );
			}
		}
		check( 'Ширины 1440–280: без прокрутки вбок', 0 === wide.length, wide.join( ', ' ) );
		await page.setViewportSize( { width: 390, height: 900 } );
		const mob = await page.evaluate( () => {
			const v = [ ...document.querySelectorAll( '.b-vrev__card' ) ].map( ( c ) => Math.round( c.getBoundingClientRect().left ) );
			const lt2 = [ ...document.querySelectorAll( '.b-letters__card' ) ].slice( 0, 2 ).map( ( c ) => c.getBoundingClientRect() );
			const cardsList = [ ...document.querySelectorAll( '.b-rlist__card' ) ].slice( 0, 2 ).map( ( c ) => Math.round( c.getBoundingClientRect().left ) );
			return [ new Set( v ).size, Math.abs( lt2[ 0 ].top - lt2[ 1 ].top ) < 1, new Set( cardsList ).size ];
		} );
		check( 'Телефон 390: отзывы и видео в одну колонку, письма по два в ряд', JSON.stringify( mob ) === JSON.stringify( [ 1, true, 1 ] ), JSON.stringify( mob ) );
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
		const axe = await page.evaluate( async () => {
			const r = await window.axe.run( '.page-entry__content', { resultTypes: [ 'violations' ] } );
			return r.violations.filter( ( x ) => [ 'critical', 'serious' ].includes( x.impact ) ).map( ( x ) => `${ x.id } (${ x.nodes.length })` );
		} );
		check( 'axe: нет нарушений critical/serious на странице «Отзывы»', 0 === axe.length, axe.join( ', ' ) );
		check( 'Консоль браузера на сайте без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1400, height: 1000 } } );
		const editorErrors = collectConsoleErrors( editor );
		await login( editor );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ own }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		const reg = await editor.evaluate( () => [ 'trust/reviews-list', 'trust/video-reviews', 'trust/letters', 'trust/review-form' ].map( ( name ) => {
			const type = wp.blocks.getBlockType( name );
			return [ type && type.title, type && type.category, !! ( type && type.example ) ].join( ':' );
		} ) );
		check( 'Редактор: четыре блока («Компания» и «Формы»), у всех превью',
			JSON.stringify( reg ) === JSON.stringify( [ 'Что говорят клиенты:trust-company:true', 'Видеоотзывы:trust-company:true', 'Благодарственные письма:trust-company:true', 'Оставьте отзыв:trust-forms:true' ] ), JSON.stringify( reg ) );
		const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-letters' ).first().waitFor( { timeout: 30000 } );
		check( 'Редактор: превью блоков на странице', 1 === await canvas.locator( '.b-vrev__card' ).count() && 2 === await canvas.locator( '.b-letters__card' ).count() );
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
