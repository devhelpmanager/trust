<?php
/**
 * Блок «Кнопка связи» (макет holst-stranitsy/Main → ContactSwipe, seo-bloki/09-knopka-svyazi.html).
 *
 * Слева — надзаголовок, H2 и текст, справа — белая карточка с формой «Обратный звонок»: имя,
 * телефон, галочка согласия (вместо фразы «Отправляя заявку…», решение заказчика) и свайп-кнопка.
 * Заявка уходит, если дотянуть кнопку до конца или нажать на ней Enter (и Enter в поле).
 * Пока галочка не отмечена и поля не заполнены, заявка не уходит — под полями ошибки.
 * Проверка и отправка — модуль форм (assets/js/forms.js, REST), без JavaScript — обычная кнопка.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Forms;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_text     = static function ( string $name, string $fallback ): string {
	$value = get_field( $name );
	return null === $value || '' === trim( (string) $value ) ? $fallback : (string) $value;
};
$trust_title    = (string) get_field( 'title' );
$trust_eyebrow  = (string) get_field( 'eyebrow' );
$trust_lead     = (string) get_field( 'lead' );
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_uid      = Blocks::uid( $block, 'swipe' );
$trust_form     = Forms::type( 'callback' );
$trust_consent  = Forms::consent_url();
?>
<section class="b-swipe trust-bg-violet"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-swipe__inner">
		<div class="b-swipe__head">
			<?php if ( $trust_eyebrow ) : ?>
				<span class="eyebrow"><?php echo esc_html( $trust_eyebrow ); ?></span>
			<?php endif; ?>
			<?php if ( $trust_title ) : ?>
				<h2 class="section-title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
			<?php endif; ?>
			<?php if ( $trust_lead ) : ?>
				<p class="section-lead section-lead--lg"><?php echo esc_html( $trust_lead ); ?></p>
			<?php endif; ?>
		</div>

		<form class="b-swipe__form" id="<?php echo esc_attr( $trust_uid ); ?>-form" method="post" action="<?php echo esc_url( Forms::fallback_url() ); ?>" data-trust-form="callback" data-trust-form-custom novalidate>
			<span class="b-swipe__label"><?php echo esc_html( $trust_text( 'form_title', __( 'Обратный звонок', 'trust' ) ) ); ?></span>
			<?php Forms::hidden_fields( 'callback' ); ?>
			<input type="hidden" name="_trust_anchor" value="<?php echo esc_attr( $trust_uid ); ?>-form">

			<div class="field">
				<input class="field__input" id="<?php echo esc_attr( $trust_uid ); ?>-name" name="name" type="text" autocomplete="name" maxlength="100" required
					aria-label="<?php esc_attr_e( 'Имя', 'trust' ); ?>"
					placeholder="<?php echo esc_attr( $trust_text( 'name_placeholder', __( 'Ваше имя', 'trust' ) ) ); ?>"
					aria-describedby="<?php echo esc_attr( $trust_uid ); ?>-name-error"
					data-error="<?php echo esc_attr( $trust_form['fields']['name']['error'] ?? '' ); ?>">
				<span class="field__error" id="<?php echo esc_attr( $trust_uid ); ?>-name-error" hidden></span>
			</div>

			<div class="field">
				<input class="field__input" id="<?php echo esc_attr( $trust_uid ); ?>-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" maxlength="30" required
					aria-label="<?php esc_attr_e( 'Телефон', 'trust' ); ?>"
					placeholder="<?php echo esc_attr( $trust_text( 'phone_placeholder', Forms::phone_example() ) ); ?>"
					aria-describedby="<?php echo esc_attr( $trust_uid ); ?>-phone-error"
					data-error="<?php echo esc_attr( $trust_form['fields']['phone']['error'] ?? '' ); ?>">
				<span class="field__error" id="<?php echo esc_attr( $trust_uid ); ?>-phone-error" hidden></span>
			</div>

			<div class="field b-swipe__consent">
				<label class="check">
					<input class="check__input" type="checkbox" name="consent" value="1" required
						aria-describedby="<?php echo esc_attr( $trust_uid ); ?>-consent-error"
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
				<span class="field__error field__error--check" id="<?php echo esc_attr( $trust_uid ); ?>-consent-error" hidden></span>
			</div>

			<div class="b-swipe__track" data-swipe>
				<span class="b-swipe__fill" aria-hidden="true"></span>
				<span class="b-swipe__hint" aria-hidden="true">
					<?php echo esc_html( $trust_text( 'swipe_label', __( 'Свайпните', 'trust' ) ) ); ?>
					<svg width="18" height="12" viewBox="0 0 22 14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M2 2l5 5-5 5" opacity="0.35"/><path d="M9 2l5 5-5 5" opacity="0.65"/><path d="M16 2l5 5-5 5"/></svg>
				</span>
				<span class="b-swipe__sending" aria-hidden="true"><?php echo esc_html( $trust_text( 'sending', __( 'Отправка…', 'trust' ) ) ); ?></span>
				<button type="button" class="b-swipe__knob" aria-label="<?php esc_attr_e( 'Отправить заявку: потяните вправо или нажмите Enter', 'trust' ); ?>">
					<?php Icons::render( 'phone', array( 'size' => 18 ) ); ?>
				</button>
			</div>

			<button type="submit" class="form__submit b-swipe__fallback"><?php esc_html_e( 'Отправить заявку', 'trust' ); ?></button>

			<div class="b-swipe__sent" role="status" hidden>
				<span class="b-swipe__sent-icon" aria-hidden="true">
					<?php
					Icons::render(
						'check',
						array(
							'size'         => 18,
							'stroke_width' => 2.4,
						)
					);
					?>
				</span>
				<span class="b-swipe__sent-text">
					<span class="b-swipe__sent-title"><?php echo esc_html( $trust_text( 'success_title', __( 'Заявка отправлена', 'trust' ) ) ); ?></span>
					<span class="b-swipe__sent-note"><?php echo esc_html( $trust_text( 'success_text', __( 'Мы перезвоним вам в ближайшее время', 'trust' ) ) ); ?></span>
				</span>
			</div>
			<button type="button" class="b-swipe__again" hidden><?php echo esc_html( $trust_text( 'again', __( 'Отправить ещё одну заявку', 'trust' ) ) ); ?></button>

			<?php Forms::status( 'callback' ); ?>
		</form>
	</div>
</section>
