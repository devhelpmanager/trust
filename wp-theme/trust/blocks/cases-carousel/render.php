<?php
/**
 * Блок «Карусель кейсов» (макет holst-blokov/CasesCarousel, seo-bloki/14-karusel-keysov.html).
 *
 * Фиолетовая секция: слева надзаголовок, H2 и вводный текст, справа счётчик «01 / 04» и стрелки.
 * Ниже лента слайдов шириной 60 % (на телефоне 86 %): скриншот 16:10, чем занимается клиент,
 * заголовок (ссылка на кейс, если он выбран) и описание. У соседних слайдов скриншот бледнее,
 * текст скрыт. Листается стрелками (по кругу) и пальцем (view.js). Без JavaScript — лента
 * прокручивается вбок, видны все тексты.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Cases;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_slides = array();
foreach ( (array) get_field( 'slides' ) as $trust_row ) {
	$trust_image = (int) ( $trust_row['image'] ?? 0 );
	$trust_head  = trim( (string) ( $trust_row['title'] ?? '' ) );
	if ( ! $trust_image || '' === $trust_head ) {
		continue;
	}
	$trust_case  = (int) ( $trust_row['case'] ?? 0 );
	$trust_case  = $trust_case && 'publish' === get_post_status( $trust_case ) && 'case' === get_post_type( $trust_case ) ? $trust_case : 0;
	$trust_niche = trim( (string) ( $trust_row['niche'] ?? '' ) );

	$trust_slides[] = array(
		'image' => $trust_image,
		'alt'   => trim( (string) ( $trust_row['alt'] ?? '' ) ),
		'title' => $trust_head,
		'text'  => trim( (string) ( $trust_row['text'] ?? '' ) ),
		'niche' => '' === $trust_niche && $trust_case ? Cases::niche( $trust_case ) : $trust_niche,
		'url'   => $trust_case ? (string) get_permalink( $trust_case ) : '',
	);
}

if ( ! $trust_slides && ! $is_preview ) {
	return;
}

$trust_count    = count( $trust_slides );
$trust_title    = (string) get_field( 'title' );
$trust_eyebrow  = (string) get_field( 'eyebrow' );
$trust_lead     = (string) get_field( 'lead' );
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_track_id = Blocks::uid( $block, 'track' );
$trust_pad      = static fn( int $n ): string => str_pad( (string) $n, 2, '0', STR_PAD_LEFT );
?>
<section class="b-carousel section--violet trust-bg-violet"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?> aria-roledescription="<?php esc_attr_e( 'карусель', 'trust' ); ?>"<?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ' aria-label="' . esc_attr__( 'Кейсы', 'trust' ) . '"'; ?> data-carousel>
	<div class="b-carousel__inner">
		<div class="b-carousel__top">
			<?php if ( $trust_eyebrow || $trust_title || $trust_lead ) : ?>
				<div class="section-head b-carousel__head">
					<?php if ( $trust_eyebrow ) : ?>
						<span class="eyebrow"><?php echo esc_html( $trust_eyebrow ); ?></span>
					<?php endif; ?>
					<?php if ( $trust_title ) : ?>
						<h2 class="section-title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
					<?php endif; ?>
					<?php if ( '' !== trim( $trust_lead ) ) : ?>
						<p class="section-lead section-lead--lg"><?php echo esc_html( $trust_lead ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( $trust_count > 1 ) : ?>
				<div class="b-carousel__nav">
					<span class="b-carousel__counter" aria-live="polite" aria-atomic="true"><span data-carousel-current><?php echo esc_html( $trust_pad( 1 ) ); ?></span> / <?php echo esc_html( $trust_pad( $trust_count ) ); ?></span>
					<button type="button" class="b-carousel__btn b-carousel__btn--prev" data-carousel-prev aria-controls="<?php echo esc_attr( $trust_track_id ); ?>" aria-label="<?php esc_attr_e( 'Предыдущий кейс', 'trust' ); ?>">
						<?php
						Icons::render(
							'arrow-left',
							array(
								'size'         => 20,
								'stroke_width' => 2,
							)
						);
						?>
					</button>
					<button type="button" class="b-carousel__btn b-carousel__btn--next" data-carousel-next aria-controls="<?php echo esc_attr( $trust_track_id ); ?>" aria-label="<?php esc_attr_e( 'Следующий кейс', 'trust' ); ?>">
						<?php
						Icons::render(
							'arrow-right',
							array(
								'size'         => 20,
								'stroke_width' => 2,
							)
						);
						?>
					</button>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $trust_slides ) : ?>
			<div class="b-carousel__track" id="<?php echo esc_attr( $trust_track_id ); ?>" data-carousel-track>
				<?php foreach ( $trust_slides as $trust_i => $trust_slide ) : ?>
					<div class="b-carousel__slide<?php echo 0 === $trust_i ? ' is-active' : ''; ?>" role="group" aria-roledescription="<?php esc_attr_e( 'слайд', 'trust' ); ?>" aria-label="<?php /* translators: 1: номер слайда, 2: всего слайдов. */ echo esc_attr( sprintf( __( '%1$d из %2$d', 'trust' ), $trust_i + 1, $trust_count ) ); ?>" data-carousel-slide>
						<?php
						$trust_img = Blocks::image(
							$trust_slide['image'],
							'large',
							array(
								'class'   => 'b-carousel__img',
								'loading' => 'lazy',
								// Слайд — 60 % ленты (до 720 px), на телефоне — 86 %.
								'sizes'   => '(max-width: 640px) 86vw, (max-width: 1296px) 60vw, 720px',
							),
							$trust_slide['alt']
						);
						echo $trust_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка wp_get_attachment_image().
						if ( $is_preview && Blocks::image_alt_missing( $trust_slide['image'], $trust_slide['alt'] ) ) {
							/* translators: %d: номер слайда. */
							echo Blocks::editor_warning( sprintf( __( 'Слайд %d: нет описания картинки (alt). Заполните поле «Alt» или описание в медиатеке.', 'trust' ), $trust_i + 1 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка экранирована в Blocks::editor_warning().
						}
						?>
						<div class="b-carousel__text">
							<?php if ( '' !== $trust_slide['niche'] ) : ?>
								<span class="b-carousel__niche"><?php echo esc_html( $trust_slide['niche'] ); ?></span>
							<?php endif; ?>
							<h3 class="b-carousel__title">
								<?php if ( '' !== $trust_slide['url'] ) : ?>
									<a class="b-carousel__link" href="<?php echo esc_url( $trust_slide['url'] ); ?>"><?php echo esc_html( $trust_slide['title'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $trust_slide['title'] ); ?>
								<?php endif; ?>
							</h3>
							<?php if ( '' !== $trust_slide['text'] ) : ?>
								<p class="b-carousel__desc"><?php echo esc_html( $trust_slide['text'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<?php echo Blocks::editor_warning( __( 'Добавьте слайды: скриншот и заголовок.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка экранирована в Blocks::editor_warning(). ?>
		<?php endif; ?>
	</div>
</section>
