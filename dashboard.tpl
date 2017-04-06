<script id="initialrender" type="text/template">
  <input type='hidden' id='start' value='0' />
<input type='hidden' id='end' value='10' />
<div class='c-menu'>
    <ul class='c-menu-cont'>
        <li class='c-menu-list dashboard'>
          <i class='c-selected c-db-selected sel'></i>
            <i class='dashboard-icon c-home sel'></i>
            <span class='c-menu-lable'><?= _campaign("Home"); ?></span>
        </li>
        <%if(rc.is_perf == 1){%>
        <li class='c-menu-list performance'>
      <i class='c-selected c-pf-selected'></i>
      <i class='dashboard-icon c-performance'></i>
      <span class='c-menu-lable'><?= _campaign("Performance"); ?></span>
    </li>
    <%}%>
        <li class='c-menu-list creative'>
          <i class='c-selected c-ca-selected'></i>
            <i class='dashboard-icon c-creatives'></i>
            <span class='c-menu-lable'><?= _campaign("Creatives"); ?></span>
        </li>
        <li class='c-menu-list sticky'>
          <i class='c-selected c-sl-selected'></i>
            <i class='dashboard-icon c-sticky'></i>
            <span class='c-menu-lable'><?= _campaign("Sticky Lists"); ?></span>
        </li>
        <li class='c-menu-list credits'>
          <a id='org_credits_menu' data-container="body" data-toggle="popover" data-placement="right" data-content="" data-original-title="" title="">
            <i class='dashboard-icon c-credits'></i><span class='c-menu-lable' ><?= _campaign("Credits"); ?></span></a>
          <div id="pop_credit" class="pop-credit popover right">
            <div id="close_credit_pop" class="cnew-close">×</div>
        <div class="arrow" style=""></div>
        <div class="popover-content" id="credit_popover">
          <div class='credit_wait_message'></div>
          <div class='summary_title'><?= _campaign("Credits"); ?></div>
          <div class='create-campaigns clearfix'>
            <div class='credit_summary pull-left'>
              <ul class='l-v-list'>
                <li class='item credit-type'> 
                  <span class='item'><?= _campaign("SMS:"); ?> </span>
                  <span id="sms_credit_value" class='credit-value'></span>
                </li>
                <li class='item credit-type'>
                  <span class=''><?= _campaign("EMAIL:"); ?> </span>
                  <span id="email_credits" class='credit-value'></span>
                </li>
                <li class='item'>
                  <div class="popover-button" id="buy_credits"><?= _campaign("Buy Credits"); ?></div>
                </li>
              </ul>
            </div>
            <div class='sms_credit_hidden hide'>
              <label id='error1' class='hide red-error'><?= _campaign("Please Enter Valid Numeric Value!"); ?></label>
              <input type='text' name='sms_credit_val' id='sms_credit_val' placeholder=<?= _campaign('sms credit') ?> value='' class='credit-text'/>
              <input type='hidden' name='old_credit' id='old_sms_credit' value='' />
              <ul class='l-h-list'>
                <li class='item'>
                  <div id="buy_more_credits" class='popover-button buy_more_shown'><?= _campaign("Buy More"); ?></div>
                </li>
                <li class='item'>
                  <input type='button' value="<?= _campaign('Cancel') ?>" id='cancel_credit' class='btn' />
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
        </li>
  </ul>
    
</div>

<div class='c-content'>
    <div class="wait_initial_form"></div>
    <div id="container">
    </div>
</div>
</script>
<script id="dashboardview" type="text/template">
  <div class='d-content-cont'>
        <div class='c-header'>
            <div class='c-title'><?= _campaign("Campaigns Home"); ?></div>
            <div class='c-but-cont'>
                <div class='c-button' id='new-campaign'>+ <?= _campaign("New Campaign"); ?></div>
            </div>
            <div class='clearfix'></div>
        </div>
        <div class='c-options-cont'>
            <div class='c-fright'>
                <div class='c-status c-fleft' id="c-type-status">
                    <div class='c-status-head'><?= _campaign("Status"); ?></div>
                    <div class='c-status-option'>
                        <a class='c-status-live sel' status-type='Live' href='#status/live'><?= _campaign("Live") ?></a><a class='c-status-lapsed' status-type='Lapsed' href='#status/lapsed'><?= _campaign("Lapsed"); ?></a><a class='c-status-upcoming' status-type='Upcoming' href='#status/upcoming'><?= _campaign("Upcoming"); ?></a>
                    </div>
                </div>
                <div class='c-type c-fleft'>
                    <div class='c-type-head'><?= _campaign("Campaign Type"); ?></div>
                    <select class='c-type-select' id="c-type-select">
                      <option value='all'><?= _campaign("All"); ?></option>
                        <option value='outbound'><?= _campaign("Outbound"); ?></option>
                        <option value='action'><?= _campaign("Bounceback") ?></option>
                        <option value='referral'><?= _campaign("Referral"); ?></option>
                        <option value='survey'><?= _campaign("Survey"); ?></option>
                        <option value='timeline'><?= _campaign("Timeline"); ?></option>
                    </select>
                </div>
                <div class='c-fleft c-tracker'>
                  <div class="c-tracker-inner">
                  <div class="c-status-head"><?= _campaign("Bulk Sms/Email Tracking"); ?></div>
                  <a id="campaign_tracker" class="c-button-grey inline-block" data-toggle="modal" data-target="#model_campaign_tracker" ><i class="icon-download-alt"></i> <?= _campaign("Download Campaign Tracker"); ?></a>
                  </div>
                </div>
            </div>
            <div class='c-search'>
                <div class='search-container'>
                    <i class='c-search-icon c-fleft'></i>
                    <input type='text' class='c-search-text c-fright' id="c-type-search" placeholder=<?= _campaign("campaign name") ?> />
                </div>
            </div>
            <div class='clearfix'></div>
        </div>
        <div class='nocamp-msg'>
            <span class='c-nodata hide'><?= _campaign("No campaign found"); ?></span>
        </div>
        <div class='display-headtitle row-fluid'>
           <div class='span5'><?= _campaign("Campaign Name"); ?></div>
           <div class='span5 title-head'><?= _campaign("Valid Between"); ?></div>
           <div class='span2'><?= _campaign("Actions"); ?></div>
        </div>
        <div class='camp-scroll table-cont'>
            <div class='c-table-layout display-table c-dashboard-layout'>
                <div class='wait_message_form'></div>
            </div>
            <div class='showmore-cont'>
                <a class='c-showmore' href='#showmore/10' id="c-type-showmore"><?= _campaign("show more"); ?></a>
            </div>
        </div>
        
    </div>
    <div class='c-content-cont next camp-scroll' id="create_new_campaign">
      <div class="new-c-create">
    <div class="new-c-content">
      <div class='c-title'><?= _campaign("New Campaign"); ?></div>
      <form method="post" action="" id="newcampaign" name="newcampaign">
        
        <div class="form-controls main">
          <label for="campaign_type"><?= _campaign("Campaign Type"); ?></label>
          <div class="input-container">
            <select id="campaign_type" name="campaign_type" class="input-nsl">
              <option value="outbound"><?= _campaign("Outbound"); ?></option>
              <option value="bounceback"><?= _campaign("Bounceback"); ?></option>
              <option value="referral"><?= _campaign("Referral"); ?></option>
              <option value="survey"><?= _campaign("Survey"); ?></option>
              <option value='timeline'><?= _campaign("Timeline"); ?></option>
            </select>
          </div>
        </div>

        <div class="form-controls main">
          <label for="campaign_name"><?= _campaign("Campaign Name"); ?>*</label>
          <div class="input-container">
            <input type="text" id="campaign_name" name="campaign_name" 
            class="input-nsl margin_left_5 validate[required;skip_locale<zh-cn>;regexcheck</^[0-9a-zA-Z][0-9 _a-zA-z]{0,48}[0-9a-zA-Z]$/>;custom_val<<?= _campaign('Use only alphanumeric , underscore, space & maximum upto 50 characters') ?>>]">
          </div>
        </div>

              <div class="form-controls main">
                <label for="campaign_desc"><?= _campaign("Description"); ?></label>
                <div class="input-container">
                  <textarea type="text" id="campaign_desc" name="campaign_desc" class="input-nsltextarea"></textarea>
                </div>
              </div>
        
        <div class="form-controls for-timeline hide" id="timeline-minutes">
          <label for="timeline_start_minute"><?= _campaign("Active between"); ?></label>
          <div class="input-container">
            <select id="timeline_start_minute" name="timeline_start_minute" 
            class="input-nsl margin_left_5"></select>
          </div>
          <label id="tl-minutes1"><?= _campaign("and"); ?></label>
          <div class="input-container">
            <select id="timeline_end_minute" name="timeline_end_minute" 
            class="input-nsl"></select>
          </div>
          <label id="tl-minutes2"><?= _campaign("hrs everyday"); ?></label>
        </div>

        <div class="form-controls main" id="daterange-container">
          <label><?= _campaign("Valid between"); ?>*</label>
          <div class="input-container">
            <ul class="unstyled l-h-list">
            <li class="item c-range"><input type="text" class="datepicker-newcamp" id="cnew_start_date" 
              name="cnew_start_date" readonly class="margin_left_5 validate[required];"></li>
            <li class="item c-range"><i class="dashboard-icon camp-clock"> </i></li>
            <li class="item c-range"><input type="text" class="datepicker-newcamp" id="cnew_end_date" 
              name="cnew_end_date" readonly class="margin_left_5 validate[required];"></li>
            </ul>
          </div>
        </div>

        <div id="addcampaigntemplate" class="main">
            <div class="wait_metadata_form intouch-loader"></div>
        </div>

        <div id="addoucampaigntemplate" class="main">
            <div class="wait_metadata_form intouch-loader"></div>
        </div>

        <div id="for-outbound-advanced" class="form-controls for-outbound for-timeline">
            <label></label>
              <div class="input-container">
            <i class="icon icon-plus icon-large outbound-adv-txt"></i><span class="outbound-adv-txt"> <?= _campaign("Show Advanced"); ?></span>
          </div>
          </div>
          <div id="for-outbound-settings" class="form-controls hide">
            <label></label>
            <div class="input-container">
            <i class="icon icon-minus icon-large outbound-adv-txt"></i><span class="outbound-adv-txt"> <?= _campaign("Hide Advanced"); ?></span>
          </div>
          </div>
              
        <div class="for-outbound-holder hide">
            <div class="form-controls for-outbound for-timeline">
            <label></label>
            <div class="input-container">
              <input type="checkbox" id="is_test_control_enabled" name="is_test_control_enabled" class="">
              <label for="is_test_control_enabled" class="outbound-test-control"><?= _campaign("Use custom Test-Control split for lists in this Campaign"); ?></label>
              <label for="is_test_control_enabled" class="timeline-test-control hide"><?= _campaign("Disable Test-Control for this Campaign"); ?></label>
            </div>
          </div>
          <div class="form-controls for-outbound for-timeline">
            <label></label>
            <div class="input-container">
              <input type="checkbox" id="is_ga_enabled" name="is_ga_enabled" class="">
              <label for="is_ga_enabled"><?= _campaign("Track using Google Analytics"); ?></label>
            </div>
          </div>
          <div class="form-controls for-outbound for-timeline hide" id="ga_track">
            <label><?= _campaign("GA Name");?></label>
            <div class="input-container">
              <input type="text" id="ga_name" name="ga_name" class="input-nsl">
            </div>
          </div>
          <div class="form-controls for-outbound for-timeline hide" id="ga_source">
            <label><?= _campaign("GA Source"); ?>*</label>
            <div class="input-container">
              <input type="text" id="ga_source_name" name="ga_source_name" class="input-nsl">
            </div>
          </div>
          <div class="form-controls for-outbound">
              <label></label>
              <div class="input-container">
                <input type="checkbox" id="isRecoCamp" name="isRecoCamp" class="">
                <label for="isRecoCamp"><?= _campaign("Is this for a Recommendation enabled Campaign"); ?></label>
              </div>
          </div>
          <div class="form-controls for-outbound hide" id="selectReco">
            <label><?= _campaign("Select Plan"); ?>*</label>
            <div class="input-container">
              <select id="reco_campaigns" name="reco_campaigns" class="">
              </select>
              </div>
          </div>
          <div class="form-controls for-outbound for-timeline" id="conquest_schedule">
            <label></label>
            <div class="input-container">
              <input type="checkbox" id="report_schedule" name="report_schedule" class="">
              <label for="report_schedule"><?= _campaign("Send automated reports"); ?></label>
            </div>
          </div>
                  <div class="form-controls for-outbound">
                    <label></label>
                    <div class="input-container">
                      <input type="checkbox" id="isRefCamp" name="isRefCamp" class="">
                      <label for="isRefCamp"><?= _campaign("Is this for a Referral Campaign"); ?></label>
                    </div>
                  </div>
                  <div class="form-controls for-outbound hide" id="selectReferral">
                    <label><?= _campaign("Select Campaign"); ?>*</label>
                    <div class="input-container">
                      <select id="referral_campaigns" name="referral_campaigns" class="">
                      </select>
                    </div>
                  </div>
                  <div class="form-controls for-outbound">
                    <label></label>
                    <div class="input-container">
                      <input type="checkbox" id="isSurveyCamp" name="isSurveyCamp" class="">
                      <label for="isSurveyCamp"><?= _campaign("Is this for a Survey Campaign"); ?></label>
                    </div>
                  </div>
                  <div class="form-controls for-outbound hide" id="selectSurvey">
                    <label><?= _campaign("Select Campaign"); ?>*</label>
                    <div class="input-container">
                      <select id="survey_campaigns" name="survey_campaigns" class="">
                      </select>
                    </div>
                  </div>
          <div class="form-controls for-outbound">
              <label></label>
              <div class="input-container">
                <input type="checkbox" id="enable_roi_reports" name="enable_roi_reports" class="">
                <label for="roi_report" class="roi_report"><?= _campaign("Send ROI Reports"); ?></label>
                <div class="form-controls for-outbound for-timeline hide" id="selectRoiReportType">
                  <label><?= _campaign("ROI Report Type"); ?></label>
                  <div class="input-container">
                     <select id="roi_report_type" name="roi_report_type"></select>
                  </div>
                </div>
              </div>
          </div>
          <div class="form-controls for-outbound for-timeline" id="recipient_list">
            <label><?= _campaign("Recipient List"); ?></label>
            <div class="select-container">
              <select>
                <option><?= _campaign("Segmented"); ?></option>
                <option><?= _campaign("Unsegmented"); ?></option>
              </select>
            </div>
          </div>
          <div class="form-controls for-outbound for-timeline" id="ca_tags">
            <label><?= _campaign("Add Tags"); ?></label>
            <div class="input-container">
              <input type="text" id="ca_tag_names" name="ca_tag_names" class="input-nsl">
            </div>
            <label><?= _campaign("(Comma seperated)"); ?></label>
          </div>
        </div>
        <div class="form-controls for-referral hide">
          <label><?= _campaign("Incentivize Referrer"); ?></label>
          <div class="input-container">
            <select id="incentivize" name="incentivize">
              <option value="TRIGGER"><?= _campaign("Dynamically as he reached the criteria"); ?></option>
              <option value="FINAL"> <?= _campaign("At the end of campaign"); ?> </option>
            </select>
          </div>
        </div>
        <div class="form-controls for-referral hide">
          <label></label>
          <div class="input-container">
            <input type="checkbox" id="is_test_control_enabled_for_referral" name="is_test_control_enabled_for_referral" class="">
          <label for="is_test_control_enabled_for_referral"><?= _campaign("Disable Test-Control for this Campaign"); ?></label>
          </div>
        </div>
        <div class="form-controls for-referral hide">
          <label></label>
          <div class="input-container">
            <input type="checkbox" id="defaultPos" name="defaultPos" class="">
            <label class="for-pos" for="defaultPos"><?= _campaign("Make Default for POS"); ?></label>
            <i class="icon-question-sign pos-info" data-toggle="tooltip" title="<?= _campaign("This campaign will be activated on POS") ?>"></i>
          </div>
        </div>
        <div class="form-controls for-referral hide" id="incentive_container">
          <label><?= _campaign("Refer By"); ?></label>
          <div class="input-container">
            <select id="refer_type" name="refer_type">
              <option value="0" selected="selected"> <?= _campaign("Email or Mobile"); ?> </option>
              <option value="1"><?= _campaign("Email"); ?></option>
              <option value="2"><?= _campaign("Mobile"); ?></option>
            </select>
          </div>
        </div>
        <div class="form-controls for-referral hide">
          <label></label>
          <div class="input-container">
            <input type="checkbox" id="invite_loyalty" name="invite_loyalty" class="">
            <label class="for-pos" for="invite_loyalty"><?= _campaign("Invite Registered Customers"); ?></label>
            <i class="icon-question-sign pos-info" data-toggle="tooltip" title="<?= _campaign("This campaign will invite registered customers") ?>"></i>
          </div>
        </div>
         <div class="form-controls for-bounceback hide">
         <label></label>
          <div class="input-container">
            <input type="checkbox" id="is_test_control_enabled_for_bounceback" name="is_test_control_enabled_for_bounceback" class="">
            <label for="is_test_control_enabled_for_bounceback"><?= _campaign("Disable Test-Control for this Campaign"); ?></label>
          </div>
        </div>
        <div class="form-controls for-referral hide">
          <label></label>
          <div class="input-container">
            <input type="checkbox" id="online" name="online" class="">
            <label for="online"><?= _campaign("Register Customer Online"); ?></label>
          </div>
        </div>
        <div class="form-controls for-referral hide" id="microsite_container">
          <label><?= _campaign('Link to Microsite') ?>*</label>
          <div class="input-container microsite-container">
            <input type="text" id="microsite" name="microsite" class="microsite-link">
          </div>
        </div>
        <div class="form-controls for-survey hide">
          <label><?= _campaign("Survey Type"); ?></label>
          <div class="input-container">
              <select id="survey_type" name="survey_type">
              </select>
            <div class="text-info"><?= _campaign("Survey type is for reporting purpose"); ?></div>
          </div>
        </div>
        <div class="form-controls for-survey hide">
          <label><?= _campaign("Brand Logo"); ?></label>
          <div class="input-container">
            <input type="text" id="brand_logo" name="brand_logo" class="">
          </div>
        </div>
        <div class="form-controls main">
          <label></label>
          <div class="input-container">
            <input type="hidden" name="c_org_id" id="c_org_id" value="-1">
            <input type="button" id="CreateNewCampaign" class="bttn-submit" value=<?= _campaign("Create Campaign"); ?>>
            <input type="button" id="cancel_create" value=<?= _campaign("Cancel"); ?> class="cancel-btn">
          </div>
        </div>
      </form>
    </div>  
    </div>
    </div>
    <div id="question" style="display:none; cursor: default">
    <h5 id="question_referral"></h5>
    <button id="ref_yes" class="btn btn-primary"><i class="icon icon-ok-sign"></i><?= _campaign("Yes"); ?></button>
    <button id="ref_no" class="btn"><i class="icon icon-remove-sign"></i><?= _campaign("No"); ?></button>
  </div>
