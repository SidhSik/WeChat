<script type='text/template' id='nav_bar_step_1'>
	<div class="msg-nav-bar row-fluid">
		<div class="back_div span1 padding-5"></div>
		<div class="span8 center navigation-control">
			<span><a id="nav_step1" class="active"><?= _campaign("Choose Recipient");?> > </a></span>
			<span><a id="nav_step2" class=""><?= _campaign("Attach Incentive"); ?> > </a></span>
			<% if( _.isUndefined( is_sms ) ) { %>
				<span><a id="nav_step2" class="" ><?= _campaign("Customize Design"); ?> > </a></span>
				<span><a id="nav_step3" class=""><?= _campaign("Plain Text Version"); ?> > </a></span>
			<% }else{ %>
				<span><a id="nav_step2" class="" ><?= _campaign("Customize Content"); ?> > </a></span>
			<% }%>
			<span><a id="nav_step4" class=""><?= _campaign("Delivery Settings");?></a></span>
		</div>
		<div class="next_div span3 padding-5 ">
			<a class="c-button c-button-next pull-right pt-trigger" id="next-button-action" data-animation="1" data-goto="2"><?= _campaign("Next"); ?> ></a>
		</div>
	</div>
</script>
<script type='text/template' id='nav_bar_step_2'>
	<div class="msg-nav-bar row-fluid">
		<div class="back_div span1 padding-5">
			<a class="c-button-grey pt-trigger" id="back_to_recipient" data-animation="2" data-goto="1"> < <?= _campaign("Back"); ?></a>
		</div>
		<div class="span8 center navigation-control">
			<span><a id="nav_step1" class=""><?= _campaign("Choose Recipient"); ?> > </a></span>
			<span><a id="nav_step2" class="active"><?= _campaign("Attach Incentive"); ?> > </a></span>
			<% if( _.isUndefined( is_sms ) ) { %>
				<span><a id="nav_step3" class="" ><?= _campaign("Customize Design"); ?> > </a></span>
				<span><a id="nav_step4" class=""><?= _campaign("Plain Text Version"); ?> > </a></span>
			<% }else{ %>
				<span><a id="nav_step3" class="" ><?= _campaign("Customize Content"); ?> > </a></span>
			<% }%>
			<span><a id="nav_step5" class=""><?= _campaign("Delivery Settings"); ?></a></span>
		</div>
		<div class="next_div span3 padding-5" >
			<a class="c-button c-button-next pull-right pt-trigger" id="next-button-action" data-animation="1" data-goto="3" ><?= _campaign("Next"); ?> ></a>
		</div>
	</div>
</script>
<script type='text/template' id='nav_bar_step_3'>
	<div class="msg-nav-bar row-fluid">
		<div class="back_div span1 padding-5">
			<a class="c-button-grey pt-trigger" id="back_to_coupon" data-animation="2" data-goto="2"> < <?= _campaign("Back"); ?></a>
		</div>
		<div class="span8 center navigation-control">
			<span><a id="nav_step1" class=""><?= _campaign("Choose Recipient"); ?> > </a></span>
			<span><a id="nav_step2" class=""><?= _campaign("Attach Incentive"); ?> > </a></span>
			<% if( _.isUndefined( is_sms )) { %>
				<span><a id="nav_step3" class="active"><?= _campaign("Customize Design"); ?> > </a></span>
				<span><a id="nav_step4" class=""><?= _campaign("Plain Text Version"); ?> > </a></span>
			<% }else{ %>
				<span><a id="nav_step3" class="active"><?= _campaign("Customize Content"); ?> > </a></span>
			<% } %>
			<span><a id="nav_step5" class=""><?= _campaign("Delivery Settings"); ?> </a></span>
		</div>
		<div class="next_div span3 padding-5">
			<% if(typeof is_wechat == 'undefined') {
			if( _.isUndefined( is_sms )){if(message_id) { %>
			<a class='c-button pull-right pt-trigger' id='edit_existing'><?= _campaign("Edit Existing"); ?></a>
			<%} else {%>
			<a class='c-button pull-right pt-trigger' id='edit_existing' style="display:none"><?= _campaign("Edit Existing"); ?></a>
			<%}
			 } } else {
			 if(message_id) { 
			%>
			<a class='c-button pull-right pt-trigger' id='edit_existing'><?= _campaign("Edit Existing"); ?></a>
			
			<% } } %>

			<% if( typeof is_mobilepush != 'undefined' && message_id > 0) { %>
			 	<a class='c-button c-button-next pull-right pt-trigger' id='skip-template' data-animation="1" data-goto="4"><?= _campaign("Skip"); ?></a>
			 	<%} else if(typeof is_mobilepush != 'undefined'){ %>
			 	<a class='c-button c-button-next pull-right pt-trigger' id='skip-template' data-animation="1" data-goto="4" style="display:none"><?= _campaign("Skip"); ?></a>
			 <%}%>
			
		</div>
	</div>
</script>
<script type='text/template' id='nav_bar_step_4'>
	<div class="msg-nav-bar row-fluid">
		<div class="back_div span1 padding-5">
			<a class="c-button-grey pt-trigger" id="back_to_template" data-animation="2" data-goto="3"> < <?= _campaign("Back"); ?></a>
		</div>
		<div class="span8 center navigation-control">
			<span><a id="nav_step1" class=""><?= _campaign("Choose Recipient"); ?> > </a></span>
			<span><a id="nav_step2" class=""><?= _campaign("Attach Incentive") ?> > </a></span>
			<% if( _.isUndefined( is_sms ) ) { %>
				<span><a id="nav_step3" class="active"><?= _campaign("Customize Design") ?> > </a></span>
				<span><a id="nav_step4" class=""><?= _campaign("Plain Text Version") ?> > </a></span>
			<% }else{ %>
				<span><a id="nav_step3" class="active"><?= _campaign("Customize Content"); ?> > </a></span>
			<% } %>
			<span><a id="nav_step5" class=""><?= _campaign("Delivery Settings"); ?></a></span>
		</div>
		<div class="next_div c-button-bar padding-5 span3">
			<% if( _.isUndefined( is_sms ) ) { %>
				<a class="c-button c-button-next margin-left active pull-right pt-trigger" id="goto_plain_text" data-animation="1" data-goto="5"><?= _campaign("Next") ?> ></a>
			<% }else { %>
				<a class="c-button c-button-next margin-left active pull-right pt-trigger" id="goto_delivery_setting" data-animation="1" data-goto="5"><?= _campaign("Next"); ?> ></a>
				<% if(typeof is_wechat == 'undefined') { %>
				<a class="c-button-grey plain-text-preview pull-right margin-left" id="preview_and_test"><?= _campaign("Preview And Test") ?></a>
			<% } }%>
		</div>
	</div>
</script>
<script type='text/template' id='nav_bar_step_5'>
	<div class="msg-nav-bar row-fluid">
		<% if( _.isUndefined( is_sms ) ) { %>
		<div class="back_div span1 padding-5"><a class="c-button-grey pt-trigger" id="back_to_edit" data-animation="2" data-goto="3"> < <?= _campaign("Back"); ?></a></div>
		<% } else { %>
			<div class="back_div span1 padding-5"><a class="c-button-grey pt-trigger" id="back_to_edit" data-animation="2" data-goto="4"> < <?= _campaign("Back"); ?></a></div>
		<% } %>
		<div class="span8 center navigation-control">
			<span><a id="nav_step1" class=""><?= _campaign("Choose Recipient"); ?> > </a></span>
			<span><a id="nav_step2" class=""><?= _campaign("Attach Incentive"); ?> > </a></span>
			<% if( _.isUndefined( is_sms ) ) { %>
				<span><a id="nav_step3" class=""><?= _campaign("Customize Design"); ?> > </a></span>
				<span><a id="nav_step4" class="active"><?= _campaign("Plain Text Version") ?> > </a></span>
				<span><a id="nav_step5" class=""><?= _campaign("Delivery Settings"); ?> </a></span>
			<% }else{ %>
				<span><a id="nav_step3" class=""><?= _campaign("Customize Content"); ?> > </a></span>
				<span><a id="nav_step5" class="active"><?= _campaign("Delivery Settings"); ?> </a></span>
			<% } %>
		</div>
		<div class="next_div span3 padding-5">
			<% if( _.isUndefined( is_sms ) ) { %>
				<a class="c-button c-button-next margin-left active pull-right pt-trigger" id="goto_delivery_settings" data-animation="1" data-goto="6"><?= _campaign("Next") ?> ></a>
				<a class="c-button-grey plain-text-preview pull-right" id="preview_and_test"><?= _campaign("Preview And Test"); ?></a>
			<% } %>
		</div>
	</div>
</script>
<script type='text/template' id='nav_bar_step_6'>
	<div class="msg-nav-bar row-fluid">
		<div class="back_div span1 padding-5">
			<a class="c-button-grey pt-trigger" id='go_back_to_plain_text' data-animation="2" data-goto="5"> < <?= _campaign("Back"); ?></a>
		</div>
		<div class="span8 center navigation-control">
			<span><a id="nav_step1" class=""><?= _campaign("Choose Recipient"); ?> > </a></span>
			<span><a id="nav_step2" class=""><?= _campaign("Attach Incentive"); ?> > </a></span>
			<span><a id="nav_step3" class=""><?= _campaign("Customize Design"); ?> > </a></span>
			<span><a id="nav_step4" class=""><?= _campaign("Plain Text Version"); ?> > </a></span>
			<span><a id="nav_step5" class="active"><?= _campaign("Delivery Settings"); ?> </a></span>
		</div>
		<div class="next_div span3 padding-5">
		</div>
	</div>
</script>
<script type='text/template' id='search-bar'>
	<div class='margin-top search_nav' style='display:inline-flex;'>
			<input type="text" class="msg_search" name="search_list" id="search_list">
			<span class='margin-right'><?= _campaign("Or"); ?></span>
			<% if( _.isUndefined( is_calltask )){ %>
				<div class="btn-group margin-left">
					  <button type="button" class="btn btn-inverse dropdown-toggle"
					  		  data-toggle="dropdown"><?= _campaign("Create Recipient List") ?>
				  		  <span class="caret"></span>
				  	 </button>
					  <ul class="dropdown-menu" role="menu">
					    <li><a id='open_filter' class='open_popup'><?= _campaign("Using a Loyalty Filter") ?></a></li>
					    <li><a id='open_nlfilter' class='open_popup'><?= _campaign("Using a Non Loyalty Filter") ?></a></li>
					    <li><a id='upload_csv' class='open_popup'><?= _campaign("Upload CSV"); ?></a></li>
					    <li><a id='paste_list' class='open_popup'><?= _campaign("Paste a List"); ?></a></li>
					    <!-- <li><a id='upload_ftp' class='open_popup'><?= _campaign("Upload via FTP"); ?></a></li> -->
					    <li class="divider"></li>
					    <li><a id='deduplicate' class='open_popup'><?= _campaign("De-duplicate from selected"); ?></a></li>
					  </ul>
				</div>
			<% }else{ %>
				<a id='open_filter' class='open_popup c-button-grey marign-left' style='height:20px'><?= _campaign("Using a Filter"); ?></a>
			<% } %>
	</div>
	
</script>

