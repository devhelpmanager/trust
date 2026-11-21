<?php
/**
 * Блок «Слово руководителя» (макет o-nas/o-kompanii.html, секция «Слово руководителя»).
 *
 * Лавандовая секция: слева фото 4:5 со скруглением, справа надпись (H2 в виде надзаголовка),
 * кавычки, цитата крупным шрифтом, имя и должность, кнопка «Написать руководителю».
 * «Познакомиться с командой» из макета убрана по просьбе заказчика. Без фото — силуэт.
 *
 * Правка заказчика: кнопка открывает окно с формой (modal.php; открытие и отправка —
 * assets/js/callback.js и forms.js), сообщения — в «Заявки → Обращения к руководителю»
 * (Trust\Forms\Leader_Messages).
 *
 * Правка заказчика: имя с должностью и кнопка — строкой под цитатой с небольшим отступом,
 * подпись слева, кнопка у правого края; на узком экране кнопка под подписью, тоже справа.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_member   = get_post( (int) get_field( 'member' ) );
$trust_member   = $trust_member && 'team' === $trust_member->post_type && 'publish' === $trust_member->post_status ? $trust_member : null;
$trust_title    = Blocks::text( 'title', __( 'Слово руководителя', 'trust' ) );
$trust_quote    = trim( (string) get_field( 'quote' ) );
$trust_name     = $trust_member ? get_the_title( $trust_member ) : '';
$trust_role     = trim( (string) get_field( 'role' ) );
$trust_role     = '' !== $trust_role || ! $trust_member ? $trust_role : trim( (string) get_field( 'trust_position', $trust_member->ID ) );
$trust_photo_id = (int) get_field( 'photo' );
$trust_photo_id = $trust_photo_id ? $trust_photo_id : ( $trust_member ? (int) get_post_thumbnail_id( $trust_member ) : 0 );
$trust_alt      = Blocks::alt_text( $trust_photo_id, '', implode( ', ', array_filter( array( $trust_name, $trust_role ) ) ) );
$trust_photo    = Blocks::image(
	$trust_photo_id,
	'large',
	array(
		'class'   => 'b-leader__photo',
		'loading' => 'lazy',
		'sizes'   => '(max-width: 760px) 92vw, 380px',
	),
	$trust_alt
);
$trust_write    = false !== get_field( 'write' );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_modal_id = Blocks::uid( $block, 'write' );
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );

if ( '' === $trust_quote && ! $is_preview ) {
	return;
}
?>
<section class="b-leader trust-bg-lavender"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?> aria-labelledby="<?php echo esc_attr( $trust_title_id ); ?>">
	<div class="b-leader__inner">
		<?php if ( '' !== $trust_photo ) : ?>
			<?php echo $trust_photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
		<?php else : ?>
			<span class="b-leader__photo b-leader__photo--empty" aria-hidden="true">
				<?php
				Icons::render(
					'user',
					array(
						'size'         => 64,
						'stroke_width' => 1.4,
					)
				);
				?>
			</span>
		<?php endif; ?>
		<figure class="b-leader__figure">
			<h2 class="eyebrow b-leader__label" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
			<svg class="b-leader__mark" width="44" height="34" viewBox="0 0 44 34" aria-hidden="true" focusable="false"><path d="M0 34V20.4C0 8.9 6.2 2.1 17.4 0l1.9 4.6C13 6.4 10.2 10 9.8 15.3H18V34H0zm25 0V20.4C25 8.9 31.2 2.1 42.4 0l1.6 4.6c-6.3 1.8-9.1 5.4-9.5 10.7H43V34H25z"/></svg>
			<?php if ( '' !== $trust_quote ) : ?>
				<blockquote class="b-leader__quote"><?php echo wp_kses_post( wpautop( $trust_quote ) ); ?></blockquote>
			<?php elseif ( $is_preview ) : ?>
				<p class="b-leader__empty"><?php esc_html_e( 'Добавьте цитату в настройках блока.', 'trust' ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $trust_name || '' !== $trust_role || $trust_write ) : ?>
				<figcaption class="b-leader__foot">
					<?php if ( '' !== $trust_name || '' !== $trust_role ) : ?>
						<span class="b-leader__who">
							<?php if ( '' !== $trust_name ) : ?>
								<span class="b-leader__name"><?php echo esc_html( $trust_name ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $trust_role ) : ?>
								<span class="b-leader__role"><?php echo esc_html( $trust_role ); ?></span>
							<?php endif; ?>
						</span>
					<?php endif; ?>
					<?php if ( $trust_write ) : ?>
						<button type="button" class="b-leader__btn" data-trust-modal="<?php echo esc_attr( $trust_modal_id ); ?>" aria-haspopup="dialog" aria-controls="<?php echo esc_attr( $trust_modal_id ); ?>"><?php echo esc_html( Blocks::text( 'write_label', __( 'Написать руководителю', 'trust' ) ) ); ?></button>
					<?php endif; ?>
				</figcaption>
			<?php endif; ?>
		</figure>
	</div>
	<?php
	if ( $trust_write && ! $is_preview ) {
		require __DIR__ . '/modal.php';
	}
	?>
</section>
