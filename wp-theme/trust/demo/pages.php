<?php
/**
 * Демо-контент: страницы сайта из меню (ТЗ, Приложение Б).
 *
 * Поля: path — адрес страницы (родитель/ярлык), title — заголовок. Страницы пока пустые:
 * блоки в них добавляются на шагах, где появляются эти блоки и типы страниц.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	// Услуги.
	array(
		'path'  => 'dizayn',
		'title' => 'Дизайн',
	),
	array(
		'path'  => 'sozdanie-saytov',
		'title' => 'Создание сайтов',
	),
	array(
		'path'  => 'soprovozhdenie-saytov',
		'title' => 'Сопровождение сайтов',
	),
	array(
		'path'  => 'seo',
		'title' => 'SEO-продвижение',
	),
	array(
		'path'  => 'seo/audit',
		'title' => 'Технический аудит сайта',
	),
	array(
		'path'  => 'seo/pochasovaya-oplata',
		'title' => 'SEO с почасовой оплатой',
	),
	array(
		'path'  => 'seo/za-obem-raboty',
		'title' => 'SEO за объём работы',
	),
	array(
		'path'  => 'seo/voprosy',
		'title' => 'Задать вопрос SEO-шнику',
	),
	array(
		'path'  => 'geo',
		'title' => 'GEO-продвижение',
	),
	array(
		'path'  => 'geo/prodvizhenie-v-ii',
		'title' => 'Продвижение в ИИ',
	),
	array(
		'path'  => 'geo/audit-vidimosti',
		'title' => 'Аудит видимости',
	),
	array(
		'path'  => 'kontekstnaya-reklama',
		'title' => 'Контекстная реклама',
	),
	array(
		'path'  => 'kontekstnaya-reklama/yandeks-direkt',
		'title' => 'Яндекс Директ',
	),
	array(
		'path'  => 'kontekstnaya-reklama/google-reklama',
		'title' => 'Google Реклама',
	),
	array(
		'path'  => 'ai',
		'title' => 'AI-решения',
	),

	// Разделы.
	array(
		'path'  => 'portfolio',
		'title' => 'Портфолио',
	),
	array(
		'path'  => 'blog',
		'title' => 'Блог',
	),

	// О нас.
	array(
		'path'  => 'kontakty',
		'title' => 'Контакты',
	),
	array(
		'path'  => 'o-kompanii',
		'title' => 'О компании',
	),
	array(
		'path'  => 'komanda',
		'title' => 'Наша команда',
	),
	array(
		'path'  => 'otzyvy',
		'title' => 'Отзывы о нас',
	),
	array(
		'path'  => 'karera',
		'title' => 'Карьера',
	),
	array(
		'path'  => 'oferta',
		'title' => 'Публичная оферта',
	),
);
