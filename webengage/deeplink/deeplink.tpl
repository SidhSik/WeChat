<script id="add_deeplink" type="text/template">
		<div id="<%= 'deeplink_' + index %>" class="deeplink <%= 'deeplink_' + index %>">
			<div style="height: 10px;"></div>
			
			<div class='deeplname' style='display:inline-flex;'>&nbsp;&nbsp;
					<div>Name&nbsp;&nbsp;</div>
					<div>
						<input type="text" id="deeplink_name"/>
					</div>
			</div>
			
			<div class='deepllink' style='display:inline-flex;'>&nbsp;&nbsp;
				<div>Link&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
				<div>
					<input type="text" id="deeplink_link"/>
				</div>
			</div>
			
			<div class='deeplkeys' style='display:inline-flex;'>&nbsp;&nbsp;
				<div>Key&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
				<div class="deeplink_key_container">
					<div id="keysGoHere"></div>
					<div id="key_1" class="deeplink_addkey">+Add Keys</div>
				</div>
			</div>
			
			<br>		
			
			<div class='deeplfooter' style='float:right'>
				<span>
					<input type="button" id="<%= 'deeplink_' + index + '_discard' %>" value="Discard"/>
				</span>
				<span>
					<input type="button" id="<%= 'deeplink_' + index + '_save' %>" value="Save"/>
				</span>
			</div>
		</div>

</script>