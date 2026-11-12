<?php
/**
 * Блок «Первый экран услуги» (макет holst-stranitsy/Main, seo-stranitsa/index.html).
 *
 * Белый экран: хлебные крошки, H1 (поле «H1» в SEO страницы или заголовок) на всю ширину,
 * под ним вводный текст и кнопки — основная и контурная. На широком экране кнопки справа от
 * текста, внизу (в макете H1 стоял в колонке текста — правка заказчика).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Breadcrumbs;
use Trust\Seo;

defined( 'ABSPATH' ) || exit;

$trust_h1       = Seo::h1( (int) $post_id );
$trust_lead     = trim( (string) get_field( 'lead' ) );
$trust_buttons  = Blocks::buttons( get_field( 'buttons' ), array( 'btn', 'btn btn--outline' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
?>
<section class="b-hero-service" aria-labelledby="<?php echo esc_attr( $trust_title_id ); ?>">
	<div class="b-hero-service__inner">
		<?php
		if ( $is_preview ) {
			Breadcrumbs::render_preview( (int) $post_id, 'b-hero-service__crumbs' );
		} else {
			Breadcrumbs::render( 'b-hero-service__crumbs' );
		}
		?>
		<div class="b-hero-service__row">
			<h1 class="b-hero-service__title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( '' !== $trust_h1 ? $trust_h1 : __( 'Заголовок страницы', 'trust' ) ); ?></h1>
			<?php if ( '' !== $trust_lead ) : ?>
				<p class="b-hero-service__lead"><?php echo esc_html( $trust_lead ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $trust_buttons ) : ?>
				<div class="b-hero-service__buttons">
					<?php echo $trust_buttons; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- собрано Blocks::buttons() с экранированием. ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
