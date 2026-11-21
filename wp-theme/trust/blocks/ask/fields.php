<?php
/**
 * Поля блока «Задать вопрос SEO-специалисту» (ТЗ, п. 8.3; макет holst-voprosov/AskBlock,
 * seo-vopros/blok-vopros.html). Задать вопрос можно только на странице вопросов (решение
 * заказчика) — блок ведёт туда. Пустые тексты — из «Настроек сайта → Блоки по умолчанию»
 * (Trust\Block_Texts).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_defaults = Trust\Block_Texts::defaults();
$trust_empty    = __( 'Пусто — текст из «Настроек сайта → Блоки по умолчанию».', 'trust' );

return array(
	array(
		'name'          => 'background',
		'label'         => __( 'Фон секции', 'trust' ),
		'type'          => 'button_group',
		'choices'       => array(
			'lavender' => __( 'Лавандовый', 'trust' ),
			'white'    => __( 'Белый', 'trust' ),
			'violet'   => __( 'Фиолетовый', 'trust' ),
		),
		'default_value' => 'lavender',
		'wrapper'       => array( 'width' => 50 ),
	),
	array(
		'name'         => 'anchor',
		'label'        => __( 'Якорь', 'trust' ),
		'type'         => 'text',
		'placeholder'  => 'ask',
		'instructions' => __( 'Необязательно. Латиница, цифры и дефис, без #.', 'trust' ),
		'wrapper'      => array( 'width' => 50 ),
	),
	array(
		'name'         => 'title',
		'label'        => __( 'Заголовок (H2)', 'trust' ),
		'type'         => 'text',
		'maxlength'    => 100,
		'placeholder'  => $trust_defaults['ask_title'],
		'instructions' => $trust_empty,
	),
	array(
		'name'         => 'text',
		'label'        => __( 'Текст', 'trust' ),
		'type'         => 'textarea',
		'rows'         => 2,
		'new_lines'    => '',
		'maxlength'    => 300,
		'placeholder'  => $trust_defaults['ask_text'],
		'instructions' => $trust_empty,
	),
	array(
		'name'         => 'points',
		'label'        => __( 'Пункты', 'trust' ),
		'type'         => 'textarea',
		'rows'         => 3,
		'new_lines'    => '',
		'maxlength'    => 200,
		'placeholder'  => $trust_defaults['ask_points'],
		'instructions' => __( 'Каждый с новой строки, до 4 пунктов (плашки с галочкой).', 'trust' ) . ' ' . $trust_empty,
	),
	array(
		'name'         => 'button_label',
		'label'        => __( 'Текст кнопки', 'trust' ),
		'type'         => 'text',
		'maxlength'    => 40,
		'placeholder'  => $trust_defaults['ask_button'],
		'instructions' => $trust_empty,
		'wrapper'      => array( 'width' => 50 ),
	),
	array(
		'name'         => 'button_url',
		'label'        => __( 'Адрес кнопки', 'trust' ),
		'type'         => 'text',
		'maxlength'    => 300,
		'placeholder'  => '/seo/voprosy/',
		'instructions' => __( 'Пусто — страница из «Блоков по умолчанию», иначе страница типа «Вопросы SEO».', 'trust' ),
		'wrapper'      => array( 'width' => 50 ),
	),
	array(
		'name'         => 'note',
		'label'        => __( 'Примечание у кнопки', 'trust' ),
		'type'         => 'text',
		'maxlength'    => 200,
		'placeholder'  => $trust_defaults['ask_note'],
		'instructions' => $trust_empty,
	),
);
