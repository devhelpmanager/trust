<?php
/**
 * Поля блока «Карточки с плашкой» (макет o-nas/politika-cookie.html, категории cookie). В ТЗ
 * блока нет — добавлен по макету (инвентарь, раздел 7.6).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'name'         => 'items',
		'label'        => __( 'Карточки', 'trust' ),
		'type'         => 'repeater',
		'layout'       => 'table',
		'min'          => 1,
		'max'          => 6,
		'button_label' => __( 'Добавить карточку', 'trust' ),
		'instructions' => __( 'Обычно 3 карточки — в ряд; на узком экране — одна под другой. Плашка первой карточки — темнее.', 'trust' ),
		'sub_fields'   => array(
			array(
				'name'      => 'label',
				'label'     => __( 'Плашка', 'trust' ),
				'type'      => 'text',
				'required'  => 1,
				'maxlength' => 40,
				'wrapper'   => array( 'width' => 30 ),
			),
			array(
				'name'      => 'text',
				'label'     => __( 'Текст', 'trust' ),
				'type'      => 'textarea',
				'rows'      => 2,
				'new_lines' => '',
				'required'  => 1,
				'maxlength' => 300,
			),
		),
	),
);
