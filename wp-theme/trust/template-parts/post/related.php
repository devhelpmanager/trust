<?php
/**
 * «Читайте также» под статьёй (макет seo-blog/statya.html, RecentPosts variant="related"):
 * лавандовая секция, надзаголовок «Блог», H2, кнопка «Все статьи»; три карточки статей
 * (Trust\Article::related(): общие теги, затем рубрика, затем новые) — в ряд, на узком экране
 * листаются вбок. Тексты — «Настройки сайта → Тексты шаблонов». Нет других статей — не выводится.
 *
 * Аргументы get_template_part(): post (WP_Post).
 *
 * @package Trust
 */

use Trust\Article;
use Trust\Blog;
use Trust\Icons;
use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

$trust_post = $args['post'] ?? null;
if ( ! $trust_post instanceof WP_Post ) {
	return;
}
$trust_related = Article::related( $trust_post, 3 );
if ( ! $trust_related ) {
	return;
}
$trust_eyebrow = Template_Texts::get( 'post_related_label' );
$trust_more    = Template_Texts::get( 'post_related_more' );
?>
<section class="post-related trust-bg-lavender" aria-labelledby="post-related-title">
	<div class="post-related__inner">
		<div class="post-related__top">
			<div class="post-related__head">
				<?php if ( '' !== $trust_eyebrow ) : ?>
					<span class="eyebrow"><?php echo esc_html( $trust_eyebrow ); ?></span>
				<?php endif; ?>
				<h2 class="post-related__title" id="post-related-title"><?php echo esc_html( Template_Texts::get( 'post_related' ) ); ?></h2>
			</div>
			<?php if ( '' !== $trust_more ) : ?>
				<a class="post-related__all" href="<?php echo esc_url( Blog::index_url() ); ?>">
					<?php echo esc_html( $trust_more ); ?>
					<?php Icons::render( 'arrow-right', array( 'size' => 16 ) ); ?>
				</a>
			<?php endif; ?>
		</div>
		<ul class="post-cards post-related__list" aria-label="<?php esc_attr_e( 'Статьи', 'trust' ); ?>">
			<?php
			foreach ( $trust_related as $trust_item ) {
				get_template_part(
					'template-parts/cards/post',
					null,
					array(
						'post'  => $trust_item,
						'size'  => 'sm',
						'sizes' => '(max-width: 760px) 82vw, 384px',
					)
				);
			}
			?>
		</ul>
	</div>
</section>
