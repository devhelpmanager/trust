<?php
/**
 * Блок «Пример галочки согласия» (макет o-nas/soglasie-pd.html, «Как выглядит согласие в форме»).
 *
 * Белая карточка: подпись, нарисованная форма (поле, отмеченная галочка с подчёркнутой ссылкой,
 * кнопка; для скринридера скрыта — это картинка, смысл передаёт пояснение) и пояснение. Рядом с
 * «Коротко о главном», если стоит сразу после него (Trust\Document::build()). При печати не выводится.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_note = trim( (string) get_field( 'note' ) );
?>
<figure class="b-consent-example">
	<figcaption class="b-consent-example__caption"><?php echo esc_html( Blocks::text( 'caption', __( 'Как выглядит согласие в форме', 'trust' ) ) ); ?></figcaption>
	<div class="b-consent-example__form" aria-hidden="true">
		<span class="b-consent-example__field"><?php echo esc_html( Blocks::text( 'field', __( 'Имя', 'trust' ) ) ); ?></span>
		<span class="b-consent-example__check">
			<span class="b-consent-example__box">
				<?php
				Icons::render(
					'check',
					array(
						'size'         => 12,
						'stroke_width' => 4,
					)
				);
				?>
			</span>
			<span><?php echo esc_html( Blocks::text( 'check_text', __( 'Я даю', 'trust' ) ) ); ?> <u><?php echo esc_html( Blocks::text( 'check_link', __( 'согласие на обработку персональных данных', 'trust' ) ) ); ?></u></span>
		</span>
		<span class="b-consent-example__button"><?php echo esc_html( Blocks::text( 'button', __( 'Отправить', 'trust' ) ) ); ?></span>
	</div>
	<?php if ( '' !== $trust_note ) : ?>
		<p class="b-consent-example__note"><?php echo esc_html( $trust_note ); ?></p>
	<?php endif; ?>
</figure>
