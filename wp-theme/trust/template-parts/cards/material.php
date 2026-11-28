<?php
/**
 * Карточка материала на странице тега (макет portfolio-blog-404/teg.html): статья или ответ на
 * вопрос. Вся карточка — ссылка.
 *
 * Статья: обложка слева (на телефоне — сверху), плашка «Статья» и рубрика, заголовок H3, анонс,
 * дата и время чтения. Ответ: знак «?» (уже 480 px — без него), плашка «Ответ SEO-специалиста»,
 * вопрос (H3), начало ответа, дата ответа и «Отвечает Имя». Подписи — «Настройки сайта → Тексты
 * шаблонов».
 *
 * Аргументы get_template_part(): post (WP_Post — статья или вопрос).
 *
 * @package Trust
 */

use Trust\Answers_List;
use Trust\Blog;
use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

$trust_post = $args['post'] ?? null;
if ( ! $trust_post instanceof WP_Post ) {
	return;
}
$trust_is_answer = 'question' === $trust_post->post_type;
?>
<li class="materials__item">
	<?php if ( $trust_is_answer ) : ?>
		<?php
		$trust_date   = Answers_List::answered( $trust_post->ID );
		$trust_expert = get_post( (int) get_post_meta( $trust_post->ID, 'trust_expert', true ) );
		$trust_expert = $trust_expert && 'team' === $trust_expert->post_type ? get_the_title( $trust_expert ) : '';
		$trust_text   = Answers_List::excerpt( $trust_post->ID );
		?>
		<a class="material material--answer" href="<?php echo esc_url( get_permalink( $trust_post ) ); ?>">
			<span class="material__icon" aria-hidden="true">?</span>
			<span class="material__body">
				<span class="material__top"><span class="material__badge material__badge--answer"><?php echo esc_html( Template_Texts::get( 'tag_answer_badge' ) ); ?></span></span>
				<h3 class="material__title"><?php echo esc_html( get_the_title( $trust_post ) ); ?></h3>
				<?php if ( '' !== $trust_text ) : ?>
					<span class="material__text"><?php echo esc_html( $trust_text ); ?></span>
				<?php endif; ?>
				<span class="material__meta">
					<time datetime="<?php echo esc_attr( $trust_date ); ?>"><?php echo esc_html( Answers_List::date_text( $trust_date ) ); ?></time>
					<?php if ( '' !== $trust_expert ) : ?>
						<span><?php echo esc_html( Template_Texts::get( 'tag_answered_by' ) . ' ' . $trust_expert ); ?></span>
					<?php endif; ?>
				</span>
			</span>
		</a>
	<?php else : ?>
		<?php $trust_rubric = Blog::rubric( $trust_post ); ?>
		<a class="material material--post" href="<?php echo esc_url( get_permalink( $trust_post ) ); ?>">
			<span class="material__cover">
				<?php
				echo has_post_thumbnail( $trust_post )
					? get_the_post_thumbnail(
						$trust_post,
						'trust-cover',
						array(
							'class'   => 'material__img',
							'alt'     => '',
							'loading' => 'lazy',
							'sizes'   => '(max-width: 640px) calc(100vw - 40px), 240px',
						)
					)
					: '<span class="post-card__placeholder"></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка WordPress.
				?>
			</span>
			<span class="material__body">
				<span class="material__top">
					<span class="material__badge"><?php echo esc_html( Template_Texts::get( 'tag_post_badge' ) ); ?></span>
					<?php if ( $trust_rubric ) : ?>
						<span class="material__rubric"><?php echo esc_html( $trust_rubric->name ); ?></span>
					<?php endif; ?>
				</span>
				<h3 class="material__title"><?php echo esc_html( get_the_title( $trust_post ) ); ?></h3>
				<?php if ( has_excerpt( $trust_post ) ) : ?>
					<span class="material__text"><?php echo esc_html( get_the_excerpt( $trust_post ) ); ?></span>
				<?php endif; ?>
				<span class="material__meta">
					<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $trust_post ) ); ?>"><?php echo esc_html( Blog::date( $trust_post ) ); ?></time>
					<?php /* translators: %d: минуты. */ ?>
					<span><?php echo esc_html( sprintf( __( '%d мин чтения', 'trust' ), Blog::reading_time( $trust_post ) ) ); ?></span>
				</span>
			</span>
		</a>
	<?php endif; ?>
</li>
