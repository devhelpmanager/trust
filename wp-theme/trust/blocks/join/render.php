<?php
/**
 * Блок «Хотите в команду?» (макет o-nas/komanda.html, секция «Карьера»).
 *
 * Фиолетовая секция: слева надзаголовок, H2 и текст, справа две кнопки — белая со стрелкой
 * и контурная. Пустое поле «Кнопки» — «Открытые вакансии» (#vacancies) и «Отправить резюме»
 * (#resume) на странице типа «Карьера»; её нет — кнопок нет.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;
use Trust\Page_Types;

defined( 'ABSPATH' ) || exit;

$trust_classes = array( 'btn btn--white b-join__btn', 'btn btn--ghost b-join__btn' );
$trust_buttons = Blocks::buttons( get_field( 'buttons' ), $trust_classes );
if ( '' === $trust_buttons ) {
	$trust_career = Page_Types::url( 'career' );
	if ( '' !== $trust_career ) {
		$trust_buttons = sprintf(
			'<a class="%1$s" href="%2$s">%3$s%4$s</a><a class="%5$s" href="%6$s">%7$s</a>',
			esc_attr( $trust_classes[0] ),
			esc_url( $trust_career . '#vacancies' ),
			esc_html__( 'Открытые вакансии', 'trust' ),
			Icons::svg(
				'arrow-right',
				array(
					'size'         => 18,
					'stroke_width' => 2.2,
				)
			),
			esc_attr( $trust_classes[1] ),
			esc_url( $trust_career . '#resume' ),
			esc_html__( 'Отправить резюме', 'trust' )
		);
	}
}
$trust_title_id = Blocks::uid( $block, 'title' );
?>
<section class="b-join section--violet trust-bg-violet"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-join__inner">
		<?php Blocks::section_head( 'b-join__head', $trust_title_id ); ?>
		<?php if ( '' !== $trust_buttons ) : ?>
			<div class="b-join__buttons">
				<?php echo $trust_buttons; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- собрано с экранированием выше и в Blocks::buttons(). ?>
			</div>
		<?php elseif ( $is_preview ) : ?>
			<?php echo Blocks::editor_warning( __( 'Нет страницы типа «Карьера»: добавьте кнопки в настройках блока.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- текст экранирован в editor_warning(). ?>
		<?php endif; ?>
	</div>
</section>
