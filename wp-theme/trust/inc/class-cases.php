<?php
/**
 * Кейсы: колонка «Главный» в админке, данные кейса для карточек блоков.
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
	 * Чем занимается клиент: поле «Чем занимается клиент», иначе первая отрасль кейса.
	 *
	 * @param int $post_id Кейс.
	 */
	public static function niche( int $post_id ): string {
		$niche = trim( (string) get_post_meta( $post_id, 'trust_niche', true ) );
		if ( '' !== $niche ) {
			return $niche;
		}
		$terms = get_the_terms( $post_id, 'industry' );
		return is_array( $terms ) && $terms ? $terms[0]->name : '';
	}

	/**
	 * Название проекта для карточек: клиент, иначе заголовок кейса.
	 *
	 * @param int $post_id Кейс.
	 */
	public static function name( int $post_id ): string {
		$client = trim( (string) get_post_meta( $post_id, 'trust_client', true ) );
		return '' !== $client ? $client : get_the_title( $post_id );
	}

	/**
	 * Скриншот сайта: поле «Скриншот сайта», иначе «Изображение записи» (0 — нет).
	 *
	 * @param int $post_id Кейс.
	 */
	public static function screenshot( int $post_id ): int {
		$id = (int) get_post_meta( $post_id, 'trust_screenshot', true );
		return $id ? $id : (int) get_post_thumbnail_id( $post_id );
	}

	/**
	 * Команда проекта: строки поля «Команда проекта» — сотрудник (ID) и роль в проекте.
	 *
	 * @param int $post_id Кейс.
	 * @return array<int, array{member: int, role: string}>
	 */
	public static function team( int $post_id ): array {
		$rows = array();
		foreach ( (array) get_field( 'trust_team', $post_id ) as $row ) {
			if ( is_array( $row ) && ! empty( $row['member'] ) ) {
				$rows[] = array(
					'member' => (int) $row['member'],
					'role'   => trim( (string) ( $row['role'] ?? '' ) ),
				);
			}
		}
		return $rows;
	}

	/**
	 * Позиции в поиске: строки «запрос, Яндекс, Google» (позиция 0 — не указана).
	 *
	 * @param int $post_id Кейс.
	 * @return array<int, array{query: string, yandex: int, google: int}>
	 */
	public static function positions( int $post_id ): array {
		$rows       = array();
		$rows_count = (int) get_post_meta( $post_id, 'trust_positions', true );
		for ( $i = 0; $i < $rows_count; $i++ ) {
			$query = trim( (string) get_post_meta( $post_id, "trust_positions_{$i}_query", true ) );
			if ( '' === $query ) {
				continue;
			}
			$rows[] = array(
				'query'  => $query,
				'yandex' => (int) get_post_meta( $post_id, "trust_positions_{$i}_yandex", true ),
				'google' => (int) get_post_meta( $post_id, "trust_positions_{$i}_google", true ),
			);
		}
		return $rows;
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
