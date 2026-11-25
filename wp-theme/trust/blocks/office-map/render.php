<?php
/**
 * Блок «Приезжайте в гости» (макет o-nas/kontakty.html, секция «Офис»).
 *
 * Слева — схема проезда: картинка из «Настроек сайта → Контакты» или схема-заглушка из макета с
 * меткой «[Бренд] · офис». Если задан код интерактивной карты — кнопка «Показать карту»: карта
 * (сторонний сервис) загружается только по нажатию (view.js). Справа — надзаголовок, H2, адрес,
 * «Метро», «На машине», «Часы работы», «Пропуск» (пустые не выводятся) и кнопки «Открыть в
 * Яндекс Картах», «Открыть в 2ГИС» (новая вкладка). Все данные — «Настройки сайта → Контакты».
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Contacts;
use Trust\Icons;
use Trust\Settings;

defined( 'ABSPATH' ) || exit;

$trust_title_id = Blocks::uid( $block, 'title' );
$trust_address  = Contacts::address_line();
$trust_items    = array_filter(
	array(
		array( 'metro', Blocks::text( 'transport_label', __( 'Метро', 'trust' ) ), trim( (string) Settings::get( 'directions_transport', '' ) ) ),
		array( 'car', Blocks::text( 'parking_label', __( 'На машине', 'trust' ) ), trim( (string) Settings::get( 'directions_parking', '' ) ) ),
		array( 'clock', Blocks::text( 'hours_label', __( 'Часы работы', 'trust' ) ), Contacts::hours() ),
		array( 'pass', Blocks::text( 'note_label', __( 'Пропуск', 'trust' ) ), trim( (string) Settings::get( 'directions_note', '' ) ) ),
	),
	static fn( array $item ): bool => '' !== $item[2]
);
$trust_links    = array_filter(
	array(
		array( (string) Settings::get( 'yandex_maps_url', '' ), Blocks::text( 'yandex_label', __( 'Открыть в Яндекс Картах', 'trust' ) ), 'b-omap__link--wide' ),
		array( (string) Settings::get( 'gis_url', '' ), Blocks::text( 'gis_label', __( 'Открыть в 2ГИС', 'trust' ) ), '' ),
	),
	static fn( array $link ): bool => '' !== trim( $link[0] ) && '' !== $link[1]
);
$trust_image    = (int) Settings::get( 'map_image', 0 );
$trust_image    = $trust_image ? Blocks::image(
	$trust_image,
	'large',
	array(
		'class'   => 'b-omap__image',
		'loading' => 'lazy',
		'sizes'   => '(max-width: 960px) 100vw, 720px',
	)
) : '';
$trust_embed    = $is_preview ? '' : trim( (string) Settings::get( 'map_embed', '' ) );
$trust_marker   = str_replace( '{brand}', Settings::brand(), Blocks::text( 'marker', __( '{brand} · офис', 'trust' ) ) );
?>
<section class="b-omap trust-bg-white"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-omap__inner">
		<div class="b-omap__map" data-office-map<?php echo '' === $trust_image ? ' role="img" aria-label="' . esc_attr__( 'Схема проезда к офису', 'trust' ) . '"' : ''; ?>>
			<?php if ( '' !== $trust_image ) : ?>
				<?php echo $trust_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
			<?php else : ?>
				<?php echo file_get_contents( __DIR__ . '/scheme.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- постоянная картинка темы. ?>
				<span class="b-omap__marker" aria-hidden="true">
					<span class="b-omap__marker-label"><?php echo esc_html( $trust_marker ); ?></span>
					<span class="b-omap__pin"><span></span></span>
				</span>
			<?php endif; ?>
			<?php if ( '' !== $trust_embed ) : ?>
				<button type="button" class="btn btn--sm b-omap__show" data-office-map-show>
					<?php
					Icons::render(
						'eye',
						array(
							'size'         => 18,
							'stroke_width' => 2,
						)
					);
					?>
					<?php echo esc_html( Blocks::text( 'show_map', __( 'Показать карту', 'trust' ) ) ); ?>
				</button>
				<?php // Код карты — из настроек (поле доступно только администратору); вставляется по нажатию. ?>
				<template data-office-map-embed><?php echo $trust_embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- код карты администратора, см. выше. ?></template>
			<?php endif; ?>
		</div>

		<div class="b-omap__info">
			<?php Blocks::section_head( 'b-omap__head', $trust_title_id ); ?>
			<?php if ( '' !== $trust_address ) : ?>
				<p class="b-omap__address"><?php echo esc_html( $trust_address ); ?></p>
			<?php endif; ?>
			<?php if ( $trust_items ) : ?>
				<ul class="b-omap__list">
					<?php foreach ( $trust_items as list( $trust_icon, $trust_label, $trust_value ) ) : ?>
						<li>
							<span class="b-omap__icon" aria-hidden="true">
								<?php
								if ( 'metro' === $trust_icon ) {
									echo 'М';
								} else {
									Icons::render(
										$trust_icon,
										array(
											'size'         => 18,
											'stroke_width' => 2,
										)
									);
								}
								?>
							</span>
							<span><strong><?php echo esc_html( $trust_label ); ?></strong><?php echo esc_html( $trust_value ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $trust_links ) : ?>
				<div class="b-omap__links">
					<?php foreach ( $trust_links as list( $trust_url, $trust_label, $trust_class ) ) : ?>
						<a class="btn btn--outline btn--sm b-omap__link <?php echo esc_attr( $trust_class ); ?>" href="<?php echo esc_url( $trust_url ); ?>" target="_blank" rel="noopener"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>>
							<?php echo esc_html( $trust_label ); ?>
							<span class="screen-reader-text"><?php esc_html_e( '(откроется в новой вкладке)', 'trust' ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
