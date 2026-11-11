<?php
/**
 * Настройки сайта → Соцсети.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'label'  => __( 'Соцсети', 'trust' ),
	'fields' => array(
		array(
			'name'         => 'social_links',
			'label'        => __( 'Соцсети компании', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'button_label' => __( 'Добавить соцсеть', 'trust' ),
			'instructions' => __( 'Ссылки на страницы компании. Выводятся с официальными значками сетей и попадают в микроразметку организации (sameAs).', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'     => 'network',
					'label'    => __( 'Сеть', 'trust' ),
					'type'     => 'select',
					'required' => 1,
					'choices'  => Trust\Social::networks(),
					'wrapper'  => array( 'width' => 30 ),
				),
				array(
					'name'        => 'url',
					'label'       => __( 'Ссылка', 'trust' ),
					'type'        => 'url',
					'required'    => 1,
					'placeholder' => 'https://…',
				),
			),
		),
	),
);
