<?php
/**
 * Только для автотеста leads.js: копируется в mu-plugins стенда на время теста.
 *
 * - Тестовая форма «test» со всеми типами полей, двумя согласиями и капчей — страница
 *   /?trust_test_form=1 (разметка как у будущих блоков с формами).
 * - Подмена серверов проверки капчи: ответ «good-token» — верный, остальные — нет;
 *   что пришло на проверку — в опции trust_test_captcha.
 * - Лимит частоты: 1000, а с заголовком X-Trust-Limit — его значение.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'trust_form_types',
	static function ( array $types ): array {
		$types['test'] = array(
			'label'    => 'Тестовая форма',
			'captcha'  => true,
			'fields'   => array(
				'site'    => array(
					'label'    => 'Сайт',
					'type'     => 'url',
					'required' => true,
					'error'    => 'Укажите адрес сайта',
				),
				'contact' => array(
					'label'    => 'Телефон или email',
					'type'     => 'contact',
					'required' => true,
					'error'    => 'Укажите телефон или email',
				),
				'email'   => array(
					'label' => 'Email для отчёта',
					'type'  => 'email',
					'error' => 'Проверьте email',
				),
				'mode'    => array(
					'label'    => 'Режим',
					'type'     => 'choice',
					'required' => true,
					'options'  => array(
						'audit' => 'Аудит',
						'full'  => 'Продвижение',
					),
					'error'    => 'Выберите режим',
				),
				'text'    => array(
					'label'    => 'Задача',
					'type'     => 'textarea',
					'required' => true,
					'min'      => 10,
					'max'      => 50,
					'error'    => 'Опишите задачу: от 10 до 50 знаков',
				),
				'share'   => array(
					'label' => 'Расшарить доступ',
					'type'  => 'checkbox',
					'error' => '',
				),
			),
			'consents' => array(
				'consent' => array(
					'label' => 'Согласие на обработку персональных данных',
					'error' => 'Отметьте согласие',
				),
				'publish' => array(
					'label' => 'Согласие на распространение',
					'error' => 'Отметьте согласие на распространение',
				),
			),
			'name'     => '',
			'contact'  => array( 'contact' ),
		);
		return $types;
	}
);

add_action(
	'wp_footer',
	static function (): void {
		if ( ! isset( $_GET['trust_test_form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$field = static function ( string $name, string $label, string $input ): void {
			printf(
				'<div class="field"><label class="field__label" for="t-%1$s">%2$s</label>%3$s<span class="field__error" id="t-%1$s-error" hidden></span></div>',
				esc_attr( $name ),
				esc_html( $label ),
				$input // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		};
		echo '<main style="max-width:480px;margin:40px auto;padding:0 16px">';
		echo '<form class="form" id="form-test" method="post" action="' . esc_url( Trust\Forms::fallback_url() ) . '" data-trust-form="test" data-success="test-success">';
		Trust\Forms::hidden_fields( 'test' );
		$field( 'site', 'Сайт', '<input class="field__input" id="t-site" name="site" type="text" data-type="url" required data-error="Укажите адрес сайта" aria-describedby="t-site-error">' );
		$field( 'contact', 'Телефон или email', '<input class="field__input" id="t-contact" name="contact" type="text" data-type="contact" required data-error="Укажите телефон или email" aria-describedby="t-contact-error">' );
		$field( 'email', 'Email для отчёта', '<input class="field__input" id="t-email" name="email" type="email" data-error="Проверьте email" aria-describedby="t-email-error">' );
		$field( 'mode', 'Режим', '<select class="field__input" id="t-mode" name="mode" required data-error="Выберите режим" aria-describedby="t-mode-error"><option value="">—</option><option value="audit">Аудит</option><option value="full">Продвижение</option></select>' );
		$field( 'text', 'Задача', '<textarea class="field__input" id="t-text" name="text" required minlength="10" maxlength="50" data-error="Опишите задачу: от 10 до 50 знаков" aria-describedby="t-text-error"></textarea>' );
		echo '<label class="check"><input class="check__input" type="checkbox" name="share" value="1"> <span>Расшарить доступ</span></label>';
		echo '<div class="field"><label class="check"><input class="check__input" id="t-consent" type="checkbox" name="consent" value="1" required data-error="Отметьте согласие" aria-describedby="t-consent-error"> <span>Согласие</span></label><span class="field__error" id="t-consent-error" hidden></span></div>';
		echo '<div class="field"><label class="check"><input class="check__input" id="t-publish" type="checkbox" name="publish" value="1" required data-error="Отметьте согласие на распространение" aria-describedby="t-publish-error"> <span>Распространение</span></label><span class="field__error" id="t-publish-error" hidden></span></div>';
		Trust\Forms::captcha( 'test' );
		echo '<button type="submit" class="form__submit">Отправить</button>';
		Trust\Forms::status( 'test' );
		echo '</form>';
		echo '<div id="test-success" hidden><p class="form-success__title">Готово</p><button type="button" class="btn btn--outline btn--sm" data-form-again>Отправить ещё</button></div>';
		echo '</main>';
	},
	5
);

add_filter(
	'pre_http_request',
	static function ( $pre, array $args, string $url ) {
		$hosts = array(
			'https://www.google.com/recaptcha/api/siteverify' => 'recaptcha',
			'https://smartcaptcha.yandexcloud.net/validate'   => 'smartcaptcha',
		);
		if ( ! isset( $hosts[ $url ] ) ) {
			return $pre;
		}
		$body = (array) $args['body'];
		update_option( 'trust_test_captcha', array( 'provider' => $hosts[ $url ] ) + $body, false );
		$good = 'good-token' === ( $body['response'] ?? $body['token'] ?? '' );
		$data = 'recaptcha' === $hosts[ $url ] ? array( 'success' => $good ) : array( 'status' => $good ? 'ok' : 'failed' );
		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( $data ),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
		);
	},
	10,
	3
);

add_filter(
	'trust_form_rate_limit',
	static fn(): int => isset( $_SERVER['HTTP_X_TRUST_LIMIT'] ) ? (int) $_SERVER['HTTP_X_TRUST_LIMIT'] : 1000
);
