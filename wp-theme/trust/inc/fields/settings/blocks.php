<?php
/**
 * Настройки сайта → Блоки по умолчанию.
 *
 * Тексты общих компонентов. Сейчас — окно «Заказать обратный звонок» и согласие в формах;
 * тексты блоков «Обсудим ваш проект», «Задать вопрос» и оповещений добавятся на их шагах.
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
