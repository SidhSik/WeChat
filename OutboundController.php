<?php
include_once 'business_controller/campaigns/message/api/BulkMessageTypes.php';
include_once 'business_controller/campaigns/message/impl/BulkMessageSenderFactory.php';
include_once 'thrift/conquestscheduleservice.php';
include_once 'business_controller/campaigns/CheckListController.php';
include_once 'helper/coupons/CouponProductManager.php';
include_once 'helper/coupons/CouponManager.php';
include_once 'creative_assets/model/class.Template.php';
include_once 'luci-php-sdk/LuciClient.php' ;

/**
 * Outbound Campaign Controller
 * @author nayan
 */
class OutboundController extends CampaignController{

	private $client;
	private $C_coupon_series;
	private $C_org_controller;
	
	public function __construct(){
		parent::__construct();
		$this->logger->debug("Inside OutboundController Contructor");
		
		$this->C_coupon_series = new CouponSeriesManager();
		$this->C_org_controller = new OrganizationController();
		$this->client = new ConquestScheduleThriftService();
	}
	
	public function getOverviewDetailsByCampaignId( $campaign_id ){
		
		$this->load( $campaign_id );
		$campaign_info = $this->getDetails();
		
		if( $campaign_info['type'] == 'outbound' )
			$voucher_series_id = $campaign_info['voucher_series_id'];
		else
			$voucher_series_id = array_values( json_decode( $campaign_info['voucher_series_id'], true ) );
		
		$campaign_info["no_coupon_series"] = 0;
		if( $voucher_series_id > 0 ){
			$no_of_coupon_series = count( $voucher_series_id );
			$campaign_info["no_coupon_series"] = $no_of_coupon_series;
		}
		
		$campaign_info["no_coupon_series"] = $no_of_coupon_series;
		$coupon_series_details = $this->
			getVoucherSeriesDetailsByVoucherId( $voucher_series_id , 'from_voucher_table');
		
		$campaign_info["coupon_series_desc"] = 
			( !$coupon_series_details[0]['description'] ) ? 'N/A' :
				$coupon_series_details[0]['description'];
		
		$campaign_info["num_of_coupon_issued"] = 
			( $coupon_series_details[0]['NUM_OF_ISSUED'] <= 0 ) ? '0' :
				$coupon_series_details[0]['NUM_OF_ISSUED'];
		
		$campaign_info["num_of_coupon_redeemed"] = 
			( $coupon_series_details[0]['NUM_OF_REDEEMED'] <= 0) ? '0' :
				$coupon_series_details[0]['NUM_OF_REDEEMED'];
		
		$campaign_info["display_start_date"] = I18nUtil::convertDateToLocale($campaign_info["start_date"], IntlDateFormatter::LONG, IntlDateFormatter::NONE);
		$campaign_info["display_end_date"] = I18nUtil::convertDateToLocale($campaign_info["end_date"], IntlDateFormatter::LONG, IntlDateFormatter::NONE);
		$campaign_info["created"] = date( "d M Y" , strtotime( $campaign_info["created"] ) );
		$campaign_info["modified"] = date( "d M Y" , strtotime( $campaign_info["modified"] ) );
		$campaign_info["org_id"] = $this->org_id;
		
		$is_expired = $this->isCampaignExpired( $campaign_id );
		
		$campaign_info["is_expired"] = 0;
		if( !empty($is_expired) )
			$campaign_info["is_expired"] = 1;
		
		$groups = GroupDetailModel::getAllByCampaignId( $campaign_id, $this->org_id );
		
		$campaign_info["total_emails"] = 0;
		$campaign_info["total_mobiles"] = 0;
		
		//getting the customer groups for all campaign ids
		$count_groups = 0;
		$auto_generated_group = 'auto_gen_expiry_reminder_group_';
		foreach( $groups as $group_details ){
			if(strncmp($group_details['group_label'],$auto_generated_group,strlen($auto_generated_group))===0)
				continue;
			$count_groups++;
			$params = json_decode( $group_details["params"] , true );
			$campaign_info["total_emails"] += $params["email"]; 
			$campaign_info["total_mobiles"] += $params["mobile"];
		}
		$campaign_info["no_of_audience_list"] = $count_groups;
		
		$this->getMessagesOverview( $campaign_id , $campaign_info );

		//Coupon Url
		$c_url = new UrlCreator();
		$c_url->setNameSpace( 'campaign/v2/coupons' );
		$c_url->setPage( 'CreateOutBoundCoupons' );
		$c_url->setPageParams( array('campaign_id' => $campaign_id) );
		$campaign_info["view_coupon_series"] = $c_url->generateUrl(false);
		
		//customer list url
		$cust_url = new UrlCreator();
		$cust_url->setNameSpace( 'campaign/audience' );
		$cust_url->setPage( 'Home' );
		$cust_url->setPageParams( array('campaign_id' => $campaign_id) );
		$campaign_info["view_audience_list"] = $cust_url->generateUrl(false);
		
		//Reports url
		$r_url = new UrlCreator();
		$r_url->setNameSpace( 'campaign/roi' );
		$r_url->setPage( 'Home' );
		$r_url->setPageParams( array('campaign_id' => $campaign_id) );
		$campaign_info["view_reports"] = $r_url->generateUrl(false);
		
		//View Page Url
		$url = new UrlCreator();
		$url->setNameSpace('campaign');
		$url->setPage( 'Index' );
		$url->setPageParams( array('type' => $campaign_info["type"]) );
		$campaign_info["back_url"] = $url->generateUrl(false);
		
		$info_count = 0;
		$C_checklist_controller = new CheckListController();
		$str = $C_checklist_controller->prepareHtml( $campaign_id );
		$info_count = $C_checklist_controller->getAttentionCount();
		
		//conquest schedule report
		$metadata = $this->getCampaignScheduleMetadata( $this->org_id );
		if( !empty($metadata) && $metadata->isSchedulingEnabled ){

			$res = $this->getCampaignScheduleData( $this->org_id, $campaign_id );
			$this->logger->debug( 'res for campaign data '.print_r($res, true) );
			$is_expired = $this->isCampaignExpired( $campaign_id );
			
			if( $is_expired ){
				//org level scheduling enabled & campaign expired 
				if( $res->isActive )
					$str .= '<li><input type="checkbox" name="report_sc" class="c-report-sc" 
								checked disabled>'._campaign("Send automated reports").'</li>';
				else
					$str .= '<li><input type="checkbox" name="report_sc" class="c-report-sc" 
								disabled>'._campaign("Send automated reports").'</li>';
			}else{
				//org level scheduling enabled & active campaign
				if( $res->isActive ){
					$str .= '<li><input type="checkbox" name="report_sc" class="c-report-sc" 
								checked disabled>'._campaign("Send automated reports").'<br>';
				
					if( $campaign_info['total_messages'] == 0 )
						$str .= '<span class="report-trouble">'._campaign('Reports scheduled').'</span>
									<span class="disable_report">'._campaign("Disable").'</span></li>';
							
				}else{
					$str .= '<li><input type="checkbox" name="report_sc" 
								class="c-report-sc" disabled>'._campaign('Send automated reports').'<br>
								<span><i class="report-trouble icon-exclamation-sign 
									e-quickinfo"></i>'._campaign("Reports not scheduled").'</span>
								<span class="retry_auto">'._campaign("Schedule now").'</span>
							</li>';
					$info_count += 1;
				}
			}
		}else{
			//org level scheduling disabled
			$str .= '<li><i class="icon-exclamation-sign e-quickinfo"></i>
						'._campaign("Automated reports are disabled for the Organization").'</li>';
			$info_count += 1;
		}
		
		$campaign_info['quick_list'] = $str;
		$info = '<span> '._campaign("Quick Info").'</span>';
		if( $info_count > 0 )
			$info .= '<span> ('.$info_count.') </span>';
		$campaign_info['quick_info'] = $info;
		$campaign_info['quick_count'] = $info_count;
		
		return $campaign_info;
	}
	public function getWeChatAccounts( $org_id) {
		return $this->campaign_model_extension->fetchWeChatAccounts($org_id);
	}

	public function getMobilePushAccounts() {
 		
		include_once 'business_controller/ChannelController.php';
 		$channelController = new ChannelController();

 		$channel_id = $channelController->getAccountIdByName('PUSH');

 		$this->logger->debug("Inside getMobilePushAccounts in OutboundController. The channel_id is : ".print_r($channel_id,true)) ;

 		$mobile_push_accounts = $channelController->getAccounts($channel_id[0]['id']);
 		$this->logger->debug("The mobile push accounts are".print_r($mobile_push_accounts,true)) ;

 		return $mobile_push_accounts;

	}
	public function getMessagesOverview( $campaign_id , &$campaign_info ){
		
		$queue_messages = $this->campaign_model_extension->
			getCampaignsMessageDetailsByCampaignId( 
					$this->org_id , $campaign_id );
		
		include_once 'business_controller/campaigns/library/VenenoDataDetailsHandler.php';
		$delivery_details = 
			VenenoDataDetailsHandler::getMessageDeliveryRate( $campaign_id , $this->org_id );
		
		$this->logger->debug( "@Delivery: ".print_r( $delivery_details , true ) );
		
		$delivery_sms_details = 
			VenenoDataDetailsHandler::getSMSSentCount( $campaign_id , $this->org_id );
		
		$this->logger->debug( "@Delivery SMS: ".print_r( $delivery_sms_details , true ) );

		$sent_count = 0;
		if($queue_messages==null)
			$total_count=0;
		else
			$total_count = count( $queue_messages );

		$points_count = 0;
		$messages = array();
		if( !empty( $queue_messages ) ){
			
			foreach ( $queue_messages as $msg_key => $msg_value ){
				$message = array();
				list( $group_label, $group_tooltip ) = $this->fetchGroupLabel( $msg_value );
				$message["queue_id"] = $msg_value["id"];
				$message["group_label"] = $group_label;
				$message["group_tooltip"] = $group_tooltip;
				$message["msg_type"] = $msg_value['type'];
				$message["campaign_id"] = $campaign_id;

				if( $msg_value['type'] == 'CUSTOMER_TASK' ){
					$message["msg_display_type"] = _campaign("Customer Task");
					$this->constructCustomerTaskDetails( $msg_value , $message );
				}else if( $msg_value['type'] == 'CALL_TASK' || $msg_value['type'] == 'CALL_TASK_REMINDER' ){
					$message["msg_display_type"] = _campaign("Call Task");
					$this->constructCallTaskDetails( $msg_value , $message );
				}else if( $msg_value['type'] == 'EMAIL' || $msg_value['type'] == 'EMAIL_REMINDER' || $msg_value['type']=='EMAIL_EXPIRY_REMINDER'){
					$message["msg_display_type"] = _campaign("Email");
					$this->constructEmailMessageDetails( $msg_value , $message , $delivery_details );
				}else if( $msg_value['type'] == 'SMS' || $msg_value['type'] == 'SMS_REMINDER' || $msg_value['type']=='SMS_EXPIRY_REMINDER'){
					$message["msg_display_type"] = _campaign("SMS");
					$this->constructTextMessageDetails( $msg_value , $message , $delivery_details );
				} else if( $msg_value['type'] == 'WECHAT' || $msg_value['type'] == 'WECHAT_REMINDER') {
					$message["msg_display_type"] = _campaign("WeChat");
					$this->constructWeChatMessageDetails($msg_value, $message ,$delivery_details);
				}  else if( $msg_value['type'] == 'MOBILEPUSH' || $msg_value['type'] == 'MOBILEPUSH_REMINDER' ){
					$message["msg_display_type"] = _campaign("MOBILE PUSH");
					$this->constructTextMessageDetails( $msg_value , $message , $delivery_details );
				}

				
				$this->constuctActionMenuForMessage( $msg_value , $message , $campaign_id );
				
				//increment the sent message count when its approved and status is sent
				//it will not include recurring messages in this count 
				if( ( $msg_value["approved"] == 1 ) 
						&&
					( $msg_value["status"] == "SENT" ) ) 
					$sent_count++;
				
				$message['approve'] = $msg_value["approved"];
				if( $message['approve'] == 1 ){
					$message['approved_by'] = $msg_value["Approved_by"];
				}

				/*if( strlen( $message['message'] ) > 60 ){
					$message['message'] = substr( $message['message'], 0,60 ).'...';
				}*/

				//checking if points strategy is attached to message or not				

				$df_params = json_decode( $msg_value['default_arguments'] , true );
			
				if( $df_params["program_id"] > 0 ){

					$points_count++;
				}

				array_push( $messages , $message );
			}
		}
		$_SESSION["total_messages"] = $total_count;
		$_SESSION["sent_messages"] = $sent_count;
		
		$campaign_info["messages"] = $messages;
		$campaign_info["total_messages"] = $total_count;
		$campaign_info["sent_messages"] = $sent_count;
		$campaign_info["no_points_strategy_attached"] = $points_count;
		$campaign_info["total_sent_email_count"] = $delivery_details["total_sent_email_count"];
		$campaign_info["open_count"] = $delivery_details["open_count"];
		$campaign_info["total_sent_sms_count"] = $delivery_sms_details["total_count"];
		$campaign_info["sent_count"] = $delivery_sms_details["sent_count"];
	}
	
