<?php
/**
 * Блок «Почему мы открыто пишем про всё» (макет holst-blokov/OpenApproach,
 * seo-bloki/05-otkryto-pishem.html).
 *
 * Фиолетовая секция с точечным фоном: слева надзаголовок и H2, справа знак цитаты, цитата
 * крупным шрифтом, продолжение и подпись — фото (или значок), имя и должность.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_title    = (string) get_field( 'title' );
$trust_eyebrow  = (string) get_field( 'eyebrow' );
$trust_quote    = (string) get_field( 'quote' );
$trust_text     = (string) get_field( 'text' );
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_source   = (string) get_field( 'author_source' );
$trust_source   = '' === $trust_source ? 'team' : $trust_source;
$trust_role     = trim( (string) get_field( 'author_role' ) );
$trust_name     = '';
$trust_photo    = '';

if ( 'team' === $trust_source ) {
	$trust_member = get_post( (int) get_field( 'author' ) );
	if ( $trust_member && 'team' === $trust_member->post_type ) {
		$trust_name  = get_the_title( $trust_member );
		$trust_role  = '' !== $trust_role ? $trust_role : (string) get_field( 'trust_position', $trust_member->ID );
		$trust_photo = (string) get_the_post_thumbnail(
			$trust_member,
			'thumbnail',
			array(
				'class'   => 'b-open__photo',
				'alt'     => '',
				'loading' => 'lazy',
			)
		);
	}
} elseif ( 'manual' === $trust_source ) {
	$trust_name  = trim( (string) get_field( 'author_name' ) );
	$trust_photo = Blocks::image(
		(int) get_field( 'author_photo' ),
		'thumbnail',
		array(
			'class'   => 'b-open__photo',
			'loading' => 'lazy',
		),
		'',
		true
	);
}
?>
<section class="b-open trust-bg-violet"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-open__inner">
		<div class="b-open__head">
			<?php if ( $trust_eyebrow ) : ?>
				<span class="eyebrow"><?php echo esc_html( $trust_eyebrow ); ?></span>
			<?php endif; ?>
			<?php if ( $trust_title ) : ?>
				<h2 class="b-open__title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
			<?php endif; ?>
		</div>

		<?php if ( '' !== $trust_quote ) : ?>
			<figure class="b-open__figure">
				<svg class="b-open__mark" width="52" height="40" viewBox="0 0 44 34" aria-hidden="true" focusable="false"><path d="M0 34V20.4C0 8.9 6.2 2.1 17.4 0l1.9 4.6C13 6.4 10.2 10 9.8 15.3H18V34H0zm25 0V20.4C25 8.9 31.2 2.1 42.4 0l1.6 4.6c-6.3 1.8-9.1 5.4-9.5 10.7H43V34H25z"/></svg>
				<blockquote class="b-open__quote">
					<p class="b-open__lead"><?php echo esc_html( $trust_quote ); ?></p>
					<?php if ( '' !== $trust_text ) : ?>
						<p class="b-open__text"><?php echo esc_html( $trust_text ); ?></p>
					<?php endif; ?>
				</blockquote>
				<?php if ( '' !== $trust_name || ( 'none' !== $trust_source && '' !== $trust_role ) ) : ?>
					<figcaption class="b-open__author">
						<span class="b-open__avatar" aria-hidden="true">
							<?php
							if ( '' !== $trust_photo ) {
								echo $trust_photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image().
							} else {
								Icons::render(
									'user',
									array(
										'size'         => 22,
										'stroke_width' => 1.8,
									)
								);
							}
							?>
						</span>
						<span class="b-open__who">
							<?php if ( '' !== $trust_name ) : ?>
								<span class="b-open__name"><?php echo esc_html( $trust_name ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $trust_role ) : ?>
								<span class="b-open__role"><?php echo esc_html( $trust_role ); ?></span>
							<?php endif; ?>
						</span>
					</figcaption>
				<?php endif; ?>
			</figure>
		<?php elseif ( $is_preview ) : ?>
			<p class="b-open__empty"><?php esc_html_e( 'Добавьте цитату в настройках блока.', 'trust' ); ?></p>
		<?php endif; ?>
	</div>
</section>
