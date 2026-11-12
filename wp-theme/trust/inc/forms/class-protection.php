<?php
/**
 * Защита форм от спама (ТЗ, раздел 12.2).
 *
 * @package Trust
 */

namespace Trust\Forms;

use Trust\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * - Токен: время выдачи и подпись (HMAC с солью сайта) — подделать нельзя; по нему считается
 *   минимальное время заполнения. Скрипт форм берёт свежий токен при первом фокусе в форме,
 *   поэтому кэш страниц не мешает.
 * - nonce WordPress.
 * - Скрытое поле-ловушка: заполнено — заявка тихо отбрасывается.
 * - Ограничение частоты с одного IP.
 * - Капча: Google reCAPTCHA v2 или Яндекс SmartCaptcha — проверка токена на сервере.
 */
final class Protection {

	/**
	 * Действие nonce форм.
	 */
	public const NONCE = 'trust_form';

	/**
	 * Имя поля-ловушки.
	 */
	public const HONEYPOT = 'trust_website';

	/**
	 * Минимальное время заполнения формы, секунд.
	 */
	public const MIN_SECONDS = 3;

	/**
	 * Срок жизни токена, секунд.
	 */
	public const TOKEN_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Сколько отправок форм разрешено с одного IP за RATE_WINDOW.
	 */
	public const RATE_LIMIT = 8;

	/**
	 * Окно лимита частоты, секунд.
	 */
	public const RATE_WINDOW = 10 * MINUTE_IN_SECONDS;

	/**
	 * Провайдеры капчи.
	 */
	public const CAPTCHA_PROVIDERS = array(
		'none'         => 'Без капчи',
		'recaptcha'    => 'Google reCAPTCHA v2 («Я не робот»)',
		'smartcaptcha' => 'Яндекс SmartCaptcha',
	);

	/**
	 * Подпись строки секретом сайта.
	 *
	 * @param string $data Строка.
	 */
	private static function sign( string $data ): string {
		return hash_hmac( 'sha256', $data, wp_salt( 'nonce' ) );
	}

	/**
	 * Новый токен: «время-подпись».
	 */
	public static function token(): string {
		$time = (string) time();
		return $time . '-' . self::sign( $time . '|' . self::NONCE );
	}

	/**
	 * Проверка токена: 'ok', 'invalid' (подделан или устарел) или 'fast' (заполнено слишком быстро).
	 *
	 * @param string $token Токен из формы.
	 */
	public static function check_token( string $token ): string {
		if ( ! preg_match( '/^(\d{10})-([a-f0-9]{64})$/', $token, $m ) || ! hash_equals( self::sign( $m[1] . '|' . self::NONCE ), $m[2] ) ) {
			return 'invalid';
		}
		$age = time() - (int) $m[1];
		if ( $age > self::TOKEN_TTL || $age < 0 ) {
			return 'invalid';
		}
		return $age < self::MIN_SECONDS ? 'fast' : 'ok';
	}

	/**
	 * Сети Cloudflare (cloudflare.com/ips): только от них принимается заголовок CF-Connecting-IP.
	 */
	private const CLOUDFLARE = array(
		'173.245.48.0/20',
		'103.21.244.0/22',
		'103.22.200.0/22',
		'103.31.4.0/22',
		'141.101.64.0/18',
		'108.162.192.0/18',
		'190.93.240.0/20',
		'188.114.96.0/20',
		'197.234.240.0/22',
		'198.41.128.0/17',
		'162.158.0.0/15',
		'104.16.0.0/13',
		'104.24.0.0/14',
		'172.64.0.0/13',
		'131.0.72.0/22',
		'2400:cb00::/32',
		'2606:4700::/32',
		'2803:f800::/32',
		'2405:b500::/32',
		'2405:8100::/32',
		'2a06:98c0::/29',
		'2c0f:f248::/32',
	);

	/**
	 * Входит ли адрес в сеть (IPv4 или IPv6).
	 *
	 * @param string $ip   Адрес.
	 * @param string $cidr Сеть «адрес/маска».
	 */
	private static function in_network( string $ip, string $cidr ): bool {
		list( $net, $bits ) = explode( '/', $cidr );

		$ip  = inet_pton( $ip );
		$net = inet_pton( $net );
		if ( false === $ip || false === $net || strlen( $ip ) !== strlen( $net ) ) {
			return false;
		}
		$bits  = (int) $bits;
		$bytes = intdiv( $bits, 8 );
		if ( substr( $ip, 0, $bytes ) !== substr( $net, 0, $bytes ) ) {
			return false;
		}
		$rest = $bits % 8;
		if ( ! $rest ) {
			return true;
		}
		$mask = 0xFF << ( 8 - $rest ) & 0xFF;
		return ( ord( $ip[ $bytes ] ) & $mask ) === ( ord( $net[ $bytes ] ) & $mask );
	}

