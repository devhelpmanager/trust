<?php
/**
 * Плашка «Не нашли ответ?» под списком статей рубрики (макет portfolio-blog-404/rubrika.html):
 * значок и справа от него заголовок, под ними во всю ширину — текст, белая кнопка «Задать вопрос»
 * в правом нижнем углу (правка заказчика; в макете текст справа от значка, кнопка справа по
 * центру). Тексты — «Настройки сайта → Блоки по умолчанию», адрес — «Страница кнопки» блока
 * «Задать вопрос SEO-специалисту», иначе страница типа «Вопросы SEO». Нет страницы — нет плашки.
 *
 * @package Trust
 */

use Trust\Block_Texts;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_url = Block_Texts::page_url( 'ask_page', 'questions' );
if ( '' === $trust_url ) {
	return;
}
$trust_text = Block_Texts::get( 'teaser_text' );
?>
<div class="ask-teaser">
	<span class="ask-teaser__icon" aria-hidden="true"><?php Icons::render( 'chat-question', array( 'size' => 24 ) ); ?></span>
	<p class="ask-teaser__title"><?php echo esc_html( Block_Texts::get( 'teaser_title' ) ); ?></p>
	<?php if ( '' !== $trust_text ) : ?>
		<p class="ask-teaser__text"><?php echo esc_html( $trust_text ); ?></p>
	<?php endif; ?>
	<a class="ask-teaser__btn" href="<?php echo esc_url( $trust_url ); ?>">
		<?php echo esc_html( Block_Texts::get( 'teaser_button' ) ); ?>
		<?php
		Icons::render(
			'arrow-right',
			array(
				'size'         => 18,
				'stroke_width' => 2.2,
			)
		);
		?>
	</a>
</div>
