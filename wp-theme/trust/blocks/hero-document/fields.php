<?php
/**
 * Поля блока «Первый экран документа» (макеты holst-o-nas/Offer, Cookies, Consent →
 * o-nas/oferta.html, politika-cookie.html, soglasie-pd.html).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_date = array(
	'type'           => 'date_picker',
	'display_format' => 'd.m.Y',
	'return_format'  => 'Y-m-d',
	'first_day'      => 1,
	'wrapper'        => array( 'width' => 50 ),
);

return array(
	array(
		'name'    => 'h1_note',
		'label'   => __( 'Заголовок H1 и хлебные крошки', 'trust' ),
		'type'    => 'message',
		'message' => __( 'H1 — заголовок страницы (или поле «H1» в группе «SEO» справа). Хлебные крошки строятся автоматически.', 'trust' ),
	),
	array(
		'name'          => 'badge',
		'label'         => __( 'Плашка', 'trust' ),
		'type'          => 'text',
		'default_value' => __( 'Документы', 'trust' ),
		'maxlength'     => 40,
	),
	array_merge(
		$trust_date,
		array(
			'name'         => 'revision',
			'label'        => __( 'Редакция от', 'trust' ),
			'instructions' => __( 'Дата текущей редакции. У страницы согласия на обработку данных она же записывается в каждую заявку как дата редакции документа.', 'trust' ),
		)
	),
	array_merge(
		$trust_date,
		array(
			'name'         => 'effective',
			'label'        => __( 'Действует с', 'trust' ),
			'instructions' => __( 'Необязательно.', 'trust' ),
		)
	),
	array(
		'name'          => 'pdf',
		'label'         => __( 'PDF документа', 'trust' ),
		'type'          => 'file',
		'return_format' => 'id',
		'mime_types'    => 'pdf',
		'instructions'  => __( 'Необязательно. Есть файл — показывается кнопка «Скачать PDF».', 'trust' ),
		'wrapper'       => array( 'width' => 50 ),
	),
	array(
		'name'        => 'pdf_label',
		'label'       => __( 'Текст кнопки PDF', 'trust' ),
		'type'        => 'text',
		'maxlength'   => 30,
		'placeholder' => __( 'Скачать PDF', 'trust' ),
		'wrapper'     => array( 'width' => 50 ),
	),
	array(
		'name'          => 'print',
		'label'         => __( 'Кнопка «Распечатать»', 'trust' ),
		'type'          => 'true_false',
		'ui'            => 1,
		'default_value' => 1,
		'wrapper'       => array( 'width' => 50 ),
	),
	array(
		'name'          => 'cookie',
		'label'         => __( 'Кнопка «Настроить cookie»', 'trust' ),
		'type'          => 'true_false',
		'ui'            => 1,
		'default_value' => 0,
		'instructions'  => __( 'Для политики cookie: открывает выбор cookie.', 'trust' ),
		'wrapper'       => array( 'width' => 50 ),
	),
);
