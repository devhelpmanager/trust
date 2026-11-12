<?php
/**
 * Группа «SEO» (ТЗ, раздел 10.1): у страниц, статей, кейсов, ответов, сотрудников, рубрик и тегов.
 *
 * Пустые поля — значения по правилам Trust\Seo (шаблон title из «Настроек сайта → SEO»,
 * анонс или начало текста, заголовок записи, адрес самой страницы). Счётчики символов и
 * предпросмотр сниппета — assets/js/seo-fields.js.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'      => __( 'SEO', 'trust' ),
	'locations'  => array(
		array( 'post_type', 'page' ),
		array( 'post_type', 'post' ),
		array( 'post_type', 'case' ),
		array( 'post_type', 'question' ),
		array( 'post_type', 'team' ),
		array( 'taxonomy', 'category' ),
		array( 'taxonomy', 'post_tag' ),
	),
	'position'   => 'side',
	'menu_order' => 100,
	'fields'     => array(
		array(
			'name'     => 'trust_seo_preview',
			'label'    => __( 'Как выглядит в поиске', 'trust' ),
			'type'     => 'message',
			'message'  => '<div class="trust-seo-preview" aria-live="polite"></div>',
			'esc_html' => 0,
		),
		array(
			'name'         => 'trust_seo_title',
			'label'        => __( 'SEO title', 'trust' ),
			'type'         => 'text',
			'instructions' => __( 'Заголовок в поиске и во вкладке браузера, до 60 символов. Пусто — по шаблону из «Настроек сайта → SEO». Можно {site} — название сайта и {sep} — разделитель.', 'trust' ),
		),
		array(
			'name'         => 'trust_seo_description',
			'label'        => __( 'Meta description', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 3,
			'new_lines'    => '',
			'instructions' => __( 'Описание в поиске, до 160 символов. Пусто — анонс, вводный текст первого экрана или начало текста.', 'trust' ),
		),
		array(
			'name'         => 'trust_seo_h1',
			'label'        => __( 'H1', 'trust' ),
			'type'         => 'text',
			'instructions' => __( 'Главный заголовок на странице. Пусто — заголовок записи (название рубрики или тега). Можно {site}.', 'trust' ),
		),
		array(
			'name'  => 'trust_crumbs_accordion',
			'label' => __( 'Хлебные крошки', 'trust' ),
			'type'  => 'accordion',
			'open'  => 0,
		),
		array(
			'name'         => 'trust_crumb_title',
			'label'        => __( 'Название в хлебных крошках', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 60,
			'instructions' => __( 'Короткое название для цепочки «Главная / … / Страница»: «Технический аудит» вместо «Технический аудит сайта». Пусто — заголовок.', 'trust' ),
		),
		array(
			'name'          => 'trust_crumb_section',
			'label'         => __( 'Раздел в хлебных крошках', 'trust' ),
			'type'          => 'link',
			'return_format' => 'array',
			'instructions'  => __( 'Для страницы без родителя: пункт перед ней, например «О нас» → страница «О компании». Пусто — «Главная / Страница».', 'trust' ),
		),
		array(
			'name'  => 'trust_seo_index_section',
			'label' => __( 'Индексация и canonical', 'trust' ),
			'type'  => 'accordion',
			'open'  => 0,
		),
		array(
			'name'         => 'trust_seo_noindex',
			'label'        => __( 'noindex', 'trust' ),
			'type'         => 'true_false',
			'ui'           => 1,
			'message'      => __( 'Не показывать в поиске', 'trust' ),
			'instructions' => __( 'Страница убирается из поиска и из карты сайта.', 'trust' ),
		),
		array(
			'name'    => 'trust_seo_nofollow',
			'label'   => __( 'nofollow', 'trust' ),
			'type'    => 'true_false',
			'ui'      => 1,
			'message' => __( 'Не переходить по ссылкам страницы', 'trust' ),
		),
		array(
			'name'         => 'trust_seo_canonical',
			'label'        => __( 'Canonical', 'trust' ),
			'type'         => 'url',
			'placeholder'  => 'https://',
			'instructions' => __( 'Пусто — адрес самой страницы. Укажите другой адрес, только если эта страница — копия той.', 'trust' ),
		),
		array(
			'name'  => 'trust_og_section',
			'label' => __( 'Соцсети (Open Graph)', 'trust' ),
			'type'  => 'accordion',
			'open'  => 0,
		),
		array(
			'name'         => 'trust_og_title',
			'label'        => __( 'Заголовок для соцсетей', 'trust' ),
			'type'         => 'text',
			'instructions' => __( 'Пусто — SEO title.', 'trust' ),
		),
		array(
			'name'         => 'trust_og_description',
			'label'        => __( 'Описание для соцсетей', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 2,
			'new_lines'    => '',
			'instructions' => __( 'Пусто — meta description.', 'trust' ),
		),
		array(
			'name'          => 'trust_og_image',
			'label'         => __( 'Картинка для соцсетей', 'trust' ),
			'type'          => 'image',
			'return_format' => 'id',
			'preview_size'  => 'medium',
			'library'       => 'all',
			'mime_types'    => 'jpg, jpeg, png, webp',
			'instructions'  => __( '1200×630. Пусто — обложка записи или картинка по умолчанию из «Настроек сайта → SEO».', 'trust' ),
		),
	),
);
