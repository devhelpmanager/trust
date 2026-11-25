<?php
/**
 * Реквизиты компании: «Настройки сайта → Реквизиты».
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Строки реквизитов и карточка компании.
 */
final class Requisites {

	/**
	 * Строки реквизитов: название и значение (пустые пропускаются).
	 *
	 * @return array<int, array{label: string, value: string}>
	 */
	public static function rows(): array {
		$rows = array();
		foreach ( (array) Settings::get( 'requisites', array() ) as $row ) {
			$label = trim( (string) ( $row['label'] ?? '' ) );
			$value = trim( (string) ( $row['value'] ?? '' ) );
			if ( '' !== $label && '' !== $value ) {
				$rows[] = array(
					'label' => $label,
					'value' => $value,
				);
			}
		}
		return $rows;
	}

	/**
	 * Адрес файла «Карточка компании, PDF» или ''.
	 */
	public static function card_url(): string {
		$id = (int) Settings::get( 'requisites_card', 0 );
		return $id ? (string) wp_get_attachment_url( $id ) : '';
	}

	/**
	 * Реквизиты строками «Название: значение» — для кнопки «Скопировать».
	 */
	public static function as_text(): string {
		return implode( "\n", array_map( static fn( array $row ): string => $row['label'] . ': ' . $row['value'], self::rows() ) );
	}
}
