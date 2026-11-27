<?php
/**
 * Под текстом статьи (макет seo-blog/statya.html): «Ключевые слова» — теги статьи ссылками,
 * справа — «Статья была полезной?» и кнопка «Нравится». Нет тегов и лайки выключены — не
 * выводится.
 *
 * Аргументы get_template_part(): post (WP_Post), tags (WP_Term[]).
 *
 * @package Trust
 */

use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

$trust_post = $args['post'] ?? null;
if ( ! $trust_post instanceof WP_Post ) {
	return;
}
$trust_tags = (array) ( $args['tags'] ?? array() );
ob_start();
get_template_part( 'template-parts/post/like', null, array( 'post' => $trust_post ) );
$trust_like = trim( (string) ob_get_clean() );
if ( ! $trust_tags && '' === $trust_like ) {
	return;
}
$trust_label = Template_Texts::get( 'post_tags' );
?>
<div class="post-foot">
	<?php if ( $trust_tags ) : ?>
		<div class="post-foot__tags">
			<span class="post-foot__label" aria-hidden="true"><?php echo esc_html( $trust_label ); ?></span>
			<ul class="post-tags" aria-label="<?php echo esc_attr( $trust_label ); ?>">
				<?php foreach ( $trust_tags as $trust_term ) : ?>
					<li><a class="post-tag" href="<?php echo esc_url( get_term_link( $trust_term ) ); ?>"><?php echo esc_html( $trust_term->name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
	<?php if ( '' !== $trust_like ) : ?>
		<div class="post-foot__useful">
			<span class="post-foot__question"><?php echo esc_html( Template_Texts::get( 'post_useful' ) ); ?></span>
			<?php echo $trust_like; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка template-parts/post/like.php. ?>
		</div>
	<?php endif; ?>
</div>
