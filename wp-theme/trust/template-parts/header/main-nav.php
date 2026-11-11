<?php
/**
 * Главное меню в шапке (область «Шапка»), экраны от 1180 px.
 *
 * Пункт с вложенными пунктами — кнопка, открывающая выпадающий список (как в макете).
 * Вторая строка у вложенного пункта — поле «Описание» пункта меню.
 * Разделитель над вложенным пунктом — CSS-класс пункта separator-before.
 *
 * @package Trust
 */

use Trust\Icons;
use Trust\Menu;

defined( 'ABSPATH' ) || exit;

$trust_menu = Menu::tree( 'header' );
if ( ! $trust_menu ) {
	return;
}
?>
<nav class="main-nav" aria-label="<?php esc_attr_e( 'Основное меню', 'trust' ); ?>">
	<ul class="main-nav__list">
		<?php foreach ( $trust_menu as $trust_item ) : ?>
			<?php
			$trust_active = $trust_item['current'] || $trust_item['in_section'];
			$trust_link   = 'main-nav__link' . ( $trust_active ? ' is-active' : '' );
			?>
			<?php if ( $trust_item['children'] ) : ?>
				<?php
				$trust_sub_id    = 'main-nav-sub-' . $trust_item['id'];
				$trust_described = (bool) array_filter( wp_list_pluck( $trust_item['children'], 'description' ) );
				?>
				<li class="main-nav__item main-nav__item--parent">
					<button type="button" class="<?php echo esc_attr( $trust_link ); ?> main-nav__toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $trust_sub_id ); ?>">
						<?php echo esc_html( $trust_item['title'] ); ?>
						<?php
						Icons::render(
							'chevron-down',
							array(
								'size'  => 14,
								'class' => 'main-nav__chevron',
							)
						);
						?>
					</button>
					<div class="main-nav__dropdown" id="<?php echo esc_attr( $trust_sub_id ); ?>">
						<ul class="main-nav__sub main-nav__sub--<?php echo $trust_described ? 'described' : 'simple'; ?>">
							<?php foreach ( $trust_item['children'] as $trust_child ) : ?>
								<li class="<?php echo esc_attr( trim( 'main-nav__sub-item ' . implode( ' ', $trust_child['classes'] ) ) ); ?>">
									<a class="main-nav__sub-link" href="<?php echo esc_url( $trust_child['url'] ); ?>"
										<?php echo $trust_child['current'] ? ' aria-current="page"' : ''; ?>
										<?php echo $trust_child['target'] ? ' target="' . esc_attr( $trust_child['target'] ) . '"' : ''; ?>
										<?php echo $trust_child['rel'] ? ' rel="' . esc_attr( $trust_child['rel'] ) . '"' : ''; ?>>
										<span class="main-nav__sub-title"><?php echo esc_html( $trust_child['title'] ); ?></span>
										<?php if ( $trust_described && $trust_child['description'] ) : ?>
											<span class="main-nav__sub-desc"><?php echo esc_html( $trust_child['description'] ); ?></span>
										<?php endif; ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</li>
			<?php else : ?>
				<li class="main-nav__item">
					<a class="<?php echo esc_attr( $trust_link ); ?>" href="<?php echo esc_url( $trust_item['url'] ); ?>"
						<?php echo $trust_item['current'] ? ' aria-current="page"' : ''; ?>
						<?php echo $trust_item['target'] ? ' target="' . esc_attr( $trust_item['target'] ) . '"' : ''; ?>
						<?php echo $trust_item['rel'] ? ' rel="' . esc_attr( $trust_item['rel'] ) . '"' : ''; ?>><?php echo esc_html( $trust_item['title'] ); ?></a>
				</li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
</nav>
