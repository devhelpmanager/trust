<?php
/**
 * Блок «Видеоотзывы» (макет o-nas/otzyvy.html, секция «Видео»).
 *
 * Лавандовая секция, шапка, карточки в 3 колонки (уже 700 px — в одну): обложка 16:9 (без
 * обложки — тёмная заглушка из макета) с белой кнопкой воспроизведения и плашкой длительности,
 * ниже — о чём видео и автор (имя, должность, компания). Вся карточка — кнопка: плеер
 * загружается только по нажатию (assets/js/video-player.js), до этого — ни одного запроса к
 * YouTube, Rutube или VK; неизвестная ссылка открывается в новой вкладке. У видео с обложкой и
 * названием — микроразметка VideoObject (Trust\Schema\Graph::video()).
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Reviews;
use Trust\Schema\Graph;
use Trust\Video;

defined( 'ABSPATH' ) || exit;

$trust_manual = 'manual' === get_field( 'source' ) ? (array) get_field( 'reviews' ) : array();
$trust_count  = (int) get_field( 'count' );
$trust_ids    = Reviews::of_kind( 'video', $trust_manual, $trust_manual ? 12 : ( $trust_count > 0 ? min( 12, $trust_count ) : 3 ) );
$trust_title  = Blocks::uid( $block, 'title' );

if ( ! $trust_ids && ! $is_preview ) {
	return;
}
?>
<section class="b-vrev trust-bg-lavender"<?php echo Blocks::section_attrs( $trust_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в section_attrs(). ?>>
	<div class="b-vrev__inner">
		<?php Blocks::section_head( 'b-vrev__head', $trust_title ); ?>
		<?php if ( ! $trust_ids ) : ?>
			<?php echo Blocks::editor_warning( __( 'Нет опубликованных видеоотзывов: добавьте отзыв вида «Видео» в разделе «Отзывы».', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- текст экранирован в editor_warning(). ?>
		<?php else : ?>
			<ul class="b-vrev__list">
				<?php foreach ( $trust_ids as $trust_id ) : ?>
					<?php
					$trust_url      = trim( (string) get_post_meta( $trust_id, 'trust_video_url', true ) );
					$trust_file     = (int) get_post_meta( $trust_id, 'trust_video_file', true );
					$trust_file_url = '' === $trust_url && $trust_file ? (string) wp_get_attachment_url( $trust_file ) : '';
					$trust_embed    = '' !== $trust_url ? Video::embed( $trust_url ) : null;
					$trust_href     = '' !== $trust_url ? $trust_url : $trust_file_url;
					if ( '' === $trust_href ) {
						continue;
					}
					$trust_cover    = (int) get_post_meta( $trust_id, 'trust_video_cover', true );
					$trust_name     = trim( (string) get_post_meta( $trust_id, 'trust_video_title', true ) );
					$trust_name     = '' !== $trust_name ? $trust_name : get_the_title( $trust_id );
					$trust_who      = Reviews::author_line( $trust_id );
					$trust_duration = trim( (string) get_post_meta( $trust_id, 'trust_video_duration', true ) );
					$trust_iso      = Video::duration( $trust_duration );
					$trust_data     = $trust_embed ? ' data-video-embed="' . esc_url( Video::autoplay( $trust_embed['embed'] ) ) . '"' : ( '' !== $trust_file_url ? ' data-video-file="' . esc_url( $trust_file_url ) . '"' : '' );
					/* translators: %s: о чём видео. */
					$trust_label = sprintf( __( 'Смотреть видео: %s', 'trust' ), $trust_name );
					if ( ! $is_preview && $trust_cover ) {
						$trust_date = (string) get_post_meta( $trust_id, 'trust_date', true );
						$trust_time = $trust_date ? strtotime( $trust_date . ' 12:00:00' ) : false;
						Graph::video(
							array(
								'name'         => $trust_name,
								'description'  => $trust_who,
								'thumbnailUrl' => (string) wp_get_attachment_image_url( $trust_cover, 'full' ),
								'uploadDate'   => $trust_time ? wp_date( 'c', $trust_time ) : (string) get_post_time( 'c', true, $trust_id ),
								'duration'     => $trust_iso,
								'embedUrl'     => $trust_embed ? $trust_embed['embed'] : null,
								'contentUrl'   => '' !== $trust_file_url ? $trust_file_url : null,
							)
						);
					}
					?>
					<li>
						<figure class="b-vrev__card"<?php echo $trust_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано выше. ?> data-video-title="<?php echo esc_attr( $trust_name ); ?>">
							<div class="b-vrev__media" data-video-frame>
								<?php
								echo Blocks::image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image().
									$trust_cover,
									'medium_large',
									array(
										'class'   => 'b-vrev__cover',
										'loading' => 'lazy',
										'sizes'   => '(max-width: 700px) 92vw, 380px',
									),
									'',
									true
								);
								?>
								<a class="b-vrev__play" href="<?php echo esc_url( $trust_href ); ?>" target="_blank" rel="noopener" data-video-play aria-label="<?php echo esc_attr( $trust_label ); ?>"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>>
									<span class="b-vrev__btn" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path d="M8 5.5v13l11-6.5z"/></svg></span>
								</a>
								<?php if ( '' !== $trust_duration ) : ?>
									<span class="b-vrev__duration">
										<span class="screen-reader-text"><?php esc_html_e( 'Длительность:', 'trust' ); ?> </span>
										<?php if ( '' !== $trust_iso ) : ?>
											<time datetime="<?php echo esc_attr( $trust_iso ); ?>"><?php echo esc_html( $trust_duration ); ?></time>
										<?php else : ?>
											<?php echo esc_html( $trust_duration ); ?>
										<?php endif; ?>
									</span>
								<?php endif; ?>
							</div>
							<figcaption class="b-vrev__caption">
								<span class="b-vrev__title"><?php echo esc_html( $trust_name ); ?></span>
								<?php if ( '' !== $trust_who ) : ?>
									<span class="b-vrev__who"><?php echo esc_html( $trust_who ); ?></span>
								<?php endif; ?>
							</figcaption>
						</figure>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
