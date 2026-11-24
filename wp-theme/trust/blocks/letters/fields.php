<?php
/**
 * Поля блока «Благодарственные письма» (ТЗ, п. 8.7; макет holst-o-nas/Reviews, o-nas/otzyvy.html).
 * Скан и компания — поля отзыва вида «Благодарственное письмо».
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'letters',
			'eyebrow' => __( 'Документы', 'trust' ),
			'title'   => __( 'Благодарственные письма', 'trust' ),
			'lead'    => __( 'Сканы писем на бланках компаний — нажмите, чтобы открыть в полном размере.', 'trust' ),
		)
	),
	array(
		array(
			'name'          => 'source',
			'label'         => __( 'Какие письма', 'trust' ),
			'type'          => 'radio',
			'choices'       => array(
				'latest' => __( 'Последние письма', 'trust' ),
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
			'max'               => 24,
			'default_value'     => 12,
			'conditional_logic' => array(
				array(
					array(
						'field'    => 'field_trust_block_letters_source',
						'operator' => '!=',
						'value'    => 'manual',
					),
				),
			),
			'wrapper'           => array( 'width' => 50 ),
		),
		array(
			'name'              => 'reviews',
			'label'             => __( 'Письма', 'trust' ),
			'type'              => 'relationship',
			'post_type'         => array( 'review' ),
			'post_status'       => array( 'publish' ),
			'filters'           => array( 'search' ),
			'return_format'     => 'id',
			'max'               => 24,
			'instructions'      => __( 'Только отзывы вида «Благодарственное письмо». Порядок — как в списке справа.', 'trust' ),
			'conditional_logic' => array(
				array(
					array(
						'field'    => 'field_trust_block_letters_source',
						'operator' => '==',
						'value'    => 'manual',
					),
				),
			),
		),
	)
);
