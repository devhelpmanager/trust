<?php
/**
 * Блоки темы: регистрация, категории, поля, общие поля секции, данные для превью и демо.
 *
 * Каждый блок — папка blocks/<имя>/:
 *   block.json   — описание блока (ключ "acf" — блок Secure Custom Fields, renderTemplate: render.php);
 *   fields.php   — поля блока (массив без ключей, ключи ставит Trust\Fields);
 *   render.php   — вёрстка (переменные SCF: $block, $is_preview, $post_id);
 *   style.css    — стили блока (подключаются только на страницах с блоком);
 *   view.js      — скрипт блока на сайте (если нужен);
 *   example.php  — значения для превью в списке блоков.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Регистрирует блоки темы и помогает их вёрстке.
 */
final class Blocks {

	/**
	 * Категории блоков в редакторе (ТЗ, п. 8.1). Пустые категории редактор не показывает.
	 */
	private const CATEGORIES = array(
		'trust-heroes'   => 'Первые экраны',
		'trust-services' => 'Услуги',
		'trust-company'  => 'Компания',
		'trust-lists'    => 'Списки',
		'trust-forms'    => 'Формы',
		'trust-case'     => 'Кейс',
		'trust-content'  => 'Текст и медиа',
	);

	/**
	 * Поля блоков с ключами: имя блока => поля.
	 *
	 * @var array<string, array>
	 */
	private static array $fields = array();

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'block_categories_all', array( self::class, 'categories' ) );
		add_filter( 'block_type_metadata', array( self::class, 'metadata' ) );
		add_action( 'init', array( self::class, 'register_blocks' ) );
		add_action( 'acf/init', array( self::class, 'register_fields' ) );
	}

	/**
	 * Имена блоков темы — папки blocks/<имя>/ с block.json.
	 *
	 * @return string[]
	 */
	public static function names(): array {
		$names = array();
		foreach ( glob( TRUST_DIR . '/blocks/*/block.json' ) ? glob( TRUST_DIR . '/blocks/*/block.json' ) : array() as $file ) {
			$names[] = basename( dirname( $file ) );
		}
		return $names;
	}

	/**
	 * Регистрирует блоки. Без Secure Custom Fields блоки не регистрируются (ТЗ, п. 4.1).
	 */
	public static function register_blocks(): void {
		if ( ! Dependencies::has_scf() ) {
			return;
		}
		foreach ( self::names() as $name ) {
			register_block_type( TRUST_DIR . '/blocks/' . $name );
		}
	}

	/**
	 * Категории темы — первыми в списке блоков.
	 *
	 * @param array $categories Категории редактора.
	 * @return array
	 */
	public static function categories( array $categories ): array {
		$ours = array();
		foreach ( self::CATEGORIES as $slug => $title ) {
			$ours[] = array(
				'slug'  => $slug,
				'title' => $title,
				'icon'  => null,
			);
		}
		return array_merge( $ours, $categories );
	}

	/**
	 * Описание блока темы: версия файлов (сброс кеша браузера и CDN после обновления)
	 * и пример для превью из example.php.
	 *
	 * @param array $metadata Данные block.json.
	 * @return array
	 */
	public static function metadata( array $metadata ): array {
		if ( empty( $metadata['name'] ) || ! str_starts_with( $metadata['name'], 'trust/' ) || empty( $metadata['file'] ) ) {
			return $metadata;
		}

		$dir   = dirname( $metadata['file'] );
		$times = array();
		foreach ( glob( $dir . '/*.{css,js,json}', GLOB_BRACE ) ? glob( $dir . '/*.{css,js,json}', GLOB_BRACE ) : array() as $file ) {
			$times[] = (int) filemtime( $file );
		}
		$metadata['version'] = $times ? (string) max( $times ) : TRUST_VERSION;

		if ( is_readable( $dir . '/example.php' ) ) {
			$metadata['example'] = array(
				'attributes' => array(
					'mode' => 'preview',
					'data' => self::flatten( self::fields( basename( $dir ) ), require $dir . '/example.php' ),
				),
			);
		}

		return $metadata;
	}

	/**
	 * Поля блока с ключами (field_trust_block_<блок>_<поле>).
	 *
	 * @param string $name Имя блока (папка).
	 */
	public static function fields( string $name ): array {
		if ( ! isset( self::$fields[ $name ] ) ) {
			$file                  = TRUST_DIR . '/blocks/' . $name . '/fields.php';
			self::$fields[ $name ] = is_readable( $file )
				? Fields::with_keys( require $file, 'block_' . str_replace( '-', '_', $name ) )
				: array();
		}
		return self::$fields[ $name ];
	}

	/**
	 * Группы полей блоков: по одной на блок, показываются только в этом блоке.
	 */
	public static function register_fields(): void {
		foreach ( self::names() as $name ) {
			$fields = self::fields( $name );
			if ( ! $fields ) {
				continue;
			}
			$json = wp_json_file_decode( TRUST_DIR . '/blocks/' . $name . '/block.json', array( 'associative' => true ) );
			acf_add_local_field_group(
				array(
					'key'                   => 'group_trust_block_' . str_replace( '-', '_', $name ),
					'title'                 => $json['title'] ?? $name,
					'fields'                => $fields,
					'location'              => array(
						array(
							array(
								'param'    => 'block',
								'operator' => '==',
								'value'    => 'trust/' . $name,
							),
						),
					),
					'instruction_placement' => 'field',
				)
			);
		}
	}

	/**
	 * Общие поля секции (ТЗ, п. 8.1): якорь, надзаголовок, заголовок H2, вводный текст.
	 * Значения по умолчанию — из макета блока.
	 *
	 * @param array $defaults Значения по умолчанию: anchor, eyebrow, title, lead (ключ false — поля нет).
	 * @return array
	 */
	public static function section_fields( array $defaults ): array {
		$fields = array(
			array(
				'name'         => 'anchor',
				'label'        => __( 'Якорь', 'trust' ),
				'type'         => 'text',
				'placeholder'  => $defaults['anchor'] ?? '',
				'instructions' => __( 'Необязательно. Латиница, цифры и дефис, без #. Ссылка на блок: адрес страницы#якорь.', 'trust' ),
				'wrapper'      => array( 'width' => 50 ),
			),
			array(
				'name'          => 'eyebrow',
				'label'         => __( 'Надзаголовок', 'trust' ),
				'type'          => 'text',
				'default_value' => $defaults['eyebrow'] ?? '',
				'maxlength'     => 60,
				'instructions'  => __( 'Короткая строка над заголовком, до 30 символов.', 'trust' ),
				'wrapper'       => array( 'width' => 50 ),
			),
			array(
				'name'          => 'title',
				'label'         => __( 'Заголовок (H2)', 'trust' ),
				'type'          => 'text',
				'required'      => 1,
				'default_value' => $defaults['title'] ?? '',
				'maxlength'     => 120,
				'instructions'  => __( 'Заголовок секции, до 60 символов.', 'trust' ),
			),
			array(
				'name'          => 'lead',
				'label'         => __( 'Вводный текст', 'trust' ),
				'type'          => 'textarea',
				'rows'          => 2,
				'new_lines'     => '',
				'default_value' => $defaults['lead'] ?? '',
				'maxlength'     => 400,
				'instructions'  => __( 'Необязательно. 1–2 предложения под заголовком.', 'trust' ),
			),
		);

		return array_values(
			array_filter(
				$fields,
				static fn( $field ) => ! array_key_exists( $field['name'], $defaults ) || false !== $defaults[ $field['name'] ]
			)
		);
	}

	/**
	 * Поле «Кнопки» (до $max штук): текст и адрес — поле «Ссылка» WordPress; адрес может быть
	 * якорем (#раздел), #callback-modal открывает окно «Заказать обратный звонок».
	 * Первая кнопка — основная, остальные — второстепенные.
	 *
	 * @param int  $max  Сколько кнопок можно добавить.
	 * @param bool $icon Есть ли выбор иконки (стрелка вправо или вниз).
	 */
	public static function buttons_field( int $max = 2, bool $icon = false ): array {
		$sub = array(
			array(
				'name'          => 'link',
				'label'         => __( 'Текст и адрес', 'trust' ),
				'type'          => 'link',
				'return_format' => 'array',
				'required'      => 1,
			),
		);
		if ( $icon ) {
			$sub[] = array(
				'name'          => 'icon',
				'label'         => __( 'Иконка', 'trust' ),
				'type'          => 'select',
				'choices'       => array(
					''      => __( 'Без иконки', 'trust' ),
					'down'  => __( 'Стрелка вниз — к разделу этой страницы', 'trust' ),
					'right' => __( 'Стрелка вправо', 'trust' ),
				),
				'default_value' => '',
				'wrapper'       => array( 'width' => 40 ),
			);
		}
		return array(
			'name'         => 'buttons',
			'label'        => __( 'Кнопки', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'max'          => $max,
			'button_label' => __( 'Добавить кнопку', 'trust' ),
			'instructions' => __( 'Необязательно. Первая — основная, вторая — контурная. Адрес — страница, якорь раздела (#vacancies) или #callback-modal — окно «Заказать обратный звонок».', 'trust' ),
			'sub_fields'   => $sub,
		);
	}

	/**
	 * Кнопки из поля buttons_field(): <a class="btn …">.
	 *
	 * @param mixed    $rows    Значение поля.
	 * @param string[] $classes Классы кнопок по порядку (последний — для всех следующих).
	 */
	public static function buttons( $rows, array $classes ): string {
		$html = '';
		foreach ( array_values( is_array( $rows ) ? $rows : array() ) as $i => $row ) {
			$link = $row['link'] ?? null;
			if ( ! is_array( $link ) || empty( $link['url'] ) || '' === trim( (string) ( $link['title'] ?? '' ) ) ) {
				continue;
			}
			$class = $classes[ min( $i, count( $classes ) - 1 ) ];
			$icon  = array(
				'down'  => 'arrow-down',
				'right' => 'arrow-right',
			)[ $row['icon'] ?? '' ] ?? '';
			$html .= sprintf(
				'<a class="%s" href="%s"%s>%s%s</a>',
				esc_attr( $class ),
				esc_url( $link['url'] ),
				! empty( $link['target'] ) ? ' target="_blank" rel="noopener"' : '',
				esc_html( $link['title'] ),
				$icon ? Icons::svg(
					$icon,
					array(
						'size'         => 18,
						'stroke_width' => 2.2,
					)
				) : ''
			);
		}
		return $html;
	}

	/**
	 * Значение поля «Якорь», пригодное для id (или пустая строка).
	 *
	 * @param mixed $value Значение поля.
	 */
	public static function anchor( $value ): string {
		return sanitize_title( (string) $value );
	}

	/**
	 * Уникальный id элемента внутри блока (для aria-labelledby и aria-controls).
	 *
	 * @param array  $block  Блок SCF.
	 * @param string $suffix Окончание.
	 */
	public static function uid( array $block, string $suffix ): string {
		$id = (string) ( $block['id'] ?? ( $block['anchor'] ?? '' ) );
		if ( '' === $id ) {
			$id = 'b' . wp_unique_id();
		}
		return sanitize_html_class( $id ) . '-' . $suffix;
	}

	/**
	 * Значения полей в виде данных блока SCF: имя => значение и _имя => ключ поля,
	 * повторители — имя_0_подполе. Для превью (example.php) и демо-контента.
	 *
	 * @param array  $fields Поля с ключами.
	 * @param array  $values Значения: имя => значение, повторитель — список строк.
	 * @param string $prefix Префикс имён (для вложенных полей).
	 * @return array
	 */
	public static function flatten( array $fields, array $values, string $prefix = '' ): array {
		$data = array();

		foreach ( $fields as $field ) {
			$name = $field['name'] ?? '';
			if ( '' === $name || ! array_key_exists( $name, $values ) ) {
				continue;
			}
			$full  = $prefix . $name;
			$value = $values[ $name ];

			if ( 'repeater' === $field['type'] ) {
				$rows                = array_values( (array) $value );
				$data[ $full ]       = count( $rows );
				$data[ '_' . $full ] = $field['key'];
				foreach ( $rows as $i => $row ) {
					$data += self::flatten( $field['sub_fields'], (array) $row, $full . '_' . $i . '_' );
				}
			} elseif ( 'group' === $field['type'] ) {
				$data[ $full ]       = '';
				$data[ '_' . $full ] = $field['key'];
				$data               += self::flatten( $field['sub_fields'], (array) $value, $full . '_' );
			} else {
				$data[ $full ]       = $value;
				$data[ '_' . $full ] = $field['key'];
			}
		}

		return $data;
	}

	/**
	 * Разметка блока для содержимого записи: <!-- wp:trust/имя {…} /-->.
	 *
	 * @param string $name   Имя блока (папка).
	 * @param array  $values Значения полей.
	 */
	public static function serialize( string $name, array $values ): string {
		return serialize_block(
			array(
				'blockName'    => 'trust/' . $name,
				'attrs'        => array(
					'name' => 'trust/' . $name,
					'data' => self::flatten( self::fields( $name ), $values ),
					'mode' => 'preview',
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}
}
