<?php
/**
 * Блок «Кто работал над проектом» (макет portfolio-blog-404/keys.html, раздел «06 · Команда»).
 *
 * Надзаголовок с номером раздела, H2 и карточки сотрудников: фото (или силуэт), имя, роль в
 * проекте (пусто — должность). Состав — поле кейса «Команда проекта» или свой список блока.
 * Якорь по умолчанию case-team: на него ведёт «Команда проекта — N человек» в карточке кейса.
 * Три в ряд; блок уже 640 px (телефон, узкая колонка рядом с карточкой «О проекте») — в колонку.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Case_Blocks;
use Trust\Cases;

defined( 'ABSPATH' ) || exit;

$trust_rows = (array) get_field( 'members' );
$trust_rows = array_filter( $trust_rows, static fn( $row ) => is_array( $row ) && ! empty( $row['member'] ) ) ? $trust_rows : Cases::team( (int) $post_id );
$trust_team = array();
foreach ( $trust_rows as $trust_row ) {
	$trust_id = is_array( $trust_row ) ? (int) ( $trust_row['member'] ?? 0 ) : 0;
	if ( $trust_id && 'team' === get_post_type( $trust_id ) && 'publish' === get_post_status( $trust_id ) ) {
		$trust_role              = trim( (string) ( $trust_row['role'] ?? '' ) );
		$trust_team[ $trust_id ] = '' !== $trust_role ? $trust_role : trim( (string) get_post_meta( $trust_id, 'trust_position', true ) );
	}
}
$trust_title_id = Blocks::uid( $block, 'title' );

if ( ! $trust_team && ! $is_preview ) {
	return;
}

echo Case_Blocks::open( 'b-case-team', $trust_title_id, 'case-team' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в open().
Case_Blocks::head( $trust_title_id, __( 'Кто работал над проектом', 'trust' ), __( 'Команда', 'trust' ) );
?>
	<?php if ( $trust_team ) : ?>
		<ul class="b-case-team__list">
			<?php foreach ( $trust_team as $trust_id => $trust_role ) : ?>
				<li class="b-case-team__card">
					<?php
					$trust_photo = Blocks::image(
						(int) get_post_thumbnail_id( $trust_id ),
						'thumbnail',
						array(
							'class'   => 'b-case-team__photo',
							'loading' => 'lazy',
						),
						'',
						true
					);
					?>
					<?php if ( '' !== $trust_photo ) : ?>
						<?php echo $trust_photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
					<?php else : ?>
						<span class="b-case-team__photo b-case-team__photo--empty" aria-hidden="true"><svg width="40" height="40" viewBox="0 0 40 40" focusable="false"><circle cx="20" cy="15" r="7.5"/><path d="M5 40c1.5-9 7.5-14 15-14s13.5 5 15 14z"/></svg></span>
					<?php endif; ?>
					<span class="b-case-team__who">
						<span class="b-case-team__name"><?php echo esc_html( get_the_title( $trust_id ) ); ?></span>
						<?php if ( '' !== $trust_role ) : ?>
							<span class="b-case-team__role"><?php echo esc_html( $trust_role ); ?></span>
						<?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<?php echo Blocks::editor_warning( __( 'Нет команды: добавьте сотрудников в данных кейса («Команда проекта») или в настройках блока.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning(). ?>
	<?php endif; ?>
</section>
