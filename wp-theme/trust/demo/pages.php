<?php
/**
 * Демо-контент: страницы сайта из меню (ТЗ, Приложение Б).
 *
 * Поля: path — адрес страницы (родитель/ярлык), title — заголовок, type — тип страницы
 * (Trust\Page_Types: шаблон; нет — «Обычная страница»), blocks — блоки темы по порядку:
 * array( 'имя блока', array( значения полей ) ); {page:адрес} в значениях — ссылка на страницу;
 * seo — поля группы «SEO»: H1 и title, если в макете они отличаются от заголовка страницы,
 * название и раздел в хлебных крошках; was — прежние адрес и заголовок переименованной страницы
 * (импорт переименует уже созданную страницу и пункты меню с прежним заголовком).
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

// Первый экран услуги: вводный текст (заглушка, если в макете страницы нет) и кнопка «Оставить заявку»,
// которая открывает окно «Заказать обратный звонок».
$trust_service_hero = static fn( string $lead = '[Вводный текст: какую задачу решает услуга и что получит клиент — 1–2 предложения.]' ): array => array(
	'hero-service',
	array(
		'lead'    => $lead,
		'buttons' => array(
			array(
				'link' => array(
					'title'  => 'Оставить заявку',
					'url'    => '#callback-modal',
					'target' => '',
				),
			),
		),
	),
);

/**
 * Первый экран раздела (индиго): плашка, текст, кнопки, цифры.
 *
 * @param string $badge   Плашка.
 * @param string $lead    Текст.
 * @param array  $stats   Цифры: подпись => значение.
 * @param array  $buttons Кнопки.
 */
$trust_section_hero = static function ( string $badge, string $lead, array $stats, array $buttons = array() ): array {
	$rows = array();
	foreach ( $stats as $label => $value ) {
		$rows[] = array(
			'value' => $value,
			'label' => $label,
		);
	}
	return array(
		'hero-section',
		array(
			'badge'   => $badge,
			'lead'    => $lead,
			'buttons' => $buttons,
			'stats'   => $rows,
		),
	);
};

/**
 * Первый экран документа: плашка «Документы», даты редакции, кнопки.
 *
 * @param bool $cookie Кнопка «Настроить cookie».
 */
$trust_document_hero = static fn( bool $cookie = false ): array => array(
	'hero-document',
	array(
		'badge'     => 'Документы',
		'revision'  => '20261101',
		'effective' => '20261101',
		'pdf_label' => $cookie ? 'PDF' : '',
		'print'     => 1,
		'cookie'    => $cookie ? 1 : 0,
	),
);

