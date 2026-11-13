<?php
/**
 * Поля блока «Первый экран услуги» (макет holst-stranitsy/Main, seo-stranitsa/index.html).
 *
 * Хлебные крошки и H1 не настраиваются в блоке: H1 — поле «H1» в группе «SEO» страницы
 * или её заголовок, крошки строятся сами (Trust\Breadcrumbs).
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
		'name'         => 'lead',
		'label'        => __( 'Вводный текст', 'trust' ),
		'type'         => 'textarea',
		'new_lines'    => '',
		'rows'         => 5,
		'maxlength'    => 1000,
		'instructions' => __( 'Под заголовком: что получит клиент. До 1000 символов (правка заказчика); на первом экране лучше 1–3 предложения.', 'trust' ),
	),
	Trust\Blocks::buttons_field( 2 ),
);
