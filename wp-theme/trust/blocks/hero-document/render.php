<?php
/**
 * Блок «Первый экран документа» (макеты o-nas/oferta.html, politika-cookie.html, soglasie-pd.html).
 *
 * Крошки над тёмной карточкой; в карточке плашка, H1 (поле «H1» в SEO страницы или заголовок)
 * на всю ширину (правка заказчика), под ним даты редакции и кнопки: «Настроить cookie»
 * (#cookie-settings), «Скачать PDF», «Распечатать». При печати крошки, плашка и кнопки скрыты,
 * карточка белая.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Breadcrumbs;
use Trust\Icons;
use Trust\Seo;

defined( 'ABSPATH' ) || exit;

$trust_h1       = Seo::h1( (int) $post_id );
$trust_badge    = trim( (string) get_field( 'badge' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_date     = static function ( $value ): string {
	$time = $value ? strtotime( (string) $value ) : false;
	return $time ? wp_date( 'd.m.Y', $time, new DateTimeZone( 'UTC' ) ) : '';
};
$trust_revision = $trust_date( get_field( 'revision' ) );
$trust_from     = $trust_date( get_field( 'effective' ) );
$trust_dates    = array_filter(
	array(
		/* translators: %s: дата. */
		$trust_revision ? sprintf( __( 'Редакция от %s', 'trust' ), $trust_revision ) : '',
		/* translators: %s: дата. */
		$trust_from ? sprintf( $trust_revision ? __( 'действует с %s', 'trust' ) : __( 'Действует с %s', 'trust' ), $trust_from ) : '',
	)
);
$trust_pdf      = (int) get_field( 'pdf' );
$trust_pdf_url  = $trust_pdf ? (string) wp_get_attachment_url( $trust_pdf ) : '';
$trust_pdf_text = trim( (string) get_field( 'pdf_label' ) );
$trust_print    = (bool) get_field( 'print' );
$trust_cookie   = (bool) get_field( 'cookie' );
$trust_buttons  = $trust_cookie || $trust_pdf_url || $trust_print;
?>
<div class="b-hero-document">
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
			<span class="hero-circle b-hero-document__circle" aria-hidden="true"></span>
			<div class="hero-in b-hero-document__in">
				<?php if ( '' !== $trust_badge ) : ?>
					<span class="hero-badge hero-noprint b-hero-document__badge"><?php echo esc_html( $trust_badge ); ?></span>
				<?php endif; ?>
				<h1 class="hero-title b-hero-document__title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( '' !== $trust_h1 ? $trust_h1 : __( 'Заголовок страницы', 'trust' ) ); ?></h1>
				<?php if ( $trust_dates ) : ?>
					<p class="b-hero-document__dates"><?php echo esc_html( implode( ' · ', $trust_dates ) ); ?></p>
				<?php endif; ?>
				<?php if ( $trust_buttons ) : ?>
					<div class="b-hero-document__buttons hero-noprint">
						<?php if ( $trust_cookie ) : ?>
							<a class="hero-btn hero-btn--white" href="#cookie-settings"><?php Icons::render( 'sliders', array( 'size' => 18 ) ); ?><?php esc_html_e( 'Настроить cookie', 'trust' ); ?></a>
						<?php endif; ?>
						<?php if ( $trust_pdf_url ) : ?>
							<a class="hero-btn" href="<?php echo esc_url( $trust_pdf_url ); ?>" download><?php Icons::render( 'download', array( 'size' => 18 ) ); ?><?php echo esc_html( '' !== $trust_pdf_text ? $trust_pdf_text : __( 'Скачать PDF', 'trust' ) ); ?></a>
						<?php endif; ?>
						<?php if ( $trust_print ) : ?>
							<button type="button" class="hero-btn" data-trust-print<?php echo $is_preview ? '' : ' hidden'; ?>><?php Icons::render( 'print', array( 'size' => 18 ) ); ?><?php esc_html_e( 'Распечатать', 'trust' ); ?></button>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
</div>
