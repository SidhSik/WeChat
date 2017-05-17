<script id="insert_image_tpl" type="text/template">
  <div>
		<div class="modal fade image-gallery-modal" id="image_gallery_modal" style="width:90%;left: 25%">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<span class="ca-modal-header"><?= _campaign("Insert Image") ?></span>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<div class="image_gallery_container">

						</div>
					</div>
					<div class="modal-footer">	
						<button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Cancel")?> </button>
					</div>
				</div>
			</div>
		</div>
	</div>
</script>
<script id="lang_based_content" type="text/template">
	<div class="ca-dibm ca-subject-container" >
		<span class="ca-subject-label"><?= _campaign("Subject")?> </span>
		<span><input type="text" class="subject ca-subject-input" id="edit_template__subject" name="edit_template__subject" value="<%=subject%>"/></span>
	</div>

    <div class="ca-dibm ca-from-container from_name_div">
		<?=_campaign('From')?>: <span class="from_name"><%=sender_info.sender_label%> </span>			 < <span class="from_email"><%=sender_info.sender_from%> </span> >
	</div>

	<div class="ca-dibm ca-from-container from_gateway_div">
		<?=_campaign('Gateway')?>: <span class="from_gateway"></span>
	</div>

	<span class="edit_from ca-cursor-pointer"><i class="icon-pencil"></i>
	</span>
	
    <div class="lang_create_new_container">    	
	<div class="ca-dib" style="display:none;float:left;margin-left:20px;margin-top:-4.7%">
		<button class="back_view ca-grey-btn"> < <?= _campaign("Back")?></button>
	</div>
        <div class="ca-dibm ca-save-delete" style="float:right; display:none;">
        	<button class="ca-g-btn ca-save-btn save_all_templates"> <?= _campaign("Save") ?> </button>
        	<button class="ca-g-btn ca-save-btn save_all_templates"> <?= _campaign("Delete") ?> </button>
    	</div>	    
    </div>

    <div id="lang_tab_parent">
        <ul id="lang_list" style="list-style-type: none">
            <li lang_id="<%=base_lang.lang_id%>" class="lang_tab" ><%=base_lang.lang_name%>
            	<span class="lang_type_indicator"><i class="base_img"></i></span>
            </li> 
        </ul>
        <span id="add_lang" class="add_lang"><u>+<?= _campaign("new language"); ?></u></span>
    </div>
    <div id="lang_content_parent" style="border:1px solid #b7b7b7;display:none">
        <% _.each(languages,function(option,key){%>
    		<div class="template_editor" editor_lang_id="<%=option.lang_id%>" style="display:none"></div>
    	<%});%>
    </div>
    <div id="create_new_template_parent" style="border:1px solid #b7b7b7;display:none;">
    	<div class="new_template_header" style="margin-bottom:15px">
    		<div class="ca-layout-header" style="margin:5px"><?= _campaign("Select or upload a layout to begin")?></div>
    		<div class="btn-group" role="group" style="float:right;margin:5px">
				<button type="button" class="ca-g-btn ca-new-btn btn dropdown-toggle" data-toggle="dropdown" aria-expanded="false">	<?= _campaign("Upload")?>
					<span class="caret ca-fright"></span>
				</button>
				<ul class="dropdown-menu" role="menu">
					<li><a class="upload_html_file"><?= _campaign("HTML file")?></a></li>
					<li><a class="upload_zip_file"> <?= _campaign("ZIP File")?></a></li>					
				</ul>
			</div>
    	</div>	
    	<% _.each(languages,function(option,key){%>
    		<div class="create_new_template" template_lang_id="<%=option.lang_id%>" style="display:none;width:90%;margin:0 auto">
	    		<% if(option.lang_id!=base_lang.lang_id) { %>
	    			<div class="ca-layout-lang-header" style="width:100%">
	    				<?= _campaign('Please edit the text to')?> <span><%=option.lang_name%></span> <?= _campaign('after you select a layout')?>
	    			</div>
	    		<% } %>	
    		</div>
    	<%});%>
    </div>

    <div id="add_lang_modal"  class="modal fade">
		<div class="modal-header">
			<div class="ca-modal-header"><?= _campaign("Add new language") ?></div>
			<button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="modal-body">
			<p><?= _campaign("Choose the language to be added") ?></p>
			<select id="lang_select">
			<option selected disabled value='1'><?= _campaign("Choose here") ?></option>
			<% _.each(languages,function(option,key){%>
                   <% if(option.lang_id==base_lang.lang_id)%>
						<option option_lang_id="<%=option.lang_id%>" class="lang_option" style="display:none" value=""><%=option.lang_name%></option>
					<% else %>	
					    <option option_lang_id="<%=option.lang_id%>" class="lang_option" value=""><%=option.lang_name%></option>	
			<%});%>    
			</select>
		</div>
		<div class="modal-footer">
			<button type="button" data-dismiss="modal" class="btn ca-g-btn add_lang_btn"><?= _campaign("Add")?></button>			
		</div>
	</div>

	<div>
		<div class="modal fade" id="edit_name_modal">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<div class="ca-modal-header"> <?= _campaign("Rename template") ?> </div>
						<button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>"">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						
						<input type="text" id="rename_template" value="" />
					</div>
					<div class="modal-footer">	
						<button type="button" class="btn ca-g-btn" id="change_name"> <?= _campaign("Save") ?> </button>
						<button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Cancel") ?> </button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div>
		<div class="modal fade" id="save_edit_new_modal">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<div class="ca-modal-header"> <?= _campaign("Save as Template? ")?></div>
						<button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<p><?= _campaign("Save if you intend to use this later. Skip otherwise.")?> </p>
						<input type="text" id="edit_new_name" value="" />
					</div>
					<div class="modal-footer">	
						<button type="button" class="btn ca-g-btn" id="save_edit_new"> <?= _campaign("Save")?> </button>
						<button type="button" class="btn btn-default" id="skip_edit_new" data-dismiss="modal"> <?= _campaign("Skip")?> </button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div>
		<div class="modal fade from_address_modal">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<span class="ca-modal-header"><?= _campaign("From Address")?></span>
						<button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<label>
							<p class="ca-modal-body-header"> <?= _campaign("Domain")?></p>
							<select class="domain_select" id="from_domain_list">
							</select>
							<div><?= _campaign('Use domain for Active Customers')?></div>
							<div><?= _campaign('Only Domains with valid Gateways are shown here. Please contact Gateways Team for more information')?></div>
						</label>
						<label>
							<p class="ca-modal-body-header"> <?= _campaign("Mailed by - Gateway")?></p>
							<select class="domain_select" id="from_subdomain_list">
							</select>
						</label>
						<label>
							<p class="ca-modal-body-header"> <?= _campaign("Sender ID ")?></p>
							<select class="sender_id_select" id="sender_id_select">
							</select>
						</label>
						<label>
							<p class="ca-modal-body-header"> <?= _campaign("Sender Name")?></p>
							<input type="text" class="from_sender_name" id="from_sender_name" />
						</label>
						<label>
							<p class="ca-modal-body-header"> <?= _campaign("Reply-to ID ")?></p>
							<select class="replyto_id_select" id="replyto_id_select">
							</select>
						</label>
						<label>
							<p class="ca-modal-body-header"> <?= _campaign("Reply-to Name")?></p>
							<input type="text" class="from_replyto_name" id="from_replyto_name" />
						</label>
					</div>
					<div class="modal-footer">	
						<button type="button" class="btn ca-g-btn save_name_address"> <?= _campaign("Save ")?></button>
						<button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Cancel")?> </button>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<div  class="modal hide fade remove_tags_modal">
		<div class="modal-header">
			<div class="ca-modal-header"><?= _campaign("Irrelevant tags removed")?></div>
			<button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="modal-body">
			<p><?= _campaign("The below tags, which were a part of the original template have been removed for this campaign as they are not relevant.")?></p>
			<p class="remove_tags_msg"></p>
			
		</div>
		<div class="modal-footer">
			
			<button type="button" data-dismiss="modal" class="btn ca-g-btn"><?= _campaign("Okay")?></button>
		</div>
	</div>
</script>

