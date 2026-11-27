<?php
/**
 * Тексты шаблонов (ТЗ, раздел 5.4): заголовки и подписи, которые выводят шаблоны, а не блоки
 * («Новая статья», «Читать полностью», «Все статьи» и т. п.). Редактируются в «Настройках сайта →
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
			'blog_new'           => __( 'Новая статья', 'trust' ),
			'blog_read'          => __( 'Читать полностью', 'trust' ), // Правка заказчика (в макете «Читать статью»).
			'blog_all'           => __( 'Все статьи', 'trust' ),
			// Статья (шаг 5.2, макет seo-blog/statya.html).
			'post_toc'           => __( 'Содержание', 'trust' ),
			'post_tags'          => __( 'Ключевые слова', 'trust' ),
			'post_useful'        => __( 'Понравилось? Лайкни!', 'trust' ), // Правка заказчика (в макете «Статья была полезной?»).
			'post_related_label' => __( 'Блог', 'trust' ),
			'post_related'       => __( 'Читайте также', 'trust' ),
			'post_related_more'  => __( 'Все статьи', 'trust' ),
			// Комментарии статьи (шаг 5.3).
			'post_comments'      => __( 'Комментарии', 'trust' ),
			'comment_note'       => __( 'Комментарий появится после проверки модератором.', 'trust' ),
			'comment_sent'       => __( 'Спасибо! Комментарий появится после проверки модератором.', 'trust' ),
			'comment_reply_sent' => __( 'Ответ отправлен на модерацию.', 'trust' ),
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
