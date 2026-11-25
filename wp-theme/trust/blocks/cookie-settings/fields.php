<?php
/**
 * Поля блока «Настройки cookie» (ТЗ, п. 8.9 и раздел 14; макет o-nas/politika-cookie.html):
 * подписи категорий и кнопок. Сам выбор хранится там же, где выбор плашки cookie (cookie
 * trust_consent, assets/js/cookie.js).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;

return array(
	array(
		'name'    => 'cookie_note',
		'label'   => __( 'Как работает', 'trust' ),
		'type'    => 'message',
		'message' => __( 'Ссылки на #cookie-settings (кнопка «Настроить cookie» первого экрана, меню) на этой странице ведут к блоку, на других — открывают плашку cookie. Какие счётчики относятся к категориям — «Настройки сайта → Cookie и счётчики».', 'trust' ),
	),
	Blocks::text_field( 'title', __( 'Заголовок (H3)', 'trust' ), __( 'Настройки cookie', 'trust' ), 60 ),
	Blocks::text_field( 'necessary_label', __( 'Необходимые: название', 'trust' ), __( 'Необходимые', 'trust' ), 40 ),
	Blocks::text_field( 'necessary_text', __( 'Необходимые: пояснение', 'trust' ), __( 'Нужны для работы Сайта, отключить нельзя', 'trust' ), 120 ),
	Blocks::text_field( 'always', __( 'Плашка «Всегда включены»', 'trust' ), __( 'Всегда включены', 'trust' ), 30 ),
	Blocks::text_field( 'analytics_label', __( 'Аналитические: название', 'trust' ), __( 'Аналитические', 'trust' ), 40 ),
	Blocks::text_field( 'analytics_text', __( 'Аналитические: пояснение', 'trust' ), __( 'Яндекс Метрика: статистика посещений', 'trust' ), 120 ),
	Blocks::text_field( 'marketing_label', __( 'Рекламные: название', 'trust' ), __( 'Рекламные', 'trust' ), 40 ),
	Blocks::text_field( 'marketing_text', __( 'Рекламные: пояснение', 'trust' ), __( 'Показ рекламы тем, кто уже был на Сайте', 'trust' ), 120 ),
	Blocks::text_field( 'save', __( 'Кнопка «Сохранить выбор»', 'trust' ), __( 'Сохранить выбор', 'trust' ), 30 ),
	Blocks::text_field( 'accept_all', __( 'Кнопка «Принять все»', 'trust' ), __( 'Принять все', 'trust' ), 30 ),
	Blocks::text_field( 'only_necessary', __( 'Кнопка «Только необходимые»', 'trust' ), __( 'Только необходимые', 'trust' ), 30 ),
);