<script id="wechat_templates_collection_tpl" type="text/templates">
	<div class="ca-templates-collection-view">
		<div id="ca_templates_collection_div" class="ca-templates-collection-div">
			<div class="all_wait_loader"></div>
			<div id="ca_lang_based_parent_container"></div>
			<div class="ca_top_view_container">

				<div class="ca-template-scope ">
					<div class="ca-layout-header"><?= _campaign("Select a template")?></div>
				    <select name="template_scope" id="template_scope">
				    	<option value="WECHAT_TEMPLATE"><?= _campaign('Template Message')?></option> 
						<option value="WECHAT_SINGLE_TEMPLATE" selected="true"><?= _campaign('Single Image Broadcast')?></option> 
				       	<option value="WECHAT_MULTI_TEMPLATE"><?= _campaign('Multi Image Broadcast')?></option> 
				  	</select>
    			</div>

				<div class="ca-container-header hide">
					<div class='ca-template-option'>
						<a class='ca-all sel all_template'><?= _campaign('All') ?></a>
						<a class='ca-favourites favourite_template' ><?= _campaign("Favourites")?></a>
				    	</div>
				    <div class='ca-search'>
				        <div class='ca-search-container'>
				            <i class='c-search-icon'></i>
				            <input type='text' class='ca-search-text ca_search' placeholder= "Search for templates">
				        </div>
				    </div>
				</div>

				<div class="ca-container-body ca_container_body">
					<div class="ca_all_container_body">
						<div class="ca_spic_container_body"></div>
						<div class="ca_template_messages_container_body"></div>
						<div class="ca_mpic_container_body hide"></div>
					</div>
					<div class="ca_favourite_container_body"></div>
					<div class="ca_search_container_body" ></div>

					<div class="ca_complete_msg ca-complete-msg" style="display:none"><?= _campaign("That is all we have") ?></div>
					<div style="position:relative; height:100px" >
						<div class="ca_loader" style="display:none"></div>
					</div>
				</div>

				<div class="ca_image_preview_container_body ca-image-preview-container">
				</div>

			</div>
			<div class="ca_top_edit_container"></div>
		</div>
	</div>
</script>

<script id="templates_collection_tpl" type="text/template">
	<div id="ca_templates_collection_div" class="ca-templates-collection-div">
		<div class="all_wait_loader"></div>
		<div id="ca_lang_based_parent_container"></div>
		<div class="ca_top_create_new_container hide">
			<div class="ca-dib" style="display:none">
				<button class="back_view ca-grey-btn"> < <?= _campaign("Back")?></button>
			</div>
			<div class="ca-layout-header" style="display:none"><?= _campaign("Select or upload a layout to begin")?></div>
			<button type="button" class="ca-g-btn ca-new-btn btn dropdown-toggle" data-toggle="dropdown" aria-expanded="false" style="display:none"><?= _campaign("Upload")?>
				<span class="caret ca-fright"></span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<li><a class="upload_html_file"><?= _campaign("HTML")?></a></li>
				<li><a class="upload_zip_file"> <?= _campaign("Zip")?></a></li>
			</ul>
			<div class="ca_create_new_container ca-create-new-container">
			</div>
			<div style="position:relative; height:100px" >
				<div class="ca_default_loader" style="display:none"></div>
			</div>
		</div>

		<div class="ca_top_view_container">
			<div class="ca-layout-header"><?= _campaign("Select or Create a template")?></div>
			<div class="ca-container-header">
				<div class='ca-template-option'>
					<a class='ca-all sel all_template'><?= _campaign('All') ?></a>
					<a class='ca-favourites favourite_template' ><?= _campaign("Favourites")?></a>
			    </div>

			    <div class='ca-search'>
			        <div class='ca-search-container'>
			            <i class='c-search-icon'></i>
			            <input type='text' class='ca-search-text ca_search' placeholder= "Search for templates">
			        </div>
			    </div>

			    <div class="ca-new-btn">
			    	<% if(template_type == 'image') {%>
			    	<button type="button" class="ca-g-btn btn upload_image_file" > <?= _campaign("Upload Image")?> </button>	
					
					<%} else {%>

					<div class="btn-group" role="group">
						<a class="create_from_scratch">
							<button type="button" class="ca-g-btn ca-new-btn btn" aria-expanded="false"><?= _campaign("Create new template")?>
							</button>
						</a>
						<div class="hide"> 
							<input type="file" id="file_upload" name="file_upload"/>
			    		</div>
					</div>
						
					<% } %>
			    </div>
			</div>

			<div class="ca-container-body ca_container_body">
				<div class="ca_all_container_body">
				</div>
				<div class="ca_favourite_container_body">
				</div>
				<div class="ca_search_container_body" >
				</div>
				
				<div class="ca_complete_msg ca-complete-msg" style="display:none"><?= _campaign("That is all we have") ?></div>
				<div style="position:relative; height:100px" >
					<div class="ca_loader" style="display:none"></div>
				</div>
			</div>
			
			<div class="ca_image_preview_container_body ca-image-preview-container">
			</div>

		</div>

		<div class="ca_top_edit_container">
		</div>
		
		<div style="display:none">
			<form id="image_upload" name="image_upload">
				<input type="file" name="upload_image" id="upload_image"/>
			</form>
		</div>
	</div>
</script>

<script id="template_tpl" type="text/template">
 	<div>
		<div class="ca_preview_holder ca-preview-holder">
			<img src="<%=preview_url%>" alt="<?= _campaign('Preview is being generated...')?> "/>
			<div class='ca-preview-holder-footer'>
				<div class="ca-multi-language">
					<span class="linked-templates-icon"></span>
					<span class="linked-templates-count"><%=linked_templates%></span>
				</div>
			</div>
		</div>
		<div class="ca_favourite_icon ca-favourite-icon">
		<% if(is_favourite){ %>
			<i class="icon-heart"></i>
		<%} else {%>
			<i class="icon-heart-empty" ></i>
		<% } %>
		</div>
	</div>
	<div>
		<div class="ca-template-name" title="<%=name %>"><%=name %></div>
		<% if(is_drag_drop){ %>
			<span class="ca-edm-icon" data-toggle="tooltip" data-placement="bottom" title="<?= _campaign('Template is Drag-Drop compatible')?>">
				<i class="drag-drop-icon"></i>
			</span>
		<% } %>
	</div>
</script>

<script id="img_template_tpl" type="text/template">
	<div>
		<div class="ca_preview_holder ca-img-preview-holder" title="<%=name%>">
			<img src="<%=preview_url%>" alt="<?= _campaign('Preview is being generated...')?>" />
		</div>
		<div class="ca_favourite_icon ca-favourite-icon">
		<% if(is_favourite){%>
			<i class="icon-heart"></i>
		<%} else {%>
			<i class="icon-heart-empty" ></i>
		<% } %>
		</div>
		<div>
			<div class="ca-template-name" title="<%=name%>" ><%=name %></div>
		</div>
</script>

<script id="wechat_template_messgage_tpl" type="text/template">
	<div class="wechatTemplateMessage" template-id ="<%=model.template_id%>">
		<div class="templateContainer" style="font-size: 10px;">
			<%= data_content %>
		</div>
		<div class="templateName" style="width:97px" title= <%-model.template_name%> >
			<%- model.template_name %>
		</div>
		<div class="option_view">
	    	<div class="btn-group btn-tablecell">
	    		<a class="c-button-grey template_selected" context="creative_assets" template-name='<%- model.template_name %>' data-animation="1" data-goto="4" type="WECHAT_TEMPLATE"><?= _campaign('Select')?></a>
	      	</div>
	    </div>
	</div>
</script>

<script id="preview_wechat_template_messages" type="text/template">
	<div>
      <div class="c-sel-temp c-float"><?= _campaign('Template Name:')?></div>
      <div class="ca-dibm123">
        <div style="background: #efefef;">
        	<%= model.template_name%>
        </div>
      </div>
    </div>
    <div style="clear:both"></div>
	<div class = "c-show-template"></div>
</script>

<script id="details_tpl" type="text/template">
	<%
		var count = 0;
	    count = keylist.length;
  	%>
    <div class = 'c-margin-left'>
  	<% _.each(keylist,function(key,val){ %>
    <% if(key.key=='first'){%>
    	<span style="clear:both; float:left; margin: 18px 0px 0px 0;"><%='Header*'%></span>
    <% }else if(key.key=='remark'){ %>
    	<span style="clear:both; float:left; margin: 18px 0px 0px 0;"><%='Footer*'%></span>
    <% }else{ %>
    	<span style="clear:both; float:left; margin: 18px 0px 0px 0;"><%-key.key%><%='*'%></span>
    <% } %>
  	<% }); %>
  	</div>
  	<div class = "c-show-template-details">
    	<div class="c-margin-bottom" style="white-space: nowrap;" title="<%- temp['Title'] +'-'+ temp['TemplateId'] %>">
      		<%- temp['Title'] +'-'+ temp['TemplateId'] %>
    	</div>
    	<% _.each(keylist,function(key,val){%>

      		<% if(key.key=='first'){ %>
	    	<div>
	        	<input type="text" class="c-input-width c-first-data c-input-tag-box c-tag-<%-key.key%>" name="FirstName" wechat-tag-data="<%-key.key%>" placeholder="<?= _campaign('Enter First Data Here');?>"  value="<%-key.val%>" disabled>
	      	</div>

      		<% }else if(key.key=='remark'){ %>
      		<div>
        		<input type="text" class="c-input-width c-remarks-data c-input-tag-box c-tag-<%-key.key%>" name="RemarkName" wechat-tag-data="<%-key.key%>" placeholder="<?= _campaign('Enter Remark Data Here');?>" value="<%-key.val%>" disabled>
      		</div>

		    <% }else{ %>
      		<div>
      			<div style = "width:100%;" class="c-tag-<%-key.key%> c-selected-tag-box" wechat-tag-data="<%-key.key%>">
              		<input type="text" class="c-input-width" id="<%- key.key %>" value="<%- key.val %>" disabled>
      			</div>
      		</div>
			<% } %>
    	<% }); %>
  	</div>

  	<div class = "c-show-url">
    	<span style="clear:both; float:left; margin: 18px 0px 0px 22px;text-align: center"><%='Link to details page'%></span>
    	<input type="text" class="c-input-width c-url-data-style c-input-tag-box c-tag-url" name="UrlName" wechat-tag-data="url" placeholder="http://" value="<%- url %>" style="width: 500px;margin: 14px 0 0 52px;" disabled/>
  	</div>

  	<div class = "c-show-url">
    	<span style="clear:both; float:left; margin: 8px 0px 0px 22px;"><%='Is this internal url'%></span>
    	<input type="checkbox" style="margin-left: 59px;margin-top: 13px;" disabled <%- (isInternalUrl == 1)? 'checked': '' %>/>
  	</div>
