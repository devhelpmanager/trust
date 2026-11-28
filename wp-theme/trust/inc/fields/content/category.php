<?php
/**
 * Поля рубрики (ТЗ, раздел 5.3).
 *
 * Название — H1 страницы рубрики, описание — подзаголовок под ним (подсказки к стандартным
 * полям WordPress — Trust\Content_Types::category_labels()). SEO-поля — шаг 3.1. Поле «Ведёт
 * рубрику» убрано по решению заказчика (блок «Ведёт рубрику» на странице рубрики не выводится).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'    => __( 'Рубрика', 'trust' ),
	'location' => 'taxonomy',
	'fields'   => array(
		array(
			'name'         => 'trust_rubric_about',
			'label'        => __( 'О рубрике', 'trust' ),
			'type'         => 'wysiwyg',
			'tabs'         => 'visual',
			'toolbar'      => 'basic',
			'media_upload' => 0,
			'instructions' => __( 'Раскрывающийся блок «О рубрике» внизу страницы: о чём рубрика и что в ней найдёт читатель. 1–3 абзаца, можно со ссылками на услуги. Пусто — блок не показывается.', 'trust' ),
		),
	),
);
