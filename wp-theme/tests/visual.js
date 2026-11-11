/**
 * Визуальная сверка с макетами (шаг 1.8, ТЗ п. 18.1.1): шапка, мобильное меню, подвал, «Вопрос-ответ».
 *
 * Запуск: node wp-theme/tests/visual.js (стенд запущен, демо-контент импортирован).
 *
 * Каждый элемент снимается на теме и в HTML-экспорте макета (шрифты эталона — локальные шрифты
 * темы), всё, что стоит на странице выше элемента, скрывается — так координаты совпадают.
 * Порог — 2 % отличающихся пикселей. На время сверки в настройки подставляются заглушки
 * из макетов шапки и подвала («[Часы работы]», «[Адрес офиса]», «© [год]»), потом прежние
 * значения возвращаются — маски не нужны.
 *
 * Результат: tests/output/visual/report.html (эталон / тема / разница) и PNG по каждому сравнению.
 */

const { chromium } = require( 'playwright' );
const fs = require( 'fs' );
const path = require( 'path' );
const { PNG } = require( 'pngjs' );
const { URL, OUT, check, finish, clearLog, checkLog, routeMockupFonts, mockupUrl, comparePng, settle, isolate, getSettings, setSettings } = require( './lib' );

const DIR = path.join( OUT, 'visual' );
fs.mkdirSync( DIR, { recursive: true } );

const PORTFOLIO = 'portfolio-blog-404/portfolio.html';
const FAQ = 'seo-bloki/10-vopros-otvet.html';

/** Сравнения: что, где в теме, где в макете, ширины, действия перед снимком. */
const CASES = [
	{ id: 'header', title: 'Шапка', theme: '/portfolio/', themeSel: '.site-header', mock: PORTFOLIO, mockSel: 'header', widths: [ 1280, 768, 390 ] },
	{
		id: 'header-menu', title: 'Мобильное меню, раскрыт раздел SEO', theme: '/portfolio/', themeSel: '.site-header', mock: PORTFOLIO, mockSel: 'header', widths: [ 768, 390 ],
		themeAct: async ( p ) => {
			await p.click( '.header-burger' );
			await p.locator( '.mobile-nav__toggle', { hasText: 'SEO' } ).click();
		},
		mockAct: async ( p ) => {
			await p.click( '.dc-burger' );
			await p.locator( '.dc-mobile-menu button', { hasText: 'SEO' } ).click();
		},
	},
	{ id: 'footer', title: 'Подвал', theme: '/', themeSel: '.site-footer', mock: PORTFOLIO, mockSel: 'footer.dc-foot', widths: [ 1280, 768, 390 ] },
	{ id: 'faq', title: 'Вопрос-ответ', theme: '/seo/audit/', themeSel: '.b-faq', mock: FAQ, mockSel: 'section', widths: [ 1280, 768, 390 ] },
	{
		id: 'faq-open', title: 'Вопрос-ответ, первый ответ открыт', theme: '/seo/audit/', themeSel: '.b-faq', mock: FAQ, mockSel: 'section', widths: [ 1280, 390 ],
		themeAct: async ( p ) => p.locator( '.b-faq__toggle' ).first().click(),
		mockAct: async ( p ) => p.locator( 'section button' ).first().click(),
	},
];

/**
 * Снимок элемента: открыть страницу, выполнить действия, скрыть всё выше элемента.
 */
async function shoot( page, url, selector, action, file ) {
	await page.goto( url, { waitUntil: 'networkidle' } );
	await settle( page );
	if ( action ) {
		await action( page );
		await page.mouse.move( 0, 0 );
		await page.waitForTimeout( 300 );
	}
	await isolate( page, selector );
	await page.locator( selector ).first().screenshot( { path: file } );
}

/**
 * Картинка «эталон | тема | разница» в одну строку.
 */
function composite( files, out ) {
	const imgs = files.map( ( f ) => PNG.sync.read( fs.readFileSync( f ) ) );
	const gap = 24;
	const width = imgs.reduce( ( sum, img ) => sum + img.width, 0 ) + gap * ( imgs.length - 1 );
	const height = Math.max( ...imgs.map( ( img ) => img.height ) );
	const png = new PNG( { width, height } );
	png.data.fill( 255 );
	let x = 0;
	for ( const img of imgs ) {
		PNG.bitblt( img, png, 0, 0, img.width, img.height, x, 0 );
		x += img.width + gap;
	}
	fs.writeFileSync( out, PNG.sync.write( png ) );
}