<!-- Template for campaign list radio button -->
<script type='text/template' id='radio_box1'>
        <div class='campaign_list'>
                <h3 class='margin-bottom'><?= _campaign("Campaign Specific List") ?>  <i class='icon-refresh font-color-green hand_cursor'></i>
                </h3>
                <div class='campaign_list_radio'>
                        <ul class="l-v-list" id="campaign_list_radio">
                                        <% if( _.size(item_data) < 1 ) {%>
                                                <li class='item'><?= _campaign('No campaign specific list') ?></li>
                                        <%}%>
                                        <% var cnt = 0 %>
                                        <% _.each(item_data,function(group,i){
                                                 var count = 0
                                                 if( _.isUndefined( is_sms )) {
                                                        count = group.email
                                                 }else{
                                                        count = group.mobile
                                                 }
                                                 var hide = 'search_enabled'
                                                 if( cnt >= 4 ) {
                                                        var hide = 'hide'
                                                 }
                                                 %>
                                                  <li class="item <%= hide %>" group_id="<%= group.group_id %>"
                                                        search_name="<%= group.group_label %>" count="<%= count %>">
                                                        <% if( _.isUndefined(msg_data) ){ %>
                                                                <i class="intouch-green-tick"></i>  <%= group.group_label %>
                                                        <% }else{ %>
                                                                <% if( group.group_id == msg_data.group_id ) { %>
                                                                        <i class="intouch-green-tick intouch-green-tick-active"></i>  <%= group.group_label %>
                                                                <% }else{ %>
                                                                        <i class="intouch-green-tick"></i>  <%= group.group_label %>
                                                                <% } %>
                                                        <% } %>
                                                        <span class="margin-left-double font-small">
                                                                <b><?= _campaign('Total') ?> :</b> <%= group.count %>

                                                                <% var test = ''
                                                                   var control = ''
                                                                %>
                                                                <% if( !_.isUndefined( group.test ) ) {
                                                                        test = "<b><?= _campaign('Test') ?> :</b>" + group.test
                                                                        control = "<b><?= _campaign('Control') ?> :</b>" + group.control
                                                                } %>
                                                                <% if( _.isUndefined( is_sms )) {%>
                                                                        <b><?= _campaign('Emails') ?> :</b> <%= group.email %> <%= test %>
                                                                <% }else{ %>
                                                                        <b><?= _campaign('Mobile Numbers') ?> :</b> <%= group.mobile %>  <%= test %>

                                                                <% } %>
                                                                <%= control %>
                                                        </span>
                                                </li>
                                                <% cnt = cnt + 1 %>
                                        <% }); %>
                        </ul>
                        <% if( cnt >= 4 ) { %>
                                <a class='font-color-green show_more_list
                                                  show_more_campaign_list_radio' ul-id="campaign_list_radio"><?= _campaign('Show More') ?></a>
                                <a class='font-color-green show_less_list
                                                  show_less_campaign_list_radio hide' ul-id="campaign_list_radio"><?= _campaign('Show Less') ?></a>
                        <% } %>
                </div>
        </div>
</script>



<script type='text/template' id='radio_box'>
	<div class='campaign_list'>
		<h3 class='margin-bottom'><?= _campaign("Campaign Specific List") ?>  <i class='icon-refresh font-color-green hand_cursor'></i>
		</h3>
		<div class='campaign_list_radio'>
			
			<ul class="l-v-list l-v-list-non_sticky" id="campaign_list_radio">
					
			</ul>
				<a class='font-color-green show_more_list
						  show_more_campaign_list_radio hide' ul-id="campaign_list_radio"><?= _campaign('Show More') ?></a>
				<a class='font-color-green show_less_list
						  show_less_campaign_list_radio hide' ul-id="campaign_list_radio"><?= _campaign('Show Less') ?></a>
		</div>
	</div>
</script>

<script type='text/template' id='recipient_vlist'>
	
	<% if(group.data_size < 1) {%>
		<li class='item'><?= _campaign('No campaign specific list') ?></li>
					<%}%>


	<% var count = 0%>
	<% if( _.isUndefined( is_sms )) {
						 	count = group.email
						 }else{
						 	if( typeof is_wechat != 'undefined' ) {
						 		count = group.wechatOpenIdCount["wechat_"+account_id] ? group.wechatOpenIdCount["wechat_"+account_id] : (group.wechatOpenIdCount["wechat_0"] ? group.wechatOpenIdCount["wechat_0"] : 0 )
						 	}else if(typeof is_mobilepush != 'undefined'){
                                count = group.count
                            }
						 	else{
						 		count = group.mobile
						 	}
						 }
						 var hide = 'search_enabled'
						 if( group.item_data_count >= 5 && group.showMore==0) {
						 	var hide = 'hide'
						 }else{
						 	var hide = 'search_enabled'
						 }
						 %>
	<% if(group.successMessage==1) {%>
		<div class="c-error-popup c-error-popup-green">
	<% }else if(group.errorMessage==1){ %>
		<div class="c-error-popup c-error-popup-orange">
	<%}else{%>
		<div class="c-error-popup">
		<%}%>
	<li class="item <%= hide %> c-pad5" id="recipient_<%= group.group_id %>" gtype="<%= group.type %>" group_id="<%= group.group_id %>" search_name="<%= group.group_label %>" count="<%= count %>">
							<% if( _.isUndefined(msg_data) ){ %>
								<% if(group.radioSelected ==1) {%>
								<i class="intouch-green-tick intouch-green-tick-active"></i>  <%= group.group_label %>
								<% } else { %>
									<i class="intouch-green-tick"></i>  <%= group.group_label %>
									<%}%>
							<% }else{ %>
								<% if( group.group_id == msg_data.group_id ) { %>
									<i class="intouch-green-tick intouch-green-tick-active"></i>  <%= group.group_label %>
								<% }else{ %>
									<i class="intouch-green-tick"></i>  <%= group.group_label %>
								<% } %>
							<% } %>
							<span class="margin-left-double font-small">
								<b><?= _campaign('Total Customers') ?> :</b> <%= group.count %>

								<% var test = ''
								   var control = ''
								   var is_completed = 0
								%>

								<% if(group.isPrevious==true) { %>
									<% if( !_.isUndefined( group.test ) ) {
                                                                        test = "<b>Test :</b>" + group.test
                                                                        control = "<b>Control :</b>" + group.control
                                                                } %>
                                                                <% if( _.isUndefined( is_sms )) {%>
                                                                        <b><?= _campaign('Emails') ?> :</b> <%= group.email %> <%= test %>
                                                                <% }else{ %>

                                                                	<% if( typeof is_wechat != 'undefined' ) {%>
                                                                        <b><?= _campaign("WeChat OpenIds") ?> :</b> <%= group.wechatOpenIdCount["wechat_"+account_id] ? group.wechatOpenIdCount["wechat_"+account_id] : (group.wechatOpenIdCount["wechat_0"] ? group.wechatOpenIdCount["wechat_0"] : 0 ) %> <%= test %>
                                                                    <% }else{
                                                                    		if(typeof is_mobilepush != 'undefined'){%>
                                                                    	<b><?= _campaign("Android :") ?></b>
                                                                    	<b><?= _campaign("IOS :") ?></b>
                                                                    	<%}else{%>
                                                                    	<b><?= _campaign("Mobile Numbers :") ?></b> <%= group.mobile %>  <%= test %>
                                                                    <% }} %>
                                                                <% } %>
                                                                <%= control %>
                                         <% } else { %>
								
								<% if( _.isUndefined( is_sms )) {%>
									<b><?= _campaign('Target Email IDs') ?> :</b> <%= group.reachable_email %>
								<% }else{ %>

									<% if( typeof is_wechat != 'undefined' ) {%>
										<b><?= _campaign('Target WeChat OpenIds') ?> :</b> <%= group.wechatOpenIdCount["wechat_"+account_id] ? group.wechatOpenIdCount["wechat_"+account_id] :
											(group.wechatOpenIdCount["wechat_0"] ? group.wechatOpenIdCount["wechat_0"] : 0 ) %>
									<% }else{ 
											if(typeof is_mobilepush != 'undefined'){%>
											<b><?= _campaign("Android :") ?></b><%=group.androidCount%>
                                                                    	<b><?= _campaign("IOS :") ?></b><%=group.iosCount%>
										<%}else{%>
										<b><?= _campaign('Target Mobile Nos') ?> :</b> <%= group.reachable_mobile %>
									<% }} %>
								<% } %>
								<%= control %>
								<% if( typeof is_wechat == 'undefined' && typeof is_mobilepush == 'undefined' ) {%>
								<% if( _.isUndefined( is_sms ) ) {
									is_completed = group.yet_verifying_email;
								} else{
									is_completed = group.yet_verifying_mobile;
									}%>
								<% if(is_completed==0) { %>
									 				<i class='c-checkmark c-show-progress'>&#10004</i>
									 			<% } else { %>
									 				<i class="sk-fading-circle c-show-progress">
														  <i class="sk-circle1 sk-circle"></i>
														  <i class="sk-circle2 sk-circle"></i>
														  <i class="sk-circle3 sk-circle"></i>
														  <i class="sk-circle4 sk-circle"></i>
														  <i class="sk-circle5 sk-circle"></i>
														  <i class="sk-circle6 sk-circle"></i>
														  <i class="sk-circle7 sk-circle"></i>
														  <i class="sk-circle8 sk-circle"></i>
														  <i class="sk-circle9 sk-circle"></i>
														  <i class="sk-circle10 sk-circle"></i>
														  <i class="sk-circle11 sk-circle"></i>
														  <i class="sk-circle12 sk-circle"></i>
													</i> 
												<% } 
									}%>
							</span>
							<% if( typeof is_wechat == 'undefined' && typeof is_mobilepush == 'undefined') {%>
								<% if(group.moreDetails==1) {%>
									<div><i class = "plus-icon c-plus-icon"> <?= _campaign("Less details"); ?></i></div>
								<% }else{ %>
									<div><i class = "plus-icon c-plus-icon"> <?= _campaign("More details"); ?></i></div>
								<%}%> 
							<% } %>
							<% if( typeof is_wechat == 'undefined' ) {%>
								<% if( _.isUndefined( is_sms )) {%>
								<div class='c-show-information hide'>
											<% if(group.yet_verifying_email==0) { %>
												<?= _campaign("Email ID Verification Completed"); ?>
											<% } else { %>
												<?= _campaign("Email ID Verification: <%= group.percentage_email %>% of <%= group.email %> remaining. <br> More Email IDs can be reached, as List verification progresses"); ?> </br>
												<% } %>

										</div>
									<% }else{ %>
									<div class='c-show-information hide'>
											<% if(group.yet_verifying_mobile==0) { %>
												<?= _campaign("Mobile No. Verification Completed"); ?>
											<% } else { %>
												<?= _campaign("Mobile No. Verification: <%= group.percentage_mobile %>% of <%= group.mobile %> remaining. <br> More Mobile Nos can be reached, as List verification progresses"); ?> </br>
												<% } %>
										</div>
									<% } %>
								<div class='clearfix'></div>
						        <div class='c-rec-popup-cont c-rec-popup-style' ></div>
						        <% } %>
					        <% } %>
						</li>
						<% if( typeof is_wechat == 'undefined' && typeof is_mobilepush == 'undefined') {%>
							<% if(group.successMessage==1) {%>
							<div class="c-popup-text-green"><?= _campaign("Great. All customers on the list are verified."); ?> <i class="plus-icon c-plus-icon c-margin5">

								<% if(group.moreDetails==1) {%>
								<?= _campaign("Less details"); ?></i><i class='c-downarrow-icon hide'></i><i class='c-uparrow-icon'></i>
								<% }else{ %>
								<?= _campaign("More details"); ?></i><i class='c-downarrow-icon'></i><i class='c-uparrow-icon hide'></i>
								<%}%>

								</div>
							<% } else {%>
								<div class="c-popup-text-green hide"><?= _campaign("Great. All customers on the list are verified."); ?> <i class="plus-icon c-plus-icon c-margin5"><?= _campaign("More details"); ?></i><i class='c-downarrow-icon'></i><i class='c-uparrow-icon hide'></i></div>
								<%}%>
							<% if(group.errorMessage==1) {%>
							<div class="c-popup-text-orange"><i class='c-warning-icon'></i><?= _campaign("This list is still being verified. More customers can be reached, as verification progresses."); ?><i class="plus-icon c-plus-icon c-margin5"><?= _campaign("More details"); ?></i><i class='c-downarrow-icon'></i><i class='c-uparrow-icon hide'></i> </div>
							<% } else {%>
								<div class="c-popup-text-orange hide"><i class='c-warning-icon'></i><?= _campaign("This list is still being verified. More customers can be reached, as verification progresses."); ?><i class="plus-icon c-plus-icon c-margin5"><?= _campaign("More details"); ?></i><i class='c-downarrow-icon'></i><i class='c-uparrow-icon hide'></i> </div>
								<%}%>
						<% } %>
