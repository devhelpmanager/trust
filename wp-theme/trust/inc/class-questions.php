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
 *
 * Форма «Вопрос SEO-специалисту» (блок «Первый экран вопросов»): после проверки и сохранения
 * заявки создаётся вопрос со статусом «На утверждении» — имя, email, полный текст, дата;
 * заголовок — первые слова вопроса (редактор правит его перед публикацией).
 */
final class Questions {

	/**
	 * Тип формы вопроса.
	 */
	public const FORM = 'question';

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'trust_form_types', array( self::class, 'form_type' ) );
		add_action( 'trust_form_submitted', array( self::class, 'from_form' ), 5, 3 );
		add_filter( 'enter_title_here', array( self::class, 'title_placeholder' ), 10, 2 );
		add_filter( 'manage_question_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_question_posts_custom_column', array( self::class, 'column' ), 10, 2 );
	}

	/**
	 * Форма «Вопрос SEO-специалисту»: поля, правила, сообщения (как в макете), капча.
	 *
	 * @param array $types Формы сайта.
	 */
	public static function form_type( array $types ): array {
		$types[ self::FORM ] = array(
			'label'    => __( 'Вопрос SEO-специалисту', 'trust' ),
			'captcha'  => true,
			'fields'   => array(
				'name'     => array(
					'label'    => __( 'Имя', 'trust' ),
					'type'     => 'text',
					'required' => true,
					'max'      => 40,
					'error'    => __( 'Укажите, как к вам обращаться', 'trust' ),
				),
				'email'    => array(
					'label'    => __( 'Email', 'trust' ),
					'type'     => 'email',
					'required' => true,
					'max'      => 100,
					'error'    => __( 'Проверьте email, например name@example.ru', 'trust' ),
				),
				'question' => array(
					'label'    => __( 'Вопрос', 'trust' ),
					'type'     => 'textarea',
					'required' => true,
					'min'      => 10,
					'max'      => 2000,
					'error'    => __( 'Опишите вопрос подробнее — хотя бы пару предложений', 'trust' ),
				),
			),
			'consents' => array( 'consent' => Forms::consent() ),
			'name'     => 'name',
			'contact'  => array( 'email' ),
		);
		return $types;
	}

	/**
	 * Заголовок вопроса из текста: первые слова, не длиннее 90 знаков.
	 *
	 * @param string $text Текст вопроса.
	 */
	public static function title_from( string $text ): string {
		$text  = trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
		$title = wp_trim_words( $text, 12, '' );
		if ( mb_strlen( $title ) > 90 ) {
			$title = rtrim( mb_substr( $title, 0, 90 ) );
			$space = mb_strrpos( $title, ' ' );
			$title = false !== $space && $space > 40 ? mb_substr( $title, 0, $space ) : $title;
		}
		$title = (string) preg_replace( '/[\s,.;:—-]+$/u', '', $title );
		return $title . ( $title !== $text ? '…' : '' );
	}

	/**
	 * Форма вопроса отправлена: вопрос «На утверждении», связь с заявкой.
	 *
	 * @param int    $lead   Заявка.
	 * @param string $type   Тип формы.
	 * @param array  $values Значения полей.
	 */
	public static function from_form( int $lead, string $type, array $values ): void {
		if ( self::FORM !== $type ) {
			return;
		}
		$id = wp_insert_post(
			wp_slash(
				array(
					'post_type'   => 'question',
					'post_status' => 'pending',
					'post_title'  => self::title_from( (string) $values['question'] ),
				)
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return;
		}
		$fields = array(
			'trust_question'    => (string) $values['question'],
			'trust_asker'       => (string) $values['name'],
			'trust_asker_email' => (string) $values['email'],
			'trust_asked_at'    => current_time( 'Ymd' ),
		);
		foreach ( $fields as $name => $value ) {
			update_field( Fields::key( 'question', $name ), $value, $id );
		}
		update_post_meta( $lead, 'trust_related', $id );
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
