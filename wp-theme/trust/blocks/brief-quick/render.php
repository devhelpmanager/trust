<?php
/**
 * Блок «Бриф — короткий» (макет holst-blokov/BriefQuick, seo-bloki/11b-brif-korotkiy.html).
 *
 * Лавандовая секция, шапка слева, белая карточка формы «Бриф (короткий)»
 * (Trust\Forms\Block_Forms::BRIEF_QUICK): адрес сайта, имя и «Как с вами связаться?» (решение
 * заказчика: в макете — только адрес; способ связи — любой), под полями — карточка-флажок
 * «Расшарить доступ к статистике» (уходит в заявке; при отметке раскрывается подсказка с email
 * для доступа), галочка согласия и кнопка (правка заказчика: в макете карточка — отдельно под
 * разделителем «Также советуем»). После отправки — экран «Заявка отправлена».
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Contacts;
use Trust\Forms;
use Trust\Forms\Block_Forms;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_title    = (string) get_field( 'title' );
$trust_eyebrow  = (string) get_field( 'eyebrow' );
$trust_lead     = (string) get_field( 'lead' );
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_uid      = Blocks::uid( $block, 'quick' );
$trust_type     = Block_Forms::BRIEF_QUICK;
$trust_form     = Forms::type( $trust_type );
$trust_email    = sanitize_email( (string) get_field( 'share_email' ) );
if ( '' === $trust_email ) {
	$trust_main  = Contacts::main_email();
	$trust_email = $trust_main ? $trust_main['email'] : '';
}

/**
 * Поле полосы формы: input без видимой подписи (aria-label) с местом для ошибки.
 *
 * @param string $name  Имя поля.
 * @param string $attrs Атрибуты (константы разметки).
 * @param string $label Подпись для скринридеров.
 * @param string $hint  Подсказка (placeholder).
 */
$trust_input = static function ( string $name, string $attrs, string $label, string $hint ) use ( $trust_uid, $trust_form ): void {
	$id = $trust_uid . '-' . $name;
	printf(
		'<div class="field b-quick__field b-quick__field--%2$s"><input class="field__input" id="%1$s" name="%2$s" %3$s required maxlength="%4$d" aria-label="%5$s" placeholder="%6$s" aria-describedby="%1$s-error" data-error="%7$s"><span class="field__error" id="%1$s-error" hidden></span></div>',
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
<section class="b-quick trust-bg-lavender"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-quick__inner">
		<?php if ( $trust_eyebrow || $trust_title || $trust_lead ) : ?>
			<div class="b-quick__head">
				<?php if ( $trust_eyebrow ) : ?>
					<span class="eyebrow"><?php echo esc_html( $trust_eyebrow ); ?></span>
				<?php endif; ?>
				<?php if ( $trust_title ) : ?>
					<h2 class="section-title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
				<?php endif; ?>
				<?php if ( '' !== trim( $trust_lead ) ) : ?>
					<p class="b-quick__lead"><?php echo esc_html( $trust_lead ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<form class="form b-quick__form" id="<?php echo esc_attr( $trust_uid ); ?>-form" method="post" action="<?php echo esc_url( Forms::fallback_url() ); ?>" data-trust-form="<?php echo esc_attr( $trust_type ); ?>" data-success="<?php echo esc_attr( $trust_uid ); ?>-sent"<?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?> novalidate>
			<?php Forms::hidden_fields( $trust_type ); ?>
			<input type="hidden" name="_trust_anchor" value="<?php echo esc_attr( $trust_uid ); ?>-form">

			<div class="b-quick__card">
				<div class="b-quick__fields">
					<?php
					$trust_input( 'url', 'type="url" inputmode="url" autocomplete="url"', __( 'Адрес сайта', 'trust' ), Blocks::text( 'url_placeholder', 'https://example.ru' ) );
					$trust_input( 'name', 'type="text" autocomplete="name"', __( 'Имя', 'trust' ), Blocks::text( 'name_placeholder', __( 'Ваше имя', 'trust' ) ) );
					$trust_input( 'contact', 'type="text" minlength="3"', __( 'Как с вами связаться? Телефон, email, Telegram или другой способ', 'trust' ), Blocks::text( 'contact_placeholder', __( 'Как с вами связаться?', 'trust' ) ) );
					?>
				</div>

				<div class="b-quick__share">
					<label class="b-quick__share-label">
						<input class="check__input b-quick__share-input" type="checkbox" name="share" value="1" aria-describedby="<?php echo esc_attr( $trust_uid ); ?>-share-text">
						<span class="b-quick__share-body">
							<span class="b-quick__share-title"><?php echo esc_html( Blocks::text( 'share_title', __( 'Расшарить доступ к статистике', 'trust' ) ) ); ?></span>
							<span class="b-quick__share-text" id="<?php echo esc_attr( $trust_uid ); ?>-share-text"><?php echo esc_html( Blocks::text( 'share_text', __( 'Яндекс Метрика, Вебмастер или Google Search Console — с данными аудит будет точнее', 'trust' ) ) ); ?></span>
						</span>
						<span class="b-quick__share-icon" aria-hidden="true">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false"><circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="M8.2 10.8l7.6-4.4M8.2 13.2l7.6 4.4"/></svg>
						</span>
					</label>
					<?php if ( '' !== $trust_email ) : ?>
						<div class="b-quick__share-note">
							<?php Icons::render( 'mail', array( 'size' => 18 ) ); ?>
							<span><?php echo esc_html( Blocks::text( 'share_note', __( 'Предоставьте нам доступ к статистике на email', 'trust' ) ) ); ?> <a href="mailto:<?php echo esc_attr( $trust_email ); ?>"><?php echo esc_html( $trust_email ); ?></a></span>
						</div>
					<?php endif; ?>
				</div>

				<?php Forms::consent_check( $trust_uid, $trust_type ); ?>

				<button type="submit" class="form__submit b-quick__submit">
					<?php echo esc_html( Blocks::text( 'submit', __( 'Получить бесплатный аудит', 'trust' ) ) ); ?>
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
		<?php Forms::sent_screen( $trust_uid . '-sent', Blocks::text( 'success_title', __( 'Заявка на аудит отправлена', 'trust' ) ), Blocks::text( 'success_text', __( 'Проверим сайт и пришлём список ключевых ошибок с рекомендациями.', 'trust' ) ), Blocks::text( 'again', __( 'Отправить ещё одну заявку', 'trust' ) ) ); ?>
	</div>
</section>
