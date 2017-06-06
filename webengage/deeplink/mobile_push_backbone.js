$.ajaxSetup({
  cache: false
});
var ca_mobilepush = new CreativeAssets();
//single mobile push view



//collection mobile push view 
ca_mobilepush.MobilePushTemplateCollectionView = Backbone.View.extend({
  template_type: 'mobile_push',
  scope: 'PUSH',
  fetchXhr: null,
  tpl: _.template($('#templates_collection_tpl').html()),
  events: {
    'click #create_mobile_push_template': 'createMobileTemplate',
    'keyup .ca-search-text': 'renderSearch',
    'click .all_template': 'renderAll',
    'click .favourite_template': 'renderFavourite',
    'change #template_scope': 'renderNewScope',
    'change #mobile-push-accounts' : 'renderByAccount'
 
  },
  initialize: function(data) {
     _.extend(this,data);
     this.listenTo(ca_mobilepush.mobilePushList, 'sync', this.renderTemplates);
     this.listenTo(ca_mobilepush.mobilePushList, 'reset', this.removeAll);
  },
  renderTemplates: function(data) {
    var templateModels = _.isArray(data) ? data : ca_mobilepush.mobilePushList.models;
    $('.ca_all_container_body').empty();
    _.each(templateModels, function(value, key) {
      console.log("madhu model",value);
      var mobilePushView = new ca_mobilepush.MobilePushTemplateView({
        model: value
      });
      $('.ca_all_container_body').append(mobilePushView.render().el);
    });
    $('.wait_initial_form').show().removeClass('intouch-loader');
  },
  renderAll: function() {
    console.log(ca_mobilepush.mobilePushList);
    this.$('.all_template').addClass('sel');
    this.$('.favourite_template').removeClass('sel');
    this.renderTemplates(ca_mobilepush.mobilePushList.models);
  },
  render: function() {
    console.log("madhu call come to here");
    this.$el.html(this.tpl({
      template_type: this.template_type,
      template_scope: this.scope,
      scopes_available: ca_mobilepush.scopesAvailable
    }));
    this.showComplete(true);
    return this;
    },
  renderNewScope: function(e) {
    $('.wait_initial_form').show().addClass('intouch-loader');
    this.removeAll();
    var scope = ((e) ? ($(e.currentTarget).val()) : 'MOBILEPUSH_TEMPLATE');
    ca_mobilepush.mobilePushList.scope = scope;
    $('.ca_top_view_container').attr('scope', scope);
    ca_mobilepush.mobilePushList.ajax_url = '/xaja/AjaxService/assets/get_all_mobile_push_templates.json';
      if (!_.isNull(this.fetchXhr)) {
        this.fetchXhr.abort();
      }
      this.fetchXhr = ca_mobilepush.mobilePushList.fetch({
         data: { account_id : $("#mobile-push-accounts").val(),scope : scope }
       
      });
  },
  renderByAccount:function(e){
    $('.wait_initial_form').show().addClass('intouch-loader');
    var account_id =  $(e.currentTarget).val();
    var scope = $("#template_scope").val();
    console.log(account_id); 
    ca_mobilepush.mobilePushList.ajax_url = '/xaja/AjaxService/assets/get_mobile_push_templates_by_accountid.json?account_id=account_id';
      if (ca_mobilepush.mobilePushList.ajax_url) {

      if (!_.isNull(this.fetchXhr)) {
        this.fetchXhr.abort();
      }
      this.fetchXhr = ca_mobilepush.mobilePushList.fetch({
         data: { account_id : $("#mobile-push-accounts").val(),
         scope : scope 
       }
       
      });
    }
       
  },
  renderFavourite: function() {
    this.$('.all_template').removeClass('sel');
    this.$('.favourite_template').addClass('sel');
    this.renderTemplates(_.filter(ca_mobilepush.mobilePushList.models, function(template) {
     return (template.get('is_favourite') * 1);
    }));
  },
  renderSearch: function(e) {
    var searchTerm = $(e.currentTarget).val();
    this.renderTemplates(_.filter(ca_mobilepush.mobilePushList.models, function(template) {
      console.log("testing madhu",template);
      return (template.get('name').indexOf(searchTerm) != -1 || (template.get('title') ? template.get('title').indexOf(searchTerm) != -1 : false));
    }));
  },
  removeAll: function() {
    this.$('.ca_all_container_body').empty();
    this.$('.ca_favourite_container_body').empty();
  },
   showComplete: function(show) {
    if (show) {
      this.$('.ca_complete_msg').show();
    } else {
      this.$('.ca_complete_msg').hide();
    }
  },
  createMobileTemplate: function(e){
    var self = this;
    var model = new MobileTemplateModel();
    this.$('.ca_top_create_new_container').hide();
    this.$('.ca_top_view_container').hide();
    this.$('.ca_top_edit_container').show();
    var that = this;
     that.editTemplateViewInstance = new CreativeAssetsMobilePushTemplateView({
       model: model, 
       el: that.$('.ca_top_edit_container'),
       mobilePushAccounts : $("#mobile-push-accounts option:selected").val(),
       allTags: this.allTags,
       tags : this.tags,
       scope : this.scope,
       template_scope: $(".ca-template-scope option:selected").val()
     });
     self.showEditLoader(false);
     that.editTemplateViewInstance.activeTab(e,"android");
     that.editTemplateViewInstance.render();
   },
   showEditLoader: function(show) {
    if (show) {
      this.$('.edit_template_loader').addClass('intouch-loader').show();
    } else {
      this.$('.edit_template_loader').removeClass('intouch-loader').hide();
    }
  }

  });

// create mobile push template view

