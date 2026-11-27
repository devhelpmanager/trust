<?php
/**
 * Поля страницы блога — страницы, назначенной в «Настройки → Чтение → Страница записей» (ТЗ,
 * раздел 6.2): подзаголовок под H1 списка статей. H1 — SEO-поле страницы или её заголовок.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'     => __( 'Страница блога', 'trust' ),
	'locations' => array( array( 'page_type', 'posts_page' ) ),
	'position'  => 'side',
	'fields'    => array(
		array(
			'name'         => 'trust_blog_lead',
			'label'        => __( 'Подзаголовок', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 3,
			'new_lines'    => '',
			'maxlength'    => 300,
			'instructions' => __( 'Текст под заголовком «Блог» в списке статей. Пусто — без подзаголовка.', 'trust' ),
		),
	),
);
