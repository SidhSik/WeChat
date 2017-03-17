var auth = auth || {};
(function($) {
  _.templateSettings.variable = "rc";
  auth.AuthorizeModel = Backbone.Model.extend({
    defaults: {
      checkAuthorize: 1
    },
    initialize: function() {
      this.on("all", function(e) {
        console.log(this.get("id") + " event: " + e);
      });
    },
    urlRoot: '/xaja/AjaxService/campaign_v2/get_auth_details.json',
    url: function() {
      var base = this.urlRoot || "/";
      if (this.isNew()) return base;
      return base + "?message_id=" + encodeURIComponent(this.id);
    },
    showError: function(msg) {
      $('.flash_message').show().addClass('redError').html(msg);
      setTimeout(function() {
        $('.flash_message').fadeOut('fast').removeClass('redError');
      }, 3000);
      $('.wait_message').hide().removeClass('indicator_1');
    },
    showSuccessMsg: function(msg) {
      window.parent.$('.flash_message').html(msg).show();
      window.parent.$('.flash_message').fadeOut(7000);
    },
    hideModelPopup: function() {
      window.parent.hidePopup();
    },
    reloadParent: function() {
      console.log(top.overview);
      top.overview.refreshMessages();
    }
  });
  auth.AuthorizeView = Backbone.View.extend({
    initialize: function() {
      this.getDetails();
    },
    getDetails: function() {
      var that = this;
      this.model.fetch({
        success: function(response) {
          if (response.toJSON().error_msg) {
            window.parent.hidePopup();
            window.parent.location.href = "/campaign/index?flash=" + response.toJSON().error_msg;
          } else {
            that.details = response.toJSON().auth_details;
            that.render();
            that.listenTo(that.model, 'change', that.render);
          }
        }
      });
    },
    events: {
      "click #msg_approve_btn": "approveMessage",
      "click #msg_reject_btn": "rejectMessage",
      "click #msg_close_btn": "closeMessage",
      "click #btn-desktop": "showDesktopView",
      "click #btn-mobile": "showMobileView",
      "click #auth-mobile-portrait-id": "showMobilePortTrait",
      "click #auth-mobile-landscape-id": "showMobileLandScape",
      "click .checkbox-authorize": "setCheckBox",
      'click #android-all-preview': 'previewAllAndroid',
      'click #ios-all-preview': 'previewAllIos'
    },
    render: function(details) {
      var details = this.details;
      if (details.messages.default_args.msg_type == "WECHAT_MULTI_TEMPLATE") {
        $('#multi_image_preview_modal .singlePic, .wechat-msg-content-container .close').off('click');
      }
      if (details.messages.default_args.msg_type == "WECHAT_SINGLE_TEMPLATE") {
        $('.wechat-msg-preview-container, .wechat-msg-content-container.hide .close').off('click');
      }
      // Compile the template using underscore
      var template = _.template($("#campaign_overview_authorize").html());
      details['checkAuthorize'] = this.model.get('checkAuthorize');
      // Load the compiled HTML into the Backbone "el"
      this.$el.html(template(details));
      window.setTimeout(function() {
        $("#auth-iframe-preview").contents().find("html").html(details.messages.msg);
        $("#preview-iframe-mobile-portrait").contents().find("html").html(details.messages.msg);
        $("#iframe-mobile-landscape").contents().find("html").html(details.messages.msg);
      }, 500);

      if (details.type == 'mobilepush') {
        this.mobile_push_data = JSON.parse(details.messages.msg);
        this.previewAllMobilePushNew('android');
      }

      if (details.messages.default_args.msg_type == "WECHAT_SINGLE_TEMPLATE") {
        $('.wechat-msg-preview-container').on('click', function() {
          $(this).addClass('hide');
          $('.wechat-msg-content-container.hide').removeClass('hide');
        });
        $('.wechat-msg-content-container.hide .close').on('click', function() {
          $(this).parent('.wechat-msg-content-container').addClass('hide');
          $('.wechat-msg-preview-container.hide').removeClass('hide');
        });
      }
      if (details.messages.default_args.msg_type == "WECHAT_TEMPLATE") {
        var html_content = details.messages.default_args.templateData.content;
        html_content = html_content.replace('{{first.DATA}}', 'first.DATA');
        html_content = html_content.replace('{{remark.DATA}}', 'remark.DATA');
        var obj = details.messages.default_args.templateData.Data;
        for (var propt in obj) {
          html_content = html_content.replace(propt + '.DATA', obj[propt]['Value'].replace('{{', '').replace('}}', ''));
        }
        html_content = html_content.replace(/(?:\r\n|\r|\n)/g, '<br />');
        $('#mobile-preview-icon-wechat-template-message-authorize .preview_container').html(html_content);
      }
      if (details.messages.default_args.msg_type == "WECHAT_MULTI_TEMPLATE") {
        $('#multi_image_preview_modal .singlePic').on('click', function() {
          $('.wechat-msg-content-container[template=' + $(this).attr('template') + ']').removeClass('hide');
        });
        $('.wechat-msg-content-container .close').on('click', function() {
          $(this).parent('.wechat-msg-content-container').addClass('hide');
        });
      }
    },

    previewAllAndroid: function( e ) {
      console.log('previewAllAndroid');
      this.previewAllMobilePushNew('android');
    },

    previewAllIos: function( e ) {
      this.previewAllMobilePushNew('ios');
    },

    previewAllMobilePushNew:function( container ){
      console.log('previewAllMobilePushNew');
      var preview_model = this.mobile_push_data;
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
        delete _.templateSettings.variable;

        $('#ios-all-preview').addClass('sel');
        $('#android-all-preview').removeClass('sel');
        
        var template = _.template($("#ios-notif-preview").html());
        var ios_data = this.getIosAllTemplateData(preview_model.templateData);
        ios_data.adjust_width = true;
        var html = template({ios_data:ios_data});
        
        $(".mobile-preview-icon-ios").show();
        $(".mobile-preview-icon-android").hide();
        
        $('#mobile-push-auth-preview').html(html);

        _.templateSettings.variable = 'rc';
      }else{
       $(e.currentTarget).prop('disabled', true);
        return false;
      }
    },

    renderAndroidAllPreview: function( preview_model ) {
      console.log('renderAndroidAllPreview');
      if(!_.isUndefined(preview_model.templateData.ANDROID)){
        delete _.templateSettings.variable;

        $('#android-all-preview').addClass('sel');
        $('#ios-all-preview').removeClass('sel');
        
        var template = _.template($("#android-notif-preview").html());
        var android_data = this.getAndroidAllTemplateData(preview_model.templateData);
        android_data.adjust_width = true;
        var html = template({android_data:android_data});
        
        $(".mobile-preview-icon-android").show();
        $(".mobile-preview-icon-ios").hide();
        
        $('#mobile-push-auth-preview').html(html);
        
        _.templateSettings.variable = 'rc';
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

      console.log('android_data', android_data);

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

      var non_actionable_ctas = preview_model[channel].expandableDetails.nonActionableCta;
      if (non_actionable_ctas) {
        non_actionable_ctas.forEach(function(element) {
        if (element.actionText) {
          sec_label.push(element.actionText);
          }
        });
      }      

      return sec_label;
    },

    approveMessage: function(e) {
      var details = this.details;
      var domain_gateway_config = details.domain_gateway_config;
      if(domain_gateway_config && domain_gateway_config.domain_gateway_map_id && !domain_gateway_config.is_validated) {
        this.model.showError( _campaign("Domain-Gateway combination is not validated") );
        return;
      }
      
      var that = this;
      this.model.url = '/xaja/AjaxService/campaign_v2/approve_msg.json?message_id=' + encodeURIComponent(this.model.get('id'));
      $('.wait_message').show().addClass('indicator_1');
      this.model.fetch({
        success: function(response) {
          var data = response.toJSON(),
            error = data.approve_error,
            info = data.approve_info,
            org_error = data.approve_org_error;
          checkSessionExpiry(data);
          if (org_error) {
            window.parent.hidePopup();
            window.parent.location.href = "/campaign/index?flash=" + org_error;
          } else if (error) {
            that.model.showError(error);
          } else {
            that.model.showSuccessMsg(info);
            that.model.hideModelPopup();
            // that.model.reloadParent();
            top.overview.getDetails();
          }
        }
      });
    },
    setCheckBox: function(e) {
      if (this.model.get('checkAuthorize') == 1) this.model.set('checkAuthorize', 0);
      else this.model.set('checkAuthorize', 1);
    },
    rejectMessage: function(e) {
      var that = this;
      this.model.url = '/xaja/AjaxService/campaign_v2/reject_msg.json?message_id=' + encodeURIComponent(this.model.get('id'));
      $('.wait_message').show().addClass('indicator_1');
      this.model.fetch({
        success: function(response) {
          var data = response.toJSON(),
            error = data.reject_error,
            info = data.reject_info;
          checkSessionExpiry(data);
          if (error) {
            that.model.showError(error);
          } else {
            that.model.showSuccessMsg(info);
            that.model.hideModelPopup();
            that.model.reloadParent();
          }
        }
      });
    },
    showDesktopView: function(e) {
      var element = $(e.currentTarget);
      if ($("#btn-mobile").hasClass("active")) {
        $("#btn-mobile").removeClass("btn-inverse active background-color");
        $("#btn-mobile").addClass("btn-default");
        $("#btn-desktop").removeClass("btn-default");
        $("#btn-desktop").addClass("btn-inverse active background-color");
        $("#auth-mobile").addClass("hide");
        $("#auth-desktop").removeClass("hide");
      }
    },
    showMobileView: function(e) {
      if ($("#btn-desktop").hasClass("active")) {
        $("#btn-desktop").removeClass("btn-inverse active background-color");
        $("#btn-desktop").addClass("btn-default");
        $("#btn-mobile").removeClass("btn-default");
        $("#btn-mobile").addClass("btn-inverse active background-color");
        $("#auth-mobile").removeClass("hide");
        $("#auth-desktop").addClass("hide");
      }
    },
    showMobilePortTrait: function(e) {
      $("#auth-mobile-landscape-id").removeClass("auth-mobile-landscape-active");
      $("#auth-mobile-landscape-id").addClass("auth-mobile-landscape");
      $("#auth-mobile-portrait-id").removeClass("auth-mobile-portrait");
      $("#auth-mobile-portrait-id").addClass("auth-mobile-portrait-active");
      $(".auth-bananaphone").removeClass("hide");
      $(".auth-bananaphone-landscape").addClass("hide");
    },
    showMobileLandScape: function(e) {
      $("#auth-mobile-landscape-id").addClass("auth-mobile-landscape-active");
      $("#auth-mobile-landscape-id").removeClass("auth-mobile-landscape");
      $("#auth-mobile-portrait-id").addClass("auth-mobile-portrait");
      $("#auth-mobile-portrait-id").removeClass("auth-mobile-portrait-active");
      $(".auth-bananaphone").addClass("hide");
      $(".auth-bananaphone-landscape").removeClass("hide");
    },
    closeMessage: function(e) {
      this.model.hideModelPopup();
    }
  });
  auth.AuthorizeRouter = Backbone.Router.extend({
    routes: {
      "message/:id": "getDetails"
    }
  });
  var authRouterInstance = new auth.AuthorizeRouter();
  authRouterInstance.on("route:getDetails", function(id) {
    // Here we have set the `id` of the model
    var authModelInstance = new auth.AuthorizeModel({
      id: id
    });
    var authViewInstance = new auth.AuthorizeView({
      el: $("#message_authorize_container"),
      model: authModelInstance
    });
  });
  Backbone.history.start();
}(jQuery));