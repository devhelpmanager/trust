<?php
/**
 * Демо-кейсы: 9 карточек из макета portfolio-blog-404/portfolio.html (заголовок, анонс,
 * два результата, услуги, отрасль), данные первого экрана и карточки проекта из keys.html
 * (кейс «Ремонт квартир»), позиции из seo-bloki/12-proekty-v-tope.html, «чем занимается
 * клиент» — из «Проектов в топе» и карусели кейсов (seo-bloki/14-karusel-keysov.html).
 *
 * Поле terms — ключи демо-терминов из demo/terms.php; {post:review:ключ} — ID демо-отзыва.
 * Обложки и скриншоты добавятся вместе с картинками в демо-контенте (шаг 4.16);
 * команда проекта — {post:team:ключ} из demo/team.php.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

/**
 * Два результата карточки.
 *
 * @param string $v1 Значение 1.
 * @param string $l1 Подпись 1.
 * @param string $v2 Значение 2.
 * @param string $l2 Подпись 2.
 */
$trust_results = static fn( string $v1, string $l1, string $v2, string $l2 ): array => array(
	array(
		'value' => $v1,
		'label' => $l1,
	),
	array(
		'value' => $v2,
		'label' => $l2,
	),
);

/**
 * Позиции в поиске: строки «запрос, Яндекс, Google».
 *
 * @param array $rows Строки.
 */
$trust_positions = static fn( array $rows ): array => array_map(
	static fn( array $row ): array => array(
		'query'  => $row[0],
		'yandex' => $row[1],
		'google' => $row[2],
	),
	$rows
);

