<?php
/**
 * Поля блока «Этапы работы» (макет holst-stranitsy/Main → Stages, seo-bloki/06-etapy-raboty.html).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'stages',
			'eyebrow' => __( 'Как мы работаем', 'trust' ),
			'title'   => __( 'Этапы работы', 'trust' ),
			'lead'    => __( 'Нажмите на этап, чтобы узнать, что на нём происходит.', 'trust' ),
		)
	),
	array(
		array(
			'name'         => 'stages',
			'label'        => __( 'Этапы', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 3,
			'max'          => 7,
			'button_label' => __( 'Добавить этап', 'trust' ),
			'collapsed'    => 'field_trust_block_stages_stages_title',
			'instructions' => __( 'От 3 до 7 этапов. Номера ставятся автоматически. До 5 этапов — в один ряд, 6 и 7 — в два ряда.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'          => 'picture',
					'label'         => __( 'Картинка', 'trust' ),
					'type'          => 'select',
					'choices'       => array(
						'application' => __( 'Рисунок «Заявка»', 'trust' ),
						'analysis'    => __( 'Рисунок «Анализ»', 'trust' ),
						'contract'    => __( 'Рисунок «Договор»', 'trust' ),
						'work'        => __( 'Рисунок «Работа»', 'trust' ),
						'report'      => __( 'Рисунок «Отчёт» (для итогового этапа)', 'trust' ),
						'image'       => __( 'Своя картинка', 'trust' ),
						'none'        => __( 'Без картинки', 'trust' ),
					),
					'default_value' => 'work',
					'instructions'  => __( 'Рисунки из макета или своя картинка.', 'trust' ),
					'wrapper'       => array( 'width' => 40 ),
				),
				array(
					'name'              => 'image',
					'label'             => __( 'Своя картинка', 'trust' ),
					'type'              => 'image',
					'return_format'     => 'id',
					'preview_size'      => 'medium',
					'library'           => 'all',
					'mime_types'        => 'jpg,jpeg,png,webp,svg',
					'instructions'      => __( 'Пропорции 16:10, не меньше 480×300 px. Описание (alt) — в медиатеке; для украшения оставьте пустым.', 'trust' ),
					'wrapper'           => array( 'width' => 60 ),
					'conditional_logic' => array(
						array(
							array(
								'field'    => 'field_trust_block_stages_stages_picture',
								'operator' => '==',
								'value'    => 'image',
							),
						),
					),
				),
				array(
					'name'         => 'title',
					'label'        => __( 'Название', 'trust' ),
					'type'         => 'text',
					'required'     => 1,
					'maxlength'    => 60,
					'instructions' => __( '1–3 слова.', 'trust' ),
				),
				array(
					'name'         => 'short',
					'label'        => __( 'Короткий текст', 'trust' ),
					'type'         => 'textarea',
					'rows'         => 2,
					'new_lines'    => '',
					'required'     => 1,
					'maxlength'    => 160,
					'instructions' => __( 'Виден сразу под названием. Одно предложение, до 70 символов.', 'trust' ),
				),
				array(
					'name'         => 'detail',
					'label'        => __( 'Подробный текст', 'trust' ),
					'type'         => 'textarea',
					'rows'         => 3,
					'new_lines'    => '',
					'required'     => 1,
					'maxlength'    => 400,
					'instructions' => __( 'Показывается по нажатию на карточку вместо картинки и короткого текста. До 200 символов.', 'trust' ),
				),
				array(
					'name'         => 'result',
					'label'        => __( 'Итоговый этап', 'trust' ),
					'type'         => 'true_false',
					'ui'           => 1,
					'instructions' => __( 'Фиолетовая карточка с подписью «Результат». Обычно — последний этап.', 'trust' ),
				),
			),
		),
	)
);