	private function fetchGroupLabel( $value ){
	
		if( strlen( $value['group_label'] ) > 18 ){
			$group_label = substr( $value['group_label'], 0,18 ).'...';
			$tooltip = $value['group_label'];
		}else{
			$group_label = $value['group_label'];
			$tooltip = $value['group_label'];
		}
	
		return array( $group_label, $tooltip );
	}
	
	/**
	 * It will extract the details from message queue data and contruct the json accrodingly
	 * for customer task
	 * @param array $msg_data 
	 */
	private function constructCustomerTaskDetails( $msg_data , &$message ){
		
		$params = json_decode( $msg_data['params'] , true );
		
		$message["subject"] = strtoupper( $params['store_task_action_type'] );
		$message["task_title"] = $params['store_task_display_title'];
		$message["task_display_text"] = $params['store_task_display_title'];
		$message["start_date"] = Util::convertTimeToCurrentOrgTimeZone(date(  "d M Y, H:i", strtotime( trim($params['store_task_start_date']) ) ))." hrs";
		$message["completion_in_days"] = $params['store_task_completion_in_days'];
		$message["created_by"] = $msg_data['Created_by'];
		$message["store_task_id"] = $params['store_task_id'];
		
		$now = date( 'Y-m-d' );
		$start_date = $params['store_task_start_date'];
		$expiry_date = Util::getDateByDays( false, $params['store_task_completion_in_days'], $params['store_task_start_date'] );
		
		if( $start_date > $now )
			$message["status"] = "<font style='color:red' >"._campaign("WAITING")."</font>";
		else if( $now > $expiry_date  )
			$message["status"] = "<font style='color:green' >"._campaign("EXECUTED")."</font>";
		else
			$message["status"] = "<font style='color:black' >"._campaign("PROCESSING")."</font>";
	}
	
	/**
	 * It will extract the details from message queue data and contruct the json accrodingly
	 * for call task 
	 * @param array $msg_data
	 */
	private function constructCallTaskDetails( $msg_data , &$message ){
		
		$params = json_decode( $msg_data['params'] , true );
		
		$message["created_by"] = $msg_data['Created_by'];
		$message["message"] = $params["subject"];
		
		$this->statusDetailsForMessage( $msg_data , $message );
	}
	
	/**
	 * It will extract the details from message queue data and contruct the json accrodingly
	 * for email message
	 * @param array $msg_data
	 */
	private function constructEmailMessageDetails( $msg_data , &$message , $delivery_details ){
		
		$params = json_decode( $msg_data['params'] , true );
		
		$message["created_by"] = $msg_data['Created_by'];
		$message["message"] =  _campaign("Subject:")." ".$params["subject"];
		$message["preview_message"] =  _campaign("Subject:")." ".$params["subject"];
		
		$this->statusDetailsForMessage( $msg_data , $message );
		
		if( isset($delivery_details["delivery_rate"][$msg_data["guid"]]) ){
			$message["delivery_rate"] = $delivery_details["delivery_rate"][$msg_data["guid"]]."%";
		}

		if( isset($delivery_details["open_click_rate"][$msg_data["guid"]]) ){
			$message["recipients"] = $delivery_details["open_click_rate"][$msg_data["guid"]]["overall_recipient_count"];
			$message["open_rate"] = $delivery_details["open_click_rate"][$msg_data["guid"]]["open_rate"]."%";
			$message["click_rate"] = $delivery_details["open_click_rate"][$msg_data["guid"]]["click_rate"]."%";
		}
	}
	
	
	private function constructWeChatMessageDetails( $msg_data , &$message , $delivery_details ){
		$params = json_decode( $msg_data['params'] , true );
		$message["created_by"] = $msg_data['Created_by'];
		$message["message"] = $params["subject"];
		$this->statusDetailsForMessage( $msg_data , $message );
	}

	/**
	 * It will extract the details from message queue data and contruct the json accrodingly
	 * for text message
	 * @param array $msg_data
	 */
	private function constructTextMessageDetails( $msg_data , &$message , $delivery_details ){
		
		$params = json_decode( $msg_data['params'] , true );
		
		$message["created_by"] = $msg_data['Created_by'];
		$message["message"] = $params["message"];
		
		$this->statusDetailsForMessage( $msg_data , $message );

		if( isset($delivery_details["delivery_rate"][$msg_data["guid"]]) ){
			$message["delivery_rate"] = $delivery_details["delivery_rate"][$msg_data["guid"]]."%";
		}

		if( isset($delivery_details["open_click_rate"][$msg_data["guid"]]) ){
			$message["recipients"] = $delivery_details["open_click_rate"][$msg_data["guid"]]["overall_recipient_count"];
		}
	}
	
	/**
	 *
	 * Status Details For Messages table by campaign id
	 * @param array $row
	 * @param int $campaign_id
	 */
	private function statusDetailsForMessage( $msg_data , &$message ){
	
		$cron_mgr = new CronMgr();
		if($msg_data['scheduled_on'] == '0000-00-00 00:00:00'){
			$scheduled_on = $msg_data['scheduled_on'];
			$last_updated_on = $msg_data['last_updated_on'];
		}
		else{
			$scheduled_on = Util::convertTimeToCurrentOrgTimeZone($msg_data['scheduled_on']);
			$last_updated_on = Util::convertTimeToCurrentOrgTimeZone($msg_data['last_updated_on']);
		}
	
		if( $scheduled_on == '0000-00-00 00:00:00' ){
				
			$frequency = $this->getDateFromRemidner( $msg_data['id'] );
			$frequency_array = explode(' ',$frequency);
			
			$cron_minutes = $frequency_array[0];
			$cron_hours = $frequency_array[1];
			// modify time w.r.t. current org time zone
			$changed_time = Util::convertTimeToCurrentOrgTimeZone("$cron_hours:$cron_minutes");
			$frequency_array[0] = date( 'i' , strtotime( $changed_time ) );
			$frequency_array[1] = date( 'H' , strtotime( $changed_time) );
			$converted_frequency = implode(' ',$frequency_array);
			$read_frequency	= $cron_mgr->getFrequencyExplainationNew( $converted_frequency );
	
			$message["scheduled_on"] = _campaign("RECURRING")."<br/>".$read_frequency;
		}
		else if( $scheduled_on < date( 'Y-m-d H:i:s' ) && $msg_data['status'] != 'SENT' && $msg_data['status'] != 'REJECT' &&
				$msg_data['type'] != 'SMS' && $msg_data['type'] != 'EMAIL' && $msg_data['approved'] == true ){
	
			if( $scheduled_on == '0000-00-00 00:00:00' ){
				$message["scheduled_on"] = "RECURRING";
			}
			else {
				$message["scheduled_on"] = _campaign("Sent at").date(  "d M Y, H:i", strtotime( trim($msg_data['scheduled_on']) ) ).' '."hrs";
			}
		}
		else if( $msg_data['status'] == 'SENT' ){
			if( $msg_data['scheduled_type'] == 'IMMEDIATELY' ){
				$message["scheduled_on"] =_campaign("Sent at").date(  "d M Y, H:i", strtotime( trim($last_updated_on) ) ).' '."hrs";
			}else{
				$message["scheduled_on"] =_campaign("Sent at").date(  "d M Y, H:i", strtotime( trim($scheduled_on) ) ).' '."hrs";
			}
		}
		else if ( $msg_data['status'] == 'OPEN'){
			if( $msg_data['scheduled_type'] == 'IMMEDIATELY' ){
				
				$message["scheduled_on"] =_campaign("Created on").date(  "d M Y, H:i", strtotime( trim($scheduled_on) ) ).' '."hrs";
			}else{
				$message["scheduled_on"] =_campaign("Scheduled on").date(  "d M Y, H:i", strtotime( trim($scheduled_on) ) ).' '."hrs";
			}
		}
		else {
			$message["scheduled_on"] = "<span class='c-red-txt'>".$msg_data['status']."</span>";
		}
	}
	
	/**
	*
	* Gives Reminder status
	* @param unknown_type $row
	* @param unknown_type $campaign_id
	*/
	private function reminderState( $msg_data , &$message , $campaign_id ){

		$immediate = false;
		if( $msg_data['scheduled_type'] == 'IMMEDIATELY' ){
			$immediate = true;
		}

		if( !$immediate ){

			$audience_group_id = $this->getAudienceGroupByCampaignIdAndGroupId( $campaign_id , $msg_data['group_id'] );

			$reminder = $this->campaign_model->getReminderTaskIdAndState( $msg_data['id'] , $msg_data['group_id'] , $audience_group_id );

			if ( $msg_data['status'] == 'OPEN'){
	
				if( $reminder['state'] == 'STOP' ){
					$message["reminder_state"] = 'RUNNING';
					$message["display_reminder_state"] = _campaign("Start Scheduler");
				}
					
				if( $reminder['state'] == 'RUNNING' ){
					$message["reminder_state"] = 'STOP';
					$message["display_reminder_state"] = _campaign("Stop Scheduler");  
				}
			}
		}
	}
	
