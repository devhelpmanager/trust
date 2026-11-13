<?php
/**
 * Блок «Статьи из блога» (ТЗ, п. 8.3, № 16).
 *
 * Вид «Наш блог» (seo-stranitsa/index.html, страница услуги): карточки без обложек — рубрика,
 * заголовок, дата, «Читать →», до 5 в ряд. Вид «3 карточки с обложками» (seo-bloki/16-blog.html):
 * обложка 16:9, рубрика, время чтения, заголовок, анонс, дата, «Читать →». Вверху — надзаголовок,
 * H2 и кнопка «Все статьи». Фон лавандовый. Нет статей — блок на сайте не выводится.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Blog;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_view    = 'cards' === get_field( 'view' ) ? 'cards' : 'blog';
$trust_source  = (string) get_field( 'source' );
$trust_count   = (int) get_field( 'count' );
$trust_count   = $trust_count > 0 ? min( 8, $trust_count ) : ( 'cards' === $trust_view ? 3 : 5 );
$trust_args    = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => $trust_count,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);
$trust_current = is_singular( 'post' ) ? get_queried_object_id() : 0;
if ( $trust_current ) {
	$trust_args['post__not_in'] = array( $trust_current ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- одна статья, небольшой список.
}
switch ( $trust_source ) {
	case 'category':
		$trust_args['cat'] = (int) get_field( 'category' );
		break;
	case 'popular':
		// По просмотрам; статьи без счётчика (ещё не открывали) — в конце, по дате.
		$trust_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- сортировка по счётчику просмотров.
			'relation' => 'OR',
			'views'    => array(
				'key'  => Blog::VIEWS,
				'type' => 'NUMERIC',
			),
			array(
				'key'     => Blog::VIEWS,
				'compare' => 'NOT EXISTS',
			),
		);
		$trust_args['orderby']    = array(
			'views' => 'DESC',
			'date'  => 'DESC',
		);
		break;
	case 'manual':
		$trust_ids                    = array_map( 'intval', (array) get_field( 'posts' ) );
		$trust_args['post__in']       = $trust_ids ? $trust_ids : array( 0 );
		$trust_args['orderby']        = 'post__in';
		$trust_args['posts_per_page'] = count( $trust_ids );
		break;
}
$trust_posts = get_posts( $trust_args );

if ( ! $trust_posts && ! $is_preview ) {
	return;
}

$trust_title    = (string) get_field( 'title' );
$trust_eyebrow  = (string) get_field( 'eyebrow' );
$trust_lead     = (string) get_field( 'lead' );
$trust_more     = get_field( 'more_label' );
$trust_more     = null === $trust_more ? __( 'Все статьи', 'trust' ) : trim( (string) $trust_more );
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
?>
<section class="b-posts b-posts--<?php echo esc_attr( $trust_view ); ?> trust-bg-lavender"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-posts__inner">
		<div class="b-posts__top">
			<?php if ( $trust_eyebrow || $trust_title || $trust_lead ) : ?>
				<div class="b-posts__head">
					<?php if ( $trust_eyebrow ) : ?>
						<span class="eyebrow"><?php echo esc_html( $trust_eyebrow ); ?></span>
					<?php endif; ?>
					<?php if ( $trust_title ) : ?>
						<h2 class="section-title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
					<?php endif; ?>
					<?php if ( $trust_lead ) : ?>
						<p class="section-lead"><?php echo esc_html( $trust_lead ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( '' !== $trust_more ) : ?>
				<a class="b-posts__all" href="<?php echo esc_url( Blog::index_url() ); ?>">
					<?php echo esc_html( $trust_more ); ?>
					<?php Icons::render( 'arrow-right', array( 'size' => 16 ) ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $trust_posts ) : ?>
			<ul class="b-posts__list">
				<?php foreach ( $trust_posts as $trust_post ) : ?>
					<?php
					$trust_rubric = Blog::rubric( $trust_post );
					$trust_date   = Blog::date( $trust_post );
					$trust_iso    = get_the_date( 'c', $trust_post );
					?>
					<li>
						<a class="b-posts__card" href="<?php echo esc_url( get_permalink( $trust_post ) ); ?>">
							<?php if ( 'cards' === $trust_view ) : ?>
								<span class="b-posts__cover" aria-hidden="true">
									<?php
									echo has_post_thumbnail( $trust_post )
										? get_the_post_thumbnail(
											$trust_post,
											'medium_large',
											array(
												'class'   => 'b-posts__img',
												'alt'     => '',
												'loading' => 'lazy',
												'sizes'   => '(max-width: 760px) 100vw, 400px',
											)
										)
										: '<span class="b-posts__placeholder"></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка WordPress.
									?>
								</span>
								<span class="b-posts__body">
									<span class="b-posts__row">
										<?php if ( $trust_rubric ) : ?>
											<span class="b-posts__rubric"><?php echo esc_html( $trust_rubric->name ); ?></span>
										<?php endif; ?>
										<span class="b-posts__time">
											<?php
											/* translators: %d: минуты. */
											echo esc_html( sprintf( __( '%d мин чтения', 'trust' ), Blog::reading_time( $trust_post ) ) );
											?>
										</span>
									</span>
									<h3 class="b-posts__title"><?php echo esc_html( get_the_title( $trust_post ) ); ?></h3>
									<?php if ( has_excerpt( $trust_post ) ) : ?>
										<span class="b-posts__excerpt"><?php echo esc_html( get_the_excerpt( $trust_post ) ); ?></span>
									<?php endif; ?>
									<span class="b-posts__meta">
										<time datetime="<?php echo esc_attr( $trust_iso ); ?>"><?php echo esc_html( $trust_date ); ?></time>
										<span class="b-posts__read" aria-hidden="true"><?php esc_html_e( 'Читать →', 'trust' ); ?></span>
									</span>
								</span>
							<?php else : ?>
								<?php if ( $trust_rubric ) : ?>
									<span class="b-posts__rubric"><?php echo esc_html( $trust_rubric->name ); ?></span>
								<?php endif; ?>
								<h3 class="b-posts__title"><?php echo esc_html( get_the_title( $trust_post ) ); ?></h3>
								<span class="b-posts__meta">
									<time datetime="<?php echo esc_attr( $trust_iso ); ?>"><?php echo esc_html( $trust_date ); ?></time>
									<span class="b-posts__read" aria-hidden="true"><?php esc_html_e( 'Читать →', 'trust' ); ?></span>
								</span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="b-posts__empty"><?php esc_html_e( 'Подходящих статей нет: опубликуйте статьи или выберите другие условия в настройках блока.', 'trust' ); ?></p>
		<?php endif; ?>
	</div>
</section>
