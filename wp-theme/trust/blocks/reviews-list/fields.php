<?php
/**
 * Поля блока «Что говорят клиенты» (ТЗ, п. 8.7; макет holst-o-nas/Reviews, o-nas/otzyvy.html).
 * Отзывы — все опубликованные текстовые из раздела «Отзывы» (новые сначала).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'reviews',
			'eyebrow' => __( 'Отзывы клиентов', 'trust' ),
			'title'   => __( 'Что говорят клиенты', 'trust' ),
			'lead'    => '',
		)
	),
	array(
		array(
			'name'          => 'per_page',
			'label'         => __( 'Сколько отзывов показывать сразу', 'trust' ),
			'type'          => 'number',
			'min'           => 2,
			'max'           => 24,
			'default_value' => 6,
			'instructions'  => __( 'Остальные — по кнопке «Показать ещё» (столько же за раз).', 'trust' ),
			'wrapper'       => array( 'width' => 50 ),
		),
		array(
			'name'          => 'filter',
			'label'         => __( 'Фильтр по услугам', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'instructions'  => __( 'Кнопки услуг с числом отзывов (услуги — поле «Услуги» отзыва). Нужны две услуги и больше.', 'trust' ),
			'wrapper'       => array( 'width' => 50 ),
		),
		Trust\Blocks::text_field( 'button_label', __( 'Кнопка справа от заголовка', 'trust' ), __( 'Оставить отзыв', 'trust' ), 30, __( 'Пусто — без кнопки.', 'trust' ) ),
		array(
			'name'         => 'button_url',
			'label'        => __( 'Адрес кнопки', 'trust' ),
			'type'         => 'text',
			'placeholder'  => '#leave-review',
			'instructions' => __( 'Пусто — к блоку «Оставьте отзыв» на этой странице (#leave-review).', 'trust' ),
		),
		Trust\Blocks::text_field( 'all_label', __( 'Кнопка «Все»', 'trust' ), __( 'Все', 'trust' ), 20 ),
		Trust\Blocks::text_field( 'more_label', __( 'Кнопка догрузки', 'trust' ), __( 'Показать ещё', 'trust' ), 30, __( 'К подписи добавляется, сколько отзывов осталось.', 'trust' ) ),
	)
);
