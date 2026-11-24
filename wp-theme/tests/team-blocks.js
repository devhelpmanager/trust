/**
 * Тест блоков шага 4.11 — страница «Команда»: «Руководство», «Команда по направлениям»,
 * «Кто работает над вашим проектом», «Хотите в команду?».
 *
 * Запуск: node wp-theme/tests/team-blocks.js
 *
 * Демо на /komanda/ (порядок и тексты из макета o-nas/komanda.html): руководители с флажком
 * «Руководство» — ссылки «Статьи в блоге» (демо-авторы), «Ответы на вопросы», email; 12
 * специалистов без руководства, фильтр отделов с числом людей и строкой «Показано» (без
 * перезагрузки и без JavaScript), «Статьи автора» у пяти авторов; главная карточка и 6 ролей;
 * кнопки «Хотите в команду?» на страницу «Карьера». Свои данные: руководители вручную,
 * руководство в общем списке, фильтр выключен, без главной карточки, свои кнопки. Демо-авторы
 * (повторный импорт их не дублирует). Сверка с макетом на 1280 / 768 / 390 px, раскладки
 * 4 / 3 / 2 / 1 колонки, ширины 1440–280, axe-core, консоль, редактор.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp } = require( './lib' );

const PAGE = `${ URL }/komanda/`;
const MOCKUP = 'o-nas/komanda.html';
const MARK = 'ТестКоманда';

/** PHP: блок по имени и полям (PHP-массив). */
const block = ( name, fields ) => `Trust\\Blocks::serialize( '${ name }', ${ fields } )`;

/**
 * Опубликованная тестовая страница «Команда» с блоками.
 *
 * @param {string} title  Заголовок.
 * @param {string} blocks PHP-выражение: разметка блоков.
 * @return {string} ID.
 */
