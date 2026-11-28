<?php
/**
 * Страница тега блога (ТЗ, разделы 6.2 и 13; макет portfolio-blog-404/teg.html).
 *
 * - Теги общие у статей и ответов на вопросы: на странице тега — и то и другое, новые сверху.
 *   Статей на странице — «Настройки сайта → Блог» (как в блоге и рубрике).
 * - Фильтр «Все / Статьи / Ответы на вопросы»: ?type=posts или ?type=answers. Переключатель меняет
 *   список без перезагрузки через REST (GET /wp-json/trust/v1/tags/{id}?type=posts&page=1), прямой
 *   адрес с параметром тоже работает; canonical у адреса с параметром — на тег без него
 *   (Trust\Seo::canonical()). Фильтр — только когда у тега есть и статьи, и ответы.
 * - Сбоку: рубрика, в которой больше всего статей тега; похожие теги — теги, которые чаще всего
 *   стоят вместе с этим; «Остался вопрос про …?» со ссылкой на страницу вопросов.
 * - Тег с материалами меньше порога — noindex (Trust\Seo::is_thin_tag()).
 *
 * Разметка — tag.php и template-parts/blog/tag-*.php, карточки — template-parts/cards/material.php,
 * стили — assets/css/tag.css, скрипт — assets/js/tag.js.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Запросы, данные и REST страницы тега.
 */
final class Tag_Page {

	/**
	 * Типы материалов фильтра: значение ?type= => типы записей.
	 */
	public const TYPES = array(
		'all'     => array( 'post', 'question' ),
		'posts'   => array( 'post' ),
		'answers' => array( 'question' ),
	);

	/**
	 * Сколько похожих тегов показывать.
	 */
	public const SIMILAR = 8;

