/**
 * «Первый экран вопросов»: счётчик символов вопроса и адрес в сообщении «Вопрос отправлен».
 * Отправку, проверку полей и экран успеха выполняет модуль форм (assets/js/forms.js).
 */
( () => {
	document.querySelectorAll( '.b-hero-questions__form' ).forEach( ( form ) => {
		const text = form.querySelector( 'textarea' );
		const count = form.querySelector( '[data-trust-count]' );
		const update = () => {
			count.textContent = String( text.value.length );
		};
		text.addEventListener( 'input', update );
		form.addEventListener( 'reset', () => setTimeout( update ) );
		update();

		// До сброса формы: адрес, на который придёт ссылка на ответ.
		form.addEventListener( 'trust:form-sent', () => {
			const email = document.querySelector( `#${ form.dataset.success } [data-trust-sent-email]` );
			if ( email ) {
				email.textContent = form.elements.email.value.trim();
			}
		} );
	} );
} )();
