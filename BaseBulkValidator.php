<?php

include_once 'helper/Reminder.php';
include_once 'helper/coupons/CouponSeriesManager.php';
include_once 'helper/coupons/CouponManager.php';
include_once 'helper/scheduler/CampaignGroupCallBack.php';
include_once 'helper/scheduler/VoucherReminderCallBack.php';
include_once 'base_model/campaigns/class.BulkMessage.php';
include_once 'model_extension/campaigns/class.CampaignModelExtension.php';
include_once 'helper/ConfigManager.php';
include_once 'SubscriptionService/thrift/subscriptionservice.php';
include_once 'business_controller/points/PointsEngineServiceController.php';

/**
 *
 * Base bulk validator class
 * @author nayan
 *
 */
abstract class BaseBulkValidator{

	const FORMAT_1 = "m/d/Y";
	const FORMAT_2 = "d/m/Y";
	const FORMAT_3 = "Y-m-d";
	const FORMAT_4 = "m/d/y";
	const FORMAT_5 = "d M Y";
	const FORMAT_6 = "D, M d, 'y";
	const FORMAT_7 = "d.m.y";
	const FORMAT_8 = "d, M";

	protected $org;
	protected $data;
	protected $org_id;
	protected $logger;
	protected $user_id;
	protected $C_campaign_controller;
	protected $C_campaign_model_extension;
	protected $C_config_mgr;
	protected $msg_id;
	protected $campaign_id;

	private $validator_type;

	public function __construct( $validator_type ){

		global $currentorg,$logger,$currentuser, $data;

		$this->data = $data;
		$this->logger = $logger;
		$this->org = $currentorg;

		$this->org_id = $currentorg->org_id;
		$this->user_id = $currentuser->user_id;
		$this->validator_type = $validator_type;

		$this->C_campaign_controller = new CampaignController();
		$this->C_campaign_model_extension = new CampaignModelExtension();
		$this->C_config_mgr = new ConfigManager($this->org_id);
	}

	/**
	 * it will give date based on sending type for bulk blast
	 * @param BulkMessage $C_message
	 * @param string $readable
	 * @return string
	 */
	private function getDateBySendingTypeForBulkBlast( BulkMessage $C_message , $readable = true ){

		$send_type = $C_message->getScheduledType();

		$this->logger->info( '@@Sending Time Send When : '.$send_type );

		switch( $send_type ){

			case 'SCHEDULE' :

				return 'RECURRING';

			case 'PARTICULAR_DATE' :

				$send_date = $C_message->getDateField();
				$hour = $C_message->getHours();
				$minute = $C_message->getMinutes();
				$seconds = "00";
				if( $readable )
					$date = date( 'dS M Y' , strtotime( $send_date ) );
				else
					$date = $send_date;

				$this->logger->info( 'date selected '.$date );
				//time details
				

				$calculated_date = $date.' '.$hour.':'.$minute.':'.$seconds;
				$this->logger->info( 'date calculated '.$calculated_date );
				$calculated_date = Util::convertTimeFromCurrentOrgTimeZone($calculated_date);
				$this->logger->info( 'date calculated after time zone modification'.$calculated_date );
				return $calculated_date;

			case 'IMMEDIATE' :

				return date( 'Y-m-d H:i:s' );
		}
	}

	/**
	 *
	 * It will prepare default arguments for queue message insertion or updation
	 * @param BulkMessage $C_message
	 * @throws Exception
	 * @return array
	 */
	private function prepareDefaultArguments( BulkMessage $C_message ){

		$campaign_id = $C_message->getCampaignId();
		$params = $C_message->getParams();
		$default_arguments = $C_message->getDefaultArguments();
		$this->logger->info( '@@Finish Prepare Default Arguments'.print_r( $default_arguments, true ) );
		
		$default_arguments_options = $this->getDefaultTemplatesOptions();
 
		$default_arguments = empty( $default_arguments ) ? array() : $default_arguments;
		if( !empty( $default_arguments_options ) ){
			foreach( $default_arguments_options as $key => $value ){
				$default_arguments[ $key ] = $value;
			}
		}

		$this->logger->info( '@@Prepare Default Arguments: '.$campaign_id );

		//convert default arguments to json encoded list
		if(count( $default_arguments ) <  1 ){

			$default_arguments = array( 'fullname' => 'CUSTOMER' );
		}

		$type = $C_message->getType();

		if( $type == 'EMAIL' || $type == 'EMAIL_REMINDER' )
			$default_arguments['unsubscribe_label'] =
				$this->C_config_mgr->getKey('CONF_CAMPAIGN_UNSUBSCRIBE_TAG_TEXT');

		$this->C_campaign_model_extension->load( $campaign_id );
		//add voucher series id for issual for vouchers
		$campaign_details = $this->C_campaign_model_extension->getHash();

		$campaign_type = $campaign_details['type'];
		$attached_voucher_series_id = $campaign_details['voucher_series_id'];
		if($params['expiry_reminder']){
			$expiry_reminder_voucher_series_id = $params['voucher_series_id']; 
		}
		else{
			if( $campaign_type == 'outbound' ){
				
				//voucher series id is valid only if voucher tag is attached in message. This is yet to be added to referral and timeline
				if(strpos($params['message'],'{{voucher}}')===false){
					$attached_voucher_series_id = -1;
				}
			}elseif( $campaign_type == 'referral' ){
	
				$attached_voucher_series_id = json_decode( $attached_voucher_series_id, true );
				$attached_voucher_series_id = ( int ) $attached_voucher_series_id['referer'];

			}else if( $campaign_type == 'timeline' ){
				
				$attached_voucher_series_id = $params['series_selected'] ? $params['series_selected'] : -1;
			}else{
	
				throw new Exception( _campaign(" The Campaign Type Is Not Configured To Send Campaign Blast") );
			}
		}

		//supply voucher series id to messaging
		//keeping all objects same
		$default_args = $C_message->getDefaultArguments();
		$default_args['created_by'] = $default_arguments['created_by'] = $this->user_id;
		// if expiry reminder message set voucher_series as voucher_series_id of 
		// voucher series of which reminder to be set 
		if($params['expiry_reminder']){
			$default_args['voucher_series'] =
				$default_arguments['voucher_series'] = "$expiry_reminder_voucher_series_id";
		}
		// else set voucher series of campaign 
		else{
			$default_args['voucher_series'] =
				$default_arguments['voucher_series'] = ( string ) $attached_voucher_series_id;
		}
		$C_message->setDefaultArguments( $default_args );

		//encode it into json format
		$default_arguments = json_encode( $default_arguments );

		$this->logger->info( '@@Finish Prepare Default Arguments'.$default_arguments );

		return $default_arguments;
	}

