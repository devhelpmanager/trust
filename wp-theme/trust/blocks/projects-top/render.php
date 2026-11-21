<?php
/**
 * Блок «Наши проекты в топе» (макет holst-blokov/Projects, seo-bloki/12-proekty-v-tope.html).
 *
 * Фиолетовая секция: надзаголовок, H2, вводный текст; белые карточки кейсов по 3 в ряд
 * (колонка от 320 px): скриншот сайта 16:10, чем занимается клиент, название, таблица
 * «Запрос — Позиция в Я — Позиция в G» (позиции 1–3 — фиолетовые, дальше — светлые),
 * регион и дата проверки. Вся карточка — ссылка на страницу кейса (правка заказчика; ссылка —
 * название проекта, её область растянута на карточку). Кейсы — последние с позициями или
 * выбранные вручную; кейс без позиций не выводится. Нет кейсов — блока на сайте нет (в редакторе —
 * подсказка). Заголовок и вводный текст — во всю ширину блока (правка заказчика).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Cases;

defined( 'ABSPATH' ) || exit;

$trust_manual = 'manual' === get_field( 'source' );
$trust_count  = (int) get_field( 'count' );
$trust_count  = $trust_count > 0 ? min( 6, $trust_count ) : 3;

if ( $trust_manual ) {
	$trust_ids = array_map( 'intval', (array) get_field( 'cases' ) );
	$trust_ids = $trust_ids ? get_posts(
		array(
			'post_type'      => 'case',
			'post_status'    => 'publish',
			'post__in'       => $trust_ids,
			'orderby'        => 'post__in',
			'posts_per_page' => count( $trust_ids ),
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	) : array();
} else {
	$trust_ids = get_posts(
		array(
			'post_type'      => 'case',
			'post_status'    => 'publish',
			'posts_per_page' => $trust_count,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- кейсы с позициями: число строк повторителя.
				array(
					'key'     => 'trust_positions',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		)
	);
}

$trust_cards = array();
foreach ( $trust_ids as $trust_id ) {
	$trust_rows = Cases::positions( (int) $trust_id );
	if ( $trust_rows ) {
		$trust_cards[ (int) $trust_id ] = $trust_rows;
	}
}

if ( ! $trust_cards && ! $is_preview ) {
	return;
}

$trust_title    = (string) get_field( 'title' );
$trust_eyebrow  = (string) get_field( 'eyebrow' );
$trust_lead     = (string) get_field( 'lead' );
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_q_label  = Blocks::text( 'query_label', __( 'Запрос', 'trust' ) );
$trust_p_label  = Blocks::text( 'position_label', __( 'Позиция в', 'trust' ) );
$trust_d_label  = Blocks::text( 'date_label', __( 'данные на', 'trust' ) );
$trust_engines  = array(
	'yandex' => array( 'Я', __( 'Яндекс', 'trust' ) ),
	'google' => array( 'G', 'Google' ),
);
?>
<section class="b-projects section--violet trust-bg-violet"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-projects__inner">
		<?php if ( $trust_eyebrow || $trust_title || $trust_lead ) : ?>
			<div class="section-head b-projects__head">
				<?php if ( $trust_eyebrow ) : ?>
					<span class="eyebrow"><?php echo esc_html( $trust_eyebrow ); ?></span>
				<?php endif; ?>
				<?php if ( $trust_title ) : ?>
					<h2 class="section-title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
				<?php endif; ?>
				<?php if ( '' !== trim( $trust_lead ) ) : ?>
					<p class="section-lead section-lead--lg"><?php echo esc_html( $trust_lead ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $trust_cards ) : ?>
			<ul class="b-projects__list">
				<?php foreach ( $trust_cards as $trust_id => $trust_rows ) : ?>
					<?php
					$trust_name   = Cases::name( $trust_id );
					$trust_niche  = Cases::niche( $trust_id );
					$trust_shot   = Cases::screenshot( $trust_id );
					$trust_region = trim( (string) get_post_meta( $trust_id, 'trust_positions_region', true ) );
					$trust_region = '' !== $trust_region ? $trust_region : trim( (string) get_post_meta( $trust_id, 'trust_region', true ) );
					$trust_date   = (string) get_post_meta( $trust_id, 'trust_positions_date', true );
					$trust_time   = $trust_date ? strtotime( $trust_date ) : false;
					$trust_meta   = array_filter(
						array(
							$trust_region,
							$trust_time ? $trust_d_label . ' ' . wp_date( 'd.m.Y', $trust_time, new DateTimeZone( 'UTC' ) ) : '',
						)
					);
					?>
					<li class="b-projects__card">
						<?php if ( $trust_shot ) : ?>
							<?php
							$trust_img = Blocks::image(
								$trust_shot,
								'large', // Без обрезки 16:9: скриншот 16:10 показывается от верха.
								array(
									'class'   => 'b-projects__img',
									'loading' => 'lazy',
									// Колонка от 320 px: одна — почти вся ширина, две — половина, три — 384 px.
									'sizes'   => '(max-width: 760px) calc(100vw - 40px), (max-width: 1100px) 50vw, 384px',
								),
								'' === trim( (string) get_post_meta( $trust_shot, '_wp_attachment_image_alt', true ) )
									/* translators: %s: название проекта. */
									? sprintf( __( 'Сайт проекта «%s»', 'trust' ), $trust_name )
									: ''
							);
							echo $trust_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка wp_get_attachment_image().
							?>
						<?php else : ?>
							<span class="b-projects__img b-projects__img--empty" aria-hidden="true"></span>
						<?php endif; ?>
						<div class="b-projects__body">
							<div class="b-projects__titles">
								<?php if ( '' !== $trust_niche ) : ?>
									<span class="b-projects__niche"><?php echo esc_html( $trust_niche ); ?></span>
								<?php endif; ?>
								<h3 class="b-projects__name"><a class="b-projects__link" href="<?php echo esc_url( (string) get_permalink( $trust_id ) ); ?>"><?php echo esc_html( $trust_name ); ?></a></h3>
							</div>
							<table class="b-projects__table">
								<caption class="screen-reader-text">
									<?php
									/* translators: %s: название проекта. */
									echo esc_html( sprintf( __( 'Позиции сайта «%s» в поиске', 'trust' ), $trust_name ) );
									?>
								</caption>
								<thead>
									<tr>
										<th scope="col" class="b-projects__th-query"><?php echo esc_html( $trust_q_label ); ?></th>
										<?php foreach ( $trust_engines as $trust_engine => list( $trust_letter, $trust_engine_name ) ) : ?>
											<th scope="col" class="b-projects__th-pos">
												<span class="b-projects__th-label">
													<?php echo esc_html( $trust_p_label ); ?>
													<span class="b-projects__engine b-projects__engine--<?php echo esc_attr( $trust_engine ); ?>" role="img" aria-label="<?php echo esc_attr( $trust_engine_name ); ?>"><?php echo esc_html( $trust_letter ); ?></span>
												</span>
											</th>
										<?php endforeach; ?>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $trust_rows as $trust_row ) : ?>
										<tr>
											<td class="b-projects__query"><?php echo esc_html( $trust_row['query'] ); ?></td>
											<?php foreach ( array_keys( $trust_engines ) as $trust_engine ) : ?>
												<?php $trust_pos = (int) $trust_row[ $trust_engine ]; ?>
												<td class="b-projects__pos">
													<?php if ( $trust_pos > 0 ) : ?>
														<span class="b-projects__badge<?php echo $trust_pos <= 3 ? ' is-top' : ''; ?>"><?php echo esc_html( (string) $trust_pos ); ?></span>
													<?php else : ?>
														<span class="b-projects__none" aria-hidden="true">—</span><span class="screen-reader-text"><?php esc_html_e( 'нет данных', 'trust' ); ?></span>
													<?php endif; ?>
												</td>
											<?php endforeach; ?>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<?php if ( $trust_meta ) : ?>
								<span class="b-projects__meta"><?php echo esc_html( implode( ' · ', $trust_meta ) ); ?></span>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<?php echo Blocks::editor_warning( __( 'Нет кейсов с позициями в поиске. Заполните «Позиции в поиске» в кейсе (панель «Данные кейса») или выберите кейсы вручную.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка экранирована в Blocks::editor_warning(). ?>
		<?php endif; ?>
	</div>
</section>
