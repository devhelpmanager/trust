<?php
/**
 * Поля блока «Обсудим ваш проект» (ТЗ, п. 8.3; макет holst-o-nas/CtaBlock, o-nas/blok-cta.html).
 * Пустые заголовок, текст и кнопка — из «Настроек сайта → Блоки по умолчанию» (Trust\Block_Texts).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_defaults = Trust\Block_Texts::defaults();
$trust_empty    = __( 'Пусто — текст из «Настроек сайта → Блоки по умолчанию».', 'trust' );

return array(
	array(
		'name'         => 'anchor',
		'label'        => __( 'Якорь', 'trust' ),
		'type'         => 'text',
		'placeholder'  => 'contact',
		'instructions' => __( 'Необязательно. Латиница, цифры и дефис, без #.', 'trust' ),
		'wrapper'      => array( 'width' => 50 ),
	),
	array(
		'name'          => 'background',
		'label'         => __( 'Фон секции', 'trust' ),
		'type'          => 'button_group',
		'choices'       => array(
			'lavender' => __( 'Лавандовый', 'trust' ),
			'white'    => __( 'Белый', 'trust' ),
		),
		'default_value' => 'lavender',
		'wrapper'       => array( 'width' => 50 ),
	),
	array(
		'name'         => 'title',
		'label'        => __( 'Заголовок (H2)', 'trust' ),
		'type'         => 'text',
		'maxlength'    => 80,
		'placeholder'  => $trust_defaults['cta_title'],
		'instructions' => $trust_empty,
	),
	array(
		'name'         => 'text',
		'label'        => __( 'Текст', 'trust' ),
		'type'         => 'textarea',
		'rows'         => 2,
		'new_lines'    => '',
		'maxlength'    => 300,
		'placeholder'  => $trust_defaults['cta_text'],
		'instructions' => $trust_empty,
	),
	array(
		'name'         => 'button_label',
		'label'        => __( 'Текст кнопки', 'trust' ),
		'type'         => 'text',
		'maxlength'    => 40,
		'placeholder'  => $trust_defaults['cta_button'],
		'instructions' => $trust_empty,
		'wrapper'      => array( 'width' => 50 ),
	),
	array(
		'name'         => 'button_url',
		'label'        => __( 'Адрес кнопки', 'trust' ),
		'type'         => 'text',
		'maxlength'    => 300,
		'placeholder'  => '/kontakty/',
		'instructions' => __( 'Пусто — страница из «Блоков по умолчанию», иначе страница контактов. #callback-modal — окно «Заказать обратный звонок».', 'trust' ),
		'wrapper'      => array( 'width' => 50 ),
	),
	array(
		'name'          => 'contacts',
		'label'         => __( 'Телефон и Telegram', 'trust' ),
		'type'          => 'true_false',
		'ui'            => 1,
		'default_value' => 1,
		'message'       => __( 'Показывать под текстом основной телефон и «Написать в Telegram» из «Настроек сайта → Контакты»', 'trust' ),
	),
);
