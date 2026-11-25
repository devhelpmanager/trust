<?php
/**
 * Блок «Карточки с плашкой» (макет o-nas/politika-cookie.html, раздел «Какие cookie мы
 * используем»): карточки по три в ряд, в каждой плашка-название и пояснение. Колонка уже
 * 520 px — по одной (container query).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;

defined( 'ABSPATH' ) || exit;

$trust_cards = array();
foreach ( (array) get_field( 'items' ) as $trust_row ) {
	$trust_label = trim( (string) ( $trust_row['label'] ?? '' ) );
	$trust_text  = trim( (string) ( $trust_row['text'] ?? '' ) );
	if ( '' !== $trust_label || '' !== $trust_text ) {
		$trust_cards[] = array( $trust_label, $trust_text );
	}
}
if ( ! $trust_cards ) {
	if ( $is_preview ) {
		echo Blocks::editor_warning( __( 'Добавьте карточки в настройках блока.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning().
	}
	return;
}
?>
<div class="b-doc-cards">
	<ul class="b-doc-cards__list">
		<?php foreach ( $trust_cards as list( $trust_label, $trust_text ) ) : ?>
			<li class="b-doc-cards__card">
				<?php if ( '' !== $trust_label ) : ?>
					<span class="b-doc-cards__label"><?php echo esc_html( $trust_label ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $trust_text ) : ?>
					<span class="b-doc-cards__text"><?php echo esc_html( $trust_text ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
