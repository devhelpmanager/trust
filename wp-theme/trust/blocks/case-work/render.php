<?php
/**
 * Блок «Что сделали» (макет portfolio-blog-404/keys.html, раздел «03 · Работы»).
 *
 * Надзаголовок с номером раздела, H2 и этапы на вертикальной шкале: кружок и линия слева,
 * плашка срока, заголовок (H3), текст. У последнего этапа — пустой кружок без линии.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Case_Blocks;

defined( 'ABSPATH' ) || exit;

$trust_steps    = array_values( array_filter( (array) get_field( 'steps' ), static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['title'] ?? '' ) ) ) );
$trust_title_id = Blocks::uid( $block, 'title' );

if ( ! $trust_steps && ! $is_preview ) {
	return;
}

echo Case_Blocks::open( 'b-case-work', $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в open().
Case_Blocks::head( $trust_title_id, __( 'Что сделали', 'trust' ), __( 'Работы', 'trust' ) );
?>
	<?php if ( $trust_steps ) : ?>
		<ol class="b-case-work__list">
			<?php foreach ( $trust_steps as $trust_row ) : ?>
				<li class="b-case-work__step">
					<span class="b-case-work__rail" aria-hidden="true"><span class="b-case-work__dot"></span><span class="b-case-work__line"></span></span>
					<div class="b-case-work__body">
						<?php if ( '' !== trim( (string) ( $trust_row['term'] ?? '' ) ) ) : ?>
							<span class="b-case-work__term"><?php echo esc_html( trim( $trust_row['term'] ) ); ?></span>
						<?php endif; ?>
						<h3 class="b-case-work__title"><?php echo esc_html( trim( $trust_row['title'] ) ); ?></h3>
						<?php if ( '' !== trim( (string) ( $trust_row['text'] ?? '' ) ) ) : ?>
							<p class="b-case-work__text"><?php echo esc_html( trim( $trust_row['text'] ) ); ?></p>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php else : ?>
		<?php echo Blocks::editor_warning( __( 'Добавьте этапы в настройках блока.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning(). ?>
	<?php endif; ?>
</section>
