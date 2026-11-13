<?php
/**
 * Иконки первого экрана услуги (правка заказчика): рисунок справа от длинного вводного текста,
 * над кнопками. Файлы — assets/art/hero/<имя>.svg (240×200, цвета сайта).
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Список иконок, поле выбора с превью и вывод.
 */
final class Hero_Art {

	/**
	 * Иконки: имя файла => название в списке.
	 */
	public const LIST = array(
		'analytics' => 'Аналитика',
		'web'       => 'Создание сайтов',
		'code'      => 'Программирование',
		'bugfix'    => 'Исправление ошибок',
		'seo'       => 'SEO',
		'geo'       => 'GEO',
		'ads'       => 'Реклама',
		'ai'        => 'AI',
		'blog'      => 'Блог',
		'article'   => 'Авторская статья',
		'company'   => 'Компания',
		'team'      => 'Команда',
		'robot'     => 'Поисковый робот',
	);

	/**
	 * С какой длины вводного текста (в символах) показывается иконка: короче — рядом с текстом
	 * нет места (решение заказчика).
	 */
	public const MIN_LEAD = 380;

	/**
	 * Регистрирует хуки: превью иконок в поле выбора (после скрипта списка иконок).
	 */
	public static function register(): void {
		add_action( 'acf/input/admin_enqueue_scripts', array( self::class, 'admin_assets' ), 20 );
	}

	/**
	 * Разметка иконок для списка в редакторе: window.trustArts (имя => SVG).
	 */
	public static function admin_assets(): void {
		$arts = array();
		foreach ( array_keys( self::LIST ) as $name ) {
			$arts[ $name ] = self::svg( $name );
		}
		wp_add_inline_script( 'trust-icon-select', 'window.trustArts = ' . wp_json_encode( $arts, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . ';', 'before' );
	}

	/**
	 * SVG иконки или '' (нет такой).
	 *
	 * @param string $name Имя.
	 */
	public static function svg( string $name ): string {
		if ( ! isset( self::LIST[ $name ] ) ) {
			return '';
		}
		$file = TRUST_DIR . '/assets/art/hero/' . $name . '.svg';
		return is_readable( $file ) ? trim( (string) file_get_contents( $file ) ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- файл темы.
	}

	/**
	 * Поле SCF «Иконка справа» — список с превью рисунков (assets/js/icon-select.js).
	 */
	public static function field(): array {
		return array(
			'name'          => 'art',
			'label'         => __( 'Иконка справа', 'trust' ),
			'type'          => 'select',
			'ui'            => 1,
			'allow_null'    => 0,
			'choices'       => array( 'none' => __( 'Без иконки', 'trust' ) ) + self::LIST,
			'default_value' => 'none',
			'instructions'  => sprintf(
				/* translators: %d: число символов. */
				__( 'Рисунок справа над кнопками. Появляется, если во «Вводном тексте» от %d символов; на телефонах и планшетах скрыт.', 'trust' ),
				self::MIN_LEAD
			),
			'wrapper'       => array( 'class' => 'trust-icon-select trust-art-select' ),
		);
	}

	/**
	 * Показывать ли иконку при таком вводном тексте.
	 *
	 * @param string $name Имя иконки.
	 * @param string $lead Вводный текст.
	 */
	public static function shown( string $name, string $lead ): bool {
		return '' !== self::svg( $name ) && mb_strlen( $lead ) >= self::MIN_LEAD;
	}
}