</div>
<div style="clear:both" />	
</script>

<!-- Tempalte for sticky list check box -->
<script type='text/template' id='sticky_checkbox'>
	<div class='campaign_list'>
		<h3 class="margin-bottom"><?= _campaign("Sticky/ Test Lists"); ?>
		</h3>
		<ul class="l-v-list l-v-list-sticky" id="campaign_list_check">
			
		</ul>

		<a class='font-color-green show_more_list
						  show_more_campaign_list_check hide' ul-id="campaign_list_check"><?= _campaign("Show More"); ?></a>
				<a class='font-color-green show_less_list
						  show_less_campaign_list_check hide' ul-id="campaign_list_check"><?= _campaign("Show Less"); ?></a>
	</div>
</script>

<script type='text/template' id='recipient_vlist_sticky'>
	
<% if( (group.data_size) < 1 ) {%>
				<li class='item sticky-f15'><?= _campaign("No sticky lists available"); ?></li>
			<%}%>
				<% var cnt = 0
				   var email_mobile = ''
				   var grp_count = 0
				%>
				<% 
						if( _.isUndefined( is_sms ) ) {
							email_mobile = '<b><?= _campaign("Emails :") ?></b>' + group.email
							grp_count = group.email
						}else{

							if( typeof is_wechat != 'undefined' ) {
								
								grp_count = group.params["wechat_"+account_id] != undefined ? group.params["wechat_"+account_id] : ( group.params["wechat_0"] != undefined ? group.params["wechat_0"] : 0 );

								email_mobile = '<b><?= _campaign("WeChat OpenIds :") ?></b>' + grp_count

							}else{
								if(typeof is_mobilepush != 'undefined'){
									grp_count = group.androidCount + group.iosCount
								}else{
									email_mobile = '<b><?= _campaign("Mobile Numbers :") ?></b>' + group.mobile
									grp_count = group.mobile
								}
							}
						}
						if( group.item_data_count < 5 ) { %>
							<li class="item search_enabled" id="recipient_<%= group.group_id %>" gtype="<%= group.type %>" group_id="<%= group.group_id %>"
								search_name="<%= group.group_label%>" count="<%= grp_count %>">
								<% if( _.isUndefined(msg_data) ){ %>
									<% if(group.radioSelected ==1) {%>
								<i class="intouch-green-tick intouch-green-tick-active"></i>  <%= group.group_label %>
								<% } else { %>
									<i class="intouch-green-tick"></i>  <%= group.group_label %>
									<%}%>
								<% }else{ %>
									<% if( group.group_id == msg_data.group_id ) { %>
										<i class="intouch-green-tick intouch-green-tick-active"></i>  <%= group.group_label %>
									<% }else{ %>
										<i class="intouch-green-tick"></i>  <%= group.group_label %>
									<% } %>
								<% } %>
								<span class="margin-left-double font-small">
								<b><?= _campaign("Total :") ?></b> <%= group.count %>
								<%= email_mobile %></span>
							</li>
							
						<% }else{ %>
							<li class="item hide" id="recipient_<%= group.group_id %>" gtype="<%= group.type %>" group_id="<%= group.group_id %>"
								search_name="<%= group.group_label%>" count="<%= grp_count %>">
								<% if( _.isUndefined(msg_data) ){ %>
									<% if(group.radioSelected ==1) {%>
								<i class="intouch-green-tick intouch-green-tick-active"></i>  <%= group.group_label %>
								<% } else { %>
									<i class="intouch-green-tick"></i>  <%= group.group_label %>
									<%}%>
								<% }else{ %>
									<% if( group.group_id == msg_data.group_id ) { %>
										<i class="intouch-green-tick intouch-green-tick-active"></i>  <%= group.group_label %>
									<% }else{ %>
										<i class="intouch-green-tick"></i>  <%= group.group_label %>
									<% } %>
								<% } %>
								<span class="margin-left-double font-small">
								<b><?= _campaign("Total :"); ?></b> <%= group.count %>
								<%= email_mobile %></span>
							</li>
							
						<% } %>


</script>

<script type='text/template' id='ndnc_checkbox'>
        <% if ( data.ndnc_enabled == 1)  { %>
       		 <div class='ndnc_target'>
                <% if (!data.readOnly) { %>
                    <div class="ndnc_label"><label><input type="checkbox" id="ndnc_check"/><?= _campaign("Target NDNC Opt out customers (not recommended)"); ?></label></div>
                	<span class = "gateway_error hide"><i class="icon-exclamation  hand_cursor"></i><?= _campaign("Ndnc Gateway not set. Contact Gateways Team"); ?></span> 
            <% } else { %>
                <% if ( data.ndnc_selected == 'true') { %>
                    <div class="ndnc_label"><label ><input type="checkbox" id="ndnc_check" disabled="disabled" checked="checked" /><?= _campaign("Target NDNC Opt out customers (not recommended)"); ?></label></div>
                     <% if (  data.ndnc_gateway == 'false' ) { %>
                    		 <span class = "gateway_error "><i class="icon-exclamation  hand_cursor"></i><?= _campaign("Ndnc Gateway not set. Contact Gateways Team"); ?></span>
                       	<% } %>
                <% } else { %>
                    <input type="checkbox" id="ndnc_check" disabled="disabled"/> <?= _campaign("Target NDNC Opt out customers (not recommended)"); ?>
                	<% } %>
            <% } %>             
                </div>
                
        <% } else { %>  
        	<% if ( !data.readOnly) { %>
        		<div class='ndnc_no_target'>
                	<span class="ndnc_no"><?= _campaign("Note:- NDNC Opt out customers will not be targeted"); ?><i class="icon-info-sign hand_cursor" id="info_circle_tip" data-toggle="popover" title="<?= _campaign('NDNC Disabled'); ?>" data-content="<?= _campaign('Targeting NDNC customers is  disabled for all campaigns.This setting can be changed from Org Admin Page->Send to NDNC Customers section'); ?>" data-placement="right"></i><span/>
                </div>
        	<%} else { %>
        		<div class='ndnc_no_target'>
                	<span class="ndnc_no"><?= _campaign("Note:- NDNC Opt out customers will not be targeted"); ?><span/>
                </div>
        	<% } %>	
                
        <% } %> 
</script>



<script type="text/template" id="details-templ">
	
	<div class="c-main-div">
		<div class = "c-left-section">
			<% 
				var rr2 =''
			%>
			<div>
    		<% if( _.isUndefined(is_sms) ){ %>

    			<% if(group.rule2==0) {%>
    				<% rr2 = group.reachable_email - group.soft_bounced_email %>
    			<% }else{ %>
    				<% rr2 = group.reachable_email %>
    			<% } %>

				<div class='c-element c-div-class'>
					<span class='c-left-text'><b><?= _campaign("Can be contacted (Target Email IDs)"); ?></b></span> <span class='c-right-text'><%= rr2 %></span>
				</div>

				<div class = 'c-element c-element-margin'>
					<span class="c-left-text">
					<% if(group.rule1==1) {%>
					<input type="checkbox" class="c-checkbox" value="c-rule1" name="cc" checked disabled />
					<%}else{%>
					<input type="checkbox" class="c-checkbox" value="c-rule1" name="cc" />
					<%}%>
					</span><span class ="c-left-text c-element-line"><?= _campaign("Email IDs which are valid"); ?></span><span class='c-right-text'>
					<%= group.valid_email %></span>
				</div>
				<% if(group.unable_email > 0) {%>
				<div class = 'c-element c-element-margin'>
					<span class="c-left-text">
					<input type="checkbox" class="c-checkbox"  name="cc" checked disabled />
					</span><span class ="c-left-text c-element-line"><?= _campaign("Unable to Verify(Unknown)"); ?></span><span class='c-right-text'>
					<%= group.unable_email %></span>
				</div>
				<% } %>
				<div class = 'c-element c-element-margin'>
					<span class="c-left-text">
					<% if(group.rule2==1) {%>
					<input type="checkbox" class="c-checkbox" value="c-rule2" name="cc" checked />
					<%}else{%>
					<input type="checkbox" class="c-checkbox" value="c-rule2" name="cc" />
					<%}%>
					</span><span class ="c-left-text c-element-line"><?= _campaign("Email IDs with 1-3 are soft bounces"); ?></span><span class='c-right-text'>
					<%= group.soft_bounced_email %></span>
				</div>

				
										<% if(group.yet_verifying_email==0) { %>
											
										<% } else { %>
											<div class='c-element c-element-style'>
											<span class='c-left-text'><?= _campaign("Email ID Verification: <%= group.percentage_email %>% of <%= group.email %> remaining."); ?></span><span class='c-right-data c-margin2'><%= group.yet_verifying_email %></span>
											</div>
											<% } %>

				
				<div class='c-element c-div-class-cannot'>
					</span><span class='c-left-text '><b><?= _campaign("Cannot be contacted"); ?> </b></span> <span class='c-right-text '> <%= group.unreachable_email %></span>
				</div>
				<div class = 'c-element c-element-margin'>
					<span class="c-left-text"><input type="checkbox" class="checkbox" name="cc" disabled /></span><span class ="c-left-text c-element-line"><?= _campaign("Unsubscribed"); ?> </span><span class='c-right-text'>
					<%= group.unsubscribed_email %></span>
				</div>
				<div class = 'c-element c-element-margin'>
					<span class="c-left-text"><input type="checkbox" class="checkbox" name="cc" disabled /></span><span class ="c-left-text c-element-line"><?= _campaign("Emails not available"); ?> </span><span class='c-right-text'>
					<%= group.unavailable_email %></span>
				</div>
				<% if(group.unverify_email > 0) {%>
				<div class = 'c-element c-element-margin'>
					<span class="c-left-text">
					<input type="checkbox" class="c-checkbox"  name="cc" disabled />
					</span><span class ="c-left-text c-element-line"><?= _campaign("Service Issue"); ?></span><span class='c-right-text'>
					<%= group.unverify_email %></span>
				</div>
				<% } %>
				<div class = 'c-element c-element-margin'>
					<span class="c-left-text"><input type="checkbox" class="checkbox" name="cc" disabled /></span><span class ="c-left-text c-element-line"><?= _campaign("Invalid Email address"); ?> </span><span class='c-right-text'>
					<%= group.invalid_email %></span>
				</div>
	
			<% }else{ 
					if(typeof is_mobilepush != 'undefined'){%>
						<div class="table">
					    <div class="heading">
					        <div class="cell">
					            <p></p>
					        </div>
					        <div class="cell">
					            <p><b><?=_campaign('Total')?></b></p>
					        </div>
					        <div class="cell">
					            <p><b><?=_campaign('Android')?></b></p>
					        </div>
					         <div class="cell">
					            <p><b><?=_campaign('IOS')?></b></p>
					        </div>
					    </div>
					    <div class="row">
					        <div class="cell">
					            <p><b><?=_campaign('Can be Contacted(Mobile Push)')?></b></p>
					        </div>
					        <div class="cell">
					            <p><%=group.totalMobilePushCanContact%></p>
					        </div>
					        <div class="cell">
					            <p><%=group.totalAndroidCanContact%></p>
					        </div>
					         <div class="cell">
					            <p><%=group.totalIOSCanContact%></p>
					        </div>
					    </div>
					    <div class="row">
					        <div class="cell">
					            <p><input type="checkbox" class="checkbox c-margin-sms" name="cc" checked disabled /><b><?=_campaign('Subscribed')?></b></p>
					        </div>
					        <div class="cell">
					            <p><%=group.mobilePushTotalSubscribed%></p>
					        </div>
					        <div class="cell">
					            <p><%=group.androidSubscriptionCount%></p>
					        </div>
					        <div class="cell">
					            <p><%=group.iosSubscriptionCount%></p>
					        </div>
					    </div>
					    <div class="row" id = "cannot-be-contacted">
					        <div class="cell">
					            <p><b><?=_campaign('Cannot be Contacted')?></b></p>
					        </div>
					        <div class="cell">
					            <p><%=group.totalMobilePushCannotContact%></p>
					        </div>
					        <div class="cell">
					            <p><%=group.totalAndroidCannotContact%></p>
					        </div>
					        <div class="cell">
					            <p><%=group.totalIOSCannotContact%></p>
					        </div>
					    </div>
					    <div class="row">
					        <div class="cell">
					            <p><input type="checkbox" class="checkbox c-margin-sms" name="cc" disabled /><b><?=_campaign('Unsubscribed')?></b></p>
					        </div>
					        <div class="cell">
					            <p><%=group.mobilePushTotalUnsubscribed%></p>
					        </div>
					        <div class="cell">
					            <p><%=group.androidUnsubscriptionCount%></p>
					        </div>
					        <div class="cell">
					            <p><%=group.iosUnsubscriptionCount%></p>
					        </div>
					    </div>
					    <div class="row">
					        <div class="cell">
					            <p><input type="checkbox" class="checkbox c-margin-sms" name="cc" disabled /><b><?=_campaign('Unavailable on App')?></b></p>
					        </div>
					        <div class="cell">
					            <p><%=group.totalMobilePushCannotContact%></p>
					        </div>
					        <div class="cell">
					            <p><%=group.totalAndroidCannotContact%></p>
					        </div>
					        <div class="cell">
					            <p><%=group.totalIOSCannotContact%></p>
					        </div>
					    </div>
					</div>
					<%}else{%>
				
				<div class='c-element'>
					</span><span class='c-left-text'><b><?= _campaign("Can be contacted (Target Mobile Nos)"); ?></b></span> <span class='c-right-text'><%= group.reachable_mobile %></span>
				</div>
				<div class = 'c-element c-element-margin'>
					<span class="c-left-text"><input type="checkbox" class="checkbox c-margin-sms" name="cc" checked disabled /></span><span class ="c-left-text c-element-line"><?= _campaign("Numbers which are valid"); ?></span><span class='c-right-text'>
					<%= group.valid_mobile %></span>
				</div>
				<% if(group.unable_mobile > 0) {%>
				<div class = 'c-element c-element-margin'>
					<span class="c-left-text">
					<input type="checkbox" class="c-checkbox c-margin-sms"  name="cc" checked disabled />
					</span><span class ="c-left-text c-element-line"><?= _campaign("Unable to Verify(Unknown)"); ?></span><span class='c-right-text'>
					<%= group.unable_mobile %></span>
				</div>
				<% } %>
				
										<% if(group.yet_verifying_mobile==0) { %>
											
										<% } else { %>
											<div class='c-element c-element-style'>
											<span class='c-left-text'><?= _campaign("Mobile No Verification: <%= group.percentage_mobile %>% of <%= group.mobile %> remaining."); ?></span><span class='c-right-text c-margin2'><%= group.yet_verifying_mobile %></span>
											</div>
											<% } %>

				
				<div class='c-element'>
					<span class='c-left-text'><b><?= _campaign("Cannot be contacted"); ?> </b></span> <span class='c-right-text'> <%= group.unreachable_mobile %></span>
				</div>
				<div class = 'c-element c-element-margin'>
					<span class="c-left-text"><input type="checkbox" class="checkbox c-margin-sms" name="cc" disabled /></span><span class ="c-left-text c-element-line"><?= _campaign("Unsubscribed"); ?> </span><span class='c-right-text'>
					<%= group.unsubscribed_mobile %></span>
				</div>
				<% if(group.unverify_mobile > 0) {%>
				<div class = 'c-element c-element-margin'>
					<span class="c-left-text">
					<input type="checkbox" class="c-checkbox c-margin-sms"  name="cc" disabled />
					</span><span class ="c-left-text c-element-line"><?= _campaign("Service Issue"); ?></span><span class='c-right-text'>
					<%= group.verify_mobile %></span>
				</div>
				<% } %>
				<div class = 'c-element c-element-margin'>
					<span class="c-left-text"><input type="checkbox" class="checkbox c-margin-sms" name="cc" disabled /></span><span class ="c-left-text c-element-line"><?= _campaign("Numbers not available"); ?> </span><span class='c-right-text'>
					<%= group.invalid_mobile %></span>
				</div>
			<% }} %>
			</div>
		</div>
		<div class = "c-right-section">
			<div class="c-default-content">
			<?= _campaign("The list has been created using"); ?> <% if(group.type.toLowerCase()=='loyalty') { %>
											<?= _campaign("Loyalty Filters"); ?>
											<% }else if(group.type.toLowerCase()=='non_loyalty') { %>
											<?= _campaign("Non Loyalty Filters"); ?>
											<% } else { %>
											<?= _campaign("CSV Upload"); ?>
											<% } %>
			</div>
			<ul>
				<% _.each(rc,function(i,val){ %> 
					<li>
						<%= i  %>
					</li>
				<% }); %>
			</ul>
		</div>
	</div>

