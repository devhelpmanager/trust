<?php
/**
 * Иконки темы из SVG-спрайта assets/icons/sprite.svg.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Выводит иконку из спрайта встроенным <svg> (без отдельного запроса к файлу).
 */
final class Icons {

	/**
	 * Разобранный спрайт: id => array( viewBox, внутренняя разметка, залитая ли иконка ).
	 *
	 * @var array<string, array{0: string, 1: string, 2: bool}>|null
	 */
	private static ?array $symbols = null;

	/**
	 * SVG-код иконки.
	 *
	 * @param string $id   Имя иконки (id символа в спрайте).
	 * @param array  $args Атрибуты: size (px, по умолчанию 24), stroke (цвет, по умолчанию currentColor),
	 *                     stroke_width (2), class, label (текст для скринридеров; без него иконка скрыта).
	 */
	public static function svg( string $id, array $args = array() ): string {
		$symbols = self::symbols();
		if ( ! isset( $symbols[ $id ] ) ) {
			return '';
		}

		$size  = (int) ( $args['size'] ?? 24 );
		$attrs = array(
			'class'   => $args['class'] ?? '',
			'width'   => $size,
			'height'  => $size,
			'viewBox' => $symbols[ $id ][0],
		);

		if ( $symbols[ $id ][2] ) {
			// Залитая иконка (значки соцсетей).
			$attrs['fill'] = $args['fill'] ?? 'currentColor';
		} else {
			// Линейная иконка из макетов.
			$attrs += array(
				'fill'            => 'none',
				'stroke'          => $args['stroke'] ?? 'currentColor',
				'stroke-width'    => $args['stroke_width'] ?? 2,
				'stroke-linecap'  => 'round',
				'stroke-linejoin' => 'round',
			);
		}
		$attrs['focusable'] = 'false';

		if ( ! empty( $args['label'] ) ) {
			$attrs['role']       = 'img';
			$attrs['aria-label'] = $args['label'];
		} else {
			$attrs['aria-hidden'] = 'true';
		}

		$html = '<svg';
		foreach ( $attrs as $name => $value ) {
			if ( '' !== (string) $value ) {
				$html .= ' ' . $name . '="' . esc_attr( (string) $value ) . '"';
			}
		}
		return $html . '>' . $symbols[ $id ][1] . '</svg>';
	}

	/**
	 * Выводит иконку (см. svg()).
	 *
	 * @param string $id   Имя иконки.
	 * @param array  $args Атрибуты.
	 */
	public static function render( string $id, array $args = array() ): void {
		echo self::svg( $id, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG из спрайта темы, атрибуты экранированы в svg().
	}

	/**
	 * Поле SCF «Иконка» — список с превью значков (ТЗ, п. 8.1): select с поиском, у каждого
	 * варианта и у выбранного значения — сама иконка (assets/js/icon-select.js).
	 *
	 * @param array $field Поле: name, label, choices (имя иконки => подпись), default_value, wrapper и др.
	 */
	public static function field( array $field ): array {
		$wrapper          = $field['wrapper'] ?? array();
		$wrapper['class'] = trim( ( $wrapper['class'] ?? '' ) . ' trust-icon-select' );
		return array_merge(
			array(
				'name'  => 'icon',
				'label' => __( 'Иконка', 'trust' ),
				'type'  => 'select',
				'ui'    => 1,
			),
			$field,
			array( 'wrapper' => $wrapper )
		);
	}

	/**
	 * Регистрирует хуки: скрипт превью иконок в полях SCF (в редакторе блоков и админке).
	 */
	public static function register(): void {
		add_action( 'acf/input/admin_enqueue_scripts', array( self::class, 'admin_assets' ) );
	}

	/**
	 * Скрипт и стили превью иконок; разметка иконок — из спрайта темы.
	 */
	public static function admin_assets(): void {
		wp_enqueue_style( 'trust-icon-select', Assets::url( 'assets/css/icon-select.css' ), array(), Assets::version( 'assets/css/icon-select.css' ) );
		wp_enqueue_script( 'trust-icon-select', Assets::url( 'assets/js/icon-select.js' ), array( 'acf-input' ), Assets::version( 'assets/js/icon-select.js' ), true );
		$icons = array();
		foreach ( array_keys( self::symbols() ) as $id ) {
			$icons[ $id ] = self::svg(
				$id,
				array(
					'size'         => 20,
					'stroke_width' => 1.8,
				)
			);
		}
		wp_add_inline_script( 'trust-icon-select', 'window.trustIcons = ' . wp_json_encode( $icons, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . ';', 'before' );
	}

	/**
	 * Есть ли иконка в спрайте.
	 *
	 * @param string $id Имя иконки.
	 */
	public static function exists( string $id ): bool {
		return isset( self::symbols()[ $id ] );
	}

	/**
	 * Читает спрайт один раз за запрос.
	 *
	 * @return array<string, array{0: string, 1: string, 2: bool}>
	 */
	private static function symbols(): array {
		if ( null !== self::$symbols ) {
			return self::$symbols;
		}

		self::$symbols = array();
		$file          = TRUST_DIR . '/assets/icons/sprite.svg';
		$svg           = is_readable( $file ) ? file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- локальный файл темы.

		if ( preg_match_all( '~<symbol\s+id="([a-z0-9-]+)"\s+viewBox="([^"]+)"(\s+data-style="fill")?>(.*?)</symbol>~s', (string) $svg, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				self::$symbols[ $m[1] ] = array( $m[2], trim( $m[4] ), '' !== $m[3] );
			}
		}
		return self::$symbols;
	}
}
