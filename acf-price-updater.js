jQuery(document).ready(function($) {
    // Handle the form submission
    $('#acf-price-updater-search-form').submit(function(e) {
        e.preventDefault();
        var searchQuery = $('#acf-price-updater-search-query').val();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'acf_price_updater_search',
                search_query: searchQuery,
            },
            success: function(response) {
                $('#acf-price-updater-search-results').html(response);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('AJAX error:', textStatus, errorThrown);
            }
        });
    });

	$(document).on('click', '.acf-price-updater-save', function() {
		var $row = $(this).closest('tr');
		var serviceName = $row.find('td:first').text();  // Получаем название услуги
		var priceInput = $row.find('input[type="text"]');
		var price = priceInput.val();  // Получаем новую цену
		var postId = priceInput.data('post-id');
		var fieldNumber = priceInput.data('field-number');

		// Обновляем все скрытые строки с этим названием услуги
		var requests = $('tr').filter(function() {
			return $(this).find('td:first').text() === serviceName;
		}).map(function() {
			$(this).find('input[type="text"]').val(price);  // Обновляем цену
			var postId = $(this).find('input[type="text"]').data('post-id');
			var fieldNumber = $(this).find('input[type="text"]').data('field-number');
			return $.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'acf_price_updater_save',
					post_id: postId,
					field_number: fieldNumber,
					price: price,
					nonce: acf_price_updater.nonce,
				}
			});
		}).get();

		// Ждем, пока все AJAX-запросы завершатся
		$.when.apply($, requests)
			.done(function() {
				console.log('All prices updated successfully.');
			})
			.fail(function() {
				console.log('An error occurred while updating the prices.');
			});
	});

});
