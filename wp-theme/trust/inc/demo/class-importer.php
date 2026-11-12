<?php
/**
 * Импорт и удаление демо-контента.
 *
 * Данные лежат в папке demo/. Демо-контент пополняется на каждом шаге разработки:
 * сейчас — «Настройки сайта», страницы, меню, политика конфиденциальности, термины.
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
	 * Два прохода: сначала создаются недостающие страницы (пустыми), потом заполняется
	 * содержимое. В блоках есть ссылки {page:…} на страницы, которые идут в списке ниже
	 * (из «Аудита» — на «Вопросы SEO»): к моменту заполнения все они уже существуют.
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
		$fill   = array();

		foreach ( $pages as $page ) {
			$path = $page['path'];
			$demo = self::find_demo_post( 'page', $path );

			if ( $demo ) {
				if ( self::post_hash( $demo ) !== get_post_meta( $demo->ID, self::META_HASH, true ) ) {
					$result['skipped'][] = $path;
					continue;
				}
				$fill[ $path ] = array( $demo->ID, $page, 'updated' );
				continue;
			}

			if ( get_page_by_path( $path ) ) {
				$result['skipped'][] = $path;
				continue;
			}

			$parent = str_contains( $path, '/' ) ? get_page_by_path( dirname( $path ) ) : null;
			$id     = wp_insert_post(
				wp_slash(
					array(
						'post_type'   => 'page',
						'post_status' => 'publish',
						'post_title'  => $page['title'],
						'post_name'   => basename( $path ),
						'post_parent' => $parent ? $parent->ID : 0,
					)
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				$result['skipped'][] = $path;
				continue;
			}

			update_post_meta( $id, self::META_ID, 'page:' . $path );
			$fill[ $path ] = array( $id, $page, 'created' );
		}

		foreach ( $fill as $path => list( $id, $page, $status ) ) {
			$content = isset( $page['blocks'] ) ? self::blocks_content( $page['blocks'] ) : ( $page['content'] ?? '' );
			// wp_slash: WordPress снимает слэши при сохранении, а в данных блоков есть \u003c и т. п.
			wp_update_post(
				wp_slash(
					array(
						'ID'           => $id,
						'post_title'   => $page['title'],
						'post_content' => $content,
					)
				)
			);
			update_post_meta( $id, self::META_HASH, self::post_hash( get_post( $id ) ) );

			// Тип страницы: при создании или если тип ещё не выбран — выбранный вручную не трогаем.
			$template = \Trust\Page_Types::template( $page['type'] ?? '' );
			if ( '' !== $template && ( 'created' === $status || '' === get_page_template_slug( $id ) ) ) {
				update_post_meta( $id, '_wp_page_template', $template );
			}

			$result[ $status ][] = $path;
		}

		return $result;
	}

	/**
	 * Создаёт демо-записи типа из demo/<файл>.php: заголовок, анонс, текст, поля темы, термины;
	 * необязательно — slug (адрес), date (дата публикации), menu_order (порядок), status.
	 *
	 * Демо-запись обновляется, если её не меняли вручную (отпечаток учитывает и поля).
	 * Записи без даты получают даты по порядку файла: первая — самая новая, поэтому в списках
	 * они идут как в макете.
	 *
	 * @param string $type Тип записи.
	 * @param string $file Файл данных в папке demo/ без .php.
	 * @return array{created: string[], updated: string[], skipped: string[]}
	 */
	public static function import_posts( string $type, string $file ): array {
		$items  = require TRUST_DIR . '/demo/' . $file . '.php';
		$result = array(
			'created' => array(),
			'updated' => array(),
			'skipped' => array(),
		);
		$now    = time();

		foreach ( $items as $index => $item ) {
			$key  = $item['key'];
			$demo = self::find_demo_post( $type, $key );

			if ( $demo && self::post_hash( $demo ) !== get_post_meta( $demo->ID, self::META_HASH, true ) ) {
				$result['skipped'][] = $key;
				continue;
			}

			$data = array(
				'post_type'    => $type,
				'post_title'   => $item['title'],
				'post_excerpt' => $item['excerpt'] ?? '',
				'post_content' => $item['content'] ?? '',
				'menu_order'   => $item['menu_order'] ?? 0,
			);
			if ( isset( $item['date'] ) ) {
				$data['post_date']     = $item['date'];
				$data['post_date_gmt'] = get_gmt_from_date( $item['date'] );
			}

			if ( $demo ) {
				$data['ID'] = $demo->ID;
				wp_update_post( wp_slash( $data ) );
				$id = $demo->ID;
			} else {
				$data['post_status'] = $item['status'] ?? 'publish';
				if ( isset( $item['slug'] ) ) {
					$data['post_name'] = $item['slug'];
				}
				if ( ! isset( $item['date'] ) ) {
					$data['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $now - ( $index + 1 ) * HOUR_IN_SECONDS );
					$data['post_date']     = get_date_from_gmt( $data['post_date_gmt'] );
				}
				$id = wp_insert_post( wp_slash( $data ), true );
				if ( is_wp_error( $id ) ) {
					$result['skipped'][] = $key;
					continue;
				}
				update_post_meta( $id, self::META_ID, $type . ':' . $key );
			}

			foreach ( $item['terms'] ?? array() as $taxonomy => $keys ) {
				$term_ids = array();
				foreach ( $keys as $term_key ) {
					$term = self::find_demo_term( $taxonomy, $taxonomy . ':' . $term_key );
					if ( $term ) {
						$term_ids[] = $term->term_id;
					}
				}
				wp_set_object_terms( $id, $term_ids, $taxonomy );
			}

			foreach ( self::resolve_links( $item['fields'] ?? array() ) as $name => $value ) {
				update_field( Fields::key( $type, $name ), $value, $id );
			}

			update_post_meta( $id, self::META_HASH, self::post_hash( get_post( $id ) ) );
			$result[ $demo ? 'updated' : 'created' ][] = $key;
		}

		return $result;
	}

	/**
	 * Удаляет демо-записи типа, которые не меняли вручную.
	 *
	 * @param string $type Тип записи.
	 * @return array{removed: string[], kept: string[]}
	 */
	public static function remove_posts( string $type ): array {
		$result = array(
			'removed' => array(),
			'kept'    => array(),
		);
		$posts  = get_posts(
			array(
				'post_type'   => $type,
				'post_status' => 'any',
				'numberposts' => -1,
				'meta_key'    => self::META_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- разовая команда WP-CLI.
			)
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
	 * Создаёт демо-термины из demo/terms.php.
	 *
	 * Демо-термин обновляется, если его не меняли вручную. Если термин с таким названием
	 * уже есть и он не демо, он не трогается.
	 *
	 * @return array{created: string[], updated: string[], skipped: string[]}
	 */
	public static function import_terms(): array {
		$taxonomies = require TRUST_DIR . '/demo/terms.php';
		$result     = array(
			'created' => array(),
			'updated' => array(),
			'skipped' => array(),
		);

		foreach ( $taxonomies as $taxonomy => $terms ) {
			foreach ( $terms as $key => $data ) {
				$data = ( is_array( $data ) ? $data : array( 'name' => $data ) ) + array(
					'description' => '',
					'fields'      => array(),
				);
				$id   = $taxonomy . ':' . $key;
				$demo = self::find_demo_term( $taxonomy, $id );

				if ( $demo ) {
					if ( self::term_hash( $demo ) !== get_term_meta( $demo->term_id, self::META_HASH, true ) ) {
						$result['skipped'][] = $id;
					} elseif ( self::write_term( $demo->term_id, $taxonomy, $data ) ) {
						$result['updated'][] = $id;
					}
					continue;
				}

				if ( term_exists( $data['name'], $taxonomy ) ) {
					$result['skipped'][] = $id;
					continue;
				}

				$term = wp_insert_term( $data['name'], $taxonomy, array( 'description' => $data['description'] ) );
				if ( is_wp_error( $term ) ) {
					$result['skipped'][] = $id;
					continue;
				}

				update_term_meta( $term['term_id'], self::META_ID, $id );
				self::write_term( $term['term_id'], $taxonomy, $data );
				$result['created'][] = $id;
			}
		}

		return $result;
	}

	/**
	 * Записывает название, описание и поля демо-термина и его отпечаток.
	 *
	 * @param int    $term_id  Термин.
	 * @param string $taxonomy Таксономия.
	 * @param array  $data     Название, описание, поля из demo/terms.php.
	 * @return bool Изменилось ли что-нибудь.
	 */
	private static function write_term( int $term_id, string $taxonomy, array $data ): bool {
		$term    = get_term( $term_id, $taxonomy );
		$changed = false;

		if ( $term->name !== $data['name'] || $term->description !== $data['description'] ) {
			wp_update_term(
				$term_id,
				$taxonomy,
				array(
					'name'        => $data['name'],
					'description' => $data['description'],
				)
			);
			$changed = true;
		}

		foreach ( self::resolve_links( $data['fields'] ) as $name => $value ) {
			$current = get_term_meta( $term_id, $name, true );
			if ( is_scalar( $value ) ? (string) $current !== (string) $value : $current !== $value ) {
				update_field( Fields::key( $taxonomy, $name ), $value, 'term_' . $term_id );
				$changed = true;
			}
		}

		update_term_meta( $term_id, self::META_HASH, self::term_hash( get_term( $term_id, $taxonomy ) ) );
		return $changed;
	}

	/**
	 * Удаляет демо-термины, которые не меняли вручную.
	 *
	 * @return array{removed: string[], kept: string[]}
	 */
	public static function remove_terms(): array {
		$result = array(
			'removed' => array(),
			'kept'    => array(),
		);
		$terms  = get_terms(
			array(
				'taxonomy'   => array_keys( require TRUST_DIR . '/demo/terms.php' ),
				'hide_empty' => false,
				'meta_key'   => self::META_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- разовая команда WP-CLI.
			)
		);

		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$id = (string) get_term_meta( $term->term_id, self::META_ID, true );
			if ( self::term_hash( $term ) === get_term_meta( $term->term_id, self::META_HASH, true ) ) {
				wp_delete_term( $term->term_id, $term->taxonomy );
				$result['removed'][] = $id;
			} else {
				$result['kept'][] = $id;
			}
		}

		return $result;
	}

	/**
	 * Демо-термин по его идентификатору в демо-данных.
	 *
	 * @param string $taxonomy Таксономия.
	 * @param string $id       Идентификатор: <таксономия>:<ключ>.
	 */
	private static function find_demo_term( string $taxonomy, string $id ): ?\WP_Term {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 1,
				'meta_key'   => self::META_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- разовая команда WP-CLI.
				'meta_value' => $id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- разовая команда WP-CLI.
			)
		);
		return is_array( $terms ) && $terms ? $terms[0] : null;
	}

	/**
	 * Отпечаток термина: название, ярлык, описание и поля темы (метаполя trust_…).
	 *
	 * @param \WP_Term $term Термин.
	 */
	private static function term_hash( \WP_Term $term ): string {
		$source = $term->name . "\n" . $term->slug . "\n" . $term->description;
		$fields = self::theme_meta( (array) get_term_meta( $term->term_id ) );

		if ( $fields ) {
			$source .= "\n" . wp_json_encode( $fields );
		}
		return md5( $source );
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
		$source = $post->post_title . "\n" . $post->post_content;
		$fields = self::theme_meta( (array) get_post_meta( $post->ID ) );

		// У страниц нет анонса и полей темы — их отпечаток не меняется.
		if ( '' !== $post->post_excerpt ) {
			$source .= "\n" . $post->post_excerpt;
		}
		if ( $fields ) {
			$source .= "\n" . wp_json_encode( $fields );
		}
		return md5( $source );
	}

	/**
	 * Метаполя полей темы (trust_…) — для отпечатков записей и терминов.
	 *
	 * @param array $meta Все метаполя.
	 */
	private static function theme_meta( array $meta ): array {
		$fields = array_filter( $meta, static fn( $key ): bool => str_starts_with( (string) $key, 'trust_' ), ARRAY_FILTER_USE_KEY );
		ksort( $fields );
		return $fields;
	}

	/**
	 * Содержимое страницы из блоков темы.
	 *
	 * @param array $blocks Список array( 'имя блока', array( значения полей ) ).
	 */
	private static function blocks_content( array $blocks ): string {
		$parts = array();
		foreach ( $blocks as $block ) {
			$parts[] = \Trust\Blocks::serialize( $block[0], self::resolve_links( $block[1] ) );
		}
		return implode( "\n\n", $parts );
	}

	/**
	 * Заменяет {page:адрес} ссылкой на страницу, {page_id:адрес} — её ID, {post:тип:ключ} — ID
	 * демо-записи, {term_id:таксономия:ключ} — ID демо-термина (рекурсивно в массивах;
	 * {page:…} — и внутри текста).
	 * Поэтому страницы импортируются раньше настроек.
	 *
	 * @param mixed $value Значение из демо-данных.
	 * @return mixed
	 */
	private static function resolve_links( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( self::class, 'resolve_links' ), $value );
		}
		if ( ! is_string( $value ) ) {
			return $value;
		}
		if ( preg_match( '~^\{post:([a-z_]+):([a-z0-9-]+)\}$~', $value, $m ) ) {
			$post = self::find_demo_post( $m[1], $m[2] );
			return $post ? $post->ID : 0;
		}
		if ( preg_match( '~^\{term_id:([a-z_]+):([a-z0-9-]+)\}$~', $value, $m ) ) {
			$term = self::find_demo_term( $m[1], $m[1] . ':' . $m[2] );
			return $term ? $term->term_id : 0;
		}
		if ( preg_match( '~^\{page(_id)?:([a-z0-9/-]+)\}$~', $value, $m ) ) {
			$page = get_page_by_path( $m[2] );
			if ( '_id' === $m[1] ) {
				return $page ? $page->ID : 0;
			}
			return $page ? (string) get_permalink( $page ) : '';
		}
		// Ссылки внутри текста: <a href="{page:seo/audit}">.
		return (string) preg_replace_callback(
			'~\{page:([a-z0-9/-]+)\}~',
			static function ( array $m ): string {
				$page = get_page_by_path( $m[1] );
				return $page ? (string) get_permalink( $page ) : '#';
			},
			$value
		);
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
