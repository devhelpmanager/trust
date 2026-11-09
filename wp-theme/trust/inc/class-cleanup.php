<?php
/**
 * Отключение лишнего: emoji, служебные ссылки в <head>, библиотека шрифтов,
 * каталог блоков и удалённые паттерны.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Убирает из ядра то, что теме не нужно и мешает скорости или дизайну.
 */
final class Cleanup {

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'disable_emoji' ) );
		add_action( 'init', array( self::class, 'clean_head' ) );

		// Стили ядра — только для блоков, которые есть на странице.
		add_filter( 'should_load_separate_core_block_assets', '__return_true' );
		add_action( 'wp_enqueue_scripts', array( self::class, 'dequeue_core_styles' ), 20 );

		// Редактор: без каталога блоков, удалённых паттернов и библиотеки шрифтов.
		remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
		add_filter( 'should_load_remote_block_patterns', '__return_false' );
		add_filter( 'block_editor_settings_all', array( self::class, 'editor_settings' ) );
	}

	/**
	 * Отключает скрипты и стили emoji.
	 */
	public static function disable_emoji(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_action( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );
		add_filter(
			'tiny_mce_plugins',
			static fn( $plugins ) => is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : $plugins
		);
	}

	/**
	 * Убирает служебные ссылки из <head>.
	 */
	public static function clean_head(): void {
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	}

	/**
	 * Отключает стили ядра для классических тем — тема задаёт всё сама.
	 */
	public static function dequeue_core_styles(): void {
		wp_dequeue_style( 'classic-theme-styles' );
	}

	/**
	 * Настройки редактора блоков.
	 *
	 * @param array $settings Настройки редактора.
	 * @return array
	 */
	public static function editor_settings( array $settings ): array {
		$settings['fontLibraryEnabled'] = false;
		return $settings;
	}
}
