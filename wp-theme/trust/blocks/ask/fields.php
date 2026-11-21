<?php
/**
 * Поля блока «Задать вопрос SEO-специалисту» (ТЗ, п. 8.3; макет holst-voprosov/AskBlock,
 * seo-vopros/blok-vopros.html, blok-vopros-forma.html). Пустые тексты — из «Настроек сайта →
 * Блоки по умолчанию» (Trust\Block_Texts).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_defaults = Trust\Block_Texts::defaults();
$trust_empty    = __( 'Пусто — текст из «Настроек сайта → Блоки по умолчанию».', 'trust' );
$trust_view_is  = static fn( string $view ): array => array(
	array(
		array(
			'field'    => 'field_trust_block_ask_view',
			'operator' => '==',
			'value'    => $view,
		),
	),
);

return array(
	array(
		'name'          => 'view',
		'label'         => __( 'Вид', 'trust' ),
		'type'          => 'button_group',
		'choices'       => array(
			'link' => __( 'Кнопка', 'trust' ),
			'form' => __( 'Форма в блоке', 'trust' ),
		),
		'default_value' => 'link',
		'instructions'  => __( '«Кнопка» ведёт на страницу вопросов, «Форма в блоке» отправляет вопрос сразу: он появится в «Вопросах SEO» «На утверждении», придёт заявка.', 'trust' ),
		'wrapper'       => array( 'width' => 50 ),
	),
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
		'name'              => 'button',
		'label'             => __( 'Кнопка', 'trust' ),
		'type'              => 'link',
		'return_format'     => 'array',
		'instructions'      => __( 'Пусто — «Задать вопрос бесплатно» на страницу из «Блоков по умолчанию» (иначе — на страницу типа «Вопросы SEO»).', 'trust' ),
		'conditional_logic' => $trust_view_is( 'link' ),
	),
	array(
		'name'         => 'note',
		'label'        => __( 'Примечание', 'trust' ),
		'type'         => 'text',
		'maxlength'    => 200,
		'placeholder'  => $trust_defaults['ask_note'],
		'instructions' => __( 'Под кнопкой (вид «Кнопка») или под пунктами (вид «Форма в блоке»).', 'trust' ) . ' ' . $trust_empty,
	),
	array(
		'name'              => 'submit',
		'label'             => __( 'Кнопка отправки', 'trust' ),
		'type'              => 'text',
		'maxlength'         => 40,
		'placeholder'       => $trust_defaults['ask_submit'],
		'conditional_logic' => $trust_view_is( 'form' ),
	),
	array(
		'name'              => 'success_title',
		'label'             => __( 'Заголовок после отправки', 'trust' ),
		'type'              => 'text',
		'maxlength'         => 60,
		'placeholder'       => $trust_defaults['ask_success_title'],
		'wrapper'           => array( 'width' => 50 ),
		'conditional_logic' => $trust_view_is( 'form' ),
	),
	array(
		'name'              => 'success_text',
		'label'             => __( 'Текст после отправки', 'trust' ),
		'type'              => 'textarea',
		'rows'              => 2,
		'new_lines'         => '',
		'maxlength'         => 300,
		'placeholder'       => $trust_defaults['ask_success_text'],
		'instructions'      => __( '{email} — адрес, который указал посетитель.', 'trust' ),
		'wrapper'           => array( 'width' => 50 ),
		'conditional_logic' => $trust_view_is( 'form' ),
	),
);
