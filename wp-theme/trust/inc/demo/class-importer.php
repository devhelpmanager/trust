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
	 * Метаполе демо-записи: её адрес в демо-данных (например, page:seo/audit).
	 */
	public const META_ID = '_trust_demo_id';

	/**
	 * Метаполе демо-записи: отпечаток заголовка и текста на момент импорта.
	 */
	public const META_HASH = '_trust_demo_hash';

	/**
	 * Опция с демо-меню: область меню => ID меню.
	 */
	public const MENUS_RECORD = 'trust_demo_menus';

	/**
	 * Адрес демо-страницы политики конфиденциальности.
	 */
	private const PRIVACY_PAGE_PATH = 'politika-konfidencialnosti';

	/**
	 * Опция с прежней страницей политики конфиденциальности (до демо-импорта).
	 */
	public const PRIVACY_RECORD = 'trust_demo_privacy_page';

	/**
	 * Создаёт демо-страницы из demo/pages.php.
	 *
	 * Существующая демо-страница обновляется, если её не меняли вручную.
	 * Если по адресу уже есть своя (не демо) страница, она не трогается.
	 *
	 * @return array{created: string[], updated: string[], skipped: string[]}
	 */
	public static function import_pages(): array {
		$pages  = require TRUST_DIR . '/demo/pages.php';
		$result = array(
			'created' => array(),
			'updated' => array(),
			'skipped' => array(),
		);

		foreach ( $pages as $page ) {
			$path    = $page['path'];
			$content = $page['content'] ?? '';
			$demo    = self::find_demo_post( 'page', $path );

			if ( $demo ) {
				if ( self::post_hash( $demo ) !== get_post_meta( $demo->ID, self::META_HASH, true ) ) {
					$result['skipped'][] = $path;
					continue;
				}
				wp_update_post(
					array(
						'ID'           => $demo->ID,
						'post_title'   => $page['title'],
						'post_content' => $content,
					)
				);
				update_post_meta( $demo->ID, self::META_HASH, self::post_hash( get_post( $demo->ID ) ) );
				$result['updated'][] = $path;
				continue;
			}

			if ( get_page_by_path( $path ) ) {
				$result['skipped'][] = $path;
				continue;
			}

			$parent = str_contains( $path, '/' ) ? get_page_by_path( dirname( $path ) ) : null;
			$id     = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $page['title'],
					'post_name'    => basename( $path ),
					'post_parent'  => $parent ? $parent->ID : 0,
					'post_content' => $content,
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				$result['skipped'][] = $path;
				continue;
			}

			update_post_meta( $id, self::META_ID, 'page:' . $path );
			update_post_meta( $id, self::META_HASH, self::post_hash( get_post( $id ) ) );
			$result['created'][] = $path;
		}

		return $result;
	}

	/**
	 * Удаляет демо-страницы, которые не меняли вручную (вложенные — первыми).
	 *
	 * @return array{removed: string[], kept: string[]}
	 */
	public static function remove_pages(): array {
		$result = array(
			'removed' => array(),
			'kept'    => array(),
		);
		$posts  = get_posts(
			array(
				'post_type'   => 'page',
				'post_status' => 'any',
				'numberposts' => -1,
				'meta_key'    => self::META_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- разовая команда WP-CLI.
			)
		);

		usort(
			$posts,
			static fn( $a, $b ) => substr_count( (string) get_post_meta( $b->ID, self::META_ID, true ), '/' )
				<=> substr_count( (string) get_post_meta( $a->ID, self::META_ID, true ), '/' )
		);

		foreach ( $posts as $post ) {
			$id = (string) get_post_meta( $post->ID, self::META_ID, true );
			if ( self::post_hash( $post ) === get_post_meta( $post->ID, self::META_HASH, true ) ) {
				wp_delete_post( $post->ID, true );
				$result['removed'][] = $id;
			} else {
				$result['kept'][] = $id;
			}
		}

		return $result;
	}

	/**
	 * Создаёт демо-меню из demo/menus.php и назначает их областям.
	 *
	 * Меню не создаётся, если оно уже импортировано или в области стоит своё меню.
	 *
	 * @return array{created: string[], skipped: string[]}
	 */
	public static function import_menus(): array {
		$menus     = require TRUST_DIR . '/demo/menus.php';
		$record    = (array) get_option( self::MENUS_RECORD, array() );
		$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
		$result    = array(
			'created' => array(),
			'skipped' => array(),
		);

		foreach ( $menus as $location => $menu ) {
			$is_imported = ! empty( $record[ $location ] ) && is_nav_menu( $record[ $location ] );
			$is_occupied = ! empty( $locations[ $location ] ) && is_nav_menu( $locations[ $location ] );
			if ( $is_imported || $is_occupied ) {
				$result['skipped'][] = $location;
				continue;
			}

			$name    = wp_get_nav_menu_object( $menu['name'] ) ? $menu['name'] . ' (демо)' : $menu['name'];
			$menu_id = wp_create_nav_menu( $name );
			if ( is_wp_error( $menu_id ) ) {
				$result['skipped'][] = $location;
				continue;
			}

			self::add_menu_items( (int) $menu_id, $menu['items'], 0 );

			$locations[ $location ] = (int) $menu_id;
			$record[ $location ]    = (int) $menu_id;
			$result['created'][]    = $location;
		}

		set_theme_mod( 'nav_menu_locations', $locations );
		update_option( self::MENUS_RECORD, $record, false );
		return $result;
	}

	/**
	 * Удаляет демо-меню.
	 *
	 * @return array{removed: string[]}
	 */
	public static function remove_menus(): array {
		$record    = (array) get_option( self::MENUS_RECORD, array() );
		$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
		$result    = array( 'removed' => array() );

		foreach ( $record as $location => $menu_id ) {
			if ( is_nav_menu( $menu_id ) ) {
				wp_delete_nav_menu( $menu_id );
				$result['removed'][] = $location;
			}
			if ( isset( $locations[ $location ] ) && (int) $locations[ $location ] === (int) $menu_id ) {
				unset( $locations[ $location ] );
			}
		}

		set_theme_mod( 'nav_menu_locations', $locations );
		delete_option( self::MENUS_RECORD );
		return $result;
	}

	/**
	 * Добавляет пункты меню (рекурсивно — вложенные).
	 *
	 * Позиция сквозная по всему меню (родитель, его вложенные, следующий пункт…) —
	 * так пункты стоят по порядку и в редакторе меню WordPress.
	 *
	 * @param int   $menu_id  ID меню.
	 * @param array $items    Пункты из demo/menus.php.
	 * @param int   $parent_id ID родительского пункта.
	 * @param int   $position Сквозной счётчик позиций.
	 */
	private static function add_menu_items( int $menu_id, array $items, int $parent_id, int &$position = 0 ): void {
		foreach ( $items as $item ) {
			$args = array(
				'menu-item-title'       => $item['title'],
				'menu-item-status'      => 'publish',
				'menu-item-parent-id'   => $parent_id,
				'menu-item-position'    => ++$position,
				'menu-item-description' => $item['description'] ?? '',
				'menu-item-classes'     => $item['classes'] ?? '',
			);

			if ( ! empty( $item['page'] ) ) {
				$page = get_page_by_path( $item['page'] );
				if ( ! $page ) {
					continue;
				}
				$args['menu-item-type']      = 'post_type';
				$args['menu-item-object']    = 'page';
				$args['menu-item-object-id'] = $page->ID;
			} else {
				$args['menu-item-type'] = 'custom';
				$args['menu-item-url']  = $item['url'];
			}

			$item_id = wp_update_nav_menu_item( $menu_id, 0, $args );
			if ( ! is_wp_error( $item_id ) && ! empty( $item['children'] ) ) {
				self::add_menu_items( $menu_id, $item['children'], (int) $item_id, $position );
			}
		}
	}

	/**
	 * Демо-запись по её адресу в демо-данных.
	 *
	 * @param string $type Тип записи.
	 * @param string $path Адрес в демо-данных.
	 */
	private static function find_demo_post( string $type, string $path ): ?\WP_Post {
		$posts = get_posts(
			array(
				'post_type'   => $type,
				'post_status' => 'any',
				'numberposts' => 1,
				'meta_key'    => self::META_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- разовая команда WP-CLI.
				'meta_value'  => $type . ':' . $path, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- разовая команда WP-CLI.
			)
		);
		return $posts[0] ?? null;
	}

	/**
	 * Отпечаток записи: заголовок и текст.
	 *
	 * @param \WP_Post $post Запись.
	 */
	private static function post_hash( \WP_Post $post ): string {
		return md5( $post->post_title . "\n" . $post->post_content );
	}

	/**
	 * Заменяет {page:адрес} ссылкой на страницу (рекурсивно в массивах).
	 * Поэтому страницы импортируются раньше настроек.
	 *
	 * @param mixed $value Значение из демо-данных.
	 * @return mixed
	 */
	private static function resolve_links( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( self::class, 'resolve_links' ), $value );
		}
		if ( is_string( $value ) && preg_match( '~^\{page(_id)?:([a-z0-9/-]+)\}$~', $value, $m ) ) {
			$page = get_page_by_path( $m[2] );
			if ( '_id' === $m[1] ) {
				return $page ? $page->ID : 0;
			}
			return $page ? (string) get_permalink( $page ) : '';
		}
		return $value;
	}

	/**
	 * Назначает демо-страницу «Политика конфиденциальности» в «Настройки → Конфиденциальность»,
	 * если там не выбрана своя опубликованная страница. Прежнее значение запоминается.
	 *
	 * @return string Что сделано.
	 */
	public static function import_privacy_page(): string {
		$page    = get_page_by_path( self::PRIVACY_PAGE_PATH );
		$current = (int) get_option( 'wp_page_for_privacy_policy' );

		if ( ! $page ) {
			return 'нет демо-страницы';
		}
		if ( $current === $page->ID ) {
			return 'уже назначена';
		}
		if ( $current && 'publish' === get_post_status( $current ) ) {
			return 'оставлена ваша страница';
		}

		update_option( self::PRIVACY_RECORD, $current, false );
		update_option( 'wp_page_for_privacy_policy', $page->ID );
		return 'назначена демо-страница';
	}

	/**
	 * Возвращает прежнюю страницу политики конфиденциальности.
	 */
	public static function remove_privacy_page(): void {
		$previous = get_option( self::PRIVACY_RECORD, null );
		if ( null === $previous ) {
			return;
		}
		update_option( 'wp_page_for_privacy_policy', (int) $previous );
		delete_option( self::PRIVACY_RECORD );
	}

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

			update_field( Fields::key( Settings::PREFIX, $name ), self::resolve_links( $value ), 'option' );
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
