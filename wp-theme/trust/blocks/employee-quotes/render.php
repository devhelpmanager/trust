<?php
/**
 * Блок «Сотрудники о работе у нас» (макет o-nas/karera.html, секция «Изнутри»).
 *
 * Лавандовая секция: шапка и кнопка «Вся команда» справа, карточки-цитаты по три в ряд (на
 * телефоне — в столбик): значок кавычек, цитата, внизу фото (без фото — силуэт), имя и подпись
 * («должность · в команде с ГГГГ»; пусто — должность из карточки сотрудника).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;
use Trust\Page_Types;

defined( 'ABSPATH' ) || exit;

$trust_quotes = array();
foreach ( (array) get_field( 'quotes' ) as $trust_row ) {
	$trust_member = is_array( $trust_row ) ? get_post( (int) ( $trust_row['member'] ?? 0 ) ) : null;
	$trust_text   = is_array( $trust_row ) ? trim( (string) ( $trust_row['text'] ?? '' ) ) : '';
	if ( ! $trust_member || 'team' !== $trust_member->post_type || 'publish' !== $trust_member->post_status || '' === $trust_text ) {
		continue;
	}
	$trust_caption  = trim( (string) ( $trust_row['caption'] ?? '' ) );
	$trust_quotes[] = array(
		'member'  => $trust_member,
		'text'    => $trust_text,
		'caption' => '' !== $trust_caption ? $trust_caption : trim( (string) get_post_meta( $trust_member->ID, 'trust_position', true ) ),
	);
}

// Кнопка «Вся команда»: своя ссылка или страница типа «Команда»; выключатель убирает кнопку.
$trust_more = null;
if ( false !== get_field( 'show_more' ) ) {
	$trust_more = get_field( 'more' );
	$trust_more = is_array( $trust_more ) && ! empty( $trust_more['url'] ) && '' !== trim( (string) ( $trust_more['title'] ?? '' ) ) ? $trust_more : null;
	$trust_team = $trust_more ? '' : Page_Types::url( 'team' );
	if ( '' !== $trust_team ) {
		$trust_more = array(
			'url'    => $trust_team,
			'title'  => __( 'Вся команда', 'trust' ),
			'target' => '',
		);
	}
}
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_mark     = '<svg class="b-voices__mark" width="32" height="25" viewBox="0 0 44 34" aria-hidden="true" focusable="false"><path d="M0 34V20.4C0 8.9 6.2 2.1 17.4 0l1.9 4.6C13 6.4 10.2 10 9.8 15.3H18V34H0zm25 0V20.4C25 8.9 31.2 2.1 42.4 0l1.6 4.6c-6.3 1.8-9.1 5.4-9.5 10.7H43V34H25z"/></svg>';

if ( ! $trust_quotes && ! $is_preview ) {
	return;
}
?>
<section class="b-voices trust-bg-lavender"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-voices__inner">
		<div class="b-voices__top">
			<?php Blocks::section_head( 'b-voices__head', $trust_title_id ); ?>
			<?php if ( $trust_more ) : ?>
				<a class="btn btn--outline btn--sm b-voices__more" href="<?php echo esc_url( $trust_more['url'] ); ?>"<?php echo ! empty( $trust_more['target'] ) ? ' target="_blank" rel="noopener"' : ''; ?><?php echo $is_preview ? ' tabindex="-1"' : ''; ?>>
					<?php echo esc_html( $trust_more['title'] ); ?>
					<?php Icons::render( 'arrow-right', array( 'size' => 16 ) ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php if ( $trust_quotes ) : ?>
			<ul class="b-voices__list">
				<?php foreach ( $trust_quotes as $trust_quote ) : ?>
					<?php
					$trust_photo = Blocks::image(
						(int) get_post_thumbnail_id( $trust_quote['member'] ),
						'thumbnail',
						array(
							'class'   => 'b-voices__photo',
							'loading' => 'lazy',
							'sizes'   => '52px',
						),
						'',
						true
					);
					?>
					<li>
						<figure class="b-voices__card">
							<?php echo $trust_mark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- постоянный SVG. ?>
							<blockquote class="b-voices__text"><?php echo esc_html( $trust_quote['text'] ); ?></blockquote>
							<figcaption class="b-voices__who">
								<?php if ( '' !== $trust_photo ) : ?>
									<?php echo $trust_photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
								<?php else : ?>
									<span class="b-voices__photo b-voices__photo--empty" aria-hidden="true">
										<?php
										Icons::render(
											'user',
											array(
												'size' => 24,
												'stroke_width' => 1.6,
											)
										);
										?>
									</span>
								<?php endif; ?>
								<span class="b-voices__person">
									<span class="b-voices__name"><?php echo esc_html( get_the_title( $trust_quote['member'] ) ); ?></span>
									<?php if ( '' !== $trust_quote['caption'] ) : ?>
										<span class="b-voices__role"><?php echo esc_html( $trust_quote['caption'] ); ?></span>
									<?php endif; ?>
								</span>
							</figcaption>
						</figure>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<?php echo Blocks::editor_warning( __( 'Добавьте цитаты: выберите сотрудника и напишите текст.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning(). ?>
		<?php endif; ?>
	</div>
</section>
