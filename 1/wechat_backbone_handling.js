var step1_view, step2_view, step3_view, step4_view;
$(document).ready(function() {
  window.onbeforeunload = function() {
    return _campaign("You will lose all progress made.");
  }
  var campaign_id = $('#campaign_id').val();
  var message_id = $('#message_id').val();
  timestamp = (new Date).getTime();
  //step1_view = new recipientView({el:$('#container_1')});
  step2_view = new couponSeriesView({
    el: $('#container_2')
  });
  step3_view = new templateView({
    el: $('#container_3')
  });
  step4_view = new editTempalteView({
    el: $('#container_4')
  });
  step5_view = new deliveryView({
    el: $('#container_5')
  });
  ajaxModel1 = new ajaxModel();
  // messagesModel = new ajaxModel({id:"recipient.json"});
  ajaxModel1.addWait();
  ajaxModel1.clearCacheKeys(window.name);
  window.name = timestamp;
  ajaxModel1.setCache('campaign_id', campaign_id);
  ajaxModel1.setCache('message_id', message_id);
  ajaxModel1.removeWait();
  messagesModel = new MessageCollection();
  messagesModel.set({
    'campaign_id': campaign_id
  });
  messagesModel.fetch({
    data: {
      campaign_id: campaign_id,
      message_id: message_id
    }
  });
  parentViewInstance = new MessageParentView({
    el: $('#container_1'),
    collection: messagesModel
  });
  parentViewInstance.render();
  $('.formError').live('click', function() {
    $(this).remove();
  });
});

