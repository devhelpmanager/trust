<?php
/**
 * Формы блоков шага 4.6 (ТЗ, раздел 12.1): «Заявка на аудит» (блок «С какими проблемами…»),
 * «Бриф» (полный) и «Бриф (короткий)».
 *
 * В брифах по решению заказчика добавлены «Имя» и «Как с вами связаться?» — любой способ связи
 * (телефон, email, Telegram, Instagram…): в макетах полей для связи нет, а без них ответить на
 * заявку нельзя.
 *
 * @package Trust
 */

namespace Trust\Forms;

use Trust\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Описания форм блоков (фильтр trust_form_types).
 */
final class Block_Forms {

	/**
	 * Типы форм.
	 */
	public const AUDIT       = 'audit';
	public const BRIEF       = 'brief';
	public const BRIEF_QUICK = 'brief-quick';

	/**
	 * Варианты «Что хотите получить или с чем столкнулись» по умолчанию (макет seo-bloki/11a).
	 * В блоке их можно заменить своими: форма присылает подписи отмеченных вариантов.
	 */
	public const WANTS = array(
		'Вывести сайт в топ',
		'Больше заявок с сайта',
		'Упал трафик',
		'Страницы не индексируются',
		'Сайт медленно загружается',
		'Нужен новый сайт',
		'Настроить контекстную рекламу',
		'Другое',
	);

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'trust_form_types', array( self::class, 'types' ) );
		add_filter( 'acf/load_value/key=field_trust_block_brief_wants', array( self::class, 'default_wants' ), 20 );
	}

	/**
	 * Варианты брифа по умолчанию в настройках блока: пока список пустой, в нём 8 вариантов из
	 * макета — их можно переименовать, удалить или дополнить (правка заказчика).
	 *
	 * @param mixed $value Строки повторителя (по ключам подполей) или пусто.
	 * @return mixed
	 */
	public static function default_wants( $value ) {
		if ( ! empty( $value ) ) {
			return $value;
		}
		return array_map( static fn( string $option ): array => array( 'field_trust_block_brief_wants_option' => $option ), self::WANTS );
	}

	/**
	 * Общие поля: имя и контакт.
	 *
	 * @param string $contact_label Подпись поля контакта.
	 * @param bool   $any           Любой способ связи (телефон, email, Telegram, Instagram…) —
	 *                              решение заказчика для брифов; иначе телефон или email.
	 */
	private static function person( string $contact_label, bool $any = false ): array {
		if ( $any ) {
			return array(
				'name'    => self::person( $contact_label )['name'],
				'contact' => array(
					'label'    => $contact_label,
					'type'     => 'text',
					'required' => true,
					'min'      => 3,
					'max'      => 100,
					'error'    => __( 'Укажите, как с вами связаться: телефон, email, Telegram или другой способ', 'trust' ),
				),
			);
		}
		return array(
			'name'    => array(
				'label'    => __( 'Имя', 'trust' ),
				'type'     => 'text',
				'required' => true,
				'max'      => 100,
				'error'    => __( 'Укажите, как к вам обращаться', 'trust' ),
			),
			'contact' => array(
				'label'    => $contact_label,
				'type'     => 'contact',
				'required' => true,
				'max'      => 100,
				/* translators: %s: пример номера телефона. */
				'error'    => sprintf( __( 'Укажите телефон или email, например %s или name@example.ru', 'trust' ), Forms::phone_example() ),
			),
		);
	}

	/**
	 * Поле «Адрес сайта».
	 */
	private static function url(): array {
		return array(
			'label'    => __( 'Адрес сайта', 'trust' ),
			'type'     => 'url',
			'required' => true,
			'max'      => 200,
			'error'    => __( 'Проверьте адрес сайта, например example.ru', 'trust' ),
		);
	}

	/**
	 * Добавляет формы блоков.
	 *
	 * @param array $types Описания форм.
	 */
	public static function types( array $types ): array {
		$types[ self::AUDIT ] = array(
			'label'    => __( 'Заявка на аудит', 'trust' ),
			'captcha'  => true,
			'fields'   => array( 'url' => self::url() ) + self::person( __( 'Телефон или email', 'trust' ) ),
			'consents' => array( 'consent' => Forms::consent() ),
			'name'     => 'name',
			'contact'  => array( 'contact' ),
		);

		$types[ self::BRIEF ] = array(
			'label'    => __( 'Бриф', 'trust' ),
			'captcha'  => false,
			'fields'   => array(
				'mode'    => array(
					'label'    => __( 'Тип заявки', 'trust' ),
					'type'     => 'choice',
					'required' => true,
					'options'  => array(
						'promo' => __( 'Продвижение', 'trust' ),
						'audit' => __( 'Бесплатный аудит', 'trust' ),
					),
					'error'    => __( 'Выберите тип заявки', 'trust' ),
				),
				'url'     => self::url(),
				'topic'   => array(
					'label' => __( 'Тематика сайта', 'trust' ),
					'type'  => 'text',
					'max'   => 200,
					'error' => __( 'Тематика — не длиннее 200 символов', 'trust' ),
				),
				'problem' => array(
					'label' => __( 'Проблема или задача', 'trust' ),
					'type'  => 'textarea',
					'max'   => 2000,
					'error' => __( 'Описание — не длиннее 2000 символов', 'trust' ),
				),
				'wants'   => array(
					'label' => __( 'Что хотите получить', 'trust' ),
					'type'  => 'choices',
					'max'   => 100,
					'error' => __( 'Выберите варианты из списка', 'trust' ),
				),
			) + self::person( __( 'Как с вами связаться', 'trust' ), true ),
			'consents' => array( 'consent' => Forms::consent() ),
			'name'     => 'name',
			'contact'  => array( 'contact' ),
		);

		$types[ self::BRIEF_QUICK ] = array(
			'label'    => __( 'Бриф (короткий)', 'trust' ),
			'captcha'  => false,
			'fields'   => array( 'url' => self::url() ) + self::person( __( 'Как с вами связаться', 'trust' ), true ) + array(
				'share' => array(
					'label' => __( 'Расшарить доступ к статистике', 'trust' ),
					'type'  => 'checkbox',
					'error' => '',
				),
			),
			'consents' => array( 'consent' => Forms::consent() ),
			'name'     => 'name',
			'contact'  => array( 'contact' ),
		);

		return $types;
	}
}
