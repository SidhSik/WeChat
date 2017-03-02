(function(factory) {
  var root = (typeof self == 'object' && self.self == self && self) || (typeof global == 'object' && global.global == global && global);
  root.CreativeAssets = factory(root.Backbone, root._, root.jQuery);
})(function(Backbone, _, $) {
  var ca_ref;
  var eventBus;
  var creativeAssets = function() {
    ca_ref = this;
    return this;
  };
  var ca = creativeAssets.prototype;
  var helper = ca.helper = {
    showFlash: function(msg, error) {
      if (error) {
        $('.flash_message').addClass('redError').html(msg).show();
      } else {
        $('.flash_message').removeClass('redError').html(msg).show();
      }
      setTimeout(function() {
        $('.flash_message').fadeOut('fast').removeClass('redError');
      }, 3000);
    },
    showLoader: function(selector) {
      if (selector.css('position') === 'static') selector.css('position', 'relative');
      selector.append('<div class="ca_wait_div intouch-loader"></div>');
    },
    removeLoader: function(selector) {
      //selector.css('position','static');
      selector.children('.ca_wait_div').remove();
    },
    slideView: function(selector1, selector2) {},
    addWait: function() {
      $('.wait_message').show().addClass('indicator_1');
    },
    removeWait: function() {
      $('.wait_message').hide().removeClass('indicator_1');
    }
  };
  ca.backToTemplate = function() {
    eventBus.trigger('go_back_to_template');
  };
  ca.getResult = function() {
    var status = $.Deferred();
    result = {
      status: status,
      output: {}
    };
    eventBus.trigger('get_output', result);
    result.status.done(function() {});
    return result;
  };
  ca.initialize = function(container, tags, edmUserId, sender_info, existing_template_data, languages) {
    var token_url = "/xaja/AjaxService/assets/get_edm_token.json";
    var self = this;
    if (container instanceof jQuery) {
      this.container = container;
    } else {
      this.container = $(container);
    }
    if (this.container.length <= 0) {
      ca.helper.showFlash(_campaign("container doesn't exist on window document"), true);
    }
    this.languages = languages;
    this.removed_tags = [];
    this.tags = tags;
    this.edmUserId = edmUserId;
    this.sender_info = sender_info;
    this.existing_template_data = existing_template_data;
    if (this.edmUserId) {
      if (typeof initEDMdesignerPlugin != 'undefined') {
        initEDMdesignerPlugin(token_url, this.edmUserId, function(edmDesignerApi) {
          self.edmDesignerApi = edmDesignerApi;
        });
      } else {
        ca.helper.showFlash(_campaign("Edm editor cannot be initialized"), true);
      }
    }
    if (eventBus) eventBus.off();
    eventBus = this.eventBus = _.extend({}, Backbone.Events);
    if (this.existing_template_data) {
      this.existing_model = new TemplateModel(this.existing_template_data.base_template);
      this.secondary_templates = this.existing_template_data.secondary_templates;
    }
  };
  ca.renderImageGallery = function() {
    this.image_gallery_view = new ca.ImageGalleryView();
    this.image_gallery_view.setElement();
    this.container.empty();
    this.container.html(this.image_gallery_view.render().el);
  };
  ca.render = function() {
    if (this.templates_view) {
      this.templates_view.undelegateEvents();
      this.templates_view.stopListening();
    }
    this.container.empty();
    if (false && window.location.href.indexOf('WeChat')) {
      this.templates_view = new ca.WeChatTemplatesCollectionView();
    } else {
      this.templates_view = new ca.TemplatesCollectionView({
        languages: this.languages
      });
    }
    this.container.html(this.templates_view.render().el);
    this.container.append($("#insert_image_tpl").html());
  };
  ca.editExisting = function() {
    if (this.existing_model) {
      this.eventBus.trigger('edit_existing', this.existing_model, this.secondary_templates);
      return true;
    } else {
      this.helper.showFlash(_campaign("Template data don't exist"), true);
      return false;
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
        is_deleted: 0
      };
    },
    idAttribute: 'template_id',
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
    duplicateModel: function(status) {
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
        });
        return duplicate_model;
      }
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
    getHtmlFromEdm: function(status) {
      var self = this;
      var iframe = $('.template_editor[editor_lang_id=' + this.get('language_id') + ']').find('.edm_editor_iframe')[0];
      //var iframe = document.getElementById("edm_editor_iframe");
      if (iframe) {
        var win = $('.template_editor[editor_lang_id=' + this.get('language_id') + ']').find('.edm_editor_iframe')[0].contentWindow;
        win.postMessage('saveProject', '*');
      }
      ca_ref.edmDesignerApi.generateProject(self.get('drag_drop_id'), function(result) {
        result = result.replace(/TOKEN%7D%7D/g, 'TOKEN}}').replace(/&=amp%3B%7B%7BSURVEY/g, '&amp;{{SURVEY');
        self.set('html_content', result);
        if (status) status.resolve();
      });
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
      this.setHtml().done(function() {
        var url = "/xaja/AjaxService/assets/save_html_template.json";
        var data = self.toJSON();
        var status = $.post(url, data, function(resp) {
          if (resp.success) {
            ca.helper.showFlash(resp.success);
            self.set('template_id', resp.template_id);
          } else {
            ca.helper.showFlash(resp.error, true);
          }
        }, 'json');
        return status;
      });
    }
  });
  // this view contains template image with favourite button
  var TemplateView = ca.TemplateView = Backbone.View.extend({
    model: TemplateModel,
    className: 'ca-template-view',
    initialize: function() {
      this.tpl = _.template($('#template_tpl').html());
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
  var ImageView = ca.TemplateView = Backbone.View.extend({
    className: 'ca-img-template-view',
    initialize: function() {
      this.tpl = _.template($('#img_template_tpl').html());
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
    className: 'ca-preview-image',
    option: 'insert',
    initialize: function() {
      this.tpl = _.template($('#preview_image_tpl').html());
    },
    render: function() {
      if (this.currentModel) this.currentModel.templateUnselected();
      var self = this;
      var img = new Image();
      img.src = this.model.get('image_url');
      this.$el.html('<div class="ca-img-loader">');
      img.onload = function() {
        var image_info = self.loadImageInfo(img);
        self.$el.html(self.tpl({
          model: self.model.toJSON(),
          info: image_info,
          option: this.option
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
  var CreateTemplateView = ca.CreateTemplateView = Backbone.View.extend({
    className: 'ca-create-template-view',
    initialize: function() {
      this.tpl = _.template($('#create_template_tpl').html());
    },
    render: function() {
      this.$el.html(this.tpl(this.model.toJSON()));
      return this;
    },
    events: {
      'click': 'editSelectedTemplate'
    },
    editSelectedTemplate: function(e) {
      $('.new_template_header').hide();
      $('.lang_create_new_container').hide();
      var self = this;
      var status = $.Deferred();
      var model = this.model.duplicateModel(status);
      var clickElementParent = $(e.currentTarget).parent();
      var lang_id = clickElementParent.attr("template_lang_id");
      ca.helper.addWait();
      status.done(function() {
        ca.helper.removeWait();
        self.trigger('edit_selected_template', model, 'new', lang_id);
      });
    }
  });
  var MessageDesignTemplateView = ca.MessageDesignTemplateView = Backbone.View.extend({
    tagName: 'div',
    className: 'ca-template-view',
    initialize: function() {
      this.tpl = _.template($('#msg_template_tpl').html());
      this.template_view = new TemplateView({
        model: this.model
      });
      this.listenTo(this.template_view, 'show_preview', this.showPreview);
      this.listenTo(this.model, 'destroy', this.remove);
    },
    render: function() {
      this.$el.html(this.tpl(this.model.toJSON()));
      this.template_view.setElement(this.$('.template_view')).render();
      return this;
    },
    events: {
      'click .select_template': 'editTemplate'
    },
    editTemplate: function() {
      var status = $.Deferred();
      var self = this;
      var duplicate_model = this.model.duplicateModel(status);
      status.done(function() {
        self.model.set('drag_drop_id', duplicate_model.get('drag_drop_id'));
        self.trigger('edit_template', self.model, 'edit');
      })
    },
    showPreview: function() {
      var self = this;
      self.$("#template_preview_modal").modal('show');
      var top_preview_view = new TemplatePreviewParent({
        model: self.model
      });
      top_preview_view.setElement(this.$('.preview_container')).render();
    }
  });
  var TemplatePreviewParent = Backbone.View.extend({
    child_preview: [],
    initialize: function() {
      this.tpl = _.template($('#container_email_preview_tpl').html());
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
      lang_id = $(e.currentTarget).attr("id").split("__")[1];
      scope = 'ORG';
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
    initialize: function() {
      this.tpl = _.template($('#email_preview_tpl').html());
    },
    render: function() {
      var self = this;
      this.$el.html(this.tpl(this.model.toJSON()));
      ca.helper.addWait();
      this.model.setHtml().done(function() {
        var html_content = self.model.get('html_content');
        window.setTimeout(function() {
          ca.helper.removeWait();
          self.$el.find('#template_email_iframe_preview').contents().find('html').html(html_content);
          $('#template_preview_iframe_mobile_portrait').contents().find('html').html(html_content);
          $('#template_iframe_mobile_landscape').contents().find('html').html(html_content);
        }, 1000);
      });
      return this;
    },
    events: {
      'click .btn_email_mobile': 'showMobile',
      'click .btn_email_desktop': 'showDesktop',
      'click .btn_email_tablet': 'showTablet',
      'click .preview_favourite': 'favouriteTemplate'
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
    favouriteTemplate: function(e) {
      this.model.toggleFavourite();
      $(e.currentTarget).children('i').toggleClass('icon-heart-empty').toggleClass('icon-heart');
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
        var ajax_url = '/xaja/AjaxService/assets/get_html_templates.json?start=' + this.start_limit + '&limit=' + limit;
        if (this.favourite) {
          ajax_url += '&favourite=1';
        }
        if (this.search) {
          ajax_url += '&search=' + this.search;
        }
        if (this.template_type) {
          ajax_url += '&template_type=' + this.template_type;
        }
        if (this.scope) {
          ajax_url += '&scope=' + this.scope;
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
    tpl: "",
    subject: "",
    sender_info: "",
    parent: null,
    initialize: function(options) {
      this.tpl = _.template($('#lang_based_content').html());
      if (options && options.languages) {
        this.languages = options.languages;
      }
      this.lang_id_name = [];
      var i = 0;
      for (var key in this.languages.all_lang) {
        this.lang_id_name[i] = {};
        this.lang_id_name[i].lang_id = key;
        this.lang_id_name[i].lang_name = this.languages.all_lang[key];
        i++;
      }
      this.added_languages[0] = {};
      var base_lang_id = this.languages.base_language_id;
      this.added_languages[0].lang_id = base_lang_id;
      this.added_languages[0].lang_name = ca_ref.languages.all_lang[base_lang_id];
      if (ca_ref.existing_template_data && ca_ref.existing_template_data.base_template) {
        this.subject = ca_ref.existing_template_data.base_template.subject;
      } else {
        this.subject = '';
      }
      this.sender_info = ca_ref.sender_info;
      this.listenTo(eventBus, 'get_output', this.goToNext);
    },
    render: function() {
      var base_lang_spec = {};
      base_lang_spec.lang_id = this.languages.base_language_id;
      base_lang_spec.lang_name = this.languages.all_lang[base_lang_spec.lang_id];
      this.$el.html(this.tpl({
        languages: this.lang_id_name,
        base_lang: base_lang_spec,
        sender_info: this.sender_info,
        subject: this.subject
      }));
      $(".ca-header").hide();
      $(".lang_tab[lang_id=" + this.languages.base_language_id + "]").trigger("click");
      $("#create_new_template_parent").show();
      $(".create_new_template").hide();
      $(".create_new_template[template_lang_id=" + this.languages.base_language_id + "]").show();
      $("#lang_content_parent").show();
      this.renderFromDetails();
    },
    events: {
      'click .edit_from': 'showFromAddressModal',
      'click .save_name_address': 'saveNameAddress',
      'click #change_name': 'changeTemplateName',
      'click #edit_name': 'openEditNameModel',
      "click #add_lang": "showLanguageModal",
      "click .add_lang_btn": "addLanguageModal",
      "click .lang_tab": "showLanguageSpecificContent",
      "click .save_all_templates": "saveAllTemplates",
      'click #skip_edit_new': 'skipEditNewTemplate',
      'click #save_edit_new': 'saveAllTemplates',
      'change .subject': 'saveSubject',
      'change #from_domain_list': 'fromDomainChange',
      'click .remove_language_list': 'removeLanguage'
    },
    /*editExistingTemplate: function(model , all_models){
        if(this.parent!=undefined && this.parent!=null)
            this.parent.editExistingTemplate(model , all_models) ;
        else
            ca.helper.showFlash(_campaign('Unable to edit the existing template, Please create a new template'), true);

    } ,*/
    fromDomainChange: function(e) {
      var domain_props = this.sender_info.domain_props;
      var selected_domain = $("#from_domain_list :selected").text();
      this.renderSubDomainDetails(selected_domain);
      this.renderDomainDetails(selected_domain);
    },
    renderSubDomainDetails: function(selected_domain, selected_map_id) {
      var domain_props = this.sender_info.domain_props;
      $("#from_subdomain_list").empty();
      _.each(domain_props[selected_domain]['sub_domains'], function (value, prop) {
        var gateway_entry = value['sub_domain'] + " - " + value['sub_gateway'];
        var option = "<option class='from_sub_domains' value='" +
            value['dom_gateway_map_id'] + "'>" +
            gateway_entry + "</option>";
        $("#from_subdomain_list").append(option);
      });

      if(selected_map_id) {
        $('#from_subdomain_list option[value="' + selected_map_id + '"]').prop('selected', true);
      }
    },
    renderDomainDetails: function(selected_domain, selected_sender_details ,selected_reply_details) {
      var domain_props = this.sender_info.domain_props;
      this.$('.domain_desc').html(domain_props[selected_domain]['description']);
      var selected_sender = domain_props[selected_domain]['senders'][0];
      $("#from_sender_name").val(selected_sender['label']);
      
      $("#sender_id_select").empty();
      _.each(domain_props[selected_domain]['senders'], function(value, prop) {
        var option = "<option class=''>" + value['value'] + "</option>";
        $("#sender_id_select").append(option);
      });

      var selected_replyto = domain_props[selected_domain]['replytos'][0];
      $("#from_replyto_name").val(selected_replyto['label']);
      
      $("#replyto_id_select").empty();
      _.each(domain_props[selected_domain]['replytos'], function(value, prop) {
        var option = "<option class=''>" + value['value'] + "</option>";
        $("#replyto_id_select").append(option);
      });

      if(selected_sender_details && Object.keys(selected_sender_details).length) {
        $("#from_sender_name").val(selected_sender_details.label);
        $("#sender_id_select").val(selected_sender_details.address);
      }

      if(selected_reply_details && Object.keys(selected_reply_details).length) {
        $("#from_replyto_name").val(selected_reply_details.label);
        $("#replyto_id_select").val(selected_reply_details.address);
      }
    },
    renderDomainList: function( domain_props, selected_domain ) {
      delete domain_props.selected_details;
      _.each(domain_props, function (value, prop) {
        var option = "<option class='from_domains' name=" + prop + ">" + prop + "</option>";
        $("#from_domain_list").append(option);
      });
      if (selected_domain) {
        $('#from_domain_list option[name="' + selected_domain + '"]').prop('selected', true);
      }
    },
    fetchDomainFromGateway: function(selected_map_id, domain_props) {
      var selected_domain;

      selected_map_id = parseInt(selected_map_id);
      for(var domain in domain_props) {
        if(domain == "selected_details") {
          continue;
        }
        for(var map_index in domain_props[domain]['sub_domains']) {
          var dom_gateway_map_id = domain_props[domain]['sub_domains'][map_index]['dom_gateway_map_id'];
          if(dom_gateway_map_id == selected_map_id) {
            selected_domain = domain;
          }
        }
      }
      return selected_domain;
    },
    initDomainDetails: function() {
      var domain_props = this.sender_info.domain_props;
      if(!domain_props)
       return;
      var selected_domain = Object.keys(domain_props)[0];
      var selected_map_id;
      var selected_reply_details = {};
      var selected_sender_details = {};
      
      var is_edit = this.sender_info.is_edit;
      
      if(is_edit) {
        this.$('.from_name').html(this.sender_info.sender_label);
        this.$('.from_email').html(this.sender_info.sender_from);
        this.$('.from_gateway').html(this.sender_info.domain_props.selected_details.domain_gateway_from);
        selected_map_id = this.sender_info.domain_props.selected_details.domain_gateway_map_id;
        selected_sender_details["address"] = this.sender_info.sender_from;
        selected_sender_details["label"] = this.sender_info.sender_label;
        selected_reply_details["address"] = this.sender_info.domain_props.selected_details.replyto_from;
        selected_reply_details["label"] = this.sender_info.domain_props.selected_details.replyto_label;
        selected_domain = this.fetchDomainFromGateway(selected_map_id, this.sender_info.domain_props);
      }
      else {
        var default_domain = this.sender_info.default_domain;
        this.$('.from_name').html(sender_label);
        if(default_domain && default_domain != "null") {
          this.$('.from_name').html(default_domain['domainProperties']['contactInfo']['sender_id'][0]['label']);
          this.$('.from_email').html(default_domain['domainProperties']['contactInfo']['sender_id'][0]['value']);
          this.$('.from_gateway').html(default_domain['gatewayOrgConfigs']['shortName']);
          selected_domain = default_domain['domainProperties']['domainName'];
          selected_map_id = default_domain['id'];
        }
        else {
          var name = '',
            address = '';
          
          if (this.sender_info.sender_details && this.sender_info.sender_details.length > 1) {
            var idx = this.$(".sender_details_idx").val();
            name = this.sender_info.sender_details[idx].sender_label;
            address = this.sender_info.sender_details[idx].sender_email;
          } else {
            name = this.$(".from_sender_name").val();
            address = this.$(".sender_id_select option:selected").text();
          }
          
          if (!name && !address) return false;

          this.$('.from_name').html(name);
          this.$('.from_email').html(address);
          this.sender_info.sender_label = name;
          this.sender_info.sender_from = address;
        }
      }
      
      this.renderDomainList(domain_props, selected_domain);
      this.renderSubDomainDetails(selected_domain, selected_map_id);
      this.renderDomainDetails(selected_domain, selected_sender_details, selected_reply_details);
    },
    renderFromDetails: function() {
      this.initDomainDetails();
    },
    saveNameAddress: function() {
      var sender_name = '',
        sender_address = '',
        replyto_name = '',
        replyto_address = '',
        from_gateway = ''
        domain_gateway_map_id = '';
      var selected_gateway = $("#from_subdomain_list option:selected").text().split('-')[1];
      sender_name = this.$(".from_sender_name").val();
      sender_address = this.$(".sender_id_select").val();
      replyto_address = this.$(".replyto_id_select").val();
      replyto_name = this.$(".from_replyto_name").val();
      from_gateway = selected_gateway.trim();
      this.$('.from_gateway').html(from_gateway);
      domain_gateway_map_id = $("#from_subdomain_list option:selected").val();

      if (!sender_name && !sender_address) 
	return false;
      var domain_gateway_details = {};
      domain_gateway_details['sender_name'] = sender_name;
      domain_gateway_details['sender_address'] = sender_address;
      domain_gateway_details['replyto_name'] = replyto_name;
      domain_gateway_details['replyto_address'] = replyto_address;
      domain_gateway_details['from_gateway'] = from_gateway;
      domain_gateway_details['domain_gateway_map_id'] = domain_gateway_map_id;
      this.$('.from_name').html(sender_name);
      this.$('.from_email').html(sender_address);
      this.sender_info.sender_label = sender_name;
      this.sender_info.sender_from = sender_address;
      this.sender_info.from_details_edited = true;
      this.sender_info.domain_gateway_details = domain_gateway_details;

      this.$('.from_address_modal').modal('hide');
    },
    showFromAddressModal: function() {
      this.$(".from_address_modal").modal();
    },
    saveSubject: function(e) {
      this.subject = this.$(e.currentTarget).val();
    },
    skipEditNewTemplate: function() {
      //this.template_name = this.$('#edit_new_name').val().trim() ;
      this.$('#save_edit_new_modal').modal('hide');
      var template_ref = this.langBasedEditTemplates[ca_ref.languages.base_language_id];
      this.getOutput(template_ref.returnResult);
    },
    saveEditNewTemplate: function() {
      var self = this;
      var name = this.$("#edit_new_name").val();
      if (!name) return false;
      this.model.set({
        name: name,
        template_id: ''
      });
      this.$('#save_edit_new_modal').modal('hide');
      this.model.saveTemplate().done(function() {
        self.getOutput(self.returnResult);
      });
    },
    getOutput: function(result) {
      var self = this;
      var baseEditTemplate = this.langBasedEditTemplates[ca_ref.languages.base_language_id];
      var arr_lang_id = [];
      var secondary_templates = {};
      var base_language_id = ca_ref.languages.base_language_id;
      var resp = [];
      for (var key in self.langBasedEditTemplates) {
        if (self.langBasedEditTemplates[key] == null) continue;
        arr_lang_id[key] = ca_ref.languages.all_lang[key];
        self.langBasedEditTemplates[key].model.set("language_id", key);
        resp.push(self.langBasedEditTemplates[key].model.setHtml());
      }
      $.when.apply($, resp).done(function() {
        for (var key in self.langBasedEditTemplates) {
          if (self.langBasedEditTemplates[key] == null || self.langBasedEditTemplates[key].model.get('is_deleted') != 0) continue;
          secondary_templates[key] = {};
          secondary_templates[key].template_data = self.langBasedEditTemplates[key].model;
          secondary_templates[key].lang_name = ca_ref.languages.all_lang[key];
        }
        baseEditTemplate.editTemplateView.setHtmlToIframe(baseEditTemplate.model.get('html_content'));
        subject = self.$('#edit_template__subject').val();
        result.output = {
          "html_content": baseEditTemplate.model.get('html_content'),
          subject: subject,
          sender_info: self.sender_info,
          lang_arr: JSON.stringify(arr_lang_id),
          base_language_id: base_language_id,
          secondary_templates: JSON.stringify(secondary_templates)
        };
        result.status.resolve();
      }).fail(function() {});
    },
    goToNext: function(result) {
      var edit_template = this.langBasedEditTemplates;
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
      if (!this.subject) {
        ca.helper.showFlash(_campaign('Please provide an appropriate subject to proceed!'), true);
        return false;
      }
      var base_lang_id = ca_ref.languages.base_language_id;
      if (!edit_template[base_lang_id]) {
        ca.helper.showFlash(_campaign('Please mandatorily set the template for!') + ca_ref.languages.all_lang[base_lang_id], true);
      } else {
        if (!edit_template[base_lang_id].model.get('template_id')) {
          for (var key in edit_template) {
            if (edit_template[key] == null) continue;
            edit_template[key].returnResult = result;
          }
          this.$('#save_edit_new_modal').modal('show');
          this.template_name = this.template_name.trim();
          if (this.template_name == '') {
            var timestamp = Date.now();
            this.template_name = _campaign('Template') + '_' + timestamp;
          }
          this.$('#edit_new_name').val(this.template_name);
        } else {
          result = this.getOutput(result);
        }
      }
    },
    changeTemplateName: function() {
      var name = this.$("#rename_template").val();
      name = name && name.trim();
      if (!name) return false;
      this.model.set('name', name);
      this.renderNewName(name);
      this.$('#edit_name_modal').modal('hide');
    },
    renderNewName: function(name) {
      $("#edit_template_name").html(name);
      $(".edit-name-container").removeClass('hide');
    },
    openEditNameModel: function() {
      // this.$("#rename_template").val(this.model.get('name'));
      var name = this.$("#edit_template_name").html();
      // var name = this.model.name;
      this.$("#rename_template").val(name);
      this.$("#edit_name_modal").modal();
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
      var url = "/xaja/AjaxService/assets/save_html_template.json";
      var deferred = [];
      var self = this;
      var base_template_id = -1;
      var template_name = $('#edit_new_name').val().trim();
      $("#save_edit_new_modal").modal('hide');
      if (template_name == '') {
        ca.helper.showFlash(_campaign("Please provide template name"), true);
        return;
      }
      this.template_name = template_name;
      this.showEditLoader(true);
      if (ca_ref.languages && ca_ref.languages.base_language_id) {
        for (var key in this.langBasedEditTemplates) {
          if (this.langBasedEditTemplates[key] == null) continue;
          this.langBasedEditTemplates[key].model.set("language_id", key);
          lang_temp_name = this.template_name + "_" + ca_ref.languages.all_lang[key];
          this.langBasedEditTemplates[key].model.set("name", lang_temp_name);
          resp.push(this.langBasedEditTemplates[key].model.setHtml());
        }
        lang_temp_name = this.template_name;
        this.langBasedEditTemplates[ca_ref.languages.base_language_id].model.set("name", lang_temp_name);
      }
      $.when.apply($, resp).done(function() {
        var base_language_id = self.languages.base_language_id;
        self.langBasedEditTemplates[base_language_id].model.set('is_multi_lang', 1);
        self.langBasedEditTemplates[base_language_id].model.set('base_template_id', -1);
        var data = self.langBasedEditTemplates[base_language_id].model.toJSON();
        $.post(url, data, function(resp) {
          self.showEditLoader(false);
          if (resp.success) {
            base_template_id = resp.template_id;
            self.langBasedEditTemplates[base_language_id].model.set('template_id', resp.template_id);
          } else {
            ca.helper.showFlash(resp.error, true);
          }
        }, 'json').done(function() {
          if (base_template_id > 0) {
            for (var key in self.langBasedEditTemplates) {
              if (key == self.languages.base_language_id || self.langBasedEditTemplates[key] == null) {
                continue;
              }
              self.langBasedEditTemplates[key].model.set('is_multi_lang', 1);
              self.langBasedEditTemplates[key].model.set('base_template_id', base_template_id);
              data = self.langBasedEditTemplates[key].model.toJSON();
              deferred.push($.post(url, data, function(resp) {
                self.showEditLoader(false);
                if (resp.success) {
                  self.langBasedEditTemplates[key].model.set('template_id', resp.template_id);
                } else {
                  ca.helper.showFlash(resp.error, true);
                }
              }, 'json'));
            }
            $.when.apply($, deferred).done(function() {
              self.showEditLoader(false);
              ca.helper.showFlash(_campaign("Template") +" "+ self.template_name + " "+_campaign("saved succesfully"));
            }).fail(function() {
              ca.helper.showFlash(_campaign("Failed to save templates"), true);
              this.showEditLoader(false);
            });
          }
        }).fail(function() {
          ca.helper.showFlash(_campaign("Failed to save base template"), true);
          this.showEditLoader(false);
        });
      }).fail(function() {
        ca.helper.showFlash(_campaign("Failed to save templates to server"), true);
        this.showEditLoader(false);
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
      }
    },
    editLanguageTemplate: function(lang_ids) {
      len = lang_ids.length;
      var element = "";
      for (i = 0; i < len; i++) {
        lang_id = lang_ids[i];
        if (this.getLanguageIndex(lang_id, 'edit') == -1) {
          element += '<li lang_id="' + lang_id + '" class="lang_tab">' + this.languages.all_lang[lang_id] + '</li>';
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
    getLanguageIndex: function(lang_id, edit) {
      if (edit != undefined) {
        return -1;
      }
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
        this.added_languages.splice(lang_index, 1);
        if (this.langBasedEditTemplates[lang_id].model.get("secondary_template_id")) {
          this.langBasedEditTemplates[lang_id].model.set("is_deleted", 1);
          var url = "/xaja/AjaxService/messages/remove_secondary_language.json";
          var data = this.langBasedEditTemplates[lang_id].model.toJSON();
          $.post(url, data, function(resp) {}, 'json');
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
    },
    getLanguageAddedCount: function() {
      return this.added_languages.length;
    }
  });
  var WeChatTemplatesCollectionView = ca.WeChatTemplatesCollectionView = Backbone.View.extend({
    className: 'ca-templates-collection-view',
    events: {
      'click .select_template': 'selectTemplate'
    },
    initialize: function(options) {
      this.all_collection = new TemplatesCollection();
      this.all_collection.template_type = 'WECHAT_SINGLE_TEMPLATE';
      this.all_collection.scope = 'WECHAT';
      this.weChatTemplateId = 'wechat_single_tpl';
      this.listenTo(this.all_collection, 'sync', this.renderTemplateList);
      this.renderShell();
      $('.msg-nav-bar .next_div').addClass('hide');
    },
    renderShell: function() {
      this.$el.html(_.template($('#wechat_templates_collection_tpl').html()));
      this.getTemplates();
    },
    getTemplates: function() {
      this.all_collection.fetch();
    },
    renderTemplateList: function() {
      var that = this;
      var templateListHtml = '';
      this.all_collection.each(function(template) {
        templateListHtml += _.template($('#' + that.weChatTemplateId).html())({
          model: template.toJSON()
        });
      });
      this.$el.find('.ca_all_container_body').html(templateListHtml);
    },
    selectTemplate: function(e) {
      var templateData = this.all_collection.get($(e.currentTarget).parents('.ca-wechat-template-view').attr('templateid'));
      $('.ca_top_view_container').addClass('hide');
      this.$el.find('.ca_top_edit_container').html(_.template($('#preview_' + this.weChatTemplateId).html())({
        model: templateData.toJSON()
      }));
      $('.msg-nav-bar .next_div').removeClass('hide');
    }
  });
  //  this is more like mother view
  // this view is all templates view
  // it includes collection of all subview is CreativeAssetsTemplateView and header option
  // It also includes edit template view
  var TemplatesCollectionView = ca.TemplatesCollectionView = Backbone.View.extend({
    template_type: 'html',
    className: 'ca-templates-collection-view',
    initialize: function(options) {
      this.tpl = _.template($('#templates_collection_tpl').html());
      this.templateView = MessageDesignTemplateView;
      this.editTemplateView = MessageDesignEditTemplateView;
      this.previewImageView = new PreviewImageView();
      this.listenTo(this.previewImageView, 'insert_image', this.insertImage);
      this.editTemplateViewInstance = null;
      this.lang_view = null;
      if (options && options.template_type) {
        this.template_type = options.template_type;
      }
      this.languages = null;
      if (options && options.languages) {
        this.languages = options.languages;
      }
      this.all_collection = new TemplatesCollection();
      this.all_collection.template_type = this.template_type;
      this.favourite_collection = new TemplatesCollection();
      this.favourite_collection.favourite = true;
      this.favourite_collection.template_type = this.template_type;
      this.search_collection = new TemplatesCollection();
      this.search_collection.template_type = this.template_type;
      this.create_new_collection = new TemplatesCollection();
      this.create_new_collection.default_template = true;
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
      this.listenTo(eventBus, 'go_back_to_template', this.showView);
      if (this.languages != null) this.listenTo(eventBus, 'edit_existing', this.editExistingTemplate);
      //this.eventBus.on('edit_template', this.editTemplate);
    },
    render: function() {
      this.$el.html(this.tpl({
        template_type: this.template_type
      }));
      if (this.collection.length < this.collection.default_limit) this.addTemplates();
      var self = this;
      this.$('.ca_container_body').on('scroll', function() {
        self.checkScroll(this);
      });
      var height = $(window).height() - 170;
      this.$('.ca_container_body').css("max-height", height);
      $(window).resize(function() {
        var height = $(window).height() - 170;
        self.$('.ca_container_body').css("max-height", height);
      });
      if (this.template_type == 'image') {
        this.$("#image_upload").submit(function() {
          self.showAllLoader(true);
          var formObj = $(this);
          var formURL = '/xaja/AjaxService/assets/upload_image.json';
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
              success: function(data, textStatus, jqXHR) {
                var data = JSON.parse(data);
                if (!data.error) {
                  var info = data.info;
                  self.addImage(info);
                  ca.helper.showFlash(_campaign("Image uploaded successfully"))
                } else {
                  ca.helper.showFlash(data.error)
                }
                self.showAllLoader(false);
              },
              error: function(jqXHR, textStatus, errorThrown) {
                ca.helper.showFlash('textStatus');
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
      'click .all_template': 'showAllTemplates',
      'click .favourite_template': 'showFavouriteTemplates',
      'keyup .ca_search': 'showSearchTemplates',
      'click .upload_zip_file': 'showZipUpload',
      'click .upload_html_file': 'showHtmlUpload',
      'change #file_upload': 'uploadFile',
      'click .create_from_scratch': 'createFromScratch',
      'click .upload_image_file': 'showImageUpload',
      'change #upload_image': 'uploadImage',
      'click .back_view': 'showView'
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
      if (this.collection.length == 0) {
        var filtered_models = this.all_collection.filter(function(model) {
          return model.get('is_favourite');
        });
        this.collection.set(filtered_models);
        this.collection.completed = false;
        this.collection.start_limit = filtered_models.length;
      }
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
            return model.get('name').indexOf(search_val.toLowerCase()) >= 0;
          });
          self.collection.set(filtered_models);
          self.collection.completed = false;
          self.collection.start_limit = filtered_models.length;
          if (self.collection.length < self.collection.default_limit) {
            self.addTemplates();
          }
        }, t);
      }
    },
    addTemplates: function() {
      var self = this;
      self.collection.new_models = [];
      if (!this.collection.completed) {
        this.is_loading = true;
        this.showLoader(true);
        self.showComplete(false);
        this.collection.fetch({
          remove: false,
          success: function(collection, response) {
            if (self.template_type == 'image' && self.collection.start_limit == 0) {
              if (self.collection.length > 0) {
                self.previewImageView.setModel(self.collection.at(0)).setElement(self.$(".ca_image_preview_container_body")).render();
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
          var formObj = $(this);
          var formURL = formObj.attr('action');
          $("#popup #close").hide();
          iframe.find('#loading').removeClass('hide');
          iframe.find('.wait_message').show().addClass('indicator_1');
          if (window.FormData !== undefined) { // for HTML5 browsers
            //  if(false)
            //prevent iframe from closing by escape key.
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
                  $("#popup #close").show().trigger('click');
                  self.editWithHtml(html);
                } else {
                  ca.helper.showFlash(data.error, true);
                }
              },
              error: function(jqXHR, textStatus, errorThrown) {
                ca.helper.showFlash('textStatus');
              }
            });
            e.preventDefault();
          }
        });
      });
    },
    showHtmlUpload: function() {
      this.$('#file_upload').trigger('click');
    },
    uploadFile: function() {
      var file = $('#file_upload')[0].files[0];
      if (typeof file == 'undefined') {
        return false;
      }
      if (!file.type.match('html.*') || !file.type.match('htm.*')) {
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
    createFromScratch: function() {
      this.unregisterAllTemplates();
      $('#edit_existing').hide();
      $('.lang_create_new_container').show();
      this.showCreateNew();
      this.fetchDefaultTemplates();
      $(".lang_option").show();
      $(".lang_option[option_lang_id=" + ca_ref.languages.base_language_id + "]").hide();
      $('#add_lang').css('display', 'inline');
      //this.newLangLinkVisibility() ;
    },
    addLanguage: function() {
      if (this.lang_view == null) {
        this.lang_view = new LanguageBasedTemplate({
          el: $("#ca_lang_based_parent_container"),
          languages: this.languages
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
        $('#lang_list').html('<li lang_id="' + base_language_id + '" class="lang_tab" >' + this.lang_view.languages.all_lang[base_language_id] + '</li> ');
        $(".create_new_template[template_lang_id=" + base_language_id + "]").show();
      }
    },
    showCreateNew: function() {
      this.$('.ca_top_view_container').hide();
      this.$('.ca_top_edit_container').hide();
      this.$('.ca_top_create_new_container').show();
      $('#back_to_coupon').hide();
      this.addLanguage();
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
    showView: function() {
      this.$('.ca_top_edit_container').hide();
      this.$('.ca_top_create_new_container').hide();
      this.$('.ca_top_view_container').show();
      $('#back_to_coupon').show();
      $('#ca_lang_based_parent_container').hide();
    },
    showImageUpload: function() {
      if (this.template_type == 'image') this.$('#upload_image').trigger('click');
    },
    uploadImage: function() {
      if (this.template_type == 'image') this.$("#image_upload").submit();
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
      //this.favourite_collection.add(model,{at:0, silent:true});
      var view = this.createTemplateView(model);
      this.$('.ca_all_container_body').prepend(this.getHtml(view));
      model.trigger('selected');
    },
    showAllLoader: function(show) {
      if (show) this.$('.all_wait_loader').addClass('intouch-loader').show();
      else this.$('.all_wait_loader').removeClass('intouch-loader').hide();
    },
    showDefaultLoader: function(show) {
      if (show) {
        this.$('.ca_default_loader').addClass('intouch-loader').show().parent('div').show();
      } else {
        this.$('.ca_default_loader').removeClass('intouch-loader').hide().parent('div').hide();
      }
    },
    showLoader: function(show) {
      if (show) {
        this.$('.ca_loader').addClass('intouch-loader').show().parent('div').show();
      } else {
        this.$('.ca_loader').removeClass('intouch-loader').hide().parent('div').hide();
      }
    },
    showComplete: function(show) {
      if (show) {
        this.$('.ca_complete_msg').show();
      } else {
        this.$('.ca_complete_msg').hide();
      }
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
        this.listenTo(view, 'duplicate_template', this.editTemplate);
      }
      return view;
    },
    editExistingTemplate: function(model, all_models) {
      this.$('.ca_top_edit_container').show();
      this.$('.ca_top_create_new_container').show();
      this.$('.ca_top_view_container').hide();
      $('#back_to_coupon').hide();
      $('#ca_lang_based_parent_container').show();
      if (this.lang_view != null) return;
      this.unregisterAllTemplates();
      this.createFromScratch();
      /*this.lang_view = new LanguageBasedTemplate({
                        el:$("#ca_lang_based_parent_container") ,
                       languages : this.languages
                       
                   }) ;                    
      this.lang_view.render() ;             
      */
      //this.addLanguage() ;
      if (all_models[ca_ref.languages.base_language_id]) {
        secondary_template_id = all_models[ca_ref.languages.base_language_id].secondary_template_id;
        model.set('secondary_template_id', secondary_template_id);
      }
      this.editTemplate(model, "edit", ca_ref.languages.base_language_id);
      var lang_ids = [];
      this.lang_view.added_languages = [];
      this.lang_view.addLanguage(ca_ref.languages.base_language_id);
      var self = this;
      this.lang_view.$('.lang_tab[lang_id=' + ca_ref.languages.base_language_id + ']').trigger('click');
      $.each(all_models, function(key, val) {
        if (key == ca_ref.languages.base_language_id) {
          return true;
        }
        tempModel = new TemplateModel(val);
        lang_ids.push(key);
        self.lang_view.addLanguage(key);
        self.editTemplate(tempModel, "edit", key);
      });
      this.lang_view.editLanguageTemplate(lang_ids);
      //this.createFromScratch() ;
      $("#lang_content_parent").show();
      $("#new_template_header").hide();
      $("#create_new_template_parent").show();
      $(".create_new_template").hide();
      $(".lang_tab[lang_id=" + ca_ref.languages.base_language_id + "]").addClass("selected_lang_tab");
      $(".template_editor[editor_lang_id=" + ca_ref.languages.base_language_id + "]").show();
      //$(".lang_tab").hide() ;
      //$(".lang_tab[lang_id="+ca_ref.languages.base_language_id+"],[lang_id="+lang_ids.join("],[lang_id=")+"]").show() ;
      $(".lang_option").show();
      $(".lang_option[option_lang_id=" + ca_ref.languages.base_language_id + "],[option_lang_id=" + lang_ids.join("],[option_lang_id=") + "]").hide();
      count = 0;
      for (var key in ca_ref.languages.all_lang) {
        count++;
      }
      if (count > self.lang_view.getLanguageAddedCount()) {
        $('#add_lang').css('display', 'inline');
      } else {
        $('#add_lang').css('display', 'none');
      }
      if (ca_ref.removed_tags.length > 0) {
        var remove_msg = ca_ref.removed_tags.join(', ');
        $('.remove_tags_msg').html(remove_msg);
        $('.remove_tags_modal').modal();
        ca_ref.removed_tags = [];
      }
    },
    removeAllLanguageTabs: function() {
      this.lang_view.added_languages = [];
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
        this.removeAllLanguageTabs();
      }
    },
    editMultiLangTemplate: function(model, type) {
      //purpose is to edit the existing templates
      //this.unregisterAllTemplates() ;
      this.createFromScratch();
      this.editTemplate(model, type, ca_ref.languages.base_language_id);
      var scope = 'ORG';
      var self = this;
      ca.helper.addWait();
      var url = '/xaja/AjaxService/assets/get_multi_lang_template.json?parent_template_id=' + model.get('template_id') + '&scope=' + scope;
      var lang_ids = [];
      var li_str = "";
      var total_lang = 0;
      var completed_lang_render = 0;
      var final_deferred = $.Deferred();
      this.lang_view.addLanguage(ca_ref.languages.base_language_id);
      $.getJSON(url, function(data) {
        ca.helper.removeWait();
        if (data.templates) {
          $.each(data.templates, function(key, val) {
            total_lang++;
            tempModel = new TemplateModel(val);
            lang_ids.push(val.language_id);
            li_str += '<li lang_id="' + val.language_id + '" class="lang_tab" >' + ca_ref.languages.all_lang[val.language_id] + '</li> ';
            self.lang_view.addLanguage(val.language_id);
            var status = $.Deferred();
            tempModel = tempModel.duplicateModel(status);
            (function(tempModel, type, language_id) {
              status.done(function() {
                self.editTemplate(tempModel, type, language_id);
                completed_lang_render++;
                if (completed_lang_render == total_lang) {
                  final_deferred.resolve();
                }
                $(".template_editor[editor_lang_id=" + language_id + "]").hide();
              });
            })(tempModel, type, val.language_id);
            //self.editTemplate(tempModel, type, val.language_id) ;    
          });
          self.lang_view.editLanguageTemplate(lang_ids);
          $("#lang_content_parent").show();
          $("#new_template_header").hide();
          $("#create_new_template_parent").hide();
          $(".create_new_template").hide();
          $(".template_editor[editor_lang_id=" + ca_ref.languages.base_language_id + "]").show();
          $(".lang_option").show();
          $(".lang_option[option_lang_id=" + ca_ref.languages.base_language_id + "],[option_lang_id=" + lang_ids.join("],[option_lang_id=") + "]").hide();
        }
        //self.newLangLinkVisibility() ;                
        count = 0;
        for (var key in ca_ref.languages.all_lang) {
          count++;
        }
        if (count > self.lang_view.getLanguageAddedCount()) {
          $('#add_lang').css('display', 'inline');
        } else {
          $('#add_lang').css('display', 'none');
        }
        $('.lang_tab[lang_id=' + ca_ref.languages.base_language_id + ']').trigger('click');
      });
      if (lang_ids.length == 0 && completed_lang_render == total_lang) {
        final_deferred.resolve();
      }
      final_deferred.done(function() {
        if (ca_ref.removed_tags.length > 0) {
          var remove_msg = ca_ref.removed_tags.join(', ');
          $('.remove_tags_msg').html(remove_msg);
          $('.remove_tags_modal').modal();
          ca_ref.removed_tags = [];
        }
      });
    },
    previewImage: function(model) {
      this.previewImageView.setModel(model).setElement(this.$(".ca_image_preview_container_body")).render();
    },
    getHtml: function(view) {
      return view.render().el;
    },
    removeTemplateview: function() {
      this.$(this.template_container).empty();
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
    checkScroll: function(container) {
      var triggerPoint = 100; // 100px from the bottom
      if (!this.is_loading && (container.scrollTop + container.clientHeight + triggerPoint) > (container.scrollHeight)) {
        // Load next page
        this.addTemplates();
      }
    },
    showMoreTemplates: function() {
      this.addTemplates();
    },
    editTemplate: function(model, type, lang_id) {
      // destroyViews: function () {
      //     _.invoke(this.views, 'destroy');
      //     this.views.length = 0;
      // },
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
      model.set({
        "language_id": lang_id
      });
      this.lang_view.langBasedEditTemplates[lang_id] = new this.editTemplateView({
        model: model,
        el: this.$(".template_editor[editor_lang_id=" + lang_id + "]"),
        edit_type: type
      });
      ca.helper.addWait();
      this.lang_view.langBasedEditTemplates[lang_id].render();
      ca.helper.removeWait();
      this.lang_view.langBasedEditTemplates[lang_id].$(".lang_enabled_hide").hide();
      this.lang_view.langBasedEditTemplates[lang_id].$(".lang_enabled_show").show();
      if (this.lang_view.langBasedEditTemplates[lang_id].editor_name == 'inline_ck') {
        this.lang_view.langBasedEditTemplates[lang_id].$(".edit_as_html").hide();
        this.lang_view.langBasedEditTemplates[lang_id].$(".change_to_classic_editor").removeClass("hide");
      } else {
        this.lang_view.langBasedEditTemplates[lang_id].$(".edit_as_html").show();
        this.lang_view.langBasedEditTemplates[lang_id].$(".change_to_classic_editor").addClass("hide");
      }
      if (lang_id == ca_ref.languages.base_language_id) {
        ca_ref.existing_model = model;
        this.lang_view.langBasedEditTemplates[lang_id].$('.remove_language_list').hide();
        this.lang_view.langBasedEditTemplates[lang_id].$('.template_options_text').text(_campaign("Main Language - Sent as an Email"));
      } else {
        this.lang_view.langBasedEditTemplates[lang_id].$('.remove_language_list').show();
        this.lang_view.langBasedEditTemplates[lang_id].$('.template_options_text').text(_campaign("Sec. Language - Stored as an URL"));
      }
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
    showEdit: function() {
      this.$('.ca_top_create_new_container').hide();
      this.$('.ca_top_view_container').hide();
      this.$('.ca_top_edit_container').show();
      $('#back_to_coupon').show();
      eventBus.trigger('render_next_header');
    },
    insertImage: function(url) {
      this.trigger('insert_image', url)
    }
  });
  var MessageDesignEditTemplateView = ca.MessageDesignEditTemplateView = Backbone.View.extend({
    initialize: function(options) {
      this.tpl = _.template($('#msg_edit_template_tpl').html());
      this.image_gallery_view = new ImageGalleryView();
      this.listenTo(this.image_gallery_view, 'insert_image', this.insertImage);
      this.option_set = {
        inline_ck: [{
          className: 'image_gallery',
          label: _campaign('Insert Image')
        }, {
          className: 'preview_and_test',
          label: _campaign('Preview and Test')
        }, {
          className: 'change_to_classic_editor',
          label: _campaign('Change to Classic Editor')
        }, {
          className: 'save_original_template',
          label: _campaign('Save to Original Template')
        }, {
          className: 'save_new_template',
          label: _campaign('Save as New Template')
        }],
        classic_ck: [{
          className: 'image_gallery',
          label: _campaign('Insert Image')
        }, {
          className: 'preview_and_test',
          label: _campaign('Preview and Test')
        }, {
          className: 'change_to_inline_editor',
          label: _campaign('Change to Inline Editor')
        }, {
          className: 'save_original_template',
          label: _campaign('Save to Original Template')
        }, {
          className: 'save_new_template',
          label: _campaign('Save as New Template')
        }],
        edm: [{
          className: 'image_gallery',
          label: _campaign('Insert Image')
        }, {
          className: 'preview_and_test',
          label: _campaign('Preview and Test')
        }, {
          className: 'edit_as_html',
          label: _campaign('Edit as HTML')
        }, {
          className: 'save_original_template',
          label: _campaign('Save to Original Template')
        }, {
          className: 'save_new_template',
          label: _campaign('Save as New Template')
        }]
      };
      var drag_drop_id = this.model.get('drag_drop_id');
      var is_drag_drop = this.model.get('is_drag_drop');
      if (is_drag_drop && drag_drop_id) {
        this.editor_name = 'edm';
      } else {
        this.editor_name = 'inline_ck';
      }
      this.edit_type = options.edit_type;
      this.saved = false;
      this.sender_info = ca_ref.sender_info;
      if (ca_ref.existing_template_data) {
        this.subject = ca_ref.existing_template_data.base_template.subject;
      } else {
        this.subject = '';
      }
      this.edm_changed = false;
    },
    render: function() {
      this.$el.html(this.tpl({
        options: this.option_set[this.editor_name],
        model: this.model.toJSON(),
        sender_info: this.sender_info,
        subject: this.subject
      }));
      this.renderEditor();
      if (!this.model.get('template_id')) this.$('.save_original_template').hide();
      return this;
    },
    events: {
      'click .edit_from': 'showFromAddressModal',
      'focus #edit_template__subject': 'setFocus',
      'change .subject': 'saveSubject',
      'click .image_gallery': 'openImageGallery',
      'click .preview_and_test': 'previewAndTestTemplate',
      'click .change_to_classic_editor': 'changeToClassicEditor',
      'click .change_to_inline_editor': 'changeToInlineEditor',
      'click .edit_as_html': 'showConfirmEdit',
      'click .confirm_edit': 'editAsHtml',
      'change .confirm_box': 'enableContinue',
      'click .save_original_template': 'openConfirmSave',
      'click #confirm_save': 'saveOriginalTemplate',
      'click .save_new_template': 'openNameModal',
      'click #save_template': 'saveNewTemplate',
      'click #save_edit_new': 'saveEditNewTemplate',
      'click #skip_edit_new': 'skipEditNewTemplate',
      'click .preview': 'previewAndTestTemplate'
    },
    showFromAddressModal: function() {
      this.$(".from_address_modal").modal();
      if (this.sender_info.sender_details && this.sender_info.sender_details.length <= 1) {
        this.$(".from_sender_name").val(this.sender_info.sender_label);
        this.$(".sender_id_select").val(this.sender_info.sender_from);
      }
    },
    saveNameAddress: function() {
      var name = '',
        address = '';
      if (this.sender_info.sender_details && this.sender_info.sender_details.length > 1) {
        var idx = this.$(".sender_details_idx").val();
        name = this.sender_info.sender_details[idx].sender_label;
        address = this.sender_info.sender_details[idx].sender_email;
      } else {
        name = this.$(".from_sender_name").val();
        address = this.$(".sender_id_select").val();
      }
      if (!name && !address) return false;
      this.$('.from_name').html(name);
      this.$('.from_email').html(address);
      this.sender_info.sender_label = name;
      this.sender_info.sender_from = address;
      this.$('.from_address_modal').modal('hide');
    },
    setFocus: function() {
      this.editTemplateView.insert_focus = 'subject';
    },
    saveSubject: function(e) {
      this.subject = this.$(e.currentTarget).val();
    },
    openImageGallery: function() {
      this.image_gallery_view.setElement($('.image_gallery_container')).render();
      $('#image_gallery_modal').modal('show');
    },
    previewAndTestTemplate: function() {
      var self = this;
      var html = "<html>" + $("#iedit_template__template_holder").contents().find("html").html() + "</html>";
      if (html.length > 102400) {
        this.model.showError(_campaign('Email size is greater than 100 KB'));
        return false;
      }
      this.model.setHtml().done(function() {
        self.editTemplateView.setHtmlToIframe(self.model.get('html_content'));
        var campaign_id = $('#campaign_id').val();
        showPopup("/campaign/messages/v2/PreviewAndTest?campaign_id=" + campaign_id);
        $('#popupiframe').css('height', '95%');
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
      if (ca_ref.languages.base_language_id == selected_lang_id) {
        self.$('.remove_language_list').hide();
        self.$('.template_options_text').text(_campaign("Main Language - Sent as an Email"));
      } else {
        self.$('.remove_language_list').show();
        self.$('.template_options_text').text(_campaign("Sec. Language - Stored as URL"));
      }
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
      status.done(function() {
        self.model.set({
          is_drag_drop: false,
          drag_drop_id: ''
        });
        self.editor_name = 'inline_ck';
        self.edm_changed = true;
        self.render();
        self.$('.change_to_classic_editor').first().removeClass('hide');
        self.$('.edit_as_html').first().addClass('hide');
        selected_lang_id = $('.selected_lang_tab').attr('lang_id');
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
    openConfirmSave: function() {
      if (this.edm_changed) {
        this.$('.non_edm_msg').hide();
        this.$('.edm_msg').show();
        this.$('.change_edm_save').show();
      }
      this.$('.confirm_save_modal').modal('show');
    },
    saveOriginalTemplate: function() {
      this.model.saveTemplate();
      this.saved = true;
    },
    openNameModal: function() {
      this.model.set('template_id', "");
      this.$("#rename_template").val();
      this.$("#edit_name_modal").modal();
    },
    saveNewTemplate: function() {
      var name = this.$("#rename_template").val();
      if (!name) return false;
      this.model.set('name', name);
      this.model.set('template_id', '');
      this.model.set('is_favourite', false);
      this.model.saveTemplate();
      this.$('.save_original_template').show();
      this.saved = true;
      this.$('#edit_name_modal').modal('hide');
    },
    saveEditNewTemplate: function() {
      var self = this;
      var name = this.$("#edit_new_name").val();
      if (!name) return false;
      this.model.set({
        name: name,
        template_id: ''
      });
      this.$('#save_edit_new_modal').modal('hide');
      this.model.saveTemplate().done(function() {
        self.getOutput(self.returnResult);
      });
    },
    skipEditNewTemplate: function() {
      this.$('#save_edit_new_modal').modal('hide');
      this.getOutput(this.returnResult);
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
      var processed_tags = changeTags(ca_ref.tags['tags']);
      var social = ca_ref.tags['social'];
      var inserts = ca_ref.tags['inserts'];
      var editor_model = new EditorModel({
        tags: processed_tags,
        inserts: inserts,
        social: social,
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
      this.addAutoSuggest('edit_template__subject');
    },
    addAutoSuggest: function(name) {
      var url = '/xaja/AjaxService/messages/subject_autosuggest';
      var as_json = new bsn.AutoSuggest(name, {
        script: url,
        varname: '/',
        json: true, // Returned response type
        shownoresults: true, // If disable, display nothing if no results
        noresults: _campaign('No Results'), // String displayed when no results
        maxresults: 5, // Max num results displayed
        comma: false, // Is comma Separated
        cache: true, // To enable cache
        minchars: 7, // Start AJAX request with at least 5 chars
        timeout: 100000, // AutoHide in XX ms
        callback: function(obj) { // Callback after click or selection
        }
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
    showEditLoader: function(show) {
      if (show) {
        this.$('.edit_template_loader').addClass('intouch-loader').show();
      } else {
        this.$('.edit_template_loader').removeClass('intouch-loader').hide();
      }
    },
    backToView: function() {
      this.$el.hide();
      this.$el.siblings('.ca_top_view_container').show();
    },
    setModel: function(model) {
      this.model = model;
    },
    insertImage: function(url) {
      this.editTemplateView.insertImage(url);
    },
    goToNext: function(result) {
      if (!this.subject) {
        ca.helper.showFlash(_campaign('Please provide an appropriate subject to proceed!'), true);
        this.$('#edit_template__subject').focus();
        return false;
      } else {
        if (!this.model.get('template_id')) {
          this.$('#save_edit_new_modal').modal('show');
          this.returnResult = result;
        } else {
          result = this.getOutput(result);
        }
      }
    },
    getOutput: function(result) {
      var self = this;
      this.model.setHtml().done(function() {
        self.editTemplateView.setHtmlToIframe(self.model.get('html_content'));
        subject = self.$('#edit_template__subject').val();
        result.output = {
          "html_content": self.model.get('html_content'),
          subject: subject,
          sender_info: self.sender_info,
          template: self.model.toJSON()
        };
        result.status.resolve();
      });
    }
  });
  var EditorModel = ca.EditorModel = Backbone.Model.extend({
    defaults: function() {
      return {
        tags: [],
        social: {},
        inserts: {},
        template_model: undefined
      };
    }
  });
  var EditTemplateView = ca.EditTemplateView = Backbone.View.extend({
    editor_name: 'edm',
    insert_focus: 'editor',
    initialize: function(options) {
      this.tpl = _.template($('#edit_template_tpl').html());
      var self = this;
      this.template_model = this.model.get('template_model');
      this.editor_name = options.editor_name;
      $(window).blur(function() {
        self.changeFocusToEditor();
      });
    },
    render: function() {
      this.$el.html(this.tpl(this.model.toJSON()));
      //var template_model =  this.model.get('template_model');
      this.setEditor(this.editor_name);
      html = this.template_model.get('html_content');
      this.addColorToSubscribe();
      this.setHtmlToIframe(html);
      this.assignDiv();
      return this;
    },
    events: {
      'click .tags-container li.parent > span': 'openSubTag',
      'click .insert_tag': 'insertTagToEditor',
      "click div.content-box-small": "addContent",
      "click li.social-media": "openSocial",
      "click a#save_url": "addSocialMedia",
      "click a#cancel_url": "cancelSocialMedia"
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
                var someText = $(some).text().trim();
                self.$(e.currentTarget).parent().find('ul').append("<li class = 'tag-list'><span><a class='insert_tag' tag-data='{{Link_to_" + someText + "}}'>" + someText + "</a></span></li>");
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
        var linkContent = prompt("Provide language link", defaultText);
        if (linkContent != null) {
          tag = "<a href='" + tag + "'>" + linkContent + "</a>";
        } else {
          tag = "";
        }
      }
      bits = bits.split('_');
      if (bits[0] == 'dynamic') {
        var str = $(e.currentTarget).position().top;
        str = str - ($(e.currentTarget).height());
        str = str + 'px';
        //document.getElementById('dTags').style.top=str;
        this.$("#dTags").css({
          top: str
        });
        this.$('.dynamicTags').toggleClass('hide');
        this.$('#dynamicButton').unbind('click').click(function(event) {
          if (self.$("#dynamicTextbox").val().length == 0) {
            $('.flash_message').show().addClass('redError').html(_campaign("Enter The Number Of Days"));
            setTimeout(function() {
              $('.flash_message').fadeOut('fast');
            }, 3000);
            return false;
          }
          var regexp1 = new RegExp("[^0-9]");
          if (regexp1.test(document.getElementById("dynamicTextbox").value)) {
            $('.flash_message').show().addClass('redError').html(_campaign("Only Numbers Are Allowed"));
            setTimeout(function() {
              $('.flash_message').fadeOut('fast');
            }, 3000);
            self.$('#dynamicTextbox').val('');
            return false;
          }
          tag = tag.replace("(N)", self.$("#dynamicTextbox").val());
          self.editorView.insertTag(tag, parent_element);
          self.$('#dynamicTextbox').val('');
          self.$('.dynamicTags').toggleClass('hide');
        });
        return true;
      }
      if (this.insert_focus == 'subject') {
        var cursorPos = $('#edit_template__subject').prop('selectionStart');
        var value = $('#edit_template__subject').val();
        var before = value.substring(0, cursorPos);
        var after = value.substring(cursorPos, value.length);
        $('#edit_template__subject').val(before + tag + after).trigger('change');
      } else {
        this.editorView.insertTag(tag, parent_element);
      }
    },
    addContent: function(e) {
      var self = this;
      if ($(e.currentTarget).attr('type') == 'IMAGE') {
        var image = "<img src='" + $(e.currentTarget).children('img').attr('src') + "' />";
        self.editorView.insertTag(image);
      } else if ($(e.currentTarget).attr('type') == 'SURVEY') {
        var form_id = $(e.currentTarget).attr('template-id');
        var campaign_id = $('#campaign_id').val();
        var ajaxUrl = '/xaja/AjaxService/campaign_v2/get_survey_form.json?form_id=' + form_id + '&campaign_id=' + campaign_id;
        ca.helper.addWait();
        var that = this;
        $.getJSON(ajaxUrl, function(data) {
          var html_data = data.info;
          ca.helper.removeWait();
          if (html_data) {
            html_data = html_data.replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&quot;/g, "\"");
            self.editorView.insertTag(html_data);
          }
        });
      } else {
        var id = $(e.currentTarget).attr('template-id');
        var ajaxUrl = '/xaja/AjaxService/assets/msg_preview.json?template_id=' + id;
        ca.helper.addWait();
        var that = this;
        $.getJSON(ajaxUrl, function(data) {
          var html_data = data.info;
          html_data = html_data.replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&quot;/g, "\"");
          ca.helper.removeWait();
          self.editorView.insertTag(html_data);
        });
      }
    },
    openSocial: function(e) {
      var pos = $(e.currentTarget).position();
      var width = $(e.currentTarget).width();
      var url = $(e.currentTarget).attr('url');
      $('#add-social-media').removeClass('hide');
      $('#add-social-media').css('top', pos.top + 85);
      $('#add-social-media').css('left', pos.left + 200);
      $('a#save_url').attr('open_id', $(e.currentTarget).attr('id'));
      $('a#save_url').attr('img_url', $(e.currentTarget).attr('img_url'));
      $('input#url').val(url);
    },
    addSocialMedia: function(e) {
      var self = this;
      var pattern = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
      var url = $('input#url').val();
      var open_id = $(e.currentTarget).attr('open_id');
      var img = $(e.currentTarget).attr('img_url');
      if (pattern.test(url)) {
        var ajaxUrl = "/xaja/AjaxService/campaign/save_social_url.json?myurl=" + url + '&platform=' + open_id;
        if ($('#set_url').is(':checked')) {
          $.getJSON(ajaxUrl, function(data) {
            checkSessionExpiry(data);
            if (data.status == 'SUCCESS') {
              $('.flash_message').css('display', 'inline');
              $('div#add-social-media').addClass('hide');
              ca.helper.showFlash(_campaign('Social URL saved successfully'), false);
              var image_link = "<a href='" + url + "' target='_blank'><img src='" + decodeURIComponent(img) + "'></img</a>";
              self.editorView.insertTag(image_link);
              $("#" + open_id).attr("url", url);
            } else {
              ca.helper.showFlash(_campaign('Error: We Were Unable to process your request please try again later'), true);
            }
          });
        } else {
          var image_link = "<a href='" + url + "' target='_blank'><img src='" + decodeURIComponent(img) + "'></img</a>";
          self.editorView.insertTag(image_link);
          this.$('div#add-social-media').addClass('hide');
        }
      } else ca.helper.showFlash('Url not valid', true);
    },
    cancelSocialMedia: function() {
      $('div#add-social-media').addClass('hide');
      $('input#url').val('');
    },
    addColorToSubscribe: function() {
      var self = this;
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
            str = $(this).attr('id');
            if (str.indexOf("unsub") >= 0) {
              $('#sp-unsub-colorpicker-div_' + self.model.attributes.template_model.get('language_id')).css('background-color', color.toHexString());
              tag.attr('tag-data', '{{unsubscribe(' + color.toHexString() + ')}}');
            } else {
              $('#sp-sub-colorpicker-div_' + self.model.attributes.template_model.get('language_id')).css('background-color', color.toHexString());
              tag.attr('tag-data', '{{subscribe(' + color.toHexString() + ')}}');
            }
            //$('#sp-unsub-colorpicker-div').css('background-color', color.toHexString());
            //tag.attr('tag-data', '{{unsubscribe(' + color.toHexString() + ')}}');
            //alert("Selected Color :"+color.toHexString());
          },
          palette: [
            ["rgb(0, 0, 0)", "rgb(67, 67, 67)", "rgb(102, 102, 102)", "rgb(204, 204, 204)", "rgb(217, 217, 217)", "rgb(255, 255, 255)"],
            ["rgb(152, 0, 0)", "rgb(255, 0, 0)", "rgb(255, 153, 0)", "rgb(255, 255, 0)", "rgb(0, 255, 0)", "rgb(0, 255, 255)", "rgb(74, 134, 232)", "rgb(0, 0, 255)", "rgb(153, 0, 255)", "rgb(255, 0, 255)"],
            ["rgb(230, 184, 175)", "rgb(244, 204, 204)", "rgb(252, 229, 205)", "rgb(255, 242, 204)", "rgb(217, 234, 211)", "rgb(208, 224, 227)", "rgb(201, 218, 248)", "rgb(207, 226, 243)", "rgb(217, 210, 233)", "rgb(234, 209, 220)", "rgb(221, 126, 107)", "rgb(234, 153, 153)", "rgb(249, 203, 156)", "rgb(255, 229, 153)", "rgb(182, 215, 168)", "rgb(162, 196, 201)", "rgb(164, 194, 244)", "rgb(159, 197, 232)", "rgb(180, 167, 214)", "rgb(213, 166, 189)", "rgb(204, 65, 37)", "rgb(224, 102, 102)", "rgb(246, 178, 107)", "rgb(255, 217, 102)", "rgb(147, 196, 125)", "rgb(118, 165, 175)", "rgb(109, 158, 235)", "rgb(111, 168, 220)", "rgb(142, 124, 195)", "rgb(194, 123, 160)", "rgb(166, 28, 0)", "rgb(204, 0, 0)", "rgb(230, 145, 56)", "rgb(241, 194, 50)", "rgb(106, 168, 79)", "rgb(69, 129, 142)", "rgb(60, 120, 216)", "rgb(61, 133, 198)", "rgb(103, 78, 167)", "rgb(166, 77, 121)", "rgb(91, 15, 0)", "rgb(102, 0, 0)", "rgb(120, 63, 4)", "rgb(127, 96, 0)", "rgb(39, 78, 19)", "rgb(12, 52, 61)", "rgb(28, 69, 135)", "rgb(7, 55, 99)", "rgb(32, 18, 77)", "rgb(76, 17, 48)"]
          ]
        });
      };
      this.$('.insert_tag').each(function() {
        if ($(this).attr('tag-data').indexOf('{{unsubscribe(') !== -1) {
          $(this).append('<div class="unsub-colorpicker">' + '<div id="sp-unsub-colorpicker-div_' + self.template_model.get('language_id') + '" class="sp-colorpicker-div"></div>' + '<input type="text" id="unsub-colorpicker-input_' + self.template_model.get('language_id') + '">' + '</div>');
          updateColorTag('#unsub-colorpicker-input_' + self.template_model.get('language_id'), $(this))
        }
        if ($(this).attr('tag-data').indexOf('{{subscribe(') !== -1) {
          $(this).append('<div class="unsub-colorpicker">' + '<div id="sp-sub-colorpicker-div_' + self.template_model.get('language_id') + '" class="sp-colorpicker-div"></div>' + '<input type="text" id="sub-colorpicker-input_' + self.template_model.get('language_id') + '">' + '</div>');
          var that = $(this);
          updateColorTag('#sub-colorpicker-input_' + self.template_model.get('language_id'), $(this));
        }
      });
    },
    setHtmlToIframe: function(html_content, selector) {
      var iframe = $("#iedit_template__template_holder");
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
    },
    assignDiv: function() {
      var count = 1;
      var divs = this.$("#iedit_template__template_holder").contents().find('.cap-email-block');
      divs.each(function() {
        $(this).attr('id', 'div_' + count);
        count++;
      })
    },
    setEditor: function(name) {
      var self = this;
      var template_model = this.model.get('template_model');
      this.showEditLoader(true);
      if (this.editorView) {
        this.editorView.$el.empty().off();
        this.editorView.stopListening();
        this.editorView.undelegateEvents();
      }
      if (name == 'inline_ck') {
        template_model.setHtml().done(function() {
          self.removeUnsupportedTags(template_model);
          self.showEditLoader(false);
          self.editorView = new InlineCKEditorView({
            model: template_model
          });
          self.editorView.setElement(self.$('.ca-edit-right-panel')).render();
          self.listenTo(self.editorView, 'set_html', self.setHtmlToIframe)
        })
      } else if (name == 'classic_ck') {
        template_model.setHtml().done(function() {
          self.removeUnsupportedTags(template_model);
          self.showEditLoader(false);
          self.editorView = new ClassicCKEditorView({
            model: template_model
          });
          self.editorView.setElement(self.$('.ca-edit-right-panel')).render();
          self.listenTo(self.editorView, 'set_html', self.setHtmlToIframe)
        });
      } else {
        this.showEditLoader(false);
        this.editorView = new EdmEditorView({
          model: template_model
        });
        this.editorView.setElement(this.$('.ca-edit-right-panel')).render();
      }
    },
    changeFocusToEditor: function() {
      this.insert_focus = 'editor';
    },
    showEditLoader: function(show) {
      this.trigger('show_loader', show);
    },
    removeUnsupportedTags: function(template_model) {
      html = template_model.get('html_content');
      var pattern = /{{(.*?)}}/i;
      var tags = this.model.get('tags');
      var aggregateTags = function(tags, tags_list) {
        _.each(tags, function(tag) {
          if (tag.val) {
            var res = pattern.exec(tag.val);
            if (res && res[0]) {
              tags_list.push(res[0]);
            }
          } else if (tag.children) {
            aggregateTags(tag.children, tags_list);
          }
        })
      };
      var tags_list = [];
      aggregateTags(tags, tags_list);
      var removed_tags = [];
      var remove_survey_tags = [];
      pattern = /{{(.*?)}}/gi;
      var html_replaced = html.replace(pattern, function(tag) {
        if (tag.indexOf('{{unsubscribe(') !== -1 || tag.indexOf('{{user_id_b64}}') !== -1 || tag.indexOf('{{outbox_id_b64}}') !== -1 || tag.indexOf('{{Link_to_') !== -1) {
          return tag;
        } else if (tags_list.indexOf(tag) == -1) {
          if (ca_ref.removed_tags.indexOf(tag) == -1) {
            ca_ref.removed_tags.push(tag);
          }
          if (tag.indexOf('SURVEY__TOKEN') != -1) {
            remove_survey_tags.push(tag);
            return tag;
          }
          return '';
        } else {
          return tag;
        }
      });
      remove_survey_tags.forEach(function(tag) {
        var idx = html_replaced.indexOf(tag);
        var l_idx = idx + tag.length;
        var s_idx = html_replaced.lastIndexOf('http', l_idx);
        html_replaced = html_replaced.substring(0, s_idx) + html_replaced.substring(l_idx, html_replaced.length);
      })
      template_model.set('html_content', html_replaced);
      /*if (removed_tags.length > 0) {
          var remove_msg = removed_tags.join(', ');
          $('.remove_tags_msg').append(remove_msg) ;
          //this.$('.remove_tags_msg').html(remove_msg);
          //this.$('.remove_tags_modal').modal();
      }*/
    },
    insertImage: function(url) {
      this.editorView.insertImage(url);
    }
  });
  var ClassicCKEditorView = ca.ClassicCKEditorView = Backbone.View.extend({
    initialize: function() {
      this.tpl = _.template($('#classic_ck_editor_tpl').html());
      this.editor = undefined;
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
      this.editor.on('focus', function(evt) {
        self.trigger('focus_editor');
      });
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
      return this;
    },
    setModel: function(model) {
      this.model = model;
    },
    insertTag: function(tag) {
      this.editor.insertHtml(tag);
    },
    insertHtml: function(html) {
      this.editor.insertHtml(html);
    },
    insertImage: function(url) {
      var img = '<img src="' + url + '"/>';
      this.editor.insertHtml(img);
    }
  });
  var InlineCKEditorView = ca.InlineCKEditorView = Backbone.View.extend({
    initialize: function() {
      var self = this;
      this.edit_mode = 'single';
      this.tpl = _.template($('#inline_ck_editor_tpl').html());
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
            allowedContent: true,
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
          allowedContent: true,
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
    initialize: function() {
      this.tpl = _.template($('#edm_editor_tpl').html());
      self.url = '';
    },
    render: function() {
      this.$el.html(this.tpl());
      $('.image_gallery').hide();
      var self = this;

      function openEdmEditor() {
        ca_ref.edmDesignerApi.openProject(self.model.get('drag_drop_id'), 'en', function(result) {
          self.url = result.url;
          self.openEditor(result.url);
        }, function(error) {});
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
  var ImageGalleryView = ca.ImageGalleryView = Backbone.View.extend({
    initialize: function() {
      this.tpl = _.template($('#image_gallery_tpl').html());
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
    render: function() {
      this.initialize();
      this.$el.html(this.tpl(this.option));
      this.template_container_view.setElement(this.$('.ca-image-collection-container')).render();
      return this;
    }
  });
  return creativeAssets;
});