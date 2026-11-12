<?php
/**
 * Общее для админки: счётчики в меню, колонки списков.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Счётчики у пунктов меню (ТЗ, раздел 16): новые заявки, вопросы и отзывы на утверждении.
 * Помощники для колонок списков записей.
 */
final class Admin {

	/**
	 * Метаполе статуса заявки (заявки — шаг 3.5).
	 */
	public const LEAD_STATUS = 'trust_status';

	/**
	 * Статус «Новая» у заявки.
	 */
	public const LEAD_NEW = 'new';

	/**
	 * Подписи статусов записей для колонки «Статус».
	 */
	private const STATUSES = array(
		'publish' => 'Опубликован',
		'pending' => 'На утверждении',
		'draft'   => 'Черновик',
		'future'  => 'Запланирован',
		'private' => 'Личный',
	);

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu_counters' ), 999 );
	}

	/**
	 * Сколько ждёт внимания: тип записи => число.
	 */
	public static function counts(): array {
		$leads = new \WP_Query(
			array(
				'post_type'      => 'lead',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::LEAD_STATUS, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- запрос по индексу meta_key, только в админке.
				'meta_value'     => self::LEAD_NEW, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- см. выше.
			)
		);

		return array(
			'lead'     => (int) $leads->found_posts,
			'question' => (int) ( wp_count_posts( 'question' )->pending ?? 0 ),
			'review'   => (int) ( wp_count_posts( 'review' )->pending ?? 0 ),
		);
	}

	/**
	 * Число у пунктов «Заявки», «Вопросы SEO», «Отзывы» — как у «Комментариев».
	 */
	public static function menu_counters(): void {
		global $menu;

		if ( ! is_array( $menu ) ) {
			return;
		}

		$hints = array(
			'lead'     => __( 'Новые заявки', 'trust' ),
			'question' => __( 'На утверждении', 'trust' ),
			'review'   => __( 'На утверждении', 'trust' ),
		);

		foreach ( self::counts() as $type => $count ) {
			if ( ! $count ) {
				continue;
			}
			foreach ( $menu as $i => $item ) {
				if ( isset( $item[2] ) && 'edit.php?post_type=' . $type === $item[2] ) {
					$menu[ $i ][0] .= sprintf( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- так WordPress добавляет счётчик к «Комментариям».
						' <span class="awaiting-mod count-%1$d"><span class="pending-count" aria-hidden="true">%1$d</span><span class="screen-reader-text">%2$s: %1$d</span></span>',
						$count,
						esc_html( $hints[ $type ] )
					);
				}
			}
		}
	}

	/**
	 * Подпись статуса записи.
	 *
	 * @param int $post_id Запись.
	 */
	public static function status_label( int $post_id ): string {
		$status = (string) get_post_status( $post_id );
		return self::STATUSES[ $status ] ?? $status;
	}

	/**
	 * Вставляет колонки после указанной (или в конец, если её нет); «Дата» остаётся последней.
	 *
	 * @param array  $columns Колонки списка.
	 * @param string $after   Ключ колонки, после которой вставить.
	 * @param array  $insert  Новые колонки: ключ => подпись.
	 * @param array  $tail    Колонки в конец, перед «Датой».
	 */
	public static function insert_columns( array $columns, string $after, array $insert, array $tail = array() ): array {
		$date = $columns['date'] ?? null;
		unset( $columns['date'] );

		$result = array();
		foreach ( $columns as $key => $label ) {
			$result[ $key ] = $label;
			if ( $after === $key ) {
				$result += $insert;
			}
		}
		$result += $insert + $tail;

		if ( $date ) {
			$result['date'] = $date;
		}
		return $result;
	}
}
