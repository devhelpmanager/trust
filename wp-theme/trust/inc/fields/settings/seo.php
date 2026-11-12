<?php
/**
 * Настройки сайта → SEO (только администратор): шаблоны title, разделитель, картинка для
 * соцсетей, порог индексации тегов. Тип организации для микроразметки — шаг 3.4,
 * текст robots.txt — шаг 3.3.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

/**
 * Поле шаблона title. Шаблон по умолчанию — серой подсказкой (Trust\Seo::TITLE_TEMPLATES).
 *
 * @param string $type  Тип записи, таксономия или special-ключ (search, 404).
 * @param string $label Подпись.
 */
$trust_template = static fn( string $type, string $label ): array => array(
	'name'        => 'seo_title_' . $type,
	'label'       => $label,
	'type'        => 'text',
	'placeholder' => Trust\Seo::TITLE_TEMPLATES[ $type ],
	'wrapper'     => array( 'width' => 50 ),
);

return array(
	'label'  => __( 'SEO', 'trust' ),
	'cap'    => 'manage_options',
	'fields' => array(
		array(
			'name'    => 'seo_templates_heading',
			'label'   => __( 'Шаблоны заголовков (title)', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Используются, если у записи не заполнено поле «SEO title». {title} — заголовок записи или название рубрики, {site} — название сайта (бренд), {sep} — разделитель, {query} — поисковый запрос. На страницах 2, 3… к заголовку добавляется « — страница N». Пустое поле — шаблон по умолчанию (серым).', 'trust' ),
		),
		$trust_template( 'page', __( 'Страницы', 'trust' ) ),
		$trust_template( 'post', __( 'Статьи', 'trust' ) ),
		$trust_template( 'case', __( 'Кейсы', 'trust' ) ),
		$trust_template( 'question', __( 'Ответы на вопросы', 'trust' ) ),
		$trust_template( 'team', __( 'Сотрудники', 'trust' ) ),
		$trust_template( 'category', __( 'Рубрики', 'trust' ) ),
		$trust_template( 'post_tag', __( 'Теги', 'trust' ) ),
		$trust_template( 'search', __( 'Поиск', 'trust' ) ),
		array(
			'name'          => 'seo_separator',
			'label'         => __( 'Разделитель {sep}', 'trust' ),
			'type'          => 'select',
			'choices'       => array_combine( Trust\Seo::SEPARATORS, Trust\Seo::SEPARATORS ),
			'default_value' => '—',
			'wrapper'       => array( 'width' => 50 ),
		),
		array(
			'name'         => 'seo_tag_threshold',
			'label'        => __( 'Порог индексации тегов', 'trust' ),
			'type'         => 'number',
			'min'          => 0,
			'max'          => 100,
			'placeholder'  => '3',
			'instructions' => __( 'Тег, у которого статей и ответов меньше этого числа, получает noindex и не попадает в карту сайта. Пусто — 3, 0 — индексировать все.', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'          => 'seo_og_image',
			'label'         => __( 'Картинка для соцсетей по умолчанию', 'trust' ),
			'type'          => 'image',
			'return_format' => 'id',
			'preview_size'  => 'medium',
			'library'       => 'all',
			'mime_types'    => 'jpg, jpeg, png, webp',
			'instructions'  => __( '1200×630. Для страниц без своей картинки и обложки, когда ссылкой делятся в мессенджерах и соцсетях.', 'trust' ),
		),
	),
);
