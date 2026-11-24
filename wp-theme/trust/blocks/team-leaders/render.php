<?php
/**
 * Блок «Руководство» (макет o-nas/komanda.html, секция «Кто отвечает за результат»).
 *
 * Белая секция, шапка и карточки в 3 колонки: фото 4:5 (без фото — силуэт), имя (H3),
 * должность, «Коротко для карточки», ссылки: «Статьи в блоге» (у авторов статей — страница
 * сотрудника), «Ответы на вопросы» (если отвечает на вопросы SEO — страница вопросов), email.
 * До 720 px — одна колонка, фото слева; до 440 px — фото сверху 4:3.
 *
 * Сотрудники с флажком «Руководство» (по полю «Порядок») или выбранные вручную.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;
use Trust\Page_Types;
use Trust\Team;

defined( 'ABSPATH' ) || exit;

if ( 'manual' === get_field( 'source' ) ) {
	$trust_ids     = array_map( 'intval', (array) get_field( 'members' ) );
	$trust_members = array_values(
		array_filter(
			array_map( 'get_post', $trust_ids ),
			static fn( $member ) => $member instanceof WP_Post && 'team' === $member->post_type && 'publish' === $member->post_status
		)
	);
} else {
	$trust_members = get_posts(
		array(
			'post_type'      => 'team',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'no_found_rows'  => true,
			'meta_key'       => 'trust_leader', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- несколько руководителей по индексу meta_key.
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- см. выше.
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		)
	);
}

$trust_email    = false !== get_field( 'email' );
$trust_blog     = Blocks::text( 'blog_label', __( 'Статьи в блоге', 'trust' ) );
$trust_answers  = Blocks::text( 'answers_label', __( 'Ответы на вопросы', 'trust' ) );
$trust_q_url    = Page_Types::url( 'questions' );
$trust_title_id = Blocks::uid( $block, 'title' );

if ( ! $trust_members && ! $is_preview ) {
	return;
}
?>
<section class="b-leaders trust-bg-white"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-leaders__inner">
		<?php Blocks::section_head( 'b-leaders__head', $trust_title_id ); ?>
		<?php if ( ! $trust_members ) : ?>
			<?php echo Blocks::editor_warning( __( 'Нет руководителей: отметьте флажок «Руководство» у сотрудников или выберите их вручную.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- текст экранирован в editor_warning(). ?>
		<?php else : ?>
			<ul class="b-leaders__list">
				<?php foreach ( $trust_members as $trust_member ) : ?>
					<?php
					$trust_id      = $trust_member->ID;
					$trust_role    = trim( (string) get_post_meta( $trust_id, 'trust_position', true ) );
					$trust_summary = trim( (string) get_post_meta( $trust_id, 'trust_summary', true ) );
					$trust_mail    = $trust_email ? sanitize_email( (string) get_post_meta( $trust_id, 'trust_email', true ) ) : '';
					$trust_name_id = Blocks::uid( $block, 'leader-' . $trust_id );
					$trust_photo   = Blocks::image(
						(int) get_post_thumbnail_id( $trust_member ),
						'large',
						array(
							'class'   => 'b-leaders__photo',
							'loading' => 'lazy',
							'sizes'   => '(max-width: 440px) 92vw, (max-width: 720px) 150px, 380px',
						),
						Team::photo_alt( $trust_id )
					);
					$trust_links   = array();
					if ( Team::is_author( $trust_id ) ) {
						$trust_links[] = array( (string) get_permalink( $trust_member ), $trust_blog, 'more' );
					}
					if ( '' !== $trust_q_url && Team::answers_count( $trust_id ) > 0 ) {
						$trust_links[] = array( $trust_q_url, $trust_answers, 'plain' );
					}
					if ( '' !== $trust_mail ) {
						$trust_links[] = array( 'mailto:' . $trust_mail, $trust_mail, 'plain' );
					}
					?>
					<li class="b-leaders__card">
						<?php if ( '' !== $trust_photo ) : ?>
							<?php echo $trust_photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
						<?php else : ?>
							<span class="b-leaders__photo b-leaders__photo--empty" aria-hidden="true">
								<?php
								Icons::render(
									'user',
									array(
										'size'         => 56,
										'stroke_width' => 1.4,
									)
								);
								?>
							</span>
						<?php endif; ?>
						<div class="b-leaders__body">
							<h3 class="b-leaders__name" id="<?php echo esc_attr( $trust_name_id ); ?>"><?php echo esc_html( get_the_title( $trust_member ) ); ?></h3>
							<?php if ( '' !== $trust_role ) : ?>
								<span class="b-leaders__role"><?php echo esc_html( $trust_role ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $trust_summary ) : ?>
								<p class="b-leaders__text"><?php echo esc_html( $trust_summary ); ?></p>
							<?php endif; ?>
							<?php if ( $trust_links ) : ?>
								<div class="b-leaders__links">
									<?php foreach ( $trust_links as list( $trust_href, $trust_label, $trust_kind ) ) : ?>
										<a class="b-leaders__link b-leaders__link--<?php echo esc_attr( $trust_kind ); ?>" href="<?php echo esc_url( $trust_href ); ?>"<?php echo 'more' === $trust_kind ? ' aria-describedby="' . esc_attr( $trust_name_id ) . '"' : ''; ?>>
											<?php echo esc_html( $trust_label ); ?>
											<?php
											if ( 'more' === $trust_kind ) {
												Icons::render(
													'arrow-right',
													array(
														'size'         => 16,
														'stroke_width' => 2.2,
													)
												);
											}
											?>
										</a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
