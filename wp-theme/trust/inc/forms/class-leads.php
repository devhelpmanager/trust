<?php
/**
 * Заявки (тип записи lead): сохранение, экран заявки, список, CSV, срок хранения (ТЗ, раздел 12.2).
 *
 * @package Trust
 */

namespace Trust\Forms;

use Trust\Admin;
use Trust\Forms;
use Trust\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Заявка хранит тип формы, все поля, страницу отправки, метки UTM и источник перехода
 * (первое посещение), дату, статус («Новая», «В работе», «Закрыта») и факт согласия: какие
 * галочки, дата и время, адрес и дата редакции документа согласия. Данные остаются в базе
 * сайта; во внешние сервисы тема их не отправляет (кроме каналов уведомлений — шаг 3.6).
 */
final class Leads {

	/**
	 * Статусы заявки.
	 */
	public const STATUSES = array(
		'new'    => 'Новая',
		'work'   => 'В работе',
		'closed' => 'Закрыта',
	);

	/**
	 * Метки источника перехода, которые принимаются из формы.
	 */
	private const SOURCE_KEYS = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'referrer', 'landing', 'at' );

	/**
	 * Событие WP-Cron удаления старых заявок.
	 */
	public const CLEANUP_EVENT = 'trust_leads_cleanup';

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'add_meta_boxes_lead', array( self::class, 'meta_boxes' ) );
		add_action( 'save_post_lead', array( self::class, 'save_status' ), 10, 2 );
		add_filter( 'manage_lead_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_lead_posts_custom_column', array( self::class, 'column' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( self::class, 'filters' ) );
		add_action( 'pre_get_posts', array( self::class, 'apply_filters' ) );
		add_filter( 'bulk_actions-edit-lead', array( self::class, 'bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-lead', array( self::class, 'handle_bulk' ), 10, 3 );
		add_action( 'admin_notices', array( self::class, 'bulk_notice' ) );
		add_action( 'admin_head-edit.php', array( self::class, 'styles' ) );
		add_action( 'admin_head-post.php', array( self::class, 'styles' ) );
		add_action( 'init', array( self::class, 'schedule' ) );
		add_action( self::CLEANUP_EVENT, array( self::class, 'cleanup' ) );
		add_action( 'switch_theme', array( self::class, 'unschedule' ) );
	}

	/**
	 * Строка из отправленных данных: без HTML, не длиннее $max символов.
	 *
	 * @param mixed $value Значение.
	 * @param int   $max   Длина.
	 */
	private static function text( $value, int $max = 300 ): string {
		return is_scalar( $value ) ? mb_substr( trim( sanitize_text_field( (string) $value ) ), 0, $max ) : '';
	}

	/**
	 * Адрес из отправленных данных (http или https). Без sanitize_text_field: она вырезает
	 * %-коды, и адреса с кириллицей и поисковыми запросами портились бы.
	 *
	 * @param mixed $value Значение.
	 */
	private static function url( $value ): string {
		return is_scalar( $value ) ? esc_url_raw( mb_substr( trim( (string) $value ), 0, 1000 ), array( 'http', 'https' ) ) : '';
	}

	/**
	 * Сохраняет заявку.
	 *
	 * @param string $type     Тип формы.
	 * @param array  $form     Описание формы.
	 * @param array  $values   Проверенные значения полей.
	 * @param array  $consents Отмеченные согласия: имя => подпись.
	 * @param array  $params   Все отправленные поля (страница, источник перехода).
	 * @return int|\WP_Error ID заявки.
	 */
	public static function create( string $type, array $form, array $values, array $consents, array $params ) {
		$fields = array();
		foreach ( $values as $name => $value ) {
			$field = $form['fields'][ $name ];
			if ( is_bool( $value ) ) {
				$value = $value ? __( 'да', 'trust' ) : __( 'нет', 'trust' );
			} elseif ( 'choice' === $field['type'] && '' !== $value ) {
				$value = (string) $field['options'][ $value ];
			}
			$fields[ $name ] = array(
				'label' => $field['label'],
				'value' => $value,
			);
		}

		$name    = (string) ( $values[ $form['name'] ?? '' ] ?? '' );
		$contact = '';
		foreach ( $form['contact'] ?? array() as $field ) {
			if ( ! empty( $values[ $field ] ) && is_string( $values[ $field ] ) ) {
				$contact = $values[ $field ];
				break;
			}
		}

		// Страница отправки — только адрес этого сайта.
		$page      = self::url( $params['page'] ?? '' );
		$page_host = wp_parse_url( $page, PHP_URL_HOST );
		if ( ! $page || wp_parse_url( home_url(), PHP_URL_HOST ) !== $page_host ) {
			$page = '';
		}
		$page_id = $page ? url_to_postid( $page ) : 0;
		if ( ! $page_id && $page && trailingslashit( (string) strtok( $page, '?#' ) ) === trailingslashit( home_url( '/' ) ) ) {
			$page_id = (int) get_option( 'page_on_front' );
		}

		$source = array();
		// Источник перехода присылает скрипт форм; без него — та же cookie trust_source из запроса.
		$raw = is_string( $params['source'] ?? null ) ? $params['source'] : '';
		if ( '' === $raw && isset( $_COOKIE['trust_source'] ) ) {
			// Без sanitize_text_field: она вырезала бы %-коды в адресах; каждое значение очищается ниже.
			$raw = substr( (string) wp_unslash( $_COOKIE['trust_source'] ), 0, 4096 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		$raw = json_decode( $raw, true );
		foreach ( self::SOURCE_KEYS as $key ) {
			$value = is_array( $raw ) ? ( $raw[ $key ] ?? '' ) : '';
			$value = in_array( $key, array( 'referrer', 'landing' ), true ) ? self::url( $value ) : self::text( $value );
			if ( 'at' === $key && '' !== $value ) {
				// Время первого визита приходит из браузера в UTC (ISO 8601) — в часовой пояс сайта.
				$time  = strtotime( $value );
				$value = $time ? wp_date( 'Y-m-d H:i:s', $time ) : '';
			}
			if ( '' !== $value ) {
				$source[ $key ] = $value;
			}
		}

		$id = wp_insert_post(
			array(
				'post_type'   => 'lead',
				'post_status' => 'publish',
				'post_title'  => wp_slash( trim( $form['label'] . ': ' . ( '' !== $name ? $name : $contact ), ' :' ) ),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$meta = array(
			'trust_form'       => $type,
			Admin::LEAD_STATUS => Admin::LEAD_NEW,
			'trust_fields'     => $fields,
			'trust_name'       => $name,
			'trust_contact'    => $contact,
			'trust_page'       => $page,
			'trust_page_title' => $page_id ? get_the_title( $page_id ) : '',
			'trust_source'     => $source,
			'trust_consent'    => array(
				'items'    => array_values( $consents ),
				'at'       => current_time( 'mysql' ),
				'document' => Forms::consent_url(),
				'revision' => Forms::consent_revision(),
			),
		);
		foreach ( array_filter( $meta, static fn( $value ): bool => '' !== $value && array() !== $value ) as $key => $value ) {
			update_post_meta( $id, $key, wp_slash( $value ) );
		}
		return $id;
	}

	/**
	 * Статус заявки.
	 *
	 * @param int $id Заявка.
	 */
	public static function status( int $id ): string {
		$status = (string) get_post_meta( $id, Admin::LEAD_STATUS, true );
		return isset( self::STATUSES[ $status ] ) ? $status : 'new';
	}

	/**
	 * Название формы заявки.
	 *
	 * @param int $id Заявка.
	 */
	public static function form_label( int $id ): string {
		$type = (string) get_post_meta( $id, 'trust_form', true );
		$form = Forms::type( $type );
		return $form ? $form['label'] : $type;
	}

	/**
	 * Окна на экране заявки: данные заявки и статус.
	 */
	public static function meta_boxes(): void {
		add_meta_box( 'trust-lead-box', __( 'Заявка', 'trust' ), array( self::class, 'render_lead' ), 'lead', 'normal', 'high' );
		add_meta_box( 'trust-lead-status-box', __( 'Статус', 'trust' ), array( self::class, 'render_status' ), 'lead', 'side', 'high' );
	}

	/**
	 * Данные заявки (только чтение).
	 *
	 * @param \WP_Post $post Заявка.
	 */
	public static function render_lead( \WP_Post $post ): void {
		$fields  = (array) get_post_meta( $post->ID, 'trust_fields', true );
		$source  = (array) get_post_meta( $post->ID, 'trust_source', true );
		$consent = (array) get_post_meta( $post->ID, 'trust_consent', true );
		$page    = (string) get_post_meta( $post->ID, 'trust_page', true );
		$title   = (string) get_post_meta( $post->ID, 'trust_page_title', true );

		$rows = array(
			array( __( 'Форма', 'trust' ), esc_html( self::form_label( $post->ID ) ) ),
			array( __( 'Дата', 'trust' ), esc_html( get_the_date( 'd.m.Y H:i', $post ) ) ),
		);
		foreach ( $fields as $field ) {
			$rows[] = array( (string) ( $field['label'] ?? '' ), nl2br( esc_html( (string) ( $field['value'] ?? '' ) ) ) );
		}
		if ( $page ) {
			$rows[] = array( __( 'Страница', 'trust' ), '<a href="' . esc_url( $page ) . '" target="_blank" rel="noopener">' . esc_html( $title ? $title : $page ) . '</a>' );
		}
		$labels = array(
			'utm_source'   => 'utm_source',
			'utm_medium'   => 'utm_medium',
			'utm_campaign' => 'utm_campaign',
			'utm_content'  => 'utm_content',
			'utm_term'     => 'utm_term',
			'referrer'     => __( 'Откуда пришёл', 'trust' ),
			'landing'      => __( 'Первая страница визита', 'trust' ),
			'at'           => __( 'Первое посещение', 'trust' ),
		);
		foreach ( $labels as $key => $label ) {
			if ( ! empty( $source[ $key ] ) ) {
				$rows[] = array( $label, esc_html( self::readable( $key, (string) $source[ $key ] ) ) );
			}
		}
		if ( $consent ) {
			$text = implode( '; ', (array) ( $consent['items'] ?? array() ) ) . ' — ' . self::readable( 'at', (string) ( $consent['at'] ?? '' ) );
			if ( ! empty( $consent['document'] ) ) {
				$text .= '<br><a href="' . esc_url( $consent['document'] ) . '" target="_blank" rel="noopener">' . esc_html__( 'Документ согласия', 'trust' ) . '</a>';
				$text .= ! empty( $consent['revision'] ) ? ' ' . esc_html( sprintf( /* translators: %s: дата. */ __( '(редакция от %s)', 'trust' ), mysql2date( 'd.m.Y', $consent['revision'] ) ) ) : '';
			}
			$rows[] = array( __( 'Согласие', 'trust' ), wp_kses_post( $text ) );
		}

		$rows[] = array( __( 'Уведомления', 'trust' ), Notifications::summary( $post->ID ) );

		echo '<table class="widefat striped trust-lead-table"><tbody>';
		foreach ( $rows as list( $label, $value ) ) {
			echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . $value . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- значения экранированы выше.
		}
		echo '</tbody></table>';
	}

	/**
	 * Значение для чтения человеком: адреса — с раскодированной кириллицей и запросами,
	 * время — «дд.мм.гггг чч:мм».
	 *
	 * @param string $key   Ключ (referrer, landing, at …).
	 * @param string $value Значение.
	 */
	private static function readable( string $key, string $value ): string {
		if ( in_array( $key, array( 'referrer', 'landing', 'page' ), true ) ) {
			return rawurldecode( $value );
		}
		if ( 'at' === $key && '' !== $value ) {
			return mysql2date( 'd.m.Y H:i', $value );
		}
		return $value;
	}

	/**
	 * Выбор статуса.
	 *
	 * @param \WP_Post $post Заявка.
	 */
	public static function render_status( \WP_Post $post ): void {
		wp_nonce_field( 'trust_lead_status', 'trust_lead_status_nonce' );
		echo '<label class="screen-reader-text" for="trust-lead-status">' . esc_html__( 'Статус', 'trust' ) . '</label>';
		echo '<select name="trust_lead_status" id="trust-lead-status" style="width:100%">';
		foreach ( self::STATUSES as $key => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( self::status( $post->ID ), $key, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	/**
	 * Сохраняет статус с экрана заявки.
	 *
	 * @param int      $post_id Заявка.
	 * @param \WP_Post $post    Заявка.
	 */
	public static function save_status( int $post_id, \WP_Post $post ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- сигнатура хука.
		if ( ! isset( $_POST['trust_lead_status_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['trust_lead_status_nonce'] ) ), 'trust_lead_status' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$status = isset( $_POST['trust_lead_status'] ) ? sanitize_key( wp_unslash( $_POST['trust_lead_status'] ) ) : '';
		if ( isset( self::STATUSES[ $status ] ) ) {
			update_post_meta( $post_id, Admin::LEAD_STATUS, $status );
		}
	}

	/**
	 * Колонки списка заявок: форма, имя, контакт, статус, дата.
	 *
	 * @param array $columns Колонки.
	 */
	public static function columns( array $columns ): array {
		$columns['title'] = __( 'Заявка', 'trust' );
		return Admin::insert_columns(
			$columns,
			'title',
			array(
				'trust_form'    => __( 'Форма', 'trust' ),
				'trust_name'    => __( 'Имя', 'trust' ),
				'trust_contact' => __( 'Контакт', 'trust' ),
				'trust_status'  => __( 'Статус', 'trust' ),
			)
		);
	}

	/**
	 * Стили списка и экрана заявки: новые заявки выделены.
	 */
	public static function styles(): void {
		if ( 'lead' !== get_current_screen()?->post_type ) {
			return;
		}
		echo '<style>.trust-lead-status{display:inline-block;padding:2px 8px;border-radius:10px;background:#f0f0f1}.trust-lead-status--new{background:#fcf0f1;color:#8a2424;font-weight:600}.trust-lead-status--closed{color:#646970}.trust-lead-table th{width:30%}.trust-lead-table td{word-break:break-word}.trust-notify--error{color:#b32d2e}.trust-notify--ok{color:#007017}</style>';
	}

	/**
	 * Значение колонки.
	 *
	 * @param string $column  Колонка.
	 * @param int    $post_id Заявка.
	 */
	public static function column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'trust_form':
				echo esc_html( self::form_label( $post_id ) );
				break;
			case 'trust_name':
			case 'trust_contact':
				echo esc_html( (string) get_post_meta( $post_id, $column, true ) );
				break;
			case 'trust_status':
				$status = self::status( $post_id );
				printf( '<span class="trust-lead-status trust-lead-status--%s">%s</span>', esc_attr( $status ), esc_html( self::STATUSES[ $status ] ) );
				break;
		}
	}

	/**
	 * Фильтры списка: форма и статус.
	 *
	 * @param string $post_type Тип записи списка.
	 */
	public static function filters( $post_type ): void {
		if ( 'lead' !== $post_type ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- фильтр списка, только чтение.
		$form   = isset( $_GET['trust_form'] ) ? sanitize_key( wp_unslash( $_GET['trust_form'] ) ) : '';
		$status = isset( $_GET['trust_status'] ) ? sanitize_key( wp_unslash( $_GET['trust_status'] ) ) : '';
		// phpcs:enable

		echo '<select name="trust_form"><option value="">' . esc_html__( 'Все формы', 'trust' ) . '</option>';
		foreach ( Forms::types() as $key => $type ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $form, $key, false ), esc_html( $type['label'] ) );
		}
		echo '</select><select name="trust_status"><option value="">' . esc_html__( 'Все статусы', 'trust' ) . '</option>';
		foreach ( self::STATUSES as $key => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $status, $key, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	/**
	 * Применяет фильтры списка.
	 *
	 * @param \WP_Query $query Запрос.
	 */
	public static function apply_filters( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || 'lead' !== $query->get( 'post_type' ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- фильтр списка, только чтение.
		$meta   = array();
		$params = array(
			'trust_form'   => 'trust_form',
			'trust_status' => Admin::LEAD_STATUS,
		);
		foreach ( $params as $param => $key ) {
			$value = isset( $_GET[ $param ] ) ? sanitize_key( wp_unslash( $_GET[ $param ] ) ) : '';
			if ( '' !== $value ) {
				$meta[] = array(
					'key'   => $key,
					'value' => $value,
				);
			}
		}
		// phpcs:enable
		if ( $meta ) {
			$query->set( 'meta_query', $meta ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- фильтр списка в админке.
		}
	}

	/**
	 * Массовые действия: статусы и выгрузка в CSV.
	 *
	 * @param array $actions Действия.
	 */
	public static function bulk_actions( array $actions ): array {
		foreach ( self::STATUSES as $key => $label ) {
			/* translators: %s: статус заявки. */
			$actions[ 'trust_status_' . $key ] = sprintf( __( 'Статус: %s', 'trust' ), $label );
		}
		$actions['trust_export_csv'] = __( 'Выгрузить в CSV', 'trust' );
		return $actions;
	}

	/**
	 * Выполняет массовое действие.
	 *
	 * @param string $redirect Адрес возврата.
	 * @param string $action   Действие.
	 * @param int[]  $ids      Отмеченные заявки.
	 */
	public static function handle_bulk( $redirect, $action, $ids ) {
		$ids = array_filter( array_map( 'intval', (array) $ids ), static fn( int $id ): bool => current_user_can( 'edit_post', $id ) );

		if ( 'trust_export_csv' === $action ) {
			self::export( $ids );
		}
		if ( str_starts_with( (string) $action, 'trust_status_' ) ) {
			$status = substr( (string) $action, strlen( 'trust_status_' ) );
			if ( isset( self::STATUSES[ $status ] ) ) {
				foreach ( $ids as $id ) {
					update_post_meta( $id, Admin::LEAD_STATUS, $status );
				}
				return add_query_arg( 'trust_status_changed', count( $ids ), $redirect );
			}
		}
		return $redirect;
	}

	/**
	 * Сообщение после смены статуса.
	 */
	public static function bulk_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- только число для сообщения.
		$count = isset( $_GET['trust_status_changed'] ) ? absint( $_GET['trust_status_changed'] ) : 0;
		if ( $count ) {
			/* translators: %d: число заявок. */
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( sprintf( __( 'Статус изменён у заявок: %d.', 'trust' ), $count ) ) );
		}
	}

	/**
	 * Значение для CSV: формулы таблиц экранируются («=», «+», «-», «@» в начале).
	 *
	 * @param mixed $value Значение.
	 */
	private static function cell( $value ): string {
		$value = trim( (string) $value );
		return preg_match( '/^[=+\-@\t\r]/', $value ) ? "'" . $value : $value;
	}

	/**
	 * Строки CSV по заявкам.
	 *
	 * @param int[] $ids Заявки.
	 * @return array<int, string[]>
	 */
	public static function csv_rows( array $ids ): array {
		$rows = array(
			array( 'ID', 'Дата', 'Форма', 'Статус', 'Имя', 'Контакт', 'Поля', 'Страница', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'Откуда пришёл', 'Первая страница визита', 'Согласие', 'Документ согласия' ),
		);
		foreach ( $ids as $id ) {
			$fields  = (array) get_post_meta( $id, 'trust_fields', true );
			$source  = (array) get_post_meta( $id, 'trust_source', true );
			$consent = (array) get_post_meta( $id, 'trust_consent', true );
			$rows[]  = array_map(
				array( self::class, 'cell' ),
				array(
					$id,
					get_the_date( 'Y-m-d H:i', $id ),
					self::form_label( $id ),
					self::STATUSES[ self::status( $id ) ],
					get_post_meta( $id, 'trust_name', true ),
					get_post_meta( $id, 'trust_contact', true ),
					implode( '; ', array_map( static fn( $f ) => ( $f['label'] ?? '' ) . ': ' . ( $f['value'] ?? '' ), $fields ) ),
					get_post_meta( $id, 'trust_page', true ),
					$source['utm_source'] ?? '',
					$source['utm_medium'] ?? '',
					$source['utm_campaign'] ?? '',
					$source['utm_content'] ?? '',
					$source['utm_term'] ?? '',
					self::readable( 'referrer', $source['referrer'] ?? '' ),
					self::readable( 'landing', $source['landing'] ?? '' ),
					trim( implode( '; ', (array) ( $consent['items'] ?? array() ) ) . ' ' . self::readable( 'at', (string) ( $consent['at'] ?? '' ) ) ),
					$consent['document'] ?? '',
				)
			);
		}
		return $rows;
	}

	/**
	 * Отдаёт CSV отмеченных заявок (UTF-8 с BOM и «;» — открывается в Excel на русском).
	 *
	 * @param int[] $ids Заявки.
	 */
	private static function export( array $ids ): void {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="leads-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- поток ответа.
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- BOM для Excel.
		foreach ( self::csv_rows( $ids ) as $row ) {
			fputcsv( $out, $row, ';', '"', '\\' );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- поток ответа.
		exit;
	}

	/**
	 * Ежедневная проверка срока хранения.
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CLEANUP_EVENT ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_EVENT );
		}
	}

	/**
	 * При смене темы — без ежедневной задачи.
	 */
	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::CLEANUP_EVENT );
	}

	/**
	 * Удаляет заявки старше срока из «Настроек сайта → Формы» (пусто или 0 — не удалять).
	 *
	 * @return int Сколько удалено.
	 */
	public static function cleanup(): int {
		$days = (int) Settings::get( 'forms_retention_days', 0 );
		if ( $days <= 0 ) {
			return 0;
		}
		$deleted = 0;
		// Порциями по 100, не больше 50 порций за запуск (остальное — на следующий день).
		for ( $batch = 0; $batch < 50; $batch++ ) {
			$old = get_posts(
				array(
					'post_type'   => 'lead',
					'post_status' => 'any',
					'numberposts' => 100,
					'fields'      => 'ids',
					'orderby'     => 'ID',
					'order'       => 'ASC',
					'date_query'  => array(
						array(
							'before' => gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ),
							'column' => 'post_date_gmt',
						),
					),
				)
			);
			if ( ! $old ) {
				break;
			}
			foreach ( $old as $id ) {
				$deleted += wp_delete_post( $id, true ) ? 1 : 0;
			}
		}
		return $deleted;
	}
}
