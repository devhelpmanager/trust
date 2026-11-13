<?php
/**
 * Блок «Бриф — полный» (макет holst-blokov/Brief, seo-bloki/11a-brif-polnyy.html).
 *
 * Лавандовая секция, шапка по центру, белая карточка-форма «Бриф» (Trust\Forms\Block_Forms::BRIEF):
 * переключатель «Продвижение / Бесплатный аудит» (радиокнопки), адрес и тематика сайта, задача,
 * варианты «что хотите получить» — флажки-«таблетки» (можно несколько), имя и «Как с вами
 * связаться?» (решение заказчика: в макете полей для связи нет), галочка согласия вместо фразы
 * «Нажимая кнопку…». После отправки — экран «Бриф отправлен» (assets/js/forms.js).
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
$trust_uid      = Blocks::uid( $block, 'brief' );
$trust_type     = Block_Forms::BRIEF;
$trust_form     = Forms::type( $trust_type );
$trust_wants    = array_values( array_filter( array_map( static fn( $row ) => trim( (string) ( is_array( $row ) ? ( $row['label'] ?? '' ) : '' ) ), (array) get_field( 'wants' ) ) ) );
$trust_wants    = $trust_wants ? $trust_wants : Block_Forms::WANTS;

/**
 * Поле с подписью: input или textarea.
 *
 * @param string $name     Имя поля.
 * @param string $label    Подпись.
 * @param string $hint     Подсказка (placeholder).
 * @param string $attrs    Атрибуты (константы разметки).
 * @param bool   $textarea Многострочное поле.
 */
