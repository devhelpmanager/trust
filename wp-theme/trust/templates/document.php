<?php
/**
 * Template Name: Документ
 * Template Post Type: page
 *
 * Тип страницы «Документ» (оферта, политика, согласие; макеты o-nas/oferta.html,
 * politika-cookie.html, soglasie-pd.html). Первый экран — во всю ширину; ниже на компьютере
 * содержание сбоку (едет при прокрутке, подсвечивает текущий раздел), на телефоне и планшете —
 * кнопка «Содержание»; справа текст документа. Содержание — из заголовков H2 текста
 * (Trust\Document::build()), подсветка и закрытие на телефоне — assets/js/document.js.
 * Печать: без шапки, подвала, содержания и кнопок (assets/css/document.css).
 *
 * @package Trust
 */

use Trust\Breadcrumbs;
use Trust\Document;
use Trust\Icons;
use Trust\Page_Types;
use Trust\Seo;

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$trust_doc      = Document::build( get_post() );
	$trust_numbered = Document::numbered( get_the_ID() );
	$trust_has_toc  = count( $trust_doc['toc'] ) > 1;
	$trust_toc      = static function ( string $extra ) use ( $trust_doc, $trust_numbered ): void {
		?>
		<ol class="doc-toc__list <?php echo esc_attr( $extra ); ?>">
			<?php foreach ( $trust_doc['toc'] as $trust_i => $trust_item ) : ?>
				<li>
					<a class="doc-toc__link" href="#<?php echo esc_attr( $trust_item['id'] ); ?>" data-doc-toc-link>
						<?php if ( $trust_numbered ) : ?>
							<span class="doc-toc__num"><?php echo esc_html( (string) ( $trust_i + 1 ) ); ?></span>
						<?php endif; ?>
						<span><?php echo esc_html( $trust_item['title'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php
	};
	?>
	<article <?php post_class( 'page-entry doc' . ( $trust_numbered ? ' doc--numbered' : '' ) ); ?>>
		<?php if ( Page_Types::has_hero() ) : ?>
			<?php echo $trust_doc['hero']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка блоков (the_content). ?>
		<?php else : ?>
			<header class="page-default">
				<div class="container">
					<?php Breadcrumbs::render( 'page-default__crumbs' ); ?>
					<h1 class="page-default__title"><?php echo esc_html( Seo::h1() ); ?></h1>
				</div>
			</header>
		<?php endif; ?>

		<div class="doc__body">
			<div class="doc__inner<?php echo $trust_has_toc ? ' doc__inner--toc' : ''; ?>">
				<?php if ( $trust_has_toc ) : ?>
					<nav class="doc-toc doc-toc--side" aria-label="<?php esc_attr_e( 'Содержание', 'trust' ); ?>" data-doc-toc>
						<span class="doc-toc__title" aria-hidden="true"><?php esc_html_e( 'Содержание', 'trust' ); ?></span>
						<?php $trust_toc( '' ); ?>
					</nav>
				<?php endif; ?>

				<div class="doc__main">
					<?php if ( $trust_has_toc ) : ?>
						<details class="doc-toc doc-toc--drop" data-doc-toc>
							<summary class="doc-toc__toggle">
								<span><?php esc_html_e( 'Содержание', 'trust' ); ?></span>
								<?php
								Icons::render(
									'chevron-down',
									array(
										'size'         => 20,
										'stroke_width' => 2.2,
										'class'        => 'doc-toc__chev',
									)
								);
								?>
							</summary>
							<?php $trust_toc( 'doc-toc__list--drop' ); ?>
						</details>
					<?php endif; ?>

					<div class="doc-content">
						<?php echo $trust_doc['main']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка блоков (the_content). ?>
					</div>
				</div>
			</div>
		</div>
	</article>
	<?php
endwhile;

get_footer();
