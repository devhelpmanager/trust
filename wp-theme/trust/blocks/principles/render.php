<?php
/**
 * Блок «Как мы работаем» (макет o-nas/o-kompanii.html, «Принципы»).
 *
 * Фиолетовая секция, шапка, нумерованный список (от 320 px в колонку — 3 / 2 / 1): над пунктом —
 * белая линия, номер 01, 02… крупным шрифтом (или значок, если выбран), заголовок (H3), текст.
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
<section class="b-principles trust-bg-violet"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-principles__inner">
		<?php Blocks::section_head( 'b-principles__head', $trust_title_id ); ?>
		<ol class="b-principles__list">
			<?php foreach ( $trust_items as $trust_i => $trust_row ) : ?>
				<li>
					<?php if ( ! empty( $trust_row['icon'] ) && Icons::exists( $trust_row['icon'] ) ) : ?>
						<span class="b-principles__icon" aria-hidden="true">
							<?php
							Icons::render(
								$trust_row['icon'],
								array(
									'size'         => 30,
									'stroke_width' => 1.8,
								)
							);
							?>
						</span>
					<?php else : ?>
						<span class="b-principles__num" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $trust_i + 1 ) ); ?></span>
					<?php endif; ?>
					<h3 class="b-principles__title"><?php echo esc_html( $trust_row['title'] ); ?></h3>
					<?php if ( '' !== trim( (string) ( $trust_row['text'] ?? '' ) ) ) : ?>
						<p class="b-principles__text"><?php echo esc_html( $trust_row['text'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
