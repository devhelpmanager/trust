<?php
/**
 * Блок «Первый экран раздела» (макеты portfolio-blog-404/portfolio.html, o-nas/o-kompanii.html,
 * o-nas/komanda.html, o-nas/karera.html).
 *
 * Над тёмной карточкой — хлебные крошки; в карточке плашка, H1 (поле «H1» в SEO страницы или
 * заголовок), текст, до двух кнопок (белая и прозрачная), до четырёх цифр (значение над
 * подписью; {vacancies} — число открытых вакансий). На телефоне карточка во всю ширину.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Breadcrumbs;
use Trust\Seo;
use Trust\Vacancies;

defined( 'ABSPATH' ) || exit;

$trust_h1       = Seo::h1( (int) $post_id );
$trust_badge    = trim( (string) get_field( 'badge' ) );
$trust_lead     = trim( (string) get_field( 'lead' ) );
$trust_buttons  = Blocks::buttons( get_field( 'buttons' ), array( 'btn btn--white b-hero-section__btn', 'btn btn--ghost b-hero-section__btn' ) );
$trust_stats    = array_values(
	array_filter(
		(array) get_field( 'stats' ),
		static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['value'] ?? '' ) ) && '' !== trim( (string) ( $row['label'] ?? '' ) )
	)
);
$trust_title_id = Blocks::uid( $block, 'title' );
?>
<div class="b-hero-section">
	<div class="hero-crumbs">
		<?php
		if ( $is_preview ) {
			Breadcrumbs::render_preview( (int) $post_id );
		} else {
			Breadcrumbs::render();
		}
		?>
	</div>
	<section class="hero-sec" aria-labelledby="<?php echo esc_attr( $trust_title_id ); ?>">
		<div class="hero-box">
			<span class="hero-circle hero-circle--top" aria-hidden="true"></span>
			<span class="hero-circle hero-circle--bottom" aria-hidden="true"></span>
			<div class="hero-in b-hero-section__in">
				<div class="b-hero-section__head">
					<?php if ( '' !== $trust_badge ) : ?>
						<span class="hero-badge"><?php echo esc_html( $trust_badge ); ?></span>
					<?php endif; ?>
					<h1 class="hero-title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( '' !== $trust_h1 ? $trust_h1 : __( 'Заголовок страницы', 'trust' ) ); ?></h1>
					<?php if ( '' !== $trust_lead ) : ?>
						<p class="hero-lead b-hero-section__lead"><?php echo esc_html( $trust_lead ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $trust_buttons ) : ?>
						<div class="b-hero-section__buttons">
							<?php echo $trust_buttons; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- собрано Blocks::buttons() с экранированием. ?>
						</div>
					<?php endif; ?>
				</div>
				<?php if ( $trust_stats ) : ?>
					<dl class="b-hero-section__stats">
						<?php foreach ( $trust_stats as $trust_stat ) : ?>
							<div class="b-hero-section__stat">
								<dt><?php echo esc_html( $trust_stat['label'] ); ?></dt>
								<dd><?php echo esc_html( str_replace( '{vacancies}', (string) Vacancies::open_count(), (string) $trust_stat['value'] ) ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>
			</div>
		</div>
	</section>
</div>
