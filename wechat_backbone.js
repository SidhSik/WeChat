$.ajaxSetup({
  cache: false
});
var ca_wechat = new CreativeAssets();
var WeChatTemplateModel = ca_wechat.WeChatTemplateModel = Backbone.Model.extend({
  url: '/xaja/AjaxService/assets/get_wechat_content_template.json',
  defaults: function() {
    return {
      template_id: -1,
      template_type: 'wechat',
      scope: 'WECHAT',
      is_favourite: false,
      file_service_params: {
        TemplateId: "",
        OpenId: "{{wechat_open_id}}",
        Title: "",
        BrandId: "{{wechat_brand_id}}",
        Url: "{{wechat_service_acc_url}}",
        TopColor: "#000000",
        Data: {},
        content: ""
      }
    };
  },
  setHtml: function() {
    var status = $.Deferred();
    var self = this;
    var old_html = this.get('html_content');
    if (old_html) {
      status.resolve();
    } else {
      var url = '/xaja/AjaxService/assets/get_wechat_content.json?template_id=' + this.get('template_id');
      $.getJSON(url, function(data) {
        if (data.html_content) {
          self.set('html_content', data.html_content);
        }
        status.resolve();
      })
    }
    return status;
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
      if (resp.success) ca_wechat.helper.showFlash(resp.success);
      else ca_wechat.helper.showFlash(resp.error, true);
    }, 'json');
  },
  mapCapTags: function(wechatTag, capTag) {
    if (wechatTag == 'url') {
      this.get('file_service_params').Url = capTag;
    } else {
      var wechatTagStr = wechatTag;
      this.get('file_service_params').Data[wechatTagStr].Value = capTag;
    }
  },
  saveTemplate: function(wechatScope) {
    var self = this;
    //var that = this;
    //$('.c-selected-box').val()==this.model
    // var length = self.get('templates1').length;
    // for( i=0;i<length;i++){
    //     if($('.c-selected-box').val()==self.get('templates1')[i]['Title']){
    //         that = self.get('templates1')[i];
    //         break;
    //     }
    // }
    var status = $.Deferred();
    //this.setHtml().done(function () {
    var url = "/xaja/AjaxService/assets/save_wechat_template.json";
    //self.get('templates1')[0]['Title']
    //set Account Id
    self.set('AccountId', $('#wechat-accounts option:selected').val());
    self.set('scope', wechatScope );
    var data = self.toJSON();
    status = $.post(url, data, function(resp) {
      if (resp.success) {
        ca_wechat.helper.showFlash(resp.success);
        self.set('template_id', resp.template_id);
        app.CreativeViewInstance.showSocialTemplates();
      } else {
        ca_wechat.helper.showFlash(resp.error, true);
      }
    }, 'json');
    //});
    return status;
  },
  deleteTemplate: function() {
    if (this.get('template_id') && this.get('template_id') != "-1") {
      var url = "/xaja/AjaxService/assets/delete_html_template.json";
      var data = {
        template_id: this.get('template_id'),
        template_type: this.get('template_type'),
        account_id: $('#wechat-accounts').val()
      };
      var self = this;
      $.post(url, data, function(resp) {
        if (resp.success) {
          ca_wechat.helper.showFlash(resp.success);
          self.destroy();
        } else {
          ca_wechat.helper.showFlash(resp.error, true);
        }
      }, 'json');
    }
    this.destroy();
  }
});
//this is a util which give tabular version of JSON 
//Even if the JSON is multiple level
function getTableFromJSON(temp_cont) {
  var table = '<table>';
  for (var key in temp_cont) {
    if (typeof temp_cont[key] == "object") {
      table += '<tr>';
      table += '<td >' + key + ': </td>';
      table += '<td>';
      table += getTableFromJSON(temp_cont[key]);
    } else {
      table += '<tr style = "padding : 2px">';
      table += '<td style = "padding : 2px ">' + key + ': </td>';
      table += '<td style = "padding : 2px ">';
      table += temp_cont[key];
    }
    table += '</td></tr>';
  }
  table += '</table>';
  return table;
}
var CreativeAssetsWeChatTemplateView = Backbone.View.extend({
  tpl: _.template($('#ca_wechat_template_tpl').html()),
  tpl2: _.template($("#details-tpl").html()),
  initialize: function(options) {
    _.bindAll(this, "backToView");
    this.option_set = {
      text: {
        'default': [{
          className: 'preview',
          label: _campaign('Preview')
        }, {
          className: 'delete_template',
          label: _campaign('Delete')
        }],
        'wechat': [{
          className: 'delete_template',
          label: _campaign('Delete')
        }, {
          className: 'hello_template',
          label: _campaign('Template 1 jsadgjas jasgdjas askgdja')
        }]
      }
    };
    this.edit_type = options.edit_type;
    this.scope = options.scope;
    this.start = true;
    this.renderedList = this.model;
  },
  render: function() {
    var editor_options_set = this.option_set[this.editor_name];
    if (this.editor_name == 'text') {
      switch (this.scope) {
        case 'wechat':
          editor_options_set = this.option_set[this.editor_name][this.scope];
          break;
        default:
          editor_options_set = this.option_set[this.editor_name]['default'];
      }
    }
    var that = this;
    var edit_name = this.model.get('name')
    this.$el.html(this.tpl({
      template: this.model.get('templates1')
    }));
    if (edit_name != undefined) {
      this.$(".c-selected-box").val(edit_name);
      this.$(".c-selected-box").trigger('change');
    }


    return this;
  },
  events: {
    'click .back_to_view': 'backToView',
    'click .save_new_template': 'saveNewTemplate',
    'click .see_preview': 'showPreview',
    'click .preview_template': 'previewTemplate',
    'change .c-selected-box': 'showTemplate',
    'change .c-selected-tag-box': 'tagMapping',
    'click #wechat_scope_selector .wechat_scope': 'getTagsByScope',
    'keyup .c-input-tag-box': 'tagMapping'
  },
  getTagsByScope: function(e) {
    var that = this;
    that.wechatScope = e.currentTarget.value;
    console.log(that.wechatScope);
    // $.ajax({url: '/xaja/AjaxService/assets/'+'get_'+e.currentTarget.value+'_tags.json',
    //   dataType: 'json',
    //   success: function(res){
    //     console.log(res);
    //     switch(that.wechatScope){
    //       case 'wechat_loyalty':
    //         that.showTemplate(e, res['tags']['LOYALTY']);
    //         break;
    //       case 'wechat_dvs':
    //         that.showTemplate(e, res['tags']['DVS']);
    //         break;
    //       case 'wechat_outbound':
    //         that.showTemplate(e, res['tags']['OUTBOUND']);
    //         break;
    //     }
    //   }
    // });
    switch(that.wechatScope){
      case 'wechat_loyalty':
        that.showTemplate(e, this.model.attributes['tags']['LOYALTY']);
        break;
      case 'wechat_dvs':
        that.showTemplate(e, this.model.attributes['tags']['DVS']);
        break;
      case 'wechat_outbound':
        that.showTemplate(e, this.model.attributes['tags']['OUTBOUND']);
        break;
    }
  },
  tagMapping: function(e) {
    //var array = this.model.attributes.current_editing_template.attributes.file_service_params.Tag;
    var capTag;
    if ($(e.currentTarget).find('option:selected').attr("value") != undefined) {
      capTag = $(e.currentTarget).find('option:selected').attr("value");
    } else {
      capTag = $(e.currentTarget).val();
    }
    var wechatTag = this.$(e.currentTarget).attr('wechat-tag-data');
    //this.showEditLoader(true);
    this.model.get('current_editing_template').mapCapTags(wechatTag, capTag);
    
  },
  showTemplate: function(e, tagObj) {
    var src = $(e.target);
    var item = src.closest(".c-wechat-main");
    //var tmpl = _.template(this.detailsTempl);
    //var tmpl = _.template($("#details-tpl").html());
    //var tmpl = details_tpl;
    var length = this.renderedList.get('templates1').length;
    for (i = 0; i < length; i++) {
      if ($('.c-selected-box').val() == this.renderedList.get('templates1')[i]['Title']) break;
    }

    if(typeof(this.wechatScope) == "undefined"){
      this.wechatScope = this.renderedList.attributes.scope || "wechat_loyalty";
    }

    var current_editing_template = new WeChatTemplateModel();
    current_editing_template.set('template_id', this.model.get('template_id'));
    current_editing_template.set('file_service_params', this.renderedList.get('templates1')[i]);
    this.model.set('current_editing_template', current_editing_template);
    var str = this.renderedList.get('templates1')[i].content;
    var result = str.split('{{');
    var arr = [];
    for (j = 0; j < result.length; j++) {
      if (result[j].indexOf('}}') != -1) {
        var key = result[j].split('}}')[0].split('.DATA')[0];
        var str = key;
        var value = this.model.get('templates1')[i].Data[str].Value;
        arr.push({
          key: key,
          val: value
        });
      }
    }
    var tagArr = [];

    if(tagObj == null || tagObj.length == 0){
      console.log("Showing by default the tags for the scope that was selected while creating this template");
      switch(this.wechatScope){
        case 'WECHAT_LOYALTY':
        case 'wechat_loyalty':
          var obj = this.renderedList.get('tags')['WECHAT'];
          break;
        case 'WECHAT_DVS':
        case 'wechat_dvs':
          var obj = this.renderedList.get('tags')['DVS'];
          break;
        case 'WECHAT_OUTBOUND':
        case 'wechat_outbound':
          var obj = this.renderedList.get('tags')['OUTBOUND'];
          break;
        default:
          var obj = this.renderedList.get('tags')['WECHAT'];
      }
    } else {
      var obj = tagObj;
      console.log(obj);
    }
    for (var prop in obj) {
      if (obj.hasOwnProperty(prop)) {
        tagArr.push({
          label: prop,
          value: obj[prop]
        });
      }
    }
    var tempstr = this.renderedList.attributes.templates1[i]['Url'];
    var tempurl = tempstr.substring(tempstr.indexOf("callback="),tempstr.indexOf("&scope")).split('=')[1];
    var tempsend = (tempstr!='{{wechat_service_acc_url}}') ? ( (tempstr.indexOf("&callback=") == -1) ? tempstr : tempurl  ) : '';
    var tempInternal = (tempstr.indexOf("&callback=") == -1) ? 0 : 1;

    item.find(".c-show-template").html(this.tpl2({
      temp: this.renderedList.attributes.templates1[i],
      keylist: arr,
      capTags: tagArr,
      scope: this.wechatScope,
      url: tempsend,
      isInternalUrl: tempInternal
    }));
    //item.find(".c-show-template").html(this.tpl2(this.model.selected_model.toJSON()));
  },
  backToView: function() {
    //this.unbind();
    if (window.confirm(_campaign("You will lose all progress made, Do you want to continue"))) {
      //this.model.set('html_content','');
      app.CreativeViewInstance.showSocialTemplates();
      //this.$el.hide();
      //this.$el.siblings('.ca_top_view_container').show();
    }
    $(this.el).undelegate('.back_to_view', 'click');
  },
  saveNewTemplate: function() {
    if( this.$('.wechatcheck input[type="checkbox"]').is(':checked') == true) {
      this.model.get('current_editing_template').get('file_service_params').Url = 'https://capillary.qxuninfo.com/webapi/WeixinOauth/Authorize?appid=wxdebdf3f10c2f33f8&callback=' + this.model.get('current_editing_template').get('file_service_params').Url + '&scope=snsapi_base';
    }
    var self = this;
    if (this.model.get('current_editing_template') == "") {
      ca_wechat.helper.showFlash(_campaign('Please select the template'), true);
      return;
    }
    this.showEditLoader(true);
    this.model.get('current_editing_template').saveTemplate(this.wechatScope).done(function() {
      self.showEditLoader(false);
    });
  },
  showPreview: function() {
    var self = this;
    if (this.model.get('current_editing_template') == "") {
      ca_wechat.helper.showFlash(_campaign('Please select the template'), true);
      return false;
    }
    this.$('#edit_template_preview_modal').modal('show');
    var html_content = this.model.get('current_editing_template').get('file_service_params').content;
    html_content = html_content.replace('{{first.DATA}}', 'first.DATA');
    html_content = html_content.replace('{{remark.DATA}}', 'remark.DATA');
    var obj = this.model.get('current_editing_template').get('file_service_params').Data;
    for (var propt in obj) {
      html_content = html_content.replace(propt + '.DATA', obj[propt]['Value'].replace('{{', '').replace('}}', ''));
    }
    html_content = html_content.replace(/(?:\r\n|\r|\n)/g, '<br />');
    this.$('.preview_container').html(html_content);
  },
  setModel: function(model) {
    this.model = model;
  },
  showEditLoader: function(show) {
    if (show) {
      this.$('.edit_template_loader').addClass('intouch-loader').show();
    } else {
      this.$('.edit_template_loader').removeClass('intouch-loader').hide();
    }
  }
});
//using a new class for height of the wechat template
//check css
ca_wechat.WeChatTemplateView = Backbone.View.extend({
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
    }
  },
  events: {
    'click .delete_template': 'confirmDeleteTemplate',
    'click .confirm_delete': 'deleteTemplate',
    'click #edit_wechat_template': 'editWechatTemplate',
    'click .ca_preview_holder ': 'showPreview',
    'click .footer .favorite': 'toggleFavourite'
  },
  templateMessage: _.template($('#wechat_template_tpl').html()),
  singleImageTemplate: _.template($('#wechat_single_tpl').html()),
  multiImageTemplate: _.template($('#wechat_multi_tpl').html()),
  initialize: function() {
    this.listenTo(this.model, 'destroy', this.remove);
    this.listenTo(this.model, 'change', this.render);
  },
  render: function() {
    
    switch (ca_wechat.wechatList.scope) {
      case 'WECHAT_TEMPLATE':
        this.$el.addClass('ca-img-template-view');
        var table;
        try {
          var json = JSON.parse(this.model.toJSON().html_content);
          table = getTableFromJSON(json);
        } catch (e) {
          table = _campaign("INVALID JSON FORMAT");
        }
        var str = this.model.toJSON().html_content;
        str = str.replace(/(?:\r\n|\r|\n)/g, '<br />');
        str = str.replace(/{{/g, '');
        str = str.replace(/}}/g, '');
        this.$el.html(this.templateMessage({
          edit_options: this.edit_options,
          model: this.model.toJSON()
        }));
        break;
      case 'WECHAT_SINGLE_TEMPLATE':
        this.$el.html(this.singleImageTemplate({
          model: this.model.toJSON()
        }));
        break;
      case 'WECHAT_MULTI_TEMPLATE':
        this.$el.html(this.multiImageTemplate({
          model: this.model.toJSON()
        }));
        break;
      case 'text broadcast':
        break;
    }
    this.$el.contents('div').find('.ca_preview_holder').html(str);
    return this;
  },
  toggleFavourite: function(e) {
    var that = this;
    if ($('#template_scope').val() == 'WECHAT_SINGLE_TEMPLATE') {
      var template_id = $(e.currentTarget).parents('.wechatSingleTemplate').attr('template-id');
    }
    if ($('#template_scope').val() == 'WECHAT_MULTI_TEMPLATE') {
      var template_id = $(e.currentTarget).siblings('.wechatMultiTemplate').attr('template-id');
    }
    var url = "/xaja/AjaxService/assets/set_favourite_template.json";
    var data = {
      template_id: this.model.get('template_id'),
      is_favourite: this.model.get('is_favourite') ? 0 : 1,
      account_id: $('#wechat-accounts').val()
    };
    $.post(url, data, function(resp) {
      if (resp.success) {
        that.model.set('is_favourite', !that.model.get('is_favourite'));
        ca_wechat.helper.showFlash(resp.success);
      } else {
        ca_wechat.helper.showFlash(resp.error, true);
      }
    }, 'json');
  },
  showPreview: function(e) {
    if ($('.ca_top_view_container').attr('scope') == 'WECHAT_TEMPLATE') {
      this.$('#edit_template_preview_modal').modal('show');
      var html_content = this.model.get('templates1')[0].content;
      html_content = html_content.replace('{{first.DATA}}', 'first.DATA');
      html_content = html_content.replace('{{remark.DATA}}', 'remark.DATA');
      var obj = this.model.get('templates1')[0].Data;
      for (var propt in obj) {
        html_content = html_content.replace(propt + '.DATA', obj[propt]['Value'].replace('{{', '').replace('}}', ''));
      }
      html_content = html_content.replace(/(?:\r\n|\r|\n)/g, '<br />');
      this.$('.preview_container').html(html_content);
    }
  },
  confirmDeleteTemplate: function() {
    this.$('.confirm_delete_modal').modal('show');
  },
  editWechatTemplate: function() {
    if ($('#template_scope').val() == 'WECHAT_TEMPLATE') {
      $('.ca_top_create_new_container').hide();
      $('.ca_top_view_container').hide();
      $('.ca_top_edit_container').show();
      editTemplateViewInstance = new CreativeAssetsWeChatTemplateView({
        model: this.model,
        el: $('.ca_top_edit_container'),
        editor_name: 'text',
        scope: 'wechat'
      });
      editTemplateViewInstance.render();
    } else if ($('#template_scope').val() == 'WECHAT_MULTI_TEMPLATE') {
      $('.c-content #container #creative_tpl_container').addClass('hide');
      _.templateSettings.variable = "data";
      $('.c-content #container').append(_.template($('#multi_image_broadcast_create_tpl').html())(this.model.toJSON()));
    } else {
      $('.c-content #container #creative_tpl_container').addClass('hide');
      _.templateSettings.variable = "data";
      $('.c-content #container').append(_.template($('#single_image_broadcast_create_tpl').html())(this.model.toJSON()));
      CKEDITOR.replace('wechat_content', {
        toolbar: 'Basic'
      });
      CKEDITOR.instances.wechat_content.setData(decodeURIComponent((this.model.get('content')).content));
      app.CreativeViewInstance.activateImageUpload();
    }
  },
  deleteTemplate: function() {
    this.model.deleteTemplate();
  }
});


