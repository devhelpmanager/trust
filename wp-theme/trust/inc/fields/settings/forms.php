<?php
/**
 * Настройки сайта → Формы (только администратор).
 *
 * Уведомления о заявках (ТЗ, раздел 12.2): письма — свои получатели у каждой формы; Telegram-бот;
 * вебхук CRM. Кнопка «Проверить уведомления» отправляет тестовую заявку во все заданные каналы.
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
use Trust\Forms;
use Trust\Forms\Notifications;
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

// Получатели писем: по умолчанию и у каждой формы (формы блоков добавляются сами).
$trust_recipients = array(
	array(
		'name'         => 'forms_emails',
		'label'        => __( 'Получатели писем по умолчанию', 'trust' ),
		'type'         => 'text',
		'placeholder'  => (string) get_option( 'admin_email' ),
		'instructions' => __( 'Адреса через запятую. Пусто — адрес администратора из «Настройки → Общие» (серым).', 'trust' ),
		'trust_emails' => true,
	),
);
foreach ( Forms::types() as $trust_type => $trust_form ) {
	$trust_recipients[] = array(
		'name'         => 'forms_emails_' . $trust_type,
		/* translators: %s: название формы. */
		'label'        => sprintf( __( 'Получатели: %s', 'trust' ), $trust_form['label'] ),
		'type'         => 'text',
		'placeholder'  => __( 'как по умолчанию', 'trust' ),
		'wrapper'      => array( 'width' => 50 ),
		'trust_emails' => true,
	);
}

return array(
	'label'  => __( 'Формы', 'trust' ),
	'cap'    => 'manage_options',
	'fields' => array(
		array(
			'name'    => 'forms_notify_heading',
			'label'   => __( 'Уведомления о заявках', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Каждая заявка сначала сохраняется в «Заявках», затем уходят уведомления: письмо, Telegram, вебхук CRM — те, что заполнены ниже. Ошибка канала не мешает сохранению; итог по каждому каналу виден на экране заявки.', 'trust' ),
		),
		...$trust_recipients,
		array(
			'name'         => 'telegram_bot_token',
			'label'        => __( 'Telegram: токен бота', 'trust' ),
			'type'         => 'password',
			'instructions' => __( 'В Telegram откройте @BotFather, команда /newbot — он пришлёт токен (вида 123456:ABC…). Не пересылайте токен в письмах и чатах.', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'         => 'telegram_chat_id',
			'label'        => __( 'Telegram: chat_id', 'trust' ),
			'type'         => 'text',
			'instructions' => __( 'Кому писать. Напишите боту любое сообщение (или добавьте бота в группу и напишите там), сохраните токен и нажмите «Проверить уведомления» — тема покажет chat_id. У групп он начинается с минуса.', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'         => 'webhook_url',
			'label'        => __( 'Вебхук CRM: адрес', 'trust' ),
			'type'         => 'url',
			'instructions' => __( 'На этот адрес уходит POST с данными заявки в JSON (формат — в README темы).', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'         => 'webhook_secret',
			'label'        => __( 'Вебхук CRM: секрет', 'trust' ),
			'type'         => 'password',
			'instructions' => __( 'Случайная строка от 32 символов. Подпись — заголовок X-Trust-Signature: «sha256=» и HMAC-SHA256 тела запроса этим секретом; CRM по ней проверяет, что запрос от сайта.', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'    => 'forms_notify_test',
			'label'   => __( 'Проверка', 'trust' ),
			'type'    => 'message',
			'message' => sprintf(
				'<a class="button" href="%s">%s</a> %s',
				esc_url( Notifications::test_url() ),
				esc_html__( 'Проверить уведомления', 'trust' ),
				esc_html__( 'Сначала сохраните настройки. Тестовая заявка уйдёт во все заданные каналы; в «Заявках» она не появится.', 'trust' )
			),
		),
		array(
			'name'         => 'forms_success_text',
			'label'        => __( 'Сообщение после отправки', 'trust' ),
			'type'         => 'text',
			'placeholder'  => Forms::SUCCESS_TEXT,
			'instructions' => __( 'Для форм без своего экрана «Заявка отправлена». У окна обратного звонка — свои тексты в «Блоках по умолчанию».', 'trust' ),
		),
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
