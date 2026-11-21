<?php
/**
 * Просчёты стоимости работ (правка заказчика к шагу 4.8): каждый расчёт в калькуляторе
 * (нажатие «Рассчитать стоимость») сохраняется — ответы и цена, страница, источник перехода.
 * Личных данных нет: имя и телефон приходят только заявкой «Калькулятор» (форма контактов).
 *
 * REST POST /wp-json/trust/v1/estimates — токен и nonce форм (Trust\Forms\Protection), свой
 * лимит частоты (повторные расчёты одного посетителя не должны мешать его заявке).
 * В админке — «Заявки → Просчёт стоимости работ»: список (цена, регион, страница, дата),
 * просмотр, выгрузка в CSV. Удаляются по сроку хранения заявок (Leads::cleanup()).
 *
 * @package Trust
 */

namespace Trust\Forms;

use Trust\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Просчёты стоимости.
 */
final class Estimates {

	/**
	 * Тип записи (регистрирует Trust\Content_Types).
	 */
	public const TYPE = 'estimate';

	/**
	 * Сколько просчётов с одного IP за 10 минут.
	 */
	public const RATE_LIMIT = 30;

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
		add_action( 'add_meta_boxes_' . self::TYPE, array( self::class, 'meta_boxes' ) );
		add_filter( 'manage_' . self::TYPE . '_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_' . self::TYPE . '_posts_custom_column', array( self::class, 'column' ), 10, 2 );
		add_filter( 'bulk_actions-edit-' . self::TYPE, array( self::class, 'bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-' . self::TYPE, array( self::class, 'handle_bulk' ), 10, 3 );
		add_filter( 'post_row_actions', array( self::class, 'row_actions' ), 10, 2 );
	}

