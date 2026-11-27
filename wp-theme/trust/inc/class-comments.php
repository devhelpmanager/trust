<?php
/**
 * Комментарии статей блога (ТЗ, раздел 13; макет seo-blog/statya.html).
 *
 * - Форма «Комментарий» — общий механизм форм сайта (Trust\Forms\Handler): те же проверки полей,
 *   ловушка для ботов, токен, ограничение частоты с IP, галочка согласия. Вместо заявки создаётся
 *   стандартный комментарий WordPress (wp_new_comment(): модерация, проверка повторов и частоты);
 *   email обязателен, на сайте не показывается. В «Заявки» комментарии не попадают.
 * - Ответы — до двух уровней: ответ на ответ становится ответом на исходный комментарий.
 * - Отправка в обход формы (wp-comments-post.php) для статей закрыта.
 * - Аватары — инициалы на цветном круге, Gravatar отключён («Показывать аватары» выключено всегда).
 * - Один раз при входе в админку включаются «Комментарий должен быть одобрен вручную» и
 *   древовидные комментарии на 2 уровня (дальше настройки «Обсуждения» — как решит владелец).
 *
 * @package Trust
 */

namespace Trust;

use Trust\Forms\Protection;

defined( 'ABSPATH' ) || exit;

/**
 * Форма, сохранение и список комментариев.
 */
final class Comments {

	/**
	 * Тип формы комментария.
	 */
	public const FORM = 'comment';

	/**
	 * Метаполе комментария: когда дано согласие на обработку персональных данных (и редакция документа).
	 */
	public const CONSENT = 'trust_consent';

	/**
	 * Сколько веток комментариев показано сразу (по умолчанию, как в макете; «Настройки сайта → Блог»).
	 */
	public const SHOWN = 3;

	/**
	 * Флаг: настройки «Обсуждения» по умолчанию уже записаны.
	 */
	private const DEFAULTS = 'trust_comment_defaults';