	/**
	 * Якорь списка (пагинация ведёт к нему).
	 */
	public const ANCHOR = 'materials';

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'pre_get_posts', array( self::class, 'main_query' ) );
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/**
	 * Фильтр из адреса: all, posts или answers.
	 */
	public static function type(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- только выбор фильтра списка.
		$type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
		return isset( self::TYPES[ $type ] ) ? $type : 'all';
	}

	/**
	 * Главный запрос тега: статьи и ответы (или одно из них), новые сверху.
	 *
	 * @param \WP_Query $query Запрос.
	 */
	public static function main_query( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_tag() ) {
			return;
		}
		foreach ( self::query_args( self::type() ) as $key => $value ) {
			$query->set( $key, $value );
		}
	}

	/**
	 * Параметры списка (главный запрос и REST).
	 *
	 * @param string $type all, posts или answers.
	 */
	public static function query_args( string $type ): array {
		return array(
			'post_type'           => self::TYPES[ $type ] ?? self::TYPES['all'],
			'post_status'         => 'publish',
			'posts_per_page'      => Blog::per_page(),
			'ignore_sticky_posts' => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
		);
	}

	/**
	 * Адрес страницы списка тега: /blog/teg/<ярлык>/page/N/?type=posts.
	 *
	 * @param \WP_Term $term Тег.
	 * @param int      $page Страница.
	 * @param string   $type all, posts или answers.
	 */
	public static function url( \WP_Term $term, int $page = 1, string $type = 'all' ): string {
		global $wp_rewrite;
		$url = (string) get_term_link( $term );
		if ( $page > 1 ) {
			$url = user_trailingslashit( trailingslashit( $url ) . $wp_rewrite->pagination_base . '/' . $page, 'paged' );
		}
		return 'all' !== $type && isset( self::TYPES[ $type ] ) ? add_query_arg( 'type', $type, $url ) : $url;
	}

	/**
	 * Число статей и ответов с тегом.
	 *
	 * @param \WP_Term $term Тег.
	 * @return array{posts: int, answers: int}
	 */
	public static function counts( \WP_Term $term ): array {
		$count = static fn( string $post_type ): int => (int) ( new \WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'tag_id'         => $term->term_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		) )->found_posts;
		return array(
			'posts'   => $count( 'post' ),
			'answers' => $count( 'question' ),
		);
	}

	/**
	 * «4 статьи и 3 ответа на вопросы» (без нулевых частей).
	 *
	 * @param array{posts: int, answers: int} $counts Числа.
	 */
	public static function counts_text( array $counts ): string {
		$parts = array();
		if ( $counts['posts'] ) {
			$parts[] = Article::count( $counts['posts'], 'posts' );
		}
		if ( $counts['answers'] ) {
			$parts[] = Article::number( $counts['answers'] ) . "\u{00A0}" . Article::plural( $counts['answers'], array( 'ответ на вопрос', 'ответа на вопросы', 'ответов на вопросы' ) );
		}
		/* translators: Связка двух частей: «4 статьи и 3 ответа на вопросы». */
		return implode( ' ' . __( 'и', 'trust' ) . ' ', $parts );
	}

	/**
	 * ID опубликованных статей и ответов с тегом.
	 *
	 * @param \WP_Term $term Тег.
	 * @return int[]
	 */
	private static function object_ids( \WP_Term $term ): array {
		return array_map(
			'intval',
			get_posts(
				array(
					'post_type'      => self::TYPES['all'],
					'post_status'    => 'publish',
					'tag_id'         => $term->term_id,
					'posts_per_page' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- только ID материалов тега.
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			)
		);
	}

	/**
	 * Похожие теги: стоят вместе с этим в статьях и ответах; сначала те, что чаще, при равенстве —
	 * по алфавиту.
	 *
	 * @param \WP_Term $term  Тег.
	 * @param int      $limit Сколько тегов.
	 * @return \WP_Term[]
	 */
	public static function similar( \WP_Term $term, int $limit = self::SIMILAR ): array {
		$ids = self::object_ids( $term );
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
			if ( (int) $link->term_id === (int) $term->term_id ) {
				continue;
			}
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
	 * Рубрика тега: в ней больше всего статей с этим тегом (рубрика по умолчанию не считается).
	 *
	 * @param \WP_Term $term Тег.
	 */
	public static function rubric( \WP_Term $term ): ?\WP_Term {
		$ids = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'tag_id'         => $term->term_id,
				'posts_per_page' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- только ID статей тега.
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		if ( ! $ids ) {
			return null;
		}
		$default = (int) get_option( 'default_category' );
		$count   = array();
		$terms   = array();
		$links   = wp_get_object_terms( $ids, 'category', array( 'fields' => 'all_with_object_id' ) );
		foreach ( is_array( $links ) ? $links : array() as $link ) {
			if ( (int) $link->term_id === $default ) {
				continue;
			}
			$terms[ $link->term_id ] = $link;
			$count[ $link->term_id ] = ( $count[ $link->term_id ] ?? 0 ) + 1;
		}
		if ( ! $count ) {
			return null;
		}
		arsort( $count );
		return $terms[ array_key_first( $count ) ];
	}

	/**
	 * Карточки и пагинация (шаблон и ответ REST).
	 *
	 * @param \WP_Term   $term  Тег.
	 * @param \WP_Post[] $posts Материалы страницы.
	 * @param int        $page  Страница.
	 * @param int        $pages Всего страниц.
	 * @param string     $type  all, posts или answers.
	 */
	public static function list_html( \WP_Term $term, array $posts, int $page, int $pages, string $type ): string {
		if ( ! $posts ) {
			return '<p class="post-list__empty">' . esc_html__( 'Материалов пока нет.', 'trust' ) . '</p>';
		}
		ob_start();
		echo '<ul class="materials">';
		foreach ( $posts as $post ) {
			get_template_part( 'template-parts/cards/material', null, array( 'post' => $post ) );
		}
		echo '</ul>';
		$pager = Pagination::render(
			$page,
			$pages,
			self::ANCHOR,
			array( 'nav' => __( 'Страницы тега', 'trust' ) ),
			static fn( int $n ): string => self::url( $term, $n, $type )
		);
		echo $pager; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в Pagination::render().
		return (string) ob_get_clean();
	}

	/**
	 * REST: материалы тега с фильтром.
	 */
	public static function routes(): void {
		register_rest_route(
			'trust/v1',
			'/tags/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'rest_list' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'type' => array(
						'type'    => 'string',
						'enum'    => array_keys( self::TYPES ),
						'default' => 'all',
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
		$term = get_term( (int) $request['id'], 'post_tag' );
		if ( ! $term instanceof \WP_Term ) {
			return new \WP_Error( 'trust_tag_not_found', __( 'Тег не найден.', 'trust' ), array( 'status' => 404 ) );
		}
		$type  = isset( self::TYPES[ (string) $request['type'] ] ) ? (string) $request['type'] : 'all';
		$page  = (int) $request['page'];
		$query = new \WP_Query(
			array(
				'tag_id' => $term->term_id,
				'paged'  => $page,
			) + self::query_args( $type )
		);
		$pages = max( 1, (int) $query->max_num_pages );
		if ( $page > $pages ) {
			return new \WP_Error( 'trust_tag_page', __( 'Такой страницы нет.', 'trust' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response(
			array(
				'html'  => self::list_html( $term, $query->posts, $page, $pages, $type ),
				'page'  => $page,
				'pages' => $pages,
				'url'   => self::url( $term, $page, $type ),
			)
		);
	}

	/**
	 * Стили и скрипт страницы тега.
	 */
	public static function enqueue(): void {
		if ( ! is_tag() ) {
			return;
		}
		$term = get_queried_object();
		if ( ! $term instanceof \WP_Term ) {
			return;
		}
		wp_enqueue_style( 'trust-blog', Assets::url( 'assets/css/blog.css' ), array( 'trust-main', 'trust-pagination' ), Assets::version( 'assets/css/blog.css' ) );
		wp_enqueue_style( 'trust-tag', Assets::url( 'assets/css/tag.css' ), array( 'trust-blog' ), Assets::version( 'assets/css/tag.css' ) );
		wp_enqueue_script( 'trust-tag', Assets::url( 'assets/js/tag.js' ), array(), Assets::version( 'assets/js/tag.js' ), true );
		wp_add_inline_script(
			'trust-tag',
			'window.trustTag = ' . wp_json_encode(
				array(
					'rest' => rest_url( 'trust/v1/tags/' . $term->term_id ),
					'page' => max( 1, (int) get_query_var( 'paged' ) ),
				)
			) . ';',
			'before'
		);
	}
}