</script>
<script id="c-listview" type="text/template">
  <div class='clearfix'></div>
    <div class='display-tablecell'>
        <div class='camp-name-cont'>
        <i class='dashboard-icon c-camp-icons c-<%- rc.campaign_type %>'></i>
        <span class='camp-name' data-toggle="tooltip" title="<%- rc.campaign_name %>">
          <a class='camp-name' href='<%- rc.base_url %>'><%- rc.campaign_name %></a>
        </span>
        </div>
         <div class='camp-desc'>
            <%= rc.description %>
        </div>
    </div>
    <div class='display-tablecell'>
        <div class='camp-date-cont'>
            <span class='c-startdate'><%- rc.start_date %></span>
            <i class='dashboard-icon c-clock'> </i>
            <span class='c-enddate'><%- rc.end_date %></span>
        </div>
    </div>
    <div class='display-tablecell'>
        <div class='btn-group btn-tablecell'>
            <%index=0; _.each( rc.actions, function( item,key )
                {
                index++ ;
                if(index==1){
            %>
            <button type='button' id=<%-key%><%-rc.id%>  class='btn' onclick="window.location.href='<%-item%>'">
            <%-key%>
            </button>
            <button type='button' id=<%-key%><%-rc.id%> class='btn dropdown-toggle' data-toggle='dropdown'>
                <span class='caret'></span><span class='sr-only'></span>
            </button>
            <ul class='dropdown-menu camp-menu' role='menu'>
                <li><%}else{ %><a id=<%-key%><%-rc.id%> href='<%-item%>'><%-key%></a> <%} }); %></li>
            </ul>
        </div>
    </div>
</script>
<script id="stickyview" type="text/template">
  <div class='c-content-cont'>
        <div class='c-header'>
            <div class='c-title'><?= _campaign("Sticky Lists"); ?></div>
            <div class='c-but-cont'>
                <div class='c-button' id='newlist'>+ <?= _campaign("New List"); ?></div>
            </div>
            <div class='clearfix'></div>
        </div>
                <!-- <div class='c-options-cont'>
                    <div class='c-fleft margin-top-20'>
                        <div class='c-type c-fleft'>
                            <select class='c-type-select'>
                                <option value='10'>10 <?= _campaign("Entries per page"); ?></option>
                                <option value='20'>20 <?= _campaign("Entries per page"); ?></option>
                                <option value='30'>30 <?= _campaign("Entries per page"); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class='c-search'>
                        <div class='search-container'>
                            <i class='c-search-icon c-fleft'></i>
                            <input type='text' class='c-search-text c-fright' placeholder='<?= _campaign("list name"); ?>' />
                        </div>
                    </div>
                    <div class='clearfix'></div>
                </div>
               
               <div class='c-table-layout display-table sticky-table'>
                    <div class='wait_message_form'></div>
                    <div class='display-tablerow sticky-table-head'>
                        <div class='display-tablecell-st'><?= _campaign("Group Label"); ?></div>
                        <div class='display-tablecell-st'><?= _campaign("Customer Count"); ?></div>
                        <div class='display-tablecell-st'></div>
                    </div>-->
            <div class='c-sticky-cont'>
                <table class="c-table-layout sticky-table">
                    <thead><tr><th><?= _campaign("Group Label");  ?></th><th><?= _campaign("Customer Count"); ?></th><th>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp</th></tr></thead>
                    <tbody>
                    </tbody>
                </table>
           
            </div>
    </div>
    
     <div class="c-content-cont next" id="create_new_list">
         <div class='c-title' style="float:none"><?= _campaign("Create New Sticky List"); ?></div><br>
         <div style="position:relative;">
         <iframe id="listform" src="/campaign/v3/ListForm" border=0 width=100% height=300px></iframe>
         </div>
        
    </div>
</script>
<script id="performance_view" type="text/template">
  <div style="position:relative;height:100%">
      <iframe id='performance_frame' src='' border=0 width=100% height= 100% ></iframe>
    </div>
