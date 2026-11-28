<?php
/**
 * Тег блога (ТЗ, раздел 6.2; макет portfolio-blog-404/teg.html).
 *
 * Первый экран (лавандовый): крошки, плашка «Тег», H1 «#тег», описание тега, «4 статьи и 3 ответа
 * на вопросы» — во всю ширину (как у рубрики после правки заказчика; в макете до 780 px). Ниже две
 * колонки: материалы — фильтр «Все / Статьи / Ответы на вопросы» с числом (когда есть и то и
 * другое), карточки статей и ответов, новые сверху, пагинация; сбоку (на компьютере едет вместе со
 * страницей) — рубрика, похожие теги, «Остался вопрос про …?». На телефоне боковая колонка — под
 * материалами. Данные и фильтр — Trust\Tag_Page, микроразметка — CollectionPage + ItemList.
 *
 * @package Trust
 */

use Trust\Breadcrumbs;
use Trust\Schema\Graph;
use Trust\Seo;
use Trust\Tag_Page;
use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

global $wp_query;

$trust_term = get_queried_object();
if ( ! $trust_term instanceof WP_Term ) {
	get_template_part( 'index' );
	return;
}
$trust_paged  = max( 1, (int) get_query_var( 'paged' ) );
$trust_pages  = max( 1, (int) $wp_query->max_num_pages );
$trust_type   = Tag_Page::type();
$trust_posts  = $wp_query->posts;
$trust_counts = Tag_Page::counts( $trust_term );
$trust_desc   = trim( wp_strip_all_tags( (string) $trust_term->description ) );
$trust_h1     = Seo::h1( $trust_term );
$trust_total  = $trust_counts['posts'] + $trust_counts['answers'];

foreach ( $trust_posts as $trust_item ) {
	Graph::item( (string) get_permalink( $trust_item ), get_the_title( $trust_item ) );
}

get_header();
?>
<section class="tag-hero" aria-labelledby="tag-title">
	<div class="tag-hero__inner">
		<?php Breadcrumbs::render( 'archive-hero__crumbs' ); ?>
		<div class="tag-hero__head">
			<span class="tag-hero__label"><?php echo esc_html( Template_Texts::get( 'tag_label' ) ); ?></span>
			<h1 class="tag-hero__title" id="tag-title"><span class="tag-hero__hash" aria-hidden="true">#</span><?php echo esc_html( ltrim( $trust_h1, '#' ) ); ?></h1>
			<?php if ( '' !== $trust_desc ) : ?>
				<p class="tag-hero__lead"><?php echo esc_html( $trust_desc ); ?></p>
			<?php endif; ?>
			<?php if ( $trust_total ) : ?>
				<p class="tag-hero__meta"><?php echo esc_html( Tag_Page::counts_text( $trust_counts ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>

<div class="tag-body">
	<div class="tag-body__inner">
		<section class="tag-main" id="<?php echo esc_attr( Tag_Page::ANCHOR ); ?>" aria-labelledby="tag-list-title">
			<?php /* translators: %s: название тега. */ ?>
			<h2 class="screen-reader-text" id="tag-list-title"><?php echo esc_html( sprintf( __( 'Материалы с тегом %s', 'trust' ), $trust_term->name ) ); ?></h2>
			<?php if ( $trust_counts['posts'] && $trust_counts['answers'] ) : ?>
				<div class="tag-filter" role="group" aria-label="<?php esc_attr_e( 'Тип материалов', 'trust' ); ?>">
					<?php
					foreach ( array(
						'all'     => array( 'tag_all', $trust_total ),
						'posts'   => array( 'tag_posts', $trust_counts['posts'] ),
						'answers' => array( 'tag_answers', $trust_counts['answers'] ),
					) as $trust_key => $trust_filter ) :
						?>
						<a class="tag-filter__item" href="<?php echo esc_url( Tag_Page::url( $trust_term, 1, $trust_key ) . '#' . Tag_Page::ANCHOR ); ?>" data-tag-type="<?php echo esc_attr( $trust_key ); ?>"<?php echo $trust_key === $trust_type ? ' aria-current="true"' : ''; ?>><?php echo esc_html( Template_Texts::get( $trust_filter[0] ) ); ?><span class="tag-filter__count"><?php echo esc_html( (string) $trust_filter[1] ); ?></span></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="tag-main__list" data-tag-list aria-live="polite">
				<?php echo Tag_Page::list_html( $trust_term, $trust_posts, $trust_paged, $trust_pages, $trust_type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в Tag_Page::list_html(). ?>
			</div>
		</section>
		<?php get_template_part( 'template-parts/blog/tag-aside', null, array( 'term' => $trust_term ) ); ?>
	</div>
</div>
<?php
get_footer();
