<?php
/**
 * Видео блока «Видео»: адрес плеера по ссылке YouTube, Rutube или VK Видео и длительность
 * для микроразметки (ТЗ, п. 8.3: ссылка или файл MP4, плеер загружается только по клику).
 *
 * @package Trust
 */

namespace Trust;

defined( 'ABSPATH' ) || exit;

/**
 * Разбор ссылок на видео.
 */
final class Video {

	/**
	 * Плеер по ссылке на видео.
	 *
	 * YouTube — youtube-nocookie.com (без cookie до запуска), Rutube — rutube.ru/play/embed,
	 * VK Видео — vk.com/video_ext.php. Неизвестная ссылка — null (кнопка просто откроет её).
	 *
	 * @param string $url Ссылка на страницу видео.
	 * @return array{provider: string, embed: string}|null Площадка и адрес плеера (без автозапуска).
	 */
	public static function embed( string $url ): ?array {
		$url   = trim( $url );
		$parts = wp_parse_url( $url );
		if ( ! $parts || empty( $parts['host'] ) ) {
			return null;
		}
		$host = strtolower( preg_replace( '/^(www\.|m\.)/', '', $parts['host'] ) );
		$path = (string) ( $parts['path'] ?? '' );
		parse_str( (string) ( $parts['query'] ?? '' ), $query );

		// YouTube: watch?v=, youtu.be/, /embed/, /shorts/, /live/.
		$youtube = null;
		if ( 'youtu.be' === $host ) {
			$youtube = trim( $path, '/' );
		} elseif ( in_array( $host, array( 'youtube.com', 'youtube-nocookie.com', 'music.youtube.com' ), true ) ) {
			$youtube = is_string( $query['v'] ?? null ) ? $query['v'] : ( preg_match( '~^/(?:embed|shorts|live|v)/([^/?#]+)~', $path, $m ) ? $m[1] : null );
		}
		if ( $youtube && preg_match( '/^[A-Za-z0-9_-]{6,20}$/', $youtube ) ) {
			return array(
				'provider' => 'youtube',
				'embed'    => 'https://www.youtube-nocookie.com/embed/' . $youtube,
			);
		}

		// Rutube: /video/<id>/, /play/embed/<id>, /shorts/<id>/.
		if ( 'rutube.ru' === $host && preg_match( '~^/(?:video|play/embed|shorts)/([a-f0-9]{20,40})~', $path, $m ) ) {
			return array(
				'provider' => 'rutube',
				'embed'    => 'https://rutube.ru/play/embed/' . $m[1],
			);
		}

		// VK Видео: vk.com/video-123_456, vkvideo.ru/video-123_456, vk.com/video_ext.php?oid=&id=&hash=.
		if ( in_array( $host, array( 'vk.com', 'vk.ru', 'vkvideo.ru' ), true ) ) {
			if ( preg_match( '~^/video_ext\.php$~', $path ) && isset( $query['oid'], $query['id'] ) ) {
				$oid  = (string) $query['oid'];
				$id   = (string) $query['id'];
				$hash = is_string( $query['hash'] ?? null ) ? $query['hash'] : '';
			} elseif ( preg_match( '~video(-?\d+)_(\d+)~', $path . '?' . ( $parts['query'] ?? '' ), $m ) ) {
				$oid  = $m[1];
				$id   = $m[2];
				$hash = '';
			}
			if ( isset( $oid, $id ) && preg_match( '/^-?\d+$/', $oid ) && preg_match( '/^\d+$/', $id ) ) {
				return array(
					'provider' => 'vk',
					'embed'    => add_query_arg(
						array_filter(
							array(
								'oid'  => $oid,
								'id'   => $id,
								'hash' => preg_match( '/^[a-f0-9]+$/i', $hash ) ? $hash : '',
								'hd'   => '2',
							)
						),
						'https://vk.com/video_ext.php'
					),
				);
			}
		}
		return null;
	}

	/**
	 * Адрес плеера с автозапуском (плеер появляется по нажатию — видео сразу играет).
	 *
	 * @param string $embed Адрес плеера из embed().
	 */
	public static function autoplay( string $embed ): string {
		return add_query_arg( 'autoplay', '1', $embed );
	}

	/**
	 * Длительность «12:40» или «1:02:03» → ISO 8601 (PT12M40S); не разобрать — пусто.
	 *
	 * @param string $text Длительность.
	 */
	public static function duration( string $text ): string {
		if ( ! preg_match( '/^\s*(?:(\d{1,2}):)?(\d{1,2}):(\d{2})\s*$/', $text, $m ) || (int) $m[3] > 59 ) {
			return '';
		}
		$h = (int) $m[1];
		$i = (int) $m[2];
		$s = (int) $m[3];
		if ( ! $h && ! $i && ! $s ) {
			return '';
		}
		return 'PT' . ( $h ? $h . 'H' : '' ) . ( $i ? $i . 'M' : '' ) . ( $s ? $s . 'S' : '' );
	}
}
