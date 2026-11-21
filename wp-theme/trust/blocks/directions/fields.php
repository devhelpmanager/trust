<?php
/**
 * Поля блока «Чем мы занимаемся» (ТЗ, п. 8.3; макет holst-o-nas/Company, o-nas/o-kompanii.html).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'services',
			'eyebrow' => __( 'Направления', 'trust' ),
			'title'   => __( 'Чем мы занимаемся', 'trust' ),
			'lead'    => '',
		)
	),
	array(
		array(
			'name'         => 'items',
			'label'        => __( 'Направления', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 1,
			'max'          => 9,
			'button_label' => __( 'Добавить направление', 'trust' ),
			'collapsed'    => 'field_trust_block_directions_items_title',
			'sub_fields'   => array(
				Trust\Icons::field(
					array(
						'name'          => 'icon',
						'label'         => __( 'Значок', 'trust' ),
						'choices'       => array(
							'search'       => __( 'Поиск (SEO)', 'trust' ),
							'sparkles'     => __( 'Звёзды (GEO)', 'trust' ),
							'browser-code' => __( 'Сайт с кодом', 'trust' ),
							'wrench'       => __( 'Ключ (поддержка)', 'trust' ),
							'megaphone'    => __( 'Рупор (реклама)', 'trust' ),
							'bot'          => __( 'Робот (AI)', 'trust' ),
							'layers'       => __( 'Слои', 'trust' ),
							'code'         => __( 'Код', 'trust' ),
							'report'       => __( 'Отчёт', 'trust' ),
							'shield'       => __( 'Щит', 'trust' ),
							'chat'         => __( 'Сообщение', 'trust' ),
							'star'         => __( 'Звезда', 'trust' ),
						),
						'default_value' => 'search',
						'wrapper'       => array( 'width' => 30 ),
					)
				),
				array(
					'name'      => 'title',
					'label'     => __( 'Название', 'trust' ),
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
					'maxlength' => 200,
				),
				array(
					'name'           => 'link',
					'label'          => __( 'Страница направления', 'trust' ),
					'type'           => 'page_link',
					'post_type'      => array( 'page' ),
					'allow_null'     => 1,
					'allow_archives' => 0,
					'instructions'   => __( 'Необязательно. Есть страница — вся карточка ведёт на неё, внизу «Подробнее».', 'trust' ),
				),
			),
		),
		array(
			'name'          => 'more_label',
			'label'         => __( 'Подпись ссылки', 'trust' ),
			'type'          => 'text',
			'default_value' => __( 'Подробнее', 'trust' ),
			'maxlength'     => 30,
		),
	)
);
