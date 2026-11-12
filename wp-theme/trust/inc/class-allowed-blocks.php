<?php
/**
 * Разрешённые блоки по типам записей (ТЗ, раздел 16).
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Какие блоки доступны в редакторе:
 * - страницы — все блоки темы (кроме блоков кейса и ответа) и стандартные: абзац, заголовок,
 *   список, цитата, картинка, таблица, разделитель, кнопки;
 * - статьи — стандартные текстовые блоки, «Видео», «Картинка», «Оповещение», «Статьи из блога»;
 * - ответы на вопросы — то же и «Как бы поступил я»;
 * - кейсы — блоки кейса и стандартные текстовые.
 * Везде — синхронизированные паттерны (блок, одинаковый на многих страницах, правится в одном месте).
 * Блоки только своего типа страницы скрывает скрипт окна типов (Trust\Page_Types::EXCLUSIVE).
 */
final class Allowed_Blocks {

	/**
	 * Стандартные текстовые блоки статей, ответов и кейсов.
	 */
	private const TEXT = array( 'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/quote', 'core/image', 'core/table', 'core/separator' );

	/**
	 * Паттерны: синхронизированные (core/block) и обычные.
	 */
	private const PATTERNS = array( 'core/block', 'core/pattern' );

	/**
	 * Блоки темы для статей.
	 */
	private const POST = array( 'trust/video', 'trust/image', 'trust/notice', 'trust/posts' );

	/**
	 * Блоки кейса (ТЗ, раздел 8.10) — только у кейсов.
	 */
	private const CASE = array( 'trust/case-task', 'trust/case-obstacles', 'trust/case-work', 'trust/case-results', 'trust/case-quote', 'trust/case-team' );

	/**
	 * Блоки ответа — только у вопросов.
	 */
	private const ANSWER = array( 'trust/how-i-would' );

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'allowed_block_types_all', array( self::class, 'filter' ), 10, 2 );
	}

	/**
	 * Блоки для типа записи; null — без ограничений темы.
	 *
	 * @param string $post_type Тип записи.
	 * @return string[]|null
	 */
	public static function for_post_type( string $post_type ): ?array {
		switch ( $post_type ) {
			case 'page':
				$theme = array_values(
					array_filter(
						array_keys( \WP_Block_Type_Registry::get_instance()->get_all_registered() ),
						static fn( string $name ): bool => str_starts_with( $name, 'trust/' ) && ! in_array( $name, array_merge( self::CASE, self::ANSWER ), true )
					)
				);
				// Блоки только своего типа страницы — в списке всегда; на чужих типах их скрывает окно типов.
				$theme = array_values( array_unique( array_merge( $theme, array_keys( Page_Types::exclusive() ) ) ) );
				return array_merge( $theme, self::TEXT, array( 'core/buttons', 'core/button' ), self::PATTERNS );
			case 'post':
				return array_merge( self::TEXT, self::POST, self::PATTERNS );
			case 'question':
				return array_merge( self::TEXT, self::POST, self::ANSWER, self::PATTERNS );
			case 'case':
				return array_merge( self::TEXT, self::CASE, self::PATTERNS );
		}
		return null;
	}

	/**
	 * Фильтр allowed_block_types_all.
	 *
	 * @param bool|string[]            $allowed Разрешённые блоки.
	 * @param \WP_Block_Editor_Context $context Контекст редактора.
	 * @return bool|string[]
	 */
	public static function filter( $allowed, $context ) {
		if ( ! $context instanceof \WP_Block_Editor_Context || ! $context->post ) {
			return $allowed;
		}
		$list = self::for_post_type( $context->post->post_type );
		return null === $list ? $allowed : $list;
	}
}
