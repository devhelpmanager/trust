<?php
/**
 * Блок «Картинка» (макет holst-blokov/ImageBlock, seo-bloki/08-kartinka.html).
 *
 * Белая секция, картинка в рамке со скруглением по ширине контента (1200 px) или текста
 * (760 px), ниже подпись «Рис. N» и описание. Номер — по порядку картинок на странице
 * (Trust\Blocks::figure_number()), можно задать вручную. Картинка для телефона — <picture>
 * с источником до 600 px. alt — из поля блока, иначе из медиатеки; декоративная — alt="".
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_id      = (int) get_field( 'image' );
$trust_mobile  = (int) get_field( 'mobile_image' );
$trust_alt     = (string) get_field( 'alt' );
$trust_deco    = (bool) get_field( 'decorative' );
$trust_caption = trim( (string) get_field( 'caption' ) );
$trust_wide    = 'text' !== get_field( 'width' );
$trust_anchor  = Blocks::anchor( get_field( 'anchor' ) );
$trust_manual  = trim( (string) get_field( 'number' ) );
$trust_number  = '' !== $trust_manual ? $trust_manual : (string) Blocks::figure_number( $block, $post_id );
$trust_sizes   = $trust_wide ? '(max-width: 1304px) 92vw, 1200px' : '(max-width: 826px) 92vw, 760px';
$trust_img     = Blocks::image(
	$trust_id,
	'full',
	array(
		'class'   => 'b-image__img',
		'loading' => 'lazy',
		'sizes'   => $trust_sizes,
	),
	$trust_alt,
	$trust_deco
);

// Размеры картинки для телефона — у <source>, иначе браузер оставит пропорции основной картинки.
$trust_mobile_src = $trust_mobile ? wp_get_attachment_image_src( $trust_mobile, 'full' ) : false;

if ( '' === $trust_img && ! $is_preview ) {
	return;
}
?>
<section class="b-image trust-bg-white"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?>>
	<?php
	if ( $is_preview && Blocks::image_alt_missing( $trust_id, $trust_alt, $trust_deco ) ) {
		echo Blocks::editor_warning( __( 'У картинки нет описания (alt). Заполните поле «Alt» или отметьте «Декоративная картинка».', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- текст экранирован в editor_warning().
	}
	?>
	<figure class="b-image__figure<?php echo $trust_wide ? '' : ' b-image__figure--text'; ?>">
		<div class="b-image__frame">
			<?php if ( '' !== $trust_img ) : ?>
				<picture>
					<?php if ( $trust_mobile ) : ?>
						<source media="(max-width: 600px)" srcset="<?php echo esc_attr( (string) wp_get_attachment_image_srcset( $trust_mobile, 'full' ) ); ?>" sizes="calc(100vw - 40px)"<?php echo $trust_mobile_src ? ' width="' . (int) $trust_mobile_src[1] . '" height="' . (int) $trust_mobile_src[2] . '"' : ''; ?>>
					<?php endif; ?>
					<?php echo $trust_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
				</picture>
			<?php else : ?>
				<span class="b-image__placeholder">
					<span class="b-image__placeholder-icon" aria-hidden="true">
						<?php
						Icons::render(
							'image',
							array(
								'size'         => 34,
								'stroke_width' => 1.6,
							)
						);
						?>
					</span>
					<?php esc_html_e( 'Выберите картинку в настройках блока', 'trust' ); ?>
				</span>
			<?php endif; ?>
		</div>
		<?php if ( '0' !== $trust_number || '' !== $trust_caption ) : ?>
			<figcaption class="b-image__caption">
				<?php if ( '0' !== $trust_number ) : ?>
					<span class="b-image__num">
						<?php
						/* translators: %s: номер рисунка. */
						echo esc_html( sprintf( __( 'Рис. %s', 'trust' ), $trust_number ) );
						?>
					</span>
				<?php endif; ?>
				<?php if ( '' !== $trust_caption ) : ?>
					<span class="b-image__text"><?php echo esc_html( $trust_caption ); ?></span>
				<?php endif; ?>
			</figcaption>
		<?php endif; ?>
	</figure>
</section>
