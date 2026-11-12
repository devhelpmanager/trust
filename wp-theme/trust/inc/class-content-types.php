<?php
/**
 * Типы записей и таксономии (ТЗ, разделы 5.1 и 5.2).
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Регистрирует кейсы, отзывы, сотрудников, вакансии, вопросы и заявки, их таксономии,
 * переименовывает «Записи» в «Статьи» и «Метки» в «Теги».
 *
 * Архивов у типов записей нет: списки выводят обычные страницы с блоками
 * (/portfolio/, /komanda/, /seo/voprosy/), а записи живут внутри их адресов (Urls).
 *
 * Права:
 * - кейсы, отзывы, сотрудники, вакансии — как у страниц: редактор и администратор;
 * - вопросы — как у статей: авторы тоже могут отвечать;
 * - заявки — только редактор и администратор, создать заявку вручную нельзя
 *   (заявки приходят из форм сайта, шаг 3.5).
 *
 * Подписи в админке собираются из форм слова (labels()); тема русскоязычная,
 * поэтому шаблоны подписей не переводятся, переводятся сами слова.
 */
final class Content_Types {

	/**
	 * Типы записей из одних полей (без текста в редакторе) открываются на классическом
	 * экране: заголовок и поля на одной странице, а не под пустым холстом редактора блоков.
	 */
	private const CLASSIC_EDITOR = array( 'review', 'team', 'vacancy' );

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_taxonomies' ), 9 );
		add_action( 'init', array( self::class, 'register_post_types' ) );
		add_filter( 'post_type_labels_post', array( self::class, 'post_labels' ) );
		add_filter( 'taxonomy_labels_post_tag', array( self::class, 'tag_labels' ) );
		add_filter( 'taxonomy_labels_category', array( self::class, 'category_labels' ) );
		add_filter( 'use_block_editor_for_post_type', array( self::class, 'use_block_editor' ), 10, 2 );
	}

	/**
	 * Редактор блоков — только у типов записей с содержимым из блоков.
	 *
	 * @param bool   $use_block_editor Использовать ли редактор блоков.
	 * @param string $post_type        Тип записи.
	 */
	public static function use_block_editor( $use_block_editor, $post_type ) {
		return in_array( $post_type, self::CLASSIC_EDITOR, true ) ? false : $use_block_editor;
	}

	/**
	 * Таксономии. Регистрируются раньше типов записей, чтобы привязка была готова.
	 */
	public static function register_taxonomies(): void {
		$taxonomies = array(
			'service_type'   => array(
				'objects' => array( 'case', 'review' ),
				'words'   => array( __( 'Услуга', 'trust' ), __( 'Услуги', 'trust' ), __( 'услугу', 'trust' ), __( 'услуг', 'trust' ) ),
			),
			'industry'       => array(
				'objects' => array( 'case' ),
				'words'   => array( __( 'Отрасль', 'trust' ), __( 'Отрасли', 'trust' ), __( 'отрасль', 'trust' ), __( 'отраслей', 'trust' ) ),
			),
			'department'     => array(
				'objects' => array( 'team', 'vacancy' ),
				'words'   => array( __( 'Отдел', 'trust' ), __( 'Отделы', 'trust' ), __( 'отдел', 'trust' ), __( 'отделов', 'trust' ) ),
			),
			'question_topic' => array(
				'objects' => array( 'question' ),
				'words'   => array( __( 'Тема вопроса', 'trust' ), __( 'Темы вопросов', 'trust' ), __( 'тему вопроса', 'trust' ), __( 'тем вопросов', 'trust' ) ),
			),
		);

		foreach ( $taxonomies as $name => $taxonomy ) {
			list( $one, $many, $acc, $gen ) = $taxonomy['words'];

			register_taxonomy(
				$name,
				$taxonomy['objects'],
				array(
					'labels'             => self::taxonomy_labels( $one, $many, $acc, $gen ),
					// Без публичных страниц: термины нужны для фильтров и плашек.
					'public'             => false,
					'publicly_queryable' => false,
					'show_ui'            => true,
					'show_in_menu'       => true,
					'show_in_rest'       => true,
					'show_in_nav_menus'  => false,
					'show_tagcloud'      => false,
					'show_admin_column'  => true,
					// Флажки в редакторе вместо поля с метками.
					'hierarchical'       => true,
					'rewrite'            => false,
					'query_var'          => false,
				)
			);
		}
	}

	/**
	 * Типы записей.
	 */
	public static function register_post_types(): void {
		$public  = array(
			'public'       => true,
			'show_in_rest' => true,
			'has_archive'  => false,
		);
		$private = array(
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_in_nav_menus'   => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
		);

		$types = array(
			'question' => $public + array(
				'labels'          => self::labels( __( 'Вопрос', 'trust' ), __( 'Вопросы', 'trust' ), __( 'вопрос', 'trust' ), __( 'вопросов', 'trust' ), false, __( 'Вопросы SEO', 'trust' ) ),
				'description'     => __( 'Вопрос читателя и ответ специалиста. Текст записи — ответ.', 'trust' ),
				'menu_icon'       => 'dashicons-format-chat',
				'menu_position'   => 6,
				'capability_type' => 'post',
				'supports'        => array( 'title', 'editor', 'author', 'excerpt', 'revisions' ),
				// Теги общие для статей и ответов (ТЗ, раздел 5.2).
				'taxonomies'      => array( 'post_tag' ),
				'rewrite'         => self::rewrite( 'question' ),
			),
			'case'     => $public + array(
				'labels'          => self::labels( __( 'Кейс', 'trust' ), __( 'Кейсы', 'trust' ), __( 'кейс', 'trust' ), __( 'кейсов', 'trust' ), false, '', array( __( 'Обложка', 'trust' ), __( 'обложку', 'trust' ) ) ),
				'description'     => __( 'Кейсы портфолио.', 'trust' ),
				'menu_icon'       => 'dashicons-portfolio',
				'menu_position'   => 21,
				'capability_type' => 'page',
				'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
				'rewrite'         => self::rewrite( 'case' ),
			),
			'review'   => $private + array(
				'labels'          => self::labels( __( 'Отзыв', 'trust' ), __( 'Отзывы', 'trust' ), __( 'отзыв', 'trust' ), __( 'отзывов', 'trust' ) ),
				'description'     => __( 'Текстовые и видеоотзывы, благодарственные письма. Выводятся в блоках.', 'trust' ),
				'menu_icon'       => 'dashicons-format-quote',
				'menu_position'   => 22,
				'capability_type' => 'page',
				'supports'        => array( 'title', 'revisions' ),
			),
			'team'     => $public + array(
				'labels'          => self::labels( __( 'Сотрудник', 'trust' ), __( 'Сотрудники', 'trust' ), __( 'сотрудника', 'trust' ), __( 'сотрудников', 'trust' ), false, '', array( __( 'Фото', 'trust' ), __( 'фото', 'trust' ) ) ),
				'description'     => __( 'Сотрудники. Страница сотрудника — это и страница автора статей.', 'trust' ),
				'menu_icon'       => 'dashicons-groups',
				'menu_position'   => 23,
				'capability_type' => 'page',
				'supports'        => array( 'title', 'thumbnail', 'page-attributes', 'revisions' ),
				'rewrite'         => self::rewrite( 'team' ),
			),
			'vacancy'  => $private + array(
				'labels'          => self::labels( __( 'Вакансия', 'trust' ), __( 'Вакансии', 'trust' ), __( 'вакансию', 'trust' ), __( 'вакансий', 'trust' ), true ),
				'description'     => __( 'Вакансии для страницы «Карьера».', 'trust' ),
				'menu_icon'       => 'dashicons-id-alt',
				'menu_position'   => 24,
				'capability_type' => 'page',
				'supports'        => array( 'title', 'page-attributes', 'revisions' ),
			),
			'lead'     => array_merge(
				$private,
				array(
					'labels'            => self::labels( __( 'Заявка', 'trust' ), __( 'Заявки', 'trust' ), __( 'заявку', 'trust' ), __( 'заявок', 'trust' ), true ),
					'description'       => __( 'Заявки из форм сайта.', 'trust' ),
					'menu_icon'         => 'dashicons-email-alt',
					'menu_position'     => 3,
					'show_in_rest'      => false,
					'show_in_admin_bar' => false,
					'capability_type'   => 'post',
					'capabilities'      => self::lead_capabilities(),
					'map_meta_cap'      => true,
					'supports'          => array( 'title' ),
				)
			),
		);

		foreach ( $types as $name => $args ) {
			register_post_type( $name, $args );
		}
	}

	/**
	 * Адрес записи внутри адреса страницы-списка, без префикса /blog/.
	 *
	 * @param string $type Тип записи.
	 */
	private static function rewrite( string $type ): array {
		return array(
			'slug'       => Urls::slug( $type ),
			'with_front' => false,
			'feeds'      => false,
			'pages'      => false,
		);
	}

	/**
	 * Права на заявки: всё — с правом редактора, создавать вручную нельзя никому.
	 */
	private static function lead_capabilities(): array {
		$caps = array_fill_keys(
			array(
				'edit_posts',
				'edit_others_posts',
				'edit_private_posts',
				'edit_published_posts',
				'publish_posts',
				'read_private_posts',
				'read',
				'delete_posts',
				'delete_others_posts',
				'delete_private_posts',
				'delete_published_posts',
			),
			'edit_others_posts'
		);

		$caps['create_posts'] = 'do_not_allow';
		return $caps;
	}

	/**
	 * Подписи типа записи.
	 *
	 * @param string $one    Именительный падеж: «Кейс».
	 * @param string $many   Множественное число: «Кейсы».
	 * @param string $acc    Винительный падеж: «кейс», «сотрудника», «вакансию».
	 * @param string $gen    Родительный падеж множественного числа: «кейсов».
	 * @param bool   $female Женский род: «Новая вакансия», «Заявка обновлена».
	 * @param string $menu   Название в меню, если отличается от $many.
	 * @param array  $image  Изображение записи: именительный и винительный падеж
	 *                       (array( 'Обложка', 'обложку' )), пусто — подписи WordPress.
	 */
	private static function labels( string $one, string $many, string $acc, string $gen, bool $female = false, string $menu = '', array $image = array() ): array {
		$lower = self::lower( $one );
		$list  = self::lower( $many );
		$g     = static fn( string $masculine, string $feminine ): string => $female ? $feminine : $masculine;

		$labels = array(
			'name'                     => $menu ? $menu : $many,
			'singular_name'            => $one,
			'menu_name'                => $menu ? $menu : $many,
			'name_admin_bar'           => $one,
			'add_new'                  => 'Добавить',
			'add_new_item'             => 'Добавить ' . $acc,
			'edit_item'                => 'Редактировать ' . $acc,
			'new_item'                 => $g( 'Новый ', 'Новая ' ) . $lower,
			'view_item'                => 'Посмотреть ' . $acc,
			'view_items'               => 'Посмотреть ' . $list,
			'search_items'             => 'Найти ' . $list,
			'not_found'                => $many . ' не найдены.',
			'not_found_in_trash'       => 'В корзине нет ' . $gen . '.',
			'parent_item_colon'        => 'Родительский элемент:',
			'all_items'                => 'Все ' . $list,
			'archives'                 => $many,
			'attributes'               => 'Свойства',
			'insert_into_item'         => 'Вставить',
			'uploaded_to_this_item'    => 'Загружено для этой записи',
			'filter_items_list'        => 'Фильтр списка',
			'filter_by_date'           => 'Фильтр по дате',
			'items_list_navigation'    => 'Навигация по списку',
			'items_list'               => 'Список: ' . $list,
			'item_published'           => $one . $g( ' опубликован.', ' опубликована.' ),
			'item_published_privately' => $one . $g( ' опубликован как личный.', ' опубликована как личная.' ),
			'item_reverted_to_draft'   => $one . $g( ' переведён в черновики.', ' переведена в черновики.' ),
			'item_trashed'             => $one . $g( ' перемещён в корзину.', ' перемещена в корзину.' ),
			'item_scheduled'           => $one . $g( ' запланирован.', ' запланирована.' ),
			'item_updated'             => $one . $g( ' обновлён.', ' обновлена.' ),
			'item_link'                => 'Ссылка: ' . $lower,
			'item_link_description'    => 'Ссылка на запись «' . $lower . '».',
		);

		if ( $image ) {
			list( $name, $image_acc )        = $image;
			$labels['featured_image']        = $name;
			$labels['set_featured_image']    = 'Выбрать ' . $image_acc;
			$labels['remove_featured_image'] = 'Удалить ' . $image_acc;
			$labels['use_featured_image']    = 'Использовать как ' . $image_acc;
		}

		return $labels;
	}

	/**
	 * Подписи таксономии.
	 *
	 * @param string $one  Именительный падеж: «Услуга».
	 * @param string $many Множественное число: «Услуги».
	 * @param string $acc  Винительный падеж: «услугу».
	 * @param string $gen  Родительный падеж множественного числа: «услуг».
	 */
	private static function taxonomy_labels( string $one, string $many, string $acc, string $gen ): array {
		$list = self::lower( $many );

		return array(
			'name'                       => $many,
			'singular_name'              => $one,
			'menu_name'                  => $many,
			'search_items'               => 'Найти ' . $list,
			'popular_items'              => 'Часто используемые',
			'all_items'                  => 'Все ' . $list,
			'parent_item'                => 'Родительский элемент',
			'parent_item_colon'          => 'Родительский элемент:',
			'edit_item'                  => 'Изменить ' . $acc,
			'view_item'                  => 'Посмотреть ' . $acc,
			'update_item'                => 'Обновить ' . $acc,
			'add_new_item'               => 'Добавить ' . $acc,
			'new_item_name'              => 'Название',
			'separate_items_with_commas' => 'Разделяйте запятыми',
			'add_or_remove_items'        => 'Добавить или удалить',
			'choose_from_most_used'      => 'Выбрать из часто используемых',
			'not_found'                  => $many . ' не найдены.',
			'no_terms'                   => 'Нет ' . $gen,
			'filter_by_item'             => 'Фильтр: ' . $list,
			'items_list_navigation'      => 'Навигация по списку',
			'items_list'                 => 'Список: ' . $list,
			'most_used'                  => 'Часто используемые',
			'back_to_items'              => '← Вернуться к списку',
			'item_link'                  => 'Ссылка',
			'item_link_description'      => 'Ссылка на элемент.',
		);
	}

	/**
	 * Строчными буквами — без расширения mbstring, которого может не быть на сервере.
	 *
	 * @param string $text Строка («Вопросы» → «вопросы»).
	 */
	private static function lower( string $text ): string {
		static $map = null;

		if ( null === $map ) {
			$map = array_combine(
				preg_split( '//u', 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ', -1, PREG_SPLIT_NO_EMPTY ),
				preg_split( '//u', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя', -1, PREG_SPLIT_NO_EMPTY )
			);
		}
		return strtolower( strtr( $text, $map ) );
	}

	/**
	 * «Записи» → «Статьи» (ТЗ, раздел 5.1).
	 *
	 * @param object $labels Подписи типа записи post.
	 */
	public static function post_labels( $labels ) {
		$custom = self::labels( __( 'Статья', 'trust' ), __( 'Статьи', 'trust' ), __( 'статью', 'trust' ), __( 'статей', 'trust' ), true, '', array( __( 'Обложка', 'trust' ), __( 'обложку', 'trust' ) ) );

		foreach ( $custom as $key => $value ) {
			$labels->$key = $value;
		}
		return $labels;
	}

	/**
	 * Подсказки к стандартным полям рубрики: где выводятся название и описание.
	 *
	 * @param object $labels Подписи таксономии category.
	 */
	public static function category_labels( $labels ) {
		$labels->name_field_description = 'Заголовок H1 страницы рубрики и название в полосе рубрик.';
		$labels->desc_field_description = 'Подзаголовок под H1 на странице рубрики. 1–2 предложения, до 200 символов.';
		return $labels;
	}

	/**
	 * «Метки» → «Теги» (ТЗ, раздел 5.2).
	 *
	 * @param object $labels Подписи таксономии post_tag.
	 */
	public static function tag_labels( $labels ) {
		$custom = array_merge(
			self::taxonomy_labels( __( 'Тег', 'trust' ), __( 'Теги', 'trust' ), __( 'тег', 'trust' ), __( 'тегов', 'trust' ) ),
			array(
				'popular_items'              => 'Популярные теги',
				'separate_items_with_commas' => 'Теги разделяются запятыми',
				'add_or_remove_items'        => 'Добавить или удалить теги',
				'choose_from_most_used'      => 'Выбрать из часто используемых тегов',
				'name_field_description'     => 'Заголовок H1 страницы тега (выводится с #).',
				'desc_field_description'     => 'Подзаголовок под H1 на странице тега: о чём материалы с этим тегом. 1–2 предложения, до 200 символов.',
			)
		);

		foreach ( $custom as $key => $value ) {
			$labels->$key = $value;
		}
		return $labels;
	}
}
