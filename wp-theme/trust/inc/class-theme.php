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
	}
}
