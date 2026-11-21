<?php
/**
 * Поля блока «Оповещение» (ТЗ, п. 8.3; макеты seo-vopros/vopros-seo.html, otvet.html).
 * Пустые заголовок, текст и ссылка — из «Настроек сайта → Блоки по умолчанию» (Trust\Block_Texts).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_defaults = Trust\Block_Texts::defaults();
$trust_empty    = __( 'Пусто — текст из «Настроек сайта → Блоки по умолчанию».', 'trust' );

return array(
	Trust\Icons::field(
		array(
			'name'          => 'icon',
			'label'         => __( 'Значок', 'trust' ),
			'choices'       => array(
				'info'   => __( 'Информация', 'trust' ),
				'shield' => __( 'Щит', 'trust' ),
				'check'  => __( 'Галочка', 'trust' ),
				'chat'   => __( 'Сообщение', 'trust' ),
				'report' => __( 'Отчёт', 'trust' ),
				'eye'    => __( 'Глаз', 'trust' ),
			),
			'default_value' => 'info',
			'wrapper'       => array( 'width' => 50 ),
		)
	),
	array(
		'name'         => 'title',
		'label'        => __( 'Заголовок', 'trust' ),
		'type'         => 'text',
		'maxlength'    => 120,
		'placeholder'  => $trust_defaults['notice_title'],
		'instructions' => __( 'На странице — заголовок H2, в статье и ответе — выделенная строка.', 'trust' ) . ' ' . $trust_empty,
	),
	array(
		'name'         => 'text',
		'label'        => __( 'Текст', 'trust' ),
		'type'         => 'textarea',
		'rows'         => 3,
		'new_lines'    => '',
		'maxlength'    => 500,
		'placeholder'  => str_replace( '{brand}', '[Бренд]', $trust_defaults['notice_text'] ),
		'instructions' => __( '{brand} — название бренда из настроек.', 'trust' ) . ' ' . $trust_empty,
	),
	array(
		'name'          => 'show_link',
		'label'         => __( 'Ссылка под текстом', 'trust' ),
		'type'          => 'true_false',
		'ui'            => 1,
		'default_value' => 1,
		'message'       => __( 'Показывать строку со ссылкой («Нужна полная проверка сайта? Закажите технический аудит»)', 'trust' ),
	),
	array(
		'name'              => 'link_prefix',
		'label'             => __( 'Текст перед ссылкой', 'trust' ),
		'type'              => 'text',
		'maxlength'         => 100,
		'placeholder'       => $trust_defaults['notice_link_prefix'],
		'wrapper'           => array( 'width' => 50 ),
		'conditional_logic' => array(
			array(
				array(
					'field'    => 'field_trust_block_notice_show_link',
					'operator' => '==',
					'value'    => '1',
				),
			),
		),
	),
	array(
		'name'              => 'link',
		'label'             => __( 'Ссылка', 'trust' ),
		'type'              => 'link',
		'return_format'     => 'array',
		'wrapper'           => array( 'width' => 50 ),
		'instructions'      => __( 'Пусто — ссылка из «Блоков по умолчанию». Нет и там — строка не выводится.', 'trust' ),
		'conditional_logic' => array(
			array(
				array(
					'field'    => 'field_trust_block_notice_show_link',
					'operator' => '==',
					'value'    => '1',
				),
			),
		),
	),
);
