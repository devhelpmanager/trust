<?php
/**
 * Поля блока «Слово клиенту» (ТЗ, п. 8.10; макет portfolio-blog-404/keys.html, «05 · Отзыв»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;

$trust_source_is = static fn( string $operator, string $value ): array => array(
	array(
		array(
			'field'    => 'field_trust_block_case_quote_source',
			'operator' => $operator,
			'value'    => $value,
		),
	),
);

return array_merge(
	Trust\Case_Blocks::fields( __( 'Отзыв', 'trust' ), __( 'Слово клиенту', 'trust' ) ),
	array(
		array(
			'name'          => 'source',
			'label'         => __( 'Откуда отзыв', 'trust' ),
			'type'          => 'select',
			'choices'       => array(
				'case'   => __( 'Отзыв из данных кейса («Отзыв клиента»)', 'trust' ),
				'review' => __( 'Выбрать отзыв', 'trust' ),
				'manual' => __( 'Вписать вручную', 'trust' ),
			),
			'default_value' => 'case',
			'instructions'  => __( 'Из отзыва берутся текст, имя, должность и компания, фото и благодарственное письмо (скан) или ссылка на оригинал.', 'trust' ),
		),
		array(
			'name'              => 'review',
			'label'             => __( 'Отзыв', 'trust' ),
			'type'              => 'post_object',
			'post_type'         => array( 'review' ),
			'post_status'       => array( 'publish' ),
			'return_format'     => 'id',
			'ui'                => 1,
			'conditional_logic' => $trust_source_is( '==', 'review' ),
		),
		array(
			'name'              => 'text',
			'label'             => __( 'Цитата', 'trust' ),
			'type'              => 'textarea',
			'rows'              => 4,
			'new_lines'         => '',
			'maxlength'         => 800,
			'instructions'      => __( 'Публикуйте только с согласия клиента.', 'trust' ),
			'conditional_logic' => $trust_source_is( '==', 'manual' ),
		),
		array(
			'name'              => 'name',
			'label'             => __( 'Имя', 'trust' ),
			'type'              => 'text',
			'maxlength'         => 60,
			'wrapper'           => array( 'width' => 50 ),
			'conditional_logic' => $trust_source_is( '==', 'manual' ),
		),
		array(
			'name'              => 'role',
			'label'             => __( 'Должность и компания', 'trust' ),
			'type'              => 'text',
			'maxlength'         => 100,
			'wrapper'           => array( 'width' => 50 ),
			'conditional_logic' => $trust_source_is( '==', 'manual' ),
		),
		array(
			'name'              => 'photo',
			'label'             => __( 'Фото', 'trust' ),
			'type'              => 'image',
			'return_format'     => 'id',
			'preview_size'      => 'thumbnail',
			'mime_types'        => 'jpg, jpeg, png, webp',
			'instructions'      => __( 'Необязательно. Квадратное, от 120 px.', 'trust' ),
			'wrapper'           => array( 'width' => 50 ),
			'conditional_logic' => $trust_source_is( '==', 'manual' ),
		),
		array(
			'name'              => 'letter',
			'label'             => __( 'Благодарственное письмо', 'trust' ),
			'type'              => 'file',
			'return_format'     => 'id',
			'mime_types'        => 'jpg, jpeg, png, webp, pdf',
			'instructions'      => __( 'Необязательно. Скан (картинка — откроется на весь экран) или PDF (в новой вкладке).', 'trust' ),
			'wrapper'           => array( 'width' => 50 ),
			'conditional_logic' => $trust_source_is( '==', 'manual' ),
		),
		Blocks::text_field( 'letter_label', __( 'Ссылка на письмо', 'trust' ), __( 'Благодарственное письмо', 'trust' ), 40 ),
		Blocks::text_field( 'source_label', __( 'Ссылка на оригинал отзыва (если нет письма)', 'trust' ), __( 'Оригинал отзыва', 'trust' ), 40 ),
	)
);
