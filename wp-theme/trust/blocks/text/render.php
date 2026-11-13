<?php
/**
 * Блок «Блок с текстом» (макет holst-blokov/TextBlock, seo-bloki/03-tekst.html).
 *
 * Белая секция, текст в колонке 960 px по центру. Содержимое — вложенные блоки ядра
 * (Trust\Blocks::TEXT_BLOCKS); оформление текста — класс .prose (assets/css/main.css),
 * тот же, что у текста статей.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;

defined( 'ABSPATH' ) || exit;

$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_template = array(
	array(
		'core/heading',
		array(
			'level'       => 2,
			'placeholder' => __( 'Заголовок раздела', 'trust' ),
		),
	),
	array( 'core/paragraph', array( 'placeholder' => __( 'Текст…', 'trust' ) ) ),
);
?>
<section class="b-text trust-bg-white"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?>>
	<InnerBlocks class="b-text__body prose" allowedBlocks="<?php echo esc_attr( wp_json_encode( Blocks::TEXT_BLOCKS ) ); ?>" template="<?php echo esc_attr( wp_json_encode( $trust_template ) ); ?>" />
</section>
