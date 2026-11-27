<?php
/**
 * Поля блока «Как бы поступил я» (ТЗ, п. 8.11; макет seo-vopros/otvet.html, итог ответа).
 * Пункты и текст — вложенные блоки (список, абзац) прямо в блоке.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;

return array(
	array(
		'name'    => 'how_note',
		'label'   => __( 'Как заполнить', 'trust' ),
		'type'    => 'message',
		'message' => __( 'Пункты печатаются прямо в блоке: список — с галочками, можно добавить абзац. Внизу — текст и кнопка (необязательно).', 'trust' ),
	),
	Blocks::text_field( 'title', __( 'Заголовок', 'trust' ), __( 'Как бы поступил я', 'trust' ), 60 ),
	array(
		'name'          => 'level',
		'label'         => __( 'Уровень заголовка', 'trust' ),
		'type'          => 'select',
		'choices'       => array(
			'h2' => 'H2',
			'h3' => 'H3',
		),
		'default_value' => 'h2',
		'instructions'  => __( 'H2 — отдельный раздел ответа (обычно), H3 — часть раздела выше.', 'trust' ),
		'wrapper'       => array( 'width' => 50 ),
	),
	array(
		'name'      => 'note',
		'label'     => __( 'Текст внизу', 'trust' ),
		'type'      => 'textarea',
		'rows'      => 2,
		'new_lines' => '',
		'maxlength' => 240,
	),
	array(
		'name'          => 'button',
		'label'         => __( 'Кнопка внизу', 'trust' ),
		'type'          => 'link',
		'return_format' => 'array',
		'instructions'  => __( 'Например: «Заказать технический аудит» — страница услуги.', 'trust' ),
	),
);
