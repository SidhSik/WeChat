<?php
include_once 'base_model/campaigns/class.BulkMessage.php';
include_once 'services/applications/impl/applications/ApplicationTypes.php';
include_once 'services/applications/impl/applications/ApplicationFactory.php';
include_once 'services/applications/impl/features/types/campaigns/CampaignServiceFeatureTypeImpl.php';
include_once 'base_model/class.OrgDetails.php';
include_once 'ui/widget/base/WidgetFactory.php';
include_once 'creative_assets/CreativeAssetsManager.php';
include_once 'creative_assets/controller/ModuleWiseTagsProvider.php';
include_once 'business_controller/campaigns/message/api/ValidationStep.php';
include_once 'business_controller/campaigns/CheckListController.php';
include_once 'business_controller/campaigns/message/api/ValidationStep.php';
include_once 'tasks/impl/CallTask.php';
include_once 'business_controller/campaigns/OutboundController.php';
include_once 'helper/simple_html_dom.php';
include_once 'business_controller/campaigns/CampaignMetadataController.php';
include_once 'business_controller/NSAdminController.php';
include_once 'helper/ConfigManager.php';
/**
 * Campaign Messages handling ajax service seprating it from campaign ajaxservice
 * 
 * @author bhavesh
 *        
 */
class CampaignMessagesAjaxService extends BaseAjaxService {
	private $OrgController;
	private $C_campaign_controller;
	private $campaign_model;
	private $C_config_mgr;
	private $C_outbound_controller;
	private $C_metadata_controller;
	private $nsadmin_controller;
	private $config_controller;

	public function __construct($type, $params = null) {
		global $url_version, $currentorg;
		
		parent::__construct ( $type, $params );
		
		$url_version = '1.0.0.1';
		
		// To load the cheetah's organizational model extension
		$org_id = $currentorg->org_id;
		$currentorg = new OrganizationModelExtension ();
		$currentorg->load ( $org_id );
		
		$this->OrgController = new OrganizationController ();
		$this->C_campaign_controller = new CampaignController ();
		$this->campaign_model = new CampaignBaseModel ();
		$this->C_config_mgr = new ConfigManager ();
		$this->C_outbound_controller = new OutboundController ();
		$this->C_metadata_controller = new CampaignMetadataController();
		$this->nsadmin_controller = new NSAdminController();
		$this->config_controller = new ConfigManager();
	}
	public function process() {
		$this->logger->debug ( '@@@TYPE:' . $this->type );
		
		switch ($this->type) {
			
			case 'recipient' :
				$this->logger->debug ( '@@@INSIDE TEST BACKBONE' );
				$this->getRecipient ();
				break;

			case 'Singlerecipient' :
				$this->logger->debug ( '@@@INSIDE TEST SINGLE' );
				$this->getSingleRecipient ();
				break;
			
			case 'get_template_list' :
				$this->logger->debug ( '@@@Get Template List' );
				$this->getTemplateList ();
				break;
			
			case 'msg_preview' :
				$this->logger->debug ( '@@@Inside Message Preview' );
				$this->getMessagePreview ();
				break;
			
			case 'process_template' :
				$this->logger->debug ( '@@@inside process_template' );
				$this->processTemplate ();
				break;
			
			case 'default_msg_val' :
				$this->logger->debug ( '@@@Inside Default Value ' );
				$this->getMessageDefaultValue ();
				break;
			
			case 'get_plain_text' :
				$this->logger->debug ( '@@@Inside get plain text' );
				$this->getPlainText ();
				break;
			
			case 'proccess_plain' :
				$this->logger->debug ( '@@@inside process plain text' );
				$this->processPlainTextView ();
				break;
			
			case 'queue_message' :
				$this->logger->debug ( '@@@inside quque message' );
				$this->queueMessage ();
				break;
			
			case 'recipient_sms' :
				$this->logger->debug ( '@@@inside recipient for sms' );
				$this->getRecipientForSMS ();
			
			case 'sms_template' :
				$this->logger->debug ( '@@@Inside Process Recipient of sms template' );
				$this->processRecipientSMS ();
				break;
			
			case 'process_sms_tempalte' :
				$this->logger->debug ( '@@@Inside SMS template' );
				$this->getSmsTemplateTag ();
				break;
			
			case 'save_sender' :
				$this->logger->debug ( '@@@Saving Sender Label' );
				$this->saveSenderLabel ();
				break;
			
			case 'get_template_preview_url' :
				$this->logger->debug ( '@@@Generating preview for file service' );
				$this->generatePreviewUrl ();
				break;
			
			case 'call_recipient' :
				$this->logger->debug ( '@@@inside call taks get recipient' );
				$this->getCallTaskRecipient ();
				break;
			
			case 'call_template_processing' :
				$this->logger->debug ( '@@@inside process tempalte selection' );
				$this->processTemplateCallTask ();
				break;
			
			case 'call_task_delivery' :
				$this->logger->debug ( '@@@processing call task template' );
				$this->getDeliverySettingsForCallTask ();
				break;
			
			case 'group_user' :
				$this->logger->debug ( '@@@Inside Get Group USer' );
				$this->getCallTaskPreview ();
				break;
			
			case 'subject_autosuggest' :
				$this->logger->debug ( '@@Inside Subject Auto Suggest' );
				$this->getSubjectList ();
				break;
			case 'check_session' :
				$this->logger->debug ( '@@@CheckSessionExpire' );
				$this->checkSessionExpiry ();
				break;
			case 'add_required_links' :
				$this->logger->debug ( '@@@add required links' );
				$this->addRequiredLinks ();
				break;
			case 'validate_msg' :
				$this->validateMsg ();
				break;
			case 'validate_all_msg' :
                $this->validateAllMsg();
                break;
			case 'process_reminder_sms_template':
				$this->processReminderSmsTemplate();
				break;
			case 'process_reminder_email_template':
				$this->processReminderEmailTemplate();
				break;
			case 'save_reminder_message':
				$this->saveReminderMessage();
				break;
			case 'process_plain_reminder':
				$this->processPlainReminder();
				break;
			case 'get_message_creative_data':
				$this->getMessageCreativeData();
				break;
			case 'get_multi_lang_template':
				$this->getSecondaryTemplates();
				break;
			case 'remove_secondary_language' :
				$this->removeSecondaryTemplate();
				break ;	
			case 'get_mobile_push_data' :
				$this->getMobilePushData() ;
				break ;	
			default :
				$this->logger->debug ( '@@@Invalid type passed ' );
		}
	}

	private function getSingleGroupDetails($campaign_id, $group_id_param) {
		$C_campaign_controller = new CampaignController ();
		
		$audience_data = $C_campaign_controller->getGroupDetailsForCampaignId ( $campaign_id );
		
		$this->logger->debug ( 'test audience data: ' . print_r ( $audience_data, true ) );
		foreach ( $audience_data as $val )
			$tmp [] = $val ['id'];
		array_multisort ( $tmp, SORT_ASC, $audience_data );
		
		$this->logger->debug ( 'test audience data: sorted ' . print_r ( $audience_data, true ) );
		
		$control_groups = $C_campaign_controller->getControlGroupsByCampaignID ( $campaign_id );
		
		$group_ids = array ();
		$audience_group_ids = array ();
		$sticky_array = array ();
		foreach ( $audience_data as $row ) {
			if($group_id_param==$row['params']){
			array_push ( $group_ids, $row ['params'] );
			array_push ( $audience_group_ids, $row ['id'] );
			}
		}
		
		$all_group_details = $C_campaign_controller->getGroupDetailsbyGroupIds ( $group_ids );
		
		$all_groups = array ();
		foreach ( $all_group_details as $row ) {
			$all_groups [$row ['group_id']] = $row;
		}
		
		foreach ( $audience_data as $row ) {

			if($group_id_param==$row['params']){
				$group_id = $row ['params'];
				$group_details = $all_groups [$group_id];
			}
			
			if ($group_id) {
				if (! isset ( $group_details ['group_id'] ))
					continue;
				$auto_generated_group = 'auto_gen_expiry_reminder_group_';
				if(strncmp($group_details['group_label'],$auto_generated_group,strlen($auto_generated_group))===0)
					continue;
				
				$peer_group_id = $group_details ['peer_group_id'];
				if ($peer_group_id)
					$control_group_details = $control_groups [$peer_group_id];
				
				$params_number = json_decode ( $group_details ['params'], true );
				
				if ($peer_group_id) {
					
					$count = $group_details ['total_clients'];
					$test_users = $group_details ['customer_count'];
					$control_users = ( int ) $control_group_details ['customer_count'];
					
					// If Both the test and controll user are zero then total client must be zero.
					if ($test_users == 0 && $control_users == 0)
						$count = 0;
					
					$sticky_array [$group_id] ['count'] = $count;
					$sticky_array [$group_id] ['test'] = $test_users;
					$sticky_array [$group_id] ['control'] = $control_users ? $control_users : 0;
				} else {
					$count = $group_details ['customer_count'];
					$sticky_array [$group_id] ['count'] = $count;
				}
				
				$sticky_array [$group_id] ['group_id'] = $group_details ['group_id'];
				$sticky_array [$group_id] ['group_label'] = $group_details ['group_label'];
				//$sticky_array [$group_id] ['group_label'] = 'abhinav';
				$sticky_array [$group_id] ['email'] = $params_number ['email'] ? $params_number ['email'] : 0;
				$sticky_array [$group_id] ['mobile'] = $params_number ['mobile'] ? $params_number ['mobile'] : 0;
				$sticky_array [$group_id] ['id'] = $group_details ['group_id'];
				$sticky_array [$group_id] ['campaign_id'] = $campaign_id;
				$sticky_array [$group_id] ['is_completed_mobile'] = 1;
				$sticky_array [$group_id] ['is_completed_email'] = 0;

			}
		}
		$this->logger->debug ( '@@@CAMPAIGN_LIST:-' . print_r ( $sticky_array, true ) );

		$channel = 'ALL';
		$reach_response_mode = $this->C_outbound_controller->getReachableCustomer($campaign_id,$channel,$group_id_param);

		$this->logger->debug('@@@Group List CampaignMessagesAjaxService:-'.print_r( $reach_response_mode , true ));
		$this->logger->debug ( '@@@CAMPAIGN_LIST:- sticky' . print_r ( $sticky_array, true ) );
		//return $sticky_array;
		return $reach_response_mode;

		//return $sticky_array;
	}

