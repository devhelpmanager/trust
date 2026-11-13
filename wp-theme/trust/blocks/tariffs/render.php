<?php
/**
 * Блок «Выбор тарифа» (макет holst-blokov/Main → Tariffs, seo-bloki/01-tarify.html).
 *
 * Карточки тарифов в ряд (колонка от 320 px): название H3, плашка «Рекомендуем», кому подходит,
 * старая цена и скидка, цена и период, состав с отметками «включено / не включено», кнопка.
 * Рекомендуемый тариф — тёмная карточка. Тарифы с числовой ценой попадают в микроразметку
 * услуги (Offer / AggregateOffer, Trust\Schema\Graph::offer()).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Schema\Graph;

defined( 'ABSPATH' ) || exit;

$trust_tariffs  = array_values(
	array_filter(
		(array) get_field( 'tariffs' ),
		static fn( $row ) => ! empty( $row['name'] ) && ! empty( $row['price'] )
	)
);
$trust_title    = (string) get_field( 'title' );
$trust_eyebrow  = (string) get_field( 'eyebrow' );
$trust_lead     = (string) get_field( 'lead' );
$trust_badge    = get_field( 'badge_label' );
$trust_badge    = null === $trust_badge ? __( 'Рекомендуем', 'trust' ) : (string) $trust_badge;
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title_id = Blocks::uid( $block, 'title' );

/**
 * Отметка пункта состава: зелёная галочка или красный крестик в круге, с подписью для скринридеров.
 *
 * @param bool $included Включено ли.
 */
$trust_mark = static function ( bool $included ): string {
	return $included
		? '<svg class="b-tariffs__mark b-tariffs__mark--yes" width="22" height="22" viewBox="0 0 22 22" role="img" aria-label="' . esc_attr__( 'Включено', 'trust' ) . '" focusable="false"><circle cx="11" cy="11" r="11"/><path d="M6.5 11.3l3 3 6-6.3" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
		: '<svg class="b-tariffs__mark b-tariffs__mark--no" width="22" height="22" viewBox="0 0 22 22" role="img" aria-label="' . esc_attr__( 'Не включено', 'trust' ) . '" focusable="false"><circle cx="11" cy="11" r="11"/><path d="M7.8 7.8l6.4 6.4M14.2 7.8l-6.4 6.4" fill="none" stroke-width="2" stroke-linecap="round"/></svg>';
};
?>
<section class="b-tariffs trust-bg-lavender"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-tariffs__inner">
		<?php if ( $trust_eyebrow || $trust_title || $trust_lead ) : ?>
			<div class="section-top">
				<div class="b-tariffs__head">
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
				<?php echo Blocks::head_art( 'tariffs' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- рисунок темы. ?>
			</div>
		<?php endif; ?>

		<?php if ( $trust_tariffs ) : ?>
			<div class="b-tariffs__list">
				<?php foreach ( $trust_tariffs as $trust_row ) : ?>
					<?php
					$trust_rec    = ! empty( $trust_row['recommended'] );
					$trust_link   = is_array( $trust_row['button'] ?? null ) && ! empty( $trust_row['button']['url'] ) ? $trust_row['button'] : null;
					$trust_items  = array_values( array_filter( (array) ( $trust_row['items'] ?? array() ), static fn( $item ) => '' !== trim( (string) ( $item['text'] ?? '' ) ) ) );
					$trust_number = isset( $trust_row['price_value'] ) && '' !== (string) $trust_row['price_value'] ? (float) $trust_row['price_value'] : null;
					if ( ! $is_preview && null !== $trust_number ) {
						Graph::offer( (string) $trust_row['name'], $trust_number, (string) ( $trust_row['who'] ?? '' ) );
					}
					?>
					<article class="b-tariffs__card<?php echo $trust_rec ? ' b-tariffs__card--rec' : ''; ?>">
						<div class="b-tariffs__about">
							<div class="b-tariffs__name">
								<h3 class="b-tariffs__title"><?php echo esc_html( $trust_row['name'] ); ?></h3>
								<?php if ( $trust_rec && '' !== $trust_badge ) : ?>
									<span class="b-tariffs__badge"><?php echo esc_html( $trust_badge ); ?></span>
								<?php endif; ?>
							</div>
							<?php if ( ! empty( $trust_row['who'] ) ) : ?>
								<p class="b-tariffs__who"><?php echo esc_html( $trust_row['who'] ); ?></p>
							<?php endif; ?>
						</div>

						<div class="b-tariffs__prices">
							<?php if ( ! empty( $trust_row['old_price'] ) || ! empty( $trust_row['discount'] ) ) : ?>
								<div class="b-tariffs__old">
									<?php if ( ! empty( $trust_row['old_price'] ) ) : ?>
										<s><span class="screen-reader-text"><?php esc_html_e( 'Старая цена:', 'trust' ); ?> </span><?php echo esc_html( $trust_row['old_price'] ); ?></s>
									<?php endif; ?>
									<?php if ( ! empty( $trust_row['discount'] ) ) : ?>
										<span class="b-tariffs__discount"><?php echo esc_html( $trust_row['discount'] ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							<div class="b-tariffs__now">
								<span class="b-tariffs__price"><?php echo esc_html( $trust_row['price'] ); ?></span>
								<?php if ( ! empty( $trust_row['period'] ) ) : ?>
									<span class="b-tariffs__period"><?php echo esc_html( $trust_row['period'] ); ?></span>
								<?php endif; ?>
							</div>
						</div>

						<?php if ( $trust_items ) : ?>
							<ul class="b-tariffs__items">
								<?php foreach ( $trust_items as $trust_item ) : ?>
									<?php $trust_yes = ! array_key_exists( 'included', $trust_item ) || ! empty( $trust_item['included'] ); ?>
									<li class="b-tariffs__item<?php echo $trust_yes ? '' : ' b-tariffs__item--no'; ?>">
										<?php echo $trust_mark( $trust_yes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG темы, подписи экранированы. ?>
										<span><?php echo esc_html( $trust_item['text'] ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>

						<?php if ( $trust_link ) : ?>
							<?php
							/* translators: %s: название тарифа. */
							$trust_label = '' !== trim( (string) ( $trust_link['title'] ?? '' ) ) ? $trust_link['title'] : sprintf( __( 'Выбрать «%s»', 'trust' ), $trust_row['name'] );
							?>
							<a class="b-tariffs__btn" href="<?php echo esc_url( $trust_link['url'] ); ?>"<?php echo ! empty( $trust_link['target'] ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $trust_label ); ?></a>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		<?php elseif ( $is_preview ) : ?>
			<p class="b-tariffs__empty"><?php esc_html_e( 'Добавьте тарифы в настройках блока.', 'trust' ); ?></p>
		<?php endif; ?>
	</div>
</section>
