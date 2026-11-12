<?php
/**
 * Настройки сайта → SEO (только администратор): шаблоны title, разделитель, картинка для
 * соцсетей, порог индексации тегов, текст robots.txt, тип организации и география услуг для
 * микроразметки (Trust\Schema\Graph).
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
		array(
			'name'          => 'seo_org_type',
			'label'         => __( 'Тип организации в микроразметке', 'trust' ),
			'type'          => 'select',
			'choices'       => Trust\Schema\Graph::ORG_TYPES,
			'default_value' => 'Organization',
			'instructions'  => __( '«Компания с офисом» — если клиенты приходят в офис; без адреса в «Контактах» всё равно выводится «Организация».', 'trust' ),
			'wrapper'       => array( 'width' => 50 ),
		),
		array(
			'name'         => 'seo_area_served',
			'label'        => __( 'География услуг', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 100,
			'placeholder'  => 'Россия',
			'instructions' => __( 'Где вы оказываете услуги — свойство areaServed у страниц услуг: страна, город или «весь мир». Пусто — не выводится.', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'         => 'seo_robots',
			'label'        => __( 'robots.txt', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 7,
			'new_lines'    => '',
			'placeholder'  => Trust\Sitemap::ROBOTS_DEFAULT,
			'instructions' => __( 'Пусто — текст по умолчанию (серым): закрыты админка и результаты поиска. Строка «Sitemap: …/wp-sitemap.xml» добавляется сама. Если в папке сайта лежит файл robots.txt, сервер отдаёт его, а не этот текст. Пока сайт закрыт от поисковиков в «Настройки → Чтение», действует стандартный robots.txt WordPress, карта сайта отключена, а страницы получают noindex.', 'trust' ),
		),
	),
);
