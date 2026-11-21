<?php
/**
 * Настройки сайта → Блоки по умолчанию.
 *
 * Тексты общих компонентов: окно «Заказать обратный звонок», согласие в формах, блоки «Обсудим
 * ваш проект», «Задать вопрос SEO-специалисту» и «Оповещение» (пустые поля блока берутся отсюда,
 * Trust\Block_Texts), блог.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_defaults = Trust\Callback::defaults();

/**
 * Текстовое поле окна звонка. Текст по умолчанию — серой подсказкой в пустом поле,
 * а не заполненным значением: тогда при сохранении настроек он не записывается в базу
 * и пустое поле всегда означает «текст темы по умолчанию».
 *
 * @param string $key   Ключ текста (Trust\Callback::defaults()).
 * @param string $label Подпись поля.
 * @param array  $extra Дополнительные параметры поля.
 */
$trust_field = static fn( string $key, string $label, array $extra = array() ): array => array_merge(
	array(
		'name'        => 'callback_' . $key,
		'label'       => $label,
		'type'        => 'text',
		'placeholder' => $trust_defaults[ $key ],
	),
	$extra
);

$trust_texts = Trust\Block_Texts::defaults();

/**
 * Текст блока по умолчанию: пустое поле — текст темы (серая подсказка).
 *
 * @param string $key   Имя поля (Trust\Block_Texts::defaults()).
 * @param string $label Подпись поля.
 * @param array  $extra Дополнительные параметры поля.
 */
$trust_text = static fn( string $key, string $label, array $extra = array() ): array => array_merge(
	array(
		'name'        => $key,
		'label'       => $label,
		'type'        => 'text',
		'placeholder' => str_replace( '{brand}', '[Бренд]', $trust_texts[ $key ] ),
	),
	$extra
);
$trust_area = static fn( int $rows, int $max ): array => array(
	'type'      => 'textarea',
	'rows'      => $rows,
	'new_lines' => '',
	'maxlength' => $max,
);
$trust_page = static fn( string $name, string $label, string $note ): array => array(
	'name'          => $name,
	'label'         => $label,
	'type'          => 'post_object',
	'post_type'     => array( 'page' ),
	'return_format' => 'id',
	'allow_null'    => 1,
	'ui'            => 1,
	'instructions'  => $note,
);

