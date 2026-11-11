<?php
/**
 * Окно «Заказать обратный звонок»: тексты из «Настроек сайта → Блоки по умолчанию».
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
	 * Значения по умолчанию (из блока «Кнопка связи» и формы контактов в макетах).
	 * Те же значения подставлены в поля настроек.
	 *
	 * @return array<string, string> Ключ => текст.
	 */
	public static function defaults(): array {
		return array(
			'button'            => __( 'Заказать обратный звонок', 'trust' ),
			'title'             => __( 'Заказать обратный звонок', 'trust' ),
			'text'              => __( 'Оставьте имя и телефон — специалист свяжется с вами и поможет выбрать услугу.', 'trust' ),
			'name_label'        => __( 'Имя', 'trust' ),
			'name_placeholder'  => __( 'Как к вам обращаться', 'trust' ),
			'phone_label'       => __( 'Телефон', 'trust' ),
			'phone_placeholder' => Forms::phone_example(),
			'submit'            => __( 'Отправить заявку', 'trust' ),
			'success_title'     => __( 'Заявка отправлена', 'trust' ),
			'success_text'      => __( 'Мы перезвоним вам в ближайшее время.', 'trust' ),
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
