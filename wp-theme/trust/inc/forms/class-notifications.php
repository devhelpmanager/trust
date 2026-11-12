<?php
/**
 * Уведомления о заявках: письмо, Telegram, вебхук CRM (ТЗ, раздел 12.2).
 *
 * @package Trust
 */

namespace Trust\Forms;

use Trust\Forms;
use Trust\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Каналы задаются в «Настройках сайта → Формы»:
 * - письмо через wp_mail: свои получатели у каждой формы, иначе получатели по умолчанию, иначе
 *   адрес администратора сайта;
 * - Telegram: сообщение от бота, если заданы токен и chat_id;
 * - вебхук CRM: POST JSON с подписью X-Trust-Signature (HMAC-SHA256 тела секретом), если задан адрес.
 *
 * Заявка сохраняется до уведомлений, ошибка канала её не отменяет. Уведомления уходят в конце
 * запроса, после ответа посетителю (на PHP-FPM — fastcgi_finish_request), поэтому медленный
 * канал не задерживает форму. Итог по каждому каналу записывается в заявку (trust_notify) и
 * виден на её экране; там же — «Отправить уведомления ещё раз».
 */
final class Notifications {

	/**
	 * Поле заявки с итогами уведомлений.
	 */
	public const META = 'trust_notify';

	/**
	 * Каналы и их названия.
	 */
	public const CHANNELS = array(
		'email'    => 'Письмо',
		'telegram' => 'Telegram',
		'webhook'  => 'Вебхук CRM',
	);

	/**
	 * Длина значения поля в сообщении Telegram (лимит сообщения — 4096 символов).
	 */
	private const TELEGRAM_VALUE = 700;

