/**
 * Миниатюры типов страниц для окна «Выберите тип страницы» — assets/img/page-types/<тип>.jpg.
 *
 * Запуск (после изменения макетов): node wp-theme/tests/page-type-thumbs.js
 *
 * Первый экран HTML-экспорта макета (1280×1440) в уменьшенном виде 320×360. Для типов без
 * макета («Главная / посадочная», «Обычная страница») — схема в цветах темы.
 */

const { chromium } = require( 'playwright' );
const path = require( 'path' );
const { THEME, routeMockupFonts, mockupUrl } = require( './lib' );

const OUT = path.join( THEME, 'assets/img/page-types' );

const MOCKUPS = {
	service: 'seo-stranitsa/index.html',
	portfolio: 'portfolio-blog-404/portfolio.html',
	about: 'o-nas/o-kompanii.html',
	team: 'o-nas/komanda.html',
	reviews: 'o-nas/otzyvy.html',
	career: 'o-nas/karera.html',
	contacts: 'o-nas/kontakty.html',
	document: 'o-nas/oferta.html',
	questions: 'seo-vopros/vopros-seo.html',
};

/** Схема страницы: шапка, первый экран (индиго или светлый) и строки текста. */
function scheme( hero ) {
	const line = ( w ) => `<div style="height:22px;width:${ w }%;background:#E6DFF5;border-radius:11px;margin:0 0 26px"></div>`;
	const heroHtml = 'indigo' === hero
		? '<div style="background:#1E1B4B;padding:110px 96px 120px"><div style="height:70px;width:60%;background:#8B5CF6;border-radius:12px;margin-bottom:40px"></div><div style="height:22px;width:45%;background:#4C468F;border-radius:11px"></div></div>'
		: '<div style="padding:110px 96px 40px"><div style="height:70px;width:55%;background:#0E0B14;border-radius:12px"></div></div>';
	return `<!doctype html><html><body style="margin:0;background:#fff;font-family:sans-serif">
		<div style="height:96px;border-bottom:2px solid #EEE7FE;display:flex;align-items:center;padding:0 96px"><div style="width:48px;height:48px;background:#6D28D9;border-radius:12px"></div></div>
		${ heroHtml }
		<div style="padding:90px 96px">${ line( 90 ) }${ line( 95 ) }${ line( 80 ) }${ line( 92 ) }${ line( 60 ) }</div>
	</body></html>`;
}

( async () => {
	const browser = await chromium.launch();
	const context = await browser.newContext( { viewport: { width: 1280, height: 1440 }, deviceScaleFactor: 0.25 } );
	await routeMockupFonts( context );
	const page = await context.newPage();

	for ( const [ key, file ] of Object.entries( MOCKUPS ) ) {
		await page.goto( mockupUrl( file ), { waitUntil: 'networkidle' } );
		await page.evaluate( () => document.fonts.ready );
		await page.waitForTimeout( 300 );
		await page.screenshot( { path: path.join( OUT, `${ key }.jpg` ), type: 'jpeg', quality: 80 } );
		console.log( `${ key }.jpg ← ${ file }` );
	}
	for ( const [ key, hero ] of [ [ 'landing', 'indigo' ], [ 'default', 'light' ] ] ) {
		await page.setContent( scheme( hero ) );
		await page.screenshot( { path: path.join( OUT, `${ key }.jpg` ), type: 'jpeg', quality: 80 } );
		console.log( `${ key }.jpg ← схема` );
	}
	await browser.close();
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
