<?php
/**
 * Первый экран списка (макет seo-blog/blog.html): хлебные крошки, H1, подзаголовок. Белый фон,
 * открывает секцию — содержимое ниже (главная статья) выводит шаблон до закрытия секции.
 *
 * Аргументы get_template_part(): title — H1, lead — подзаголовок ('' — без него).
 *
 * @package Trust
 */

use Trust\Breadcrumbs;

defined( 'ABSPATH' ) || exit;

$trust_title = (string) ( $args['title'] ?? '' );
$trust_lead  = (string) ( $args['lead'] ?? '' );
?>
<?php Breadcrumbs::render( 'archive-hero__crumbs' ); ?>
<div class="archive-hero__head">
	<h1 class="archive-hero__title"><?php echo esc_html( $trust_title ); ?></h1>
	<?php if ( '' !== $trust_lead ) : ?>
		<p class="archive-hero__lead"><?php echo esc_html( $trust_lead ); ?></p>
	<?php endif; ?>
</div>
