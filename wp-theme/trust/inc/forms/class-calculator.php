<?php
/**
 * Калькулятор стоимости (ТЗ, п. 8.3.1; блок trust/calculator, макет holst-blokov/Calculator,
 * seo-bloki/13-kalkulyator.html): варианты по умолчанию и форма «Калькулятор» для заявки
 * с экрана результата (по флажку блока).
 *
 * Варианты шагов (регионы, контент, внешняя оптимизация, репутация, AI-системы) — повторители
 * блока. Пока повторитель пустой, в настройках блока и на сайте — варианты из макета; цены
 * и коэффициенты в макете не указаны — здесь примерные, в долларах (все цены сайта — в долларах,
 * решение заказчика); их задаёт владелец сайта.
 *
 * @package Trust
 */

namespace Trust\Forms;

use Trust\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Варианты по умолчанию и форма калькулятора.
 */
final class Calculator {

	/**
	 * Тип формы.
	 */
	public const FORM = 'calculator';

	/**
	 * Регионы: название, коэффициент, выбран сразу (макет: Москва).
	 */
	public const REGIONS = array(
		array( 'Москва', 1.5, true ),
		array( 'Санкт-Петербург', 1.3, false ),
		array( 'Новосибирск', 1.1, false ),
		array( 'Екатеринбург', 1.1, false ),
		array( 'Казань', 1, false ),
		array( 'Нижний Новгород', 1, false ),
		array( 'Краснодар', 1, false ),
		array( 'Вся Россия', 1.8, false ),
	);

	/**
	 * Варианты шагов «Контент», «Внешняя оптимизация», «Репутация»: название, описание, надбавка
	 * в месяц, выбран сразу (как в макете).
	 */
	public const OPTIONS = array(
		'content'    => array(
			array( 'Ваш специалист', 'Мы готовим техническое задание, тексты пишет специалист с вашей стороны.', 0, false ),
			array( 'Специалист с биржи', 'Готовим ТЗ, подбираем исполнителя на бирже и проверяем его работу.', 60, true ),
			array( 'Наш специалист', 'Контент полностью готовит наша команда.', 200, false ),
		),
		'external'   => array(
			array( 'Минимально необходимые работы', 'Только то, без чего сайту сложно расти в выдаче.', 150, true ),
			array( 'Весь комплекс работ', 'Системная работа со ссылками и упоминаниями на протяжении проекта.', 350, false ),
			array( 'Без внешней оптимизации', 'Работаем только с самим сайтом.', 0, false ),
		),
		'reputation' => array(
			array( 'Не следим за репутацией', 'Работаем только с поисковым продвижением.', 0, false ),
			array( 'Только отзывы', 'Отслеживаем отзывы на картах и сайтах-отзовиках.', 60, true ),
			array( 'Все упоминания бренда', 'Следим за упоминаниями компании на любых сайтах.', 120, false ),
			array( 'Репутация в соцсетях', 'Отслеживаем упоминания и обсуждения в социальных сетях.', 100, false ),
		),
	);

