<?php
/**
 * Блок «Видео» (ТЗ, п. 8.3; «Видеоразбор» в макете статьи seo-blog/statya.html).
 *
 * Обложка 16:9 со скруглением, по центру — белая круглая кнопка воспроизведения, справа внизу —
 * длительность, под видео — подпись. Плеер загружается только по нажатию (view.js): YouTube
 * (youtube-nocookie.com), Rutube, VK Видео — встроенный плеер, MP4 — <video>. Без JavaScript
 * кнопка — ссылка на видео (или файл). Микроразметка VideoObject (Trust\Schema\Graph::video()).
 *
 * На странице — белая секция (по ширине текста 960 px или во всю ширину 1200 px), в статье
 * и ответе — рисунок в тексте.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;
use Trust\Schema\Graph;
use Trust\Video;

defined( 'ABSPATH' ) || exit;

$trust_is_file  = 'file' === get_field( 'source' );
$trust_url      = $trust_is_file ? '' : trim( (string) get_field( 'url' ) );
$trust_file     = $trust_is_file ? (int) get_field( 'file' ) : 0;
$trust_file_url = $trust_file ? (string) wp_get_attachment_url( $trust_file ) : '';
$trust_poster   = (int) get_field( 'poster' );
$trust_title    = trim( (string) get_field( 'title' ) );
$trust_caption  = trim( (string) get_field( 'caption' ) );
$trust_duration = trim( (string) get_field( 'duration' ) );
$trust_iso      = Video::duration( $trust_duration );
$trust_embed    = '' !== $trust_url ? Video::embed( $trust_url ) : null;
$trust_href     = $trust_is_file ? $trust_file_url : $trust_url;
$trust_inline   = 'page' !== get_post_type( (int) $post_id );
$trust_wide     = ! $trust_inline && 'wide' === get_field( 'width' );
$trust_img      = Blocks::image(
	$trust_poster,
	'full',
	array(
		'class'   => 'b-video__poster',
		'loading' => 'lazy',
		'sizes'   => $trust_wide ? '(max-width: 1304px) 92vw, 1200px' : '(max-width: 1043px) 92vw, 960px',
	),
	'',
	true
);

if ( ( '' === $trust_href || '' === $trust_img ) && ! $is_preview ) {
	return;
}

/* translators: %s: название видео. */
$trust_label = '' !== $trust_title ? sprintf( __( 'Смотреть видео: %s', 'trust' ), $trust_title ) : __( 'Смотреть видео', 'trust' );

if ( ! $is_preview && '' !== $trust_title && $trust_poster ) {
	$trust_date = (string) get_field( 'date' );
	$trust_time = $trust_date ? strtotime( $trust_date . ' 12:00:00' ) : false;
	Graph::video(
		array(
			'name'         => $trust_title,
			'description'  => $trust_caption,
			'thumbnailUrl' => (string) wp_get_attachment_image_url( $trust_poster, 'full' ),
			'uploadDate'   => $trust_time ? wp_date( 'c', $trust_time ) : (string) get_post_time( 'c', true, (int) $post_id ),
			'duration'     => $trust_iso,
			'embedUrl'     => $trust_embed ? $trust_embed['embed'] : null,
			'contentUrl'   => $trust_is_file ? $trust_file_url : null,
		)
	);
}

$trust_figure = static function () use ( $trust_inline, $trust_wide, $trust_embed, $trust_is_file, $trust_file_url, $trust_href, $trust_img, $trust_label, $trust_title, $trust_duration, $trust_iso, $trust_caption, $is_preview ): void {
	$classes = 'b-video' . ( $trust_inline ? ' b-video--inline' : '' ) . ( $trust_wide ? ' b-video--wide' : '' );
	$data    = '';
	if ( $trust_embed ) {
		$data = ' data-video-embed="' . esc_url( Video::autoplay( $trust_embed['embed'] ) ) . '"';
	} elseif ( $trust_is_file && '' !== $trust_file_url ) {
		$data = ' data-video-file="' . esc_url( $trust_file_url ) . '"';
	}
	?>
	<figure class="<?php echo esc_attr( $classes ); ?>"<?php echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано выше. ?> data-video-title="<?php echo esc_attr( $trust_title ); ?>">
		<div class="b-video__frame">
			<?php echo $trust_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image(). ?>
			<?php if ( '' !== $trust_href ) : ?>
				<a class="b-video__play" href="<?php echo esc_url( $trust_href ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $trust_label ); ?>"<?php echo $is_preview ? ' tabindex="-1"' : ''; ?>>
					<?php Icons::render( 'play', array( 'size' => 30 ) ); ?>
				</a>
			<?php else : ?>
				<span class="b-video__play" aria-hidden="true"><?php Icons::render( 'play', array( 'size' => 30 ) ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $trust_duration ) : ?>
				<span class="b-video__duration">
					<span class="screen-reader-text"><?php esc_html_e( 'Длительность:', 'trust' ); ?> </span>
					<?php if ( '' !== $trust_iso ) : ?>
						<time datetime="<?php echo esc_attr( $trust_iso ); ?>"><?php echo esc_html( $trust_duration ); ?></time>
					<?php else : ?>
						<?php echo esc_html( $trust_duration ); ?>
					<?php endif; ?>
				</span>
			<?php endif; ?>
		</div>
		<?php if ( '' !== $trust_caption ) : ?>
			<figcaption class="b-video__caption"><?php echo esc_html( $trust_caption ); ?></figcaption>
		<?php endif; ?>
	</figure>
	<?php
};

if ( $is_preview && ( '' === $trust_href || '' === $trust_img ) ) {
	echo Blocks::editor_warning( __( 'Укажите видео (ссылку или файл) и обложку — без них блок на сайте не выводится.', 'trust' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- текст экранирован в editor_warning().
}
?>
<?php if ( $trust_inline ) : ?>
	<?php $trust_figure(); ?>
<?php else : ?>
	<section class="b-video-sec trust-bg-white">
		<?php $trust_figure(); ?>
	</section>
<?php endif; ?>
