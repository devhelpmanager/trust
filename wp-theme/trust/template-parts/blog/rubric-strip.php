<?php
/**
 * Полоса рубрик (макет portfolio-blog-404/rubrika.html): «Все статьи N» (ведёт на блог) и все
 * непустые рубрики с числом статей, кроме рубрики по умолчанию, — в порядке создания; текущая
 * выделена. На телефоне — одной строкой с прокруткой (на странице рубрики текущая прокручивается
 * в видимую область — assets/js/rubric.js). Выводится на странице рубрики и в блоге — между «Все
 * статьи» и карточками (правка заказчика). Стили — assets/css/blog.css.
 *
 * Аргументы get_template_part(): current (ID текущей рубрики; 0 — блог, выделены «Все статьи»).
 *
 * @package Trust
 */

use Trust\Article;
use Trust\Blog;
use Trust\Rubric;

defined( 'ABSPATH' ) || exit;

$trust_strip   = Rubric::strip();
$trust_current = (int) ( $args['current'] ?? 0 );
if ( ! $trust_strip ) {
	return;
}
$trust_all = (int) ( wp_count_posts( 'post' )->publish ?? 0 );
?>
<nav class="rubric-strip" aria-label="<?php esc_attr_e( 'Рубрики блога', 'trust' ); ?>" data-rubric-strip>
	<a class="rubric-strip__item" href="<?php echo esc_url( Blog::index_url() ); ?>"<?php echo 0 === $trust_current ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Все статьи', 'trust' ); ?><span class="rubric-strip__count"><?php echo esc_html( Article::number( $trust_all ) ); ?></span></a>
	<?php foreach ( $trust_strip as $trust_item ) : ?>
		<a class="rubric-strip__item" href="<?php echo esc_url( get_term_link( $trust_item ) ); ?>"<?php echo $trust_item->term_id === $trust_current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $trust_item->name ); ?><span class="rubric-strip__count"><?php echo esc_html( Article::number( (int) $trust_item->count ) ); ?></span></a>
	<?php endforeach; ?>
</nav>
