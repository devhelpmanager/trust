<?php
/**
 * Кнопка «Нравится» статьи: сердце и число лайков. Нажатая (aria-pressed="true") — залитая.
 * Состояние браузера и числа ставит assets/js/post.js (страница может прийти из кеша, поэтому
 * сервер всегда выводит «не нажата»). Скрыта, если лайки выключены в «Настройках сайта → Блог».
 *
 * Аргументы get_template_part(): post (WP_Post), class (дополнительный класс).
 *
 * @package Trust
 */

use Trust\Article;
use Trust\Blog;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_post = $args['post'] ?? null;
if ( ! $trust_post instanceof WP_Post || ! Blog::show_likes() ) {
	return;
}
?>
<button type="button" class="post-like <?php echo esc_attr( (string) ( $args['class'] ?? '' ) ); ?>" aria-pressed="false" data-post-like>
	<?php
	Icons::render(
		'heart',
		array(
			'size'  => 18,
			'class' => 'post-like__icon',
		)
	);
	?>
	<span class="screen-reader-text"><?php esc_html_e( 'Нравится:', 'trust' ); ?> </span>
	<span data-post-count="likes-number"><?php echo esc_html( Article::number( Blog::likes( $trust_post ) ) ); ?></span>
</button>
