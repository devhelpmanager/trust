<?php
/**
 * Настройки сайта → Компания.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'label'  => __( 'Компания', 'trust' ),
	'fields' => array(
		array(
			'name'         => 'company_brand',
			'label'        => __( 'Бренд', 'trust' ),
			'type'         => 'text',
			'required'     => 1,
			'maxlength'    => 60,
			'instructions' => __( 'Название компании. Выводится в шапке и подвале, в заголовках страниц и в микроразметке. Рекомендуем до 30 символов.', 'trust' ),
		),
		array(
			'name'         => 'company_tagline',
			'label'        => __( 'Подпись под названием', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 60,
			'instructions' => __( 'Короткая строка под названием в шапке, например «SEO · сайты · реклама». Рекомендуем до 30 символов.', 'trust' ),
		),
		array(
			'name'          => 'company_logo_light',
			'label'         => __( 'Логотип для тёмного фона', 'trust' ),
			'type'          => 'image',
			'return_format' => 'id',
			'preview_size'  => 'medium',
			'library'       => 'all',
			'instructions'  => __( 'Шапка и подвал (фон индиго). PNG или WebP с прозрачным фоном, высота не меньше 84 px. Пока логотип не загружен, показывается знак из дизайна и название бренда.', 'trust' ),
		),
		array(
			'name'          => 'company_logo_dark',
			'label'         => __( 'Логотип для светлого фона', 'trust' ),
			'type'          => 'image',
			'return_format' => 'id',
			'preview_size'  => 'medium',
			'library'       => 'all',
			'instructions'  => __( 'Для печатной версии документов, писем и микроразметки. PNG или WebP с прозрачным фоном, высота не меньше 84 px.', 'trust' ),
		),
		array(
			'name'    => 'company_site_icon_note',
			'label'   => __( 'Значок сайта', 'trust' ),
			'type'    => 'message',
			'message' => sprintf(
				/* translators: %s: адрес настройки «Иконка сайта». */
				__( 'Значок во вкладке браузера (фавиконка) задаётся в <a href="%s">Внешний вид → Настроить → Свойства сайта → Иконка сайта</a>. Квадрат не меньше 512 × 512 px.', 'trust' ),
				esc_url( admin_url( 'customize.php?autofocus[control]=site_icon' ) )
			),
		),
	),
);
