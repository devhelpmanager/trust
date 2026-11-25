/**
 * Тест блоков шага 4.14 — страница «Контакты»: «Приезжайте в гости», «Кому написать»,
 * «Расскажите о задаче», «Реквизиты»; вкладка «Настройки сайта → Реквизиты».
 *
 * Запуск: node wp-theme/tests/contacts-blocks.js
 *
 * Демо на /kontakty/ (порядок и тексты из макета o-nas/kontakty.html): офис — адрес, как
 * добраться, часы, кнопки карт из «Настроек → Контакты», схема-заглушка с меткой; интерактивная
 * карта — только по кнопке «Показать карту»; картинка схемы из настроек; пустой пункт не выводится.
 * Отделы — 5 карточек (email, телефон, ссылка в тексте) и «Письмо руководителю» (имя и email
 * из карточки сотрудника). Форма — ошибки как в макете, способ связи, «Заявка отправлена» поверх
 * формы с текстом по способу, заявка «Заявка с контактов» (поле «Ваши контакты»). Реквизиты —
 * 10 строк из настроек, «Скопировать» (буфер обмена, «Скопировано»), «Скачать PDF»; на узком
 * экране кнопки под таблицей в одну строку. Сверка с макетом (с правками заказчика) на
 * 1280 / 768 / 390 px, ширины 1440–280, axe-core, консоль, редактор.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp, resetFormLimits, getSettings, setSettings } = require( './lib' );

const PAGE = `${ URL }/kontakty/`;
const MOCKUP = 'o-nas/kontakty.html';
const MARK = 'ТестКонтакты';

/** PHP: блок по имени и полям (PHP-массив). */
const block = ( name, fields ) => `Trust\\Blocks::serialize( '${ name }', ${ fields } )`;

/**
 * Опубликованная тестовая страница «Контакты» с блоками.
 *
 * @param {string} title  Заголовок.
 * @param {string} blocks PHP-выражение: разметка блоков.
 * @return {string} ID.
 */
