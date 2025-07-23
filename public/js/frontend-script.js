/**
 * Frontend JavaScript
 *
 * @package MySamplePlugin
 */

jQuery( document ).ready(
	function ($) {
		// Handle click events.
		$( '.msp-shortcode-button' ).on(
			'click',
			function () {
				$( this ).addClass( 'msp-clicked' );
			}
		);

		// AJAX example.
		function mspAjaxRequest(data) {
			$.ajax(
				{
					url: msp_ajax.ajax_url,
					type: 'POST',
					data: {
						action: 'msp_ajax_handler',
						data: data,
						nonce: msp_ajax.nonce
					},
					success: function (response) {
						console.log( 'Success: ', response );
					},
					error: function (xhr, status, error) {
						console.error( 'Error: ', error );
					}
				}
			);
		}
	}
);