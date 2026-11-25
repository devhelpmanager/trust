<?php
/**
 * Блок «Кому написать» (макет o-nas/kontakty.html, секция «Отделы»).
 *
 * Белая секция: шапка и карточки отделов (от 340 px в колонку — 3 / 2 / 1): значок, название (H3),
 * описание (можно со ссылкой), внизу — email, телефон, Telegram (email пуст — из карточки
 * ответственного сотрудника). Последняя — тёмная карточка «Письмо руководителю»: имя руководителя
 * в тексте ({name}) и email — из его карточки в «Сотрудниках» (или свои).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Contacts;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * Ссылки связи карточки: email, телефон, Telegram.
 *
 * @param string $email    Email.
 * @param string $phone    Телефон.
 * @param string $telegram Telegram.
 * @return array<int, array{0: string, 1: string}> Адрес и текст.
 */
$trust_links = static function ( string $email, string $phone, string $telegram ): array {
	$links = array();
	$email = sanitize_email( $email );
	if ( '' !== $email ) {
		$links[] = array( 'mailto:' . $email, $email );
	}
	if ( '' !== trim( $phone ) ) {
		$links[] = array( Contacts::tel_href( $phone ), trim( $phone ) );
	}
	$tg = Contacts::parse_telegram( $telegram );
	if ( $tg ) {
		$links[] = array( $tg['url'], $tg['handle'] );
	}
	return $links;
};

$trust_items = array();
foreach ( (array) get_field( 'items' ) as $trust_row ) {
	if ( ! is_array( $trust_row ) || '' === trim( (string) ( $trust_row['title'] ?? '' ) ) ) {
		continue;
	}
	$trust_email = (string) ( $trust_row['email'] ?? '' );
	if ( '' === trim( $trust_email ) && ! empty( $trust_row['member'] ) ) {
		$trust_email = (string) get_post_meta( (int) $trust_row['member'], 'trust_email', true );
	}
	$trust_items[] = array(
		'icon'  => (string) ( $trust_row['icon'] ?? '' ),
		'title' => trim( (string) $trust_row['title'] ),
		'text'  => (string) ( $trust_row['text'] ?? '' ),
		'links' => $trust_links( $trust_email, (string) ( $trust_row['phone'] ?? '' ), (string) ( $trust_row['telegram'] ?? '' ) ),
	);
}

// Карточка руководителя: выбранный сотрудник или первый с отметкой «Руководство».
$trust_leader = null;
if ( false !== get_field( 'leader_card' ) ) {
	$trust_member = (int) get_field( 'leader' );
	if ( ! $trust_member ) {
		$trust_found  = get_posts(
			array(
				'post_type'     => 'team',
				'post_status'   => 'publish',
				'numberposts'   => 1,
				'fields'        => 'ids',
				'no_found_rows' => true,
				'orderby'       => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'meta_key'      => 'trust_leader', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- одна запись.
				'meta_value'    => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- одна запись.
			)
		);
		$trust_member = $trust_found ? (int) $trust_found[0] : 0;
	}
	$trust_email = trim( (string) get_field( 'leader_email' ) );
	if ( '' === $trust_email && $trust_member ) {
		$trust_email = (string) get_post_meta( $trust_member, 'trust_email', true );
	}
	$trust_name   = $trust_member ? get_the_title( $trust_member ) : '';
	$trust_text   = Blocks::text( 'leader_text', __( 'Если что-то пошло не так, напишите напрямую — письмо прочитает {name} лично', 'trust' ) );
	$trust_text   = '' !== $trust_name ? str_replace( '{name}', $trust_name, $trust_text ) : str_replace( ' {name}', '', $trust_text );
	$trust_leader = array(
		'title' => Blocks::text( 'leader_title', __( 'Письмо руководителю', 'trust' ) ),
		'text'  => $trust_text,
		'links' => $trust_links( $trust_email, '', '' ),
	);
}

$trust_title_id = Blocks::uid( $block, 'title' );

if ( ! $trust_items && ! $trust_leader && ! $is_preview ) {
	return;
}
?>
<section class="b-depts trust-bg-white"<?php echo Blocks::section_attrs( $trust_title_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-depts__inner">
		<?php Blocks::section_head( 'b-depts__head', $trust_title_id ); ?>
		<?php if ( $trust_items || $trust_leader ) : ?>
			<ul class="b-depts__list">
				<?php foreach ( $trust_items as $trust_item ) : ?>
					<li class="b-depts__card">
						<span class="b-depts__icon" aria-hidden="true">
							<?php
							Icons::render(
								Icons::exists( $trust_item['icon'] ) ? $trust_item['icon'] : 'mail',
								array(
									'size'         => 22,
									'stroke_width' => 1.9,
								)
							);
							?>
						</span>
						<h3 class="b-depts__title"><?php echo esc_html( $trust_item['title'] ); ?></h3>
						<?php if ( '' !== trim( wp_strip_all_tags( $trust_item['text'] ) ) ) : ?>
							<div class="b-depts__text"><?php echo wp_kses_post( $trust_item['text'] ); ?></div>
						<?php endif; ?>
						<?php if ( $trust_item['links'] ) : ?>
							<div class="b-depts__links">
								<?php foreach ( $trust_item['links'] as list( $trust_href, $trust_label ) ) : ?>
									<a class="b-depts__link" href="<?php echo esc_url( $trust_href, array( 'mailto', 'tel', 'https' ) ); ?>"<?php echo str_starts_with( $trust_href, 'https' ) ? ' target="_blank" rel="noopener"' : ''; ?><?php echo $is_preview ? ' tabindex="-1"' : ''; ?>><?php echo esc_html( $trust_label ); ?></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
				<?php if ( $trust_leader ) : ?>
					<li class="b-depts__card b-depts__card--leader">
						<span class="b-depts__icon" aria-hidden="true">
							<?php
							Icons::render(
								'user',
								array(
									'size'         => 22,
									'stroke_width' => 1.9,
								)
							);
							?>
						</span>
						<h3 class="b-depts__title"><?php echo esc_html( $trust_leader['title'] ); ?></h3>
						<?php if ( '' !== $trust_leader['text'] ) : ?>
							<p class="b-depts__text"><?php echo esc_html( $trust_leader['text'] ); ?></p>
						<?php endif; ?>
						<?php if ( $trust_leader['links'] ) : ?>
							<div class="b-depts__links">
								<?php foreach ( $trust_leader['links'] as list( $trust_href, $trust_label ) ) : ?>
									<a class="b-depts__link" href="<?php echo esc_url( $trust_href, array( 'mailto' ) ); ?>"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>><?php echo esc_html( $trust_label ); ?></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</li>
				<?php endif; ?>
			</ul>
		<?php else : ?>
			<?php echo Blocks::editor_warning( __( 'Добавьте отделы в настройках блока.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в editor_warning(). ?>
		<?php endif; ?>
	</div>
</section>
