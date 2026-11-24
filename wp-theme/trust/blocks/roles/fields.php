<?php
/**
 * Поля блока «Кто работает над вашим проектом» (ТЗ, п. 8.6; макет holst-o-nas/Team,
 * o-nas/komanda.html). Главная карточка — по макету (в ТЗ только роли).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_icons = array(
	'search'     => __( 'Поиск', 'trust' ),
	'pen'        => __( 'Карандаш (тексты)', 'trust' ),
	'code-slash' => __( 'Код (разработка)', 'trust' ),
	'layout'     => __( 'Макет (дизайн)', 'trust' ),
	'chart'      => __( 'График (аналитика)', 'trust' ),
	'speaker'    => __( 'Динамик (реклама)', 'trust' ),
	'chat-dots'  => __( 'Сообщение', 'trust' ),
	'headset'    => __( 'Гарнитура (поддержка)', 'trust' ),
	'user'       => __( 'Человек', 'trust' ),
	'shield'     => __( 'Щит', 'trust' ),
	'report'     => __( 'Отчёт', 'trust' ),
	'sparkles'   => __( 'Звёзды (GEO, AI)', 'trust' ),
);

// Поля главной карточки — только при включённой карточке.
$trust_if_main = array(
	array(
		array(
			'field'    => 'field_trust_block_roles_main',
			'operator' => '==',
			'value'    => '1',
		),
	),
);

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'roles',
			'eyebrow' => __( 'Как устроена работа', 'trust' ),
			'title'   => __( 'Кто работает над вашим проектом', 'trust' ),
			'lead'    => __( 'Состав команды подбираем под задачу и называем до начала работ.', 'trust' ),
		)
	),
	array(
		array(
			'name'          => 'main',
			'label'         => __( 'Главная карточка', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'instructions'  => __( 'Тёмная карточка слева: кто на связи с клиентом.', 'trust' ),
		),
		Trust\Icons::field(
			array(
				'name'              => 'main_icon',
				'label'             => __( 'Значок', 'trust' ),
				'choices'           => $trust_icons,
				'default_value'     => 'chat-dots',
				'conditional_logic' => $trust_if_main,
				'wrapper'           => array( 'width' => 30 ),
			)
		),
		array(
			'name'              => 'main_label',
			'label'             => __( 'Плашка', 'trust' ),
			'type'              => 'text',
			'maxlength'         => 30,
			'placeholder'       => __( 'Ваш контакт', 'trust' ),
			'conditional_logic' => $trust_if_main,
			'wrapper'           => array( 'width' => 35 ),
		),
		array(
			'name'              => 'main_title',
			'label'             => __( 'Название (H3)', 'trust' ),
			'type'              => 'text',
			'maxlength'         => 60,
			'placeholder'       => __( 'Аккаунт-менеджер', 'trust' ),
			'conditional_logic' => $trust_if_main,
			'wrapper'           => array( 'width' => 35 ),
		),
		array(
			'name'              => 'main_text',
			'label'             => __( 'Текст', 'trust' ),
			'type'              => 'textarea',
			'rows'              => 3,
			'new_lines'         => '',
			'maxlength'         => 400,
			'conditional_logic' => $trust_if_main,
		),
		array(
			'name'              => 'main_points',
			'label'             => __( 'Пункты с галочкой', 'trust' ),
			'type'              => 'textarea',
			'rows'              => 3,
			'new_lines'         => '',
			'instructions'      => __( 'Каждый пункт — с новой строки, до 5.', 'trust' ),
			'conditional_logic' => $trust_if_main,
		),
		array(
			'name'         => 'items',
			'label'        => __( 'Роли', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 1,
			'max'          => 9,
			'button_label' => __( 'Добавить роль', 'trust' ),
			'collapsed'    => 'field_trust_block_roles_items_title',
			'sub_fields'   => array(
				Trust\Icons::field(
					array(
						'name'          => 'icon',
						'label'         => __( 'Значок', 'trust' ),
						'choices'       => $trust_icons,
						'default_value' => 'search',
						'wrapper'       => array( 'width' => 30 ),
					)
				),
				array(
					'name'      => 'title',
					'label'     => __( 'Роль', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 60,
					'wrapper'   => array( 'width' => 70 ),
				),
				array(
					'name'      => 'text',
					'label'     => __( 'Чем занимается', 'trust' ),
					'type'      => 'textarea',
					'rows'      => 2,
					'new_lines' => '',
					'maxlength' => 200,
				),
			),
		),
	)
);
