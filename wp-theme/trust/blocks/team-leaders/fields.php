<?php
/**
 * Поля блока «Руководство» (ТЗ, п. 8.6; макет holst-o-nas/Team, o-nas/komanda.html).
 * Карточка — из карточки сотрудника: фото, имя, должность, «Коротко для карточки», email.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'leaders',
			'eyebrow' => __( 'Руководство', 'trust' ),
			'title'   => __( 'Кто отвечает за результат', 'trust' ),
			'lead'    => '',
		)
	),
	array(
		array(
			'name'          => 'source',
			'label'         => __( 'Кого показывать', 'trust' ),
			'type'          => 'radio',
			'choices'       => array(
				'flag'   => __( 'Сотрудников с флажком «Руководство» (порядок — поле «Порядок»)', 'trust' ),
				'manual' => __( 'Выбрать вручную', 'trust' ),
			),
			'default_value' => 'flag',
			'layout'        => 'vertical',
		),
		array(
			'name'              => 'members',
			'label'             => __( 'Руководители', 'trust' ),
			'type'              => 'relationship',
			'post_type'         => array( 'team' ),
			'post_status'       => array( 'publish' ),
			'filters'           => array( 'search' ),
			'return_format'     => 'id',
			'max'               => 6,
			'instructions'      => __( 'Порядок — как в списке справа (перетаскивайте).', 'trust' ),
			'conditional_logic' => array(
				array(
					array(
						'field'    => 'field_trust_block_team_leaders_source',
						'operator' => '==',
						'value'    => 'manual',
					),
				),
			),
		),
		array(
			'name'          => 'email',
			'label'         => __( 'Email в карточке', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'instructions'  => __( 'Поле «Email для связи» сотрудника. Пусто — не показывается.', 'trust' ),
		),
		Trust\Blocks::text_field( 'blog_label', __( 'Ссылка на статьи', 'trust' ), __( 'Статьи в блоге', 'trust' ), 30, __( 'У авторов статей: ведёт на страницу сотрудника.', 'trust' ) ),
		Trust\Blocks::text_field( 'answers_label', __( 'Ссылка на ответы', 'trust' ), __( 'Ответы на вопросы', 'trust' ), 30, __( 'Если сотрудник отвечает на вопросы SEO: ведёт на страницу вопросов.', 'trust' ) ),
	)
);