	private function getSingleRecipient() {
		global $currentorg;
		
		$this->logger->debug ( '@@@GROUPsingle:-' . print_r ( $_GET, true ) );
		$campaign_id = $_GET ['campaign_id'];
		$message_id = $_GET ['message_id'];
		
		$C_campaign_controller = new CampaignController ();
		$campaign_data = array ();
		
		if ($message_id) {
			$message_details = $C_campaign_controller->getDefaultValuesbyMessageId ( $message_id );
			$this->logger->debug('message-details'.print_r($message_details['group_id'],true));
			$group_id_param = $message_details['group_id'];
		}

		$group_info = $this->getSingleGroupDetails ( $campaign_id, $group_id_param );
		//$this->logger->debug('@@@checkingGroupInfo'.$group_info);
		$this->logger->debug('group-details'.print_r($group_info,true));
		$this->data ['item_data'] = $group_info[$group_id_param];
		$this->data ['message_data'] = $message_details;

		$msg_id = $_GET["message_id"];
			$messages = $this->C_outbound_controller->getMessageDetailsForAuthorization( $msg_id );
			$this->logger->debug("auth Called".print_r($messages,true));
			$details = $this->C_outbound_controller->getCheckListDetails( $msg_id );
			$this->logger->debug("auth Called".print_r($details,true));
			$details["messages"] = $messages;
			$this->logger->debug( "@@Authorize Messages: ".print_r( $details[checklist], true ) );
			$this->data["auth_details"] = $details[checklist];
			$this->logger->debug( "@@Authorize Message Details End" );
			
			$this->addNotificationReceivers();
	}
	
	/**
	 * returning list of recipient
	 */
	private function getRecipient() {
		global $currentorg;
		
		$this->logger->debug ( '@@@GROUP:-' . print_r ( $_GET, true ) );
		//$selected_group_ids = $_GET ['group_id'];

		//  value whether ndnc gateway is present for org or not !!!
		

		$campaign_id = $_GET ['campaign_id'];
		$message_id = $_GET ['message_id'];
		$get_from_details = $_GET ['get_from_details'];

		$ndnc_gateway = $this->nsadmin_controller->isGatewayNdnc($currentorg->org_id,$campaign_id);
		$this->logger->debug ('Inside get recipient result::'.$ndnc_gateway);

		// getting key value for ndnc enabled at org level !!!
		$key_name = "CONF_ORG_SEND_TO_NDNC_ENABLED";
		$key_value = $this->config_controller->getKeyValueForOrg($key_name,$currentorg->org_id);
		$this->logger->debug("config key value updated".print_r($key_value,true));

		$ndnc = array();
		$ndnc['ndnc_gateway'] = $ndnc_gateway;
		$ndnc['ndnc_enabled'] = $key_value;

		$this->data['ndnc'] = $ndnc;
		
		$group_info = $this->getGroupDetails ( $campaign_id );
		
		$C_campaign_controller = new CampaignController ();
		$campaign_data = array ();
		$nsadmin_controller = new NSAdminController();
		
		if ($message_id) {
			$message_details = $C_campaign_controller->getDefaultValuesbyMessageId ( $message_id );
			$this->logger->debug('message-details'.print_r($message_details,true));
			$this->data ['message_data'] = $message_details;
		}
		
		$campaign_list_sticky = $C_campaign_controller->getCampaignGroupsByCampaignIds ( - 20 );
		foreach ( $campaign_list_sticky as $row ) {
			$groupsids [] = $row ['group_id'];
			$this->group_list [$row ['group_label']] = $row ['group_id'];
		}
		
		$campaign_list_sticky = $C_campaign_controller->getGroupDetailsbyGroupIds ( $groupsids );
		$sticky = array ();
		foreach ( $campaign_list_sticky as $row ) {
			$group_id = $row ['group_id'];
			$params = json_decode ( $row ['params'], true );
			$sticky [$group_id] ['group_id'] = $group_id;
			$sticky [$group_id] ['group_label'] = $row ['group_label'];
			$sticky [$group_id] ['count'] = $row ['customer_count'];
			$sticky [$group_id] ['is_favourite'] = $row ['is_favourite'];
			$sticky [$group_id] ['email'] = ($params ['email']) ? $params ['email'] : 0;
			$sticky [$group_id] ['mobile'] = ($params ['mobile']) ? $params ['mobile'] : 0;
			$sticky [$group_id] ['listype'] = 'sticky';
			$sticky [$group_id] ['params'] = $row ['params'];
			$sticky [$group_id] ['id'] = $group_id;
			$sticky [$group_id] ['campaign_id'] = $campaign_id;
		}
		
		array_push ( $campaign_data, array (
				'non_sticky' => $group_info,
				'sticky' => $sticky 
		) );
		$this->data ['item_data'] = $campaign_data [0];
		$this->logger->debug ( 'campaign list daa: ' . print_r ( $this->data ['item_data'], true ) );
		$this->logger->debug ( 'current org '.$currentorg->org_id );
		
		$sender_details = $C_campaign_controller->getSenderDetails ();
		
		if((int)$get_from_details == 1) {
			$domain_gateway_map = $nsadmin_controller->getDomainPropertiesGatewayMapByOrg();
			$this->logger->debug("getRecipient getDomainPropertiesGatewayMapByOrg: " . print_r($domain_gateway_map, true));
			$sender_details["default_domain"] = $nsadmin_controller->getDefaultDomain($domain_gateway_map['bulk']);
			$domain_gateway_map = $nsadmin_controller->parseDomainGatewayMap($domain_gateway_map['bulk']);
			$sender_details["domainProps"] = $domain_gateway_map;
		}	
		
		$sender_details ["sender_label"] = Util::valueOrDefault ( $sender_details[0] ["sender_label"], "N/A" );
		$sender_details ["sender_email"] = Util::valueOrDefault ( $sender_details[0] ["sender_email"], "N/A" );
		$sender_details ["sender_gsm"] = Util::valueOrDefault ( $sender_details [0]["sender_gsm"], "N/A" );
		$sender_details ["sender_cdma"] = Util::valueOrDefault ( $sender_details[0] ["sender_cdma"], "N/A" );
		$this->data ['custom_sender'] = $sender_details;
	}
	
	// Get list of templates for backbone
	private function getTemplateList() {
		global $currentorg;
		try {
			$campaign_id = $_GET ['campaign_id'];
			$is_expired = $this->C_campaign_controller->isCampaignExpired ( $campaign_id );
			if (! $is_expired) {
				$C_creative_assets_manager = new CreativeAssetsManager ();
				
				$file_list = $C_creative_assets_manager->getAllTemplates ( $currentorg->org_id, 'HTML', 'ORG' );
				//$basic_file_list = $C_creative_assets_manager->getTemplateByTag ( 'BASIC' );
				//$advanced_file_list = $C_creative_assets_manager->getTemplateByTag ( 'ADVANCED' );
				$responsive_file_list = $C_creative_assets_manager->getTemplateByTag ( 'MOBILE_BLOCK' );
				
				$this->data ['creative'] = $file_list;
				//$this->data ['basic'] = array_merge ( $basic_file_list, $advanced_file_list );
				$this->data ['responsive'] = $responsive_file_list;
			} else
				$this->data ['error'] = $is_expired;
		} catch ( Exception $e ) {
			$this->data ['error'] = $e->getMessage ();
		}
	}
	
