<?php
/**
 * Настройки сайта → Формы (только администратор).
 *
 * Капча (ТЗ, раздел 12.2): Google reCAPTCHA v2 или Яндекс SmartCaptcha. Ключи получает
 * владелец сайта в кабинете сервиса для домена сайта и вводит здесь; секретный ключ
 * хранится в базе сайта и в браузер не попадает. Пока капча не выбрана или ключи не
 * введены, формы защищены остальными способами (ловушка, время заполнения, лимит по IP, nonce).
 * Срок хранения заявок: старые заявки удаляются раз в сутки.
 *
 * @package Trust
 */

use Trust\Fields;
use Trust\Forms\Protection;

defined( 'ABSPATH' ) || exit;

$trust_provider = Fields::key( Trust\Settings::PREFIX, 'forms_captcha' );
$trust_when     = static fn( string $provider ): array => array(
	array(
		array(
			'field'    => $trust_provider,
			'operator' => '==',
			'value'    => $provider,
		),
	),
);

return array(
	'label'  => __( 'Формы', 'trust' ),
	'cap'    => 'manage_options',
	'fields' => array(
		array(
			'name'          => 'forms_captcha',
			'label'         => __( 'Капча', 'trust' ),
			'type'          => 'select',
			'choices'       => Protection::CAPTCHA_PROVIDERS,
			'default_value' => 'none',
			'instructions'  => __( 'Капча появляется в формах, где она включена (заявки с подробными данными). Окно «Заказать обратный звонок» работает без капчи. Без ключей капча не показывается, даже если выбрана.', 'trust' ),
		),
		array(
			'name'              => 'recaptcha_site_key',
			'label'             => __( 'reCAPTCHA: ключ сайта', 'trust' ),
			'type'              => 'text',
			'instructions'      => __( 'Ключи — на google.com/recaptcha/admin: тип «Задача reCAPTCHA (v2)», вариант «Флажок „Я не робот“», домен сайта без https://.', 'trust' ),
			'conditional_logic' => $trust_when( 'recaptcha' ),
		),
		array(
			'name'              => 'recaptcha_secret_key',
			'label'             => __( 'reCAPTCHA: секретный ключ', 'trust' ),
			'type'              => 'password',
			'instructions'      => __( 'Не передавайте секретный ключ в письмах и чатах: он нужен только здесь.', 'trust' ),
			'conditional_logic' => $trust_when( 'recaptcha' ),
		),
		array(
			'name'              => 'smartcaptcha_client_key',
			'label'             => __( 'SmartCaptcha: ключ клиента', 'trust' ),
			'type'              => 'text',
			'instructions'      => __( 'Ключи — в Yandex Cloud, раздел SmartCaptcha: создайте капчу для домена сайта.', 'trust' ),
			'conditional_logic' => $trust_when( 'smartcaptcha' ),
		),
		array(
			'name'              => 'smartcaptcha_server_key',
			'label'             => __( 'SmartCaptcha: ключ сервера', 'trust' ),
			'type'              => 'password',
			'instructions'      => __( 'Не передавайте ключ сервера в письмах и чатах: он нужен только здесь.', 'trust' ),
			'conditional_logic' => $trust_when( 'smartcaptcha' ),
		),
		array(
			'name'         => 'forms_retention_days',
			'label'        => __( 'Хранить заявки, дней', 'trust' ),
			'type'         => 'number',
			'min'          => 0,
			'max'          => 3650,
			'step'         => 1,
			'placeholder'  => __( 'не удалять', 'trust' ),
			'instructions' => __( 'Заявки старше этого срока удаляются автоматически раз в сутки — по требованиям к хранению персональных данных. Пусто или 0 — не удалять.', 'trust' ),
		),
	),
);
