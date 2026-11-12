<?php
/**
 * Поля отзыва (ТЗ, раздел 5.3). Отзывы выводятся в блоках (шаги 4.7, 4.12), своей страницы нет.
 *
 * Поля для видео и письма видны только у отзыва своего вида. Что обязательно для вида
 * (текст, скан письма, ссылка или файл видео), проверяет Trust\Reviews.
 * Услуги — панель «Услуги» редактора.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_kind  = Trust\Fields::key( 'review', 'trust_kind' );
$trust_when  = static fn( string $operator, string $kind ): array => array(
	array(
		array(
			'field'    => $trust_kind,
			'operator' => $operator,
			'value'    => $kind,
		),
	),
);
$trust_image = array(
	'type'          => 'image',
	'return_format' => 'id',
	'preview_size'  => 'medium',
	'library'       => 'all',
	'mime_types'    => 'jpg, jpeg, png, webp',
);

return array(
	'title'    => __( 'Отзыв', 'trust' ),
	'location' => 'post_type',
	'fields'   => array(
		array(
			'name'          => 'trust_kind',
			'label'         => __( 'Вид отзыва', 'trust' ),
			'type'          => 'button_group',
			'choices'       => Trust\Reviews::KINDS,
			'default_value' => 'text',
			'layout'        => 'horizontal',
			'return_format' => 'value',
		),
		array(
			'name'         => 'trust_name',
			'label'        => __( 'Имя и фамилия', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 60,
			'required'     => 1,
			'instructions' => __( 'Заголовок отзыва можно не заполнять — подставятся имя и компания.', 'trust' ),
			'wrapper'      => array( 'width' => 50 ),
		),
		array(
			'name'      => 'trust_position',
			'label'     => __( 'Должность', 'trust' ),
			'type'      => 'text',
			'maxlength' => 60,
			'wrapper'   => array( 'width' => 50 ),
		),
		array(
			'name'      => 'trust_company',
			'label'     => __( 'Компания', 'trust' ),
			'type'      => 'text',
			'maxlength' => 80,
			'wrapper'   => array( 'width' => 50 ),
		),
		array(
			'name'           => 'trust_date',
			'label'          => __( 'Дата отзыва', 'trust' ),
			'type'           => 'date_picker',
			'display_format' => 'd.m.Y',
			'return_format'  => 'Y-m-d',
			'first_day'      => 1,
			'instructions'   => __( 'Пусто — дата публикации.', 'trust' ),
			'wrapper'        => array( 'width' => 50 ),
		),
		array_merge(
			$trust_image,
			array(
				'name'         => 'trust_photo',
				'label'        => __( 'Фото автора', 'trust' ),
				'instructions' => __( 'Квадрат, не меньше 200×200. Пусто — инициалы на цветном круге.', 'trust' ),
			)
		),
		array(
			'name'              => 'trust_text',
			'label'             => __( 'Текст отзыва', 'trust' ),
			'type'              => 'textarea',
			'rows'              => 6,
			'instructions'      => __( 'Обязательно для текстового отзыва; у письма — необязательно (цитата для блока «Слово клиенту»). 3–5 предложений.', 'trust' ),
			'conditional_logic' => $trust_when( '!=', 'video' ),
		),
		array(
			'name'              => 'trust_results',
			'label'             => __( 'Результаты', 'trust' ),
			'type'              => 'repeater',
			'layout'            => 'table',
			'max'               => 3,
			'button_label'      => __( 'Добавить результат', 'trust' ),
			'instructions'      => __( 'До 3 коротких строк под текстом: «+40% трафика», «за 6 месяцев».', 'trust' ),
			'conditional_logic' => $trust_when( '==', 'text' ),
			'sub_fields'        => array(
				array(
					'name'      => 'text',
					'label'     => __( 'Результат', 'trust' ),
					'type'      => 'text',
					'maxlength' => 30,
					'required'  => 1,
				),
			),
		),
		array_merge(
			$trust_image,
			array(
				'name'              => 'trust_letter',
				'label'             => __( 'Скан благодарственного письма', 'trust' ),
				'instructions'      => __( 'Обязательно для письма. У текстового отзыва — ссылка «Оригинал письма». Вертикальный лист, не меньше 1200 px по высоте.', 'trust' ),
				'conditional_logic' => $trust_when( '!=', 'video' ),
			)
		),
		array(
			'name'              => 'trust_video_url',
			'label'             => __( 'Ссылка на видео', 'trust' ),
			'type'              => 'url',
			'placeholder'       => 'https://',
			'instructions'      => __( 'YouTube, Rutube или VK Видео. Или загрузите файл ниже.', 'trust' ),
			'conditional_logic' => $trust_when( '==', 'video' ),
		),
		array(
			'name'              => 'trust_video_file',
			'label'             => __( 'Видеофайл', 'trust' ),
			'type'              => 'file',
			'return_format'     => 'id',
			'library'           => 'all',
			'mime_types'        => 'mp4, webm',
			'instructions'      => __( 'MP4 или WebM, если видео нет на видеохостинге.', 'trust' ),
			'conditional_logic' => $trust_when( '==', 'video' ),
		),
		array_merge(
			$trust_image,
			array(
				'name'              => 'trust_video_cover',
				'label'             => __( 'Обложка видео', 'trust' ),
				'instructions'      => __( '16:9, не меньше 1280×720.', 'trust' ),
				'conditional_logic' => $trust_when( '==', 'video' ),
			)
		),
		array(
			'name'              => 'trust_video_title',
			'label'             => __( 'О чём видео', 'trust' ),
			'type'              => 'text',
			'maxlength'         => 90,
			'placeholder'       => 'Как изменились заявки после редизайна',
			'conditional_logic' => $trust_when( '==', 'video' ),
			'wrapper'           => array( 'width' => 70 ),
		),
		array(
			'name'              => 'trust_video_duration',
			'label'             => __( 'Длительность', 'trust' ),
			'type'              => 'text',
			'maxlength'         => 8,
			'placeholder'       => '3:45',
			'conditional_logic' => $trust_when( '==', 'video' ),
			'wrapper'           => array( 'width' => 30 ),
		),
		array(
			'name'         => 'trust_source',
			'label'        => __( 'Ссылка на оригинал', 'trust' ),
			'type'         => 'url',
			'placeholder'  => 'https://',
			'instructions' => __( 'Например, этот отзыв на Яндекс Картах или в 2ГИС — ссылка «Оригинал».', 'trust' ),
		),
		array(
			'name'  => 'trust_form_section',
			'label' => __( 'Из формы на сайте (не публикуется)', 'trust' ),
			'type'  => 'accordion',
			'open'  => 0,
		),
		array(
			'name'    => 'trust_form_note',
			'label'   => '',
			'type'    => 'message',
			'message' => __( 'Заполняется, когда отзыв оставлен через форму «Оставьте отзыв». Контакты нужны, чтобы подтвердить отзыв перед публикацией; на сайт они не выводятся.', 'trust' ),
		),
		array(
			'name'    => 'trust_email',
			'label'   => __( 'Email автора', 'trust' ),
			'type'    => 'email',
			'wrapper' => array( 'width' => 50 ),
		),
		array(
			'name'    => 'trust_phone',
			'label'   => __( 'Телефон автора', 'trust' ),
			'type'    => 'text',
			'wrapper' => array( 'width' => 50 ),
		),
		array(
			'name'    => 'trust_rating',
			'label'   => __( 'Оценка', 'trust' ),
			'type'    => 'number',
			'min'     => 1,
			'max'     => 5,
			'wrapper' => array( 'width' => 20 ),
		),
		array(
			'name'     => 'trust_consent_at',
			'label'    => __( 'Согласие на обработку данных', 'trust' ),
			'type'     => 'text',
			'readonly' => 1,
			'wrapper'  => array( 'width' => 40 ),
		),
		array(
			'name'     => 'trust_publish_consent_at',
			'label'    => __( 'Согласие на распространение', 'trust' ),
			'type'     => 'text',
			'readonly' => 1,
			'wrapper'  => array( 'width' => 40 ),
		),
	),
);
