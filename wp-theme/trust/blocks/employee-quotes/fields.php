<?php
/**
 * Поля блока «Сотрудники о работе у нас» (ТЗ, п. 8.8; макет o-nas/karera.html, «Изнутри»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'voices',
			'eyebrow' => __( 'Изнутри', 'trust' ),
			'title'   => __( 'Сотрудники о работе у нас', 'trust' ),
			'lead'    => '',
		)
	),
	array(
		array(
			'name'          => 'more',
			'label'         => __( 'Кнопка справа от заголовка', 'trust' ),
			'type'          => 'link',
			'return_format' => 'array',
			'instructions'  => __( 'Пусто — «Вся команда» на страницу типа «Команда». Чтобы убрать кнопку, выключите переключатель ниже.', 'trust' ),
			'wrapper'       => array( 'width' => 70 ),
		),
		array(
			'name'          => 'show_more',
			'label'         => __( 'Показывать кнопку', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'wrapper'       => array( 'width' => 30 ),
		),
		array(
			'name'         => 'quotes',
			'label'        => __( 'Цитаты', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 1,
			'max'          => 9,
			'button_label' => __( 'Добавить цитату', 'trust' ),
			'instructions' => __( 'Фото и имя — из карточки сотрудника. На компьютере — по три в ряд, на телефоне — в столбик.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'          => 'member',
					'label'         => __( 'Сотрудник', 'trust' ),
					'type'          => 'post_object',
					'post_type'     => array( 'team' ),
					'return_format' => 'id',
					'required'      => 1,
					'ui'            => 1,
					'wrapper'       => array( 'width' => 50 ),
				),
				array(
					'name'         => 'caption',
					'label'        => __( 'Подпись под именем', 'trust' ),
					'type'         => 'text',
					'maxlength'    => 80,
					'placeholder'  => __( 'SEO-специалист · в команде с 2021', 'trust' ),
					'instructions' => __( 'Пусто — должность сотрудника.', 'trust' ),
					'wrapper'      => array( 'width' => 50 ),
				),
				array(
					'name'         => 'text',
					'label'        => __( 'Цитата', 'trust' ),
					'type'         => 'textarea',
					'rows'         => 3,
					'new_lines'    => '',
					'required'     => 1,
					'maxlength'    => 500,
					'instructions' => __( '2–3 предложения: как пришёл в команду, чему научился, что нравится в работе.', 'trust' ),
				),
			),
		),
	)
);