return array(
	// Услуги.
	array(
		'path'   => 'dizayn',
		'title'  => 'Дизайн',
		'type'   => 'service',
		'blocks' => array( $trust_service_hero() ),
	),
	array(
		'path'   => 'sozdanie-saytov',
		'title'  => 'Создание сайтов',
		'type'   => 'service',
		'blocks' => array( $trust_service_hero() ),
	),
	array(
		'path'   => 'site-support',
		'title'  => 'Поддержка сайтов',
		'type'   => 'service',
		// Переименована по просьбе заказчика (было «Сопровождение сайтов»), без редиректа.
		'was'    => array(
			'path'  => 'soprovozhdenie-saytov',
			'title' => 'Сопровождение сайтов',
		),
		'blocks' => array( $trust_service_hero() ),
	),
	array(
		'path'   => 'seo',
		'title'  => 'SEO-продвижение',
		'type'   => 'service',
		'seo'    => array(
			'trust_crumb_title' => 'SEO',
		),
		'blocks' => array( $trust_service_hero() ),
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
				'hero-service',
				array(
					'lead'    => 'Найдём ошибки, которые мешают сайту расти в Яндексе и Google, и дадим пошаговый план исправлений.',
					'buttons' => array(
						array(
							'link' => array(
								'title'  => 'Заказать аудит',
								'url'    => '#callback-modal',
								'target' => '',
							),
						),
						array(
							'link' => array(
								'title'  => 'Рассчитать стоимость',
								'url'    => '#calculator',
								'target' => '',
							),
						),
					),
				),
			),
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
		'path'   => 'seo/pochasovaya-oplata',
		'title'  => 'SEO с почасовой оплатой',
		'type'   => 'service',
		'blocks' => array( $trust_service_hero() ),
	),
	array(
		'path'   => 'seo/za-obem-raboty',
		'title'  => 'SEO за объём работы',
		'type'   => 'service',
		'blocks' => array( $trust_service_hero() ),
	),
	array(
		'path'   => 'seo/voprosy',
		'title'  => 'Задать вопрос SEO-шнику',
		'type'   => 'questions',
		'seo'    => array(
			'trust_crumb_title' => 'Вопросы по SEO',
		),
		'blocks' => array(
			array(
				'hero-questions',
				array(
					'badge'  => 'Бесплатно',
					'lead'   => 'Задайте вопрос о продвижении сайта — SEO-специалист [Бренд] ответит здесь, на сайте, и расскажет, как поступил бы на вашем месте.',
					'points' => array(
						array(
							'icon' => 'check',
							'text' => 'Бесплатно и без обязательств',
						),
						array(
							'icon' => 'list',
							'text' => 'Ответ публикуем на сайте, ссылку присылаем на email',
						),
						array(
							'icon' => 'user',
							'text' => 'Отвечает практикующий SEO-специалист',
						),
					),
					'link'   => array(
						'title'  => 'Читать ответы на вопросы',
						'url'    => '#answers',
						'target' => '',
					),
				),
			),
		),
	),
	array(
		'path'   => 'geo',
		'title'  => 'GEO-продвижение',
		'type'   => 'service',
		'seo'    => array(
			'trust_crumb_title' => 'GEO',
		),
		'blocks' => array( $trust_service_hero() ),
	),
	array(
		'path'   => 'geo/prodvizhenie-v-ii',
		'title'  => 'Продвижение в ИИ',
		'type'   => 'service',
		'blocks' => array( $trust_service_hero() ),
	),
	array(
		'path'   => 'geo/audit-vidimosti',
		'title'  => 'Аудит видимости',
		'type'   => 'service',
		'blocks' => array( $trust_service_hero() ),
	),
	array(
		'path'   => 'kontekstnaya-reklama',
		'title'  => 'Контекстная реклама',
		'type'   => 'service',
		'blocks' => array( $trust_service_hero() ),
	),
	array(
		'path'   => 'kontekstnaya-reklama/yandeks-direkt',
		'title'  => 'Яндекс Директ',
		'type'   => 'service',
		'blocks' => array( $trust_service_hero() ),
	),
	array(
		'path'   => 'kontekstnaya-reklama/google-reklama',
		'title'  => 'Google Реклама',
		'type'   => 'service',
		'blocks' => array( $trust_service_hero() ),
	),
	array(
		'path'   => 'ai',
		'title'  => 'AI-решения',
		'type'   => 'service',
		'blocks' => array( $trust_service_hero() ),
	),

	// Разделы.
	array(
		'path'   => 'portfolio',
		'title'  => 'Портфолио',
		'type'   => 'portfolio',
		'seo'    => array(
			'trust_seo_h1' => 'Кейсы и проекты',
		),
		'blocks' => array(
			$trust_section_hero(
				'Портфолио',
				'Показываем, с какой задачей пришёл клиент, что мы сделали и какой получился результат. Цифры берём из систем аналитики клиентов и публикуем с их согласия.',
				array(
					'завершённых проектов'           => '[N]+',
					'отраслей'                       => '[N]',
					'кейсов с цифрами'               => '[N]',
					'средний срок работы с клиентом' => '[N] года',
				)
			),
		),
	),
	array(
		'path'  => 'blog',
		'title' => 'Блог',
	),

	// О нас.
	array(
		'path'   => 'kontakty',
		'title'  => 'Контакты',
		'type'   => 'contacts',
		'seo'    => array(
			'trust_crumb_section' => $trust_about,
		),
		'blocks' => array(
			array(
				'hero-contacts',
				array(
					'badge' => 'О нас',
					'lead'  => 'Позвоните, напишите в мессенджер или приезжайте в офис — расскажем, как можем помочь вашему проекту.',
				),
			),
		),
	),
	array(
		'path'   => 'o-kompanii',
		'title'  => 'О компании',
		'type'   => 'about',
		'blocks' => array(
			$trust_section_hero(
				'О нас',
				'[Бренд] — агентство интернет-маркетинга. Продвигаем сайты в Яндексе, Google и нейросетях, создаём сайты и настраиваем рекламу. Открыто пишем, что входит в каждую услугу, и отчитываемся после каждого этапа.',
				array(
					'работаем на рынке'              => 'с [ГГГГ]',
					'завершённых проектов'           => '[N]+',
					'специалистов в штате'           => '[N]',
					'средний срок работы с клиентом' => '[N] лет',
				)
			),
		),
	),
	array(
		'path'   => 'komanda',
		'title'  => 'Наша команда',
		'type'   => 'team',
		'seo'    => array(
			'trust_crumb_section' => $trust_about,
		),
		'blocks' => array(
			$trust_section_hero(
				'О нас',
				'SEO-специалисты, разработчики, дизайнеры, специалисты по рекламе и аналитики — в штате, а не на фрилансе. Знакомьтесь с теми, кто будет работать над вашим проектом.',
				array(
					'специалистов в штате'           => '[N]',
					'средний опыт в профессии'       => '[N] лет',
					'сертифицированных специалистов' => '[N]',
					'экспертных статей в блоге'      => '[N]',
				)
			),
		),
	),
	array(
		'path'   => 'otzyvy',
		'title'  => 'Отзывы о нас',
		'type'   => 'reviews',
		'seo'    => array(
			'trust_crumb_section' => $trust_about,
		),
		'blocks' => array(
			array(
				'hero-reviews',
				array(
					'badge'     => 'О нас',
					'lead'      => 'Публикуем отзывы с именем, должностью и компанией клиента, а также сканы благодарственных писем. Отзывы на независимых площадках можно прочитать по ссылкам ниже.',
					'platforms' => array(
						array(
							'name'   => 'Яндекс Карты',
							'rating' => '[X,X]',
							'count'  => '[N] отзывов',
							'url'    => 'https://yandex.ru/maps/',
						),
						array(
							'name'   => '2ГИС',
							'rating' => '[X,X]',
							'count'  => '[N] отзывов',
							'url'    => 'https://2gis.ru/',
						),
						array(
							'name'   => 'Google Карты',
							'rating' => '[X,X]',
							'count'  => '[N] отзывов',
							'url'    => 'https://www.google.com/maps',
						),
						array(
							'name'   => 'Workspace',
							'rating' => '[X,X]',
							'count'  => '[N] отзывов',
							'url'    => 'https://workspace.ru/',
						),
					),
				),
			),
		),
	),
	array(
		'path'   => 'karera',
		'title'  => 'Карьера',
		'type'   => 'career',
		'seo'    => array(
			'trust_seo_title'     => 'Карьера в {site} {sep} вакансии и стажировка',
			'trust_seo_h1'        => 'Работа в {site}',
			'trust_crumb_section' => $trust_about,
		),
		'blocks' => array(
			$trust_section_hero(
				'Карьера',
				'Растим специалистов внутри команды: наставник с первого дня, понятные грейды и реальные проекты. Ищем тех, кому интересно разбираться в поиске, сайтах и рекламе.',
				array(
					'человек в команде'       => '[N]',
					'открытых вакансий'       => '{vacancies}',
					'стажёров перешли в штат' => '[N]',
					'средний стаж в компании' => '[N] года',
				),
				array(
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
				)
			),
		),
	),
	array(
		'path'   => 'oferta',
		'title'  => 'Публичная оферта',
		'type'   => 'document',
		'seo'    => array(
			'trust_seo_h1'        => 'Публичная оферта на оказание услуг',
			'trust_crumb_section' => $trust_about,
		),
		'blocks' => array(
			$trust_document_hero(),
		),
	),

	// Документы (меню «Подвал: документы»).
	array(
		'path'   => 'politika-konfidencialnosti',
		'title'  => 'Политика конфиденциальности',
		'type'   => 'document',
		'seo'    => array(
			'trust_crumb_section' => $trust_about,
		),
		'blocks' => array(
			$trust_document_hero(),
		),
	),
	array(
		'path'   => 'soglasie-na-obrabotku-dannyh',
		'title'  => 'Согласие на обработку персональных данных',
		'type'   => 'document',
		'seo'    => array(
			'trust_crumb_section' => $trust_about,
		),
		'blocks' => array(
			$trust_document_hero(),
		),
	),
	array(
		'path'   => 'politika-cookie',
		'title'  => 'Политика использования файлов cookie',
		'type'   => 'document',
		'seo'    => array(
			'trust_seo_title'     => 'Политика cookie {sep} {site}',
			'trust_crumb_title'   => 'Политика cookie',
			'trust_crumb_section' => $trust_about,
		),
		'blocks' => array(
			$trust_document_hero( true ),
		),
	),
);
