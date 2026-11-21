/**
 * Тест блоков шага 4.9: «Обсудим ваш проект», «Задать вопрос SEO-специалисту», «Оповещение», «Видео».
 *
 * Запуск: node wp-theme/tests/shared-blocks.js
 *
 * «Обсудим ваш проект»: демо на «О компании», «Команде», «Отзывах», «Портфолио» (фон из макетов),
 * тексты по умолчанию, телефон и Telegram из настроек, кнопка на страницу контактов; тексты из
 * «Настроек сайта → Блоки по умолчанию» и свои поля блока сильнее настроек.
 * «Задать вопрос»: пункты, кнопка на страницу вопросов, примечание (формы в блоке нет — задать
 * вопрос можно только на странице вопросов, решение заказчика), фиолетовый фон.
 * Раскладка карточек на планшете и телефоне (правки заказчика): иконка и заголовок в строку, текст
 * и пункты во всю ширину, примечание слева от кнопки, на телефоне кнопка во всю ширину, на узком —
 * у «Задать вопрос» без иконки. «Вопросы SEO»: текст первого экрана во всю ширину, когда форма под
 * ним; «Оповещение» последним на странице — с отступом до подвала.
 * «Оповещение»: на странице «Вопросы SEO» — секция с H2 и ссылкой на аудит из настроек, {brand};
 * в статье — плашка в тексте без H2.
 * «Видео»: обложка, кнопка «Смотреть видео: …», длительность, подпись; до нажатия — ни одного
 * запроса к площадкам, по нажатию — плеер (YouTube без cookie, Rutube, VK) с автозапуском
 * и фокусом, файл MP4 — <video>; неизвестная ссылка открывается в новой вкладке; VideoObject;
 * разбор ссылок и длительности.
 * Общее: сверка с макетами (o-nas/blok-cta.html, seo-vopros/blok-vopros.html,
 * «Важно знать» из vopros-seo.html, «Видеоразбор» из seo-blog/statya.html),
 * ширины 1440–280 без прокрутки вбок, axe-core, консоль, редактор — категории и превью.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp, getSettings, setSettings, resetFormLimits } = require( './lib' );

const MARK = 'ТестОбщихБлоков';
const plain = ( text ) => String( text ).replace( /[\s  ]+/g, ' ' ).trim();

/** PHP: блок по имени и полям (PHP-массив). */
const block = ( name, fields ) => `Trust\\Blocks::serialize( '${ name }', ${ fields } )`;

/**
 * Опубликованная тестовая запись с блоками.
 *
 * @param {string} type   Тип записи (page, post).
 * @param {string} title  Заголовок.
 * @param {string} blocks PHP-выражение: разметка блоков.
 * @return {string} ID.
 */
function createPost( type, title, blocks ) {
	const content = wp( 'eval', `echo ${ blocks };` );
	const id = wp( 'post', 'create', `--post_type=${ type }`, `--post_title=${ MARK } ${ title }`, '--post_status=publish', `--post_content=${ content }`, '--porcelain' );
	if ( 'page' === type ) {
		wp( 'post', 'meta', 'update', id, '_wp_page_template', 'templates/service.php' );
	}
	return id;
}

/**
 * Снимки блока темы и макета и их сравнение.
 *
 * @param {import('playwright').Page} page Страница.
 * @param {Object}                    o    name, width, url, theme, mockup, mock, before (в теме), mockBefore (в макете).
 */
