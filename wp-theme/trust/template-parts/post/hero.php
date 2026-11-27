<?php
/**
 * Первый экран статьи (макет seo-blog/statya.html): тёмная карточка с кругами, H1, автор,
 * «О статье» (дата, время чтения, просмотры, лайки, комментарии), ключевые слова; от 1280 px
 * справа — рисунок (assets/art/post-hero.svg).
 *
 * Автор — сотрудник, связанный с пользователем WordPress (фото или инициалы, должность и опыт;
 * ссылка на страницу сотрудника), иначе — имя пользователя без ссылки. Просмотры и лайки
 * скрываются в «Настройках сайта → Блог»; их числа обновляет assets/js/post.js.
 * Тег — имя файла или адрес (с точкой, без пробелов: «robots.txt») — не переводится в заглавные.
 *
 * Аргументы get_template_part(): post (WP_Post), tags (WP_Term[]).
 *
 * @package Trust
 */

use Trust\Article;
use Trust\Blog;
use Trust\Icons;
use Trust\Seo;
use Trust\Team;
use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

$trust_post = $args['post'] ?? null;
if ( ! $trust_post instanceof WP_Post ) {
	return;
}
$trust_tags   = (array) ( $args['tags'] ?? array() );
$trust_author = Blog::author( $trust_post );
$trust_member = $trust_author['member'];
$trust_about  = array_filter(
	array(
		$trust_author['role'],
		$trust_member ? Team::experience_line( $trust_member ) : '',
	)
);
$trust_icon   = static function ( string $name ): string {
	return Icons::svg(
		$name,
		array(
			'size'   => 18,
			'stroke' => '#C4B5FD',
			'class'  => 'post-hero__icon',
		)
	);
};
$trust_art    = TRUST_DIR . '/assets/art/post-hero.svg';
?>
<section class="post-hero" aria-labelledby="post-title">
	<div class="post-hero__card">
		<?php if ( is_readable( $trust_art ) ) : ?>
			<span class="post-hero__ring" aria-hidden="true"></span>
			<span class="post-hero__art" aria-hidden="true"><?php echo file_get_contents( $trust_art ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- SVG темы. ?></span>
		<?php endif; ?>
		<div class="post-hero__text">
			<h1 class="post-hero__title" id="post-title"><?php echo esc_html( Seo::h1( $trust_post->ID ) ); ?></h1>

			<?php if ( '' !== $trust_author['name'] ) : ?>
				<?php
				$trust_url = $trust_member && is_post_type_viewable( 'team' ) ? (string) get_permalink( $trust_member ) : '';
				$trust_tag = '' !== $trust_url ? 'a' : 'span';
				?>
				<<?php echo esc_html( $trust_tag ); ?> class="post-hero__author"<?php echo '' !== $trust_url ? ' href="' . esc_url( $trust_url ) . '"' : ''; ?>>
					<?php if ( $trust_author['photo'] ) : ?>
						<?php
						echo wp_get_attachment_image(
							$trust_author['photo'],
							'thumbnail',
							false,
							array(
								'class'   => 'post-hero__avatar',
								'alt'     => '',
								'loading' => 'eager',
							)
						);
						?>
					<?php else : ?>
						<span class="post-hero__avatar" aria-hidden="true"><?php echo esc_html( $trust_author['initials'] ); ?></span>
					<?php endif; ?>
					<span class="post-hero__who">
						<span class="post-hero__name"><?php echo esc_html( $trust_author['name'] ); ?></span>
						<?php if ( $trust_about ) : ?>
							<span class="post-hero__role"><?php echo esc_html( implode( ' · ', $trust_about ) ); ?></span>
						<?php endif; ?>
					</span>
				</<?php echo esc_html( $trust_tag ); ?>>
			<?php endif; ?>

			<ul class="post-hero__meta" aria-label="<?php esc_attr_e( 'О статье', 'trust' ); ?>">
				<li>
					<?php echo $trust_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG из Icons::svg(). ?>
					<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $trust_post ) ); ?>"><?php echo esc_html( Blog::date( $trust_post ) ); ?></time>
				</li>
				<li>
					<?php
					echo $trust_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG из Icons::svg().
					/* translators: %d: минуты. */
					echo esc_html( sprintf( __( '%d мин чтения', 'trust' ), Blog::reading_time( $trust_post ) ) );
					?>
				</li>
				<?php
				$trust_counts = array(
					'views'    => Blog::show_views() ? array( 'eye', Blog::views( $trust_post ) ) : null,
					'likes'    => Blog::show_likes() ? array( 'heart', Blog::likes( $trust_post ) ) : null,
					'comments' => array( 'chat', (int) get_comments_number( $trust_post ) ),
				);
				foreach ( array_filter( $trust_counts ) as $trust_kind => list( $trust_name, $trust_value ) ) :
					?>
					<li class="post-hero__count post-hero__count--<?php echo esc_attr( $trust_kind ); ?>">
						<?php echo $trust_icon( $trust_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG из Icons::svg(). ?>
						<span data-post-count="<?php echo esc_attr( $trust_kind ); ?>" data-forms="<?php echo esc_attr( implode( '|', Article::FORMS[ $trust_kind ] ) ); ?>"><?php echo esc_html( Article::count( $trust_value, $trust_kind ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( $trust_tags ) : ?>
				<ul class="post-hero__tags" aria-label="<?php echo esc_attr( Template_Texts::get( 'post_tags' ) ); ?>">
					<?php foreach ( $trust_tags as $trust_term ) : ?>
						<?php $trust_raw = preg_match( '/^\S+\.\S+$/u', $trust_term->name ); ?>
						<li><a class="post-hero__tag<?php echo $trust_raw ? ' is-raw' : ''; ?>" href="<?php echo esc_url( get_term_link( $trust_term ) ); ?>"><?php echo esc_html( $trust_term->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php
unset( $trust_icon );
