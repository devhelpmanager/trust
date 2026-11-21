<?php
/**
 * Блок «Где мы работаем» (макет o-nas/o-kompanii.html, «Офис»).
 *
 * Белая секция: надзаголовок, H2, текст со ссылкой на страницу контактов (и адрес из настроек по
 * флажку), галерея: 4 колонки, первое фото — 2 × 2, на телефоне 2 колонки. Подпись — плашкой на
 * фото. Фото открывается на весь экран (assets/js/lightbox.js); без фото — полосатая заглушка
 * с подписью (как в макете).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Contacts;
use Trust\Icons;
use Trust\Page_Types;

defined( 'ABSPATH' ) || exit;

$trust_photos   = array_values( array_filter( (array) get_field( 'photos' ), static fn( $row ) => is_array( $row ) && ( ! empty( $row['image'] ) || '' !== trim( (string) ( $row['caption'] ?? '' ) ) ) ) );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_text     = Blocks::text( 'text', __( 'Приезжайте познакомиться — адрес и схема проезда', 'trust' ) );
$trust_label    = Blocks::text( 'link_label', __( 'на странице контактов', 'trust' ) );
$trust_url      = trim( (string) get_field( 'link_url' ) );
$trust_url      = '' !== $trust_url ? $trust_url : Page_Types::url( 'contacts' );
$trust_address  = get_field( 'address' ) ? Contacts::address_line() : '';

if ( ! $trust_photos && ! $is_preview ) {
	return;
}
?>
<section class="b-office trust-bg-white"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-office__inner">
		<div class="section-head b-office__head">
			<?php Blocks::section_head( 'b-office__titles', $trust_title_id ); ?>
			<?php if ( '' !== $trust_text || ( '' !== $trust_label && '' !== $trust_url ) ) : ?>
				<p class="section-lead">
					<?php echo esc_html( $trust_text ); ?>
					<?php if ( '' !== $trust_label && '' !== $trust_url ) : ?>
						<a href="<?php echo esc_url( $trust_url ); ?>"><?php echo esc_html( $trust_label ); ?></a>.
					<?php endif; ?>
				</p>
			<?php endif; ?>
			<?php if ( '' !== $trust_address ) : ?>
				<p class="b-office__address"><?php echo esc_html( $trust_address ); ?></p>
			<?php endif; ?>
		</div>
		<div class="b-office__gallery">
			<?php foreach ( $trust_photos as $trust_i => $trust_row ) : ?>
				<?php
				$trust_id      = (int) ( $trust_row['image'] ?? 0 );
				$trust_caption = trim( (string) ( $trust_row['caption'] ?? '' ) );
				$trust_alt     = trim( (string) ( $trust_row['alt'] ?? '' ) );
				$trust_img     = Blocks::image(
					$trust_id,
					0 === $trust_i ? 'large' : 'medium_large',
					array(
						'class'   => 'b-office__img',
						'loading' => 'lazy',
						'sizes'   => 0 === $trust_i ? '(max-width: 760px) 92vw, 600px' : '(max-width: 760px) 46vw, 290px',
					),
					Blocks::alt_text( $trust_id, $trust_alt, $trust_caption )
				);
				$trust_full    = $trust_id ? (string) wp_get_attachment_image_url( $trust_id, 'full' ) : '';
				?>
				<figure class="b-office__item<?php echo '' === $trust_img ? ' b-office__item--empty' : ''; ?>">
					<?php if ( '' !== $trust_img && '' !== $trust_full && ! $is_preview ) : ?>
						<a class="b-office__open" href="<?php echo esc_url( $trust_full ); ?>" data-trust-lightbox<?php echo '' !== $trust_caption ? ' data-lightbox-caption="' . esc_attr( $trust_caption ) . '"' : ''; ?>>
							<?php echo $trust_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
						</a>
					<?php elseif ( '' !== $trust_img ) : ?>
						<?php echo $trust_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
					<?php elseif ( 0 === $trust_i ) : ?>
						<span class="b-office__placeholder" aria-hidden="true">
							<?php
							Icons::render(
								'image',
								array(
									'size'         => 30,
									'stroke_width' => 1.6,
								)
							);
							?>
						</span>
					<?php endif; ?>
					<?php if ( '' !== $trust_caption ) : ?>
						<figcaption class="b-office__caption"><?php echo esc_html( $trust_caption ); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
