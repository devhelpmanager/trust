<?php
/**
 * Комментарий статьи (макет seo-blog/statya.html): аватар-инициалы (автор статьи — фиолетовый
 * круг и плашка «Автор»), имя, дата, текст, «Нравится» с числом, «Ответить» (только у первого
 * уровня — ответы не глубже двух уровней), под ним — ответы.
 *
 * Аргументы get_template_part(): comment (WP_Comment), post (WP_Post), replies (WP_Comment[]),
 * reply (это ответ), open (комментарии открыты), class (дополнительный класс <li>).
 *
 * @package Trust
 */

use Trust\Article;
use Trust\Blog;
use Trust\Comments;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_comment = $args['comment'] ?? null;
$trust_post    = $args['post'] ?? null;
if ( ! $trust_comment instanceof WP_Comment || ! $trust_post instanceof WP_Post ) {
	return;
}
$trust_id      = (int) $trust_comment->comment_ID;
$trust_reply   = ! empty( $args['reply'] );
$trust_replies = (array) ( $args['replies'] ?? array() );
$trust_avatar  = Comments::avatar( $trust_comment, $trust_post );
$trust_class   = trim( 'comment ' . ( $trust_reply ? 'comment--reply ' : '' ) . ( $args['class'] ?? '' ) );
?>
<li class="<?php echo esc_attr( $trust_class ); ?>" id="comment-<?php echo esc_attr( (string) $trust_id ); ?>">
	<span class="comment__avatar<?php echo $trust_avatar['author'] ? ' comment__avatar--author' : ''; ?>" aria-hidden="true" style="--avatar-bg: <?php echo esc_attr( $trust_avatar['bg'] ); ?>; --avatar-fg: <?php echo esc_attr( $trust_avatar['fg'] ); ?>"><?php echo esc_html( $trust_avatar['initials'] ); ?></span>
	<div class="comment__body">
		<div class="comment__head">
			<span class="comment__author"><?php echo esc_html( $trust_comment->comment_author ); ?></span>
			<?php if ( $trust_avatar['author'] ) : ?>
				<span class="comment__badge"><?php esc_html_e( 'Автор', 'trust' ); ?></span>
			<?php endif; ?>
			<time class="comment__date" datetime="<?php echo esc_attr( (string) wp_date( 'Y-m-d', (int) strtotime( $trust_comment->comment_date_gmt . ' UTC' ) ) ); ?>"><?php echo esc_html( Comments::date( $trust_comment ) ); ?></time>
		</div>
		<div class="comment__text"><?php comment_text( $trust_comment ); ?></div>
		<?php if ( Blog::show_likes() || ( ! $trust_reply && ! empty( $args['open'] ) ) ) : ?>
			<div class="comment__actions">
				<?php if ( Blog::show_likes() ) : ?>
					<button type="button" class="comment__like" aria-pressed="false" data-comment-like="<?php echo esc_attr( (string) $trust_id ); ?>">
						<?php
						Icons::render(
							'heart',
							array(
								'size'   => 16,
								'stroke' => 'var(--c-violet)',
								'class'  => 'comment__like-icon',
							)
						);
						?>
						<span class="screen-reader-text"><?php esc_html_e( 'Нравится:', 'trust' ); ?> </span>
						<span data-comment-likes><?php echo esc_html( Article::number( Comments::likes( $trust_comment ) ) ); ?></span>
					</button>
				<?php endif; ?>
				<?php if ( ! $trust_reply && ! empty( $args['open'] ) ) : ?>
					<button type="button" class="comment__reply" aria-expanded="false" aria-controls="comment-reply" data-comment-reply="<?php echo esc_attr( (string) $trust_id ); ?>"><?php esc_html_e( 'Ответить', 'trust' ); ?></button>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( ! $trust_reply ) : ?>
			<div class="comment__slot"></div>
		<?php endif; ?>
		<?php if ( $trust_replies ) : ?>
			<ol class="comment__replies">
				<?php
				foreach ( $trust_replies as $trust_child ) {
					get_template_part(
						'template-parts/post/comment',
						null,
						array(
							'comment' => $trust_child,
							'post'    => $trust_post,
							'reply'   => true,
						)
					);
				}
				?>
			</ol>
		<?php endif; ?>
	</div>
</li>
