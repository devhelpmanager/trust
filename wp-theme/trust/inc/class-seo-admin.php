<?php
/**
 * SEO в админке: счётчики и предпросмотр сниппета, напоминание об alt картинок.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Подключает assets/js/seo-fields.js на экранах с группой «SEO» и следит за alt в медиатеке
 * (ТЗ, раздел 10.1: «Альтернативный текст» обязателен — подсказка и предупреждение).
 */
final class Seo_Admin {

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'acf/input/admin_enqueue_scripts', array( self::class, 'assets' ) );
		add_filter( 'manage_media_columns', array( self::class, 'media_columns' ) );
		add_action( 'manage_media_custom_column', array( self::class, 'media_column' ), 10, 2 );
		add_filter( 'attachment_fields_to_edit', array( self::class, 'alt_warning' ), 10, 2 );
		add_action( 'admin_head', array( self::class, 'alt_styles' ) );
	}

	/**
	 * Запись или термин, который сейчас редактируется; null — экран без группы «SEO».
	 *
	 * @return \WP_Post|\WP_Term|string|null Строка — таксономия формы «Добавить термин».
	 */
	private static function edited_object() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return null;
		}
		if ( 'post' === $screen->base && in_array( $screen->post_type, Seo::POST_TYPES, true ) ) {
			return get_post();
		}
		if ( in_array( $screen->taxonomy, Seo::TAXONOMIES, true ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- только чтение ID термина из адреса экрана.
			$term = isset( $_GET['tag_ID'] ) ? get_term( absint( $_GET['tag_ID'] ), $screen->taxonomy ) : null;
			return $term instanceof \WP_Term ? $term : $screen->taxonomy;
		}
		return null;
	}

	/**
	 * Скрипт счётчиков и предпросмотра с данными экрана.
	 */
	public static function assets(): void {
		$object = self::edited_object();
		if ( null === $object ) {
			return;
		}

		$type     = $object instanceof \WP_Post ? $object->post_type : ( $object instanceof \WP_Term ? $object->taxonomy : $object );
		$url      = '';
		$fallback = '';
		if ( $object instanceof \WP_Post ) {
			$url      = 'publish' === $object->post_status ? (string) get_permalink( $object ) : '';
			$fallback = Seo::fallback_description( $object );
		} elseif ( $object instanceof \WP_Term ) {
			$link     = get_term_link( $object );
			$url      = is_wp_error( $link ) ? '' : $link;
			$fallback = Seo::fallback_description( $object );
		}

		wp_enqueue_script( 'trust-seo-fields', TRUST_URI . '/assets/js/seo-fields.js', array( 'jquery', 'acf-input' ), (string) filemtime( TRUST_DIR . '/assets/js/seo-fields.js' ), true );
		wp_enqueue_style( 'trust-seo-fields', TRUST_URI . '/assets/css/seo-fields.css', array(), (string) filemtime( TRUST_DIR . '/assets/css/seo-fields.css' ) );
		wp_add_inline_script(
			'trust-seo-fields',
			'window.trustSeo = ' . wp_json_encode(
				array(
					'keys'        => array(
						'preview'     => Fields::key( 'seo', 'trust_seo_preview' ),
						'title'       => Fields::key( 'seo', 'trust_seo_title' ),
						'description' => Fields::key( 'seo', 'trust_seo_description' ),
					),
					'limits'      => array(
						'title'       => Seo::TITLE_LIMIT,
						'description' => Seo::DESCRIPTION_LIMIT,
					),
					'template'    => Seo::fill( Seo::template( $type ), array( 'title' => '%%TITLE%%' ) ),
					'site'        => Settings::brand(),
					'sep'         => Seo::separator(),
					'url'         => $url,
					'home'        => home_url( '/' ),
					'description' => $fallback,
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Колонка «Alt» в медиатеке (режим списка).
	 *
	 * @param array $columns Колонки.
	 */
	public static function media_columns( array $columns ): array {
		return Admin::insert_columns( $columns, 'title', array( 'trust_alt' => __( 'Alt', 'trust' ) ) );
	}

	/**
	 * Значение колонки «Alt»: текст или предупреждение у картинки без alt.
	 *
	 * @param string $column  Колонка.
	 * @param int    $post_id Файл.
	 */
	public static function media_column( string $column, int $post_id ): void {
		if ( 'trust_alt' !== $column || ! wp_attachment_is_image( $post_id ) ) {
			return;
		}
		$alt = trim( (string) get_post_meta( $post_id, '_wp_attachment_image_alt', true ) );
		if ( '' === $alt ) {
			printf( '<span class="trust-alt-missing">%s</span>', esc_html__( 'Нет — заполните', 'trust' ) );
		} else {
			echo esc_html( $alt );
		}
	}

	/**
	 * Под полями картинки — предупреждение, если не заполнен «Альтернативный текст».
	 *
	 * @param array    $fields Поля вложения.
	 * @param \WP_Post $post   Вложение.
	 */
	public static function alt_warning( $fields, $post ) {
		if ( ! is_array( $fields ) || ! $post instanceof \WP_Post || ! wp_attachment_is_image( $post ) ) {
			return $fields;
		}
		$alt = trim( (string) get_post_meta( $post->ID, '_wp_attachment_image_alt', true ) );

		$fields['trust_alt_hint'] = array(
			'label' => '',
			'input' => 'html',
			'html'  => '' === $alt
				? '<p class="trust-alt-missing">' . esc_html__( 'Заполните «Альтернативный текст»: что изображено на картинке, 5–15 слов. Его читают поисковики и экранные дикторы; без него картинка не попадёт в поиск по картинкам. Для чисто декоративной картинки в блоке есть флажок «Декоративная».', 'trust' ) . '</p>'
				: '<p class="trust-alt-ok">' . esc_html__( 'Альтернативный текст заполнен.', 'trust' ) . '</p>',
		);
		return $fields;
	}

	/**
	 * Цвета предупреждения об alt.
	 */
	public static function alt_styles(): void {
		echo '<style>.trust-alt-missing{color:#b32d2e;font-weight:600}.compat-field-trust_alt_hint p{margin:0}.trust-alt-ok{color:#007017}.fixed .column-trust_alt{width:22%}</style>';
	}
}
