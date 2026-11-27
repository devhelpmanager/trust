<?php
/**
 * Поля блока «Кто работал над проектом» (ТЗ, п. 8.10; макет portfolio-blog-404/keys.html,
 * «06 · Команда»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Case_Blocks::fields( __( 'Команда', 'trust' ), __( 'Кто работал над проектом', 'trust' ), 'case-team' ),
	array(
		array(
			'name'         => 'members',
			'label'        => __( 'Свой список', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'max'          => 12,
			'button_label' => __( 'Добавить сотрудника', 'trust' ),
			'instructions' => __( 'Пусто — команда из данных кейса («Команда проекта»). Заполните, чтобы показать другой состав.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'          => 'member',
					'label'         => __( 'Сотрудник', 'trust' ),
					'type'          => 'post_object',
					'post_type'     => array( 'team' ),
					'return_format' => 'id',
					'ui'            => 1,
					'required'      => 1,
				),
				array(
					'name'         => 'role',
					'label'        => __( 'Роль в проекте', 'trust' ),
					'type'         => 'text',
					'maxlength'    => 60,
					'instructions' => __( 'Пусто — должность сотрудника.', 'trust' ),
				),
			),
		),
	)
);