</script>

<script type='text' id='render-template-list'>
	<div class="" id="creative_template">
		<div>
			<% if( hide.is_select ) { %>
				<input type="text" class="msg_search margin-top" name="search_template" id="search_template">
				<% if( _.isUndefined( is_sms )){ %>
					<a class="c-button-grey" id='create_new_template'>
							<?= _campaign("Create New Template"); ?>
					</a>
				<% }else{ %>
					<a class="c-button-grey" id='create_new_template'>
							<?= _campaign("Create New Template"); ?>
					</a>
				<% } %>
				<select id='template_type' class='pull-right margin-right margin-top'>
					<option value='ALL'><?= _campaign("All"); ?></option>
					<option value='CREATIVE'><?= _campaign("Creative Assets"); ?></option>
					<option value='BASIC'><?= _campaign("Basic Template"); ?></option>
				</select>
			<% } %>
		</div>
		<div style='height:60px;' class=''>
			<h3 class="margin-bottom pull-left <%= hide.ul_class %>"><%= hide.template_title %>
				<i class="hand_cursor font-color-green margin-left icon-refresh" id="refresh-list"></i>
			</h3>
		</div><div class='<%= hide.ul_class %>'>
		<ul class='l-h-list'>
			<% _.each(template,function(temp,i){ %>
				<% if( i >= 6 ) { %>
					<li class='margin-top template-list_<%= hide.ul_class %> item <%= hide.hide %>'
						template_name='<%= temp.template_name%>'>
				<% }else{ %>
						<li class='margin-top template-list_<%= hide.ul_class %> item search_enabled'
						template_name='<%= temp.template_name%>'>
				<% } %>
					<ul class="l-v-list">
						<li class="item template-name" rel='tooltip' title='<%= temp.template_name %>'>
							<%= temp.template_name %>
						</li>
						<li class="item image_preview">
							<% if( _.isUndefined( is_sms ) ) { %>
								<% if( temp.is_preview_generated == 1 ){ %>
									<img src="<%=temp.content%>" alt="<%=temp.template_name%>">
								<% }else{ %>
									<div class="tmp_preview"><?= _campaign("Preview is being generated.") ?></div>
								<% } %>
							<% }else{ %>
								<div><%= temp.content %></div>
							<% } %>
						</li>
						<li style='margin-bottom:10px;'>
							<a class="c-button-grey template_selected" id="select_template"
								context="<%= hide.ul_class %>" template_id="<%= temp.template_id %>" data-animation="1" data-goto="4" ><?= _campaign("Select"); ?></a>
							<a class="link font-color-green margin-left"
							   id="show_template_preview" context="<%= hide.ul_class %>" template_id="<%= temp.template_id %>"><?= _campaign("Preview"); ?></a>
						</li>
					</ul>
				</li>
			<% }); %>
		</ul>
		 <% if( _.size( template ) > 6 ) { %>
			 <div class="center margin-top <%= hide.ul_class %>">
				<a class="font-color-green font-bold" id="show_more_template_<%= hide.ul_class %>"><?= _campaign("Show More Templates"); ?></a>
			</div>
		<% } %>
		</div>
	</div>
</script>

<script type='text/template' id='preview-modal'>
		<div class="modal hide fade" id="<%= modal %>" style='overflow:hidden'>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<h3 class="modal-title"><%= title %></h3>
			</div>
			<div class="modal-body <%= modal %>">
				<iframe id="modal-iframe" style="width:98%;height:450px;border:0px;"></iframe>
			</div>
			<div class="modal-footer">
				<a data-dismiss="modal" class="btn"><?= _campaign("Close"); ?></a>
			</div>
		</div>
</script>

<script type='text/template' id='email-preview-modal'>
	<div class="modal hide fade" id="<%= modal %>" style='overflow:hidden'>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 class="modal-title"><%= title %></h3>
			<div id="lang_tab_preview_parent" style="margin-bottom:2.2%">
                <ul class='lang_tab_preview lang_list_tabs' style="list-style-type:none">

                </ul>
            </div>
		</div>
		<div class="modal-body <%= modal %>">
			<div class="email-preview-button-bar">
				<div class="btn-group" style="margin-left:39%">
					<button type="button" class="btn-email-desktop btn btn-padding btn-inverse active background-color"><?= _campaign("Desktop"); ?></button>
					<button type="button" class="btn-email-mobile btn btn-padding btn-default"><?= _campaign("Mobile")?></button>
				</div>
			</div>
			<div class="email-desktop pull-left flexcroll">
				<iframe id="<%= modal %>__email-iframe-preview" class="email-iframe-preview flexcroll"></iframe>
			</div>
			<div class="email-mobile pull-left flexcroll hide">
				<div style="width:9%;" class="pull-right mobile-right-bar-hide">
					<span class="mobile-view pull-right margin-top mobile-portrait hand_cursor"><?= _campaign("Portrait"); ?></span>
					<span class="mobile-view pull-right margin-top mobile-landscape-active"><?= _campaign("Landscape"); ?></span>
				</div>
				<div class="bananaphone hide">
					<iframe id="<%= modal %>__preview-iframe-mobile-portrait" class="flexcroll"></iframe>
				</div>
				<div class="bananaphone-landscape" style="width: 55%;">
					<iframe id="<%= modal %>__iframe-mobile-landscape" class="flexcroll"></iframe>
				</div>
			</div>
		</div>
		<div class="email-modal-footer">
			<a data-dismiss="modal" class="btn"><?= _campaign("Close"); ?></a>
		</div>
	</div>
</script>

<!-- template edit script starts from here -->
<script type='text/template'id='template-edit-subject'>
	<div class='subject-main'>
		<div class='padding-left-default pull-left sender-info'>
			<?= _campaign("From: "); ?><span class='from'><%= sender_label %></span>
			<span class='email'> &lt;<%= sender_from %>&gt;</span>
			<i class='icon-pencil font-color-green hand_cursor'></i>
		</div>
		<div class="subject-bar">
			<div class="pull-left subject-lbl">
				<label class="margin-left margin-right"><?= _campaign("Subject"); ?></label>
				<input type="text" id="edit_template__subject" name="edit_template__subject"
					   value="" style="">
				<input type='hidden' name='current_position'
					   id='current_position' value='0' last_focused='' />
			</div>
        </div>

        <div class="div-gallery pull-right">
        	<a class="c-button-grey" id="cap-email-editor-switch" editor="inline"><?= _campaign("Back to Old Editor"); ?></a>
        	<a class="c-button-grey" id="image_gallery"><?= _campaign("Image Gallery"); ?></a>
        </div>
        <div class="div-gallery pull-right">
        	<a id="spam_checker" name="spam_checker" class="c-button-grey"><i class="icon-dashboard"></i> <?= _campaign("Check Spam") ?></a>
        </div>
        <div id="spamModal" class="hide modal spam_preview coupen-preview" style="display: none;">
			<div id="spam_score__widget"></div>
		</div>
		<div class='subject-bar hide'>
			<label class="margin-left margin-right"><?= _campaign("From Name"); ?></label>
			<input type='text' name='from_name' id='from_name' style="width:20%;margin-top:10px;"
				   value='<%= sender_label %>'/>
			<label class="margin-left margin-right"><?= _campaign("From Email Address"); ?></label>
			<input type='text' name='from_name' id='from_email' style="width:20%;margin-top:10px;"
				   value='<%= sender_from %>'
				   class='validate[required;regexcheck</^[a-zA-Z0-9-_+.]+@[a-zA-Z0-9-_.]+\.[a-zA-Z]+$/i>;custom_val<Email must be a valid email>]' />
			<a class='padding-save-btn c-button margin-left' id='save-sender'><?= _campaign("Save"); ?></a>
			<a class='c-button-grey' id='cancel-sender'><?= _campaign("Cancel"); ?></a>
		</div>
	</div>
