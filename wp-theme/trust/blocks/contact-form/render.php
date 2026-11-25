<?php
/**
 * Блок «Расскажите о задаче» (макет o-nas/kontakty.html, секция «Заявка»).
 *
 * Лавандовая секция: слева надзаголовок, H2, текст и шаги «что будет дальше» (1, 2, 3); справа
 * белая карточка с формой: имя, телефон или email, задача (необязательно), «Как с вами связаться»
 * (переключатели-«таблетки», первый выбран), согласие, капча — если настроена. Отправка — модуль
 * форм (forms.js): заявка «Заявка с контактов» (Trust\Forms\Contact_Form), после — «Заявка
 * отправлена» поверх формы (значок слева, заголовок справа) с текстом «Менеджер позвонит по
 * телефону…» по выбранному способу (view.js). Без JavaScript форма отправляется на admin-post.php.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Forms;
use Trust\Forms\Contact_Form;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_form     = Forms::type( Contact_Form::TYPE );
$trust_fields   = $trust_form['fields'] ?? array();
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_anchor   = '' !== $trust_anchor ? $trust_anchor : 'contact-form';
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_uid      = Blocks::uid( $block, 'contact' );
$trust_privacy  = Forms::privacy_url();
$trust_steps    = array_values( array_filter( array_map( static fn( $row ) => is_array( $row ) ? trim( (string) ( $row['text'] ?? '' ) ) : '', (array) get_field( 'steps' ) ), 'strlen' ) );
$trust_channels = array_values( array_filter( (array) get_field( 'channels' ), static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['label'] ?? '' ) ) ) );
$trust_channels = $trust_channels ? $trust_channels : Contact_Form::default_channels();
$trust_template = Blocks::text( 'success_text', __( 'Спасибо! Менеджер {channel} и уточнит детали задачи.', 'trust' ) );
$trust_say      = static fn( array $row ): string => trim( (string) ( $row['say'] ?? '' ) ) !== '' ? trim( (string) $row['say'] ) : __( 'свяжется с вами', 'trust' );
$trust_error    = static fn( string $name ): string => esc_attr( $trust_fields[ $name ]['error'] ?? '' );
$trust_id       = static fn( string $name ): string => esc_attr( $trust_uid . '-' . $name );
?>
<section class="b-cform trust-bg-lavender" id="<?php echo esc_attr( $trust_anchor ); ?>"<?php echo '' !== trim( (string) get_field( 'title' ) ) ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-cform__inner">
		<div class="b-cform__intro">
			<?php Blocks::section_head( 'b-cform__head', $trust_title_id ); ?>
			<?php if ( $trust_steps ) : ?>
				<ol class="b-cform__steps">
					<?php foreach ( $trust_steps as $trust_i => $trust_step ) : ?>
						<li><span class="b-cform__num" aria-hidden="true"><?php echo esc_html( (string) ( $trust_i + 1 ) ); ?></span><span><?php echo esc_html( $trust_step ); ?></span></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</div>

		<div class="b-cform__card">
			<form class="form b-cform__form" id="<?php echo esc_attr( $trust_uid ); ?>-form" method="post" action="<?php echo esc_url( Forms::fallback_url() ); ?>" data-trust-form="<?php echo esc_attr( Contact_Form::TYPE ); ?>" data-success="<?php echo esc_attr( $trust_uid ); ?>-sent" data-success-template="<?php echo esc_attr( $trust_template ); ?>" aria-label="<?php esc_attr_e( 'Заявка', 'trust' ); ?>">
				<?php Forms::hidden_fields( Contact_Form::TYPE ); ?>
				<input type="hidden" name="_trust_anchor" value="<?php echo esc_attr( $trust_uid ); ?>-form">

				<div class="b-cform__row">
					<div class="field">
						<label class="field__label" for="<?php echo $trust_id( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>"><?php esc_html_e( 'Имя', 'trust' ); ?></label>
						<input class="field__input" id="<?php echo $trust_id( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>" name="name" type="text" autocomplete="name" maxlength="100" required placeholder="<?php esc_attr_e( 'Как к вам обращаться', 'trust' ); ?>" aria-describedby="<?php echo $trust_id( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" data-error="<?php echo $trust_error( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_error. ?>">
						<span class="field__error" id="<?php echo $trust_id( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" hidden></span>
					</div>
					<div class="field">
						<label class="field__label" for="<?php echo $trust_id( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>"><?php esc_html_e( 'Телефон или email', 'trust' ); ?></label>
						<input class="field__input" id="<?php echo $trust_id( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>" name="contact" type="text" autocomplete="tel" minlength="5" maxlength="100" required placeholder="<?php echo esc_attr( Blocks::text( 'contact_placeholder', Forms::phone_example() ) ); ?>" aria-describedby="<?php echo $trust_id( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" data-error="<?php echo $trust_error( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_error. ?>">
						<span class="field__error" id="<?php echo $trust_id( 'contact' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" hidden></span>
					</div>
				</div>

				<div class="field">
					<label class="field__label" for="<?php echo $trust_id( 'task' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>"><?php esc_html_e( 'Задача', 'trust' ); ?> <span class="field__optional"><?php esc_html_e( '— необязательно', 'trust' ); ?></span></label>
					<textarea class="field__input" id="<?php echo $trust_id( 'task' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>" name="task" rows="4" maxlength="2000" placeholder="<?php echo esc_attr( Blocks::text( 'task_placeholder', __( 'Например: нужен аудит и продвижение интернет-магазина мебели', 'trust' ) ) ); ?>" aria-describedby="<?php echo $trust_id( 'task' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" data-error="<?php echo $trust_error( 'task' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_error. ?>"></textarea>
					<span class="field__error" id="<?php echo $trust_id( 'task' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_attr() в $trust_id. ?>-error" hidden></span>
				</div>

				<fieldset class="b-cform__channels">
					<legend class="field__label"><?php echo esc_html( Blocks::text( 'channel_legend', __( 'Как с вами связаться', 'trust' ) ) ); ?></legend>
					<div class="b-cform__options">
						<?php foreach ( $trust_channels as $trust_i => $trust_channel ) : ?>
							<label class="b-cform__option">
								<input type="radio" name="channel" value="<?php echo esc_attr( trim( (string) $trust_channel['label'] ) ); ?>" data-say="<?php echo esc_attr( $trust_say( $trust_channel ) ); ?>"<?php checked( 0 === $trust_i && ! $is_preview ); // В превью без checked: React ругается на поле без обработчика. ?>>
								<?php echo esc_html( trim( (string) $trust_channel['label'] ) ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>

				<?php Forms::consent_check( $trust_uid, Contact_Form::TYPE ); ?>
				<?php Forms::captcha( Contact_Form::TYPE ); ?>

				<button type="submit" class="form__submit">
					<?php echo esc_html( Blocks::text( 'submit', __( 'Отправить заявку', 'trust' ) ) ); ?>
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
				<?php Forms::status( Contact_Form::TYPE ); ?>
				<?php if ( $trust_privacy ) : ?>
					<p class="form__note b-cform__note">
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
				Blocks::nbsp( Blocks::text( 'success_title', __( 'Заявка отправлена', 'trust' ) ) ),
				str_replace( '{channel}', $trust_say( $trust_channels[0] ), $trust_template ),
				Blocks::text( 'again', __( 'Отправить ещё одну заявку', 'trust' ) ),
				true,
				true
			);
			?>
		</div>
	</div>
</section>
