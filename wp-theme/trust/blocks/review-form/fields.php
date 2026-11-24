<?php
/**
 * Поля блока «Оставьте отзыв» (ТЗ, п. 8.7 и 12.1; макет holst-o-nas/Reviews, o-nas/otzyvy.html).
 * Поля формы, правила и сообщения об ошибках — Trust\Forms\Review_Form; здесь — тексты блока
 * и площадки отзывов.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;

return array_merge(
	Blocks::section_fields(
		array(
			'anchor'  => 'leave-review',
			'eyebrow' => __( 'Обратная связь', 'trust' ),
			'title'   => __( 'Оставьте отзыв', 'trust' ),
			'lead'    => __( 'Работали с нами? Расскажите, как всё прошло — нам важна любая обратная связь, в том числе критика. Перед публикацией свяжемся с вами, чтобы подтвердить отзыв.', 'trust' ),
		)
	),
	array(
		Blocks::text_field( 'platforms_label', __( 'Подпись над площадками', 'trust' ), __( 'Или оставьте отзыв на площадке:', 'trust' ), 80 ),
		array(
			'name'         => 'platforms',
			'label'        => __( 'Площадки отзывов', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'table',
			'max'          => 6,
			'button_label' => __( 'Добавить площадку', 'trust' ),
			'instructions' => __( 'Яндекс Карты, 2ГИС, Google Карты и другие — ссылка на страницу отзывов компании. Пусто — без площадок.', 'trust' ),
			'sub_fields'   => array(
				array(
					'name'      => 'name',
					'label'     => __( 'Площадка', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 30,
					'wrapper'   => array( 'width' => 35 ),
				),
				array(
					'name'     => 'url',
					'label'    => __( 'Ссылка', 'trust' ),
					'type'     => 'url',
					'required' => 1,
				),
			),
		),
		Blocks::text_field( 'contact_note', __( 'Подсказка под «Телефон или email»', 'trust' ), __( 'Только для подтверждения отзыва, на сайте не публикуется', 'trust' ), 100 ),
		Blocks::text_field( 'text_placeholder', __( 'Подсказка в поле «Отзыв»', 'trust' ), __( 'Какая была задача, как шла работа, что получилось', 'trust' ), 100 ),
		Blocks::text_field( 'submit', __( 'Кнопка отправки', 'trust' ), __( 'Отправить отзыв', 'trust' ), 40 ),
		Blocks::text_field( 'success_title', __( 'Заголовок после отправки', 'trust' ), __( 'Спасибо за отзыв!', 'trust' ), 60 ),
		Blocks::text_field( 'success_text', __( 'Текст после отправки', 'trust' ), __( 'Перед публикацией мы свяжемся с вами, чтобы подтвердить отзыв. Контакты на сайте не публикуются.', 'trust' ), 200 ),
		Blocks::text_field( 'again', __( 'Кнопка «ещё один отзыв»', 'trust' ), __( 'Написать ещё один отзыв', 'trust' ), 40 ),
	)
);