</script>


<script id="wechat_single_tpl" type="text/template">
	<div class="wechatSingleTemplate" template-id ="<%=model.template_id%>" qxun-template-id="<%=model.content.qXunTemplateId%>">
    	<div class="templateContainer">
	      	<div class="title"><%=model.title%></div>
		    <div class="imageContainer">
		    	<img src="<%=model.image%>">
		    </div>
    	</div>
	    <div class="templateName hide"><%=model.template_name%></div>
	    <div class="option_view">
	    	<div class="btn-group btn-tablecell">
	    		<a class="c-button-grey template_selected" context="creative_assets" template-name='<%- model.title %>' data-animation="1" data-goto="4" type="WECHAT_SINGLE_TEMPLATE"><?= _campaign('Select')?></a>
	      	</div>
	    </div>
  	</div>
</script>

<script id="wechat_multipic_tpl" type="text/template">
	<div class="wechatMultiTemplate <%=(model.isPreview) ? 'previewMultiPic' : ''%>" qxun-template-id="<%=model.content.qXunTemplateId%>" single-image-template-id="<%=model.content.TemplateIds%>" template-id="<%= model.template_id %>" article-id="<%=model.content.ArticleIds%>">
  		<div class="templateContainer">
  			<div class='templateName'><%=model.template_name%></div>
  			<div class="singlePicContainer">
  			<% _.each(model.singlePicData,function(value,key) { %>
    			<div class="singlePic">
      				<div class="title"><%=value.title%></div>
      				<div class="imageContainer">
      					<img src="<%=value.image%>">
      				</div>
    			</div>
  			<% });%>
  			</div>
  		</div>
	<% if(!model.isPreview) { %>
 		<div class="option_view">
      		<div class="btn-group btn-tablecell">
      			<a class="c-button-grey template_selected" context="creative_assets" template-name='<%- model.template_name %>' data-animation="1" data-goto="4" type="WECHAT_MULTI_TEMPLATE"><?= _campaign('Select')?></a>
      		</div>
 		</div>
 	<% } %>

 	<% if(renderLink){ %>
	 	<div class = "c-show-url">
	    	<span style="clear:both; float:left; margin: 30px 0px 0px -1px;text-align: center"><%='Link to details page'%></span>
	    	<input type="text" class="c-input-width c-url-data-style c-input-tag-box c-tag-url" name="UrlName" wechat-tag-data="url" placeholder="http://" value="<%- url %>" style="width: 500px;margin: 25px 0 0 24px;" disabled/>
	  	</div>

	  	<div class = "c-show-url">
	    	<span style="clear:both; float:left; margin: 8px 0px 0px 12px;"><%='Is this internal url'%></span>
	    	<input type="checkbox" style="margin-left: 25px;margin-top: 13px;" disabled <%- (isInternalUrl == 1)? 'checked': '' %>/>
	  	</div>
	<% } %>
 	</div>
</script>

<script id="template_message_preview_template" type="text/template">
	<div class="modal fade template-preview-modal" id="template_message_preview_modal" style="width:22%; left: 59%">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-body">
            <div class="mobile-preview-icon">
              <div class="preview_container preview_container_margin">
              </div>
            </div>
          </div>
          <div class="modal-footer">  
            <button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Cancel")?> </button>
          </div>
        </div>
      </div>
    </div>
</script>

<script id="single_image_preview_template" type="text/template">
	<div class="modal fade in hide" id="single_image_preview_modal">
  		<div class="modal-header">
      		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    		<h3 id="camp_msg_preview_header"><?= _campaign('Preview') ?></h3>
  		</div>
  		<div class="modal-body">
   			<div class='auth-bananaphone'>
	      		<div class="wechat-msg-preview-container">
		        	<div class="wechat-msg-title"><%=model.title%></div>
		        	<div class="wechat-msg-image"><img src="<%=model.image%>"/></div>
		        	<div class="wechat-msg-summary"><%=model.summary%></div>
	      		</div>
	      		<div class="wechat-msg-content-container hide">
	        		<div class="close">X</div>
	        		<iframe src = "<%='data:text/html;charset=utf-8,' + (model.content.content)%>"></iframe>
	      		</div>
    		</div>
  		</div>
		<div class="modal-footer">
			<a data-dismiss="modal" class="btn"><?= _campaign('Close') ?></a>
		</div>
	</div>
</script>

<script id="preview_wechat_single_tpl" type="text/template">
	<div class="singleImageTemplatecontainer">
		<div class="templateForm">
    		<div id="template-name"><?= _campaign('Template Name')?> : <%=model.template_name%></div>
    		<div class="shellContainer">
      			<div class="shellLeft">
			        <div style="margin-top:15px;"><?= _campaign('Title')?></div>
			        <div style="margin-top:25px;"><?= _campaign('Cover Image')?></div>
			        <div style="margin-top:195px;"><?= _campaign('Summary')?></div>
      			</div>
      			<div class="shell">
        			<div class="shellBorder">
          				<div>
            				<input type="text" value="<%=model.title%>" readonly maxlength="64" id="template_title" placeholder="<?= _campaign('Enter title here')?>" />
          				</div>
			        	<div class="uploadPic upload_image_file">
				            <div>
				            	<img src="<%=model.image%>"/>
				            </div>
				            <div class="hide">
				            	<i class="fa fa-cloud-upload" aria-hidden="true"></i>
				            	<?= _campaign('Upload Pic')?>
				            </div>
          				</div>
          				<div class="summary">
            				<textarea type="text" rows="3" readonly="readonly" maxlength="120" placeholder="<?= _campaign('Enter summary here')?>" id="template_summary"><%=model.summary%></textarea>
          				</div>
        			</div>
      			</div>
        		<div class="shellright">
        			<div style="margin-top:15px;">
        				0/64 <?= _campaign('characters')?>
        			</div>
        			<div style="margin-top:25px;">
        				<?= _campaign('Recommended')?>
        				<br/>
        				<?= _campaign('Resolution')?> : 360 x 200 <?= _campaign('px')?>
        				<br/>
        				<?= _campaign('size')?>: 64kb
        			</div>
        			<div style="margin-top:155px;">
        				0/120 <?= _campaign('characters')?>
       				</div>
        		</div>
        		<div style="display: none;">
        			<form id="image_upload" name="image_upload">
            			<input type="file" name="upload_image" id="upload_image">
          			</form>
        		</div>
      		</div>
    		<div id="template-link">
        		<div style="display: inline-block;margin-left: 70px;"><?= _campaign('Content')?></div>
		        <div id="wechat_content" name="wechat_content" style="margin: 0 20px;display: inline-block;vertical-align: top;">
		        	<iframe src = "<%='data:text/html;charset=utf-8,' + (model.content.content)%>"></iframe>
		        </div>
      		</div>
      		<div class = "c-show-url">
		    	<span style="clear:both; float:left; margin: 18px 0px 0px 6px;text-align: center"><%='Link to details page'%></span>
		    	<input type="text" class="c-input-width c-url-data-style c-input-tag-box c-tag-url" name="UrlName" wechat-tag-data="url" placeholder="http://" value="<%- url %>" style="width: 500px;margin: 14px 0 0 29px;" disabled/>
		  	</div>

		  	<div class = "c-show-url">
		    	<span style="clear:both; float:left; margin: 8px 0px 0px 19px;"><%='Is this internal url'%></span>
		    	<input type="checkbox" style="margin-left: 30px;margin-top: 13px;" disabled <%- (isInternalUrl == 1)? 'checked': '' %>/>
		  	</div>
   		</div>
  	</div>
</script>

