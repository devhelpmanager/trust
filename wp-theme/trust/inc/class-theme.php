<?php
/**
 * Запуск модулей темы.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Подключает модули темы в нужном порядке.
 */
final class Theme {

	/**
	 * Регистрирует хуки всех модулей.
	 */
	public static function boot(): void {
		Setup::register();
		Cleanup::register();
		Assets::register();
		Dependencies::register();
		Settings::register();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'trust demo', CLI\Demo_Command::class );
		}
	}
}
