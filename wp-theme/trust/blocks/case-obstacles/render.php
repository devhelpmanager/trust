<?php
/**
 * Блок «Что мешало росту» (макет portfolio-blog-404/keys.html, раздел «02 · Аудит»).
 *
 * Надзаголовок с номером раздела, H2, карточки находок аудита: крупное число, подпись и
 * необязательное пояснение. По две в ряд, уже 520 px — в колонку.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Case_Blocks;

defined( 'ABSPATH' ) || exit;

$trust_cards    = array_values(
	array_filter(
		(array) get_field( 'cards' ),
		static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['number'] ?? '' ) ) . trim( (string) ( $row['label'] ?? '' ) )
	)
);
$trust_title_id = Blocks::uid( $block, 'title' );

if ( ! $trust_cards && ! $is_preview ) {
	return;
}

echo Case_Blocks::open( 'b-case-obstacles', $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в open().
Case_Blocks::head( $trust_title_id, __( 'Что мешало росту', 'trust' ), __( 'Аудит', 'trust' ) );
?>
	<?php if ( $trust_cards ) : ?>
		<ul class="b-case-obstacles__list">
			<?php foreach ( $trust_cards as $trust_row ) : ?>
				<li class="b-case-obstacles__card">
					<span class="b-case-obstacles__number"><?php echo esc_html( trim( (string) $trust_row['number'] ) ); ?></span>
					<span class="b-case-obstacles__label"><?php echo esc_html( trim( (string) $trust_row['label'] ) ); ?></span>
					<?php if ( '' !== trim( (string) ( $trust_row['note'] ?? '' ) ) ) : ?>
						<span class="b-case-obstacles__note"><?php echo esc_html( trim( $trust_row['note'] ) ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<?php echo Blocks::editor_warning( __( 'Добавьте карточки в настройках блока.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning(). ?>
	<?php endif; ?>
</section>
