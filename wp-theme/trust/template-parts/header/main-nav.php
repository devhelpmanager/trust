<?php
/**
 * Главное меню в шапке (область «Шапка»), экраны от 1180 px.
 *
 * Пункт с вложенными пунктами: если у него есть страница (адрес не «#»), название — ссылка на неё,
 * а стрелка рядом — кнопка выпадающего списка (правка заказчика); список, как в макете, открывается
 * и при наведении. Пункт без страницы — одна кнопка «название + стрелка».
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
				$trust_sub_id  = 'main-nav-sub-' . $trust_item['id'];
				$trust_section = Menu::section_url( $trust_item );
				/* translators: %s: название пункта меню. */
				$trust_sub_label = sprintf( __( 'Подменю «%s»', 'trust' ), $trust_item['title'] );
				$trust_described = (bool) array_filter( wp_list_pluck( $trust_item['children'], 'description' ) );
				?>
				<li class="main-nav__item main-nav__item--parent">
					<?php if ( $trust_section ) : ?>
						<span class="<?php echo esc_attr( $trust_link ); ?> main-nav__split">
							<a class="main-nav__split-link" href="<?php echo esc_url( $trust_section ); ?>"
								<?php echo $trust_item['current'] ? ' aria-current="page"' : ''; ?>
								<?php echo $trust_item['target'] ? ' target="' . esc_attr( $trust_item['target'] ) . '"' : ''; ?>
								<?php echo $trust_item['rel'] ? ' rel="' . esc_attr( $trust_item['rel'] ) . '"' : ''; ?>><?php echo esc_html( $trust_item['title'] ); ?></a>
							<button type="button" class="main-nav__toggle main-nav__split-toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $trust_sub_id ); ?>" aria-label="<?php echo esc_attr( $trust_sub_label ); ?>">
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
						</span>
					<?php else : ?>
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
					<?php endif; ?>
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
