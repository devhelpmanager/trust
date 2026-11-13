/**
 * Блок «Кнопка связи»: свайп-кнопка отправки формы «Обратный звонок» (как в макете
 * holst-stranitsy → ContactSwipe).
 *
 * Ручку тянут вправо: дотянули почти до конца (90 %) — форма проверяется и отправляется
 * (модуль форм window.trustForms: те же правила, что на сервере, REST без перезагрузки);
 * не дотянули — ручка возвращается. С клавиатуры — Enter или пробел на ручке, Enter в поле.
 * Ошибки (пустые поля, нет галочки согласия) — под полями, ручка возвращается, фокус —
 * на первом поле с ошибкой. После отправки — «Заявка отправлена» и кнопка «ещё одна заявка».
 */
( () => {
	document.querySelectorAll( '.b-swipe__form' ).forEach( ( form ) => {
		const track = form.querySelector( '[data-swipe]' );
		const knob = track.querySelector( '.b-swipe__knob' );
		const sent = form.querySelector( '.b-swipe__sent' );
		const again = form.querySelector( '.b-swipe__again' );
		let max = 0;
		let startX = 0;
		let dx = 0;
		let dragging = false;
		let sending = false;

		const limit = () => track.clientWidth - 44;
		const move = ( value ) => {
			dx = Math.max( 0, Math.min( max, value ) );
			track.style.setProperty( '--b-swipe-dx', `${ dx }px` );
			track.style.setProperty( '--b-swipe-p', String( max ? dx / max : 0 ) );
		};
		const back = () => {
			track.classList.remove( 'is-dragging', 'is-sending' );
			move( 0 );
		};

		async function send() {
			if ( sending || ! window.trustForms ) {
				back();
				return;
			}
			max = limit();
			const invalid = window.trustForms.validate( form );
			if ( invalid ) {
				back();
				invalid.focus();
				return;
			}
			sending = true;
			track.classList.remove( 'is-dragging' );
			track.classList.add( 'is-sending' );
			move( max );
			try {
				if ( await window.trustForms.submit( form ) ) {
					form.reset();
					track.hidden = true;
					sent.hidden = false;
					again.hidden = false;
					again.focus();
				}
			} finally {
				sending = false;
				if ( ! track.hidden ) {
					back();
				}
			}
		}

		knob.addEventListener( 'pointerdown', ( event ) => {
			if ( sending ) {
				return;
			}
			try {
				knob.setPointerCapture( event.pointerId );
			} catch ( e ) {}
			max = limit();
			startX = event.clientX - dx;
			dragging = true;
			track.classList.add( 'is-dragging' );
		} );
		knob.addEventListener( 'pointermove', ( event ) => {
			if ( dragging ) {
				move( event.clientX - startX );
			}
		} );
		const release = () => {
			if ( ! dragging ) {
				return;
			}
			dragging = false;
			if ( dx >= max * 0.9 ) {
				send();
			} else {
				back();
			}
		};
		knob.addEventListener( 'pointerup', release );
		knob.addEventListener( 'pointercancel', release );

		// С клавиатуры (Enter, пробел): щелчок без мыши — event.detail === 0.
		knob.addEventListener( 'click', ( event ) => {
			if ( 0 === event.detail ) {
				send();
			}
		} );

		// Enter в поле формы.
		form.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			send();
		} );

		// Ошибка исправлена — сообщение под полем убирается сразу.
		const recheck = ( event ) => {
			if ( event.target.dataset && event.target.dataset.error && event.target.hasAttribute( 'aria-invalid' ) && window.trustForms ) {
				window.trustForms.check( event.target );
			}
		};
		form.addEventListener( 'input', recheck );
		form.addEventListener( 'change', recheck );

		again.addEventListener( 'click', () => {
			sent.hidden = true;
			again.hidden = true;
			track.hidden = false;
			back();
			form.querySelector( '[name="name"]' ).focus();
		} );
	} );
} )();
