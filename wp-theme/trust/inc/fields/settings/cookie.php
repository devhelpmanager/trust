<?php
/**
 * Настройки сайта → Cookie и счётчики (только администратор; ТЗ, раздел 14).
 *
 * Тексты плашки, страница политики cookie, срок хранения выбора, версия согласия, код счётчиков
 * (аналитика) и рекламных пикселей. Код вставляется на страницу только после согласия посетителя
 * на свою категорию (Trust\Cookie, assets/js/cookie.js).
 *
 * @package Trust
 */

use Trust\Cookie;

defined( 'ABSPATH' ) || exit;

$trust_defaults = Cookie::defaults();

return array(
	'label'  => __( 'Cookie и счётчики', 'trust' ),
	'cap'    => 'manage_options',
	'fields' => array(
		array(
			'name'    => 'cookie_heading',
			'label'   => __( 'Плашка cookie', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Плашка появляется внизу экрана при первом визите: «Принять», «Принять только обязательные», «Отклонить всё». Пока посетитель не нажал «Принять», код счётчиков и пикселей на странице не выполняется. Открыть плашку снова можно ссылкой на адрес #cookie-settings — её можно добавить в любое меню («Произвольная ссылка»).', 'trust' ),
		),
		array(
			'name'        => 'cookie_title',
			'label'       => __( 'Заголовок плашки', 'trust' ),
			'type'        => 'text',
			'placeholder' => $trust_defaults['title'],
			'maxlength'   => 80,
		),
		array(
			'name'         => 'cookie_text',
			'label'        => __( 'Текст плашки', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 3,
			'placeholder'  => $trust_defaults['text'],
			'instructions' => __( 'Пусто — текст по умолчанию (серым). Слова в {фигурных скобках} станут ссылкой на страницу политики cookie.', 'trust' ),
		),
		array(
			'name'          => 'cookie_policy',
			'label'         => __( 'Страница политики cookie', 'trust' ),
			'type'          => 'post_object',
			'post_type'     => array( 'page' ),
			'return_format' => 'id',
			'allow_null'    => 1,
			'ui'            => 1,
			'wrapper'       => array( 'width' => 50 ),
		),
		array(
			'name'         => 'cookie_days',
			'label'        => __( 'Хранить выбор посетителя, дней', 'trust' ),
			'type'         => 'number',
			'min'          => 1,
			'max'          => 3650,
			'step'         => 1,
			'placeholder'  => (string) Cookie::DAYS,
			'instructions' => __( 'Через этот срок плашка появится снова.', 'trust' ),
			'wrapper'      => array( 'width' => 25 ),
		),
		array(
			'name'         => 'cookie_version',
			'label'        => __( 'Версия согласия', 'trust' ),
			'type'         => 'text',
			'placeholder'  => '1',
			'maxlength'    => 20,
			'instructions' => __( 'Изменили политику cookie или добавили счётчик — поменяйте версию (например, 2): плашка снова появится у всех посетителей.', 'trust' ),
			'wrapper'      => array( 'width' => 25 ),
		),
		array(
			'name'         => 'cookie_code_analytics',
			'label'        => __( 'Код счётчиков (аналитика)', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 6,
			'new_lines'    => '',
			'class'        => 'code',
			'instructions' => __( 'Например, код Яндекс Метрики целиком, вместе с <script>. Выполняется только у посетителей, которые нажали «Принять».', 'trust' ),
		),
		array(
			'name'         => 'cookie_code_marketing',
			'label'        => __( 'Код рекламных пикселей', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 6,
			'new_lines'    => '',
			'class'        => 'code',
			'instructions' => __( 'Пиксели рекламных систем (VK, Meta и т. п.). Выполняется только у посетителей, которые нажали «Принять».', 'trust' ),
		),
	),
);
