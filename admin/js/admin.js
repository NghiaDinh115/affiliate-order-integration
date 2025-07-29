jQuery( document ).ready(
	function ($) {
		// Test connection
		$( '#test-connection' ).on(
			'click',
			function () {
				var $button = $( this );
				var $result = $( '#test-result' );

				$button.prop( 'disabled', true ).text( 'Testing...' );
				$result.html( '' );

				$.ajax(
					{
						url: aoi_ajax.url,
						type: 'POST',
						data: {
							action: 'aoi_test_connection',
							nonce: aoi_ajax.nonce
						},
						success: function (response) {
							if (response.success) {
								$result.html( '<div class="notice notice-success"><p>' + response.message + '</p></div>' );
							} else {
								$result.html( '<div class="notice notice-error"><p>' + response.message + '</p></div>' );
							}
						},
						error: function () {
							$result.html( '<div class="notice notice-error"><p>Connection test failed.</p></div>' );
						},
						complete: function () {
							$button.prop( 'disabled', false ).text( 'Test Connection' );
						}
					}
				);
			}
		);

		// Resend order
		$( '.resend-order' ).on(
			'click',
			function () {
				var $button = $( this );
				var orderId = $button.data( 'order-id' );

				if ( ! confirm( 'Are you sure you want to resend this order?' )) {
					return;
				}

				$button.prop( 'disabled', true ).text( 'Sending...' );

				$.ajax(
					{
						url: aoi_ajax.url,
						type: 'POST',
						data: {
							action: 'aoi_resend_order',
							order_id: orderId,
							nonce: aoi_ajax.nonce
						},
						success: function (response) {
							if (response.success) {
								alert( 'Order resent successfully!' );
								location.reload();
							} else {
								alert( 'Failed to resend order: ' + response.data.message );
							}
						},
						error: function () {
							alert( 'Failed to resend order.' );
						},
						complete: function () {
							$button.prop( 'disabled', false ).text( 'Resend' );
						}
					}
				);
			}
		);
	}
);