</script>
<script id="creativeview" type="text/template">
  <div style="position:relative;height:100%">
      <iframe id='creativeframe' src='/campaign/assets/v3/CreativeAssetsHome?flash='  border=0 width=100% height= 100% ></iframe>
    </div>
</script>
<script id="sticky-list-view" type="text/template">
  <div class='display-tablecell-st'><%-rc.group_label %></div>
    <div class='display-tablecell-st'><%-rc.customer_count %></div>
    <div class='display-tablecell-st'>
        <span class='cu-add' id='<%- rc.group_id %'><?= _campaign("Add Customer"); ?></span>
        <span class='cu-remove' id='<%- rc.group_id %'><?= _campaign("Remove"); ?></span>
        <span class='cu-group' id='<%- rc.group_id %'><?= _campaign("Add Group Tags"); ?></span>
    </div>
</script>
<script id="creative_tpl" type="text/template">
  <div style="position:relative;height:100%" id="creative_tpl_container">
      <div class="ca-header">
        <div class="ca-title"><?= _campaign("Creative Assets") ?></div>
        <div class="ca-option-div" id="ca-option-div">
          <select name="template_type" id="template_type">
            <option value="email" selected><?= _campaign("Email Templates")?></option> 
            <option value="coupon"><?= _campaign("Coupon Templates")?></option>
            <option value="image"><?= _campaign("Image Gallery")?></option>
            <option value="social"><?= _campaign("Wechat Templates")?></option>
            <option value="mobile_push" class="mobile_push"><?= _campaign("Mobile Push Templates")?></option>
        </select>
      </div>
      </div>
      <div id="email_template_div" class="ca-body">
      </div>
      <div id="image_gallery_div" class="ca-body">
      </div>
      <div id="coupon_template_div" class="ca-body">      
      </div>
      <div id="social_template_div" class="ca-body">      
      </div>
      <div id="mobile_push_template_div" class="ca-body"></div>

    </div>
</script>
<script type="text/template" id="account-type">
  <div class="ca-option-div" id="ca-option-mobilePushAccounts">
  <select name="mobile-push-accounts" id="mobile-push-accounts">
    <% if (_.isUndefined(rc.mobilePushAccounts) || !rc.mobilePushAccounts.length) { %>
        <option><?= _campaign("No accounts created") ?></option>
      <% }else{ %>
    <% 
    _.each(rc.mobilePushAccounts, function(v, k) { %>
      <option value="<%= v.id %>"> <%= v.account_name %></option>
    <% }); %>
  </select>
    <% if (rc.mobilePushAccounts.length) { %>
      <span class="ca-option-scope-separator"> &nbsp;/&nbsp;</span>
    <% }} %>
  </div>
</script>
<script id="insert_image_tpl" type="text/template">
  <div>
    <div class="modal fade image-gallery-modal" id="image_gallery_modal" style="width:90%;left: 25%">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <span class="ca-modal-header"><?= _campaign("Insert Image") ?></span>
            <button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="image_gallery_container">

            </div>
          </div>
          <div class="modal-footer">  
            <button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Cancel") ?> </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</script>
<script id="lang_based_content" type="text/template">
  <div class="lang_create_new_container">     
        <div class="ca-dib" style="float:left;margin-left:23px"><button class="back_view ca-grey-btn"> < <?= _campaign("Back")?></button></div>
        <div class="ca-main-header ca-title" style="width:50%;margin-left:5%"><span style="font-weight:bold"><?= _campaign('Creative Assets')?> -</span> <%=template_spec.template_type%> / <span id="lang_content_scope"><%=template_spec.scope%></scope></div>
        <div class="ca-dibm" style="float:right">
          <button class="ca-g-btn ca-save-btn save_all_templates"> <?= _campaign("Save") ?> </button>
          <button class="ca-grey-btn delete_template"> <?= _campaign("Delete") ?> </button>
      </div>
      <div class="ca-dibm lang-edit-name-container" >
      <div class="ca_edit_favourite_icon ca-cursor-pointer" style="float:left">
        <% if(model.is_favourite){%>
        <i class="icon-heart favourite-indicator"></i>
        <%}else {%>
        <i class="icon-heart-empty favourite-indicator"></i>
        <%}%>
      </div>
      <div id="lang_edit_template_name" class="ca-edit-template-name" style="float:left"> <%=model.template_name%></div>
      <div id="lang_edit_name" class="ca-cursor-pointer" style="float:left"><i class="icon-pencil"></i></div>
    </div>      
    </div>

    <div id="lang_tab_parent" style="margin-bottom:1px">
        <ul id="lang_list" style="list-style-type: none">
            <li lang_id="<%=base_lang.lang_id%>" class="lang_tab"><%=base_lang.lang_name%></li> 
        </ul>
        <span id="add_lang" style="cursor:pointer;margin-left:3px"><u>+<?= _campaign("new language"); ?></u></span>
    </div>
    <div id="lang_content_parent" style="border:1px solid #b7b7b7;display:none">
        <% _.each(languages,function(option,key){%>
        <div class="template_editor" editor_lang_id="<%=option.lang_id%>" style="display:none"></div>
      <%});%>
    </div>
    <div id="create_new_template_parent" style="border:1px solid #b7b7b7;display:none">
      <div class="new_template_header" style="margin-bottom:20px">
        <div class="ca-layout-header" style="margin-left:2%"><?= _campaign("Select or upload a layout to begin")?></div>
        <div class="btn-group" role="group" style="float:right;margin-right:2%;margin-top:5px">
        <button type="button" class="ca-g-btn ca-new-btn btn dropdown-toggle" data-toggle="dropdown" aria-expanded="false"> <?= _campaign("Upload")?>
          <span class="caret ca-fright"></span>
        </button>
        <ul class="dropdown-menu" role="menu">
          <li><a class="upload_html_file"><?= _campaign("HTML file")?></a></li>
          <li><a class="upload_zip_file"> <?= _campaign("ZIP File")?></a></li>          
        </ul>
        <div class="hide"> 
          <input type="file" id="file_upload" name="file_upload"/>
          </div>
      </div>
      </div>  
      <% _.each(languages,function(option,key){%>
        <div class="create_new_template" template_lang_id="<%=option.lang_id%>" style="display:none;width:96%;margin:0 auto"></div>
      <%});%>
    </div>

    <div id="add_lang_modal"  class="modal hide fade">
    <div class="modal-header">
      <div class="ca-modal-header"><?= _campaign("Add new language") ?></div>
      <button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="modal-body">
      <p><?= _campaign('Choose the language to be added')?> </p>
      <select id="lang_select">
      <option selected disabled value='1'><?= _campaign('Choose here')?></option>
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

  <div id="lang_new_name_modal"  class="modal hide fade lang_enabled_show">
    <div class="modal-header">
      <div class="ca-modal-header"><?= _campaign("New Template") ?></div>
      <button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="modal-body">
      <p><?= _campaign("Name template")?> </p>
      <input type="text" id="lang_new_template_name" value="<%=model.template_name %>" /> 
      <p><?= _campaign("Scope")?> </p>
      <select id="lang_new_template_scope">
      
        <%_.each(scopes_available,function(value,key) { %>
            <option value="<%=value%>" <%if(template_spec.scope == value){%>selected = true<%}%> > <%=key%> </option>   
        <%});%>
                       
      </select>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn ca-g-btn" id="save_name_scope"> <?= _campaign("Save")?> </button>
      <button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Cancel")?> </button>
    </div>
  </div>

<div>
    <div class="modal fade" id="lang_edit_name_modal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <div class="ca-modal-header"> <?= _campaign("Rename template") ?> </div>
            <button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>"">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            
            <input type="text" id="lang_rename_template" value="<%= model.template_name %>" />
          </div>
          <div class="modal-footer">  
            <button type="button" class="btn ca-g-btn" id="change_name"> <?= _campaign("Save") ?> </button>
            <button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Cancel") ?> </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</script>

<script id="templates_collection_tpl" type="text/template">
  <div id="ca_templates_collection_div">
    <div class="all_wait_loader"></div>

    <div class="ca_top_create_new_container hide">
      <div class="ca-dib"><button class="back_view ca-grey-btn"> < <?= _campaign("Back")?></button></div>
      <div class="ca-layout-header"><?= _campaign("Select a layout to begin")?></div>
      <div class="ca_create_new_container ca-create-new-container"></div>
      <div style="position:relative; height:100px" ><div class="ca_default_loader" style="display:none"></div></div>
    </div>

    <div class="ca_top_view_container">
      <div class="ca-container-header">
        <span><?= _campaign("View Only:")?></span>
        <div class="ca-option-div">
          <select name="template_type" id="template_type">
            <option value="email" class="email"><?= _campaign("Email Templates")?></option> 
            <option value="coupon" class="coupon"><?= _campaign("Coupon Templates")?></option>
            <option value="image" class="image"><?= _campaign("Image Gallery")?></option>
            <option value="social" class="social"><?= _campaign("WeChat Templates")?></option>
            <option value="mobile_push" class="mobile_push"><?= _campaign("Mobile Push Templates")?></option>
          </select>
        </div>
      <% if(template_type !== 'image') {%>
        <span class="ca-option-scope-separator">/</span>
         <% if(template_type == 'mobile_push') {%>
        <span class="ca-account-type"></span>
          <%}%>
        <div class="ca-template-scope">
          <select name="template_scope" id="template_scope">
            <%_.each(scopes_available,function(value,key) { %>
             <option value="<%=value%>" <%if(value == template_scope){%>selected = true<%}%> ><%=key%> </option> 
          <%});%>
          </select>
        </div>
      <%}%>
      
        <div class="ca-new-btn">
      <% if(template_type == 'image') {%>
          <button type="button" class="ca-g-btn btn upload_image_file" > <?= _campaign("Upload Image") ?> </button> 
        
      <%} else {%>
        <%if(template_type == 'wechat'){%>
          <button type="button" class="ca-g-btn btn" template_id = -1 id="create_wechat_template" > <?= _campaign("Map Template") ?> </button>
          <button type="button" class="ca-g-btn btn" id="create_from_scratch" data-toggle="dropdown" aria-expanded="false"> <?= _campaign("New Template")?>
          </button>
        <% } if(template_type == 'mobile_push'){%>
          <button type="button" class="ca-g-btn btn"  id="create_mobile_push_template" > <?= _campaign("New Template") ?> </button>
        <%} else { %> 
          <button type="button" class="ca-g-btn ca-new-btn btn create_from_scratch" data-toggle="dropdown" aria-expanded="false">  <?= _campaign("New Template")?></button>
        <% } }%>
        </div>
      </div>
  
    
      <div class="ca-container-search">
        <div class='ca-template-option'>
          <a class='ca-all sel all_template'><?= _campaign('All') ?></a>
          <a class='ca-favourites favourite_template' ><?= _campaign("Favourites")?></a>
        </div>
        <div class='ca-search'>
          <div class='ca-search-container'>
            <i class='c-search-icon'></i>
            <input type='text' class='ca-search-text ca_search' placeholder=<?= _campaign('Search&nbsp;for&nbsp;template'); ?>  />
          </div>
        </div>
      </div>
    
      
      <div class="ca-container-body ca_container_body">
    
        <div class="ca_all_container_body">
        </div>
        <div class="ca_favourite_container_body">
        </div>
        <div class="ca_search_container_body" >
        </div>
    
      <%if(template_type != 'wechat'){%>
        <div class="ca_complete_msg ca-complete-msg" style="display:none"><?= _campaign("That is all we have")?></div>
      <%} else {%>
        <div class="ca_complete_msg ca-complete-msg" style="display:none"><?= _campaign("That is all we have")?></div>
      <% } %>

      <%if($('#template_type').val() != 'social'){%>
        <div style="position:relative; height:100px" >
          <div class="ca_loader" style="display:none"></div>
        </div>
      <% } %>
      </div>

      <div class="ca_image_preview_container_body ca-image-preview-container">
      </div>

    </div>

    <div class="ca_top_edit_container">
    </div>
    <div id="ca_lang_based_parent_container">
    </div>
    <div style="display: none">
      <form id="image_upload" name="image_upload">
        <input type="file" name="upload_image" id="upload_image"/>
      </form>
    </div>
