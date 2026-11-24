<?php
/**
 * Блок «Команда по направлениям» (макет o-nas/komanda.html, секция «Специалисты»).
 *
 * Лавандовая секция: шапка, кнопки отделов с числом людей («Все» — первая), строка «Показаны
 * все специалисты: N» / «Показано: N из M» и сетка карточек 4 / 3 / 2 колонки (на телефоне —
 * строки: фото слева). Карточка: фото 1:1 (без фото — силуэт), отдел, имя (H3), должность,
 * «Опыт: N лет», у авторов статей — «Статьи автора» (страница сотрудника).
 *
 * Сотрудники — раздел «Сотрудники» по полю «Порядок»; руководство (флажок «Руководство») — по
 * выбору. Фильтр — без перезагрузки (assets/js/filter.js); без JavaScript видны все.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;
use Trust\Team;

defined( 'ABSPATH' ) || exit;

$trust_args = array(
	'post_type'      => 'team',
	'post_status'    => 'publish',
	'posts_per_page' => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- весь штат на одной странице (фильтр без перезагрузки).
	'no_found_rows'  => true,
	'orderby'        => array(
		'menu_order' => 'ASC',
		'title'      => 'ASC',
	),
);
if ( ! get_field( 'leaders' ) ) {
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- список сотрудников небольшой.
	$trust_args['meta_query'] = array(
		'relation' => 'OR',
		array(
			'key'     => 'trust_leader',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => 'trust_leader',
			'value'   => '1',
			'compare' => '!=',
		),
	);
}
$trust_members = get_posts( $trust_args );

// Отделы — в порядке первого появления у сотрудников, с числом людей.
$trust_depts = array();
$trust_cards = array();
foreach ( $trust_members as $trust_member ) {
	$trust_terms = get_the_terms( $trust_member, 'department' );
	$trust_terms = is_array( $trust_terms ) ? $trust_terms : array();
	foreach ( $trust_terms as $trust_term ) {
		$trust_depts[ $trust_term->slug ] = array(
			'name'  => $trust_term->name,
			'count' => ( $trust_depts[ $trust_term->slug ]['count'] ?? 0 ) + 1,
		);
	}
	$trust_cards[] = array( $trust_member, $trust_terms );
}

$trust_total    = count( $trust_cards );
$trust_filter   = false !== get_field( 'filter' ) && count( $trust_depts ) > 1;
$trust_blog     = Blocks::text( 'blog_label', __( 'Статьи автора', 'trust' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
/* translators: %total%: число сотрудников (подставляет скрипт фильтра). */
$trust_all_text = __( 'Показаны все специалисты: %total%', 'trust' );
/* translators: %shown%, %total%: сколько сотрудников показано и сколько всего. */
$trust_some_text = __( 'Показано: %shown% из %total%', 'trust' );

if ( ! $trust_cards && ! $is_preview ) {
	return;
}
?>
<section class="b-team trust-bg-lavender"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-team__inner"<?php echo $trust_filter ? ' data-trust-filter' : ''; ?>>
		<?php Blocks::section_head( 'b-team__head', $trust_title_id ); ?>
		<?php if ( ! $trust_cards ) : ?>
			<?php echo Blocks::editor_warning( __( 'Нет опубликованных сотрудников: добавьте их в разделе «Сотрудники».', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- текст экранирован в editor_warning(). ?>
		<?php endif; ?>
		<?php if ( $trust_filter ) : ?>
			<div class="filter-chips" role="group" aria-label="<?php esc_attr_e( 'Фильтр по отделам', 'trust' ); ?>">
				<button type="button" class="filter-chip" data-filter-value="" aria-pressed="true"><?php echo esc_html( Blocks::text( 'all_label', __( 'Все', 'trust' ) ) ); ?><span class="filter-chip__count"><?php echo esc_html( (string) $trust_total ); ?></span></button>
				<?php foreach ( $trust_depts as $trust_slug => $trust_dept ) : ?>
					<button type="button" class="filter-chip" data-filter-value="<?php echo esc_attr( $trust_slug ); ?>" aria-pressed="false"><?php echo esc_html( $trust_dept['name'] ); ?><span class="filter-chip__count"><?php echo esc_html( (string) $trust_dept['count'] ); ?></span></button>
				<?php endforeach; ?>
			</div>
			<p class="filter-status" aria-live="polite" data-filter-status data-all="<?php echo esc_attr( $trust_all_text ); ?>" data-some="<?php echo esc_attr( $trust_some_text ); ?>"><?php echo esc_html( str_replace( '%total%', (string) $trust_total, $trust_all_text ) ); ?></p>
		<?php endif; ?>
		<?php if ( $trust_cards ) : ?>
			<ul class="b-team__list">
				<?php foreach ( $trust_cards as list( $trust_member, $trust_terms ) ) : ?>
					<?php
					$trust_id      = $trust_member->ID;
					$trust_name    = get_the_title( $trust_member );
					$trust_role    = trim( (string) get_post_meta( $trust_id, 'trust_position', true ) );
					$trust_exp     = Team::experience_short( $trust_id );
					$trust_photo   = Blocks::image(
						(int) get_post_thumbnail_id( $trust_member ),
						'medium_large',
						array(
							'class'   => 'b-team__photo',
							'loading' => 'lazy',
							'sizes'   => '(max-width: 560px) 84px, (max-width: 767px) 45vw, (max-width: 1023px) 30vw, 270px',
						),
						Team::photo_alt( $trust_id )
					);
					$trust_name_id = Blocks::uid( $block, 'member-' . $trust_id );
					?>
					<li class="b-team__card" data-filter-tags="<?php echo esc_attr( implode( ' ', wp_list_pluck( $trust_terms, 'slug' ) ) ); ?>">
						<?php if ( '' !== $trust_photo ) : ?>
							<?php echo $trust_photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
						<?php else : ?>
							<span class="b-team__photo b-team__photo--empty" aria-hidden="true">
								<?php
								Icons::render(
									'user',
									array(
										'size'         => 40,
										'stroke_width' => 1.5,
									)
								);
								?>
							</span>
						<?php endif; ?>
						<div class="b-team__body">
							<?php if ( $trust_terms ) : ?>
								<span class="b-team__dept"><?php echo esc_html( $trust_terms[0]->name ); ?></span>
							<?php endif; ?>
							<h3 class="b-team__name" id="<?php echo esc_attr( $trust_name_id ); ?>"><?php echo esc_html( $trust_name ); ?></h3>
							<?php if ( '' !== $trust_role ) : ?>
								<span class="b-team__role"><?php echo esc_html( $trust_role ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $trust_exp ) : ?>
								<span class="b-team__exp"><?php echo esc_html( $trust_exp ); ?></span>
							<?php endif; ?>
							<?php if ( Team::is_author( $trust_id ) ) : ?>
								<a class="b-team__blog" href="<?php echo esc_url( get_permalink( $trust_member ) ); ?>" aria-describedby="<?php echo esc_attr( $trust_name_id ); ?>">
									<?php echo esc_html( $trust_blog ); ?>
									<?php
									Icons::render(
										'arrow-right',
										array(
											'size'         => 15,
											'stroke_width' => 2.2,
										)
									);
									?>
								</a>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
