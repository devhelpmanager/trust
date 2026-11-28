<?php
/**
 * Блок «Цитата с автором» (макет seo-blog/statya.html — цитата в тексте статьи).
 *
 * <figure>: знак цитаты, <blockquote> с текстом (абзацы — через пустую строку), <figcaption> —
 * фото или инициалы в круге, имя и должность. Автор — сотрудник (пусто — сотрудник, связанный с
 * автором статьи), вписанный вручную или без автора. Без текста на сайте блока нет, в редакторе —
 * подсказка.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Answers_List;
use Trust\Blocks;
use Trust\Team;

defined( 'ABSPATH' ) || exit;

$trust_text = trim( (string) get_field( 'text' ) );
if ( '' === $trust_text ) {
	if ( $is_preview ) {
		echo Blocks::editor_warning( __( 'Впишите текст цитаты.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в Blocks::editor_warning().
	}
	return;
}

// Автор: сотрудник (иначе — сотрудник автора статьи), вписанный вручную или никто.
$trust_source = (string) get_field( 'source' );
$trust_author = array(
	'name'  => '',
	'role'  => '',
	'photo' => 0,
);
if ( 'manual' === $trust_source ) {
	$trust_author = array(
		'name'  => trim( (string) get_field( 'name' ) ),
		'role'  => trim( (string) get_field( 'role' ) ),
		'photo' => (int) get_field( 'photo' ),
	);
} elseif ( 'none' !== $trust_source ) {
	$trust_member = get_post( (int) get_field( 'member' ) );
	if ( ! $trust_member || 'team' !== $trust_member->post_type || 'publish' !== $trust_member->post_status ) {
		$trust_owner  = get_post( (int) $post_id );
		$trust_member = $trust_owner ? Team::for_user( (int) $trust_owner->post_author ) : null;
	}
	if ( $trust_member ) {
		$trust_author = array(
			'name'  => get_the_title( $trust_member ),
			'role'  => trim( (string) get_post_meta( $trust_member->ID, 'trust_position', true ) ),
			'photo' => (int) get_post_thumbnail_id( $trust_member ),
		);
	}
}
$trust_paragraphs = array_filter( array_map( 'trim', (array) preg_split( '/\R\s*\R/', $trust_text ) ), 'strlen' );
?>
<figure class="b-quote">
	<svg class="b-quote__icon" width="36" height="36" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4 18v-5.5C4 8.4 6.2 6 9.8 5.2l.7 1.6C8.4 7.6 7.4 9 7.3 11H10v7H4zm10 0v-5.5c0-4.1 2.2-6.5 5.8-7.3l.7 1.6c-2.1.8-3.1 2.2-3.2 4.2H20v7h-6z"/></svg>
	<blockquote class="b-quote__text">
		<?php foreach ( $trust_paragraphs as $trust_paragraph ) : ?>
			<p><?php echo nl2br( esc_html( $trust_paragraph ) ); ?></p>
		<?php endforeach; ?>
	</blockquote>
	<?php if ( '' !== $trust_author['name'] ) : ?>
		<figcaption class="b-quote__author">
			<?php if ( $trust_author['photo'] ) : ?>
				<?php
				echo wp_get_attachment_image(
					$trust_author['photo'],
					'thumbnail',
					false,
					array(
						'class'   => 'b-quote__photo',
						'alt'     => '',
						'loading' => 'lazy',
					)
				);
				?>
			<?php else : ?>
				<span class="b-quote__photo" aria-hidden="true"><?php echo esc_html( Answers_List::initials( $trust_author['name'] ) ); ?></span>
			<?php endif; ?>
			<span class="b-quote__who">
				<span class="b-quote__name"><?php echo esc_html( $trust_author['name'] ); ?></span>
				<?php if ( '' !== $trust_author['role'] ) : ?>
					<span class="b-quote__role"><?php echo esc_html( $trust_author['role'] ); ?></span>
				<?php endif; ?>
			</span>
		</figcaption>
	<?php endif; ?>
</figure>
