/**
 * Тест блоков шага 4.18 — «Ответы SEO-специалистов» (страница «Вопросы SEO») и «Как бы
 * поступил я» (ответ на вопрос).
 *
 * Запуск: node wp-theme/tests/answers-blocks.js
 *
 * Демо на /seo/voprosy/ (макет seo-vopros/vopros-seo.html): после первого экрана и «Важно знать»,
 * якорь #answers (на него ведёт «Читать ответы»), 20 опубликованных вопросов (на утверждении — нет),
 * новые первыми; карточка — тема, «Спрашивает Анна», вопрос (H3), начало ответа («Отрывок» или
 * первый абзац; на телефоне 3 строки), инициалы и имя специалиста, «ответ от» дата, «Читать ответ»;
 * 2 колонки от ~800 px. Пагинация на своей странице (по 6, 42 ответа = 7 страниц): «‹ Назад 1 2 3 4
 * … 7 Вперёд ›», на узких экранах сокращается до «‹ 1 … 7 ›», «…» там, где скрыты номера, ссылки
 * /page/N/#answers, страница за пределами — 404. ItemList в микроразметке. «Как бы поступил я» в
 * первом ответе (макет seo-vopros/otvet.html): заголовок H2 (или H3), пункты-галочки из вложенного
 * списка, текст и кнопка «Заказать технический аудит»; без текста и кнопки — без нижней строки.
 * Сверка с макетами на 1280 / 768 / 390 px, ширины 1440–280, axe-core, консоль, редактор.
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp } = require( './lib' );

const PAGE = `${ URL }/seo/voprosy/`;
const ANSWER = `${ URL }/seo/voprosy/pochemu-yandeks-ne-indeksiruet-novye-stranicy-kataloga/`;
const MARK = 'ТестОтветы';

/** PHP: блок по имени и полям (PHP-массив). */
const block = ( name, fields, inner = '' ) => `Trust\\Blocks::serialize( '${ name }', ${ fields }${ inner ? `, ${ inner }` : '' } )`;

/**
 * Сравнение снимков темы и макета.
 *
 * @param {import('playwright').Page} page   Страница.
 * @param {Object}                    o      name, width, url, theme, mockup, mock, before(page), mockBefore(page).
 */