</script>
<script type='text/template'id='template-edit-left-panel'>
	<div class='margin-left custom-tag-bar pull-left'>
		<div class="template-function margin-top">
			<span id="tags" class="active"><?= _campaign("TAGS"); ?></span>
			<span id="inserts"><?= _campaign("INSERTS"); ?></span>
			<span id="social"><?= _campaign("SOCIAL MEDIA"); ?></span>
		</div>
		<div class='template-function template-function-tags'>
			<div class='template-options' id='custom-tags'>
				<%= tags %>
			</div>
			<div class='template-options hide' id='custom-social'>
				<%= social %>
			</div>
			<div class='template-options hide' id='custom-inserts'>
				<% if( _.size(html) > 0 ) { %>
					<div class='content-box-small' type='HTML' template-id='<%= html.template_id %>'>
						<img class='add-image' src='<%= html.is_preview_generated == 1 ? html.content : "" %>' alt='<%= html.is_preview_generated == 1 ? html.template_name : html.content %>' />
					</div>
					<span class='small-content-title'><%= html.template_name %></span>
				<% } %>
				<% _.each(surveys,function(survey , i){ %>
				<div class='content-box-small' type='SURVEY' template-id='<%= survey.form_id %>'>
					<img class='add-image' src='<%= survey.preview_url %>' alt='<%= survey.form_name %>' />
				</div>
				<span class='small-content-title'><%= survey.form_name %></span>
				<% }); %>

				<% _.each(images,function(image , i){ %>
				<div class='content-box-small' type='IMAGE'>
					<img class='add-image' src='<%= image.content %>' alt='<%= image.template_name %>' />
				</div>
				<span class='small-content-title'><%= image.template_name %></span>
				<% }); %>
			</div>
			<div id='add-social-media' class='hide arrow_box'>
				<input type='text' name='url' id='url' placeholder=<?= _campaign("enter social url") ?> class='margin-top'/><br/>
				<input type='checkbox' name='set_url' id='set_url' /><?= _campaign(" Save This URL") ?><br/>
				<a class='btn btn-inverse' id='cancel_url'><?= _campaign("Cancel"); ?></a>
				<a class='btn creative-assets-button-active active' id='save_url'><?= _campaign("Add Url"); ?></a>
			</div>
		</div>		
	</div>	
</script>
<script type='text/template' id='template-edit-area'>
	<div class='template-area'>
		<textarea id="edit_template__template" name="edit_template__template" style="visibility: hidden; display: none;"></textarea>
		<iframe id="iedit_template__template" style="width:100%;height:500px;overflow-x:hidden;overflow-y:auto;border:0"></iframe>
		<iframe id="iedit_template__template_holder" style="display:none"></iframe>
	</div>
</script>

<!-- Plain Text Step html template -->
<script type='text/template' id='function-bar'>
		<div class="function-bar">
				<span class="font-color-green hand_cursor"><?= _campaign("Why Plain Text?");?></span>
				<a id="edit_plain_text" class="c-button pull-right margin-right" ><?= _campaign("Edit Plain Text Content"); ?></a>
		</div>
		<div class="function-bar hide" id='function-bar-edit'>
			<span class="font-color-green"><?= _campaign("Why Plain Text?"); ?></span>
			<span class="font-color-green padding-5 hand_cursor
				  pull-right margin-right resetHtml" id=''><?= _campaign("Cancel"); ?></span>
			<span class="font-color-green hand_cursor
				  padding-5 pull-right margin-right resetHtml" id=""><?= _campaign("Reset to HTML Content"); ?></span>
			<a class="c-button
			   pull-right margin-right" id='save_plain_text'><?= _campaign("Save"); ?></a>
		</div>
<textarea id="plain_text__plain_text" name="plain_text__plain_text" readonly="readonly"
		  style="width:99%;height:480px"></textarea>
<input type="hidden" id="plain_text__hidden_plain_text" name="plain_text__hidden_plain_text" value="">
</script>

<!-- Campaign messages step 4 delivery instruction template html-->
<script type='text/template' id='delivery_script'>
    <div class="recipient-container margin-left margin-top-double">
        <span class="title"><i class="intouch-green-tick-active margin-right"></i><?= _campaign("Recipients")?> </span>
            <div><span class="margin-left-double">
                    <% var test = ''
                       var control = ''
                    %>
                    <% 
                    if( !_.isUndefined( msg_data.test ) ) {
                                    test = "<b><?= _campaign('Test');?>:</b>" + msg_data.test
                                    control = "<b><?= _campaign('Control') ?>:</b>" + msg_data.control
                    } %>
                     <%= msg_data.group_label %> <b><?= _campaign('Total')?> :</b> <%= msg_data.count %>
                     <% if( _.isUndefined( is_sms ) ){ %>
                            <b><?= _campaign("Emails")?> :</b> <%= msg_data.email %> <%= test %>
                    <% }else{ %>

                            <% if(typeof is_wechat != 'undefined') { %>
                                <b><?= _campaign("WeChat OpenIds") ?>:</b> <%= msg_data.params["wechat_"+account_id] ? msg_data.params["wechat_"+account_id] : ( msg_data.params["wechat_0"] ? msg_data.params["wechat_0"] : 0 ) %> <%= control %>
                              <% } else if(typeof is_mobilepush != 'undefined'){ %>
                                <b><?= _campaign("Android") ?>:</b> <%= 30%> 
                                <b><?= _campaign("IOS") ?>:</b><%= 40 %>

                            <% }else{ %>
                                <b><?= _campaign("Mobile Numbers") ?>:</b> <%=msg_data.mobile %> <%= control %>
                            <% } %>
                    <% } %>
                    <% if(typeof is_mobilepush == 'undefined'){ %><span class="plus-icon"><?= _campaign("show more");?></span><% } %>
                  </span>
                  <div class="rec-more-cont"></div>
                  <div class="font-color-green margin-left-double">
                  <span id="delivery_group" class="hand_cursor" group_id="<%=msg_data.group_id %>" campaign_id="<%=campaign_id%>"><?= _campaign("Download") ?></span></div>
            </div>
        </span>
    </div>
    <% if( _.isUndefined( is_sms ) ) { %>
        <div class="template-container margin-left margin-top-double">
            <span class="title"><i class="intouch-green-tick-active margin-right"></i><?=_campaign("Design") ?></span>
            <span class="hand_cursor font-color-green margin-left-double" id="design_test"><?= _campaign("Test Primary");?></span>
            <span class="hand_cursor font-color-green margin-left" id="design_preview"><?= _campaign("Preview");?></span>
        </div>
        <div class="plain-text-container margin-left margin-top-double">
            <span class="title"><i class="intouch-green-tick-active margin-right"></i> <?= _campaign("Plain Text Version");?></span>
            <span class="hand_cursor font-color-green margin-left-double" id="plaintext_test"><?= _campaign("Test Primary");?></span>
            <span class="hand_cursor font-color-green margin-left" id="plaintext_preview"><?= _campaign("Preview");?></span>
        </div>
    <% }else{ %>
        <% if(typeof is_wechat == 'undefined') { 
        	if(_.isUndefined('is_mobilepush')){%>
            <div class="template-container margin-left margin-top-double">
                <span class="title"><i class="intouch-green-tick-active margin-right"></i> <?= _campaign("Content"); ?></span>
                <div class='margin-left-double' id='sms_content'><%= sms_content %></div>
                <span class="hand_cursor font-color-green margin-left-double" id="test_and_preview"><?= _campaign("Preview and Test");?></span>
            </div>
        <% } } %>
    <% } %>
    <div class="coupon-container margin-left margin-top-double">
        <span class="title">
        <% if( series.voucher_series_id > 0 ) {%>
            <i class="intouch-green-tick-active margin-right"></i> <?= _campaign("Coupon Series"); ?>
        <% }else{ %>
            <i class="intouch-red-cross margin-right"></i><?= _campaign("Coupon Series"); ?>
        <% } %>
        </span>
        <span class="margin-left-double"><%= series.description %></span>
        <span class="margin-left-double color-gray"><%= series.discount %></span>
    </div>
    <% if(typeof check_list != 'undefined') { %>
    <div class="checklist-container margin-left margin-top-double">
        <span class="title">
        <% if( check_list.valid == 1 ) {%>
            <i class="intouch-green-tick-active margin-right"></i> <?= _campaign("CheckList");?></span>
        <% } else { %>
            <i class="intouch-red-cross margin-right"></i> <?= _campaign("CheckList");?></span>
        <% } %>
        <span class="margin-left-double">
            <%= check_list.list %>
        </span>
    </div>
    <% } %>
    <div class="checklist-container margin-left margin-top-double">
        <span class="title"><i class="intouch-green-tick-active margin-right"></i><?= _campaign("Sender Information") ?> </span>
        <% if(typeof is_wechat != 'undefined') { %>
        <div class='sender-info-delivery'>
            <span class='sender-from margin-left-double'> <?= _campaign('Account Name') ?> : <%=sender_details.account_name%> </span>
        </div>
        <% } else if(typeof is_mobilepush != 'undefined'){ %>
        	<div class='sender-info-delivery'>
            <span class='sender-from margin-left-double'> <?= _campaign('Recipient  App Name') ?> : <%=sender_details.account_name %> </span>
        </div>
        <% } else { %>
        <% if( _.isUndefined( is_sms )) { %>
            <span class='sender-from margin-left-double'></span>
            <span class='sender-email margin-left'></span>
        <% } else { %>
                <div class='sender-info-delivery'>
                    <span class='sender-from margin-left-double'> <?= _campaign('From') ?> : <%=label%> </span>
                    <span class='sender-email margin-left'>  <?= _campaign('Mobile') ?> : <%=mobile%> </span>
                    <i class='icon-pencil font-color-green hand_cursor'></i>
                </div>
                <div class='sender-info-delivery hide'>
                    <?= _campaign("Name :")?> <input type='text' name='from' id='from' value='<%= label %>' class='margin-1p'/>
                    <?= _campaign("Mobile : "); ?><input type='text' name='mobile' id='mobile' value='<%= mobile %>' class='margin-1p' />
                    <a class='save-sender c-button'><?= _campaign("Save"); ?></a><a class='c-button-grey cancel-sender margin-left'><?= _campaign("Cancel");?></a>
                </div>
        <% } } %>
    </div>
    <% if(typeof is_wechat == 'undefined') { %>
    <div class="checklist-container margin-left margin-top-double">
            <span class="title"><i class="intouch-green-tick-active margin-right"></i> <?= _campaign("Store tag type?");?></span>
            <input type='radio' checked="checked" name='store_tag' id='store_tag'
                   value='registered_store' class='margin-left-double'><?= _campaign("Registered at"); ?>
            <input type='radio' name='store_tag' id='store_tag' value='last_shopped_at'><?=_campaign("Last Shopped"); ?>
    </div>

    <% if(typeof is_mobilepush == 'undefined'){
    			if( !(_.isUndefined( is_sms ))){ %>
        <div class="checklist-container margin-left margin-top-double">
                <span class="title">
                <% if( approx_credits_count <= org_credits ) {%>
                        <i class="intouch-green-tick-active margin-right"></i> <?= _campaign("Credits");?></span>
                <% } else { %>
                        <i class="intouch-red-cross margin-right"></i> <?= _campaign("Credits");?></span>
                        <span class="margin-left-double">
                                <?= _campaign("Not enough credits");?>
                        </span><br/>

                <% } %>

                <span class="margin-left-double">
                        <?= _campaign("Available");?> : <%= org_credits %>
                </span><br/>
                <span class="margin-left-double">
                        <?= _campaign("Max Required");?> : <%= approx_credits_count  %>
                </span><br/>
        </div>
    <% } %>
    
    <% } } %>
    <div class="checklist-container margin-left margin-top-double">
            <span class="title"><i class="icon-time margin-right color-time"></i><?= _campaign("Schedule")?> </span>
            <div class="margin-left-double">
            <select id="send_when" name="send_when">
                    <option value="IMMEDIATE"><?= _campaign("Immediately");?></option>
                    <option value="PARTICULAR_DATE"><?= _campaign("On a fixed date");?></option>
                    <option value="SCHEDULE"><?= _campaign("Recurring");?></option>
            </select>
            <div id="div_date_time" style="display:none">
                <label style="display:inherit"><?= _campaign("Choose Date");?></label>
                <input type="text" id="date_time" name="date_time" value="" readonly="">
                <img id="trigger__date_time" class="new_calender_img" src="/images/calendar-icon.gif">
            </div>
            <div id='div_schedule' style="display:none">
                <select id='cron_day' name='cron_day' multiple='multiple' >
                    <option value="*"><?= _campaign("All");?></option>
                    <option value="l"><?= _campaign("Last Day of Month");?></option>
                    <% _.each(_.range(1,32),function(i){ %>
                        <% if( i < 10 ) {%>
                            <option value='<%= i %>'>0<%= i %></option>
                        <% }else{ %>
                            <option value='<%= i %>'><%= i %></option>
                        <% } %>
                    <% }); %>
                </select>
                <select id='cron_week' name='cron_week' multiple='multiple' >
                    <% _.each(input.week,function(week,i){ %>
                        <option value='<%= week %>'><%= i %> </option>
                    <% }); %>
               </select>
               <select id='cron_month' name='cron_month' multiple='multiple' >
                    <% _.each(input.month,function(month,i){ %>
                        <option value='<%= month %>'><%= i %> </option>
                    <% }); %>
               </select>
               <div class='margin-top'>
                   <select id='cron_hours'>
                        <option value="HH"><?= _campaign("HH") ?></option>
                        <% _.each(_.range(0,24),function(i){ %>
                            <% if( i < 10 ) {%>
                                <option value='0<%= i %>'>0<%= i %></option>
                            <% }else{ %>
                                <option value='<%= i %>'><%= i %></option>
                            <% } %>
                        <% }); %>
                    </select>
                    <select id="cron_minutes" name="cron_minutes"><option value="mins"><?= _campaign("mins") ?></option><option value="00"> 00 </option><option value="30"> 30 </option></select>
                </div>
            </div>
            <div id="div_max_users" style="display:none">
                <label style="display:inherit"><?= _campaign("Maximum customers limit"); ?></label>
                <input type="text" id="max_users" name="max_users" value="5000">
            </div>
    </div>
    <div class="margin-left-double margin-top">
        <a class="c-button
                  active" id='queue_message'><?= _campaign("Schedule Campaign Message"); ?></a>
    </div>
    <% if( is_sms ){ %>
    <div>
        <div class="modal fade" id="sender_info_modal" tabindex="-1" role="dialog" aria-labelledby="sender_info_modal">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel"><?= _campaign("Custom Sender") ?></h4>
                    </div>
                    <div class="modal-body">
                        <%if(sender_details.length>1){%>
                            <div> <?= _campaign('From') ?>:
                                <select id="ou_sender_from">
                                    <%_.each(sender_details,function(val, i){%>
                                    <option value="<%=i%>" <%if(val.sender_gsm==label && val.sender_cdma==mobile){%>selected="true" <%}%> ><%=val.sender_gsm%> ( <%=val.sender_cdma%> )</option>
                                    <%})%>
                                </select>
                            </div>
                        <%}else {%>
                            <div><?= _campaign('From') ?>: <input id="ou_sender_from" type="text" value = "<%=label%>"/> </div>
                            <div><?= _campaign('Mobile') ?> <input id="ou_sender_mobile" type="text" value = "<%=mobile%>"/> </div>
                        <%}%>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Close")?></button>
                        <button type="button" class="btn btn-primary" data-dismiss="modal" id="save_sender_details"><?= _campaign("Save")?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <%}%>
