/**
 * Админка, форма ответа на комментарий («Комментарии → Ответить», комментарии в записи,
 * «Активность» в консоли): список «Ответить от имени».
 *
 * Форма ядра (#replyrow) отправляет только поля <input>, поэтому выбор копируется в скрытое
 * поле trust_reply_as. Сервер (Trust\Comments::reply_as) ставит автором выбранного пользователя.
 * При редактировании комментария («Свойства») список скрыт и значение очищено.
 *
 * Данные — window.trustReplyAs: { label, options: [ { value, label } ] }.
 */
( function ( $ ) {
	'use strict';

	const data = window.trustReplyAs;

	$( function () {
		const row = $( '#replyrow' );
		if ( ! data || ! row.length || ! window.commentReply ) {
			return;
		}

		const box = $( '<p class="trust-reply-as"></p>' );
		const label = $( '<label for="trust-reply-as"></label>' ).text( data.label + ': ' );
		const select = $( '<select id="trust-reply-as"></select>' );
		const hidden = $( '<input type="hidden" name="trust_reply_as" value="">' );
		data.options.forEach( function ( option ) {
			select.append( $( '<option></option>' ).val( option.value ).text( option.label ) );
		} );
		box.append( label, select, hidden ).css( { margin: '8px 0 0' } );
		select.css( { maxWidth: '100%', marginLeft: '4px' } );
		row.find( '#replysubmit' ).before( box );

		const sync = function () {
			hidden.val( box.prop( 'hidden' ) ? '' : select.val() );
		};
		select.on( 'change', sync );

		// Последний выбор сохраняется до перезагрузки страницы.
		const open = window.commentReply.open;
		window.commentReply.open = function ( commentId, postId, action ) {
			const edit = 'edit' === action;
			box.prop( 'hidden', edit ).toggle( ! edit );
			sync();
			return open.apply( this, arguments );
		};
		sync();
	} );
} )( window.jQuery );
