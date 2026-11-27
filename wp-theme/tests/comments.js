/**
 * Тест шага 5.3 — комментарии статьи и лайки комментариев (comments.php, макет seo-blog/statya.html).
 *
 * Запуск: node wp-theme/tests/comments.js
 *
 * Демо: у статьи о robots.txt 12 комментариев (8 веток, 4 ответа автора). Проверяет раздел (H2 «Комментарии 12»,
 * форма: имя, email «— не публикуется», комментарий, согласие, «Отправить» неактивна, пока текст пустой),
 * список (новые сверху, 3 ветки, ответы со сдвигом, «Автор», аватары-инициалы, без Gravatar), «Показать ещё
 * 8 комментариев», пункт содержания «Комментарии · 12», кнопку рядом с «Нравится» и ссылку из первого экрана,
 * проверку полей, отправку (на модерации: «Спасибо!…», в базе — с email и согласием, на сайте не виден),
 * «Написать ещё», ответ под комментарием (форма переносится, имя и email подставляются, Esc, «Ответ
 * отправлен на модерацию.»), ответ на ответ — к исходному комментарию, модератор — публикуется сразу,
 * без JavaScript (admin-post.php), защиту (ловушка, закрытые комментарии, черновик, wp-comments-post.php),
 * лайки комментариев (REST, cookie, после перезагрузки), настройки («Комментариев сразу», «Показывать
 * лайки», «Тексты шаблонов»), закрытые комментарии, телефон 390 px, сверку списка с макетом, axe, консоль.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp, getSettings, setSettings, resetFormLimits, formToken, postForm } = require( './lib' );

const POST = `${ URL }/blog/robots-txt-proverka/`;
const BROWSER_UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
const MARK = 'ТЕСТ-КОММЕНТАРИЙ';
const SETTINGS = [ 'blog_comments_shown', 'blog_show_likes', 'tpl_post_comments' ];
const sleep = ( ms ) => new Promise( ( resolve ) => setTimeout( resolve, ms ) );

const restoreSettings = ( values ) => {
	const b64 = Buffer.from( JSON.stringify( values ) ).toString( 'base64' );
	wp( 'eval', `foreach ( json_decode( base64_decode( '${ b64 }' ), true ) as $n => $v ) { $k = Trust\\Fields::key( Trust\\Settings::PREFIX, $n ); if ( null === $v ) { delete_field( $k, 'option' ); } else { update_field( $k, $v, 'option' ); } }` );
};
const postId = () => wp( 'eval', "echo (int) get_page_by_path( 'robots-txt-proverka', OBJECT, 'post' )->ID;" );
const demoComment = ( key ) => wp( 'eval', `echo (int) ( get_comments( array( 'status' => 'all', 'number' => 1, 'meta_key' => '_trust_demo_id', 'meta_value' => 'comment:post:robots:${ key }' ) )[0]->comment_ID ?? 0 );` );
// Комментарии теста (по метке в тексте): статус, родитель, email, согласие.
const testComments = () => JSON.parse( wp( 'eval', `echo wp_json_encode( array_map( static fn( $c ) => array( 'id' => (int) $c->comment_ID, 'approved' => $c->comment_approved, 'parent' => (int) $c->comment_parent, 'email' => $c->comment_author_email, 'author' => $c->comment_author, 'consent' => (string) get_comment_meta( $c->comment_ID, 'trust_consent', true ), 'text' => $c->comment_content ), get_comments( array( 'status' => 'all', 'search' => '${ MARK }', 'orderby' => 'comment_ID', 'order' => 'ASC' ) ) ) );` ) );
const cleanTests = () => wp( 'eval', `foreach ( get_comments( array( 'status' => 'all', 'search' => '${ MARK }' ) ) as $c ) { wp_delete_comment( $c->comment_ID, true ); }` );
// WordPress не принимает комментарии с одного IP чаще раза в 15 с: отодвигаем время тестовых назад.
const ageTests = () => wp( 'eval', `global $wpdb; $wpdb->query( "UPDATE {$wpdb->comments} SET comment_date_gmt = DATE_SUB( comment_date_gmt, INTERVAL 1 HOUR ), comment_date = DATE_SUB( comment_date, INTERVAL 1 HOUR ) WHERE comment_content LIKE '%${ MARK }%'" ); clean_comment_cache( $wpdb->get_col( "SELECT comment_ID FROM {$wpdb->comments}" ) );` );
const clearLikeLimits = () => wp( 'eval', "global $wpdb; foreach ( $wpdb->get_col( \"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_trust_like_ip_%'\" ) as $o ) { delete_transient( substr( $o, strlen( '_transient_' ) ) ); }" );

/** Состояние раздела комментариев. */
function state( page ) {
	return page.evaluate( () => {
		const text = ( el ) => ( el ? el.textContent.replace( /\s+/g, ' ' ).trim() : null );
		const visible = ( el ) => !! el && null !== el.offsetParent;
		const sec = document.querySelector( '.post-comments' );
		if ( ! sec ) {
			return null;
		}
		const form = document.getElementById( 'comment-form' );
		return {
			title: text( sec.querySelector( 'h2' ) ),
			labelledby: sec.getAttribute( 'aria-labelledby' ),
			form: form && {
				visible: visible( form ),
				labels: [ ...form.querySelectorAll( '.field__label' ) ].map( text ),
				email: form.elements.email && form.elements.email.type,
				consent: !! form.querySelector( 'input[name="consent"][required]' ),
				disabled: form.querySelector( '[type="submit"]' ).disabled,
				note: text( form.querySelector( '.comment-form__note' ) ),
				action: form.getAttribute( 'action' ),
			},
			closed: text( sec.querySelector( '.post-comments__closed' ) ),
			threads: [ ...sec.querySelectorAll( '.comments > .comment' ) ].filter( visible ).map( ( li ) => ( {
				id: li.id,
				author: text( li.querySelector( ':scope > .comment__body .comment__author' ) ),
				avatar: [ text( li.querySelector( ':scope > .comment__avatar' ) ), getComputedStyle( li.querySelector( ':scope > .comment__avatar' ) ).width ],
				date: li.querySelector( ':scope > .comment__body time' ).getAttribute( 'datetime' ),
				text: text( li.querySelector( ':scope > .comment__body > .comment__text' ) ),
				like: text( li.querySelector( ':scope > .comment__body [data-comment-like]' ) ),
				reply: !! li.querySelector( ':scope > .comment__body [data-comment-reply]' ),
				replies: [ ...li.querySelectorAll( '.comment--reply' ) ].map( ( r ) => [ text( r.querySelector( '.comment__author' ) ), text( r.querySelector( '.comment__badge' ) ), text( r.querySelector( '.comment__avatar' ) ), !! r.querySelector( '[data-comment-reply]' ), getComputedStyle( r.querySelector( '.comment__avatar' ) ).backgroundColor ] ),
			} ) ),
			all: sec.querySelectorAll( '.comments > .comment' ).length,
			more: text( sec.querySelector( '[data-comments-more]' ) ),
			toc: [ ...document.querySelectorAll( '.post-side .post-toc__link' ) ].map( ( a ) => [ text( a ), a.hash ] ).pop(),
			aside: document.querySelector( '.post-side__comments' ) && [ text( document.querySelector( '.post-side__comments' ) ), document.querySelector( '.post-side__comments' ).hash ],
			hero: document.querySelector( '.post-hero__count--comments a' ) && [ text( document.querySelector( '.post-hero__count--comments a' ) ), document.querySelector( '.post-hero__count--comments a' ).hash ],
			heroText: text( document.querySelector( '.post-hero__count--comments' ) ),
			gravatar: [ ...document.querySelectorAll( 'img' ) ].some( ( img ) => /gravatar/.test( img.src ) ),
		};
	} );
}

