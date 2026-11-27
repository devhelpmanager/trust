<?php
/**
 * Превью блока «Как бы поступил я» (тексты из макета ответа).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'        => 'Как бы поступил я',
	'note'         => 'Хотите проверить весь сайт, а не одну проблему? Это задача для технического аудита.',
	'button'       => array(
		'title'  => 'Заказать технический аудит',
		'url'    => '#',
		'target' => '',
	),
	'inner_blocks' => array(
		array(
			'core/list',
			array( 'ordered' => true ),
			array(
				array( 'core/list-item', array( 'content' => 'Нашёл бы новые карточки среди исключённых страниц и выписал причины' ) ),
				array( 'core/list-item', array( 'content' => 'Убрал бы технические запреты и дубли' ) ),
			),
		),
	),
);
