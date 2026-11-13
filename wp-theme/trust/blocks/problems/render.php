<?php
/**
 * Блок «С какими проблемами вы можете столкнуться» (макет holst-blokov/Problems,
 * seo-bloki/07-problemy.html).
 *
 * Лавандовая секция: шапка по центру; слева вводный текст и форма «Заявка на аудит»
 * (Trust\Forms\Block_Forms::AUDIT: адрес сайта, имя, телефон или email, капча — если включена,
 * галочка согласия вместо фразы «Нажимая кнопку…» — решение заказчика), справа список проблем.
 * После отправки — экран «Заявка отправлена» (assets/js/forms.js); без JavaScript — обычная
 * отправка с возвратом к форме.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Forms;
use Trust\Forms\Block_Forms;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_title    = (string) get_field( 'title' );
$trust_eyebrow  = (string) get_field( 'eyebrow' );
$trust_lead     = (string) get_field( 'lead' );
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_uid      = Blocks::uid( $block, 'audit' );
$trust_problems = array_filter( (array) get_field( 'problems' ), static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['title'] ?? '' ) ) );
$trust_type     = Block_Forms::AUDIT;
$trust_form     = Forms::type( $trust_type );

/**
 * Поле формы: input с подписью для скринридеров и местом для ошибки.
 *
 * @param string $name  Имя поля.
 * @param string $attrs Атрибуты input (экранированные).
 * @param string $label Подпись (aria-label).
 * @param string $hint  Подсказка (placeholder).
 */
$trust_input = static function ( string $name, string $attrs, string $label, string $hint ) use ( $trust_uid, $trust_form ): void {
	$id = $trust_uid . '-' . $name;
	printf(
		'<div class="field"><input class="field__input" id="%1$s" name="%2$s" %3$s required maxlength="%4$d" aria-label="%5$s" placeholder="%6$s" aria-describedby="%1$s-error" data-error="%7$s"><span class="field__error" id="%1$s-error" hidden></span></div>',
		esc_attr( $id ),
		esc_attr( $name ),
		$attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- константы разметки ниже.
		(int) ( $trust_form['fields'][ $name ]['max'] ?? 100 ),
		esc_attr( $label ),
		esc_attr( $hint ),
		esc_attr( $trust_form['fields'][ $name ]['error'] ?? '' )
	);
};
?>
<section class="b-problems trust-bg-lavender"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-problems__inner">
		<?php if ( $trust_eyebrow || $trust_title ) : ?>
			<div class="b-problems__head">
				<?php if ( $trust_eyebrow ) : ?>
					<span class="eyebrow"><?php echo esc_html( $trust_eyebrow ); ?></span>
				<?php endif; ?>
				<?php if ( $trust_title ) : ?>
					<h2 class="section-title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="b-problems__body">
			<?php if ( '' !== trim( $trust_lead ) ) : ?>
				<p class="b-problems__lead"><?php echo esc_html( $trust_lead ); ?></p>
			<?php endif; ?>

			<?php if ( $trust_problems ) : ?>
				<ul class="b-problems__list">
					<?php foreach ( $trust_problems as $trust_row ) : ?>
						<li class="b-problems__item">
							<span class="b-problems__icon" aria-hidden="true">
								<?php
								Icons::render(
									(string) ( $trust_row['icon'] ?? 'traffic-flat' ),
									array(
										'size'         => 22,
										'stroke_width' => 1.8,
									)
								);
								?>
							</span>
							<div class="b-problems__text">
								<h3 class="b-problems__title"><?php echo esc_html( $trust_row['title'] ); ?></h3>
								<?php if ( '' !== trim( (string) ( $trust_row['text'] ?? '' ) ) ) : ?>
									<p class="b-problems__desc"><?php echo esc_html( $trust_row['text'] ); ?></p>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<div class="b-problems__card">
				<form class="form b-problems__form" id="<?php echo esc_attr( $trust_uid ); ?>-form" method="post" action="<?php echo esc_url( Forms::fallback_url() ); ?>" data-trust-form="<?php echo esc_attr( $trust_type ); ?>" data-success="<?php echo esc_attr( $trust_uid ); ?>-sent" aria-labelledby="<?php echo esc_attr( $trust_uid ); ?>-label" novalidate>
					<span class="b-problems__label" id="<?php echo esc_attr( $trust_uid ); ?>-label"><?php echo esc_html( Blocks::text( 'form_title', __( 'Заявка на аудит', 'trust' ) ) ); ?></span>
					<?php Forms::hidden_fields( $trust_type ); ?>
					<input type="hidden" name="_trust_anchor" value="<?php echo esc_attr( $trust_uid ); ?>-form">
					<?php
					$trust_input( 'url', 'type="url" inputmode="url" autocomplete="url"', __( 'Адрес сайта', 'trust' ), Blocks::text( 'url_placeholder', __( 'Адрес сайта', 'trust' ) ) );
					$trust_input( 'name', 'type="text" autocomplete="name"', __( 'Имя', 'trust' ), Blocks::text( 'name_placeholder', __( 'Ваше имя', 'trust' ) ) );
					$trust_input( 'contact', 'type="text" data-type="contact" autocomplete="email"', __( 'Телефон или email', 'trust' ), Blocks::text( 'contact_placeholder', __( 'Телефон или email', 'trust' ) ) );
					Forms::captcha( $trust_type );
					Forms::consent_check( $trust_uid, $trust_type );
					?>
					<button type="submit" class="form__submit b-problems__submit">
						<span><?php echo esc_html( Blocks::text( 'submit', __( 'Проверить точки роста на моём сайте', 'trust' ) ) ); ?></span>
						<?php
						Icons::render(
							'arrow-right',
							array(
								'size'         => 18,
								'stroke_width' => 2,
							)
						);
						?>
					</button>
					<?php Forms::status( $trust_type ); ?>
				</form>
				<?php Forms::sent_screen( $trust_uid . '-sent', Blocks::text( 'success_title', __( 'Заявка отправлена', 'trust' ) ), Blocks::text( 'success_text', __( 'Проверим сайт и свяжемся с вами в течение рабочего дня.', 'trust' ) ), Blocks::text( 'again', __( 'Отправить ещё одну заявку', 'trust' ) ) ); ?>
			</div>
		</div>
	</div>
</section>