</script>

<script type='text/template' id='sms_tag_left_panel'>
	 <div class='sms-right-panel'>
	 	<div class='' style='width:100%;height:540px;'>
	 		<textarea id='sms_text_box' name='sms_text_box'></textarea>
	 	</div>
	 	<div class='center char_counter margin-top' style='width:100%;font-size:20px;color:#505050'>
	 		<?= _campaign("0 character 0 Message"); ?>*
	 	</div>
	 </div>
</script>
<script type='text/template' id='call_task_subject_bar'>
	<div class="subject-bar" style='border-bottom:1px solid #b7b7b7'>
			<label class="margin-left margin-right font-bold"><?= _campaign("Description"); ?></label>
			<% if( !_.isUndefined( default_data )) { %>
					<input type="text" id="call_task_message__description" name="edit_template__description"
				   		   value="<%= default_data.description %>" class="margin-left"><br/>
					<label class="margin-left margin-right font-bold"><?= _campaign("Subject"); ?></label>
					<input type="text" id="call_task_message__subject" name="edit_template__subject"
						   value="<%= default_data.subject %>" class="margin-left-double">
			   <% }else{%>
			   		<input type="text" id="call_task_message__description" name="edit_template__description"
					       value="" class="margin-left"><br/>
					<label class="margin-left margin-right font-bold"><?= _campaign("Subject"); ?></label>
					<input type="text" id="call_task_message__subject" name="edit_template__subject"
						   value="" class="margin-left-double">
				<% } %>
			<input type='hidden' name='current_position'
				   id='current_position' value='0' last_focused='' />
		</div>
</script>
<script type="text/template" id="call_task_left_panel">
	<div class='custom-tag-bar margin-left pull-left call_task_panel'>
		<div class="template-function margin-top">
			<span id="tags" class="active"><?= _campaign("TAGS"); ?></span>
		</div>
		<div class='template-function template-function-tags'>
			<div class='template-options' id='custom-tags'>
				<%= tags %>
			</div>
		</div>
	</div>
	<div class='template-area call_task_panel'>
		<textarea class='margin-left call_task_template'
				 id='call_task_message__message' name='call_task_message__message'><%= content %></textarea>
	</div>
</script>

<script type='text/template' id='call_task_delivery'>
	<div class="recipient-container margin-left margin-top-double">
		<span class="title"><i class="intouch-green-tick-active margin-right"></i><?= _campaign("Recipients"); ?> </span>
			<div><span class="margin-left-double">
					 <%= msg_data.group_label %> <?= _campaign('Total')?> : <%= msg_data.count %>
					<?= _camp1('Mobile')." "._campaign('Test')?> : <%= msg_data.mobile %> <?=_campaign('Control')?>: <%= msg_data.control %></span>
					<span class="plus-icon"><?= _campaign("show more")?></span>
					</span>
				<div class="rec-more-cont"></div>
			</div>
			<div class="font-color-green margin-left-double">
                  <span id="delivery_group" class="hand_cursor" group_id="<%=msg_data.group_id %>" campaign_id="<%=campaign_id%>"><?= _campaign("Download");?></span></div>
            <div class="margin-left-double"><?= _campaign("Note: Customer count displayed above is the actual targeted audience."); ?></div>
		</span>
	</div>
	<div class="template-container margin-left margin-top-double">
			<span class="title"><i class="intouch-green-tick-active margin-right"></i><?= _campaign("Content") ?></span>
			<span class='margin-left-double' id='sms_content'><%= sms_content %></span><br/>
			<span class="hand_cursor font-color-green margin-left-double" id="test_and_preview"><?= _campaign("Preview") ?></span>
	</div>
	<div class="coupon-container margin-left margin-top-double">
		<span class="title"><i class="intouch-green-tick-active margin-right"></i> <?= _campaign("Coupon Series"); ?></span>
		<span class="margin-left-double">
				<%= series.description %>
		</span>
	</div>
	<div class="status-container margin-left margin-top-double">
		<span class="title"><i class="intouch-green-tick-active margin-right"></i> <?= _campaign("Allowed Statuses"); ?></span>
		<% var option = '' %>
		<ul class='l-h-list margin-left-double allowed_statuses'>
			<% _.each(stauses,function(key,value){ %>
				<% if( !_.isUndefined( default_data ) ) { %>
					<% if( _.indexOf( default_data.allowed_status,key ) != -1 ) { %>
						  <li class='item' value='<%= key %>'>
						  	<i class='intouch-green-tick-sqaure status_check intouch-green-tick-active' val='<%= value %>'></i> <%= value %>
						  </li>
					<% }else{ %>
						<li class='item' value='<%= key %>'>
							<i class='intouch-green-tick-sqaure status_check' val='<%= value %>'></i> <%= value %>
						</li>
					<% } %>
				<% }else{ %>
					<li class='item' value='<%= key %>'>
						<i class='intouch-green-tick-sqaure status_check' val='<%= value %>'></i> <%= value %>
					</li>
				<% } %>
			<% }); %>
		</ul>

		<span style='margin-left:3.2%'><?= _campaign("Default Status"); ?></span>
		<select class='margin-top margin-left-double' id='default_status' name='default_status'>
			<option value='-1'><?= _campaign('Please select status')?></option>
			<% _.each(stauses,function(key,value){ %>
				<% if( !_.isUndefined( default_data ) ) { %>
					<% if( _.indexOf( default_data.allowed_status,key ) != -1 ) { %>
						<option class='status_<%= value %>' value='<%= key %>'><%= value %></option>
					<% }else{ %>
						<option class='status_<%= value %> hide' value='<%= key %>'><%= value %></option>
					<% } %>
				<% }else{ %>
					<option class='status_<%= value %> hide' value='<%= key %>'><%= value %></option>
			   	<% } %>
			<% }); %>
		</select><br/>
		<span style='margin-left:3.2%'><?= _campaign("Validity (in days)") ?>*</span>
		<input class='margin-left' type='text' name='validity_for_task_entry' id='validity_for_task_entry' />
	</div>
	<div class="checklist-container margin-left margin-top-double">
			<span class="title"><i class="icon-time margin-right color-time"></i> <?= _campaign("Schedule") ?></span>
			<div class="margin-left-double">
			<select id="send_when" name="send_when">
					<option value="IMMEDIATE"> <?= _campaign("Immediately"); ?></option>
					<option value="PARTICULAR_DATE"> <?= _campaign("On a fixed date"); ?></option>
					<option value="SCHEDULE"><?= _campaign("Recurring"); ?></option>
			</select>
			<div id="div_date_time" style="display:none">
				<label style="display:inherit"><?= _campaign("Choose Date"); ?></label>
				<input type="text" id="date_time" name="date_time" value="" readonly="">
				<img id="trigger__date_time" class="new_calender_img" src="/images/calendar-icon.gif">
			</div>
			<div id='div_schedule' style="display:none">
				<select id='cron_day' name='cron_day' multiple='multiple' >
					<option value="*"><?= _campaign("All"); ?></option>
					<option value="l"><?= _campaign("Last Day of Month"); ?></option>
					<% _.each(_.range(1,32),function(i){ %>
						<% if( i < 10 ) {%>
							<option value='<%= i %>'>0<%= i %></option>
						<% }else{ %>
							<option value='<%= i %>'><%= i %></option>
						<% } %>
					<% }); %>
				</select>
				<select id='cron_week' name='cron_week' multiple='multiple' >
					<% _.each(input.week,function(week,i){ %>
						<option value='<%= week %>'><%= i %> </option>
					<% }); %>
			   </select>
			   <select id='cron_month' name='cron_month' multiple='multiple' >
					<% _.each(input.month,function(month,i){ %>
						<option value='<%= month %>'><%= i %> </option>
					<% }); %>
			   </select>
			</div>
	</div>
	<div class="margin-left-double margin-top">
		<a class="c-button active" id='queue_message'><?= _campaign("Schedule Campaign Message");?></a>
	</div>
	<div class='validity-help hide'><?= _campaign("Task Entry Valid Days must be a positive Integer."); ?></div>
