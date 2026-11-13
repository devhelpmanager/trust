<?php
/**
 * Поля блока «Кнопка связи» (макет holst-stranitsy/Main → ContactSwipe, seo-bloki/09-knopka-svyazi.html).
 *
 * Поля формы, проверки и сообщения об ошибках — форма «Обратный звонок» (Trust\Forms::types()),
 * здесь — только тексты.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

$trust_text = static fn( string $name, string $label, string $value, int $max = 80, string $help = '' ): array => array(
	'name'          => $name,
	'label'         => $label,
	'type'          => 'text',
	'default_value' => $value,
	'maxlength'     => $max,
	'instructions'  => $help,
);

return array_merge(
	Trust\Blocks::section_fields(
		array(
			'anchor'  => 'callback',
			'eyebrow' => __( 'Связаться с нами', 'trust' ),
			'title'   => __( 'Перезвоним и ответим на ваши вопросы', 'trust' ),
			'lead'    => __( 'Оставьте имя и телефон — специалист свяжется с вами и поможет выбрать услугу.', 'trust' ),
		)
	),
	array(
		$trust_text( 'form_title', __( 'Подпись над формой', 'trust' ), __( 'Обратный звонок', 'trust' ), 40 ),
		$trust_text( 'name_placeholder', __( 'Подсказка в поле «Имя»', 'trust' ), __( 'Ваше имя', 'trust' ), 40 ),
		$trust_text( 'phone_placeholder', __( 'Подсказка в поле «Телефон»', 'trust' ), Trust\Forms::phone_example(), 40 ),
		$trust_text( 'swipe_label', __( 'Надпись на свайп-кнопке', 'trust' ), __( 'Свайпните', 'trust' ), 20, __( 'Коротко: кнопка 224 px.', 'trust' ) ),
		$trust_text( 'sending', __( 'Пока заявка отправляется', 'trust' ), __( 'Отправка…', 'trust' ), 20 ),
		$trust_text( 'success_title', __( 'Заявка отправлена: заголовок', 'trust' ), __( 'Заявка отправлена', 'trust' ), 60 ),
		$trust_text( 'success_text', __( 'Заявка отправлена: текст', 'trust' ), __( 'Мы перезвоним вам в ближайшее время', 'trust' ), 120 ),
		$trust_text( 'again', __( 'Кнопка «ещё одна заявка»', 'trust' ), __( 'Отправить ещё одну заявку', 'trust' ), 60 ),
	)
);
