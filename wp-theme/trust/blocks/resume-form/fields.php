<?php
/**
 * Поля блока «Отправить резюме» (ТЗ, п. 8.8 и 12.1; макет o-nas/karera.html, «Отклик»).
 * Поля формы, правила и сообщения об ошибках — Trust\Forms\Resume_Form; email и Telegram HR —
 * «Настройки сайта → Контакты»; здесь — тексты блока.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;

return array_merge(
	Blocks::section_fields(
		array(
			'anchor'  => Trust\Vacancies::FORM_ANCHOR,
			'eyebrow' => __( 'Отклик', 'trust' ),
			'title'   => __( 'Отправить резюме', 'trust' ),
			'lead'    => __( 'Выберите вакансию или отправьте резюме без неё — свяжемся, когда появится подходящее место.', 'trust' ),
		)
	),
	array(
		Blocks::text_field( 'hr_label', __( 'Подпись над контактами HR', 'trust' ), __( 'HR-отдел', 'trust' ), 40, __( 'Email и Telegram — «Настройки сайта → Контакты → Email и Telegram для резюме».', 'trust' ) ),
		array(
			'name'          => 'intern_option',
			'label'         => __( 'Пункт «Стажировка» в списке вакансий', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'instructions'  => __( 'Выключите, если стажировка заведена отдельной вакансией в разделе «Вакансии» — тогда она уже есть в списке.', 'trust' ),
		),
		Blocks::text_field( 'link_placeholder', __( 'Подсказка в поле ссылки на резюме', 'trust' ), __( 'hh.ru, GitHub, Behance или облачный диск', 'trust' ), 100 ),
		Blocks::text_field( 'about_placeholder', __( 'Подсказка в поле «О себе»', 'trust' ), __( 'Почему вам интересна эта работа, какие проекты делали', 'trust' ), 100 ),
		Blocks::text_field( 'submit', __( 'Кнопка отправки', 'trust' ), __( 'Отправить резюме', 'trust' ), 40 ),
		Blocks::text_field( 'success_title', __( 'Заголовок после отправки', 'trust' ), __( 'Резюме отправлено', 'trust' ), 60 ),
		Blocks::text_field( 'success_text', __( 'Текст после отправки', 'trust' ), __( 'Спасибо! HR-менеджер изучит резюме и ответит в течение [N] рабочих дней.', 'trust' ), 200 ),
		Blocks::text_field( 'again', __( 'Кнопка «ещё одно резюме»', 'trust' ), __( 'Отправить ещё одно резюме', 'trust' ), 40 ),
	)
);
