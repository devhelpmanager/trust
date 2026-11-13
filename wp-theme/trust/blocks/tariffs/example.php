<?php
/**
 * Превью блока «Выбор тарифа» в списке блоков (тексты из макета).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_items = static fn( array $rows ): array => array_map(
	static fn( $row ) => array(
		'text'     => $row[0],
		'included' => $row[1],
	),
	$rows
);

return array(
	'eyebrow' => 'Тарифы',
	'title'   => 'Выберите тариф',
	'tariffs' => array(
		array(
			'name'   => 'Старт',
			'who'    => 'Для небольших сайтов и локального бизнеса.',
			'price'  => 'XX XXX ₽',
			'period' => '/ месяц',
			'items'  => $trust_items( array( array( 'Технический аудит сайта', 1 ), array( 'Внутренняя перелинковка', 0 ) ) ),
		),
		array(
			'name'        => 'Бизнес',
			'recommended' => 1,
			'who'         => 'Для компаний, которым нужен поток заявок из поиска.',
			'price'       => 'XX XXX ₽',
			'period'      => '/ месяц',
			'items'       => $trust_items( array( array( 'Технический аудит сайта', 1 ), array( 'Внутренняя перелинковка', 1 ) ) ),
		),
	),
);
