<?php
/**
 * Поля блока «С чем пришёл клиент» (ТЗ, п. 8.10; макет portfolio-blog-404/keys.html, «01 · Задача»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Case_Blocks::fields( __( 'Задача', 'trust' ), __( 'С чем пришёл клиент', 'trust' ) ),
	array(
		array(
			'name'         => 'text',
			'label'        => __( 'Текст', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 4,
			'new_lines'    => 'wpautop',
			'maxlength'    => 1200,
			'instructions' => __( 'Чем занимается компания, кто её клиенты, какую задачу поставили — 2–3 предложения.', 'trust' ),
		),
		array(
			'name'         => 'problems',
			'label'        => __( 'Проблемы', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'max'          => 6,
			'button_label' => __( 'Добавить проблему', 'trust' ),
			'instructions' => __( 'Список с красными значками. Обычно 3 пункта. Заголовок необязателен — выводится жирным перед текстом.', 'trust' ),
			'collapsed'    => 'field_trust_block_case_task_problems_text',
			'sub_fields'   => array(
				array(
					'name'      => 'title',
					'label'     => __( 'Заголовок', 'trust' ),
					'type'      => 'text',
					'maxlength' => 60,
					'wrapper'   => array( 'width' => 35 ),
				),
				array(
					'name'      => 'text',
					'label'     => __( 'Текст', 'trust' ),
					'type'      => 'textarea',
					'rows'      => 2,
					'new_lines' => '',
					'maxlength' => 240,
					'wrapper'   => array( 'width' => 65 ),
				),
			),
		),
	)
);
