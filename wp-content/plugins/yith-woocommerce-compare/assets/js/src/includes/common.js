/* global jQuery */

'use strict';

const
	addQueryArgs = ( args, url ) => {
		const urlObj = new URL( url || window.location.href ),
			searchParams = urlObj.searchParams;

		Object.entries( args ).map( ( [ key, value ] ) => searchParams.set( key, value ) );

		return urlObj.toString();
	},
	block = ( $item ) => {
		if ( 'undefined' === typeof jQuery.fn.block ) {
			return;
		}

		$item
			.addClass( 'js-blocked' )
			.block({
				message: null,
				overlayCSS: {
					background: '#fff url(' + yith_woocompare.loader + ') no-repeat center',
					backgroundSize: '20px 20px',
					opacity: 0.6
				}
			});
	},
	unblock = ( $item ) => {
		if ( 'undefined' === typeof jQuery.fn.unblock ) {
			return;
		}

		$item
			.removeClass( 'js-blocked' )
			.unblock();
	},
	getCookie = ( cookieName ) => {
		const cookies = document.cookie
			.split( ';' )
			.reduce( ( a, i ) => {
				const [ key, value ] = i.trim().split('=');
				a[ key ] = value;
				return a;
			}, {} );

		return cookies?.[ cookieName ];
	};

export {
	addQueryArgs,
	getCookie,
	block,
	unblock
};