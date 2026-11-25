<?php
/**
 * Поля блока «Кому написать» (ТЗ, п. 8.9; макет o-nas/kontakty.html, «Отделы»).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;
use Trust\Icons;

$trust_leader_on = array(
	array(
		array(
			'field'    => 'field_trust_block_departments_leader_card',
			'operator' => '==',
			'value'    => '1',
		),
	),
);

return array_merge(
	Blocks::section_fields(
		array(
			'anchor'  => 'departments',
			'eyebrow' => __( 'Отделы', 'trust' ),
			'title'   => __( 'Кому написать', 'trust' ),
			'lead'    => __( 'Напишите сразу в нужный отдел — так ответ придёт быстрее.', 'trust' ),
		)
	),
	array(
		array(
			'name'         => 'items',
			'label'        => __( 'Отделы', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'max'          => 12,
			'button_label' => __( 'Добавить отдел', 'trust' ),
			'instructions' => __( 'На компьютере — по три в ряд. Контакты — email, телефон, Telegram (пустые не выводятся).', 'trust' ),
			'collapsed'    => 'field_trust_block_departments_items_title',
			'sub_fields'   => array(
				Icons::field(
					array(
						'name'          => 'icon',
						'label'         => __( 'Значок', 'trust' ),
						'choices'       => array(
							'trend-up'  => __( 'Рост — новые проекты', 'trust' ),
							'support'   => __( 'Гарнитура — поддержка', 'trust' ),
							'document'  => __( 'Документ — бухгалтерия', 'trust' ),
							'people'    => __( 'Люди — работа у нас', 'trust' ),
							'partners'  => __( 'График — партнёрство', 'trust' ),
							'user'      => __( 'Человек', 'trust' ),
							'mail'      => __( 'Письмо', 'trust' ),
							'chat'      => __( 'Сообщение', 'trust' ),
							'megaphone' => __( 'Рупор — PR', 'trust' ),
							'code'      => __( 'Код — разработка', 'trust' ),
							'shield'    => __( 'Щит', 'trust' ),
						),
						'default_value' => 'mail',
						'wrapper'       => array( 'width' => 30 ),
					)
				),
				array(
					'name'      => 'title',
					'label'     => __( 'Название', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 60,
					'wrapper'   => array( 'width' => 70 ),
				),
				array(
					'name'         => 'text',
					'label'        => __( 'Описание', 'trust' ),
					'type'         => 'wysiwyg',
					'tabs'         => 'visual',
					'toolbar'      => 'basic',
					'media_upload' => 0,
					'instructions' => __( 'С какими вопросами писать, 1–2 предложения. Можно вставить ссылку.', 'trust' ),
				),
				array(
					'name'    => 'email',
					'label'   => __( 'Email', 'trust' ),
					'type'    => 'email',
					'wrapper' => array( 'width' => 34 ),
				),
				array(
					'name'        => 'phone',
					'label'       => __( 'Телефон', 'trust' ),
					'type'        => 'text',
					'maxlength'   => 30,
					'placeholder' => Trust\Forms::phone_example(),
					'wrapper'     => array( 'width' => 33 ),
				),
				array(
					'name'        => 'telegram',
					'label'       => __( 'Telegram', 'trust' ),
					'type'        => 'text',
					'maxlength'   => 60,
					'placeholder' => '@name',
					'wrapper'     => array( 'width' => 33 ),
				),
				array(
					'name'          => 'member',
					'label'         => __( 'Ответственный', 'trust' ),
					'type'          => 'post_object',
					'post_type'     => array( 'team' ),
					'return_format' => 'id',
					'allow_null'    => 1,
					'ui'            => 1,
					'instructions'  => __( 'Необязательно. Сотрудник, который отвечает за отдел: если email отдела пуст, берётся email из его карточки.', 'trust' ),
				),
			),
		),
		array(
			'name'          => 'leader_card',
			'label'         => __( 'Карточка «Письмо руководителю»', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'instructions'  => __( 'Тёмная карточка последней в списке.', 'trust' ),
		),
		array(
			'name'              => 'leader',
			'label'             => __( 'Руководитель', 'trust' ),
			'type'              => 'post_object',
			'post_type'         => array( 'team' ),
			'return_format'     => 'id',
			'allow_null'        => 1,
			'ui'                => 1,
			'instructions'      => __( 'Пусто — первый сотрудник с отметкой «Руководство». Его имя подставляется вместо {name}, email — из его карточки.', 'trust' ),
			'conditional_logic' => $trust_leader_on,
			'wrapper'           => array( 'width' => 50 ),
		),
		array(
			'name'              => 'leader_email',
			'label'             => __( 'Email', 'trust' ),
			'type'              => 'email',
			'instructions'      => __( 'Пусто — email из карточки руководителя.', 'trust' ),
			'conditional_logic' => $trust_leader_on,
			'wrapper'           => array( 'width' => 50 ),
		),
		array_merge( Blocks::text_field( 'leader_title', __( 'Заголовок карточки', 'trust' ), __( 'Письмо руководителю', 'trust' ), 60 ), array( 'conditional_logic' => $trust_leader_on ) ),
		array_merge(
			Blocks::text_field( 'leader_text', __( 'Текст карточки', 'trust' ), __( 'Если что-то пошло не так, напишите напрямую — письмо прочитает {name} лично', 'trust' ), 200, __( '{name} — имя руководителя.', 'trust' ) ),
			array( 'conditional_logic' => $trust_leader_on )
		),
	)
);
