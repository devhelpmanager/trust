<?php
/**
 * Демо-контент: меню (ТЗ, Приложение Б).
 *
 * Ключ — область меню. Пункт: page — адрес демо-страницы из demo/pages.php или url — своя ссылка;
 * title — название пункта; description — вторая строка в выпадающем списке;
 * classes — CSS-классы пункта (separator-before — разделитель над пунктом).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'header'          => array(
		'name'  => 'Шапка',
		'items' => array(
			array(
				'page'  => 'dizayn',
				'title' => 'Дизайн',
			),
			array(
				'page'  => 'sozdanie-saytov',
				'title' => 'Создание сайтов',
			),
			array(
				'page'  => 'site-support',
				'title' => 'Поддержка сайтов',
			),
			array(
				'page'     => 'seo',
				'title'    => 'SEO',
				'children' => array(
					array(
						'page'        => 'seo/audit',
						'title'       => 'Аудит',
						'description' => 'Технический и SEO-аудит сайта',
					),
					array(
						'page'        => 'seo/pochasovaya-oplata',
						'title'       => 'SEO с почасовой оплатой',
						'description' => 'Оплата за фактически отработанные часы',
					),
					array(
						'page'        => 'seo/za-obem-raboty',
						'title'       => 'За объём работы',
						'description' => 'Фиксированный набор работ в месяц',
					),
					array(
						'page'        => 'seo/voprosy',
						'title'       => 'Задать вопрос SEO-шнику',
						'description' => 'Бесплатный ответ специалиста',
						'classes'     => 'separator-before',
					),
				),
			),
			array(
				'page'     => 'geo',
				'title'    => 'GEO',
				'children' => array(
					array(
						'page'        => 'geo/prodvizhenie-v-ii',
						'title'       => 'Продвижение в ИИ',
						'description' => 'Чтобы нейросети рекомендовали ваш сайт',
					),
					array(
						'page'        => 'geo/audit-vidimosti',
						'title'       => 'Аудит видимости',
						'description' => 'Как ваш бренд видят ChatGPT, Алиса и другие',
					),
				),
			),
			array(
				'page'     => 'kontekstnaya-reklama',
				'title'    => 'Контекстная реклама',
				'children' => array(
					array(
						'page'        => 'kontekstnaya-reklama/yandeks-direkt',
						'title'       => 'Яндекс Директ',
						'description' => 'Настройка и ведение рекламных кампаний',
					),
					array(
						'page'        => 'kontekstnaya-reklama/google-reklama',
						'title'       => 'Google Реклама',
						'description' => 'Поисковые и медийные кампании',
					),
				),
			),
			array(
				'page'  => 'ai',
				'title' => 'AI',
			),
			array(
				'page'  => 'portfolio',
				'title' => 'Портфолио',
			),
			array(
				'page'  => 'blog',
				'title' => 'Блог',
			),
			array(
				'url'      => '#',
				'title'    => 'О нас',
				'children' => array(
					array(
						'page'  => 'kontakty',
						'title' => 'Контакты',
					),
					array(
						'page'  => 'o-kompanii',
						'title' => 'О компании',
					),
					array(
						'page'  => 'komanda',
						'title' => 'Наша команда',
					),
					array(
						'page'  => 'otzyvy',
						'title' => 'Отзывы о нас',
					),
					array(
						'page'  => 'karera',
						'title' => 'Карьера',
					),
					array(
						'page'  => 'oferta',
						'title' => 'Публичная оферта',
					),
				),
			),
		),
	),

	// Подвал: название меню — заголовок колонки.
	'footer-services' => array(
		'name'  => 'Услуги',
		'items' => array(
			array(
				'page'  => 'dizayn',
				'title' => 'Дизайн',
			),
			array(
				'page'  => 'sozdanie-saytov',
				'title' => 'Создание сайтов',
			),
			array(
				'page'  => 'site-support',
				'title' => 'Поддержка сайтов',
			),
			array(
				'page'  => 'seo',
				'title' => 'SEO-продвижение',
			),
			array(
				'page'  => 'seo/voprosy',
				'title' => 'Задать вопрос SEO-шнику',
			),
			array(
				'page'  => 'geo',
				'title' => 'GEO-продвижение',
			),
			array(
				'page'  => 'kontekstnaya-reklama',
				'title' => 'Контекстная реклама',
			),
			array(
				'page'  => 'ai',
				'title' => 'AI-решения',
			),
		),
	),
	'footer-company'  => array(
		'name'  => 'Компания',
		'items' => array(
			array(
				'page'  => 'o-kompanii',
				'title' => 'О компании',
			),
			array(
				'page'  => 'komanda',
				'title' => 'Наша команда',
			),
			array(
				'page'  => 'portfolio',
				'title' => 'Портфолио',
			),
			array(
				'page'  => 'otzyvy',
				'title' => 'Отзывы о нас',
			),
			array(
				'page'  => 'blog',
				'title' => 'Блог',
			),
			array(
				'page'  => 'karera',
				'title' => 'Карьера',
			),
		),
	),
	'footer-docs'     => array(
		'name'  => 'Документы',
		'items' => array(
			array(
				'page'  => 'oferta',
				'title' => 'Публичная оферта',
			),
			array(
				'page'  => 'politika-konfidencialnosti',
				'title' => 'Политика конфиденциальности',
			),
			array(
				'page'  => 'soglasie-na-obrabotku-dannyh',
				'title' => 'Согласие на обработку данных',
			),
			array(
				'page'  => 'politika-cookie',
				'title' => 'Политика cookie',
			),
		),
	),
);
