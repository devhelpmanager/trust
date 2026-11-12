<?php
/**
 * Превью блока «Первый экран раздела» в списке блоков (тексты из макета «Карьера»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'badge'   => 'Карьера',
	'lead'    => 'Растим специалистов внутри команды: наставник с первого дня, понятные грейды и реальные проекты.',
	'buttons' => array(
		array(
			'link' => array(
				'title'  => 'Смотреть вакансии',
				'url'    => '#vacancies',
				'target' => '',
			),
			'icon' => 'down',
		),
		array(
			'link' => array(
				'title'  => 'Отправить резюме',
				'url'    => '#resume',
				'target' => '',
			),
			'icon' => '',
		),
	),
	'stats'   => array(
		array(
			'value' => '[N]',
			'label' => 'человек в команде',
		),
		array(
			'value' => '{vacancies}',
			'label' => 'открытых вакансий',
		),
		array(
			'value' => '[N]',
			'label' => 'стажёров перешли в штат',
		),
		array(
			'value' => '[N] года',
			'label' => 'средний стаж в компании',
		),
	),
);
