<?php
/**
 * Окно «Связаться с нами» (было «Заказать обратный звонок»; правки заказчика): тексты из «Настроек
 * сайта → Блоки по умолчанию»; заявки — подпункт «Заявки → Обратная связь».
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Тексты окна звонка и кнопок, которые его открывают.
 */
final class Callback {

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'trust_lead_sections', array( self::class, 'section' ) );
	}

	/**
	 * Подпункт «Заявки → Обратная связь» (правка заказчика; Trust\Forms\Lead_Sections): заявки из
	 * окна «Связаться с нами» и блока «Кнопка связи» — первым после «Все заявки».
	 *
	 * @param array $sections Подпункты.
	 */
	public static function section( array $sections ): array {
		$sections['callback'] = array(
			'menu'      => __( 'Обратная связь', 'trust' ),
			'not_found' => __( 'Заявок пока нет: они появляются, когда посетитель отправляет форму «Связаться с нами» (кнопка в шапке и подвале) или блок «Кнопка связи».', 'trust' ),
			'position'  => 1,
			'columns'   => array(),
		);
		return $sections;
	}

	/**
	 * Значения по умолчанию (правки заказчика: «Связаться с нами», любой способ связи).
	 * Те же значения подставлены в поля настроек.
	 *
	 * @return array<string, string> Ключ => текст.
	 */
	public static function defaults(): array {
		return array(
			'button'            => __( 'Связаться с нами', 'trust' ),
			'title'             => __( 'Запросить обратную связь', 'trust' ),
			'text'              => __( 'Укажите своё имя и способ связи, мы свяжемся с вами в кратчайшие сроки.', 'trust' ),
			'name_label'        => __( 'Имя', 'trust' ),
			'name_placeholder'  => __( 'Как к вам обращаться', 'trust' ),
			'phone_label'       => __( 'Способ связи', 'trust' ),
			'phone_placeholder' => __( 'телефон, имейл или никнейм', 'trust' ),
			'submit'            => __( 'Отправить заявку', 'trust' ),
			'success_title'     => __( 'Заявка отправлена', 'trust' ),
			'success_text'      => __( 'Мы свяжемся с вами в ближайшее время.', 'trust' ),
		);
	}

	/**
	 * Текст окна: из настроек, иначе значение по умолчанию.
	 *
	 * @param string $key Ключ из defaults().
	 */
	public static function text( string $key ): string {
		$defaults = self::defaults();
		$fallback = $defaults[ $key ] ?? '';
		return (string) Settings::get( 'callback_' . $key, $fallback );
	}
}
