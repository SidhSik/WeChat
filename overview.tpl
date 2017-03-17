<script id="campaign_overview_header_v3" type="text/template">
  <div class="campaigns-container">
		<div class="header-wrapper">
			<div class="home-icon">
				<div class="homeleft">
					<a href="/campaign/index?type=outbound" class="home-btn-icon"><?= _campaign("Home"); ?></a>
				</div>
			</div>
			<div class="head-navigation-content">
			  <ul>
				<li class="first-nav-tab">
				  <a href="#campaign/<%=id%>" class="thanksgivingnav sel" data-id="<%=id%>">
					<span class="heading-thanksgiving" id="heading-campaign-name" 
						data-toggle="tooltip" title=""> </span>
					<span class="caption-thanksgiving"><?= _campaign("Overview"); ?></span>
				  </a>
				</li>
			  	<li class="nav-items c-reports-tab">
				  <a href="#reports/<%=id%>" class="campaignnav" id="view_reports">
					<ul class="l-h-list">
				  	  <li class="reports-img"></li>
				  	  <li class="camp-nav-label"><?= _campaign("Reports"); ?></li>
				  	</ul>
				  </a>
			  	</li>
			  	<li class="nav-items c-coupons-tab">
				  <a href="#view-coupon/<%=id%>" class="campaignnav" id="view_coupons">
				  	<ul class="l-h-list">
				      <li class="coupons-img"></li>
				      <li class="camp-nav-label"><?= _campaign("Incentives"); ?></li>
				    </ul>
				  </a>
			  	</li>
			    <li class="nav-items c-lists-tab">
				  <a href="#recipient/<%=id%>" class="campaignnav" id="view-recipients">
				  	<ul class="l-h-list">
				      <li class="recipient-img"></li>
				      <li class="camp-nav-label"><?= _campaign("Recipient Lists"); ?></li>
					</ul>
				  </a>
			   	</li>
			  </ul>
			</div>
			
			<div class="btn-group createNewMessage" id = "MessageCreationFlow">
					<button type="button" class="btn btn-success dropdown-toggle active" style="background-color:#86b91e!important" data-toggle="dropdown">
    					+ <?= _campaign("New Message "); ?><span class="caret"></span><span class="sr-only"></span>
					</button>
					<ul class="dropdown-menu camp-menu" role="menu">
						<li id = "sms-container"><a href="/campaign/messages/v2/CampaignMessages?campaign_id=<%= id %>"><?= _campaign("SMS"); ?></a></li>
    					<li id = "email-container"><a href="/campaign/messages/v2/Messages?campaign_id=<%= id %>"><?= _campaign("Email"); ?></a></li>
    					<li id="wechat-account-container"><a id = wechat-title><?= _campaign("WeChat"); ?><span id="wechat-caret" class="caret wechat-caret-right caret-right"></span></a></li>
    					<li id="mobilepush-account-container"><a id = "mobilepush-title"><?= _campaign("Mobile Push"); ?><span id="mobilepush-caret" class="caret mobilepush-caret-right caret-right"</span></a></li>
    					<li id = "calltask-container"><a href="/campaign/messages/v2/CallTask?campaign_id=<%= id %>"><?= _campaign("Call Task"); ?></a></li>
					</ul>
				</div>
		</div>
	</div>
</script>
<script id="wechat-account-list" type="text/template">
  <% if( model.weChat_accounts.length > 0 ){ %>
	<ul class="dropdown">
	  <% _.each(model.weChat_accounts,function(v,k) { %>
		<li>
			<a id="wechat_accounts__<%=v.id%>" href="/campaign/messages/v2/WeChat?campaign_id=<%=model.id%>&account_id=<%=v.id%>"><div class = "truncate"><%=v.account_name%></div></a>
		</li>
	  <% }); %>
	</ul>
<% } %>


