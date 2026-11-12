<?php
/**
 * Поля блока «Первый экран отзывов» (макет holst-o-nas/Reviews → o-nas/otzyvy.html).
 *
 * Рейтинги — только текст (без микроразметки AggregateRating: оценки площадок не наши).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'name'    => 'h1_note',
		'label'   => __( 'Заголовок H1 и хлебные крошки', 'trust' ),
		'type'    => 'message',
		'message' => __( 'H1 — заголовок страницы (или поле «H1» в группе «SEO» справа). Хлебные крошки строятся автоматически.', 'trust' ),
	),
	array(
		'name'          => 'badge',
		'label'         => __( 'Плашка', 'trust' ),
		'type'          => 'text',
		'default_value' => __( 'О нас', 'trust' ),
		'maxlength'     => 40,
	),
	array(
		'name'         => 'lead',
		'label'        => __( 'Текст', 'trust' ),
		'type'         => 'textarea',
		'rows'         => 3,
		'new_lines'    => '',
		'maxlength'    => 400,
		'instructions' => __( 'Под заголовком, 1–3 предложения.', 'trust' ),
	),
	array(
		'name'         => 'platforms',
		'label'        => __( 'Рейтинги на площадках', 'trust' ),
		'type'         => 'repeater',
		'layout'       => 'table',
		'max'          => 8,
		'button_label' => __( 'Добавить площадку', 'trust' ),
		'instructions' => __( 'Например: Яндекс Карты · 4,9 · 128 отзывов · ссылка на страницу отзывов. Звёзды закрашиваются по оценке (из 5).', 'trust' ),
		'sub_fields'   => array(
			array(
				'name'        => 'name',
				'label'       => __( 'Площадка', 'trust' ),
				'type'        => 'text',
				'required'    => 1,
				'maxlength'   => 30,
				'placeholder' => __( 'Яндекс Карты', 'trust' ),
			),
			array(
				'name'        => 'rating',
				'label'       => __( 'Оценка', 'trust' ),
				'type'        => 'text',
				'required'    => 1,
				'maxlength'   => 6,
				'placeholder' => '4,9',
				'wrapper'     => array( 'width' => 15 ),
			),
			array(
				'name'        => 'count',
				'label'       => __( 'Отзывов', 'trust' ),
				'type'        => 'text',
				'maxlength'   => 30,
				'placeholder' => __( '128 отзывов', 'trust' ),
			),
			array(
				'name'        => 'url',
				'label'       => __( 'Ссылка', 'trust' ),
				'type'        => 'url',
				'placeholder' => 'https://',
			),
		),
	),
);
