<?php
/**
 * Микроразметка schema.org: один JSON-LD с @graph на страницу (ТЗ, раздел 11).
 *
 * @package Trust
 */

namespace Trust\Schema;

use Trust\Breadcrumbs;
use Trust\Contacts;
use Trust\Page_Types;
use Trust\Seo;
use Trust\Settings;
use Trust\Social;

defined( 'ABSPATH' ) || exit;

/**
 * Собирает граф страницы и выводит его в конце страницы — после блоков: блоки при выводе
 * регистрируют свои данные (Graph::faq()), поэтому в разметку попадает только то, что
 * действительно есть на странице, и без дублей (два блока FAQ — один FAQPage).
 *
 * Сущности связаны через @id: {сайт}#organization, {сайт}#website, {страница}#webpage,
 * {страница}#breadcrumb, {страница}#service. Пустые поля в разметку не попадают.
 * Фильтр trust_schema_graph дополняет или отключает разметку (пустой массив — без вывода).
 *
 * Разметка статей, ответов, сотрудников, кейсов, списков и видео добавляется вместе с их
 * шаблонами и блоками (этапы 4–5).
 */
final class Graph {

	/**
	 * Типы организации на выбор в «Настройках сайта → SEO».
	 */
	public const ORG_TYPES = array(
		'Organization'        => 'Организация (Organization)',
		'ProfessionalService' => 'Компания с офисом (ProfessionalService) — нужен адрес',
	);

	/**
	 * Тип WebPage по типу страницы (Trust\Page_Types).
	 */
	private const PAGE_TYPES = array(
		'about'     => 'AboutPage',
		'contacts'  => 'ContactPage',
		'portfolio' => 'CollectionPage',
		'questions' => 'CollectionPage',
		'team'      => 'CollectionPage',
		'reviews'   => 'CollectionPage',
	);

	/**
	 * Теги, разрешённые в ответах FAQ (простые теги, которые принимает Google).
	 */
	private const ANSWER_TAGS = array(
		'p'      => array(),
		'br'     => array(),
		'ul'     => array(),
		'ol'     => array(),
		'li'     => array(),
		'a'      => array( 'href' => true ),
		'b'      => array(),
		'strong' => array(),
		'i'      => array(),
		'em'     => array(),
	);

	/**
	 * Вопросы блоков «Вопрос-ответ» на странице: текст вопроса => HTML ответа.
	 *
	 * @var array<string, string>
	 */
	private static array $faq = array();

