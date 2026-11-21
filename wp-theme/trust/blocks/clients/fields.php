<?php
/**
 * Поля блока «Нам доверяют» (ТЗ, п. 8.3; макет o-nas/o-kompanii.html, «Клиенты»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'clients',
			'eyebrow' => __( 'Клиенты', 'trust' ),
			'title'   => __( 'Нам доверяют', 'trust' ),
			'lead'    => '',
		)
	),
	array(
		array(
			'name'         => 'logos',
			'label'        => __( 'Клиенты', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'min'          => 1,
			'max'          => 24,
			'button_label' => __( 'Добавить клиента', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'          => 'logo',
					'label'         => __( 'Логотип', 'trust' ),
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'thumbnail',
					'library'       => 'all',
					'mime_types'    => 'jpg,jpeg,png,webp,svg',
					'wrapper'       => array( 'width' => 25 ),
				),
				array(
					'name'         => 'name',
					'label'        => __( 'Название', 'trust' ),
					'type'         => 'text',
					'required'     => 1,
					'maxlength'    => 60,
					'instructions' => __( 'Alt логотипа; без логотипа — текстом в плитке.', 'trust' ),
					'wrapper'      => array( 'width' => 40 ),
				),
				array(
					'name'         => 'url',
					'label'        => __( 'Сайт клиента', 'trust' ),
					'type'         => 'url',
					'instructions' => __( 'Необязательно. Откроется в новой вкладке.', 'trust' ),
					'wrapper'      => array( 'width' => 35 ),
				),
			),
		),
		array(
			'name'          => 'button',
			'label'         => __( 'Кнопка «Смотреть кейсы»', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'wrapper'       => array( 'width' => 34 ),
		),
		array(
			'name'        => 'button_label',
			'label'       => __( 'Текст кнопки', 'trust' ),
			'type'        => 'text',
			'maxlength'   => 40,
			'placeholder' => __( 'Смотреть кейсы', 'trust' ),
			'wrapper'     => array( 'width' => 33 ),
		),
		array(
			'name'         => 'button_url',
			'label'        => __( 'Адрес кнопки', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 300,
			'placeholder'  => '/portfolio/',
			'instructions' => __( 'Пусто — страница типа «Портфолио».', 'trust' ),
			'wrapper'      => array( 'width' => 33 ),
		),
	)
);
