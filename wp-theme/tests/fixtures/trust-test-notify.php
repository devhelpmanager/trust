<?php
/**
 * Только для автотеста notifications.js: копируется в mu-plugins стенда на время теста.
 *
 * - Почта: письма не уходят, а записываются в опцию trust_test_mails; при опции
 *   trust_test_mail_fail wp_mail «не отправляет».
 * - Telegram (api.telegram.org): токен bad-token — 401 Unauthorized; sendMessage записывается
 *   в trust_test_telegram; getUpdates — два чата (у токена empty-token — ни одного).
 * - Вебхук: https://crm.example/hook — 200, запрос записывается в trust_test_webhook;
 *   https://crm.example/fail — 500.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

/**
 * Дописывает запись в опцию-журнал.
 *
 * @param string $option Опция.
 * @param array  $entry  Запись.
 */
function trust_test_log( string $option, array $entry ): void {
	$log   = (array) get_option( $option, array() );
	$log[] = $entry;
	update_option( $option, $log, false );
}

add_filter(
	'pre_wp_mail',
	static function ( $pre, array $atts ) {
		trust_test_log(
			'trust_test_mails',
			array(
				'to'      => (array) $atts['to'],
				'subject' => $atts['subject'],
				'message' => $atts['message'],
				'headers' => (array) $atts['headers'],
			)
		);
		return ! get_option( 'trust_test_mail_fail' );
	},
	10,
	2
);

add_filter(
	'pre_http_request',
	static function ( $pre, array $args, string $url ) {
		$reply = static fn( int $code, array $data ): array => array(
			'headers'  => array(),
			'body'     => wp_json_encode( $data ),
			'response' => array(
				'code'    => $code,
				'message' => '',
			),
			'cookies'  => array(),
		);

		if ( preg_match( '#^https://api\.telegram\.org/bot([^/]+)/(\w+)$#', $url, $m ) ) {
			if ( 'bad-token' === $m[1] ) {
				return $reply(
					401,
					array(
						'ok'          => false,
						'description' => 'Unauthorized',
					)
				);
			}
			if ( 'getUpdates' === $m[2] ) {
				$chats = 'empty-token' === $m[1] ? array() : array(
					array(
						'update_id' => 1,
						'message'   => array(
							'chat' => array(
								'id'         => 111,
								'first_name' => 'Анна',
								'last_name'  => 'Петрова',
								'type'       => 'private',
							),
						),
					),
					array(
						'update_id' => 2,
						'message'   => array(
							'chat' => array(
								'id'    => -100222,
								'title' => 'Заявки',
								'type'  => 'supergroup',
							),
						),
					),
				);
				return $reply(
					200,
					array(
						'ok'     => true,
						'result' => $chats,
					)
				);
			}
			trust_test_log( 'trust_test_telegram', (array) $args['body'] + array( 'token' => $m[1] ) );
			return $reply(
				200,
				array(
					'ok'     => true,
					'result' => array( 'message_id' => 1 ),
				)
			);
		}

		if ( 'https://crm.example/hook' === $url ) {
			trust_test_log(
				'trust_test_webhook',
				array(
					'headers' => (array) $args['headers'],
					'body'    => (string) $args['body'],
				)
			);
			return $reply( 200, array( 'ok' => true ) );
		}
		if ( 'https://crm.example/fail' === $url ) {
			return $reply( 500, array( 'error' => 'down' ) );
		}
		return $pre;
	},
	10,
	3
);
