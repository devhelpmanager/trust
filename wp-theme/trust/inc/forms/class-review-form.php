<?php
/**
 * Форма «Оставьте отзыв» (ТЗ, раздел 12.1; блок trust/review-form): поля и правила, отзыв
 * «На утверждении» после отправки.
 *
 * Отправка — как у всех форм (Trust\Forms\Handler): сначала заявка «Отзыв с сайта» (уведомления),
 * затем запись «Отзыв» в статусе «На утверждении» с текстом, оценкой, услугой, контактами (на
 * сайт не выводятся) и датами двух согласий. Заявка и отзыв связаны (trust_related).
 *
 * @package Trust
 */

namespace Trust\Forms;

use Trust\Fields;
use Trust\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Форма отзыва.
 */
final class Review_Form {

	/**
	 * Тип формы.
	 */
	public const TYPE = 'review';

	/**
	 * Подписи оценок 1–5 (как в макете).
	 */
	public const RATINGS = array(
		'1' => 'Плохо',
		'2' => 'Так себе',
		'3' => 'Нормально',
		'4' => 'Хорошо',
		'5' => 'Отлично',
	);

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'trust_form_types', array( self::class, 'types' ) );
		add_action( 'trust_form_submitted', array( self::class, 'from_form' ), 5, 3 );
	}

	/**
	 * Услуги для выбора: термины «Услуги» (как в отзывах и кейсах) и «Другое».
	 *
	 * @return array<string, string> slug => название.
	 */
	public static function services(): array {
		$terms   = get_terms(
			array(
				'taxonomy'   => 'service_type',
				'hide_empty' => false,
				'orderby'    => 'term_id',
			)
		);
		$options = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$options[ $term->slug ] = $term->name;
		}
		$options['other'] = __( 'Другое', 'trust' );
		return $options;
	}

	/**
	 * Описание формы: имя, компания и должность, услуга, телефон или email, оценка, отзыв, два
	 * согласия; сообщения — как в макете.
	 *
	 * @param array $types Формы сайта.
	 */
	public static function types( array $types ): array {
		$types[ self::TYPE ] = array(
			'label'    => __( 'Отзыв с сайта', 'trust' ),
			'captcha'  => true,
			'fields'   => array(
				'name'    => array(
					'label'    => __( 'Имя и фамилия', 'trust' ),
					'type'     => 'text',
					'required' => true,
					'max'      => 100,
					'error'    => __( 'Укажите имя и фамилию', 'trust' ),
				),
				'company' => array(
					'label' => __( 'Компания и должность', 'trust' ),
					'type'  => 'text',
					'max'   => 150,
					'error' => __( 'Не длиннее 150 символов', 'trust' ),
				),
				'service' => array(
					'label'    => __( 'Услуга', 'trust' ),
					'type'     => 'choice',
					'required' => true,
					'options'  => self::services(),
					'error'    => __( 'Выберите услугу', 'trust' ),
				),
				'contact' => array(
					'label'    => __( 'Телефон или email', 'trust' ),
					'type'     => 'contact',
					'required' => true,
					'max'      => 100,
					'error'    => __( 'Оставьте телефон или email для подтверждения', 'trust' ),
				),
				'rating'  => array(
					'label'    => __( 'Оценка', 'trust' ),
					'type'     => 'choice',
					'required' => true,
					'options'  => self::RATINGS,
					'error'    => __( 'Поставьте оценку от 1 до 5', 'trust' ),
				),
				'text'    => array(
					'label'    => __( 'Отзыв', 'trust' ),
					'type'     => 'textarea',
					'required' => true,
					'min'      => 30,
					'max'      => 2000,
					'error'    => __( 'Напишите хотя бы пару предложений — от 30 символов', 'trust' ),
				),
			),
			'consents' => array(
				'consent' => array(
					'label' => __( 'Согласие на обработку персональных данных', 'trust' ),
					'error' => __( 'Отметьте согласие, чтобы отправить отзыв', 'trust' ),
				),
				'publish' => array(
					'label' => __( 'Согласие на распространение: публикация отзыва с именем, компанией и должностью', 'trust' ),
					'error' => __( 'Без этого согласия мы не сможем опубликовать отзыв', 'trust' ),
				),
			),
			'name'     => 'name',
			'contact'  => array( 'contact' ),
		);
		return $types;
	}

	/**
	 * Адрес страницы согласия на распространение («Настройки сайта → Блоки по умолчанию»).
	 */
	public static function publish_consent_url(): string {
		$page = (int) \Trust\Settings::get( 'publish_consent_page', 0 );
		return $page && 'publish' === get_post_status( $page ) ? (string) get_permalink( $page ) : '';
	}

	/**
	 * Форма отправлена: отзыв «На утверждении», связь с заявкой.
	 *
	 * @param int    $lead   Заявка.
	 * @param string $type   Тип формы.
	 * @param array  $values Значения полей (проверенные).
	 */
	public static function from_form( int $lead, string $type, array $values ): void {
		if ( self::TYPE !== $type ) {
			return;
		}
		$name    = (string) ( $values['name'] ?? '' );
		$company = (string) ( $values['company'] ?? '' );
		$contact = (string) ( $values['contact'] ?? '' );
		$id      = wp_insert_post(
			wp_slash(
				array(
					'post_type'   => 'review',
					'post_status' => 'pending',
					'post_title'  => implode( ', ', array_filter( array( $name, $company ), 'strlen' ) ),
				)
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return;
		}
		$now    = current_time( 'Y-m-d H:i:s' );
		$fields = array(
			'trust_kind'               => 'text',
			'trust_name'               => $name,
			'trust_company'            => $company,
			'trust_text'               => (string) ( $values['text'] ?? '' ),
			'trust_rating'             => (int) ( $values['rating'] ?? 0 ),
			'trust_date'               => current_time( 'Ymd' ),
			'trust_email'              => is_email( $contact ) ? $contact : '',
			'trust_phone'              => is_email( $contact ) ? '' : $contact,
			'trust_consent_at'         => $now,
			'trust_publish_consent_at' => $now,
		);
		foreach ( $fields as $field => $value ) {
			update_field( Fields::key( 'review', $field ), $value, $id );
		}
		$service = (string) ( $values['service'] ?? '' );
		if ( '' !== $service && 'other' !== $service ) {
			wp_set_object_terms( $id, array( $service ), 'service_type' );
		}
		update_post_meta( $lead, 'trust_related', $id );
	}
}
