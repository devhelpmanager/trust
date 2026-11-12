<?php
/**
 * Блок «Этапы работы» (макет holst-stranitsy/Main → Stages, seo-bloki/06-etapy-raboty.html).
 *
 * Над каждой карточкой — номер в круге и линия со стрелкой к следующему этапу (на телефоне —
 * стрелка вниз под карточкой). Карточка — кнопка: картинка, название, короткий текст;
 * по нажатию вместо картинки и короткого текста показывается подробный. Открытыми могут
 * быть несколько этапов. Итоговый этап — фиолетовая карточка с подписью «Результат».
 * До 5 этапов — в один ряд, 6 и 7 — в два (3 и 4 колонки); уже 1100 px — 3 колонки,
 * 760 px — 2, 480 px — одна. Без JavaScript подробный текст виден сразу.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_stages   = array_values(
	array_filter(
		(array) get_field( 'stages' ),
		static fn( $row ) => ! empty( $row['title'] ) && ! empty( $row['short'] ) && ! empty( $row['detail'] )
	)
);
$trust_title    = (string) get_field( 'title' );
$trust_eyebrow  = (string) get_field( 'eyebrow' );
$trust_lead     = (string) get_field( 'lead' );
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_count    = count( $trust_stages );
$trust_cols     = $trust_count <= 5 ? $trust_count : (int) ceil( $trust_count / 2 );

/**
 * Картинка этапа: рисунок из макета (встроенный SVG, украшение) или своя картинка из медиатеки.
 *
 * @param array $row Строка повторителя.
 */
$trust_picture = static function ( array $row ): string {
	$picture = (string) ( $row['picture'] ?? 'work' );
	if ( 'image' === $picture ) {
		$id = (int) ( $row['image'] ?? 0 );
		return $id ? (string) wp_get_attachment_image(
			$id,
			'medium_large',
			false,
			array(
				'class'   => 'b-stages__img',
				'loading' => 'lazy',
				'sizes'   => '(max-width: 480px) 100vw, (max-width: 1100px) 33vw, 240px',
			)
		) : '';
	}
	$file = __DIR__ . '/illustrations/' . sanitize_key( $picture ) . '.svg';
	return is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- файл темы.
};
?>
<section class="b-stages"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-stages__inner">
		<?php if ( $trust_eyebrow || $trust_title || $trust_lead ) : ?>
			<div class="b-stages__head">
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

		<?php if ( $trust_stages ) : ?>
			<ol class="b-stages__list b-stages__list--<?php echo esc_attr( (string) $trust_cols ); ?>">
				<?php foreach ( $trust_stages as $trust_i => $trust_row ) : ?>
					<?php
					$trust_result  = ! empty( $trust_row['result'] );
					$trust_last    = $trust_i === $trust_count - 1;
					$trust_pic     = $trust_picture( $trust_row );
					$trust_classes = 'b-stages__item' . ( $trust_result ? ' b-stages__item--result' : '' );
					?>
					<li class="<?php echo esc_attr( $trust_classes ); ?>">
						<div class="b-stages__top" aria-hidden="true">
							<span class="b-stages__num"><?php echo esc_html( str_pad( (string) ( $trust_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<?php if ( $trust_result ) : ?>
								<span class="b-stages__label"><?php esc_html_e( 'Результат', 'trust' ); ?></span>
							<?php elseif ( ! $trust_last ) : ?>
								<span class="b-stages__line"></span>
								<svg class="b-stages__arrow" width="10" height="12" viewBox="0 0 10 12" focusable="false"><path d="M1.5 1l7 5-7 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							<?php endif; ?>
						</div>
						<button type="button" class="b-stages__card" aria-expanded="false">
							<?php if ( '' !== $trust_pic ) : ?>
								<span class="b-stages__pic"><?php echo $trust_pic; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- рисунок темы или wp_get_attachment_image(). ?></span>
							<?php endif; ?>
							<span class="b-stages__name">
								<span class="b-stages__title"><?php echo esc_html( $trust_row['title'] ); ?></span>
								<span class="b-stages__plus" aria-hidden="true"><?php Icons::render( 'plus', array( 'size' => 14 ) ); ?></span>
							</span>
							<span class="b-stages__short"><?php echo esc_html( $trust_row['short'] ); ?></span>
							<span class="b-stages__detail" hidden><?php echo esc_html( $trust_row['detail'] ); ?></span>
						</button>
						<?php if ( ! $trust_last ) : ?>
							<span class="b-stages__down" aria-hidden="true"><svg width="12" height="22" viewBox="0 0 12 22" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M6 1v18" class="b-stages__down-line"/><path d="M1.5 15l4.5 4.5 4.5-4.5" class="b-stages__down-head"/></svg></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php elseif ( $is_preview ) : ?>
			<p class="b-stages__empty"><?php esc_html_e( 'Добавьте этапы в настройках блока.', 'trust' ); ?></p>
		<?php endif; ?>
	</div>
</section>