<script id="mobilepush-account-list" type="text/template">
  <% if( model.mobilepush_accounts.length > 0 ){ %>
	<ul class="mobilepush_dropdown">
	  <% _.each(model.mobilepush_accounts,function(v,k) { %>
		<li>
			<a id="mobilepush_accounts" href="/campaign/messages/v2/MobilePush?campaign_id=<%=model.id%>&account_id=<%=v.id%>"><div class = "truncate"><%=v.account_name%></div></a>
		</li>
	  <% }); %>
	</ul>
<% } %>
</script>
<script id="details-template" type="text/template">
  <div class="margin-heading">
			<span class="auth-note c-list-margin c-font-bolder" style="font-size:16px;"><b><?= _campaign("List Details"); ?></b></span>
			<% var is_sms =1 %>
			<% _.each( group.auth_details, function( item,key ){ %> 
				<% if(key=='<?= _campaign("Sender Email") ?>') {
					is_sms=0;
				}%>
				<% if(key!='<?= _campaign("Sender Email")?>' && key!='<?= _campaign("Sender From")?>' && key!='<?= _campaign("Sender Mobile")?>' && key!='<?= _campaign("Sender Account")?>' && key!='<?= _campaign("Schedule Type")?>'){ %>
					<span class="auth-note"><b><%=key%>:</b> <%=item%></span>
				<%}%>
			<% }); %>
			<% if(group.item_data['isPrevious']==true || group.item_data['isPrevious']==false) {%>

			<% if (group.item_data!=null && group.item_data['isPrevious'] != true && group.message_data['is_list_processed_for_reachability'] != false && group.message_data.type != 'MOBILEPUSH') { %>
			<span class="auth-note"><b><?= _campaign('Total Customers')?>:</b> <%=group.item_data['count']%></span>
			<% if(group.message_data.type=='WECHAT') { %>
					<span class="auth-note"><b><?= _campaign('Target WeChat OpenIds')?>: </b> <%= (_.isObject(group.item_data['wechatOpenIdCount'])?group.item_data['wechatOpenIdCount']['wechat_'+group.message_data.ServiceAccoundId]:0) %></span>
				<% }  else {
			 if( is_sms==0 ){ %>
			<span class="auth-note"><b><?= _campaign('Target Email IDs')?>:</b> <%=group.item_data['reachable_email']%></span>
			<% }else{ %>
				<span class="auth-note"><b><?= _campaign('Target Mobile Nos')?>:</b> <%=group.item_data['reachable_mobile']%></span>
				<% } %>
				<% } }%>
				
		</div>
		<% if (group.item_data['isPrevious'] != true && group.message_data['is_list_processed_for_reachability'] != false) { %>
		<div>
    		<% if( is_sms==0 ){ %>
    			<% 
    			var rr2  = ''
				%>

				<% if(_.indexOf(group.message_data['reachability_rules'],'S')!=-1) {%>
    				<% rr2 = group.item_data['reachable_email'] %>
    			<% }else{ %>
    				<% rr2 = group.item_data['reachable_email'] - group.item_data['soft_bounced_email'] %>
    			<% } %>

				<div class='c-element-details c-div-class'>
					<span class='c-left-text c-font-bolder'><b><?= _campaign('Being contacted (Target Email IDs)')?></b></span> <span class='c-right-text'><%= rr2 %></span>
				</div>

				<div class = 'c-element-details c-element-margin'>
					<span class="c-left-text">
					<% if( _.indexOf(group.message_data['reachability_rules'],'V')!=-1){ %>
                    	<input type="checkbox" class="checkbox" name="cc" checked disabled />
                    <%} else {%>
                    	<input type="checkbox" class="checkbox" name="cc" disabled />
                    <%}%>
					</span><span class ="c-left-text c-element-line"><?= _campaign('Email IDs which are valid')?></span><span class='c-right-text'>
					<%= group.item_data['valid_email'] %></span>
				</div>
				<% if(group.item_data['unable_email'] > 0) { %>
				<div class = 'c-element-details c-element-margin'>
					<span class="c-left-text">
                    <input type="checkbox" class="checkbox" name="cc" checked disabled />
					</span><span class ="c-left-text c-element-line"><?= _campaign('Unable to Verify')?></span><span class='c-right-text'>
					<%= group.item_data['unable_email'] %></span>
				</div>
				<% } %>
				<div class = 'c-element-details c-element-margin'>
					<span class="c-left-text">
					<% if( _.indexOf(group.message_data['reachability_rules'],'S')!=-1){ %>
                    	<input type="checkbox" class="checkbox" name="cc" checked disabled />
                    <%} else {%>
                    	<input type="checkbox" class="checkbox" name="cc" disabled />
                    <%}%>
					</span><span class ="c-left-text c-element-line"><?= _campaign('Email IDs with 1-3 are soft bounces')?></span><span class='c-right-text'>
					<%= group.item_data['soft_bounced_email'] %></span>
				</div>

				
										<% if(group.item_data['yet_verifying_email']==0) { %>
										<% } else { %>
											<div class='c-element-details c-element-style-details'>
											<span class='c-left-text'><?= _campaign('Email ID Verification')?>: <%= group.item_data['percentage_email']%><?= _campaign('% of')?> <%= group.item_data['email'] %> <?= _campaign('remaining')?></span><span class='c-right-data'><%= group.item_data['yet_verifying_email'] %></span>
											</div>
											<% } %>

				
				<div class='c-element-details c-div-class-cannot'>
					</span><span class='c-left-text c-font-bolder'><b><?= _campaign('Cannot be contacted')?> </b></span> <span class='c-right-text '><%= group.item_data['unreachable_email']%></span>
				</div>
				<div class = 'c-element-details c-element-margin'>
					<span class="c-left-text"><input type="checkbox" class="checkbox" name="cc" disabled /></span><span class ="c-left-text c-element-line"><?= _campaign('Unsubscribed (Cannot Send)')?></span><span class='c-right-text'>
					<%= group.item_data['unsubscribed_email'] %></span>
				</div>
				<div class = 'c-element-details c-element-margin'>
					<span class="c-left-text"><input type="checkbox" class="checkbox" name="cc" disabled /></span><span class ="c-left-text c-element-line"><?= _campaign('Emails not available (Cannot Send)')?></span><span class='c-right-text'>
					<%= group.item_data['unavailable_email'] %></span>
				</div>
				<% if(group.item_data['unverify_email'] > 0) { %>
				<div class = 'c-element-details c-element-margin'>
					<span class="c-left-text">
                    <input type="checkbox" class="checkbox" name="cc" disabled />
					</span><span class ="c-left-text c-element-line"><?= _campaign('Unverified Emails')?></span><span class='c-right-text'>
					<%= group.item_data['unverify_email'] %></span>
				</div>
				<% } %>
				<div class = 'c-element-details c-element-margin'>
					<span class="c-left-text"><input type="checkbox" class="checkbox" name="cc" disabled /></span><span class ="c-left-text c-element-line"><?= _campaign('Invalid Email address (Cannot Send)')?></span><span class='c-right-text'>
					<%= group.item_data['invalid_email'] %></span>
				</div>
	
			<% }else if(group.message_data.type != 'MOBILEPUSH'){ %>  
				
				<div class='c-element-details'>
					</span><span class='c-left-text c-font-bolder'><b><?= _campaign('Can be contacted (Target Mobile Nos)')?></b></span> <span class='c-right-text'><%= group.item_data['reachable_mobile'] %></span>
				</div>
				<div class = 'c-element-details c-element-margin'>
					<span class="c-left-text"><input type="checkbox" class="checkbox" name="cc" checked disabled /></span><span class ="c-left-text c-element-line"><?= _campaign('Numbers which are valid')?></span><span class='c-right-text'>
					<%= group.item_data['valid_mobile'] %></span>
				</div>
				<% if(group.item_data['unable_mobile'] > 0) { %>
				<div class = 'c-element-details c-element-margin'>
					<span class="c-left-text">
                    <input type="checkbox" class="checkbox" name="cc" checked disabled />
					</span><span class ="c-left-text c-element-line"><?= _campaign('Unable to Verify')?></span><span class='c-right-text'>
					<%= group.item_data['unable_mobile'] %></span>
				</div>
				<% } %>
				
										<% if(group.item_data['yet_verifying_mobile']==0) { %>
											
										<% } else { %>
											<div class='c-element-details c-element-style-details'>
											<span class='c-left-text'><?= _campaign('Mobile No Verification')?>: <%= group.item_data['percentage_mobile'] %><?= _campaign('% of')?> <%=group.item_data['mobile'] %> <?= _campaign('remaining')?></span><span class='c-right-text'><%= group.item_data['yet_verifying_mobile'] %></span>
											</div>
											<% } %>

				
				<div class='c-element-details'>
					<span class='c-left-text c-font-bolder'><b><?= _campaign('Cannot be contacted')?> </b></span> <span class='c-right-text'><%= group.item_data['unreachable_mobile'] %>	</span>
				</div>
				<div class = 'c-element-details c-element-margin'>
					<span class="c-left-text"><input type="checkbox" class="checkbox" name="cc" disabled /></span><span class ="c-left-text c-element-line"><?= _campaign('Unsubscribed (Cannot Send)')?></span><span class='c-right-text'>
					<%= group.item_data['unsubscribed_mobile'] %></span>
				</div>
				<% if(group.item_data['unverify_mobile'] > 0) { %>
				<div class = 'c-element-details c-element-margin'>
					<span class="c-left-text">
                    <input type="checkbox" class="checkbox" name="cc" disabled />
					</span><span class ="c-left-text c-element-line"><?= _campaign('Unverified Mobiles')?></span><span class='c-right-text'>
					<%= group.item_data['unverify_mobile'] %></span>
				</div>
				<% } %>
				<div class = 'c-element-details c-element-margin'>
					<span class="c-left-text"><input type="checkbox" class="checkbox" name="cc" disabled /></span><span class ="c-left-text c-element-line"><?= _campaign('Numbers not available (Cannot Send)')?></span><span class='c-right-text'>
					<%= group.item_data['invalid_mobile'] %></span>
				</div>
			<% } %>
			</div>
			<% } %>
			<%}%>
			<div style="clear:both"></div>
			<div class="margin-heading">
			<span class="auth-note c-schedule-margin c-font-bolder" style="font-size:16px;"><b><?= _campaign("Sender & Schedule Details"); ?></b></span>
			<% _.each( group.auth_details, function( item,key ){ %> 
				<% if(key=='<?= _campaign("Sender Email")?>' || key=='<?= _campaign("Sender From")?>' || key=='<?= _campaign("Sender Mobile")?>' || key=='<?= _campaign("Schedule Type")?>' || key=='<?= _campaign("Sender Account")?>'){ %>
					<span class="auth-note"><b><%=key%>:</b> <%=item%></span>
				<%}%>
			<% }); %>
			<% if(group.message_data.type=='WECHAT') { %>
				<span class="auth-note"><?= _campaign("Service Account") ?> : <%=group.item_data.wechat_accounts['0']['account_name']%></span>
				<% } %>
				</div>
				
			<div style="clear:both"></div>
			<div class="margin-heading">
			<span class="auth-note c-schedule-margin c-font-bolder" style="font-size:16px;"><b><?= _campaign("Notification Receivers"); ?></b></span>
			<% _.each( group.notification_receivers, function( item,key ){ %> 
				<span class="auth-note"><b><%=key%>:</b> <%=item%></span>
			<% }); %>
