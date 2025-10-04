/**
 * Debug version of Content Follows Feed JavaScript
 * This helps identify issues with AJAX filtering
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Log when page loads
		console.log('BuddyPress Followers Feed JS Loaded');

		// Check if containers exist
		$('.bp-follow-feed-container').each(function() {
			var $container = $(this);
			console.log('Found feed container:', {
				feedType: $container.data('feed-type'),
				userId: $container.data('user-id'),
				perPage: $container.data('per-page'),
				hasPostsList: $container.find('.bp-follow-posts-list').length > 0
			});
		});

		// Monitor Apply Filter clicks
		$(document).on('click', '.bp-follow-apply-filter', function(e) {
			console.log('Apply Filter clicked');
			var $container = $(this).closest('.bp-follow-feed-container');
			var selectedIds = [];
			$container.find('.bp-follow-filter-checkbox:checked').each(function() {
				selectedIds.push($(this).val());
			});
			console.log('Selected IDs:', selectedIds);
		});

		// Monitor AJAX calls
		$(document).ajaxComplete(function(event, xhr, settings) {
			if (settings.url && settings.url.indexOf('admin-ajax.php') !== -1) {
				console.log('AJAX Response:', {
					url: settings.url,
					data: settings.data,
					status: xhr.status,
					response: xhr.responseText ? JSON.parse(xhr.responseText) : null
				});

				// Check if response contains HTML
				try {
					var response = JSON.parse(xhr.responseText);
					if (response.success && response.data && response.data.html) {
						console.log('Response HTML length:', response.data.html.length);
						console.log('Response HTML preview:', response.data.html.substring(0, 200));
						console.log('Posts found:', response.data.found);
						console.log('Max pages:', response.data.max_pages);
					}
				} catch(e) {
					console.log('Could not parse response');
				}
			}
		});

		// Check for JavaScript errors
		window.addEventListener('error', function(e) {
			console.error('JavaScript Error:', e.message, 'at', e.filename, ':', e.lineno);
		});
	});

})(jQuery);