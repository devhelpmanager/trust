<?php
/**
 * Вакансии: разделы по умолчанию, колонки в админке.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Поля вакансии — inc/fields/content/vacancy.php.
 */
final class Vacancies {

	/**
	 * Заголовки разделов новой вакансии — как в макете «Карьера».
	 */
	public const DEFAULT_SECTIONS = array( 'Чем предстоит заниматься', 'Что ждём от кандидата', 'Что предлагаем' );

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'acf/load_value/key=' . Fields::key( 'vacancy', 'trust_sections' ), array( self::class, 'default_sections' ), 20, 2 );
		add_filter( 'enter_title_here', array( self::class, 'title_placeholder' ), 10, 2 );
		add_filter( 'manage_vacancy_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_vacancy_posts_custom_column', array( self::class, 'column' ), 10, 2 );
	}

	/**
	 * Открыта ли вакансия («Принимаем резюме»). Пустое поле — открыта (значение по умолчанию).
	 *
	 * @param int $post_id Вакансия.
	 */
	public static function is_open( int $post_id ): bool {
		$value = get_post_meta( $post_id, 'trust_open', true );
		return '' === $value || (bool) $value;
	}

	/**
	 * Число открытых вакансий (опубликованных, с отметкой «Принимаем резюме») — для {vacancies}
	 * в цифрах первого экрана «Карьеры».
	 */
	public static function open_count(): int {
		$ids = get_posts(
			array(
				'post_type'     => 'vacancy',
				'post_status'   => 'publish',
				'numberposts'   => -1,
				'fields'        => 'ids',
				'no_found_rows' => true,
			)
		);
		return count( array_filter( $ids, static fn( $id ): bool => self::is_open( (int) $id ) ) );
	}

	/**
	 * Пункты раздела: строки текста без пустых.
	 *
	 * @param string $items Текст поля «Пункты».
	 * @return string[]
	 */
	public static function items( string $items ): array {
		return array_values( array_filter( array_map( 'trim', preg_split( '/\R/u', $items ) ), 'strlen' ) );
	}

	/**
	 * У новой вакансии разделы описания уже добавлены — остаётся заполнить пункты.
	 *
	 * @param mixed      $value   Значение повторителя.
	 * @param int|string $post_id Запись.
	 * @return mixed
	 */
	public static function default_sections( $value, $post_id ) {
		if ( ! empty( $value ) || ! is_numeric( $post_id ) || 'auto-draft' !== get_post_status( (int) $post_id ) ) {
			return $value;
		}

		$title = Fields::key( 'vacancy_trust_sections', 'title' );
		$items = Fields::key( 'vacancy_trust_sections', 'items' );

		return array_map(
			static fn( string $section ): array => array(
				$title => $section,
				$items => '',
			),
			self::DEFAULT_SECTIONS
		);
	}

	/**
	 * Подсказка в поле заголовка вакансии.
	 *
	 * @param string   $text Подсказка.
	 * @param \WP_Post $post Запись.
	 */
	public static function title_placeholder( $text, $post ) {
		return $post instanceof \WP_Post && 'vacancy' === $post->post_type ? __( 'Название вакансии: «SEO-специалист»', 'trust' ) : $text;
	}

	/**
	 * Колонки списка вакансий (отдел — колонка таксономии).
	 *
	 * @param array $columns Колонки.
	 */
	public static function columns( array $columns ): array {
		return Admin::insert_columns(
			$columns,
			'title',
			array(
				'trust_salary' => __( 'Зарплата', 'trust' ),
				'trust_format' => __( 'Формат', 'trust' ),
			),
			array( 'trust_open' => __( 'Принимаем резюме', 'trust' ) )
		);
	}

	/**
	 * Значение колонки.
	 *
	 * @param string $column  Колонка.
	 * @param int    $post_id Вакансия.
	 */
	public static function column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'trust_salary':
			case 'trust_format':
				echo esc_html( (string) get_post_meta( $post_id, $column, true ) );
				break;
			case 'trust_open':
				echo esc_html( self::is_open( $post_id ) ? __( 'Да', 'trust' ) : __( 'Нет — скрыта', 'trust' ) );
				break;
		}
	}
}
