<?php
/**
 * Окно «Написать руководителю» блока «Слово руководителя» (правка заказчика; макета нет —
 * в стиле окна «Заказать обратный звонок»).
 *
 * Кому — фото, имя и должность руководителя; форма: имя, телефон или email, сообщение,
 * согласие, капча (если настроена). Поля, правила и сообщения об ошибках —
 * Trust\Forms\Leader_Messages; открытие и экран «Сообщение отправлено» — assets/js/callback.js,
 * отправка — forms.js (REST). Сообщения — в «Заявки → Обращения к руководителю».
 *
 * Переменные из render.php: $trust_modal_id, $trust_name, $trust_role, $trust_photo_id.
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Forms;
use Trust\Forms\Leader_Messages;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_form    = Forms::type( Leader_Messages::TYPE );
$trust_privacy = Forms::privacy_url();
$trust_to_img  = Blocks::image( $trust_photo_id, 'thumbnail', array( 'class' => 'b-leader__to-photo' ), '', true );

/**
 * Поле формы: подпись, поле ввода (или многострочное), место для ошибки.
 *
 * @param string $name  Имя поля (как в Trust\Forms\Leader_Messages::types()).
 * @param string $label Подпись.
 * @param string $attrs Атрибуты поля (экранированы).
 */
$trust_modal_field = static function ( string $name, string $label, string $attrs ) use ( $trust_modal_id, $trust_form ): void {
	$id     = $trust_modal_id . '-' . $name;
	$common = sprintf(
		'class="field__input" id="%1$s" name="%2$s" required aria-describedby="%1$s-error" data-error="%3$s" %4$s',
		esc_attr( $id ),
		esc_attr( $name ),
		esc_attr( $trust_form['fields'][ $name ]['error'] ?? '' ),
		$attrs
	);
	echo '<div class="field">';
	printf( '<label class="field__label" for="%s">%s</label>', esc_attr( $id ), esc_html( $label ) );
	echo 'message' === $name ? '<textarea rows="5" ' . $common . '></textarea>' : '<input ' . $common . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- атрибуты экранированы выше.
	printf( '<span class="field__error" id="%s-error" hidden></span>', esc_attr( $id ) );
	echo '</div>';
};
?>
<dialog class="callback-modal b-leader__modal" id="<?php echo esc_attr( $trust_modal_id ); ?>" aria-labelledby="<?php echo esc_attr( $trust_modal_id ); ?>-title">
	<div class="callback-modal__inner">
		<button type="button" class="callback-modal__close" data-callback-close aria-label="<?php esc_attr_e( 'Закрыть', 'trust' ); ?>">
			<?php Icons::render( 'close', array( 'size' => 22 ) ); ?>
		</button>

		<div class="callback-modal__form-view">
			<h2 class="callback-modal__title" id="<?php echo esc_attr( $trust_modal_id ); ?>-title"><?php echo esc_html( Blocks::text( 'modal_title', __( 'Написать руководителю', 'trust' ) ) ); ?></h2>
			<?php if ( '' !== $trust_name ) : ?>
				<p class="b-leader__to">
					<?php echo $trust_to_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
					<span class="b-leader__to-who">
						<span class="b-leader__to-name"><?php echo esc_html( $trust_name ); ?></span>
						<?php if ( '' !== $trust_role ) : ?>
							<span class="b-leader__to-role"><?php echo esc_html( $trust_role ); ?></span>
						<?php endif; ?>
					</span>
				</p>
			<?php endif; ?>
			<p class="callback-modal__text"><?php echo esc_html( Blocks::text( 'modal_text', __( 'Вопрос, предложение или отзыв о работе команды — сообщение получит руководитель агентства.', 'trust' ) ) ); ?></p>

			<form class="form" id="<?php echo esc_attr( $trust_modal_id ); ?>-form" method="post" action="<?php echo esc_url( Forms::fallback_url() ); ?>" data-trust-form="<?php echo esc_attr( Leader_Messages::TYPE ); ?>" data-trust-form-custom>
				<?php Forms::hidden_fields( Leader_Messages::TYPE ); ?>
				<input type="hidden" name="_trust_anchor" value="<?php echo esc_attr( $trust_modal_id ); ?>-form">
				<?php
				$trust_modal_field( 'name', __( 'Имя', 'trust' ), 'type="text" autocomplete="name" maxlength="100" placeholder="' . esc_attr__( 'Как к вам обращаться', 'trust' ) . '"' );
				$trust_modal_field( 'contact', __( 'Телефон или email', 'trust' ), 'type="text" autocomplete="email" maxlength="100" placeholder="' . esc_attr__( 'Телефон или email', 'trust' ) . '"' );
				$trust_modal_field( 'message', __( 'Сообщение', 'trust' ), 'minlength="10" maxlength="2000" placeholder="' . esc_attr__( 'Ваш вопрос, предложение или отзыв', 'trust' ) . '"' );
				Forms::consent_check( $trust_modal_id, Leader_Messages::TYPE );
				Forms::captcha( Leader_Messages::TYPE );
				?>

				<button type="submit" class="form__submit">
					<?php echo esc_html( Blocks::text( 'modal_submit', __( 'Отправить сообщение', 'trust' ) ) ); ?>
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

				<?php Forms::status( Leader_Messages::TYPE ); ?>

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
			<h2 class="form-success__title"><?php esc_html_e( 'Сообщение отправлено', 'trust' ); ?></h2>
			<p class="form-success__text"><?php echo esc_html( Blocks::text( 'success_text', __( 'Спасибо! Руководитель прочитает его и при необходимости свяжется с вами.', 'trust' ) ) ); ?></p>
			<button type="button" class="btn btn--outline btn--sm" data-callback-close><?php esc_html_e( 'Закрыть', 'trust' ); ?></button>
		</div>
	</div>
</dialog>
