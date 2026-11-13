<?php
/**
 * Превью «Блока с текстом» в списке блоков: заголовок, абзац и список из макета.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'inner_blocks' => array(
		array(
			'core/heading',
			array(
				'level'   => 2,
				'content' => 'Зачем нужен технический аудит сайта',
			),
		),
		array( 'core/paragraph', array( 'content' => 'Технический аудит — это проверка сайта на ошибки, которые мешают поисковым системам находить, сканировать и индексировать страницы.' ) ),
		array(
			'core/quote',
			array(
				'className' => 'is-style-important',
				'value'     => '<p>Одна строка в robots.txt может перечеркнуть месяцы работы над контентом.</p>',
			),
		),
	),
);
