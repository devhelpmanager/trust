<?php
/**
 * Поля блока «Что сделали» (ТЗ, п. 8.10; макет portfolio-blog-404/keys.html, «03 · Работы»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Case_Blocks::fields( __( 'Работы', 'trust' ), __( 'Что сделали', 'trust' ) ),
	array(
		array(
			'name'         => 'steps',
			'label'        => __( 'Этапы', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'max'          => 12,
			'button_label' => __( 'Добавить этап', 'trust' ),
			'instructions' => __( 'Этапы на шкале сверху вниз. Последний — с пустым кружком (то, что продолжается: отчёты, сопровождение).', 'trust' ),
			'collapsed'    => 'field_trust_block_case_work_steps_title',
			'sub_fields'   => array(
				array(
					'name'         => 'term',
					'label'        => __( 'Срок', 'trust' ),
					'type'         => 'text',
					'maxlength'    => 30,
					'instructions' => __( 'Например: Месяц 1, Месяцы 2–3, Каждый месяц.', 'trust' ),
					'wrapper'      => array( 'width' => 35 ),
				),
				array(
					'name'      => 'title',
					'label'     => __( 'Заголовок (H3)', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 80,
					'wrapper'   => array( 'width' => 65 ),
				),
				array(
					'name'      => 'text',
					'label'     => __( 'Текст', 'trust' ),
					'type'      => 'textarea',
					'rows'      => 3,
					'new_lines' => '',
					'maxlength' => 400,
				),
			),
		),
	)
);
