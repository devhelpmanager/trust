<?php
/**
 * Поля блока «Пример галочки согласия» (ТЗ, п. 8.9; макет o-nas/soglasie-pd.html): подписи
 * иллюстрации.
 *
 * @package Trust
 */

defined( 'ABSPATH' ) || exit;

use Trust\Blocks;

return array(
	Blocks::text_field( 'caption', __( 'Подпись (заголовок)', 'trust' ), __( 'Как выглядит согласие в форме', 'trust' ), 80 ),
	Blocks::text_field( 'field', __( 'Поле формы', 'trust' ), __( 'Имя', 'trust' ), 30 ),
	Blocks::text_field( 'check_text', __( 'Текст у галочки', 'trust' ), __( 'Я даю', 'trust' ), 40 ),
	Blocks::text_field( 'check_link', __( 'Подчёркнутая часть (ссылка в настоящей форме)', 'trust' ), __( 'согласие на обработку персональных данных', 'trust' ), 80 ),
	Blocks::text_field( 'button', __( 'Кнопка', 'trust' ), __( 'Отправить', 'trust' ), 30 ),
	array(
		'name'          => 'note',
		'label'         => __( 'Пояснение', 'trust' ),
		'type'          => 'textarea',
		'rows'          => 2,
		'new_lines'     => '',
		'maxlength'     => 200,
		'default_value' => __( 'Поле не отмечено заранее. Пока вы его не отметите, форма не отправится.', 'trust' ),
	),
);
