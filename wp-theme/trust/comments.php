<?php
/**
 * Комментарии статьи (ТЗ, раздел 13; макет seo-blog/statya.html): H2 «Комментарии N», форма,
 * список (новые сверху, ответы — под комментарием, два уровня), «Показать ещё N комментариев».
 *
 * Форма — общий механизм форм сайта (тип «comment», Trust\Comments): имя, email (обязателен, на
 * сайте не показывается), комментарий, согласие на обработку персональных данных. Комментарий
 * уходит на модерацию — «Спасибо! Комментарий появится после проверки модератором.». «Ответить»
 * открывает форму ответа под комментарием (assets/js/comments.js). Без JavaScript форма уходит
 * на admin-post.php и возвращается сюда с сообщением; ответы, «Показать ещё» и лайки — со скриптом.
 * Вошедшему пользователю имя и email подставлены, форма отправляется от его имени (data-rest-nonce):
 * комментарий модератора публикуется сразу.
 *
 * Подключается из single-post.php (comments_template()).
 *
 * @package Trust
 */

use Trust\Article;
use Trust\Comments;
use Trust\Forms;
use Trust\Icons;
use Trust\Template_Texts;

defined( 'ABSPATH' ) || exit;

$trust_post = get_post();
if ( ! $trust_post || 'post' !== $trust_post->post_type || post_password_required( $trust_post ) ) {
	return;
}
$trust_open    = comments_open( $trust_post );
$trust_threads = Comments::tree( $trust_post );
if ( ! $trust_open && ! $trust_threads ) {
	return;
}
$trust_total = Comments::count( $trust_threads );
$trust_shown = Comments::shown();
$trust_more  = Comments::count( array_slice( $trust_threads, $trust_shown ) );
$trust_form  = Forms::type( Comments::FORM );
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- только выбор сообщения после отправки без JavaScript.
$trust_result = isset( $_GET['trust_form'], $_GET['trust_form_type'] ) && Comments::FORM === sanitize_key( wp_unslash( $_GET['trust_form_type'] ) )
	? sanitize_key( wp_unslash( $_GET['trust_form'] ) )
	: '';
// phpcs:enable
$trust_sent  = 'sent' === $trust_result;
$trust_user  = wp_get_current_user();
$trust_rest  = $trust_user->exists() ? ' data-rest-nonce="' . esc_attr( wp_create_nonce( 'wp_rest' ) ) . '"' : '';
$trust_error = static function ( string $name ) use ( $trust_form ): string {
	return (string) ( $trust_form['fields'][ $name ]['error'] ?? '' );
};

/**
 * Поля имени и email (основная форма и форма ответа).
 *
 * @param string $uid Начало id полей.
 */
