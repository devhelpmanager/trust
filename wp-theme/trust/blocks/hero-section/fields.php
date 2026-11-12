<?php
/**
 * Поля блока «Первый экран раздела» (макеты holst-portfolio/Main, holst-o-nas/Company, Team,
 * Career → portfolio-blog-404/portfolio.html, o-nas/o-kompanii.html, komanda.html, karera.html).
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
		'name'         => 'badge',
		'label'        => __( 'Плашка', 'trust' ),
		'type'         => 'text',
		'maxlength'    => 40,
		'instructions' => __( 'Над заголовком: раздел сайта, например «О нас» или «Портфолио».', 'trust' ),
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
	Trust\Blocks::buttons_field( 2, true ),
	array(
		'name'         => 'stats',
		'label'        => __( 'Цифры', 'trust' ),
		'type'         => 'repeater',
		'layout'       => 'table',
		'max'          => 4,
		'button_label' => __( 'Добавить цифру', 'trust' ),
		'instructions' => __( 'Необязательно, до 4. В значении можно написать {vacancies} — подставится число вакансий с отметкой «Принимаем резюме».', 'trust' ),
		'sub_fields'   => array(
			array(
				'name'        => 'value',
				'label'       => __( 'Значение', 'trust' ),
				'type'        => 'text',
				'required'    => 1,
				'maxlength'   => 20,
				'placeholder' => '120+',
				'wrapper'     => array( 'width' => 35 ),
			),
			array(
				'name'        => 'label',
				'label'       => __( 'Подпись', 'trust' ),
				'type'        => 'text',
				'required'    => 1,
				'maxlength'   => 60,
				'placeholder' => __( 'завершённых проектов', 'trust' ),
			),
		),
	),
);
