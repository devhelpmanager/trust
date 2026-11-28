<?php
/**
 * Рубрика блога (ТЗ, раздел 6.2; макет portfolio-blog-404/rubrika.html).
 *
 * Первый экран (белый): крошки, плашка «Рубрика», H1 и описание рубрики во всю ширину (правка
 * заказчика; «Ведёт рубрику» убрано), «N статей · обновлено …», полоса рубрик, теги рубрики, главная
 * статья рубрики (на первой странице). Список (лавандовый): «Статьи рубрики» с сортировкой «Новые / Популярные», карточки
 * по 2 в ряд, пагинация, плашка «Не нашли ответ?». Внизу — раскрывающийся «О рубрике «…»» (поле
 * рубрики). Данные и сортировка — Trust\Rubric, микроразметка — CollectionPage + ItemList.
 *
 * @package Trust
 */

use Trust\Blog;
use Trust\Rubric;
use Trust\Schema\Graph;
use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

global $wp_query;

$trust_term = get_queried_object();
if ( ! $trust_term instanceof WP_Term ) {
	get_template_part( 'index' );
	return;
}
$trust_paged    = max( 1, (int) get_query_var( 'paged' ) );
$trust_pages    = max( 1, (int) $wp_query->max_num_pages );
$trust_sort     = Rubric::sort();
$trust_featured = 1 === $trust_paged ? Blog::featured( $trust_term->term_id ) : null;
$trust_posts    = $wp_query->posts;
$trust_about    = Rubric::about( $trust_term );

foreach ( array_merge( $trust_featured ? array( $trust_featured ) : array(), $trust_posts ) as $trust_item ) {
	Graph::item( (string) get_permalink( $trust_item ), get_the_title( $trust_item ) );
}

get_header();
?>
<section class="archive-hero rubric-hero">
	<div class="archive-hero__inner">
		<?php
		get_template_part( 'template-parts/blog/rubric-hero', null, array( 'term' => $trust_term ) );
		if ( $trust_featured ) {
			get_template_part(
				'template-parts/cards/post-featured',
				null,
				array(
					'post'    => $trust_featured,
					'variant' => 'rubric',
				)
			);
		}
		?>
	</div>
</section>

<section class="post-list rubric-list" id="<?php echo esc_attr( Rubric::ANCHOR ); ?>" aria-labelledby="posts-title">
	<div class="post-list__inner">
		<div class="post-list__head rubric-list__head">
			<h2 class="section-title post-list__title" id="posts-title"><?php echo esc_html( Template_Texts::get( 'rubric_list' ) ); ?></h2>
			<?php if ( $trust_posts ) : ?>
				<div class="rubric-sort" role="group" aria-label="<?php esc_attr_e( 'Сортировка', 'trust' ); ?>">
					<?php
					foreach ( array(
						'new'           => 'rubric_new',
						Rubric::POPULAR => 'rubric_popular',
					) as $trust_key => $trust_text ) :
						?>
						<a class="rubric-sort__item" href="<?php echo esc_url( Rubric::url( $trust_term, 1, $trust_key ) . '#' . Rubric::ANCHOR ); ?>" data-rubric-sort="<?php echo esc_attr( $trust_key ); ?>"<?php echo $trust_key === $trust_sort ? ' aria-current="true"' : ''; ?>><?php echo esc_html( Template_Texts::get( $trust_text ) ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="rubric-list__body" data-rubric-list aria-live="polite">
			<?php echo Rubric::list_html( $trust_term, $trust_posts, $trust_paged, $trust_pages, $trust_sort ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в Rubric::list_html(). ?>
		</div>
		<?php get_template_part( 'template-parts/blog/ask-teaser' ); ?>
	</div>
</section>

<?php if ( '' !== $trust_about ) : ?>
	<section class="rubric-about" aria-labelledby="rubric-about-title">
		<details class="rubric-about__box">
			<summary class="rubric-about__summary">
				<?php /* translators: 1: «О рубрике», 2: название рубрики. */ ?>
				<h2 class="rubric-about__title" id="rubric-about-title"><?php echo esc_html( sprintf( __( '%1$s «%2$s»', 'trust' ), Template_Texts::get( 'rubric_about' ), $trust_term->name ) ); ?></h2>
				<?php
				Trust\Icons::render(
					'chevron-down',
					array(
						'size'         => 22,
						'stroke_width' => 2.2,
						'class'        => 'rubric-about__chevron',
					)
				);
				?>
			</summary>
			<div class="rubric-about__text"><?php echo wp_kses_post( $trust_about ); ?></div>
		</details>
	</section>
<?php endif; ?>
<?php
get_footer();
