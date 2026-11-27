<?php
/**
 * Блок «Слово клиенту» (макет portfolio-blog-404/keys.html, раздел «05 · Отзыв»).
 *
 * Надзаголовок с номером раздела, H2 и тёмная карточка: кавычки, цитата, фото (или силуэт), имя,
 * должность и компания, справа — «Благодарственное письмо» (скан на весь экран — trust-lightbox,
 * PDF — в новой вкладке) или «Оригинал отзыва» (ссылка). Отзыв — из поля кейса «Отзыв клиента»,
 * выбранный в блоке или вписанный вручную. Нет текста отзыва — блока на сайте нет.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Case_Blocks;
use Trust\Reviews;

defined( 'ABSPATH' ) || exit;

$trust_source = (string) get_field( 'source' );
$trust_text   = '';
$trust_name   = '';
$trust_role   = '';
$trust_photo  = 0;
$trust_letter = 0;
$trust_link   = '';

if ( 'manual' === $trust_source ) {
	$trust_text   = trim( (string) get_field( 'text' ) );
	$trust_name   = trim( (string) get_field( 'name' ) );
	$trust_role   = trim( (string) get_field( 'role' ) );
	$trust_photo  = (int) get_field( 'photo' );
	$trust_letter = (int) get_field( 'letter' );
} else {
	$trust_review = 'review' === $trust_source ? (int) get_field( 'review' ) : (int) get_post_meta( (int) $post_id, 'trust_review', true );
	if ( $trust_review && 'review' === get_post_type( $trust_review ) && 'publish' === get_post_status( $trust_review ) ) {
		$trust_text   = trim( (string) get_post_meta( $trust_review, 'trust_text', true ) );
		$trust_name   = trim( (string) get_post_meta( $trust_review, 'trust_name', true ) );
		$trust_name   = '' !== $trust_name ? $trust_name : Reviews::author_line( $trust_review, true );
		$trust_role   = implode(
			', ',
			array_filter(
				array(
					trim( (string) get_post_meta( $trust_review, 'trust_position', true ) ),
					trim( (string) get_post_meta( $trust_review, 'trust_company', true ) ),
				),
				'strlen'
			)
		);
		$trust_photo  = (int) get_post_meta( $trust_review, 'trust_photo', true );
		$trust_letter = (int) get_post_meta( $trust_review, 'trust_letter', true );
		$trust_link   = trim( (string) get_post_meta( $trust_review, 'trust_source', true ) );
	}
}

$trust_title_id = Blocks::uid( $block, 'title' );

if ( '' === $trust_text && ! $is_preview ) {
	return;
}

// Письмо: картинка — на весь экран, PDF — в новой вкладке; без письма — ссылка на оригинал отзыва.
$trust_letter_url = $trust_letter ? (string) wp_get_attachment_url( $trust_letter ) : '';
$trust_letter_img = $trust_letter && wp_attachment_is_image( $trust_letter );

echo Case_Blocks::open( 'b-case-quote', $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в open().
Case_Blocks::head( $trust_title_id, __( 'Слово клиенту', 'trust' ), __( 'Отзыв', 'trust' ) );
?>
	<?php if ( '' !== $trust_text ) : ?>
		<figure class="b-case-quote__card">
			<svg class="b-case-quote__mark" width="36" height="28" viewBox="0 0 44 34" aria-hidden="true" focusable="false"><path d="M0 34V20.4C0 8.9 6.2 2.1 17.4 0l1.9 4.6C13 6.4 10.2 10 9.8 15.3H18V34H0zm25 0V20.4C25 8.9 31.2 2.1 42.4 0l1.6 4.6c-6.3 1.8-9.1 5.4-9.5 10.7H43V34H25z"/></svg>
			<blockquote class="b-case-quote__text"><?php echo wp_kses_post( wpautop( esc_html( $trust_text ) ) ); ?></blockquote>
			<?php if ( '' !== $trust_name || '' !== $trust_role || $trust_letter_url || '' !== $trust_link ) : ?>
				<figcaption class="b-case-quote__foot">
					<?php if ( '' !== $trust_name || '' !== $trust_role ) : ?>
						<span class="b-case-quote__author">
							<?php if ( $trust_photo ) : ?>
								<?php
								echo Blocks::image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image().
									$trust_photo,
									'thumbnail',
									array(
										'class'   => 'b-case-quote__photo',
										'loading' => 'lazy',
									),
									'',
									true
								);
								?>
							<?php else : ?>
								<span class="b-case-quote__photo b-case-quote__photo--empty" aria-hidden="true"><svg width="40" height="40" viewBox="0 0 40 40" focusable="false"><circle cx="20" cy="15" r="7.5"/><path d="M5 40c1.5-9 7.5-14 15-14s13.5 5 15 14z"/></svg></span>
							<?php endif; ?>
							<span class="b-case-quote__who">
								<?php if ( '' !== $trust_name ) : ?>
									<span class="b-case-quote__name"><?php echo esc_html( $trust_name ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== $trust_role ) : ?>
									<span class="b-case-quote__role"><?php echo esc_html( $trust_role ); ?></span>
								<?php endif; ?>
							</span>
						</span>
					<?php endif; ?>
					<?php
					$trust_icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/></svg>';
					?>
					<?php if ( $trust_letter_url && $trust_letter_img ) : ?>
						<?php /* translators: %s: автор отзыва. */ ?>
						<a class="b-case-quote__link" href="<?php echo esc_url( $trust_letter_url ); ?>" data-trust-lightbox data-lightbox-caption="<?php echo esc_attr( sprintf( __( 'Благодарственное письмо — %s', 'trust' ), $trust_name ) ); ?>"><?php echo $trust_icon . esc_html( Blocks::text( 'letter_label', __( 'Благодарственное письмо', 'trust' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- постоянная разметка SVG. ?></a>
					<?php elseif ( $trust_letter_url ) : ?>
						<a class="b-case-quote__link" href="<?php echo esc_url( $trust_letter_url ); ?>" target="_blank" rel="noopener"><?php echo $trust_icon . esc_html( Blocks::text( 'letter_label', __( 'Благодарственное письмо', 'trust' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- постоянная разметка SVG. ?><span class="screen-reader-text"> <?php esc_html_e( '(PDF, откроется в новой вкладке)', 'trust' ); ?></span></a>
					<?php elseif ( '' !== $trust_link ) : ?>
						<a class="b-case-quote__link" href="<?php echo esc_url( $trust_link ); ?>" target="_blank" rel="noopener"><?php echo $trust_icon . esc_html( Blocks::text( 'source_label', __( 'Оригинал отзыва', 'trust' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- постоянная разметка SVG. ?><span class="screen-reader-text"> <?php esc_html_e( '(откроется в новой вкладке)', 'trust' ); ?></span></a>
					<?php endif; ?>
				</figcaption>
			<?php endif; ?>
		</figure>
	<?php else : ?>
		<?php echo Blocks::editor_warning( 'manual' === $trust_source ? __( 'Впишите цитату в настройках блока.', 'trust' ) : __( 'Нет отзыва: выберите его в данных кейса («Отзыв клиента») или в настройках блока. У отзыва должен быть текст.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning(). ?>
	<?php endif; ?>
</section>
