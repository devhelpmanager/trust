<?php
/**
 * Блок «Стажировка» (макет o-nas/karera.html, «Стажировка в SEO-отделе»).
 *
 * Фиолетовая плашка со скруглением и кругом-украшением: слева метка «Без опыта», H2, текст и
 * необязательные пункты с галочками, справа белая кнопка «Подать заявку». Кнопка ведёт к форме
 * «Отправить резюме» (#resume на этой странице или на странице «Карьера») и выбирает в ней
 * вакансию стажёра или пункт «Стажировка» (data-apply-vacancy — скрипт блока trust/resume-form).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;
use Trust\Vacancies;

defined( 'ABSPATH' ) || exit;

$trust_title    = trim( (string) get_field( 'title' ) );
$trust_badge    = trim( (string) get_field( 'badge' ) );
$trust_text     = trim( (string) get_field( 'text' ) );
$trust_items    = Vacancies::items( (string) get_field( 'items' ) );
$trust_button   = Blocks::text( 'button', __( 'Подать заявку', 'trust' ) );
$trust_vacancy  = (int) get_field( 'vacancy' );
$trust_vacancy  = $trust_vacancy && 'publish' === get_post_status( $trust_vacancy ) && Vacancies::is_open( $trust_vacancy ) ? get_post( $trust_vacancy ) : null;
$trust_value    = $trust_vacancy ? Vacancies::form_value( $trust_vacancy ) : Vacancies::INTERN;
$trust_title_id = Blocks::uid( $block, 'title' );

if ( '' === $trust_title && ! $is_preview ) {
	return;
}
?>
<section class="b-intern trust-bg-white"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-intern__card">
		<div class="b-intern__body">
			<?php if ( '' !== $trust_badge ) : ?>
				<span class="b-intern__badge"><?php echo esc_html( $trust_badge ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $trust_title ) : ?>
				<h2 class="b-intern__title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
			<?php endif; ?>
			<?php if ( '' !== $trust_text ) : ?>
				<p class="b-intern__text"><?php echo esc_html( $trust_text ); ?></p>
			<?php endif; ?>
			<?php if ( $trust_items ) : ?>
				<ul class="b-intern__items">
					<?php foreach ( $trust_items as $trust_item ) : ?>
						<li>
							<?php
							Icons::render(
								'check',
								array(
									'size'         => 18,
									'stroke_width' => 2.4,
								)
							);
							?>
							<?php echo esc_html( $trust_item ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php if ( '' !== $trust_button ) : ?>
			<a class="btn btn--white b-intern__btn" href="<?php echo esc_url( Vacancies::apply_url( $trust_value ) ); ?>" data-apply-vacancy="<?php echo esc_attr( $trust_value ); ?>"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>>
				<?php echo esc_html( $trust_button ); ?>
				<?php
				Icons::render(
					'arrow-right',
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