<script id="multi_image_preview_template" type="text/template">
	<div class="modal fade in hide" id="multi_image_preview_modal">
		<div class="modal-header">
  			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    		<h3 id="camp_msg_preview_header"><?= _campaign('Preview') ?></h3>
  		</div>
  		<div class="modal-body">
		    <div class='auth-bananaphone'>
		    	<div class="wechatMultiTemplate">
			        <div class="singlePicContainer">
			        <% var index = 1;
			            _.each(model.singlePicData,function(value,key) { %>
			        	<div class="singlePic" template="<%=index%>" template-id="<%=value.template_id%>" qxun-template-id="<%=value.qxun_template_id%>">
			            	<div class="title"><%=value.title%></div>
			            	<div class="imageContainer"><img src="<%=value.image%>"/></div>
			          	</div>
			          	<div class="wechat-msg-content-container hide" template="<%=index++%>">
			            	<div class="close">X</div>
			            	<iframe src = "<%='data:text/html;charset=utf-8,' + (value.content)%>"></iframe>
			          	</div>
			        <% }); %>
			        </div>
		    	</div>
		    </div>
  		</div>
		<div class="modal-footer">
			<a data-dismiss="modal" class="btn"><?= _campaign('Close') ?></a>
		</div>
	</div>
</script>

<script id="msg_template_tpl" type="text/template">
	<div class="template_view"></div>
	<div class="option_view">
		<button type="button" class="select_template ca-grey-btn" style="width:85px">
			<?= _campaign('Select')?>
		</button>
	</div>
	<div  class="modal hide fade confirm_delete_modal">
		<div class="modal-header">
			<div class="ca-modal-header"><?= _campaign("Delete Template") ?></div>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="modal-body">
			<?= _campaign("Are you sure you want to delete ?") ?>
		</div>
		<div class="modal-footer">
			<button type="button" data-dismiss="modal" class="btn ca-g-btn confirm_delete"><?= _campaign("Delete")?></button>
			<button type="button" data-dismiss="modal" class="btn"><?= _campaign("Cancel")?></button>
		</div>
	</div>

	<div>
		<div class="modal fade template-preview-modal" id="template_preview_modal" style="width: 85%; left:25%" data-keyboard="false" data-backdrop="static">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-body">
						<div class="preview_container">
							
						</div>
					</div>
					<div class="modal-footer">
					<% if(is_drag_drop){ %>
						<span class="ca-edm-icon ca-fleft" data-toggle="tooltip" data-placement="bottom" title="<?= _campaign('Template is Drag-drop compatible')?> ">
							<i class="drag-drop-icon"></i>
						</span>
						<span class="ca-drag-drop-msg"><?= _campaign("Drag-drop compatible")?></span>	
					<% } %>
						<div class="edit_button_container dab">		
							<button type="button" class="select_template btn ca-g-btn" data-dismiss="modal"> <?= _campaign("Select")?> 
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</script>

<script id="msg_edit_template_tpl" type="text/template">
  <div>
		<div class="edit_template_loader"></div>
		<div class="template_options lang_enabled_show" style="display:none">
			<div class="template_options_text" style="float:left"></div>
			<div class="template_options_list" style="float:left">
				<div class="btn-group">
					<button type="button" class="ca-grey-btn ca-option-btn btn dropdown-toggle" data-toggle="dropdown"><?= _campaign('Options') ?>
				  		  <span class="caret ca-fright"></span>
					</button>
					<ul class="dropdown-menu" role="menu">
						<li class="edit_as_html"><a ><?= _campaign('Edit as HTML')?></a></li>
						<li class="remove_language_list"><a ><?= _campaign('Remove Language')?></a></li>
						<li class="preview"><a ><?= _campaign('Preview and Test')?></a></li>
						<li class="image_gallery"><a> <?= _campaign('Insert Image')?></a></li>
						<li class="change_to_classic_editor hide"><a> <?= _campaign('Change to Classic Editor')?></a></li>
                        <li class="change_to_inline_editor hide"><a> <?= _campaign('Change to Inline Editor')?></a></li>
					</ul>
				</div>
			</div>
		</div>
		<div style="clear:both;"></div>
		
			<div class="ca-dibm ca-from-container lang_enabled_hide">
				<?=_campaign('From')?>: <span class="from_name"><%=sender_info.sender_label%> </span> < <span class="from_email"><%=sender_info.sender_from%> </span> >
				<span class="edit_from ca-cursor-pointer"><i class="icon-pencil"></i>
				</span>
			</div>
		
		
			<div class="ca-dibm ca-subject-container lang_enabled_hide" >
				<span class="ca-subject-label"><?= _campaign("Subject")?> </span>
				<span><input type="text" class="subject ca-subject-input" id="edit_template__subject" name="edit_template__subject" value="<%=subject%>"/></span>
			</div>
			<div class="ca-dibm ca-option-container lang_enabled_hide">
				<div class="btn-group">
					<button type="button" class="ca-grey-btn ca-option-btn btn dropdown-toggle" data-toggle="dropdown"><?= _campaign("Options")?>
				  		  <span class="caret ca-fright"></span>
					</button>
					<ul class="dropdown-menu" role="menu">
						<% _.each(options,function(option,key){%>
							<li><a class="<%-option.className%>"><%-option.label%></a></li>
						<%});%>

					</ul>
				</div>
			</div>
	</div>
	<div class="ca_edit_template_container ca-edit-template-container">

	</div>
	<div>
		<div class="modal fade" id="edit_name_modal">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<div class="ca-modal-header"> <?= _campaign("Name Template") ?> </div>
						<button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						
						<input type="text" id="rename_template" value="<%- model.name %>_copy" />
					</div>
					<div class="modal-footer">	
						<button type="button" class="btn ca-g-btn" id="save_template"><?= _campaign(" Save")?> </button>
						<button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Cancel ")?></button>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<div class="modal fade template-preview-modal" id="edit_template_preview_modal" style="width:85%; left: 25%">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-body">
					<div class="preview_container">

					</div>
				</div>
				<div class="modal-footer">	
					<button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Cancel")?> </button>
				</div>
			</div>
		</div>
	</div>

	<div  class="modal hide fade confirm_save_modal">
		<div class="modal-header">
			<div class="ca-modal-header"><?= _campaign("Save to Original Template")?></div>
			<button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="modal-body">
			<p class="non_edm_msg"> <?= _campaign("The changes made to the template will be permanent, and will be available in other campaigns.")?></p>
			<p class="edm_msg" style="display:none"><?= _campaign("The template is drag-drop compatible. By saving the changes, easy email design options such as drag-drop will be no longer available when this is reused in other campaigns.")?></p>
			<p> <?= _campaign("Note that all tags from this template may not be available during re-use, as they depend on the configuration of the campaign.")?></p>
		</div>
		<div class="modal-footer">
			<button type="button" data-dismiss="modal" class="btn ca-g-btn confirm_save" id="confirm_save"><?= _campaign("Proceed")?></button>
			<button type="button" data-dismiss="modal" class="change_edm_save save_new_template btn ca-grey-btn" style="display:none"><?= _campaign("Save as New Template")?></button>
			<button type="button" data-dismiss="modal" class=" btn ca-grey-btn"><?= _campaign("Cancel")?></button>
		</div>
	</div>

	<div  class="modal hide fade confirm_edit_modal">
		<div class="modal-header">
			<div class="ca-modal-header"><?= _campaign("Edit as Html")?></div>
			<button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="modal-body">
			<p>
			<?= _campaign("On choosing Edit as HTML, easy email design options such as drag-drop will be no longer available for this template.")?>
			</p>
			<p> 
				<label>
					<input type="checkbox" class="confirm_box"> <?= _campaign("I understand that this cannot be reversed.")?>
				</label>
			</p>
		</div>
		<div class="modal-footer">
			<button type="button" data-dismiss="modal" disabled=true class="btn ca-g-btn confirm_edit" id="confirm_edit"><?= _campaign("Continue")?></button>
			<button type="button" data-dismiss="modal" class="btn"><?= _campaign("Cancel")?></button>
		</div>
	</div>
</script>

