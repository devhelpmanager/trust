<?php
/**
 * Блок «Первый экран контактов» (макет o-nas/kontakty.html).
 *
 * Крошки над тёмной карточкой; в карточке плашка, H1 (поле «H1» в SEO страницы или заголовок),
 * текст и карточки связи. Номер, адрес, Telegram и WhatsApp — из «Настроек сайта → Контакты»
 * (Trust\Contacts), подписи и порядок — поля блока.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Breadcrumbs;
use Trust\Contacts;
use Trust\Icons;
use Trust\Seo;

defined( 'ABSPATH' ) || exit;

$trust_h1       = Seo::h1( (int) $post_id );
$trust_badge    = trim( (string) get_field( 'badge' ) );
$trust_lead     = trim( (string) get_field( 'lead' ) );
$trust_title_id = Blocks::uid( $block, 'title' );

// Каналы: что показать (по умолчанию — все четыре) и данные из настроек.
$trust_rows = array_values( array_filter( (array) get_field( 'channels' ), 'is_array' ) );
if ( ! $trust_rows ) {
	$trust_rows = array_map( static fn( $channel ) => array( 'channel' => $channel ), array( 'phone', 'email', 'telegram', 'whatsapp' ) );
}
$trust_phone    = Contacts::main_phone();
$trust_email    = Contacts::main_email();
$trust_telegram = Contacts::telegram();
$trust_whatsapp = Contacts::whatsapp();
$trust_data     = array(
	'phone'    => $trust_phone ? array( __( 'Телефон', 'trust' ), 'phone', $trust_phone['number'], $trust_phone['href'], Contacts::hours(), false ) : null,
	'email'    => $trust_email ? array( __( 'Email', 'trust' ), 'mail', $trust_email['email'], $trust_email['href'], __( 'Для вопросов и заявок', 'trust' ), false ) : null,
	'telegram' => $trust_telegram ? array( 'Telegram', 'send', $trust_telegram['handle'], $trust_telegram['url'], __( 'Написать в Telegram', 'trust' ), true ) : null,
	'whatsapp' => $trust_whatsapp ? array( 'WhatsApp', 'chat', '' !== $trust_whatsapp['number'] ? $trust_whatsapp['number'] : 'WhatsApp', $trust_whatsapp['url'], __( 'Написать в WhatsApp', 'trust' ), true ) : null,
);
$trust_cards    = array();
foreach ( $trust_rows as $trust_row ) {
	$trust_info = $trust_data[ $trust_row['channel'] ?? '' ] ?? null;
	if ( ! $trust_info ) {
		continue;
	}
	$trust_label   = trim( (string) ( $trust_row['label'] ?? '' ) );
	$trust_note    = trim( (string) ( $trust_row['note'] ?? '' ) );
	$trust_cards[] = array(
		'label'    => '' !== $trust_label ? $trust_label : $trust_info[0],
		'icon'     => $trust_info[1],
		'value'    => $trust_info[2],
		'href'     => $trust_info[3],
		'note'     => '' !== $trust_note ? $trust_note : $trust_info[4],
		'external' => $trust_info[5],
	);
}
?>
<div class="b-hero-contacts">
	<div class="hero-crumbs">
		<?php
		if ( $is_preview ) {
			Breadcrumbs::render_preview( (int) $post_id );
		} else {
			Breadcrumbs::render();
		}
		?>
	</div>
	<section class="hero-sec" aria-labelledby="<?php echo esc_attr( $trust_title_id ); ?>">
		<div class="hero-box">
			<span class="hero-circle hero-circle--top" aria-hidden="true"></span>
			<span class="hero-circle hero-circle--bottom" aria-hidden="true"></span>
			<div class="hero-in b-hero-contacts__in">
				<div class="b-hero-contacts__head">
					<?php if ( '' !== $trust_badge ) : ?>
						<span class="hero-badge"><?php echo esc_html( $trust_badge ); ?></span>
					<?php endif; ?>
					<h1 class="hero-title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( '' !== $trust_h1 ? $trust_h1 : __( 'Заголовок страницы', 'trust' ) ); ?></h1>
					<?php if ( '' !== $trust_lead ) : ?>
						<p class="hero-lead b-hero-contacts__lead"><?php echo esc_html( $trust_lead ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( $trust_cards ) : ?>
					<ul class="b-hero-contacts__list" aria-label="<?php esc_attr_e( 'Быстрые способы связи', 'trust' ); ?>">
						<?php foreach ( $trust_cards as $trust_card ) : ?>
							<li>
								<a class="b-hero-contacts__card" href="<?php echo esc_url( $trust_card['href'] ); ?>"<?php echo $trust_card['external'] ? ' target="_blank" rel="noopener"' : ''; ?>>
									<span class="b-hero-contacts__top">
										<span class="b-hero-contacts__icon" aria-hidden="true"><?php Icons::render( $trust_card['icon'], array( 'size' => 20 ) ); ?></span>
										<span class="b-hero-contacts__label"><?php echo esc_html( $trust_card['label'] ); ?></span>
									</span>
									<span class="b-hero-contacts__value"><?php echo esc_html( $trust_card['value'] ); ?></span>
									<?php if ( '' !== $trust_card['note'] ) : ?>
										<span class="b-hero-contacts__note"><?php echo esc_html( $trust_card['note'] ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	</section>
</div>
