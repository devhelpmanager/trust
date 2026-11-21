<?php
/**
 * Обращения к руководителю (правка заказчика): форма в окне блока «Слово руководителя» и
 * подпункт «Заявки → Обращения к руководителю».
 *
 * Обращение — обычная заявка (тип записи lead, форма leader): те же защита, статусы,
 * уведомления (свои адреса — «Настройки сайта → Формы»), CSV и срок хранения. В «Все заявки»
 * обращения не попадают — только в свой подпункт со счётчиком новых.
 *
 * @package Trust
 */

namespace Trust\Forms;

use Trust\Admin;
use Trust\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Форма и список обращений к руководителю.
 */
final class Leader_Messages {

	/**
	 * Тип формы.
	 */
	public const TYPE = 'leader';

	/**
	 * Адрес подпункта меню (список заявок с фильтром по форме).
	 */
	public const MENU_SLUG = 'edit.php?post_type=lead&trust_form=leader';

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'trust_form_types', array( self::class, 'types' ) );
		add_action( 'admin_menu', array( self::class, 'menu' ), 20 );
		add_filter( 'submenu_file', array( self::class, 'submenu_file' ), 10, 2 );
		add_action( 'load-edit.php', array( self::class, 'labels' ) );
		add_action( 'pre_get_posts', array( self::class, 'exclude' ), 20 );
		add_filter( 'views_edit-lead', array( self::class, 'views' ) );
		add_filter( 'manage_lead_posts_columns', array( self::class, 'columns' ), 20 );
		add_action( 'manage_lead_posts_custom_column', array( self::class, 'column' ), 10, 2 );
	}

	/**
	 * Описание формы: имя, телефон или email, сообщение, согласие.
	 *
	 * @param array $types Описания форм.
	 */
	public static function types( array $types ): array {
		$types[ self::TYPE ] = array(
			'label'    => __( 'Обращение к руководителю', 'trust' ),
			'captcha'  => true,
			'fields'   => array(
				'name'    => array(
					'label'    => __( 'Имя', 'trust' ),
					'type'     => 'text',
					'required' => true,
					'max'      => 100,
					'error'    => __( 'Укажите, как к вам обращаться', 'trust' ),
				),
				'contact' => array(
					'label'    => __( 'Телефон или email', 'trust' ),
					'type'     => 'contact',
					'required' => true,
					'max'      => 100,
					/* translators: %s: пример номера телефона. */
					'error'    => sprintf( __( 'Укажите телефон или email, например %s или name@example.ru', 'trust' ), Forms::phone_example() ),
				),
				'message' => array(
					'label'    => __( 'Сообщение', 'trust' ),
					'type'     => 'textarea',
					'required' => true,
					'min'      => 10,
					'max'      => 2000,
					'error'    => __( 'Напишите сообщение — от 10 до 2000 символов', 'trust' ),
				),
			),
			'consents' => array( 'consent' => Forms::consent() ),
			'name'     => 'name',
			'contact'  => array( 'contact' ),
		);
		return $types;
	}

	/**
	 * Открыт список обращений (или одно обращение) в админке.
	 */
	public static function is_screen(): bool {
		global $pagenow;
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- только выбор экрана.
		if ( 'edit.php' === $pagenow ) {
			return isset( $_GET['post_type'] ) && 'lead' === sanitize_key( wp_unslash( $_GET['post_type'] ) ) && self::TYPE === self::form_param();
		}
		if ( 'post.php' === $pagenow && isset( $_GET['post'] ) ) {
			$id = absint( $_GET['post'] );
			return 'lead' === get_post_type( $id ) && self::TYPE === get_post_meta( $id, 'trust_form', true );
		}
		// phpcs:enable
		return false;
	}

	/**
	 * Форма из фильтра списка заявок (?trust_form=…) или пустая строка.
	 */
	private static function form_param(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- фильтр списка, только чтение.
		return isset( $_GET['trust_form'] ) ? sanitize_key( wp_unslash( $_GET['trust_form'] ) ) : '';
	}

	/**
	 * Число новых обращений.
	 */
	public static function count_new(): int {
		$query = new \WP_Query(
			array(
				'post_type'      => 'lead',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- только в админке, по индексу meta_key.
					array(
						'key'   => 'trust_form',
						'value' => self::TYPE,
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
	 * Подпункт «Обращения к руководителю» сразу под «Все заявки», с числом новых.
	 */
	public static function menu(): void {
		$type = get_post_type_object( 'lead' );
		if ( ! $type ) {
			return;
		}
		$label = __( 'Обращения к руководителю', 'trust' );
		$count = self::count_new();
		add_submenu_page(
			'edit.php?post_type=lead',
			$label,
			$label . ( $count ? sprintf(
				' <span class="awaiting-mod count-%1$d"><span class="pending-count" aria-hidden="true">%1$d</span><span class="screen-reader-text">%2$s: %1$d</span></span>',
				$count,
				esc_html__( 'Новые обращения', 'trust' )
			) : '' ),
			$type->cap->edit_posts,
			self::MENU_SLUG,
			'',
			1
		);
	}

	/**
	 * Подсветка подпункта на списке обращений и на экране обращения.
	 *
	 * @param string|null $submenu_file Активный подпункт.
	 * @param string      $parent_file  Активный пункт.
	 */
	public static function submenu_file( $submenu_file, $parent_file ) {
		return 'edit.php?post_type=lead' === $parent_file && self::is_screen() ? self::MENU_SLUG : $submenu_file;
	}

	/**
	 * Заголовок и пустой список на экране обращений.
	 */
	public static function labels(): void {
		$type = get_post_type_object( 'lead' );
		if ( ! $type || ! self::is_screen() ) {
			return;
		}
		$type->labels->name      = __( 'Обращения к руководителю', 'trust' );
		$type->labels->not_found = __( 'Обращений пока нет: они появляются, когда посетитель отправляет форму «Написать руководителю» в блоке «Слово руководителя».', 'trust' );
	}

	/**
	 * Колонки списка обращений: вместо «Формы» — начало сообщения.
	 *
	 * @param array $columns Колонки.
	 */
	public static function columns( array $columns ): array {
		if ( ! self::is_screen() ) {
			return $columns;
		}
		unset( $columns['trust_form'] );
		return Admin::insert_columns( $columns, 'trust_contact', array( 'trust_leader_message' => __( 'Сообщение', 'trust' ) ) );
	}

	/**
	 * Колонка «Сообщение»: первые 140 символов.
	 *
	 * @param string $column  Колонка.
	 * @param int    $post_id Обращение.
	 */
	public static function column( string $column, int $post_id ): void {
		if ( 'trust_leader_message' !== $column ) {
			return;
		}
		$fields = (array) get_post_meta( $post_id, 'trust_fields', true );
		$text   = (string) ( $fields['message']['value'] ?? '' );
		echo esc_html( mb_strlen( $text ) > 140 ? rtrim( mb_substr( $text, 0, 139 ) ) . '…' : $text );
	}

	/**
	 * Условие запроса: только обращения или все заявки, кроме обращений.
	 *
	 * @param bool $leader Только обращения.
	 */
	private static function clause( bool $leader ): array {
		return array(
			'key'     => 'trust_form',
			'value'   => self::TYPE,
			'compare' => $leader ? '=' : '!=',
		);
	}

	/**
	 * «Все заявки» — без обращений к руководителю (они в своём подпункте). Фильтр по форме
	 * (Trust\Forms\Leads::apply_filters) показывает выбранную форму, обращения — на своём экране.
	 *
	 * @param \WP_Query $query Запрос.
	 */
	public static function exclude( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || 'lead' !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( '' !== self::form_param() ) {
			return;
		}
		$meta   = (array) $query->get( 'meta_query' );
		$meta[] = self::clause( false );
		$query->set( 'meta_query', $meta ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- список в админке.
	}

	/**
	 * Ссылки «Все (N) | Опубликованные (N) | Корзина (N)»: числа без обращений в «Все заявки»
	 * и только обращения — на их экране (ссылки остаются на экране обращений).
	 *
	 * @param array<string, string> $views Ссылки.
	 */
	public static function views( array $views ): array {
		$leader = self::is_screen();
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
					'meta_query'     => array( self::clause( $leader ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- только в админке.
				)
			);
			$count = (int) $query->found_posts;
			if ( ! $count && 'all' !== $key ) {
				unset( $views[ $key ] );
				continue;
			}
			$html = (string) preg_replace( '#(<span class="count">\()[^)]*(\)</span>)#', '${1}' . number_format_i18n( $count ) . '${2}', $html );
			if ( $leader ) {
				$html = str_replace( 'post_type=lead', 'post_type=lead&amp;trust_form=' . self::TYPE, $html );
			}
			$views[ $key ] = $html;
		}
		return $views;
	}
}
