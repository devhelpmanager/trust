<?php
/**
 * Поля блока «Первый экран вопросов» (макет holst-voprosov/Main → seo-vopros/vopros-seo.html).
 *
 * Поля формы, правила и сообщения об ошибках — форма «question» (Trust\Questions::form()),
 * общие для браузера и сервера; здесь — тексты экрана.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'name'    => 'h1_note',
		'label'   => __( 'Заголовок H1 и хлебные крошки', 'trust' ),
		'type'    => 'message',
		'message' => __( 'H1 — заголовок страницы (или поле «H1» в группе «SEO» справа). Хлебные крошки строятся автоматически. Вопрос из формы появляется в «Вопросах SEO» со статусом «На утверждении» и в «Заявках».', 'trust' ),
	),
	array(
		'name'          => 'badge',
		'label'         => __( 'Плашка', 'trust' ),
		'type'          => 'text',
		'default_value' => __( 'Бесплатно', 'trust' ),
		'maxlength'     => 30,
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
		'name'         => 'points',
		'label'        => __( 'Пункты', 'trust' ),
		'type'         => 'repeater',
		'layout'       => 'table',
		'max'          => 5,
		'button_label' => __( 'Добавить пункт', 'trust' ),
		'instructions' => __( 'Под текстом (на широком экране) или под формой.', 'trust' ),
		'sub_fields'   => array(
			Trust\Icons::field(
				array(
					'choices'       => array(
						'check' => __( 'Галочка', 'trust' ),
						'list'  => __( 'Документ', 'trust' ),
						'user'  => __( 'Человек', 'trust' ),
						'mail'  => __( 'Письмо', 'trust' ),
						'chat'  => __( 'Сообщение', 'trust' ),
					),
					'default_value' => 'check',
					'wrapper'       => array( 'width' => 30 ),
				)
			),
			array(
				'name'      => 'text',
				'label'     => __( 'Текст', 'trust' ),
				'type'      => 'text',
				'required'  => 1,
				'maxlength' => 80,
			),
		),
	),
	array(
		'name'          => 'link',
		'label'         => __( 'Ссылка под пунктами', 'trust' ),
		'type'          => 'link',
		'return_format' => 'array',
		'instructions'  => __( 'Например, «Читать ответы на вопросы» → #answers (список ответов ниже на странице).', 'trust' ),
	),
	array(
		'name'        => 'submit',
		'label'       => __( 'Текст кнопки', 'trust' ),
		'type'        => 'text',
		'maxlength'   => 30,
		'placeholder' => __( 'Получить ответ', 'trust' ),
		'wrapper'     => array( 'width' => 50 ),
	),
	array(
		'name'        => 'success_title',
		'label'       => __( 'Заголовок после отправки', 'trust' ),
		'type'        => 'text',
		'maxlength'   => 40,
		'placeholder' => __( 'Вопрос отправлен', 'trust' ),
		'wrapper'     => array( 'width' => 50 ),
	),
	array(
		'name'         => 'success_text',
		'label'        => __( 'Текст после отправки', 'trust' ),
		'type'         => 'textarea',
		'rows'         => 2,
		'new_lines'    => '',
		'maxlength'    => 300,
		'placeholder'  => __( 'Специалист ответит здесь, на сайте: ответ появится в списке ниже. Когда он будет готов, пришлём ссылку на {email}.', 'trust' ),
		'instructions' => __( '{email} — адрес, который указал посетитель.', 'trust' ),
	),
);
