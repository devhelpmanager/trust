<?php
/**
 * Приём форм: REST POST /wp-json/trust/v1/forms/{type} и запасной путь без JavaScript.
 *
 * @package Trust
 */

namespace Trust\Forms;

use Trust\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Порядок проверок: форма существует → лимит частоты → ловушка → токен (подпись, срок,
 * минимальное время) → nonce → поля и согласия (те же правила и сообщения, что в браузере) →
 * капча → заявка (Trust\Forms\Leads). После сохранения — действие trust_form_submitted
 * (уведомления — шаг 3.6).
 */
final class Handler {

	/**
	 * Пространство имён REST.
	 */
	public const REST_NAMESPACE = 'trust/v1';

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
		add_action( 'admin_post_nopriv_trust_form', array( self::class, 'fallback' ) );
		add_action( 'admin_post_trust_form', array( self::class, 'fallback' ) );
	}

	/**
	 * Маршруты REST.
	 */
	public static function routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/forms/token',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'token' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/forms/(?P<type>[a-z0-9_-]+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'rest' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Свежие токен и nonce для формы (скрипт берёт их при первом фокусе: страница могла
	 * долго лежать в кэше).
	 */
	public static function token(): \WP_REST_Response {
		$response = new \WP_REST_Response(
			array(
				'token' => Protection::token(),
				'nonce' => wp_create_nonce( Protection::NONCE ),
			)
		);
		$response->set_headers( wp_get_nocache_headers() );
		return $response;
	}

	/**
	 * Отправка формы через REST.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 */
	public static function rest( \WP_REST_Request $request ): \WP_REST_Response {
		$result   = self::process( (string) $request['type'], $request->get_body_params() + $request->get_query_params() );
		$response = new \WP_REST_Response( $result['body'], $result['status'] );
		$response->set_headers( wp_get_nocache_headers() );
		return $response;
	}

	/**
	 * Отправка формы без JavaScript: та же обработка, затем возврат на страницу с отметкой
	 * ?trust_form=sent|error&trust_form_type={тип} (форма показывает сообщение — Forms::status()).
	 */
	public static function fallback(): void {
		// Проверку nonce выполняет process() — как и для REST.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$type   = isset( $_POST['trust_form_type'] ) ? sanitize_key( wp_unslash( $_POST['trust_form_type'] ) ) : '';
		$params = wp_unslash( $_POST );
		// phpcs:enable
		$result = self::process( $type, is_array( $params ) ? $params : array() );

		$back = wp_get_referer();
		$back = $back ? $back : home_url( '/' );
		$back = remove_query_arg( array( 'trust_form', 'trust_form_type' ), $back );
		$args = array(
			'trust_form'      => $result['body']['ok'] ? 'sent' : 'error',
			'trust_form_type' => $type,
		);
		wp_safe_redirect( add_query_arg( $args, $back ) . '#form-' . $type );
		exit;
	}

	/**
	 * Ответ с ошибкой.
	 *
	 * Коды: not_found, rate_limited, stale (токен или nonce устарели — скрипт берёт новые и
	 * повторяет отправку), too_fast (скрипт ждёт и повторяет), invalid (поля), captcha, server.
	 *
	 * @param int    $status  Код HTTP.
	 * @param string $code    Код ошибки.
	 * @param string $message Сообщение для формы.
	 * @param array  $errors  Ошибки полей: имя поля => сообщение.
	 */
	private static function fail( int $status, string $code, string $message, array $errors = array() ): array {
		return array(
			'status' => $status,
			'body'   => array(
				'ok'      => false,
				'code'    => $code,
				'message' => $message,
				'errors'  => (object) $errors,
			),
		);
	}

	/**
	 * Обработка формы.
	 *
	 * @param string $type   Тип формы.
	 * @param array  $params Отправленные поля (без слэшей).
	 * @return array{status: int, body: array}
	 */
	public static function process( string $type, array $params ): array {
		$form = Forms::type( $type );
		if ( ! $form ) {
			return self::fail( 404, 'not_found', __( 'Такой формы нет на сайте.', 'trust' ) );
		}

		if ( Protection::rate_limited() ) {
			return self::fail( 429, 'rate_limited', __( 'Слишком много заявок с вашего адреса. Попробуйте через 10 минут или позвоните нам.', 'trust' ) );
		}

		// Ловушка заполнена — это бот: отвечаем «успешно», но ничего не сохраняем.
		if ( '' !== trim( (string) ( $params[ Protection::HONEYPOT ] ?? '' ) ) ) {
			return array(
				'status' => 200,
				'body'   => array( 'ok' => true ),
			);
		}

		$token = Protection::check_token( (string) ( $params['_trust_token'] ?? '' ) );
		if ( 'invalid' === $token || ! wp_verify_nonce( (string) ( $params['_trust_nonce'] ?? '' ), Protection::NONCE ) ) {
			return self::fail( 403, 'stale', __( 'Страница устарела. Обновите её и отправьте форму ещё раз.', 'trust' ) );
		}
		if ( 'fast' === $token ) {
			return self::fail( 400, 'too_fast', __( 'Форма отправлена слишком быстро. Подождите несколько секунд и отправьте ещё раз.', 'trust' ) );
		}

		$values = array();
		$errors = array();
		foreach ( $form['fields'] as $name => $field ) {
			$value = self::clean( $field, $params[ $name ] ?? '' );
			if ( ! self::valid( $field, $value ) ) {
				$errors[ $name ] = $field['error'];
				continue;
			}
			$values[ $name ] = $value;
		}
		$consents = array();
		foreach ( $form['consents'] as $name => $consent ) {
			if ( empty( $params[ $name ] ) ) {
				$errors[ $name ] = $consent['error'];
				continue;
			}
			$consents[ $name ] = $consent['label'];
		}
		if ( $errors ) {
			return self::fail( 400, 'invalid', __( 'Проверьте поля формы.', 'trust' ), $errors );
		}

		if ( ! empty( $form['captcha'] ) && ! Protection::verify_captcha( (string) ( $params['captcha'] ?? '' ) ) ) {
			return self::fail( 400, 'captcha', __( 'Подтвердите, что вы не робот.', 'trust' ), array( 'captcha' => __( 'Подтвердите, что вы не робот', 'trust' ) ) );
		}

		$lead = Leads::create( $type, $form, $values, $consents, $params );
		if ( is_wp_error( $lead ) ) {
			return self::fail( 500, 'server', __( 'Не удалось сохранить заявку. Попробуйте ещё раз или позвоните нам.', 'trust' ) );
		}

		/**
		 * Форма принята и заявка сохранена (уведомления, CRM).
		 *
		 * @param int    $lead   ID заявки.
		 * @param string $type   Тип формы.
		 * @param array  $values Значения полей.
		 */
		do_action( 'trust_form_submitted', $lead, $type, $values );

		return array(
			'status' => 200,
			'body'   => array(
				'ok'   => true,
				'lead' => $lead,
			),
		);
	}

	/**
	 * Значение поля после очистки.
	 *
	 * @param array $field Описание поля.
	 * @param mixed $value Отправленное значение.
	 * @return string|bool
	 */
	private static function clean( array $field, $value ) {
		if ( 'checkbox' === $field['type'] ) {
			return ! empty( $value );
		}
		$raw   = is_scalar( $value ) ? (string) $value : '';
		$value = 'textarea' === $field['type'] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		// Теги вырезаны, но текст после одиночного «<» («цена < 1000 & быстро») очистка
		// экранирует (&lt;, &amp;) — возвращаем символы: хранится текст, при выводе он экранируется.
		if ( str_contains( $raw, '<' ) ) {
			$value = wp_specialchars_decode( $value, ENT_QUOTES );
		}
		return trim( $value );
	}

	/**
	 * Проверка значения по правилам поля.
	 *
	 * @param array       $field Описание поля.
	 * @param string|bool $value Значение после очистки.
	 */
	private static function valid( array $field, $value ): bool {
		if ( 'checkbox' === $field['type'] ) {
			return empty( $field['required'] ) || true === $value;
		}
		if ( '' === $value ) {
			return empty( $field['required'] );
		}

		$length = mb_strlen( $value );
		if ( ( isset( $field['min'] ) && $length < $field['min'] ) || $length > ( $field['max'] ?? 2000 ) ) {
			return false;
		}

		switch ( $field['type'] ) {
			case 'tel':
				return self::is_phone( $value );
			case 'email':
				return (bool) is_email( $value );
			case 'contact':
				return (bool) is_email( $value ) || self::is_phone( $value );
			case 'url':
				$host = wp_parse_url( preg_match( '#^https?://#i', $value ) ? $value : 'https://' . $value, PHP_URL_HOST );
				return is_string( $host ) && str_contains( $host, '.' );
			case 'choice':
				return isset( $field['options'][ $value ] );
		}
		return true;
	}

	/**
	 * Похоже ли на телефон: 10–15 цифр.
	 *
	 * @param string $value Значение.
	 */
	private static function is_phone( string $value ): bool {
		$digits = strlen( (string) preg_replace( '/\D/', '', $value ) );
		return $digits >= 10 && $digits <= 15 && ! preg_match( '/[^\d\s()+\-.]/', $value );
	}
}
