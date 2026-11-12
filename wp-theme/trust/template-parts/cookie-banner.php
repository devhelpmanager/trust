<?php
/**
 * Плашка согласия на cookie (макет seo-bloki/17-cookie.html, ТЗ раздел 14).
 *
 * Плашка скрыта: её показывает assets/js/cookie.js при первом визите (или после смены версии
 * согласия). После выбора видимой подсказки нет (правка заказчика), выбор озвучивается
 * скринридеру строкой состояния. Без JavaScript плашки нет: без скриптов
 * счётчики не работают и спрашивать не о чем. Тексты и ссылка на политику — «Настройки сайта →
 * Cookie и счётчики».
 *
 * @package Trust
 */

use Trust\Cookie;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;
?>
<div class="cookie-banner" role="dialog" aria-labelledby="cookie-banner-title" aria-describedby="cookie-banner-text" data-trust-cookie-banner hidden>
	<div class="cookie-banner__body">
		<span class="cookie-banner__icon" aria-hidden="true">
			<?php
			Icons::render(
				'cookie',
				array(
					'size'         => 28,
					'stroke_width' => 1.7,
				)
			);
			?>
		</span>
		<div class="cookie-banner__text">
			<h2 class="cookie-banner__title" id="cookie-banner-title"><?php echo esc_html( Cookie::text( 'title' ) ); ?></h2>
			<p id="cookie-banner-text"><?php echo wp_kses( Cookie::text_html(), array( 'a' => array( 'href' => true ) ) ); ?></p>
		</div>
	</div>
	<div class="cookie-banner__actions">
		<button type="button" class="cookie-banner__btn cookie-banner__btn--primary" data-trust-consent-choice="all"><?php esc_html_e( 'Принять', 'trust' ); ?></button>
		<button type="button" class="cookie-banner__btn" data-trust-consent-choice="necessary"><?php esc_html_e( 'Принять только обязательные', 'trust' ); ?></button>
		<button type="button" class="cookie-banner__btn cookie-banner__btn--muted" data-trust-consent-choice="none"><?php esc_html_e( 'Отклонить всё', 'trust' ); ?></button>
	</div>
</div>
<?php // Сообщение о сохранённом выборе — только для скринридеров (видимой подсказки нет по просьбе заказчика). ?>
<div class="screen-reader-text" role="status" data-trust-cookie-saved></div>
