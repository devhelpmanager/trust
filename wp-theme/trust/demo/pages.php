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
 * Строки повторителя из одного текстового подполя: «Что проверяем» и т. п.
 *
 * @param string ...$lines Строки.
 */
$trust_lines = static fn( string ...$lines ): array => array_map( static fn( $line ) => array( 'text' => $line ), $lines );

/**
 * Тариф из макета seo-bloki/01-tarify.html: цены — заглушки «XX XXX ₽», состав — восемь работ
 * (1 — входит, 0 — нет), кнопка открывает окно «Заказать обратный звонок».
 *
 * @param string $name        Название.
 * @param string $who         Кому подходит.
 * @param array  $included    Входит ли каждая из восьми работ.
 * @param bool   $recommended «Рекомендуем».
 */
$trust_tariff = static function ( string $name, string $who, array $included, bool $recommended = false ): array {
	$works = array(
		'Технический аудит сайта',
		'Сбор семантического ядра',
		'Оптимизация мета-тегов',
		'Настройка Яндекс Вебмастера и Google Search Console',
		'Внутренняя перелинковка',
		'Тексты для продвигаемых страниц',
		'Работа с внешними ссылками',
		'Ежемесячный отчёт с рекомендациями',
	);
	$items = array();
	foreach ( $works as $i => $work ) {
		$items[] = array(
			'text'     => $work,
			'included' => (int) $included[ $i ],
		);
	}
	return array(
		'name'        => $name,
		'recommended' => (int) $recommended,
		'who'         => $who,
		'old_price'   => 'XX XXX ₽',
		'discount'    => '−XX%',
		'price'       => 'XX XXX ₽',
		'period'      => '/ месяц',
		'items'       => $items,
		'button'      => array(
			'title'  => 'Выбрать «' . $name . '»',
			'url'    => '#callback-modal',
			'target' => '',
		),
	);
};

/**
 * Абзац (блок ядра) для «Блока с текстом».
 *
 * @param string $html Текст с разметкой ссылок.
 */
$trust_paragraph = static fn( string $html ): string => "<!-- wp:paragraph -->\n<p>" . $html . "</p>\n<!-- /wp:paragraph -->";

/**
 * Список (блок ядра) для «Блока с текстом».
 *
 * @param bool  $ordered Нумерованный.
 * @param array $items   Пункты.
 */
$trust_list = static function ( bool $ordered, array $items ): string {
	$tag  = $ordered ? 'ol' : 'ul';
	$html = '<!-- wp:list' . ( $ordered ? ' {"ordered":true}' : '' ) . " -->\n<" . $tag . ' class="wp-block-list">';
	foreach ( $items as $item ) {
		$html .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->";
	}
	return $html . '</' . $tag . ">\n<!-- /wp:list -->";
};

/**
 * Цитата (блок ядра) со стилем темы: author — «С автором», important — «Важно».
 *
 * @param string $style Стиль.
 * @param string $text  Текст.
 * @param string $cite  Подпись: имя<br>должность.
 */
