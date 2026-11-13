<?php
/**
 * Поля блока «Бриф — полный» (макет holst-blokov/Brief, seo-bloki/11a-brif-polnyy.html).
 *
 * Поля формы, проверки и сообщения об ошибках — форма «Бриф» (Trust\Forms\Block_Forms),
 * здесь — подписи, подсказки и варианты «что хотите получить».
 *
 * @package Trust
 */

use Trust\Blocks;

defined( 'ABSPATH' ) || exit;

return array_merge(
	Blocks::section_fields(
		array(
			'anchor'  => 'brief',
			'eyebrow' => __( 'Бриф', 'trust' ),
			'title'   => __( 'Заполните бриф', 'trust' ),
			'lead'    => __( 'На продвижение или бесплатный аудит. Расскажите о сайте и задаче — подготовим предложение под вашу ситуацию.', 'trust' ),
		)
	),
	array(
		Blocks::text_field( 'mode_promo', __( 'Переключатель: первый вариант', 'trust' ), __( 'Продвижение', 'trust' ), 30 ),
		Blocks::text_field( 'mode_audit', __( 'Переключатель: второй вариант', 'trust' ), __( 'Бесплатный аудит', 'trust' ), 30 ),
		Blocks::text_field( 'url_label', __( 'Подпись «Адрес сайта»', 'trust' ), __( 'Адрес сайта', 'trust' ), 40 ),
		Blocks::text_field( 'url_placeholder', __( 'Подсказка в поле «Адрес сайта»', 'trust' ), 'https://example.ru', 60 ),
		Blocks::text_field( 'topic_label', __( 'Подпись «Тематика сайта»', 'trust' ), __( 'Тематика сайта', 'trust' ), 40 ),
		Blocks::text_field( 'topic_placeholder', __( 'Подсказка в поле «Тематика»', 'trust' ), __( 'Например, ремонт квартир', 'trust' ), 60 ),
		Blocks::text_field( 'problem_label', __( 'Подпись «Проблема или задача»', 'trust' ), __( 'Опишите проблему или задачу', 'trust' ), 60 ),
		Blocks::text_field( 'problem_placeholder', __( 'Подсказка в поле задачи', 'trust' ), __( 'Например: после переезда на новую CMS упал трафик из Яндекса', 'trust' ), 120 ),
		Blocks::text_field( 'wants_label', __( 'Подпись над вариантами', 'trust' ), __( 'Что хотите получить или с чем столкнулись', 'trust' ), 80 ),
		Blocks::text_field( 'wants_note', __( 'Пояснение к вариантам', 'trust' ), __( 'можно выбрать несколько', 'trust' ), 40 ),
		array(
			'name'         => 'wants',
			'label'        => __( 'Варианты', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'max'          => 12,
			'button_label' => __( 'Добавить вариант', 'trust' ),
			'instructions' => __( 'Пусто — 8 вариантов из макета («Вывести сайт в топ», «Больше заявок с сайта»…). Отмеченные варианты приходят в заявке.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'      => 'label',
					'label'     => __( 'Вариант', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 60,
				),
			),
		),
		Blocks::text_field( 'name_label', __( 'Подпись «Имя»', 'trust' ), __( 'Имя', 'trust' ), 40 ),
		Blocks::text_field( 'name_placeholder', __( 'Подсказка в поле «Имя»', 'trust' ), __( 'Как к вам обращаться', 'trust' ), 60 ),
		Blocks::text_field( 'contact_label', __( 'Подпись поля контакта', 'trust' ), __( 'Как с вами связаться?', 'trust' ), 40 ),
		Blocks::text_field( 'contact_placeholder', __( 'Подсказка в поле контакта', 'trust' ), __( 'Телефон, email или Telegram', 'trust' ), 60, __( 'Посетитель пишет любой способ связи: телефон, email, Telegram, Instagram…', 'trust' ) ),
		Blocks::text_field( 'submit', __( 'Текст кнопки', 'trust' ), __( 'Получить', 'trust' ), 40 ),
		Blocks::text_field( 'success_title', __( 'Заявка отправлена: заголовок', 'trust' ), __( 'Бриф отправлен', 'trust' ), 60 ),
		Blocks::text_field( 'success_text', __( 'Заявка отправлена: текст', 'trust' ), __( 'Изучим сайт и задачу и свяжемся с вами в течение рабочего дня.', 'trust' ), 160 ),
		Blocks::text_field( 'again', __( 'Кнопка «ещё одна заявка»', 'trust' ), __( 'Заполнить бриф ещё раз', 'trust' ), 60 ),
	)
);