</div>
</script>

<script id="template_tpl" type="text/template">
  <div>
    <div class="ca_preview_holder ca-preview-holder">
      <img src="<%=preview_url%>" alt="<?= _campaign("Preview is being generated...") ?>" />
      <div class='ca-preview-holder-footer'>
        <div class="ca-multi-language">
          <span class="linked-templates-icon"></span>
          <span class="linked-templates-count"><%=linked_templates%></span>
        </div>
      </div>
    </div>
    <div class="ca_favourite_icon ca-favourite-icon">
    <% if(is_favourite){%>
      <i class="icon-heart"></i>
    <%}else {%>
      <i class="icon-heart-empty" ></i>
    <%}%>
    </div>

  </div>
  <div>
    <div class="ca-template-name" title="<%=name %>"><%=name %></div>
    <% if(is_drag_drop){ %>
    
      <span class="ca-edm-icon" data-toggle="tooltip" data-placement="bottom" title="<?= _campaign("Template is Drag-Drop compatible") ?>">
        <i class="drag-drop-icon"></i>
      </span>
    
    <%}%>
  </div>
</script>
<script id="img_template_tpl" type="text/template">
  <div>
    <div class="ca_preview_holder ca-img-preview-holder" title="<%=name%>">
      <img src="<%=preview_url%>" alt="<?= _campaign("Preview is being generated...") ?>" />
    </div>
    <div class="ca_favourite_icon ca-favourite-icon">
    <% if(is_favourite){%>
      <i class="icon-heart"></i>
    <%}else {%>
      <i class="icon-heart-empty" ></i>
    <%}%>
    </div>
    <div>
    <div class="ca-template-name" title="<%=name %>"><%=name %></div>
  </div>
</script>

<script id="wechat_multi_tpl" type="text/template">
  <div class="wechatMultiTemplate" qXun-template-id="<%=model.content.qXunTemplateId%>" template-id="<%= model.template_id %>">
    <div class='templateName'><%=model.template_name%></div>
    <div class="singlePicContainer">
    <% _.each(model.singlePicData,function(value,key) { %>
      <div class="singlePic">
        <div class="title"><%=value.title%></div>
        <div class="imageContainer"><img src="<%=value.image%>"></div>
      </div>
    <% }); %>
    </div>
  </div>
  <div class="footer">
    <div class="favorite <%=((model.is_favourite)? 'active' : '')%>">&#9829;</div>
  </div>
  <div class="option_view">
    <div class="!btn-group btn-tablecell">
      <button type="button" id="edit_wechat_template"  template_id="<%=model.template_id%>" class="edit_template btn" style="width:97px"><?= _campaign("Edit"); ?></button>
    </div>
  </div>
</script>

<script id="wechat_single_tpl" type="text/template">
  <div class="wechatSingleTemplate <%=model.selected%>" qXun-template-id="<%=model.content.qXunTemplateId%>" template-id = "<%=model.template_id %>">
  <% if(model.catalogueView) { %>
    <div class="hover">
      <div class="checkIcon">&#x2714;</div>
    </div>
  <% } %>
    <div class="templateContainer">
      <div class="title"><%=model.title%></div>
      <div class="imageContainer">
        <img src="<%=model.image%>">
      </div>
      <div class="content hide"><%=model.content.content%></div>
      <% if(!model.catalogueView) { %>
      <div class="footer">
        <div class="favorite <%=((model.is_favourite)? 'active' : '')%>">&#9829;</div>
      </div>
      <% } %>
    </div>
    <div class="templateName"><%=model.template_name%></div>
    <% if(!model.catalogueView) { %>
    <div class="option_view">
      <div class="!btn-group btn-tablecell">
        <button type="button" id="edit_wechat_template"  template_id="<%= model.template_id %>" class="edit_template btn" style="width:97px">
          <?= _campaign('Edit')?>
        </button>
      </div>
    </div>
    <% } %>
  </div>
</script>

<script id="wechat_template_tpl" type="text/template">
  <div>
    <div class="ca_preview_holder ca-img-preview-holder" style="font-size: 10px;"></div>
    
  <% if(model.name) { %>
    <div class="ca-template-name" style="width:97px" title = <%-model.name%> >
      <%-model.name%>
    </div>
  <% } else { %>
    //It should not come here
    <div class="ca-template-name"  style="width:97px" title = "Random Name" >"<?= _campaign('Random Name')?>"</div>
  <% } %>
    <div class="option_view">
      <div class="btn-group btn-tablecell">
      
        <button type="button" id="edit_wechat_template"  template_id="<%- model.template_id %>" class="<%- edit_options.edit.className %> btn" style="width:97px">
        <%- edit_options.edit.label%>
        </button>
        <button type="button" class="btn dropdown-toggle" data-toggle="dropdown" style="width:31px">
          <span class="caret"></span>
          <span class="sr-only"></span>
        </button>
        <ul class="dropdown-menu" role="menu">
          <li>
            <a class="<%-edit_options.delete_t.className%>"><%-edit_options.delete_t.label%></a>
          </li>
        </ul>
      </div>
    </div>
    <div class="modal fade template-preview-modal" id="edit_template_preview_modal" style="width:22%; left: 59%">
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
    <div  class="modal hide fade confirm_delete_modal">
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

<script id="ca_template_tpl" type="text/template">
  <div class="template_view">
  </div>
  <div class="option_view">
    <div class="btn-group btn-tablecell">
      <button type="button" class="<%- edit_options.edit.className %> btn" style="width:85px">
        <%- edit_options.edit.label%>
      </button>
      <button type="button" class="btn dropdown-toggle" data-toggle="dropdown">
        <span class="caret"></span><span class="sr-only"></span>
      </button>
      <ul class="dropdown-menu" role="menu">
        <li><a class="<%-edit_options.duplicate.className%>"><%-edit_options.duplicate.label%></a></li> 
        <li><a class="<%-edit_options.delete_t.className%>"><%-edit_options.delete_t.label%></a></li>
      </ul>
    </div>
  </div>
  <div  class="modal hide fade confirm_delete_modal">
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

  <div>
    <div class="modal fade template-preview-modal" id="template_preview_modal" style="width: 85%; left:25%" data-keyboard="false" data-backdrop="static">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-body">
            <div class="preview_container">
              
            </div>
          </div>
          <div class="modal-footer">
            <% if(model.is_drag_drop){ %>
              
                <span class="ca-edm-icon ca-fleft" data-toggle="tooltip" data-placement="bottom" title="<?= _campaign("Template is Drag-Drop compatible") ?>">
                  <i class="drag-drop-icon"></i>
                </span>
                <span class="ca-drag-drop-msg"><?= _campaign("Drag-drop compatible") ?></span>
              <%}%>
            <div class="edit_button_container dab">
              
              
              <button type="button" class="<%-edit_options.edit.className%> btn ca-g-btn" data-dismiss="modal"><%- edit_options.edit.label%></button>
              <button class="<%-edit_options.duplicate.className%> btn btn-default" data-dismiss="modal" ><%-edit_options.duplicate.label%></button> 
              <button class="<%-edit_options.delete_t.className%> btn btn-default" data-dismiss="modal" ><%-edit_options.delete_t.label%></button>
            </div>
          </div>
        
        </div>
      </div>
    </div>
  </div>
</script>

<script id="ca_wechat_template_tpl" type="text/template">
  <div class = "c-wechat-main">
    <div class="edit_template_loader"></div>
    <div class="ca-fleft lang_enabled_hide"></div>

    <div class="modal fade template-preview-modal" id="edit_template_preview_modal" style="width:22%; left: 59%">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-body">
            <div class="mobile-preview-icon">
              <div class="preview_container preview_container_margin">



              </div>
            </div>
          </div>
          <div class="modal-footer">  
            <button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Cancel")?></button>
          </div>
        </div>
      </div>
    </div>

    <div class="ca-fright lang_enabled_hide">
      <div class="ca-dibm">
        <button class="ca-g-btn ca-save-btn save_new_template"> <?= _campaign("Save Template") ?> </button>
      </div>
      <div class="ca-dibm">
        <div class="btn-group">
          <button type="button" class="ca-grey-btn see_preview wechat_preview_margin" ><?= _campaign('See Preview') ?>
          </button>
        </div>
      </div>
      <div class="ca-dibm">
        <button class="ca-grey-btn back_to_view"> <?= _campaign("Cancel") ?> </button> 
      </div>
    </div>

    <div style="clear:both"></div>
    <div>
      <div class="c-sel-temp c-float"><?= _campaign('Select Template')?></div>
      <div class="ca-dibm">
        <div class="btn-group">
          <select class = "c-selected-box">
            <option selected="selected" disabled>
              <?= _campaign("Template List") ?>
            </option>
          <%  _.each(template,function(template,key){%>
            <option id="<%-template['Template_id']%>" value="<%-template['Title']%>" class="c-select-template">
              <%-template['Title'] +'-'+template['TemplateId']%>
            </option>
          <% }); %>
          </select>
        </div>
      </div>
    </div>
    
    <div style="clear:both"></div>
    <div class = "c-show-template"></div>
  </div>
</script>

