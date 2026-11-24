<?php
/**
 * Блок «Почему у нас интересно работать» (макет o-nas/karera.html, секция «Условия»).
 *
 * Белая секция: шапка и карточки от 340 px в колонку (3 / 2 / 1): значок на лавандовой плашке,
 * заголовок (H3), текст.
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
<section class="b-benefits trust-bg-white"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-benefits__inner">
		<?php Blocks::section_head( 'b-benefits__head', $trust_title_id ); ?>
		<?php if ( $trust_items ) : ?>
			<ul class="b-benefits__list">
				<?php foreach ( $trust_items as $trust_row ) : ?>
					<li class="b-benefits__item">
						<?php if ( ! empty( $trust_row['icon'] ) && Icons::exists( $trust_row['icon'] ) ) : ?>
							<span class="b-benefits__icon" aria-hidden="true">
								<?php
								Icons::render(
									$trust_row['icon'],
									array(
										'size'         => 24,
										'stroke_width' => 1.9,
									)
								);
								?>
							</span>
						<?php endif; ?>
						<h3 class="b-benefits__title"><?php echo esc_html( $trust_row['title'] ); ?></h3>
						<?php if ( '' !== trim( (string) ( $trust_row['text'] ?? '' ) ) ) : ?>
							<p class="b-benefits__text"><?php echo esc_html( $trust_row['text'] ); ?></p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<?php echo Blocks::editor_warning( __( 'Добавьте пункты в настройках блока.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning(). ?>
		<?php endif; ?>
	</div>
</section>
