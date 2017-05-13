$(document).ready(function(){

	// $('#deeplink_addkey').click(function(e){
	// 	var keyIdPrefix = $(e.currentTarget).parents('.deeplink_1').attr('id');
	// 	var html = "<input type='text' id='"+keyIdPrefix+"_key_1'/>";
	// 	$(this).html(html);
	// });

	$('.add_deep_links').click(function() {
		// body...
		var x = this.innerHTML;
		var claas = this.className;
		this.className = "nohide";
		var html = "<td>"+
						"<div id='deeplink_1' class='deeplink_1' style='width:500px;height:180px;border:1px solid lightgrey;display:flex;flex-direction:column;'>"+
						"<br>"+
							"<div class='deeplname' style='display:inline-flex;'>&nbsp;&nbsp;"+
								"<div>Name&nbsp;&nbsp;</div>"+
								"<div>"+
								"<input type='text' id='deeplink_name'/>"+
								"</div>"+
							"</div>"+
							"<div class='deepllink' style='display:inline-flex;'>&nbsp;&nbsp;"+
								"<div>Link&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+
								"</div>"+
								"<div>"+
									"<input type='text' id='deeplink_link'/>"+
								"</div>"+
							"</div>"+
							"<div class='deeplkeys' style='display:inline-flex;'>&nbsp;&nbsp;"+
								"<div>Key&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+
								"</div>"+
								"<div>"+
									"<input type='button' id='deeplink_addkey' value='Add Key'/>"+
								"</div>"+
							"</div>"+
							"<br>"+
							"<div class='deeplfooter' style='float:right'>"+
								"<span>"+
									"<input type='button' id='deeplink_discard' value='Discard'/>"+
								"</span>"+
								"<span>"+
									"<input type='button' id='deeplink_save' value='Save'/>"+
								"</span>"+
							"</div>"+
						"</div>"+
					"</td>";
					// "<tr id='didididi'>"+
					// 	"<td class='"+claas+"'>"+
					// 	x+
					// 	"</td>"+
					// "</tr>";

		$(this).html(html);
		$(this).closest('tr').next('tr').children('td').children('div').removeClass('hide').addClass('add_deep_links');
		// $('.add_deep_links').off('click');

		$('#deeplink_addkey').click(function(e){
		var keyIdPrefix = $(e.currentTarget).parents('.deeplink_1').attr('id');
		var html = "<input type='text' id='"+keyIdPrefix+"_key_1'/>";
		$(this).html(html);
	});
	});

});



