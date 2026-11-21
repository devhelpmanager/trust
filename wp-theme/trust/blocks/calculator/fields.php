<?php
/**
 * Поля блока «Калькулятор стоимости» (ТЗ, п. 8.3.1; макеты holst-blokov/Calculator,
 * seo-bloki/13-kalkulyator.html). Все тексты, варианты, надбавки и коэффициенты — поля;
 * варианты шагов по умолчанию — из макета (Trust\Forms\Calculator), цены — примерные.
 *
 * @package Trust
 */

use Trust\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Раскрывающийся раздел настроек.
 *
 * @param string $name  Имя.
 * @param string $label Заголовок.
 */
$trust_section = static fn( string $name, string $label ): array => array(
	'name'  => $name,
	'label' => $label,
	'type'  => 'accordion',
	'open'  => 0,
);

/**
 * Число (цена в рублях, коэффициент).
 *
 * @param string $name    Имя.
 * @param string $label   Подпись.
 * @param float  $fallback Значение по умолчанию.
 * @param string $help    Подсказка.
 * @param float  $step    Шаг.
 * @param array  $more    Другие настройки поля.
 */
$trust_number = static fn( string $name, string $label, float $fallback, string $help = '', float $step = 1, array $more = array() ): array => array_merge(
	array(
		'name'          => $name,
		'label'         => $label,
		'type'          => 'number',
		'default_value' => $fallback,
		'min'           => 0,
		'step'          => $step,
		'instructions'  => $help,
	),
	$more
);

/**
 * Заголовок вкладки, заголовок шага и пояснение.
 *
 * @param int    $n     Номер шага.
 * @param string $tab   Вкладка.
 * @param string $title Заголовок шага.
 * @param string $text  Пояснение.
 */
$trust_step = static fn( int $n, string $tab, string $title, string $text ): array => array(
	Blocks::text_field( "step{$n}_tab", __( 'Название на вкладке', 'trust' ), $tab, 30 ),
	Blocks::text_field( "step{$n}_title", __( 'Заголовок шага', 'trust' ), $title, 80 ),
	Blocks::text_field( "step{$n}_text", __( 'Пояснение', 'trust' ), $text, 200 ),
);

/**
 * Варианты шага (один на выбор): название, описание, надбавка, «выбран сразу».
 *
 * @param string $name  Повторитель.
 * @param string $label Подпись.
 */
$trust_options = static fn( string $name, string $label ): array => array(
	'name'         => $name,
	'label'        => $label,
	'type'         => 'repeater',
	'layout'       => 'block',
	'min'          => 1,
	'max'          => 6,
	'button_label' => __( 'Добавить вариант', 'trust' ),
	'collapsed'    => "field_trust_block_calculator_{$name}_variant",
	'instructions' => __( 'Посетитель выбирает один вариант. Надбавка прибавляется к базовой цене. Если «Выбран сразу» не отмечен ни у одного, выбран первый.', 'trust' ),
	'sub_fields'   => array(
		array(
			'name'      => 'variant',
			'label'     => __( 'Название', 'trust' ),
			'type'      => 'text',
			'maxlength' => 60,
			'required'  => 1,
			'wrapper'   => array( 'width' => 50 ),
		),
		array(
			'name'          => 'extra',
			'label'         => __( 'Надбавка, ₽ в месяц', 'trust' ),
			'type'          => 'number',
			'min'           => 0,
			'step'          => 100,
			'default_value' => 0,
			'wrapper'       => array( 'width' => 25 ),
		),
		array(
			'name'    => 'selected',
			'label'   => __( 'Выбран сразу', 'trust' ),
			'type'    => 'true_false',
			'ui'      => 1,
			'wrapper' => array( 'width' => 25 ),
		),
		array(
			'name'      => 'about',
			'label'     => __( 'Описание', 'trust' ),
			'type'      => 'text',
			'maxlength' => 160,
		),
	),
);

