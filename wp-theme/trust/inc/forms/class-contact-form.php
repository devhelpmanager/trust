<?php
/**
 * Форма «Расскажите о задаче» на странице контактов (ТЗ, раздел 12.1, «Заявка с контактов»;
 * блок trust/contact-form): имя, телефон или email, задача, удобный способ связи, согласие.
 *
 * Заявка — обычная, в «Все заявки». Способ связи — подпись варианта из блока («Звонок»,
 * «Telegram»…): варианты задаёт блок, поэтому на сервере это короткий текст.
 *
 * @package Trust
 */

namespace Trust\Forms;

use Trust\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Форма заявки со страницы контактов.
 */
final class Contact_Form {

	/**
	 * Тип формы.
	 */
	public const TYPE = 'contact';

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'trust_form_types', array( self::class, 'types' ) );
	}

	/**
	 * Способы связи по умолчанию (как в макете): подпись и окончание фразы после отправки.
	 *
	 * @return array<int, array{label: string, say: string}>
	 */
	public static function default_channels(): array {
		return array(
			array(
				'label' => __( 'Звонок', 'trust' ),
				'say'   => __( 'позвонит по телефону', 'trust' ),
			),
			array(
				'label' => 'Telegram',
				'say'   => __( 'напишет в Telegram', 'trust' ),
			),
			array(
				'label' => 'WhatsApp',
				'say'   => __( 'напишет в WhatsApp', 'trust' ),
			),
			array(
				'label' => 'Email',
				'say'   => __( 'напишет на почту', 'trust' ),
			),
		);
	}

	/**
	 * Описание формы; сообщения — как в макете.
	 *
	 * @param array $types Формы сайта.
	 */
	public static function types( array $types ): array {
		$types[ self::TYPE ] = array(
			'label'    => __( 'Заявка с контактов', 'trust' ),
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
					'label'    => __( 'Ваши контакты', 'trust' ), // Правка заказчика (в макете «Телефон или email»).
					'type'     => 'text',
					'required' => true,
					'min'      => 5,
					'max'      => 100,
					'error'    => __( 'Оставьте телефон или email', 'trust' ),
				),
				'task'    => array(
					'label' => __( 'Задача', 'trust' ),
					'type'  => 'textarea',
					'max'   => 2000,
					'error' => __( 'Не длиннее 2000 символов', 'trust' ),
				),
				'channel' => array(
					'label' => __( 'Как связаться', 'trust' ),
					'type'  => 'text',
					'max'   => 60,
					'error' => __( 'Выберите способ связи', 'trust' ),
				),
			),
			'consents' => array( 'consent' => Forms::consent() ),
			'name'     => 'name',
			'contact'  => array( 'contact' ),
		);
		return $types;
	}
}
