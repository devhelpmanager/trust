<?php
/**
 * Группа «Документ» — у страниц типа «Документ» (ТЗ, раздел 6.2): нумерация разделов и пунктов
 * и как страница выглядит в блоке «Другие документы».
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Icons;

return array(
	'title'      => __( 'Документ', 'trust' ),
	'locations'  => array(
		array( 'page_template', 'templates/document.php' ),
	),
	'position'   => 'side',
	'menu_order' => 10,
	'fields'     => array(
		array(
			'name'          => 'trust_doc_numbering',
			'label'         => __( 'Нумерация разделов и пунктов', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'instructions'  => __( 'Номера ставятся сами: у заголовков H2 — «1.», «2.», у пунктов списка со стилем «Пункты документа» — «1.1.», «1.2.». Не пишите номера в тексте.', 'trust' ),
		),
		Icons::field(
			array(
				'name'          => 'trust_doc_icon',
				'label'         => __( 'Значок в «Других документах»', 'trust' ),
				'default_value' => 'document',
				'choices'       => array(
					'document'       => __( 'Документ', 'trust' ),
					'document-check' => __( 'Документ с галочкой — согласие', 'trust' ),
					'shield-outline' => __( 'Щит — конфиденциальность', 'trust' ),
					'cookie-dots'    => __( 'Cookie', 'trust' ),
					'globe-half'     => __( 'Публикация — распространение данных', 'trust' ),
				),
			)
		),
		array(
			'name'         => 'trust_doc_short',
			'label'        => __( 'Название в «Других документах»', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 80,
			'instructions' => __( 'Пусто — заголовок страницы.', 'trust' ),
		),
		array(
			'name'         => 'trust_doc_note',
			'label'        => __( 'Подпись в «Других документах»', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 80,
			'instructions' => __( 'Необязательно, мелким шрифтом под названием: например, «для публикации отзывов и вопросов».', 'trust' ),
		),
	),
);
