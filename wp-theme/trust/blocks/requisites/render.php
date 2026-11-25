<?php
/**
 * Блок «Реквизиты» (макет o-nas/kontakty.html, «Реквизиты»; также страницы-документы).
 *
 * Белая секция: надзаголовок и H2 слева, справа кнопки «Скопировать» (строки «Название: значение»
 * в буфер обмена, view.js; без JavaScript кнопки нет) и «Скачать PDF» — карточка компании (если
 * файл загружен). Ниже — карточка-таблица <dl> в две колонки (на узком экране — в одну). На
 * телефоне кнопки — под таблицей, в одну строку (правки заказчика; в макете «Скопировать
 * реквизиты» и «Карточка компании, PDF» под заголовком). Данные — «Настройки сайта → Реквизиты».
 * Вид «В тексте документа» — только таблица в рамке (как «Реквизиты исполнителя» оферты) и ссылка
 * на «Контакты».
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;
use Trust\Page_Types;
use Trust\Requisites;

defined( 'ABSPATH' ) || exit;

$trust_rows     = Requisites::rows();
$trust_card     = Requisites::card_url();
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_copy     = Blocks::text( 'copy_label', __( 'Скопировать', 'trust' ) );

if ( ! $trust_rows && ! $is_preview ) {
	return;
}

// В тексте документа (раздел «Реквизиты исполнителя» оферты): только таблица и ссылка на «Контакты».
if ( 'document' === get_field( 'view' ) ) {
	if ( ! $trust_rows ) {
		echo Blocks::editor_warning( __( 'Заполните реквизиты: «Настройки сайта → Реквизиты».', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning().
		return;
	}
	$trust_contacts = false !== get_field( 'contacts_link' ) ? Page_Types::url( 'contacts' ) : '';
	?>
	<div class="b-req b-req--doc">
		<dl class="b-req__list b-req__list--doc" data-requisites>
			<?php foreach ( $trust_rows as $trust_row ) : ?>
				<div class="b-req__row">
					<dt><?php echo esc_html( $trust_row['label'] ); ?></dt>
					<dd>
					<?php
					echo wp_kses(
						make_clickable( esc_html( $trust_row['value'] ) ),
						array(
							'a' => array(
								'href' => true,
								'rel'  => true,
							),
						)
					);
					?>
						</dd>
				</div>
			<?php endforeach; ?>
		</dl>
		<?php if ( '' !== $trust_contacts ) : ?>
			<p class="b-req__more" data-noprint>
				<?php
				printf(
					/* translators: %s: ссылка на страницу «Контакты». */
					esc_html__( 'Полные реквизиты и карточка компании — на странице %s.', 'trust' ),
					'<a href="' . esc_url( $trust_contacts ) . '"' . ( $is_preview ? ' tabindex="-1"' : '' ) . '>«' . esc_html( get_the_title( url_to_postid( $trust_contacts ) ) ) . '»</a>'
				);
				?>
			</p>
		<?php endif; ?>
	</div>
	<?php
	return;
}
?>
<section class="b-req trust-bg-white"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-req__inner">
		<?php Blocks::section_head( 'b-req__head', $trust_title_id ); ?>
		<?php if ( $trust_rows ) : ?>
			<div class="b-req__actions">
				<button type="button" class="btn btn--outline btn--sm b-req__copy" data-requisites-copy data-copied="<?php echo esc_attr( Blocks::text( 'copied_label', __( 'Скопировано', 'trust' ) ) ); ?>"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>>
					<?php
					Icons::render(
						'copy-text',
						array(
							'size'         => 18,
							'stroke_width' => 2,
						)
					);
					?>
					<span aria-live="polite" data-requisites-copy-label><?php echo esc_html( $trust_copy ); ?></span>
				</button>
				<?php if ( '' !== $trust_card ) : ?>
					<a class="btn btn--sm b-req__card" href="<?php echo esc_url( $trust_card ); ?>" download<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>>
						<?php
						Icons::render(
							'download',
							array(
								'size'         => 18,
								'stroke_width' => 2,
							)
						);
						?>
						<?php echo esc_html( Blocks::text( 'card_label', __( 'Скачать PDF', 'trust' ) ) ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( $trust_rows ) : ?>
			<dl class="b-req__list" data-requisites>
				<?php foreach ( $trust_rows as $trust_row ) : ?>
					<div class="b-req__row">
						<dt><?php echo esc_html( $trust_row['label'] ); ?></dt>
						<dd><?php echo esc_html( $trust_row['value'] ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php else : ?>
			<?php echo Blocks::editor_warning( __( 'Заполните реквизиты: «Настройки сайта → Реквизиты».', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning(). ?>
		<?php endif; ?>
	</div>
</section>
