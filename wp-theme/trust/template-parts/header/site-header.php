<?php
/**
 * Шапка сайта (макет iskhodniki/holst-portfolio/Header.dc.html).
 *
 * Логотип и контакты — из «Настроек сайта», меню — из области «Шапка».
 * Уже 1180 px главное меню заменяется кнопкой-бургером и мобильным меню.
 *
 * @package Trust
 */

use Trust\Contacts;
use Trust\Icons;
use Trust\Settings;

defined( 'ABSPATH' ) || exit;

$trust_phone    = Contacts::main_phone();
$trust_email    = Contacts::main_email();
$trust_hours    = Contacts::hours();
$trust_callback = (string) Settings::get( 'callback_header_label', __( 'Заказать обратный звонок', 'trust' ) );
?>
<header class="site-header">
	<div class="site-header__inner">
		<div class="site-header__top">
			<?php get_template_part( 'template-parts/brand', null, array( 'tagline' => true ) ); ?>

			<?php if ( $trust_phone || $trust_email ) : ?>
				<div class="site-header__contacts">
					<?php if ( $trust_phone ) : ?>
						<a class="header-phone" href="<?php echo esc_url( $trust_phone['href'] ); ?>">
							<span class="header-phone__number"><?php echo esc_html( $trust_phone['number'] ); ?></span>
							<?php if ( $trust_hours ) : ?>
								<span class="header-phone__hours"><?php echo esc_html( $trust_hours ); ?></span>
							<?php endif; ?>
						</a>
					<?php endif; ?>
					<?php if ( $trust_email ) : ?>
						<a class="header-email" href="<?php echo esc_url( $trust_email['href'] ); ?>"><?php echo esc_html( $trust_email['email'] ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="site-header__actions">
				<a class="header-callback" href="#callback" data-trust-callback>
					<?php Icons::render( 'phone', array( 'size' => 18 ) ); ?>
					<?php echo esc_html( $trust_callback ); ?>
				</a>
				<a class="header-callback-icon" href="#callback" data-trust-callback aria-label="<?php echo esc_attr( $trust_callback ); ?>">
					<?php Icons::render( 'phone', array( 'size' => 20 ) ); ?>
				</a>
				<button type="button" class="header-burger" aria-label="<?php esc_attr_e( 'Меню', 'trust' ); ?>" aria-expanded="false" aria-controls="mobile-menu">
					<?php
					Icons::render(
						'menu',
						array(
							'size'  => 22,
							'class' => 'header-burger__open',
						)
					);
					Icons::render(
						'close',
						array(
							'size'  => 22,
							'class' => 'header-burger__close',
						)
					);
					?>
				</button>
			</div>
		</div>

		<?php get_template_part( 'template-parts/header/main-nav' ); ?>
		<?php get_template_part( 'template-parts/header/mobile-nav' ); ?>
	</div>
</header>
