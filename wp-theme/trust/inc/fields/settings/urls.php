<?php
/**
 * Настройки сайта → Адреса (только администратор).
 *
 * Ярлыки URL типов записей и таксономий (ТЗ, разделы 5.1, 5.2). Пустое поле — значение
 * по умолчанию (серая подсказка). При сохранении ярлык приводится к виду для адреса
 * (Trust\Urls::normalize()), правила перезаписи обновляются сами (Trust\Urls::maybe_flush()).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

/**
 * Поле ярлыка.
 *
 * @param string $type   Тип записи или таксономия.
 * @param string $label  Подпись.
 * @param string $append Что идёт в адресе после ярлыка.
 * @param string $note   Пояснение.
 */
$trust_field = static fn( string $type, string $label, string $append, string $note ): array => array(
	'name'         => Trust\Urls::FIELD_PREFIX . $type,
	'label'        => $label,
	'type'         => 'text',
	'placeholder'  => Trust\Urls::DEFAULTS[ $type ],
	'prepend'      => '/',
	'append'       => $append,
	'maxlength'    => 60,
	'instructions' => $note,
);

return array(
	'label'  => __( 'Адреса', 'trust' ),
	'cap'    => 'manage_options',
	'fields' => array(
		array(
			'name'    => 'urls_heading',
			'label'   => __( 'Ярлыки в адресах', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Пустое поле — используется значение по умолчанию (серым). Латиница, цифры, дефис и «/»; кириллица и пробелы при сохранении переводятся в латиницу и дефисы. Адреса обновляются сразу после сохранения. Старые ссылки на записи после смены ярлыка перестанут открываться — меняйте ярлыки до запуска сайта.', 'trust' ),
		),
		$trust_field(
			'case',
			__( 'Кейсы', 'trust' ),
			'/[кейс]/',
			__( 'Совпадает с адресом страницы со списком кейсов («Портфолио»).', 'trust' )
		),
		$trust_field(
			'team',
			__( 'Сотрудники', 'trust' ),
			'/[сотрудник]/',
			__( 'Совпадает с адресом страницы «Команда».', 'trust' )
		),
		$trust_field(
			'question',
			__( 'Вопросы SEO', 'trust' ),
			'/[вопрос]/',
			__( 'Совпадает с адресом страницы со списком вопросов.', 'trust' )
		),
		$trust_field(
			'category',
			__( 'Рубрики блога', 'trust' ),
			'/[рубрика]/',
			__( 'Адрес статьи задаётся в «Настройки → Постоянные ссылки» (/blog/%postname%/).', 'trust' )
		),
		$trust_field(
			'post_tag',
			__( 'Теги', 'trust' ),
			'/[тег]/',
			__( 'Страница тега показывает статьи и ответы с этим тегом.', 'trust' )
		),
	),
);
