<?php
/**
 * SEO: title, description, canonical, robots, Open Graph, H1 (ТЗ, раздел 10).
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Значения берутся из группы «SEO» записи или термина (inc/fields/content/seo.php),
 * пустые — по правилам ТЗ: шаблон title из «Настроек сайта → SEO», анонс или начало текста,
 * заголовок записи, адрес самой страницы. Метаполя читаются напрямую: SEO работает и при
 * выключенном Secure Custom Fields.
 */
final class Seo {

	/**
	 * Шаблоны title по умолчанию — как в макетах. {title}, {site}, {sep}, {query}.
	 */
	public const TITLE_TEMPLATES = array(
		'page'     => '{title} {sep} {site}',
		'post'     => '{title} {sep} {site}',
		'case'     => '{title} {sep} {site}',
		'question' => '{title} {sep} ответ SEO-специалиста {site}',
		'team'     => '{title} {sep} автор блога {site}',
		'category' => '{title} {sep} блог {site}',
		'post_tag' => '#{title} {sep} блог {site}',
		'search'   => 'Поиск: «{query}» {sep} {site}',
		'404'      => 'Страница не найдена {sep} {site}',
	);

	/**
	 * Разделители на выбор.
	 */
	public const SEPARATORS = array( '—', '–', '|', '·', '/', ':' );

	/**
	 * Рекомендуемая длина title (подсветка в счётчике).
	 */
	public const TITLE_LIMIT = 60;

	/**
	 * Рекомендуемая длина description (подсветка в счётчике).
	 */
	public const DESCRIPTION_LIMIT = 160;

	/**
	 * Порог индексации тегов по умолчанию: меньше материалов — noindex.
	 */
	public const TAG_THRESHOLD = 3;

	/**
	 * Типы записей с группой «SEO».
	 */
	public const POST_TYPES = array( 'page', 'post', 'case', 'question', 'team' );