<script id="edit_template_tpl" type="text/template">
	<div style="display:none;">
		<iframe id="iedit_template__template_holder"> </iframe>
	</div>
	<div class="ca-edit-left-panel">
		<ul class="nav nav-tabs" role="tablist">
		    <li role="presentation" class="active">
		    	<a href="#tags" aria-controls="tags" role="tab" data-toggle="tab"><?= _campaign("Tags")?></a>
		    </li>
		    <li role="presentation">
		    	<a href="#inserts" aria-controls="inserts" role="tab" data-toggle="tab"><?= _campaign("Inserts")?></a>
		    </li>
		    <li role="presentation">
		    	<a href="#social" aria-controls="social" role="tab" data-toggle="tab"><?= _campaign("Social")?></a>
		    </li>
	  	</ul>
	  	<div class="tab-content">
	    	<div role="tabpanel" class="tab-pane active" id="tags">
	    		<div class="tags-container">
					<ul>
					<% 
					template_function = function(tag){
						var html_val = '';
						if(tag.children){
							html_val += '<li class="parent" ><span><i class="drop-icon"></i><a>'+tag.name+'</a></span><ul style="display:none">';
							_.each(tag.children, function(t){
								html_val += template_function(t)
							})
							html_val += '</ul>'
						} else if(tag.val){
							html_val += '<li class="tag-list"><span><a class="insert_tag" tag-data='+ tag.val +' >'+tag.name+'</a><span>';
						}
						html_val += '</li>'
						return html_val;
					} 
					_.each(tags, function(tag) {
						print(template_function(tag));
					})%>
					</ul>
				</div>
	    	</div>
			<div role="tabpanel" class="tab-pane" id="inserts">
				<div class='template-options' id='custom-inserts'>
				<% if( _.size(inserts.html) > 0 ) { %>
					<div class='content-box-small' type='HTML' template-id='<%= inserts.html.template_id %>'>
						<img class='add-image' src='<%= inserts.html.is_preview_generated == 1 ? inserts.html.content : "" %>' alt='<%= inserts.html.is_preview_generated == 1 ? inserts.html.template_name : inserts.html.content %>' />
					</div>
					<span class='small-content-title'><%= inserts.html.template_name %></span>
				<% } %>
				<% _.each(inserts.surveys,function(survey , i){ %>
					<div class='content-box-small' type='SURVEY' template-id='<%= survey.form_id %>'>
						<img class='add-image' src='<%= survey.preview_url %>' alt='<%= survey.form_name %>'/>
					</div>
					<span class='small-content-title'><%= survey.form_name %></span>
				<% }); %>

				<% _.each(inserts.images,function(image , i){ %>
					<div class='content-box-small' type='IMAGE'>
						<img class='add-image' src='<%= image.content %>' alt='<%= image.template_name %>'/>
					</div>
					<span class='small-content-title'><%= image.template_name %></span>
				<% }); %>
				</div>
			</div>
		    <div role="tabpanel" class="tab-pane" id="social">
		    	<div class='template-options' id='custom-social'>
					<%= social %>
				</div>
				<div id='add-social-media' class='hide arrow_box'>
					<input type='text' name='url' id='url' placeholder=<?= _campaign("enter social url") ?> class='margin-top'/><br/>
					<input type='checkbox' name='set_url' id='set_url' /> <?= _campaign("Save This URL")?><br/>
					<a class='btn btn-inverse' id='cancel_url'><?= _campaign("Cancel")?></a>
					<a class='btn creative-assets-button-active active' id='save_url'><?= _campaign("Add Url")?></a>
				</div>
		    </div>
	    </div>
	</div>
	<div class="hide dynamicTags" id="dTags" style="top: 224px;">
		<input type="text" id="dynamicTextbox">
			<label class="dynamicLabel"> <?= _campaign('days from message send')?></label>
			<a class="c-button-grey plain-text-preview pull-right margin-right" id="dynamicButton"><?= _campaign('Insert Tag')?></a>
	</div>
	<div class="ca-edit-right-panel"></div>
</script>

<script id="inline_ck_editor_content_tpl" type="text/template">
	<div class="source" style="position:fixed;top:5px;right:10px;width:25px;height:25px;background-image: url('/images/code-128.png'); background-size: 100% 100%;z-index:10000;cursor:pointer;" title="<?= _campaign('source')?>">
	</div>
	<div>
	<div class='ca_template_editor' style='background: #FFF;min-height: 287px;margin:5% 5%;'>
	</div>
</script>

<script id="inline_ck_editor_tpl" type="text/template">
  <div>
	<iframe class="iedit_template__template" style="width: 100%; height: 450px">
	</iframe>
	</div>
</script>
<script id="classic_ck_editor_tpl" type="text/template">
  <div><textarea class="edit_template__template"></textarea></div>
</script>
<script id="edm_editor_tpl" type="text/template">
  <div id="edm_editor" class="ca-full">
		<iframe id="edm_editor_iframe" class="ca-full edm_editor_iframe" style="border:none"></iframe>
	</div>
</script>
<script id="create_template_tpl" type="text/template">
  <div class="ca_preview_holder ca-preview-holder ca-dib">
		<img src="<%=preview_url%>" alt="<?= _campaign('Preview is being generated...') ?> "/>
	</div>
</script>
<script id="image_gallery_tpl" type="text/template">
  <div class="ca-image-collection-container">
	
	</div> 
	<div class="ca-image-preview-container">
	
	</div>
</script>
<script id="container_email_preview_tpl" type="text/template">
  <div>
		<div class="ca-preview-header" style="width:100%">
			<div class="preview-title" style="float:left"><span class="preview_favourite ca-cursor-pointer"> <% if(modelData.is_favourite){%>
					<i class="icon-heart"></i>
				<%}else {%>
					<i class="icon-heart-empty" ></i>
				<%}%></span><%=modelData.name %> 
				<?= _campaign('Preview') ?>
			</div>
			<button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
			<span aria-hidden="true">&times;</span>
			</button>
		</div>

		<div id="lang_tab_preview_parent" style="margin-bottom:2.2%">
			<ul class='lang_tab_preview lang_list_tabs' style="list-style-type:none">
				<li id='language_button__<%=base_language_id%>' class='language_based_preview_tab tab_selected'><%=languages[base_language_id] %></li><% _.each(template_lang_ids,function(lang_id){ %>
					<li id='language_button__<%=lang_id%>' class='language_based_preview_tab'><%=languages[lang_id]%></li><% });%>

		</ul>
		</div>	
		<div class="language_content_parent" style="border:1px solid black">
			<div id='language_content__<%=base_language_id%>' class='language_based_preview_content' >
			</div>
			<% _.each(template_lang_ids,function(lang_id){ %><div id='language_content__<%=lang_id%>' class='language_based_preview_content'></div><% });%>
		</div>	
	</div>
</script>
<script id="email_preview_tpl" type="text/template">
  <div class="email_preview_btn_parent">
		<div class="ca-email-preview-button-bar">
			<div class="btn-group">
				<button type="button" class="btn_email_desktop btn btn-padding btn-inverse active"><?= _campaign("Desktop")?></button>
				<button type="button" class="btn_email_tablet btn btn-padding btn-default"><?= _campaign("Tablet")?></button>
				<button type="button" class="btn_email_mobile btn btn-padding btn-default"><?= _campaign("Mobile")?></button>
			</div>
		</div>
		<button type="button" class="close" data-dismiss="modal" style="display:none" aria-label=<?= _campaign("Close") ?>>
			<span aria-hidden="true">&times;</span>
		</button>
	</div>
	<div class="">
		<div class="email_desktop flexcroll">
			<iframe id="template_email_iframe_preview" class="email-iframe-preview flexcroll"></iframe>
		</div>
		<div class="email_mobile flexcroll hide">
			<div class="ca-mobile">
				<iframe id="template_preview_iframe_mobile_portrait" class="flexcroll"></iframe>
			</div>
			
			
		</div>
		<div class="email_tablet flexcroll hide">
			
			<div class="ca-ipad">
				<iframe id="template_iframe_mobile_landscape" class="flexcroll"></iframe>
			</div>
		</div>

	</div>
</script>
<script id="preview_image_tpl" type="text/template">
  <div class="ca-img-inner-container">
		<div class="ca-image-container" title="<%=model.name%>">
			<img src="<%=model.image_url%>" style="width:<%=info.width%>px;height:<%=info.height%>px"  /> 
		</div>
		<div class="ca-image-controls">
			<div class="ca-image-info img_div">
				<div><?= _campaign('Image Name') ?>: <%=info.name%> </div>
				<div><?= _campaign('Dimension') ?>: <%=info.orgWidth%> * <%=info.orgHeight%></div>
				<div><?= _campaign('Image Size') ?>: <%=model.file_size%> <?= _campaign("KB")?></div>
			</div>
			<div class="ca-image-nav">
				<button class="btn btn-default prev_image "> < </button>
				<button class="btn btn-default next_image"> > </button>
			</div>
		</div>
		<div class="ca-image-action">
			<% if(option =='delete') {%>
			<button class="btn btn-default delete_img"><?= _campaign("Delete")?></button>
			<%}else {%> 
			<button class="btn ca-g-btn insert_img" data-dismiss="modal"><?= _campaign("Insert")?></button>
			<%}%>
		</div>
	</div>
</script>

