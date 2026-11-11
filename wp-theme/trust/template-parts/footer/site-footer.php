<?php
/**
 * Подвал сайта (макет iskhodniki/holst-portfolio/Footer.dc.html).
 *
 * Описание, плашки, юридическая строка и заголовок колонки контактов — «Настройки сайта → Подвал»;
 * контакты — «Настройки сайта → Контакты»; колонки ссылок — меню «Подвал: услуги»,
 * «Подвал: компания» (заголовок колонки — название меню), «Подвал: документы».
 * Значки соцсетей выводятся, если в «Настройках сайта → Соцсети» есть ссылки (в макете их нет).
 *
 * @package Trust
 */

use Trust\Contacts;
use Trust\Icons;
use Trust\Menu;
use Trust\Settings;
use Trust\Social;

defined( 'ABSPATH' ) || exit;

$trust_description = (string) Settings::get( 'footer_description', '' );
$trust_tags        = (array) Settings::get( 'footer_tags', array() );
$trust_heading     = Settings::get( 'footer_contacts_link' );
$trust_phone       = Contacts::main_phone();
$trust_email       = Contacts::main_email();
$trust_address     = Contacts::address_line();
$trust_hours       = Contacts::hours();
$trust_socials     = Social::company_links();
$trust_callback    = (string) Settings::get( 'callback_header_label', __( 'Заказать обратный звонок', 'trust' ) );
$trust_legal       = str_replace( '{год}', wp_date( 'Y' ), (string) Settings::get( 'footer_legal', '' ) );
$trust_docs        = Menu::tree( 'footer-docs', 1 );
?>
<footer class="site-footer">
	<div class="site-footer__inner">
		<div class="site-footer__top">
			<div class="site-footer__about">
				<?php get_template_part( 'template-parts/brand' ); ?>
				<?php if ( $trust_description ) : ?>
					<p class="site-footer__desc"><?php echo esc_html( $trust_description ); ?></p>
				<?php endif; ?>
				<?php if ( $trust_tags ) : ?>
					<div class="site-footer__tags">
						<?php foreach ( $trust_tags as $trust_tag ) : ?>
							<span class="site-footer__tag"><?php echo esc_html( $trust_tag['label'] ?? '' ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php foreach ( array( 'footer-services', 'footer-company' ) as $trust_location ) : ?>
				<?php
				$trust_links = Menu::tree( $trust_location, 1 );
				if ( ! $trust_links ) {
					continue;
				}
				$trust_title = Menu::name( $trust_location );
				?>
				<nav class="site-footer__col" aria-label="<?php echo esc_attr( $trust_title ); ?>">
					<span class="site-footer__heading"><?php echo esc_html( $trust_title ); ?></span>
					<ul class="site-footer__list">
						<?php foreach ( $trust_links as $trust_link ) : ?>
							<li>
								<a class="site-footer__link" href="<?php echo esc_url( $trust_link['url'] ); ?>"
									<?php echo $trust_link['target'] ? ' target="' . esc_attr( $trust_link['target'] ) . '"' : ''; ?>
									<?php echo $trust_link['rel'] ? ' rel="' . esc_attr( $trust_link['rel'] ) . '"' : ''; ?>><?php echo esc_html( $trust_link['title'] ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endforeach; ?>

			<div class="site-footer__contacts">
				<?php if ( is_array( $trust_heading ) && ! empty( $trust_heading['url'] ) ) : ?>
					<a class="site-footer__heading site-footer__tap" href="<?php echo esc_url( $trust_heading['url'] ); ?>"
						<?php echo ! empty( $trust_heading['target'] ) ? ' target="' . esc_attr( $trust_heading['target'] ) . '" rel="noopener"' : ''; ?>><?php echo esc_html( $trust_heading['title'] ? $trust_heading['title'] : __( 'Контакты', 'trust' ) ); ?></a>
				<?php else : ?>
					<span class="site-footer__heading"><?php esc_html_e( 'Контакты', 'trust' ); ?></span>
				<?php endif; ?>

				<?php if ( $trust_phone ) : ?>
					<a class="site-footer__phone site-footer__tap" href="<?php echo esc_url( $trust_phone['href'] ); ?>"><?php echo esc_html( $trust_phone['number'] ); ?></a>
				<?php endif; ?>
				<?php if ( $trust_email ) : ?>
					<a class="site-footer__email site-footer__tap" href="<?php echo esc_url( $trust_email['href'] ); ?>"><?php echo esc_html( $trust_email['email'] ); ?></a>
				<?php endif; ?>
				<?php if ( $trust_address || $trust_hours ) : ?>
					<span class="site-footer__address">
						<?php echo esc_html( $trust_address ); ?>
						<?php if ( $trust_address && $trust_hours ) : ?>
							<br>
						<?php endif; ?>
						<?php echo esc_html( $trust_hours ); ?>
					</span>
				<?php endif; ?>

				<?php if ( $trust_socials ) : ?>
					<ul class="site-footer__social">
						<?php foreach ( $trust_socials as $trust_social ) : ?>
							<li>
								<a class="site-footer__social-link" href="<?php echo esc_url( $trust_social['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $trust_social['label'] ); ?>">
									<?php Icons::render( 'social-' . $trust_social['network'], array( 'size' => 20 ) ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<a class="site-footer__callback" href="#callback" data-trust-callback><?php echo esc_html( $trust_callback ); ?></a>
			</div>
		</div>

		<?php if ( $trust_legal || $trust_docs ) : ?>
			<div class="site-footer__bottom">
				<span><?php echo esc_html( $trust_legal ); ?></span>
				<?php if ( $trust_docs ) : ?>
					<nav class="site-footer__docs" aria-label="<?php esc_attr_e( 'Юридическая информация', 'trust' ); ?>">
						<?php foreach ( $trust_docs as $trust_doc ) : ?>
							<a href="<?php echo esc_url( $trust_doc['url'] ); ?>"
								<?php echo $trust_doc['target'] ? ' target="' . esc_attr( $trust_doc['target'] ) . '"' : ''; ?>
								<?php echo $trust_doc['rel'] ? ' rel="' . esc_attr( $trust_doc['rel'] ) . '"' : ''; ?>><?php echo esc_html( $trust_doc['title'] ); ?></a>
						<?php endforeach; ?>
					</nav>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</footer>
