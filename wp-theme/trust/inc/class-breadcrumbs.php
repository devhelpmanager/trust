<?php
/**
 * Хлебные крошки (ТЗ, раздел 6.4).
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Цепочки строятся автоматически:
 * - страницы — по иерархии; если у страницы нет родителя, перед ней — «Раздел в хлебных
 *   крошках» (например, «О нас» у «Команды»);
 * - статьи — «Главная / Блог / Рубрика / Статья»;
 * - кейсы, ответы, сотрудники — через страницу-список с тем же адресом, что ярлык типа
 *   («Главная / Портфолио / Кейс», «Главная / SEO / Вопросы по SEO / Вопрос»,
 *   «Главная / Сотрудники / Имя»);
 * - рубрика и тег — «Главная / Блог / …»; на главной крошек нет.
 * Название пункта — поле «Название в хлебных крошках», иначе заголовок. Те же пункты —
 * в микроразметке BreadcrumbList (шаг 3.4).
 */
final class Breadcrumbs {

	/**
	 * Пункты текущей страницы (кэш на запрос).
	 *
	 * @var array|null
	 */
	private static ?array $items = null;

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'acf/prepare_field/key=' . Fields::key( 'seo', 'trust_crumb_section' ), array( self::class, 'only_pages' ) );
		add_filter( 'acf/prepare_field/key=' . Fields::key( 'seo', 'trust_crumbs_accordion' ), array( self::class, 'not_terms' ) );
		add_filter( 'acf/prepare_field/key=' . Fields::key( 'seo', 'trust_crumb_title' ), array( self::class, 'not_terms' ) );
	}

	/**
	 * Название записи в крошках: поле «Название в хлебных крошках», иначе заголовок.
	 *
	 * @param \WP_Post $post Запись.
	 */
	public static function title( \WP_Post $post ): string {
		$own = trim( (string) get_post_meta( $post->ID, 'trust_crumb_title', true ) );
		return '' !== $own ? $own : html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Страница блога: «Страница записей» из «Настроек → Чтение», иначе страница с адресом,
	 * с которого начинаются адреса статей (/blog/ при /blog/%postname%/).
	 */
	public static function blog_page(): ?\WP_Post {
		$id = (int) get_option( 'page_for_posts' );
		if ( $id ) {
			return get_post( $id );
		}
		$prefix = trim( (string) strtok( trim( (string) get_option( 'permalink_structure' ), '/' ), '/' ) );
		if ( '' === $prefix || str_contains( $prefix, '%' ) ) {
			return null;
		}
		return get_page_by_path( $prefix );
	}

	/**
	 * Пункт крошек из записи.
	 *
	 * @param \WP_Post $post Запись.
	 */
	private static function from_post( \WP_Post $post ): array {
		return array(
			'title' => self::title( $post ),
			'url'   => (string) get_permalink( $post ),
			'id'    => (int) $post->ID,
		);
	}

	/**
	 * Страница и её родители сверху вниз.
	 *
	 * @param \WP_Post $page Страница.
	 */
	private static function page_chain( \WP_Post $page ): array {
		$chain = array();
		foreach ( array_reverse( get_post_ancestors( $page ) ) as $ancestor ) {
			$chain[] = self::from_post( get_post( $ancestor ) );
		}
		$chain[] = self::from_post( $page );
		return $chain;
	}

	/**
	 * Пункты текущей страницы. Последний — текущая страница (current = true).
	 *
	 * @return array<int, array{title: string, url: string, id?: int, current?: bool}>
	 */
	public static function items(): array {
		if ( null !== self::$items ) {
			return self::$items;
		}
		self::$items = self::build();
		return self::$items;
	}

	/**
	 * Строит цепочку.
	 */
	private static function build(): array {
		if ( is_front_page() || is_404() ) {
			return array();
		}

		$trail = array(
			array(
				'title' => __( 'Главная', 'trust' ),
				'url'   => home_url( '/' ),
			),
		);
		$blog  = self::blog_page();

		if ( is_home() ) {
			if ( $blog ) {
				$trail = array_merge( $trail, self::page_chain( $blog ) );
			}
		} elseif ( is_singular( 'page' ) ) {
			$page = get_queried_object();
			if ( ! $page->post_parent ) {
				$section = get_post_meta( $page->ID, 'trust_crumb_section', true );
				if ( is_array( $section ) && ! empty( $section['url'] ) && ! empty( $section['title'] ) ) {
					$trail[] = array(
						'title' => (string) $section['title'],
						'url'   => (string) $section['url'],
					);
				}
			}
			$trail = array_merge( $trail, self::page_chain( $page ) );
		} elseif ( is_singular( 'post' ) ) {
			if ( $blog ) {
				$trail = array_merge( $trail, self::page_chain( $blog ) );
			}
			$category = self::primary_category( get_queried_object() );
			if ( $category ) {
				foreach ( array_reverse( get_ancestors( $category->term_id, 'category', 'taxonomy' ) ) as $ancestor ) {
					$trail[] = self::from_term( get_term( $ancestor, 'category' ) );
				}
				$trail[] = self::from_term( $category );
			}
			$trail[] = self::from_post( get_queried_object() );
		} elseif ( is_singular( array( 'case', 'question', 'team' ) ) ) {
			$post = get_queried_object();
			$list = get_page_by_path( Urls::slug( $post->post_type ) );
			if ( $list ) {
				$chain = self::page_chain( $list );
				// «Главная / Сотрудники / Имя» (ТЗ): у сотрудников — название раздела, а не страницы «Команда».
				if ( 'team' === $post->post_type ) {
					$chain[ count( $chain ) - 1 ]['title'] = get_post_type_object( 'team' )->labels->name;
				}
				$trail = array_merge( $trail, $chain );
			}
			$trail[] = self::from_post( $post );
		} elseif ( is_singular() ) {
			$trail[] = self::from_post( get_queried_object() );
		} elseif ( is_category() || is_tag() ) {
			if ( $blog ) {
				$trail = array_merge( $trail, self::page_chain( $blog ) );
			}
			$term = get_queried_object();
			foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $ancestor ) {
				$trail[] = self::from_term( get_term( $ancestor, $term->taxonomy ) );
			}
			$trail[] = self::from_term( $term );
		} elseif ( is_search() ) {
			$trail[] = array(
				'title' => __( 'Результаты поиска', 'trust' ),
				'url'   => (string) get_search_link(),
			);
		} else {
			$trail[] = array(
				'title' => wp_strip_all_tags( (string) get_the_archive_title() ),
				'url'   => '',
			);
		}

		$last                      = count( $trail ) - 1;
		$trail[ $last ]['current'] = true;
		return $trail;
	}

	/**
	 * Пункт крошек из рубрики или тега (тег — с #, как в макете).
	 *
	 * @param \WP_Term $term Термин.
	 */
	private static function from_term( \WP_Term $term ): array {
		$link = get_term_link( $term );
		return array(
			'title' => ( 'post_tag' === $term->taxonomy ? '#' : '' ) . $term->name,
			'url'   => is_wp_error( $link ) ? '' : $link,
		);
	}

	/**
	 * Рубрика статьи для крошек: первая, кроме рубрики по умолчанию, если есть другие.
	 *
	 * @param \WP_Post $post Статья.
	 */
	public static function primary_category( \WP_Post $post ): ?\WP_Term {
		$categories = get_the_category( $post->ID );
		if ( ! $categories ) {
			return null;
		}
		$default = (int) get_option( 'default_category' );
		foreach ( $categories as $category ) {
			if ( (int) $category->term_id !== $default ) {
				return $category;
			}
		}
		return $categories[0];
	}

	/**
	 * ID записей в цепочке, кроме текущей: по ним подсвечивается раздел меню
	 * (у кейса — «Портфолио», у ответа — «SEO» и «Вопросы по SEO», у статьи — «Блог»).
	 *
	 * @return int[]
	 */
	public static function trail_ids(): array {
		$ids = array();
		foreach ( self::items() as $item ) {
			if ( ! empty( $item['id'] ) && empty( $item['current'] ) ) {
				$ids[] = (int) $item['id'];
			}
		}
		return $ids;
	}

	/**
	 * Выводит крошки (ссылки не короче 44 px по высоте — как в макетах).
	 *
	 * @param string $extra_class Дополнительный класс.
	 */
	public static function render( string $extra_class = '' ): void {
		$items = self::items();
		if ( count( $items ) < 2 ) {
			return;
		}

		echo '<nav class="breadcrumbs' . ( $extra_class ? ' ' . esc_attr( $extra_class ) : '' ) . '" aria-label="' . esc_attr__( 'Хлебные крошки', 'trust' ) . '"><ol class="breadcrumbs__list">';
		foreach ( $items as $i => $item ) {
			if ( $i > 0 ) {
				echo '<li class="breadcrumbs__sep" aria-hidden="true">/</li>';
			}
			if ( ! empty( $item['current'] ) ) {
				echo '<li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page">' . esc_html( $item['title'] ) . '</li>';
			} elseif ( '' !== $item['url'] ) {
				echo '<li class="breadcrumbs__item"><a class="breadcrumbs__link" href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['title'] ) . '</a></li>';
			} else {
				echo '<li class="breadcrumbs__item">' . esc_html( $item['title'] ) . '</li>';
			}
		}
		echo '</ol></nav>';
	}

	/**
	 * Крошки в превью блока первого экрана в редакторе: «Главная / … / страница» без ссылок
	 * (в редакторе нет текущей страницы сайта, по которой строится обычная цепочка).
	 *
	 * @param int    $post_id     Страница.
	 * @param string $extra_class Дополнительный класс.
	 */
	public static function render_preview( int $post_id, string $extra_class = '' ): void {
		$post  = get_post( $post_id );
		$items = array( array( 'title' => __( 'Главная', 'trust' ) ) );
		if ( $post ) {
			$items = array_merge( $items, 'page' === $post->post_type ? self::page_chain( $post ) : array( self::from_post( $post ) ) );
		}
		echo '<nav class="breadcrumbs' . ( $extra_class ? ' ' . esc_attr( $extra_class ) : '' ) . '" aria-label="' . esc_attr__( 'Хлебные крошки', 'trust' ) . '"><ol class="breadcrumbs__list">';
		$last = count( $items ) - 1;
		foreach ( $items as $i => $item ) {
			if ( $i > 0 ) {
				echo '<li class="breadcrumbs__sep" aria-hidden="true">/</li>';
			}
			$current = $i === $last && $i > 0;
			printf(
				'<li class="breadcrumbs__item%s">%s</li>',
				$current ? ' breadcrumbs__item--current' : '',
				$current ? esc_html( $item['title'] ) : '<span class="breadcrumbs__link">' . esc_html( $item['title'] ) . '</span>'
			);
		}
		echo '</ol></nav>';
	}

	/**
	 * «Раздел в хлебных крошках» — только у страниц.
	 *
	 * @param array|false $field Поле SCF.
	 * @return array|false
	 */
	public static function only_pages( $field ) {
		$post = get_post();
		return $post && 'page' === $post->post_type ? $field : false;
	}

	/**
	 * Поля крошек не нужны рубрикам и тегам: их название и так короткое.
	 *
	 * @param array|false $field Поле SCF.
	 * @return array|false
	 */
	public static function not_terms( $field ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && in_array( $screen->base, array( 'term', 'edit-tags' ), true ) ? false : $field;
	}
}