<script id="details-tpl" type="text/template">
  <% var count = 0 
    var isFrst = 0
  %>
  <% count = keylist.length %>
  <div class = 'c-margin-left'>
  <% _.each(keylist,function(key,val){ %>
    <% if(key.key=='first'){%>
      <span style="clear:both; float:left; margin: 18px 0px 0px 0;"><%='Header*'%></span>
    <% }else if(key.key=='remark'){ %>
      <span style="clear:both; float:left; margin: 18px 0px 0px 0;"><%='Footer*'%></span>
    <% }else{ %>
    <span style="clear:both; float:left; margin: 18px 0px 0px 0;"><%-key.key%><%='*'%></span>
    <%}%>
  <% }); %>
  </div>
  <div></div>
  <div class = "c-show-template-details">
    <div id="wechat_scope_selector">
      <span style="padding-right: 48px;"><?= _campaign("Select Scope:") ?></span>
      <form style="display: inline-flex;">
        <label style="padding-right: 21px;">
          <input type="radio" id="wechat_loyalty" class="wechat_scope" value="wechat_loyalty" <%- (scope == 'wechat_loyalty')? 'checked': '' %> ><?= _campaign("LOYALTY") ?>
        </label>
        <label style="padding-right: 21px;">
          <input type="radio" id="wechat_dvs" class="wechat_scope" value="wechat_dvs" <%- (scope == 'wechat_dvs')? 'checked': '' %> ><?= _campaign("DVS") ?>
        </label>
        <label style="padding-right: 21px;">
          <input type="radio" id="wechat_outbound" class="wechat_scope" value="wechat_outbound" <%- (scope == 'wechat_outbound')? 'checked': '' %> ><?= _campaign("OUTBOUND") ?>
        </label>
      </form>
    </div>
    <div class="c-margin-bottom" style="white-space: nowrap;" title="<%- temp['Title'] +'-'+ temp['TemplateId'] %>">
      <%- temp['Title'] +'-'+ temp['TemplateId'] %>
    </div>
    <% _.each(keylist,function(key,val){%>

      <%if(key.key=='first'){%>

      <div>
        <input type="text" class="c-input-width c-first-data c-input-tag-box c-tag-<%-key.key%>" name="FirstName" wechat-tag-data="<%-key.key%>" 
          placeholder="<?= _campaign('Enter First Data Here');?>"  value="<%-key.val%>">
      </div>  

      <%} else if(key.key=='remark'){%>

      <div>
        <input type="text" class="c-input-width c-remarks-data c-input-tag-box c-tag-<%-key.key%>" name="RemarkName" wechat-tag-data="<%-key.key%>" 
          placeholder="<?= _campaign('Enter Remark Data Here');?>" value="<%-key.val%>">
      </div>

      <%}else{%>

      <div>
        <select style = "width:100%;" class="c-tag-<%-key.key%> c-selected-tag-box" wechat-tag-data="<%-key.key%>">
          <!-- <option selected="selected" disabled>
            <?= _campaign('Capillary Tags');?>
          </option> -->
        <% _.each(capTags,function(key1,val1){%>
        <% if(key.val==capTags[val1]['value']){ %>  
          <option selected="selected" id="<%-capTags[val1]['value']%>" value="<%-capTags[val1]['value']%>">
            <%-capTags[val1]['label']%>
          </option>
        <% }else{ %>
          <option id="<%-capTags[val1]['value']%>" value="<%-capTags[val1]['value']%>"><%-capTags[val1]['label']%></option>
        <% } %>
        <% }); %>
        </select>
      </div>

      <% } %>
    <% }); %>
  </div>
  <div class = "c-show-url">
    <span style="clear:both; float:left; margin: 18px 0px 0px 22px;"><%='Link to details page'%></span>
    <input type="text" class="c-input-width c-url-data-style c-input-tag-box c-tag-url" name="UrlName" wechat-tag-data="url" placeholder="http://" value="<%- url %>" style="width: 500px;margin: 14px 0 0 20px;" />
  </div>

  <div class = "c-show-url wechatcheck">
    <span style="clear:both; float:left; margin: 8px 0px 0px 22px;"><%='Is This Internal Url'%></span>
    <input type="checkbox" style="margin-left: 23px;margin-top: 13px;" <%- (isInternalUrl == 1)? 'checked': '' %>/>
  </div>

</script>

<script id="ca_edit_template_tpl" type="text/template">
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
          <% console.log("scope",scope); if(scope != "EBILL") {%>
            <li class="edit_as_html"><a ><?= _campaign('Edit as HTML')?></a></li>
            <li class="image_gallery"><a> <?= _campaign('Insert Image')?></a></li>
          <% } %>
            <li class="remove_language_list"><a ><?= _campaign('Remove Language')?></a></li>
            <li class="preview"><a ><?= _campaign('Preview')?></a></li>
            <li class="change_to_classic_editor hide"><a> <?= _campaign('Change to Classic Editor')?></a></li>
                        <li class="change_to_inline_editor hide"><a> <?= _campaign('Change to Inline Editor')?></a></li>
          </ul>
        </div>
      </div>
    </div>
    <div class="ca-fleft lang_enabled_hide">
      <div class="ca-dibm">
        <button class="ca-grey-btn back_to_view"> < <?= _campaign("Back") ?> </button> 
      </div>

      <div class="ca-dibm edit-name-container" >
        <div class="ca_edit_favourite_icon ca-cursor-pointer">
          <% if(model.is_favourite){%>
          <i class="icon-heart"></i>
          <%}else {%>
          <i class="icon-heart-empty"></i>
          <%}%>
        </div>
        <div id="edit_template_name" class="ca-edit-template-name"> <%-model.name%></div>
        <div id="edit_name" class="ca-cursor-pointer"><i class="icon-pencil"></i></div>
      </div>
    </div>
    <div class="ca-fright lang_enabled_hide">
      <div class="ca-dibm"><button class="ca-g-btn ca-save-btn save_new_template"> <?= _campaign("Save") ?> </button></div>
      <div class="ca-dibm">
        <div class="btn-group">
          <button type="button" class="ca-grey-btn ca-option-btn btn dropdown-toggle" data-toggle="dropdown"><?= _campaign('Options') ?>
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
    <div style="clear:both"></div>
  </div>
  <div class="ca_edit_template_container ca-edit-template-container">

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
            
            <input type="text" id="rename_template" value="<%- model.name %>" />
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
    <div class="modal fade" id="new_name_modal" class="lang_enabled_hide">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <span class="ca-modal-header"><?= _campaign("New Template")?></span>
            <button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close")?>>
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p class="ca-modal-body-header"> <?= _campaign("Name template")?> </p>
              <input type="text" id="new_template_name" value="<%- model.name%>" />
            <p class="ca-modal-body-header"> <?= _campaign("Scope")?> </p>
            <label>
            
            <select name="new_template_scope" id="new_template_scope">
              <%_.each(scopes_available,function(value,key) { %>
                <option value="<%=value%>" <%if(scope == value){%>selected = true<%}%> > <%=key%> </option>   
              <%});%>
            </select>
            </label>
          </div>
          <div class="modal-footer">  
            <button type="button" class="btn ca-g-btn" id="save_name_scope"> <?= _campaign("Save")?> </button>
            <button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Cancel")?> </button>
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
            <!--<div class="preview_title"></div>
            <div class="preview_msg"></div>-->
          </div>
        </div>
        <div class="modal-footer">  
          <button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Cancel")?> </button>
        </div>
      </div>
    </div>
  </div>

  <div  class="modal hide fade confirm_edit_modal">
    <div class="modal-header">
      <div class="ca-modal-header"><?= _campaign("Edit as Html")?></div>
      <button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close")?>>
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
  <div style="display:none;"><iframe id="iedit_template__template_holder"> </iframe> </div>
<%if(supported_tags.length) { %>
<div class="ca-edit-left-panel">
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
        html_val += '<li class="tag-list"><span><a class="insert_tag" tag-data='+ tag.val +' >'+tag.name+'</a></span>';
      }
      html_val += '</li>'
      return html_val;
    } 
    _.each(supported_tags, function(tag) {
      print(template_function(tag));
    })%>
    </ul>
  </div>
</div><div class="ca-edit-right-panel">
<% }else {%>  
     </div><div class="ca-edit-right-panel" style= "width: 100%">
<% } %>
</div>
<div id="iframe_html_container" class="hide">
  <iframe id="template_holder">
  </iframe>
</div>
</script>
<script id="inline_ck_editor_content_tpl" type="text/template">
  <div class="source" style="position:fixed;top:5px;right:10px;width:25px;height:25px;background-image: url('/images/code-128.png'); background-size: 100% 100%;z-index:10000;cursor:pointer;" title="<?= _campaign('source') ?>">
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
<script id="text_editor_tpl" type="text/template">
  <div class="ca-full" ><textarea class="text_editor_textarea ca-full"><%=html_content%></textarea></div>
</script>
<script id="create_template_tpl" type="text/template">
  <div class="ca_preview_holder ca-preview-holder ca-dib">
  <img src="<%=preview_url%>" alt="<?= _campaign("Preview is being generated...")?>" />
</div>
</script>
<script id="we_chat_template_view" type="text/template">
  <div class="ca_preview_holder ca-preview-holder ca-dib">
  <img src="<%=preview_url%>" alt="<?= _campaign("Preview is being generated...")?>" />
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

    <div id="lang_tab_preview_parent">
      <ul class='lang_tab_preview lang_list_tabs' style="list-style-type:none">
        <li id='language_button__<%=base_language_id%>' class='language_based_preview_tab tab_selected'><%=languages[base_language_id] %></li><% _.each(template_lang_ids,function(lang_id){ %>
          <li id='language_button__<%=lang_id%>' class='language_based_preview_tab'><%=languages[lang_id]%></li><% });%>

      </ul>
    </div>  
    <div class="language_content_parent" >
      <div id='language_content__<%=base_language_id%>' class='language_based_preview_content' >
      </div>
      <% _.each(template_lang_ids,function(lang_id){ %><div id='language_content__<%=lang_id%>' class='language_based_preview_content'></div><% });%>
    </div>  
  </div>
</script>
<script id="email_preview_tpl" type="text/template">
  <div>
    <div style="position:relative;"><div style= "min-height:390px" class="ca_loader"></div>
  </div>
  
  <div class="ca-email-preview-button-bar">
    <div class="btn-group">
      <button type="button" class="btn_email_desktop btn btn-padding btn-inverse active"><?= _campaign("Desktop")?></button>
      <button type="button" class="btn_email_tablet btn btn-padding btn-default"><?= _campaign("Tablet")?></button>
      <button type="button" class="btn_email_mobile btn btn-padding btn-default"><?= _campaign("Mobile")?></button>
    </div>
  </div>
  <button type="button" class="close lang_enabled_hide" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
    <span aria-hidden="true">&times;</span>
  </button>
</div>
<div class="">
  <div class="email_desktop email-desktop flexcroll">
    <iframe id="template_email_iframe_preview" class="email-iframe-preview flexcroll"></iframe>
  </div>
  <div class="email_mobile email-mobile flexcroll hide">
    <div class="ca-mobile">
      <iframe id="template_preview_iframe_mobile_portrait" class="flexcroll"></iframe>
    </div>
    
    
  </div>
  <div class="email_tablet email-mobile flexcroll hide">
    
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
      <% if(option == 'delete') {%>
        <button class="btn btn-default delete_img"><?= _campaign("Delete")?></button>
      <%}else {%> 
      <button class="btn ca-g-btn insert_img" data-dismiss="modal"><?= _campaign("Insert")?></button>
      <%}%>
    </div>
  </div>
</script>
<script id="single_image_broadcast_create_tpl" type="text/template">
  <div class="singleImageTemplatecontainer">
    <div>
    <!-- ashish -->
    <div class="title">
    <% if(typeof data == 'undefined') { %>
    <?= _campaign("New Single image Broadcast Template"); ?>
    <% } else { %>
    <?= _campaign("Edit Single image Broadcast Template"); ?>
    <% } %>
    </div>
    <div class="action">
      <% if(typeof data == 'undefined') { %>
      <button type="button" id="save_single_image_tpl" class="ca-g-btn btn"><?= _campaign('Save Template')?></button>
      <% } else { %>
      <button type="button" id="update_single_image_tpl" data-template-id="<%=data.template_id%>" class="ca-g-btn btn"><?= _campaign('Update Template')?></button>
      <% } %>
      <button type="button" id="preview_single_image_tpl" class="btn"><?= _campaign('Preview Template')?></button>
      <button type="button" id="cancel_single_image_tpl" class="btn"><?= _campaign('Cancel')?></button>
    </div>
  </div>
  <div class="templateForm">
    <div id="template-name"><?= _campaign('Template Name')?> <input type="text" name="template_name" id="template_name" maxlength="25" value = "<%=(typeof data == 'object')? data.template_name:''%>" /></div>
    <div class="shellContainer">
      <div class="shellLeft">
        <div style="margin-top:15px;"><?= _campaign('Title')?></div>
        <div style="margin-top:25px;"><?= _campaign('Cover Image')?></div>
        <div style="margin-top:195px;"><?= _campaign('Summary')?></div>
      </div>
      <div class="shell">
        <div class="shellBorder">
          <div>
            <input type="text" maxlength="64" id="template_title" placeholder="<?= _campaign('Enter title here'); ?>" value = "<%=(typeof data == 'object')? data.title:''%>"/>
          </div>
          <% var imgSrc = ((typeof data == 'object')? data.image:'')%>
          <div class="uploadPic upload_image_file">
            <div class="<%= (imgSrc)?'':'hide'%>">
              <img src = "<%=imgSrc%>" />
            </div>
            <div  class="<%= (imgSrc)?'hide':''%>">
              <i class="fa fa-cloud-upload" aria-hidden="true"></i> <?= _campaign('Upload Pic')?>
            </div>
          </div>
          <div class="summary">
            <textarea type="text" rows="3" maxlength="120" placeholder="Enter summary here" id="template_summary"><%=(typeof data == 'object')? data.summary:''%></textarea>
          </div>
        </div>
      </div>
        <div class="shellright">
          <div id="template_title_count" style="margin-top:15px;"><span>0</span>/64 <?= _campaign('characters')?></div>
          <div style="margin-top:25px;"><?= _campaign('Recommended')?><br/><?= _campaign('Resolution')?> : 360 x 200 px<br/><?= _campaign('size')?>: 64kb</div>
          <div id="template_summary_count" style="margin-top:155px;"><span>0</span>/120 <?= _campaign('characters')?></div>
        </div>
         <div style="display: none;">
          <form id="image_upload" name="image_upload">
            <input type="file" name="upload_image" id="upload_image">
          </form>
        </div>
      </div>
      <div id="template-link">
        <div style="display: inline-block;margin-left: 70px;"><?= _campaign('Content')?></div>
        <textarea id="wechat_content" name="wechat_content" style="float: none;margin: 0 90px;display:none;">
        </textarea>
      </div>
    </div>
  </div>