	/**
	 * Таксономии с группой «SEO».
	 */
	public const TAXONOMIES = array( 'category', 'post_tag' );

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'pre_get_document_title', array( self::class, 'document_title' ), 20 );
		add_filter( 'wp_robots', array( self::class, 'robots' ) );
		remove_action( 'wp_head', 'rel_canonical' );
		add_action( 'wp_head', array( self::class, 'head' ), 2 );
	}

	/**
	 * Что показывается сейчас: запись (в том числе страница блога), термин, поиск, 404.
	 *
	 * @return array{kind: string, object: \WP_Post|\WP_Term|null}
	 */
	public static function context(): array {
		if ( is_404() ) {
			return array(
				'kind'   => '404',
				'object' => null,
			);
		}
		if ( is_search() ) {
			return array(
				'kind'   => 'search',
				'object' => null,
			);
		}
		if ( is_singular() ) {
			return array(
				'kind'   => 'post',
				'object' => get_queried_object(),
			);
		}
		if ( is_home() && get_option( 'page_for_posts' ) ) {
			return array(
				'kind'   => 'post',
				'object' => get_post( (int) get_option( 'page_for_posts' ) ),
			);
		}
		if ( is_category() || is_tag() ) {
			return array(
				'kind'   => 'term',
				'object' => get_queried_object(),
			);
		}
		return array(
			'kind'   => 'other',
			'object' => null,
		);
	}

	/**
	 * Поле группы «SEO» записи или термина.
	 *
	 * @param \WP_Post|\WP_Term|null $entity Запись или термин.
	 * @param string                 $name   Имя поля (trust_seo_title…).
	 */
	public static function field( $entity, string $name ): string {
		if ( $entity instanceof \WP_Post ) {
			return trim( (string) get_post_meta( $entity->ID, $name, true ) );
		}
		if ( $entity instanceof \WP_Term ) {
			return trim( (string) get_term_meta( $entity->term_id, $name, true ) );
		}
		return '';
	}

	/**
	 * Разделитель из настроек.
	 */
	public static function separator(): string {
		$sep = (string) Settings::get( 'seo_separator', '—' );
		return in_array( $sep, self::SEPARATORS, true ) ? $sep : '—';
	}

	/**
	 * Шаблон title для типа записи, таксономии, поиска или 404.
	 *
	 * @param string $type Ключ из TITLE_TEMPLATES.
	 */
	public static function template( string $type ): string {
		$default = self::TITLE_TEMPLATES[ $type ] ?? self::TITLE_TEMPLATES['page'];
		$custom  = trim( (string) Settings::get( 'seo_title_' . $type, '' ) );
		return '' === $custom ? $default : $custom;
	}

	/**
	 * Подставляет переменные шаблона и убирает лишние пробелы и разделители по краям.
	 *
	 * @param string $template Шаблон.
	 * @param array  $vars     title, query.
	 */
	public static function fill( string $template, array $vars = array() ): string {
		$sep  = self::separator();
		$text = strtr(
			$template,
			array(
				'{title}' => $vars['title'] ?? '',
				'{query}' => $vars['query'] ?? '',
				'{site}'  => Settings::brand(),
				'{sep}'   => $sep,
			)
		);
		$text = trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
		// Без trim(): он режет побайтно, а «—» — три байта UTF-8 (испортил бы соседние буквы).
		$edge = '[\s' . preg_quote( $sep, '/' ) . ']+';
		return (string) preg_replace( '/^' . $edge . '|' . $edge . '$/u', '', $text );
	}

	/**
	 * Название записи или термина без HTML.
	 *
	 * @param \WP_Post|\WP_Term $entity Запись или термин.
	 */
	private static function name( $entity ): string {
		$name = $entity instanceof \WP_Term ? $entity->name : get_the_title( $entity );
		return html_entity_decode( wp_strip_all_tags( $name ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * SEO title записи или термина (без номера страницы).
	 *
	 * @param \WP_Post|\WP_Term $entity Запись или термин.
	 */
	public static function title_for( $entity ): string {
		$own = self::field( $entity, 'trust_seo_title' );
		if ( '' !== $own ) {
			return self::fill( $own );
		}
		$type = $entity instanceof \WP_Term ? $entity->taxonomy : $entity->post_type;
		return self::fill( self::template( $type ), array( 'title' => self::name( $entity ) ) );
	}

	/**
	 * Номер страницы пагинации (архивы — paged, запись из нескольких страниц — page).
	 */
	private static function page_number(): int {
		return max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	}

	/**
	 * Title текущей страницы; пустая строка — оставить WordPress.
	 */
	public static function title(): string {
		$context = self::context();

		switch ( $context['kind'] ) {
			case 'post':
			case 'term':
				$title = self::title_for( $context['object'] );
				break;
			case 'search':
				$title = self::fill( self::template( 'search' ), array( 'query' => get_search_query( false ) ) );
				break;
			case '404':
				$title = self::fill( self::template( '404' ) );
				break;
			default:
				return '';
		}

		$page = self::page_number();
		if ( $page > 1 && '404' !== $context['kind'] ) {
			/* translators: %d: номер страницы. */
			$title .= ' ' . self::separator() . ' ' . sprintf( __( 'страница %d', 'trust' ), $page );
		}
		return $title;
	}

	/**
	 * Фильтр pre_get_document_title.
	 *
	 * @param string $title Заголовок от других фильтров.
	 */
	public static function document_title( $title ) {
		$own = self::title();
		return '' === $own ? $title : $own;
	}

	/**
	 * Обрезает текст до длины по границе слова, с многоточием.
	 *
	 * @param string $text  Текст.
	 * @param int    $limit Длина.
	 */
	public static function trim_text( string $text, int $limit = self::DESCRIPTION_LIMIT ): string {
		$text = trim( (string) preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' ) ) );
		if ( mb_strlen( $text ) <= $limit ) {
			return $text;
		}
		$cut = mb_substr( $text, 0, $limit - 1 );
		// По границе слова, если она не слишком далеко от конца.
		if ( preg_match( '/^(.+)\s/su', $cut, $m ) && mb_strlen( $m[1] ) > $limit / 2 ) {
			$cut = $m[1];
		}
		return (string) preg_replace( '/[\s,.;:—–-]+$/u', '', $cut ) . '…';
	}

	/**
	 * Вводный текст блока первого экрана (поле lead блока trust/hero-…).
	 *
	 * @param \WP_Post $post Запись.
	 */
	private static function hero_lead( \WP_Post $post ): string {
		if ( ! str_contains( $post->post_content, '<!-- wp:trust/hero-' ) ) {
			return '';
		}
		foreach ( parse_blocks( $post->post_content ) as $block ) {
			if ( str_starts_with( (string) $block['blockName'], 'trust/hero-' ) ) {
				return is_string( $block['attrs']['data']['lead'] ?? null ) ? $block['attrs']['data']['lead'] : '';
			}
		}
		return '';
	}

	/**
	 * Описание по умолчанию: анонс, поля карточки, вводный текст первого экрана, начало текста.
	 *
	 * @param \WP_Post|\WP_Term $entity Запись или термин.
	 */
	public static function fallback_description( $entity ): string {
		if ( $entity instanceof \WP_Term ) {
			return self::trim_text( $entity->description );
		}

		$candidates = array(
			$entity->post_excerpt,
			(string) get_post_meta( $entity->ID, 'trust_lead', true ),
			(string) get_post_meta( $entity->ID, 'trust_summary', true ),
			self::hero_lead( $entity ),
			// Текст стандартных блоков; данные блоков темы (в комментариях) не попадают.
			(string) preg_replace( '/<!--.*?-->/s', '', $entity->post_content ),
			(string) get_post_meta( $entity->ID, 'trust_question', true ),
			(string) get_post_meta( $entity->ID, 'trust_about', true ),
		);
		foreach ( $candidates as $text ) {
			$text = self::trim_text( $text );
			if ( '' !== $text ) {
				return $text;
			}
		}
		return '';
	}

	/**
	 * Meta description текущей страницы.
	 */
	public static function description(): string {
		$context = self::context();
		if ( ! in_array( $context['kind'], array( 'post', 'term' ), true ) ) {
			return '';
		}
		$own = self::field( $context['object'], 'trust_seo_description' );
		return '' !== $own ? self::trim_text( $own, 300 ) : self::fallback_description( $context['object'] );
	}

	/**
	 * H1 записи или термина: поле «H1», иначе заголовок (название).
	 *
	 * @param \WP_Post|\WP_Term|int|null $entity Запись, термин или ID записи; по умолчанию текущая.
	 */
	public static function h1( $entity = null ): string {
		if ( ! $entity instanceof \WP_Term ) {
			$entity = get_post( $entity );
		}
		if ( ! $entity ) {
			return '';
		}
		$own = self::field( $entity, 'trust_seo_h1' );
		return '' !== $own ? self::fill( $own ) : self::name( $entity );
	}

	/**
	 * Адрес без параметров запроса (сортировка и т. п. — canonical на адрес без них).
	 *
	 * @param string $url Адрес.
	 */
	private static function strip_query( string $url ): string {
		return (string) strtok( $url, '?' );
	}

	/**
	 * Canonical текущей страницы; пустая строка — не выводить (поиск, 404).
	 */
	public static function canonical(): string {
		$context = self::context();
		$entity  = $context['object'];
		if ( ! $entity ) {
			return '';
		}

		$own = self::field( $entity, 'trust_seo_canonical' );
		if ( '' !== $own ) {
			return $own;
		}

		// Страницы пагинации — свой адрес: архивы и страницы-списки (/portfolio/page/2/).
		$paged = max( 1, (int) get_query_var( 'paged' ) );
		if ( $entity instanceof \WP_Term || is_home() || $paged > 1 ) {
			return self::strip_query( get_pagenum_link( $paged, false ) );
		}
		return (string) wp_get_canonical_url( $entity );
	}

	/**
	 * Мало ли материалов у тега (статьи + ответы) — тогда noindex и не в карте сайта.
	 *
	 * @param \WP_Term $term Тег.
	 */
	public static function is_thin_tag( \WP_Term $term ): bool {
		$threshold = Settings::get( 'seo_tag_threshold', null );
		$threshold = null === $threshold || '' === $threshold ? self::TAG_THRESHOLD : (int) $threshold;
		return 'post_tag' === $term->taxonomy && (int) $term->count < $threshold;
	}

	/**
	 * Закрыта ли запись или термин от индексации.
	 *
	 * @param \WP_Post|\WP_Term $entity Запись или термин.
	 */
	public static function is_noindex( $entity ): bool {
		return (bool) self::field( $entity, 'trust_seo_noindex' ) || ( $entity instanceof \WP_Term && self::is_thin_tag( $entity ) );
	}

	/**
	 * Фильтр wp_robots: поиск и 404 — noindex; флажки группы «SEO»; теги ниже порога.
	 *
	 * @param array $robots Директивы.
	 */
	public static function robots( array $robots ): array {
		$context = self::context();

		if ( in_array( $context['kind'], array( 'search', '404' ), true ) ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			return $robots;
		}
		if ( ! $context['object'] ) {
			return $robots;
		}

		if ( self::is_noindex( $context['object'] ) ) {
			$robots['noindex'] = true;
			$robots['follow']  = ! self::field( $context['object'], 'trust_seo_nofollow' );
		}
		if ( self::field( $context['object'], 'trust_seo_nofollow' ) ) {
			$robots['nofollow'] = true;
			unset( $robots['follow'] );
		}
		return $robots;
	}

	/**
	 * Картинка для соцсетей: своя, обложка записи, по умолчанию из настроек.
	 *
	 * @param \WP_Post|\WP_Term $entity Запись или термин.
	 */
	public static function og_image_id( $entity ): int {
		$own = (int) self::field( $entity, 'trust_og_image' );
		if ( $own ) {
			return $own;
		}
		if ( $entity instanceof \WP_Post && has_post_thumbnail( $entity ) ) {
			return (int) get_post_thumbnail_id( $entity );
		}
		return (int) Settings::get( 'seo_og_image', 0 );
	}

	/**
	 * Метатеги в <head>: description, canonical, Open Graph, twitter:card.
	 */
	public static function head(): void {
		$context = self::context();
		$entity  = $context['object'];
		$tags    = array();

		$description = self::description();
		if ( '' !== $description ) {
			$tags[] = array( 'name', 'description', $description );
		}

		$canonical = self::canonical();
		if ( '' !== $canonical ) {
			printf( "<link rel=\"canonical\" href=\"%s\">\n", esc_url( $canonical ) );
		}

		if ( $entity ) {
			$og_title       = self::field( $entity, 'trust_og_title' );
			$og_description = self::field( $entity, 'trust_og_description' );
			$is_article     = $entity instanceof \WP_Post && in_array( $entity->post_type, array( 'post', 'question' ), true ) && is_singular();

			$tags[] = array( 'property', 'og:locale', get_locale() );
			$tags[] = array( 'property', 'og:type', $is_article ? 'article' : 'website' );
			$tags[] = array( 'property', 'og:title', '' !== $og_title ? self::fill( $og_title ) : self::title() );
			if ( '' !== $og_description || '' !== $description ) {
				$tags[] = array( 'property', 'og:description', '' !== $og_description ? $og_description : $description );
			}
			$tags[] = array( 'property', 'og:url', '' !== $canonical ? $canonical : home_url( add_query_arg( array() ) ) );
			$tags[] = array( 'property', 'og:site_name', Settings::brand() );

			$image = self::og_image_id( $entity );
			$src   = $image ? wp_get_attachment_image_src( $image, 'full' ) : false;
			if ( $src ) {
				$tags[] = array( 'property', 'og:image', $src[0] );
				$tags[] = array( 'property', 'og:image:width', (string) $src[1] );
				$tags[] = array( 'property', 'og:image:height', (string) $src[2] );
				$alt    = (string) get_post_meta( $image, '_wp_attachment_image_alt', true );
				if ( '' !== $alt ) {
					$tags[] = array( 'property', 'og:image:alt', $alt );
				}
			}
			if ( $is_article ) {
				$tags[] = array( 'property', 'article:published_time', get_post_time( 'c', true, $entity ) );
				$tags[] = array( 'property', 'article:modified_time', get_post_modified_time( 'c', true, $entity ) );
			}
			$tags[] = array( 'name', 'twitter:card', $src ? 'summary_large_image' : 'summary' );
		}

		foreach ( $tags as list( $attr, $key, $value ) ) {
			printf( "<meta %s=\"%s\" content=\"%s\">\n", esc_attr( $attr ), esc_attr( $key ), esc_attr( $value ) );
		}
	}
}
