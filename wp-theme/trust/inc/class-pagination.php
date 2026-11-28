<?php
/**
 * Пагинация списков (макеты seo-vopros/vopros-seo.html, seo-blog/blog.html): «‹ Назад 1 2 3 4 … 7
 * Вперёд ›». На узких экранах номера сокращаются (уже 640 px — без дальних, уже 600 — без подписей
 * «Назад» / «Вперёд», уже 340 — «‹ 1 … 7 ›»); первая и последняя страницы видны всегда. Стиль —
 * assets/css/pagination.css (trust-pagination). Списки: «Ответы SEO-специалистов», блог, далее —
 * рубрика, тег, автор, поиск.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Разметка пагинации.
 */
final class Pagination {

	/**
	 * Регистрирует хуки: стиль trust-pagination (его указывают блоки и шаблоны списков).
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_style' ), 5 );
	}

	/**
	 * Стиль пагинации.
	 */
	public static function register_style(): void {
		wp_register_style( 'trust-pagination', Assets::url( 'assets/css/pagination.css' ), array(), Assets::version( 'assets/css/pagination.css' ) );
	}

	/**
	 * Подписи по умолчанию; nav — название навигации («Страницы блога»).
	 */
	public static function labels(): array {
		return array(
			'nav'       => __( 'Страницы', 'trust' ),
			'prev'      => __( 'Назад', 'trust' ),
			'next'      => __( 'Вперёд', 'trust' ),
			'prev_aria' => __( 'Предыдущая страница', 'trust' ),
			'next_aria' => __( 'Следующая страница', 'trust' ),
			/* translators: %d: номер страницы. */
			'page'      => __( 'Страница %d', 'trust' ),
		);
	}

	/**
	 * Пагинация: ссылки get_pagenum_link() (/page/N/) с якорем списка. Номера рядом с текущим
	 * помечены расстоянием до него (near — 1, mid — 2, extra — 3): на узких экранах дальние
	 * скрываются, вместо них — «…». Одна страница — ''.
	 *
	 * @param int           $current Текущая страница.
	 * @param int           $pages   Всего страниц.
	 * @param string        $anchor  Якорь списка без # ('' — без якоря).
	 * @param array         $labels  Подписи (см. labels()).
	 * @param callable|null $link    Адрес страницы N вместо get_pagenum_link() (для ответа REST).
	 */
	public static function render( int $current, int $pages, string $anchor = '', array $labels = array(), ?callable $link = null ): string {
		if ( $pages < 2 ) {
			return '';
		}
		$labels = array_merge( self::labels(), $labels );
		$url    = static fn( int $n ): string => esc_url( ( $link ? (string) $link( $n ) : get_pagenum_link( $n, false ) ) . ( '' !== $anchor ? '#' . $anchor : '' ) );
		$arrow  = static fn( string $icon ): string => Icons::svg(
			$icon,
			array(
				'size'         => 16,
				'stroke_width' => 2,
			)
		);
		$class  = array(
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
			return '<span class="pager__dots' . ( $gap ? '' : $when[ $distance ] ) . '" aria-hidden="true">…</span>';
		};

		$html = '<nav class="pager" aria-label="' . esc_attr( $labels['nav'] ) . '">';
		if ( $current > 1 ) {
			$html .= '<a class="pager__arrow" href="' . $url( $current - 1 ) . '" aria-label="' . esc_attr( $labels['prev_aria'] ) . '">' . $arrow( 'arrow-left' ) . '<span class="pager__label">' . esc_html( $labels['prev'] ) . '</span></a>';
		} else {
			$html .= '<span class="pager__arrow is-disabled" aria-disabled="true">' . $arrow( 'arrow-left' ) . '<span class="pager__label">' . esc_html( $labels['prev'] ) . '</span></span>';
		}

		$from = max( 2, $current - 3 );
		$to   = min( $pages - 1, $current + 3 );
		$page = static function ( int $n ) use ( $current, $pages, $url, $class, $labels ): string {
			$aria = esc_attr( sprintf( $labels['page'], $n ) );
			if ( $n === $current ) {
				return '<a class="pager__page is-current" href="' . $url( $n ) . '" aria-current="page" aria-label="' . $aria . '">' . $n . '</a>';
			}
			// Первая и последняя страницы видны всегда.
			$distance = 1 === $n || $pages === $n ? 0 : abs( $n - $current );
			$css      = $class[ $distance ] ?? '';
			return '<a class="pager__page' . ( '' !== $css ? ' ' . $css : '' ) . '" href="' . $url( $n ) . '" aria-label="' . $aria . '">' . $n . '</a>';
		};

		$html .= $page( 1 );
		$html .= $current > 2 ? $dots( $from > 2, $current - 2 ) : '';
		for ( $n = $from; $n <= $to; $n++ ) {
			$html .= $page( $n );
		}
		$html .= $current < $pages - 1 ? $dots( $to < $pages - 1, $pages - 1 - $current ) : '';
		$html .= $page( $pages );

		if ( $current < $pages ) {
			$html .= '<a class="pager__arrow is-next" href="' . $url( $current + 1 ) . '" aria-label="' . esc_attr( $labels['next_aria'] ) . '"><span class="pager__label">' . esc_html( $labels['next'] ) . '</span>' . $arrow( 'arrow-right' ) . '</a>';
		} else {
			$html .= '<span class="pager__arrow is-next is-disabled" aria-disabled="true"><span class="pager__label">' . esc_html( $labels['next'] ) . '</span>' . $arrow( 'arrow-right' ) . '</span>';
		}
		return $html . '</nav>';
	}
}
