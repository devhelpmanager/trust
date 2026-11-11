<?php
/**
 * Поля записей и терминов (ТЗ, раздел 5.3).
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Регистрирует группы полей Secure Custom Fields для типов записей и таксономий.
 *
 * Каждая группа описана в inc/fields/content/<тип записи или таксономия>.php и возвращает
 * array( 'title' => …, 'location' => 'post_type' | 'taxonomy', 'position' => …, 'fields' => … ).
 * Ключи полей — field_trust_<группа>_<имя>; имена полей начинаются с trust_, поэтому
 * метаполя темы не пересекаются с метаполями плагинов.
 */
final class Content_Fields {

	/**
	 * Группы полей. Пополняется на шагах 2.3 и 2.4 (кейсы, отзывы, сотрудники…).
	 */
	private const GROUPS = array( 'post', 'category' );

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'acf/init', array( self::class, 'register_groups' ) );
	}

	/**
	 * Группы полей.
	 */
	public static function register_groups(): void {
		foreach ( self::GROUPS as $name ) {
			$group = require TRUST_DIR . '/inc/fields/content/' . $name . '.php';

			acf_add_local_field_group(
				array(
					'key'                   => 'group_trust_' . $name,
					'title'                 => $group['title'],
					'fields'                => Fields::with_keys( $group['fields'], $name ),
					'location'              => array(
						array(
							array(
								'param'    => $group['location'],
								'operator' => '==',
								'value'    => $name,
							),
						),
					),
					'position'              => $group['position'] ?? 'normal',
					'style'                 => 'default',
					'label_placement'       => 'top',
					'instruction_placement' => 'label',
				)
			);
		}
	}
}
