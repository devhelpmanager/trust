<?php
/**
 * Поля вопроса (ТЗ, раздел 5.3). Заголовок записи — короткая формулировка вопроса (H1),
 * текст записи (редактор блоков) — ответ специалиста. Тема и теги — панели редактора.
 *
 * Вопросы из формы «Задать вопрос» приходят «На утверждении» (шаг 3.5); когда ответ
 * опубликован, спросившему уходит письмо со ссылкой (шаг 5.7).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_date = array(
	'type'           => 'date_picker',
	'display_format' => 'd.m.Y',
	'return_format'  => 'Y-m-d',
	'first_day'      => 1,
);

return array(
	'title'    => __( 'Вопрос', 'trust' ),
	'location' => 'post_type',
	'position' => 'side',
	'fields'   => array(
		array(
			'name'         => 'trust_question',
			'label'        => __( 'Текст вопроса', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 6,
			'new_lines'    => 'wpautop',
			'required'     => 1,
			'instructions' => __( 'Как написал читатель; можно поправить опечатки. Заголовок записи — короткая формулировка.', 'trust' ),
		),
		array(
			'name'         => 'trust_asker',
			'label'        => __( 'Имя спросившего', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 40,
			'required'     => 1,
			'instructions' => __( 'Публикуется: «Спрашивает Анна».', 'trust' ),
		),
		array(
			'name'         => 'trust_asker_email',
			'label'        => __( 'Email спросившего', 'trust' ),
			'type'         => 'email',
			'instructions' => __( 'Не публикуется: сюда уйдёт ссылка на ответ.', 'trust' ),
		),
		array_merge(
			$trust_date,
			array(
				'name'  => 'trust_asked_at',
				'label' => __( 'Дата вопроса', 'trust' ),
			)
		),
		array(
			'name'          => 'trust_expert',
			'label'         => __( 'Отвечает', 'trust' ),
			'type'          => 'post_object',
			'post_type'     => array( 'team' ),
			'post_status'   => array( 'publish' ),
			'return_format' => 'id',
			'allow_null'    => 1,
			'ui'            => 1,
			'instructions'  => __( 'Сотрудник: фото, имя, должность и стаж у ответа.', 'trust' ),
		),
		array_merge(
			$trust_date,
			array(
				'name'         => 'trust_answered_at',
				'label'        => __( 'Дата ответа', 'trust' ),
				'instructions' => __( 'Пусто — дата публикации.', 'trust' ),
			)
		),
		array(
			'name'          => 'trust_related_posts',
			'label'         => __( 'Статьи по теме из блога', 'trust' ),
			'type'          => 'relationship',
			'post_type'     => array( 'post' ),
			'post_status'   => array( 'publish' ),
			'filters'       => array( 'search' ),
			'return_format' => 'id',
			'max'           => 4,
			'instructions'  => __( 'До 4. Пусто — подберутся статьи с общими тегами.', 'trust' ),
		),
		array(
			'name'         => 'trust_answer_sent_at',
			'label'        => __( 'Ссылка на ответ отправлена', 'trust' ),
			'type'         => 'text',
			'readonly'     => 1,
			'instructions' => __( 'Заполняется само, когда спросившему уходит письмо. Письмо уходит один раз.', 'trust' ),
		),
	),
);
