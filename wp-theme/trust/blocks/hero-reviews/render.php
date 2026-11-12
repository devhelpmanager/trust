<?php
/**
 * Блок «Первый экран отзывов» (макет o-nas/otzyvy.html).
 *
 * Крошки над тёмной карточкой; в карточке плашка, H1 (поле «H1» в SEO страницы или заголовок),
 * текст и рейтинги на площадках: название, оценка со звёздами, число отзывов, ссылка.
 * Микроразметки рейтинга нет — оценки ставят площадки, а не сайт (ТЗ).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Breadcrumbs;
use Trust\Icons;
use Trust\Seo;

defined( 'ABSPATH' ) || exit;

$trust_h1        = Seo::h1( (int) $post_id );
$trust_badge     = trim( (string) get_field( 'badge' ) );
$trust_lead      = trim( (string) get_field( 'lead' ) );
$trust_title_id  = Blocks::uid( $block, 'title' );
$trust_platforms = array_values(
	array_filter(
		(array) get_field( 'platforms' ),
		static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['name'] ?? '' ) ) && '' !== trim( (string) ( $row['rating'] ?? '' ) )
	)
);

/**
 * Сколько звёзд закрасить: оценка из 5, округлённая; не число (заглушка «[X,X]») — все пять.
 *
 * @param string $rating Оценка.
 */
$trust_stars = static function ( string $rating ): int {
	$value = str_replace( ',', '.', trim( $rating ) );
	return is_numeric( $value ) ? max( 0, min( 5, (int) round( (float) $value ) ) ) : 5;
};
?>
<div class="b-hero-reviews">
	<div class="hero-crumbs">
		<?php
		if ( $is_preview ) {
			Breadcrumbs::render_preview( (int) $post_id );
		} else {
			Breadcrumbs::render();
		}
		?>
	</div>
	<section class="hero-sec" aria-labelledby="<?php echo esc_attr( $trust_title_id ); ?>">
		<div class="hero-box">
			<span class="hero-circle hero-circle--top" aria-hidden="true"></span>
			<span class="hero-circle hero-circle--bottom" aria-hidden="true"></span>
			<div class="hero-in b-hero-reviews__in">
				<div class="b-hero-reviews__head">
					<?php if ( '' !== $trust_badge ) : ?>
						<span class="hero-badge"><?php echo esc_html( $trust_badge ); ?></span>
					<?php endif; ?>
					<h1 class="hero-title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( '' !== $trust_h1 ? $trust_h1 : __( 'Заголовок страницы', 'trust' ) ); ?></h1>
					<?php if ( '' !== $trust_lead ) : ?>
						<p class="hero-lead b-hero-reviews__lead"><?php echo esc_html( $trust_lead ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( $trust_platforms ) : ?>
					<ul class="b-hero-reviews__list" aria-label="<?php esc_attr_e( 'Рейтинг на площадках', 'trust' ); ?>">
						<?php foreach ( $trust_platforms as $trust_platform ) : ?>
							<?php
							$trust_url    = trim( (string) ( $trust_platform['url'] ?? '' ) );
							$trust_tag    = '' !== $trust_url ? 'a' : 'div';
							$trust_filled = $trust_stars( (string) $trust_platform['rating'] );
							?>
							<li>
								<<?php echo esc_html( $trust_tag ); ?> class="b-hero-reviews__card"<?php echo '' !== $trust_url ? ' href="' . esc_url( $trust_url ) . '" target="_blank" rel="noopener"' : ''; ?>>
									<span class="b-hero-reviews__name"><?php echo esc_html( $trust_platform['name'] ); ?></span>
									<span class="b-hero-reviews__rating">
										<span class="b-hero-reviews__value"><?php echo esc_html( $trust_platform['rating'] ); ?><span class="screen-reader-text"> <?php esc_html_e( 'из 5', 'trust' ); ?></span></span>
										<span class="b-hero-reviews__stars" aria-hidden="true">
											<?php for ( $trust_i = 1; $trust_i <= 5; $trust_i++ ) : ?>
												<?php
												Icons::render(
													'star',
													array(
														'size'  => 16,
														'class' => $trust_i <= $trust_filled ? '' : 'is-empty',
													)
												);
												?>
											<?php endfor; ?>
										</span>
									</span>
									<span class="b-hero-reviews__count">
										<?php echo esc_html( (string) ( $trust_platform['count'] ?? '' ) ); ?>
										<?php if ( '' !== $trust_url ) : ?>
											<?php Icons::render( 'arrow-up-right', array( 'size' => 16 ) ); ?>
										<?php endif; ?>
									</span>
								</<?php echo esc_html( $trust_tag ); ?>>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	</section>
</div>
