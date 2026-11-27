<?php
/**
 * Настройки сайта → Блог (ТЗ, раздел 5.4): статей на странице списка, скорость чтения для времени
 * чтения, показывать ли просмотры и лайки.
 *
 * @package Trust
 */

use Trust\Blog;

defined( 'ABSPATH' ) || exit;

return array(
	'label'  => __( 'Блог', 'trust' ),
	'fields' => array(
		array(
			'name'         => 'blog_per_page',
			'label'        => __( 'Статей на странице', 'trust' ),
			'type'         => 'number',
			'min'          => 6,
			'max'          => 60,
			'placeholder'  => (string) Blog::PER_PAGE,
			'instructions' => sprintf(
				/* translators: %d: число статей. */
				__( 'Сколько карточек в «Все статьи» на одной странице блога (главная статья — сверх них). Пусто — %d.', 'trust' ),
				Blog::PER_PAGE
			),
			'wrapper'      => array( 'width' => 33 ),
		),
		array(
			'name'         => 'blog_read_speed',
			'label'        => __( 'Скорость чтения, слов в минуту', 'trust' ),
			'type'         => 'number',
			'min'          => 60,
			'max'          => 600,
			'placeholder'  => (string) Blog::READ_SPEED,
			'instructions' => sprintf(
				/* translators: %d: слов в минуту. */
				__( 'Для времени чтения статьи: число слов текста / скорость. Пусто — %d. Время округляется вверх, минимум 1 минута.', 'trust' ),
				Blog::READ_SPEED
			),
			'wrapper'      => array( 'width' => 33 ),
		),
		array(
			'name'          => 'blog_show_views',
			'label'         => __( 'Показывать просмотры', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'instructions'  => __( 'Число просмотров на странице статьи. Считаются всегда.', 'trust' ),
			'wrapper'       => array( 'width' => 34 ),
		),
		array(
			'name'          => 'blog_show_likes',
			'label'         => __( 'Показывать лайки', 'trust' ),
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'instructions'  => __( 'Число лайков в карточках и на странице статьи, кнопка «Нравится».', 'trust' ),
			'wrapper'       => array( 'width' => 33 ),
		),
	),
);
