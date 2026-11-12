<?php
/**
 * Сотрудники: стаж, связь с пользователем WordPress, колонки в админке.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Поля сотрудника — inc/fields/content/team.php.
 */
final class Team {

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'acf/validate_value/key=' . Fields::key( 'team', 'trust_user' ), array( self::class, 'validate_user' ), 10, 2 );
		add_filter( 'enter_title_here', array( self::class, 'title_placeholder' ), 10, 2 );
		add_filter( 'manage_team_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_team_posts_custom_column', array( self::class, 'column' ), 10, 2 );
		add_action( 'admin_head-edit.php', array( self::class, 'column_styles' ) );
	}

	/**
	 * Сотрудник, связанный с пользователем WordPress (автор статей и ответов).
	 *
	 * @param int $user_id Пользователь.
	 */
	public static function for_user( int $user_id ): ?\WP_Post {
		if ( ! $user_id ) {
			return null;
		}
		$posts = get_posts(
			array(
				'post_type'   => 'team',
				'post_status' => 'publish',
				'numberposts' => 1,
				'meta_key'    => 'trust_user', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- один сотрудник по индексу meta_key.
				'meta_value'  => $user_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- см. выше.
			)
		);
		return $posts[0] ?? null;
	}

	/**
	 * Стаж в годах по году начала работы в профессии; null — год не указан.
	 *
	 * @param int $post_id Сотрудник.
	 */
	public static function experience_years( int $post_id ): ?int {
		$start = (int) get_post_meta( $post_id, 'trust_career_start', true );
		if ( ! $start ) {
			return null;
		}
		return max( 1, (int) current_time( 'Y' ) - $start );
	}

	/**
	 * «год», «года» или «лет» для числа.
	 *
	 * @param int $years Число лет.
	 */
	public static function years_word( int $years ): string {
		$mod10  = $years % 10;
		$mod100 = $years % 100;

		if ( 1 === $mod10 && 11 !== $mod100 ) {
			return 'год';
		}
		if ( $mod10 >= 2 && $mod10 <= 4 && ( $mod100 < 12 || $mod100 > 14 ) ) {
			return 'года';
		}
		return 'лет';
	}

	/**
	 * Строка стажа: «опыт в SEO — 11 лет»; пустая строка, если год не указан.
	 *
	 * @param int $post_id Сотрудник.
	 */
	public static function experience_line( int $post_id ): string {
		$years = self::experience_years( $post_id );
		if ( null === $years ) {
			return '';
		}
		$area = trim( (string) get_post_meta( $post_id, 'trust_experience_area', true ) );
		return trim( 'опыт ' . $area ) . ' — ' . $years . ' ' . self::years_word( $years );
	}

	/**
	 * Пользователь WordPress связывается только с одним сотрудником: иначе непонятно,
	 * чья страница — страница автора.
	 *
	 * @param bool|string $valid Результат проверки.
	 * @param mixed       $value ID пользователя.
	 * @return bool|string
	 */
	public static function validate_user( $valid, $value ) {
		if ( true !== $valid || empty( $value ) ) {
			return $valid;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce проверяет SCF до вызова acf/validate_value.
		$current = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0;
		$other   = get_posts(
			array(
				'post_type'    => 'team',
				'post_status'  => 'any',
				'numberposts'  => 1,
				'post__not_in' => array( $current ),
				'meta_key'     => 'trust_user', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- проверка при сохранении в админке.
				'meta_value'   => (int) $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- см. выше.
			)
		);

		if ( $other ) {
			/* translators: %s: имя сотрудника. */
			return sprintf( __( 'Этот пользователь уже связан с сотрудником «%s».', 'trust' ), $other[0]->post_title );
		}
		return $valid;
	}

	/**
	 * Подсказка в поле заголовка сотрудника.
	 *
	 * @param string   $text Подсказка.
	 * @param \WP_Post $post Запись.
	 */
	public static function title_placeholder( $text, $post ) {
		return $post instanceof \WP_Post && 'team' === $post->post_type ? __( 'Имя и фамилия', 'trust' ) : $text;
	}

	/**
	 * Колонки списка сотрудников (отделы — колонка таксономии).
	 *
	 * @param array $columns Колонки.
	 */
	public static function columns( array $columns ): array {
		$columns = Admin::insert_columns(
			$columns,
			'title',
			array(
				'trust_position' => __( 'Должность', 'trust' ),
				'trust_exp'      => __( 'Опыт', 'trust' ),
			),
			array(
				'trust_leader' => __( 'Руководство', 'trust' ),
				'trust_user'   => __( 'Пользователь', 'trust' ),
				'trust_order'  => __( 'Порядок', 'trust' ),
			)
		);

		// Фото — сразу после флажка выбора.
		return array_slice( $columns, 0, 1, true ) + array( 'trust_photo' => __( 'Фото', 'trust' ) ) + array_slice( $columns, 1, null, true );
	}

	/**
	 * Значение колонки.
	 *
	 * @param string $column  Колонка.
	 * @param int    $post_id Сотрудник.
	 */
	public static function column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'trust_photo':
				echo get_the_post_thumbnail( $post_id, array( 40, 40 ), array( 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка WordPress.
				break;
			case 'trust_position':
				echo esc_html( (string) get_post_meta( $post_id, 'trust_position', true ) );
				break;
			case 'trust_exp':
				$years = self::experience_years( $post_id );
				echo esc_html( null === $years ? '' : $years . ' ' . self::years_word( $years ) );
				break;
			case 'trust_leader':
				if ( get_post_meta( $post_id, 'trust_leader', true ) ) {
					printf( '<span aria-hidden="true">★</span><span class="screen-reader-text">%s</span>', esc_html__( 'Руководство', 'trust' ) );
				}
				break;
			case 'trust_user':
				$user = get_userdata( (int) get_post_meta( $post_id, 'trust_user', true ) );
				echo esc_html( $user ? $user->display_name : '' );
				break;
			case 'trust_order':
				echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
				break;
		}
	}

	/**
	 * Ширина колонок списка сотрудников.
	 */
	public static function column_styles(): void {
		if ( 'team' === ( get_current_screen()->post_type ?? '' ) ) {
			echo '<style>.fixed .column-trust_photo{width:52px}.column-trust_photo img{border-radius:50%;display:block}.fixed .column-trust_leader,.fixed .column-trust_order{width:7em;text-align:center}.fixed .column-trust_exp{width:6em}</style>';
		}
	}
}
