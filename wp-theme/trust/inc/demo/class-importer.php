<?php
/**
 * Импорт и удаление демо-контента.
 *
 * Данные лежат в папке demo/. Демо-контент пополняется на каждом шаге разработки:
 * сейчас — «Настройки сайта».
 *
 * @package Trust
 */

namespace Trust\Demo;

use Trust\Fields;
use Trust\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Записывает и удаляет демо-контент, не трогая то, что изменено вручную.
 */
final class Importer {

	/**
	 * Опция с отпечатками записанных демо-настроек: имя поля => хеш значения.
	 */
	public const SETTINGS_RECORD = 'trust_demo_settings';

	/**
	 * Записывает демо-настройки.
	 *
	 * Поле заполняется, если оно пустое или в нём всё ещё значение прошлого демо-импорта.
	 * Изменённое вручную поле пропускается.
	 *
	 * @return array{written: string[], skipped: string[]}
	 */
	public static function import_settings(): array {
		$data   = require TRUST_DIR . '/demo/settings.php';
		$record = (array) get_option( self::SETTINGS_RECORD, array() );
		$result = array(
			'written' => array(),
			'skipped' => array(),
		);

		foreach ( $data as $name => $value ) {
			$current  = self::current( $name );
			$is_empty = self::is_empty( $current );
			$is_demo  = isset( $record[ $name ] ) && self::hash( $current ) === $record[ $name ];

			if ( ! $is_empty && ! $is_demo ) {
				$result['skipped'][] = $name;
				continue;
			}

			update_field( Fields::key( Settings::PREFIX, $name ), $value, 'option' );
			$record[ $name ]     = self::hash( self::current( $name ) );
			$result['written'][] = $name;
		}

		update_option( self::SETTINGS_RECORD, $record, false );
		return $result;
	}

	/**
	 * Удаляет демо-настройки, которые не меняли вручную.
	 *
	 * @return array{removed: string[], kept: string[]}
	 */
	public static function remove_settings(): array {
		$record = (array) get_option( self::SETTINGS_RECORD, array() );
		$result = array(
			'removed' => array(),
			'kept'    => array(),
		);

		foreach ( $record as $name => $hash ) {
			if ( self::hash( self::current( $name ) ) === $hash ) {
				delete_field( Fields::key( Settings::PREFIX, $name ), 'option' );
				$result['removed'][] = $name;
			} else {
				$result['kept'][] = $name;
			}
		}

		delete_option( self::SETTINGS_RECORD );
		return $result;
	}

	/**
	 * Текущее значение поля настроек (в том виде, как его отдаёт SCF).
	 *
	 * @param string $name Имя поля.
	 * @return mixed
	 */
	private static function current( string $name ) {
		return get_field( Fields::key( Settings::PREFIX, $name ), 'option' );
	}

	/**
	 * Пустое ли значение (в том числе группа или повторитель только из пустых значений).
	 *
	 * @param mixed $value Значение.
	 */
	private static function is_empty( $value ): bool {
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( ! self::is_empty( $item ) ) {
					return false;
				}
			}
			return true;
		}
		return null === $value || false === $value || '' === $value;
	}

	/**
	 * Отпечаток значения для сравнения «не меняли ли вручную».
	 *
	 * @param mixed $value Значение.
	 */
	private static function hash( $value ): string {
		return md5( (string) wp_json_encode( $value ) );
	}
}
