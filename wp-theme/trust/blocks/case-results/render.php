<?php
/**
 * Блок «Результаты» (макет portfolio-blog-404/keys.html, раздел «04 · Результат»).
 *
 * Надзаголовок с номером раздела, H2, графики во вкладках (кнопки-«таблетки», role="tablist":
 * стрелки ← →, Home, End; один график — без вкладок; без JavaScript видны все графики) с
 * подписью под каждым; график открывается на весь экран — trust-lightbox), таблица «до / после»: показатель (th), было, стало (фиолетовым). Таблица в
 * рамке листается вбок, если не помещается (тогда её можно прокрутить и с клавиатуры).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Case_Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_tabs     = array_values( array_filter( (array) get_field( 'tabs' ), static fn( $row ) => is_array( $row ) && ! empty( $row['image'] ) ) );
$trust_rows     = array_values( array_filter( (array) get_field( 'rows' ), static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['metric'] ?? '' ) ) ) );
$trust_uid      = Blocks::uid( $block, 'results' );
$trust_title_id = $trust_uid . '-title';
$trust_has_tabs = count( $trust_tabs ) > 1;

if ( ! $trust_tabs && ! $trust_rows && ! $is_preview ) {
	return;
}

echo Case_Blocks::open( 'b-case-results', $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в open().
Case_Blocks::head( $trust_title_id, __( 'Что получилось', 'trust' ), __( 'Результат', 'trust' ) );
?>
	<?php if ( $trust_has_tabs ) : ?>
		<div class="b-case-results__tabs" role="tablist" aria-label="<?php echo esc_attr( Blocks::text( 'tablist_label', __( 'Графики результатов', 'trust' ) ) ); ?>" data-case-tabs>
			<?php foreach ( $trust_tabs as $trust_i => $trust_tab ) : ?>
				<button type="button" role="tab" class="b-case-results__tab" id="<?php echo esc_attr( $trust_uid . '-tab-' . $trust_i ); ?>" aria-selected="<?php echo 0 === $trust_i ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $trust_uid . '-panel-' . $trust_i ); ?>" tabindex="<?php echo 0 === $trust_i && ! $is_preview ? '0' : '-1'; ?>"><?php echo esc_html( trim( (string) $trust_tab['label'] ) ); ?></button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<?php foreach ( $trust_tabs as $trust_i => $trust_tab ) : ?>
		<?php
		$trust_image   = (int) $trust_tab['image'];
		$trust_caption = trim( (string) ( $trust_tab['caption'] ?? '' ) );
		/* translators: %s: название вкладки графика. */
		$trust_alt = Blocks::alt_text( $trust_image, (string) ( $trust_tab['alt'] ?? '' ), sprintf( __( 'График: %s', 'trust' ), trim( (string) ( $trust_tab['label'] ?? '' ) ) ) );
		?>
		<figure class="b-case-results__panel<?php echo 0 === $trust_i ? ' is-active' : ''; ?>" id="<?php echo esc_attr( $trust_uid . '-panel-' . $trust_i ); ?>"<?php echo $trust_has_tabs ? ' role="tabpanel" aria-labelledby="' . esc_attr( $trust_uid . '-tab-' . $trust_i ) . '"' . ( $is_preview ? '' : ' tabindex="0"' ) : ''; ?>>
			<?php
			$trust_img = Blocks::image(
				$trust_image,
				'large',
				array(
					'class'   => 'b-case-results__img',
					'sizes'   => '(max-width: 1023px) 100vw, 800px',
					'loading' => 'lazy',
				),
				$trust_alt
			);
			// График открывается на весь экран (правка заказчика, как скриншоты в «Карусели кейсов»):
			// ссылка на оригинал, assets/js/lightbox.js; без JavaScript ссылка открывает файл картинки.
			$trust_full = $is_preview ? '' : (string) wp_get_attachment_image_url( $trust_image, 'full' );
			if ( '' !== $trust_full ) {
				printf(
					'<a class="b-case-results__open" href="%s" data-trust-lightbox data-lightbox-caption="%s" aria-label="%s">',
					esc_url( $trust_full ),
					esc_attr( trim( (string) ( $trust_tab['label'] ?? '' ) ) ),
					/* translators: %s: описание графика (alt). */
					esc_attr( sprintf( __( 'Открыть на весь экран: %s', 'trust' ), $trust_alt ) )
				);
			}
			echo $trust_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image().
			if ( '' !== $trust_full ) {
				echo '<span class="b-case-results__zoom" aria-hidden="true">';
				Icons::render( 'expand', array( 'size' => 20 ) );
				echo '</span></a>';
			}
			?>
			<?php if ( '' !== $trust_caption ) : ?>
				<figcaption class="b-case-results__caption"><?php echo esc_html( $trust_caption ); ?></figcaption>
			<?php endif; ?>
		</figure>
	<?php endforeach; ?>
	<?php if ( $trust_rows ) : ?>
		<div class="b-case-results__table" role="region" aria-labelledby="<?php echo esc_attr( $trust_uid . '-caption' ); ?>" data-case-table>
			<?php
			// Без пробелов между тегами таблицы: превью блока в редакторе (React) их не допускает.
			$trust_table  = '<table><caption class="screen-reader-text" id="' . esc_attr( $trust_uid . '-caption' ) . '">' . esc_html( Blocks::text( 'table_caption', __( 'Показатели до и после работ', 'trust' ) ) ) . '</caption>';
			$trust_table .= '<thead><tr><th scope="col">' . esc_html( Blocks::text( 'col_metric', __( 'Показатель', 'trust' ) ) ) . '</th><th scope="col">' . esc_html( Blocks::text( 'col_before', __( 'До', 'trust' ) ) ) . '</th><th scope="col">' . esc_html( Blocks::text( 'col_after', __( 'После', 'trust' ) ) ) . '</th></tr></thead><tbody>';
			foreach ( $trust_rows as $trust_row ) {
				$trust_table .= '<tr><th scope="row">' . esc_html( trim( (string) $trust_row['metric'] ) ) . '</th><td>' . esc_html( trim( (string) ( $trust_row['before'] ?? '' ) ) ) . '</td><td class="is-after">' . esc_html( trim( (string) ( $trust_row['after'] ?? '' ) ) ) . '</td></tr>';
			}
			echo $trust_table . '</tbody></table>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано выше.
			?>
		</div>
	<?php endif; ?>
	<?php if ( ! $trust_tabs && ! $trust_rows ) : ?>
		<?php echo Blocks::editor_warning( __( 'Добавьте графики или строки таблицы в настройках блока.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning(). ?>
	<?php endif; ?>
</section>