	public function utf8ize($mixed) {
        if (is_array($mixed)) {
        	foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } else if (is_string ($mixed)) {
           	return utf8_encode($mixed);
        }
        return $mixed;
    }


	/**
	 *
	 * It will set the blast params based on blast type
	 * @param BulkMessage $C_message
	 * @return string
	 */
	private function getQueueParamsByType( BulkMessage $C_message ){

		$this->logger->info( '@@Start of getQueueParamsbyType' );

		$params = $C_message->getParams();
		$blast_params = array();

		if( $C_message->getType() == 'SMS' ){

			$blast_params['message'] = $params['message'];
			$blast_params['max_users'] = $params['max_users'];
			if($C_message->getScheduledType() == 'SCHEDULE')
				$blast_params['max_users'] = $params['max_users'];
// 			$blast_params['sender_label'] = $params['sender_label'];
// 			$blast_params['sender_from'] = $params['sender_from'];
          
		}
		else if( $C_message->getType() == 'CUSTOMER_TASK' ){
			$blast_params = $params;
			unset( $blast_params['is_form_submitted'] );
		}
		elseif( $this->validator_type == 'EMAIL' ){
			
			//form email
			$blast_params['track'] = $params['track'];
			$blast_params['message'] = $params['message'];
			$blast_params['subject'] = $params['subject'];
			$blast_params['signature'] = $params['signature'];
			$blast_params['signature_value'] = $params['signature_value'];
			if($C_message->getScheduledType() == 'SCHEDULE')
				$blast_params['max_users'] = $params['max_users'];
// 			$blast_params['sender_label'] = $params['sender_label'];
// 			$blast_params['sender_from'] = $params['sender_from'];
			$blast_params['plain_text'] = rawurldecode( $params['plain_text'] );
		}else{

			//call task, wechat and mobilepush
			$blast_params['message'] = $params['message'];
			$blast_params['subject'] = $params['subject'];
			$blast_params['description'] = $params['description'];
			
		}

		$this->logger->info( '@@End of getQueueParamsByType' );

  		$result =  json_encode( $blast_params );

  		return $result;
	}

	/**
	 *
	 * It will prepare schedule type and return appropriate string
	 * @param BulkMessage $C_message
	 */
	private function getScheduleType( BulkMessage &$C_message ){

		$send_type = $C_message->getScheduledType();

		$this->logger->info( '@@Start of getScheduleType Send Type : '.$send_type );

		switch ( $send_type ){

			case 'IMMEDIATE' :

				$schedule_type = 'IMMEDIATELY';

				break;

			case 'PARTICULAR_DATE' :

				$schedule_type = 'PARTICULAR_DATE';

				break;

			case 'SCHEDULE' :

				$schedule_type = 'SCHEDULED';

				break;
		}

		return $schedule_type;
	}

