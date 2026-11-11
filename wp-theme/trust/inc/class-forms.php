<?php
/**
 * Формы сайта: общие помощники.
 *
 * Сейчас — ссылки для галочки согласия. Отправка заявок (REST, проверка, антиспам,
 * сохранение) появится на шаге 3.5.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Ссылки на документы, которые нужны каждой форме с персональными данными.
 */
final class Forms {

	/**
	 * Страница «Согласие на обработку персональных данных» —
	 * «Настройки сайта → Блоки по умолчанию → Страница согласия».
	 */
	public static function consent_url(): string {
		$page = (int) Settings::get( 'consent_page', 0 );
		return $page && 'publish' === get_post_status( $page ) ? (string) get_permalink( $page ) : '';
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
