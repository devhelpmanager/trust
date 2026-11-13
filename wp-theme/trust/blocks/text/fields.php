<?php
/**
 * Поля блока «Блок с текстом» (макет holst-blokov/TextBlock, seo-bloki/03-tekst.html).
 * Сам текст — вложенные блоки (InnerBlocks), здесь только якорь и фон.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'name'         => 'anchor',
		'label'        => __( 'Якорь', 'trust' ),
		'type'         => 'text',
		'instructions' => __( 'Необязательно. Латиница, цифры и дефис, без #. Ссылка на блок: адрес страницы#якорь.', 'trust' ),
	),
	array(
		'name'    => 'text_note',
		'label'   => __( 'Текст', 'trust' ),
		'type'    => 'message',
		'message' => __( 'Печатайте текст прямо в блоке: абзацы, заголовки H2–H4, списки, цитаты, картинки, таблицы, видео. У цитаты два стиля — «С автором» (имя и должность — в подписи цитаты, имя первой строкой) и «Важно».', 'trust' ),
	),
);