async function compare( page, o ) {
	const name = `shared-${ o.name }-${ o.width }`;
	await page.setViewportSize( { width: o.width, height: 900 } );
	await page.goto( mockupUrl( o.mockup ), { waitUntil: 'networkidle' } );
	await settle( page );
	if ( o.mockBefore ) {
		await page.evaluate( o.mockBefore );
	}
	await page.mouse.move( 0, 0 );
	await isolate( page, o.mock );
	await page.waitForTimeout( 300 );
	const mockWidth = await page.locator( o.mock ).first().evaluate( ( el ) => el.getBoundingClientRect().width );
	await page.locator( o.mock ).first().screenshot( { path: path.join( OUT, `${ name }-mockup.png` ) } );

	await page.goto( o.url, { waitUntil: 'networkidle' } );
	await settle( page );
	if ( o.before ) {
		await page.evaluate( o.before, mockWidth );
	}
	await page.mouse.move( 0, 0 );
	await isolate( page, o.theme );
	await page.waitForTimeout( 300 );
	await page.locator( o.theme ).first().screenshot( { path: path.join( OUT, `${ name }-theme.png` ) } );
	const diff = comparePng( path.join( OUT, `${ name }-mockup.png` ), path.join( OUT, `${ name }-theme.png` ), path.join( OUT, `${ name }-diff.png` ) );
	check( `Макет ${ o.mockup }${ o.label ? ' ' + o.label : '' } ${ o.width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
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
	const questionsBefore = wp( 'post', 'list', '--post_type=question', '--post_status=any', '--format=ids' ).split( ' ' ).filter( Boolean );
	const leadsBefore = wp( 'post', 'list', '--post_type=lead', '--post_status=any', '--format=ids' ).split( ' ' ).filter( Boolean );
	const SETTINGS = [ 'cta_title', 'cta_button', 'ask_title', 'notice_title', 'notice_link' ];
	const saved = getSettings( SETTINGS );

	try {
		// ===== «Обсудим ваш проект» =====
		const cta = async ( url ) => {
			await page.goto( URL + url, { waitUntil: 'networkidle' } );
			return page.locator( '.b-cta' ).evaluate( ( el ) => ( {
				last: ! el.nextElementSibling,
				bg: getComputedStyle( el ).backgroundColor,
				labelled: el.getAttribute( 'aria-labelledby' ) === el.querySelector( 'h2' ).id,
				h2: el.querySelector( 'h2' ).textContent.trim(),
				text: el.querySelector( '.dark-card__text' ).textContent.trim(),
				contacts: [ ...el.querySelectorAll( '.b-cta__contact' ) ].map( ( a ) => a.textContent.trim() + ' ' + a.getAttribute( 'href' ) + ( a.target ? ' ' + a.target : '' ) ),
				button: el.querySelector( '.dark-card__cta' ).textContent.trim() + ' ' + new URL( el.querySelector( '.dark-card__cta' ).href ).pathname,
				icons: el.querySelectorAll( '.dark-card__icon svg, .dark-card__cta-arrow svg' ).length,
			} ) );
		};
		const about = await cta( '/o-kompanii/' );
		check( '«Обсудим ваш проект» на «О компании»: последний блок, лавандовый фон, H2 и текст по умолчанию (из макета)',
			about.last && 'rgb(248, 245, 255)' === about.bg && about.labelled && 'Обсудим ваш проект' === about.h2 &&
			'Расскажите о задаче — подберём решение, назовём сроки и стоимость. Состав работ фиксируем в договоре.' === about.text, JSON.stringify( about ) );
		check( '«Обсудим ваш проект»: телефон из настроек (tel:), «Написать в Telegram» в новой вкладке, «Оставить заявку» → «Контакты»',
			'+1 (000) 000-00-00 tel:+10000000000' === about.contacts[ 0 ] && /^Написать в Telegram https:\/\/t\.me\/.+ _blank$/.test( about.contacts[ 1 ] ) && 'Оставить заявку /kontakty/' === about.button && 2 === about.icons, JSON.stringify( about ) );
		const bgs = [];
		for ( const url of [ '/komanda/', '/otzyvy/', '/portfolio/' ] ) {
			bgs.push( ( await cta( url ) ).bg );
		}
		check( 'Демо: «Команда» — лавандовый фон, «Отзывы» и «Портфолио» — белый (как в макетах)', JSON.stringify( bgs ) === JSON.stringify( [ 'rgb(248, 245, 255)', 'rgb(255, 255, 255)', 'rgb(255, 255, 255)' ] ), bgs.join( ' | ' ) );

		setSettings( {
			cta_title: 'Расскажите о задаче',
			cta_button: 'Написать нам',
		} );
		const fromSettings = await cta( '/o-kompanii/' );
		const ownId = createPost( 'page', 'CTA', block( 'cta', "array( 'title' => 'Свой заголовок', 'text' => 'Свой текст', 'contacts' => 0, 'button_label' => 'Позвонить', 'button_url' => '#callback-modal' )" ) );
		created.push( ownId );
		await page.goto( `${ URL }/?page_id=${ ownId }`, { waitUntil: 'networkidle' } );
		const own = await page.locator( '.b-cta' ).evaluate( ( el ) => [ el.querySelector( 'h2' ).textContent.trim(), el.querySelector( '.dark-card__text' ).textContent.trim(), el.querySelectorAll( '.b-cta__contact' ).length, el.querySelector( '.dark-card__cta' ).getAttribute( 'href' ), el.querySelector( '.dark-card__cta' ).textContent.trim() ] );
		await page.locator( '.b-cta .dark-card__cta' ).click();
		const modal = await page.locator( '#callback-modal[open]' ).isVisible().catch( () => false );
		await page.keyboard.press( 'Escape' );
		setSettings( saved );
		check( 'Тексты «Настроек сайта → Блоки по умолчанию» вместо пустых полей блока', 'Расскажите о задаче' === fromSettings.h2 && fromSettings.button.startsWith( 'Написать нам ' ), JSON.stringify( fromSettings ) );
		check( 'Свои поля блока сильнее настроек; без телефона и Telegram; кнопка #callback-modal открывает окно звонка',
			JSON.stringify( own ) === JSON.stringify( [ 'Свой заголовок', 'Свой текст', 0, '#callback-modal', 'Позвонить' ] ) && modal, JSON.stringify( [ own, modal ] ) );

		// ===== «Задать вопрос SEO-специалисту» =====
		await page.goto( `${ URL }/seo/pochasovaya-oplata/`, { waitUntil: 'networkidle' } );
		const askLink = await page.locator( '.b-ask' ).evaluate( ( el ) => ( {
			bg: getComputedStyle( el ).backgroundColor,
			h2: el.querySelector( 'h2' ).textContent.trim(),
			text: el.querySelector( '.dark-card__text' ).textContent.trim(),
			points: [ ...el.querySelectorAll( '.b-ask__points li' ) ].map( ( li ) => li.textContent.trim() ),
			pointsLabel: el.querySelector( '.b-ask__points' ).getAttribute( 'aria-label' ),
			button: el.querySelector( '.dark-card__cta' ).textContent.trim() + ' ' + new URL( el.querySelector( '.dark-card__cta' ).href ).pathname,
			note: el.querySelector( '.dark-card__note' ).textContent.trim(),
			form: !! el.querySelector( 'form' ),
		} ) );
		check( '«Задать вопрос», вид «Кнопка»: H2, текст и 3 пункта из макета (список «Условия»), «Задать вопрос бесплатно» → страница вопросов, примечание',
			'Хотите бесплатно задать вопрос SEO-специалисту?' === askLink.h2 && 'Опишите задачу — специалист ответит на сайте и расскажет, как поступил бы на вашем месте.' === askLink.text &&
			'Бесплатно|Ответ на сайте|Отвечает практикующий специалист' === askLink.points.join( '|' ) && 'Условия' === askLink.pointsLabel &&
			'Задать вопрос бесплатно /seo/voprosy/' === askLink.button && 'Ответ — личное мнение специалиста, а не аудит сайта.' === askLink.note && ! askLink.form && 'rgb(248, 245, 255)' === askLink.bg, JSON.stringify( askLink ) );

		const forms = JSON.parse( wp( 'eval', `echo wp_json_encode( array_values( array_filter( array_map( static fn( $p ) => str_contains( $p->post_content, 'wp:trust/ask' ) ? get_permalink( $p ) : null, get_posts( array( 'post_type' => array( 'page', 'post' ), 'numberposts' => -1 ) ) ) ) ) );` ) );
		const askForms = [];
		for ( const url of forms ) {
			await page.goto( url, { waitUntil: 'domcontentloaded' } );
			askForms.push( await page.locator( '.b-ask form, .b-ask input, .b-ask textarea' ).count() );
		}
		const fieldNames = JSON.parse( wp( 'eval', "echo wp_json_encode( array_column( include get_template_directory() . '/blocks/ask/fields.php', 'name' ) );" ) );
		check( '«Задать вопрос» только ведёт на страницу вопросов: в блоке нет формы и полей вида «Форма»',
			forms.length >= 1 && askForms.every( ( n ) => 0 === n ) && ! fieldNames.includes( 'view' ) && ! fieldNames.includes( 'submit' ), JSON.stringify( [ forms, askForms, fieldNames ] ) );

		const violetId = createPost( 'page', 'Ask', block( 'ask', "array( 'background' => 'violet', 'title' => 'Свой вопрос?', 'points' => \"Раз\\nДва\", 'note' => '', 'button_label' => 'Спросить' )" ) );
		created.push( violetId );
		setSettings( { ask_title: 'Заголовок из настроек' } );
		await page.goto( `${ URL }/?page_id=${ violetId }`, { waitUntil: 'networkidle' } );
		const violet = await page.locator( '.b-ask' ).evaluate( ( el ) => [ getComputedStyle( el ).backgroundColor, el.querySelector( 'h2' ).textContent.trim(), el.querySelectorAll( '.b-ask__points li' ).length, el.querySelector( '.dark-card__note' ).textContent.trim(), el.querySelector( '.dark-card__cta' ).textContent.trim() + ' ' + new URL( el.querySelector( '.dark-card__cta' ).href ).pathname ] );
		setSettings( saved );
		check( 'Фиолетовый фон; свой заголовок и текст кнопки сильнее настроек (адрес — страница вопросов); свои пункты; пустое примечание — из настроек',
			JSON.stringify( violet ) === JSON.stringify( [ 'rgb(91, 33, 182)', 'Свой вопрос?', 2, 'Ответ — личное мнение специалиста, а не аудит сайта.', 'Спросить /seo/voprosy/' ] ), JSON.stringify( violet ) );

		// ===== «Оповещение» =====
		await page.goto( `${ URL }/seo/voprosy/`, { waitUntil: 'networkidle' } );
		const notice = await page.locator( '.b-notice-sec' ).evaluate( ( el ) => ( {
			afterHero: !! el.previousElementSibling && el.previousElementSibling.classList.contains( 'b-hero-questions' ),
			labelled: el.getAttribute( 'aria-labelledby' ) === el.querySelector( 'h2' ).id,
			h2: el.querySelector( 'h2' ).textContent.trim(),
			text: el.querySelector( '.b-notice__text' ).textContent.trim(),
			more: el.querySelector( '.b-notice__more' ).textContent.replace( /\s+/g, ' ' ).trim(),
			href: new URL( el.querySelector( '.b-notice__more a' ).href ).pathname,
			icon: !! el.querySelector( '.b-notice__icon[aria-hidden="true"] svg' ),
		} ) );
		check( '«Оповещение» на «Вопросы SEO»: под первым экраном, H2 и текст по умолчанию ({brand} — бренд из настроек)',
			notice.afterHero && notice.labelled && 'Ответ — личное мнение автора, а не аудит сайта' === notice.h2 && notice.text.startsWith( 'Ответ отражает личное мнение автора — SEO-специалиста [Бренд].' ) && notice.icon, JSON.stringify( notice ) );
		check( '«Оповещение»: «Нужна полная проверка сайта? Закажите технический аудит» → /seo/audit/ (ссылка из настроек)',
			'Нужна полная проверка сайта? Закажите технический аудит' === notice.more && '/seo/audit/' === notice.href, JSON.stringify( notice ) );
		const inlineId = createPost( 'post', 'Notice', block( 'notice', "array( 'title' => 'Важно', 'text' => 'Текст в статье', 'show_link' => 0 )" ) );
		created.push( inlineId );
		await page.goto( `${ URL }/?p=${ inlineId }`, { waitUntil: 'networkidle' } );
		const inline = await page.locator( '.b-notice' ).evaluate( ( el ) => [ el.getAttribute( 'role' ), el.classList.contains( 'b-notice--inline' ), el.querySelectorAll( 'h2' ).length, el.querySelector( '.b-notice__title' ).tagName, el.querySelectorAll( '.b-notice__more' ).length, !! el.closest( 'section.b-notice-sec' ) ] );
		check( 'В статье — плашка в тексте (role="note"), заголовок — строка, не H2; без ссылки при выключенном флажке',
			JSON.stringify( inline ) === JSON.stringify( [ 'note', true, 0, 'P', 0, false ] ), JSON.stringify( inline ) );
		setSettings( { notice_title: 'Заголовок оповещения из настроек', notice_link: '' } );
		await page.goto( `${ URL }/seo/voprosy/`, { waitUntil: 'networkidle' } );
		const noticeSet = [ ( await page.locator( '.b-notice-sec h2' ).textContent() ).trim(), await page.locator( '.b-notice-sec .b-notice__more' ).count() ];
		setSettings( saved );
		check( 'Заголовок из настроек; без ссылки в настройках строки со ссылкой нет', JSON.stringify( noticeSet ) === JSON.stringify( [ 'Заголовок оповещения из настроек', 0 ] ), JSON.stringify( noticeSet ) );

		// ===== «Видео» =====
		const external = [];
		const track = ( r ) => {
			if ( ! r.url().startsWith( URL ) && ! r.url().startsWith( 'data:' ) && ! r.url().startsWith( 'file:' ) ) {
				external.push( r.url() );
			}
		};
		page.on( 'request', track );
		await page.goto( `${ URL }/blog/robots-txt-proverka/`, { waitUntil: 'networkidle' } );
		const video = page.locator( '.b-video' ).first();
		const v = await video.evaluate( ( el ) => ( {
			inline: el.classList.contains( 'b-video--inline' ) && ! el.closest( 'section' ),
			poster: [ el.querySelector( 'img.b-video__poster' ).getAttribute( 'alt' ), el.querySelector( 'img' ).getAttribute( 'loading' ), Math.round( ( el.querySelector( '.b-video__frame' ).clientWidth / el.querySelector( '.b-video__frame' ).clientHeight ) * 100 ) / 100 ],
			play: [ el.querySelector( '.b-video__play' ).tagName, el.querySelector( '.b-video__play' ).getAttribute( 'aria-label' ), el.querySelector( '.b-video__play' ).getAttribute( 'href' ), el.querySelector( '.b-video__play' ).target ],
			duration: [ el.querySelector( '.b-video__duration' ).textContent.replace( /\s+/g, ' ' ).trim(), el.querySelector( '.b-video__duration time' ).getAttribute( 'datetime' ) ],
			caption: el.querySelector( 'figcaption' ).textContent.trim(),
			frames: el.querySelectorAll( 'iframe, video' ).length,
		} ) );
		check( '«Видео» в статье: рисунок в тексте, обложка 16:9 (alt="", ленивая загрузка), кнопка «Смотреть видео: …» — ссылка на видео, длительность, подпись',
			v.inline && JSON.stringify( v.poster ) === JSON.stringify( [ '', 'lazy', 1.78 ] ) && 'A' === v.play[ 0 ] && 'Смотреть видео: Как проверить robots.txt' === v.play[ 1 ] &&
			v.play[ 2 ].startsWith( 'https://www.youtube.com/watch?v=' ) && 'Длительность: 12:40' === v.duration[ 0 ] && 'PT12M40S' === v.duration[ 1 ] &&
			'Видео: проверяем robots.txt в Яндекс Вебмастере и Google Search Console.' === v.caption && 0 === v.frames, JSON.stringify( v ) );
		const ld = await page.evaluate( () => JSON.parse( document.querySelector( 'script[type="application/ld+json"]' ).textContent )[ '@graph' ].filter( ( n ) => 'VideoObject' === n[ '@type' ] ) );
		check( 'Микроразметка VideoObject: название, описание, обложка, дата, длительность, embedUrl без cookie',
			1 === ld.length && 'Как проверить robots.txt' === ld[ 0 ].name && ld[ 0 ].description.startsWith( 'Видео: проверяем' ) && /video-poster.*\.jpg$/.test( ld[ 0 ].thumbnailUrl ) &&
			/^\d{4}-\d\d-\d\dT/.test( ld[ 0 ].uploadDate ) && 'PT12M40S' === ld[ 0 ].duration && ld[ 0 ].embedUrl.startsWith( 'https://www.youtube-nocookie.com/embed/' ), JSON.stringify( ld ) );
		check( 'До нажатия — ни одного запроса к площадкам видео', 0 === external.length, external.slice( 0, 3 ).join( ' | ' ) );
		await page.route( /youtube-nocookie\.com|rutube\.ru|vk\.com/, ( route ) => route.fulfill( { status: 200, contentType: 'text/html', body: '<!doctype html><title>player</title>' } ) );
		await video.locator( '.b-video__play' ).click();
		const playing = await video.evaluate( ( el ) => ( {
			src: el.querySelector( 'iframe' ) && el.querySelector( 'iframe' ).src,
			title: el.querySelector( 'iframe' ) && el.querySelector( 'iframe' ).title,
			allow: el.querySelector( 'iframe' ) && el.querySelector( 'iframe' ).getAttribute( 'allow' ),
			focus: document.activeElement === el.querySelector( 'iframe' ),
			poster: getComputedStyle( el.querySelector( '.b-video__poster' ) ).display,
		} ) );
		check( 'Нажатие: плеер YouTube без cookie с автозапуском, название, полноэкранный режим, фокус на плеере, обложка скрыта',
			/^https:\/\/www\.youtube-nocookie\.com\/embed\/[^?]+\?autoplay=1$/.test( playing.src ) && 'Как проверить robots.txt' === playing.title && playing.allow.includes( 'fullscreen' ) && playing.focus && 'none' === playing.poster, JSON.stringify( playing ) );
		page.off( 'request', track );

		const parse = JSON.parse( wp( 'eval', `echo wp_json_encode( array_map( static fn( $u ) => Trust\\Video::embed( $u )['embed'] ?? null, array( 'https://youtu.be/dQw4w9WgXcQ?si=abc', 'https://www.youtube.com/shorts/dQw4w9WgXcQ', 'https://m.youtube.com/watch?v=dQw4w9WgXcQ&t=30', 'https://rutube.ru/video/0123456789abcdef0123456789abcdef/', 'https://vk.com/video-12345_67890', 'https://vkvideo.ru/video-1_2?list=x', 'https://vk.com/video_ext.php?oid=-1&id=2&hash=abc123', 'https://example.com/video.html' ) ) );` ) );
		check( 'Ссылки: youtu.be, Shorts, m.youtube, Rutube, VK (vk.com, vkvideo.ru, video_ext) — плеер; чужой сайт — нет',
			JSON.stringify( parse ) === JSON.stringify( [
				'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
				'https://rutube.ru/play/embed/0123456789abcdef0123456789abcdef', 'https://vk.com/video_ext.php?oid=-12345&id=67890&hd=2', 'https://vk.com/video_ext.php?oid=-1&id=2&hd=2',
				'https://vk.com/video_ext.php?oid=-1&id=2&hash=abc123&hd=2', null,
			] ), JSON.stringify( parse ) );
		const durations = JSON.parse( wp( 'eval', `echo wp_json_encode( array_map( array( 'Trust\\\\Video', 'duration' ), array( '12:40', '1:02:03', '0:45', '5:00', '12:60', 'abc', '' ) ) );` ) );
		check( 'Длительность для поиска: 12:40 → PT12M40S, 1:02:03 → PT1H2M3S, 0:45 → PT45S, 5:00 → PT5M; неверная — пусто',
			JSON.stringify( durations ) === JSON.stringify( [ 'PT12M40S', 'PT1H2M3S', 'PT45S', 'PT5M', '', '', '' ] ), JSON.stringify( durations ) );

		// Своя страница: файл MP4, неизвестная ссылка, ширина.
		const poster = wp( 'eval', 'echo get_posts( array( "post_type" => "attachment", "post_status" => "any", "numberposts" => 1, "fields" => "ids", "meta_key" => "_trust_demo_id", "meta_value" => "attachment:video-poster.jpg" ) )[0] ?? 0;' );
		const mp4 = wp( 'eval', `$f = wp_upload_bits( '${ MARK }.mp4', null, str_repeat( "\\0", 64 ) ); $id = wp_insert_attachment( array( 'post_title' => '${ MARK } mp4', 'post_mime_type' => 'video/mp4', 'post_status' => 'inherit' ), $f['file'] ); echo $id;` );
		created.push( mp4 );
		const videoPage = createPost( 'page', 'Video', [
			block( 'video', `array( 'source' => 'file', 'file' => ${ mp4 }, 'poster' => ${ Number( poster ) }, 'title' => 'Файл', 'width' => 'text' )` ),
			block( 'video', `array( 'source' => 'link', 'url' => 'https://example.com/promo', 'poster' => ${ Number( poster ) }, 'title' => 'Чужой сайт', 'width' => 'wide' )` ),
			block( 'video', `array( 'source' => 'link', 'url' => 'https://rutube.ru/video/0123456789abcdef0123456789abcdef/', 'poster' => 0, 'title' => 'Без обложки' )` ),
		].join( ' . ' ) );
		created.push( videoPage );
		await page.goto( `${ URL }/?page_id=${ videoPage }`, { waitUntil: 'networkidle' } );
		const pv = await page.evaluate( () => [ ...document.querySelectorAll( '.b-video' ) ].map( ( el ) => [ Math.round( el.getBoundingClientRect().width ), !! el.closest( 'section.b-video-sec' ), el.dataset.videoFile ? 'file' : ( el.dataset.videoEmbed ? 'embed' : 'link' ), el.querySelector( '.b-video__play' ).target, el.querySelectorAll( '.b-video__duration' ).length ] ) );
		check( 'На странице — белая секция: по ширине текста 960 px и во всю ширину 1200 px; файл — <video>, чужая ссылка — новая вкладка; без обложки блока нет; без длительности — без плашки',
			JSON.stringify( pv ) === JSON.stringify( [ [ 960, true, 'file', '_blank', 0 ], [ 1184, true, 'link', '_blank', 0 ] ] ), JSON.stringify( pv ) );
		await page.locator( '.b-video' ).first().locator( '.b-video__play' ).click();
		const file = await page.locator( '.b-video' ).first().evaluate( ( el ) => {
			const video = el.querySelector( 'video' );
			return video ? [ video.controls, video.autoplay, new URL( video.src ).pathname.endsWith( '.mp4' ), document.activeElement === video ] : null;
		} );
		check( 'Файл MP4 по нажатию — <video> с управлением, автозапуском и фокусом', JSON.stringify( file ) === JSON.stringify( [ true, true, true, true ] ), JSON.stringify( file ) );
		const ldPage = await page.evaluate( () => JSON.parse( document.querySelector( 'script[type="application/ld+json"]' ).textContent )[ '@graph' ].filter( ( n ) => 'VideoObject' === n[ '@type' ] ).map( ( n ) => [ n.name, n.contentUrl ? 'content' : '', n.embedUrl ? 'embed' : '' ].join( ':' ) ) );
		check( 'VideoObject: файл — contentUrl; чужая ссылка — без VideoObject (поиску нужен адрес плеера или файла)', JSON.stringify( ldPage ) === JSON.stringify( [ 'Файл:content:' ] ), JSON.stringify( ldPage ) );

		// ===== Ширины и доступность =====
		const pages = [ '/o-kompanii/', '/seo/pochasovaya-oplata/', '/seo/voprosy/', '/blog/robots-txt-proverka/' ];
		const wide = [];
		for ( const width of [ 1440, 1024, 768, 390, 320, 280 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			for ( const url of pages ) {
				await page.goto( URL + url, { waitUntil: 'domcontentloaded' } );
				if ( await page.evaluate( () => document.documentElement.scrollWidth > innerWidth ) ) {
					wide.push( `${ width }${ url }` );
				}
			}
		}
		check( 'Ширины 1440–280: без прокрутки вбок', 0 === wide.length, wide.join( ', ' ) );
		// Раскладка карточек (правки заказчика).
		const geometry = ( sel ) => page.locator( sel ).evaluate( ( el ) => {
			const r = ( s ) => {
				const node = el.querySelector( s );
				return node && 'none' !== getComputedStyle( node ).display ? node.getBoundingClientRect() : null;
			};
			const card = r( '.dark-card' );
			const icon = r( '.dark-card__icon' );
			const h2 = r( 'h2' );
			const text = r( '.dark-card__text' );
			const cta = r( '.dark-card__cta' );
			const note = r( '.dark-card__note' );
			return {
				icon: !! icon,
				titleBeside: !! icon && h2.left >= icon.right && h2.top < icon.bottom,
				textFull: Math.abs( text.left - ( icon || h2 ).left ) < 1,
				ctaFull: Math.abs( cta.left - text.left ) < 1 && Math.abs( text.right - cta.right ) < 1,
				ctaCentered: Math.abs( ( cta.left - card.left ) - ( card.right - cta.right ) ) < 1,
				ctaRight: Math.abs( card.right - cta.right - ( text.left - card.left ) ) < 1 && cta.left > card.left + card.width / 2,
				noteLeft: note ? note.right <= cta.left && Math.abs( note.left - text.left ) < 1 : null,
			};
		} );
		const layouts = {};
		for ( const [ url, sel, key ] of [ [ '/o-kompanii/', '.b-cta', 'cta' ], [ '/seo/pochasovaya-oplata/', '.b-ask', 'ask' ] ] ) {
			for ( const width of [ 768, 440, 390 ] ) {
				await page.setViewportSize( { width, height: 900 } );
				await page.goto( URL + url, { waitUntil: 'networkidle' } );
				layouts[ `${ key }${ width }` ] = await geometry( sel );
			}
		}
		const L = layouts;
		check( 'Телефон 390–440: «Обсудим ваш проект» — заголовок справа от иконки, текст во всю ширину, кнопка во всю ширину по центру',
			[ L.cta390, L.cta440 ].every( ( g ) => g.icon && g.titleBeside && g.textFull && g.ctaFull && g.ctaCentered ), JSON.stringify( [ L.cta390, L.cta440 ] ) );
		check( 'Телефон 440: «Задать вопрос» — заголовок справа от иконки, текст и пункты во всю ширину, кнопка по центру',
			L.ask440.icon && L.ask440.titleBeside && L.ask440.textFull && L.ask440.ctaFull && L.ask440.ctaCentered, JSON.stringify( L.ask440 ) );
		check( 'Телефон 390: «Задать вопрос» без иконки, заголовок и текст во всю ширину', ! L.ask390.icon && L.ask390.textFull && L.ask390.ctaCentered, JSON.stringify( L.ask390 ) );
		check( 'Планшет 768: «Задать вопрос» — заголовок справа от иконки, текст во всю ширину, кнопка справа, примечание слева от неё',
			L.ask768.titleBeside && L.ask768.textFull && L.ask768.ctaRight && L.ask768.noteLeft, JSON.stringify( L.ask768 ) );
		check( 'Планшет 768: «Обсудим ваш проект» — заголовок справа от иконки, текст во всю ширину', L.cta768.titleBeside && L.cta768.textFull, JSON.stringify( L.cta768 ) );

		await page.setViewportSize( { width: 990, height: 900 } );
		await page.goto( `${ URL }/seo/voprosy/`, { waitUntil: 'networkidle' } );
		const heroLead = await page.evaluate( () => [ Math.round( document.querySelector( '.b-hero-questions__lead' ).getBoundingClientRect().width ), Math.round( document.querySelector( '.b-hero-questions__card' ).getBoundingClientRect().width ) ] );
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( `${ URL }/seo/voprosy/`, { waitUntil: 'networkidle' } );
		const footerGap = await page.evaluate( () => Math.round( document.querySelector( '.site-footer' ).getBoundingClientRect().top - document.querySelector( '.b-notice' ).getBoundingClientRect().bottom ) );
		check( '«Вопросы SEO», 990 px: форма под текстом — текст первого экрана во всю ширину карточки', heroLead[ 0 ] === heroLead[ 1 ], JSON.stringify( heroLead ) );
		check( '«Оповещение» последним на странице — отступ до подвала 64 px', 64 === footerGap, String( footerGap ) );

		await page.setViewportSize( { width: 1280, height: 900 } );
		const axeIssues = [];
		for ( const [ url, sel ] of [ [ '/o-kompanii/', '.b-cta' ], [ '/seo/pochasovaya-oplata/', '.b-ask' ], [ '/seo/voprosy/', '.b-notice-sec' ], [ '/blog/robots-txt-proverka/', '.b-video' ] ] ) {
			await page.goto( URL + url, { waitUntil: 'networkidle' } );
			await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
			const res = await page.evaluate( async ( s ) => {
				const r = await window.axe.run( s, { resultTypes: [ 'violations' ] } );
				return r.violations.filter( ( x ) => [ 'critical', 'serious' ].includes( x.impact ) ).map( ( x ) => `${ x.id } (${ x.nodes.length })` );
			}, sel );
			res.forEach( ( x ) => axeIssues.push( `${ url } ${ x }` ) );
		}
		check( 'axe: нет нарушений critical/serious в четырёх блоках', 0 === axeIssues.length, axeIssues.join( ', ' ) );
		check( 'Консоль браузера на сайте без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Сверка с макетами =====
		for ( const width of [ 1280, 768, 390 ] ) {
			await compare( page, { name: 'cta', width, url: `${ URL }/o-kompanii/`, theme: '.b-cta', mockup: 'o-nas/blok-cta.html', mock: 'section' } );
			await compare( page, { name: 'ask-link', width, url: `${ URL }/seo/pochasovaya-oplata/`, theme: '.b-ask', mockup: 'seo-vopros/blok-vopros.html', mock: 'section' } );
			await compare( page, { name: 'notice', width, url: `${ URL }/seo/voprosy/`, theme: '.b-notice-sec', mockup: 'seo-vopros/vopros-seo.html', mock: 'section[aria-label="Важно знать"]' } );
			// «Видеоразбор»: шаблон статьи — шаг 5.2, сверяется сам рисунок по ширине колонки макета.
			await compare( page, {
				name: 'video', width, url: `${ URL }/blog/robots-txt-proverka/`, theme: '.b-video', mockup: 'seo-blog/statya.html', mock: 'figure:has(img[src*="video-poster"])',
				before: ( w ) => {
					const el = document.querySelector( '.b-video' );
					el.style.setProperty( 'width', `${ w }px`, 'important' );
					el.style.setProperty( 'margin', '0', 'important' );
				},
			} );
		}

		// ===== Редактор =====
		const editorPage = await browser.newPage( { viewport: { width: 1400, height: 1000 } } );
		const editorErrors = collectConsoleErrors( editorPage );
		await login( editorPage );
		await editorPage.goto( `${ URL }/wp-admin/post.php?post=${ ownId }&action=edit` );
		await editorPage.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		const reg = await editorPage.evaluate( () => [ 'trust/cta', 'trust/ask', 'trust/notice', 'trust/video' ].map( ( name ) => {
			const type = wp.blocks.getBlockType( name );
			return [ name, type && type.title, type && type.category, !! ( type && type.example ) ];
		} ) );
		check( 'Редактор: «Обсудим ваш проект» и «Задать вопрос» — «Формы», «Оповещение» и «Видео» — «Текст и медиа», у всех превью',
			JSON.stringify( reg ) === JSON.stringify( [
				[ 'trust/cta', 'Обсудим ваш проект', 'trust-forms', true ],
				[ 'trust/ask', 'Задать вопрос SEO-специалисту', 'trust-forms', true ],
				[ 'trust/notice', 'Оповещение', 'trust-content', true ],
				[ 'trust/video', 'Видео', 'trust-content', true ],
			] ), JSON.stringify( reg ) );
		const canvas = editorPage.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-cta' ).first().waitFor( { timeout: 30000 } );
		check( 'Редактор: превью «Обсудим ваш проект» со своими текстами', 'Свой заголовок' === ( await canvas.locator( '.b-cta h2' ).textContent() ).trim() );
		const allowed = JSON.parse( wp( 'eval', 'echo wp_json_encode( array( in_array( "trust/video", Trust\\Allowed_Blocks::for_post_type( "post" ), true ), in_array( "trust/notice", Trust\\Allowed_Blocks::for_post_type( "question" ), true ), in_array( "trust/cta", Trust\\Allowed_Blocks::for_post_type( "page" ), true ), in_array( "trust/ask", Trust\\Allowed_Blocks::for_post_type( "page" ), true ) ) );' ) );
		check( 'Разрешены: «Видео» в статьях, «Оповещение» в ответах, «Обсудим ваш проект» и «Задать вопрос» на страницах', JSON.stringify( allowed ) === '[true,true,true,true]', JSON.stringify( allowed ) );
		const fields = await ( async () => {
			await editorPage.goto( `${ URL }/wp-admin/admin.php?page=trust-settings`, { waitUntil: 'domcontentloaded' } );
			return editorPage.evaluate( () => [ 'cta_title', 'cta_page', 'ask_title', 'ask_points', 'ask_page', 'notice_title', 'notice_link' ].map( ( n ) => !! document.querySelector( `.acf-field[data-name="${ n }"]` ) ) );
		} )();
		check( '«Настройки сайта → Блоки по умолчанию»: поля «Обсудим ваш проект», «Задать вопрос», «Оповещение»', fields.every( Boolean ), JSON.stringify( fields ) );
		check( 'Редактор и настройки без ошибок JavaScript', 0 === editorErrors.length, editorErrors.slice( 0, 3 ).join( ' | ' ) );
		await editorPage.close();
	} finally {
		setSettings( saved );
		created.forEach( ( id ) => wp( 'post', 'delete', String( id ), '--force' ) );
		wp( 'post', 'list', '--post_type=question', '--post_status=any', '--format=ids' ).split( ' ' ).filter( ( id ) => id && ! questionsBefore.includes( id ) ).forEach( ( id ) => wp( 'post', 'delete', id, '--force' ) );
		wp( 'post', 'list', '--post_type=lead', '--post_status=any', '--format=ids' ).split( ' ' ).filter( ( id ) => id && ! leadsBefore.includes( id ) ).forEach( ( id ) => wp( 'post', 'delete', id, '--force' ) );
		resetFormLimits();
	}

	await browser.close();
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