	/**
	 * Маршрут REST.
	 */
	public static function routes(): void {
		register_rest_route(
			Handler::REST_NAMESPACE,
			'/estimates',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'rest' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Приём просчёта.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 */
	public static function rest( \WP_REST_Request $request ): \WP_REST_Response {
		$result   = self::process( $request->get_body_params() + $request->get_query_params() );
		$response = new \WP_REST_Response( $result['body'], $result['status'] );
		$response->set_headers( wp_get_nocache_headers() );
		return $response;
	}

	/**
	 * Проверка и сохранение.
	 *
	 * Коды ошибок — как у форм: rate_limited, stale (новый токен — и ещё раз), too_fast (подождать
	 * и ещё раз), invalid (нет цены).
	 *
	 * @param array $params Отправленные поля.
	 * @return array{status: int, body: array}
	 */
	public static function process( array $params ): array {
		$fail = static fn( int $status, string $code ): array => array(
			'status' => $status,
			'body'   => array(
				'ok'   => false,
				'code' => $code,
			),
		);

		if ( self::rate_limited() ) {
			return $fail( 429, 'rate_limited' );
		}
		$token = Protection::check_token( (string) ( $params['_trust_token'] ?? '' ) );
		if ( 'invalid' === $token || ! wp_verify_nonce( (string) ( $params['_trust_nonce'] ?? '' ), Protection::NONCE ) ) {
			return $fail( 403, 'stale' );
		}
		if ( 'fast' === $token ) {
			return $fail( 400, 'too_fast' );
		}

		$form   = Forms::type( Calculator::FORM );
		$fields = array();
		foreach ( $form['fields'] ?? array() as $name => $field ) {
			if ( ! str_starts_with( $name, 'calc_' ) ) {
				continue;
			}
			$value = is_scalar( $params[ $name ] ?? null ) ? (string) $params[ $name ] : '';
			$value = 'textarea' === $field['type'] ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
			$value = mb_substr( trim( $value ), 0, (int) ( $field['max'] ?? 300 ) );
			if ( '' !== $value ) {
				$fields[ $name ] = array(
					'label' => $field['label'],
					'value' => $value,
				);
			}
		}
		if ( empty( $fields['calc_price'] ) ) {
			return $fail( 400, 'invalid' );
		}

		$id = self::create( $fields, $params );
		if ( ! $id ) {
			return $fail( 500, 'server' );
		}
		return array(
			'status' => 200,
			'body'   => array(
				'ok'       => true,
				'estimate' => $id,
			),
		);
	}

	/**
	 * Лимит просчётов с одного IP (свой счётчик: не мешает отправке заявок).
	 */
	private static function rate_limited(): bool {
		$key   = 'trust_estimate_rate_' . md5( Protection::client_ip() );
		$state = get_transient( $key );
		$state = is_array( $state ) ? $state : array(
			'count' => 0,
			'start' => time(),
		);
		++$state['count'];
		$left = max( 1, Protection::RATE_WINDOW - ( time() - (int) $state['start'] ) );
		set_transient( $key, $state, $left );

		/**
		 * Сколько просчётов стоимости с одного IP разрешено за 10 минут.
		 *
		 * @param int $limit Лимит.
		 */
		return $state['count'] > (int) apply_filters( 'trust_estimate_rate_limit', self::RATE_LIMIT );
	}

	/**
	 * Запись просчёта.
	 *
	 * @param array $fields Поля: имя => label, value.
	 * @param array $params Отправленные поля (страница, источник перехода).
	 * @return int ID или 0.
	 */
	private static function create( array $fields, array $params ): int {
		list( $page, $page_title, $source ) = Leads::context( $params );

		$price  = $fields['calc_price']['value'];
		$region = $fields['calc_region']['value'] ?? '';
		$id     = wp_insert_post(
			array(
				'post_type'   => self::TYPE,
				'post_status' => 'publish',
				'post_title'  => wp_slash( mb_substr( trim( $price . ( '' !== $region ? ' — ' . $region : '' ) ), 0, 200 ) ),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return 0;
		}
		$meta = array(
			'trust_fields'     => $fields,
			'trust_price'      => $price,
			'trust_region'     => $region,
			'trust_page'       => $page,
			'trust_page_title' => $page_title,
			'trust_source'     => $source,
		);
		foreach ( array_filter( $meta, static fn( $value ): bool => '' !== $value && array() !== $value ) as $key => $value ) {
			update_post_meta( $id, $key, wp_slash( $value ) );
		}
		return (int) $id;
	}

	/**
	 * Окно на экране просчёта.
	 */
	public static function meta_boxes(): void {
		add_meta_box( 'trust-estimate-box', __( 'Просчёт стоимости', 'trust' ), array( self::class, 'render' ), self::TYPE, 'normal', 'high' );
	}

	/**
	 * Данные просчёта (только чтение).
	 *
	 * @param \WP_Post $post Просчёт.
	 */
	public static function render( \WP_Post $post ): void {
		$fields = (array) get_post_meta( $post->ID, 'trust_fields', true );
		$source = (array) get_post_meta( $post->ID, 'trust_source', true );
		$page   = (string) get_post_meta( $post->ID, 'trust_page', true );
		$title  = (string) get_post_meta( $post->ID, 'trust_page_title', true );

		$rows = array( array( __( 'Дата', 'trust' ), esc_html( get_the_date( 'd.m.Y H:i', $post ) ) ) );
		foreach ( $fields as $field ) {
			$rows[] = array( (string) ( $field['label'] ?? '' ), nl2br( esc_html( (string) ( $field['value'] ?? '' ) ) ) );
		}
		if ( $page ) {
			$rows[] = array( __( 'Страница', 'trust' ), '<a href="' . esc_url( $page ) . '" target="_blank" rel="noopener">' . esc_html( $title ? $title : Leads::readable( 'page', $page ) ) . '</a>' );
		}
		foreach ( self::source_labels() as $key => $label ) {
			if ( ! empty( $source[ $key ] ) ) {
				$rows[] = array( $label, esc_html( Leads::readable( $key, (string) $source[ $key ] ) ) );
			}
		}

		echo '<p>' . esc_html__( 'Расчёт посетителя в калькуляторе стоимости. Контактов здесь нет: если посетитель оставил телефон, его заявка — в «Заявках» (форма «Калькулятор»).', 'trust' ) . '</p>';
		echo '<table class="widefat striped trust-lead-table"><tbody>';
		foreach ( $rows as list( $label, $value ) ) {
			echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . $value . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- значения экранированы выше.
		}
		echo '</tbody></table>';
	}

	/**
	 * Подписи источника перехода.
	 */
	private static function source_labels(): array {
		return array(
			'utm_source'   => 'utm_source',
			'utm_medium'   => 'utm_medium',
			'utm_campaign' => 'utm_campaign',
			'utm_content'  => 'utm_content',
			'utm_term'     => 'utm_term',
			'referrer'     => __( 'Откуда пришёл', 'trust' ),
			'landing'      => __( 'Первая страница визита', 'trust' ),
			'at'           => __( 'Первое посещение', 'trust' ),
		);
	}

	/**
	 * Колонки списка: просчёт, цена, регион, страница, дата.
	 *
	 * @param array $columns Колонки.
	 */
	public static function columns( array $columns ): array {
		return array(
			'cb'           => $columns['cb'] ?? '',
			'title'        => __( 'Просчёт', 'trust' ),
			'trust_price'  => __( 'Цена', 'trust' ),
			'trust_region' => __( 'Регион', 'trust' ),
			'trust_page'   => __( 'Страница', 'trust' ),
			'date'         => __( 'Дата', 'trust' ),
		);
	}

	/**
	 * Значение колонки.
	 *
	 * @param string $column  Колонка.
	 * @param int    $post_id Просчёт.
	 */
	public static function column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'trust_price':
				echo esc_html( (string) get_post_meta( $post_id, 'trust_price', true ) );
				break;
			case 'trust_region':
				echo esc_html( (string) get_post_meta( $post_id, 'trust_region', true ) );
				break;
			case 'trust_page':
				$title = (string) get_post_meta( $post_id, 'trust_page_title', true );
				$page  = (string) get_post_meta( $post_id, 'trust_page', true );
				echo esc_html( '' !== $title ? $title : Leads::readable( 'page', $page ) );
				break;
		}
	}

	/**
	 * В строке списка — только «Посмотреть» и «Удалить» (править нечего).
	 *
	 * @param array    $actions Действия.
	 * @param \WP_Post $post    Запись.
	 */
	public static function row_actions( array $actions, $post ): array {
		if ( ! $post instanceof \WP_Post || self::TYPE !== $post->post_type ) {
			return $actions;
		}
		unset( $actions['inline hide-if-no-js'] );
		if ( isset( $actions['edit'] ) ) {
			$actions['edit'] = sprintf( '<a href="%s">%s</a>', esc_url( (string) get_edit_post_link( $post->ID ) ), esc_html__( 'Посмотреть', 'trust' ) );
		}
		return $actions;
	}

	/**
	 * Массовое действие «Выгрузить в CSV».
	 *
	 * @param array $actions Действия.
	 */
	public static function bulk_actions( array $actions ): array {
		unset( $actions['edit'] );
		return array( 'trust_export' => __( 'Выгрузить в CSV', 'trust' ) ) + $actions;
	}

	/**
	 * Выгрузка отмеченных просчётов.
	 *
	 * @param string $redirect Адрес возврата.
	 * @param string $action   Действие.
	 * @param int[]  $ids      Просчёты.
	 * @return string
	 */
	public static function handle_bulk( $redirect, $action, $ids ) {
		if ( 'trust_export' !== $action || ! current_user_can( 'edit_others_posts' ) ) {
			return $redirect;
		}
		self::export( array_map( 'intval', (array) $ids ) );
		exit;
	}

	/**
	 * Строки CSV: дата, страница, источник и ответы — по столбцу на вопрос.
	 *
	 * @param int[] $ids Просчёты.
	 * @return array<int, string[]>
	 */
	public static function csv_rows( array $ids ): array {
		$keys   = array_keys( array_filter( Forms::type( Calculator::FORM )['fields'] ?? array(), static fn( $name ): bool => str_starts_with( $name, 'calc_' ), ARRAY_FILTER_USE_KEY ) );
		$labels = array_map( static fn( string $key ): string => Forms::type( Calculator::FORM )['fields'][ $key ]['label'], $keys );
		$rows   = array( array_merge( array( 'ID', 'Дата', 'Страница', 'utm_source', 'utm_medium', 'utm_campaign', 'Откуда пришёл' ), $labels ) );
		$cell   = static fn( $value ): string => preg_match( '/^[=+\-@\t\r]/', (string) $value ) ? "'" . $value : (string) $value;
		foreach ( $ids as $id ) {
			$fields = (array) get_post_meta( $id, 'trust_fields', true );
			$source = (array) get_post_meta( $id, 'trust_source', true );
			$row    = array( $id, get_the_date( 'Y-m-d H:i', $id ), get_post_meta( $id, 'trust_page', true ), $source['utm_source'] ?? '', $source['utm_medium'] ?? '', $source['utm_campaign'] ?? '', Leads::readable( 'referrer', $source['referrer'] ?? '' ) );
			foreach ( $keys as $key ) {
				$row[] = (string) ( $fields[ $key ]['value'] ?? '' );
			}
			$rows[] = array_map( $cell, $row );
		}
		return $rows;
	}

	/**
	 * Отдаёт CSV (UTF-8 с BOM и «;» — открывается в Excel на русском).
	 *
	 * @param int[] $ids Просчёты.
	 */
	private static function export( array $ids ): void {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="estimates-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- поток ответа.
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- BOM для Excel.
		foreach ( self::csv_rows( $ids ) as $row ) {
			fputcsv( $out, $row, ';', '"', '\\' );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- поток ответа.
	}
}
