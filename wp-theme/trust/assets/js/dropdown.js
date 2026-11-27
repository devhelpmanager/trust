/**
 * Выпадающий список в стиле сайта вместо системного: <select data-dropdown> получает кнопку с
 * ролью combobox и панель вариантов role="listbox" (шаблон WAI-ARIA «Select-Only Combobox»).
 * <select> остаётся в разметке невидимым: в нём значение (выбор отправляет ему событие change,
 * как обычный список) и от него ширина кнопки. Число у варианта — атрибут data-count у <option>
 * (при 0 вариант приглушён).
 * Если скрипт страницы сам поменял значение или числа, он отправляет <select> событие
 * «trust-dropdown:update».
 *
 * Клавиши: ↓ ↑ Home End PageUp PageDown — по вариантам (закрытый список они открывают), Enter и
 * пробел — выбрать, Esc — закрыть без выбора, Tab — выбрать и перейти дальше, буквы — к варианту,
 * который с них начинается. Фокус всё время на кнопке (aria-activedescendant).
 */
( () => {
	let uid = 0;

	const enhance = ( select ) => {
		if ( select.dataset.dropdownReady ) {
			return;
		}
		select.dataset.dropdownReady = '1';
		uid += 1;
		const id = select.id || `trust-dropdown-${ uid }`;
		select.id = id;
		const label = document.querySelector( `label[for="${ id }"]` );
		if ( label && ! label.id ) {
			label.id = `${ id }-label`;
		}

		const wrap = document.createElement( 'div' );
		wrap.className = 'dropdown';
		select.parentNode.insertBefore( wrap, select );
		wrap.appendChild( select );

		const button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'dropdown__button';
		button.id = `${ id }-button`;
		button.setAttribute( 'role', 'combobox' );
		button.setAttribute( 'aria-haspopup', 'listbox' );
		button.setAttribute( 'aria-expanded', 'false' );
		button.setAttribute( 'aria-controls', `${ id }-listbox` );
		const value = document.createElement( 'span' );
		value.className = 'dropdown__value';
		button.appendChild( value );

		const list = document.createElement( 'ul' );
		list.className = 'dropdown__list';
		list.id = `${ id }-listbox`;
		list.setAttribute( 'role', 'listbox' );
		list.tabIndex = -1;
		list.hidden = true;
		if ( label ) {
			button.setAttribute( 'aria-labelledby', label.id );
			list.setAttribute( 'aria-labelledby', label.id );
			// Подпись ведёт к кнопке (не открывая список — как у обычного <select>).
			label.addEventListener( 'click', ( event ) => {
				event.preventDefault();
				button.focus();
			} );
		}
		wrap.append( button, list );

		let active = -1;
		let buffer = '';
		let timer = 0;
		const options = () => [ ...select.options ];
		const isOpen = () => ! list.hidden;

		const sync = () => {
			const current = select.options[ select.selectedIndex ];
			value.textContent = current ? current.text : '';
		};

		/** Варианты — заново при каждом открытии: значения и числа берутся из <select>. */
		const build = () => {
			list.textContent = '';
			options().forEach( ( option, i ) => {
				const item = document.createElement( 'li' );
				item.className = 'dropdown__option';
				item.id = `${ id }-option-${ i }`;
				item.setAttribute( 'role', 'option' );
				item.setAttribute( 'aria-selected', String( option.selected ) );
				item.dataset.index = String( i );
				item.append( option.text );
				if ( undefined !== option.dataset.count ) {
					const count = document.createElement( 'span' );
					count.className = 'dropdown__count';
					count.textContent = option.dataset.count;
					item.appendChild( count );
					item.classList.toggle( 'is-empty', '0' === option.dataset.count );
				}
				list.appendChild( item );
			} );
		};

		const setActive = ( index ) => {
			const items = list.children;
			if ( ! items.length ) {
				return;
			}
			active = Math.max( 0, Math.min( items.length - 1, index ) );
			[ ...items ].forEach( ( item, i ) => item.classList.toggle( 'is-active', i === active ) );
			button.setAttribute( 'aria-activedescendant', items[ active ].id );
			items[ active ].scrollIntoView( { block: 'nearest' } );
		};

		/** Не хватает места под кнопкой — панель над ней; не помещается справа — по правому краю. */
		const place = () => {
			wrap.classList.remove( 'dropdown--up', 'dropdown--end' );
			const box = list.getBoundingClientRect();
			const top = parseFloat( getComputedStyle( document.documentElement ).getPropertyValue( '--sticky-top' ) ) || 0;
			if ( box.bottom > window.innerHeight - 8 && button.getBoundingClientRect().top - box.height - 6 > top + 8 ) {
				wrap.classList.add( 'dropdown--up' );
			}
			if ( box.right > document.documentElement.clientWidth - 8 ) {
				wrap.classList.add( 'dropdown--end' );
			}
		};

		const onOutside = ( event ) => {
			if ( ! wrap.contains( event.target ) ) {
				close();
			}
		};

		const open = ( index ) => {
			if ( isOpen() ) {
				setActive( index );
				return;
			}
			build();
			list.hidden = false;
			button.setAttribute( 'aria-expanded', 'true' );
			place();
			setActive( undefined === index ? select.selectedIndex : index );
			document.addEventListener( 'pointerdown', onOutside );
		};

		function close() {
			list.hidden = true;
			list.classList.remove( 'is-keyboard' );
			button.setAttribute( 'aria-expanded', 'false' );
			button.removeAttribute( 'aria-activedescendant' );
			document.removeEventListener( 'pointerdown', onOutside );
		}

		const choose = ( index ) => {
			close();
			if ( index < 0 || index === select.selectedIndex ) {
				return;
			}
			select.selectedIndex = index;
			sync();
			select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		};

		/** Буквы: к варианту, который начинается с набранного (набор сбрасывается через 0,5 с). */
		const typeahead = ( char ) => {
			window.clearTimeout( timer );
			timer = window.setTimeout( () => {
				buffer = '';
			}, 500 );
			buffer += char.toLowerCase();
			const all = options();
			const from = isOpen() ? active : select.selectedIndex;
			const start = 1 === buffer.length ? from + 1 : from;
			for ( let k = 0; k < all.length; k++ ) {
				const i = ( start + k + all.length ) % all.length;
				if ( all[ i ].text.toLowerCase().startsWith( buffer ) ) {
					open( i );
					return;
				}
			}
		};

		button.addEventListener( 'click', ( event ) => {
			// Нажатие с клавиатуры (detail = 0) обработано в keydown.
			if ( 0 === event.detail ) {
				return;
			}
			if ( isOpen() ) {
				close();
			} else {
				open();
			}
		} );

		button.addEventListener( 'keydown', ( event ) => {
			const { key } = event;
			const last = select.options.length - 1;
			const printable = 1 === key.length && ! event.ctrlKey && ! event.metaKey && ! event.altKey;
			if ( printable && ( ' ' !== key || buffer ) ) {
				event.preventDefault();
				typeahead( key );
				list.classList.add( 'is-keyboard' );
				return;
			}
			if ( ! isOpen() ) {
				if ( [ 'ArrowDown', 'ArrowUp', 'Enter', ' ', 'Home', 'End' ].includes( key ) ) {
					event.preventDefault();
					open( { Home: 0, End: last }[ key ] );
					list.classList.add( 'is-keyboard' );
				}
				return;
			}
			const moves = {
				ArrowDown: active + 1,
				ArrowUp: active - 1,
				Home: 0,
				End: last,
				PageDown: active + 10,
				PageUp: active - 10,
			};
			if ( 'ArrowUp' === key && event.altKey ) {
				event.preventDefault();
				choose( active );
			} else if ( key in moves ) {
				event.preventDefault();
				list.classList.add( 'is-keyboard' );
				setActive( moves[ key ] );
			} else if ( 'Enter' === key || ' ' === key ) {
				event.preventDefault();
				choose( active );
			} else if ( 'Escape' === key ) {
				event.preventDefault();
				event.stopPropagation();
				close();
			} else if ( 'Tab' === key ) {
				choose( active );
			}
		} );

		button.addEventListener( 'blur', () => {
			if ( isOpen() ) {
				close();
			}
		} );

		// Нажатие на вариант не уводит фокус с кнопки.
		list.addEventListener( 'mousedown', ( event ) => event.preventDefault() );
		list.addEventListener( 'click', ( event ) => {
			const item = event.target.closest( '.dropdown__option' );
			if ( item ) {
				choose( Number( item.dataset.index ) );
				button.focus();
			}
		} );
		list.addEventListener( 'mousemove', () => list.classList.remove( 'is-keyboard' ) );

		select.addEventListener( 'change', sync );
		select.addEventListener( 'trust-dropdown:update', () => {
			sync();
			if ( isOpen() ) {
				build();
				setActive( active );
			}
		} );
		// Поворот экрана или другая ширина окна — закрыть. Высота на телефоне меняется при прокрутке
		// (прячется адресная строка) — от этого список не закрывается.
		let width = window.innerWidth;
		window.addEventListener( 'resize', () => {
			if ( window.innerWidth !== width && isOpen() ) {
				close();
			}
			width = window.innerWidth;
		} );
		sync();
	};

	document.querySelectorAll( 'select[data-dropdown]' ).forEach( enhance );
	window.trustDropdown = { enhance };
} )();
