<?php
/**
 * Кейсы: колонка «Главный» в админке.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Поля кейса — inc/fields/content/case.php. Колонки «Услуги» и «Отрасли» добавляют
 * сами таксономии (show_admin_column).
 */
final class Cases {

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'manage_case_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_case_posts_custom_column', array( self::class, 'column' ), 10, 2 );
		add_action( 'admin_head-edit.php', array( self::class, 'column_styles' ) );
	}

	/**
	 * Колонки «Главный» и «Клиент» после заголовка.
	 *
	 * @param array $columns Колонки.
	 */
	public static function columns( array $columns ): array {
		$result = array();
		foreach ( $columns as $key => $label ) {
			$result[ $key ] = $label;
			if ( 'title' === $key ) {
				$result['trust_featured'] = __( 'Главный', 'trust' );
				$result['trust_client']   = __( 'Клиент', 'trust' );
			}
		}
		return $result;
	}

	/**
	 * Значение колонки.
	 *
	 * @param string $column  Колонка.
	 * @param int    $post_id Кейс.
	 */
	public static function column( string $column, int $post_id ): void {
		if ( 'trust_featured' === $column && get_post_meta( $post_id, Blog::FEATURED, true ) ) {
			printf( '<span aria-hidden="true">★</span><span class="screen-reader-text">%s</span>', esc_html__( 'Главный кейс', 'trust' ) );
		}
		if ( 'trust_client' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, 'trust_client', true ) );
		}
	}

	/**
	 * Ширина колонки «Главный».
	 */
	public static function column_styles(): void {
		if ( 'case' === ( get_current_screen()->post_type ?? '' ) ) {
			echo '<style>.fixed .column-trust_featured{width:5.5em;text-align:center}</style>';
		}
	}
}
