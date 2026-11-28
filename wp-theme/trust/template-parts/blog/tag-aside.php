<?php
/**
 * Боковая колонка тега (макет portfolio-blog-404/teg.html): рубрика, в которой больше всего статей
 * с тегом (название-ссылка, число статей рубрики), похожие теги, тёмная карточка «Остался вопрос про
 * …?» с кнопкой на страницу вопросов. Пустые части не выводятся. Подписи — «Настройки сайта →
 * Тексты шаблонов» ({tag} — название тега).
 *
 * Аргументы get_template_part(): term (WP_Term).
 *
 * @package Trust
 */

use Trust\Article;
use Trust\Block_Texts;
use Trust\Icons;
use Trust\Tag_Page;
use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

$trust_term = $args['term'] ?? null;
if ( ! $trust_term instanceof WP_Term ) {
	return;
}
$trust_rubric  = Tag_Page::rubric( $trust_term );
$trust_similar = Tag_Page::similar( $trust_term );
$trust_ask_url = Block_Texts::page_url( 'ask_page', 'questions' );
$trust_arrow   = static fn( int $size ): string => Icons::svg(
	'arrow-right',
	array(
		'size'         => $size,
		'stroke_width' => 2.2,
	)
);
?>
<aside class="tag-side" aria-label="<?php esc_attr_e( 'Ещё по теме', 'trust' ); ?>">
	<?php if ( $trust_rubric ) : ?>
		<div class="tag-side__card">
			<span class="tag-side__label"><?php echo esc_html( Template_Texts::get( 'tag_rubric' ) ); ?></span>
			<a class="tag-side__rubric" href="<?php echo esc_url( get_term_link( $trust_rubric ) ); ?>"><?php echo esc_html( $trust_rubric->name ); ?><?php echo $trust_arrow( 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG темы. ?></a>
			<span class="tag-side__note"><?php echo esc_html( Article::count( (int) $trust_rubric->count, 'posts' ) ); ?></span>
		</div>
	<?php endif; ?>
	<?php if ( $trust_similar ) : ?>
		<div class="tag-side__card">
			<span class="tag-side__label" id="tag-similar-label"><?php echo esc_html( Template_Texts::get( 'tag_similar' ) ); ?></span>
			<ul class="tag-side__tags" role="list" aria-labelledby="tag-similar-label">
				<?php foreach ( $trust_similar as $trust_tag ) : ?>
					<li><a class="tag-side__tag" href="<?php echo esc_url( get_term_link( $trust_tag ) ); ?>">#<?php echo esc_html( $trust_tag->name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
	<?php if ( '' !== $trust_ask_url ) : ?>
		<div class="tag-side__ask">
			<span class="tag-side__circle" aria-hidden="true"></span>
			<p class="tag-side__ask-title"><?php echo esc_html( str_replace( '{tag}', $trust_term->name, Template_Texts::get( 'tag_ask_title' ) ) ); ?></p>
			<p class="tag-side__ask-text"><?php echo esc_html( Template_Texts::get( 'tag_ask_text' ) ); ?></p>
			<a class="tag-side__ask-btn" href="<?php echo esc_url( $trust_ask_url ); ?>"><?php echo esc_html( Template_Texts::get( 'tag_ask_button' ) ); ?><?php echo $trust_arrow( 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG темы. ?></a>
		</div>
	<?php endif; ?>
</aside>
