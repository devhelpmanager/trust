<?php
/**
 * Блок «Первый экран вопросов» (макет seo-vopros/vopros-seo.html).
 *
 * Крошки над тёмной карточкой; в карточке плашка, H1 (поле «H1» в SEO страницы или заголовок)
 * на всю ширину (правка заказчика: в макете он в колонке рядом с формой), текст, форма вопроса,
 * пункты и ссылка на ответы. С 1000 px форма справа от текста и пунктов, на телефоне — сразу
 * после текста. Форма — тип «question» (Trust\Questions): заявка и вопрос «На утверждении»;
 * вместо фразы «Нажимая кнопку…» — галочка согласия (решение заказчика).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Breadcrumbs;
use Trust\Forms;
use Trust\Icons;
use Trust\Questions;
use Trust\Seo;

defined( 'ABSPATH' ) || exit;

$trust_h1       = Seo::h1( (int) $post_id );
$trust_badge    = trim( (string) get_field( 'badge' ) );
$trust_lead     = trim( (string) get_field( 'lead' ) );
$trust_points   = array_values( array_filter( (array) get_field( 'points' ), static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['text'] ?? '' ) ) ) );
$trust_link     = get_field( 'link' );
$trust_submit   = trim( (string) get_field( 'submit' ) );
$trust_sent     = trim( (string) get_field( 'success_title' ) );
$trust_sent_txt = trim( (string) get_field( 'success_text' ) );
$trust_sent_txt = '' !== $trust_sent_txt ? $trust_sent_txt : __( 'Специалист ответит здесь, на сайте: ответ появится в списке ниже. Когда он будет готов, пришлём ссылку на {email}.', 'trust' );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_uid      = Blocks::uid( $block, 'ask' );
$trust_form     = Forms::type( Questions::FORM );
$trust_fields   = $trust_form['fields'] ?? array();
$trust_consent  = Forms::consent_url();
$trust_privacy  = Forms::privacy_url();
$trust_art      = (string) file_get_contents( __DIR__ . '/art.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- файл темы.

/**
 * Поле формы: подпись, поле, ошибка (её показывает скрипт форм) и подсказка.
 *
 * @param string $name  Имя поля.
 * @param string $label Подпись.
 * @param string $input Разметка поля (с aria-describedby).
 * @param string $note  Подсказка.
 */
