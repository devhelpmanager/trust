<?php
/**
 * Демо-отзывы: заглушки из макетов o-nas/otzyvy.html (список «Что говорят клиенты»)
 * и portfolio-blog-404/keys.html (блок «Слово клиенту» кейса «Ремонт квартир»).
 *
 * Ключ — постоянный идентификатор (по нему кейсы ссылаются на отзыв: {post:review:ключ}).
 * terms — ключи демо-терминов из demo/terms.php. Видеоотзывы и благодарственные письма
 * появятся вместе с картинками и видео в демо-контенте (шаг 4.12).
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

$trust_reviews = array(
	array(
		'key'    => 'case-repair',
		'title'  => '[Имя Фамилия], [Ремонт&Дизайн]',
		'terms'  => array( 'service_type' => array( 'seo', 'sites' ) ),
		'fields' => array(
			'trust_kind'     => 'text',
			'trust_name'     => '[Имя Фамилия]',
			'trust_position' => 'Директор',
			'trust_company'  => '[Ремонт&Дизайн]',
			'trust_text'     => '[Отзыв клиента о проекте: что изменилось для бизнеса, как шла работа — 3–4 предложения. Публикуйте только с согласия клиента.]',
		),
	),
);

// Список на странице «Отзывы о нас»: услуги в порядке макета.
foreach ( array( 'seo', 'sites', 'ppc', 'seo', 'support', 'sites', 'seo', 'ppc' ) as $trust_i => $trust_service ) {
	$trust_reviews[] = array(
		'key'    => 'list-' . ( $trust_i + 1 ),
		'title'  => sprintf( '[Имя Фамилия], [Компания] — %d', $trust_i + 1 ),
		'terms'  => array( 'service_type' => array( $trust_service ) ),
		'fields' => array(
			'trust_kind'     => 'text',
			'trust_name'     => '[Имя Фамилия]',
			'trust_position' => '[Должность]',
			'trust_company'  => '[Компания]',
			'trust_text'     => $trust_texts[ $trust_service ],
			'trust_results'  => array_map( static fn( string $text ): array => array( 'text' => $text ), $trust_results[ $trust_service ] ),
		),
	);
}

return $trust_reviews;
