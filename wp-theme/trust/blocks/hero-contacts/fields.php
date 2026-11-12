<?php
/**
 * Поля блока «Первый экран контактов» (макет holst-o-nas/Main → o-nas/kontakty.html).
 *
 * Сами телефон, email, Telegram и WhatsApp — в «Настройках сайта → Контакты»; в блоке —
 * какие каналы показать, в каком порядке и с какими подписями.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'name'    => 'h1_note',
		'label'   => __( 'Заголовок H1 и хлебные крошки', 'trust' ),
		'type'    => 'message',
		'message' => __( 'H1 — заголовок страницы (или поле «H1» в группе «SEO» справа). Хлебные крошки строятся автоматически. Телефон, email, Telegram и WhatsApp — в «Настройках сайта → Контакты».', 'trust' ),
	),
	array(
		'name'          => 'badge',
		'label'         => __( 'Плашка', 'trust' ),
		'type'          => 'text',
		'default_value' => __( 'О нас', 'trust' ),
		'maxlength'     => 40,
	),
	array(
		'name'         => 'lead',
		'label'        => __( 'Текст', 'trust' ),
		'type'         => 'textarea',
		'rows'         => 3,
		'new_lines'    => '',
		'maxlength'    => 300,
		'instructions' => __( 'Под заголовком, 1–2 предложения.', 'trust' ),
	),
	array(
		'name'         => 'channels',
		'label'        => __( 'Карточки связи', 'trust' ),
		'type'         => 'repeater',
		'layout'       => 'table',
		'max'          => 4,
		'button_label' => __( 'Добавить карточку', 'trust' ),
		'instructions' => __( 'Пусто — все четыре, как в макете: телефон, email, Telegram, WhatsApp. Карточка не показывается, если канал не заполнен в настройках. Пустые подписи — по умолчанию (у телефона — часы работы).', 'trust' ),
		'sub_fields'   => array(
			array(
				'name'     => 'channel',
				'label'    => __( 'Канал', 'trust' ),
				'type'     => 'select',
				'required' => 1,
				'choices'  => array(
					'phone'    => __( 'Телефон', 'trust' ),
					'email'    => __( 'Email', 'trust' ),
					'telegram' => __( 'Telegram', 'trust' ),
					'whatsapp' => __( 'WhatsApp', 'trust' ),
				),
				'wrapper'  => array( 'width' => 25 ),
			),
			array(
				'name'        => 'label',
				'label'       => __( 'Подпись', 'trust' ),
				'type'        => 'text',
				'maxlength'   => 30,
				'placeholder' => __( 'Как у канала', 'trust' ),
			),
			array(
				'name'        => 'note',
				'label'       => __( 'Текст под контактом', 'trust' ),
				'type'        => 'text',
				'maxlength'   => 60,
				'placeholder' => __( 'Например: Для вопросов и заявок', 'trust' ),
			),
		),
	),
);
