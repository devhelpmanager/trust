/**
 * Блок «Что включает услуга»: раскрытие подробностей работы по нажатию на строку.
 *
 * Как в макете (holst-blokov/ServiceIncludes): каждая работа раскрывается независимо,
 * открытыми могут быть несколько. Кнопка — aria-expanded, подробности — hidden.
 * Работает с клавиатуры (Enter и пробел на кнопке).
 */
( () => {
	document.querySelectorAll( '.b-includes__toggle' ).forEach( ( toggle ) => {
		toggle.addEventListener( 'click', () => {
			const item = toggle.closest( '.b-includes__item' );
			const panel = document.getElementById( toggle.getAttribute( 'aria-controls' ) );
			const open = 'true' !== toggle.getAttribute( 'aria-expanded' );

			toggle.setAttribute( 'aria-expanded', String( open ) );
			item.classList.toggle( 'is-open', open );
			panel.hidden = ! open;
		} );
	} );
} )();
