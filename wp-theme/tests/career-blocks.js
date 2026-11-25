/**
 * Тест блоков шага 4.13 — страница «Карьера»: «Почему у нас интересно работать», «Открытые
 * вакансии», «Как проходит отбор», «Стажировка», «Сотрудники о работе у нас», «Отправить резюме».
 *
 * Запуск: node wp-theme/tests/career-blocks.js
 *
 * Демо на /karera/ (порядок и тексты из макета o-nas/karera.html): 6 условий со значками;
 * 6 вакансий, фильтр отделов с числом, раскрыта одна (первая), «Откликнуться» выбирает вакансию в
 * форме и ведёт к ней; 4 шага отбора (последний выделен); стажировка — кнопка выбирает вакансию
 * стажёра; цитаты трёх сотрудников и «Вся команда»; форма резюме (ошибки как в макете, заявка
 * «Резюме» со ссылкой, «Резюме отправлено» поверх формы, значок слева, заголовок справа).
 * Свои данные: закрытая вакансия не видна (и не считается в первом экране), город, пустой список,
 * пункт «Стажировка», подпись цитаты по умолчанию, ?vacancy= с другой страницы. Без JavaScript
 * раскрыты все вакансии. Сверка с макетом на 1280 / 768 / 390 px, ширины 1440–280, axe-core,
 * консоль, редактор.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp, resetFormLimits } = require( './lib' );

const PAGE = `${ URL }/karera/`;
const MOCKUP = 'o-nas/karera.html';
const MARK = 'ТестКарьера';

/** PHP: блок по имени и полям (PHP-массив). */
const block = ( name, fields ) => `Trust\\Blocks::serialize( '${ name }', ${ fields } )`;

/**
 * Опубликованная тестовая страница с блоками.
 *
 * @param {string} title    Заголовок.
 * @param {string} blocks   PHP-выражение: разметка блоков.
 * @param {string} template Шаблон страницы.
 * @return {string} ID.
 */