</script>

<script type='text/template' id='sms-preview-modal'>
		<div class="modal fade hide" id="<%= modal %>" style='overflow:hidden'>
		  <div class="modal-dialog">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		        <h4 class="modal-title"><%= title %></h4>
		      </div>
		      <div class="modal-body <%= modal %>">
		      </div>
		      <div class="modal-footer">
		        <button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Close"); ?></button>
		      </div>
		    </div>
		  </div>
		</div>
</script>

<script type='text/template' id='call_task_preview'>
	<div class="modal fade hide" id="<%= modal %>" style='overflow:hidden'>
		  <div class="modal-dialog">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		        <h5 style='margin-top:0px;'><?= _campaign("Preview"); ?></h5>
		      </div>
		      <div class="modal-body <%= modal %>">
					<div class='preview-box'>
						<div class='call-task-header'><span><?= _campaign("tasks"); ?></span><img src='/images/webclient/icoCloseWhite.png' class='pull-right'/></div>
						<div style='width:33%;height:405px;border-right:1px solid #336666;float:left'>
							<div class='left-side-details'>
							</div>
							<ul class='l-v-list customr-list-left'>
								<li class='item background-loader'></li>
							</ul>
						</div>
						<div id="content_container">
							<div id="content_title"><?= _campaign("customer details"); ?></div>
			                    <div id="content_block">
			                        <table border="0">
			                            <tr><td class="key" style="width: 1px; min-width: 1px;"><?= _campaign("Name");?></td><td class='key-name'>: <?= _campaign("Not Available"); ?></td></tr>
			                            <tr><td class="key"><?= _campaign('Mobile') ?></td><td class='key-mobile'>: <?= _campaign("Not Available"); ?></td></tr>
			                            <tr><td class="key"><?= _campaign('Email Id') ?></td><td class='key-email'>: <?= _campaign("Not Available"); ?></td></tr>
			                        </table>
			                    </div>
			                    <div id="content_block">
			                        <table border="0">
			                            <tr><td class="key"><?= _campaign('Birthday') ?></td><td>: <?= _campaign("Not Available"); ?></td></tr>
			                            <tr><td class="key"><?= _campaign('Gender') ?></td><td>: <?= _campaign("Not Available"); ?></td></tr>
			                            <tr><td class="key"><?= _campaign('Address') ?></td><td>: <?= _campaign("Not Available"); ?></td></tr>
			                        </table>
			                    </div>
										<div id="content_title"><?= _campaign("task details"); ?></div>
			                    <div id="content_block">
			                        <table border="0">
			                            <tr><td class="key"><?= _campaign('Task Id'); ?></td><td>: <?= _campaign('XXX'); ?></td></tr>
			                            <tr><td class="key"><?= _campaign('Created By'); ?></td><td class='key-created'>: <?= _campaign("Not Available"); ?></td></tr>
			                            <tr><td class="key"><?= _campaign('Assigned To'); ?></td><td class='key-assigned'>: <?= _campaign("Not Available"); ?></td></tr>
			                        </table>
			                    </div>
			                      <div id="content_block">
									<table border="0">
										<tr><td class="key"><?= _campaign("Status"); ?></td><td class='key-status'>: <?= _campaign("OPEN");?></td></tr>
										<tr><td class="key"><?= _campaign("End Date"); ?></td><td class='key-enddate'>: <?= _campaign("Not Available"); ?></td></tr>
										<tr><td class="key"><?= _campaign("Expiry Date"); ?></td><td class='key-expire'>: <?= _campaign("Not Available"); ?></td></tr>
									</table>
								</div>
								<div id="content_block" style="width: 99%;">
									<div style="width: 100%; background: #eee; border: solid 1px #444; margin-top: 15px;padding: 0px;">
										<p id='task-subject' class='task-subject'></p>
										<div style="background: #666; height: 1px; width: 100%;"></div>
										<p id='task-message' class='task-message'></p>
									</div>
								</div>
							</div>
						</div>
						<div class='call-task-header call-task-footer'>
						</div>
					</div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Close"); ?></button>
			      </div>
		    </div>
		  </div>
		</div>
</script>

<script type='text' id='render-responsive-template-list'>
	<div id="responsive_creative_template">
		<div style='height:60px;'>
			<h3 class="margin-bottom pull-left <%= hide.ul_class %>"><%= hide.template_title %></h3>
		</div>
		<div class='<%= hide.ul_class %>'>
		<ul class='l-h-list'>
			<% _.each(template,function(temp,i){ %>
				<% if( i > 6 ) { %>
					<li class='margin-top template-list_<%= hide.ul_class %> item <%= hide.hide %>'
						template_name='<%= temp.template_name%>'>
				<% }else{ %>
						<li class='margin-top template-list_<%= hide.ul_class %> item search_enabled'
						template_name='<%= temp.template_name%>'>
				<% } %>
					<ul class="l-v-list">
						<li class="item template-name" rel='tooltip' title='<%= temp.template_name %>'>
							<%= temp.template_name %>
						</li>
						<li class="item image_preview">
							<% if( _.isUndefined( is_sms ) ) { %>
								<% if( temp.is_preview_generated == 1 ){ %>
									<img src="<%=temp.content%>" alt="<%=temp.template_name%>">
								<% }else{ %>
									<div class="tmp_preview"><?= _campaign("Preview is being generated."); ?></div>
								<% } %>
							<% }else{ %>
								<div><%= temp.content %></div>
							<% } %>
							<i class="c-mobile-template-icons c-mobile-template"></i>
						</li>
						<li style='margin-bottom:10px;'>
							<a class="c-button-grey template_selected" id="select_template"
								context="<%= hide.ul_class %>" template_id="<%= temp.template_id %>" data-animation="1" data-goto="4"><?= _campaign("Select");?></a>
							<a class="link font-color-green margin-left"
							   id="show_template_preview" context="<%= hide.ul_class %>" template_id="<%= temp.template_id %>"><?= _campaign("Preview"); ?></a>
						</li>
					</ul>
				</li>
			<% }); %>
		</ul>
		 <% if( _.size( template ) > 6 ) { %>
			 <div class="center margin-top <%= hide.ul_class %>">
				<a class="font-color-green font-bold" id="show_more_template_<%= hide.ul_class %>"><?= _campaign("Show More Templates") ?></a>
			</div>
		<% } %>
		</div>
	</div>
</script>
<input type="hidden" id="msg_timestamp" name="msg_timestamp"></input>