ca_wechat.WeChatTemplateCollection = Backbone.Collection.extend({
  model: ca_wechat.WeChatTemplateModel,
  ajax_url: '/xaja/AjaxService/assets/get_all_wechat_templates.json',
  scope: 'WECHAT_TEMPLATE',
  url: function() {
    return this.ajax_url;
  },
  parse: function(resp) {
    return resp.templates;
  },
});

var WechatModelTemplate = Backbone.Model.extend({
  url: '/xaja/AjaxService/assets/get_wechat_content_template.json',
  defaults: function() {
    return {
      wechat_data: [],
      current_editing_template: ""
    };
  }
});


ca_wechat.WeChatTemplateCollectionView = Backbone.View.extend({
  template_type: 'wechat',
  scope: 'WECHAT',
  tpl: _.template($('#templates_collection_tpl').html()),
  fetchXhr: null,
  events: {
    'click #create_wechat_template': 'createWechatTemplate',
    'keyup .ca-search-text': 'renderSearch',
    'click .all_template': 'renderAll',
    'click .favourite_template': 'renderFavourite',
    'change #template_scope': 'renderNewScope'
  },
  initialize: function() {
    this.listenTo(ca_wechat.wechatList, 'sync', this.renderTemplates);
    this.listenTo(ca_wechat.wechatList, 'reset', this.removeAll);
  },
  renderTemplates: function(data) {
    var templateModels = _.isArray(data) ? data : ca_wechat.wechatList.models;
    $('.ca_all_container_body').empty();
    _.each(templateModels, function(value, key) {
      var wechatView = new ca_wechat.WeChatTemplateView({
        model: value
      });
      $('.ca_all_container_body').append(wechatView.render().el);
    });
    $('.wait_initial_form').show().removeClass('intouch-loader');
  },
  renderNewScope: function(e) {
    $('.wait_initial_form').show().addClass('intouch-loader');
    this.removeAll();
    var scope = ((e) ? ($(e.currentTarget).val()) : 'WECHAT_TEMPLATE');
    ca_wechat.wechatList.scope = scope;
    $('.ca_top_view_container').attr('scope', scope);
    $('.ca-new-btn button').hide();
    switch (scope) {
      case 'WECHAT_TEMPLATE':
        ca_wechat.wechatList.ajax_url = '/xaja/AjaxService/assets/get_all_wechat_templates.json';
        $('.ca-new-btn #create_wechat_template').show();
        break;
      case 'WECHAT_SINGLE_TEMPLATE':
        ca_wechat.wechatList.ajax_url = '/xaja/AjaxService/assets/get_all_wechat_single_image_templates.json';
        $('.ca-new-btn #create_from_scratch').show();
        break;
      case 'WECHAT_MULTI_TEMPLATE':
        ca_wechat.wechatList.ajax_url = '/xaja/AjaxService/assets/get_all_wechat_multi_image_templates.json';
        $('.ca-new-btn #create_from_scratch').show();
        break;
      case 'WECHAT_BROADCAST':
        ca_wechat.wechatList.ajax_url = null
        break;
    }
    if (ca_wechat.wechatList.ajax_url) {
      if (!_.isNull(this.fetchXhr)) {
        this.fetchXhr.abort();
      }
      this.fetchXhr = ca_wechat.wechatList.fetch({
        data: {
          account_id: $('#wechat-accounts').val()
        }
      }).done(function() {
        $('.wait_initial_form').show().removeClass('intouch-loader');
      });
    }
  },
  showEditLoader: function(show) {
    if (show) {
      this.$('.all_wait_loader').addClass('intouch-loader').show();
    } else {
      this.$('.all_wait_loader').removeClass('intouch-loader').hide();
    }
  },
  showComplete: function(show) {
    if (show) {
      this.$('.ca_complete_msg').show();
    } else {
      this.$('.ca_complete_msg').hide();
    }
  },
  renderFavourite: function() {
    this.$('.all_template').removeClass('sel');
    this.$('.favourite_template').addClass('sel');
    this.renderTemplates(_.filter(ca_wechat.wechatList.models, function(template) {
      // values in template.get('is_favourite') are either boolean or string (1 || 0)
      // so type casting both to integer by multiplying by 1
      return (template.get('is_favourite') * 1);
    }));
  },
  renderAll: function() {
    this.$('.all_template').addClass('sel');
    this.$('.favourite_template').removeClass('sel');
    this.renderTemplates(ca_wechat.wechatList.models);
  },
  renderSearch: function(e) {
    var searchTerm = $(e.currentTarget).val();
    this.renderTemplates(_.filter(ca_wechat.wechatList.models, function(template) {
      return (template.get('template_name').indexOf(searchTerm) != -1 || (template.get('title') ? template.get('title').indexOf(searchTerm) != -1 : false));
    }));
  },
  addOne: function(wechatModel) {
    var wechatView = new ca_wechat.WeChatTemplateView({
      model: wechatModel
    });
    this.$('.ca_all_container_body').append(wechatView.render().el);
    $('.wait_initial_form').show().removeClass('intouch-loader');
  },
  removeAll: function() {
    this.$('.ca_all_container_body').empty();
    this.$('.ca_favourite_container_body').empty();
  },
  render: function() {
    this.$el.html(this.tpl({
      template_type: this.template_type,
      template_scope: this.scope,
      scopes_available: ca_wechat.scopesAvailable
    }));
    this.showComplete(true);
    return this;
  },
  createWechatTemplate: function(model) {
    var model1 = new ca_wechat.WeChatTemplateModel();
    var model2 = new WechatModelTemplate();
    var self = this;
    this.$('.ca_top_create_new_container').hide();
    this.$('.ca_top_view_container').hide();
    this.$('.ca_top_edit_container').show();
    this.showEditLoader(true);
    model2.fetch({
      reset: true,
      data: {
        account_id: $('#wechat-accounts').val()
      }
    });
    var that = this;
    model2.once('change', function() {
      that.editTemplateViewInstance1 = new CreativeAssetsWeChatTemplateView({
        el: that.$('.ca_top_edit_container'),
        model: model2
      });
      self.showEditLoader(false);
      that.editTemplateViewInstance1.render();
    });
  },
});