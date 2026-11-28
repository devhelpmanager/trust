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
 * - Ответ из админки («Комментарии → Ответить», комментарии в записи, «Активность» в консоли):
 *   список «Ответить от имени» — автор статьи (по умолчанию) или любой пользователь, который
 *   пишет статьи (assets/js/admin-reply-as.js). Ответ публикуется сразу; у автора статьи — плашка
 *   «Автор». Имя — сотрудника, связанного с пользователем, иначе «Отображать как».
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
	 * Значение «Автор статьи» в списке «Ответить от имени».
	 */
	public const POST_AUTHOR = 'post_author';

	/**
	 * Ответ из админки от имени другого пользователя — публикуется сразу.
	 *
	 * @var bool
	 */
	private static bool $reply_as = false;

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
		add_action( 'admin_enqueue_scripts', array( self::class, 'admin_assets' ) );
		add_filter( 'preprocess_comment', array( self::class, 'reply_as' ), 5 );
		add_filter( 'pre_comment_approved', array( self::class, 'reply_as_approved' ), 20 );
	}

	/**
	 * Имя и email пользователя для комментария: сотрудник, связанный с пользователем, иначе
	 * «Отображать как».
	 *
	 * @param \WP_User $user Пользователь.
	 * @return array{name: string, email: string}
	 */
	public static function person( \WP_User $user ): array {
		$member = Team::for_user( $user->ID );
		return array(
			'name'  => $member ? get_the_title( $member ) : (string) $user->display_name,
			'email' => (string) $user->user_email,
		);
	}

	/**
	 * Кто может быть автором ответа из админки: пользователи, которые пишут статьи.
	 *
	 * @return \WP_User[]
	 */
	public static function reply_users(): array {
		return get_users(
			array(
				'capability' => array( 'edit_posts' ),
				'orderby'    => 'display_name',
				'number'     => 200,
			)
		);
	}

	/**
	 * Скрипт «Ответить от имени» — там, где в админке есть форма ответа на комментарий.
	 *
	 * @param string $hook Экран.
	 */
	public static function admin_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'edit-comments.php', 'post.php', 'index.php' ), true ) || ! current_user_can( 'moderate_comments' ) ) {
			return;
		}
		$options = array(
			array(
				'value' => self::POST_AUTHOR,
				'label' => __( 'Автор статьи', 'trust' ),
			),
		);
		foreach ( self::reply_users() as $user ) {
			$person    = self::person( $user );
			$member    = Team::for_user( $user->ID );
			$position  = $member ? trim( (string) get_post_meta( $member->ID, 'trust_position', true ) ) : '';
			$options[] = array(
				'value' => (string) $user->ID,
				'label' => $person['name'] . ( '' !== $position ? ' — ' . $position : '' ) . ' (' . $user->user_login . ( get_current_user_id() === $user->ID ? ', ' . __( 'вы', 'trust' ) : '' ) . ')',
			);
		}
		wp_enqueue_script( 'trust-admin-reply-as', Assets::url( 'assets/js/admin-reply-as.js' ), array( 'jquery' ), Assets::version( 'assets/js/admin-reply-as.js' ), true );
		wp_add_inline_script(
			'trust-admin-reply-as',
			'window.trustReplyAs = ' . wp_json_encode(
				array(
					'label'   => __( 'Ответить от имени', 'trust' ),
					'options' => $options,
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Ответ из админки: автор — выбранный в «Ответить от имени» пользователь.
	 *
	 * @param array $data Данные комментария.
	 */
	public static function reply_as( array $data ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce ответа проверяется ниже.
		$action = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
		if ( ! wp_doing_ajax() || 'replyto-comment' !== $action || empty( $_POST['trust_reply_as'] ) ) {
			return $data;
		}
		$nonce  = isset( $_POST['_ajax_nonce-replyto-comment'] ) ? sanitize_text_field( wp_unslash( $_POST['_ajax_nonce-replyto-comment'] ) ) : '';
		$choice = sanitize_key( wp_unslash( $_POST['trust_reply_as'] ) );
		// phpcs:enable
		if ( ! wp_verify_nonce( $nonce, 'replyto-comment' ) || ! current_user_can( 'moderate_comments' ) ) {
			return $data;
		}
		$post = get_post( (int) ( $data['comment_post_ID'] ?? 0 ) );
		$user = $post ? get_userdata( self::POST_AUTHOR === $choice ? (int) $post->post_author : absint( $choice ) ) : false;
		if ( ! $user || ! user_can( $user, 'edit_posts' ) ) {
			return $data;
		}
		$person                       = self::person( $user );
		$data['user_id']              = $user->ID;
		$data['comment_author']       = wp_slash( $person['name'] );
		$data['comment_author_email'] = wp_slash( $person['email'] );
		$data['comment_author_url']   = '';
		self::$reply_as               = true;
		return $data;
	}

	/**
	 * Ответ из админки от имени другого пользователя публикуется сразу.
	 *
	 * @param int|string|\WP_Error $approved Статус.
	 * @return int|string|\WP_Error
	 */
	public static function reply_as_approved( $approved ) {
		return self::$reply_as && ! is_wp_error( $approved ) && 'spam' !== $approved && 'trash' !== $approved ? 1 : $approved;
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