<script type='text/template' id='coupon_series_step'>
	<div class='camp-content'>
	<div>
		<div class='msg-coupon'>
			<i id='attach-incentive' class='attach-incentive intouch-green-tick-active'/>
			<span ><?=_campaign("Communication only - No deal or incentives"); ?></span>
		</div>

		<div class="coupon_div">
			<i id='attach-coupons' class='attach-incentive'/>
			 <?= _campaign("Personalized Coupon") ?> 
		</div>
		
		<div class="points_div">
			<i style="float:left;margin-right:0px" id='attach-points' class='attach-incentive'/>
			<span style="margin-left:15px" class="span_msg"><?= _campaign("Loyalty Points"); ?></span>
			<span style="margin-left:15px" class="err_span_msg hide"><?= _campaign("Points") ?> - <%= camp_details.points_info.status.msg%> </span>
		</div>

		<div class="generic_div">
			<i style="float:left;margin-right:0px" id='attach-generic' class='attach-incentive'/>
			<span style="margin-left:15px" class="span_msg"><?= _campaign("Generic deal"); ?></span>
		</div>

		<div class="generic_details hide" id="generic_details">
			
		</div>

		<div class="coupon_details hide" id="coupon_details" coupon-series-id="<%= camp_details.voucher_series_id %>">
			<div class="c-but-cont-show hide">
				<span class='margin-right'></span>
				<a class="c-button-grey" id="show_coupon_button">
				<i class="icon-arrow-left"></i> <?= _campaign("Show All Coupon Series"); ?>
				</a>
			</div>
			<div id="coupons_btn_bar" class="">
				<div class="c-title" id="a_c_series"><?= _campaign("Coupon Incentive"); ?></div>
				<div class="c-title hide" id="c_c_series"><?= _campaign("Configure Coupon Series"); ?></div>
				<% if( camp_details.no_coupon_series < 1 ) {%>
				<div class="c-but-cont">
	   				<span class='margin-right'></span>
					<a class="c-button-grey" id="create_coupon_series">
					+ <?= _campaign("Create Coupon Series"); ?>
					</a>
				</div>
				<%}%>
			</div>
			<div class="clearfix"></div>
			<div class="no-c-msg hide"><?= _campaign("No coupon series created for this campaign"); ?></div>
			<div id="configure_coupon" class="hide show-edit-coupon flexcroll"></div>
			<div class="series-container" style='clear:both;padding-top:10px;font-style:italic' id="coupon-series-container">
				<% if( camp_details.no_coupon_series > 0 ) {%>
				<div class="row-fluid c-new-details coupon_details">
					<div class="coupon-sel-radio"><i class="intouch-green-tick" series-id="<%=camp_details.voucher_series_id%>" id="coupon_tick"></i>
					</div>
					<div class="span4">
						<div class="c-new-info">
							<span class="c-new-label"><?= _campaign("Coupon Series:"); ?></span>
							<span><%= camp_details.description %></span>
						</div>
						<div class="c-new-info">
							<span class="c-new-label"><?= _campaign("Coupon Code:") ?></span>
							<span><%= camp_details.code %></span>
						</div>
						<div class="c-new-info">
							<span class="c-new-label"><?= _campaign("Discount:"); ?></span>
							<span><%= camp_details.discount %></span>
						</div>
						<% if(camp_details.voucher_validity.valid){%>
						<div class="c-new-info" id="c_product_details">
						<%}else{%>
						<div class="c-new-info hide" id="c_product_details">
						<%}%>
						<span class="c-new-label"><?= _campaign("Valid Brands and Categories:  "); ?></span>
						<span class="view-coupon-validity"><a id="view_coupon_validity" class="c-v-view-details" v_s_id="<%=camp_details.voucher_series_id %>"  ><?= _campaign("View"); ?></a></span>
						</div>
						<input type="hidden" id="coupon_validity_val" name="coupon_validity_val" value="<% if(camp_details.voucher_validity.valid){%>Custom<%}else{%>All<%}%>" />
					</div>
					<div class="span4">
						<div class="c-new-info">
							<span class="c-new-label"><?= _campaign("Redeemed:"); ?></span>
							<span><%= camp_details.num_redeemed %></span>
						</div>
						<div class="c-new-info">
							<span class="c-new-label"><?= _campaign("Issued:"); ?></span>
							<span><%= camp_details.num_issued %></span>
						</div>
					</div>
					<div class="span3">
						<div class="btn-group">
			  				<button type="button" class="btn btn-default dropdown-toggle c-coupon-button" data-toggle="dropdown"><?= _campaign("Options ") ?><span class="caret pull-right"></span> </button>
			  				<ul class="dropdown-menu" role="menu">
			    				<li><a class="open_coupon_details" coupon-id="<%=camp_details.id%>" id="cnew_details" data-toggle="modal" data-target="#coupon_series_modal" ><i class="icon-file"></i><?= _campaign("View") ?></a></li>
							    <li><a class="coupon_edit" coupon-id="<%=camp_details.id%>" id="coupon_edit"><i class="icon-pencil"></i> <?=_campaign("Edit"); ?></a></li>
							    <li><a onclick="showPopup( '/campaign/coupons/VouchersUpload?flash=&amp;coupon_series_id=<%= camp_details.voucher_series_id %>' );"><i class="icon-upload-alt"></i> <?= _campaign("Upload Coupons"); ?> </a></li>
							    <li><a onclick="showPopup( '/campaign/coupons/Listener?flash=&amp;campaign_id=<%= camp_details.id %>' );"><i class="icon-cogs"></i> <?= _campaign("Define Redemption Actions"); ?></a></li>
			  				</ul>
						</div>
					</div>
			
					<% }else{ %>
					<div style='clear:both;padding: 10px 0 10px 0'><?= _campaign("No Coupon series created for this campaign"); ?></div>
					<% } %>
				</div>
				<div class="clearfix"></div>
				<div class="modal fade view-coupon-details" tabindex='-1' role='dialog' id="coupon_series_modal" style="display:none;">
				  	<div class="modal-dialog">
		    			<div class="modal-content">
			    			<div class="modal-header">
			        			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			        			<h3 class="modal-title series-title" id="series_modal_title"></h3>
			      			</div>
			      			<div class="modal-body cnew-body">
			        			<table id='coupon_details_table' cellpadding='0' cellspacing='0' border='0' width='100%' class='cnew-table'>
									<thead></thead>
									<tbody id="series_modal_body"></tbody>
								</table>
			      			</div>
		     			</div>
		  			</div>
				</div>
				<div class="modal fade c-v-view-modal" tabindex='-1' role='dialog' id="view_coupon_validity_modal">
				  	<div class="modal-dialog">
				    	<div class="modal-content">
				      		<div class="modal-header">
				        		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				        		<h3 class="modal-title"><?= _campaign("Valid Brands and Categories"); ?></h3>
				      		</div>
				      		<div class="modal-body cnew-body">
				      			<div id="coupon_product_details">
				      			</div>
				      		</div>
				     	</div>
				  	</div>
				</div>
			</div>
			<div class="new-c-create" id="coupon_new_create" style="display:none;">
				<div class="new-c-content coupon">
		       		<div class="c-title"><?= _campaign("New Coupon Series"); ?></div>
	        		<form method="post" action="" id="new_coupon_series" name="new_coupon_series">
				  	  	<div class="form-controls">
				    		<label for="coupon_tag"><?= _campaign("Coupon Series Tag"); ?></label>
							<div class="input-container">
						  		<input type="text" id="info" name="info" class="input-nsl validate[required;regexcheck</^(?=.*[a-zA-Z]).{1,100}$/>;custom_val<<?= _campaign('Tag should be less than 100 characters and must have atleast one letter') ?> >]">
							</div>
					  	</div>
					  	<div class="form-controls">
				    		<label for="discount_on"><?= _campaign("Offer On"); ?></label>
							<div class="input-container">
								<select id="discount_on" name="discount_on">
								<option value="BILL" selected="selected"><?= _campaign("BILL VALUE"); ?></option>
						  		<option value="ITEM"> <?= _campaign("ITEM"); ?></option>
						  		</select>
							</div>
					  	</div>
					  	<div class="form-controls">
				    		<label for="discount_of"><?= _campaign("Discount"); ?></label>
							<div class="input-container">
							  <input type="text" id="discount_value" name="discount_value"
							    class="discount-input validate[required;regexcheck</^\d*\.?\d*$/>;custom_val<<?= _campaign('Discount should be positive value') ?>>]">
								<select id="discount_type" name="discount_type" class="discount-input">
						  			<option value="ABS"> <%= currency %> </option>
						  			<option value="PERC"> % </option>
						  		</select>
							</div>
					  	</div>
					  	<div class="form-controls" >
						<i id="specify_num_cpn" style="float:left;margin-left:10%;margin-right:5px" class=" num_cpn_radio intouch-green-tick intouch-green-tick-active"></i><label for="num_coupons"><?= _campaign('Limit the number of coupons to be issued to') ?> </label>
						<div class="input-container">
							<input type="text" id="num_coupons" name="num_coupons_specified"/>
						</div>
						<div style="float:left;margin-left:4%;min-width:130px">
						<i id="default_num_cpn" style="float:left;margin-right:5px" class="num_cpn_radio intouch-green-tick "></i><span style="text-align:left"><?= _campaign('Do not limit') ?> </span>
						</div>
					</div>	
					  	<div class="form-controls">
					  		<label></label>
					  		<div class="input-container">
					  	  	<div class="text-info">*<?= _campaign("Advanced settings are available after saving"); ?></div>
					  		</div>
					  	</div>
				  	  	<div class="form-controls">
				    		<label></label>
				    		<div class="input-container">
				    			<input type="hidden" name="max_create" id="max_create" value="-1">	
				    			<input type="hidden" name="coupon_org_id" id="coupon_org_id" value="-1">
				      			<input type="button" id="createNewSeries" class="bttn-submit" value="<?= _campaign('Attach Coupon Series') ?>">
				      			<input type="button" value="<?= _campaign('Cancel') ?>" class="cancel-btn" id="coupon_cancel">
				    		</div>
						</div>
					</form>
				</div>
			</div>
		</div>

		<div class="points_details hide" id="points_details" program-id="<%= camp_details.points_info.program_id %>">
			<div class="triangle hide"></div>
			<div class="pop_up hide" id="alloc_pop_up">
				<% _.each(camp_details.points_info.allocation_strategy,function(post,i) { %>
					<div div-id="<%= i %>" class="hide alloc_cmn alloc_row__<%= i %>">
						<ul style="list-style-type:none;margin:0px">
							<% _.each(post.property_values,function(prop,j) { %>
								<li><%= camp_details.points_info.slabs[j] %> <%= prop %> <?= _campaign('points') ?></li>
							<% }); %>	
						</ul>		
					</div>
				<% }); %>		
			</div>

			<div class="pop_up hide" id="exit_pop_up">
				
				<% _.each(camp_details.points_info.exit_strategy,function(post,i) { %>
					<div div-id="<%= i %>" class="hide exit_cmn exit_row__<%= i %>">
						<ul style="list-style-type:none;margin:0px">
							<% _.each(post.property_values,function(prop,j) { %>
								<li><%= camp_details.points_info.slabs[j] %> <%= prop %> </li>
							<% }); %>	
						</ul>		
					</div>
				<% }); %>				
				
			</div>

			<input type="hidden" id="prog_id" program-id="<%= camp_details.points_info.program_id %>"></input>
			<div id="points_data">
				<div id="err_msg" class="hide" >
					<input type="hidden" value="0" id="input_msg" />
				</div>
				<div id="choose_points">
					<p class="points_sub_heading"> <?= _campaign("No Points settings chosen"); ?></p>
					<p class="points_sub_heading"> <?= _campaign("Choose Points Allocation Strategy"); ?></p>
					<div class="p-new-details" id="alloc_points_strategy">
						<ul class="points_list" id="alloc_points_list">
							<% _.each(camp_details.points_info.allocation_strategy,function(post,i) { %>
								<div class="row-fluid">
									<div class="span5">
										<li strat-id="<%= post.id %>"><i class="choose-empty-alloc"></i><%= post.name %></li>
									</div>	
									<div class="span3 " position-id="<%= i %>">
										<span class="alloc_details" style="cursor:pointer" position-id="<%= i %>"><u><?= _campaign("View Details"); ?></u></span>
									</div>	
									<div class="span4">
										<span id="num_msg"></span>
									</div>	
								</div>	
							<% }); %>	
						</ul>
					</div>
					<br/>
					<p class="points_sub_heading"><?= _campaign("Choose Points Expiry Strategy"); ?></p>
				
					<div class="p-new-details" id="exit_points_strategy" >
						<ul class="points_list" id="exit_points_list">
							<% _.each(camp_details.points_info.exit_strategy,function(post,i) { %>
								<div class="row-fluid">
									<div class="span5">
										<li strat-id="<%= post.id %>"><i class="choose-empty-expiry"></i><%= post.name %></li>
									</div>	
									<div class="span3 " position-id="<%= i %>">
										<span class="expiry_details" style="cursor:pointer" position-id="<%= i %>" ><u><?= _campaign("View Details"); ?></u></span>
									</div>	
									<div class="span4">
										<span id="num_msg"></span>
									</div>	
								</div>	
							<% }); %>	
						</ul>
					</div>
					
					<input type="button" class="bttn-submit" value=<?= _campaign('Save') ?> id="points_save"/>
					<input type="button" value=<?= _campaign("Cancel"); ?> class="cancel-btn" id="points_cancel" />

			</div>	

			<div id="points_fixed">
				<p class="points_sub_heading"><?= _campaign("Points Allocation Strategy Used"); ?> </p>
				<div class="p-new-details" id="alloc_points_fixed">
					<ul class="points_list" id="fixed_alloc_list">
						<% _.each(camp_details.points_info.allocation_strategy,function(post,i) { %>
							<div class="row-fluid">
								<div class="span5">
									<li strat-id="<%= post.id %>"><i class="assign-empty-alloc"></i><%= post.name %></li>
								</div>	
								<div class="span3 " position-id="<%= i %>">
									<span class="alloc_details" style="cursor:pointer" position-id="<%= i %>"><u><?= _campaign("View Details"); ?></u></span>
								</div>	
								<div class="span4">
									<span id="num_msg"></span>
								</div>	
							</div>		
						<% }); %>	
					</ul>
				</div>
				<br/>
				<p class="points_sub_heading"><?= _campaign("Points Expiry Strategy Used"); ?></p>
				<div class="p-new-details" id="exit_points_fixed">
					<ul class="points_list" id="fixed_exit_list">
						<% _.each(camp_details.points_info.exit_strategy,function(post,i) { %>
							<div class="row-fluid">
								<div class="span5">
									<li strat-id="<%= post.id %>"><i class="assign-empty-expiry"></i><%= post.name %></li>
								</div>	
								<div class="span3 " position-id="<%= i %>">
									<span class="expiry_details" style="cursor:pointer" position-id="<%= i %>"><u><?= _campaign("View Details"); ?></u></span>
								</div>	
								<div class="span4">
									<span id="num_msg"></span>
								</div>	
							</div>		
						<% }); %>	
					</ul>
				</div>
			</div>

		</div>
	</div>
	</div>
	</div>
</script>

<script type="text/template" id="coupon_product_tpl">
	<div class="c-v-brand-div">	      		
	    <div id='v_s_b_title' class='c-v-selected-title'>
	      <span id='v_s_b_arrow' class='down-arrow'> </span> 
	      <span><?= _campaign("Selected Brands"); ?></span>
	    </div>
	    <div id='v_s_b_desc' class="c-v-brand-desc">
	      <% no_brand=true; _.each(brand,function(a,i){no_brand=false;%>
	        <div>
	          <span class='category-name'> <%=a%></span>
	        </div>
	        <%});
	        if(no_brand){%>
	        <div>
	          <span ><?= _campaign("No Brands selected");?></span>
	        </div>
	        <%}%>
	            
	    </div>
	</div>
    <div class="c-v-category-div">
    	
	    <div id='v_s_c_title' class='c-v-selected-title'>
	      <span id='v_s_c_arrow' class='right-arrow'> </span> 
	      <span><?= _campaign("Selected Categories"); ?></span>
	    </div>
	    <div id='v_s_c_desc' class="c-v-category-desc" style="display:none">
	    	<%no_cat=true;_.each(category,function(a,i){ no_cat = false;%>
	        <div>
	        	<% _.each(a.parents,function(p,i){%>
	            <span> <%=p%> ></span>
	          	<%});%>
	          	<span class='category-name'> <%=a.name%></span>
	         </div>
	        <%});
	        if(no_cat){%>
	        <div>
	        	<span><?= _campaign("No Categories selected"); ?>
	        </div>
	        <%}%>    
	    </div>
	</div>
</script>



