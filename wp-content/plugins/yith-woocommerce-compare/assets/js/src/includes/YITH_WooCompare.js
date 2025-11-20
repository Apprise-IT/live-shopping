import {block, getCookie, unblock} from "./common";
import {$, $body, $window, $document} from "./globals";

export default class YITH_WooCompare {
	constructor() {
		if ( $body.hasClass('elementor-editor-active') ) {
			return;
		}

		this.refresh();
		this.init();
	}

	init() {
		this.initTables();
		this.initEvents();
		this.initWidget();
	}

	initEvents() {
		// add to cart.
		$body.on('added_to_cart', this.onAddedToCart );

		// reload / refresh.
		$window.on( 'resize orientationchange', () => this.initTables() );
		$document.on('yith_woocompare_refresh_table yith_woocompare_table_updated', () => this.initTables() );
		$document.on('yith_woocompare_table_updated', () => this.initSlider() );

		// add.
		$document.on(
			'click',
			'a.compare:not(.added)',
			( ev ) => ( ev.preventDefault(), this.add( $( ev.target ) ) )
		);

		// remove.
		$document.on(
			'click',
			'.compare-list .remove a, a.yith_woocompare_clear',
			( ev ) => ( ev.preventDefault(), this.remove( $( ev.target ) ) )
		);
		$document.on(
			'click',
			'a.compare.added input[type="checkbox"]',
			( ev ) => ( ev.preventDefault(), this.remove( $( ev.target ) ), false )
		);

		// open popup.
		$document.on(
			'yith_woocompare_open_popup',
			( ev ) => ( ev.preventDefault(), this.openPopup() )
		);
		$document.on(
			'click',
			'a.compare.added',
			( ev ) => ( ev.preventDefault(), this.openPopup() )
		);
		$document.on(
			'click',
			'.yith-woocompare-open a, a.yith-woocompare-open',
			( ev ) => ( ev.preventDefault(), this.openPopup() )
		);

		// filter.
		$document.on(
			'click',
			'#yith-woocompare-cat-nav li > a',
			( ev ) => ( ev.preventDefault(), this.filter( $( ev.target ) ) )
		);
	}

	getTables( includeInitialized = false ) {
		let $tables = $document.find('#yith-woocompare table.compare-list');

		if ( ! includeInitialized ) {
			$tables = $tables.not( '.dataTable' );
		}

		return $tables;
	}

	initTables() {
		if ( 'undefined' === typeof $.fn.DataTable || 'undefined' === typeof $.fn.imagesLoaded ) {
			return;
		}

		const $tables = this.getTables();

		if ( ! $tables.length ) {
			return;
		}

		$tables.get().map( table => this.initTable( $( table ) ) );
	}

	initTable( $table ) {
		$table.DataTable().destroy();

		$table.imagesLoaded( () => {
			$table.DataTable({
				'info': false,
				'scrollX': true,
				'scrollCollapse': true,
				'paging': false,
				'ordering': false,
				'searching': false,
				'autoWidth': false,
				'destroy': true,
				'fixedColumns': {
					leftColumns: yith_woocompare.fixedcolumns
				},
				'columnDefs': [{ width: 250, targets: 0 }]
			});
		} );
	}

	initSlider() {
		if ( 'undefined' === typeof $.fn.owlCarousel ) {
			return;
		}

		const related = $('#yith-woocompare-related'),
			slider = related.find('.related-products'),
			nav = related.find('.related-slider-nav');

		if ( ! related.length ) {
			return;
		}

		slider.owlCarousel( {
			autoplay: yith_woocompare.autoplay_related,
			autoplayHoverPause: true,
			loop: true,
			margin: 15,
			responsiveClass: true,
			responsive: {
				0: {
					items: 2
				},
				// breakpoint from 480 up
				480: {
					items: 3
				},
				// breakpoint from 768 up
				768: {
					items: yith_woocompare.num_related
				}
			}
		} );

		if ( nav.length ) {
			nav.find('.related-slider-nav-prev').click( () => {
				slider.trigger('prev.owl.carousel');
			} );

			nav.find('.related-slider-nav-next').click( () => {
				slider.trigger('next.owl.carousel');
			} );
		}
	}

