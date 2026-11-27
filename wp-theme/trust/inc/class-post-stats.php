<?php
/**
 * Просмотры и лайки статей (ТЗ, раздел 13).
 *
 * REST trust/v1/posts/{id}:
 * - POST …/view — +1 просмотр. Скрипт статьи шлёт его после загрузки, не чаще раза в сутки
 *   с браузера (метка в localStorage); боты не учитываются; сервер тоже не считает повтор с
 *   того же IP в течение суток. Кеширование страниц подсчёт не ломает — счёт идёт запросом.
 * - POST …/like { liked: true|false } — поставить или снять лайк. Один лайк с браузера: метка
 *   в localStorage и cookie trust_likes (номера статей); повторное нажатие снимает лайк.
 *   Защита от накрутки — не больше LIKE_LIMIT нажатий с IP в час.
 * - GET … — текущие счётчики (страница могла прийти из кеша).
 *
 * @package Trust
 */

namespace Trust;

use Trust\Forms\Protection;

defined( 'ABSPATH' ) || exit;

/**
 * Счётчики статей.
 */
final class Post_Stats {

	/**
	 * Cookie со статьями, которые лайкнул браузер.
	 */
	public const COOKIE = 'trust_likes';

	/**
	 * Нажатий «Нравится» с одного IP в час.
	 */
	public const LIKE_LIMIT = 30;

	/**
	 * Признаки ботов в User-Agent: поисковые роботы, проверки, браузеры без окна.
	 */
	private const BOTS = '~bot|crawl|spider|slurp|preview|fetch|monitor|lighthouse|headless|python|curl|wget|httpclient~i';

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
	}

	/**
	 * REST-маршруты.
	 */
	public static function routes(): void {
		$id = array(
			'type'     => 'integer',
			'required' => true,
		);
		register_rest_route(
			'trust/v1',
			'/posts/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest_stats' ),
				'permission_callback' => '__return_true',
				'args'                => array( 'id' => $id ),
			)
		);
		register_rest_route(
			'trust/v1',
			'/posts/(?P<id>\d+)/view',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'rest_view' ),
				'permission_callback' => '__return_true',
				'args'                => array( 'id' => $id ),
			)
		);
		register_rest_route(
			'trust/v1',
			'/posts/(?P<id>\d+)/like',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'rest_like' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id'    => $id,
					'liked' => array(
						'type'     => 'boolean',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Опубликованная статья или ошибка 404.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_Post|\WP_Error
	 */
	private static function post( \WP_REST_Request $request ) {
		$post = get_post( (int) $request['id'] );
		if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || post_password_required( $post ) ) {
			return new \WP_Error( 'trust_not_found', __( 'Статья не найдена.', 'trust' ), array( 'status' => 404 ) );
		}
		return $post;
	}

	/**
	 * Счётчики статьи для ответа.
	 *
	 * @param \WP_Post $post Статья.
	 */
	private static function stats( \WP_Post $post ): array {
		return array(
			'views' => Blog::views( $post ),
			'likes' => Blog::likes( $post ),
			'liked' => in_array( $post->ID, self::liked_ids(), true ),
		);
	}

	/**
	 * GET: счётчики.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_stats( \WP_REST_Request $request ) {
		$post = self::post( $request );
		return is_wp_error( $post ) ? $post : self::response( self::stats( $post ) );
	}

	/**
	 * POST view: +1 просмотр (не бот, не повтор с IP за сутки).
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_view( \WP_REST_Request $request ) {
		$post = self::post( $request );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		$counted = false;
		if ( ! self::is_bot( (string) $request->get_header( 'user_agent' ) ) ) {
			$key = 'trust_view_' . md5( Protection::client_ip() . '|' . $post->ID );
			if ( ! get_transient( $key ) ) {
				set_transient( $key, 1, DAY_IN_SECONDS );
				update_post_meta( $post->ID, Blog::VIEWS, Blog::views( $post ) + 1 );
				$counted = true;
			}
		}
		return self::response( array( 'counted' => $counted ) + self::stats( $post ) );
	}

	/**
	 * POST like: поставить или снять лайк.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_like( \WP_REST_Request $request ) {
		$post = self::post( $request );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		$limit = 'trust_like_ip_' . md5( Protection::client_ip() );
		$count = (int) get_transient( $limit );
		if ( $count >= self::LIKE_LIMIT ) {
			return new \WP_Error( 'trust_too_many', __( 'Слишком много нажатий. Попробуйте позже.', 'trust' ), array( 'status' => 429 ) );
		}
		set_transient( $limit, $count + 1, HOUR_IN_SECONDS );

		$want  = (bool) $request['liked'];
		$ids   = self::liked_ids();
		$has   = in_array( $post->ID, $ids, true );
		$likes = Blog::likes( $post );
		if ( $want && ! $has ) {
			++$likes;
			$ids[] = $post->ID;
		} elseif ( ! $want && $has ) {
			$likes = max( 0, $likes - 1 );
			$ids   = array_values( array_diff( $ids, array( $post->ID ) ) );
		}
		update_post_meta( $post->ID, Blog::LIKES, $likes );
		self::set_liked_ids( $ids );
		return self::response(
			array(
				'views' => Blog::views( $post ),
				'likes' => $likes,
				'liked' => $want,
			)
		);
	}

	/**
	 * Ответ без кеширования.
	 *
	 * @param array $data Данные.
	 */
	private static function response( array $data ): \WP_REST_Response {
		$response = new \WP_REST_Response( $data );
		$response->header( 'Cache-Control', 'no-store' );
		return $response;
	}

	/**
	 * Бот ли это (по User-Agent; пустой — тоже).
	 *
	 * @param string $agent User-Agent.
	 */
	public static function is_bot( string $agent ): bool {
		return '' === trim( $agent ) || (bool) preg_match( self::BOTS, $agent );
	}

	/**
	 * Статьи, лайкнутые этим браузером (cookie).
	 *
	 * @return int[]
	 */
	public static function liked_ids(): array {
		$raw = isset( $_COOKIE[ self::COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) : '';
		return array_values( array_unique( array_filter( array_map( 'intval', explode( '.', $raw ) ) ) ) );
	}

	/**
	 * Записывает cookie лайков (год, последние 200 статей).
	 *
	 * @param int[] $ids Статьи.
	 */
	private static function set_liked_ids( array $ids ): void {
		$ids                     = array_slice( array_values( array_unique( $ids ) ), -200 );
		$_COOKIE[ self::COOKIE ] = implode( '.', $ids );
		if ( headers_sent() ) {
			return;
		}
		setcookie(
			self::COOKIE,
			implode( '.', $ids ),
			array(
				'expires'  => $ids ? time() + YEAR_IN_SECONDS : time() - HOUR_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'secure'   => is_ssl(),
				'httponly' => false,
				'samesite' => 'Lax',
			)
		);
	}
}