</script>
<script id="single_image_preview_template" type="text/template">
  <div class="modal fade in" id="single_image_preview_modal">
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
        <iframe src = "<%='data:text/html;charset=utf-8,' + (model.content)%>"></iframe>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <a data-dismiss="modal" class="btn"><?= _campaign('Close') ?></a>
  </div>
</div>
</script>
<script id="multi_image_broadcast_create_tpl" type="text/template">
  <div class="multiImageTemplatecontainer">
    <div>
    <!-- ashish -->
      <div class="title">
        <% if(typeof data == 'undefined') { %>
         <?= _campaign('New Multi image Broadcast Template')?>
        <% } else { %>
          <?= _campaign('Edit Multi image Broadcast Template')?>
        <% } %>
      </div>
      <div class="action">
        <% if(typeof data == 'undefined') { %>
        <button type="button" id="save_multi_image_tpl" class="ca-g-btn btn"><?= _campaign('Save Template')?></button>
        <% } else { %>
        <button type="button" id="update_multi_image_tpl" data-template-id="<%=data.template_id%>" class="ca-g-btn btn"><?= _campaign('Update Template')?></button>
        <% } %>
        <button type="button" id="preview_multi_image_tpl" class="btn"><?= _campaign('Preview Template')?></button>
        <button type="button" id="cancel_multi_image_tpl" class="btn"><?= _campaign('Cancel')?></button>
      </div>
    </div>
    <div class="templateForm">
    <div id="template-name"><?= _campaign('Template Name')?><input type="text" name="template_name" id="template_name" value="<%=(typeof data == 'object')? data.template_name:''%>"></div>
      <div class="shellContainer">
        <div class="shellLeft">
          <div class="hide" style="margin-top:15px;"><?= _campaign('Title')?></div>
          <div style="margin-top:15px;"><?= _campaign('Cover Image')?></div>
        </div>
        <div class="shell">
          <div class="shellBorder wechatMultiTemplate">
            <div class="hide">
              <input type="text" id="template_title" placeholder="<?= _campaign('Enter title here')?>" />
            </div>
            <div class="singlePicContainer">
            <% if(typeof data == 'object') { var index = 1; _.each(data.singlePicData,function(value,key) {
            %>
              <div class="singlePic openSingleImageCatalogue" template="<%=index++%>" template-id="<%=value.template_id%>" qxun-template-id="<%=value.qXunTemplateId%>">
                <div class="title"><%=value.title%></div>
                <div class="imageContainer"><img src="<%=value.image%>"/></div>
                <div class="content hide"><%=value.content%></div>
                <div class="remove"><div>X</div></div>
              </div>
              <% }); } else {  %>
              <div class="singlePic openSingleImageCatalogue" template="1">
                <div class="title"><?= _campaign('Title for this SIBM goes here')?></div>
                <div class="imageContainer"><img src=""></div>
                <div class="content hide"></div>
                <div class="remove"><div>X</div></div>
              </div>
              <div class="singlePic openSingleImageCatalogue" template="2">
                <div class="title"><?= _campaign('Title for this SIBM goes here')?></div>
                <div class="imageContainer"><img src=""></div>
                <div class="content hide"></div>
                <div class="remove"><div>X</div></div>
              </div>
              <div class="singlePic openSingleImageCatalogue" template="3">
                <div class="title"><?= _campaign('Title for this SIBM goes here')?></div>
                <div class="imageContainer"><img src=""></div>
                <div class="content hide"></div>
                <div class="remove"><div>X</div></div>
              </div>
              <% } %>
            </div>
            <div id="add_single_placeholder">
              <i class="fa fa-plus-square">+</i>
              <span><?= _campaign('Add one more placeholder')?></span>
            </div>
          </div>
        </div>
        <div class="shellright">
          <div class="hide" style="margin-top:15px;">0/64 <?= _campaign('characters')?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade in hide" id="single_image_catalogue">
    <div class="modal-header">
      <div style="display: inline-block;">
        <h3 id="camp_msg_preview_header" style="margin: auto;"><?= _campaign('Add Templates')?></h3>
      </div>
      <div style="display: inline-block;float: right;">
        <button type="button" id="apply-template-selection" template-anchor="" class="ca-g-btn btn" style="margin: 0 10px;"><?= _campaign('Continue')?></button>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><?= _campaign('close')?> ×</button>
      </div>
      <div><?= _campaign('select 1 template to proceed, you can change the order inplace')?></div>
    </div>
    <div class="modal-body">
    </div>
    <div class="modal-footer"></div>
  </div>
