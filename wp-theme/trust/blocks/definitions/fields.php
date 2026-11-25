<?php
/**
 * Поля блока «Список определений» (макеты документов: «Термины» оферты — линии; «Оператор»
 * и «Контакты» согласия и политики cookie — карточка). В ТЗ блока нет — добавлен по макетам
 * (инвентарь, раздел 7.6).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'name'          => 'view',
		'label'         => __( 'Вид', 'trust' ),
		'type'          => 'button_group',
		'choices'       => array(
			'lines' => __( 'Линии — термины', 'trust' ),
			'card'  => __( 'Карточка — данные, контакты', 'trust' ),
		),
		'default_value' => 'lines',
	),
	array(
		'name'         => 'items',
		'label'        => __( 'Строки', 'trust' ),
		'type'         => 'repeater',
		'layout'       => 'table',
		'min'          => 1,
		'max'          => 30,
		'button_label' => __( 'Добавить строку', 'trust' ),
		'instructions' => __( 'Email и адреса сайтов в значении становятся ссылками сами.', 'trust' ),
		'sub_fields'   => array(
			array(
				'name'      => 'term',
				'label'     => __( 'Название', 'trust' ),
				'type'      => 'text',
				'required'  => 1,
				'maxlength' => 80,
				'wrapper'   => array( 'width' => 35 ),
			),
			array(
				'name'      => 'value',
				'label'     => __( 'Значение', 'trust' ),
				'type'      => 'textarea',
				'rows'      => 2,
				'new_lines' => 'br',
				'required'  => 1,
				'maxlength' => 600,
			),
		),
	),
);
