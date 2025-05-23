jQuery( document ).ready( function( $ ) {
    'use strict';

	$('table.users tbody').sortable({
		items: '> tr',
		helper: function(e, tr) {
			var origins = tr.children(),
			    helper = tr.clone();
			helper.children().each(function(index) {
				$(this).width(origins.eq(index).width());
			});
			return helper;
		},
		stop: function(e, ui) {
			var rows = ui.item.parent().find('> tr'),
			    rowClass = 'alternate';
			rows.each(function(i, v) {
				var user = $(this),
				userData = $('td.user_order .uni_sort', user),
				plusIndex = user.index() + 1;
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action:'uni_sortable_users_save_order',
						user_ID: userData.attr('data-uni-user-id'),
						user_order_value: plusIndex
					},
					async: false
				});
				userData.text(plusIndex);
				rows.removeClass(rowClass).filter(':even').addClass(rowClass);
			});
		}
	});

});