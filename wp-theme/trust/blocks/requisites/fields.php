<?php
/**
 * Поля блока «Реквизиты» (ТЗ, п. 8.9; макет o-nas/kontakty.html, «Реквизиты»). Сами реквизиты и
 * файл карточки компании — «Настройки сайта → Реквизиты»; здесь — подписи.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;

return array_merge(
	Blocks::section_fields(
		array(
			'anchor'  => 'requisites',
			'eyebrow' => __( 'Для договора и счетов', 'trust' ),
			'title'   => __( 'Реквизиты', 'trust' ),
			'lead'    => false,
		)
	),
	array(
		array(
			'name'    => 'data_note',
			'label'   => __( 'Откуда данные', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Реквизиты и файл «Карточка компании, PDF» — «Настройки сайта → Реквизиты».', 'trust' ),
		),
		Blocks::text_field( 'copy_label', __( 'Кнопка копирования', 'trust' ), __( 'Скопировать реквизиты', 'trust' ), 40 ),
		Blocks::text_field( 'copied_label', __( 'Подпись после копирования', 'trust' ), __( 'Скопировано', 'trust' ), 30 ),
		Blocks::text_field( 'card_label', __( 'Кнопка карточки компании', 'trust' ), __( 'Карточка компании, PDF', 'trust' ), 40 ),
	)
);
