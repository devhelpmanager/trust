<?php
/**
 * Блок «Открытые вакансии» (макет o-nas/karera.html, секция «Вакансии»).
 *
 * Лавандовая секция: шапка, кнопки отделов с числом вакансий («Все» — первая) и список
 * карточек-«аккордеонов». Строка карточки — кнопка: название, отдел, формат, опыт (и город, если
 * указан), зарплата и стрелка. Раскрытая часть: «коротко о вакансии», разделы описания списками
 * (обычно три колонки), кнопка «Откликнуться» и «Или пришлите резюме на hr@…».
 *
 * Вакансии — раздел «Вакансии» с отметкой «Принимаем резюме», по полю «Порядок». Раскрыта одна
 * (по умолчанию первая); фильтр — без перезагрузки (assets/js/filter.js). Без JavaScript
 * раскрыты все, кнопок фильтра нет. «Откликнуться» ведёт к форме резюме (#resume) и выбирает
 * вакансию (data-apply-vacancy — скрипт блока trust/resume-form).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Contacts;
use Trust\Icons;
use Trust\Vacancies;

defined( 'ABSPATH' ) || exit;

$trust_vacancies = Vacancies::open_posts();
$trust_depts     = array();
$trust_cards     = array();
foreach ( $trust_vacancies as $trust_vacancy ) {
	$trust_terms = get_the_terms( $trust_vacancy, 'department' );
	$trust_terms = is_array( $trust_terms ) ? $trust_terms : array();
	foreach ( $trust_terms as $trust_term ) {
		$trust_depts[ $trust_term->slug ] = array(
			'name'  => $trust_term->name,
			'count' => ( $trust_depts[ $trust_term->slug ]['count'] ?? 0 ) + 1,
		);
	}
	$trust_cards[] = array( $trust_vacancy, $trust_terms );
}

$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_anchor   = '' !== $trust_anchor ? $trust_anchor : 'vacancies';
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_total    = count( $trust_cards );
$trust_filter   = false !== get_field( 'filter' ) && count( $trust_depts ) > 1;
$trust_open     = false !== get_field( 'open_first' );
$trust_apply    = Blocks::text( 'apply', __( 'Откликнуться', 'trust' ) );
$trust_mail     = Contacts::hr_email();
$trust_mail_txt = Blocks::text( 'mail_text', __( 'Или пришлите резюме на', 'trust' ) );
/* translators: %total%: число вакансий (подставляет скрипт фильтра). */
$trust_all_text = __( 'Показаны все вакансии: %total%', 'trust' );
/* translators: %shown%, %total%: сколько вакансий показано и сколько всего. */
$trust_some_text = __( 'Показано вакансий: %shown% из %total%', 'trust' );
?>
<section class="b-vac trust-bg-lavender" id="<?php echo esc_attr( $trust_anchor ); ?>"<?php echo '' !== trim( (string) get_field( 'title' ) ) ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-vac__inner"<?php echo $trust_filter ? ' data-trust-filter' : ''; ?>>
		<?php Blocks::section_head( 'b-vac__head', $trust_title_id ); ?>

		<?php if ( $trust_filter ) : ?>
			<div class="filter-chips" role="group" aria-label="<?php esc_attr_e( 'Фильтр по отделам', 'trust' ); ?>">
				<button type="button" class="filter-chip" data-filter-value="" aria-pressed="true"><?php esc_html_e( 'Все', 'trust' ); ?><span class="filter-chip__count"><?php echo esc_html( (string) $trust_total ); ?></span></button>
				<?php foreach ( $trust_depts as $trust_slug => $trust_dept ) : ?>
					<button type="button" class="filter-chip" data-filter-value="<?php echo esc_attr( $trust_slug ); ?>" aria-pressed="false"><?php echo esc_html( $trust_dept['name'] ); ?><span class="filter-chip__count"><?php echo esc_html( (string) $trust_dept['count'] ); ?></span></button>
				<?php endforeach; ?>
			</div>
			<p class="screen-reader-text" aria-live="polite" data-filter-status data-all="<?php echo esc_attr( $trust_all_text ); ?>" data-some="<?php echo esc_attr( $trust_some_text ); ?>"></p>
		<?php endif; ?>

		<?php if ( $trust_cards ) : ?>
			<ul class="b-vac__list">
				<?php foreach ( $trust_cards as $trust_i => list( $trust_vacancy, $trust_terms ) ) : ?>
					<?php
					$trust_id       = $trust_vacancy->ID;
					$trust_is_open  = ( $trust_open || $is_preview ) && 0 === $trust_i;
					$trust_btn_id   = Blocks::uid( $block, 'vac-btn-' . $trust_id );
					$trust_panel_id = Blocks::uid( $block, 'vac-panel-' . $trust_id );
					$trust_tags     = array_filter(
						array(
							trim( (string) get_post_meta( $trust_id, 'trust_format', true ) ),
							trim( (string) get_post_meta( $trust_id, 'trust_experience', true ) ),
							trim( (string) get_post_meta( $trust_id, 'trust_city', true ) ),
						),
						'strlen'
					);
					$trust_salary   = trim( (string) get_post_meta( $trust_id, 'trust_salary', true ) );
					$trust_summary  = trim( (string) get_post_meta( $trust_id, 'trust_summary', true ) );
					$trust_sections = array();
					foreach ( (array) get_field( 'trust_sections', $trust_id ) as $trust_row ) {
						$trust_items = is_array( $trust_row ) ? Vacancies::items( (string) ( $trust_row['items'] ?? '' ) ) : array();
						if ( $trust_items ) {
							$trust_sections[] = array( trim( (string) ( $trust_row['title'] ?? '' ) ), $trust_items );
						}
					}
					$trust_value = Vacancies::form_value( $trust_vacancy );
					?>
					<li class="b-vac__item<?php echo $trust_is_open ? ' is-open' : ''; ?>" data-filter-tags="<?php echo esc_attr( implode( ' ', wp_list_pluck( $trust_terms, 'slug' ) ) ); ?>">
						<h3 class="b-vac__heading">
							<button type="button" class="b-vac__toggle" id="<?php echo esc_attr( $trust_btn_id ); ?>" aria-expanded="<?php echo $trust_is_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $trust_panel_id ); ?>">
								<span class="b-vac__summary">
									<span class="b-vac__main">
										<span class="b-vac__title"><?php echo esc_html( get_the_title( $trust_vacancy ) ); ?></span>
										<span class="b-vac__tags">
											<?php foreach ( $trust_terms as $trust_term ) : ?>
												<span class="b-vac__tag b-vac__tag--dept"><?php echo esc_html( $trust_term->name ); ?></span>
											<?php endforeach; ?>
											<?php foreach ( $trust_tags as $trust_tag ) : ?>
												<span class="b-vac__tag"><?php echo esc_html( $trust_tag ); ?></span>
											<?php endforeach; ?>
										</span>
									</span>
									<?php if ( '' !== $trust_salary ) : ?>
										<span class="b-vac__salary"><?php echo esc_html( $trust_salary ); ?></span>
									<?php endif; ?>
								</span>
								<span class="b-vac__chevron" aria-hidden="true">
									<?php
									Icons::render(
										'chevron-down',
										array(
											'size'         => 20,
											'stroke_width' => 2.2,
										)
									);
									?>
								</span>
							</button>
						</h3>
						<div class="b-vac__panel" id="<?php echo esc_attr( $trust_panel_id ); ?>" role="region" aria-labelledby="<?php echo esc_attr( $trust_btn_id ); ?>"<?php echo $trust_is_open ? '' : ' hidden'; ?>>
							<?php if ( '' !== $trust_summary ) : ?>
								<p class="b-vac__about"><?php echo esc_html( $trust_summary ); ?></p>
							<?php endif; ?>
							<?php if ( $trust_sections ) : ?>
								<div class="b-vac__sections">
									<?php foreach ( $trust_sections as list( $trust_section_title, $trust_items ) ) : ?>
										<div class="b-vac__section">
											<?php if ( '' !== $trust_section_title ) : ?>
												<h4 class="b-vac__section-title"><?php echo esc_html( $trust_section_title ); ?></h4>
											<?php endif; ?>
											<ul class="b-vac__points">
												<?php foreach ( $trust_items as $trust_item ) : ?>
													<li><?php echo esc_html( $trust_item ); ?></li>
												<?php endforeach; ?>
											</ul>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							<div class="b-vac__actions">
								<?php if ( '' !== $trust_apply ) : ?>
									<a class="btn b-vac__apply" href="<?php echo esc_url( Vacancies::apply_url( $trust_value ) ); ?>" data-apply-vacancy="<?php echo esc_attr( $trust_value ); ?>"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>>
										<?php echo esc_html( $trust_apply ); ?>
										<span class="screen-reader-text"><?php echo esc_html( '— ' . get_the_title( $trust_vacancy ) ); ?></span>
										<?php
										Icons::render(
											'arrow-right',
											array(
												'size' => 18,
												'stroke_width' => 2.2,
											)
										);
										?>
									</a>
								<?php endif; ?>
								<?php if ( $trust_mail && '' !== $trust_mail_txt ) : ?>
									<span class="b-vac__mail"><?php echo esc_html( $trust_mail_txt ); ?> <a href="<?php echo esc_attr( $trust_mail['href'] ); ?>"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>><?php echo esc_html( $trust_mail['email'] ); ?></a></span>
								<?php endif; ?>
							</div>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="b-vac__empty">
				<?php echo esc_html( Blocks::text( 'empty', __( 'Сейчас открытых вакансий нет. Пришлите резюме — свяжемся, когда появится подходящее место.', 'trust' ) ) ); ?>
				<?php if ( $trust_mail ) : ?>
					<a href="<?php echo esc_attr( $trust_mail['href'] ); ?>"><?php echo esc_html( $trust_mail['email'] ); ?></a>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</div>
</section>
