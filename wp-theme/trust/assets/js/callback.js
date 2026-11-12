/**
 * Окно «Заказать обратный звонок».
 *
 * Открывается любой кнопкой или ссылкой с атрибутом data-trust-callback (шапка,
 * мобильное меню, подвал, блоки). Стандартный <dialog> в модальном режиме: остальная
 * страница недоступна, фокус остаётся в окне, Esc закрывает. Окно закрывается также
 * крестиком, кнопкой «Закрыть» и щелчком по затемнённому фону; фокус возвращается
 * на кнопку, которая открыла окно. Пока окно открыто, страница не прокручивается.
 *
 * Поля проверяются при отправке, сообщения — как в формах макетов; проверку и отправку
 * заявки выполняет модуль форм (forms.js, window.trustForms). После отправки окно
 * показывает «Заявка отправлена»; при следующем открытии — снова пустая форма.
 */
( () => {
	const dialog = document.getElementById( 'callback-modal' );
	if ( ! dialog || 'function' !== typeof dialog.showModal || ! window.trustForms ) {
		return;
	}

	const form = dialog.querySelector( 'form' );
	const formView = dialog.querySelector( '.callback-modal__form-view' );
	const success = dialog.querySelector( '.callback-modal__success' );
	const status = form.querySelector( '.form__status' );
	const submit = form.querySelector( '[type="submit"]' );
	const fields = [ ...form.querySelectorAll( '[data-error]' ) ];
	let opener = null;
	let pressedOnBackdrop = false;

	// Без JavaScript работает проверка браузера (required), с ним — своя, с сообщениями.
	form.noValidate = true;

	const forms = window.trustForms;
	const validate = ( input ) => forms.check( input );

	function showStatus( message ) {
		status.textContent = message;
		status.hidden = ! message;
	}

	function showSuccess() {
		form.reset();
		formView.hidden = true;
		success.hidden = false;
		success.querySelector( 'button' ).focus();
	}

	function open( trigger ) {
		opener = trigger;
		formView.hidden = false;
		success.hidden = true;
		showStatus( '' );
		fields.forEach( ( input ) => forms.setError( input, '' ) );
		dialog.showModal();
		document.documentElement.classList.add( 'has-modal' );
		fields[ 0 ].focus();
	}

	document.addEventListener( 'click', ( event ) => {
		const trigger = event.target.closest( '[data-trust-callback]' );
		if ( trigger ) {
			event.preventDefault();
			open( trigger );
		}
	} );

	// Щелчок по фону: и нажатие, и отпускание — на самом <dialog> (не внутри окна).
	dialog.addEventListener( 'pointerdown', ( event ) => {
		pressedOnBackdrop = event.target === dialog;
	} );
	dialog.addEventListener( 'click', ( event ) => {
		if ( ( event.target === dialog && pressedOnBackdrop ) || event.target.closest( '[data-callback-close]' ) ) {
			dialog.close();
		}
	} );

	dialog.addEventListener( 'close', () => {
		document.documentElement.classList.remove( 'has-modal' );
		if ( opener && opener.isConnected ) {
			opener.focus();
		}
		opener = null;
	} );

	fields.forEach( ( input ) => {
		input.addEventListener( 'checkbox' === input.type ? 'change' : 'input', () => {
			if ( input.hasAttribute( 'aria-invalid' ) ) {
				validate( input );
			}
		} );
	} );

	form.addEventListener( 'submit', ( event ) => {
		event.preventDefault();
		showStatus( '' );

		const invalid = fields.filter( ( input ) => ! validate( input ) );
		if ( invalid.length ) {
			invalid[ 0 ].focus();
			return;
		}

		submit.disabled = true;
		forms.submit( form )
			.then( ( ok ) => {
				if ( ok ) {
					showSuccess();
				}
			} )
			.finally( () => {
				submit.disabled = false;
			} );
	} );
} )();
