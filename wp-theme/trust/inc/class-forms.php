<?php
/**
 * Формы сайта: описания форм, общие поля разметки, ссылки на документы (ТЗ, раздел 12).
 *
 * @package Trust
 */

namespace Trust;

use Trust\Forms\Protection;

defined( 'ABSPATH' ) || exit;

/**
 * Описание формы (types()): поля с правилами проверки и сообщениями — одни и те же в браузере
 * и на сервере; галочки согласий; нужна ли капча. Формы блоков добавляются фильтром
 * trust_form_types на шагах этих блоков. Приём заявок — Trust\Forms\Handler, хранение —
 * Trust\Forms\Leads, защита — Trust\Forms\Protection.
 */
final class Forms {

	/**
	 * Описания форм.
	 *
	 * Тип поля: text, textarea, tel, email, url, contact (телефон или email), choice, checkbox.
	 * Правила: required, min, max (длина), options (для choice). error — сообщение, как в макетах.
	 *
	 * @return array<string, array{label: string, captcha: bool, fields: array, consents: array, name: string, contact: string[]}>
	 */
	public static function types(): array {
		$types = array(
			'callback' => array(
				'label'    => __( 'Обратный звонок', 'trust' ),
				'captcha'  => false,
				'fields'   => array(
					'name'  => array(
						'label'    => __( 'Имя', 'trust' ),
						'type'     => 'text',
						'required' => true,
						'max'      => 100,
						'error'    => __( 'Укажите, как к вам обращаться', 'trust' ),
					),
					'phone' => array(
						'label'    => __( 'Телефон', 'trust' ),
						'type'     => 'tel',
						'required' => true,
						/* translators: %s: пример номера телефона. */
						'error'    => sprintf( __( 'Проверьте номер телефона, например %s', 'trust' ), self::phone_example() ),
					),
				),
				'consents' => array(
					'consent' => array(
						'label' => __( 'Согласие на обработку персональных данных', 'trust' ),
						'error' => __( 'Отметьте согласие — без него мы не сможем ответить на заявку', 'trust' ),
					),
				),
				// Поле с именем и поля с контактом — для колонок списка заявок.
				'name'     => 'name',
				'contact'  => array( 'phone' ),
			),
		);

		/**
		 * Формы сайта: блоки с формами добавляют свои описания.
		 *
		 * @param array $types Описания форм.
		 */
		return (array) apply_filters( 'trust_form_types', $types );
	}

	/**
	 * Описание формы или null.
	 *
	 * @param string $type Тип формы.
	 */
	public static function type( string $type ): ?array {
		$types = self::types();
		return $types[ $type ] ?? null;
	}

	/**
	 * Скрытые поля формы: тип, токен (время и подпись), nonce, ловушка для ботов.
	 * Без JavaScript форма отправляется на admin-post.php с этими полями.
	 *
	 * @param string $type Тип формы.
	 */
	public static function hidden_fields( string $type ): void {
		printf( '<input type="hidden" name="trust_form_type" value="%s">', esc_attr( $type ) );
		echo '<input type="hidden" name="action" value="trust_form">';
		printf( '<input type="hidden" name="_trust_token" value="%s">', esc_attr( Protection::token() ) );
		printf( '<input type="hidden" name="_trust_nonce" value="%s">', esc_attr( wp_create_nonce( Protection::NONCE ) ) );
		// Ловушка: люди поле не видят и не заполняют, боты — заполняют.
		printf(
			'<div class="form__hp" aria-hidden="true"><label>%s <input type="text" name="%s" value="" tabindex="-1" autocomplete="off"></label></div>',
			esc_html__( 'Не заполняйте это поле', 'trust' ),
			esc_attr( Protection::HONEYPOT )
		);
	}

