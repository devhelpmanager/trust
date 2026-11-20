<?php
/**
 * Поля блока «Отзывы клиентов» (ТЗ, п. 8.3, № 15; макеты holst-blokov/Reviews,
 * seo-bloki/15-otzyvy.html — «3 карточки»; «Что говорят клиенты» в portfolio-blog-404/portfolio.html —
 * «2 карточки»). Отзывы — записи «Отзывы» вида «Текстовый» (у других видов карточек нет).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_source_is = static fn( string $operator, string $value ): array => array(
	array(
		array(
			'field'    => 'field_trust_block_reviews_source',
			'operator' => $operator,
			'value'    => $value,
		),
	),
);

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'reviews',
			'eyebrow' => __( 'Отзывы', 'trust' ),
			'title'   => __( 'Что говорят клиенты', 'trust' ),
			'lead'    => '',
		)
	),
	array(
		array(
			'name'          => 'view',
			'label'         => __( 'Вид', 'trust' ),
			'type'          => 'select',
			'choices'       => array(
				'three' => __( '3 карточки — фото автора и ссылка «Оригинал»', 'trust' ),
				'two'   => __( '2 карточки — коротко, как в портфолио', 'trust' ),
			),
			'default_value' => 'three',
			'wrapper'       => array( 'width' => 50 ),
		),
		array(
			'name'          => 'background',
			'label'         => __( 'Фон', 'trust' ),
			'type'          => 'button_group',
			'choices'       => array(
				'white'    => __( 'Белый', 'trust' ),
				'lavender' => __( 'Лавандовый', 'trust' ),
			),
			'default_value' => 'white',
			'return_format' => 'value',
			'instructions'  => __( 'На белом — лавандовые карточки, на лавандовом — белые с рамкой (как в портфолио).', 'trust' ),
			'wrapper'       => array( 'width' => 50 ),
		),
		array(
			'name'          => 'source',
			'label'         => __( 'Какие отзывы', 'trust' ),
			'type'          => 'select',
			'choices'       => array(
				'latest'  => __( 'Последние', 'trust' ),
				'service' => __( 'Последние по услуге', 'trust' ),
				'manual'  => __( 'Выбрать вручную', 'trust' ),
			),
			'default_value' => 'latest',
			'instructions'  => __( 'Автоматически — текстовые отзывы. Вручную можно выбрать и благодарственное письмо, если у него есть текст. Видеоотзывы показываются в своём блоке.', 'trust' ),
		),
		array(
			'name'              => 'service',
			'label'             => __( 'Услуга', 'trust' ),
			'type'              => 'taxonomy',
			'taxonomy'          => 'service_type',
			'field_type'        => 'select',
			'return_format'     => 'id',
			'add_term'          => 0,
			'save_terms'        => 0,
			'load_terms'        => 0,
			'allow_null'        => 1,
			'conditional_logic' => $trust_source_is( '==', 'service' ),
		),
		array(
			'name'              => 'reviews',
			'label'             => __( 'Отзывы', 'trust' ),
			'type'              => 'relationship',
			'post_type'         => array( 'review' ),
			'post_status'       => array( 'publish' ),
			'filters'           => array( 'search', 'taxonomy' ),
			'return_format'     => 'id',
			'max'               => 9,
			'instructions'      => __( 'Порядок — перетаскиванием.', 'trust' ),
			'conditional_logic' => $trust_source_is( '==', 'manual' ),
		),
		array(
			'name'              => 'count',
			'label'             => __( 'Сколько отзывов', 'trust' ),
			'type'              => 'number',
			'min'               => 1,
			'max'               => 9,
			'placeholder'       => '3 / 2',
			'instructions'      => __( 'Пусто — 3 для «3 карточек», 2 для «2 карточек».', 'trust' ),
			'conditional_logic' => $trust_source_is( '!=', 'manual' ),
		),
		array(
			'name'          => 'more_label',
			'label'         => __( 'Кнопка «Все отзывы»', 'trust' ),
			'type'          => 'text',
			'default_value' => __( 'Все отзывы', 'trust' ),
			'maxlength'     => 30,
			'instructions'  => __( 'Ведёт на страницу типа «Отзывы». Пусто или такой страницы нет — без кнопки.', 'trust' ),
		),
		Trust\Blocks::text_field( 'original_label', __( 'Ссылка на оригинал отзыва', 'trust' ), __( 'Оригинал', 'trust' ), 20, __( 'В виде «3 карточки», если у отзыва есть ссылка на оригинал или скан письма.', 'trust' ) ),
	)
);