function slidePage($pageTrigger) {
  PageTransitions.Animate($pageTrigger);
}
//----------------------------Campaign SMS Flow Model -------------------------------
ajaxModel = Backbone.Model.extend({
  addWait: function() {
    $('.wait_message').show().addClass('indicator_1');
  },
  removeWait: function() {
    $('.wait_message').hide().removeClass('indicator_1');
  },
  showError: function(msg) {
    $('.flash_message').show().addClass('redError').html(msg);
    setTimeout(function() {
      $('.flash_message').removeClass('redError').fadeOut('fast');
    }, 3000);
  },
  showMessage: function(msg) {
    $('.flash_message').removeClass('redError').show().html(msg);
    setTimeout(function() {
      $('.flash_message').fadeOut('fast');
    }, 3000);
  },
  urlRoot: '/xaja/AjaxService/messages',
  checkSessionExpire: function() {
    var ajaxUrl = this.urlRoot + '/check_session.json';
    $.getJSON(ajaxUrl, function(data) {
      checkSessionExpiry(data);
    });
  },
  setCache: function(key, value) { //Setting variable in cache
    key = key + "_" + timestamp;
    localStorage.setItem(key, value);
  },
  getCache: function(key) { //get variable from cache
    key = key + "_" + timestamp;
    return localStorage.getItem(key);
  },
  clearCache: function(key) {
    key = key + "_" + timestamp;
    localStorage.removeItem(key);
  },
  clearCacheKeys: function(uniqeId) {
    var appender = timestamp;
    if (uniqeId != undefined) {
      appender = uniqeId;
    }
    var keys = ["campaign_id", "message_id", "sender_label", "sender_from", "list_type", "group_selected", "subject", "message", "plain_text", "hidden_plain_text", "voucher_series_id", "coupon_attached", "customer_count", "points", 'allocation_strategy_id', 'expiry_strategy_id', 'program_id', 'till_id', 'promotion_id'];
    $.each(keys, function(i, val) {
      localStorage.removeItem(val);
      localStorage.removeItem(val + "_" + appender);
    });
  }
});
//Message Model
ajaxMessageModel = Backbone.Model.extend({
  defaults: {
    html_text: '',
    is_selected: 0,
    is_details: 0,
    rule1: 1,
    rule2: 1,
    subject: '',
    plain_text: '',
    showMore: 0,
    radioSelected: 0
  },
  urlRoot: '/xaja/AjaxService/messages'
});
MessageCollection = Backbone.Collection.extend({
  url: '/xaja/AjaxService/messages/recipient.json',
  model: ajaxMessageModel,
  merge: function(obj1, obj2) {
    var obj3 = {};
    for (var attrname in obj1) {
      obj3[attrname] = obj1[attrname];
    }
    for (var attrname in obj2) {
      obj3[attrname] = obj2[attrname];
    }
    return obj3;
  },
  parse: function(resp) {
    var recipients1 = resp.item_data.non_sticky;
    var recipients2 = resp.item_data.sticky;
    var recipients = this.merge(recipients1, recipients2);
    var count_sticky = 1;
    var count = 1;
    return Object.keys(recipients).map(function(key) {
      if (recipients[key].listype == 'sticky') {
        recipients[key].data_size = Object.keys(recipients2).length;
        recipients[key].item_data_count = count_sticky;
        count_sticky = count_sticky + 1;
      } else {
        recipients[key].data_size = Object.keys(recipients1).length;
        recipients[key].item_data_count = count;
        count = count + 1;
      }
      recipients[key].custom_sender = resp.custom_sender;
      recipients[key].message_data = resp.message_data;
      return recipients[key];
    });
  },
  showError: function(msg) {
    $('.flash_message').show().addClass('redError').html(msg);
    setTimeout(function() {
      $('.flash_message').removeClass('redError').fadeOut('fast');
    }, 3000);
  },
  showMessage: function(msg) {
    $('.flash_message').removeClass('redError').show().html(msg);
    setTimeout(function() {
      $('.flash_message').fadeOut('fast');
    }, 3000);
  },
  urlRoot: '/xaja/AjaxService/messages',
  setCache: function(key, value) { //Setting variable in cache
    key = key + "_" + timestamp;
    localStorage.setItem(key, value);
  },
  getCache: function(key) { //get variable from cache
    key = key + "_" + timestamp;
    return localStorage.getItem(key);
  },
  clearCache: function(key) {
    key = key + "_" + timestamp;
    localStorage.removeItem(key);
  },
  addWait: function() {
    $('.wait_message').show().addClass('indicator_1');
  },
  removeWait: function() {
    $('.wait_message').hide().removeClass('indicator_1');
  },
  checkSessionExpire: function() {
    var ajaxUrl = this.urlRoot + '/check_session.json';
    $.getJSON(ajaxUrl, function(data) {
      checkSessionExpiry(data);
    });
  },
  clearCacheKeys: function(uniqeId) {
    var appender = timestamp;
    if (uniqeId != undefined) {
      appender = uniqeId;
    }
    var keys = ["campaign_id", "message_id", "sender_label", "sender_from", "list_type", "group_selected", "subject", "message", "plain_text", "hidden_plain_text", "voucher_series_id", "coupon_attached", "customer_count", "points", 'allocation_strategy_id', 'expiry_strategy_id', 'program_id', 'till_id', 'promotion_id', 'is_drag_drop', 'drag_drop_id'];
    $.each(keys, function(i, val) {
      localStorage.removeItem(val);
      localStorage.removeItem(val + "_" + appender);
    });
  }
});
//---------------------------- campaign sms flow recipient list view ----------------------------
recipientView = Backbone.View.extend({
  initialize: function() {
    this.listenTo(this.model, 'change', this.render);
  },
  renderFn: function(model) {
    this.model = model;
    this.render();
  },
  render: function() {
    if (this.model.get('listype') == 'sticky') {
      this.renderSticky();
    } else {
      this.renderNonSticky();
    }
  },
  renderNonSticky: function() {
    var custom_sender = this.model.get('custom_sender');
    $('#sender_label').val(custom_sender.sender_gsm);
    $('#sender_from').val(custom_sender.sender_cdma);
    if (this.model.get('data_size') > 4) $('.campaign_list_radio').find('.show_more_campaign_list_radio').removeClass('hide');
    var msg_data = this.model.get('message_data');
    if (msg_data != undefined) {
      $('#sender_label').val(msg_data.sender_gsm);
      $('#sender_from').val(msg_data.sender_cdma);
      $('#allocation_id').val(msg_data.allocation_strategy_id);
      $('#expiry_id').val(msg_data.expiry_strategy_id);
      $('#program_id').val(msg_data.program_id);
      $('#promotion_id').val(msg_data.till_id);
      $('#till_id').val(msg_data.promotion_id);
      $('#coupon_series_id').val(msg_data.voucher_series);
      $('#template_selected').val(msg_data.template_id);
      $("#msg_template_type").val(msg_data.msg_type);
    }
    var template = _.template($("#recipient_vlist").html(), {
      group: this.model.toJSON(),
      msg_data: msg_data,
      is_sms: 1,
      is_wechat: 1,
      account_id: $('#account_id').val()
    });
    this.$el.html(template);
    return this;
  },
  renderSticky: function() {
    var custom_sender = this.model.get('custom_sender');
    $('#sender_label').val(custom_sender.sender_gsm);
    $('#sender_from').val(custom_sender.sender_cdma);
    if (this.model.get('data_size') > 4) $('.campaign_list').find('.show_more_campaign_list_check').removeClass('hide');
    var msg_data = this.model.get('message_data');
    if (msg_data != undefined) {
      $('#sender_label').val(msg_data.sender_gsm);
      $('#sender_from').val(msg_data.sender_cdma);
      $('#allocation_id').val(msg_data.allocation_strategy_id);
      $('#expiry_id').val(msg_data.expiry_strategy_id);
      $('#program_id').val(msg_data.program_id);
      $('#promotion_id').val(msg_data.till_id);
      $('#till_id').val(msg_data.promotion_id);
      $('#coupon_series_id').val(msg_data.voucher_series);
      $('#template_selected').val(msg_data.template_id);
    }
    var template = _.template($("#recipient_vlist_sticky").html(), {
      group: this.model.toJSON(),
      msg_data: msg_data,
      is_sms: 1,
      is_wechat: 1,
      account_id: $('#account_id').val()
    });
    this.$el.html(template);
    return this;
  },
  events: {
    "click i.intouch-green-tick": "selectRadio",
    "keyup input#search_list": "searchList",
    "click a#next-button-action": "processRecipient",
    "click a.open_popup": "openPopup",
    "click a.show_more_list": "showMore",
    "click a.show_less_list": "showLess",
    "click i.icon-refresh": "refreshList",
    "mouseover .c-show-progress": "showProgess",
    "mouseleave .c-show-progress": "hideProgress",
    "click .plus-icon": "showDetails"
  },
  hideProgress: function(e) {
    $('.c-show-information').addClass('hide');
  },
  showDetails: function(e) {
    this.openPopupRec("sd", e);
  },
  openPopupRec: function(templ, e) {
    $('.c-show-information').addClass('hide');
    //$('.c-show-information-mobile').addClass('hide');
    $(".plus-icon").text(_campaign("More details"));
    var that = this;
    var src = this.$('.plus-icon');
    src.each(function() {
      listit = $(this);
      item = listit.closest('.item');
      if (!listit.hasClass('sel')) {
        //$('.item .sel').removeClass("sel");
        listit.addClass("sel");
        if (templ == 'sd') {
          var tmpl = _.template($("#details-templ").html());
          if (listit.closest('.item').hasClass('search_enabled')) {
            $(".c-rec-popup-cont").html("").slideUp();
            that.$('.c-downarrow-icon').addClass('hide');
            that.$('.c-uparrow-icon').removeClass('hide');
            item.find(".c-rec-popup-cont").html(tmpl({
              rc: that.model.get('name'),
              group: that.model.toJSON(),
              is_sms: 1
            })).slideDown();
          }
          listit.text(_campaign("Less details"));
        }
      } else {
        if (listit.closest('.item').hasClass('search_enabled')) {
          $(".c-rec-popup-cont").html("").slideUp();
          that.$('.c-uparrow-icon').addClass('hide');
          that.$('.c-downarrow-icon').removeClass('hide');
        }
        //$(".c-rec-popup-cont").slideUp();
        if (listit.hasClass("icon-plus")) listit.text(_campaign("More details"));
        listit.removeClass("sel");
      }
    });
  },
  showProgess: function(e) {
    //$(".c-item-rec .sel").removeClass("sel");
    //$(".c-rec-popup-cont").html("").slideUp();
    //$('.plus-icon').text('More details');
    $('.c-show-information').addClass('hide');
    this.$('.c-show-information').removeClass('hide');
    //$('.c-show-information').addClass('hide');
    // if(this.$(e.currentTarget).parent().attr('class')=='c-text-mobile'){
    //  this.$('.c-show-information-mobile').removeClass('hide');
    // }
    // else{
    //  this.$('.c-show-information-email').removeClass('hide');
    // }
  },
  refreshList: function(e) {
    this.model.checkSessionExpire();
    var campaign_id = $('#campaign_id').val();
    this.model.setCache('campaign');
    var message_id = $('#message_id').val();
    this.model.fetch({
      data: {
        campaign_id: campaign_id,
        message_id: message_id
      }
    });
  },
  selectRadio: function(e) {
    $('.c-error-popup').removeClass('c-error-popup-orange');
    $('.c-error-popup').removeClass('c-error-popup-green');
    $('.c-popup-text-orange').addClass('hide');
    $('.c-popup-text-green').addClass('hide');
    $('i.intouch-green-tick').removeClass('intouch-green-tick-active');
    $(e.currentTarget).addClass('intouch-green-tick-active');
    if (this.model.get('listype') != 'sticky') {
      if ($(e.currentTarget).parent().find('.c-show-progress').hasClass('sk-fading-circle')) {
        $(e.currentTarget).parent().parent().addClass('c-error-popup-orange');
        $(e.currentTarget).parent().parent().find('.c-popup-text-orange').removeClass('hide')
      } else {
        $(e.currentTarget).parent().parent().addClass('c-error-popup-green');
        $(e.currentTarget).parent().parent().find('.c-popup-text-green').removeClass('hide')
      }
    }
  },
  searchList: function(e) {
    var search = $(e.currentTarget).val().toLowerCase();
    $('.search_enabled').addClass('hide');
    $('#campaign_list_check li').each(function() {
      if ($(this).attr('search_name') != undefined) {
        var val = $(this).attr('search_name').toLowerCase();
        if (val.indexOf(search) != -1) $(this).addClass('cmp-temp-search');
        else $(this).removeClass('cmp-temp-search');
      }
    });
    $('#campaign_list_radio li').each(function() {
      if ($(this).attr('search_name') != undefined) {
        var val = $(this).attr('search_name').toLowerCase();
        if (val.indexOf(search) != -1) $(this).addClass('cmp-temp-search');
        else $(this).removeClass('cmp-temp-search');
      }
    });
    if (search.length < 1) {
      $('#campaign_list_radio li').removeClass("cmp-temp-search");
      $('#campaign_list_check li').removeClass("cmp-temp-search");
      $('.search_enabled').removeClass("hide");
    }
  },
  processRecipient: function(e) {
    this.model.checkSessionExpire();
    var group_id = '';
    var count = 0;
    $('#campaign_list_radio li').each(function() {
      if ($(this).find('i').hasClass('intouch-green-tick-active')) {
        group_id = $(this).attr('group_id');
        count = parseInt($(this).attr('count'));
      }
    });
    $('#campaign_list_check li').each(function() {
      if ($(this).find('i').hasClass('intouch-green-tick-active')) {
        group_id = $(this).attr('group_id');
        count = parseInt($(this).attr('count'));
      }
    });
    if (group_id) {
      this.model.setCache('customer_count', count);
      this.model.addWait();
      var campaign_id = $('#campaign_id').val();
      $('#group_selected').val(group_id);
      step2_view.renderFn(this.model).done(function() {
        slidePage($(e.currentTarget));
        $('#container_1 #nav_div_1').hide();
        $('#container_2 #nav_div_2').show();
      });
    } else this.model.showError(_campaign("Please select at least one customer list to proceed!"));
  },
  openPopup: function(e) {
    var campaign_id = $('#campaign_id').val();
    this.model.checkSessionExpire();
    switch ($(e.currentTarget).attr('id')) {
      case 'upload_csv':
        showPopup('/campaign/v2/audience/uploadAudiences?campaign_id=' + campaign_id + '&option=upload_csv_type&source=communication');
        break;
      case 'upload_ftp':
        showPopup('/campaign/v2/audience/uploadAudiences?campaign_id=' + campaign_id + '&option=upload_ftp_type&source=communication');
        break;
      case 'open_filter':
        showPopup('/campaign/audience/AudienceFilter?campaign_id=' + campaign_id + '&list_option=new&params=sms&source=communication&type=LOYALTY');
        break;
      case 'open_nlfilter':
        showPopup('/campaign/audience/AudienceFilter?campaign_id=' + campaign_id + '&list_option=new&params=sms&source=communication&type=NON_LOYALTY');
        break;
      case 'paste_list':
        showPopup('/campaign/v2/audience/uploadAudiences?campaign_id=' + campaign_id + '&option=paste_list_type&source=communication');
        break;
      case 'deduplicate':
        showPopup('/campaign/v2/dedup/Dedup?campaign_id=' + campaign_id + '&check=1&source=communication');
        break;
    }
  },
  showMore: function(e) {
    var id = $(e.currentTarget).attr('ul-id');
    $('#' + id + ' li').each(function() {
      if ($(this).hasClass('hide')) {
        $(this).addClass('show-more').removeClass('hide');
        $(this).addClass('search_enabled');
      }
    });
    $(e.currentTarget).addClass('hide');
    $('.show_less_' + id).removeClass('hide');
  },
  showLess: function(e) {
    var id = $(e.currentTarget).attr('ul-id');
    $('#' + id + ' li').each(function() {
      if ($(this).hasClass('show-more')) {
        $(this).addClass('hide').removeClass('show-more');
        $(this).removeClass('search_enabled');
      }
    });
    $(e.currentTarget).addClass('hide');
    $('.show_more_' + id).removeClass('hide');
  }
});
MessageParentView = Backbone.View.extend({
  el: $('#container_1'),
  initialize: function(options) {
    this.collection = options.collection;
    this.listenTo(this.collection, 'add', this.append);
    var id = $('#campaign_id').val(); //this.collection.models[0].attributes["campaign_id"];
    var self = this;
    this.refresh_list = setInterval(function() {
      self.collection.fetch({
        data: {
          campaign_id: id
        }
      });
    }, 10000);
  },
  render: function() {
    var main_template = _.template($('#nav_bar_step_1').html(), {
      is_sms: 1
    });
    this.$el.children('#nav_div_1').html(main_template).show();
    var template = _.template($("#search-bar").html(), {
      is_calltask: undefined
    });
    this.$el.children('#content_div_1').html(template);
    var template2 = _.template($("#radio_box").html(), {
      is_sms: undefined
    });
    this.$el.children('#content_div_1').append(template2);
    var template2 = _.template($("#sticky_checkbox").html(), {
      is_sms: undefined
    });
    this.$el.children('#content_div_1').append(template2);
  },
  append: function(rec) {
    var messageView = new recipientView({
      model: rec
    });
    if (rec.get('listype') == 'sticky') {
      this.$('.l-v-list-sticky').append(messageView.renderSticky().$el);
      //messageView.$el.find('.l-v-list-sticky').append(messageView.renderTemp());
    } else {
      this.$('.l-v-list-non_sticky').append(messageView.renderNonSticky().$el);
      //messageView.$el.find('.l-v-list-non_sticky').append(messageView.render());
    }
  },
  events: {
    "click a#next-button-action": "processRecipient",
    "click a.open_popup": "openPopup",
    "keyup input#search_list": "searchList",
    //"click .plus-icon":"showDetails",
    "click a.show_more_list": "showMore",
    "click a.show_less_list": "showLess",
    "click i.icon-refresh": "refreshList"
  },
  refreshList: function(e) {
    //this.model.checkSessionExpire();
    var campaign_id = ajaxModel1.getCache('campaign_id');
    var message_id = ajaxModel1.getCache('message_id');
    this.collection.fetch({
      data: {
        campaign_id: campaign_id,
        message_id: message_id
      }
    });
  },
  searchList: function(e) {
    var search = $(e.currentTarget).val().toLowerCase();
    $('.search_enabled').addClass('hide');
    $('#campaign_list_check li').each(function() {
      if ($(this).attr('search_name') != undefined) {
        var val = $(this).attr('search_name').toLowerCase();
        if (val.indexOf(search) != -1) $(this).addClass('cmp-temp-search');
        else $(this).removeClass('cmp-temp-search');
      }
    });
    $('#campaign_list_radio li').each(function() {
      if ($(this).attr('search_name') != undefined) {
        var val = $(this).attr('search_name').toLowerCase();
        if (val.indexOf(search) != -1) $(this).addClass('cmp-temp-search');
        else $(this).removeClass('cmp-temp-search');
      }
    });
    if (search.length < 1) {
      $('#campaign_list_radio li').removeClass("cmp-temp-search");
      $('#campaign_list_check li').removeClass("cmp-temp-search");
      $('.search_enabled').removeClass("hide");
    }
  },
  showMore: function(e) {
    var id = $(e.currentTarget).attr('ul-id');
    $('#' + id + ' li').each(function() {
      if ($(this).hasClass('hide')) {
        $(this).addClass('show-more').removeClass('hide');
        $(this).addClass('search_enabled');
      }
    });
    $(e.currentTarget).addClass('hide');
    $('.show_less_' + id).removeClass('hide');
    var favourite = this.collection.where({
      showMore: 0
    });
    for (i = 0; i < favourite.length; i++) {
      favourite[i].set('showMore', 1);
    }
    $('.show_more_list').addClass('hide');
  },
  showLess: function(e) {
    var id = $(e.currentTarget).attr('ul-id');
    $('#' + id + ' li').each(function() {
      if ($(this).hasClass('show-more')) {
        $(this).addClass('hide').removeClass('show-more');
        $(this).removeClass('search_enabled');
      }
    });
    $(e.currentTarget).addClass('hide');
    $('.show_more_' + id).removeClass('hide');
    var favourite = this.collection.where({
      showMore: 1
    });
    for (i = 0; i < favourite.length; i++) {
      favourite[i].set('showMore', 0);
    }
  },
  //goto next step from the step 1 create model object getting template data
  processRecipient: function(e) {
    ajaxModel1.checkSessionExpire();
    //var campaign_id = this.model.getCache('campaign_id');
    //var campaign_id = this.collection.models[0].get('campaign_id');
    var group_id = new Array();
    var count = 0;
    var type = '';
    $('#campaign_list_radio li').each(function() {
      if ($(this).find('i').hasClass('intouch-green-tick-active')) {
        group_id.push($(this).attr('group_id'));
        count = parseInt($(this).attr('count'));
      }
      type = 'non-sticky';
    });
    $('#campaign_list_check li').each(function() {
      if ($(this).find('i').hasClass('intouch-green-tick-active')) {
        group_id.push($(this).attr('group_id'));
        count = parseInt($(this).attr('count'));
      }
      type = 'sticky';
    });
    if (group_id.length > 0) {
      var jsonArray = [];
      jsonArray.push('UNABLE_TO_VERIFY');
      jsonArray.push('VALID');
      jsonArray.push('SOFTBOUNCED');
      //var json = {"rule1":temp.get('rule1'),"rule2":temp.get('rule2'),"rule3":temp.get('rule3')};
      ajaxModel1.setCache('rule_selected', jsonArray);
      ajaxModel1.setCache('list_type', type);
      ajaxModel1.setCache('customer_count', count);
      $('#group_selected').val(group_id);
      ajaxModel1.addWait();
      ajaxModel1.setCache('group_selected', group_id);
      step2_view.renderFn(ajaxModel1).done(function() {
        slidePage($(e.currentTarget));
        $('#container_1 #nav_div_1').hide();
        $('#container_2 #nav_div_2').show();
      });
      clearInterval(this.refresh_list);
    } else {
      this.collection.showError(_campaign("please select a group to proceed!"));
    }
  },
  openPopup: function(e) {
    ajaxModel1.checkSessionExpire();
    //var campaign_id = this.model.getCache('campaign_id');
    var campaign_id = $('#campaign_id').val(); //this.collection.models[0].get('campaign_id');
    switch ($(e.currentTarget).attr('id')) {
      case 'upload_csv':
        showPopup('/campaign/v2/audience/uploadAudiences?campaign_id=' + campaign_id + '&option=upload_csv_type&source=communication');
        break;
      case 'upload_ftp':
        showPopup('/campaign/v2/audience/uploadAudiences?campaign_id=' + campaign_id + '&option=upload_ftp_type&source=communication');
        break;
      case 'open_filter':
        showPopup('/campaign/audience/AudienceFilter?campaign_id=' + campaign_id + '&list_option=new&params=sms&source=communication&type=LOYALTY');
        break;
      case 'open_nlfilter':
        showPopup('/campaign/audience/AudienceFilter?campaign_id=' + campaign_id + '&list_option=new&params=sms&source=communication&type=NON_LOYALTY');
        break;
      case 'paste_list':
        showPopup('/campaign/v2/audience/uploadAudiences?campaign_id=' + campaign_id + '&option=paste_list_type&source=communication');
        break;
      case 'deduplicate':
        showPopup('/campaign/v2/dedup/Dedup?campaign_id=' + campaign_id + '&check=1&source=communication');
        break;
    }
  }
});
//-------------------------------Voucher Create View---------------------------------------
couponSeriesView = Backbone.View.extend({
  initialize: function() {},
  renderFn: function(model) {
    var r = $.Deferred();
    this.model = model;
    this.render(r);
    return r;
  },
  render: function(r) {
    var main_template = _.template($('#nav_bar_step_2').html(), {
      is_sms: 1
    });
    this.$el.children('#nav_div_2').html(main_template);
    var campaign_id = this.model.getCache('campaign_id');
    var msg_id = this.model.getCache('message_id');
    var group_id = this.model.getCache('group_selected');
    var coupon_model = new cpn.ViewCouponModel({
      id: campaign_id,
      message_id: msg_id
    });
    var that = this;
    coupon_model.fetch({
      success: function(response) {
        var r_data = response.toJSON();
        var details = r_data.camp_details;
        that.model.setCache("inc_elems", r_data.inc_elems);
        var inc_elems = JSON.parse(r_data.inc_elems);
        var alloc = parseInt($('#allocation_id').val());
        var exit = parseInt($('#expiry_id').val());
        var prog = parseInt($('#program_id').val());
        var prom = parseInt($('#promotion_id').val());
        var till = parseInt($('#till_id').val());
        var is_points_edit = false;
        var coupon_id = $('#coupon_series_id').val();
        if (alloc > 0 && exit > 0 && prom >= 0 && till >= 0 && prog > 0) {
          is_points_edit = true;
        }
        var coupon_template = _.template($('#coupon_series_step').html(), coupon_model.toJSON());
        that.$el.children('#content_div_2').html(coupon_template);
        that.renderGenericDetails(inc_elems);
        that.model.setCache('coupon_attached', 1);
        //hiding coupon and points
        // $('.coupon_div').addClass('hide');
        // $('.points_div').addClass('hide');
        // $('#show_coupon_button').hide();
        $('#coupon_org_id').val(response.attributes.c_org_id);
        // $('#points_details').addClass('hide');
        // $('#coupon_details').addClass('hide');
        $('#attach-incentive').addClass('intouch-green-tick-active');
        //check if coupon series is attached to this campaign
        if (details.no_coupon_series > 0) {
          $('#coupon_tick').addClass('intouch-green-tick-active');
          $('#series_modal_title').html(decodeURIComponent(r_data.c_title));
          $('#series_modal_body').html(decodeURIComponent(r_data.c_body));
          that.model.setCache('voucher_series_id', details.voucher_series_id);
        } else {
          that.model.setCache('voucher_series_id', -1);
        }
        // $('#points_details').addClass('hide');
        // $('#coupon_details').addClass('hide');
        $('#attach-incentive').addClass('intouch-green-tick-active');
        //check if points has been saved for this campaign 
        if (details.points_info.is_first_time) {
          $('#choose_points').removeClass('hide');
          $('#points_fixed').addClass('hide');
          $('#err_msg').addClass('hide');
          $('.err_span_msg').addClass('hide');
          $('.span_msg').removeClass('hide');
          $('#points_details').addClass('hide');
        } else {
          //$('#attach-incentive').removeClass('intouch-green-tick-active') ;
          //$('#attach-points').addClass('intouch-green-tick-active') ;
          $('#points_fixed').removeClass('hide');
          $('.assign-empty-alloc').addClass('intouch-green-tick-active');
          $('.assign-empty-expiry').addClass('intouch-green-tick-active');
          $('.assign-empty-alloc').removeClass('hide');
          $('.assign-empty-expiry').removeClass('hide');
          //$('#points_details').removeClass('hide') ;
          $('#choose_points').addClass('hide');
          $('#err_msg').addClass('hide');
          $('.err_span_msg').addClass('hide');
          $('.span_msg').removeClass('hide');
          $('#points_details').addClass('hide');
        }
        //check if message is being edited 
        if (((parseInt(coupon_id) > 0) || (parseInt($('#is_cpn_save').val()) == 1)) && (parseInt($('#is_pnt_save').val()) != 1)) {
          $('#coupon_details').removeClass('hide');
          $('#attach-coupons').addClass('intouch-green-tick-active');
          $('#attach-incentive').removeClass('intouch-green-tick-active');
          $('#attach-points').removeClass('intouch-green-tick-active');
          $('#points_details').addClass('hide');
          $('#is_cpn_save').val('0');
        } else if ((is_points_edit || (parseInt($('#is_pnt_save').val()) == 1)) && details.points_info.status.state) {
          $('#points_fixed').removeClass('hide');
          $('#attach-incentive').removeClass('intouch-green-tick-active');
          $('#attach-points').addClass('intouch-green-tick-active');
          $('.assign-empty-alloc').addClass('intouch-green-tick-active');
          $('.assign-empty-expiry').addClass('intouch-green-tick-active');
          $('.assign-empty-alloc').removeClass('hide');
          $('.assign-empty-expiry').removeClass('hide');
          $('#choose_points').addClass('hide');
          $('#err_msg').addClass('hide');
          $('.err_span_msg').addClass('hide');
          $('.span_msg').removeClass('hide');
          $('#points_details').removeClass('hide');
          $('#is_pnt_save').val('0');
        } else {
          $('#points_details').addClass('hide');
          $('#coupon_details').addClass('hide');
          $('#attach-incentive').addClass('intouch-green-tick-active');
          $('#attach-points').removeClass('intouch-green-tick-active');
          $('#attach-coupons').removeClass('intouch-green-tick-active');
        }
        //if exception occurs while fetching points
        if (!details.points_info.status.state) {
          $('#points_details').removeClass('hide');
          $('#choose_points').addClass('hide');
          $('#points_fixed').addClass('hide');
          $('#input_msg').val("1");
          $('.err_span_msg').removeClass('hide');
          $('.span_msg').addClass('hide');
          $('#attach-points').removeClass('intouch-green-tick-active');
          $('#attach-points').css({
            'opacity': 0.4,
            'cursor': 'not-allowed'
          });
          //that.model.showError(details.points_info.status.msg);
        }
        $(".generic_div span").removeClass("hide");
        var eMsg = details.points_info.status.msg;
        if ($("#recipient_" + group_id).attr("gtype").toLowerCase() == "non_loyalty") {
          $('#attach-points').removeClass('intouch-green-tick-active');
          $('#attach-points').css({
            'opacity': 0.4,
            'cursor': 'not-allowed'
          });
          $('.err_span_msg').html(_campaign("Points - Points cannot be awarded to Non-Loyalty customers")).removeClass('hide');
          $('#input_msg').val("1");
          $('.span_msg').addClass('hide');
        } else if ($("#recipient_" + group_id).attr("gtype").toLowerCase() == "campaign_users") {
          $('.err_span_msg').html(_campaign("Points - Points cannot be awarded to Non-Loyalty customers in this list"));
          $('.err_span_msg').removeClass('hide');
          $('.span_msg').addClass('hide');
        } else {
          $('.err_span_msg').html(eMsg);
        }
        $(".generic_div span").removeClass("hide");
        that.model.removeWait();
        r.resolve();
      }
    });
  },
  events: {
    "click a#back_to_recipient": "backToRecipient",
    "click a#next-button-action": "processVoucher",
    "click #attach-incentive": "showHideCouponSeries",
    "click #attach-coupons": "showCoupon",
    "click #attach-points": "showPoint",
    "click #attach-generic": "showGeneric",
    "click #generic-select": "modifyGeneric",
    "click .attach-incentive": "saveIncentiveState",
    "click a#create_coupon_series": "createSeries",
    "click #coupon_cancel": "couponSlideUp",
    "click #createNewSeries": "createCouponSeries",
    "click i.intouch-green-tick": "selectRadio",
    "click #coupon_edit": "editCoupon",
    "click #advanced_voucher_form__create_btn": "updateCouponDetails",
    "click #advanced_voucher_form__reset": "hideConfigureCoupon",
    "click #show_coupon_button": "hideConfigureCoupon",
    "click .choose-empty-alloc": "chooseEmptyAlloc",
    "click .choose-empty-expiry": "chooseEmptyExpiry",
    "click .assign-empty-alloc": "assignEmptyAlloc",
    "click .assign-empty-expiry": "assignEmptyExpiry",
    "click #points_save": "savePoints",
    "click #points_cancel": "cancelPoints",
    "mouseover .alloc_details": "allocMouseOver",
    "mouseover .expiry_details": "exitMouseOver",
    "mouseout .alloc_details": "allocMouseOut",
    "mouseout .expiry_details": "exitMouseOut",
    "click #v_s_c_title": "toggleCategoryDesc",
    "click #v_s_b_title": "toggleBrandDesc",
    "click #view_coupon_validity": "viewProductDetails",
    "change #coupon_validity_val": "showValidProduct"
  },
  resetPoints: function() {
    this.model.setCache('allocation_strategy_id', 0);
    this.model.setCache('expiry_strategy_id', 0);
    this.model.setCache('program_id', 0);
    this.model.setCache('till_id', 0);
    this.model.setCache('promotion_id', 0);
  },
  setPoints: function(allocation_id, expiry_id, prog_id, till_id, promotion_id) {
    this.model.setCache('allocation_strategy_id', allocation_id);
    this.model.setCache('expiry_strategy_id', expiry_id);
    this.model.setCache('program_id', prog_id);
    this.model.setCache('till_id', till_id);
    this.model.setCache('promotion_id', promotion_id);
  },
  cancelPoints: function() {
    $('.choose-empty-expiry').removeClass('intouch-green-tick-active');
    $('.choose-empty-alloc').removeClass('intouch-green-tick-active');
    this.resetPoints();
  },
  validatePoint: function() {
    var allocation_id = 0;
    var expiry_id = 0;
    var prog_id = 0;
    //attach points is checked
    if ($("#attach-points").hasClass('intouch-green-tick-active')) {
      prog_id = $('#points_details').attr('program-id');
      //alert("program id is "+prog_id) ;
      if ($('#points_fixed').hasClass('hide')) {
        //alert("points fixed hidden") ;
        //existing points
        $('#alloc_points_list').find('i').each(function() {
          //alert("alloc_points_list ") ;
          if ($(this).hasClass('intouch-green-tick-active')) {
            allocation_id = $(this).parent().attr('strat-id');
            //alert("allocation id points list "+allocation_id) ;
          }
        });
        $('#exit_points_list').find('i').each(function() {
          //alert("exit_points_list ") ;
          if ($(this).hasClass('intouch-green-tick-active')) {
            expiry_id = $(this).parent().attr('strat-id');
            //alert("expiry id points list "+expiry_id) ;
          }
        });
      } else {
        //alert("points fixed visible") ;
        $('#fixed_alloc_list').find('i').each(function() {
          //alert("fixed_alloc_list ") ;
          if ($(this).hasClass('intouch-green-tick-active')) {
            allocation_id = $(this).parent().attr('strat-id');
            //alert("allocation id fixed list "+allocation_id) ;
          }
        });
        $('#fixed_exit_list').find('i').each(function() {
          //alert("fixed_exit_list ") ;
          if ($(this).hasClass('intouch-green-tick-active')) {
            expiry_id = $(this).parent().attr('strat-id');
            //alert("expiry id fixed list "+expiry_id) ;
          }
        });
      }
      this.setPoints(allocation_id, expiry_id, prog_id, 0, 0);
    }
  },
  savePoints: function(e) {
    //$('.wait_message_form').show().addClass('intouch-loader');
    this.validatePoint();
    var points_strategy = {
      alloc_strategy_id: -1,
      exit_strategy_id: -1,
      is_first_time: true,
      program_id: -1
    };
    points_strategy['alloc_strategy_id'] = this.model.getCache('allocation_strategy_id');
    points_strategy['exit_strategy_id'] = this.model.getCache('expiry_strategy_id');
    points_strategy['is_first_time'] = true;
    points_strategy['program_id'] = this.model.getCache('program_id');
    points_strategy['campaign_id'] = this.model.getCache('campaign_id');
    if (points_strategy['alloc_strategy_id'] <= 0) {
      this.model.showError(_campaign("Please select allocation strategy"));
    } else if (points_strategy['exit_strategy_id'] <= 0) {
      this.model.showError(_campaign("Please select exit strategy"));
    } else if (points_strategy['program_id'] <= 0) {
      this.model.showError(_campaign("Invalid program"));
    } else if (points_strategy['campaign_id'] <= 0) {
      this.model.showError(_campaign("Invalid campaign"));
    } else {
      var cpn_model = new cpn.CouponModel({
        points_data: points_strategy
      });
      //alert("alert model is : "+cpn_model.toJSON()) ;
      var that = this;
      $('#points_save').attr('disabled', true);
      $('#points_cancel').attr('disabled', true);
      this.model.addWait();
      cpn_model.save(cpn_model.toJSON(), {
        type: "PUT",
        success: function(response) {
          if (response.attributes.istatus == 'success') {
            $('#is_pnt_save').val('1');
            that.model.setCache("inc_mapping_id", response.attributes.inc_mapping_id);
            that.model.showMessage(_campaign("Points allocated successfully"));
            $('#points_save').addClass('hide');
            $('#points_cancel').addClass('hide');
            that.render();
          } else {
            that.model.removeWait();
            that.model.showError(response.attributes.error_msg);
            that.model.setCache("inc_mapping_id", "none");
            $('#points_save').attr('disabled', false);
            $('#points_cancel').attr('disabled', false);
          }
        },
        error: function() {
          that.model.removeWait();
          that.model.showError(_campaign("Error while allocating points"));
          that.model.setCache("inc_mapping_id", "none");
          $('#points_save').attr('disabled', false);
          $('#points_cancel').attr('disabled', false);
        }
      });
    }
  },
  allocMouseOver: function(e) {
    $('#exit_pop_up').addClass('hide');
    $('#alloc_pop_up').removeClass('hide');
    $('.triangle').removeClass('hide');
    var positionId = $(e.currentTarget).attr('position-id');
    $('.alloc_cmn').addClass('hide');
    var pos = $(e.currentTarget).position();
    $('.triangle').css({
      "position": "absolute",
      "top": pos.top + 6,
      "left": pos.left + 71
    });
    $('.alloc_row__' + positionId).css({
      "position": "absolute",
      "top": pos.top - 13,
      "left": pos.left + 80.5
    });
    $('.alloc_row__' + positionId).removeClass('hide');
  },
  exitMouseOver: function(e) {
    $('#exit_pop_up').removeClass('hide');
    $('#alloc_pop_up').addClass('hide');
    $('.triangle').removeClass('hide');
    var positionId = $(e.currentTarget).attr('position-id');
    $('.exit_cmn').addClass('hide');
    var pos = $(e.currentTarget).position();
    $('.triangle').css({
      "position": "absolute",
      "top": pos.top + 6,
      "left": pos.left + 71
    });
    $('.exit_row__' + positionId).css({
      "position": "absolute",
      "top": pos.top - 13,
      "left": pos.left + 80.5
    });
    $('.exit_row__' + positionId).removeClass('hide');
  },
  allocMouseOut: function(e) {
    $('#exit_pop_up').addClass('hide');
    $('#alloc_pop_up').addClass('hide');
    $('.triangle').addClass('hide');
  },
  exitMouseOut: function(e) {
    $('#exit_pop_up').addClass('hide');
    $('#alloc_pop_up').addClass('hide');
    $('.triangle').addClass('hide');
  },
  renderGenericDetails: function(inc_elems) {
    for (inc in inc_elems) {
      if (inc_elems[inc]['type'] == "GENERIC") {
        var select = '<select id="generic-select" prev="genid_1">';
        var select_span = '<span>';
        var label = '<label class="generic_label">' + _campaign("Deal type") + '*' + '</label>';
        var label_span = '<span>' + label + '</span>';
        for (child in inc_elems[inc]['children']) {
          var genreic_text = inc_elems[inc]['children'][child]['type'];
          select += '<option value="' + genreic_text + ' genid_' + child.toString() + '" id="genid_' + child.toString() + '">' + _campaign(genreic_text) + '</option>';
        }
        select += '</select>'
        select_span += select + '</span>';
        $("#generic_details").append(label_span, select_span);
      }
    }
    $(".generic_div" > "span").removeClass("hide");
  },
  showCoupon: function(e) {
    $('i.attach-incentive').removeClass('intouch-green-tick-active');
    $(e.currentTarget).addClass('intouch-green-tick-active');
    $('#points_details').addClass('hide');
    $('#generic_details').addClass('hide');
    $('#coupon_details').removeClass('hide');
    $('.formError').remove();
  },
  showPoint: function(e) {
    $('.formError').remove();
    if (parseInt($('#input_msg').val()) == 1) {
      $('#err_msg').removeClass('hide');
      $('.err_span_msg').removeClass('hide');
      $('.span_msg').addClass('hide');
      $(".generic_div span").removeClass("hide");
      $('#attach-points').removeClass('intouch-green-tick-active');
    } else {
      $('#err_msg').addClass('hide');
      $('.err_span_msg').addClass('hide');
      $('.span_msg').removeClass('hide');
      $('i.attach-incentive').removeClass('intouch-green-tick-active');
      $(e.currentTarget).addClass('intouch-green-tick-active');
      $('#coupon_details').addClass('hide');
      $('#generic_details').addClass('hide');
      $('#coupon_new_create').addClass('hide');
      $('#coupon_new_create').css({
        "display": "none"
      });
      $('#points_details').removeClass('hide');
    }
  },
  modifyGeneric: function(e) {
    var selected_generic = $("#generic-select").children(":selected").attr("id");
    selected_generic = selected_generic.substr(selected_generic.indexOf('_') + 1);
    selected_generic = parseInt(selected_generic);
    this.model.setCache("selected_generic", selected_generic);
  },
  saveIncentiveState: function(e) {
    var current_target = $(e.currentTarget).attr("id");
    var selected_incentive;
    var selected_generic = "none";
    switch (current_target) {
      case "attach-coupons":
        selected_incentive = "coupon";
        break;
      case "attach-points":
        selected_incentive = "points";
        break;
      case "attach-generic":
        selected_incentive = "generic";
        selected_generic = $("#generic-select").children(":selected").attr("id");
        selected_generic = selected_generic.substr(selected_generic.indexOf('_') + 1);
        selected_generic = parseInt(selected_generic);
        break;
      default:
        selected_incentive = "none";
        break;
    }
    this.model.setCache("selected_incentive", selected_incentive);
    this.model.setCache("selected_generic", selected_generic);
  },
  showGeneric: function(e) {
    $('i.attach-incentive').removeClass('intouch-green-tick-active');
    $(e.currentTarget).addClass('intouch-green-tick-active');
    $('#points_details').addClass('hide');
    $('#coupon_details').addClass('hide');
    $('#coupon_new_create').slideUp();
    $("#generic_details").removeClass('hide');
  },
  chooseEmptyAlloc: function(e) {
    $('i.choose-empty-alloc').removeClass('intouch-green-tick-active');
    $(e.currentTarget).addClass('intouch-green-tick-active');
  },
  chooseEmptyExpiry: function(e) {
    $('i.choose-empty-expiry').removeClass('intouch-green-tick-active');
    $(e.currentTarget).addClass('intouch-green-tick-active');
  },
  assignEmptyAlloc: function(e) {
    $('i.assign-empty-alloc').removeClass('intouch-green-tick-active');
    $(e.currentTarget).addClass('intouch-green-tick-active');
  },
  assignEmptyExpiry: function(e) {
    $('i.assign-empty-expiry').removeClass('intouch-green-tick-active');
    $(e.currentTarget).addClass('intouch-green-tick-active');
  },
  backToRecipient: function(e) {
    this.model.checkSessionExpire();
    $('#search_list').val('');
    slidePage($(e.currentTarget));
    $('#container_2 #nav_div_2').hide();
    $('#container_1 #nav_div_1').show();
    $('a#next-button-action').show();
  },
  processVoucher: function(e) {
    $('.wait_message').show().addClass('indicator_1');
    var d_model = new ajaxModel({
      id: "get_wechat_templates.json"
    });
    d_model.urlRoot = '/xaja/AjaxService/assets';
    campaign_id = d_model.getCache('campaign_id');
    var allocation_id = -1;
    var expiry_id = -1;
    var prog_id = -1;
    var nextPane = true;
    var msg = "";
    if ($("#attach-points").hasClass('intouch-green-tick-active')) {
      //enforce user to save before going to next pane
      this.validatePoint();
      this.model.setCache('coupon_attached', 0);
      if (this.model.getCache('allocation_strategy_id') <= 0) {
        nextPane = false;
        msg = _campaign("Please save allocation strategy");
      } else if (this.model.getCache('expiry_strategy_id') <= 0) {
        nextPane = false;
        msg = _campaign("Please save exit strategy");
      } else if (this.model.getCache('program_id') <= 0) {
        nextPane = false;
        msg = _campaign("There is problem with program");
      } else if ($('#choose_points').hasClass('hide')) {
        if (!$('.assign-empty-alloc').hasClass('intouch-green-tick-active')) {
          nextPane = false;
          msg = _campaign("Please select and save allocation strategy");
        } else if (!$('.assign-empty-expiry').hasClass('intouch-green-tick-active')) {
          nextPane = false;
          msg = _campaign("Please select and save exit strategy");
        }
      } else if ($('#points_fixed').hasClass('hide')) {
        if (!$('.choose-empty-alloc').hasClass('intouch-green-tick-active')) {
          nextPane = false;
          msg = _campaign("Please select and save allocation strategy");
        } else if (!$('.choose-empty-expiry').hasClass('intouch-green-tick-active')) {
          nextPane = false;
          msg = _campaign("Please select and save exit strategy");
        }
      }
    } else if ($("#attach-coupons").hasClass('intouch-green-tick-active')) {
      this.resetPoints();
      if ($('#coupon_tick').hasClass('intouch-green-tick-active') && !$('#coupon_tick').hasClass('hide')) this.model.setCache('coupon_attached', 1);
      else {
        nextPane = false;
        msg = _campaign("Coupon series not selected");
      }
    } else if ($('#attach-incentive').hasClass('intouch-green-tick-active')) {
      this.resetPoints();
      this.model.setCache('coupon_attached', 0);
    }
    if (nextPane) {
      var that = this;
      this.model.setCache('points', 'wassup');
      d_model.fetch({
        data: {
          start: 0,
          limit: 18,
          account_id: $('#account_id').val()
        }
      });
      d_model.on('change', function() {
        if (d_model.get('error') == undefined) {
          step3_view.renderFn(d_model);
          slidePage($(e.currentTarget));
          $('#container_2 #nav_div_2').hide();
          $('#container_3 #nav_div_3').show();
          $('.wait_message').hide().removeClass('indicator_1');
        } else {
          d_model.showError(d_model.get('error'));
          $('.wait_message').hide().removeClass('indicator_1');
        }
      });
    } else {
      this.model.removeWait();
      this.model.showError(msg);
    }
  },
  showHideCouponSeries: function(e) {
    $('i.attach-incentive').removeClass('intouch-green-tick-active');
    $(e.currentTarget).addClass('intouch-green-tick-active');
    $('#points_details').addClass('hide');
    $('#coupon_details').addClass('hide');
    $('#generic_details').addClass('hide');
    $('#coupon_new_create').slideUp();
    $('.formError').remove();
    /*
    if( $(e.currentTarget).attr('checked') ){
      $('#coupon-series-container').css({opacity: 0.5,'pointer-events': 'none'});
      $('#create_coupon_series').css({opacity: 0.5,'pointer-events': 'none'});
      $('#coupon_tick').removeClass('intouch-green-tick-active');
      this.model.setCache('coupon_attached',0);
      $('#coupon_cancel').trigger('click');
    }else{
      $('#coupon_tick').addClass('intouch-green-tick-active');
      $('#coupon-series-container').css({opacity: 1,'pointer-events': 'all'});
      $('#create_coupon_series').css({opacity: 1,'pointer-events': 'all'});
      this.model.setCache('coupon_attached',1);
      
    }
    */
  },
  createSeries: function() {
    $('#coupon_new_create').slideDown();
    $('#points_details').addClass('hide');
  },
  couponSlideUp: function() {
    $('#coupon_new_create').slideUp();
    $('.formError').remove();
  },
  createCouponSeries: function() {
    this.model.checkSessionExpire();
    var that = this;
    if ($('#specify_num_cpn').hasClass('intouch-green-tick-active')) {
      var num = $('#num_coupons').val();
      $('#max_create').val(num);
      $('#num_coupons').addClass("validate[required;regexcheck</^[0-9]*[1-9][0-9]*$/>;custom_val<" + _campaign('Number of coupons to be issued should be positive value') + ">]");
    } else {
      $('#max_create').val(-1);
      $('#num_coupons').removeClass("validate[required;regexcheck</^[0-9]*[1-9][0-9]*$/>;custom_val<" + _campaign('Number of coupons to be issued should be positive value') + ">]");
    }
    if (!$('#new_coupon_series').validationEngine({
        promptPosition: "centerRight",
        validationEventTriggers: 'keyup blur',
        success: false,
        scroll: true,
        returnIsValid: true
      })) {
      return;
    }
    if ($('#discount_type').val() == 'PERC') {
      var perc = $('#discount_value').val();
      var regex = /(^100([.]0{1,2})?)$|(^\d{1,2}([.]\d{1,2})?)$/;
      if (!regex.test(perc)) {
        this.model.showError(_campaign("Enter a valid discount percentage"));
        return;
      }
    }
    var campaign_id = this.model.getCache('campaign_id');
    var post_data = $('#new_coupon_series').serialize();
    var org_id = $('#coupon_org_id').val();
    if (org_id == -1) return;
    this.model.addWait();
    var cpn_model = new cpn.CouponModel({
      coupon_data: post_data,
      id: campaign_id
    });
    var that = this;
    cpn_model.save(cpn_model.toJSON(), {
      success: function(response) {
        if (response.attributes.istatus == 'success') {
          $('#is_cpn_save').val('1');
          that.model.showMessage(_campaign("Coupon series created successfully"));
          that.render();
        } else {
          that.model.removeWait();
          that.model.showError(response.attributes.error_msg);
        }
      },
      error: function(response) {
        that.model.removeWait();
      }
    });
    $('.formError').remove();
  },
  selectRadio: function(e) {
    $('i.intouch-green-tick').removeClass('intouch-green-tick-active');
    $(e.currentTarget).addClass('intouch-green-tick-active');
    this.model.setCache('series_selected', $(e.currentTarget).attr('series-id'));
  },
  editCoupon: function() {
    this.model.addWait();
    var campaign_id = this.model.getCache('campaign_id');
    var v_id = this.model.getCache('voucher_series_id');
    var edit_model = new cpn.EditCouponModel({
      cid: campaign_id,
      vid: v_id
    });
    var that = this;
    edit_model.fetch({
      success: function(response) {
        var status = response.toJSON().status;
        //Either no msg with voucher tag is authorised or campaign config is false for this org
        if (status.isSuccess) {
          $('#points_details').hide();
          $('#a_c_series').addClass('hide');
          $('#c_c_series').removeClass('hide');
          $('.c-but-cont-show').removeClass('hide');
          $('#show_coupon_button').show();
          $('.msg-coupon').hide();
          $('#coupon-series-container').hide();
          $('.coupon_div').hide();
          $('.points_div').hide();
          $('.generic_div').hide();
          $('#configure_coupon_series').show();
          $('a#next-button-action').hide();
          var edit = decodeURIComponent(response.toJSON().edit_coupon_html);
          $('#configure_coupon').show().html(edit);
          //intouch.widgets.heightResize($("#camp-body-cont"),$('#configure_coupon'));
          $('#advanced_voucher_form__create_btn').attr('onclick', '').css('background', '#84B81D');
          $('#advanced_voucher_form__create_btn').attr("coupon-id", v_id);
          $('#advanced_voucher_form__reset').attr('onclick', '').html('Cancel');
          $('.coupons-channel#EMAIL').trigger('click');
        } else {
          that.model.showError(status.message);
        }
        that.model.removeWait();
      },
      error: function(response) {
        that.model.removeWait();
      }
    });
  },
  updateCouponDetails: function() {
    var that = this;
    if ($("#advanced_voucher_form").validationEngine({
        promptPosition: "centerRight",
        returnIsValid: true
      })) {
      $('.flash_message').hide();
      var form_id = 'advanced_voucher_form';
      var post_data = $('#' + form_id).serialize();
      //Adding validation series name and discount code required. validation engine failed when space entered!
      if (!$('#' + form_id + '__info').val().trim()) {
        $('#' + form_id + '__info').focus();
        this.model.showError(_campaign("Series name required!"));
        return;
      }
      if (!$('#' + form_id + '__discount_code').val().trim()) {
        $('#' + form_id + '__discount_code').focus();
        this.model.showError(_campaign("Discount code required!"));
        return;
      }
      var campaign_id = this.model.getCache('campaign_id');
      var voucher_id = this.model.getCache('voucher_series_id');
      that.model.addWait();
      var prefix = $('#prefix').val();
      var ajaxUrl = prefix + '/xaja/AjaxService/campaign_v2/update_advance_coupon.json?campaign_id=' + campaign_id + '&voucher_id=' + voucher_id;
      $.post(ajaxUrl, post_data, function(data) {
        if (data.info) {
          that.render();
          that.model.showMessage(_campaign("Coupon Series successfully updated"));
        } else {
          that.model.showError(data.error);
          that.model.removeWait();
        }
      }, 'json');
    }
  },
  showValidProduct: function(e) {
    var a = $('#coupon_validity_val').val();
    $('#coupon_validity_value').text(a);
    if (a.toLowerCase() == 'custom') this.$('#c_product_details').removeClass('hide');
    else this.$('#c_product_details').addClass('hide');
  },
  viewProductDetails: function(e) {
    $('.wait_message').show().addClass('intouch-loader');
    var v_id = $(e.currentTarget).attr("v_s_id");
    var ajaxUrl = '/xaja/AjaxService/campaign_v2/get_coupon_prouct_details.json?voucher_series_id=' + v_id;
    var that = this;
    $.getJSON(ajaxUrl, function(data) {
      if (data) {
        var html = _.template($("#coupon_product_tpl").html(), data);
        that.$('#coupon_product_details').html(html);
        $("#view_coupon_validity_modal").modal("show");
        $('.wait_message').hide().removeClass('intouch-loader');
      }
      $('.wait_message').hide().removeClass('intouch-loader');
    });
  },
  toggleCategoryDesc: function(e) {
    this.$('#v_s_c_arrow').toggleClass('right-arrow down-arrow')
    this.$('#v_s_c_desc').toggle();
    this.$('#v_s_b_desc').hide();
    this.$('#v_s_b_arrow').removeClass('down-arrow').addClass('right-arrow');
  },
  toggleBrandDesc: function(e) {
    this.$('#v_s_b_arrow').toggleClass('right-arrow down-arrow');
    this.$('#v_s_b_desc').toggle();
    this.$('#v_s_c_desc').hide();
    this.$('#v_s_c_arrow').removeClass('down-arrow').addClass('right-arrow');
  },
  hideConfigureCoupon: function() {
    $('.msg-coupon').show();
    $('.coupon_details').show();
    $('.coupon_div').show();
    $('.points_div').show();
    $('.generic_div').show();
    $('#coupon-series-container').show();
    $('#a_c_series').removeClass('hide');
    $('#c_c_series').addClass('hide');
    $('.c-but-cont-show').addClass('hide');
    $('.edit-coupon-save-cont').addClass('hide');
    $('a#next-button-action').show();
    $('#show_coupon_button').hide();
    $('#configure_coupon').hide();
    $('#coupon_new_create').addClass('hide');
  },
});
//------------------------------ SMS Template View ----------------------------------------
templateView = Backbone.View.extend({
  events: {
    "click a#back_to_coupon": "backToCoupon",
    "click a#show_more_template_creative_assets": "showMoreTemplate",
    "click a#show_template_preview": "showTemplatePreview",
    "change select#template_scope": "showTemplateBasedOnType",
    "click i#refresh-list": "refreshTemplate",
    "keyup input#search_template": "searchTemplate",
    "click a#skip-template": "processTemplate",
    "click a#select_template": "processTemplate",
    "click a#create_new_template": "createNewTemplate",
    "click .template_selected": "selectTemplate",
    "click #edit_existing": "editExistingTemplate",
    "click .wechatSingleTemplate .templateContainer": "previewSingleImageTemplate",
    "click .wechatTemplateMessage .templateContainer": "previewWeChatTemplateMessages",
    "click .wechatMultiTemplate  .templateContainer": "previewMultiImageTemplate"
  },
  initialize: function() {},
  renderFn: function(model) {
    this.model = model;
    this.render();
  },
  render: function() {
    var that = this;
    this.model.checkSessionExpire();
    var message_id = this.model.getCache('message_id');
    var main_template = _.template($('#nav_bar_step_3').html(), {
      is_sms: 1,
      message_id: message_id,
      is_wechat: 1
    });
    this.$el.children('#nav_div_3').html(main_template);
    this.$el.find('#content_div_3').html(_.template($('#wechat_templates_collection_tpl').html()));
    var templateListHtml = '',
      weChatTemplateMessagesListHtml = '',
      templateMultiListHtml = '';
    var templates = this.model.get('templates');
    var singlePicTemplates = templates["single"],
      weChatTemplateMessages = templates["template_messages"],
      multiPicTemplates = templates["multi"];
    _.each(singlePicTemplates, function(template) {
      templateListHtml += _.template($('#wechat_single_tpl').html())({
        model: template
      });
    });
    _.each(weChatTemplateMessages, function(template) {
      var str = template.content;
      str = str.replace(/(?:\r\n|\r|\n)/g, '<br />');
      str = str.replace(/{{/g, '');
      str = str.replace(/}}/g, '');
      weChatTemplateMessagesListHtml += _.template($('#wechat_template_messgage_tpl').html())({
        model: template,
        data_content: str
      });
    });
    _.each(multiPicTemplates, function(template) {
      templateMultiListHtml += _.template($('#wechat_multipic_tpl').html())({
        model: template
      });
    });
    this.$el.find('.ca_all_container_body').find('.ca_spic_container_body').html(templateListHtml);
    this.$el.find('.ca_all_container_body').find('.ca_mpic_container_body').html(templateMultiListHtml);
    this.$el.find('.ca_all_container_body').find('.ca_template_messages_container_body').html(weChatTemplateMessagesListHtml);
    if ($("#msg_template_type").val()) {
      $('#template_scope').val($("#msg_template_type").val()).trigger('change')
    }
  },
  previewMultiImageTemplate: function(e) {
    $('#multi_image_preview_modal').remove();
    $('#multi_image_preview_modal .singlePic, .wechat-msg-content-container .close').off('click');
    var template_id = $(e.currentTarget).parent('.wechatMultiTemplate').attr('template-id');
    $('.ca_mpic_container_body').append(_.template($('#multi_image_preview_template').html())({
      model: _.findWhere(this.model.get('templates').multi, {
        template_id: template_id
      })
    }));
    $('#multi_image_preview_modal').modal('show');
    $('#multi_image_preview_modal .singlePic').on('click', function() {
      $('.wechat-msg-content-container[template=' + $(this).attr('template') + ']').removeClass('hide');
    });
    $('.wechat-msg-content-container .close').on('click', function() {
      $(this).parent('.wechat-msg-content-container').addClass('hide');
    });
  },
  previewWeChatTemplateMessages: function(e) {
    $('.ca_template_messages_container_body').append(_.template($('#template_message_preview_template').html()));
    $('#template_message_preview_modal').modal('show');
    var template_id = $(e.currentTarget).parent('.wechatTemplateMessage').attr('template-id');
    var currModel = _.findWhere(this.model.get('templates').template_messages,{
      template_id: template_id
    });
    var html_content = currModel.content;
    html_content = html_content.replace('{{first.DATA}}', 'first.DATA');
    html_content = html_content.replace('{{remark.DATA}}', 'remark.DATA');
    var obj = currModel.templates1[0].Data;
    for (var propt in obj) {
      html_content = html_content.replace(propt + '.DATA', obj[propt]['Value'].replace('{{', '').replace('}}', ''));
    }
    html_content = html_content.replace(/(?:\r\n|\r|\n)/g, '<br />');
    this.$('#template_message_preview_modal .preview_container').html(html_content);
  },
  previewSingleImageTemplate: function(e) {
    $('#single_image_preview_modal').remove();
    $('.wechat-msg-preview-container, .wechat-msg-content-container.hide .close').off('click');
    var template_id = $(e.currentTarget).parent('.wechatSingleTemplate').attr('template-id');
    $('.ca_spic_container_body').append(_.template($('#single_image_preview_template').html())({
      model: _.findWhere(this.model.get('templates').single, {
        template_id: template_id
      })
    }));
    $('#single_image_preview_modal').modal('show');
    $('.wechat-msg-preview-container').on('click', function() {
      $(this).addClass('hide');
      $('.wechat-msg-content-container.hide').removeClass('hide');
    });
    $('.wechat-msg-content-container.hide .close').on('click', function() {
      $(this).parent('.wechat-msg-content-container').addClass('hide');
      $('.wechat-msg-preview-container.hide').removeClass('hide');
    });
  },
  editExistingTemplate: function() {
    if ($("#msg_template_type").val() == "WECHAT_SINGLE_TEMPLATE") {
      $('.wechatSingleTemplate[template-id=' + $('#template_selected').val() + '] a.template_selected').trigger('click');
    }
    if ($("#msg_template_type").val() == "WECHAT_MULTI_TEMPLATE") {
      $('.wechatMultiTemplate [template-id=' + $('#template_selected').val() + '] a.template_selected').trigger('click');
    }
  },
  selectTemplate: function(e) {
    this.model.checkSessionExpire();
    this.model.addWait();
    switch($("#msg_template_type").val()){
      case "WECHAT_SINGLE_TEMPLATE":
        this.model.setCache('selectedTemplate', $(e.currentTarget).parents('.wechatSingleTemplate').attr('template-id'));
        this.model.setCache('qxuntemplateid', $(e.currentTarget).parents('.wechatSingleTemplate').attr('qxun-template-id'));
        if ($(e.currentTarget).attr("type") != $("#msg_template_type").val()) {
          $("#msg_template_type").val($(e.currentTarget).attr("type"));
        }
        $('#template_selected').val($(e.currentTarget).parents('.wechatSingleTemplate').attr('template-id')).attr({
          'qxun-template-id': $(e.currentTarget).parents('.wechatSingleTemplate').attr('qxun-template-id')
        });
        break;
      case "WECHAT_TEMPLATE":
        this.model.setCache('selectedTemplate', $(e.currentTarget).parents('.wechatTemplateMessage').attr('template-id'));
        if ($(e.currentTarget).attr("type") != $("#msg_template_type").val()) {
          $("#msg_template_type").val($(e.currentTarget).attr("type"));
        }
        $('#template_selected').val($(e.currentTarget).parents('.wechatTemplateMessage').attr('template-id')).attr({
          'template-id': $(e.currentTarget).parents('.wechatTemplateMessage').attr('template-id')
        });
        break;
      case "WECHAT_MULTI_TEMPLATE":
        this.model.setCache('selectedTemplate', $(e.currentTarget).parents('.wechatMultiTemplate').attr('template-id'));
        this.model.setCache('qxuntemplateid', $(e.currentTarget).parents('.wechatMultiTemplate').attr('qxun-template-id'));
        if ($(e.currentTarget).attr("type") != $("#msg_template_type").val()) {
          $("#msg_template_type").val($(e.currentTarget).attr("type"));
        }
        $('#template_selected').val($(e.currentTarget).parents('.wechatMultiTemplate').attr('template-id')).attr({
          'qxun-template-id': $(e.currentTarget).parents('.wechatMultiTemplate').attr('qxun-template-id')
        });
        this.model.setCache('singleImageTemplateIds', $(e.currentTarget).parents('.wechatMultiTemplate').attr('single-image-template-id'));
        this.model.setCache('articleid', $(e.currentTarget).parents('.wechatMultiTemplate').attr('article-id'));
        $('#template_selected').attr({
          'article-id': $(e.currentTarget).parents('.wechatMultiTemplate').attr('article-id'),
          'single-image-template-id': $(e.currentTarget).parents('.wechatMultiTemplate').attr('single-image-template-id')
        });
        break;
    }

    step4_view.renderFn(this.model);
    slidePage($(e.currentTarget));
    $('#container_3 #nav_div_3').hide();
    $('#container_4 #nav_div_4').show();
    this.model.removeWait();
  },
  backToCoupon: function(e) {
    slidePage($(e.currentTarget));
    $('#container_3 #nav_div_3').hide();
    $('#container_2 #nav_div_2').show();
  },
  showMoreTemplate: function(e) {
    $('.template-list_creative_assets').each(function() {
      if ($(this).hasClass('hide')) {
        $(this).removeClass('hide').addClass('show-less');
        $('#show_more_template_creative_assets').html(_campaign("Show Less Template"));
        $(this).addClass('search_enabled');
      } else if ($(this).hasClass('show-less')) {
        $(this).removeClass('show-less').addClass('hide');
        $('#show_more_template_creative_assets').html(_campaign("Show More Template"));
        $(this).removeClass('search_enabled');
      }
    });
  },
  showTemplatePreview: function(e) {
    var template_id = $(e.currentTarget).attr('template_id');
    $('#sms_preview_modal').modal();
    $('#sms_preview_modal').css('top', '65%');
    $('.sms_preview_modal').html($(e.currentTarget).parent().prev('li').html());
  },
  showTemplateBasedOnType: function(e) {
    switch ($(e.currentTarget).val()) {
      case 'WECHAT_SINGLE_TEMPLATE':
        $('.ca_spic_container_body').removeClass('hide');
        $('.ca_mpic_container_body').addClass('hide');
        $('.ca_template_messages_container_body').addClass('hide');
        break;
      case 'WECHAT_TEMPLATE':
        $('.ca_template_messages_container_body').removeClass('hide');
        $('.ca_spic_container_body').addClass('hide');
        $('.ca_mpic_container_body').addClass('hide');
        break;
      case 'WECHAT_MULTI_TEMPLATE':
        $('.ca_mpic_container_body').removeClass('hide');
        $('.ca_spic_container_body').addClass('hide');
        $('.ca_template_messages_container_body').addClass('hide');
        break;
    }
    $("#msg_template_type").val($(e.currentTarget).val());
  },
  refreshTemplate: function(e) {
    var that = this;
    this.model.checkSessionExpire();
    this.model.addWait();
    var campaign_id = $('#campaign_id').val();
    this.model.fetch({
      data: {
        campaign_id: campaign_id
      },
      success: function(response) {
        that.model.removeWait();
      }
    });
  },
  searchTemplate: function(e) {
    var search = $(e.currentTarget).val().toLowerCase();
    $('.search_enabled').addClass('hide');
    $('.l-h-list li').each(function() {
      if ($(this).attr('template_name') != undefined) {
        var val = $(this).attr('template_name').toLowerCase();
        if (val.indexOf(search) != -1) $(this).addClass('cmp-temp-search');
        else $(this).removeClass('cmp-temp-search');
      }
    });
    if (search.length < 1) {
      $('.l-h-list li ').removeClass("cmp-temp-search");
      $('.search_enabled').removeClass("hide");
    }
  },
  processTemplate: function(e) {
    this.model.checkSessionExpire();
    var template_id = $(e.currentTarget).attr('template_id');
    var campaign_id = $('#campaign_id').val();
    var message_id = $('#message_id').val();
    this.model.addWait();
    var program_id = this.model.getCache('program_id');
    console.log("program id " + program_id);
    var contentModel = new ajaxModel({
      id: "process_sms_tempalte.json"
    });
    contentModel.fetch({
      data: {
        template_id: template_id,
        campaign_id: campaign_id,
        message_id: message_id,
        program_id: program_id
      }
    });
    contentModel.on('change', function() {
      step4_view.renderFn(this);
      slidePage($(e.currentTarget));
      $('#container_3 #nav_div_3').hide();
      $('#container_4 #nav_div_4').show();
      this.removeWait();
    });
  },
  createNewTemplate: function(e) {
    showPopup("/campaign/assets/CreateNewTemplate?type=SMS_TEXT&reference_id=-1");
    $('#close').attr('onclick', '');
    var that = this;
    $('#close').live('click', function(e) {
      hidePopup();
      that.refreshTemplate();
    });
  }
});
editTempalteView = Backbone.View.extend({
  initialize: function() {},
  renderFn: function(model) {
    this.model = model;
    this.render();
  },
  render: function() {
    this.model.checkSessionExpire();
    var main_template = _.template($('#nav_bar_step_4').html(), {
      is_sms: 1,
      is_wechat: 1
    });
    this.$el.children('#nav_div_4').html(main_template);
    switch ($("#msg_template_type").val()) {
      case 'WECHAT_MULTI_TEMPLATE':
        var templateData = _.where(this.model.get('templates').multi, {
          template_id: this.model.getCache('selectedTemplate')
        });
        _.extend(templateData['0'], {
          isPreview: true
        });
        this.$el.find('#content_div_4').html(_.template($('#wechat_multipic_tpl').html())({
          model: templateData['0']
        }));
        break;
      case 'WECHAT_TEMPLATE':
        var templateData = _.where(this.model.get('templates').template_messages, {
          template_id: this.model.getCache('selectedTemplate')
        });
        templateData = templateData['0'];
        var str = templateData.content;
        var result = str.split('{{');
        var arr = [];
        var obj = [];
        for (j = 0; j < result.length; j++) {
          if (result[j].indexOf('}}') != -1) {
            var key = result[j].split('}}')[0].split('.DATA')[0];
            var str = key;
            var value = templateData.templates1['0'].Data[str].Value;
            arr.push({
              key: key,
              val: value
            });
          }
        }
        this.$el.find('#content_div_4').html(_.template($('#preview_wechat_template_messages').html())({
          model: templateData
        }));
        var item = this.$el.find('.c-show-template');

        var tempstr = templateData.templates1[0]['Url'];
        var tempurl = tempstr.substring(tempstr.indexOf("callback="),tempstr.indexOf("&scope")).split('=')[1];
        var tempsend = (tempstr!='{{wechat_service_acc_url}}') ? ( (tempstr.indexOf("&callback=") == -1) ? tempstr : tempurl  ) : '';
        var tempInternal = (tempstr.indexOf("&callback=") == -1) ? 0 : 1;

        item.html(_.template($('#details_tpl').html())({
          temp: templateData.templates1['0'],
          keylist: arr,
          url: decodeURIComponent(tempsend),
          isInternalUrl: tempInternal
        }));
        break;
      case 'WECHAT_SINGLE_TEMPLATE':
        var templateData = _.where(this.model.get('templates').single, {
          template_id: this.model.getCache('selectedTemplate')
        });
        this.$el.find('#content_div_4').html(_.template($('#preview_wechat_single_tpl').html())({
          model: templateData['0']
        }));
        break;
    }
    //sajwan
  },
  events: {
    "click a#back_to_template": "goBackToTemplate",
    "click li.parent_tags_menu": "openParent",
    "click ul.parent_email_sub_tag": "openSubTag",
    "click .parent_tags_menu2": "openSubmenu2",
    "click ul.parent_email_sub_tag2": "openEmailSubmenu",
    "keyup .editor": "updateOnKeyup",
    "click .editor_results": "countChar",
    "click a#goto_delivery_setting": "processEditTemplate"
  },
  openPreview: function(e) {
    this.model.checkSessionExpire();
    var campaign_id = $('#campaign_id').val();
    showPopup("/campaign/messages/v2/PreviewAndTest?campaign_id=" + campaign_id + "&plain=0&is_sms=1");
  },
  goBackToTemplate: function(e) {
    slidePage($(e.currentTarget));
    $('#container_4 #nav_div_4').hide();
    $('#container_3 #nav_div_3').show();
  },
  switchToTag: function(e) {
    var id = $(e.currentTarget).attr('id');
    $('.template-options').addClass('hide');
    $('#custom-' + id).toggleClass('hide');
    $('.template-function span').removeClass('active');
    $(e.currentTarget).addClass('active');
  },
  openParent: function(e) {
    var id = $(e.currentTarget).attr('id');
    var tag = id.split('__');
    $('#tags_submenu__' + tag[1]).toggleClass('hide');
    $('#submenu_icon__' + tag[1]).toggleClass('icon-caret-down');
    $('#submenu_icon__' + tag[1]).toggleClass('icon-caret-right');
  },
  openSubTag: function(e) {
    var id = $(e.currentTarget).attr('id');
    var tag = id.split('__');
    if (!$('#' + id).hasClass('hide')) {
      var s_var = id.split('tags_submenu__');
      $('#ptags__' + s_var[1] + ' > i').toggleClass('icon-caret-down');
      $('#ptags__' + s_var[1] + ' > i').toggleClass('icon-caret-right');
    }
    $('#' + id).toggleClass('hide');
  },
  openSubmenu2: function(e) {
    var id = $(e.currentTarget).attr('id');
    var tag = id.split('__');
    $('#tags_submenu2__' + tag[1]).toggleClass('hide');
    $('#submenu_icon2__' + tag[1]).toggleClass('icon-caret-down');
    $('#submenu_icon2__' + tag[1]).toggleClass('icon-caret-right');
  },
  openEmailSubmenu: function(e) {
    var id = $(e.currentTarget).attr('id');
    var tag = id.split('__');
    if (!$('#' + id).hasClass('hide')) {
      var s_var = id.split('tags_submenu2__');
      $('#ptags2__' + s_var[1] + ' > i').toggleClass('icon-caret-down');
      $('#ptags2__' + s_var[1] + ' > i').toggleClass('icon-caret-right');
    }
    $('#' + id).toggleClass('hide');
  },
  clearTextBoxCounter: function(e) {
    $(e.currentTarget).val('');
  },
  updateTextBoxCounter: function(boxVal) {
    var unicodeFlag = 0;
    var extraChars = 0;
    var msgCount = 0;
    var len_count = smsCharCount(boxVal);
    var len = len_count[0];
    var msgCount = len_count[1];
    var charLeft = len_count[2]["chars_left"];
    var totalChars = len_count[2]["total_chars"];
    var charset = len_count[2]["charset"];
    if (!charset && totalChars > 1000) {
      this.model.showError(_campaign("only 1000 character allowed in normal sms remaining character will be discarded"));
    } else if (charset && totalChars > 500) {
      this.model.showError(_campaign("only 500 character allowed in unicode sms remaining character will be discarded"));
    }
    $('.char_counter').html(len + _campaign(" characters = ") + msgCount + _campaign(" Messages*, Characters left: ") + charLeft);
  },
  updateOnKeyup: function(e) {
    var tagHtml = "<span class='editor_tag' title='%desc%' contenteditable='false'>%name%</span>";
    var tagText = "{{%name%}}";

    function escapeRegex(text) {
      return text.replace(/[-[\]{}()*+?.,\\^$|#]/g, "\\$&").replace(/\s/g, "\\s+").replace(/'|"/g, "(?:'|\")");
    }
    var boxVal = $(".editor").html().replace(new RegExp(escapeRegex(tagHtml).replace("%name%", "(.*?)").replace(/%.*?%/g, "(?:.*?)"), "g"), tagText.replace(/%name%/g, "$1")).replace(/\<(p|div|br).*?\>/g, "\\n").replace(/\<.*?\>/g, "");
    //var boxVal = "" + boxVal + String.fromCharCode(e.keyCode);
    var boxVal = "" + boxVal;
    this.updateTextBoxCounter(boxVal);
  },
  countChar: function(e) {
    this.updateOnKeyup($('.editor'));
  },
  processEditTemplate: function(e) {
    var that = this;
    this.model.checkSessionExpire();
    this.model.addWait();
    var campaign_id = $('#campaign_id').val();
    var msg_id = $('#message_id').val();
    var group_ids = $('#group_selected').val();
    var account_id = $('#account_id').val();
    switch($("#msg_template_type").val()){
      case "WECHAT_MULTI_TEMPLATE":
        var templateData = _.where(this.model.get('templates')["multi"], {
          template_id: this.model.getCache('selectedTemplate')
        });
        break;
      case "WECHAT_TEMPLATE":
        var templateData = _.where(this.model.get('templates')["template_messages"], {
          template_id: this.model.getCache('selectedTemplate')
        });
        break;
      case "WECHAT_SINGLE_TEMPLATE":
        var templateData = _.where(this.model.get('templates')["single"], {
          template_id: this.model.getCache('selectedTemplate')
        });
        break;
    }

    templateData = templateData['0'];
    var editModel = new ajaxModel({
      id: "proccess_plain.json"
    });
    editModel.fetch({
      data: {
        campaign_id: campaign_id,
        group_ids: group_ids,
        msg_id: msg_id,
        msg_type: 'WECHAT',
        account_id: account_id
      }
    });
    editModel.on('change', function() {
      this.set(templateData);
      step5_view.renderFn(this);
      slidePage($(e.currentTarget));
      $('#container_4 #nav_div_4').hide();
      $('#container_5 #nav_div_5').show();
      that.model.removeWait();
    });
  }
});
deliveryView = Backbone.View.extend({
  detailsTempl: '<ul><% _.each(rc,function(i,val){ %> <li><%= i  %></li> <% }); %></ul>',
  initialize: function() {},
  renderFn: function(model) {
    this.model = model;
    this.render();
  },
  render: function() {
    this.model.checkSessionExpire();
    var main_template = _.template($('#nav_bar_step_5').html(), {
      is_sms: 1
    });
    this.$el.children('#nav_div_5').html(main_template);
    var group_data = this.model.get('group_info');
    var series_info = this.model.get('series_details');
    var input_data = this.model.get('input_data');
    var org_credits = this.model.get('org_credits');
    var msg_details = this.model.get('msg_details');
    var sender_details = this.model.get('sender_details');
    var msg_id = $('#message_id').val();
    var campaign_id = $("#campaign_id").val();
    var sms_content = $('#message').val();
    var label = $('#sender_label').val();
    var from = $('#sender_from').val();
    var main_template = _.template($('#delivery_script').html(), {
      msg_data: group_data,
      series: series_info,
      input: input_data,
      is_sms: 0,
      sms_content: sms_content,
      label: label,
      mobile: from,
      org_credits: org_credits,
      campaign_id: campaign_id,
      sender_details: sender_details,
      is_wechat: true,
      account_id: $("#account_id").val()
    });
    this.$el.children('#content_div_5').html(main_template);
    //setting sender information    
    var coupon_attached = this.model.getCache('coupon_attached');
    if (coupon_attached == 0) {
      $('.coupon-container').hide();
    }
    this.setDefaultValue();
    $('#cron_month').multiselect({
      noneSelectedText: _campaign('Month'),
      selectedList: 30
    });
    $('#cron_month').multiselectfilter();
    $('#cron_week').multiselect({
      noneSelectedText: _campaign('Week'),
      selectedList: 30
    });
    $('#cron_week').multiselectfilter();
    $('#cron_day').multiselect({
      noneSelectedText: _campaign('Day'),
      selectedList: 30
    });
    $('#cron_day').multiselectfilter();
    this.addDatePicker();
    //    if( msg_details.is_ndnc_enabled == '1' )
    //      $('.is_ndnc').addClass('intouch-green-tick-active');
  },
  events: {
    "click a#back_to_edit": "goBackToEdit",
    "change select#send_when": "changeSchedule",
    "click a#queue_message": "queueMessage",
    "click span#test_and_preview": "openPreview",
    "click i.icon-pencil": "openEditLabel",
    "click .cancel-sender": "openEditLabel",
    "click .save-sender": "saveSender",
    "click span.plus-icon": "showMore",
    "click span#delivery_group": "downloadGroup",
    "click #save_sender_details": 'saveSenderDetails'
  },
  showMore: function(e) {
    var target = $(e.target);
    if (target.text() == _campaign("show more")) {
      var tmpl = _.template(this.detailsTempl);
      var data = this.model.attributes.group_info;
      $(".rec-more-cont").html(tmpl({
        rc: data.name
      })).slideDown();
      target.text(_campaign("show less"));
    } else {
      $(".rec-more-cont").slideUp();
      target.text(_campaign("show more"));
    }
  },
  saveSender: function(e) {
    var mobile = $('#mobile').val();
    var reg = /^[0-9a-zA-Z- ]{2,16}$/;
    if (!reg.test(mobile)) {
      $('.flash_message').addClass('redError').show().html(_campaign("Invalid sender mobile number"));
      setTimeout(function() {
        $('.flash_message').fadeOut('slow');
      }, 5000);
      return;
    }
    $('#sender_label').val($('#from').val());
    $('#sender_from').val($('#mobile').val());
    $('.sender-from').html(" From : " + $('#from').val());
    $('.sender-email').html(" Mobile : " + $('#mobile').val());
    $('.sender-info-delivery').toggleClass('hide');
  },
  openEditLabel: function(e) {
    $('#sender_info_modal').modal();
    //$('.sender-info-delivery').toggleClass('hide');
  },
  openPreview: function(e) {
    var campaign_id = $('#campaign_id').val();
    showPopup("/campaign/messages/v2/PreviewAndTest?campaign_id=" + campaign_id + "&plain=0&is_sms=1");
  },
  changeSchedule: function(e) {
    switch ($(e.currentTarget).val()) {
      case 'IMMEDIATE':
        $('#div_date_time').hide();
        $('#div_schedule').hide();
        $('#div_max_users').hide();
        break;
      case 'PARTICULAR_DATE':
        $('#div_date_time').show();
        $('#div_schedule').hide();
        $('#div_max_users').hide();
        break;
      case 'SCHEDULE':
        $('#div_date_time').hide();
        $('#div_schedule').show();
        $('#div_max_users').show();
        break;
    }
  },
  goBackToEdit: function(e) {
    this.model.checkSessionExpire();
    slidePage($(e.currentTarget));
    $('#container_5 #nav_div_5').hide();
    $('#container_4 #nav_div_4').show();
  },
  addDatePicker: function() {
    $('#date_time').datetimepicker({
      yearRange: '-50y:5y',
      showOn: 'both',
      buttonImage: '/images/calendar-icon.gif',
      buttonImageOnly: true,
      dateFormat: 'yy-mm-dd',
      clearText: '',
      changeMonth: true,
      changeYear: true,
      showSecond: false,
      timeFormat: 'hh:mm:ss'
    });
    $('#trigger__date_time').click(function() {
      $('#date_time').mobiscroll('show');
      return false;
    });
    $('#clear__date_time').click(function() {
      $('#date_time').val('');
      return false;
    });
  },
  saveSenderDetails: function() {
    var sender_details = this.model.get('sender_details');
    if (sender_details.length > 1) {
      var sender_idx = $('#ou_sender_from').val();
      $('.sender-from').html(" From : " + sender_details[sender_idx].sender_gsm);
      $('.sender-email').html(" Mobile : " + sender_details[sender_idx].sender_cdma);
      $('#sender_label').val(sender_details[sender_idx].sender_gsm);
      $('#sender_from').val(sender_details[sender_idx].sender_cdma);
    } else {
      $('.sender-from').html(" From : " + $("#ou_sender_from").val());
      $('.sender-email').html(" Mobile : " + $("#ou_sender_mobile").val());
      $('#sender_label').val($("#ou_sender_from").val());
      $('#sender_from').val($("#ou_sender_mobile").val());
    }
  },
  queueMessage: function(e) {
    ajaxModel1.checkSessionExpire();
    //this.model.checkSessionExpire();
    //    var is_ndnc = 0;
    var store_tag = $('input[name=store_tag]:checked').val();
    //    if( $('.is_ndnc').hasClass('intouch-green-tick-active'))
    //      is_ndnc = 1;
    var max_users = $('#max_users').val();
    var reg = /^[1-9]\d*$/;
    if (!reg.test(max_users)) {
      this.model.showError(_campaign("Maximum customers limit must be positive integer"));
      return false;
    }
    var params = {};
    // check for customer count
    var customer_count = this.model.getCache('customer_count');
    var send_when = $('#send_when').val();
    if (send_when != 'SCHEDULE' && customer_count < 1) {
      this.model.showError(_campaign("Selected list has no recipient!"));
      return false;
    }
    var allocation_strategy_id = this.model.getCache('allocation_strategy_id');
    var expiry_strategy_id = this.model.getCache('expiry_strategy_id');
    var till_id = this.model.getCache('till_id');
    var promotion_id = this.model.getCache('promotion_id');
    var program_id = this.model.getCache('program_id');
    var voucher_series_id = this.model.getCache('voucher_series_id');
    var selected_incentive = this.model.getCache('selected_incentive');
    var selected_generic = this.model.getCache('selected_generic');
    if (!selected_incentive) {
      this.model.setCache("selected_incentive", "none");
      this.model.setCache("selected_generic", "none");
      selected_incentive = "none";
      selected_generic = "none";
    }
    var inc_mapping_id = this.model.getCache('inc_mapping_id');
    var inc_elems = JSON.parse(this.model.getCache("inc_elems"));
    for (i in inc_elems) {
      if (inc_elems[i]['type'].toLowerCase() == selected_incentive) selected_incentive = i;
    }
    var accountDetails = (this.model.get('sender_details'));
    var qIDs = $('#template_selected').attr('qxun-template-id');
    switch($("#msg_template_type").val()){
      case "WECHAT_MULTI_TEMPLATE":
        qIDs = $('#template_selected').attr('article-id');
        params.singleImageTemplateIds = $('#template_selected').attr('single-image-template-id');
        _.extend(params,{
          message: '{"BrandId": "' + accountDetails.brand_id + '","PushName": "' + $("#msg_template_type").val() + '","Content": "' + qIDs + '","PushType": "3","PushInfo": "{OPENID}"}{{wechat}}'
        });
        break;
      case "WECHAT_TEMPLATE":
        var weChatFileServiceParams = this.model.get('file_service_params');

        // var datajsonforqxun = {};
        // var payload = {};

        // _.extend(datajsonforqxun, {
        //   BrandId: weChatFileServiceParams.BrandId,
        //   TemplateId: weChatFileServiceParams.TemplateId,
        //   OpenId: weChatFileServiceParams.OpenId,
        //   Url: weChatFileServiceParams.Url,
        //   TopColor: weChatFileServiceParams.TopColor,
        //   Data: weChatFileServiceParams.Data
        // });

        // _.extend(payload, {
        //   OriginalId: weChatFileServiceParams.OriginalId,
        //   DataJson: datajsonforqxun
        // });

        // _
        // .extend(params, {
        //   message: datajsonforqxun
        // });

        _.extend(params,{
          message: '{"TemplateId": "' + weChatFileServiceParams.TemplateId +
                   '","OpenId": "' + weChatFileServiceParams.OpenId +
                   '","OriginalId": "' + weChatFileServiceParams.OriginalId +
                   '","Title": "' + weChatFileServiceParams.Title +
                   '","BrandId": "' + weChatFileServiceParams.BrandId +
                   '","Url": "' + weChatFileServiceParams.Url +
                   '","TopColor": "' + weChatFileServiceParams.TopColor +
                   '","Data": ' + JSON.stringify(weChatFileServiceParams.Data) +
                  '}'
        });
        break;
      case "WECHAT_SINGLE_TEMPLATE":
        _.extend(params,{
          message: '{"BrandId": "' + accountDetails.brand_id + '","PushName": "' + $("#msg_template_type").val() + '","Content": "' + qIDs + '","PushType": "3","PushInfo": "{OPENID}"}{{wechat}}'
        });
        break;
    }



    _.extend(params, {
      // message: '{"BrandId": "' + accountDetails.brand_id + '","PushName": "' + $("#msg_template_type").val() + '","Content": "' + qIDs + '","PushType": "3","PushInfo": "{OPENID}"}{{wechat}}',
      subject: encodeURIComponent(this.model.get('template_name')),
      template_id: $('#template_selected').val(),
      title: encodeURIComponent(this.model.get('title')),
      name: encodeURIComponent(this.model.get('template_name')),
      summary: encodeURIComponent(this.model.get('summary')),
      image: this.model.get('image'),
      rule_selected: encodeURIComponent(this.model.getCache('rule_selected')),
      send_when: $('#send_when').val(),
      date_time: $('#date_time').val(),
      cron_day: $('#cron_day').val(),
      cron_week: $('#cron_week').val(),
      cron_month: $('#cron_month').val(),
      cron_hours: $('#cron_hours').val(),
      cron_minutes: $('#cron_minutes').val(),
      camp_group: $('#group_selected').val(),
      sender_label: $('#sender_label').val(),
      sender_from: $('#sender_from').val(),
      org_credits: this.model.get('org_credits'),
      store_type: store_tag,
      max_users: max_users,
      program_id: program_id,
      allocation_strategy_id: allocation_strategy_id,
      expiry_strategy_id: expiry_strategy_id,
      till_id: till_id,
      promotion_id: promotion_id,
      selected_incentive: selected_incentive,
      selected_generic: selected_generic,
      voucher_series_id: voucher_series_id[0],
      inc_mapping_id: inc_mapping_id,
      AppId: accountDetails.app_id,
      AppSecret: accountDetails.app_secret,
      ServiceAccoundId: accountDetails.id,
      OriginalId: accountDetails.original_id,
      msg_type: $("#msg_template_type").val()
    });
    var campaign_id = $('#campaign_id').val();
    var msg_id = $('#message_id').val();
    var ajaxUrl = '/xaja/AjaxService/messages/queue_message.json?campaign_id=' + campaign_id + '&msg_id=' + msg_id + '&msg_type=WECHAT';
    var model = this.model;
    $('.wait_message').show().addClass('indicator_1');
    $.post(ajaxUrl, {
      params: JSON.stringify(params)
    }, function(data) {
      if (!data.error) {
        if (model.getCache(selected_incentive) != 1) {
          model.setCache("selected_incentive", "none");
          model.setCache("selected_generic", "none");
        }
        $('.wait_message').hide().removeClass('indicator_1');
        window.onbeforeunload = null;
        window.location = '/campaign/v3/base/CampaignOverview#campaign/' + campaign_id;
      } else {
        model.showError(data.error);
        $('.wait_message').hide().removeClass('indicator_1');
      }
    }, 'json');
  },
  setDefaultValue: function() {
    var msg_details = this.model.get('msg_details');
    if (msg_details != undefined) {
      if (msg_details.send_when == 'PARTICULAR_DATE') {
        $('#send_when').val(msg_details.send_when);
        $('#date_time').val(msg_details.scheduled_on[0] + ' ' + msg_details.scheduled_on[1]);
        $('#div_date_time').show();
      } else if (msg_details.send_when == 'SCHEDULE') {
        $('#send_when').val(msg_details.send_when);
        $('#cron_day').val(msg_details.cron_days_month);
        $('#cron_week').val(msg_details.cron_week);
        $('#cron_month').val(msg_details.cron_months);
        $('#cron_hours').val(msg_details.cron_hours);
        $('#cron_minutes').val(msg_details.cron_minutes);
        $('#max_users').val(msg_details.max_users);
        $('#div_schedule').show();
        $('#div_max_users').show();
      }
      $("input[name=store_tag][value='" + msg_details.store_type + "']").attr('checked', 'checked');
    }
  },
  downloadGroup: function(e) {
    var targetObj = $(e.target);
    var gid = targetObj.attr("group_id");
    var cid = targetObj.attr("campaign_id");
    $.ajax({
      type: "GET",
      dataType: "json",
      url: "/xaja/AjaxService/filter/download.json?group_id=" + gid + "&campaign_id=" + cid,
      context: document.body
    }).done(function(obj) {
      if (obj.success == "SUCCESS") {
        $("#flash_message").html(obj.info).show();
        setTimeout(function() {
          $('#flash_message').hide().removeClass('redError');
        }, 5000);
      } else {
        $("#flash_message").addClass('redError').html(obj.info).show();
        setTimeout(function() {
          $('#flash_message').hide().removeClass('redError');
        }, 5000);
      }
    }).fail(function(obj) {
      $("#flash_message").addClass('redError').html(obj.info).show();
      setTimeout(function() {
        $('#flash_message').hide().removeClass('redError');
      }, 5000);
    });
  }
});
