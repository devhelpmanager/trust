<?php
/**
 * Блок «Сертификаты и рейтинги» (макет o-nas/o-kompanii.html, «Подтверждения»).
 *
 * Белая секция, шапка, сетка карточек (от 260 px в колонку): картинка 4:3 (скан или логотип;
 * нет — значок награды на полосатом фоне), название, строка «кто выдал · год · смотреть документ».
 * Карточка — ссылка: скан открывается на весь экран (assets/js/lightbox.js, подпись — название),
 * PDF и ссылка — в новой вкладке. На телефоне (до 560 px) — компактные строки: картинка слева.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_items    = array_values( array_filter( (array) get_field( 'items' ), static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['title'] ?? '' ) ) ) );
$trust_title_id = Blocks::uid( $block, 'title' );

if ( ! $trust_items && ! $is_preview ) {
	return;
}
?>
<section class="b-certs trust-bg-white"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-certs__inner">
		<?php Blocks::section_head( 'b-certs__head', $trust_title_id ); ?>
		<ul class="b-certs__list">
			<?php foreach ( $trust_items as $trust_row ) : ?>
				<?php
				$trust_name  = trim( $trust_row['title'] );
				$trust_file  = (int) ( $trust_row['file'] ?? 0 );
				$trust_url   = trim( (string) ( $trust_row['url'] ?? '' ) );
				$trust_scan  = $trust_file && wp_attachment_is_image( $trust_file );
				$trust_href  = $trust_file ? (string) wp_get_attachment_url( $trust_file ) : $trust_url;
				$trust_label = trim( (string) ( $trust_row['link_label'] ?? '' ) );
				$trust_label = '' !== $trust_label ? $trust_label : __( 'смотреть документ', 'trust' );
				$trust_meta  = array_filter( array( trim( (string) ( $trust_row['issuer'] ?? '' ) ), trim( (string) ( $trust_row['year'] ?? '' ) ), '' !== $trust_href ? $trust_label : '' ), 'strlen' );
				$trust_img   = Blocks::image(
					(int) ( $trust_row['image'] ?? 0 ),
					'medium_large',
					array(
						'class'   => 'b-certs__img',
						'loading' => 'lazy',
						'sizes'   => '(max-width: 560px) 76px, 290px',
					),
					Blocks::alt_text( (int) ( $trust_row['image'] ?? 0 ), (string) ( $trust_row['alt'] ?? '' ), $trust_name )
				);
				$trust_tag   = '' !== $trust_href ? 'a' : 'div';
				$trust_attrs = '';
				if ( '' !== $trust_href ) {
					$trust_attrs = ' href="' . esc_url( $trust_href ) . '"';
					if ( $trust_scan ) {
						$trust_attrs .= ' data-trust-lightbox data-lightbox-caption="' . esc_attr( $trust_name ) . '"';
					} elseif ( ! str_starts_with( $trust_href, '#' ) ) {
						$trust_attrs .= ' target="_blank" rel="noopener"';
					}
				}
				?>
				<li>
					<<?php echo esc_attr( $trust_tag ); ?> class="b-certs__card"<?php echo $trust_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано выше. ?>>
						<span class="b-certs__media<?php echo '' === $trust_img ? ' b-certs__media--empty' : ''; ?>">
							<?php if ( '' !== $trust_img ) : ?>
								<?php echo $trust_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
							<?php else : ?>
								<?php
								Icons::render(
									'award',
									array(
										'size'         => 36,
										'stroke_width' => 1.6,
									)
								);
								?>
							<?php endif; ?>
						</span>
						<span class="b-certs__title"><?php echo esc_html( $trust_name ); ?></span>
						<?php if ( $trust_meta ) : ?>
							<span class="b-certs__meta"><?php echo esc_html( implode( ' · ', $trust_meta ) ); ?></span>
						<?php endif; ?>
					</<?php echo esc_attr( $trust_tag ); ?>>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
