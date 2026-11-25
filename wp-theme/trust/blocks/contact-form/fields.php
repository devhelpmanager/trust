<?php
/**
 * Поля блока «Расскажите о задаче» (ТЗ, п. 8.9 и 12.1; макет o-nas/kontakty.html, «Заявка»).
 * Поля формы, правила и сообщения — Trust\Forms\Contact_Form; здесь — тексты, шаги и способы связи.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;

return array_merge(
	Blocks::section_fields(
		array(
			'anchor'  => 'contact-form',
			'eyebrow' => __( 'Заявка', 'trust' ),
			'title'   => __( 'Расскажите о задаче', 'trust' ),
			'lead'    => __( 'Опишите проект — менеджер свяжется удобным для вас способом и задаст уточняющие вопросы.', 'trust' ),
		)
	),
	array(
		array(
			'name'         => 'steps',
			'label'        => __( 'Что будет дальше', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'max'          => 5,
			'button_label' => __( 'Добавить шаг', 'trust' ),
			'instructions' => __( 'Шаги с номерами 1, 2, 3 под текстом. Пусто — без шагов.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'      => 'text',
					'label'     => __( 'Шаг', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 100,
				),
			),
		),
		array(
			'name'         => 'channels',
			'label'        => __( 'Способы связи', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'max'          => 6,
			'button_label' => __( 'Добавить способ', 'trust' ),
			'instructions' => __( 'Пусто — «Звонок», «Telegram», «WhatsApp», «Email». Первый выбран заранее. «Как сообщить» подставляется в текст после отправки вместо {channel}: «Менеджер позвонит по телефону…».', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'      => 'label',
					'label'     => __( 'Способ', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 30,
				),
				array(
					'name'        => 'say',
					'label'       => __( 'Как сообщить', 'trust' ),
					'type'        => 'text',
					'maxlength'   => 60,
					'placeholder' => __( 'позвонит по телефону', 'trust' ),
				),
			),
		),
		Blocks::text_field( 'channel_legend', __( 'Подпись способов связи', 'trust' ), __( 'Как с вами связаться', 'trust' ), 60 ),
		Blocks::text_field( 'contact_placeholder', __( 'Подсказка в поле «Телефон или email»', 'trust' ), Trust\Forms::phone_example(), 60 ),
		Blocks::text_field( 'task_placeholder', __( 'Подсказка в поле «Задача»', 'trust' ), __( 'Например: нужен аудит и продвижение интернет-магазина мебели', 'trust' ), 120 ),
		Blocks::text_field( 'submit', __( 'Кнопка отправки', 'trust' ), __( 'Отправить заявку', 'trust' ), 40 ),
		Blocks::text_field( 'success_title', __( 'Заголовок после отправки', 'trust' ), __( 'Заявка отправлена', 'trust' ), 60 ),
		Blocks::text_field( 'success_text', __( 'Текст после отправки', 'trust' ), __( 'Спасибо! Менеджер {channel} и уточнит детали задачи.', 'trust' ), 200, __( '{channel} — «Как сообщить» выбранного способа связи.', 'trust' ) ),
		Blocks::text_field( 'again', __( 'Кнопка «ещё одна заявка»', 'trust' ), __( 'Отправить ещё одну заявку', 'trust' ), 40 ),
	)
);
