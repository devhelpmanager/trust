<?php
/**
 * Соцсети и мессенджеры: список сетей для выбора в полях и ссылки компании.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Список сетей общий для компании (Настройки сайта → Соцсети) и сотрудников.
 */
final class Social {

	/**
	 * Сети, из которых можно выбрать (по согласованию с заказчиком).
	 *
	 * @return array<string, string> Ключ сети => название.
	 */
	public static function networks(): array {
		return array(
			'telegram'  => 'Telegram',
			'whatsapp'  => 'WhatsApp',
			'youtube'   => 'YouTube',
			'instagram' => 'Instagram',
			'facebook'  => 'Facebook',
		);
	}

	/**
	 * Ссылки компании из настроек: только заполненные и известные сети.
	 *
	 * @return array<int, array{network: string, label: string, url: string}>
	 */
	public static function company_links(): array {
		$networks = self::networks();
		$links    = array();

		foreach ( (array) Settings::get( 'social_links', array() ) as $row ) {
			$network = $row['network'] ?? '';
			$url     = $row['url'] ?? '';
			if ( $url && isset( $networks[ $network ] ) ) {
				$links[] = array(
					'network' => $network,
					'label'   => $networks[ $network ],
					'url'     => $url,
				);
			}
		}
		return $links;
	}
}