<script type="text/template" id="mobilepush_templates_collection_tpl">
	<div class="ca-templates-collection-view">
		<div id="ca_templates_collection_div" class="ca-templates-collection-div">
			<div class="all_wait_loader"></div>
			<div id="ca_lang_based_parent_container"></div>
			<div class="ca_top_view_container">
				<div class="creative_assets_header">
					<h3 style="margin-bottom: 10px;">
						<?= _campaign("Creative Assets Template")?>
					</h3>
				</div>
				<div class="ca-template-scope ">
					<div class="ca-layout-header"><?= _campaign("Select a template")?></div>
				    <select name="template_scope" id="template_scope">
				       <option value="MOBILEPUSH_TEMPLATE" selected="true"><?= _campaign('Text Template')?></option> 
				       <option value="MOBILEPUSH_IMAGE"><?= _campaign('Image Template')?></option> 
				  	</select>
	  				<div class='ca-template-option'>
						<a class='ca-all sel all_template'><?= _campaign('All') ?></a>
						<a class='ca-favourites favourite_template' ><?= _campaign("Favourites")?></a>
		    		</div>
				    <div class='ca-search'>
				        <div class='ca-search-container'>
				            <i class='c-search-icon'></i>
				            <input type='text' class='ca-search-text ca_search' placeholder= "Search for templates">
				        </div>
				    </div>
    			</div>
				<div class="ca-container-body ca_container_body">		
					<div class="ca_all_container_body">
						<div class="ca_spic_container_body"></div>
						<div class="ca_mpic_container_body hide"></div>
					</div>
					<div class="ca_favourite_container_body"></div>
					<div class="ca_search_container_body" ></div>
					<div class="ca_complete_msg ca-complete-msg" style="display:none">
						<?= _campaign("That is all we have") ?>
					</div>
					<div style="position:relative; height:100px" >
						<div class="ca_loader" style="display:none"></div>
					</div>
				</div>
				<div class="ca_image_preview_container_body ca-image-preview-container">
				</div>
			</div>
			<div class="ca_top_edit_container"></div>
		</div>
	</div>
</script>

<script type="text/template" id="mobilepush_tpl">
	<div class="ca_mobile_push">
		<div class="ca_preview_holder_mobile_push">

		<%if(model.name) {%>
	    	<div class="ca-template-name-mobile" title = <%-model.name%> >
	    		<span class="ca-template-name-mobile-text"><%-model.name %></span>
	     	</div>
	  	<%} else { %>
	    	//It should not come here
    		<div class="ca-template-name" title = "Random Name" >"<?= _campaign('Random Name')?>"</div>
    	<% } %>
  			<div class="ca_preview_mobile">  
    			<div class="ca_preview_holder_mobile ca-img-preview-holder">
      			<% if(!_.isEmpty(model.html_content.ANDROID)){ %>
				    <div class="mobile_push_title">
				        <%= model.html_content.ANDROID.title %>
				    </div>
				    <div class="mobile_push_msg">
				        <%= model.html_content.ANDROID.message %>
				    </div>
    			<% }else{ %>
    				<span class="no-push-available"><?= _campaign("No Android Push")?></span>
    			<% } %> 
    				<div class="mobile-icon"> <img src="/images/android-logo.png" alt="<?= _campaign("android") ?>">
    					<i class="icon-android"></i>
    				</div>
    			</div>

			    <div class="ca_preview_holder_mobile ca-img-preview-holder">
    			<% if(!_.isEmpty(model.html_content.IOS)){ %>
				    <div class="mobile_push_title">
				        <%= model.html_content.IOS.title %>
				    </div>
				    <div class="mobile_push_msg">
				        <%= model.html_content.IOS.message %>
				    </div>
    			<% }else{ %>
    				<span class="no-push-available"><?= _campaign("No IOS Push")?></span>
    			<% }  %>
    				<div class="mobile-icon"><img src="/images/apple.png" alt="<?= _campaign("ios") ?>">
    					<i class="icon-apple"></i>
    				</div>
    			</div>
			</div>

		    <div class="modal fade template-preview-modal" id="edit_template_preview_modal" >
    			<div class="modal-dialog">
    				<div class="modal-content">
        				<div class="modal-body">
        					<div class="c-status-option preview_type">
                        		<a class="c-status-live sel preview-mobile-push" id="android-preview"><?= _campaign("Android")?></a>
                        		<a class="c-status-upcoming preview-mobile-push" id="ios-preview"><?= _campaign("IOS")?></a>
                    		</div>
	        				<div class="mobile-preview-icon-android">
	            				<div class="preview_container preview_container_margin_mobilepush"></div>
	        				</div>
					        <div class="mobile-preview-icon-ios" style="display:none">
					        	<div class="preview_container preview_container_margin_mobilepush"></div>
					        </div>
        				</div>
				        <div class="modal-footer">  
				        	<button type="button" class="btn btn-default" data-dismiss="modal">
				        		<?=_campaign("Cancel")?>
				        	</button>
				        </div>
    				</div>
    			</div>
    		</div>
		</div>

		<div class="mobile_push_btn">
   			<a class="c-button-grey template_selected" id="mobile_template_selected" data-animation="2" data-goto="4" mobile_template_id = "<%= model.template_id %>"" > <?= _campaign("Select")?> </a>
  			<a class="c-button c-button-next hide" id="next-button-action" data-animation="1" data-goto="2"><?= _campaign("Preview") ?></a>
  		</div>
  		<div class="modal hide fade confirm_delete_modal">
    		<div class="modal-header">
      			<div class="ca-modal-header"><?= _campaign("Delete Template") ?></div>
			    <button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
			        <span aria-hidden="true">&times;</span>
			    </button>
    		</div>
		    <div class="modal-body">
		      <?= _campaign("Are you sure you want to delete ?") ?>
		    </div>
		    <div class="modal-footer">
			    <button type="button" data-dismiss="modal" class="btn ca-g-btn confirm_delete"><?= _campaign("Delete")?></button>
			    <button type="button" data-dismiss="modal" class="btn"><?= _campaign("Cancel")?></button>
		    </div>
		</div>
	</div>
</script>


<script id="ca_mobile_push_template_tpl" type="text/template">
<div id="ca-full">
<div class="singleImageTemplatecontainer">
	
	<div class="ca_edit_template_container ca-edit-template-container  mobile-push-container">
		<div class="ca-edit-left-panel">
			<div class="ca-layout-header shellpadding"><?= _campaign("Mobile Push Template") ?></div>
			<div>
			<div class="tags-container">
	            <ul>
	            <% 
	            template_function = function(tag){
	              var html_val = '';
	              if(tag.children){
	                html_val += '<li class="tag-list tag-margin" ><span class="mobile-tag-icon"><i class="icon-caret-right"></i></span><span><a class="parent">'+tag.name+'</a></span><ul style="display:none">';
	                _.each(tag.children, function(t){
	                  html_val += template_function(t)
	                })
	                html_val += '</ul>'
	              } else if(tag.val){
	                html_val += '<li class="tag-list"><span><a class="insert_tag" tag-data='+ tag.val +' >'+tag.name+'</a></span>';
	              }
	              html_val += '</li>'
	              return html_val;
	            } 
	            _.each(data.tags, function(tag) {
	              print(template_function(tag));
	            })%>
	            </ul>
        	</div>
			</div>
		</div>
		<div class="ca-edit-right-panel">
		<div>
			<ul class="nav nav-tabs mobile-push-tabs">
		      <li id="mob_android" class="active"><a class="display-headtitle" id="android"><?= _campaign("Android")?></a></li>
		      <li id="mob_ios"><a class="display-headtitle" id="ios"><?= _campaign("IOS")?></a></li>
		    </ul>
		</div>
		 <div id="mob_android_container"></div>
      	<div id="mob_ios_container" style="display:none"></div>
    
		</div>
	</div>
	</div>
 </div> 
</script>

