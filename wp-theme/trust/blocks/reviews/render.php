<?php
/**
 * Блок «Отзывы клиентов» (ТЗ, п. 8.3, № 15).
 *
 * Вид «3 карточки» (holst-blokov/Reviews, seo-bloki/15-otzyvy.html): знак цитаты, текст отзыва,
 * внизу через черту — фото автора (или силуэт), имя, должность и компания, ссылка «Оригинал»
 * (ссылка на оригинал отзыва или скан благодарственного письма — открывается на весь экран).
 * Вид «2 карточки» («Что говорят клиенты» в portfolio-blog-404/portfolio.html): знак цитаты,
 * текст, имя, «должность, компания · отрасль кейса», к которому привязан отзыв.
 * Фон белый (карточки лавандовые) или лавандовый (карточки белые с рамкой). Вверху справа —
 * кнопка «Все отзывы» на страницу типа «Отзывы». Нет отзывов — блока на сайте нет.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;
use Trust\Page_Types;
use Trust\Reviews;

defined( 'ABSPATH' ) || exit;

$trust_view   = 'two' === get_field( 'view' ) ? 'two' : 'three';
$trust_bg     = 'lavender' === get_field( 'background' ) ? 'lavender' : 'white';
$trust_source = (string) get_field( 'source' );
$trust_count  = (int) get_field( 'count' );
$trust_count  = $trust_count > 0 ? min( 9, $trust_count ) : ( 'two' === $trust_view ? 2 : 3 );
$trust_args   = array(
	'post_type'      => 'review',
	'post_status'    => 'publish',
	'posts_per_page' => $trust_count + 6, // С запасом: отзывы без текста пропускаются.
	'no_found_rows'  => true,
	'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- только текстовые отзывы.
		'relation' => 'OR',
		array(
			'key'   => 'trust_kind',
			'value' => 'text',
		),
		array(
			'key'     => 'trust_kind',
			'compare' => 'NOT EXISTS',
		),
	),
);
if ( 'service' === $trust_source ) {
	$trust_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- отзывы по услуге.
		array(
			'taxonomy' => 'service_type',
			'terms'    => array( (int) get_field( 'service' ) ),
		),
	);
} elseif ( 'manual' === $trust_source ) {
	$trust_ids                    = array_map( 'intval', (array) get_field( 'reviews' ) );
	$trust_args['post__in']       = $trust_ids ? $trust_ids : array( 0 );
	$trust_args['orderby']        = 'post__in';
	$trust_args['posts_per_page'] = count( $trust_ids );
	$trust_count                  = count( $trust_ids );
	unset( $trust_args['meta_query'] );
}

$trust_reviews = array();
foreach ( get_posts( $trust_args ) as $trust_review ) {
	$trust_text = trim( (string) get_post_meta( $trust_review->ID, 'trust_text', true ) );
	if ( '' !== $trust_text && 'video' !== Reviews::kind( $trust_review->ID ) ) {
		$trust_reviews[ $trust_review->ID ] = $trust_text;
	}
	if ( count( $trust_reviews ) >= $trust_count ) {
		break;
	}
}

if ( ! $trust_reviews && ! $is_preview ) {
	return;
}

$trust_title    = (string) get_field( 'title' );
$trust_eyebrow  = (string) get_field( 'eyebrow' );
$trust_lead     = (string) get_field( 'lead' );
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_more     = get_field( 'more_label' );
$trust_more     = null === $trust_more ? __( 'Все отзывы', 'trust' ) : trim( (string) $trust_more );
$trust_more_url = '' !== $trust_more ? Page_Types::url( 'reviews' ) : '';
$trust_original = Blocks::text( 'original_label', __( 'Оригинал', 'trust' ) );
$trust_mark     = '<svg class="b-reviews__mark" viewBox="0 0 44 34" aria-hidden="true" focusable="false"><path d="M0 34V20.4C0 8.9 6.2 2.1 17.4 0l1.9 4.6C13 6.4 10.2 10 9.8 15.3H18V34H0zm25 0V20.4C25 8.9 31.2 2.1 42.4 0l1.6 4.6c-6.3 1.8-9.1 5.4-9.5 10.7H43V34H25z"/></svg>';
?>
<section class="b-reviews b-reviews--<?php echo esc_attr( $trust_view ); ?> b-reviews--<?php echo esc_attr( $trust_bg ); ?> trust-bg-<?php echo esc_attr( $trust_bg ); ?>"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-reviews__inner">
		<div class="b-reviews__top">
			<?php if ( $trust_eyebrow || $trust_title || $trust_lead ) : ?>
				<div class="section-head b-reviews__head">
					<?php if ( $trust_eyebrow ) : ?>
						<span class="eyebrow"><?php echo esc_html( $trust_eyebrow ); ?></span>
					<?php endif; ?>
					<?php if ( $trust_title ) : ?>
						<h2 class="section-title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
					<?php endif; ?>
					<?php if ( '' !== trim( $trust_lead ) ) : ?>
						<p class="section-lead"><?php echo esc_html( $trust_lead ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( '' !== $trust_more_url ) : ?>
				<a class="b-reviews__all" href="<?php echo esc_url( $trust_more_url ); ?>">
					<?php echo esc_html( $trust_more ); ?>
					<?php Icons::render( 'arrow-right', array( 'size' => 16 ) ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $trust_reviews ) : ?>
			<ul class="b-reviews__list">
				<?php foreach ( $trust_reviews as $trust_id => $trust_text ) : ?>
					<?php
					$trust_name = trim( (string) get_post_meta( $trust_id, 'trust_name', true ) );
					$trust_role = implode(
						', ',
						array_filter(
							array(
								trim( (string) get_post_meta( $trust_id, 'trust_position', true ) ),
								trim( (string) get_post_meta( $trust_id, 'trust_company', true ) ),
							),
							'strlen'
						)
					);
					$trust_who  = '' !== $trust_name ? $trust_name : Reviews::author_line( $trust_id, true );
					?>
					<li class="b-reviews__item">
						<figure class="b-reviews__card">
							<?php echo $trust_mark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- постоянная разметка SVG. ?>
							<blockquote class="b-reviews__quote"><?php echo wp_kses_post( wpautop( esc_html( $trust_text ) ) ); ?></blockquote>
							<?php if ( 'two' === $trust_view ) : ?>
								<?php
								$trust_case     = Reviews::case_of( $trust_id );
								$trust_industry = $trust_case ? get_the_terms( $trust_case, 'industry' ) : false;
								$trust_line     = implode(
									' · ',
									array_filter(
										array(
											$trust_role,
											is_array( $trust_industry ) && $trust_industry ? $trust_industry[0]->name : '',
										),
										'strlen'
									)
								);
								?>
								<figcaption class="b-reviews__author">
									<span class="b-reviews__name"><?php echo esc_html( $trust_who ); ?></span>
									<?php if ( '' !== $trust_line ) : ?>
										<span class="b-reviews__role"><?php echo esc_html( $trust_line ); ?></span>
									<?php endif; ?>
								</figcaption>
							<?php else : ?>
								<?php
								$trust_photo  = (int) get_post_meta( $trust_id, 'trust_photo', true );
								$trust_link   = trim( (string) get_post_meta( $trust_id, 'trust_source', true ) );
								$trust_letter = (int) get_post_meta( $trust_id, 'trust_letter', true );
								$trust_scan   = $trust_letter ? wp_get_attachment_image_url( $trust_letter, 'full' ) : '';
								?>
								<figcaption class="b-reviews__author">
									<span class="b-reviews__avatar" aria-hidden="true">
										<?php if ( $trust_photo ) : ?>
											<?php
											echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка WordPress.
												$trust_photo,
												'thumbnail',
												false,
												array(
													'class' => 'b-reviews__photo',
													'alt' => '',
													'loading' => 'lazy',
												)
											);
											?>
										<?php else : ?>
											<svg width="40" height="40" viewBox="0 0 40 40" focusable="false"><circle cx="20" cy="15" r="7.5"/><path d="M5 40c1.5-9 7.5-14 15-14s13.5 5 15 14z"/></svg>
										<?php endif; ?>
									</span>
									<span class="b-reviews__who">
										<span class="b-reviews__name"><?php echo esc_html( $trust_who ); ?></span>
										<?php if ( '' !== $trust_role ) : ?>
											<span class="b-reviews__role"><?php echo esc_html( $trust_role ); ?></span>
										<?php endif; ?>
									</span>
									<?php if ( '' !== $trust_original && ( '' !== $trust_link || $trust_scan ) ) : ?>
										<?php if ( '' !== $trust_link ) : ?>
											<a class="b-reviews__original" href="<?php echo esc_url( $trust_link ); ?>" target="_blank" rel="noopener">
												<?php echo esc_html( $trust_original ); ?>
												<?php /* translators: %s: имя автора отзыва. */ ?>
												<span class="screen-reader-text"><?php echo esc_html( sprintf( __( 'отзыва: %s (откроется в новой вкладке)', 'trust' ), $trust_who ) ); ?></span>
											</a>
										<?php else : ?>
											<a class="b-reviews__original" href="<?php echo esc_url( $trust_scan ); ?>" data-trust-lightbox data-lightbox-caption="<?php /* translators: %s: автор отзыва. */ echo esc_attr( sprintf( __( 'Благодарственное письмо — %s', 'trust' ), Reviews::author_line( $trust_id, true ) ) ); ?>">
												<?php echo esc_html( $trust_original ); ?>
												<?php /* translators: %s: имя автора отзыва. */ ?>
												<span class="screen-reader-text"><?php echo esc_html( sprintf( __( 'отзыва: благодарственное письмо, %s', 'trust' ), $trust_who ) ); ?></span>
											</a>
										<?php endif; ?>
									<?php endif; ?>
								</figcaption>
							<?php endif; ?>
						</figure>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<?php echo Blocks::editor_warning( __( 'Подходящих отзывов нет: опубликуйте текстовые отзывы («Отзывы» в меню админки) или выберите другие условия.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка экранирована в Blocks::editor_warning(). ?>
		<?php endif; ?>
	</div>
</section>
