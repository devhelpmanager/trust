<?php
/**
 * Блок «С чем пришёл клиент» (макет portfolio-blog-404/keys.html, раздел «01 · Задача»).
 *
 * Надзаголовок с номером раздела, H2, текст о клиенте и запросе, список проблем с красными
 * значками-крестиками (заголовок пункта — жирным перед текстом).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Case_Blocks;

defined( 'ABSPATH' ) || exit;

$trust_text     = trim( (string) get_field( 'text' ) );
$trust_problems = array_values(
	array_filter(
		(array) get_field( 'problems' ),
		static fn( $row ) => is_array( $row ) && ( '' !== trim( (string) ( $row['title'] ?? '' ) ) || '' !== trim( (string) ( $row['text'] ?? '' ) ) )
	)
);
$trust_title_id = Blocks::uid( $block, 'title' );

if ( '' === $trust_text && ! $trust_problems && ! $is_preview ) {
	return;
}

echo Case_Blocks::open( 'b-case-task', $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в open().
Case_Blocks::head( $trust_title_id, __( 'С чем пришёл клиент', 'trust' ), __( 'Задача', 'trust' ) );
?>
	<?php if ( '' !== $trust_text ) : ?>
		<div class="b-case-task__text"><?php echo wp_kses_post( $trust_text ); ?></div>
	<?php endif; ?>
	<?php if ( $trust_problems ) : ?>
		<ul class="b-case-task__list">
			<?php foreach ( $trust_problems as $trust_row ) : ?>
				<li class="b-case-task__item">
					<span class="b-case-task__icon" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg></span>
					<span>
						<?php if ( '' !== trim( (string) ( $trust_row['title'] ?? '' ) ) ) : ?>
							<strong><?php echo esc_html( trim( $trust_row['title'] ) ); ?></strong>
						<?php endif; ?>
						<?php echo esc_html( trim( (string) ( $trust_row['text'] ?? '' ) ) ); ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php elseif ( '' === $trust_text ) : ?>
		<?php echo Blocks::editor_warning( __( 'Добавьте текст и проблемы в настройках блока.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning(). ?>
	<?php endif; ?>
</section>
