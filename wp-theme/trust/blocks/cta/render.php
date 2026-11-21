<?php
/**
 * Блок «Обсудим ваш проект» (ТЗ, п. 8.3; макет o-nas/blok-cta.html, portfolio-blog-404/blok-cta.html).
 *
 * Секция лавандового или белого фона, в ней тёмная карточка: иконка, заголовок H2, текст,
 * основной телефон и «Написать в Telegram» (из «Настроек сайта → Контакты»), справа белая
 * кнопка со стрелкой — по умолчанию «Оставить заявку» на страницу контактов. Пустые поля —
 * из «Настроек сайта → Блоки по умолчанию» (Trust\Block_Texts).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Block_Texts;
use Trust\Blocks;
use Trust\Contacts;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_bg       = 'white' === get_field( 'background' ) ? 'white' : 'lavender';
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title    = Block_Texts::field( 'title', 'cta_title' );
$trust_text     = Block_Texts::field( 'text', 'cta_text' );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_label    = Block_Texts::field( 'button_label', 'cta_button' );
$trust_url      = trim( (string) get_field( 'button_url' ) );
$trust_url      = '' !== $trust_url ? $trust_url : Block_Texts::page_url( 'cta_page', 'contacts' );
$trust_url      = '' !== $trust_url ? $trust_url : '#callback-modal';
$trust_contacts = false !== get_field( 'contacts' );
$trust_phone    = $trust_contacts ? Contacts::main_phone() : null;
$trust_telegram = $trust_contacts ? Contacts::telegram() : null;
?>
<section class="b-cta dark-card-sec dark-card-sec--<?php echo esc_attr( $trust_bg ); ?> trust-bg-<?php echo esc_attr( $trust_bg ); ?>"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?> aria-labelledby="<?php echo esc_attr( $trust_title_id ); ?>">
	<div class="dark-card">
		<span class="dark-card__circle dark-card__circle--top" aria-hidden="true"></span>
		<span class="dark-card__circle dark-card__circle--bottom" aria-hidden="true"></span>
		<div class="dark-card__in">
			<div class="dark-card__main">
				<span class="dark-card__icon" aria-hidden="true"><?php Icons::render( 'logo-mark', array( 'size' => 28 ) ); ?></span>
				<div class="dark-card__body">
					<h2 class="dark-card__title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
					<?php if ( '' !== $trust_text ) : ?>
						<p class="dark-card__text"><?php echo esc_html( $trust_text ); ?></p>
					<?php endif; ?>
					<?php if ( $trust_phone || $trust_telegram ) : ?>
						<div class="b-cta__contacts">
							<?php if ( $trust_phone ) : ?>
								<a class="b-cta__contact" href="<?php echo esc_url( $trust_phone['href'] ); ?>">
									<?php Icons::render( 'phone', array( 'size' => 18 ) ); ?>
									<?php echo esc_html( $trust_phone['number'] ); ?>
								</a>
							<?php endif; ?>
							<?php if ( $trust_telegram ) : ?>
								<a class="b-cta__contact" href="<?php echo esc_url( $trust_telegram['url'] ); ?>" target="_blank" rel="noopener">
									<?php Icons::render( 'send', array( 'size' => 18 ) ); ?>
									<?php esc_html_e( 'Написать в Telegram', 'trust' ); ?>
								</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<div class="dark-card__side">
				<a class="dark-card__cta" href="<?php echo esc_url( $trust_url ); ?>">
					<span class="dark-card__cta-text"><?php echo esc_html( $trust_label ); ?></span>
					<span class="dark-card__cta-arrow" aria-hidden="true">
						<?php
						Icons::render(
							'arrow-right',
							array(
								'size'         => 20,
								'stroke_width' => 2.2,
							)
						);
						?>
					</span>
				</a>
			</div>
		</div>
	</div>
</section>