function createPage( title, blocks, template = 'templates/career.php' ) {
	const content = wp( 'eval', `echo ${ blocks };` );
	const id = wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK } ${ title }`, '--post_status=publish', `--post_content=${ content }`, '--porcelain' );
	wp( 'post', 'meta', 'update', id, '_wp_page_template', template );
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
	const base = path.join( OUT, `career-${ name }-${ width }` );
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
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const created = [];
	const vacancies = JSON.parse( wp( 'eval', 'echo wp_json_encode( array_map( static fn( $p ) => array( $p->post_name, $p->post_title ), Trust\\Vacancies::open_posts() ) );' ) );

	try {
		// ===== Демо-страница =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const order = await page.evaluate( () => [ ...document.querySelectorAll( '.b-hero-section, .b-benefits, .b-vac, .b-hiring, .b-intern, .b-voices, .b-resume' ) ].map( ( s ) => s.classList[ 0 ] ) );
		check( 'Порядок блоков как в макете: первый экран, условия, вакансии, отбор, стажировка, цитаты, резюме',
			'b-hero-section|b-benefits|b-vac|b-hiring|b-intern|b-voices|b-resume' === order.join( '|' ), JSON.stringify( order ) );
		const heroCount = await page.evaluate( () => [ ...document.querySelectorAll( 'dl dt, dl dd' ) ].map( ( e ) => e.textContent.trim() ) );
		check( 'Первый экран: «открытых вакансий» — число открытых вакансий (6)', heroCount.join( '|' ).includes( '6|открытых вакансий' ) || heroCount.join( '|' ).includes( 'открытых вакансий|6' ), heroCount.join( '|' ) );

		// ===== Почему у нас интересно работать =====
		const benefits = await page.evaluate( () => [ ...document.querySelectorAll( '.b-benefits__item' ) ].map( ( li ) => [ !! li.querySelector( '.b-benefits__icon svg' ), li.querySelector( 'h3' ).textContent ] ) );
		check( '«Почему у нас»: 6 пунктов со значками и заголовками (H3) из макета',
			6 === benefits.length && benefits.every( ( b ) => b[ 0 ] ) && 'Наставник с первого дня|Обучение за счёт компании|Понятный рост|Гибкий формат|Официальное оформление|Реальные проекты' === benefits.map( ( b ) => b[ 1 ] ).join( '|' ), JSON.stringify( benefits ) );
		const benefitsLayout = () => page.evaluate( () => {
			const head = document.querySelector( '.b-benefits__head' ).getBoundingClientRect();
			const inner = document.querySelector( '.b-benefits__inner' ).getBoundingClientRect();
			const li = document.querySelector( '.b-benefits__item' );
			const icon = li.querySelector( '.b-benefits__icon' ).getBoundingClientRect();
			const h3 = li.querySelector( 'h3' ).getBoundingClientRect();
			const p = li.querySelector( 'p' ).getBoundingClientRect();
			return [ Math.round( head.width ) === Math.round( inner.width ), h3.left > icon.right && Math.abs( ( h3.top + h3.bottom ) / 2 - ( icon.top + icon.bottom ) / 2 ) < 2, p.top > icon.bottom && Math.round( p.left ) === Math.round( icon.left ) ];
		} );
		const benefitsDesk = await benefitsLayout();
		check( '«Почему у нас»: шапка во всю ширину блока, заголовок пункта справа от значка, текст под ними (правки заказчика)',
			JSON.stringify( benefitsDesk ) === JSON.stringify( [ true, true, true ] ), JSON.stringify( benefitsDesk ) );

		// ===== Открытые вакансии =====
		const vac = await page.evaluate( () => {
			const root = document.querySelector( '.b-vac' );
			const items = [ ...root.querySelectorAll( '.b-vac__item' ) ];
			const first = items[ 0 ];
			return {
				id: root.id,
				chips: [ ...root.querySelectorAll( '.filter-chip' ) ].map( ( b ) => b.firstChild.textContent + ':' + b.querySelector( '.filter-chip__count' ).textContent ),
				titles: items.map( ( li ) => li.querySelector( '.b-vac__title' ).textContent ),
				open: items.map( ( li ) => li.querySelector( '.b-vac__toggle' ).getAttribute( 'aria-expanded' ) + ( li.querySelector( '.b-vac__panel' ).hidden ? '-' : '+' ) ),
				tags: [ ...first.querySelectorAll( '.b-vac__tag' ) ].map( ( t ) => t.textContent ),
				salary: first.querySelector( '.b-vac__salary' ).textContent,
				h3: !! first.querySelector( 'h3 > button.b-vac__toggle' ),
				region: first.querySelector( '.b-vac__panel' ).getAttribute( 'role' ) + ':' + ( first.querySelector( '.b-vac__panel' ).getAttribute( 'aria-labelledby' ) === first.querySelector( '.b-vac__toggle' ).id ),
				sections: [ ...first.querySelectorAll( '.b-vac__section' ) ].map( ( s ) => s.querySelector( 'h4' ).textContent + ':' + s.querySelectorAll( 'li' ).length ),
				about: first.querySelector( '.b-vac__about' ).textContent.slice( 0, 20 ),
				apply: [ first.querySelector( '.b-vac__apply' ).getAttribute( 'href' ), first.querySelector( '.b-vac__apply' ).dataset.applyVacancy ],
				mail: first.querySelector( '.b-vac__mail a' ).getAttribute( 'href' ),
			};
		} );
		check( '«Открытые вакансии»: якорь #vacancies, кнопки отделов с числом (Все 6, SEO 3, Разработка, Реклама, Менеджмент по 1)',
			'vacancies' === vac.id && 'Все:6|SEO:3|Разработка:1|Реклама:1|Менеджмент:1' === vac.chips.join( '|' ), JSON.stringify( [ vac.id, vac.chips ] ) );
		check( 'Вакансии — 6 открытых в порядке поля «Порядок», раскрыта только первая',
			JSON.stringify( vac.titles ) === JSON.stringify( vacancies.map( ( v ) => v[ 1 ] ) ) && 'true+|false-|false-|false-|false-|false-' === vac.open.join( '|' ), JSON.stringify( [ vac.titles, vac.open ] ) );
		check( 'Карточка: H3 с кнопкой, отдел, формат, опыт, зарплата; панель — region с подписью кнопки',
			vac.h3 && 'SEO|[Офис или гибрид]|Опыт от 3 лет' === vac.tags.join( '|' ) && '[от N $]' === vac.salary && 'region:true' === vac.region, JSON.stringify( vac ) );
		check( 'Раскрытая вакансия: «коротко о вакансии», три раздела по 4 пункта, «Откликнуться» (#resume), «Или пришлите резюме на hr@example.ru»',
			'Ведёте несколько про' === vac.about && 'Чем предстоит заниматься:4|Что ждём от кандидата:4|Что предлагаем:4' === vac.sections.join( '|' ) && '#resume' === vac.apply[ 0 ] && vacancies[ 0 ][ 0 ] === vac.apply[ 1 ] && 'mailto:hr@example.ru' === vac.mail, JSON.stringify( vac ) );
		const actions = await page.evaluate( () => {
			const item = document.querySelector( '.b-vac__item' );
			const panelEl = item.querySelector( '.b-vac__panel' );
			const panel = panelEl.getBoundingClientRect();
			const apply = item.querySelector( '.b-vac__apply' ).getBoundingClientRect();
			const mail = item.querySelector( '.b-vac__mail' ).getBoundingClientRect();
			return [ Math.round( panel.right - parseFloat( getComputedStyle( panelEl ).paddingRight ) - apply.right ), mail.top >= apply.bottom, Math.round( mail.right ) === Math.round( apply.right ), Math.round( apply.height ) ];
		} );
		check( '«Откликнуться» (46 px) у правого края карточки, «Или пришлите резюме на …» — под кнопкой (правки заказчика)',
			JSON.stringify( actions ) === JSON.stringify( [ 0, true, true, 46 ] ), JSON.stringify( actions ) );

		const state = () => page.evaluate( () => [ ...document.querySelectorAll( '.b-vac__item' ) ].map( ( li ) => ( li.hidden ? 'h' : '' ) + ( 'true' === li.querySelector( '.b-vac__toggle' ).getAttribute( 'aria-expanded' ) ? '1' : '0' ) ).join( '' ) );
		await page.locator( '.b-vac__toggle' ).nth( 1 ).click();
		const second = await state();
		await page.locator( '.b-vac__toggle' ).nth( 1 ).click();
		const none = await state();
		await page.locator( '.b-vac__toggle' ).nth( 3 ).press( 'Enter' );
		const keyboard = await state();
		check( 'Раскрытие по одной: открыта вторая — первая свернулась; повторное нажатие сворачивает; Enter с клавиатуры',
			'010000' === second && '000000' === none && '000100' === keyboard, JSON.stringify( [ second, none, keyboard ] ) );

		await page.locator( '.b-vac .filter-chip', { hasText: 'Разработка' } ).click();
		const dev = await page.evaluate( () => [ [ ...document.querySelectorAll( '.b-vac__item:not([hidden]) .b-vac__title' ) ].map( ( t ) => t.textContent ), document.querySelector( '.b-vac [data-filter-status]' ).textContent, document.querySelector( '.b-vac .filter-chip[aria-pressed="true"]' ).firstChild.textContent ] );
		await page.locator( '.b-vac .filter-chip', { hasText: 'Все' } ).click();
		const allBack = await page.locator( '.b-vac__item:not([hidden])' ).count();
		check( 'Фильтр «Разработка»: только Frontend-разработчик, для скринридера — «Показано вакансий: 1 из 6»; «Все» — снова 6',
			'Frontend-разработчик' === dev[ 0 ].join() && 'Показано вакансий: 1 из 6' === dev[ 1 ] && 'Разработка' === dev[ 2 ] && 6 === allBack, JSON.stringify( [ dev, allBack ] ) );

		// «Откликнуться» → форма резюме с выбранной вакансией.
		await page.locator( '.b-vac__toggle' ).nth( 1 ).click();
		await page.locator( '.b-vac__item' ).nth( 1 ).locator( '.b-vac__apply' ).click();
		await page.waitForTimeout( 600 );
		const applied = await page.evaluate( () => {
			const select = document.querySelector( '.b-resume select[name="vacancy"]' );
			const r = document.querySelector( '.b-resume' ).getBoundingClientRect();
			return [ select.value, select.selectedOptions[ 0 ].textContent, document.activeElement.name, location.hash, r.top < innerHeight && r.bottom > 0 ];
		} );
		check( '«Откликнуться» у «SEO-специалиста»: форма резюме на экране, выбрана эта вакансия, фокус в «Имя и фамилия»',
			JSON.stringify( applied ) === JSON.stringify( [ vacancies[ 1 ][ 0 ], 'SEO-специалист', 'name', '#resume', true ] ), JSON.stringify( applied ) );

		// ===== Как проходит отбор =====
		const steps = await page.evaluate( () => [ ...document.querySelectorAll( '.b-hiring__step' ) ].map( ( li ) => li.querySelector( '.b-hiring__num' ).textContent + ':' + li.querySelector( 'h3' ).textContent + ( li.classList.contains( 'is-final' ) ? ':final' : '' ) ) );
		const stepColor = await page.evaluate( () => [ ...document.querySelectorAll( '.b-hiring__num' ) ].map( ( n ) => getComputedStyle( n ).backgroundColor ) );
		check( '«Как проходит отбор»: шаги 1–4 (нумерованный список), последний выделен фиолетовым',
			'1:Отклик|2:Звонок с HR|3:Задание и интервью|4:Предложение:final' === steps.join( '|' ) && 'rgb(109, 40, 217)' === stepColor[ 3 ] && 'rgb(30, 27, 75)' === stepColor[ 0 ] && 'OL' === await page.evaluate( () => document.querySelector( '.b-hiring__list' ).tagName ), JSON.stringify( [ steps, stepColor ] ) );

		// ===== Стажировка =====
		const intern = await page.evaluate( () => {
			const b = document.querySelector( '.b-intern' );
			const btn = b.querySelector( '.b-intern__btn' );
			return [ b.querySelector( '.b-intern__badge' ).textContent, b.querySelector( 'h2' ).textContent, btn.textContent.trim(), btn.getAttribute( 'href' ), btn.dataset.applyVacancy ];
		} );
		check( '«Стажировка»: метка «Без опыта», H2, «Подать заявку» ведёт к форме и выбирает вакансию стажёра',
			JSON.stringify( intern ) === JSON.stringify( [ 'Без опыта', 'Стажировка в SEO-отделе', 'Подать заявку', '#resume', vacancies[ 2 ][ 0 ] ] ), JSON.stringify( intern ) );
		await page.locator( '.b-intern__btn' ).click();
		await page.waitForTimeout( 300 );
		check( '«Подать заявку» — в форме выбран «Стажёр в SEO-отдел»', 'Стажёр в SEO-отдел' === await page.evaluate( () => document.querySelector( '.b-resume select[name="vacancy"]' ).selectedOptions[ 0 ].textContent ) );

		// ===== Сотрудники о работе у нас =====
		const voices = await page.evaluate( () => {
			const b = document.querySelector( '.b-voices' );
			return {
				more: [ b.querySelector( '.b-voices__more' ).textContent.trim(), new URL( b.querySelector( '.b-voices__more' ).href ).pathname ],
				cards: [ ...b.querySelectorAll( '.b-voices__card' ) ].map( ( c ) => [ c.querySelector( 'blockquote' ).textContent.slice( 0, 18 ), c.querySelector( '.b-voices__name' ).textContent, c.querySelector( '.b-voices__role' ).textContent, !! c.querySelector( '.b-voices__photo' ) ] ),
			};
		} );
		check( '«Сотрудники о работе у нас»: «Вся команда» на /komanda/, три цитаты: фото, имя сотрудника, подпись «… · в команде с [ГГГГ]»',
			'Вся команда' === voices.more[ 0 ] && '/komanda/' === voices.more[ 1 ] && 3 === voices.cards.length && voices.cards.every( ( c ) => c[ 0 ].startsWith( '[Цитата сотрудника' ) && '[Имя Фамилия]' === c[ 1 ] && c[ 2 ].endsWith( '· в команде с [ГГГГ]' ) && c[ 3 ] ), JSON.stringify( voices ) );

		// ===== Отправить резюме =====
		resetFormLimits();
		const rf = await page.evaluate( () => {
			const b = document.querySelector( '.b-resume' );
			return {
				id: b.id,
				hr: [ ...b.querySelectorAll( '.b-resume__hr a' ) ].map( ( a ) => a.textContent.trim().replace( /\s*\(откроется.*$/, '' ) + '>' + a.getAttribute( 'href' ) ),
				options: [ ...b.querySelectorAll( 'select[name="vacancy"] option' ) ].map( ( o ) => o.textContent ),
			};
		} );
		check( '«Отправить резюме»: якорь #resume, HR — email и Telegram из настроек, вакансии: «Без вакансии» и 6 открытых (пункт «Стажировка» выключен в демо)',
			'resume' === rf.id && 'hr@example.ru>mailto:hr@example.ru|Telegram: @[brand]_hr>https://t.me/%5Bbrand%5D_hr' === rf.hr.join( '|' )
			&& JSON.stringify( rf.options ) === JSON.stringify( [ 'Без вакансии — инициативное резюме', ...vacancies.map( ( v ) => v[ 1 ] ) ] ), JSON.stringify( rf ) );
		const form = page.locator( '.b-resume__form' );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await form.locator( '[type="submit"]' ).click();
		const errs = await form.locator( '.field__error:not([hidden])' ).allTextContents();
		check( 'Пустая форма: 4 сообщения как в макете, фокус на «Имя и фамилия»',
			JSON.stringify( errs ) === JSON.stringify( [ 'Укажите имя и фамилию', 'Оставьте телефон или email', 'Добавьте ссылку на резюме или портфолио', 'Отметьте согласие, чтобы отправить резюме' ] ) && 'name' === await page.evaluate( () => document.activeElement.name ), JSON.stringify( errs ) );
		await form.locator( '[name="name"]' ).fill( `${ MARK } Ольга` );
		await form.locator( '[name="contact"]' ).fill( 'olga@example.com' );
		await form.locator( '[name="vacancy"]' ).selectOption( { label: 'Frontend-разработчик' } );
		await form.locator( '[name="link"]' ).fill( 'github.com/olga' );
		await form.locator( '[name="about"]' ).fill( 'Верстаю 3 года, люблю доступность.' );
		await form.locator( '[name="consent"]' ).check();
		const top = () => page.evaluate( () => Math.round( document.querySelector( '.b-resume__form' ).getBoundingClientRect().top + scrollY ) );
		const before = await top();
		await form.locator( '[type="submit"]' ).click();
		const sent = await page.locator( '.b-resume .form-sent' ).waitFor( { state: 'visible', timeout: 20000 } ).then( () => true ).catch( () => false );
		const sentState = await page.evaluate( () => {
			const icon = document.querySelector( '.b-resume .form-success__icon' ).getBoundingClientRect();
			const title = document.querySelector( '.b-resume .form-success__title' );
			const t = title.getBoundingClientRect();
			return [ title.textContent, document.querySelector( '.b-resume__form' ).inert, document.querySelector( '.b-resume__form' ).hidden, t.left > icon.right && Math.abs( ( t.top + t.bottom ) / 2 - ( icon.top + icon.bottom ) / 2 ) < 2, document.querySelector( '.b-resume .form-sent__again' ).textContent.trim() ];
		} );
		check( 'После отправки — «Резюме отправлено» поверх формы (форма на месте), значок слева, заголовок справа, «Отправить ещё одно резюме»',
			sent && JSON.stringify( sentState ) === JSON.stringify( [ 'Резюме отправлено', true, false, true, 'Отправить ещё одно резюме' ] ) && before === await top(), JSON.stringify( [ sentState, before ] ) );
		const lead = JSON.parse( wp( 'eval', `$l = get_posts( array( 'post_type' => 'lead', 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => 'trust_name', 'meta_value' => '${ MARK } Ольга' ) ); $id = $l ? $l[0]->ID : 0; $f = $id ? (array) get_post_meta( $id, 'trust_fields', true ) : array(); echo wp_json_encode( array( $id, get_post_meta( $id, 'trust_form', true ), array_map( static fn( $x ) => $x['label'] . '=' . $x['value'], array_values( $f ) ), $f['link']['type'] ?? '' ) );` ) );
		if ( lead[ 0 ] ) {
			created.push( String( lead[ 0 ] ) );
		}
		check( 'Заявка «Резюме»: имя, контакт, вакансия (название), ссылка, о себе',
			'resume' === lead[ 1 ] && JSON.stringify( lead[ 2 ] ) === JSON.stringify( [ `Имя и фамилия=${ MARK } Ольга`, 'Телефон или email=olga@example.com', 'Вакансия=Frontend-разработчик', 'Ссылка на резюме или портфолио=github.com/olga', 'О себе=Верстаю 3 года, люблю доступность.' ] ) && 'url' === lead[ 3 ], JSON.stringify( lead ) );
		if ( lead[ 0 ] ) {
			const admin = await browser.newPage( { viewport: { width: 1280, height: 900 } } );
			await login( admin );
			await admin.goto( `${ URL }/wp-admin/post.php?post=${ lead[ 0 ] }&action=edit` );
			const link = await admin.evaluate( () => {
				const a = [ ...document.querySelectorAll( '.trust-lead-table a' ) ].find( ( x ) => 'github.com/olga' === x.textContent );
				return a ? [ a.href, a.target, a.rel ] : null;
			} );
			await admin.close();
			check( 'В заявке ссылка на резюме — кликабельная (https://, новая вкладка, nofollow)', link && 'https://github.com/olga' === link[ 0 ] && '_blank' === link[ 1 ] && link[ 2 ].includes( 'nofollow' ), JSON.stringify( link ) );

			// «Заявки → Резюме» (правка заказчика): резюме — только в своём подпункте.
			const adm = await browser.newPage( { viewport: { width: 1280, height: 900 } } );
			await login( adm );
			await adm.goto( `${ URL }/wp-admin/edit.php?post_type=lead` );
			const sub = await adm.locator( '#menu-posts-lead .wp-submenu li:not(.wp-submenu-head) a' ).evaluateAll( ( links ) => links.map( ( a ) => [ a.childNodes[ 0 ].textContent.trim(), a.querySelector( '.pending-count' )?.textContent || '' ] ) );
			const allRows = await adm.locator( '#the-list tr' ).allInnerTexts();
			const formOptions = await adm.locator( 'select[name="trust_form"] option' ).allTextContents();
			check( 'Админка: подпункт «Резюме» после «Все заявки», «Обратной связи» и «Обращений к руководителю», с числом новых',
				'Все заявки|Обратная связь|Обращения к руководителю|Резюме' === sub.slice( 0, 4 ).map( ( x ) => x[ 0 ] ).join( '|' ) && Number( sub[ 3 ][ 1 ] ) >= 1, JSON.stringify( sub ) );
			check( 'Админка: в «Все заявки» резюме нет, в фильтре форм — тоже',
				! allRows.some( ( r ) => r.includes( `${ MARK } Ольга` ) ) && ! formOptions.includes( 'Резюме' ) && formOptions.includes( 'Бриф' ), JSON.stringify( formOptions ) );
			await adm.locator( '#menu-posts-lead .wp-submenu a', { hasText: 'Резюме' } ).click();
			await adm.waitForLoadState();
			const list = await adm.evaluate( ( mark ) => {
				const row = [ ...document.querySelectorAll( '#the-list tr' ) ].find( ( tr ) => tr.textContent.includes( mark ) );
				const a = row ? row.querySelector( '.column-trust_resume_link a' ) : null;
				return {
					h1: document.querySelector( 'h1' ).textContent.trim(),
					current: document.querySelector( '#menu-posts-lead .wp-submenu li.current a' )?.childNodes[ 0 ].textContent.trim(),
					cols: [ ...document.querySelectorAll( '#posts-filter thead th' ) ].map( ( th ) => th.id ),
					vacancy: row ? row.querySelector( '.column-trust_resume_vacancy' ).textContent.trim() : '',
					link: a ? [ a.textContent, a.href, a.target ] : null,
					hidden: document.querySelector( '#posts-filter input[type="hidden"][name="trust_form"]' )?.value,
					rows: document.querySelectorAll( '#the-list tr:not(.no-items)' ).length,
					views: document.querySelector( '.subsubsub' ).textContent.replace( /\s+/g, ' ' ).trim(),
				};
			}, `${ MARK } Ольга` );
			check( 'Админка: экран «Резюме» — заголовок, подсвечен подпункт, колонки «Вакансия» и «Резюме» (ссылка) вместо «Формы»',
				'Резюме' === list.h1 && 'Резюме' === list.current && list.cols.includes( 'trust_resume_vacancy' ) && list.cols.includes( 'trust_resume_link' ) && ! list.cols.includes( 'trust_form' ) && 'resume' === list.hidden
				&& 'Frontend-разработчик' === list.vacancy && list.link && 'github.com/olga' === list.link[ 0 ] && 'https://github.com/olga' === list.link[ 1 ] && '_blank' === list.link[ 2 ] && list.views.includes( `(${ list.rows })` ), JSON.stringify( list ) );
			await adm.goto( `${ URL }/wp-admin/post.php?post=${ lead[ 0 ] }&action=edit` );
			const single = await adm.locator( '#menu-posts-lead .wp-submenu li.current a' ).evaluate( ( a ) => a.childNodes[ 0 ].textContent.trim() ).catch( () => '' );
			check( 'Админка: на экране резюме подсвечен подпункт «Резюме»', 'Резюме' === single, single );
			await adm.close();
		}

		// Отклик при открытом экране «Резюме отправлено» — форма возвращается, вакансия выбрана.
		await page.locator( '.b-vac__item' ).first().locator( '.b-vac__apply' ).click();
		await page.waitForTimeout( 400 );
		const reopened = await page.evaluate( () => [ document.querySelector( '.b-resume .form-sent' ).hidden, document.querySelector( '.b-resume__form' ).inert, document.querySelector( '[name="name"]' ).value, document.querySelector( '.b-resume select[name="vacancy"]' ).selectedOptions[ 0 ].textContent ] );
		check( '«Откликнуться» после отправки — слой убран, форма пустая, выбрана новая вакансия',
			JSON.stringify( reopened ) === JSON.stringify( [ true, false, '', 'Старший SEO-специалист' ] ), JSON.stringify( reopened ) );

		await page.goto( `${ PAGE }?vacancy=${ vacancies[ 4 ][ 0 ] }#resume`, { waitUntil: 'networkidle' } );
		check( 'Ссылка ?vacancy=…#resume с другой страницы — вакансия выбрана при загрузке',
			'Специалист по контекстной рекламе' === await page.evaluate( () => document.querySelector( '.b-resume select[name="vacancy"]' ).selectedOptions[ 0 ].textContent ) );

		// ===== Без JavaScript =====
		const noJs = await browser.newContext( { javaScriptEnabled: false, viewport: { width: 1280, height: 900 } } );
		const plain = await noJs.newPage();
		await plain.goto( PAGE, { waitUntil: 'domcontentloaded' } );
		const nj = await plain.evaluate( () => [ getComputedStyle( document.querySelector( '.b-vac .filter-chips' ) ).display, [ ...document.querySelectorAll( '.b-vac__panel' ) ].filter( ( p ) => 'none' !== getComputedStyle( p ).display ).length ] );
		await noJs.close();
		check( 'Без JavaScript: кнопок отделов нет, раскрыты все 6 вакансий', 'none' === nj[ 0 ] && 6 === nj[ 1 ], JSON.stringify( nj ) );

		// ===== Сверка с макетом =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const sections = [
			[ 'why', '.b-benefits', 'section[aria-labelledby="why-title"]' ],
			[ 'vacancies', '.b-vac', 'section[aria-labelledby="vac-title"]' ],
			[ 'steps', '.b-hiring', 'section[aria-labelledby="steps-title"]' ],
			[ 'intern', '.b-intern', 'section[aria-labelledby="intern-title"]' ],
			[ 'voices', '.b-voices', 'section[aria-labelledby="voices-title"]' ],
			[ 'resume', '.b-resume', 'section[aria-labelledby="resume-title"]' ],
		];
		for ( const width of [ 1280, 768, 390 ] ) {
			for ( const [ name, theme, mock ] of sections ) {
				await compare( page, name, width, theme, mock );
			}
		}

		// ===== Свои данные =====
		const closed = wp( 'eval', `$id = wp_insert_post( array( 'post_type' => 'vacancy', 'post_status' => 'publish', 'post_title' => '${ MARK } закрытая', 'menu_order' => 0 ) ); update_post_meta( $id, 'trust_open', '0' ); echo $id;` );
		const city = wp( 'eval', `$id = wp_insert_post( array( 'post_type' => 'vacancy', 'post_status' => 'publish', 'post_title' => '${ MARK } с городом', 'menu_order' => 99 ) ); update_post_meta( $id, 'trust_open', '1' ); update_post_meta( $id, 'trust_city', 'Казань' ); update_post_meta( $id, 'trust_format', 'Офис' ); echo $id;` );
		created.push( closed, city );
		const member = wp( 'eval', 'echo get_posts( array( "post_type" => "team", "name" => "qa", "numberposts" => 1, "fields" => "ids" ) )[0] ?? 0;' );
		const own = createPage( 'Блоки', [
			block( 'vacancies', `array( 'title' => 'Вакансии', 'filter' => 0, 'open_first' => 0 )` ),
			block( 'internship', `array( 'title' => 'Стажировка' )` ),
			block( 'employee-quotes', `array( 'title' => 'Цитаты', 'show_more' => 0, 'quotes' => array( array( 'member' => ${ Number( member ) }, 'text' => 'Своя цитата' ) ) )` ),
			block( 'resume-form', `array( 'title' => 'Резюме', 'anchor' => 'cv' )` ),
		].join( ' . ' ) );
		created.push( own );
		await page.goto( `${ URL }/?page_id=${ own }`, { waitUntil: 'networkidle' } );
		const ownData = await page.evaluate( () => ( {
			titles: [ ...document.querySelectorAll( '.b-vac__title' ) ].map( ( t ) => t.textContent ),
			chips: !! document.querySelector( '.b-vac .filter-chips' ),
			open: document.querySelectorAll( '.b-vac__toggle[aria-expanded="true"]' ).length,
			cityTags: [ ...document.querySelectorAll( '.b-vac__item:last-child .b-vac__tag' ) ].map( ( t ) => t.textContent ),
			intern: [ document.querySelector( '.b-intern__btn' ).getAttribute( 'href' ), document.querySelector( '.b-intern__btn' ).dataset.applyVacancy ],
			quote: [ document.querySelector( '.b-voices__role' ).textContent, !! document.querySelector( '.b-voices__more' ) ],
			options: [ ...document.querySelectorAll( '.b-resume select[name="vacancy"] option' ) ].map( ( o ) => o.textContent ).slice( -1 )[ 0 ],
			resumeId: document.querySelector( '.b-resume' ).id,
		} ) );
		check( 'Закрытая вакансия («Принимаем резюме» снята) не видна; город — отдельной меткой; фильтр выключен; все свёрнуты',
			! ownData.titles.includes( `${ MARK } закрытая` ) && ownData.titles.includes( `${ MARK } с городом` ) && ! ownData.chips && 0 === ownData.open && 'Офис|Казань' === ownData.cityTags.join( '|' ), JSON.stringify( ownData ) );
		check( 'Стажировка без вакансии — пункт «Стажировка» в форме (включён по умолчанию); цитата без подписи — должность сотрудника; кнопка «Вся команда» выключена; свой якорь формы',
			'intern' === ownData.intern[ 1 ] && 'Стажировка' === ownData.options && 'Тестировщик' === ownData.quote[ 0 ] && ! ownData.quote[ 1 ] && 'cv' === ownData.resumeId, JSON.stringify( ownData ) );
		await page.locator( '.b-intern__btn' ).click();
		await page.waitForTimeout( 300 );
		check( '«Подать заявку» без вакансии — выбран пункт «Стажировка»', 'Стажировка' === await page.evaluate( () => document.querySelector( '.b-resume select[name="vacancy"]' ).selectedOptions[ 0 ].textContent ) );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const heroCount2 = await page.evaluate( () => document.querySelector( 'dl' ).textContent.replace( /\s+/g, ' ' ) );
		check( 'Первый экран считает только открытые вакансии (6 + 1 с городом = 7)', heroCount2.includes( 'открытых вакансий 7' ), heroCount2 );

		// Кнопка на другой странице (не «Карьера») ведёт на страницу «Карьера» с выбором вакансии.
		const other = createPage( 'Другая', block( 'internship', `array( 'title' => 'Стажировка' )` ), 'default' );
		created.push( other );
		await page.goto( `${ URL }/?page_id=${ other }`, { waitUntil: 'networkidle' } );
		const otherHref = new globalThis.URL( await page.locator( '.b-intern__btn' ).getAttribute( 'href' ) );
		check( 'На другой странице «Подать заявку» ведёт на /karera/?vacancy=intern#resume', '/karera/' === otherHref.pathname && 'intern' === otherHref.searchParams.get( 'vacancy' ) && '#resume' === otherHref.hash, otherHref.href );
		wp( 'post', 'delete', closed, city, '--force' );
		created.splice( created.indexOf( closed ), 2 );

		// Пустой список вакансий.
		const hidden = wp( 'eval', 'echo implode( ",", array_map( static fn( $p ) => $p->ID, Trust\\Vacancies::open_posts() ) );' ).split( ',' ).filter( Boolean );
		hidden.forEach( ( id ) => wp( 'post', 'meta', 'update', id, 'trust_open', '0' ) );
		try {
			await page.goto( `${ URL }/?page_id=${ own }`, { waitUntil: 'networkidle' } );
			const empty = await page.evaluate( () => [ !! document.querySelector( '.b-vac__list' ), ( document.querySelector( '.b-vac__empty' ) || {} ).textContent || '' ] );
			check( 'Нет открытых вакансий — текст «Сейчас открытых вакансий нет…» и email для резюме', ! empty[ 0 ] && empty[ 1 ].includes( 'Сейчас открытых вакансий нет' ) && empty[ 1 ].includes( 'hr@example.ru' ), JSON.stringify( empty ) );
		} finally {
			hidden.forEach( ( id ) => wp( 'post', 'meta', 'update', id, 'trust_open', '1' ) );
		}

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
			const cols = ( sel ) => new Set( [ ...document.querySelectorAll( sel ) ].map( ( c ) => Math.round( c.getBoundingClientRect().left ) ) ).size;
			return [ cols( '.b-benefits__item' ), cols( '.b-voices__card' ), cols( '.b-hiring__step' ) ];
		} );
		check( 'Телефон 390: условия, цитаты и шаги — в одну колонку', JSON.stringify( mob ) === JSON.stringify( [ 1, 1, 1 ] ), JSON.stringify( mob ) );
		const benefitsMob = await benefitsLayout();
		const mobFix = await page.evaluate( () => {
			const centers = [ ...document.querySelectorAll( '.b-vac__item' ) ].map( ( li ) => {
				const a = li.querySelector( '.b-vac__salary' ).getBoundingClientRect();
				const b = li.querySelector( '.b-vac__chevron' ).getBoundingClientRect();
				return Math.abs( ( a.top + a.bottom ) / 2 - ( b.top + b.bottom ) / 2 ) < 2 && b.left > a.left;
			} );
			const more = document.querySelector( '.b-voices__more' ).getBoundingClientRect();
			const list = document.querySelector( '.b-voices__list' ).getBoundingClientRect();
			const head = document.querySelector( '.b-resume__head' ).getBoundingClientRect();
			const intro = document.querySelector( '.b-resume__intro' ).getBoundingClientRect();
			return [ centers.every( Boolean ), more.top > list.bottom && Math.abs( more.right - list.right ) < 1, Math.round( head.width ) === Math.round( intro.width ) ];
		} );
		check( 'Телефон 390: в «Условиях» заголовок справа от значка; стрелка вакансии — на уровне зарплаты; «Вся команда» — после цитат, у правого края; текст «Отклика» во всю ширину (правки заказчика)',
			JSON.stringify( benefitsMob ) === JSON.stringify( [ true, true, true ] ) && JSON.stringify( mobFix ) === JSON.stringify( [ true, true, true ] ), JSON.stringify( [ benefitsMob, mobFix ] ) );
		const heroButtons = [];
		for ( const width of [ 320, 390, 430, 1280 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			heroButtons.push( await page.evaluate( () => {
				const b = [ ...document.querySelectorAll( '.b-hero-section__btn' ) ];
				return ( Math.abs( b[ 0 ].getBoundingClientRect().top - b[ 1 ].getBoundingClientRect().top ) < 2 ? 'строка:' : 'перенос:' ) + b[ 0 ].innerText.trim();
			} ) );
		}
		check( 'Первый экран: кнопки в одну строку от 320 px; до 430 px — «Вакансии» вместо «Смотреть вакансии» (правка заказчика)',
			'строка:Вакансии|строка:Вакансии|строка:Смотреть вакансии|строка:Смотреть вакансии' === heroButtons.join( '|' ), heroButtons.join( '|' ) );
		const voicesDesk = await page.evaluate( () => document.querySelector( '.b-voices__more' ).getBoundingClientRect().top < document.querySelector( '.b-voices__list' ).getBoundingClientRect().top );
		check( 'Компьютер: «Вся команда» — в строке с заголовком «Изнутри»', voicesDesk );
		await page.setViewportSize( { width: 390, height: 900 } );
		await page.setViewportSize( { width: 1280, height: 900 } );
		const desk = await page.evaluate( () => {
			const cols = ( sel ) => new Set( [ ...document.querySelectorAll( sel ) ].map( ( c ) => Math.round( c.getBoundingClientRect().left ) ) ).size;
			return [ cols( '.b-benefits__item' ), cols( '.b-voices__card' ), cols( '.b-hiring__step' ) ];
		} );
		check( 'Компьютер 1280: условия и цитаты по 3 в ряд, шаги — 4 в ряд', JSON.stringify( desk ) === JSON.stringify( [ 3, 3, 4 ] ), JSON.stringify( desk ) );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
		const axe = await page.evaluate( async () => {
			const r = await window.axe.run( '.page-entry__content', { resultTypes: [ 'violations' ] } );
			return r.violations.filter( ( x ) => [ 'critical', 'serious' ].includes( x.impact ) ).map( ( x ) => `${ x.id } (${ x.nodes.length })` );
		} );
		check( 'axe: нет нарушений critical/serious на странице «Карьера»', 0 === axe.length, axe.join( ', ' ) );
		check( 'Консоль браузера на сайте без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1400, height: 1000 } } );
		const editorErrors = collectConsoleErrors( editor );
		await login( editor );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ own }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		const reg = await editor.evaluate( () => [ 'trust/benefits', 'trust/vacancies', 'trust/hiring-steps', 'trust/internship', 'trust/employee-quotes', 'trust/resume-form' ].map( ( name ) => {
			const type = wp.blocks.getBlockType( name );
			return [ type && type.title, type && type.category, !! ( type && type.example ) ].join( ':' );
		} ) );
		check( 'Редактор: шесть блоков («Компания» и «Формы»), у всех превью',
			JSON.stringify( reg ) === JSON.stringify( [ 'Почему у нас интересно работать:trust-company:true', 'Открытые вакансии:trust-company:true', 'Как проходит отбор:trust-company:true', 'Стажировка:trust-company:true', 'Сотрудники о работе у нас:trust-company:true', 'Отправить резюме:trust-forms:true' ] ), JSON.stringify( reg ) );
		const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-resume' ).first().waitFor( { timeout: 30000 } );
		check( 'Редактор: превью блоков на странице', 6 === await canvas.locator( '.b-vac__item' ).count() && 1 === await canvas.locator( '.b-voices__card' ).count() );
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
