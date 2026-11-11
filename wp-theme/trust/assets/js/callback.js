/**
 * Окно «Заказать обратный звонок».
 *
 * Открывается любой кнопкой или ссылкой с атрибутом data-trust-callback (шапка,
 * мобильное меню, подвал, блоки). Стандартный <dialog> в модальном режиме: остальная
 * страница недоступна, фокус остаётся в окне, Esc закрывает. Окно закрывается также
 * крестиком, кнопкой «Закрыть» и щелчком по затемнённому фону; фокус возвращается
 * на кнопку, которая открыла окно. Пока окно открыто, страница не прокручивается.
 *
 * Поля проверяются при отправке, сообщения — как в формах макетов. Отправку заявки
 * выполняет модуль форм (шаг 3.5): window.trustForms.submit( form ) → Promise<boolean>.
 * Пока его нет, форма сообщает, что приём заявок ещё не подключён.
 */
( () => {
	const dialog = document.getElementById( 'callback-modal' );
	if ( ! dialog || 'function' !== typeof dialog.showModal ) {
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

	const errorOf = ( input ) => document.getElementById( input.getAttribute( 'aria-describedby' ) );

	function setError( input, message ) {
		const error = errorOf( input );
		if ( message ) {
			input.setAttribute( 'aria-invalid', 'true' );
			error.textContent = message;
			error.hidden = false;
		} else {
			input.removeAttribute( 'aria-invalid' );
			error.textContent = '';
			error.hidden = true;
		}
	}

	function isValid( input ) {
		if ( 'checkbox' === input.type ) {
			return input.checked;
		}
		if ( 'tel' === input.type ) {
			const digits = input.value.replace( /\D/g, '' );
			return digits.length >= 10 && digits.length <= 15;
		}
		return input.value.trim().length > 0;
	}

	function validate( input ) {
		const ok = isValid( input );
		setError( input, ok ? '' : input.dataset.error );
		return ok;
	}

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
		fields.forEach( ( input ) => setError( input, '' ) );
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

		if ( ! window.trustForms || 'function' !== typeof window.trustForms.submit ) {
			showStatus( form.dataset.notConnected || '' );
			return;
		}

		submit.disabled = true;
		window.trustForms.submit( form )
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
