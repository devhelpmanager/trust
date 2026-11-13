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
	 * Блоки ядра внутри «Блока с текстом» (ТЗ, п. 8.1): абзац, заголовок H2–H4, список, цитата,
	 * картинка, таблица. Видео — отдельным блоком темы (шаг 4.9).
	 */
	public const TEXT_BLOCKS = array( 'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/quote', 'core/image', 'core/table' );

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
		add_action( 'init', array( self::class, 'register_styles' ) );
		add_filter( 'render_block_core/quote', array( self::class, 'quote_important' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( self::class, 'editor_assets' ) );
	}

	/**
	 * Стили цитаты из макетов (ТЗ, п. 8.3, № 3): «С автором» — лавандовая плашка со знаком
	 * цитаты, текстом и подписью (имя первой строкой, ниже должность); «Важно» — крупный текст
	 * между двумя линиями.
	 */
	public static function register_styles(): void {
		register_block_style(
			'core/quote',
			array(
				'name'  => 'author',
				'label' => __( 'С автором', 'trust' ),
			)
		);
		register_block_style(
			'core/quote',
			array(
				'name'  => 'important',
				'label' => __( 'Важно', 'trust' ),
			)
		);
	}

	/**
	 * Цитата «Важно» — не цитата, а выделенная мысль: на сайте выводится боковой плашкой
	 * <aside> с подписью «Важно» (как в макете), а не <blockquote>.
	 *
	 * @param string $html  Разметка блока.
	 * @param array  $block Блок.
	 */
	public static function quote_important( string $html, array $block ): string {
		$class = (string) ( $block['attrs']['className'] ?? '' );
		if ( ! preg_match( '/(^|\s)is-style-important(\s|$)/', $class ) ) {
			return $html;
		}
		$html = (string) preg_replace( '~<blockquote\b([^>]*)>~', '<aside$1><span class="prose__label">' . esc_html__( 'Важно', 'trust' ) . '</span>', $html, 1 );
		$html = (string) preg_replace( '~<cite>.*?</cite>~s', '', $html );
		return (string) preg_replace( '~</blockquote>\s*$~', '</aside>', $html );
	}

	/**
	 * Скрипт редактора: уровни заголовков H2–H4 (H1 — первый экран страницы, ТЗ п. 8.1).
	 */
	public static function editor_assets(): void {
		wp_enqueue_script( 'trust-editor-blocks', Assets::url( 'assets/js/editor-blocks.js' ), array( 'wp-hooks', 'wp-blocks' ), Assets::version( 'assets/js/editor-blocks.js' ), false );
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
		// Общие стили тёмной карточки первых экранов: блоки указывают "trust-hero" в block.json.
		wp_register_style( 'trust-hero', Assets::url( 'assets/css/hero.css' ), array(), Assets::version( 'assets/css/hero.css' ) );
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
			$values = require $dir . '/example.php';
			// Вложенные блоки превью (блоки с InnerBlocks): 'inner_blocks' => array( array( имя, атрибуты ) ).
			$inner = $values['inner_blocks'] ?? array();
			unset( $values['inner_blocks'] );
			$metadata['example'] = array(
				'attributes' => array(
					'mode' => 'preview',
					'data' => self::flatten( self::fields( basename( $dir ) ), $values ),
				),
			);
			if ( $inner ) {
				$metadata['example']['innerBlocks'] = array_map(
					static fn( array $item ): array => array(
						'name'       => $item[0],
						'attributes' => $item[1] ?? array(),
					),
					$inner
				);
			}
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
	 * Поле «Картинка справа от заголовка» (правка заказчика): рисунок по теме блока справа от
	 * надзаголовка, заголовка и вводного текста — файл head-art.svg в папке блока.
	 */
	public static function head_art_field(): array {
		return array(
			'name'          => 'head_art',
			'label'         => __( 'Картинка справа от заголовка', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'instructions'  => __( 'Рисунок по теме блока. Показывается на экранах шире 900 px, на телефоне скрыт.', 'trust' ),
		);
	}

	/**
	 * Рисунок справа от шапки секции (см. head_art_field()) — украшение, скрыт от скринридеров.
	 *
	 * @param string $name Имя блока (папка с head-art.svg).
	 */
	public static function head_art( string $name ): string {
		$on = get_field( 'head_art' );
		if ( null !== $on && ! $on ) {
			return '';
		}
		$file = TRUST_DIR . '/blocks/' . $name . '/head-art.svg';
		if ( ! is_readable( $file ) ) {
			return '';
		}
		return '<div class="section-art" aria-hidden="true">' . file_get_contents( $file ) . '</div>'; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- файл темы.
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
	 * Поля «Alt» и «Декоративная картинка» к полю картинки блока (ТЗ, п. 8.1).
	 *
	 * @param array $conditional Условие показа (conditional_logic) — как у поля картинки.
	 */
	public static function image_alt_fields( array $conditional = array() ): array {
		$fields = array(
			array(
				'name'         => 'alt',
				'label'        => __( 'Alt', 'trust' ),
				'type'         => 'text',
				'maxlength'    => 200,
				'instructions' => __( 'Что изображено, 5–15 слов. Пусто — описание из медиатеки.', 'trust' ),
			),
			array(
				'name'         => 'decorative',
				'label'        => __( 'Декоративная картинка', 'trust' ),
				'type'         => 'true_false',
				'ui'           => 1,
				'instructions' => __( 'Только украшение — скринридеры её пропустят (alt="").', 'trust' ),
			),
		);
		if ( $conditional ) {
			foreach ( $fields as $i => $field ) {
				$fields[ $i ]['conditional_logic'] = $conditional;
			}
		}
		return $fields;
	}

	/**
	 * Картинка блока через wp_get_attachment_image() (srcset, sizes): alt — из поля блока,
	 * иначе из медиатеки; у декоративной — alt="".
	 *
	 * @param int    $id         ID картинки.
	 * @param string $size       Размер.
	 * @param array  $attrs      Атрибуты <img>.
	 * @param string $alt        Поле «Alt» блока.
	 * @param bool   $decorative Флажок «Декоративная картинка».
	 */
	public static function image( int $id, string $size, array $attrs = array(), string $alt = '', bool $decorative = false ): string {
		if ( ! $id ) {
			return '';
		}
		if ( $decorative ) {
			$attrs['alt'] = '';
		} elseif ( '' !== trim( $alt ) ) {
			$attrs['alt'] = trim( $alt );
		}
		return (string) wp_get_attachment_image( $id, $size, false, $attrs );
	}

	/**
	 * Нет описания у содержательной картинки: ни в поле «Alt» блока, ни в медиатеке.
	 *
	 * @param int    $id         ID картинки.
	 * @param string $alt        Поле «Alt» блока.
	 * @param bool   $decorative Флажок «Декоративная картинка».
	 */
	public static function image_alt_missing( int $id, string $alt = '', bool $decorative = false ): bool {
		return $id && ! $decorative && '' === trim( $alt ) && '' === trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	}

	/**
	 * Номер рисунка блока «Картинка» — порядковый среди картинок страницы с подписью «Рис.»
	 * (без тех, у кого в поле «Номер рисунка» стоит 0). Блок находится в содержимом записи
	 * по данным полей; одинаковые блоки различаются по порядку вывода (в редакторе у них номер
	 * первого). В редакторе после правки полей блока (данные ещё не сохранены) — по картинке,
	 * иначе номер за последним; после сохранения номер уточняется.
	 *
	 * @param array      $block   Блок SCF.
	 * @param int|string $post_id ID записи.
	 */
	public static function figure_number( array $block, $post_id ): int {
		static $figures = array();
		static $used    = array();

		$post_id = (int) $post_id;
		if ( ! isset( $figures[ $post_id ] ) ) {
			$figures[ $post_id ] = array();
			$used[ $post_id ]    = array();
			if ( $post_id ) {
				self::collect_figures( parse_blocks( (string) get_post_field( 'post_content', $post_id ) ), $figures[ $post_id ] );
			}
		}
		$list = $figures[ $post_id ];
		$data = self::figure_data( (array) ( $block['data'] ?? array() ) );

		// Тот же блок, ещё не выведенный на этой странице; все выведены — содержимое выводится заново.
		if ( count( $used[ $post_id ] ) >= count( $list ) ) {
			$used[ $post_id ] = array();
		}
		$by_image = 0;
		foreach ( $list as $i => $figure ) {
			if ( isset( $used[ $post_id ][ $i ] ) ) {
				continue;
			}
			if ( $figure['data'] == $data ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- данные из редактора и из записи: числа могут прийти строками.
				$used[ $post_id ][ $i ] = true;
				return $figure['number'];
			}
			if ( ! $by_image && ! empty( $data['image'] ) && (int) ( $figure['data']['image'] ?? 0 ) === (int) $data['image'] ) {
				$by_image = $figure['number'];
			}
		}
		if ( $by_image ) {
			return $by_image;
		}
		$numbered = array_filter( wp_list_pluck( $list, 'number' ) );
		return count( $numbered ) + 1;
	}

	/**
	 * Данные полей блока «Картинка» в виде имя => значение: SCF хранит их либо так (с парами
	 * _имя => ключ поля), либо по ключам полей (field_trust_block_image_имя).
	 *
	 * @param array $data Данные блока.
	 */
	private static function figure_data( array $data ): array {
		$prefix = Fields::key( 'block_image', '' );
		$values = array();
		foreach ( $data as $key => $value ) {
			$key = (string) $key;
			if ( str_starts_with( $key, '_' ) ) {
				continue;
			}
			$values[ str_starts_with( $key, $prefix ) ? substr( $key, strlen( $prefix ) ) : $key ] = $value;
		}
		ksort( $values );
		return $values;
	}

	/**
	 * Блоки «Картинка» в порядке документа (с вложенными): данные полей и номер рисунка (0 — без «Рис.»).
	 *
	 * @param array $blocks  Разобранные блоки (parse_blocks()).
	 * @param array $figures Список, в который добавляются картинки.
	 */
	private static function collect_figures( array $blocks, array &$figures ): void {
		foreach ( $blocks as $parsed ) {
			if ( 'trust/image' === ( $parsed['blockName'] ?? '' ) ) {
				$data      = self::figure_data( (array) ( $parsed['attrs']['data'] ?? array() ) );
				$numbered  = '0' !== trim( (string) ( $data['number'] ?? '' ) );
				$count     = count( array_filter( wp_list_pluck( $figures, 'number' ) ) );
				$figures[] = array(
					'data'   => $data,
					'number' => $numbered ? $count + 1 : 0,
				);
			}
			if ( ! empty( $parsed['innerBlocks'] ) ) {
				self::collect_figures( $parsed['innerBlocks'], $figures );
			}
		}
	}

	/**
	 * Предупреждение в превью блока в редакторе (на сайте не выводится).
	 *
	 * @param string $text Текст.
	 */
	public static function editor_warning( string $text ): string {
		return '<p class="trust-block-warning" role="alert">' . esc_html( $text ) . '</p>';
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
	 * Разметка блока для содержимого записи: <!-- wp:trust/имя {…} /-->, с вложенными блоками
	 * (блоки с InnerBlocks, например «Блок с текстом») — <!-- wp:trust/имя {…} -->…<!-- /wp:trust/имя -->.
	 *
	 * @param string $name   Имя блока (папка).
	 * @param array  $values Значения полей.
	 * @param string $inner  Разметка вложенных блоков ядра или ''.
	 */
	public static function serialize( string $name, array $values, string $inner = '' ): string {
		$inner_blocks = array_values(
			array_filter(
				'' === $inner ? array() : parse_blocks( $inner ),
				static fn( array $parsed ): bool => null !== $parsed['blockName']
			)
		);
		$content      = array();
		foreach ( $inner_blocks as $i => $parsed ) {
			if ( $i ) {
				$content[] = "\n\n";
			}
			$content[] = null;
		}
		return serialize_block(
			array(
				'blockName'    => 'trust/' . $name,
				'attrs'        => array(
					'name' => 'trust/' . $name,
					'data' => self::flatten( self::fields( $name ), $values ),
					'mode' => 'preview',
				),
				'innerBlocks'  => $inner_blocks,
				'innerHTML'    => '',
				'innerContent' => $content,
			)
		);
	}
}
