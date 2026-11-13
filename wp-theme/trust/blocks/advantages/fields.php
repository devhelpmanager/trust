<?php
/**
 * Поля блока «Наши преимущества» (макет holst-blokov/Advantages, seo-bloki/02-preimushchestva.html).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'advantages',
			'eyebrow' => __( 'Почему мы', 'trust' ),
			'title'   => __( 'Наши преимущества', 'trust' ),
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
			'max'          => 6,
			'button_label' => __( 'Добавить пункт', 'trust' ),
			'collapsed'    => 'field_trust_block_advantages_items_title',
			'instructions' => __( 'До 6 пунктов. Номера 01, 02… ставятся автоматически по порядку.', 'trust' ),
			'sub_fields'   => array(
				Trust\Icons::field(
					array(
						'choices'       => array(
							'eye'      => __( 'Глаз (прозрачность)', 'trust' ),
							'tag'      => __( 'Ценник (стоимость)', 'trust' ),
							'report'   => __( 'Отчёт', 'trust' ),
							'headset'  => __( 'Гарнитура (связь, поддержка)', 'trust' ),
							'layers'   => __( 'Слои (всё в одной команде)', 'trust' ),
							'shield'   => __( 'Щит с галочкой (договор, гарантии)', 'trust' ),
							'user'     => __( 'Человек', 'trust' ),
							'chat'     => __( 'Сообщение', 'trust' ),
							'bolt'     => __( 'Молния (скорость)', 'trust' ),
							'list'     => __( 'Список', 'trust' ),
							'sliders'  => __( 'Настройки', 'trust' ),
							'database' => __( 'База данных', 'trust' ),
							'check'    => __( 'Галочка', 'trust' ),
						),
						'default_value' => 'check',
					)
				),
				array(
					'name'         => 'title',
					'label'        => __( 'Заголовок', 'trust' ),
					'type'         => 'text',
					'required'     => 1,
					'maxlength'    => 80,
					'instructions' => __( 'До 40 символов.', 'trust' ),
				),
				array(
					'name'         => 'text',
					'label'        => __( 'Текст', 'trust' ),
					'type'         => 'textarea',
					'rows'         => 3,
					'new_lines'    => '',
					'maxlength'    => 300,
					'instructions' => __( '1–2 предложения, до 140 символов.', 'trust' ),
				),
			),
		),
	)
);
