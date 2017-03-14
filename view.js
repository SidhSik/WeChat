var ov = {};
var header = header || {};
(function($) {
  ov.OverViewModel = Backbone.Model.extend({
    initialize: function() {
      this.on("all", function(e) {
        console.log(this.get("id") + " event: " + e);
      });
    },
    urlRoot: '/xaja/AjaxService/campaign_v2/overview.json',
    url: function() {
      var base = this.urlRoot || "/";
      if (this.isNew()) return base;
      return base + "?campaign_id=" + encodeURIComponent(this.id);
    },
    showError: function(msg) {
      $('.flash_message').show().addClass('redError').html(msg);
      setTimeout(function() {
        $('.flash_message').removeClass('redError').fadeOut('fast');
      }, 3000);
    },
    clearCacheKeys: function() {
      var keys = ["campaign_id", "message_id", "sender_label", "sender_from", "list_type", "group_selected", "subject", "message", "plain_text", "hidden_plain_text"];
      $.each(keys, function(i, val) {
        localStorage.removeItem(val + "_" + window.name);
      });
    }
  });
  ov.OverView = Backbone.View.extend({
    all_languages: [],
    secondary_templates: [],
    initialize: function() {
      this.getDetails();
    },
    showCreateCoupon: function() {
      $('#coupon_new_create').removeClass('hide');
      $('#create_new_coupon').prop('disabled', true);
    },
    hideCreateCoupon: function() {
      $(".content-msgcontain").removeClass('hide');
      $("#coupon_container").addClass('hide');
      $('#coupon_new_create').addClass('hide');
      $('#create_new_coupon').prop('disabled', false);
    },
    getDetails: function() {
      var that = this;
      this.model.fetch({
        success: function(response) {
          var jsonResp = response.toJSON();
          that.render(jsonResp.camp_details, jsonResp.obj_elems, jsonResp.obj_md);
          that.model.clearCacheKeys();
        }
      });
    },
    events: {
      "click .camp_requeue_msg": "reQueueMessage",
      "click .camp_msg_view": "getPreview",
      "click .camp_msg_details": "getMsgDetails",
      "click .reminder_state_change": "updateScheduler",
      "click #create_new_coupon": "showCreateCoupon",
      "click #coupon_cancel": "hideCreateCoupon",
      "click #campaign-update-btn": "showCampaignUpdate",
      "click .show_dialog": "showDialog",
      "click #campaign_status_btn": "changeCampaignStatusAjax",
      "click .change_campaign_status": "changeCampaignStatusAjax",
      "click #hide_dialog": "hideDialog",
      "click #updateCampaign-btn": "updateCampaignDetails",
      "click .automation-info": "showReportInfo",
      "click #close_pop": "showReportInfo",
      "click .retry_auto": "retryReportSchedule",
      "click .disable_report": "disableSchedule",
      "click .msg_preview_tab": "msgPreviewTabClick",
      "change input:radio[name=content]": "modifyIncentive",
      "change #deal_type": "modifyDealType",
      "change .obj-select": "modifyObjective",
      "change #generic-select": "modifyGeneric" ,
      'click #android-all-preview': 'previewAllAndroid',
      'click #ios-all-preview': 'previewAllIos'
    },
    checkIfClickEnabled: function(clickedId){
      if(clickedId == "android-all-preview" ){
        status = $("#android-status").attr('value') ;
        if(status == 'true')
          return true ;
        else
          return false ;
      }else if(clickedId == "ios-all-preview"){
        status = $("#ios-status").attr('value') ;
        if(status == 'true')
          return true ;
        else
          return false ;
      }
    },
    previewTabClick: function(e){
      console.log("preview tab clicked") ;
      var clickedId = $(e.currentTarget).attr('id');
      if(!checkIfClickEnabled(clickedId)){
        return ;
      }

      $(".preview-mobile-push").addClass('hide');
      $(".preview-mobile-push").removeClass('sel') ;  
      $(".mobile-preview-icon").addClass('hide');
      if(clickedId == "android-all-preview"){
        $("#android-all-preview").removeClass('hide') ;
        $("#android-all-preview").addClass('sel') ;
        $("#mobile-preview-icon-android").removeClass('hide') ;
      }else if(clickedId == "ios-all-preview"){
        $("#ios-all-preview").removeClass('hide') ;
        $("#ios-all-preview").addClass('sel') ;
        $("#mobile-preview-icon-ios").removeClass('hide') ;
      }
    },
    msgPreviewTabClick: function(e) {
      var clickedId = $(e.currentTarget).attr('id');
      var clickedArr = clickedId.split('__');
      var lang_id = clickedArr[clickedArr.length - 1];
      $('#camp_msg_preview_frame').contents().find('html').html("");
      $('#camp_msg_preview_frame').contents().find('html').html("<center><img src='/images/ajax-loader.gif' style='margin-top:15%'></img></center>");
      $('.msg_preview_tab').removeClass('tab_selected');
      $('#msg_preview_tab__' + lang_id).addClass('tab_selected');
      if (this.secondary_templates && this.secondary_templates[lang_id] != undefined) {
        if (this.all_languages[lang_id].is_base_template) $('#camp_msg_preview_frame').contents().find('html').html(decodeURIComponent(this.base_template));
        else $('#camp_msg_preview_frame').contents().find('html').html(this.secondary_templates[lang_id].html_content);
      }
    },
    showReportInfo: function() {
      if ($('.automation-info').hasClass('disabled')) return;
      $('#pop_report').toggle();
    },
    render: function(details, obj_elems, obj_mapping) {
      this.model.set({
        'obj_elems': obj_elems
      });
      this.model.set({
        'obj_mapping': obj_mapping
      });
      console.log(details);
      var template = _.template($("#campaign_overview_v3").html(), details);
      this.$el.html(template);
      var addeditcampaign = _.template($("#campaignaddedit").html(), details);
      this.$("#addedittemplate").html(addeditcampaign);
      this.renderObjective();
      this.renderObjectiveHeirarchy(obj_mapping);
      this.setSelectMapping();
      if (details.total_messages > 0) {
        this.renderMessages(details.messages);
      }
      $('.wait_message_form').hide().removeClass('intouch-loader');
    },
    findLevel: function(lev, elem) {
      var pid = this.obj_elems[elem]['pid'];
      if (pid == -1) return lev;
      return this.findLevel(lev + 1, pid);
    },
    setSelectMapping: function() {
      var select_mapping = [];
      $('.obj-select').each(function() {
        var selected_val = $(this).find(":selected").val();
        select_mapping.push({
          "select": this,
          "selected_val": selected_val
        });
      });
      this.model.set({
        "select_mapping": select_mapping
      });
    },
    modifyObjective: function(e) {
      var obj_elems = this.model.get('obj_elems');
      var prev = $(e.currentTarget).attr('prev');
      $("div.p" + prev).addClass('hide');
      $("select.p" + prev).removeClass('is_selected');
      var objective = $(e.currentTarget).val();
      var obj_id = objective.substr(objective.indexOf("sid_"));
      var help_text = $("#" + obj_id).attr('help');
      obj_id = obj_id.substr(obj_id.indexOf('_') + 1);
      $(e.currentTarget).attr('prev', 'id_' + obj_id);
      $("div.pid_" + obj_id + ":first").removeClass('hide');
      $("select.pid_" + obj_id + ":first").addClass('is_selected')
      $(e.currentTarget).parent().siblings(".help_text_edit").first().text(help_text);
    },
    getClasses: function(pid, classes) {
      obj_elems = this.obj_elems;
      if (pid == -1) return classes;
      else {
        classes = this.getClasses(parseInt(obj_elems[pid]['pid']), classes + " pid_" + pid.toString());
        return classes;
      }
    },
    renderObjectiveSub: function(elem_id, hide_sub) {
      var obj_elems = this.obj_elems;
      var el = obj_elems[elem_id];
      if (el['type'] == 'parent-select') {
        var div;
        if (hide_sub) div = '<div class ="for-content camp-child-edit hide';
        else div = '<div class ="for-content camp-child-edit';
        var classes = this.getClasses(parseInt(el['pid']), '');
        div += classes + '">';
        var label = '<label class="campaign_label">' + _campaign(el['name']) + '</label>';
        var label_span = '<span>' + label + '</span>';
        div += label_span;
        var select = '';
        var select_span = '<span>';
        if (Object.keys(el['children']).length != 0) {
          var first_child = Object.keys(el['children'])[0];
          if (el['children'][first_child]['type'] == 'select') {
            for (child in el['children']) {
              var opt = '<option class="opt-capitalize" value="' + child + ' sid_' + el['children'][child]['id'] + '" help="' + el['children'][child]['help'] + '" id="sid_' + el['children'][child]['id'] + '">' + _campaign(child) + '</option>';
              select += opt;
            }
            select = '<select prev="id_' + el['children'][first_child]['id'] + '" class="obj-select ' + classes + '">' + select + '</select>';
            select_span += select + '</span>';
            div += select_span;
            this.obj_elems[elem_id]['is_rendered'] = true;
            for (child in el['children']) {
              var cid = parseInt(el['children'][child]['id']);
              if (obj_elems[cid]) {
                var obj_sub = this.renderObjectiveSub(cid);
                if (obj_sub) div += obj_sub;
              }
            }
          }
        }
        div += '</div>';
        return div;
      } else if (el['type'] == 'parent-input') {
        var div = '<div class ="for-content camp-child-edit';
        var classes = this.getClasses(parseInt(el['pid']), '');
        div += classes + '">';
        var label = '<label class="campaign_label">' + _campaign(el['name']) + '</label>';
        var label_span = '<span>' + label + '</span>';
        div += label_span;
        var input = '<input type="text" class="input-nsl ca-input obj-input ' + classes + '">';
        var input_span = '<span>' + input + '</span>';
        div += input_span;
        return div;
      }
    },
    renderObjective: function() {
      var obj_elems = this.model.get('obj_elems');
      obj_elems = JSON.parse(obj_elems);
      this.obj_elems = obj_elems;
      $('#updateCampaign-btn').attr('data-obj_elems', this.obj_elems);
      var select = '<select id="campaign_objective"' + ' name="campaign_objective" class="input-nsl campaign_objective obj-select is_selected"' + 'prev="id_';
      var optgroup_count = 0;
      var first_elem_help;
      var elem_count = 0;
      for (elem in this.obj_elems) {
        if (this.obj_elems[elem]['pid'] == -1) {
          optgroup_count++;
          if (this.obj_elems[elem]['type'] == 'group-select') {
            this.obj_elems[elem]['is_rendered'] = true;
            var elem_prop = this.obj_elems[elem];
            var optgroup = '<optgroup class="opt-capitalize" label="' + _campaign(elem_prop['name']) + '">';
            var c = 0;
            var c_help = "";
            for (child in elem_prop['children']) {
              if (c == 0) {
                select += elem_prop['children'][child]['id'] + '">';
                c_help = elem_prop['children'][child]['help'];
              }
              if (c == 0 && elem_count == 0) first_elem_help = c_help;
              c++;
              child_prop = elem_prop['children'][child];
              if (this.obj_elems[child_prop['id']]) this.obj_elems[child_prop['id']]['is_rendered'] = true;
              var opt = '<option class="opt-capitalize" value="' + child + ' sid_' + elem_prop['children'][child]['id'] + '" id="sid_' + elem_prop['children'][child]['id'] + '" help="' + elem_prop['children'][child]['help'] + '">' + _campaign(child) + '</option>';
              optgroup += opt;
            }
            if (optgroup_count == 1) optgroup = "<option disabled selected value='none'> -- "+_campaign("select an option")+" -- </option>" + optgroup;
            optgroup += '</optgroup>';
            select += optgroup;
          }
        }
        elem_count++;
      }
      select += '</select>';
      var select_span = '<span>' + select + '</span>';
      var help_span = '<span name="' + $("#objective-div").html(select_span);
      var help_text_div = '<br><p class="help_text_edit">' + first_elem_help + '</p>'
      $("#objective-div").append(help_text_div);
      var c = 0;
      for (elem in this.obj_elems) {
        var lev = 0;
        lev = this.findLevel(0, elem);
        this.obj_elems[elem]['level'] = lev;
        if (!obj_elems[elem]['is_rendered']) {
          var objectiveSub;
          if (c == 0) objectiveSub = this.renderObjectiveSub(elem, false);
          else objectiveSub = this.renderObjectiveSub(elem, true);
          $("#objective-div").append(objectiveSub);
          if (objectiveSub) $("#objective-div").append(objectiveSub);
          c++;
        }
      }
      $('.obj-select').not(':has(.hide)').addClass('is_selected');
      $('.obj-input').not(':has(.hide)').addClass('is_selected');
    },
    renderObjectiveMappings: function(obj_metadata, path) {
      if (path.length == 0) return
      if (obj_metadata[path[0]]['input_type'] == "select") {
        var elem = $("#sid_" + path[0].toString());
        elem.prop('selected', true);
        elem.parent().closest('div').removeClass('hide');
        elem.parent().closest('select').addClass("is_selected");
        var help_text = elem.attr("help");
        elem.closest("span").siblings(".help_text_edit").first().text(help_text);;
        path.shift()
        this.renderObjectiveMappings(obj_metadata, path);
      } else {
        path.shift();
        this.renderObjectiveMappings(obj_metadata, path);
      }
    },
    hideUnselectedDivs: function() {
      $('select').each(function(i, obj) {
        if ($(obj).attr('id') == "campaign_objective" || $(obj).hasClass("is_selected")) return;
        $(obj).parent().closest('div').addClass('hide');
      });
    },
    renderObjectiveHeirarchy: function(obj_mapping) {
      var selected_obj = parseInt(obj_mapping['selected_obj']);
      if (!selected_obj) {
        this.model.set("previous_selected", "none");
        $("#campaign_objective").val($("#campaign_objective option:first").val());
        $("#campaign_objective").parent().siblings('div').addClass('hide');
        none_value = $("#campaign_objective option:first").attr("value");
        $("#campaign_objective").attr("value", none_value);
        $(".help").attr('title', 'Select an option from dropdown');
      } else {
        this.model.set("previous_selected", selected_obj);
        var obj_metadata = obj_mapping['obj_metadata'];
        var id = selected_obj;
        var path = new Array();
        path.push(id);
        var pid = 1;
        while (pid != -1) {
          pid = parseInt(obj_metadata[id]['objective_parent_id']);
          path.push(pid);
          id = pid;
        }
        path = path.reverse();
        path.shift();
        $('select').removeClass('is_selected');
        this.renderObjectiveMappings(obj_metadata, path);
        this.hideUnselectedDivs();
      }
    },
    renderMessages: function(messages) {
      var html = '<div class="c-table-layout display-table c-table-campaign-messages">';
      $.each(messages, function(index) {
        var message = messages[index];
        console.log(message);
        html += '<div class="display-mtablerow">';
        html += '<div class="display-mtablecell">';
        if(message.msg_display_type != 'MOBILE PUSH'){
          html += '<div class="c-messages-table-mtype">' + message.msg_display_type + '</div>';
          html += '<div class="c-messages-table-content truncate_msg">'+message.message + '</div>';
        }else{
          html += '<div class="c-messages-table-mtype" style="width:100%">' + message.msg_display_type + '</div>';
        }
        html += '<div id="c-messages-table-content-' + message.queue_id + '" class="hide" type="' + message.msg_display_type + '">';
        if (message.msg_display_type == "Email") {
          html += message.preview_message + '</div>';
        } else {
          html += message.message + '</div>';
        }
        html += '<div class="c-messages-table-audienceText">' +_campaign('List')+' : ' + message.group_tooltip + ' , ' + message.scheduled_on;
        if (message.msg_display_type != "Call Task") {
          html += '<span class="c-messages-table-rate">';
          if (message.delivery_rate) html += '<span class="c-messages-table-deliveryRate"><span class="c-messages-table-inner-text">' + message.delivery_rate + '</span>' + _campaign("Delivery Rate") + '</span>';
          if (message.click_rate) html += '<span class="c-messages-table-click-rate"><span class="c-messages-table-inner-text">' + message.click_rate + '</span>' + _campaign("Click Rate") + '</span>';
          if (message.open_rate) html += '<span class="c-messages-table-open-rate"><span class="c-messages-table-inner-text">' + message.open_rate + '</span>' + _campaign("Open Rate") + '</span>';
          if (message.recipients) html += '<span class="c-messages-table-recipients"><span class="c-messages-table-inner-text">' + message.recipients + '</span>' + _campaign("Recipients") + '</span>';
          html += '</span>';
        }
        html += '</div>';
        if (message.approve == 1) {
          html += '<div class="c-messages-table-approved-by">' + _campaign('Approved By') + ': ' + message.approved_by + '</div>';
        }
        html += '</div>';
        html += '<div class="display-rtablecell">';
        var style = "";
        if (!message.actions) style = "style=''";
        if (index >= 3) html += '<div class="btn-group mtable-actions dropup">';
        else html += '<div class="btn-group mtable-actions">';
        if (message.approved) html += message.approved;
        else {
          html += '<button type="button" class="btn camp_msg_view" ' + style + ' msg_id="' + message.queue_id + '" campaign_id="' + message.campaign_id + '">' + _campaign('View') + '</button>';
        }
        if (message.actions) html += '<button type="button" class="btn dropdown-toggle" data-toggle="dropdown">' + '<span class="caret"></span><span class="sr-only"></span>' + '</button>' + '<ul class="dropdown-menu camp-menu" role="menu">' + message.actions + '</ul>';
        html += '</div></div></div>';
      });
      html += '</div>';
      this.$el.find(".messagesTableContainer").html(html);
    },
    reQueueMessage: function(e) {
      var that = this;
      var msg_id = $(e.currentTarget).attr("msg_id");
      var campaign_id = $(e.currentTarget).attr("campaign_id");
      var ajaxUrl = '/xaja/AjaxService/campaign/requeue_msgs.json?ajax_params_1=' + campaign_id + '&ajax_params_2=' + msg_id;
      $('.wait_message').show().addClass('indicator_1');
      $.getJSON(ajaxUrl, function(data) {
        checkSessionExpiry(data);
        if (data.info != 'success') {
          $('.flash_message').addClass('redError').show().html(data.error);
          setTimeout(function() {
            $('.flash_message').fadeOut('fast');
          }, 7000);
          $('.indicator_1').hide();
          $('.wait_message').removeClass('indicator_1');
        } else {
          that.getDetails();
          $('.indicator_1').hide();
          $('.wait_message').removeClass('indicator_1');
        }
      });
    },
    getPreview: function(e) {
      var msg_id = $(e.currentTarget).attr("msg_id");
      if (!msg_id) {
        return false;
      }
      var ajaxUrl = '/xaja/AjaxService/campaign/get_message_preview.json?message_id=' + msg_id;
      $('#camp_msg_preview_frame').contents().find('html').html("");
      $('#camp_msg_preview_frame').contents().find('html').html("<center><img src='/images/ajax-loader.gif' style='margin-top:15%'></img></center>");
      //$('#camp_msg_preview_modal').show("slide", { direction: "left" }, 5000);
      $("#camp_msg_preview_modal").modal('show');
      //$("#camp_msg_preview_modal").show( "slide", {direction: "up" }, 5000 );
      this.all_languages = [];
      this.secondary_templates = [];
      this.base_template = '';
      var self = this;
      $.getJSON(ajaxUrl, function(data) {
        $("#camp_msg_preview_inside_header").hide();
        // handle content preview when previewing wechat templates
        if ($("#c-messages-table-content-" + msg_id).attr("type") == "WeChat") {
          if (data.default_arguments.msg_type == "WECHAT_MULTI_TEMPLATE") {
            $('#multi_image_preview_modal .singlePic, .wechat-msg-content-container .close').off('click');
          }
          // if (data.default_arguments.msg_type == "WECHAT_TEMPLATE") {

          // }
          if (data.default_arguments.msg_type == "WECHAT_SINGLE_TEMPLATE") {
            $('.wechat-msg-preview-container, .wechat-msg-content-container.hide .close').off('click');
          }
          if (data.default_arguments.msg_type == "WECHAT_SINGLE_TEMPLATE") {
            $('#camp_msg_preview_modal .modal-body').html(_.template($('#campaign_overview_preview').html())({
              model: data.default_arguments
            }));
            $('.wechat-msg-preview-container').on('click', function() {
              $(this).addClass('hide');
              $('.wechat-msg-content-container.hide').removeClass('hide');
            });
            $('.wechat-msg-content-container.hide .close').on('click', function() {
              $(this).parent('.wechat-msg-content-container').addClass('hide');
              $('.wechat-msg-preview-container.hide').removeClass('hide');
            });
          }
          if (data.default_arguments.msg_type == "WECHAT_TEMPLATE") {
            $('#camp_msg_preview_modal .modal-body').html(_.template($('#campaign_overiew_preview_wechat_template_message').html()));
            var html_content = data.default_arguments.templateData.content;
            html_content = html_content.replace('{{first.DATA}}', 'first.DATA');
            html_content = html_content.replace('{{remark.DATA}}', 'remark.DATA');
            var obj = data.default_arguments.templateData.Data;
            for (var propt in obj) {
              html_content = html_content.replace(propt + '.DATA', obj[propt]['Value'].replace('{{', '').replace('}}', ''));
            }
            html_content = html_content.replace(/(?:\r\n|\r|\n)/g, '<br />');
            $('#mobile-preview-icon-wechat-template-message .preview_container').html(html_content);
          }
          if (data.default_arguments.msg_type == "WECHAT_MULTI_TEMPLATE") {
            $('#camp_msg_preview_modal .modal-body').html(_.template($('#campaign_overview_preview').html())({
              model: data.default_arguments
            }));
            $('#multi_image_preview_modal .singlePic').on('click', function() {
              $('.wechat-msg-content-container[template=' + $(this).attr('template') + ']').removeClass('hide');
            });
            $('.wechat-msg-content-container .close').on('click', function() {
              $(this).parent('.wechat-msg-content-container').addClass('hide');
            });
          }
        } else if($("#c-messages-table-content-" + msg_id).attr("type") == "MOBILE PUSH"){
            decoded_html = decodeURIComponent(data.html) ;
            templateData = JSON.parse(decoded_html) ; 
            self.mobile_push_data = templateData;
            self.previewAllMobilePushNew('android');
        }else {
          if ($("#c-messages-table-content-" + msg_id).attr("type") == "Email") {
            $("#camp_msg_preview_inside_header").show().html($("#c-messages-table-content-" + msg_id).html());
            self.all_languages = data.languages;
            self.secondary_templates = data.secondary_templates;
            var str = '';
            var lang_id = -1;
            for (var key in self.all_languages) {
              str += '<li class="msg_preview_tab lang_tab_preview" id="msg_preview_tab__' + key + '">' + self.all_languages[key].lang_name + '</li>';
              lang_id = key;
            }
            $('#msg_preview_lang_list').html(str);
          }
          self.base_template = data.html;
          if (self.secondary_templates[lang_id] != undefined && self.secondary_templates[lang_id] != null) {
            $('#camp_msg_preview_frame').contents().find('html').html(self.secondary_templates[lang_id].html_content);
            $('#msg_preview_tab__' + lang_id).addClass('tab_selected');
          } else {
            $('#camp_msg_preview_frame').contents().find('html').html(decodeURIComponent(self.base_template));
          }
        }
      });
      $("#camp_msg_preview_modal").slideToggle("slow");
    },

    previewAllAndroid: function( e ) {
      this.previewAllMobilePushNew('android');
    },

    previewAllIos: function( e ) {
      this.previewAllMobilePushNew('ios');
    },

    previewAllMobilePushNew:function( container ){
      preview_model = this.mobile_push_data;
      // preview_mob = container == 'android' ? container : $(e.currentTarget).attr("id");
      if(container == 'android') {
        return this.renderAndroidAllPreview(preview_model);
      } else {
        return this.renderIosAllPreview(preview_model);
      }
    },

    renderIosAllPreview: function( preview_model ) {
      console.log(preview_model);
      if(!_.isUndefined(preview_model.templateData.IOS)){
        $('#ios-all-preview').addClass('sel');
        $('#android-all-preview').removeClass('sel');
        
        var template = _.template($("#ios-notif-preview").html());
        var ios_data = this.getIosAllTemplateData(preview_model.templateData);
        var html = template({ios_data:ios_data});
        
        $(".mobile-preview-icon-ios").show();
        $(".mobile-preview-icon-android").hide();
        
        // this.$('#camp_msg_preview_modal').modal('show');
        $('#camp_msg_preview_modal .modal-body').html(html);
      }else{
       $(e.currentTarget).prop('disabled', true);
        return false;
      }
    },

    renderAndroidAllPreview: function( preview_model ) {
      if(!_.isUndefined(preview_model.templateData.ANDROID)){
        $('#android-all-preview').addClass('sel');
        $('#ios-all-preview').removeClass('sel');
        
        var template = _.template($("#android-notif-preview").html());
        var android_data = this.getAndroidAllTemplateData(preview_model.templateData);
        var html = template({android_data:android_data});
        
        $(".mobile-preview-icon-android").show();
        $(".mobile-preview-icon-ios").hide();
        
        // $('#camp_msg_preview_modal').modal('show');
        $('#camp_msg_preview_modal .modal-body').html(html);
      }else{
       $(e.currentTarget).prop('disabled', true);
        return false;
      }
    },

    getIosAllTemplateData: function( preview_model ) {
      var ios_data = {};
      ios_data.title = preview_model.IOS.title;
      ios_data.content = preview_model.IOS.message;
      // ios_data.img = preview_model.IOS.expandableDetails.img;
      if(preview_model.IOS.expandableDetails &&
          (preview_model.IOS.expandableDetails.image || 
            preview_model.IOS.expandableDetails.ctas)) {
        // ios_data.notif_img = preview_model.IOS.expandableDetails.image;
      ios_data.notif_img = 'http://www.pngmart.com/files/2/Yoshi-PNG-Photos.png';
        ios_data.cta_sec = this.fetchSecondaryLabels(preview_model, 'IOS');
      } else {
        // ios_data.notif_img = undefined;
        ios_data.notif_img = 'http://www.pngmart.com/files/2/Yoshi-PNG-Photos.png';
        ios_data.cta_sec = [];
      }

      return ios_data;
    },

    getAndroidAllTemplateData: function( preview_model ) {
      var android_data = {};
      android_data.title = preview_model.ANDROID.title;
      android_data.content = preview_model.ANDROID.message;
      // android_data.img = preview_model.ANDROID.expandableDetails.img;
      if(preview_model.ANDROID.expandableDetails &&
          (preview_model.ANDROID.expandableDetails.image || 
            preview_model.ANDROID.expandableDetails.ctas)) {
        // android_data.notif_img = preview_model.ANDROID.expandableDetails.image;
      android_data.notif_img = 'http://www.imagenspng.com.br/wp-content/uploads/2015/02/super-mario-mario-04-221x300.png';
        android_data.cta_sec = this.fetchSecondaryLabels(preview_model, 'ANDROID');
      } else {
        android_data.notif_img = undefined;
        android_data.cta_sec = [];
      }

      return android_data;
    },

    fetchSecondaryLabels: function(preview_model, channel) {
      var sec_label = []
      var sec_label_elements = preview_model[channel].expandableDetails.ctas;
      if (sec_label_elements) {
        sec_label_elements.forEach(function(element) {
          if (element.actionText) {
            sec_label.push(element.actionText);
          }
        });
      }

      return sec_label;
    },
    getMsgDetails: function(e) {
      var msg_id = $(e.currentTarget).attr("msg_id");
      var camp_id = $(e.currentTarget).attr("campaign_id");
      if (!msg_id) {
        return false;
      }
      $('.wait_message').show().addClass('indicator_1');
      var ajaxUrl = '/xaja/AjaxService/messages/Singlerecipient.json?message_id=' + msg_id + '&campaign_id=' + camp_id;
      var tmpl = _.template($("#details-template").html());
      $("#camp_msg_details_modal").modal('show');
      $.getJSON(ajaxUrl, function(data) {
        $("#camp_msg_details_modal").find('#camp_msg_details').html(tmpl({
          group: data
        }));
        $('.indicator_1').hide();
        $('.wait_message').removeClass('indicator_1');
      });
      $("#camp_msg_details_modal").slideToggle("slow");
    },
    updateScheduler: function(e) {
      var msg_id = $(e.currentTarget).attr("msg_id");
      var campaign_id = $(e.currentTarget).attr("campaign_id");
      var state = $(e.currentTarget).attr("state");
      var ajaxUrl = '/xaja/AjaxService/campaign/scheduler_status.json?ajax_params_1=' + campaign_id + '&ajax_params_2=' + msg_id + '&ajax_params_3=' + state;
      var that = this;
      $('.wait_message').show().addClass('indicator_1');
      $.getJSON(ajaxUrl, function(data) {
        checkSessionExpiry(data);
        if (data.info == 'success') {
          $('.flash_message').removeClass('redError').show().html(data.info_status);
          that.getDetails();
          $('.indicator_1').hide();
          $('.wait_message').removeClass('indicator_1');
        } else {
          $('.flash_message').addClass('redError').show().html(data.error);
          $('.indicator_1').hide();
          $('.wait_message').removeClass('indicator_1');
        }
        setTimeout(function() {
          $('.flash_message').fadeOut('fast');
        }, 5000);
      });
    },
    showCampaignUpdate: function(e) {
      var obj_mapping = this.model.get('obj_mapping');
      var select_mapping = this.model.get("select_mapping");
      select_mapping.forEach(function(map) {
        $(map['select']).val(map['selected_val']).trigger('change');
      });
      $("#camp_update_modal").modal('show');
      $("input:radio[name=content]:first").click();
      $("#updatecampaign").validationEngine({
        promptPosition: 'centerRight',
        validationEventTriggers: 'keyup blur',
        success: false,
        scroll: true
      });
      var start_date = this.model.get('camp_details')['start_date'];
      var end_date = this.model.get('camp_details')['end_date'];
      $("#u_starting_date").datepicker({
        clearText: start_date,
        minDate: 0,
        showOn: 'both',
        yearRange: '2012:2015',
        changeMonth: true,
        changeYear: true,
        buttonImage: '/images/calendar-icon.gif',
        buttonImageOnly: true,
        dateFormat: 'yy-mm-dd'
      });
      $("#u_end_date").datepicker({
        clearText: end_date,
        minDate: 1,
        showOn: 'both',
        yearRange: '2015:2017',
        changeMonth: true,
        changeYear: true,
        buttonImage: '/images/calendar-icon.gif',
        buttonImageOnly: true,
        dateFormat: 'yy-mm-dd'
      });
      $("#u_starting_date").val($.datepicker.formatDate('yy-mm-dd', $.datepicker.parseDate('yy-mm-dd', start_date)));
      $("#u_end_date").val($.datepicker.formatDate('yy-mm-dd', $.datepicker.parseDate('yy-mm-dd', end_date)));
    },
    showDialog: function(e) {
      $("#camp_status_update_modal").modal('show');
    },
    hideDialog: function(e) {
      $("#camp_status_update_modal").modal('hide');
    },
    changeCampaignStatusAjax: function(e) {
      var that = this;
      that.hideDialog(e);
      window.location.href = '/campaign/base/ChangeCampaignActiveStatus?campaign_id=' + $('#hdn_status_val').val();
    },
    getObjectivesSelection: function(obj_elems) {
      var obj_final;
      if ($('input:text').hasClass('is_selected')) {
        obj_classes = $('obj-input.is_selected:text').className.split(/\s+/);
        for (cl in obj_classes) {
          if (cl.indexOf("pid") > 0) {
            class_id = parseInt(cl.substr(cl.indexOf('pid') + 1));
            var parent = obj_elems[class_id];
            if (obj_elems[parent]['type'] == 'parent-select') {
              var child = Object.keys(obj_elems[clmax]['children'])[0];
              obj_final = parseInt(obj_elems[parent]['children'][child]['id']);
              break;
            }
          }
        }
      } else {
        var c_max = 0;
        var cmax_elem;
        $(".obj-select.is_selected").each(function() {
          obj_classes = $(this).attr('class').split(/\s+/);
          var c = 0;
          for (cl in obj_classes) {
            if (obj_classes[cl].indexOf("pid") != -1) c++;
          }
          if (c_max < c) {
            c_max = this;
            cmax_elem = this;
          }
        });
        obj_final = $(cmax_elem).attr('prev');
        if (typeof(obj_final) != "undefined") obj_final = parseInt(obj_final.substr(obj_final.indexOf('_') + 1));
        else {
          obj_final = $("#campaign_objective").attr("prev");
          obj_final = parseInt(obj_final.substr(obj_final.indexOf('_') + 1));
        }
      }
      return obj_final;
    },
    updateCampaignDetails: function(e) {
      var previous_selected = this.model.get("previous_selected");
      var camp_obj = $("#campaign_objective").attr("value");
      var obj_elems = $(e.currentTarget).data('obj_elems');
      var obj_final = this.getObjectivesSelection(obj_elems);
      var that = this;
      if ($("#updatecampaign").validationEngine({
          promptPosition: "centerRight",
          returnIsValid: true
        })) {
        if (camp_obj == "none") {
          $('#camp-update-error').html("").show().html(_campaign("Campaign Objective cannot be empty"));
          setTimeout(function() {
            $('#camp-update-error').fadeOut('fast');
          }, 3000);
          $('.indicator_1').hide();
          $('.wait_message').removeClass('indicator_1');
        } else {
          var post_data = $("#updatecampaign").serializeArray();
          var obj_index = 0;
          for (var i = 0; i < post_data.length; i++) {
            if (post_data[i]['name'] == "campaign_objective") obj_index = i;
          }
          post_data.splice(obj_index, 1);
          post_data.push({
            name: "campaign_objective",
            value: obj_final
          });
          var ajaxUrl = "/xaja/AjaxService/campaign_v2/update_campaign.json";
          $('.wait_message').show().addClass('indicator_1');
          $.getJSON(ajaxUrl, jQuery.param(post_data), function(data) {
            checkSessionExpiry(data);
            if (data.info == 'SUCCESS') {
              that.setSelectMapping();
              $("#camp_update_modal").modal('hide');
              $('.flash_message').removeClass('redError').show().html(_campaign("Campaign details updated successfully."));
              $("#in-heading-campaign-name").html(data.campaign_name);
              if (data.ga_name != '') {
                $('#in-heading-ga-name').html(data.ga_name);
              }
              var name = data.campaign_name;
              if (name.length > 14) {
                $('#heading-campaign-name').attr('title', name);
                name = name.substr(0, 12) + '...';
              }
              $("#heading-campaign-name").html(name);
              $("#camp-fromdate").html(data.start_date);
              $("#camp-todate").html(data.end_date);
              $('.indicator_1').hide();
              $('.wait_message').removeClass('indicator_1');
            } else {
              $('#camp-update-error').html("").show().html(data.info);
              setTimeout(function() {
                $('#camp-update-error').fadeOut('fast');
              }, 5000);
              $('.indicator_1').hide();
              $('.wait_message').removeClass('indicator_1');
            }
            setTimeout(function() {
              $('.flash_message').fadeOut('fast');
            }, 5000);
          });
        }
      }
    },
    refreshMessages: function() {
      var that = this;
      var ajaxUrl = "/xaja/AjaxService/campaign_v2/get_queued_msgs.json?campaign_id=" + this.model.get("id");
      $('.wait_message').show().addClass('indicator_1');
      $.getJSON(ajaxUrl, function(data) {
        if (data.camp_messages) {
          that.renderMessages(data.camp_messages.messages);
        }
        $('.indicator_1').hide();
        $('.wait_message').removeClass('indicator_1');
      });
    },
    retryReportSchedule: function() {
      var campaign_id = this.model.get("id");
      $('.automation-info').addClass('disabled');
      var ajaxUrl = "/xaja/AjaxService/campaign_v2/retry_schedule.json?campaign_id=" + campaign_id;
      $('.wait_message').show().addClass('indicator_1');
      $.getJSON(ajaxUrl, function(data) {
        if (data.status == 'success') $('.flash_message').removeClass('redError').show().html(data.info);
        else $('.flash_message').show().addClass('redError').html(data.info);
        $('#pop_report').hide();
        header.overView.initialize();
        $('.wait_message').removeClass('indicator_1');
        setTimeout(function() {
          $('.flash_message').fadeOut('slow');
        }, 4000);
      });
    },
    disableSchedule: function() {
      var campaign_id = this.model.get("id");
      $('.automation-info').addClass('disabled');
      var ajaxUrl = "/xaja/AjaxService/campaign_v2/disable_schedule.json?campaign_id=" + campaign_id;
      $('.wait_message').show().addClass('indicator_1');
      $.getJSON(ajaxUrl, function(data) {
        if (data.status == 'failed') $('.flash_message').show().addClass('redError').html(data.info);
        else $('.flash_message').show().removeClass('redError').html(_campaign("Report schedule disabled"));
        $('#pop_report').hide();
        header.overView.initialize();
        $('.wait_message').removeClass('indicator_1');
        setTimeout(function() {
          $('.flash_message').fadeOut('slow');
        }, 3000);
      });
    }
  });
  $('#pop_report').hide();
  $('body').click(function(event) {
    if ($(event.target).closest('div').hasClass('a-report-cont') || $(event.target).parent('div').hasClass('a-report-cont') || $(event.target).hasClass('automation-info')) {} else {
      $('#pop_report').hide();
    }
  });
  $("#camp-body-cont").scroll(function() {
    $('.text-info').hide();
    $('.formError').remove();
    if ($("#camp-body-cont").scrollTop() > 100) {
      $("#take-to-top").show();
    } else {
      // <= 100px from top - hide div
      $("#take-to-top").hide();
    }
  });
  $("#take-to-top").live("click", function() {
    $(".campaigns-container").stop(true, true).animate({
      scrollTop: $(".content-msgcontain").offset().top
    }, 1000);
  });
  var keys = ["campaign_id", "message_id", "sender_label", "sender_from", "list_type", "group_selected", "subject", "message", "plain_text", "hidden_plain_text"];
  $.each(keys, function(i, val) {
    localStorage.removeItem(val + "_" + window.name);
  });
}(jQuery));