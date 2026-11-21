<?php
/**
 * Блок «Калькулятор стоимости» (ТЗ, п. 8.3.1; макеты holst-blokov/Calculator,
 * seo-bloki/13-kalkulyator.html).
 *
 * Лавандовая секция; белая карточка: вкладки 7 шагов (номер, у пройденных — галочка), шаг
 * (заголовок, пояснение, варианты), внизу «Назад», шкала «Шаг N из 7 · название», «Далее» /
 * «Рассчитать стоимость». Экран результата: «Предварительный расчёт», цена, пояснение, кнопка
 * (по умолчанию — окно «Заказать обратный звонок»), «Изменить параметры»; по флажку — форма
 * контактов, ответы и цена уходят в заявку. Цену считает скрипт блока (view.js) по формуле ТЗ:
 * надбавки и коэффициенты — в атрибутах data-* вариантов. Без JavaScript видны все шаги подряд
 * и кнопка связи.
 *
 * Переменные SCF: $block (array), $is_preview (bool), $post_id (int|string).
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Forms;
use Trust\Forms\Calculator;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * Число из поля блока; пусто — значение по умолчанию.
 *
 * @param string $name    Поле.
 * @param float  $fallback По умолчанию.
 */
$trust_num = static function ( string $name, float $fallback ): float {
	$value = get_field( $name );
	return is_numeric( $value ) ? max( 0, (float) $value ) : $fallback;
};

$trust_title    = (string) get_field( 'title' );
$trust_eyebrow  = (string) get_field( 'eyebrow' );
$trust_lead     = (string) get_field( 'lead' );
$trust_anchor   = Blocks::anchor( get_field( 'anchor' ) );
$trust_title_id = Blocks::uid( $block, 'title' );
$trust_uid      = Blocks::uid( $block, 'calc' );
$trust_contact  = (bool) get_field( 'contact_form' );
$trust_cta_url  = trim( (string) get_field( 'cta_url' ) );
$trust_cta_url  = '' !== $trust_cta_url ? $trust_cta_url : '#callback-modal';
$trust_geo_on   = 'off' !== get_field( 'geo_default' );
$trust_ages     = Calculator::lines( Blocks::text( 'age_options', "Новый\nДо 1 года\n1–3 года\nБолее 3 лет" ) );
$trust_answers  = Calculator::lines( Blocks::text( 'traffic_options', "Да\nНет" ) );

$trust_steps = array(
	1 => array( __( 'Регион', 'trust' ), __( 'Регион продвижения', 'trust' ), __( 'Выберите один или несколько регионов или впишите свой.', 'trust' ) ),
	2 => array( __( 'Запросы', 'trust' ), __( 'Ключевые запросы', 'trust' ), __( 'По каким запросам вы хотите видеть сайт в поиске? Если не знаете — оставьте поле пустым, подберём запросы сами.', 'trust' ) ),
	3 => array( __( 'Контент', 'trust' ), __( 'Кто будет создавать контент', 'trust' ), __( 'Тексты для продвигаемых страниц, статьи и описания.', 'trust' ) ),
	4 => array( __( 'Внешняя оптимизация', 'trust' ), __( 'Внешняя оптимизация', 'trust' ), __( 'Работа с внешними факторами: ссылки, упоминания, площадки.', 'trust' ) ),
	5 => array( __( 'Репутация', 'trust' ), __( 'Работа с репутацией', 'trust' ), __( 'Насколько внимательно следить за тем, что пишут о вашей компании.', 'trust' ) ),
	6 => array( __( 'GEO', 'trust' ), __( 'GEO-продвижение', 'trust' ), __( 'Адаптация сайта под ответы нейросетей, чтобы они находили и упоминали ваш сайт.', 'trust' ) ),
	7 => array( __( 'О сайте', 'trust' ), __( 'О сайте', 'trust' ), __( 'Последний шаг — расскажите о сайте и цели продвижения.', 'trust' ) ),
);
foreach ( $trust_steps as $trust_n => list( $trust_tab, $trust_head, $trust_text ) ) {
	$trust_steps[ $trust_n ] = array(
		'tab'   => Blocks::text( "step{$trust_n}_tab", $trust_tab ),
		'title' => Blocks::text( "step{$trust_n}_title", $trust_head ),
		'text'  => Blocks::text( "step{$trust_n}_text", $trust_text ),
	);
}
$trust_total = count( $trust_steps );

