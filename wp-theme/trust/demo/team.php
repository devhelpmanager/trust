<?php
/**
 * Демо-сотрудники из макета o-nas/komanda.html: три руководителя («Руководство») и 12 специалистов
 * («Команда по направлениям»). Имена — заглушка «[Имя Фамилия]» из макета, поэтому адреса страниц
 * заданы по должности (slug). Текст «Об авторе» и «Пишет о» у старшего SEO-специалиста — из
 * макета страницы автора seo-blog/avtor.html.
 *
 * Год начала работы — условный (в макете «[N] лет»). Фото — вместе с картинками в демо-контенте
 * (шаг 4.11). Поле terms — ключи демо-терминов (demo/terms.php); {term_id:таксономия:ключ} —
 * ID демо-термина (теги «Пишет о»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

/**
 * Сотрудник.
 *
 * @param string $key      Ключ и адрес страницы.
 * @param string $position Должность.
 * @param string $dept     Ключ отдела или ''.
 * @param int    $since    Год начала работы в профессии.
 * @param array  $extra    Дополнительные поля.
 */
$trust_member = static function ( string $key, string $position, string $dept, int $since, array $extra = array() ): array {
	return array(
		'key'    => $key,
		'slug'   => $key,
		'title'  => '[Имя Фамилия]',
		'terms'  => $dept ? array( 'department' => array( $dept ) ) : array(),
		'fields' => array_merge(
			array(
				'trust_position'     => $position,
				'trust_career_start' => $since,
			),
			$extra
		),
	);
};

$trust_team = array(
	$trust_member(
		'ceo',
		'Основатель и генеральный директор',
		'',
		2010,
		array(
			'trust_leader'          => 1,
			'trust_experience_area' => 'в digital',
			'trust_summary'         => '[Опыт в digital — [N] лет. Отвечает за стратегию агентства и качество работ по всем направлениям.]',
			'trust_email'           => 'ceo@example.ru',
		)
	),
	$trust_member(
		'seo-lead',
		'Руководитель SEO-отдела',
		'seo',
		2013,
		array(
			'trust_leader'          => 1,
			'trust_experience_area' => 'в SEO',
			'trust_summary'         => '[Опыт в SEO — [N] лет. Утверждает стратегию продвижения и проверяет отчёты по каждому проекту.]',
		)
	),
	$trust_member(
		'dev-lead',
		'Руководитель отдела разработки',
		'dev',
		2014,
		array(
			'trust_leader'          => 1,
			'trust_experience_area' => 'в разработке',
			'trust_summary'         => '[Опыт в разработке — [N] лет. Отвечает за сроки и техническое качество сайтов.]',
		)
	),
	$trust_member(
		'seo-senior',
		'Старший SEO-специалист',
		'seo',
		2016,
		array(
			'trust_experience_area' => 'в SEO',
			'trust_about'           => '<p>[Имя] отвечает в [Бренд] за техническое SEO: аудит сайтов, индексацию и скорость загрузки. В поисковом продвижении — [N] лет, на счету — [N] технических аудитов для интернет-магазинов, сервисов и корпоративных сайтов.</p>' . "\n" .
				'<p>В блоге пишет о том, как поисковые системы сканируют и индексируют сайты, и объясняет технические вещи простым языком — с примерами и чек-листами, которые можно сразу применить.</p>' . "\n" .
				'<p>[Образование, сертификаты, выступления на конференциях]</p>',
			'trust_topic_tags'      => array( '{term_id:post_tag:index}', '{term_id:post_tag:robots}' ),
			'trust_topics'          => "Технический аудит\nСкорость загрузки\nМикроразметка",
		)
	),
	$trust_member( 'seo-specialist', 'SEO-специалист', 'seo', 2020, array( 'trust_experience_area' => 'в SEO' ) ),
	$trust_member( 'seo-specialist-2', 'SEO-специалист', 'seo', 2021, array( 'trust_experience_area' => 'в SEO' ) ),
	$trust_member( 'content', 'Контент-маркетолог', 'seo', 2019 ),
	$trust_member( 'frontend', 'Frontend-разработчик', 'dev', 2018 ),
	$trust_member( 'backend', 'Backend-разработчик', 'dev', 2017 ),
	$trust_member( 'qa', 'Тестировщик', 'dev', 2021 ),
	$trust_member( 'designer', 'UI/UX-дизайнер', 'design', 2018 ),
	$trust_member( 'ppc', 'Специалист по контекстной рекламе', 'ads', 2019 ),
	$trust_member( 'analyst', 'Веб-аналитик', 'ads', 2020 ),
	$trust_member( 'account', 'Аккаунт-менеджер', 'pm', 2019 ),
	$trust_member( 'pm', 'Проджект-менеджер', 'pm', 2017 ),
);

// Порядок вывода — как в макете.
foreach ( $trust_team as $trust_i => $trust_item ) {
	$trust_team[ $trust_i ]['menu_order'] = $trust_i + 1;
}

return $trust_team;