<script type="text/template" id="IOS_secondary_detail_tpl">
  <div class="IOS-secondary-detail-block">
  <div class="secondary-CTA">
    <div style="float:right;margin: 10px;">
    <a class="ca-mobile-push-delete-container"><i class="icon-trash ca-mobile-icon-ios"></i></a> 
    </div>
     <div class="display-headtitle ios-cta-name"><%=  ios_name%></div>
     <input type="hidden" id="ios_category_id" value="<%=  categoryId  %>">

     <% 
     console.log("CtasObj :",CtasObj);
     _.each(CtasObj,function(option,key){
     console.log("option madhu:",option);

      if(!_.isEmpty(option)){
        if(key == 'name' ){
        return false;
     } %>
      <% if(option.launch_app =="false" || option.launch_app == false){ %>        
         <div class="ca-mobile-push-ios-container" style="display:none;" ca-mobile-push-key=<%= key %>>
      <% } else {%>
         <div class="ca-mobile-push-ios-container secondary-cta-show" ca-mobile-push-key=<%= key %>>
      <% } %>   
      <div class="ca-mobile-push-reset-container" >    
      <input type="hidden" id="ca-mobile-push-ios-launchapp-<%= key %>" value="<%= option.launch_app %>">        
      <input type="hidden" id="ca-mobile-push-ios-<%= key %>"  value="<%= option.item_id %>">
               <div class="display-headtitle ca-mobile-push-secondary-label ios-label" id="ca-mobile-push-label<%= key %>"
              ca-mobile-push-label<%= key %>-ios="<%= option.item_text%>" ><%= option.item_text%></div>
             </div>
             <div>
               <div class="span" style="width: 250px !important;margin-left: 0px !important;"> 
               <%
               if(!_.isUndefined(option.item_type)){
                if(option.item_type=="DEEP_LINK"){

                 var iosSecDeepLink = option.item_type;
                }
               if(option.item_type=="EXTERNAL_URL"){
                 var iosSecExternalLink = option.item_type;
                }
                
                }
             %> 
               <input type="radio" name="ios-secondary-link<%= key %>" value="deep-link" class = "ios-secondary-link" <% if( iosSecDeepLink == 'DEEP_LINK'){
                %> checked="checked" <% } %> /> <?= _campaign("Deep Link")?>
               </div>
               <div>        
               <input type="radio" name="ios-secondary-link<%= key %>" value="external-link"  class = "ios-secondary-link" <% if( iosSecExternalLink == 'EXTERNAL_URL'){
                %> checked="checked" <% } %> /> <?= _campaign("External Link")?>
               </div>
               </div>
              <div class="ca-mobile-push-reset-container"> 
              <% 
        	  	var actionLink = "" ;
      			var secDeepActionLink = "" ;
      			var secExternalActionLink = "" ;
              if(!_.isUndefined(option.item_link)){
                if(option.item_type=="DEEP_LINK")
                 var secDeepActionLink = option.item_link;
                
               if(option.item_type=="EXTERNAL_URL")
                 var secExternalActionLink =option.item_link;
               console.log("secExternalActionLink madhu :",secExternalActionLink); 
              
              if(secDeepActionLink)
                var actionLink =  secDeepActionLink;
              if(secExternalActionLink)
                var  actionLink = secExternalActionLink;  
                }
               console.log("actionLink :",actionLink);  

             %> 
              <input type="text" name="" id="ca-mobile-push-secondary<%= key %>" class="ca-mobile-push-text ca-mobile-push-text-link ca-mobile-push-text-link ca-mobile-push-secondary" ca-mobile-push-secondary<%= key %>-deep-link-ios='<%= secDeepActionLink%>' ca-mobile-push-secondary<%= key %>-external-link-ios='<%= secExternalActionLink%>' value='<%= actionLink%>'>
             </div>
            </div>                  
           <%} }); %>          
  </div>
</div>
</script>

<script id="create_IOS_secondary_tpl" type="text/template">
	<div class="secondary-CTA secondary-CTA-IOS">
	  <div style="float:right;margin: 10px;">
	    <a class="ca-mobile-push-reset-ios-div"><i class="icon-trash ca-mobile-icon"></i></a></div>
	   <div class="secondary-cta-ios-container">
	    <table class="table table-striped">
	          <thead><th style="width: 65%;"><?= _campaign("NAME & DESCRIPTION")?></th>
	         <th><?= _campaign("BUTTON 1")?></th><th><?= _campaign("BUTTON 2")?></th>
	         </tr></thead>
            <tbody class="ca-mobile-push-ios-cta">
            </tbody>
        </table>
	   </div>
	</div>
	<div class="IOS-secondary-detail-block">
	</div>
</script>
<script type="text/template" id="create_IOS_secondary_tpl_cta">
    <tr class="ca-mobile-push-cta">
     <td>
     <input type="hidden" name="categoryId" id="categoryId" value="<%= model.id %>">
     <span class="name"><%= model.name %></span><br/>
     <%= model.description %>
      </td>
      <%
        _.each(model.ctaTemplateDetails, function(v, k) { 
          var button_text = model.ctaTemplateDetails[k].buttonText;  
          var button_description = model.ctaTemplateDetails[k].description; 
          var button_id = model.ctaTemplateDetails[k].id;
          var to_LaunchApp = model.ctaTemplateDetails[k].toLaunchApp;
        %>
        <td class="ios-cta-btn">
            <input type="hidden" id="toLaunchApp<%= k %>" value="<%= to_LaunchApp %>">  
            <input type="hidden" id="button<%= k %>_id" value="<%= button_id%>">
            <span id="mobilepuh_button<%= k %>"><%= button_text %></span>  <br/>
                 <%= button_description %> 
            <input type="hidden" id="mobile_cta_key" value="<%= k %>">     
        </td>
      <% }); %>
           
     </tr>      
</script>

