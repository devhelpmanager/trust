<?php
/**
 * Отзывы: виды, проверка полей, заголовок, колонки в админке.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Поля отзыва — inc/fields/content/review.php.
 */
final class Reviews {

	/**
	 * Виды отзывов: значение => название.
	 */
	public const KINDS = array(
		'text'   => 'Текстовый',
		'video'  => 'Видео',
		'letter' => 'Благодарственное письмо',
	);

	/**
	 * Статусы для колонки «Статус».
	 */
	private const STATUSES = array(
		'publish' => 'Опубликован',
		'pending' => 'На проверке',
		'draft'   => 'Черновик',
		'future'  => 'Запланирован',
		'private' => 'Личный',
	);

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'acf/validate_value/key=' . self::key( 'trust_text' ), array( self::class, 'validate_text' ), 10, 2 );
		add_filter( 'acf/validate_value/key=' . self::key( 'trust_letter' ), array( self::class, 'validate_letter' ), 10, 2 );
		add_filter( 'acf/validate_value/key=' . self::key( 'trust_video_url' ), array( self::class, 'validate_video' ), 10, 2 );
		add_action( 'acf/save_post', array( self::class, 'fill_title' ), 20 );
		add_action( 'acf/input/admin_footer', array( self::class, 'clear_errors_script' ) );
		add_filter( 'enter_title_here', array( self::class, 'title_placeholder' ), 10, 2 );
		add_filter( 'manage_review_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_review_posts_custom_column', array( self::class, 'column' ), 10, 2 );
	}

	/**
	 * Ключ поля отзыва.
	 *
	 * @param string $name Имя поля.
	 */
	private static function key( string $name ): string {
		return Fields::key( 'review', $name );
	}

	/**
	 * Вид отзыва.
	 *
	 * @param int $post_id Отзыв.
	 */
	public static function kind( int $post_id ): string {
		$kind = (string) get_post_meta( $post_id, 'trust_kind', true );
		return isset( self::KINDS[ $kind ] ) ? $kind : 'text';
	}

	/**
	 * Значение поля из отправленной формы редактирования.
	 * Проверку nonce Secure Custom Fields выполняет до валидации полей.
	 *
	 * @param string $name Имя поля.
	 */
	private static function posted( string $name ): string {
		$key = self::key( $name );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce проверяет SCF до вызова acf/validate_value.
		if ( ! isset( $_POST['acf'][ $key ] ) || ! is_scalar( $_POST['acf'][ $key ] ) ) {
			return '';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- см. выше.
		return sanitize_text_field( wp_unslash( (string) $_POST['acf'][ $key ] ) );
	}

	/**
	 * Текст обязателен у текстового отзыва.
	 *
	 * @param bool|string $valid Результат проверки.
	 * @param mixed       $value Значение поля.
	 * @return bool|string
	 */
	public static function validate_text( $valid, $value ) {
		if ( true === $valid && 'text' === ( self::posted( 'trust_kind' ) ? self::posted( 'trust_kind' ) : 'text' ) && '' === trim( (string) $value ) ) {
			return __( 'Напишите текст отзыва.', 'trust' );
		}
		return $valid;
	}

	/**
	 * Скан обязателен у благодарственного письма.
	 *
	 * @param bool|string $valid Результат проверки.
	 * @param mixed       $value Значение поля.
	 * @return bool|string
	 */
	public static function validate_letter( $valid, $value ) {
		if ( true === $valid && 'letter' === self::posted( 'trust_kind' ) && empty( $value ) ) {
			return __( 'Загрузите скан письма.', 'trust' );
		}
		return $valid;
	}

	/**
	 * У видеоотзыва нужна ссылка или файл.
	 *
	 * @param bool|string $valid Результат проверки.
	 * @param mixed       $value Значение поля.
	 * @return bool|string
	 */
	public static function validate_video( $valid, $value ) {
		if ( true === $valid && 'video' === self::posted( 'trust_kind' ) && empty( $value ) && '' === self::posted( 'trust_video_file' ) ) {
			return __( 'Укажите ссылку на видео или загрузите видеофайл.', 'trust' );
		}
		return $valid;
	}

	/**
	 * При смене вида отзыва убирает сообщения об ошибках прошлой проверки: «Загрузите скан
	 * письма» не должно висеть у текстового отзыва.
	 */
	public static function clear_errors_script(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'review' !== $screen->post_type ) {
			return;
		}
		printf(
			'<script>jQuery( function ( $ ) { $( document ).on( "change", %s, function () { $( ".acf-notice.-error" ).remove(); $( ".acf-field.acf-error" ).removeClass( "acf-error" ); } ); } );</script>',
			wp_json_encode( '.acf-field[data-key="' . self::key( 'trust_kind' ) . '"] input' )
		);
	}

	/**
	 * Пустой заголовок отзыва заполняется именем и компанией: «Анна Петрова, ООО Ромашка».
	 *
	 * @param int|string $post_id Запись, которую сохранил SCF.
	 */
	public static function fill_title( $post_id ): void {
		if ( ! is_numeric( $post_id ) || 'review' !== get_post_type( (int) $post_id ) || '' !== get_post_field( 'post_title', (int) $post_id ) ) {
			return;
		}

		$title = self::author_line( (int) $post_id, true );
		if ( '' === $title ) {
			return;
		}

		remove_action( 'acf/save_post', array( self::class, 'fill_title' ), 20 );
		wp_update_post(
			array(
				'ID'         => (int) $post_id,
				'post_title' => $title,
			)
		);
		add_action( 'acf/save_post', array( self::class, 'fill_title' ), 20 );
	}

	/**
	 * Имя автора отзыва с должностью и компанией.
	 *
	 * @param int  $post_id    Отзыв.
	 * @param bool $short      Только имя и компания (для заголовка).
	 */
	public static function author_line( int $post_id, bool $short = false ): string {
		$parts = array(
			(string) get_post_meta( $post_id, 'trust_name', true ),
			$short ? '' : (string) get_post_meta( $post_id, 'trust_position', true ),
			(string) get_post_meta( $post_id, 'trust_company', true ),
		);
		return implode( ', ', array_filter( array_map( 'trim', $parts ), 'strlen' ) );
	}

	/**
	 * Подсказка в поле заголовка отзыва.
	 *
	 * @param string   $text Подсказка.
	 * @param \WP_Post $post Запись.
	 */
	public static function title_placeholder( $text, $post ) {
		return $post instanceof \WP_Post && 'review' === $post->post_type
			? __( 'Заголовок для списка в админке — можно не заполнять', 'trust' )
			: $text;
	}

	/**
	 * Колонки списка отзывов: вид, автор, статус (услуги — колонка таксономии).
	 *
	 * @param array $columns Колонки.
	 */
	public static function columns( array $columns ): array {
		$date = $columns['date'] ?? null;
		unset( $columns['date'] );

		$result = array();
		foreach ( $columns as $key => $label ) {
			$result[ $key ] = $label;
			if ( 'title' === $key ) {
				$result['trust_kind']   = __( 'Вид', 'trust' );
				$result['trust_author'] = __( 'Автор отзыва', 'trust' );
			}
		}
		$result['trust_status'] = __( 'Статус', 'trust' );
		if ( $date ) {
			$result['date'] = $date;
		}
		return $result;
	}

	/**
	 * Значение колонки.
	 *
	 * @param string $column  Колонка.
	 * @param int    $post_id Отзыв.
	 */
	public static function column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'trust_kind':
				echo esc_html( self::KINDS[ self::kind( $post_id ) ] );
				break;
			case 'trust_author':
				echo esc_html( self::author_line( $post_id ) );
				break;
			case 'trust_status':
				$status = get_post_status( $post_id );
				echo esc_html( self::STATUSES[ $status ] ?? $status );
				break;
		}
	}
}