return array(
	array(
		'key'     => 'repair',
		'title'   => 'Новый сайт и SEO для компании по ремонту квартир',
		// В хлебных крошках макета кейса — короткое название.
		'seo'     => array( 'trust_crumb_title' => 'Ремонт квартир под ключ' ),
		'excerpt' => 'Перенесли сайт с конструктора и собрали структуру под все виды ремонта.',
		'terms'   => array(
			'service_type' => array( 'seo', 'sites' ),
			'industry'     => array( 'repair' ),
		),
		'fields'  => array(
			'trust_featured'         => 1,
			'trust_client'           => '[Ремонт&Дизайн]',
			'trust_niche'            => 'Ремонт квартир',
			'trust_region'           => '[Город]',
			'trust_start'            => '20260101',
			'trust_end'              => '20260630',
			'trust_lead'             => '[Ремонт&Дизайн] делает ремонт квартир под ключ в [город]. Сайт на конструкторе почти не приводил заявок из поиска. За [N] месяцев сделали новый сайт и вывели его в топ-10 по основным запросам.',
			'trust_results'          => array(
				array(
					'value' => '+[N]%',
					'label' => 'визитов из поиска',
				),
				array(
					'value' => '[N] из [N]',
					'label' => 'запросов в топ-10',
				),
				array(
					'value' => '×[N]',
					'label' => 'заявок с сайта',
				),
				array(
					'value' => '[N] мес.',
					'label' => 'срок работ',
				),
			),
			'trust_positions'        => $trust_positions(
				array(
					array( 'ремонт квартир под ключ', 1, 1 ),
					array( 'дизайн-проект квартиры', 4, 3 ),
					array( 'ремонт ванной комнаты', 3, 5 ),
					array( 'ремонт кухни цена', 8, 6 ),
				)
			),
			'trust_positions_region' => '[Регион]',
			'trust_team'             => array(
				array(
					'member' => '{post:team:seo-specialist}',
					'role'   => 'SEO-специалист, руководитель проекта',
				),
				array(
					'member' => '{post:team:designer}',
					'role'   => 'Веб-дизайнер',
				),
				array(
					'member' => '{post:team:frontend}',
					'role'   => '',
				),
			),
			'trust_review'           => '{post:review:case-repair}',
		),
	),
	array(
		'key'     => 'dental',
		'title'   => 'Локальное SEO и карты для стоматологии',
		'excerpt' => 'Оптимизировали страницы услуг и врачей, настроили карточки в Яндекс Картах и 2ГИС.',
		'terms'   => array(
			'service_type' => array( 'seo', 'geo' ),
			'industry'     => array( 'med' ),
		),
		'fields'  => array(
			'trust_client'           => '[Название проекта]',
			'trust_niche'            => 'Стоматологическая клиника',
			'trust_results'          => $trust_results( '+[N]%', 'записей с сайта', 'Топ-[N]', 'в картах по району' ),
			'trust_positions'        => $trust_positions(
				array(
					array( 'имплантация зубов цена', 3, 4 ),
					array( 'виниры на зубы', 2, 1 ),
					array( 'стоматология рядом', 5, 3 ),
					array( 'лечение кариеса', 6, 8 ),
				)
			),
			'trust_positions_region' => '[Регион]',
		),
	),
	array(
		'key'     => 'furniture',
		'title'   => 'Интернет-магазин мебели с нуля и запуск рекламы',
		'excerpt' => 'Сделали каталог с фильтрами и быстрым заказом, запустили товарную рекламу в Директе.',
		'terms'   => array(
			'service_type' => array( 'sites', 'ppc' ),
			'industry'     => array( 'shop' ),
		),
		'fields'  => array(
			'trust_client'           => '[Название проекта]',
			'trust_niche'            => 'Интернет-магазин мебели',
			'trust_results'          => $trust_results( '[N]', 'заказов в первый месяц', '[N] ₽', 'стоимость заказа' ),
			'trust_positions'        => $trust_positions(
				array(
					array( 'купить диван-кровать', 2, 3 ),
					array( 'угловой диван недорого', 4, 6 ),
					array( 'диван с ортопедическим матрасом', 1, 2 ),
					array( 'мебель для гостиной', 7, 5 ),
				)
			),
			'trust_positions_region' => '[Регион]',
		),
	),
	array(
		'key'     => 'auto',
		'title'   => 'Контекстная реклама для автосервиса',
		'excerpt' => 'Пересобрали кампании по видам работ и районам, отключили нецелевые запросы.',
		'terms'   => array(
			'service_type' => array( 'ppc' ),
			'industry'     => array( 'auto' ),
		),
		'fields'  => array(
			'trust_client'  => '[Название проекта]',
			'trust_niche'   => 'Автосервис',
			'trust_results' => $trust_results( '−[N]%', 'цена заявки', '[N]', 'заявок в месяц' ),
		),
	),
	array(
		'key'     => 'law',
		'title'   => 'SEO и GEO для юридической компании',
		'excerpt' => 'Подготовили страницы практик и экспертные статьи, на которые ссылаются нейросети.',
		'terms'   => array(
			'service_type' => array( 'seo', 'geo' ),
			'industry'     => array( 'law' ),
		),
		'fields'  => array(
			'trust_client'  => '[Название проекта]',
			'trust_niche'   => 'Юридическая компания',
			'trust_results' => $trust_results( '+[N]%', 'трафика из поиска', '[N]', 'упоминаний в ответах ИИ' ),
		),
	),
	array(
		'key'     => 'school',
		'title'   => 'Лендинг и реклама для онлайн-школы английского',
		'excerpt' => 'Сделали страницу курса и настроили рекламу на запись на пробный урок.',
		'terms'   => array(
			'service_type' => array( 'sites', 'ppc' ),
			'industry'     => array( 'edu' ),
		),
		'fields'  => array(
			'trust_client'  => '[Название проекта]',
			'trust_niche'   => 'Онлайн-школа английского',
			'trust_results' => $trust_results( '[N]%', 'конверсия в заявку', '[N] ₽', 'цена заявки' ),
		),
	),
	array(
		'key'     => 'factory',
		'title'   => 'Каталог продукции для завода металлоконструкций',
		'excerpt' => 'Перевели прайс в каталог с карточками продукции и формой отправки чертежа.',
		'terms'   => array(
			'service_type' => array( 'sites', 'seo' ),
			'industry'     => array( 'prod' ),
		),
		'fields'  => array(
			'trust_client'  => '[Название проекта]',
			'trust_niche'   => 'Завод металлоконструкций',
			'trust_results' => $trust_results( '+[N]%', 'запросов на расчёт', '[N]', 'страниц в индексе' ),
		),
	),
	array(
		'key'     => 'beauty',
		'title'   => 'Сопровождение сайта студии красоты',
		'excerpt' => 'Обновляем прайс и мастеров, подключили онлайн-запись и следим за скоростью сайта.',
		'terms'   => array(
			'service_type' => array( 'support', 'seo' ),
			'industry'     => array( 'beauty' ),
		),
		'fields'  => array(
			'trust_client'  => '[Название проекта]',
			'trust_niche'   => 'Студия красоты',
			'trust_results' => $trust_results( '[N]', 'задач в месяц', '[N] с', 'загрузка страницы' ),
		),
	),
	array(
		'key'     => 'flowers',
		'title'   => 'SEO для цветочного интернет-магазина',
		'excerpt' => 'Создали страницы под поводы и районы доставки, ускорили сайт перед праздниками.',
		'terms'   => array(
			'service_type' => array( 'seo', 'support' ),
			'industry'     => array( 'shop' ),
		),
		'fields'  => array(
			'trust_client'  => '[Название проекта]',
			'trust_niche'   => 'Цветочный интернет-магазин',
			'trust_results' => $trust_results( '+[N]%', 'заказов из поиска', '[N]', 'запросов в топ-10' ),
		),
	),
);
