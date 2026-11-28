<?php
/**
 * Список статей блога (ТЗ, раздел 6.2; макет seo-blog/blog.html) — страница, назначенная
 * «Страницей записей» (Настройки → Чтение). Блоки этой страницы не выводятся: H1 — её SEO-поле
 * или заголовок, подзаголовок — поле «Подзаголовок».
 *
 * Хлебные крошки, H1, подзаголовок; на первой странице — главная статья («Главная статья»
 * или самая новая; в сетке не повторяется); «Все статьи» и «Страница N из M», под ними — полоса
 * рубрик (правка заказчика, как на странице рубрики); карточки —
 * первые две крупные (2 в ряд), остальные по 3 в ряд; пагинация /blog/page/N/. Статей на
 * странице — «Настройки сайта → Блог» (Trust\Blog::list_query()). Микроразметка —
 * CollectionPage + ItemList показанных статей.
 *
 * @package Trust
 */

use Trust\Blog;
use Trust\Pagination;
use Trust\Schema\Graph;
use Trust\Seo;
use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

global $wp_query;

$trust_page_id  = (int) get_option( 'page_for_posts' );
$trust_paged    = max( 1, (int) get_query_var( 'paged' ) );
$trust_pages    = max( 1, (int) $wp_query->max_num_pages );
$trust_featured = 1 === $trust_paged ? Blog::featured() : null;
$trust_posts    = $wp_query->posts;
$trust_lead     = $trust_page_id ? trim( (string) get_post_meta( $trust_page_id, 'trust_blog_lead', true ) ) : '';

// Микроразметка: главная статья и карточки страницы — ItemList списка.
foreach ( array_merge( $trust_featured ? array( $trust_featured ) : array(), $trust_posts ) as $trust_item ) {
	Graph::item( (string) get_permalink( $trust_item ), get_the_title( $trust_item ) );
}

get_header();
?>
<section class="archive-hero">
	<div class="archive-hero__inner">
		<?php
		get_template_part(
			'template-parts/archive/hero',
			null,
			array(
				'title' => $trust_page_id ? Seo::h1( $trust_page_id ) : __( 'Блог', 'trust' ),
				'lead'  => $trust_lead,
			)
		);
		if ( $trust_featured ) {
			get_template_part( 'template-parts/cards/post-featured', null, array( 'post' => $trust_featured ) );
		}
		?>
	</div>
</section>

<section class="post-list" id="posts" aria-labelledby="posts-title">
	<div class="post-list__inner">
		<div class="post-list__head">
			<h2 class="section-title post-list__title" id="posts-title"><?php echo esc_html( Template_Texts::get( 'blog_all' ) ); ?></h2>
			<?php if ( $trust_posts ) : ?>
				<?php /* translators: 1: номер страницы, 2: всего страниц. */ ?>
				<span class="post-list__page"><?php echo esc_html( sprintf( __( 'Страница %1$d из %2$d', 'trust' ), $trust_paged, $trust_pages ) ); ?></span>
			<?php endif; ?>
		</div>
		<?php
		// Правка заказчика: рубрики блога — между «Все статьи» и карточками (выделены «Все статьи»).
		get_template_part( 'template-parts/blog/rubric-strip', null, array( 'current' => 0 ) );
		?>
		<?php if ( $trust_posts ) : ?>
			<?php
			// Первые две карточки — крупные, остальные — по 3 в ряд.
			$trust_groups = array_filter(
				array(
					'lg' => array_slice( $trust_posts, 0, 2 ),
					'sm' => array_slice( $trust_posts, 2 ),
				)
			);
			foreach ( $trust_groups as $trust_size => $trust_group ) :
				?>
				<ul class="post-cards post-cards--<?php echo esc_attr( $trust_size ); ?>">
					<?php
					foreach ( $trust_group as $trust_item ) {
						get_template_part(
							'template-parts/cards/post',
							null,
							array(
								'post'  => $trust_item,
								'size'  => $trust_size,
								'sizes' => 'lg' === $trust_size ? '(max-width: 760px) calc(100vw - 40px), 600px' : '(max-width: 640px) 112px, (max-width: 1023px) 50vw, 400px',
							)
						);
					}
					?>
				</ul>
			<?php endforeach; ?>
			<?php echo Pagination::render( $trust_paged, $trust_pages, 'posts', array( 'nav' => __( 'Страницы блога', 'trust' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в Pagination::render(). ?>
		<?php else : ?>
			<p class="post-list__empty"><?php esc_html_e( 'Статей пока нет.', 'trust' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
