<?php
/**
 * Поля блока «Хотите в команду?» (ТЗ, п. 8.6; макет holst-o-nas/Team, o-nas/komanda.html).
 * Две кнопки — по макету (в ТЗ одна).
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_buttons                 = Trust\Blocks::buttons_field( 2, true );
$trust_buttons['instructions'] = __( 'Пусто — «Открытые вакансии» и «Отправить резюме» на странице типа «Карьера» (её разделы #vacancies и #resume). Первая кнопка — белая, вторая — контурная.', 'trust' );

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'join',
			'eyebrow' => __( 'Карьера', 'trust' ),
			'title'   => __( 'Хотите в команду?', 'trust' ),
			'lead'    => __( 'Ищем SEO-специалистов, разработчиков и менеджеров. Для начинающих есть стажировка с наставником.', 'trust' ),
		)
	),
	array( $trust_buttons )
);
