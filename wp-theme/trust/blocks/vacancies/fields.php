<?php
/**
 * Поля блока «Открытые вакансии» (ТЗ, п. 8.8; макет o-nas/karera.html, «Вакансии»). Сами
 * вакансии — раздел «Вакансии»; email для резюме — «Настройки сайта → Контакты».
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'vacancies',
			'eyebrow' => __( 'Вакансии', 'trust' ),
			'title'   => __( 'Открытые вакансии', 'trust' ),
			'lead'    => '',
		)
	),
	array(
		array(
			'name'          => 'filter',
			'label'         => __( 'Фильтр по отделам', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'instructions'  => __( 'Кнопки «Все», «SEO», «Разработка»… с числом вакансий. Показываются, если отделов больше одного.', 'trust' ),
			'wrapper'       => array( 'width' => 50 ),
		),
		array(
			'name'          => 'open_first',
			'label'         => __( 'Первая вакансия раскрыта', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'instructions'  => __( 'Раскрыта может быть одна вакансия: при открытии другой предыдущая сворачивается.', 'trust' ),
			'wrapper'       => array( 'width' => 50 ),
		),
		Trust\Blocks::text_field( 'apply', __( 'Кнопка отклика', 'trust' ), __( 'Откликнуться', 'trust' ), 40, __( 'Ведёт к форме «Отправить резюме» и выбирает в ней вакансию.', 'trust' ) ),
		Trust\Blocks::text_field( 'mail_text', __( 'Текст перед email', 'trust' ), __( 'Или пришлите резюме на', 'trust' ), 60, __( 'Email — «Настройки сайта → Контакты → Email для резюме».', 'trust' ) ),
		array(
			'name'          => 'empty',
			'label'         => __( 'Текст, если вакансий нет', 'trust' ),
			'type'          => 'textarea',
			'rows'          => 2,
			'new_lines'     => '',
			'maxlength'     => 300,
			'default_value' => __( 'Сейчас открытых вакансий нет. Пришлите резюме — свяжемся, когда появится подходящее место.', 'trust' ),
		),
	)
);
