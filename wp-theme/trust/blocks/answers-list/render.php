<?php
/**
 * Блок «Ответы SEO-специалистов» (макет seo-vopros/vopros-seo.html, секция «Ответы SEO-специалистов»).
 *
 * Шапка секции (справа — рисунок на выбор, на экранах уже 900 px скрыт) и карточки опубликованных
 * вопросов, новые первыми (Trust\Answers_List): тема, «Спрашивает Имя», вопрос (H3), начало ответа
 * (на телефоне 3 строки), инициалы и имя специалиста, «ответ от» дата, «Читать ответ» (не помещается
 * рядом с именем — «Ответ», затем только стрелка). Две колонки от 800 px. Страницы — ссылками /page/N/#answers:
 * «‹ Назад 1 2 3 4 … 7 Вперёд ›», на узких экранах номера сокращаются (до 340 px — «‹ 1 … 7 ›»).
 * Страница за пределами списка — 404. Показанные ответы — ItemList в микроразметке.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Answers_List;
use Trust\Blocks;

defined( 'ABSPATH' ) || exit;

$trust_per      = Answers_List::per_page( get_field( 'per_page' ) );
$trust_paged    = $is_preview ? 1 : max( 1, (int) get_query_var( 'paged' ) );
$trust_page     = Answers_List::query( $trust_paged, $is_preview ? min( 4, $trust_per ) : $trust_per );
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_anchor   = '' !== $trust_anchor ? $trust_anchor : 'answers';
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_labels   = array(
	'asks'     => Blocks::text( 'asks_label', __( 'Спрашивает', 'trust' ) ),
	'answered' => Blocks::text( 'answered_label', __( 'ответ от', 'trust' ) ),
	'more'     => Blocks::text( 'more_label', __( 'Читать ответ', 'trust' ) ),
	'short'    => Blocks::text( 'more_short_label', __( 'Ответ', 'trust' ) ),
);

// Шапка секции; справа — выбранный рисунок (на экранах уже 900 px скрыт, текст во всю ширину).
ob_start();
Blocks::section_head( 'b-answers__head', $trust_title_id );
$trust_head = (string) ob_get_clean();
$trust_art  = '' !== $trust_head ? Blocks::head_art( 'answers-list' ) : '';

// Микроразметка: показанные ответы — ItemList страницы «Вопросы SEO».
if ( ! $is_preview ) {
	foreach ( $trust_page['ids'] as $trust_id ) {
		Trust\Schema\Graph::item( (string) get_permalink( $trust_id ), get_the_title( $trust_id ) );
	}
}
?>
<section class="b-answers trust-bg-white" id="<?php echo esc_attr( $trust_anchor ); ?>" aria-labelledby="<?php echo esc_attr( $trust_title_id ); ?>">
	<div class="b-answers__inner">
		<?php if ( '' !== $trust_art ) : ?>
			<div class="section-top b-answers__top">
				<?php echo $trust_head . $trust_art; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_head(), рисунок темы. ?>
			</div>
		<?php else : ?>
			<?php echo $trust_head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_head(). ?>
		<?php endif; ?>
		<?php if ( $trust_page['ids'] ) : ?>
			<ul class="b-answers__grid" aria-label="<?php esc_attr_e( 'Ответы на вопросы', 'trust' ); ?>">
				<?php
				foreach ( $trust_page['ids'] as $trust_id ) {
					echo Answers_List::card( $trust_id, $trust_labels ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в card().
				}
				?>
			</ul>
			<?php
			if ( ! $is_preview ) {
				$trust_pages = Trust\Pagination::render( $trust_paged, $trust_page['pages'], $trust_anchor, array( 'nav' => __( 'Страницы ответов', 'trust' ) ) );
				echo $trust_pages; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в Pagination::render().
			}
			?>
		<?php else : ?>
			<p class="b-answers__empty"><?php echo esc_html( Blocks::text( 'empty_text', __( 'Ответов пока нет. Задайте первый вопрос — специалист ответит здесь.', 'trust' ) ) ); ?></p>
		<?php endif; ?>
	</div>
</section>
