<?php
/**
 * Помощники для описания полей Secure Custom Fields в коде.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Поля описываются массивами без ключей: ключи проставляются автоматически
 * по шаблону field_trust_<префикс>_<имя>[_<имя вложенного поля>], поэтому
 * они стабильны и одинаковы на всех сайтах.
 */
final class Fields {

	/**
	 * Проставляет ключи полям и вложенным полям (sub_fields у повторителей и групп).
	 *
	 * @param array  $fields Описания полей.
	 * @param string $prefix Префикс ключа: группа полей или родительское поле.
	 * @return array
	 */
	public static function with_keys( array $fields, string $prefix ): array {
		foreach ( $fields as $i => $field ) {
			$name = $field['name'] ?? (string) $i;
			$key  = $prefix . '_' . $name;

			if ( empty( $field['key'] ) ) {
				$fields[ $i ]['key'] = 'field_trust_' . $key;
			}
			if ( ! empty( $field['sub_fields'] ) ) {
				$fields[ $i ]['sub_fields'] = self::with_keys( $field['sub_fields'], $key );
			}
		}
		return $fields;
	}

	/**
	 * Ключ поля по тем же правилам, что в with_keys().
	 *
	 * @param string $prefix Префикс группы.
	 * @param string $name   Имя поля.
	 */
	public static function key( string $prefix, string $name ): string {
		return 'field_trust_' . $prefix . '_' . $name;
	}
}
