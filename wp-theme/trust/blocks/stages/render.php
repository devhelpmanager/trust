<?php
/**
 * Блок «Этапы работы» (макет holst-stranitsy/Main → Stages, seo-bloki/06-etapy-raboty.html).
 *
 * Над каждой карточкой — скруглённый прямоугольник на всю её ширину с номером этапа (правка
 * заказчика; в макете — номер в круге и линия со стрелкой). Карточка — кнопка: картинка, название,
 * короткий текст; при наведении мыши (на компьютере) или по нажатию вместо картинки и короткого
 * текста показывается подробный. Итоговый этап — фиолетовая карточка, в прямоугольнике «Результат».
 * До 5 этапов — в один ряд, 6 и 7 — в два (3 и 4 колонки); уже 1100 px — 3 колонки, 760 px — 2,
 * 480 px — одна: картинка узкой полосой и видна всегда, по нажатию короткий текст сменяется
 * подробным (правки заказчика). Без JavaScript подробный текст виден сразу.
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
		return Blocks::image(
			(int) ( $row['image'] ?? 0 ),
			'medium_large',
			array(
				'class'   => 'b-stages__img',
				'loading' => 'lazy',
				'sizes'   => '(max-width: 480px) 100vw, (max-width: 1100px) 33vw, 240px',
			),
			(string) ( $row['alt'] ?? '' ),
			! empty( $row['decorative'] )
		);
	}
	$file = __DIR__ . '/illustrations/' . sanitize_key( $picture ) . '.svg';
	return is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- файл темы.
};
?>
<section class="b-stages trust-bg-white"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
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

		<?php
		if ( $is_preview ) {
			// Своя картинка этапа без описания — предупреждение в редакторе (ТЗ, п. 8.1).
			foreach ( $trust_stages as $trust_row ) {
				if ( 'image' === ( $trust_row['picture'] ?? '' ) && Blocks::image_alt_missing( (int) ( $trust_row['image'] ?? 0 ), (string) ( $trust_row['alt'] ?? '' ), ! empty( $trust_row['decorative'] ) ) ) {
					/* translators: %s: название этапа. */
					echo Blocks::editor_warning( sprintf( __( 'У картинки этапа «%s» нет описания (alt). Заполните поле «Alt» или отметьте «Декоративная картинка».', 'trust' ), $trust_row['title'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- текст экранирован в editor_warning().
				}
			}
		}
		?>
		<?php if ( $trust_stages ) : ?>
			<ol class="b-stages__list b-stages__list--<?php echo esc_attr( (string) $trust_cols ); ?>">
				<?php foreach ( $trust_stages as $trust_i => $trust_row ) : ?>
					<?php
					$trust_result  = ! empty( $trust_row['result'] );
					$trust_pic     = $trust_picture( $trust_row );
					$trust_classes = 'b-stages__item' . ( $trust_result ? ' b-stages__item--result' : '' );
					?>
					<li class="<?php echo esc_attr( $trust_classes ); ?>">
						<div class="b-stages__top" aria-hidden="true">
							<span class="b-stages__num"><?php echo esc_html( str_pad( (string) ( $trust_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<?php if ( $trust_result ) : ?>
								<span class="b-stages__label"><?php esc_html_e( 'Результат', 'trust' ); ?></span>
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
					</li>
				<?php endforeach; ?>
			</ol>
		<?php elseif ( $is_preview ) : ?>
			<p class="b-stages__empty"><?php esc_html_e( 'Добавьте этапы в настройках блока.', 'trust' ); ?></p>
		<?php endif; ?>
	</div>
</section>