	/**
	 * gettting preview for template selected
	 */
	private function getMessagePreview() {
		$template_id = $_GET ['template_id'];
		$this->C_assets = new CreativeAssetsManager ();
		$C_template = new Template ();
		
		if ($template_id === "undefined" || $template_id == '') {
			$this->data ['error'] = _campaign("Coupon html is not available for this organization.");
			return;
		}
		
		if ($template_id == - 1) {
			$this->data ['info'] = "{{unsubscribe}}";
			return;
		}
		try {
			
			$C_template->load ( $template_id );
			$this->logger->debug ( '@@@Inside message preview method template id ' . $template_id );
			$content = $this->C_assets->getDetailsByTemplateId ( $template_id );
			$this->data ['info'] = stripcslashes ( $content ['content'] );
		} catch ( Exception $e ) {
			$this->data ['error'] = $e->getMessage ();
		}
	}
	private function processTemplate() {
		global $currentorg;
		
		$this->logger->debug ( '@@@Inside Process Template' );
		$campaign_id = $_GET ['campaign_id'];
		$message_id = $_GET ['msg_id'];
		$template_id = $_GET ['template_id'];
		
		$C_campaign_controller = new CampaignController ();
		$C_creative_assets_manager = new CreativeAssetsManager ();
		
		if (! $message_id)
			$this->getMessagePreview ();
		else {
			$default_val = $C_campaign_controller->getDefaultValuesbyMessageId ( $message_id );
			if (! $template_id)
				$this->data ['info'] = rawurldecode ( $default_val ['message'] );
			else
				$this->getMessagePreview ();
			
			$this->data ['subject'] = rawurldecode ( $default_val ['subject'] );
		}
		
		$C_campaign_controller->load ( $campaign_id );
		$series_id = $C_campaign_controller->campaign_model_extension->getVoucherSeriesId ();
		if (! $series_id) {
			$series_id = - 1;
		}
		
		$C_creative_assets_manager = new CreativeAssetsManager ();
		
		//pass program id to check for tag validation
		$params["program_id"] = $_GET['program_id'];
		$custom_tag = $C_campaign_controller->getSupportedTagsByType ( 'EMAIL', $campaign_id, false , $params);
		$this->data ['custom_tag'] = $this->getTagList ( $custom_tag );
		
		$sender_details = $C_campaign_controller->getSenderDetails ();
		
		$sender_details ["sender_label"] = Util::valueOrDefault ( $sender_details[0] ["sender_label"], "N/A" );
		$sender_details ["sender_email"] = Util::valueOrDefault ( $sender_details[0] ["sender_email"], "N/A" );
		$sender_details ["sender_gsm"] = Util::valueOrDefault ( $sender_details[0] ["sender_gsm"], "N/A" );
		$sender_details ["sender_cdma"] = Util::valueOrDefault ( $sender_details[0] ["sender_cdma"], "N/A" );
		
		$this->data ['custom_sender'] = $sender_details;
		
		$image_list = $C_creative_assets_manager->getAllOrgCouponTemplates ( $currentorg->org_id, $series_id, 'IMAGE' );
		$html_list = $C_creative_assets_manager->getTemplateByChannelsPreview ( $currentorg->org_id, $series_id, 'HTML', 'EMAIL' );
		
		$this->data ['image_list'] = $image_list;
		$this->data ['html_list'] = $html_list;
		
		$survey_list = $C_campaign_controller->getSurveyFormsByOutboundCampaignId ( $campaign_id );
		$this->data ['survey_list'] = $survey_list;
		
		$social_icon = $C_campaign_controller->getSupportedSocial ();
		
		$social_platform = $C_campaign_controller->getSupportedSocialPlatform ();
		
		$image_list = $C_creative_assets_manager->getAllOrgCouponTemplates ( $this->org_id, $series_id, 'IMAGE' );
		$this->data ['social'] = $this->generateSocialIcon ( $social_icon, $social_platform );
		
		$social_platform = $C_campaign_controller->getSupportedSocialPlatform ();
		
		$html_list = $C_creative_assets_manager->getTemplateByChannelsPreview ( $this->org_id, $series_id, 'HTML', 'EMAIL' );
	}

	private function getTimeTagList($campaign){
		
		foreach ( $campaign as $k => $v ) {
			if (is_array ( $v )) {
				$str .= "<li class='parent_tags_menu2' id='ptags2__" . Util::uglify ( $k ) . "' >
						<i class='color-Med-Gray icon-caret-right' id='submenu_icon2__" . Util::uglify ( $k ) . "' ></i> " . Util::beautify ( $k );
				$str .= "<ul id='tags_submenu2__" . Util::uglify ( $k ) . "' class='parent_email_sub_tag2 hide'>";
				
				foreach ( $v as $k2 => $v2 ) {
					$str .= "<li id='tag2__$v2' url='$v2' class='msg_tags_edit survey_tags' >" . Util::beautify ( $k2 ) . "<i class='color-Green icon-chevron-right float-right'></i></li>";
				}
				if(is_array($v) && count($v)==0){
					$str .= "<p>"._campaign("No Campaigns to Display")."</p>" ;	
				}
				$str .= "</ul></li>";
			} else {
				$str .= "<li id='tag__$v' class='msg_tags_edit' >" . Util::beautify ( $k ) . "
				<i class='color-Green icon-chevron-right float-right'></i></li>";
			}
		}
		
		return $str ;	
	}

	private function getTimeRefList($refTags){
		$str="" ;
		foreach($refTags as $k=>$v){
			if (is_array ( $v )) {
				$str .= "<li class='parent_tags_menu2' id='ptags2__" . Util::uglify ( $k ) . "' >
						<i class='color-Med-Gray icon-caret-right' id='submenu_icon2__" . Util::uglify ( $k ) . "' ></i> " . Util::beautify ( $k );
				$str .= "<ul id='tags_submenu2__" . Util::uglify ( $k ) . "' class='parent_email_sub_tag2 hide'>";
				
				foreach ( $v as $k2 => $v2 ) {
					$str .= "<li id='tag2__$v2' url='$v2' class='msg_tags_edit' >" . Util::beautify ( $k2 ) . "<i class='color-Green icon-chevron-right float-right'></i></li>";
				}
				
				$str .= "</ul></li>";
			} else {
				$str .= "<li id='tag__$v' class='msg_tags_edit' >" . Util::beautify ( $k ) . "
				<i class='color-Green icon-chevron-right float-right'></i></li>";
			}			
		}
		return $str ;
	}
	
