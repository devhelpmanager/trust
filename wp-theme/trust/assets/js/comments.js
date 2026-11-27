/**
 * Комментарии статьи (comments.php): отправка формы, ответ под комментарием, «Показать ещё».
 *
 * Формы — общий механизм форм сайта (window.trustForms из forms.js: проверка полей, токен,
 * отправка на /wp-json/trust/v1/forms/comment). Кнопка «Отправить» неактивна, пока комментарий
 * пустой (как в макете). Принят на модерацию — вместо формы «Спасибо! Комментарий появится после
 * проверки модератором.» («Написать ещё» возвращает форму); опубликован сразу (комментирует
 * модератор) — страница открывается на нём.
 * «Ответить» переносит общую форму ответа под комментарий (ответы — до двух уровней); «Отмена»
 * и Esc закрывают её, фокус возвращается на кнопку. «Показать ещё N комментариев» открывает
 * остальные ветки; ссылка на скрытый комментарий (#comment-N) открывает их сразу.
 * Лайки комментариев — assets/js/post.js.
 */
( () => {
	const section = document.querySelector( '.post-comments' );
	const forms = window.trustForms;
	if ( ! section || ! forms ) {
		return;
	}
	const main = document.getElementById( 'comment-form' );
	const sent = document.getElementById( 'comment-sent' );
	const replyBox = document.getElementById( 'comment-reply' );
	const replyForm = document.getElementById( 'comment-reply-form' );
	const replySent = document.getElementById( 'comment-reply-sent' );
	let replyButton = null;

	// --- Кнопка «Отправить» неактивна, пока комментарий пустой ---

	function syncButton( form ) {
		const text = form.querySelector( 'textarea[name="comment"]' );
		const button = form.querySelector( '[type="submit"]' );
		if ( text && button && 'true' !== button.getAttribute( 'aria-busy' ) ) {
			button.disabled = '' === text.value.trim();
		}
	}

	[ main, replyForm ].filter( Boolean ).forEach( ( form ) => {
		const text = form.querySelector( 'textarea[name="comment"]' );
		text.addEventListener( 'input', () => syncButton( form ) );
		syncButton( form );
		// Ошибка поля снимается, как только поле исправлено.
		const recheck = ( event ) => {
			if ( event.target.hasAttribute && event.target.hasAttribute( 'aria-invalid' ) && event.target.dataset.error ) {
				forms.check( event.target );
			}
		};
		form.addEventListener( 'input', recheck );
		form.addEventListener( 'change', recheck );
	} );

	// --- Отправка ---

	async function send( form, done ) {
		const invalid = forms.validate( form );
		if ( invalid ) {
			invalid.focus();
			return;
		}
		const button = form.querySelector( '[type="submit"]' );
		button.setAttribute( 'aria-busy', 'true' );
		button.disabled = true;
		let detail = {};
		const listener = ( event ) => ( detail = event.detail || {} );
		form.addEventListener( 'trust:form-sent', listener );
		const ok = await forms.submit( form );
		form.removeEventListener( 'trust:form-sent', listener );
		button.removeAttribute( 'aria-busy' );
		button.disabled = false;
		if ( ! ok ) {
			return;
		}
		// Опубликован сразу — открыть страницу на нём (новый адрес, чтобы страница обновилась).
		if ( detail.approved && detail.url ) {
			const url = new URL( detail.url, location.href );
			url.searchParams.set( 'comment', String( detail.comment ) );
			location.assign( url.toString() );
			return;
		}
		form.querySelector( 'textarea[name="comment"]' ).value = '';
		const consent = form.querySelector( 'input[name="consent"]' );
		if ( consent ) {
			consent.checked = false;
		}
		syncButton( form );
		done();
	}

	if ( main && sent ) {
		main.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			send( main, () => {
				main.hidden = true;
				sent.hidden = false;
				sent.focus();
			} );
		} );
		const again = sent.querySelector( '[data-comment-again]' );
		if ( again ) {
			again.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				sent.hidden = true;
				main.hidden = false;
				main.querySelector( 'textarea[name="comment"]' ).focus();
			} );
		}
	}

	// --- Ответ под комментарием ---

	function closeReply( focusButton ) {
		if ( ! replyBox || replyBox.hidden ) {
			return;
		}
		replyBox.hidden = true;
		if ( replyButton ) {
			replyButton.setAttribute( 'aria-expanded', 'false' );
			if ( focusButton ) {
				replyButton.focus();
			}
		}
	}

	function openReply( button ) {
		const comment = button.closest( '.comment' );
		const slot = comment && comment.querySelector( ':scope > .comment__body > .comment__slot' );
		if ( ! slot ) {
			return;
		}
		if ( replyButton && replyButton !== button ) {
			replyButton.setAttribute( 'aria-expanded', 'false' );
		}
		replyButton = button;
		replySent.hidden = true;
		slot.appendChild( replyBox );
		replyForm.elements.comment_parent.value = button.dataset.commentReply;
		// Имя и email — из основной формы, если там уже заполнены.
		[ 'name', 'email' ].forEach( ( name ) => {
			if ( main && ! replyForm.elements[ name ].value && main.elements[ name ].value ) {
				replyForm.elements[ name ].value = main.elements[ name ].value;
			}
		} );
		replyBox.hidden = false;
		button.setAttribute( 'aria-expanded', 'true' );
		replyForm.elements.comment.focus();
	}

	if ( replyBox && replyForm && replySent ) {
		section.addEventListener( 'click', ( event ) => {
			const button = event.target.closest( '[data-comment-reply]' );
			if ( ! button ) {
				return;
			}
			if ( button === replyButton && ! replyBox.hidden ) {
				closeReply( false );
			} else {
				openReply( button );
			}
		} );
		replyForm.querySelector( '[data-comment-cancel]' ).addEventListener( 'click', () => closeReply( true ) );
		replyForm.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' === event.key ) {
				closeReply( true );
			}
		} );
		replyForm.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			send( replyForm, () => {
				const slot = replyBox.parentElement;
				closeReply( false );
				slot.appendChild( replySent );
				replySent.hidden = false;
				replySent.focus();
			} );
		} );
	}

	// --- «Показать ещё N комментариев» ---

	const more = section.querySelector( '[data-comments-more]' );

	function showAll( focus ) {
		const hidden = [ ...section.querySelectorAll( '.comments > .is-more' ) ];
		hidden.forEach( ( item ) => item.classList.remove( 'is-more' ) );
		if ( more ) {
			more.remove();
		}
		if ( focus && hidden[ 0 ] ) {
			hidden[ 0 ].tabIndex = -1;
			hidden[ 0 ].focus();
		}
	}

	if ( more ) {
		more.addEventListener( 'click', () => showAll( true ) );
	}

	// Ссылка на скрытый комментарий: открыть все и прокрутить к нему.
	function revealHash() {
		const target = /^#comment-\d+$/.test( location.hash ) ? document.querySelector( location.hash ) : null;
		if ( target && target.closest( '.is-more' ) ) {
			showAll( false );
			target.scrollIntoView();
		}
	}
	revealHash();
	window.addEventListener( 'hashchange', revealHash );
} )();
