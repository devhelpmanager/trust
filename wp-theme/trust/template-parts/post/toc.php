<?php
/**
 * Содержание статьи (макет seo-blog/statya.html) — пункты из H2 текста (Trust\Article::content()).
 *
 * Вид side: колонка слева (от 1024 px), закреплена при прокрутке — «Содержание», «Прочитано
 * N %», полоса прогресса, пункты с номерами 01, 02…, под ними кнопка «Нравится».
 * Вид bar: до 1024 px — полоса вверху экрана: «Содержание», текущий раздел, N %, стрелка;
 * по нажатию раскрывается список пунктов. Текущий пункт (aria-current) и проценты ставит
 * assets/js/post.js.
 *
 * Аргументы get_template_part(): post (WP_Post), toc (array{id, title}[]), view (side | bar).
 *
 * @package Trust
 */

use Trust\Icons;
use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

$trust_post = $args['post'] ?? null;
$trust_toc  = (array) ( $args['toc'] ?? array() );
$trust_view = 'bar' === ( $args['view'] ?? '' ) ? 'bar' : 'side';
if ( ! $trust_post instanceof WP_Post ) {
	return;
}
$trust_label = Template_Texts::get( 'post_toc' );
$trust_list  = static function () use ( $trust_toc ): void {
	echo '<ol class="post-toc__list">';
	foreach ( $trust_toc as $trust_i => $trust_item ) {
		printf(
			'<li><a class="post-toc__link" href="#%1$s"><span class="post-toc__num" aria-hidden="true">%2$s</span>%3$s</a></li>',
			esc_attr( $trust_item['id'] ),
			esc_html( sprintf( '%02d', $trust_i + 1 ) ),
			esc_html( $trust_item['title'] )
		);
	}
	echo '</ol>';
};

if ( 'side' === $trust_view ) :
	ob_start();
	get_template_part(
		'template-parts/post/like',
		null,
		array(
			'post'  => $trust_post,
			'class' => 'post-like--wide',
		)
	);
	$trust_like = trim( (string) ob_get_clean() );
	if ( ! $trust_toc && '' === $trust_like ) {
		return;
	}
	?>
	<aside class="post-side">
		<?php if ( $trust_toc ) : ?>
			<nav class="post-toc" aria-label="<?php esc_attr_e( 'Содержание статьи', 'trust' ); ?>">
				<div class="post-toc__head">
					<span class="post-toc__label"><?php echo esc_html( $trust_label ); ?></span>
					<?php /* translators: %s: процент прочитанного (число подставляет скрипт). */ ?>
					<span class="post-toc__read"><?php echo wp_kses( sprintf( __( 'Прочитано %s%%', 'trust' ), '<span data-post-progress>0</span>' ), array( 'span' => array( 'data-post-progress' => true ) ) ); ?></span>
				</div>
				<div class="post-toc__bar" aria-hidden="true"><span class="post-toc__fill"></span></div>
				<?php $trust_list(); ?>
			</nav>
		<?php endif; ?>
		<?php if ( '' !== $trust_like ) : ?>
			<div class="post-side__actions">
				<?php echo $trust_like; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка template-parts/post/like.php. ?>
			</div>
		<?php endif; ?>
	</aside>
	<?php
	return;
endif;

if ( ! $trust_toc ) {
	return;
}
?>
<div class="post-tocbar">
	<button type="button" class="post-tocbar__toggle" aria-expanded="false" aria-controls="post-tocbar-panel">
		<span class="post-toc__label"><?php echo esc_html( $trust_label ); ?></span>
		<span class="post-tocbar__current" data-post-current><?php echo esc_html( $trust_toc[0]['title'] ); ?></span>
		<span class="post-tocbar__read"><span data-post-progress>0</span>%</span>
		<?php
		Icons::render(
			'chevron-down',
			array(
				'size'   => 18,
				'stroke' => 'var(--c-violet)',
				'class'  => 'post-tocbar__chevron',
			)
		);
		?>
	</button>
	<div class="post-tocbar__bar" aria-hidden="true"><span class="post-toc__fill"></span></div>
	<nav class="post-toc post-tocbar__panel" id="post-tocbar-panel" aria-label="<?php esc_attr_e( 'Содержание статьи', 'trust' ); ?>" hidden>
		<?php $trust_list(); ?>
	</nav>
</div>
