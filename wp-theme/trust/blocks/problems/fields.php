<?php
/**
 * Поля блока «С какими проблемами вы можете столкнуться» (макет holst-blokov/Problems,
 * seo-bloki/07-problemy.html).
 *
 * Поля формы, проверки и сообщения об ошибках — форма «Заявка на аудит»
 * (Trust\Forms\Block_Forms), здесь — тексты и список проблем.
 *
 * @package Trust
 */

use Trust\Blocks;
use Trust\Icons;

defined( 'ABSPATH' ) || exit;

return array_merge(
	Blocks::section_fields(
		array(
			'anchor'  => 'problems',
			'eyebrow' => __( 'Возможные проблемы', 'trust' ),
			'title'   => __( 'С какими проблемами вы можете столкнуться', 'trust' ),
			'lead'    => __( 'Если вы узнали свой сайт хотя бы в одном пункте, причина, скорее всего, в технических ошибках — и аудит поможет их найти.', 'trust' ),
		)
	),
	array(
		array(
			'name'         => 'problems',
			'label'        => __( 'Проблемы', 'trust' ),
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 1,
			'max'          => 8,
			'button_label' => __( 'Добавить проблему', 'trust' ),
			'collapsed'    => 'field_trust_block_problems_problems_title',
			'sub_fields'   => array(
				Icons::field(
					array(
						'choices'       => array(
							'traffic-flat'  => __( 'Трафик стоит на месте', 'trust' ),
							'index-off'     => __( 'Страница не в индексе', 'trust' ),
							'console-alert' => __( 'Ошибки в панели вебмастера', 'trust' ),
							'traffic-drop'  => __( 'Падение трафика', 'trust' ),
							'speed'         => __( 'Скорость загрузки', 'trust' ),
							'search-wrong'  => __( 'Не те страницы в поиске', 'trust' ),
							'robot'         => __( 'Робот (robots.txt)', 'trust' ),
							'sitemap'       => __( 'Карта сайта', 'trust' ),
							'server'        => __( 'Сервер', 'trust' ),
							'copy'          => __( 'Дубли', 'trust' ),
							'mobile'        => __( 'Мобильная версия', 'trust' ),
							'code'          => __( 'Код', 'trust' ),
						),
						'default_value' => 'traffic-flat',
						'wrapper'       => array( 'width' => 35 ),
					)
				),
				array(
					'name'      => 'title',
					'label'     => __( 'Заголовок', 'trust' ),
					'type'      => 'text',
					'required'  => 1,
					'maxlength' => 80,
					'wrapper'   => array( 'width' => 65 ),
				),
				array(
					'name'      => 'text',
					'label'     => __( 'Текст', 'trust' ),
					'type'      => 'textarea',
					'rows'      => 2,
					'new_lines' => '',
					'maxlength' => 240,
				),
			),
		),
		Blocks::text_field( 'form_title', __( 'Подпись над формой', 'trust' ), __( 'Заявка на аудит', 'trust' ), 40 ),
		Blocks::text_field( 'url_placeholder', __( 'Подсказка в поле «Адрес сайта»', 'trust' ), __( 'Адрес сайта', 'trust' ), 40 ),
		Blocks::text_field( 'name_placeholder', __( 'Подсказка в поле «Имя»', 'trust' ), __( 'Ваше имя', 'trust' ), 40 ),
		Blocks::text_field( 'contact_placeholder', __( 'Подсказка в поле контакта', 'trust' ), __( 'Телефон или email', 'trust' ), 40 ),
		Blocks::text_field( 'submit', __( 'Текст кнопки', 'trust' ), __( 'Проверить точки роста на моём сайте', 'trust' ), 60 ),
		Blocks::text_field( 'success_title', __( 'Заявка отправлена: заголовок', 'trust' ), __( 'Заявка отправлена', 'trust' ), 60 ),
		Blocks::text_field( 'success_text', __( 'Заявка отправлена: текст', 'trust' ), __( 'Проверим сайт и свяжемся с вами в течение рабочего дня.', 'trust' ), 160 ),
		Blocks::text_field( 'again', __( 'Кнопка «ещё одна заявка»', 'trust' ), __( 'Отправить ещё одну заявку', 'trust' ), 60 ),
	)
);
