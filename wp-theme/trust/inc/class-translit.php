<?php
/**
 * Транслитерация кириллицы в ярлыках и именах файлов (ТЗ, раздел 10.4).
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Переводит кириллицу в латиницу в новых ярлыках записей и терминов и в именах
 * загружаемых файлов. Правила — как в адресах демо-страниц и макетов:
 * «Дизайн» → dizayn, «Карьера» → karera, «Данных» → dannyh, «Объём» → obem.
 *
 * Существующие ярлыки не меняются: WordPress хранит кириллический ярлык в виде
 * %d0%… — в нём нет кириллицы, и фильтр его пропускает. При поиске записи по адресу
 * (контекст 'query') транслитерация не применяется, поэтому старые кириллические
 * адреса продолжают открываться.
 */
final class Translit {

	/**
	 * Таблица замен: русский алфавит, украинские и белорусские буквы.
	 */
	private const MAP = array(
		'а' => 'a',
		'б' => 'b',
		'в' => 'v',
		'г' => 'g',
		'д' => 'd',
		'е' => 'e',
		'ё' => 'e',
		'ж' => 'zh',
		'з' => 'z',
		'и' => 'i',
		'й' => 'y',
		'к' => 'k',
		'л' => 'l',
		'м' => 'm',
		'н' => 'n',
		'о' => 'o',
		'п' => 'p',
		'р' => 'r',
		'с' => 's',
		'т' => 't',
		'у' => 'u',
		'ф' => 'f',
		'х' => 'h',
		'ц' => 'c',
		'ч' => 'ch',
		'ш' => 'sh',
		'щ' => 'sch',
		'ъ' => '',
		'ы' => 'y',
		'ь' => '',
		'э' => 'e',
		'ю' => 'yu',
		'я' => 'ya',
		'є' => 'ye',
		'і' => 'i',
		'ї' => 'yi',
		'ґ' => 'g',
		'ў' => 'u',
		'А' => 'A',
		'Б' => 'B',
		'В' => 'V',
		'Г' => 'G',
		'Д' => 'D',
		'Е' => 'E',
		'Ё' => 'E',
		'Ж' => 'Zh',
		'З' => 'Z',
		'И' => 'I',
		'Й' => 'Y',
		'К' => 'K',
		'Л' => 'L',
		'М' => 'M',
		'Н' => 'N',
		'О' => 'O',
		'П' => 'P',
		'Р' => 'R',
		'С' => 'S',
		'Т' => 'T',
		'У' => 'U',
		'Ф' => 'F',
		'Х' => 'H',
		'Ц' => 'C',
		'Ч' => 'Ch',
		'Ш' => 'Sh',
		'Щ' => 'Sch',
		'Ъ' => '',
		'Ы' => 'Y',
		'Ь' => '',
		'Э' => 'E',
		'Ю' => 'Yu',
		'Я' => 'Ya',
		'Є' => 'Ye',
		'І' => 'I',
		'Ї' => 'Yi',
		'Ґ' => 'G',
		'Ў' => 'U',
	);

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		// Раньше sanitize_title_with_dashes (приоритет 10): он превратил бы кириллицу в %d0%….
		add_filter( 'sanitize_title', array( self::class, 'title' ), 9, 3 );
		add_filter( 'sanitize_file_name', array( self::class, 'file_name' ) );
	}

	/**
	 * Есть ли в строке кириллица.
	 *
	 * @param string $text Строка.
	 */
	public static function has_cyrillic( string $text ): bool {
		return 1 === preg_match( '/\p{Cyrillic}/u', $text );
	}

	/**
	 * Строка латиницей.
	 *
	 * @param string $text Строка.
	 */
	public static function convert( string $text ): string {
		return strtr( $text, self::MAP );
	}

	/**
	 * Фильтр sanitize_title: ярлык из заголовка записи, названия термина и т. п.
	 *
	 * @param string $title     Заголовок (после remove_accents).
	 * @param string $raw_title Исходный заголовок.
	 * @param string $context   'save' — создаётся ярлык, 'query' — поиск по адресу.
	 */
	public static function title( $title, $raw_title = '', $context = 'save' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed, Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed -- сигнатура фильтра.
		if ( 'save' !== $context || ! is_string( $title ) || ! self::has_cyrillic( $title ) ) {
			return $title;
		}
		return self::convert( $title );
	}

	/**
	 * Фильтр sanitize_file_name: «Логотип компании.png» → logotip-kompanii.png.
	 *
	 * @param string $filename Имя файла после очистки WordPress.
	 */
	public static function file_name( $filename ) {
		if ( ! is_string( $filename ) || ! self::has_cyrillic( $filename ) ) {
			return $filename;
		}
		return strtolower( self::convert( $filename ) );
	}
}
