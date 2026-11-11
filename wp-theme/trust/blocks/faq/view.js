/**
 * Блок «Вопрос-ответ»: раскрытие ответов по нажатию на вопрос.
 *
 * Как в макете (holst-blokov/Faq): каждый ответ раскрывается независимо, открытыми
 * могут быть несколько. Кнопка вопроса — aria-expanded, ответ — hidden.
 * Работает с клавиатуры (Enter и пробел на кнопке).
 */
( () => {
	document.querySelectorAll( '.b-faq__toggle' ).forEach( ( toggle ) => {
		toggle.addEventListener( 'click', () => {
			const item = toggle.closest( '.b-faq__item' );
			const answer = document.getElementById( toggle.getAttribute( 'aria-controls' ) );
			const open = 'true' !== toggle.getAttribute( 'aria-expanded' );

			toggle.setAttribute( 'aria-expanded', String( open ) );
			item.classList.toggle( 'is-open', open );
			answer.hidden = ! open;
		} );
	} );
} )();
