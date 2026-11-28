<?php
/**
 * Первый экран рубрики (макет portfolio-blog-404/rubrika.html): крошки, плашка «Рубрика», H1,
 * описание, «N статей · обновлено …», карточка «Ведёт рубрику», полоса рубрик (на телефоне —
 * строкой с прокруткой, текущая видна), теги рубрики. Подписи — «Настройки сайта → Тексты шаблонов».
 *
 * Аргументы get_template_part(): term (WP_Term).
 *
 * @package Trust
 */

use Trust\Article;
use Trust\Blog;
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
$trust_lead  = Rubric::lead( $trust_term );
$trust_desc  = trim( wp_strip_all_tags( (string) $trust_term->description ) );
$trust_tags  = Rubric::tags( $trust_term );
$trust_strip = Rubric::strip();
$trust_all   = (int) ( wp_count_posts( 'post' )->publish ?? 0 );
?>
<?php Breadcrumbs::render( 'archive-hero__crumbs' ); ?>
<div class="rubric-hero__top">
	<div class="archive-hero__head rubric-hero__head">
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
	<?php if ( $trust_lead ) : ?>
		<?php $trust_tag = '' !== $trust_lead['url'] ? 'a' : 'div'; ?>
		<<?php echo esc_html( $trust_tag ); ?> class="rubric-lead"<?php echo '' !== $trust_lead['url'] ? ' href="' . esc_url( $trust_lead['url'] ) . '"' : ''; ?>>
			<?php if ( $trust_lead['photo'] ) : ?>
				<?php
				echo wp_get_attachment_image(
					$trust_lead['photo'],
					'thumbnail',
					false,
					array(
						'class'   => 'rubric-lead__photo',
						'alt'     => '',
						'loading' => 'lazy',
					)
				);
				?>
			<?php else : ?>
				<span class="rubric-lead__photo" aria-hidden="true"><?php echo esc_html( $trust_lead['initials'] ); ?></span>
			<?php endif; ?>
			<span class="rubric-lead__who">
				<span class="rubric-lead__label"><?php echo esc_html( Template_Texts::get( 'rubric_lead' ) ); ?></span>
				<span class="rubric-lead__name"><?php echo esc_html( $trust_lead['name'] ); ?></span>
				<?php if ( '' !== $trust_lead['role'] ) : ?>
					<span class="rubric-lead__role"><?php echo esc_html( $trust_lead['role'] ); ?></span>
				<?php endif; ?>
			</span>
		</<?php echo esc_html( $trust_tag ); ?>>
	<?php endif; ?>
</div>

<?php if ( $trust_strip ) : ?>
	<nav class="rubric-strip" aria-label="<?php esc_attr_e( 'Рубрики блога', 'trust' ); ?>" data-rubric-strip>
		<a class="rubric-strip__item" href="<?php echo esc_url( Blog::index_url() ); ?>"><?php esc_html_e( 'Все статьи', 'trust' ); ?><span class="rubric-strip__count"><?php echo esc_html( Article::number( $trust_all ) ); ?></span></a>
		<?php foreach ( $trust_strip as $trust_item ) : ?>
			<?php $trust_current = $trust_item->term_id === $trust_term->term_id; ?>
			<a class="rubric-strip__item" href="<?php echo esc_url( get_term_link( $trust_item ) ); ?>"<?php echo $trust_current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $trust_item->name ); ?><span class="rubric-strip__count"><?php echo esc_html( Article::number( (int) $trust_item->count ) ); ?></span></a>
		<?php endforeach; ?>
	</nav>
<?php endif; ?>

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
