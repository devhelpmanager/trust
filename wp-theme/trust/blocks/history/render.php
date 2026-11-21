<?php
/**
 * Блок «Как мы росли» (макет o-nas/o-kompanii.html, «История»).
 *
 * Белая секция, шапка, события по годам (от 200 px в колонку): над событием — линия с точкой,
 * год крупным шрифтом, заголовок (H3, если задан) и текст. У последнего события линия фиолетовая.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;

defined( 'ABSPATH' ) || exit;

$trust_items    = array_values( array_filter( (array) get_field( 'items' ), static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['year'] ?? '' ) ) ) );
$trust_title_id = Blocks::uid( $block, 'title' );

if ( ! $trust_items && ! $is_preview ) {
	return;
}
?>
<section class="b-history trust-bg-white"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-history__inner">
		<?php Blocks::section_head( 'b-history__head', $trust_title_id ); ?>
		<ol class="b-history__list">
			<?php foreach ( $trust_items as $trust_row ) : ?>
				<li>
					<span class="b-history__dot" aria-hidden="true"></span>
					<span class="b-history__year"><?php echo esc_html( trim( $trust_row['year'] ) ); ?></span>
					<?php if ( '' !== trim( (string) ( $trust_row['title'] ?? '' ) ) ) : ?>
						<h3 class="b-history__title"><?php echo esc_html( $trust_row['title'] ); ?></h3>
					<?php endif; ?>
					<?php if ( '' !== trim( (string) ( $trust_row['text'] ?? '' ) ) ) : ?>
						<p class="b-history__text"><?php echo esc_html( $trust_row['text'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
