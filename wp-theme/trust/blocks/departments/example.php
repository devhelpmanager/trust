<?php
/**
 * Превью блока «Кому написать» (тексты из макета).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'eyebrow'     => 'Отделы',
	'title'       => 'Кому написать',
	'lead'        => 'Напишите сразу в нужный отдел — так ответ придёт быстрее.',
	'items'       => array(
		array(
			'icon'  => 'trend-up',
			'title' => 'Новые проекты',
			'text'  => 'Расчёт стоимости, коммерческие предложения и брифы',
			'email' => 'sales@example.ru',
		),
		array(
			'icon'  => 'support',
			'title' => 'Текущим клиентам',
			'text'  => 'Вопросы по проекту, отчётам и доступам — или напишите своему менеджеру',
			'email' => 'support@example.ru',
		),
	),
	'leader_card' => 1,
);