$trust_person = static function ( string $uid ) use ( $trust_error, $trust_user ): void {
	?>
	<div class="comment-form__row">
		<div class="field">
			<label class="field__label" for="<?php echo esc_attr( $uid ); ?>-name"><?php esc_html_e( 'Имя', 'trust' ); ?></label>
			<input class="field__input" id="<?php echo esc_attr( $uid ); ?>-name" name="name" type="text" autocomplete="name" maxlength="100" required value="<?php echo esc_attr( $trust_user->exists() ? $trust_user->display_name : '' ); ?>"
				placeholder="<?php esc_attr_e( 'Как к вам обращаться', 'trust' ); ?>"
				aria-describedby="<?php echo esc_attr( $uid ); ?>-name-error"
				data-error="<?php echo esc_attr( $trust_error( 'name' ) ); ?>">
			<span class="field__error" id="<?php echo esc_attr( $uid ); ?>-name-error" hidden></span>
		</div>
		<div class="field">
			<label class="field__label" for="<?php echo esc_attr( $uid ); ?>-email"><?php esc_html_e( 'Email', 'trust' ); ?> <span class="field__optional"><?php esc_html_e( '— не публикуется', 'trust' ); ?></span></label>
			<input class="field__input" id="<?php echo esc_attr( $uid ); ?>-email" name="email" type="email" autocomplete="email" maxlength="100" required value="<?php echo esc_attr( $trust_user->exists() ? $trust_user->user_email : '' ); ?>"
				placeholder="name@mail.ru"
				aria-describedby="<?php echo esc_attr( $uid ); ?>-email-error"
				data-error="<?php echo esc_attr( $trust_error( 'email' ) ); ?>">
			<span class="field__error" id="<?php echo esc_attr( $uid ); ?>-email-error" hidden></span>
		</div>
	</div>
	<?php
};
?>
<section class="post-comments" aria-labelledby="comments">
	<h2 class="post-comments__title" id="comments">
		<?php echo esc_html( Template_Texts::get( 'post_comments' ) ); ?>
		<span class="post-comments__count"><?php echo esc_html( Article::number( $trust_total ) ); ?></span>
	</h2>

	<?php if ( $trust_open ) : ?>
		<div class="comment-sent" id="comment-sent" role="status" tabindex="-1"<?php echo $trust_sent ? '' : ' hidden'; ?>>
			<span class="comment-sent__icon" aria-hidden="true">
				<?php
				Icons::render(
					'check',
					array(
						'size'         => 16,
						'stroke_width' => 2.6,
					)
				);
				?>
			</span>
			<span class="comment-sent__text"><?php echo esc_html( Template_Texts::get( 'comment_sent' ) ); ?></span>
			<a class="comment-sent__again" href="<?php echo esc_url( get_permalink( $trust_post ) . '#comment-form' ); ?>" data-comment-again><?php esc_html_e( 'Написать ещё', 'trust' ); ?></a>
		</div>

		<form class="comment-form" id="comment-form" method="post" action="<?php echo esc_url( Forms::fallback_url() ); ?>" data-trust-form="<?php echo esc_attr( Comments::FORM ); ?>" data-trust-form-custom novalidate<?php echo $trust_rest . ( $trust_sent ? ' hidden' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() выше. ?>>
			<?php Forms::hidden_fields( Comments::FORM ); ?>
			<input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( (string) $trust_post->ID ); ?>">
			<input type="hidden" name="comment_parent" value="0">
			<input type="hidden" name="_trust_anchor" value="comments">
			<?php $trust_person( 'comment' ); ?>
			<div class="field">
				<label class="field__label" for="comment-text"><?php esc_html_e( 'Комментарий', 'trust' ); ?></label>
				<textarea class="field__input comment-form__text" id="comment-text" name="comment" rows="4" maxlength="3000" required
					placeholder="<?php esc_attr_e( 'Поделитесь опытом или задайте вопрос автору', 'trust' ); ?>"
					aria-describedby="comment-text-error"
					data-error="<?php echo esc_attr( $trust_error( 'comment' ) ); ?>"></textarea>
				<span class="field__error" id="comment-text-error" hidden></span>
			</div>
			<?php Forms::consent_check( 'comment', Comments::FORM ); ?>
			<p class="form__status" role="alert"<?php echo 'error' === $trust_result ? '' : ' hidden'; ?>><?php echo 'error' === $trust_result ? esc_html__( 'Комментарий не отправлен: проверьте поля формы и отправьте его ещё раз.', 'trust' ) : ''; ?></p>
			<div class="comment-form__foot">
				<span class="comment-form__note"><?php echo esc_html( Template_Texts::get( 'comment_note' ) ); ?></span>
				<button type="submit" class="comment-form__submit"><?php esc_html_e( 'Отправить', 'trust' ); ?></button>
			</div>
		</form>
	<?php else : ?>
		<p class="post-comments__closed"><?php esc_html_e( 'Комментарии к статье закрыты.', 'trust' ); ?></p>
	<?php endif; ?>

	<?php if ( $trust_threads ) : ?>
		<ol class="comments" id="comments-list">
			<?php
			foreach ( $trust_threads as $trust_i => $trust_thread ) {
				get_template_part(
					'template-parts/post/comment',
					null,
					array(
						'comment' => $trust_thread['comment'],
						'replies' => $trust_thread['replies'],
						'post'    => $trust_post,
						'open'    => $trust_open,
						'class'   => $trust_i >= $trust_shown ? 'is-more' : '',
					)
				);
			}
			?>
		</ol>
		<?php if ( $trust_more ) : ?>
			<button type="button" class="comments__more" data-comments-more aria-controls="comments-list">
				<?php
				/* translators: 1: число, 2: «комментарий», «комментария» или «комментариев». */
				echo esc_html( sprintf( __( 'Показать ещё %1$s %2$s', 'trust' ), Article::number( $trust_more ), Article::plural( $trust_more, Article::FORMS['comments'] ) ) );
				?>
			</button>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( $trust_open && $trust_threads ) : ?>
		<div class="comment-reply" id="comment-reply" hidden>
			<form class="comment-form comment-form--reply" id="comment-reply-form" method="post" action="<?php echo esc_url( Forms::fallback_url() ); ?>" data-trust-form="<?php echo esc_attr( Comments::FORM ); ?>" data-trust-form-custom novalidate<?php echo $trust_rest; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() выше. ?>>
				<?php Forms::hidden_fields( Comments::FORM ); ?>
				<input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( (string) $trust_post->ID ); ?>">
				<input type="hidden" name="comment_parent" value="0">
				<div class="field">
					<label class="field__label" for="comment-reply-text"><?php esc_html_e( 'Ваш ответ', 'trust' ); ?></label>
					<textarea class="field__input comment-form__text" id="comment-reply-text" name="comment" rows="3" maxlength="3000" required
						aria-describedby="comment-reply-text-error"
						data-error="<?php echo esc_attr( $trust_error( 'comment' ) ); ?>"></textarea>
					<span class="field__error" id="comment-reply-text-error" hidden></span>
				</div>
				<?php $trust_person( 'comment-reply' ); ?>
				<?php Forms::consent_check( 'comment-reply', Comments::FORM ); ?>
				<p class="form__status" role="alert" hidden></p>
				<div class="comment-form__buttons">
					<button type="submit" class="comment-form__submit comment-form__submit--sm"><?php esc_html_e( 'Отправить', 'trust' ); ?></button>
					<button type="button" class="comment-form__cancel" data-comment-cancel><?php esc_html_e( 'Отмена', 'trust' ); ?></button>
				</div>
			</form>
		</div>
		<p class="comment-reply-sent" id="comment-reply-sent" role="status" tabindex="-1" hidden><?php echo esc_html( Template_Texts::get( 'comment_reply_sent' ) ); ?></p>
	<?php endif; ?>
</section>
