<?php
/**
 * Документы (страницы типа «Документ»): данные из их первого экрана.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Дата редакции документа — поле «Редакция от» блока «Первый экран документа».
 */
final class Documents {

	/**
	 * Дата редакции документа (Y-m-d) из блока «Первый экран документа» или '' — блока или даты нет.
	 *
	 * @param int $post_id Страница.
	 */
	public static function revision( int $post_id ): string {
		$post = get_post( $post_id );
		if ( ! $post || ! str_contains( $post->post_content, '<!-- wp:trust/hero-document' ) ) {
			return '';
		}
		foreach ( parse_blocks( $post->post_content ) as $block ) {
			if ( 'trust/hero-document' !== $block['blockName'] ) {
				continue;
			}
			$value = (string) ( $block['attrs']['data']['revision'] ?? '' );
			if ( preg_match( '/^(\d{4})-?(\d{2})-?(\d{2})$/', $value, $m ) ) {
				return $m[1] . '-' . $m[2] . '-' . $m[3];
			}
		}
		return '';
	}
}