$trust_quote = static function ( string $style, string $text, string $cite = '' ): string {
	return '<!-- wp:quote {"className":"is-style-' . $style . '"} -->' . "\n"
		. '<blockquote class="wp-block-quote is-style-' . $style . '"><!-- wp:paragraph -->' . "\n<p>" . $text . "</p>\n<!-- /wp:paragraph -->"
		. ( '' !== $cite ? '<cite>' . $cite . '</cite>' : '' ) . "</blockquote>\n<!-- /wp:quote -->";
};

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
		// Блоки шага 4.5 (макеты seo-bloki/01, 03, 08, 05): тарифы, текст, картинка, «Главная мысль».
		'blocks' => array(
			$trust_service_hero(),
			array(
				'tariffs',
				array(
					'anchor'  => 'tariffs',
					'eyebrow' => 'Тарифы',
					'title'   => 'Выберите тариф SEO-продвижения',
					'lead'    => 'Сравните, что входит в каждый тариф. Состав работ и стоимость фиксируются в договоре.',
					'tariffs' => array(
						$trust_tariff( 'Старт', 'Для небольших сайтов и локального бизнеса: наводим порядок в технической части и выводим сайт в поиск по основным запросам.', array( 1, 1, 1, 1, 0, 0, 0, 1 ) ),
						$trust_tariff( 'Бизнес', 'Для компаний, которым нужен стабильный поток заявок из поиска: системная работа со структурой сайта, семантикой и текстами.', array( 1, 1, 1, 1, 1, 1, 0, 1 ), true ),
						$trust_tariff( 'Максимум', 'Для интернет-магазинов и сайтов в конкурентных нишах, где без работы с внешними ссылками рост упирается в потолок.', array( 1, 1, 1, 1, 1, 1, 1, 1 ) ),
					),
				),
			),
			array(
				'text',
				array(),
				implode(
					"\n\n",
					array(
						'<!-- wp:heading -->' . "\n" . '<h2 class="wp-block-heading">Зачем нужен технический аудит сайта</h2>' . "\n" . '<!-- /wp:heading -->',
						$trust_paragraph( 'Технический аудит — это проверка сайта на ошибки, которые мешают поисковым системам находить, сканировать и индексировать страницы. Даже качественный контент не принесёт трафика, если робот не может добраться до нужных страниц или видит вместо них дубли.' ),
						$trust_paragraph( 'Аудит особенно важен после редизайна, переезда на новый домен или CMS, а также если трафик из поиска перестал расти без видимых причин. Типичные симптомы мы разобрали в разделе <a href="{page:seo/audit}#faq">«Частые вопросы»</a>.' ),
						'<!-- wp:heading {"level":3} -->' . "\n" . '<h3 class="wp-block-heading">Что проверяем в первую очередь</h3>' . "\n" . '<!-- /wp:heading -->',
						$trust_list(
							false,
							array(
								'Файл robots.txt — не закрыты ли от индексации важные разделы',
								'Карту сайта sitemap.xml — актуальность и корректность адресов',
								'Коды ответа сервера — битые ссылки, цепочки редиректов, ошибки 5xx',
								'Дубли страниц и корректность канонических адресов',
							)
						),
						$trust_quote( 'author', 'Большинство проблем с индексацией не видно при обычном просмотре сайта: страница открывается у вас в браузере, но поисковый робот может получать совсем другой ответ сервера.', '[Имя Фамилия]<br>SEO-специалист' ),
						'<!-- wp:heading {"level":3} -->' . "\n" . '<h3 class="wp-block-heading">Как проходит аудит</h3>' . "\n" . '<!-- /wp:heading -->',
						$trust_list(
							true,
							array(
								'Собираем данные из Яндекс Вебмастера, Google Search Console и систем аналитики.',
								'Сканируем сайт специализированными инструментами и вручную проверяем ключевые шаблоны страниц.',
								'Расставляем найденные ошибки по приоритету — от тех, что сильнее всего влияют на трафик.',
								'Передаём отчёт с пошаговыми рекомендациями, понятными вашему разработчику.',
							)
						),
						$trust_quote( 'important', 'Одна строка в robots.txt или неверно настроенный редирект могут перечеркнуть месяцы работы над контентом.' ),
						$trust_paragraph( 'Каждую из проверок можно заказать отдельно — например, <a href="{page:seo/audit}#includes">аудит скорости загрузки</a> или <a href="{page:seo/audit}#includes">анализ индексации</a>. Полный список работ — в блоке <a href="{page:seo/audit}#includes">«Что включает услуга»</a>.' ),
					)
				),
			),
			array(
				'image',
				array(
					'image'        => '{image:image-audit-report.jpg|Фрагмент отчёта по техническому аудиту: найденные ошибки сгруппированы по приоритету}',
					'alt'          => 'Фрагмент отчёта по техническому аудиту: найденные ошибки сгруппированы по приоритету',
					'caption'      => 'Фрагмент отчёта по техническому аудиту. Найденные ошибки сгруппированы по приоритету — от критичных к желательным.',
					'width'        => 'wide',
					'mobile_image' => '{image:image-audit-report-mobile.jpg|Фрагмент отчёта по техническому аудиту: найденные ошибки сгруппированы по приоритету}',
				),
			),
			array(
				'key-idea',
				array(
					'eyebrow'       => 'Главная мысль',
					'title'         => 'Почему мы открыто пишем про всё',
					'quote'         => 'Мы открыто пишем, что входит в каждую услугу, потому что список работ — это только названия.',
					'text'          => 'Прочитать «проверка robots.txt» может любой. Но что именно проверять, как трактовать найденное и что исправлять в первую очередь — это опыт и экспертиза, и они остаются нашими. А вы заранее видите, за что платите, и можете спокойно сравнить нас с другими подрядчиками.',
					'author_source' => 'team',
					'author'        => '{post:team:ceo}',
					'author_role'   => 'руководитель агентства',
				),
			),
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
				'service-includes',
				array(
					'anchor'       => 'includes',
					'eyebrow'      => 'Состав услуги',
					'title'        => 'Что включает технический аудит',
					'lead'         => 'Нажмите на работу, чтобы узнать подробности. Каждую из них можно заказать отдельно — у неё есть своя страница с описанием и стоимостью.',
					'checks_label' => 'Что проверяем',
					'more_label'   => 'Подробнее об услуге',
					// Ссылки на страницы работ — якоря из макета: страниц под-услуг пока нет.
					'items'        => array(
						array(
							'icon'        => 'robot',
							'title'       => 'Проверка robots.txt',
							'description' => 'Файл robots.txt подсказывает поисковым роботам, какие разделы сайта сканировать, а какие нет. Одна ошибка в нём может закрыть от индексации весь каталог или, наоборот, открыть служебные страницы.',
							'price'       => 'от X XXX ₽',
							'link'        => array(
								'title'  => '',
								'url'    => '#robots',
								'target' => '',
							),
							'checks'      => $trust_lines(
								'Синтаксис и корректность директив',
								'Важные разделы, случайно закрытые от индексации',
								'Служебные страницы, открытые для роботов',
								'Отдельные правила для Яндекса и Google, ссылка на карту сайта',
							),
						),
						array(
							'icon'        => 'sitemap',
							'title'       => 'Анализ карты сайта',
							'description' => 'Карта сайта sitemap.xml помогает поисковым системам быстрее находить новые и обновлённые страницы. Если в ней остались устаревшие или закрытые адреса, робот тратит время впустую.',
							'price'       => 'от X XXX ₽',
							'link'        => array(
								'title'  => '',
								'url'    => '#sitemap',
								'target' => '',
							),
							'checks'      => $trust_lines(
								'Наличие и доступность файла',
								'Адреса с ошибками, редиректами и запретом индексации',
								'Пропущенные важные страницы',
								'Актуальность дат изменения страниц',
							),
						),
						array(
							'icon'        => 'server',
							'title'       => 'Коды ответа сервера',
							'description' => 'Каждая страница сообщает роботу код ответа: 200 — всё в порядке, 301 — страница переехала, 404 — не найдена. Неверные коды мешают индексации и расходуют время, которое робот тратит на обход сайта.',
							'price'       => 'от X XXX ₽',
							'link'        => array(
								'title'  => '',
								'url'    => '#server-codes',
								'target' => '',
							),
							'checks'      => $trust_lines(
								'Битые внутренние ссылки (404)',
								'Цепочки и циклы редиректов',
								'Ошибки сервера 5xx',
								'Пустые страницы, которые отдают код 200 вместо 404',
							),
						),
						array(
							'icon'        => 'copy',
							'title'       => 'Поиск дублей страниц',
							'description' => 'Дубли — это одинаковые или почти одинаковые страницы по разным адресам. Поисковая система сама выбирает, какую из них показывать, и нередко выбирает не ту.',
							'price'       => 'от X XXX ₽',
							'link'        => array(
								'title'  => '',
								'url'    => '#duplicates',
								'target' => '',
							),
							'checks'      => $trust_lines(
								'Адреса с www и без, со слешем на конце и без',
								'Дубли из-за GET-параметров и фильтров',
								'Настройку канонических адресов (rel=canonical)',
								'Одинаковые заголовки и описания страниц',
							),
						),
						array(
							'icon'        => 'bolt',
							'title'       => 'Скорость загрузки',
							'description' => 'Скорость влияет и на позиции в поиске, и на поведение посетителей. Измеряем показатели Core Web Vitals и находим, что именно замедляет страницы.',
							'price'       => 'от X XXX ₽',
							'link'        => array(
								'title'  => '',
								'url'    => '#speed',
								'target' => '',
							),
							'checks'      => $trust_lines(
								'Показатели LCP, INP и CLS',
								'Вес и формат изображений',
								'Скрипты и стили, блокирующие отрисовку',
								'Кеширование и сжатие на сервере',
							),
						),
						array(
							'icon'        => 'mobile',
							'title'       => 'Мобильная версия',
							'description' => 'Поисковые системы оценивают сайт в первую очередь по мобильной версии. Проверяем, удобно ли пользоваться сайтом со смартфона и видит ли робот весь контент.',
							'price'       => 'от X XXX ₽',
							'link'        => array(
								'title'  => '',
								'url'    => '#mobile',
								'target' => '',
							),
							'checks'      => $trust_lines(
								'Адаптивность вёрстки на разных экранах',
								'Размер шрифтов и кнопок',
								'Контент, скрытый на мобильных устройствах',
								'Всплывающие окна, которые мешают чтению',
							),
						),
						array(
							'icon'        => 'database',
							'title'       => 'Анализ индексации',
							'description' => 'Сравниваем, сколько страниц есть на сайте и сколько из них знают Яндекс и Google. Расхождение в любую сторону указывает на проблему, которую нужно найти.',
							'price'       => 'от X XXX ₽',
							'link'        => array(
								'title'  => '',
								'url'    => '#indexing',
								'target' => '',
							),
							'checks'      => $trust_lines(
								'Данные Яндекс Вебмастера и Google Search Console',
								'Важные страницы, которых нет в индексе',
								'Мусорные страницы, попавшие в индекс',
								'Причины исключения страниц из поиска',
							),
						),
						array(
							'icon'        => 'code',
							'title'       => 'Микроразметка',
							'description' => 'Микроразметка помогает поисковым системам понять содержимое страницы и показать в выдаче расширенный сниппет: цену, рейтинг, хлебные крошки.',
							'price'       => 'от X XXX ₽',
							'link'        => array(
								'title'  => '',
								'url'    => '#schema',
								'target' => '',
							),
							'checks'      => $trust_lines(
								'Разметку Schema.org для товаров, услуг и организации',
								'Разметку Open Graph для соцсетей и мессенджеров',
								'Ошибки в валидаторах Яндекса и Google',
								'Хлебные крошки и контактные данные',
							),
						),
					),
				),
			),
			array(
				'stages',
				array(
					'anchor'  => 'stages',
					'eyebrow' => 'Как мы работаем',
					'title'   => 'Этапы работы',
					'lead'    => 'Пять шагов от заявки до отчёта. Нажмите на этап, чтобы узнать, что на нём происходит.',
					'stages'  => array(
						array(
							'picture' => 'application',
							'title'   => 'Заявка',
							'short'   => 'Оставляете заявку на сайте или звоните нам',
							'detail'  => 'Уточняем задачу, адрес сайта и желаемые сроки. Если услуга не подходит под вашу ситуацию, скажем об этом сразу и предложим подходящую.',
						),
						array(
							'picture' => 'analysis',
							'title'   => 'Анализ и предложение',
							'short'   => 'Изучаем сайт и готовим коммерческое предложение',
							'detail'  => 'Смотрим сайт, нишу и конкурентов, определяем объём работ. В предложении — состав работ, сроки и фиксированная стоимость.',
						),
						array(
							'picture' => 'contract',
							'title'   => 'Договор',
							'short'   => 'Фиксируем состав работ, сроки и стоимость',
							'detail'  => 'Закрепляем в договоре состав работ, стоимость и даты сдачи этапов. После подписания запрашиваем доступы к сайту и сервисам аналитики.',
						),
						array(
							'picture' => 'work',
							'title'   => 'Выполнение работ',
							'short'   => 'Специалист выполняет работы по согласованному плану',
							'detail'  => 'Ответственный специалист на связи и сообщает о важных находках сразу, не дожидаясь финального отчёта.',
						),
						array(
							'picture' => 'report',
							'title'   => 'Отчёт',
							'short'   => 'Передаём отчёт с найденными проблемами и рекомендациями',
							'detail'  => 'В отчёте — что проверено, какие ошибки найдены, как их исправить и в каком порядке. Разбираем его вместе и отвечаем на вопросы.',
							'result'  => 1,
						),
					),
				),
			),
			array(
				'advantages',
				array(
					'anchor'  => 'advantages',
					'eyebrow' => 'Почему мы',
					'title'   => 'Наши преимущества',
					'items'   => array(
						array(
							'icon'  => 'eye',
							'title' => 'Прозрачный состав работ',
							'text'  => 'Публикуем полный список работ по каждой услуге. Вы заранее знаете, за что платите.',
						),
						array(
							'icon'  => 'tag',
							'title' => 'Фиксированная стоимость',
							'text'  => 'Цена закрепляется в договоре и не меняется в процессе работы.',
						),
						array(
							'icon'  => 'report',
							'title' => 'Отчёт после каждого этапа',
							'text'  => 'Показываем, что сделано, какие проблемы найдены и что их исправление даст сайту.',
						),
						array(
							'icon'  => 'headset',
							'title' => 'Один ответственный специалист',
							'text'  => 'Ведёт проект от брифа до финального отчёта и остаётся на связи на всех этапах.',
						),
						array(
							'icon'  => 'layers',
							'title' => 'SEO, сайты и реклама в одной команде',
							'text'  => 'Доработки сайта, продвижение и контекстная реклама согласуются внутри команды, а не между разными подрядчиками.',
						),
						array(
							'icon'  => 'shield',
							'title' => 'Работаем по договору',
							'text'  => 'Официальный договор, закрывающие документы и чёткие сроки по каждому этапу.',
						),
					),
				),
			),
			array(
				'faq',
				array(
					'anchor'    => 'faq',
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
			array(
				'contact-swipe',
				array(
					'anchor'            => 'callback',
					'eyebrow'           => 'Связаться с нами',
					'title'             => 'Перезвоним и ответим на ваши вопросы',
					'lead'              => 'Оставьте имя и телефон — специалист свяжется с вами и поможет выбрать услугу.',
					'form_title'        => 'Обратный звонок',
					'name_placeholder'  => 'Ваше имя',
					'phone_placeholder' => '+1 000 000 00 00',
					'swipe_label'       => 'Свайпните',
					'sending'           => 'Отправка…',
					'success_title'     => 'Заявка отправлена',
					'success_text'      => 'Мы перезвоним вам в ближайшее время',
					'again'             => 'Отправить ещё одну заявку',
				),
			),
			array(
				'posts',
				array(
					'anchor'     => 'blog',
					'eyebrow'    => 'Блог',
					'title'      => 'Наш блог',
					'view'       => 'blog',
					'source'     => 'latest',
					'count'      => 5,
					'more_label' => 'Все статьи',
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