var CreativeAssetsMobilePushTemplateView = Backbone.View.extend({
  tpl: _.template($('#ca_mobile_push_template_tpl').html()),
  currentTab : "android",
  textAreaClick: true,
  initialize: function(options) {
    _.extend(this,options);
     console.log("madhu initialize", this);
  },
  showError: function(msg) {
    $('.flash_message').show().addClass('redError').html(msg);
    setTimeout(function() {
      $('.flash_message').removeClass('redError').fadeOut('fast');
    }, 5000);
    },
    showMessage: function(msg) {
    $('.flash_message').removeClass('redError').show().html(msg);
    setTimeout(function() {
      $('.flash_message').fadeOut('fast');
    }, 5000);
    },
  showEditLoader: function(show) {
    if (show) {
      this.$('.edit_template_loader').addClass('intouch-loader').show();
    } else {
      this.$('.edit_template_loader').removeClass('intouch-loader').hide();
    }
  },  
  events: {
      'click .tags-container li .parent ': 'openSubTag',
      'click .insert_tag': 'insertTag',
      'click .ca-mobile-push-title': 'tagAreaClick',
      'click .ca-mobile-push-textarea': 'tagAreaClick',
      'click .ca-mobile-push-add' : 'createSecondary',
      'keyup .ca-mobile-push-textarea': 'updateOnKeyup',
      'click .ca-mobile-push-reset' : 'resetAttribute',
      'click .ca-mobile-push-reset-primary-container' : 'resetPrimaryContainer',
      'click .ca-mobile-push-delete-container': 'resetContainerAttribute',
      'keyup .ca-mobile-push-text-link' : 'setTitleLink',
      'change .primary-link' : 'showTitleLink',
      'change .secondary-link' : 'showTitleLink',
      'change .ios-secondary-link' : 'showTitleLink',
      'keyup .ca-mobile-push-set-text' : 'setText',
      'click .ca-mobile-push-preview': 'showPreview',
      'click .back_to_view':'backToView',
      'click .save_new_template': 'saveNewTemplate',
      'click #mob_android' : 'activeTab',
      'click #mob_ios' : 'activeTab',
      'click .ca-mobile-push-reset-div':'deleteContainer',
      'click .upload_image_file': 'showMobileImageUpload',
      'change #upload_image_android': 'uploadMobilePushImage',
      'change #upload_image_ios': 'uploadMobilePushImage',
      'click .ca-mobile-push-copy' : 'mobilePushCopyText',
      'click .ca-mobile-push-add-ios' : 'getSecondaryIOSContainer',
      'click .ca-mobile-push-reset-ios-div' : 'deleteIOSContainer',
      'click .ca-mobile-push-cta' : 'getSecondaryDetailIOSContainer',
      'click .ca-mobile-icon-ios':'deleteIOSDetailContainer',
      // 'click .preview-mobile-push' :"previewMobilePushNew",
      'click .create_ios' : 'createIOS',
      'click .create_android' : 'createAndroid',
      'click .save_android' : "saveTemplate",
      'click #ios-preview': "showPreview",
      'click #mobilepush_scope_selector .mobpush_scope': 'getTagsByScope',
      'change .deeplinkSelectBox':'showDeepLinks',
      'click .deepLinkSelectKeys':'showDeepLinkKeys',
      'click #android-preview': "showPreview"
    },
    showDeepLinks: function(e) {
      console.log("ideep", e);
      var tab_value = this.fetchCurrentChannel();
      var tempContainer = $('#mob_'+tab_value+'_container');
      var deepId = tempContainer.find('.deeplinkSelectBox option:selected').attr('id');
      // var that = this;
      tempContainer.find('.deepLinkBox').removeClass('hideMe').attr({
        id: deepId,
        value: this.deepLinkObject[deepId].link,
        'hidden-by': ''
      });
      tempContainer.find('.deepLinkSelectKeys').removeClass('hideMe').attr({'hidden-by': ''});
      if(_.size(this.deepLinkObject[deepId].keys) == 0){
        tempContainer.find('.deepLinkSelectKeys').css({'pointer-events': 'none'});
      }else{
        tempContainer.find('.deepLinkSelectKeys').css({'pointer-events': ''});
      }
      tempContainer.find('#deepKeyTable').addClass('hideMe').attr({'hidden-by': 'deep-link'});
      tempContainer.find('.showKeysForSavedDeepLink').addClass('hideMe');
    },
    showDeepLinkKeys: function(e) {
      console.log("ikey", e);
      var tab_value = this.fetchCurrentChannel();
      var tempContainer = $('#mob_'+tab_value+'_container');
      tempContainer.find('.deepLinkSelectKeys').addClass('hideMe').attr({'hidden-by': 'deep-link'});
      var deepId = tempContainer.find('.deeplinkSelectBox option:selected').attr('id');
      var deepTable = _.template($('#deepLinkKeyTable').html())({
        deepKeys: this.deepLinkObject[deepId].keys,
        deepLinkId: deepId
      });
      $(e.currentTarget).after(deepTable);
      tempContainer.find('#deepKeyTable').attr({'hidden-by': ''});
    },
    tagAreaClick: function(e){
       if($(e.currentTarget).hasClass('ca-mobile-push-textarea')){
          this.textAreaClick = true ; 
       }else{
          this.textAreaClick = false ; 
       } 
    },
    getTagsByScope: function(e) {
    this.mobpushScope = e.currentTarget.value;
    console.log(this.mobpushScope);
    var that = this;
    switch(that.mobpushScope){
      case 'mobilepush_loyalty':
        console.log("Loyalty Scope");
        that.render(e);
        break;
      case 'mobilepush_dvs':
        console.log("DVS Scope");
        that.render(e);
        break;
      case 'mobilepush_outbound':
        console.log("Outbound Scope");
        that.render(e);
        break;
    }
  },
    messageTextChange: function() {
      var textMessage = $('#ca-mobile-push-textarea').text();
      $('#count-characters-create-mp-tpl').text(textMessage.length + _campaign('characters'));
    },
    deleteIOSDetailContainer:function(e){
      $(".IOS-secondary-detail-block").empty();
      $(".secondary-CTA-IOS").show();
    },
    mobilePushCopyText:function(e){
      var target_input =  $(e.currentTarget).parent().next().children(); 
      if(this.currentTab === "android"){
        container = $("#mob_ios_container");
        count_container = $("#mob_android_container");
      }
      if(this.currentTab === "ios"){
        container = $("#mob_android_container");
        count_container = $("#mob_ios_container");
      }
       var get_input = container.find("#"+target_input.attr("id")).val();
       var set_attr_input = target_input.attr("id")+"-"+this.currentTab;
       target_input.attr(set_attr_input,get_input);
       target_input.val(get_input); 
        if($(target_input).get(0).tagName==='TEXTAREA'){
         var msgCount = 0;
        msgCount = get_input.length;
        count_container.find('.show-count').html( msgCount );
      }
     
    },
    showMobileImageUpload: function() {
      tab_value = this.fetchCurrentChannel();
      var container = (tab_value === 'android') ? $("#mob_android_container") : $("#mob_ios_container");
      container.find('#upload_image_'+tab_value).trigger('click');
    },
    uploadMobilePushImage: function() {
         tab_value =this.fetchCurrentChannel();
         var container = (tab_value === 'android') ? $("#mob_android_container") : $("#mob_ios_container");
         container.find("#image_upload_"+tab_value).submit();
         container.find('#image_upload_'+tab_value).val('');
      },
    activeTab : function(e,tab){
    $("#mob_android_container").hide();
    $("#mob_ios_container").hide();
    var event_id = $(e.currentTarget).children().attr('id');  
    if(_.isUndefined(event_id)){
      event_id = tab;
      this.currentTab = tab;
    }else{
      this.currentTab = event_id; 
    }
   
    if(event_id === "android"){
      $("#mob_android").addClass("active");
      $("#mob_ios").removeClass("active");
    }
    if(event_id === "ios"){
      $("#mob_android").removeClass("active");
      $("#mob_ios").addClass("active");
   
    }
    console.log("event_id",this.currentTab);
    
    if(this.currentTab === "android"){
       $("#mob_android_container").show();
    }
    if(this.currentTab === "ios"){
       $("#mob_ios_container").show();
    }
    
  },
  deleteContainer:function(e){
    var no = $(e.currentTarget).attr('ca-mobile-no');
    $("#mob_android_container").find("#secondary-CTA"+no).remove();
    $("#mob_android_container").find("#secondary-CTA"+no).empty(); 
    var size =$("#mob_android_container").find(".secondary-CTA" ).size();
    if(size <= 1)
      $("#mob_android_container").find('.ca-mobile-push-hide').show();
  },
  deleteIOSContainer:function(e){
    $(".secondary-CTA-IOS").empty();
    $(".secondary-CTA-IOS").hide();
    $(".ca-mobile-push-add-ios").show();

  },
  render: function(e){ 
    template =this.model.toJSON();

    if(typeof(this.mobpushScope) == "undefined"){
      this.mobpushScope = this.model.attributes.scope || "mobilepush_outbound";
      if(this.model.attributes.scope == 'PUSH')
        this.mobpushScope = "mobilepush_outbound";
    }

    var tagsToPass;
    var that = this;
    var tempScope;

    switch(this.mobpushScope){
      case 'mobilepush_loyalty':
        tempScope = 'mobilepush_loyalty';
        break;
      case 'mobilepush_dvs':
        tempScope = 'dvs';
        break;
      case 'mobilepush_outbound':
        tempScope = 'mobilepush';
        break;
      default:
        tempScope = 'mobilepush';
    }

    _.each(this.allTags, function(value,key){
      if(value.name.toLowerCase() === tempScope.toLowerCase()){
        tagsToPass = value;
      }
    });

    this.$el.html(this.tpl({
      tab_value :this.currentTab,
      template : template,
      tagScope: this.mobpushScope
    }));

    this.renderMobilePush(e, tagsToPass);
    this.registerImageUpload() ;
  },
  registerImageUpload: function(){
     var self = this;
    _.each(['ios','android'],function(tab_value){
      if(tab_value==="android")
        var container = $("#mob_android_container");
      if(tab_value==="ios")
        var container = $("#mob_ios_container");
      container.find("#image_upload_"+tab_value).submit(function(e) {
          $('.wait_message').show().addClass('indicator_1');
          var formObj = new FormData(this);
          var formURL = '/xaja/AjaxService/assets/upload_image.json?maxSize=3145728';
          if (window.FormData !== undefined) {
            $.ajax({
              url: formURL,
              type: 'POST',
              data: formObj,
              mimeType: 'multipart/form-data',
              contentType: false,
              cache: false,
              processData: false,
              success: function(data) {
                data = JSON.parse(data);
                console.log("image uploaded",data);
                if (!data.error) {
                   var imageInfo = JSON.parse(data.info);
                 container.find('.mobile_push_image_file img').attr({
                  'src': imageInfo.public_url
                });
                $('.uploadPic div:nth-child(1)').removeClass('hide');
                $('.uploadPic div:nth-child(2)').addClass('hide');
                  self.showMessage(_campaign("Image uploaded successfully"))
                } else {
                  self.showError(data.error, true)
                }
                $('.wait_message').hide().removeClass('indicator_1');
              }
            });
            return false;
          }
        });
   });
  },
  renderMobilePush:function(e, tagsPassed){
    // var tags = this.tags;
    var tags = tagsPassed.children;
    template =this.model.toJSON();
    console.log("modelllllll",template);
    var tmpl = _.template($("#create_mobile_push_template_tpl").html());
    var that = this;
    var eve = e;
    $.ajax({
      url: '/xaja/AjaxService/assets/getDeepLinkData.json?channel_id=2&account_id=28',
      dataType: 'json',
      success: function(deep) {
        console.log(deep, JSON.parse(deep.deeplink));
        var deepJson = JSON.parse(deep.deeplink);
        that.deepLinkObject = deepJson;

        $("#mob_android_container").html(tmpl({ tags: tags, template:  template, tab_value : "android" , template_scope : that.template_scope, deeplinks: deepJson}));
        $("#mob_ios_container").html(tmpl({ tags: tags, template:  template, tab_value :"ios" , template_scope : that.template_scope, deeplinks: deepJson}));
        if(!_.isUndefined(template.template_id)){
          if(!_.isUndefined(template.html_content.ANDROID) &&
              !_.isUndefined(template.html_content.ANDROID.expandableDetails.ctas))
              that.createSecondary(template);
          if(!_.isUndefined(template.html_content.IOS) &&
                !_.isUndefined(template.html_content.IOS.expandableDetails) && 
                  !_.isUndefined(template.html_content.IOS.expandableDetails.categoryId)){
            that.getSecondaryIOSContainer(eve,template);
            that.getSecondaryDetailIOSContainer(eve,template);
           }
        }  
      }
    });

    // var scope;
    // if($('.scopeCheck input[type="checkbox"]').is(':checked') == true)

  },
  getSecondaryDetailIOSContainer:function(e,mod){
    CtasObj = [];
    $(".secondary-CTA-IOS").hide();
    $(".IOS-secondary-detail-block").show();
    var tmpl = _.template($("#IOS_secondary_detail_tpl").html());
        
    if(!_.isUndefined(mod)){
      if(!_.isUndefined(mod.html_content.IOS) &&
          !_.isUndefined(mod.html_content.IOS.expandableDetails) &&
             !_.isUndefined(mod.html_content.IOS.expandableDetails.categoryId)){ 
        categoryId = mod.html_content.IOS.expandableDetails.categoryId;
        mobile_push_account = this.mobilePushAccounts;
        sec_cta = this.model.getSecondaryIOS(mobile_push_account);   
        console.log("sec_cta",sec_cta);
        var templateData = _.where(sec_cta.response.data, {
          id: categoryId
        });
        console.log("templateData",templateData);
        console.log("mod madhu",mod);
        var ios_name = templateData[0].name;
        var categoryId = templateData[0].id;
        var size = templateData[0].ctaTemplateDetails.length;
        for(i=0;i<size;i++){
          item = {};
          if(!_.isUndefined( templateData[0].ctaTemplateDetails[i])){
            //if(templateData[0].ctaTemplateDetails[i].toLaunchApp !== false){
              item ["item_id"] = templateData[0].ctaTemplateDetails[i].id;
              item['item_text'] = templateData[0].ctaTemplateDetails[i].buttonText;
              item['launch_app']=templateData[0].ctaTemplateDetails[i].toLaunchApp;

          }
         _.filter(mod.html_content.IOS.expandableDetails.ctas, function(model,key){
                    if(model.templateCtaId.toLowerCase().indexOf(
                          (templateData[0].ctaTemplateDetails[i].id).toLowerCase()) != -1) {
                       item['item_link']=model.actionLink;
                       item['item_type']=model.type;
                    }
           });  
          CtasObj.push(item);
        }
        console.log("getSecondaryDetailIOSContainer edit:",CtasObj, ios_name);     
      }
    }else{  
      var ios_name = $(e.currentTarget).find(".name").html();
      var categoryId = $(e.currentTarget).find("#categoryId").val();
      var size =  $(e.currentTarget).children(".ios-cta-btn").length;        
      for(i=0;i<size;i++)
      {
        item = {};
        if($(e.currentTarget).find("#button"+i+"_id").val()!==""){
            item ["item_id"] = $(e.currentTarget).find("#button"+i+"_id").val();
            item['launch_app']= $(e.currentTarget).find("#toLaunchApp"+i).val();
            item['item_text'] = $(e.currentTarget).find("#mobilepuh_button"+i).html();
        }
        CtasObj.push(item);
      }
      console.log("getSecondaryDetailIOSContainer :",CtasObj, ios_name);      
    }
    $(".IOS-secondary-detail-block").html(tmpl({ios_name : ios_name, categoryId : categoryId , CtasObj : CtasObj}));
  },
  getSecondaryIOSContainer: function(e,template){
       var tmpl = _.template($("#create_IOS_secondary_tpl").html());
       $(".add-secondary-IOS").html(tmpl());
       $(".ca-mobile-push-add-ios").hide();

        mobile_push_account = this.mobilePushAccounts;
       sec_cta = this.model.getSecondaryIOS(mobile_push_account);
        console.log("get madhu sec cta",sec_cta);
        var cta_tmpl = _.template($("#create_IOS_secondary_tpl_cta").html());
      _.each(sec_cta.response.data,function(model,key){
        console.log("cta modellll",model);
      $(".ca-mobile-push-ios-cta").append(cta_tmpl({model : model}));
      });
      if(!_.isUndefined(template)){

         $(".secondary-CTA-IOS").hide();
       }

        

      },
  openSubTag : function(e){
    $(e.currentTarget).addClass( "active" );
     
    $(e.currentTarget).parent().next('ul').toggle();  
    var inner_div = $(e.currentTarget).parent().next('ul');
    if($(inner_div).is(':visible'))
      $(e.currentTarget).parent().prev().html('<i class="icon-caret-down"></i>');
    else
       $(e.currentTarget).parent().prev().html('<i class="icon-caret-right"></i>');         
  },
  insertTag: function(e) {
      tab_value = this.fetchCurrentChannel();
      var container = (tab_value === 'android') ? $("#mob_android_container") : $("#mob_ios_container");
      var tag = $(e.currentTarget).attr('tag-data');
      if(this.textAreaClick){
        var taxt_area_val = container.find(".ca-mobile-push-textarea").val();
        set = 90;
        remain = parseInt(set - (taxt_area_val+tag).length);
        if (remain >= 0) 
          container.find(".ca-mobile-push-textarea").val(taxt_area_val+tag);
        this.setText(e,container.find(".ca-mobile-push-textarea"));
        this.updateOnKeyup(e,container.find(".ca-mobile-push-textarea"));  
      }else{
        var taxt_area_val = container.find(".ca-mobile-push-title").val();
        container.find(".ca-mobile-push-title").val(taxt_area_val+tag);
        this.setText(e,container.find(".ca-mobile-push-title"));
      }      
     },
  createSecondary : function(mod){
    var tmpl = _.template($("#secondary_tpl").html());
    var flag = true; 
      if(!_.isUndefined(mod.html_content) &&
          !_.isUndefined(mod.html_content.ANDROID) &&
          !_.isUndefined(mod.html_content.ANDROID.expandableDetails) &&
              !_.isUndefined(mod.html_content.ANDROID.expandableDetails.ctas)){
        length = mod.html_content.ANDROID.expandableDetails.ctas.length;
        console.log("length",length);
        if(length === 2)
            $("#mob_android_container").find('.ca-mobile-push-hide').hide();
        for(i=0;i<length;i++){
          $('.ca-mobile-push-show-secondary').append(tmpl({ no: i, mod:  mod,tab_value :this.currentTab }));     
        }
    }else{
      var no =$("#mob_android_container").find(".secondary-CTA").size();
      
      if(no === 0)
          cta_no = 0;
      else
        cta_no = parseInt($("#mob_android_container").find(".secondary-CTA").attr("ca-mobile-push-key"))+1;
      if(no >= 1)
        $("#mob_android_container").find('.ca-mobile-push-hide').hide();  
      if(no > 0)
        var flag = this.validateSecondary($('.ca-mobile-push-show-secondary'));
      if(flag === false){
        $("#mob_android_container").find('.ca-mobile-push-hide').show(); 
        return false;
      }
      $('.ca-mobile-push-show-secondary').append(tmpl({ no: cta_no })); 
    }  
  },
  updateOnKeyup : function(e,textarea_container){
      tab_value = this.fetchCurrentChannel();
      var container = (tab_value === 'android') ? $("#mob_android_container") : $("#mob_ios_container");
      var msgCount = 0;
      if(textarea_container)
        msgCount = textarea_container.val().length;
      else
        msgCount = $(e.currentTarget).val().length;
      container.find('.show-count').html( msgCount );

    },
    resetAttribute : function(e){
      var attribute = $(e.currentTarget).prev().val("");
      console.log(attribute);
    },
    resetContainerAttribute :function(e){
       $(e.currentTarget).parent().parent().find("input").val("");
    },
    resetPrimaryContainer:function(e){
      tab_value = this.fetchCurrentChannel();
      var container = (tab_value === 'android') ? $("#mob_android_container") : $("#mob_ios_container");
      container.find('.primary-link').removeAttr('checked');
      container.find('#ca-mobile-push-primary').val('');
      $('.deeplinkSelectBox').addClass('hideMe').attr({'hidden-by': ''});
      $('.deepLinkSelectKeys').addClass('hideMe').attr({'hidden-by': ''});
      $('.deepLinkBox').addClass('hideMe').attr({'hidden-by': ''});
      $('.deepKeyTable').addClass('hideMe').attr({'hidden-by': ''});
    },
    setTitleLink : function(e){
      tab_value = this.fetchCurrentChannel();
      var link = $(e.currentTarget).parent().prev().children().find(':checked').val();
      $(e.currentTarget).attr($(e.currentTarget).attr("id")+"-"+link+"-"+tab_value,$(e.currentTarget).val());
    },
    showTitleLink : function(e){
      tab_value = this.fetchCurrentChannel();
      tempContainer = $('#mob_'+tab_value+'_container');

      if(_.isEqual("deep-link",e.currentTarget.value)){
        $(e.currentTarget).parent().parent().next().children('input#ca-mobile-push-primary').addClass('hideMe');
        tempContainer.find('.deeplinkSelectBox').removeClass('hideMe').attr({'hidden-by': ''});
        if(_.isEqual("external-link",tempContainer.find('.deepLinkSelectKeys').attr('hidden-by'))){
          tempContainer.find('.deepLinkSelectKeys').removeClass('hideMe').attr({'hidden-by': ''});
        }
        if(_.isEqual("external-link",tempContainer.find('.deepLinkBox').attr('hidden-by'))){
          tempContainer.find('.deepLinkBox').removeClass('hideMe').attr({'hidden-by': ''});
        }
        if(_.isEqual("external-link",tempContainer.find('#deepKeyTable').attr('hidden-by'))){
          tempContainer.find('#deepKeyTable').removeClass('hideMe').attr({'hidden-by': ''});
        }
      }else if(_.isEqual("external-link",e.currentTarget.value)){
        tempContainer.find('.showKeysForSavedDeepLink').addClass('hideMe');
        tempContainer.find('.deeplinkSelectBox').addClass('hideMe').attr({'hidden-by': 'external-link'});
        if(!_.isEqual("deep-link",tempContainer.find('.deepLinkSelectKeys').attr('hidden-by'))){
          tempContainer.find('.deepLinkSelectKeys').addClass('hideMe').attr({'hidden-by': 'external-link'});
        }
        if(!_.isEqual("deep-link",tempContainer.find('.deepLinkBox').attr('hidden-by'))){
          tempContainer.find('.deepLinkBox').addClass('hideMe').attr({'hidden-by': 'external-link'});
        }
        if(!_.isEqual("deep-link",tempContainer.find('#deepKeyTable').attr('hidden-by'))){
          tempContainer.find('#deepKeyTable').addClass('hideMe').attr({'hidden-by': 'external-link'});
        }
        target_textbox = $(e.currentTarget).parent().parent().next().children('input#ca-mobile-push-primary');
        $(target_textbox).removeClass('hideMe');
        id = target_textbox.attr("id");
        link_value = target_textbox.attr(id+"-"+$(e.currentTarget).val()+"-"+tab_value);
        console.log(link_value);
        target_textbox.val(link_value);
      }else{}

    },
    setText : function(e,target_val){
      tab_value = this.fetchCurrentChannel();
      if(tab_value==="android")
        var container = $("#mob_android_container");
      if(tab_value==="ios")
        var container = $("#mob_ios_container");
      if(target_val)
         target_val.attr(target_val.attr("id")+"-"+tab_value,target_val.val());
      else
      $(e.currentTarget).attr($(e.currentTarget).attr("id")+"-"+tab_value,$(e.currentTarget).val());
    },
    showPreview : function(e){
      var targetElem = $(e.currentTarget);
      var isChannelBtn = targetElem.hasClass('preview-mobile-push');
      
      if (isChannelBtn) {
        var channel = (e.currentTarget.id == 'ios-preview') ? 'ios' : 'android';
        this.previewMobilePushNew(e,channel);
      } 
      else {
        var currentChannel = this.fetchCurrentChannel();
        currentChannel === 'android' ? $('#android-preview').click() : $('#ios-preview').click();
      }
    },
    previewMobilePushNew: function( e, channel ) {
      var currentChannel = channel ? channel : this.fetchCurrentChannel();
      if(currentChannel === 'android'){
        var container = $('#mob_android_container');
        this.renderAndroidContainer(container);
      } else {
        var container = $('#mob_ios_container');
        this.renderIosContainer( container );
      }
    },

    fetchCurrentChannel: function() {
      var channel = $('.mobile-push-tabs').find('.active').find('a').attr('id');
      return channel.toLowerCase();
    },

    renderIosContainer: function( container ) {
      var template = _.template($("#ios-notif-preview").html());
      var ios_data = this.getIosTemplateData(container);
      var html = template({ios_data:ios_data});

      this.$('#edit_template_preview_modal').modal('show');
      this.$('.mobile-preview-icon-android').addClass('hide');
      this.$('.mobile-preview-icon-ios').removeClass('hide');
      this.$('.preview_container_ios').html(html);
    },

    renderAndroidContainer: function( container ) {
      var template = _.template($("#android-notif-preview").html());
      var android_data = this.getAndroidTemplateData(container);
      var html = template({android_data:android_data});

      this.$('#edit_template_preview_modal').modal('show');
      this.$('.mobile-preview-icon-ios').addClass('hide');
      this.$('.mobile-preview-icon-android').removeClass('hide');
      this.$('.preview_container').html(html);
    },

    getIosTemplateData: function( container ) {
      var ios_data = {};
      ios_data.title = container.find("#ca-mobile-push-title").val();
      ios_data.content = container.find(".ca-mobile-push-textarea").val();
      // ios_data.notif_img = "http://www.menucool.com/slider/jsImgSlider/images/image-slider-2.jpg";
      ios_data.notif_img = container.find(".mobile_push_image_file").find("img").attr("src");
      ios_data.cta_sec = this.fetchSecondaryLabelsIos();

      return ios_data;
    },

    getAndroidTemplateData: function( container ) {
      var android_data = {};
      android_data.title = container.find("#ca-mobile-push-title").val();
      android_data.content = container.find(".ca-mobile-push-textarea").val();
      // android_data.notif_img = "http://www.w3schools.com/css/img_fjords.jpg";
      android_data.notif_img = container.find(".mobile_push_image_file").find("img").attr("src");
      android_data.cta_sec = this.fetchSecondaryLabelsAndroid(container);
      
      return android_data;
    },

    fetchSecondaryLabelsIos: function() {
      var sec_label = [];
      var sec_label_elements = $('div.ca-mobile-push-secondary-label');
      for(var i = 0; i < sec_label_elements.length ; i++) {
        sec_label[i] = $(sec_label_elements[i]).text();
      }

      return sec_label;
    },

    fetchSecondaryLabelsAndroid: function( container ) {
      var sec_label = []
      var sec_label_elements = container.find(".ca-mobile-push-secondary-label");
      for(var i = 0; i < sec_label_elements.length ; i++) {
        sec_label[i] = $(sec_label_elements[i]).val();
      }

      return sec_label;
    },

    renderAllIosContainer: function( container ) {

    },
    backToView : function(e){
      if (window.confirm(_campaign("You will lose all progress made, Do you want to continue"))) {
      //this.model.set('html_content','');
      app.CreativeViewInstance.showMobilePushTemplates();
      //this.$el.hide();
      //this.$el.siblings('.ca_top_view_container').show();
    }
    },
    validateFormDetails : function(e){
      var flag =true;
      tab_value = this.fetchCurrentChannel();
      var container = (tab_value == 'android') ? $("#mob_android_container") : $("#mob_ios_container");
      var temp_name = $("#ca-mobile-push-name");
      title = container.find("#ca-mobile-push-title");
      msg = container.find("#ca-mobile-push-textarea");
      scope = container.find("#mobilepush_template_scope");
      img = container.find('.mobile_push_image_file').find("img").attr('src');
       
      if(scope == "MOBILEPUSH_IMAGE"){
        var img = container.find('.mobile_push_image_file').find("img").attr('src');
      }
      console.log("img :",img);
      if(temp_name.val().length == 0){
          this.showError(_campaign("please fill template name"));
          temp_name.addClass("red-error-border");
          flag = false;
      }
      else if(title.val().length == 0){
          this.showError(_campaign("please fill title"));
          title.addClass("red-error-border");
          flag = false;
      }
      else if(msg.val().length == 0){
          this.showError(_campaign("please fill message"));
          msg.addClass("red-error-border");
          flag = false;
      }
      else if(!_.isUndefined(img) && _.isEmpty(img)){
          this.showError(_campaign("please upload image."));
          flag = false;
      }
      else if(tab_value=="android" && !this.validateSecondary(container)){
          this.showError(_campaign("please fill Secondary CTA"));
          flag = false;
      }
              
      else if(tab_value=="ios" && !this.validateSecondaryIOS(container)){
          this.showError(_campaign("please fill IOS Secondary CTA"));
          flag = false;
      }
      else if(!this.validateCTA(container)['modelFlag']){
          this.showError(_campaign(this.validateCTA(container)['msg']));
          flag = false;
      }

       if(flag===false)
            return false;

    },
    saveNewTemplate : function(e){
      var flag = this.validateFormDetails();
      if(flag===false)
          return;
      var android_container = $("#mob_android_container");
      var ios_container = $("#mob_ios_container");

         if(android_container.find("#ca-mobile-push-title").val()==""){
           this.$('.mobilepush_confirm_save_ios').modal('show');
         return false;
        }
        if(ios_container.find("#ca-mobile-push-title").val()==""){
          this.$('.mobilepush_confirm_save_android').modal('show');
         return false;
        }
        this.saveTemplate();
    
    },
    saveTemplate:function(){
      var that =this;
      data = this.getFormDetails();
      console.log("getFormDetails:",data);
      if(data == false){
        that.showError(_campaign("please fill message"));
        return false;
      }
      else{
        that.showEditLoader(true);
        this.model.saveTemplate(data).done(function() {
        that.showEditLoader(false);
        });
      }
    },
     getFormDetails: function(e){
      var that = this;
        var formDetails = {};
        var temp_detail = {};
        var no = $( ".secondary-CTA" ).size();
        var mob_type_ios=$("#mob_android_container").find("#ca-mobile-push-title").val();
        var mob_type = [];
        if($("#mob_android_container").find("#ca-mobile-push-title").val()!=""){
          mob_type.push("ANDROID");
        }
        if($("#mob_ios_container").find("#ca-mobile-push-title").val()!=""){
          mob_type.push("IOS");
        }
        
        console.log("check type",mob_type);
          formDetails['template_id'] = $('#ca-mobile-push-id').val();
          formDetails['accountId'] = this.mobilePushAccounts;
          formDetails['editor_name'] =  'text';
          formDetails['scope'] = this.scope;
          formDetails['mobpushScope'] = this.mobpushScope;
          formDetails['templateName'] =  $('#ca-mobile-push-name').val();
          formDetails['content'] = temp_detail;
          formDetails['template_scope'] = this.template_scope;
          _.each(mob_type, function(value, key) {
           jsonCtasObj = [];
           nonActionableCta = [];
           var cta_type_value = "";
            if(value.toUpperCase()=="ANDROID"){
                 var mob_container = $("#mob_android_container");
                 var tab_value = "android";
                 var expandable_container = mob_container.find(".secondary-CTA");
              }
              if(value.toUpperCase()=="IOS"){
                 var mob_container = $("#mob_ios_container");
                 var tab_value = "ios";
                 var expandable_container = $(".ca-mobile-push-ios-container");

              }
              var cta_type = mob_container.find("input[name=primary-link-"+tab_value+"]:checked").val();
              var cta_label,cta_name;
              if(cta_type == "deep-link"){
                cta_type_value = "DEEP_LINK";
                cta_name = mob_container.find('.deeplinkSelectBox option:selected').val();
                var tempLink = mob_container.find('.deepLinkBox').val();
                cta_label = tempLink + '?';
                var table = mob_container.find('#deepKeyTable');
                if(table.length > 0){
                  var tableCheckBoxKeys = table.find('input[type="checkbox"]');
                  var tableTextKeys = table.find('input[type="text"]');
                  for(i=0; i<tableCheckBoxKeys.length; i++){
                      if(tableCheckBoxKeys[i].checked){
                          cta_label += tableCheckBoxKeys[i].value + '=' + tableTextKeys[i].value + '&';
                      }
                  }
                  cta_label = cta_label.substr(0,cta_label.length-1);
                }
              }
              if(cta_type == "external-link"){
                cta_type_value = "EXTERNAL_URL";
                cta_label = mob_container.find("#ca-mobile-push-primary").attr("ca-mobile-push-primary-"+cta_type+"-"+tab_value); 
                cta_name = cta_type;
              }
              primaryCTA = {}
              if(!_.isEmpty(cta_label)){
                primaryCTA['type'] = cta_type_value;
                primaryCTA['actionLink'] = cta_label;
                primaryCTA['deepLinkName'] = cta_name;
              }
              var sec_label =mob_container.find(".ca-mobile-push-secondary-label");
              var sec_link = mob_container.find(".ca-mobile-push-secondary");
              var img = mob_container.find('.mobile_push_image_file').find("img").attr('src');
       
              if(!_.isUndefined(img)){
                  var mobile_style = "BIG_PICTURE";
              }
              else
                var mobile_style ="BIG_TEXT";
              expandableDetails = {};
              _.each(expandable_container, function(expandable_container){
                var i = $(expandable_container).attr("ca-mobile-push-key");
                var sec_cta_type_value = "";
                if(tab_value=="ios"){
                  sec_cta_type =  mob_container.find("input[name="+tab_value+"-secondary-link"+i+"]:checked").val();
                  }
                if(tab_value=="android"){
                  sec_cta_type =  mob_container.find("input[name=secondary-link"+i+"]:checked").val();
                }
                if(sec_cta_type == "deep-link")
                     sec_cta_type_value = "DEEP_LINK";
                    if(sec_cta_type == "external-link")
                    sec_cta_type_value = "EXTERNAL_URL"; 
                templateCtaId =  mob_container.find("#ca-mobile-push-ios-"+i).val();
                templateLaunchApp =  mob_container.find("#ca-mobile-push-ios-launchapp-"+i).val();
                nonIOSCta ={};  
                if(templateLaunchApp==="false"){
                  nonIOSCta['actionText'] = mob_container.find("#ca-mobile-push-label"+i).attr("ca-mobile-push-label"+i+"-"+tab_value);
                  nonIOSCta["templateCtaId"] =templateCtaId;
                  nonActionableCta.push(nonIOSCta); 
                }else{
                  item = {};
                item ["actionText"] =  mob_container.find("#ca-mobile-push-label"+i).attr("ca-mobile-push-label"+i+"-"+tab_value);
                item ["type"] = sec_cta_type_value;
                item["actionLink"] =  mob_container.find("#ca-mobile-push-secondary"+i).attr("ca-mobile-push-secondary"+i+"-"+sec_cta_type+"-"+tab_value);
                if(!_.isUndefined(templateCtaId))
                item["templateCtaId"] =templateCtaId;
                jsonCtasObj.push(item);
                }
                
               });              
              expandableDetails['style'] = mobile_style;
              expandableDetails['message'] = mob_container.find("#ca-mobile-push-textarea").attr('ca-mobile-push-textarea-'+tab_value);
               if(!_.isEmpty(jsonCtasObj))
              expandableDetails['ctas'] = jsonCtasObj;
              categoryId = mob_container.find("#ios_category_id").val();
              if(tab_value=="ios")
                  expandableDetails['nonActionableCta'] = nonActionableCta;
              if(!_.isUndefined(categoryId))
                 expandableDetails['categoryId'] = categoryId;
              if(!_.isUndefined(img))
              expandableDetails['image'] = img ; 
            
              
              temp_detail[ value ] = {
                 'title' : mob_container.find("#ca-mobile-push-title").attr('ca-mobile-push-title-'+tab_value),
                 'message': mob_container.find("#ca-mobile-push-textarea").attr('ca-mobile-push-textarea-'+tab_value)                     
              }
              if(!_.isEmpty(expandableDetails))
                 temp_detail[ value ]['expandableDetails']=expandableDetails;
              if(!_.isEmpty(primaryCTA))
                 temp_detail[ value ]['cta']=primaryCTA;
            });
      console.log(formDetails);    
      return formDetails;
          
      },
      createIOS : function(e){
          this.activeTab(e,"ios");
      },
      createAndroid: function(e){
          this.activeTab(e,"android");
      },
  validateSecondary :function(container){
        var modelFlag = true;
        var sec_cta=container.find(".secondary-CTA");
        _.each(sec_cta, function(t){
          var i =$(t).attr("ca-mobile-push-key");
          var sec_text = container.find("#ca-mobile-push-label"+i);
          var sec_link = container.find("#ca-mobile-push-secondary"+i);
          var sec_btn = container.find('input[name=secondary-link'+i+']');
          if(_.isEmpty(sec_text.val()) || _.isEmpty(sec_link.val())){
                sec_text.addClass("red-error-border");
                sec_link.addClass("red-error-border");
                modelFlag = false;           
          }else if(sec_btn.is(':checked') && sec_link.val().length == 0){
                sec_link.addClass("red-error-border");
                modelFlag = false;     
            
          }else if(!sec_btn.is(':checked') && sec_link.val().length !== 0){
                sec_link.addClass("red-error-border");
                modelFlag = false;      
          }else{
          sec_text.removeClass("red-error-border");
          sec_link.removeClass("red-error-border");
          }
        });
        
        return modelFlag;
      
   },
   //TODO validation for ios link still pending.
   validateSecondaryIOS:function(container){
     var modelFlag = true;
     var sec_label =$(".ca-mobile-push-ios-container:visible");
     var key = sec_label.attr("ca-mobile-push-key");
         for(var i = key; i<=sec_label.length; i++){
            var sec_btn = container.find('input[name=ios-secondary-link'+i+']');
            var sec_link = container.find("#ca-mobile-push-secondary"+i);
            if($( sec_link ).parent().parent().is(':hidden') || sec_link.length ==0){
             continue;
          }else{
            if( _.isEmpty(sec_link.val())){
                  sec_link.addClass("red-error-border");
                  modelFlag = false;      
            }else if(sec_btn.is(':checked') && sec_link.val().length == 0){
              sec_link.addClass("red-error-border");
              modelFlag = false;
            }else if(!sec_btn.is(':checked') && sec_link.val().length !== 0){
              sec_link.addClass("red-error-border");
              modelFlag = false;   
            }else{
            sec_link.removeClass("red-error-border");
            }
        }
        
    }
        return modelFlag;
   },
   validateCTA :function(container){
     var item = {};
     item['modelFlag'] = true;
     var primary_btn=container.find(".primary-link");
     // var primary_link = container.find("#ca-mobile-push-primary");
     
     for(i=0; i<primary_btn.length; i++){
        if(primary_btn[i].checked){
          switch(primary_btn[i].value){
            case 'deep-link':
                
                  if(_.isUndefined(container.find('.deeplinkSelectBox option:selected').attr('id'))){
                          // container.find('.deeplinkSelectBox').addClass("red-error-border");
                          item['msg'] = "Please choose a valid Deep Link";
                          item['modelFlag'] = false;
                  }else{
                    var table = container.find('#deepKeyTable');
                    if(table.length !== 0){
                      switch(table.attr('hidden-by')) {
                          case 'deep-link':
                            break;
                          case '':
                          case 'external-link':
                                if(!table.hasClass('hideMe')){
                                  var tableCheckBoxContainer = table.find('input[type="checkbox"]');
                                  var tableTextBoxContainer = table.find('input[type="text"]');
                                  for(j=0; j<tableCheckBoxContainer.length; j++){
                                      if(tableCheckBoxContainer[j].checked){
                                          if( _.isEmpty(tableTextBoxContainer[j].value) ){
                                              item['msg'] = "Please fill value for the selected key(Deep Link)";
                                              item['modelFlag'] = false;
                                              break;
                                          }
                                      }else{
                                          if( !_.isEmpty(tableTextBoxContainer[j].value) ){
                                              item['msg'] = "Please select the checkbox for the key";
                                              item['modelFlag'] = false;
                                              break;
                                          }
                                      }
                                  }
                                }
                      }
                    }
                  }
                
              break;
            case 'external-link':
                  var primary_link = container.find("#ca-mobile-push-primary");
                  if(primary_btn[i].checked && primary_link.val().length == 0){
                          primary_link.addClass("red-error-border");
                          item['msg'] = "Please fill primary CTA(External Link)";
                          item['modelFlag'] = false;
                  }
                  else if(!primary_btn[i].checked && primary_link.val().length !== 0){
                          item['msg'] = "Please select primary CTA(External Link)";
                          item['modelFlag'] = false;   
                  }else{
                    primary_link.removeClass("red-error-border");
                  }
              break;
          }
        }
     }
     // var sec_link ;
     // if(primary_btn.is(':checked') && primary_link.val().length == 0){
     //        primary_link.addClass("red-error-border");
     //        item['msg'] = "please fill primary CTA";
     //        item['modelFlag'] = false;   
     // }
     // else if(!primary_btn.is(':checked') && primary_link.val().length !== 0){
     //        item['msg'] = "please select primary CTA";
     //        item['modelFlag'] = false;   
     // }
     // else
     //  primary_link.removeClass("red-error-border");


     return item;

   }  
});

