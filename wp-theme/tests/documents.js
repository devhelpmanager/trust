/**
 * Тест шага 4.15 — тип страницы «Документ»: шаблон (содержание сбоку и на телефоне, подсветка
 * раздела, нумерация разделов и пунктов, таблицы-карточки, печать) и блоки «Коротко о главном»,
 * «Список определений», «Карточки с плашкой», «Пример галочки согласия», «Настройки cookie»,
 * «Другие документы», «Реквизиты» в тексте документа.
 *
 * Запуск: node wp-theme/tests/documents.js
 *
 * Демо (demo/documents.php — тексты из макетов o-nas/oferta.html, politika-cookie.html,
 * soglasie-pd.html; политика конфиденциальности и согласие на распространение — заглушки).
 * Сверка блоков с макетами на 1280 / 768 / 390 px (содержание — на 1280), ширины 1440–280,
 * axe-core, консоль, редактор (блоки, стили, группа полей «Документ»).
 */

const path = require( 'path' );
const { launch, URL, OUT, check, finish, clearLog, checkLog, collectConsoleErrors, routeMockupFonts, mockupUrl, comparePng, settle, isolate, login, wp } = require( './lib' );

const MARK = 'ТестДокумент';
const DOCS = {
	oferta: { url: `${ URL }/oferta/`, mockup: 'o-nas/oferta.html' },
	cookie: { url: `${ URL }/politika-cookie/`, mockup: 'o-nas/politika-cookie.html' },
	consent: { url: `${ URL }/soglasie-na-obrabotku-dannyh/`, mockup: 'o-nas/soglasie-pd.html' },
};

/**
 * Снимки блока темы и элемента макета и их сравнение.
 *
 * @param {import('playwright').Page} page  Страница.
 * @param {string}                    doc   Документ (ключ DOCS).
 * @param {string}                    name  Имя снимка.
 * @param {number}                    width Ширина окна.
 * @param {string}                    theme Элемент в теме.
 * @param {string}                    mock  Элемент в макете.
 */
