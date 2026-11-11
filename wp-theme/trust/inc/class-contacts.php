<?php
/**
 * Контакты компании из «Настроек сайта → Контакты» в готовом для вывода виде.
 *
 * Шапка, подвал, блоки и микроразметка берут контакты только отсюда.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Телефоны, email, мессенджеры, часы работы и адрес.
 */
final class Contacts {

	/**
	 * Телефоны: номер, подпись, основной ли, ссылка tel:.
	 *
	 * @return array<int, array{number: string, label: string, is_main: bool, href: string}>
	 */
	public static function phones(): array {
		$phones = array();
		foreach ( (array) Settings::get( 'phones', array() ) as $row ) {
			$number = trim( (string) ( $row['number'] ?? '' ) );
			if ( '' === $number ) {
				continue;
			}
			$phones[] = array(
				'number'  => $number,
				'label'   => trim( (string) ( $row['label'] ?? '' ) ),
				'is_main' => ! empty( $row['is_main'] ),
				'href'    => self::tel_href( $number ),
			);
		}
		return $phones;
	}

	/**
	 * Основной телефон: отмеченный «Основной», иначе первый.
	 *
	 * @return array{number: string, label: string, is_main: bool, href: string}|null
	 */
	public static function main_phone(): ?array {
		$phones = self::phones();
		foreach ( $phones as $phone ) {
			if ( $phone['is_main'] ) {
				return $phone;
			}
		}
		return $phones[0] ?? null;
	}

	/**
	 * Адреса email: адрес, подпись, ссылка mailto:.
	 *
	 * @return array<int, array{email: string, label: string, href: string}>
	 */
	public static function emails(): array {
		$emails = array();
		foreach ( (array) Settings::get( 'emails', array() ) as $row ) {
			$email = sanitize_email( (string) ( $row['email'] ?? '' ) );
			if ( '' === $email ) {
				continue;
			}
			$emails[] = array(
				'email' => $email,
				'label' => trim( (string) ( $row['label'] ?? '' ) ),
				'href'  => 'mailto:' . $email,
			);
		}
		return $emails;
	}

	/**
	 * Основной email — первый в списке.
	 *
	 * @return array{email: string, label: string, href: string}|null
	 */
	public static function main_email(): ?array {
		return self::emails()[0] ?? null;
	}

	/**
	 * Telegram: имя для показа (@name) и ссылка https://t.me/name.
	 *
	 * @return array{handle: string, url: string}|null
	 */
	public static function telegram(): ?array {
		return self::parse_telegram( (string) Settings::get( 'telegram', '' ) );
	}

	/**
	 * WhatsApp: номер и ссылка https://wa.me/<цифры>.
	 *
	 * @return array{number: string, url: string}|null
	 */
	public static function whatsapp(): ?array {
		return self::parse_whatsapp( (string) Settings::get( 'whatsapp', '' ) );
	}

	/**
	 * Часы работы для показа.
	 */
	public static function hours(): string {
		return trim( (string) Settings::get( 'hours', '' ) );
	}

	/**
	 * Части адреса офиса.
	 *
	 * @return array{postal_code: string, city: string, street: string, office: string}
	 */
	public static function address(): array {
		$address = (array) Settings::get( 'address', array() );
		return array(
			'postal_code' => trim( (string) ( $address['postal_code'] ?? '' ) ),
			'city'        => trim( (string) ( $address['city'] ?? '' ) ),
			'street'      => trim( (string) ( $address['street'] ?? '' ) ),
			'office'      => trim( (string) ( $address['office'] ?? '' ) ),
		);
	}

	/**
	 * Адрес одной строкой: «Город, улица, дом, этаж, офис» (без индекса).
	 */
	public static function address_line(): string {
		$address = self::address();
		return implode( ', ', array_filter( array( $address['city'], $address['street'], $address['office'] ) ) );
	}

	/**
	 * Ссылка tel: из номера в любом написании: «+7 (000) 000-00-00» → tel:+70000000000,
	 * «8 900 …» → tel:+7900….
	 *
	 * @param string $number Номер телефона.
	 */
	public static function tel_href( string $number ): string {
		$digits = preg_replace( '/\D+/', '', $number );
		if ( 11 === strlen( $digits ) && '8' === $digits[0] ) {
			$digits = '7' . substr( $digits, 1 );
		}
		return 'tel:+' . $digits;
	}

	/**
	 * Разбирает поле Telegram: @name, name или ссылку t.me/name.
	 *
	 * @param string $value Значение поля.
	 * @return array{handle: string, url: string}|null
	 */
	public static function parse_telegram( string $value ): ?array {
		$value = trim( $value );
		if ( '' === $value ) {
			return null;
		}
		if ( preg_match( '~^(?:https?://)?(?:www\.)?(?:t\.me|telegram\.me)/(?:s/)?([^/?#\s]+)~i', $value, $m ) ) {
			$name = $m[1];
		} else {
			$name = ltrim( $value, '@' );
		}
		return array(
			'handle' => '@' . $name,
			'url'    => 'https://t.me/' . rawurlencode( $name ),
		);
	}

	/**
	 * Разбирает поле WhatsApp: номер в любом написании или ссылку wa.me.
	 *
	 * @param string $value Значение поля.
	 * @return array{number: string, url: string}|null
	 */
	public static function parse_whatsapp( string $value ): ?array {
		$value = trim( $value );
		if ( '' === $value ) {
			return null;
		}
		if ( preg_match( '~^https?://~i', $value ) ) {
			return array(
				'number' => '',
				'url'    => $value,
			);
		}
		$digits = substr( self::tel_href( $value ), strlen( 'tel:+' ) );
		return array(
			'number' => $value,
			'url'    => 'https://wa.me/' . $digits,
		);
	}
}
