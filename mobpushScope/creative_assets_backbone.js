(function(factory) {
  var root = (typeof self == 'object' && self.self == self && self) || (typeof global == 'object' && global.global == global && global);
  root.CreativeAssets = factory(root.Backbone, root._, root.jQuery);
})(function(Backbone, _, $, edm_config) {
  var ca_ref;
  var creativeAssets = function() {
    if (ca_ref) return ca_ref;
    ca_ref = this;
    return this;
  };
  var ca = creativeAssets.prototype;
  ca.initialize = function(container, tags, edmUserId, scopesAvailable, scope, languages) {
    var self = this;
    this.container = $(container);
    this.tags = tags;
    this.scopesAvailable = scopesAvailable;
    this.edmUserId = edmUserId;
    this.languages = languages;
    if (this.edmUserId) {
      var token_url = "/xaja/AjaxService/assets/get_edm_token.json";
      if (typeof initEDMdesignerPlugin != 'undefined') {
        initEDMdesignerPlugin(token_url, this.edmUserId, function(edmDesignerApi) {
          self.edmDesignerApi = edmDesignerApi;
        });
      } else {
        ca.helper.showFlash(_campaign("Edm cannot be initialized"), true);
      }
    }
    if (scope) {
      this.scope = scope;
    } else {
      this.scope = 'ORG';
    }
  };
  ca.renderImageGallery = function() {
    if (this.image_gallery_view) {
      this.image_gallery_view.$el.empty().off();
      this.image_gallery_view.stopListening();
      this.image_gallery_view.undelegateEvents();
    }
    this.image_option = "delete";
    this.image_gallery_view = new ca.ImageGalleryView();
    this.image_gallery_view.setElement(this.container);
    this.image_gallery_view.option = 'delete';
    this.image_gallery_view.render();
  };
  ca.render = function() {
    if (this.templates_view) {
      this.templates_view.$el.empty().off();
      this.templates_view.stopListening();
      this.templates_view.undelegateEvents();
    }
    this.image_option = "insert";
    this.templates_view = new ca.TemplatesCollectionView({
      scope: this.scope,
      languages: this.languages
    });
    this.container.html(this.templates_view.render().el);
    this.container.append($("#insert_image_tpl").html());
  };
  ca.renderWeChat = function(weChatAccounts) {
    this.container.empty();
    this.wechatList = new this.WeChatTemplateCollection();
    appView = new this.WeChatTemplateCollectionView();
    this.container.append(appView.render().el);
    $('#template_type').val('social').attr("selected", "selected");
    this.renderWeChatAccounts(weChatAccounts);
    appView.renderNewScope();
  };
  ca.renderWeChatAccounts = function(weChatAccounts) {
    var select = '<div class="ca-option-div" id="ca-option-weChatAccounts"><select name="wechat-accounts" id="wechat-accounts">';
    _.each(weChatAccounts, function(v, k) {
      select += '<option value=' + v.id + ' data-app_id =' + v.app_id + ' data-app_secret = ' + v.app_secret + ' data-original_id=' + v.original_id + ' data-brand_id = ' + v.brand_id + '>' + v.account_name + '</option>';
    });
    select += '</select> <span class="ca-option-scope-separator"> &nbsp;/&nbsp;</span> </div>';
    $(select).insertBefore('#creative_tpl_container .ca-template-scope');
  };

   ca.renderMobilePush = function(mobilePushAccounts , tags) {
    $(".ca-header").hide();
    this.container.empty();
    var allTags = this.changeTags(tags);
    var tags = this.changeTags(tags.MOBILEPUSH);
    this.mobilePushList = new this.MobilePushTemplateCollection();
    appView = new this.MobilePushTemplateCollectionView({mobilePushAccounts : mobilePushAccounts , tags : tags, allTags: allTags});
    this.container.append(appView.render().el);
    $('#template_type').val('mobile_push').attr("selected", "selected");
    this.renderMobilePushAccounts(mobilePushAccounts);
    appView.renderNewScope();
  };

  ca.changeTags =function(tags){
    return _.map(tags, function(val, key) {
          if (typeof val == "string") {
            return {
              name: key,
              val: val
            }
          } else if (typeof val == "object") {
            return {
              name: key,
              children: ca.changeTags(val)
            }
          }
        });
  };


  ca.renderMobilePushAccounts = function(mobilePushAccounts) {
    console.log(mobilePushAccounts);
    var acc_tpl = _.template($('#account-type').html());
    $("#creative_tpl_container .ca-account-type").html(acc_tpl({ mobilePushAccounts: mobilePushAccounts}));
    if ( typeof mobilePushAccounts == 'undefined' || !mobilePushAccounts.length) {
      disableOnZeroAccounts();
      $('.ca-template-scope').hide();
    }
  };
  ca.mobilePushTags = function(){
      var url = 
      $.ajax({
        url: "/xaja/AjaxService/assets/initial_mobile_push_data.json",
        dataType: 'json',
        async: false,
        success: function(data) {
           tags = data.tags; 
        }
      });
      // var tags = this.changeTags(tags.MOBILEPUSH);
      var tags = this.changeTags(tags);
      return tags;
  };

  function disableOnZeroAccounts() {
    $('#mobile-push-accounts').prop('disabled', true);
    $('#template_scope').prop('disabled', true);
    $('#create_mobile_push_template').prop('disabled', true);
  }

  var helper = ca.helper = {
    showFlash: function(msg, error) {
      if (error) {
        $('.flash_message').addClass('redError').html(msg).show();
      } else {
        $('.flash_message').removeClass('redError').html(msg).show();
      }
      setTimeout(function() {
        $('.flash_message').fadeOut('fast', function() {
          $('.flash_message').removeClass('redError');
        })
      }, 3000);
    }
  };
  var TemplateModel = ca.TemplateModel = Backbone.Model.extend({
    defaults: function() {
      return {
        template_id: '',
        name: '',
        html_content: '',
        is_preview_generated: false,
        preview_url: '',
        is_favourite: false,
        is_drag_drop: false,
        drag_drop_id: '',
        scope: 'ORG',
        tag: 'GENERAL',
        is_default: false,
        language_id: -1,
        base_language_id: -1,
        is_multi_lang: 0,
        base_template_id: -1
      };
    },
    next: function() {
      if (this.collection) {
        return this.collection.at(this.collection.indexOf(this) + 1);
      }
    },
    prev: function() {
      if (this.collection) {
        return this.collection.at(this.collection.indexOf(this) - 1);
      }
    },
    templateSelected: function() {
      this.trigger('selected');
    },
    templateUnselected: function() {
      this.trigger('unselected');
    },
    getHtmlFromEdm: function(status) {
      var self = this;
      var iframe = document.getElementById("edm_editor_iframe");
      if (iframe) {
        var win = document.getElementById("edm_editor_iframe").contentWindow;
        win.postMessage('saveProject', '*');
      }
      ca_ref.edmDesignerApi.generateProject(self.get('drag_drop_id'), function(result) {
        result = result.replace(/TOKEN%7D%7D/g, 'TOKEN}}').replace(/&=amp%3B%7B%7BSURVEY/g, '&amp;{{SURVEY');
        self.set('html_content', result);
        if (status) status.resolve();
      });
    },
    setHtml: function() {
      var status = $.Deferred();
      var self = this;
      var old_html = this.get('html_content');
      if (this.get('is_drag_drop') && this.get('drag_drop_id')) {
        this.getHtmlFromEdm(status);
      } else {
        if (old_html) {
          status.resolve();
          return status;
        }
        var url = '/xaja/AjaxService/assets/get_html_content.json?template_id=' + this.get('template_id');
        if (this.get('is_default')) url += '&default=1';
        $.getJSON(url, function(data) {
          if (data.html) {
            self.set('html_content', data.html);
          }
          status.resolve();
        })
      }
      return status;
    },
    toggleFavourite: function() {
      this.set('is_favourite', !this.get('is_favourite'));
      var url = "/xaja/AjaxService/assets/set_favourite_template.json";
      var data = {
        template_id: this.get('template_id'),
        is_favourite: this.get('is_favourite') ? 1 : 0
      };
      $.post(url, data, function(resp) {}, 'json');
    },
    duplicateModel: function(status) {
      console.log("duplicate model is called");
      var is_drag_drop = this.get('is_drag_drop');
      var drag_drop_id = this.get('drag_drop_id');
      var duplicate_model = this.clone();
      var timestamp = Date.now();
      duplicate_model.set({
        name: this.get('name') + '_' + timestamp,
        is_favourite: false
      });
      if (is_drag_drop && drag_drop_id) {
        var done = false;
        if (this.get('is_default')) {
          ca_ref.edmDesignerApi.createFromDefaults(drag_drop_id, {
            title: "new project",
            description: "new project"
          }, function(result) {
            duplicate_model.set({
              template_id: '',
              drag_drop_id: result._id,
              is_default: false,
              name: _campaign('Template') + '_' + timestamp
            });
            done = true;
            status.resolve();
          });
        } else {
          ca_ref.edmDesignerApi.duplicateProject(drag_drop_id, function(result) {
            duplicate_model.set({
              template_id: '',
              drag_drop_id: result._id
            });
            done = true;
            status.resolve();
          });
        }
        return duplicate_model;
      } else {
        duplicate_model.setHtml().done(function() {
          duplicate_model.set({
            template_id: ''
          });
          status.resolve();
        })
        return duplicate_model;
      }
    },
    deleteTemplate: function() {
      var url = "/xaja/AjaxService/assets/delete_html_template.json";
      var data = {
        template_id: this.get('template_id')
      };
      var self = this;
      $.post(url, data, function(resp) {
        if (resp.success) {
          ca.helper.showFlash(resp.success);
          self.destroy();
        } else {
          ca.helper.showFlash(resp.error, true);
        }
      }, 'json');
    },
    saveTemplate: function() {
      var self = this;
      var status = $.Deferred();
      this.setHtml().done(function() {
        var url = "/xaja/AjaxService/assets/save_html_template.json";
        var data = self.toJSON();
        status = $.post(url, data, function(resp) {
          if (resp.success) {
            ca.helper.showFlash(resp.success);
            self.set('template_id', resp.template_id);
            ca_ref.render();
          } else {
            ca.helper.showFlash(resp.error, true);
          }
        }, 'json');
      });
      return status;
    }
  });
  // this view contains template image with favourite button
  var TemplateView = ca.TemplateView = Backbone.View.extend({
    model: TemplateModel,
    tpl: _.template($('#template_tpl').html()),
    className: 'ca-template-view',
    initialize: function() {
      this.listenTo(this.model, 'change', this.render);
    },
    render: function() {
      this.$el.html(this.tpl(this.model.toJSON()));
      return this;
    },
    events: {
      'click .ca_favourite_icon': 'toggleFavourite',
      'click .ca_preview_holder': 'showPreview'
    },
    toggleFavourite: function() {
      this.model.toggleFavourite();
    },
    showPreview: function() {
      this.trigger('show_preview', this.model);
    }
  });
  var CreateTemplateView = ca.CreateTemplateView = Backbone.View.extend({
    tpl: _.template($('#create_template_tpl').html()),
    className: 'ca-create-template-view',
    initialize: function() {},
    render: function() {
      this.$el.html(this.tpl(this.model.toJSON()));
      return this;
    },
    events: {
      'click': 'editSelectedTemplate'
    },
    editSelectedTemplate: function(e) {
      var self = this;
      var status = $.Deferred();
      var model = this.model.duplicateModel(status);
      var clickElementParent = $(e.currentTarget).parent();
      var lang_id = clickElementParent.attr("template_lang_id");
      $('.all_wait_loader').addClass('intouch-loader').show();
      status.done(function() {
        $('.all_wait_loader').addClass('intouch-loader').hide();
        self.trigger('edit_selected_template', model, 'new', lang_id);
      });
    }
  });
  var TemplatePreviewParent = Backbone.View.extend({
    tpl: _.template($('#container_email_preview_tpl').html()),
    child_preview: [],
    initialize: function() {
      console.log("in template preview parent initialize");
      this.child_preview = [];
    },
    render: function() {
      template_lang_ids = [];
      if (this.model.get('lang_id_group')) template_lang_ids = this.model.get('lang_id_group').split(",");
      this.$el.html(this.tpl({
        modelData: this.model.toJSON(),
        languages: ca_ref.languages.all_lang,
        base_language_id: ca_ref.languages.base_language_id,
        template_lang_ids: template_lang_ids
      }));
      if (!this.child_preview[ca_ref.languages.base_language_id]) {
        this.child_preview[ca_ref.languages.base_language_id] = new TemplatePreviewView({
          model: this.model,
          el: this.$("#language_content__" + ca_ref.languages.base_language_id)
        });
      }
      this.child_preview[ca_ref.languages.base_language_id].render();
      this.child_preview[ca_ref.languages.base_language_id].$(".lang_enabled_hide").hide();
    },
    events: {
      "click .language_based_preview_tab": "previewLangTabClicked",
      "click .preview_favourite": "toggleFavourite",
      "click .reset_preview_tab": "resetPreviewTab",
      "click .close": "closingPreview"
    },
    closingPreview: function(e) {
      $("#template_preview_modal").modal('hide');
      for (var lang_id in this.child_preview) {
        this.child_preview[lang_id].stopListening();
        this.child_preview[lang_id].undelegateEvents();
        this.child_preview[lang_id].$el.empty().off();
        this.child_preview[lang_id].remove();
      }
    },
    resetPreviewTab: function(e) {
      console.log("reset called");
      this.child_preview = [];
    },
    toggleFavourite: function(e) {
      var base_template_view = this.child_preview[ca_ref.languages.base_language_id];
      base_template_view.model.set('is_favourite', !base_template_view.model.get('is_favourite'));
      var url = "/xaja/AjaxService/assets/set_favourite_template.json";
      var data = {
        template_id: base_template_view.model.get('template_id'),
        is_favourite: base_template_view.model.get('is_favourite') ? 1 : 0
      };
      $.post(url, data, function(resp) {}, 'json');
      $(e.currentTarget).children('i').toggleClass('icon-heart-empty').toggleClass('icon-heart');
    },
    previewLangTabClicked: function(e) {
      console.log("clicked element is : ");
      console.log(e);
      lang_id = $(e.currentTarget).attr("id").split("__")[1];
      scope = ca_ref.scope;
      base_template_id = this.model.get('template_id');
      $(".language_based_preview_tab").removeClass("tab_selected");
      $("#language_button__" + lang_id).addClass("tab_selected");
      if (!this.child_preview[lang_id]) {
        var url = '/xaja/AjaxService/assets/get_multi_lang_template.json?parent_template_id=' + base_template_id + '&scope=' + scope;
        var lang_ids = [];
        var self = this;
        $.getJSON(url, function(data) {
          if (data.templates) {
            $.each(data.templates, function(key, val) {
              tempModel = new TemplateModel(val);
              console.log("value is : ");
              console.log(val);
              lang_ids.push(val.language_id);
              self.child_preview[val.language_id] = new TemplatePreviewView({
                model: tempModel,
                el: $("#language_content__" + val.language_id)
              });
            });
            $(".language_based_preview_content").hide();
            self.child_preview[lang_id].render();
            self.child_preview[lang_id].$(".lang_enabled_hide").hide();
            $("#language_content__" + lang_id).show();
          }
        });
      } else {
        $(".language_based_preview_content").hide();
        $("#language_content__" + lang_id).show();
        this.child_preview[lang_id].render();
        this.child_preview[lang_id].$(".lang_enabled_hide").hide();
      }
    }
  });
  var TemplatePreviewView = ca.TemplatePreviewView = Backbone.View.extend({
    tpl: _.template($('#email_preview_tpl').html()),
    initialize: function() {},
    render: function() {
      var self = this;
      this.$el.html(this.tpl(this.model.toJSON()));
      this.showLoader(true);
      this.model.setHtml().done(function() {
        self.showLoader(false);
        var html_content = self.model.get('html_content');
        window.setTimeout(function() {
          self.$('#template_email_iframe_preview').contents().find('html').html(html_content);
          self.$('#template_preview_iframe_mobile_portrait').contents().find('html').html(html_content);
          self.$('#template_iframe_mobile_landscape').contents().find('html').html(html_content);
        }, 1000);
      })
    },
    events: {
      'click .btn_email_mobile': 'showMobile',
      'click .btn_email_desktop': 'showDesktop',
      'click .btn_email_tablet': 'showTablet',
      'click .preview_favourite': 'favouriteTemplate'
    },
    favouriteTemplate: function(e) {
      this.model.toggleFavourite();
      $(e.currentTarget).children('i').toggleClass('icon-heart-empty').toggleClass('icon-heart');
    },
    showMobile: function() {
      this.$('.email_desktop').addClass('hide');
      this.$('.email_mobile').removeClass('hide');
      this.$('.email_tablet').addClass('hide');
      this.$('.btn_email_mobile ').addClass('btn-inverse active background-color').removeClass('btn-default');
      this.$('.btn_email_desktop').addClass('btn-default').removeClass('btn-inverse active background-color');
      this.$('.btn_email_tablet').addClass('btn-default').removeClass('btn-inverse active background-color');
    },
    showDesktop: function() {
      this.$('.email_desktop').removeClass('hide');
      this.$('.email_mobile').addClass('hide');
      this.$('.email_tablet').addClass('hide');
      this.$('.btn_email_desktop').addClass('btn-inverse active background-color').removeClass('btn-default');
      this.$('.btn_email_mobile').addClass('btn-default').removeClass('btn-inverse active background-color');
      this.$('.btn_email_tablet').addClass('btn-default').removeClass('btn-inverse active background-color');
    },
    showTablet: function() {
      this.$('.email_desktop').addClass('hide');
      this.$('.email_mobile').addClass('hide');
      this.$('.email_tablet').removeClass('hide');
      this.$('.btn_email_tablet').addClass('btn-inverse active background-color').removeClass('btn-default');
      this.$('.btn_email_mobile').addClass('btn-default').removeClass('btn-inverse active background-color');
      this.$('.btn_email_desktop').addClass('btn-default').removeClass('btn-inverse active background-color');
    },
    showLoader: function(show) {
      if (show) {
        this.$('.ca_loader').addClass('intouch-loader').show().parent('div').show();
      } else {
        this.$('.ca_loader').removeClass('intouch-loader').hide().parent('div').hide();
      }
    }
  });
  //this view also include all the editing option especially for creative assets
  var CreativeAssetsTemplateView = ca.CreativeAssetsTemplateView = Backbone.View.extend({
    tagName: 'div',
    className: 'ca-template-view',
    tpl: _.template($('#ca_template_tpl').html()),
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
    initialize: function() {
      this.template_view = new TemplateView({
        model: this.model
      });
      this.listenTo(this.template_view, 'show_preview', this.showPreview);
      this.listenTo(this.model, 'destroy', this.remove);
    },
    render: function() {
      this.$el.html(this.tpl({
        edit_options: this.edit_options,
        model: this.model.toJSON()
      }));
      this.template_view.setElement(this.$('.template_view')).render();
      return this;
    },
    events: {
      'click .edit_template': 'editTemplate',
      'click .duplicate_template': 'duplicateTemplate',
      'click .delete_template': 'confirmDeleteTemplate',
      'click .confirm_delete': 'deleteTemplate'
    },
    editTemplate: function() {
      this.trigger('edit_template', this.model, 'edit');
    },
    duplicateTemplate: function() {
      this.trigger('duplicate_template', this.model, 'duplicate');
      /*var status = $.Deferred(),
          self = this;
      var model = this.model.duplicateModel(status);
      status.done(function () {
          self.trigger('duplicate_template', model, 'duplicate');
      });*/
    },
    confirmDeleteTemplate: function() {
      this.$('.confirm_delete_modal').modal('show');
    },
    deleteTemplate: function() {
      this.model.deleteTemplate();
    },
    showPreview: function() {
      var self = this;
      self.$("#template_preview_modal").modal('show');
      //self.preview_view = new TemplatePreviewView({model: self.model});
      console.log("model is : ");
      console.log(self.model);
      self.preview_view = new TemplatePreviewParent({
        model: self.model
      });
      self.preview_view.setElement(self.$('.preview_container')).render();
    }
  });
  // this is collection of template model
  var TemplatesCollection = ca.TemplatesCollection = Backbone.Collection.extend({
    model: TemplateModel,
    completed: false,
    favourite: false,
    search: '',
    default_template: false,
    default_limit: 18,
    start_limit: 0,
    limit: 12,
    new_models: [],
    scope: 'ORG',
    initialize: function() {
      this.on('add', function(model) {
        this.new_models.push(model);
      });
    },
    url: function() {
      if (this.default_template) {
        ajax_url = '/xaja/AjaxService/assets/get_default_html_templates.json';
      } else {
        var limit = this.limit;
        if (this.length < this.default_limit) {
          limit = this.default_limit - this.length;
        }
        var ajax_url = '/xaja/AjaxService/assets/get_html_templates.json?start=' + this.start_limit + '&limit=' + limit + '&scope=' + this.scope;
        if (this.favourite) {
          ajax_url += '&favourite=1';
        }
        if (this.search) {
          ajax_url += '&search=' + this.search;
        }
        if (this.template_type) {
          ajax_url += '&template_type=' + this.template_type;
        }
      }
      return ajax_url;
    },
    parse: function(resp) {
      this.completed = resp.completed;
      var search = resp.search;
      if (search && this.search && search !== this.search) {
        return false;
      }
      return resp.templates;
    }
  });
  var LanguageBasedTemplate = Backbone.View.extend({
    languages: [],
    added_languages: [],
    lang_based_reference: [],
    langBasedEditTemplates: [],
    template_name: "",
    is_favourite: false,
    tpl: _.template($("#lang_based_content").html()),
    initialize: function(options) {
      console.log("lang based content initialize called");
      if (options && options.languages) {
        this.languages = options.languages;
      }
      if (options && options.is_favourite) {
        this.is_favourite = options.is_favourite;
      }
      this.lang_id_name = [];
      var i = 0;
      for (var key in this.languages.all_lang) {
        this.lang_id_name[i] = {};
        this.lang_id_name[i].lang_id = key;
        this.lang_id_name[i].lang_name = this.languages.all_lang[key];
        i++;
      }
      this.added_languages = [];
      this.added_languages[0] = {};
      var base_lang_id = this.languages.base_language_id;
      this.added_languages[0].lang_id = base_lang_id;
      this.added_languages[0].lang_name = this.languages.all_lang[base_lang_id];
    },
    render: function() {
      console.log("lang based content render called");
      console.log("language based template render langs:");
      console.log(this.languages);
      console.log(this.$el);
      var template_spec = {};
      template_spec.scope = _campaign(ca_ref.scope);
      template_spec.template_type = $("#template_type option:selected").html();
      if (ca_ref.scope == "ORG") template_spec.scope = _campaign("Bulk Campaigns");
      else {
        template_spec.scope = _campaign(ca_ref.scope);
        if (ca_ref.scope == 'POINTSENGINE') template_spec.scope = _campaign('Points Engine');
      }
      var base_lang_spec = {};
      base_lang_spec.lang_id = this.languages.base_language_id;
      base_lang_spec.lang_name = this.languages.all_lang[base_lang_spec.lang_id];
      var template_info = {};
      template_info.is_favourite = this.is_favourite;
      this.template_name = this.template_name.trim();
      /*if(this.template_name == ''){
          var timestamp = Date.now();
          this.template_name = _campaign('Template')+'_' + timestamp;
      }
      template_info.template_name = this.template_name ;*/
      template_info.template_name = "";
      this.$el.html(this.tpl({
        languages: this.lang_id_name,
        base_lang: base_lang_spec,
        template_spec: template_spec,
        scopes_available: ca_ref.scopesAvailable,
        model: template_info
      }));
      $(".ca-header").hide();
      $(".lang_tab[lang_id=" + this.languages.base_language_id + "]").trigger("click");
      $("#create_new_template_parent").show();
      $(".create_new_template").hide();
      $(".create_new_template[template_lang_id=" + this.languages.base_language_id + "]").show();
      $("#lang_content_parent").show();
      console.log("model is : ");
      console.log(this.model);
    },
    events: {
      "click #add_lang": "showLanguageModal",
      "click .add_lang_btn": "addLanguageModal",
      "click .lang_tab": "showLanguageSpecificContent",
      "click .save_all_templates": "saveAllTemplates",
      "click .delete_template": "deleteAllTemplates",
      "click .remove_language_list": "removeLanguage",
      "click #lang_edit_name": "openEditNameModel",
      'click #save_name_scope': 'saveNameScope',
      'click .ca_edit_favourite_icon': 'favouriteTemplate',
      'click #change_name': 'changeTemplateName'
    },
    setIsFavourite: function(is_favourite) {
      this.is_favourite = is_favourite;
      if (is_favourite) {
        $('.favourite-indicator').removeClass('icon-heart-empty');
        $('.favourite-indicator').addClass('icon-heart');
      } else {
        $('.favourite-indicator').removeClass('icon-heart');
        $('.favourite-indicator').addClass('icon-heart-empty');
      }
    },
    setTemplateName: function(name) {
      var timestamp = Date.now();
      var temp_name = _campaign('Template') + '_' + timestamp;
      this.template_name = temp_name;
      if (name) {
        this.template_name = name;
      }
    },
    favouriteTemplate: function(e) {
      this.is_favourite = !this.is_favourite;
      $(e.currentTarget).children('i').toggleClass('icon-heart-empty').toggleClass('icon-heart');
    },
    changeTemplateName: function() {
      var name = $("#lang_rename_template").val();
      name = name && name.trim();
      if (!name) return false;
      this.template_name = name;
      this.renderNewName(name);
      $('#lang_edit_name_modal').modal('hide');
    },
    renderNewName: function(template_name) {
      $("#lang_edit_template_name").html(template_name);
    },
    openEditNameModel: function() {
      $("#lang_rename_template").val(this.template_name);
      $("#lang_edit_name_modal").modal();
    },
    saveNameScope: function() {
      var name = $("#lang_new_template_name").val();
      var scope = $("#lang_new_template_scope :selected").val();
      this.template_name = name && name.trim();
      if (!this.template_name) {
        var timestamp = Date.now();
        var temp_name = _campaign('Template') + '_' + timestamp;
      }
      ca_ref.scope = scope;
      if (ca_ref.scope == 'POINTSENGINE' || ca_ref.scope == 'REFERRAL') {
        $('.lang_enabled_show').hide();
        $('#lang_tab_parent').hide();
        $('#lang_content_scope').val(_campaign(ca_ref.scope));
        if (ca_ref.scope == 'POINTSENGINE') $('#lang_content_scope').val(_campaign('Points Engine'));
      } else {
        $('.lang_enabled_show').show();
        $('#lang_tab_parent').show();
        $('#lang_content_scope').val(_campaign(ca_ref.scope));
        $('#lang_content_scope').val(_campaign('Bulk Campaigns'));
      }
      //this.model.set({name: name, scope: scope});
      this.renderNewName(this.template_name);
      $('#lang_new_name_modal').modal('hide');
      /*if (scope != this.scope) {
          if (scope == 'EBILL') {
              this.editor_name = 'text';
              this.scope = scope;
              this.render();
          } else if (this.scope == 'EBILL') {
              this.editor_name = 'inline_ck';
              this.scope = scope;
              this.render();
          } else {
              this.scope = scope;
              this.renderEditor();
          }
      }*/
    },
    showNewNameModal: function(name, type) {
      $("#lang_new_name_modal").modal();
      $('#lang_new_template_scope').val(ca_ref.scope);
      $("#lang_new_template_name").val(name);
      if (type == 'duplicate') $('#lang_new_template_scope').attr('disabled', true);
      this.template_name = name;
    },
    deleteAllTemplates: function() {
      for (var key in this.langBasedEditTemplates) {
        this.langBasedEditTemplates[key].model.deleteTemplate();
      }
      ca_ref.render();
    },
    showEditLoader: function(show) {
      if (show) {
        this.$('.edit_template_loader').addClass('intouch-loader').show();
      } else {
        this.$('.edit_template_loader').removeClass('intouch-loader').hide();
      }
    },
    saveAllTemplates: function(e) {
      var resp = [];
      var arr = [];
      var flag = 0;
      $('#lang_list li').each(function() {
        arr.push($(this).attr('lang_id'))
      });
      $.each(arr, function(i, val) {
        $('.template_editor').each(function() {
          if ($(this).attr('editor_lang_id') == arr[i]) {
            if ($(this).children().css('display') == undefined) {
              ca.helper.showFlash(_campaign('Please insert contents for all selected language'), true);
              flag = 1;
              return false;
            }
          }
        });
        if (flag == 1) return false;
      });
      if (flag == 1) return false;
      var url = "/xaja/AjaxService/assets/save_html_template.json";
      var deferred = [];
      var self = this;
      var base_template_id = -1;
      this.showEditLoader(true);
      if (ca_ref.languages && ca_ref.languages.base_language_id) {
        for (var key in this.langBasedEditTemplates) {
          if (this.langBasedEditTemplates[key] == null) continue;
          var lang_name = ca_ref.languages.all_lang[key];
          var template_name = this.template_name + "_" + lang_name;
          this.langBasedEditTemplates[key].model.set({
            name: template_name,
            language_id: key,
            scope: ca_ref.scope,
            is_favourite: this.is_favourite
          });
          resp.push(this.langBasedEditTemplates[key].model.setHtml());
        }
        this.langBasedEditTemplates[ca_ref.languages.base_language_id].model.set("name", this.template_name);
      }
      $.when.apply($, resp).done(function() {
        console.log("edm save success");
        var base_language_id = self.languages.base_language_id;
        self.langBasedEditTemplates[base_language_id].model.set('is_multi_lang', 1);
        self.langBasedEditTemplates[base_language_id].model.set('base_template_id', -1);
        var data = self.langBasedEditTemplates[base_language_id].model.toJSON();
        $.post(url, data, function(resp) {
          if (resp.success) {
            base_template_id = resp.template_id;
            self.langBasedEditTemplates[base_language_id].model.set('template_id', resp.template_id);
          } else {
            ca.helper.showFlash(resp.error, true);
            self.showEditLoader(false);
          }
        }, 'json').done(function() {
          console.log("base template success");
          if (base_template_id > 0) {
            console.log("valid base template id");
            for (var key in self.langBasedEditTemplates) {
              if (key == self.languages.base_language_id || self.langBasedEditTemplates[key] == null) {
                continue;
              }
              self.langBasedEditTemplates[key].model.set('is_multi_lang', 1);
              self.langBasedEditTemplates[key].model.set('base_template_id', base_template_id);
              data = self.langBasedEditTemplates[key].model.toJSON();
              deferred.push($.post(url, data, function(resp) {
                console.log("in success 1");
                if (resp.success) {
                  self.langBasedEditTemplates[key].model.set('template_id', resp.template_id);
                } else {
                  ca.helper.showFlash(resp.error, true);
                  self.showEditLoader(false);
                }
              }, 'json'));
            }
            $.when.apply($, deferred).done(function() {
              ca.helper.showFlash(_campaign("Template saved successfully"));
              self.showEditLoader(false);
              ca_ref.render();
            }).fail(function() {
              ca.helper.showFlash(_campaign("Failed to save all the templates"), true);
              self.showEditLoader(false);
            });
          }
        }).fail(function() {
          //console.log("base template failure") ;
          ca.helper.showFlash(_campaign("Failed to save primary template"), true);
          self.showEditLoader(false);
        });
      }).fail(function() {
        //console.log("edm save failure") ;
        ca.helper.showFlash(_campaign("Failed to save templates to server"), true);
        self.showEditLoader(false);
      });
    },
    showLanguageSpecificContent: function(e) {
      var clickedElement = $(e.currentTarget);
      var lang_id = clickedElement.attr("lang_id");
      //$(".lang_tab").css("display","none") ;
      //$(".lang_tab[lang_id="+lang_id+"]").css("display","block") ;
      $(".create_new_template").hide();
      $(".template_editor").hide();
      $(".lang_tab").removeClass('selected_lang_tab');
      clickedElement.addClass('selected_lang_tab');
      if (this.langBasedEditTemplates[lang_id]) {
        $(".template_editor[editor_lang_id=" + lang_id + "]").show();
        $("#create_new_template_parent").hide();
      } else {
        $(".create_new_template[template_lang_id=" + lang_id + "]").show();
        $("#create_new_template_parent").show();
        $(".new_template_header").show();
      }
    },
    editLanguageTemplate: function(lang_ids) {
      len = lang_ids.length;
      var element = "";
      for (i = 0; i < len; i++) {
        lang_id = lang_ids[i];
        if (this.getLanguageIndex(lang_id) == -1) {
          element += '<li lang_id="' + lang_id + '" class="lang_tab" >' + this.languages.all_lang[lang_id] + '</li>';
          this.addLanguage(lang_id);
        }
      }
      $(".lang_tab[lang_id=" + lang_ids.join("],[lang_id=") + "]").remove();
      if (element) {
        $("#lang_list").append(element);
      }
      $(".create_new_template").hide();
      $(".template_editor").hide();
      $(".template_editor[editor_lang_id=" + this.languages.base_language_id + "]").show();
    },
    addLanguageModal: function() {
      var $option_select = $("#lang_select option:selected");
      if ($option_select.val() == 1) {
        ca.helper.showFlash(_campaign('please select the language!'), true);
        return false;
      }
      $("select#lang_select").val("1");
      var lang_id = $option_select.attr("option_lang_id");
      var element = '<li lang_id="' + lang_id + '" class="lang_tab" >' + this.languages.all_lang[lang_id] + '</li>';
      if (this.getLanguageIndex(lang_id) == -1) $("#lang_list").append(element);
      this.addLanguage(lang_id);
      $option_select.css("display", "none");
      $(".lang_tab").removeClass("selected_lang_tab");
      $(".lang_tab[lang_id=" + lang_id + "]").addClass("selected_lang_tab");
      $(".lang_tab[lang_id=" + lang_id + "]").css("display", "block");
      $('#add_lang_modal').modal('hide');
      $("#create_new_template_parent").show();
      $(".create_new_template").hide();
      $(".template_editor").hide();
      $(".create_new_template[template_lang_id=" + lang_id + "]").show();
      $(".new_template_header").show();
      var flag = 0;
      $('.lang_option').each(function() {
        if ($(this).css('display') == 'none') {
          flag = 1;
        } else {
          flag = 0;
          return false;
        }
      });
      if (flag == 1) {
        $('#add_lang').css('display', 'none');
      } else {
        $('#add_lang').css('display', 'inline');
      }
    },
    showLanguageModal: function() {
      $('#add_lang_modal').modal('show');
    },
    getLanguageIndex: function(lang_id) {
      for (var key in this.added_languages) {
        if (this.added_languages[key].lang_id == lang_id) return key;
      }
      return -1;
    },
    addLanguage: function(lang_id) {
      if (this.getLanguageIndex(lang_id) == -1) {
        var tempObj = {};
        tempObj.lang_id = lang_id;
        tempObj.lang_name = this.languages.all_lang[lang_id];
        this.added_languages.push(tempObj);
      }
    },
    removeLanguage: function() {
      var lang_id = $(".selected_lang_tab").attr("lang_id");
      var lang_index = this.getLanguageIndex(lang_id);
      if (lang_index >= 0) {
        console.log("is lang added , added_languages");
        console.log(this.added_languages);
        this.added_languages.splice(lang_index, 1);
        console.log("removing languages, langbasededittemplates model is : ");
        console.log(this.langBasedEditTemplates.model);
        if (this.langBasedEditTemplates[lang_id].model.get("template_id")) {
          this.langBasedEditTemplates[lang_id].model.set("is_deleted", 1);
        } else {
          this.langBasedEditTemplates[lang_id] = null;
        }
        $(".template_editor[editor_lang_id=" + lang_id + "]").hide();
        $(".lang_tab[lang_id=" + lang_id + "]").remove();
        var base_lang_id = ca_ref.languages.base_language_id;
        $(".lang_tab[lang_id=" + base_lang_id + "]").addClass("selected_lang_tab");
        $(".lang_option[option_lang_id=" + lang_id + "]").show();
        $(".lang_tab[lang_id=" + base_lang_id + "]").trigger("click");
        $('#add_lang').css('display', 'inline');
      }
      /*if(this.langBasedEditTemplates[base_lang_id]){
          $("#lang_content_parent").show() ;
          $(".template_editor[editor_lang_id="+base_lang_id+"]").show() ;
      }else{
          $("#create_new_template_parent").show() ;
          $(".new_template_header").show() ;
          $(".create_new_template[template_lang_id="+base_lang_id+"]").show() ;
      }*/
    }
  });
  //  this is more like mother view
  // this view is all templates view
  // it includes collection of all subview is CreativeAssetsTemplateView and header option
  // It also includes edit template view
  var TemplatesCollectionView = ca.TemplatesCollectionView = Backbone.View.extend({
    new_label: 'New',
    current_view: 'all',
    mode: 'view',
    tpl: _.template($('#templates_collection_tpl').html()),
    template_type: 'html',
    scope: 'ORG',
    initialize: function(options) {
      this.templateView = CreativeAssetsTemplateView;
      this.editTemplateView = CreativeAssetsEditTemplateView;
      this.previewImageViewInstance = new PreviewImageView();
      this.listenTo(this.previewImageViewInstance, 'insert_image', this.insertImage);
      this.editTemplateViewInstance = null;
      this.lang_view = null;
      if (options && options.template_type) {
        this.template_type = options.template_type;
      }
      if (options && options.scope) {
        this.scope = options.scope;
      }
      if (options && options.languages) {
        this.languages = options.languages;
      }
      this.all_collection = new TemplatesCollection();
      this.all_collection.template_type = this.template_type;
      this.all_collection.scope = this.scope;
      this.favourite_collection = new TemplatesCollection();
      this.favourite_collection.favourite = true;
      this.favourite_collection.template_type = this.template_type;
      this.favourite_collection.scope = this.scope;
      this.search_collection = new TemplatesCollection();
      this.search_collection.template_type = this.template_type;
      this.search_collection.scope = this.scope;
      this.create_new_collection = new TemplatesCollection();
      this.create_new_collection.default_template = true;
      //defaults values
      this.collection = this.all_collection;
      this.template_container = '.ca_all_container_body';
      this.view_type = 'all';
      this.is_searching = false;
      this.listenTo(this.all_collection, 'add', this.addTemplateview);
      this.listenTo(this.favourite_collection, 'add', this.addTemplateview);
      this.listenTo(this.search_collection, 'add', this.addTemplateview);
      this.listenTo(this.create_new_collection, 'add', this.addCreateNewTemplateView);
      this.listenTo(this.all_collection, 'reset', this.removeTemplateview);
      this.listenTo(this.favourite_collection, 'reset', this.removeTemplateview);
      this.listenTo(this.search_collection, 'reset', this.removeTemplateview);
    },
    render: function() {
      this.$el.html(this.tpl({
        template_type: this.template_type,
        template_scope: this.scope,
        scopes_available: ca_ref.scopesAvailable
      }));
      if (this.collection.length < this.collection.default_limit) this.addTemplates();
      var self = this;
      var height = $(window).height() - ca_ref.container.offset().top - 65;
      this.$('.ca_container_body').css("max-height", height);
      this.$('.ca_container_body').on('scroll', function() {
        self.checkScroll(this);
      });
      $(window).resize(function() {
        var height = $(window).height() - ca_ref.container.offset().top - 65;
        self.$('.ca_container_body').css("max-height", height);
      });
      if (this.template_type == 'image') {
        this.$("#image_upload").submit(function(e) {
          self.showAllLoader(true);
          var formObj = new FormData(this);
          var formURL = '/xaja/AjaxService/assets/upload_image.json';
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
                if (!data.error) {
                  var info = data.info;
                  self.addImage(info);
                  self.showFlash(_campaign("Image uploaded successfully"))
                } else {
                  self.showFlash(data.error, true)
                }
                self.showAllLoader(false);
              },
              error: function(jqXHR, textStatus) {
                ca.helper.showFlash(textStatus);
                self.showAllLoader(false);
              }
            });
            return false;
          }
        });
      }
      return this;
    },
    events: {
      'change select#template_scope': 'changeScope',
      'click .all_template': 'showAllTemplates',
      'click .favourite_template': 'showFavouriteTemplates',
      'keyup .ca_search': 'showSearchTemplates',
      'click .upload_html_file': 'showHtmlUpload',
      'change #file_upload': 'uploadFile',
      'click .upload_zip_file': 'showZipUpload',
      'click .create_from_scratch': 'createFromScratch',
      'click .upload_image_file': 'showImageUpload',
      'change #upload_image': 'uploadImage',
      'click .back_view': 'showView'
    },
    showFlash: function(msg, is_error) {
      if (is_error) {
        //console.log("hello");
        $('.Img_flash').addClass('redError').html(msg).show();
      } else {
        $('.Img_flash').removeClass('redError').html(msg).show();
      }
      setTimeout(function() {
        $('.Img_flash').fadeOut('fast', function() {
          $('.Img_flash').removeClass('redError');
        });
      }, 3000);
    },
    changeScope: function(e) {
      var type = $(e.currentTarget).val();
      this.initialize({
        scope: type
      });
      ca_ref.scope = type;
      this.render();
    },
    showAllTemplates: function() {
      this.view_type = 'all';
      this.$('.favourite_template').removeClass('sel');
      this.$('.all_template').addClass('sel');
      if (this.is_searching) {
        this.showSearchTemplates();
        return;
      }
      this.$('.ca_favourite_container_body').hide();
      this.$('.ca_search_container_body').hide();
      this.$('.ca_all_container_body').show();
      this.collection = this.all_collection;
      this.template_container = '.ca_all_container_body';
      if (this.collection.length < this.collection.default_limit) {
        this.addTemplates();
      }
    },
    showFavouriteTemplates: function() {
      this.view_type = 'favourite';
      this.$('.all_template').removeClass('sel');
      this.$('.favourite_template').addClass('sel');
      if (this.is_searching) {
        this.showSearchTemplates();
        return;
      }
      this.$('.ca_all_container_body').hide();
      this.$('.ca_search_container_body').hide();
      this.$('.ca_favourite_container_body').show();
      this.collection = this.favourite_collection;
      this.template_container = '.ca_favourite_container_body';
      this.collection.reset();
      var filtered_models = this.all_collection.filter(function(model) {
        return model.get('is_favourite');
      });
      this.collection.set(filtered_models);
      this.collection.completed = false;
      this.collection.start_limit = filtered_models.length;
      if (this.collection.length < this.collection.default_limit) {
        this.addTemplates();
      }
    },
    showSearchTemplates: function() {
      var self = this;
      clearTimeout(self.searchTemplate);
      var search_val = this.$('.ca_search').val();
      if (!search_val) {
        this.is_searching = false;
        if (this.view_type === 'all') {
          this.showAllTemplates();
        } else {
          this.showFavouriteTemplates();
        }
      } else {
        var t = 500;
        this.searchTemplate = setTimeout(function() {
          self.collection = self.search_collection;
          self.template_container = '.ca_search_container_body';
          if (self.view_type === 'all') {
            prev_collection = self.all_collection;
            self.collection.favourite = false;
          } else {
            prev_collection = self.favourite_collection;
            self.collection.favourite = true;
          }
          self.collection.reset();
          self.collection.search = search_val;
          self.$('.ca_all_container_body').hide();
          self.$('.ca_favourite_container_body').hide();
          self.$('.ca_search_container_body').show();
          self.is_searching = true;
          var filtered_models = prev_collection.filter(function(model) {
            return model.get('name').toLowerCase().indexOf(search_val.toLowerCase()) >= 0;
          });
          self.collection.set(filtered_models);
          self.collection.completed = false;
          self.collection.start_limit = filtered_models.length;
          if (self.collection.length < self.collection.default_limit) {
            self.addTemplates(true, search_val);
          }
        }, t);
      }
    },
    checkScroll: function(container) {
      var triggerPoint = 100; // 100px from the bottom
      if (!this.is_loading && (container.scrollTop + container.clientHeight + triggerPoint) > (container.scrollHeight)) {
        // Load next page
        this.addTemplates();
      }
    },
    addTemplates: function(is_search, search_val) {
      var self = this;
      self.collection.new_models = [];
      if (!this.collection.completed) {
        this.is_loading = true;
        this.showLoader(true);
        self.showComplete(false);
        this.collection.fetch({
          remove: false,
          success: function(collection, response) {
            if (is_search && search_val !== self.collection.search) {
              return;
            }
            if (self.template_type == 'image' && self.collection.start_limit == 0) {
              if (self.collection.length > 0) {
                self.previewImageViewInstance.setModel(self.collection.at(0)).setElement(self.$(".ca_image_preview_container_body")).render();
              } else {
                self.$(".ca_image_preview_container_body").empty();
              }
            }
            self.collection.start_limit = self.collection.length;
            self.is_loading = false;
            self.showLoader(false);
            if (self.collection.completed) {
              self.showComplete(true);
            } else {
              self.showComplete(false);
            }
          }
        });
      }
    },
    fetchDefaultTemplates: function() {
      var self = this;
      if (!this.create_new_collection.completed) {
        this.is_loading = true;
        this.showDefaultLoader(true);
        this.create_new_collection.fetch({
          remove: false,
          success: function() {
            self.is_loading = false;
            self.showDefaultLoader(false);
          }
        });
      }
    },
    showHtmlUpload: function() {
      this.$('#file_upload').trigger('click');
    },
    uploadFile: function() {
      var file = $('#file_upload')[0].files[0];
      if (typeof file == 'undefined') {
        ca.helper.showFlash(_campaign('please select file to upload!'), true);
        $('#file_upload').val('');
        return false;
      }
      if (!file.type.match('html.*') || !file.type.match('htm.*')) {
        ca.helper.showFlash(_campaign('Upload only html file'), true);
        $('#file_upload').val('');
        return false;
      } else {
        var reader = new FileReader();
        reader.readAsText(file, 'UTF-8');
        var self = this;
        reader.onload = (function(theFile) {
          return function(e) {
            var result = e.target.result;
            if (result) {
              self.editWithHtml(result);
            }
          }
        })(file);
        $('#file_upload').val('');
        return true;
      }
    },
    showZipUpload: function() {
      showPopup('/campaign/assets/ZipUpload?ref_id=-20&temp_id=0');
      var self = this;
      $('#popupiframe').unbind();
      $('#popupiframe').load(function() {
        var iframe = $('#popupiframe').contents();
        iframe.find('#upload_file').click(function() {
          iframe.find('#zip_upload_html').attr('action', '/xaja/AjaxService/assets/upload_using_iframe.json').attr('method', 'post').attr('enctype', 'multipart/form-data').attr('encoding', 'multipart/form-data').submit();
        });
        iframe.find('#zip_upload_html').submit(function(e) {
          $(".sdContainer #close").hide();
          iframe.find('#loading').removeClass('hide');
          iframe.find('.wait_message').show().addClass('indicator_1');
          var formObj = $(this);
          var formURL = formObj.attr('action');
          if (window.FormData !== undefined) {
            $('document').keyup(function(e) {
              if (e.keyCode == 27) e.preventDefault();
            });
            var formData = new FormData(this);
            $.ajax({
              url: formURL,
              type: 'POST',
              data: formData,
              mimeType: 'multipart/form-data',
              contentType: false,
              cache: false,
              processData: false,
              success: function(data, textStatus, jqXHR) {
                var data = JSON.parse(data);
                if (!data.error) {
                  var html = data.html_template;
                  $(".sdContainer #close").show().trigger('click');
                  self.editWithHtml(html);
                } else {
                  $(".sdContainer #close").show().trigger('click');
                  ca.helper.showFlash(data.error);
                }
              },
              error: function(jqXHR, textStatus, errorThrown) {
                $(".sdContainer #close").show().trigger('click');
                ca.helper.showFlash(textStatus);
              }
            });
            e.preventDefault();
          }
        });
      });
    },
    newLangLinkVisibility: function() {
      var flag = 0;
      $('.lang_option').each(function() {
        if ($(this).css('display') == 'none') {
          flag = 1;
        } else {
          flag = 0;
          return false;
        }
      });
      if (flag == 1) {
        $('#add_lang').css('display', 'none');
      } else {
        $('#add_lang').css('display', 'inline');
      }
    },
    createFromScratch: function(type, model) {
      this.unregisterAllTemplates();
      this.showCreateNew(model);
      this.fetchDefaultTemplates();
      //trigger template name model
      if (type != 'edit') {
        var timestamp = Date.now();
        var name = _campaign('Template') + '_' + timestamp;
        this.lang_view.showNewNameModal(name, type);
      }
      $('.lang_enabled_hide').hide();
      this.newLangLinkVisibility();
      if (ca_ref.scope == 'POINTSENGINE' || ca_ref.scope == 'REFERRAL') {
        $('.lang_enabled_show').hide();
        $('#lang_tab_parent').hide();
        $('#lang_content_scope').val(_campaign(ca_ref.scope));
        if (ca_ref.scope == 'POINTSENGINE') $('#lang_content_scope').val(_campaign('Points Engine'));
      }
    },
    showImageUpload: function() {
      if (this.template_type == 'image') this.$('#upload_image').trigger('click');
    },
    uploadImage: function() {
      if (this.template_type == 'image') {
        this.$("#image_upload").submit();
        this.$('#image_upload').val('');
      }
    },
    addImage: function(info) {
      if (typeof info == "string") {
        info = JSON.parse(info);
      }
      var model = new TemplateModel(info);
      this.all_collection.add(model, {
        at: 0,
        silent: true
      });
      var view = this.createTemplateView(model);
      this.$('.ca_all_container_body').prepend(this.getHtml(view));
      model.trigger('selected');
    },
    showAllLoader: function(show) {
      if (show) this.$('.all_wait_loader').addClass('intouch-loader').show();
      else this.$('.all_wait_loader').removeClass('intouch-loader').hide();
    },
    showLoader: function(show) {
      if (show) {
        this.$('.ca_loader').addClass('intouch-loader').show().parent('div').show();
      } else {
        this.$('.ca_loader').removeClass('intouch-loader').hide().parent('div').hide();
      }
    },
    showDefaultLoader: function(show) {
      if (show) {
        this.$('.ca_default_loader').addClass('intouch-loader').show().parent('div').show();
      } else {
        this.$('.ca_default_loader').removeClass('intouch-loader').hide().parent('div').hide();
      }
    },
    showComplete: function(show) {
      if (show) {
        this.$('.ca_complete_msg').show();
      } else {
        this.$('.ca_complete_msg').hide();
      }
    },
    addTemplateview: function(model) {
      var view = this.createTemplateView(model);
      this.$(this.template_container).append(this.getHtml(view));
    },
    createTemplateView: function(model) {
      var view = new this.templateView({
        model: model
      });
      if (this.template_type == 'image') {
        this.listenTo(view, 'preview_image', this.previewImage);
      } else {
        this.listenTo(view, 'edit_template', this.editMultiLangTemplate);
        this.listenTo(view, 'duplicate_template', this.duplicateMultiLangTemplate);
      }
      return view;
    },
    duplicateMultiLangTemplate: function(model, type) {
      var url = '/xaja/AjaxService/assets/get_multi_lang_template.json?parent_template_id=' + model.get('template_id') + '&scope=' + this.scope;
      var lang_ids = [];
      var deferred_arr = [];
      deferred_arr.push($.Deferred());
      var duplicate_model_arr = [];
      var self = this;
      self.createFromScratch('duplicate');
      model.set('language_id', ca_ref.languages.base_language_id);
      duplicate_model_arr.push(model.duplicateModel(deferred_arr[0]));
      $.getJSON(url, function(data) {
        if (data.templates) {
          var i = 1;
          $.each(data.templates, function(key, val) {
            console.log("inside the data templates loop");
            tempModel = new TemplateModel(val);
            console.log("value is : ");
            console.log(val);
            lang_ids.push(val.language_id);
            tempModel.set('language_id', val.language_id);
            deferred_arr.push($.Deferred());
            duplicate_model_arr.push(tempModel.duplicateModel(deferred_arr[i]));
            i++;
          });
        }
        $.when.apply($, deferred_arr).done(function() {
          console.log("succesfully resolved, duplicate model is : ");
          console.log(duplicate_model_arr);
          self.addLanguage();
          for (var key in duplicate_model_arr) {
            language_id = duplicate_model_arr[key].get('language_id');
            self.editTemplate(duplicate_model_arr[key], type, language_id, true);
            self.lang_view.langBasedEditTemplates[language_id].$("#edit_name_modal").modal('hide');
          }
          self.lang_view.editLanguageTemplate(lang_ids);
          $("#lang_content_parent").show();
          $("#new_template_header").hide();
          $("#create_new_template_parent").hide();
          $(".create_new_template").hide();
          $(".template_editor[editor_lang_id=" + ca_ref.languages.base_language_id + "]").show();
          $(".lang_tab").hide();
          $(".lang_tab[lang_id=" + ca_ref.languages.base_language_id + "],[lang_id=" + lang_ids.join("],[lang_id=") + "]").show();
          $(".lang_option[option_lang_id=" + ca_ref.languages.base_language_id + "],[option_lang_id=" + lang_ids.join("],[option_lang_id=") + "]").hide();
        }).fail(function() {
          ca.helper.showFlash(_campaign("Failed to save primary template"), true);
        });
      });
    },
    unregisterAllTemplates: function() {
      if (this.lang_view != null) {
        for (var lang_id in this.lang_view.langBasedEditTemplates) {
          if (this.lang_view.langBasedEditTemplates[lang_id] != undefined && this.lang_view.langBasedEditTemplates[lang_id]) {
            this.lang_view.langBasedEditTemplates[lang_id].stopListening();
            this.lang_view.langBasedEditTemplates[lang_id].undelegateEvents();
            this.lang_view.langBasedEditTemplates[lang_id].$el.empty().off();
          }
        }
        this.lang_view.langBasedEditTemplates = [];
      }
    },
    editMultiLangTemplate: function(model, type) {
      //this.unregisterAllTemplates() ;
      this.addLanguage();
      this.createFromScratch('edit', model);
      this.editTemplate(model, type, ca_ref.languages.base_language_id);
      if (ca_ref.scope == 'POINTSENGINE' || ca_ref.scope == 'REFERRAL') {
        $('.lang_enabled_show').hide();
        $('#lang_tab_parent').hide();
        $('#lang_content_scope').val(_campaign(ca_ref.scope));
        if (ca_ref.scope == 'POINTSENGINE') $('#lang_content_scope').val(_campaign('Points Engine'));
      }
      var name = model.get('name');
      $("#lang_edit_template_name").html(name);
      var self = this;
      var url = '/xaja/AjaxService/assets/get_multi_lang_template.json?parent_template_id=' + model.get('template_id') + '&scope=' + this.scope;
      var lang_ids = [];
      $.getJSON(url, function(data) {
        if (data.templates) {
          $.each(data.templates, function(key, val) {
            tempModel = new TemplateModel(val);
            lang_ids.push(val.language_id);
            tempModel.set('language_id', val.language_id);
            self.editTemplate(tempModel, type, val.language_id);
          });
          self.lang_view.editLanguageTemplate(lang_ids);
          $("#lang_content_parent").show();
          $("#new_template_header").hide();
          $("#create_new_template_parent").hide();
          $(".create_new_template").hide();
          $(".template_editor[editor_lang_id=" + ca_ref.languages.base_language_id + "]").show();
          $(".lang_tab").hide();
          $(".lang_tab[lang_id=" + ca_ref.languages.base_language_id + "],[lang_id=" + lang_ids.join("],[lang_id=") + "]").show();
          $(".lang_option[option_lang_id=" + ca_ref.languages.base_language_id + "],[option_lang_id=" + lang_ids.join("],[option_lang_id=") + "]").hide();
        }
        //self.newLangLinkVisibility() ;
      });
    },
    addCreateNewTemplateView: function(model) {
      var view = this.createNewTemplateView(model);
      this.$('.create_new_template').append(this.getHtml(view));
    },
    createNewTemplateView: function(model) {
      var timestamp = Date.now();
      var name = _campaign('Template') + '_' + timestamp;
      model.set('name', name);
      var view = new CreateTemplateView({
        model: model
      });
      this.listenTo(view, 'edit_selected_template', this.editTemplate);
      return view;
    },
    previewImage: function(model) {
      this.previewImageViewInstance.setModel(model).setElement(this.$(".ca_image_preview_container_body")).render();
    },
    getHtml: function(view) {
      return view.render().el;
    },
    removeTemplateview: function() {
      this.$(this.template_container).empty();
    },
    addLanguage: function(model) {
      var base_lang_id = this.languages.base_language_id;
      console.log("add language : ");
      console.log(this.languages);
      is_favourite = false;
      name = null;
      if (model) {
        if (model.get('is_favourite')) is_favourite = model.get('is_favourite');
        if (model.get('name')) name = model.get('name');
      }
      if (this.lang_view == null) {
        this.lang_view = new LanguageBasedTemplate({
          el: $("#ca_lang_based_parent_container"),
          languages: this.languages,
          is_favourite: is_favourite,
          template_name: name
        });
        this.lang_view.render();
      } else {
        $("#ca_lang_based_parent_container").show();
        $("#lang_content_parent").hide();
        $(".template_editor").hide();
        $("#lang_create_new_container").show();
        $(".create_new_template").hide();
        $("#create_new_template_parent").show();
        $(".new_template_header").show();
        $(".lang_tab").hide();
        var base_language_id = this.lang_view.languages.base_language_id;
        $(".lang_tab[lang_id=" + base_language_id + "]").show();
        $(".create_new_template[template_lang_id=" + base_language_id + "]").show();
        this.lang_view.setIsFavourite(is_favourite);
        this.lang_view.setTemplateName(name);
      }
    },
    showCreateNew: function(model) {
      this.$('.ca_top_view_container').hide();
      this.addLanguage(model);
    },
    showView: function() {
      this.$('.ca_top_edit_container').hide();
      this.$('.ca_top_create_new_container').hide();
      this.$('.ca_top_view_container').show();
      //parent view of create and edit hide
      this.$("#ca_lang_based_parent_container").hide();
      //editor view hide
      this.$("#lang_content_parent").hide();
      this.$(".template_editor").hide();
      //create new template hide
      this.$("#create_new_template_parent").hide();
      this.$("#create_new_template").hide();
      if (this.lang_view != null) {
        this.lang_view.langBasedEditTemplates = [];
        var remove_lang = [];
        var base_lang_id = this.lang_view.languages.base_language_id;
        for (var key in this.lang_view.added_languages) {
          if (base_lang_id == this.lang_view.added_languages[key].lang_id) continue;
          remove_lang.push(this.lang_view.added_languages[key].lang_id);
        }
        $('.lang_tab[lang_id="' + remove_lang.join('"],[lang_id="') + '"]').remove();
        this.lang_view.added_languages = [];
        this.lang_view.added_languages[0] = {};
        this.lang_view.added_languages[0].lang_id = base_lang_id;
        this.lang_view.added_languages[0].lang_name = this.lang_view.languages.all_lang[base_lang_id];
        $(".lang_option").show();
        $(".lang_option[option_lang_id=" + base_lang_id + "]").hide();
      }
    },
    showEdit: function(lang_id) {
      //this.$('.ca_top_create_new_container').hide();
      this.$('.ca_top_view_container').hide();
      this.$(".template_editor").hide();
      this.$(".template_editor[editor_lang_id=" + lang_id + "]").show();
      //this.$('.ca_top_edit_container').show();
    },
    editWithHtml: function(html) {
      var model = new TemplateModel();
      var timestamp = Date.now();
      var default_name = _campaign('Template') + '_' + timestamp;
      model.set({
        name: default_name,
        html_content: html
      });
      lang_id = $(".selected_lang_tab").attr("lang_id");
      this.editTemplate(model, 'new', lang_id);
    },
    editTemplate: function(model, type, lang_id, is_multi_duplicate) {
      console.log('12hello');
      if (lang_id == undefined || lang_id == null) {
        console.log("No lang_id passed");
        return;
      }
      if (this.lang_view != null) {
        if (this.lang_view.langBasedEditTemplates[lang_id] != undefined && this.lang_view.langBasedEditTemplates[lang_id]) {
          this.lang_view.langBasedEditTemplates[lang_id].stopListening();
          this.lang_view.langBasedEditTemplates[lang_id].undelegateEvents();
          this.lang_view.langBasedEditTemplates[lang_id].$el.empty().off();
        }
      }
      this.showEdit(lang_id);
      $(".create_new_template").hide();
      $(".new_template_header").hide();
      $('#create_new_template_parent').hide();
      $(".template_editor[editor_lang_id=" + lang_id + "]").show();
      $("#lang_content_parent").show();
      model.language_id = lang_id;
      var self = this;
      this.lang_view.langBasedEditTemplates[lang_id] = new this.editTemplateView({
        model: model,
        el: this.$(".template_editor[editor_lang_id=" + lang_id + "]"),
        edit_type: type,
        scope: this.scope,
        scopes_available: ca_ref.scopesAvailable,
        is_multi_duplicate: is_multi_duplicate
      });
      this.lang_view.langBasedEditTemplates[lang_id].render();
      this.lang_view.langBasedEditTemplates[lang_id].$(".lang_enabled_hide").hide();
      this.lang_view.langBasedEditTemplates[lang_id].$(".lang_enabled_show").show();
      if (this.lang_view.langBasedEditTemplates[lang_id].editor_name == 'inline_ck') {
        this.lang_view.langBasedEditTemplates[lang_id].$(".edit_as_html").hide();
        this.lang_view.langBasedEditTemplates[lang_id].$(".change_to_classic_editor").removeClass("hide");
      } else {
        this.lang_view.langBasedEditTemplates[lang_id].$(".edit_as_html").show();
        this.lang_view.langBasedEditTemplates[lang_id].$(".change_to_classic_editor").addClass("hide");
      }
      if (ca_ref.scope == 'REFERRAL' || ca_ref.scope == 'POINTSENGINE') {
        this.lang_view.langBasedEditTemplates[lang_id].$('.remove_language_list').hide();
        this.lang_view.langBasedEditTemplates[lang_id].$('.template_options_text').hide();
      } else if (lang_id == ca_ref.languages.base_language_id) {
        this.lang_view.langBasedEditTemplates[lang_id].$('.remove_language_list').hide();
        this.lang_view.langBasedEditTemplates[lang_id].$('.template_options_text').show();
        this.lang_view.langBasedEditTemplates[lang_id].$('.template_options_text').text(_campaign("Main Language - Sent as an Email"));
      } else {
        this.lang_view.langBasedEditTemplates[lang_id].$('.remove_language_list').show();
        this.lang_view.langBasedEditTemplates[lang_id].$('.template_options_text').show();
        this.lang_view.langBasedEditTemplates[lang_id].$('.template_options_text').text(_campaign("Sec. Language - Stored as an URL"));
      }
    },
    destroyViews: function() {
      _.invoke(this.views, 'destroy');
      this.views.length = 0;
    },
    renderAllTemplates: function() {
      var self = this;
      this.collection.fetch({
        remove: false,
        success: function(collection, response) {
          self.views = self.collection.map(self.createTemplateView, self);
          self.$(self.template_container).empty();
          self.$(self.template_container).append(_.map(self.views, self.getHtml, self));
          self.is_loading = false;
          self.collection.start_limit = self.collection.length;
        }
      });
    },
    setTemplateView: function(tv) {
      this.templateView = tv;
    },
    setEditTemplateView: function(tv) {
      this.editTemplateView = tv;
    },
    insertImage: function(url) {
      this.trigger('insert_image', url)
    }
  });
  var EditorModel = ca.EditorModel = Backbone.Model.extend({
    defaults: function() {
      return {
        supported_tags: [],
        template_model: null
      };
    }
  });
  var CreativeAssetsEditTemplateView = ca.CreativeAssetsEditTemplateView = Backbone.View.extend({
    tpl: _.template($('#ca_edit_template_tpl').html()),
    initialize: function(options) {
      this.image_gallery_view = new ImageGalleryView();
      this.listenTo(this.image_gallery_view, 'insert_image', this.insertImage);
      this.option_set = {
        inline_ck: [{
          className: 'preview',
          label: _campaign('Preview')
        }, {
          className: 'change_to_classic_editor',
          label: _campaign('Change to Classic Editor')
        }, {
          className: 'image_gallery',
          label: _campaign('Insert Image')
        }, {
          className: 'delete_template',
          label: _campaign('Delete')
        }],
        classic_ck: [{
          className: 'preview',
          label: _campaign('Preview')
        }, {
          className: 'change_to_inline_editor',
          label: _campaign('Change to Inline Editor')
        }, {
          className: 'image_gallery',
          label: _campaign('Insert Image')
        }, {
          className: 'delete_template',
          label: _campaign('Delete')
        }],
        edm: [{
          className: 'preview',
          label: _campaign('Preview')
        }, {
          className: 'edit_as_html',
          label: _campaign('Edit as HTML')
        }, {
          className: 'image_gallery',
          label: _campaign('Insert Image')
        }, {
          className: 'delete_template',
          label: _campaign('Delete')
        }],
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
          }]
        }
      };
      this.edit_type = options.edit_type;
      this.scope = options.scope;
      this.start = true;
      this.is_multi_duplicate = false;
      if (options.is_multi_duplicate) this.is_multi_duplicate = options.is_multi_duplicate;
      var drag_drop_id = this.model.get('drag_drop_id');
      var is_drag_drop = this.model.get('is_drag_drop');
      if (options.editor_name) {
        this.editor_name = options.editor_name;
      } else {
        if (this.scope == 'EBILL') {
          this.editor_name = 'text';
        } else if (is_drag_drop && drag_drop_id) {
          this.editor_name = 'edm';
        } else {
          this.editor_name = 'inline_ck';
        }
      }
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
      this.$el.html(this.tpl({
        options: editor_options_set,
        model: this.model.toJSON(),
        scope: this.scope,
        scopes_available: ca_ref.scopesAvailable
      }));
      if (this.start) {
        if (this.edit_type == 'new') {
          if (!ca_ref.languages) this.showNewNameModal();
        } else if (this.edit_type == 'duplicate' && !this.is_multi_duplicate) {
          this.openEditNameModel();
        }
        this.start = false;
      }
      this.renderEditor();
      return this;
    },
    events: {
      'click #save_name_scope': 'saveNameScope',
      'click .back_to_view': 'backToView',
      'click .ca_edit_favourite_icon': 'favouriteTemplate',
      'click #edit_name': 'openEditNameModel',
      'click #change_name': 'changeTemplateName',
      'click .save_new_template': 'saveNewTemplate',
      'click .preview': 'showPreview',
      'click .edit_as_html': 'showConfirmEdit',
      'change .confirm_box': 'enableContinue',
      'click .confirm_edit': 'editAsHtml',
      'click .change_to_classic_editor': 'changeToClassicEditor',
      'click .change_to_inline_editor': 'changeToInlineEditor',
      'click .image_gallery': 'openImageGallery',
      'click .preview_template': 'previewTemplate',
      'click .delete_template': 'deleteTemplate'
    },
    showNewNameModal: function() {
      this.$("#new_name_modal").modal();
    },
    saveNameScope: function() {
      var name = this.$("#new_template_name").val();
      var scope = this.$("#new_template_scope").val();
      var name = name && name.trim();
      if (!name) return false;
      this.model.set({
        name: name,
        scope: scope
      });
      this.renderNewName(name);
      this.$('#new_name_modal').modal('hide');
      if (scope != this.scope) {
        if (scope == 'EBILL') {
          this.editor_name = 'text';
          this.scope = scope;
          this.render();
        } else if (this.scope == 'EBILL') {
          this.editor_name = 'inline_ck';
          this.scope = scope;
          this.render();
        } else {
          this.scope = scope;
          this.renderEditor();
        }
      }
    },
    renderNewName: function(name) {
      this.$("#edit_template_name").html(name);
    },
    backToView: function() {
      if (window.confirm(_campaign("You will lose all progress made, Do you want to continue"))) {
        this.model.set('html_content', '');
        this.$el.hide();
        this.$el.siblings('.ca_top_view_container').show();
      }
    },
    favouriteTemplate: function(e) {
      this.model.toggleFavourite();
      $(e.currentTarget).children('i').toggleClass('icon-heart-empty').toggleClass('icon-heart');
    },
    openEditNameModel: function() {
      this.$("#rename_template").val(this.model.get('name'));
      this.$("#edit_name_modal").modal();
    },
    changeTemplateName: function() {
      var name = this.$("#rename_template").val();
      name = name && name.trim();
      if (!name) return false;
      this.model.set('name', name);
      this.renderNewName(name);
      this.$('#edit_name_modal').modal('hide');
    },
    saveNewTemplate: function() {
      var self = this;
      this.showEditLoader(true);
      this.model.saveTemplate().done(function() {
        self.showEditLoader(false);
      });
    },
    showPreview: function() {
      var self = this;
      var preview_view = new TemplatePreviewView({
        model: this.model
      });
      preview_view.setElement(this.$('.preview_container')).render();
      self.$("#edit_template_preview_modal").modal('show');
    },
    showConfirmEdit: function() {
      this.$('.confirm_edit_modal').modal('show');
    },
    enableContinue: function(e) {
      this.$('.confirm_edit').attr("disabled", !$(e.currentTarget).attr("checked"));
    },
    editAsHtml: function() {
      var drag_drop_id = this.model.get('drag_drop_id');
      var self = this;
      var status = this.model.setHtml();
      this.showEditLoader(true);
      status.done(function() {
        self.showEditLoader(false);
        self.model.set({
          is_drag_drop: false,
          drag_drop_id: ''
        });
        self.editor_name = 'inline_ck';
        self.render();
        self.$('.change_to_classic_editor').first().removeClass('hide');
        self.$('.edit_as_html').first().addClass('hide');
        selected_lang_id = self.$('.selected_lang_tab').attr('lang_id');
        $('.lang_enabled_hide').hide();
        $('.lang_enabled_show').show();
        if (ca_ref.languages.base_language_id == selected_lang_id) {
          self.$('.remove_language_list').hide();
          self.$('.template_options_text').text(_campaign("Main Language - Sent as an Email"));
        } else {
          self.$('.remove_language_list').show();
          self.$('.template_options_text').text(_campaign("Sec. Language - Stored as URL"));
        }
      })
    },
    changeToClassicEditor: function() {
      var self = this;
      this.editor_name = 'classic_ck';
      this.render();
      selected_lang_id = $('.selected_lang_tab').attr('lang_id');
      $('.lang_enabled_hide').hide();
      $('.lang_enabled_show').show();
      self.$('.change_to_inline_editor').first().removeClass('hide');
      self.$('.edit_as_html').first().addClass('hide');
      //self.$(e.currentTarget).parent().find('.change_to_classic_editor').removeClass('hide');
      //self.$(e.currentTarget).parent().find('.edit_as_html').addClass('hide');
      if (ca_ref.languages.base_language_id == selected_lang_id) {
        self.$('.remove_language_list').hide();
        self.$('.template_options_text').text(_campaign("Main Language - Sent as an Email"));
      } else {
        self.$('.remove_language_list').show();
        self.$('.template_options_text').text(_campaign("Sec. Language - Stored as URL"));
      }
    },
    changeToInlineEditor: function() {
      var self = this;
      this.editor_name = 'inline_ck';
      this.render();
      selected_lang_id = $('.selected_lang_tab').attr('lang_id');
      $('.lang_enabled_hide').hide();
      $('.lang_enabled_show').show();
      self.$('.change_to_classic_editor').first().removeClass('hide');
      self.$('.edit_as_html').first().addClass('hide');
      //self.$(e.currentTarget).parent().find('.change_to_classic_editor').removeClass('hide');
      //self.$(e.currentTarget).parent().find('.edit_as_html').addClass('hide');
      if (ca_ref.languages.base_language_id == selected_lang_id) {
        self.$('.remove_language_list').hide();
        self.$('.template_options_text').text(_campaign("Main Language - Sent as an Email"));
      } else {
        self.$('.remove_language_list').show();
        self.$('.template_options_text').text(_campaign("Sec. Language - Stored as URL"));
      }
    },
    openImageGallery: function() {
      this.image_gallery_view.setElement($('.image_gallery_container')).render();
      $('#image_gallery_modal').modal('show');
    },
    deleteTemplate: function() {
      this.model.deleteTemplate();
      if (this.model.get('template_type') == 'wechat') {
        ca_ref.renderWeChat();
      } else {
        ca_ref.render();
      }
    },
    renderEditor: function() {
      var changeTags = function(tags) {
        return _.map(tags, function(val, key) {
          if (typeof val == "string") {
            return {
              name: key,
              val: val
            }
          } else if (typeof val == "object") {
            return {
              name: key,
              children: changeTags(val)
            }
          }
        })
      };
      var processed_tags = changeTags(ca_ref.tags[ca_ref.scope]);
      var editor_model = new EditorModel({
        supported_tags: processed_tags,
        template_model: this.model
      });
      if (this.editTemplateView) {
        this.editTemplateView.$el.empty().off();
        this.editTemplateView.stopListening();
        this.editTemplateView.undelegateEvents();
      }
      this.editTemplateView = new EditTemplateView({
        model: editor_model,
        editor_name: this.editor_name
      });
      this.listenTo(this.editTemplateView, 'show_loader', this.showEditLoader);
      this.editTemplateView.setElement(this.$('.ca_edit_template_container')).render();
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
    },
    insertImage: function(url) {
      this.editTemplateView.insertImage(url);
    }
  });
  var EditTemplateView = ca.EditTemplateView = Backbone.View.extend({
    editor_name: 'edm',
    tpl: _.template($('#edit_template_tpl').html()),
    insert_focus: 'editor',
    initialize: function(options) {
      this.template_model = this.model.get('template_model');
      this.editor_name = options.editor_name;
    },
    render: function() {
      this.$el.html(this.tpl(this.model.toJSON()));
      this.setEditor(this.editor_name);
      html = this.template_model.get('html_content');
      this.addColorToSubscribe();
      this.setHtmlToIframe(html);
      this.assignDiv();
    },
    events: {
      'click .tags-container li.parent > span': 'openSubTag',
      'click .insert_tag': 'insertTagToEditor'
    },
    openSubTag: function(e) {
      var self = this;
      self.$("li.active").each(function() {
        if (!($("li.active").is(self.$(e.currentTarget).parent().parent().parent()))) {
          if (!($("li.active").is(self.$(e.currentTarget).parent()))) {
            self.$("li.active").children('ul').slideToggle('fast');
            self.$("li.active").removeClass("active");
            if (self.$(e.currentTarget).text() == _campaign('Link to other Language')) {
              self.$(e.currentTarget).parent().find('ul li').remove();
            }
          }
        }
      });
      var arr = [];
      $('.template_editor').find('.ca-edit-left-panel').parent().parent().each(function() {
        arr.push($(this).attr('editor_lang_id'))
      });
      //$('.template_editor').find('.ca-edit-left-panel')
      self.$(e.currentTarget).parent().toggleClass('active');
      if (self.$(e.currentTarget).text() == _campaign('Link to other Language')) {
        if (self.$(e.currentTarget).parent().attr('class') == 'parent active') {
          $('#lang_list li').each(function() {
            var some = this;
            $(arr).each(function(i, l) {
              if (l == $(some).attr('lang_id')) {
                self.$(e.currentTarget).parent().find('ul').append("<li class = 'tag-list'><span><a class='insert_tag' tag-data='{{Link_to_" + $(some).text() + "}}'>" + $(some).text() + "</a></span></li>");
              }
            });
          });
        } else {
          self.$(e.currentTarget).parent().find('ul li').remove();
        }
      }
      self.$(e.currentTarget).parent().children('ul').slideToggle('fast');
    },
    insertTagToEditor: function(e) {
      var tag = $(e.currentTarget).attr('tag-data');
      var parent_element = $(e.currentTarget).parents(".template_editor");
      var bits = tag.replace('{{', '');
      var self = this;
      if (bits.indexOf("Link_to_") != -1) {
        //var jqPromptEl = $.prompt( "msg" , "options" );
        var removeBraces = tag.replace('{{', '');
        var defaultText = removeBraces.replace('}}', '');
        var linkContent = prompt(_campaign("Provide language link"), defaultText);
        if (linkContent != null) {
          tag = "<a href='" + tag + "'>" + linkContent + "</a>";
        } else {
          tag = "";
        }
      }
      if (this.insert_focus = 'editor') this.editorView.insertTag(tag, parent_element);
    },
    addColorToSubscribe: function() {
      var updateColorTag = function(container_id, tag) {
        $(container_id).spectrum({
          color: "#0000EE",
          flat: false,
          showInput: true,
          className: "full-spectrum",
          showInitial: true,
          showPalette: true,
          showSelectionPalette: true,
          maxPaletteSize: 10,
          preferredFormat: "hex",
          localStorageKey: "spectrum.demo",
          change: function(color) {
            $('#sp-unsub-colorpicker-div').css('background-color', color.toHexString());
            tag.attr('tag-data', '{{unsubscribe(' + color.toHexString() + ')}}');
          },
          palette: [
            ["rgb(0, 0, 0)", "rgb(67, 67, 67)", "rgb(102, 102, 102)", "rgb(204, 204, 204)", "rgb(217, 217, 217)", "rgb(255, 255, 255)"],
            ["rgb(152, 0, 0)", "rgb(255, 0, 0)", "rgb(255, 153, 0)", "rgb(255, 255, 0)", "rgb(0, 255, 0)", "rgb(0, 255, 255)", "rgb(74, 134, 232)", "rgb(0, 0, 255)", "rgb(153, 0, 255)", "rgb(255, 0, 255)"],
            ["rgb(230, 184, 175)", "rgb(244, 204, 204)", "rgb(252, 229, 205)", "rgb(255, 242, 204)", "rgb(217, 234, 211)", "rgb(208, 224, 227)", "rgb(201, 218, 248)", "rgb(207, 226, 243)", "rgb(217, 210, 233)", "rgb(234, 209, 220)", "rgb(221, 126, 107)", "rgb(234, 153, 153)", "rgb(249, 203, 156)", "rgb(255, 229, 153)", "rgb(182, 215, 168)", "rgb(162, 196, 201)", "rgb(164, 194, 244)", "rgb(159, 197, 232)", "rgb(180, 167, 214)", "rgb(213, 166, 189)", "rgb(204, 65, 37)", "rgb(224, 102, 102)", "rgb(246, 178, 107)", "rgb(255, 217, 102)", "rgb(147, 196, 125)", "rgb(118, 165, 175)", "rgb(109, 158, 235)", "rgb(111, 168, 220)", "rgb(142, 124, 195)", "rgb(194, 123, 160)", "rgb(166, 28, 0)", "rgb(204, 0, 0)", "rgb(230, 145, 56)", "rgb(241, 194, 50)", "rgb(106, 168, 79)", "rgb(69, 129, 142)", "rgb(60, 120, 216)", "rgb(61, 133, 198)", "rgb(103, 78, 167)", "rgb(166, 77, 121)", "rgb(91, 15, 0)", "rgb(102, 0, 0)", "rgb(120, 63, 4)", "rgb(127, 96, 0)", "rgb(39, 78, 19)", "rgb(12, 52, 61)", "rgb(28, 69, 135)", "rgb(7, 55, 99)", "rgb(32, 18, 77)", "rgb(76, 17, 48)"]
          ]
        });
      };
      $('.insert_tag').each(function() {
        if ($(this).attr('tag-data').indexOf('{{unsubscribe(') != -1) {
          $(this).append('<div class="unsub-colorpicker">' + '<div id="sp-unsub-colorpicker-div" class="sp-colorpicker-div"></div>' + '<input type="text" id="unsub-colorpicker-input">' + '</div>');
          updateColorTag('#unsub-colorpicker-input', $(this))
        }
        if ($(this).attr('tag-data').indexOf('{{subscribe(') != -1) {
          $(this).append('<div class="unsub-colorpicker">' + '<div id="sp-sub-colorpicker-div" class="sp-colorpicker-div"></div>' + '<input type="text" id="sub-colorpicker-input">' + '</div>');
          updateColorTag('#sub-colorpicker-input', $(this));
        }
      });
    },
    setHtmlToIframe: function(html_content, selector) {
      if (this.template_model.get('scope') != 'WECHAT') {
        var iframe = this.$("#iedit_template__template_holder");
        if (selector) {
          iframe.contents().find(selector).html(html_content);
        } else {
          var idoc = iframe[0].contentDocument;
          idoc.open();
          idoc.write(html_content);
          idoc.close();
        }
        html = '<html>' + iframe.contents().find('html').html() + '</html>';
        this.template_model.set('html_content', html);
      } else {
        this.template_model.set('html_content', html_content);
      }
    },
    assignDiv: function() {
      var count = 1;
      var divs = this.$("#iedit_template__template_holder").contents().find('.cap-email-block');
      divs.each(function() {
        $(this).attr('id', 'div_' + count);
        count++;
      })
    },
    showEditLoader: function(show) {
      this.trigger('show_loader', show);
    },
    setEditor: function(name) {
      var self = this;
      var template_model = this.model.get('template_model');
      this.showEditLoader(true);
      if (this.editorView) {
        this.editorView.stopListening();
        this.editorView.undelegateEvents();
        this.editorView.$el.empty().off();
      }
      if (name == 'text') {
        template_model.setHtml().done(function() {
          self.showEditLoader(false);
          self.editorView = new TextEditorView({
            model: template_model
          });
          self.editorView.setElement(self.$('.ca-edit-right-panel')).render();
          self.listenTo(self.editorView, 'set_html', self.setHtmlToIframe);
        });
      } else if (name == 'inline_ck') {
        template_model.setHtml().done(function() {
          self.showEditLoader(false);
          self.editorView = new InlineCKEditorView({
            model: template_model
          });
          self.editorView.setElement(self.$('.ca-edit-right-panel')).render();
          self.listenTo(self.editorView, 'set_html', self.setHtmlToIframe);
        })
      } else if (name == 'classic_ck') {
        template_model.setHtml().done(function() {
          self.showEditLoader(false);
          self.editorView = new ClassicCKEditorView({
            model: template_model
          });
          self.editorView.setElement(self.$('.ca-edit-right-panel')).render();
          self.listenTo(self.editorView, 'set_html', self.setHtmlToIframe)
        });
      } else {
        this.editorView = new EdmEditorView({
          model: template_model
        });
        this.editorView.setElement(this.$('.ca-edit-right-panel')).render();
        this.showEditLoader(false);
      }
    },
    insertImage: function(url) {
      this.editorView.insertImage(url);
    }
  });
  var ClassicCKEditorView = ca.ClassicCKEditorView = Backbone.View.extend({
    tpl: _.template($('#classic_ck_editor_tpl').html()),
    initialize: function() {
      this.editor = undefined;
    },
    setModel: function(model) {
      this.model = model;
    },
    render: function() {
      var self = this;
      this.language_id = this.model.get('language_id');
      var element = 'edit_template__template_' + this.language_id;
      this.$el.html(this.tpl());
      if (this.$('.edit_template__template').attr('id') == undefined) {
        this.$('.edit_template__template').attr('id', 'edit_template__template_' + self.language_id);
      }
      this.editor = CKEDITOR.replace(element, {
        'width': '99%',
        'height': '310px',
        'fullPage': true
      });
      html = this.model.get('html_content');
      this.editor.on('instanceReady', function(evt) {
        CKEDITOR.instances['edit_template__template_' + self.language_id].setData(html);
      });
      this.editor.on('change', function(ev) {
        self.model.set('html_content', this.getData());
        self.trigger('set_html', this.getData());
      });
      this.editor.on('focus', function(evt) {});
      this.editor.on('mode', function(evt) {
        var mode = self.editor.mode;
        if (mode == "source") {
          self.mode = "source";
        }
        if (self.mode == "source" && mode == "wysiwyg") {
          self.trigger('set_html', this.getData());
          self.model.set('html_content', this.getData());
          self.mode = '';
        }
      });
    },
    insertTag: function(tag) {
      this.editor.insertHtml(tag);
    },
    insertImage: function(url) {
      var img = '<img src="' + url + '"/>';
      this.editor.insertHtml(img);
    }
  });
  var InlineCKEditorView = ca.InlineCKEditorView = Backbone.View.extend({
    tpl: _.template($('#inline_ck_editor_tpl').html()),
    initialize: function() {
      var self = this;
      this.edit_mode = 'single';
      //this.tpl = _.template($('#inline_ck_editor_tpl').html());
      this.iframe_jquery = function(selector) {
        return this.$("#iedit_template__template_" + self.language_id).contents().find(selector);
      };
    },
    render: function() {
      var self = this;
      var getRef = function() {
        window.setTimeout(function() {
          self.ckeditor_iframe = this.$("#iedit_template__template_" + self.language_id)[0].contentWindow.CKEDITOR;
          if (self.edit_mode == 'single') {
            self.iframe_jquery("#ca_template_editor_" + self.language_id).bind('click', function() {
              self.editSingleDiv();
            });
            self.iframe_jquery("#source_" + self.language_id).bind("dblclick", function() {
              console.log(this);
              //self.ckeditor_iframe.instances['ca_template_editor_341'].openDialog('sourcedialog');
              self.ckeditor_iframe.instances['ca_template_editor_' + self.language_id].openDialog('sourcedialog');
            });
          }
          if (self.edit_mode == 'multiple') {
            self.iframe_jquery(".cap-email-block").bind('dblclick', function(e) {
              self.editMultipleDiv(e);
            });
            self.iframe_jquery("#source_" + self.language_id).bind("dblclick", function() {
              self.editor.openDialog('sourcedialog');
            });
            self.iframe_jquery(".cap-email-block").on("mouseover", function(e) {
              e.stopPropagation();
              var style = "border: 1px solid gray; cursor: pointer;";
              self.iframe_jquery(".cap-email-block").removeAttr("style");
              $(e.currentTarget).attr("style", style);
              $(e.currentTarget).attr("title", _campaign("Double Click on this block to edit"));
            });
          }
        }, 1000);
      };
      this.$el.html(this.tpl());
      var iframe = this.$(".iedit_template__template");
      this.language_id = iframe.parents('.template_editor').attr('editor_lang_id');
      iframe.attr('id', 'iedit_template__template_' + this.language_id);
      var idoc = iframe[0].contentDocument;
      html = this.model.get('html_content');
      idoc.open();
      if (html) {
        idoc.write(html);
      } else {
        idoc.write("<html><head></head><body></body></html>");
      }
      idoc.close();
      self.iframe_jquery("body").css("background", "#efefef");
      var settings = "<div id='source_" + this.language_id + "' class='source' style='position:fixed;top:5px;right:10px;width:25px;height:25px;background-image: url(\"/images/code-128.png\");background-size: 100% 100%;z-index:10000;cursor:pointer;display:none' title='source'></div>";
      var body = settings + "<div id='ca_template_editor_" + this.language_id + "' class='ca_template_editor' style='background: #FFF;min-height: 287px;margin:5% 5%;'>" + this.iframe_jquery("body").html() + "</div>";
      this.iframe_jquery("body").html(body);
      this.iframe_jquery('#ca_template_editor_' + this.language_id).on('click', 'a', function(e) {
        e.preventDefault();
        return false;
      });
      if (this.iframe_jquery(".cap-email-block").length > 0) {
        this.edit_mode = "multiple";
        var count = 1;
        this.iframe_jquery(".cap-email-block").each(function() {
          var id = "div_" + count;
          $(this).attr("id", id);
          count++;
        })
      } else {
        this.edit_mode = "single";
      }
      var headID = $("#iedit_template__template_" + this.language_id).contents().find("head")[0];
      var newScript1 = document.createElement("script");
      newScript1.type = "text/javascript";
      newScript1.src = "/js/jquery-1.7.2.min.js";
      headID.appendChild(newScript1);
      var newScript2 = document.createElement("script");
      newScript2.type = "text/javascript";
      newScript2.src = "/js/ckeditor/ckeditor.js";
      newScript2.onload = getRef;
      headID.appendChild(newScript2);
      return this;
    },
    showSource: function() {
      this.editor.openDialog('sourcedialog');
    },
    preventDefault: function(e) {
      e.preventDefault();
    },
    editSingleDiv: function() {
      var self = this;
      if (this.edit_mode === 'single') {
        var element = this.$("#iedit_template__template_" + self.language_id).contents().find('#ca_template_editor_' + self.language_id)[0];
        this.$("#iedit_template__template_" + self.language_id).contents().find('#ca_template_editor_' + self.language_id).attr('contenteditable', 'true');
        self.ckeditor_iframe.disableAutoInline = true;
        self.ckeditor_iframe.on('dialogDefinition', function(ev) {
          ev.data.definition.resizable = self.ckeditor_iframe.DIALOG_RESIZE_NONE;
        });
        if (!this.editor) {
          this.editor = self.ckeditor_iframe.inline(element, {
            toolbar: 'Basic',
            // allowedContent: true,
            extraPlugins: 'sourcedialog',
            removePlugins: 'magicline'
          });
          this.editor.on('change', function(ev) {
            self.trigger('set_html', this.getData(), 'body');
            //self.model.set('html_content', this.getData());
          });
          this.editor.on('instanceReady', function(ev) {
            self.$("#iedit_template__template_" + self.language_id).contents().find('#source_' + self.language_id).show();
          });
        }
      } else {
        return false;
      }
    },
    editMultipleDiv: function(e) {
      var self = this;
      if (this.edit_mode == 'multiple') {
        if (this.editor && this.block != $(e.currentTarget).attr('id')) {
          var old_html = this.editor.getData();
          this.iframe_jquery('#' + this.block).attr('contenteditable', false);
          this.editor.destroy();
          this.editor = null;
          this.iframe_jquery('#' + this.block).html(old_html);
        }
        var that = $(e.currentTarget);
        that.attr('contenteditable', true);
        this.block = that.attr('id');
        self.ckeditor_iframe.disableAutoInline = true;
        self.ckeditor_iframe.on('dialogDefinition', function(ev) {
          ev.data.definition.resizable = self.ckeditor_iframe.DIALOG_RESIZE_NONE;
        });
        this.editor = self.ckeditor_iframe.inline(that.attr('id'), {
          toolbar: 'Basic',
          // allowedContent: true,
          extraPlugins: 'sourcedialog',
          removePlugins: 'about,magicline'
        });
        this.editor.on('change', function(ev) {
          self.trigger('set_html', this.getData(), ('#' + self.block));
          //currentObj.html(this.getData());
          //self.model.set('html_content',"<html>"+$('#iedit_template__template_holder').contents().find('html').html()+"<html>");
        });
        this.editor.on('instanceReady', function(ev) {
          self.iframe_jquery('#source').show();
        });
      } else {
        return false;
      }
    },
    insertTag: function(tag) {
      this.editor.insertHtml(tag);
    },
    insertImage: function(url) {
      var img = '<img src="' + url + '"/>';
      this.editor.insertHtml(img);
    }
  });
  var EdmEditorView = ca.EdmEditorView = Backbone.View.extend({
    tpl: _.template($('#edm_editor_tpl').html()),
    initialize: function() {
      self.url = "";
    },
    render: function() {
      this.$el.html(this.tpl());
      $('.image_gallery').hide();
      var self = this;

      function openEdmEditor() {
        ca_ref.edmDesignerApi.openProject(self.model.get('drag_drop_id'), 'en', function(result) {
          self.url = result.url;
          self.openEditor(result.url);
        }, function(error) {
          console.log(error);
        });
      }
      openEdmEditor();
      var edmEventListener = function(event) {
        var msg = {};
        try {
          msg = JSON.parse(event.data);
          if (msg.action === "edit" && msg.elementJson.type === "IMAGE") {
            $('.image_gallery').trigger('click');
            self.insert_img_func = function(src) {
              event.source.postMessage(JSON.stringify({
                _dynId: msg._dynId,
                action: "setProps",
                props: {
                  src: src
                }
              }), "*");
            }
          } else if (msg.action === "editBackground" && (msg.elementJson.type === "BOX" || msg.elementJson.type === "BUTTON" || msg.elementJson.type === "GENERAL")) {
            $('.image_gallery').trigger('click');
            self.insert_img_func = function(src) {
              event.source.postMessage(JSON.stringify({
                _dynId: msg._dynId,
                action: "setProps",
                props: {
                  background: {
                    image: {
                      src: src
                    }
                  }
                }
              }), "*");
            }
          }
        } catch (e) {}
      };
      window.removeEventListener("message", edmEventListener, false);
      window.addEventListener("message", edmEventListener, false);
      return this;
    },
    openEditor: function(src) {
      this.$('#edm_editor_iframe').attr('src', src);
    },
    insertTag: function(tag, parent_element) {
      var msg = {
        "action": "InsertToCursor",
        "content": tag
      };
      var msg_string = JSON.stringify(msg);
      if (parent_element == undefined) {
        var win = document.getElementById("edm_editor_iframe").contentWindow;
      } else {
        var win = parent_element.find(".edm_editor_iframe")[0].contentWindow;
      }
      win.postMessage(msg_string, '*');
    },
    insertImage: function(src) {
      if (typeof this.insert_img_func === "function") {
        this.insert_img_func(src);
      }
    }
  });
  var TextEditorView = ca.TextEditorView = Backbone.View.extend({
    tpl: _.template($('#text_editor_tpl').html()),
    className: 'ca-full',
    initialize: function() {
      this.editor = undefined;
    },
    events: {
      'change .text_editor_textarea': 'setHtml'
    },
    setHtml: function() {
      html_content = this.$('.text_editor_textarea').val();
      this.trigger('set_html', html_content);
    },
    setModel: function(model) {
      this.model = model;
    },
    render: function() {
      this.$el.html(this.tpl({
        html_content: this.model.get('html_content')
      }));
    },
    insertTag: function(tag) {
      var cursorPos = this.$('.text_editor_textarea').prop('selectionStart');
      var value = $('.text_editor_textarea').val();
      var before = value.substring(0, cursorPos);
      var after = value.substring(cursorPos, value.length);
      this.$('.text_editor_textarea').val(before + tag + after).trigger('change');
    }
  });
  var ImageGalleryView = ca.ImageGalleryView = Backbone.View.extend({
    tpl: _.template($('#image_gallery_tpl').html()),
    option: 'insert',
    initialize: function() {
      this.template_container_view = new TemplatesCollectionView({
        template_type: 'image'
      });
      this.template_container_view.setTemplateView(ImageView);
      this.listenTo(this.template_container_view, 'insert_image', this.insertImage);
      //this.template_container_view.setEditTemplateView(EditImageView);
      //this.initializeImageGallery('.ca-container-body');
    },
    insertImage: function(url) {
      this.trigger('insert_image', url)
    },
    /**
     * loading image name and dimension on click of image or next or previous button.
     */
    render: function() {
      this.initialize();
      this.$el.html(this.tpl());
      this.template_container_view.setElement(this.$('.ca-image-collection-container')).render();
    }
  });
  var ImageView = ca.ImageView = Backbone.View.extend({
    tpl: _.template($('#img_template_tpl').html()),
    className: 'ca-img-template-view',
    initialize: function() {
      this.listenTo(this.model, 'change', this.render);
      this.listenTo(this.model, 'destroy', this.remove);
      this.listenTo(this.model, 'selected', this.selectThis);
      this.listenTo(this.model, 'unselected', this.unselectThis);
    },
    render: function() {
      this.$el.html(this.tpl(this.model.toJSON()));
      return this;
    },
    events: {
      'click .ca_favourite_icon': 'toggleFavourite',
      'click .ca_preview_holder': 'showPreview'
    },
    toggleFavourite: function() {
      this.model.toggleFavourite();
    },
    showPreview: function() {
      this.trigger('preview_image', this.model);
    },
    selectThis: function() {
      this.$('.ca-img-preview-holder').addClass('ca-selected');
    },
    unselectThis: function() {
      this.$('.ca-img-preview-holder').removeClass('ca-selected');
    }
  });
  var PreviewImageView = ca.PreviewImageView = Backbone.View.extend({
    tpl: _.template($('#preview_image_tpl').html()),
    className: 'ca-preview-image',
    option: 'insert',
    initialize: function() {
      if (ca_ref.image_option == "delete") this.option = "delete";
      else this.option = 'insert';
    },
    render: function() {
      if (this.currentModel) this.currentModel.templateUnselected();
      var self = this;
      this.$el.html('<div class="ca-img-loader">');
      var img = new Image();
      img.src = this.model.get('image_url');
      img.onload = function() {
        var image_info = self.loadImageInfo(img);
        self.$el.html(self.tpl({
          model: self.model.toJSON(),
          info: image_info,
          option: self.option
        }));
      };
      this.model.templateSelected();
      this.currentModel = this.model;
      return this;
    },
    events: {
      'click .next_image': 'nextImage',
      'click .prev_image': 'prevImage',
      'click .insert_img': 'insertImage',
      'click .delete_img': 'deleteImage',
      'click .ca-image-container': 'nextImage'
    },
    nextImage: function() {
      var next_model = this.model.next();
      this.setModel(next_model).render();
    },
    prevImage: function() {
      var prev_model = this.model.prev();
      this.setModel(prev_model).render();
    },
    insertImage: function() {
      this.trigger('insert_image', this.model.get('image_url'));
    },
    deleteImage: function() {
      this.model.deleteTemplate();
      this.nextImage();
    },
    setModel: function(model) {
      this.model = model;
      return this;
    },
    loadImageInfo: function() {
      var title = this.model.get('image_url');
      var image_size = this.model.get('image_size');
      var img = new Image();
      img.src = title;
      var height = parseInt(img.height);
      var width = parseInt(img.width);
      var image_name = this.model.get('name');
      if (image_name.length > 15) image_name = image_name.substring(0, 15) + '...';
      var maxWidth = 380 - 2;
      var maxHeight = 250 - 2;
      var ratio = Math.min(maxWidth / width, maxHeight / height);
      var final_width = ratio * width;
      var final_height = ratio * height;
      var image_info = {
        name: image_name,
        size: image_size,
        width: final_width,
        height: final_height,
        orgWidth: width,
        orgHeight: height
      };
      return image_info;
    }
  });
  return creativeAssets;
});
