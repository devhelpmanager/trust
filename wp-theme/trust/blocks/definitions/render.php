<?php
/**
 * Блок «Список определений» (макеты документов): <dl> со строками «название — значение».
 * «Линии» — термины (название жирным, строки через линию, как «Термины» оферты); «Карточка» —
 * данные и контакты в рамке (название серым, значение жирным, как «Оператор» согласия).
 * Email и адреса сайтов в значении — ссылками (make_clickable).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;

defined( 'ABSPATH' ) || exit;

$trust_view = 'card' === get_field( 'view' ) ? 'card' : 'lines';
$trust_rows = array();
foreach ( (array) get_field( 'items' ) as $trust_row ) {
	$trust_term  = trim( (string) ( $trust_row['term'] ?? '' ) );
	$trust_value = trim( (string) ( $trust_row['value'] ?? '' ) );
	if ( '' !== $trust_term && '' !== $trust_value ) {
		$trust_rows[] = array( $trust_term, $trust_value );
	}
}
if ( ! $trust_rows ) {
	if ( $is_preview ) {
		echo Blocks::editor_warning( __( 'Добавьте строки в настройках блока.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning().
	}
	return;
}
?>
<dl class="b-defs b-defs--<?php echo esc_attr( $trust_view ); ?>">
	<?php foreach ( $trust_rows as list( $trust_term, $trust_value ) ) : ?>
		<div class="b-defs__row">
			<dt><?php echo esc_html( $trust_term ); ?></dt>
			<dd>
			<?php
			echo wp_kses(
				make_clickable( $trust_value ),
				array(
					'a'  => array(
						'href' => true,
						'rel'  => true,
					),
					'br' => array(),
				)
			);
			?>
				</dd>
		</div>
	<?php endforeach; ?>
</dl>
