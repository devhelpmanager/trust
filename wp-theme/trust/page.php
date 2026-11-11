<?php
/**
 * Страница: стандартная шапка страницы (H1) и блоки во всю ширину.
 *
 * Типы страниц (шаблоны с наборами блоков), хлебные крошки и H1 из SEO-поля появятся на
 * шагах 2.5, 3.1 и 3.2. Когда на странице будет блок первого экрана, он выведет H1 сам.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'page-entry' ); ?>>
		<header class="page-default">
			<div class="container">
				<h1 class="page-default__title"><?php the_title(); ?></h1>
			</div>
		</header>
		<div class="page-entry__content">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
