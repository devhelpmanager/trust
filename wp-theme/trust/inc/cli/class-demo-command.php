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
	 * Сейчас импортируются «Настройки сайта» (заглушки из макетов), страницы из меню
	 * и меню «Шапка». Повторный импорт не создаёт дублей и не трогает то, что изменено
	 * вручную; демо-меню не ставится в область, где уже есть своё меню.
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
		self::log_list( 'Пропущены (изменены вручную)', $settings['skipped'] );

		$pages = Importer::import_pages();
		WP_CLI::log( sprintf( 'Страницы: создано %d, обновлено %d.', count( $pages['created'] ), count( $pages['updated'] ) ) );
		self::log_list( 'Пропущены (изменены вручную или уже есть свои)', $pages['skipped'] );

		$menus = Importer::import_menus();
		WP_CLI::log( sprintf( 'Меню: создано %d.', count( $menus['created'] ) ) );
		self::log_list( 'Пропущены (уже импортированы или в области стоит своё меню)', $menus['skipped'] );

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

		$menus = Importer::remove_menus();
		WP_CLI::log( sprintf( 'Меню: удалено %d.', count( $menus['removed'] ) ) );

		$pages = Importer::remove_pages();
		WP_CLI::log( sprintf( 'Страницы: удалено %d.', count( $pages['removed'] ) ) );
		self::log_list( 'Оставлены (изменены вручную)', $pages['kept'] );

		$settings = Importer::remove_settings();
		WP_CLI::log( sprintf( 'Настройки сайта: удалено %d.', count( $settings['removed'] ) ) );
		self::log_list( 'Оставлены (изменены вручную)', $settings['kept'] );

		WP_CLI::success( 'Демо-контент удалён.' );
	}

	/**
	 * Печатает список, если он не пустой.
	 *
	 * @param string   $label Подпись.
	 * @param string[] $items Элементы.
	 */
	private static function log_list( string $label, array $items ): void {
		if ( $items ) {
			WP_CLI::log( '  ' . $label . ': ' . implode( ', ', $items ) );
		}
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
