<?php
/**
 * Данные блога: просмотры, лайки, главная статья (ТЗ, разделы 5.3 и 13).
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Служебные метаполя статей и выбор главной статьи.
 *
 * Счётчики trust_views и trust_likes заполняются на шаге 5.2 (REST после загрузки страницы
 * и кнопка «Нравится»). Здесь они регистрируются, скрыты из «Произвольных полей» (вручную
 * их не правят) и выводятся колонками в списке статей с сортировкой.
 */
final class Blog {

	/**
	 * Месяцы в родительном падеже: даты по-русски («5 ноября 2026») без языковых файлов WordPress.
	 */
	public const MONTHS = array( 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря' );

	/**
	 * Метаполе: число просмотров статьи.
	 */
	public const VIEWS = 'trust_views';

	/**
	 * Метаполе: число лайков статьи.
	 */
	public const LIKES = 'trust_likes';

	/**
	 * Поле SCF «Главная статья» (inc/fields/content/post.php).
	 */
	public const FEATURED = 'trust_featured';

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ) );
		add_filter( 'is_protected_meta', array( self::class, 'protect_meta' ), 10, 2 );
		add_filter( 'manage_post_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_post_posts_custom_column', array( self::class, 'column' ), 10, 2 );
		add_filter( 'manage_edit-post_sortable_columns', array( self::class, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( self::class, 'sort_by_counter' ) );
		add_action( 'pre_get_posts', array( self::class, 'list_query' ) );
		add_action( 'admin_head-edit.php', array( self::class, 'column_styles' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/**
	 * Список статей блога (home.php): статей на странице — из настроек, без закреплённых;
	 * главная статья выводится крупной карточкой сверху и в сетке не повторяется (на всех
	 * страницах списка — поэтому страницы 2+ продолжают сетку без пропусков).
	 *
	 * @param \WP_Query $query Запрос.
	 */
	public static function list_query( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_home() ) {
			return;
		}
		$query->set( 'posts_per_page', self::per_page() );
		$query->set( 'ignore_sticky_posts', true );
		$featured = self::featured();
		if ( $featured ) {
			$query->set( 'post__not_in', array( $featured->ID ) ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- одна статья.
		}
	}

	/**
	 * Стили списка статей и пагинации — на странице блога.
	 */
	public static function enqueue(): void {
		if ( is_home() ) {
			wp_enqueue_style( 'trust-blog', Assets::url( 'assets/css/blog.css' ), array( 'trust-main', 'trust-pagination' ), Assets::version( 'assets/css/blog.css' ) );
		}
	}

	/**
	 * Автор статьи для карточки: сотрудник, связанный с пользователем (имя, должность, фото),
	 * иначе имя пользователя. initials — для аватара без фото.
	 *
	 * @param int|\WP_Post $post Статья.
	 * @return array{name: string, role: string, photo: int, initials: string}
	 */
	public static function author( $post ): array {
		$post   = get_post( $post );
		$user   = $post ? (int) $post->post_author : 0;
		$member = $user ? Team::for_user( $user ) : null;
		$name   = $member ? get_the_title( $member ) : ( $user ? (string) get_the_author_meta( 'display_name', $user ) : '' );
		return array(
			'name'     => $name,
			'role'     => $member ? trim( (string) get_post_meta( $member->ID, 'trust_position', true ) ) : '',
			'photo'    => $member ? (int) get_post_thumbnail_id( $member ) : 0,
			'initials' => Answers_List::initials( $name ),
		);
	}

	/**
	 * Счётчик в карточке: значок, число; подпись — для скринридеров («Лайки: 64»).
	 *
	 * @param string $icon  Иконка (heart, chat, clock).
	 * @param string $value Значение.
	 * @param string $label Подпись для скринридеров ('' — без неё).
	 */
	public static function stat( string $icon, string $value, string $label = '' ): string {
		return '<span class="post-stat">' . Icons::svg(
			$icon,
			array(
				'size'         => 16,
				'stroke'       => 'var(--c-violet)',
				'stroke_width' => 2,
			)
		) . ( '' !== $label ? '<span class="screen-reader-text">' . esc_html( $label ) . ': </span>' : '' ) . esc_html( $value ) . '</span>';
	}

	/**
	 * Счётчики — целые числа, по умолчанию 0.
	 */
	public static function register_meta(): void {
		foreach ( array( self::VIEWS, self::LIKES ) as $key ) {
			register_post_meta(
				'post',
				$key,
				array(
					'type'              => 'integer',
					'single'            => true,
					'default'           => 0,
					'sanitize_callback' => 'absint',
					'show_in_rest'      => false,
				)
			);
		}
	}

	/**
	 * Счётчики не показываются в «Произвольных полях» и не правятся вручную.
	 *
	 * @param bool   $is_protected Защищено ли метаполе.
	 * @param string $meta_key     Ключ метаполя.
	 */
	public static function protect_meta( $is_protected, $meta_key ) {
		return in_array( $meta_key, array( self::VIEWS, self::LIKES ), true ) ? true : $is_protected;
	}

	/**
	 * Число просмотров.
	 *
	 * @param int|\WP_Post $post Статья.
	 */
	public static function views( $post ): int {
		return (int) get_post_meta( get_post( $post )->ID ?? 0, self::VIEWS, true );
	}

	/**
	 * Число лайков.
	 *
	 * @param int|\WP_Post $post Статья.
	 */
	public static function likes( $post ): int {
		return (int) get_post_meta( get_post( $post )->ID ?? 0, self::LIKES, true );
	}

	/**
	 * Скорость чтения по умолчанию, слов в минуту (ТЗ, раздел 13).
	 */
	public const READ_SPEED = 180;

	/**
	 * Статей на странице списка по умолчанию (ТЗ, раздел 5.4: «Записей на странице (20)»).
	 */
	public const PER_PAGE = 20;

	/**
	 * Статей на странице списка: «Настройки сайта → Блог» (6–60), пусто — PER_PAGE.
	 */
	public static function per_page(): int {
		$value = (int) Settings::get( 'blog_per_page', 0 );
		return $value > 0 ? max( 6, min( 60, $value ) ) : self::PER_PAGE;
	}

	/**
	 * Показывать ли лайки в карточках и статьях: «Настройки сайта → Блог», по умолчанию — да.
	 */
	public static function show_likes(): bool {
		if ( ! Dependencies::has_scf() ) {
			return true;
		}
		// Настройку ещё не сохраняли — null: показывать (как флажок по умолчанию).
		$value = get_field( 'blog_show_likes', 'option' );
		return null === $value || (bool) $value;
	}

	/**
	 * Время чтения в минутах: слова текста / скорость из «Настроек сайта → Блог»,
	 * округление вверх, минимум 1 минута (ТЗ, раздел 13).
	 *
	 * @param int|\WP_Post $post Статья.
	 */
	public static function reading_time( $post ): int {
		$post  = get_post( $post );
		$text  = $post ? wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) : '';
		$words = (int) preg_match_all( '/[\p{L}\p{N}]+/u', $text );
		$speed = (int) Settings::get( 'blog_read_speed', 0 );
		$speed = $speed > 0 ? $speed : self::READ_SPEED;
		return max( 1, (int) ceil( $words / $speed ) );
	}

	/**
	 * Дата статьи по-русски: «5 ноября 2026» (не зависит от языковых файлов WordPress).
	 *
	 * @param int|\WP_Post $post Статья.
	 */
	public static function date( $post ): string {
		$time = get_post_timestamp( $post );
		return $time ? wp_date( 'j', $time ) . ' ' . self::MONTHS[ (int) wp_date( 'n', $time ) - 1 ] . ' ' . wp_date( 'Y', $time ) : '';
	}

	/**
	 * Рубрика статьи для плашки в карточке: первая рубрика (кроме «Без рубрики»).
	 *
	 * @param int|\WP_Post $post Статья.
	 */
	public static function rubric( $post ): ?\WP_Term {
		$default = (int) get_option( 'default_category' );
		$terms   = get_the_category( get_post( $post )->ID ?? 0 );
		$named   = array_values( array_filter( $terms, static fn( $term ) => (int) $term->term_id !== $default ) );
		return $named[0] ?? null;
	}

	/**
	 * Адрес списка статей: страница записей из «Настроек → Чтение», страница /blog/ или /blog/.
	 */
	public static function index_url(): string {
		$page = (int) get_option( 'page_for_posts' );
		if ( $page ) {
			return (string) get_permalink( $page );
		}
		$blog = get_page_by_path( 'blog' );
		return $blog ? (string) get_permalink( $blog ) : home_url( '/blog/' );
	}

	/**
	 * Главная статья блога или рубрики: самая новая из отмеченных «Главная статья»,
	 * если отмеченных нет — самая новая статья.
	 *
	 * @param int $category ID рубрики; 0 — весь блог.
	 */
	public static function featured( int $category = 0 ): ?\WP_Post {
		static $cache = array();
		if ( array_key_exists( $category, $cache ) ) {
			return $cache[ $category ];
		}
		$cache[ $category ] = self::find_featured( $category );
		return $cache[ $category ];
	}

	/**
	 * Поиск главной статьи (см. featured()).
	 *
	 * @param int $category ID рубрики; 0 — весь блог.
	 */
	private static function find_featured( int $category ): ?\WP_Post {
		$args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
		if ( $category ) {
			$args['cat'] = $category;
		}

		$flagged = get_posts(
			$args + array(
				'meta_key'   => self::FEATURED, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- одна статья, запрос по индексу meta_key.
				'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- см. выше.
			)
		);
		if ( $flagged ) {
			return $flagged[0];
		}

		$latest = get_posts( $args );
		return $latest[0] ?? null;
	}

	/**
	 * Колонки «Главная», «Просмотры», «Лайки» в списке статей (перед датой).
	 *
	 * @param array $columns Колонки.
	 */
	public static function columns( array $columns ): array {
		$date = $columns['date'] ?? null;
		unset( $columns['date'] );

		$columns['trust_featured'] = __( 'Главная', 'trust' );
		$columns['trust_views']    = __( 'Просмотры', 'trust' );
		$columns['trust_likes']    = __( 'Лайки', 'trust' );

		if ( $date ) {
			$columns['date'] = $date;
		}
		return $columns;
	}

	/**
	 * Значение колонки.
	 *
	 * @param string $column  Колонка.
	 * @param int    $post_id Статья.
	 */
	public static function column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'trust_featured':
				if ( get_post_meta( $post_id, self::FEATURED, true ) ) {
					printf( '<span aria-hidden="true">★</span><span class="screen-reader-text">%s</span>', esc_html__( 'Главная статья', 'trust' ) );
				}
				break;
			case 'trust_views':
				echo esc_html( number_format_i18n( self::views( $post_id ) ) );
				break;
			case 'trust_likes':
				echo esc_html( number_format_i18n( self::likes( $post_id ) ) );
				break;
		}
	}

	/**
	 * Сортировка по просмотрам и лайкам.
	 *
	 * @param array $columns Сортируемые колонки.
	 */
	public static function sortable_columns( array $columns ): array {
		$columns['trust_views'] = array( 'trust_views', true );
		$columns['trust_likes'] = array( 'trust_likes', true );
		return $columns;
	}

	/**
	 * Сортирует список статей в админке по счётчику. Статьи без счётчика (0 просмотров)
	 * не пропадают из списка, а встают в конец.
	 *
	 * @param \WP_Query $query Запрос.
	 */
	public static function sort_by_counter( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		if ( ! in_array( $orderby, array( 'trust_views', 'trust_likes' ), true ) ) {
			return;
		}

		$key   = 'trust_views' === $orderby ? self::VIEWS : self::LIKES;
		$order = 'ASC' === strtoupper( (string) $query->get( 'order' ) ) ? 'ASC' : 'DESC';

		$query->set(
			'meta_query',
			array(
				'relation' => 'OR',
				'counter'  => array(
					'key'  => $key,
					'type' => 'NUMERIC',
				),
				array(
					'key'     => $key,
					'compare' => 'NOT EXISTS',
				),
			)
		);
		$query->set(
			'orderby',
			array(
				'counter' => $order,
				'date'    => 'DESC',
			)
		);
	}

	/**
	 * Ширина колонок счётчиков.
	 */
	public static function column_styles(): void {
		if ( 'post' !== ( get_current_screen()->post_type ?? '' ) ) {
			return;
		}
		echo '<style>.fixed .column-trust_featured{width:5.5em;text-align:center}.fixed .column-trust_views,.fixed .column-trust_likes{width:7em}</style>';
	}
}
