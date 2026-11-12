<?php
/**
 * Меню сайта в виде дерева для вывода в шапке, мобильном меню и подвале.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Собирает пункты меню из области (Шапка, Подвал: …) в дерево с отметками
 * «текущая страница» и «текущий раздел».
 */
final class Menu {

	/**
	 * Дерево меню области.
	 *
	 * @param string $location  Область меню (header, footer-services…).
	 * @param int    $max_depth Сколько уровней собрать: 1 — только верхний, 2 — с подменю.
	 * @return array<int, array{id: int, title: string, url: string, description: string, target: string, rel: string, classes: string[], current: bool, in_section: bool, children: array}>
	 */
	public static function tree( string $location, int $max_depth = 2 ): array {
		$locations = get_nav_menu_locations();
		if ( empty( $locations[ $location ] ) ) {
			return array();
		}

		$items = wp_get_nav_menu_items( $locations[ $location ], array( 'update_post_term_cache' => false ) );
		if ( ! $items ) {
			return array();
		}

		// Классы из поля «CSS-классы» пункта — до того, как WordPress добавит свои.
		$user_classes = array();
		foreach ( $items as $item ) {
			$user_classes[ $item->ID ] = array_values( array_filter( (array) $item->classes ) );
		}

		// Отметки current, current_item_ancestor, current_item_parent — как в wp_nav_menu().
		_wp_menu_item_classes_by_context( $items );

		/**
		 * Пункты меню после отметки текущей страницы.
		 * Здесь шаблоны записей отмечают раздел, в который вложены (кейс → «Портфолио»).
		 *
		 * @param \WP_Post[] $items    Пункты меню.
		 * @param string     $location Область меню.
		 */
		$items = apply_filters( 'trust_menu_items', $items, $location );

		$by_parent = array();
		foreach ( $items as $item ) {
			$by_parent[ (int) $item->menu_item_parent ][] = $item;
		}

		return self::branch( $by_parent, 0, 1, $max_depth, $user_classes );
	}

	/**
	 * Название меню, назначенного области (в подвале — заголовок колонки).
	 *
	 * @param string $location Область меню.
	 */
	public static function name( string $location ): string {
		$locations = get_nav_menu_locations();
		$menu      = empty( $locations[ $location ] ) ? null : wp_get_nav_menu_object( $locations[ $location ] );
		return $menu ? (string) $menu->name : '';
	}

	/**
	 * Пункты одного уровня с вложенными.
	 *
	 * @param array $by_parent    Пункты, сгруппированные по родителю.
	 * @param int   $parent_id    ID родительского пункта.
	 * @param int   $depth        Текущий уровень (с 1).
	 * @param int   $max_depth    Последний уровень.
	 * @param array $user_classes Классы из поля «CSS-классы».
	 */
	private static function branch( array $by_parent, int $parent_id, int $depth, int $max_depth, array $user_classes ): array {
		$branch = array();

		foreach ( $by_parent[ $parent_id ] ?? array() as $item ) {
			$children = $depth < $max_depth
				? self::branch( $by_parent, (int) $item->ID, $depth + 1, $max_depth, $user_classes )
				: array();

			$child_current = false;
			foreach ( $children as $child ) {
				$child_current = $child_current || $child['current'] || $child['in_section'];
			}

			// Страница пункта есть в цепочке хлебных крошек: у кейса горит «Портфолио»,
			// у ответа — «SEO» и «Вопросы по SEO», у статьи — «Блог».
			$in_trail = 'post_type' === $item->type && in_array( (int) $item->object_id, Breadcrumbs::trail_ids(), true );

			$branch[] = array(
				'id'          => (int) $item->ID,
				'title'       => (string) $item->title,
				'url'         => (string) $item->url,
				'description' => trim( (string) $item->description ),
				'target'      => (string) $item->target,
				'rel'         => trim( (string) $item->xfn . ( '_blank' === $item->target ? ' noopener' : '' ) ),
				'classes'     => $user_classes[ $item->ID ] ?? array(),
				'current'     => ! empty( $item->current ),
				'in_section'  => $child_current || $in_trail || ! empty( $item->current_item_ancestor ) || ! empty( $item->current_item_parent ),
				'children'    => $children,
			);
		}

		return $branch;
	}
}