<script id="create_mobile_push_template_tpl" type="text/template">
    <div class="channel-container">
        <div class="c-float span6 outbound-cta-container">
        	<div class="ca-mobile-push-container-width shellpadding">
	          	<span class="display-headtitle"><?= _campaign("Title")?></span>
	          	<span class="red-error">*</span>
          		<a class="ca-fright ca-mobile-push-copy ca-mobile-push-italic"><?= _campaign('Copy from')?> 
			        <span>
			        	<% if(data.tab_value == "android") {%>
			          		<?= _campaign('IOS')?>
			        	<%} if(data.tab_value == "ios") { %>
			        		<?= _campaign('Android')?> 
			           	<% } %>
			        </span>
        		</a>
        	</div>
        	<div class="ca-mobile-push-reset-container ca-mobile-push-container-width">
        <%
        if(data.tab_value == "android"){
            if(!_.isUndefined(data.template.html_content.ANDROID)){
                var mob_title = data.template.html_content.ANDROID.title;
            }
        }
        if(data.tab_value == "ios"){
            if(!_.isUndefined(data.template.html_content.IOS)){
                var mob_title = data.template.html_content.IOS.title;
            }
        }    
		%>
        		<input type="text" name="ca-mobile-push-title" id="ca-mobile-push-title" class="ca-mobile-push-text ca-mobile-push-set-text outbound-title-messaage ca-mobile-push-title" value="<%= mob_title%>" ca-mobile-push-title-android="<%= mob_title %>" ca-mobile-push-title-ios="<%= mob_title %>" >
        	</div>
        	<br>
        	<div class="ca-mobile-push-container-width shellpadding">
        		<span class="display-headtitle"><?= _campaign("Message")?></span>
        		<span class="red-error">*</span>
        		<span class="ca-mobile-push-italic"> (<?= _campaign("Max limit 90 Characters")?>)</span>     
        		<a class="ca-fright ca-mobile-push-copy ca-mobile-push-italic"><?= _campaign('Copy from')?>
        			<span>
			        <% if(data.tab_value == "android") {%>
			        	<?= _campaign('IOS')?>
			        <%} if(data.tab_value == "ios") { %>
			        	<?= _campaign('Android')?> 
			        <% } %>
			        </span>
        		</a>
          	</div>
        	<div class="ca-mobile-push-reset-container ca-mobile-push-container-width shellpadding "> 
        <% 
        	var mob_msg = "" ;
          	if(data.tab_value == "android"){
              	if(!_.isUndefined(data.template.html_content.ANDROID)){
                	mob_msg = data.template.html_content.ANDROID.message;
            	}
        	}
          	if(data.tab_value == "ios"){
              	if(!_.isUndefined(data.template.html_content.IOS)){
                	mob_msg = data.template.html_content.IOS.message;
            	}
          	}    
		%>
        		<textarea id="ca-mobile-push-textarea" class="ca-mobile-push-textarea ca-mobile-push-set-text outbound-title-messaage" ca-mobile-push-textarea-android="<%= mob_msg%>" ca-mobile-push-set-text ca-mobile-push-textarea-ios="<%= mob_msg %>" maxlength="90"><%= mob_msg %></textarea>
        	</div>
        	<div style="clear:both"></div>
	        <div class="">
	        	<span class="show-count"><%= mob_msg.length %></span>
	        	<?= _campaign('characters')?>
	        </div>
	        <br/>
        <%if(data.template_scope == "MOBILEPUSH_IMAGE") {%>
            <div class="shellContainer">
             	<div style="font-size: 12px;">
             		<span class="display-headtitle" ><?= _campaign("Image") ?></span>
             		<span class="red-error">*</span>
             		<span class="ca-mobile-push-italic">(<?= _campaign("max image size")?>:1080 x 540 px, <?= _campaign("max image file size")?>: 3MB)</span>
             	</div>
            	<span class="mobileViewPicLeft">
               		<div class="mobile_push_image_file">
	               		<% if(data.tab_value == "android"){
	                		if(!_.isUndefined(data.template.html_content.ANDROID)){
	                 			var imgSrc = data.template.html_content.ANDROID.expandableDetails.image;
	              			}
	              		}
	          			if(data.tab_value == "ios"){
	                 		if(!_.isUndefined(data.template.html_content.IOS)){
	                  			var imgSrc = data.template.html_content.IOS.expandableDetails.image;
	              			} 
	              		}   
	              		
	              		%>   
	            		<div>
	              			<!-- <img src = "<%=imgSrc%>" style="height: 200px;" /> -->
	              			<img src = "http://akstatic.nightly.capillary.in/intouch_creative_assets/c5271590ab1f4371419f.png" style="height: 200px;" />
	            		</div>
          			</div>
            	</span>
             	<span class="mobileViewPicRight">
               		<button type="button" class="ca-g-btn btn upload_image_file" > <?= _campaign("Upload") ?></button>
             	</span>
           	</div>
           	<div style="display: none">
	            <form id="image_upload_<%= data.tab_value %>" name="image_upload">
	              <input type="file" name="upload_image" id="upload_image_<%= data.tab_value %>">
	            </form>
        	</div>
        <% } %>
        
	        <div id="primary_cta">
	        	<div class="display-headtitle"><?= _campaign("Primary Call To Action")?></div>
	        	<div class="primary_cta_body_container">
	        		<div class="ca-mobile-push-container-width shellpadding">
	          			<div class="cta_link" style="display: inline-block;">
         	<%  console.log(data.tab_value);
          		var deepLink = "" ;
          		if(data.tab_value == "android"){
              		if(!_.isUndefined(data.template.html_content.ANDROID) && !_.isUndefined(data.template.html_content.ANDROID.cta)){
              			cta_type = data.template.html_content.ANDROID.cta.type ;
                		if(cta_type=="DEEP_LINK" || cta_type == "EXTERNAL_URL"){
                 			deepLink = cta_type;
                		}
              		}
              	} else if(data.tab_value == "ios"){
             		if(!_.isUndefined(data.template.html_content.IOS) && !_.isUndefined(data.template.html_content.IOS.cta)){
             			cta_type = data.template.html_content.IOS.cta.type ;
             			if(cta_type=="DEEP_LINK" || cta_type == "EXTERNAL_URL"){
             				deepLink = cta_type;
                		}               
               		}
               	}    
               console.log("deeplink",deepLink);   
            %> 
		           			<input type="radio" name="primary-link-<%= data.tab_value %>" class="primary-link" value="deep-link"
		            		<% if( deepLink == 'DEEP_LINK'){ %> checked="checked" <% } %> /> <?= _campaign("Deep Link")?>
	            		</div>
	           			<div style="display: inline-block;">        
	          				<input type="radio" name="primary-link-<%= data.tab_value %>" class="primary-link" value="external-link"  <% if( deepLink == 'EXTERNAL_URL') { %> checked="checked" <% } %>  /> <?= _campaign("External Link")?>
        				</div>          
	          			<div id="reset-primary-cta-android" style="display: inline-block;float: right;display: inline-block;margin-right: 15px;">
	          				<a class="ca-mobile-push-reset-container">
	          					<i class="icon-refresh"></i>
	          				</a>
	          			</div>
        			</div>
        			<div class="ca-mobile-push-reset-container ca-mobile-push-container-width shellpadding">
          	<% 
          		if(data.tab_value == "android"){
            		if(!_.isUndefined(data.template.html_content.ANDROID) && !_.isUndefined(data.template.html_content.ANDROID.cta)){
		            	cta_type = data.template.html_content.ANDROID.cta.type ;
		                if(cta_type=="DEEP_LINK" || cta_type=="EXTERNAL_URL"){
		                	var deepActionLink = data.template.html_content.ANDROID.cta.actionLink;
		                } else if(cta_type=="EXTERNAL_URL"){
		                	var externalActionLink = data.template.html_content.ANDROID.cta.actionLink;
		                }
               		} else
                		var deepLink = 'deep-link';
              	}
          		if(data.tab_value == "ios"){
            		if(!_.isUndefined(data.template.html_content.IOS) && !_.isUndefined(data.template.html_content.IOS.cta)){
		            	if(data.template.html_content.IOS.cta.type=="DEEP_LINK"){
                			var deepActionLink = data.template.html_content.IOS.cta.actionLink;
                		}
            			if(data.template.html_content.IOS.cta.type=="EXTERNAL_URL"){
                			var externalActionLink = data.template.html_content.IOS.cta.actionLink;
                		}
              		} else
                		var deepLink = 'deep-link';
              	}  
            	if(deepActionLink)
                	var actionLink =  deepActionLink;
              	if(externalActionLink)
                	var  actionLink = externalActionLink;            
            %> 

        				<input type="text" name="ca-mobile-push-title" id="ca-mobile-push-primary" class="ca-mobile-push-text ca-mobile-push-text-link" ca-mobile-push-primary-deep-link-android="<%= deepActionLink %>" ca-mobile-push-primary-external-link-android="<%= externalActionLink %>" ca-mobile-push-primary-deep-link-ios="<%= deepActionLink %>" ca-mobile-push-primary-external-link-ios="<%= externalActionLink %>"  value="<%= actionLink %>">
          			</div>
        		</div>
	    	</div>

        	<br>
        	<div>
          		<div class="display-headtitle"><?= _campaign("Secondary Call To Action")?></div>
           	<% if(data.tab_value == "android"){%>
           		<div class="ca-mobile-push-show-secondary"></div>
           	<% } %>
          		<div class="ca-mobile-push-hide">
          	<% if(data.tab_value == "ios")
            	var cta_cls =  "ca-mobile-push-add-ios";
               if(data.tab_value == "android")
                var cta_cls =  "ca-mobile-push-add";
            %>     
          			<button class="ca-g-btn ca-save-btn <%= cta_cls %>"><?= _campaign("Add")?></button>
          		</div>
			</div>
        	<% if(data.tab_value == "ios"){%>
          	<div class="add-secondary-IOS"></div>
         	<% } %> 
            <div class="red-error ca-required">* <?= _campaign("Required Field")?></div> 
        </div>
    </div>
</script>

<script id="secondary_tpl" type="text/template">
  <div class="secondary-CTA secondary-CTA<%= data.no %> ca-mobile-push-container-width" ca-mobile-push-key = "<%= data.no %>">
    <div style="float:right;margin: 0 14px 5px 5px;">
    <a class="ca-mobile-push-delete-container" ca-mobile-no = "<%= data.no %>"><i class="icon-trash ca-mobile-icon"></i></a>
    <a class="ca-mobile-push-reset-div" ca-mobile-no = "<%= data.no %>"></a> </div>
     <div class="display-headtitle"><?= _campaign("Label Name")?></div>
      <div class="ca-mobile-push-reset-container">
      <% 
      if(!_.isUndefined(data.mod)){
       var secLabel=data.mod.html_content.ANDROID.expandableDetails.ctas[data.no].actionText
      }    
      %>
       
             <input type="text" name="ca-mobile-push-secondary-label" id="ca-mobile-push-label<%= data.no %>" class="ca-mobile-push-text ca-mobile-push-set-text ca-mobile-push-secondary-label" ca-mobile-push-label<%= data.no %>-android="<%= secLabel%>" value="<%= secLabel%>">
             </div>
             <div class="ca-mobile-push-container-width shellpadding">
               <div class="cta_link secondary-deep-link" style="display: inline-block;"> 
               <% 
               var deepLink = "" ;
               if(!_.isUndefined(data.mod)){
                if(data.mod.html_content.ANDROID.expandableDetails.ctas[data.no].type=="DEEP_LINK"){
                	deepLink = "DEEP_LINK";
                }else if(data.mod.html_content.ANDROID.expandableDetails.ctas[data.no].type=="EXTERNAL_URL"){
                	deepLink = "EXTERNAL_URL";
                }
                }
             %> 
               <input type="radio" name="secondary-link<%= data.no %>" class = "secondary-link"  value="deep-link" <% if( deepLink == 'DEEP_LINK'){ %> checked="checked" <% } %> /> <?= _campaign("Deep Link")?>
               </div>
               <div style="display: inline-block;">        
               <input type="radio"  name="secondary-link<%= data.no %>" class = "secondary-link" value="external-link" <% if( deepLink == 'EXTERNAL_URL'){ %> checked="checked" <% } %> /> <?= _campaign("External Link")?>
               </div>
             </div>
              <% 
        	  	var actionLink = "" ;
          		var secDeepActionLink = "" ;
          		var secExternalActionLink = "" ;
              	if(!_.isUndefined(data.mod)){
              		if(data.mod.html_content.ANDROID.expandableDetails.ctas[data.no].type=="DEEP_LINK"){
                 		actionLink = data.mod.html_content.ANDROID.expandableDetails.ctas[data.no].actionLink;
                 		secDeepActionLink = actionLink ;
                	}
               		if(data.mod.html_content.ANDROID.expandableDetails.ctas[data.no].type=="EXTERNAL_URL"){
                 		actionLink = data.mod.html_content.ANDROID.expandableDetails.ctas[data.no].actionLink;
                 		secExternalActionLink = actionLink ;
                	}
                }
             %> 

              <div class="ca-mobile-push-reset-container"> 
             <input type="text" name="" id="ca-mobile-push-secondary" class="ca-mobile-push-text ca-mobile-push-text-link ca-mobile-push-secondary<%= data.no %>" ca-mobile-push-secondary-deep-link-android='<%= secDeepActionLink%>' ca-mobile-push-secondary-external-link-android='<%= secExternalActionLink%>' value='<%= actionLink%>'>
             </div>               
                    

  </div>

</script>
