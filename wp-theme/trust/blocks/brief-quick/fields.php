<?php
/**
 * Поля блока «Бриф — короткий» (макет holst-blokov/BriefQuick, seo-bloki/11b-brif-korotkiy.html).
 *
 * Поля формы, проверки и сообщения об ошибках — форма «Бриф (короткий)»
 * (Trust\Forms\Block_Forms), здесь — тексты и email для доступа к статистике.
 *
 * @package Trust
 */

use Trust\Blocks;

defined( 'ABSPATH' ) || exit;

return array_merge(
	Blocks::section_fields(
		array(
			'anchor'  => 'free-audit',
			'eyebrow' => __( 'Бесплатный аудит', 'trust' ),
			'title'   => __( 'Получите бесплатный аудит сайта', 'trust' ),
			'lead'    => __( 'Укажите адрес сайта — проверим его и пришлём список ключевых ошибок с рекомендациями.', 'trust' ),
		)
	),
	array(
		Blocks::text_field( 'url_placeholder', __( 'Подсказка в поле «Адрес сайта»', 'trust' ), 'https://example.ru', 60 ),
		Blocks::text_field( 'name_placeholder', __( 'Подсказка в поле «Имя»', 'trust' ), __( 'Ваше имя', 'trust' ), 40 ),
		Blocks::text_field( 'contact_placeholder', __( 'Подсказка в поле контакта', 'trust' ), __( 'Как с вами связаться?', 'trust' ), 60, __( 'Принимаем телефон или email.', 'trust' ) ),
		Blocks::text_field( 'submit', __( 'Текст кнопки', 'trust' ), __( 'Получить бесплатный аудит', 'trust' ), 40 ),
		Blocks::text_field( 'divider', __( 'Разделитель над карточкой', 'trust' ), __( 'Также советуем', 'trust' ), 30 ),
		Blocks::text_field( 'share_title', __( 'Карточка: заголовок', 'trust' ), __( 'Расшарить доступ к статистике', 'trust' ), 60 ),
		Blocks::text_field( 'share_text', __( 'Карточка: текст', 'trust' ), __( 'Яндекс Метрика, Вебмастер или Google Search Console — с данными аудит будет точнее', 'trust' ), 160 ),
		array(
			'name'         => 'share_email',
			'label'        => __( 'Email для доступа к статистике', 'trust' ),
			'type'         => 'email',
			'instructions' => __( 'Показывается, когда посетитель отмечает флажок. Пусто — основной email из «Настроек сайта → Контакты».', 'trust' ),
		),
		Blocks::text_field( 'share_note', __( 'Текст с email', 'trust' ), __( 'Предоставьте нам доступ к статистике на email', 'trust' ), 120, __( 'После текста — email ссылкой.', 'trust' ) ),
		Blocks::text_field( 'success_title', __( 'Заявка отправлена: заголовок', 'trust' ), __( 'Заявка на аудит отправлена', 'trust' ), 60 ),
		Blocks::text_field( 'success_text', __( 'Заявка отправлена: текст', 'trust' ), __( 'Проверим сайт и пришлём список ключевых ошибок с рекомендациями.', 'trust' ), 160 ),
		Blocks::text_field( 'again', __( 'Кнопка «ещё одна заявка»', 'trust' ), __( 'Отправить ещё одну заявку', 'trust' ), 60 ),
	)
);