	/**
	 * Цвета круга с инициалами читателя: фон и текст (контраст текста не ниже 4,5 : 1).
	 */
	private const COLORS = array(
		array( '#EEE7FE', '#5B21B6' ),
		array( '#E2F3E9', '#0E5A31' ),
		array( '#FDECDC', '#8A3B00' ),
		array( '#E3EEFB', '#1E4E8C' ),
	);

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'trust_form_types', array( self::class, 'types' ) );
		add_filter( 'pre_option_show_avatars', '__return_zero' );
		add_action( 'pre_comment_on_post', array( self::class, 'direct' ) );
		add_action( 'admin_init', array( self::class, 'defaults' ) );
	}

	/**
	 * Описание формы «Комментарий».
	 *
	 * @param array $types Описания форм.
	 */
	public static function types( array $types ): array {
		$types[ self::FORM ] = array(
			'label'    => __( 'Комментарий', 'trust' ),
			'captcha'  => false,
			'lead'     => false,
			'save'     => array( self::class, 'save' ),
			'fields'   => array(
				'name'    => array(
					'label'    => __( 'Имя', 'trust' ),
					'type'     => 'text',
					'required' => true,
					'max'      => 100,
					'error'    => __( 'Укажите, как к вам обращаться', 'trust' ),
				),
				'email'   => array(
					'label'    => __( 'Email', 'trust' ),
					'type'     => 'email',
					'required' => true,
					'max'      => 100,
					'error'    => __( 'Укажите email, например name@mail.ru', 'trust' ),
				),
				'comment' => array(
					'label'    => __( 'Комментарий', 'trust' ),
					'type'     => 'textarea',
					'required' => true,
					'min'      => 2,
					'max'      => 3000,
					'error'    => __( 'Напишите комментарий', 'trust' ),
				),
			),
			'consents' => array(
				'consent' => array(
					'label' => Forms::consent()['label'],
					'error' => __( 'Отметьте согласие — без него комментарий не отправить', 'trust' ),
				),
			),
			'name'     => 'name',
			'contact'  => array( 'email' ),
		);
		return $types;
	}

	/**
	 * Сохраняет комментарий из формы (Trust\Forms\Handler, поле save описания формы).
	 *
	 * @param array $values   Проверенные поля: name, email, comment.
	 * @param array $consents Отмеченные согласия.
	 * @param array $params   Все поля формы: comment_post_ID, comment_parent.
	 * @return array|\WP_Error Данные ответа: comment (ID), approved (опубликован сразу), url.
	 */
	public static function save( array $values, array $consents, array $params ) {
		$post = get_post( absint( $params['comment_post_ID'] ?? 0 ) );
		if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || post_password_required( $post ) ) {
			return new \WP_Error( 'not_found', __( 'Статья не найдена.', 'trust' ), array( 'status' => 404 ) );
		}
		if ( ! comments_open( $post ) ) {
			return new \WP_Error( 'closed', __( 'Комментарии к этой статье закрыты.', 'trust' ), array( 'status' => 403 ) );
		}

		$parent = absint( $params['comment_parent'] ?? 0 );
		if ( $parent ) {
			$reply_to = get_comment( $parent );
			if ( ! $reply_to || (int) $reply_to->comment_post_ID !== $post->ID || '1' !== (string) $reply_to->comment_approved ) {
				return new \WP_Error( 'invalid', __( 'Комментарий, на который вы отвечаете, не найден.', 'trust' ), array( 'status' => 400 ) );
			}
			// Не глубже двух уровней: ответ на ответ — ответ исходному комментарию.
			if ( (int) $reply_to->comment_parent ) {
				$parent = (int) $reply_to->comment_parent;
			}
		}

		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$id    = wp_new_comment(
			wp_slash(
				array(
					'comment_post_ID'      => $post->ID,
					'comment_author'       => $values['name'],
					'comment_author_email' => $values['email'],
					'comment_author_url'   => '',
					'comment_content'      => $values['comment'],
					'comment_parent'       => $parent,
					'comment_type'         => 'comment',
					'user_id'              => get_current_user_id(),
					'comment_author_IP'    => Protection::client_ip(),
					'comment_agent'        => mb_substr( $agent, 0, 254 ),
				)
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			$messages = array(
				'comment_duplicate' => array( 409, __( 'Такой комментарий уже отправлен.', 'trust' ) ),
				'comment_flood'     => array( 429, __( 'Комментарии отправляются слишком часто. Подождите минуту и отправьте ещё раз.', 'trust' ) ),
			);
			$known    = $messages[ $id->get_error_code() ] ?? array( 500, __( 'Не удалось отправить комментарий. Попробуйте ещё раз.', 'trust' ) );
			return new \WP_Error( $id->get_error_code(), $known[1], array( 'status' => $known[0] ) );
		}

		$revision = Forms::consent_revision();
		add_comment_meta( (int) $id, self::CONSENT, current_time( 'mysql' ) . ( '' !== $revision ? ' · ' . $revision : '' ) );

		$approved = '1' === (string) get_comment( $id )->comment_approved;
		return array(
			'comment'  => (int) $id,
			'approved' => $approved,
			'url'      => $approved ? (string) get_comment_link( $id ) : '',
		);
	}

	/**
	 * Комментарии статьи принимаются только через форму на странице (wp-comments-post.php закрыт).
	 *
	 * @param int $post_id Запись.
	 */
	public static function direct( $post_id ): void {
		if ( 'post' === get_post_type( (int) $post_id ) ) {
			wp_die(
				esc_html__( 'Комментарии принимаются только через форму на странице статьи.', 'trust' ),
				esc_html__( 'Комментарий не принят', 'trust' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}
	}

	/**
	 * Настройки «Обсуждения» по умолчанию (один раз): модерация и ответы на 2 уровня.
	 */
	public static function defaults(): void {
		if ( get_option( self::DEFAULTS ) ) {
			return;
		}
		update_option( 'comment_moderation', 1 );
		update_option( 'thread_comments', 1 );
		update_option( 'thread_comments_depth', 2 );
		update_option( self::DEFAULTS, 1, false );
	}

	/**
	 * Сколько веток показано сразу (остальные — по кнопке «Показать ещё»).
	 */
	public static function shown(): int {
		$value = (int) Settings::get( 'blog_comments_shown', 0 );
		return $value > 0 ? $value : self::SHOWN;
	}

	/**
	 * Одобренные комментарии статьи: ветки (новые сверху), в каждой — ответы (по порядку).
	 *
	 * @param \WP_Post $post Статья.
	 * @return array<int, array{comment: \WP_Comment, replies: \WP_Comment[]}>
	 */
	public static function tree( \WP_Post $post ): array {
		static $cache = array();
		if ( isset( $cache[ $post->ID ] ) ) {
			return $cache[ $post->ID ];
		}
		$all  = get_comments(
			array(
				'post_id' => $post->ID,
				'status'  => 'approve',
				'type'    => 'comment',
				'orderby' => 'comment_date_gmt',
				'order'   => 'ASC',
			)
		);
		$byid = array();
		foreach ( $all as $comment ) {
			$byid[ (int) $comment->comment_ID ] = $comment;
		}
		$threads = array();
		$replies = array();
		foreach ( $all as $comment ) {
			// Корень ветки: ответы глубже второго уровня (например, из админки) — к исходному комментарию.
			$root = $comment;
			$seen = 0;
			while ( (int) $root->comment_parent && isset( $byid[ (int) $root->comment_parent ] ) && $seen++ < 50 ) {
				$root = $byid[ (int) $root->comment_parent ];
			}
			if ( $root === $comment ) {
				$threads[] = $comment;
			} else {
				$replies[ (int) $root->comment_ID ][] = $comment;
			}
		}
		$cache[ $post->ID ] = array_map(
			static fn( \WP_Comment $comment ): array => array(
				'comment' => $comment,
				'replies' => $replies[ (int) $comment->comment_ID ] ?? array(),
			),
			array_reverse( $threads )
		);
		return $cache[ $post->ID ];
	}

	/**
	 * Дата комментария по-русски: «6 ноября 2026».
	 *
	 * @param \WP_Comment $comment Комментарий.
	 */
	public static function date( \WP_Comment $comment ): string {
		$time = strtotime( $comment->comment_date_gmt . ' UTC' );
		return $time ? wp_date( 'j', $time ) . ' ' . Blog::MONTHS[ (int) wp_date( 'n', $time ) - 1 ] . ' ' . wp_date( 'Y', $time ) : '';
	}

	/**
	 * Сколько комментариев в ветках (с ответами).
	 *
	 * @param array $threads Ветки из tree().
	 */
	public static function count( array $threads ): int {
		return array_sum( array_map( static fn( array $thread ): int => 1 + count( $thread['replies'] ), $threads ) );
	}

	/**
	 * Лайки комментария.
	 *
	 * @param int|\WP_Comment $comment Комментарий.
	 */
	public static function likes( $comment ): int {
		$comment = get_comment( $comment );
		return $comment ? max( 0, (int) get_comment_meta( (int) $comment->comment_ID, Blog::LIKES, true ) ) : 0;
	}

	/**
	 * Лайки одобренных комментариев статьи: ID => число (для свежих чисел на странице из кеша).
	 *
	 * @param \WP_Post $post Статья.
	 * @return array<int, int>
	 */
	public static function likes_map( \WP_Post $post ): array {
		$map = array();
		foreach ( get_comments(
			array(
				'post_id' => $post->ID,
				'status'  => 'approve',
				'type'    => 'comment',
			)
		) as $comment ) {
			$map[ (int) $comment->comment_ID ] = self::likes( $comment );
		}
		return $map;
	}

	/**
	 * Аватар комментария: инициалы; автор статьи — фиолетовый круг, читатели — цвет по имени.
	 *
	 * @param \WP_Comment $comment Комментарий.
	 * @param \WP_Post    $post    Статья.
	 * @return array{initials: string, author: bool, bg: string, fg: string}
	 */
	public static function avatar( \WP_Comment $comment, \WP_Post $post ): array {
		$author = (int) $comment->user_id && (int) $comment->user_id === (int) $post->post_author;
		$color  = self::COLORS[ abs( crc32( (string) $comment->comment_author ) ) % count( self::COLORS ) ];
		return array(
			'initials' => Answers_List::initials( (string) $comment->comment_author ),
			'author'   => $author,
			'bg'       => $author ? '#6D28D9' : $color[0],
			'fg'       => $author ? '#FFFFFF' : $color[1],
		);
	}
}