$trust_field = static function ( string $name, string $label, string $input, string $note ) use ( $trust_uid ): void {
	printf(
		'<div class="field"><label class="field__label" for="%1$s-%2$s">%3$s</label>%4$s<span class="field__error" id="%1$s-%2$s-error" hidden></span><span class="field__note" id="%1$s-%2$s-note">%5$s</span></div>',
		esc_attr( $trust_uid ),
		esc_attr( $name ),
		esc_html( $label ),
		$input, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- собрано ниже с экранированием.
		esc_html( $note )
	);
};
$trust_attrs = static fn( string $name ): string => sprintf(
	'id="%1$s-%2$s" name="%2$s" aria-describedby="%1$s-%2$s-error %1$s-%2$s-note" data-error="%3$s"',
	esc_attr( $trust_uid ),
	esc_attr( $name ),
	esc_attr( $trust_fields[ $name ]['error'] ?? '' )
);
?>
<div class="b-hero-questions">
	<div class="hero-crumbs">
		<?php
		if ( $is_preview ) {
			Breadcrumbs::render_preview( (int) $post_id );
		} else {
			Breadcrumbs::render();
		}
		?>
	</div>
	<section class="hero-sec" aria-labelledby="<?php echo esc_attr( $trust_title_id ); ?>">
		<div class="hero-box">
			<span class="hero-circle hero-circle--top" aria-hidden="true"></span>
			<span class="hero-circle hero-circle--bottom" aria-hidden="true"></span>
			<div class="hero-in b-hero-questions__in">
				<?php if ( '' !== $trust_badge ) : ?>
					<span class="hero-badge b-hero-questions__badge">
					<?php
					Icons::render(
						'chat',
						array(
							'size'         => 20,
							'stroke_width' => 2.2,
						)
					);
					?>
																		<?php echo esc_html( $trust_badge ); ?></span>
				<?php endif; ?>
				<h1 class="hero-title b-hero-questions__title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( '' !== $trust_h1 ? $trust_h1 : __( 'Заголовок страницы', 'trust' ) ); ?></h1>
				<?php if ( '' !== $trust_lead ) : ?>
					<p class="b-hero-questions__lead"><?php echo esc_html( $trust_lead ); ?></p>
				<?php endif; ?>

				<div class="b-hero-questions__col">
					<div class="b-hero-questions__card">
						<form class="form b-hero-questions__form" id="form-<?php echo esc_attr( Questions::FORM ); ?>" method="post" action="<?php echo esc_url( Forms::fallback_url() ); ?>" data-trust-form="<?php echo esc_attr( Questions::FORM ); ?>" data-success="<?php echo esc_attr( $trust_uid ); ?>-sent" aria-label="<?php esc_attr_e( 'Вопрос SEO-специалисту', 'trust' ); ?>">
							<?php Forms::hidden_fields( Questions::FORM ); ?>
							<div class="b-hero-questions__row">
								<?php
								$trust_field( 'name', __( 'Имя', 'trust' ), '<input class="field__input" type="text" autocomplete="given-name" required maxlength="40" placeholder="' . esc_attr__( 'Как к вам обращаться', 'trust' ) . '" ' . $trust_attrs( 'name' ) . '>', __( 'Покажем рядом с вопросом', 'trust' ) );
								$trust_field( 'email', __( 'Email', 'trust' ), '<input class="field__input" type="email" inputmode="email" autocomplete="email" required maxlength="100" placeholder="name@example.ru" ' . $trust_attrs( 'email' ) . '>', __( 'Не публикуем — пришлём ссылку на ответ', 'trust' ) );
								?>
							</div>
							<div class="field">
								<div class="b-hero-questions__label-row">
									<label class="field__label" for="<?php echo esc_attr( $trust_uid ); ?>-question"><?php esc_html_e( 'Ваш вопрос', 'trust' ); ?></label>
									<span class="b-hero-questions__count" aria-hidden="true"><span data-trust-count>0</span> / 2000</span>
								</div>
								<textarea class="field__input b-hero-questions__textarea" rows="6" required minlength="10" maxlength="2000" placeholder="<?php esc_attr_e( 'Например: после переезда на новый домен трафик из Яндекса упал вдвое. С чего начать поиск причины?', 'trust' ); ?>" <?php echo $trust_attrs( 'question' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в $trust_attrs. ?>></textarea>
								<span class="field__error" id="<?php echo esc_attr( $trust_uid ); ?>-question-error" hidden></span>
								<span class="field__note" id="<?php echo esc_attr( $trust_uid ); ?>-question-note"><?php esc_html_e( 'Вопрос опубликуем на сайте вместе с ответом', 'trust' ); ?></span>
							</div>
							<?php Forms::captcha( Questions::FORM ); ?>
							<div class="field">
								<label class="check">
									<input class="check__input" type="checkbox" name="consent" value="1" required aria-describedby="<?php echo esc_attr( $trust_uid ); ?>-consent-error" data-error="<?php echo esc_attr( $trust_form['consents']['consent']['error'] ?? '' ); ?>">
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
							<button type="submit" class="form__submit b-hero-questions__submit">
								<?php echo esc_html( '' !== $trust_submit ? $trust_submit : __( 'Получить ответ', 'trust' ) ); ?>
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

						<div class="b-hero-questions__sent" id="<?php echo esc_attr( $trust_uid ); ?>-sent" role="status" hidden>
							<span class="b-hero-questions__sent-icon" aria-hidden="true">
							<?php
							Icons::render(
								'check',
								array(
									'size'         => 30,
									'stroke_width' => 2.4,
								)
							);
							?>
							</span>
							<h2 class="b-hero-questions__sent-title"><?php echo esc_html( '' !== $trust_sent ? $trust_sent : __( 'Вопрос отправлен', 'trust' ) ); ?></h2>
							<p class="b-hero-questions__sent-text">
								<?php
								$trust_parts = explode( '{email}', esc_html( $trust_sent_txt ), 2 );
								echo $trust_parts[0]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано выше.
								if ( isset( $trust_parts[1] ) ) {
									echo '<strong data-trust-sent-email></strong>' . $trust_parts[1]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано выше.
								}
								?>
							</p>
							<button type="button" class="b-hero-questions__again" data-form-again><?php esc_html_e( 'Задать ещё вопрос', 'trust' ); ?></button>
						</div>
					</div>
				</div>

				<?php if ( $trust_points || ( is_array( $trust_link ) && ! empty( $trust_link['url'] ) ) ) : ?>
					<div class="b-hero-questions__extra">
						<?php if ( $trust_points ) : ?>
							<ul class="b-hero-questions__points" aria-label="<?php esc_attr_e( 'Как это работает', 'trust' ); ?>">
								<?php foreach ( $trust_points as $trust_point ) : ?>
									<li>
										<span class="b-hero-questions__point-icon" aria-hidden="true">
										<?php
										Icons::render(
											in_array( $trust_point['icon'] ?? '', array( 'check', 'list', 'user', 'mail', 'chat' ), true ) ? $trust_point['icon'] : 'check',
											array(
												'size' => 16,
												'stroke_width' => 'check' === ( $trust_point['icon'] ?? 'check' ) ? 2.4 : 2.2,
											)
										);
										?>
										</span>
										<span><?php echo esc_html( $trust_point['text'] ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<?php if ( is_array( $trust_link ) && ! empty( $trust_link['url'] ) && ! empty( $trust_link['title'] ) ) : ?>
							<a class="b-hero-questions__link" href="<?php echo esc_url( $trust_link['url'] ); ?>"<?php echo ! empty( $trust_link['target'] ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $trust_link['title'] ); ?>
							<?php
							Icons::render(
								'arrow-down-long',
								array(
									'size'         => 16,
									'stroke_width' => 2.2,
								)
							);
							?>
							</a>
						<?php endif; ?>
						<?php echo $trust_art; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- файл темы, декоративная картинка. ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
</div>
