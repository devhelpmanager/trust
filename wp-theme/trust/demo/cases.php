<?php
/**
 * Демо-кейсы: 9 карточек из макета portfolio-blog-404/portfolio.html (заголовок, анонс,
 * два результата, услуги, отрасль), данные первого экрана и карточки проекта из keys.html
 * (кейс «Ремонт квартир»), позиции из seo-bloki/12-proekty-v-tope.html, «чем занимается
 * клиент» — из «Проектов в топе» и карусели кейсов (seo-bloki/14-karusel-keysov.html).
 *
 * Поле terms — ключи демо-терминов из demo/terms.php; {post:review:ключ} — ID демо-отзыва.
 * Скриншоты сайтов трёх кейсов с позициями — из seo-bloki/images (шаг 4.7), обложки — на шаге 4.16;
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

// Разделы кейса «Ремонт квартир»: «Слово клиенту» и «Кто работал над проектом» берут отзыв и команду
// из полей кейса.
$trust_chart_note    = 'Демонстрационный график. [Замените скриншотом из Яндекс Метрики или Вебмастера клиента.]';
$trust_repair_blocks = array(
	array(
		'case-task',
		array(
			'eyebrow'  => 'Задача',
			'title'    => 'С чем пришёл клиент',
			'text'     => '<p>[Описание клиента и запроса: чем занимается компания, кто её клиенты, какую задачу поставили — 2–3 предложения.]</p>',
			'problems' => array(
				array( 'text' => 'Сайт на конструкторе: нельзя менять структуру и технические настройки.' ),
				array( 'text' => 'Заявки приходили только с рекламы — из поиска почти никого.' ),
				array( 'text' => 'Одна страница на все услуги, без цен и примеров работ.' ),
			),
		),
	),
	array(
		'case-obstacles',
		array(
			'eyebrow' => 'Аудит',
			'title'   => 'Что мешало росту',
			'cards'   => array(
				array(
					'number' => '[N]',
					'label'  => 'важных запросов без своей страницы на сайте',
				),
				array(
					'number' => '[N]',
					'label'  => 'ошибок в Вебмастере: дубли и битые ссылки',
				),
				array(
					'number' => '[N] с',
					'label'  => 'загружалась мобильная версия',
				),
				array(
					'number' => '0',
					'label'  => 'целей в Метрике — заявки не отслеживались',
				),
			),
		),
	),
	array(
		'case-work',
		array(
			'eyebrow' => 'Работы',
			'title'   => 'Что сделали',
			'steps'   => array(
				array(
					'term'  => 'Месяц 1',
					'title' => 'Аудит и семантика',
					'text'  => 'Собрали [N] запросов, разбили их на [N] групп и спроектировали структуру: отдельная страница под каждый вид ремонта.',
				),
				array(
					'term'  => 'Месяцы 2–3',
					'title' => 'Новый сайт',
					'text'  => 'Дизайн и разработка [N] страниц услуг, калькулятор стоимости ремонта и портфолио с фотографиями объектов.',
				),
				array(
					'term'  => 'Месяц 4',
					'title' => 'Переезд без потери позиций',
					'text'  => 'Настроили 301-редиректы со старых адресов, карту сайта и микроразметку, ускорили мобильную версию.',
				),
				array(
					'term'  => 'Месяцы 4–6',
					'title' => 'Контент и доверие',
					'text'  => 'Написали тексты для страниц услуг, добавили цены, отзывы и примеры работ, оформили карточки в Яндекс Картах и 2ГИС.',
				),
				array(
					'term'  => 'Каждый месяц',
					'title' => 'Аналитика и отчёты',
					'text'  => 'Настроили цели и учёт звонков в Метрике. После каждого этапа — отчёт и созвон с клиентом.',
				),
			),
		),
	),
	array(
		'case-results',
		array(
			'eyebrow'   => 'Результат',
			'title'     => 'Что получилось за [N] месяцев',
			'tabs'      => array(
				array(
					'label'   => 'Визиты из поиска',
					'image'   => '{image:case-traffic.jpg|График визитов из поисковых систем по месяцам}',
					'alt'     => 'График визитов из поисковых систем по месяцам',
					'caption' => $trust_chart_note,
				),
				array(
					'label'   => 'Позиции',
					'image'   => '{image:case-positions.jpg|Таблица позиций по ключевым запросам}',
					'alt'     => 'Таблица позиций по ключевым запросам',
					'caption' => $trust_chart_note,
				),
				array(
					'label'   => 'Заявки',
					'image'   => '{image:case-leads.jpg|График заявок с сайта по месяцам}',
					'alt'     => 'График заявок с сайта по месяцам',
					'caption' => $trust_chart_note,
				),
				array(
					'label'   => 'Видимость',
					'image'   => '{image:case-visibility.jpg|График видимости сайта в поиске}',
					'alt'     => 'График видимости сайта в поиске',
					'caption' => $trust_chart_note,
				),
			),
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
				array(
					'metric' => 'Доля трафика из поиска',
					'before' => '[N]%',
					'after'  => '[N]%',
				),
			),
		),
	),
	array( 'case-quote', array() ),
	array( 'case-team', array() ),
);

return array(
	array(
		'key'       => 'repair',
		'thumbnail' => 'project-repair.jpg',
		'title'     => 'Новый сайт и SEO для компании по ремонту квартир',
		// В хлебных крошках макета кейса — короткое название.
		'seo'       => array( 'trust_crumb_title' => 'Ремонт квартир под ключ' ),
		// Главный кейс портфолио: анонс — из большой карточки макета.
		'excerpt'   => 'Перенесли сайт с конструктора, собрали структуру под все виды ремонта и вывели ключевые запросы в топ-10 Яндекса и Google.',
		'terms'     => array(
			'service_type' => array( 'seo', 'sites' ),
			'industry'     => array( 'repair' ),
		),
		'fields'    => array(
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
			'trust_positions_date'   => '20261101',
			'trust_screenshot'       => '{image:project-repair.jpg|Главная страница сайта компании по ремонту квартир}',
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
		// Содержимое страницы кейса — шесть разделов из макета portfolio-blog-404/keys.html (шаг 4.17).
		'blocks'    => $trust_repair_blocks,
	),
	array(
		'key'       => 'dental',
		'thumbnail' => 'project-dental.jpg',
		'title'     => 'Локальное SEO и карты для стоматологии',
		'excerpt'   => 'Оптимизировали страницы услуг и врачей, настроили карточки в Яндекс Картах и 2ГИС.',
		'terms'     => array(
			'service_type' => array( 'seo', 'geo' ),
			'industry'     => array( 'med' ),
		),
		'fields'    => array(
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
			'trust_positions_date'   => '20261101',
			'trust_screenshot'       => '{image:project-dental.jpg|Главная страница сайта стоматологической клиники}',
			'trust_review'           => '{post:review:case-dental}',
		),
	),
	array(
		'key'       => 'furniture',
		'thumbnail' => 'project-shop.jpg',
		'title'     => 'Интернет-магазин мебели с нуля и запуск рекламы',
		'excerpt'   => 'Сделали каталог с фильтрами и быстрым заказом, запустили товарную рекламу в Директе.',
		'terms'     => array(
			'service_type' => array( 'sites', 'ppc' ),
			'industry'     => array( 'shop' ),
		),
		'fields'    => array(
			'trust_client'           => '[Название проекта]',
			'trust_niche'            => 'Интернет-магазин мебели',
			'trust_results'          => $trust_results( '[N]', 'заказов в первый месяц', '[N] $', 'стоимость заказа' ),
			'trust_positions'        => $trust_positions(
				array(
					array( 'купить диван-кровать', 2, 3 ),
					array( 'угловой диван недорого', 4, 6 ),
					array( 'диван с ортопедическим матрасом', 1, 2 ),
					array( 'мебель для гостиной', 7, 5 ),
				)
			),
			'trust_positions_region' => '[Регион]',
			'trust_positions_date'   => '20261101',
			'trust_screenshot'       => '{image:project-shop.jpg|Главная страница сайта интернет-магазина мебели}',
		),
	),
	array(
		'key'       => 'auto',
		'thumbnail' => 'project-auto.jpg',
		'title'     => 'Контекстная реклама для автосервиса',
		'excerpt'   => 'Пересобрали кампании по видам работ и районам, отключили нецелевые запросы.',
		'terms'     => array(
			'service_type' => array( 'ppc' ),
			'industry'     => array( 'auto' ),
		),
		'fields'    => array(
			'trust_client'  => '[Название проекта]',
			'trust_niche'   => 'Автосервис',
			'trust_results' => $trust_results( '−[N]%', 'цена заявки', '[N]', 'заявок в месяц' ),
		),
	),
	array(
		'key'       => 'law',
		'thumbnail' => 'project-law.jpg',
		'title'     => 'SEO и GEO для юридической компании',
		'excerpt'   => 'Подготовили страницы практик и экспертные статьи, на которые ссылаются нейросети.',
		'terms'     => array(
			'service_type' => array( 'seo', 'geo' ),
			'industry'     => array( 'law' ),
		),
		'fields'    => array(
			'trust_client'  => '[Название проекта]',
			'trust_niche'   => 'Юридическая компания',
			'trust_results' => $trust_results( '+[N]%', 'трафика из поиска', '[N]', 'упоминаний в ответах ИИ' ),
		),
	),
	array(
		'key'       => 'school',
		'thumbnail' => 'project-school.jpg',
		'title'     => 'Лендинг и реклама для онлайн-школы английского',
		'excerpt'   => 'Сделали страницу курса и настроили рекламу на запись на пробный урок.',
		'terms'     => array(
			'service_type' => array( 'sites', 'ppc' ),
			'industry'     => array( 'edu' ),
		),
		'fields'    => array(
			'trust_client'  => '[Название проекта]',
			'trust_niche'   => 'Онлайн-школа английского',
			'trust_results' => $trust_results( '[N]%', 'конверсия в заявку', '[N] $', 'цена заявки' ),
		),
	),
	array(
		'key'       => 'factory',
		'thumbnail' => 'project-factory.jpg',
		'title'     => 'Каталог продукции для завода металлоконструкций',
		'excerpt'   => 'Перевели прайс в каталог с карточками продукции и формой отправки чертежа.',
		'terms'     => array(
			'service_type' => array( 'sites', 'seo' ),
			'industry'     => array( 'prod' ),
		),
		'fields'    => array(
			'trust_client'  => '[Название проекта]',
			'trust_niche'   => 'Завод металлоконструкций',
			'trust_results' => $trust_results( '+[N]%', 'запросов на расчёт', '[N]', 'страниц в индексе' ),
		),
	),
	array(
		'key'       => 'beauty',
		'thumbnail' => 'project-beauty.jpg',
		'title'     => 'Сопровождение сайта студии красоты',
		'excerpt'   => 'Обновляем прайс и мастеров, подключили онлайн-запись и следим за скоростью сайта.',
		'terms'     => array(
			'service_type' => array( 'support', 'seo' ),
			'industry'     => array( 'beauty' ),
		),
		'fields'    => array(
			'trust_client'  => '[Название проекта]',
			'trust_niche'   => 'Студия красоты',
			'trust_results' => $trust_results( '[N]', 'задач в месяц', '[N] с', 'загрузка страницы' ),
		),
	),
	array(
		'key'       => 'flowers',
		'thumbnail' => 'project-flowers.jpg',
		'title'     => 'SEO для цветочного интернет-магазина',
		'excerpt'   => 'Создали страницы под поводы и районы доставки, ускорили сайт перед праздниками.',
		'terms'     => array(
			'service_type' => array( 'seo', 'support' ),
			'industry'     => array( 'shop' ),
		),
		'fields'    => array(
			'trust_client'  => '[Название проекта]',
			'trust_niche'   => 'Цветочный интернет-магазин',
			'trust_results' => $trust_results( '+[N]%', 'заказов из поиска', '[N]', 'запросов в топ-10' ),
		),
	),
);
