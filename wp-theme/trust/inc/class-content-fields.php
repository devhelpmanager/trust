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
 * Каждая группа описана в inc/fields/content/<имя>.php и возвращает
 * array( 'title' => …, 'location' => 'post_type' | 'taxonomy', 'position' => …, 'fields' => … ) —
 * группа для типа записи или таксономии <имя>; или вместо 'location' —
 * 'locations' => array( array( 'post_type', 'page' ), array( 'taxonomy', 'category' ), … ) —
 * группа для нескольких (например, «SEO»); 'menu_order' — порядок групп на экране.
 * Ключи полей — field_trust_<группа>_<имя>; имена полей начинаются с trust_, поэтому
 * метаполя темы не пересекаются с метаполями плагинов.
 */
final class Content_Fields {

	/**
	 * Группы полей: тип записи или таксономия => файл inc/fields/content/<имя>.php.
	 */
	private const GROUPS = array( 'post', 'category', 'case', 'review', 'team', 'vacancy', 'question', 'seo' );

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
			$group     = require TRUST_DIR . '/inc/fields/content/' . $name . '.php';
			$locations = $group['locations'] ?? array( array( $group['location'], $name ) );

			acf_add_local_field_group(
				array(
					'key'                   => 'group_trust_' . $name,
					'title'                 => $group['title'],
					'fields'                => Fields::with_keys( $group['fields'], $name ),
					// Правила «ИЛИ»: группа показывается на любом из мест.
					'location'              => array_map(
						static fn( array $place ): array => array(
							array(
								'param'    => $place[0],
								'operator' => '==',
								'value'    => $place[1],
							),
						),
						$locations
					),
					'menu_order'            => $group['menu_order'] ?? 0,
					'position'              => $group['position'] ?? 'normal',
					'style'                 => 'default',
					'label_placement'       => 'top',
					'instruction_placement' => 'label',
				)
			);
		}
	}
}
