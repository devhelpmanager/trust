<?php
/**
 * Форма «Отправить резюме» (ТЗ, раздел 12.1; блок trust/resume-form на странице «Карьера»).
 *
 * Резюме — обычная заявка «Резюме» (те же защита, уведомления, CSV и срок хранения): имя и
 * фамилия, телефон или email, вакансия (открытые вакансии, «Стажировка», «Без вакансии»),
 * ссылка на резюме или портфолио (в заявке — ссылкой), о себе. Сообщения — как в макете.
 *
 * @package Trust
 */

namespace Trust\Forms;

use Trust\Vacancies;

defined( 'ABSPATH' ) || exit;

/**
 * Форма резюме.
 */
final class Resume_Form {

	/**
	 * Тип формы.
	 */
	public const TYPE = 'resume';

	/**
	 * Значение пункта «Без вакансии — инициативное резюме».
	 */
	public const NO_VACANCY = 'none';

	/**
	 * Регистрирует хуки.
	 */
	public static function register(): void {
		add_filter( 'trust_form_types', array( self::class, 'types' ) );
	}

	/**
	 * Варианты поля «Вакансия»: без вакансии, открытые вакансии (значение — адрес записи),
	 * стажировка.
	 *
	 * @return array<string, string> значение => подпись.
	 */
	public static function vacancies(): array {
		$options = array( self::NO_VACANCY => __( 'Без вакансии — инициативное резюме', 'trust' ) );
		foreach ( Vacancies::open_posts() as $post ) {
			$options[ Vacancies::form_value( $post ) ] = get_the_title( $post );
		}
		$options[ Vacancies::INTERN ] = __( 'Стажировка', 'trust' );
		return $options;
	}

	/**
	 * Описание формы.
	 *
	 * @param array $types Формы сайта.
	 */
	public static function types( array $types ): array {
		$types[ self::TYPE ] = array(
			'label'    => __( 'Резюме', 'trust' ),
			'captcha'  => true,
			'fields'   => array(
				'name'    => array(
					'label'    => __( 'Имя и фамилия', 'trust' ),
					'type'     => 'text',
					'required' => true,
					'max'      => 100,
					'error'    => __( 'Укажите имя и фамилию', 'trust' ),
				),
				'contact' => array(
					'label'    => __( 'Телефон или email', 'trust' ),
					'type'     => 'contact',
					'required' => true,
					'max'      => 100,
					'error'    => __( 'Оставьте телефон или email', 'trust' ),
				),
				'vacancy' => array(
					'label'   => __( 'Вакансия', 'trust' ),
					'type'    => 'choice',
					'options' => self::vacancies(),
					'error'   => __( 'Выберите вакансию из списка', 'trust' ),
				),
				'link'    => array(
					'label'    => __( 'Ссылка на резюме или портфолио', 'trust' ),
					'type'     => 'url',
					'required' => true,
					'max'      => 300,
					'error'    => __( 'Добавьте ссылку на резюме или портфолио', 'trust' ),
				),
				'about'   => array(
					'label' => __( 'О себе', 'trust' ),
					'type'  => 'textarea',
					'max'   => 2000,
					'error' => __( 'Не длиннее 2000 символов', 'trust' ),
				),
			),
			'consents' => array(
				'consent' => array(
					'label' => __( 'Согласие на обработку персональных данных', 'trust' ),
					'error' => __( 'Отметьте согласие, чтобы отправить резюме', 'trust' ),
				),
			),
			'name'     => 'name',
			'contact'  => array( 'contact' ),
		);
		return $types;
	}
}
