<?php
/**
 * Тексты блоков «Обсудим ваш проект», «Задать вопрос», «Оповещение» и плашки «Не нашли ответ?»
 * (рубрика блога) по умолчанию
 * (ТЗ, п. 8.3: пустые поля блока — из «Настроек сайта → Блоки по умолчанию»).
 *
 * Порядок: поле блока → поле настроек → текст темы (из макетов). В настройках текст темы
 * показан серой подсказкой в пустом поле: пустое поле всегда значит «текст темы».
 * {brand} в тексте заменяется названием бренда из настроек.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Тексты общих блоков.
 */
final class Block_Texts {

	/**
	 * Тексты темы (из макетов o-nas/blok-cta.html, seo-vopros/blok-vopros*.html, vopros-seo.html).
	 *
	 * @return array<string, string> Имя поля настроек => текст.
	 */
	public static function defaults(): array {
		return array(
			'cta_title'          => __( 'Обсудим ваш проект', 'trust' ),
			'cta_text'           => __( 'Расскажите о задаче — подберём решение, назовём сроки и стоимость. Состав работ фиксируем в договоре.', 'trust' ),
			'cta_button'         => __( 'Оставить заявку', 'trust' ),

			'ask_title'          => __( 'Хотите бесплатно задать вопрос SEO-специалисту?', 'trust' ),
			'ask_text'           => __( 'Опишите задачу — специалист ответит на сайте и расскажет, как поступил бы на вашем месте.', 'trust' ),
			'ask_points'         => __( "Бесплатно\nОтвет на сайте\nОтвечает практикующий специалист", 'trust' ),
			'ask_button'         => __( 'Задать вопрос бесплатно', 'trust' ),
			'ask_note'           => __( 'Ответ — личное мнение специалиста, а не аудит сайта.', 'trust' ),

			'notice_title'       => __( 'Ответ — личное мнение автора, а не аудит сайта', 'trust' ),
			'notice_text'        => __( 'Ответ отражает личное мнение автора — SEO-специалиста {brand}. Его задача не провести аудит вашего сайта, а рассказать, как автор поступил бы сам, решая вашу задачу.', 'trust' ),
			'notice_link_prefix' => __( 'Нужна полная проверка сайта?', 'trust' ),
			'notice_link_label'  => __( 'Закажите технический аудит', 'trust' ),

			'teaser_title'       => __( 'Не нашли ответ?', 'trust' ),
			'teaser_text'        => __( 'Задайте вопрос SEO-специалисту — ответим бесплатно, ответ опубликуем на сайте.', 'trust' ),
			'teaser_button'      => __( 'Задать вопрос', 'trust' ),
		);
	}

	/**
	 * Текст из настроек, иначе текст темы.
	 *
	 * @param string $key Имя поля настроек (ключ defaults()).
	 */
	public static function get( string $key ): string {
		$defaults = self::defaults();
		$value    = Settings::get( $key );
		$text     = is_string( $value ) && '' !== trim( $value ) ? trim( $value ) : ( $defaults[ $key ] ?? '' );
		return self::brand( $text );
	}

	/**
	 * Текст поля блока, иначе — настроек, иначе — темы.
	 *
	 * @param string $field Поле блока.
	 * @param string $key   Имя поля настроек.
	 */
	public static function field( string $field, string $key ): string {
		$value = get_field( $field );
		return is_string( $value ) && '' !== trim( $value ) ? self::brand( trim( $value ) ) : self::get( $key );
	}

	/**
	 * Строки многострочного текста (пункты «Бесплатно / Ответ на сайте / …»).
	 *
	 * @param string $text Текст.
	 * @return string[]
	 */
	public static function lines( string $text ): array {
		return array_values( array_filter( array_map( 'trim', (array) preg_split( '/\R/', $text ) ), 'strlen' ) );
	}

	/**
	 * Адрес страницы: выбранная в настройках (поле post_object), иначе — первая страница типа.
	 *
	 * @param string $key  Имя поля настроек со страницей.
	 * @param string $type Тип страницы (Trust\Page_Types) по умолчанию.
	 */
	public static function page_url( string $key, string $type ): string {
		$page = (int) Settings::get( $key, 0 );
		if ( $page && 'publish' === get_post_status( $page ) ) {
			return (string) get_permalink( $page );
		}
		return Page_Types::url( $type );
	}

	/**
	 * {brand} → название бренда.
	 *
	 * @param string $text Текст.
	 */
	private static function brand( string $text ): string {
		return str_replace( '{brand}', Settings::brand(), $text );
	}
}