	initWidget() {
		$document
			.on(
				'click',
				'.yith-woocompare-widget a.compare-widget',
				( ev ) => ( ev.preventDefault(), this.openPopup() )
			)
			.on(
				'click',
				'.yith-woocompare-widget li a.remove, .yith-woocompare-widget a.clear-all',
				( ev ) => ( ev.preventDefault(), this.remove( $( ev.target ) ) )
			);
	}

	add( $initiator ) {
		$initiator = $initiator.closest( '[data-product_id]' );

		const {
				added_label: addedLabel,
				auto_open: autoOpen,
				is_page: isPage
			} = yith_woocompare,
			productId = $initiator.data( 'product_id' ),
			isRelated = $initiator.closest( '.yith-woocompare-related' ).length;

		return this
			.doAjax( $initiator, 'add', {
				id: productId,
			} )
			.success( ( response ) => {
				const {
					only_one: onlyOne,
					table_url: tableUrl,
					limit_reached: limitReached,
					added,
				} = response;

				if ( added && ! isRelated ) {
					$initiator
						.addClass( 'added' )
						.attr( 'href', tableUrl )
						.find( 'input[type="checkbox"]' )
						.prop( 'checked', true )
						.change()
						.end()
						.find( '.label' )
						.html( addedLabel );

					if ( autoOpen && ! onlyOne && ! isPage ) {
						$document.trigger('yith_woocompare_open_popup', { response: tableUrl, button: $initiator } );
					}
				}

				limitReached && $( '.compare:not(.added)' ).addClass( 'disabled' );

				this.replaceFragments( response );
				this.refreshCounter();

				added && $document.trigger( 'yith_woocompare_product_added', { productId, $initiator } );
			} );
	}

	remove( $initiator ) {
		$initiator = $initiator.closest( '[data-product_id]' );

		const productId = $initiator.data('product_id');

		return this
			.doAjax( $initiator, 'remove', {
				id: productId,
			} )
			.success( ( response ) => {
				let {
						custom_label_for_compare_button: customLabel,
						selector_for_custom_label_compare_button: customSelector,
						button_text: defaultText
					} = yith_woocompare,
					{
						limit_reached: limitReached,
						removed,
					} = response,
					toRemove = 'all' === productId ? '.compare.added' : `.compare[data-product_id="${ productId }"]`,
					buttonText = customLabel ? $initiator.closest( 'tbody' ).find( `tr ${customSelector}` ).find( `td.product_${ productId }` ).text() : defaultText;

				removed && $( toRemove, window.parent.document )
					.removeClass( 'added' )
					.find( 'input[type="checkbox"]' )
					.prop( 'checked', false )
					.change()
					.end()
					.find( '.label' )
					.html( buttonText );

				! limitReached && $( '.compare:not(.added)' ).removeClass( 'disabled' );

				this.replaceFragments( response );
				this.refreshCounter();

				removed && $document.trigger( 'yith_woocompare_product_removed', { productId, $initiator } );
			} );
	}

	filter( $initiator ) {
		$initiator = $initiator.closest( '[data-cat_id]' );

		const $nav = $initiator.closest( '#yith-woocompare-cat-nav > ul' ),
			cat = $initiator.data( 'cat_id' ),
			products = $nav.data( 'product_ids' );

		return this
			.doAjax( $initiator, 'filter', {
				yith_compare_cat: cat,
				yith_compare_prod: products,
			} )
			.success( ( response ) => {
				this.replaceFragments( response );
				this.refreshCounter();
			} );
	}

	maybeShowPreviewBar() {
		const $previewBar = $( '#yith-woocompare-preview-bar' );

		if ( ! $previewBar.hasClass( 'shown' ) ) {
			return;
		}

		$previewBar.show();
	}

	hidePreviewBar() {
		$( '#yith-woocompare-preview-bar' ).hide();
	}

	openPopup() {
		const {
			force_showing_popup: forcePopup,
			page_url: pageUrl,
			is_page: isPage
		} = yith_woocompare;

		if ( isPage || ! forcePopup && $window.width < 768 ) {
			window.location = pageUrl;
			return;
		}

		let $container = $('.yith-woocompare-popup-container');
		$container = $container.length ? $container : this.buildPopupContainer();

		$('html, body').css( 'overflow', 'hidden' );
		$body.addClass( 'yith-woocompare-popup-open' );
		$container.show();

		this.hidePreviewBar();
		this.refreshFragments().then( () => this.hidePreviewBar() );
	}

