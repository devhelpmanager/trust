<?php
/**
 * Поля блока «Картинка» (макет holst-blokov/ImageBlock, seo-bloki/08-kartinka.html).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	array(
		array(
			'name'          => 'image',
			'label'         => __( 'Картинка', 'trust' ),
			'type'          => 'image',
			'required'      => 1,
			'return_format' => 'id',
			'preview_size'  => 'medium',
			'mime_types'    => 'jpg,jpeg,png,webp',
			'instructions'  => __( 'JPG, PNG или WebP; по ширине контента — от 2400 px, по ширине текста — от 1520 px (для чётких экранов).', 'trust' ),
		),
	),
	Trust\Blocks::image_alt_fields(),
	array(
		array(
			'name'         => 'caption',
			'label'        => __( 'Описание', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 2,
			'new_lines'    => '',
			'maxlength'    => 300,
			'instructions' => __( 'Необязательно. Подпись под картинкой после «Рис. N».', 'trust' ),
		),
		array(
			'name'         => 'number',
			'label'        => __( 'Номер рисунка', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 10,
			'placeholder'  => __( 'авто', 'trust' ),
			'instructions' => __( 'Пусто — по порядку картинок на странице (Рис. 1, Рис. 2…). «0» — без подписи «Рис.».', 'trust' ),
		),
		array(
			'name'          => 'width',
			'label'         => __( 'Ширина', 'trust' ),
			'type'          => 'button_group',
			'choices'       => array(
				'wide' => __( 'Во всю ширину (1200 px)', 'trust' ),
				'text' => __( 'По ширине текста (960 px)', 'trust' ),
			),
			'default_value' => 'wide',
		),
		array(
			'name'          => 'mobile_image',
			'label'         => __( 'Картинка для телефона', 'trust' ),
			'type'          => 'image',
			'return_format' => 'id',
			'preview_size'  => 'medium',
			'mime_types'    => 'jpg,jpeg,png,webp',
			'instructions'  => __( 'Необязательно. Показывается на экранах до 600 px — например, вертикальная схема вместо широкой. От 1000 px по ширине.', 'trust' ),
		),
		array(
			'name'         => 'anchor',
			'label'        => __( 'Якорь', 'trust' ),
			'type'         => 'text',
			'instructions' => __( 'Необязательно. Латиница, цифры и дефис, без #.', 'trust' ),
		),
	)
);
