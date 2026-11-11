<?php
/**
 * Поля блока «Вопрос-ответ» (макет iskhodniki/holst-blokov/Faq.dc.html, seo-bloki/10-vopros-otvet.html).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'faq',
			'eyebrow' => __( 'Вопрос — ответ', 'trust' ),
			'title'   => __( 'Частые вопросы', 'trust' ),
			'lead'    => __( 'Собрали ответы на вопросы, которые клиенты задают чаще всего.', 'trust' ),
		)
	),
	array(
		array(
			'name'          => 'more_link',
			'label'         => __( 'Ссылка под заголовком', 'trust' ),
			'type'          => 'link',
			'return_format' => 'array',
			'instructions'  => __( 'Необязательно. Например, «Не нашли ответ? Задайте вопрос» → страница вопросов SEO-специалисту.', 'trust' ),
		),
		array(
			'name'         => 'questions',
			'label'        => __( 'Вопросы', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 1,
			'button_label' => __( 'Добавить вопрос', 'trust' ),
			'collapsed'    => 'field_trust_block_faq_questions_question',
			'instructions' => __( 'Номера 01, 02… ставятся автоматически по порядку. Порядок меняется перетаскиванием.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'         => 'question',
					'label'        => __( 'Вопрос', 'trust' ),
					'type'         => 'text',
					'required'     => 1,
					'maxlength'    => 200,
					'instructions' => __( 'Как спрашивает клиент, до 80 символов.', 'trust' ),
				),
				array(
					'name'         => 'answer',
					'label'        => __( 'Ответ', 'trust' ),
					'type'         => 'wysiwyg',
					'required'     => 1,
					'tabs'         => 'visual',
					'toolbar'      => 'basic',
					'media_upload' => 0,
					'instructions' => __( 'Абзацы, списки, ссылки. Рекомендуем до 500 символов.', 'trust' ),
				),
			),
		),
	)
);
