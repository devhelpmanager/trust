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
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class(); ?>>
					<?php if ( is_singular() ) : ?>
						<h1 class="page-default__title"><?php the_title(); ?></h1>
						<div class="page-default__content"><?php the_content(); ?></div>
					<?php else : ?>
						<h2 class="page-default__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php endif; ?>
				</article>
			<?php endwhile; ?>
		<?php else : ?>
			<h1 class="page-default__title"><?php esc_html_e( 'Ничего не найдено', 'trust' ); ?></h1>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
