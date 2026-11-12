<?php
/**
 * Типы страниц (ТЗ, раздел 6): шаблоны, наборы блоков, окно выбора типа в редакторе.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Тип страницы = PHP-шаблон из папки templates/ + набор блоков по умолчанию.
 *
 * Окно «Выберите тип страницы» (assets/js/page-types.js) открывается при создании страницы,
 * ставит шаблон и вставляет набор. В набор попадают только уже зарегистрированные блоки —
 * наборы пополняются сами по мере появления блоков (этап 4).
 */
final class Page_Types {

	/**
	 * Типы страниц в порядке окна выбора.
	 *
	 * Ключ => array(
	 *   'label'       => название,
	 *   'description' => что на странице,
	 *   'template'    => файл шаблона ('' — page.php),
	 *   'blocks'      => набор блоков: имя или array( имя, атрибуты ),
	 * ).
	 */
	private const TYPES = array(
		'service'   => array(
			'label'       => 'Услуга',
			'description' => 'Первый экран с кнопками, состав услуги, этапы, преимущества, вопросы, кнопка связи, статьи блога.',
			'template'    => 'templates/service.php',
			'blocks'      => array( 'trust/hero-service', 'trust/service-includes', 'trust/stages', 'trust/advantages', 'trust/faq', 'trust/contact-swipe', array( 'trust/posts', array( 'data' => array( 'view' => 'blog' ) ) ) ),
		),
		'landing'   => array(
			'label'       => 'Главная / посадочная',
			'description' => 'Первый экран раздела, дальше — любые блоки на ваш выбор.',
			'template'    => 'templates/landing.php',
			'blocks'      => array( 'trust/hero-section' ),
		),
		'portfolio' => array(
			'label'       => 'Портфолио',
			'description' => 'Первый экран с цифрами, кейсы с фильтрами, отзывы клиентов, «Обсудим ваш проект».',
			'template'    => 'templates/portfolio.php',
			'blocks'      => array( 'trust/hero-section', 'trust/cases-grid', array( 'trust/reviews', array( 'data' => array( 'view' => 'two' ) ) ), 'trust/cta' ),
		),
		'about'     => array(
			'label'       => 'О компании',
			'description' => 'Направления, принципы, история, слово руководителя, сертификаты, клиенты, офис.',
			'template'    => 'templates/about.php',
			'blocks'      => array( 'trust/hero-section', 'trust/directions', 'trust/principles', 'trust/history', 'trust/leader-word', 'trust/certificates', 'trust/clients', 'trust/office', 'trust/cta' ),
		),
		'team'      => array(
			'label'       => 'Команда',
			'description' => 'Руководство, специалисты по отделам, кто работает над проектом, приглашение в команду.',
			'template'    => 'templates/team.php',
			'blocks'      => array( 'trust/hero-section', 'trust/team-leaders', 'trust/team-grid', 'trust/roles', 'trust/join', 'trust/cta' ),
		),
		'reviews'   => array(
			'label'       => 'Отзывы',
			'description' => 'Рейтинги площадок, отзывы клиентов, видеоотзывы, благодарственные письма, форма отзыва.',
			'template'    => 'templates/reviews.php',
			'blocks'      => array( 'trust/hero-reviews', 'trust/reviews-list', 'trust/video-reviews', 'trust/letters', 'trust/review-form', 'trust/cta' ),
		),
		'career'    => array(
			'label'       => 'Карьера',
			'description' => 'Почему у нас, вакансии, как проходит отбор, стажировка, отзывы сотрудников, форма резюме.',
			'template'    => 'templates/career.php',
			'blocks'      => array( 'trust/hero-section', 'trust/benefits', 'trust/vacancies', 'trust/hiring-steps', 'trust/internship', 'trust/employee-quotes', 'trust/resume-form' ),
		),
		'contacts'  => array(
			'label'       => 'Контакты',
			'description' => 'Каналы связи, офис и схема проезда, кому написать, форма заявки, реквизиты.',
			'template'    => 'templates/contacts.php',
			'blocks'      => array( 'trust/hero-contacts', 'trust/office-map', 'trust/departments', 'trust/contact-form', 'trust/requisites' ),
		),
		'document'  => array(
			'label'       => 'Документ',
			'description' => 'Оферта, политика, согласие: дата редакции, «коротко о главном», текст с содержанием сбоку, реквизиты.',
			'template'    => 'templates/document.php',
			'blocks'      => array( 'trust/hero-document', 'trust/summary', array( 'core/heading', array( 'level' => 2 ) ), 'core/paragraph', array( 'trust/requisites', array( 'data' => array( 'view' => 'document' ) ) ), 'trust/documents-list' ),
		),
		'questions' => array(
			'label'       => 'Вопросы SEO',
			'description' => 'Форма «Задать вопрос», оповещение «личное мнение автора», список ответов специалистов.',
			'template'    => 'templates/questions.php',
			'blocks'      => array( 'trust/hero-questions', 'trust/notice', 'trust/answers-list' ),
		),
		'default'   => array(
			'label'       => 'Обычная страница',
			'description' => 'Заголовок и текст. Подходит для простых страниц без макета.',
			'template'    => '',
			'blocks'      => array( 'trust/text' ),
		),
	);

	/**
	 * Блоки, которые имеют смысл только на своём типе страницы (ТЗ, раздел 16):
	 * на остальных они скрыты из списка блоков.
	 */
	private const EXCLUSIVE = array(
		'trust/cases-grid'   => 'portfolio',
		'trust/answers-list' => 'questions',
	);

