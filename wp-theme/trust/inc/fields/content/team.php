<?php
/**
 * Поля сотрудника (ТЗ, раздел 5.3). Классический экран: заголовок — имя (H1 страницы
 * сотрудника), «Фото» — изображение записи, «Отделы» и «Порядок» — стандартные панели.
 *
 * Страница сотрудника — это и страница автора статей и ответов: связь с пользователем
 * WordPress задаётся здесь (Trust\Team::for_user()).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'    => __( 'Сотрудник', 'trust' ),
	'location' => 'post_type',
	'fields'   => array(
		array(
			'name'         => 'trust_position',
			'label'        => __( 'Должность', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 60,
			'required'     => 1,
			'placeholder'  => 'Старший SEO-специалист',
			'instructions' => __( 'Под именем в карточке, на странице сотрудника и у его статей.', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'         => 'trust_leader',
			'label'        => __( 'Руководство', 'trust' ),
			'type'         => 'true_false',
			'ui'           => 1,
			'message'      => __( 'Показывать в блоке «Руководство»', 'trust' ),
			'instructions' => __( 'Крупная карточка с описанием и контактом. Порядок — поле «Порядок» справа.', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'         => 'trust_career_start',
			'label'        => __( 'В профессии с (год)', 'trust' ),
			'type'         => 'number',
			'min'          => 1970,
			'max'          => (int) gmdate( 'Y' ),
			'placeholder'  => '2015',
			'instructions' => __( 'Стаж считается сам и растёт каждый год: «Опыт: 11 лет».', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'         => 'trust_experience_area',
			'label'        => __( 'Опыт в чём', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 30,
			'placeholder'  => 'в SEO',
			'instructions' => __( '«опыт в SEO — 11 лет», «11 лет в SEO». Пусто — просто «опыт — 11 лет».', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'         => 'trust_summary',
			'label'        => __( 'Коротко для карточки', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 3,
			'maxlength'    => 220,
			'instructions' => __( 'За что отвечает, 1–2 предложения. Выводится в карточке руководителя.', 'trust' ),
		),
		array(
			'name'         => 'trust_about',
			'label'        => __( 'Об авторе', 'trust' ),
			'type'         => 'wysiwyg',
			'tabs'         => 'visual',
			'toolbar'      => 'basic',
			'media_upload' => 0,
			'instructions' => __( 'Текст на странице сотрудника: чем занимается, опыт, образование, сертификаты. 2–4 абзаца.', 'trust' ),
		),
		array(
			'name'          => 'trust_topic_tags',
			'label'         => __( 'Пишет о: теги', 'trust' ),
			'type'          => 'taxonomy',
			'taxonomy'      => 'post_tag',
			'field_type'    => 'multi_select',
			'return_format' => 'id',
			'add_term'      => 0,
			'save_terms'    => 0,
			'load_terms'    => 0,
			'allow_null'    => 1,
			'instructions'  => __( 'Ссылки на страницы тегов в блоке «Пишет о».', 'trust' ),
			'wrapper'       => array( 'width' => 50 ),
		),
		array(
			'name'         => 'trust_topics',
			'label'        => __( 'Пишет о: свои темы', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 3,
			'new_lines'    => '',
			'instructions' => __( 'Темы без страницы тега, каждая с новой строки. Выводятся после тегов.', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'         => 'trust_socials',
			'label'        => __( 'Соцсети', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'button_label' => __( 'Добавить соцсеть', 'trust' ),
			'instructions' => __( 'Значки — официальные иконки сетей.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'     => 'network',
					'label'    => __( 'Сеть', 'trust' ),
					'type'     => 'select',
					'choices'  => Trust\Social::networks(),
					'required' => 1,
					'wrapper'  => array( 'width' => 30 ),
				),
				array(
					'name'        => 'url',
					'label'       => __( 'Ссылка', 'trust' ),
					'type'        => 'url',
					'placeholder' => 'https://',
					'required'    => 1,
				),
			),
		),
		array(
			'name'         => 'trust_email',
			'label'        => __( 'Email для связи', 'trust' ),
			'type'         => 'email',
			'instructions' => __( 'Публикуется в карточке руководителя. Пусто — не показывается.', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'          => 'trust_user',
			'label'         => __( 'Пользователь WordPress', 'trust' ),
			'type'          => 'user',
			'return_format' => 'id',
			'allow_null'    => 1,
			'multiple'      => 0,
			'instructions'  => __( 'Если сотрудник пишет статьи или отвечает на вопросы: его статьи и ответы покажут эту страницу как страницу автора.', 'trust' ),
			'wrapper'       => array( 'width' => 50 ),
		),
	),
);
