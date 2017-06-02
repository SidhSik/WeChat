<script id="add_deeplink" type="text/template">
	<div class="hide" id="deepLinkMessage"></div>
	<div id="<%= 'deeplink_' + index %>" class="deeplink <%= 'deeplink_' + index %>">

		<div class="scrollDiv" style="display: flex; flex-direction: column; overflow-x: auto; overflow-y: auto; white-space: nowrap;">
			<div class='deeplname' style='display:table-row;'>&nbsp;&nbsp;
					<span style="vertical-align: text-bottom;">Name&nbsp;&nbsp;</span>
					<span class="nameVal">
						<input type="text" id="deeplink_name" value= <%- (name)?name:''  %> >
					</span>
			</div>
			
			<div class='deepllink' style='display:table-row;'>&nbsp;&nbsp;
				<span style="vertical-align: text-bottom;">Link&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
				<span class="linkVal">
					<input type="text" id="deeplink_link" value= <%- (link)?link:'' %> >
				</span>
			</div>
			
			<div class='deeplkeys' style='display:inline-flex;'>&nbsp;&nbsp;
				<div style="padding-left: 4px; padding-top: 5px;">Key&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
				<div class="deeplink_key_container">
					<div id="keysGoHere">
						<% if(keys){ %>
							<% _.each(keys, function(val,k){ %>
								<div style="padding-left:4px; padding-top:2px;">
									<input type="text" id= <%- ('deeplink_' + index + '_key_' + (k+1)) %> value= <%- val %> >
									<i class="fa fa-times keyCancel" style="padding-left:4px; padding-top:5px;"></i>
								</div>
							<% }); %>
						<% } %>
					</div>
					<div id="key_1" class="deeplink_addkey">+Add Keys</div>
				</div>
			</div>
		</div>
		
		<div class='deeplfooter'>
			<span style='float:right; padding-right: 5px;'>
				<input type="button" class="discardButton" id="<%= 'deeplink_' + index + '_discard' %>" value="Discard"/>
			</span>
			<span style='float:right; padding-right: 5px;'>
				<input type="button" class="validateButton" id="<%= 'deeplink_' + index + '_validate' %>" value="Validate & Save"/>
			</span>
		</div>
	</div>

</script>

<script id="add_key" type="text/template">
	<div style="padding-left:4px; padding-top:2px;">
		<input type="text" id= <%- (keyIdPrefix + '_key_' + keyIndexToBeAdded) %> >
		<i class="fa fa-times keyCancel" style="padding-left:4px; padding-top:5px;"></i>
	</div>
</script>