var MobileTemplateModel = ca_mobilepush.MobileTemplateModel = Backbone.Model.extend({
  rooturl: '/xaja/AjaxService/assets/get_mobile_push_content_template.json',
  initialize:function(){
    console.log("model loadingg");
  },
  defaults: function() {
    return {
      html_content:{
      }
      }
    },
  getSecondaryIOS:function(mobile_push_account){

     var url = 
      $.ajax({
        url: "/xaja/AjaxService/assets/get_secondary_ios_cta.json?mobile_push_account="+mobile_push_account,
        dataType: 'json',
        async: false,
        success: function(data) {
           secondary_cta = data.secondary_cta; 
        }
      });
      console.log("secondary_cta",secondary_cta);
      return secondary_cta;
   

  }
  ,
  showError: function(msg) {
    $('.flash_message').show().addClass('redError').html(msg);
    setTimeout(function() {
      $('.flash_message').removeClass('redError').fadeOut('fast');
    }, 5000);
    },
    showMessage: function(msg) {
    $('.flash_message').removeClass('redError').show().html(msg);
    setTimeout(function() {
      $('.flash_message').fadeOut('fast');
    }, 5000);
    },
  //toggles favourite in the model
  toggleFavourite: function() {
    this.set('is_favourite', !this.get('is_favourite'));
  },
  //toggels in the model as well as saves
  toggleFavouriteAndSave: function() {
    this.set('is_favourite', !this.get('is_favourite'));
    var url = "/xaja/AjaxService/assets/set_favourite_template.json";
    var data = {
      template_id: this.get('template_id'),
      is_favourite: this.get('is_favourite') ? 1 : 0,
      account_id: $('#wechat-accounts').val()
    };
    $.post(url, data, function(resp) {
      if (resp.success) ca_mobilepush.helper.showFlash(resp.success);
      else ca_mobilepush.helper.showFlash(resp.error, true);
    }, 'json');
  },
  saveTemplate: function(data) {
    var that = this;
    var status = $.Deferred();
     var url = "/xaja/AjaxService/assets/save_mobile_push_template.json";
    status = $.post(url, data, function(resp) {
      if (resp.success) {
        that.showMessage(resp.success);
        app.CreativeViewInstance.showMobilePushTemplates();
      } else {
        that.showError(resp.error);
      }
    }, 'json');
    return status;
  },
  deleteTemplate: function() {
    if (this.get('template_id') && this.get('template_id') != "-1") {
      var url = "/xaja/AjaxService/assets/delete_html_template.json";
      var data = {
        template_id: this.get('template_id'),
        template_type: 'mobile_push',
        account_id: $('#mobile-push-accounts').val()
      };
      var self = this;
      $.post(url, data, function(resp) {
        if (resp.success) {
          ca_mobilepush.helper.showFlash(resp.success);
          self.destroy();
        } else {
          ca_mobilepush.helper.showFlash(resp.error, true);
        }
      }, 'json');
    }
    this.destroy();
  },
  renderByAccount:function(account_id){
    console.log("model account id",account_id);

  }
});

