/**
 * Content Follows Feed JavaScript
 *
 * Handles AJAX filtering and interactions for followed authors/categories feeds.
 *
 * @package BP-Follow
 */

(function($) {
	'use strict';

	var BPFollowFeed = {
		/**
		 * Initialize feed functionality.
		 */
		init: function() {
			this.bindEvents();
			this.loadFilterState();
		},

		/**
		 * Bind event handlers.
		 */
		bindEvents: function() {
			// Toggle all checkboxes
			$(document).on('click', '.bp-follow-toggle-all', this.toggleAll);

			// Apply filter
			$(document).on('click', '.bp-follow-apply-filter', this.applyFilter);

			// Auto-apply on checkbox change (optional - remove if you want manual apply only)
			$(document).on('change', '.bp-follow-filter-checkbox', this.saveFilterState);

			// Load more posts
			$(document).on('click', '.bp-follow-load-more', this.loadMore);

			// Unfollow author/category
			$(document).on('click', '.bp-follow-unfollow-btn', this.unfollow);
		},

		/**
		 * Toggle all checkboxes.
		 */
		toggleAll: function(e) {
			e.preventDefault();
			var $container = $(this).closest('.bp-follow-feed-container');
			var $checkboxes = $container.find('.bp-follow-filter-checkbox');
			var allChecked = $checkboxes.filter(':checked').length === $checkboxes.length;

			$checkboxes.prop('checked', !allChecked);
			BPFollowFeed.saveFilterState.call($checkboxes.first());
		},

		/**
		 * Apply filter and reload posts.
		 */
		applyFilter: function(e) {
			e.preventDefault();
			var $container = $(this).closest('.bp-follow-feed-container');
			var $postsContainer = $container.find('.bp-follow-posts-container');
			var $loading = $container.find('.bp-follow-loading');
			var feedType = $container.data('feed-type');
			var $button = $(this);

			// Get selected IDs
			var selectedIds = [];
			$container.find('.bp-follow-filter-checkbox:checked').each(function() {
				selectedIds.push($(this).val());
			});

			if (selectedIds.length === 0) {
				$postsContainer.html('<p class="bp-follow-no-posts">' + 'Please select at least one filter.' + '</p>');
				return;
			}

			// Disable button and show loading state
			$button.prop('disabled', true).text('Applying...');

			// Fade out posts and show loading
			$postsContainer.fadeOut(200, function() {
				$loading.fadeIn(200);
			});

			// Prepare AJAX data
			var ajaxData = {
				action: feedType === 'authors' ? 'bp_follow_get_author_posts' : 'bp_follow_get_category_posts',
				nonce: bpFollowFeed.nonce,
				user_id: $container.data('user-id'),
				paged: 1,
				per_page: $container.data('per-page')
			};

			if (feedType === 'authors') {
				ajaxData.author_ids = selectedIds;
				ajaxData.post_type = $container.data('post-type');
			} else {
				ajaxData.term_ids = selectedIds;
				ajaxData.taxonomy = $container.data('taxonomy');
			}

			// AJAX request
			$.post(bpFollowFeed.ajaxurl, ajaxData, function(response) {
				// Re-enable button
				$button.prop('disabled', false).text('Apply Filter');

				// Hide loading
				$loading.fadeOut(200);

				if (response.success) {
					// Check if we have a posts list UL already
					var $postsList = $postsContainer.find('.bp-follow-posts-list');

					// If the response contains "bp-follow-no-posts", show it directly
					if (response.data.html.indexOf('bp-follow-no-posts') !== -1) {
						$postsContainer.html(response.data.html).fadeIn(300);
					} else {
						// We have posts, update the list
						if ($postsList.length > 0) {
							// Update existing list
							$postsList.html(response.data.html);
						} else {
							// Create new list container
							$postsContainer.html('<ul class="bp-follow-posts-list" role="list">' + response.data.html + '</ul>');
						}

						// Update count with animation
						var $countElement = $container.find('.bp-follow-posts-count');
						$countElement.fadeOut(100, function() {
							$(this).text(
								response.data.found + ' ' + (response.data.found === 1 ? 'post' : 'posts')
							).fadeIn(100);
						});

						// Handle pagination
						var $pagination = $container.find('.bp-follow-pagination');
						if (response.data.max_pages > 1) {
							if ($pagination.length === 0) {
								// Add new pagination
								$postsContainer.append(
									'<nav class="bp-follow-pagination">' +
									'<button type="button" class="bp-follow-load-more button" data-paged="2" data-max-pages="' + response.data.max_pages + '">Load More</button>' +
									'</nav>'
								);
							} else {
								// Update existing pagination
								var $loadMore = $pagination.find('.bp-follow-load-more');
								$loadMore.data('paged', 2).data('max-pages', response.data.max_pages).prop('disabled', false).text('Load More').show();
								$pagination.show();
							}
						} else {
							// Remove pagination if not needed
							$pagination.remove();
						}

						// Fade in the posts container
						$postsContainer.fadeIn(300);
					}
				} else {
					$postsContainer.html('<p class="bp-follow-error">' + (response.data.message || 'Error loading posts.') + '</p>').fadeIn(300);
				}
			}).fail(function() {
				$button.prop('disabled', false).text('Apply Filter');
				$loading.fadeOut(200);
				$postsContainer.html('<p class="bp-follow-error">Error loading posts. Please try again.</p>').fadeIn(300);
			});
		},

		/**
		 * Load more posts (pagination).
		 */
		loadMore: function(e) {
			e.preventDefault();
			var $btn = $(this);
			var $container = $btn.closest('.bp-follow-feed-container');
			var $postsList = $container.find('.bp-follow-posts-list');
			var feedType = $container.data('feed-type');
			var paged = parseInt($btn.data('paged'), 10);
			var maxPages = parseInt($btn.data('max-pages'), 10);

			// Get selected IDs
			var selectedIds = [];
			$container.find('.bp-follow-filter-checkbox:checked').each(function() {
				selectedIds.push($(this).val());
			});

			// Disable button and show loading
			$btn.prop('disabled', true).text('Loading...');

			// Prepare AJAX data
			var ajaxData = {
				action: feedType === 'authors' ? 'bp_follow_get_author_posts' : 'bp_follow_get_category_posts',
				nonce: bpFollowFeed.nonce,
				user_id: $container.data('user-id'),
				paged: paged,
				per_page: $container.data('per-page')
			};

			if (feedType === 'authors') {
				ajaxData.author_ids = selectedIds;
				ajaxData.post_type = $container.data('post-type');
			} else {
				ajaxData.term_ids = selectedIds;
				ajaxData.taxonomy = $container.data('taxonomy');
			}

			// AJAX request
			$.post(bpFollowFeed.ajaxurl, ajaxData, function(response) {
				if (response.success) {
					$postsList.append(response.data.html);

					// Update button
					if (paged < maxPages) {
						$btn.data('paged', paged + 1).prop('disabled', false).text('Load More');
					} else {
						$btn.remove();
					}
				} else {
					$btn.prop('disabled', false).text('Load More');
					alert(response.data.message || 'Error loading more posts.');
				}
			}).fail(function() {
				$btn.prop('disabled', false).text('Load More');
				alert('Error loading more posts. Please try again.');
			});
		},

		/**
		 * Unfollow author or category.
		 */
		unfollow: function(e) {
			e.preventDefault();
			var $btn = $(this);
			var $item = $btn.closest('.bp-follow-filter-item');
			var $container = $btn.closest('.bp-follow-feed-container');
			var feedType = $container.data('feed-type');

			if (!confirm('Are you sure you want to unfollow?')) {
				return;
			}

			// Add removing class and disable button
			$item.addClass('removing');
			$btn.prop('disabled', true);

			var ajaxData = {
				action: 'bp_follow_unfollow_ajax',
				nonce: bpFollowFeed.nonce,
				user_id: $container.data('user-id')
			};

			if (feedType === 'authors') {
				ajaxData.author_id = $btn.data('author-id');
				ajaxData.post_type = $container.data('post-type');
				ajaxData.type = 'author';
			} else {
				ajaxData.term_id = $btn.data('term-id');
				ajaxData.taxonomy = $btn.data('taxonomy');
				ajaxData.type = 'term';
			}

			// AJAX request
			$.post(bpFollowFeed.ajaxurl, ajaxData, function(response) {
				if (response.success) {
					// Remove item from list
					$item.fadeOut(300, function() {
						$(this).remove();

						// Check if list is empty
						if ($container.find('.bp-follow-filter-item').length === 0) {
							location.reload(); // Reload to show empty state
						} else {
							// Reapply filter
							BPFollowFeed.applyFilter.call($container.find('.bp-follow-apply-filter'));
						}
					});
				} else {
					$item.removeClass('removing');
					$btn.prop('disabled', false);
					alert(response.data.message || 'Error unfollowing. Please try again.');
				}
			}).fail(function() {
				$item.removeClass('removing');
				$btn.prop('disabled', false);
				alert('Error unfollowing. Please try again.');
			});
		},

		/**
		 * Save filter state to localStorage.
		 */
		saveFilterState: function() {
			var $container = $(this).closest('.bp-follow-feed-container');
			var feedType = $container.data('feed-type');
			var userId = $container.data('user-id');
			var key = 'bp_follow_filter_' + feedType + '_' + userId;

			var selectedIds = [];
			$container.find('.bp-follow-filter-checkbox:checked').each(function() {
				selectedIds.push($(this).val());
			});

			localStorage.setItem(key, JSON.stringify(selectedIds));
		},

		/**
		 * Load filter state from localStorage.
		 */
		loadFilterState: function() {
			$('.bp-follow-feed-container').each(function() {
				var $container = $(this);
				var feedType = $container.data('feed-type');
				var userId = $container.data('user-id');
				var key = 'bp_follow_filter_' + feedType + '_' + userId;

				var saved = localStorage.getItem(key);
				if (saved) {
					var selectedIds = JSON.parse(saved);
					$container.find('.bp-follow-filter-checkbox').each(function() {
						var $checkbox = $(this);
						$checkbox.prop('checked', selectedIds.indexOf($checkbox.val()) !== -1);
					});
				}
			});
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		BPFollowFeed.init();
	});

})(jQuery);