</script>
<script id="multi_image_preview_template" type="text/template">
  <div class="modal fade in" id="multi_image_preview_modal">
  <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="camp_msg_preview_header"><?= _campaign('Preview') ?></h3>
  </div>
  <div class="modal-body">
    <div class='auth-bananaphone'>
      <div class="wechatMultiTemplate">
        <div class="singlePicContainer">
          <% var index = 1;
              _.each(data,function(value,key) { %>
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

<script id="android-notif-preview" type="text/template">
  <div class="android-notif-header">
    <div class="android-notif-app-icon">
      <i class="icon-star"></i>
    </div>
    <div class="android-notif-title-body">
      <div class="android-notif-title">
        <h1><bold><%= rc.android_data.title %></bold></h1>
      </div>
      <div class="android-notif-body <% (rc.android_data.notif_img) ? 'body-truncate' : '' %>"><%= rc.android_data.content %></div>
    </div>
  </div>
  <hr style="border-color: lightgrey">
  <% cta_length = rc.android_data.cta_sec.length %>
  <% if(rc.android_data.notif_img) { %>
  <div class="android-notif-img">
    <img src="<%= rc.android_data.notif_img %>" alt="image" height="42" width="42">
    <% if(cta_length > 0) { 
        if(cta_length == 1) {
    %>
        <div class="android-notif-action bottom-adjust" style="padding-left:0px">
            <div style="width: 100%; text-align: center; font-size: 13.5px;" class="single-button-truncate"><%= rc.android_data.cta_sec[0] %></div>
        </div>
    <% } else if(cta_length == 2) { %>
        <div class="android-notif-action bottom-adjust">
          <div style="width: 100%; display: inline-flex;" class="">
            <div class="sec-cta-notif-preview double-button-truncate"><%= rc.android_data.cta_sec[0] %></div>
            <span style="width: 1px;height: 31px;background-color: lightgrey;margin: 0 13.5px 0 7.5px;"></span>
            <div class="sec-cta-notif-preview double-button-truncate"><%= rc.android_data.cta_sec[1] %></div>
          </div>
        </div>
    </div>
    <% }}} %>

  <% if(!rc.android_data.notif_img) { %>
    <%if(cta_length == 1){%>
      <div class="android-notif-action android-notif-action-noimg" style="padding-left:0px">
        <div style="width: 100%; text-align: center; font-size: 13.5px;" class="single-button-truncate"><%= rc.android_data.cta_sec[0] %></div>
      </div>
    <% }else if(cta_length > 1){ %>
      <div class="android-notif-action android-notif-action-noimg">
        <div style="width: 100%;display: inline-flex;" class="">
          <div class="sec-cta-notif-preview double-button-truncate"><%= rc.android_data.cta_sec[0] %></div>
          <span style="width: 1px;height: 31px;background-color: lightgrey;margin: 0 13.5px 0 7.5px;"></span>
          <div class="sec-cta-notif-preview double-button-truncate"><%= rc.android_data.cta_sec[1] %></div>
        </div>
      </div>
    <% } %>
  <% } %>
</script>


<script id="ios-notif-preview" type="text/template">
  <div class="ios-notif-header">
    <div class="ios-notif-app-icon">
      <i class="icon-star"></i>
    </div>
    <div class="ios-notif-appname">
      <bold><?= _campaign("APP NAME")?></bold>
    </div>
    <div style="float: right;">x</div>
  </div>
  <% if(rc.ios_data.notif_img) { %>
  <div id="ios-notif-img">
    <img src="<%= rc.ios_data.notif_img %>" alt="image" height="100%" width="100%">
  </div>
  <% } %>
  <div class="ios-notif-title-body">
    <div class="ios-notif-title">
      <h4>
        <bold><%= rc.ios_data.title %></bold>
      </h4>
    </div>
    <div class="ios-notif-body <%= (rc.ios_data.notif_img) ? 'ios-body-truncate' : '' %>"><%= rc.ios_data.content %></div>
  </div>
  <% cta_length = rc.ios_data.cta_sec.length %>
  <% if(cta_length > 0) { %>
  <div class="ios-notif-action">
    <% if(cta_length == 1) { %>
      <div class="ios-notif-cation-btn ios-body-truncate" style="border-radius: 7px;"><%= rc.ios_data.cta_sec[0] %></div>
    <% } else { %>
      <div class="ios-notif-cation-btn ios-body-truncate" style="border-top-right-radius: 7px;border-top-left-radius: 7px;"><%= rc.ios_data.cta_sec[0] %></div>
      <div class="ios-notif-cation-btn ios-body-truncate" style="border-bottom-right-radius: 7px;border-bottom-left-radius: 7px; margin-top: 2px;"><%= rc.ios_data.cta_sec[1] %></div>
    <% } %>
  </div>
  <% } %>
</script>


<script id="ca_mobile_push_template_tpl" type="text/template">
<div class='ca-mobile-push-container'>
    <div class="wait_initial_form"></div>
  <div class="">
    <div class="edit_template_loader"></div>
    <div class="modal fade template-preview-modal" id="edit_template_preview_modal" >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-body">
        <div class="c-status-option preview_type">
                        <a class="c-status-live preview-mobile-push sel" id="android-preview"><?= _campaign("Android")?></a><a class="c-status-upcoming preview-mobile-push" id="ios-preview"><?= _campaign("IOS")?></a>
                    </div>
          <div class="mobile-preview-icon-android">
            <div class="preview_container preview_container_margin_mobilepush">
            </div>
          </div>
          <div class="mobile-preview-icon-ios">
            <div class="preview_container_ios preview_container_margin_mobilepush_ios">
            </div>
          </div>
        </div>
        <div class="modal-footer">  
          <button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Cancel")?> </button>
        </div>
      </div>
    </div>
    </div>

    <div class="ca-fright">
      <div class="ca-dibm"><button class="ca-g-btn ca-save-btn save_new_template"> <?= _campaign("Save")?> </button></div>
      <div class="ca-dibm">
        <div class="btn-group">
          <button type="button" class="ca-grey-btn see_preview ca-mobile-push-preview"><?= _campaign("Preview")?></button>
        </div>
      </div>
      <div class="ca-dibm">
        <button class="ca-grey-btn back_to_view"> <?= _campaign("Cancel")?> </button> 
      </div>
    </div>
    <div class="c-sel-temp ">
      <div class="display-headtitle"><?= _campaign("Template Name")?><span class="red-error">*</span>     </div>
    <input type="hidden" name="ca-mobile-push-id" id="ca-mobile-push-id" value="<%= template.template_id %>">
         <input type="text" name="ca-mobile-push-name" id="ca-mobile-push-name" class="ca-mobile-push-text" value="<%= template.name %>">
            
        <ul class="nav nav-tabs mobile-push-tabs">

      <li id="mob_android"  <% if(tab_value == "android"){ %>class="active" <%}%> ><a class="display-headtitle" id="android"><?= _campaign("Android")?></a></li>
      <li id="mob_ios"  <% if(tab_value == "ios"){%>class="active" <%}%> ><a class="display-headtitle" id="ios"><?= _campaign("IOS")?></a></li>
      </ul>
      <div id="mob_android_container"></div>
      <div id="mob_ios_container" style="display:none"></div>
    </div>
    <div style="clear:both"></div>
   
</div>  
  <div  class="modal hide fade mobilepush_confirm_save_android">
    <div class="modal-header">
      <div class="ca-modal-header"><?= _campaign("Warning") ?></div>
      <button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="modal-body">
      <?= _campaign("You have not created IOS template. Do you want to create IOS template") ?>
    </div>
    <div class="modal-footer">
      <button type="button" data-dismiss="modal" class="btn ca-g-btn create_ios"><?= _campaign("Create IOS")?></button>
      <button type="button" data-dismiss="modal" class="btn save_android"><?= _campaign("Save Android ?")?></button>
      <button type="button" data-dismiss="modal" class="btn"><?= _campaign("Cancel")?></button>
    </div>
  </div>
    <div  class="modal hide fade mobilepush_confirm_save_ios">
    <div class="modal-header">
      <div class="ca-modal-header"><?= _campaign("Warning") ?></div>
      <button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="modal-body">
      <?= _campaign("You have not created Android template. Do you want to create Android template ?") ?>
    </div>
    <div class="modal-footer">
      <button type="button" data-dismiss="modal" class="btn ca-g-btn create_android"><?= _campaign("Create Android")?></button>
      <button type="button" data-dismiss="modal" class="btn save_android"><?= _campaign("Save IOS")?></button>
      <button type="button" data-dismiss="modal" class="btn"><?= _campaign("Cancel")?></button>
    </div>
  </div>

  <div  class="modal hide fade secondary_CTA_IOS_save">
    <div class="modal-header">
      <div class="ca-modal-header"></div>
      <button type="button" class="close" data-dismiss="modal" aria-label=<?= _campaign("Close") ?>>
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="modal-body">
      <?= _campaign("Continue without adding secondary CTA?") ?>
    </div>
    <div class="modal-footer">
      <button type="button" data-dismiss="modal" class="btn"><?= _campaign("Yes")?></button>
      <button type="button" data-dismiss="modal" class="btn"><?= _campaign("Cancel")?></button>
    </div>
  </div>
  
</script>

<script id="secondary_tpl" type="text/template">
  <div class="secondary-CTA" id="secondary-CTA<%= rc.no %>" ca-mobile-push-key =<%= rc.no %> >
    <div style="float:right;margin: 10px;">
    <a class="ca-mobile-push-reset-div" ca-mobile-no = "<%= rc.no %>"><i class="icon-trash ca-mobile-icon"></i></a> </div>
     <div class="display-headtitle"><?= _campaign("Label Name")?></div>
      <div class="ca-mobile-push-reset-container">
      <% 
      if(!_.isUndefined(rc.mod)){
       var secLabel=rc.mod.html_content.ANDROID.expandableDetails.ctas[rc.no].actionText
      }    
      %>
       
             <input type="text" name="ca-mobile-push-secondary-label" id="ca-mobile-push-label<%= rc.no %>" class="ca-mobile-push-text ca-mobile-push-set-text ca-mobile-push-secondary-label" ca-mobile-push-label<%= rc.no %>-android="<%= secLabel%>" value="<%= secLabel%>">
             </div>
             <div>
               <div class="span5"> 
               <% 
               if(!_.isUndefined(rc.mod)){
                if(rc.mod.html_content.ANDROID.expandableDetails.ctas[rc.no].type=="DEEP_LINK"){

                 var secLink = rc.mod.html_content.ANDROID.expandableDetails.ctas[rc.no].type;
                }
                if(rc.mod.html_content.ANDROID.expandableDetails.ctas[rc.no].type=="EXTERNAL_URL"){
                 var secLink = rc.mod.html_content.ANDROID.expandableDetails.ctas[rc.no].type;
                }
               }
             %> 
               <input type="radio" name="secondary-link<%= rc.no %>" class = "secondary-link"  value="deep-link" <% if( secLink == 'DEEP_LINK'){ %> checked="checked" <% } %> /> <?= _campaign("Deep Link")?>
               </div>
               <div>        
               <input type="radio"  name="secondary-link<%= rc.no %>" class = "secondary-link" value="external-link" <% if( secLink == 'EXTERNAL_URL'){ %> checked="checked" <% } %> /> <?= _campaign("External Link")?>
               </div>
             </div>
              <% if(!_.isUndefined(rc.mod)){
                if(rc.mod.html_content.ANDROID.expandableDetails.ctas[rc.no].type=="DEEP_LINK"){
                 var secDeepActionLink = rc.mod.html_content.ANDROID.expandableDetails.ctas[rc.no].actionLink;
                }
               if(rc.mod.html_content.ANDROID.expandableDetails.ctas[rc.no].type=="EXTERNAL_URL"){
                 var secExternalActionLink = rc.mod.html_content.ANDROID.expandableDetails.ctas[rc.no].actionLink;
                }
               
              if(secDeepActionLink)
                var actionLink =  secDeepActionLink;
              if(secExternalActionLink)
                var  actionLink = secExternalActionLink; 
              } 

             %> 

              <div class="ca-mobile-push-reset-container"> 
             <input type="text" name="" id="ca-mobile-push-secondary<%= rc.no %>" class="ca-mobile-push-text ca-mobile-push-text-link ca-mobile-push-secondary" ca-mobile-push-secondary<%= rc.no %>-deep-link-android='<%= secDeepActionLink%>' ca-mobile-push-secondary<%= rc.no %>-external-link-android='<%= secExternalActionLink%>' value='<%= actionLink%>'>
             </div>               
                    

  </div>

</script>

<script id="mobile_push_template_tpl" type="text/template">
  <div>
    <div class="ca_preview_holder_mobile_push" style="font-size: 10px;">

      <%if(model.name) {%>
        <div class="ca-template-name-mobile" title = <%-model.name%> ><span class="ca-template-name-mobile-text"><%-model.name %></span>
        <span class="favorite" style="padding: 0px 12px">
        <% if(model.is_favourite){%>
          <i class="icon-heart"></i>
        <%}else {%>
          <i class="icon-heart-empty" ></i>
        <% } %>
        </span></div>
      <% }else { %>
      //It should not come here
      <div class="ca-template-name" title = "Random Name" >"<?= _campaign('Random Name')?>"</div>
      <%}%>

      <div class="ca_preview_mobile">  
        <div class="ca_preview_holder_mobile ca-img-preview-holder" id="forandroid">
      
      <% if(!_.isUndefined(model.html_content.ANDROID)){ %>
          <div class="mobile_push_title">
            <%= model.html_content.ANDROID.title %>
          </div>
          <div class="mobile_push_msg">
            <%= model.html_content.ANDROID.message %>
          </div>
      <% }else { %>
          <span class="no-push-available"><?= _campaign("No Android Push")?></span>
      <% } %> 
          <div class="mobile-icon"><img src="/images/android-logo.png" alt="<?= _campaign("android") ?>"><i class="icon-android"></i> </div>
        </div>

        <div class="ca_preview_holder_mobile ca-img-preview-holder" id="forios">

      <% if(!_.isUndefined(model.html_content.IOS)){ %>
          <div class="mobile_push_title">
            <%= model.html_content.IOS.title %>
          </div>
          <div class="mobile_push_msg">
            <%= model.html_content.IOS.message %>
          </div>
      <% }else { %>
          <span class="no-mobile-display no-push-available"><?= _campaign("No IOS Push")?></span><% }  %>
          <div class="mobile-icon"><img src="/images/apple.png" alt="<?= _campaign("ios") ?>"><i class="icon-apple"></i> </div>
        </div> 
      </div>

      <div class="modal fade template-preview-modal" id="edit_template_preview_modal" >
        <div class="modal-dialog">
          <div class="modal-content">

            <div class="modal-body">
              <div class="c-status-option-123 preview_type-123">
                  <a class="c-status-live preview-mobile-push" id="android-all-preview"><?= _campaign("Android")?></a><a class="c-status-upcoming preview-mobile-push" id="ios-all-preview"><?= _campaign("IOS")?></a>
              </div>
              <div class="mobile-preview-icon-android">
                <div class="preview_container preview_container_margin_mobilepush"></div>
              </div>
              <div class="mobile-preview-icon-ios" style="display:none">
                <div class="preview_container_ios preview_container_margin_mobilepush_ios"></div>
              </div>
            </div>

            <div class="modal-footer">  
              <button type="button" class="btn btn-default" data-dismiss="modal"><?= _campaign("Cancel")?> </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="option_view">
      <div class="btn-group btn-tablecell edit_template_btn_group">
      
        <button type="button" id="edit_mobile_push_template"  template_id="<%- model.template_id %>" class="<%- edit_options.edit.className %> btn" style="width:97px">
        <%- edit_options.edit.label%>
        </button>
        <button type="button" class="btn dropdown-toggle" data-toggle="dropdown" style="width:31px">
            <span class="caret"></span><span class="sr-only"></span>
        </button>
        <ul class="dropdown-menu" role="menu">
          <li><a class="<%-edit_options.delete_t.className%>"><%-edit_options.delete_t.label%></a></li>
        </ul>

      </div>
    </div>

    <div  class="modal hide fade confirm_delete_modal">
      <div class="modal-header">
        <div class="ca-modal-header"><?= _campaign("Delete Template") ?>
        </div>
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

<script id="create_mobile_push_template_tpl" type="text/template">
     <div class="channel-container">
        <div class="c-float span2 ca-mobile-push-left">
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
            _.each(rc.tags, function(tag) {
              print(template_function(tag));
            })%>
            </ul>
        </div>
        </div>
        <div class="c-sel-temp c-float span6 ">
        <div>
          <span class="display-headtitle"><?= _campaign("Title")?></span><span class="red-error">*</span>
        
          <a class="ca-fright ca-mobile-push-copy ca-mobile-push-italic"><?= _campaign('Copy from')?> 
          <span>
          <% if(rc.tab_value == "android") {%>
          <?= _campaign('IOS')?>
          <%} if(rc.tab_value == "ios") { %>
           <?= _campaign('Android')?> 
           <% } %>
          </span>
          </a>
        </div>
        <div class="ca-mobile-push-reset-container">
         <% 
         if(rc.tab_value == "android"){
            if(!_.isUndefined(template.html_content.ANDROID)){
                 var mob_title = template.html_content.ANDROID.title;
              }
              }
          if(rc.tab_value == "ios"){
              if(!_.isUndefined(template.html_content.IOS)){
                  var mob_title = template.html_content.IOS.title;
              }
              }    

             %>
         <input type="text" name="ca-mobile-push-title" id="ca-mobile-push-title" class="ca-mobile-push-text ca-mobile-push-set-text ca-mobile-push-title" value="<%= mob_title%>" ca-mobile-push-title-android="<%= mob_title %>" ca-mobile-push-title-ios="<%= mob_title %>" >
         </div>

         <br>
         <div class=""><span class="display-headtitle"><?= _campaign("Message")?></span><span class="red-error">*</span><span class="ca-mobile-push-italic"> (<?= _campaign("Max limit 90 Characters")?>)</span>     
          <a class="ca-fright ca-mobile-push-copy ca-mobile-push-italic"><?= _campaign('Copy from')?>   <span>
          <% if(rc.tab_value == "android") {%>
          <?= _campaign('IOS')?>
          <%} if(rc.tab_value == "ios") { %>
           <?= _campaign('Android')?> 
           <% } %>
          </span></a>
          </div>
        <div class="ca-mobile-push-reset-container"> 
          <% if(rc.tab_value == "android"){
              if(!_.isUndefined(template.html_content.ANDROID)){
                 var mob_msg = template.html_content.ANDROID.message;
              }
              }
          if(rc.tab_value == "ios"){
              if(!_.isUndefined(template.html_content.IOS)){
                  var mob_msg = template.html_content.IOS.message;
              }
              }    

             %>
        <textarea id="ca-mobile-push-textarea" class="ca-mobile-push-textarea ca-mobile-push-set-text" ca-mobile-push-textarea-android="<%= mob_msg%>" ca-mobile-push-set-text" ca-mobile-push-textarea-ios="<%= mob_msg %>" maxlength="90"><%= mob_msg%></textarea>
        </div>
         <div style="clear:both"></div>
         <div class="">
         <% if (mob_msg) { %>
          <span class="show-count" id="count-characters-create-mp-tpl"><%= mob_msg.length %>
          </span> 
         <% } else { %>
          <span class="show-count" id="count-characters-create-mp-tpl">0</span>
         <% } %>
         <?= _campaign('characters')?>
         </div>
         <div style="clear:both"></div>
         <div style="clear:both"></div>
         <input type="hidden" id="mobilepush_template_scope" value="<%= rc.template_scope%>">
          <%
            if(rc.template_scope == "MOBILEPUSH_IMAGE") {%>
            <br>
            <div class="shellContainer">
              <div style="font-size: 12px;">
                <span class="display-headtitle" ><?= _campaign("Image") ?></span>
                <span class="red-error">*</span><span class="ca-mobile-push-italic">(<?= _campaign("max image size")?>:1080 x 540 px, <?= _campaign("max image file size")?>: 3MB)</span>
              </div>
              <span class="mobileViewPicLeft">
                <div class="mobile_push_image_file">
                  <% 
                  if(rc.tab_value == "android"){
                    if(!_.isUndefined(template.html_content.ANDROID)){
                     var imgSrc = template.html_content.ANDROID.expandableDetails.image;
                    }
                  }
                  if(rc.tab_value == "ios"){
                     if(!_.isUndefined(template.html_content.IOS)){
                      var imgSrc = template.html_content.IOS.expandableDetails.image;
                  } 
                  }   
                  %>   
                  <div>
                    <img src = "<%=imgSrc%>" style="height: 200px;" />
                  </div>
                </div>
              </span>
              <span class="mobileViewPicRight">
                <button type="button" class="ca-g-btn btn upload_image_file" > <?= _campaign("Upload") ?> </button> 
              </span>
            </div>
            <br>
            <div style="display: none">
              <form id="image_upload_<%= rc.tab_value %>" name="image_upload">
                <input type="file" name="upload_image" id="upload_image_<%= rc.tab_value %>">
              </form>
            </div>
         <% } %>
        
        <br>
        <div class="display-headtitle"><?= _campaign("Primary Call To Action")?></div>
        <div class="primary-cta-container">
        <div>
          <div class="span5">

         <%  console.log(rc.tab_value);

          if(rc.tab_value == "android"){
              if(!_.isUndefined(template.html_content.ANDROID)){
                if(!_.isUndefined(template.html_content.ANDROID.cta) &&
                    template.html_content.ANDROID.cta.type=="DEEP_LINK"){

                 var deepLink = template.html_content.ANDROID.cta.type;
                }
                if(!_.isUndefined(template.html_content.ANDROID.cta) &&
                    template.html_content.ANDROID.cta.type=="EXTERNAL_URL"){
                 var externalLink = template.html_content.ANDROID.cta.type;
                }
              }              }
              if(rc.tab_value == "ios"){
                 if(!_.isUndefined(template.html_content.IOS)){
                 if(!_.isUndefined(template.html_content.IOS.cta) &&           
                      template.html_content.IOS.cta.type=="DEEP_LINK"){

                 var deepLink = template.html_content.IOS.cta.type;
                }
               if(!_.isUndefined(template.html_content.IOS.cta) &&           
                      template.html_content.IOS.cta.type=="EXTERNAL_URL"){
                 var externalLink = template.html_content.IOS.cta.type;
                }
                }
               }
             %> 
           <input type="radio" name="primary-link-<%= rc.tab_value %>" class="primary-link" value="deep-link"

            <% if( deepLink == 'DEEP_LINK'){ %> checked="checked" <% } %> /> <?= _campaign("Deep Link")?></div>
           <div>        
          <input type="radio" name="primary-link-<%= rc.tab_value %>" class="primary-link" value="external-link"  <% if( externalLink == 'EXTERNAL_URL') { %> checked="checked" <% } %>  /> <?= _campaign("External Link")?>
          <span id="reset-primary-cta-android" style="display: inline-block;float: right;display: inline-block;margin-right: 15px;"><a class="ca-mobile-push-reset-primary-container"><i class="icon-refresh"></i></a></span>
          </div>
        </div>
         <div class="ca-mobile-push-reset-container">
          <% 
          if(rc.tab_value == "android"){
            if(!_.isUndefined(template.html_content.ANDROID)){
                if(!_.isUndefined(template.html_content.ANDROID.cta) &&
                    template.html_content.ANDROID.cta.type=="DEEP_LINK"){
                 var deepActionLink = template.html_content.ANDROID.cta.actionLink;
                }
               if(!_.isUndefined(template.html_content.ANDROID.cta) &&
                    template.html_content.ANDROID.cta.type=="EXTERNAL_URL"){
                 var externalActionLink = template.html_content.ANDROID.cta.actionLink;
                }
               }
              }
          if(rc.tab_value == "ios"){
            if(!_.isUndefined(template.html_content.IOS)){
              if(!_.isUndefined(template.html_content.IOS.cta) &&
                  template.html_content.IOS.cta.type=="DEEP_LINK"){
                var deepActionLink = template.html_content.IOS.cta.actionLink;
                }
            if(!_.isUndefined(template.html_content.IOS.cta) &&
                template.html_content.IOS.cta.type=="EXTERNAL_URL"){
                 var externalActionLink = template.html_content.IOS.cta.actionLink;
                }
              }
              }  

              if(deepActionLink)
                var actionLink =  deepActionLink;
              if(externalActionLink)
                var  actionLink = externalActionLink;            
             %> 
         <input type="text" id="ca-mobile-push-primary" class="ca-mobile-push-text ca-mobile-push-text-link" ca-mobile-push-primary-deep-link-android="<%= deepActionLink %>" ca-mobile-push-primary-external-link-android="<%= externalActionLink %>" ca-mobile-push-primary-deep-link-ios="<%= deepActionLink %>" ca-mobile-push-primary-external-link-ios="<%= externalActionLink %>"  value="<%= actionLink %>">
        
          </div>
        </div>
        <br>
        <div>
          <div class="display-headtitle"><?= _campaign("Secondary Call To Action")?></div>
           <% if(rc.tab_value == "android"){%>
           <div class="ca-mobile-push-show-secondary"></div>
           <% } %>
          <div class="ca-mobile-push-hide">
          <%   if(rc.tab_value == "ios")
                var cta_cls =  "ca-mobile-push-add-ios";
               if(rc.tab_value == "android")
                var cta_cls =  "ca-mobile-push-add";
            %>     
          <button class="ca-g-btn ca-save-btn <%= cta_cls %>"><?= _campaign("Add")?></button></div>

        </div>
        <% if(rc.tab_value == "ios"){%>
          <div class="add-secondary-IOS"></div>
         <% } %> 
              <div class="red-error ca-required">* <?= _campaign("Required Fields")?></div> 
        </div>
      </div>
</script>
<script id="create_IOS_secondary_tpl" type="text/template">
<div class="secondary-CTA secondary-CTA-IOS">
  <div style="float:right;margin: 10px;">
    <a class="ca-mobile-push-reset-ios-div"><i class="icon-trash ca-mobile-icon"></i></a> </div>
   <div class="secondary-cta-ios-container">
    <table class="table table-striped">
          <thead><tr><th style="width: 62%;"><?= _campaign("NAME & DESCRIPTION")?></th>
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
<script type="text/template" id="IOS_secondary_detail_tpl">
  <div class="IOS-secondary-detail-block">
  <div class="secondary-CTA">
    <div style="float:right;margin: 10px;">
    <a class="ca-mobile-push-delete-container"><i class="icon-trash ca-mobile-icon ca-mobile-icon-ios"></i></a> 
    </div>
     <div class="display-headtitle ios-cta-name"><%=  rc.ios_name%></div>
     <input type="hidden" id="ios_category_id" value="<%=  rc.categoryId  %>">

     <% 
     console.log("CtasObj :",CtasObj);
     _.each(rc.CtasObj,function(option,key){
     console.log("option madhu:",option);

      if(!_.isEmpty(option)){
        if(key == 'name' ){
        return false;
     } %>
      <% if(option.launch_app =="false" || option.launch_app == false){ %>        
         <div class="ca-mobile-push-ios-container" ca-mobile-push-key=<%= key %> style="display:none;">
      <% } else {%>
         <div class="ca-mobile-push-ios-container" ca-mobile-push-key=<%= key %>>
      <% } %>   
      <div class="ca-mobile-push-reset-container" >    
      <input type="hidden" id="ca-mobile-push-ios-launchapp-<%= key %>" value="<%= option.launch_app %>">        
      <input type="hidden" id="ca-mobile-push-ios-<%= key %>"  value="<%= option.item_id %>">
               <div class="display-headtitle ca-mobile-push-secondary-label" id="ca-mobile-push-label<%= key %>"
              ca-mobile-push-label<%= key %>-ios="<%= option.item_text%>" ><%= option.item_text%></div>
             </div>
             <div>
               <div class="span5"> 
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
<script type="text/template" id="create_IOS_secondary_tpl_cta">
    <tr class="ca-mobile-push-cta">
     <td>
     <input type="hidden" name="categoryId" id="categoryId" value="<%= rc.model.id %>">
     <span class="name"><%= rc.model.name %></span><br/>
     <%= rc.model.description %>
      </td>
      <%
        _.each(rc.model.ctaTemplateDetails, function(v, k) { 
          var button_text = rc.model.ctaTemplateDetails[k].buttonText;  
          var button_description = rc.model.ctaTemplateDetails[k].description; 
          var button_id = rc.model.ctaTemplateDetails[k].id;
          var to_LaunchApp = rc.model.ctaTemplateDetails[k].toLaunchApp;
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
