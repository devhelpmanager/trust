<?php
/**
 * Поля статьи (ТЗ, раздел 5.3).
 *
 * Обложка — «Изображение записи» (alt берётся из медиатеки), анонс — «Отрывок»,
 * автор — пользователь WordPress (его связь с сотрудником задаётся у сотрудника).
 * Просмотры и лайки — служебные метаполя trust_views и trust_likes (Trust\Blog).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	'title'    => __( 'Статья', 'trust' ),
	'location' => 'post_type',
	'position' => 'side',
	'fields'   => array(
		array(
			'name'         => Trust\Blog::FEATURED,
			'label'        => __( 'Главная статья', 'trust' ),
			'type'         => 'true_false',
			'ui'           => 1,
			'message'      => __( 'Крупная карточка «Новая статья»', 'trust' ),
			'instructions' => __( 'Вверху блога и рубрики. Если отмечено несколько — показывается самая новая из отмеченных, если ни одной — последняя статья.', 'trust' ),
			// Решение редакции: авторам флажок не показывается.
			'trust_cap'    => 'edit_others_posts',
		),
	),
);
