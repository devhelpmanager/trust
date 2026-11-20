<?php
/**
 * Поля блока «Карусель кейсов» (ТЗ, п. 8.3, № 14; макеты holst-blokov/CasesCarousel,
 * seo-bloki/14-karusel-keysov.html).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'results',
			'eyebrow' => __( 'Кейсы', 'trust' ),
			'title'   => __( 'Результаты наших клиентов', 'trust' ),
			'lead'    => __( 'Скриншоты из систем аналитики: позиции, трафик и заявки до и после работы.', 'trust' ),
		)
	),
	array(
		array(
			'name'         => 'slides',
			'label'        => __( 'Слайды', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 1,
			'max'          => 8,
			'button_label' => __( 'Добавить слайд', 'trust' ),
			'collapsed'    => 'field_trust_block_cases-carousel_slides_title',
			'instructions' => __( 'До 8 слайдов. Слайд без картинки или заголовка не показывается. Один слайд — без стрелок и счётчика.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'          => 'image',
					'label'         => __( 'Скриншот результата', 'trust' ),
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'medium',
					'library'       => 'all',
					'mime_types'    => 'jpg, jpeg, png, webp',
					'required'      => 1,
					'instructions'  => __( 'График или таблица из аналитики: 16:10, не меньше 1280×800 px.', 'trust' ),
				),
				array(
					'name'         => 'alt',
					'label'        => __( 'Alt', 'trust' ),
					'type'         => 'text',
					'maxlength'    => 200,
					'instructions' => __( 'Что показывает скриншот, 5–15 слов: «График визитов из поиска: рост за год». Пусто — описание из медиатеки.', 'trust' ),
				),
				array(
					'name'          => 'case',
					'label'         => __( 'Кейс', 'trust' ),
					'type'          => 'post_object',
					'post_type'     => array( 'case' ),
					'post_status'   => array( 'publish' ),
					'return_format' => 'id',
					'allow_null'    => 1,
					'ui'            => 1,
					'instructions'  => __( 'Необязательно. Заголовок слайда станет ссылкой на кейс.', 'trust' ),
				),
				array(
					'name'         => 'niche',
					'label'        => __( 'Чем занимается клиент', 'trust' ),
					'type'         => 'text',
					'maxlength'    => 50,
					'placeholder'  => 'Интернет-магазин мебели',
					'instructions' => __( 'Строка над заголовком. Пусто — из выбранного кейса.', 'trust' ),
				),
				array(
					'name'      => 'title',
					'label'     => __( 'Заголовок', 'trust' ),
					'type'      => 'text',
					'maxlength' => 90,
					'required'  => 1,
				),
				array(
					'name'      => 'text',
					'label'     => __( 'Описание', 'trust' ),
					'type'      => 'textarea',
					'rows'      => 2,
					'new_lines' => '',
					'maxlength' => 240,
				),
			),
		),
	)
);
