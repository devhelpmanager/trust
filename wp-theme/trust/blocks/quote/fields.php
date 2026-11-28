<?php
/**
 * Поля блока «Цитата с автором» (макет seo-blog/statya.html — цитата в тексте статьи).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_source_is = static fn( string $value ): array => array(
	array(
		array(
			'field'    => 'field_trust_block_quote_source',
			'operator' => '==',
			'value'    => $value,
		),
	),
);

return array(
	array(
		'name'         => 'text',
		'label'        => __( 'Текст цитаты', 'trust' ),
		'type'         => 'textarea',
		'rows'         => 4,
		'new_lines'    => '',
		'maxlength'    => 600,
		'required'     => 1,
		'instructions' => __( 'Без кавычек — знак цитаты выводится сам. Новый абзац — через пустую строку.', 'trust' ),
	),
	array(
		'name'          => 'source',
		'label'         => __( 'Автор цитаты', 'trust' ),
		'type'          => 'radio',
		'layout'        => 'vertical',
		'choices'       => array(
			'member' => __( 'Сотрудник', 'trust' ),
			'manual' => __( 'Вписать вручную', 'trust' ),
			'none'   => __( 'Без автора', 'trust' ),
		),
		'default_value' => 'member',
	),
	array(
		'name'              => 'member',
		'label'             => __( 'Сотрудник', 'trust' ),
		'type'              => 'post_object',
		'post_type'         => array( 'team' ),
		'post_status'       => array( 'publish' ),
		'return_format'     => 'id',
		'allow_null'        => 1,
		'ui'                => 1,
		'instructions'      => __( 'Имя, должность и фото — из карточки сотрудника. Пусто — автор статьи (сотрудник, связанный с автором записи).', 'trust' ),
		'conditional_logic' => $trust_source_is( 'member' ),
	),
	array(
		'name'              => 'name',
		'label'             => __( 'Имя', 'trust' ),
		'type'              => 'text',
		'maxlength'         => 60,
		'wrapper'           => array( 'width' => 50 ),
		'conditional_logic' => $trust_source_is( 'manual' ),
	),
	array(
		'name'              => 'role',
		'label'             => __( 'Должность', 'trust' ),
		'type'              => 'text',
		'maxlength'         => 100,
		'wrapper'           => array( 'width' => 50 ),
		'instructions'      => __( 'Например, «Старший SEO-специалист» или «Директор, ООО «Компания»».', 'trust' ),
		'conditional_logic' => $trust_source_is( 'manual' ),
	),
	array(
		'name'              => 'photo',
		'label'             => __( 'Фото', 'trust' ),
		'type'              => 'image',
		'return_format'     => 'id',
		'preview_size'      => 'thumbnail',
		'mime_types'        => 'jpg, jpeg, png, webp',
		'instructions'      => __( 'Необязательно, квадратное от 80 px. Без фото — инициалы в круге.', 'trust' ),
		'conditional_logic' => $trust_source_is( 'manual' ),
	),
);
