<?php
/**
 * Главная статья (макет seo-blog/blog.html): крупная карточка на всю ширину — обложка слева,
 * справа плашки «Новая статья» и рубрика, заголовок H2, анонс, автор (фото или инициалы, имя,
 * должность), дата, время чтения, лайки, комментарии, кнопка «Читать полностью» в правом нижнем
 * углу (правка заказчика). Вся карточка — ссылка на статью. Подписи — «Настройки сайта → Тексты
 * шаблонов».
 *
 * Вид «рубрика» (макет portfolio-blog-404/rubrika.html): вместо плашки рубрики — два тега, без
 * автора (рядом с заголовком рубрики — «Ведёт рубрику»).
 *
 * Аргументы get_template_part(): post (WP_Post), variant ('' | 'rubric').
 *
 * @package Trust
 */

use Trust\Blog;
use Trust\Icons;
use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

$trust_post = $args['post'] ?? null;
if ( ! $trust_post instanceof WP_Post ) {
	return;
}
$trust_in_rub = 'rubric' === ( $args['variant'] ?? '' );
$trust_rubric = $trust_in_rub ? null : Blog::rubric( $trust_post );
$trust_author = $trust_in_rub ? array( 'name' => '' ) : Blog::author( $trust_post );
$trust_time   = Blog::reading_time( $trust_post );
?>
<a class="post-feature" href="<?php echo esc_url( get_permalink( $trust_post ) ); ?>">
	<span class="post-feature__media">
		<?php
		echo has_post_thumbnail( $trust_post )
			? get_the_post_thumbnail(
				$trust_post,
				'trust-cover-lg',
				array(
					'class'         => 'post-feature__img',
					'alt'           => '',
					'fetchpriority' => 'high',
					'loading'       => 'eager',
					'sizes'         => '(max-width: 1023px) calc(100vw - 40px), 660px',
				)
			)
			: '<span class="post-card__placeholder"></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка WordPress.
		?>
	</span>
	<span class="post-feature__body">
		<span class="post-feature__badges">
			<span class="post-feature__badge"><?php echo esc_html( Template_Texts::get( 'blog_new' ) ); ?></span>
			<?php if ( $trust_rubric ) : ?>
				<span class="post-rubric"><?php echo esc_html( $trust_rubric->name ); ?></span>
			<?php endif; ?>
			<?php echo $trust_in_rub ? Blog::tags_html( Blog::card_tags( $trust_post ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в Blog::tags_html(). ?>
		</span>
		<h2 class="post-feature__title"><?php echo esc_html( get_the_title( $trust_post ) ); ?></h2>
		<?php if ( has_excerpt( $trust_post ) ) : ?>
			<span class="post-feature__excerpt"><?php echo esc_html( get_the_excerpt( $trust_post ) ); ?></span>
		<?php endif; ?>
		<?php if ( '' !== $trust_author['name'] ) : ?>
			<span class="post-author">
				<?php if ( $trust_author['photo'] ) : ?>
					<?php
					echo wp_get_attachment_image(
						$trust_author['photo'],
						'thumbnail',
						false,
						array(
							'class'   => 'post-author__photo',
							'alt'     => '',
							'loading' => 'lazy',
						)
					);
					?>
				<?php else : ?>
					<span class="post-author__photo" aria-hidden="true"><?php echo esc_html( $trust_author['initials'] ); ?></span>
				<?php endif; ?>
				<span class="post-author__who">
					<span class="post-author__name"><?php echo esc_html( $trust_author['name'] ); ?></span>
					<?php if ( '' !== $trust_author['role'] ) : ?>
						<span class="post-author__role"><?php echo esc_html( $trust_author['role'] ); ?></span>
					<?php endif; ?>
				</span>
			</span>
		<?php endif; ?>
		<span class="post-feature__meta">
			<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $trust_post ) ); ?>"><?php echo esc_html( Blog::date( $trust_post ) ); ?></time>
			<?php
			/* translators: %d: минуты. */
			echo Blog::stat( 'clock', sprintf( __( '%d мин чтения', 'trust' ), $trust_time ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в Blog::stat().
			if ( Blog::show_likes() ) {
				echo Blog::stat( 'heart', (string) Blog::likes( $trust_post ), __( 'Лайки', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в Blog::stat().
			}
			echo Blog::stat( 'chat', (string) get_comments_number( $trust_post ), __( 'Комментарии', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в Blog::stat().
			?>
		</span>
		<span class="btn post-feature__btn">
			<?php echo esc_html( Template_Texts::get( 'blog_read' ) ); ?>
			<?php
			Icons::render(
				'arrow-right',
				array(
					'size'         => 16,
					'stroke_width' => 2,
				)
			);
			?>
		</span>
	</span>
</a>
