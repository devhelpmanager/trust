<?php
/**
 * Блок «Оставьте отзыв» (макет o-nas/otzyvy.html, секция «Обратная связь»).
 *
 * Лавандовая секция: слева надзаголовок, H2, текст и ссылки на площадки отзывов; справа белая
 * карточка с формой (уже ~900 px — под текстом): имя и фамилия, компания и должность
 * (необязательно), услуга (термины «Услуги» и «Другое»), телефон или email (с подсказкой),
 * оценка звёздами 1–5 с подписью, отзыв (от 30 символов), две галочки — согласие на обработку и
 * на распространение (публикацию) — и капча, если настроена. Отправка — модуль форм (forms.js):
 * отзыв «На утверждении» и заявка (Trust\Forms\Review_Form), после — «Спасибо за отзыв!» и
 * «Написать ещё один отзыв» поверх формы (правка заказчика: форма остаётся на месте под матовым
 * слоем, блок не «прыгает»; значок слева, заголовок справа). Без JavaScript форма отправляется на
 * admin-post.php.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Forms;
use Trust\Forms\Review_Form;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_form      = Forms::type( Review_Form::TYPE );
$trust_fields    = $trust_form['fields'] ?? array();
$trust_anchor    = Blocks::anchor( get_field( 'anchor' ) );
$trust_anchor    = '' !== $trust_anchor ? $trust_anchor : 'leave-review';
$trust_title_id  = Blocks::uid( $block, 'title' );
$trust_uid       = Blocks::uid( $block, 'review' );
$trust_platforms = array_values( array_filter( (array) get_field( 'platforms' ), static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['name'] ?? '' ) ) && '' !== trim( (string) ( $row['url'] ?? '' ) ) ) );
$trust_privacy   = Forms::privacy_url();
$trust_publish   = Review_Form::publish_consent_url();
$trust_error     = static fn( string $name ): string => esc_attr( $trust_fields[ $name ]['error'] ?? '' );
$trust_id        = static fn( string $name ): string => esc_attr( $trust_uid . '-' . $name );
$trust_stars     = '<svg width="32" height="32" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.5l1.2-6.5L2.5 9.4l6.6-.9z"/></svg>';
?>
<section class="b-rform trust-bg-lavender" id="<?php echo esc_attr( $trust_anchor ); ?>"<?php echo '' !== trim( (string) get_field( 'title' ) ) ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-rform__inner">
		<div class="b-rform__intro">
			<?php Blocks::section_head( 'b-rform__head', $trust_title_id ); ?>
			<?php if ( $trust_platforms ) : ?>
				<div class="b-rform__platforms">
					<span class="b-rform__platforms-label"><?php echo esc_html( Blocks::text( 'platforms_label', __( 'Или оставьте отзыв на площадке:', 'trust' ) ) ); ?></span>
					<ul class="b-rform__platform-list">
						<?php foreach ( $trust_platforms as $trust_row ) : ?>
							<li>
								<a class="b-rform__platform" href="<?php echo esc_url( $trust_row['url'] ); ?>" target="_blank" rel="noopener">
									<?php echo esc_html( $trust_row['name'] ); ?>
									<span class="screen-reader-text"><?php esc_html_e( '(откроется в новой вкладке)', 'trust' ); ?></span>
									<?php
									Icons::render(
										'arrow-up-right',
										array(
											'size'         => 15,
											'stroke_width' => 2,
										)
									);
									?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>

		<div class="b-rform__card">
			<form class="form b-rform__form" id="<?php echo esc_attr( $trust_uid ); ?>-form" method="post" action="<?php echo esc_url( Forms::fallback_url() ); ?>" data-trust-form="<?php echo esc_attr( Review_Form::TYPE ); ?>" data-success="<?php echo esc_attr( $trust_uid ); ?>-sent" aria-label="<?php esc_attr_e( 'Отзыв', 'trust' ); ?>">
				<?php Forms::hidden_fields( Review_Form::TYPE ); ?>
				<input type="hidden" name="_trust_anchor" value="<?php echo esc_attr( $trust_uid ); ?>-form">

				<div class="b-rform__row">
					<div class="field">
						<label class="field__label" for="<?php echo $trust_id( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>"><?php esc_html_e( 'Имя и фамилия', 'trust' ); ?></label>
						<input class="field__input" id="<?php echo $trust_id( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>" name="name" type="text" autocomplete="name" maxlength="100" required aria-describedby="<?php echo $trust_id( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" data-error="<?php echo $trust_error( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_error. ?>">
						<span class="field__error" id="<?php echo $trust_id( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" hidden></span>
					</div>
					<div class="field">
						<label class="field__label" for="<?php echo $trust_id( 'company' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>"><?php esc_html_e( 'Компания и должность', 'trust' ); ?> <span class="field__optional"><?php esc_html_e( '— необязательно', 'trust' ); ?></span></label>
						<input class="field__input" id="<?php echo $trust_id( 'company' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>" name="company" type="text" autocomplete="organization" maxlength="150">
					</div>
				</div>

				<div class="b-rform__row">
					<div class="field">
						<label class="field__label" for="<?php echo $trust_id( 'service' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>"><?php esc_html_e( 'Услуга', 'trust' ); ?></label>
						<select class="field__input" id="<?php echo $trust_id( 'service' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>" name="service" required aria-describedby="<?php echo $trust_id( 'service' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" data-error="<?php echo $trust_error( 'service' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_error. ?>">
							<option value=""><?php esc_html_e( 'Выберите услугу', 'trust' ); ?></option>
							<?php foreach ( (array) ( $trust_fields['service']['options'] ?? array() ) as $trust_value => $trust_label ) : ?>
								<option value="<?php echo esc_attr( (string) $trust_value ); ?>"><?php echo esc_html( $trust_label ); ?></option>
							<?php endforeach; ?>
						</select>
						<span class="field__error" id="<?php echo $trust_id( 'service' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" hidden></span>
					</div>
					<div class="field">
						<label class="field__label" for="<?php echo $trust_id( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>"><?php esc_html_e( 'Телефон или email', 'trust' ); ?></label>
						<input class="field__input" id="<?php echo $trust_id( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>" name="contact" type="text" data-type="contact" autocomplete="email" maxlength="100" required aria-describedby="<?php echo $trust_id( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error <?php echo $trust_id( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-note" data-error="<?php echo $trust_error( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_error. ?>">
						<span class="field__error" id="<?php echo $trust_id( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" hidden></span>
						<span class="field__note" id="<?php echo $trust_id( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-note"><?php echo esc_html( Blocks::text( 'contact_note', __( 'Только для подтверждения отзыва, на сайте не публикуется', 'trust' ) ) ); ?></span>
					</div>
				</div>

				<fieldset class="b-rform__rating" aria-describedby="<?php echo $trust_id( 'rating' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error">
					<legend class="field__label"><?php esc_html_e( 'Оценка', 'trust' ); ?></legend>
					<div class="b-rform__rating-row">
						<div class="b-rform__stars">
							<?php foreach ( Review_Form::RATINGS as $trust_value => $trust_word ) : ?>
								<label class="b-rform__star">
									<input type="radio" name="rating" value="<?php echo esc_attr( $trust_value ); ?>" data-word="<?php echo esc_attr( $trust_word ); ?>"
										<?php /* translators: %s: оценка от 1 до 5. */ ?>
										aria-label="<?php echo esc_attr( sprintf( __( '%s из 5', 'trust' ), $trust_value ) ); ?>"
										<?php if ( '1' === (string) $trust_value ) : ?>
											required aria-describedby="<?php echo $trust_id( 'rating' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" data-error="<?php echo $trust_error( 'rating' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_error. ?>"
										<?php endif; ?>>
									<?php echo $trust_stars; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- постоянный SVG. ?>
								</label>
							<?php endforeach; ?>
						</div>
						<span class="b-rform__rating-word" data-rating-word data-empty="<?php esc_attr_e( 'Выберите от 1 до 5', 'trust' ); ?>"><?php esc_html_e( 'Выберите от 1 до 5', 'trust' ); ?></span>
					</div>
					<span class="field__error" id="<?php echo $trust_id( 'rating' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" hidden></span>
				</fieldset>

				<div class="field">
					<label class="field__label" for="<?php echo $trust_id( 'text' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>"><?php esc_html_e( 'Отзыв', 'trust' ); ?></label>
					<textarea class="field__input" id="<?php echo $trust_id( 'text' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>" name="text" rows="5" minlength="30" maxlength="2000" required placeholder="<?php echo esc_attr( Blocks::text( 'text_placeholder', __( 'Какая была задача, как шла работа, что получилось', 'trust' ) ) ); ?>" aria-describedby="<?php echo $trust_id( 'text' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" data-error="<?php echo $trust_error( 'text' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_error. ?>"></textarea>
					<span class="field__error" id="<?php echo $trust_id( 'text' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" hidden></span>
				</div>

				<?php Forms::consent_check( $trust_uid, Review_Form::TYPE ); ?>
				<div class="field form__consent">
					<label class="check">
						<input class="check__input" type="checkbox" name="publish" value="1" required aria-describedby="<?php echo $trust_id( 'publish' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" data-error="<?php echo esc_attr( $trust_form['consents']['publish']['error'] ?? '' ); ?>">
						<span>
							<?php esc_html_e( 'Разрешаю опубликовать отзыв с моим именем, компанией и должностью', 'trust' ); ?>
							<?php if ( '' !== $trust_publish ) : ?>
								— <a href="<?php echo esc_url( $trust_publish ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'согласие на распространение данных', 'trust' ); ?></a>
							<?php endif; ?>
						</span>
					</label>
					<span class="field__error field__error--check" id="<?php echo $trust_id( 'publish' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" hidden></span>
				</div>

				<?php Forms::captcha( Review_Form::TYPE ); ?>

				<button type="submit" class="form__submit">
					<?php echo esc_html( Blocks::text( 'submit', __( 'Отправить отзыв', 'trust' ) ) ); ?>
					<?php
					Icons::render(
						'arrow-right',
						array(
							'size'         => 18,
							'stroke_width' => 2.2,
						)
					);
					?>
				</button>
				<?php Forms::status( Review_Form::TYPE ); ?>
				<?php if ( $trust_privacy ) : ?>
					<p class="form__note b-rform__note">
						<?php
						printf(
							/* translators: %s: ссылка «политике конфиденциальности». */
							esc_html__( 'Как мы храним и защищаем данные — в %s.', 'trust' ),
							'<a href="' . esc_url( $trust_privacy ) . '" target="_blank" rel="noopener">' . esc_html__( 'политике конфиденциальности', 'trust' ) . '</a>'
						);
						?>
					</p>
				<?php endif; ?>
			</form>
			<?php
			Forms::sent_screen(
				$trust_uid . '-sent',
				Blocks::nbsp( Blocks::text( 'success_title', __( 'Спасибо за отзыв!', 'trust' ) ) ),
				Blocks::text( 'success_text', __( 'Перед публикацией мы свяжемся с вами, чтобы подтвердить отзыв. Контакты на сайте не публикуются.', 'trust' ) ),
				Blocks::text( 'again', __( 'Написать ещё один отзыв', 'trust' ) ),
				true
			);
			?>
		</div>
	</div>
</section>