	closePopup() {
		let $container = $('.yith-woocompare-popup-container');

		if ( ! $container.length ) {
			return;
		}

		$container.hide();

		$('html, body').css( 'overflow', 'auto' );
		$body.removeClass( 'yith-woocompare-popup-open' );

		this.maybeShowPreviewBar();
	}

	buildPopupContainer() {
		const $closeButton = $('<a/>', {
				'href': '#',
				'role': 'button',
				'html': '&times;',
				'class': 'yith-woocompare-popup-close'
			}),
			$container = $( '<div/>', {
				'class': 'yith-woocompare-popup-container'
			}),
			$tableWrapper = $( '<div/>', {
				'class': 'yith-woocompare-table-wrapper'
			}),
			$tableScrollWrapper = $( '<div/>', {
				'class': 'yith-woocompare-table-scroll-wrapper'
			}),
			$tablePlaceholder = $( '<div/>', {
				'id': 'yith-woocompare',
				'class': 'yith-woocompare-table-placeholder'
			});

		$tableScrollWrapper
			.prepend( $closeButton )
			.append( $tablePlaceholder )
			.appendTo( $tableWrapper );
		$container
			.append( $tableWrapper )
			.appendTo('body');

		$closeButton.on( 'click', () => this.closePopup() );

		return $container;
	}

	redirectToPage() {

	}

	doAjax( $initiator, key, data ) {
		const action = yith_woocompare?.actions?.[key],
			security = yith_woocompare?.nonces?.[key];

		return $.ajax( {
			type: 'post',
			url: this.getAjxUrl( action ),
			data: {
				action,
				security,
				context: 'frontend',
				lang: $initiator.data( 'lang' ),
				...( data || {} )
			},
			dataType: 'json',
			cache: false,
			beforeSend: () => block( $initiator ),
			complete: () => unblock( $initiator ),
			error: ( ...errorParams ) => console.log( errorParams )
		} );
	}

	getAjxUrl( action ) {
		return yith_woocompare.ajaxurl.toString().replace('%%endpoint%%', action);
	}

	refresh() {
		this.refreshFragments();
		this.refreshCounter();
	}

	refreshFragments() {
		const $fragments = $( '.yith-woocompare-widget-content' )
			.add( '#yith-woocompare:not(.fixed-compare-table)' )
			.add( '#yith-woocompare-preview-bar' );

		if ( ! $fragments.length ) {
			return;
		}

		return this
			.doAjax( $fragments, 'reload' )
			.success( ( response ) => this.replaceFragments( response ) );
	}

	refreshCounter() {
		const $counter = $( '.yith-woocompare-counter' );

		if ( ! $counter.length ) {
			return;
		}

		const {
				cookie_name: cookieName
			} = yith_woocompare,
			type = $counter.data( 'type' ),
			text = $counter.data( 'text_o' ),
			cookie = getCookie( cookieName );
		let c;

		try {
			c = cookie ? JSON.parse(cookie).length : 0;
		} catch (e) {
			return;
		}

		$counter
			.find( '.yith-woocompare-count' )
			.html('text' === type ? text.replace( '{{count}}', c ) : c );

		$document.trigger( 'yith_woocompare_counter_updated', c );
	}

	replaceFragments( response ) {
		const {
			table_html: tableHtml,
			widget_html: widgetHtml,
			preview_bar_html: barHtml,
		} = response;

		if ( tableHtml ) {
			$( '#yith-woocompare:not(.fixed-compare-table)' ).replaceWith( tableHtml );
			$document.trigger( 'yith_woocompare_table_updated' );
		}

		if ( widgetHtml ) {
			$( '.yith-woocompare-widget-content' ).replaceWith( widgetHtml );
			$document.trigger('yith_woocompare_widget_updated');
		}

		if ( barHtml ) {
			$( '#yith-woocompare-preview-bar' ).replaceWith( barHtml );
		}

		$document.trigger( 'yith_woocompare_fragments_replaced', response );
	}

	onAddedToCart( ev, fragments, cart_hash, button ) {
		const $button = $( button );

		if ( $button.closest('table.compare-list').length ) {
			$button.hide();
		}
	}
}