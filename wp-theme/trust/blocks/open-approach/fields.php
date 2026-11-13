<?php
/**
 * Поля блока «Почему мы открыто пишем про всё» (макет holst-blokov/OpenApproach,
 * seo-bloki/05-otkryto-pishem.html).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_author_is = static fn( string $value ): array => array(
	array(
		array(
			'field'    => 'field_trust_block_open_approach_author_source',
			'operator' => '==',
			'value'    => $value,
		),
	),
);

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'open',
			'eyebrow' => __( 'Открытость', 'trust' ),
			'title'   => __( 'Почему мы открыто пишем про всё', 'trust' ),
			'lead'    => false,
		)
	),
	array(
		array(
			'name'         => 'quote',
			'label'        => __( 'Цитата', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 2,
			'new_lines'    => '',
			'required'     => 1,
			'maxlength'    => 200,
			'instructions' => __( 'Главная мысль крупным шрифтом, 1 предложение.', 'trust' ),
		),
		array(
			'name'         => 'text',
			'label'        => __( 'Продолжение', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 4,
			'new_lines'    => '',
			'maxlength'    => 600,
			'instructions' => __( 'Необязательно. Абзац под цитатой обычным шрифтом.', 'trust' ),
		),
		array(
			'name'          => 'author_source',
			'label'         => __( 'Автор', 'trust' ),
			'type'          => 'button_group',
			'choices'       => array(
				'team'   => __( 'Сотрудник', 'trust' ),
				'manual' => __( 'Вручную', 'trust' ),
				'none'   => __( 'Без подписи', 'trust' ),
			),
			'default_value' => 'team',
		),
		array(
			'name'              => 'author',
			'label'             => __( 'Сотрудник', 'trust' ),
			'type'              => 'post_object',
			'post_type'         => array( 'team' ),
			'return_format'     => 'id',
			'allow_null'        => 1,
			'ui'                => 1,
			'instructions'      => __( 'Фото, имя и должность — из карточки сотрудника.', 'trust' ),
			'conditional_logic' => $trust_author_is( 'team' ),
		),
		array(
			'name'              => 'author_name',
			'label'             => __( 'Имя', 'trust' ),
			'type'              => 'text',
			'maxlength'         => 60,
			'conditional_logic' => $trust_author_is( 'manual' ),
		),
		array(
			'name'              => 'author_photo',
			'label'             => __( 'Фото', 'trust' ),
			'type'              => 'image',
			'return_format'     => 'id',
			'preview_size'      => 'thumbnail',
			'instructions'      => __( 'Необязательно. Квадратное, от 96×96 px. Без фото — значок.', 'trust' ),
			'conditional_logic' => $trust_author_is( 'manual' ),
		),
		array(
			'name'              => 'author_role',
			'label'             => __( 'Подпись под именем', 'trust' ),
			'type'              => 'text',
			'maxlength'         => 80,
			'instructions'      => __( 'Например, «руководитель агентства». У сотрудника пусто — его должность.', 'trust' ),
			'conditional_logic' => array(
				array(
					array(
						'field'    => 'field_trust_block_open_approach_author_source',
						'operator' => '!=',
						'value'    => 'none',
					),
				),
			),
		),
	)
);
