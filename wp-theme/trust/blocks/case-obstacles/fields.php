<?php
/**
 * Поля блока «Что мешало росту» (ТЗ, п. 8.10; макет portfolio-blog-404/keys.html, «02 · Аудит»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Case_Blocks::fields( __( 'Аудит', 'trust' ), __( 'Что мешало росту', 'trust' ) ),
	array(
		array(
			'name'         => 'cards',
			'label'        => __( 'Карточки', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'max'          => 8,
			'button_label' => __( 'Добавить карточку', 'trust' ),
			'instructions' => __( 'По две в ряд, на телефоне — в колонку. Обычно 4 карточки.', 'trust' ),
			'collapsed'    => 'field_trust_block_case_obstacles_cards_label',
			'sub_fields'   => array(
				array(
					'name'         => 'number',
					'label'        => __( 'Число', 'trust' ),
					'type'         => 'text',
					'required'     => 1,
					'maxlength'    => 12,
					'instructions' => __( 'Например: 42, 8 с, 0.', 'trust' ),
					'wrapper'      => array( 'width' => 25 ),
				),
				array(
					'name'      => 'label',
					'label'     => __( 'Подпись', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 100,
					'wrapper'   => array( 'width' => 75 ),
				),
				array(
					'name'         => 'note',
					'label'        => __( 'Пояснение', 'trust' ),
					'type'         => 'textarea',
					'rows'         => 2,
					'new_lines'    => '',
					'maxlength'    => 200,
					'instructions' => __( 'Необязательно. Мелким текстом под подписью.', 'trust' ),
				),
			),
		),
	)
);
