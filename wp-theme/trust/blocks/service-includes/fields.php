<?php
/**
 * Поля блока «Что включает услуга» (макет holst-blokov/ServiceIncludes, seo-bloki/04-chto-vklyuchaet-usluga.html).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'includes',
			'eyebrow' => __( 'Состав услуги', 'trust' ),
			'title'   => __( 'Что включает услуга', 'trust' ),
			'lead'    => __( 'Нажмите на работу, чтобы узнать подробности. Каждую из них можно заказать отдельно — у неё есть своя страница с описанием и стоимостью.', 'trust' ),
		)
	),
	array(
		Trust\Blocks::head_art_field(),
		array(
			'name'          => 'checks_label',
			'label'         => __( 'Подпись над списком', 'trust' ),
			'type'          => 'text',
			'default_value' => __( 'Что проверяем', 'trust' ),
			'maxlength'     => 40,
			'instructions'  => __( 'Одна для всех работ блока: «Что проверяем», «Что делаем»…', 'trust' ),
		),
		array(
			'name'          => 'more_label',
			'label'         => __( 'Текст ссылки на страницу работы', 'trust' ),
			'type'          => 'text',
			'default_value' => __( 'Подробнее об услуге', 'trust' ),
			'maxlength'     => 40,
			'instructions'  => __( 'Если в работе у ссылки не указан свой текст.', 'trust' ),
		),
		array(
			'name'         => 'items',
			'label'        => __( 'Работы', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 1,
			'max'          => 20,
			'button_label' => __( 'Добавить работу', 'trust' ),
			'collapsed'    => 'field_trust_block_service_includes_items_title',
			'instructions' => __( 'Номера 01, 02… ставятся автоматически по порядку, порядок меняется перетаскиванием. На странице все работы закрыты, открыть можно несколько сразу.', 'trust' ),
			'sub_fields'   => array(
				Trust\Icons::field(
					array(
						'choices'       => array(
							'robot'    => __( 'Робот (robots.txt)', 'trust' ),
							'sitemap'  => __( 'Схема (карта сайта, структура)', 'trust' ),
							'server'   => __( 'Сервер (коды ответа, хостинг)', 'trust' ),
							'copy'     => __( 'Копии (дубли)', 'trust' ),
							'bolt'     => __( 'Молния (скорость)', 'trust' ),
							'mobile'   => __( 'Телефон (мобильная версия)', 'trust' ),
							'database' => __( 'База данных (индексация)', 'trust' ),
							'code'     => __( 'Код (микроразметка, вёрстка)', 'trust' ),
							'list'     => __( 'Список', 'trust' ),
							'sliders'  => __( 'Настройки', 'trust' ),
							'chat'     => __( 'Сообщение', 'trust' ),
							'user'     => __( 'Человек', 'trust' ),
							'check'    => __( 'Галочка', 'trust' ),
						),
						'default_value' => 'check',
					)
				),
				array(
					'name'         => 'title',
					'label'        => __( 'Название', 'trust' ),
					'type'         => 'text',
					'required'     => 1,
					'maxlength'    => 120,
					'instructions' => __( 'До 40 символов.', 'trust' ),
				),
				array(
					'name'         => 'description',
					'label'        => __( 'Описание', 'trust' ),
					'type'         => 'textarea',
					'rows'         => 3,
					'new_lines'    => '',
					'required'     => 1,
					'maxlength'    => 600,
					'instructions' => __( 'Что это за работа и зачем она нужна, 1–2 предложения (до 250 символов).', 'trust' ),
				),
				array(
					'name'         => 'price',
					'label'        => __( 'Цена', 'trust' ),
					'type'         => 'text',
					'maxlength'    => 40,
					'placeholder'  => 'от 15 000 ₽',
					'instructions' => __( 'Необязательно. Как показывать: «от 15 000 ₽».', 'trust' ),
				),
				array(
					'name'          => 'link',
					'label'         => __( 'Страница работы', 'trust' ),
					'type'          => 'link',
					'return_format' => 'array',
					'instructions'  => __( 'Необязательно. Текст ссылки можно не заполнять — будет «Подробнее об услуге».', 'trust' ),
				),
				array(
					'name'         => 'checks',
					'label'        => __( 'Что проверяем', 'trust' ),
					'type'         => 'repeater',
					'layout'       => 'table',
					'max'          => 10,
					'button_label' => __( 'Добавить строку', 'trust' ),
					'instructions' => __( 'Необязательно. 3–5 коротких строк. Пусто — блок справа не выводится.', 'trust' ),
					'sub_fields'   => array(
						array(
							'name'      => 'text',
							'label'     => __( 'Строка', 'trust' ),
							'type'      => 'text',
							'required'  => 1,
							'maxlength' => 200,
						),
					),
				),
			),
		),
	)
);
