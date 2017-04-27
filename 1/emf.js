
$(document).ready( function() {
	heightFix();
	hidePromotions()// hide promotions for un supported events
	$('.widget_title').hide();
	addExpressionEditor();
	renameForwarededRulesetsInAction();
	replaceSetName();
	$("[id$='send_email']").die("click").live("click",function(){
		removeErrors();
	});
	$("input").die("keyup").live("keyup",function(){
		removeErrors();
	});

	$('.inline_block').die('hover').live('hover', function(){
		$(this).find('i').addClass('hover');
	});
	
	$('.inline_block').die('mouseout').live('mouseout', function(){
		$(this).find('i').removeClass('hover');
	});
	
	$('.cancel, .close').die('click').live('click', function(){
		var close = confirm("Are you sure you want to close ? ");
		if( close ){
			$('#promotions_form').modal('hide');
			$('#promotions_form').html('');
			$('#configure_sms_email').hide();
			$('#configure_sms_email').modal('hide');
			$('#configure_sms_email').removeClass('preview_modal');
			$('#configure_sms_email').html('');
			$('#strategy_form').modal('hide');
			$('#strategy_form').html('');
			$('#scopes_form').modal('hide');
			$('#scopes_form').html('');
			$(".formError").remove();
			$(".lp-tooltip").remove();
		}
	});
	function storescsvTrim(str) {
	   var newString = [];
	   var nsplit = str.replace(/\s/g,"").split(",");
	   for (i=0; i<nsplit.length ; i++) {
	        if (nsplit[i] != '' && nsplit[i] != 'null') { 
	         	newString.push(nsplit[i]); 
	         }     
	        }
	      return newString;
	  }
	
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

	
	$('div[data-function="save-event-rulesets"]').die('click').live('click', function(){
		
		var event_type = $('#selected_event_type').html();
		var endpoint = $('#endpoint').val();
		
		validateExpression( event_type, endpoint );
	});
	
	// On change of event refresh the event sets (  all 3 panes ) and scopes
	$('li.event').die('click').live('click', function(){
		var event_type = $(this).find('a').html();
		var event_type_id = $(this).val();
		var prefix = $('#prefix').val();
		var ajaxUrl = prefix + '/xaja/AjaxService/emf/clear_all_rulesets.json?org_id='+$('#org_id').val();
		var endpoint = $('#endpoint').val();
		var data_type = $(this).attr('data-type');
		var that = $(this);
		
		$('.wait_message').show().addClass('indicator_1');
		$.post(ajaxUrl, { endpoint : endpoint } ,  
						  function(data) {
								if(!isOrgValid(data))
									return;
							   if ( data.success ){
								   refreshEventSets( event_type, endpoint, data_type, that, -1, -1, -1, true );						   
							   }else{
								   setFlashMessage( data.error, "error" );
								   $('.wait_message').hide();
							   }
						  },
				'json');
	});
	
	// Onclick of ruleset get all rules
	$('li[data-function="get_rules_list"]').die('click').live( 'click', function(){
		var ruleset_id = $(this).attr('data-id');
		var prefix = $('#prefix').val();
		var endpoint = $('#endpoint').val();
		var event_type_id = $('#selected_event_type').attr('value');
		var event_type = $('#selected_event_type').html();
		var unroller = $(this).attr('data-unroller-supported');
		$("#rulesets_promotions_list_html li.sel").removeClass("sel");
		$(".set_holder .sel").removeClass("sel");
		$(this).addClass("sel");
		var ajaxUrl = prefix + '/xaja/AjaxService/emf/get_rules.json?org_id='+$('#org_id').val();
		$('.wait_message').show().addClass('indicator_1');
		
		
		$.post(ajaxUrl, { ruleset_id : ruleset_id ,endpoint : endpoint, event_type : encodeURIComponent( event_type ) }, 
					function(data) {

						if(!isOrgValid(data))
							return;

					   if ( data.success ){
						//console.log(decodeURIComponent( data.rules_html ) );
						   $('#rules_list_html').html( decodeURIComponent( data.rules_html ) );
						   $('#rule_html').html( decodeURIComponent( data.rule_html ) );
						   $('#add_scope_container').html(decodeURIComponent( data.scopes ) );
						   renderButton(); 
						   addExpressionEditor( unroller );
						   $('#ruleset_id').val( ruleset_id );
						   //emf.linkWidget.init();
						   replaceSetName();
						   renameForwarededRulesetsInAction();
						    //Condition render expression
					   }else{
						 setFlashMessage( data.error, "error" );
					   }
					   $('.wait_message').hide();
					}, 
				'json');
	});

	// Onclick of rule get all configs of rule -  condition + cases + actions
	$('li[data-function="get_rule"]').die('click').live( 'click', function(){
		var ruleset_id = $('#ruleset_id').val();
		var rule_id = $(this).attr('data-id');
		var prefix = $('#prefix').val();
		var endpoint = $('#endpoint').val();
		var event_type = $('#selected_event_type').html();
		var unroller = $('[data-function="get_rules_list"][data-id=' + ruleset_id + ']').attr('data-unroller-supported');
		$(".rule_list_content .sel").removeClass("sel");
		$(this).addClass("sel");
		var ajaxUrl = prefix + '/xaja/AjaxService/emf/get_rule.json?org_id='+$('#org_id').val();
		$('.wait_message').show().addClass('indicator_1');
		$.post(ajaxUrl, { ruleset_id : ruleset_id , rule_id : rule_id,
						  endpoint : endpoint, event_type : encodeURIComponent(event_type)
						}, 
				function(data) {

					if(!isOrgValid(data))
						return;

				   if ( data.success ){
					   $('#rule_html').html( decodeURIComponent(data.rule_html ));
					   addExpressionEditor( unroller );
					   renderButton();
					   $('#rule_id').val( rule_id );
					   renameForwarededRulesetsInAction();
					   //emf.linkWidget.init();
					  
				   }else{
					 setFlashMessage( data.error, "error" );
				   }
				   $('.wait_message').hide();
				}, 
			'json');
	});
	
	$('div[data-function="create-ruleset"]').die('click').live('click', function(){
		if (!$(this).hasClass("disable")) {
			var ruleset_id = $('#ruleset_id').val();
			var endpoint = $('#endpoint').val();
			var event_type = $('#selected_event_type').html();
			
			var prefix = $('#prefix').val();
			var ajaxUrl = prefix + '/xaja/AjaxService/emf/create_ruleset.json?org_id='+$('#org_id').val();
			$('.wait_message').show().addClass('indicator_1');
			$.post(ajaxUrl, { event_type : encodeURIComponent(event_type),
							  endpoint : endpoint
							}, 
					function(data) {
						if(!isOrgValid(data))
							return;
					   if ( data.success ){
						   $('li.event').each( function() {
							   if( $(this).find('a').html() == event_type )
								   refreshEventSets( event_type, endpoint, $(this).attr('data-type'), $(this), -1, -1, -1 );
						   });
						   	// to refresh 3 panes
					   }else{
						 setFlashMessage( data.error, "error" );
						 $('.wait_message').hide();
					   }
					   
					}, 
				'json');	
		}
	});
	
	$('div[data-function="create-rule"]').die('click').live('click', function(){
		if (!$(this).hasClass("disable")) {
			var ruleset_id = $('#ruleset_id').val();
			var endpoint = $('#endpoint').val();
			var event_type = $('#selected_event_type').html();
			var unroller = $('[data-function="get_rules_list"][data-id=' + ruleset_id + ']').attr('data-unroller-supported');
			
			var prefix = $('#prefix').val();
			var ajaxUrl = prefix + '/xaja/AjaxService/emf/create_rule.json?org_id='+$('#org_id').val();
			$('.wait_message').show().addClass('indicator_1');
			$.post(ajaxUrl, { event_type : encodeURIComponent(event_type),
							  ruleset_id : ruleset_id,
							  endpoint : endpoint
							}, 
					function(data) {
						if(!isOrgValid(data))
							return;
					   if ( data.success ){
						   $('#rule_html').html( decodeURIComponent(data.rule_html ));
						   $('#rules_list_html').html( decodeURIComponent( data.rules_list_html ) );
						   renderButton();
						   addExpressionEditor( unroller );
						   replaceSetName();
						   renameForwarededRulesetsInAction();
						   
					   }else{
						 setFlashMessage( data.error, "error" );
					   }
					   $('.wait_message').hide();
					}, 
				'json');	
		}
	});
	
	$('[data-function="get-forwarded-ruleset"],.link-icon').die('click').live('click', function(e){
		e.stopPropagation();
		$('li[data-function="get_rules_list"][data-name="' + $(this).attr('data-name') + '"]').trigger('click'); 
	});
	
	var prev;
	$( ".expredit" ).focus(function() {
		prev=getReturnTypeOfExpression();
	});
	// update exression return type when expression is written
	$('.expredit').die('blur').live('blur', function(){
		
		if( $('#expression').val()==$("#expression_old").val() ){
			return;	
		}
		
		if (!$("#configure_sms_email:visible")[0]) { // Quick fix for executing the function only when the div(id=configure_sms_email)  is not opened
	
			//alert(action_list);
			var exj = $('#expression_json').val();
			var exjo =JSON.parse( exj );
			if( exjo['type'] )
				var type = exjo['type'].split(':');
			
			if ( ( !$(".expredit ").find("span.has_errors")[0] ) && 
				( type[0] == "integer" || type[0] =="boolean" || 
				  type[0] == "date" || type[0] == "real" ||
				  type[0] == "number" || type[0] == "string" )) { // validate for erroneous input in expression editor
				var return_type = getReturnTypeOfExpression();
				var prev=$('#expression_return_type').val();
				$('.balloon').hide();
				var confirm = "true";
				if( return_type !== $('#expression_return_type').val() ){
					if ($('#case_count').val() >0) {
						confirm = window.confirm('Retain actions ?');
					}
					
				}
				$('#expression_return_type').val( return_type );
				
				var ruleset_id = $('#ruleset_id').val();
				var unroller = $('[data-function="get_rules_list"][data-id=' + ruleset_id + ']').attr('data-unroller-supported');
				var rule_id = $('#rule_id').val();
				var prefix = $('#prefix').val();
				var ajaxUrl = prefix + '/xaja/AjaxService/emf/update_expression.json?org_id='+$('#org_id').val();
				var action_list = $('#action_list').val();
				var expression = $('#expression').val();
				var expression_json = $('#expression_json').val();
				var endpoint = $('#endpoint').val();
				var event_type = $('#selected_event_type').html();
				$(".exp_error").hide();
				//$('.wait_message').show().addClass('indicator_1');
				$.post(ajaxUrl, { 	expression : encodeURIComponent( expression ),
									expression_json : encodeURIComponent( expression_json ),
									ruleset_id : ruleset_id,
									rule_id : rule_id,
									data_type : return_type,
									action_list : encodeURIComponent( action_list ),
									retain_cases :  confirm,
									endpoint : endpoint,
									event_type : encodeURIComponent(event_type)
								}, 
				function(data) {
					if(!isOrgValid(data))
						return;
				   if ( data.html){
		 
					   $('#rule_html').html( decodeURIComponent(data.html ));
					   addExpressionEditor( unroller );
					   renderButton();
					   $('#rule_id').val( rule_id );
					   $(".rule_list_content .cursor_pointer.sel .expression-rule").text($("#expression").val());
					   if( $("#expression").val() == "true" )
						   $(".rule_list_content .cursor_pointer.sel .expression-rule").text("No Expression");
					   $(".rule_list_content .cursor_pointer.sel").attr("title",$("#expression").val());
					   //emf.linkWidget.init();
				   }else{
					   //
				   }
				   $('.wait_message').show().removeClass('indicator_1');   
				}, 'json');		
			}else{
				$("#error_msg").text("It contains erroneous input");
				$(".exp_error").show();
			}
			
		}
		
	});
	
	// add Case form 
	$('#when').die('click').live('click', function(){
		if( ( $('#expression_return_type').val() == "boolean") && ( $('#case_count').val() == 2 ) )
			;//console.log('add case disabled'); // TODO for this case disable #when
		else{
			var case_value_is_empty = false;
			$('[id$="__expression_value"]').each( function(){
				if( $(this).val().trim().length == "" ){
					var offset = $(this).offset();
					$("<div class='lp-tooltip' data-item='"+$(this).attr("id")+"'><div class='arrow-down'></div>This field cant be empty</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
					case_value_is_empty = true;
				}
			});
			
			if( case_value_is_empty )
				return;
			
			var ruleset_id = $('#ruleset_id').val();
			var rule_id = $('#rule_id').val();
			var return_type = $('#expression_return_type').val(); 
			var case_count = $('#case_count').val();
			var endpoint = $('#endpoint').val();
			var event_type = $('#selected_event_type').html();
			
			if( return_type ){
				
				var prefix = $('#prefix').val();
				var ajaxUrl = prefix + '/xaja/AjaxService/emf/get_case.json?org_id='+$('#org_id').val();
				
				$('.wait_message').show().addClass('indicator_1');
				$.post(ajaxUrl, { 
								  ruleset_id : ruleset_id,
								  rule_id : rule_id,
								  data_type : return_type,
								  case_count : parseInt(case_count) + 1 ,
								  endpoint : endpoint,
								  event_type : encodeURIComponent(event_type)
								}, 
					function(data) {
						if(!isOrgValid(data))
							return;
					   if ( data.html ){
						   $('#case_count').val( parseInt(case_count) + 1 );
						   $('.case_container').append( '<div class = "case_instance">' + 
											decodeURIComponent( data.html ) + 
											'</div>' );
						   
						   /*if( $('#expression_return_type').val() == "boolean" )
							   $('[id$=__expression_value]').each( function() {
								   $(this).trigger('change');
							   });*/
						   		
					   }else{
						 setFlashMessage( data.error, "error" );
					   }
					   $('.wait_message').hide();
					}, 
				'json');
			}
			
		}
			
	});
	
	//minimize maximize case content
	
	$('[id^=case_home_] .icon-minus').live('click', function(){
		$(this).parents('[id^=case_home_]').find('.case_content').hide('slow');
		$(this).parent().find('.icon-minus').attr('class','icon-plus pull-right cursor_pointer');
		$(this).parents('[id^=case_home_]').find('.case_content').attr("style","");	
	});
	
	$('[id^=case_home_] .icon-plus').live('click', function(){
		$(this).parents('[id^=case_home_]').find('.case_content').show('slow');
		$(this).parent().find('.icon-plus').attr('class','icon-minus pull-right cursor_pointer');
		$(this).parents('[id^=case_home_]').find('.case_content').attr("style","");
	});
	
	//show list of actions
	$('.show_action').die('click').live('click', function() {
		if ($(this).hasClass("active")) {
			//code
			$(this).removeClass("active")
			$(this).parents('.case_instance').find('.action_list').hide();
			$(this).find(".add-more").css("background-position", "0 -28px");
		}else{
			$(this).parents('.case_instance').find('.action_list').find('a').each( function(){
				$(this).show();
			});
			
			$(this).closest('.case_instance').find('.action_container').find('.action_instance').each( function(){
				if( $(this).attr('data-action') != "FORWARD" )
					$(this).parents('.case_instance').find('.action_list').find('a[value=' + $(this).attr('data-action')+ ']').hide();
			});
			
			$(this).parents('.case_instance').find('.action_list').show();
			$(this).find(".add-more").css("background-position", "0 -169px");
			$(this).addClass("active");	
		}
		
	});
	
	$('div#add_scope').die('click').live('click', function(){
		callback=function(){
			//console.log($("#scopes_form .scope_row:visible"));
			$("#scopes_form .scope_row:visible").first().find(".scope_description .scope-symbol").click();
		   }
		openScopePopup(callback);
		
		
	});
	
	$('div[data-function="scopes_save"]').die('click').live('click', function () {
		
		removeErrors();
		var ruleset_id = $('#ruleset_id').val();
		var rule_id = $('#rule_id').val();
		var event_type = $('#selected_event_type').html();
		var endpoint = $('#endpoint').val();
		
		var promotion_id = -1;
		 if( $('#prom_button').hasClass('sel') )
			 promotion_id = $('.proms_container.sel').attr('data-id');
		 
		var bool=validateScope(this);
		
		if (bool==false) {
			return false;
		}
		var forms=$("#scopes_container").find("li:visible").find("form");
		var serializedData= "";
		var count = forms.length;
		$.each(forms, function(i,val) {
			var scope_type = $(val).attr('data-scope-type');
			var scope_id = $(val).attr('data-id');
			serializedData = $(val).serialize() ;
			
			var prefix = $('#prefix').val();
			var ajaxUrl = prefix + '/xaja/AjaxService/program/save_scopes.json?form=' + $(val).attr('id') + "&scope_type=" + scope_type +"&scope_id="+ scope_id + "&org_id="+$('#org_id').val();
			$('.wait_message').show().addClass('indicator_1');

			if(scope_type == "Stores"){
				var store_data = serializedData;
				var store_url = ajaxUrl;
				var reader = new FileReader();
				if(document.getElementById('scope_form_Stores__csv_file').files[0]) {
					var filename=document.getElementById('scope_form_Stores__csv_file').files[0]['name'];
					var exp_file = filename.split(".");
				    var length = exp_file.length;
				    var ext = exp_file[length - 1];
					if( ext == 'csv') {
						reader.readAsDataURL(document.getElementById('scope_form_Stores__csv_file').files[0]);
					} else 	{
						alert("Only csv files accepted");
						$('.wait_message').removeClass('indicator_1');
						return false;
					}
				}else{
					scopeSaveAjax(serializedData,ajaxUrl,"");
				}
					reader.onloadend = function () {
				    dataToBeSent = reader.result.split("base64,")[1];
				    validateStores(store_data,store_url,dataToBeSent);
				  }
				
			}else{
				scopeSaveAjax(serializedData,ajaxUrl,"");
			}
		
			function scopeSaveAjax(serializedData,ajaxUrl,filedata)
			{
				filedata = encodeURIComponent(filedata);
				serializedData = serializedData + "&file=" + filedata;
				$.ajax({
				  type: "POST",
				  url: ajaxUrl,
				  data: serializedData,
				  dataType : "json",
				  success :
					function(data){
						if(!isOrgValid(data))
							  return;
					  	if( data.success ){
								count--;
						if( count == 0 ){
							$('.wait_message').removeClass('indicator_1');
							$('#scopes_form').modal("hide");
							$('#scopes_form').html('');
							$('li.event').each( function() {
							   if( $(this).find('a').html() == event_type ){
								   refreshEventSets( event_type, endpoint, $(this).attr('data-type'), $(this),ruleset_id, rule_id, promotion_id );
							   }
							});
						}
						$(val).find('input[id$=scope_id]').val(data.scope_id);
						$(val).attr('data-id',data.scope_id);
					}else{
						$("#error-notifier").show().html(data.error);
						setTimeout( function(){$("#error-notifier").hide().html("")} ,10000 )
						$('.wait_message').removeClass('indicator_1');
					}
				 },
				 async: "false" ,
			});
		}

function validateStores(serializedData,ajaxUrl,file) {			
	var validate_url = prefix + '/xaja/AjaxService/program/validate_stores.json?form='+ $(val).attr('id') + "&scope_type=" + scope_type +"&scope_id="+ scope_id + "&org_id="+$('#org_id').val();
    $.post(validate_url,{ file : encodeURIComponent(file) },
    	 function(data){
 		 	if(data.invalid_count > 0 && data.invalid_stores != null && data.invalid_stores != ""){
   			 var invalid = storescsvTrim(String(data.invalid_stores));
   			 var choice = confirm('Following stores are/is invalid : ' + invalid);
	   			 if(choice){ 
	             		scopeSaveAjax(serializedData,ajaxUrl,file);
	          	 }else{
	         			$('.wait_message').removeClass('indicator_1');
				 		return false;
	          	  }
  		  	}else if(data.invalid_stores == null || data.invalid_stores == "" && data.valid_stores == "" ){
              var choice_store = confirm('No stores found in the uploaded file, Do you want to continue with selected stores?'); 
              if(choice_store){
               	 scopeSaveAjax(serializedData,ajaxUrl,"");
               } else{
                 $('.wait_message').removeClass('indicator_1');
                 return false;
               }
            } else{
               scopeSaveAjax(serializedData,ajaxUrl,file);
            }
         },'json');
}
 });
});
		
	// get promotion form for create/edit
	$('div[data-function="get-promotion-form"]').die('click').live('click', function(){
		
		var id = $(this).attr('data-id');
		var prefix = $('#prefix').val();
		var event_type = $('#selected_event_type').html();
		var ajaxUrl = prefix + '/xaja/AjaxService/program/promotions_form.json?org_id='+$('#org_id').val();
		$('.wait_message').show().addClass('indicator_1');
		$.post(ajaxUrl, { id : id , event_type : encodeURIComponent(event_type) }, 
		function(data) 
		{
			if(!isOrgValid(data))
				return;
			if (data.html != null){
			   $('#promotions_form').html( decodeURIComponent( data.html ));
			   $('#promotions_form').removeData("modal").modal({backdrop: 'static', keyboard: false});
			   
			}else{
				$("#error-notifier").html(data.error);
				setTimeout(function(){$("#error-notifier").hide().html("")},2000)
			}
			
			$('.wait_message').removeClass('indicator_1');
			
		}, 'json');
		
	});
	
	// get action form based on action type
	$('.action_list li a').die('click').live('click', function(){
		var expressionVal=$(this).closest(".case_instance").find('[id$="expression_value"]').val().trim();
		//console.log(expressionVal);
		if (expressionVal.length!=0) {
			
			var action_type = $(this).attr('value');
			var prefix = $('#prefix').val();
			var ruleset_id = $('#ruleset_id').val();
			var rule_id = $('#rule_id').val();
			var case_value = $(this).closest('form[id^=case_home_]').find('[id$="__expression_value"]').val();
			var endpoint = $('#endpoint').val();
			var event_li = $("li.event[data-type=tracker]").css("display");
			var is_tracker = (event_li === 'none') ? true : false;
			
			
			var is_promotion_selected = $("#prom_button").hasClass('sel');
			var selected_promotion = $(".proms_container.sel");
			var selected_promotion_id = selected_promotion.attr("data-id");
			
			//if( !case_value ) { alert('Enter Expresssion value')}
			var ajaxUrl = prefix + '/xaja/AjaxService/program/get_action_form.json?org_id='+$('#org_id').val();
			var action_home = $(this).parents('.case_home').find('.action_home');
			var action_list = $(this).parents('ul');
			var form_case_home= $(this).parents('.case_home').attr('id').split('_');
			var id_suffix = form_case_home[2];
			var event_type = $('#selected_event_type').html();
			var waitForm=$(this).closest(".case_home").find(".wait_message_form");

			waitForm.addClass('loader');
			$.post(ajaxUrl, { 
								action_type : action_type,
								ruleset_id : ruleset_id,
								rule_id : rule_id,
								case_value : case_value,
								action_id : -1,
								endpoint : endpoint,
								id_suffix : id_suffix,
								event_type : encodeURIComponent(event_type),
								is_promotion : is_promotion_selected,
								promotion_id : selected_promotion_id,
								is_tracker : is_tracker
							}, 
				function(data) {
					if(!isOrgValid(data))
						return;
					waitForm.removeClass('loader');
					if ( data.html ){
						action_home.hide();
						action_home.html( decodeURIComponent ( data.html ) ).show();
						var elems=$(".action_home a[data-function=strategy-edit]");
					
						$.each(elems, function(i,val) {
							var id=$(val).prev().prev().val();
							$(val).attr("data-id",id);
						});
						action_list.hide();
					}else{
						alert( data.error );
					}
					$('.wait_message').show().removeClass('indicator_1');   
				}, 
			'json');
		}else{
			var elem=$(this).closest(".case_instance").find('[id$="expression_value"]');
			var offset=elem.offset();
			$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>This field cant be empty</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
		
		}
	});
	
	// Save action
	$('div[data-function="action_save"]').die('click').live('click', function () {
		
		removeErrors();
		var bool=validateAction(this, $(this).attr('data-action_type') );
		if ( bool==false )
			return false;
	
		var that=this;
		var return_type = $('#expression_return_type').val(); 
		var ruleset_id = $('#ruleset_id').val();
		var rule_id = $('#rule_id').val();
		var case_value = $(this).closest('form[id^=case_home_]').find('[id$="__expression_value"]').val();
		var expression_return_type = $('#expression_return_type').val();
		var form_case_home = $(this).parents('form[id^=case_home_]').attr('id');
		var event_type = $('#selected_event_type').html();
		var endpoint = $('#endpoint').val();
		var promotion_id = -1;
		 if( $('#prom_button').hasClass('sel') )
			 promotion_id = $('.proms_container.sel').attr('data-id');
		
		var prefix = $('#prefix').val();
		var ajaxUrl = prefix + '/xaja/AjaxService/program/save_action.json?' +
							'action_type=' + $(this).attr('data-action_type') +
							'&action_id=' + $(this).attr('data-action_id') + 
							'&form=' + $(this).parents('form').attr('id')+
							'&ruleset_id=' + ruleset_id +
							'&rule_id=' + rule_id +
							'&case_value=' + case_value + 
							'&endpoint=' + endpoint +
							'&org_id='+$('#org_id').val();
							//console.log(ajaxUrl);

		var post_data = $( '#' + $(this).parents('form').attr('id') ).serialize();
		//console.log(post_data);
		var waitForm=$(this).closest(".case_home").find(".wait_message_form");
		waitForm.addClass('loader');
		$.post( ajaxUrl, post_data, function(data) {
			if(!isOrgValid(data))
				return;
			waitForm.removeClass('loader');
		   if ( data.success ){
			   
			   $('li.event').each( function() {
				   if( $(this).find('a').html() == event_type )
					   refreshEventSets( event_type, endpoint, $(this).attr('data-type'), $(this), ruleset_id,
							   rule_id, promotion_id );
			   });
			   
		   }else{
			   $('#error_' + strategy_type ).html( data.error ).css('text-align','center').show();
			   setTimeout(function(){ $('#error' + strategy_type ).fadeOut('fast'); }, 5000);
			   alert( data.error );
			   $('.wait_message').show().removeClass('indicator_1');   
		   }
		   
		}
		, 'json');
	});
	
	
	
	$('div[data-function="action_cancel"]').die('click').live('click', function () {
		var close = confirm("Are you sure you want to close ? ");
		if( close ){
			removeErrors();
			var actionHome=$(this).closest(".action_home");
			if(!actionHome[0]){
				var li=$(this).closest(".action_instance");
				li.css("padding","0px");
				li.html(li.attr("data-html"));
			}else{
				$(this).closest(".action_home").hide().html("");
			}
		}
	});
	
	$('div[data-function="action-remove"]').die('click').live('click', function ( event ) {
		var close = confirm("Are you sure you want to remove ? ");
		if( close ){
			var dataAction=$(this).closest(".action_instance").attr("data-action");
			if($('#expression_return_type').val() == "boolean"){
				var expressionVal="boolean";
			}else{
				var expressionVal=$(this).closest(".case_instance").find("[id$=expression_value]").val();
				//console.log($(this).closest(".case_instance").find("[id$=expression_value]"));
			}
			
			if (expressionVal.length!=0) {
				var action_id = $(this).closest(".action_instance").attr("data-action-id");
				var prefix = $('#prefix').val();
				var ruleset_id = $('#ruleset_id').val();
				var rule_id = $('#rule_id').val();
				var case_value = $(this).closest('form[id^=case_home_]').find('[id$="__expression_value"]').val();
				var endpoint = $('#endpoint').val();
				var ajaxUrl = prefix + '/xaja/AjaxService/program/remove_action.json?org_id='+$('#org_id').val();
				var action_instance = $(this).closest(".action_instance");
				var event_type = $('#selected_event_type').html();
				
				var promotion_id = -1;
				 if( $('#prom_button').hasClass('sel') )
					 promotion_id = $('.proms_container.sel').attr('data-id');
				 
				var waitForm=$(this).closest(".case_home").find(".wait_message_form");
				waitForm.addClass('loader');
				
				$.post(ajaxUrl, { 
									ruleset_id : ruleset_id,
									rule_id : rule_id,
									case_value : case_value,
									action_id : action_id,
									endpoint : endpoint,
									event_type : encodeURIComponent(event_type)
								}, 
					function(data) {
						if(!isOrgValid(data))
							return;
						waitForm.removeClass('loader');
						if ( data.success ){
							action_instance.remove();
							if (dataAction=="FORWARD") {
								 $('li.event').each( function() {
									if( $(this).find('a').html() == event_type )
										refreshEventSets( event_type, endpoint, $(this).attr('data-type'), $(this), ruleset_id, rule_id, promotion_id );	
								 });
							}
						}else{
							setFlashMessage( data.error, "error" );
							$('.wait_message').removeClass('indicator_1');
						}
					
						   
				}, 'json');
			}else{
				var elem=$(this).closest(".case_instance").find("#expression_value");
				var offset=elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>This field cant be empty</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			}
		}
	});
		
	
	$('a[data-function="sms_create"]').die('click').live('click', function () {
		$(this).addClass("clickedLink");//remove it on save and store the sms content
		removeErrors();
		var event_type = $('#selected_event_type').html();
		var type = event_type.toUpperCase() + "_SMS"; // TODO
		var action_type = $(this).closest('form').find('[data-function="action_save"]').attr('data-action_type');
		//console.log( type );
		var formId=$(this).closest("form").attr("id");
		if ($(this).text()=="Configured") {
			getSMSSettingPageContent($("#"+formId+"__sms_content").val(), type, action_type, $("#"+formId+"__sms_sender_id").val() );
		}else{
			getSMSSettingPageContent("", type, action_type, $("#"+formId+"__sms_sender_id").val());
		}
	});
	
	$('a[data-function="email_create"]').die('click').live('click', function () {
		$(this).addClass("clickedLink");//remove it on save and store the sms content
		removeErrors();
		var event_type = $('#selected_event_type').html();
		$('#email_sms_scope').val( event_type.toUpperCase() );
		$('#selected_action').val(  $(this).closest('form').find('[data-function="action_save"]').attr('data-action_type') );
		var selected_ruleset_id = getSelectedRuleset();
		console.log("Selected ruleset id " + selected_ruleset_id);
		var formId=$(this).closest("form").attr("id");
		if ($(this).text()=="Configured") {
			//console.log('email configured');
			var email_subject=$("#"+formId+"__email_subject").val();
			var email_body=$("#"+formId+"__email_body").val();
			var ajaxUrl = '/xaja/AjaxService/program/edit_email_form.json?org_id='+$('#org_id').val(); 
			$.post(ajaxUrl, { 
						email_subject : encodeURIComponent( email_subject ),
						email_body : email_body,
						email_scope : $('#email_sms_scope').val(),
						action_type : $('#selected_action').val(),
						selected_ruleset_id : selected_ruleset_id
					},
			function(data){
				if(!isOrgValid(data))
					return;
				$('#configure_sms_email').html('<div class="container-title">' +
						 '<div>Email Setting' + 
						 '<button type="button" class="email_cancel close" >×</button>' +
						 '</div>' + 
						 '</div>' +
						 '<div id = "email-preview" name = "email-preview" class = "hide" />' +  
						 '<div id = "email-edit" name = "email-edit">' + decodeURIComponent( data.html )+ '</div>' 
						 );
				
				$('#configure_sms_email').removeData("modal").modal({backdrop: 'static', keyboard: false});
				$('.wait_message').removeClass('indicator_1');
			}, 'json');
		}else{
			getEmailSettingPageContent(formId);	
		}
	});
	
	 $('a[data-function="wechat_create"]').die('click').live('click', function (){
		 		//remove it on save and store the sms content
            $(this).addClass("clickedLink");
            removeErrors();
            var event_type = $('#selected_event_type').html();
            $('#email_sms_scope').val( event_type.toUpperCase() );
            var type = event_type.toUpperCase() + "_WECHAT";
    		var action_type = $('#selected_action').val(  $(this).closest('form').find('[data-function="action_save"]').attr('data-action_type') ).val();
            var wechat_id ;
            var wechat_acc_id;
            if ($(this).text()=="Configured") {
	                var formId=$(this).closest("form").attr("id");
	                wechat_id = $("#"+formId+"__wechat_id").val();
	                 wechat_acc_id = $("#"+formId+"__wechat_acc_id").val();
            } 
            getWeChatSettingPageContent(wechat_id,wechat_acc_id);
        });

	$('a[data-function="strategy-edit"]').die('click').live('click', function(){
		
		var that=this;
		var prefix = $('#prefix').val();
		$('#selected_action').val($(this).closest('form').find('[data-function="action_save"]').attr('data-action_type'));
		var ajaxUrl = prefix + '/xaja/AjaxService/program/strategy_form.json?org_id='+$('#org_id').val();
		
		var is_promotion_selected = $("#prom_button").hasClass('sel');
		var selected_promotion_id = 'undefined';
		if($("#prom_button").length != 0){
			var selected_promotion = $(".proms_container.sel");
			selected_promotion_id = selected_promotion.attr("data-id");
		}
		$('.wait_message').show().addClass('indicator_1');
		$.post(ajaxUrl, { 
							program_id : 1,
							strategy : $(this).attr('data-strategy'),
							id : $(this).attr('data-id'),
							action_type: $('#selected_action').val(),
							event_type : $('#selected_event_type').html(),
							is_promotion : is_promotion_selected,
							promotion_id : selected_promotion_id
						}, function(data) {
			if(!isOrgValid(data))
				return;
		   if (data.html != null){ 
			   $('#strategy_form').html( decodeURIComponent( data.html ) );

   			   if ($(that).attr("data-strategy")=="point_allocation") 
   				   renderAllSlab();
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
	
	$('.action_home select[id$=strategy], .action_instance select[id$=strategy]').die('click').live('click',function(){
		var id=$(this).val();
		$(this).next().next().attr("data-id",id);
	})
	
	 
	$('.cursor_custom').die('click').live('click',function(){
		var expressionVal=$(this).closest(".case_instance").find('[id$="expression_value"]').val().trim();
		if (expressionVal.length!=0) {
			var that=this;
			var action_type = $(this).closest("li").attr("data-action");
			var action_id = $(this).closest("li").attr("data-action-id");
			var prefix = $('#prefix').val();
			var ruleset_id = $('#ruleset_id').val();
			var rule_id = $('#rule_id').val();
			var case_value = $(this).closest('form[id^=case_home_]').find('[id$="expression_value"]').val();
			var endpoint = $('#endpoint').val();
			var event_li = $("li.event[data-type=tracker]").css("display");
			var is_tracker = (event_li === 'none') ? true : false;
			//if( !case_value ) { alert('Enter Expresssion value')}
			var is_promotion_selected = $("#prom_button").hasClass('sel');
			var selected_promotion = $(".proms_container.sel");
			var selected_promotion_id = selected_promotion.attr("data-id");
			
			var ajaxUrl = prefix + '/xaja/AjaxService/program/get_action_form.json?org_id='+$('#org_id').val();
			var action_home = $(this).parents('.case_home').find('.action_home');
			var action_list = $(this).parents('ul');
			var form_case_home= $(this).parents('.case_home').attr('id').split('_');
			var id_suffix = form_case_home[2];
			var event_type = $('#selected_event_type').html();
			var waitForm=$(this).closest(".case_home").find(".wait_message_form");
			waitForm.addClass('loader');
			
			$.post(ajaxUrl, { 
								action_type : action_type,
								ruleset_id : ruleset_id,
								rule_id : rule_id,
								case_value : case_value,
								action_id : action_id,
								endpoint : endpoint,
								id_suffix : id_suffix,
								event_type : encodeURIComponent(event_type),
								is_promotion : is_promotion_selected,
								promotion_id : selected_promotion_id,
								is_tracker : is_tracker
							}, 
				function(data) {
					if(!isOrgValid(data))
						return;
					waitForm.removeClass('loader');
					if ( data.html ){
						$(that).closest(".action_instance").attr("data-html",$(that).closest(".action_instance").html());
						$(that).closest(".action_instance").html(decodeURIComponent ( data.html ) ).css("padding", "13px");
						var elems=$(".action_instance a[data-function=strategy-edit]");
						$.each(elems, function(i,val) {
							var id=$(val).prev().prev().val();
							$(val).attr("data-id",id);
						});
						//console.log(decodeURIComponent ( data.html ).find("form"));
						var formId=$(decodeURIComponent ( data.html )).find("form").attr("id");
						var sendEmail=$("#"+formId+" a[id="+formId+"__send_email]");
						if (sendEmail[0]) {
							if (sendEmail.hasClass("active")) {
								sendEmail.next().next().removeClass("hide");
								sendEmail.next().next().text("Configured");
							}	
						}
						var sendSms=$("#"+formId+" a[id="+formId+"__send_sms]");
						if (sendSms[0]) {
							if (sendSms.hasClass("active")) {
								sendSms.next().next().removeClass("hide");
								sendSms.next().next().text("Configured");
							}	
						}
						
						var sendWeChat=$("#"+formId+" a[id="+formId+"__send_social]");
						if (sendWeChat[0]) {
							if (sendWeChat.hasClass("active")) {
								sendWeChat.next().next().removeClass("hide");
								sendWeChat.next().next().text("Configured");
							}	
						}
						
						var sendEBill=$("#"+formId+" a[id="+formId+"__send_ebill]");
						if (sendEBill[0]) {
							if (sendEBill.hasClass("active")) {
								sendEBill.next().next().removeClass("hide");
								sendEBill.next().next().text("Configured");
							}	
						}
					}else{
						alert( data.error );
					}
					$('.wait_message').show().removeClass('indicator_1');   
				}, 
			'json');
		}else{
			var elem=$(this).closest(".case_instance").find('[id$="expression_value"]');
			
			var offset=elem.offset();
			$("<div class='lp-tooltip'><div class='arrow-down'></div>This field cant be empty</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
		}
	});
	
	$('div[data-function=scope-remove]').die('click').live('click',function(e){
		
		var result = confirm("Are you sure you want to delete ?");
		if( !result )
			return;
		
		var ruleset_id = $('#ruleset_id').val();
		var scope_id = $(this).attr('data-scope-id');
		var endpoint = $('#endpoint').val();
		var event_type = $('#selected_event_type').html();
		var that = $(this);
		var scope_type = $(this).closest('li').attr('data-scope-type');
		
		var prefix = $('#prefix').val();
		var ajaxUrl = prefix + '/xaja/AjaxService/program/remove_scope.json?org_id='+$('#org_id').val();
		$('.wait_message').show().addClass('indicator_1');
		$.post(ajaxUrl, { scope_id : scope_id,
						ruleset_id : ruleset_id,
						endpoint : endpoint,
						event_type : encodeURIComponent(event_type) },
					function(data)	{
						if(!isOrgValid(data))
							return;
						if (data.success){
							if( that.closest(".scope_row").find(".scope-symbol").text() == '-' )
								that.closest(".scope_row").find(".scope-symbol").click();
							
							var id= that.closest(".scope_row").addClass(" hide ").attr("data-scope-type");
							$("[id='"+id+"']").show();
							$('#scope_list').find('.scopes').each( function(){
								if( $(this).html() == scope_type ){
									$(this).remove();
								}
							})	
						}else{
							 $("#error-notifier").html(data.error);
							 setTimeout(function(){$("#error-notifier").hide().html("")},2000)
						}
						
						$('.wait_message').removeClass('indicator_1');
						
					}, 'json');
		
	});
	
	$('#scopes_buttons').die('click').live('click',function(e){
		if (e.target.id!="scopes_buttons") {
			if (e.target.id!="scope_save") {
				$(".scopes_container [data-scope-type='"+e.target.id+"']").removeClass("hide").find(".scope-symbol").click();
				$("[id='"+e.target.id+"']").hide();
			}
		}
	})
	$('div[data-scope-id=remove]').die('click').live('click',function(e){
		var id=$(this).closest(".scope_row").addClass(" hide ").attr("data-scope-type");
		$("[id='"+id+"']").show();
	})
	$('#promotion_button').die('click').live('click',function(e){
		
		$("#promotions_form").removeData("modal").modal({backdrop: 'static', keyboard: false});
	})
	
	$('div[data-function="save_promotion"]').die('click').live('click', function(){
		var ruleset_id = $('#ruleset_id').val();
		var rule_id = $('#rule_id').val();
		var event_type = $('#selected_event_type').html();
		var endpoint = $('#endpoint').val();
		removeErrors();
		var bool=validatePromotion(this);
		if (!bool) {
			return false;
		}
		var prefix = $('#prefix').val();
		var ajaxUrl = prefix + '/xaja/AjaxService/program/save_promotion.json?event_type=' +  event_type
								+ '&org_id='+$('#org_id').val();
		$('.wait_message').show().addClass('indicator_1');
		var post_data = $( 'form#promotion_form' ).serialize();
		//console.log( post_data );
		$.post(ajaxUrl, post_data, 
		function(data) 
		{
			//console.log(data);
			if(!isOrgValid(data))
				return;
			if (data.success != null){
				//console.log(data);
				$('#promotions_form').modal('hide');
				
				$('li.event').each( function() {
					   if( $(this).find('a').html() == event_type ){
							var callback=function(){
								$("#prom_button").click();
								$('.proms_header').last().click();
							}
							refreshEventSets( event_type, endpoint, $(this).attr('data-type'), $(this) ,
											ruleset_id, rule_id, -1, false, callback);
							
					   }
				});
				
			}else{
				$("#error-notifier").show().html(data.error);
				setTimeout(function(){$("#error-notifier").hide().html("")},2000)
				$('.wait_message').removeClass('indicator_1');
			}
			
			
			
		}, 'json');
		
	});
	
	
	$(document).click(function(e){
		var elem=$(this).closest(".action_list");
		var anotherElem=$(this).closest(".show_action");
		if (elem.length==0 && !$(e.target).hasClass("add-more")) {
			$(".show_action.active").find(".action_list").hide();
			$(".show_action.active").find(".add-more").css("background-position", "0 -28px");
			$(".show_action.active").removeClass("active");	
		}
	})
	$('.scope-symbol').die('click').live('click',function(e){
		var pOrM=$(this).text();
		if (pOrM=="-") {
			//show form
			$(".scope_active").removeClass("scope_active")
			$(this).text("+");
			$(this).next().next().hide();
			$(this).next().show();
		}else{
			//hide form
			$(".scope_active").text("+");
			$(".scope_active").next().next().hide();
			$(".scope_active").next().show();
			$(".scope_active").removeClass("scope_active")
			$(this).addClass("scope_active");
			$(this).text("-");
			$(this).next().next().show();
			$(this).next().hide();
		}
		e.stopPropagation();
	})
	$('[id$="expression_value"]').die('keyup').live('keyup',function(e){
		$(".lp-tooltip").remove();
	})
	$('.twin_container').die('click').live('click',function(e){
		if (e.target.id=="set_button") {
			$("#promotion").css("left","-100%");
			$("#sets").css("left","0%");
			$("#prom_button").removeClass("sel");
			$("#set_button").addClass("sel");
		}else{
			$("#sets").css("left","-100%");
			$("#promotion").css("left","0%");
			$("#set_button").removeClass("sel");
			$("#prom_button").addClass("sel");	
		}
	})
	
	
	$('[id$=__expression_value]').die('change').live('change',function(){
		
		if(  $('#expression_return_type').val() == "boolean" ){ 
			if( $(this).attr('id') == "case_home_1__expression_value" ){
				if ($(this).val()=="true") {
					$('#case_home_2__expression_value').val("false");
					$('#case_home_2__expression_value').closest('form.case_home').attr('data-case-value', "false" );
				}
				else{
					$('#case_home_2__expression_value').val("true");
					$('#case_home_2__expression_value').closest('form.case_home').attr('data-case-value', "true" );
				}
				
				updateCaseValue( 1, $('#case_home_1__expression_value').val(), $('#case_home_1__expression_value'),"boolean");
				
			}else if( $(this).attr('id') == "case_home_2__expression_value" ){
				if ($(this).val()=="true") {
					$('#case_home_1__expression_value').val("false");
					$('#case_home_1__expression_value').closest('form.case_home').attr('data-case-value', "false" );
				}
				else{
					$('#case_home_1__expression_value').val("true");
					$('#case_home_1__expression_value').closest('form.case_home').attr('data-case-value', "true" );
				}
				
				updateCaseValue( 2, $('#case_home_2__expression_value').val(),$('#case_home_2__expression_value'),"boolean");
			}
			
		}else{
			
			var case_count = $(this).attr('id').split('_')[2];
			var case_value = $(this).val();
			var id =  $(this).attr('id');
			var that = $(this);
					
			if ( case_value.length != 0  ){
				updateCaseValue( case_count, $(this).val(), $(this), "string");
			}else{
			
				var offset=$(this).offset();
				$("<div class='lp-tooltip' data-item='"+$(this).attr("id")+"'><div class='arrow-down'></div>This field cant be empty</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			
			}
		}
		
	});
	
	$(document).on("click","[id$='__send_sms']",function(){	
		if($(this).hasClass("active")){
			$(this).next().next().show();
		}else{
			$(this).next().next().hide();
			$(this).next().next().text("Configure Sms");
			$(this).next().next().next().val("");
			$(this).next().next().next().next().val("");
		}
	})
	$(document).on("click","[id$='__send_social']",function(){	
		if($(this).hasClass("active")){
			$(this).next().next().show();
		}else{
			$(this).next().next().hide();
			$(this).next().next().text("Configure WeChat Message");
			$(this).next().next().next().val("");
			$(this).next().next().next().next().val("");
		}
	})
	$(document).on("click","[id$='__send_email']",function(){	
		if($(this).hasClass("active")){
			$(this).next().next().show();
		}else{
			$(this).next().next().hide();
			$(this).next().next().text("Configure Mail");
			var formId=$(this).closest("form").attr("id");
			$("#"+formId+"__email_subject").val("");
			$("#"+formId+"__email_body").val("");
		}
	})
	$(document).on("click", "[id$='__send_ebill']", function() {
		if ($(this).hasClass("active")) {
			$(this).next().next().show();
		} else {
			$(this).next().next().hide();
			$(this).next().next().text("Configure Mail");
			var formId = $(this).closest("form").attr("id");
			$("#" + formId + "__email_subject").val("");
			$("#" + formId + "__email_body").val("");
		}
	})
	$('.proms_header').die('click').live('click',function(){
		$(".proms_container.sel").removeClass("sel");
		$(this).closest(".proms_container").addClass("sel");
		var li=$(this).next().next().find("ul").find("li");
		if (li[0]) {
			li.first().click();
		}else{
			noRulesetContent();
		}
		//$(this).next().show();
	});
	$("#prom_button").die("click").live("click",function(){
		if ($(".promotions_list_content").find(".proms_header").first()[0]) {
			var li=$(".proms_container").first().find("ul li");
			//console.log(li);
			if (!li[0]) {
				noRulesetContent();	
			}else{
				$(".promotions_list_content").find(".proms_header").first()[0].click();
			}
		}else{
			noRulesetContent();
		}
		
	});
	$("#set_button").die("click").live("click",function(){
		var li=$(".ruleset_list_content").find("ul").find("li");
		//console.log(li);
		if (li[0]) {
			li.first().click();
		}else{
			noRulesetContent();
		}
		
	});
	
	$(".rule_list_content .delete-icon").die('click').live( 'click', function( e ){
	
		var linkIcon=$(this).closest("li").find(".link-icon");
		e.stopPropagation();
		 
		if (linkIcon[0])
			var close = confirm("Are you sure you want to delete ? It will delete the forwarded rulesets also");
		else
			var close = confirm("Are you sure you want to delete ?");
			
		if( close ){
			
			var prefix = $('#prefix').val();
			var ruleset_id = $('#ruleset_id').val();
			var rule_id = $(this).closest('[data-function="get_rule"]').attr('data-id');
			var endpoint = $('#endpoint').val();
			var ajaxUrl = prefix + '/xaja/AjaxService/emf/remove_rule.json?org_id='+$('#org_id').val();
			var event_type = $('#selected_event_type').html();
			
			var promotion_id = -1;
			 if( $('#prom_button').hasClass('sel') )
				 promotion_id = $('.proms_container.sel').attr('data-id');
			 
			 $('.wait_message').show().addClass('indicator_1');
			
			$.post(ajaxUrl, { 
								ruleset_id : ruleset_id,
								rule_id : rule_id,
								endpoint : endpoint,
								event_type : encodeURIComponent(event_type)
							}, 
				function(data) {
					if(!isOrgValid(data))
						return;
					if ( data.success ){
						 $('li.event').each( function() {
							 
							if( $(this).find('a').html() == event_type )
								refreshEventSets( event_type, endpoint, $(this).attr('data-type'), $(this), ruleset_id, -1, promotion_id );	
						 });
						 
					}else{
						setFlashMessage( data.error, "error" );
						$('.wait_message').removeClass('indicator_1');
					}
					   
			}, 'json');
			
		}
	});
	
	$('.case_home .delete-icon').die('click').live( 'click', function( e ){
		
		e.stopPropagation();
		var close = confirm("Are you sure you want to delete ?");
		
		if( close ){
			
			var data_case_value = $(this).closest('form.case_home').attr('data-case-value');
			
			if ( data_case_value.length != 0 ) {
				
				var prefix = $('#prefix').val();
				var ruleset_id = $('#ruleset_id').val();
				var rule_id = $('#rule_id').val();
				var endpoint = $('#endpoint').val();
				var ajaxUrl = prefix + '/xaja/AjaxService/emf/remove_case.json?org_id='+$('#org_id').val();
				var event_type = $('#selected_event_type').html();
				
				var promotion_id = -1;
				 if( $('#prom_button').hasClass('sel') )
					 promotion_id = $('.proms_container.sel').attr('data-id');
				 
				var waitForm=$(this).closest(".case_home").find(".wait_message_form");
				waitForm.addClass('loader');
				
				$.post(ajaxUrl, { 
									ruleset_id : ruleset_id,
									rule_id : rule_id,
									case_value : data_case_value,
									endpoint : endpoint,
									event_type : encodeURIComponent(event_type)
								}, 
					function(data) {
						if(!isOrgValid(data))
							return;
						waitForm.removeClass('loader');
						if ( data.success ){
							
							 $('li.event').each( function() {
								if( $(this).find('a').html() == event_type )
									refreshEventSets( event_type, endpoint, $(this).attr('data-type'), $(this), ruleset_id, rule_id, promotion_id );	
							 });
							 
						}else{
							setFlashMessage( data.error, "error" );
							$('.wait_message').removeClass('indicator_1');
						}
						   
				}, 'json');
				
			}else{
				$(this).closest(".case_instance").remove();
			}
		}
		
	});
	
	$(".scopes").die("click").live("click",function(){
		
		var callback=function(){
			$(".scope_row[data-scope-type='"+$(this).text()+"']").find(".scope-symbol").click();
		}.bind(this);
		openScopePopup(callback);
	});
	
	renderButton();
});

function setHeightForPanes( height ){
	if( !height ){
		window_height = $(window).height();
		header_height = $('header').height();
		footer_height = $('footer').height()
		height = window_height - header_height - footer_height - 100;
	}
	
	$('.tab_pane').css({'height' :+ height + 'px'});
}


function addExpressionEditor( unroller ){
	try{
		var type_source = window.location.protocol + $('#type_source').val();
		if( $("#expression").next().hasClass('expredit') )
			 $("#expression").next().remove();
		
		var refined_grammar = $.extend( true, {}, window.grammar );
		//console.log( refined_grammar );
		//console.log( window.grammar )
		if( unroller != "true" ){
			delete refined_grammar['identifiers']['currentLineItem'];
			delete refined_grammar['identifiers']['trackerLineItem'];
		}
		
		
		$("#expression").expredit( fix( refined_grammar ), { "expectedTypes" : ["boolean"], 
			"typeSource" : type_source, "delims" : ["{{","}}" ]}).change(
					function() {
						$("#expression_json").val( $("#expression").data("json"));
					});
		$("#expression").next().show();	
	
		 $('div.display_rule_expression').html(
				   	$('li[data-function="get_rule"].sel').find('div.condition-head').html() );
		 $("#expression_old").val($("#expression").val());
		 
	}catch( err ){
		
	}
}

function getReturnTypeOfExpression(){

	var exp_json = $.parseJSON( $('#expression_json').val() );
	
	var return_type = "";
	if( exp_json.type && exp_json.type !== undefined ){
		if ( exp_json.type == "boolean:primitive" )
			return_type = "boolean";
		else
			return_type = "string";
	}
	return return_type;
}

function getSelectedRuleset(){
	var is_ruleset_selected = $("#set_button").hasClass('sel');
	var selected_ruleset_id = null;
	if($("#set_button").length != 0){
		var selected_ruleset = $(".cursor_pointer.sel");
		is_ruleset_selected = is_ruleset_selected && (selected_ruleset.attr("data-type") === "ruleset_info");
		selected_ruleset_id = selected_ruleset.attr("data-id");
	}
	return is_ruleset_selected ? selected_ruleset_id : null;
}

function getSMSSettingPageContent( sms_content, type, action_type, sms_sender_id ){
	
	var prefix = $('#prefix').val();
	var ajaxUrl = prefix + '/xaja/AjaxService/program/get_sms_setting_form.json?org_id='+$('#org_id').val();
	$('.wait_message').show().addClass('indicator_1');
	var selected_ruleset_id = getSelectedRuleset();
	console.log("Selected ruleset id " + selected_ruleset_id);
	$.post(ajaxUrl, { sms_content : sms_content,
		              sms_sender_id : sms_sender_id,
					  type : type ,
					  action_type : action_type,
					  selected_ruleset_id : selected_ruleset_id
					}, function(data){
						if(!isOrgValid(data))
							return;
						if( data.html ){
							$('#configure_sms_email').html( decodeURIComponent( data.html ));
							$('#configure_sms_email').removeData("modal").modal({backdrop: 'static', keyboard: false});
							$('#configure_sms_email').removeClass("wechat_configure");
							$('.editor').trigger('keyup');
						}else{
							alert( data.error );
						}
							$('.wait_message').removeClass('indicator_1');
		
	}, 'json');
}
function getEmailSettingPageContent(formId){
	var prefix = $('#prefix').val();
	var template_type = $("#"+formId+"__template_type").val();
	var ajaxUrl = prefix + '/xaja/AjaxService/program/get_email_setting_form.json?org_id='+$('#org_id').val();
	$('.wait_message').show().addClass('indicator_1');
	$.post(ajaxUrl,  { template_type : template_type }, function(data){
		if(!isOrgValid(data))
			return;
		$('#configure_sms_email').html( decodeURIComponent( data.html ) );
		$('#configure_sms_email').removeData("modal").modal({backdrop: 'static', keyboard: false});
		$('#configure_sms_email').removeClass("wechat_configure");
		$('.wait_message').removeClass('indicator_1');
	}, 'json');
}
function fetchWechatDetail(wechat_id)
{
    if(wechat_id)
     fetchWechatDetailsPreview(wechat_id);
   
    $(document).on('change','#wechat_templates__wechat_temp_org',function(){
      wechat_templateval=$("#wechat_templates__wechat_temp_org").find(":selected").val();

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

function getWeChatSettingPageContent( wechat_id ,wechat_acc_id){
        var prefix = $('#prefix').val();
        var ajaxUrl = prefix + '/xaja/AjaxService/program/get_wechat_setting_form.json?org_id='+$('#org_id').val();
        $('.wait_message').show().addClass('indicator_1');
        $.post(ajaxUrl, { wechat_id : wechat_id ,wechat_acc_id : wechat_acc_id},
        		function(data){
                        if(!isOrgValid(data))
                                return;
                        if( data.html ){
                                $('#configure_sms_email').html( decodeURIComponent( data.html ));
                                $('#configure_sms_email').removeData("modal").modal({backdrop: 'static', keyboard: false});
                                $('#configure_sms_email').addClass("wechat_configure");
                        }  else{
                                alert( data.error );
                        }
		                $('.wait_message').removeClass('indicator_1');
		                fetchWechatDetail(wechat_id);
                   }, 'json');      
		}

function getTableDivFromJSON(temp_cont) {
	var div = '<div>';
	div += getTableFromJSON(temp_cont) ;
	div += '</div>';
	return div;
}

function getTableFromJSON(temp_cont){

var table = '<table>' ;
for(var key in temp_cont){
if(typeof temp_cont[key] == "object"){
	table += '<tr>' ;
	table += '<td >'+key+': </td>';
	table += '<td>' ;
	table += getTableFromJSON(temp_cont[key]);
}else{
	table += '<tr style = "padding : 2px">' ;
	table += '<td style = "padding : 2px ">'+key+': </td>';
	table += '<td style = "padding : 2px ">' ;
	table += temp_cont[key];
}
table += '</td></tr>';
}
table += '</table>' ;
return table;
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
function renderButton() {
	
	if( ( $('#expression_return_type').val() == "boolean") && ( $('#case_count').val() == 2 ) ){
		$("#when").addClass("disable");
	}else{
			$("#when").removeClass("disable");
	}
	
	if (!$("#sets li")[0] && $("#set_button").hasClass("sel")) {
		$("#add_condition").addClass("disable");
	}
	if (!$("#promotion li")[0] && $("#prom_button").hasClass("sel")) {
		$("#add_condition").addClass("disable");
	}
	if (!$(".rule_list_content li")[0]) {
		
		$("#when").addClass("disable");
	}
}
function validateAction( that, action_name ) {
	
	var bool = true;
		
	var sendSms=$(that).closest("form").find("[id$='__send_sms']");
	if (sendSms[0]) {
		if (sendSms.hasClass("active")) {
			if (sendSms.next().next().text()=="Configure Sms") {
				var elem=sendSms;
				var offset=elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>You haven't configured SMS yet</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				bool=false;	
			}
			if ($("[id$='sms_delay']")[0]) {
				if ( ($("[id$='sms_delay']").val().length==0 )  || ( !isNormalInteger( $("[id$='sms_delay']").val().trim() ) ) ){
					if (bool) {
						var elem=$("[id$='sms_delay']");
						var offset=elem.offset();
						$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>Sms delay has to be mentioned</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
						bool=false;	
					}
				}
								
			}	
		}
	}
	
	var sendEmail=$(that).closest("form").find("[id$='__send_email']");
	if (sendEmail[0]) {
		if (sendEmail.hasClass("active")) {
			if (sendEmail.next().next().text()=="Configure Mail") {
				var elem=sendEmail;
				var offset=elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>You haven't configured Email yet</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				bool=false;	
			}
			if ($("[id$='email_delay']")[0]) {
				if ( ($("[id$='email_delay']").val().length==0 )  || ( !isNormalInteger( $("[id$='email_delay']").val().trim() ) ) ){
					if (bool) {
						var elem=$("[id$='email_delay']");
						var offset=elem.offset();
						$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>Email delay has to be mentioned</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
						bool=false;	
					}
				}
								
			}
				
		}	
	}
	
	var sendEBill=$(that).closest("form").find("[id$='__send_ebill']");
	if (sendEBill[0]) {
		if (sendEBill.hasClass("active")) {
			if (sendEBill.next().next().text()=="Configure Mail") {
				var elem=sendEBill;
				var offset=elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>You haven't configured Email yet</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				bool=false;	
			}
			if ($("[id$='pdf_attachment_name']")[0]) {
				if ( ($("[id$='pdf_attachment_name']").val().length==0 ) ){
					if (bool) {
						var elem=$("[id$='pdf_attachment_name']");
						var offset=elem.offset();
						$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>* PDF Attachment Name has to be mentioned</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
						bool=false;	
					}
				}
				if (! validateFileName( $("[id$='pdf_attachment_name']").val().trim() )){
					if (bool) {
						var elem=$("[id$='pdf_attachment_name']");
						var offset=elem.offset();
						$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>* PDF Attachment Name can only have 3-100 characters, number, under score & space</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
						bool=false;	
					}
				}
								
			}
			if ($("[id$='pdf_template']")[0]) {
				if ( ($("[id$='pdf_template']").val()==0 ) ){
					if (bool) {
						var elem=$("[id$='pdf_template']");
						var offset=elem.offset();
						$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>PDF Template has to be Selected</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
						bool=false;	
					}
				}
								
			}
				
		}
	}
	
	bool = validateSocialMessages(that) && bool;
	
	if( !bool )
		return bool;
		
	var sendSocial=$(that).closest("form").find("[id$='__send_social']");
	
	if( ( action_name == "PE_SMS_ACTION" ) ||
			(action_name == "PE_EMAIL_ACTION") || 
				(action_name == "PE_WECHAT_MESSAGE_ACTION") || (action_name == "EBILL_ACTION") ){
		

		var inputs=$(that).closest("form").find("input:visible[id$='sms_delay']");
		$.each(inputs, function(i,val) {
			var value=$(val).val().trim();
			if ( ( value.length == 0 ) || ( !isNormalInteger( value ) )) {
				bool=false;
				var elem=$(val);
				var offset=elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>This field must be a positive integer</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			}
		});
		
		if (sendEmail && sendEmail.length && !sendEmail.hasClass("active")){		
				var elem=sendEmail;
				var offset=elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>Email must be active</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				bool=false;
		}
		if (sendSms && sendSms.length && !sendSms.hasClass("active")){
				var elem=sendSms;
				var offset=elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>Sms must be active</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				bool=false;
		}	
		if (sendSocial && sendSocial.length && !sendSocial.hasClass("active")){
			var elem=sendSocial;
			var offset=elem.offset();
			$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>WeChat must be active</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			bool=false;
		}
		if (sendEBill && sendEBill.length && !sendEBill.hasClass("active")){		
			var elem=sendEBill;
			var offset=elem.offset();
			$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>Email must be active</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			bool=false;
		}
		
		return bool;
	}else{
		
		var inputs=$(that).closest("form").find("input:visible").not("[id$='sms_delay']");;
		$.each(inputs, function(i,val) {
			var value = $(val).val() ? $(val).val().trim() : '';
			if ( ( value.length == 0 ) || ( !isNormalInteger( value ) )) {
				bool=false;
				var elem=$(val);
				var offset=elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>This field must be a positive integer</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			}
		});
		
		var inputs=$(that).closest("form").find("select:visible").not("[id$='sms_delay']");;
		$.each(inputs, function(i,val) {
			if ( !$(val).val() ){
				bool=false;
				var elem=$(val);
				var offset=elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>This field is mandatory</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			}
		});
		
		return bool;
	}
}
//Need to change this function if any more communications are added like wechat

function validateSocialMessages(that) {

	var sendSocial = $(that).closest("form").find("[id$='__send_social']");
	if (sendSocial[0]) {
		if (sendSocial.hasClass("active")) {
			if (sendSocial.next().next().text() == "Configure WeChat Message") {
				var elem = sendSocial;
				var offset = elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>You haven't configured WeChatMessage yet</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				return false;
			}
		}
	}
	return true;
}

function validateScope(that){
	var count=0;
	var values=$(that).closest("#scopes_form").find(".scope_row:visible").not("[data-scope-type='Line Item']").not("[data-scope-type='Stores']").find(".ui-multiselect").prev();
	var StoreScope = $('#scopes_form').find('.scope_row:visible[data-scope-type="Stores"]');
	var isemptyStore = ! StoreScope.length ;
	//console.log(values);
	var bool=true;
	$.each(values, function(i,val) {
		if ($(val).val()==null) {
			var elem=$(val).next();
			var scopeSymbol=$(val).closest(".scope_description").find(".scope-symbol");
			if (!scopeSymbol.hasClass("scope_active")) {
				$(val).closest(".scope_description").find(".scope-symbol").click();
			}
			
			var offset=elem.offset();
			$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>Atleast one option should be selected</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
			bool=false;
			return false;
		}
	});
	if (bool) {
		if ($(".scope_row[data-scope-type='Line Item']:visible")[0]) {
			var values=$(that).closest("#scopes_form").find("[data-scope-type='Line Item']").find(".ui-multiselect").prev();
			//console.log(values);
			$.each(values, function(i,val) {
				if ($(val).val()==null) {
					count++;
				}
			});
			if (count<values.length) {
				//code
			}else{
				var elem=$(values[0]).next();
				var scopeSymbol=$(values[0]).closest(".scope_description").find(".scope-symbol");
				if (!scopeSymbol.hasClass("scope_active")) {
					$(values[0]).closest(".scope_description").find(".scope-symbol").click();
				}
				
				var offset=elem.offset();
				$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>Atleast one option should be selected</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				bool=false;
			}	
		}
			
	}

	if(!isemptyStore && bool)
	{
		var Selectvalues=$(that).closest("#scopes_form").find("[data-scope-type='Stores']").find(".ui-multiselect").value;
		var type = typeof(document.getElementById('scope_form_Stores__csv_file').files[0]);
		if(type != "undefined")
		{
			bool = true;			
		}
		else if( type == 'undefined' &&  Selectvalues != "" )
		{
			var values = $(that).closest("#scopes_form").find("[data-scope-type='Stores']").find(".ui-multiselect").prev();
			var svalues = $(that).closest("#scopes_form").find("[data-scope-type='Stores']").find(".csv-file-point");
			if(svalues.val()=="" && values.val() == null)
			{
				var scopeSymbol=svalues.closest(".scope_description").find(".scope-symbol");
				if (!scopeSymbol.hasClass("scope_active")) {
					svalues.closest(".scope_description").find(".scope-symbol").click();
				}
				var offset=svalues.offset();
				offset.left=4*(offset.left)/3.5;

				$("<div class='lp-tooltip' data-item='"+svalues.attr("id")+"'><div class='arrow-down'></div>Atleast one option should be selected!!</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
				bool=false;
				return false;
			}
		}
		
	}
	
	return bool;
}
function validatePromotion(that){
	var elem=$("#promotion_form__name");
	if($("#promotion_form__name").val().trim().length==0){
		var offset=elem.offset();
		$("<div class='lp-tooltip' data-item='"+elem.attr("id")+"'><div class='arrow-down'></div>This field is required</div>").appendTo("body").css("top",offset.top).css("left",offset.left).css("margin-top","-"+($(".lp-tooltip").height()+20)+"px");
		return false;
	}else{
		return true;
	}
}

function heightFix(args) {
	/*height fix*/
	$("#rulesets_promotions_list_html").css("height",$("#rules_list_html").height());
	$("#rulesets_promotions_list_html .button_container").css("top",$("#rules_list_html").height()-$("#rules_set_button").height()-1);
	/*height fix*/
}
function hidePromotions() {
	
	var eventName=$('#selected_event_type').text();
	if (eventName=="NewTransaction" || eventName=="PointsRedemption" || 
			eventName=="CustomerRegistration" || eventName=="CouponRedemption" 		|| 
			eventName=="CustomerUpdate" || eventName == "TransactionAdd") {
	     // do nothing
	     $("#set_button").css("width","50%");
	     $("#prom_button").show();
	}else{
		
	     //$(".ruleset_list_home").css("top","33px");
	     $("#set_button").css("width","100%");
	     $("#prom_button").hide();
	     $("div[data-function='get-promotion-form']").hide()
	}	
}
 function removeErrors(){
	$(".lp-tooltip").remove();
 }
 
 function validateExpression( event_type ){
	 	
	var prefix = $('#prefix').val();
	var event_type = $('#selected_event_type').html();
	var endpoint = $('#endpoint').val();
	var ajaxUrl = prefix + '/xaja/AjaxService/emf/get_all_expressions.json?org_id='+$('#org_id').val();
	$('.wait_message').show().addClass('indicator_1');
	
	var valid = true;
	var invalid = new Array();
	var validate = $.post(ajaxUrl, { event_type: encodeURIComponent(event_type), endpoint : endpoint },
	function(data){
		if(!isOrgValid(data))
			return;
		if ( data.success ){
		   var all_expressions = $.parseJSON ( data.data );
		   //console.log( all_expressions );
		   window.all_expressions_valid = true;
		   for ( var ruleset_id in  all_expressions ){
			   for ( var rule_id in  all_expressions[ ruleset_id ]){
				   var expression = all_expressions[ ruleset_id ][rule_id ]
				   
				   // Try 
				   var type_source = window.location.protocol + $('#type_source').val();
				   $("#offline_expression").val( expression );
					$("#offline_expression").expredit( fix( window.grammar ), {
						"expectedTypes" : [],
						"typeSource" : type_source,
						"delims" : ["{{","}}"],
						"offline" : true
					}).change(function() {
						$("#offline_json").val( $("#offline_expression").data("json"));} );
					
					$("#offline_expression").trigger("change");
					
					var json= $("#offline_expression").data("json");
					if( $("#offline_expression").nextAll(".expredit").find('span').hasClass("has_errors" ) ){
						$('#offline_expression').nextAll('.expredit').remove();
						//console.log( expression );
						invalid['ruleset_id'] = ruleset_id;
						invalid['rule_id'] = rule_id;
						valid = false;
						window.all_expressions_valid = false;
						$('.wait_message').removeClass('indicator_1');
						break;
					}
			   }
			   if( !valid )
					break;
		   }
		   $('#offline_expression').nextAll('.expredit').remove()
		   
		}else{
			setFlashMessage( data.error, "error"); 
		}
		
		$('.wait_message').removeClass('indicator_1');
		
	}, 'json');
	
	$.when( validate ).done( function() {	
		if( !valid ){
			//console.log( invalid ) // TODO select invalid ruleset id and rule id and error msg
			getRuleContent(invalid);
		}else{
			var ajaxUrl = prefix + '/xaja/AjaxService/emf/save_event_rulesets.json?org_id='+$('#org_id').val();
			$('.wait_message').show().addClass('indicator_1');
			$.post(ajaxUrl, { event_type : encodeURIComponent(event_type),
							endpoint : endpoint,
							ruleset_id : -1 ,
							rule_id : -1 },
			  function(data) {
					if(!isOrgValid(data))
						return;
				   if ( data.success ){
					   setFlashMessage( data.success );
					   $('li.event').each( function() {
						   if( $(this).find('a').html() == event_type )
							   refreshEventSets( event_type, endpoint, $(this).attr('data-type'), $(this), -1, -1, -1 );
					   });
					   
				   }else{
					   setFlashMessage( data.error, "error" );
					   $('.wait_message').hide();
				   }
			  },
			'json');
		}
	});
 }
 
 function getRuleContent(invalid) {
	var invalidNew=invalid;
	var ruleset_id = invalid['ruleset_id'];
	var prefix = $('#prefix').val();
	var endpoint = $('#endpoint').val();
	var event_type_id = $('#selected_event_type').attr('value');
	var event_type = $('#selected_event_type').html();
	var ajaxUrl = prefix + '/xaja/AjaxService/emf/get_rules.json?org_id='+$('#org_id').val();
	$('.wait_message').show().addClass('indicator_1');
	var unroller = $('[data-function="get_rules_list"][data-id=' + ruleset_id + ']').attr('data-unroller-supported');
	
	$.post(ajaxUrl, { ruleset_id : ruleset_id ,endpoint : endpoint, event_type : encodeURIComponent(event_type) }, 
				function(data) {
					if(!isOrgValid(data))
						return;
				   if ( data.success ){
					   $('#rules_list_html').html( decodeURIComponent( data.rules_html ) );
					   $('#rule_html').html( decodeURIComponent( data.rule_html ) );
					   $('#add_scope_container').html(decodeURIComponent( data.scopes ) );
					   renderButton(); 
					   addExpressionEditor( unroller );
					   $('#ruleset_id').val( ruleset_id );
					   
					    //add sel for selected condition
					    $("[data-function='get_rules_list'].sel").removeClass("sel");
					    $("[data-function='get_rules_list'][data-id="+invalid['ruleset_id']+"]").addClass("sel");
					   
					   getRulesetContent(invalidNew);
				   }else{
					 setFlashMessage( data.error, "error" );
					 $('.wait_message').hide();
				   }
				   
				}, 
			'json');
 }
 function getRulesetContent(invalid){
		
		
		var ruleset_id =invalid['ruleset_id'];
		var rule_id = invalid['rule_id'];
		var prefix = $('#prefix').val();
		var endpoint = $('#endpoint').val();
		var event_type = $('#selected_event_type').html();
		var unroller = $('[data-function="get_rules_list"][data-id=' + ruleset_id + ']').attr('data-unroller-supported');
		
		$(".rule_list_content .sel").removeClass("sel");
		$(this).addClass("sel");
		var ajaxUrl = prefix + '/xaja/AjaxService/emf/get_rule.json?org_id='+$('#org_id').val();
		$('.wait_message').show().addClass('indicator_1');
		$.post(ajaxUrl, { ruleset_id : ruleset_id , rule_id : rule_id,
						  endpoint : endpoint, event_type : encodeURIComponent(event_type)
						}, 
				function(data) {
					if(!isOrgValid(data))
						return;
				   if ( data.success ){
					   $('#rule_html').html( decodeURIComponent(data.rule_html ));
					    //add sel for selected condition
					   $("[data-function='get_rule'][data-id="+invalid['rule_id']+"]").addClass("sel");
					   addExpressionEditor( unroller );
					   renderButton();
					   $('#rule_id').val( rule_id );
					  
					   
					    //show error
					    $("#error_msg").text("It contains erroneous input");
					    $(".exp_error").show();
				   }else{
					 setFlashMessage( data.error, "error" );
				   }
				   $('.wait_message').hide();
				}, 
			'json');
 }

 function updateCaseValue( case_count, case_value, ele, return_type ){
	 	var prefix = $('#prefix').val();
		var ruleset_id = $('#ruleset_id').val();
		var rule_id = $('#rule_id').val();
		var event_type = $('#selected_event_type').html();
		var endpoint = $('#endpoint').val();
		
		var ajaxUrl = prefix + '/xaja/AjaxService/emf/update_case_value.json?event_type=' +  event_type 
							+ '&org_id='+$('#org_id').val();
		//$('.wait_message').show().addClass('indicator_1');
		$.post(ajaxUrl, { ruleset_id : ruleset_id,
						  rule_id : rule_id,
						  event_type : encodeURIComponent(event_type),
						  endpoint : endpoint,
						  case_count : case_count,
						  case_value : case_value,
						  return_type : return_type
						}, 
					function(data){
						if(!isOrgValid(data))
							return;
						if (data.success ){
							ele.closest('form.case_home').attr('data-case-value', case_value );
						}else{
							setFlashMessage( data.error, "error" );
						}
						$('.wait_message').removeClass('indicator_1');
						
					}, 'json');
	
 }
 

 function refreshEventSets( event_type, endpoint, data_type, ele, ruleset_id, rule_id, promotion_id, grammar, callback ){
	 
	 //console.log( "***********************************************" );
	 //console.log( data_type + event_type + endpoint );
	 
	 if( event_type == "SlabUpgrade" )
		   grammar = true;
	   
	 var prefix = $('#prefix').val();
	 var ajaxUrl = prefix + '/xaja/AjaxService/emf/get_event_sets.json?org_id='+$('#org_id').val();
	 $('.wait_message').show().addClass('indicator_1');
		$.post(ajaxUrl, { event_type : encodeURIComponent(event_type),
						  endpoint : endpoint,
						  data_type : data_type,
						  ruleset_id : ruleset_id ,
						  rule_id : rule_id,
						  promotion_id : promotion_id,
						  grammar : grammar } ,
						  
						  function(data) {
							  	if(!isOrgValid(data))
									return;
							   if ( data.success ){
								
								   //TODO refresh scopes
								   $('#rulesets_promotions_list_html').html( decodeURIComponent( data.rulesets_promotions_list_html ) );
								   $('#rules_list_html').html( decodeURIComponent( data.rules_html ) );
								   $('#rule_html').html( decodeURIComponent( data.rule_html ) );
								   $('#add_scope_container').html( decodeURIComponent( data.scopes_list_html ) );
								   renderButton();
								   heightFix();
								   
								   if( grammar )
									   window.grammar = $.parseJSON ( data.grammar );
								   
								   var unroller = $('[data-function="get_rules_list"][data-id=' + ruleset_id + ']').attr('data-unroller-supported');
								   addExpressionEditor( unroller );
								   /*hide promotions for un supported events*/
								   
									$(ele).parent().find('li').each( function(){
										$(this).show();
									})

									$(ele).parent().find('li').each( function(){
										if(  event_type == $(this).find('a').html() ){
											$(this).hide();
											$('#selected_event_type').html( event_type );
										}
									});
									
									hidePromotions();
									
									$('.wait_message').hide();
									 /*hide promotions for un supported events*/
									if( promotion_id == -1 ){
										$("#promotion").css("left","-100%");
										$("#sets").css("left","0%");
										$("#prom_button").removeClass("sel");
										$("#set_button").addClass("sel");
									}else{
											$("#sets").css("left","-100%");
											$("#promotion").css("left","0%");
											$("#set_button").removeClass("sel");
											$("#prom_button").addClass("sel");	
							   		}
									//emf.linkWidget.init();
									replaceSetName();
									if( callback )
										callback();

									renameForwarededRulesetsInAction();
									
								  }else{
									  setFlashMessage( data.error, "error" );
									  $('.wait_message').hide();
								  }
							   
							   $('.wait_message').removeClass('indicator_1');
								  
							 },
				       'json');
		
 }
 
 function noRulesetContent() {
	$(".rule_list_content").html('<div class="no_data_msg">No Sets available. Create one to proceed</div>');
	$(".rule_content").html("<div class='no_data_msg'> No Conditions available. Create one to proceed</div>");
	$("#scope_list").html("");
	$("#add_condition").addClass("disable");
	$("#when").addClass("disable");
	$("#save").addClass("disable");
 }
 
 
 function replaceSetName() {
	var title,li;
	var linkIcons=$(".rule_list_content .link-icon");
	$(linkIcons).each( function(i,linksicon) {
		title=$(linksicon).attr("title");
		li=$(".ruleset_list_content li.sel").next().find("li[data-name='"+title+"']");
		$(linksicon).attr("title",$(li).text());
	});
 }
 
 function openScopePopup(callback) {
	var prefix = $('#prefix').val();
	var ruleset_id = $('#ruleset_id').val();
	var event_type = $('#selected_event_type').html(); 
	var ajaxUrl = prefix + '/xaja/AjaxService/program/get_scope_form.json?org_id='+$('#org_id').val();
	$('.wait_message').show().addClass('indicator_1');
	$.post( ajaxUrl, { ruleset_id : ruleset_id,
					   event_type : encodeURIComponent(event_type) }, function(data) {
	   if(!isOrgValid(data))
			return;
	   if ( data.html ){
		   $('#scopes_form').html( decodeURIComponent ( data.html ) );
		   $('#scopes_form').removeData("modal").modal({backdrop: 'static', keyboard: false});
		   $('#scope_form_CustomerCluster__cluster_value').attr('style','height:100px!important')
		   $('#1_scope_form_CustomerCluster__cluster_value').attr('style','height:100px!important')
		   setTimeout(callback,400);
		   
	   }else{
		  setFlashMessage( data.error, "error" );
	   }
	   $('.wait_message').show().removeClass('indicator_1');   
	}, 'json');
 }

 
 function renameForwarededRulesetsInAction(){
	 
	 $('.action_instance[data-action="FORWARD"]').each( function(){
		
		 $(this).find('[data-function="get-forwarded-ruleset"]').html( 
				 	$('[data-function="get_rules_list"][data-name="'+ 
				 			$(this).find('[data-function="get-forwarded-ruleset"]').attr('data-name') + '"]').html()
				 );
		 
		 
	 });
	 
 }
 
function setClusterType( source, dest ){
	$('#' +dest).val( $('#' + source + ' option:selected').html());
	$('#scope_form_CustomerCluster__cluster_name').trigger('blur');
}

function isNormalInteger(str) {
    return /^\+?(0|[1-9]\d*)$/.test(str);
}

function validateFileName(str){
	return /^[0-9 _a-zA-Z]{3,100}$/.test(str);
}

function isOrgValid(data){
	if(data.org_change){
		setFlashMessage(data.error,"error");
		location.href=$('#prefix').val()+'/org/points/program/Program?flash=You have changed the organization in another tab';
		return false;
	}
	
	return true;
}
