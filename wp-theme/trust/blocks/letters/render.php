<?php
/**
 * Блок «Благодарственные письма» (макет o-nas/otzyvy.html, секция «Документы»).
 *
 * Белая секция, шапка с вводным текстом, сетка карточек (колонки от 170 px, на телефоне — по
 * две): скан 3:4 и название компании. По нажатию скан открывается крупно в окне просмотра
 * (trust-lightbox: <dialog>, Esc и «Закрыть», фокус возвращается). Без скана — рисунок листа
 * из макета, карточка без ссылки.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Reviews;

defined( 'ABSPATH' ) || exit;

$trust_manual = 'manual' === get_field( 'source' ) ? (array) get_field( 'reviews' ) : array();
$trust_count  = (int) get_field( 'count' );
$trust_ids    = Reviews::of_kind( 'letter', $trust_manual, $trust_manual ? 24 : ( $trust_count > 0 ? min( 24, $trust_count ) : 12 ) );
$trust_title  = Blocks::uid( $block, 'title' );

if ( ! $trust_ids && ! $is_preview ) {
	return;
}
?>
<section class="b-letters trust-bg-white"<?php echo Blocks::section_attrs( $trust_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-letters__inner">
		<?php Blocks::section_head( 'b-letters__head', $trust_title ); ?>
		<?php if ( ! $trust_ids ) : ?>
			<?php echo Blocks::editor_warning( __( 'Нет опубликованных писем: добавьте отзыв вида «Благодарственное письмо» в разделе «Отзывы».', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- текст экранирован в editor_warning(). ?>
		<?php else : ?>
			<ul class="b-letters__list">
				<?php foreach ( $trust_ids as $trust_id ) : ?>
					<?php
					$trust_company = trim( (string) get_post_meta( $trust_id, 'trust_company', true ) );
					$trust_company = '' !== $trust_company ? $trust_company : Reviews::author_line( $trust_id, true );
					$trust_scan    = (int) get_post_meta( $trust_id, 'trust_letter', true );
					$trust_full    = $trust_scan ? (string) wp_get_attachment_image_url( $trust_scan, 'full' ) : '';
					/* translators: %s: компания. */
					$trust_caption = sprintf( __( 'Благодарственное письмо — %s', 'trust' ), $trust_company );
					$trust_tag     = '' !== $trust_full ? 'a' : 'div';
					$trust_img     = Blocks::image(
						$trust_scan,
						'medium_large',
						array(
							'class'   => 'b-letters__scan',
							'loading' => 'lazy',
							'sizes'   => '(max-width: 560px) 44vw, 190px',
						),
						$trust_caption
					);
					?>
					<li>
						<<?php echo esc_attr( $trust_tag ); ?> class="b-letters__card"<?php echo '' !== $trust_full ? ' href="' . esc_url( $trust_full ) . '" data-trust-lightbox data-lightbox-caption="' . esc_attr( $trust_caption ) . '"' : ''; ?>>
							<?php if ( '' !== $trust_full ) : ?>
								<?php echo $trust_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
							<?php else : ?>
								<span class="b-letters__scan b-letters__scan--empty" aria-hidden="true">
									<span></span><span></span><span></span><span></span><span></span><span></span><span></span>
								</span>
							<?php endif; ?>
							<span class="b-letters__company"><?php echo esc_html( $trust_company ); ?></span>
						</<?php echo esc_attr( $trust_tag ); ?>>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
