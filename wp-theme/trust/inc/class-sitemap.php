<?php
/**
 * Карта сайта и robots.txt (ТЗ, раздел 10.3).
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Встроенная карта сайта WordPress (/wp-sitemap.xml):
 * - страницы, статьи, кейсы, ответы, сотрудники, рубрики и индексируемые теги;
 * - без отзывов, вакансий, заявок (они и так непубличные) и пользователей (авторы — сотрудники);
 * - без записей и терминов с флажком noindex и тегов ниже порога (Trust\Seo);
 * - у записей — дата изменения (lastmod).
 *
 * Файл robots.txt — виртуальный: текст из «Настроек сайта → SEO» (пусто — по умолчанию) и строка
 * Sitemap. Если сайт закрыт от поисковиков («Настройки → Чтение»), остаётся ответ WordPress:
 * карта отключена, страницы получают метатег noindex (WordPress с версии 5.3 не запрещает
 * обход в robots.txt — иначе поисковик не увидел бы noindex).
 */
final class Sitemap {

	/**
	 * Текст robots.txt по умолчанию: закрыты админка (кроме admin-ajax.php) и результаты поиска.
	 */
	public const ROBOTS_DEFAULT = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\nDisallow: /?s=\nDisallow: /search/";

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'wp_sitemaps_add_provider', array( self::class, 'providers' ), 10, 2 );
		add_filter( 'wp_sitemaps_post_types', array( self::class, 'post_types' ) );
		add_filter( 'wp_sitemaps_taxonomies', array( self::class, 'taxonomies' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( self::class, 'posts_query' ) );
		add_filter( 'wp_sitemaps_taxonomies_query_args', array( self::class, 'terms_query' ), 10, 2 );
		add_filter( 'wp_sitemaps_posts_entry', array( self::class, 'posts_entry' ), 10, 2 );
		add_filter( 'robots_txt', array( self::class, 'robots_txt' ), 10, 2 );
	}

	/**
	 * Без карты пользователей: страница автора — это страница сотрудника.
	 *
	 * @param \WP_Sitemaps_Provider|false $provider Поставщик.
	 * @param string                      $name     Имя поставщика.
	 * @return \WP_Sitemaps_Provider|false
	 */
	public static function providers( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	}

	/**
	 * Типы записей карты: те, у которых есть страницы на сайте и группа «SEO».
	 *
	 * @param \WP_Post_Type[] $post_types Типы записей.
	 */
	public static function post_types( array $post_types ): array {
		return array_intersect_key( $post_types, array_flip( Seo::POST_TYPES ) );
	}

	/**
	 * Таксономии карты: рубрики и теги.
	 *
	 * @param \WP_Taxonomy[] $taxonomies Таксономии.
	 */
	public static function taxonomies( array $taxonomies ): array {
		return array_intersect_key( $taxonomies, array_flip( Seo::TAXONOMIES ) );
	}

	/**
	 * Без записей с флажком «Не показывать в поиске».
	 *
	 * @param array $args Параметры WP_Query.
	 */
	public static function posts_query( array $args ): array {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- карта сайта кэшируется поисковиками, запрос редкий.
			'relation' => 'OR',
			array(
				'key'     => 'trust_seo_noindex',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => 'trust_seo_noindex',
				'value'   => '1',
				'compare' => '!=',
			),
		);
		return $args;
	}

	/**
	 * Без терминов с флажком noindex и тегов, где материалов меньше порога.
	 *
	 * @param array  $args     Параметры get_terms().
	 * @param string $taxonomy Таксономия.
	 */
	public static function terms_query( array $args, string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);
		$skip  = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			if ( Seo::is_noindex( $term ) ) {
				$skip[] = (int) $term->term_id;
			}
		}
		if ( $skip ) {
			$args['exclude'] = array_merge( (array) ( $args['exclude'] ?? array() ), $skip );
		}
		return $args;
	}

	/**
	 * Дата изменения записи.
	 *
	 * @param array    $entry Пункт карты.
	 * @param \WP_Post $post  Запись.
	 */
	public static function posts_entry( array $entry, $post ): array {
		$entry['lastmod'] = (string) get_post_modified_time( 'c', true, $post );
		return $entry;
	}

	/**
	 * Текст robots.txt.
	 *
	 * @param string $output Ответ WordPress.
	 * @param bool   $is_public Открыт ли сайт для поисковиков.
	 */
	public static function robots_txt( $output, $is_public ) {
		if ( ! $is_public ) {
			return $output;
		}
		$text = trim( str_replace( "\r", '', (string) Settings::get( 'seo_robots', '' ) ) );
		if ( '' === $text ) {
			$text = self::ROBOTS_DEFAULT;
		}
		// Строку Sitemap тема добавляет сама — своя из настроек не дублируется.
		$text = trim( (string) preg_replace( '/^\s*Sitemap:.*$/mi', '', $text ) );
		return $text . "\n\nSitemap: " . get_sitemap_url( 'index' ) . "\n";
	}
}
