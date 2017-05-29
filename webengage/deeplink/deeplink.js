$(document).ready(function(){
	deepLinkObject = [];

	$('.deeplink_addkey').live('click', function(e) {
		var keyIdPrefix = $(e.currentTarget).parents('.add_deep_links').attr('id');
		var keyId = $(e.currentTarget).attr('id');
		var keyIndexToBeAdded = keyId.substr(keyId.indexOf('_')+1);
		var prevAddedKeyId = keyIdPrefix+'_key_'+(parseInt(keyIndexToBeAdded)-1);
		var prevAddedKeyVal = $(e.currentTarget.previousElementSibling).children('div').children('input#'+prevAddedKeyId).val();

		if( !_.isEqual(keyIndexToBeAdded,"1") &&
				(_.isEmpty(prevAddedKeyVal) || _.isUndefined(prevAddedKeyVal)) ){
			alert('Please add entry for previous key before adding another key');
		}else{
			var html = "<div style='padding-left:4px; padding-top:2px;'>" +
						"<input type='text' id=" + keyIdPrefix + "_key_" + keyIndexToBeAdded + ">" +
						"<i class='fa fa-times keyCancel' style='padding-left:4px; padding-top:5px;'></i>" +
						"</div>";
			$(e.currentTarget).siblings('div#keysGoHere').append(html);
			$(e.currentTarget).attr({
				id: 'key_'+(parseInt(keyIndexToBeAdded)+1)
			});
			e.preventDefault();
			e.stopPropagation();
		}
		
	});

	$('.validateButton').live('click', function(e) {
		var name = $(this).parents('.deeplfooter').siblings('.scrollDiv').children('.deeplname').children('.nameVal').children('input#deeplink_name').val();
		var link = $(this).parents('.deeplfooter').siblings('.scrollDiv').children('.deepllink').children('.linkVal').children('input#deeplink_link').val();
		var keyDivId = $(this).parents('.deeplfooter').siblings('.scrollDiv').children('.deeplkeys').children('.deeplink_key_container').children('.deeplink_addkey').attr('id')
		var keysIndex = keyDivId.substr(keyDivId.indexOf('_')+1);
		var numOfKeys = parseInt(keysIndex)-1;

		if(_.isEmpty(name) || _.isUndefined(name)){
			alert('Please add a name');
			return;
		}

		if(_.isEmpty(link) || _.isUndefined(link)){
			alert('Please add a link');
			return;
		}

		var keyPrefix = $(this).parents('.deeplink').attr('id');
		var that = this;

		if( !_.isEqual(keysIndex,"1") ){
			var keyId = keyPrefix+'_key_'+(parseInt(keysIndex)-1);
			var keyVal = $(this).parents('.deeplfooter').siblings('.scrollDiv').children('.deeplkeys').children('.deeplink_key_container').children('#keysGoHere').children('div').children('input#'+keyId).val();
			
			if(_.isEmpty(keyVal) || _.isUndefined(keyVal)){
				alert('Please add entry for the last key');
				return;
			}else{
				var temp = createDeepLinkObject(name,link,keyPrefix,numOfKeys,that);
				deepLinkObject.push(temp);
				alert('Validated');
			}
			
		}else{
			var temp = createDeepLinkObject(name,link,keyPrefix,numOfKeys,that);
			deepLinkObject.push(temp);
			alert('Validated');
		}
	});

	$('.deepadd').live('click', function(e) {
		var deepLinkId = $(e.currentTarget).attr('id');
		var deepLinkIndex = $(e.currentTarget).attr('id').substr(deepLinkId.indexOf('_')+1);

		if(!_.isEqual(deepLinkIndex,"1")){
			if( !$('.deeplink_'+(parseInt(deepLinkIndex)-1)).hasClass('hide') ){
				alert('Please save previous deeplink before adding new one');
				return;
			}
		}

		var template = _.template($('#add_deeplink').html())({
			index: deepLinkIndex
		});
		$(e.currentTarget).html(template);
		var newId = parseInt(deepLinkIndex)+1;
		$('table#box-table-a input#addaccount__csrf_token').before("<tr>" +
																	"<td></td>" +
																	"<td>" +
																		"<div id='deeplink_" + newId + "' class='add_deep_links deepadd'>+ Add Deep Links</div>" +
																	"</td>");
		$(e.currentTarget).removeClass('deepadd');
		e.preventDefault();
		e.stopPropagation();
	});

	$('.keyCancel').live('click', function(e) {
		console.log('fgdg');
		$(e.currentTarget.parentElement).addClass('hide');
	});

});

function createDeepLinkObject(name,link,keyPrefix,numOfKeys,that){
	var temp = {};
	var keys = [];
	temp.name = name;
	temp.link = link;
	_.each($(that).parents('.deeplfooter').siblings('.scrollDiv').children('.deeplkeys').children('.deeplink_key_container').children('#keysGoHere').children(), function(child){
		if(child.classList.value.indexOf('hide') === -1) {
			keys.push(child.firstChild.value);
		}
	});
	temp.keys = keys;
	console.log(temp);
	return temp;
}