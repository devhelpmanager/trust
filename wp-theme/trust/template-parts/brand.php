<?php
/**
 * Логотип: загруженная картинка из «Настроек сайта → Компания» или знак из дизайна + название.
 *
 * Аргументы (get_template_part( 'template-parts/brand', null, array( … ) )):
 * - tagline (bool) — показывать подпись под названием (в шапке — да, в подвале — нет).
 *
 * @package Trust
 */

use Trust\Icons;
use Trust\Settings;

defined( 'ABSPATH' ) || exit;

$trust_args    = wp_parse_args( $args ?? array(), array( 'tagline' => false ) );
$trust_brand   = Settings::brand();
$trust_tagline = $trust_args['tagline'] ? (string) Settings::get( 'company_tagline', '' ) : '';
$trust_logo    = (int) Settings::get( 'company_logo_light', 0 );
/* translators: %s: название бренда. */
$trust_label = sprintf( __( '%s — на главную', 'trust' ), $trust_brand );
?>
<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $trust_label ); ?>">
	<?php if ( $trust_logo ) : ?>
		<?php
		echo wp_get_attachment_image(
			$trust_logo,
			'medium',
			false,
			array(
				'class'         => 'brand__logo',
				'alt'           => '',
				'loading'       => false,
				'fetchpriority' => 'high',
			)
		);
		?>
	<?php else : ?>
		<span class="brand__mark" aria-hidden="true">
			<?php
			Icons::render(
				'logo-mark',
				array(
					'size'         => 22,
					'stroke_width' => 2.2,
				)
			);
			?>
		</span>
		<span class="brand__text">
			<span class="brand__name"><?php echo esc_html( $trust_brand ); ?></span>
			<?php if ( $trust_tagline ) : ?>
				<span class="brand__tagline"><?php echo esc_html( $trust_tagline ); ?></span>
			<?php endif; ?>
		</span>
	<?php endif; ?>
</a>
