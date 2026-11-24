<?php
/**
 * Поля блока «Стажировка» (ТЗ, п. 8.8; макет o-nas/karera.html, «Стажировка в SEO-отделе»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'name'         => 'anchor',
		'label'        => __( 'Якорь', 'trust' ),
		'type'         => 'text',
		'placeholder'  => 'internship',
		'instructions' => __( 'Необязательно. Латиница, цифры и дефис, без #.', 'trust' ),
		'wrapper'      => array( 'width' => 50 ),
	),
	array(
		'name'          => 'badge',
		'label'         => __( 'Метка над заголовком', 'trust' ),
		'type'          => 'text',
		'default_value' => __( 'Без опыта', 'trust' ),
		'maxlength'     => 30,
		'wrapper'       => array( 'width' => 50 ),
	),
	array(
		'name'          => 'title',
		'label'         => __( 'Заголовок (H2)', 'trust' ),
		'type'          => 'text',
		'required'      => 1,
		'default_value' => __( 'Стажировка в SEO-отделе', 'trust' ),
		'maxlength'     => 80,
	),
	array(
		'name'          => 'text',
		'label'         => __( 'Текст', 'trust' ),
		'type'          => 'textarea',
		'rows'          => 3,
		'new_lines'     => '',
		'maxlength'     => 400,
		'default_value' => __( '[N] месяц с наставником на реальных проектах. Тем, кто успешно прошёл стажировку, предлагаем место в штате.', 'trust' ),
	),
	array(
		'name'         => 'items',
		'label'        => __( 'Пункты', 'trust' ),
		'type'         => 'textarea',
		'rows'         => 3,
		'new_lines'    => '',
		'maxlength'    => 600,
		'instructions' => __( 'Необязательно. Каждый пункт — с новой строки: например, «Оплачиваемая стажировка». Выводятся списком с галочками под текстом.', 'trust' ),
	),
	array(
		'name'          => 'button',
		'label'         => __( 'Текст кнопки', 'trust' ),
		'type'          => 'text',
		'default_value' => __( 'Подать заявку', 'trust' ),
		'maxlength'     => 40,
		'wrapper'       => array( 'width' => 50 ),
	),
	array(
		'name'          => 'vacancy',
		'label'         => __( 'Вакансия в форме резюме', 'trust' ),
		'type'          => 'post_object',
		'post_type'     => array( 'vacancy' ),
		'return_format' => 'id',
		'allow_null'    => 1,
		'ui'            => 1,
		'instructions'  => __( 'Кнопка ведёт к форме «Отправить резюме» и выбирает в ней эту вакансию. Пусто — пункт «Стажировка».', 'trust' ),
		'wrapper'       => array( 'width' => 50 ),
	),
);
