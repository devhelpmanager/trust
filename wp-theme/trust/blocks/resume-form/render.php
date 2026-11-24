<?php
/**
 * Блок «Отправить резюме» (макет o-nas/karera.html, секция «Отклик»).
 *
 * Белая секция: слева надзаголовок, H2, текст и плашка «HR-отдел» (email и Telegram для резюме из
 * «Настроек сайта → Контакты»); справа белая карточка с формой: имя и фамилия, телефон или email,
 * вакансия (без вакансии, открытые вакансии, «Стажировка»), ссылка на резюме или портфолио,
 * о себе (необязательно), согласие и капча, если настроена. Отправка — модуль форм (forms.js):
 * заявка «Резюме» (Trust\Forms\Resume_Form), после — «Резюме отправлено» поверх формы (как
 * «Спасибо за отзыв!»). Кнопки «Откликнуться» и «Подать заявку» других блоков выбирают вакансию
 * (view.js). Без JavaScript форма отправляется на admin-post.php.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Contacts;
use Trust\Forms;
use Trust\Forms\Resume_Form;
use Trust\Icons;
use Trust\Vacancies;

defined( 'ABSPATH' ) || exit;

$trust_form    = Forms::type( Resume_Form::TYPE );
$trust_fields  = $trust_form['fields'] ?? array();
$trust_options = (array) ( $trust_fields['vacancy']['options'] ?? array() );
if ( false === get_field( 'intern_option' ) ) {
	unset( $trust_options[ Vacancies::INTERN ] );
}
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_anchor   = '' !== $trust_anchor ? $trust_anchor : Vacancies::FORM_ANCHOR;
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_uid      = Blocks::uid( $block, 'resume' );
$trust_mail     = Contacts::hr_email();
$trust_tg       = Contacts::hr_telegram();
$trust_hr_label = Blocks::text( 'hr_label', __( 'HR-отдел', 'trust' ) );
$trust_privacy  = Forms::privacy_url();
$trust_error    = static fn( string $name ): string => esc_attr( $trust_fields[ $name ]['error'] ?? '' );
$trust_id       = static fn( string $name ): string => esc_attr( $trust_uid . '-' . $name );
?>
<section class="b-resume trust-bg-white" id="<?php echo esc_attr( $trust_anchor ); ?>"<?php echo '' !== trim( (string) get_field( 'title' ) ) ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-resume__inner">
		<div class="b-resume__intro">
			<?php Blocks::section_head( 'b-resume__head', $trust_title_id ); ?>
			<?php if ( $trust_mail || $trust_tg ) : ?>
				<div class="b-resume__hr">
					<?php if ( '' !== $trust_hr_label ) : ?>
						<span class="b-resume__hr-label"><?php echo esc_html( $trust_hr_label ); ?></span>
					<?php endif; ?>
					<?php if ( $trust_mail ) : ?>
						<a class="b-resume__hr-mail" href="<?php echo esc_attr( $trust_mail['href'] ); ?>"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>><?php echo esc_html( $trust_mail['email'] ); ?></a>
					<?php endif; ?>
					<?php if ( $trust_tg ) : ?>
						<a class="b-resume__hr-tg" href="<?php echo esc_url( $trust_tg['url'] ); ?>" target="_blank" rel="noopener"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>>
							<?php
							/* translators: %s: имя в Telegram (@name). */
							echo esc_html( sprintf( __( 'Telegram: %s', 'trust' ), $trust_tg['handle'] ) );
							?>
							<span class="screen-reader-text"><?php esc_html_e( '(откроется в новой вкладке)', 'trust' ); ?></span>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="b-resume__card">
			<form class="form b-resume__form" id="<?php echo esc_attr( $trust_uid ); ?>-form" method="post" action="<?php echo esc_url( Forms::fallback_url() ); ?>" data-trust-form="<?php echo esc_attr( Resume_Form::TYPE ); ?>" data-success="<?php echo esc_attr( $trust_uid ); ?>-sent" aria-label="<?php esc_attr_e( 'Резюме', 'trust' ); ?>">
				<?php Forms::hidden_fields( Resume_Form::TYPE ); ?>
				<input type="hidden" name="_trust_anchor" value="<?php echo esc_attr( $trust_uid ); ?>-form">

				<div class="b-resume__row">
					<div class="field">
						<label class="field__label" for="<?php echo $trust_id( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>"><?php esc_html_e( 'Имя и фамилия', 'trust' ); ?></label>
						<input class="field__input" id="<?php echo $trust_id( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>" name="name" type="text" autocomplete="name" maxlength="100" required aria-describedby="<?php echo $trust_id( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" data-error="<?php echo $trust_error( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_error. ?>">
						<span class="field__error" id="<?php echo $trust_id( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" hidden></span>
					</div>
					<div class="field">
						<label class="field__label" for="<?php echo $trust_id( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>"><?php esc_html_e( 'Телефон или email', 'trust' ); ?></label>
						<input class="field__input" id="<?php echo $trust_id( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>" name="contact" type="text" data-type="contact" autocomplete="tel" maxlength="100" required aria-describedby="<?php echo $trust_id( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" data-error="<?php echo $trust_error( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_error. ?>">
						<span class="field__error" id="<?php echo $trust_id( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" hidden></span>
					</div>
				</div>

				<div class="field">
					<label class="field__label" for="<?php echo $trust_id( 'vacancy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>"><?php esc_html_e( 'Вакансия', 'trust' ); ?></label>
					<select class="field__input" id="<?php echo $trust_id( 'vacancy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>" name="vacancy" data-resume-vacancy>
						<?php foreach ( $trust_options as $trust_value => $trust_label ) : ?>
							<option value="<?php echo esc_attr( (string) $trust_value ); ?>"><?php echo esc_html( $trust_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="field">
					<label class="field__label" for="<?php echo $trust_id( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>"><?php esc_html_e( 'Ссылка на резюме или портфолио', 'trust' ); ?></label>
					<input class="field__input" id="<?php echo $trust_id( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>" name="link" type="url" inputmode="url" autocomplete="url" maxlength="300" required placeholder="<?php echo esc_attr( Blocks::text( 'link_placeholder', __( 'hh.ru, GitHub, Behance или облачный диск', 'trust' ) ) ); ?>" aria-describedby="<?php echo $trust_id( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" data-error="<?php echo $trust_error( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_error. ?>">
					<span class="field__error" id="<?php echo $trust_id( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" hidden></span>
				</div>

				<div class="field">
					<label class="field__label" for="<?php echo $trust_id( 'about' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>"><?php esc_html_e( 'О себе', 'trust' ); ?> <span class="field__optional"><?php esc_html_e( '— необязательно', 'trust' ); ?></span></label>
					<textarea class="field__input" id="<?php echo $trust_id( 'about' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>" name="about" rows="4" maxlength="2000" placeholder="<?php echo esc_attr( Blocks::text( 'about_placeholder', __( 'Почему вам интересна эта работа, какие проекты делали', 'trust' ) ) ); ?>" aria-describedby="<?php echo $trust_id( 'about' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" data-error="<?php echo $trust_error( 'about' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_error. ?>"></textarea>
					<span class="field__error" id="<?php echo $trust_id( 'about' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" hidden></span>
				</div>

				<?php Forms::consent_check( $trust_uid, Resume_Form::TYPE ); ?>
				<?php Forms::captcha( Resume_Form::TYPE ); ?>

				<button type="submit" class="form__submit">
					<?php echo esc_html( Blocks::text( 'submit', __( 'Отправить резюме', 'trust' ) ) ); ?>
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
				<?php Forms::status( Resume_Form::TYPE ); ?>
				<?php if ( $trust_privacy ) : ?>
					<p class="form__note b-resume__note">
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
				Blocks::nbsp( Blocks::text( 'success_title', __( 'Резюме отправлено', 'trust' ) ) ),
				Blocks::text( 'success_text', __( 'Спасибо! HR-менеджер изучит резюме и ответит в течение [N] рабочих дней.', 'trust' ) ),
				Blocks::text( 'again', __( 'Отправить ещё одно резюме', 'trust' ) ),
				true,
				true
			);
			?>
		</div>
	</div>
</section>
