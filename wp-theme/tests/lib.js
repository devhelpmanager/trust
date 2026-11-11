/**
 * Общие помощники автотестов темы Trust.
 */

const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const URL = process.env.TRUST_URL || 'http://localhost:8080';
const DEV_DIR = process.env.TRUST_DEV_DIR || path.join( os.homedir(), 'wp-dev' );
const LOG = path.join( DEV_DIR, 'debug.log' );
const LOCAL = path.join( __dirname, '..', 'bin', 'local.sh' );
const OUT = path.join( __dirname, 'output' );

fs.mkdirSync( OUT, { recursive: true } );

/** Результаты проверок текущего теста. */
const results = [];

/**
 * Записывает результат проверки и печатает его.
 *
 * @param {string}  name    Что проверяем.
 * @param {boolean} ok      Пройдено ли.
 * @param {string}  details Подробности (необязательно).
 */
function check( name, ok, details = '' ) {
	results.push( { name, ok: !! ok, details } );
	console.log( `${ ok ? '✓' : '✗' } ${ name }${ details ? ' — ' + details : '' }` );
}

/**
 * Печатает итог и завершает процесс с кодом 1, если есть проваленные проверки.
 */
function finish() {
	const failed = results.filter( ( r ) => ! r.ok ).length;
	console.log( `\n${ results.length - failed } из ${ results.length } проверок пройдено` );
	process.exit( failed ? 1 : 0 );
}

/**
 * Команда WP-CLI на стенде.
 *
 * @param {...string} args Аргументы команды.
 * @return {string} Вывод команды.
 */
function wp( ...args ) {
	return execFileSync( LOCAL, [ 'wp', ...args ], {
		encoding: 'utf8',
		env: { ...process.env, TRUST_DEV_DIR: DEV_DIR },
		stdio: [ 'ignore', 'pipe', 'pipe' ],
	} ).trim();
}

/** Очищает журнал ошибок WordPress. */
function clearLog() {
	if ( fs.existsSync( LOG ) ) {
		fs.writeFileSync( LOG, '' );
	}
}

/**
 * Проверяет, что журнал ошибок WordPress пуст.
 */
function checkLog() {
	const log = fs.existsSync( LOG ) ? fs.readFileSync( LOG, 'utf8' ).trim() : '';
	check( 'Журнал ошибок WordPress пуст', log === '', log.split( '\n' ).slice( 0, 3 ).join( ' | ' ) );
}

/**
 * Входит в админку.
 *
 * @param {import('playwright').Page} page     Страница.
 * @param {string}                    user     Логин.
 * @param {string}                    password Пароль.
 */
async function login( page, user = 'admin', password = 'admin' ) {
	await page.context().clearCookies();
	await page.goto( `${ URL }/wp-login.php` );
	await page.fill( '#user_login', user );
	await page.fill( '#user_pass', password );
	await Promise.all( [ page.waitForNavigation(), page.click( '#wp-submit' ) ] );
}

/**
 * Собирает ошибки консоли и необработанные исключения страницы.
 *
 * @param {import('playwright').Page} page Страница.
 * @return {string[]} Массив, который пополняется по ходу теста.
 */
function collectConsoleErrors( page ) {
	const errors = [];
	page.on( 'console', ( msg ) => msg.type() === 'error' && errors.push( `${ page.url() }: ${ msg.text() }` ) );
	page.on( 'pageerror', ( err ) => errors.push( `${ page.url() }: ${ err.message }` ) );
	return errors;
}

/**
 * Создаёт пользователя на стенде, если его ещё нет.
 *
 * @param {string} login Логин (он же пароль).
 * @param {string} role  Роль.
 */
function ensureUser( login, role ) {
	try {
		wp( 'user', 'get', login, '--field=ID' );
	} catch ( e ) {
		wp( 'user', 'create', login, `${ login }@example.test`, `--role=${ role }`, `--user_pass=${ login }`, '--quiet' );
	}
}

module.exports = { URL, DEV_DIR, LOG, OUT, check, finish, wp, clearLog, checkLog, login, collectConsoleErrors, ensureUser };