	/**
	 * Наборы блоков записей других типов (ТЗ, раздел 6.1): свойство template типа записи.
	 */
	private const POST_TYPE_TEMPLATES = array(
		'case'     => array( 'trust/case-task', 'trust/case-obstacles', 'trust/case-work', 'trust/case-results', 'trust/case-quote', 'trust/case-team' ),
		'question' => array( 'core/paragraph', 'trust/how-i-would' ),
	);

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'post_type_templates' ), 100 );
		add_filter( 'body_class', array( self::class, 'body_class' ) );
		add_action( 'enqueue_block_editor_assets', array( self::class, 'editor_assets' ) );
	}

	/**
	 * Все типы.
	 */
	public static function all(): array {
		return self::TYPES;
	}

	/**
	 * Файл шаблона типа ('' — обычная страница или неизвестный тип).
	 *
	 * @param string $type Тип страницы.
	 */
	public static function template( string $type ): string {
		return self::TYPES[ $type ]['template'] ?? '';
	}

	/**
	 * Тип страницы по её шаблону ('default' — обычная страница или неизвестный шаблон).
	 *
	 * @param int|\WP_Post|null $post Страница; по умолчанию текущая.
	 */
	public static function of( $post = null ): string {
		$template = (string) get_page_template_slug( $post );
		foreach ( self::TYPES as $key => $type ) {
			if ( '' !== $type['template'] && $type['template'] === $template ) {
				return $key;
			}
		}
		return 'default';
	}

	/**
	 * Блоки только своего типа: блок => тип.
	 */
	public static function exclusive(): array {
		return self::EXCLUSIVE;
	}

	/**
	 * Есть ли на странице блок первого экрана (trust/hero-…), который тема умеет вывести.
	 * Если нет, шаблон выводит стандартную шапку страницы: страница без H1 невозможна
	 * (ТЗ, раздел 6.3) — даже если в тексте остался блок, которого в теме нет.
	 *
	 * @param int|\WP_Post|null $post Страница; по умолчанию текущая.
	 */
	public static function has_hero( $post = null ): bool {
		$post = get_post( $post );
		if ( ! $post || ! str_contains( $post->post_content, '<!-- wp:trust/hero-' ) ) {
			return false;
		}
		$registry = \WP_Block_Type_Registry::get_instance();
		foreach ( parse_blocks( $post->post_content ) as $block ) {
			$name = (string) $block['blockName'];
			if ( str_starts_with( $name, 'trust/hero-' ) && $registry->is_registered( $name ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Класс page-type-<тип> у страниц — для стилей шаблона.
	 *
	 * @param string[] $classes Классы body.
	 */
	public static function body_class( array $classes ): array {
		if ( is_page() ) {
			$classes[] = 'page-type-' . self::of( get_queried_object_id() );
		}
		return $classes;
	}

	/**
	 * Набор блоков новых кейсов и вопросов — только из зарегистрированных блоков,
	 * иначе редактор показал бы «блок не поддерживается».
	 */
	public static function post_type_templates(): void {
		foreach ( self::POST_TYPE_TEMPLATES as $type => $blocks ) {
			$object = get_post_type_object( $type );
			if ( ! $object ) {
				continue;
			}
			$template = array();
			foreach ( $blocks as $name ) {
				if ( \WP_Block_Type_Registry::get_instance()->is_registered( $name ) ) {
					$template[] = array( $name );
				}
			}
			// Шаблон из одного пустого абзаца ничего не даёт — не задаём.
			if ( array( array( 'core/paragraph' ) ) !== $template && $template ) {
				$object->template = $template;
			}
		}
	}

	/**
	 * Скрипт окна выбора типа — только в редакторе страниц.
	 */
	public static function editor_assets(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'page' !== $screen->post_type ) {
			return;
		}

		$path = TRUST_DIR . '/assets/js/page-types.js';
		wp_enqueue_script(
			'trust-page-types',
			TRUST_URI . '/assets/js/page-types.js',
			array( 'wp-blocks', 'wp-components', 'wp-data', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-notices', 'wp-plugins' ),
			(string) filemtime( $path ),
			true
		);

		$path = TRUST_DIR . '/assets/css/page-types.css';
		wp_enqueue_style( 'trust-page-types', TRUST_URI . '/assets/css/page-types.css', array( 'wp-components' ), (string) filemtime( $path ) );

		$types = array();
		foreach ( self::TYPES as $key => $type ) {
			$types[] = array(
				'key'         => $key,
				'label'       => $type['label'],
				'description' => $type['description'],
				'template'    => $type['template'],
				'thumb'       => TRUST_URI . '/assets/img/page-types/' . $key . '.jpg',
				'blocks'      => array_map(
					static fn( $block ): array => is_array( $block )
						? array(
							'name'       => $block[0],
							'attributes' => $block[1],
						)
						: array(
							'name'       => $block,
							'attributes' => new \stdClass(),
						),
					$type['blocks']
				),
			);
		}

		wp_add_inline_script(
			'trust-page-types',
			'window.trustPageTypes = ' . wp_json_encode(
				array(
					'types'     => $types,
					'exclusive' => self::EXCLUSIVE,
					'blogPage'  => (int) get_option( 'page_for_posts' ),
				)
			) . ';',
			'before'
		);
	}
}
