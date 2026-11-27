<?php
/**
 * Ответы SEO-специалистов (ТЗ, п. 8.11; макет seo-vopros/vopros-seo.html, «Ответы SEO-специалистов»):
 * опубликованные вопросы, новые первыми, карточки и пагинация по адресам /страница/page/N/.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Список ответов для блока trust/answers-list.
 */
final class Answers_List {

	/**
	 * Ответов на странице по умолчанию.
	 */
	public const PER_PAGE = 20;

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'template_redirect', array( self::class, 'not_found' ) );
	}

	/**
	 * Сколько ответов на странице: поле блока «Ответов на странице» (6–40, по умолчанию 20).
	 *
	 * @param mixed $value Значение поля.
	 */
	public static function per_page( $value ): int {
		$per = (int) $value;
		return $per > 0 ? max( 6, min( 40, $per ) ) : self::PER_PAGE;
	}

	/**
	 * Страница ответов: ID и сколько всего страниц.
	 *
	 * @param int $paged Номер страницы.
	 * @param int $per   Ответов на странице.
	 * @return array{ids: int[], pages: int, total: int}
	 */
	public static function query( int $paged, int $per ): array {
		$query = new \WP_Query(
			array(
				'post_type'      => 'question',
				'post_status'    => 'publish',
				'has_password'   => false,
				'orderby'        => array(
					'date' => 'DESC',
					'ID'   => 'DESC',
				),
				'fields'         => 'ids',
				'posts_per_page' => max( 1, $per ),
				'paged'          => max( 1, $paged ),
			)
		);
		return array(
			'ids'   => array_map( 'intval', $query->posts ),
			'pages' => (int) $query->max_num_pages,
			'total' => (int) $query->found_posts,
		);
	}

	/**
	 * Страница списка за пределами пагинации (/voprosy/page/99/) — 404, а не пустой список.
	 */
	public static function not_found(): void {
		$paged = (int) get_query_var( 'paged' );
		$post  = get_queried_object();
		if ( $paged < 2 || ! is_page() || ! $post instanceof \WP_Post || ! has_block( 'trust/answers-list', $post ) ) {
			return;
		}
		$per = self::PER_PAGE;
		foreach ( parse_blocks( $post->post_content ) as $block ) {
			if ( 'trust/answers-list' === $block['blockName'] ) {
				$per = self::per_page( $block['attrs']['data']['per_page'] ?? 0 );
				break;
			}
		}
		if ( $paged > max( 1, self::query( 1, $per )['pages'] ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}

	/**
	 * Инициалы для кружка специалиста: «[Имя Фамилия]» → «ИФ».
	 *
	 * @param string $name Имя.
	 */
	public static function initials( string $name ): string {
		$words = preg_split( '/[\s\-]+/u', trim( (string) preg_replace( '/[^\p{L}\s\-]/u', '', $name ) ), -1, PREG_SPLIT_NO_EMPTY );
		$first = array_map( static fn( string $word ): string => mb_strtoupper( mb_substr( $word, 0, 1 ) ), array_slice( (array) $words, 0, 2 ) );
		return implode( '', $first );
	}

	/**
	 * Начало ответа: «Отрывок», иначе первый абзац ответа (до 32 слов).
	 *
	 * @param int $id Вопрос.
	 */
	public static function excerpt( int $id ): string {
		$excerpt = trim( (string) get_post_field( 'post_excerpt', $id ) );
		if ( '' !== $excerpt ) {
			return $excerpt;
		}
		foreach ( parse_blocks( (string) get_post_field( 'post_content', $id ) ) as $block ) {
			if ( 'core/paragraph' === $block['blockName'] ) {
				$text = trim( wp_strip_all_tags( (string) $block['innerHTML'] ) );
				if ( '' !== $text ) {
					return wp_trim_words( $text, 32, '…' );
				}
			}
		}
		return '';
	}

	/**
	 * Дата ответа: поле «Дата ответа», иначе дата публикации (Y-m-d).
	 *
	 * @param int $id Вопрос.
	 */
	public static function answered( int $id ): string {
		$date = (string) get_post_meta( $id, 'trust_answered_at', true );
		if ( preg_match( '/^(\d{4})-?(\d{2})-?(\d{2})$/', $date, $m ) ) {
			return $m[1] . '-' . $m[2] . '-' . $m[3];
		}
		return (string) get_the_date( 'Y-m-d', $id );
	}

	/**
	 * Дата по-русски: «2026-11-06» → «6 ноября 2026» (месяцы — Trust\Blog::MONTHS).
	 *
	 * @param string $date Дата Y-m-d.
	 */
	public static function date_text( string $date ): string {
		$parts = array_map( 'intval', explode( '-', $date ) );
		if ( 3 !== count( $parts ) ) {
			return $date;
		}
		return $parts[2] . ' ' . Blog::MONTHS[ max( 1, min( 12, $parts[1] ) ) - 1 ] . ' ' . $parts[0];
	}

	/**
	 * Карточка ответа: тема, «Спрашивает Имя», вопрос (H3), начало ответа, специалист (инициалы,
	 * имя), «ответ от» дата, «Читать ответ» (не помещается рядом с именем — «Ответ», затем только
	 * стрелка; style.css). Вся карточка — ссылка на ответ.
	 *
	 * @param int   $id     Вопрос.
	 * @param array $labels Подписи: asks, answered, more, short.
	 */
	public static function card( int $id, array $labels ): string {
		$topics = get_the_terms( $id, 'question_topic' );
		$asker  = trim( (string) get_post_meta( $id, 'trust_asker', true ) );
		$expert = (int) get_post_meta( $id, 'trust_expert', true );
		$expert = $expert && 'publish' === get_post_status( $expert ) ? $expert : 0;
		$date   = self::answered( $id );
		$text   = self::excerpt( $id );

		$html = '<li class="b-answers__item"><a class="b-answers__card" href="' . esc_url( get_permalink( $id ) ) . '">';
		if ( ( is_array( $topics ) && $topics ) || '' !== $asker ) {
			$html .= '<div class="b-answers__meta">';
			if ( is_array( $topics ) && $topics ) {
				$html .= '<span class="b-answers__topic">' . esc_html( $topics[0]->name ) . '</span>';
			}
			if ( '' !== $asker ) {
				$html .= '<span>' . esc_html( trim( $labels['asks'] . ' ' . $asker ) ) . '</span>';
			}
			$html .= '</div>';
		}
		$html .= '<h3 class="b-answers__title">' . esc_html( get_the_title( $id ) ) . '</h3>';
		if ( '' !== $text ) {
			$html .= '<p class="b-answers__excerpt">' . esc_html( $text ) . '</p>';
		}
		$html .= '<div class="b-answers__foot">';
		if ( $expert ) {
			$name  = get_the_title( $expert );
			$html .= '<span class="b-answers__avatar" aria-hidden="true">' . esc_html( self::initials( $name ) ) . '</span>';
		}
		$html .= '<span class="b-answers__who">';
		if ( $expert ) {
			$html .= '<span class="b-answers__name">' . esc_html( get_the_title( $expert ) ) . '</span>';
		}
		$html .= '<span class="b-answers__date">' . esc_html( $labels['answered'] ) . ' <time datetime="' . esc_attr( $date ) . '">' . esc_html( self::date_text( $date ) ) . '</time></span>';
		$html .= '</span><span class="b-answers__more" aria-hidden="true">';
		// Варианты от короткого к длинному: виден самый длинный, что помещается рядом с именем.
		foreach ( array_unique( array( '', (string) ( $labels['short'] ?? '' ), (string) $labels['more'] ) ) as $label ) {
			$html .= '<span class="b-answers__more-v"><span class="b-answers__more-in">' . ( '' !== $label ? '<span class="b-answers__more-text">' . esc_html( $label ) . '</span>' : '' ) . Icons::svg(
				'arrow-right',
				array(
					'size'         => 16,
					'stroke_width' => 2.2,
				)
			) . '</span></span>';
		}
		$html .= '</span></div></a></li>';
		return $html;
	}

	/**
	 * Пагинация «‹ Назад 1 2 3 4 … 7 Вперёд ›». Номера рядом с текущим помечены расстоянием до
	 * него (near — 1, mid — 2, extra — 3): на узких экранах дальние скрываются, вместо них — «…»
	 * (уже 640 px — без extra, уже 600 — без mid и подписей «Назад» / «Вперёд», уже 340 — только
	 * «‹ 1 … 7 ›»). Первая и последняя страницы видны всегда.
	 *
	 * @param int    $current Текущая страница.
	 * @param int    $pages   Всего страниц.
	 * @param string $anchor  Якорь списка (ссылки ведут сразу к ответам).
	 * @param array  $labels  Подписи: nav, prev, next, prev_aria, next_aria, page.
	 */
	public static function pagination( int $current, int $pages, string $anchor, array $labels ): string {
		if ( $pages < 2 ) {
			return '';
		}
		$url   = static fn( int $n ): string => esc_url( get_pagenum_link( $n, false ) . ( '' !== $anchor ? '#' . $anchor : '' ) );
		$arrow = static fn( string $icon ): string => Icons::svg(
			$icon,
			array(
				'size'         => 16,
				'stroke_width' => 2,
			)
		);
		$class = array(
			1 => 'is-near',
			2 => 'is-mid',
			3 => 'is-extra',
		);
		// «…» между первой (последней) страницей и ближайшим номером: всегда, если там пропущены
		// страницы, иначе — с того размера экрана, на котором скрывается соседний с ней номер
		// (на расстоянии 3, 2 или 1 от текущей).
		$dots = static function ( bool $gap, int $distance ): string {
			$when = array(
				3 => ' is-from-640',
				2 => ' is-from-600',
				1 => ' is-from-340',
			);
			if ( ! $gap && ! isset( $when[ $distance ] ) ) {
				return '';
			}
			return '<span class="b-answers__dots' . ( $gap ? '' : $when[ $distance ] ) . '" aria-hidden="true">…</span>';
		};

		$html = '<nav class="b-answers__pages" aria-label="' . esc_attr( $labels['nav'] ) . '">';
		if ( $current > 1 ) {
			$html .= '<a class="b-answers__arrow" href="' . $url( $current - 1 ) . '" aria-label="' . esc_attr( $labels['prev_aria'] ) . '">' . $arrow( 'arrow-left' ) . '<span class="b-answers__arrow-label">' . esc_html( $labels['prev'] ) . '</span></a>';
		} else {
			$html .= '<span class="b-answers__arrow is-disabled" aria-disabled="true">' . $arrow( 'arrow-left' ) . '<span class="b-answers__arrow-label">' . esc_html( $labels['prev'] ) . '</span></span>';
		}

		$from = max( 2, $current - 3 );
		$to   = min( $pages - 1, $current + 3 );
		$page = static function ( int $n ) use ( $current, $pages, $url, $class, $labels ): string {
			/* translators: %d: номер страницы. */
			$aria = esc_attr( sprintf( $labels['page'], $n ) );
			if ( $n === $current ) {
				return '<a class="b-answers__page is-current" href="' . $url( $n ) . '" aria-current="page" aria-label="' . $aria . '">' . $n . '</a>';
			}
			// Первая и последняя страницы видны всегда.
			$distance = 1 === $n || $pages === $n ? 0 : abs( $n - $current );
			$css      = $class[ $distance ] ?? '';
			return '<a class="b-answers__page' . ( '' !== $css ? ' ' . $css : '' ) . '" href="' . $url( $n ) . '" aria-label="' . $aria . '">' . $n . '</a>';
		};

		$html .= $page( 1 );
		$html .= $current > 2 ? $dots( $from > 2, $current - 2 ) : '';
		for ( $n = $from; $n <= $to; $n++ ) {
			$html .= $page( $n );
		}
		$html .= $current < $pages - 1 ? $dots( $to < $pages - 1, $pages - 1 - $current ) : '';
		$html .= $page( $pages );

		if ( $current < $pages ) {
			$html .= '<a class="b-answers__arrow is-next" href="' . $url( $current + 1 ) . '" aria-label="' . esc_attr( $labels['next_aria'] ) . '"><span class="b-answers__arrow-label">' . esc_html( $labels['next'] ) . '</span>' . $arrow( 'arrow-right' ) . '</a>';
		} else {
			$html .= '<span class="b-answers__arrow is-next is-disabled" aria-disabled="true"><span class="b-answers__arrow-label">' . esc_html( $labels['next'] ) . '</span>' . $arrow( 'arrow-right' ) . '</span>';
		}
		return $html . '</nav>';
	}
}