return array(
	'label'  => __( 'Блоки по умолчанию', 'trust' ),
	'fields' => array(
		array(
			'name'    => 'callback_heading',
			'label'   => __( 'Окно «Заказать обратный звонок»', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Открывается кнопками в шапке, мобильном меню и подвале. Поля: имя, телефон, согласие. Пустое поле — используется текст по умолчанию.', 'trust' ),
		),
		$trust_field(
			'button',
			__( 'Текст кнопки в шапке и подвале', 'trust' ),
			array(
				'maxlength'    => 40,
				'instructions' => __( 'До 30 символов: кнопка в шапке не должна переноситься.', 'trust' ),
			)
		),
		$trust_field( 'title', __( 'Заголовок окна', 'trust' ), array( 'maxlength' => 60 ) ),
		$trust_field(
			'text',
			__( 'Текст под заголовком', 'trust' ),
			array(
				'type'      => 'textarea',
				'rows'      => 2,
				'maxlength' => 200,
			)
		),
		$trust_field( 'name_label', __( 'Подпись поля «Имя»', 'trust' ), array( 'wrapper' => array( 'width' => 50 ) ) ),
		$trust_field( 'name_placeholder', __( 'Подсказка в поле «Имя»', 'trust' ), array( 'wrapper' => array( 'width' => 50 ) ) ),
		$trust_field( 'phone_label', __( 'Подпись поля «Телефон»', 'trust' ), array( 'wrapper' => array( 'width' => 50 ) ) ),
		$trust_field( 'phone_placeholder', __( 'Подсказка в поле «Телефон»', 'trust' ), array( 'wrapper' => array( 'width' => 50 ) ) ),
		$trust_field( 'submit', __( 'Текст кнопки отправки', 'trust' ), array( 'maxlength' => 40 ) ),
		$trust_field( 'success_title', __( 'Заголовок после отправки', 'trust' ), array( 'wrapper' => array( 'width' => 50 ) ) ),
		$trust_field( 'success_text', __( 'Текст после отправки', 'trust' ), array( 'wrapper' => array( 'width' => 50 ) ) ),

		array(
			'name'    => 'consent_heading',
			'label'   => __( 'Согласие в формах', 'trust' ),
			'type'    => 'message',
			'message' => sprintf(
				/* translators: %s: адрес настройки «Конфиденциальность». */
				__( 'В каждой форме с персональными данными есть галочка «Я даю согласие на обработку персональных данных» со ссылкой на страницу согласия и строка со ссылкой на политику конфиденциальности. Страница политики выбирается в <a href="%s">Настройки → Конфиденциальность</a>.', 'trust' ),
				esc_url( admin_url( 'options-privacy.php' ) )
			),
		),
		array(
			'name'          => 'consent_page',
			'label'         => __( 'Страница согласия на обработку персональных данных', 'trust' ),
			'type'          => 'post_object',
			'post_type'     => array( 'page' ),
			'return_format' => 'id',
			'allow_null'    => 1,
			'ui'            => 1,
			'instructions'  => __( 'Ссылка из галочки согласия во всех формах сайта.', 'trust' ),
		),
		array(
			'name'    => 'cta_heading',
			'label'   => __( 'Блок «Обсудим ваш проект»', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Тёмная карточка в конце страниц «О компании», «Команда», «Отзывы», «Портфолио» и кейсов. Пустые поля самого блока берутся отсюда, пустые здесь — тексты темы (серым). Телефон и Telegram — из вкладки «Контакты».', 'trust' ),
		),
		$trust_text( 'cta_title', __( 'Заголовок', 'trust' ), array( 'maxlength' => 80 ) ),
		$trust_text( 'cta_text', __( 'Текст', 'trust' ), $trust_area( 2, 300 ) ),
		$trust_text(
			'cta_button',
			__( 'Текст кнопки', 'trust' ),
			array(
				'maxlength' => 40,
				'wrapper'   => array( 'width' => 50 ),
			)
		),
		array_merge( $trust_page( 'cta_page', __( 'Страница кнопки', 'trust' ), __( 'Пусто — страница типа «Контакты»; нет её — окно «Заказать обратный звонок».', 'trust' ) ), array( 'wrapper' => array( 'width' => 50 ) ) ),

		array(
			'name'    => 'ask_heading',
			'label'   => __( 'Блок «Задать вопрос SEO-специалисту»', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Кнопка ведёт на страницу вопросов: задать вопрос можно только там.', 'trust' ),
		),
		$trust_text( 'ask_title', __( 'Заголовок', 'trust' ), array( 'maxlength' => 100 ) ),
		$trust_text( 'ask_text', __( 'Текст', 'trust' ), $trust_area( 2, 300 ) ),
		$trust_text(
			'ask_points',
			__( 'Пункты', 'trust' ),
			array_merge(
				$trust_area( 3, 200 ),
				array(
					'new_lines'    => '',
					'instructions' => __( 'Каждый с новой строки, до 4 пунктов.', 'trust' ),
				)
			)
		),
		$trust_text(
			'ask_button',
			__( 'Текст кнопки (вид «Кнопка»)', 'trust' ),
			array(
				'maxlength' => 40,
				'wrapper'   => array( 'width' => 50 ),
			)
		),
		array_merge( $trust_page( 'ask_page', __( 'Страница кнопки', 'trust' ), __( 'Пусто — страница типа «Вопросы SEO».', 'trust' ) ), array( 'wrapper' => array( 'width' => 50 ) ) ),
		$trust_text( 'ask_note', __( 'Примечание у кнопки', 'trust' ), array( 'maxlength' => 160 ) ),

		array(
			'name'    => 'notice_heading',
			'label'   => __( 'Блок «Оповещение»', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Плашка «Ответ — личное мнение автора, а не аудит сайта» на странице вопросов и в ответах. {brand} — название бренда.', 'trust' ),
		),
		$trust_text( 'notice_title', __( 'Заголовок', 'trust' ), array( 'maxlength' => 120 ) ),
		$trust_text( 'notice_text', __( 'Текст', 'trust' ), $trust_area( 3, 500 ) ),
		$trust_text(
			'notice_link_prefix',
			__( 'Текст перед ссылкой', 'trust' ),
			array(
				'maxlength' => 100,
				'wrapper'   => array( 'width' => 50 ),
			)
		),
		array(
			'name'          => 'notice_link',
			'label'         => __( 'Ссылка', 'trust' ),
			'type'          => 'link',
			'return_format' => 'array',
			'wrapper'       => array( 'width' => 50 ),
			'instructions'  => __( 'Например, страница технического аудита. Без текста ссылки — «Закажите технический аудит». Пусто — строки со ссылкой нет.', 'trust' ),
		),

		array(
			'name'    => 'blog_heading',
			'label'   => __( 'Блог', 'trust' ),
			'type'    => 'message',
			'message' => __( 'Время чтения статьи считается по числу слов текста.', 'trust' ),
		),
		array(
			'name'         => 'blog_read_speed',
			'label'        => __( 'Скорость чтения, слов в минуту', 'trust' ),
			'type'         => 'number',
			'min'          => 60,
			'max'          => 600,
			'placeholder'  => (string) Trust\Blog::READ_SPEED,
			'instructions' => __( 'Пусто — 180 слов в минуту. Время округляется вверх, минимум 1 минута.', 'trust' ),
		),
	),
);
