<?php
/**
 * Настройки сайта → Реквизиты (ТЗ, раздел 5): строки «название — значение» и файл «Карточка
 * компании, PDF». Выводит блок «Реквизиты» (страница контактов, документы).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'label'  => __( 'Реквизиты', 'trust' ),
	'fields' => array(
		array(
			'name'         => 'requisites',
			'label'        => __( 'Реквизиты', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'button_label' => __( 'Добавить реквизит', 'trust' ),
			'instructions' => __( 'В нужном порядке: полное наименование, руководитель, ИНН / КПП, ОГРН, адреса, расчётный счёт, банк, БИК, корр. счёт. Выводятся блоком «Реквизиты»; кнопка «Скопировать» копирует их строками «Название: значение».', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'        => 'label',
					'label'       => __( 'Название', 'trust' ),
					'type'        => 'text',
					'required'    => 1,
					'maxlength'   => 60,
					'placeholder' => __( 'ИНН / КПП', 'trust' ),
					'wrapper'     => array( 'width' => 35 ),
				),
				array(
					'name'      => 'value',
					'label'     => __( 'Значение', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 300,
				),
			),
		),
		array(
			'name'          => 'requisites_card',
			'label'         => __( 'Карточка компании, PDF', 'trust' ),
			'type'          => 'file',
			'return_format' => 'id',
			'mime_types'    => 'pdf',
			'instructions'  => __( 'Необязательно. Кнопка «Карточка компании, PDF» в блоке «Реквизиты»; без файла кнопки нет.', 'trust' ),
		),
	),
);
