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
	 * Демо-записи по типам: тип => array( файл в demo/, подпись ). Порядок — порядок импорта.
	 */
	private const POSTS = array(
		'team'     => array( 'team', 'Сотрудники' ),
		'review'   => array( 'reviews', 'Отзывы' ),
		'case'     => array( 'cases', 'Кейсы' ),
		'vacancy'  => array( 'vacancies', 'Вакансии' ),
		'question' => array( 'questions', 'Вопросы' ),
	);

	/**
	 * Импортирует демо-контент.
	 *
	 * Сейчас импортируются «Настройки сайта» (заглушки из макетов), страницы из меню,
	 * меню шапки и подвала, политика конфиденциальности, рубрики и теги блога, услуги,
	 * отрасли, отделы и темы вопросов, сотрудники, отзывы, кейсы, вакансии и вопросы. Повторный импорт не создаёт дублей и не трогает то, что изменено
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

		// Сначала страницы: на них ссылаются настройки и меню.
		$pages = Importer::import_pages();
		WP_CLI::log( sprintf( 'Страницы: создано %d, обновлено %d.', count( $pages['created'] ), count( $pages['updated'] ) ) );
		self::log_list( 'Пропущены (изменены вручную или уже есть свои)', $pages['skipped'] );

		$settings = Importer::import_settings();
		WP_CLI::log( sprintf( 'Настройки сайта: записано %d.', count( $settings['written'] ) ) );
		self::log_list( 'Пропущены (изменены вручную)', $settings['skipped'] );

		$menus = Importer::import_menus();
		WP_CLI::log( sprintf( 'Меню: создано %d.', count( $menus['created'] ) ) );
		self::log_list( 'Пропущены (уже импортированы или в области стоит своё меню)', $menus['skipped'] );

		WP_CLI::log( 'Политика конфиденциальности: ' . Importer::import_privacy_page() . '.' );

		$terms = Importer::import_terms();
		WP_CLI::log( sprintf( 'Термины: создано %d, обновлено %d.', count( $terms['created'] ), count( $terms['updated'] ) ) );
		self::log_list( 'Пропущены (изменены вручную или уже есть свои)', $terms['skipped'] );

		// Сотрудники и отзывы раньше кейсов и вопросов: те ссылаются на них.
		foreach ( self::POSTS as $type => list( $file, $label ) ) {
			$posts = Importer::import_posts( $type, $file );
			WP_CLI::log( sprintf( '%s: создано %d, обновлено %d.', $label, count( $posts['created'] ), count( $posts['updated'] ) ) );
			self::log_list( 'Пропущены (изменены вручную)', $posts['skipped'] );
		}

		// Второй проход по терминам: поля, которые ссылаются на записи («Ведёт рубрику»).
		$linked = Importer::import_terms();
		if ( $linked['updated'] ) {
			WP_CLI::log( sprintf( 'Термины, связанные с записями: обновлено %d.', count( $linked['updated'] ) ) );
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

		foreach ( array_reverse( self::POSTS, true ) as $type => list( , $label ) ) {
			$posts = Importer::remove_posts( $type );
			WP_CLI::log( sprintf( '%s: удалено %d.', $label, count( $posts['removed'] ) ) );
			self::log_list( 'Оставлены (изменены вручную)', $posts['kept'] );
		}

		$terms = Importer::remove_terms();
		WP_CLI::log( sprintf( 'Термины: удалено %d.', count( $terms['removed'] ) ) );
		self::log_list( 'Оставлены (изменены вручную)', $terms['kept'] );

		Importer::remove_privacy_page();

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
