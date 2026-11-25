<?php
/**
 * Поля блока «Реквизиты» (ТЗ, п. 8.9; макет o-nas/kontakty.html, «Реквизиты»). Сами реквизиты и
 * файл карточки компании — «Настройки сайта → Реквизиты»; здесь — подписи.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;

$trust_block_view = static fn( string $view ): array => array(
	array(
		array(
			'field'    => 'field_trust_block_requisites_view',
			'operator' => '==',
			'value'    => $view,
		),
	),
);

return array_merge(
	array(
		array(
			'name'          => 'view',
			'label'         => __( 'Вид', 'trust' ),
			'type'          => 'button_group',
			'choices'       => array(
				'block'    => __( 'Блок страницы', 'trust' ),
				'document' => __( 'В тексте документа', 'trust' ),
			),
			'default_value' => 'block',
			'instructions'  => __( '«В тексте документа» — только таблица в рамке (под заголовком раздела, например «Реквизиты исполнителя») и ссылка на «Контакты».', 'trust' ),
		),
	),
	array_map(
		static fn( array $field ): array => $field + array( 'conditional_logic' => $trust_block_view( 'block' ) ),
		Blocks::section_fields(
			array(
				'anchor'  => 'requisites',
				'eyebrow' => __( 'Для договора и счетов', 'trust' ),
				'title'   => __( 'Реквизиты', 'trust' ),
				'lead'    => false,
			)
		)
	),
	array(
		array(
			'name'    => 'data_note',
			'label'   => __( 'Откуда данные', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Реквизиты и файл «Карточка компании, PDF» — «Настройки сайта → Реквизиты».', 'trust' ),
		),
		Blocks::text_field( 'copy_label', __( 'Кнопка копирования', 'trust' ), __( 'Скопировать', 'trust' ), 40 ) + array( 'conditional_logic' => $trust_block_view( 'block' ) ),
		Blocks::text_field( 'copied_label', __( 'Подпись после копирования', 'trust' ), __( 'Скопировано', 'trust' ), 30 ) + array( 'conditional_logic' => $trust_block_view( 'block' ) ),
		Blocks::text_field( 'card_label', __( 'Кнопка карточки компании', 'trust' ), __( 'Скачать PDF', 'trust' ), 40 ) + array( 'conditional_logic' => $trust_block_view( 'block' ) ),
		array(
			'name'              => 'contacts_link',
			'label'             => __( 'Ссылка на «Контакты» под таблицей', 'trust' ),
			'type'              => 'true_false',
			'ui'                => 1,
			'default_value'     => 1,
			'instructions'      => __( '«Полные реквизиты и карточка компании — на странице «Контакты»» (страница типа «Контакты»). При печати не выводится.', 'trust' ),
			'conditional_logic' => $trust_block_view( 'document' ),
		),
	)
);
