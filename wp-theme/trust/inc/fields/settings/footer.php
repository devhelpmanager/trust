<?php
/**
 * Настройки сайта → Подвал.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'label'  => __( 'Подвал', 'trust' ),
	'fields' => array(
		array(
			'name'         => 'footer_description',
			'label'        => __( 'Описание компании', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 3,
			'maxlength'    => 400,
			'instructions' => __( 'Текст под названием в подвале. Рекомендуем 150–250 символов.', 'trust' ),
		),
		array(
			'name'         => 'footer_tags',
			'label'        => __( 'Плашки-направления', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'max'          => 8,
			'button_label' => __( 'Добавить плашку', 'trust' ),
			'instructions' => __( 'Короткие слова под описанием, например SEO, GEO, Сайты. Рекомендуем 3–6 штук.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'      => 'label',
					'label'     => __( 'Текст', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 30,
				),
			),
		),
		array(
			'name'          => 'footer_contacts_link',
			'label'         => __( 'Заголовок колонки контактов', 'trust' ),
			'type'          => 'link',
			'return_format' => 'array',
			'instructions'  => __( 'Текст и ссылка заголовка над телефоном в подвале, например «Контакты» → страница контактов. Если не заполнено — заголовок «Контакты» без ссылки. Заголовки колонок «Услуги» и «Компания» — это названия меню «Подвал: услуги» и «Подвал: компания».', 'trust' ),
		),
		array(
			'name'         => 'footer_legal',
			'label'        => __( 'Юридическая строка', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 2,
			'new_lines'    => '',
			'instructions' => __( 'Внизу подвала. {год} заменяется текущим годом. Например: © {год} ООО «Название» · ИНН 0000000000 · ОГРН 0000000000000 · юридический адрес.', 'trust' ),
		),
		array(
			'name'    => 'footer_menus_note',
			'label'   => __( 'Ссылки в подвале', 'trust' ),
			'type'    => 'message',
			'message' => sprintf(
				/* translators: %s: адрес страницы «Меню». */
				__( 'Колонки «Услуги», «Компания» и ссылки на документы настраиваются в <a href="%s">Внешний вид → Меню</a>: меню «Подвал: услуги», «Подвал: компания», «Подвал: документы».', 'trust' ),
				esc_url( admin_url( 'nav-menus.php' ) )
			),
		),
	),
);