</script>
<script id="campaign_overview_v3" type="text/template">
  <div class="camp-content">
		<div class="padding_class1">
			<div class="in-content">
				<div class="left-desc">
					<ul>
						<li class='camp-name'>
							<span class="headtext" id="in-heading-campaign-name"><%= name %></span>
						<span class="desctext"><?= _campaign("Outbound Campaign") ?></span>
						</li>
						<li class='g-analytics'>
							<span class="headtext" id="in-heading-ga-name"><%= ga_name!=''?ga_name:"<?= _campaign('Not Tracked') ?>" %></span>
							<span class="desctext"><?= _campaign("on Google Analytics") ?></span>
						</li>
						<li class='camp-date'>
							<span class="floatingleft fromdate" id="camp-fromdate"><%= display_start_date %></span> <span class="floatingleft calendericon"></span> <span class="floatingleft todate" id="camp-todate"><%= display_end_date %></span>
						</li>
					</ul>
				</div>
				<div class="right-desc">
					<ul>
					<% if( quick_count > 0 ){ %>
						<li><a class="help-btn-icon automation-info i-quickinfo"><?= _campaign('help')?></a>
					<% }else{ %>
						<li><a class="help-btn-icon automation-info"><?= _campaign('help')?></a>
					<% } %>
						<div id="pop_report" class="popover bottom">
							<div id="close_pop" class="cnew-close">×</div>
							<div class="auto-report-arrow"></div>
							<div class="popover-content a-report-cont" id="reports_popover">
								<div class="report-check"><%= quick_info %></div>
								<ul class="c-check-list"><%= quick_list %></ul>
							</div>
						</div>
						</li>
						<li>
						
						<a class="<%= is_expired > 0 ? 'play-btn-icon change_campaign_status' : 'pause-btn-icon show_dialog' %>" ><?= _campaign('Pause')?></a>
						</li>
						<li><a class="note-btn-icon" id="campaign-update-btn" campaign_id="<%= id %>"><?= _campaign('edit') ?></a></li>
					</ul>
				</div>
			</div>
			<div class="c-m-container">
				<div class="corosol-container">
					<div class="padding_class">
						 <% if( total_messages < 1 ){ %>
						 <div id="myCarousel" class="carousel slide">
						 	<ol class="carousel-indicators">
							    <li data-target="#carousel-example-generic" data-slide-to="0" class="active"></li>
							    <li data-target="#carousel-example-generic" data-slide-to="1"></li>
							    <li data-target="#carousel-example-generic" data-slide-to="2"></li>
							  </ol>
						      <div class="carousel-inner">
						        <div class="item active">
						          <div class="container">
						            <div class="carousel-caption">
						               <p class="lead">
						               		<div class="campaignSend"><?= _campaign('message') ?></div>
						               		<div class="newCampaignHelp"><?= _campaign("Begin this Campaign by sending a Message to Customers") ?><br/><?= _campaign("Using Coupons can increase Customer Engagement (Optional)") ?></div>
						               </p>
						            </div>
						          </div>
						        </div>
						        <div class="item">
						          <div class="container">
						            <div class="carousel-caption">
						              <p class="lead">
						              	<div class="campaignSend"><?= _campaign('message') ?></div>
						               		<div class="newCampaignHelp"><?= _campaign("Begin this Campaign by creating Customer lists") ?></div>
						              </p>
						            </div>
						          </div>
						        </div>
						        <div class="item">
						          <div class="container">
						            <div class="carousel-caption">
						              <p class="lead">
						              	<div class="campaignSend"><?= _campaign('message') ?></div>
										<div class="newCampaignHelp"><?= _campaign("Begin this Campaign by creating Coupons (Optional)") ?></div>
						              </p>
						            </div>
						          </div>
						        </div>
						      </div>
						      <a class="left carousel-control" href="#myCarousel" data-slide="prev">&lsaquo;</a>
						      <a class="right carousel-control" href="#myCarousel" data-slide="next">&rsaquo;</a>
						 </div><!-- /.carousel -->
						 <% } else { %>
						 	<div class="reports-summary">
								<h5 class="report-head report-imghead report-head-active"><?= _campaign("Reports") ?></h5>
								<div class="r-summary-container">
									<div class="summary-inside summary-emails"><%= open_count > 0 ? open_count : 0 %>/<%= total_sent_email_count > 0 ? total_sent_email_count : 0 %><div class="summary-report-lbl"><?= _campaign("Emails Opened") ?></div></div>
									<div class="summary-inside summary-roi"><%= sent_count > 0 ? sent_count : 0 %>/<%= total_sent_sms_count > 0 ? total_sent_sms_count : 0 %><div class="summary-report-lbl"><?= _campaign("SMS Sent") ?></div></div>
									<div class="summary-inside summary-coupons"><%= num_of_coupon_redeemed %>/<%= num_of_coupon_issued %><div class="summary-report-lbl"><?= _campaign("Coupons Redeemed") ?></div></div>
								</div>
								<div class="addreportitem">
									<a href="#reports/<%= id %>" id="newReport"><?= _campaign("View more"); ?></a>
								</div>
							</div>
						<% } %>
					</div>
				</div>
				<div class="coupon-container">
					<div class="padding_class">
						<h5 class="coupon-head imghead <%= no_coupon_series > 0 ? 'coupon-head-active' : '' %>"><?= _campaign("Incentives") ?></h5>
						<p class="status-msg"></p>
						<p class="status-msg o-coupon-desc" title="<%= no_coupon_series > 0 ? coupon_series_desc : "<?= _campaign('No coupon series created') ?>" %>">
						<%= no_coupon_series > 0 ? coupon_series_desc : "<?= _campaign('No coupon series created') ?>" %></p>
						<%= no_coupon_series > 0 ? "<p class='status-msg'>"+no_coupon_series+" <?= _campaign('coupon attached') ?> </p>" : "" %>
						<%= no_points_strategy_attached > 0 ? "<p class='status-msg'> <?= _camp1('Points Incentivisation Selected') ?></p>" : "<?= _camp1('No Points Incentivisation Selected') ?>" %>
						
					</div>
					<div class="addnewitem"><a href="#view-coupon/<%=id%>" id="newCoupon"><%= no_coupon_series > 0 ? "<?= _campaign('View Incentives') ?>" : "<?= _campaign('Create Incentives') ?>" %></a></div>
				</div>
				<div class="receipt-container">
					<div class="padding_class">
						<h5 class="receipt-head imghead <%= no_of_audience_list > 0 ? 'receipt-head-active' : '' %>"><?= _campaign("Recipient Lists") ?></h5>
						<p class="status-msg"></p>
						<p class="status-msg"><%= no_of_audience_list > 0 ? no_of_audience_list+" <?= _campaign('lists attached') ?>" : "<?= _campaign('Not Attached') ?>" %></p>
						<%= no_of_audience_list > 0 ? "<p class='status-msg'>"+total_emails+"<?= _campaign('Email IDs') ?></p>" : "" %>
						<%= no_of_audience_list > 0 ? "<p class='status-msg'>"+total_mobiles+"<?= _campaign('Mobile Numbers') ?></p>" : "" %>
						<% var openIdCount = 0;
							_.each(reachability,function(value,key){
								openIdCount+= parseInt(value.wechatOpenIdCount);
							});
							openIdCount > 0 ? "<p class='status-msg'>"+openIdCount+"<?= _campaign('Target WeChat OpenIds') ?></p>" : ""
						%>
					</div>
					<div class="addnewitem">
						<a href="#recipient/<%=id%>" id="newList"><%= no_of_audience_list > 0 ? "<?= _campaign('View Lists') ?>" : "+ <?= _campaign('New List') ?>" %></a>
					</div>
				</div>
			</div>
			<div class="messages-container">
				<div class="newContainer">
					<div class="messagesToDisplay">
					<h5 class="msgtext_header"><?= _campaign("Messages") ?><span class="msgtext"><%= ( total_messages > 0 ) ? "(" + sent_messages + "/" + total_messages + "<?= _campaign('Sent') ?>" + ")" : "" %></span></h5>
					<%= ( total_messages < 1 ) ? "<p class='nomessagesstatus'><?= _campaign('No Messages are configured from this Campaign.') ?></p>" : "" %>
					
					</div>
				</div>
				<div class="modal hide fade in" id="camp_msg_preview_modal" style="display: none;">
					<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
						<h3 id="camp_msg_preview_header"><?= _campaign('Preview') ?></h3>
					</div>
					<div class="modal-body">
						<h5 id="camp_msg_preview_inside_header"></h5>
						<div class="lang_content_parent">
							<ul class="lang_tab_preview  lang_list_tabs" id="msg_preview_lang_list">

						</ul>
						</div>
						<iframe id="camp_msg_preview_frame" width="100%" height="340px"></iframe>
					</div>
					<div class="modal-footer">
						<a data-dismiss="modal" class="btn"><?= _campaign('Close') ?></a>
					</div>
				</div>
				<div class="modal hide fade in" id="camp_msg_details_modal" style="display: none;">
					<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
						<h3 id="camp_msg_details_header"><?= _campaign('Message Details') ?></h3>
					</div>
					<div class="modal-body c-min-height">
						<div id="camp_msg_details" width="100%" height="340px"></div>
					</div>
					<div class="modal-footer">
						<a data-dismiss="modal" class="btn"><?= _campaign('Close') ?></a>
					</div>
				</div>

				<div class="modal hide fade in" id="camp_update_modal" style="display: none;">
					<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
						<h3 id="camp_update_header"><?= _campaign('Campaign Details') ?></h3>
					</div>
					<div class="modal-body" id="modal-body">
						<div style="text-align: center;color: red;" class="hide" id="camp-update-error"><?= _campaign('sdds') ?></div>
						<form method="post" action="" id="updatecampaign" name="updatecampaign">
						
							<div class="form-controls main">
							    <label for="campaign_name" class="campaign_label left_label campaign_name_label"><?= _campaign('Campaign Name') ?></label>
							    <div class="input-container">
							      <input type="text" id="campaign_name" name="campaign_name" value="<%= name %>"
							    	class="input-nsl margin_left_5 validate[required;regexcheck</^[0-9a-zA-Z][0-9 _a-zA-z]{0,48}[0-9a-zA-Z]$/>;custom_val<<?= _campaign('Campaign Name can be alphanumeric, can have underscore & space, but no leading or ending underscore & space') ?>>]">
								</div>
							</div>
							  <div class="form-controls main main-content">
							    <label class="campaign_label left_label"><?= _campaign('Valid between') ?></label>
							    <div class="input-container">
							      <ul class="unstyled l-h-list">
							      <li class="item c-range"><input type="text" class="datepicker-newcamp" id="u_starting_date" readonly
							        name="u_starting_date" class="margin_left_5 validate[required];"></li>
							      <li class="item c-range"><i class="dashboard-icon camp-clock"> </i></li>
							      <li class="item c-range"><input type="text" class="datepicker-newcamp" id="u_end_date" readonly
							        name="u_end_date" class="margin_left_5 validate[required];" ></li>
							      </ul>
						    	</div>
						  	</div>
						  	<div id="addedittemplate" class="main">
        					</div>
						  <% if( ga_name != '' ){ %>	
					          <div class="form-controls main">
					            <label><?= _campaign('GA Name')?></label>
					            <div class="input-container">
					              <input type="text" id="ga_name" name="ga_name" value="<%= ga_name %>"
					              	class="input-nsl margin_left_5 validate[required;regexcheck</^[a-zA-Z0-9][0-9 _\-/a-zA-z]*[a-zA-Z0-9]$/>;custom_val<<?= _campaign('GA Name can be alphanumeric, can have underscore,hiphen,slash & space, but no leading or ending underscore & space') ?>>]">
					            </div>
					          </div>
					          <div class="form-controls main">
					            <label><?= _campaign('GA Source')?>*</label>
					            <div class="input-container">
					              <input type="text" id="ga_source_name" name="ga_source_name" value="<%= ga_source_name %>"
					              	class="input-nsl margin_left_5 validate[required;regexcheck</^[a-zA-Z0-9][0-9 _\-/a-zA-z]*[a-zA-Z0-9]$/>;custom_val<<?= _campaign('GA Source Name can be alphanumeric, can have underscore,hiphen,slash & space, but no leading or ending underscore & space') ?>>]">
					            </div>
					          </div>				
							<% } %>				          		  	
							<div class="form-controls main submit_form_div main-content">
							   <label></label>
							   <div class="input-container input-container-edit">
							   <input type="hidden" name="org_id" id="org_id" value="<%= org_id %>">
							   <input type="hidden" name="campaign_id" id="campaign_id" value="<%= id %>">
							   <a id="updateCampaign-btn" class="btn btn-success active" style="background-color:#86b91e!important"><?= _campaign('Update Campaign') ?></a>
							</div>
						</form>
					</div>
					</div>
					<div class="modal-footer" id="modal-footer">
					</div>
				</div>
				<div class="modal hide fade" id="camp_status_update_modal" style="display:none">
					<!--TODO: VINAY-->
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
						<h3 id="camp_status_header"><?= _campaign('Confirm End Campaign') ?></h3>
					</div>
					
					<div class="modal-body">
						<form action="" method="POST" id="campaignChangeStatus" >
							<p><?= _campaign('Ending the campaign would lapse the campaign') ?></p>
							<p><?= _campaign('We would not recommend it unless you do not need the Campaign anymore.') ?></p>
							<div class="form-controls main pull-right" >
							   	<div class="input-container">
							   		<input type="hidden" id="hdn_status_val" name="campaign_status_value" value="<%= id %>"/>
								   <a  class="btn btn-success active" id="hide_dialog" style="background-color:#86b91e!important"><?= _campaign('Oh, wait') ?></a>
								   <a id="campaign_status_btn" data-dismiss="modal" class="btn"><?= _campaign('End Campaign') ?></a>
								   
								</div>
							</div>
						</form>
					</div>
					
				</div>
				<div class="messagesTableContainer mTable-qslider"></div>
			</div>
		</div>
	</div>
