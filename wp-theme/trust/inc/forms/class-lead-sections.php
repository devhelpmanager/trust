<?php
/**
 * Отдельные подпункты «Заявок» для некоторых форм (правки заказчика): «Обращения к руководителю»,
 * «Резюме». Заявки такой формы — обычные заявки (тип записи lead): те же защита, статусы,
 * уведомления, CSV и срок хранения; но в «Все заявки» и в фильтр форм не попадают — только в свой
 * подпункт со счётчиком новых, своим заголовком и своими колонками.
 *
 * Подпункт добавляет фильтр trust_lead_sections: тип формы => array(
 *     'menu'      => подпись подпункта и заголовок списка,
 *     'not_found' => текст пустого списка,
 *     'position'  => место в подменю «Заявки» (1 — сразу после «Все заявки»),
 *     'columns'   => колонки вместо «Формы»: имя => подпись (вставляются после «Контакт»),
 *     'column'    => callable( string $column, int $post_id ): void — значение своей колонки,
 * ).
 *
 * @package Trust
 */

namespace Trust\Forms;

use Trust\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Подпункты «Заявок» по формам.
 */
final class Lead_Sections {

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ), 20 );
		add_filter( 'submenu_file', array( self::class, 'submenu_file' ), 10, 2 );
		add_action( 'load-edit.php', array( self::class, 'labels' ) );
		add_action( 'pre_get_posts', array( self::class, 'exclude' ), 20 );
		add_filter( 'views_edit-lead', array( self::class, 'views' ) );
		add_filter( 'manage_lead_posts_columns', array( self::class, 'columns' ), 20 );
		add_action( 'manage_lead_posts_custom_column', array( self::class, 'column' ), 10, 2 );
	}

	/**
	 * Подпункты: тип формы => описание.
	 *
	 * @return array<string, array>
	 */
	public static function sections(): array {
		$sections = (array) apply_filters( 'trust_lead_sections', array() );
		uasort( $sections, static fn( $a, $b ): int => (int) ( $a['position'] ?? 1 ) <=> (int) ( $b['position'] ?? 1 ) );
		return $sections;
	}

	/**
	 * Адрес подпункта: список заявок с фильтром по форме.
	 *
	 * @param string $type Тип формы.
	 */
	public static function menu_slug( string $type ): string {
		return 'edit.php?post_type=lead&trust_form=' . $type;
	}

	/**
	 * Форма из фильтра списка заявок (?trust_form=…) или пустая строка.
	 */
	private static function form_param(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- фильтр списка, только чтение.
		return isset( $_GET['trust_form'] ) ? sanitize_key( wp_unslash( $_GET['trust_form'] ) ) : '';
	}

	/**
	 * Подпункт открытого экрана админки (список заявок формы или одна её заявка) или ''.
	 */
	public static function current(): string {
		global $pagenow;
		$sections = self::sections();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- только выбор экрана.
		if ( 'edit.php' === $pagenow ) {
			$type = isset( $_GET['post_type'] ) && 'lead' === sanitize_key( wp_unslash( $_GET['post_type'] ) ) ? self::form_param() : '';
			return isset( $sections[ $type ] ) ? $type : '';
		}
		if ( 'post.php' === $pagenow && isset( $_GET['post'] ) ) {
			$id   = absint( $_GET['post'] );
			$type = 'lead' === get_post_type( $id ) ? (string) get_post_meta( $id, 'trust_form', true ) : '';
			return isset( $sections[ $type ] ) ? $type : '';
		}
		// phpcs:enable
		return '';
	}

	/**
	 * Число новых заявок формы.
	 *
	 * @param string $type Тип формы.
	 */
	public static function count_new( string $type ): int {
		$query = new \WP_Query(
			array(
				'post_type'      => 'lead',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- только в админке, по индексу meta_key.
					array(
						'key'   => 'trust_form',
						'value' => $type,
					),
					array(
						'key'   => Admin::LEAD_STATUS,
						'value' => Admin::LEAD_NEW,
					),
				),
			)
		);
		return (int) $query->found_posts;
	}

	/**
	 * Подпункты сразу под «Все заявки», с числом новых.
	 */
	public static function menu(): void {
		$post_type = get_post_type_object( 'lead' );
		if ( ! $post_type ) {
			return;
		}
		foreach ( self::sections() as $type => $section ) {
			$label = (string) $section['menu'];
			$count = self::count_new( $type );
			add_submenu_page(
				'edit.php?post_type=lead',
				$label,
				$label . ( $count ? sprintf(
					' <span class="awaiting-mod count-%1$d"><span class="pending-count" aria-hidden="true">%1$d</span><span class="screen-reader-text">%2$s: %1$d</span></span>',
					$count,
					esc_html__( 'Новые', 'trust' )
				) : '' ),
				$post_type->cap->edit_posts,
				self::menu_slug( $type ),
				'',
				(int) ( $section['position'] ?? 1 )
			);
		}
	}

	/**
	 * Подсветка подпункта на его списке и на экране его заявки.
	 *
	 * @param string|null $submenu_file Активный подпункт.
	 * @param string      $parent_file  Активный пункт.
	 */
	public static function submenu_file( $submenu_file, $parent_file ) {
		$type = 'edit.php?post_type=lead' === $parent_file ? self::current() : '';
		return '' !== $type ? self::menu_slug( $type ) : $submenu_file;
	}

	/**
	 * Заголовок и пустой список на экране подпункта.
	 */
	public static function labels(): void {
		$post_type = get_post_type_object( 'lead' );
		$type      = self::current();
		if ( ! $post_type || '' === $type ) {
			return;
		}
		$section                      = self::sections()[ $type ];
		$post_type->labels->name      = (string) $section['menu'];
		$post_type->labels->not_found = (string) ( $section['not_found'] ?? $post_type->labels->not_found );
	}

	/**
	 * Колонки списка подпункта: вместо «Формы» — свои.
	 *
	 * @param array $columns Колонки.
	 */
	public static function columns( array $columns ): array {
		$type = self::current();
		if ( '' === $type ) {
			return $columns;
		}
		unset( $columns['trust_form'] );
		return Admin::insert_columns( $columns, 'trust_contact', (array) ( self::sections()[ $type ]['columns'] ?? array() ) );
	}

	/**
	 * Значение своей колонки подпункта.
	 *
	 * @param string $column  Колонка.
	 * @param int    $post_id Заявка.
	 */
	public static function column( string $column, int $post_id ): void {
		$type    = self::current();
		$section = '' !== $type ? self::sections()[ $type ] : array();
		if ( isset( $section['columns'][ $column ] ) && is_callable( $section['column'] ?? null ) ) {
			call_user_func( $section['column'], $column, $post_id );
		}
	}

	/**
	 * Условие запроса: только заявки формы или все, кроме форм с подпунктами.
	 *
	 * @param string $type Тип формы ('' — все, кроме подпунктов).
	 */
	private static function clause( string $type ): array {
		if ( '' !== $type ) {
			return array(
				'key'   => 'trust_form',
				'value' => $type,
			);
		}
		return array(
			'key'     => 'trust_form',
			'value'   => array_keys( self::sections() ),
			'compare' => 'NOT IN',
		);
	}

	/**
	 * «Все заявки» — без форм с подпунктами. Фильтр по форме (Trust\Forms\Leads::apply_filters)
	 * показывает выбранную форму, подпункт — свою.
	 *
	 * @param \WP_Query $query Запрос.
	 */
	public static function exclude( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || 'lead' !== $query->get( 'post_type' ) || '' !== self::form_param() || ! self::sections() ) {
			return;
		}
		$meta   = (array) $query->get( 'meta_query' );
		$meta[] = self::clause( '' );
		$query->set( 'meta_query', $meta ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- список в админке.
	}

	/**
	 * Ссылки «Все (N) | Опубликованные (N) | Корзина (N)»: в «Все заявки» — без форм с
	 * подпунктами, в подпункте — только его форма (ссылки остаются в подпункте).
	 *
	 * @param array<string, string> $views Ссылки.
	 */
	public static function views( array $views ): array {
		if ( ! self::sections() ) {
			return $views;
		}
		$type = self::current();
		foreach ( $views as $key => $html ) {
			if ( 'mine' === $key ) {
				continue;
			}
			$query = new \WP_Query(
				array(
					'post_type'      => 'lead',
					'post_status'    => 'all' === $key ? array( 'publish', 'pending', 'draft', 'future', 'private' ) : $key,
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array( self::clause( $type ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- только в админке.
				)
			);
			$count = (int) $query->found_posts;
			if ( ! $count && 'all' !== $key ) {
				unset( $views[ $key ] );
				continue;
			}
			$html = (string) preg_replace( '#(<span class="count">\()[^)]*(\)</span>)#', '${1}' . number_format_i18n( $count ) . '${2}', $html );
			if ( '' !== $type ) {
				$html = str_replace( 'post_type=lead', 'post_type=lead&amp;trust_form=' . $type, $html );
			}
			$views[ $key ] = $html;
		}
		return $views;
	}
}
