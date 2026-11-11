<?php
/**
 * Команда WP-CLI `wp trust demo`.
 *
 * @package Trust
 */

namespace Trust\CLI;

use Trust\Demo\Importer;
use Trust\Dependencies;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Демо-контент темы Trust: сайт, который выглядит как макеты.
 *
 * ## EXAMPLES
 *
 *     wp trust demo import
 *     wp trust demo remove
 */
final class Demo_Command {

	/**
	 * Импортирует демо-контент.
	 *
	 * Сейчас импортируются «Настройки сайта» (заглушки из макетов). Повторный импорт
	 * не создаёт дублей и не трогает то, что изменено вручную.
	 *
	 * ## EXAMPLES
	 *
	 *     wp trust demo import
	 *
	 * @param array $args       Позиционные аргументы (не используются).
	 * @param array $assoc_args Именованные аргументы (не используются).
	 */
	public function import( array $args, array $assoc_args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- сигнатура команды WP-CLI.
		self::require_scf();

		$settings = Importer::import_settings();
		WP_CLI::log( sprintf( 'Настройки сайта: записано %d.', count( $settings['written'] ) ) );
		if ( $settings['skipped'] ) {
			WP_CLI::log( 'Пропущены (изменены вручную): ' . implode( ', ', $settings['skipped'] ) );
		}

		WP_CLI::success( 'Демо-контент импортирован.' );
	}

	/**
	 * Удаляет демо-контент. То, что изменено вручную, остаётся.
	 *
	 * ## EXAMPLES
	 *
	 *     wp trust demo remove
	 *
	 * @param array $args       Позиционные аргументы (не используются).
	 * @param array $assoc_args Именованные аргументы (не используются).
	 */
	public function remove( array $args, array $assoc_args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- сигнатура команды WP-CLI.
		self::require_scf();

		$settings = Importer::remove_settings();
		WP_CLI::log( sprintf( 'Настройки сайта: удалено %d.', count( $settings['removed'] ) ) );
		if ( $settings['kept'] ) {
			WP_CLI::log( 'Оставлены (изменены вручную): ' . implode( ', ', $settings['kept'] ) );
		}

		WP_CLI::success( 'Демо-контент удалён.' );
	}

	/**
	 * Без Secure Custom Fields демо-контент не записать.
	 */
	private static function require_scf(): void {
		if ( ! Dependencies::has_scf() ) {
			WP_CLI::error( 'Нужен активный плагин Secure Custom Fields: wp plugin install secure-custom-fields --activate' );
		}
	}
}
