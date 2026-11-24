<?php
/**
 * Поля блока «Почему у нас интересно работать» (ТЗ, п. 8.8; макет o-nas/karera.html, «Условия»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'why',
			'eyebrow' => __( 'Условия', 'trust' ),
			'title'   => __( 'Почему у нас интересно работать', 'trust' ),
			'lead'    => '',
		)
	),
	array(
		array(
			'name'         => 'items',
			'label'        => __( 'Пункты', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 1,
			'max'          => 12,
			'button_label' => __( 'Добавить пункт', 'trust' ),
			'instructions' => __( 'Обычно 6 пунктов: на компьютере — по три в ряд.', 'trust' ),
			'collapsed'    => 'field_trust_block_benefits_items_title',
			'sub_fields'   => array(
				Trust\Icons::field(
					array(
						'name'          => 'icon',
						'label'         => __( 'Значок', 'trust' ),
						'choices'       => array(
							'mentor'     => __( 'Наставник', 'trust' ),
							'graduation' => __( 'Обучение', 'trust' ),
							'growth'     => __( 'Рост', 'trust' ),
							'home'       => __( 'Дом, формат работы', 'trust' ),
							'contract'   => __( 'Договор', 'trust' ),
							'trend-up'   => __( 'Результат', 'trust' ),
							'user'       => __( 'Человек', 'trust' ),
							'chat'       => __( 'Общение', 'trust' ),
							'award'      => __( 'Награда', 'trust' ),
							'star'       => __( 'Звезда', 'trust' ),
							'shield'     => __( 'Щит', 'trust' ),
							'sparkles'   => __( 'Идеи', 'trust' ),
							'layers'     => __( 'Слои', 'trust' ),
							'chart'      => __( 'График', 'trust' ),
							'code'       => __( 'Код', 'trust' ),
							'megaphone'  => __( 'Реклама', 'trust' ),
							'check'      => __( 'Галочка', 'trust' ),
						),
						'default_value' => 'check',
						'wrapper'       => array( 'width' => 30 ),
					)
				),
				array(
					'name'      => 'title',
					'label'     => __( 'Заголовок', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 60,
					'wrapper'   => array( 'width' => 70 ),
				),
				array(
					'name'      => 'text',
					'label'     => __( 'Текст', 'trust' ),
					'type'      => 'textarea',
					'rows'      => 2,
					'new_lines' => '',
					'maxlength' => 220,
				),
			),
		),
	)
);