// mobile push get collection

ca_mobilepush.MobilePushTemplateCollection = Backbone.Collection.extend({
  model: MobileTemplateModel,
  ajax_url: '/xaja/AjaxService/assets/get_all_mobile_push_templates.json',
  scope: 'MOBILEPUSH_TEMPLATE',
  initialize:function(){
    console.log("call comes to here madhu");
  },
  url: function() {
    return this.ajax_url;
  },
  parse: function(resp) {
    console.log('asasasasas',resp);
    return resp.templates;
  }
});

// mobile push model


//using a new class for height of the mobile push template
//check css
ca_mobilepush.MobilePushTemplateView = Backbone.View.extend({
  tagName: 'div',
  className: 'ca-wechat-template-view',
  edit_options: {
    edit: {
      className: 'edit_template',
      label: _campaign('Edit')
    },
    duplicate: {
      className: 'duplicate_template',
      label: _campaign('Create Duplicate')
    },
    delete_t: {
      className: 'delete_template',
      label: _campaign('Delete')
    }/*,
    preview: {
      className: 'preview_template',
      label: _campaign('preview')
    }*/

    },
    events: {
      'click .delete_template': 'confirmDeleteTemplate',
      'click .confirm_delete': 'deleteTemplate',
      'click #edit_mobile_push_template': 'editMobilePushTemplate',
      'click .favorite': 'toggleFavourite',
      // 'click .ca_preview_mobile ': 'showAllPreview',
      'click #forandroid': 'showAllPreview',
      'click #forios': 'showAllPreview',
      // 'click #android-all-preview': 'previewAllAndroid',
      // 'click .ca_preview_holder_mobile': 'showAllPreview',      
      'click #android-all-preview': 'showAllPreview',
      // 'click #ios-all-preview': 'previewAllIos'
      'click #ios-all-preview': 'showAllPreview'
    },

    templateMessage: _.template($('#mobile_push_template_tpl').html()),
    initialize: function() {
      this.listenTo(this.model, 'destroy', this.remove);
      this.listenTo(this.model, 'change', this.render);
    },
    // showAllPreview:function(e){
    //   var container = "android-all-preview";
    //   this.previewAllMobilePushNew(e,container);
    // },
    showAllPreview:function(e){
      this.disableUndefinedChannels();
      var container = e.currentTarget.id;
      switch(container){
        case 'android-all-preview':
        case 'forandroid':
          this.renderAndroidAllPreview(e);
          break;
        case 'ios-all-preview':
        case 'forios':
          this.renderIosAllPreview(e);
          break;
        default:
          console.log("Container is empty");
      }
    },
    disableUndefinedChannels: function(preview_model){
      preview_model = this.model.toJSON();
      if(_.isUndefined(preview_model.html_content.IOS)){
        this.$('#ios-all-preview').css({'pointer-events':'none','cursor':'default'});
        // DIABLED TRUE DOES NOT WORK ON ANCHOR TAGS
        // this.$('#ios-all-preview').prop('disabled', true);
      }
      if(_.isUndefined(preview_model.html_content.ANDROID)){
        this.$('#android-all-preview').css({'pointer-events':'none','cursor':'default'});
      }
    },

    // previewAllAndroid: function( e ) {
    //   this.previewAllMobilePushNew(e, 'android-all-preview');
    // },

    // previewAllIos: function( e ) {
    //   this.previewAllMobilePushNew(e, 'ios-all-preview');
    // },

    // previewAllMobilePushNew:function( e,container ){
    //   preview_model = this.model.toJSON();
    //   preview_mob = container ? container : $(e.currentTarget).attr("id");
    //   if(preview_mob == "android-all-preview") {
    //     return this.renderAndroidAllPreview(e,preview_model);
    //   } else {
    //     return this.renderIosAllPreview(e,preview_model);
    //   }
    // },

    // renderIosAllPreview: function( e,preview_model ) {
    renderIosAllPreview: function(e) {
      preview_model = this.model.toJSON();
      console.log(preview_model);
      if(!_.isUndefined(preview_model.html_content.IOS)){
        this.$('#ios-all-preview').addClass('sel');
        this.$('#android-all-preview').removeClass('sel');
        
        var template = _.template($("#ios-notif-preview").html());
        var ios_data = this.getIosAllTemplateData(preview_model);
        var html = template({ios_data:ios_data});
        
        $(".mobile-preview-icon-ios").show();
        $(".mobile-preview-icon-android").hide();
        
        this.$('#edit_template_preview_modal').modal('show');
        this.$('.preview_container_ios').html(html);
      }else{
        $(e.currentTarget).prop('disabled', true);
        return false;
      }
    },

    // renderAndroidAllPreview: function( e,preview_model ) {
    renderAndroidAllPreview: function(e) {
      preview_model = this.model.toJSON();
      if(!_.isUndefined(preview_model.html_content.ANDROID)){
        this.$('#android-all-preview').addClass('sel');
        this.$('#ios-all-preview').removeClass('sel');
        
        var template = _.template($("#android-notif-preview").html());
        var android_data = this.getAndroidAllTemplateData(preview_model);
        var html = template({android_data:android_data});
        
        $(".mobile-preview-icon-android").show();
        $(".mobile-preview-icon-ios").hide();
        
        this.$('#edit_template_preview_modal').modal('show');
        this.$('.preview_container').html(html);
      }else{
       $(e.currentTarget).prop('disabled', true);
        return false;
      }
    },

    getIosAllTemplateData: function( preview_model ) {
      var ios_data = {};
      ios_data.title = preview_model.html_content.IOS.title;
      ios_data.content = preview_model.html_content.IOS.message;
      // ios_data.img = preview_model.html_content.IOS.expandableDetails.img;
      if( !_.isUndefined(preview_model.html_content.IOS.expandableDetails) &&
          ( !_.isUndefined(preview_model.html_content.IOS.expandableDetails.image) || 
             !_.isUndefined(preview_model.html_content.IOS.expandableDetails.ctas))) {
        ios_data.notif_img = preview_model.html_content.IOS.expandableDetails.image;
        ios_data.cta_sec = this.fetchSecondaryLabels(preview_model, 'IOS');
      } else {
        ios_data.notif_img = undefined;
        ios_data.cta_sec = [];
      }

      return ios_data;
    },

    getAndroidAllTemplateData: function( preview_model ) {
      var android_data = {};
      android_data.title = preview_model.html_content.ANDROID.title;
      android_data.content = preview_model.html_content.ANDROID.message;
      // android_data.img = preview_model.html_content.ANDROID.expandableDetails.img;
      if( !_.isUndefined(preview_model.html_content.ANDROID.expandableDetails) &&
          ( !_.isUndefined(preview_model.html_content.ANDROID.expandableDetails.image) || 
            ( !_.isUndefined(preview_model.html_content.ANDROID.expandableDetails.ctas)))){
        android_data.notif_img = preview_model.html_content.ANDROID.expandableDetails.image;
        android_data.cta_sec = this.fetchSecondaryLabels(preview_model, 'ANDROID');
      } else{
        android_data.notif_img = undefined;
        android_data.cta_sec = [];
      } 
             

      return android_data;
    },

    fetchSecondaryLabels: function(preview_model, channel) {
      var sec_label = []
      var sec_label_elements = preview_model.html_content[channel].expandableDetails.ctas;
      if (sec_label_elements) {
        for(var i = 0; i < sec_label_elements.length; i++) {
          sec_label.push(sec_label_elements[i].actionText);
        }
      }

      var non_actionable_ctas = preview_model.html_content[channel].expandableDetails.nonActionableCta;
      if (non_actionable_ctas) {
        for(var i = 0; i < non_actionable_ctas.length; i++) {
          sec_label.push(non_actionable_ctas[i].actionText);
        }
      }
      return sec_label;
    },

    previewAllMobilePush:function(e,container){
      preview_model = this.model.toJSON();
      console.log("previewAllMobilePush madhu",preview_model);
      if(container)
          preview_mob = container;
      else
          preview_mob= $(e.currentTarget).attr("id");
      if(preview_mob == "android-all-preview"){
        if(!_.isUndefined(preview_model.html_content.ANDROID)){
          $('#android-all-preview').addClass('sel');
          $('#ios-all-preview').removeClass('sel');
          var title = preview_model.html_content.ANDROID.title;
          var content = preview_model.html_content.ANDROID.message; 
          
          if(!_.isUndefined(preview_model.html_content.ANDROID.expandableDetails)){
             var img = preview_model.html_content.ANDROID.expandableDetails.img;
             var sec_label = preview_model.html_content.ANDROID.expandableDetails.ctas;
          }
          console.log(content);
          console.log("sec_label", sec_label);
          $(".mobile-preview-icon-android").show();
          $(".mobile-preview-icon-ios").hide();
        }else{
         $(e.currentTarget).prop('disabled', true);
          return false;
        }
      }

      if(preview_mob=="ios-all-preview"){
        if(!_.isUndefined(preview_model.html_content.IOS)){
          $('#android-all-preview').removeClass('sel');
          $('#ios-all-preview').addClass('sel');
          var title = preview_model.html_content.IOS.title;
          var content = preview_model.html_content.IOS.message; 
          var sec_label = preview_model.html_content.IOS.expandableDetails.ctas;
          if(!_.isUndefined(preview_model.html_content.IOS.expandableDetails)){
            var img = preview_model.html_content.IOS.expandableDetails.img;
            var sec_label = preview_model.html_content.IOS.expandableDetails.ctas;
          }
          $(".mobile-preview-icon-android").hide();
          $(".mobile-preview-icon-ios").show();
        }else{
          $(e.currentTarget).prop('disabled', true);
          return false;
        }
        $(".mobile-preview-icon-android").hide();
         $(".mobile-preview-icon-ios").show();
      }else{
        $(e.currentTarget).prop('disabled', true);
         return false;
      }

      this.$('#edit_template_preview_modal').modal('show');
      var text= "<div>"+title+"</div><div>"+content+"</div>"+"<div style='clear:both'></div>";
      if(img)
        text +="<div> <img src ="+img+" alt=''/></div>";
      text +="<div class='secondary-cta'>";
      if(!_.isUndefined(sec_label)){
        for(var i = 0; i<sec_label.length; i++)
        {
          var cta = sec_label[i].actionText;
          console.log(cta);
          text +="<div class='secondary-cta-span mobile-preview-type'>"+cta+"</div>";
          console.log(text);
        }
      }
      text +="</div>";
      this.$('.preview_container').html(text); 
    },
    render: function() {
       this.$el.addClass('ca-img-template-view');
        var table;
        try {
          var json = JSON.parse(this.model.toJSON().html_content);
          table = getTableFromJSON(json);
        } catch (e) {
          table = _campaign("INVALID JSON FORMAT");
        }
        var str = this.model.toJSON().html_content;
         console.log("str madhu",str);
        this.$el.html(this.templateMessage({
          edit_options: this.edit_options,
          model: this.model.toJSON()
        }));
    console.log("hello model madhu",this.model);
    this.$el.contents('div').find('.ca_preview_holder').html(str);
    return this;
  },
  toggleFavourite: function(e) {
    var that = this;
   
    var url = "/xaja/AjaxService/assets/set_favourite_template.json";
    var data = {
      template_id: this.model.get('template_id'),
      is_favourite: this.model.get('is_favourite') ? 0 : 1,
      account_id: $('#mobile-push-accounts').val()
    };
    $.post(url, data, function(resp) {
      if (resp.success) {
        that.model.set('is_favourite', !that.model.get('is_favourite'));
        ca_mobilepush.helper.showFlash(resp.success);
      } else {
        ca_mobilepush.helper.showFlash(resp.error, true);
      }
    }, 'json');
  },
  confirmDeleteTemplate: function() {
    this.$('.confirm_delete_modal').modal('show');
  },
  editMobilePushTemplate: function() {
    tags = ca_mobilepush.mobilePushTags();
    console.log(tags);

    template_scope = $('#template_scope').val();

      $('.ca_top_create_new_container').hide();
      $('.ca_top_view_container').hide();
      $('.ca_top_edit_container').show();
      editTemplateViewInstance = new CreativeAssetsMobilePushTemplateView({
        model: this.model,
        el: $('.ca_top_edit_container'),
        scope: 'PUSH',
        allTags:tags,
        template_scope : template_scope,
        mobilePushAccounts : $("#mobile-push-accounts option:selected").val(),
      });
      editTemplateViewInstance.render();
  },
  deleteTemplate: function() {
    this.model.deleteTemplate();
  }
});
