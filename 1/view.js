var app = app || {};
(function($) {
  //define individual campaign view
  app.CampaignView = Backbone.View.extend({
    tagName: "div",
    className: "display-tablerow",
    template: _.template($("#c-listview").html()),
    render: function() {
      var tmpl = _.template($("#c-listview").html());
      $(this.el).html(tmpl(this.model.toJSON()));
      return this;
    }
  });
  //define master view (List of campaigns)
  app.CampaignsListView = Backbone.View.extend({
    el: $(".c-table-layout"),
    initialize: function() {
      this.collection.on('sync', this.afterFetched, this);
      this.collection.on('reset', this.afterReset, this);
      //this.fetchFn();
    },
    events: {},
    render: function() {
      var that = this;
      _.each(this.collection.models, function(item) {
        that.renderCampaign(item);
      }, this);
      intouch.widgets.heightResize($("body"), $(".c-outer-article")); /* just for a confirmation that outter div height is first set */
      camp.widgets.heightResize($(".d-content-cont"), $(".table-cont"));
      $('.wait_initial_form').show().removeClass('intouch-loader');
      $('.wait_message_form').show().removeClass('intouch-loader');
    },
    renderCampaign: function(item) {
      app.CampaignViewInstance = new app.CampaignView({
        model: item
      });
      $(".c-dashboard-layout").append(app.CampaignViewInstance.render().el);
    },
    afterFetched: function(res) {
      var showMore = $(".c-showmore");
      var noData = $(".c-nodata");
      if (res.length < camp.paginationLimit) showMore.hide();
      else showMore.show();
    },
    afterReset: function(res) {
      var noData = $(".c-nodata");
      $(".display-tablerow").not(".table-head").remove();
      $("#start").val(0);
      $(".c-showmore").attr("href", "#showmore/" + camp.paginationLimit);
      if (res.length == 0) {
        var status = $(".c-status-option .sel").attr('status-type');
        if (status.toLowerCase() == 'live') {
          noData.text(_campaign("No Live Campaigns found"));
        } else if (status.toLowerCase() == 'lapsed') {
          noData.text(_campaign("No Lapsed Campaigns found"));
        } else {
          noData.text(_campaign("No Upcoming Campaigns found"));
        }
        noData.show();
        $('.display-headtitle').hide();
      } else {
        noData.hide();
        $('.display-headtitle').show();
      }
    },
    fetchFn: function() {
      var that = this;
      this.collection.fetch({
        success: function(res) {
          that.render();
        }
      });
    },
    resetFn: function() {
      var that = this;
      this.collection.fetch({
        success: function(res) {
          that.render();
        },
        reset: true
      });
    }
  });
  app.CampaignsListInstance = new app.CampaignsListView({
    collection: app.collection
  });
  app.DashboardView = Backbone.View.extend({
    el: $(".c-content"),
    initialize: function() {
      // this.render();
    },
    render: function() {
      var tmpl = _.template($("#dashboardview").html());
      $("#container").html(tmpl());
    },
    renderFn: function(callback) {
      this.render();
      callback();
    }
  });
  app.DashboardViewInstance = new app.DashboardView();
  app.PerformanceView = Backbone.View.extend({
    el: $(".c-content"),
    initialize: function() {},
    render: function() {
      $('.wait_initial_form').show().addClass('intouch-loader');
      var iframe_url = this.model.get('url');
      var tmpl = _.template($("#performance_view").html());
      $("#container").html(tmpl());
      $("#performance_frame").attr('src', iframe_url);
      $("#performance_frame").load(function() {
        // do something once the iframe is loaded
        $('.wait_initial_form').show().removeClass('intouch-loader');
      });
    },
  });
  app.CreativeView = Backbone.View.extend({
    el: $(".c-container"),
    initialize: function() {
      this.creative_assets = new CreativeAssets();
    },
    render: function() {
      $('.wait_initial_form').show().addClass('intouch-loader');
      var tmpl = _.template($("#creative_tpl").html());
      $("#container").html(tmpl());
      this.showEmailTemplate();
      $('.wait_initial_form').show().removeClass('intouch-loader');
      return this;
    },
    events: {
      'change select#template_type': 'showTemplate',
      'click #create_from_scratch': 'createNewWechatTemplate',
      'click .singleImageTemplatecontainer .upload_image_file': 'showImageUpload',
      'change #upload_image': 'uploadImage',
      'click #save_single_image_tpl': 'saveSingleImageTemplate',
      'click #update_single_image_tpl': 'saveSingleImageTemplate',
      'click #preview_single_image_tpl': 'previewSingleImageTemplate',
      'click #cancel_single_image_tpl': 'loadSingleImage',
      'click #cancel_multi_image_tpl': 'loadMultiImage',
      'click .openSingleImageCatalogue': 'showSingleImageTemplateCatalogue',
      'click #single_image_catalogue .wechatSingleTemplate:not(.disabled)': 'chooseTemplateForMultiPic',
      'click #apply-template-selection': 'addSelectedSingleImage',
      'click #save_multi_image_tpl': 'saveMultiImageTemplate',
      'click #update_multi_image_tpl': 'saveMultiImageTemplate',
      'click #preview_multi_image_tpl': 'previewMultiImageTemplate',
      'click #add_single_placeholder': 'addSingleImagePlaceholder',
      'click .singlePic .remove': 'removeSingleImageTemplate',
      'keyup .singleImageTemplatecontainer #template_title': 'countChars',
      'keyup .singleImageTemplatecontainer #template_summary': 'countChars',
      'keydown #template_name': 'restrictSpecialChar',
      'keydown #template_title': 'restrictSpecialChar'
    },
    restrictSpecialChar: function(e) {
      return !((e.shiftKey && e.keyCode == 190) || (e.shiftKey && e.keyCode == 188))
    },
    countChars: function(e) {
      var charsLen = $(e.currentTarget).val().split('').length;
      $('#' + $(e.currentTarget).attr('id') + '_count span').html(charsLen);
    },
    removeSingleImageTemplate: function(e) {
      e.stopPropagation();
      if (confirm(_campaign('Are you sure you want to remove this ?'))) {
        var placeholderHandler = $(e.currentTarget).parent('.singlePic:visible');
        placeholderHandler.removeAttr('template-id qxun-template-id');
        placeholderHandler.html('<div class="title">' + _campaign("Title for this SIBM goes here") + '</div><div class="imageContainer"><img src=""></div><div class="content hide"></div><div class="remove"><div>X</div></div>');
      }
    },
    addSingleImagePlaceholder: function() {
      //some validation
      var index = $('.multiImageTemplatecontainer:visible .singlePicContainer .singlePic').length + 1;
      $('.multiImageTemplatecontainer:visible .singlePicContainer').append('<div class="singlePic openSingleImageCatalogue" template="' + index + '"><div class="title">' + _campaign("Title for this SIBM goes here") + '</div><div class="imageContainer"><img src=""></div><div class="content hide"></div><div class="remove"><div>X</div></div></div>');
    },
    addSelectedSingleImage: function(e) {
      var templateAnchor = $(e.currentTarget).attr('template-anchor');
      if ($('.wechatSingleTemplate.selected').length) {
        var templateData = {
          title: $('.wechatSingleTemplate.selected .title').html(),
          image: $('.wechatSingleTemplate.selected .imageContainer img').attr('src'),
          template_id: $('.wechatSingleTemplate.selected').attr('template-id'),
          qxun_template_id: $('.wechatSingleTemplate.selected').attr('qxun-template-id'),
          content: $('.wechatSingleTemplate.selected .content').html()
        };
        var templateContainerHandler = $('.multiImageTemplatecontainer .singlePicContainer .singlePic[template=' + templateAnchor + ']');
        templateContainerHandler.attr({
          'template-id': templateData.template_id,
          'qXun-template-id': templateData.qxun_template_id
        });
        templateContainerHandler.find('.title').html(templateData.title);
        templateContainerHandler.find('.imageContainer img').attr('src', templateData.image);
        templateContainerHandler.find('.content').html(templateData.content);
      }
      $('#single_image_catalogue').modal('hide');
    },
    chooseTemplateForMultiPic: function(e) {
      $('#single_image_catalogue .wechatSingleTemplate:not(.disabled)').removeClass('selected');
      $(e.currentTarget).addClass('selected');
    },
    showSingleImageTemplateCatalogue: function(e) {
      $('#single_image_catalogue').modal('show');
      $('#apply-template-selection').attr('template-anchor', $(e.currentTarget).attr('template'));
      var choosenTemplates = [];
      $('.multiImageTemplatecontainer .singlePic').each(function() {
        choosenTemplates.push($(this).attr('template-id'));
      });
      $.getJSON('/xaja/AjaxService/assets/get_all_wechat_single_image_templates.json', {
        account_id: $('#wechat-accounts').val()
      }, function(json) {
        var templateHtml = '';
        _.templateSettings.variable = "model";
        var compiledTemplate = _.template($('#wechat_single_tpl').html());
        _.each(json.templates, function(data, key) {
          var selected = '';
          if (_.indexOf(choosenTemplates, data.template_id) != -1) {
            selected = 'disabled';
          }
          _.extend(data, {
            catalogueView: true,
            selected: selected
          });
          templateHtml += compiledTemplate(data);
        });
        $('#single_image_catalogue .modal-body').html(templateHtml);
      });
    },
    previewMultiImageTemplate: function() {
      $('#multi_image_preview_modal').remove();
      $('#multi_image_preview_modal .singlePic, .wechat-msg-content-container .close').off('click');
      var data = [];
      $('.singlePicContainer:visible .singlePic').each(function() {
        data.push({
          'template_id': $(this).attr('template-id'),
          'qxun_template_id': $(this).attr('qxun-template-id'),
          'title': $(this).children('.title').html(),
          'image': $(this).find('.imageContainer img').attr('src'),
          'content': $(this).children('.content').html()
        });
      });
      _.templateSettings.variable = "data";
      $('.multiImageTemplatecontainer').append(_.template($('#multi_image_preview_template').html())(data));
      $("#multi_image_preview_modal").modal('show');
      $('#multi_image_preview_modal .singlePic').on('click', function() {
        $('.wechat-msg-content-container[template=' + $(this).attr('template') + ']').removeClass('hide');
      });
      $('.wechat-msg-content-container .close').on('click', function() {
        $(this).parent('.wechat-msg-content-container').addClass('hide');
      });
    },
    previewSingleImageTemplate: function() {
      $('#single_image_preview_modal').remove();
      $('.wechat-msg-preview-container, .wechat-msg-content-container.hide .close').off('click');
      var data = {
        title: $('#template_title').val(),
        image: $('.upload_image_file img').attr('src'),
        summary: $('#template_summary').val(),
        content: encodeURIComponent(CKEDITOR.instances.wechat_content.getData())
      };
      _.templateSettings.variable = "model";
      $('.singleImageTemplatecontainer').append(_.template($('#single_image_preview_template').html())(data));
      $("#single_image_preview_modal").modal('show');
      $('.wechat-msg-preview-container').on('click', function() {
        $(this).addClass('hide');
        $('.wechat-msg-content-container.hide').removeClass('hide');
      });
      $('.wechat-msg-content-container.hide .close').on('click', function() {
        $(this).parent('.wechat-msg-content-container').addClass('hide');
        $('.wechat-msg-preview-container.hide').removeClass('hide');
      });
    },
    loadMultiImage: function(fetch) {
      $('.multiImageTemplatecontainer,#single_image_catalogue').remove();
      $('#creative_tpl_container').removeClass('hide');
      if (_.isBoolean(fetch)) {
        $('#template_scope').trigger('change');
      }
    },
    loadSingleImage: function(fetch) {
      $('.singleImageTemplatecontainer').remove();
      $('#creative_tpl_container').removeClass('hide');
      if (_.isBoolean(fetch)) {
        $('#template_scope').trigger('change');
      }
    },
    saveMultiImageTemplate: function(e) {
      if ($('#template_name').val() == '') {
        ca_wechat.helper.showFlash(_campaign('Enter a name for this template'), true);
        return false;
      }
      if ($('.singlePic:visible img[src!=""]').length < 2) {
        ca_wechat.helper.showFlash(_campaign('Select atleast 2 templates'), true);
        return false;
      }
      $('.wait_initial_form').show().addClass('intouch-loader');
      var template_id = $(e.currentTarget).data('template-id');
      var that = this;
      var templateJson = {};
      var choosenTemplates = {
        templateIds: '',
        qxunTemplateIds: ''
      };
      $('.multiImageTemplatecontainer:visible .singlePic').each(function() {
        if ($(this).attr('template-id')) {
          choosenTemplates.qxunTemplateIds += $(this).attr('qxun-template-id') + ',';
          choosenTemplates.templateIds += $(this).attr('template-id') + ',';
        }
      });
      choosenTemplates.qxunTemplateIds = choosenTemplates.qxunTemplateIds.slice(0, -1);
      choosenTemplates.templateIds = choosenTemplates.templateIds.slice(0, -1);
      templateJson = {
        'TemplateName': $('#template_name').val(),
        'ArticleIds': choosenTemplates.qxunTemplateIds,
        'TemplateIds': choosenTemplates.templateIds,
        'AppId': $('#wechat-accounts option:selected').data('app_id'),
        'AppSecret': $('#wechat-accounts option:selected').data('app_secret'),
        'OrignalId': $('#wechat-accounts option:selected').data('original_id'),
        'BrandId': $('#wechat-accounts option:selected').data('brand_id'),
        'AccountId': $('#wechat-accounts option:selected').val(),
        'template_id': template_id,
      };
      $.ajax({
        url: '/xaja/AjaxService/assets/save_multi_image_broadcast.json',
        type: 'POST',
        data: templateJson,
        success: function(data) {
          $('.wait_initial_form').show().removeClass('intouch-loader');
          that.loadMultiImage(true);
        },
        error: function(jqXHR, textStatus) {
          console.log('error', textStatus);
          $('.wait_initial_form').show().removeClass('intouch-loader');
        }
      });
    },

    saveSingleImageTemplate: function(e) {
      var template_id = $(e.currentTarget).data('template-id');
      var that = this;
      var templateJson = {};
      if ($('#template_title').val() == '') {
        ca_wechat.helper.showFlash(_campaign('Enter message title'), true);
        return false;
      }
      if ($('#template_summary').val() == '') {
        ca_wechat.helper.showFlash(_campaign('Enter message summary'), true);
        return false;
      }
      $('.wait_initial_form').show().addClass('intouch-loader');

      $.ajax({url: '/xaja/AjaxService/assets/get_wechat_content_by_org.json',
        dataType: 'json',
        success: function(res){
          console.log(res);
          var brandId = $('#wechat-accounts option:selected').data('brand_id');
          for(i=0; i<res.weChatOrgData.length; i++){
            if( res.weChatOrgData[i]['brand_id'] == brandId){
              break;
            }
          }
          if( that.$('.wechatcheck input[type="checkbox"]').is(':checked') == true) {
            that.tempUrl = 'https://capillary.qxuninfo.com/webapi/WeixinOauth/Authorize?appid=' +res.weChatOrgData[i].wechat_app_id+  '&callback=' + encodeURIComponent(that.$('.c-show-url input[type="text"]').val()) + '?original_id=' + res.weChatOrgData[i].original_id + '&scope=snsapi_base';
          }else{
            that.tempUrl = that.$('.c-show-url input[type="text"]').val();
          }

          templateJson = {
            'name': $('#template_name').val(),
            'title': $('#template_title').val(),
            'image': $('.upload_image_file img').attr('src'),
            'summary': $('#template_summary').val(),
            'content': encodeURIComponent(CKEDITOR.instances.wechat_content.getData()),
            'url': that.tempUrl,
            'AppId': $('#wechat-accounts option:selected').data('app_id'),
            'AppSecret': $('#wechat-accounts option:selected').data('app_secret'),
            'OrignalId': $('#wechat-accounts option:selected').data('original_id'),
            'BrandId': $('#wechat-accounts option:selected').data('brand_id'),
            'AccountId': $('#wechat-accounts option:selected').val(),
            'template_id': template_id,
          };

          $.ajax({
            url: '/xaja/AjaxService/assets/save_single_image_broadcast.json',
            type: 'POST',
            data: templateJson,
            success: function(data) {
              $('.wait_initial_form').show().removeClass('intouch-loader');
              that.loadSingleImage(true);
            },
            error: function(jqXHR, textStatus) {
              console.log('error', textStatus);
              $('.wait_initial_form').show().removeClass('intouch-loader');
            }
          });
        }
      });
    },
    showImageUpload: function() {
      $('#upload_image').trigger('click');
    },
    uploadImage: function() {
      if (this.creative_assets.scope == 'WECHAT') {
        $("#image_upload").submit();
        $('#image_upload').val('');
      }
    },
    createNewWechatTemplate: function() {
      switch ($('#template_scope').val()) {
        case 'WECHAT_MULTI_TEMPLATE':
          this.$el.find('.c-content #container #creative_tpl_container').addClass('hide');
          _.templateSettings.variable = "data";
          this.$el.find('.c-content #container').append(_.template($('#multi_image_broadcast_create_tpl').html())(undefined));
          break;
        case 'WECHAT_SINGLE_TEMPLATE':
          this.$el.find('.c-content #container #creative_tpl_container').addClass('hide');
          _.templateSettings.variable = "data";
          this.$el.find('.c-content #container').append(_.template($('#single_image_broadcast_create_tpl').html())({
            data: undefined,
            url: '',
            isInternalUrl: 0
          }));
          // this.$el.find('.c-content #container').append(_.template($('#single_image_broadcast_create_tpl').html())(undefined));
          CKEDITOR.replace('wechat_content', {
            toolbar: 'Basic'
          });
          this.activateImageUpload();
          break;
      }
    },
    activateImageUpload: function() {
      $("#image_upload").submit(function() {
        $('.wait_initial_form').show().addClass('intouch-loader');
        var formObj = $(this);
        var formURL = '/xaja/AjaxService/assets/upload_image.json?maxSize=64000&validExtensions=jpg,jpeg,png';
        if (window.FormData !== undefined) {
          var formData = new FormData(this);
          $.ajax({
            url: formURL,
            type: 'POST',
            data: formData,
            mimeType: 'multipart/form-data',
            contentType: false,
            cache: false,
            processData: false,
            success: function(data) {
              data = JSON.parse(data);
              if (!data.error) {
                var imageInfo = JSON.parse(data.info);
                $('.upload_image_file img').attr({
                  'src': imageInfo.public_url
                });
                $('.uploadPic div:nth-child(1)').removeClass('hide');
                $('.uploadPic div:nth-child(2)').addClass('hide');
              } else {
                ca_wechat.helper.showFlash(data.error, true);
              }
              $('.wait_initial_form').hide().addClass('intouch-loader');
            },
            error: function(jqXHR, textStatus) {
              $('.wait_initial_form').hide().addClass('intouch-loader');
              ca_wechat.helper.showFlash(_campaign('oops something went wrong while uploading image!'), true);
              console.log('oops something went wrong while uploading image to s3', jqXHR, textStatus);
            }
          });
          return false;
        }
      });
    },
    showTemplate: function(e) {
      var type = this.$(e.currentTarget).val();
      $('#ca-option-weChatAccounts').remove();
      if (type == 'email') {
        this.showEmailTemplate();
      } else if (type == 'image') {
        this.showImageGallery();
      } else if (type == 'coupon') {
        this.showCouponTemplate();
      } else if (type == 'social') {
        this.showSocialTemplates();
      }else if (type == 'mobile_push'){
        this.showMobilePushTemplates();
      }
    },
    showMobilePushTemplates: function(){
      $('#ca-option-div').hide();
      var that = this;
      var tags = {};
      var edm_user_id;
      var scopes_available;
      var mobilePushAccounts; 
      var url = "/xaja/AjaxService/assets/initial_mobile_push_data.json";
      $.getJSON(url, function(data) {
        if (data.tags) tags = data.tags;
        if (data.scopes) scopes_available = data.scopes;
        if (data.mobilePushAccounts) mobilePushAccounts = data.mobilePushAccounts;
      }).done(function() {
        that.$('#social_template_div').empty();
        that.$('#email_template_div').empty().hide();
        that.$('#image_gallery_div').empty().hide();
        that.$('#coupon_template_div').hide();
        that.$('#mobile_push_template_div').empty();
        that.$('#social_template_div').hide();
        that.$('#mobile_push_template_div').show();
        that.creative_assets.initialize('#mobile_push_template_div', tags, edm_user_id, scopes_available, 'MOBILEPUSH');
        that.creative_assets.renderMobilePush(mobilePushAccounts ,tags);
      }).always(function() {
        $('select[name^=template_type] option[value="mobile_push"]').attr("selected", "selected");
      });
    },
    showSocialTemplates: function() {
      $('#ca-option-div').hide();
      var that = this;
      var tags = {};
      var edm_user_id;
      var scopes_available;
      var weChatAccounts;
      var url = "/xaja/AjaxService/assets/initial_social_data.json";
      $.getJSON(url, function(data) {
        if (data.tags) tags = data.tags;
        if (data.scopes) scopes_available = data.scopes;
        if (data.weChatAccounts) weChatAccounts = data.weChatAccounts;
      }).done(function() {
        that.$('#social_template_div').empty();
        that.$('#email_template_div').empty().hide();
        that.$('#image_gallery_div').empty().hide();
        that.$('#coupon_template_div').hide();
        that.$('#mobile_push_template_div').hide();
        that.$('#social_template_div').show();
        that.creative_assets.initialize('#social_template_div', tags, edm_user_id, scopes_available, 'WECHAT');
        that.creative_assets.renderWeChat(weChatAccounts);
      }).always(function() {
        $('select[name^=template_type] option[value="social"]').attr("selected", "selected");
      });
    },
    showEmailTemplate: function() {
      $('#ca-option-div').hide();
      var that = this;
      var url = "/xaja/AjaxService/assets/initial_creative_data.json";
      var tags = {};
      var edm_user_id = "";
      var scopes_available;
      var org_lang_mapping;
      var languages = {};
      $.getJSON(url, function(data) {
        tags = data.tags;
        edm_user_id = data.edm_user_id;
        scopes_available = data.scopes;
        languages.base_language_id = data.base_language;
        languages.all_lang = [];
        for (var key in data.language) {
          languages.all_lang[key] = data.language[key];
        }
      }).done(function() {
        that.$('#email_template_div').show();
        that.$('#image_gallery_div').hide();
        that.$('#social_template_div').empty().hide();
        that.$('#mobile_push_template_div').hide();
        that.$('#coupon_template_div').hide();
        that.creative_assets.initialize('#email_template_div', tags, edm_user_id, scopes_available, null, languages);
        that.creative_assets.render();
      }).always(function() {
        $('select[name^=template_type] option[value="email"]').attr("selected", "selected");
      });
    },
    showImageGallery: function() {
      $('#ca-option-div').hide();
      this.$('#image_upload').remove();
      this.$('#email_template_div').hide();
      this.$('#image_gallery_div').show();
      this.$('#coupon_template_div').hide();
      this.$('#social_template_div').empty().hide();
      this.$('#mobile_push_template_div').hide();
      this.creative_assets.initialize('#image_gallery_div', {}, [], 'perer_england');
      this.creative_assets.renderImageGallery();
      $('select[name^=template_type] option[value="image"]').attr("selected", "selected");
    },
    showCouponTemplate: function() {
      $('#ca-option-div').show();
      this.$('#coupon_template_div').html($('#creativeview').html());
      $('.wait_initial_form').show().addClass('intouch-loader');
      $("#creativeframe").load(function() {
        // do something once the iframe is loaded
        $('.wait_initial_form').show().removeClass('intouch-loader');
      });
      this.$('#email_template_div').hide();
      this.$('#image_gallery_div').hide();
      this.$('#coupon_template_div').show();
      this.$('#social_template_div').empty().hide();
      this.$('#mobile_push_template_div').hide();
      $('select[name^=template_type] option[value="coupon"]').attr("selected", "selected");
    }
  });
  app.CreativeViewInstance = new app.CreativeView();
  app.StickyView = Backbone.View.extend({
    el: "body",
    initialize: function() {},
    template: _.template($("#stickyview").html()),
    events: {
      "click #newlist": "newList",
      "click #sticky_cancel": "cancelList"
    },
    cancelList: function() {
      $(".c-content-cont:first").css("left", "10px");
      $(".c-content-cont.next").css("left", $(".c-content-cont").width() + 20);
      $(".c-content-cont.next").fadeOut();
    },
    newList: function(e) {
      $(".c-content-cont:first").css("left", -$(".c-content-cont").width());
      $(".c-content-cont.next").css("left", "10px");
      $(".c-content-cont.next").fadeIn();
    },
    render: function(callback) {
      $("#container").html(this.template());
      callback();
    }
  })
  app.StickyViewInstance = new app.StickyView();
  app.StickyListView = Backbone.View.extend({
    el: $(".sticky-table"),
    initialize: function() {
      this.collection.on('sync', this.afterFetched, this);
      this.collection.on('reset', this.afterReset, this);
      app.AddCustomerModelInstance.on('sync', this.fetchReRenderFn, this);
      app.RemoveCustomerModelInstance.on('sync', this.fetchReRenderFn, this);
    },
    render: function() {
      var height = $(".c-content").height() - 180;
      app.DT = $('.sticky-table').dataTable({
        "fnDrawCallback": function(oSettings) {
          if (!$("#popup_form")[0]) $(".dataTables_scrollBody").append('<form id="popup_form"><i class="arrow-icon"></i><a class="c-fright close-button" id="add_close_popup" href="#/add_close_popup">X</a><h3>' + _campaign("Add a customer") + '</h3><h6>' + _campaign("Mobile") + '</h6><input type="text" id="mobile" class="validate[regexcheck</^[0-9]+$/>;custom_val<' + _campaign("Mobile number should contain only digits from 0 to 9") + '>]"/><h6>' + _campaign("Email") + '</h6><input type="text" id="email" class="validate[regexcheck</^[a-zA-Z0-9-_+.]+@[a-zA-Z0-9-_.]+\.[a-zA-Z]+$/i;custom_val<' + _campaign("Email id should be of the form") + ' abc@xyz.com>] text-input"/><h6>' + _campaign("Name") + '</h6><input type="text" id="name" class="validate[required;regexcheck</^[a-zA-Z\\s\']+$/>;custom_val<' + _campaign("Name Must contain characters only from A to Z") + '>]"/><h6>' + _campaign("Custom Tag 1") + '</h6><input type="text" id="custometag1"/><h6>' + _campaign("Custom Tag 2") + '</h6><input type="text" id="customtag2"/><br><a id="c-add-customer" class="c-button c-fright" href="#/sync_customer/add">' + _campaign("Add") + '</a></form><form id="remove_popup_form"><i class="arrow-icon"></i><a id="remove_close_popup" class="c-fright close-button" href="#/remove_close_popup">X</a><h3>' + _campaign("Remove customer") + '</h3><h6>' + _campaign("Mobile") + '</h6><input type="text" id="mobile" class="validate[regexcheck</^[0-9]+$/>;custom_val<' + _campaign("Mobile number should contain only digits from 0 to 9") + '>]"/><h6>' + _campaign("Email") + '</h6><input type="text" id="email" class="validate[regexcheck</^[a-zA-Z0-9-_+.]+@[a-zA-Z0-9-_.]+\.[a-zA-Z]+$/i;custom_val<' + _campaign("Email id should be of the form") + ' abc@xyz.com>] text-input"/><h6>' + _campaign("Name") + '</h6><input type="text" id="name" class="validate[required;regexcheck</^[a-zA-Z\\s\']+$/>;custom_val<' + _campaign("Name Must contain characters only from A to Z") + '>]"/><br><a id="c-remove-customer" class="c-button c-fright" href="#/sync_customer/remove">' + _campaign("Remove") + '</a></form><div id="groups_popupform"><form class="c-popup-cont-form" style="overflow: hidden; display: block;"><i class="arrow-icon"></i><a id="group_close_popup" class="c-fright close-button" href="#/group_close_popup">X</a><h4>' + _campaign("Add Group Tags") + '</h4><h7>' + _campaign("Maximum 15 group tags are allowed.") + '</h7><div class="tags-container"><div class="tags"><h6 class="">' + _campaign("Tag1") + '</h6><input type="text" id="1" class="validate[required]" placeholder="" name="group_tag_1" style="opacity: 1;"></div> <div class="tags"><h6 class="opacity0">' + _campaign("Tag2") + '</h6><input type="text" id="future-tag" placeholder="' + _campaign("Add new tag") + '"></div></div><br><div class="but-group-pop"><a id="c-add-group" class="c-button c-fright" href="#/sync_customer/addgroup">' + _campaign("Add") + '</a></div></form></div>');
          $("#popup_form").html('<i class="arrow-icon"></i><a class="c-fright close-button" id="add_close_popup" href="#/add_close_popup">X</a><h3>' + _campaign("Add a customer") + '</h3><h6>' + _campaign("Mobile") + '</h6><input type="text" id="mobile" class="validate[regexcheck</^[0-9]+$/>;custom_val<' + _campaign("Mobile number should contain only digits from 0 to 9") + '>]"/><h6>' + _campaign("Email") + '</h6><input type="text" id="email" class="validate[skip_locale<zh-cn>;regexcheck</^[a-zA-Z0-9-_+.]+@[a-zA-Z0-9-_.]+\.[a-zA-Z]+$/i;custom_val<' + _campaign("Email id should be of the form ") + 'abc@xyz.com>] text-input"/><h6>' + _campaign("Name") + '</h6><input type="text" id="name" class="validate[required;skip_locale<zh-cn>;regexcheck</^[a-zA-Z\\s\']+$/>;custom_val<' + _campaign("Name Must contain characters only from A to Z") + '>]"/><h6>' + _campaign("Custom Tag 1") + '</h6><input type="text" id="custometag1"/><h6>' + _campaign("Custom Tag 2") + '</h6><input type="text" id="customtag2"/><br><a id="c-add-customer" class="c-button c-fright" href="#/sync_customer/add">' + _campaign("Add") + '</a>');
          $("#remove_popup_form").html('<i class="arrow-icon"></i><a id="remove_close_popup" class="c-fright close-button" href="#/remove_close_popup">X</a><h3>' + _campaign("Remove customer") + '</h3><h6>' + _campaign("Mobile") + '</h6><input type="text" id="mobile" class="validate[regexcheck</^[0-9]+$/>;custom_val<' + _campaign("Mobile number should contain only digits from 0 to 9") + '>]"/><h6>' + _campaign("Email") + '</h6><input type="text" id="email" class="validate[regexcheck</^[a-zA-Z0-9-_+.]+@[a-zA-Z0-9-_.]+\.[a-zA-Z]+$/i;custom_val<' + _campaign("Email id should be of the form ") + 'abc@xyz.com>] text-input"/><h6>' + _campaign("Name") + '</h6><input type="text" id="name" class="validate[required;regexcheck</^[a-zA-Z\\s\']+$/>;custom_val<' + _campaign("Name Must contain characters only from A to Z") + '>]"/><br><a id="c-remove-customer" class="c-button c-fright" href="#/sync_customer/remove">' + _campaign("Remove") + '</a>');
          $('.wait_initial_form').show().removeClass('intouch-loader');
          $('#popup_form').hide();
          $('#remove_popup_form').hide();
          //var id=$(".dataTables_filter").attr("id");
          //$(".dataTables_filter label").html('<div class="search-container"><i class="c-search-icon c-fleft"></i><input  aria-controls="'+id.substring(0,id.lastIndexOf("_"))+'" type="text" class="c-search-text c-fright" placeholder="campaign name"></div>');
        },
        "bProcessing": true,
        "sPaginationType": "full_numbers",
        "sScrollY": height,
        "oLanguage": {
          "sZeroRecords": "'" + _campaign("no data present") + "'",
          "oPaginate": {
            "sPrevious": "<",
            "sNext": ">",
            "sLast": ">>",
            "sFirst": "<<"
          }
        },
        "aaData": app.StickyCollectionInstance.toJSON(),
        "aoColumns": [{
          "mData": "group_label"
        }, {
          "mData": "customer_count"
        }, {
          "mData": "html"
        }]
      });
      console.log(app.DT);
    },
    fetchFn: function() {
      $('.wait_initial_form').show().addClass('intouch-loader');
      var that = this;
      this.collection.fetch({
        success: function(res) {
          console.log(res);
          that.render();
        }
      });
    },
    fetchReRenderFn: function() {
      var that = this;
      this.collection.fetch({
        success: function(res) {
          app.DT.fnClearTable();
          app.DT.fnAddData(app.StickyCollectionInstance.toJSON());
          app.DT.fnDraw();
        }
      });
    },
    resetFn: function() {
      var that = this;
      this.collection.fetch({
        success: function(res) {
          that.render();
        },
        reset: true
      });
    }
  })
  app.StickyListViewInstance = new app.StickyListView({
    collection: app.StickyCollectionInstance
  });
  //define master view (List of campaigns)
  app.InitialRenderView = Backbone.View.extend({
    el: $(".c-container"),
    initialize: function(callback) {
      this.callBack = callback; // Callback will be called on completion of render
      this.render();
    },
    events: {
      "click #new-campaign": "newCampaign",
      "click .c-status-option": "statusSelect",
      "change .c-type-select": "typeSelect",
      "keyup .c-search-text": "searchSelect",
      "click .c-menu-list.performance": "performanceSelect",
      "click .c-menu-list.creative": "creativeSelect",
      "click .c-menu-list.sticky": "stickySelect",
      "click .c-menu-list.dashboard": "dashboardSelect",
      "click #org_credits_menu": "populatePopover",
      "click #CreateNewCampaign": "createCampaign",
      "click #cancel_create": "cancelCreate",
      "change #campaign_type": "modifyForm",
      "change #campaign_objective": "modifyObjective",
      "change #survey_type": "hideShowBrandLogo",
      "change #deal_type": "modifyDealType",
      "click #is_ga_enabled": "toggleGAFields",
      "click #enable_roi_reports": "toggleROIFields",
      "click #buy_credits": "showCreditForm",
      "click #buy_more_credits": "updateCredits",
      "click #cancel_credit": "closePopover",
      "click #close_credit_pop": "closePopover",
      "click #for-outbound-advanced": "showAdvanceSettings",
      "click #for-outbound-settings": "hideAdvanceSettings"
    },
    populatePopover: function(e) {
      $('.formError').hide();
      $('#pop_credit').toggle();
      app.router.navigate('/credits', {
        trigger: true
      });
    },
    performanceSelect: function() {
      $('.formError').hide();
      app.router.navigate('/performance', {
        trigger: true
      });
    },
    stickySelect: function() {
      $('.formError').hide();
      app.router.navigate('/sticky', {
        trigger: true
      });
    },
    creativeSelect: function(e) {
      $('.formError').hide();
      app.router.navigate('/creative', {
        trigger: true
      });
    },
    dashboardSelect: function() {
      $('.formError').hide();
      this.initializePagination();
      app.router.navigate('/dashboard', {
        trigger: true
      });
    },
    searchSelect: function(e) {
      this.initializePagination();
      if ($(e.target).val() != "") {
        var txt = $(e.target).val();
        if (txt.length > 3) app.router.navigate('/search/' + txt, {
          trigger: true
        });
      } else {
        app.router.navigate('/search', {
          trigger: true
        });
      }
    },
    typeSelect: function(e) {
      this.initializePagination();
      app.router.navigate('/status/' + $(e.target).val(), {
        trigger: true
      });
      if ($(e.target).val() == "outbound" || $(e.target).val() == "all") {
        $(".c-tracker-inner").show();
      } else {
        $(".c-tracker-inner").hide();
      }
    },
    statusSelect: function(e) {
      this.initializePagination();
      $(".c-status-option .sel").removeClass("sel");
      $(e.target).addClass("sel");
    },
    initializePagination: function() {
      $("#start").val(0);
    },
    newCampaign: function(e) {
      $(".d-content-cont").css("left", -$(".d-content-cont").width());
      $(".c-content-cont.next").fadeIn();
      $(".c-content-cont.next").css("left", "10px");
      $("#for-outbound-advanced").removeClass("hide");
      $("#for-outbound-settings").addClass("hide");
      $(".for-outbound-holder").addClass("hide");
      var createModel = new app.CreateCampaignModel({});
      createModel.fetch();
      createModel.on('change', function() {
        app.CreateNewInstance = new app.CreateNewView({
          el: "#create_new_campaign",
          model: createModel
        });
      });
      $(".c-context-help").attr("href", "https://support.capillarytech.com/solution/categories/96975/folders/167460/articles/124267-creating-new");
    },
    createCampaign: function(e) {
      var obj_elems = $(e.currentTarget).data('obj_elems');
      var obj_final = this.getObjectivesSelection(obj_elems);
      var campaign_tags = $('#ca_tag_names').val();
      e.preventDefault();
      if (!$('#newcampaign').validationEngine({
          promptPosition: "centerRight",
          validationEventTriggers: 'keyup blur',
          success: false,
          scroll: false,
          returnIsValid: true
        })) {
        return;
      } else {
        if ($("#campaign_type").val() != "timeline") {
          var status = validateDateRange('cnew_start_date', 'cnew_end_date');
          if (!status || $('#cnew_start_date').val() == '' || $('#cnew_end_date').val() == '') {
            $('.flash_message').show().addClass('redError').html(_campaign("Invalid date range given!"));
            setTimeout(function() {
              $('.flash_message').removeClass('redError').fadeOut('fast');
            }, 3000);
            return;
          }
        }
      }
      if ($("#campaign_type").val() == "outbound" || $("#campaign_type").val() == "bounceback") {
        var camp_objective_val = $("#campaign_objective option:selected").val();
        if (camp_objective_val == "init_affiliate") {
          $('.flash_message').show().addClass('redError').html(_campaign("Please select valid Campaign Objective"));
          setTimeout(function() {
            $('.flash_message').removeClass('redError').fadeOut('fast');
          }, 3000);
          return;
        }
      }
      if ($("#campaign_type").val() == "bounceback") {

        is_test_control_enabled = encodeURIComponent($('#is_test_control_enabled_for_bounceback:checked').val());
        if (is_test_control_enabled === "on")
          is_test_control_enabled = 1;
        else
          is_test_control_enabled = 0;
        var ajaxUrl = '/xaja/AjaxService/em_framework/create_campaign.json?';
        $('.wait_message').show().addClass('indicator_1');
        $.post(ajaxUrl, {
          campaign_name: encodeURIComponent($('#campaign_name').val()),
           description: encodeURIComponent($('#campaign_desc').val()),
          start_date: encodeURIComponent($('#cnew_start_date').val()),
          end_date: encodeURIComponent($('#cnew_end_date').val()),
          issued_at: encodeURIComponent('issued_at'),
          client_scope: encodeURIComponent('client_scope'),
          campaign_objective: encodeURIComponent(obj_final),
          campaign_tags: encodeURIComponent(campaign_tags),
          is_test_control_enabled: is_test_control_enabled,
          campaign_frm_dashboard: true
        }, function(data) {
          if (data.error) {
            $('.flash_message').show().addClass('redError').html(data.error);
            setTimeout(function() {
              $('.flash_message').removeClass('redError').fadeOut('fast');
            }, 3000);
            $('.wait_message').hide().removeClass('indicator_1');
          } else {
            if (data.result != null) {
              if (data.coupons) {
                 if(data.new_dvs_enabled){
                  window.location.href = '/campaign/DvsHome?id=' + data.campaign_id;
                }else{
                  window.location.href = 'v2/coupons/CreateBounceBackCoupons?q=a&campaign_id=' + data.campaign_id; 
                }
              } else {
                if(data.new_dvs_enabled){
                  window.location.href = '/campaign/DvsHome?id=' + data.campaign_id;
                }else{
                  window.location.href = 'rules/basic_config/NewRule?campaign_id=' + data.campaign_id + "&mode=create";  
                }
                
              }
            }
          }
        }, 'json');
        return;
      }
      var org_id = $('#c_org_id').val();
      if (org_id == -1) return;
      // Timeline validations
      if ($("#campaign_type").val() == 'timeline') {
        var start_time = +$('#timeline_start_minute').val();
        var end_time = +$('#timeline_end_minute').val();
        if (start_time >= end_time) {
          $('.flash_message').show().addClass('redError').html(_campaign("Invalid time range given!"));
          setTimeout(function() {
            $('.flash_message').removeClass('redError').fadeOut('fast');
          }, 3000);
          return;
        }
      }
      if ($('#campaign_type').val() == 'referral' && $('#defaultPos').is(':checked')) checkForDefault();
      else this.progressCreation(obj_final);
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
    progressCreation: function(obj_final) {
      $('.wait_message').show().addClass('indicator_1');
      var that = this;
      var name = $('#campaign_name').val();
      var type = $('#campaign_type').val();
      var ou_selected_id = $('#ou_campaign').val();
      var campaign_tags = $('#ca_tag_names').val();
      var createModel = new app.CreateCampaignModel();
      var form_data = $('#newcampaign').serializeArray();
      var obj_index = 0;
      var tag_index = 0;
      for (var i = 0; i < form_data.length; i++) {
        if (form_data[i]['name'] == "campaign_objective") {
          obj_index = i;
          break;
        }
      }
      form_data.splice(obj_index, 1);
      for (var i = 0; i < form_data.length; i++) {
        if (form_data[i]['name'] == "ca_tag_names") {
          tag_index = i;
          break
        }
      }
      form_data.splice(tag_index, 1);
      form_data.push({
        name: "campaign_objective",
        value: obj_final
      });
      form_data.push({
        name: "campaign_tags",
        value: campaign_tags
      });
      form_data.push({name: "ou_selected_id",value: ou_selected_id});
      createModel.set({
        camp_data: jQuery.param(form_data)
      });
      createModel.save(createModel.toJSON(), {
        success: function(response) {
          if (response.attributes.istatus == 'success') {
            $('.formError').remove();
            $('#newcampaign')[0].reset();
            setCampaignDate('cnew_start_date');
            var c_id = response.attributes.campaign_id;
            var msg = _campaign("Campaign ") + '"' + name + '"' + _campaign(" successfully created");
            $('.wait_message').hide().removeClass('indicator_1');
            localStorage.setItem("new_camp_name", name);
            if (type == 'outbound') window.location.href = '/campaign/v3/base/CampaignOverview#campaign/' + c_id + '/' + name;
            if (type == 'referral') window.location.href = '/campaign/v2/referral/ReferralMessages?q=a&campaign_id=' + c_id + '&flash=' + msg;
            if (type == 'survey') window.location.href = '/campaign/v2/csat/SurveyDashboard?q=a&campaign_id=' + c_id + '&tab=overview&flash=' + msg;
            if (type == 'timeline') window.location.href = '/campaign/timeline/TimelineCampaignOverview#timeline/campaign/' + c_id + '/' + name;
          } else {
            createModel.showError(response.attributes.error_msg);
            $('.wait_message').hide().removeClass('indicator_1');
          }
        },
        error: function(response) {
          createModel.showError(response.attributes.error_msg);
          $('.wait_message').hide().removeClass('indicator_1');
        },
        wait: true
      });
      $('formErrorContent').hide();
    },
    cancelCreate: function() {
      $(".d-content-cont").css("left", 'auto').fadeIn();
      $(".c-content-cont.next").css("left", "100%");
      $(".c-content-cont.next").fadeOut();
      $('.formError').remove();
      $(".c-context-help").attr("href", "https://support.capillarytech.com/support/solutions/articles/4000002477-understanding-the-campaigns-user");
    },
    modifyForm: function() {
      var type = $('#campaign_type').val();
      $('div.form-controls').each(function() {
        if ($(this).hasClass('main') || $(this).hasClass('for-' + type)) $(this).removeClass('hide');
        else $(this).addClass('hide');
      });
      // for now adding redirection to old flow for bounceback campaign
      //TODO: In future bounceback should be also created from this flow only
      if (type == 'bounceback') {
        $('#for-bounceback-holder').removeClass('hide');
        return;
      }
      if (type == 'outbound') {
        if ($('#is_ga_enabled').is(':checked')) {
          $('#ga_track').removeClass('hide');
          $('#ga_source').removeClass('hide');
        } else {
          $('#ga_track').addClass('hide');
          $('#ga_source').addClass('hide');
        }
        $(".timeline-test-control").addClass("hide");
        $(".outbound-test-control").removeClass("hide");
        if ($('#isRefCamp').is(':checked')) $('#selectReferral').removeClass('hide');
        else $('#selectReferral').addClass('hide');
        if ($('#isSurveyCamp').is(':checked')) $('#selectSurvey').removeClass('hide');
        else $('#selectSurvey').addClass('hide');
        if ($('#isRecoCamp').is(':checked')) $('#selectReco').removeClass('hide');
        else $('#selectReco').addClass('hide');
      }
      if (type == 'referral') {
        if ($('#online').is(':checked')) $('#microsite_container').removeClass('hide');
        else $('#microsite_container').addClass('hide');
        if ($('#defaultPos').is(':checked')) $('#incentive_container').removeClass('hide');
        else $('#incentive_container').addClass('hide');
      }
      if (type == 'timeline') {
        $('#timeline-minutes').removeClass('hide');
        $('#daterange-container').addClass('hide');
        $(".outbound-test-control").addClass("hide");
        $(".timeline-test-control").removeClass("hide");
        if ($('#is_ga_enabled').is(':checked')) {
          $('#ga_track').removeClass('hide');
          $('#ga_source').removeClass('hide');
        } else {
          $('#ga_track').addClass('hide');
          $('#ga_source').addClass('hide');
        }
      }
    },
    hideShowBrandLogo: function() {
      var type = $('#survey_type').val();
      var parent_div = $('#brand_logo').parent().parent();
      type == 'CLOUDCHERRY' ? parent_div.hide() : parent_div.show();
    },
    toggleROIFields: function() {
      $("#selectRoiReportType").toggleClass('hide');
    },
    toggleGAFields: function(e) {
      $('#ga_track').toggleClass('hide');
      $('#ga_source').toggleClass('hide');
      $("#ga_name").toggleClass("validate[required;regexcheck</^[0-9- _a-zA-Z]+$/>;custom_val<" + _campaign("Label must be alpha numeric and can have underscore,space") + ">]/");
      $("#ga_source_name").toggleClass("validate[required;regexcheck</^[0-9 _a-zA-Z]+$/>;custom_val<" + _campaign("Source Name must be alpha numeric and can have underscore,space") + ">]/");
      var val = $("#is_ga_enabled").val();
      if (val == "1") {
        $("#is_ga_enabled").val("0");
        $(".ga_nameformError").remove();
      } else {
        var camp_name = $("#campaign_name").val().trim();
        var today = new Date();
        var date = today.getDate() + "-" + today.getMonth() + "-" + today.getFullYear();
        $("#is_ga_enabled").val("1");
        if (camp_name != "") camp_name = camp_name + "__" + date;
        $("#ga_name").val(camp_name);
        $("#ga_name").focus();
        $("#ga_source_name").val("Capillary Tech");
      }
    },
    closePopover: function(e) {
      $('#pop_credit').hide();
      $('.credit_summary').removeClass('hide');
      $('.sms_credit_hidden').addClass('hide');
    },
    showCreditForm: function(e) {
      $('.credit_summary').addClass('hide');
      $('.sms_credit_hidden').removeClass('hide');
      $('#sms_credit_val').val('');
      $('#error1').addClass('hide');
    },
    updateCredits: function(e) {
      var that = this;
      var credit = $('#sms_credit_val').val();
      $('#error1').addClass('hide');
      if (credit == "") {
        $('#error1').removeClass('hide').html(_campaign("Please enter sms credits value !"));
        return;
      }
      //checking for is number or not
      if (isNaN(credit)) {
        $('#error1').removeClass('hide').html(_campaign("Specify integral values for credits !"));
        return;
      }
      //checking for negative number
      if (credit < 0) {
        $('#error1').removeClass('hide').html(_campaign("Specify integral values for credits !"));
        return;
      }
      if (Math.ceil(credit) != Math.floor(credit)) {
        $('#error1').removeClass('hide').html(_campaign("Specify integral values for SMS credits !"));
        return;
      }
      var credit_model = new app.CreditModel();
      var updated_credit = $('#old_sms_credit').val() + credit;
      var creditDetails = {
        sms_credit: updated_credit
      };
      credit_model.save(creditDetails, {
        success: function(response) {
          if (response.attributes.istatus == 'success') {
            var msg = _campaign("Request email has been sent to the concerned authority successfully.");
            $('.flash_message').show().removeClass('redError').html(msg);
            setTimeout(function() {
              $('.flash_message').fadeOut('fast');
            }, 3000);
          } else {
            credit_model.showError(response.attributes.error_msg);
          }
        },
        error: function(response) {
          credit_model.showError(response.attributes.message);
        },
        wait: true
      });
      this.closePopover();
    },
    render: function() {
      var is_perf = $('#is_performance_enabled').val();
      var tmpl = _.template($("#initialrender").html());
      this.$el.html(tmpl({
        is_perf: is_perf
      }));
      return this;
    },
    showAdvanceSettings: function(e) {
      $("#for-outbound-advanced").toggleClass("hide");
      $("#for-outbound-settings").toggleClass("hide");
      $(".for-outbound-holder").toggleClass("hide");
    },
    hideAdvanceSettings: function(e) {
      $("#for-outbound-advanced").toggleClass("hide");
      $("#for-outbound-settings").toggleClass("hide");
      $(".for-outbound-holder").toggleClass("hide");
    }
  });
  app.CreditView = Backbone.View.extend({
    initialize: function() {
      this.render();
    },
    render: function() {
      var sms = this.model.get('sms_credit');
      var email = this.model.get('email_credit');
      $('#sms_credit_value').html(sms);
      $('#sms_credit_val').val(sms);
      $('#email_credits').html(email);
      $('.credit_wait_message').hide().removeClass('intouch-loader');
    }
  });
  app.CreateNewView = Backbone.View.extend({
    initialize: function() {
      this.render();
    },
    events: {
      "change .obj-select": "modifyObjective",
      "change input:radio[name=content]": "modifyIncentive",
      "change #generic-select": "modifyGeneric"
    },
    render: function() {
      var tmpl = _.template($("#campaignoubased").html());
      if (typeof this.model.get('org_units') !== 'undefined') {
        var ou_elements = JSON.parse(this.model.get('org_units'));
        var result = Object.keys(ou_elements).map(function(key) {
          return {
            id: this[key],
            value: key
          };
        }, ou_elements);
        this.$('#addoucampaigntemplate').html(tmpl({
          group: result
        }));
      }
      var addeditcampaign = _.template($("#campaignaddedit").html(), {});
      this.$("#addcampaigntemplate").html(addeditcampaign);
      $("input:radio[name=content]:first").click();
      this.renderObjective();
      $(".wait_metadata_form").removeClass("intouch-loader");
      var list = this.model.get('ref_list');
      var org_id = this.model.get('c_org_id');
      var min_sms_hour = +this.model.get('c_min_sms_hour');
      var max_sms_hour = +this.model.get('c_max_sms_hour');
      var valid_min_hours = _.range(min_sms_hour, max_sms_hour);
      var valid_max_hours = _.range(min_sms_hour + 1, max_sms_hour + 1);
      var valid_min_hours_options = '';
      _.each(valid_min_hours, function(hour) {
        var hour_val = hour + ':00';
        if (hour < 10) hour_val = "0" + hour + ':00';
        valid_min_hours_options += '<option value="' + hour * 60 + '">' + hour_val + '</option>';
      });
      var valid_max_hours_options = '';
      _.each(valid_max_hours, function(hour) {
        var hour_val = hour + ':00';
        if (hour < 10) hour_val = "0" + hour + ':00';
        valid_max_hours_options += '<option value="' + hour * 60 + '">' + hour_val + '</option>';
      });
      list = JSON.parse(list);
      var options = '';
      _.each(list, function(val, key) {
        options += '<option value="' + val + '">' + key + '</option>';
      });
      $('#referral_campaigns').html(options);
      $('#timeline_start_minute').html(valid_min_hours_options);
      $('#timeline_end_minute').html(valid_max_hours_options);
      var r_list = this.model.get('roi_report_type');
      r_list = JSON.parse(r_list);
      var options = '';
      _.each(r_list, function(val, key) {
        options += '<option value="' + val + '">' + key + '</option>';
      });
      $('#roi_report_type').html(options);
      var list = this.model.get('survey_list');
      list = JSON.parse(list);
      var options = '';
      _.each(list, function(val, key) {
        options += '<option value="' + val + '">' + key + '</option>';
      });
      $('#survey_campaigns').html(options);
      var list = this.model.get('survey_type');
      list = JSON.parse(list);
      var options = '';
      _.each(list, function(val, key) {
        options += '<option value="' + val + '">' + val + '</option>';
      });
      $('#survey_type').html(options);
      var list = this.model.get('reco_plan_list');
      list = JSON.parse(list);
      var options = '';
      _.each(list, function(val, key) {
        options += '<option value="' + val + '">' + key + '</option>';
      });
      $('#reco_campaigns').html(options);
      if (this.model.get('is_schedule') == 1) $('#report_schedule').attr('checked', true);
      else $('#conquest_schedule').remove();
      $('#c_org_id').val(org_id);
      $("#newcampaign").validationEngine({
        promptPosition: 'centerRight',
        validationEventTriggers: 'keyup blur',
        success: false,
        scroll: true
      });
      $("#cnew_start_date").datepicker({
        minDate: 0,
        showOn: 'both',
        yearRange: '2013:2025',
        changeMonth: true,
        changeYear: true,
        buttonImage: '/images/calendar-icon.gif',
        buttonImageOnly: true,
        defaultDate: 0,
        dateFormat: 'yy-mm-dd'
      });
      $("#cnew_end_date").datepicker({
        minDate: 1,
        showOn: 'both',
        yearRange: '2013:2025',
        changeMonth: true,
        changeYear: true,
        buttonImage: '/images/calendar-icon.gif',
        buttonImageOnly: true,
        dateFormat: 'yy-mm-dd'
      });

      var zone = $("body").attr("org-timezone") ;
      console.log("zone is : "+zone) ;
      var startMoment = moment().tz(zone) ;
      
      $( "#cnew_start_date" ).datepicker( "option", "minDate", new Date(startMoment.year(), startMoment.month(), startMoment.date()) );
      $("#cnew_start_date").val(startMoment.format("YYYY-MM-DD"));  

      var endMoment = startMoment.clone().add(1,"days") ;
      $("#cnew_end_date").datepicker( "option", "minDate", new Date(endMoment.year(), endMoment.month(), endMoment.date()) );

      $('.formErrorContent').hide();
    },
    findLevel: function(lev, elem) {
      var pid = this.ui_elements[elem]['pid'];
      if (pid == -1) return lev;
      return this.findLevel(lev + 1, pid);
    },
    modifyObjective: function(e) {
      var ui_elements = this.ui_elements;
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
      $(e.currentTarget).parent().siblings(".help_text_create").first().text(_campaign(help_text));
    },
    getClasses: function(pid, classes) {
      ui_elements = this.ui_elements;
      if (pid == -1) return classes;
      else {
        classes = this.getClasses(parseInt(ui_elements[pid]['pid']), classes + " pid_" + pid.toString());
        return classes;
      }
    },
    renderObjectiveSub: function(elem_id, hide_sub) {
      var ui_elements = this.ui_elements;
      var el = ui_elements[elem_id];
      if (el['type'] == 'parent-select') {
        var div;
        if (hide_sub) div = '<div class ="for-content camp-child-new hide';
        else div = '<div class ="for-content camp-child-new';
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
            this.ui_elements[elem_id]['is_rendered'] = true;
            for (child in el['children']) {
              var cid = parseInt(el['children'][child]['id']);
              if (ui_elements[cid]) {
                var obj_sub = this.renderObjectiveSub(cid);
                if (obj_sub) div += obj_sub;
              }
            }
          }
        }
        div += '</div>';
        return div;
      } else if (el['type'] == 'parent-input') {
        var div = '<div class ="for-content camp-child-new';
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
      var ui_elements = this.model.get('ui_elements');
      ui_elements = JSON.parse(ui_elements);
      this.ui_elements = ui_elements;
      $('#CreateNewCampaign').attr('data-obj_elems', this.ui_elements);
      var select = '<select id="campaign_objective"' + ' name="campaign_objective" class="input-nsl campaign_objective obj-select is_selected"' + 'prev="id_';
      var first_elem_help;
      var elem_count = 0;
      for (elem in this.ui_elements) {
        if (this.ui_elements[elem]['pid'] == -1) {
          if (this.ui_elements[elem]['type'] == 'group-select') {
            this.ui_elements[elem]['is_rendered'] = true;
            var elem_prop = this.ui_elements[elem];
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
              if (this.ui_elements[child_prop['id']]) this.ui_elements[child_prop['id']]['is_rendered'] = true;
              var opt = '<option class="opt-capitalize" value="' + child + ' sid_' + elem_prop['children'][child]['id'] + '" id="sid_' + elem_prop['children'][child]['id'] + '" help="' + elem_prop['children'][child]['help'] + '">' + _campaign(child) + '</option>';
              optgroup += opt;
            }
            optgroup += '</optgroup>';
            select += optgroup;
          }
        }
        elem_count++;
      }
      select += '</select>';
      var select_span = '<span>' + select + '</span>';
      var help_span = '<span name="' + $("#objective-div").html(select_span);
      var help_text_div = '<br><p class="help_text_create">' + _campaign(first_elem_help) + '</p>'
      $("#objective-div").append(help_text_div);
      var c = 0;
      for (elem in this.ui_elements) {
        var lev = 0;
        lev = this.findLevel(0, elem);
        this.ui_elements[elem]['level'] = lev;
        if (!ui_elements[elem]['is_rendered']) {
          var objectiveSub;
          if (c == 0) objectiveSub = this.renderObjectiveSub(elem, false);
          else objectiveSub = this.renderObjectiveSub(elem, true);
          if (objectiveSub) $("#objective-div").append(objectiveSub);
          c++;
        }
      }
      $("#campaign_objective").prepend("<option value='init_affiliate' selected='selected' disabled>" + _campaign('Select Objective') + "</option>");
      $(".for-content").addClass('hide');
      $(".help_text_create").text("");
      $('.obj-select').not(':has(.hide)').addClass('is_selected');
      $('.obj-input').not(':has(.hide)').addClass('is_selected');
    }
  });
}(jQuery));
