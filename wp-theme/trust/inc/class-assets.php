<?php
/**
 * Подключение общих стилей и предзагрузка шрифтов.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Общие CSS и шрифты темы.
 */
final class Assets {

	/**
	 * Шрифты, которые нужны на первом экране любой страницы:
	 * основной текст и заголовки, кириллица.
	 */
	private const PRELOAD_FONTS = array(
		'onest-cyrillic-400-normal.woff2',
		'unbounded-cyrillic-600-normal.woff2',
	);

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_action( 'wp_head', array( self::class, 'js_class' ), 0 );
		add_action( 'wp_head', array( self::class, 'preload_fonts' ), 1 );
	}

	/**
	 * Класс js у <html> до отрисовки страницы: стили знают, что JavaScript работает,
	 * и мобильное меню не мелькает раскрытым (запасной вид без JavaScript).
	 * Одна строка встроенного кода, без загрузки файлов.
	 */
	public static function js_class(): void {
		wp_print_inline_script_tag( "document.documentElement.classList.add('js');" );
	}

	/**
	 * Подключает общий файл стилей.
	 */
	public static function enqueue(): void {
		wp_enqueue_style( 'trust-main', self::url( 'assets/css/main.css' ), array(), self::version( 'assets/css/main.css' ) );

		wp_enqueue_script(
			'trust-header',
			self::url( 'assets/js/header.js' ),
			array(),
			self::version( 'assets/js/header.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}

	/**
	 * Выводит <link rel="preload"> для основных начертаний.
	 */
	public static function preload_fonts(): void {
		foreach ( self::PRELOAD_FONTS as $font ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				esc_url( self::url( 'assets/fonts/' . $font ) )
			);
		}
	}

	/**
	 * Адрес файла темы.
	 *
	 * @param string $path Путь от корня темы.
	 */
	public static function url( string $path ): string {
		return TRUST_URI . '/' . ltrim( $path, '/' );
	}

	/**
	 * Версия файла для сброса кеша браузера: время изменения файла.
	 *
	 * @param string $path Путь от корня темы.
	 */
	public static function version( string $path ): string {
		$file = TRUST_DIR . '/' . ltrim( $path, '/' );
		return is_readable( $file ) ? (string) filemtime( $file ) : TRUST_VERSION;
	}
}
