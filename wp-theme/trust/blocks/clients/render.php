<?php
/**
 * Блок «Нам доверяют» (макет o-nas/o-kompanii.html, «Клиенты»).
 *
 * Лавандовая секция: шапка и справа кнопка «Смотреть кейсы» (по умолчанию — страница типа
 * «Портфолио»), ниже плитки логотипов (список «Логотипы клиентов», от 170 px в колонку). Логотип —
 * картинка с alt-названием, без логотипа — название текстом. Со ссылкой плитка открывает сайт
 * клиента в новой вкладке.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;
use Trust\Page_Types;

defined( 'ABSPATH' ) || exit;

$trust_logos    = array_values( array_filter( (array) get_field( 'logos' ), static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['name'] ?? '' ) ) ) );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_url      = trim( (string) get_field( 'button_url' ) );
$trust_url      = '' !== $trust_url ? $trust_url : Page_Types::url( 'portfolio' );
$trust_button   = false !== get_field( 'button' ) && '' !== $trust_url;

if ( ! $trust_logos && ! $is_preview ) {
	return;
}
?>
<section class="b-clients trust-bg-lavender"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-clients__inner">
		<div class="b-clients__top">
			<?php Blocks::section_head( 'b-clients__head', $trust_title_id ); ?>
			<?php if ( $trust_button ) : ?>
				<a class="b-clients__more" href="<?php echo esc_url( $trust_url ); ?>">
					<?php echo esc_html( Blocks::text( 'button_label', __( 'Смотреть кейсы', 'trust' ) ) ); ?>
					<?php
					Icons::render(
						'arrow-right',
						array(
							'size'         => 16,
							'stroke_width' => 2.2,
						)
					);
					?>
				</a>
			<?php endif; ?>
		</div>
		<ul class="b-clients__list" aria-label="<?php esc_attr_e( 'Логотипы клиентов', 'trust' ); ?>">
			<?php foreach ( $trust_logos as $trust_row ) : ?>
				<?php
				$trust_name = trim( $trust_row['name'] );
				$trust_img  = Blocks::image(
					(int) ( $trust_row['logo'] ?? 0 ),
					'medium',
					array(
						'class'   => 'b-clients__logo',
						'loading' => 'lazy',
						'sizes'   => '170px',
					),
					$trust_name
				);
				$trust_site = trim( (string) ( $trust_row['url'] ?? '' ) );
				$trust_body = '' !== $trust_img ? $trust_img : '<span class="b-clients__name">' . esc_html( $trust_name ) . '</span>';
				?>
				<li class="b-clients__item">
					<?php if ( '' !== $trust_site ) : ?>
						<a class="b-clients__tile" href="<?php echo esc_url( $trust_site ); ?>" target="_blank" rel="noopener"><?php echo $trust_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано выше. ?></a>
					<?php else : ?>
						<span class="b-clients__tile"><?php echo $trust_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано выше. ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
