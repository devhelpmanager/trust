<?php
/**
 * Под текстом статьи (макет seo-blog/statya.html, с правками заказчика): сразу после текста —
 * карточка автора на тёмном фоне первого экрана; ниже линия, «Ключевые слова» — теги статьи
 * ссылками, под ними «Понравилось? Лайкни!» с кнопкой «Нравится». На всех ширинах — в этом порядке.
 *
 * Автор — сотрудник, связанный с пользователем WordPress (фото или инициалы, «должность · опыт
 * в SEO — N лет», ссылка на страницу сотрудника), иначе — имя пользователя без ссылки.
 *
 * Аргументы get_template_part(): post (WP_Post), tags (WP_Term[]).
 *
 * @package Trust
 */

use Trust\Blog;
use Trust\Team;
use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

$trust_post = $args['post'] ?? null;
if ( ! $trust_post instanceof WP_Post ) {
	return;
}
$trust_tags = (array) ( $args['tags'] ?? array() );
ob_start();
get_template_part( 'template-parts/post/like', null, array( 'post' => $trust_post ) );
$trust_like   = trim( (string) ob_get_clean() );
$trust_author = Blog::author( $trust_post );
$trust_member = $trust_author['member'];
if ( ! $trust_tags && '' === $trust_like && '' === $trust_author['name'] ) {
	return;
}
$trust_label = Template_Texts::get( 'post_tags' );
?>
<div class="post-foot">
	<?php
	if ( '' !== $trust_author['name'] ) :
		$trust_url   = $trust_member && is_post_type_viewable( 'team' ) ? (string) get_permalink( $trust_member ) : '';
		$trust_tag   = '' !== $trust_url ? 'a' : 'div';
		$trust_about = array_filter(
			array(
				$trust_author['role'],
				$trust_member ? Team::experience_line( $trust_member ) : '',
			)
		);
		?>
		<<?php echo esc_html( $trust_tag ); ?> class="post-author-card"<?php echo '' !== $trust_url ? ' href="' . esc_url( $trust_url ) . '"' : ''; ?>>
			<?php if ( $trust_author['photo'] ) : ?>
				<?php
				echo wp_get_attachment_image(
					$trust_author['photo'],
					'thumbnail',
					false,
					array(
						'class'   => 'post-author-card__avatar',
						'alt'     => '',
						'loading' => 'lazy',
					)
				);
				?>
			<?php else : ?>
				<span class="post-author-card__avatar" aria-hidden="true"><?php echo esc_html( $trust_author['initials'] ); ?></span>
			<?php endif; ?>
			<span class="post-author-card__who">
				<span class="post-author-card__name"><?php echo esc_html( $trust_author['name'] ); ?></span>
				<?php if ( $trust_about ) : ?>
					<span class="post-author-card__role"><?php echo esc_html( implode( ' · ', $trust_about ) ); ?></span>
				<?php endif; ?>
			</span>
		</<?php echo esc_html( $trust_tag ); ?>>
	<?php endif; ?>
	<?php if ( $trust_tags || '' !== $trust_like ) : ?>
		<div class="post-foot__bottom">
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
	<?php endif; ?>
</div>
