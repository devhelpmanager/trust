<?php
/**
 * Демо-контент: «Настройки сайта». Заглушки и тексты из макетов
 * (holst-portfolio/Header, Footer; holst-o-nas/Main — контакты).
 *
 * Ключ — имя поля настроек, значение — то, что запишет `wp trust demo import`.
 * {page:адрес} в строке заменяется ссылкой на демо-страницу (например, {page:kontakty}).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	// Компания.
	'company_brand'        => '[Бренд]',
	'company_tagline'      => 'SEO · сайты · реклама',

	// Контакты.
	'phones'               => array(
		array(
			'number'  => '+7 (000) 000-00-00',
			'label'   => '',
			'is_main' => 1,
		),
	),
	'emails'               => array(
		array(
			'email' => 'hello@example.ru',
			'label' => 'Для вопросов и заявок',
		),
	),
	'telegram'             => '@[brand]',
	'whatsapp'             => '+7 (000) 000-00-00',
	'hours'                => '[Пн–Пт, 10:00–19:00]',
	'address'              => array(
		'city'        => '[Город]',
		'street'      => '[улица, дом]',
		'office'      => '[этаж, офис]',
		'postal_code' => '',
	),
	'directions_transport' => '[Станция], [N] минут пешком',
	'directions_parking'   => '[Где оставить машину: гостевая парковка, въезд с улицы]',
	'directions_note'      => 'Перед визитом позвоните — закажем пропуск в бизнес-центр',
	'yandex_maps_url'      => 'https://yandex.ru/maps/',
	'gis_url'              => 'https://2gis.ru/',

	// Подвал.
	'footer_description'   => 'Продвигаем сайты в Яндексе, Google и нейросетях, создаём и сопровождаем сайты, настраиваем контекстную рекламу. Открыто пишем, что входит в каждую услугу.',
	'footer_tags'          => array(
		array( 'label' => 'SEO' ),
		array( 'label' => 'GEO' ),
		array( 'label' => 'Сайты' ),
		array( 'label' => 'Контекстная реклама' ),
		array( 'label' => 'AI' ),
	),
	'footer_contacts_link' => array(
		'title'  => 'Контакты',
		'url'    => '{page:kontakty}',
		'target' => '',
	),
	'footer_legal'         => '© {год} [Юридическое название] · ИНН [0000000000] · ОГРН [0000000000000] · [Юридический адрес]',
);
