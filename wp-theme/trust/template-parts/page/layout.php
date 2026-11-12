<?php
/**
 * Разметка страницы любого типа: блоки во всю ширину; если блока первого экрана нет —
 * стандартная шапка страницы с H1 (ТЗ, раздел 6.3). Хлебные крошки и H1 из SEO-поля —
 * шаги 3.2 и 3.1.
 *
 * Шаблоны типов (templates/*.php) и page.php подключают этот файл; особенности типа
 * (например, содержание сбоку у документа) добавляются на шагах блоков.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'page-entry' ); ?>>
		<?php if ( ! Trust\Page_Types::has_hero() ) : ?>
			<header class="page-default">
				<div class="container">
					<h1 class="page-default__title"><?php the_title(); ?></h1>
				</div>
			</header>
		<?php endif; ?>
		<div class="page-entry__content">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
