<?php
/**
 * Демо-отзывы: заглушки из макетов o-nas/otzyvy.html (список «Что говорят клиенты»),
 * portfolio-blog-404/keys.html (блок «Слово клиенту» кейса «Ремонт квартир»), portfolio.html
 * («Что говорят клиенты», 2 карточки) и seo-bloki/15-otzyvy.html (блок «Отзывы клиентов»).
 *
 * Ключ — постоянный идентификатор (по нему кейсы ссылаются на отзыв: {post:review:ключ}).
 * terms — ключи демо-терминов из demo/terms.php. Шаг 4.12: в списке «Что говорят клиенты» —
 * оценки, даты (8 отзывов списка — самые новые, как в макете), сканы писем у пяти отзывов
 * («Оригинал письма»); 3 видеоотзыва (ссылка-заглушка, обложки нет — тёмная заглушка из макета)
 * и 6 благодарственных писем (скан-заглушка demo/images/letter-scan.jpg).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_texts   = array(
	'seo'     => '[Отзыв о результате: какая была задача по продвижению, что сделали, как изменились трафик и заявки — 3–5 предложений.]',
	'sites'   => '[Отзыв о результате: какой сайт был нужен, как шла работа над дизайном и разработкой, что получилось — 3–5 предложений.]',
	'ppc'     => '[Отзыв о результате: какие были цели рекламы, что настроили, как изменилась стоимость заявки — 3–5 предложений.]',
	'support' => '[Отзыв о результате: какие задачи решали по сайту, как быстро реагировала команда — 3–5 предложений.]',
);
$trust_results = array(
	'seo'     => array( '[+N% трафика]', '[за N месяцев]' ),
	'sites'   => array( '[Срок: N недель]', '[Конверсия +N%]' ),
	'ppc'     => array( '[−N% цена заявки]', '[N заявок в месяц]' ),
	'support' => array( '[N задач закрыто]', '[Работаем N лет]' ),
);

$trust_day   = static fn( int $days ): string => wp_date( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
$trust_scan  = '{image:letter-scan.jpg|Благодарственное письмо — скан-заглушка}';
$trust_older = 40; // Отзывы кейсов и блока «Отзывы клиентов» — старше отзывов списка.

$trust_reviews = array(
	array(
		'key'    => 'case-repair',
		'date'   => $trust_day( $trust_older ),
		'title'  => '[Имя Фамилия], [Ремонт&Дизайн]',
		'terms'  => array( 'service_type' => array( 'seo', 'sites' ) ),
		'fields' => array(
			'trust_kind'     => 'text',
			'trust_name'     => '[Имя Фамилия]',
			'trust_position' => 'Директор',
			'trust_company'  => '[Ремонт&Дизайн]',
			'trust_text'     => '[Отзыв клиента о проекте: что изменилось для бизнеса, как шла работа — 3–4 предложения. Публикуйте только с согласия клиента.]',
			// Скан письма — ссылка «Благодарственное письмо» в «Слове клиенту» на странице кейса (макет keys.html).
			'trust_letter'   => '{image:letter-scan.jpg|Благодарственное письмо компании «Ремонт&Дизайн»}',
		),
	),
);

// Отзыв к кейсу «Стоматология» — вторая карточка «Что говорят клиенты» в портфолио (вид «2 карточки»).
$trust_reviews[] = array(
	'key'    => 'case-dental',
	'date'   => $trust_day( $trust_older + 1 ),
	'title'  => '[Имя Фамилия], [Компания] — стоматология',
	'terms'  => array( 'service_type' => array( 'seo', 'geo' ) ),
	'fields' => array(
		'trust_kind'     => 'text',
		'trust_name'     => '[Имя Фамилия]',
		'trust_position' => '[Должность]',
		'trust_company'  => '[Компания]',
		'trust_text'     => '[Отзыв клиента о работе команды: сроки, отчёты, общение — 2–3 предложения.]',
	),
);

// Блок «Отзывы клиентов», вид «3 карточки» (seo-bloki/15-otzyvy.html): ссылка «Оригинал» — заглушка.
foreach (
	array(
		'result'  => array( 'о результате', '[Отзыв о результате: с какой задачей клиент пришёл и что изменилось в трафике и заявках после работы с вами.]' ),
		'process' => array( 'о процессе', '[Отзыв о процессе: как проходило сотрудничество, насколько понятными были отчёты и соблюдались ли сроки.]' ),
		'team'    => array( 'о команде', '[Отзыв о команде: кто вёл проект, как быстро отвечали на вопросы и решали возникающие задачи.]' ),
	) as $trust_key => list( $trust_about, $trust_text )
) {
	$trust_reviews[] = array(
		'key'    => 'block-' . $trust_key,
		'date'   => $trust_day( $trust_older + 2 ),
		'title'  => '[Имя Фамилия], [Компания] — ' . $trust_about,
		'terms'  => array( 'service_type' => array( 'seo' ) ),
		'fields' => array(
			'trust_kind'     => 'text',
			'trust_name'     => '[Имя Фамилия]',
			'trust_position' => '[Должность]',
			'trust_company'  => '[Компания]',
			'trust_text'     => $trust_text,
			'trust_source'   => 'https://example.com/review-' . $trust_key,
		),
	);
}

// Список на странице «Отзывы о нас»: услуги и «Оригинал письма» в порядке макета; самые новые.
$trust_letters = array( true, false, true, false, true, true, true, false );
foreach ( array( 'seo', 'sites', 'ppc', 'seo', 'support', 'sites', 'seo', 'ppc' ) as $trust_i => $trust_service ) {
	$trust_reviews[] = array(
		'key'    => 'list-' . ( $trust_i + 1 ),
		'date'   => $trust_day( $trust_i + 1 ),
		'title'  => sprintf( '[Имя Фамилия], [Компания] — %d', $trust_i + 1 ),
		'terms'  => array( 'service_type' => array( $trust_service ) ),
		'fields' => array_filter(
			array(
				'trust_kind'     => 'text',
				'trust_name'     => '[Имя Фамилия]',
				'trust_position' => '[Должность]',
				'trust_company'  => '[Компания]',
				'trust_text'     => $trust_texts[ $trust_service ],
				'trust_results'  => array_map( static fn( string $text ): array => array( 'text' => $text ), $trust_results[ $trust_service ] ),
				'trust_rating'   => 5,
				'trust_letter'   => $trust_letters[ $trust_i ] ? $trust_scan : '',
			)
		),
	);
}

// Видеоотзывы (блок «Видеоотзывы»): ссылка — заглушка YouTube, обложки нет.
for ( $trust_i = 1; $trust_i <= 3; $trust_i++ ) {
	$trust_reviews[] = array(
		'key'    => 'video-' . $trust_i,
		'date'   => $trust_day( 10 + $trust_i ),
		'title'  => sprintf( '[Имя Фамилия], [Компания] — видео %d', $trust_i ),
		'terms'  => array( 'service_type' => array( 'seo' ) ),
		'fields' => array(
			'trust_kind'           => 'video',
			'trust_name'           => '[Имя Фамилия]',
			'trust_position'       => '[Должность]',
			'trust_company'        => '[Компания]',
			'trust_video_url'      => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
			'trust_video_title'    => '[Название видео: о чём рассказывает клиент]',
			'trust_video_duration' => '[м:сс]',
		),
	);
}

// Благодарственные письма (блок «Благодарственные письма»): скан-заглушка.
for ( $trust_i = 1; $trust_i <= 6; $trust_i++ ) {
	$trust_reviews[] = array(
		'key'    => 'letter-' . $trust_i,
		'date'   => $trust_day( 20 + $trust_i ),
		'title'  => sprintf( '[Компания] — письмо %d', $trust_i ),
		'fields' => array(
			'trust_kind'    => 'letter',
			'trust_company' => '[Компания]',
			'trust_letter'  => $trust_scan,
		),
	);
}

return $trust_reviews;
