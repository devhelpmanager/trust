<?php
/**
 * Блок «Что говорят клиенты» (макет o-nas/otzyvy.html, секция «Отзывы клиентов»).
 *
 * Белая секция: шапка и справа кнопка «Оставить отзыв»; кнопки услуг с числом отзывов («Все»
 * первая); карточки в 2 колонки (уже ~1000 px — в одну): автор с фото или силуэтом, должность и
 * компания, оценка звёздами, услуга, текст, результаты, через черту — дата, «Оригинал письма» /
 * «Оригинал отзыва», «Кейс проекта». Под списком — «Показать ещё N».
 *
 * Фильтр и «Показать ещё» — без перезагрузки: view.js берёт карточки из REST
 * (Trust\Reviews_List, GET /wp-json/trust/v1/reviews). Без JavaScript кнопок услуг нет, а
 * «Показать ещё» открывает страницу со всеми отзывами (?reviews=all).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;
use Trust\Reviews_List;

defined( 'ABSPATH' ) || exit;

$trust_per = (int) get_field( 'per_page' );
$trust_per = $trust_per >= 2 ? min( Reviews_List::MAX_PER, $trust_per ) : 6;
// Без JavaScript «Показать ещё» ведёт на ?reviews=all — тогда сразу все отзывы.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- только выбор числа карточек.
$trust_all    = ! $is_preview && isset( $_GET['reviews'] ) && 'all' === sanitize_key( wp_unslash( $_GET['reviews'] ) );
$trust_page   = Reviews_List::query( '', 0, $trust_all ? 200 : ( $is_preview ? 2 : $trust_per ) );
$trust_counts = Reviews_List::counts();
$trust_rest   = $trust_page['total'] - count( $trust_page['ids'] );
$trust_filter = false !== get_field( 'filter' ) && count( $trust_counts['services'] ) > 1;
$trust_more   = Blocks::text( 'more_label', __( 'Показать ещё', 'trust' ) );
$trust_btn    = get_field( 'button_label' );
$trust_btn    = null === $trust_btn ? __( 'Оставить отзыв', 'trust' ) : trim( (string) $trust_btn );
$trust_href   = trim( (string) get_field( 'button_url' ) );
$trust_href   = '' !== $trust_href ? $trust_href : '#leave-review';
$trust_anchor = Blocks::anchor( get_field( 'anchor' ) );
$trust_anchor = '' !== $trust_anchor ? $trust_anchor : 'reviews';
$trust_uid    = Blocks::uid( $block, 'reviews' );
$trust_title  = Blocks::uid( $block, 'title' );

if ( ! $trust_page['ids'] && ! $is_preview ) {
	return;
}
?>
<section class="b-rlist trust-bg-white" id="<?php echo esc_attr( $trust_anchor ); ?>"<?php echo '' !== trim( (string) get_field( 'title' ) ) ? ' aria-labelledby="' . esc_attr( $trust_title ) . '"' : ''; ?>
	data-reviews-list data-endpoint="<?php echo esc_url( rest_url( 'trust/v1/reviews' ) ); ?>" data-per="<?php echo esc_attr( (string) $trust_per ); ?>" data-total="<?php echo esc_attr( (string) $trust_page['total'] ); ?>">
	<div class="b-rlist__inner">
		<div class="b-rlist__top-row">
			<?php Blocks::section_head( 'b-rlist__head', $trust_title ); ?>
			<?php if ( '' !== $trust_btn ) : ?>
				<a class="b-rlist__leave" href="<?php echo esc_url( $trust_href ); ?>"><?php echo esc_html( $trust_btn ); ?></a>
			<?php endif; ?>
		</div>
		<?php if ( $trust_filter ) : ?>
			<div class="filter-chips" role="group" aria-label="<?php esc_attr_e( 'Фильтр по услугам', 'trust' ); ?>" aria-controls="<?php echo esc_attr( $trust_uid ); ?>">
				<button type="button" class="filter-chip" data-service="" aria-pressed="true"><?php echo esc_html( Blocks::text( 'all_label', __( 'Все', 'trust' ) ) ); ?><span class="filter-chip__count"><?php echo esc_html( (string) $trust_counts['total'] ); ?></span></button>
				<?php foreach ( $trust_counts['services'] as $trust_slug => $trust_service ) : ?>
					<button type="button" class="filter-chip" data-service="<?php echo esc_attr( $trust_slug ); ?>" aria-pressed="false"><?php echo esc_html( $trust_service['name'] ); ?><span class="filter-chip__count"><?php echo esc_html( (string) $trust_service['count'] ); ?></span></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( ! $trust_page['ids'] ) : ?>
			<?php echo Blocks::editor_warning( __( 'Нет опубликованных текстовых отзывов: добавьте их в разделе «Отзывы».', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- текст экранирован в editor_warning(). ?>
		<?php else : ?>
			<ul class="b-rlist__list" id="<?php echo esc_attr( $trust_uid ); ?>">
				<?php
				foreach ( $trust_page['ids'] as $trust_id ) {
					echo Reviews_List::card( $trust_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- собрано с экранированием в card().
				}
				?>
			</ul>
		<?php endif; ?>
		<p class="screen-reader-text" aria-live="polite" data-reviews-status></p>
		<?php if ( $trust_rest > 0 && ! $is_preview ) : ?>
			<a class="b-rlist__more" href="<?php echo esc_url( add_query_arg( 'reviews', 'all', (string) get_permalink() ) . '#' . $trust_anchor ); ?>" data-reviews-more data-label="<?php echo esc_attr( $trust_more ); ?>">
				<span data-reviews-more-text><?php echo esc_html( $trust_more . ' ' . $trust_rest ); ?></span>
				<?php
				Icons::render(
					'chevron-down',
					array(
						'size'         => 18,
						'stroke_width' => 2.2,
					)
				);
				?>
			</a>
		<?php endif; ?>
	</div>
</section>