	/**
	 * IP посетителя. За Cloudflare адрес посетителя — в заголовке CF-Connecting-IP (иначе у всех
	 * был бы адрес Cloudflare и лимит срабатывал бы на всех сразу). Заголовок принимается, только
	 * если запрос пришёл от Cloudflare или от своего прокси на сервере (nginx перед Apache):
	 * иначе бот, обратившийся к серверу напрямую, подставлял бы в заголовок любой адрес.
	 */
	public static function client_ip(): string {
		$remote = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( ! filter_var( $remote, FILTER_VALIDATE_IP ) ) {
			return '';
		}
		$cf = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) : '';
		if ( ! filter_var( $cf, FILTER_VALIDATE_IP ) ) {
			return $remote;
		}
		// Локальный или внутренний адрес — свой прокси на сервере.
		$trusted = ! filter_var( $remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
		foreach ( self::CLOUDFLARE as $cidr ) {
			$trusted = $trusted || self::in_network( $remote, $cidr );
		}
		return $trusted ? $cf : $remote;
	}

	/**
	 * Учитывает отправку и сообщает, превышен ли лимит частоты.
	 */
	public static function rate_limited(): bool {
		$key   = 'trust_form_rate_' . md5( self::client_ip() );
		$state = get_transient( $key );
		$state = is_array( $state ) ? $state : array(
			'count' => 0,
			'start' => time(),
		);

		++$state['count'];
		$left = max( 1, self::RATE_WINDOW - ( time() - (int) $state['start'] ) );
		set_transient( $key, $state, $left );

		/**
		 * Сколько отправок форм с одного IP разрешено за 10 минут.
		 *
		 * @param int $limit Лимит.
		 */
		return $state['count'] > (int) apply_filters( 'trust_form_rate_limit', self::RATE_LIMIT );
	}

	/**
	 * Настроенная капча: провайдер и ключи; null — капча не настроена (формы работают без неё).
	 *
	 * @return array{provider: string, site_key: string, secret: string}|null
	 */
	public static function captcha(): ?array {
		$provider = (string) Settings::get( 'forms_captcha', 'none' );
		$keys     = array(
			'recaptcha'    => array( 'recaptcha_site_key', 'recaptcha_secret_key' ),
			'smartcaptcha' => array( 'smartcaptcha_client_key', 'smartcaptcha_server_key' ),
		);
		if ( ! isset( $keys[ $provider ] ) ) {
			return null;
		}
		$site   = trim( (string) Settings::get( $keys[ $provider ][0], '' ) );
		$secret = trim( (string) Settings::get( $keys[ $provider ][1], '' ) );
		if ( '' === $site || '' === $secret ) {
			return null;
		}
		return array(
			'provider' => $provider,
			'site_key' => $site,
			'secret'   => $secret,
		);
	}

	/**
	 * Проверяет ответ капчи на сервере провайдера.
	 *
	 * @param string $response Токен капчи из формы.
	 */
	public static function verify_captcha( string $response ): bool {
		$captcha = self::captcha();
		if ( ! $captcha ) {
			return true;
		}
		if ( '' === $response ) {
			return false;
		}

		if ( 'recaptcha' === $captcha['provider'] ) {
			$result = wp_remote_post(
				'https://www.google.com/recaptcha/api/siteverify',
				array(
					'timeout' => 10,
					'body'    => array(
						'secret'   => $captcha['secret'],
						'response' => $response,
						'remoteip' => self::client_ip(),
					),
				)
			);
			$data   = json_decode( (string) wp_remote_retrieve_body( $result ), true );
			return is_array( $data ) && ! empty( $data['success'] );
		}

		$result = wp_remote_post(
			'https://smartcaptcha.yandexcloud.net/validate',
			array(
				'timeout' => 10,
				'body'    => array(
					'secret' => $captcha['secret'],
					'token'  => $response,
					'ip'     => self::client_ip(),
				),
			)
		);
		$data   = json_decode( (string) wp_remote_retrieve_body( $result ), true );
		return is_array( $data ) && 'ok' === ( $data['status'] ?? '' );
	}
}
