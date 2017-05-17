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
//---------------------------- campaign mobilepush flow recipient list view ----------------------------
recipientView = Backbone.View.extend({
  initialize: function() {
    this.listenTo(this.model, 'change', this.render);
  },
  renderFn: function(model) {
    this.model = model;
    this.model.setCache('message_data',this.model.get('message_data')) ;
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
      is_mobilepush: 1,
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
      is_mobilepush: 1,
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
              is_sms: 1,
              is_mobilepush: 1
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
        $('#points_details').addClass('hide');
        $('#coupon_details').addClass('hide');
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
    });
    d_model.urlRoot = '/xaja/AjaxService/assets/get_all_mobile_push_templates.json?scope=MOBILEPUSH_TEMPLATE&mobpushScope=mobilepush_outbound';
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
          d_model.editTemplate = that.model ;
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
          $('.msg-coupon').show();
          $('#coupon-series-container').hide();
          $('.coupon_div').show();
          $('.points_div').show();
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
//------------------------------ Mobilepush Template View ----------------------------------------
templateView = Backbone.View.extend({
  events: {
    "click a#back_to_coupon": "backToCoupon",
    "click a#show_more_template_creative_assets": "showMoreTemplate",
    "keyup input#search_template": "searchTemplate",
    "click a#skip-template": "skipTemplateSelect",
    "click a#select_template": "processTemplate",
    "click .template_selected": "selectTemplate",
    'keyup .ca-search-text': 'renderSearch',
    'change #template_scope': 'renderNewScope'
    },
  initialize: function() {},
  renderFn: function(model) {
    this.model = model;
    this.previousTemplate = this.model.editTemplate ;
    this.render();
    $('.wait_message').hide().removeClass('indicator_1');
  },
  renderNewScope: function(){
    $('.wait_message').show().addClass('indicator_1');
        var that = this ;
    var d_model = new ajaxModel({
    });
    scope = $("#template_scope").val() ;
    d_model.urlRoot = '/xaja/AjaxService/assets/get_all_mobile_push_templates.json?scope='+scope+'&mobpushScope=mobilepush_outbound';
    d_model.fetch({
      data: {
        start: 0,
        limit: 18,
        account_id: $('#account_id').val()
      }
    });
    d_model.on('change', function() {
      if (d_model.get('error') == undefined) {
        that.model = d_model ;
        var templates = d_model.get('templates');
        var templateListHtml = '';
      _.each(templates, function(template) {
          templateListHtml += _.template($('#mobilepush_tpl').html())({
            model: template
          });
        });
        that.$el.find('.ca_all_container_body').find('.ca_spic_container_body').html(templateListHtml);
        $('.wait_message').hide().removeClass('indicator_1');
      } else {
        d_model.showError(d_model.get('error'));
        $('.wait_message').hide().removeClass('indicator_1');
      }
    });

  },
  renderSearch: function(e) {
    var searchTerm = $(e.currentTarget).val();
    var templates = this.model.get('templates');
    var templateListHtml = '';
    _.each(templates, function(template) {
        if(template.name.indexOf(searchTerm) > -1 ){
          templateListHtml += _.template($('#mobilepush_tpl').html())({
            model: template
          });
        }
    });
    this.$el.find('.ca_all_container_body').find('.ca_spic_container_body').html(templateListHtml);
  },
  render: function() {
    var that = this;
    this.model.checkSessionExpire();
    var message_id = this.model.getCache('message_id');
    var main_template = _.template($('#nav_bar_step_3').html(), {
      is_sms: 1,
      is_mobilepush:1,
      message_id: message_id,
    });
    this.$el.children('#nav_div_3').html(main_template);
    this.$el.find('#content_div_3').html(_.template($('#mobilepush_templates_collection_tpl').html()));
    var templates = this.model.get('templates');
    var templateListHtml = '';
    _.each(templates, function(template) {
      templateListHtml += _.template($('#mobilepush_tpl').html())({
        model: template
      });
    });
    this.$el.find('.ca_all_container_body').find('.ca_spic_container_body').html(templateListHtml);
    if ($("#msg_template_type").val()) {
      $('#template_scope').val($("#msg_template_type").val()).trigger('change');
    }
  },
  skipTemplateSelect: function(e){
    this.model.checkSessionExpire();
    this.model.addWait();
    var d_model = new ajaxModel({
    });
    message_id = $('#message_id').val() ;
    message_id = parseInt(message_id) ;
    campaign_id = $('#campaign_id').val() ;
    if(!isNaN(message_id) && message_id > 0){
      d_model.urlRoot = '/xaja/AjaxService/messages/get_mobile_push_data.json?campaign_id='
                        +campaign_id+'&message_id='+message_id;  
    }
    
    d_model.fetch();
    var that = this ;
    d_model.on('change', function() {
      if (d_model.get('error') == undefined ) {
        console.log("model is : "+d_model) ;
        that.model.skipTemplate = true ;
        that.model.skipTemplateModel = d_model ;
        step4_view.renderFn(that.model);
        slidePage($(e.currentTarget));
        $('#container_3 #nav_div_3').hide();
        $('#container_4 #nav_div_4').show();
        $('.wait_message').hide().removeClass('indicator_1');
      } else {
        d_model.showError(d_model.get('error'));
        $('.wait_message').hide().removeClass('indicator_1');
      }
    });    
  },
  selectTemplate: function(e) {
    this.model.checkSessionExpire();
    this.model.addWait();
    this.model.setCache('selectedTemplate',  $(e.currentTarget).attr('mobile_template_id'));
    this.model.template_type = $("#template_scope").val() ;
    this.model.skipTemplate = false ;
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
  }
});
editTempalteView = Backbone.View.extend({
  textAreaClick: true,
  initialize: function() {},
  renderFn: function(model) {
    this.model = model;
    this.render();
  },
  constructTemplateDataForEdit: function(strTemplateModel){
    supportedKeys = ["message","title","cta","expandableDetails"] ;
    template = [] ;
    templateObj = {} ;
    objTemplateModel = JSON.parse(strTemplateModel) ;
    templateObj.html_content = {} ;
    if(typeof objTemplateModel.templateData == 'undefined'){
      return ;
    }
    objTemplateModel = objTemplateModel.templateData ;
    this.model.template_type = "MOBILEPUSH_TEMPLATE" ;
    if(objTemplateModel.hasOwnProperty("ANDROID")){
      templateObj.isSecondaryTemplate = true ;
      templateObj.html_content.ANDROID = {} ;

      if(objTemplateModel.ANDROID.expandableDetails.style == "BIG_PICTURE"){
        this.model.template_type = "MOBILEPUSH_IMAGE" ;
      }
      for(var channelKey in objTemplateModel.ANDROID){
        if(objTemplateModel.ANDROID.hasOwnProperty(channelKey) &&
                             supportedKeys.indexOf(channelKey)>=0){
          templateObj.html_content.ANDROID[channelKey] = objTemplateModel.ANDROID[channelKey] ;   
        }
      }      
    }
    if(objTemplateModel.hasOwnProperty("IOS")){
      templateObj.isSecondaryTemplate = true ;
      templateObj.html_content.IOS = {} ;
      if(objTemplateModel.IOS.expandableDetails.style == "BIG_PICTURE"){
        this.model.template_type = "MOBILEPUSH_IMAGE" ;
      }
      for(var channelKey in objTemplateModel.IOS){
        if(objTemplateModel.IOS.hasOwnProperty(channelKey) &&
                             supportedKeys.indexOf(channelKey)>=0){
          templateObj.html_content.IOS[channelKey] = objTemplateModel.IOS[channelKey] ;   
        }
      }
    }

    template.push(templateObj) ;
    return template ;
  } ,
  render: function(e) {
    this.model.checkSessionExpire();
    var main_template = _.template($('#nav_bar_step_4').html());

    this.$el.children('#nav_div_4').html(main_template({is_sms: 1}));
        var tag = this.model.get('tags').MOBILEPUSH;
        var templateData = [] ;
        if(typeof this.model.skipTemplate != 'undefined' && this.model.skipTemplate){
          templateData = this.constructTemplateDataForEdit(this.model.skipTemplateModel.get("message_data")) ;
        }else{
          templateData = _.where(this.model.get('templates'), {
            template_id: this.model.getCache('selectedTemplate')
          });    
        }
                
        console.log("template",templateData);
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
       var tags = changeTags(tag);
        this.$el.find('#content_div_4').html(_.template($('#ca_mobile_push_template_tpl').html())({
         data:{ model: templateData['0'],
          tags : tags
        }}));
       this.renderMobilePush(e,templateData);
       this.registerImageUpload() ;
       // $('a#preview_and_test').addClass('hide');
       
  },
  events: {
    "click a#back_to_template": "goBackToTemplate",
    'click .ca-mobile-push-title': 'tagAreaClick',
    'click .ca-mobile-push-textarea': 'tagAreaClick',
    "click a#goto_delivery_setting": "processEditTemplate",
    'click .tags-container li .parent ': 'openTag',
    'click #mob_android' : 'activeTab',
    'click #mob_ios' : 'activeTab',
     'click .insert_tag': 'insertTag',
    'click .ca-mobile-push-add': 'addSecondaryCta' ,
    'click #reset-primary-cta-android': 'resetPrimaryCta',
    'click .ca-mobile-push-copy' : 'mobilePushCopyText',
    'click .ca-mobile-push-delete-container': 'deleteContainer',
    'click .ca-mobile-push-add-ios' : 'constructIosSecondaryCta',
    'click .ca-mobile-push-reset-ios-div' : 'deleteIOSContainer',
    'click .ca-mobile-push-cta' : 'getSecondaryDetailIOSContainer',
    'click .ca-mobile-icon-ios':'deleteIOSDetailContainer',
    'click .upload_image_file' : 'showMobileImageUpload',
    'change #upload_image_android': 'uploadMobilePushImage',
    'change #upload_image_ios': 'uploadMobilePushImage',
    'click a#preview_and_test': "openPreviewforPlainText",
    'keyup .ca-mobile-push-textarea': 'updateOnKeyup'
  },
  tagAreaClick: function(e){
       if($(e.currentTarget).hasClass('ca-mobile-push-textarea')){
          this.textAreaClick = true ; 
       }else{
          this.textAreaClick = false ; 
       } 
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
  openPreviewforPlainText: function(e) {
    var campaign_id = $('#campaign_id').val();
    showPopup("/campaign/messages/v2/PreviewAndTest?campaign_id=" + campaign_id + "&plain=0&is_sms=0&is_mobilepush=1");
  },
  mobilePushCopyText:function(e){
    var target_input = $(e.currentTarget).parent().next().children(); 
    if(this.currentTab == "android"){
      container = $("#mob_ios_container");
      count_container = $("#mob_android_container");
    }
    if(this.currentTab == "ios"){
      container = $("#mob_android_container");
      count_container = $("#mob_ios_container");
    }
    var get_input = container.find("#"+target_input.attr("id")).val();
    var set_attr_input = target_input.attr("id")+"-"+this.currentTab;
    target_input.attr(set_attr_input,get_input);
    target_input.val(get_input); 
    if($(target_input).get(0).tagName=='TEXTAREA'){
      var msgCount = 0;
      msgCount = get_input.length;
      count_container.find('.show-count').html( msgCount );
    }
  },
  resetPrimaryCta: function() {
    $('.primary-link').removeAttr('checked');
    $('#ca-mobile-push-primary').val('');
  },

  updateOnKeyup : function(e,textarea_container){
    tab_value = $(".mobile-push-tabs").children(".active").children().attr("id");
    if(tab_value=="android")
      var container = $("#mob_android_container");
    if(tab_value=="ios")
      var container = $("#mob_ios_container");
    var msgCount = 0
    if(textarea_container)
        msgCount = textarea_container.val().length;
    else
        msgCount = $(e.currentTarget).val().length;
    
    var msg_str = $(e.currentTarget).val();
    container.find('.show-count').html( msgCount );
  },

  showMobileImageUpload: function() {
    tab_value = $(".mobile-push-tabs").children(".active").children().attr("id");
    if(tab_value=="android")
      var container = $("#mob_android_container");
    if(tab_value=="ios")
      var container = $("#mob_ios_container");
    container.find('#upload_image_'+tab_value).trigger('click');
  },
  uploadMobilePushImage: function() {
    tab_value = $(".mobile-push-tabs").children(".active").children().attr("id");
    if(tab_value=="android")
      var container = $("#mob_android_container");
    if(tab_value=="ios")
      var container = $("#mob_ios_container"); 
    container.find("#image_upload_"+tab_value).submit();
    container.find('#image_upload_'+tab_value).val('');
  },
  deleteIOSDetailContainer:function(e){
    $(".IOS-secondary-detail-block").empty();
    $(".secondary-CTA-IOS").show();
  },
  deleteIOSContainer:function(e){
    $(".secondary-CTA-IOS").empty();
    $(".secondary-CTA-IOS").hide();
    $(".ca-mobile-push-add-ios").show();
  },
  constructIosSecondaryCta: function(e,template){
    this.model.addWait();
    var tmpl = _.template($("#create_IOS_secondary_tpl").html());
       $(".add-secondary-IOS").html(tmpl());
       $(".ca-mobile-push-add-ios").hide();

        sec_cta = this.getIosSecondaryCta();
        var cta_tmpl = _.template($("#create_IOS_secondary_tpl_cta").html());
      _.each(sec_cta.response.data,function(model,key){
        console.log("cta modellll",model);
      $(".ca-mobile-push-ios-cta").append(cta_tmpl({model : model}));
      });
      if(!_.isUndefined(template)){

         $(".secondary-CTA-IOS").hide();
       }
      this.model.removeWait() ;      
  },
  getIosSecondaryCta: function(){
      mobile_push_account = $('#account_id').val();
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
        sec_cta = this.getIosSecondaryCta(mobile_push_account);   
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
            if(templateData[0].ctaTemplateDetails[i].toLaunchApp == false)
             continue;
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
    $(".IOS-secondary-detail-block").html(tmpl({ios_name : ios_name, categoryId : categoryId , 
    CtasObj : CtasObj}));  
                 
    },
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
                return false;                  
               }else if(sec_btn.is(':checked') && sec_link.val().length == 0){
                primary_link.addClass("red-error-border");
                item['msg'] = "please fill primary CTA";
                item['modelFlag'] = false;   
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
  deleteContainer:function(e){
    var no = $(e.currentTarget).attr('ca-mobile-no');
    $("#mob_android_container").find(".secondary-CTA"+no).remove();
    $("#mob_android_container").find(".secondary-CTA"+no).empty(); 
    var size =$("#mob_android_container").find(".secondary-CTA" ).size();
    if(size <= 1)
      $("#mob_android_container").find('.ca-mobile-push-hide').show();
  },
  addSecondaryCta : function(){
    this.createSecondary({}) ;
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
   
    if(event_id == "android"){
      $("#mob_android").addClass("active");
      $("#mob_ios").removeClass("active");
    }
    if(event_id == "ios"){
      $("#mob_android").removeClass("active");
      $("#mob_ios").addClass("active");
   
    }
    console.log("event_id",this.currentTab);
    
    if(this.currentTab == "android"){
       $("#mob_android_container").show();
    }
    if(this.currentTab == "ios"){
       $("#mob_ios_container").show();
    }
    
  },
  openTag : function(e){
    $(e.currentTarget).addClass( "active" );   
    $(e.currentTarget).parent().next('ul').toggle();  
    var inner_div = $(e.currentTarget).parent().next('ul');
    if($(inner_div).is(':visible'))
      $(e.currentTarget).parent().prev().html('<i class="icon-caret-down"></i>');
    else
       $(e.currentTarget).parent().prev().html('<i class="icon-caret-right"></i>');         

   },
  insertTag: function(e) {
      tab_value = $(".mobile-push-tabs").children(".active").children().attr("id");
      if(tab_value=="android")
        var container = $("#mob_android_container");
      if(tab_value=="ios")
        var container = $("#mob_ios_container");

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
     setText : function(e,target_val){
      tab_value = $(".mobile-push-tabs").children(".active").children().attr("id");
      if(tab_value=="android")
        var container = $("#mob_android_container");
      if(tab_value=="ios")
        var container = $("#mob_ios_container");
      if(target_val)
         target_val.attr(target_val.attr("id")+"-"+tab_value,target_val.val());
      else
      $(e.currentTarget).attr($(e.currentTarget).attr("id")+"-"+tab_value,$(e.currentTarget).val());
    },
  renderMobilePush:function(e,templateData){
    template =templateData['0'];
    console.log("modelllllll",template);
    var tmpl = _.template($("#create_mobile_push_template_tpl").html());
    $("#mob_android_container").html(tmpl({data:{template:  template, tab_value : "android" , template_scope : this.model.template_type}}));
    $("#mob_ios_container").html(tmpl({  data:{template:  template, tab_value :"ios" , template_scope : this.model.template_type}}));
    if(!_.isUndefined(template.template_id) || (!_.isUndefined(template.isSecondaryTemplate) && template.isSecondaryTemplate)){
      if(!_.isUndefined(template.html_content.ANDROID) &&
          !_.isUndefined(template.html_content.ANDROID.expandableDetails.ctas))
          this.createSecondary(template);
      if(!_.isUndefined(template.html_content.IOS) &&
            !_.isUndefined(template.html_content.IOS.expandableDetails) && 
              !_.isUndefined(template.html_content.IOS.expandableDetails.categoryId)){
        this.constructIosSecondaryCta(e,template);
        this.getSecondaryDetailIOSContainer(e,template);
       }
    }
  },
  registerImageUpload: function(){
    var self = this;
    _.each(['ios','android'],function(tab_value){
      if(tab_value=="android")
        var container = $("#mob_android_container");
      if(tab_value=="ios")
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
                //self.showMessage(_campaign("Image uploaded successfully"))
                } else {
                  //self.showError(data.error, true)
                }
                $('.wait_message').hide().removeClass('indicator_1');
              }
            });
            return false;
          }
        });
   });
  },
    createSecondary : function(mod){
     var tmpl = _.template($("#secondary_tpl").html()); 
     if(!_.isUndefined(mod.html_content) && !_.isUndefined(mod.html_content.ANDROID) && !_.isUndefined(mod.html_content.ANDROID.expandableDetails) && 
                !_.isUndefined(mod.html_content.ANDROID.expandableDetails.ctas)){
        length = mod.html_content.ANDROID.expandableDetails.ctas.length;
        console.log("length",length);
        if(length == 2)
            $("#mob_android_container").find('.ca-mobile-push-hide').hide();
          for(i=0;i<length;i++){
            $('.ca-mobile-push-show-secondary').append(tmpl({ data:{no: i, mod:  mod,tab_value :this.currentTab }}));     
          }        
      }else{
          var size =$("#mob_android_container").find(".secondary-CTA").size();
          if(size === 0)
            cta_no = 0;
          else
            cta_no = parseInt($("#mob_android_container").find(".secondary-CTA").attr("ca-mobile-push-key"))+1;
     
          
          if(size >= 1)
            $("#mob_android_container").find('.ca-mobile-push-hide').hide();  

          var flag = true ;
          if(size > 0)
            var flag = this.validateSecondary($('.ca-mobile-push-show-secondary'));
          if(flag == false){
            $("#mob_android_container").find('.ca-mobile-push-hide').show(); 
            return false;
          }
          $('.ca-mobile-push-show-secondary').append(tmpl({data:{ no: cta_no }})); 
      }
      },            
      validateSecondary :function(container){
        var modelFlag = true;
        var sec_cta=container.find(".secondary-CTA");
          _.each(sec_cta, function(t){
            var i =$(t).attr("ca-mobile-push-key");
            var sec_text = container.find("#ca-mobile-push-label"+i);
            var sec_link = container.find(".ca-mobile-push-secondary"+i);
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
  goBackToTemplate: function(e) {
    slidePage($(e.currentTarget));
    $('#container_4 #nav_div_4').hide();
    $('#container_3 #nav_div_3').show();
  },
  isSaveChannelTemplate($channel){
    title = "" ;
    message = "" ;
    if($channel == "android"){
      title = $("#mob_android_container #ca-mobile-push-title").val() ;
      message = $("#mob_android_container #ca-mobile-push-textarea").val() ;
    }else if($channel == "ios"){
      title = $("#mob_ios_container #ca-mobile-push-title").val() ;
      message = $("#mob_ios_container #ca-mobile-push-textarea").val() ;
    }

    if(title != "" && message != ""){
      return true ;
    }else{
      return false ;
    }
  },
  validatePrimary: function(linkType, tempLink){
    if(typeof linkType == 'undefined' && (typeof tempLink == 'undefined' || tempLink == "")){
      return true ;
    }
    errorText = '' ;
    if(typeof linkType == 'undefined'){
      //TODO: how to provide feedback to user
      return false ;
    }

    if(typeof tempLink == 'undefined' || tempLink == ""){
      //TODO: how to provide feedback to user
      return false ;
    }
    return true ;
  },
   validateCTA :function(container){
     var item = {};
     item['modelFlag'] = true;   
     var primary_btn=container.find(".primary-link");
     var primary_link = container.find("#ca-mobile-push-primary")
     var sec_link ;
     if(primary_btn.is(':checked') && primary_link.val().length == 0){
            primary_link.addClass("red-error-border");
            item['msg'] = "please fill primary CTA";
            item['modelFlag'] = false;   
     }
     else if(!primary_btn.is(':checked') && primary_link.val().length !== 0){
            item['msg'] = "please select primary CTA";
            item['modelFlag'] = false;   
     }
     else
      primary_link.removeClass("red-error-border");


     return item;

   },
  validateFormDetails:function(e){
    var flag =true;
    var tab_value = $('.mobile-push-tabs').find('.active').find('a').attr('id').toLowerCase();
    var container = (tab_value == 'android') ? $("#mob_android_container") : $("#mob_ios_container");
    var title = container.find("#ca-mobile-push-title");
    var msg = container.find("#ca-mobile-push-textarea");
    var img = container.find('.mobile_push_image_file').find("img").attr('src');
     if(title.val().length == 0){
          this.showError(_campaign("please fill title"));
          title.addClass("red-error-border");
          flag = false;
      }else if(msg.val().length == 0){
          this.showError(_campaign("please fill message"));
          msg.addClass("red-error-border");
          flag = false;
      }else if(!_.isUndefined(img) && _.isEmpty(img)){
          this.showError(_campaign("please upload image."));
          flag = false;
      }else if(tab_value=="android" && !this.validateSecondary(container)){
          this.showError(_campaign("please fill Secondary CTA"));
          flag = false;
      } else if(tab_value=="ios" && !this.validateSecondaryIOS(container)){
          this.showError(_campaign("please fill IOS Secondary CTA"));
          flag = false;
      }else if(!this.validateCTA(container)['modelFlag']){
          this.showError(_campaign(this.validateCTA(container)['msg']));
          flag = false;
      }

       if(flag===false)
            return false;     

  }
  ,
  processEditTemplate: function(e) {
     var flag = this.validateFormDetails();
     if(flag===false)
          return;
     templateData = this.getFormObject(); 
    var that = this; 
    var group_ids = $('#group_selected').val();
    var msg_id = $('#message_id').val();
    var account_id = $('#account_id').val();
    var campaign_id = $('#campaign_id').val();
    var editModel = new ajaxModel({
        id: "proccess_plain.json"
      });
    editModel.fetch({
      data: {
        campaign_id: campaign_id,
        group_ids: group_ids,
        msg_id: msg_id,
        msg_type: 'PUSH',
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
    
  },
  getFormObject:function(){
    this.model.checkSessionExpire();
    this.model.addWait();
    var templateData = _.where(this.model.get('templates'), {
      template_id: this.model.getCache('selectedTemplate')
    });
    templateData = templateData['0'];
    if(this.isSaveChannelTemplate("android")){
      templateData.html_content.ANDROID.title = $("#mob_android_container #ca-mobile-push-title").val() ;
      templateData.html_content.ANDROID.message = $("#mob_android_container #ca-mobile-push-textarea").val() ;      
      ctaType = $('input[name="primary-link-android"]:checked').val() ;
      tempLink = $("#mob_android_container #ca-mobile-push-primary").val() ;
      if(typeof ctaType != 'undefined'){
        templateData.html_content.ANDROID.cta = {} ;
        if(ctaType == 'deep-link'){
          templateData.html_content.ANDROID.cta.type = "DEEP_LINK" ;  
        }else if( ctaType == 'external-link'){
          templateData.html_content.ANDROID.cta.type = "EXTERNAL_URL" ;  
        }
        templateData.html_content.ANDROID.cta.actionLink = tempLink ;
      }
      templateData.html_content.ANDROID.expandableDetails = {} ;
      if(this.model.template_type == "MOBILEPUSH_IMAGE"){
        templateData.html_content.ANDROID.expandableDetails.style =  "BIG_PICTURE" ;
        templateData.html_content.ANDROID.expandableDetails.image = $('#mob_android_container .mobile_push_image_file img').attr('src') ;
      }else if(this.model.template_type == "MOBILEPUSH_TEMPLATE"){
        templateData.html_content.ANDROID.expandableDetails.style =  "BIG_TEXT" ;
      }

      templateData.html_content.ANDROID.expandableDetails.message = $("#mob_android_container #ca-mobile-push-textarea").val() ;
      if($('#mob_android_container .secondary-CTA').length >0 ){        
        templateData.html_content.ANDROID.expandableDetails.ctas = [] ;
        $("#mob_android_container .secondary-CTA").each(function(index){
          ctaIndex = $(this).attr("ca-mobile-push-key") ;
          secondaryCtaObj = {} ;
          secondaryCtaObj.actionText = $("#mob_android_container #ca-mobile-push-label"+ctaIndex).val() ;
          checkedRadio = $('#mob_android_container input[name=secondary-link'+ctaIndex+']:checked').val() ;
          if(checkedRadio == 'deep-link'){
            secondaryCtaObj.type = 'DEEP_LINK' ;
          }else if(checkedRadio == 'external-link'){
            secondaryCtaObj.type = 'EXTERNAL_URL' ;
          }
          secondaryCtaObj.actionLink = $("#mob_android_container .ca-mobile-push-secondary"+ctaIndex).val() ;
          templateData.html_content.ANDROID.expandableDetails.ctas[index] = secondaryCtaObj ;
        }) ;
      }

    }
    
    if(this.isSaveChannelTemplate("ios")){
      templateData.html_content.IOS = {} ;
      templateData.html_content.IOS.title = $("#mob_ios_container #ca-mobile-push-title").val() ;
      templateData.html_content.IOS.message = $("#mob_ios_container #ca-mobile-push-textarea").val() ;      
      ctaType = $('input[name="primary-link-ios"]:checked').val() ;
      if(typeof ctaType != 'undefined'){
        actionLink = $("#mob_ios_container #ca-mobile-push-primary").val() ;
        if(actionLink == 'undefined' || actionLink.trim() == ''){
          //TODO: highlight the unselected 
          return ;
        }
        templateData.html_content.IOS.cta = {} ;
        if(ctaType == 'deep-link'){
          templateData.html_content.IOS.cta.type = "DEEP_LINK" ;  
        }else if( ctaType == 'external-link'){
          templateData.html_content.IOS.cta.type = "EXTERNAL_URL" ;  
        }
        templateData.html_content.IOS.cta.actionLink = $("#mob_ios_container #ca-mobile-push-primary").val() ;
      }

      templateData.html_content.IOS.expandableDetails = {} ;
      if(this.model.template_type == "MOBILEPUSH_IMAGE"){
        templateData.html_content.IOS.expandableDetails.style =  "BIG_PICTURE" ;
        templateData.html_content.IOS.expandableDetails.image = $('#mob_ios_container .mobile_push_image_file img').attr('src') ;
      }else if(this.model.template_type == "MOBILEPUSH_TEMPLATE"){
        templateData.html_content.IOS.expandableDetails.style =  "BIG_TEXT" ;
      }
      templateData.html_content.IOS.expandableDetails.message = $("#mob_ios_container #ca-mobile-push-textarea").val() ;

      if($('#mob_ios_container .secondary-cta-show').length >0 ){
        templateData.html_content.IOS.expandableDetails.categoryId = $("#ios_category_id").val() ;      
        templateData.html_content.IOS.expandableDetails.ctas = [] ;
        $("#mob_ios_container .secondary-cta-show").each(function(index){
          ctaIndex = $(this).attr("ca-mobile-push-key") ;
          secondaryCtaObj = {} ;
          secondaryCtaObj.actionText = $("#mob_ios_container #ca-mobile-push-label"+ctaIndex).attr("ca-mobile-push-label"+ctaIndex+"-ios") ;
          checkedRadio = $('#mob_ios_container input[name=ios-secondary-link'+ctaIndex+']:checked').val() ;
          secondaryCtaObj.templateCtaId = $("#ca-mobile-push-ios-"+ctaIndex).val() ;
          if(checkedRadio == 'deep-link'){
            secondaryCtaObj.type = 'DEEP_LINK' ;
          }else if(checkedRadio == 'external-link'){
            secondaryCtaObj.type = 'EXTERNAL_URL' ;
          }
          secondaryCtaObj.actionLink = $("#mob_ios_container #ca-mobile-push-secondary"+ctaIndex).val() ;
          templateData.html_content.IOS.expandableDetails.ctas[index] = secondaryCtaObj ;
        }) ;
      }      
    }
    return templateData;
  }
});
deliveryView = Backbone.View.extend({
  detailsTempl: '<ul><% _.each(rc,function(i,val){ %> <li><%= i  %></li> <% }); %></ul>',
  initialize: function() {},
  renderFn: function(model) {
    this.model = model;
    this.render();
  },
  getPushChannelCount: function(channel){
    channelCount = 0 ;
    for(var key in channel){
      if(channel.hasOwnProperty(key)){
        channelCount += parseInt(channel.key) ;
      }  
    }
    return channelCount ;
  },
  render: function() {
    this.model.checkSessionExpire();
    var main_template = _.template($('#nav_bar_step_5').html(), {
      is_sms: 1
    });
    this.$el.children('#nav_div_5').html(main_template);
    var customer_count = this.model.getCache('customer_count');
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
      customer_count: customer_count,
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
      is_mobilepush: true,
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
    "click .save-sender": "saveSender",
    "click span.plus-icon": "showMore",
    "click span#delivery_group": "downloadGroup",
    "click #save_sender_details": 'saveSenderDetails'
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
    var accountDetails = this.model.get('sender_details');
    if(typeof this.model.get('html_content').ANDROID != 'undefined'){
      this.model.get('html_content').ANDROID['luid']="{{luid}}";
      this.model.get('html_content').ANDROID['cuid']="{{cuid}}";
       this.model.get('html_content').ANDROID['communicationId']="{{communicationId}}";
    }   
     if(typeof this.model.get('html_content').IOS != 'undefined'){
        this.model.get('html_content').IOS['luid']="{{luid}}";
        this.model.get('html_content').IOS['cuid']="{{cuid}}";
        this.model.get('html_content').IOS['communicationId']="{{communicationId}}";
     }     
    var templateData = this.model.get('html_content');
    _.extend(params, {
      message: encodeURIComponent(JSON.stringify({"templateData" : templateData})),
      subject: encodeURIComponent(this.model.get('name')),
      template_id: this.model.get('template_id'),
      //title: encodeURIComponent(this.model.get('title')),
      //name: encodeURIComponent(this.model.get('template_name')),
      //summary: encodeURIComponent(this.model.get('summary')),
      //image: this.model.get('image'),

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
      accountDetails: accountDetails,
      //ServiceAccoundId: accountDetails.id,
      //OriginalId: accountDetails.original_id,
      //msg_type: $("#msg_template_type").val()
    });
    console.log("params",params);
    var campaign_id = $('#campaign_id').val();
    var msg_id = $('#message_id').val();
    var ajaxUrl = '/xaja/AjaxService/messages/queue_message.json?campaign_id=' + campaign_id + '&msg_id=' + msg_id + '&msg_type=PUSH';
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
