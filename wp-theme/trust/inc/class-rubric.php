<?php
/**
 * Страница рубрики блога (ТЗ, разделы 6.2 и 13; макет portfolio-blog-404/rubrika.html).
 *
 * - Главный запрос рубрики: статей на странице — «Настройки сайта → Блог» (как в блоге), без
 *   закреплённых; главная статья рубрики («Главная статья» или самая новая) выводится крупной
 *   карточкой на первой странице и в списке не повторяется.
 * - Сортировка «Новые / Популярные»: ?sort=popular — по лайкам, затем по дате (статьи без лайков —
 *   в конце). Переключатель меняет список без перезагрузки через REST
 *   (GET /wp-json/trust/v1/rubrics/{id}?sort=popular&page=1), прямой адрес с параметром тоже
 *   работает; canonical у адреса с параметром — на рубрику без него (Trust\Seo::canonical()).
 * - Полоса рубрик: «Все статьи» и все непустые рубрики (кроме рубрики по умолчанию) с числом
 *   статей — в порядке создания; теги рубрики — теги её статей, сначала самые частые.
 *
 * Разметка — category.php и template-parts/blog/*, стили — assets/css/rubric.css, скрипт —
 * assets/js/rubric.js.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Запросы, данные и REST страницы рубрики.
 */
final class Rubric {

	/**
	 * Значение ?sort= для сортировки по популярности.
	 */
	public const POPULAR = 'popular';

	/**
	 * Сколько тегов рубрики показывать.
	 */
	public const TAGS = 12;

