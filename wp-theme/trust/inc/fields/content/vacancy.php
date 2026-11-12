<?php
/**
 * Поля вакансии (ТЗ, раздел 5.3). Классический экран: заголовок — название вакансии,
 * «Отделы» и «Порядок» — стандартные панели. Вакансии выводятся на странице «Карьера»
 * (шаг 4.13), своей страницы нет.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'    => __( 'Вакансия', 'trust' ),
	'location' => 'post_type',
	'fields'   => array(
		array(
			'name'          => 'trust_open',
			'label'         => __( 'Принимаем резюме', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'message'       => __( 'Показывать вакансию на странице «Карьера»', 'trust' ),
			'instructions'  => __( 'Снимите, когда набор закрыт: вакансия пропадёт с сайта, но останется в админке.', 'trust' ),
		),
		array(
			'name'        => 'trust_salary',
			'label'       => __( 'Зарплата', 'trust' ),
			'type'        => 'text',
			'maxlength'   => 50,
			'placeholder' => 'от 150 000 ₽',
			'wrapper'     => array( 'width' => 50 ),
		),
		array(
			'name'        => 'trust_format',
			'label'       => __( 'Формат работы', 'trust' ),
			'type'        => 'text',
			'maxlength'   => 50,
			'placeholder' => 'Офис, гибрид или удалённо',
			'wrapper'     => array( 'width' => 50 ),
		),
		array(
			'name'        => 'trust_experience',
			'label'       => __( 'Опыт', 'trust' ),
			'type'        => 'text',
			'maxlength'   => 40,
			'placeholder' => 'Опыт от 3 лет',
			'wrapper'     => array( 'width' => 50 ),
		),
		array(
			'name'         => 'trust_city',
			'label'        => __( 'Город', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 40,
			'instructions' => __( 'Пусто — город из «Настроек сайта → Контакты».', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'         => 'trust_summary',
			'label'        => __( 'Коротко о вакансии', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 3,
			'maxlength'    => 300,
			'instructions' => __( 'Абзац над разделами: чем будет заниматься сотрудник, 1–2 предложения.', 'trust' ),
		),
		array(
			'name'         => 'trust_sections',
			'label'        => __( 'Разделы описания', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'button_label' => __( 'Добавить раздел', 'trust' ),
			'instructions' => __( 'Обычно три раздела — у новой вакансии они уже добавлены. Пункты списка — каждый с новой строки.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'      => 'title',
					'label'     => __( 'Заголовок раздела', 'trust' ),
					'type'      => 'text',
					'maxlength' => 60,
					'required'  => 1,
				),
				array(
					'name'      => 'items',
					'label'     => __( 'Пункты', 'trust' ),
					'type'      => 'textarea',
					'rows'      => 4,
					'new_lines' => '',
					'required'  => 1,
				),
			),
		),
	),
);