( async () => {
	clearLog();
	const saved = getSettings( [ 'hours', 'address', 'footer_legal' ] );
	const results = [];

	const browser = await chromium.launch();
	const context = await browser.newContext();
	await routeMockupFonts( context );
	const page = await context.newPage();

	try {
		setSettings( {
			hours: '[Часы работы]',
			address: { city: '[Адрес офиса]', street: '', office: '', postal_code: '' },
			footer_legal: String( saved.footer_legal || '' ).replace( '{год}', '[год]' ),
		} );

		for ( const c of CASES ) {
			for ( const w of c.widths ) {
				await page.setViewportSize( { width: w, height: 900 } );
				const base = path.join( DIR, `${ c.id }-${ w }` );
				await shoot( page, URL + c.theme, c.themeSel, c.themeAct, `${ base }-theme.png` );
				await shoot( page, mockupUrl( c.mock ), c.mockSel, c.mockAct, `${ base }-mockup.png` );
				const diff = comparePng( `${ base }-mockup.png`, `${ base }-theme.png`, `${ base }-diff.png` );
				if ( diff < 100 ) {
					composite( [ `${ base }-mockup.png`, `${ base }-theme.png`, `${ base }-diff.png` ], `${ base }-compare.png` );
				}
				results.push( { title: c.title, width: w, diff, base: path.basename( base ) } );
				check( `${ c.title }, ${ w }px: совпадение с макетом (≤ 2 %)`, diff <= 2, `${ diff.toFixed( 2 ) } %` );
			}
		}
	} finally {
		setSettings( saved );
		await browser.close();
	}

	// Отчёт: картинки встроены, файл открывается сам по себе.
	const img = ( file ) => fs.existsSync( file ) ? `data:image/png;base64,${ fs.readFileSync( file ).toString( 'base64' ) }` : '';
	const rows = results.map( ( r ) => `
		<section>
			<h2>${ r.title } — ${ r.width } px: <span class="${ r.diff <= 2 ? 'ok' : 'bad' }">${ r.diff.toFixed( 2 ) } %</span></h2>
			<div class="row">
				<figure><figcaption>Макет</figcaption><img src="${ img( path.join( DIR, `${ r.base }-mockup.png` ) ) }"></figure>
				<figure><figcaption>Тема</figcaption><img src="${ img( path.join( DIR, `${ r.base }-theme.png` ) ) }"></figure>
				<figure><figcaption>Разница (красное — отличия)</figcaption><img src="${ img( path.join( DIR, `${ r.base }-diff.png` ) ) }"></figure>
			</div>
		</section>` ).join( '' );
	const html = `<!doctype html><html lang="ru"><head><meta charset="utf-8"><title>Визуальная сверка с макетами</title>
<style>body{margin:0;padding:24px;font:15px/1.5 system-ui,sans-serif;color:#0E0B14;background:#F8F5FF}h1{margin:0 0 8px}
table{border-collapse:collapse;margin:16px 0 32px;background:#fff}td,th{padding:6px 12px;border:1px solid #E0D7F5;text-align:left}
section{margin:0 0 32px;padding:16px;background:#fff;border:1px solid #E0D7F5;border-radius:12px}h2{margin:0 0 12px;font-size:17px}
.row{display:flex;gap:16px;overflow-x:auto;align-items:flex-start}figure{margin:0;flex:none}figcaption{font-size:13px;color:#5A5566;margin-bottom:4px}
img{display:block;max-width:none;border:1px solid #E0D7F5}.ok{color:#17914F}.bad{color:#D63A2F}</style></head><body>
<h1>Визуальная сверка с макетами</h1><p>${ new Date().toLocaleString( 'ru-RU' ) }. Порог — 2 % отличающихся пикселей.</p>
<table><tr><th>Что</th><th>Ширина</th><th>Отличия</th></tr>${ results.map( ( r ) => `<tr><td>${ r.title }</td><td>${ r.width } px</td><td class="${ r.diff <= 2 ? 'ok' : 'bad' }">${ r.diff.toFixed( 2 ) } %</td></tr>` ).join( '' ) }</table>
${ rows }</body></html>`;
	fs.writeFileSync( path.join( DIR, 'report.html' ), html );
	console.log( `\nОтчёт: ${ path.join( DIR, 'report.html' ) }` );

	checkLog();
	finish();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
