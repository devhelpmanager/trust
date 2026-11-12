<?php
/**
 * Шаблон по умолчанию: используется, если не нашёлся более точный.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="page-default">
	<div class="container">
		<?php
		Trust\Breadcrumbs::render( 'page-default__crumbs' );
		// Заголовок архива — H1; тогда «Ничего не найдено» ниже — обычный текст (один H1 на странице).
		$trust_archive_h1 = ! is_singular() && ( is_category() || is_tag() || is_search() || ( is_home() && ! is_front_page() ) );
		?>
		<?php if ( ! is_singular() && ( is_category() || is_tag() ) ) : ?>
			<h1 class="page-default__title"><?php echo esc_html( Trust\Seo::h1( get_queried_object() ) ); ?></h1>
		<?php elseif ( is_search() ) : ?>
			<?php /* translators: %s: поисковый запрос. */ ?>
			<h1 class="page-default__title"><?php echo esc_html( sprintf( __( 'Поиск: «%s»', 'trust' ), get_search_query( false ) ) ); ?></h1>
		<?php elseif ( is_home() && ! is_front_page() ) : ?>
			<h1 class="page-default__title"><?php echo esc_html( Trust\Seo::h1( (int) get_option( 'page_for_posts' ) ) ); ?></h1>
		<?php endif; ?>
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class(); ?>>
					<?php if ( is_singular() ) : ?>
						<h1 class="page-default__title"><?php echo esc_html( Trust\Seo::h1() ); ?></h1>
						<div class="page-default__content"><?php the_content(); ?></div>
					<?php else : ?>
						<h2 class="page-default__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php endif; ?>
				</article>
			<?php endwhile; ?>
		<?php elseif ( $trust_archive_h1 ) : ?>
			<p class="page-default__content"><?php esc_html_e( 'Ничего не найдено.', 'trust' ); ?></p>
		<?php else : ?>
			<h1 class="page-default__title"><?php esc_html_e( 'Ничего не найдено', 'trust' ); ?></h1>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
