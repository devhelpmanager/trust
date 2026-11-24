<?php
/**
 * Поля блока «Команда по направлениям» (ТЗ, п. 8.6; макет holst-o-nas/Team, o-nas/komanda.html).
 * Сотрудники и отделы — из раздела «Сотрудники» (порядок — поле «Порядок» сотрудника).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'team',
			'eyebrow' => __( 'Специалисты', 'trust' ),
			'title'   => __( 'Команда по направлениям', 'trust' ),
			'lead'    => '',
		)
	),
	array(
		array(
			'name'          => 'leaders',
			'label'         => __( 'Руководство в списке', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 0,
			'instructions'  => __( 'Выключено — сотрудники с флажком «Руководство» не повторяются: они в блоке «Руководство».', 'trust' ),
			'wrapper'       => array( 'width' => 50 ),
		),
		array(
			'name'          => 'filter',
			'label'         => __( 'Фильтр по отделам', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'instructions'  => __( 'Кнопки отделов с числом людей. Показываются, если отделов два и больше.', 'trust' ),
			'wrapper'       => array( 'width' => 50 ),
		),
		Trust\Blocks::text_field( 'all_label', __( 'Кнопка «Все»', 'trust' ), __( 'Все', 'trust' ), 20 ),
		Trust\Blocks::text_field( 'blog_label', __( 'Ссылка на статьи', 'trust' ), __( 'Статьи автора', 'trust' ), 30, __( 'У авторов статей: ведёт на страницу сотрудника.', 'trust' ) ),
	)
);
