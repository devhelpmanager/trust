<?php
/**
 * Поля блока «Видео» (ТЗ, п. 8.3; «Видеоразбор» в макете статьи seo-blog/statya.html).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_source_is = static fn( string $source ): array => array(
	array(
		array(
			'field'    => 'field_trust_block_video_source',
			'operator' => '==',
			'value'    => $source,
		),
	),
);

return array(
	array(
		'name'          => 'source',
		'label'         => __( 'Видео', 'trust' ),
		'type'          => 'button_group',
		'choices'       => array(
			'link' => __( 'Ссылка', 'trust' ),
			'file' => __( 'Файл MP4', 'trust' ),
		),
		'default_value' => 'link',
	),
	array(
		'name'              => 'url',
		'label'             => __( 'Ссылка на видео', 'trust' ),
		'type'              => 'url',
		'required'          => 1,
		'placeholder'       => 'https://www.youtube.com/watch?v=…',
		'instructions'      => __( 'YouTube, Rutube или VK Видео — плеер откроется прямо на странице. Другая ссылка — кнопка откроет её в новой вкладке.', 'trust' ),
		'conditional_logic' => $trust_source_is( 'link' ),
	),
	array(
		'name'              => 'file',
		'label'             => __( 'Файл видео', 'trust' ),
		'type'              => 'file',
		'required'          => 1,
		'return_format'     => 'id',
		'library'           => 'all',
		'mime_types'        => 'mp4,webm',
		'instructions'      => __( 'MP4 (H.264) или WebM. Большие файлы лучше загрузить на YouTube или Rutube и вставить ссылку.', 'trust' ),
		'conditional_logic' => $trust_source_is( 'file' ),
	),
	array(
		'name'          => 'poster',
		'label'         => __( 'Обложка', 'trust' ),
		'type'          => 'image',
		'required'      => 1,
		'return_format' => 'id',
		'preview_size'  => 'medium',
		'library'       => 'all',
		'mime_types'    => 'jpg,jpeg,png,webp',
		'instructions'  => __( 'Обязательно. 16:9, от 1280×720 px. Кадр из видео или заставка — по ней видео узнают в поиске.', 'trust' ),
	),
	array(
		'name'         => 'title',
		'label'        => __( 'Название видео', 'trust' ),
		'type'         => 'text',
		'required'     => 1,
		'maxlength'    => 120,
		'instructions' => __( 'Для кнопки («Смотреть видео: …») и поиска — на странице не выводится.', 'trust' ),
	),
	array(
		'name'         => 'caption',
		'label'        => __( 'Подпись под видео', 'trust' ),
		'type'         => 'textarea',
		'rows'         => 2,
		'new_lines'    => '',
		'maxlength'    => 300,
		'instructions' => __( 'Необязательно. Что показано в видео — то же описание получит поиск.', 'trust' ),
	),
	array(
		'name'         => 'duration',
		'label'        => __( 'Длительность', 'trust' ),
		'type'         => 'text',
		'maxlength'    => 8,
		'placeholder'  => '12:40',
		'instructions' => __( 'Минуты:секунды или часы:минуты:секунды. Пусто — плашки нет.', 'trust' ),
		'wrapper'      => array( 'width' => 33 ),
	),
	array(
		'name'           => 'date',
		'label'          => __( 'Дата публикации видео', 'trust' ),
		'type'           => 'date_picker',
		'display_format' => 'd.m.Y',
		'return_format'  => 'Y-m-d',
		'first_day'      => 1,
		'instructions'   => __( 'Для поиска. Пусто — дата страницы.', 'trust' ),
		'wrapper'        => array( 'width' => 33 ),
	),
	array(
		'name'          => 'width',
		'label'         => __( 'Ширина на странице', 'trust' ),
		'type'          => 'button_group',
		'choices'       => array(
			'text' => __( 'По ширине текста', 'trust' ),
			'wide' => __( 'Во всю ширину', 'trust' ),
		),
		'default_value' => 'text',
		'instructions'  => __( 'На страницах. В статье и ответе видео — по ширине текста.', 'trust' ),
		'wrapper'       => array( 'width' => 34 ),
	),
);
