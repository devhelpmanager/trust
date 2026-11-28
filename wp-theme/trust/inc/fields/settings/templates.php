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
		array(
			'name'    => 'tpl_post_heading',
			'label'   => __( 'Статья', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Страница статьи блога.', 'trust' ),
		),
		$trust_text( 'post_toc', __( 'Заголовок содержания', 'trust' ) ),
		$trust_text( 'post_tags', __( 'Над тегами', 'trust' ) ),
		$trust_text( 'post_useful', __( 'Рядом с «Нравится»', 'trust' ) ),
		$trust_text( 'post_related_label', __( 'Надзаголовок «Читайте также»', 'trust' ) ),
		$trust_text( 'post_related', __( 'Заголовок «Читайте также»', 'trust' ) ),
		$trust_text( 'post_related_more', __( 'Кнопка «Читайте также»', 'trust' ) ),
		$trust_text( 'post_comments', __( 'Заголовок комментариев', 'trust' ) ),
		$trust_text( 'comment_note', __( 'Подпись у кнопки «Отправить»', 'trust' ) ),
		$trust_text( 'comment_sent', __( 'После отправки комментария', 'trust' ) ),
		$trust_text( 'comment_reply_sent', __( 'После отправки ответа', 'trust' ) ),
		array(
			'name'    => 'tpl_rubric_heading',
			'label'   => __( 'Рубрика', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Страница рубрики блога. Тексты блока «Не нашли ответ?» — во вкладке «Блоки по умолчанию».', 'trust' ),
		),
		$trust_text( 'rubric_label', __( 'Плашка над заголовком', 'trust' ) ),
		$trust_text( 'rubric_tags', __( 'Перед тегами', 'trust' ) ),
		$trust_text( 'rubric_list', __( 'Заголовок списка статей', 'trust' ) ),
		$trust_text( 'rubric_new', __( 'Сортировка: новые', 'trust' ) ),
		$trust_text( 'rubric_popular', __( 'Сортировка: популярные', 'trust' ) ),
		$trust_text( 'rubric_about', __( 'Заголовок «О рубрике»', 'trust' ), __( 'После него — название рубрики в кавычках.', 'trust' ) ),
		array(
			'name'    => 'tpl_tag_heading',
			'label'   => __( 'Тег', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Страница тега: статьи и ответы на вопросы с этим тегом.', 'trust' ),
		),
		$trust_text( 'tag_label', __( 'Плашка над заголовком', 'trust' ) ),
		$trust_text( 'tag_all', __( 'Фильтр: все материалы', 'trust' ) ),
		$trust_text( 'tag_posts', __( 'Фильтр: статьи', 'trust' ) ),
		$trust_text( 'tag_answers', __( 'Фильтр: ответы', 'trust' ) ),
		$trust_text( 'tag_post_badge', __( 'Плашка статьи', 'trust' ) ),
		$trust_text( 'tag_answer_badge', __( 'Плашка ответа', 'trust' ) ),
		$trust_text( 'tag_answered_by', __( 'Перед именем специалиста', 'trust' ) ),
		$trust_text( 'tag_rubric', __( 'Сбоку: над рубрикой', 'trust' ) ),
		$trust_text( 'tag_similar', __( 'Сбоку: над похожими тегами', 'trust' ) ),
		$trust_text( 'tag_ask_title', __( 'Сбоку: заголовок «Остался вопрос»', 'trust' ), __( '{tag} — название тега.', 'trust' ) ),
		array_merge(
			$trust_text( 'tag_ask_text', __( 'Сбоку: текст «Остался вопрос»', 'trust' ) ),
			array(
				'maxlength' => 160,
				'wrapper'   => array( 'width' => 66 ),
			)
		),
		$trust_text( 'tag_ask_button', __( 'Сбоку: кнопка', 'trust' ) ),
	),
);
