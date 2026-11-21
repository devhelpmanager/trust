<?php
/**
 * Поля блока «Где мы работаем» (ТЗ, п. 8.3; макет o-nas/o-kompanii.html, «Офис»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'office',
			'eyebrow' => __( 'Офис', 'trust' ),
			'title'   => __( 'Где мы работаем', 'trust' ),
			'lead'    => false,
		)
	),
	array(
		array(
			'name'         => 'text',
			'label'        => __( 'Текст', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 160,
			'placeholder'  => __( 'Приезжайте познакомиться — адрес и схема проезда', 'trust' ),
			'instructions' => __( 'Перед ссылкой на страницу контактов. Пусто — текст из макета.', 'trust' ),
		),
		array(
			'name'        => 'link_label',
			'label'       => __( 'Текст ссылки', 'trust' ),
			'type'        => 'text',
			'maxlength'   => 60,
			'placeholder' => __( 'на странице контактов', 'trust' ),
			'wrapper'     => array( 'width' => 50 ),
		),
		array(
			'name'         => 'link_url',
			'label'        => __( 'Адрес ссылки', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 300,
			'placeholder'  => '/kontakty/',
			'instructions' => __( 'Пусто — страница типа «Контакты».', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'    => 'address',
			'label'   => __( 'Адрес', 'trust' ),
			'type'    => 'true_false',
			'ui'      => 1,
			'message' => __( 'Показать под текстом адрес из «Настроек сайта → Контакты»', 'trust' ),
		),
		array(
			'name'         => 'photos',
			'label'        => __( 'Фотографии', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'min'          => 1,
			'max'          => 9,
			'button_label' => __( 'Добавить фото', 'trust' ),
			'instructions' => __( 'Первое — крупно. 5 фото заполняют сетку ровно. Горизонтальные, от 1200 px по ширине.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'          => 'image',
					'label'         => __( 'Фото', 'trust' ),
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'thumbnail',
					'library'       => 'all',
					'mime_types'    => 'jpg,jpeg,png,webp',
					'wrapper'       => array( 'width' => 25 ),
				),
				array(
					'name'         => 'alt',
					'label'        => __( 'Alt', 'trust' ),
					'type'         => 'text',
					'maxlength'    => 150,
					'instructions' => __( 'Пусто — из медиатеки, иначе подпись.', 'trust' ),
					'wrapper'      => array( 'width' => 40 ),
				),
				array(
					'name'      => 'caption',
					'label'     => __( 'Подпись на фото', 'trust' ),
					'type'      => 'text',
					'maxlength' => 60,
					'wrapper'   => array( 'width' => 35 ),
				),
			),
		),
	)
);
