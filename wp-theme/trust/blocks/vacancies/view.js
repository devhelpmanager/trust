/**
 * Блок «Открытые вакансии»: раскрытие по одной (как в макете — при открытии вакансии
 * предыдущая сворачивается; нажатие на раскрытую сворачивает её). Кнопка строки — aria-expanded,
 * панель — hidden. Фильтр по отделам — assets/js/filter.js.
 */
( () => {
	document.querySelectorAll( '.b-vac__list' ).forEach( ( list ) => {
		const toggles = [ ...list.querySelectorAll( '.b-vac__toggle' ) ];

		const set = ( toggle, open ) => {
			toggle.setAttribute( 'aria-expanded', String( open ) );
			toggle.closest( '.b-vac__item' ).classList.toggle( 'is-open', open );
			document.getElementById( toggle.getAttribute( 'aria-controls' ) ).hidden = ! open;
		};

		toggles.forEach( ( toggle ) => {
			toggle.addEventListener( 'click', () => {
				const open = 'true' !== toggle.getAttribute( 'aria-expanded' );
				toggles.forEach( ( other ) => {
					if ( other !== toggle && 'true' === other.getAttribute( 'aria-expanded' ) ) {
						set( other, false );
					}
				} );
				set( toggle, open );
			} );
		} );
	} );
} )();
