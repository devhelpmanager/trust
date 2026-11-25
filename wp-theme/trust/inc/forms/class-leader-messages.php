<?php
/**
 * Обращения к руководителю (правка заказчика): форма в окне блока «Слово руководителя» и
 * подпункт «Заявки → Обращения к руководителю».
 *
 * Обращение — обычная заявка (тип записи lead, форма leader): те же защита, статусы,
 * уведомления (свои адреса — «Настройки сайта → Формы»), CSV и срок хранения. В «Все заявки»
 * обращения не попадают — только в свой подпункт со счётчиком новых (Trust\Forms\Lead_Sections).
 *
 * @package Trust
 */

namespace Trust\Forms;

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
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'trust_form_types', array( self::class, 'types' ) );
		add_filter( 'trust_lead_sections', array( self::class, 'section' ) );
	}

	/**
	 * Подпункт «Заявки → Обращения к руководителю» (Trust\Forms\Lead_Sections): после
	 * «Обратной связи», колонка «Сообщение» вместо «Формы».
	 *
	 * @param array $sections Подпункты.
	 */
	public static function section( array $sections ): array {
		$sections[ self::TYPE ] = array(
			'menu'      => __( 'Обращения к руководителю', 'trust' ),
			'not_found' => __( 'Обращений пока нет: они появляются, когда посетитель отправляет форму «Написать руководителю» в блоке «Слово руководителя».', 'trust' ),
			'position'  => 2,
			'columns'   => array( 'trust_leader_message' => __( 'Сообщение', 'trust' ) ),
			'column'    => array( self::class, 'column' ),
		);
		return $sections;
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
		return self::TYPE === Lead_Sections::current();
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
}
