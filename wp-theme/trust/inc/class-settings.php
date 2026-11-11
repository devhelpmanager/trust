<?php
/**
 * Страница «Настройки сайта» (страница настроек Secure Custom Fields с вкладками).
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Регистрирует страницу настроек и её поля, отдаёт значения настроек.
 *
 * Вкладки описаны в inc/fields/settings/<вкладка>.php и возвращают массив
 * array( 'label' => …, 'cap' => право (необязательно), 'fields' => array( … ) ).
 * Вкладка или поле с 'cap' показываются только пользователям с этим правом.
 */
final class Settings {

	/**
	 * Адрес страницы настроек в админке: admin.php?page=trust-settings.
	 */
	public const PAGE_SLUG = 'trust-settings';

	/**
	 * Префикс ключей полей настроек.
	 */
	public const PREFIX = 'settings';

	/**
	 * Вкладки в порядке вывода. Остальные вкладки из ТЗ добавляются на своих шагах.
	 */
	private const TABS = array( 'company', 'contacts', 'social', 'footer', 'blocks', 'urls' );

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'acf/init', array( self::class, 'register_page' ) );
		add_action( 'acf/init', array( self::class, 'register_fields' ) );
		add_filter( 'acf/prepare_field', array( self::class, 'restrict_field' ) );
	}

	/**
	 * Страница «Настройки сайта» в меню админки.
	 */
	public static function register_page(): void {
		acf_add_options_page(
			array(
				'page_title'      => __( 'Настройки сайта', 'trust' ),
				'menu_title'      => __( 'Настройки сайта', 'trust' ),
				'menu_slug'       => self::PAGE_SLUG,
				'capability'      => 'edit_others_posts',
				'icon_url'        => 'dashicons-admin-site-alt3',
				'position'        => 61,
				'redirect'        => false,
				'autoload'        => true,
				'update_button'   => __( 'Сохранить настройки', 'trust' ),
				'updated_message' => __( 'Настройки сохранены.', 'trust' ),
			)
		);
	}

	/**
	 * Поля страницы настроек: все вкладки — одна группа полей.
	 */
	public static function register_fields(): void {
		$fields = array();

		foreach ( self::TABS as $tab ) {
			$section = require TRUST_DIR . '/inc/fields/settings/' . $tab . '.php';
			$cap     = $section['cap'] ?? '';

			$fields[] = array(
				'name'      => 'tab_' . $tab,
				'label'     => $section['label'],
				'type'      => 'tab',
				'placement' => 'top',
				'trust_cap' => $cap,
			);

			foreach ( $section['fields'] as $field ) {
				if ( $cap && empty( $field['trust_cap'] ) ) {
					$field['trust_cap'] = $cap;
				}
				$fields[] = $field;
			}
		}

		acf_add_local_field_group(
			array(
				'key'                   => 'group_trust_settings',
				'title'                 => __( 'Настройки сайта', 'trust' ),
				'fields'                => Fields::with_keys( $fields, self::PREFIX ),
				'location'              => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => self::PAGE_SLUG,
						),
					),
				),
				'style'                 => 'seamless',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
			)
		);
	}

	/**
	 * Скрывает поля и вкладки, для которых у пользователя нет права из 'trust_cap'.
	 * Скрытое поле не отправляется формой, поэтому его значение не меняется.
	 *
	 * @param array|false $field Поле SCF.
	 * @return array|false
	 */
	public static function restrict_field( $field ) {
		if ( is_array( $field ) && ! empty( $field['trust_cap'] ) && ! current_user_can( $field['trust_cap'] ) ) {
			return false;
		}
		return $field;
	}

	/**
	 * Значение настройки.
	 *
	 * @param string $name     Имя поля (например, 'company_brand').
	 * @param mixed  $fallback Что вернуть, если поле пустое или SCF не активен.
	 * @return mixed
	 */
	public static function get( string $name, $fallback = null ) {
		if ( ! Dependencies::has_scf() ) {
			return $fallback;
		}

		$value = get_field( $name, 'option' );

		if ( null === $value || '' === $value || false === $value || array() === $value ) {
			return $fallback;
		}
		return $value;
	}

	/**
	 * Название бренда: из настроек, иначе «Название сайта» WordPress.
	 */
	public static function brand(): string {
		return (string) self::get( 'company_brand', get_bloginfo( 'name' ) );
	}

	/**
	 * Адрес страницы настроек (для ссылок из админки).
	 *
	 * @param string $tab Имя вкладки (необязательно) — откроется она.
	 */
	public static function admin_url( string $tab = '' ): string {
		$url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		return $tab ? $url . '#' . rawurlencode( $tab ) : $url;
	}
}
