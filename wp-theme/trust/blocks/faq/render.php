<?php
/**
 * Блок «Вопрос-ответ» (макет iskhodniki/holst-blokov/Faq.dc.html).
 *
 * Слева заголовок и ссылка, справа вопросы с номерами 01, 02…; ответы раскрываются
 * по нажатию, открытыми могут быть несколько. В редакторе все ответы раскрыты,
 * без JavaScript — тоже (чтобы содержимое было доступно).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

$trust_questions = array_values(
	array_filter(
		(array) get_field( 'questions' ),
		static fn( $row ) => ! empty( $row['question'] ) && ! empty( $row['answer'] )
	)
);
$trust_title     = (string) get_field( 'title' );
$trust_eyebrow   = (string) get_field( 'eyebrow' );
$trust_lead      = (string) get_field( 'lead' );
$trust_link      = get_field( 'more_link' );
$trust_anchor    = Blocks::anchor( get_field( 'anchor' ) );
$trust_title_id  = Blocks::uid( $block, 'title' );
$trust_open      = ! empty( $is_preview );
?>
<section class="b-faq"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-faq__inner">
		<div class="b-faq__head">
			<?php if ( $trust_eyebrow ) : ?>
				<span class="eyebrow"><?php echo esc_html( $trust_eyebrow ); ?></span>
			<?php endif; ?>
			<?php if ( $trust_title ) : ?>
				<h2 class="section-title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
			<?php endif; ?>
			<?php if ( $trust_lead ) : ?>
				<p class="section-lead"><?php echo esc_html( $trust_lead ); ?></p>
			<?php endif; ?>
			<?php if ( is_array( $trust_link ) && ! empty( $trust_link['url'] ) && ! empty( $trust_link['title'] ) ) : ?>
				<a class="b-faq__more" href="<?php echo esc_url( $trust_link['url'] ); ?>"<?php echo ! empty( $trust_link['target'] ) ? ' target="_blank" rel="noopener"' : ''; ?>>
					<?php echo esc_html( $trust_link['title'] ); ?>
					<?php Icons::render( 'arrow-right', array( 'size' => 18 ) ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $trust_questions ) : ?>
			<div class="b-faq__list">
				<?php foreach ( $trust_questions as $trust_i => $trust_row ) : ?>
					<?php
					$trust_number   = str_pad( (string) ( $trust_i + 1 ), 2, '0', STR_PAD_LEFT );
					$trust_panel_id = Blocks::uid( $block, 'answer-' . ( $trust_i + 1 ) );
					?>
					<div class="b-faq__item<?php echo $trust_open ? ' is-open' : ''; ?>">
						<h3 class="b-faq__q">
							<button type="button" class="b-faq__toggle" aria-expanded="<?php echo $trust_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $trust_panel_id ); ?>">
								<span class="b-faq__label">
									<span class="b-faq__num"><?php echo esc_html( $trust_number ); ?></span>
									<span><?php echo esc_html( $trust_row['question'] ); ?></span>
								</span>
								<span class="b-faq__icon" aria-hidden="true">
									<?php Icons::render( 'chevron-down', array( 'size' => 16 ) ); ?>
								</span>
							</button>
						</h3>
						<div class="b-faq__answer" id="<?php echo esc_attr( $trust_panel_id ); ?>"<?php echo $trust_open ? '' : ' hidden'; ?>>
							<?php echo wp_kses_post( $trust_row['answer'] ); ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php elseif ( $is_preview ) : ?>
			<p class="b-faq__empty"><?php esc_html_e( 'Добавьте вопросы в настройках блока.', 'trust' ); ?></p>
		<?php endif; ?>
	</div>
</section>
