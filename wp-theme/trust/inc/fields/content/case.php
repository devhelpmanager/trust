<?php
/**
 * Поля кейса (ТЗ, раздел 5.3): данные для карточек портфолио, фильтров, первого экрана
 * и карточки проекта. Содержимое страницы кейса — блоки (шаг 4.17).
 *
 * Обложка для карточек — «Изображение записи», анонс карточки — «Отрывок»,
 * услуги и отрасль — панели «Услуги» и «Отрасли» редактора.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_image = array(
	'type'          => 'image',
	'return_format' => 'id',
	'preview_size'  => 'medium',
	'library'       => 'all',
	'mime_types'    => 'jpg, jpeg, png, webp',
);

return array(
	'title'    => __( 'Данные кейса', 'trust' ),
	'location' => 'post_type',
	'position' => 'side',
	'fields'   => array(
		array(
			'name'         => Trust\Blog::FEATURED,
			'label'        => __( 'Главный кейс', 'trust' ),
			'type'         => 'true_false',
			'ui'           => 1,
			'message'      => __( 'Крупная карточка в портфолио', 'trust' ),
			'instructions' => __( 'Первой в списке, пока фильтры не выбраны. Если отмечено несколько — самый новый из отмеченных.', 'trust' ),
		),
		array(
			'name'         => 'trust_client',
			'label'        => __( 'Клиент', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 80,
			'placeholder'  => '[Ремонт&Дизайн]',
			'instructions' => __( 'Название компании. Если клиент просил не раскрывать название — заглушка в квадратных скобках.', 'trust' ),
		),
		array(
			'name'         => 'trust_niche',
			'label'        => __( 'Чем занимается клиент', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 50,
			'placeholder'  => 'Ремонт квартир',
			'instructions' => __( 'Коротко, 2–4 слова: «Стоматологическая клиника». Выводится в карточке проекта, «Проектах в топе» и карусели кейсов. Пусто — отрасль из панели «Отрасли».', 'trust' ),
		),
		array(
			'name'        => 'trust_region',
			'label'       => __( 'Регион', 'trust' ),
			'type'        => 'text',
			'maxlength'   => 60,
			'placeholder' => '[Город]',
		),
		array(
			'name'           => 'trust_start',
			'label'          => __( 'Начало работ', 'trust' ),
			'type'           => 'date_picker',
			'display_format' => 'd.m.Y',
			'return_format'  => 'Y-m-d',
			'first_day'      => 1,
			'instructions'   => __( 'На сайте — месяц и год.', 'trust' ),
		),
		array(
			'name'           => 'trust_end',
			'label'          => __( 'Окончание работ', 'trust' ),
			'type'           => 'date_picker',
			'display_format' => 'd.m.Y',
			'return_format'  => 'Y-m-d',
			'first_day'      => 1,
			'instructions'   => __( 'Пусто — работы идут сейчас.', 'trust' ),
		),
		array(
			'name'         => 'trust_site',
			'label'        => __( 'Сайт клиента', 'trust' ),
			'type'         => 'url',
			'placeholder'  => 'https://',
			'instructions' => __( 'Адрес в рамке браузера на первом экране и кнопка «Открыть сайт клиента». Пусто — кнопки нет.', 'trust' ),
		),
		array(
			'name'         => 'trust_lead',
			'label'        => __( 'Вводный текст', 'trust' ),
			'type'         => 'textarea',
			'rows'         => 4,
			'maxlength'    => 400,
			'instructions' => __( 'Абзац под H1 на странице кейса: кто клиент и что сделали, 2–3 предложения. Анонс для карточек — в «Отрывке».', 'trust' ),
		),
		array_merge(
			$trust_image,
			array(
				'name'         => 'trust_screenshot',
				'label'        => __( 'Скриншот сайта', 'trust' ),
				'instructions' => __( 'Первый экран сайта клиента в рамке браузера: 16:10, не меньше 1600×1000. Alt — в медиатеке. Обложка карточек — «Изображение записи».', 'trust' ),
			)
		),
		array(
			'name'         => 'trust_results',
			'label'        => __( 'Результаты', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'max'          => 4,
			'button_label' => __( 'Добавить результат', 'trust' ),
			'instructions' => __( 'До 4: все — на первом экране кейса, первые два — в карточках портфолио и «Похожих кейсах».', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'        => 'value',
					'label'       => __( 'Значение', 'trust' ),
					'type'        => 'text',
					'maxlength'   => 16,
					'placeholder' => '+40%',
					'required'    => 1,
				),
				array(
					'name'        => 'label',
					'label'       => __( 'Подпись', 'trust' ),
					'type'        => 'text',
					'maxlength'   => 40,
					'placeholder' => 'визитов из поиска',
					'required'    => 1,
				),
			),
		),
		array(
			'name'  => 'trust_positions_section',
			'label' => __( 'Позиции в поиске', 'trust' ),
			'type'  => 'accordion',
			'open'  => 0,
		),
		array(
			'name'         => 'trust_positions',
			'label'        => __( 'Запросы', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'max'          => 6,
			'button_label' => __( 'Добавить запрос', 'trust' ),
			'instructions' => __( 'Для блока «Наши проекты в топе»: 3–5 запросов, по которым сайт на первой странице. Пусто — кейс в блоке не показывается.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'      => 'query',
					'label'     => __( 'Запрос', 'trust' ),
					'type'      => 'text',
					'maxlength' => 60,
					'required'  => 1,
				),
				array(
					'name'  => 'yandex',
					'label' => __( 'Позиция в Яндексе', 'trust' ),
					'type'  => 'number',
					'min'   => 1,
					'max'   => 100,
				),
				array(
					'name'  => 'google',
					'label' => __( 'Позиция в Google', 'trust' ),
					'type'  => 'number',
					'min'   => 1,
					'max'   => 100,
				),
			),
		),
		array(
			'name'        => 'trust_positions_region',
			'label'       => __( 'Регион проверки', 'trust' ),
			'type'        => 'text',
			'maxlength'   => 60,
			'placeholder' => 'Москва',
		),
		array(
			'name'           => 'trust_positions_date',
			'label'          => __( 'Дата проверки', 'trust' ),
			'type'           => 'date_picker',
			'display_format' => 'd.m.Y',
			'return_format'  => 'Y-m-d',
			'first_day'      => 1,
		),
		array(
			'name'  => 'trust_team_section',
			'label' => __( 'Команда и отзыв', 'trust' ),
			'type'  => 'accordion',
			'open'  => 0,
		),
		array(
			'name'         => 'trust_team',
			'label'        => __( 'Команда проекта', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'button_label' => __( 'Добавить сотрудника', 'trust' ),
			'instructions' => __( 'Блок «Кто работал над проектом» и строка «Команда проекта — N человек» в карточке проекта.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'          => 'member',
					'label'         => __( 'Сотрудник', 'trust' ),
					'type'          => 'post_object',
					'post_type'     => array( 'team' ),
					'return_format' => 'id',
					'ui'            => 1,
					'required'      => 1,
				),
				array(
					'name'         => 'role',
					'label'        => __( 'Роль в проекте', 'trust' ),
					'type'         => 'text',
					'maxlength'    => 60,
					'instructions' => __( 'Пусто — должность сотрудника.', 'trust' ),
				),
			),
		),
		array(
			'name'          => 'trust_review',
			'label'         => __( 'Отзыв клиента', 'trust' ),
			'type'          => 'post_object',
			'post_type'     => array( 'review' ),
			'return_format' => 'id',
			'allow_null'    => 1,
			'ui'            => 1,
			'instructions'  => __( 'Необязательно. Блок «Слово клиенту» на странице кейса и ссылка «Кейс проекта» у отзыва.', 'trust' ),
		),
	),
);
