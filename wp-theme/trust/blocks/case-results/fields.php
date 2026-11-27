<?php
/**
 * Поля блока «Результаты» (ТЗ, п. 8.10; макет portfolio-blog-404/keys.html, «04 · Результат»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;

return array_merge(
	Trust\Case_Blocks::fields( __( 'Результат', 'trust' ), __( 'Что получилось за [N] месяцев', 'trust' ) ),
	array(
		array(
			'name'         => 'tabs',
			'label'        => __( 'Графики', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'max'          => 6,
			'button_label' => __( 'Добавить график', 'trust' ),
			'instructions' => __( 'Каждый график — своя вкладка (один график — без вкладок). Картинка 16 : 10, лучше 1280 × 800 px: скриншот из Метрики, Вебмастера или отчёта.', 'trust' ),
			'collapsed'    => 'field_trust_block_case_results_tabs_label',
			'sub_fields'   => array(
				array(
					'name'      => 'label',
					'label'     => __( 'Название вкладки', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 30,
					'wrapper'   => array( 'width' => 40 ),
				),
				array(
					'name'          => 'image',
					'label'         => __( 'Картинка графика', 'trust' ),
					'type'          => 'image',
					'required'      => 1,
					'return_format' => 'id',
					'preview_size'  => 'medium',
					'mime_types'    => 'jpg, jpeg, png, webp',
					'wrapper'       => array( 'width' => 60 ),
				),
				array(
					'name'         => 'alt',
					'label'        => __( 'Alt — что на графике', 'trust' ),
					'type'         => 'text',
					'maxlength'    => 160,
					'instructions' => __( 'Пусто — alt из медиатеки.', 'trust' ),
				),
				array(
					'name'         => 'caption',
					'label'        => __( 'Подпись под графиком', 'trust' ),
					'type'         => 'textarea',
					'rows'         => 2,
					'new_lines'    => '',
					'maxlength'    => 240,
					'instructions' => __( 'Необязательно. Например: «Демонстрационный график», источник данных.', 'trust' ),
				),
			),
		),
		Blocks::text_field( 'tablist_label', __( 'Название группы вкладок (для скринридеров)', 'trust' ), __( 'Графики результатов', 'trust' ), 60 ),
		array(
			'name'  => 'table_tab',
			'label' => __( 'Таблица «до / после»', 'trust' ),
			'type'  => 'accordion',
			'open'  => 0,
		),
		array_merge( Blocks::text_field( 'col_metric', __( 'Столбец 1', 'trust' ), __( 'Показатель', 'trust' ), 40 ), array( 'wrapper' => array( 'width' => 33 ) ) ),
		array_merge( Blocks::text_field( 'col_before', __( 'Столбец 2', 'trust' ), __( 'До', 'trust' ), 40 ), array( 'wrapper' => array( 'width' => 33 ) ) ),
		array_merge( Blocks::text_field( 'col_after', __( 'Столбец 3', 'trust' ), __( 'Через [N] мес.', 'trust' ), 40 ), array( 'wrapper' => array( 'width' => 34 ) ) ),
		array(
			'name'         => 'rows',
			'label'        => __( 'Строки', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'max'          => 12,
			'button_label' => __( 'Добавить строку', 'trust' ),
			'instructions' => __( 'Нет строк — таблицы нет. Значения «после» выделены фиолетовым.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'      => 'metric',
					'label'     => __( 'Показатель', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 80,
				),
				array(
					'name'      => 'before',
					'label'     => __( 'Было', 'trust' ),
					'type'      => 'text',
					'maxlength' => 20,
				),
				array(
					'name'      => 'after',
					'label'     => __( 'Стало', 'trust' ),
					'type'      => 'text',
					'maxlength' => 20,
				),
			),
		),
		Blocks::text_field( 'table_caption', __( 'Подпись таблицы (для скринридеров)', 'trust' ), __( 'Показатели до и после работ', 'trust' ), 80 ),
	)
);
