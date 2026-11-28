<?php
/**
 * Плашка «Не нашли ответ?» под списком статей рубрики (макет portfolio-blog-404/rubrika.html):
 * значок, заголовок, текст и белая кнопка «Задать вопрос» на страницу вопросов. Тексты —
 * «Настройки сайта → Блоки по умолчанию», адрес — «Страница кнопки» блока «Задать вопрос
 * SEO-специалисту», иначе страница типа «Вопросы SEO». Нет страницы — нет плашки.
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
?>
<div class="ask-teaser">
	<div class="ask-teaser__main">
		<span class="ask-teaser__icon" aria-hidden="true"><?php Icons::render( 'chat-question', array( 'size' => 24 ) ); ?></span>
		<div class="ask-teaser__body">
			<p class="ask-teaser__title"><?php echo esc_html( Block_Texts::get( 'teaser_title' ) ); ?></p>
			<p class="ask-teaser__text"><?php echo esc_html( Block_Texts::get( 'teaser_text' ) ); ?></p>
		</div>
	</div>
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
