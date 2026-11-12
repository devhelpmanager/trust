<?php
/**
 * Вопросы SEO: колонки в админке.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Поля вопроса — inc/fields/content/question.php. Письмо спросившему — шаг 5.7.
 */
final class Questions {

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'enter_title_here', array( self::class, 'title_placeholder' ), 10, 2 );
		add_filter( 'manage_question_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_question_posts_custom_column', array( self::class, 'column' ), 10, 2 );
	}

	/**
	 * Подсказка в поле заголовка вопроса.
	 *
	 * @param string   $text Подсказка.
	 * @param \WP_Post $post Запись.
	 */
	public static function title_placeholder( $text, $post ) {
		return $post instanceof \WP_Post && 'question' === $post->post_type ? __( 'Вопрос коротко — это заголовок H1', 'trust' ) : $text;
	}

	/**
	 * Колонки списка вопросов: спросивший, специалист, статус (тема — колонка таксономии).
	 *
	 * @param array $columns Колонки.
	 */
	public static function columns( array $columns ): array {
		unset( $columns['author'] );
		return Admin::insert_columns(
			$columns,
			'title',
			array(
				'trust_asker'  => __( 'Спрашивает', 'trust' ),
				'trust_expert' => __( 'Специалист', 'trust' ),
			),
			array( 'trust_status' => __( 'Статус', 'trust' ) )
		);
	}

	/**
	 * Значение колонки.
	 *
	 * @param string $column  Колонка.
	 * @param int    $post_id Вопрос.
	 */
	public static function column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'trust_asker':
				echo esc_html( (string) get_post_meta( $post_id, 'trust_asker', true ) );
				break;
			case 'trust_expert':
				$expert = (int) get_post_meta( $post_id, 'trust_expert', true );
				echo esc_html( $expert ? get_the_title( $expert ) : '—' );
				break;
			case 'trust_status':
				echo esc_html( Admin::status_label( $post_id ) );
				break;
		}
	}
}
