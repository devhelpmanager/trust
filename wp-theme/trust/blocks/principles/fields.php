<?php
/**
 * Поля блока «Как мы работаем» (ТЗ, п. 8.3; макет o-nas/o-kompanii.html, «Принципы»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'principles',
			'eyebrow' => __( 'Принципы', 'trust' ),
			'title'   => __( 'Как мы работаем', 'trust' ),
			'lead'    => '',
		)
	),
	array(
		array(
			'name'         => 'items',
			'label'        => __( 'Принципы', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 1,
			'max'          => 12,
			'button_label' => __( 'Добавить принцип', 'trust' ),
			'collapsed'    => 'field_trust_block_principles_items_title',
			'sub_fields'   => array(
				Trust\Icons::field(
					array(
						'name'         => 'icon',
						'label'        => __( 'Значок вместо номера', 'trust' ),
						'choices'      => array(
							'check'  => __( 'Галочка', 'trust' ),
							'report' => __( 'Отчёт', 'trust' ),
							'user'   => __( 'Человек', 'trust' ),
							'layers' => __( 'Слои', 'trust' ),
							'shield' => __( 'Щит', 'trust' ),
							'tag'    => __( 'Цена', 'trust' ),
							'eye'    => __( 'Глаз', 'trust' ),
							'star'   => __( 'Звезда', 'trust' ),
						),
						'allow_null'   => 1,
						'instructions' => __( 'Пусто — номер по порядку: 01, 02…', 'trust' ),
						'wrapper'      => array( 'width' => 30 ),
					)
				),
				array(
					'name'      => 'title',
					'label'     => __( 'Заголовок', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 80,
					'wrapper'   => array( 'width' => 70 ),
				),
				array(
					'name'      => 'text',
					'label'     => __( 'Текст', 'trust' ),
					'type'      => 'textarea',
					'rows'      => 2,
					'new_lines' => '',
					'maxlength' => 240,
				),
			),
		),
	)
);
