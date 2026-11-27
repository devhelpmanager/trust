<?php
/**
 * Блок «Кейсы» (макет portfolio-blog-404/portfolio.html, секция «Кейсы»).
 *
 * Лавандовая панель фильтров: «Услуга» — кнопки с числом кейсов («Все» первая; на телефоне
 * листаются одной строкой), «Отрасль» — список. Фильтры работают вместе, числа на кнопках
 * пересчитываются по выбранной отрасли. Под панелью — «Все проекты · N кейсов» / «Найдено: N»
 * (aria-live) и «Сбросить фильтры». Пока фильтры не выбраны — главный кейс крупной карточкой
 * (Cases_Grid::featured()), ниже сетка карточек 3 / 2 / 1 и «Показать ещё кейсы» с «Показано N
 * из M». Ничего не подошло — «Таких кейсов пока нет» и «Показать все кейсы».
 *
 * Карточки отдаёт REST (Trust\Cases_Grid, GET /wp-json/trust/v1/cases) — view.js меняет список без
 * перезагрузки и пишет выбор в адрес (?service=…&industry=…; с таким адресом блок сразу
 * открывается отфильтрованным). Без JavaScript фильтров нет, а «Показать ещё» открывает
 * страницу со всеми кейсами (?cases=all).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Cases_Grid;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_index = Cases_Grid::index();
if ( ! $trust_index['cases'] ) {
	if ( $is_preview ) {
		echo Blocks::editor_warning( __( 'Кейсов пока нет: добавьте их в разделе «Кейсы».', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning().
	}
	return;
}

$trust_per = (int) get_field( 'per_page' );
$trust_per = $trust_per >= 3 ? min( Cases_Grid::MAX_PER, $trust_per ) : 9;
$trust_per = $is_preview ? min( 4, $trust_per ) : $trust_per;

// Выбор фильтров из адреса (?service=seo&industry=med) — только существующие услуги и отрасли.
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- только выбор фильтров.
$trust_get      = static fn( string $key ): string => isset( $_GET[ $key ] ) ? sanitize_title( wp_unslash( $_GET[ $key ] ) ) : '';
$trust_service  = $is_preview ? '' : $trust_get( 'service' );
$trust_service  = isset( $trust_index['services'][ $trust_service ] ) ? $trust_service : '';
$trust_industry = $is_preview ? '' : $trust_get( 'industry' );
$trust_industry = isset( $trust_index['industries'][ $trust_industry ] ) ? $trust_industry : '';
// Без JavaScript «Показать ещё» ведёт на ?cases=all — тогда сразу все кейсы.
$trust_all = ! $is_preview && isset( $_GET['cases'] ) && 'all' === sanitize_key( wp_unslash( $_GET['cases'] ) );
// phpcs:enable

$trust_filtered = '' !== $trust_service || '' !== $trust_industry;
$trust_featured = Cases_Grid::featured();
$trust_lead     = $trust_featured && ! $trust_filtered;
$trust_page     = Cases_Grid::query( $trust_service, $trust_industry, 0, $trust_all ? 200 : ( $trust_lead ? $trust_per - 1 : $trust_per ), $trust_lead ? array( $trust_featured ) : array() );
$trust_total    = $trust_page['total'] + ( $trust_lead ? 1 : 0 );
$trust_shown    = count( $trust_page['ids'] ) + ( $trust_lead ? 1 : 0 );

// Микроразметка: показанные кейсы — ItemList страницы (главный первым).
if ( ! $is_preview ) {
	foreach ( array_merge( $trust_lead ? array( $trust_featured ) : array(), $trust_page['ids'] ) as $trust_id ) {
		Trust\Schema\Graph::item( (string) get_permalink( $trust_id ), get_the_title( $trust_id ) );
	}
}

// Сколько кейсов подходит под услугу и отрасль (пусто — любая): числа на кнопках услуг (по
// выбранной отрасли) и у отраслей в списке (по выбранной услуге).
$trust_count = static function ( string $service, string $industry ) use ( $trust_index ): int {
	$n = 0;
	foreach ( $trust_index['cases'] as $trust_case ) {
		$n += ( '' === $industry || in_array( $industry, $trust_case['n'], true ) ) && ( '' === $service || in_array( $service, $trust_case['s'], true ) ) ? 1 : 0;
	}
	return $n;
};

$trust_anchor = Blocks::anchor( get_field( 'anchor' ) );
$trust_anchor = '' !== $trust_anchor ? $trust_anchor : 'cases';
$trust_uid    = Blocks::uid( $block, 'cases' );
$trust_all_n  = count( $trust_index['cases'] );
/* translators: %s: «9 кейсов». */
$trust_all_label = sprintf( __( 'Все проекты · %s', 'trust' ), '%n%' );
/* translators: %s: «3 кейса». */
$trust_some_label = sprintf( __( 'Найдено: %s', 'trust' ), '%n%' );
/* translators: 1: сколько показано, 2: сколько всего. */
$trust_shown_label = sprintf( __( 'Показано %1$s из %2$s', 'trust' ), '%shown%', '%total%' );
$trust_found       = str_replace( '%n%', Cases_Grid::word( $trust_filtered ? $trust_total : $trust_all_n ), $trust_filtered ? $trust_some_label : $trust_all_label );
$trust_cases       = array_map(
	static fn( int $id, array $c ): array => array(
		'id' => $id,
		's'  => $c['s'],
		'n'  => $c['n'],
	),
	array_keys( $trust_index['cases'] ),
	array_values( $trust_index['cases'] )
);
?>
<section class="b-cases trust-bg-white" id="<?php echo esc_attr( $trust_anchor ); ?>" aria-labelledby="<?php echo esc_attr( $trust_uid ); ?>-title"
	data-cases data-endpoint="<?php echo esc_url( rest_url( 'trust/v1/cases' ) ); ?>" data-per="<?php echo esc_attr( (string) $trust_per ); ?>" data-featured="<?php echo esc_attr( (string) $trust_featured ); ?>"
	data-service="<?php echo esc_attr( $trust_service ); ?>" data-industry="<?php echo esc_attr( $trust_industry ); ?>"
	data-index="<?php echo esc_attr( (string) wp_json_encode( $trust_cases ) ); ?>"
	data-all-label="<?php echo esc_attr( $trust_all_label ); ?>" data-some-label="<?php echo esc_attr( $trust_some_label ); ?>" data-shown-label="<?php echo esc_attr( $trust_shown_label ); ?>"
	data-words="<?php echo esc_attr( (string) wp_json_encode( array( __( 'кейс', 'trust' ), __( 'кейса', 'trust' ), __( 'кейсов', 'trust' ) ) ) ); ?>">
	<div class="b-cases__inner">
		<h2 class="screen-reader-text" id="<?php echo esc_attr( $trust_uid ); ?>-title"><?php echo esc_html( Blocks::text( 'title', __( 'Кейсы', 'trust' ) ) ); ?></h2>

		<div class="b-cases__filters">
			<div class="b-cases__services">
				<span class="b-cases__label" id="<?php echo esc_attr( $trust_uid ); ?>-svc"><?php echo esc_html( Blocks::text( 'service_label', __( 'Услуга', 'trust' ) ) ); ?></span>
				<div class="filter-chips b-cases__chips" role="group" aria-labelledby="<?php echo esc_attr( $trust_uid ); ?>-svc" aria-controls="<?php echo esc_attr( $trust_uid ); ?>-list">
					<button type="button" class="filter-chip" data-service="" aria-pressed="<?php echo '' === $trust_service ? 'true' : 'false'; ?>"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>><?php echo esc_html( Blocks::text( 'all_label', __( 'Все', 'trust' ) ) ); ?><span class="filter-chip__count" data-count><?php echo esc_html( (string) $trust_count( '', $trust_industry ) ); ?></span></button>
					<?php foreach ( $trust_index['services'] as $trust_slug => $trust_name ) : ?>
						<button type="button" class="filter-chip" data-service="<?php echo esc_attr( $trust_slug ); ?>" aria-pressed="<?php echo $trust_slug === $trust_service ? 'true' : 'false'; ?>"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>><?php echo esc_html( $trust_name ); ?><span class="filter-chip__count" data-count><?php echo esc_html( (string) $trust_count( $trust_slug, $trust_industry ) ); ?></span></button>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="b-cases__industry">
				<label class="b-cases__label" for="<?php echo esc_attr( $trust_uid ); ?>-niche"><?php echo esc_html( Blocks::text( 'industry_label', __( 'Отрасль', 'trust' ) ) ); ?></label>
				<select class="b-cases__select" id="<?php echo esc_attr( $trust_uid ); ?>-niche" data-industry-select data-dropdown<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>>
					<option value="" data-count="<?php echo esc_attr( (string) $trust_count( $trust_service, '' ) ); ?>"><?php echo esc_html( Blocks::text( 'industry_all', __( 'Все отрасли', 'trust' ) ) ); ?></option>
					<?php foreach ( $trust_index['industries'] as $trust_slug => $trust_name ) : ?>
						<option value="<?php echo esc_attr( $trust_slug ); ?>" data-count="<?php echo esc_attr( (string) $trust_count( $trust_service, $trust_slug ) ); ?>"<?php selected( $trust_slug, $trust_industry ); ?>><?php echo esc_html( $trust_name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="b-cases__bar">
			<p class="b-cases__found" aria-live="polite" data-cases-found><?php echo esc_html( $trust_found ); ?></p>
			<button type="button" class="b-cases__reset" data-cases-reset<?php echo $trust_filtered ? '' : ' hidden'; ?>>
				<?php
				Icons::render(
					'close',
					array(
						'size'         => 16,
						'stroke_width' => 2.2,
					)
				);
				?>
				<?php esc_html_e( 'Сбросить фильтры', 'trust' ); ?>
			</button>
		</div>

		<?php if ( $trust_featured ) : ?>
			<div class="b-cases__lead" data-cases-lead<?php echo $trust_lead ? '' : ' hidden'; ?>>
				<?php echo Cases_Grid::featured_card( $trust_featured, Blocks::text( 'badge', __( 'Главный кейс', 'trust' ) ), Blocks::text( 'more', __( 'Смотреть кейс', 'trust' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в featured_card(). ?>
			</div>
		<?php endif; ?>

		<ul class="b-cases__grid" id="<?php echo esc_attr( $trust_uid ); ?>-list" data-cases-list<?php echo $trust_page['ids'] ? '' : ' hidden'; ?>>
			<?php
			foreach ( $trust_page['ids'] as $trust_id ) {
				echo Cases_Grid::card( $trust_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в card().
			}
			?>
		</ul>

		<div class="b-cases__empty" role="status" data-cases-empty<?php echo $trust_total ? ' hidden' : ''; ?>>
			<span class="b-cases__empty-icon" aria-hidden="true">
				<?php
				Icons::render(
					'search',
					array(
						'size'         => 26,
						'stroke_width' => 2,
					)
				);
				?>
			</span>
			<p class="b-cases__empty-title"><?php echo esc_html( Blocks::text( 'empty_title', __( 'Таких кейсов пока нет', 'trust' ) ) ); ?></p>
			<p class="b-cases__empty-text"><?php echo esc_html( Blocks::text( 'empty_text', __( 'Попробуйте другую услугу или отрасль. А если у вас похожая задача — расскажите о ней, подберём решение.', 'trust' ) ) ); ?></p>
			<button type="button" class="btn btn--outline b-cases__empty-btn" data-cases-reset><?php echo esc_html( Blocks::text( 'empty_button', __( 'Показать все кейсы', 'trust' ) ) ); ?></button>
		</div>

		<div class="b-cases__more" data-cases-more-wrap<?php echo $trust_shown < $trust_total ? '' : ' hidden'; ?>>
			<a class="btn btn--outline b-cases__more-btn" href="<?php echo esc_url( add_query_arg( 'cases', 'all' ) . '#' . $trust_anchor ); ?>" data-cases-more<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>>
				<?php echo esc_html( Blocks::text( 'load_more', __( 'Показать ещё кейсы', 'trust' ) ) ); ?>
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
			<span class="b-cases__shown" data-cases-shown><?php echo esc_html( str_replace( array( '%shown%', '%total%' ), array( (string) $trust_shown, (string) $trust_total ), $trust_shown_label ) ); ?></span>
		</div>
	</div>
</section>
