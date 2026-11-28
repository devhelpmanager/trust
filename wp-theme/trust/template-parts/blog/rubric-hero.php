<?php
/**
 * Первый экран рубрики (макет portfolio-blog-404/rubrika.html): крошки, плашка «Рубрика», H1,
 * описание, «N статей · обновлено …» — во всю ширину (правка заказчика: без «Ведёт рубрику»),
 * полоса рубрик (на телефоне — строкой с прокруткой, текущая видна), теги рубрики. Подписи —
 * «Настройки сайта → Тексты шаблонов».
 *
 * Аргументы get_template_part(): term (WP_Term).
 *
 * @package Trust
 */

use Trust\Article;
use Trust\Breadcrumbs;
use Trust\Rubric;
use Trust\Seo;
use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

$trust_term = $args['term'] ?? null;
if ( ! $trust_term instanceof WP_Term ) {
	return;
}
$trust_stats = Rubric::stats( $trust_term );
$trust_desc  = trim( wp_strip_all_tags( (string) $trust_term->description ) );
$trust_tags  = Rubric::tags( $trust_term );
?>
<?php Breadcrumbs::render( 'archive-hero__crumbs' ); ?>
<div class="archive-hero__head">
	<span class="rubric-hero__label"><?php echo esc_html( Template_Texts::get( 'rubric_label' ) ); ?></span>
	<h1 class="archive-hero__title"><?php echo esc_html( Seo::h1( $trust_term ) ); ?></h1>
	<?php if ( '' !== $trust_desc ) : ?>
		<p class="archive-hero__lead"><?php echo esc_html( $trust_desc ); ?></p>
	<?php endif; ?>
	<?php if ( $trust_stats['count'] ) : ?>
		<p class="rubric-hero__meta">
			<?php
			echo esc_html(
				Article::count( $trust_stats['count'], 'posts' )
				/* translators: %s: дата самой новой статьи. */
				. ( '' !== $trust_stats['updated'] ? ' · ' . sprintf( __( 'обновлено %s', 'trust' ), $trust_stats['updated'] ) : '' )
			);
			?>
		</p>
	<?php endif; ?>
</div>

<?php get_template_part( 'template-parts/blog/rubric-strip', null, array( 'current' => $trust_term->term_id ) ); ?>

<?php if ( $trust_tags ) : ?>
	<div class="rubric-tags">
		<span class="rubric-tags__label" id="rubric-tags-label"><?php echo esc_html( Template_Texts::get( 'rubric_tags' ) ); ?></span>
		<ul class="rubric-tags__list" role="list" aria-labelledby="rubric-tags-label">
			<?php foreach ( $trust_tags as $trust_item ) : ?>
				<li><a class="rubric-tags__tag" href="<?php echo esc_url( get_term_link( $trust_item ) ); ?>">#<?php echo esc_html( $trust_item->name ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
