<?php
/**
 * Демо-контент: страницы сайта из меню (ТЗ, Приложение Б).
 *
 * Поля: path — адрес страницы (родитель/ярлык), title — заголовок, type — тип страницы
 * (Trust\Page_Types: шаблон; нет — «Обычная страница»), blocks — блоки темы по порядку:
 * array( 'имя блока', array( значения полей ) ); {page:адрес} в значениях — ссылка на страницу;
 * seo — поля группы «SEO»: H1 и title, если в макете они отличаются от заголовка страницы,
 * название и раздел в хлебных крошках.
 * Блоки добавляются на шагах, где они появляются.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

// «Раздел в хлебных крошках» страниц раздела «О нас» (в макетах — «Главная / О нас / …»).
$trust_about = array(
	'title'  => 'О нас',
	'url'    => '{page:o-kompanii}',
	'target' => '',
);

return array(
	// Услуги.
	array(
		'path'  => 'dizayn',
		'title' => 'Дизайн',
		'type'  => 'service',
	),
	array(
		'path'  => 'sozdanie-saytov',
		'title' => 'Создание сайтов',
		'type'  => 'service',
	),
	array(
		'path'  => 'soprovozhdenie-saytov',
		'title' => 'Сопровождение сайтов',
		'type'  => 'service',
	),
	array(
		'path'  => 'seo',
		'title' => 'SEO-продвижение',
		'type'  => 'service',
		'seo'   => array(
			'trust_crumb_title' => 'SEO',
		),
	),
	array(
		'path'   => 'seo/audit',
		'title'  => 'Технический аудит сайта',
		'type'   => 'service',
		'seo'    => array(
			'trust_crumb_title' => 'Технический аудит',
		),
		// Блоки страницы услуги (holst-stranitsy/Main) — по мере готовности блоков.
		'blocks' => array(
			array(
				'faq',
				array(
					'eyebrow'   => 'Вопрос — ответ',
					'title'     => 'Частые вопросы',
					'lead'      => 'Собрали ответы на вопросы, которые клиенты задают чаще всего.',
					'more_link' => array(
						'title'  => 'Не нашли ответ? Задайте вопрос',
						'url'    => '{page:seo/voprosy}',
						'target' => '',
					),
					'questions' => array(
						array(
							'question' => 'Сколько времени занимает технический аудит?',
							'answer'   => '<p>Срок зависит от количества страниц и сложности сайта. Точные даты мы называем в коммерческом предложении и фиксируем в договоре до начала работ.</p>',
						),
						array(
							'question' => 'Какие доступы вам понадобятся?',
							'answer'   => '<p>Для аудита достаточно гостевого доступа к Яндекс Вебмастеру, Google Search Console и системе аналитики. Доступ к админке нужен, только если вы хотите, чтобы исправления внесли мы.</p>',
						),
						array(
							'question' => 'Вы сами исправляете найденные ошибки?',
							'answer'   => '<p>Аудит — это диагностика и рекомендации. Исправления можем взять на себя отдельной услугой или передать отчёт вашему разработчику: он написан так, чтобы по нему можно было работать без нас.</p>',
						),
						array(
							'question' => 'В каком виде я получу отчёт?',
							'answer'   => '<p>Это документ со списком найденных ошибок, их приоритетом и пошаговыми рекомендациями по исправлению. После сдачи разбираем отчёт вместе и отвечаем на вопросы.</p>',
						),
						array(
							'question' => 'Можно заказать только одну проверку из списка?',
							'answer'   => '<p>Да. Каждая работа из состава аудита продаётся отдельно — например, только проверка скорости загрузки или анализ индексации.</p>',
						),
						array(
							'question' => 'Гарантируете ли вы рост позиций?',
							'answer'   => '<p>Аудит находит технические причины, которые мешают росту. Но позиции зависят и от других факторов — контента, конкурентов, ссылок, — поэтому честно гарантировать конкретные места в выдаче не может никто.</p>',
						),
					),
				),
			),
		),
	),
	array(
		'path'  => 'seo/pochasovaya-oplata',
		'title' => 'SEO с почасовой оплатой',
		'type'  => 'service',
	),
	array(
		'path'  => 'seo/za-obem-raboty',
		'title' => 'SEO за объём работы',
		'type'  => 'service',
	),
	array(
		'path'  => 'seo/voprosy',
		'title' => 'Задать вопрос SEO-шнику',
		'type'  => 'questions',
		'seo'   => array(
			'trust_crumb_title' => 'Вопросы по SEO',
		),
	),
	array(
		'path'  => 'geo',
		'title' => 'GEO-продвижение',
		'type'  => 'service',
		'seo'   => array(
			'trust_crumb_title' => 'GEO',
		),
	),
	array(
		'path'  => 'geo/prodvizhenie-v-ii',
		'title' => 'Продвижение в ИИ',
		'type'  => 'service',
	),
	array(
		'path'  => 'geo/audit-vidimosti',
		'title' => 'Аудит видимости',
		'type'  => 'service',
	),
	array(
		'path'  => 'kontekstnaya-reklama',
		'title' => 'Контекстная реклама',
		'type'  => 'service',
	),
	array(
		'path'  => 'kontekstnaya-reklama/yandeks-direkt',
		'title' => 'Яндекс Директ',
		'type'  => 'service',
	),
	array(
		'path'  => 'kontekstnaya-reklama/google-reklama',
		'title' => 'Google Реклама',
		'type'  => 'service',
	),
	array(
		'path'  => 'ai',
		'title' => 'AI-решения',
		'type'  => 'service',
	),

	// Разделы.
	array(
		'path'  => 'portfolio',
		'title' => 'Портфолио',
		'type'  => 'portfolio',
		'seo'   => array(
			'trust_seo_h1' => 'Кейсы и проекты',
		),
	),
	array(
		'path'  => 'blog',
		'title' => 'Блог',
	),

	// О нас.
	array(
		'path'  => 'kontakty',
		'title' => 'Контакты',
		'type'  => 'contacts',
		'seo'   => array(
			'trust_crumb_section' => $trust_about,
		),
	),
	array(
		'path'  => 'o-kompanii',
		'title' => 'О компании',
		'type'  => 'about',
	),
	array(
		'path'  => 'komanda',
		'title' => 'Наша команда',
		'type'  => 'team',
		'seo'   => array(
			'trust_crumb_section' => $trust_about,
		),
	),
	array(
		'path'  => 'otzyvy',
		'title' => 'Отзывы о нас',
		'type'  => 'reviews',
		'seo'   => array(
			'trust_crumb_section' => $trust_about,
		),
	),
	array(
		'path'  => 'karera',
		'title' => 'Карьера',
		'type'  => 'career',
		'seo'   => array(
			'trust_seo_title'     => 'Карьера в {site} {sep} вакансии и стажировка',
			'trust_seo_h1'        => 'Работа в {site}',
			'trust_crumb_section' => $trust_about,
		),
	),
	array(
		'path'  => 'oferta',
		'title' => 'Публичная оферта',
		'type'  => 'document',
		'seo'   => array(
			'trust_seo_h1'        => 'Публичная оферта на оказание услуг',
			'trust_crumb_section' => $trust_about,
		),
	),

	// Документы (меню «Подвал: документы»).
	array(
		'path'  => 'politika-konfidencialnosti',
		'title' => 'Политика конфиденциальности',
		'type'  => 'document',
		'seo'   => array(
			'trust_crumb_section' => $trust_about,
		),
	),
	array(
		'path'  => 'soglasie-na-obrabotku-dannyh',
		'title' => 'Согласие на обработку персональных данных',
		'type'  => 'document',
		'seo'   => array(
			'trust_crumb_section' => $trust_about,
		),
	),
	array(
		'path'  => 'politika-cookie',
		'title' => 'Политика использования файлов cookie',
		'type'  => 'document',
		'seo'   => array(
			'trust_seo_title'     => 'Политика cookie {sep} {site}',
			'trust_crumb_title'   => 'Политика cookie',
			'trust_crumb_section' => $trust_about,
		),
	),
);
