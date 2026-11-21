<?php
/**
 * Блок «Задать вопрос SEO-специалисту» (ТЗ, п. 8.3; макеты seo-vopros/blok-vopros.html —
 * вид «Кнопка», blok-vopros-forma.html — вид «Форма в блоке»).
 *
 * Тёмная карточка (общие стили trust-dark-card): иконка, H2, текст, пункты-плашки с галочкой.
 * «Кнопка» — справа белая кнопка на страницу вопросов и примечание под ней. «Форма в блоке» —
 * под пунктами примечание, справа белая карточка с формой: имя, email, вопрос (10–2000 знаков
 * со счётчиком), капча (если включена), галочка согласия (вместо фразы «Нажимая кнопку…» —
 * решение заказчика), «Получить ответ». Форма — тип «question» (Trust\Questions), как на
 * странице «Вопросы SEO»: вопрос «На утверждении» и заявка. После отправки — «Вопрос отправлен»
 * с адресом посетителя и «Задать ещё вопрос».
 *
 * Пустые поля — из «Настроек сайта → Блоки по умолчанию» (Trust\Block_Texts).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Block_Texts;
use Trust\Blocks;
use Trust\Forms;
use Trust\Icons;
use Trust\Questions;

defined( 'ABSPATH' ) || exit;

$trust_form_view = 'form' === get_field( 'view' );
$trust_bg        = (string) get_field( 'background' );
$trust_bg        = in_array( $trust_bg, array( 'white', 'violet' ), true ) ? $trust_bg : 'lavender';
$trust_anchor    = Blocks::anchor( get_field( 'anchor' ) );
$trust_title     = Block_Texts::field( 'title', 'ask_title' );
$trust_text      = Block_Texts::field( 'text', 'ask_text' );
$trust_points    = array_slice( Block_Texts::lines( Block_Texts::field( 'points', 'ask_points' ) ), 0, 4 );
$trust_note      = Block_Texts::field( 'note', $trust_form_view ? 'ask_form_note' : 'ask_note' );
$trust_title_id  = Blocks::uid( $block, 'title' );
$trust_uid       = Blocks::uid( $block, 'ask' );

$trust_info = static function ( int $size ): void {
	Icons::render( 'info', array( 'size' => $size ) );
};
?>
<section class="b-ask dark-card-sec dark-card-sec--<?php echo esc_attr( $trust_bg ); ?> trust-bg-<?php echo esc_attr( $trust_bg ); ?>"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?> aria-labelledby="<?php echo esc_attr( $trust_title_id ); ?>">
	<div class="dark-card">
		<span class="dark-card__circle dark-card__circle--top" aria-hidden="true"></span>
		<span class="dark-card__circle dark-card__circle--bottom" aria-hidden="true"></span>
		<div class="dark-card__in<?php echo $trust_form_view ? ' dark-card__in--top' : ''; ?>">
			<div class="dark-card__main">
				<span class="dark-card__icon" aria-hidden="true"><?php Icons::render( 'chat-question', array( 'size' => 28 ) ); ?></span>
				<div class="dark-card__body">
					<h2 class="dark-card__title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
					<?php if ( '' !== $trust_text ) : ?>
						<p class="dark-card__text"><?php echo esc_html( $trust_text ); ?></p>
					<?php endif; ?>
					<?php if ( $trust_points ) : ?>
						<ul class="b-ask__points" aria-label="<?php esc_attr_e( 'Условия', 'trust' ); ?>">
							<?php foreach ( $trust_points as $trust_point ) : ?>
								<li>
									<?php
									Icons::render(
										'check',
										array(
											'size'         => 14,
											'stroke_width' => 2.6,
										)
									);
									?>
									<?php echo esc_html( $trust_point ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if ( $trust_form_view && '' !== $trust_note ) : ?>
						<p class="dark-card__note b-ask__form-note"><?php $trust_info( 16 ); ?><span><?php echo esc_html( $trust_note ); ?></span></p>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! $trust_form_view ) : ?>
				<?php
				$trust_button = get_field( 'button' );
				$trust_label  = is_array( $trust_button ) && '' !== trim( (string) ( $trust_button['title'] ?? '' ) ) ? trim( $trust_button['title'] ) : Block_Texts::get( 'ask_button' );
				$trust_url    = is_array( $trust_button ) && ! empty( $trust_button['url'] ) ? (string) $trust_button['url'] : Block_Texts::page_url( 'ask_page', 'questions' );
				$trust_blank  = is_array( $trust_button ) && ! empty( $trust_button['target'] );
				?>
				<div class="dark-card__side">
					<?php if ( '' !== $trust_url ) : ?>
						<a class="dark-card__cta" href="<?php echo esc_url( $trust_url ); ?>"<?php echo $trust_blank ? ' target="_blank" rel="noopener"' : ''; ?>>
							<span class="dark-card__cta-text"><?php echo esc_html( $trust_label ); ?></span>
							<span class="dark-card__cta-arrow" aria-hidden="true">
								<?php
								Icons::render(
									'arrow-right',
									array(
										'size'         => 20,
										'stroke_width' => 2.2,
									)
								);
								?>
							</span>
						</a>
					<?php elseif ( $is_preview ) : ?>
						<?php echo Blocks::editor_warning( __( 'Нет страницы типа «Вопросы SEO»: выберите страницу кнопки в блоке или в «Настройках сайта → Блоки по умолчанию».', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- текст экранирован в editor_warning(). ?>
					<?php endif; ?>
					<?php if ( '' !== $trust_note ) : ?>
						<p class="dark-card__note"><?php $trust_info( 16 ); ?><span><?php echo esc_html( $trust_note ); ?></span></p>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<?php
				$trust_form     = Forms::type( Questions::FORM );
				$trust_fields   = $trust_form['fields'] ?? array();
				$trust_privacy  = Forms::privacy_url();
				$trust_sent_txt = Block_Texts::field( 'success_text', 'ask_success_text' );
				$trust_attrs    = static fn( string $name ): string => sprintf(
					'id="%1$s-%2$s" name="%2$s" aria-describedby="%1$s-%2$s-error" data-error="%3$s"',
					esc_attr( $trust_uid ),
					esc_attr( $name ),
					esc_attr( $trust_fields[ $name ]['error'] ?? '' )
				);
				?>
				<div class="b-ask__form-col">
					<div class="b-ask__card">
						<form class="form b-ask__form" id="<?php echo esc_attr( $trust_uid ); ?>-form" method="post" action="<?php echo esc_url( Forms::fallback_url() ); ?>" data-trust-form="<?php echo esc_attr( Questions::FORM ); ?>" data-success="<?php echo esc_attr( $trust_uid ); ?>-sent" aria-label="<?php esc_attr_e( 'Вопрос SEO-специалисту', 'trust' ); ?>" novalidate>
							<?php Forms::hidden_fields( Questions::FORM ); ?>
							<div class="b-ask__row">
								<div class="field">
									<label class="field__label" for="<?php echo esc_attr( $trust_uid ); ?>-name"><?php esc_html_e( 'Имя', 'trust' ); ?></label>
									<input class="field__input" type="text" autocomplete="given-name" required maxlength="40" placeholder="<?php esc_attr_e( 'Как к вам обращаться', 'trust' ); ?>" <?php echo $trust_attrs( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в $trust_attrs. ?>>
									<span class="field__error" id="<?php echo esc_attr( $trust_uid ); ?>-name-error" hidden></span>
								</div>
								<div class="field">
									<label class="field__label" for="<?php echo esc_attr( $trust_uid ); ?>-email"><?php esc_html_e( 'Email', 'trust' ); ?> <span class="b-ask__label-note"><?php esc_html_e( '— не публикуем', 'trust' ); ?></span></label>
									<input class="field__input" type="email" inputmode="email" autocomplete="email" required maxlength="100" placeholder="name@example.ru" <?php echo $trust_attrs( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в $trust_attrs. ?>>
									<span class="field__error" id="<?php echo esc_attr( $trust_uid ); ?>-email-error" hidden></span>
								</div>
							</div>
							<div class="field">
								<div class="b-ask__label-row">
									<label class="field__label" for="<?php echo esc_attr( $trust_uid ); ?>-question"><?php esc_html_e( 'Ваш вопрос', 'trust' ); ?></label>
									<span class="b-ask__count" aria-hidden="true"><span data-trust-count>0</span> / 2000</span>
								</div>
								<textarea class="field__input b-ask__textarea" rows="3" required minlength="10" maxlength="2000" placeholder="<?php esc_attr_e( 'Например: после переезда на новый домен трафик из Яндекса упал вдвое. С чего начать?', 'trust' ); ?>" <?php echo $trust_attrs( 'question' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в $trust_attrs. ?>></textarea>
								<span class="field__error" id="<?php echo esc_attr( $trust_uid ); ?>-question-error" hidden></span>
							</div>
							<?php Forms::consent_check( $trust_uid, Questions::FORM ); ?>
							<div class="b-ask__submit-row">
								<?php Forms::captcha( Questions::FORM ); ?>
								<button type="submit" class="form__submit b-ask__submit">
									<?php echo esc_html( Block_Texts::field( 'submit', 'ask_submit' ) ); ?>
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
							</div>
							<?php Forms::status( Questions::FORM ); ?>
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
						<div class="b-ask__sent" id="<?php echo esc_attr( $trust_uid ); ?>-sent" role="status" tabindex="-1" hidden>
							<span class="b-ask__sent-icon" aria-hidden="true">
								<?php
								Icons::render(
									'check',
									array(
										'size'         => 26,
										'stroke_width' => 2.4,
									)
								);
								?>
							</span>
							<h3 class="b-ask__sent-title"><?php echo esc_html( Block_Texts::field( 'success_title', 'ask_success_title' ) ); ?></h3>
							<p class="b-ask__sent-text">
								<?php
								$trust_parts = explode( '{email}', esc_html( $trust_sent_txt ), 2 );
								echo $trust_parts[0]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано выше.
								if ( isset( $trust_parts[1] ) ) {
									echo '<strong data-trust-sent-email></strong>' . $trust_parts[1]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано выше.
								}
								?>
							</p>
							<button type="button" class="b-ask__again" data-form-again><?php esc_html_e( 'Задать ещё вопрос', 'trust' ); ?></button>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
