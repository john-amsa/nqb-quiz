(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(document).ready(function() {

		// Add event handler for the button click
		$('#load-csvs').on('click', function(e) {
			e.preventDefault();

			// Disable the button to prevent multiple clicks
			$(this).prop('disabled', true).text('Loading...');

			// Send the AJAX request
			$.ajax({
				url: nqb_quiz_ajax_object.ajax_url,  // The AJAX URL provided by wp_localize_script
				method: 'POST',
				data: {
					action: 'load_csvs',  // The action defined in PHP for the AJAX call
					security: nqb_quiz_ajax_object.nonce  // The nonce for security
				},
				success: function(response) {
					// Check if the request was successful
					if (response.success) {
						$('#csv-loader-result').text(response.data);
					} else {
						$('#csv-loader-result').text('Error: ' + response.data);
					}
					$('#load-csvs').prop('disabled', false).text('Load CSVs');
				},
				error: function(xhr, status, error) {
					// Handle any errors
					$('#csv-loader-result').text('AJAX request failed: ' + error);
					$('#load-csvs').prop('disabled', false).text('Load CSVs');
				}
			});
		});
	});

})( jQuery );

