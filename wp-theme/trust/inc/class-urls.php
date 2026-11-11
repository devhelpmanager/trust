<?php
/**
 * Адреса записей и терминов: ярлыки из «Настроек сайта → Адреса», сброс правил перезаписи.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Ярлыки URL типов записей и таксономий (ТЗ, разделы 5.1, 5.2, 10.4).
 *
 * Значения читаются прямо из опций WordPress, без Secure Custom Fields: адреса не должны
 * меняться, если плагин отключён. Правила перезаписи сбрасываются сами, когда меняются
 * ярлыки или набор типов записей в коде темы, — после обновления темы на сервере
 * ничего делать не нужно.
 */
final class Urls {

	/**
	 * Ярлыки по умолчанию: тип записи или таксономия => ярлык.
	 */
	public const DEFAULTS = array(
		'case'     => 'portfolio',
		'team'     => 'komanda',
		'question' => 'seo/voprosy',
		'category' => 'blog/rubrika',
		'post_tag' => 'blog/teg',
	);

	/**
	 * Типы записей, список которых выводит страница с тем же адресом, что и ярлык
	 * (/portfolio/ — список кейсов, кейс — /portfolio/<кейс>/).
	 */
	private const LIST_PAGES = array( 'case', 'team', 'question' );

	/**
	 * Опция с отпечатком правил перезаписи темы.
	 */
	private const SIGNATURE_OPTION = 'trust_rewrite_signature';

	/**
	 * Префикс имени поля настроек: urls_case, urls_category…
	 */
	public const FIELD_PREFIX = 'urls_';

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'register_taxonomy_args', array( self::class, 'builtin_taxonomy_args' ), 10, 2 );
		add_action( 'init', array( self::class, 'list_page_rules' ), 20 );
		add_action( 'init', array( self::class, 'maybe_flush' ), 99 );
		add_action( 'switch_theme', array( self::class, 'forget_rules' ) );
		add_action( 'admin_notices', array( self::class, 'permalink_notice' ) );

		foreach ( array_keys( self::DEFAULTS ) as $type ) {
			add_filter( 'acf/update_value/key=' . Fields::key( Settings::PREFIX, self::FIELD_PREFIX . $type ), array( self::class, 'normalize_field' ) );
		}
	}

	/**
	 * Ярлык типа записи или таксономии.
	 *
	 * @param string $type Тип записи или таксономия из DEFAULTS.
	 */
	public static function slug( string $type ): string {
		$default = self::DEFAULTS[ $type ] ?? $type;
		// Так Secure Custom Fields хранит поле страницы настроек.
		$value = self::normalize( (string) get_option( 'options_' . self::FIELD_PREFIX . $type, '' ) );

		return '' === $value ? $default : $value;
	}

	/**
	 * Приводит ярлык к виду для адреса: латиница, цифры, дефис, «/» между частями.
	 *
	 * @param string $value Введённое значение (« /Наши Проекты/ » → nashi-proekty).
	 */
	public static function normalize( string $value ): string {
		$value = strtolower( Translit::convert( trim( $value ) ) );
		$value = (string) preg_replace( '/[\s_]+/', '-', $value );
		$value = (string) preg_replace( '/[^a-z0-9\-\/]/', '', $value );
		$parts = array_filter(
			array_map(
				static fn( string $part ): string => trim( (string) preg_replace( '/-+/', '-', $part ), '-' ),
				explode( '/', $value )
			),
			'strlen'
		);
		return implode( '/', $parts );
	}

	/**
	 * Фильтр сохранения поля «Адреса»: в базу попадает уже приведённый ярлык.
	 *
	 * @param mixed $value Значение поля.
	 * @return mixed
	 */
	public static function normalize_field( $value ) {
		return is_string( $value ) ? self::normalize( $value ) : $value;
	}

	/**
	 * Рубрики и теги блога: /blog/rubrika/<рубрика>/, /blog/teg/<тег>/.
	 *
	 * Префиксы рубрик и меток из «Настройки → Постоянные ссылки» не используются:
	 * все адреса задаются в одном месте — «Настройки сайта → Адреса».
	 *
	 * @param array  $args     Параметры таксономии.
	 * @param string $taxonomy Таксономия.
	 */
	public static function builtin_taxonomy_args( array $args, string $taxonomy ): array {
		if ( in_array( $taxonomy, array( 'category', 'post_tag' ), true ) && ! empty( $args['rewrite'] ) ) {
			$args['rewrite'] = array_merge(
				(array) $args['rewrite'],
				array(
					'slug'       => self::slug( $taxonomy ),
					'with_front' => false,
				)
			);
		}
		return $args;
	}

	/**
	 * Постраничный вывод на страницах-списках: /portfolio/page/2/ — вторая страница списка,
	 * а не кейс с ярлыком «page».
	 */
	public static function list_page_rules(): void {
		foreach ( self::LIST_PAGES as $type ) {
			$slug = self::slug( $type );
			add_rewrite_rule( '^' . $slug . '/page/?([0-9]{1,})/?$', 'index.php?pagename=' . $slug . '&paged=$matches[1]', 'top' );
		}
	}

	/**
	 * Отпечаток всего, от чего зависят правила перезаписи темы.
	 */
	private static function signature(): string {
		$config = array();

		foreach ( get_post_types( array( '_builtin' => false ), 'objects' ) as $name => $type ) {
			$config[ 'post_type:' . $name ] = $type->rewrite;
		}
		foreach ( get_taxonomies( array(), 'objects' ) as $name => $taxonomy ) {
			$config[ 'taxonomy:' . $name ] = $taxonomy->rewrite;
		}
		foreach ( self::LIST_PAGES as $type ) {
			$config[ 'list:' . $type ] = self::slug( $type );
		}
		ksort( $config );

		return md5( (string) wp_json_encode( $config ) );
	}

	/**
	 * Сбрасывает правила перезаписи, если изменились ярлыки или типы записей.
	 * Срабатывает один раз после изменения: дальше отпечаток совпадает.
	 */
	public static function maybe_flush(): void {
		$signature = self::signature();

		if ( get_option( self::SIGNATURE_OPTION ) !== $signature ) {
			flush_rewrite_rules( false );
			update_option( self::SIGNATURE_OPTION, $signature, true );
		}
	}

	/**
	 * При смене темы правила пересоберутся без типов записей Trust.
	 */
	public static function forget_rules(): void {
		delete_option( 'rewrite_rules' );
		delete_option( self::SIGNATURE_OPTION );
	}

	/**
	 * Подсказка на странице «Настройки → Постоянные ссылки».
	 */
	public static function permalink_notice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'options-permalink' !== $screen->id ) {
			return;
		}

		printf(
			'<div class="notice notice-info"><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'Тема Trust: адреса рубрик и тегов блога, кейсов, сотрудников и вопросов задаются в «Настройки сайта → Адреса». Префиксы рубрик и меток на этой странице тема не использует.', 'trust' ),
			esc_url( Settings::admin_url() ),
			esc_html__( 'Открыть настройки', 'trust' )
		);
	}
}
