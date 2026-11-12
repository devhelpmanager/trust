<?php
/**
 * Демо-вопросы из макета seo-vopros/vopros-seo.html (20 вопросов: тема, кто спросил, вопрос,
 * начало ответа, дата ответа) и seo-vopros/otvet.html (полный вопрос и ответ на первый).
 * Ещё один вопрос — «На утверждении», как пришедший из формы: на нём виден счётчик в меню.
 *
 * Текст записи — ответ специалиста из стандартных блоков. Схема в ответе, ссылки на статьи
 * и блок «Как бы поступил я» появятся вместе с демо-статьями и блоком (шаги 4.18, 5.x).
 * Дата вопроса в макете есть только у первого («3 ноября»), у остальных — за 3 дня до ответа.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

/**
 * Абзац в разметке блоков.
 *
 * @param string $text Текст (HTML).
 */
$trust_p = static fn( string $text ): string => "<!-- wp:paragraph -->\n<p>" . $text . "</p>\n<!-- /wp:paragraph -->";

/**
 * Заголовок H2 в разметке блоков.
 *
 * @param string $text Текст.
 */
$trust_h = static fn( string $text ): string => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . $text . "</h2>\n<!-- /wp:heading -->";

/**
 * Список в разметке блоков.
 *
 * @param string[] $items Пункты (HTML).
 */
$trust_ul = static fn( array $items ): string => "<!-- wp:list -->\n<ul class=\"wp-block-list\">" . implode(
	'',
	array_map( static fn( string $item ): string => "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->", $items )
) . "</ul>\n<!-- /wp:list -->";

$trust_first_question = "Месяц назад добавили в интернет-магазин новый раздел — около 300 карточек товаров. В Google страницы уже в поиске, а в Яндексе из них появилось меньше двадцати.\n\nSitemap обновляется автоматически, robots.txt не меняли. С чего начать поиск причины?";

$trust_first_answer = implode(
	"\n\n",
	array(
		$trust_p( 'Анна, здравствуйте! Ситуация частая: Google обычно индексирует новые страницы охотнее, а Яндекс отбирает их в поиск строже. Расскажу, в каком порядке я бы искал причину.' ),
		$trust_h( '1. Посмотрите, что Яндекс знает о новых страницах' ),
		$trust_p( 'В Яндекс Вебмастере откройте раздел «Индексирование → Страницы в поиске» и выберите исключённые страницы. Если новые карточки там есть, рядом будет указана причина исключения — с неё и начинайте.' ),
		$trust_h( '2. Если страниц нет даже среди исключённых' ),
		$trust_p( 'Значит, робот до них ещё не добрался. Я бы проверил три вещи:' ),
		$trust_ul(
			array(
				'новые адреса есть в sitemap.xml, а сам файл добавлен в Вебмастер;',
				'на карточки ведут обычные ссылки из категорий каталога, а не только поиск по сайту;',
				'страницы отдают код ответа 200 — это видно в инструменте «Проверка ответа сервера».',
			)
		),
		$trust_p( 'Самые важные страницы отправьте в «Индексирование → Переобход страниц»: так робот узнает о них быстрее.' ),
		$trust_h( '3. Если страницы исключены — действуйте по причине' ),
		$trust_ul(
			array(
				'<strong>Запрет в robots.txt или метатег noindex.</strong> Проверьте правила для раздела и шаблон карточки: запрет часто остаётся после разработки.',
				'<strong>Дубль или неканоническая страница.</strong> Проверьте атрибут canonical и параметры в адресах: сортировки и фильтры часто создают копии карточек.',
				'<strong>Малоценная или маловостребованная страница.</strong> Яндекс решил, что карточка мало что добавляет к уже известным ему страницам. Дополните её уникальным описанием, характеристиками, фото и отзывами.',
			)
		),
	)
);