$trust_check = '<svg class="b-calc__tick" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M2.5 6.5l2.2 2.2 4.8-5"/></svg>';

/**
 * Варианты «один на выбор» карточками (контент, внешняя оптимизация, репутация, GEO).
 *
 * @param string $name    Имя группы.
 * @param string $legend  Подпись группы (для скринридеров).
 * @param array  $options Варианты: title, text, price, selected, value (необязательно).
 * @param string $mod     Модификатор сетки.
 */
$trust_cards = static function ( string $name, string $legend, array $options, string $mod = '' ) use ( $trust_uid ): void {
	printf( '<fieldset class="b-calc__options%s"><legend class="screen-reader-text">%s</legend>', $mod ? ' b-calc__options--' . esc_attr( $mod ) : '', esc_html( $legend ) );
	foreach ( $options as $i => $option ) {
		$id = "{$trust_uid}-{$name}-{$i}";
		printf(
			'<label class="b-calc__option" for="%1$s"><input class="b-calc__radio" type="radio" id="%1$s" name="step_%2$s" value="%3$s" data-price="%4$s" data-title="%5$s"%6$s><span class="b-calc__option-body"><span class="b-calc__option-title">%5$s</span>%7$s</span></label>',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $option['value'] ?? $option['title'] ),
			esc_attr( (string) $option['price'] ),
			esc_html( $option['title'] ),
			$option['selected'] ? ' checked' : '',
			'' !== $option['text'] ? '<span class="b-calc__option-text">' . esc_html( $option['text'] ) . '</span>' : ''
		);
	}
	echo '</fieldset>';
};

/**
 * Поле шага «О сайте».
 *
 * @param string $name  Имя.
 * @param string $label Подпись.
 * @param string $hint  Подсказка.
 * @param string $attrs Атрибуты (константы разметки).
 */
$trust_field = static function ( string $name, string $label, string $hint, string $attrs ) use ( $trust_uid ): void {
	$id = "{$trust_uid}-{$name}";
	printf(
		'<div class="field"><label class="field__label" for="%1$s">%2$s</label><input class="field__input" id="%1$s" name="step_%3$s" placeholder="%4$s" %5$s></div>',
		esc_attr( $id ),
		esc_html( $label ),
		esc_attr( $name ),
		esc_attr( $hint ),
		$attrs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- константы разметки ниже.
	);
};

/**
 * Варианты-«таблетки» «один на выбор» (возраст сайта, есть ли трафик).
 *
 * @param string   $name    Имя.
 * @param string   $legend  Подпись.
 * @param string[] $options Варианты.
 */
$trust_pills = static function ( string $name, string $legend, array $options ) use ( $trust_uid ): void {
	printf( '<div class="b-calc__pills" role="radiogroup" aria-labelledby="%1$s"><span class="b-calc__legend" id="%1$s">%2$s</span><div class="b-calc__pill-list">', esc_attr( "{$trust_uid}-{$name}-label" ), esc_html( $legend ) );
	foreach ( $options as $i => $option ) {
		printf(
			'<label class="b-calc__pill" for="%1$s"><input class="b-calc__pill-input" type="radio" id="%1$s" name="step_%2$s" value="%3$s"><span>%3$s</span></label>',
			esc_attr( "{$trust_uid}-{$name}-{$i}" ),
			esc_attr( $name ),
			esc_html( $option )
		);
	}
	echo '</div></div>';
};

