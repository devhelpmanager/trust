<?php
/**
 * Статья блога (single-post.php, макет seo-blog/statya.html): текст с якорями разделов и
 * содержание, ключевые слова, «Читайте также», русские склонения и числа.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Помощники страницы статьи.
 */
final class Article {

	/**
	 * Формы слов у счётчиков статьи: для 1, для 2–4, для 5–20.
	 */
	public const FORMS = array(
		'views'    => array( 'просмотр', 'просмотра', 'просмотров' ),
		'likes'    => array( 'лайк', 'лайка', 'лайков' ),
		'comments' => array( 'комментарий', 'комментария', 'комментариев' ),
	);

	/**
	 * Регистрирует хуки: стили и скрипт статьи, инициалы автора в цитате «С автором».
	 */
	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_filter( 'render_block_core/quote', array( self::class, 'quote_initials' ), 10, 2 );
	}

	/**
	 * Стили (статья, карточки «Читайте также») и скрипт (содержание, лайки, просмотры).
	 */
	public static function enqueue(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}
		wp_enqueue_style( 'trust-blog', Assets::url( 'assets/css/blog.css' ), array( 'trust-main' ), Assets::version( 'assets/css/blog.css' ) );
		wp_enqueue_style( 'trust-post', Assets::url( 'assets/css/post.css' ), array( 'trust-blog' ), Assets::version( 'assets/css/post.css' ) );
		wp_enqueue_script(
			'trust-post',
			Assets::url( 'assets/js/post.js' ),
			array(),
			Assets::version( 'assets/js/post.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_add_inline_script(
			'trust-post',
			'window.trustPost = ' . wp_json_encode(
				array(
					'id'   => get_queried_object_id(),
					'rest' => esc_url_raw( rest_url( 'trust/v1/posts/' ) ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Текст статьи и содержание: у каждого H2 текста (в том числе H2 блоков, например «Коротко:
	 * чек-лист») — id транслитом из заголовка; свой «HTML-якорь» сохраняется, служебный id блока
	 * (block_…) заменяется читаемым вместе со ссылками на него (aria-labelledby). Пункты
	 * содержания — эти H2 по порядку.
	 *
	 * @param \WP_Post $post Статья.
	 * @return array{html: string, toc: array<int, array{id: string, title: string}>}
	 */
	public static function content( \WP_Post $post ): array {
		$html    = (string) apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- фильтр ядра.
		$toc     = array();
		$used    = array();
		$renamed = array();
		$html    = (string) preg_replace_callback(
			'~<h2\b([^>]*)>(.*?)</h2>~is',
			static function ( array $m ) use ( &$toc, &$used, &$renamed ): string {
				$title = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $m[2] ), ENT_QUOTES, 'UTF-8' ) ) );
				if ( '' === $title ) {
					return $m[0];
				}
				$attrs = $m[1];
				$own   = preg_match( '~\sid="([^"]+)"~', $attrs, $found ) ? $found[1] : '';
				if ( '' !== $own && ! str_starts_with( $own, 'block_' ) ) {
					$id = $own;
				} else {
					$base = sanitize_title( Translit::convert( mb_substr( $title, 0, 60 ) ) );
					$base = '' !== $base ? $base : 'razdel';
					$id   = $base;
					$n    = 2;
					while ( in_array( $id, $used, true ) ) {
						$id = $base . '-' . $n;
						++$n;
					}
					if ( '' !== $own ) {
						$renamed[ $own ] = $id;
						$attrs           = str_replace( ' id="' . $own . '"', ' id="' . esc_attr( $id ) . '"', $attrs );
					} else {
						$attrs .= ' id="' . esc_attr( $id ) . '"';
					}
				}
				$used[] = $id;
				$toc[]  = array(
					'id'    => $id,
					'title' => $title,
				);
				return '<h2' . $attrs . '>' . $m[2] . '</h2>';
			},
			$html
		);
		foreach ( $renamed as $from => $to ) {
			$html = str_replace( '="' . $from . '"', '="' . esc_attr( $to ) . '"', $html );
		}
		return array(
			'html' => $html,
			'toc'  => $toc,
		);
	}

	/**
	 * Ключевые слова статьи — её теги.
	 *
	 * @param \WP_Post $post Статья.
	 * @return \WP_Term[]
	 */
	public static function tags( \WP_Post $post ): array {
		$tags = get_the_tags( $post->ID );
		return is_array( $tags ) ? $tags : array();
	}

	/**
	 * «Читайте также»: статьи с общими тегами, затем из той же рубрики, затем самые новые —
	 * без текущей.
	 *
	 * @param \WP_Post $post  Статья.
	 * @param int      $count Сколько.
	 * @return \WP_Post[]
	 */
	public static function related( \WP_Post $post, int $count = 3 ): array {
		$found  = array();
		$base   = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'has_password'        => false,
		);
		$tags   = wp_list_pluck( self::tags( $post ), 'term_id' );
		$rubric = Blog::rubric( $post );
		$steps  = array();
		if ( $tags ) {
			$steps[] = array( 'tag__in' => $tags );
		}
		if ( $rubric ) {
			$steps[] = array( 'cat' => $rubric->term_id );
		}
		$steps[] = array();
		foreach ( $steps as $args ) {
			if ( count( $found ) >= $count ) {
				break;
			}
			$exclude = array_merge( array( $post->ID ), wp_list_pluck( $found, 'ID' ) );
			$found   = array_merge(
				$found,
				get_posts(
					$base + $args + array(
						'posts_per_page' => $count - count( $found ),
						'post__not_in'   => $exclude, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- несколько статей.
					)
				)
			);
		}
		return array_slice( $found, 0, $count );
	}

	/**
	 * Русское склонение: 1 просмотр, 2 просмотра, 5 просмотров (не зависит от языковых файлов).
	 *
	 * @param int      $n     Число.
	 * @param string[] $forms Формы: для 1, для 2–4, для 5–20.
	 */
	public static function plural( int $n, array $forms ): string {
		$n10  = abs( $n ) % 10;
		$n100 = abs( $n ) % 100;
		if ( 1 === $n10 && 11 !== $n100 ) {
			return $forms[0];
		}
		if ( $n10 >= 2 && $n10 <= 4 && ( $n100 < 12 || $n100 > 14 ) ) {
			return $forms[1];
		}
		return $forms[2];
	}

	/**
	 * Счётчик словами: «1 248 просмотров», «1 лайк».
	 *
	 * @param int    $n    Число.
	 * @param string $kind views, likes или comments.
	 */
	public static function count( int $n, string $kind ): string {
		return self::number( $n ) . "\u{00A0}" . self::plural( $n, self::FORMS[ $kind ] );
	}

	/**
	 * Число с пробелом между разрядами: «1 248» (неразрывный пробел).
	 *
	 * @param int $n Число.
	 */
	public static function number( int $n ): string {
		return number_format( $n, 0, ',', "\u{00A0}" );
	}

	/**
	 * Цитата «С автором»: инициалы автора (первая строка подписи) — в data-initials у подписи,
	 * стили статьи показывают их в круге вместо значка.
	 *
	 * @param string $html  Разметка блока.
	 * @param array  $block Блок.
	 */
	public static function quote_initials( string $html, array $block ): string {
		if ( ! str_contains( (string) ( $block['attrs']['className'] ?? '' ), 'is-style-author' ) || ! preg_match( '~<cite\b[^>]*>(.*?)</cite>~is', $html, $m ) ) {
			return $html;
		}
		$name     = trim( (string) preg_split( '~<br\s*/?>~i', $m[1] )[0] );
		$initials = Answers_List::initials( wp_strip_all_tags( $name ) );
		if ( '' === $initials ) {
			return $html;
		}
		$tags = new \WP_HTML_Tag_Processor( $html );
		if ( $tags->next_tag( 'cite' ) ) {
			$tags->set_attribute( 'data-initials', $initials );
		}
		return $tags->get_updated_html();
	}
}
