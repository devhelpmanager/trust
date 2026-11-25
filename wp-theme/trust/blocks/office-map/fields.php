<?php
/**
 * Поля блока «Приезжайте в гости» (ТЗ, п. 8.9; макет o-nas/kontakty.html, «Офис»). Адрес, как
 * добраться, часы, ссылки на карты, схема и код карты — «Настройки сайта → Контакты»; здесь — подписи.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;

return array_merge(
	Blocks::section_fields(
		array(
			'anchor'  => 'office',
			'eyebrow' => __( 'Офис', 'trust' ),
			'title'   => __( 'Приезжайте в гости', 'trust' ),
			'lead'    => false,
		)
	),
	array(
		array(
			'name'    => 'data_note',
			'label'   => __( 'Откуда данные', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Адрес, «Как добраться», часы работы, ссылки на Яндекс Карты и 2ГИС, схема проезда и код интерактивной карты — «Настройки сайта → Контакты». Пустой пункт не показывается.', 'trust' ),
		),
		Blocks::text_field( 'transport_label', __( 'Подпись «Транспорт»', 'trust' ), __( 'Метро', 'trust' ), 30 ),
		Blocks::text_field( 'parking_label', __( 'Подпись «На машине»', 'trust' ), __( 'На машине', 'trust' ), 30 ),
		Blocks::text_field( 'hours_label', __( 'Подпись «Часы работы»', 'trust' ), __( 'Часы работы', 'trust' ), 30 ),
		Blocks::text_field( 'note_label', __( 'Подпись примечания', 'trust' ), __( 'Пропуск', 'trust' ), 30 ),
		Blocks::text_field( 'yandex_label', __( 'Кнопка Яндекс Карт', 'trust' ), __( 'Открыть в Яндекс Картах', 'trust' ), 40 ),
		Blocks::text_field( 'gis_label', __( 'Кнопка 2ГИС', 'trust' ), __( 'Открыть в 2ГИС', 'trust' ), 40 ),
		Blocks::text_field( 'marker', __( 'Подпись метки на схеме', 'trust' ), __( '{brand} · офис', 'trust' ), 40, __( 'Видна на схеме-заглушке (когда схема проезда не загружена). {brand} — бренд из настроек.', 'trust' ) ),
		Blocks::text_field( 'show_map', __( 'Кнопка «Показать карту»', 'trust' ), __( 'Показать карту', 'trust' ), 30, __( 'Видна, если задан код интерактивной карты. Карта загружается только по нажатию.', 'trust' ) ),
	)
);