( async () => {
	clearLog();
	resetFormLimits();
	clearLikeLimits();
	cleanTests();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 }, userAgent: BROWSER_UA } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const requests = [];
	page.on( 'request', ( r ) => requests.push( r.url() ) );
	const saved = getSettings( SETTINGS );
	const id = postId();
	const first = demoComment( 'filters' );
	const firstReply = demoComment( 'filters-answer' );
	const savedLikes = wp( 'comment', 'meta', 'get', first, 'trust_likes' );
	const temp = [];

	try {
		// ===== Раздел комментариев =====
		await page.goto( POST, { waitUntil: 'networkidle' } );
		const s = await state( page );
		check( 'Раздел: H2 «Комментарии 12» (aria-labelledby), форма — имя, «Email — не публикуется», комментарий, согласие; «Отправить» неактивна, пока текст пустой; подпись о модерации; без JavaScript — admin-post.php',
			s && 'Комментарии 12' === s.title && 'comments' === s.labelledby && s.form.visible && JSON.stringify( s.form.labels ) === JSON.stringify( [ 'Имя', 'Email — не публикуется', 'Комментарий' ] ) &&
			'email' === s.form.email && s.form.consent && true === s.form.disabled && 'Комментарий появится после проверки модератором.' === s.form.note && s.form.action.endsWith( '/wp-admin/admin-post.php' ),
			JSON.stringify( s && s.form ) );
		check( 'Список: новые ветки сверху, видно 3 из 8 (как в макете); ответы автора — со сдвигом, плашка «Автор», инициалы «ИФ» на фиолетовом; у ответов нет «Ответить»',
			3 === s.threads.length && 8 === s.all && s.threads[ 0 ].text.startsWith( 'Если закрыть страницы фильтров' ) && s.threads[ 1 ].text.startsWith( 'Нужно ли писать' ) && s.threads[ 2 ].text.startsWith( 'У нас магазин' ) &&
			s.threads[ 0 ].date >= s.threads[ 1 ].date && JSON.stringify( s.threads[ 0 ].replies ) === JSON.stringify( [ [ '[Имя Фамилия]', 'Автор', 'ИФ', false, 'rgb(109, 40, 217)' ] ] ) && s.threads.every( ( t ) => t.reply ),
			JSON.stringify( s.threads.map( ( t ) => [ t.text.slice( 0, 20 ), t.date, t.replies ] ) ) );
		check( 'Комментарий: аватар — инициалы «ИЧ» в круге 44 px, имя, дата, текст, «Нравится: 5»; изображений Gravatar нет, запросов к Gravatar нет',
			JSON.stringify( s.threads[ 0 ].avatar ) === JSON.stringify( [ 'ИЧ', '44px' ] ) && '[Имя читателя]' === s.threads[ 0 ].author && /^\d{4}-\d\d-\d\d$/.test( s.threads[ 0 ].date ) && 'Нравится: 5' === s.threads[ 0 ].like &&
			! s.gravatar && ! requests.some( ( u ) => u.includes( 'gravatar' ) ) && '0' === wp( 'option', 'get', 'show_avatars' ),
			JSON.stringify( s.threads[ 0 ] ) );
		check( 'В статье: последний пункт содержания «Комментарии · 12», кнопка «Комментарии: 12» рядом с «Нравится», «12 комментариев» в первом экране — ссылки на #comments',
			JSON.stringify( s.toc ) === JSON.stringify( [ '08Комментарии · 12', '#comments' ] ) && JSON.stringify( s.aside ) === JSON.stringify( [ 'Комментарии: 12', '#comments' ] ) && JSON.stringify( s.hero ) === JSON.stringify( [ '12 комментариев', '#comments' ] ),
			JSON.stringify( [ s.toc, s.aside, s.hero ] ) );

		await page.click( '[data-comments-more]' );
		const more = await state( page );
		const focused = await page.evaluate( () => document.activeElement.id );
		check( '«Показать ещё 8 комментариев» открывает остальные 5 веток, кнопка исчезает, фокус — на первой открытой',
			'Показать ещё 8 комментариев' === s.more && 8 === more.threads.length && null === more.more && more.threads[ 3 ].id === focused, JSON.stringify( [ s.more, more.threads.length, more.more, focused ] ) );
		await page.goto( `${ POST }#comment-${ demoComment( 'pdf' ) }`, { waitUntil: 'networkidle' } );
		const hashOpen = await page.evaluate( ( cid ) => null !== document.getElementById( `comment-${ cid }` ).offsetParent, demoComment( 'pdf' ) );
		check( 'Ссылка на скрытый комментарий (#comment-N) открывает все ветки', hashOpen );

		// ===== Проверка полей и отправка =====
		await page.goto( POST, { waitUntil: 'networkidle' } );
		await page.fill( '#comment-text', `${ MARK } пустые поля` );
		const enabled = await page.evaluate( () => ! document.querySelector( '#comment-form [type="submit"]' ).disabled );
		await page.click( '#comment-form [type="submit"]' );
		await page.waitForTimeout( 300 );
		const invalid = await page.evaluate( () => [ ...document.querySelectorAll( '#comment-form [aria-invalid="true"]' ) ].map( ( el ) => el.name ) );
		const errTexts = await page.evaluate( () => [ ...document.querySelectorAll( '#comment-form .field__error:not([hidden])' ) ].map( ( e ) => e.textContent.trim() ) );
		check( 'С текстом кнопка активна; пустые имя, email и без согласия — ошибки под полями, отправки нет',
			enabled && JSON.stringify( invalid ) === JSON.stringify( [ 'name', 'email', 'consent' ] ) && errTexts.includes( 'Укажите, как к вам обращаться' ) && 0 === testComments().length, JSON.stringify( [ invalid, errTexts ] ) );

		await page.fill( '#comment-name', 'Тест Тестов' );
		await page.fill( '#comment-email', 'reader-test@example.com' );
		await page.check( '#comment-form input[name="consent"]' );
		await page.fill( '#comment-text', `${ MARK } первый: а как закрыть поддомен?` );
		await page.waitForTimeout( 3300 );
		await page.click( '#comment-form [type="submit"]' );
		await page.waitForSelector( '#comment-sent:not([hidden])', { timeout: 15000 } );
		const sentState = await page.evaluate( () => [ document.getElementById( 'comment-form' ).hidden, document.getElementById( 'comment-sent' ).textContent.replace( /\s+/g, ' ' ).trim(), document.activeElement.id ] );
		const db1 = testComments();
		check( 'Отправка: вместо формы «Спасибо! Комментарий появится после проверки модератором.» (фокус на сообщении)',
			true === sentState[ 0 ] && sentState[ 1 ].startsWith( 'Спасибо! Комментарий появится после проверки модератором.' ) && 'comment-sent' === sentState[ 2 ], JSON.stringify( sentState ) );
		check( 'В базе: комментарий на модерации (не одобрен), имя, email, дата согласия; на сайте не виден',
			1 === db1.length && '0' === db1[ 0 ].approved && 'reader-test@example.com' === db1[ 0 ].email && 'Тест Тестов' === db1[ 0 ].author && /^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d/.test( db1[ 0 ].consent ) && 0 === db1[ 0 ].parent,
			JSON.stringify( db1 ) );
		await page.click( '[data-comment-again]' );
		const again = await page.evaluate( () => [ document.getElementById( 'comment-form' ).hidden, document.getElementById( 'comment-sent' ).hidden, document.activeElement.id, document.getElementById( 'comment-name' ).value, document.getElementById( 'comment-text' ).value ] );
		check( '«Написать ещё» возвращает форму: имя сохранено, текст пустой, фокус в поле комментария', JSON.stringify( again ) === JSON.stringify( [ false, true, 'comment-text', 'Тест Тестов', '' ] ), JSON.stringify( again ) );
		ageTests();

		// ===== Ответ под комментарием =====
		const replyBtn = page.locator( `#comment-${ first } [data-comment-reply]` );
		await replyBtn.click();
		const opened = await page.evaluate( ( cid ) => {
			const box = document.getElementById( 'comment-reply' );
			return [ ! box.hidden, box.parentElement.closest( '.comment' ).id === `comment-${ cid }`, document.querySelector( `#comment-${ cid } [data-comment-reply]` ).getAttribute( 'aria-expanded' ), document.activeElement.id, document.getElementById( 'comment-reply-name' ).value, document.getElementById( 'comment-reply-email' ).value, document.querySelector( '#comment-reply-form [name="comment_parent"]' ).value ];
		}, first );
		await page.keyboard.press( 'Escape' );
		const escaped = await page.evaluate( ( cid ) => [ document.getElementById( 'comment-reply' ).hidden, document.activeElement === document.querySelector( `#comment-${ cid } [data-comment-reply]` ) ], first );
		check( '«Ответить»: форма ответа — под комментарием, кнопка aria-expanded, фокус в тексте, имя и email — из основной формы; Esc закрывает, фокус на «Ответить»',
			JSON.stringify( opened ) === JSON.stringify( [ true, true, 'true', 'comment-reply-text', 'Тест Тестов', 'reader-test@example.com', first ] ) && JSON.stringify( escaped ) === JSON.stringify( [ true, true ] ),
			JSON.stringify( [ opened, escaped ] ) );
		await replyBtn.click();
		await page.fill( '#comment-reply-text', `${ MARK } ответ на первый` );
		await page.check( '#comment-reply-form input[name="consent"]' );
		await page.waitForTimeout( 3300 );
		await page.click( '#comment-reply-form [type="submit"]' );
		await page.waitForSelector( '#comment-reply-sent:not([hidden])', { timeout: 15000 } );
		const replied = await page.evaluate( ( cid ) => [ document.getElementById( 'comment-reply' ).hidden, document.getElementById( 'comment-reply-sent' ).parentElement.closest( '.comment' ).id === `comment-${ cid }`, document.getElementById( 'comment-reply-sent' ).textContent.trim() ], first );
		const db2 = testComments();
		check( 'Ответ: «Ответ отправлен на модерацию.» под комментарием; в базе — ответ этому комментарию, на модерации',
			JSON.stringify( replied ) === JSON.stringify( [ true, true, 'Ответ отправлен на модерацию.' ] ) && 2 === db2.length && db2[ 1 ].parent === Number( first ) && '0' === db2[ 1 ].approved,
			JSON.stringify( [ replied, db2 ] ) );
		ageTests();

		// ===== REST: ответ на ответ, ошибки, ловушка =====
		resetFormLimits();
		const t = await formToken();
		await sleep( 3200 );
		const base = { _trust_token: t.token, _trust_nonce: t.nonce, name: 'Тест', email: 'rest@example.com', consent: '1', comment_post_ID: id };
		const deep = await postForm( 'comment', { ...base, comment_parent: firstReply, comment: `${ MARK } ответ на ответ` } );
		ageTests();
		const noConsent = await postForm( 'comment', { ...base, consent: '', comment: `${ MARK } без согласия` } );
		const trap = await postForm( 'comment', { ...base, trust_website: 'http://spam.example', comment: `${ MARK } бот` } );
		const draft = wp( 'post', 'create', '--post_type=post', '--post_status=draft', '--post_title=Черновик комментариев', '--porcelain' );
		temp.push( draft );
		const toDraft = await postForm( 'comment', { ...base, comment_post_ID: draft, comment: `${ MARK } в черновик` } );
		const closed = wp( 'post', 'create', '--post_type=post', '--post_status=publish', '--comment_status=closed', '--post_title=Закрытые комментарии', '--porcelain' );
		temp.push( closed );
		const toClosed = await postForm( 'comment', { ...base, comment_post_ID: closed, comment: `${ MARK } в закрытые` } );
		const db3 = testComments();
		const deepRow = db3.find( ( c ) => c.text.includes( 'ответ на ответ' ) );
		check( 'Ответ на ответ сохраняется ответом исходному комментарию (два уровня)', 200 === deep.status && deepRow && deepRow.parent === Number( first ), JSON.stringify( [ deep, deepRow ] ) );
		check( 'Без согласия — 400 и ошибка поля; ловушка заполнена — «успешно», но не сохранено; черновик — 404; закрытые комментарии — 403',
			400 === noConsent.status && noConsent.body.errors.consent && 200 === trap.status && true === trap.body.ok && ! db3.some( ( c ) => c.text.includes( 'бот' ) ) &&
			404 === toDraft.status && 403 === toClosed.status && 'closed' === toClosed.body.code && ! db3.some( ( c ) => c.text.includes( 'без согласия' ) ),
			JSON.stringify( [ noConsent.status, trap.status, toDraft.status, toClosed.status ] ) );
		const direct = await fetch( `${ URL }/wp-comments-post.php`, { method: 'POST', body: new URLSearchParams( { comment_post_ID: id, author: 'Bot', email: 'bot@example.com', comment: `${ MARK } в обход` } ), redirect: 'manual' } );
		await direct.text();
		check( 'Отправка в обход формы (wp-comments-post.php) — 403, не сохранено', 403 === direct.status && ! testComments().some( ( c ) => c.text.includes( 'в обход' ) ), String( direct.status ) );
		const leadTypes = wp( 'eval', "echo wp_json_encode( array_keys( array_filter( Trust\\Forms::types(), static fn( $t ) => false !== ( $t['lead'] ?? true ) ) ) );" );
		check( 'Комментарии не попадают в «Заявки» (форма без заявки)', ! JSON.parse( leadTypes ).includes( 'comment' ) && '0' === wp( 'eval', `echo count( get_posts( array( 'post_type' => 'lead', 'post_status' => 'any', 's' => '${ MARK }', 'fields' => 'ids' ) ) );` ), leadTypes );
		ageTests();

		// ===== Без JavaScript =====
		const noJs = await browser.newContext( { javaScriptEnabled: false } );
		const nj = await noJs.newPage();
		await nj.goto( POST );
		const njVisible = await nj.evaluate( () => [ getComputedStyle( document.querySelector( '.comment__reply' ) ).display, document.querySelectorAll( '.comments > .comment' ).length, [ ...document.querySelectorAll( '.comments > .comment' ) ].every( ( li ) => 'none' !== getComputedStyle( li ).display ), getComputedStyle( document.querySelector( '[data-comments-more]' ) ).display ] );
		await nj.fill( '#comment-name', 'Без скрипта' );
		await nj.fill( '#comment-email', 'nojs@example.com' );
		await nj.fill( '#comment-text', `${ MARK } без JavaScript` );
		await nj.check( '#comment-form input[name="consent"]', { force: true } );
		await sleep( 3200 );
		await Promise.all( [ nj.waitForNavigation(), nj.click( '#comment-form [type="submit"]' ) ] );
		const back = new globalThis.URL( nj.url() );
		const njSent = await nj.evaluate( () => [ ! document.getElementById( 'comment-sent' ).hidden, document.getElementById( 'comment-form' ).hidden ] );
		await noJs.close();
		check( 'Без JavaScript: все ветки видны, «Ответить» и «Показать ещё» скрыты; отправка через admin-post.php — возврат к #comments со «Спасибо!…», комментарий сохранён',
			'none' === njVisible[ 0 ] && 8 === njVisible[ 1 ] && njVisible[ 2 ] && 'none' === njVisible[ 3 ] && 'sent' === back.searchParams.get( 'trust_form' ) && '#comments' === back.hash && JSON.stringify( njSent ) === JSON.stringify( [ true, true ] ) &&
			testComments().some( ( c ) => c.text.includes( 'без JavaScript' ) && 'nojs@example.com' === c.email ),
			JSON.stringify( [ njVisible, nj.url(), njSent ] ) );
		ageTests();

		// ===== Лайки комментариев =====
		clearLikeLimits();
		await page.goto( POST, { waitUntil: 'networkidle' } );
		await page.click( `#comment-${ first } > .comment__body [data-comment-like]` );
		await page.waitForTimeout( 700 );
		const like1 = await page.evaluate( ( cid ) => { const b = document.querySelector( `#comment-${ cid } > .comment__body [data-comment-like]` ); return [ b.getAttribute( 'aria-pressed' ), b.textContent.replace( /\s+/g, ' ' ).trim() ]; }, first );
		const cookie = ( await context.cookies() ).find( ( c ) => 'trust_comment_likes' === c.name );
		const meta1 = wp( 'comment', 'meta', 'get', first, 'trust_likes' );
		await page.reload( { waitUntil: 'networkidle' } );
		await page.waitForTimeout( 500 );
		const like2 = await page.evaluate( ( cid ) => { const b = document.querySelector( `#comment-${ cid } > .comment__body [data-comment-like]` ); return [ b.getAttribute( 'aria-pressed' ), b.textContent.replace( /\s+/g, ' ' ).trim() ]; }, first );
		await page.click( `#comment-${ first } > .comment__body [data-comment-like]` );
		await page.waitForTimeout( 700 );
		const meta2 = wp( 'comment', 'meta', 'get', first, 'trust_likes' );
		const stats = await ( await fetch( `${ URL }/wp-json/trust/v1/posts/${ id }` ) ).json();
		const pending = db3[ 0 ].id;
		const pendingLike = await fetch( `${ URL }/wp-json/trust/v1/comments/${ pending }/like`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify( { liked: true } ) } );
		await pendingLike.text();
		check( 'Лайк комментария: нажата, «Нравится: 6», на сервере 6, cookie trust_comment_likes; после перезагрузки помнится; повторное нажатие — 5',
			JSON.stringify( like1 ) === JSON.stringify( [ 'true', 'Нравится: 6' ] ) && String( Number( savedLikes ) + 1 ) === meta1 && cookie && cookie.value.split( '.' ).includes( first ) &&
			JSON.stringify( like2 ) === JSON.stringify( [ 'true', 'Нравится: 6' ] ) && savedLikes === meta2,
			JSON.stringify( [ like1, meta1, like2, meta2, cookie && cookie.value ] ) );
		check( 'GET статьи отдаёт лайки комментариев; лайк неодобренного комментария — 404', stats.comments && Number( savedLikes ) === stats.comments[ first ] && 404 === pendingLike.status, JSON.stringify( [ stats.comments, pendingLike.status ] ) );

		// ===== Модератор: комментарий публикуется сразу =====
		const admin = await browser.newPage( { viewport: { width: 1280, height: 900 } } );
		await login( admin );
		await admin.goto( POST, { waitUntil: 'networkidle' } );
		await admin.fill( '#comment-name', 'Модератор' );
		await admin.fill( '#comment-email', 'moderator@example.com' );
		await admin.fill( '#comment-text', `${ MARK } от модератора` );
		await admin.check( '#comment-form input[name="consent"]' );
		await admin.waitForTimeout( 3300 );
		await Promise.all( [ admin.waitForURL( /[?&]comment=\d+#comment-\d+$/, { timeout: 20000 } ), admin.click( '#comment-form [type="submit"]' ) ] );
		await admin.waitForLoadState( 'networkidle' );
		const modId = new globalThis.URL( admin.url() ).searchParams.get( 'comment' );
		const modVisible = await admin.evaluate( ( cid ) => { const el = document.getElementById( `comment-${ cid }` ); return !! el && null !== el.offsetParent && el.textContent.includes( 'от модератора' ); }, modId );
		const discussion = [ wp( 'option', 'get', 'comment_moderation' ), wp( 'option', 'get', 'thread_comments' ), wp( 'option', 'get', 'thread_comments_depth' ) ];
		await admin.close();
		check( 'Модератор: комментарий публикуется сразу — страница открывается на нём', !! modId && modVisible && testComments().some( ( c ) => c.id === Number( modId ) && '1' === c.approved ), admin.url() );
		check( '«Обсуждение» по умолчанию (один раз): модерация вручную, древовидные комментарии на 2 уровня', JSON.stringify( discussion ) === JSON.stringify( [ '1', '1', '2' ] ), JSON.stringify( discussion ) );
		cleanTests();

		// ===== Настройки и закрытые комментарии =====
		setSettings( { blog_comments_shown: 5, blog_show_likes: 0, tpl_post_comments: 'Обсуждение' } );
		await page.goto( POST, { waitUntil: 'networkidle' } );
		const set = await state( page );
		restoreSettings( saved );
		check( '«Комментариев сразу» = 5: видно 5 веток, «Показать ещё 4 комментария»; «Показывать лайки» выключено — у комментариев нет «Нравится»; «Тексты шаблонов» — H2 и пункт содержания',
			5 === set.threads.length && 'Показать ещё 4 комментария' === set.more && set.threads.every( ( t ) => null === t.like ) && 'Обсуждение 12' === set.title && '08Обсуждение · 12' === set.toc[ 0 ],
			JSON.stringify( [ set.threads.length, set.more, set.title, set.toc ] ) );
		wp( 'post', 'update', id, '--comment_status=closed' );
		await page.goto( POST, { waitUntil: 'networkidle' } );
		const shut = await state( page );
		wp( 'post', 'update', id, '--comment_status=open' );
		check( 'Комментарии закрыты: формы нет — «Комментарии к статье закрыты.», список остаётся, «Ответить» нет',
			null === shut.form && 'Комментарии к статье закрыты.' === shut.closed && 3 === shut.threads.length && shut.threads.every( ( t ) => ! t.reply ), JSON.stringify( [ shut.form, shut.closed, shut.threads.length ] ) );
		const closedPath = new globalThis.URL( wp( 'eval', `echo get_permalink( ${ closed } );` ) ).pathname;
		await page.goto( URL + closedPath, { waitUntil: 'networkidle' } );
		const none = await state( page );
		const noneExtra = await page.evaluate( () => [ !! document.querySelector( '.post-side__comments' ), [ ...document.querySelectorAll( '.post-toc__link' ) ].some( ( a ) => '#comments' === a.hash ), !! document.querySelector( '.post-hero__count--comments a' ) ] );
		check( 'Закрыты и комментариев нет: раздела нет, нет пункта содержания, кнопки и ссылки в первом экране', null === none && JSON.stringify( noneExtra ) === JSON.stringify( [ false, false, false ] ), JSON.stringify( noneExtra ) );

		// ===== Телефон =====
		const phone = await browser.newPage( { viewport: { width: 390, height: 844 }, userAgent: BROWSER_UA } );
		await phone.goto( POST, { waitUntil: 'networkidle' } );
		const mob = await phone.evaluate( () => {
			const row = document.querySelector( '#comment-form .comment-form__row' ).children;
			const reply = document.querySelector( '.comment__replies' );
			return [ row[ 0 ].getBoundingClientRect().top < row[ 1 ].getBoundingClientRect().top, getComputedStyle( reply ).paddingLeft, getComputedStyle( document.querySelector( '.comment__avatar' ) ).width, document.documentElement.scrollWidth <= innerWidth ];
		} );
		await phone.locator( '.comments > .comment [data-comment-reply]' ).first().click();
		const mobReply = await phone.evaluate( () => { const box = document.getElementById( 'comment-reply' ); const r = box.getBoundingClientRect(); return [ ! box.hidden, r.right <= innerWidth, document.documentElement.scrollWidth <= innerWidth ]; } );
		await phone.close();
		check( 'Телефон 390 px: имя и email — друг под другом, ответы со сдвигом 10 px, аватар 36 px, форма ответа помещается, без прокрутки вбок',
			JSON.stringify( mob ) === JSON.stringify( [ true, '10px', '36px', true ] ) && JSON.stringify( mobReply ) === JSON.stringify( [ true, true, true ] ), JSON.stringify( [ mob, mobReply ] ) );

		// ===== Сверка списка с макетом =====
		// Даты демо — от дня импорта, у читателей в макете — силуэт: при сверке — как в макете.
		const silhouette = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6D28D9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"></path></svg>';
		for ( const width of [ 1280, 390 ] ) {
			const base = path.join( OUT, `comments-list-${ width }` );
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( POST, { waitUntil: 'networkidle' } );
			await settle( page );
			await page.evaluate( ( svg ) => {
				const dates = [ '6 ноября 2026', '6 ноября 2026', '5 ноября 2026', '5 ноября 2026' ];
				[ ...document.querySelectorAll( '.comments time' ) ].slice( 0, 4 ).forEach( ( t, i ) => ( t.textContent = dates[ i ] ) );
				document.querySelectorAll( '.comments > .comment > .comment__avatar' ).forEach( ( a ) => {
					a.innerHTML = svg;
					a.style.setProperty( '--avatar-bg', '#EEE7FE' );
				} );
			}, silhouette );
			await isolate( page, '.comments' );
			await page.waitForTimeout( 200 );
			await page.locator( '.comments' ).screenshot( { path: `${ base }-theme.png` } );
			await page.goto( mockupUrl( 'seo-blog/statya.html' ), { waitUntil: 'networkidle' } );
			await settle( page );
			await isolate( page, 'section[aria-labelledby="s-comments"] > ol' );
			await page.waitForTimeout( 200 );
			await page.locator( 'section[aria-labelledby="s-comments"] > ol' ).screenshot( { path: `${ base }-mockup.png` } );
			const diff = comparePng( `${ base }-mockup.png`, `${ base }-theme.png`, `${ base }-diff.png` );
			check( `Макет seo-blog/statya.html, список комментариев, ${ width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
		}

		// ===== Доступность, консоль =====
		for ( const w of [ 1280, 390 ] ) {
			await page.setViewportSize( { width: w, height: 900 } );
			await page.goto( POST, { waitUntil: 'networkidle' } );
			await page.click( '[data-comments-more]' );
			await page.locator( '.comments > .comment [data-comment-reply]' ).first().click();
			await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
			const axe = await page.evaluate( async () => ( await window.axe.run( document.querySelector( '.post-comments' ), { resultTypes: [ 'violations' ] } ) ).violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => v.id ) );
			check( `axe, раздел комментариев с открытой формой ответа, ${ w }px: нет нарушений critical/serious`, 0 === axe.length, axe.join( ', ' ) );
		}
		check( 'Консоль браузера без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );
	} finally {
		restoreSettings( saved );
		cleanTests();
		wp( 'comment', 'meta', 'update', first, 'trust_likes', savedLikes );
		wp( 'post', 'update', id, '--comment_status=open' );
		temp.forEach( ( t ) => wp( 'post', 'delete', t, '--force' ) );
		resetFormLimits();
		clearLikeLimits();
	}
	checkLog();
	await browser.close();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
