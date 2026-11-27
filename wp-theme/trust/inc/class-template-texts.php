<?php
/**
 * Тексты шаблонов (ТЗ, раздел 5.4): заголовки и подписи, которые выводят шаблоны, а не блоки
 * («Новая статья», «Читать статью», «Все статьи» и т. п.). Редактируются в «Настройках сайта →
 * Тексты шаблонов»; пустое поле — текст по умолчанию из макета.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Тексты по умолчанию и чтение из настроек.
 */
final class Template_Texts {

	/**
	 * Тексты по умолчанию: ключ => текст. Поле настроек — tpl_{ключ}.
	 */
	public static function defaults(): array {
		return array(
			// Блог (шаг 5.1, макет seo-blog/blog.html).
			'blog_new'  => __( 'Новая статья', 'trust' ),
			'blog_read' => __( 'Читать статью', 'trust' ),
			'blog_all'  => __( 'Все статьи', 'trust' ),
		);
	}

	/**
	 * Текст из настроек или по умолчанию.
	 *
	 * @param string $key Ключ (см. defaults()).
	 */
	public static function get( string $key ): string {
		$value = Settings::get( 'tpl_' . $key );
		return is_string( $value ) && '' !== trim( $value ) ? trim( $value ) : ( self::defaults()[ $key ] ?? '' );
	}
}
