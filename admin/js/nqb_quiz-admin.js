(function ($) {
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

	$(document).ready(function () {

		// todo: remove error logging in prod
		console.log('Document ready fired');
		console.log('Delete button exists:', $('#delete-all-questions').length);
		console.log('AJAX URL:', nqb_quiz_ajax_object.ajax_url);
		console.log('Nonce:', nqb_quiz_ajax_object.nonce);


		$('#delete-all-questions').on('click', function (e) {
			e.preventDefault();

			console.log('Delete button clicked');

			if (!confirm('Are you sure you want to delete ALL questions? This action cannot be undone!')) {
				return;
			}

			const button = $(this);
			const resultElement = $('#delete-questions-result');

			// Disable button and show loading state
			button.prop('disabled', true).text('Deleting...');

			// Log the request data
			const requestData = {
				action: 'delete_all_questions',
				security: nqb_quiz_ajax_object.nonce
			};

			console.log('Making AJAX request with:', {
				url: nqb_quiz_ajax_object.ajax_url,
				data: requestData
			});

			// Make the AJAX request
			$.ajax({
				url: nqb_quiz_ajax_object.ajax_url,
				type: 'POST',
				data: requestData,
				success: function (response) {
					console.log('AJAX Response:', response);

					if (response.success) {
						resultElement.html(`<span style="color: green;">${response.data.message}</span>`);
					} else {
						resultElement.html(`<span style="color: red;">${response.data.message || 'An error occurred'}</span>`);
					}
				},
				error: function (xhr, status, errorThrown) {
					console.error('AJAX Error:', {
						status: status,
						error: errorThrown,
						response: xhr.responseText
					});
					resultElement.html('<span style="color: red;">Failed to process request. Check console for details.</span>');
				},
				complete: function () {
					button.prop('disabled', false).text('Delete All Questions');
				}
			});
		});

		/**
		 * for loading csvs
		 */
		// Add event handler for the button click
		$('#load-csvs').on('click', function (e) {
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
				success: function (response) {
					// Check if the request was successful
					if (response.success) {
						$('#csv-loader-result').text(response.data);
					} else {
						$('#csv-loader-result').text('Error: ' + response.data);
					}
					$('#load-csvs').prop('disabled', false).text('Load CSVs');
				},
				error: function (xhr, status, error) {
					// Handle any errors
					$('#csv-loader-result').text('AJAX request failed: ' + error);
					$('#load-csvs').prop('disabled', false).text('Load CSVs');
				}
			});
		});
	});

})(jQuery);

