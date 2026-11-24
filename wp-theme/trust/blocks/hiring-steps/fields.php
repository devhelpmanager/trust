<?php
/**
 * Поля блока «Как проходит отбор» (ТЗ, п. 8.8; макет o-nas/karera.html, «Отбор»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'hiring',
			'eyebrow' => __( 'Отбор', 'trust' ),
			'title'   => __( 'Как проходит отбор', 'trust' ),
			'lead'    => __( 'Отвечаем на каждый отклик в течение [N] рабочих дней.', 'trust' ),
		)
	),
	array(
		array(
			'name'         => 'steps',
			'label'        => __( 'Шаги', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 1,
			'max'          => 8,
			'button_label' => __( 'Добавить шаг', 'trust' ),
			'instructions' => __( 'Номера 1, 2, 3… ставятся по порядку. Последний шаг выделен фиолетовым. Обычно 4 шага.', 'trust' ),
			'collapsed'    => 'field_trust_block_hiring_steps_steps_title',
			'sub_fields'   => array(
				array(
					'name'      => 'title',
					'label'     => __( 'Заголовок', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 60,
				),
				array(
					'name'      => 'text',
					'label'     => __( 'Текст', 'trust' ),
					'type'      => 'textarea',
					'rows'      => 2,
					'new_lines' => '',
					'maxlength' => 200,
				),
			),
		),
	)
);
