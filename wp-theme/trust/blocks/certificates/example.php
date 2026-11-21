<?php
/**
 * Превью блока «Сертификаты и рейтинги» (тексты из макета).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'eyebrow' => 'Подтверждения',
	'title'   => 'Сертификаты и рейтинги',
	'lead'    => 'Каждый документ можно открыть и проверить.',
	'items'   => array(
		array(
			'title' => 'Сертификат агентства Яндекс Директа',
			'year'  => '2025',
			'url'   => 'https://example.com/',
		),
		array(
			'title' => 'Сертификат Google Ads',
			'year'  => '2025',
			'url'   => 'https://example.com/',
		),
		array(
			'title'      => 'Место в рейтинге Рунета',
			'year'       => '2024',
			'url'        => 'https://example.com/',
			'link_label' => 'смотреть диплом',
		),
	),
);
