<?php
/**
 * Формы блоков шага 4.6 (ТЗ, раздел 12.1): «Заявка на аудит» (блок «С какими проблемами…»),
 * «Бриф» (полный) и «Бриф (короткий)».
 *
 * В брифах по решению заказчика добавлены «Имя» и «Как с вами связаться?» (телефон или email):
 * в макетах полей для связи нет, а без них ответить на заявку нельзя.
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
	}

	/**
	 * Общие поля: имя и контакт (телефон или email).
	 *
	 * @param string $contact_label Подпись поля контакта.
	 */
	private static function person( string $contact_label ): array {
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
			) + self::person( __( 'Как с вами связаться', 'trust' ) ),
			'consents' => array( 'consent' => Forms::consent() ),
			'name'     => 'name',
			'contact'  => array( 'contact' ),
		);

		$types[ self::BRIEF_QUICK ] = array(
			'label'    => __( 'Бриф (короткий)', 'trust' ),
			'captcha'  => false,
			'fields'   => array( 'url' => self::url() ) + self::person( __( 'Как с вами связаться', 'trust' ) ) + array(
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
