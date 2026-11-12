<?php
/**
 * Превью блока «Что включает услуга» в списке блоков (тексты из макета).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'eyebrow'      => 'Состав услуги',
	'title'        => 'Что включает технический аудит',
	'lead'         => 'Нажмите на работу, чтобы узнать подробности.',
	'checks_label' => 'Что проверяем',
	'more_label'   => 'Подробнее об услуге',
	'items'        => array(
		array(
			'icon'        => 'robot',
			'title'       => 'Проверка robots.txt',
			'description' => 'Файл robots.txt подсказывает поисковым роботам, какие разделы сайта сканировать, а какие нет.',
			'price'       => 'от X XXX ₽',
			'link'        => array(
				'title'  => '',
				'url'    => '#robots',
				'target' => '',
			),
			'checks'      => array(
				array( 'text' => 'Синтаксис и корректность директив' ),
				array( 'text' => 'Важные разделы, случайно закрытые от индексации' ),
			),
		),
		array(
			'icon'        => 'sitemap',
			'title'       => 'Анализ карты сайта',
			'description' => 'Карта сайта sitemap.xml помогает поисковым системам быстрее находить новые страницы.',
			'price'       => 'от X XXX ₽',
			'checks'      => array(
				array( 'text' => 'Наличие и доступность файла' ),
			),
		),
	),
);
