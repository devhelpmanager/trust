<?php
/**
 * Превью блока «Результаты» (тексты из макета кейса; графики — в настройках блока).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'eyebrow'   => 'Результат',
	'title'     => 'Что получилось за [N] месяцев',
	'col_after' => 'Через [N] мес.',
	'rows'      => array(
		array(
			'metric' => 'Визиты из поиска в месяц',
			'before' => '[N]',
			'after'  => '[N]',
		),
		array(
			'metric' => 'Запросы в топ-10',
			'before' => '[N]',
			'after'  => '[N]',
		),
		array(
			'metric' => 'Заявки с сайта в месяц',
			'before' => '[N]',
			'after'  => '[N]',
		),
	),
);
