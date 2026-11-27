<?php
/**
 * Статья блога (ТЗ, раздел 6.3; макет seo-blog/statya.html).
 *
 * Хлебные крошки; первый экран — тёмная карточка: H1, автор (ссылка на страницу сотрудника),
 * дата, время чтения, просмотры, лайки, комментарии, ключевые слова (теги). Ниже — две колонки:
 * слева закреплённое содержание (H2 текста, «Прочитано N %») и кнопка «Нравится», справа —
 * обложка, анонс, текст статьи; под текстом — ключевые слова и «Статья была полезной?».
 * До 1024 px содержание — полоса вверху экрана, раскрывается по нажатию. Внизу — «Читайте
 * также» (Trust\Article::related()). Просмотры, лайки, содержание — assets/js/post.js.
 * Комментарии — шаг 5.3.
 *
 * @package Trust
 */

use Trust\Article;
use Trust\Breadcrumbs;

defined( 'ABSPATH' ) || exit;

the_post();
$trust_post    = get_post();
$trust_content = Article::content( $trust_post );
$trust_tags    = Article::tags( $trust_post );

get_header();
?>
<div class="post-crumbs">
	<?php Breadcrumbs::render( 'post-crumbs__nav' ); ?>
</div>

<?php
get_template_part(
	'template-parts/post/hero',
	null,
	array(
		'post' => $trust_post,
		'tags' => $trust_tags,
	)
);
?>

<section class="post-body">
	<div class="post-body__inner">
		<?php
		get_template_part(
			'template-parts/post/toc',
			null,
			array(
				'post' => $trust_post,
				'toc'  => $trust_content['toc'],
				'view' => 'side',
			)
		);
		?>
		<div class="post-main">
			<?php
			get_template_part(
				'template-parts/post/toc',
				null,
				array(
					'post' => $trust_post,
					'toc'  => $trust_content['toc'],
					'view' => 'bar',
				)
			);
			?>
			<article class="post-text" id="post-article" aria-labelledby="post-title">
				<?php if ( has_post_thumbnail( $trust_post ) ) : ?>
					<figure class="post-text__cover">
						<?php
						the_post_thumbnail(
							'trust-cover-lg',
							array(
								'class'         => 'post-text__cover-img',
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'sizes'         => '(max-width: 1023px) calc(100vw - 40px), 760px',
							)
						);
						?>
					</figure>
				<?php endif; ?>
				<?php if ( has_excerpt( $trust_post ) ) : ?>
					<p class="post-text__lead"><?php echo esc_html( get_the_excerpt( $trust_post ) ); ?></p>
				<?php endif; ?>
				<?php echo $trust_content['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- текст статьи после фильтра the_content. ?>
			</article>

			<?php
			get_template_part(
				'template-parts/post/foot',
				null,
				array(
					'post' => $trust_post,
					'tags' => $trust_tags,
				)
			);
			?>
		</div>
	</div>
</section>

<?php
get_template_part( 'template-parts/post/related', null, array( 'post' => $trust_post ) );
get_footer();