	/**
	 * It constructs the action menu for messages table depends on the type of message 
	 * @param array $msg_data
	 * @param array $message
	 * @param int $campaign_id
	 */
	private function constuctActionMenuForMessage( $msg_data , &$message , $campaign_id ){
		
		global $prefix;
		
		$scheduler_message = false;
		$params = json_decode( $msg_data['params'] , true );
		$default_arguments = json_decode( $msg_data['default_arguments'] , true );
		
		$actions = array();

		array_push( $actions , "<li><a msg_id='".$msg_data["id"]."'
								class='camp_msg_details'
								campaign_id='".$campaign_id."'>"._campaign("Details")."</a></li>" );
		
		if($msg_data['scheduled_type'] == 'IMMEDIATELY'){
			$immediate = true;
			$accept_btn_label = _campaign("Authorize");
		}
		else {
			$accept_btn_label = _campaign("Authorize");
			$immediate = false;
		}
		
		if( $msg_data['type'] == 'CUSTOMER_TASK' ){
			$edit_url = "<a>"._campaign("Edit")."</a>";
		}else if( $msg_data['type'] == 'CALL_TASK' || $msg_data['type'] == 'CALL_TASK_REMINDER' ){
			$edit_url = "<a href='$prefix/campaign/messages/v2/CallTask?campaign_id=".$campaign_id."&message_id=".$msg_data['id']."'>"._campaign("Edit")."</a>";
		}else if( $msg_data['type'] == 'EMAIL' || $msg_data['type'] == 'EMAIL_REMINDER' ){
			$edit_url = "<a href='$prefix/campaign/messages/v2/Messages?campaign_id=".$campaign_id."&message_id=".$msg_data['id']."'>"._campaign("Edit")."</a>";
		}else if( $msg_data['type'] == 'SMS' || $msg_data['type'] == 'SMS_REMINDER' ){
			$edit_url = "<a href='$prefix/campaign/messages/v2/CampaignMessages?campaign_id=".$campaign_id."&message_id=".$msg_data['id']."'>"._campaign("Edit")."</a>";
		}else if( $msg_data['type'] == 'WECHAT' || $msg_data['type'] == 'WECHAT_REMINDER' ){
			$edit_url = "<a href='$prefix/campaign/messages/v2/WeChat?campaign_id=".$campaign_id."&message_id=".$msg_data['id']."&account_id=".$default_arguments['ServiceAccoundId']."'>"._campaign("Edit")."</a>";
		}else if($msg_data['type'] == 'MOBILEPUSH' || $msg_data['type'] == 'MOBILEPUSH_REMINDER'){
			$edit_url = "<a href='$prefix/campaign/messages/v2/MobilePush?campaign_id=".$campaign_id."&account_id=".$default_arguments['accountId']."&message_id=".$msg_data['id']."'>"._campaign("Edit")."</a>";
		}else if( $msg_data['type'] == 'SMS_EXPIRY_REMINDER' || $msg_data['type'] == 'EMAIL_EXPIRY_REMINDER' ){
			$edit_url = false;
		}
		
		if( $msg_data['type'] != "SMS" && $msg_data['type'] != "EMAIL" )
			$scheduler_message = true;
		
		if( 
				( $msg_data['approved'] == 0 && $msg_data['status'] == 'OPEN' )
						&&
				(
					$immediate 
						|| 
					( $msg_data['scheduled_on'] >= date( 'Y-m-d H:i:s' ) ) 
						||
					$scheduler_message 
				)
		){
		
			$authorize_url = new UrlCreator();
			$authorize_url->setNameSpace('campaign/v3/messages');
			$authorize_url->setPage('Authorize');
			$authorize_url_link = $authorize_url->generateUrl(false,false)."#message/".$msg_data['id'];
			$js = "onClick=\"showPopup( '$authorize_url_link' );\"";
			//$auth_url = "<a $js>$accept_btn_label</a>";
			//array_push( $actions , "<li>".$auth_url."</li>" );
			$auth_url = "<button type='button' class='btn' campaign_id='".$campaign_id."' $js>".$accept_btn_label."</button>";
			$message["approved"] = $auth_url;
		}
		
		if( $msg_data["approved"] == 0 ){
			
			$view_url = "<li><a msg_id='".$msg_data["id"]."'
								class='camp_msg_view'
								campaign_id='".$campaign_id."'>"._campaign("View")."</a></li>";
			array_push( $actions , $view_url );
		}
		
		if( !empty($params["message"]) &&  $msg_data['status'] == 'OPEN' && $edit_url){
			array_push( $actions , "<li>".$edit_url."</li>" );
		}
		
		if ($msg_data['approved'] == 0 && $msg_data['status'] == 'REJECTED'){
		$re_queue_url = "<li><a msg_id='".$msg_data["id"]."'
		class='camp_requeue_msg'
		campaign_id='".$campaign_id."'>"._campaign("Requeue")."</a></li>";
		array_push( $actions , $re_queue_url ); }
		
		$this->reminderState( $msg_data , $message , $campaign_id );
		
		if( !empty($message["reminder_state"]) ){
			
			$reminder_url = "<li><a msg_id='".$msg_data["id"]."' class='reminder_state_change' 
								campaign_id='".$campaign_id."' state='".$message["reminder_state"]."'>".
							 	$message["display_reminder_state"]."</a></li>";
			
			array_push( $actions , $reminder_url );
		}
		
		$action_menu = "";
		foreach( $actions as $action ){
			$action_menu .= $action;
		}
		
		$message["actions"] = $action_menu;
	}
	
	/*
	 * it fetches messages for authorization
	*/
	public function getMessageDetailsForAuthorization( $msg_id ){
	
		$md = $this->campaign_model_extension->getQueuedMessageDetailsById( $msg_id );
		$params = json_decode($md['params']);
		$default_args = json_decode( $md['default_arguments'], true );
		$group_id = $md['group_id'];
		$type = $md['type'];
		$campaign_id = $md['campaign_id'];
		$msg = $params->message;
	
		$queue_type = 'SMS';
		$extra_params['store_type'] = $default_args['store_type'];
		if ( strtolower( $type ) == 'email' || strtolower( $type ) == 'email_reminder'){
				
			$msg_subject = $params->subject;
			$msg = $params->message;
			$queue_type = 'EMAIL';
				
		} else if( strtolower( $type ) == 'customer_task' ){
				
			$queue_type = 'CUSTOMER_TASK';
			$msg = stripcslashes( rawurldecode( $params->store_task_display_text ) );
			$msg_subject = stripcslashes( rawurldecode( $params->store_task_display_title ) );
		} elseif( strtolower( $type ) == 'call_task' || strtolower( $type ) == 'call_task_reminder' ){
	
			$msg_subject = $params->subject;
			$msg = $params->message;
			$description = $params->description;
			$queue_type = 'CALL_TASK';
		}  else
			$msg_subject = false;
	
		$messages = $this->getUsersPreviewTable( $group_id, $campaign_id, $msg , $msg_subject , $queue_type, $extra_params );
		
		if( empty( $messages ) ){
			$messages = array( "msg" => $msg , "subject" => $msg_subject , "description" => $description , "to" => false );
		}else{
			$messages = $messages[0];
			$messages["description"] = $description;
		}
		
		if(strtolower( $type ) == 'wechat' && $default_args['TemplateIds'] && $default_args['msg_type']=='WECHAT_MULTI_TEMPLATE') {
			$C_template = new Template();
			$default_args['singlePicTemplates'] = array();
			if($default_args['TemplateIds']) {
				$templateIds = explode(',', $default_args['TemplateIds']);
				foreach($templateIds as $templateId) {
					$this->logger->debug("@@templateId : ". $templateId );
					$C_template->load( $templateId );
					$singlePicData = json_decode( $C_template->getFileServiceParams() , true );
			 		array_push($default_args['singlePicTemplates'], $singlePicData);
				}
			}
		} else if($default_args['msg_type'] == 'WECHAT_SINGLE_TEMPLATE') {
			$C_template = new Template();

			$C_template->load( $default_args['template_id'] );

			$singlePicData = json_decode( $C_template->getFileServiceParams() , true );

			$default_args['templateData'] = $singlePicData;
		} else {	//$default_args['msg_type'] == 'WECHAT_TEMPLATE'
			$C_template = new Template();
			$C_template->load( $default_args['template_id'] );
			$wechat_fileserviceparams = json_decode( $C_template->getFileServiceParams() , true );
			$default_args['templateData'] = $wechat_fileserviceparams;
		}
		$messages['default_args'] =  $default_args;
		return $messages;
	}
	
	/**
	 * It returns the checklist details for the particular message id
	 * @param int $message_id
	 */
	public function getCheckListDetails( $message_id ){
		
		$C_bulk_message = $this->getBulkMessageDetails( $message_id );
		
		$group_details = $this->getGroupDetails( $C_bulk_message->getGroupId() );
		
		$default_args = json_decode( $C_bulk_message->getDefaultArguments() , true );
		
		$audience_details = $this->getAudienceDetailsByGroupId
		(
				$C_bulk_message->getCampaignId(),
				$group_details['group_id']
		);
		
		$audience_filter_details = $this->getFilterDataByAudienceId
		(
				$audience_details['id']
		);
		
		$details = array();
		
		$details[_campaign('List Name')] = $group_details['group_label'];
		$params = json_decode( $group_details['params'] , true );
		
		$details[_campaign('List Type')] = _campaign(Util::beautify( $group_details['type'] ));
		
		$audience_group_id = $audience_details['id'];
		$group_type = $group_details['type'];
		
		$filter_details = array();
		foreach ( $audience_filter_details as $row ){
			if( $row['audience_group_id'] == $audience_group_id ){
				array_push( $filter_details, $row);
			}
		}
		
		if( $group_type == 'loyalty' ){
		
			if( $filter_details[0]['filter_type'] ){
				if( $filter_details['filter_type'] == 'entityBased' ){
		
					$filter_values = explode( 'Selected' , $filter_details[0]['filter_explaination'] );
					
					$details[_campaign('List Explanation')] = _campaign("Entity based").strtolower($filter_values[0])._campaign("filter selected ")."<br/>";
				}else{
					$details[_campaign('List Explanation')] = $filter_details[0]['filter_explaination']."<br/>";
				}
			}
			else
				$details[_campaign('List Explanation')] = _campaign("All registered customers")."\n";
		
		}else{
		
			if( $filter_details[0]['filter_type'] == 'test_control' ){
		
				$details[_campaign('List Explanation')] = Util::templateReplace( $filter_details[0]['filter_explaination'],
						array( 'css_start' => "<b style='color:green;'>",
								'css_end' => "</b>" ) );
			}else{
		
				$details[_campaign('List Explanation')] = _campaign("Uploaded customers");
			}
		}
		
		if( !empty( $filter_details ) ){
			
			$details[_campaign('List Explanation')] = "<ul class='new-camp-checklist-filter-ul flexcroll'>";
			foreach( $filter_details as $filter_detail ){
			
				$explaination = Util::templateReplace( $filter_detail['filter_explaination'],
						array( 'css_start' => "<b style='color:green;'>",
								'css_end' => "</b>" ) );
								
				$details[_campaign('List Explanation')] .= "<li>".$explaination."</li>";
			}
			$details[_campaign('List Explanation')] .= "</ul>";
		}
		
		if( $C_bulk_message->getType() == 'EMAIL' || $C_bulk_message->getType() == 'EMAIL_REMINDER' )
			$details[_campaign('Customer Count')] = $params['email'];
		elseif($C_bulk_message->getType() == 'MOBILEPUSH' || $C_bulk_message->getType() == 'MOBILEPUSH_REMINDER'){
			$details[_campaign('Customer Count')] = array_sum($params['android']) + array_sum($params['ios']) ;
		}else
			$details[_campaign('Customer Count')] = $params['mobile'];
		
		
		$schedule_type = $C_bulk_message->getScheduledType();
		
		if( $schedule_type == "PARTICULAR_DATE" )
			$details[_campaign('Scheduled Time')] = I18nUtil::convertDateToLocale(Util::convertTimeToCurrentOrgTimeZone($C_bulk_message->getScheduledOn()), IntlDateFormatter::LONG, IntlDateFormatter::SHORT)." "._campaign('hours');

		if( $schedule_type == "IMMEDIATELY" )
			$details[_campaign('Created Time')] = I18nUtil::convertDateToLocale(Util::convertTimeToCurrentOrgTimeZone($C_bulk_message->getScheduledOn()), IntlDateFormatter::LONG, IntlDateFormatter::SHORT)." "._campaign('hours');

		$details[_campaign('Schedule Type')] = _campaign($schedule_type);
		
		$msg = _campaign("Approve");
		if( $schedule_type == "IMMEDIATELY" ){
			$msg	= _campaign("Approve and Send");
		}
		
		$blast_type = strtoupper( $C_bulk_message->getType() );
		$reject = 1;
// 		if( ( ( $blast_type == 'SMS' || $blast_type == 'EMAIL' ) && $schedule_type == 'IMMEDIATELY' )
// 				|| $blast_type == 'CALL_TASK' ){
// 			$reject = 1;
// 		}
		//add store type
		if( !$default_args['store_type'] ) $default_args['store_type'] = 'registered_store';
		$details[_campaign('Store tag type')] = ( $default_args['store_type'] == 'registered_store' )?( _campaign("Registered") ):( _campaign("Last Shopped") );

		if( $C_bulk_message->getType() == 'SMS' ){
			if( $default_args['sendToNdnc'] == 'true'){
				$details[_campaign('NDNC')] = _campaign("NDNC Opt out customers would be targeted");

			}else{
				$details[_campaign('NDNC')] = _campaign("NDNC Opt out customers would not be targeted");

			}
		}
		
		$type = "sms";
		if ( strtolower( $blast_type ) == 'email' || strtolower( $blast_type ) == 'email_reminder'){
			$type = "email";
		}else if( strtolower( $blast_type ) == 'customer_task' ){
			$type = "customer";
		}elseif( strtolower( $blast_type ) == 'call_task' || strtolower( $blast_type ) == 'call_task_reminder' ){
			$type = "call";		
		}elseif( strtolower( $blast_type ) == 'wechat' || strtolower( $blast_type ) == 'wechat_reminder' ){
			$type = "wechat";		
		}elseif($blast_type == 'MOBILEPUSH' || $blast_type == 'MOBILEPUSH_REMINDER'){
			$type = "mobilepush";		
		}
		
		$default_arguments = json_decode($C_bulk_message->getDefaultArguments() , true);
		$this->logger->debug("default arguments : ".print_r($default_arguments, true)) ;
		include_once 'business_controller/ChannelController.php';
 		$channel_controller = new ChannelController();

		if($type == "sms"){
			$details[_campaign('Sender From')] = $default_args['sender_gsm'];
			$details[_campaign('Sender Mobile')] = $default_args['sender_cdma'];
		}else if ($type == "email"){
			$details[_campaign('Sender From')] = $default_args['sender_label'];
			$details[_campaign('Sender Email')] = $default_args['sender_email'];
		}elseif($blast_type == 'MOBILEPUSH' || $blast_type == 'MOBILEPUSH_REMINDER'){
			$account_details = $channel_controller->getAccountDetailByID($default_arguments['accountId']) ;
			$account_name = "NA" ;
			if(is_array($account_details) && count($account_details) > 0){
				$account_name = $account_details[0]['account_name'] ;
			}
			$details[_campaign('Sender Account')] = $account_name ;
		}
		
		return array( "checklist" => $details , "btn_label" => $msg , 
				"reject_label" => $reject , "type" => $type );
	}
	
	/**
	 * 
	 * @see CampaignController::approveMessage()
	 */
	public function approveMessage( $message_id ){
		
		$this->logger->debug("@@Approve Message: ".$message_id);
		
		$C_message = $this->getBulkMessageDetails( $message_id );
		$scheduled_type = $C_message->getScheduledType();
		$blast_type = strtoupper( $C_message->getType() );

		$this->logger->debug('The blast_type in approveMessage is : '.$blast_type);
		
		//check for campaign expiry before approve the message
		$campaign_id = $C_message->getCampaignId();
		$expired = $this->isCampaignExpired( $campaign_id );
		if( $expired ){
			throw new Exception( $expired );			
		}
			$C_message->setApproved( 1 );
			$C_message->setApprovedBy( $this->user_id );
			$C_message->update( $message_id );
			
		if( ( ( $blast_type == 'SMS' || $blast_type == 'EMAIL' || $blast_type == 'CALL_TASK' || $blast_type == 'WECHAT' || $blast_type == 'MOBILEPUSH' )
				&& $scheduled_type == 'IMMEDIATELY' ) ){
	
			$C_bulk_sender = BulkMessageSenderFactory::getSender( BulkMessageTypes::valueOf( $blast_type ) );

			

			$response = $C_bulk_sender->send( $C_message );
			//add control group users
			$C_bulk_sender->addControlGroupUsersHistory( $C_message, $response );
			$status = _campaign('Bulk '.Util::beautify(strtolower($blast_type)).' sent successfully.');
			$this->logger->debug("@@Approve Message End");
			return $status;
		}
		
		$status = _campaign('Bulk '.Util::beautify(strtolower($blast_type)).' approved successfully.');

		$this->logger->debug("@@Approve Message End");
		return $status;
	}
	
	public function rejectMessage( $message_id ){
		
		$this->logger->debug("@@Reject Message: ".$message_id);
		
		$C_message = $this->getBulkMessageDetails( $message_id );
		$scheduled_type = $C_message->getScheduledType();		
		$blast_type = strtoupper( $C_message->getType() );
		
		//check for campaign expiry before approve the message
		$campaign_id = $C_message->getCampaignId();
		$expired = $this->isCampaignExpired( $campaign_id );
		if( $expired ){
			throw new Exception( $expired );
		}

		$status = _campaign("Bulk message rejected successfully.");
		
		if( ( ( $blast_type == 'SMS' || $blast_type == 'EMAIL' || $blast_type == 'WECHAT' ) && $scheduled_type == 'IMMEDIATELY' )
				|| $blast_type == 'CALL_TASK' ){
	
			$C_bulk_sender = BulkMessageSenderFactory::getSender( BulkMessageTypes::valueOf( $blast_type ) );
			$C_bulk_sender->reject( $C_message );
			$status = _campaign('Bulk '.strtolower($blast_type).'rejected successfully.');
		}
		return $status;
	}
	
	public function getVoucherSeriesDetailsForCoupon($campaign_id){
		
		$default_currency = $this->C_org_controller->getBaseCurrencyForOrg();
		$this->load( $campaign_id );
		$campaign_info = $this->getDetails();
		$vs_id = $campaign_info['voucher_series_id'];
		
		$campaign_info["no_coupon_series"] = 1;
		if( $vs_id == -1 )
			$campaign_info["no_coupon_series"] = 0;
		
		$campaign_info["org_id"] = $this->org_id;
		
		$C_coupon_series_manager = new CouponSeriesManager();
		$C_coupon_series_manager->loadById($vs_id);
		$luci = false;
		if($luci){
			$result = $this->getVoucherSeriesDetails($vs_id) ;			
		}else{
			$result = $C_coupon_series_manager->getDetails();
		}
		
		$C_coupon_product_manager = new CouponProductManager();
		$brand_result = $C_coupon_product_manager->getProductBrandValues('root',$vs_id);
		$brands = $brand_result["items"];
		$categories = $C_coupon_product_manager->getSelectedProductCategoryHierarchy($vs_id);
		$validity = $C_coupon_product_manager->isProductSelected($vs_id);
		//$validity = true;
		$voucher_validity = array("valid"=>$validity,"brand"=>$brands,"category"=>$categories);
		$campaign_info['description'] = $result['description'];
		$campaign_info['code'] = $result['discount_code'];
		$dis = $result['discount_value']." ".( $result['discount_type'] == 'ABS' ? $default_currency : '%' )." "._campaign("off on")." ".( $result['discount_on'] == 'BILL' ? _campaign('Bill Value') : _campaign('Item') );
		$campaign_info['discount'] = $dis;
		$campaign_info["num_issued"] = ( $result['num_issued'] <= 0 ) ? '0' : $result['num_issued'];
		$campaign_info["num_redeemed"] = ( $result['num_redeemed'] <= 0) ? '0' : $result['num_redeemed'];
		$campaign_info["voucher_validity"] = $voucher_validity;
		return $campaign_info;
	}

	public function getPointsDetails($campaign_id,$msg_id){
		include_once "business_controller/loyaltyprogram/LoyaltyProgramController.php" ;
		include_once "base_model/campaigns/class.IncentiveBase.php" ;
		include_once "model_extension/campaigns/class.IncentiveModelExtension.php" ;
		
		$alloc_strategy_type_id = 0 ;
		$exit_strategy_type_id = 0 ;
		$allocation_strategy = array() ;
		$exit_strategy = array() ;
		$prog_id = 0 ;
		$arrSlabName = array() ;

		$status = new stdClass() ;
		$status->state = true ;
		$status->msg = "" ;
        
        $isFirstTime = false ;

        $loyalty_ctrl = new LoyaltyProgramController() ;
		$incentive_model = new IncentiveModelExtension() ;

		$incentive_prop = $incentive_model->arePointsAllocatedToCamapign($campaign_id) ;
		$incentive_prop = (int)$incentive_prop;
		$this->logger->debug("the incentive prop is : ".print_r($incentive_prop,true)) ;
		
		//get allocation and strategy type id
		try{
			$strategy_types = $loyalty_ctrl->getStrategyTypes() ;	
		}
		catch(Exception $ex){
			$this->logger->debug("Exception while getting strategytypes for points with message : ".$ex->getMessage()) ;
			$status->state = false ;
			$status->msg = _campaign("Error fetching points strategies") ;
	        
	        $isFirstTime = false ;
			$point_info = array("allocation_strategy"=>$allocation_strategy,"exit_strategy"=>$exit_strategy,"is_first_time"=>$isFirstTime,"program_id"=>$prog_id,"status"=>$status,"slabs"=>$arrSlabName) ;
			return $point_info ;
		}
		
		foreach($strategy_types as $id=>$name){
			if($name=="POINT_ALLOCATION"){
				$alloc_strategy_type_id = $id;		
			}
			else if($name=="POINT_EXPIRY"){
				$exit_strategy_type_id = $id ;
			}
			if(!empty($alloc_strategy_type_id) && !empty($exit_strategy_type_id)){
				break ;
			}
		}

		//check if points are already selected for this campaign
		if($incentive_prop <= 0 ){
			//all the points are displayed for a given org
			try{
				$prog_id = $loyalty_ctrl->getProgramId() ;
				if(is_null($prog_id)){
					$this->logger->debug("Program id from points is null") ;
					$status->state = false ;
					$status->msg = _campaign("Error fetching points strategies") ;
			        
			        $isFirstTime = false ;
					$point_info = array("allocation_strategy"=>$allocation_strategy,"exit_strategy"=>$exit_strategy,"is_first_time"=>$isFirstTime,"program_id"=>$prog_id,"status"=>$status,"slabs"=>$arrSlabName) ;
					return $point_info ;
				}
			}
			catch(Exception $ex){
				$this->logger->debug("Exception thrown while fetching program id with message : ".$ex->getMessage()) ;
				$status->state = false ;
				$status->msg = _campaign("Error fetching points strategies") ;
		        
		        $isFirstTime = false ;
				$point_info = array("allocation_strategy"=>$allocation_strategy,"exit_strategy"=>$exit_strategy,"is_first_time"=>$isFirstTime,"program_id"=>$prog_id,"status"=>$status,"slabs"=>$arrSlabName) ;
				return $point_info ;
			}
			
			$isFirstTime = true ;
		}
		else{
			//previously selected points are displayed
			$points_info = $incentive_model->getPointsPropertiesByCampaignId($campaign_id) ;
			if(count($points_info)>0){
				$prog_id = $points_info[0]['program_id'] ;
				$alloc_strategy_id = $points_info[0]['allocation_strategy_id'] ;
				$exit_strategy_id = $points_info[0]['expiry_strategy_id'] ;
				$this->logger->debug("alloc strategy is : ".$alloc_strategy_id." exit strat is : ".$exit_strategy_id) ;
				
				//this is not working hence long route
				/*$allocation = $loyalty_ctrl->getStrategy($alloc_strategy_id) ;
				$exit = $loyalty_ctrl->getStrategy($exit_strategy_id) ;			*/
			}

			$isFirstTime = false ;
		}

		try{
			$allocation = $loyalty_ctrl->getStrategiesByStrategyTypeIdAndOwner($prog_id,$alloc_strategy_type_id,"CAMPAIGN") ;
			$exit = $loyalty_ctrl->getStrategiesByStrategyTypeIdAndOwner($prog_id,$exit_strategy_type_id,"CAMPAIGN") ;	
		}
		catch(Exception $ex){
			$this->logger->debug("Exception thrown while fetching allocation and exit strategies : ".$ex->getMessage()) ;
			$status->state = false ;
			$status->msg = _campaign("Error fetching points strategies") ;
	        $isFirstTime = false ;
			$point_info = array("allocation_strategy"=>$allocation_strategy,"exit_strategy"=>$exit_strategy,"is_first_time"=>$isFirstTime,"program_id"=>$prog_id,"status"=>$status,"slabs"=>$arrSlabName) ;
			return $point_info ;			 
		}
		

		$this->logger->debug("the allocation object : ".print_r($allocation,true)) ;
		$this->logger->debug("the exit strategy object : ".print_r($exit,true)) ;
		
		try{
			//points slabs
			$slabs = $loyalty_ctrl->getAllSlabs($prog_id) ;
		}
		catch(Exception $ex){
			$this->logger->debug("Exception thrown while fetching points slabs with message : ".$ex->getMessage()) ;
			$status->state = false ;
			$status->msg = _campaign("Error fetching points strategies") ;
	        $isFirstTime = false ;
			$point_info = array("allocation_strategy"=>$allocation_strategy,"exit_strategy"=>$exit_strategy,"is_first_time"=>$isFirstTime,"program_id"=>$prog_id,"status"=>$status,"slabs"=>$arrSlabName) ;
			return $point_info ;			 
		}
		
		foreach($slabs as $obj){
			array_push($arrSlabName, $obj->getName()) ;
		}

		//check wheteher points are displayed for first time or already selected
		if($incentive_prop <= 0){
			//get all the allocation and exit strategies for the current org
			foreach($allocation as $key=>$val){
				$propVal = json_decode($val->getPropertyValues()) ;
				$allocation_strategy[] = array("id"=>$val->getId(),"name"=>$val->getName(),"property_values"=>explode(",",$propVal->allocation_values)) ;
			}
			foreach($exit as $key=>$val){
				$prop = json_decode($val->getPropertyValues()) ;
				$expire_from = $propVal->expiry_from ;
				
				//$expire_value decides the point from which expiry date is applicable
				$expire_value = "" ;
				if(strcasecmp($expire_from, "CURRENT_DATE")==0){
					$expire_value = _campaign("from current date") ;
				}
				else if(strcasecmp($expire_from, "ACTIVITY_BASED_EXTENSION")==0){
					$expire_value = "" ;
				}
				else if(strcasecmp($expire_from, "MEMBERSHIP_DATE")==0){
					$expire_value = "" ;
				}

				$propVal = explode(",",$prop->expiry_time_values) ;
				$propUnit = explode(",",$prop->expiry_time_units) ;
				
				//get different formats of expiry date/days
				$actualVal = array() ;
				
				foreach($propVal as $key1=>$val1){
					if(strcasecmp($propUnit[$key1], "NUM_DAYS")==0){
						array_push($actualVal, $val1." "._campaign("Days")." ") ;
					}
					else if(strcasecmp($propUnit[$key1], "NUM_MONTHS_END")==0){
						array_push($actualVal, $val1." "._campaign(" Months")." " ) ;
					}
					else if(strcasecmp($propUnit[$key1], "NEVER")==0){
						array_push($actualVal, _campaign("Never expires") ) ;
					}
					else if(strcasecmp($propUnit[$key1], "FIXED_DATE")==0){
						$date = date_create($val1) ;
						array_push($actualVal,date_format($date,'d M y') ) ;	
					}

				}

				$this->logger->debug("the actual value is : ".print_r($actualVal,true)) ;
				$exit_strategy[] = array("id"=>$val->getId(),"name"=>$val->getName(),"property_values"=>$actualVal) ;
				
			}	
		}
		else{
			//get only the selected allocation and exit strategy
			foreach($allocation as $key=>$val){
				$propVal = json_decode($val->getPropertyValues()) ;
				if($val->getId()==$alloc_strategy_id){
					$allocation_strategy[] = array("id"=>$val->getId(),"name"=>$val->getName(),"property_values"=>explode(",",$propVal->allocation_values)) ;	
					break ;
				}
			}

			foreach($exit as $key=>$val){
				$prop = json_decode($val->getPropertyValues()) ;
				$expire_from = $propVal->expiry_from ;
				
				//$expire_value decides the point from which expiry date is applicable
				$expire_value = "" ;
				if(strcasecmp($expire_from, "CURRENT_DATE")==0){
					$expire_value = _campaign("from current date") ;
				}
				else if(strcasecmp($expire_from, "ACTIVITY_BASED_EXTENSION")==0){
					$expire_value = "" ;
				}
				else if(strcasecmp($expire_from, "MEMBERSHIP_DATE")==0){
					$expire_value = "" ;
				}

				$propVal = explode(",",$prop->expiry_time_values) ;
				$propUnit = explode(",",$prop->expiry_time_units) ;
				
				//get different formats of expiry date/days
				$actualVal = array() ;
				
				foreach($propVal as $key1=>$val1){
					if(strcasecmp($propUnit[$key1], "NUM_DAYS")==0){
						array_push($actualVal, $val1._campaign(" Days ") ) ;
					}
					else if(strcasecmp($propUnit[$key1], "NUM_MONTHS_END")==0){
						array_push($actualVal, $val1._campaign(" Months") ) ;
					}
					else if(strcasecmp($propUnit[$key1], "NEVER")==0){
						array_push($actualVal, _campaign("Never expires") ) ;
					}
					else if(strcasecmp($propUnit[$key1], "FIXED_DATE")==0){
						$date = date_create($val1) ;
						array_push($actualVal,date_format($date,'d M y') ) ;	
					}

				}

				if($val->getId()==$exit_strategy_id){
					$exit_strategy[] = array("id"=>$val->getId(),"name"=>$val->getName(),"property_values"=>$actualVal) ;
					break ;
				}
			}		
		}
				
		
		if(count($allocation_strategy)==0 && count($exit_strategy)==0){
			$status->state = false ;
			$status->msg = _campaign("No Allocation and Expiry strategies are available for selection") ;
		}
		else if(count($allocation_strategy)==0){
			$status->state = false ;
			$status->msg = _campaign("No Allocation strategy is available for selection") ;
			$this->logger->debug("the allocation strategy is empty") ;
		}
		else if(count($exit_strategy)==0){
			$status->state = false ;
			$status->msg = _campaign("No Expiry strategy is available for selection") ;
		}
		
		$point_info = array("allocation_strategy"=>$allocation_strategy,"exit_strategy"=>$exit_strategy,"is_first_time"=>$isFirstTime,"program_id"=>$prog_id,"status"=>$status,"slabs"=>$arrSlabName) ;
		$this->logger->debug('get details for points info: '.print_r($point_info,true) );
		return $point_info ;
	}
	
	private function setCouponValues( $series_id ){
	
		$default_values = array();
		$this->logger->debug('get details for coupon series: '.$series_id );
		
		$this->default = $this->C_coupon_series->getDefaultValues();
		$this->radio_inputs = $this->C_coupon_series->getOutBoundRadioFormLabels();
		$this->combo_inputs = $this->C_coupon_series->getOutBoundComboFormLabels();
	
		$this->week = array_flip(Util::getDOW());
		$this->month =  array_flip( Util::getDOM());
		$this->hour  =  array_flip(Util::getHours());
		$this->logger->debug("memory peak usage : ".memory_get_peak_usage()) ;	
		$this->logger->debug("memory peak real usage : ".memory_get_peak_usage(true)) ;
		$this->logger->debug("memory usage : ".memory_get_usage()) ;
		$this->logger->debug("memory real usage : ".memory_get_usage(true)) ;
		$this->logger->debug("before getSeriesAsOptions") ;			
		$this->mutual_exclusive_series = array_flip($this->C_coupon_series->getSeriesAsOptions(true));
		$this->logger->debug("memory peak usage : ".memory_get_peak_usage()) ;	
		$this->logger->debug("memory peak real usage : ".memory_get_peak_usage(true)) ;
		$this->logger->debug("memory usage : ".memory_get_usage()) ;
		$this->logger->debug("memory real usage : ".memory_get_usage(true)) ;
		$this->logger->debug("after getSeriesAsOptions") ;			
		$this->C_coupon_series->loadById( $series_id );
		$default_values = $this->C_coupon_series->getDetails();
	
		$redemption_range = json_decode( $default_values['redemption_range'], true);
	
		$redemption_range['dow'] = !is_array( $redemption_range['dow'] ) ? array() : $redemption_range['dow'] ;
		foreach ( $redemption_range['dow'] as $dow) {
	
			$dayofweek .= $this->week[$dow].',';
		}
			
		$redemption_range['dom'] = !is_array( $redemption_range['dom'] ) ? array() : $redemption_range['dom'] ;
		foreach ( $redemption_range['dom'] as $dom) {
			$dayofmonths .= $this->month[$dom].',';
		}
			
		$redemption_range['hours'] = !is_array( $redemption_range['hours'] ) ? array() : $redemption_range['hours'] ;
		foreach ( $redemption_range['hours'] as $hr) {
			$hours .= $this->hour[$hr].',';
		}
	
		$default_values['redemption_range'] = _campaign("Month:").$dayofmonths.'<br/>'._campaign("Week:").$dayofweek.'<br/>'._campaign("Hours :").$hours ;
	
		$profile = StoreProfile::getById( $default_values['created_by'] );
		$default_values['created_by'] = $profile->username;
	
		$issual[_campaign("Number Of Vouchers Issued")] = $default_values['num_issued'];
		$issual[_campaign("Number Of Vouchers Redeemed")] = $default_values['num_redeemed'];
	
		$default_values = array_merge( $issual, $default_values );
	
		$default_values['do_not_resend_existing_voucher'] =
		( $default_values['do_not_resend_existing_voucher']  ) ? ( false ) : ( true );
	
		foreach( $this->radio_inputs as $name => $label ){
	
			$label = util::uglify($label);
	
			$default_values[$name] = ( int ) $default_values[$name];
			if( $default_values[$name] == -1 || $default_values[$name] == false ){
	
				$default_values[$label] = _campaign('NO');
			}else if($default_values[$name] == true ){
	
				$default_values[$label] = _campaign('YES');
			}
	
			unset( $default_values[$name] );
		}
	
		//unset($default_values['do_not_resend_existing_voucher']);
		foreach( $this->combo_inputs as $name => $label ){
	
			$label = util::uglify($label);
	
			if($default_values[$name] == '' || $default_values[$name] == '-1' || $default_values[$name] == '0')
				$default_values[$label] = _campaign('NO');
			else
				$default_values[$label] = $default_values[$name];
	
			unset( $default_values[$name] );
		}
	
	
		$default_values['created'] =  date( 'dS M Y' , strtotime($default_values['created']));
		$default_values['valid_till_date'] =  date( 'dS M Y' , strtotime($default_values['valid_till_date']));
		
		$year = date('Y', strtotime($default_values['last_used']));
		if( $year > 2000 )
			$default_values['last_used'] =  date( 'dS M Y' , strtotime($default_values['last_used']));
		else
			$default_values['last_used'] = 'N/A';
		
		//$default_values['series_description'] = $default_values['info'];
		$default_values['procedure_at_POS'] = $default_values['client_handling_type'];
		$default_values['coupon_message'] = $default_values['sms_template'];
	
	
		$redeem_stores = json_decode($default_values['redeem_at_store'],true);
		if( $redeem_stores[0] != -1){
	
			$till_details = $this->C_org_controller->StoreTillController->getByIds( $redeem_stores );
	
			foreach( $till_details as $td ){
	
				$store_names .= $td['name']."</br>";
			}
		} else {
				
			$store_names = _campaign("All Stores");
		}
		$default_values['redeem_stores'] = $store_names ;
	
			
		if($default_values['mutual_exclusive_series_ids'] != null )	{
			
			$default_values['mutual_exclusive_series'] =  json_decode($default_values['mutual_exclusive_series_ids'], true) ;
	
			$default_values['mutual_exclusive_series'] =
			!is_array( $default_values['mutual_exclusive_series'] )
			? array() : $default_values['mutual_exclusive_series'] ;
	
			foreach ($default_values['mutual_exclusive_series'] as $mutual_series_ids){
				$mutual_series .= $this->mutual_exclusive_series[$mutual_series_ids].'<br />';
			}
			$default_values['mutual_exclusive_series'] = $mutual_series;
		}
		else
			$default_values['mutual_exclusive_series'] = 'NO';
		
		$default_values['Coupon_Series_Validity_Expires_On'] = $default_values['valid_till_date'];
		$coupon_manager = new CouponManager() ;
		//get valid days based on expiry strategy type
		$default_values['Coupons_Validity_From_Created_Date'] = $coupon_manager->getValidDaysForStrategy( $default_values['id'] ) ;
	
		unset($default_values['dvs_enabled']);
		unset($default_values['priority']);
		unset($default_values['terms_and_condition']);
		unset($default_values['sync_to_client']);
		unset($default_values['dvs_items']);
		unset($default_values['dvs_expiry_date']);
		unset($default_values['store_ids_json']);
		unset($default_values['short_sms_template']);
		unset( $default_values['sms_template'] );
		unset( $default_values['org_id'] );
		unset( $default_values['id'] );
		unset( $default_values['client_handling_type'] );
		unset( $default_values['mutual_exclusive_series_ids'] );
		unset( $default_values['valid_till_date'] );
		unset( $default_values['valid_days_from_create'] );
		unset( $default_values['any_user'] );
		unset( $default_values['info'] );
		unset ( $default_values['num_issued'] );
		unset ( $default_values['num_redeemed'] );
		unset( $default_values['redeem_at_store']);
	
	
		if( $default_values['max_referrals_per_referee'] == -1){
			$default_values['max_referrals_per_referee'] = 'NO';
		}
		$C_coupon_product_manager = new CouponProductManager();
		$validity = $C_coupon_product_manager->isProductSelected($series_id);
		if($validity){
			$default_values[_campaign("Valid Brands and Categories")] = _campaign("Custom");
		}
		else{
			$default_values[_campaign("Valid Brands and Categories")] = _campaign("All");
		}
		if(count($default_values)%2 === 1)
			$default_values[' '] = ' ';
		$reminder_details = $this->C_coupon_series->getVoucherExpiryReminderDetails($series_id);
		if($reminder_details['is_reminder_set']){
			
			$default_values['Set Expiry Reminder'] = _campaign("Yes");
			$default_values['  '] = '  ';
			$alert_days = implode(', ', $reminder_details['alert_days_before_expiry']);
			$default_values['Alert'] = "$alert_days days before expiry";
			$time = intval($reminder_details['reminder_time']);
			$hour = intval($time/60);
			$minute = $time%60;
			$meridiem = ($hour<12)?'AM':'PM';
			$hour = ($hour>12)?$hour-12:$hour;
			$hour = sprintf( "%02d", $hour);;
			$minute = sprintf("%02d", $minute);
			$default_values[_campaign("Sending Time")] = "$hour:$minute $meridiem";
			if($reminder_details['is_sms_configured'])
				$default_values[_campaign("SMS Content")] = _campaign("Yes");
			else 
				$default_values[_campaign("SMS Content")] = _campaign("No");
			if($reminder_details['is_email_configured'])
				$default_values['Email Content'] = _campaign("Yes");
			else
				$default_values['Email Content'] = _campaign("No");
		}
		else{
			$default_values['Set Expiry Reminder'] = _campaign("No"); 
		}
		
		return $default_values;
	}
	
	public function setCouponDetailsHtml( $id ){
	
		$content = array();
		$default_values = $this->setCouponValues( $id );
		
		$content['title'] = _campaign("Coupon Series Details of")." ".$default_values['description'];
		
		$str = "";
		$headers = array_keys( $default_values );
	
		for ( $i = 0 ; $i < count($default_values) ; $i = $i+2 ){
					
			if( $i%4 == 0 )
				$str .="<tr class='gradeC even remove_display_style'>";
			else
				$str .="<tr class='gradeC odd'>";
			
			$loop_count = 0;
			for( $j = $i ; $j < $i + 2 ; $j++ ){

				if( $loop_count++ == 1 )
					$str .= "<td></td>";

				if( $j%2 == 0 ){
					if(strlen(trim($headers[$j]))>0){
						$str.="<td class='bold text-right'>"._coupon(Util::beautify( $headers[$j] ))."</td>";
					} else{
						$str.="<td class='bold text-right'>".Util::beautify( $headers[$j] )."</td>";

					}
				}
				else{
					if(strlen(trim($headers[$j]))>0){
						$str.="<td class='bold'>"._coupon(Util::beautify( $headers[$j] ))."</td>";
					} else{
						$str.="<td class='bold'>".Util::beautify( $headers[$j] )."</td>";

					}

				}
						
				$value = $default_values[ $headers[$j] ];
				$str.="<td >$value</td>";
			}

			$str.="</tr>";
		}
		$str .= "<tr><th scope='col' colspan='5'></th></tr>";
		
		$content['body'] = $str;
		return $content;
	}
	
	/**
	 * create date filter for campaign selection for duplication 
	 */
	public function getDateFilter( $from, $to ){
	
		$filter = " AND ( `start_date` >= '".$from."' AND `start_date` < '".$to."' )";
		return $filter;
	}
	
	/**
	 * campaign list for duplication 
	 */
	public function createListForDuplication($type, $where, $search, $limit ){
	
		$results = $this->campaign_model_extension->createListForDuplication(
				$this->org_id, $type, $where, $search, $limit );
		$table_content = array();
	
		foreach( $results as $key => $value ){
	
			$value["campaign_name"] = Util::beautify( $value["campaign_name"] );
			$value[ 'start_date' ] = date( 'd M Y', strtotime( $value[ 'start_date' ] ) );
			$value[ 'end_date' ] = date( 'd M Y', strtotime( $value[ 'end_date' ] ) );
	
			array_push($table_content, $value);
		}
	
		return $table_content;
	}
	
	/**
	 * duplicate uploaded customer list 
	 * @param unknown $params
	 */
	public function duplicateUploadedList( $params ){
	
		try{
			$group_id = $params[0];
			$campaign_id = $params[1];
			$group_name = rawurldecode( $params[2] );
	
			$C_transaction_manager = new TransactionManager();
			$C_transaction_manager->beginTransaction();
	
			$this->campaign_model_extension->isGroupNameExists( $group_name, $campaign_id );
	
			$C_bucket_handler = new CampaignGroupBucketHandler( $group_id );
			$temp_table_name = $C_bucket_handler->createTempTableForDuplication();
			$this->logger->debug('file name for duplicate file: '.$temp_table_name );
	
			$new_group_id = $this->campaign_model_extension->insertGroupDetails(
					$campaign_id , $group_name );
			$this->logger->debug('group id for duplicate group: '.$new_group_id );
	
			$C_bucket_handler = new CampaignGroupBucketHandler( $new_group_id );
			$C_bucket_handler->duplicateCustomerList( $temp_table_name );
	
			$provider_type = 'duplicate_list';
			$audience_group_id = $this->campaign_model_extension->insertAudienceGroups(
					$campaign_id , $provider_type , $new_group_id );
	
			$this->CampaignGroup->updateGroupMetaInfo( $new_group_id );
			$this->logger->debug('group meta info updated '.$new_group_id );
	
			$C_bucket_handler->dropTempTable( $temp_table_name );
	
			$C_transaction_manager->commitTransaction();
			$this->updateReachability($campaign_id,$new_group_id);
			return 'success';
	
		}catch (Exception $e){
	
			$C_transaction_manager->rollbackTransaction();
			$this->logger->error("ROLLING BACK Exception: ".$e->getMessage() );
			return $e->getMessage();
		}
	}
	
	/**
	 * duplicate customer list which were created using filters
	 * @param unknown $params
	 * @return string
	 */
	public function duplicateFilterList( $params ){
	
		try{
			$this->logger->debug('duplicating filter list: '.print_r( $params, true ) );
			$group_id = $params[0];
			$campaign_id = $params[1];
			$group_name = rawurldecode( $params[2] );
			$audience_group_type = strtoupper($params[4]);
				
			$C_transaction_manager = new TransactionManager();
			$C_transaction_manager->beginTransaction();

			$C_group_detail = new GroupDetailModel();
			$C_base_filter = new BaseFilter();
			$C_base_filter->initArgs( $campaign_id, false, $audience_group_type );
			
			//check if group name exist
			$this->campaign_model_extension->isGroupNameExists( $group_name, $campaign_id );
				
			//get peer( control list ) id 
			$peer_details = $this->campaign_model_extension->getGroupDetails( $group_id );
			$peer_id = $peer_details['peer_group_id'];
			$peer_name = $group_name.' (control)';
			$this->logger->debug('group peer id: '.$peer_id );

			//duplicate test group
			$audience_group_id = $C_base_filter->createAudienceGroup();
			$this->logger->debug('duplicate test audience: '.$audience_group_id );
						
			$C_base_filter->initArgs( $campaign_id, $audience_group_id, $audience_group_type, true );
			$C_base_filter->createFiltersByGroupId( $group_id );
			$test_id = $this->campaign_model_extension->insertGroupDetails(
										$campaign_id, $group_name, $audience_group_type );
			$C_base_filter->updateAudienceGroupParams( $audience_group_id, $test_id );
			$this->logger->debug('duplicate test list created: '.$test_id );
			
			//duplicate controll group
			$audience_group_id = $C_base_filter->createAudienceGroup();
			$this->logger->debug('duplicate control audience: '.$audience_group_id );
			
			$C_base_filter->initArgs( $campaign_id, $audience_group_id, $audience_group_type, true );
			$C_base_filter->createFiltersByGroupId( $peer_id );
			$control_id = $this->campaign_model_extension->insertGroupDetails(
							$campaign_id, $peer_name, $audience_group_type, 0, 'CONTROL');
			$C_base_filter->updateAudienceGroupParams( $audience_group_id, $control_id );
			$this->logger->debug('duplicate control list created: '.$test_id );
			
			//update groups peer id
			$C_group_detail->load( $test_id );
			$C_group_detail->setPeerGroupID( $control_id );
			$C_group_detail->update( $test_id );
			
			$C_group_detail->load( $control_id );
			$C_group_detail->setPeerGroupID( $test_id );
			$C_group_detail->update( $control_id );
			
			$new_groups = array( 'test' => $test_id, 'control' => $control_id );
			$C_transaction_manager->commitTransaction();
			return $new_groups;
				
		}catch( Exception $e ){
				
			$C_transaction_manager->rollbackTransaction();
			$this->logger->error("ROLLING BACK Exception: ".$e->getMessage() );
			return $e->getMessage();
		}
	}
	
	public function refreshDuplicateList( $params ){
			
		$group_ids = json_decode( $params[0], true );
		$campaign_id = $params[1];
		$this->logger->debug('refreshing group for : '.print_r($group_ids, true) );
		
		try{
			$C_base_filter = new BaseFilter();
			
			foreach ( $group_ids as $type => $id ){
				
				$this->logger->debug('refreshing group id: '.$id );
				$details =  $C_base_filter->getAudienceGroupByGroupId( $id );
				$audience_group_id = $details['id'];
				$C_base_filter->initArgs( $campaign_id, $audience_group_id, true );
				$this->logger->debug('refreshing group for audience: '.$audience_group_id );
				$res = $C_base_filter->refreshGroup( $id );
				if($strtolower($type)=='test')
					$this->updateReachability($campaign_id,$id);
			}
			return 'success';
			
		}catch (Exception $e){
			
			$this->logger->debug('reload error: '.$e->getMessage());
			return $e->getMessage();
		}
	}

	public function getListDetailsByGroupId( $campaign_id, $group_id ){

		$list = array();
		$group_details = $this->getGroupDetails( $group_id );
		$audience_details = 
				$this->getAudienceDetailsByGroupId( $campaign_id, $group_id );
		
		$audience_id = $audience_details['id'];
		$filter_details = $this->getFilterDataByAudienceId( $audience_id );
		$this->logger->debug('filter details: '.print_r( $filter_details, true ) );
				
		if( $group_details['type'] == 'loyalty' || strtolower( $group_details['type'] ) == 'non_loyalty'  ){
		
			if( count( $filter_details ) < 1 ){
				array_push( $list, _campaign("All registered customers") );
			}
			foreach( $filter_details as $det ){
		
				array_push( $list, $det['filter_explaination']." ( ".$det['no_of_customers']." ) " );
			}
		}else{
			if( $filter_details[0]['filter_type'] == 'test_control' ){
		
				$name = Util::templateReplace( $filter_details[0]['filter_explaination'],
						array( 'css_start' => "<b style='color:green;'>",
								'css_end' => "</b>" ) );
				array_push( $list, $name );
			}else{
				$name = _campaign("Customers uploaded.");
				array_push( $list, $name );
			}
		}
		$this->logger->debug('filter explanation: '.print_r( $list, true ) );
		return $list;
	}
	
	/**
	 * Fetches the data for a campaign (if any) specified
	 * by the orgId and campaignId
	 * @param unknown $org_id
	 * @param unknown $campaign_id
	 */
	public function getCampaignScheduleData( $org_id, $campaign_id ){
		
		$this->logger->debug('get campaign schedule data: '.$campaign_id);
		return $this->client->getCampaignScheduleData( $org_id, $campaign_id );
	}
	
	/**
	 * Call to get the org-level campaign metadata
	 * @param unknown $org_id
	 */
	public function getCampaignScheduleMetadata( $org_id ){
		
		$this->logger->debug('get campaign schedule meta data: '.$org_id);
		return $this->client->getCampaignScheduleMetadata( $org_id );
	}
	
	/**
	 * Call to set the org-level campaign metadata
	 * @param unknown $params
	 */
	public function setCampaignScheduleMetadata( $params ){
	
		$this->logger->debug('set schedule meta data: '.print_r($params, true) );
		
		$params['cap_poc'] = array_values( $params['cap_poc'] );
		$params['org_poc'] = array_values( $params['org_poc'] );
		
		$metaObj = $this->client->createScheduleMetaDataObj( $params['roi'],
					$params['delivery'], $params['cap_poc'], $params['org_poc'] );
		
		$res = $this->client->setCampaignScheduleMetadata( $params['org_id'], $metaObj );
		$this->logger->debug('result set camp sche data: '.print_r($res, true) );
		if( $res && $res->isSuccess == 1 )
			return true;
		else
			return false;
	}
	
	/**
	 * Starts report scheduling for a campaign.If a schedule already exists,
	 * it is activated. Otherwise, a new schedule is created.
	 * @param unknown $org_id
	 * @param unknown $campaign_id
	 */
	public function createCampaignReportSchedule( $campaign_id ){
		
		$this->logger->debug('campaign report schedule: '.$campaign_id);

		$this->load( $campaign_id );
		$details = $this->getDetails();
		$this->logger->debug('campaign report type: '.print_r($details, true));
		$end_date = strtotime( $details['end_date'] )*1000;
		$is_active = false;
		if( $details['active'] == 1 )
			$is_active = true;
		
		$campaign_type = $this->client->getCampaignType('BULK');
		
		$schedule_params['campaignId'] = $campaign_id;
		$schedule_params['orgId'] = $this->org_id;
		$schedule_params['campaignType'] = $campaign_type;
		$schedule_params['campaignEndDate'] = $end_date;
		
		$schedule_data = 
			$this->client->createCampaignScheduleDataObj( $schedule_params );	
		
		$res = $this->client->createCampaignReportSchedule( $schedule_data );
		$this->logger->debug('campaign schedule result: '.print_r($res, true));
		
		if( $res->isSuccess == 1 )
			return 'success';
		else
			return _campaign("Error scheduling reports");
	}
		
	/**
	 * Call when the end date of a campaign has changed
	 * @param unknown $org_id
	 * @param unknown $campaign_id
	 * @param unknown $new_end_date
	 */
	public function updateCampaignReportSchedule($org_id, $campaign_id, $new_end_date){
		
		$this->logger->debug('update campaign schedule: '.$campaign_id);
		return $this->client->onCampaignEndDateChange( $org_id,
										$campaign_id, $new_end_date );
	}
	
	/**
	 * activate campaign schedule for the org
	 * @param unknown $org_id
	 */
	public function enableCampaignSchedulesForOrg( $org_id ){
		
		$this->logger->debug('active campaign schedule: '.$org_id );
		return $this->client->enableCampaignSchedulesForOrg( $org_id );
	}
	
	/**
	 * inactivate campaign schedule for the org
	 * @param unknown $org_id
	 */
	public function disableCampaignSchedulesForOrg( $org_id ){
	
		$this->logger->debug('active campaign schedule: '.$org_id );
		return $this->client->disableCampaignSchedulesForOrg( $org_id );
	}
	
	/**
	 * inactivates schedule for campaign
	 * @param unknown $campaign_id
	 * @param unknown $org_id
	 */
	public function inactivateCampaignReportSchedule( $campaign_id, $org_id ){
		
		$this->logger->debug('inactivates campaign schedule: '.$campaign_id );
		return $this->client->inactivateCampaignReportSchedule( 
											$campaign_id, $org_id );
	}

	/*
	 * it fetches messages before sending test email and sms
	*/
	public function getMessageDetailsForPreview( $type, $params = array() ){
	
		$group_id = $params['group_id'];
		$type = strtoupper($type);
		$campaign_id = $params['campaign_id'];
		$extra_params = array();
		$extra_params['segments'] = isset( $params['segments'] ) ? $params['segments'] : ""; //segment info used for Timeline email preview
		$msg = $params["message"];

		if ( $type == 'EMAIL' ){
			$msg_subject = $params["subject"];
		}else{
			$msg_subject = false;
		}
	
		$messages = $this->getUsersPreviewTable( $group_id, $campaign_id, $msg , $msg_subject , $type, $extra_params );
		
		if( empty( $messages ) ){
			$messages = array( "msg" => $msg , "subject" => $msg_subject , "description" => $description , "to" => false );
		}else{
			$messages = $messages[0];
			$messages["description"] = $description;
		}
		return $messages;
	}
	
	public function getCallTaskForCampaign( $campaign_id ) {
		
		$campaign_ids = implode( ",", $campaign_id );
		
		$call_tasks = $this->campaign_model_extension->
				getCallTaskForCampaign( $campaign_ids, $this->org_id );
		
		return $call_tasks;
	}
	
	public function getTaskIdForMsgIds( $message_ids ) {
		
		$message_id_csv = implode( ",", $message_ids );
		
		$task_ids = $this->campaign_model_extension->
				getTaskIdForMsgIds( $message_id_csv, $this->org_id );
		
		return $task_ids;
	}
	
	public function getMessagesByCampaign(
			$campaign_ids_array, $message_type ) {
		
		$campaign_ids = implode( ",", $campaign_ids_array );
		
		$messages = $this->campaign_model_extension->
				getMessagesByCampaign( $campaign_ids, $message_type,
				$this->org_id );
		
		return $messages;
	}
	
	public function getMessageCampaignIdHash( $message_ids ) {
		
		return $this->campaign_model->
				getMessageCampaignIdHash( $message_ids, $this->org_id );
	}
	
	public function getMessageSubjectById( $message_ids ) {
		
		$message_id_csv = implode( ",", $message_ids );

		return $this->campaign_model_extension->
				getMessageSubjectById( $message_id_csv, $this->org_id );
	}

	public function getRoiTypes(){
		return $this->campaign_model_extension->getRoiTypes();
	}

	public function getCouponProductDetails(){
		$C_coupon_product_manager = new CouponProductManager();
		$brand_result = $C_coupon_product_manager->getProductBrandValues('root',$vs_id);
		$brands = $brand_result["items"];
		$categories = $C_coupon_product_manager->getSelectedProductCategoryHierarchy($vs_id);
		$validity = $C_coupon_product_manager->isProductSelected($vs_id);
		$voucher_validity = array("valid"=>$validity,"brand"=>$brands,"category"=>$categories);
	}

	/*
		function to check if there is atleast 1 authorised message containing {{voucher}} tag
	*/
	private function getCouponBasedAuthorisedMsg($campaign_id){
		
		$result = $this->campaign_model_extension->getApprovedMsgDetailsByCampaignId($campaign_id) ;
		
		$voucher_tag = "{{voucher}}" ;
		foreach($result as $msgQueueRow){
			$msg = json_decode($msgQueueRow['params'] , true) ;
			
			if(strpos($msg['message'],$voucher_tag ) !== false){
				return true ;
			}
		}

		return false ;
	}

	// function to check if there is atleast 1 authorised message
	private function getAuthorisedMsg($campaign_id){
		$result = $this->campaign_model_extension->getApprovedMsgDetailsByCampaignId($campaign_id) ;
		
		if(count($result) > 0 )
			return true ;
		
		return false ;
	}

	//calls relevant function based on the $type
	public function checkAuthorisedMessageByType($campaign_id , $type='campaign'){

		$type = strtolower($type) ;
		$C_config_manager = new ConfigManager();
		
		if( $C_config_manager->getKey(CONF_CAMPAIGN_STRICT_ACTIVE_CHECK_ENABLED)  ){
			switch ($type) {
				case "coupon" :
					return $this->getCouponBasedAuthorisedMsg($campaign_id) ;

				case "campaign" :
					return $this->getAuthorisedMsg($campaign_id) ;

			}
		}
		
		return false ;
	}

	public function getReachableCustomer($campaign_id , $channel , $gid='value'){


		$this->logger->debug('@@@Inside getReachableCustomer Params:');
		include_once 'business_controller/ReachabilityController.php';
		$this->reach_controller = new ReachabilityController();
		$reach_response_mode = $this->reach_controller->
						getReachabilityProgressByCampaignID($campaign_id, $channel);

		include_once 'business_controller/wechat/WeChatAccountController.php';
		$wechat_account_controller = new WeChatAccountController($org_id);
		$service_info = $wechat_account_controller->get_all_accounts_by_org();

		//$this->logger->debug('@@@Inside reach Params:'.print_r($reach_response_mode,true));

		$group_ids = array();
		$sticky_array = array();
		$all_groups = array();

		if( !$reach_response_mode){
			return;
		}

		foreach ( $reach_response_mode->recipient_list as $row ){
			
			//adding dummy data to mobile push
			$json = json_encode(array(
			"channel" =>  array("IOS"=>array("subscription_count"=> "25",
											"unsubscription_count"=> "15",
									    	 "unavailable_count"=> "10"),

					      		"Android"=>array("subscription_count"=> "12",
												"unsubscription_count"=> "6",
												"unavailable_count"=> "2")
			)));

			$group = array();
			$group['totalCustomerCount'] = $row->totalCustomerCount;
			$group['group_id'] = $row->groupId;
			$group['is_favourite'] = $row->isFavourite;
			$group['test_count'] = $row->testCount;
			$group['control_count'] = $row->controlCount;
			$group['email_count'] = $row->emailCount;
			$group['mobile_count'] = $row->mobileCount;
			$group['type'] = $row->groupType;
			$group['group_label'] = $row->groupLabel;
			$group['isPrevious'] = $row->isPrevious;
			$group['wechatOpenIdCount'] = $row->wechatOpenIdcount;
			$group['wechat_accounts'] = $service_info;

			//adding mobile push account details
			
			$androidBreakup = array();
			$iosBreakup = array();
			$mobilePush = json_decode($json);

			$this->logger->debug('The mobilePush object in getReachableCustomer is : '.print_r($mobilePush->channel,true));
			
			$iosBreakup["subscription_count"] = $mobilePush->channel->IOS->subscription_count;
			$iosBreakup["unsubscription_count"] = $mobilePush->channel->IOS->unsubscription_count;
			$iosBreakup["unavailable_count"] = $mobilePush->channel->IOS->unavailable_count;
		
		
			$androidBreakup["subscription_count"] = $mobilePush->channel->Android->subscription_count;
			$androidBreakup["unsubscription_count"] = $mobilePush->channel->Android->unsubscription_count;
			$androidBreakup["unavailable_count"] = $mobilePush->channel->Android->unavailable_count;
				
			
			$this->logger->debug('The androidBreakup object in getReachableCustomer is : '.print_r($androidBreakup,true));
			$this->logger->debug('The ios object in getReachableCustomer is : '.print_r($iosBreakup,true));
			$group["androidBreakup"] = $androidBreakup;
			$group["iosBreakup"] = $iosBreakup;

			$emailBreakup = array();
			foreach ( $row->emailBreakup as $row1 ){
				if($row1->sendingRules=='VALID')
					$emailBreakup['valid'] = $row1->count;
				if($row1->sendingRules=='SOFTBOUNCED')
					$emailBreakup['soft_bounced'] = $row1->count;
				if($row1->sendingRules=='INVALID')
					$emailBreakup['invalid'] = $row1->count;
				if($row1->sendingRules=='HARDBOUNCED')	
					$emailBreakup['invalid'] += $row1->count;
				if($row1->sendingRules=='CONTACT_UNAVAILABLE')
					$emailBreakup['not_available'] = $row1->count;
				if($row1->sendingRules=='UNSUBCRIBED')
					$emailBreakup['unsubscribed'] = $row1->count;
				if($row1->sendingRules=='UNABLE_TO_VERIFY')
					$emailBreakup['unable_email'] = $row1->count;
				if($row1->sendingRules=='UNVERIFIED')
					$emailBreakup['unverify_email'] = $row1->count;
			}
			$group['emailBreakup'] = $emailBreakup;
			$mobileBreakup = array();
			foreach ( $row->mobileBreakup as $row2 ){
				if($row2->sendingRules=='VALID')
					$mobileBreakup['valid'] = $row2->count;
				if($row2->sendingRules=='INVALID')	
					$mobileBreakup['invalid'] = $row2->count;
				if($row2->sendingRules=='HARDBOUNCED')	
					$mobileBreakup['invalid'] += $row2->count;
				if($row2->sendingRules=='SOFTBOUNCED')	
					$mobileBreakup['invalid'] += $row2->count;
				if($row2->sendingRules=='CONTACT_UNAVAILABLE'){	
					$mobileBreakup['invalid'] += $row2->count;
					$mobileBreakup['unavailable_mobile'] = $row2->count;
				}
				if($row2->sendingRules=='UNSUBCRIBED')
					$mobileBreakup['unsubscribed'] = $row2->count;
				if($row2->sendingRules=='UNABLE_TO_VERIFY')
					$mobileBreakup['unable_mobile'] = $row2->count;
				if($row2->sendingRules=='UNVERIFIED')
					$mobileBreakup['unverify_mobile'] = $row2->count;
			}
			$group['mobileBreakup'] = $mobileBreakup;
			$all_groups[$row->groupId] = $group;
			array_push( $group_ids, $row->groupId);
		}

		//$this->logger->debug('allGroups:'.print_r($all_groups,true));

		//$this->logger->debug('allGroups:'.print_r($all_groups['100507']['emailBreakup']['soft_bounced'],true));

		$filter_details =  $this->getFilterDataByGroupIds($group_ids, $campaign_id);

		$filter_detail = array();
		foreach ( $filter_details as $row ){
			
			if( !is_array( $filter_detail[$row['group_ids']]) ){
				$filter_detail[$row['group_ids']] = array();
			}
			array_push( $filter_detail[$row['group_ids']], $row);
		}
		
		foreach( $reach_response_mode->recipient_list as $row ) {
			if($gid!='value'){
				$group_id = $gid;
				$group_details = $all_groups[$group_id];
				$this->logger->debug('SingleCAllCheck:'.print_r($group_details,true));
			}else{
				$group_id = $row->groupId;
				$group_details = $all_groups[$group_id];
			}
			

			//$this->logger->debug('@@@group_details: ',print_r($all_groups[$group_details],true));
			//$this->logger->debug('@@@group_details: ',print_r($group_details['emailBreakup']['valid'],true));
			
			if( $group_id ){
				if( !isset( $group_details['group_id'] ) ) continue;
				$auto_generated_group = 'auto_gen_expiry_reminder_group_';
				if(strncmp($group_details['group_label'],$auto_generated_group,strlen($auto_generated_group))===0)
					continue;
				

				$control_users = (int) $group_details['control_count'];

				$total_email = $group_details['email_count'];
				$test_count = $group_details['test_count'];
				$wechatOpenIdCount = $group_details['wechatOpenIdCount'];
				$wechat_accounts = $group_details['wechat_accounts'];
				$valid_email = $group_details['emailBreakup']['valid'];
				$soft_bounced_email = $group_details['emailBreakup']['soft_bounced'];
				$invalid_email = $group_details['emailBreakup']['invalid'];
				$unavailable_email = $group_details['emailBreakup']['not_available'];
				$unsubscribed_email = $group_details['emailBreakup']['unsubscribed'];
				$unable_email = $group_details['emailBreakup']['unable_email'];
				$unverify_email = $group_details['emailBreakup']['unverify_email'];
				$reachable_email = $valid_email + $soft_bounced_email + $unable_email;
				$unreachable_email = $invalid_email + $unavailable_email + $unsubscribed_email + $unverify_email ;
				$yet_verifying_email = $test_count - $reachable_email - $unreachable_email ;
				if($yet_verifying_email < 0)
					$yet_verifying_email = 0;
				if($total_email != 0)
					$percentage_email =  round(( $yet_verifying_email / $total_email ) * 100) ;
				else
					$percentage_email = 0;

				$androidCount = $group_details['androidCount'];
				$iosCount = $group_details['iosCount'];

				$total_mobile = $group_details['mobile_count'];
				$valid_mobile = $group_details['mobileBreakup']['valid'];
				$invalid_mobile = $group_details['mobileBreakup']['invalid'];
				$unsubscribed_mobile = $group_details['mobileBreakup']['unsubscribed'];
				$unavailable_mobile = $group_details['mobileBreakup']['unavailable_mobile'];
				$unable_mobile = $group_details['mobileBreakup']['unable_mobile'];
				$unverify_mobile = $group_details['mobileBreakup']['unverify_mobile'];
				$reachable_mobile = $valid_mobile + $unable_mobile;
				$unreachable_mobile = $invalid_mobile + $unsubscribed_mobile + $unverify_mobile;
				$yet_verifying_mobile = $total_mobile - $reachable_mobile - $unreachable_mobile + $unavailable_mobile;
				if($yet_verifying_mobile < 0)
					$yet_verifying_mobile = 0;
				if($total_mobile != 0)
					$percentage_mobile = round(( $yet_verifying_mobile / $total_mobile ) * 100) ;
				else
					$percentage_mobile = 0;

				$total_mobile_push_can_contact = $group["androidBreakup"]["subscription_count"] + 
				$group["iosBreakup"]["subscription_count"];
				$group["iosBreakup"]["subscription_count"];
				$total_android_can_contact = $group_details["androidBreakup"]["subscription_count"];
				$total_ios_can_contact = $group_details["iosBreakup"]["subscription_count"];

				$subscribed_android = $group_details["androidBreakup"]["subscription_count"];
				$unsubscribed_android = $group_details["androidBreakup"]["unsubscription_count"];
				$unavailable_android = $group_details["androidBreakup"]["unavailable_count"];
				
				$subscribed_ios = $group_details["iosBreakup"]["subscription_count"];
				$unsubscribed_ios = $group_details["iosBreakup"]["unsubscription_count"];
				$unavailable_ios = $group_details["iosBreakup"]["unavailable_count"];

				$mobile_push_total_subscribed = $group["androidBreakup"]["subscription_count"] + 
				$group["iosBreakup"]["subscription_count"];
				$mobile_push_total_unsubscribed = $group["androidBreakup"]["unsubscription_count"] + $group["iosBreakup"]["unsubscription_count"];
				$mobile_push_total_unavailable = $unavailable_ios + $unavailable_android;

				$total_android_cannot_contact = $group["androidBreakup"]["unsubscription_count"] + $group["androidBreakup"]["unavailable_count"];
				$total_ios_cannot_contact = $group["iosBreakup"]["unsubscription_count"] + 
				$group["iosBreakup"]["unavailable_count"];

				/*"channel" =>  array("IOS"=>array("subscription_count"=> "25",
											"unsubscription_count"=> "15",
									    	 "unavailable_count"=> "10"),

					      		"Android"=>array("subscription_count"=> "12",
												"unsubscription_count"=> "6",
												"unavailable_count"=> "2")*/

				$this->logger->debug('The total_ios_cannot_contact is : '.$total_ios_cannot_contact);
				$total_mobile_push_cannot_contact = $total_ios_cannot_contact + $total_android_cannot_contact;

				
				
				if( $control_users > 0 ){

					$count = $group_details['totalCustomerCount'];
					$test_users = (int) $group_details['test_count'];
					// $test_users = (int) $group_details['test_count'];
					$control_users = (int) $group_details['control_count'];
					//If Both the test and controll user are zero then total client must be zero.
					if( $test_users == 0 && $control_users == 0 )
						$count = 0;

					$sticky_array[$group_id]['count'] = $count;
					$sticky_array[$group_id]['test'] = $test_users;
					$sticky_array[$group_id]['control'] = $control_users ? $control_users : 0;
					$sticky_array[$group_id]['type'] = $group_details['type'];
				}else{
					
					$test_users = (int) $group_details['test_count'];
					$count = $group_details['totalCustomerCount'];
					$sticky_array[$group_id]['test'] = $test_users;
					$sticky_array[$group_id]['control'] = $control_users ? $control_users : 0;
					$sticky_array[$group_id]['count'] = $count;
					$sticky_array[$group_id]['type'] = $group_details['type'];
				}
				
				$sticky_array[$group_id] =
					$this->getSelectionFilterDetailsv2($filter_detail[$group_id], $sticky_array[$group_id] );
				
				$sticky_array[$group_id]['campaign_id'] = $campaign_id;
				$sticky_array[$group_id]['group_label'] = $group_details['group_label'];
				$sticky_array[$group_id]['email'] = $total_email ? $total_email : 0;
				$sticky_array[$group_id]['mobile'] = $total_mobile ? $total_mobile : 0;
				$sticky_array[$group_id]['is_favourite'] = $group_details['is_favourite'];
				$sticky_array[$group_id]['id'] = $group_id;
				$sticky_array[$group_id]['group_id'] = $group_id;

				

				$sticky_array[$group_id]['reachable_email'] = $reachable_email ? $reachable_email:0;
				$sticky_array[$group_id]['valid_email'] = $valid_email ? $valid_email :0;
				$sticky_array[$group_id]['soft_bounced_email'] = $soft_bounced_email ? $soft_bounced_email : 0;
				$sticky_array[$group_id]['yet_verifying_email'] = $yet_verifying_email ? $yet_verifying_email:0;
				$sticky_array[$group_id]['percentage_email'] = $percentage_email;
				$sticky_array[$group_id]['unreachable_email'] = $unreachable_email ? $unreachable_email : 0;
				$sticky_array[$group_id]['invalid_email'] = $invalid_email ? $invalid_email : 0;
				$sticky_array[$group_id]['unavailable_email'] = $unavailable_email ? $unavailable_email : 0;
				$sticky_array[$group_id]['unsubscribed_email'] = $unsubscribed_email ? $unsubscribed_email : 0;
				$sticky_array[$group_id]['unable_email'] = $unable_email ? $unable_email : 0;
				$sticky_array[$group_id]['unverify_email'] = $unverify_email ? $unverify_email : 0;

				$sticky_array[$group_id]['reachable_mobile'] = $reachable_mobile ? $reachable_mobile :0;
				$sticky_array[$group_id]['valid_mobile'] = $valid_mobile ? $valid_mobile : 0;
				$sticky_array[$group_id]['yet_verifying_mobile'] = $yet_verifying_mobile ? $yet_verifying_mobile:0;
				$sticky_array[$group_id]['percentage_mobile'] = $percentage_mobile;
				$sticky_array[$group_id]['unreachable_mobile'] = $unreachable_mobile ? $unreachable_mobile : 0;
				$sticky_array[$group_id]['invalid_mobile'] = $invalid_mobile ? $invalid_mobile : 0;
				$sticky_array[$group_id]['unsubscribed_mobile'] = $unsubscribed_mobile ? $unsubscribed_mobile : 0;
				$sticky_array[$group_id]['unable_mobile'] = $unable_mobile ? $unable_mobile : 0;
				$sticky_array[$group_id]['unverify_mobile'] = $unverify_mobile ? $unverify_mobile : 0;

				$sticky_array[$group_id]['wechatOpenIdCount'] = $wechatOpenIdCount ? $wechatOpenIdCount:0;
				$sticky_array[$group_id]['type'] = $group_details['type'];
				$sticky_array[$group_id]['isPrevious'] = $group_details['isPrevious'];
				$sticky_array[$group_id]['wechat_accounts'] = $wechat_accounts;


				$sticky_array[$group_id]["totalMobilePushCanContact"] = $total_mobile_push_can_contact ? $total_mobile_push_can_contact:0;
				$sticky_array[$group_id]["totalAndroidCanContact"] = $total_android_can_contact ? $total_android_can_contact:0;
				$sticky_array[$group_id]["totalIOSCanContact"] = $total_ios_can_contact ? $total_ios_can_contact:0;

				$sticky_array[$group_id]['androidSubscriptionCount'] = $subscribed_android ? $subscribed_android:0;
				$sticky_array[$group_id]['androidUnsubscriptionCount'] = $unsubscribed_android ? $unsubscribed_android:0;
				$sticky_array[$group_id]['androidUnavailableCount'] = $unavailable_android ? $unavailable_android:0;

				$sticky_array[$group_id]['iosSubscriptionCount'] = $subscribed_ios ? $subscribed_ios:0;
				$sticky_array[$group_id]['iosUnsubscriptionCount'] = $unsubscribed_ios ? $unsubscribed_ios:0;
				$sticky_array[$group_id]['iosUnavailableCount'] = $unavailable_ios ? $unavailable_ios:0;
				$sticky_array[$group_id]['mobilePushTotalSubscribed'] = $mobile_push_total_subscribed ? $mobile_push_total_subscribed
				:0;
				$sticky_array[$group_id]['mobilePushTotalUnsubscribed'] = $mobile_push_total_unsubscribed ? $mobile_push_total_unsubscribed
				:0;
				$sticky_array[$group_id]['mobilePushTotalUnavailable'] = $mobile_push_total_unavailable ? $mobile_push_total_unavailable
				:0;
				$sticky_array[$group_id]["totalMobilePushCannotContact"] = $total_mobile_push_cannot_contact ? $total_mobile_push_cannot_contact:0;
				$sticky_array[$group_id]["totalAndroidCannotContact"] = $total_android_cannot_contact ? $total_android_cannot_contact:0;
				$sticky_array[$group_id]["totalIOSCannotContact"] = $total_ios_cannot_contact ? $total_ios_cannot_contact:0;
				$sticky_array[$group_id]["androidCount"] = $total_android_cannot_contact + $total_android_can_contact; 
				$sticky_array[$group_id]["iosCount"] = $total_ios_cannot_contact + $total_ios_can_contact;

			}
		}
		$group_details = $this->getGroupDetailsbyGroupIds( $group_ids ) ;

		foreach ($group_details as $key => $group) {
			$channels = json_decode($group['params'],true) ;
			$android = $channels["android"];
			$ios = $channels["ios"];
			$androidCount = 0 ;
			$iosCount = 0 ;
			foreach($android as $count){
				$androidCount += $count ; 
			}

			foreach($ios as $count){
				$iosCount += $count ; 
			}

			$sticky_array[$group['group_id']]["iosCount"] = $iosCount ;
			$sticky_array[$group['group_id']]["androidCount"] = $androidCount ;
		}
		//$this->logger->debug('@@@Group Sticky List:-'.print_r( $sticky_array , true ));
		return $sticky_array;
	}

	private function getSelectionFilterDetailsv2( $filter_details, $group_details ){
		
		$group_details['name'] = array( );
		if( strtolower( $group_details['type'] ) == 'loyalty' || strtolower( $group_details['type'] ) == 'non_loyalty' ){

			if( count( $filter_details ) < 1 ){
				
				array_push( $group_details['name'], _campaign("All registered customers") );
			}
			
			foreach( $filter_details as $filter_detail ){
				
				array_push( $group_details['name'], $filter_detail['filter_explaination']." ( ".$filter_detail['no_of_customers']." ) " );
			}
		
		}else{
		
			if( $filter_details['filter_type'] == 'test_control' ){
		
				$name = Util::templateReplace( $filter_details['filter_explaination'],
						array( 'css_start' => "<b style='color:green;'>",
								'css_end' => "</b>" ) );
				array_push( $group_details['name'], $name );
			}else{
		
				$name = _campaign("Customers uploaded.");
				array_push( $group_details['name'], $name );
			}
		}
		
		return $group_details;		
	}
	
}
?>
