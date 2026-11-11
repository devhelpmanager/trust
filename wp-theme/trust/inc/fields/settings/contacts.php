<?php
/**
 * Настройки сайта → Контакты.
 *
 * Используются в шапке, подвале, окне звонка, на странице контактов и в микроразметке.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'label'  => __( 'Контакты', 'trust' ),
	'fields' => array(
		array(
			'name'         => 'phones',
			'label'        => __( 'Телефоны', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'button_label' => __( 'Добавить телефон', 'trust' ),
			'instructions' => __( 'Основной телефон показывается в шапке, подвале и на кнопках «Позвонить». Если основной не отмечен — берётся первый.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'        => 'number',
					'label'       => __( 'Номер', 'trust' ),
					'type'        => 'text',
					'required'    => 1,
					'placeholder' => Trust\Forms::phone_example(),
				),
				array(
					'name'        => 'label',
					'label'       => __( 'Подпись', 'trust' ),
					'type'        => 'text',
					'placeholder' => __( 'Например: Отдел продаж', 'trust' ),
				),
				array(
					'name'  => 'is_main',
					'label' => __( 'Основной', 'trust' ),
					'type'  => 'true_false',
					'ui'    => 1,
				),
			),
		),
		array(
			'name'         => 'emails',
			'label'        => __( 'Email', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'button_label' => __( 'Добавить email', 'trust' ),
			'instructions' => __( 'Первый адрес — основной: он показывается в шапке и подвале.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'        => 'email',
					'label'       => __( 'Адрес', 'trust' ),
					'type'        => 'email',
					'required'    => 1,
					'placeholder' => 'hello@example.ru',
				),
				array(
					'name'        => 'label',
					'label'       => __( 'Подпись', 'trust' ),
					'type'        => 'text',
					'placeholder' => __( 'Например: Для вопросов и заявок', 'trust' ),
				),
			),
		),
		array(
			'name'         => 'telegram',
			'label'        => __( 'Telegram', 'trust' ),
			'type'         => 'text',
			'placeholder'  => '@name',
			'wrapper'      => array( 'width' => 50 ),
			'instructions' => __( 'Имя пользователя (@name) или ссылка https://t.me/name.', 'trust' ),
		),
		array(
			'name'         => 'whatsapp',
			'label'        => __( 'WhatsApp', 'trust' ),
			'type'         => 'text',
			'placeholder'  => Trust\Forms::phone_example(),
			'wrapper'      => array( 'width' => 50 ),
			'instructions' => __( 'Номер в международном формате или ссылка https://wa.me/….', 'trust' ),
		),
		array(
			'name'         => 'hours',
			'label'        => __( 'Часы работы', 'trust' ),
			'type'         => 'text',
			'placeholder'  => __( 'Пн–Пт, 10:00–19:00', 'trust' ),
			'instructions' => __( 'Как показывать на сайте: в шапке, подвале и на странице контактов.', 'trust' ),
		),
		array(
			'name'         => 'address',
			'label'        => __( 'Адрес офиса', 'trust' ),
			'type'         => 'group',
			'layout'       => 'block',
			'instructions' => __( 'Показывается в подвале и на странице контактов, используется в микроразметке. Индекс на сайте не выводится — только в микроразметке.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'    => 'city',
					'label'   => __( 'Город', 'trust' ),
					'type'    => 'text',
					'wrapper' => array( 'width' => 25 ),
				),
				array(
					'name'    => 'street',
					'label'   => __( 'Улица, дом', 'trust' ),
					'type'    => 'text',
					'wrapper' => array( 'width' => 35 ),
				),
				array(
					'name'    => 'office',
					'label'   => __( 'Этаж, офис', 'trust' ),
					'type'    => 'text',
					'wrapper' => array( 'width' => 25 ),
				),
				array(
					'name'    => 'postal_code',
					'label'   => __( 'Индекс', 'trust' ),
					'type'    => 'text',
					'wrapper' => array( 'width' => 15 ),
				),
			),
		),
		array(
			'name'         => 'geo',
			'label'        => __( 'Координаты офиса', 'trust' ),
			'type'         => 'group',
			'layout'       => 'block',
			'instructions' => __( 'Для микроразметки. Скопируйте из Яндекс Карт: нажмите на точку — координаты под адресом.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'        => 'lat',
					'label'       => __( 'Широта', 'trust' ),
					'type'        => 'text',
					'placeholder' => '55.751244',
					'wrapper'     => array( 'width' => 50 ),
				),
				array(
					'name'        => 'lng',
					'label'       => __( 'Долгота', 'trust' ),
					'type'        => 'text',
					'placeholder' => '37.618423',
					'wrapper'     => array( 'width' => 50 ),
				),
			),
		),
		array(
			'name'         => 'directions_transport',
			'label'        => __( 'Как добраться: транспорт', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 2,
			'placeholder'  => __( 'Станция метро, сколько минут пешком', 'trust' ),
			'instructions' => __( 'Для блока «Приезжайте в гости» на странице контактов.', 'trust' ),
		),
		array(
			'name'        => 'directions_parking',
			'label'       => __( 'Как добраться: на машине', 'trust' ),
			'type'        => 'textarea',
			'rows'        => 2,
			'placeholder' => __( 'Где оставить машину: гостевая парковка, въезд с улицы', 'trust' ),
		),
		array(
			'name'        => 'directions_note',
			'label'       => __( 'Как добраться: примечание', 'trust' ),
			'type'        => 'textarea',
			'rows'        => 2,
			'placeholder' => __( 'Например: перед визитом позвоните — закажем пропуск', 'trust' ),
		),
		array(
			'name'        => 'yandex_maps_url',
			'label'       => __( 'Ссылка на офис в Яндекс Картах', 'trust' ),
			'type'        => 'url',
			'placeholder' => 'https://yandex.ru/maps/…',
			'wrapper'     => array( 'width' => 50 ),
		),
		array(
			'name'        => 'gis_url',
			'label'       => __( 'Ссылка на офис в 2ГИС', 'trust' ),
			'type'        => 'url',
			'placeholder' => 'https://2gis.ru/…',
			'wrapper'     => array( 'width' => 50 ),
		),
		array(
			'name'          => 'map_image',
			'label'         => __( 'Схема проезда', 'trust' ),
			'type'          => 'image',
			'return_format' => 'id',
			'preview_size'  => 'medium',
			'instructions'  => __( 'Картинка вместо интерактивной карты, 16:9, не меньше 1200 × 675 px. Alt — в медиатеке.', 'trust' ),
		),
		array(
			'name'         => 'map_embed',
			'label'        => __( 'Код интерактивной карты', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 4,
			'trust_cap'    => 'manage_options',
			'instructions' => __( 'Необязательно. Код из конструктора Яндекс Карт. Карта загружается только по кнопке «Показать карту», до этого видна схема проезда. Поле видно только администратору.', 'trust' ),
		),
	),
);