	/**
	 * Якорь списка статей (пагинация ведёт к нему).
	 */
	public const ANCHOR = 'posts';

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'pre_get_posts', array( self::class, 'main_query' ) );
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/**
	 * Сортировка из адреса: popular или new.
	 */
	public static function sort(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- только выбор сортировки списка.
		$sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : '';
		return self::POPULAR === $sort ? self::POPULAR : 'new';
	}

	/**
	 * Главный запрос рубрики: число статей, без закреплённых и главной статьи, сортировка.
	 *
	 * @param \WP_Query $query Запрос.
	 */
	public static function main_query( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_category() ) {
			return;
		}
		$term = $query->get_queried_object();
		if ( ! $term instanceof \WP_Term ) {
			return;
		}
		foreach ( self::query_args( $term, self::sort() ) as $key => $value ) {
			$query->set( $key, $value );
		}
	}

	/**
	 * Параметры списка статей рубрики (главный запрос и REST).
	 *
	 * @param \WP_Term $term Рубрика.
	 * @param string   $sort popular или new.
	 */
	public static function query_args( \WP_Term $term, string $sort ): array {
		$args     = array(
			'posts_per_page'      => Blog::per_page(),
			'ignore_sticky_posts' => true,
		);
		$featured = Blog::featured( $term->term_id );
		if ( $featured ) {
			$args['post__not_in'] = array( $featured->ID ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- одна статья.
		}
		if ( self::POPULAR === $sort ) {
			// Статьи без счётчика лайков не пропадают, а встают в конец.
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- сортировка по счётчику.
				'relation' => 'OR',
				'likes'    => array(
					'key'  => Blog::LIKES,
					'type' => 'NUMERIC',
				),
				array(
					'key'     => Blog::LIKES,
					'compare' => 'NOT EXISTS',
				),
			);
			$args['orderby']    = array(
				'likes' => 'DESC',
				'date'  => 'DESC',
			);
		} else {
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
		}
		return $args;
	}

	/**
	 * Адрес страницы списка рубрики: /blog/rubrika/<ярлык>/page/N/?sort=popular.
	 *
	 * @param \WP_Term $term Рубрика.
	 * @param int      $page Страница.
	 * @param string   $sort popular или new.
	 */
	public static function url( \WP_Term $term, int $page = 1, string $sort = 'new' ): string {
		global $wp_rewrite;
		$url = (string) get_term_link( $term );
		if ( $page > 1 ) {
			$url = user_trailingslashit( trailingslashit( $url ) . $wp_rewrite->pagination_base . '/' . $page, 'paged' );
		}
		return self::POPULAR === $sort ? add_query_arg( 'sort', self::POPULAR, $url ) : $url;
	}

	/**
	 * Число статей рубрики (с подрубриками) и дата самой новой.
	 *
	 * @param \WP_Term $term Рубрика.
	 * @return array{count: int, updated: string}
	 */
	public static function stats( \WP_Term $term ): array {
		$query  = new \WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'cat'                 => $term->term_id,
				'posts_per_page'      => 1,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
			)
		);
		$latest = $query->posts[0] ?? null;
		return array(
			'count'   => (int) $query->found_posts,
			'updated' => $latest ? Blog::date( $latest ) : '',
		);
	}

	/**
	 * Полоса рубрик: непустые рубрики, кроме рубрики по умолчанию, в порядке создания.
	 *
	 * @return \WP_Term[]
	 */
	public static function strip(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => true,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
				'exclude'    => array( (int) get_option( 'default_category' ) ),
			)
		);
		return is_array( $terms ) ? $terms : array();
	}

	/**
	 * Теги статей рубрики: сначала встречающиеся чаще, при равенстве — по алфавиту.
	 *
	 * @param \WP_Term $term  Рубрика.
	 * @param int      $limit Сколько тегов.
	 * @return \WP_Term[]
	 */
	public static function tags( \WP_Term $term, int $limit = self::TAGS ): array {
		$ids = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'cat'            => $term->term_id,
				'posts_per_page' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- только ID статей рубрики.
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		if ( ! $ids ) {
			return array();
		}
		$links = wp_get_object_terms( $ids, 'post_tag', array( 'fields' => 'all_with_object_id' ) );
		if ( ! is_array( $links ) ) {
			return array();
		}
		$tags  = array();
		$count = array();
		foreach ( $links as $link ) {
			$tags[ $link->term_id ]  = $link;
			$count[ $link->term_id ] = ( $count[ $link->term_id ] ?? 0 ) + 1;
		}
		uasort(
			$tags,
			static function ( \WP_Term $a, \WP_Term $b ) use ( $count ): int {
				$by_count = $count[ $b->term_id ] <=> $count[ $a->term_id ];
				return 0 !== $by_count ? $by_count : strcmp( mb_strtolower( $a->name ), mb_strtolower( $b->name ) );
			}
		);
		return array_slice( array_values( $tags ), 0, $limit );
	}

	/**
	 * Ведущий рубрики: сотрудник из поля «Ведёт рубрику» (имя, должность, фото, адрес страницы).
	 *
	 * @param \WP_Term $term Рубрика.
	 * @return array{name: string, role: string, photo: int, initials: string, url: string}|null
	 */
	public static function lead( \WP_Term $term ): ?array {
		$id     = Dependencies::has_scf() ? (int) get_field( 'trust_rubric_lead', $term ) : (int) get_term_meta( $term->term_id, 'trust_rubric_lead', true );
		$member = $id ? get_post( $id ) : null;
		if ( ! $member || 'team' !== $member->post_type || 'publish' !== $member->post_status ) {
			return null;
		}
		$name = get_the_title( $member );
		return array(
			'name'     => $name,
			'role'     => trim( (string) get_post_meta( $member->ID, 'trust_position', true ) ),
			'photo'    => (int) get_post_thumbnail_id( $member ),
			'initials' => Answers_List::initials( $name ),
			'url'      => is_post_type_viewable( 'team' ) ? (string) get_permalink( $member ) : '',
		);
	}

	/**
	 * Текст «О рубрике» (поле WYSIWYG), '' — блока нет.
	 *
	 * @param \WP_Term $term Рубрика.
	 */
	public static function about( \WP_Term $term ): string {
		$html = Dependencies::has_scf() ? (string) get_field( 'trust_rubric_about', $term ) : wpautop( (string) get_term_meta( $term->term_id, 'trust_rubric_about', true ) );
		return '' !== trim( wp_strip_all_tags( $html ) ) ? $html : '';
	}

	/**
	 * Карточки списка и пагинация (шаблон и ответ REST).
	 *
	 * @param \WP_Term   $term  Рубрика.
	 * @param \WP_Post[] $posts Статьи страницы.
	 * @param int        $page  Страница.
	 * @param int        $pages Всего страниц.
	 * @param string     $sort  popular или new.
	 */
	public static function list_html( \WP_Term $term, array $posts, int $page, int $pages, string $sort ): string {
		if ( ! $posts ) {
			return '<p class="post-list__empty">' . esc_html__( 'Статей пока нет.', 'trust' ) . '</p>';
		}
		ob_start();
		echo '<ul class="post-cards post-cards--lg post-cards--rubric">';
		foreach ( $posts as $post ) {
			get_template_part(
				'template-parts/cards/post',
				null,
				array(
					'post'    => $post,
					'size'    => 'lg',
					'variant' => 'rubric',
					'sizes'   => '(max-width: 760px) calc(100vw - 40px), 600px',
				)
			);
		}
		echo '</ul>';
		$pager = Pagination::render(
			$page,
			$pages,
			self::ANCHOR,
			array( 'nav' => __( 'Страницы рубрики', 'trust' ) ),
			static fn( int $n ): string => self::url( $term, $n, $sort )
		);
		echo $pager; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в Pagination::render().
		return (string) ob_get_clean();
	}

	/**
	 * REST: список статей рубрики в нужной сортировке.
	 */
	public static function routes(): void {
		register_rest_route(
			'trust/v1',
			'/rubrics/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'rest_list' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'sort' => array(
						'type'    => 'string',
						'enum'    => array( 'new', self::POPULAR ),
						'default' => 'new',
					),
					'page' => array(
						'type'    => 'integer',
						'minimum' => 1,
						'maximum' => 1000,
						'default' => 1,
					),
				),
			)
		);
	}

	/**
	 * Ответ REST: html (карточки и пагинация), page, pages, url (адрес этого состояния списка).
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_list( \WP_REST_Request $request ) {
		$term = get_term( (int) $request['id'], 'category' );
		if ( ! $term instanceof \WP_Term ) {
			return new \WP_Error( 'trust_rubric_not_found', __( 'Рубрика не найдена.', 'trust' ), array( 'status' => 404 ) );
		}
		$sort  = self::POPULAR === $request['sort'] ? self::POPULAR : 'new';
		$page  = (int) $request['page'];
		$query = new \WP_Query(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'cat'         => $term->term_id,
				'paged'       => $page,
			) + self::query_args( $term, $sort )
		);
		$pages = max( 1, (int) $query->max_num_pages );
		if ( $page > $pages ) {
			return new \WP_Error( 'trust_rubric_page', __( 'Такой страницы нет.', 'trust' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response(
			array(
				'html'  => self::list_html( $term, $query->posts, $page, $pages, $sort ),
				'page'  => $page,
				'pages' => $pages,
				'url'   => self::url( $term, $page, $sort ),
			)
		);
	}

	/**
	 * Стили и скрипт страницы рубрики.
	 */
	public static function enqueue(): void {
		if ( ! is_category() ) {
			return;
		}
		$term = get_queried_object();
		if ( ! $term instanceof \WP_Term ) {
			return;
		}
		wp_enqueue_style( 'trust-blog', Assets::url( 'assets/css/blog.css' ), array( 'trust-main', 'trust-pagination' ), Assets::version( 'assets/css/blog.css' ) );
		wp_enqueue_style( 'trust-rubric', Assets::url( 'assets/css/rubric.css' ), array( 'trust-blog' ), Assets::version( 'assets/css/rubric.css' ) );
		wp_enqueue_script( 'trust-rubric', Assets::url( 'assets/js/rubric.js' ), array(), Assets::version( 'assets/js/rubric.js' ), true );
		wp_add_inline_script(
			'trust-rubric',
			'window.trustRubric = ' . wp_json_encode(
				array(
					'rest' => rest_url( 'trust/v1/rubrics/' . $term->term_id ),
					'page' => max( 1, (int) get_query_var( 'paged' ) ),
				)
			) . ';',
			'before'
		);
	}
}