async function compare( page, doc, name, width, theme, mock ) {
	const base = path.join( OUT, `documents-${ doc }-${ name }-${ width }` );
	await page.setViewportSize( { width, height: 900 } );
	for ( const [ url, selector, file ] of [ [ DOCS[ doc ].url, theme, 'theme' ], [ mockupUrl( DOCS[ doc ].mockup ), mock, 'mockup' ] ] ) {
		await page.goto( url, { waitUntil: 'networkidle' } );
		await settle( page );
		await page.mouse.move( 0, 0 );
		await isolate( page, selector );
		await page.waitForTimeout( 300 );
		await page.locator( selector ).first().screenshot( { path: `${ base }-${ file }.png` } );
	}
	const diff = comparePng( `${ base }-mockup.png`, `${ base }-theme.png`, `${ base }-diff.png` );
	check( `Макет ${ DOCS[ doc ].mockup }, ${ name }, ${ width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
}

/**
 * Сравнение участка страницы — от верха элемента first до низа элемента last (раздел документа:
 * заголовок и пункты; в теме у раздела нет общей обёртки) — с тем же участком макета.
 *
 * @param {import('playwright').Page} page  Страница.
 * @param {string}                    doc   Документ (ключ DOCS).
 * @param {string}                    name  Имя снимка.
 * @param {number}                    width Ширина окна.
 * @param {string[]}                  theme Первый и последний элементы в теме.
 * @param {string[]}                  mock  Первый и последний элементы в макете.
 */
async function compareRange( page, doc, name, width, theme, mock ) {
	const base = path.join( OUT, `documents-${ doc }-${ name }-${ width }` );
	await page.setViewportSize( { width, height: 2000 } ); // участок целиком в окне
	for ( const [ url, [ first, last ], file ] of [ [ DOCS[ doc ].url, theme, 'theme' ], [ mockupUrl( DOCS[ doc ].mockup ), mock, 'mockup' ] ] ) {
		await page.goto( url, { waitUntil: 'networkidle' } );
		await settle( page );
		await page.mouse.move( 0, 0 );
		{
			// Как isolate(), но без перестановки, а в теме разделы перед участком не убираются, а
			// сжимаются до нуля: CSS-счётчики номеров считают и их (у display: none счётчик не растёт).
			await page.evaluate( ( [ sel, collapse ] ) => {
				const start = collapse ? document.querySelector( sel ) : document.querySelector( sel ).closest( 'section' );
				let prev = start.previousElementSibling;
				while ( prev ) {
					if ( collapse ) {
						[ 'height', 'margin', 'padding', 'border-width' ].forEach( ( prop ) => prev.style.setProperty( prop, '0', 'important' ) );
						prev.style.setProperty( 'overflow', 'hidden', 'important' );
					} else {
						prev.style.setProperty( 'display', 'none', 'important' );
					}
					prev = prev.previousElementSibling;
				}
				start.style.setProperty( 'margin-top', '0', 'important' );
				let el = start.parentElement;
				while ( el && el !== document.body ) {
					for ( let p = el.previousElementSibling; p; p = p.previousElementSibling ) {
						p.style.setProperty( 'display', 'none', 'important' );
					}
					el = el.parentElement;
				}
				window.scrollTo( 0, 0 );
			}, [ first, 'theme' === file ] );
		}
		await page.waitForTimeout( 300 );
		const clip = await page.evaluate( ( [ a, b ] ) => {
			const top = document.querySelector( a ).getBoundingClientRect();
			const bottom = document.querySelector( b ).getBoundingClientRect();
			return { x: top.left, y: top.top, width: Math.max( top.right, bottom.right ) - top.left, height: bottom.bottom - top.top };
		}, [ first, last ] );
		await page.screenshot( { path: `${ base }-${ file }.png`, clip } );
	}
	const diff = comparePng( `${ base }-mockup.png`, `${ base }-theme.png`, `${ base }-diff.png` );
	check( `Макет ${ DOCS[ doc ].mockup }, ${ name }, ${ width }px: совпадение (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
}

/**
 * Опубликованная тестовая страница типа «Документ».
 *
 * @param {string} title   Заголовок.
 * @param {string} content Разметка блоков.
 * @param {Object} meta    Метаполя.
 * @return {string} ID.
 */
function createDoc( title, content, meta = {} ) {
	const id = wp( 'post', 'create', '--post_type=page', `--post_title=${ MARK } ${ title }`, '--post_status=publish', `--post_content=${ content }`, '--porcelain' );
	wp( 'post', 'meta', 'update', id, '_wp_page_template', 'templates/document.php' );
	Object.entries( meta ).forEach( ( [ key, value ] ) => wp( 'post', 'meta', 'update', id, key, String( value ) ) );
	return id;
}

const h2 = ( text, anchor = '' ) => `<!-- wp:heading${ anchor ? ` {"anchor":"${ anchor }"}` : '' } -->\n<h2 class="wp-block-heading"${ anchor ? ` id="${ anchor }"` : '' }>${ text }</h2>\n<!-- /wp:heading -->`;
const clauses = ( items ) => `<!-- wp:list {"ordered":true,"className":"is-style-clauses"} -->\n<ol class="wp-block-list is-style-clauses">${ items.map( ( i ) => `<!-- wp:list-item -->\n<li>${ i }</li>\n<!-- /wp:list-item -->` ).join( '\n\n' ) }</ol>\n<!-- /wp:list -->`;

( async () => {
	clearLog();
	const browser = await launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 900 } } );
	await routeMockupFonts( context );
	const page = await context.newPage();
	const errors = collectConsoleErrors( page );
	const created = [];

	// Остатки прерванного прогона.
	wp( 'eval', `foreach ( get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 's' => '${ MARK }', 'fields' => 'ids' ) ) as $id ) { wp_delete_post( $id, true ); }` );

	try {
		// ===== Оферта: шаблон, содержание, нумерация =====
		await page.goto( DOCS.oferta.url, { waitUntil: 'networkidle' } );
		const offer = await page.evaluate( () => {
			const side = document.querySelector( '.doc-toc--side' );
			const links = [ ...side.querySelectorAll( '[data-doc-toc-link]' ) ];
			const heads = [ ...document.querySelectorAll( '.doc-content > h2' ) ];
			const before = ( el ) => getComputedStyle( el, '::before' ).content;
			const lists = [ ...document.querySelectorAll( '.doc-content > ol.is-style-clauses' ) ];
			const style = ( el ) => getComputedStyle( el );
			return {
				order: [ ...document.querySelectorAll( '.b-hero-document, .doc-toc--side, .b-summary, .doc-content > h2, .b-defs, .b-req--doc, .b-docs' ) ].map( ( el ) => ( el.matches( 'h2' ) ? 'H2' : el.classList[ 0 ] ) ).filter( ( v, i, a ) => v !== a[ i - 1 ] ).join( '|' ),
				toc: links.map( ( a ) => a.textContent.replace( /\s+/g, ' ' ).trim() ),
				ids: links.every( ( a, i ) => heads[ i ] && a.hash === '#' + heads[ i ].id && /^[a-z0-9-]+$/.test( heads[ i ].id ) ),
				// Значения счётчиков браузер не отдаёт — проверяются правила; сами номера — сверкой с макетом.
				h2: [ before( heads[ 0 ] ), style( heads[ 0 ] ).counterIncrement, style( heads[ 0 ] ).counterReset ],
				points: [ before( lists[ 0 ].children[ 0 ] ), style( lists[ 0 ].children[ 0 ] ).counterIncrement, style( lists[ 0 ] ).counterReset ],
				current: links.findIndex( ( a ) => a.getAttribute( 'aria-current' ) ),
				sideShown: 'none' !== getComputedStyle( side ).display,
				dropShown: 'none' !== getComputedStyle( document.querySelector( '.doc-toc--drop' ) ).display,
				summaryInToc: links.some( ( a ) => /Коротко|Другие/.test( a.textContent ) ),
			};
		} );
		check( 'Оферта: первый экран, содержание сбоку, «Коротко о главном», разделы (H2), термины, реквизиты, «Другие документы»',
			'b-hero-document|doc-toc|b-summary|H2|b-defs|H2|b-req|b-docs' === offer.order, offer.order );
		check( 'Содержание: 11 разделов с номерами, как в макете; «Коротко о главном» и «Другие документы» в него не входят',
			11 === offer.toc.length && '1 Общие положения' === offer.toc[ 0 ] && '11 Реквизиты исполнителя' === offer.toc[ 10 ] && ! offer.summaryInToc, JSON.stringify( offer.toc ) );
		check( 'Якоря разделов латиницей из заголовков, ссылки содержания ведут на них', offer.ids );
		check( 'Номера разделов и пунктов ставятся сами: H2 — счётчик раздела, пункт — «раздел.пункт.», счёт пунктов начинается заново у каждого H2 (а не у списка)',
			JSON.stringify( offer.h2 ) === JSON.stringify( [ 'counter(doc-section) ". "', 'doc-section 1', 'doc-point 0' ] ) && JSON.stringify( offer.points ) === JSON.stringify( [ 'counter(doc-section) "." counter(doc-point) "."', 'doc-point 1', 'none' ] ), JSON.stringify( [ offer.h2, offer.points ] ) );
		check( 'Компьютер: содержание сбоку, кнопки «Содержание» нет; подсвечен первый раздел', offer.sideShown && ! offer.dropShown && 0 === offer.current, JSON.stringify( offer ) );

		// Подсветка текущего раздела при прокрутке.
		await page.evaluate( () => document.querySelectorAll( '.doc-content > h2' )[ 4 ].scrollIntoView() );
		await page.waitForTimeout( 400 );
		const scrolled = await page.evaluate( () => {
			const side = [ ...document.querySelectorAll( '.doc-toc--side [data-doc-toc-link]' ) ];
			const drop = [ ...document.querySelectorAll( '.doc-toc--drop [data-doc-toc-link]' ) ];
			const box = document.querySelector( '.doc-toc--side' ).getBoundingClientRect();
			return [ side.findIndex( ( a ) => 'location' === a.getAttribute( 'aria-current' ) ), drop.findIndex( ( a ) => a.getAttribute( 'aria-current' ) ), box.top >= 0 && box.top < 200 ];
		} );
		check( 'Прокрутка до раздела 5: в содержании подсвечен он (aria-current="location"), содержание едет вместе со страницей',
			JSON.stringify( scrolled ) === JSON.stringify( [ 4, 4, true ] ), JSON.stringify( scrolled ) );

		const parts = await page.evaluate( () => {
			const sum = document.querySelector( '.b-summary' );
			const defs = document.querySelector( '.b-defs' );
			const req = document.querySelector( '.b-req--doc' );
			return {
				summary: [ sum.tagName, sum.querySelector( 'h2' ).textContent, sum.querySelectorAll( 'li' ).length, sum.querySelectorAll( 'li svg' ).length, sum.querySelector( '.b-summary__note' ).textContent ],
				defs: [ defs.classList.contains( 'b-defs--lines' ), defs.querySelectorAll( 'dt' ).length, defs.querySelector( 'dt' ).textContent, getComputedStyle( defs.querySelector( 'dt' ) ).fontWeight ],
				req: [ req.querySelectorAll( '.b-req__row' ).length, req.querySelector( 'dt' ).textContent, !! req.querySelector( '.b-req__copy' ), new URL( req.querySelector( '.b-req__more a' ).href ).pathname ],
				docs: [ ...document.querySelectorAll( '.b-docs__card' ) ].map( ( a ) => {
					const svg = a.querySelector( '.b-docs__icon svg' ).innerHTML;
					const icon = [ [ 'M9 14l2 2 4-4', 'document-check' ], [ 'M8.5 9.5h.01', 'cookie-dots' ], [ 'M12 3l7 3v5', 'shield-outline' ] ].find( ( [ sign ] ) => svg.includes( sign ) );
					return a.querySelector( '.b-docs__name' ).textContent + ':' + ( icon ? icon[ 1 ] : '?' );
				} ),
			};
		} );
		check( '«Коротко о главном»: <aside> с H2, 5 пунктов с галочками, примечание',
			'ASIDE' === parts.summary[ 0 ] && 'Коротко о главном' === parts.summary[ 1 ] && 5 === parts.summary[ 2 ] && 5 === parts.summary[ 3 ] && parts.summary[ 4 ].startsWith( 'Краткое изложение' ), JSON.stringify( parts.summary ) );
		check( '«Список определений», вид «Линии»: 6 терминов, название жирным', parts.defs[ 0 ] && 6 === parts.defs[ 1 ] && 'Заказчик' === parts.defs[ 2 ] && '600' === parts.defs[ 3 ], JSON.stringify( parts.defs ) );
		check( '«Реквизиты» в тексте документа: строки из «Настроек → Реквизиты», без кнопок, ссылка на «Контакты»',
			10 === parts.req[ 0 ] && 'Полное наименование' === parts.req[ 1 ] && ! parts.req[ 2 ] && '/kontakty/' === parts.req[ 3 ], JSON.stringify( parts.req ) );
		check( '«Другие документы» вручную (как в макете оферты): политика конфиденциальности, согласие, политика cookie — со своими значками',
			'Политика конфиденциальности:shield-outline|Согласие на обработку персональных данных:document-check|Политика cookie:cookie-dots' === parts.docs.join( '|' ), JSON.stringify( parts.docs ) );

		// Телефон: «Содержание» кнопкой, после выбора раздела сворачивается.
		await page.setViewportSize( { width: 390, height: 800 } );
		await page.goto( DOCS.oferta.url, { waitUntil: 'networkidle' } );
		const drop = page.locator( '.doc-toc--drop' );
		const phone1 = await page.evaluate( () => [ getComputedStyle( document.querySelector( '.doc-toc--side' ) ).display, document.querySelector( '.doc-toc--drop' ).open ] );
		await drop.locator( 'summary' ).click();
		const opened = await drop.evaluate( ( d ) => d.open );
		await drop.locator( '[data-doc-toc-link]' ).nth( 6 ).click();
		await page.waitForTimeout( 800 );
		const phone2 = await page.evaluate( () => {
			const head = document.querySelectorAll( '.doc-content > h2' )[ 6 ];
			const top = head.getBoundingClientRect().top;
			return [ document.querySelector( '.doc-toc--drop' ).open, top > 0 && top < 260, location.hash === '#' + head.id ];
		} );
		check( 'Телефон: содержание — кнопкой «Содержание»; раскрывается, по разделу — переход к нему, список сворачивается',
			'none' === phone1[ 0 ] && false === phone1[ 1 ] && opened && JSON.stringify( phone2 ) === JSON.stringify( [ false, true, true ] ), JSON.stringify( [ phone1, opened, phone2 ] ) );
		await page.setViewportSize( { width: 1280, height: 900 } );

		// ===== Политика cookie: карточки, таблица, настройки cookie =====
		await page.goto( DOCS.cookie.url, { waitUntil: 'networkidle' } );
		const ck = await page.evaluate( () => {
			const table = document.querySelector( '.doc-content .wp-block-table table' );
			const groups = [ ...table.querySelectorAll( 'tr.doc-table__group th' ) ];
			return {
				cards: [ ...document.querySelectorAll( '.b-doc-cards__label' ) ].map( ( l ) => l.textContent ),
				groups: groups.map( ( th ) => th.textContent + ':' + th.getAttribute( 'colspan' ) + ':' + th.getAttribute( 'scope' ) ),
				labels: [ ...table.querySelectorAll( 'tbody tr:not(.doc-table__group) ' ) ][ 0 ].querySelectorAll( 'td' ).length + ':' + [ ...table.querySelectorAll( 'tbody td' ) ].slice( 0, 4 ).map( ( td ) => td.dataset.label ).join( ',' ),
				code: table.querySelector( 'code' ).textContent,
				note: getComputedStyle( document.querySelector( '.is-style-doc-note' ) ).fontSize,
				thead: getComputedStyle( table.querySelector( 'thead' ) ).display,
			};
		} );
		check( '«Карточки с плашкой»: Необходимые, Аналитические, Рекламные', 'Необходимые|Аналитические|Рекламные' === ck.cards.join( '|' ), JSON.stringify( ck.cards ) );
		check( 'Таблица: строки с текстом только в первой ячейке — подзаголовки групп на всю ширину; у ячеек data-label из шапки',
			'Необходимые:4:colgroup|Аналитические:4:colgroup|Рекламные:4:colgroup' === ck.groups.join( '|' ) && '4:Название,Сервис,Зачем нужен,Срок' === ck.labels && 'trust_consent' === ck.code, JSON.stringify( ck ) );
		check( 'Компьютер: таблица таблицей; абзац «Примечание» — мелкий', 'table-header-group' === ck.thead && '14px' === ck.note, JSON.stringify( [ ck.thead, ck.note ] ) );

		// Ширина колонки: у планшета рядом содержание — таблица уже карточки (container query).
		const cardsAt = async ( width ) => {
			await page.setViewportSize( { width, height: 900 } );
			return page.evaluate( () => {
				const table = document.querySelector( '.doc-content .wp-block-table' );
				const td = table.querySelector( 'tbody tr:not(.doc-table__group) td:nth-child(3)' );
				return [ getComputedStyle( table.querySelector( 'thead' ) ).display, getComputedStyle( td, '::before' ).content ];
			} );
		};
		const at1280 = await cardsAt( 1280 );
		const at1024 = await cardsAt( 1024 );
		const at390 = await cardsAt( 390 );
		await page.setViewportSize( { width: 1280, height: 900 } );
		check( 'Таблица — карточки там, где колонке текста меньше 640 px: 1024 (рядом содержание) и 390; подпись — название колонки',
			'none' !== at1280[ 0 ] && 'none' === at1024[ 0 ] && 'none' === at390[ 0 ] && '"Зачем нужен"' === at390[ 1 ], JSON.stringify( [ at1280, at1024, at390 ] ) );

		// Настройки cookie (выбор браузера теста — «только обязательные»).
		const set = page.locator( '#cookie-settings' );
		const sw = ( key ) => set.locator( `[data-cookie-switch="${ key }"]` );
		const consent = async () => {
			const raw = ( await context.cookies() ).find( ( c ) => 'trust_consent' === c.name );
			const v = raw ? JSON.parse( decodeURIComponent( raw.value ) ) : {};
			return [ v.analytics, v.marketing, v.choice ].join( ':' );
		};
		const st0 = [ await sw( 'analytics' ).getAttribute( 'aria-checked' ), await sw( 'marketing' ).getAttribute( 'aria-checked' ), await set.locator( '[data-cookie-status]' ).textContent() ];
		check( '«Настройки cookie»: переключатели (role="switch") показывают сохранённый выбор, строка состояния',
			'false|false|Сейчас: аналитические — выключены, рекламные — выключены. Нажмите «Сохранить выбор», чтобы применить.' === st0.join( '|' ), JSON.stringify( st0 ) );
		await sw( 'analytics' ).click();
		const st1 = [ await sw( 'analytics' ).getAttribute( 'aria-checked' ), await consent() ];
		await set.locator( '[data-cookie-save="custom"]' ).click();
		const st2 = [ await set.locator( '[data-cookie-status]' ).textContent(), await consent() ];
		check( 'Переключение — только вид; «Сохранить выбор» сохраняет выбор (тот же cookie, что у плашки)',
			'true' === st1[ 0 ] && 'false:false:necessary' === st1[ 1 ] && 'Выбор сохранён: аналитические — включены, рекламные — выключены.' === st2[ 0 ] && 'true:false:custom' === st2[ 1 ], JSON.stringify( [ st1, st2 ] ) );
		await set.locator( '[data-cookie-save="all"]' ).click();
		const all = [ await sw( 'marketing' ).getAttribute( 'aria-checked' ), await consent() ];
		await page.reload( { waitUntil: 'networkidle' } );
		await set.locator( '[data-cookie-save="necessary"]' ).click();
		const needed = [ await sw( 'analytics' ).getAttribute( 'aria-checked' ), await sw( 'marketing' ).getAttribute( 'aria-checked' ), await consent() ];
		check( '«Принять все» — оба включены; «Только необходимые» — оба выключены (после перезагрузки выбор на месте)',
			'true' === all[ 0 ] && 'true:true:all' === all[ 1 ] && JSON.stringify( needed ) === JSON.stringify( [ 'false', 'false', 'false:false:necessary' ] ), JSON.stringify( [ all, needed ] ) );

		// «Настроить cookie» первого экрана — к блоку, плашка не открывается.
		await page.goto( DOCS.cookie.url, { waitUntil: 'networkidle' } );
		await page.locator( '.b-hero-document a[href="#cookie-settings"]' ).click();
		await page.waitForTimeout( 800 );
		const toBlock = await page.evaluate( () => [ document.querySelector( '[data-trust-cookie-banner]' ).hidden, Math.round( document.getElementById( 'cookie-settings' ).getBoundingClientRect().top ) < 300 ] );
		check( '«Настроить cookie» на странице политики ведёт к блоку настроек, плашка не открывается', JSON.stringify( toBlock ) === JSON.stringify( [ true, true ] ), JSON.stringify( toBlock ) );

		// ===== Согласие: пример галочки рядом с «Коротко о главном», вступление, карточка =====
		await page.goto( DOCS.consent.url, { waitUntil: 'networkidle' } );
		const cs = await page.evaluate( () => {
			const sum = document.querySelector( '.b-summary' ).getBoundingClientRect();
			const ex = document.querySelector( '.b-consent-example' );
			const box = ex.getBoundingClientRect();
			const intro = document.querySelector( '.is-style-doc-intro' );
			return {
				pair: !! document.querySelector( '.doc-pair > .b-summary + .b-consent-example' ),
				side: Math.abs( sum.top - box.top ) < 1 && box.left > sum.right,
				hidden: ex.querySelector( '.b-consent-example__form' ).getAttribute( 'aria-hidden' ),
				caption: ex.querySelector( 'figcaption' ).textContent,
				intro: getComputedStyle( intro ).borderLeftWidth,
				card: [ document.querySelector( '.b-defs--card' ) && document.querySelector( '.b-defs--card dt' ).textContent ],
				rowHead: [ ...document.querySelectorAll( '.doc-content .wp-block-table tbody th[scope="row"]' ) ].length,
				docs: [ ...document.querySelectorAll( '.b-docs__card' ) ].map( ( a ) => a.textContent.replace( /\s+/g, ' ' ).trim() ),
			};
		} );
		check( '«Пример галочки согласия» — сразу после «Коротко о главном», на компьютере рядом; рисунок формы скрыт от скринридера',
			cs.pair && cs.side && 'true' === cs.hidden && 'Как выглядит согласие в форме' === cs.caption, JSON.stringify( cs ) );
		check( 'Абзац «Вступление» — с полосой слева; «Оператор» — «Список определений» карточкой; таблица — первая колонка заголовками строк',
			'4px' === cs.intro && 'Наименование' === cs.card[ 0 ] && 4 === cs.rowHead, JSON.stringify( cs ) );
		check( '«Другие документы» автоматически: все документы, кроме этого, по «Порядку»; короткое название и подпись — из группы «Документ»',
			'Политика конфиденциальности|Согласие на распространение данных для публикации отзывов и вопросов|Политика cookie|Публичная оферта' === cs.docs.join( '|' ), JSON.stringify( cs.docs ) );
		await page.setViewportSize( { width: 390, height: 900 } );
		const csPhone = await page.evaluate( () => document.querySelector( '.b-consent-example' ).getBoundingClientRect().top > document.querySelector( '.b-summary' ).getBoundingClientRect().bottom );
		await page.setViewportSize( { width: 1280, height: 900 } );
		check( 'Телефон: пример галочки — под «Коротко о главном»', csPhone );

		// ===== Свои данные: без нумерации, свой якорь, одинаковые заголовки, без первого экрана =====
		const own = createDoc( 'Без нумерации', [
			h2( 'Раздел', 'my-anchor' ), clauses( [ 'Пункт А', 'Пункт Б' ] ),
			h2( 'Раздел' ), clauses( [ 'Пункт В' ] ),
			h2( 'Раздел' ), clauses( [ 'Пункт Г' ] ),
		].join( '\n\n' ), { trust_doc_numbering: '0' } );
		created.push( own );
		await page.goto( `${ URL }/?page_id=${ own }`, { waitUntil: 'networkidle' } );
		const plain = await page.evaluate( () => ( {
			h1: document.querySelector( '.page-default__title' ) && document.querySelector( '.page-default__title' ).textContent,
			ids: [ ...document.querySelectorAll( '.doc-content > h2' ) ].map( ( h ) => h.id ),
			h2: getComputedStyle( document.querySelector( '.doc-content > h2' ), '::before' ).content,
			points: [ getComputedStyle( document.querySelector( '.doc-content li' ), '::before' ).content, getComputedStyle( document.querySelector( '.doc-content ol' ) ).counterReset ],
			nums: document.querySelectorAll( '.doc-toc__num' ).length,
		} ) );
		check( 'Без первого экрана — крошки и H1; нумерация выключена: без номеров разделов, пункты «1.», «2.» в каждом списке; свой якорь сохраняется, одинаковые — с -2, -3',
			/Без нумерации/.test( plain.h1 ) && 'my-anchor|razdel|razdel-2' === plain.ids.join( '|' ) && 'none' === plain.h2 && 'counter(doc-point) "."|doc-point 0' === plain.points.join( '|' ) && 0 === plain.nums, JSON.stringify( plain ) );
		// Своя страница — тоже документ: убираем сразу, иначе она попадёт в «Другие документы» при сверке.
		wp( 'post', 'delete', own, '--force' );
		created.length = 0;

		// ===== Печать =====
		await page.goto( DOCS.cookie.url, { waitUntil: 'networkidle' } );
		await page.emulateMedia( { media: 'print' } );
		const printed = await page.evaluate( () => [ '.site-header', '.site-footer', '.doc-toc--side', '.b-docs', '.b-cookie-set', '.hero-crumbs', '.b-hero-document__buttons' ].map( ( sel ) => getComputedStyle( document.querySelector( sel ) ).display ).concat( getComputedStyle( document.querySelector( '.hero-box' ) ).backgroundColor ) );
		await page.emulateMedia( { media: 'screen' } );
		check( 'Печать: без шапки, подвала, содержания, «Других документов», настроек cookie, крошек и кнопок; карточка первого экрана белая',
			printed.slice( 0, 7 ).every( ( d ) => 'none' === d ) && 'rgb(255, 255, 255)' === printed[ 7 ], JSON.stringify( printed ) );

		// ===== Без JavaScript =====
		const noJs = await browser.newContext( { javaScriptEnabled: false, viewport: { width: 390, height: 900 } } );
		const plainPage = await noJs.newPage();
		await plainPage.goto( DOCS.cookie.url, { waitUntil: 'domcontentloaded' } );
		await plainPage.locator( '.doc-toc--drop summary' ).dispatchEvent( 'click' );
		const nj = await plainPage.evaluate( () => [ document.querySelector( '.doc-toc--drop' ).open, getComputedStyle( document.querySelector( '.b-cookie-set__switch' ) ).display, getComputedStyle( document.querySelector( '.b-cookie-set__nojs' ) ).display ] );
		await noJs.close();
		check( 'Без JavaScript: «Содержание» раскрывается; вместо переключателей cookie — подсказка', JSON.stringify( nj ) === JSON.stringify( [ true, 'none', 'block' ] ), JSON.stringify( nj ) );

		// ===== Сверка с макетами =====
		const sets = [
			[ 'oferta', 'summary', '.b-summary', 'aside.dc-sum' ],
			[ 'oferta', 'terms', '.b-defs--lines', 'dl:has(dt[style*="170px"])' ],
			[ 'oferta', 'docs', '.b-docs', 'section[aria-labelledby="docs-title"]' ],
			[ 'cookie', 'cards', '.b-doc-cards', 'ul.dc-cats' ],
			[ 'cookie', 'table', '.doc-content .wp-block-table', '.dc-tablewrap' ],
			[ 'cookie', 'settings', '.b-cookie-set', '#cookie-settings' ],
			[ 'cookie', 'contacts', '.b-defs--card', 'section:has(#ck-s7) dl' ],
			[ 'consent', 'pair', '.doc-pair', 'div:has(> aside.dc-sum)' ],
			[ 'consent', 'intro', '.is-style-doc-intro', '.dc-stmt' ],
			[ 'consent', 'table', '.doc-content .wp-block-table', '.dc-tablewrap' ],
			[ 'consent', 'docs', '.b-docs', 'section[aria-labelledby="docs-title"]' ],
		];
		for ( const width of [ 1280, 768, 390 ] ) {
			for ( const [ doc, name, theme, mock ] of sets ) {
				await compare( page, doc, name, width, theme, mock );
			}
		}
		await compare( page, 'oferta', 'toc', 1280, '.doc-toc--side', 'aside.dc-toc-desk' );
		// Разделы целиком (номера «1.», «1.1.», «3.1.» — как в макете).
		for ( const width of [ 1280, 390 ] ) {
			await compareRange( page, 'oferta', 'section-1', width, [ '.doc-content > h2:nth-of-type(1)', '.doc-content > ol:nth-of-type(1)' ], [ '#offer-s1', '#offer-s1 + ol' ] );
			await compareRange( page, 'oferta', 'section-3-4', width, [ '.doc-content > h2:nth-of-type(3)', '.doc-content > ol:nth-of-type(3)' ], [ '#offer-s3', '#offer-s4 + ol' ] );
		}
		await compare( page, 'cookie', 'toc-phone', 390, '.doc-toc--drop', 'details.dc-toc-mob' );

		// ===== Ширины и доступность =====
		const wide = [];
		for ( const doc of Object.keys( DOCS ) ) {
			for ( const width of [ 1440, 1024, 768, 390, 320, 280 ] ) {
				await page.setViewportSize( { width, height: 900 } );
				await page.goto( DOCS[ doc ].url, { waitUntil: 'domcontentloaded' } );
				if ( await page.evaluate( () => document.documentElement.scrollWidth > innerWidth ) ) {
					wide.push( `${ doc } ${ width }` );
				}
			}
		}
		check( 'Ширины 1440–280: без прокрутки вбок (три документа)', 0 === wide.length, wide.join( ', ' ) );
		await page.setViewportSize( { width: 1280, height: 900 } );
		for ( const doc of Object.keys( DOCS ) ) {
			await page.goto( DOCS[ doc ].url, { waitUntil: 'networkidle' } );
			await page.addScriptTag( { path: require.resolve( 'axe-core' ) } );
			const axe = await page.evaluate( async () => ( await window.axe.run( document, { resultTypes: [ 'violations' ] } ) ).violations.filter( ( v ) => [ 'critical', 'serious' ].includes( v.impact ) ).map( ( v ) => v.id ) );
			check( `axe: нет нарушений critical/serious — ${ doc }`, 0 === axe.length, axe.join( ', ' ) );
		}
		check( 'Консоль браузера на сайте без ошибок', 0 === errors.length, errors.slice( 0, 3 ).join( ' | ' ) );

		// ===== Редактор =====
		const editor = await browser.newPage( { viewport: { width: 1400, height: 1000 } } );
		const editorErrors = collectConsoleErrors( editor );
		await login( editor );
		const offerId = wp( 'post', 'list', '--post_type=page', '--name=oferta', '--field=ID' ).trim();
		await editor.goto( `${ URL }/wp-admin/post.php?post=${ offerId }&action=edit` );
		await editor.waitForFunction( () => window.wp && wp.data && wp.data.select( 'core/block-editor' ) && wp.data.select( 'core/block-editor' ).getBlocks().length, null, { timeout: 60000 } );
		await editor.waitForTimeout( 1500 );
		const ed = await editor.evaluate( () => {
			const flat = ( list ) => list.flatMap( ( b ) => [ b, ...flat( b.innerBlocks ) ] );
			const blocks = flat( wp.data.select( 'core/block-editor' ).getBlocks() );
			return {
				types: [ 'trust/summary', 'trust/definitions', 'trust/doc-cards', 'trust/consent-example', 'trust/cookie-settings', 'trust/documents-list' ].map( ( name ) => {
					const type = wp.blocks.getBlockType( name );
					return type ? `${ type.title }:${ type.category }:${ !! type.example }` : name;
				} ),
				invalid: blocks.filter( ( b ) => ! b.isValid ).length,
				styles: [ 'core/list', 'core/paragraph', 'core/table' ].map( ( name ) => wp.data.select( 'core/blocks' ).getBlockStyles( name ).map( ( s ) => s.name ).filter( ( s ) => [ 'clauses', 'doc-intro', 'doc-note', 'row-headers' ].includes( s ) ).join( ',' ) ),
				group: [ ...document.querySelectorAll( '.postbox .hndle, .postbox h2' ) ].map( ( h ) => h.textContent.trim() ).filter( ( t ) => 'Документ' === t ).length,
			};
		} );
		check( 'Редактор: шесть блоков документа («Текст и медиа»), у всех превью',
			'Коротко о главном:trust-content:true|Список определений:trust-content:true|Карточки с плашкой:trust-content:true|Пример галочки согласия:trust-content:true|Настройки cookie:trust-content:true|Другие документы:trust-content:true' === ed.types.join( '|' ), JSON.stringify( ed.types ) );
		check( 'Редактор: блоки оферты без ошибок разметки; стили «Пункты документа», «Вступление», «Примечание», «Первая колонка — заголовки строк»; группа полей «Документ»',
			0 === ed.invalid && 'clauses|doc-intro,doc-note|row-headers' === ed.styles.join( '|' ) && ed.group >= 1, JSON.stringify( ed ) );
		check( 'Редактор без ошибок JavaScript', 0 === editorErrors.length, editorErrors.slice( 0, 3 ).join( ' | ' ) );
		await editor.close();
	} finally {
		created.forEach( ( id ) => wp( 'post', 'delete', id, '--force' ) );
		await browser.close();
	}
	checkLog();
	finish();
} )();
