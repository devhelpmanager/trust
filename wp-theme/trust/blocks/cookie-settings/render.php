<?php
/**
 * Блок «Настройки cookie» (макет o-nas/politika-cookie.html, раздел «Как управлять cookie»).
 *
 * Карточка с id="cookie-settings" (на неё ведёт кнопка «Настроить cookie» первого экрана):
 * необходимые — «Всегда включены», аналитические и рекламные — переключатели (role="switch");
 * «Сохранить выбор», «Принять все», «Только необходимые» сохраняют выбор через
 * window.trustConsent (assets/js/cookie.js) — тот же, что у плашки; строка состояния под
 * кнопками (view.js). Без JavaScript переключатели не работают — вместо них подсказка.
 * При печати блок не выводится.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_uid  = Blocks::uid( $block, 'cookie' );
$trust_cats = array(
	'analytics' => array( Blocks::text( 'analytics_label', __( 'Аналитические', 'trust' ) ), Blocks::text( 'analytics_text', __( 'Яндекс Метрика: статистика посещений', 'trust' ) ) ),
	'marketing' => array( Blocks::text( 'marketing_label', __( 'Рекламные', 'trust' ) ), Blocks::text( 'marketing_text', __( 'Показ рекламы тем, кто уже был на Сайте', 'trust' ) ) ),
);
$trust_save = Blocks::text( 'save', __( 'Сохранить выбор', 'trust' ) );
/* translators: %1$s: категории и их состояние, %2$s: текст кнопки «Сохранить выбор». */
$trust_current = sprintf( __( 'Сейчас: %1$s. Нажмите «%2$s», чтобы применить.', 'trust' ), '{list}', $trust_save );
/* translators: %s: категории и их состояние. */
$trust_saved = sprintf( __( 'Выбор сохранён: %s.', 'trust' ), '{list}' );
?>
<div class="b-cookie-set"<?php echo $is_preview ? '' : ' id="cookie-settings"'; ?> data-cookie-settings
	data-on="<?php esc_attr_e( 'включены', 'trust' ); ?>"
	data-off="<?php esc_attr_e( 'выключены', 'trust' ); ?>"
	data-current="<?php echo esc_attr( $trust_current ); ?>"
	data-saved="<?php echo esc_attr( $trust_saved ); ?>">
	<div class="b-cookie-set__head">
		<span class="b-cookie-set__icon" aria-hidden="true">
			<?php
			Icons::render(
				'cookie-dots',
				array(
					'size'         => 22,
					'stroke_width' => 1.9,
				)
			);
			?>
		</span>
		<h3 class="b-cookie-set__title"><?php echo esc_html( Blocks::text( 'title', __( 'Настройки cookie', 'trust' ) ) ); ?></h3>
	</div>

	<div class="b-cookie-set__row">
		<div class="b-cookie-set__info">
			<span class="b-cookie-set__name"><?php echo esc_html( Blocks::text( 'necessary_label', __( 'Необходимые', 'trust' ) ) ); ?></span>
			<span class="b-cookie-set__desc"><?php echo esc_html( Blocks::text( 'necessary_text', __( 'Нужны для работы Сайта, отключить нельзя', 'trust' ) ) ); ?></span>
		</div>
		<span class="b-cookie-set__always"><?php echo esc_html( Blocks::text( 'always', __( 'Всегда включены', 'trust' ) ) ); ?></span>
	</div>

	<?php foreach ( $trust_cats as $trust_key => list( $trust_label, $trust_text ) ) : ?>
		<div class="b-cookie-set__row">
			<div class="b-cookie-set__info">
				<span class="b-cookie-set__name" id="<?php echo esc_attr( $trust_uid . '-' . $trust_key ); ?>"><?php echo esc_html( $trust_label ); ?></span>
				<span class="b-cookie-set__desc" id="<?php echo esc_attr( $trust_uid . '-' . $trust_key . '-d' ); ?>"><?php echo esc_html( $trust_text ); ?></span>
			</div>
			<button type="button" role="switch" class="b-cookie-set__switch" aria-checked="false" data-cookie-switch="<?php echo esc_attr( $trust_key ); ?>"
				aria-labelledby="<?php echo esc_attr( $trust_uid . '-' . $trust_key ); ?>" aria-describedby="<?php echo esc_attr( $trust_uid . '-' . $trust_key . '-d' ); ?>"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>>
				<span class="b-cookie-set__track" aria-hidden="true"><span></span></span>
			</button>
		</div>
	<?php endforeach; ?>

	<div class="b-cookie-set__buttons">
		<button type="button" class="btn b-cookie-set__btn" data-cookie-save="custom"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>><?php echo esc_html( $trust_save ); ?></button>
		<button type="button" class="btn btn--outline b-cookie-set__btn" data-cookie-save="all"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>><?php echo esc_html( Blocks::text( 'accept_all', __( 'Принять все', 'trust' ) ) ); ?></button>
		<button type="button" class="btn btn--outline b-cookie-set__btn b-cookie-set__btn--muted" data-cookie-save="necessary"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>><?php echo esc_html( Blocks::text( 'only_necessary', __( 'Только необходимые', 'trust' ) ) ); ?></button>
	</div>
	<p class="b-cookie-set__status" role="status" data-cookie-status></p>
	<p class="b-cookie-set__nojs"><?php esc_html_e( 'Чтобы изменить выбор, включите в браузере JavaScript или удалите cookie сайта в настройках браузера.', 'trust' ); ?></p>
</div>
