<?php
/**
 * Окно «Заказать обратный звонок» (макета нет — в стиле форм сайта, ТЗ п. 9).
 *
 * Открывается кнопками с атрибутом data-trust-callback (шапка, мобильное меню, подвал).
 * Стандартный <dialog>: фокус остаётся внутри окна, Esc закрывает.
 * Тексты — «Настройки сайта → Блоки по умолчанию». Поля, правила и сообщения об ошибках —
 * Trust\Forms::types()['callback']; отправка — assets/js/forms.js (REST), без JavaScript —
 * admin-post.php.
 *
 * @package Trust
 */

use Trust\Callback;
use Trust\Forms;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_consent = Forms::consent_url();
$trust_privacy = Forms::privacy_url();
$trust_form    = Forms::type( 'callback' );
?>
<dialog class="callback-modal" id="callback-modal" aria-labelledby="callback-modal-title" aria-describedby="callback-modal-text">
	<div class="callback-modal__inner">
		<button type="button" class="callback-modal__close" data-callback-close aria-label="<?php esc_attr_e( 'Закрыть', 'trust' ); ?>">
			<?php Icons::render( 'close', array( 'size' => 22 ) ); ?>
		</button>

		<div class="callback-modal__form-view">
			<h2 class="callback-modal__title" id="callback-modal-title"><?php echo esc_html( Callback::text( 'title' ) ); ?></h2>
			<p class="callback-modal__text" id="callback-modal-text"><?php echo esc_html( Callback::text( 'text' ) ); ?></p>

			<form class="form" id="form-callback" method="post" action="<?php echo esc_url( Forms::fallback_url() ); ?>" data-trust-form="callback" data-trust-form-custom>
				<?php Forms::hidden_fields( 'callback' ); ?>
				<div class="field">
					<label class="field__label" for="callback-name"><?php echo esc_html( Callback::text( 'name_label' ) ); ?></label>
					<input class="field__input" id="callback-name" name="name" type="text" autocomplete="name" maxlength="100" required
						placeholder="<?php echo esc_attr( Callback::text( 'name_placeholder' ) ); ?>"
						aria-describedby="callback-name-error"
						data-error="<?php echo esc_attr( $trust_form['fields']['name']['error'] ?? '' ); ?>">
					<span class="field__error" id="callback-name-error" hidden></span>
				</div>

				<div class="field">
					<label class="field__label" for="callback-phone"><?php echo esc_html( Callback::text( 'phone_label' ) ); ?></label>
					<input class="field__input" id="callback-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" maxlength="30" required
						placeholder="<?php echo esc_attr( Callback::text( 'phone_placeholder' ) ); ?>"
						aria-describedby="callback-phone-error"
						data-error="<?php echo esc_attr( $trust_form['fields']['phone']['error'] ?? '' ); ?>">
					<span class="field__error" id="callback-phone-error" hidden></span>
				</div>

				<div class="field">
					<label class="check">
						<input class="check__input" type="checkbox" name="consent" value="1" required
							aria-describedby="callback-consent-error"
							data-error="<?php echo esc_attr( $trust_form['consents']['consent']['error'] ?? '' ); ?>">
						<span>
							<?php if ( $trust_consent ) : ?>
								<?php
								printf(
									/* translators: %s: ссылка «согласие на обработку персональных данных». */
									esc_html__( 'Я даю %s', 'trust' ),
									'<a href="' . esc_url( $trust_consent ) . '" target="_blank" rel="noopener">' . esc_html__( 'согласие на обработку персональных данных', 'trust' ) . '</a>'
								);
								?>
							<?php else : ?>
								<?php esc_html_e( 'Я даю согласие на обработку персональных данных', 'trust' ); ?>
							<?php endif; ?>
						</span>
					</label>
					<span class="field__error field__error--check" id="callback-consent-error" hidden></span>
				</div>

				<button type="submit" class="form__submit">
					<?php echo esc_html( Callback::text( 'submit' ) ); ?>
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

				<?php Forms::status( 'callback' ); ?>

				<?php if ( $trust_privacy ) : ?>
					<p class="form__note">
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
		</div>

		<div class="callback-modal__success" role="status" hidden>
			<span class="form-success__icon" aria-hidden="true">
				<?php
				Icons::render(
					'check',
					array(
						'size'         => 28,
						'stroke_width' => 2.4,
					)
				);
				?>
			</span>
			<h2 class="form-success__title"><?php echo esc_html( Callback::text( 'success_title' ) ); ?></h2>
			<p class="form-success__text"><?php echo esc_html( Callback::text( 'success_text' ) ); ?></p>
			<button type="button" class="btn btn--outline btn--sm" data-callback-close><?php esc_html_e( 'Закрыть', 'trust' ); ?></button>
		</div>
	</div>
</dialog>
