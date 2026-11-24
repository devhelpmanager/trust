<?php
/**
 * Поля блока «Видеоотзывы» (ТЗ, п. 8.7; макет holst-o-nas/Reviews, o-nas/otzyvy.html).
 * Видео, обложка, длительность и «О чём видео» — поля отзыва вида «Видео».
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'video',
			'eyebrow' => __( 'Видео', 'trust' ),
			'title'   => __( 'Видеоотзывы', 'trust' ),
			'lead'    => '',
		)
	),
	array(
		array(
			'name'          => 'source',
			'label'         => __( 'Какие отзывы', 'trust' ),
			'type'          => 'radio',
			'choices'       => array(
				'latest' => __( 'Последние видеоотзывы', 'trust' ),
				'manual' => __( 'Выбрать вручную', 'trust' ),
			),
			'default_value' => 'latest',
			'layout'        => 'horizontal',
			'wrapper'       => array( 'width' => 50 ),
		),
		array(
			'name'              => 'count',
			'label'             => __( 'Сколько', 'trust' ),
			'type'              => 'number',
			'min'               => 1,
			'max'               => 12,
			'default_value'     => 3,
			'conditional_logic' => array(
				array(
					array(
						'field'    => 'field_trust_block_video_reviews_source',
						'operator' => '!=',
						'value'    => 'manual',
					),
				),
			),
			'wrapper'           => array( 'width' => 50 ),
		),
		array(
			'name'              => 'reviews',
			'label'             => __( 'Видеоотзывы', 'trust' ),
			'type'              => 'relationship',
			'post_type'         => array( 'review' ),
			'post_status'       => array( 'publish' ),
			'filters'           => array( 'search' ),
			'return_format'     => 'id',
			'max'               => 12,
			'instructions'      => __( 'Только отзывы вида «Видео». Порядок — как в списке справа.', 'trust' ),
			'conditional_logic' => array(
				array(
					array(
						'field'    => 'field_trust_block_video_reviews_source',
						'operator' => '==',
						'value'    => 'manual',
					),
				),
			),
		),
	)
);
