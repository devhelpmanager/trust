<?php
/**
 * Блок «Как проходит отбор» (макет o-nas/karera.html, секция «Отбор»).
 *
 * Белая секция: шапка и нумерованный список шагов (от 240 px в колонку — 4 в ряд на компьютере):
 * над шагом — линия с кружком-номером, последний шаг — фиолетовый (итог отбора). Заголовок (H3)
 * и текст.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;

defined( 'ABSPATH' ) || exit;

$trust_steps    = array_values( array_filter( (array) get_field( 'steps' ), static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['title'] ?? '' ) ) ) );
$trust_title_id = Blocks::uid( $block, 'title' );

if ( ! $trust_steps && ! $is_preview ) {
	return;
}
?>
<section class="b-hiring trust-bg-white"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-hiring__inner">
		<?php Blocks::section_head( 'b-hiring__head', $trust_title_id ); ?>
		<?php if ( $trust_steps ) : ?>
			<ol class="b-hiring__list">
				<?php foreach ( $trust_steps as $trust_i => $trust_row ) : ?>
					<li class="b-hiring__step<?php echo count( $trust_steps ) - 1 === $trust_i && $trust_i > 0 ? ' is-final' : ''; ?>">
						<span class="b-hiring__num" aria-hidden="true"><?php echo esc_html( (string) ( $trust_i + 1 ) ); ?></span>
						<h3 class="b-hiring__title"><?php echo esc_html( $trust_row['title'] ); ?></h3>
						<?php if ( '' !== trim( (string) ( $trust_row['text'] ?? '' ) ) ) : ?>
							<p class="b-hiring__text"><?php echo esc_html( $trust_row['text'] ); ?></p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php else : ?>
			<?php echo Blocks::editor_warning( __( 'Добавьте шаги в настройках блока.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning(). ?>
		<?php endif; ?>
	</div>
</section>