	/**
	 * Тарифы блока «Выбор тарифа» с числовой ценой: название => array( цена, описание ).
	 *
	 * @var array<string, array{0: float, 1: string}>
	 */
	private static array $offers = array();

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'wp_footer', array( self::class, 'output' ), 20 );
	}

	/**
	 * Блок «Вопрос-ответ» сообщает свой вопрос (вызывается из render.php на сайте).
	 *
	 * @param string $question Вопрос.
	 * @param string $answer   Ответ (HTML).
	 */
	public static function faq( string $question, string $answer ): void {
		$question = self::text( $question );
		if ( '' !== $question && ! isset( self::$faq[ $question ] ) ) {
			self::$faq[ $question ] = trim( wp_kses( $answer, self::ANSWER_TAGS ) );
		}
	}

	/**
	 * Блок «Выбор тарифа» сообщает тариф с числовой ценой (вызывается из render.php на сайте).
	 *
	 * @param string $name        Название тарифа.
	 * @param float  $price       Цена, ₽.
	 * @param string $description Кому подходит.
	 */
	public static function offer( string $name, float $price, string $description = '' ): void {
		$name = self::text( $name );
		if ( '' !== $name && $price >= 0 && ! isset( self::$offers[ $name ] ) ) {
			self::$offers[ $name ] = array( $price, self::text( $description ) );
		}
	}

	/**
	 * Цены услуги для Service.offers: один тариф — Offer, несколько — AggregateOffer
	 * с наименьшей и наибольшей ценой и списком тарифов (ТЗ, раздел 11).
	 *
	 * @param string $url Адрес страницы.
	 */
	private static function offers( string $url ): ?array {
		if ( ! self::$offers ) {
			return null;
		}
		$list = array();
		foreach ( self::$offers as $name => list( $price, $description ) ) {
			$list[] = array(
				'@type'         => 'Offer',
				'name'          => $name,
				'description'   => $description,
				'price'         => $price,
				'priceCurrency' => 'RUB',
				'url'           => $url,
			);
		}
		if ( 1 === count( $list ) ) {
			return $list[0];
		}
		$prices = array_column( $list, 'price' );
		return array(
			'@type'         => 'AggregateOffer',
			'priceCurrency' => 'RUB',
			'lowPrice'      => min( $prices ),
			'highPrice'     => max( $prices ),
			'offerCount'    => count( $list ),
			'offers'        => $list,
		);
	}

	/**
	 * Текст без HTML и лишних пробелов.
	 *
	 * @param string $text Текст.
	 */
	private static function text( string $text ): string {
		return trim( (string) preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' ) ) );
	}

	/**
	 * Убирает пустые свойства (рекурсивно): «если поле пустое — свойство не выводится».
	 *
	 * @param array $node Сущность.
	 */
	private static function clean( array $node ): array {
		foreach ( $node as $key => $value ) {
			if ( is_array( $value ) ) {
				$value        = self::clean( $value );
				$node[ $key ] = $value;
			}
			if ( null === $value || '' === $value || array() === $value ) {
				unset( $node[ $key ] );
			}
		}
		return $node;
	}

	/**
	 * Адрес сайта для @id общих сущностей.
	 */
	private static function site(): string {
		return home_url( '/' );
	}

	/**
	 * Картинка как ImageObject.
	 *
	 * @param int $id Вложение.
	 */
	private static function image( int $id ): ?array {
		$src = $id ? wp_get_attachment_image_src( $id, 'full' ) : false;
		if ( ! $src ) {
			return null;
		}
		return array(
			'@type'  => 'ImageObject',
			'url'    => $src[0],
			'width'  => (int) $src[1],
			'height' => (int) $src[2],
		);
	}

	/**
	 * Организация: данные из «Настроек сайта» (бренд, логотип, контакты, адрес, соцсети).
	 *
	 * @param bool $extended На странице контактов — ещё координаты и ссылка на карту.
	 */
	public static function organization( bool $extended = false ): array {
		$address  = Contacts::address();
		$phone    = Contacts::main_phone();
		$email    = Contacts::main_email();
		$has_addr = '' !== $address['city'] || '' !== $address['street'];
		$type     = (string) Settings::get( 'seo_org_type', 'Organization' );
		// ProfessionalService — разновидность местного бизнеса: без адреса неверна.
		$type = 'ProfessionalService' === $type && $has_addr ? $type : 'Organization';

		$contact_points = array();
		foreach ( Contacts::phones() as $item ) {
			$contact_points[] = array(
				'@type'             => 'ContactPoint',
				'telephone'         => $item['number'],
				'contactType'       => '' !== $item['label'] ? $item['label'] : 'customer service',
				'availableLanguage' => 'Russian',
			);
		}

		$node = array(
			'@type'        => $type,
			'@id'          => self::site() . '#organization',
			'name'         => Settings::brand(),
			'url'          => self::site(),
			'logo'         => self::image( self::logo_id() ),
			'telephone'    => $phone['number'] ?? '',
			'email'        => $email['email'] ?? '',
			'address'      => $has_addr
				? array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => implode( ', ', array_filter( array( $address['street'], $address['office'] ) ) ),
					'addressLocality' => $address['city'],
					'postalCode'      => $address['postal_code'],
				)
				: null,
			'sameAs'       => wp_list_pluck( Social::company_links(), 'url' ),
			'contactPoint' => $contact_points,
		);

		if ( $extended ) {
			$geo = (array) Settings::get( 'geo', array() );
			if ( ! empty( $geo['lat'] ) && ! empty( $geo['lng'] ) ) {
				$node['geo'] = array(
					'@type'     => 'GeoCoordinates',
					'latitude'  => (float) $geo['lat'],
					'longitude' => (float) $geo['lng'],
				);
			}
			$node['hasMap'] = (string) Settings::get( 'yandex_maps_url', '' );
		}
		return $node;
	}

	/**
	 * Логотип: светлый или тёмный из «Настроек сайта → Компания», иначе иконка сайта.
	 */
	private static function logo_id(): int {
		foreach ( array( Settings::get( 'company_logo_light' ), Settings::get( 'company_logo_dark' ), get_option( 'site_icon' ) ) as $id ) {
			if ( (int) $id ) {
				return (int) $id;
			}
		}
		return 0;
	}

	/**
	 * Сайт.
	 */
	public static function website(): array {
		return array(
			'@type'      => 'WebSite',
			'@id'        => self::site() . '#website',
			'url'        => self::site(),
			'name'       => Settings::brand(),
			'inLanguage' => self::language(),
			'publisher'  => array( '@id' => self::site() . '#organization' ),
		);
	}

	/**
	 * Язык страницы: ru-RU.
	 */
	private static function language(): string {
		return \Trust\Setup::language();
	}

	/**
	 * Адрес текущей страницы.
	 */
	private static function url(): string {
		if ( is_search() ) {
			return (string) get_search_link();
		}
		if ( is_front_page() ) {
			return self::site();
		}
		$canonical = Seo::canonical();
		return '' !== $canonical ? $canonical : home_url( add_query_arg( array() ) );
	}

	/**
	 * Тип WebPage текущей страницы.
	 */
	private static function page_type(): string {
		if ( is_search() ) {
			return 'SearchResultsPage';
		}
		if ( is_home() || is_category() || is_tag() ) {
			return 'CollectionPage';
		}
		if ( is_page() ) {
			return self::PAGE_TYPES[ Page_Types::of( get_queried_object_id() ) ] ?? 'WebPage';
		}
		return 'WebPage';
	}

	/**
	 * Граф текущей страницы.
	 */
	public static function build(): array {
		if ( is_404() ) {
			return array_map( array( self::class, 'clean' ), array( self::organization(), self::website() ) );
		}

		$url     = self::url();
		$context = Seo::context();
		$post    = $context['object'] instanceof \WP_Post ? $context['object'] : null;
		$type    = self::page_type();
		$crumbs  = Breadcrumbs::items();

		$graph = array( self::organization( 'ContactPage' === $type ), self::website() );

		$page = array(
			'@type'       => $type,
			'@id'         => $url . '#webpage',
			'url'         => $url,
			'name'        => '' !== Seo::title() ? Seo::title() : self::text( wp_get_document_title() ),
			'description' => Seo::description(),
			'inLanguage'  => self::language(),
			'isPartOf'    => array( '@id' => self::site() . '#website' ),
		);
		if ( count( $crumbs ) > 1 ) {
			$page['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
		}
		if ( 'AboutPage' === $type || 'ContactPage' === $type ) {
			$page['about'] = array( '@id' => self::site() . '#organization' );
		}
		if ( $post && is_singular() ) {
			$page['datePublished'] = get_post_time( 'c', true, $post );
			$page['dateModified']  = get_post_modified_time( 'c', true, $post );
			if ( has_post_thumbnail( $post ) ) {
				$page['primaryImageOfPage'] = self::image( (int) get_post_thumbnail_id( $post ) );
			}
		}

		// Вопросы блоков «Вопрос-ответ»: страница становится и FAQPage.
		if ( self::$faq ) {
			$page['@type']      = array( $type, 'FAQPage' );
			$page['mainEntity'] = array();
			foreach ( self::$faq as $question => $answer ) {
				$page['mainEntity'][] = array(
					'@type'          => 'Question',
					'name'           => $question,
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $answer,
					),
				);
			}
		}
		$graph[] = $page;

		if ( count( $crumbs ) > 1 ) {
			$list = array();
			foreach ( $crumbs as $i => $crumb ) {
				$list[] = array(
					'@type'    => 'ListItem',
					'position' => $i + 1,
					'name'     => self::text( $crumb['title'] ),
					// У последнего пункта (текущая страница) item не обязателен.
					'item'     => empty( $crumb['current'] ) ? $crumb['url'] : null,
				);
			}
			$graph[] = array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $url . '#breadcrumb',
				'itemListElement' => $list,
			);
		}

		if ( $post && is_page() && 'service' === Page_Types::of( $post ) ) {
			$graph[] = array(
				'@type'       => 'Service',
				'@id'         => $url . '#service',
				'name'        => Seo::h1( $post ),
				'serviceType' => Breadcrumbs::title( $post ),
				'description' => Seo::description(),
				'url'         => $url,
				'provider'    => array( '@id' => self::site() . '#organization' ),
				'areaServed'  => trim( (string) Settings::get( 'seo_area_served', '' ) ),
				'offers'      => self::offers( $url ),
			);
		}

		return array_map( array( self::class, 'clean' ), $graph );
	}

	/**
	 * Выводит граф одним <script type="application/ld+json">.
	 */
	public static function output(): void {
		if ( is_admin() || is_feed() || is_embed() ) {
			return;
		}

		/**
		 * Граф микроразметки страницы: дополнить или отключить (пустой массив).
		 *
		 * @param array $graph Сущности.
		 */
		$graph = apply_filters( 'trust_schema_graph', self::build() );
		if ( ! is_array( $graph ) || ! $graph ) {
			return;
		}

		$json = wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => array_values( $graph ),
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
		);
		echo "<script type=\"application/ld+json\">{$json}</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON с JSON_HEX_TAG: «<» и «>» экранированы.
	}
}
