/**
 * Блок «Расскажите о задаче»: текст после отправки — по выбранному способу связи («Менеджер
 * позвонит по телефону…», «…напишет в Telegram…»). Шаблон — data-success-template формы,
 * окончание фразы — data-say переключателя. Проверка и отправка — модуль форм (forms.js).
 */
( () => {
	document.querySelectorAll( '.b-cform__form[data-success-template]' ).forEach( ( form ) => {
		const sent = document.getElementById( form.dataset.success );
		const text = sent && sent.querySelector( '.form-success__text' );
		if ( ! text ) {
			return;
		}
		const update = () => {
			const checked = form.querySelector( 'input[name="channel"]:checked' );
			if ( checked ) {
				text.textContent = form.dataset.successTemplate.replace( '{channel}', checked.dataset.say );
			}
		};
		form.addEventListener( 'change', ( event ) => {
			if ( 'channel' === event.target.name ) {
				update();
			}
		} );
		form.addEventListener( 'reset', () => setTimeout( update ) );
	} );
} )();
