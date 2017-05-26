$(document).ready(function(){

	$('.deeplink_addkey').live('click', function(e) {
		var keyIdPrefix = $(e.currentTarget).parents('.add_deep_links').attr('id');
		var keyId = $(e.currentTarget).attr('id');
		var keyIndex = $(e.currentTarget).attr('id').substr(keyId.indexOf('_')+1);
		var html = "<input type='text' id=" + keyIdPrefix + "_key_" + keyIndex + "/>";
		$(e.currentTarget).siblings('div#keysGoHere').append(html+'&nbsp;');
		$(e.currentTarget).attr({
			id: 'key_'+(parseInt(keyIndex)+1)
		});
		e.preventDefault();
		e.stopPropagation();
		
	});

	$('.add_deep_links').live('click', function(e) {
		// body...
		var deepLinkId = $(e.currentTarget).attr('id');
		var deepLinkIndex = $(e.currentTarget).attr('id').substr(deepLinkId.indexOf('_')+1);
		var template = _.template($('#add_deeplink').html())({
			index: deepLinkIndex
		});
		$(e.currentTarget).html(template);
		var newId = parseInt(deepLinkIndex)+1;
		$('table#box-table-a input#addaccount__csrf_token').before("<tr>" +
																	"<td></td>" +
																	"<td>" +
																		"<div id='deeplink_" + newId + "' class='add_deep_links'>+ Add Deep Links</div>" +
																	"</td>");
//		$(this).closest('tr').next('tr').children('td').children('div').removeClass('hide').addClass('add_deep_links');
		e.preventDefault();
		e.stopPropagation();
	});

});



