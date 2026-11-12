<?php
/**
 * Базовая настройка темы: поддержка возможностей, области меню.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Возможности темы и области меню.
 */
final class Setup {

	/**
	 * Ширина текстовой колонки (px) — для встраиваемых медиа.
	 */
	public const CONTENT_WIDTH = 760;

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'after_setup_theme', array( self::class, 'setup' ) );
		add_filter( 'language_attributes', array( self::class, 'language_attributes' ) );
	}

	/**
	 * Язык сайта без страны: «ru», а не «ru-RU» (как в макетах и ТЗ). Аудитория — русскоязычные
	 * клиенты по всему миру, поэтому язык не привязывается к России.
	 */
	public static function language(): string {
		return strtok( (string) get_bloginfo( 'language' ), '-' );
	}

	/**
	 * Атрибут lang у <html> на сайте — язык без страны (админка — как в WordPress).
	 *
	 * @param string $output Атрибуты lang и dir.
	 */
	public static function language_attributes( string $output ): string {
		if ( is_admin() ) {
			return $output;
		}
		return (string) preg_replace( '/\blang="[^"]*"/', 'lang="' . esc_attr( self::language() ) . '"', $output );
	}

	/**
	 * Включает возможности темы и регистрирует области меню.
	 */
	public static function setup(): void {
		$GLOBALS['content_width'] = self::CONTENT_WIDTH; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- стандартная переменная темы.

		load_theme_textdomain( 'trust', TRUST_DIR . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
		);

		// Классическая тема: без редактора шаблонов и встроенных паттернов ядра.
		remove_theme_support( 'block-templates' );
		remove_theme_support( 'core-block-patterns' );

		register_nav_menus(
			array(
				'header'          => __( 'Шапка', 'trust' ),
				'footer-services' => __( 'Подвал: услуги', 'trust' ),
				'footer-company'  => __( 'Подвал: компания', 'trust' ),
				'footer-docs'     => __( 'Подвал: документы', 'trust' ),
			)
		);

		add_editor_style( array( 'assets/css/main.css', 'assets/css/editor.css' ) );
	}
}
