$(document).ready( function() {
	hideDowngradeOnLoad();
	hideReturnOnLoad();
	$('.widget_title').hide();
	
	$('.cancel, .close').die('click').live('click', function(){
		var close = confirm("Changes will be lost. Proceed ? ");
		
		if( close ){
			$('#promotions_form').modal('hide');
			$("#tracker_strategy_form").modal('hide');
			$('#promotions_form').html('');
			$('#strategy_form').modal('hide');
			$('#strategy_form').html('');
			$('#scopes_form').modal('hide');
			$('#configure_sms_email').modal('hide');
			$('#configure_sms_email').html('');
			$('#scopes_form').html('');
			$(".formError").remove();
			$(".lp-tooltip").remove();
			$(".clickedLink").removeClass("clickedLink");
		}
	});
	
	$('.create_tier_cancel').die('click').live('click', function(){
		var close = confirm("Changes will be lost. Proceed ? ");
		if( close ){
			$('#create_tier_form').modal('hide');
			$(".formError").remove();
			$(".lp-tooltip").remove();
		}
	});
	
	$('.inline_block').die('mouseenter').live('mouseenter', function(){
		$(this).find('i').addClass('hover');
	});
	
	$('.inline_block').die('mouseout').live('mouseout', function(){
		$(this).find('i').removeClass('hover');
	});
	
	$('#reconfigure').die('click').live('click', function(){
		 
		 //handling the modal
		 $('#notif-modal').modal('show');
		 $('#mod-text').val('');

		 $('#mod-cancel').click(function(){
		 	$('#notif-modal').modal('hide');
		 	$(".lp-tooltip").remove();
		 });

		 
		 $('#mod-save').die('click').live('click', function(){
		 	var valid = true;

			 if(!$('#mod-text').val().trim()){
			 	var offset = $("textarea").offset();
				$("<div class='lp-tooltip' data-item='#mod-text'><div class='arrow-down'></div>This field cannot be empty</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				valid = false;
			 }

			 if($('#mod-text').val().length > 500){
			 	var offset = $("textarea").offset();
				$("<div class='lp-tooltip' data-item='#mod-text'><div class='arrow-down'></div>Please restrict your message to 500 characters</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				valid = false;
			 }
			 	
			 $('#mod-text').die('click').live('click', function(){
					$(".lp-tooltip").remove();
				});

			 if(valid){
			 	var close = confirm("This will NOT reconfigure any unsaved changes! Are you sure you want to proceed?");
			 	if(!close)
			 		return;

			 	var prefix = $('#prefix').val();
			 	var ajaxUrl = prefix + '/xaja/AjaxService/program/reconfigure.json?org_id='+$('#org_id').val();
			 	$('.wait_message').show().addClass('indicator_1');
			 	$.post( ajaxUrl, {
			 		user_msg: encodeURIComponent($('#mod-text').val())
			 	}, function(data) {
			 		if(!isOrgValid(data))
			 			return;
			 		if ( data.success ){
			 			setFlashMessage( data.success );

			 		}else{
			 			setFlashMessage( data.error, "error" );
			 		}
			 		$('.wait_message').show().removeClass('indicator_1');   
			 	}, 'json');

			 	$('#notif-modal').modal('hide');
			 }
			 
		 });
		 
	 });
	
	/** START - Get Strategy Form html for creation/updation of Strategies **/
	
	$('#create_allocation_strategy, #create_expiry_strategy,' +
				'#create_redemption_strategy').die('click').live('click', function(){
		$(this).addClass("clickedLink");
		var that=this;
		var is_promotion_selected = $("#prom_button").hasClass('sel');
		var selected_promotion_id = 'undefined';
		if($("#prom_button").length != 0){
			var selected_promotion = $(".proms_container.sel");
			selected_promotion_id = selected_promotion.attr("data-id");
		}
		
		var prefix = $('#prefix').val();
		renderAllSlab();
		$('#selected_action').val($(this).closest('form').find('[data-function="action_save"]').attr('data-action_type'));
		var ajaxUrl = prefix + '/xaja/AjaxService/program/strategy_form.json?org_id='+$('#org_id').val();
		$('.wait_message').show().addClass('indicator_1');
		$.post(ajaxUrl, { 
							program_id : $('#context_id').val(),
							strategy : $(this).attr('strategy'),
							id : "",
							action_type: $('#selected_action').val(),
							event_type : $('#selected_event_type').html(),
							is_promotion : is_promotion_selected,
							promotion_id : selected_promotion_id
						}, function(data) {
			if(!isOrgValid(data))
				return;
		   if (data.html != null){
			
			   $('#strategy_form').html( decodeURIComponent( data.html ) );
			   $('#strategy_form').removeData("modal").modal({backdrop: 'static', keyboard: false});
			   showHideExpiryValues();
		   }else{
			   $('#error').html( data.error ).css('text-align','center').show();
			   setTimeout(function(){ $('#error').fadeOut('fast'); }, 5000);
		   }
		   
		   $('.wait_message').removeClass('indicator_1');
		   
		}, 'json');
	  
	});
	
	/** END **/
	
	/** START - Edit strategies */
	
	$('div[data-function="strategy-edit"]').die('click').live('click', function(){
		
		var that=this;
		
		var is_promotion_selected = $("#prom_button").hasClass('sel');
		var selected_promotion_id = 'undefined';
		if($("#prom_button").length != 0){
			var selected_promotion = $(".proms_container.sel");
			selected_promotion_id = selected_promotion.attr("data-id");
		}
		
		var prefix = $('#prefix').val();
		var ajaxUrl = prefix + '/xaja/AjaxService/program/strategy_form.json?org_id='+$('#org_id').val();
		
		$('.wait_message').show().addClass('indicator_1');
		$.post(ajaxUrl, { 
							program_id : $('#program_id').val(),
							strategy : $(this).attr('data-strategy'),
							id : $(this).attr('data-id'),
							is_promotion : is_promotion_selected,
							promotion_id : selected_promotion_id
							
						}, function(data) {
			if(!isOrgValid(data))
				return;
		   if (data.html != null){ 
			   $('#strategy_form').html( decodeURIComponent( data.html ) );
   			   if ($(that).attr("data-strategy")=="point_allocation"){
   				   renderAllSlab();
   				   if( $('#allocation_strategy_home__strategy_name').val() == "PRORATED_SLAB0" || 
   						$('#allocation_strategy_home__strategy_name').val() == "PRORATED_SLAB_100_PERCENT"){
   					   $('div[data-function="strategy_save"]').remove();
   				   }
			   }
   			   
				if ($(that).attr("data-strategy")=="point_expiry"){
					showHideExpiryValues();
				}
   			   
   			   $('#strategy_form').removeData("modal").modal({backdrop: 'static', keyboard: false});
		   }else{
			   $('#error').html( data.error ).css('text-align','center').show();
			   setTimeout(function(){ $('#error').fadeOut('fast'); }, 5000);
		   }
		   
		   $('.wait_message').removeClass('indicator_1');
		   
		}, 'json');

	});
	
	$('div[data-function="strategy-delete"]').die('click').live('click', function(){
		
		var prefix = $('#prefix').val();
		var ajaxUrl = prefix + '/xaja/AjaxService/program/remove_strategy.json?org_id='+$('#org_id').val();
		$('.wait_message').show().addClass('indicator_1');

		$.post(ajaxUrl, { 
							strategy : $(this).attr('data-strategy'),
							id : $(this).attr('data-id')
						}, function(data) {
			if(!isOrgValid(data))
				return;
		   if (data.html != null){ 
			   $('#strategy_form').html( decodeURIComponent( data.html  ) );
			   $('#strategy_form').removeData("modal").modal({backdrop: 'static', keyboard: false});
		   }else{
			   $('#error').html( data.error ).css('text-align','center').show();
			   setTimeout(function(){ $('#error').fadeOut('fast'); }, 5000);
		   }
		   $('.wait_message').removeClass('indicator_1');
		}, 'json');

	});

	$('div[data-function="strategy_save"]').die('click').live('click', function(){
		var that = $(this);
		var form_id = $(this).attr("data-form-id") ;
		var strategy_type = $(this).attr('data-strategy');

		var bool=validateStrategy();
		if (bool==false) {
			return false;
		}

		if(!validateStrategyType(strategy_type)) {
			return false;
		}
		var prefix = $('#prefix').val();
		var ajaxUrl = prefix + '/xaja/AjaxService/program/save_strategy.json?' +
							'strategy=' + $(this).attr('data-strategy').toLowerCase() +
							'&id=' + $(this).attr('data-id') +
							'&program_id=' + $('#program_id').val() + 
							'&org_id='+$('#org_id').val()+
							'&event_type='+$('input[id$=event_type]').val()+
							'&action_type='+ $('#selected_action').val();
		
		var post_data = $( '#' + $(this).attr('data-form-id') ).serialize();
		
		$('.wait_message').show().addClass('indicator_1');
		$.post(ajaxUrl, post_data, function(data) {
			if(!isOrgValid(data))
				return;
		   if ( data.success ){
			   $('#strategy_form').modal('hide');
			   $('#strategy_form').html('');
			   if( form_id == "tier_downgrade_strategy_home"){
				   $('div[data-form-id=tier_downgrade_strategy_home]').html('Save').attr('data-id', data.id);
				   $('#tier_downgrade_strategy_home__tier_downgrade_strategy_id').val(data.id);
			   }else if(form_id == "expiry_strategy_home"){
			   		$('div#expiry_strategy_div_home').html(decodeURIComponent( data.html ));
			   }else if(form_id == "reminder_strategy") {
				   $('div#reminder_strategy_div_home').html(decodeURIComponent( data.html ));
				   that.attr('data-id',data.id);
			   }else{
				   $('#' + getStrategyTabId( strategy_type ) ).html( decodeURIComponent( data.html ) );
			   }
			   
			   var emf=$(".emf_rulesets");
			   if (emf.length!=0) {
				$(".clickedLink").prev().append('<option value="'+data.id+'">'+data.name+' </option>');
				$(".clickedLink").removeClass("clickedLink");
			   }
			   hideDowngradeOnLoad();
			   //renderTimeperiod();
			   setFlashMessage( data.success );
		   }else{
			   if(  that.attr('data-form-id') == "tier_downgrade_strategy_home" || that.attr('data-form-id') == "tier_upgrade_strategy_home" || that.attr('data-form-id') == "reminder_strategy"  || that.attr('data-form-id') == "return_strategy_home" )
			   		setFlashMessage( data.error, "error");
			   else{
				   	$('.alert-error').html( data.error ).css('text-align','center').removeClass("hide");
				   	setTimeout(function(){ $('.alert-error').addClass("hide"); }, 2000);
			   }
		   }
		   $('.wait_message').removeClass('indicator_1');
		}, 'json');

	});
	
	// on tier updagrade type change
	$('#tier_upgrade_strategy_home__current_value_type').live('change',
			function(){
		setFlashMessage("Change upgrade values for the tiers accordingly");
		if($('[id*="threshold_value"]').eq(0).attr('readonly')=="readonly")
			$('[data-function="threshold-value-edit"]').eq(0).click();
		$('[id*="threshold_value"]').eq(0).focus();
	});
	
	
	
	// Tier Updagrde page functions //START	
	
	/** Slab name and description enable edit **/
	$('div[data-function="slab-edit"]').die('click').live('click', function(){
		$(this).data("slabname",$('div[data-type="slab-name"][data-id=' +$(this).attr('data-id')+ ']').text().trim());
		$(this).data("slabdesc",$('div[data-type="slab-desc"][data-id=' +$(this).attr('data-id')+ ']').text());
		window.slabDesc=$('div[data-type="slab-desc"][data-id=' +$(this).attr('data-id')+ ']').text();
		$(this).addClass("d-none");
		$(this).prev().removeClass("d-none");
		$(this).prev().prev().removeClass("d-none");
		$('div[data-type="slab-name"][data-id=' +$(this).attr('data-id')+ ']').attr('contenteditable','true');
		$('div[data-type="slab-desc"][data-id=' +$(this).attr('data-id')+ ']').attr('contenteditable','true');
		$('div[data-type="slab-name"][data-id=' +$(this).attr('data-id')+ ']').css("border","2px solid #84b81d");
		$('div[data-type="slab-name"][data-id=' +$(this).attr('data-id')+ ']').focus();
	});
	
	$('div[data-function="slab-cancel"]').die('click').live('click', function(){
		var elem=$(this).closest(".program_slab").find("div[data-type='slab-name']");
		$("div.lp-tooltip[data-item="+elem.attr("data-id")+"]").remove();
		elem.text($('div[data-type="slab-desc"][data-id=5497]').next().find('[data-function="slab-edit"]').data("slabname"));
		$(this).addClass("d-none");
		$(this).prev().addClass("d-none");
		$(this).next().removeClass("d-none");
		$('div[data-type="slab-name"][data-id=' +$(this).attr('data-id')+ ']').removeAttr('contenteditable');
		$('div[data-type="slab-desc"][data-id=' +$(this).attr('data-id')+ ']').removeAttr('contenteditable');
		$('div[data-type="slab-desc"][data-id=' +$(this).attr('data-id')+ ']').text($('div[data-type="slab-desc"][data-id=5497]').next().find('[data-function="slab-edit"]').data("slabdesc"));
		$('div[data-type="slab-name"][data-id=' +$(this).attr('data-id')+ ']').text($(this).nextAll('.slab_name').text());
		$('div[data-type="slab-desc"][data-id=' +$(this).attr('data-id')+ ']').text($(this).nextAll('.slab_desc').text());
		$('div[data-type="slab-name"][data-id=' +$(this).attr('data-id')+ ']').css("border","");
	});
	
	$('div[data-function="slab-save"]').die('click').live('click', function(){
		
		var slab_id = $(this).attr('data-id');
		var slab_name = $('div[data-type="slab-name"][data-id=' +$(this).attr('data-id')+ ']').text().trim();
		var slab_desc = $('div[data-type="slab-desc"][data-id=' +$(this).attr('data-id')+ ']').text().trim();
		var elem=$(this).closest(".program_slab").find("div[data-type='slab-name']");
		var elem_desc=$(this).closest(".program_slab").find("div[data-type='slab-desc']");
		
		if(slab_desc==""&&slab_name=="") {
			var offset=elem.offset();
			$("<div class='lp-tooltip' data-item='"+elem.attr("data-id")+"' style ='word-wrap: break-word; width : 200px' ><div class='arrow-down'></div>slab name and description both cannot be empty</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			return false;
		} 

		if(slab_desc=="") {
			var offset=elem_desc.offset();
			$("<div class='lp-tooltip' data-item='"+elem_desc.attr("data-id")+"' style ='word-wrap: break-word; width : 200px' ><div class='arrow-down'></div>slab description cannot be empty</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			/*$('input[data-type="slab-name"][data-id=' +elem.attr('data-id')+ ']').css("border","");*/
			$('div[data-type="slab-desc"][data-id=' +elem_desc.attr('data-id')+ ']').focus();
			return false;
		} 
		
		if (slab_name!="") {
			var arr=$(".slab_name");
			var slabNames=[];
			$.each(arr, function(i,val) {
				if (slab_id!=$(val).attr("data-id")) {
					slabNames.push($(val).text().trim().toLowerCase());
				}	
			});
			found = $.inArray(slab_name.toLowerCase(), slabNames);
			if (found==-1) {
				$("div.lp-tooltip[data-item="+elem.attr("data-id")+"]").remove();
				createOrUpdateSlab( slab_id, slab_name, slab_desc, $(this));
			}else{
				var offset=elem.offset();
				$("<div class='lp-tooltip' style ='word-wrap: break-word; width : 200px' data-item='"+elem.attr("data-id")+"'><div class='arrow-down'></div>slab name cannot have duplicate values</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				return false;
		
			}
		
		}else{
			var offset=elem.offset();
			$("<div class='lp-tooltip' data-item='"+elem.attr("data-id")+"' style ='word-wrap: break-word; width : 200px'><div class='arrow-down'></div>slab name cannot be empty</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			return false;
		}
	});
	
	//Creation of slab
	$('#create_slab').die('click').live('click', function(){
		
		var arr=$(".slab_name");
		var slabNames=[];
		$.each(arr, function(i,val) {
			slabNames.push($(val).text().trim().toLowerCase());
		});
		$("div.lp-tooltip").remove();
		found = $.inArray($("#tier_name").val().trim().toLowerCase(), slabNames);
		if ($('#tier_name').val().trim().length!=0) {
			if (found==-1) {
			createOrUpdateSlab(-1, $('#tier_name').val(), $('#tier_desc').val() );
			}else{
				var elem=$("#tier_name");var offset=elem.offset();
				$("<div class='lp-tooltip'><div class='arrow-down'></div>Slab Name cannot have duplicate values</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			}	
		}else{
			var elem=$("#tier_name");var offset=elem.offset();
				$("<div class='lp-tooltip'><div class='arrow-down'></div>This field cant be empty</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
		}
		
	});
	
	$('div[data-function="threshold-value-edit"]').die('click').live('click', function(){
				
		if( $(this).html().trim() == "Edit" ){
			$(this).html('');
			$('input[id$=threshold_value\\[' + $(this).attr('data-id')+'\\]]').removeAttr('readonly');
		}else{
			
			var elem=$(this).closest(".program_slab");
			var prevValue=elem.prev().find("input[data-points=points-value]").val();
			var nextvalue=elem.next().find("input[data-points=points-value]").val();
			var curvalueString=$(this).prev().val();
			var offset=$(this).prev().offset();
			curvalue=parseInt(curvalueString);
			nextvalue=parseInt(nextvalue);
			prevValue=parseInt(prevValue);
			if (prevValue) {
				//code
			}else{
				prevValue=0;
			}
			if (nextvalue) {
				//code
			}else{
				nextvalue=Infinity;
			}

			if (curvalueString!="" && isNormalInteger(curvalueString)) {
				$("div.lp-tooltip[data-item="+elem.find("div[data-type='slab-name']").attr("data-id")+"]").remove();
			if (curvalue>prevValue && curvalue<nextvalue) {
					$("div.lp-tooltip[data-item="+elem.find("div[data-type='slab-name']").attr("data-id")+"]").remove();
					$(this).html('Edit');	
					$('input#tier_upgrade_strategy_home_threshold_value\\[' + $(this).attr('data-id')+'\\]' ).attr('readonly','readonly');
				}else{
					$("<div class='lp-tooltip' data-item='"+elem.find("div[data-type='slab-name']").attr("data-id")+"'>This upgrade limit is greater than that of the next tier or lesser than that of the previous tier</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				}
			}else{
				$("<div class='lp-tooltip' data-item='"+elem.find("div[data-type='slab-name']").attr("data-id")+"'><div class='arrow-down'></div>This field must be a positive integer</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			}
		}
	});
	
	
	$( "#right" ).die('click').live('click', function() {
		  var slabLeft=parseInt($( "#slab_block" ).css("left"));
		  var total=Math.abs(slabLeft)+$(window).width()+50;
		  if (total<$( "#slab_block" ).width()) 
		  	$( "#slab_block" ).css( "left","-=490px"  );
		 
	});
	 
	$( "#left" ).die('click').live('click',function(){
		var slabLeft=parseInt($( "#slab_block" ).css("left"));
		if (slabLeft<50)
			$( "#slab_block" ).css( "left","+=490px"  );
		
	});
	// Tier Updagrde page functions //STOP
	
	//Round Decimal support function
   $('div[data-function="round_decimals-edit"]').die('click').live('click', function(){
		if( $(this).html().trim() == "Edit" ){

			var close = confirm(" Do You really want to edit ? ");
			if( close )
			{
					$(this).html('Save');
					$('.round_decimals').each(function() { $(this).removeAttr('disabled');});
			}
			
		}else{
			var elem=$(this).prev();
			var that=this;
			var close = confirm(" do you really want to save ? ");
				if( !close )
					return;
				var value ='1';
				value = $('.round_decimals:checked').val();
				var prefix = $('#prefix').val();
				var program_id = $('#program_id').val();
				var ajaxUrl = prefix +'/xaja/AjaxService/program/set_round_decimals.json?org_id='+$('#org_id').val();
				$('.wait_message').show().addClass('indicator_1');
				$.post(ajaxUrl, { 
									program_id : program_id,
									round_decimals : value
								}, 
				   function(data) {

						if(!isOrgValid(data))
							return;
					   if (data.success != null){ 
						   $(that).html('Edit');	
						   setFlashMessage( data.success ); 
						   $('.round_decimals').each(function() { $(this).attr('disabled',true);});			   
					   }else{
						   setFlashMessage( data.error, 'error' );
					   }
					   $('.wait_message').removeClass('indicator_1');
				}, 'json');
			
		}
	});

	// points currency ratio update in redeemtion page
	$('div[data-function="points_currency_ratio-edit"]').die('click').live('click', function(){
		if( $(this).html().trim() == "Edit" ){
			var close = confirm("You really want to edit ? ");
			if( close )
			{
				$(this).html('Save');
				$('input#points_currency_ratio').removeAttr('readonly');
			}
			
		}else{
			var elem=$(this).prev();
			var that=this;
			var points_currency_ratio = $('input#points_currency_ratio').val()
			if ( (points_currency_ratio > 0) && isNormalIntegerPlusFloat(points_currency_ratio)) {
				$("div.lp-tooltip[data-item="+elem.attr("id")+"]").remove();
				var close = confirm("You really want to save ? ");
				if( !close )
					return;
				
				
				var prefix = $('#prefix').val();
				var program_id = $('#program_id').val();
				var ajaxUrl = prefix + '/xaja/AjaxService/program/set_points_currency_ratio.json?org_id='+$('#org_id').val();
				$('.wait_message').show().addClass('indicator_1');
				
				$.post(ajaxUrl, { 
									program_id : program_id,
									points_currency_ratio : $('input#points_currency_ratio').val()
								}, 
				   function(data) {
						if(!isOrgValid(data))
							return;
					   if (data.success != null){ 
						   $('input#points_currency_ratio').attr('readonly','readonly');	
						   $(that).html('Edit');	
						   setFlashMessage( data.success ); 
					   }else{
						   setFlashMessage( data.error, 'error' );
					   }
					   $('.wait_message').removeClass('indicator_1');
				}, 'json');
			}else{
				
				var offset=elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>This field value must be a greater than zero</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			}
		}
	});
	
	$('#tier_downgrade_strategy_home__is_active').die('click').live('click', function(){
		if( $(this).attr("checked") ){
			$('#tier_downgrade_div').show();
			$('input#tier_downgrade_strategy_home__is_active').attr("checked","checked");
		}
		else{
			$('#tier_downgrade_div').hide();
			$('input#tier_downgrade_strategy_home__is_active').removeAttr("checked")
		}
	});
	
	
	$('#tier_downgrade_strategy_home__alert_before_downgrade').die('click').live('click', function(){
		if($(this).attr('checked') == "checked"){
			$('#tier_downgrade_strategy_home__rem_days').parents('tr').show();
			$('#tier_downgrade_strategy_home__rem_days').parents('tr').next().show();
			$('#tier_downgrade_strategy_home__rem_days').parents('tr').next().next().next().show();
			$('#tier_downgrade_strategy_home__rem_days').parents('tr').next().next().next().next().next().show();
		}else{
			$('#tier_downgrade_strategy_home__rem_days').parents('tr').hide();
			$('#tier_downgrade_strategy_home__rem_days').parents('tr').next().hide();
			$('#tier_downgrade_strategy_home__rem_days').parents('tr').next().next().next().hide();
			$('#tier_downgrade_strategy_home__rem_days').parents('tr').next().next().next().next().next().hide();
		}
	});
	
	$('#tier_downgrade_strategy_home__confirm_after_downgrade, #tier_downgrade_strategy_home__confirm_after_renewal').die('click').live('click', function(){
		if($(this).attr('checked') == "checked"){
			$(this).parents('tr').next().show();
			$(this).parents('tr').next().next().next().show();
			$(this).parents('tr').next().next().next().next().next().show();
		}else{
			$(this).parents('tr').next().hide();
			$(this).parents('tr').next().next().next().hide();
			$(this).parents('tr').next().next().next().next().next().hide();
		}
	});
	
	$('.check_purchase').die('click').live('click', function(){
		if($(this).attr('checked') == "checked"){
			$(this).parents('tr').find('input[id^="tier_downgrade_strategy_home__purchase_"]').removeAttr('disabled');
		}else{
			$(this).parents('tr').find('input[id^="tier_downgrade_strategy_home__purchase_"]').attr('disabled','disabled');
			$(this).parents('tr').find('input[id^="tier_downgrade_strategy_home__purchase_"]').val(0);
		}
	});
	
	$('.check_num_visits').die('click').live('click', function(){
		if($(this).attr('checked') == "checked"){
			$(this).parents('tr').find('input[id^="tier_downgrade_strategy_home__num_visits_"]').removeAttr('disabled');			
		}else{
			$(this).parents('tr').find('input[id^="tier_downgrade_strategy_home__num_visits_"]').attr('disabled','disabled');
			$(this).parents('tr').find('input[id^="tier_downgrade_strategy_home__num_visits_"]').val(0);
		}
	});
	
	
	$('#reminder_strategy__is_active').die('click').live('click', function(){
		if( $(this).attr("checked") ){
			$('#reminder_strategy_div').show();
		}
		else{
			$('#reminder_strategy_div').hide();
			$('input#reminder_strategy__is_active').removeAttr("checked");
		}
	});
	
	$('input[id^=tier_downgrade_strategy_home__should_downgrade_]').die('click').live('click', function(){
		if($(this).attr('checked') == "checked"){
			$(this).parents('tr').next().show();
			$(this).parents('tr').next().next().show();
			$(this).parents('tr').next().next().next().show();
		}else{
			$(this).parents('tr').next().hide();
			$(this).parents('tr').next().next().hide();
			$(this).parents('tr').next().next().next().hide();
		}
		
	});
	
	$('input[class=reminder_strategy_check_points]').die('click').live('click', function(){
		if($(this).attr('checked')=="checked") {
		    $(this).next().attr("disabled",false) ;
			   $(this).next().addClass("reg_positive");
		} else {
			$(this).next().val('') ;
			$(this).next().attr("disabled",true) ;
			   $(this).next().removeClass("reg_positive");
		}
	});
	
	$('input[id^=tier_downgrade_strategy_home__condition_always_]').die('click').live('click', function(){
		
		if( ($(this).val() == "true" && $(this).attr('checked') == "checked" ) || 
			($(this).val() == "false" && $(this).attr('checked') != "checked" )){
			
			$(this).parents('td').next().hide();
			$(this).parents('td').next().next().hide();
			$(this).parents('td').next().next().next().hide();
			$(this).parents('td').next().next().next().next().hide();
		}else{
			$(this).parents('td').next().show();
			$(this).parents('td').next().next().show();
			$(this).parents('td').next().next().next().show();
			$(this).parents('td').next().next().next().next().show();
		}
		
	});
	
	$('input[id=reminder_strategy__alert_always]').die('click').live('click', function(){
		
		if($(this).val() == "true" ){
			$(this).nextAll('div:first').hide();
		}else{
			$(this).nextAll('div:first').show();
		}
		
	});

//All the sms are from one place

	$('div[data-function="sms_create"],div[data-function="sms_update"]').die('click').live('click', function (){
		removeErrors();
		//used to get tags
		var sms_scope = $(this).attr('data-scope') ;
		var sms_email_type = $(this).attr('data-type') ;
		var sms_content = "" ;
		var sms_sender_id = "";
		$('#email_sms_scope').val(sms_scope);
		$('#email_sms_data_type').val(sms_email_type);
		switch(sms_email_type) {
			case 'slab_upgrade' :
				var slab_id = $(this).attr('data-id') ;
				$('#email_sms_data_id').val(slab_id);
				sms_content = $('input[id$=slab_sms\\[' + slab_id+'\\]]' ).val() ;
				sms_sender_id = $('input[id$=slab_sms_sender_id\\[' + slab_id+'\\]]' ).val() ;
				break;
			case 'create_tier_upgrade':
				var sms_template_array = wiz_datasrc.json.data.strategies.tier_upgrade.sms_template ;
				var sms_sender_id_array = wiz_datasrc.json.data.strategies.tier_upgrade.sms_sender_id ;
				sms_content = sms_template_array[sms_template_array.length -1] ;
				sms_sender_id = sms_sender_id_array[sms_sender_id_array.length -1] ;
				break;
			case 'expiry_reminder':
				sms_content = $('#reminder_strategy__sms_template').val();
				break;
			case 'slab_downgrade_confirmation':
				sms_content =$('#tier_downgrade_strategy_home__confirmation_sms_template').val();
				break;
			case 'slab_downgrade_reminder':
				sms_content = $('#tier_downgrade_strategy_home__reminder_sms_template').val();
				break;
			case 'slab_renewal_confirmation':
				sms_content = $('#tier_downgrade_strategy_home__renewal_confirmation_sms_template').val();
				break;
		}
		getSMSSettingPage(sms_content,sms_scope, sms_sender_id);
	});

	$('div[data-function="sms_delete"]').die('click').live('click', function (){
		var close = confirm("Are you sure you want to delete");
		if( !close )
			return;

		var sms_scope = $(this).attr('data-scope') ;
		var sms_email_type = $(this).attr('data-type') ;
		var sms_btn = "";
		var data_id_attr = "";
		switch(sms_email_type) {
		case 'slab_upgrade' :
			var slab_id = $(this).attr('data-id') ;
			$('input[id$=slab_sms\\[' + slab_id+'\\]]' ).val("") ;
			data_id_attr  = 'data-id="' + slab_id + '"' ;
			break;
		case 'create_tier_upgrade':
			/**
				*  TODO wiz_datasrc values initialization present in tier_creation
			**/
			var sms_template_array = wiz_datasrc.json.data.strategies.tier_upgrade.sms_template ;
			sms_template_array[sms_template_array.length -1]="" ;
			break;
		case 'expiry_reminder':
			sms_btn = "SMS - Not set";
			$('#reminder_strategy__sms_template').val("");
			break;
		case 'slab_downgrade_confirmation':
			sms_btn = "Set";
			$('#tier_downgrade_strategy_home__confirmation_sms_template').val("");
			break;
		case 'slab_downgrade_reminder':
			sms_btn = "Set";
			$('#tier_downgrade_strategy_home__reminder_sms_template').val("");
			break;
		case 'slab_renewal_confirmation':
			sms_btn = "Set";
			$('#tier_downgrade_strategy_home__renewal_confirmation_sms_template').val("");
			break;
	}

	if(sms_email_type=='slab_upgrade' || sms_email_type=='create_tier_upgrade' ) {
		$(this).parent().prev().remove();
		$('<div class="btn" data-function="sms_create"' + data_id_attr + 'data-type = "'+sms_email_type + '" data-scope = "'+sms_scope+'">Configure</div>')
											.insertBefore( $(this).parent() );
		$(this).parent().after('<div class="row-cont">Not set</div>');
		$(this).parent().remove();
	} else {
		$(this).prev().remove();
		$(this).prev().remove();
		$(this).after(
		'<div class = "btn" data-function = "sms_create" data-type = "'+sms_email_type + '"data-scope = "'+sms_scope+'">'+sms_btn+'</div>' );	
		$(this).remove();
	}


	});

	//All the emails are from one place
		$('div[data-function="email_create"]').die('click').live('click', function (){
		removeErrors();
		//used to get tags
		$('#email_sms_scope').val($(this).attr('data-scope'));
		$('#email_sms_data_type').val($(this).attr('data-type'));
		if($(this).attr('data-id'))
			$('#email_sms_data_id').val($(this).attr('data-id'));
	
		getEmailSettingPage();
	});


	$('div[data-function="email_update"]').die('click').live('click', function (){
		removeErrors();
		//used to get tags
		var sms_email_type = $(this).attr('data-type') ;
		var sms_email_scope = $(this).attr('data-scope') ;
		var email_subject = "" ;
		var email_body = "" ;
		var slab_id ;
	switch(sms_email_type) {
		case 'slab_upgrade' :
			var slab_id = $(this).attr('data-id') ;
			email_subject = $('input[id$=slab_email_subject\\[' + slab_id+'\\]]' ).val() ;
			email_body = $('input[id$=slab_email_body\\[' + slab_id+'\\]]' ).val() ;
			break;
		case 'create_tier_upgrade':
			var email_subject_array = wiz_datasrc.json.data.strategies.tier_upgrade.email_subject ;
			var email_body_array = wiz_datasrc.json.data.strategies.tier_upgrade.email_body ;
			email_subject = email_subject_array[email_subject_array.length -1] ;
			email_body=email_body_array[email_subject_array.length -1]
			break;
		case 'expiry_reminder':
			email_subject = $('#reminder_strategy__email_subject').val();
			email_body = $('#reminder_strategy__email_body').val();
			break;
		case 'slab_downgrade_confirmation':
			email_subject=$('#tier_downgrade_strategy_home__confirmation_email_subject' ).val();
			email_body =$('#tier_downgrade_strategy_home__confirmation_email_body' ).val();
			break;
		case 'slab_downgrade_reminder':
			email_subject =  $('#tier_downgrade_strategy_home__reminder_email_subject' ).val();
			email_body =  $('#tier_downgrade_strategy_home__reminder_email_body' ).val();
			break;
		case 'slab_renewal_confirmation':
			email_subject = $('#tier_downgrade_strategy_home__renewal_confirmation_email_subject' ).val();
			email_body = $('#tier_downgrade_strategy_home__renewal_confirmation_email_body' ).val()
			break;
	}
		updateEmail( email_subject, decodeURIComponent(email_body), sms_email_scope,sms_email_type,slab_id );
	});

	$('div[data-function="email_delete"]').die('click').live('click', function (){
		var close = confirm("Are you sure you want to delete");
		if( !close )
			return;

		var email_scope = $(this).attr('data-scope') ;
		var sms_email_type = $(this).attr('data-type') ;
		var email_btn = "";
		var data_id_attr = "";
		switch(sms_email_type) {
		case 'slab_upgrade' :
			var slab_id = $(this).attr('data-id') ;
			$('input[id$=slab_email_subject\\[' + slab_id+'\\]]' ).val("") ;
			$('input[id$=slab_email_body\\[' + slab_id+'\\]]' ).val("") ;
			data_id_attr  = 'data-id="' + slab_id + '"' ;
			break;
		case 'create_tier_upgrade':
			var email_subject_array = wiz_datasrc.json.data.strategies.tier_upgrade.email_subject ;
			var email_body_array = wiz_datasrc.json.data.strategies.tier_upgrade.email_body ;
			email_subject_array[email_subject_array.length -1]="" ;
			email_body_array[email_subject_array.length -1]="";
			break;
		case 'expiry_reminder':
			email_btn = "Email - Not set";
			$('#reminder_strategy__email_subject').val("");
			$('#reminder_strategy__email_body').val("");
			break;
		case 'slab_downgrade_confirmation':
			email_btn = "Set";
			$('#tier_downgrade_strategy_home__confirmation_email_subject').val("");
			$('#tier_downgrade_strategy_home__confirmation_email_body' ).val("");
			break;
		case 'slab_downgrade_reminder':
			email_btn = "Set";
			$('#tier_downgrade_strategy_home__reminder_email_subject' ).val("");
			$('#tier_downgrade_strategy_home__reminder_email_body' ).val("");
			break;
		case 'slab_renewal_confirmation':
			email_btn = "Set";
			$('#tier_downgrade_strategy_home__renewal_confirmation_email_subject' ).val("");
			$('#tier_downgrade_strategy_home__renewal_confirmation_email_body' ).val("")
			break;
	}

	if(sms_email_type=='slab_upgrade' || sms_email_type=='create_tier_upgrade' ) {
		$(this).parent().prev().remove();
		$('<div class="btn" data-function="email_create"' + data_id_attr + 'data-type = "'+sms_email_type + '" data-scope = "'+email_scope+'">Configure</div>')
											.insertBefore( $(this).parent() );
		$(this).parent().after('<div class="row-cont">Not set</div>');
		$(this).parent().remove();
	} else {
		$(this).prev().remove();
		$(this).prev().remove();
		$(this).after(
		'<div class = "btn" data-function = "email_create" data-type = "'+sms_email_type + '"data-scope = "'+email_scope+'">'+email_btn+'</div>' );	
		$(this).remove();
	}


	});

		/*Wechat Configure*/
	$('div[data-function="wechat_create"],div[data-function="wechat_update"]').die('click').live('click', function (){
		//removeErrors();
		//used to get tags
		var wechat_scope = $(this).attr('data-scope') ;
		var wechat_type = $(this).attr('data-type') ;
		var wechat_id = "" ;
		$('#email_sms_scope').val($(this).attr('data-scope'));
		$('#email_sms_data_type').val($(this).attr('data-type'));
		
		switch(wechat_type) {
			case 'slab_upgrade' :
				var slab_id = $(this).attr('data-id') ;
				$('#email_sms_data_id').val(slab_id);
				wechat_id = $('input[id$=slab_wechat\\[' + slab_id+'\\]]' ).val() ;
				wechat_acc_id = $('input[id$=slab_wechat_acc_id\\[' + slab_id+'\\]]' ).val() ;
				break;
			case 'slab_downgrade_reminder':
				wechat_id = $('#tier_downgrade_strategy_home__reminder_wechat_id').val();
				wechat_acc_id = $('#tier_downgrade_strategy_home__reminder_wechat_acc_id').val();
				break;
			case 'slab_downgrade_confirmation':
				wechat_id = $('#tier_downgrade_strategy_home__confirmation_wechat_id').val();
				wechat_acc_id = $('#tier_downgrade_strategy_home__confirmation_wechat_acc_id').val();
				break;
			case 'slab_renewal_confirmation':
				wechat_id = $('#tier_downgrade_strategy_home__renewal_wechat_id').val();
				wechat_acc_id = $('#tier_downgrade_strategy_home__renewal_wechat_acc_id').val();
			case 'expiry_reminder':
				wechat_id = $('#reminder_strategy__wechat_id').val();
				wechat_acc_id = $('#reminder_strategy__wechat_acc_id').val();
				break;
			case 'create_tier_upgrade':
				var wechat_id_array = wiz_datasrc.json.data.strategies.tier_upgrade.wechat_id ;
				wechat_id = wechat_id_array[wechat_id_array.length -1];
				var wechat_acc_id_array = wiz_datasrc.json.data.strategies.tier_upgrade.wechat_acc_id ;
				wechat_acc_id = wechat_acc_id_array[wechat_acc_id_array.length -1];
				break;

		
		}
		
		getWechatSettingPage(wechat_id,wechat_scope,wechat_acc_id);
	});

$('div[data-function="wechat_delete"]').die('click').live('click', function (){
		var close = confirm("Are you sure you want to delete");
		if( !close )
			return;

		var wechat_scope = $(this).attr('data-scope') ;
		var wechat_type = $(this).attr('data-type') ;
		var sms_btn = "";
		var data_id_attr = "";
		switch(wechat_type) {
		case 'slab_upgrade' :
			var slab_id = $(this).attr('data-id') ;
			$('input[id$=slab_wechat\\[' + slab_id+'\\]]' ).val("") ;
			$('input[id$=slab_wechat_acc_id\\[' + slab_id+'\\]]' ).val("") ;
			data_id_attr  = 'data-id="' + slab_id + '"' ;
			break;
			case 'slab_downgrade_confirmation':
			sms_btn = "Set";
			$('#tier_downgrade_strategy_home__confirmation_wechat_id').val("");
			$('#tier_downgrade_strategy_home__confirmation_wechat_acc_id').val("");
			break;
		case 'slab_downgrade_reminder':
			sms_btn = "Set";
			$('#tier_downgrade_strategy_home__reminder_wechat_id').val("");
			$('#tier_downgrade_strategy_home__reminder_wechat_acc_id').val("");
			break;
		case 'slab_renewal_confirmation':
			sms_btn = "Set";
			$('#tier_downgrade_strategy_home__renewal_wechat_id').val("");
			$('#tier_downgrade_strategy_home__renewal_wechat_acc_id').val("");
			break;
		case 'expiry_reminder':
			sms_btn = "WeChat - Not set";
			$('#reminder_strategy__wechat_id').val("");
			$('#reminder_strategy__wechat_acc_id').val("");
			break;
		case 'create_tier_upgrade':
			var wechat_id_array = wiz_datasrc.json.data.strategies.tier_upgrade.wechat_id ;
			wechat_id_array[wechat_id_array.length -1]="" ;
			break;
	}


	if(wechat_type=='slab_upgrade' || wechat_type=='create_tier_upgrade' ) {
		$(this).parent().prev().remove();
		$('<div class="btn" data-function="wechat_create"' + data_id_attr + 'data-type = "'+wechat_type + '" data-scope = "'+wechat_scope+'">Configure</div>')
											.insertBefore( $(this).parent() );
		$(this).parent().after('<div class="row-cont">Not set</div>');
		$(this).parent().remove();
	} else {
		$(this).prev().remove();
		$(this).prev().remove();
		$(this).after(
		'<div class = "btn" data-function = "wechat_create" data-type = "'+wechat_type + '"data-scope = "'+wechat_scope+'">'+sms_btn+'</div>' );	
		$(this).remove();
	}


	});

	$('#edit_sender_id').die('click').live('click', function () {
		var senderIdValue = $('#sms_setting__sender_id_value').html();
		$( "input[name='sms_setting__input_sender_id']" ).val(senderIdValue);
		$('#sms_setting__show_sender_id').hide();
		$('#sms_setting__edit_sender_id').show();
		$('#save_sms').removeClass("creative-assets-button-active");
		$('#save_sms').addClass("disable-save-button");
	});
	
	$('#save_sender_id').die('click').live('click', function () {
		var senderIdValue = $( "input[name='sms_setting__input_sender_id']" ).val().trim();
		if(senderIdValue.length == 0){
			var elem= $("input[name='sms_setting__input_sender_id']");
			var offset=elem.offset();
			$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>* Sender ID has to be mentioned</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			return false;
		}
		$('#sms_setting__sender_id_value').html(senderIdValue);
		$('#sms_setting__edit_sender_id').hide();
		$('#sms_setting__show_sender_id').show();
		$('#save_sms').removeClass("disable-save-button");
		$('#save_sms').addClass("creative-assets-button-active");
	});
	
	$('#cancel_sender_id').die('click').live('click', function () {
		$('#sms_setting__edit_sender_id').hide();
		$('#sms_setting__show_sender_id').show();
		$('#save_sms').removeClass("disable-save-button");
		$('#save_sms').addClass("creative-assets-button-active");
	});
	
	$('#save_sms').die('click').live('click', function () {
		
		if(!checkUnicode($('.editor').html()))
			return;
		
		if( $('#endpoint').val()  == "POINTS_ENGINE" ){
			//write code to get value
			var formId=$(".clickedLink").text("Configured").closest("form").attr("id");
			if(!validateSMSEmailTags($('#sms_setting__sms').val(),window.sms_email_tags))
					return;
			$("#"+formId+"__sms_content").val($('#sms_setting__sms').val());
			$("#"+formId+"__sms_sender_id").val($('#sms_setting__sender_id_value').html());
			$(".clickedLink").removeClass("clickedLink")
			$('#configure_sms_email').modal('hide');
		}else
			saveSMSEmail('SMS');
	});

	$('#save_wechat').die('click').live('click', function () {
		if($('#wechat_templates__wechat_temp_org').val()==0)
		{
			alert("Wechat is not selected");
			return;
		}
		if($('#wechat_templates__wechat_temp_acc_id').val()==0)
		{
			alert("Wechat account is not selected");
			return;
		}
		if( $('#endpoint').val()  == "POINTS_ENGINE" ){
			//write code to get value
			var formId=$(".clickedLink").text("Configured").closest("form").attr("id");
			$("#"+formId+"__wechat_id").val($('#wechat_templates__wechat_temp_org').val());
			$("#"+formId+"__wechat_acc_id").val($('#wechat_templates__wechat_temp_acc_id').val());
			$(".clickedLink").removeClass("clickedLink")
			$('#configure_sms_email').modal('hide');
		}
		else
			saveSMSEmail('WECHAT');
	});
	
	$('#save_email').die('click').live('click', function () {
		
		if( $('#endpoint').val() == "POINTS_ENGINE" ){
			var formId=$(".clickedLink").text("Configured").closest("form").attr("id");
			if ($("#create_edit_email__email_subject").val().trim().length!=0) {
				if(CKEDITOR.instances.create_edit_email__email_body.getData().trim().length==0){
					alert('Email subject and body cannot be empty');
					return false;
				}
				if(!validateSMSEmailTags(CKEDITOR.instances.create_edit_email__email_body.getData(),window.sms_email_tags))
					return;
				
				if(!validateSMSEmailTags($("#create_edit_email__email_subject").val(),window.sms_email_tags))
					return;
				
				$("#"+formId+"__email_subject").val($("#create_edit_email__email_subject").val());
				$("#"+formId+"__email_body").val(encodeURIComponent(CKEDITOR.instances.create_edit_email__email_body.getData()));
				$(".clickedLink").removeClass("clickedLink");
				$('#configure_sms_email').modal('hide');	
			}else{
				var elem=$('#create_edit_email__email_subject');
				var offset=elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>This field cannot be empty</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				return false;
			}
		}else
			saveSMSEmail('EMAIL');
	});
	
	$("input").die("keyup").live("keyup",function(){
		removeErrors();
	});
	
	/*Get html of timeperiod field*/
	tpHtml=$("#tier_downgrade_strategy_home__time_period").clone().wrap('<p>').parent().html();
	renderTimeperiod();
	
	$("#tier_downgrade_strategy_home__condition").die("change").live("change",function(){
		renderTimeperiod();
		
	});
	
	$('input[id^=tier_downgrade_strategy_home__time_period_]').die("change").live("change",function(){
		$(this).parents('tr').next().find('.time_period_helptext').html('in last ' +$(this).val()+  ' months &nbsp;&nbsp;&nbsp;');
	});
	
	$("#return_strategy_home__is_reissual_enabled").die('click').live('click', function () {
		hideReturnOnLoad();
	});
	
});

$('select[id$=allocation_module]').die('change').live('change',function(){
	if($(this).val()=="CAMPAIGN"){
		if($('#allocation_strategy_home__alloc_type').val() != "FIXED")
			$('#allocation_strategy_home__alloc_type').val("FIXED") ;
		$('#allocation_strategy_home__alloc_type option[value="PRORATED"]').hide();
		$('#allocation_strategy_home__alloc_type option[value="POINTS_MULTIPLIER"]').hide();
		$('#allocation_strategy_home__custom_field_enabled').closest('tr').hide();
	}	
	else{
		$('#allocation_strategy_home__alloc_type option[value="PRORATED"]').show();
		$('#allocation_strategy_home__alloc_type option[value="POINTS_MULTIPLIER"]').show();
		$('#allocation_strategy_home__custom_field_enabled').closest('tr').show();
	}
});

function isValidTag(tag,tagsList) {
	if ($.inArray(tag, tagsList) != -1) {
		return true;
	} else {
		return false;
	}
}

function validateSMSEmailTags(code,tagsList) {
	var length = code.length;
	for ( var i = 0; i < length; i++) {
		if (code.charAt(i) == '{') {
			if (code.charAt(++i) == '{') {
				var tag = "";
				for (j = i; j < length; j++) {
					if (code.charAt(++i) == '}' && code.charAt(++i) == '}') {
						if (!isValidTag(tag,tagsList)) {
							if(!tag.match(/^SURVEY__TOKEN__(.).*__END__TOKEN$/)){
								alert(tag + " is an invalid tag");
								return false;
							}
						}
						break;
					} else {
						tag = tag + code.charAt(i);
					}
				}
			}
		}
	}
	return true;
}

function removeErrors(){
	$(".lp-tooltip").remove();
}


function changeCurrentValueType( ele ){
	$('.current_value_type').html( $('#' + ele.id  + ' option:selected').html() );
}

$('select[id$=strategy_filter]').die("change").live('change',function(e) {
	var strategy_type = $(this).attr('id').split('_')[0];
	switch($(this).val()) {
		case  "All_MODULES":
			$('.'+strategy_type+'_loyalty_module').closest(".row_view").show();
			$('.'+strategy_type+'_campaign_module').closest(".row_view").show();
			break;
		case "LOYALTY":
			$('.'+strategy_type+'_loyalty_module').closest(".row_view").show();
			$('.'+strategy_type+'_campaign_module').closest(".row_view").hide();
			break;
		case "CAMPAIGN":
			$('.'+strategy_type+'_loyalty_module').closest(".row_view").hide();
			$('.'+strategy_type+'_campaign_module').closest(".row_view").show();
			break;

	}
});

function createOrUpdateSlab( slab_id, slab_name, slab_desc, ele ){
	
	var program_id = $('#program_id').val();
	
	var prefix = $('#prefix').val();

	ele.nextAll('.slab_name').text(slab_name);
	ele.nextAll('.slab_desc').text(slab_desc);

	var ajaxUrl = prefix + '/xaja/AjaxService/program/slab_save.json?org_id='+$('#org_id').val();

	$('.wait_message').show().addClass('indicator_1');
	$.post(ajaxUrl, { 
						program_id : program_id,
						slab_id : slab_id,
						slab_name : encodeURIComponent(slab_name),
						slab_desc : encodeURIComponent(slab_desc),
						max_serial_number : $('#max_serial_number').val()
					}, 
	   function(data) {
			if(!isOrgValid(data))
				return;
		   if (data.success != null){ 
			   if( slab_id == -1 ){
				   $('#create_tier_form').modal('hide');
				   $('#tier_upgrade').html( decodeURIComponent( data.tier_upgrade_html ));
				   $('#tier_downgrade').html( decodeURIComponent( data.tier_downgrade_html ));
				   hideDowngradeOnLoad();
			   }
			   ele.addClass("d-none");
			   ele.next().addClass("d-none");
			   ele.next().next().removeClass("d-none");
			   $('div[data-type="slab-name"][data-id=' +ele.attr('data-id')+ ']').removeAttr('contenteditable');
			   $('div[data-type="slab-desc"][data-id=' +ele.attr('data-id')+ ']').removeAttr('contenteditable');
			   $('div[data-type="slab-name"][data-id=' +ele.attr('data-id')+ ']').css("border","");
			   setFlashMessage( data.success ); 
		   }else{
			   setFlashMessage( data.error,'error' ); 
		   }
		   $('.wait_message').removeClass('indicator_1');
	}, 'json');
}

function setFlashMessage( message, type ){
	
	 $('.flash_message').html( message );
	 $('.flash_message').html($('.flash_message').attr('title',$('.flash_message').html()).html().substr(0,150)+'...');
	 $('.flash_message').removeClass('redError');
	 if( type == "error")
		 $('.flash_message').addClass('redError');
	 $('.flash_message').show();
	 setTimeout(function(){ $('.flash_message').fadeOut('fast'); }, 5000);
	 
}

function getStrategyTabId( strategy_type ){
	
	switch ( strategy_type )
	{
		case 'slab_upgrade': return "tier_upgrade";
		case 'slab_downgrade': return "tier_downgrade";
		case 'point_allocation': return "allocation" ;
		case 'point_redemption_threshold': return "redemption";
		case 'point_expiry': return "expiry";
		case 'point_return': return "return";
		default:  "";
		  
	}
}

function getEmailSettingPage(){
	var prefix = $('#prefix').val();
	var ajaxUrl = prefix + '/xaja/AjaxService/program/get_email_setting_form.json?org_id='+$('#org_id').val();
	$('.wait_message').show().addClass('indicator_1');
	$.post(ajaxUrl, {}, function(data){
		if(!isOrgValid(data))
			return;
		$('#configure_sms_email').html( decodeURIComponent( data.html ) );
		$('#configure_sms_email').removeData("modal").modal({backdrop: 'static', keyboard: false});
		$('#configure_sms_email').removeClass("wechat_configure");
		$('.wait_message').removeClass('indicator_1');
	}, 'json');
}

function getSMSSettingPage( sms_content, type, sms_sender_id ){
	var prefix = $('#prefix').val();
	var ajaxUrl = prefix + '/xaja/AjaxService/program/get_sms_setting_form.json?org_id='+$('#org_id').val();
	$('.wait_message').show().addClass('indicator_1');
	$.post(ajaxUrl, { sms_content : sms_content,
					  sms_sender_id : sms_sender_id,
					  type : type 
					}, function(data){
		if(!isOrgValid(data))
			return;
		$('#configure_sms_email').html( decodeURIComponent( data.html ));
		$('#configure_sms_email').removeData("modal").modal({backdrop: 'static', keyboard: false});
		$('#configure_sms_email').removeClass("wechat_configure");
		$('.wait_message').removeClass('indicator_1');
		$('.editor').trigger('keyup');
	}, 'json');
}
function fetchWechatDetail(wechat_id)
{
  if(wechat_id)
     fetchWechatDetailsPreview(wechat_id);
   
    $(document).on('change','#wechat_templates__wechat_temp_org',function(){
      wechat_templateval=$("#wechat_templates__wechat_temp_org").find(":selected").val();
      console.log(wechat_templateval);

    if(wechat_templateval==0)
    {
      $(".wechat_template_block").hide();
    }
   else{
        fetchWechatDetailsPreview(wechat_templateval);
   }   
  
   });
}

function fetchWechatDetailsPreview(wechat_id)
{
   		var value = "wechat_id=" + wechat_id;   
        var prefix = $("#prefix").val();
        var ajaxUrl = prefix+ '/xaja/AjaxService/program/fetch_wechat_detail.json?org_id='+$('#org_id').val();  

        $('.wait_message').show().addClass('indicator_1');
        $.post(ajaxUrl,value,function(data) {
       if (data.status == 'success'){
        $(".wechat_template_block").show();
        $("#template_wechat").text(data.temp_detail['Title']);
        $("#template_wechat_contain").html(data.temp_detail['content']);
        var tempstr = data.temp_detail['Url'];
        var tempurl = tempstr.substring(tempstr.indexOf("callback="),tempstr.indexOf("&scope")).split('=')[1];
        var tempsend = (tempstr!='{{wechat_service_acc_url}}') ? ( (tempstr.indexOf("&callback=") == -1) ? tempstr : tempurl  ) : '';
        $("#wechat_deatils").text(decodeURIComponent(tempsend));
         $('.wait_message').removeClass('indicator_1');
       }else {
        alert("Error while fetching wechat template detail.");
           
       }
        }, 'json');
}

function getWechatSettingPage( wechat_id, type,wechat_acc_id){
	 
	var prefix = $('#prefix').val();
	var ajaxUrl = prefix + '/xaja/AjaxService/program/get_wechat_setting_form.json?org_id='+$('#org_id').val();
	$('.wait_message').show().addClass('indicator_1');
	$.post(ajaxUrl, { wechat_id : wechat_id,
					  type : type,
					  wechat_acc_id : wechat_acc_id 
					}, function(data){
		if(!isOrgValid(data))
			return;

		$('#configure_sms_email').html( decodeURIComponent( data.html ));
		$('#configure_sms_email').removeData("modal").modal({backdrop: 'static', keyboard: false});
		$('#configure_sms_email').addClass("wechat_configure");
		$('.wait_message').removeClass('indicator_1');
		$('.editor').trigger('keyup');

		 fetchWechatDetail(wechat_id);
	}, 'json');
	  
    
}

function saveSMSEmail( type ){
	var data_type = $('#email_sms_data_type').val();
	var data_id = $('#email_sms_data_id').val();
	var program_id = $('#program_id').val();
	var sms_content = "";
	var sms_sender_id = "";
	var email_subject = "";
	var email_body = "";
	
	if( type == 'SMS'){
		sms_content = $('#sms_setting__sms').val();
		sms_sender_id = $('#sms_setting__sender_id_value').html();
		if( sms_content.trim() == ""  ){
			alert('SMS cannot be empty..');
			return;
		}
		if(!validateSMSEmailTags(sms_content,window.sms_email_tags))
			return;
	}
	else if( type == 'EMAIL' ){
		email_subject = $('#create_edit_email__email_subject').val();
		email_body = CKEDITOR.instances.create_edit_email__email_body.getData();
		if( email_subject.trim() == "" || email_body.trim() == "" ){
			alert('Email subject and body cannot be empty');
			return;
		}
		if (!validateSMSEmailTags(email_body,window.sms_email_tags))
			return;

		if(data_type=='slab_downgrade_reminder' || data_type=='slab_downgrade_confirmation' || data_type=='slab_renewal_confirmation') {
			var newTagList = window.sms_email_tags ;
			newTagList.splice($.inArray(newTagList,'{{unsubscribe}}'),1);
		}
		
		if (!validateSMSEmailTags(email_subject,newTagList))
			return;
	}
	else if (type == 'WECHAT'){
	
		  var wechat_template_id=$('#wechat_templates__wechat_temp_org').val();
		  var wechat_acc_id=$('#wechat_templates__wechat_temp_acc_id').val();

	}
	switch ( data_type ){

		case 'slab_upgrade':
			if( type == 'SMS'){
				$('input[id$=slab_sms\\[' + data_id+'\\]]' ).val( sms_content );
				$('input[id$=slab_sms_sender_id\\[' + data_id+'\\]]' ).val( sms_sender_id );
				$('div[data-function="sms_create"][data-type="slab_upgrade"][data-id='+ data_id + ']').next().remove();
				$('div[data-function="sms_create"][data-type="slab_upgrade"][data-id='+ data_id + ']').after(
						'<div class="btn disabled clicked" data-id="' + data_id + '">Configured</div>' + 						
						'<div class="row-cont">' +
						'<div class="inline_pointer green bold" data-function="sms_update" data-type ="slab_upgrade" data-scope = "SLAB_UPGRADE_SMS" data-id="' + data_id + '">&nbsp;&nbsp;Edit </div>' +
						'<div class="inline_pointer bold" data-function="sms_delete" data-type ="slab_upgrade" data-scope = "SLAB_UPGRADE_SMS" data-id="' + data_id + '">Delete</div>' +
						'</div>' );
				
				$('div[data-function="sms_create"][data-type="slab_upgrade"][data-id='+ data_id + ']').remove();
								
				$('#configure_sms_email').modal('hide');
				
				if ($(".clicked")[0]) {
					$(".clicked").attr("title",sms_content).removeClass("clicked");
				}else{
					$(".clickedEdit").closest(".row-cont").prev().attr("title",sms_content);
					$(".clickedEdit").removeClass("clickedEdit");
				}
				
			}
			
			if( type == 'EMAIL' ){
				$('input[id$=slab_email_subject\\[' + data_id+'\\]]' ).val(email_subject) ;
				$('input[id$=slab_email_body\\[' + data_id+'\\]]' ).val(encodeURIComponent(email_body)) ;
				$('div[data-function="email_create"][data-type="slab_upgrade"][data-id='+ data_id + ']').next().remove();
				$('div[data-function="email_create"][data-type="slab_upgrade"][data-id='+ data_id + ']').after(
						'<div class="btn disabled clicked" data-id="' + data_id + '">Configured</div>' + 						
						'<div class="row-cont">' +
						'<div class="inline_pointer green bold" data-function="email_update" data-type ="slab_upgrade" data-scope = "SLAB_UPGRADE" data-id="' + data_id + '">&nbsp;&nbsp;Edit </div>' +
						'<div class="inline_pointer bold" data-function="email_delete" data-type ="slab_upgrade" data-scope = "SLAB_UPGRADE" data-id="' + data_id + '"> Delete </div>' +
						'</div>' );
				$('div[data-function="email_create"][data-type="slab_upgrade"][data-id='+ data_id + ']').remove();
				
				$('#configure_sms_email').modal('hide');
				var clicked=$(".clicked");
				
				if (clicked[0]) {
					$(".clicked").attr("title",email_subject).removeClass("clicked");
				}else{
					$(".clickedEdit").closest(".row-cont").prev().attr("title",email_subject);
					$(".clickedEdit").removeClass("clickedEdit")
				}
			}

			if( type == 'WECHAT'){
				$('input[id$=slab_wechat\\[' + data_id+'\\]]' ).val( wechat_template_id );
				$('input[id$=slab_wechat_acc_id\\[' + data_id+'\\]]' ).val( wechat_acc_id );
				$('div[data-function="wechat_create"][data-type="slab_upgrade"][data-id='+ data_id + ']').next().remove();
				$('div[data-function="wechat_create"][data-type="slab_upgrade"][data-id='+ data_id + ']').after(
						'<div class="btn disabled clicked" data-id="' + data_id + '">Configured</div>' + 						
						'<div class="row-cont">' +
						'<div class="inline_pointer green bold" data-function="wechat_update" data-type ="slab_upgrade" data-scope = "SLAB_UPGRADE_WECHAT" data-id="' + data_id + '">&nbsp;&nbsp;Edit </div>' +
						'<div class="inline_pointer bold" data-function="wechat_delete" data-type ="slab_upgrade" data-scope = "SLAB_UPGRADE_WECHAT" data-id="' + data_id + '">Delete</div>' +
						'</div>' );
				
				$('div[data-function="wechat_create"][data-type="slab_upgrade"][data-id='+ data_id + ']').remove();
								
				$('#configure_sms_email').modal('hide');

				if ($(".clicked")[0]) {
					$(".clicked").attr("title",wechat_template_id).removeClass("clicked");
				}else{
					$(".clickedEdit").closest(".row-cont").prev().attr("title",wechat_template_id);
					$(".clickedEdit").removeClass("clickedEdit");
				}
				
			}
			
			break;

		case 'create_tier_upgrade':
			if( type == 'SMS'){
	
		var sms_template_array = wiz_datasrc.json.data.strategies.tier_upgrade.sms_template ;
		var sms_sender_id_array = wiz_datasrc.json.data.strategies.tier_upgrade.sms_sender_id;
		sms_template_array[sms_template_array.length -1] = sms_content;
		sms_sender_id_array[sms_sender_id_array.length -1] = sms_sender_id;
				$('div[data-function="sms_create"][data-type="create_tier_upgrade"]').next().remove();
				$('div[data-function="sms_create"][data-type="create_tier_upgrade"]').after(
						'<div class="btn disabled clicked" >Configured</div>' + 						
						'<div class="row-cont" style="display:inline-flex">' +
						'<div class="inline_pointer green bold" data-function="sms_update" data-type="create_tier_upgrade" data-scope="SLAB_UPGRADE_SMS">&nbsp;&nbsp;Edit </div>' +
						'<div class="inline_pointer bold" data-function="sms_delete" data-type="create_tier_upgrade" data-scope="SLAB_UPGRADE_SMS" >&nbsp;&nbsp;Delete</div>' +
						'</div>' );
				
				$('div[data-function="sms_create"][data-type="create_tier_upgrade"]').remove();
				
				$('#configure_sms_email').modal('hide');
				$(".clickedEdit").removeClass("clickedEdit")
				
			}
			
			if( type == 'EMAIL' ){

		var email_subject_array = wiz_datasrc.json.data.strategies.tier_upgrade.email_subject ;
		email_subject_array[email_subject_array.length -1] = email_subject;

		var email_body_array = wiz_datasrc.json.data.strategies.tier_upgrade.email_body ;
		email_body_array[email_subject_array.length -1] = email_body;
				
				$('div[data-function="email_create"][data-type="create_tier_upgrade"]').next().remove();
				$('div[data-function="email_create"][data-type="create_tier_upgrade"]').after(
						'<div class="btn disabled clicked">Configured</div>' + 						
						'<div class="row-cont" style="display:inline-flex">' +
						'<div class="inline_pointer green bold" data-function="email_update" data-type="create_tier_upgrade" data-scope="SLAB_UPGRADE">&nbsp;&nbsp;Edit </div>' +
						'<div class="inline_pointer bold" data-function="email_delete" data-type="create_tier_upgrade" data-scope="SLAB_UPGRADE"> &nbsp;&nbsp;Delete </div>' +
						'</div>' );
				$('div[data-function="email_create"][data-type="create_tier_upgrade"]').remove();
				
				$('#configure_sms_email').modal('hide');
				$(".clickedEdit").removeClass("clickedEdit")
				
			}

			if( type == 'WECHAT' ){

		var wechat_array = wiz_datasrc.json.data.strategies.tier_upgrade.wechat_id ;
		wechat_array[wechat_array.length -1] = wechat_template_id;
		var wechat_acc_id_array = wiz_datasrc.json.data.strategies.tier_upgrade.wechat_acc_id ;
		wechat_acc_id_array[wechat_acc_id_array.length -1] = wechat_acc_id;

				$('div[data-function="wechat_create"][data-type="create_tier_upgrade"]').next().remove();
				$('div[data-function="wechat_create"][data-type="create_tier_upgrade"]').after(
						'<div class="btn disabled clicked">Configured</div>' + 						
						'<div class="row-cont" style="display:inline-flex">' +
						'<div class="inline_pointer green bold" data-function="wechat_update" data-type="create_tier_upgrade" data-scope="SLAB_UPGRADE">&nbsp;&nbsp;Edit </div>' +
						'<div class="inline_pointer bold" data-function="wechat_delete" data-type="create_tier_upgrade" data-scope="SLAB_UPGRADE"> &nbsp;&nbsp;Delete </div>' +
						'</div>' );
				$('div[data-function="wechat_create"][data-type="create_tier_upgrade"]').remove();
				
				$('#configure_sms_email').modal('hide');
				$(".clickedEdit").removeClass("clickedEdit")
				
			}
			
			
			break;
					
		case 'slab_downgrade_reminder':
			
			if( type == 'SMS'){
				
				$('#tier_downgrade_strategy_home__reminder_sms_template' ).val( sms_content );
				
				$(' div[data-function="sms_create"][data-type="slab_downgrade_reminder"]').after('<div class="btn disabled clicked">Configured</div>' +
						'<div class="inline_pointer bold green" data-function="sms_update" data-type="slab_downgrade_reminder" data-scope="SLAB_DOWNGRADE_REMINDER_SMS" >&nbsp;&nbsp;Edit &nbsp;</div>' +
						'<div class="inline_pointer bold" data-function="sms_delete" data-type="slab_downgrade_reminder" data-scope="SLAB_DOWNGRADE_REMINDER_SMS"> Delete</div>' );
				
				$(' div[data-function="sms_create"][data-type="slab_downgrade_reminder"]').remove();
				
				$('#configure_sms_email').modal('hide');
				if ($(".clicked")[0]) {
					$(".clicked").attr("title",sms_content).removeClass("clicked");
				}else{
					$(".clickedEdit").prev().attr("title",sms_content);
					$(".clickedEdit").removeClass("clickedEdit");
				}
			
			}else if( type == 'EMAIL' ){
				$('#tier_downgrade_strategy_home__reminder_email_subject').val( email_subject );
				$('#tier_downgrade_strategy_home__reminder_email_body').val(encodeURIComponent( email_body ));
				
				$('div[data-function="email_create"][data-type="slab_downgrade_reminder"]').after(
						"<div class = 'btn disabled clicked'  >Configured</div>" + 
						"<div class = 'inline_pointer bold green' data-function = 'email_update' data-type='slab_downgrade_reminder' data-scope='SLAB_DOWNGRADE_REMINDER' >&nbsp;&nbsp;Edit&nbsp; </div>" +
						"<div class = 'inline_pointer bold' data-function = 'email_delete' data-type='slab_downgrade_reminder' data-scope='SLAB_DOWNGRADE_REMINDER'>Delete &nbsp;&nbsp;&nbsp;</div>"
					 );
				
				$('div[data-function="email_create"][data-type="slab_downgrade_reminder"]').remove();
				
				if ($(".clicked")[0]) {
					$(".clicked").attr("title",email_subject).removeClass("clicked");
				}else{
					$(".clickedEdit").prev().attr("title",email_subject);
					$(".clickedEdit").removeClass("clickedEdit")
				}
				$('#configure_sms_email').modal('hide');
			}
			else if(type == 'WECHAT')
			{
				$('#tier_downgrade_strategy_home__reminder_wechat_id' ).val( wechat_template_id );
				$(' div[data-function="wechat_create"][data-type="slab_downgrade_reminder"]').after('<div class="btn disabled clicked">Configured</div>' +
						'<div class="inline_pointer bold green" data-function="wechat_update" data-type="slab_downgrade_reminder" data-scope="SLAB_DOWNGRADE_REMINDER_WECHAT">&nbsp;&nbsp;Edit &nbsp;</div>' +
						'<div class="inline_pointer bold" data-function="wechat_delete" data-type="slab_downgrade_reminder" data-scope="SLAB_DOWNGRADE_REMINDER_WECHAT" > Delete</div>' );
				
				$(' div[data-function="wechat_create"][data-type="slab_downgrade_reminder"]').remove();
			
				$('#configure_sms_email').modal('hide');
				if ($(".clicked")[0]) {
					$(".clicked").attr("title",wechat_template_id).removeClass("clicked");
				}else{
					$(".clickedEdit").prev().attr("title",wechat_template_id);
					$(".clickedEdit").removeClass("clickedEdit");
				}
				
			}
			
			break;
		case 'slab_downgrade_confirmation':
			
			if( type == 'SMS'){
				
				$('#tier_downgrade_strategy_home__confirmation_sms_template' ).val( sms_content );
				
				$(' div[data-function="sms_create"][data-type="slab_downgrade_confirmation"]').after('<div class="btn disabled clicked">Configured</div>' +
						'<div class="inline_pointer bold green" data-function="sms_update" data-type="slab_downgrade_confirmation" data-scope="SLAB_DOWNGRADE_CONFIRMATION_SMS">&nbsp;&nbsp;Edit &nbsp;</div>' +
						'<div class="inline_pointer bold" data-function="sms_delete" data-type="slab_downgrade_confirmation" data-scope="SLAB_DOWNGRADE_CONFIRMATION_SMS" > Delete</div>' );
				
				$(' div[data-function="sms_create"][data-type="slab_downgrade_confirmation"]').remove();
			
				$('#configure_sms_email').modal('hide');
				if ($(".clicked")[0]) {
					$(".clicked").attr("title",sms_content).removeClass("clicked");
				}else{
					$(".clickedEdit").prev().attr("title",sms_content);
					$(".clickedEdit").removeClass("clickedEdit");
				}
				
			}else if( type == 'EMAIL' ){

				$('#tier_downgrade_strategy_home__confirmation_email_subject').val( email_subject );
				$('#tier_downgrade_strategy_home__confirmation_email_body').val( encodeURIComponent(email_body ));
				
				$('div[data-function="email_create"][data-type="slab_downgrade_confirmation"]').after(
						"<div class = 'btn disabled clicked'  >Configured</div>" + 
						"<div class = 'inline_pointer bold green' data-function = 'email_update' data-type='slab_downgrade_confirmation' data-scope='SLAB_DOWNGRADE_CONFIRMATION'>&nbsp;&nbsp;Edit&nbsp; </div>" +
						"<div class = 'inline_pointer bold' data-function = 'email_delete' data-type='slab_downgrade_confirmation' data-scope='SLAB_DOWNGRADE_CONFIRMATION' >Delete &nbsp;&nbsp;&nbsp;</div>"
					 );
				
				$('div[data-function="email_create"][data-type="slab_downgrade_confirmation"]').remove();
				
				$('#configure_sms_email').modal('hide');
	
				if ($(".clicked")[0]) {
					$(".clicked").attr("title",email_subject).removeClass("clicked");
				}else{
					
					$(".clickedEdit").prev().attr("title",email_subject);
					$(".clickedEdit").removeClass("clickedEdit")
				}
			}
			else if(type == 'WECHAT')
			{
				$('#tier_downgrade_strategy_home__confirmation_wechat_id' ).val( wechat_template_id );
				$(' div[data-function="wechat_create"][data-type="slab_downgrade_confirmation"]').after('<div class="btn disabled clicked">Configured</div>' +
						'<div class="inline_pointer bold green" data-function="wechat_update" data-type="slab_downgrade_confirmation" data-scope="SLAB_DOWNGRADE_CONFIRMATION_WECHAT">&nbsp;&nbsp;Edit &nbsp;</div>' +
						'<div class="inline_pointer bold" data-function="wechat_delete" data-type="slab_downgrade_confirmation" data-scope="SLAB_DOWNGRADE_CONFIRMATION_WECHAT" > Delete</div>' );
				
				$(' div[data-function="wechat_create"][data-type="slab_downgrade_confirmation"]').remove();
			
				$('#configure_sms_email').modal('hide');
				if ($(".clicked")[0]) {
					$(".clicked").attr("title",wechat_template_id).removeClass("clicked");
				}else{
					$(".clickedEdit").prev().attr("title",wechat_template_id);
					$(".clickedEdit").removeClass("clickedEdit");
				}
				
			}
			break;
		
		case 'slab_renewal_confirmation':
			
			if( type == 'SMS'){
				
				$('#tier_downgrade_strategy_home__renewal_confirmation_sms_template' ).val( sms_content );
				
				$(' div[data-function="sms_create"][data-type="slab_renewal_confirmation"]').after('<div class="btn disabled clicked">Configured</div>' +
						'<div class="inline_pointer bold green" data-function="sms_update" data-type="slab_renewal_confirmation" data-scope="SLAB_RENEWAL_CONFIRMATION_SMS">&nbsp;&nbsp;Edit &nbsp;</div>' +
						'<div class="inline_pointer bold" data-function="sms_delete" data-type="slab_renewal_confirmation" data-scope="SLAB_RENEWAL_CONFIRMATION_SMS"> Delete</div>' );
				
				$(' div[data-function="sms_create"][data-type="slab_renewal_confirmation"]').remove();
			
				$('#configure_sms_email').modal('hide');
				if ($(".clicked")[0]) {
					$(".clicked").attr("title",sms_content).removeClass("clicked");
				}else{
					$(".clickedEdit").prev().attr("title",sms_content);
					$(".clickedEdit").removeClass("clickedEdit");
				}
				
			}else if( type == 'EMAIL' ){

				$('#tier_downgrade_strategy_home__renewal_confirmation_email_subject').val( email_subject );
				$('#tier_downgrade_strategy_home__renewal_confirmation_email_body').val( encodeURIComponent(email_body ));
				
				$('div[data-function="email_create"][data-type="slab_renewal_confirmation"]').after(
						"<div class = 'btn disabled clicked'  >Configured</div>" + 
						"<div class = 'inline_pointer bold green' data-function = 'email_update' data-type='slab_renewal_confirmation' data-scope='SLAB_RENEWAL_CONFIRMATION'>&nbsp;&nbsp;Edit </div>" +
						"<div class = 'inline_pointer bold' data-function = 'email_delete' data-type='slab_renewal_confirmation' data-scope='SLAB_RENEWAL_CONFIRMATION'>Delete &nbsp;&nbsp;&nbsp;</div>"
					 );
				
				$('div[data-function="email_create"][data-type="slab_renewal_confirmation"]').remove();
				
				$('#configure_sms_email').modal('hide');
	
				if ($(".clicked")[0]) {
					$(".clicked").attr("title",email_subject).removeClass("clicked");
				}else{
					
					$(".clickedEdit").prev().attr("title",email_subject);
					$(".clickedEdit").removeClass("clickedEdit")
				}

			}
			else if(type == 'WECHAT')
			{
				$('#tier_downgrade_strategy_home__renewal_wechat_id' ).val( wechat_template_id );
				$(' div[data-function="wechat_create"][data-type="slab_renewal_confirmation"]').after('<div class="btn disabled clicked">Configured</div>' +
						'<div class="inline_pointer bold green" data-function="wechat_update" data-type="slab_renewal_confirmation" data-scope="SLAB_RENEWAL_CONFIRMATION_WECHAT">&nbsp;&nbsp;Edit &nbsp;</div>' +
						'<div class="inline_pointer bold" data-function="wechat_delete" data-type="slab_renewal_confirmation" data-scope="SLAB_RENEWAL_CONFIRMATION_WECHAT" > Delete</div>' );
				
				$(' div[data-function="wechat_create"][data-type="slab_renewal_confirmation"]').remove();
			
				$('#configure_sms_email').modal('hide');
				if ($(".clicked")[0]) {
					$(".clicked").attr("title",wechat_template_id).removeClass("clicked");
				}else{
					$(".clickedEdit").prev().attr("title",wechat_template_id);
					$(".clickedEdit").removeClass("clickedEdit");
				}
				
			}
			break;
			
		case 'expiry_reminder':
			
			if( type == 'SMS'){
				
				$('#reminder_strategy__sms_template' ).val( sms_content );
				
				$(' div[data-function="sms_create"][data-type="expiry_reminder"]').after('<div class="btn disabled clicked">SMS Configured</div>' +
						'<div class="inline_pointer bold green" data-function="sms_update" data-type="expiry_reminder" data-scope="POINTS_EXPIRY_REMINDER_SMS">&nbsp;&nbsp;Edit&nbsp;</div>' +
						'<div class = "inline_pointer bold" data-function = "sms_delete" data-type="expiry_reminder" data-scope="POINTS_EXPIRY_REMINDER_SMS">Delete &nbsp;&nbsp;&nbsp;</div>');
				
				$(' div[data-function="sms_create"][data-type="expiry_reminder"]').remove();
				
				$('#configure_sms_email').modal('hide');
				if ($(".clicked")[0]) {
					$(".clicked").attr("title",sms_content).removeClass("clicked");
				}else{
					
					$(".clickedEdit").prev().attr("title",sms_content);
					$(".clickedEdit").removeClass("clickedEdit");
				}
			
			}
			else if( type == 'EMAIL' ){

				$('#reminder_strategy__email_subject').val( email_subject );
				$('#reminder_strategy__email_body').val( encodeURIComponent(email_body ));
				
				$('div[data-function="email_create"][data-type="expiry_reminder"]').after(
						"<div class = 'btn disabled clicked'  >Configured Email</div>" + 
						"<div class = 'inline_pointer bold green' data-function = 'email_update' data-type='expiry_reminder' data-scope='POINTS_EXPIRY_REMINDER' >&nbsp;&nbsp;Edit&nbsp; </div>" +
						"<div class = 'inline_pointer bold' data-function = 'email_delete' data-type='expiry_reminder' data-scope='POINTS_EXPIRY_REMINDER' >Delete &nbsp;&nbsp;&nbsp;</div>"
					 );
				
				$('div[data-function="email_create"][data-type="expiry_reminder"]').remove();
				
				$('#configure_sms_email').modal('hide');
				if ($(".clicked")[0]) {
					$(".clicked").attr("title",email_subject).removeClass("clicked");
				}else{
					$(".clickedEdit").prev().attr("title",email_subject);
					$(".clickedEdit").removeClass("clickedEdit")
				}
			}
			else if(type =='WECHAT')
			{
				$('#reminder_strategy__wechat_id' ).val( wechat_template_id );
				$(' div[data-function="wechat_create"][data-type="expiry_reminder"]').after('<div class="btn disabled clicked">Configured</div>' +
						'<div class="inline_pointer bold green" data-function="wechat_update" data-type="expiry_reminder" data-scope="POINTS_EXPIRY_REMINDER_WECHAT">&nbsp;&nbsp;Edit &nbsp;</div>' +
						'<div class="inline_pointer bold" data-function="wechat_delete" data-type="expiry_reminder" data-scope="POINTS_EXPIRY_REMINDER_WECHAT" > Delete</div>' );
				
				$(' div[data-function="wechat_create"][data-type="expiry_reminder"]').remove();
			
				$('#configure_sms_email').modal('hide');
				if ($(".clicked")[0]) {
					$(".clicked").attr("title",wechat_template_id).removeClass("clicked");
				}else{
					$(".clickedEdit").prev().attr("title",wechat_template_id);
					$(".clickedEdit").removeClass("clickedEdit");
				}
			}
			break;
			
		default:  "";
			
		  
	}
}


function updateEmail( email_subject, email_body, data_scope,data_type,slab_id ){
	
	var ajaxUrl = '/xaja/AjaxService/program/edit_email_form.json?org_id='+$('#org_id').val(); 
	
	$('#email_sms_scope').val( data_scope );

	$('#email_sms_data_type').val( data_type );
	$('#email_sms_data_id').val( slab_id );
	$('.wait_message').show().addClass('indicator_1');
		
	$('.template_preview').contents().find('html').html("<img src='/images/newUI/loader-big.gif'"+
		" class='center' id='template_load' style='margin-left:40%;margin-top:25%'>");
	var selected_ruleset_id = getSelectedRulesetLoyaltyProgram();
	$.post(ajaxUrl, { 
						email_subject : encodeURIComponent( email_subject ),
						email_body : encodeURIComponent( email_body ),
						email_scope : $('#email_sms_scope').val(),
						selected_ruleset_id : selected_ruleset_id
					},
			function(data){
				if(!isOrgValid(data))
					return;
				$('#configure_sms_email').html('<div class="container-title">' +
						 '<div class="float-left">Email Setting' + 
						 '</div>' + 
						 '<button type="button" class="email_cancel close" >×</button>' +
						 '</div>' +
						 '<div id = "email-preview" name = "email-preview" class = "hide" />' +  
						 '<div id = "email-edit" name = "email-edit">' + decodeURIComponent( data.html )+ '</div>' 
						 );
				
				$('#configure_sms_email').removeData("modal").modal({backdrop: 'static', keyboard: false});
				$('.wait_message').removeClass('indicator_1');
			}, 'json');
}

$(document).ready(function(){
	$('#allocation_strategy_home__slab_based, #allocation_strategy_home__custom_field_enabled').die("click").live('click',function(e){
		$(".lp-tooltip").remove();
		activeToogle();
	});
	
	$('#expiry_strategy_home__slab_based').die("click").live('click',function(e){
		$(".lp-tooltip").remove();
		showHideExpiryValues();
	});
	
	$('select[id^=expiry_strategy_home__expiry_time_units]').die("change").live('change',function(e){
		$(".lp-tooltip").remove();
		showHideExpiryValues();
	});

})

/*This removes validation messages when user moves from one from to anoter form*/
$('#program_configuration').live('click',function(e){
	$(".formError").remove();
	$(".lp-tooltip").remove();
});
/*This removes validation when user moves from one from to anoter form*/


/*tooltip*/
$(document).ready(function(){
	$(document).on("click",".lp-tooltip",function(e){
		$(e.target).remove();	
	})	
})

/*positive integer reg check*/
function isNormalInteger(str) {
    return /^\+?(0|[1-9]\d*)$/.test(str);
}

/*positive integer also float reg check*/
function isNormalIntegerPlusFloat(str) {
    return /^[+]?([0-9]+(?:[\.][0-9]*)?|\.[0-9]+)(?:[eE][+-][0-9]+)?$/.test(str);
}


//activateToogle also does same as renderallslab with a special condition to check all slab is "ON" or "OFF" When Custom field is moved from On to OFF
function activeToogle() {
	
	/*hard coded*/
	if ($("#allocation_strategy_home__custom_field_enabled").hasClass("active")) {
		$("#allocation_strategy_home [id^=allocation_strategy_home__alloc_value_]").each( function(){
			$(this).closest('tr').hide();
		});
		$("#allocation_strategy_home #allocation_strategy_home__description").closest("tr").hide();
		$("#allocation_strategy_home #allocation_strategy_home__all_slabs").closest("tr").hide();
		
		//hides POINTS_MULTIPLIER select option, and sets 'FIXED' as selected option
		if($("#allocation_strategy_home__alloc_type").val() == 'POINTS_MULTIPLIER')
			$("#allocation_strategy_home__alloc_type option[value='FIXED']").attr('selected','selected');
		$("#allocation_strategy_home__alloc_type option[value='POINTS_MULTIPLIER']").hide();
	}else{
		if ($("#allocation_strategy_home__allocation_module").val() == "LOYALTY") {
			$("#allocation_strategy_home__alloc_type option[value='POINTS_MULTIPLIER']").show();
		}
		if ($("#allocation_strategy_home__slab_based").hasClass("active")) {
			setTimeout(function(){
				$("#allocation_strategy_home [id^=allocation_strategy_home__alloc_value_]").each( function(){
					$(this).closest('tr').hide();
				});
				
				$("#allocation_strategy_home #allocation_strategy_home__description").closest("tr").show();
				$("#allocation_strategy_home #allocation_strategy_home__all_slabs").closest("tr").show();
			},100)
			
		}else{
			setTimeout(function(){
				$("#allocation_strategy_home [id^=allocation_strategy_home__alloc_value_]").each( function(){
					$(this).closest('tr').show();
				});
				$("#allocation_strategy_home #allocation_strategy_home__description").closest("tr").show()
				$("#allocation_strategy_home #allocation_strategy_home__all_slabs").closest("tr").hide()
			},100)
		}	
	}
	
	
}


//Render all slab is for rendering the inputs based on "ON" and "OFF" button
function renderAllSlab() {
	if ($("#allocation_strategy_home__custom_field_enabled").hasClass("active")) {
		$("#allocation_strategy_home [id^=allocation_strategy_home__alloc_value_]").each( function(){
			$(this).closest('tr').hide();
		});
		
		$("#allocation_strategy_home #allocation_strategy_home__description").closest("tr").hide()
		$("#allocation_strategy_home #allocation_strategy_home__all_slabs").closest("tr").hide();
	}else{
		if ($("#allocation_strategy_home__slab_based").hasClass("active")) {
			$("#allocation_strategy_home [id^=allocation_strategy_home__alloc_value_]").each( function(){
				$(this).closest('tr').hide();
			});
			
			$("#allocation_strategy_home #allocation_strategy_home__description").closest("tr").show()
			$("#allocation_strategy_home #allocation_strategy_home__all_slabs").closest("tr").show();		
		}else{
			$("#allocation_strategy_home [id^=allocation_strategy_home__alloc_value_]").each( function(){
				$(this).closest('tr').show();
			});
			$("#allocation_strategy_home #allocation_strategy_home__description").closest("tr").show()
			$("#allocation_strategy_home #allocation_strategy_home__all_slabs").closest("tr").hide()
		}
	}
}

function showHideExpiryValues(){
	
	if( $('#expiry_strategy_home__slab_based').hasClass('active') ){
		if($('#expiry_strategy_home__expiry_time_units').val() =="FIXED_DATE"){
			$('#expiry_strategy_home__expiry_time_values').hide();
			$('#expiry_strategy_home__expiry_time_values_date').show();
		}else{
			if($('#expiry_strategy_home__expiry_time_units').val() =="NEVER"){
				 $('#expiry_strategy_home__expiry_time_values').val('0');
				 $('#expiry_strategy_home__expiry_time_values').attr("disabled",true) ;
				 $('#expiry_strategy_home__expiry_time_values').removeClass("reg_positive");
			}
			else{
				$('#expiry_strategy_home__expiry_time_values').attr("disabled",false)  ;
				$('#expiry_strategy_home__expiry_time_values').addClass("reg_positive");
			}
			$('#expiry_strategy_home__expiry_time_values').show();
			$('#expiry_strategy_home__expiry_time_values_date').hide();
		}
	}else{
		$('select[id^=expiry_strategy_home__expiry_time_units]').each(function(){
			var c= $(this).attr('id').split('_').slice(-1);
			if($(this).val() =="FIXED_DATE"){
				$('#expiry_strategy_home__expiry_time_values_'+c).hide();
				
				$('#expiry_strategy_home__expiry_time_values_date_'+c).show();
			}else{
				if($(this).val() =="NEVER"){
					 $('#expiry_strategy_home__expiry_time_values_'+c).val('0');
					 $('#expiry_strategy_home__expiry_time_values_'+c).attr("disabled",true) ;
					 $('#expiry_strategy_home__expiry_time_values_'+c).removeClass("reg_positive");
				}
				else {
					$('#expiry_strategy_home__expiry_time_values_'+c).attr("disabled",false)  ;
					$('#expiry_strategy_home__expiry_time_values_'+c).addClass("reg_positive");
				}
				$('#expiry_strategy_home__expiry_time_values_'+c).show();
				$('#expiry_strategy_home__expiry_time_values_date_'+c).hide();
			}
		});
	}
}

function editEmailTemplate( template_id ){
	
	var ajaxUrl = '/xaja/AjaxService/program/edit_email_form.json?template_id='+ template_id
					+ '&org_id='+$('#org_id').val();
	
	$('.template_preview').contents().find('html').html("<img src='/images/newUI/loader-big.gif'"+
		" class='center' id='template_load' style='margin-left:40%;margin-top:25%'>");
	var selected_ruleset_id = getSelectedRulesetLoyaltyProgram();
	
	$.post(ajaxUrl, { email_scope : $('#email_sms_scope').val(),
					  action_type : $('#selected_action').val(),
					  selected_ruleset_id: selected_ruleset_id},
			function(data){
				  if(!isOrgValid(data))
						return;
				$('#email-edit').html( decodeURIComponent( data.html ) );
				$('#email-edit').show();
				$('#email-preview').hide();
				$('.wait_message').removeClass('frame_indicator');
			}, 'json');
}
function hideDowngradeOnLoad(){
	if( $('#tier_downgrade_strategy_home__is_active').attr("checked" )){
		$('#tier_downgrade_div').show();
		$('input#tier_downgrade_strategy_home__is_active').attr("checked","checked");
	}
	else{
		$('#tier_downgrade_div').hide();
		$('input#tier_downgrade_strategy_home__is_active').removeAttr("checked");
	}
	
	$('input[id^=tier_downgrade_strategy_home__time_period_]').each(function(){
		$(this).parents('tr').next().find('.time_period_helptext').html('in last ' +$(this).val()+  ' months &nbsp;&nbsp;&nbsp;');
	});
	
	$('input[id^=tier_downgrade_strategy_home__condition_always_]').each(function(){
		if( ($(this).val() == "true" && $(this).attr('checked') == "checked" ) || 
				($(this).val() == "false" && $(this).attr('checked') != "checked" )){
				
				$(this).parents('td').next().hide();
				$(this).parents('td').next().next().hide();
				$(this).parents('td').next().next().next().hide();
				$(this).parents('td').next().next().next().next().hide();
			}else{
				$(this).parents('td').next().show();
				$(this).parents('td').next().next().show();
				$(this).parents('td').next().next().next().show();
				$(this).parents('td').next().next().next().next().show();
			}
	});
	
	$('input[id^=tier_downgrade_strategy_home__should_downgrade_]').each(function(){
		if($(this).attr('checked') == "checked"){
			$(this).parents('tr').next().show();
			$(this).parents('tr').next().next().show();
			$(this).parents('tr').next().next().next().show();
		}else{
			$(this).parents('tr').next().hide();
			$(this).parents('tr').next().next().hide();
			$(this).parents('tr').next().next().next().hide();
		}
	});
	
	$('.check_purchase').each(function(){
		if($(this).attr('checked') == "checked"){
			$(this).parents('tr').find('input[id^="tier_downgrade_strategy_home__purchase_"]').removeAttr('disabled');
		}else{
			$(this).parents('tr').find('input[id^="tier_downgrade_strategy_home__purchase_"]').attr('disabled','disabled');
			$(this).parents('tr').find('input[id^="tier_downgrade_strategy_home__purchase_"]').val(0);
		}
	});
	
	$('.check_num_visits').each(function(){
		if($(this).attr('checked') == "checked"){
			$(this).parents('tr').find('input[id^="tier_downgrade_strategy_home__num_visits_"]').removeAttr('disabled');			
		}else{
			$(this).parents('tr').find('input[id^="tier_downgrade_strategy_home__num_visits_"]').attr('disabled','disabled');
			$(this).parents('tr').find('input[id^="tier_downgrade_strategy_home__num_visits_"]').val(0);
		}
	});
	
}
function hideReturnOnLoad(){
	if( $('a#return_strategy_home__is_reissual_enabled').hasClass("active") ){
		$('.hide-if-reissual-disabled').each( function(){ $(this).removeClass("hide-reissual-rows")});
		$('input#return_strategy_home__is_reissual_enabled').val(1);
	}
	else{
		//$('#return_strategy_div').hide();
		$('.hide-if-reissual-disabled').each( function(){ $(this).addClass("hide-reissual-rows")});
		$('input#return_strategy_home__is_reissual_enabled').val(0);
	}
}
function validateStrategy() {
	//removing previous errors
	$(".lp-tooltip").remove();
	var bool=true;
	var inputs=$(".reg_positive:visible");
	$(inputs).each( function(index, value){
		if($(value).val()) {
			var check1=isNormalIntegerPlusFloat($(value).val().trim());
		} else {
			var check1= $(value).val() ;
		}
		
		if (!check1) {
			var elem=$(value);
			var offset=elem.offset();
			$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>This field must be a positive integer</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			bool=false;
			return bool;
		}
	});

	if(!bool)
		return bool;
	
	var inputs=$(".required:visible");
		$(inputs).each( function(index, value){
		if($(value).val()) {
			var check1=$(value).val().trim();
		} else {
			var check1=$(value).val() ;
		}
		
		if (!check1) {
			var elem=$(value);
			var offset=elem.offset();
			$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>This field is mandatory</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			bool=false;
			return bool;
		}
	});

	if(!bool)
		return bool;

	var inputs = $("input.three-digits:visible");

	$(inputs).each(function(){
		if($(this).val().indexOf('.')!=-1){         
       if($(this).val().split(".")[1].length > 3){                   
           if(parseFloat($(this).val()) != parseFloat($(this).val()).toFixed(3)) {
           
			var offset=$(this).offset();
			$("<div class='lp-tooltip' data-item='"+$(this).attr("id")+"'><div class='arrow-down'></div>Maximum number of decimals allowed is 3</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			bool=false;
			return bool;
           }
       }  
    } 
	});

	if(!bool)
		return bool;

	var inputs=$(".int_positive:visible");
	$(inputs).each( function(index, value){
		if($(value).val()) {
			var check1=isNormalInteger($(value).val().trim());
		} else {
			var check1= $(value).val() ;
		}
		
		if (!check1) {
			var elem=$(value);
			var offset=elem.offset();
			$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>This field must be a positive integer</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			bool=false;
			return bool;
		}
	});

	if(!bool)
		return bool;

	var inputs=$(".reg_positive_csv:visible");
	$(inputs).each( function(index, csv_values){
		var input = this;
		if($(csv_values).val()) {
		values = $(csv_values).val().trim().split(",") ;
	} else {
		return false;
	}
		$(values).each( function(index, value){
			var check1=isNormalIntegerPlusFloat(value.trim());
			if (!check1) {
				var elem=$(input);
				var offset=elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>This field must be a csv of positive integer</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				bool=false;
				return false;
			}
		});
	});
	
	return bool;
	
}

function validateStrategyType(strategy_type) {
	switch(strategy_type) {

		case "slab_upgrade" :

			var elems=$("[id*=threshold_value]");
			for(var i=0; i< elems.length;i++){
			
			if(i== elems.length-1)
				break;
			
			if (parseInt($(elems[i]).val())>parseInt($(elems[i+1]).val())) {
				var elem=$(elems[i+1]);
				var offset=elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>This upgrade limit is greater than that of the next tier or lesser than that of the previous tier</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				return false;
			}
		}

			return true;

		case "point_allocation" :
			 var cont = true;
                if($('#allocation_strategy_home__alloc_type').val()=='PRORATED'){
                $('input[id^=allocation_strategy_home][type=text]').each( function(){
                                if($(this).hasClass('reg_positive')) {
                                        if(!$(this).is(':hidden')){
	                                        if($(this).val()>100){
	                                        var ret = confirm("You are about to allocate more points than the bill amount\n Click OK to continue ");
	                                        if(ret==false)
	                                        		cont = false;
	                                                return false;
	                                        }
                                        }
                                	}
                                });
                }

	    return cont;

	    case "point_expiry":

		    var cont = true;
				$('select[id^=expiry_strategy_home__expiry_time_units]').each (function() { 
				if(!$(this).is(':hidden')){
					
					if($(this).val()=="NUM_DAYS") {
		
						$(this).prevAll('input[id^=expiry_strategy_home__expiry_time_values]').each(function(){
						 if(!$(this).is(':hidden') )  {
						 	if($(this).val()>365) {
								var ret = confirm("You have set points to expire after 12 months\nDo you want to use 'Months(End)'?");
									if(ret==true) {
										cont = false;
										return false;
									}
						 		}
						 	}
						});
					}
				
	
					if($(this).val()=="NUM_MONTHS_END") {
						$(this).prevAll('input[id^=expiry_strategy_home__expiry_time_values]').each(function(){
							if(!$(this).is(':hidden')  ) {
								if($(this).val()>60) {
									var ret = confirm("You have set points to expire after 5  years\nDo you want to use 'never expire'?");
									if(ret==true) {
									cont = false;
									return false;
									}
									
								}
							}
						});
					}
				}
			});
	
			return cont;

		default:
			return true;
	}
}

function renderTimeperiod() {
	if ($("#tier_downgrade_strategy_home__condition").val()=="FIXED") {	
		$("#tier_downgrade_strategy_home__start_date").show();
		$('.down_helptext').html('months from date');
	}else{
		$("#tier_downgrade_strategy_home__start_date").hide();
		$('.down_helptext').html('months from last tier change');
	}
}

function isOrgValid(data){
	if(data.org_change){
		setFlashMessage(data.error,"error");
		location.href=$('#prefix').val()+'/org/points/program/Program?flash=You have changed the organization in another tab';
		return false;
	}
	
	return true;
}

function getSelectedRulesetLoyaltyProgram(){
	var is_ruleset_selected = $("#set_button").hasClass('sel');
	var selected_ruleset_id = null;
	if($("#set_button").length != 0){
		var selected_ruleset = $(".cursor_pointer.sel");
		is_ruleset_selected = is_ruleset_selected && (selected_ruleset.attr("data-type") === "ruleset_info");
		selected_ruleset_id = selected_ruleset.attr("data-id");
	}
	return is_ruleset_selected ? selected_ruleset_id : null;
}