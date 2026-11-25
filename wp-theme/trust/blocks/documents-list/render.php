<?php
/**
 * Блок «Другие документы» (макеты o-nas/oferta.html, politika-cookie.html, soglasie-pd.html).
 *
 * Над блоком линия, H2 (в содержание не попадает) и карточки-ссылки по сетке (от 250 px в
 * колонку): значок, название и подпись — из группы полей «Документ» каждой страницы
 * (Trust\Document::card()). Автоматически — все опубликованные документы, кроме текущего, по
 * полю «Порядок»; или выбранные вручную. При печати не выводится.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Document;
use Trust\Icons;
use Trust\Page_Types;

defined( 'ABSPATH' ) || exit;

$trust_current = (int) $post_id;
if ( 'manual' === get_field( 'mode' ) ) {
	$trust_ids = array_values(
		array_filter(
			array_map( 'intval', (array) get_field( 'pages' ) ),
			static fn( int $id ): bool => $id !== $trust_current && 'publish' === get_post_status( $id )
		)
	);
} else {
	$trust_ids = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'ID'         => 'ASC',
			),
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'post__not_in'   => array( $trust_current ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- одна страница, список короткий.
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- документы по шаблону.
			'meta_value'     => Page_Types::template( 'document' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- документы по шаблону.
		)
	);
}

if ( ! $trust_ids ) {
	if ( $is_preview ) {
		echo Blocks::editor_warning( __( 'Других опубликованных документов пока нет.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning().
	}
	return;
}

$trust_title_id = Blocks::uid( $block, 'title' );
?>
<section class="b-docs" aria-labelledby="<?php echo esc_attr( $trust_title_id ); ?>">
	<h2 class="b-docs__title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( Blocks::text( 'title', __( 'Другие документы', 'trust' ) ) ); ?></h2>
	<ul class="b-docs__list">
		<?php foreach ( $trust_ids as $trust_id ) : ?>
			<?php $trust_card = Document::card( (int) $trust_id ); ?>
			<li>
				<a class="b-docs__card" href="<?php echo esc_url( get_permalink( $trust_id ) ); ?>"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>>
					<span class="b-docs__icon" aria-hidden="true">
						<?php
						Icons::render(
							$trust_card['icon'],
							array(
								'size'         => 20,
								'stroke_width' => 1.9,
							)
						);
						?>
					</span>
					<span class="b-docs__text">
						<span class="b-docs__name"><?php echo esc_html( $trust_card['title'] ); ?></span>
						<?php if ( '' !== $trust_card['note'] ) : ?>
							<span class="b-docs__note"><?php echo esc_html( $trust_card['note'] ); ?></span>
						<?php endif; ?>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
