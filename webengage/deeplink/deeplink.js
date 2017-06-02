$(document).ready(function(){
	deepLinkObject = {};
	var previousDeepLinkVal = $('#addaccount__deeplink').val();
	if( !(_.isEmpty(previousDeepLinkVal) || _.isUndefined(previousDeepLinkVal)) ){
		deepLinkObject = JSON.parse(previousDeepLinkVal);
		var lastIndex = Object.keys(deepLinkObject)[Object.keys(deepLinkObject).length-1];
		$('.deepadd').attr({
			id: 'deeplink_'+(parseInt(lastIndex.substr(lastIndex.indexOf('_')+1))+1)
		});
	}

	$('.deeplink_addkey').live('click', function(e) {
		var keyIdPrefix = $(e.currentTarget).parents('.add_deep_links').attr('id');
		var keyId = $(e.currentTarget).attr('id');
		var keyIndexToBeAdded = keyId.substr(keyId.indexOf('_')+1);
		var prevAddedKeyId = keyIdPrefix+'_key_'+(parseInt(keyIndexToBeAdded)-1);
		var prevAddedKeyVal = $(e.currentTarget.previousElementSibling).children('div').children('input#'+prevAddedKeyId).val();

		var numOfKeys = 0;
		_.each($(e.currentTarget).siblings('#keysGoHere').children(), function(child){
			if(child.classList.value.indexOf('hide') === -1) {
				numOfKeys++;
			}
		});

		if( !_.isEqual(keyIndexToBeAdded,"1") && (numOfKeys > 0) &&
				(_.isEmpty(prevAddedKeyVal) || _.isUndefined(prevAddedKeyVal)) ){
			alert('Please add entry for previous key before adding another key');
		}else{
			var keyTemplate = _.template($('#add_key').html())({
									keyIdPrefix: keyIdPrefix,
									keyIndexToBeAdded: keyIndexToBeAdded
								});
			$(e.currentTarget).siblings('div#keysGoHere').append(keyTemplate);
			// var html = "<div style='padding-left:4px; padding-top:2px;'>" +
			// 			"<input type='text' id=" + keyIdPrefix + "_key_" + keyIndexToBeAdded + ">" +
			// 			"<i class='fa fa-times keyCancel' style='padding-left:4px; padding-top:5px;'></i>" +
			// 			"</div>";
			// $(e.currentTarget).siblings('div#keysGoHere').append(html);
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
		var keyDivId = $(this).parents('.deeplfooter').siblings('.scrollDiv').children('.deeplkeys').children('.deeplink_key_container').children('.deeplink_addkey').attr('id');
		var keysIndex = keyDivId.substr(keyDivId.indexOf('_')+1);
		var numOfKeys = 0;

		_.each($(this).parents('.deeplfooter').siblings('.scrollDiv').children('.deeplkeys').children('.deeplink_key_container').children('#keysGoHere').children(), function(child){
			if(child.classList.value.indexOf('hide') === -1) {
				numOfKeys++;
			}
		});

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

		if( !_.isEqual(keysIndex,"1") && !_.isEqual(numOfKeys,0)){
			var keyId = keyPrefix+'_key_'+(parseInt(keysIndex)-1);
			var hideCheck = $(this).parents('.deeplfooter').siblings('.scrollDiv').children('.deeplkeys').children('.deeplink_key_container').children('#keysGoHere').children('div').hasClass('hide');
			var keyVal = $(this).parents('.deeplfooter').siblings('.scrollDiv').children('.deeplkeys').children('.deeplink_key_container').children('#keysGoHere').children('div').children('input#'+keyId).val();
			
			if( !hideCheck && (_.isEmpty(keyVal) || _.isUndefined(keyVal))){
				alert('Please add entry for the last key');
				return;
			}else{
				deepLinkObject[keyPrefix] = createDeepLinkObject(name,link,keyPrefix,that);
				render(deepLinkObject);
				setHiddenValue(deepLinkObject);
				alert('Validated');
			}
			
		}else{
			deepLinkObject[keyPrefix]= createDeepLinkObject(name,link,keyPrefix,that);
			render(deepLinkObject);
			setHiddenValue(deepLinkObject);
			alert('Validated');
		}
	});

	$('.deepadd').live('click', function(e) {
		var deepLinkId = $(e.currentTarget).attr('id');
		var deepLinkIndex = $(e.currentTarget).attr('id').substr(deepLinkId.indexOf('_')+1);

		if(!_.isEqual(deepLinkIndex,"1")){
			if( !$('.deeplink_'+(parseInt(deepLinkIndex)-1)).hasClass('hide') && !$('#deeplink_'+(parseInt(deepLinkIndex)-1)).hasClass('addedBefore')){
				alert('Please save previous deeplink before adding new one');
				return;
			}
		}

		// var template = _.template($('#add_deeplink').html())({
		// 	index: deepLinkIndex
		// });
		// $(e.currentTarget).html(template);
		renderTemplate(e,deepLinkIndex);
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
		console.log('Cancelling Key');
		$(e.currentTarget.parentElement).addClass('hide');
	});

	$('.deepLinkDelete').live('click', function(e) {
		console.log('Deleting Link');
		delete deepLinkObject[e.currentTarget.id];
		if(_.isEqual("addedBefore",e.currentTarget.parentElement.className)){
			$(e.currentTarget).parent().addClass('hide');
		}else{
			$('.'+e.currentTarget.id).siblings('#deepLinkMessage').addClass('hide');
			render(deepLinkObject);
		}
		setHiddenValue(deepLinkObject);
	});

	$('.deepLinkEdit').live('click', function(e) {
		console.log('Editing Link');
		var tempId = e.currentTarget.id;
		var tempIndex = tempId.substr(tempId.indexOf('_')+1);
		// $('.'+e.currentTarget.id).removeClass('hide').siblings('#deepLinkMessage').addClass('hide');
		renderTemplate(e,tempIndex);
	});

	$('.discardButton').live('click', function(e) {
		var id = e.currentTarget.id;
		var deepId = id.substr(0,id.indexOf('_discard'));
		$('.'+deepId).addClass('hide');
	});

});

function createDeepLinkObject(name, link, keyPrefix, that){
	var temp = {};
	var keys = [];
	temp.name = name;
	temp.link = link;
	_.each($(that).parents('.deeplfooter').siblings('.scrollDiv').children('.deeplkeys').children('.deeplink_key_container').children('#keysGoHere').children(), function(child){
		if(child.classList.value.indexOf('hide') === -1) {
			keys.push(child.firstChild.nextSibling.value);
		}
	});
	temp.keys = keys;
	console.log(temp);
	return temp;
}

function render(deepLinkObject){
	var count = 0;
	for(var i in deepLinkObject){
		count++;
		$('.'+i).siblings('#deepLinkMessage').removeClass('hide').html(/*count+') '+*/deepLinkObject[i].name+' ( '+ deepLinkObject[i].keys.length+' Custom Keys )'+'&nbsp;&nbsp;&nbsp;<i class="fa fa-pencil deepLinkEdit" id='+i+'></i>&nbsp;&nbsp;&nbsp;<i class="icon-trash deepLinkDelete" id='+i+'></i>');
		$('.'+i).addClass('hide');
	}
}

function renderTemplate(e,deepLinkIndex){
	var template;
	if(_.isEqual("addedBefore",e.currentTarget.parentElement.className)){	//comes from a previously added link
		template = _.template($('#add_deeplink').html())({
						index: deepLinkIndex,
						name: deepLinkObject['deeplink_'+deepLinkIndex].name,
						link: deepLinkObject['deeplink_'+deepLinkIndex].link,
						keys: deepLinkObject['deeplink_'+deepLinkIndex].keys
					});
		$('#'+e.currentTarget.parentElement.id).html(template);
	}else if($(e.currentTarget).hasClass("deepadd")){	//comes from adding a new link
		template = _.template($('#add_deeplink').html())({
						index: deepLinkIndex,
						name: false,
						link: false,
						keys: false
					});
		$(e.currentTarget).html(template);
	}else{	//comes from edit flow
		$('.'+e.currentTarget.id).removeClass('hide').siblings('#deepLinkMessage').addClass('hide');
	}
	
}

function setHiddenValue(deepLinkObject){
	$('input[type="hidden"]#addaccount__deeplink').val(JSON.stringify(deepLinkObject));
}