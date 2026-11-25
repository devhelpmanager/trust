<?php
/**
 * Тип страницы «Документ» (ТЗ, раздел 6.2; макеты o-nas/oferta.html, politika-cookie.html,
 * soglasie-pd.html): разметка с содержанием сбоку, нумерация разделов и пунктов, стили текста
 * документа, таблицы-карточки, версия для печати.
 *
 * Содержание строится из заголовков H2 текста документа (блоки «Заголовок» верхнего уровня):
 * у каждого появляется якорь (латиницей из текста заголовка, если свой не задан). Разделы и пункты
 * списков со стилем «Пункты документа» нумеруются сами — «1.», «1.1.» (CSS-счётчики), если у
 * страницы включена «Нумерация разделов и пунктов» (группа полей «Документ»).
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Документ: разбор блоков страницы, содержание, стили, таблицы.
 */
final class Document {

	/**
	 * Разделитель первого экрана и текста документа в разметке the_content.
	 */
	private const SPLIT = '<!-- trust:document-split -->';

	/**
	 * Начало и конец пары «Коротко о главном» + «Пример галочки согласия» (рядом на компьютере).
	 */
	private const PAIR_START = '<!-- trust:document-pair -->';
	private const PAIR_END   = '<!-- /trust:document-pair -->';

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_styles' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_filter( 'render_block_core/table', array( self::class, 'table' ), 10, 2 );
	}

	/**
	 * Стили блоков ядра для текста документа (выбираются в редакторе, панель «Стили»).
	 */
	public static function register_styles(): void {
		register_block_style(
			'core/list',
			array(
				'name'  => 'clauses',
				'label' => __( 'Пункты документа', 'trust' ),
			)
		);
		register_block_style(
			'core/paragraph',
			array(
				'name'  => 'doc-intro',
				'label' => __( 'Вступление', 'trust' ),
			)
		);
		register_block_style(
			'core/paragraph',
			array(
				'name'  => 'doc-note',
				'label' => __( 'Примечание', 'trust' ),
			)
		);
		register_block_style(
			'core/table',
			array(
				'name'  => 'row-headers',
				'label' => __( 'Первая колонка — заголовки строк', 'trust' ),
			)
		);
	}

	/**
	 * Текущая страница — документ.
	 */
	public static function is_document(): bool {
		return is_page() && 'document' === Page_Types::of( get_queried_object_id() );
	}

	/**
	 * Стили и скрипт шаблона документа.
	 */
	public static function enqueue(): void {
		if ( ! self::is_document() ) {
			return;
		}
		wp_enqueue_style( 'trust-document', Assets::url( 'assets/css/document.css' ), array( 'trust-main' ), Assets::version( 'assets/css/document.css' ) );
		wp_enqueue_script(
			'trust-document',
			Assets::url( 'assets/js/document.js' ),
			array(),
			Assets::version( 'assets/js/document.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}

	/**
	 * Нумеровать разделы и пункты (поле «Документ → Нумерация разделов и пунктов»; не задано — да).
	 *
	 * @param int $post_id Страница.
	 */
	public static function numbered( int $post_id ): bool {
		$value = get_post_meta( $post_id, 'trust_doc_numbering', true );
		return '' === $value || (bool) $value;
	}

	/**
	 * Разбирает страницу документа: первый экран, текст (с якорями у H2) и содержание.
	 *
	 * @param \WP_Post $post Страница.
	 * @return array{hero: string, main: string, toc: array<int, array{id: string, title: string}>}
	 */
	public static function build( \WP_Post $post ): array {
		$blocks = parse_blocks( $post->post_content );
		$hero   = array();
		while ( $blocks && ( ( null === $blocks[0]['blockName'] && '' === trim( (string) $blocks[0]['innerHTML'] ) ) || str_starts_with( (string) $blocks[0]['blockName'], 'trust/hero-' ) ) ) {
			$hero[] = array_shift( $blocks );
		}
		$blocks = array_values( $blocks );

		$toc  = array();
		$used = array();
		foreach ( $blocks as $i => $block ) {
			if ( 'core/heading' !== $block['blockName'] || 2 !== (int) ( $block['attrs']['level'] ?? 2 ) ) {
				continue;
			}
			$title = trim( html_entity_decode( wp_strip_all_tags( (string) $block['innerHTML'] ), ENT_QUOTES, 'UTF-8' ) );
			if ( '' === $title ) {
				continue;
			}
			$id = self::heading_id( $block, $title, $used );
			if ( '' === (string) ( $block['attrs']['anchor'] ?? '' ) ) {
				$blocks[ $i ] = self::with_id( $block, $id );
			}
			$used[] = $id;
			$toc[]  = array(
				'id'    => $id,
				'title' => $title,
			);
		}

		// «Коротко о главном» и следом «Пример галочки согласия» — одна строка (макет согласия).
		$main  = array();
		$count = count( $blocks );
		for ( $i = 0; $i < $count; $i++ ) {
			$next = self::next_block( $blocks, $i );
			if ( 'trust/summary' === $blocks[ $i ]['blockName'] && null !== $next && 'trust/consent-example' === $blocks[ $next ]['blockName'] ) {
				$main[] = self::PAIR_START . serialize_block( $blocks[ $i ] ) . "\n\n" . serialize_block( $blocks[ $next ] ) . self::PAIR_END;
				$i      = $next; // Между ними — только пустые строки.
				continue;
			}
			$main[] = serialize_block( $blocks[ $i ] );
		}

		// Один вызов the_content: фильтры страницы (шорткоды, картинки) срабатывают как обычно.
		$html  = (string) apply_filters( 'the_content', serialize_blocks( $hero ) . self::SPLIT . implode( '', $main ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- фильтр ядра.
		$parts = explode( self::SPLIT, $html, 2 );
		$body  = str_replace(
			array( self::PAIR_START, self::PAIR_END ),
			array( '<div class="doc-pair">', '</div>' ),
			$parts[1] ?? ''
		);

		return array(
			'hero' => $parts[0],
			'main' => $body,
			'toc'  => $toc,
		);
	}

	/**
	 * Номер следующего непустого блока после $i или null.
	 *
	 * @param array $blocks Блоки.
	 * @param int   $i      Текущий.
	 */
	private static function next_block( array $blocks, int $i ): ?int {
		for ( $k = $i + 1, $n = count( $blocks ); $k < $n; $k++ ) {
			if ( null !== $blocks[ $k ]['blockName'] ) {
				return $k;
			}
			if ( '' !== trim( (string) $blocks[ $k ]['innerHTML'] ) ) {
				return null;
			}
		}
		return null;
	}

	/**
	 * Якорь раздела: свой («HTML-якорь» в настройках блока) или латиницей из текста заголовка.
	 *
	 * @param array    $block Блок заголовка.
	 * @param string   $title Текст заголовка.
	 * @param string[] $used  Уже занятые якоря.
	 */
	private static function heading_id( array $block, string $title, array $used ): string {
		$own = (string) ( $block['attrs']['anchor'] ?? '' );
		if ( '' !== $own ) {
			return $own;
		}
		if ( preg_match( '~<h2\b[^>]*\sid="([^"]+)"~', (string) $block['innerHTML'], $m ) ) {
			return $m[1];
		}
		$base = sanitize_title( Translit::convert( mb_substr( $title, 0, 60 ) ) );
		$base = '' !== $base ? $base : 'razdel';
		$id   = $base;
		$n    = 2;
		while ( in_array( $id, $used, true ) ) {
			$id = $base . '-' . $n;
			++$n;
		}
		return $id;
	}

	/**
	 * Блок заголовка с id в разметке.
	 *
	 * @param array  $block Блок.
	 * @param string $id    Якорь.
	 */
	private static function with_id( array $block, string $id ): array {
		$set                = static function ( string $html ) use ( $id ): string {
			$tags = new \WP_HTML_Tag_Processor( $html );
			if ( $tags->next_tag( 'h2' ) ) {
				$tags->set_attribute( 'id', $id );
			}
			return $tags->get_updated_html();
		};
		$block['innerHTML'] = $set( (string) $block['innerHTML'] );
		foreach ( $block['innerContent'] as $k => $chunk ) {
			if ( is_string( $chunk ) && str_contains( $chunk, '<h2' ) ) {
				$block['innerContent'][ $k ] = $set( $chunk );
			}
		}
		return $block;
	}

	/**
	 * Таблица (блок «Таблица»): у ячеек data-label — название колонки (на узком экране таблица
	 * становится карточками, ТЗ п. 8.9); строка, где заполнена только первая ячейка, — подзаголовок
	 * группы на всю ширину; стиль «Первая колонка — заголовки строк» — первые ячейки строк
	 * становятся <th scope="row">.
	 *
	 * @param string $html  Разметка блока.
	 * @param array  $block Блок.
	 */
	public static function table( string $html, array $block ): string {
		if ( ! str_contains( $html, '<table' ) ) {
			return $html;
		}
		$row_headers = (bool) preg_match( '/(^|\s)is-style-row-headers(\s|$)/', (string) ( $block['attrs']['className'] ?? '' ) );

		$labels = array();
		if ( preg_match( '~<thead\b[^>]*>(.*?)</thead>~s', $html, $head ) && preg_match_all( '~<th\b[^>]*>(.*?)</th>~s', $head[1], $cells ) ) {
			$labels = array_map( static fn( string $cell ): string => trim( html_entity_decode( wp_strip_all_tags( $cell ), ENT_QUOTES, 'UTF-8' ) ), $cells[1] );
		}
		return (string) preg_replace_callback(
			'~<tbody\b[^>]*>(.*?)</tbody>~s',
			static function ( array $body ) use ( $labels, $row_headers ): string {
				$rows = preg_replace_callback(
					'~<tr\b([^>]*)>(.*?)</tr>~s',
					static function ( array $row ) use ( $labels, $row_headers ): string {
						preg_match_all( '~<(td|th)\b([^>]*)>(.*?)</\1>~s', $row[2], $cells, PREG_SET_ORDER );
						if ( ! $cells ) {
							return $row[0];
						}
						$texts = array_map( static fn( array $cell ): string => trim( wp_strip_all_tags( $cell[3] ) ), $cells );
						// Подзаголовок группы: текст только в первой ячейке.
						if ( count( $cells ) > 1 && '' !== $texts[0] && '' === implode( '', array_slice( $texts, 1 ) ) ) {
							return '<tr class="doc-table__group"><th colspan="' . count( $cells ) . '" scope="colgroup">' . $cells[0][3] . '</th></tr>';
						}
						$out = '';
						foreach ( $cells as $i => $cell ) {
							$attrs = preg_replace( '~\sdata-label="[^"]*"~', '', $cell[2] );
							$label = $labels[ $i ] ?? '';
							if ( 0 === $i && $row_headers ) {
								$out .= '<th scope="row"' . $attrs . '>' . $cell[3] . '</th>';
								continue;
							}
							$out .= '<' . $cell[1] . $attrs . ( '' !== $label ? ' data-label="' . esc_attr( $label ) . '"' : '' ) . '>' . $cell[3] . '</' . $cell[1] . '>';
						}
						return '<tr' . $row[1] . '>' . $out . '</tr>';
					},
					$body[1]
				);
				return str_replace( $body[1], (string) $rows, $body[0] );
			},
			$html
		);
	}

	/**
	 * Поля в «Других документах»: значок, короткое название, подпись (группа полей «Документ»).
	 *
	 * @param int $post_id Страница.
	 * @return array{icon: string, title: string, note: string}
	 */
	public static function card( int $post_id ): array {
		$icon  = (string) get_post_meta( $post_id, 'trust_doc_icon', true );
		$title = trim( (string) get_post_meta( $post_id, 'trust_doc_short', true ) );
		return array(
			'icon'  => '' !== $icon && Icons::exists( $icon ) ? $icon : 'document',
			'title' => '' !== $title ? $title : get_the_title( $post_id ),
			'note'  => trim( (string) get_post_meta( $post_id, 'trust_doc_note', true ) ),
		);
	}
}
