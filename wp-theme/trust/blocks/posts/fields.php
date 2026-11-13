<?php
/**
 * Поля блока «Статьи из блога» (ТЗ, п. 8.3, № 16; макеты seo-bloki/16-blog.html и «Наш блог»
 * в seo-stranitsa/index.html). Виды «Свежие статьи» и «Читайте также» — на шагах блога (5.x).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_source_is = static fn( string $value ): array => array(
	array(
		array(
			'field'    => 'field_trust_block_posts_source',
			'operator' => '==',
			'value'    => $value,
		),
	),
);

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'blog',
			'eyebrow' => __( 'Блог', 'trust' ),
			'title'   => __( 'Наш блог', 'trust' ),
			'lead'    => '',
		)
	),
	array(
		array(
			'name'          => 'view',
			'label'         => __( 'Вид', 'trust' ),
			'type'          => 'select',
			'choices'       => array(
				'blog'  => __( 'Наш блог — карточки без обложек (до 5 в ряд)', 'trust' ),
				'cards' => __( '3 карточки с обложками и анонсом', 'trust' ),
			),
			'default_value' => 'blog',
		),
		array(
			'name'          => 'source',
			'label'         => __( 'Какие статьи', 'trust' ),
			'type'          => 'select',
			'choices'       => array(
				'latest'   => __( 'Последние', 'trust' ),
				'category' => __( 'Последние из рубрики', 'trust' ),
				'popular'  => __( 'Популярные (по просмотрам)', 'trust' ),
				'manual'   => __( 'Выбрать вручную', 'trust' ),
			),
			'default_value' => 'latest',
		),
		array(
			'name'              => 'category',
			'label'             => __( 'Рубрика', 'trust' ),
			'type'              => 'taxonomy',
			'taxonomy'          => 'category',
			'field_type'        => 'select',
			'return_format'     => 'id',
			'add_term'          => 0,
			'save_terms'        => 0,
			'load_terms'        => 0,
			'allow_null'        => 1,
			'conditional_logic' => $trust_source_is( 'category' ),
		),
		array(
			'name'              => 'posts',
			'label'             => __( 'Статьи', 'trust' ),
			'type'              => 'relationship',
			'post_type'         => array( 'post' ),
			'post_status'       => array( 'publish' ),
			'filters'           => array( 'search', 'taxonomy' ),
			'return_format'     => 'id',
			'max'               => 8,
			'instructions'      => __( 'Порядок — перетаскиванием.', 'trust' ),
			'conditional_logic' => $trust_source_is( 'manual' ),
		),
		array(
			'name'         => 'count',
			'label'        => __( 'Сколько статей', 'trust' ),
			'type'         => 'number',
			'min'          => 1,
			'max'          => 8,
			'placeholder'  => '5 / 3',
			'instructions' => __( 'Пусто — 5 для «Нашего блога», 3 для карточек с обложками.', 'trust' ),
		),
		array(
			'name'          => 'more_label',
			'label'         => __( 'Кнопка «Все статьи»', 'trust' ),
			'type'          => 'text',
			'default_value' => __( 'Все статьи', 'trust' ),
			'maxlength'     => 30,
			'instructions'  => __( 'Ведёт на список статей блога. Пусто — без кнопки.', 'trust' ),
		),
	)
);