	/**
	 *
	 * It will check the campaign expiry and throws exception when particular campaign has already expired
	 * @param int $campaign_id
	 * @throws InvalidInputException
	 */
	protected function checkCampaignExpiry( $campaign_id ){

		$this->logger->debug( '@@Start of Campaign Expiry Id : '.$campaign_id );

		$this->C_campaign_model_extension->load( $campaign_id );

		$campaign_start_date = $this->C_campaign_model_extension->getStartDate();
		// convert time to org timezone timestamp
		$converted_campaign_start_date = Util::convertTimeFromCurrentOrgTimeZone($campaign_start_date);
		$campagn_start_date_timestamp = strtotime( $converted_campaign_start_date);

		$campaign_end_date = $this->C_campaign_model_extension->getEndDate( );
		$converted_campaign_end_date = Util::convertTimeFromCurrentOrgTimeZone($campaign_end_date);
		$campagn_end_date_timestamp = strtotime( $converted_campaign_end_date );

		$current_timestamp = time( );

		if( $current_timestamp > $campagn_end_date_timestamp ){

			$msg = _campaign("Your campaign expired on")." ". date( 'd-m-Y',strtotime( $campaign_end_date )).
					". "._campaign("Please modify end date to re-schedule the campaign").".";

			throw new InvalidInputException( $msg );
		}

		//check if campaign is active or not
		$current_timestamp = time( );
		if( $current_timestamp < $campagn_start_date_timestamp )
			throw new InvalidInputException( _campaign("Your campaign has not started yet").".
					"._campaign("It will be active from")." ".$campaign_start_date );
	}

	/**
	 *
	 * It will check the sending time based on Schedule Type
	 * @param BulkMessage $C_message
	 * @throws Exception
	 */
	protected function checkSendingTimeByScheduleType( BulkMessage $C_message ){

		$day = $C_message->getDay();
		$week = $C_message->getWeek();
		$month = $C_message->getMonth();
		$hours = $C_message->getHours();
		$minutes = $C_message->getMinutes();
		$cron_hours = $C_message->getCronHours();
		$cron_minutes = $C_message->getCronMinutes();

		$group_id = $C_message->getGroupId();
		$campaign_id = $C_message->getCampaignId();
		$send_when = $C_message->getScheduledType();

		$this->logger->debug( '@@Start of Sending Time By Schedule Type Camp Id : '.
				$campaign_id .' , group id : '.$group_id );

		switch( $send_when ){

			case 'SCHEDULE' :

				if( !$day ) {

					throw new Exception( _campaign("In Order To Schedule The Message You Have To Select Proper Days") );
				}

				if( !$month ) {

					throw new Exception( _campaign("In Order To Schedule The Message You Have To Select Proper Month") );
				}

				if( !$week ) {

					throw new Exception( _campaign("In Order To Schedule The Message You Have To Select Proper Week") );
				}

				//get group details
				$group_details = $this->C_campaign_controller->getGroupDetails($group_id);
				if( !in_array( strtolower($group_details['type']) , array('loyalty','non_loyalty','all') ) )
					throw new Exception( _campaign("List created using filters with 'Org Level Test & Control settings' or 'List Level Test & Control Ratio of 100:0'only, can be scheduled for recurring campaigns").'.' );

				if( $cron_hours === 'HH' ) {

					throw new Exception( _campaign("Please Select The Hour At Which you Would Like To Make The Blast") );
				}

				if( $cron_minutes === 'mm' ) {
					throw new Exception( _campaign("Please Select The Minute At Which you Would Like To Make The Blast") );
				}

				break;

			case 'PARTICULAR_DATE' :

				if( $hours === 'HH' ) {

					throw new Exception( _campaign("Please Select The Hour At Which you Would Like To Make The Blast") );
				}

				if( $minutes === 'mm' ) {

					throw new Exception( _campaign("Please Select The Minute At Which you Would Like To Make The Blast") );
				}

				//check if the scheduling is happening for the future time.
				$selected_date = $this->getDateBySendingTypeForBulkBlast( $C_message , false );

				if( strtotime( date( 'Y-m-d H:i:s' ) ) > strtotime( $selected_date ) ) {

						throw new Exception( _campaign("Please select future time for queuing up").'.' );
				}

				$campaign_end_date = Util::convertTimeFromCurrentOrgTimeZone($this->C_campaign_model_extension->getEndDate( ));

				$campagn_end_date_timestamp = strtotime( $campaign_end_date );
				if( $campagn_end_date_timestamp < strtotime( $selected_date ) ) {

					throw new Exception( _campaign("Campaign is expiring before scheduled time. Please select a valid time of execution").'.' );
				}

				break;
		}
		$this->logger->debug( '@@End of Sending Time By Schedule Type' );
	}

	/**
	 *
	 * It will check voucher code in msg
	 * @param BulkMessage $C_message
	 * @throws Exception
	 */
	protected function checkForVoucherCodeInMsg( BulkMessage $C_message ){

		$this->logger->debug( '@@Start of Voucher Code Check' );

		$campaign_id = $C_message->getCampaignId();
		$message = $C_message->getMessage();

		$attached_voucher_series_id = false;

		$this->C_campaign_model_extension->load( $campaign_id );

		if( strpos( $message, '{{valid_till_date' )
		|| strpos( $message, '{{valid_days_from_create}}' ) ){

			if( strpos( $message, '{{voucher}}' ) === false ){

				throw new Exception( _campaign("Coupon tag must be present in template, if coupon expiry details are required").'.' );
			}
		}

		if( strpos( $message, '{{voucher}}' ) ){

			$campaign_details = $this->C_campaign_model_extension->getHash();

			$type = $campaign_details['type'];
			$params = $C_message->getParams();
			$this->logger->debug("params: $params");
			if($params['expiry_reminder']){
				$attached_voucher_series_id = $params['voucher_series_id'];
			}
			else{
				$attached_voucher_series_id = $campaign_details['voucher_series_id'];
	
				$this->logger->debug( "Campaign Base : ".print_r( $campaign_details, true ) );
	
				if( $type == 'outbound' ){
	
					if( $attached_voucher_series_id == -1  ){
	
						throw new Exception( _campaign("Can Not Issue Voucher As No Voucher Series Is Attached To The Campaign") );
					}
				}elseif( $type == 'referral' ){
	
					$attached_voucher_series_id = json_decode( $attached_voucher_series_id, true );
	
					$attached_voucher_series_id = ( int ) $attached_voucher_series_id['referer'];
	
					if( $attached_voucher_series_id < 1 ){
	
						throw new Exception( _campaign("Can Not Issue Voucher As No Referer Voucher Series Is Attached To The Campaign") );
					}
				}else{
	
					throw new Exception( _campaign("The Campaign Type Is Not Configured To Send Campaign Blast") );
				}
			}

			if( $attached_voucher_series_id ){

				$C_coupon_series_manager = new CouponSeriesManager();
				$C_coupon_series_manager->loadById( $attached_voucher_series_id );

				$series_valid_till_date =
				$C_coupon_series_manager->C_voucher_series_model_extension->getValidTillDate();

				if( $series_valid_till_date < date('Y-m-d') ){

					throw new Exception( _campaign("Can Not Issue Voucher As Voucher Series Is Already Expired Or Will Get Expired Today") );
				}

				$coupon_manager = new CouponManager() ;
				//get valid days based on strategy type used
				$series_valid_days_until_expiry = $coupon_manager->getValidDaysForStrategy( $attached_voucher_series_id ) ;
				
				$this->logger->debug( "@@Start series valid days till expiry "
						. $series_valid_days_until_expiry );

				if( $series_valid_days_until_expiry < 0 )
					throw new Exception( _campaign('Can Not Issue Voucher As Valid Days From Creation Is Not Configured') );
			}
		}
		if(strpos( $message, '{{redemptions_left}}' )){
			if($C_coupon_series_manager->C_voucher_series_model_extension->getSameUserMultipleRedeem()){
				throw new Exception(_campaign("Can't use")." "."{{redemptions_left}}".' '._campaign("tag as voucher can be redeemed multiple times"));
			}
		}
		$this->logger->debug( '@@EngetQueueParamsByTyped of Voucher Code Check' );
	}

	/**
	 *
	 * insert new message entry in queue message table thorugh BulkMessage base model
	 * @param BulkMessage $C_message
	 * @return message_id
	 */
	protected function queueBulkMessage( BulkMessage &$C_message ){

		$this->logger->debug( '@@Start of Queue Bulk Message : '.$C_message->getCampaignId() );

		$type = $C_message->getType();
		$group_id = $C_message->getGroupId();

		$default_arguments = $this->prepareDefaultArguments( $C_message );

		if( $type != 'CUSTOMER_TASK' ){
			$scheduled_on = $this->getDateBySendingTypeForBulkBlast( $C_message , false );
			//create guid with higher entropy
			$guid = uniqid( "$group_id__", true );
		}else{
			$scheduled_on = $C_message->getScheduledOn();
		}

 		$blast_params = $this->getQueueParamsByType( $C_message );
		$schedule_type = $this->getScheduleType( $C_message );
		$params = $C_message->getParams();
		
		if( $C_message->getScheduledType() == 'SCHEDULE' ){
			if($params['expiry_reminder'])
			{
				$scheduled_on = 'NULL';
				if( $type == 'SMS' )
					$type = 'SMS_EXPIRY_REMINDER';
				elseif( $type == 'EMAIL' )
					$type = 'EMAIL_EXPIRY_REMINDER';
				$blast_params = json_decode( $blast_params,true );
				$blast_params['expiring_in'] = $params['expiring_in'];
				$blast_params['expiry_reminder'] = 1;
				$blast_params = json_encode( $blast_params );
			}
			else{
				$scheduled_on = 'NULL';
				if( $type == 'SMS' )
					$type = 'SMS_REMINDER';
				elseif( $type == 'EMAIL' )
					$type = 'EMAIL_REMINDER';
				elseif($type == 'CALL_TASK')
					$type = 'CALL_TASK_REMINDER';
				elseif($type == 'WECHAT')
					$type = 'WECHAT_REMINDER';
				elseif($type == 'MOBILEPUSH')
					$type = 'MOBILEPUSH_REMINDER';
			}
		}
		$this->logger->debug( '@@Start of Queue Bulk Message : '.print_r($blast_params,true) );
		
		//Prepare and queue bulk blast start from here
		$C_queue_msg = new BulkMessage();
		$C_queue_msg->setOrgId( $this->org_id );
		$C_queue_msg->setGuid( $guid );
		$C_queue_msg->setCampaignId( $C_message->getCampaignId() );
		$C_queue_msg->setGroupId( $group_id );
		$C_queue_msg->setScheduledBy( $this->user_id );
		$C_queue_msg->setScheduledType( $schedule_type );
		$C_queue_msg->setScheduledOn( $scheduled_on );
		$C_queue_msg->setDefaultArguments( $default_arguments );
		$C_queue_msg->setParams( $blast_params );
		$C_queue_msg->setType( $type );
		$C_queue_msg->setStatus('OPEN');
		$C_queue_msg->setLastUpdatedOn( date('Y-m-d H:i:s') );
		$C_queue_msg->setApproved($C_message->getApproved());

		$message_id = $C_queue_msg->insert();
		$this->prepareBulkMessagePayLoad($C_message, $message_id);

		$this->logger->debug( '@@Message Queued successfully Id : '.$message_id );

		return $message_id;
	}

	private function prepareBulkMessagePayLoad( BulkMessage &$C_message, $message_id ){

		$C_message->load( $message_id );
		$C_message->setParams( json_decode( $C_message->getParams(), true ) );
		$C_message->setDefaultArguments( json_decode( $C_message->getDefaultArguments(), true ) );
	}

	/**
	 *
	 * It will update bulk Message entry in queue messages table through BulkMessage base model
	 * @param BulkMessage $C_message
	 * @return boolean status
	 */
	protected function updateBulkMessage( BulkMessage &$C_message ){

		$this->logger->debug( '@@Start of Message Update Id : '.$C_message->getId() );

		$default_arguments = $this->prepareDefaultArguments( $C_message );
		$blast_params = $this->getQueueParamsByType( $C_message );
		$schedule_type = $this->getScheduleType( $C_message );
		$params = $C_message->getParams();

		$type = $C_message->getType();
		if( $type != 'CUSTOMER_TASK' ){
			$scheduled_on = $this->getDateBySendingTypeForBulkBlast( $C_message , false );
		}

		if( $C_message->getScheduledType() == 'SCHEDULE' ){
			if($params['expiry_reminder'])
			{
				$scheduled_on = 'NULL';
				if( $type == 'SMS' )
					$type = 'SMS_EXPIRY_REMINDER';
				elseif( $type == 'EMAIL' )
					$type = 'EMAIL_EXPIRY_REMINDER';
				$blast_params = json_decode( $blast_params,true );
				$blast_params['expiring_in'] = $params['expiring_in'];
				$blast_params['expiry_reminder'] = 1;
				$blast_params = json_encode( $blast_params );
			}
			else{
				$scheduled_on = 'NULL';
				if( $type == 'SMS' )
					$type = 'SMS_REMINDER';
				elseif( $type == 'EMAIL' )
					$type = 'EMAIL_REMINDER';
				elseif( $type == 'CALL_TASK')
					$type = 'CALL_TASK_REMINDER';
				elseif ( $type == 'WECHAT' ) 
					$type = 'WECHAT_REMINDER';
				elseif ( $type == 'MOBILEPUSH' ) 
					$type = 'MOBILEPUSH_REMINDER';
						
			}
		}

		$group_id = $C_message->getGroupId();
		$message_id = $C_message->getId();

		//Prepare and update queue bulk blast start from here
		$C_queue_msg = new BulkMessage();

		$C_queue_msg->load( $message_id );

		//add Check for send when changes for scheduler types and simple immediate type
		$this->checkSendWhenForTypeChange( $C_queue_msg , $C_message );

		$C_queue_msg->setCampaignId( $C_message->getCampaignId() );
		$C_queue_msg->setGroupId( $group_id );
		$C_queue_msg->setScheduledBy( $this->user_id );
		$C_queue_msg->setScheduledType( $schedule_type );
		$C_queue_msg->setScheduledOn( $scheduled_on );
		$C_queue_msg->setDefaultArguments( $default_arguments );
		$C_queue_msg->setParams( $blast_params );
		$C_queue_msg->setType( $type );
		$C_queue_msg->setStatus('OPEN');
		$this->logger->debug("Approved status of messsage:".$C_message->getApproved());
		if( $C_message->getApproved() != 1 ){
			$C_queue_msg->setApproved( 0 );
			$C_queue_msg->setApprovedBy( 0 );
		}
		
		$C_queue_msg->setLastUpdatedOn( date('Y-m-d H:i:s') );

		$status = $C_queue_msg->update( $message_id );
		$this->prepareBulkMessagePayLoad($C_message, $message_id);

		return $status;
	}

	/**
	 *
	 * Add an entry for the scheduler campaign
	 * @param BulkMessage $C_message
	 * @param string $add_flow
	 * @throws Exception
	 */
	protected function addReminder( BulkMessage $C_message , $add_flow = true ){

		try{

			$send_when = $C_message->getScheduledType();
			$campaign_id = $C_message->getCampaignId();
			$message_id = $C_message->getId();
			$params = $C_message->getReminderParams();
			$cron_hours = $params['cron_hours'];
			$cron_minutes = $params['cron_minutes'];
			// modify time w.r.t. current org time zone 
			$changed_time = Util::convertTimeFromCurrentOrgTimeZone("$cron_hours:$cron_minutes");
			$params['cron_hours'] = array( date( 'H' , strtotime( $changed_time ) ) );
			$params['cron_minutes'] = array( date( 'i' , strtotime( $changed_time) ) );
			$this->logger->debug("time before modification $cron_hours:$cron_minutes");
			$this->logger->debug('time after timezone modification '.$params['cron_hours'].":".$params['cron_minutes']);
			$group_id = $C_message->getGroupId();
			$type = $C_message->getType();

			$scheduled_on = $this->getDateBySendingTypeForBulkBlast( $C_message , false );

			$this->logger->debug( '@@Start of Reminder : '.$send_when );

			//If scheduled type of message we need to make an
			//entry to reminder 2.
			if ( $send_when != 'IMMEDIATELY' ){

				$this->logger->debug( '@@In Reminder');
				//initialize the reminder for org now
				$reminder = new Reminder( $this->org_id );

				$reminder_params = array(
						'scheduled_on' => $scheduled_on,
						'campaign_id' => $campaign_id,
						'message_id' => $message_id,
						'group_id' => $group_id,
						'params' => $params,
				);
				if($type ==='SMS_EXPIRY_REMINDER' || $type ==='EMAIL_EXPIRY_REMINDER')
				{
					$voucher_expiry_call_back = new VoucherReminderCallBack($reminder);
					$voucher_expiry_call_back->process($reminder_params);
				}
				else{
					$campaign_call_back = new CampaignGroupCallBack( $reminder );
					$campaign_call_back->process( $reminder_params );
					$this->logger->debug( '@@In Reminder Successfully added');

					$reminder_params_health = array(
						'is_precheck_reminder'=>'1',
						'scheduled_on' => $scheduled_on,
						'campaign_id' => $campaign_id,
						'message_id' => $message_id,
						'group_id' => $group_id,
						'event_type' => 'CAMPAIGN_NOTIFY',
						'params' => $params,
					);
					
					include_once 'helper/scheduler/HealthDashboardCallBack.php';
					$health_reminder = new Reminder($this->org_id);
					$health_call_back = new HealthDashboardCallBack( $health_reminder ) ;
					$health_call_back->process( $reminder_params_health );
					$this->logger->debug('@In reminder for health successfully added');
				}			
			}
		}catch ( Exception $e ){

			if( $add_flow ){

				$this->logger->error( '@@Delete MSG Id : '.$message_id );
				$C_message->delete( $message_id );
			}
			$this->logger->error( '@@In Reminder Error : '.$e->getMessage() );
			throw new Exception( $e->getMessage() );
		}
	}

	/**
	 *
	 * Check for the send when for type change for bulk message updation
	 * @param BulkMessage $C_old_msg
	 * @param BulkMessage $C_new_msg
	 */
	private function checkSendWhenForTypeChange( BulkMessage $C_old_msg , BulkMessage $C_new_msg ){

		$old_send_when = $C_old_msg->getScheduledType();
		$new_send_when = $C_new_msg->getScheduledType();

		$send_when = ( $new_send_when == 'SCHEDULE' ) ? 'SCHEDULED' :
							( $new_send_when == 'IMMEDIATE' ) ? 'IMMEDIATELY' : $new_send_when;

		if( $old_send_when != $send_when ){

			if( $new_send_when == 'IMMEDIATE' ){

				$this->logger->debug( '@@In Check send when Reminder');
				//load the reminder for message id
				$reminder = new Reminder( $this->org_id );
				$reminder->loadByRefId( $C_old_msg->getId() , 'CAMPAIGN' );

				$campaign_call_back = new CampaignGroupCallBack( $reminder );
				$campaign_call_back->stateChange('STOP');
				$this->logger->debug( '@@In Reminder Successfully added');
			}
		}
	}

	protected function getDefaultTemplatesOptions(){

		$default_template_options = array();

		$custom_array = array(
				'custom_tag_1' => '{{NA}}',
				'custom_tag_2' => '{{NA}}',
				'custom_tag_3' => '{{NA}}',
				'custom_tag_4' => '{{NA}}',
				'custom_tag_5' => '{{NA}}',
				'custom_tag_6' => '{{NA}}',
				'custom_tag_7' => '{{NA}}',
				'custom_tag_8' => '{{NA}}',
				'custom_tag_9' => '{{NA}}',
		);

		$messages_list = array();

		$default_name = 'Customer';

		$temp_option = $this->C_campaign_model_extension->getDefaultFieldValue( $this->org_id );

		if(count($temp_option) > 0){

			foreach($temp_option as $to){

				$default_template_options[$to['field_name']] = $to['field_value'];
			}
		}

		$default_template_options = array_merge( $default_template_options , $custom_array);

		return $default_template_options;
	}

	/**
	 *
	 * @param $default_template_options : default template option
	 * @param $profile: of the user for whom template replacment will occure
	 * @param $user_type: type of user loyalty/customer
	 * @param $group_id: group id of the message group
	 */

	protected function getTagValues( $profile , $group_id, 
			$campaign_id, $use_registered_store = true ){

		$this->logger->debug('@@In tag replace method:'.print_r( $profile->user_id , true ) );

		$default_template_options = $this->getDefaultTemplatesOptions();

		if($profile == null){

			$this->logger->debug('Profile in Template Replacement For Message Preview is NULL');
			return array();
		}

		$eup = new ExtendedUserProfile( $profile , $this->org );
		$u = UserProfile::getById( $profile->user_id );
		$user_details = $u->getFields();

		$this->logger->debug( "User Details : " . print_r( $user_details , true ) );
		$loyalty_details = $this->C_campaign_model_extension->getLoyaltyDetailsForUserID( $profile->user_id );

		//check for the default argument
		$store_details = StoreProfile::getById($loyalty_details['registered_by']);
		$user_store_id = $loyalty_details[ 'registered_by' ];
		if( !$use_registered_store ){

		    $this->logger->debug( "user_store_id inside");
			$user_store_id = StoreTillController::getLastShoppedTillForUser( $profile->user_id, $this->org_id );
		}
		$this->logger->debug( "user_store_id $user_store_id  -- use_registered_store $use_registered_store ");
		$store_templates = StoreProfile::getStoreTemplateValues( $user_store_id , $this->org_id );

		$this->logger->debug( "Store_Template Outside" );

		//if fullname is null use default values
		$first_name = ( ( strlen( $eup->getFirstName() ) >0 ) ?
				$eup->getFirstName() : $user_details['firstname'] ) ;

		$last_name = ( ( strlen( $eup->getLastName() ) >0 ) ?
				$eup->getLastName() : $user_details['lastname'] ) ;
		$user_full_name = $first_name . ' ' . $last_name;

		$fullname = ( (strlen( $eup->getFullName() ) > 1 ) ?
				$eup->getFullName() : $user_full_name ) ;

		$points = $loyalty_details['loyalty_points'];
		// getting points currency ratio
		$pesController = new PointsEngineServiceController();
		$pcRatio = $pesController->getPointsCurrencyRatio($this->org_id);
		$points_value = round($loyalty_details['loyalty_points'] * $pcRatio, 2);
		$slab_name = $loyalty_details['slab_name'];
		
		
		//optout tag in sms 
		$subscription_service_thrift = new SubscriptionServiceThriftClient();
		$opt_out_tag_val = $subscription_service_thrift->
				getOptOutTagValue( $this->org_id, array( $profile->user_id ) );
		$opt_out_tag = ( strlen( $opt_out_tag_val[$profile->user_id] ) < 8 )
				? "" :$opt_out_tag_val[$profile->user_id];
		
		
		//$store_number = $store_details->mobile;
		//$store_name = $store_details->firstname;

		$store_name = 			$store_templates[ 'store_name' ];
		$store_number = 		$store_templates[ 'store_number' ];
		$store_land_line = 		$store_templates[ 'store_land_line' ];
		$store_email = 			$store_templates[ 'store_email' ];
		$store_external_id = 	$store_templates[ 'store_external_id' ];
		$store_external_id_1 =	$store_templates[ 'store_external_id_1' ];
		$store_external_id_2 = 	$store_templates[ 'store_external_id_2' ];

		$sms_store_name =	$store_templates[ 'sms_store_name' ];
		$sms_email =		$store_templates[ 'sms_email' ];
		$sms_mobile =		$store_templates[ 'sms_mobile' ];
		$sms_land_line =	$store_templates[ 'sms_land_line' ];
		$sms_address =		$store_templates[ 'sms_address' ];
		$sms_extra =		$store_templates[ 'sms_extra' ];

		$email_store_name =	$store_templates[ 'email_store_name' ];
		$email_email =		$store_templates[ 'email_email' ];
		$email_mobile =		$store_templates[ 'email_mobile' ];
		$email_land_line =	$store_templates[ 'email_land_line' ];
		$email_address =	$store_templates[ 'email_address' ];
		$email_extra =		$store_templates[ 'email_extra' ];

		$customFieldClass = new CustomFields();
		$result = $customFieldClass->getCustomFields( $this->org_id, 'query_hash', 'name', 'default', 'loyalty_registration' );
		$custom_field_tags = array();

		if( $result ){

			foreach ( $result as $key => $value ){

				$details = $customFieldClass->getCustomFieldValueByFieldName(
						$this->org_id,
						'loyalty_registration',
						$profile->user_id,
						$key
				);

				$details_decoded = json_decode( $details, true );
				if( is_array( $details_decoded ) )
					$custom_field_tags['custom_field.'.$key] = implode( ',' , $details_decoded );
			}
		}

		$this->logger->debug( "tag_replacement key" . $key );
		$this->logger->debug( "tag_replacement" . $custom_field_tags['custom_field.'.$key] );

		//fetch custom tags for all campaigns other than timeline
		$campaign_controller = new CampaignController();
		$campaign_controller->load( $campaign_id );
		$campaign_type = $campaign_controller->campaign_model_extension->getType();
		if( strtolower( $campaign_type ) == 'outbound' ){
			
			include_once 'business_controller/campaigns/library/CampaignGroupBucketHandler.php';
			$C_campaign_group_handler = new CampaignGroupBucketHandler( $group_id );
			$custom_tag =
			$this->C_campaign_model_extension->getCustomTag( $profile->user_id, $group_id,
					$C_campaign_group_handler );
			
			$custom_tags = $custom_tag[0]['custom_tags'];
			
			//$custom_tags = stripslashes( $custom_tags );
			$custom_array = json_decode( $custom_tags, true );
		}
		
		$custom_array = !is_array( $custom_array ) ? array() : $custom_array ;
		foreach ($custom_array as $k => $v){

			$default_template_options[$k] = $v;
		}

		$voucher_details = $this->getVoucherDetailsForReplacement( $group_id, $profile->user_id, $this->org_id, $campaign_id );

		$this->logger->debug( "@@@ Voucher Details " . print_r( $voucher_details , true ) );

		$coupon_manager = new CouponManager() ;
		$coupon_manager->loadByCode($voucher_details['voucher_code']) ;
		//get valid days based on strategy type used
		$valid_days_from_create = $coupon_manager->getValidDaysForStrategy() ;
		$created_date = $voucher_details['created_date'];
		$valid_till_date = $voucher_details['valid_till_date'];

		$valid_till_date_time = strtotime( $valid_till_date );

		if( $created_date ){

			if( $valid_days_from_create > 0 )
				$expiry_date_time = strtotime( '+'.$valid_days_from_create.'day', strtotime( $created_date ) );
			else
				$expiry_date_time = strtotime( $valid_days_from_create.'day', strtotime( $created_date ) );
			$this->logger->debug( "@@@ Voucher Details EXPIRY DATE " . $expiry_date_time );

			if( $valid_till_date_time < $expiry_date_time )
				$expiry_date_value = $valid_till_date_time;
			else
				$expiry_date_value = $expiry_date_time;

			$this->logger->debug( "@@@ Voucher Details valid till date time " .
											date('Y-m-d H:i:s', $valid_till_date_time ) );
			$this->logger->debug( "@@@ Voucher Details expiry date time " .
											date('Y-m-d H:i:s', $expiry_date_time ) );

		}else
			$expiry_date_value = $valid_till_date_time;

		$default_template_options['valid_till_date.FORMAT_1']
		= date( self::FORMAT_1, $expiry_date_value );

		$default_template_options['valid_till_date.FORMAT_2']
		= date( self::FORMAT_2, $expiry_date_value );

		$default_template_options['valid_till_date.FORMAT_3']
		= date( self::FORMAT_3, $expiry_date_value );

		$default_template_options['valid_till_date.FORMAT_4']
		= date( self::FORMAT_4, $expiry_date_value );

		$default_template_options['valid_till_date.FORMAT_5']
		= date( self::FORMAT_5, $expiry_date_value );

		$default_template_options['valid_till_date.FORMAT_6']
		= date( self::FORMAT_6, $expiry_date_value );

		$default_template_options['valid_till_date.FORMAT_7']
		= date( self::FORMAT_7, $expiry_date_value );

		$default_template_options['valid_till_date.FORMAT_8']
		= date( self::FORMAT_8, $expiry_date_value );

		$current_date = date( self::FORMAT_1 );


		if( strtotime( $current_date ) < $expiry_date_value ){

			$this->logger->debug( "@@@ Voucher Details currentdate strtotime " . strtotime( $current_date ) );
			$this->logger->debug( "@@@ Voucher Details expirydate strtotime " . $expiry_date_value );

			$valid_days_for_expiry =
			floor( ( $expiry_date_value - strtotime( $current_date ) ) / ( 60 * 60 * 24 ) );

			$this->logger->debug( "@@@ Voucher Details valid days for expiry " . $valid_days_for_expiry );
		}else{

			$valid_days_for_expiry = 'NA';
		}

		$default_template_options['valid_days_from_create'] = ( $valid_days_for_expiry < $valid_days_from_create ) ?
																$valid_days_for_expiry :
																$valid_days_from_create;

		$default_template_options['customer_email'] = $profile->email;
		$default_template_options['fullname'] = ((strlen($fullname) > 0)?($fullname):($default_template_options['fullname']));
		$default_template_options['first_name'] = ((strlen($first_name) > 0)?($first_name):($default_template_options['first_name']));
		$default_template_options['last_name'] = ((strlen($last_name) > 0)?($last_name):($default_template_options['last_name']));
		$default_template_options['loyalty_points'] = ($points > 0)?($points):'NA';
		$default_template_options['loyalty_points_value'] = ($points_value > 0)?($points_value):'NA';
		$default_template_options['loyalty_points_floor'] = ($points > 0)?floor($points):'NA';
		$default_template_options['loyalty_points_value_floor'] = ($points_value > 0)?floor($points_value):'NA';
		$default_template_options['slab_name'] = (strlen( $slab_name ) > 0)?($slab_name):'NA';
		$default_template_options['store_number'] = (($store_number > 0)?($store_number):($default_template_options['store_number']));
		$default_template_options['store_name'] = ((strlen($store_name) > 0)?($store_name):($default_template_options['store_name']));
		$default_template_options['store_land_line'] = ((strlen($store_land_line) > 0)?($store_land_line):
				($default_template_options['store_land_line']));
		$default_template_options['store_email'] = ((strlen($store_email) > 0)?($store_email):($default_template_options['store_email']));
		$default_template_options['store_external_id'] = ((strlen($store_external_id) > 0)?($store_external_id):
				($default_template_options['store_external_id']));
		$default_template_options['store_external_id_1'] = ((strlen($store_external_id_1) > 0)?($store_external_id_1):
				($default_template_options['store_external_id_1']));
		$default_template_options['store_external_id_2'] = ((strlen($store_external_id_2) > 0)?($store_external_id_2):
				($default_template_options['store_external_id_2']));

		$default_template_options['sms_store_name'] =
		((strlen($sms_store_name) > 0)?($sms_store_name):($default_template_options['sms_store_name']));
		$default_template_options['sms_email'] =
		((strlen($sms_email) > 0)?($sms_email):($default_template_options['sms_email']));
		$default_template_options['sms_mobile'] =
		((strlen($sms_mobile) > 0)?($sms_mobile):($default_template_options['sms_mobile']));
		$default_template_options['sms_land_line'] =
		((strlen($sms_land_line) > 0)?($sms_land_line):($default_template_options['sms_land_line']));
		$default_template_options['sms_address'] =
		((strlen($sms_address) > 0)?($sms_address):($default_template_options['sms_address']));
		$default_template_options['sms_extra'] =
		((strlen($sms_extra) > 0)?($sms_extra):($default_template_options['sms_extra']));
		$default_template_options['email_store_name'] =
		((strlen($email_store_name) > 0)?($email_store_name):($default_template_options['email_store_name']));
		$default_template_options['email_email'] =
		((strlen($email_email) > 0)?($email_email):($default_template_options['email_email']));
		$default_template_options['email_mobile'] =
		((strlen($email_mobile) > 0)?($email_mobile):($default_template_options['email_mobile']));
		$default_template_options['email_land_line'] =
		((strlen($email_land_line) > 0)?($email_land_line):($default_template_options['email_land_line']));
		$default_template_options['email_address'] =
		((strlen($email_address) > 0)?($email_address):($default_template_options['email_address']));
		$default_template_options['email_extra'] =
		((strlen($email_extra) > 0)?($email_extra):($default_template_options['email_extra']));
		$default_template_options['optout'] = $opt_out_tag;

		if( $custom_field_tags ){

			foreach( $custom_field_tags as $key => $value ){

				$default_template_options[$key] = ((strlen($value)>0)?($value):('NA'));
			}
		}

		$this->getGroupTags( $default_template_options , $group_id );

		$this->logger->debug( "End of template Method" );

		return $default_template_options;
	}

	/**
	 *
	 *
	 * @param unknown $group_id
	 * @param unknown $user_id
	 */
	private function getVoucherDetailsForReplacement( $group_id, $user_id, $org_id, $campaign_id ){

		$this->logger->debug( "@@@ Voucher Details entered voucher replacement function " );

		if( $campaign_id == -1 )
			$campaign_id = $this->C_campaign_model_extension->getCampaignIdByGroupId( $group_id );

		$this->C_campaign_controller->load( $campaign_id );
		$campaign_details = $this->C_campaign_controller->getDetails();

		$campaign_type = $campaign_details['type'];
		$attached_voucher_series_id = $campaign_details['voucher_series_id'];

		if( $campaign_type == 'outbound' ){

			$attached_voucher_series_id = $attached_voucher_series_id;
		}elseif( $campaign_type == 'referral' ){

			$attached_voucher_series_id = json_decode( $attached_voucher_series_id, true );
			$attached_voucher_series_id = ( int ) $attached_voucher_series_id['referer'];
		}else{

			$attached_voucher_series_id = -1;
		}

		$this->logger->debug( "@@@ Voucher Details voucher series id " . $attached_voucher_series_id );

		if( $attached_voucher_series_id != -1 ){


			$C_voucher_series = new VoucherSeries( $attached_voucher_series_id );
			$voucher_code = $C_voucher_series->getLastVoucherForCustomerInSeries( $user_id );

			$this->logger->debug( "@@@ Voucher Details voucher code " . $voucher_code );

			if( !$voucher_code )
				$voucher_code = false;

			$this->logger->debug( "@@@ Voucher Details org ID " . $this->C_campaign_model_extension->getOrgId() );

			return $this->C_campaign_model_extension->getVoucherDetails( $attached_voucher_series_id, $voucher_code, $org_id );
		}

		return null;
	}

	/**
	 * Compares the used tags in the message against the valid tags
	 * and returns the invalid tags
	 *
	 * @param unknown_type $message
	 * @param unknown_type $valid_tags
	 * @return multitype:
	 */
	protected function getInvalidTags( $message, $valid_tags, $allowColorParams=false ){

		$this->logger->debug( "Entire Message " . $message );

		$invalid_tags = array();
		$pattern = "/{{(.*?)}}/s";
		if ( preg_match_all( $pattern , $message , $matches , PREG_SET_ORDER ) ){

			foreach( $matches as $key => $value ){

				$find_survey_tag = '{{SURVEY__TOKEN__';
				$find_referral_tag = '{{referral_unique_';
				$find_reco_tag = '{{reco';
				$find_dynamic_tag = '{{dynamic_expiry_date_';
				$find_link_tag = '{{Link_to_';
				
				if( strstr( '{{'.$value[1].'}}', $find_survey_tag ) )
					continue;

				if( strstr( '{{'.$value[1].'}}', $find_referral_tag ) )
					continue;
				
				if( strstr( '{{'.$value[1].'}}', $find_reco_tag ) )
					continue;

				if( strstr( '{{'.$value[1].'}}', $find_dynamic_tag ) )
					continue;

				if( strstr( '{{'.$value[1].'}}', $find_link_tag ) )
					continue;

				$find_survey_sms_tag = '{{SURVEY__URL__';
				if( strstr( '{{'.$value[1].'}}', $find_survey_sms_tag ) )
					continue;

				//Do it only for the modules who supports color customization in unsubscribe
				//Handling to let subscribe/unsubscribe with color parameter to pass in the validation
				if($allowColorParams===true && preg_match("/^(unsubscribe|subscribe)[\(a-zA-Z0-9#\)]*$/", $value[1])==1)
				{
					$this->logger->debug("Unsubscribe/subscribe with color parameter matched : ".$value[1]);
					continue;
				}
				if( !in_array( '{{'.$value[1].'}}', $valid_tags ) ){
					$this->logger->debug( '@@@Invalid Tags ' . $value[1] );
					array_push( $invalid_tags, '"'.$value[1].'"' );
				}
			}
		}
		return $invalid_tags;
	}

	/**
	 * prepares the standard object for bulk message that
	 * is required by all the validators
	 * @return BulkMessage
	 */
	protected function prepareQueueMessage( $params, $default_arguments ){

		//prepare queuing params
		//validating data

		$C_bulk_message = new BulkMessage();
		$C_bulk_message->setId( $this->msg_id );
		$C_bulk_message->setCampaignId( $this->campaign_id );
		$C_bulk_message->setGroupId( $params['camp_group'] );
		$C_bulk_message->setScheduledType( $params['send_when'] );
		$C_bulk_message->setMessage( $params['message'] );
		$C_bulk_message->setDateField( $params['date_field'] );
		$C_bulk_message->setHours( $params['hours'] );
		$C_bulk_message->setMinutes( $params['minutes'] );
		$C_bulk_message->setDay( $params['cron_day'] );
		$C_bulk_message->setMonth( $params['cron_month'] );
		$C_bulk_message->setWeek( $params['cron_week'] );
		$C_bulk_message->setCronHours( $params['cron_hours'] );
		$C_bulk_message->setCronMinutes( $params['cron_minutes'] );
		$C_bulk_message->setDefaultArguments( $default_arguments );
		$C_bulk_message->setParams( $params );
		$C_bulk_message->setType( $this->validator_type );

		return $C_bulk_message;
	}

	/**
	 * CONTRACT(
	 * 	'camp_group' : group_id,
	 * 	'send_when' : Type of selections ENUM [ IMMEDIATE | PARTICULAR_DATE | SCHEDULE ]
	 * 	'hours' : hour selection
	 * 	'minutes' : minute selection
	 * 	'cron_day' : cron days
	 * 	'cron_week' : cron weeks
	 * 	'cron_month' : cron months
	 *  'org_credits' : used for sms
	 *  'message' : message
	 * )
	 * prepares the standard object for bulk message that
	 * is required by all the validators
	 * @return BulkMessage
	 */
	protected function prepareValidationMessage( $params ){

		//prepare queuing params
		//validating data
		$this->logger->debug("@@In validate user_store_id $params[store_type] ".$campaign_id);

		//validating data
		$C_bulk_message = new BulkMessage();
		$C_bulk_message->setId( $this->msg_id );
		$C_bulk_message->setCampaignId( $this->campaign_id );
		$C_bulk_message->setGroupId( $params['camp_group'] );
		$C_bulk_message->setScheduledType( $params['send_when'] );
		$C_bulk_message->setHours( $params['hours'] );
		$C_bulk_message->setMinutes( $params['minutes'] );
		$C_bulk_message->setDay( $params['cron_day'] );
		$C_bulk_message->setMonth( $params['cron_month'] );
		$C_bulk_message->setWeek( $params['cron_week'] );
		$C_bulk_message->setMessage( $params['message'] );
		$C_bulk_message->setCronHours( $params['cron_hours'] );
		$C_bulk_message->setCronMinutes( $params['cron_minutes'] );
		$C_bulk_message->setOrgCredits( $params['org_credits'] );
		$C_bulk_message->setDateField( $params['date_field'] );
		$C_bulk_message->setType( $this->validator_type );
		$C_bulk_message->setStoreType( $params['store_type'] );
		$C_bulk_message->setParams($params);

		return $C_bulk_message;
	}

	/**
	 * Replace group tags while showing preview
	 */
	private function getGroupTags( &$default_template_options , $group_id ){

		try{
			
			$this->logger->debug("Start Group Tags fetching method");
			include_once 'base_model/campaigns/class.GroupDetailService.php';
			$C_GroupDetailModel = new GroupDetailModel();
			$C_GroupDetailModel->load( $group_id );
			$tags = $C_GroupDetailModel->getGroupTags();
			$this->logger->debug("Group Tags:".print_r( $tags , true ));
			
			if( !empty( $tags ) ){
				$this->logger->debug("In");
				foreach ( $tags as $key => $value ){
					$default_template_options[$key] = ((strlen($value)>0)?($value):('NA'));
				}
			}
			$this->logger->debug("End Group Tags fetching method");
		}catch( Exception $e ){
			$this->logger->error("While fetching Group Tags fetching : ".$e->getMessage());	
		}
	}

	/**
	* check if disc code pin type coupon series is present or not 
	* based on that we will allow user to add or remove voucher related tags
	*/
	protected function isDiscCodePinCSAttached( $campaign_id ){

		$this->logger->debug("@@check for disc_code_pin type:".$campaign_id);

		$this->C_campaign_controller->load( $campaign_id );
		$campaign_details = $this->C_campaign_controller->getDetails();

		$campaign_type = $campaign_details['type'];
		$attached_voucher_series_id = $campaign_details['voucher_series_id'];

		if( $campaign_type == 'outbound' && $attached_voucher_series_id > 0 ){

			$C_coupon_series_manager = new CouponSeriesManager();
				$C_coupon_series_manager->loadById( $attached_voucher_series_id );

			$client_handling_type =
				$C_coupon_series_manager->C_voucher_series_model_extension->getClientHandlingType();

			if( $client_handling_type == "DISC_CODE_PIN" ){
				$this->logger->debug("@@disc_code_pin CS is attached");
				return true;
			}
			$this->logger->debug("@@disc_code_pin CS is not attached");
			return false;
		}
		$this->logger->debug("@@disc_code_pin CS is not attached");
		return false;
	}

	/**
	* check for voucher tag in case of disc code pin type
	*/
	protected function validateVoucherTagsForDiscCodePin( $campaign_id , $entire_message ){

		$this->logger->debug("@@validate for disc_code_pin type:".$campaign_id.":".$entire_message);

		$find_voucher = '{{voucher}}';
		$find_days_until_expiry = '{{valid_days_from_create}}';
		$find_voucher_expiry_date = '{{valid_till_date.';

		if( $this->isDiscCodePinCSAttached( $campaign_id ) ){

			$this->logger->debug("@@validate for disc_code_pin present");
			if( strpos( $entire_message, $find_days_until_expiry ) !== false 
				|| strpos( $entire_message, $find_voucher_expiry_date ) !== false 
				|| strpos( $entire_message, $find_voucher ) !== false ){

				$this->logger->debug("@@validate for disc_code_pin voucher tag present");
				//throw new Exception( _campaign("Coupon tags are not allowed for the message which has the coupon series with DISC_CODE_PIN as a client hadling type").'.' );
			}
		}
	}
}
?>
