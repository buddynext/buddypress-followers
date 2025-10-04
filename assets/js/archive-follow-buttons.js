/**
 * Archive Follow Buttons JavaScript
 *
 * Handles follow/unfollow actions on WordPress archive pages.
 *
 * @package BP-Follow
 */

(function($) {
	'use strict';

	var BPFollowArchive = {
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			$(document).on('click', '.bp-follow-archive-button', this.handleFollowClick);
		},

		/**
		 * Handle follow/unfollow button click
		 */
		handleFollowClick: function(e) {
			e.preventDefault();
			e.stopPropagation();

			var $button = $(this);

			// Check if button is already loading
			if ($button.hasClass('loading')) {
				return;
			}

			var isFollowing = $button.hasClass('following');
			var followType = $button.data('follow-type');
			var itemId = $button.data('item-id');

			// Validation
			if (!followType || !itemId) {
				console.error('Missing required data attributes', {
					followType: followType,
					itemId: itemId,
					button: $button[0]
				});
				BPFollowArchive.showMessage('Configuration error. Please refresh the page.', 'error');
				return;
			}

			var taxonomy = $button.data('taxonomy') || '';

			// Add loading state
			$button.addClass('loading');
			$button.prop('disabled', true);

			// Save original text
			var $buttonText = $button.find('.button-text');
			var originalText = $buttonText.text();

			// Update button text to loading
			$buttonText.text(isFollowing ? 'Unfollowing...' : 'Following...');

			// Prepare AJAX data - ensure proper data types
			var ajaxData = {
				action: 'bp_follow_archive_action',
				nonce: bpFollowArchive.nonce,
				follow_type: followType,
				item_id: parseInt(itemId, 10),
				action_type: isFollowing ? 'unfollow' : 'follow'
			};

			// Only add taxonomy for term follow types
			if (followType === 'term' && taxonomy) {
				ajaxData.taxonomy = taxonomy;
			}

			// Make AJAX request
			$.ajax({
				url: bpFollowArchive.ajaxurl,
				type: 'POST',
				data: ajaxData,
				dataType: 'json',
				success: function(response) {
					// Remove loading state
					$button.removeClass('loading');
					$button.prop('disabled', false);

					if (response.success) {
						// Update button state
						if (response.data.action === 'follow') {
							$button.removeClass('not-following').addClass('following');
							$buttonText.text(bpFollowArchive.strings.following);
							$button.find('.dashicons')
								.removeClass('dashicons-plus-alt2')
								.addClass('dashicons-yes');
						} else {
							$button.removeClass('following').addClass('not-following');
							$buttonText.text(bpFollowArchive.strings.follow);
							$button.find('.dashicons')
								.removeClass('dashicons-yes')
								.addClass('dashicons-plus-alt2');
						}

						// Update follower count if present
						BPFollowArchive.updateFollowerCount($button, response.data.new_count, response.data.new_count_text);

						// Show success message
						BPFollowArchive.showMessage(response.data.message, 'success');
					} else {
						// Restore original text
						$buttonText.text(originalText);

						// Show error message
						BPFollowArchive.showMessage(response.data.message || 'Error occurred', 'error');
					}
				},
				error: function(xhr, status, error) {
					// Remove loading state
					$button.removeClass('loading');
					$button.prop('disabled', false);

					// Restore original text
					$buttonText.text(originalText);

					// Log error for debugging
					console.error('AJAX Error:', status, error);
					console.error('Response:', xhr.responseText);

					// Show error message
					BPFollowArchive.showMessage('Connection error. Please try again.', 'error');
				}
			});
		},

		/**
		 * Update follower count display
		 */
		updateFollowerCount: function($button, newCount, newCountText) {
			// Find count element in different possible locations
			var $countElement = null;

			// Check if count is in same container
			var $container = $button.closest('.bp-follow-title-button, .bp-follow-archive-button-wrapper');
			if ($container.length) {
				$countElement = $container.find('.bp-follow-count, .bp-follow-info .follower-count');
			}

			// Update the count
			if ($countElement.length) {
				if ($countElement.hasClass('bp-follow-count')) {
					// Update full text
					$countElement.fadeOut(200, function() {
						$(this).html(newCountText).fadeIn(200);
					});
				} else {
					// Just update the number
					$countElement.fadeOut(200, function() {
						$(this).text(newCount).fadeIn(200);
					});
				}
			}

			// Also update any other count displays on the page
			$('.bp-follow-info').each(function() {
				var $info = $(this);
				if ($info.find('.follower-count').length) {
					$info.find('.follower-count').text(newCount);
					// Update the surrounding text too
					$info.html(newCountText);
				}
			});
		},

		/**
		 * Show success/error message
		 */
		showMessage: function(message, type) {
			// Remove any existing messages
			$('.bp-follow-message').remove();

			// Create message element
			var $message = $('<div class="bp-follow-message ' + type + '">' + message + '</div>');

			// Add to body
			$('body').append($message);

			// Auto-hide after 3 seconds
			setTimeout(function() {
				$message.fadeOut(300, function() {
					$(this).remove();
				});
			}, 3000);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		BPFollowArchive.init();

		// Add hover effect for follow buttons (using mouseenter/mouseleave instead of deprecated hover)
		$(document).on('mouseenter', '.bp-follow-archive-button.following', function() {
			var $text = $(this).find('.button-text');
			$text.data('original-text', $text.text());
			$text.text(bpFollowArchive.strings.unfollow);
		});

		$(document).on('mouseleave', '.bp-follow-archive-button.following', function() {
			var $text = $(this).find('.button-text');
			if ($text.data('original-text')) {
				$text.text($text.data('original-text'));
			}
		});

		// Handle mobile touch events
		if ('ontouchstart' in window) {
			$('.bp-follow-archive-button').on('touchstart', function() {
				$(this).addClass('touch-active');
			}).on('touchend', function() {
				$(this).removeClass('touch-active');
			});
		}

		// Enhance accessibility
		$('.bp-follow-archive-button').attr('role', 'button').attr('tabindex', '0');

		// Handle keyboard activation
		$('.bp-follow-archive-button').on('keydown', function(e) {
			// Enter or Space key
			if (e.keyCode === 13 || e.keyCode === 32) {
				e.preventDefault();
				$(this).click();
			}
		});
	});

})(jQuery);