return array_merge(
	Blocks::section_fields(
		array(
			'anchor'  => 'calculator',
			'eyebrow' => __( 'Калькулятор', 'trust' ),
			'title'   => __( 'Рассчитайте стоимость продвижения', 'trust' ),
			'lead'    => __( 'Ответьте на 7 вопросов — покажем предварительную стоимость работ.', 'trust' ),
		)
	),
	array(
		array(
			'name'    => 'formula_note',
			'label'   => __( 'Как считается цена', 'trust' ),
			'type'    => 'message',
			'message' => __( '(Базовая цена + надбавки выбранных вариантов + число запросов × цена запроса) × наибольший коэффициент выбранных регионов. Результат округляется вверх и не бывает меньше минимальной цены.', 'trust' ),
		),

		$trust_section( 'price_section', __( 'Цена и результат', 'trust' ) ),
		$trust_number( 'base_price', __( 'Базовая цена, ₽ в месяц', 'trust' ), 20000, '', 100, array( 'wrapper' => array( 'width' => 33 ) ) ),
		$trust_number( 'min_price', __( 'Минимальная цена, ₽', 'trust' ), 25000, '', 100, array( 'wrapper' => array( 'width' => 33 ) ) ),
		$trust_number(
			'round_step',
			__( 'Округлять вверх до, ₽', 'trust' ),
			1000,
			'',
			1,
			array(
				'min'     => 1,
				'wrapper' => array( 'width' => 34 ),
			)
		),
		Blocks::text_field( 'result_eyebrow', __( 'Надпись над ценой', 'trust' ), __( 'Предварительный расчёт', 'trust' ), 40 ),
		Blocks::text_field( 'result_template', __( 'Текст цены', 'trust' ), __( 'от {price} ₽ / месяц', 'trust' ), 60, __( '{price} — рассчитанная сумма. Часть после « / » выводится мельче.', 'trust' ) ),
		array(
			'name'          => 'result_note',
			'label'         => __( 'Пояснение под ценой', 'trust' ),
			'type'          => 'textarea',
			'rows'          => 2,
			'new_lines'     => '',
			'maxlength'     => 300,
			'default_value' => __( 'Точную стоимость назовём после анализа сайта — пришлём расчёт с составом работ.', 'trust' ),
		),
		Blocks::text_field( 'cta_label', __( 'Кнопка после результата', 'trust' ), __( 'Получить точный расчёт', 'trust' ), 40 ),
		array(
			'name'         => 'cta_url',
			'label'        => __( 'Куда ведёт кнопка', 'trust' ),
			'type'         => 'text',
			'maxlength'    => 300,
			'placeholder'  => '#callback-modal',
			'instructions' => __( 'Адрес страницы, якорь блока (#brief) или #callback-modal — окно «Заказать обратный звонок». Пусто — окно звонка.', 'trust' ),
		),
		Blocks::text_field( 'edit_label', __( 'Кнопка «Изменить параметры»', 'trust' ), __( 'Изменить параметры', 'trust' ), 40 ),
		array(
			'name'         => 'contact_form',
			'label'        => __( 'Форма контактов на экране результата', 'trust' ),
			'type'         => 'true_false',
			'ui'           => 1,
			'message'      => __( 'Имя, телефон и согласие под результатом', 'trust' ),
			'instructions' => __( 'В макете формы нет. Ответы калькулятора и рассчитанная цена приходят в заявку («Заявки», форма «Калькулятор»).', 'trust' ),
		),
	),
	array_map(
		static fn( array $field ): array => array_merge(
			$field,
			array(
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'field_trust_block_calculator_contact_form',
							'operator' => '==',
							'value'    => '1',
						),
					),
				),
			)
		),
		array(
			Blocks::text_field( 'contact_title', __( 'Заголовок формы', 'trust' ), __( 'Оставьте телефон — пришлём точный расчёт с составом работ', 'trust' ), 120 ),
			Blocks::text_field( 'contact_submit', __( 'Кнопка формы', 'trust' ), __( 'Отправить', 'trust' ), 40 ),
			Blocks::text_field( 'success_title', __( 'Заголовок после отправки', 'trust' ), __( 'Заявка отправлена', 'trust' ), 60 ),
			Blocks::text_field( 'success_text', __( 'Текст после отправки', 'trust' ), __( 'Пришлём точный расчёт с составом работ в течение рабочего дня.', 'trust' ), 200 ),
		)
	),
	array(

		$trust_section( 'nav_section', __( 'Кнопки и шкала шагов', 'trust' ) ),
		Blocks::text_field( 'back_label', __( 'Кнопка «Назад»', 'trust' ), __( 'Назад', 'trust' ), 30 ),
		Blocks::text_field( 'next_label', __( 'Кнопка «Далее»', 'trust' ), __( 'Далее', 'trust' ), 30 ),
		Blocks::text_field( 'calc_label', __( 'Кнопка на последнем шаге', 'trust' ), __( 'Рассчитать стоимость', 'trust' ), 40 ),
		Blocks::text_field( 'progress_label', __( 'Подпись шкалы', 'trust' ), __( 'Шаг {n} из {total} · {name}', 'trust' ), 60, __( '{n} — номер шага, {total} — всего шагов, {name} — название вкладки.', 'trust' ) ),

		$trust_section( 'step1_section', __( 'Шаг 1. Регион', 'trust' ) ),
	),
	$trust_step( 1, __( 'Регион', 'trust' ), __( 'Регион продвижения', 'trust' ), __( 'Выберите один или несколько регионов или впишите свой.', 'trust' ) ),
	array(
		array(
			'name'         => 'regions',
			'label'        => __( 'Регионы', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'min'          => 1,
			'max'          => 16,
			'button_label' => __( 'Добавить регион', 'trust' ),
			'instructions' => __( 'Можно выбрать несколько: цена умножается на наибольший коэффициент из выбранных. 1 — без наценки, 1,5 — на 50 % дороже.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'      => 'region',
					'label'     => __( 'Регион', 'trust' ),
					'type'      => 'text',
					'maxlength' => 40,
					'required'  => 1,
				),
				array(
					'name'          => 'coef',
					'label'         => __( 'Коэффициент', 'trust' ),
					'type'          => 'number',
					'min'           => 0.1,
					'step'          => 0.05,
					'default_value' => 1,
				),
				array(
					'name'  => 'selected',
					'label' => __( 'Выбран сразу', 'trust' ),
					'type'  => 'true_false',
					'ui'    => 1,
				),
			),
		),
		Blocks::text_field( 'other_label', __( 'Подпись поля «Другой регион»', 'trust' ), __( 'Другой регион', 'trust' ), 40 ),
		Blocks::text_field( 'other_placeholder', __( 'Подсказка в поле', 'trust' ), __( 'Например, Тверь и Тверская область', 'trust' ), 80 ),
		$trust_number(
			'other_coef',
			__( 'Коэффициент другого региона', 'trust' ),
			1,
			__( 'Учитывается, если посетитель вписал свой регион.', 'trust' ),
			0.05,
			array( 'min' => 0.1 )
		),

		$trust_section( 'step2_section', __( 'Шаг 2. Запросы', 'trust' ) ),
	),
	$trust_step( 2, __( 'Запросы', 'trust' ), __( 'Ключевые запросы', 'trust' ), __( 'По каким запросам вы хотите видеть сайт в поиске? Если не знаете — оставьте поле пустым, подберём запросы сами.', 'trust' ) ),
	array(
		Blocks::text_field( 'queries_label', __( 'Подпись поля (для скринридеров)', 'trust' ), __( 'Ключевые запросы', 'trust' ), 60 ),
		array(
			'name'          => 'queries_placeholder',
			'label'         => __( 'Подсказка в поле', 'trust' ),
			'type'          => 'textarea',
			'rows'          => 3,
			'maxlength'     => 300,
			'default_value' => __( "Каждый запрос с новой строки, например:\nремонт квартир под ключ\nдизайн-проект квартиры", 'trust' ),
		),
		$trust_number( 'query_price', __( 'Цена за каждый запрос, ₽', 'trust' ), 0, __( 'Умножается на число строк в поле. 0 — запросы на цену не влияют.', 'trust' ), 10 ),

		$trust_section( 'step3_section', __( 'Шаг 3. Контент', 'trust' ) ),
	),
	$trust_step( 3, __( 'Контент', 'trust' ), __( 'Кто будет создавать контент', 'trust' ), __( 'Тексты для продвигаемых страниц, статьи и описания.', 'trust' ) ),
	array(
		$trust_options( 'content', __( 'Варианты', 'trust' ) ),

		$trust_section( 'step4_section', __( 'Шаг 4. Внешняя оптимизация', 'trust' ) ),
	),
	$trust_step( 4, __( 'Внешняя оптимизация', 'trust' ), __( 'Внешняя оптимизация', 'trust' ), __( 'Работа с внешними факторами: ссылки, упоминания, площадки.', 'trust' ) ),
	array(
		$trust_options( 'external', __( 'Варианты', 'trust' ) ),

		$trust_section( 'step5_section', __( 'Шаг 5. Репутация', 'trust' ) ),
	),
	$trust_step( 5, __( 'Репутация', 'trust' ), __( 'Работа с репутацией', 'trust' ), __( 'Насколько внимательно следить за тем, что пишут о вашей компании.', 'trust' ) ),
	array(
		$trust_options( 'reputation', __( 'Варианты', 'trust' ) ),

		$trust_section( 'step6_section', __( 'Шаг 6. GEO', 'trust' ) ),
	),
	$trust_step( 6, __( 'GEO', 'trust' ), __( 'GEO-продвижение', 'trust' ), __( 'Адаптация сайта под ответы нейросетей, чтобы они находили и упоминали ваш сайт.', 'trust' ) ),
	array(
		Blocks::text_field( 'geo_off_title', __( 'Вариант «Не адаптируем»', 'trust' ), __( 'Не адаптируем под AI-системы', 'trust' ), 60 ),
		Blocks::text_field( 'geo_off_text', __( 'Описание', 'trust' ), __( 'Продвигаем сайт только в классическом поиске.', 'trust' ), 160 ),
		$trust_number( 'geo_off_price', __( 'Надбавка, ₽ в месяц', 'trust' ), 0, '', 100 ),
		Blocks::text_field( 'geo_on_title', __( 'Вариант «Адаптируем»', 'trust' ), __( 'Адаптируем под AI-системы', 'trust' ), 60 ),
		Blocks::text_field( 'geo_on_text', __( 'Описание', 'trust' ), __( 'Выберите системы, под которые нужно адаптировать сайт.', 'trust' ), 160 ),
		$trust_number( 'geo_on_price', __( 'Надбавка, ₽ в месяц', 'trust' ), 10000, __( 'Плюс надбавки отмеченных AI-систем.', 'trust' ), 100 ),
		array(
			'name'          => 'geo_default',
			'label'         => __( 'Выбран сразу', 'trust' ),
			'type'          => 'button_group',
			'choices'       => array(
				'off' => __( 'Не адаптируем', 'trust' ),
				'on'  => __( 'Адаптируем', 'trust' ),
			),
			'default_value' => 'on',
			'return_format' => 'value',
		),
		Blocks::text_field( 'ai_label', __( 'Подпись списка AI-систем', 'trust' ), __( 'AI-системы', 'trust' ), 40 ),
		array(
			'name'         => 'ai',
			'label'        => __( 'AI-системы', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'max'          => 10,
			'button_label' => __( 'Добавить систему', 'trust' ),
			'instructions' => __( 'Показываются, если выбрано «Адаптируем». Сразу отмечены все.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'      => 'system',
					'label'     => __( 'Название', 'trust' ),
					'type'      => 'text',
					'maxlength' => 40,
					'required'  => 1,
				),
				array(
					'name'          => 'extra',
					'label'         => __( 'Надбавка, ₽ в месяц', 'trust' ),
					'type'          => 'number',
					'min'           => 0,
					'step'          => 100,
					'default_value' => 0,
				),
			),
		),

		$trust_section( 'step7_section', __( 'Шаг 7. О сайте', 'trust' ) ),
	),
	$trust_step( 7, __( 'О сайте', 'trust' ), __( 'О сайте', 'trust' ), __( 'Последний шаг — расскажите о сайте и цели продвижения.', 'trust' ) ),
	array(
		array(
			'name'    => 'about_note',
			'label'   => '',
			'type'    => 'message',
			'message' => __( 'Ответы этого шага на цену не влияют — они уходят в заявку, если на экране результата включена форма контактов.', 'trust' ),
		),
		Blocks::text_field( 'url_label', __( 'Подпись «Адрес сайта»', 'trust' ), __( 'Адрес сайта', 'trust' ), 60 ),
		Blocks::text_field( 'url_placeholder', __( 'Подсказка', 'trust' ), 'https://example.ru', 60 ),
		Blocks::text_field( 'topic_label', __( 'Подпись «Тематика сайта»', 'trust' ), __( 'Тематика сайта', 'trust' ), 60 ),
		Blocks::text_field( 'topic_placeholder', __( 'Подсказка', 'trust' ), __( 'Например, ремонт квартир', 'trust' ), 60 ),
		Blocks::text_field( 'pages_label', __( 'Подпись «Число страниц»', 'trust' ), __( 'Количество продвигаемых страниц', 'trust' ), 60 ),
		Blocks::text_field( 'pages_placeholder', __( 'Подсказка', 'trust' ), __( 'Например, 30', 'trust' ), 60 ),
		Blocks::text_field( 'goal_label', __( 'Подпись «Цель»', 'trust' ), __( 'Цель по трафику или заказам', 'trust' ), 60 ),
		Blocks::text_field( 'goal_placeholder', __( 'Подсказка', 'trust' ), __( 'Например, 50 заявок в месяц', 'trust' ), 60 ),
		Blocks::text_field( 'age_label', __( 'Подпись «Возраст сайта»', 'trust' ), __( 'Возраст сайта', 'trust' ), 60 ),
		array(
			'name'          => 'age_options',
			'label'         => __( 'Варианты возраста', 'trust' ),
			'type'          => 'textarea',
			'rows'          => 4,
			'new_lines'     => '',
			'maxlength'     => 300,
			'default_value' => "Новый\nДо 1 года\n1–3 года\nБолее 3 лет",
			'instructions'  => __( 'Каждый с новой строки.', 'trust' ),
		),
		Blocks::text_field( 'traffic_label', __( 'Подпись «Есть ли трафик»', 'trust' ), __( 'Есть ли сейчас трафик или заказы с сайта', 'trust' ), 80 ),
		array(
			'name'          => 'traffic_options',
			'label'         => __( 'Варианты ответа', 'trust' ),
			'type'          => 'textarea',
			'rows'          => 2,
			'new_lines'     => '',
			'maxlength'     => 200,
			'default_value' => "Да\nНет",
			'instructions'  => __( 'Каждый с новой строки.', 'trust' ),
		),
	)
);
