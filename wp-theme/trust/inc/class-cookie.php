<?php
/**
 * Cookie и счётчики: плашка согласия, хранение выбора, код счётчиков после согласия (ТЗ, раздел 14).
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Выбор посетителя хранится в cookie trust_consent (категории necessary, analytics, marketing,
 * вариант выбора, дата, версия) на срок из «Настроек сайта → Cookie и счётчики». Плашку и код
 * счётчиков обслуживает assets/js/cookie.js — так кэш страниц не мешает: страница для всех
 * одинаковая, а решение принимается в браузере.
 *
 * Код счётчиков и пикселей из настроек выводится внутри <template> (браузер его не выполняет)
 * и вставляется скриптом только после согласия на свою категорию — сразу, без перезагрузки.
 * Обязательные cookie темы (trust_consent, trust_source — источник перехода для заявок)
 * ставятся без согласия.
 */
final class Cookie {

	/**
	 * Имя cookie с выбором посетителя.
	 */
	public const NAME = 'trust_consent';

	/**
	 * Срок хранения выбора по умолчанию, дней.
	 */
	public const DAYS = 365;

	/**
	 * Категории, на которые спрашивается согласие (обязательные включены всегда).
	 */
	public const CATEGORIES = array( 'analytics', 'marketing' );

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_action( 'wp_footer', array( self::class, 'render' ), 5 );
	}

	/**
	 * Тексты по умолчанию (из макета seo-bloki/17-cookie.html).
	 *
	 * @return array<string, string>
	 */
	public static function defaults(): array {
		return array(
			'title' => __( 'Мы используем файлы cookie', 'trust' ),
			'text'  => __( 'Обязательные cookie нужны для работы сайта, аналитические помогают понять, какие страницы полезны посетителям. Подробнее — в {политике использования cookie}.', 'trust' ),
		);
	}

	/**
	 * Текст плашки: из настроек или по умолчанию.
	 *
	 * @param string $key title или text.
	 */
	public static function text( string $key ): string {
		$value = trim( (string) Settings::get( 'cookie_' . $key, '' ) );
		return '' !== $value ? $value : self::defaults()[ $key ];
	}

	/**
	 * Текст плашки в HTML: часть в {фигурных скобках} — ссылка на политику cookie
	 * (без выбранной страницы — просто текст).
	 */
	public static function text_html(): string {
		$url  = self::policy_url();
		$text = esc_html( self::text( 'text' ) );
		return (string) preg_replace_callback(
			'/\{([^{}]+)\}/u',
			static fn( array $m ): string => $url ? '<a href="' . esc_url( $url ) . '">' . $m[1] . '</a>' : $m[1],
			$text
		);
	}

	/**
	 * Страница «Политика использования cookie».
	 */
	public static function policy_url(): string {
		$page = (int) Settings::get( 'cookie_policy', 0 );
		return $page && 'publish' === get_post_status( $page ) ? (string) get_permalink( $page ) : '';
	}

	/**
	 * Срок хранения выбора, дней.
	 */
	public static function days(): int {
		$days = (int) Settings::get( 'cookie_days', 0 );
		return $days > 0 ? min( $days, 3650 ) : self::DAYS;
	}

	/**
	 * Версия согласия: изменили — плашка снова появится у всех посетителей.
	 */
	public static function version(): string {
		$version = trim( (string) Settings::get( 'cookie_version', '' ) );
		return '' !== $version ? $version : '1';
	}

	/**
	 * Код категории из настроек (счётчики — analytics, пиксели — marketing).
	 *
	 * @param string $category Категория.
	 */
	public static function code( string $category ): string {
		return trim( (string) Settings::get( 'cookie_code_' . $category, '' ) );
	}

	/**
	 * Скрипт плашки и его настройки.
	 */
	public static function enqueue(): void {
		wp_enqueue_script(
			'trust-cookie',
			Assets::url( 'assets/js/cookie.js' ),
			array(),
			Assets::version( 'assets/js/cookie.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		$config = array(
			'name'     => self::NAME,
			'days'     => self::days(),
			'version'  => self::version(),
			'messages' => array(
				'all'       => __( 'Все cookie приняты', 'trust' ),
				'necessary' => __( 'Приняты только обязательные cookie', 'trust' ),
				'none'      => __( 'Необязательные cookie отклонены', 'trust' ),
			),
		);
		wp_add_inline_script( 'trust-cookie', 'window.trustCookieConfig = ' . wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'before' );
	}

	/**
	 * Плашка, подсказка «Изменить» и код счётчиков (до согласия не выполняется).
	 */
	public static function render(): void {
		get_template_part( 'template-parts/cookie-banner' );

		foreach ( self::CATEGORIES as $category ) {
			$code = self::code( $category );
			if ( '' === $code ) {
				continue;
			}
			// Код вводит администратор (право manage_options) — выводится как есть, внутри <template>.
			printf( '<template data-trust-consent="%s">%s</template>' . "\n", esc_attr( $category ), $code ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