function createPage( title, blocks ) {
	const content = wp( 'eval', `echo ${ blocks };` );
	const id = wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK } ${ title }`, '--post_status=publish', `--post_content=${ content }`, '--porcelain' );
	wp( 'post', 'meta', 'update', id, '_wp_page_template', 'templates/contacts.php' );
	return id;
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
	const base = path.join( OUT, `contacts-${ name }-${ width }` );
	await page.setViewportSize( { width, height: 900 } );
	await page.goto( PAGE, { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
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
	await context.grantPermissions( [ 'clipboard-read', 'clipboard-write' ], { origin: URL } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const created = [];
	const saved = getSettings( [ 'map_embed', 'map_image', 'directions_note', 'requisites' ] );

	try {
		// ===== Демо-страница =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const order = await page.evaluate( () => [ ...document.querySelectorAll( '.b-hero-contacts, .b-omap, .b-depts, .b-cform, .b-req' ) ].map( ( s ) => s.classList[ 0 ] ) );
		check( 'Порядок блоков как в макете: первый экран, офис, отделы, заявка, реквизиты', 'b-hero-contacts|b-omap|b-depts|b-cform|b-req' === order.join( '|' ), JSON.stringify( order ) );

		// ===== Приезжайте в гости =====
		const office = await page.evaluate( () => {
			const b = document.querySelector( '.b-omap' );
			const map = b.querySelector( '.b-omap__map' );
			return {
				head: b.querySelector( '.eyebrow' ).textContent + '|' + b.querySelector( 'h2' ).textContent,
				address: b.querySelector( '.b-omap__address' ).textContent,
				items: [ ...b.querySelectorAll( '.b-omap__list li' ) ].map( ( li ) => li.querySelector( 'strong' ).textContent + ':' + li.querySelector( 'strong' ).nextSibling.textContent ),
				links: [ ...b.querySelectorAll( '.b-omap__link' ) ].map( ( a ) => a.firstChild.textContent.trim() + '>' + a.getAttribute( 'href' ) + '>' + a.target ),
				map: [ map.getAttribute( 'role' ), map.getAttribute( 'aria-label' ), !! map.querySelector( '.b-omap__scheme' ), b.querySelector( '.b-omap__marker-label' )?.textContent, !! b.querySelector( '.b-omap__show' ), !! b.querySelector( 'iframe' ) ],
			};
		} );
		check( '«Приезжайте в гости»: шапка, адрес, «Метро», «На машине», «Часы работы», «Пропуск» и кнопки карт (новая вкладка) — из «Настроек → Контакты»',
			'Офис|Приезжайте в гости' === office.head && '[Город], [улица, дом], [этаж, офис]' === office.address
			&& 'Метро:[Станция], [N] минут пешком|На машине:[Где оставить машину: гостевая парковка, въезд с улицы]|Часы работы:[Пн–Пт, 10:00–19:00]|Пропуск:Перед визитом позвоните — закажем пропуск в бизнес-центр' === office.items.join( '|' )
			&& 'Открыть в Яндекс Картах>https://yandex.ru/maps/>_blank|Открыть в 2ГИС>https://2gis.ru/>_blank' === office.links.join( '|' ), JSON.stringify( office ) );
		check( 'Без картинки и кода карты — схема-заглушка (role=img, подпись), метка «[Бренд] · офис», кнопки «Показать карту» нет',
			JSON.stringify( office.map ) === JSON.stringify( [ 'img', 'Схема проезда к офису', true, '[Бренд] · офис', false, false ] ), JSON.stringify( office.map ) );

		// Код карты: загружается только по кнопке.
		const mapRequests = [];
		await page.route( 'https://maps.example.org/**', ( route ) => {
			mapRequests.push( route.request().url() );
			route.fulfill( { contentType: 'text/html', body: '<!doctype html><title>Карта</title>' } );
		} );
		setSettings( { map_embed: '<iframe src="https://maps.example.org/embed" title="Карта офиса" width="100%" height="400"></iframe>', directions_note: '' } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const before = await page.evaluate( () => [ !! document.querySelector( '.b-omap__show' ), document.querySelectorAll( '.b-omap iframe' ).length, [ ...document.querySelectorAll( '.b-omap__list strong' ) ].map( ( s ) => s.textContent ).join( '|' ) ] );
		const requestsBefore = mapRequests.length;
		await page.locator( '.b-omap__show' ).click();
		await page.waitForSelector( '.b-omap iframe' );
		await page.waitForTimeout( 300 );
		const after = await page.evaluate( () => {
			const map = document.querySelector( '.b-omap__map' );
			return [ map.classList.contains( 'is-live' ), map.hasAttribute( 'role' ), document.querySelector( '.b-omap iframe' ).title, document.activeElement.classList.contains( 'b-omap__live' ) ];
		} );
		check( 'Код карты задан: кнопка «Показать карту»; до нажатия карты нет и запросов к сервису нет; по нажатию — карта вместо схемы',
			true === before[ 0 ] && 0 === before[ 1 ] && 0 === requestsBefore && JSON.stringify( after ) === JSON.stringify( [ true, false, 'Карта офиса', true ] ) && mapRequests.length >= 1, JSON.stringify( [ before, requestsBefore, after, mapRequests.length ] ) );
		check( 'Пустое «Как добраться: примечание» — пункта «Пропуск» нет', 'Метро|На машине|Часы работы' === before[ 2 ], before[ 2 ] );
		await page.unroute( 'https://maps.example.org/**' );
		const poster = wp( 'eval', 'echo get_posts( array( "post_type" => "attachment", "post_status" => "any", "numberposts" => 1, "fields" => "ids", "meta_key" => "_trust_demo_id", "meta_value" => "attachment:video-poster.jpg" ) )[0] ?? 0;' );
		setSettings( { map_embed: '', map_image: Number( poster ), directions_note: saved.directions_note } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const withImage = await page.evaluate( () => [ !! document.querySelector( '.b-omap__image' ), !! document.querySelector( '.b-omap__scheme' ), document.querySelector( '.b-omap__map' ).hasAttribute( 'role' ) ] );
		check( 'Схема проезда из настроек — картинка вместо заглушки (alt из медиатеки)', JSON.stringify( withImage ) === JSON.stringify( [ true, false, false ] ), JSON.stringify( withImage ) );
		setSettings( { map_image: saved.map_image || '' } );

		// ===== Кому написать =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const depts = await page.evaluate( () => {
			const b = document.querySelector( '.b-depts' );
			const cards = [ ...b.querySelectorAll( '.b-depts__card' ) ];
			return {
				head: b.querySelector( 'h2' ).textContent + '|' + b.querySelector( '.section-lead' ).textContent,
				titles: cards.map( ( c ) => c.querySelector( '.b-depts__title' ).textContent ),
				icons: cards.every( ( c ) => c.querySelector( '.b-depts__icon svg' ) ),
				links: cards.map( ( c ) => [ ...c.querySelectorAll( '.b-depts__link' ) ].map( ( a ) => a.getAttribute( 'href' ) ).join( ',' ) ),
				textLink: new URL( cards[ 3 ].querySelector( '.b-depts__text a' ).href ).pathname,
				leader: [ cards[ 5 ].classList.contains( 'b-depts__card--leader' ), cards[ 5 ].querySelector( '.b-depts__text' ).textContent, getComputedStyle( cards[ 5 ] ).backgroundColor ],
			};
		} );
		check( '«Кому написать»: шапка и 5 отделов со значками, последней — «Письмо руководителю»',
			'Кому написать|Напишите сразу в нужный отдел — так ответ придёт быстрее.' === depts.head && 'Новые проекты|Текущим клиентам|Бухгалтерия и документы|Работа у нас|Партнёрство и PR|Письмо руководителю' === depts.titles.join( '|' ) && depts.icons, JSON.stringify( depts ) );
		check( 'Контакты отделов: email и телефон (tel:), ссылка в тексте «Работа у нас» — на «Карьеру»',
			'mailto:sales@example.ru,tel:+10000000001|mailto:support@example.ru|mailto:buh@example.ru|mailto:hr@example.ru|mailto:pr@example.ru|mailto:ceo@example.ru' === depts.links.join( '|' ) && '/karera/' === depts.textLink, JSON.stringify( depts.links ) );
		check( '«Письмо руководителю»: тёмная карточка, имя и email — из карточки руководителя в «Сотрудниках»',
			depts.leader[ 0 ] && 'Если что-то пошло не так, напишите напрямую — письмо прочитает [Имя Фамилия] лично' === depts.leader[ 1 ] && 'rgb(30, 27, 75)' === depts.leader[ 2 ], JSON.stringify( depts.leader ) );

		// Правки заказчика: шапка во всю ширину, название справа от значка, без тегов h в карточках.
		const deptLayout = () => page.evaluate( () => {
			const b = document.querySelector( '.b-depts' );
			const inner = b.querySelector( '.b-depts__inner' ).getBoundingClientRect();
			const lead = b.querySelector( '.section-lead' ).getBoundingClientRect();
			return {
				headings: b.querySelectorAll( '.b-depts__list :is(h1, h2, h3, h4, h5, h6)' ).length,
				leadFull: Math.abs( lead.width - inner.width ) < 1,
				beside: [ ...b.querySelectorAll( '.b-depts__card' ) ].map( ( c ) => {
					const icon = c.querySelector( '.b-depts__icon' ).getBoundingClientRect();
					const title = c.querySelector( '.b-depts__title' ).getBoundingClientRect();
					const text = c.querySelector( '.b-depts__text' ).getBoundingClientRect();
					return title.left >= icon.right && Math.abs( ( title.top + title.bottom ) / 2 - ( icon.top + icon.bottom ) / 2 ) < 2 && text.top >= icon.bottom && text.left <= icon.left + 1;
				} ),
			};
		} );
		const deptDesk = await deptLayout();
		await page.setViewportSize( { width: 390, height: 900 } );
		const deptPhone = await deptLayout();
		await page.setViewportSize( { width: 1280, height: 900 } );
		check( '«Кому написать» (1280 и 390 px): заголовок и текст шапки во всю ширину, название отдела — справа от значка, текст под ними; в карточках нет тегов h (правки заказчика)',
			[ deptDesk, deptPhone ].every( ( d ) => 0 === d.headings && d.leadFull && 6 === d.beside.length && d.beside.every( Boolean ) ), JSON.stringify( [ deptDesk, deptPhone ] ) );

		// ===== Расскажите о задаче =====
		resetFormLimits();
		const form = page.locator( '.b-cform__form' );
		const cf = await page.evaluate( () => {
			const b = document.querySelector( '.b-cform' );
			return {
				id: b.id,
				steps: [ ...b.querySelectorAll( '.b-cform__steps li' ) ].map( ( li ) => li.textContent.trim() ),
				channels: [ ...b.querySelectorAll( 'input[name="channel"]' ) ].map( ( i ) => i.value + ( i.checked ? '*' : '' ) ),
				legend: b.querySelector( 'legend' ).textContent,
				contactLabel: b.querySelector( '[name="contact"]' ).labels[ 0 ].textContent.trim(),
			};
		} );
		check( '«Расскажите о задаче»: якорь #contact-form, шаги 1–3, поле «Ваши контакты» (правка заказчика), способы связи «Звонок» (выбран), Telegram, WhatsApp, Email',
			'contact-form' === cf.id && 3 === cf.steps.length && cf.steps[ 0 ].endsWith( 'Менеджер уточнит задачу и цели' ) && 'Звонок*|Telegram|WhatsApp|Email' === cf.channels.join( '|' ) && 'Как с вами связаться' === cf.legend && 'Ваши контакты' === cf.contactLabel, JSON.stringify( cf ) );
		await form.locator( '[type="submit"]' ).click();
		const errs = await form.locator( '.field__error:not([hidden])' ).allTextContents();
		check( 'Пустая форма: 3 сообщения как в макете, фокус на «Имя»',
			JSON.stringify( errs ) === JSON.stringify( [ 'Укажите, как к вам обращаться', 'Оставьте телефон или email', 'Отметьте согласие — без него мы не сможем ответить на заявку' ] ) && 'name' === await page.evaluate( () => document.activeElement.name ), JSON.stringify( errs ) );
		await form.locator( '[name="name"]' ).fill( `${ MARK } Пётр` );
		await form.locator( '[name="contact"]' ).fill( 'petr@example.com' );
		await form.locator( '[name="task"]' ).fill( 'Аудит и продвижение магазина мебели' );
		await page.locator( '.b-cform__option', { hasText: 'Telegram' } ).click();
		await form.locator( '[name="consent"]' ).check();
		await form.locator( '[type="submit"]' ).click();
		const sent = await page.locator( '.b-cform .form-sent' ).waitFor( { state: 'visible', timeout: 20000 } ).then( () => true ).catch( () => false );
		const sentState = await page.evaluate( () => {
			const icon = document.querySelector( '.b-cform .form-success__icon' ).getBoundingClientRect();
			const t = document.querySelector( '.b-cform .form-success__title' ).getBoundingClientRect();
			return [ document.querySelector( '.b-cform .form-success__title' ).textContent, document.querySelector( '.b-cform .form-success__text' ).textContent, document.querySelector( '.b-cform__form' ).inert, t.left > icon.right ];
		} );
		check( 'После отправки — «Заявка отправлена» поверх формы, значок слева; текст по способу: «Менеджер напишет в Telegram…»',
			sent && JSON.stringify( sentState ) === JSON.stringify( [ 'Заявка отправлена', 'Спасибо! Менеджер напишет в Telegram и уточнит детали задачи.', true, true ] ), JSON.stringify( sentState ) );
		const lead = JSON.parse( wp( 'eval', `$l = get_posts( array( 'post_type' => 'lead', 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => 'trust_name', 'meta_value' => '${ MARK } Пётр' ) ); $id = $l ? $l[0]->ID : 0; $f = $id ? (array) get_post_meta( $id, 'trust_fields', true ) : array(); echo wp_json_encode( array( $id, get_post_meta( $id, 'trust_form', true ), array_map( static fn( $x ) => $x['label'] . '=' . $x['value'], array_values( $f ) ) ) );` ) );
		if ( lead[ 0 ] ) {
			created.push( String( lead[ 0 ] ) );
		}
		check( 'Заявка «Заявка с контактов»: имя, контакт, задача, способ связи',
			'contact' === lead[ 1 ] && JSON.stringify( lead[ 2 ] ) === JSON.stringify( [ `Имя=${ MARK } Пётр`, 'Ваши контакты=petr@example.com', 'Задача=Аудит и продвижение магазина мебели', 'Как связаться=Telegram' ] ), JSON.stringify( lead ) );
		await page.locator( '.b-cform .form-sent__again' ).click();
		await page.waitForTimeout( 200 );
		const reset = await page.evaluate( () => [ document.querySelector( '.b-cform .form-sent' ).hidden, document.querySelector( '.b-cform input[name="channel"]:checked' ).value, document.querySelector( '.b-cform .form-success__text' ).textContent ] );
		check( '«Отправить ещё одну заявку» — пустая форма, снова «Звонок», текст «…позвонит по телефону…»',
			JSON.stringify( reset ) === JSON.stringify( [ true, 'Звонок', 'Спасибо! Менеджер позвонит по телефону и уточнит детали задачи.' ] ), JSON.stringify( reset ) );

		// ===== Реквизиты =====
		const req = await page.evaluate( () => {
			const b = document.querySelector( '.b-req' );
			return {
				head: b.querySelector( '.eyebrow' ).textContent + '|' + b.querySelector( 'h2' ).textContent,
				rows: [ ...b.querySelectorAll( '.b-req__row' ) ].map( ( r ) => r.querySelector( 'dt' ).textContent ),
				buttons: [ b.querySelector( '.b-req__copy' ).textContent.trim(), b.querySelector( '.b-req__card' ).textContent.trim() ],
				card: [ new URL( b.querySelector( '.b-req__card' ).href ).pathname.endsWith( '.pdf' ), b.querySelector( '.b-req__card' ).hasAttribute( 'download' ), b.querySelector( '.b-req__card' ).target ],
			};
		} );
		check( '«Реквизиты»: 10 строк из «Настроек → Реквизиты» таблицей <dl>, кнопки «Скопировать» и «Скачать PDF» (правки заказчика; PDF скачивается)',
			'Для договора и счетов|Реквизиты' === req.head && 'Полное наименование|Генеральный директор|ИНН / КПП|ОГРН|Юридический адрес|Почтовый адрес|Расчётный счёт|Банк|БИК|Корр. счёт' === req.rows.join( '|' ) && 'Скопировать|Скачать PDF' === req.buttons.join( '|' ) && req.card[ 0 ] && req.card[ 1 ] && '' === req.card[ 2 ], JSON.stringify( req ) );
		// Правка заказчика: где кнопки не помещаются рядом с заголовком (уже 600 px) — под таблицей, в одну строку.
		const reqLayout = async ( width ) => {
			await page.setViewportSize( { width, height: 900 } );
			return page.evaluate( () => {
				const q = ( sel ) => document.querySelector( sel ).getBoundingClientRect();
				const head = q( '.b-req__head' ), list = q( '.b-req__list' ), copy = document.querySelector( '.b-req__copy' ), card = document.querySelector( '.b-req__card' );
				const c = copy.getBoundingClientRect(), d = card.getBoundingClientRect();
				const place = c.top >= list.bottom ? 'под таблицей' : ( c.left > head.left + 100 && c.bottom <= list.top ? 'справа' : 'иначе' );
				return [ place, Math.abs( c.top - d.top ) < 1, copy.scrollWidth <= copy.clientWidth && card.scrollWidth <= card.clientWidth ];
			} );
		};
		const reqPlaces = {};
		for ( const width of [ 1280, 768, 600, 599, 390, 320, 280 ] ) {
			reqPlaces[ width ] = await reqLayout( width );
		}
		await page.setViewportSize( { width: 1280, height: 900 } );
		check( '«Реквизиты»: от 600 px кнопки справа от заголовка, уже — под таблицей; всегда в одну строку, текст помещается (правка заказчика)',
			Object.entries( reqPlaces ).every( ( [ w, r ] ) => ( Number( w ) >= 600 ? 'справа' : 'под таблицей' ) === r[ 0 ] && r[ 1 ] && r[ 2 ] ), JSON.stringify( reqPlaces ) );
		await page.locator( '.b-req__copy' ).click();
		await page.waitForTimeout( 300 );
		const copied = await page.evaluate( async () => [ await navigator.clipboard.readText(), document.querySelector( '[data-requisites-copy-label]' ).textContent ] );
		check( '«Скопировать»: строки «Название: значение» в буфере, подпись «Скопировано»',
			copied[ 0 ].startsWith( 'Полное наименование: [ООО «Название»]\nГенеральный директор: [Имя Фамилия]\n' ) && 10 === copied[ 0 ].split( '\n' ).length && 'Скопировано' === copied[ 1 ], JSON.stringify( copied ) );
		await page.waitForTimeout( 2600 );
		check( 'Через 2,5 с — снова «Скопировать»', 'Скопировать' === await page.locator( '[data-requisites-copy-label]' ).textContent() );

		// ===== Свои данные =====
		const qa = wp( 'eval', 'echo get_posts( array( "post_type" => "team", "name" => "qa", "numberposts" => 1, "fields" => "ids" ) )[0] ?? 0;' );
		wp( 'post', 'meta', 'update', qa, 'trust_email', 'qa-team@example.ru' );
		const own = createPage( 'Блоки', [
			block( 'departments', `array( 'title' => 'Отделы', 'items' => array( array( 'icon' => 'code', 'title' => 'Тестирование', 'text' => 'Ошибки сайта', 'member' => ${ Number( qa ) }, 'telegram' => '@qa_team' ) ), 'leader_card' => 1, 'leader' => ${ Number( qa ) }, 'leader_email' => 'boss@example.ru', 'leader_text' => 'Пишите, {name} ответит' )` ),
			block( 'contact-form', `array( 'title' => 'Форма', 'channels' => array( array( 'label' => 'Max', 'say' => 'напишет в Max' ) ), 'success_text' => 'Менеджер {channel}.' )` ),
		].join( ' . ' ) );
		created.push( own );
		await page.goto( `${ URL }/?page_id=${ own }`, { waitUntil: 'networkidle' } );
		const ownData = await page.evaluate( () => ( {
			links: [ ...document.querySelectorAll( '.b-depts__card' ) ].map( ( c ) => [ ...c.querySelectorAll( '.b-depts__link' ) ].map( ( a ) => a.getAttribute( 'href' ) ).join( ',' ) ),
			leader: document.querySelector( '.b-depts__card--leader .b-depts__text' ).textContent,
			channels: [ ...document.querySelectorAll( '.b-cform input[name="channel"]' ) ].map( ( i ) => i.value ),
			sent: document.querySelector( '.b-cform .form-success__text' ).textContent,
		} ) );
		wp( 'post', 'meta', 'delete', qa, 'trust_email' );
		check( 'Свои данные: email отдела — из карточки ответственного, Telegram — ссылкой t.me; руководитель и email — свои, {name} в тексте',
			'mailto:qa-team@example.ru,https://t.me/qa_team|mailto:boss@example.ru' === ownData.links.join( '|' ) && 'Пишите, [Имя Фамилия] ответит' === ownData.leader, JSON.stringify( ownData ) );
		check( 'Свои способы связи и текст после отправки', 'Max' === ownData.channels.join( '|' ) && 'Менеджер напишет в Max.' === ownData.sent, JSON.stringify( ownData ) );
		setSettings( { requisites: [] } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		check( 'Реквизиты не заполнены — блока «Реквизиты» на сайте нет', 0 === await page.locator( '.b-req' ).count() );
		setSettings( { requisites: saved.requisites } );

		// ===== Без JavaScript =====
		const noJs = await browser.newContext( { javaScriptEnabled: false, viewport: { width: 1280, height: 900 } } );
		const plain = await noJs.newPage();
		await plain.goto( PAGE, { waitUntil: 'domcontentloaded' } );
		const nj = await plain.evaluate( () => [ getComputedStyle( document.querySelector( '.b-req__copy' ) ).display, getComputedStyle( document.querySelector( '.b-req__card' ) ).display !== 'none' ] );
		await noJs.close();
		check( 'Без JavaScript: кнопки копирования нет, «Скачать PDF» — есть', 'none' === nj[ 0 ] && nj[ 1 ], JSON.stringify( nj ) );

		// ===== Сверка с макетом =====
		const sections = [
			[ 'office', '.b-omap', 'section[aria-labelledby="office-title"]' ],
			[ 'departments', '.b-depts', 'section[aria-labelledby="dept-title"]' ],
			[ 'form', '.b-cform', 'section[aria-labelledby="form-title"]' ],
			[ 'requisites', '.b-req', 'section[aria-labelledby="req-title"]' ],
		];
		for ( const width of [ 1280, 768, 390 ] ) {
			for ( const [ name, theme, mock ] of sections ) {
				await compare( page, name, width, theme, mock );
			}
		}

		// ===== Настройки и микроразметка =====
		const admin = await browser.newPage( { viewport: { width: 1400, height: 900 } } );
		await login( admin );
		await admin.goto( `${ URL }/wp-admin/admin.php?page=trust-settings` );
		const tabs = await admin.locator( '.acf-tab-wrap .acf-tab-button' ).allTextContents();
		await admin.close();
		check( '«Настройки сайта»: вкладка «Реквизиты» после «Контакты»', tabs.map( ( t ) => t.trim() ).join( '|' ).includes( 'Контакты|Реквизиты' ), tabs.join( '|' ) );

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
		const cols = async ( width ) => {
			await page.setViewportSize( { width, height: 900 } );
			return page.evaluate( () => {
				const n = ( sel ) => new Set( [ ...document.querySelectorAll( sel ) ].map( ( c ) => Math.round( c.getBoundingClientRect().left ) ) ).size;
				const map = document.querySelector( '.b-omap__map' ).getBoundingClientRect();
				const info = document.querySelector( '.b-omap__info' ).getBoundingClientRect();
				return [ n( '.b-depts__card' ), n( '.b-req__row' ), info.top > map.bottom - 1 ];
			} );
		};
		const desk = await cols( 1280 );
		const mob = await cols( 390 );
		check( 'Компьютер: отделы по 3 в ряд, реквизиты в 2 колонки, схема и офис рядом; телефон: всё в одну колонку, офис под схемой',
			JSON.stringify( desk ) === JSON.stringify( [ 3, 2, false ] ) && JSON.stringify( mob ) === JSON.stringify( [ 1, 1, true ] ), JSON.stringify( [ desk, mob ] ) );
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
		const axe = await page.evaluate( async () => {
			const r = await window.axe.run( '.page-entry__content', { resultTypes: [ 'violations' ] } );
			return r.violations.filter( ( x ) => [ 'critical', 'serious' ].includes( x.impact ) ).map( ( x ) => `${ x.id } (${ x.nodes.length })` );
		} );
		check( 'axe: нет нарушений critical/serious на странице «Контакты»', 0 === axe.length, axe.join( ', ' ) );
		check( 'Консоль браузера на сайте без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1400, height: 1000 } } );
		const editorErrors = collectConsoleErrors( editor );
		await login( editor );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ own }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		const reg = await editor.evaluate( () => [ 'trust/office-map', 'trust/departments', 'trust/contact-form', 'trust/requisites' ].map( ( name ) => {
			const type = wp.blocks.getBlockType( name );
			return [ type && type.title, type && type.category, !! ( type && type.example ) ].join( ':' );
		} ) );
		check( 'Редактор: четыре блока («Компания» и «Формы»), у всех превью',
			JSON.stringify( reg ) === JSON.stringify( [ 'Приезжайте в гости:trust-company:true', 'Кому написать:trust-company:true', 'Расскажите о задаче:trust-forms:true', 'Реквизиты:trust-company:true' ] ), JSON.stringify( reg ) );
		const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-cform' ).first().waitFor( { timeout: 30000 } );
		check( 'Редактор: превью блоков на странице', 2 === await canvas.locator( '.b-depts__card' ).count() );
		check( 'Редактор без ошибок JavaScript', 0 === editorErrors.length, editorErrors.slice( 0, 3 ).join( ' | ' ) );
		await editor.close();
	} finally {
		setSettings( { map_embed: saved.map_embed || '', map_image: saved.map_image || '', directions_note: saved.directions_note || '', requisites: saved.requisites || [] } );
		created.forEach( ( id ) => wp( 'post', 'delete', String( id ), '--force' ) );
	}

	await browser.close();
	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
