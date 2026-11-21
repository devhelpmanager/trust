<?php
/**
 * Блок «Задать вопрос SEO-специалисту» (ТЗ, п. 8.3; макет seo-vopros/blok-vopros.html).
 *
 * Тёмная карточка (общие стили trust-dark-card): иконка, H2, текст, пункты-плашки с галочкой,
 * белая кнопка «Задать вопрос бесплатно» на страницу вопросов и примечание. Задать вопрос можно
 * только на странице вопросов (решение заказчика: вида «Форма в блоке» нет). Раскладка на
 * планшете и телефоне — в dark-card.css; на узком экране иконки нет (block style.css).
 *
 * Пустые поля — из «Настроек сайта → Блоки по умолчанию» (Trust\Block_Texts).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Block_Texts;
use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_bg       = (string) get_field( 'background' );
$trust_bg       = in_array( $trust_bg, array( 'white', 'violet' ), true ) ? $trust_bg : 'lavender';
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title    = Block_Texts::field( 'title', 'ask_title' );
$trust_text     = Block_Texts::field( 'text', 'ask_text' );
$trust_points   = array_slice( Block_Texts::lines( Block_Texts::field( 'points', 'ask_points' ) ), 0, 4 );
$trust_note     = Block_Texts::field( 'note', 'ask_note' );
$trust_title_id = Blocks::uid( $block, 'title' );

// Кнопка: свой текст и адрес блока, иначе из настроек (адрес — страница вопросов).
$trust_label = Block_Texts::field( 'button_label', 'ask_button' );
$trust_url   = trim( (string) get_field( 'button_url' ) );
$trust_url   = '' !== $trust_url ? $trust_url : Block_Texts::page_url( 'ask_page', 'questions' );
?>
<section class="b-ask dark-card-sec dark-card-sec--<?php echo esc_attr( $trust_bg ); ?> trust-bg-<?php echo esc_attr( $trust_bg ); ?>"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?> aria-labelledby="<?php echo esc_attr( $trust_title_id ); ?>">
	<div class="dark-card">
		<span class="dark-card__circle dark-card__circle--top" aria-hidden="true"></span>
		<span class="dark-card__circle dark-card__circle--bottom" aria-hidden="true"></span>
		<div class="dark-card__in">
			<div class="dark-card__main">
				<span class="dark-card__icon" aria-hidden="true"><?php Icons::render( 'chat-question', array( 'size' => 28 ) ); ?></span>
				<div class="dark-card__body">
					<h2 class="dark-card__title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
					<?php if ( '' !== $trust_text ) : ?>
						<p class="dark-card__text"><?php echo esc_html( $trust_text ); ?></p>
					<?php endif; ?>
					<?php if ( $trust_points ) : ?>
						<ul class="b-ask__points" aria-label="<?php esc_attr_e( 'Условия', 'trust' ); ?>">
							<?php foreach ( $trust_points as $trust_point ) : ?>
								<li>
									<?php
									Icons::render(
										'check',
										array(
											'size'         => 14,
											'stroke_width' => 2.6,
										)
									);
									?>
									<?php echo esc_html( $trust_point ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</div>
			<div class="dark-card__side">
				<?php if ( '' !== $trust_url ) : ?>
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
				<?php elseif ( $is_preview ) : ?>
					<?php echo Blocks::editor_warning( __( 'Нет страницы типа «Вопросы SEO»: выберите страницу кнопки в блоке или в «Настройках сайта → Блоки по умолчанию».', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- текст экранирован в editor_warning(). ?>
				<?php endif; ?>
				<?php if ( '' !== $trust_note ) : ?>
					<p class="dark-card__note"><?php Icons::render( 'info', array( 'size' => 16 ) ); ?><span><?php echo esc_html( $trust_note ); ?></span></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
