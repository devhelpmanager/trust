<?php
/**
 * Общее у блоков содержимого кейса (ТЗ, п. 8.10; макет portfolio-blog-404/keys.html, колонка
 * статьи): поля и вывод шапки раздела — надзаголовок с номером («01 · Задача»; номер ставится
 * автоматически по порядку блоков, счётчик CSS в assets/css/case.css) и H2.
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Шапка разделов «С чем пришёл клиент», «Что мешало росту», «Что сделали», «Результат»,
 * «Слово клиенту», «Кто работал над проектом».
 */
final class Case_Blocks {

	/**
	 * Поля шапки раздела: якорь, надзаголовок (слово после номера), заголовок H2.
	 *
	 * @param string $eyebrow Надзаголовок по умолчанию («Задача»).
	 * @param string $title   Заголовок по умолчанию.
	 * @param string $anchor  Якорь по умолчанию (подсказка в поле; '' — нет).
	 */
	public static function fields( string $eyebrow, string $title, string $anchor = '' ): array {
		return array(
			array(
				'name'         => 'anchor',
				'label'        => __( 'Якорь', 'trust' ),
				'type'         => 'text',
				'placeholder'  => $anchor,
				'instructions' => __( 'Необязательно. Латиница, цифры и дефис, без #.', 'trust' ),
				'wrapper'      => array( 'width' => 50 ),
			),
			array(
				'name'          => 'eyebrow',
				'label'         => __( 'Надзаголовок', 'trust' ),
				'type'          => 'text',
				'default_value' => $eyebrow,
				'maxlength'     => 30,
				'instructions'  => __( 'Слово после номера раздела. Номер («01 ·», «02 ·»…) ставится автоматически по порядку блоков кейса.', 'trust' ),
				'wrapper'       => array( 'width' => 50 ),
			),
			array(
				'name'          => 'title',
				'label'         => __( 'Заголовок (H2)', 'trust' ),
				'type'          => 'text',
				'required'      => 1,
				'default_value' => $title,
				'maxlength'     => 80,
			),
		);
	}

	/**
	 * Открывающий тег раздела: классы, id из поля «Якорь» (или $anchor), aria-labelledby.
	 *
	 * @param string $css      Класс блока (b-case-task).
	 * @param string $title_id id заголовка.
	 * @param string $anchor   Якорь, если поле пустое ('' — без id).
	 */
	public static function open( string $css, string $title_id, string $anchor = '' ): string {
		$id = Blocks::anchor( get_field( 'anchor' ) );
		$id = '' !== $id ? $id : $anchor;
		return '<section class="b-case ' . esc_attr( $css ) . '"' . ( '' !== $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . ' aria-labelledby="' . esc_attr( $title_id ) . '">';
	}

	/**
	 * Надзаголовок с номером раздела и H2. Надзаголовок по умолчанию — если поле ни разу не
	 * сохраняли (блок вставлен без настроек); пустое поле — только номер.
	 *
	 * @param string $title_id id заголовка.
	 * @param string $fallback Заголовок, если поле пустое.
	 * @param string $eyebrow  Надзаголовок по умолчанию.
	 */
	public static function head( string $title_id, string $fallback, string $eyebrow = '' ): void {
		$value   = get_field( 'eyebrow' );
		$eyebrow = null === $value ? $eyebrow : trim( (string) $value );
		echo '<p class="b-case__eyebrow"><span class="b-case__num" aria-hidden="true"></span>';
		if ( '' !== $eyebrow ) {
			echo '<span>' . esc_html( $eyebrow ) . '</span>';
		}
		echo '</p><h2 class="b-case__title" id="' . esc_attr( $title_id ) . '">' . esc_html( Blocks::text( 'title', $fallback ) ) . '</h2>';
	}
}