	/**
	 * AI-системы шага GEO: название, надбавка (в макете отмечены все).
	 */
	public const AI = array(
		array( 'Claude', 40 ),
		array( 'Gemini', 40 ),
		array( 'ChatGPT', 40 ),
		array( 'Алиса', 40 ),
	);

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'trust_form_types', array( self::class, 'types' ) );
		foreach ( array( 'regions', 'content', 'external', 'reputation', 'ai' ) as $name ) {
			add_filter( "acf/load_value/key=field_trust_block_calculator_{$name}", static fn( $value ) => self::default_rows( $name, $value ), 20 );
		}
	}

	/**
	 * Строки повторителя по умолчанию (в настройках блока, пока повторитель пустой).
	 *
	 * @param string $name  Повторитель.
	 * @param mixed  $value Строки или пусто.
	 * @return mixed
	 */
	public static function default_rows( string $name, $value ) {
		if ( ! empty( $value ) ) {
			return $value;
		}
		$key = static fn( string $sub ): string => "field_trust_block_calculator_{$name}_{$sub}";
		return array_map(
			static function ( array $row ) use ( $name, $key ): array {
				switch ( $name ) {
					case 'regions':
						return array(
							$key( 'region' )   => $row[0],
							$key( 'coef' )     => $row[1],
							$key( 'selected' ) => $row[2] ? 1 : 0,
						);
					case 'ai':
						return array(
							$key( 'system' ) => $row[0],
							$key( 'extra' )  => $row[1],
						);
					default:
						return array(
							$key( 'variant' )  => $row[0],
							$key( 'about' )    => $row[1],
							$key( 'extra' )    => $row[2],
							$key( 'selected' ) => $row[3] ? 1 : 0,
						);
				}
			},
			self::defaults( $name )
		);
	}

	/**
	 * Варианты по умолчанию повторителя.
	 *
	 * @param string $name Повторитель.
	 */
	public static function defaults( string $name ): array {
		if ( 'regions' === $name ) {
			return self::REGIONS;
		}
		if ( 'ai' === $name ) {
			return self::AI;
		}
		return self::OPTIONS[ $name ] ?? array();
	}

	/**
	 * Варианты шага для вывода: из блока (get_field) или по умолчанию. Строки без названия
	 * пропускаются; выбран — первый отмеченный «Выбран сразу», иначе первый.
	 *
	 * @param string $name Повторитель: content, external, reputation.
	 * @return array<int, array{title: string, text: string, price: float, selected: bool}>
	 */
	public static function options( string $name ): array {
		$rows = array();
		foreach ( (array) get_field( $name ) as $row ) {
			$title = trim( (string) ( $row['variant'] ?? '' ) );
			if ( '' !== $title ) {
				$rows[] = array(
					'title'    => $title,
					'text'     => trim( (string) ( $row['about'] ?? '' ) ),
					'price'    => (float) ( $row['extra'] ?? 0 ),
					'selected' => ! empty( $row['selected'] ),
				);
			}
		}
		if ( ! $rows ) {
			foreach ( self::defaults( $name ) as $row ) {
				$rows[] = array(
					'title'    => $row[0],
					'text'     => $row[1],
					'price'    => (float) $row[2],
					'selected' => $row[3],
				);
			}
		}
		$first = array_search( true, array_column( $rows, 'selected' ), true );
		foreach ( $rows as $i => $row ) {
			$rows[ $i ]['selected'] = ( false === $first ? 0 : $first ) === $i;
		}
		return $rows;
	}

	/**
	 * Регионы для вывода: название, коэффициент (не меньше 0,1; пусто — 1), выбран сразу.
	 *
	 * @return array<int, array{title: string, coef: float, selected: bool}>
	 */
	public static function regions(): array {
		$rows = array();
		foreach ( (array) get_field( 'regions' ) as $row ) {
			$title = trim( (string) ( $row['region'] ?? '' ) );
			if ( '' !== $title ) {
				$rows[] = array(
					'title'    => $title,
					'coef'     => self::coef( $row['coef'] ?? 1 ),
					'selected' => ! empty( $row['selected'] ),
				);
			}
		}
		if ( ! $rows ) {
			foreach ( self::REGIONS as $row ) {
				$rows[] = array(
					'title'    => $row[0],
					'coef'     => (float) $row[1],
					'selected' => $row[2],
				);
			}
		}
		return $rows;
	}

	/**
	 * AI-системы для вывода: название, надбавка.
	 *
	 * @return array<int, array{title: string, price: float}>
	 */
	public static function ai(): array {
		$rows = array();
		foreach ( (array) get_field( 'ai' ) as $row ) {
			$title = trim( (string) ( $row['system'] ?? '' ) );
			if ( '' !== $title ) {
				$rows[] = array(
					'title' => $title,
					'price' => (float) ( $row['extra'] ?? 0 ),
				);
			}
		}
		if ( ! $rows ) {
			foreach ( self::AI as $row ) {
				$rows[] = array(
					'title' => $row[0],
					'price' => (float) $row[1],
				);
			}
		}
		return $rows;
	}

	/**
	 * Коэффициент региона: число не меньше 0,1; пусто или не число — 1.
	 *
	 * @param mixed $value Значение поля.
	 */
	public static function coef( $value ): float {
		$value = is_numeric( $value ) ? (float) $value : 1.0;
		return $value > 0 ? max( 0.1, $value ) : 1.0;
	}

	/**
	 * Строки многострочного поля (варианты «Возраст сайта», «Есть ли трафик»).
	 *
	 * @param string $text Текст поля.
	 * @return string[]
	 */
	public static function lines( string $text ): array {
		return array_values( array_filter( array_map( 'trim', preg_split( '/\R/', $text ) ), 'strlen' ) );
	}

	/**
	 * Форма «Калькулятор»: имя, телефон, согласие и ответы калькулятора с результатом
	 * (скрытые поля заполняет скрипт блока перед отправкой).
	 *
	 * @param array $types Описания форм.
	 */
	public static function types( array $types ): array {
		$answer = static fn( string $label, int $max = 300, string $type = 'text' ): array => array(
			'label' => $label,
			'type'  => $type,
			'max'   => $max,
			/* translators: %s: название поля. */
			'error' => sprintf( __( '«%s» — слишком длинное значение', 'trust' ), $label ),
		);

		$types[ self::FORM ] = array(
			'label'    => __( 'Калькулятор', 'trust' ),
			'captcha'  => false,
			'fields'   => array(
				'name'            => array(
					'label'    => __( 'Имя', 'trust' ),
					'type'     => 'text',
					'required' => true,
					'max'      => 100,
					'error'    => __( 'Укажите, как к вам обращаться', 'trust' ),
				),
				'phone'           => array(
					'label'    => __( 'Телефон', 'trust' ),
					'type'     => 'tel',
					'required' => true,
					/* translators: %s: пример номера телефона. */
					'error'    => sprintf( __( 'Проверьте номер телефона, например %s', 'trust' ), Forms::phone_example() ),
				),
				'calc_price'      => $answer( __( 'Предварительная стоимость', 'trust' ), 100 ),
				'calc_region'     => $answer( __( 'Регион', 'trust' ), 500 ),
				'calc_queries'    => $answer( __( 'Ключевые запросы', 'trust' ), 5000, 'textarea' ),
				'calc_content'    => $answer( __( 'Контент', 'trust' ) ),
				'calc_external'   => $answer( __( 'Внешняя оптимизация', 'trust' ) ),
				'calc_reputation' => $answer( __( 'Репутация', 'trust' ) ),
				'calc_geo'        => $answer( __( 'GEO', 'trust' ), 500 ),
				'calc_url'        => $answer( __( 'Адрес сайта', 'trust' ), 200 ),
				'calc_topic'      => $answer( __( 'Тематика сайта', 'trust' ), 200 ),
				'calc_pages'      => $answer( __( 'Количество продвигаемых страниц', 'trust' ), 20 ),
				'calc_goal'       => $answer( __( 'Цель по трафику или заказам', 'trust' ), 300 ),
				'calc_age'        => $answer( __( 'Возраст сайта', 'trust' ), 100 ),
				'calc_traffic'    => $answer( __( 'Есть ли трафик или заказы', 'trust' ), 100 ),
			),
			'consents' => array( 'consent' => Forms::consent() ),
			'name'     => 'name',
			'contact'  => array( 'phone' ),
		);
		return $types;
	}
}
