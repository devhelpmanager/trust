<?php
/**
 * Поля блока «Слово руководителя» (ТЗ, п. 8.3; макет o-nas/o-kompanii.html, секция ceo).
 * Кнопка «Написать руководителю» — по макету (в ТЗ её нет): открывает окно с формой, сообщения —
 * в «Заявки → Обращения к руководителю» (правка заказчика; Trust\Forms\Leader_Messages).
 * «Познакомиться с командой» из макета убрана по просьбе заказчика.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

// Поля окна — только при включённой кнопке.
$trust_if_write = array(
	array(
		array(
			'field'    => 'field_trust_block_leader_word_write',
			'operator' => '==',
			'value'    => '1',
		),
	),
);

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
		'instructions'  => __( 'Сотрудник из раздела «Сотрудники»: фото, имя и должность (они же — в окне «Написать руководителю»).', 'trust' ),
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
		'name'          => 'write',
		'label'         => __( 'Кнопка «Написать руководителю»', 'trust' ),
		'type'          => 'true_false',
		'ui'            => 1,
		'default_value' => 1,
		'instructions'  => __( 'Открывает окно с формой: имя, телефон или email, сообщение. Сообщения — в «Заявки → Обращения к руководителю», уведомления — по «Настройкам сайта → Формы».', 'trust' ),
		'wrapper'       => array( 'width' => 50 ),
	),
	array(
		'name'        => 'write_label',
		'label'       => __( 'Текст кнопки', 'trust' ),
		'type'        => 'text',
		'maxlength'   => 40,
		'placeholder' => __( 'Написать руководителю', 'trust' ),
		'wrapper'     => array( 'width' => 50 ),
	),
	array(
		'name'              => 'modal_title',
		'label'             => __( 'Заголовок окна', 'trust' ),
		'type'              => 'text',
		'maxlength'         => 60,
		'placeholder'       => __( 'Написать руководителю', 'trust' ),
		'conditional_logic' => $trust_if_write,
		'wrapper'           => array( 'width' => 50 ),
	),
	array(
		'name'              => 'modal_submit',
		'label'             => __( 'Кнопка отправки', 'trust' ),
		'type'              => 'text',
		'maxlength'         => 40,
		'placeholder'       => __( 'Отправить сообщение', 'trust' ),
		'conditional_logic' => $trust_if_write,
		'wrapper'           => array( 'width' => 50 ),
	),
	array(
		'name'              => 'modal_text',
		'label'             => __( 'Текст окна', 'trust' ),
		'type'              => 'textarea',
		'rows'              => 2,
		'maxlength'         => 300,
		'placeholder'       => __( 'Вопрос, предложение или отзыв о работе команды — сообщение получит руководитель агентства.', 'trust' ),
		'conditional_logic' => $trust_if_write,
	),
	array(
		'name'              => 'success_text',
		'label'             => __( 'Текст после отправки', 'trust' ),
		'type'              => 'text',
		'maxlength'         => 200,
		'placeholder'       => __( 'Спасибо! Руководитель прочитает его и при необходимости свяжется с вами.', 'trust' ),
		'conditional_logic' => $trust_if_write,
	),
);
