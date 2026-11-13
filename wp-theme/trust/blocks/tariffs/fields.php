<?php
/**
 * Поля блока «Выбор тарифа» (макет holst-blokov/Main → Tariffs, seo-bloki/01-tarify.html).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'tariffs',
			'eyebrow' => __( 'Тарифы', 'trust' ),
			'title'   => __( 'Выберите тариф', 'trust' ),
			'lead'    => __( 'Сравните, что входит в каждый тариф. Состав работ и стоимость фиксируются в договоре.', 'trust' ),
		)
	),
	array(
		Trust\Blocks::head_art_field(),
		array(
			'name'          => 'badge_label',
			'label'         => __( 'Плашка рекомендуемого тарифа', 'trust' ),
			'type'          => 'text',
			'default_value' => __( 'Рекомендуем', 'trust' ),
			'maxlength'     => 20,
		),
		array(
			'name'         => 'tariffs',
			'label'        => __( 'Тарифы', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 1,
			'max'          => 4,
			'button_label' => __( 'Добавить тариф', 'trust' ),
			'collapsed'    => 'field_trust_block_tariffs_tariffs_name',
			'instructions' => __( 'От 1 до 4 тарифов. Цены — текстом, как показывать («от 25 000 ₽»).', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'      => 'name',
					'label'     => __( 'Название', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 30,
				),
				array(
					'name'         => 'recommended',
					'label'        => __( 'Рекомендуем', 'trust' ),
					'type'         => 'true_false',
					'ui'           => 1,
					'instructions' => __( 'Тёмная карточка с плашкой.', 'trust' ),
				),
				array(
					'name'         => 'who',
					'label'        => __( 'Кому подходит', 'trust' ),
					'type'         => 'textarea',
					'rows'         => 2,
					'new_lines'    => '',
					'maxlength'    => 250,
					'instructions' => __( '1–2 предложения.', 'trust' ),
				),
				array(
					'name'      => 'old_price',
					'label'     => __( 'Старая цена', 'trust' ),
					'type'      => 'text',
					'maxlength' => 30,
				),
				array(
					'name'        => 'discount',
					'label'       => __( 'Скидка', 'trust' ),
					'type'        => 'text',
					'maxlength'   => 10,
					'placeholder' => '−20%',
				),
				array(
					'name'      => 'price',
					'label'     => __( 'Цена', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 30,
				),
				array(
					'name'          => 'period',
					'label'         => __( 'Период', 'trust' ),
					'type'          => 'text',
					'maxlength'     => 20,
					'default_value' => __( '/ месяц', 'trust' ),
				),
				array(
					'name'         => 'price_value',
					'label'        => __( 'Цена числом, ₽ (для поисковиков)', 'trust' ),
					'type'         => 'number',
					'min'          => 0,
					'instructions' => __( 'Необязательно. Попадает в микроразметку услуги (Offer); пусто — тариф в разметку не попадает.', 'trust' ),
				),
				array(
					'name'         => 'items',
					'label'        => __( 'Состав', 'trust' ),
					'type'         => 'repeater',
					'layout'       => 'table',
					'max'          => 15,
					'button_label' => __( 'Добавить пункт', 'trust' ),
					'sub_fields'   => array(
						array(
							'name'      => 'text',
							'label'     => __( 'Пункт', 'trust' ),
							'type'      => 'text',
							'required'  => 1,
							'maxlength' => 120,
						),
						array(
							'name'          => 'included',
							'label'         => __( 'Включено', 'trust' ),
							'type'          => 'true_false',
							'ui'            => 1,
							'default_value' => 1,
						),
					),
				),
				array(
					'name'          => 'button',
					'label'         => __( 'Кнопка', 'trust' ),
					'type'          => 'link',
					'return_format' => 'array',
					'instructions'  => __( 'Пусто — кнопки нет. Текст можно не заполнять: будет «Выбрать «Название»». #callback-modal — окно «Заказать обратный звонок».', 'trust' ),
				),
			),
		),
	)
);
