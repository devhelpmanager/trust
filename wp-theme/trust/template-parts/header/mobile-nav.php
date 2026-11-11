<?php
/**
 * Мобильное меню (экраны уже 1180 px): открывается кнопкой-бургером в шапке.
 *
 * Те же пункты, что в главном меню (область «Шапка»); пункты с вложенными
 * раскрываются по нажатию. Внизу — телефон, email и кнопка звонка из «Настроек сайта».
 * Без JavaScript меню показано раскрытым целиком.
 *
 * @package Trust
 */

use Trust\Contacts;
use Trust\Icons;
use Trust\Menu;
use Trust\Settings;

defined( 'ABSPATH' ) || exit;

$trust_menu     = Menu::tree( 'header' );
$trust_phone    = Contacts::main_phone();
$trust_email    = Contacts::main_email();
$trust_callback = (string) Settings::get( 'callback_header_label', __( 'Заказать обратный звонок', 'trust' ) );
?>
<nav class="mobile-nav" id="mobile-menu" aria-label="<?php esc_attr_e( 'Меню', 'trust' ); ?>">
	<?php if ( $trust_menu ) : ?>
		<ul class="mobile-nav__list">
			<?php foreach ( $trust_menu as $trust_item ) : ?>
				<li class="mobile-nav__item">
					<?php if ( $trust_item['children'] ) : ?>
						<?php $trust_sub_id = 'mobile-nav-sub-' . $trust_item['id']; ?>
						<button type="button" class="mobile-nav__link mobile-nav__toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $trust_sub_id ); ?>">
							<?php echo esc_html( $trust_item['title'] ); ?>
							<?php
							Icons::render(
								'chevron-down',
								array(
									'size'  => 18,
									'class' => 'mobile-nav__chevron',
								)
							);
							?>
						</button>
						<ul class="mobile-nav__sub" id="<?php echo esc_attr( $trust_sub_id ); ?>">
							<?php foreach ( $trust_item['children'] as $trust_child ) : ?>
								<li>
									<a class="mobile-nav__sub-link" href="<?php echo esc_url( $trust_child['url'] ); ?>"
										<?php echo $trust_child['current'] ? ' aria-current="page"' : ''; ?>
										<?php echo $trust_child['target'] ? ' target="' . esc_attr( $trust_child['target'] ) . '"' : ''; ?>
										<?php echo $trust_child['rel'] ? ' rel="' . esc_attr( $trust_child['rel'] ) . '"' : ''; ?>><?php echo esc_html( $trust_child['title'] ); ?></a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<a class="mobile-nav__link" href="<?php echo esc_url( $trust_item['url'] ); ?>"
							<?php echo $trust_item['current'] ? ' aria-current="page"' : ''; ?>
							<?php echo $trust_item['target'] ? ' target="' . esc_attr( $trust_item['target'] ) . '"' : ''; ?>
							<?php echo $trust_item['rel'] ? ' rel="' . esc_attr( $trust_item['rel'] ) . '"' : ''; ?>><?php echo esc_html( $trust_item['title'] ); ?></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="mobile-nav__contacts">
		<?php if ( $trust_phone ) : ?>
			<a class="mobile-nav__phone" href="<?php echo esc_url( $trust_phone['href'] ); ?>"><?php echo esc_html( $trust_phone['number'] ); ?></a>
		<?php endif; ?>
		<?php if ( $trust_email ) : ?>
			<a class="mobile-nav__email" href="<?php echo esc_url( $trust_email['href'] ); ?>"><?php echo esc_html( $trust_email['email'] ); ?></a>
		<?php endif; ?>
		<a class="mobile-nav__callback" href="#callback" data-trust-callback><?php echo esc_html( $trust_callback ); ?></a>
	</div>
</nav>
