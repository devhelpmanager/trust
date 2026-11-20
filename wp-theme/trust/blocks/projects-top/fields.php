<?php
/**
 * Поля блока «Наши проекты в топе» (ТЗ, п. 8.3, № 12; макеты holst-blokov/Projects,
 * seo-bloki/12-proekty-v-tope.html). Данные карточек — из кейсов: скриншот, «Чем занимается
 * клиент», клиент, позиции в поиске, регион и дата проверки (панель «Данные кейса»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'projects',
			'eyebrow' => __( 'Кейсы', 'trust' ),
			'title'   => __( 'Наши проекты в топе', 'trust' ),
			'lead'    => __( 'Запросы, по которым сайты наших клиентов сейчас находятся на первой странице Яндекса и Google.', 'trust' ),
		)
	),
	array(
		array(
			'name'          => 'source',
			'label'         => __( 'Какие кейсы', 'trust' ),
			'type'          => 'select',
			'choices'       => array(
				'auto'   => __( 'Автоматически — последние кейсы с позициями в поиске', 'trust' ),
				'manual' => __( 'Выбрать вручную', 'trust' ),
			),
			'default_value' => 'auto',
			'instructions'  => __( 'Позиции заполняются в кейсе: панель «Данные кейса» → «Позиции в поиске». Кейс без позиций в блоке не показывается.', 'trust' ),
		),
		array(
			'name'              => 'cases',
			'label'             => __( 'Кейсы', 'trust' ),
			'type'              => 'relationship',
			'post_type'         => array( 'case' ),
			'post_status'       => array( 'publish' ),
			'filters'           => array( 'search', 'taxonomy' ),
			'return_format'     => 'id',
			'max'               => 6,
			'instructions'      => __( 'Порядок — перетаскиванием.', 'trust' ),
			'conditional_logic' => array(
				array(
					array(
						'field'    => 'field_trust_block_projects-top_source',
						'operator' => '==',
						'value'    => 'manual',
					),
				),
			),
		),
		array(
			'name'              => 'count',
			'label'             => __( 'Сколько кейсов', 'trust' ),
			'type'              => 'number',
			'min'               => 1,
			'max'               => 6,
			'placeholder'       => '3',
			'instructions'      => __( 'Пусто — 3. В ряд — до 3 карточек.', 'trust' ),
			'conditional_logic' => array(
				array(
					array(
						'field'    => 'field_trust_block_projects-top_source',
						'operator' => '!=',
						'value'    => 'manual',
					),
				),
			),
		),
		Trust\Blocks::text_field( 'query_label', __( 'Заголовок столбца запросов', 'trust' ), __( 'Запрос', 'trust' ), 30 ),
		Trust\Blocks::text_field( 'position_label', __( 'Заголовок столбцов позиций', 'trust' ), __( 'Позиция в', 'trust' ), 20, __( 'Перед значками «Я» и «G».', 'trust' ) ),
		Trust\Blocks::text_field( 'date_label', __( 'Перед датой проверки', 'trust' ), __( 'данные на', 'trust' ), 30 ),
	)
);
