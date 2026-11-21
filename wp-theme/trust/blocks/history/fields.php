<?php
/**
 * Поля блока «Как мы росли» (ТЗ, п. 8.3; макет o-nas/o-kompanii.html, «История»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'history',
			'eyebrow' => __( 'История', 'trust' ),
			'title'   => __( 'Как мы росли', 'trust' ),
			'lead'    => '',
		)
	),
	array(
		array(
			'name'         => 'items',
			'label'        => __( 'События', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'min'          => 1,
			'max'          => 10,
			'button_label' => __( 'Добавить событие', 'trust' ),
			'instructions' => __( 'По порядку, последнее — сейчас (выделено линией).', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'        => 'year',
					'label'       => __( 'Год', 'trust' ),
					'type'        => 'text',
					'required'    => 1,
					'maxlength'   => 12,
					'placeholder' => '2015',
					'wrapper'     => array( 'width' => 18 ),
				),
				array(
					'name'      => 'title',
					'label'     => __( 'Заголовок', 'trust' ),
					'type'      => 'text',
					'maxlength' => 60,
					'wrapper'   => array( 'width' => 30 ),
				),
				array(
					'name'      => 'text',
					'label'     => __( 'Текст', 'trust' ),
					'type'      => 'textarea',
					'rows'      => 2,
					'new_lines' => '',
					'maxlength' => 160,
				),
			),
		),
	)
);
