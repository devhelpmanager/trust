<?php
/**
 * Поля блока «Кейсы» (ТЗ, п. 8.10; макет portfolio-blog-404/portfolio.html, секция «Кейсы»).
 * Сами кейсы — раздел «Кейсы»: услуги, отрасль, «Главный кейс», результаты, обложка.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;

return array(
	array(
		'name'         => 'anchor',
		'label'        => __( 'Якорь', 'trust' ),
		'type'         => 'text',
		'placeholder'  => 'cases',
		'instructions' => __( 'Необязательно. Латиница, цифры и дефис, без #.', 'trust' ),
		'wrapper'      => array( 'width' => 50 ),
	),
	array(
		'name'          => 'per_page',
		'label'         => __( 'Кейсов сразу', 'trust' ),
		'type'          => 'number',
		'min'           => 3,
		'max'           => 24,
		'default_value' => 9,
		'instructions'  => __( 'Вместе с главным. Остальные — кнопкой «Показать ещё кейсы».', 'trust' ),
		'wrapper'       => array( 'width' => 50 ),
	),
	array(
		'name'    => 'cases_note',
		'label'   => __( 'Откуда кейсы', 'trust' ),
		'type'    => 'message',
		'message' => __( 'Все опубликованные кейсы, новые первыми. Кнопки услуг и список отраслей — из услуг и отраслей кейсов. Главный кейс (крупно, пока фильтры не выбраны) — отметка «Главный кейс» у кейса. Карточка: обложка («Изображение записи», иначе скриншот сайта), отрасль, услуги (в том порядке, в каком их отметили у кейса — основную отмечайте первой), заголовок, анонс, первые два результата. Кейсы под паролем в список не попадают.', 'trust' ),
	),
	Blocks::text_field( 'title', __( 'Заголовок (H2, только для скринридеров)', 'trust' ), __( 'Кейсы', 'trust' ), 60 ),
	Blocks::text_field( 'service_label', __( 'Подпись кнопок услуг', 'trust' ), __( 'Услуга', 'trust' ), 30 ),
	Blocks::text_field( 'all_label', __( 'Кнопка «Все»', 'trust' ), __( 'Все', 'trust' ), 20 ),
	Blocks::text_field( 'industry_label', __( 'Подпись списка отраслей', 'trust' ), __( 'Отрасль', 'trust' ), 30 ),
	Blocks::text_field( 'industry_all', __( 'Пункт «Все отрасли»', 'trust' ), __( 'Все отрасли', 'trust' ), 30 ),
	Blocks::text_field( 'badge', __( 'Плашка главного кейса', 'trust' ), __( 'Главный кейс', 'trust' ), 30 ),
	Blocks::text_field( 'more', __( 'Кнопка «Смотреть кейс»', 'trust' ), __( 'Смотреть кейс', 'trust' ), 30 ),
	Blocks::text_field( 'load_more', __( 'Кнопка «Показать ещё»', 'trust' ), __( 'Показать ещё кейсы', 'trust' ), 40 ),
	Blocks::text_field( 'empty_title', __( 'Пустой результат: заголовок', 'trust' ), __( 'Таких кейсов пока нет', 'trust' ), 60 ),
	array(
		'name'          => 'empty_text',
		'label'         => __( 'Пустой результат: текст', 'trust' ),
		'type'          => 'textarea',
		'rows'          => 2,
		'new_lines'     => '',
		'maxlength'     => 300,
		'default_value' => __( 'Попробуйте другую услугу или отрасль. А если у вас похожая задача — расскажите о ней, подберём решение.', 'trust' ),
	),
	Blocks::text_field( 'empty_button', __( 'Пустой результат: кнопка', 'trust' ), __( 'Показать все кейсы', 'trust' ), 40 ),
);
