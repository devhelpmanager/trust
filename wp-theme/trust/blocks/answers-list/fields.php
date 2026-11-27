<?php
/**
 * Поля блока «Ответы SEO-специалистов» (ТЗ, п. 8.11; макет seo-vopros/vopros-seo.html).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;

return array_merge(
	Blocks::section_fields(
		array(
			'anchor'  => 'answers',
			'eyebrow' => __( 'Вопросы и ответы', 'trust' ),
			'title'   => __( 'Ответы SEO-специалистов', 'trust' ),
			'lead'    => __( 'Вопросы читателей и ответы наших специалистов. Новые ответы — сверху.', 'trust' ),
		)
	),
	array(
		array(
			'name'    => 'answers_note',
			'label'   => __( 'Откуда ответы', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Все опубликованные вопросы («Вопросы» в меню админки), новые первыми. Карточка: тема вопроса, «Спрашивает» и имя, вопрос (заголовок записи), начало ответа — «Отрывок» или первый абзац ответа (на телефоне — 3 строки), кто ответил (инициалы и имя), дата ответа. Якорь по умолчанию — answers: на него ведёт «Читать ответы» в первом экране.', 'trust' ),
		),
		array(
			'name'          => 'per_page',
			'label'         => __( 'Ответов на странице', 'trust' ),
			'type'          => 'number',
			'min'           => 6,
			'max'           => 40,
			'default_value' => 20,
			'instructions'  => __( 'Остальные — на следующих страницах: адрес/page/2/ и т. д.', 'trust' ),
		),
		Blocks::text_field( 'asks_label', __( 'Перед именем спросившего', 'trust' ), __( 'Спрашивает', 'trust' ), 30 ),
		Blocks::text_field( 'answered_label', __( 'Перед датой ответа', 'trust' ), __( 'ответ от', 'trust' ), 30 ),
		Blocks::text_field( 'more_label', __( 'Ссылка в карточке', 'trust' ), __( 'Читать ответ', 'trust' ), 30 ),
		Blocks::text_field( 'empty_text', __( 'Нет ответов', 'trust' ), __( 'Ответов пока нет. Задайте первый вопрос — специалист ответит здесь.', 'trust' ), 160 ),
	)
);