$trust_field = static function ( string $name, string $label, string $hint, string $attrs, bool $textarea = false ) use ( $trust_uid, $trust_form ): void {
	$id       = $trust_uid . '-' . $name;
	$field    = $trust_form['fields'][ $name ] ?? array();
	$required = ! empty( $field['required'] ) ? ' required' : '';
	printf( '<div class="field"><label class="field__label" for="%s">%s</label>', esc_attr( $id ), esc_html( $label ) );
	$common = sprintf(
		'class="field__input%s" id="%s" name="%s"%s maxlength="%d" placeholder="%s" aria-describedby="%s-error" data-error="%s" %s',
		$textarea ? ' b-brief__textarea' : '',
		esc_attr( $id ),
		esc_attr( $name ),
		$required,
		(int) ( $field['max'] ?? 200 ),
		esc_attr( $hint ),
		esc_attr( $id ),
		esc_attr( $field['error'] ?? '' ),
		$attrs
	);
	echo $textarea ? '<textarea rows="4" ' . $common . '></textarea>' : '<input ' . $common . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- атрибуты экранированы выше.
	printf( '<span class="field__error" id="%s-error" hidden></span></div>', esc_attr( $id ) );
};
?>
<section class="b-brief trust-bg-lavender"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-brief__inner">
		<?php if ( $trust_eyebrow || $trust_title || $trust_lead ) : ?>
			<div class="b-brief__head">
				<?php if ( $trust_eyebrow ) : ?>
					<span class="eyebrow"><?php echo esc_html( $trust_eyebrow ); ?></span>
				<?php endif; ?>
				<?php if ( $trust_title ) : ?>
					<h2 class="section-title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
				<?php endif; ?>
				<?php if ( '' !== trim( $trust_lead ) ) : ?>
					<p class="b-brief__lead"><?php echo esc_html( $trust_lead ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="b-brief__card">
			<form class="form b-brief__form" id="<?php echo esc_attr( $trust_uid ); ?>-form" method="post" action="<?php echo esc_url( Forms::fallback_url() ); ?>" data-trust-form="<?php echo esc_attr( $trust_type ); ?>" data-success="<?php echo esc_attr( $trust_uid ); ?>-sent"<?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?> novalidate>
				<?php Forms::hidden_fields( $trust_type ); ?>
				<input type="hidden" name="_trust_anchor" value="<?php echo esc_attr( $trust_uid ); ?>-form">

				<fieldset class="b-brief__modes">
					<legend class="screen-reader-text"><?php esc_html_e( 'Тип заявки', 'trust' ); ?></legend>
					<?php
					foreach ( array(
						'promo' => Blocks::text( 'mode_promo', __( 'Продвижение', 'trust' ) ),
						'audit' => Blocks::text( 'mode_audit', __( 'Бесплатный аудит', 'trust' ) ),
					) as $trust_value => $trust_label ) :
						?>
						<label class="b-brief__mode">
							<input class="b-brief__mode-input" type="radio" name="mode" value="<?php echo esc_attr( $trust_value ); ?>"<?php checked( 'promo', $trust_value ); ?>>
							<span><?php echo esc_html( $trust_label ); ?></span>
						</label>
					<?php endforeach; ?>
				</fieldset>

				<div class="b-brief__row">
					<?php
					$trust_field( 'url', Blocks::text( 'url_label', __( 'Адрес сайта', 'trust' ) ), Blocks::text( 'url_placeholder', 'https://example.ru' ), 'type="url" inputmode="url" autocomplete="url"' );
					$trust_field( 'topic', Blocks::text( 'topic_label', __( 'Тематика сайта', 'trust' ) ), Blocks::text( 'topic_placeholder', __( 'Например, ремонт квартир', 'trust' ) ), 'type="text"' );
					?>
				</div>
				<?php $trust_field( 'problem', Blocks::text( 'problem_label', __( 'Опишите проблему или задачу', 'trust' ) ), Blocks::text( 'problem_placeholder', __( 'Например: после переезда на новую CMS упал трафик из Яндекса', 'trust' ) ), '', true ); ?>

				<fieldset class="b-brief__wants">
					<legend class="b-brief__legend">
						<?php echo esc_html( Blocks::text( 'wants_label', __( 'Что хотите получить или с чем столкнулись', 'trust' ) ) ); ?>
						<span class="b-brief__legend-note">· <?php echo esc_html( Blocks::text( 'wants_note', __( 'можно выбрать несколько', 'trust' ) ) ); ?></span>
					</legend>
					<div class="b-brief__chips">
						<?php foreach ( $trust_wants as $trust_want ) : ?>
							<label class="b-brief__chip">
								<input class="b-brief__chip-input" type="checkbox" name="wants[]" value="<?php echo esc_attr( $trust_want ); ?>">
								<span class="b-brief__chip-check" aria-hidden="true">
									<?php
									Icons::render(
										'check',
										array(
											'size'         => 16,
											'stroke_width' => 2.2,
										)
									);
									?>
								</span>
								<span><?php echo esc_html( $trust_want ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>

				<div class="b-brief__row">
					<?php
					$trust_field( 'name', Blocks::text( 'name_label', __( 'Имя', 'trust' ) ), Blocks::text( 'name_placeholder', __( 'Как к вам обращаться', 'trust' ) ), 'type="text" autocomplete="name"' );
					$trust_field( 'contact', Blocks::text( 'contact_label', __( 'Как с вами связаться?', 'trust' ) ), Blocks::text( 'contact_placeholder', __( 'Телефон или email', 'trust' ) ), 'type="text" data-type="contact" autocomplete="email"' );
					?>
				</div>

				<div class="b-brief__footer">
					<?php Forms::consent_check( $trust_uid, $trust_type ); ?>
					<button type="submit" class="form__submit b-brief__submit">
						<?php echo esc_html( Blocks::text( 'submit', __( 'Получить', 'trust' ) ) ); ?>
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
				</div>
				<?php Forms::status( $trust_type ); ?>
			</form>
			<?php Forms::sent_screen( $trust_uid . '-sent', Blocks::text( 'success_title', __( 'Бриф отправлен', 'trust' ) ), Blocks::text( 'success_text', __( 'Изучим сайт и задачу и свяжемся с вами в течение рабочего дня.', 'trust' ) ), Blocks::text( 'again', __( 'Заполнить бриф ещё раз', 'trust' ) ) ); ?>
		</div>
	</div>
</section>
