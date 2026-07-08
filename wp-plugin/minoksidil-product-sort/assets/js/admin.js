/* global jQuery, MPS */
( function ( $ ) {
	'use strict';

	$( function () {
		var $list = $( '.mps-list' );
		if ( ! $list.length ) {
			return;
		}

		var $status = $( '.mps-status' );

		$list.sortable( {
			handle: '.mps-handle',
			placeholder: 'mps-placeholder',
			forcePlaceholderSize: true,
			axis: 'y',
			update: save
		} );

		function save() {
			var order = $list
				.find( '.mps-item' )
				.map( function () {
					return $( this ).data( 'id' );
				} )
				.get();

			var offset = parseInt( $list.data( 'offset' ), 10 ) || 0;

			setStatus( 'busy', MPS.i18n.saving );

			$.post( MPS.ajaxUrl, {
				action: 'mps_save_order',
				nonce: MPS.nonce,
				offset: offset,
				order: order
			} )
				.done( function ( res ) {
					if ( res && res.success ) {
						setStatus( 'ok', MPS.i18n.saved );
					} else {
						setStatus( 'error', ( res && res.data && res.data.message ) || MPS.i18n.error );
					}
				} )
				.fail( function () {
					setStatus( 'error', MPS.i18n.error );
				} );
		}

		function setStatus( state, text ) {
			$status
				.removeClass( 'is-busy is-ok is-error' )
				.addClass( 'is-' + state )
				.text( text );
		}
	} );
} )( jQuery );