function createPage( title, blocks ) {
	const content = wp( 'eval', `echo ${ blocks };` );
	const id = wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK } ${ title }`, '--post_status=publish', `--post_content=${ content }`, '--porcelain' );
	wp( 'post', 'meta', 'update', id, '_wp_page_template', 'templates/team.php' );
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
	const base = path.join( OUT, `team-${ name }-${ width }` );
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
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const created = [];

	try {
		// ===== Демо-авторы =====
		const authors = JSON.parse( wp( 'eval', 'echo wp_json_encode( array_map( static function ( $u ) { $m = get_posts( array( "post_type" => "team", "meta_key" => "trust_user", "meta_value" => $u->ID, "fields" => "ids" ) ); return array( get_user_meta( $u->ID, "_trust_demo_id", true ), implode( ",", $u->roles ), count_user_posts( $u->ID, "post", true ) > 0, $m ? get_post_field( "post_name", $m[0] ) : "" ); }, get_users( array( "meta_key" => "_trust_demo_id", "orderby" => "login" ) ) ) );' ) );
		check( 'Демо-авторы: 8 пользователей с ролью «Автор», у каждого есть статья и связанный сотрудник',
			8 === authors.length && authors.every( ( a ) => 'author' === a[ 1 ] && a[ 2 ] && a[ 0 ] === `user:${ a[ 3 ] }` ), JSON.stringify( authors ) );
		const again = wp( 'eval', 'echo wp_json_encode( Trust\\Demo\\Importer::import_users() );' );
		check( 'Повторный импорт демо-авторов не создаёт новых пользователей', '{"created":[],"skipped":[]}' === again, again );

		// ===== Демо на «Команде» =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const order = await page.evaluate( () => [ ...document.querySelectorAll( '.page-entry__content > *' ) ].map( ( s ) => s.className.split( ' ' )[ 0 ] ) );
		check( 'Порядок блоков как в макете: первый экран, руководство, специалисты, роли, «Хотите в команду?», «Обсудим ваш проект»',
			JSON.stringify( order ) === JSON.stringify( [ 'b-hero-section', 'b-leaders', 'b-team', 'b-roles', 'b-join', 'b-cta' ] ), order.join( ', ' ) );

		const l = await page.locator( '.b-leaders' ).evaluate( ( el ) => ( {
			head: [ el.querySelector( '.eyebrow' ).textContent, el.querySelector( 'h2' ).textContent, el.getAttribute( 'aria-labelledby' ) === el.querySelector( 'h2' ).id ],
			cards: [ ...el.querySelectorAll( '.b-leaders__card' ) ].map( ( c ) => ( {
				name: c.querySelector( 'h3' ).textContent,
				role: c.querySelector( '.b-leaders__role' ).textContent,
				text: !! c.querySelector( '.b-leaders__text' ),
				alt: c.querySelector( 'img' ).alt,
				lazy: c.querySelector( 'img' ).loading,
				links: [ ...c.querySelectorAll( '.b-leaders__link' ) ].map( ( a ) => a.textContent.trim() + ' ' + a.getAttribute( 'href' ).replace( location.origin, '' ) ),
			} ) ),
		} ) );
		check( '«Руководство»: надзаголовок, H2, три карточки (имя H3, должность, описание, фото с alt «имя, должность», lazy)',
			'Руководство|Кто отвечает за результат|true' === l.head.join( '|' ) && 3 === l.cards.length && l.cards.every( ( c ) => '[Имя Фамилия]' === c.name && c.text && 'lazy' === c.lazy )
			&& '[Имя Фамилия], основатель и генеральный директор' === l.cards[ 0 ].alt && 'Руководитель SEO-отдела' === l.cards[ 1 ].role, JSON.stringify( l ) );
		check( '«Руководство»: ссылки как в макете — «Статьи в блоге» → страница сотрудника, email, «Ответы на вопросы» → вопросы SEO',
			JSON.stringify( l.cards.map( ( c ) => c.links ) ) === JSON.stringify( [
				[ 'Статьи в блоге /komanda/ceo/', 'ceo@example.ru mailto:ceo@example.ru' ],
				[ 'Статьи в блоге /komanda/seo-lead/', 'Ответы на вопросы /seo/voprosy/' ],
				[ 'Статьи в блоге /komanda/dev-lead/' ],
			] ), JSON.stringify( l.cards.map( ( c ) => c.links ) ) );

		const t = await page.locator( '.b-team' ).evaluate( ( el ) => ( {
			bg: getComputedStyle( el ).backgroundColor,
			head: el.querySelector( '.eyebrow' ).textContent + '|' + el.querySelector( 'h2' ).textContent,
			chips: [ ...el.querySelectorAll( '.filter-chip' ) ].map( ( b ) => b.firstChild.textContent + ' ' + b.querySelector( '.filter-chip__count' ).textContent + ' ' + b.getAttribute( 'aria-pressed' ) ),
			group: el.querySelector( '.filter-chips' ).getAttribute( 'role' ) + ':' + el.querySelector( '.filter-chips' ).getAttribute( 'aria-label' ),
			status: el.querySelector( '.filter-status' ).textContent + ':' + el.querySelector( '.filter-status' ).getAttribute( 'aria-live' ),
			cards: [ ...el.querySelectorAll( '.b-team__card' ) ].map( ( c ) => [ c.querySelector( '.b-team__dept' ).textContent, c.querySelector( 'h3' ).textContent, c.querySelector( '.b-team__role' ).textContent, /^Опыт: \d+ (год|года|лет)$/.test( c.querySelector( '.b-team__exp' ).textContent ), c.querySelector( '.b-team__blog' ) ? new URL( c.querySelector( '.b-team__blog' ).href ).pathname : '' ] ),
			alt: el.querySelector( '.b-team__photo' ).alt,
			described: [ ...el.querySelectorAll( '.b-team__blog' ) ].every( ( a ) => document.getElementById( a.getAttribute( 'aria-describedby' ) )?.tagName === 'H3' ),
		} ) );
		check( '«Команда по направлениям»: лавандовый фон, шапка, кнопки отделов с числом людей (в порядке появления), «Все» нажата',
			'rgb(248, 245, 255)' === t.bg && 'Специалисты|Команда по направлениям' === t.head && 'group:Фильтр по отделам' === t.group
			&& JSON.stringify( t.chips ) === JSON.stringify( [ 'Все 12 true', 'SEO 4 false', 'Разработка 3 false', 'Дизайн 1 false', 'Реклама 2 false', 'Менеджмент 2 false' ] ), JSON.stringify( [ t.chips, t.group ] ) );
		check( '«Команда по направлениям»: «Показаны все специалисты: 12» (aria-live), 12 карточек без руководства: отдел, имя H3, должность, «Опыт: N лет»',
			'Показаны все специалисты: 12:polite' === t.status && 12 === t.cards.length && t.cards.every( ( c ) => '[Имя Фамилия]' === c[ 1 ] && c[ 3 ] ) && 'SEO|Старший SEO-специалист' === t.cards[ 0 ][ 0 ] + '|' + t.cards[ 0 ][ 2 ] && '[Имя Фамилия], старший SEO-специалист' === t.alt,
			JSON.stringify( [ t.status, t.cards.length, t.alt ] ) );
		check( '«Статьи автора» — у пяти авторов статей (как в макете), ведёт на страницу сотрудника, связана с именем',
			JSON.stringify( t.cards.map( ( c ) => c[ 4 ] ).filter( Boolean ) ) === JSON.stringify( [ '/komanda/seo-senior/', '/komanda/seo-specialist/', '/komanda/content/', '/komanda/backend/', '/komanda/ppc/' ] ) && t.described,
			JSON.stringify( t.cards.map( ( c ) => c[ 4 ] ) ) );

		const filterState = () => page.locator( '.b-team' ).evaluate( ( el ) => [
			el.querySelector( '.filter-status' ).textContent,
			[ ...el.querySelectorAll( '.b-team__card' ) ].filter( ( c ) => ! c.hidden ).map( ( c ) => c.querySelector( '.b-team__dept' ).textContent ).join( ',' ),
			[ ...el.querySelectorAll( '.filter-chip[aria-pressed="true"]' ) ].map( ( b ) => b.firstChild.textContent ).join( ',' ),
		] );
		const url = page.url();
		await page.locator( '.filter-chip', { hasText: 'Разработка' } ).click();
		const dev = await filterState();
		await page.locator( '.filter-chip', { hasText: 'Менеджмент' } ).click();
		const pm = await filterState();
		await page.locator( '.filter-chip', { hasText: 'Все' } ).click();
		const all = await filterState();
		check( 'Фильтр без перезагрузки: «Разработка» — 3 из 12, «Менеджмент» — 2 из 12, «Все» — снова 12; нажата одна кнопка',
			JSON.stringify( [ dev, pm ] ) === JSON.stringify( [ [ 'Показано: 3 из 12', 'Разработка,Разработка,Разработка', 'Разработка' ], [ 'Показано: 2 из 12', 'Менеджмент,Менеджмент', 'Менеджмент' ] ] )
			&& 'Показаны все специалисты: 12' === all[ 0 ] && 12 === all[ 1 ].split( ',' ).length && 'Все' === all[ 2 ] && url === page.url(), JSON.stringify( [ dev, pm, all ] ) );

		const r = await page.locator( '.b-roles' ).evaluate( ( el ) => ( {
			head: [ el.querySelector( '.eyebrow' ).textContent, el.querySelector( 'h2' ).textContent, el.querySelector( '.section-lead' ).textContent ],
			main: [ getComputedStyle( el.querySelector( '.b-roles__main' ) ).backgroundColor, el.querySelector( '.b-roles__label' ).textContent, el.querySelector( '.b-roles__main-title' ).tagName + ' ' + el.querySelector( '.b-roles__main-title' ).textContent, el.querySelectorAll( '.b-roles__points li svg' ).length ],
			roles: [ ...el.querySelectorAll( '.b-roles__item' ) ].map( ( li ) => [ !! li.querySelector( '.b-roles__icon svg' ), li.querySelector( 'h3' ).textContent ] ),
		} ) );
		check( '«Кто работает над вашим проектом»: шапка с вводным текстом, тёмная карточка «Ваш контакт — Аккаунт-менеджер» (H3) с 3 пунктами, 6 ролей со значком и H3',
			'Как устроена работа|Кто работает над вашим проектом|Состав команды подбираем под задачу и называем до начала работ.' === r.head.join( '|' )
			&& JSON.stringify( r.main ) === JSON.stringify( [ 'rgb(30, 27, 75)', 'Ваш контакт', 'H3 Аккаунт-менеджер', 3 ] )
			&& 6 === r.roles.length && r.roles.every( ( x ) => x[ 0 ] ) && 'Специалист по рекламе' === r.roles[ 5 ][ 1 ], JSON.stringify( r ) );

		const j = await page.locator( '.b-join' ).evaluate( ( el ) => ( {
			bg: getComputedStyle( el ).backgroundColor,
			head: el.querySelector( '.eyebrow' ).textContent + '|' + el.querySelector( 'h2' ).textContent,
			buttons: [ ...el.querySelectorAll( '.b-join__btn' ) ].map( ( a ) => [ a.textContent.trim(), a.getAttribute( 'href' ).replace( location.origin, '' ), a.classList.contains( 'btn--white' ), !! a.querySelector( 'svg' ) ] ),
		} ) );
		check( '«Хотите в команду?»: фиолетовый фон, «Карьера» и H2; «Открытые вакансии» (белая, со стрелкой) и «Отправить резюме» → страница «Карьера» #vacancies и #resume',
			'rgb(91, 33, 182)' === j.bg && 'Карьера|Хотите в команду?' === j.head
			&& JSON.stringify( j.buttons ) === JSON.stringify( [ [ 'Открытые вакансии', '/karera/#vacancies', true, true ], [ 'Отправить резюме', '/karera/#resume', false, false ] ] ), JSON.stringify( j ) );

		// ===== Раскладки =====
		const layout = async ( width ) => {
			await page.setViewportSize( { width, height: 900 } );
			return page.evaluate( () => {
				const tops = ( s ) => [ ...document.querySelectorAll( s ) ].map( ( e ) => Math.round( e.getBoundingClientRect().top ) );
				const perRow = ( s ) => tops( s ).filter( ( y, i, a ) => y === a[ 0 ] ).length;
				const lead = document.querySelector( '.b-leaders__card' );
				const photo = lead.querySelector( '.b-leaders__photo' ).getBoundingClientRect();
				const body = lead.querySelector( '.b-leaders__body' ).getBoundingClientRect();
				const mem = document.querySelector( '.b-team__card' );
				const mPhoto = mem.querySelector( '.b-team__photo' ).getBoundingClientRect();
				const mBody = mem.querySelector( '.b-team__body' ).getBoundingClientRect();
				const main = document.querySelector( '.b-roles__main' ).getBoundingClientRect();
				const role = document.querySelector( '.b-roles__item' ).getBoundingClientRect();
				const rItem = document.querySelector( '.b-roles__item' );
				const [ rIcon, rTitle, rText ] = [ '.b-roles__icon', '.b-roles__title', '.b-roles__text' ].map( ( s ) => rItem.querySelector( s ).getBoundingClientRect() );
				const jb = [ ...document.querySelectorAll( '.b-join__btn' ) ].map( ( a ) => a.getBoundingClientRect() );
				const jIn = document.querySelector( '.b-join__inner' ).getBoundingClientRect();
				return {
					leaders: perRow( '.b-leaders__card' ),
					leaderPhoto: body.left > photo.right ? 'left:' + Math.round( photo.width ) : 'top:' + ( Math.round( photo.width / photo.height * 100 ) / 100 ),
					team: perRow( '.b-team__card' ),
					memberPhoto: mBody.left > mPhoto.right ? 'left:' + Math.round( mPhoto.width ) : 'top',
					roles: role.left > main.right ? 'side' : 'below',
					// Правки заказчика: название роли справа от значка; кнопки «Хотите в команду?» — в одну строку.
					roleRow: rTitle.left >= rIcon.right + 12 && rTitle.top < rIcon.bottom && rTitle.bottom > rIcon.top && rText.top >= rIcon.bottom && Math.abs( rText.left - rIcon.left ) < 1,
					joinRow: Math.abs( jb[ 0 ].top - jb[ 1 ].top ) < 1 && jb[ 1 ].left > jb[ 0 ].right && jb[ 1 ].right <= jIn.right + 0.5,
				};
			} );
		};
		const lay = {};
		for ( const width of [ 1280, 1000, 700, 420, 390, 320 ] ) {
			lay[ width ] = await layout( width );
		}
		check( 'Раскладки: руководство 3 → 1 колонка (фото 150 px слева) → фото сверху 4:3; специалисты 4 / 3 / 2 / 1 (фото 84 px слева); роли — справа от карточки, уже — под ней',
			'3:top' === lay[ 1280 ].leaders + ':' + lay[ 1280 ].leaderPhoto.split( ':' )[ 0 ] && '1:left:150' === lay[ 700 ].leaders + ':' + lay[ 700 ].leaderPhoto && 'top:1.33' === lay[ 420 ].leaderPhoto
			&& '4,3,2,1,1' === [ 1280, 1000, 700, 420, 390 ].map( ( w ) => lay[ w ].team ).join() && 'left:84' === lay[ 390 ].memberPhoto && 'top' === lay[ 700 ].memberPhoto
			&& 'side' === lay[ 1280 ].roles && 'below' === lay[ 700 ].roles, JSON.stringify( lay ) );
		check( '«Кто работает над проектом»: название роли справа от значка, текст под ними (1280 / 1000 / 700 / 390 / 320 px) — правка заказчика',
			[ 1280, 1000, 700, 420, 390, 320 ].every( ( w ) => lay[ w ].roleRow ), JSON.stringify( Object.fromEntries( Object.entries( lay ).map( ( [ w, v ] ) => [ w, v.roleRow ] ) ) ) );
		check( '«Хотите в команду?»: обе кнопки в одну строку на всех ширинах, включая телефон 390 и 320 px — правка заказчика',
			[ 1280, 1000, 700, 420, 390, 320 ].every( ( w ) => lay[ w ].joinRow ), JSON.stringify( Object.fromEntries( Object.entries( lay ).map( ( [ w, v ] ) => [ w, v.joinRow ] ) ) ) );
		await page.setViewportSize( { width: 1280, height: 900 } );

		// ===== Без JavaScript =====
		const noJs = await browser.newContext( { javaScriptEnabled: false, viewport: { width: 1280, height: 900 } } );
		const plain = await noJs.newPage();
		await plain.goto( PAGE, { waitUntil: 'domcontentloaded' } );
		const nj = await plain.evaluate( () => [ getComputedStyle( document.querySelector( '.filter-chips' ) ).display, [ ...document.querySelectorAll( '.b-team__card' ) ].filter( ( c ) => c.offsetParent ).length ] );
		await noJs.close();
		check( 'Без JavaScript: кнопок отделов не видно, показаны все 12 специалистов', 'none' === nj[ 0 ] && 12 === nj[ 1 ], JSON.stringify( nj ) );

		// ===== Свои данные =====
		const ids = JSON.parse( wp( 'eval', 'echo wp_json_encode( array_map( static fn( $s ) => get_page_by_path( $s, OBJECT, "team" )->ID, array( "qa", "ceo" ) ) );' ) );
		const own = createPage( 'Блоки', [
			block( 'team-leaders', `array( 'title' => 'Вручную', 'source' => 'manual', 'members' => array( ${ ids[ 0 ] }, ${ ids[ 1 ] } ), 'email' => 0 )` ),
			block( 'team-grid', "array( 'title' => 'Все вместе', 'leaders' => 1, 'filter' => 0, 'blog_label' => 'Читать статьи' )" ),
			block( 'roles', "array( 'title' => 'Только роли', 'main' => 0, 'items' => array( array( 'icon' => 'chart', 'title' => 'Аналитик', 'text' => 'Считает' ) ) )" ),
			block( 'join', "array( 'title' => 'Свои кнопки', 'buttons' => array( array( 'link' => array( 'title' => 'Написать нам', 'url' => '#contacts', 'target' => '' ), 'icon' => 'right' ) ) )" ),
		].join( ' . ' ) );
		created.push( own );
		await page.goto( `${ URL }/?page_id=${ own }`, { waitUntil: 'networkidle' } );
		const x = await page.evaluate( () => ( {
			leaders: [ ...document.querySelectorAll( '.b-leaders__card' ) ].map( ( c ) => [ c.querySelector( '.b-leaders__role' ).textContent, [ ...c.querySelectorAll( '.b-leaders__link' ) ].map( ( a ) => a.textContent.trim() ).join( ',' ) ] ),
			team: [ document.querySelectorAll( '.b-team__card' ).length, !! document.querySelector( '.filter-chips' ), !! document.querySelector( '.filter-status' ), document.querySelector( '.b-team__blog' ).textContent.trim() ],
			roles: [ !! document.querySelector( '.b-roles__main' ), document.querySelectorAll( '.b-roles__item' ).length ],
			join: [ ...document.querySelectorAll( '.b-join__btn' ) ].map( ( a ) => a.textContent.trim() + ' ' + a.getAttribute( 'href' ) ),
		} ) );
		check( '«Руководство» вручную: порядок выбора (тестировщик, директор), без email — у директора только «Статьи в блоге»',
			JSON.stringify( x.leaders ) === JSON.stringify( [ [ 'Тестировщик', '' ], [ 'Основатель и генеральный директор', 'Статьи в блоге' ] ] ), JSON.stringify( x.leaders ) );
		check( '«Команда по направлениям»: руководство в списке — 15 карточек, фильтр выключен, своя подпись ссылки', JSON.stringify( x.team ) === JSON.stringify( [ 15, false, false, 'Читать статьи' ] ), JSON.stringify( x.team ) );
		check( '«Кто работает над проектом» без главной карточки; «Хотите в команду?» со своей кнопкой',
			JSON.stringify( x.roles ) === JSON.stringify( [ false, 1 ] ) && JSON.stringify( x.join ) === JSON.stringify( [ 'Написать нам #contacts' ] ), JSON.stringify( [ x.roles, x.join ] ) );

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
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
		const axe = await page.evaluate( async () => {
			const res = await window.axe.run( '.page-entry__content', { resultTypes: [ 'violations' ] } );
			return res.violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => `${ v.id } (${ v.nodes.length })` );
		} );
		check( 'axe: нет нарушений critical/serious на странице «Команда»', 0 === axe.length, axe.join( ', ' ) );
		check( 'Консоль браузера на сайте без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Сверка с макетом =====
		const sections = [
			[ 'leaders', '.b-leaders', 'section[aria-labelledby="lead-title"]' ],
			[ 'grid', '.b-team', 'section[aria-labelledby="staff-title"]' ],
			[ 'roles', '.b-roles', 'section[aria-labelledby="roles-title"]' ],
			[ 'join', '.b-join', 'section[aria-labelledby="join-title"]' ],
		];
		for ( const width of [ 1280, 768, 390 ] ) {
			for ( const [ name, theme, mock ] of sections ) {
				await compare( page, name, width, theme, mock );
			}
		}

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1400, height: 1000 } } );
		const editorErrors = collectConsoleErrors( editor );
		await login( editor );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ own }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ), null, { timeout: 60000 } );
		const reg = await editor.evaluate( () => [ 'trust/team-leaders', 'trust/team-grid', 'trust/roles', 'trust/join' ].map( ( name ) => {
			const type = wp.blocks.getBlockType( name );
			return [ type && type.title, type && type.category, !! ( type && type.example ) ].join( ':' );
		} ) );
		check( 'Редактор: четыре блока в категории «Компания», у всех превью',
			JSON.stringify( reg ) === JSON.stringify( [ 'Руководство', 'Команда по направлениям', 'Кто работает над вашим проектом', 'Хотите в команду?' ].map( ( n ) => `${ n }:trust-company:true` ) ), JSON.stringify( reg ) );
		const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-join' ).first().waitFor( { timeout: 30000 } );
		check( 'Редактор: превью блоков на странице', 2 === await canvas.locator( '.b-leaders__card' ).count() && 15 === await canvas.locator( '.b-team__card' ).count() );
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
