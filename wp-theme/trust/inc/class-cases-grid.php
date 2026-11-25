<?php
/**
 * Кейсы портфолио (ТЗ, п. 8.10; макет portfolio-blog-404/portfolio.html): запросы с фильтрами
 * по услуге (service_type) и отрасли (industry), главный кейс, карточки и догрузка через REST —
 * GET /wp-json/trust/v1/cases.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Карточки кейсов для блока trust/cases-grid и ответа REST (одна разметка).
 */
final class Cases_Grid {

	/**
	 * Сколько кейсов отдаёт один запрос REST, не больше.
	 */
	public const MAX_PER = 24;

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
	}

	/**
	 * Маршрут REST: кейсы с фильтрами (следующая страница или новый выбор фильтров).
	 */
	public static function routes(): void {
		$slug = array(
			'type'              => 'string',
			'default'           => '',
			// Не 'sanitize_title' напрямую: REST передаёт вторым аргументом запрос.
			'sanitize_callback' => static fn( $value ): string => sanitize_title( (string) $value ),
		);
		register_rest_route(
			'trust/v1',
			'/cases',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'service'  => $slug,
					'industry' => $slug,
					'offset'   => array(
						'type'    => 'integer',
						'default' => 0,
						'minimum' => 0,
					),
					'per'      => array(
						'type'    => 'integer',
						'default' => 9,
						'minimum' => 1,
						'maximum' => self::MAX_PER,
					),
					// Без фильтров главный кейс выводится отдельно — в сетке его нет.
					'skip'     => array(
						'type'    => 'integer',
						'default' => 0,
						'minimum' => 0,
					),
				),
			)
		);
	}

	/**
	 * Ответ REST: разметка карточек, сколько отдано и сколько всего подходит.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 */
	public static function rest( \WP_REST_Request $request ): \WP_REST_Response {
		$skip = (int) $request['skip'];
		$page = self::query( (string) $request['service'], (string) $request['industry'], (int) $request['offset'], (int) $request['per'], $skip ? array( $skip ) : array() );
		$html = '';
		foreach ( $page['ids'] as $id ) {
			$html .= self::card( $id );
		}
		return new \WP_REST_Response(
			array(
				'html'  => $html,
				'count' => count( $page['ids'] ),
				'total' => $page['total'],
			)
		);
	}

	/**
	 * Условия запроса опубликованных кейсов (новые первыми).
	 *
	 * @param string $service  Услуга (slug service_type) или пусто.
	 * @param string $industry Отрасль (slug industry) или пусто.
	 * @param int[]  $exclude  Не включать (главный кейс).
	 */
	private static function args( string $service, string $industry, array $exclude = array() ): array {
		$args = array(
			'post_type'    => 'case',
			'post_status'  => 'publish',
			// Кейс под паролем в списке не показывается: в карточке — анонс и результаты.
			'has_password' => false,
			'orderby'      => array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			),
		);
		$tax  = array();
		foreach ( array(
			'service_type' => $service,
			'industry'     => $industry,
		) as $taxonomy => $slug ) {
			if ( '' !== $slug ) {
				$tax[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => array( $slug ),
				);
			}
		}
		if ( $tax ) {
			$args['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- фильтры портфолио.
		}
		if ( $exclude ) {
			$args['post__not_in'] = $exclude; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- один главный кейс.
		}
		return $args;
	}

	/**
	 * Страница кейсов: ID и общее число подходящих.
	 *
	 * @param string $service  Услуга или пусто.
	 * @param string $industry Отрасль или пусто.
	 * @param int    $offset   Сколько пропустить.
	 * @param int    $limit    Сколько взять.
	 * @param int[]  $exclude  Не включать.
	 * @return array{ids: int[], total: int}
	 */
	public static function query( string $service, string $industry, int $offset, int $limit, array $exclude = array() ): array {
		$query = new \WP_Query(
			self::args( $service, $industry, $exclude ) + array(
				'fields'         => 'ids',
				'offset'         => max( 0, $offset ),
				'posts_per_page' => max( 1, $limit ),
			)
		);
		return array(
			'ids'   => array_map( 'intval', $query->posts ),
			'total' => (int) $query->found_posts,
		);
	}

	/**
	 * Главный кейс: самый новый из отмеченных «Главный кейс» (0 — нет).
	 */
	public static function featured(): int {
		$ids = get_posts(
			self::args( '', '' ) + array(
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'meta_key'       => Blog::FEATURED, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- один кейс.
				'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- один кейс.
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Все кейсы для пересчёта фильтров в браузере: услуги и отрасли каждого (slug), а также
	 * названия услуг и отраслей, у которых есть кейсы, — в порядке терминов в админке.
	 *
	 * @return array{cases: array<int, array{s: string[], n: string[]}>, services: array<string, string>, industries: array<string, string>}
	 */
	public static function index(): array {
		$ids    = get_posts(
			self::args( '', '' ) + array(
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			)
		);
		$result = array(
			'cases'      => array(),
			'services'   => array(),
			'industries' => array(),
		);
		foreach ( $ids as $id ) {
			$result['cases'][ (int) $id ] = array(
				's' => array(),
				'n' => array(),
			);
		}
		if ( ! $ids ) {
			return $result;
		}
		foreach ( array(
			'service_type' => array( 's', 'services' ),
			'industry'     => array( 'n', 'industries' ),
		) as $taxonomy => list( $short, $list ) ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'object_ids' => $ids,
					'hide_empty' => false,
					'orderby'    => 'term_id',
					'fields'     => 'all_with_object_id',
				)
			);
			foreach ( is_array( $terms ) ? $terms : array() as $term ) {
				$result['cases'][ (int) $term->object_id ][ $short ][] = $term->slug;
				$result[ $list ][ $term->slug ]                        = $term->name;
			}
		}
		return $result;
	}

	/**
	 * Обложка кейса: «Изображение записи», иначе скриншот сайта (0 — нет).
	 *
	 * @param int $id Кейс.
	 */
	public static function cover( int $id ): int {
		$thumb = (int) get_post_thumbnail_id( $id );
		return $thumb ? $thumb : Cases::screenshot( $id );
	}

	/**
	 * Названия терминов кейса.
	 *
	 * @param int    $id       Кейс.
	 * @param string $taxonomy Таксономия.
	 * @return string[]
	 */
	private static function terms( int $id, string $taxonomy ): array {
		$terms = get_the_terms( $id, $taxonomy );
		return is_array( $terms ) ? wp_list_pluck( $terms, 'name' ) : array();
	}

	/**
	 * Результаты кейса: значение и подпись (поле «Результаты»).
	 *
	 * @param int $id    Кейс.
	 * @param int $limit Сколько.
	 * @return array<int, array{0: string, 1: string}>
	 */
	public static function results( int $id, int $limit ): array {
		$rows = array();
		foreach ( (array) get_field( 'trust_results', $id ) as $row ) {
			$value = trim( (string) ( $row['value'] ?? '' ) );
			if ( '' !== $value && count( $rows ) < $limit ) {
				$rows[] = array( $value, trim( (string) ( $row['label'] ?? '' ) ) );
			}
		}
		return $rows;
	}

	/**
	 * Картинка кейса (обложка) или пустая плашка; alt — из медиатеки, иначе по отрасли.
	 *
	 * @param int    $id    Кейс.
	 * @param string $sizes Атрибут sizes.
	 * @param string $css   CSS-класс.
	 * @param bool   $lazy  Ленивая загрузка.
	 */
	private static function image( int $id, string $sizes, string $css, bool $lazy ): string {
		$cover = self::cover( $id );
		if ( ! $cover ) {
			return '<span class="' . esc_attr( $css ) . ' ' . esc_attr( $css ) . '--empty"></span>';
		}
		$industry = self::terms( $id, 'industry' );
		/* translators: %s: отрасль кейса. */
		$fallback = $industry ? sprintf( __( 'Главный экран сайта: %s', 'trust' ), mb_strtolower( $industry[0] ) ) : get_the_title( $id );
		return Blocks::image(
			$cover,
			'large',
			array(
				'class'   => $css,
				'sizes'   => $sizes,
				'loading' => $lazy ? 'lazy' : 'eager',
			),
			Blocks::alt_text( $cover, '', $fallback )
		);
	}

	/**
	 * Карточка кейса в сетке: обложка с отраслью, услуги, заголовок (H3), анонс, два результата,
	 * «Смотреть кейс». Вся карточка — ссылка на кейс.
	 *
	 * @param int $id Кейс.
	 */
	public static function card( int $id ): string {
		$industry = self::terms( $id, 'industry' );
		$services = self::terms( $id, 'service_type' );
		$excerpt  = trim( (string) get_post_field( 'post_excerpt', $id ) );
		$html     = '<li class="b-cases__item"><a class="b-cases__card" href="' . esc_url( get_permalink( $id ) ) . '">';
		$html    .= '<span class="b-cases__cover">' . self::image( $id, '(max-width: 640px) 100vw, (max-width: 1023px) 50vw, 384px', 'b-cases__img', true );
		if ( $industry ) {
			$html .= '<span class="b-cases__niche">' . esc_html( $industry[0] ) . '</span>';
		}
		$html .= '</span><span class="b-cases__body">';
		if ( $services ) {
			$html .= '<span class="b-cases__tags">';
			foreach ( $services as $service ) {
				$html .= '<span>' . esc_html( $service ) . '</span>';
			}
			$html .= '</span>';
		}
		$html .= '<h3 class="b-cases__title">' . esc_html( get_the_title( $id ) ) . '</h3>';
		if ( '' !== $excerpt ) {
			$html .= '<span class="b-cases__desc">' . esc_html( $excerpt ) . '</span>';
		}
		$results = self::results( $id, 2 );
		if ( $results ) {
			$html .= '<span class="b-cases__stats">';
			foreach ( $results as list( $value, $label ) ) {
				$html .= '<span><span class="b-cases__value">' . esc_html( $value ) . '</span><span class="b-cases__stat-label">' . esc_html( $label ) . '</span></span>';
			}
			$html .= '</span>';
		}
		$html .= '<span class="b-cases__go">' . esc_html__( 'Смотреть кейс', 'trust' ) . Icons::svg(
			'arrow-right',
			array(
				'size'         => 16,
				'stroke_width' => 2.2,
			)
		) . '</span>';
		return $html . '</span></a></li>';
	}

	/**
	 * Главный кейс крупной карточкой: скриншот с плашкой «Главный кейс», отрасль и услуги,
	 * заголовок (H3), анонс, до 4 результатов, кнопка «Смотреть кейс».
	 *
	 * @param int    $id    Кейс.
	 * @param string $badge Текст плашки.
	 * @param string $more  Текст кнопки.
	 */
	public static function featured_card( int $id, string $badge, string $more ): string {
		$industry = self::terms( $id, 'industry' );
		$services = self::terms( $id, 'service_type' );
		$excerpt  = trim( (string) get_post_field( 'post_excerpt', $id ) );
		$html     = '<a class="b-cases__feat" href="' . esc_url( get_permalink( $id ) ) . '" data-cases-featured>';
		$html    .= '<span class="b-cases__feat-cover">' . self::image( $id, '(max-width: 1023px) 100vw, 640px', 'b-cases__feat-img', false );
		$html    .= '<span class="b-cases__badge">' . esc_html( $badge ) . '</span></span>';
		$html    .= '<span class="b-cases__feat-body"><span class="b-cases__pills">';
		foreach ( $industry as $name ) {
			$html .= '<span class="b-cases__pill b-cases__pill--niche">' . esc_html( $name ) . '</span>';
		}
		foreach ( $services as $name ) {
			$html .= '<span class="b-cases__pill">' . esc_html( $name ) . '</span>';
		}
		$html .= '</span><h3 class="b-cases__feat-title">' . esc_html( get_the_title( $id ) ) . '</h3>';
		if ( '' !== $excerpt ) {
			$html .= '<span class="b-cases__feat-desc">' . esc_html( $excerpt ) . '</span>';
		}
		$results = self::results( $id, 4 );
		if ( $results ) {
			$html .= '<span class="b-cases__feat-stats">';
			foreach ( $results as list( $value, $label ) ) {
				$html .= '<span><span class="b-cases__feat-value">' . esc_html( $value ) . '</span><span class="b-cases__stat-label">' . esc_html( $label ) . '</span></span>';
			}
			$html .= '</span>';
		}
		$html .= '<span class="b-cases__feat-more">' . esc_html( $more ) . Icons::svg(
			'arrow-right',
			array(
				'size'         => 18,
				'stroke_width' => 2.2,
			)
		) . '</span>';
		return $html . '</span></a>';
	}

	/**
	 * «1 кейс», «2 кейса», «5 кейсов».
	 *
	 * @param int $n Число.
	 */
	public static function word( int $n ): string {
		$mod10  = $n % 10;
		$mod100 = $n % 100;
		if ( 1 === $mod10 && 11 !== $mod100 ) {
			$word = __( 'кейс', 'trust' );
		} elseif ( $mod10 >= 2 && $mod10 <= 4 && ( $mod100 < 12 || $mod100 > 14 ) ) {
			$word = __( 'кейса', 'trust' );
		} else {
			$word = __( 'кейсов', 'trust' );
		}
		return $n . ' ' . $word;
	}
}
