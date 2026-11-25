<?php
/**
 * Поля блока «Коротко о главном» (ТЗ, п. 8.9; макеты документов, «Коротко о главном»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;
use Trust\Icons;

return array(
	Blocks::text_field( 'title', __( 'Заголовок (H2)', 'trust' ), __( 'Коротко о главном', 'trust' ), 60, __( 'В содержание документа не попадает.', 'trust' ) ),
	array(
		'name'         => 'items',
		'label'        => __( 'Пункты', 'trust' ),
		'type'         => 'repeater',
		'layout'       => 'block',
		'min'          => 1,
		'max'          => 6,
		'button_label' => __( 'Добавить пункт', 'trust' ),
		'instructions' => __( 'До 6 пунктов по одному предложению: что важно знать, не читая весь документ.', 'trust' ),
		'sub_fields'   => array(
			Icons::field(
				array(
					'name'          => 'icon',
					'label'         => __( 'Значок', 'trust' ),
					'choices'       => array(
						'check'  => __( 'Галочка', 'trust' ),
						'info'   => __( 'Информация', 'trust' ),
						'shield' => __( 'Щит', 'trust' ),
						'clock'  => __( 'Часы — сроки', 'trust' ),
						'mail'   => __( 'Письмо', 'trust' ),
					),
					'default_value' => 'check',
					'wrapper'       => array( 'width' => 25 ),
				)
			),
			array(
				'name'         => 'text',
				'label'        => __( 'Текст', 'trust' ),
				'type'         => 'wysiwyg',
				'tabs'         => 'visual',
				'toolbar'      => 'basic',
				'media_upload' => 0,
				'required'     => 1,
				'wrapper'      => array( 'width' => 75 ),
				'instructions' => __( 'Можно вставить ссылку — например, на настройки cookie (#cookie-settings) или email.', 'trust' ),
			),
		),
	),
	array(
		'name'          => 'note',
		'label'         => __( 'Примечание', 'trust' ),
		'type'          => 'textarea',
		'rows'          => 2,
		'new_lines'     => '',
		'maxlength'     => 200,
		'default_value' => __( 'Краткое изложение для удобства. Юридическую силу имеет полный текст ниже.', 'trust' ),
	),
);