$trust_questions = array(
	array(
		'key'     => 'q01',
		'title'   => 'Почему Яндекс не индексирует новые страницы каталога?',
		'date'    => '2026-11-06 12:00:00',
		'content' => $trust_first_answer,
		'terms'   => array(
			'question_topic' => array( 'indexing' ),
			'post_tag'       => array( 'index', 'sitemap', 'robots', 'dupes' ),
		),
		'fields'  => array(
			'trust_question'    => $trust_first_question,
			'trust_asker'       => 'Анна',
			'trust_asked_at'    => '20261103',
			'trust_answered_at' => '20261106',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q02',
		'title'   => 'Нужно ли закрывать от индексации страницы фильтров?',
		'date'    => '2026-11-04 12:00:00',
		'content' => $trust_p( 'Не все. Фильтры, под которые есть спрос, лучше оставить открытыми и оптимизировать, а сочетания без спроса закрыть, чтобы не плодить дубли.' ),
		'terms'   => array(
			'question_topic' => array( 'audit' ),
			'post_tag'       => array( 'index', 'dupes' ),
		),
		'fields'  => array(
			'trust_question'    => 'Нужно ли закрывать от индексации страницы фильтров?',
			'trust_asker'       => 'Дмитрий',
			'trust_asked_at'    => '20261101',
			'trust_answered_at' => '20261104',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q03',
		'title'   => 'Влияет ли скорость загрузки на позиции сайта?',
		'date'    => '2026-10-31 12:00:00',
		'content' => $trust_p( 'Влияет, но не сама по себе: медленный сайт хуже удерживает посетителей, а Google учитывает Core Web Vitals как один из сигналов. Контент и релевантность всё равно важнее.' ),
		'terms'   => array(
			'question_topic' => array( 'speed' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'Влияет ли скорость загрузки на позиции сайта?',
			'trust_asker'       => 'Ольга',
			'trust_asked_at'    => '20261028',
			'trust_answered_at' => '20261031',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q04',
		'title'   => 'Как переехать на новый домен и не потерять трафик?',
		'date'    => '2026-10-29 12:00:00',
		'content' => $trust_p( 'Настройте постраничные 301-редиректы со старых адресов на новые, сообщите о переезде в Яндекс Вебмастере и Google Search Console и первые недели следите за индексацией.' ),
		'terms'   => array(
			'question_topic' => array( 'migration' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'Как переехать на новый домен и не потерять трафик?',
			'trust_asker'       => 'Сергей',
			'trust_asked_at'    => '20261026',
			'trust_answered_at' => '20261029',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q05',
		'title'   => 'Сколько текста нужно на странице услуги?',
		'date'    => '2026-10-27 12:00:00',
		'content' => $trust_p( 'Универсальной нормы нет. Смотрите на страницы конкурентов из топа и на вопросы клиентов: текст должен отвечать на них, а не набирать объём.' ),
		'terms'   => array(
			'question_topic' => array( 'content' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'Сколько текста нужно на странице услуги?',
			'trust_asker'       => 'Марина',
			'trust_asked_at'    => '20261024',
			'trust_answered_at' => '20261027',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q06',
		'title'   => 'Стоит ли покупать ссылки для продвижения?',
		'date'    => '2026-10-24 12:00:00',
		'content' => $trust_p( 'Это риск: и Яндекс, и Google наказывают за покупные ссылки. Надёжнее получать упоминания через полезный контент, PR и партнёрства.' ),
		'terms'   => array(
			'question_topic' => array( 'links' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'Стоит ли покупать ссылки для продвижения?',
			'trust_asker'       => 'Игорь',
			'trust_asked_at'    => '20261021',
			'trust_answered_at' => '20261024',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q07',
		'title'   => 'Как продвигаться в нескольких городах с одним сайтом?',
		'date'    => '2026-10-22 12:00:00',
		'content' => $trust_p( 'Сделайте отдельные страницы под каждый город с уникальным контентом и контактами, а филиалы добавьте в Яндекс Бизнес и Google Business Profile.' ),
		'terms'   => array(
			'question_topic' => array( 'regional' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'Как продвигаться в нескольких городах с одним сайтом?',
			'trust_asker'       => 'Екатерина',
			'trust_asked_at'    => '20261019',
			'trust_answered_at' => '20261022',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q08',
		'title'   => 'Нужно ли заполнять meta keywords?',
		'date'    => '2026-10-20 12:00:00',
		'content' => $trust_p( 'Google этот тег не учитывает, а для Яндекса это слабый сигнал. Время лучше потратить на title и description — они влияют на сниппет в выдаче.' ),
		'terms'   => array(
			'question_topic' => array( 'meta' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'Нужно ли заполнять meta keywords?',
			'trust_asker'       => 'Алексей',
			'trust_asked_at'    => '20261017',
			'trust_answered_at' => '20261020',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q09',
		'title'   => 'Почему данные Метрики и Вебмастера по трафику не совпадают?',
		'date'    => '2026-10-17 12:00:00',
		'content' => $trust_p( 'Это нормально: Вебмастер считает клики в поиске, а Метрика — визиты на сайте. Часть кликов не становится визитами, например если счётчик не успел загрузиться.' ),
		'terms'   => array(
			'question_topic' => array( 'analytics' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'Почему данные Метрики и Вебмастера по трафику не совпадают?',
			'trust_asker'       => 'Наталья',
			'trust_asked_at'    => '20261014',
			'trust_answered_at' => '20261017',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q10',
		'title'   => 'Как попасть в ответы поисковых нейросетей?',
		'date'    => '2026-10-15 12:00:00',
		'content' => $trust_p( 'Нейросети опираются на страницы, которые хорошо индексируются и прямо отвечают на вопрос. Помогают понятная структура, факты с источниками и микроразметка.' ),
		'terms'   => array(
			'question_topic' => array( 'geo' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'Как попасть в ответы поисковых нейросетей?',
			'trust_asker'       => 'Павел',
			'trust_asked_at'    => '20261012',
			'trust_answered_at' => '20261015',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q11',
		'title'   => 'Что делать с дублями страниц из-за UTM-меток?',
		'date'    => '2026-10-13 12:00:00',
		'content' => $trust_p( 'Укажите canonical на основной адрес, а для Яндекса добавьте в robots.txt директиву Clean-param — она убирает такие адреса из обхода.' ),
		'terms'   => array(
			'question_topic' => array( 'duplicates' ),
			'post_tag'       => array( 'dupes' ),
		),
		'fields'  => array(
			'trust_question'    => 'Что делать с дублями страниц из-за UTM-меток?',
			'trust_asker'       => 'Юлия',
			'trust_asked_at'    => '20261010',
			'trust_answered_at' => '20261013',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q12',
		'title'   => 'Позиции упали за один день. Это фильтр?',
		'date'    => '2026-10-10 12:00:00',
		'content' => $trust_p( 'Не обязательно. Сначала проверьте раздел «Безопасность и нарушения» в Вебмастере, ошибки сервера и изменения, которые вносили на сайт в эти дни.' ),
		'terms'   => array(
			'question_topic' => array( 'sanctions' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'Позиции упали за один день. Это фильтр?',
			'trust_asker'       => 'Максим',
			'trust_asked_at'    => '20261007',
			'trust_answered_at' => '20261010',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q13',
		'title'   => 'Нужна ли карта сайта небольшому сайту?',
		'date'    => '2026-10-08 12:00:00',
		'content' => $trust_p( 'Сайту из десятка страниц с хорошей перелинковкой она не обязательна, но и не помешает: sitemap.xml помогает роботам быстрее находить новые страницы.' ),
		'terms'   => array(
			'question_topic' => array( 'sitemap' ),
			'post_tag'       => array( 'sitemap' ),
		),
		'fields'  => array(
			'trust_question'    => 'Нужна ли карта сайта небольшому сайту?',
			'trust_asker'       => 'Ирина',
			'trust_asked_at'    => '20261005',
			'trust_answered_at' => '20261008',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q14',
		'title'   => 'Нужна ли отдельная мобильная версия сайта?',
		'date'    => '2026-10-06 12:00:00',
		'content' => $trust_p( 'Сейчас стандарт — адаптивная вёрстка: один адрес и один код для всех устройств. Отдельную мобильную версию сложнее поддерживать.' ),
		'terms'   => array(
			'question_topic' => array( 'mobile' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'Нужна ли отдельная мобильная версия сайта?',
			'trust_asker'       => 'Андрей',
			'trust_asked_at'    => '20261003',
			'trust_answered_at' => '20261006',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q15',
		'title'   => 'Помогает ли реклама в Директе SEO-продвижению?',
		'date'    => '2026-10-03 12:00:00',
		'content' => $trust_p( 'Напрямую нет: оплата рекламы не влияет на позиции. Но реклама быстро показывает, какие запросы приводят заявки, и это полезно для SEO.' ),
		'terms'   => array(
			'question_topic' => array( 'ppc' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'Помогает ли реклама в Директе SEO-продвижению?',
			'trust_asker'       => 'Светлана',
			'trust_asked_at'    => '20260930',
			'trust_answered_at' => '20261003',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q16',
		'title'   => 'Что даёт микроразметка Schema.org?',
		'date'    => '2026-10-01 12:00:00',
		'content' => $trust_p( 'Она помогает поисковикам понять содержимое страницы и может сделать сниппет заметнее — с ценой, рейтингом или датой. Напрямую позиции не повышает.' ),
		'terms'   => array(
			'question_topic' => array( 'schema' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'Что даёт микроразметка Schema.org?',
			'trust_asker'       => 'Роман',
			'trust_asked_at'    => '20260928',
			'trust_answered_at' => '20261001',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q17',
		'title'   => 'Учитывает ли Яндекс поведенческие факторы?',
		'date'    => '2026-09-29 12:00:00',
		'content' => $trust_p( 'Да, Яндекс учитывает, как пользователи взаимодействуют с выдачей и сайтом. Но накрутка поведенческих факторов — нарушение, за которое сайт теряет позиции.' ),
		'terms'   => array(
			'question_topic' => array( 'behavior' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'Учитывает ли Яндекс поведенческие факторы?',
			'trust_asker'       => 'Татьяна',
			'trust_asked_at'    => '20260926',
			'trust_answered_at' => '20260929',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q18',
		'title'   => 'Как закрыть сайт от индексации на время разработки?',
		'date'    => '2026-09-26 12:00:00',
		'content' => $trust_p( 'Надёжнее всего закрыть тестовую версию паролем. Запрет в robots.txt не гарантирует, что адреса не попадут в поиск, если на них есть ссылки.' ),
		'terms'   => array(
			'question_topic' => array( 'robots' ),
			'post_tag'       => array( 'robots' ),
		),
		'fields'  => array(
			'trust_question'    => 'Как закрыть сайт от индексации на время разработки?',
			'trust_asker'       => 'Денис',
			'trust_asked_at'    => '20260923',
			'trust_answered_at' => '20260926',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q19',
		'title'   => 'Как работать с отзывами на картах?',
		'date'    => '2026-09-24 12:00:00',
		'content' => $trust_p( 'Отвечайте на все отзывы, включая негативные, и просите довольных клиентов делиться впечатлениями. Покупка отзывов нарушает правила площадок.' ),
		'terms'   => array(
			'question_topic' => array( 'local' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'Как работать с отзывами на картах?',
			'trust_asker'       => 'Елена',
			'trust_asked_at'    => '20260921',
			'trust_answered_at' => '20260924',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'     => 'q20',
		'title'   => 'С чего начать сбор семантического ядра?',
		'date'    => '2026-09-22 12:00:00',
		'content' => $trust_p( 'С маркерных запросов по каждой услуге или категории. Затем расширьте список через Вордстат и подсказки, уберите нецелевые запросы и распределите остальные по страницам.' ),
		'terms'   => array(
			'question_topic' => array( 'semantics' ),
			'post_tag'       => array(),
		),
		'fields'  => array(
			'trust_question'    => 'С чего начать сбор семантического ядра?',
			'trust_asker'       => 'Константин',
			'trust_asked_at'    => '20260919',
			'trust_answered_at' => '20260922',
			'trust_expert'      => '{post:team:seo-senior}',
		),
	),
	array(
		'key'    => 'pending',
		'title'  => '[Новый вопрос с сайта]',
		'status' => 'pending',
		'terms'  => array( 'question_topic' => array( 'indexing' ) ),
		'fields' => array(
			'trust_question' => '[Вопрос читателя из формы «Задать вопрос SEO-шнику»: ждёт ответа специалиста и утверждения редактором.]',
			'trust_asker'    => '[Имя]',
		),
	),
);

return $trust_questions;
