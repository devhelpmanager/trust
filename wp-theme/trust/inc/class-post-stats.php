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
 * - GET … — текущие счётчики (страница могла прийти из кеша), в том числе лайки комментариев.
 * - POST trust/v1/comments/{id}/like { liked } — лайк комментария (cookie trust_comment_likes,
 *   тот же лимит нажатий с IP).
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
	 * Cookie с комментариями, которые лайкнул браузер.
	 */
	public const COMMENT_COOKIE = 'trust_comment_likes';

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
		register_rest_route(
			'trust/v1',
			'/comments/(?P<id>\d+)/like',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'rest_comment_like' ),
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
			'views'    => Blog::views( $post ),
			'likes'    => Blog::likes( $post ),
			'liked'    => in_array( $post->ID, self::liked_ids(), true ),
			'comments' => (object) Comments::likes_map( $post ),
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
		$limited = self::limited();
		if ( $limited ) {
			return $limited;
		}
		$want  = (bool) $request['liked'];
		$state = self::toggle( self::liked_ids(), $post->ID, $want, Blog::likes( $post ) );
		update_post_meta( $post->ID, Blog::LIKES, $state['likes'] );
		self::set_liked_ids( $state['ids'] );
		return self::response(
			array(
				'views' => Blog::views( $post ),
				'likes' => $state['likes'],
				'liked' => $want,
			)
		);
	}

	/**
	 * POST comments/{id}/like: поставить или снять лайк комментария (одобренного, у опубликованной статьи).
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_comment_like( \WP_REST_Request $request ) {
		$comment = get_comment( (int) $request['id'] );
		$post    = $comment ? get_post( (int) $comment->comment_post_ID ) : null;
		if ( ! $comment || '1' !== (string) $comment->comment_approved || ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || post_password_required( $post ) ) {
			return new \WP_Error( 'trust_not_found', __( 'Комментарий не найден.', 'trust' ), array( 'status' => 404 ) );
		}
		$limited = self::limited();
		if ( $limited ) {
			return $limited;
		}
		$want  = (bool) $request['liked'];
		$id    = (int) $comment->comment_ID;
		$state = self::toggle( self::liked_ids( self::COMMENT_COOKIE ), $id, $want, Comments::likes( $comment ) );
		update_comment_meta( $id, Blog::LIKES, $state['likes'] );
		self::set_liked_ids( $state['ids'], self::COMMENT_COOKIE );
		return self::response(
			array(
				'likes' => $state['likes'],
				'liked' => $want,
			)
		);
	}

	/**
	 * Защита от накрутки: не больше LIKE_LIMIT нажатий «Нравится» (статьи и комментарии) с IP в час.
	 *
	 * @return \WP_Error|null Ошибка 429 или null — нажатие засчитано.
	 */
	private static function limited(): ?\WP_Error {
		$limit = 'trust_like_ip_' . md5( Protection::client_ip() );
		$count = (int) get_transient( $limit );
		if ( $count >= self::LIKE_LIMIT ) {
			return new \WP_Error( 'trust_too_many', __( 'Слишком много нажатий. Попробуйте позже.', 'trust' ), array( 'status' => 429 ) );
		}
		set_transient( $limit, $count + 1, HOUR_IN_SECONDS );
		return null;
	}

	/**
	 * Поставить или снять лайк: один с браузера (список в cookie), повтор не меняет число.
	 *
	 * @param int[] $ids   Что уже лайкнул браузер.
	 * @param int   $id    Статья или комментарий.
	 * @param bool  $want  Поставить (true) или снять.
	 * @param int   $likes Сейчас лайков.
	 * @return array{ids: int[], likes: int}
	 */
	private static function toggle( array $ids, int $id, bool $want, int $likes ): array {
		$has = in_array( $id, $ids, true );
		if ( $want && ! $has ) {
			++$likes;
			$ids[] = $id;
		} elseif ( ! $want && $has ) {
			$likes = max( 0, $likes - 1 );
			$ids   = array_values( array_diff( $ids, array( $id ) ) );
		}
		return array(
			'ids'   => $ids,
			'likes' => $likes,
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
	 * Статьи (или комментарии), лайкнутые этим браузером (cookie).
	 *
	 * @param string $cookie Имя cookie: статьи или комментарии.
	 * @return int[]
	 */
	public static function liked_ids( string $cookie = self::COOKIE ): array {
		$raw = isset( $_COOKIE[ $cookie ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie ] ) ) : '';
		return array_values( array_unique( array_filter( array_map( 'intval', explode( '.', $raw ) ) ) ) );
	}

	/**
	 * Записывает cookie лайков (год, последние 200).
	 *
	 * @param int[]  $ids    Статьи или комментарии.
	 * @param string $cookie Имя cookie.
	 */
	private static function set_liked_ids( array $ids, string $cookie = self::COOKIE ): void {
		$ids                = array_slice( array_values( array_unique( $ids ) ), -200 );
		$_COOKIE[ $cookie ] = implode( '.', $ids );
		if ( headers_sent() ) {
			return;
		}
		setcookie(
			$cookie,
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
