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
		'rows'         => 3,
		'new_lines'    => '',
		'maxlength'    => 300,
		'instructions' => __( 'Под заголовком: что получит клиент, 1–2 предложения (до 160 символов).', 'trust' ),
	),
	Trust\Blocks::buttons_field( 2 ),
);
