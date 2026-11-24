<?php
/**
 * Список текстовых отзывов «Что говорят клиенты» (ТЗ, п. 8.7): запрос, число отзывов по услугам,
 * карточка и догрузка через REST — GET /wp-json/trust/v1/reviews.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Карточки отзывов для блока trust/reviews-list и ответа REST (одна разметка).
 */
final class Reviews_List {

	/**
	 * Сколько отзывов отдаёт один запрос REST, не больше.
	 */
	public const MAX_PER = 24;

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
	}

	/**
	 * Маршрут REST: следующие отзывы списка (с фильтром по услуге).
	 */
	public static function routes(): void {
		register_rest_route(
			'trust/v1',
			'/reviews',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'service' => array(
						'type'              => 'string',
						'default'           => '',
						// Не 'sanitize_title' напрямую: REST передаёт вторым аргументом запрос, и для
						// пустой услуги sanitize_title() вернула бы его вместо строки.
						'sanitize_callback' => static fn( $value ): string => sanitize_title( (string) $value ),
					),
					'offset'  => array(
						'type'    => 'integer',
						'default' => 0,
						'minimum' => 0,
					),
					'per'     => array(
						'type'    => 'integer',
						'default' => 6,
						'minimum' => 1,
						'maximum' => self::MAX_PER,
					),
				),
			)
		);
	}

	/**
	 * Ответ REST: разметка карточек, сколько отдано и сколько всего.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 */
	public static function rest( \WP_REST_Request $request ): \WP_REST_Response {
		$service = (string) $request['service'];
		$page    = self::query( $service, (int) $request['offset'], (int) $request['per'] );
		$html    = '';
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
	 * Условия запроса текстовых отзывов (вид «Текстовый» или вид не указан).
	 *
	 * @param string $service Услуга (slug термина service_type) или пусто — все.
	 */
	private static function args( string $service ): array {
		$args = array(
			'post_type'   => 'review',
			'post_status' => 'publish',
			'orderby'     => array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			),
			'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- только текстовые отзывы.
				'relation' => 'OR',
				array(
					'key'   => 'trust_kind',
					'value' => 'text',
				),
				array(
					'key'     => 'trust_kind',
					'compare' => 'NOT EXISTS',
				),
			),
		);
		if ( '' !== $service ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- фильтр по услуге.
				array(
					'taxonomy' => 'service_type',
					'field'    => 'slug',
					'terms'    => array( $service ),
				),
			);
		}
		return $args;
	}

	/**
	 * Страница отзывов: ID и общее число.
	 *
	 * @param string $service Услуга или пусто.
	 * @param int    $offset  Сколько пропустить.
	 * @param int    $limit   Сколько взять.
	 * @return array{ids: int[], total: int}
	 */
	public static function query( string $service, int $offset, int $limit ): array {
		$query = new \WP_Query(
			self::args( $service ) + array(
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
	 * Число текстовых отзывов по услугам — в порядке услуг в админке; услуги без отзывов не
	 * выводятся.
	 *
	 * @return array{total: int, services: array<string, array{name: string, count: int}>}
	 */
	public static function counts(): array {
		$ids      = get_posts(
			self::args( '' ) + array(
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			)
		);
		$services = array();
		if ( $ids ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'service_type',
					'object_ids' => $ids,
					'hide_empty' => false,
					'orderby'    => 'term_id',
					'fields'     => 'all_with_object_id',
				)
			);
			foreach ( is_array( $terms ) ? $terms : array() as $term ) {
				$services[ $term->slug ] = array(
					'name'  => $term->name,
					'count' => ( $services[ $term->slug ]['count'] ?? 0 ) + 1,
				);
			}
		}
		return array(
			'total'    => count( $ids ),
			'services' => $services,
		);
	}

	/**
	 * Звёзды оценки (1–5); 0 — оценки нет.
	 *
	 * @param int $rating Оценка.
	 */
	public static function stars( int $rating ): string {
		if ( $rating < 1 || $rating > 5 ) {
			return '';
		}
		$html = '';
		for ( $i = 1; $i <= 5; $i++ ) {
			$html .= '<svg width="18" height="18" viewBox="0 0 24 24" class="' . ( $i <= $rating ? 'is-on' : 'is-off' ) . '" aria-hidden="true" focusable="false"><path d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.5l1.2-6.5L2.5 9.4l6.6-.9z"/></svg>';
		}
		/* translators: %d: оценка от 1 до 5. */
		return '<span class="b-rlist__stars" role="img" aria-label="' . esc_attr( sprintf( __( 'Оценка: %d из 5', 'trust' ), $rating ) ) . '">' . $html . '</span>';
	}

	/**
	 * Карточка отзыва: автор (фото или силуэт), должность и компания, оценка, услуга, текст,
	 * результаты, дата, «Оригинал письма» (скан на весь экран) или «Оригинал отзыва» (ссылка),
	 * «Кейс проекта» (кейс, к которому привязан отзыв).
	 *
	 * @param int $id Отзыв.
	 */
	public static function card( int $id ): string {
		$name    = trim( (string) get_post_meta( $id, 'trust_name', true ) );
		$name    = '' !== $name ? $name : Reviews::author_line( $id, true );
		$role    = implode( ', ', array_filter( array( trim( (string) get_post_meta( $id, 'trust_position', true ) ), trim( (string) get_post_meta( $id, 'trust_company', true ) ) ), 'strlen' ) );
		$text    = trim( (string) get_post_meta( $id, 'trust_text', true ) );
		$terms   = get_the_terms( $id, 'service_type' );
		$service = is_array( $terms ) && $terms ? $terms[0]->name : '';
		$results = array_slice( array_filter( array_map( static fn( $row ) => trim( (string) ( $row['text'] ?? '' ) ), (array) get_field( 'trust_results', $id ) ), 'strlen' ), 0, 3 );
		$date    = (string) get_post_meta( $id, 'trust_date', true );
		$time    = $date ? strtotime( $date ) : false;
		$time    = $time ? $time : (int) get_post_time( 'U', false, $id );
		$photo   = (int) get_post_meta( $id, 'trust_photo', true );
		$letter  = (int) get_post_meta( $id, 'trust_letter', true );
		$scan    = $letter ? (string) wp_get_attachment_image_url( $letter, 'full' ) : '';
		$source  = trim( (string) get_post_meta( $id, 'trust_source', true ) );
		$case    = Reviews::case_of( $id );
		$who     = Reviews::author_line( $id, true );
		$arrow   = Icons::svg(
			'arrow-right',
			array(
				'size'         => 15,
				'stroke_width' => 2.2,
			)
		);
		$avatar  = $photo ? (string) wp_get_attachment_image(
			$photo,
			'thumbnail',
			false,
			array(
				'class'   => 'b-rlist__photo',
				'alt'     => '',
				'loading' => 'lazy',
			)
		) : '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false"><circle cx="12" cy="8.5" r="3.8"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/></svg>';
		$links   = '';
		if ( '' !== $scan ) {
			$links .= sprintf(
				'<a class="b-rlist__link" href="%1$s" data-trust-lightbox data-lightbox-caption="%2$s"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/></svg>%3$s<span class="screen-reader-text">: %4$s</span></a>',
				esc_url( $scan ),
				/* translators: %s: автор отзыва. */
				esc_attr( sprintf( __( 'Благодарственное письмо — %s', 'trust' ), $who ) ),
				esc_html__( 'Оригинал письма', 'trust' ),
				esc_html( $who )
			);
		} elseif ( '' !== $source ) {
			$links .= sprintf(
				'<a class="b-rlist__link" href="%1$s" target="_blank" rel="noopener">%2$s%3$s<span class="screen-reader-text">: %4$s (%5$s)</span></a>',
				esc_url( $source ),
				esc_html__( 'Оригинал отзыва', 'trust' ),
				Icons::svg(
					'arrow-up-right',
					array(
						'size'         => 15,
						'stroke_width' => 2.2,
					)
				),
				esc_html( $who ),
				esc_html__( 'откроется в новой вкладке', 'trust' )
			);
		}
		if ( $case ) {
			$links .= sprintf(
				'<a class="b-rlist__link" href="%1$s">%2$s%3$s<span class="screen-reader-text">: %4$s</span></a>',
				esc_url( (string) get_permalink( $case ) ),
				esc_html__( 'Кейс проекта', 'trust' ),
				$arrow,
				esc_html( get_the_title( $case ) )
			);
		}

		ob_start();
		?>
		<li class="b-rlist__card">
			<div class="b-rlist__top">
				<div class="b-rlist__author">
					<span class="b-rlist__avatar" aria-hidden="true"><?php echo $avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка WordPress или постоянный SVG. ?></span>
					<span class="b-rlist__who">
						<span class="b-rlist__name"><?php echo esc_html( $name ); ?></span>
						<?php if ( '' !== $role ) : ?>
							<span class="b-rlist__role"><?php echo esc_html( $role ); ?></span>
						<?php endif; ?>
					</span>
				</div>
				<?php echo self::stars( (int) get_post_meta( $id, 'trust_rating', true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- собрано с экранированием в stars(). ?>
			</div>
			<?php if ( '' !== $service ) : ?>
				<span class="b-rlist__service"><?php echo esc_html( $service ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $text ) : ?>
				<div class="b-rlist__text"><?php echo wp_kses_post( wpautop( esc_html( $text ) ) ); ?></div>
			<?php endif; ?>
			<?php if ( $results ) : ?>
				<ul class="b-rlist__results" aria-label="<?php esc_attr_e( 'Результаты', 'trust' ); ?>">
					<?php foreach ( $results as $result ) : ?>
						<li><?php echo esc_html( $result ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div class="b-rlist__foot">
				<time class="b-rlist__date" datetime="<?php echo esc_attr( wp_date( 'Y-m-d', $time ) ); ?>"><?php echo esc_html( wp_date( 'd.m.Y', $time ) ); ?></time>
				<?php if ( '' !== $links ) : ?>
					<div class="b-rlist__links"><?php echo $links; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- собрано с экранированием выше. ?></div>
				<?php endif; ?>
			</div>
		</li>
		<?php
		return (string) ob_get_clean();
	}
}