	/**
	 * retugning list of tags and corresponding html
	 * 
	 * @param unknown $custom_tags        	
	 * @return string
	 */
	private function getTagList($custom_tags) {
		
		$C_campaign_controller = new CampaignController ();
		$C_campaign_controller->load($_GET ['campaign_id']) ; //campaign id
		$campaign_type = $C_campaign_controller->campaign_model_extension->getType() ;

		$str = "<ul class='tags-option'>";
		
		foreach ( $custom_tags as $key => $value ) {
			
			if (is_array ( $value )) {
				
				$str .= "<li class='parent_tags_menu' id='ptags__" . Util::uglify ( $key ) . "' >
							<i class='color-Med-Gray icon-caret-right' id='submenu_icon__" . Util::uglify ( $key ) . "' ></i>" . Util::beautify ( $key );
				$str .= "<ul id='tags_submenu__" . Util::uglify ( $key ) . "' class='parent_email_sub_tag hide'>";

				if($key ==_camp1('Add Survey') && $campaign_type=='timeline'){
					include_once '../business_controller/campaigns/surveys/SurveyController.php' ;
					$surveyController = new SurveyController() ;
					$temp = $surveyController->getSurveyIdName() ;
					$str .= $this->getTimeTagList($value) ;
				}else if( $key == _camp1('Referral Tags') && $campaign_type=='timeline'){
					$str .= $this->getTimeRefList($value) ;	
				}else{
					foreach ( $value as $k => $v ) {
						
						if (is_array ( $v )) {
							
							$str .= "<li class='parent_tags_menu2' id='ptags2__" . Util::uglify ( $k ) . "' >
									<i class='color-Med-Gray icon-caret-right' id='submenu_icon2__" . Util::uglify ( $k ) . "' ></i> " . Util::beautify ( $k );
							$str .= "<ul id='tags_submenu2__" . Util::uglify ( $k ) . "' class='parent_email_sub_tag2 hide'>";
							
							foreach ( $v as $k2 => $v2 ) {
								$str .= "<li id='tag2__$v2' class='msg_tags_edit' >" . Util::beautify ( $k2 ) . "<i class='color-Green icon-chevron-right float-right'></i></li>";
							}
							
							$str .= "</ul></li>";
						} else {
							
							if (Util::uglify ( $key ) == "survey_forms" || Util::uglify ( $key ) == "add_survey") {
								$form_name = Util::beautify ( $k );
								if (strlen ( $form_name ) > 30) {
									// $form_name = substr($form_name,0,30)."...";
								}
								$str .= "<li id='tag__$v' url='$v' class='msg_tags_edit survey_tags' >" . $form_name . "
								<i class='color-Green icon-chevron-right float-right'></i></li>";
							} else {
								$str .= "<li id='tag__$v' class='msg_tags_edit' >" . Util::beautify ( $k ) . "
								<i class='color-Green icon-chevron-right float-right'></i></li>";
							}
						}
					}
				}
				$str .= "</ul></li>";
			} else
				$str .= "<li id='tag__$value' class='msg_tags_edit custom-tag-margin' >" . Util::beautify ( $key ) . "
				<i class='color-Green icon-chevron-right float-right'></i></li>";
		}
		return $str;
	}
	private function getMessageDefaultValue() {
		$C_campaign_controller = new CampaignController ();
		$this->data ['message_data'] = $C_campaign_controller->getDefaultValuesbyMessageId ( $_GET ['message_id'] );
	}
	private function getPlainText() {
		$this->logger->debug ( '@@@Inside get Plain text params :' . print_r ( $_POST, true ) );
		$html = $_POST ['html'];
		
		include_once 'helper/class.html2text.inc';
		
		$html2text = & new html2text ( urldecode ( $html ) );
		$html_content = $html2text->get_text ();
		$this->logger->debug ( '@@@HELLO' . $html_content );
		$this->data ['plain_text'] = $html_content;
	}
	private function processPlainTextView() {
		$this->logger->debug ( '@@@Inside process plain text view' . print_r ( $_GET, true ) );
		$this->data ['info'] = 'success';
		
		$group_ids = $_GET ['group_ids'];
		$campaign_id = $_GET ['campaign_id'];
		$message_id = $_GET ['msg_id'];
		$list_type = $_GET ['type'];
		$msg_type = $_GET ['msg_type'];
		$account_id = $_GET['account_id'] ? $_GET['account_id'] : null;
		
		$C_campaign_controller = new CampaignController ();
		$check_list_controller = new CheckListController ();
		
		$C_outbound_msg_send_when_provider = ApplicationFactory::getApplicationByCode ( ApplicationType::CAMPAIGN );
		
		$send_options = $C_outbound_msg_send_when_provider->getData ( CampaignServiceFeatureTypeImpl::$OUTBOUND_MESSAGES_SEND_WHEN );
		
		$custom_sender_Details = $C_campaign_controller->getSenderDetails($msg_type,$account_id);
		$this->data['sender_details']= $custom_sender_Details;
		$this->logger->debug("processPlainTextView Sender details".print_r($custom_sender_Details,true));

		$C_campaign_controller->load ( $campaign_id );
		$details = $C_campaign_controller->getDetails ();
		
		$this->logger->debug ( 'get detail for sticky list ' . $group_ids );
		$C_group_model = new GroupDetailModel ();
		$C_group_model->load ( $group_ids );
		$group_hash = $C_group_model->getHash ();
		
		$list = $this->C_outbound_controller->getListDetailsByGroupId ( $campaign_id, $group_ids );
		
		$this->logger->debug ( 'detail for list ' . print_r ( $list_sticky, true ) );
		$group_info = array ();
		$group_id = $group_hash ['group_id'];
		$params = json_decode ( $group_hash ['params'], true );
		
		$group_info ['group_id'] = $group_id;
		$group_info ['group_label'] = $group_hash ['group_label'];
		$group_info ['count'] = $group_hash ['customer_count'];
		$group_info ['is_favourite'] = $group_hash ['is_favourite'];
		$group_info ['email'] = ($params ['email']) ? $params ['email'] : 0;
		$group_info ['mobile'] = ($params ['mobile']) ? $params ['mobile'] : 0;
		$group_info ['params'] = $params;
		$group_info ['name'] = $list;
		
		if (($details ["type"] == "timeline") && ($details ['voucher_series_id'] != - 1)) {
			$details ['voucher_series_id'] = json_decode ( $details ['voucher_series_id'], true );
			$series_details ["voucher_series_id"] = $details ['voucher_series_id'] [0];
			$series_details ["description"] = (count ( $details ['voucher_series_id'] ) > 0) ? _campaign("Coupon series is already configured") : _campaign("Coupon series is not configured");
		} else {
			if ($details ['voucher_series_id'] > 0)
				$series_details = $this->C_outbound_controller->getVoucherSeriesDetailsForCoupon ( $campaign_id );
			else
				$series_details = array (
						"description" => _campaign("Coupon series is not configured") 
				);
			
			$series_details ["voucher_series_id"] = $details ['voucher_series_id'];
		}
		
		$check = $check_list_controller->getCheckList ( $campaign_id );
		foreach ( $check as $key => $value ) {
			
			if ($msg_type == 'EMAIL') {
				if (isset ( $value ['sender_email'] ) && ! $value ['sender_email'])
					$check_list [] = " Sender ID";
				if (isset ( $value ['sender_label'] ) && ! $value ['sender_label'])
					$check_list [] = " Sender Label";
				if (isset ( $value ['replyto_email'] ) && ! $value ['replyto_email'])
					$check_list [] = " Replay-to ID";
			}
			
			if ($msg_type == 'SMS') {
				if (isset ( $value ['sender_gsm'] ) && ! $value ['sender_gsm'])
					$check_list [] = _campaign(" Sender GSM");
				if (isset ( $value ['sender_cdma'] ) && ! $value ['sender_cdma'])
					$check_list [] = _campaign(" Sender CDMA");
			}
		}
		
		$gateway = $check_list_controller->getOrgGatewayInfoByMsgType ( $msg_type );
		
		$valid = 0;
		if(strtolower($msg_type) != 'email') {
			if ($gateway != false && count ( $check_list ) < 1) {
				$valid = 1;
			}
		}
		else if($gateway != false) {
			$valid = 1;
		}
		
		$check_list [] = $gateway ? $gateway : _campaign("Bulk")." "._campaign( strtolower ( $msg_type ))." ". _campaign("gateway not configured");
		
		$check_list = implode ( ",", $check_list );
		
		$this->data ['group_info'] = $group_info;
		$this->data ['series_details'] = $series_details;
		$this->data ['check_list'] = array (
				"valid" => $valid,
				"list" => $check_list 
		);
		
		$this->data ['input_data'] = array (
				'days' => Util::dayofMonthOption (),
				'week' => Util::weekOption (),
				'month' => Util::monthOption (),
				'time_hrs' => Util::timeHours () 
		);
		$message_details = '';
		if ($message_id)
			$message_details = $C_campaign_controller->getDefaultValuesbyMessageId ( $message_id );
		
		$this->data ['msg_details'] = $message_details;
		
		$this->data ['org_credits'] = Util::valueOrDefault ( $C_campaign_controller->getBulkSmsCredit (), 10 );
		
		$this->addNotificationReceivers();
	}
	private function queueMessage() {
		$this->logger->debug ( '@@@RAW POST PARAMS :-' . print_r ( $_POST ['params'], true ) );
		$C_campaign_controller = new CampaignController ();
		
		$params = json_decode ( $_POST ['params'], true );
		$this->logger->debug ( '@@@Inside Queue message :' . json_last_error () );
		$params = $this->setSendParams ( $params );
		$this->logger->debug ( 'Params for Queue message :' .print_r($params,true));

		$params ['campaign_id'] = $_GET ['campaign_id'];
		$params ['msg_id'] = $_GET ['msg_id'];
		$params ['message'] = rawurldecode ( $params ['message'] );

		try {
			$msg_type = $_GET ['msg_type'];
		
			if ($msg_type == 'SMS') {
				$mobile = $params ['sender_from'];
				$regex = '/^[0-9a-zA-Z- ]{2,16}$/';
				if (! preg_match ( $regex, $mobile )) {
					throw new Exception ( _campaign("Invalid sender mobile number") );
				}
			}

			if ($msg_type == 'EMAIL')
				$message_type = BulkMessageTypes::$EMAIL;
			elseif ($msg_type == 'SMS')
				$message_type = BulkMessageTypes::$SMS;
			elseif ($msg_type == 'CALL_TASK')
				$message_type = BulkMessageTypes::$CALL_TASK;
			elseif ($msg_type == 'WECHAT')
				$message_type = BulkMessageTypes::$WECHAT;
			elseif ($msg_type == 'PUSH')
				$message_type = BulkMessageTypes::$MOBILEPUSH;
			$C_campaign_controller->queueMessage ( $params, $message_type, ValidationStep::$VALIDATE );
			
			$data = $C_campaign_controller->getProcessedParams ( $params );
			
			$params ['track'] = true;
			$params ['message'] = $C_campaign_controller->replacePhpTags ( $data ['message'] );
			$params ['subject'] = $C_campaign_controller->replacePhpTags ( rawurldecode ( $data ['subject'] ) );
			
			$campaign_id = $_GET['campaign_id'];
			if ($campaign_id){
				$plan_id = $C_campaign_controller->getRecoProductAttribPlanId( $campaign_id );
				if($plan_id){
					$default_template_options ['recommendation_plan_id'] = $plan_id;
					$details = $C_campaign_controller->getRecoProductAttribDetails ( $campaign_id, $plan_id );
					foreach( $details as $plan_detail ) {
					$default_template_options ['num_of_recommendations'] = $plan_detail['num_of_recommendations'];
					$default_template_options ['num_of_attributes'] = $plan_detail['num_of_attributes'];
					$this->logger->debug ('reco form tags : obtained details of 
							num_of_recommendations - '.print_r($plan_detail['num_of_recommendations'],true));
					$this->logger->debug ('reco form tags : obtained details of 
							num_of_attributes - '.print_r($plan_detail['num_of_attributes'], true));
					}
				}
			}
			/**
			 * add appID app secret (3 parameters) for wechat case
			 * ashish
			 */
			$default_template_options ['store_type'] = $params ['store_type'];
			if ($msg_type == 'EMAIL') {
				$default_template_options ['sender_label'] = urldecode ( $params ['sender_label'] );
				$default_template_options ['sender_email'] = urldecode ( $params ['sender_from'] );
				$default_template_options ['replyto_label'] = urldecode ( $params ['replyto_label'] );
				$default_template_options ['sender_reply_to'] = urldecode ( $params ['replyto_from'] );
				$default_template_options ['domain_gateway_from'] = urldecode ( $params ['domain_gateway_from'] );
				$default_template_options ['domain_gateway_map_id'] = urldecode ( $params ['domain_gateway_map_id'] );
				$default_template_options ['reachability_rules'] = urldecode($params ['rule_selected']) ;
			} elseif ($msg_type == 'SMS') {
				$default_template_options ['sender_gsm'] = $params ['sender_label'];
				$default_template_options ['sender_cdma'] = $params ['sender_from'];
				$default_template_options ['msg_count'] = $params ['msg_count'];
				$default_template_options ['reachability_rules'] = urldecode($params ['rule_selected']) ;
				$default_template_options ['sendToNdnc'] = urldecode( $params['sendToNdnc'] );

			} elseif ($msg_type=='WECHAT') {
				$default_template_options ['TemplateIds'] = $params ['singleImageTemplateIds'];
				$default_template_options ['AppId'] = $params ['AppId'];
				$default_template_options ['AppSecret'] = $params ['AppSecret'];
				$default_template_options ['ServiceAccoundId'] = $params ['ServiceAccoundId'] ;
				$default_template_options ['template_id'] = $params ['template_id'];
				$default_template_options ['summary'] = rawurldecode( $params ['summary'] );
				$default_template_options ['title'] = rawurldecode( $params ['title'] );
				$default_template_options ['name'] = rawurldecode( $params ['name'] );
				$default_template_options ['image'] = $params ['image'];
				$default_template_options ['OriginalId'] = $params['OriginalId'];
				$default_template_options ['msg_type'] = $params['msg_type'];
			} elseif ($msg_type=='PUSH') {
				$supported_channels_array = array();
				$template_data = json_decode(rawurldecode($params['message']),true);
				if(  isset($params['accountDetails']['android']) && ($params['accountDetails']['android']!=null) && (array_key_exists("ANDROID", $template_data['templateData'])) ){
					array_push($supported_channels_array, "android");
				}
				if(isset($params['accountDetails']['ios']) && ($params['accountDetails']['ios']!=null) && (array_key_exists("IOS", $template_data['templateData']))){
						//$supported_channels = $supported_channels.",ios";
					array_push($supported_channels_array, "ios");
				}
				$this->logger->debug("supported_channels array :".print_r($supported_channels,true));

				$supported_channels = implode(",", $supported_channels_array);
				$this->logger->debug("supported_channels csv string: ".$supported_channels);

				$default_template_options['is_list_processed_for_reachability'] = false;
				$default_template_options ['authToken'] = $params['accountDetails']['accessToken'];
				$default_template_options ['licenseCode'] = $params['accountDetails']['licenseCode'];
				$default_template_options ['variationId'] =  "";
				$default_template_options ['campaignId'] ="";
				$default_template_options ['supported_channels'] = $supported_channels;
				$default_template_options ['accountId'] = $params['accountDetails']["account_id"];
			} 
			
			if($params['is_drag_drop'] && $params['drag_drop_id']){
				$default_template_options ['is_drag_drop'] = 1;
				$default_template_options ['drag_drop_id'] = $params['drag_drop_id'];
			}else{
				$default_template_options ['is_drag_drop'] = 0;
				$default_template_options ['drag_drop_id'] = $params['drag_drop_id'];
			}
		 
		 	$this->logger->debug('@@@rule_selected:'.print_r($params ['rule_selected'],true));
			$this->setPointsParamsForStrategyAllocation( $params , $default_template_options );

			$params ['default_arguments'] = $default_template_options;
			$this->logger->debug ( '@@@DEFAULT:' . print_r ( $params, true ) );
			$C_message = $C_campaign_controller->queueMessage ( $params, $message_type, ValidationStep::$QUEUE );

			//adding entity tagging for sms and email
			$message_id = $C_message->getId();
			$metadata_mappings = array();
			$metadata_mappings['selected_incentive'] = $params['selected_incentive'];
			$metadata_mappings['voucher_series_id'] = $params['voucher_series_id'];
			$metadata_mappings['selected_generic'] = $params['selected_generic'];
			$metadata_mappings['inc_mapping_id'] = $params['inc_mapping_id'];
			$metadata_mappings['message_id'] = $message_id;
			$metadata_mappings['campaign_id'] = $campaign_id;
			$this->C_metadata_controller->addIncMappings($metadata_mappings);

			if( $msg_type == 'EMAIL' ) {

				list( $entity_type , $entity_id ) = 
					$C_campaign_controller->getEntityFromSenderDetail( 
						"EMAIL" , $default_template_options['sender_email'] );

				$C_campaign_controller->tagCampaignEntityByType(
					"OUTBOUND_MSG", $message_id, $entity_type, $entity_id );

				$C_campaign_controller->saveSecondaryTemplates($params,$message_id,"OUTBOUND","EMAIL" ) ;

			}else if ($msg_type == 'SMS' ){

				list( $entity_type , $entity_id ) = 
					$C_campaign_controller->getEntityFromSenderDetail( 
						"SMS" , $default_template_options['sender_gsm'] );

				$C_campaign_controller->tagCampaignEntityByType(
					"OUTBOUND_MSG" , $message_id, $entity_type, $entity_id );
			}

		} catch ( Exception $e ) {
			$this->data ['error'] = $e->getMessage ();
		}
	}
	public function setSendParams($params) {
		if ($params ['send_when'] == 'PARTICULAR_DATE') {
			$params ['date_field'] = date ( 'Y-m-d', strtotime ( $params ['date_time'] ) );
			$params ['hours'] = date ( 'H', strtotime ( $params ['date_time'] ) );
			$params ['minutes'] = date ( 'i', strtotime ( $params ['date_time'] ) );
		}		
		return $params;
	}
	private function generateSocialIcon($social_icon, $social_platform) {
		foreach ( $social_icon as $row ) {
			$link_mapping [$row ['id']] = $social_platform [$row ['id']];
		}
		$this->logger->debug ( '@@@Link Mapping:-' . print_r ( $link_mapping, true ) );
		
		$social = "<ul class='l-v-list'>";
		foreach ( $social_icon as $row ) {
			
			$a_href = $this->social_platform [$row ['id']];
			
			if ($row ['platform'] == 'Google+')
				$div_class = 'social-google-plus';
			else if ($row ['platform'] == 'Yelp!')
				$div_class = 'social-yelp';
			else
				$div_class = "social-" . Util::uglify ( $row ['platform'] );
			
			$social .= "<li class='social-media margin-top' id='" . $row ['id'] . "' 
							url='" . $link_mapping [$row ['id']] . "' img_url='" . rawurlencode ( $row ['logo_url'] ) . "'>
	  					<div class='social pull-left " . $div_class . "'></div>
	  					<div class='pull-left social-title'>" . Util::beautify ( $row ['platform'] ) . "</div>
	  				</li>";
		}
		$social .= "</ul>";
		
		return $social;
	}
	private function getGroupDetails($campaign_id) {
		$C_campaign_controller = new CampaignController ();
		
		$audience_data = $C_campaign_controller->getGroupDetailsForCampaignId ( $campaign_id );
		
		$this->logger->debug ( 'test audience data: ' . print_r ( $audience_data, true ) );
		foreach ( $audience_data as $val )
			$tmp [] = $val ['id'];
		array_multisort ( $tmp, SORT_ASC, $audience_data );
		
		$this->logger->debug ( 'test audience data: sorted ' . print_r ( $audience_data, true ) );
		
		$control_groups = $C_campaign_controller->getControlGroupsByCampaignID ( $campaign_id );
		
		$group_ids = array ();
		$audience_group_ids = array ();
		$sticky_array = array ();
		foreach ( $audience_data as $row ) {
			array_push ( $group_ids, $row ['params'] );
			array_push ( $audience_group_ids, $row ['id'] );
		}
		
		$all_group_details = $C_campaign_controller->getGroupDetailsbyGroupIds ( $group_ids );
		
		$all_groups = array ();
		foreach ( $all_group_details as $row ) {
			$all_groups [$row ['group_id']] = $row;
		}
		
		foreach ( $audience_data as $row ) {
			$group_id = $row ['params'];
			$group_details = $all_groups [$group_id];
			
			if ($group_id) {
				if (! isset ( $group_details ['group_id'] ))
					continue;
				$auto_generated_group = 'auto_gen_expiry_reminder_group_';
				if(strncmp($group_details['group_label'],$auto_generated_group,strlen($auto_generated_group))===0)
					continue;
				
				$peer_group_id = $group_details ['peer_group_id'];
				if ($peer_group_id)
					$control_group_details = $control_groups [$peer_group_id];
				
				$params_number = json_decode ( $group_details ['params'], true );
				
				if ($peer_group_id) {
					
					$count = $group_details ['total_clients'];
					$test_users = $group_details ['customer_count'];
					$control_users = ( int ) $control_group_details ['customer_count'];
					
					// If Both the test and controll user are zero then total client must be zero.
					if ($test_users == 0 && $control_users == 0)
						$count = 0;
					
					$sticky_array [$group_id] ['count'] = $count;
					$sticky_array [$group_id] ['test'] = $test_users;
					$sticky_array [$group_id] ['control'] = $control_users ? $control_users : 0;
				} else {
					$count = $group_details ['customer_count'];
					$sticky_array [$group_id] ['count'] = $count;
				}
				
				$sticky_array [$group_id] ['group_id'] = $group_details ['group_id'];
				$sticky_array [$group_id] ['group_label'] = $group_details ['group_label'];
				//$sticky_array [$group_id] ['group_label'] = 'abhinav';
				$sticky_array [$group_id] ['email'] = $params_number ['email'] ? $params_number ['email'] : 0;
				$sticky_array [$group_id] ['mobile'] = $params_number ['mobile'] ? $params_number ['mobile'] : 0;
				$sticky_array [$group_id] ['id'] = $group_details ['group_id'];
				$sticky_array [$group_id] ['campaign_id'] = $campaign_id;
				$sticky_array [$group_id] ['is_completed_mobile'] = 1;
				$sticky_array [$group_id] ['is_completed_email'] = 0;

			}
		}
		$channel = 'ALL';
		$reach_response_mode = $this->C_outbound_controller->getReachableCustomer($campaign_id,$channel);

		$this->logger->debug('@@@Group List CampaignMessagesAjaxService:-'.print_r( $reach_response_mode , true ));
		$this->logger->debug ( '@@@CAMPAIGN_LIST:- sticky' . print_r ( $sticky_array, true ) );
		//return $sticky_array;
		return $reach_response_mode;
	}
	private function processRecipientSMS() {
		$this->logger->debug ( '@@@Inside processRecipient SMS params' . print_r ( $_GET, true ) );
		
		$type = 'SMS_TEXT';
		if ($_GET ['call_task'])
			$type = 'CALL_TASK_TEXT';
		if ($_GET ['dvs_sms'])
			$type = 'DVS_SMS_TEXT';
		
		$is_expired = $this->C_campaign_controller->isCampaignExpired ( $_GET ['campaign_id'] );
		if (! $is_expired) {
			$C_creative_assets_manager = new CreativeAssetsManager ();
			
			$template = $C_creative_assets_manager->getTemplateByTag ( $type, 'TEXT', $this->org_id );
			
			$this->logger->debug ( '@@@TEMPLATE:-' . print_r ( $template, true ) );
			$this->data ['template'] = $template;
		} else
			$this->data ['error'] = $is_expired;
	}
	private function getSmsTemplateTag() {
		$this->logger->debug ( '@@@SMS Template tag' );
		global $js;
		
		$campaign_id = $_GET ['campaign_id'];
		$message_id = $_GET ['message_id'];
		$tempalte_id = $_GET ['template_id'];
		
		$C_creative_assets_manager = new CreativeAssetsManager ();
		$C_campaign_controller = new CampaignController ();
		
		if ($message_id)
			$default_data = $C_campaign_controller->getDefaultValuesbyMessageId ( $message_id );
		
		if ($tempalte_id)
			$info = $C_creative_assets_manager->getDetailsByTemplateId ( $_GET ['template_id'] );
		
		//pass program id to check for tag validation
		$params["program_id"] = $_GET['program_id'];
		$option = $C_campaign_controller->getSupportedTagsByType ( 'SMS', $campaign_id, false, $params );
		$C_campaign_controller->tagBuilderForSmsTag ( $option );
		$this->logger->debug ( '@@@Option:-' . $option );
		
		$this->data ['option_tag'] = $option;
		$this->data ['content'] = stripslashes ( ($info ['content']) ? $info ['content'] : $default_data ['message'] );
		
		$js->addTextBoxCounter ( 'sms_char_counter', 'sms_text_box' );
	}
	
	/*
	 * saving sender label and email address
	 */
	private function saveSenderLabel() {
		global $currentorg;
		$label = $_GET ['label'];
		$email = $_GET ['email'];
		
		$C_campaign_controller = new CampaignController ();
		$result = $C_campaign_controller->updateCustomSender ( $currentorg->org_id, $label, $email );
		if ($result)
			$this->data ['success'] = _campaign("Sender details updated successfully");
		else
			$this->data ['error'] = _campaign("Error updating sender details").'!';
	}
	
	// generating preview url for mobile preview
	private function generatePreviewUrl() {
		$this->logger->debug ( '@@@Inside Generating Preview Url' );
		$this->logger->debug ( '@@@POST DATA:' . print_r ( $_POST, true ) );
		try {
			
			$msg_id = $_POST ['message_id'];
			$type = $_POST ['type'];
			$actual_msg = rawurldecode ( $_POST ['post_msg'] );
			
			$params = array ();
			$params ['group_id'] = $_POST ["group_id"];
			$params ['campaign_id'] = $_POST ["campaign_id"];
			$params ["message"] = rawurldecode ( $_POST ['post_msg'] );
			$params ["subject"] = rawurldecode ( $_POST ["subject"] );
			
			// Segemnt info used only in case of Timeline Email Preview
			if (isset ( $_POST ['segment_name'] ) && isset ( $_POST ['segment_values'] )) {
				
				$params ['segments'] = array (
						'segment_name' => $_POST ['segment_name'],
						'segment_values' => $_POST ['segment_values'] 
				);
			}
			
			$message = $this->C_outbound_controller->getMessageDetailsForPreview ( $type, $params );
			
			if (strtoupper ( $type ) == "EMAIL") {
				
				$this->data ["msg_subject"] = rawurlencode ( $message ["subject"] );
				
				if (empty ( $msg_id ) && ! isset ( $_POST ["is_plain"] )) {
					$message ["msg"] = $this->getFTFAndViewBrowserHtml ( $message ["msg"] );
					
					$actual_msg = $this->getFTFAndViewBrowserHtml ( $actual_msg );
					$this->data ['actual_data'] = rawurlencode ( $actual_msg );
				}
				
				if (! isset ( $_POST ["is_plain"] )) {
					try {
						$C_creative_manager = new CreativeAssetsManager ();
						$params = json_decode ( $C_creative_manager->generatePreviewUrl ( $message ["msg"], true ), true );
						$this->data ['preview_url'] = $params ['file_http_url'];
					} catch ( Exception $e ) {
						$this->logger->error ( "Preview :" . $e->getMessage () );
						$this->data ['preview_url'] = "";
					}
				}
				$this->data ['msg_data'] = rawurlencode ( $message ["msg"] );
			} else {
				$this->data ['msg_data'] = rawurlencode ( $message ["msg"] );
			}
		} catch ( Exception $e ) {
			$this->logger->debug ( '@@@EXCEPTION:' . $e->getMessage () );
			$this->data ['error'] = $e->getMessage ();
		}
	}
	
	// getting call task customer list.
	private function getCallTaskRecipient() {
		$campaign_id = $_GET ['campaign_id'];
		$message_id = $_GET ['message_id'];
		$C_campaign_controller = new CampaignController ();
		
		if ($message_id) {
			$default_data = $C_campaign_controller->getDefaultValuesbyMessageId ( $message_id );
			$this->data ['message_data'] = $default_data;
		}
		
		$group_data = $C_campaign_controller->getLoyaltyGroupsByCampaignId ( $campaign_id );
		$groupd_ids = array ();
		foreach ( $group_data as $key => $value ) {
			$groupd_ids [] = $value;
		}
		$group_info = $C_campaign_controller->getGroupDetailsbyGroupIds ( $groupd_ids );
		$sticky_array = array ();
		$auto_generated_group = 'auto_gen_expiry_reminder_group_';
		foreach ( $group_info as $row ) {
			if(strncmp($row['group_label'],$auto_generated_group,strlen($auto_generated_group))===0)
				continue;
			$group_id = $row ['group_id'];
			$params_number = json_decode ( $row ['params'], true );
			
			$sticky_array [$group_id] ['group_id'] = $group_id;
			$sticky_array [$group_id] ['count'] = $row ['customer_count'];
			$sticky_array [$group_id] ['group_label'] = $row ['group_label'];
			$sticky_array [$group_id] ['email'] = $params_number ['email'] ? $params_number ['email'] : 0;
			$sticky_array [$group_id] ['mobile'] = $params_number ['mobile'] ? $params_number ['mobile'] : 0;
			$this->logger->debug ( '@@@GRoup:' . print_r ( $sticky_array, true ) );
		}
		
		$this->data ['item_data'] = array (
				'non_sticky' => $sticky_array,
				'sticky' => '' 
		);
	}
	/**
	 * processing call task template selection and getting tag data.
	 */
	private function processTemplateCallTask() {
		$C_call_task = new CallTask ();
		
		$default_data = '';
		$C_creative_assets_manager = new CreativeAssetsManager ();
		$C_campaign_controller = new CampaignController ();
		$message_id = $_GET ['message_id'];
		$template_id = $_GET ['template_id'];
		
		if ($message_id) {
			$task_id = $C_call_task->getStoreTaskByMessageID ( $_GET ['campaign_id'], $message_id );
			$C_call_task->load ( $task_id );
			$default_data = $C_call_task->getDefaultValuesById ();
			$this->logger->debug ( '@@@DATA:' . print_r ( $default_data, true ) );
			$this->data ['default_data'] = $default_data;
		}
		
		if ($template_id && $message_id)
			$info = $C_creative_assets_manager->getDetailsByTemplateId ( $template_id );
		
		if ($template_id && ! $message_id)
			$info = $C_creative_assets_manager->getDetailsByTemplateId ( $template_id );
		
		$tags = $C_campaign_controller->getSupportedTagsByType ( BulkMessageTypes::$CALL_TASK->toString () );
		$this->logger->debug ( '@@@Tags For Call Task' . print_r ( $tags, true ) );
		$tags = $this->getTagList ( $tags );
		
		$this->data ['tags'] = $tags;
		$this->data ['content'] = ($info ['content']) ? $info ['content'] : $default_data ['message'];
	}
	
	/**
	 * getting delivery instruction for call task handling.
	 */
	private function getDeliverySettingsForCallTask() {
		$group_ids = $_GET ['group_ids'];
		$campaign_id = $_GET ['campaign_id'];
		$message_id = $_GET ['msg_id'];
		
		$C_call_task = new CallTask ();
		$C_campaign_controller = new CampaignController ();
		
		if ($message_id) {
			$task_id = $C_call_task->getStoreTaskByMessageID ( $campaign_id, $message_id );
			$C_call_task->load ( $task_id );
			$this->data ['default_data'] = $C_call_task->getDefaultValuesById ();
			$message_details = $C_campaign_controller->getDefaultValuesbyMessageId ( $message_id );
			$this->logger->debug ( '@@@MSG_DETAILS:' . print_r ( $message_details, true ) );
		}
		
		$C_outbound_msg_send_when_provider = ApplicationFactory::getApplicationByCode ( ApplicationType::CAMPAIGN );
		
		$send_options = $C_outbound_msg_send_when_provider->getData ( CampaignServiceFeatureTypeImpl::$OUTBOUND_MESSAGES_SEND_WHEN );
		
		$C_campaign_controller->load ( $campaign_id );
		$details = $C_campaign_controller->getDetails ();
		
		$this->data ['msg_details'] = $message_details;
		
		$group_details = $this->C_campaign_controller->getGroupDetails ( $group_ids );
		$list = $this->C_outbound_controller->getListDetailsByGroupId ( $campaign_id, $group_ids );
		
		$sticky_array = array ();
		
		$params_number = json_decode ( $group_details ['params'], true );
		$sticky_array ['count'] = ($group_details ['customer_count']) ? $group_details ['customer_count'] : 0;
		$sticky_array ['test'] = 0;
		$sticky_array ['control'] = 0;
		$sticky_array ['group_label'] = $group_details ['group_label'];
		$sticky_array ['group_id'] = $group_details ['group_id'];
		$sticky_array ['email'] = $params_number ['email'] ? $params_number ['email'] : 0;
		$sticky_array ['mobile'] = $params_number ['mobile'] ? $params_number ['mobile'] : 0;
		$sticky_array ['name'] = $list;
		
		$this->data ['group_info'] = $sticky_array;
		
		$series_details = $C_campaign_controller->getVoucherSeriesDetailsByOrg ( $details ['voucher_series_id'] );
		$this->data ['series_details'] = $series_details;
		
		$this->data ['input_data'] = array (
				'days' => Util::dayofMonthOption (),
				'week' => Util::weekOption (),
				'month' => Util::monthOption (),
				'time_hrs' => Util::timeHours () 
		);
		$this->data ['allowed_status'] = CallTask::getAllStatuses ();
	}
	private function getCallTaskPreview() {
		global $currentuser;
		
		$group_id = $_GET ['group_id'];
		$campaign_id = $_GET ['campaign_id'];
		$msg = rawurldecode ( $_POST ['msg'] );
		$subject = rawurldecode ( $_POST ['subject'] );
		try {
			$C_campaign_controller = new CampaignController ();
			$data = $C_campaign_controller->getUsersPreviewTable ( $group_id, $campaign_id, $msg, $subject, 'CALL_TASK' );
			$this->data ['user_info'] = $data;
			
			$user = UserProfile::getById ( $data [0] ['user_id'] );
			if ($user)
				$this->data ['user_data'] = $user->getHash ();
			else
				$this->data ['user_data'] = $this->getCallTaskDefaultArgs ();
			
			$this->data ['user_data'] ['admin'] = $currentuser->getName ();
		} catch ( Exception $e ) {
			$this->data ['error'] = $e->getMessage ();
		}
	}
	private function getCallTaskDefaultArgs() {
		$hash ['firstname'] = 'Customer';
		$hash ['lastname'] = 'Customer';
		$hash ['mobile'] = 'N-A';
		$hash ['email'] = 'N-A';
		$hash ['external_id'] = 'N-A';
		$hash ['lifetime_points'] = 'N-A';
		$hash ['lifetime_purchases'] = 'N-A';
		$hash ['loyalty_points'] = 'N-A';
		$hash ['current_slab'] = 'N-A';
		$hash ['registered_on'] = 'N-A';
		$hash ['updated_on'] = 'N-A';
		
		return $hash;
	}
	private function getSubjectList() {
		$this->logger->debug ( '@@@PARAMS:' . print_r ( $this->params, true ) );
		$params = rawurldecode ( $this->params );
		$C_campaign_controller = new CampaignController ();
		$subject = $C_campaign_controller->getSubjectListByOrg ( $params );
		$this->createAutoCompleteArray ( $subject, 'subject', 'subject' );
	}
	private function checkSessionExpiry() {
		// checking if session is expired or not;
		$this->data ['check'] = _campaign("Checking if session is expire or not");
	}
	private function getFTFAndViewBrowserHtml($html_content) {
		global $campaign_cfg;
		
		$view_url = "{{domain}}/business_controller/campaigns/emails/links/view.php?utrack={{user_id_b64}}&mtrack={{outbox_id_b64}}";
		
		$view_url = Util::templateReplace ( $view_url, array (
				'domain' => $campaign_cfg ['track-url-prefix'] ['view-in-browser'] 
		) );
		
		// check if forward to friend is enabled or not
		$is_ftf_enabled = $this->C_config_mgr->getKey ( "CONF_CAMPAIGN_FTF_ENABLED" );
		
		//Disabling Forward to Friend for now for some security reasons
		$is_ftf_enabled = false;

		if ($is_ftf_enabled) {
			$frwd_token_key = base64_encode ( base64_encode ( '{{frwd_tok}}___ftf_name' ) );
			$ftf_url = "{{domain}}/business_controller/campaigns/emails/links/view.php?utrack={{user_id_b64}}&mtrack={{outbox_id_b64}}&ftf=true&frwd_tok=$frwd_token_key";
			$ftf_url = Util::templateReplace ( $ftf_url, array (
					'domain' => $campaign_cfg ['track-url-prefix'] ['view-in-browser'] 
			) );
		}
		
		$is_view_enabled = $this->C_config_mgr->getKey ( "CONF_CAMPAIGN_VIEW_IN_BROWSER_ENABLED" );
		
		if ($is_view_enabled) {
			
			$view_in_browser_msg = '<center>
										'._campaign("If you have difficulties viewing this mail, click").'
										<a href="' . $view_url . '" style = "text-decoration: underline;color: #369;" target="_blank">'._campaign("here").'</a><br/>
									</center>';
		}
		
		if ($is_ftf_enabled) {
			$ftf_in_browser_msg = '<center><a href="' . $ftf_url . '" style = "text-decoration: underline;color: green;" target="_blank">'._campaign("Forward to a Friend").'!</a></center>';
		}
		
		// Create a DOM object
		$html_obj = new simple_html_dom ();
		$html_obj->load ( $html_content );
		foreach ( $html_obj->find ( 'body' ) as $element ) {
			$element->innertext = $view_in_browser_msg . $element->innertext . $ftf_in_browser_msg;
		}
		$html_content = $html_obj->save ();
		
		return $html_content;
	}
	private function addRequiredLinks() {
		$html = rawurldecode ( $_POST ['post_html'] );
		$message_id = $_POST ['message_id'];
		if (empty ( $message_id )) {
			$this->logger->debug ( 'adding ftf and view' );
			$this->data ['msg_data'] = rawurlencode ( $this->getFTFAndViewBrowserHtml ( $html ) );
		}
	}
	private function validateMsg() {
		try {
			$campaign_id = $_POST ['campaign_id'];
			if(isset($_POST ['call_message'])){
				$msg_id = $_POST['msg_id'];
				$message = rawurldecode($_POST['call_message']);
				$subject = rawurldecode($_POST['subject']);
				$description = rawurldecode($_POST['description']);
				$C_type = BulkMessageTypes::$CALL_TASK;
				$tags_params = array(
					'campaign_id' => $campaign_id,
					'msg_id' => $msg_id,
					'subject' => $subject,
					'message' => $message,
					'description' => $description
					);
			}
			else{
				if (isset ( $_POST ['sms_content'] )) {
					$content = rawurldecode ( $_POST ['sms_content'] );
					$C_type = BulkMessageTypes::$SMS;
				} else {
					$content = rawurldecode ( $_POST ['body'] );
					$C_type = BulkMessageTypes::$EMAIL;
				}
				
				$subject = rawurldecode ( $_POST ['subject'] );
				
				$this->logger->debug ( 'validating msg, camp: ' . $campaign_id . ' subject: ' . $subject );
				
				$tags_params = array (
						'campaign_id' => $campaign_id,
						'message' => $content,
						'subject' => $subject 
				);
			}
			
			$this->C_campaign_controller->validateTags ( $tags_params, $C_type, ValidationStep::$VALIDATE_MSG );
		} catch ( Exception $e ) {
			
			$this->data ['status'] = 'error';
			$this->data ['error_msg'] = $e->getMessage ();
		}
	}

	private function validateAllMsg(){
         try {
            $campaign_id = $_POST ['campaign_id'];
            $all_templates = rawurldecode($_POST['all_templates']);
            $this->logger->debug ('all_templates: '. $all_templates );
            $obj = json_decode($all_templates,true);
            $this->logger->debug ('arr'.json_last_error().'array123:'.print_r($obj,true));
            foreach($obj as $key => $value){
                    $content = $value['value'];
                    $this->logger->debug('contentMulti'.$content);
                    $C_type = BulkMessageTypes::$EMAIL;

            $subject = rawurldecode ( $_POST ['subject'] );

            $this->logger->debug ( 'validating msg, camp: ' . $campaign_id . ' subject: ' . $subject );

            $tags_params = array (
                        'campaign_id' => $campaign_id,
                        'message' => $content,
                        'subject' => $subject
                );

            $temp = $value['key'];
            $this->logger->debug('keylang'.$temp);
            $this->C_campaign_controller->validateTags ( $tags_params, $C_type, ValidationStep::$VALIDATE_MSG );
            }
        } catch ( Exception $e ) {

            $this->data ['status'] = 'error';
            $this->data ['error_msg'] = $e->getMessage ();
            $this->data ['key'] = $temp;
        }
    }

	private function processReminderEmailTemplate(){
		global $currentorg;
		$campaign_id = $_GET ['campaign_id'];
		$voucher_series_id = $_GET ['voucher_series_id'];
		$template_id = $_GET ['template_id'];
		
		$C_campaign_controller = new CampaignController ();
		$C_coupon_controller = new CouponSeriesManager();
		$C_creative_assets_manager = new CreativeAssetsManager ();
		
		if ($template_id){
			try {
				$this->logger->debug ( 'template_id ' . $template_id );
				$content = $C_creative_assets_manager->getDetailsByTemplateId ( $template_id );
			}catch ( Exception $e ) {
				$this->data ['error'] = $e->getMessage ();
			}
		}
		
		$this->data ['info'] = stripcslashes ( $content ['content'] );	

		$custom_tag = $C_campaign_controller->getSupportedTagsByType ( 'EMAIL', false, true);
		$this->data ['custom_tag'] = $this->getTagList ( $custom_tag );
		
		$image_list = $C_creative_assets_manager->getAllOrgCouponTemplates ( $currentorg->org_id, $voucher_series_id, 'IMAGE' );
		$html_list = $C_creative_assets_manager->getTemplateByChannelsPreview ( $currentorg->org_id, $voucher_series_id, 'HTML', 'EMAIL' );
		
		$this->data ['image_list'] = $image_list;
		$this->data ['html_list'] = $html_list;
		
		$survey_list = $C_campaign_controller->getSurveyFormsByOutboundCampaignId ( $campaign_id );
		$this->data ['survey_list'] = $survey_list;
		
		$social_icon = $C_campaign_controller->getSupportedSocial ();
		
		$social_platform = $C_campaign_controller->getSupportedSocialPlatform ();
		
		$image_list = $C_creative_assets_manager->getAllOrgCouponTemplates ( $this->org_id, $voucher_series_id, 'IMAGE' );
		$this->data ['social'] = $this->generateSocialIcon ( $social_icon, $social_platform );
		
		$social_platform = $C_campaign_controller->getSupportedSocialPlatform ();
		
		
		$sender_details = $C_campaign_controller->getCustomSenderDetails ( $currentorg->org_id );
		
		$sender_details ["sender_label"] = Util::valueOrDefault ( $sender_details ["sender_label"], "N/A" );
		$sender_details ["sender_email"] = Util::valueOrDefault ( $sender_details ["sender_email"], "N/A" );
		$sender_details ["sender_gsm"] = Util::valueOrDefault ( $sender_details ["sender_gsm"], "N/A" );
		$sender_details ["sender_cdma"] = Util::valueOrDefault ( $sender_details ["sender_cdma"], "N/A" );
		
		$this->data ['custom_sender'] = $sender_details;
		
		
		$html_list = $C_creative_assets_manager->getTemplateByChannelsPreview ( $this->org_id, $voucher_series_id, 'HTML', 'EMAIL' );
	}
	
	private function processReminderSmsTemplate(){
		
		global $js,$currentorg;
		
		$campaign_id = $_GET ['campaign_id'];
		$voucher_series_id = $_GET ['voucher_series_id'];
		$tempalte_id = $_GET ['template_id'];
		$this->logger->debug('campaign_id '.$campaign_id.' voucher_series_id: '.$voucher_series_id. ' template_id '.$template_id);
		$C_creative_assets_manager = new CreativeAssetsManager ();
		$C_campaign_controller = new CampaignController ();
		$C_coupon_controller = new CouponSeriesManager();
		
		if ($tempalte_id)
			$info = $C_creative_assets_manager->getDetailsByTemplateId ( $_GET ['template_id'] );
		
		$option = $C_campaign_controller->getSupportedTagsByType ( 'SMS', false, true);
		$C_campaign_controller->tagBuilderForSmsTag ( $option );
		$this->logger->debug ( '@@@Option:-' . $option );
		
		$this->data ['option_tag'] = $option;
		$this->data ['content'] = stripslashes ($info ['content'] );
		
		$sender_details = $C_campaign_controller->getCustomSenderDetails ( $currentorg->org_id );
		
		$sender_details ["sender_label"] = Util::valueOrDefault ( $sender_details ["sender_label"], "N/A" );
		$sender_details ["sender_email"] = Util::valueOrDefault ( $sender_details ["sender_email"], "N/A" );
		$sender_details ["sender_gsm"] = Util::valueOrDefault ( $sender_details ["sender_gsm"], "N/A" );
		$sender_details ["sender_cdma"] = Util::valueOrDefault ( $sender_details ["sender_cdma"], "N/A" );
		
		$this->data ['custom_sender'] = $sender_details;
		
		$js->addTextBoxCounter ( 'sms_char_counter', 'sms_text_box' );
	}
	
	function processPlainReminder(){
		}

	//It is checking if any allocation or expiry strategy attached with this massage or not
	// based on that it will set the default parameters like "points":{"allocation_strategy_id":0,"expiry_strategy_id":0,"program_id":0,"till_id":0,"promotion_id":0}
	private function setPointsParamsForStrategyAllocation( $params , &$default_template_options ){

		if( $params["program_id"] ){
			$default_template_options["program_id"] = $params["program_id"];
			$default_template_options["allocation_strategy_id"] = $params["allocation_strategy_id"];
			$default_template_options["expiry_strategy_id"] = $params["expiry_strategy_id"];
			$default_template_options["till_id"] = $params["till_id"];
			$default_template_options["promotion_id"] = $params["promotion_id"];			
		}
	}

	// get initial data for creative assets
	private function getMessageCreativeData(){
		
		$this->logger->debug ( '@@@initialMessageCreativeData' );
		global $currentorg;
		$campaign_id = $_GET ['campaign_id'];
		$message_id = $_GET ['msg_id'];
		
		$is_expired = $this->C_campaign_controller->isCampaignExpired ( $campaign_id );

		if($is_expired){
			$this->data ['error'] = $is_expired;
			return ;
		}
		$C_campaign_controller = new CampaignController ();
		$C_creative_assets_manager = new CreativeAssetsManager ();
		
		if ($message_id){
			$default_val = $C_campaign_controller->getDefaultValuesbyMessageId ( $message_id );
			$this->data ['info'] = $default_val;
		}
		
		$C_campaign_controller->load ( $campaign_id );
		$series_id = $C_campaign_controller->campaign_model_extension->getVoucherSeriesId ();
		if (! $series_id) {
			$series_id = - 1;
		}
		
		$C_creative_assets_manager = new CreativeAssetsManager ();
		
		$sender_details = $C_campaign_controller->getSenderDetails ( $currentorg->org_id );
		
		$this->data['sender_details'] = $sender_details;
		
		//pass program id to check for tag validation
		if($_GET['program_id'] > 0)
			$params["program_id"] = $_GET['program_id'];
		$custom_tag = $C_campaign_controller->getSupportedTagsByType ( 'EMAIL', $campaign_id, false , $params);
		$this->data ['custom_tag'] = $custom_tag;
		

		$image_list = $C_creative_assets_manager->getAllOrgCouponTemplates ( $currentorg->org_id, $series_id, 'IMAGE' );
		$html_list = $C_creative_assets_manager->getTemplateByChannelsPreview ( $currentorg->org_id, $series_id, 'HTML', 'EMAIL' );
		
		$this->data ['image_list'] = $image_list;
		$this->data ['html_list'] = $html_list;
		
		$survey_list = $C_campaign_controller->getSurveyFormsByOutboundCampaignId ( $campaign_id );
		$this->data ['survey_list'] = $survey_list;
		
		$social_icon = $C_campaign_controller->getSupportedSocial ();
		$social_platform = $C_campaign_controller->getSupportedSocialPlatform ();
		$image_list = $C_creative_assets_manager->getAllOrgCouponTemplates ( $this->org_id, $series_id, 'IMAGE' );
		$this->data ['social'] = $this->generateSocialIcon ( $social_icon, $social_platform );
		include_once 'creative_assets/EdmManager.php';
		$C_edm_manager = new EdmManager();
		$edm_user_id = $C_edm_manager->getEdmUserId();
		$this->data['edm_user_id'] = $edm_user_id;

		include_once 'business_controller/OrganizationController.php' ;
		$org_controller = new OrganizationController() ;
		$this->data['base_language'] = $org_controller->getDefaultLanguageId() ;
		$this->data['language'] = $org_controller->getOrgLanguages() ;

	}
	private function getSecondaryTemplates(){
 		$this->logger->debug("inside getSecondaryTemplates") ;
 		//$this->C_assets = new CreativeAssetsManager();
 		$this->$C_creative_assets_manager = new CreativeAssetsManager ();		
 		$parent_template_id = $_GET['parent_template_id'] ;
 		$scope = "ORG" ;
		$this->logger->debug("abhinavabc".$scope);
 		$this->data['templates'] = $this->C_creative_assets_manager->getTemplateByParentId($this->org_id , $parent_template_id , $scope ) ;
 	}

 	private function removeSecondaryTemplate(){
 		$this->logger->debug("inside removeSecondaryTemplates") ;
 		$params = $_POST;
 		$this->logger->debug("params passed are : ".print_r($params,true)) ;
 		$message_id = $params['message_id'] ;
 		$this->C_campaign_controller->removeSecondaryTemplate($params , $message_id , 'OUTBOUND' , 'EMAIL') ;
 	}
 	
 	private function addNotificationReceivers(){
 		include_once 'business_controller/health_dashboard/HealthDashboardController.php';
 		$C_health = new HealthDashboardController("health");
 		$receiver_group_ids = $C_health->getReceiverGroupForChannel($this->org_id, "SMS"); // sms and email are same
 		$notification_receivers = array();
 		foreach ($receiver_group_ids as $row){
 			$receiver_group_id = $row['id'];
 			$receivers = $C_health->getOrgNotifiersReceiverGroupId($receiver_group_id, $this->org_id);
 			
 			if($row['recipient_type'] == "BRAND_POC"){
 				$users = $C_health->getAdminUserAttribute($this->org_id, "first_name", $receivers);
 				$notification_receivers['Brand POC'] = implode(",", $users);
 			}else {
 				$users = $C_health->getAdminUserAttribute(0, "first_name", $receivers);
 				$notification_receivers['CAP POC'] = implode(",", $users);
 			}
 		}
 		$this->data["notification_receivers"] = $notification_receivers;
 	}

 	private function getMobilePushData(){
 		$params = $_GET ; 		
 		$this->logger->debug("params passed are : ".print_r($params,true)) ;
		$C_campaign_controller = new CampaignController ();
		$default_data = array() ;
		if ($message_id){
			$default_data = $C_campaign_controller->getDefaultValuesbyMessageId ( $params['message_id'] ) ;			
		}

		if(count($default_data) == 0){
			$this->data['error'] = "no template found, please select a template" ;
			return ;
		}
		if($default_data['type'] != 'MOBILEPUSH'){
			$this->data['error'] = "template type is not valid" ;	
			return ;
		}
		$this->data['message_data'] = $default_data['message'] ;
		$this->logger->debug("message data : ".print_r($this->data, true)) ;	 		
 	}
}
?>
