<?php
/**
 * Поля блока «Другие документы» (ТЗ, п. 8.9; макеты документов, внизу страницы).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;

return array(
	Blocks::text_field( 'title', __( 'Заголовок (H2)', 'trust' ), __( 'Другие документы', 'trust' ), 60, __( 'В содержание документа не попадает.', 'trust' ) ),
	array(
		'name'          => 'mode',
		'label'         => __( 'Какие документы', 'trust' ),
		'type'          => 'button_group',
		'choices'       => array(
			'auto'   => __( 'Все, кроме этого', 'trust' ),
			'manual' => __( 'Выбрать вручную', 'trust' ),
		),
		'default_value' => 'auto',
		'instructions'  => __( 'Все — опубликованные страницы типа «Документ» по полю «Порядок» (Атрибуты страницы). Значок, короткое название и подпись карточки — в группе «Документ» у каждой страницы.', 'trust' ),
	),
	array(
		'name'              => 'pages',
		'label'             => __( 'Документы', 'trust' ),
		'type'              => 'relationship',
		'post_type'         => array( 'page' ),
		'filters'           => array( 'search' ),
		'return_format'     => 'id',
		'max'               => 8,
		'conditional_logic' => array(
			array(
				array(
					'field'    => 'field_trust_block_documents-list_mode',
					'operator' => '==',
					'value'    => 'manual',
				),
			),
		),
	),
);
