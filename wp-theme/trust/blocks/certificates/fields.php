<?php
/**
 * Поля блока «Сертификаты и рейтинги» (ТЗ, п. 8.3; макет o-nas/o-kompanii.html, «Подтверждения»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'certificates',
			'eyebrow' => __( 'Подтверждения', 'trust' ),
			'title'   => __( 'Сертификаты и рейтинги', 'trust' ),
			'lead'    => __( 'Каждый документ можно открыть и проверить.', 'trust' ),
		)
	),
	array(
		array(
			'name'         => 'items',
			'label'        => __( 'Документы', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 1,
			'max'          => 12,
			'button_label' => __( 'Добавить документ', 'trust' ),
			'collapsed'    => 'field_trust_block_certificates_items_title',
			'sub_fields'   => array(
				array(
					'name'          => 'image',
					'label'         => __( 'Картинка', 'trust' ),
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'thumbnail',
					'library'       => 'all',
					'mime_types'    => 'jpg,jpeg,png,webp',
					'instructions'  => __( 'Скан документа или логотип. Пусто — значок награды.', 'trust' ),
					'wrapper'       => array( 'width' => 30 ),
				),
				array(
					'name'         => 'alt',
					'label'        => __( 'Alt картинки', 'trust' ),
					'type'         => 'text',
					'maxlength'    => 150,
					'instructions' => __( 'Пусто — из медиатеки, иначе название документа.', 'trust' ),
					'wrapper'      => array( 'width' => 70 ),
				),
				array(
					'name'      => 'title',
					'label'     => __( 'Название', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 100,
					'wrapper'   => array( 'width' => 50 ),
				),
				array(
					'name'      => 'issuer',
					'label'     => __( 'Кто выдал', 'trust' ),
					'type'      => 'text',
					'maxlength' => 60,
					'wrapper'   => array( 'width' => 30 ),
				),
				array(
					'name'      => 'year',
					'label'     => __( 'Год', 'trust' ),
					'type'      => 'text',
					'maxlength' => 12,
					'wrapper'   => array( 'width' => 20 ),
				),
				array(
					'name'          => 'file',
					'label'         => __( 'Документ', 'trust' ),
					'type'          => 'file',
					'return_format' => 'id',
					'library'       => 'all',
					'mime_types'    => 'pdf,jpg,jpeg,png,webp',
					'instructions'  => __( 'Скан (JPG, PNG, WebP — откроется на весь экран) или PDF (в новой вкладке).', 'trust' ),
					'wrapper'       => array( 'width' => 35 ),
				),
				array(
					'name'         => 'url',
					'label'        => __( 'Или ссылка', 'trust' ),
					'type'         => 'url',
					'instructions' => __( 'Например, страница рейтинга. Откроется в новой вкладке.', 'trust' ),
					'wrapper'      => array( 'width' => 35 ),
				),
				array(
					'name'        => 'link_label',
					'label'       => __( 'Подпись ссылки', 'trust' ),
					'type'        => 'text',
					'maxlength'   => 30,
					'placeholder' => __( 'смотреть документ', 'trust' ),
					'wrapper'     => array( 'width' => 30 ),
				),
			),
		),
	)
);
