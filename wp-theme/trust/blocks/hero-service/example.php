<?php
/**
 * Превью блока «Первый экран услуги» в списке блоков (тексты из макета).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'lead'    => 'Найдём ошибки, которые мешают сайту расти в Яндексе и Google, и дадим пошаговый план исправлений.',
	'buttons' => array(
		array(
			'link' => array(
				'title'  => 'Заказать аудит',
				'url'    => '#callback-modal',
				'target' => '',
			),
		),
		array(
			'link' => array(
				'title'  => 'Рассчитать стоимость',
				'url'    => '#calculator',
				'target' => '',
			),
		),
	),
);
