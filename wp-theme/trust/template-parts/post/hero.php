<?php
/**
 * Первый экран статьи (макет seo-blog/statya.html, с правками заказчика): тёмная карточка с
 * кругами; сверху H1, от 1280 px напротив него справа — рисунок (assets/art/post-hero.svg); под
 * ними линия, «О статье» (дата, время чтения, просмотры, лайки, комментарии) и ключевые слова.
 * Автор статьи — под текстом (template-parts/post/foot.php).
 *
 * Просмотры и лайки скрываются в «Настройках сайта → Блог»; их числа обновляет assets/js/post.js.
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
use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

$trust_post = $args['post'] ?? null;
if ( ! $trust_post instanceof WP_Post ) {
	return;
}
$trust_tags = (array) ( $args['tags'] ?? array() );
$trust_icon = static function ( string $name ): string {
	return Icons::svg(
		$name,
		array(
			'size'   => 18,
			'stroke' => '#C4B5FD',
			'class'  => 'post-hero__icon',
		)
	);
};
$trust_art  = TRUST_DIR . '/assets/art/post-hero.svg';
?>
<section class="post-hero" aria-labelledby="post-title">
	<div class="post-hero__card">
		<div class="post-hero__text">
			<div class="post-hero__head">
				<h1 class="post-hero__title" id="post-title"><?php echo esc_html( Seo::h1( $trust_post->ID ) ); ?></h1>
				<?php if ( is_readable( $trust_art ) ) : ?>
					<span class="post-hero__art" aria-hidden="true"><span class="post-hero__ring"></span><?php echo file_get_contents( $trust_art ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- SVG темы. ?></span>
				<?php endif; ?>
			</div>

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
