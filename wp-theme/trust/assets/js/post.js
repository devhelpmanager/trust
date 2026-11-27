/**
 * Статья блога (single-post.php): просмотры, «Нравится», содержание.
 *
 * Просмотры: после загрузки — POST trust/v1/posts/{id}/view не чаще раза в сутки с браузера
 * (метка в localStorage), иначе GET — свежие числа (страница могла прийти из кеша).
 * «Нравится»: нажатие ставит или снимает лайк (POST …/like); лайкнутые статьи браузер помнит
 * в localStorage, сервер — в cookie trust_likes. Все кнопки «Нравится» и счётчик в шапке
 * статьи обновляются вместе. Лайки комментариев — так же (POST trust/v1/comments/{id}/like,
 * cookie trust_comment_likes); свежие числа приходят вместе с числами статьи.
 * Содержание: текущий раздел (aria-current), «Прочитано N %» и полоса прогресса; до 1024 px —
 * полоса вверху экрана, раскрывается по нажатию, закрывается по выбору пункта и Esc.
 *
 * Данные страницы — window.trustPost = { id, rest } (Trust\Article::enqueue()).
 */
( function () {
	'use strict';

	const data = window.trustPost || {};
	const id = parseInt( data.id, 10 );
	if ( ! id || ! data.rest ) {
		return;
	}
	const base = data.rest + id;
	const LIKES_KEY = 'trust_likes';
	const VIEWS_KEY = 'trust_viewed';

	const store = {
		get( key, fallback ) {
			try {
				const value = JSON.parse( window.localStorage.getItem( key ) );
				return null === value ? fallback : value;
			} catch ( e ) {
				return fallback;
			}
		},
		set( key, value ) {
			try {
				window.localStorage.setItem( key, JSON.stringify( value ) );
			} catch ( e ) {}
		},
	};

	// ---------- Числа и склонения ----------

	const number = ( n ) => String( n ).replace( /\B(?=(\d{3})+(?!\d))/g, ' ' );

	function plural( n, forms ) {
		const n10 = Math.abs( n ) % 10;
		const n100 = Math.abs( n ) % 100;
		if ( 1 === n10 && 11 !== n100 ) {
			return forms[ 0 ];
		}
		if ( n10 >= 2 && n10 <= 4 && ( n100 < 12 || n100 > 14 ) ) {
			return forms[ 1 ];
		}
		return forms[ 2 ];
	}

	function setCount( kind, n ) {
		if ( 'number' !== typeof n ) {
			return;
		}
		document.querySelectorAll( '[data-post-count="' + kind + '"]' ).forEach( ( el ) => {
			const forms = ( el.dataset.forms || '' ).split( '|' );
			el.textContent = number( n ) + ( 3 === forms.length ? ' ' + plural( n, forms ) : '' );
		} );
		if ( 'likes' === kind ) {
			document.querySelectorAll( '[data-post-count="likes-number"]' ).forEach( ( el ) => ( el.textContent = number( n ) ) );
		}
	}

	// ---------- Лайки ----------

	const likeButtons = document.querySelectorAll( '[data-post-like]' );
	const heroLikes = document.querySelector( '.post-hero__count--likes' );

	function cookieLiked() {
		const match = document.cookie.match( /(?:^|;\s*)trust_likes=([^;]*)/ );
		return match ? decodeURIComponent( match[ 1 ] ).split( '.' ).map( Number ).includes( id ) : false;
	}

	function storedLiked() {
		const ids = store.get( LIKES_KEY, [] );
		return Array.isArray( ids ) && ids.includes( id );
	}

	function remember( liked ) {
		let ids = store.get( LIKES_KEY, [] );
		ids = Array.isArray( ids ) ? ids.filter( ( item ) => item !== id ) : [];
		if ( liked ) {
			ids.push( id );
		}
		store.set( LIKES_KEY, ids.slice( -200 ) );
	}

	// Число лайков со страницы (до ответа сервера), null — лайки не показаны.
	function pageLikes() {
		const el = document.querySelector( '[data-post-count="likes-number"], [data-post-count="likes"]' );
		const digits = el ? el.textContent.replace( /\D+/g, '' ) : '';
		return '' === digits ? null : parseInt( digits, 10 );
	}

	let liked = cookieLiked() || storedLiked();
	let likes = pageLikes();
	let busy = false;

	function showLiked() {
		likeButtons.forEach( ( button ) => button.setAttribute( 'aria-pressed', liked ? 'true' : 'false' ) );
		if ( heroLikes ) {
			heroLikes.classList.toggle( 'is-liked', liked );
		}
	}

	function apply( stats ) {
		if ( ! stats ) {
			return;
		}
		setCount( 'views', stats.views );
		if ( stats.comments && 'object' === typeof stats.comments ) {
			document.querySelectorAll( '[data-comment-like]' ).forEach( ( button ) => {
				const value = stats.comments[ button.dataset.commentLike ];
				if ( 'number' === typeof value && ! commentBusy.has( Number( button.dataset.commentLike ) ) ) {
					button.querySelector( '[data-comment-likes]' ).textContent = number( value );
				}
			} );
		}
		if ( 'number' === typeof stats.likes ) {
			likes = stats.likes;
			setCount( 'likes', likes );
		}
	}

	function request( path, body ) {
		return window
			.fetch( base + path, {
				method: body ? 'POST' : 'GET',
				credentials: 'same-origin',
				headers: body ? { 'Content-Type': 'application/json' } : {},
				body: body ? JSON.stringify( body ) : undefined,
			} )
			.then( ( response ) => ( response.ok ? response.json() : Promise.reject( response.status ) ) );
	}

	likeButtons.forEach( ( button ) =>
		button.addEventListener( 'click', () => {
			if ( busy ) {
				return;
			}
			busy = true;
			const before = { liked, likes };
			liked = ! liked;
			if ( null !== likes ) {
				likes = Math.max( 0, likes + ( liked ? 1 : -1 ) );
				setCount( 'likes', likes );
			}
			showLiked();
			remember( liked );
			request( '/like', { liked } )
				.then( apply )
				.catch( () => {
					liked = before.liked;
					likes = before.likes;
					showLiked();
					remember( liked );
					if ( null !== likes ) {
						setCount( 'likes', likes );
					}
				} )
				.then( () => ( busy = false ) );
		} )
	);

	// ---------- Лайки комментариев ----------

	const COMMENT_LIKES_KEY = 'trust_comment_likes';
	const commentButtons = document.querySelectorAll( '[data-comment-like]' );

	function cookieIds( name ) {
		const match = document.cookie.match( new RegExp( '(?:^|;\\s*)' + name + '=([^;]*)' ) );
		return match ? decodeURIComponent( match[ 1 ] ).split( '.' ).map( Number ).filter( Boolean ) : [];
	}

	function storedIds( key ) {
		const ids = store.get( key, [] );
		return Array.isArray( ids ) ? ids : [];
	}

	const likedComments = new Set( [ ...cookieIds( 'trust_comment_likes' ), ...storedIds( COMMENT_LIKES_KEY ) ] );
	const commentBusy = new Set();

	function showComment( button, likesCount ) {
		const cid = Number( button.dataset.commentLike );
		button.setAttribute( 'aria-pressed', likedComments.has( cid ) ? 'true' : 'false' );
		if ( 'number' === typeof likesCount ) {
			button.querySelector( '[data-comment-likes]' ).textContent = number( likesCount );
		}
	}

	function rememberComments() {
		store.set( COMMENT_LIKES_KEY, [ ...likedComments ].slice( -200 ) );
	}

	commentButtons.forEach( ( button ) => {
		showComment( button );
		button.addEventListener( 'click', () => {
			const cid = Number( button.dataset.commentLike );
			if ( commentBusy.has( cid ) ) {
				return;
			}
			commentBusy.add( cid );
			const counter = button.querySelector( '[data-comment-likes]' );
			const before = parseInt( counter.textContent.replace( /\D+/g, '' ), 10 ) || 0;
			const want = ! likedComments.has( cid );
			if ( want ) {
				likedComments.add( cid );
			} else {
				likedComments.delete( cid );
			}
			showComment( button, Math.max( 0, before + ( want ? 1 : -1 ) ) );
			rememberComments();
			window
				.fetch( data.comments + cid + '/like', {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify( { liked: want } ),
				} )
				.then( ( response ) => ( response.ok ? response.json() : Promise.reject( response.status ) ) )
				.then( ( result ) => showComment( button, result.likes ) )
				.catch( () => {
					if ( want ) {
						likedComments.delete( cid );
					} else {
						likedComments.add( cid );
					}
					rememberComments();
					showComment( button, before );
				} )
				.then( () => commentBusy.delete( cid ) );
		} );
	} );

	// ---------- Просмотры (и свежие числа) ----------

	const today = new Date().toISOString().slice( 0, 10 );
	const viewed = store.get( VIEWS_KEY, {} );
	const counted = viewed && 'object' === typeof viewed && viewed[ id ] === today;
	const ready = request( counted ? '' : '/view', counted ? null : {} ).then( ( stats ) => {
		if ( ! counted ) {
			// Метки старше суток не нужны.
			const fresh = {};
			Object.keys( viewed && 'object' === typeof viewed ? viewed : {} ).forEach( ( key ) => {
				if ( viewed[ key ] === today ) {
					fresh[ key ] = today;
				}
			} );
			fresh[ id ] = today;
			store.set( VIEWS_KEY, fresh );
		}
		return stats;
	} );
	ready.then( apply ).catch( () => {} );
	showLiked();

	// ---------- Содержание ----------

	const article = document.getElementById( 'post-article' );
	const links = Array.from( document.querySelectorAll( '.post-toc__link' ) );
	const ids = [];
	links.forEach( ( link ) => {
		const target = decodeURIComponent( link.hash.slice( 1 ) );
		if ( target && ! ids.includes( target ) ) {
			ids.push( target );
		}
	} );
	const sections = ids.map( ( key ) => document.getElementById( key ) ).filter( Boolean );
	if ( ! article || ! sections.length ) {
		return;
	}
	const progressNodes = document.querySelectorAll( '[data-post-progress]' );
	const fills = document.querySelectorAll( '.post-toc__fill' );
	const current = document.querySelector( '[data-post-current]' );
	const header = document.querySelector( '.site-header' );
	const bar = document.querySelector( '.post-tocbar' );
	const toggle = bar ? bar.querySelector( '.post-tocbar__toggle' ) : null;
	const panel = bar ? bar.querySelector( '.post-tocbar__panel' ) : null;
	let active = null;
	let progress = -1;
	let queued = false;

	// Линия чтения: под шапкой и мобильной полосой содержания, но не ниже трети экрана.
	function readingLine() {
		let top = header ? Math.max( 0, header.getBoundingClientRect().bottom ) : 0;
		if ( bar && bar.offsetParent ) {
			top = Math.max( top, bar.getBoundingClientRect().bottom );
		}
		return top + Math.min( 160, window.innerHeight * 0.3 );
	}

	function spy() {
		queued = false;
		const line = readingLine();
		let now = sections[ 0 ];
		sections.forEach( ( section ) => {
			if ( section.getBoundingClientRect().top <= line ) {
				now = section;
			}
		} );
		const rect = article.getBoundingClientRect();
		const total = rect.height - line;
		const value = total > 0 ? Math.round( Math.min( 1, Math.max( 0, ( line - rect.top ) / total ) ) * 100 ) : 0;

		if ( now !== active ) {
			active = now;
			links.forEach( ( link ) => {
				if ( decodeURIComponent( link.hash.slice( 1 ) ) === now.id ) {
					link.setAttribute( 'aria-current', 'location' );
				} else {
					link.removeAttribute( 'aria-current' );
				}
			} );
			if ( current ) {
				const link = links.find( ( item ) => decodeURIComponent( item.hash.slice( 1 ) ) === now.id );
				current.textContent = link ? link.lastChild.textContent : now.textContent;
			}
		}
		if ( value !== progress ) {
			progress = value;
			progressNodes.forEach( ( node ) => ( node.textContent = String( value ) ) );
			fills.forEach( ( fill ) => fill.style.setProperty( '--post-progress', String( value / 100 ) ) );
		}
	}

	function queue() {
		if ( ! queued ) {
			queued = true;
			window.requestAnimationFrame( spy );
		}
	}

	window.addEventListener( 'scroll', queue, { passive: true } );
	window.addEventListener( 'resize', queue );
	spy();

	// Мобильная полоса: раскрыть / закрыть список.
	function setOpen( open ) {
		if ( ! toggle || ! panel ) {
			return;
		}
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		panel.hidden = ! open;
		if ( open ) {
			const cur = panel.querySelector( '[aria-current]' );
			if ( cur ) {
				panel.scrollTop = Math.max( 0, cur.offsetTop - ( ( panel.clientHeight - cur.offsetHeight ) / 2 ) );
			}
		}
	}

	if ( toggle && panel ) {
		toggle.addEventListener( 'click', () => setOpen( 'true' !== toggle.getAttribute( 'aria-expanded' ) ) );
		panel.addEventListener( 'click', ( event ) => {
			if ( event.target.closest( 'a' ) ) {
				setOpen( false );
			}
		} );
		document.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' === event.key && ! panel.hidden ) {
				setOpen( false );
				toggle.focus();
			}
		} );
	}
} )();
