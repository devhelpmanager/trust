<?php
/**
 * Блок «Коротко о главном» (макеты o-nas/oferta.html, politika-cookie.html, soglasie-pd.html).
 *
 * Лавандовая карточка: H2 (в содержание документа не попадает), пункты со значками (по умолчанию
 * галочки; в тексте можно ссылку) и примечание мелким шрифтом. Если следом стоит «Пример галочки
 * согласия», на компьютере они рядом (Trust\Document::build()).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_title_id = Blocks::uid( $block, 'title' );
$trust_title    = Blocks::text( 'title', __( 'Коротко о главном', 'trust' ) );
$trust_note     = trim( (string) get_field( 'note' ) );
$trust_items    = array();
foreach ( (array) get_field( 'items' ) as $trust_row ) {
	$trust_text = Blocks::inline( $trust_row['text'] ?? '' );
	if ( '' !== wp_strip_all_tags( $trust_text ) ) {
		$trust_items[] = array( (string) ( $trust_row['icon'] ?? '' ), $trust_text );
	}
}
if ( ! $trust_items && ! $is_preview ) {
	return;
}
?>
<aside class="b-summary" aria-labelledby="<?php echo esc_attr( $trust_title_id ); ?>">
	<h2 class="b-summary__title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
	<?php if ( $trust_items ) : ?>
		<ul class="b-summary__list">
			<?php foreach ( $trust_items as list( $trust_icon, $trust_text ) ) : ?>
				<li>
					<?php
					Icons::render(
						Icons::exists( $trust_icon ) ? $trust_icon : 'check',
						array(
							'size'         => 18,
							'stroke_width' => 2.4,
							'class'        => 'b-summary__icon',
						)
					);
					?>
					<span><?php echo $trust_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Blocks::inline() (wp_kses). ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<?php echo Blocks::editor_warning( __( 'Добавьте пункты в настройках блока.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning(). ?>
	<?php endif; ?>
	<?php if ( '' !== $trust_note ) : ?>
		<p class="b-summary__note"><?php echo esc_html( $trust_note ); ?></p>
	<?php endif; ?>
</aside>