async function compare( page, o ) {
	const base = path.join( OUT, `answers-${ o.name }-${ o.width }` );
	await page.setViewportSize( { width: o.width, height: 900 } );
	await page.goto( o.url, { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	if ( o.before ) {
		await o.before( page );
	}
	await isolate( page, o.theme );
	await page.waitForTimeout( 200 );
	await page.locator( o.theme ).first().screenshot( { path: `${ base }-theme.png` } );
	await page.goto( mockupUrl( o.mockup ), { waitUntil: 'networkidle' } );
	await settle( page );
	await page.mouse.move( 0, 0 );
	if ( o.mockBefore ) {
		await o.mockBefore( page );
	}
	await isolate( page, o.mock );
	await page.waitForTimeout( 200 );
	await page.locator( o.mock ).first().screenshot( { path: `${ base }-mockup.png` } );
	const diff = comparePng( `${ base }-mockup.png`, `${ base }-theme.png`, `${ base }-diff.png` );
	check( `Макет ${ o.mockup }, ${ o.name }, ${ o.width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
}

/**
 * Пагинация: элементы по порядку и видимые на текущей ширине.
 *
 * @param {import('playwright').Page} page Страница.
 */
function pagesState( page ) {
	return page.evaluate( () => {
		const nav = document.querySelector( '.pager' );
		if ( ! nav ) {
			return null;
		}
		const label = ( el ) => {
			if ( el.classList.contains( 'pager__arrow' ) ) {
				const text = el.querySelector( '.pager__label' );
				const shown = 'none' !== getComputedStyle( text ).display;
				return ( el.classList.contains( 'is-next' ) ? '›' : '‹' ) + ( shown ? text.textContent : '' ) + ( 'A' === el.tagName ? '' : '-' );
			}
			return el.textContent.trim() + ( 'page' === el.getAttribute( 'aria-current' ) ? '*' : '' );
		};
		const items = [ ...nav.children ];
		return {
			all: items.map( label ).join( ' ' ),
			visible: items.filter( ( el ) => 'none' !== getComputedStyle( el ).display ).map( label ).join( ' ' ),
			hrefs: items.filter( ( el ) => 'A' === el.tagName ).map( ( a ) => a.getAttribute( 'href' ).replace( /^https?:\/\/[^/]+/, '' ) ),
		};
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
		// ===== Демо: место на странице, шапка, карточки =====
		await page.goto( PAGE, { waitUntil: 'networkidle' } );
		const head = await page.evaluate( () => {
			const s = document.querySelector( '.b-answers' );
			const h2 = s.querySelector( 'h2' );
			const order = [ ...document.querySelectorAll( 'main section' ) ].map( ( x ) => x.className.split( ' ' )[ 0 ] ).filter( ( c ) => /^b-|hero/.test( c ) );
			return {
				order,
				id: s.id,
				link: !! document.querySelector( 'a[href="#answers"]' ),
				eyebrow: s.querySelector( '.eyebrow' ).textContent,
				h2: h2.textContent,
				labelled: s.getAttribute( 'aria-labelledby' ) === h2.id,
				lead: s.querySelector( '.section-lead' ).textContent,
				list: s.querySelector( 'ul' ).getAttribute( 'aria-label' ),
				art: ( ( a ) => a && [ a.dataset.art, a.getAttribute( 'aria-hidden' ), !! a.querySelector( 'svg' ), a.parentElement.classList.contains( 'section-top' ), a.previousElementSibling.classList.contains( 'b-answers__head' ) ] )( s.querySelector( '.section-art' ) ),
			};
		} );
		check( 'Демо /seo/voprosy/: «Ответы SEO-специалистов» после первого экрана и «Важно знать», якорь #answers («Читать ответы» ведёт к ним)',
			'b-answers' === head.order.at( -1 ) && 'answers' === head.id && head.link, JSON.stringify( head.order ) );
		check( 'Шапка: «Вопросы и ответы», H2 «Ответы SEO-специалистов», вводный текст; список с подписью для скринридеров',
			'Вопросы и ответы' === head.eyebrow && 'Ответы SEO-специалистов' === head.h2 && head.labelled && head.lead.startsWith( 'Вопросы читателей' ) && 'Ответы на вопросы' === head.list, JSON.stringify( head ) );
		check( 'Справа от шапки — рисунок «Эксперт отвечает на вопросы» (по умолчанию), скрыт от скринридеров',
			JSON.stringify( head.art ) === JSON.stringify( [ 'expert-answers', 'true', true, true, true ] ), JSON.stringify( head.art ) );
		const published = Number( wp( 'eval', 'echo count( get_posts( array( "post_type" => "question", "post_status" => "publish", "numberposts" => -1, "fields" => "ids" ) ) );' ) );
		const cards = await page.evaluate( () => [ ...document.querySelectorAll( '.b-answers__card' ) ].map( ( a ) => ( {
			href: a.getAttribute( 'href' ),
			topic: a.querySelector( '.b-answers__topic' ) ? a.querySelector( '.b-answers__topic' ).textContent : '',
			meta: a.querySelector( '.b-answers__meta' ).textContent,
			h3: a.querySelector( 'h3' ).textContent,
			excerpt: a.querySelector( '.b-answers__excerpt' ).textContent,
			avatar: [ a.querySelector( '.b-answers__avatar' ).textContent, a.querySelector( '.b-answers__avatar' ).getAttribute( 'aria-hidden' ) ],
			name: a.querySelector( '.b-answers__name' ).textContent,
			date: [ a.querySelector( '.b-answers__date' ).textContent, a.querySelector( 'time' ).getAttribute( 'datetime' ) ],
			more: [ a.querySelector( '.b-answers__more' ).getAttribute( 'aria-hidden' ), ...[ ...a.querySelectorAll( '.b-answers__more-v' ) ].map( ( v ) => v.textContent.trim() ), a.querySelectorAll( '.b-answers__more svg' ).length ],
		} ) ) );
		check( 'Все опубликованные вопросы (20, вопрос «на утверждении» не показан), новые первыми, без пагинации (одна страница)',
			20 === published && 20 === cards.length && 'Почему Яндекс не индексирует новые страницы каталога?' === cards[ 0 ].h3 && 'Нужно ли закрывать от индексации страницы фильтров?' === cards[ 1 ].h3 && ! await page.locator( '.pager' ).count(), `${ published } / ${ cards.length }` );
		check( 'Карточка: тема, «Спрашивает Анна», вопрос (H3), начало ответа из «Отрывка», инициалы «ИФ» (скрыты от скринридеров), имя, «ответ от 6 ноября 2026» (datetime), «Читать ответ» / «Ответ» / стрелка (оформление, скрыто от скринридеров), ссылка на ответ',
			'Индексация' === cards[ 0 ].topic && 'ИндексацияСпрашивает Анна' === cards[ 0 ].meta && cards[ 0 ].excerpt.startsWith( 'Начните с «Исключённых страниц»' ) && 'ИФ,true' === cards[ 0 ].avatar.join() && '[Имя Фамилия]' === cards[ 0 ].name &&
			'ответ от 6 ноября 2026,2026-11-06' === cards[ 0 ].date.join() && JSON.stringify( cards[ 0 ].more ) === JSON.stringify( [ 'true', '', 'Ответ', 'Читать ответ', 3 ] ) && cards[ 0 ].href.endsWith( '/seo/voprosy/pochemu-yandeks-ne-indeksiruet-novye-stranicy-kataloga/' ), JSON.stringify( cards[ 0 ] ) );
		check( 'Без «Отрывка» — первый абзац ответа', cards[ 1 ].excerpt.startsWith( 'Не все. Фильтры, под которые есть спрос' ), cards[ 1 ].excerpt );
		const ld = await page.evaluate( () => {
			const graph = [ ...document.querySelectorAll( 'script[type="application/ld+json"]' ) ].flatMap( ( s ) => JSON.parse( s.textContent )[ '@graph' ] || [] );
			const list = graph.find( ( n ) => 'ItemList' === n[ '@type' ] );
			const pg = graph.find( ( n ) => n.mainEntity );
			return [ list && list.numberOfItems, list && list.itemListElement[ 0 ].name, pg && pg.mainEntity[ '@id' ] === ( list && list[ '@id' ] ) ];
		} );
		check( 'Микроразметка: ItemList показанных ответов (20, первым — новый), главное содержимое страницы', JSON.stringify( ld ) === JSON.stringify( [ 20, 'Почему Яндекс не индексирует новые страницы каталога?', true ] ), JSON.stringify( ld ) );
		check( 'Страница за пределами списка /seo/voprosy/page/2/ — 404', 404 === ( await page.request.get( `${ PAGE }page/2/` ) ).status() );

		// ===== Раскладка =====
		const layout = {};
		const links = {};
		for ( const width of [ 1280, 901, 900, 768, 390, 360, 320, 280 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( PAGE, { waitUntil: 'domcontentloaded' } );
			[ layout[ width ], links[ width ] ] = await page.evaluate( () => {
				const card = document.querySelector( '.b-answers__card' );
				const p = card.querySelector( '.b-answers__excerpt' );
				const cs = getComputedStyle( p );
				// Ссылка: варианты лежат друг на друге; виден верхний из тех, чья подпись поместилась
				// в строку (не перенесена на обрезанную вторую строку), его фон закрывает остальные.
				const vs = [ ...card.querySelectorAll( '.b-answers__more-v' ) ];
				const fit = vs.map( ( v ) => Math.abs( v.querySelector( '.b-answers__more-in' ).getBoundingClientRect().top - v.getBoundingClientRect().top ) < 1 );
				const shown = vs[ fit.lastIndexOf( true ) ];
				const inner = shown.querySelector( '.b-answers__more-in' );
				const box = shown.getBoundingClientRect();
				const ir = inner.getBoundingClientRect();
				const arrow = shown.querySelector( 'svg' ).getBoundingClientRect();
				const inside = ( r ) => r.left >= box.left - 0.5 && r.right <= box.right + 0.5 && r.top >= box.top - 0.5 && r.bottom <= box.bottom + 0.5;
				const who = card.querySelector( '.b-answers__who' ).getBoundingClientRect();
				const art = document.querySelector( '#answers .section-art' );
				const lead = document.querySelector( '#answers .section-lead' ).getBoundingClientRect();
				return [ [
					new Set( [ ...document.querySelectorAll( '.b-answers__item' ) ].map( ( li ) => Math.round( li.getBoundingClientRect().left ) ) ).size,
					Math.round( p.getBoundingClientRect().height / parseFloat( cs.lineHeight ) ),
					getComputedStyle( card.querySelector( 'h3' ) ).fontSize,
					getComputedStyle( art ).display,
					Math.round( document.querySelector( '#answers .b-answers__inner' ).getBoundingClientRect().width - lead.width ),
				], [
					shown.textContent.trim() || '→',
					fit.join(),
					inside( ir ) && inside( arrow ), // подпись и стрелка целиком в строке, не обрезаны
					getComputedStyle( inner ).backgroundColor === getComputedStyle( card ).backgroundColor, // закрывает варианты под собой
					who.right <= ir.left, // рядом с именем, не поверх
					arrow.width > 0,
				] ];
			} );
		}
		check( 'Две колонки от ~800 px ширины списка (окно 900), уже — одна; на телефоне начало ответа — 3 строки, вопрос 17 px',
			2 === layout[ 1280 ][ 0 ] && 2 === layout[ 900 ][ 0 ] && 1 === layout[ 768 ][ 0 ] && 1 === layout[ 390 ][ 0 ] && 3 === layout[ 390 ][ 1 ] && '17px' === layout[ 390 ][ 2 ] && '19px' === layout[ 1280 ][ 2 ], JSON.stringify( layout ) );
		check( 'Рисунок справа от шапки — шире 900 px; уже — скрыт, вводный текст во всю ширину блока',
			'block' === layout[ 1280 ][ 3 ] && 'block' === layout[ 901 ][ 3 ] && 'none' === layout[ 900 ][ 3 ] && 'none' === layout[ 390 ][ 3 ] && 0 === layout[ 900 ][ 4 ] && 0 === layout[ 768 ][ 4 ] && 0 === layout[ 390 ][ 4 ], JSON.stringify( layout ) );
		check( 'Ссылка в карточке: «Читать ответ →»; не помещается рядом с именем — «Ответ →» (390, 360 px), и он не помещается — только стрелка (320, 280 px); стрелка видна всегда, подпись не налезает на имя',
			'Читать ответ' === links[ 1280 ][ 0 ] && 'Читать ответ' === links[ 768 ][ 0 ] && 'Ответ' === links[ 390 ][ 0 ] && 'Ответ' === links[ 360 ][ 0 ] && '→' === links[ 320 ][ 0 ] && '→' === links[ 280 ][ 0 ] &&
			Object.values( links ).every( ( l ) => /^true(,true)*(,false)*$/.test( l[ 1 ] ) && l[ 2 ] && l[ 3 ] && l[ 4 ] && l[ 5 ] ), JSON.stringify( links ) );

		// ===== Рисунок справа от шапки: длинный вводный текст, выбор рисунка, без рисунка =====
		const longLead = 'Вопросы читателей и ответы наших специалистов. Новые ответы — сверху. '.repeat( 5 ).trim();
		const artPages = {};
		for ( const art of [ '', 'ai-data', 'search-algorithms', 'none' ] ) {
			const fields = `array( 'title' => 'Ответы', 'lead' => '${ longLead }', 'per_page' => 6${ art ? `, 'head_art' => '${ art }'` : '' } )`;
			const id = wp( 'eval', `$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => '${ MARK } рисунок ${ art || 'default' }', 'post_content' => wp_slash( ${ block( 'answers-list', fields ) } ) ) ); update_post_meta( $id, '_wp_page_template', 'templates/questions.php' ); echo $id;` );
			created.push( id );
			artPages[ art || 'default' ] = `${ URL }/?page_id=${ id }`;
		}
		const arts = {};
		for ( const [ key, url ] of Object.entries( artPages ) ) {
			for ( const width of [ 1280, 1000, 768 ] ) {
				await page.setViewportSize( { width, height: 900 } );
				await page.goto( url, { waitUntil: 'domcontentloaded' } );
				arts[ `${ key } ${ width }` ] = await page.evaluate( () => {
					const s = document.querySelector( '.b-answers' );
					const a = s.querySelector( '.section-art' );
					const head = s.querySelector( '.b-answers__head' ).getBoundingClientRect();
					const lead = s.querySelector( '.section-lead' ).getBoundingClientRect();
					const inner = s.querySelector( '.b-answers__inner' ).getBoundingClientRect();
					const r = a && 'none' !== getComputedStyle( a ).display ? a.getBoundingClientRect() : null;
					return [
						a ? a.dataset.art : null,
						!! s.querySelector( '.section-top' ),
						r ? Math.round( r.left - lead.right ) : Math.round( inner.width - lead.width ), // с рисунком — промежуток до него, без — 0
						r ? Math.round( inner.right - r.right ) : null, // рисунок у правого края
						r ? Math.abs( Math.round( ( r.top + r.bottom ) / 2 - ( head.top + head.bottom ) / 2 ) ) <= 1 : null, // по центру шапки
						r ? r.height <= head.height : null, // не выше шапки
						Math.round( lead.height / parseFloat( getComputedStyle( s.querySelector( '.section-lead' ) ).lineHeight ) ),
					];
				} );
			}
		}
		check( 'Длинный вводный текст: рисунок справа (48 px от текста, у правого края, по центру шапки, не выше её), текст — во всю оставшуюся ширину; уже 900 px — без рисунка, текст во всю ширину',
			[ 'default 1280', 'default 1000' ].every( ( k ) => 'expert-answers' === arts[ k ][ 0 ] && arts[ k ][ 1 ] && 48 === arts[ k ][ 2 ] && 0 === arts[ k ][ 3 ] && arts[ k ][ 4 ] && arts[ k ][ 5 ] && arts[ k ][ 6 ] >= 2 ) &&
			0 === arts[ 'default 768' ][ 2 ], JSON.stringify( arts ) );
		check( 'Выбор рисунка в блоке: «Анализирует данные от ИИ», «Думает над алгоритмами поисковиков», «Без картинки» — без рисунка, текст во всю ширину',
			'ai-data' === arts[ 'ai-data 1280' ][ 0 ] && 48 === arts[ 'ai-data 1280' ][ 2 ] && 'search-algorithms' === arts[ 'search-algorithms 1280' ][ 0 ] && 48 === arts[ 'search-algorithms 1280' ][ 2 ] &&
			null === arts[ 'none 1280' ][ 0 ] && false === arts[ 'none 1280' ][ 1 ] && 0 === arts[ 'none 1280' ][ 2 ], JSON.stringify( [ arts[ 'ai-data 1280' ], arts[ 'search-algorithms 1280' ], arts[ 'none 1280' ] ] ) );

		// ===== Пагинация: 42 ответа по 6 — 7 страниц =====
		const extra = JSON.parse( wp( 'eval', `$ids = array(); for ( $i = 1; $i <= 22; $i++ ) { $ids[] = wp_insert_post( array( 'post_type' => 'question', 'post_status' => 'publish', 'post_title' => '${ MARK } ' . $i, 'post_content' => '<!-- wp:paragraph --><p>Ответ ' . $i . '.</p><!-- /wp:paragraph -->', 'post_date' => sprintf( '2025-01-%02d 10:00:00', $i ) ) ); } echo wp_json_encode( $ids );` ) );
		created.push( ...extra );
		const listPage = wp( 'eval', `$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => '${ MARK } по 6', 'post_content' => wp_slash( ${ block( 'answers-list', "array( 'title' => 'Ответы', 'per_page' => 6 )" ) } ) ) ); update_post_meta( $id, '_wp_page_template', 'templates/questions.php' ); echo $id;` );
		created.push( listPage );
		const listUrl = wp( 'eval', `echo get_permalink( ${ listPage } );` );
		await page.setViewportSize( { width: 1280, height: 900 } );
		await page.goto( listUrl, { waitUntil: 'networkidle' } );
		const p1 = await pagesState( page );
		const navLabel = await page.locator( '.pager' ).getAttribute( 'aria-label' );
		check( 'Страница 1 из 7: «‹ Назад» (неактивна) 1 2 3 4 … 7 «Вперёд ›»; навигация «Страницы ответов»; ссылки /page/N/#answers',
			'‹Назад- 1* 2 3 4 … 7 ›Вперёд' === p1.all && p1.all === p1.visible && 'Страницы ответов' === navLabel && p1.hrefs.at( -1 ).endsWith( '/page/2/#answers' ) && p1.hrefs[ 0 ].endsWith( '#answers' ) && ! /\/page\/1\//.test( p1.hrefs[ 0 ] ) && p1.hrefs.includes( p1.hrefs.find( ( h ) => h.endsWith( '/page/7/#answers' ) ) ),
			JSON.stringify( [ p1, navLabel ] ) );
		const narrow = {};
		for ( const width of [ 640, 600, 390, 340, 320 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			narrow[ width ] = ( await pagesState( page ) ).visible;
		}
		check( 'Уже 640 px — без 4, уже 600 — без 3 и подписей «Назад» / «Вперёд», уже 340 — «‹ 1 … 7 ›» (как в макете)',
			JSON.stringify( narrow ) === JSON.stringify( { 640: '‹Назад- 1* 2 3 … 7 ›Вперёд', 600: '‹- 1* 2 … 7 ›', 390: '‹- 1* 2 … 7 ›', 340: '‹- 1* … 7 ›', 320: '‹- 1* … 7 ›' } ), JSON.stringify( narrow ) );
		await page.setViewportSize( { width: 1280, height: 900 } );
		const first = await page.evaluate( () => [ ...document.querySelectorAll( '.b-answers__card h3' ) ].map( ( h ) => h.textContent ) );
		await page.locator( '.pager__arrow.is-next' ).click();
		await page.waitForLoadState( 'networkidle' );
		const second = await page.evaluate( () => [ location.pathname, location.hash, [ ...document.querySelectorAll( '.b-answers__card h3' ) ].map( ( h ) => h.textContent ), Math.round( document.querySelector( '.b-answers' ).getBoundingClientRect().top ) ] );
		check( '«Вперёд» — страница 2 (/page/2/#answers, список сразу на экране), следующие 6 ответов без повторов',
			second[ 0 ].endsWith( '/page/2/' ) && '#answers' === second[ 1 ] && 6 === second[ 2 ].length && 6 === first.length && ! second[ 2 ].some( ( t ) => first.includes( t ) ) && second[ 3 ] < 200, JSON.stringify( second ) );
		await page.goto( `${ listUrl }page/4/`, { waitUntil: 'networkidle' } );
		const p4 = { 1280: ( await pagesState( page ) ).visible };
		for ( const width of [ 600, 340 ] ) {
			await page.setViewportSize( { width, height: 900 } );
			p4[ width ] = ( await pagesState( page ) ).visible;
		}
		await page.setViewportSize( { width: 1280, height: 900 } );
		check( 'Страница 4 из 7: все номера 1–7; уже 600 px — «1 … 3 4 5 … 7», уже 340 — «1 … 4 … 7»; «‹ Назад» — ссылка',
			JSON.stringify( p4 ) === JSON.stringify( { 1280: '‹Назад 1 2 3 4* 5 6 7 ›Вперёд', 600: '‹ 1 … 3 4* 5 … 7 ›', 340: '‹ 1 … 4* … 7 ›' } ), JSON.stringify( p4 ) );
		await page.goto( `${ listUrl }page/7/`, { waitUntil: 'networkidle' } );
		const p7 = await pagesState( page );
		const last = await page.locator( '.b-answers__card' ).count();
		check( 'Последняя страница: 6 ответов, «Вперёд ›» неактивна, «… 4 5 6 7»', '‹Назад 1 … 4 5 6 7* ›Вперёд-' === p7.all && 6 === last, JSON.stringify( [ p7.all, last ] ) );
		check( 'Страница 8 из 7 — 404', 404 === ( await page.request.get( `${ listUrl }page/8/` ) ).status() );

		// ===== «Как бы поступил я» =====
		await page.goto( ANSWER, { waitUntil: 'networkidle' } );
		const how = await page.evaluate( () => {
			const s = document.querySelector( '.b-how' );
			const t = s.querySelector( '.b-how__title' );
			const li = s.querySelector( 'li' );
			return {
				tag: t.tagName,
				title: t.textContent,
				labelled: s.getAttribute( 'aria-labelledby' ) === t.id,
				items: [ s.querySelector( '.b-how__body > ol' ) && s.querySelectorAll( 'li' ).length, li.textContent ],
				check: /svg/.test( getComputedStyle( li, '::before' ).backgroundImage ),
				note: s.querySelector( '.b-how__note' ).textContent,
				cta: [ s.querySelector( '.b-how__cta-text' ).textContent, s.querySelector( '.b-how__cta' ).getAttribute( 'href' ).replace( /^https?:\/\/[^/]+/, '' ), s.querySelector( '.b-how__cta-arrow' ).getAttribute( 'aria-hidden' ) ],
				bg: getComputedStyle( s ).backgroundColor,
			};
		} );
		check( '«Как бы поступил я» в ответе: H2, 4 пункта-галочки (вложенный нумерованный список), текст и кнопка «Заказать технический аудит» → /seo/audit/, фиолетовая карточка',
			'H2' === how.tag && 'Как бы поступил я' === how.title && how.labelled && 4 === how.items[ 0 ] && how.items[ 1 ].startsWith( 'Нашёл бы новые карточки' ) && how.check &&
			how.note.startsWith( 'Хотите проверить весь сайт' ) && JSON.stringify( how.cta ) === JSON.stringify( [ 'Заказать технический аудит', '/seo/audit/', 'true' ] ) && 'rgb(91, 33, 182)' === how.bg, JSON.stringify( how ) );
		const inner = "'<!-- wp:paragraph --><p>Только абзац.</p><!-- /wp:paragraph -->'";
		const q = wp( 'eval', `$id = wp_insert_post( array( 'post_type' => 'question', 'post_status' => 'publish', 'post_title' => '${ MARK } итог', 'post_date' => '2024-01-01 10:00:00', 'post_content' => wp_slash( ${ block( 'how-i-would', "array( 'title' => 'Мой план', 'level' => 'h3' )", inner ) } ) ) ); echo $id;` );
		created.push( q );
		await page.goto( `${ URL }/?p=${ q }`, { waitUntil: 'networkidle' } );
		const own = await page.evaluate( () => {
			const s = document.querySelector( '.b-how' );
			return [ s.querySelector( '.b-how__title' ).tagName, s.querySelector( '.b-how__title' ).textContent, s.querySelector( '.b-how__body' ).textContent.trim(), !! s.querySelector( '.b-how__foot' ) ];
		} );
		check( 'Свой заголовок H3, только абзац; без текста и кнопки — без нижней строки', JSON.stringify( own ) === JSON.stringify( [ 'H3', 'Мой план', 'Только абзац.', false ] ), JSON.stringify( own ) );

		// ===== Сверка с макетами =====
		for ( const width of [ 1280, 768, 390 ] ) {
			await compare( page, {
				name: 'list', width, url: PAGE, theme: '.b-answers', mockup: 'seo-vopros/vopros-seo.html', mock: '#answers',
				// Пагинация сверяется отдельно ниже (в макете — 7 страниц, у теста здесь свои ответы).
				// Рисунок справа от шапки — правка заказчика, в макете его нет.
				before: ( p ) => p.addStyleTag( { content: '.pager, .b-answers .section-art { display: none !important; }' } ),
				mockBefore: ( p ) => p.addStyleTag( { content: '#answers nav.dc-pg { display: none !important; }' } ),
			} );
			const w = await ( async () => {
				await page.setViewportSize( { width, height: 900 } );
				await page.goto( mockupUrl( 'seo-vopros/otvet.html' ), { waitUntil: 'networkidle' } );
				return page.evaluate( () => document.querySelector( '.dc-summary' ).getBoundingClientRect().width );
			} )();
			await compare( page, {
				name: 'how', width, url: ANSWER, theme: '.b-how', mockup: 'seo-vopros/otvet.html', mock: '.dc-summary',
				// Ширина — как у колонки ответа в макете (шаблон ответа — шаг 5.7).
				before: ( p ) => p.addStyleTag( { content: `.b-how { width: ${ w }px; box-sizing: border-box; }` } ),
			} );
		}
		for ( const width of [ 1280, 390 ] ) {
			await compare( page, { name: 'pages', width, url: listUrl, theme: '.pager', mockup: 'seo-vopros/vopros-seo.html', mock: 'nav.dc-pg' } );
		}

		// ===== Ширины, доступность =====
		const wide = [];
		for ( const url of [ PAGE, `${ listUrl }page/4/`, ANSWER ] ) {
			for ( const width of [ 1440, 1024, 768, 390, 320, 280 ] ) {
				await page.setViewportSize( { width, height: 900 } );
				await page.goto( url, { waitUntil: 'domcontentloaded' } );
				if ( await page.evaluate( () => document.documentElement.scrollWidth > innerWidth ) ) {
					wide.push( `${ url } ${ width }` );
				}
			}
		}
		check( 'Ширины 1440–280: без прокрутки вбок (список, пагинация, ответ)', 0 === wide.length, wide.join( ', ' ) );
		await page.setViewportSize( { width: 1280, height: 900 } );
		const axe = [];
		for ( const [ url, sel ] of [ [ `${ listUrl }page/4/`, '.b-answers' ], [ ANSWER, '.b-how' ] ] ) {
			await page.goto( url, { waitUntil: 'networkidle' } );
			await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
			axe.push( ...await page.evaluate( async ( s ) => ( await window.axe.run( s, { resultTypes: [ 'violations' ] } ) ).violations.filter( ( x ) => [ 'critical', 'serious' ].includes( x.impact ) ).map( ( x ) => `${ x.id } (${ x.nodes.length })` ), sel ) );
		}
		check( 'axe: нет нарушений critical/serious', 0 === axe.length, axe.join( ', ' ) );
		check( 'Консоль браузера на сайте без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1400, height: 1000 } } );
		const editorErrors = collectConsoleErrors( editor );
		await login( editor );
		const qid = wp( 'eval', 'echo get_page_by_path( "pochemu-yandeks-ne-indeksiruet-novye-stranicy-kataloga", OBJECT, "question" )->ID;' );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ qid }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ) && wp.data.select( 'core/block-editor' ).getBlocks().length, null, { timeout: 60000 } );
		const qEd = await editor.evaluate( () => {
			const allowed = wp.data.select( 'core/block-editor' ).getSettings().allowedBlockTypes;
			const how = wp.data.select( 'core/block-editor' ).getBlocks().find( ( b ) => 'trust/how-i-would' === b.name );
			const type = wp.blocks.getBlockType( 'trust/how-i-would' );
			return [ type.title, type.category, !! type.example, allowed.includes( 'trust/how-i-would' ), how && how.innerBlocks.map( ( b ) => b.name + ':' + b.innerBlocks.length ).join() ];
		} );
		check( 'Редактор ответа: «Как бы поступил я» доступен, пункты — вложенный список (4 пункта), с превью', JSON.stringify( qEd ) === JSON.stringify( [ 'Как бы поступил я', 'trust-content', true, true, 'core/list:4' ] ), JSON.stringify( qEd ) );
		const canvas = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas.locator( '.b-how' ).first().waitFor( { timeout: 60000 } );
		check( 'Редактор: превью карточки с пунктами', 4 === await canvas.locator( '.b-how li' ).count() );
		const pageId = wp( 'eval', 'echo get_page_by_path( "seo/voprosy" )->ID;' );
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ pageId }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ) && wp.data.select( 'core/block-editor' ).getBlocks().length, null, { timeout: 60000 } );
		await editor.waitForTimeout( 1500 );
		const pEd = await editor.evaluate( () => {
			const allowed = wp.data.select( 'core/block-editor' ).getSettings().allowedBlockTypes;
			const type = wp.blocks.getBlockType( 'trust/answers-list' );
			return [ type.title, type.category, !! type.example, type.supports.multiple, allowed.includes( 'trust/answers-list' ), allowed.includes( 'trust/how-i-would' ) ];
		} );
		check( 'Редактор страницы «Вопросы SEO»: «Ответы SEO-специалистов» в «Списках», с превью, один на странице; «Как бы поступил я» на страницах недоступен',
			JSON.stringify( pEd ) === JSON.stringify( [ 'Ответы SEO-специалистов', 'trust-lists', true, false, true, false ] ), JSON.stringify( pEd ) );
		const canvas2 = editor.frameLocator( 'iframe[name="editor-canvas"]' );
		await canvas2.locator( '.b-answers' ).first().waitFor( { timeout: 60000 } );
		check( 'Редактор: превью списка — 4 карточки, без пагинации', 4 === await canvas2.locator( '.b-answers__card' ).count() && 0 === await canvas2.locator( '.pager' ).count() );
		const artField = JSON.parse( wp( 'eval', '$f = acf_get_field( "field_trust_block_answers_list_head_art" ); echo wp_json_encode( array( $f["type"], array_keys( $f["choices"] ), $f["default_value"], $f["wrapper"]["class"] ) );' ) );
		const artPreviews = await editor.evaluate( () => [ 'expert-answers', 'search-algorithms', 'ai-data' ].map( ( k ) => !! ( window.trustArts && window.trustArts[ k ] && window.trustArts[ k ].startsWith( '<svg' ) ) ) );
		check( 'Редактор: поле «Картинка справа от заголовка» — список с превью рисунков (по умолчанию «Эксперт отвечает на вопросы», можно без картинки)',
			JSON.stringify( artField ) === JSON.stringify( [ 'select', [ 'expert-answers', 'search-algorithms', 'ai-data', 'none' ], 'expert-answers', 'trust-icon-select trust-art-select' ] ) && artPreviews.every( Boolean ), JSON.stringify( [ artField, artPreviews ] ) );
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