	/**
	 * Заявки, ждущие уведомлений в конце запроса.
	 *
	 * @var int[]
	 */
	private static array $queue = array();

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'trust_form_submitted', array( self::class, 'queue' ) );
		add_action( 'admin_post_trust_notify_test', array( self::class, 'test' ) );
		add_action( 'admin_post_trust_notify_resend', array( self::class, 'resend' ) );
		add_action( 'admin_notices', array( self::class, 'notices' ) );
		add_filter( 'acf/validate_value', array( self::class, 'validate_emails' ), 10, 3 );
	}

	/**
	 * Ставит заявку в очередь уведомлений.
	 *
	 * @param int $lead Заявка.
	 */
	public static function queue( int $lead ): void {
		if ( ! self::$queue ) {
			add_action( 'shutdown', array( self::class, 'flush' ), 1000 );
		}
		self::$queue[] = $lead;
	}

	/**
	 * В конце запроса: отдаёт ответ посетителю и отправляет уведомления.
	 */
	public static function flush(): void {
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		}
		foreach ( array_unique( self::$queue ) as $lead ) {
			self::send( $lead );
		}
		self::$queue = array();
	}

	/**
	 * Отправляет уведомления о заявке во все заданные каналы и записывает итог в заявку.
	 *
	 * @param int $lead Заявка.
	 * @return array<string, array{ok: bool, info: string, at: string}> Итог по каналам.
	 */
	public static function send( int $lead ): array {
		$data = self::data( $lead );
		if ( ! $data ) {
			return array();
		}
		$results = self::deliver( $data, 'lead.created' );
		update_post_meta( $lead, self::META, wp_slash( $results ) );
		return $results;
	}

	/**
	 * Отправка по каналам. Каналы без настроек пропускаются.
	 *
	 * @param array  $data  Данные заявки (data()).
	 * @param string $event Событие для вебхука: lead.created или lead.test.
	 * @return array<string, array{ok: bool, info: string, at: string}>
	 */
	private static function deliver( array $data, string $event ): array {
		$results = array();
		$at      = current_time( 'mysql' );
		$sends   = array(
			'email'    => static fn() => self::email( $data ),
			'telegram' => static fn() => self::telegram( $data ),
			'webhook'  => static fn() => self::webhook( $data, $event ),
		);
		foreach ( $sends as $channel => $send ) {
			try {
				$result = $send();
			} catch ( \Throwable $e ) {
				$result = array(
					'ok'   => false,
					'info' => $e->getMessage(),
				);
			}
			if ( null !== $result ) {
				$results[ $channel ] = $result + array( 'at' => $at );
			}
		}
		return $results;
	}

	/**
	 * Данные заявки для уведомлений.
	 *
	 * @param int $lead Заявка.
	 */
	public static function data( int $lead ): ?array {
		$post = get_post( $lead );
		if ( ! $post || 'lead' !== $post->post_type ) {
			return null;
		}
		$type = (string) get_post_meta( $lead, 'trust_form', true );
		return array(
			'id'         => $lead,
			'form'       => $type,
			'form_label' => Leads::form_label( $lead ),
			'title'      => html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ),
			'created_at' => get_post_time( 'c', false, $post ),
			'date'       => get_the_date( 'd.m.Y H:i', $post ),
			'status'     => Leads::status( $lead ),
			'name'       => (string) get_post_meta( $lead, 'trust_name', true ),
			'contact'    => (string) get_post_meta( $lead, 'trust_contact', true ),
			'fields'     => (array) get_post_meta( $lead, 'trust_fields', true ),
			'page'       => array(
				'url'   => (string) get_post_meta( $lead, 'trust_page', true ),
				'title' => (string) get_post_meta( $lead, 'trust_page_title', true ),
			),
			'source'     => (array) get_post_meta( $lead, 'trust_source', true ),
			'consent'    => (array) get_post_meta( $lead, 'trust_consent', true ),
			'admin_url'  => admin_url( 'post.php?post=' . $lead . '&action=edit' ),
		);
	}

	/**
	 * Пример заявки для кнопки «Проверить уведомления».
	 */
	private static function sample(): array {
		return array(
			'id'         => 0,
			'form'       => 'test',
			'form_label' => __( 'Тестовая заявка', 'trust' ),
			'title'      => __( 'Тестовая заявка: Проверка уведомлений', 'trust' ),
			'created_at' => current_time( 'c' ),
			'date'       => current_time( 'd.m.Y H:i' ),
			'status'     => 'new',
			'name'       => __( 'Проверка уведомлений', 'trust' ),
			'contact'    => Forms::phone_example(),
			'fields'     => array(
				'name'  => array(
					'label' => __( 'Имя', 'trust' ),
					'value' => __( 'Проверка уведомлений', 'trust' ),
				),
				'phone' => array(
					'label' => __( 'Телефон', 'trust' ),
					'value' => Forms::phone_example(),
				),
			),
			'page'       => array(
				'url'   => home_url( '/' ),
				'title' => __( 'Главная', 'trust' ),
			),
			'source'     => array(),
			'consent'    => array(),
			'admin_url'  => admin_url( 'edit.php?post_type=lead' ),
		);
	}

	/**
	 * Адреса из строки «через запятую»: только правильные.
	 *
	 * @param string $text Строка.
	 * @return string[]
	 */
	private static function emails( string $text ): array {
		return array_values( array_unique( array_filter( array_map( 'trim', (array) preg_split( '/[\s,;]+/', $text ) ), 'is_email' ) ) );
	}

	/**
	 * Получатели писем о форме: свои у формы → по умолчанию → администратор сайта.
	 *
	 * @param string $type Тип формы.
	 * @return string[]
	 */
	public static function recipients( string $type ): array {
		foreach ( array( 'forms_emails_' . $type, 'forms_emails' ) as $setting ) {
			$emails = self::emails( (string) Settings::get( $setting, '' ) );
			if ( $emails ) {
				return $emails;
			}
		}
		return self::emails( (string) get_option( 'admin_email' ) );
	}

	/**
	 * Строки «Подпись: значение» о заявке — для письма и Telegram.
	 *
	 * @param array $data Данные заявки.
	 * @return array<int, array{0: string, 1: string}>
	 */
	private static function lines( array $data ): array {
		$lines = array();
		foreach ( $data['fields'] as $field ) {
			if ( '' !== (string) ( $field['value'] ?? '' ) ) {
				$lines[] = array( (string) ( $field['label'] ?? '' ), (string) $field['value'] );
			}
		}
		$source = $data['source'];
		$utm    = array_filter( array( $source['utm_source'] ?? '', $source['utm_medium'] ?? '', $source['utm_campaign'] ?? '' ) );
		if ( $utm ) {
			$lines[] = array( __( 'Реклама (UTM)', 'trust' ), implode( ' / ', $utm ) );
		}
		if ( ! empty( $source['referrer'] ) ) {
			$lines[] = array( __( 'Откуда пришёл', 'trust' ), rawurldecode( (string) $source['referrer'] ) );
		}
		$lines[] = array( __( 'Дата', 'trust' ), (string) $data['date'] );
		return $lines;
	}

	/**
	 * Письмо получателям формы.
	 *
	 * @param array $data Данные заявки.
	 * @return array{ok: bool, info: string}
	 */
	private static function email( array $data ): array {
		$to = self::recipients( $data['form'] );
		if ( ! $to ) {
			return array(
				'ok'   => false,
				'info' => __( 'нет адреса получателя', 'trust' ),
			);
		}

		$who = '' !== $data['name'] ? $data['name'] : $data['contact'];
		/* translators: 1: форма, 2: имя или контакт. */
		$subject = trim( sprintf( __( 'Новая заявка: %1$s — %2$s', 'trust' ), $data['form_label'], $who ), ' —' );
		$body    = $data['form_label'] . "\n\n";
		foreach ( self::lines( $data ) as list( $label, $value ) ) {
			$body .= $label . ': ' . $value . "\n";
		}
		if ( $data['page']['url'] ) {
			$body .= __( 'Страница', 'trust' ) . ': ' . trim( $data['page']['title'] . ' ' . rawurldecode( $data['page']['url'] ) ) . "\n";
		}
		$body .= "\n" . __( 'Заявка в админке', 'trust' ) . ': ' . $data['admin_url'] . "\n";

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		$reply   = is_email( $data['contact'] );
		if ( $reply ) {
			$headers[] = 'Reply-To: ' . ( '' !== $data['name'] ? str_replace( array( '"', "\r", "\n" ), '', $data['name'] ) . ' <' . $reply . '>' : $reply );
		}

		/**
		 * Письмо о заявке перед отправкой.
		 *
		 * @param array $mail Получатели (to), тема (subject), текст (body), заголовки (headers).
		 * @param array $data Данные заявки.
		 */
		$mail = (array) apply_filters( 'trust_notify_email', compact( 'to', 'subject', 'body', 'headers' ), $data );

		$error  = '';
		$failed = static function ( \WP_Error $e ) use ( &$error ): void {
			$error = $e->get_error_message();
		};
		$from   = static fn(): string => Settings::brand();
		add_action( 'wp_mail_failed', $failed );
		add_filter( 'wp_mail_from_name', $from );
		$ok = wp_mail( $mail['to'], $mail['subject'], $mail['body'], $mail['headers'] );
		remove_filter( 'wp_mail_from_name', $from );
		remove_action( 'wp_mail_failed', $failed );

		return array(
			'ok'   => (bool) $ok,
			'info' => $ok ? implode( ', ', (array) $mail['to'] ) : ( $error ? $error : __( 'почта сайта не отправила письмо', 'trust' ) ),
		);
	}

	/**
	 * Текст для Telegram (HTML): экранируются только <, > и &.
	 *
	 * @param string $text Текст.
	 */
	private static function tg( string $text ): string {
		return htmlspecialchars( $text, ENT_NOQUOTES, 'UTF-8' );
	}

	/**
	 * Сообщение в Telegram.
	 *
	 * @param array $data Данные заявки.
	 * @return array{ok: bool, info: string}|null Null — канал не настроен.
	 */
	private static function telegram( array $data ): ?array {
		$token = trim( (string) Settings::get( 'telegram_bot_token', '' ) );
		$chat  = trim( (string) Settings::get( 'telegram_chat_id', '' ) );
		if ( '' === $token || '' === $chat ) {
			return null;
		}

		$text = '<b>' . self::tg( __( 'Новая заявка', 'trust' ) . ': ' . $data['form_label'] ) . "</b>\n";
		foreach ( self::lines( $data ) as list( $label, $value ) ) {
			$value = mb_strlen( $value ) > self::TELEGRAM_VALUE ? mb_substr( $value, 0, self::TELEGRAM_VALUE ) . '…' : $value;
			$text .= self::tg( $label ) . ': ' . self::tg( $value ) . "\n";
		}
		if ( $data['page']['url'] ) {
			$text .= self::tg( __( 'Страница', 'trust' ) ) . ': <a href="' . esc_url( $data['page']['url'] ) . '">' . self::tg( $data['page']['title'] ? $data['page']['title'] : rawurldecode( $data['page']['url'] ) ) . '</a>' . "\n";
		}
		$text .= "\n" . '<a href="' . esc_url( $data['admin_url'] ) . '">' . self::tg( __( 'Открыть заявку в админке', 'trust' ) ) . '</a>';

		/**
		 * Текст сообщения о заявке в Telegram (разметка HTML Telegram).
		 *
		 * @param string $text Текст.
		 * @param array  $data Данные заявки.
		 */
		$text = (string) apply_filters( 'trust_notify_telegram', $text, $data );

		$response = wp_remote_post(
			'https://api.telegram.org/bot' . $token . '/sendMessage',
			array(
				'timeout' => 10,
				'body'    => array(
					'chat_id'                  => $chat,
					'text'                     => $text,
					'parse_mode'               => 'HTML',
					'disable_web_page_preview' => 'true',
				),
			)
		);
		return self::telegram_result( $response, $token );
	}

	/**
	 * Итог запроса к Telegram: ответ API или ошибка связи (токен в тексте ошибки скрывается).
	 *
	 * @param array|\WP_Error $response Ответ.
	 * @param string          $token    Токен бота.
	 */
	private static function telegram_result( $response, string $token ): array {
		if ( is_wp_error( $response ) ) {
			return array(
				'ok'   => false,
				'info' => str_replace( $token, '***', $response->get_error_message() ),
			);
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( is_array( $body ) && ! empty( $body['ok'] ) ) {
			return array(
				'ok'   => true,
				'info' => __( 'отправлено', 'trust' ),
			);
		}
		$code = wp_remote_retrieve_response_code( $response );
		return array(
			'ok'   => false,
			'info' => trim( 'HTTP ' . $code . ' ' . ( is_array( $body ) ? (string) ( $body['description'] ?? '' ) : '' ) ),
		);
	}

	/**
	 * Чаты, которые писали боту (для подсказки chat_id).
	 *
	 * @return string[]|string Список «название (chat_id)» или текст ошибки.
	 */
	private static function telegram_chats() {
		$token    = trim( (string) Settings::get( 'telegram_bot_token', '' ) );
		$response = wp_remote_get( 'https://api.telegram.org/bot' . $token . '/getUpdates', array( 'timeout' => 10 ) );
		$body     = is_wp_error( $response ) ? null : json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['ok'] ) ) {
			$result = self::telegram_result( $response, $token );
			return $result['info'];
		}
		$chats = array();
		foreach ( (array) $body['result'] as $update ) {
			foreach ( array( 'message', 'channel_post', 'my_chat_member', 'edited_message' ) as $key ) {
				$chat = $update[ $key ]['chat'] ?? null;
				if ( is_array( $chat ) && isset( $chat['id'] ) ) {
					$name                          = $chat['title'] ?? trim( ( $chat['first_name'] ?? '' ) . ' ' . ( $chat['last_name'] ?? '' ) );
					$name                          = '' !== $name ? $name : ( $chat['username'] ?? '' );
					$chats[ (string) $chat['id'] ] = sprintf( '%s (chat_id: %s)', $name, $chat['id'] );
				}
			}
		}
		return array_values( $chats );
	}

	/**
	 * Вебхук CRM: POST JSON, подпись X-Trust-Signature = "sha256=" . HMAC-SHA256(тело, секрет).
	 *
	 * @param array  $data  Данные заявки.
	 * @param string $event Событие.
	 * @return array{ok: bool, info: string}|null Null — канал не настроен.
	 */
	private static function webhook( array $data, string $event ): ?array {
		$url = trim( (string) Settings::get( 'webhook_url', '' ) );
		if ( '' === $url ) {
			return null;
		}
		$payload = array(
			'event'   => $event,
			'site'    => home_url( '/' ),
			'sent_at' => current_time( 'c' ),
			'lead'    => $data,
		);

		/**
		 * Данные вебхука о заявке.
		 *
		 * @param array $payload Данные (JSON).
		 * @param array $data    Данные заявки.
		 */
		$payload = (array) apply_filters( 'trust_webhook_payload', $payload, $data );
		$body    = (string) wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		$headers = array(
			'Content-Type'  => 'application/json; charset=utf-8',
			'X-Trust-Event' => $event,
		);
		$secret  = (string) Settings::get( 'webhook_secret', '' );
		if ( '' !== $secret ) {
			$headers['X-Trust-Signature'] = 'sha256=' . hash_hmac( 'sha256', $body, $secret );
		}

		// Только внешние адреса: запросы во внутреннюю сеть сервера отклоняются.
		$response = wp_safe_remote_post(
			$url,
			array(
				'timeout' => 10,
				'headers' => $headers,
				'body'    => $body,
			)
		);
		if ( is_wp_error( $response ) ) {
			return array(
				'ok'   => false,
				'info' => $response->get_error_message(),
			);
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		return array(
			'ok'   => $code >= 200 && $code < 300,
			'info' => 'HTTP ' . $code,
		);
	}

	/**
	 * Итоги уведомлений заявки — для её экрана.
	 *
	 * @param int $lead Заявка.
	 */
	public static function summary( int $lead ): string {
		$results = (array) get_post_meta( $lead, self::META, true );
		$items   = array();
		foreach ( self::CHANNELS as $channel => $label ) {
			if ( ! isset( $results[ $channel ] ) ) {
				continue;
			}
			$result  = $results[ $channel ];
			$items[] = sprintf(
				'<span class="trust-notify trust-notify--%s">%s: %s</span>',
				! empty( $result['ok'] ) ? 'ok' : 'error',
				esc_html( $label ),
				esc_html( ( ! empty( $result['ok'] ) ? __( 'отправлено', 'trust' ) : __( 'ошибка', 'trust' ) ) . ( '' !== (string) ( $result['info'] ?? '' ) && ( empty( $result['ok'] ) || 'email' === $channel ) ? ' — ' . $result['info'] : '' ) )
			);
		}
		$text = $items ? implode( '<br>', $items ) : esc_html__( 'Ещё не отправлялись', 'trust' );
		if ( current_user_can( 'edit_post', $lead ) ) {
			$url   = wp_nonce_url( admin_url( 'admin-post.php?action=trust_notify_resend&lead=' . $lead ), 'trust_notify_resend_' . $lead );
			$text .= '<br><a class="trust-notify-resend" href="' . esc_url( $url ) . '">' . esc_html__( 'Отправить уведомления ещё раз', 'trust' ) . '</a>';
		}
		return $text;
	}

	/**
	 * «Отправить уведомления ещё раз» с экрана заявки.
	 */
	public static function resend(): void {
		$lead = isset( $_GET['lead'] ) ? absint( $_GET['lead'] ) : 0;
		check_admin_referer( 'trust_notify_resend_' . $lead );
		if ( ! $lead || ! current_user_can( 'edit_post', $lead ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'trust' ), 403 );
		}
		$results = self::send( $lead );
		$ok      = $results && ! in_array( false, array_column( $results, 'ok' ), true );
		wp_safe_redirect( add_query_arg( 'trust_notified', $ok ? 'ok' : 'error', admin_url( 'post.php?post=' . $lead . '&action=edit' ) ) );
		exit;
	}

	/**
	 * Ссылка «Проверить уведомления» для вкладки «Формы».
	 */
	public static function test_url(): string {
		return wp_nonce_url( admin_url( 'admin-post.php?action=trust_notify_test' ), 'trust_notify_test' );
	}

	/**
	 * «Проверить уведомления»: тестовая заявка во все заданные каналы (в базе не сохраняется).
	 * Если задан токен бота, а chat_id нет — показывает чаты, которые писали боту.
	 */
	public static function test(): void {
		check_admin_referer( 'trust_notify_test' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'trust' ), 403 );
		}
		$report = array(
			'results' => self::deliver( self::sample(), 'lead.test' ),
			'chats'   => null,
		);
		if ( '' !== trim( (string) Settings::get( 'telegram_bot_token', '' ) ) && '' === trim( (string) Settings::get( 'telegram_chat_id', '' ) ) ) {
			$report['chats'] = self::telegram_chats();
		}
		set_transient( 'trust_notify_test_' . get_current_user_id(), $report, 5 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'trust_notify_test', '1', Settings::admin_url() ) );
		exit;
	}

	/**
	 * Сообщения: итог проверки уведомлений и повторной отправки.
	 */
	public static function notices(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- только выбор сообщения.
		if ( isset( $_GET['trust_notified'] ) ) {
			$ok = 'ok' === sanitize_key( wp_unslash( $_GET['trust_notified'] ) );
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				$ok ? 'success' : 'warning',
				esc_html( $ok ? __( 'Уведомления отправлены ещё раз.', 'trust' ) : __( 'Уведомления отправлены ещё раз, но не все каналы сработали — см. «Уведомления» в заявке.', 'trust' ) )
			);
		}
		if ( ! isset( $_GET['trust_notify_test'] ) ) {
			return;
		}
		// phpcs:enable
		$key    = 'trust_notify_test_' . get_current_user_id();
		$report = get_transient( $key );
		delete_transient( $key );
		if ( ! is_array( $report ) ) {
			return;
		}

		$items = array();
		foreach ( self::CHANNELS as $channel => $label ) {
			$result = $report['results'][ $channel ] ?? null;
			if ( ! $result ) {
				$items[] = esc_html( $label . ': ' . __( 'не настроен', 'trust' ) );
				continue;
			}
			$items[] = esc_html( $label . ': ' . ( $result['ok'] ? __( 'отправлено', 'trust' ) : __( 'ошибка', 'trust' ) ) . ( '' !== $result['info'] ? ' — ' . $result['info'] : '' ) );
		}
		if ( is_array( $report['chats'] ) ) {
			$items[] = $report['chats']
				? esc_html__( 'Telegram: боту писали чаты —', 'trust' ) . ' ' . esc_html( implode( '; ', $report['chats'] ) ) . '. ' . esc_html__( 'Вставьте нужный chat_id в поле «Telegram: chat_id» и сохраните.', 'trust' )
				: esc_html__( 'Telegram: боту пока никто не писал. Напишите боту любое сообщение (или добавьте его в группу и напишите там) и нажмите «Проверить уведомления» ещё раз.', 'trust' );
		} elseif ( is_string( $report['chats'] ) ) {
			$items[] = esc_html( __( 'Telegram: не удалось получить чаты бота', 'trust' ) . ' — ' . $report['chats'] );
		}
		$ok = ! in_array( false, array_column( $report['results'], 'ok' ), true );
		printf(
			'<div class="notice notice-%s is-dismissible trust-notify-test"><p><strong>%s</strong></p><p>%s</p></div>',
			$ok ? 'success' : 'warning',
			esc_html__( 'Проверка уведомлений (тестовая заявка, в базе не сохранена)', 'trust' ),
			implode( '<br>', $items ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- строки экранированы выше.
		);
	}

	/**
	 * Проверка полей с адресами («через запятую») в настройках.
	 *
	 * @param bool|string $valid Итог проверки.
	 * @param mixed       $value Значение.
	 * @param array       $field Поле.
	 * @return bool|string
	 */
	public static function validate_emails( $valid, $value, $field = array() ) {
		if ( true !== $valid || empty( $field['trust_emails'] ) || ! is_string( $value ) || '' === trim( $value ) ) {
			return $valid;
		}
		$bad = array_filter( array_map( 'trim', (array) preg_split( '/[\s,;]+/', $value ) ), static fn( string $email ): bool => '' !== $email && ! is_email( $email ) );
		/* translators: %s: неверные адреса. */
		return $bad ? sprintf( __( 'Проверьте адреса: %s', 'trust' ), implode( ', ', $bad ) ) : $valid;
	}
}
