<?php
/**
 * Блок «Чем мы занимаемся» (макет o-nas/o-kompanii.html, секция «Направления»).
 *
 * Белая секция, шапка, сетка карточек (от 340 px в колонку — 3 / 2 / 1): значок и справа от него
 * название (H3, правка заказчика), под ними текст и «Подробнее» со стрелкой. Карточка со страницей — ссылка целиком (под мышью — рамка, тень,
 * стрелка сдвигается), без страницы — просто карточка.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_items    = array_values( array_filter( (array) get_field( 'items' ), static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['title'] ?? '' ) ) ) );
$trust_more     = Blocks::text( 'more_label', __( 'Подробнее', 'trust' ) );
$trust_title_id = Blocks::uid( $block, 'title' );

if ( ! $trust_items && ! $is_preview ) {
	return;
}
?>
<section class="b-dirs trust-bg-white"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-dirs__inner">
		<?php Blocks::section_head( 'b-dirs__head', $trust_title_id ); ?>
		<ul class="b-dirs__list">
			<?php foreach ( $trust_items as $trust_row ) : ?>
				<?php
				$trust_url  = is_string( $trust_row['link'] ?? null ) ? trim( $trust_row['link'] ) : '';
				$trust_tag  = '' !== $trust_url ? 'a' : 'div';
				$trust_icon = Icons::exists( (string) ( $trust_row['icon'] ?? '' ) ) ? $trust_row['icon'] : 'search';
				?>
				<li>
					<<?php echo esc_attr( $trust_tag ); ?> class="b-dirs__card"<?php echo '' !== $trust_url ? ' href="' . esc_url( $trust_url ) . '"' : ''; ?>>
						<span class="b-dirs__icon" aria-hidden="true">
							<?php
							Icons::render(
								$trust_icon,
								array(
									'size'         => 24,
									'stroke_width' => 1.9,
								)
							);
							?>
						</span>
						<h3 class="b-dirs__title"><?php echo esc_html( $trust_row['title'] ); ?></h3>
						<?php if ( '' !== trim( (string) ( $trust_row['text'] ?? '' ) ) ) : ?>
							<p class="b-dirs__text"><?php echo esc_html( $trust_row['text'] ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== $trust_url ) : ?>
							<span class="b-dirs__more">
								<?php echo esc_html( $trust_more ); ?>
								<?php
								Icons::render(
									'arrow-right',
									array(
										'size'         => 16,
										'stroke_width' => 2.2,
									)
								);
								?>
							</span>
						<?php endif; ?>
					</<?php echo esc_attr( $trust_tag ); ?>>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