	/**
	 * Строка состояния формы. Без JavaScript после отправки обработчик возвращает на страницу
	 * с ?trust_form=sent|error&trust_form_type={тип} — строка показывает результат.
	 * Со скриптом форм в неё выводятся сообщения об ошибках отправки.
	 *
	 * @param string $type Тип формы.
	 */
	public static function status( string $type ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- только выбор сообщения.
		$result = isset( $_GET['trust_form'], $_GET['trust_form_type'] ) && sanitize_key( wp_unslash( $_GET['trust_form_type'] ) ) === $type
			? sanitize_key( wp_unslash( $_GET['trust_form'] ) )
			: '';
		// phpcs:enable
		$messages = array(
			'sent'  => __( 'Заявка отправлена. Мы свяжемся с вами в ближайшее время.', 'trust' ),
			'error' => __( 'Заявка не отправлена: проверьте поля формы и отправьте её ещё раз или позвоните нам.', 'trust' ),
		);
		if ( isset( $messages[ $result ] ) ) {
			printf( '<p class="form__status%s" role="alert">%s</p>', 'sent' === $result ? ' form__status--ok' : '', esc_html( $messages[ $result ] ) );
			return;
		}
		echo '<p class="form__status" role="alert" hidden></p>';
	}

	/**
	 * Место для капчи, если она нужна форме и настроена («Настройки сайта → Формы»).
	 * Виджет загружает скрипт форм при первом касании формы.
	 *
	 * @param string $type Тип формы.
	 */
	public static function captcha( string $type ): void {
		$form = self::type( $type );
		if ( ! $form || empty( $form['captcha'] ) || ! Protection::captcha() ) {
			return;
		}
		echo '<div class="field">';
		echo '<div class="form__captcha" data-trust-captcha aria-describedby="form-' . esc_attr( $type ) . '-captcha-error"></div>';
		printf( '<span class="field__error" id="form-%s-captcha-error" hidden></span>', esc_attr( $type ) );
		echo '</div>';
	}

	/**
	 * Настройки скрипта форм: адреса REST и открытый ключ капчи (секретный — только на сервере).
	 */
	public static function script_config(): array {
		$captcha = Protection::captcha();
		return array(
			'endpoint' => rest_url( Forms\Handler::REST_NAMESPACE . '/forms/' ),
			'captcha'  => $captcha ? array(
				'provider' => $captcha['provider'],
				'siteKey'  => $captcha['site_key'],
			) : null,
			'messages' => array(
				'sent'    => __( 'Заявка отправлена. Мы свяжемся с вами в ближайшее время.', 'trust' ),
				'network' => __( 'Не удалось отправить заявку: нет связи с сайтом. Проверьте интернет и попробуйте ещё раз.', 'trust' ),
				'failed'  => __( 'Не удалось отправить заявку. Попробуйте ещё раз или позвоните нам.', 'trust' ),
				'captcha' => __( 'Подтвердите, что вы не робот', 'trust' ),
			),
		);
	}

	/**
	 * Адрес обработчика форм без JavaScript.
	 */
	public static function fallback_url(): string {
		return admin_url( 'admin-post.php' );
	}

	/**
	 * Страница «Согласие на обработку персональных данных» —
	 * «Настройки сайта → Блоки по умолчанию → Страница согласия».
	 */
	public static function consent_url(): string {
		$page = (int) Settings::get( 'consent_page', 0 );
		return $page && 'publish' === get_post_status( $page ) ? (string) get_permalink( $page ) : '';
	}

	/**
	 * Дата редакции страницы согласия (для записи факта согласия в заявке): дата её
	 * последнего изменения.
	 */
	public static function consent_revision(): string {
		$page = (int) Settings::get( 'consent_page', 0 );
		return $page ? (string) get_post_modified_time( 'Y-m-d', false, $page ) : '';
	}

	/**
	 * Пример номера телефона: подсказка в полях «Телефон» и в сообщении об ошибке.
	 * Один для всех форм сайта (по просьбе заказчика — международный формат).
	 */
	public static function phone_example(): string {
		/**
		 * Пример номера телефона в формах.
		 *
		 * @param string $example Пример.
		 */
		return (string) apply_filters( 'trust_phone_example', '+1 000 000 00 00' );
	}

	/**
	 * Политика конфиденциальности — стандартная настройка WordPress
	 * «Настройки → Конфиденциальность».
	 */
	public static function privacy_url(): string {
		return (string) get_privacy_policy_url();
	}
}
