<?php
/**
 * Настройки сайта → Тексты шаблонов (ТЗ, раздел 5.4).
 *
 * Заголовки и подписи, которые выводят шаблоны страниц (не блоки). Текст по умолчанию — серой
 * подсказкой в пустом поле: пустое поле всегда означает «текст из макета» (Trust\Template_Texts).
 *
 * @package Trust
 */

use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

$trust_defaults = Template_Texts::defaults();

/**
 * Поле текста шаблона.
 *
 * @param string $key   Ключ (Template_Texts::defaults()).
 * @param string $label Подпись.
 * @param string $help  Подсказка.
 */
$trust_text = static fn( string $key, string $label, string $help = '' ): array => array(
	'name'         => 'tpl_' . $key,
	'label'        => $label,
	'type'         => 'text',
	'placeholder'  => $trust_defaults[ $key ],
	'maxlength'    => 60,
	'instructions' => $help,
	'wrapper'      => array( 'width' => 33 ),
);

return array(
	'label'  => __( 'Тексты шаблонов', 'trust' ),
	'fields' => array(
		array(
			'name'    => 'tpl_heading',
			'label'   => __( 'Тексты шаблонов', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Заголовки и подписи, которые страницы выводят сами (их нет в блоках). Пустое поле — текст из макета (серая подсказка).', 'trust' ),
		),
		array(
			'name'    => 'tpl_blog_heading',
			'label'   => __( 'Блог', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Список статей (страница блога).', 'trust' ),
		),
		$trust_text( 'blog_new', __( 'Плашка главной статьи', 'trust' ) ),
		$trust_text( 'blog_read', __( 'Кнопка главной статьи', 'trust' ) ),
		$trust_text( 'blog_all', __( 'Заголовок списка статей', 'trust' ) ),
	),
);
