<?php
/**
 * Проверка обязательного плагина Secure Custom Fields.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Если SCF не активирован, тема работает без блоков и полей
 * и показывает в админке уведомление со ссылкой на установку.
 */
final class Dependencies {

	/**
	 * Папка и главный файл плагина в каталоге WordPress.org.
	 */
	private const SCF_PLUGIN_FILE = 'secure-custom-fields/secure-custom-fields.php';

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'admin_notices', array( self::class, 'notice' ) );
	}

	/**
	 * Активирован ли SCF (или совместимый ACF).
	 */
	public static function has_scf(): bool {
		return function_exists( 'acf_add_local_field_group' ) && function_exists( 'get_field' );
	}

	/**
	 * Уведомление в админке, если плагина нет.
	 */
	public static function notice(): void {
		if ( self::has_scf() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$installed = file_exists( WP_PLUGIN_DIR . '/' . self::SCF_PLUGIN_FILE );

		if ( $installed ) {
			$url   = wp_nonce_url(
				admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( self::SCF_PLUGIN_FILE ) ),
				'activate-plugin_' . self::SCF_PLUGIN_FILE
			);
			$label = __( 'Активировать Secure Custom Fields', 'trust' );
		} else {
			$url   = admin_url( 'plugin-install.php?s=secure-custom-fields&tab=search&type=term' );
			$label = __( 'Установить Secure Custom Fields', 'trust' );
		}

		printf(
			'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p><p><a class="button button-primary" href="%3$s">%4$s</a></p></div>',
			esc_html__( 'Тема Trust:', 'trust' ),
			esc_html__( 'для работы блоков, полей и настроек сайта нужен плагин Secure Custom Fields. Без него сайт открывается, но блоки и настройки недоступны.', 'trust' ),
			esc_url( $url ),
			esc_html( $label )
		);
	}
}