$trust_template = Blocks::text( 'result_template', __( 'от {price} ₽ / месяц', 'trust' ) );
$trust_parts    = explode( ' / ', $trust_template, 2 );
?>
<section class="b-calc trust-bg-lavender"<?php echo $trust_anchor ? ' id="' . esc_attr( $trust_anchor ) . '"' : ''; ?><?php echo $trust_title ? ' aria-labelledby="' . esc_attr( $trust_title_id ) . '"' : ''; ?>>
	<div class="b-calc__inner">
		<?php if ( $trust_eyebrow || $trust_title || $trust_lead ) : ?>
			<div class="section-head b-calc__head">
				<?php if ( $trust_eyebrow ) : ?>
					<span class="eyebrow"><?php echo esc_html( $trust_eyebrow ); ?></span>
				<?php endif; ?>
				<?php if ( $trust_title ) : ?>
					<h2 class="section-title" id="<?php echo esc_attr( $trust_title_id ); ?>"><?php echo esc_html( $trust_title ); ?></h2>
				<?php endif; ?>
				<?php if ( '' !== trim( $trust_lead ) ) : ?>
					<p class="section-lead section-lead--lg"><?php echo esc_html( $trust_lead ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="b-calc__card" id="<?php echo esc_attr( $trust_uid ); ?>" data-calc
			data-base="<?php echo esc_attr( (string) $trust_num( 'base_price', 20000 ) ); ?>"
			data-min="<?php echo esc_attr( (string) $trust_num( 'min_price', 25000 ) ); ?>"
			data-round="<?php echo esc_attr( (string) max( 1, $trust_num( 'round_step', 1000 ) ) ); ?>"
			data-query-price="<?php echo esc_attr( (string) $trust_num( 'query_price', 0 ) ); ?>"
			data-other-coef="<?php echo esc_attr( (string) Calculator::coef( get_field( 'other_coef' ) ?? 1 ) ); ?>"
			data-template="<?php echo esc_attr( $trust_template ); ?>"
			data-progress="<?php echo esc_attr( Blocks::text( 'progress_label', __( 'Шаг {n} из {total} · {name}', 'trust' ) ) ); ?>">

			<div class="b-calc__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Этапы расчёта', 'trust' ); ?>">
				<?php foreach ( $trust_steps as $trust_n => $trust_step ) : ?>
					<button type="button" class="b-calc__tab<?php echo 1 === $trust_n ? ' is-active' : ''; ?>" role="tab" id="<?php echo esc_attr( "{$trust_uid}-tab-{$trust_n}" ); ?>" aria-controls="<?php echo esc_attr( "{$trust_uid}-panel-{$trust_n}" ); ?>" aria-selected="<?php echo 1 === $trust_n ? 'true' : 'false'; ?>" tabindex="<?php echo 1 === $trust_n ? '0' : '-1'; ?>" data-calc-tab>
						<span class="b-calc__num" aria-hidden="true"><span class="b-calc__num-text"><?php echo esc_html( (string) $trust_n ); ?></span><?php echo $trust_check; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- постоянная разметка SVG. ?></span>
						<span class="b-calc__tab-name"><?php echo esc_html( $trust_step['tab'] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="b-calc__body" data-calc-body>
				<?php foreach ( $trust_steps as $trust_n => $trust_step ) : ?>
					<div class="b-calc__panel" role="tabpanel" id="<?php echo esc_attr( "{$trust_uid}-panel-{$trust_n}" ); ?>" aria-labelledby="<?php echo esc_attr( "{$trust_uid}-tab-{$trust_n}" ); ?>" data-calc-panel<?php echo 1 === $trust_n ? '' : ' hidden'; ?>>
						<div class="b-calc__step-head">
							<h3 class="b-calc__step-title"><?php echo esc_html( $trust_step['title'] ); ?></h3>
							<?php if ( '' !== $trust_step['text'] ) : ?>
								<p class="b-calc__step-text"><?php echo esc_html( $trust_step['text'] ); ?></p>
							<?php endif; ?>
						</div>
						<?php
						switch ( $trust_n ) {
							case 1:
								printf( '<fieldset class="b-calc__chips"><legend class="screen-reader-text">%s</legend>', esc_html( $trust_step['title'] ) );
								foreach ( Calculator::regions() as $trust_i => $trust_region ) {
									printf(
										'<label class="b-calc__chip" for="%1$s"><input class="b-calc__chip-input" type="checkbox" id="%1$s" name="step_region[]" value="%2$s" data-coef="%3$s"%4$s><span class="b-calc__chip-icon" aria-hidden="true"><svg class="b-calc__chip-plus" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" focusable="false"><path d="M8 3v10M3 8h10"/></svg><svg class="b-calc__chip-check" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M3.5 8.5l3 3 6-6.5"/></svg></span><span>%2$s</span></label>',
										esc_attr( "{$trust_uid}-region-{$trust_i}" ),
										esc_html( $trust_region['title'] ),
										esc_attr( (string) $trust_region['coef'] ),
										$trust_region['selected'] ? ' checked' : ''
									);
								}
								echo '</fieldset>';
								printf(
									'<div class="field b-calc__other"><label class="field__label" for="%1$s">%2$s</label><input class="field__input" type="text" id="%1$s" name="step_region_other" maxlength="100" placeholder="%3$s" data-calc-other></div>',
									esc_attr( "{$trust_uid}-other" ),
									esc_html( Blocks::text( 'other_label', __( 'Другой регион', 'trust' ) ) ),
									esc_attr( Blocks::text( 'other_placeholder', __( 'Например, Тверь и Тверская область', 'trust' ) ) )
								);
								break;
							case 2:
								printf(
									'<label class="screen-reader-text" for="%1$s">%2$s</label><textarea class="field__input b-calc__queries" id="%1$s" name="step_queries" rows="7" maxlength="5000" placeholder="%3$s" data-calc-queries></textarea>',
									esc_attr( "{$trust_uid}-queries" ),
									esc_html( Blocks::text( 'queries_label', __( 'Ключевые запросы', 'trust' ) ) ),
									esc_attr( Blocks::text( 'queries_placeholder', __( "Каждый запрос с новой строки, например:\nремонт квартир под ключ\nдизайн-проект квартиры", 'trust' ) ) )
								);
								break;
							case 3:
								$trust_cards( 'content', $trust_step['title'], Calculator::options( 'content' ) );
								break;
							case 4:
								$trust_cards( 'external', $trust_step['title'], Calculator::options( 'external' ) );
								break;
							case 5:
								$trust_cards( 'reputation', $trust_step['title'], Calculator::options( 'reputation' ), 'narrow' );
								break;
							case 6:
								$trust_cards(
									'geo',
									$trust_step['title'],
									array(
										array(
											'value'    => 'off',
											'title'    => Blocks::text( 'geo_off_title', __( 'Не адаптируем под AI-системы', 'trust' ) ),
											'text'     => Blocks::text( 'geo_off_text', __( 'Продвигаем сайт только в классическом поиске.', 'trust' ) ),
											'price'    => $trust_num( 'geo_off_price', 0 ),
											'selected' => ! $trust_geo_on,
										),
										array(
											'value'    => 'on',
											'title'    => Blocks::text( 'geo_on_title', __( 'Адаптируем под AI-системы', 'trust' ) ),
											'text'     => Blocks::text( 'geo_on_text', __( 'Выберите системы, под которые нужно адаптировать сайт.', 'trust' ) ),
											'price'    => $trust_num( 'geo_on_price', 10000 ),
											'selected' => $trust_geo_on,
										),
									)
								);
								printf( '<div class="b-calc__ai" role="group" aria-labelledby="%1$s" data-calc-ai%2$s><span class="b-calc__legend" id="%1$s">%3$s</span><div class="b-calc__ai-list">', esc_attr( "{$trust_uid}-ai-label" ), $trust_geo_on ? '' : ' hidden', esc_html( Blocks::text( 'ai_label', __( 'AI-системы', 'trust' ) ) ) );
								foreach ( Calculator::ai() as $trust_i => $trust_ai ) {
									printf(
										'<label class="b-calc__check" for="%1$s"><input class="b-calc__checkbox" type="checkbox" id="%1$s" name="step_ai[]" value="%2$s" data-price="%3$s" checked>%2$s</label>',
										esc_attr( "{$trust_uid}-ai-{$trust_i}" ),
										esc_html( $trust_ai['title'] ),
										esc_attr( (string) $trust_ai['price'] )
									);
								}
								echo '</div></div>';
								break;
							case 7:
								echo '<div class="b-calc__about">';
								$trust_field( 'url', Blocks::text( 'url_label', __( 'Адрес сайта', 'trust' ) ), Blocks::text( 'url_placeholder', 'https://example.ru' ), 'type="url" inputmode="url" autocomplete="url" maxlength="200"' );
								$trust_field( 'topic', Blocks::text( 'topic_label', __( 'Тематика сайта', 'trust' ) ), Blocks::text( 'topic_placeholder', __( 'Например, ремонт квартир', 'trust' ) ), 'type="text" maxlength="200"' );
								$trust_field( 'pages', Blocks::text( 'pages_label', __( 'Количество продвигаемых страниц', 'trust' ) ), Blocks::text( 'pages_placeholder', __( 'Например, 30', 'trust' ) ), 'type="number" min="1" max="1000000" inputmode="numeric"' );
								$trust_field( 'goal', Blocks::text( 'goal_label', __( 'Цель по трафику или заказам', 'trust' ) ), Blocks::text( 'goal_placeholder', __( 'Например, 50 заявок в месяц', 'trust' ) ), 'type="text" maxlength="300"' );
								if ( $trust_ages ) {
									$trust_pills( 'age', Blocks::text( 'age_label', __( 'Возраст сайта', 'trust' ) ), $trust_ages );
								}
								if ( $trust_answers ) {
									$trust_pills( 'traffic', Blocks::text( 'traffic_label', __( 'Есть ли сейчас трафик или заказы с сайта', 'trust' ) ), $trust_answers );
								}
								echo '</div>';
								break;
						}
						?>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="b-calc__nav" data-calc-nav>
				<button type="button" class="b-calc__back" data-calc-prev disabled>
					<?php
					Icons::render(
						'arrow-left',
						array(
							'size'         => 16,
							'stroke_width' => 2,
						)
					);
					echo esc_html( Blocks::text( 'back_label', __( 'Назад', 'trust' ) ) );
					?>
				</button>
				<div class="b-calc__progress">
					<span class="b-calc__progress-label" aria-live="polite" data-calc-label>
					<?php
					echo esc_html(
						strtr(
							Blocks::text( 'progress_label', __( 'Шаг {n} из {total} · {name}', 'trust' ) ),
							array(
								'{n}'     => '1',
								'{total}' => (string) $trust_total,
								'{name}'  => $trust_steps[1]['tab'],
							)
						)
					);
					?>
					</span>
					<span class="b-calc__bar" aria-hidden="true"><span class="b-calc__bar-fill" style="width: <?php echo esc_attr( (string) round( 100 / $trust_total ) ); ?>%" data-calc-bar></span></span>
				</div>
				<button type="button" class="b-calc__next" data-calc-next>
					<?php
					echo esc_html( Blocks::text( 'next_label', __( 'Далее', 'trust' ) ) );
					Icons::render(
						'arrow-right',
						array(
							'size'         => 16,
							'stroke_width' => 2,
						)
					);
					?>
				</button>
				<button type="button" class="b-calc__next" data-calc-go hidden>
					<?php
					echo esc_html( Blocks::text( 'calc_label', __( 'Рассчитать стоимость', 'trust' ) ) );
					Icons::render(
						'arrow-right',
						array(
							'size'         => 16,
							'stroke_width' => 2,
						)
					);
					?>
				</button>
			</div>

			<div class="b-calc__result" tabindex="-1" data-calc-result hidden>
				<span class="b-calc__result-eyebrow"><?php echo esc_html( Blocks::text( 'result_eyebrow', __( 'Предварительный расчёт', 'trust' ) ) ); ?></span>
				<p class="b-calc__price" role="status"><span data-calc-price><?php echo esc_html( $trust_parts[0] ); ?></span><?php echo isset( $trust_parts[1] ) ? ' <span class="b-calc__price-unit">/ ' . esc_html( $trust_parts[1] ) . '</span>' : ''; ?></p>
				<?php $trust_note = Blocks::text( 'result_note', __( 'Точную стоимость назовём после анализа сайта — пришлём расчёт с составом работ.', 'trust' ) ); ?>
				<?php if ( '' !== $trust_note ) : ?>
					<p class="b-calc__note"><?php echo esc_html( $trust_note ); ?></p>
				<?php endif; ?>
				<div class="b-calc__actions">
					<a class="b-calc__cta" href="<?php echo esc_url( $trust_cta_url ); ?>"><?php echo esc_html( Blocks::text( 'cta_label', __( 'Получить точный расчёт', 'trust' ) ) ); ?></a>
					<button type="button" class="b-calc__edit" data-calc-edit><?php echo esc_html( Blocks::text( 'edit_label', __( 'Изменить параметры', 'trust' ) ) ); ?></button>
				</div>

				<?php if ( $trust_contact ) : ?>
					<?php
					$trust_type = Calculator::FORM;
					$trust_form = Forms::type( $trust_type );
					?>
					<div class="b-calc__contact">
						<form class="form b-calc__form" id="<?php echo esc_attr( $trust_uid ); ?>-form" method="post" action="<?php echo esc_url( Forms::fallback_url() ); ?>" data-trust-form="<?php echo esc_attr( $trust_type ); ?>" data-success="<?php echo esc_attr( $trust_uid ); ?>-sent" aria-labelledby="<?php echo esc_attr( $trust_uid ); ?>-contact-title" novalidate>
							<p class="b-calc__contact-title" id="<?php echo esc_attr( $trust_uid ); ?>-contact-title"><?php echo esc_html( Blocks::text( 'contact_title', __( 'Оставьте телефон — пришлём точный расчёт с составом работ', 'trust' ) ) ); ?></p>
							<?php Forms::hidden_fields( $trust_type ); ?>
							<input type="hidden" name="_trust_anchor" value="<?php echo esc_attr( $trust_uid ); ?>-form">
							<?php foreach ( array_keys( $trust_form['fields'] ) as $trust_key ) : ?>
								<?php if ( str_starts_with( $trust_key, 'calc_' ) ) : ?>
									<input type="hidden" name="<?php echo esc_attr( $trust_key ); ?>" value="" data-calc-answer="<?php echo esc_attr( $trust_key ); ?>">
								<?php endif; ?>
							<?php endforeach; ?>
							<div class="b-calc__form-row">
								<?php
								foreach (
									array(
										'name'  => array( 'type="text" autocomplete="name" maxlength="100"', __( 'Имя', 'trust' ), __( 'Как к вам обращаться', 'trust' ) ),
										'phone' => array( 'type="tel" autocomplete="tel" inputmode="tel" maxlength="30"', __( 'Телефон', 'trust' ), Forms::phone_example() ),
									) as $trust_key => list( $trust_attrs, $trust_label, $trust_hint )
								) {
									$trust_id = "{$trust_uid}-{$trust_key}";
									printf(
										'<div class="field"><label class="field__label" for="%1$s">%2$s</label><input class="field__input" id="%1$s" name="%3$s" %4$s required placeholder="%5$s" aria-describedby="%1$s-error" data-error="%6$s"><span class="field__error" id="%1$s-error" hidden></span></div>',
										esc_attr( $trust_id ),
										esc_html( $trust_label ),
										esc_attr( $trust_key ),
										$trust_attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- константы разметки выше.
										esc_attr( $trust_hint ),
										esc_attr( $trust_form['fields'][ $trust_key ]['error'] ?? '' )
									);
								}
								?>
							</div>
							<?php Forms::consent_check( $trust_uid, $trust_type ); ?>
							<button type="submit" class="form__submit b-calc__submit">
								<?php echo esc_html( Blocks::text( 'contact_submit', __( 'Отправить', 'trust' ) ) ); ?>
								<?php
								Icons::render(
									'arrow-right',
									array(
										'size'         => 18,
										'stroke_width' => 2,
									)
								);
								?>
							</button>
							<?php Forms::status( $trust_type ); ?>
						</form>
						<?php Forms::sent_screen( $trust_uid . '-sent', Blocks::text( 'success_title', __( 'Заявка отправлена', 'trust' ) ), Blocks::text( 'success_text', __( 'Пришлём точный расчёт с составом работ в течение рабочего дня.', 'trust' ) ), Blocks::text( 'again', __( 'Отправить ещё раз', 'trust' ) ) ); ?>
					</div>
				<?php endif; ?>
			</div>

			<p class="b-calc__nojs">
				<?php esc_html_e( 'Чтобы рассчитать стоимость, включите JavaScript в браузере — или оставьте заявку, и мы посчитаем сами.', 'trust' ); ?>
				<a href="<?php echo esc_url( $trust_cta_url ); ?>"><?php echo esc_html( Blocks::text( 'cta_label', __( 'Получить точный расчёт', 'trust' ) ) ); ?></a>
			</p>
		</div>
	</div>
</section>