</script>
<script id="create_coupon" type="text/template">
  <div class='camp-content'>
	<div class='padding_class1'>
		<div class='in-content'>
	<div class="coupon_wait_message"></div>
   	<div class="clearfix"></div>
   	<div id="coupons_btn_bar" class="">
   		<div class="c-title" id="a_c_series"><?= _campaign("Coupons"); ?></div>
   		<div class="c-title hide" id="c_c_series"><?= _campaign("Configure Coupon Series"); ?></div>
   		<div class="c-but-cont">
           	<div class="bttn-submit"  id="create_new_coupon">+ <?= _campaign("New Coupon Series"); ?></div>
       	</div>
		<div class="clearfix"></div>
		<button id="show_coupon_button" class="c-button pull-left c-margin10" onclick="">
			<i class="icon-arrow-left"></i><?= _campaign("Show Attached Coupon"); ?>
		</button>
	</div>
	<div class="clearfix"></div>
	<div class="no-c-msg hide"><?= _campaign("No attached coupons"); ?></div>
	<div class="row-fluid c-new-details" id="coupon_details">
		<div class="span5">
			<div class="c-new-info"><span class="c-new-label"><?= _campaign("Coupon Series:"); ?></span>
				<span><%= camp_details.description %></span></div>
			<div class="c-new-info"><span class="c-new-label"><?= _campaign("Coupon Code:"); ?></span>
				<span><%= camp_details.code %></span></div>
			<div class="c-new-info"><span class="c-new-label"><?= _campaign("Discount:"); ?></span>
				<span><%= camp_details.discount %></span></div>
			<div><span id="cache_coupon"><?= _campaign("Sync coupon to cache"); ?></span></div>	
			<% if(camp_details.voucher_validity.valid){%>
			<div class="c-new-info" id="c_product_details">
			<%}else{%>
				<div class="c-new-info hide" id="c_product_details">
			<%}%>
				<span class="c-new-label"><?= _campaign("Valid Brands and Categories:"); ?></span>
				<span class="view-coupon-validity"><a id="view_coupon_validity" class="c-v-view-details" v_s_id="<%=camp_details.voucher_series_id %>"  ><?= _campaign("View"); ?></a></span>
			</div>
			
		</div>
		<div class="span4">
			<div class="c-new-info"><span class="c-new-label"><?= _campaign("Redeemed:"); ?></span>
				<span><%= camp_details.num_redeemed %></span></div>
			<div class="c-new-info"><span class="c-new-label"><?= _campaign("Issued:"); ?></span>
				<span><%= camp_details.num_issued %></span></div>
		</div>
		<div class="span3">
			<input type="hidden" id="campaign_id" name="campaign_id" value="" />
			<input type="hidden" id="c_new_voucher" name="c_new_voucher" value="" />
			<input type="hidden" id="coupon_validity_val" name="coupon_validity_val" value="<% if(camp_details.voucher_validity.valid){%>Custom<%}else{%>All<%}%>" />
			<div class="btn-group">
  				<button type="button" class="btn btn-default dropdown-toggle c-coupon-option" data-toggle="dropdown"><?= _campaign("Options "); ?><span class="caret pull-right"></span> </button>
  				<ul class="dropdown-menu" role="menu">
    				<li><a class="open_coupon_details" id="cnew_details" data-toggle="modal" data-target="#coupon_series_modal" ><i class="icon-file"></i> <?= _campaign("View"); ?></a></li>
				    <li><a class="coupon_edit" id="coupon_edit"><i class="icon-pencil"></i> <?= _campaign("Edit"); ?></a></li>
				    <li><a onclick="showPopup( '/campaign/coupons/VouchersUpload?flash=&amp;coupon_series_id=<%= camp_details.voucher_series_id %>' );"><i class="icon-upload-alt"></i> <?= _campaign("Upload Coupons"); ?></a></li>
				    <li><a onclick="showPopup( '/campaign/coupons/Listener?flash=&amp;campaign_id=<%= camp_details.id %>' );"><i class="icon-cogs"></i><?= _campaign("Define Redemption Actions"); ?></a></li>
  				</ul>
			</div>
		</div>
		<div class="clearfix"></div>
		<div class="modal fade view-coupon-details" tabindex='-1' role='dialog' id="coupon_series_modal">
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
	
	<div id="configure_coupon" class="hide show-edit-coupon flexcroll"></div>
    <div class="new-c-create" id="coupon_new_create">
		<div class="new-c-content coupon">
       		<div class="c-title"><?= _campaign("New Coupon Series"); ?></div>
        		<form method="post" action="" id="new_coupon_series" name="new_coupon_series">
			  	  <div class="form-controls" style="margin-top:8px">
			    	<label for="coupon_tag"><?= _campaign("Coupon Series Tag"); ?></label>
					<div class="input-container">
					  <input type="text" id="info" name="info"
					    class="input-nsl validate[required;regexcheck</^(?=.*[a-zA-Z]).{1,160}$/>;custom_val< <?= _campaign('Tag should be less than 160 characters and must have atleast one letter') ?>>]">
					</div>
				  </div>
				  <div class="form-controls" style="margin-top:8px">
			    	<label for="discount_on"><?=_campaign("Offer On")?></label>
					<div class="input-container">
					  <select id="discount_on" name="discount_on">
					  	<option value="BILL" selected="selected"> <?= _campaign("BILL VALUE") ?></option>
					  	<option value="ITEM"> <?= _campaign("ITEM"); ?></option>
					  </select>
					</div>
				  </div>
				  <div class="form-controls" style="margin-top:8px">
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
					<div class="form-controls" style="margin-top:8px">
						<i id="specify_num_cpn" style="float:left;margin-left:11.3%" class=" num_cpn_radio intouch-green-tick intouch-green-tick-active"></i><label for="num_coupons"><?=_campaign("Limit the number of coupons to be issued to");?> </label>
						<div class="input-container">
							<input type="text" id="num_coupons" name="num_coupons_specified"/>
						</div>
						<div style="float:left;margin-left:4%">
						<i id="default_num_cpn" style="float:left" class="num_cpn_radio intouch-green-tick "></i><label style="text-align:left"><?=_campaign("Do not limit");?> </label>
						</div>
					</div>	
				  <div class="form-controls" style="margin-top:8px">
				  	<label></label>
				  	<div class="input-container">
				  	  <div class="text-info">*<?= _campaign("Advanced settings are available after saving"); ?></div>
				  	</div>
				  </div>
			  	  <div class="form-controls" style="margin-top:8px">
			    	<label></label>
			    	<div class="input-container">
			    	<input type="hidden" name="max_create" id="max_create" value="-1">	
			    	  <input type="hidden" name="coupon_org_id" id="coupon_org_id" value="-1">
			      	  <input type="button" id="createNewSeries" class="bttn-submit" value=<?= _campaign("Attach Coupon Series"); ?>>
			      	  <input type="button" value='<?= _campaign("Cancel") ?>' class="cancel-btn" id="coupon_cancel"/>
			    	</div>
				</div>
			</form>
		</div>
	</div>

	<div class="points_details" id="points_details" program-id="<%= camp_details.points_info.program_id %>">
			<div class="triangle hide"></div>
			<div class="pop_up hide" id="alloc_pop_up">
				<% _.each(camp_details.points_info.allocation_strategy,function(post,i) { %>
					<div div-id="<%= i %>" class="hide alloc_cmn alloc_row__<%= i %>">
						<ul style="list-style-type:none;margin:0px">
							<% _.each(post.property_values,function(prop,j) { %>
								<li><%= camp_details.points_info.slabs[j] %> <%= prop %> <?= _campaign("points"); ?></li>
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

		<div class="p-title" id="a_p_series">
			<?=_campaign("Points")?>
			<input id="points_msg" type="hidden" value="1" />
			<div class="bttn-submit" style="float:right" id="points_settings">
				<?= _campaign("Choose Points Settings"); ?>
			</div>
		</div>
		<div id="err_msg" class="hide">
			<%= camp_details.points_info.status.msg%>
		</div>

	<div id="choose_points" class="hide" >
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
						<div class="span3" position-id="<%= i %>">
							<span class="expiry_details" style="cursor:pointer" position-id="<%= i %>" ><u><?= _campaign("View Details"); ?></u></span>
						</div>	
						<div class="span4">
							<span id="num_msg"></span>
						</div>	
					</div>	
				<% }); %>	
			</ul>
		</div>
		
		<input style="margin-right:10px" type="button" class="bttn-submit" value=<?= _campaign("Save"); ?> id="points_save"/>
		<input type="button" value=<?= _campaign('Cancel') ?> class="cancel-btn" id="points_cancel"/>
		
	</div>	

	<div id="points_fixed" >
		<p class="points_sub_heading"> <?= _campaign("Points Allocation Strategy Used"); ?></p>
		<div class="p-new-details" id="alloc_points_fixed">
			<ul class="points_list" id="fixed_alloc_list">
				<% _.each(camp_details.points_info.allocation_strategy,function(post,i) { %>
					<div class="row-fluid">
						<div class="span5">
							<li strat-id="<%= post.id %>"><%= post.name %></li>
						</div>	
						<div class="span3 " position-id="<%= i %>">
							<span class="alloc_details" style="cursor:pointer" position-id="<%= i %>"><u><?= _campaign('View Details') ?></u></span>
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
							<li strat-id="<%= post.id %>"><%= post.name %></li>
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
<script id="coupon_product_tpl" type="text/template">
  <% if (sku.length < 1) { %>
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
	          <span ><?= _campaign("No Brands selected"); ?></span>
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
	<% } else {%>
	<div class="c-v-category-div">
    	<div id='v_s_b_title' class='c-v-selected-title'>
	      <span id='v_s_b_arrow' class='down-arrow'> </span> 
	      <span><?= _campaign("Selected SKU Values"); ?></span>
	    </div>
	    <div id='v_s_b_desc' class="c-v-brand-desc">
	      <% no_sku=true; _.each(sku,function(a,i){no_sku=false;%>
	        <div>
	          <span class='category-name'> <%=a%></span>
	        </div>
	        <%});
	        if(no_sku){%>
	        <div>
	          <span ><?= _campaign("No SKU Values selected"); ?></span>
	        </div>
	        <%}%>
	            
	    </div>
	   
	</div>
	<% } %>
</script>
<script id="campaign_reports_v3" type="text/template">
  <iframe id='creativeframe' src='/campaign/v3/reports/Home?q=a&campaign_id=<%=idJson.id %>' style="min-height:500px" border=0 width=100% height= 99%/></iframe>
</script>
<script id="webengage_message_preview" type="text/template">
	<div class="c-status-option preview_type">
		<a class="c-status-live preview-mobile-push" id="android-all-preview"><?= _campaign("Android")?></a>
		<input type="hidden" id="android-status" value="true">
		<a class="c-status-upcoming preview-mobile-push" id="ios-all-preview"><?= _campaign("IOS")?></a>
		<input type="hidden" id="ios-status" value="true">		
	</div>

	<% if('ANDROID' in model.templateData ) { %>
		<div class="mobile-preview-icon" id="mobile-preview-icon-android" >
			<div class="preview_container preview_container_margin_mobilepush">
				<div id="android-title"><%=model.templateData.ANDROID.title%></div>
				<div id="android-content"><%=model.templateData.ANDROID.message%></div>
				<div id="android-image"></div>
			</div>
		</div>
	<% } %>
	<% if('IOS' in model.templateData ) { %>
		<div class="mobile-preview-icon hide" id="mobile-preview-icon-ios">
			<div class="preview_container preview_container_margin_mobilepush">
				<div id="ios-title"><%=model.templateData.IOS.title%></div>
				<div id="ios-content"><%=model.templateData.IOS.message%></div>
				<div id="ios-image"></div>
			</div>
		</div>
	<% } %>	
</script>


<script id="android-notif-preview" type="text/template">
	<div class="c-status-option preview_type">
		<a class="c-status-live preview-mobile-push sel" id="android-all-preview"><?= _campaign("Android")?></a>
		<input type="hidden" id="android-status" value="true">
		<a class="c-status-upcoming preview-mobile-push" id="ios-all-preview"><?= _campaign("IOS")?></a>
		<input type="hidden" id="ios-status" value="true">		
	</div>
	<div class="mobile-preview-icon-android">
		<div class="preview_container preview_container_margin_mobilepush">
		  <div class="android-notif-header">
		    <div class="android-notif-app-icon">
		      <i class="icon-star icon-3x"></i>
		    </div>
		    <div class="android-notif-title-body">
		      <div class="android-notif-title">
		        <h1><bold><%= android_data.title %></bold></h1>
		      </div>
		      <div class="android-notif-body <%= (android_data.notif_img) ? 'body-truncate' : '' %>"><%= android_data.content %></div>
		    </div>
		  </div>
		  <hr style="border-color: lightgrey">
		  <% cta_length = android_data.cta_sec.length %>
		  <% if(android_data.notif_img) { %>
			<div class="android-notif-img">
				<img src="<%= android_data.notif_img %>" alt="image" height="42" width="42">
		    <% if(cta_length > 0) { 
		    	if(cta_length == 1) {
			%>
				<div class="android-notif-action bottom-adjust" style="padding-left:0px;">
		    		<div style="width: 100%; text-align: center; font-size: 13.5px;" class="single-button-truncate"><%= android_data.cta_sec[0] %></div>
		    	</div>
		    <% } else if(cta_length == 2) { %>
				<div class="android-notif-action bottom-adjust <%= ( android_data.adjust_width ) ? 'android-notif-action-adjust-width': '' %>">
					<div style="display: inline-flex; width: 100%;" class="">
			        	<div class="double-button-truncate <%= ( android_data.adjust_width ) ? 'adjust_width_sec_cta_btn': 'sec-cta-notif-preview' %>"><%= android_data.cta_sec[0] %></div>
			        	<span style="width: 1px;height: 31px;background-color: lightgrey;margin: 0 13.5px 0 7.5px;"></span>
			        	<div class="double-button-truncate <%= ( android_data.adjust_width ) ? 'adjust_width_sec_cta_btn': 'sec-cta-notif-preview' %>" ><%= android_data.cta_sec[1] %></div>
		    		</div>
		    	</div>
		    </div>
			<% }}} %>

			<% if(!android_data.notif_img) { %>
			    <%if(cta_length == 1){%>
			      <div class="android-notif-action android-notif-action-noimg <%= ( android_data.adjust_width ) ? 'campaign-android-preview-btn': '' %>" style="padding-left:0px">
			        	<div style="width: 100%; text-align: center; font-size: 13.5px;" class="single-button-truncate"><%= android_data.cta_sec[0] %></div>
			      </div>
			    <% }else if(cta_length > 1) { %>
			    	<div class="android-notif-action android-notif-action-noimg <%= ( android_data.adjust_width ) ? 'campaign-android-preview-btn': '' %>">
			       		<div style="width: 100%;display: inline-flex;" class="">
			        		<div class="sec-cta-notif-preview double-button-truncate"><%= android_data.cta_sec[0] %></div>
				        	<span style="width: 1px;height: 31px;background-color: lightgrey;margin: 0 13.5px 0 7.5px;"></span>
			          		<div class="sec-cta-notif-preview double-button-truncate"><%= android_data.cta_sec[1] %></div>
			      		</div>
		    		</div>
		  		<% } %>
		  	<% } %>
		</div>
		</div>
</script>


<script id="ios-notif-preview" type="text/template">
	<div class="c-status-option preview_type">
		<a class="c-status-live preview-mobile-push" id="android-all-preview"><?= _campaign("Android")?></a>
		<input type="hidden" id="android-status" value="true">
		<a class="c-status-upcoming preview-mobile-push sel" id="ios-all-preview"><?= _campaign("IOS")?></a>
		<input type="hidden" id="ios-status" value="true">		
	</div>
	<div class="mobile-preview-icon-ios" style="display: block;">
		<div class="preview_container_ios preview_container_margin_mobilepush_ios <%= ( ios_data.adjust_width ) ? 'campaign-ios-preview-container': '' %>">
		  <div class="ios-notif-header">
		    <div class="ios-notif-app-icon">
		      <i class="icon-star"></i>
		    </div>
		    <div class="ios-notif-appname">
		      <bold><?= _campaign("APP NAME")?></bold>
		    </div>
		    <div style="float: right;">x</div>
		  </div>
		  <% if(ios_data.notif_img) { %>
		  <div id="ios-notif-img">
		    <img src="<%= ios_data.notif_img %>" alt="image" height="100%" width="100%">
		  </div>
		  <% } %>
		  <div class="ios-notif-title-body">
		    <div class="ios-notif-title">
		      <h4>
		      	<bold><%= ios_data.title %></bold>
		      </h4>
		    </div>
		    <div class="ios-notif-body <%= (ios_data.notif_img) ? 'ios-body-truncate' : '' %> "><%= ios_data.content %></div>
		  </div>
		  <% cta_length = ios_data.cta_sec.length %>
		  <% if(cta_length > 0) { %>
		  <div class="ios-notif-action">
		    <% if(cta_length == 1) { %>
		      <div class="ios-notif-cation-btn ios-body-truncate" style="border-radius: 7px;"><%= ios_data.cta_sec[0] %></div>
		    <% } else { %>
		      <div class="ios-notif-cation-btn ios-body-truncate" style="border-top-right-radius: 7px;border-top-left-radius: 7px;"><%= ios_data.cta_sec[0] %></div>
		      <div class="ios-notif-cation-btn ios-body-truncate" style="border-bottom-right-radius: 7px;border-bottom-left-radius: 7px; margin-top: 2px;"><%= ios_data.cta_sec[1] %></div>
		    <% } %>
		  </div>
		  <% } %>
	 	</div>
  </div>
</script>


<script id="campaign_overiew_preview_wechat_template_message" type="text/template">
	<div class="mobile-preview-icon" id="mobile-preview-icon-wechat-template-message">
		<div class="preview_container preview_container_margin">
        </div>
	</div>
</script>

<script id="campaign_overview_preview" type="text/template">
	<div class='auth-bananaphone'>
  	<% if(model.msg_type == 'WECHAT_SINGLE_TEMPLATE') { %>
		<div class="wechat-msg-preview-container">
			<div class="wechat-msg-title"><%=model.title%></div>
			<div class="wechat-msg-image"><img src="<%=model.image%>"/></div>
			<div class="wechat-msg-summary"><%=model.summary%></div>
		</div>
		<div class="wechat-msg-content-container hide">
	    	<div class="close">X</div>
	    	<iframe src = "<%='data:text/html;charset=utf-8,' + (model.templateData.content)%>"></iframe>
	    </div>
	<% } if(model.msg_type == 'WECHAT_MULTI_TEMPLATE') { %>
		<div id="multi_image_preview_modal">
			<div class="wechatMultiTemplate">
				<div class='templateTitle hide'><%=model.name%></div>
				<div class="singlePicContainer">
				<% var index = 1; _.each(model.singlePicTemplates,function(value,key) { %>
		    		<div class="singlePic" template="<%=index%>">
				    	<div class="title"><%=value.title%></div>
				    	<div class="imageContainer"><img src="<%=value.image%>"></div>
		    		</div>
		    		<div class="wechat-msg-content-container hide" template="<%=index++%>">
	            		<div class="close">X</div>
	            		<iframe src = "<%='data:text/html;charset=utf-8,' + (value.content)%>"></iframe>
	        		</div>
		    	<% });%>
		  		</div>
	  		</div>
		</div>
	<% } %>
	</div>
</script>

<script id="campaign_overview_authorize" type="text/template">
  <div class="auth-preview-header">
		<div class="margin-left" style="padding-top:4px;"><?= _campaign("Authorize and Send"); ?></div>
	</div>
	<div class="auth-preview-button-bar <%= ( rc.type == 'email' ) ? '' : 'hide' %>">
		<div class="btn-group" style="margin-left:35%">
			<button id="btn-desktop" type="button" class="btn btn-padding btn-inverse active background-color"><?= _campaign("Desktop"); ?></button>
			<button id="btn-mobile" type="button" class="btn btn-padding btn-default"><?= _campaign("Mobile"); ?></button>
		</div>
		<span class="preview-header-info"><?= _campaign("Preview"); ?></span>
	</div>
	<% if( rc.type == 'email' ){ %>
	<div class="auth-subject"><?= _campaign("Subject:"); ?><span class="auth-subject-txt"><%= rc.messages.subject %></span></div>
	<% } %>
	<div id="auth-desktop" class="pull-left <%= ( rc.type == 'email' ) ? '' : 'hide' %>">
		<iframe id="auth-iframe-preview" class="flexcroll" style="width: 90%;margin-top: 2%;height: 370px;margin-left: 5%;"></iframe>
	</div>
	<div id="auth-mobile" class="pull-left hide">
		<div style="width:9%;" class="pull-right mobile-right-bar-hide">
			<span id="auth-mobile-portrait-id" class="auth-mobile-view pull-right margin-top auth-mobile-portrait"><?= _campaign("Portrait"); ?></span>
			<span id="auth-mobile-landscape-id" class="auth-mobile-view pull-right margin-top auth-mobile-landscape-active"><?= _campaign("Landscape"); ?></span>
		</div>
		<div class="auth-bananaphone hide">
			<iframe id="preview-iframe-mobile-portrait" class="flexcroll"></iframe>
		</div>
		<div class="auth-bananaphone-landscape">
			<iframe id="iframe-mobile-landscape" class="flexcroll"></iframe>
		</div>
	</div>

	<div class="auth-mobile-preview <%= ( rc.type == 'sms' || rc.type == 'wechat' || rc.type == 'mobilepush') ? '' : 'hide' %>">
		<div class='auth-bananaphone <%= ( rc.type == 'mobilepush' ) ? 'hide': '' %>'>
			<div class="auth-bubble-sms portrait <%= ( rc.type == 'sms' ) ? '': 'hide' %>"><%= ( rc.type == 'sms' ) ? rc.messages.msg : '' %></div>
			<% if(rc.messages.default_args.msg_type == 'WECHAT_MULTI_TEMPLATE') { %>
			<div id="multi_image_preview_modal">
				<div class="wechatMultiTemplate">
					<div class='templateTitle hide'><%=rc.messages.default_args.name%></div>
					<div class="singlePicContainer">
					<% var index = 1; _.each(rc.messages.default_args.singlePicTemplates,function(value,key) { %>
				    	<div class="singlePic"  template="<%=index%>">
				      		<div class="title"><%=value.title%></div>
				      		<div class="imageContainer"><img src="<%=value.image%>"></div>
				    	</div>
				     	<div class="wechat-msg-content-container hide" template="<%=index++%>">
			            	<div class="close">X</div>
			            	<iframe src = "<%='data:text/html;charset=utf-8,' + (value.content)%>"></iframe>
			          	</div>
				    <% });%>
				  	</div>
			  	</div>
			</div>
			<% } if(rc.messages.default_args.msg_type == 'WECHAT_SINGLE_TEMPLATE') { %>
			<div class="wechat-msg-preview-container <%= ( rc.type == 'wechat' ) ? '': 'hide' %>">
				<div class="wechat-msg-title"><%=rc.messages.default_args.title%></div>
				<div class="wechat-msg-image"><img src="<%=rc.messages.default_args.image%>"/></div>
				<div class="wechat-msg-summary"><%=rc.messages.default_args.summary%></div>
			</div>
			<div class="wechat-msg-content-container hide">
		    	<div class="close">X</div>
		    	<iframe src = "<%='data:text/html;charset=utf-8,' + (rc.messages.default_args.templateData.content)%>"></iframe>
		    </div>
			<% } if(rc.messages.default_args.msg_type == 'WECHAT_TEMPLATE') { %>
				<div id="mobile-preview-icon-wechat-template-message-authorize">
					<div class="preview_container preview_container_margin_authorize">
        			</div>
				</div>
			<% } %>
		</div>

		<div id="mobile-push-auth-preview" class="<%= ( rc.type == 'mobilepush' ) ? '': 'hide' %>"></div>
	</div>
	<% if(rc.type == 'call'){ %>
	<div class="auth-call-preview-box <%= ( rc.type == 'call' ) ? '' : 'hide' %>">
		<div class="call-task-header"><span><?= _campaign('tasks') ?></span><img src="/images/webclient/icoCloseWhite.png" class="pull-right"></div>
		<div class="call-left-block">
			<div class="left-side-details"><%= rc.messages.description %></div>
			<ul class="l-v-list customr-list-left">
				<li class="item background-loader hide"></li>
				<% if( rc.messages.to != false ){ %>
				<li class="item left-side-customer"><%=rc.messages.to%> <%=rc.messages.name%></li>
				<% } %>
			</ul>
		</div>
		<div id="content_container">
			<div id="content_title"><?= _campaign("customer details"); ?></div>
                <div id="content_block">
                    <table border="0">
                        <tbody>
                        <% if( rc.messages.to != false ){ %>
                        	<tr><td class="key" style="width: 1px; min-width: 1px;"><?= _campaign("Name"); ?></td><td class="key-name">: <%=rc.messages.name == ' ' ? '<?= _campaign("Not Available") ?>':rc.messages.name%></td></tr>
                        	<tr><td class="key"><?= _campaign("Mobile"); ?></td><td class="key-mobile">: <%=rc.messages.to%></td></tr>
                        	<tr><td class="key"><?= _campaign("Email Id"); ?></td><td class="key-email">: <%=rc.messages.email%></td></tr>
                        <% }else{ %>
                        <tr><td class="key" style="width: 1px; min-width: 1px;"><?= _campaign("Name"); ?></td><td class="key-name">: <?= _campaign("Not Available"); ?></td></tr>
                        <tr><td class="key"><?= _campaign("Mobile"); ?></td><td class="key-mobile">: <?= _campaign('Not Available')?></td></tr>
                        <tr><td class="key"><?= _campaign("Email Id"); ?></td><td class="key-email">: <?= _campaign("Not Available"); ?></td></tr>
                        <% } %>
                    </tbody></table>
                </div>
                <div id="content_block">
                    <table border="0">
                        <tbody><tr><td class="key"><?= _campaign("Birthday"); ?></td><td>: <?= _campaign("Not Available"); ?></td></tr>
                        <tr><td class="key"><?= _campaign("Gender"); ?></td><td>: <?= _campaign("Not Available"); ?></td></tr>
                        <tr><td class="key"><?= _campaign("Address"); ?></td><td>: <?= _campaign("Not Available"); ?></td></tr>
                    </tbody></table>
                </div>
						<div id="content_title"><?= _campaign("task details"); ?></div>
                <div id="content_block">
                    <table border="0">
                        <tbody><tr><td class="key"><?= _campaign("Task Id") ?></td><td>: <?= _campaign("XXX") ?></td></tr>
                        <tr><td class="key"><?= _campaign("Created By"); ?></td><td class="key-created">: <?= _campaign("Not Available"); ?></td></tr>
                        <tr><td class="key"><?= _campaign("Assigned To"); ?></td><td class="key-assigned">: <?= _campaign("Not Available"); ?></td></tr>
                    </tbody></table>
                </div>
                  <div id="content_block">
					<table border="0">
						<tbody><tr><td class="key"><?= _campaign('Status') ?></td><td class="key-status">: <?= _campaign("OPEN"); ?></td></tr>
						<tr><td class="key"><?= _campaign("End Date"); ?></td><td class="key-enddate">: <?= _campaign("Not Avaialble"); ?></td></tr>
						<tr><td class="key"><?= _campaign("Expiry Date"); ?></td><td class="key-expire">: <?= _campaign("Not Avaialble"); ?></td></tr>
					</tbody></table>
				</div>
				<div id="content_block" style="width: 99%;">
					<div style="width: 100%; background: #eee; border: solid 1px #444; margin-top: 15px;padding: 0px;">
						<p id="task-subject" class="task-subject"><%= rc.messages.subject %></p>
						<div style="background: #666; height: 1px; width: 100%;"></div>
						<p id="task-message" class="task-message"><%= rc.messages.msg %></p>
					</div>
				</div>
			</div>
		</div>
	<%}%>
	<div class="pull-right margin-right preview-right-bar c-overview-margin">
		<div class="margin-top">
			<span class="margin-left margin-top" style="font-size:16px;"><?= _campaign("Message Checklist Details"); ?></span>
			<span class="margin-fix" style="font-size:16px;"><b><?= _campaign("List Details"); ?></b></span>
			<% var is_sms =1 %>
			<%  _.each( rc.checklist, function( item,key ){ %>
				<% if(key=='<?= _campaign("Sender Email")?>') {
					is_sms=0;
				}%>
				<% if(key!='<?= _campaign("Sender Email")?>' && key!='<?= _campaign("Sender From")?>' && key!='<?= _campaign("Sender Mobile")?>' && key!='<?= _campaign("Sender Account")?>'){ %>
					<span class="auth-note"><b> <%=key%>:</b> <%=item%></span>				
				<%}%>
			<% }); %>
			<% if(rc.item_data!=null) {
				if(rc.type=='wechat') {
			%>
			<span class="auth-note"><b><?= _campaign('Target WeChat OpenIds')?>: </b> <%= (_.isObject(rc.item_data['wechatOpenIdCount'])?rc.item_data['wechatOpenIdCount']['wechat_'+rc.messages.default_args.ServiceAccoundId]:0) %></span>
			<% } else if(rc.type != 'mobilepush'){ %>
			<span class="auth-note"><b><?= _campaign('Total Customers')?>:</b> <%=rc.item_data['count']%></span>
			<% if( is_sms==0 ){ %>
			<span class="auth-note"><b><?= _campaign('Target Email IDs')?>:</b> <%=rc.item_data['reachable_email']%></span>
			<% }else{ %>
			<span class="auth-note"><b><?= _campaign('Target Mobile Nos')?>:</b> <%=rc.item_data['reachable_mobile']%></span>
			<% }
			} } %>
		</div>
		<div style="clear:both"></div>
		<div class="margin-heading">
				<span class="margin-fix" style="font-size:16px;"><b><?= _campaign("Sender & Schedule Details"); ?></b></span>
				<% _.each( rc.checklist, function( item,key ){ %> 
					<% if(rc.type != 'email'){ %>
						<% if(key=='<?= _campaign("Sender From")?>' || key=='<?= _campaign("Sender Mobile")?>' || key=='<?= _campaign("Schedule Type")?>' || key=='<?= _campaign("Sender Account")?>'){ %>
							<span class="auth-note"><b><%=key%>:</b> <%=item%></span>
						<%}%>
					<%}%>
				<% }); %>

				<% if(rc.type == 'email'){ %>
					<span class="auth-note"><b><?= _campaign("To be sent "); ?>:</b> <%=rc.checklist['Schedule Type']%></span>
					<span class="auth-note"><b><?= _campaign("From"); ?>:</b> <%=rc.domain_gateway_config.sender_label%> < <%=rc.domain_gateway_config.sender_email%> ></span>
					<span class="auth-note"><b><?= _campaign("Gateway"); ?>:</b> <%=rc.domain_gateway_config.gateway_from%></span>
				<%}%>
			<% if(rc.type=='wechat') { %>
				<span class="auth-note"><b><?= _campaign('Service Account')?> :</b> <%=rc.item_data.wechat_accounts['0']['account_name']%></span>
				<% } %>
				</div>
		<div>

			<% if(rc.item_data!=null) {%>
			<% if( is_sms==0 ){ %>
					<% if(rc.item_data['yet_verifying_email']>0) { %>
						<div class = "underProgress">
					<span class="auth-note">
					<% if(rc.checkAuthorize ==1 ) {%>
						<input type="checkbox" class="checkbox-authorize" name="cc" checked />
					<%}else{%>
						<input type="checkbox" class="checkbox-authorize" name="cc" />
					<%}%>
					 <?= _campaign('Ignore list verification and proceed')?></span>
					<span class="auth-note auth-font-style"><?= _campaign('All Email IDs on the list will be targeted')?></span>

				</div>
				<% } %>
				<% } else { %>
					<% if(rc.item_data['yet_verifying_mobile']>0) { %>
						<div class = "underProgress">
					<span class="auth-note">
					<% if(rc.checkAuthorize ==1 ) {%>
						<input type="checkbox" class="checkbox-authorize" name="cc" checked />
					<%}else{%>
						<input type="checkbox" class="checkbox-authorize" name="cc" />
					<%}%>
					 <?= _campaign('Ignore list verification and proceed')?></span>
					<span class="auth-note auth-font-style"><?= _campaign('All Mobile Nos on the list will be targeted')?></span>

					<% } %>
					<% } %>
			<% } %>


			<span class="auth-note margin-top">
				<% if(rc.checkAuthorize ==1 ) {%>
				<a id="msg_approve_btn" class="btn btn-success active" style="background-color:#86b91e!important"><%= rc.btn_label %></a>
			<%}else{%>
				<a id="msg_approve_btn" class="btn btn-success active pointer-events" style="background-color:#b7b7b7!important"><%= rc.btn_label %></a>
				<%}%>
				<%= rc.reject_label > 0 ? '<a id="msg_reject_btn" class="btn btn-danger active background-color"><?= _campaign("Reject")?></a>' : '' %>
				<a id="msg_close_btn" class="btn btn-inverse active background-color"><?= _campaign("Close"); ?></a>
			</span>
		</div>
	</div>
</script>
<div class="campaigns-parent-container hide">
 <div class="wait_message_form">
      </div>
  <div class="campaigns-header-container">
  </div>
  <div class="campaigns-body-container">
    <div class="campaigns-container flexcroll" id="camp-body-cont">
      <span id="iam-in-top">
      </span>
      <a id="take-to-top">
        <i class="c-top-img">
        </i>
        <span class="top-text">
          <?= _campaign("TOP"); ?>
        </span>
      </a>
      <div class="content-msgcontain" id="content-msgcontain">
      </div>
    </div>
  </div>
</div>
<div id="message_authorize_container">
</div>
