<?php
/**
 * Блок «Оповещение» (ТЗ, п. 8.3; макеты seo-vopros/vopros-seo.html — «Важно знать», otvet.html).
 *
 * Лавандовая плашка: значок и справа от него заголовок, под ними во всю ширину — текст и строка со
 * ссылкой. Заголовок — выделенная строка, не H2 (правка заказчика; в статье не ломает оглавление).
 * На странице — секция («Вопросы SEO»: под первым экраном, ссылка «Закажите технический аудит»,
 * название секции — заголовок плашки); в статье и ответе — плашка в тексте. Пустые поля — из
 * «Настроек сайта → Блоки по умолчанию» (Trust\Block_Texts).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Block_Texts;
use Trust\Blocks;
use Trust\Icons;
use Trust\Settings;

defined( 'ABSPATH' ) || exit;

$trust_icon     = (string) get_field( 'icon' );
$trust_icon     = Icons::exists( $trust_icon ) ? $trust_icon : 'info';
$trust_title    = Block_Texts::field( 'title', 'notice_title' );
$trust_text     = Block_Texts::field( 'text', 'notice_text' );
$trust_inline   = 'page' !== get_post_type( (int) $post_id );
$trust_title_id = Blocks::uid( $block, 'title' );

// Строка со ссылкой: ссылка блока, иначе из настроек; без адреса строки нет.
$trust_link = null;
if ( false !== get_field( 'show_link' ) ) {
	$trust_own  = get_field( 'link' );
	$trust_link = is_array( $trust_own ) && ! empty( $trust_own['url'] ) ? $trust_own : Settings::get( 'notice_link' );
	$trust_link = is_array( $trust_link ) && ! empty( $trust_link['url'] ) ? $trust_link : null;
}
$trust_prefix = Block_Texts::field( 'link_prefix', 'notice_link_prefix' );
$trust_label  = $trust_link && '' !== trim( (string) ( $trust_link['title'] ?? '' ) ) ? trim( $trust_link['title'] ) : Block_Texts::get( 'notice_link_label' );

$trust_box = static function () use ( $trust_icon, $trust_title, $trust_text, $trust_inline, $trust_title_id, $trust_link, $trust_prefix, $trust_label ): void {
	// Правка заказчика: заголовок — строка справа от значка (не H2), текст и ссылка — под ними во
	// всю ширину плашки. Без заголовка справа от значка — текст.
	$trust_beside = '' !== $trust_title ? 'title' : 'text';
	?>
	<div class="b-notice__head">
		<span class="b-notice__icon" aria-hidden="true">
			<?php Icons::render( $trust_icon, array( 'size' => $trust_inline ? 22 : 24 ) ); ?>
		</span>
		<?php if ( 'title' === $trust_beside ) : ?>
			<p class="b-notice__title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></p>
		<?php elseif ( '' !== $trust_text ) : ?>
			<p class="b-notice__text"><?php echo esc_html( $trust_text ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( 'title' === $trust_beside && '' !== $trust_text ) : ?>
		<p class="b-notice__text"><?php echo esc_html( $trust_text ); ?></p>
	<?php endif; ?>
	<?php if ( $trust_link ) : ?>
		<p class="b-notice__more">
			<?php echo '' !== $trust_prefix ? esc_html( $trust_prefix ) . ' ' : ''; ?><a href="<?php echo esc_url( $trust_link['url'] ); ?>"<?php echo ! empty( $trust_link['target'] ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $trust_label ); ?></a>
		</p>
	<?php endif; ?>
	<?php
};
?>
<?php if ( $trust_inline ) : ?>
	<div class="b-notice b-notice--inline" role="note">
		<?php $trust_box(); ?>
	</div>
<?php else : ?>
	<section class="b-notice-sec"<?php echo '' !== $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ' aria-label="' . esc_attr__( 'Важно знать', 'trust' ) . '"'; ?>>
		<div class="b-notice">
			<?php $trust_box(); ?>
		</div>
	</section>
<?php endif; ?>
