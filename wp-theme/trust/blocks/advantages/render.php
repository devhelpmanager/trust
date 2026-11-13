<?php
/**
 * Блок «Наши преимущества» (макет holst-blokov/Advantages, seo-bloki/02-preimushchestva.html).
 *
 * Фиолетовая секция: надзаголовок и H2, ниже пункты в сетке (по 3 в ряд на широком экране,
 * дальше — сколько помещается при ширине колонки от 320 px). У пункта — белая линия сверху,
 * крупный номер 01, 02…, иконка на плашке, заголовок H3 и текст.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_items    = array_values(
	array_filter(
		(array) get_field( 'items' ),
		static fn( $row ) => ! empty( $row['title'] )
	)
);
$trust_title    = (string) get_field( 'title' );
$trust_eyebrow  = (string) get_field( 'eyebrow' );
$trust_lead     = (string) get_field( 'lead' );
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
?>
<section class="b-advantages trust-bg-violet"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-advantages__inner">
		<?php if ( $trust_eyebrow || $trust_title || $trust_lead ) : ?>
			<div class="b-advantages__head">
				<?php if ( $trust_eyebrow ) : ?>
					<span class="eyebrow"><?php echo esc_html( $trust_eyebrow ); ?></span>
				<?php endif; ?>
				<?php if ( $trust_title ) : ?>
					<h2 class="section-title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
				<?php endif; ?>
				<?php if ( $trust_lead ) : ?>
					<p class="section-lead section-lead--lg"><?php echo esc_html( $trust_lead ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $trust_items ) : ?>
			<ol class="b-advantages__list">
				<?php foreach ( $trust_items as $trust_i => $trust_row ) : ?>
					<?php
					$trust_icon = Icons::svg(
						(string) ( $trust_row['icon'] ?? '' ),
						array(
							'size'         => 26,
							'stroke_width' => 1.7,
						)
					);
					$trust_icon = '' !== $trust_icon ? $trust_icon : Icons::svg(
						'check',
						array(
							'size'         => 26,
							'stroke_width' => 1.7,
						)
					);
					?>
					<li class="b-advantages__item">
						<div class="b-advantages__top">
							<span class="b-advantages__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $trust_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<span class="b-advantages__icon" aria-hidden="true"><?php echo $trust_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG из спрайта темы. ?></span>
						</div>
						<h3 class="b-advantages__title"><?php echo esc_html( $trust_row['title'] ); ?></h3>
						<?php if ( ! empty( $trust_row['text'] ) ) : ?>
							<p class="b-advantages__text"><?php echo esc_html( $trust_row['text'] ); ?></p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php elseif ( $is_preview ) : ?>
			<p class="b-advantages__empty"><?php esc_html_e( 'Добавьте пункты в настройках блока.', 'trust' ); ?></p>
		<?php endif; ?>
	</div>
</section>
