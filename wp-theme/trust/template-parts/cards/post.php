<?php
/**
 * Карточка статьи (макет seo-blog/blog.html, «Все статьи»): обложка, рубрика, заголовок H3,
 * анонс (только у крупной), дата, лайки, комментарии. Вся карточка — ссылка на статью.
 * Крупная — 2 в ряд (обложка 16 : 9), обычная — 3 в ряд (16 : 10), на телефоне обычная —
 * обложка слева, текст справа.
 *
 * Аргументы get_template_part(): post (WP_Post), size ('lg' | 'sm'), sizes (атрибут sizes обложки).
 *
 * @package Trust
 */

use Trust\Blog;

defined( 'ABSPATH' ) || exit;

$trust_post = $args['post'] ?? null;
if ( ! $trust_post instanceof WP_Post ) {
	return;
}
$trust_size   = 'lg' === ( $args['size'] ?? '' ) ? 'lg' : 'sm';
$trust_rubric = Blog::rubric( $trust_post );
$trust_count  = (int) get_comments_number( $trust_post );
?>
<li class="post-cards__item">
	<a class="post-card post-card--<?php echo esc_attr( $trust_size ); ?>" href="<?php echo esc_url( get_permalink( $trust_post ) ); ?>">
		<span class="post-card__cover">
			<?php
			echo has_post_thumbnail( $trust_post )
				? get_the_post_thumbnail(
					$trust_post,
					'lg' === $trust_size ? 'trust-cover-lg' : 'trust-cover',
					array(
						'class'   => 'post-card__img',
						'alt'     => '',
						'loading' => 'lazy',
						'sizes'   => (string) ( $args['sizes'] ?? '(max-width: 640px) 112px, (max-width: 1023px) 50vw, 400px' ),
					)
				)
				: '<span class="post-card__placeholder"></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка WordPress.
			?>
		</span>
		<span class="post-card__body">
			<?php if ( $trust_rubric ) : ?>
				<span class="post-rubric"><?php echo esc_html( $trust_rubric->name ); ?></span>
			<?php endif; ?>
			<h3 class="post-card__title"><?php echo esc_html( get_the_title( $trust_post ) ); ?></h3>
			<?php if ( 'lg' === $trust_size && has_excerpt( $trust_post ) ) : ?>
				<span class="post-card__excerpt"><?php echo esc_html( get_the_excerpt( $trust_post ) ); ?></span>
			<?php endif; ?>
			<span class="post-card__meta">
				<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $trust_post ) ); ?>"><?php echo esc_html( Blog::date( $trust_post ) ); ?></time>
				<span class="post-card__stats">
					<?php
					if ( Blog::show_likes() ) {
						echo Blog::stat( 'heart', (string) Blog::likes( $trust_post ), __( 'Лайки', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в Blog::stat().
					}
					echo Blog::stat( 'chat', (string) $trust_count, __( 'Комментарии', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в Blog::stat().
					?>
				</span>
			</span>
		</span>
	</a>
</li>
