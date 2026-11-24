<?php
/**
 * Блок «Кто работает над вашим проектом» (макет o-nas/komanda.html, секция «Как устроена работа»).
 *
 * Белая секция, шапка с вводным текстом; ниже слева — тёмная карточка (значок, плашка «Ваш
 * контакт», H3, текст, пункты с галочкой, круг в углу), справа — сетка ролей (значок, H3, текст;
 * колонки от 250 px). Уже ~880 px — карточка над ролями.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Block_Texts;
use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_items    = array_values( array_filter( (array) get_field( 'items' ), static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['title'] ?? '' ) ) ) );
$trust_main     = false !== get_field( 'main' );
$trust_points   = $trust_main ? array_slice( Block_Texts::lines( (string) get_field( 'main_points' ) ), 0, 5 ) : array();
$trust_text     = trim( (string) get_field( 'main_text' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_icon     = static fn( $id, string $fallback ): string => Icons::exists( (string) $id ) ? (string) $id : $fallback;

if ( ! $trust_items && ! $trust_main && ! $is_preview ) {
	return;
}
?>
<section class="b-roles trust-bg-white"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-roles__inner">
		<?php Blocks::section_head( 'b-roles__head', $trust_title_id ); ?>
		<div class="b-roles__body">
			<?php if ( $trust_main ) : ?>
				<div class="b-roles__main">
					<span class="b-roles__circle" aria-hidden="true"></span>
					<span class="b-roles__main-icon" aria-hidden="true">
						<?php
						Icons::render(
							$trust_icon( get_field( 'main_icon' ), 'chat-dots' ),
							array(
								'size'         => 26,
								'stroke_width' => 1.9,
							)
						);
						?>
					</span>
					<span class="b-roles__label"><?php echo esc_html( Blocks::text( 'main_label', __( 'Ваш контакт', 'trust' ) ) ); ?></span>
					<h3 class="b-roles__main-title"><?php echo esc_html( Blocks::text( 'main_title', __( 'Аккаунт-менеджер', 'trust' ) ) ); ?></h3>
					<?php if ( '' !== $trust_text ) : ?>
						<p class="b-roles__main-text"><?php echo esc_html( $trust_text ); ?></p>
					<?php endif; ?>
					<?php if ( $trust_points ) : ?>
						<ul class="b-roles__points">
							<?php foreach ( $trust_points as $trust_point ) : ?>
								<li>
									<?php
									Icons::render(
										'check',
										array(
											'size'         => 18,
											'stroke_width' => 2.4,
										)
									);
									?>
									<?php echo esc_html( $trust_point ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( $trust_items ) : ?>
				<ul class="b-roles__list">
					<?php foreach ( $trust_items as $trust_row ) : ?>
						<li class="b-roles__item">
							<span class="b-roles__icon" aria-hidden="true">
								<?php
								Icons::render(
									$trust_icon( $trust_row['icon'] ?? '', 'search' ),
									array(
										'size'         => 22,
										'stroke_width' => 1.9,
									)
								);
								?>
							</span>
							<h3 class="b-roles__title"><?php echo esc_html( $trust_row['title'] ); ?></h3>
							<?php if ( '' !== trim( (string) ( $trust_row['text'] ?? '' ) ) ) : ?>
								<p class="b-roles__text"><?php echo esc_html( $trust_row['text'] ); ?></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>
