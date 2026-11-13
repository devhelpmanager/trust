<?php
/**
 * Блок «Что включает услуга» (макет holst-blokov/ServiceIncludes, seo-bloki/04-chto-vklyuchaet-usluga.html).
 *
 * Белая плашка со списком работ. В строке: номер 01, 02…, иконка, название (H3) и круглая
 * кнопка со стрелкой; по нажатию раскрываются описание, цена, ссылка на страницу работы
 * и список «Что проверяем». Открытыми могут быть несколько работ. На сайте все закрыты;
 * в редакторе и без JavaScript — раскрыты (чтобы содержимое было видно).
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
		static fn( $row ) => ! empty( $row['title'] ) && ! empty( $row['description'] )
	)
);
$trust_title    = (string) get_field( 'title' );
$trust_eyebrow  = (string) get_field( 'eyebrow' );
$trust_lead     = (string) get_field( 'lead' );
$trust_checks   = get_field( 'checks_label' );
$trust_checks   = null === $trust_checks ? __( 'Что проверяем', 'trust' ) : (string) $trust_checks; // Поле не сохранялось — подпись по умолчанию, очищено — без подписи.
$trust_more     = (string) get_field( 'more_label' );
$trust_more     = '' !== $trust_more ? $trust_more : __( 'Подробнее об услуге', 'trust' );
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_open     = ! empty( $is_preview );
?>
<section class="b-includes trust-bg-lavender"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-includes__inner">
		<?php if ( $trust_eyebrow || $trust_title || $trust_lead ) : ?>
			<div class="section-top">
				<div class="b-includes__head">
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
				<?php echo Blocks::head_art( 'service-includes' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- рисунок темы. ?>
			</div>
		<?php endif; ?>

		<?php if ( $trust_items ) : ?>
			<ol class="b-includes__list">
				<?php foreach ( $trust_items as $trust_i => $trust_row ) : ?>
					<?php
					$trust_number   = str_pad( (string) ( $trust_i + 1 ), 2, '0', STR_PAD_LEFT );
					$trust_panel_id = Blocks::uid( $block, 'work-' . ( $trust_i + 1 ) );
					$trust_icon     = Icons::svg(
						(string) ( $trust_row['icon'] ?? '' ),
						array(
							'size'         => 22,
							'stroke_width' => 1.7,
						)
					);
					$trust_icon     = '' !== $trust_icon ? $trust_icon : Icons::svg(
						'check',
						array(
							'size'         => 22,
							'stroke_width' => 1.7,
						)
					);
					$trust_link     = is_array( $trust_row['link'] ?? null ) && ! empty( $trust_row['link']['url'] ) ? $trust_row['link'] : null;
					$trust_lines    = array_values(
						array_filter(
							array_map( static fn( $line ) => trim( (string) ( $line['text'] ?? '' ) ), (array) ( $trust_row['checks'] ?? array() ) ),
							'strlen'
						)
					);
					$trust_price    = trim( (string) ( $trust_row['price'] ?? '' ) );
					?>
					<li class="b-includes__item<?php echo $trust_open ? ' is-open' : ''; ?>">
						<h3 class="b-includes__h">
							<button type="button" class="b-includes__toggle" aria-expanded="<?php echo $trust_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $trust_panel_id ); ?>">
								<span class="b-includes__mark">
									<span class="b-includes__num"><?php echo esc_html( $trust_number ); ?></span>
									<span class="b-includes__icon" aria-hidden="true"><?php echo $trust_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG из спрайта темы. ?></span>
								</span>
								<span class="b-includes__title"><?php echo esc_html( $trust_row['title'] ); ?></span>
								<span class="b-includes__arrow" aria-hidden="true"><?php Icons::render( 'chevron-down', array( 'size' => 18 ) ); ?></span>
							</button>
						</h3>
						<div class="b-includes__panel" id="<?php echo esc_attr( $trust_panel_id ); ?>"<?php echo $trust_open ? '' : ' hidden'; ?>>
							<div class="b-includes__main">
								<p class="b-includes__desc"><?php echo esc_html( $trust_row['description'] ); ?></p>
								<?php if ( $trust_price || $trust_link ) : ?>
									<div class="b-includes__row">
										<?php if ( $trust_price ) : ?>
											<span class="b-includes__price"><?php echo esc_html( $trust_price ); ?></span>
										<?php endif; ?>
										<?php if ( $trust_link ) : ?>
											<a class="b-includes__more" href="<?php echo esc_url( $trust_link['url'] ); ?>"<?php echo ! empty( $trust_link['target'] ) ? ' target="_blank" rel="noopener"' : ''; ?>>
												<?php echo esc_html( '' !== trim( (string) ( $trust_link['title'] ?? '' ) ) ? $trust_link['title'] : $trust_more ); ?>
												<?php Icons::render( 'arrow-right', array( 'size' => 16 ) ); ?>
											</a>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
							<?php if ( $trust_lines ) : ?>
								<div class="b-includes__checks">
									<?php if ( '' !== $trust_checks ) : ?>
										<span class="b-includes__checks-title"><?php echo esc_html( $trust_checks ); ?></span>
									<?php endif; ?>
									<ul>
										<?php foreach ( $trust_lines as $trust_line ) : ?>
											<li><?php echo esc_html( $trust_line ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php elseif ( $is_preview ) : ?>
			<p class="b-includes__empty"><?php esc_html_e( 'Добавьте работы в настройках блока.', 'trust' ); ?></p>
		<?php endif; ?>
	</div>
</section>
