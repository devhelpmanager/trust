<?php
/**
 * Поля блока «Слово руководителя» (ТЗ, п. 8.3; макет o-nas/o-kompanii.html, секция ceo).
 * Кнопки «Написать руководителю» и «Познакомиться с командой» — по макету (в ТЗ их нет).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'name'         => 'anchor',
		'label'        => __( 'Якорь', 'trust' ),
		'type'         => 'text',
		'placeholder'  => 'leader',
		'instructions' => __( 'Необязательно. Латиница, цифры и дефис, без #.', 'trust' ),
		'wrapper'      => array( 'width' => 50 ),
	),
	array(
		'name'          => 'title',
		'label'         => __( 'Надпись над цитатой (H2)', 'trust' ),
		'type'          => 'text',
		'default_value' => __( 'Слово руководителя', 'trust' ),
		'maxlength'     => 60,
		'wrapper'       => array( 'width' => 50 ),
	),
	array(
		'name'          => 'member',
		'label'         => __( 'Руководитель', 'trust' ),
		'type'          => 'post_object',
		'post_type'     => array( 'team' ),
		'return_format' => 'id',
		'allow_null'    => 1,
		'ui'            => 1,
		'instructions'  => __( 'Сотрудник из раздела «Сотрудники»: фото, имя, должность и email для кнопки «Написать руководителю».', 'trust' ),
	),
	array(
		'name'         => 'quote',
		'label'        => __( 'Цитата', 'trust' ),
		'type'         => 'wysiwyg',
		'tabs'         => 'visual',
		'toolbar'      => 'basic',
		'media_upload' => 0,
		'required'     => 1,
		'instructions' => __( 'Зачем существует компания и что вы обещаете клиентам — 2–3 предложения.', 'trust' ),
	),
	array(
		'name'          => 'photo',
		'label'         => __( 'Своё фото', 'trust' ),
		'type'          => 'image',
		'return_format' => 'id',
		'preview_size'  => 'thumbnail',
		'library'       => 'all',
		'mime_types'    => 'jpg,jpeg,png,webp',
		'instructions'  => __( 'Необязательно. Пусто — фото из карточки сотрудника. Вертикальное 4:5, от 760×950 px.', 'trust' ),
		'wrapper'       => array( 'width' => 50 ),
	),
	array(
		'name'         => 'role',
		'label'        => __( 'Своя подпись под именем', 'trust' ),
		'type'         => 'text',
		'maxlength'    => 80,
		'instructions' => __( 'Пусто — должность из карточки сотрудника.', 'trust' ),
		'wrapper'      => array( 'width' => 50 ),
	),
	array(
		'name'          => 'buttons',
		'label'         => __( 'Кнопки', 'trust' ),
		'type'          => 'checkbox',
		'choices'       => array(
			'write' => __( '«Написать руководителю» (email из карточки сотрудника)', 'trust' ),
			'team'  => __( '«Познакомиться с командой» (страница «Команда»)', 'trust' ),
		),
		'default_value' => array( 'write', 'team' ),
		'layout'        => 'vertical',
	),
	array(
		'name'        => 'write_label',
		'label'       => __( 'Текст кнопки «Написать»', 'trust' ),
		'type'        => 'text',
		'maxlength'   => 40,
		'placeholder' => __( 'Написать руководителю', 'trust' ),
		'wrapper'     => array( 'width' => 50 ),
	),
	array(
		'name'        => 'team_label',
		'label'       => __( 'Текст кнопки «Команда»', 'trust' ),
		'type'        => 'text',
		'maxlength'   => 40,
		'placeholder' => __( 'Познакомиться с командой', 'trust' ),
		'wrapper'     => array( 'width' => 50 ),
	),
);
