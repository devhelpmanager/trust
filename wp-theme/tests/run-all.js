/**
 * Запуск всех автотестов темы по очереди с итоговой сводкой.
 *
 * Запуск: node wp-theme/tests/run-all.js (или npm test в папке wp-theme/tests).
 * Часть тестов: node wp-theme/tests/run-all.js smoke header faq.
 * Нужен запущенный стенд с импортированным демо-контентом (wp-theme/bin/local.sh).
 */

const { spawnSync } = require( 'child_process' );
const path = require( 'path' );

const TESTS = [ 'smoke', 'settings', 'header', 'header-mobile', 'footer', 'callback', 'faq', 'content-types', 'blog-fields', 'cases-reviews', 'team-questions', 'page-types', 'seo', 'breadcrumbs', 'sitemap', 'schema', 'leads', 'notifications', 'cookie', 'heroes', 'heroes-pages', 'service-blocks', 'service-page', 'content-blocks', 'form-blocks', 'visual' ];

let failed = 0;
const summary = [];

const names = process.argv.length > 2 ? process.argv.slice( 2 ) : TESTS;

for ( const name of names ) {
	const run = spawnSync( process.execPath, [ path.join( __dirname, `${ name }.js` ) ], { encoding: 'utf8' } );
	const out = `${ run.stdout || '' }${ run.stderr || '' }`;
	const total = ( out.match( /(\d+) из (\d+) проверок пройдено/ ) || [] ).slice( 1 );
	const fails = out.split( '\n' ).filter( ( line ) => line.startsWith( '✗' ) );
	const ok = 0 === run.status;
	failed += ok ? 0 : 1;
	summary.push( `${ ok ? '✓' : '✗' } ${ name.padEnd( 14 ) } ${ total.length ? `${ total[ 0 ] } из ${ total[ 1 ] }` : 'не завершился' }` );
	fails.forEach( ( line ) => summary.push( `    ${ line }` ) );
	if ( ! ok && ! total.length ) {
		summary.push( out.split( '\n' ).slice( -5 ).map( ( l ) => `    ${ l }` ).join( '\n' ) );
	}
}

console.log( summary.join( '\n' ) );
console.log( failed ? `\nНе прошли тестов: ${ failed }` : '\nВсе тесты пройдены' );
process.exit( failed ? 1 : 0 );
