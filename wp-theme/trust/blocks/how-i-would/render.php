<?php
/**
 * Блок «Как бы поступил я» (макет seo-vopros/otvet.html, итог ответа).
 *
 * Фиолетовая карточка: заголовок (H2 или H3), вложенные блоки — список с галочками и абзацы, внизу
 * под линией — текст и белая кнопка со стрелкой (необязательно).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_level    = 'h3' === get_field( 'level' ) ? 'h3' : 'h2';
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_note     = trim( (string) get_field( 'note' ) );
$trust_button   = get_field( 'button' );
$trust_button   = is_array( $trust_button ) && ! empty( $trust_button['url'] ) && '' !== trim( (string) ( $trust_button['title'] ?? '' ) ) ? $trust_button : null;
$trust_template = array(
	array(
		'core/list',
		array( 'ordered' => true ),
		array(
			array( 'core/list-item', array( 'placeholder' => __( 'Что бы я сделал первым…', 'trust' ) ) ),
			array( 'core/list-item', array( 'placeholder' => __( 'Потом…', 'trust' ) ) ),
		),
	),
);
?>
<section class="b-how" aria-labelledby="<?php echo esc_attr( $trust_title_id ); ?>">
	<<?php echo esc_html( $trust_level ); ?> class="b-how__title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( Blocks::text( 'title', __( 'Как бы поступил я', 'trust' ) ) ); ?></<?php echo esc_html( $trust_level ); ?>>
	<InnerBlocks class="b-how__body" allowedBlocks="<?php echo esc_attr( wp_json_encode( array( 'core/list', 'core/list-item', 'core/paragraph' ) ) ); ?>" template="<?php echo esc_attr( wp_json_encode( $trust_template ) ); ?>" />
	<?php if ( '' !== $trust_note || $trust_button ) : ?>
		<div class="b-how__foot">
			<?php if ( '' !== $trust_note ) : ?>
				<p class="b-how__note"><?php echo esc_html( $trust_note ); ?></p>
			<?php endif; ?>
			<?php if ( $trust_button ) : ?>
				<a class="b-how__cta" href="<?php echo esc_url( $trust_button['url'] ); ?>"<?php echo '_blank' === ( $trust_button['target'] ?? '' ) ? ' target="_blank" rel="noopener"' : ''; ?>>
					<span class="b-how__cta-text"><?php echo esc_html( $trust_button['title'] ); ?></span>
					<span class="b-how__cta-arrow" aria-hidden="true">
						<?php
						Icons::render(
							'arrow-right',
							array(
								'size'         => 20,
								'stroke_width' => 2.2,
							)
						);
						?>
					</span>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
