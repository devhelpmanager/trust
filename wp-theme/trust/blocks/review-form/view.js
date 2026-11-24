/**
 * Блок «Оставьте отзыв»: подпись выбранной оценки («Плохо» … «Отлично») рядом со звёздами;
 * после выбора звезды сообщение «Поставьте оценку» убирается (ошибка группы — у первого
 * переключателя). Звёзды закрашиваются стилями (:has), проверку и отправку формы выполняет модуль
 * форм (forms.js); после «Написать ещё один отзыв» форма очищается — подпись возвращается.
 */
( () => {
	document.querySelectorAll( '.b-rform__form' ).forEach( ( form ) => {
		const word = form.querySelector( '[data-rating-word]' );
		if ( ! word ) {
			return;
		}
		const show = () => {
			const checked = form.querySelector( 'input[name="rating"]:checked' );
			word.textContent = checked ? checked.dataset.word : word.dataset.empty;
		};
		const first = form.querySelector( 'input[name="rating"][data-error]' );
		form.addEventListener( 'change', ( event ) => {
			if ( 'rating' !== event.target.name ) {
				return;
			}
			show();
			if ( first && first.hasAttribute( 'aria-invalid' ) && window.trustForms ) {
				window.trustForms.check( first );
			}
		} );
		form.addEventListener( 'reset', () => setTimeout( show ) );
	} );
} )();
