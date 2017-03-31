<?php

include_once 'helper/Timer.php';
include_once 'helper/Voucher.php';
include_once 'helper/OrganizationLoader.php';
include_once 'helper/ShardedDbase';
include_once 'model/campaign.php';
include_once 'helper/Util.php';
include_once 'helper/FTPManager.php';
include_once 'base_model/class.OrgSmsCredit.php';
include_once 'helper/coupons/CouponManager.php';
include_once 'helper/coupons/CouponSeriesManager.php';
include_once 'model_extension/class.OrganizationModelExtension.php';
include_once 'base_model/class.OuCustomSender.php';
include_once 'business_controller/AdminUserController.php';
include_once 'model_extension/campaigns/class.CampaignModelExtension.php';
include_once 'business_controller/campaigns/emails/OpenRateTracking.php';
include_once 'helper/scheduler/CampaignGroupCallBack.php';
include_once 'model_extension/campaigns/class.CampaignGroupModelExtension.php';
include_once 'business_controller/campaigns/message/impl/BulkMessageValidatorFactory.php';
include_once 'business_controller/campaigns/message/api/BulkMessageTypes.php';
include_once 'business_controller/campaigns/message/api/ValidationStep.php';
include_once 'base_model/campaigns/class.BulkMessage.php';
include_once 'business_controller/campaigns/audience/impl/UploaderFactory.php';
include_once 'business_controller/campaigns/audience/impl/CampaignStatus.php';
include_once 'business_controller/campaigns/DeDupManager.php';
include_once 'helper/DownloadManager.php';
include_once 'business_controller/campaigns/library/VenenoDataDetailsHandler.php';
include_once 'business_controller/campaigns/library/VenenoDetailsHandler.php';
include_once 'business_controller/campaigns/surveys/SurveyController.php';
include_once 'base_model/class.CustomSender.php';
include_once 'health_dashboard/EntityHealthTracker.php';
include_once 'luci-php-sdk/LuciClient.php' ;

/**
 * Campaign Controller
 *
 *
 * @author Ankit Govil
 */
class CampaignController extends BaseController{

	private $ConfigManager;
	private $CouponManager;
	private $campaignsModel;
	private $OrgController;
	
	private $C_download_manager;
	private $C_open_rate_tracking;
	
	private $upload_audience_path;
	private $ftp_audience_path;
	private $ftp_copy_path;
	private $timer_prev;
	private $timer_curr;
	private $org_sms_credit_model;
	
	private $FtpManager;
	public  $campaign_model;
	public 	$campaign_model_extension;
	protected $CampaignGroup;
	public $C_BulkMessage;
	private $C_dedup_manager;
	private $C_coupon_series_manager;

	private $rule_client;
	private $security_manager;
	
	public  $memcache_mgr;
	private $peb;
	private $C_nsadmin;
	
	public function __construct(){
		
		parent::__construct();
		$this->upload_audience_path = "/mnt/campaigns/uploads/";
		$this->CouponManager = new CouponManager();
		$this->ftp_audience_path = "/home/capillary/campaigns/";
		$this->ftp_copy_path = "/mnt/campaigns/import/org_{{org-id}}/campaign_{{campaign-id}}/group_{{group-name}}/";
		$this->ConfigManager = new ConfigManager();
		$this->campaignsModel = new CampaignModel();
		$this->OrgController = new OrganizationController();
		$this->C_open_rate_tracking = new OpenRateTracking( );
		$this->campaign_model_extension = new CampaignModelExtension();
		$this->CampaignGroup = new CampaignGroupModelExtension();
		
		$this->campaign_model = $this->campaign_model_extension;
		$this->C_download_manager = new DownloadManager();

		$this->timer_prev = new Timer("timer_prev");
		$this->timer_curr = new Timer("timer_curr");

		$this->org_sms_credit_model = new OrgSmsCreditModel();
		$this->C_BulkMessage = new BulkMessage();
		$this->memcache_mgr = MemcacheMgr::getInstance();
		
		$this->C_dedup_manager = new DeDupManager();
		$this->C_coupon_series_manager = new CouponSeriesManager();
		
		$this->peb = new PEBServiceThriftClient();
		$this->C_nsadmin = new NSAdminController();
	}

	/**
	 *
	 * @param unknown_type $groups_ids: csv of group ids as a string
	 */
	public function getGroupDetailsbyGroupIds( $groups_ids ){
		return $this->campaign_model_extension->getGroupDetailsbyGroupIds( $groups_ids );

	}

	public function getAllCampainRunningStatus(){

		return $this->campaign_model_extension->getAllCampainRunningStatus();
	}
	
	public function getCampaignRunningStatus( $campaign_type = 'outbound' ){
		return $this->campaign_model_extension->getCampainRunningStatus( $campaign_type );
	}
	
	/**
	 * 
	 * @param $group_id
	 */
	public function getGroupDetails( $group_id ){
		
		return $this->campaign_model_extension->getGroupDetails( $group_id );
	}
	
	/*
	 * fetches group details based on the campaign id
	 */
	public function getCampaignGroupsByCampaignIds( $campaignids  ){

		return $this->campaign_model_extension->getCampaignGroupsByCampaignIds( $campaignids );
	}

	/**
	 * @param unknown_type $groupId
	 */
	public function getCountForGroup( $groupId ){

		return $this->campaign_model->getCountForGroup( $groupId );
	}	
		
	public function deDupUsingBitMasking( $campaign_id, $id_labels_map, $create_new_groups = false ){
		
		//If new groups required, check for duplicate group names
		if( $create_new_groups ){
				
			$label_copy = $id_labels_map;
			array_shift( $label_copy );
			
			foreach( $label_copy as $group_id => $group_label ){
				
				$this->campaign_model_extension->isGroupNameExists( $group_label, $campaign_id );
			}
		}

		//DeDuplication
		try{
			
			$this->C_dedup_manager->deDuplicate( $campaign_id, $id_labels_map, $create_new_groups );
		}catch( Exception $e ){
			
			throw $e;
		}
	}

	public function updateReachability($campaign_id , $group_id){
        include_once 'business_controller/ReachabilityController.php';
            $this->reach_controller = new ReachabilityController();
            $channel = 'ALL';
            $this->reach_controller->updateReachabilityStatus($group_id, $campaign_id, $channel);
    }
	
	public function mergeLists( $campaign_id, $id_labels_map ){
		
		$campaign_model_extension = new CampaignModelExtension();
		$campaign_group_model_extension = new CampaignGroupModelExtension();
		$group_detail_model = new GroupDetailModel();
		
		$new_group = $id_labels_map['new_group'];
		$this->campaign_model_extension->isGroupNameExists( $new_group, $campaign_id );
		$this->logger->debug( "New group name $new_group available" );
		unset( $id_labels_map['new_group'] );
		
		$this->logger->debug( "Groups to be merged: ".print_r( $id_labels_map, true ) );
		//Merging
		try{

			$customer_count = 0;
			$campaign_group_bucket_handlers = array();
			foreach( $id_labels_map as $group_id => $label ){
			
				$campaign_group_bucket_handler = new CampaignGroupBucketHandler( $group_id );
				$campaign_group_bucket_handlers[$group_id] = $campaign_group_bucket_handler;
				$group_detail_model->load( $group_id );
				$customer_count += $group_detail_model->getCustomerCount();
			}
			
			$this->logger->debug( "Total Customer count for new group :".$customer_count );
			
			$new_group_id =
			$campaign_model_extension->insertGroupDetails(
					$campaign_id,
					$new_group,
					'campaign_users',
					$customer_count );
			
			$this->logger->debug( "New merge group created : ".$new_group_id );
			$provider_type = "uploadSubscribers";
			$this->logger->debug( "Inserting into audience groups" );
			$campaign_model_extension->
				insertAudienceGroups( $campaign_id, $provider_type, $new_group_id );
			
			$campaign_group_bucket_handler_new_group =
			new CampaignGroupBucketHandler( $new_group_id );
			
			//Foreach group merge into newly created group
			$this->logger->debug( "Merging selected groups into new group" );
			foreach ( $campaign_group_bucket_handlers as $group_id => $handler ){
				
				$campaign_group_bucket_handler_new_group->
					mergeGroups( $handler, $group_id );
			}
			$campaign_group_model_extension->updateGroupMetaInfo( $new_group_id );
			
			//Updating total clients column
			$group_detail_model->load( $new_group_id );
			$total_clients = $group_detail_model->getCustomerCount(); 
			$group_detail_model->setTotalClients( $total_clients );
			$group_detail_model->update( $new_group_id );
			$this->updateReachability($campaign_id,$new_group_id);
		}catch( Exception $e ){
				
			throw $e;
		}
	}
	
	public function getTypesOfCampaign( $org_id ){
		
		return $this->campaign_model_extension->getTypesOfCampaign( $org_id );
		
	}
	
	/**
	 * @param unknown_type $where_filter
	 * @return multitype:multitype:string unknown
	 */
	public function createTableContentForHomePage ( $where_filter, $search_filter , $limit_filter ){

		$org_id = $this->org_id;	
		
		
		//To get Campaign Details for ALL Campaigns		
		$this->results = 
			$this->campaign_model_extension->
			getDataForHomePage( $org_id, $where_filter, $search_filter , $limit_filter );
		return $this->results;	
		
	}
	
	
	/*
	 * fetches group details based on the campaign id
	 */
	public function getCampaignGroupsByCampaignIdAsOptions( $campaignid ){

		return $this->getGroupsAsOptionByCampaignId( $campaignid );
	}

	public function validatedate( $date, $msg='' ){

		if(strtotime( $date ) < strtotime(date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d')))))){
			throw new Exception(_campaign("Please select correct")." $msg "._campaign("date"));
		}
	}

	public function getUsersFromGroup( $group_id, $tag_array ){}

	/**
	 *
	 * @param unknown_type $group_id
	 * @param unknown_type $campaign_id
	 * @param unknown_type $msg
	 * @param unknown_type $msg_subject
	 */
	public function getUsersPreviewTable($group_id, $campaign_id, $msg, $msg_subject = false , $queue_type = 'SMS',
	 	$extra_params = array( 'store_type' => 'registered_store' ) ){
	
		$this->logger->debug('@@In validate '.$campaign_id);
		$params = array( 'message' => $msg , 'subject' => $msg_subject );
		$params = json_encode( $params );
	
		//validating data
		$C_bulk_message = new BulkMessage();
		$C_bulk_message->setCampaignId( $campaign_id );
		$C_bulk_message->setGroupId( $group_id );
		$C_bulk_message->setParams( $params );
		$C_bulk_message->setExtraParams( $extra_params );
		$C_bulk_message->setStoreType( $extra_params['store_type'] );
	
		$C_bulk_validator = BulkMessageValidatorFactory::getValidator( BulkMessageTypes::valueOf( $queue_type ) );
	
		$messages_list = $C_bulk_validator->validate( $C_bulk_message , ValidationStep::$PREVIEW );
	
		return $messages_list;
	}
	
	public function groupDirectMailDetails( $tags_template, $group_id, $campaign_id){}
	
	/**
	 *
	 * @param unknown_type $group_id
	 * @param unknown_type $campaign_id
	 * @param unknown_type $msg
	 * @param unknown_type $msg_subject
	 */
	public function getStoreTaskStoresPreviewTable( $msg, $stores ){

		$messages_list = array();

		$count = 0;
		foreach( $stores as $store_id ){

			$count++;
			$profile = StoreProfile::getById( $store_id );

			$template_arguments['store_name'] = $profile->first_name .' '.$profile->last_name;

			$message = Util::templateReplace( $msg, $template_arguments, array( "{{NA}}" ) );
			$message = stripslashes( $message );

			$this->logger->debug( "msg=$msg. Args: ".print_r($template_arguments, true)."\nResult:$message" );

			array_push(
			$messages_list,
			array(
					"to"=>$profile->mobile, 
					"msg"=>$message, 
					'chars' => strlen( $message ) 
			)
			);

			if( $count == 9 ) break;
		}
		return $messages_list;
	}

	/**
	 * @param unknown $org_id
	 * @param unknown $params
	 * @return string
	 */
	public function prepareCampaignSettingsValues( $org_id , $params ){
		
		$values = array();
		foreach( $params as $key => $value ){
			if( !in_array( $key, array( 'is_form_submitted' , 'random_key_generator' ) ))
				array_push( $values, "  ( '$org_id','$key','$value' ) " );
		}

		return implode(",", $values);
	}
	
	/**
	 *
	 * @param unknown_type $values: set of values for organization to be inserted
	 */
	function insertMsgingDefaultValues( $values ){

		return $this->campaign_model_extension->insertMsgingDefaultValues( $values );

	}

	/**
	 *
	 * @param unknown_type $status: status of message
	 * @param unknown_type $type : type of campaign
	 */
	public function getQueuedMessages( $status, $type ){

		return $this->campaign_model_extension->getQueuedMessages( $this->org_id, $status, $type);
	}


	/**
	 *
	 * @param $status
	 * @param $type
	 */

	public function getQueuedEmailMessages( $status, $type ){

		return $this->campaign_model_extension->getQueuedEmailMessages( $status, $type);
	}

	/**
	 *
	 * @param unknown_type $msg_id: message for which data has to be fetched
	 */
	public function getMessageScheduleType( $msg_id ){

		return $this->campaign_model_extension->getMessageScheduleType( $msg_id );

	}

	/*
	 * It makes a call to thrift to send message to user
	 */

	public function sendMessageToUsers($campaign_id,$group_id,$sent_msg,$bulk_credits,
			$default_arguments, $queue_id, $guid ){

		$msg_id = Util::sendBulkSMSToGroupsnew( $sent_msg, $this->org_id, $group_id, 
				$default_arguments, $queue_id, $guid );

		return $msg_id;
	}

	/*
	 * it fetches user
	 */
	public function getUsersByQueueId( $id , $type){

		$md = $this->campaign_model_extension->getQueuedMessageDetailsById($id);
		$params = json_decode($md['params']);
		$default_args = json_decode( $md['default_arguments'], true );
		$group_id = $md['group_id'];
		$campaign_id = $md['campaign_id'];
		$msg = $params->message;

		$queue_type = 'SMS';
		$extra_params['store_type'] = $default_args['store_type'];
		if ( strtolower( $type ) == 'email' || strtolower( $type ) == 'email_reminder'){
			
			$msg_subject = $params->subject;
			$msg = $params->message;
			$queue_type = 'EMAIL';
			
		}else if( strtolower( $type ) == 'customer_task' ){
			
			$queue_type = 'CUSTOMER_TASK';
			$msg = stripcslashes( rawurldecode( $params->store_task_display_text ) );
			$msg_subject = stripcslashes( rawurldecode( $params->store_task_display_title ) );
		}elseif( strtolower( $type ) == 'call_task' || strtolower( $type ) == 'call_task_reminder' ){

			$msg_subject = $params->subject;
			$msg = $params->message;
			$queue_type = 'CALL_TASK';
			if( $params->description )
				$messsage = "<br/><b>"._campaign("Description :")."</b> ".$params->description."<br/>";
			$msg = $messsage."<br/><b>"._campaign("Message :")."</b><br/><br/> ".$msg."<br/><br/>";
		}else
			$msg_subject = false;
		
		return $this->getUsersPreviewTable( $group_id, $campaign_id, $msg , $msg_subject , $queue_type, $extra_params );
	}
	
	/**
	 * Message id whose status is to be change
	 * @param unknown_type $message_id
	 */
	public function reQueueMessage( $message_id ){

		return $this->campaign_model_extension->reQueueMessage( $message_id, $this->user_id );
	}

	/**
	 * Approve message
	 * @param unknown_type $message_id : update message id
	 */

	public function approveMessage( $message_id ){

		return $this->campaign_model_extension->approveMessage( $message_id, $this->user_id );
	}

	/**
	 * get the message count for particular campaign and group
	 * @param $campaign_id
	 * @param $group_id
	 */
	public function getMessageCount( $campaign_id, $group_id ){

		return $this->campaign_model_extension->getMessageCount( $campaign_id, $group_id );

	}

	public function getSecondaryTemplates($msg_id , $module='OUTBOUND'){
		$sql = "SELECT * FROM `msging`.`msg_secondary_templates` WHERE org_id = $this->org_id
				AND ref_id = $msg_id and module= '$module' AND is_deleted=0" ;

		$db = new Dbase('msging');
		$result = $db->query($sql);		
		$all_templates = array() ;
		$base_language_id = $this->OrgController->getBaseLanguageId() ;
		$all_lang = $this->OrgController->getOrgLanguages() ;
		foreach($result as $key=>$value){
			$lang_id = $value['lang_id'] ;
			$all_templates[$lang_id] = array() ;
			$default_params = json_decode($value['default_params'],true) ;
			if(count($default_params)>0)
				$all_templates[$lang_id] = array_merge($all_templates[$lang_id] , $default_params) ;
			$all_templates[$lang_id]['html_content'] = $value['msg_body']  ;
			$all_templates[$lang_id]['secondary_template_id']	 = 	$value['id'] ;
			$all_templates[$lang_id]['is_base_template'] = 	0 ;
			$all_templates[$lang_id]['language_name'] = $all_lang[$lang_id] ;
			if($lang_id == $base_language_id)
				$all_templates[$lang_id]['is_base_template'] = 	1 ;
		}
		$this->logger->debug("secondary templates are : ".print_r($all_templates,true)) ;
		return $all_templates ;
	}

	public function getDefaultValuesbyMessageId( $message_id ){

		$values = array();
		
		if ( $message_id ){

			$this->C_BulkMessage->load( $message_id ) ;
			
			$default_values = $this->C_BulkMessage->getHash();

			$default_arguments = json_decode( $default_values['default_arguments'] , true );
			
			if( count($default_arguments) > 0 )
				$values = array_merge( $values , $default_arguments );
			
			$params = json_decode( $default_values['params']);
			$values['message'] = $params->message;
			$values['type'] = $default_values['type'];
			$values['subject'] = $params->subject;
			$values['description'] = $params->description;
			$values['campaign_id'] = $default_values['campaign_id'];
			$values['group_id'] = $default_values['group_id'];
			$values['signature'] = $params->signature;
			$values['signature_value'] = $params->signature_value;
			if($params->is_drag_drop && $params->drag_drop_id){
				$values['is_drag_drop'] = $params->is_drag_drop;
				$values['drag_drop_id'] = $params->drag_drop_id;
			}

			$values['secondary_templates'] = array() ;
			$values['secondary_templates'] = $this->getSecondaryTemplates($message_id , 'OUTBOUND') ;
			$this->logger->debug("secondary templates returned : ".print_r($secondary_templates,true)) ;
			if ( $default_values['scheduled_type'] == 'IMMEDIATELY'){

				$values['send_when'] = 'IMMEDIATE';
			}else if ( $default_values['scheduled_type'] == 'SCHEDULED'){

				$values['send_when'] = 'SCHEDULE';
				$values['max_users'] = $params->max_users;
				$values['date_time'] = explode( ' ', $this->campaign_model->getDateFromReminder( $message_id ) );
					
				//timezone change
				$cron_minutes = $values['date_time'][0];
				$cron_hours = $values['date_time'][1];
				$changed_time = Util::convertTimeToCurrentOrgTimeZone("$cron_hours:$cron_minutes");
				$values['cron_minutes'] = date( 'i' , strtotime( $changed_time) ) ;
				$values['cron_hours'] = date( 'H' , strtotime( $changed_time ) );
				
				//get days of month
				$values['cron_days_month'] = explode(',',$values['date_time'][2]);
				$values['cron_months'] = explode(',', $values['date_time'][3]);
				$values['cron_week'] = explode(',', $values['date_time'][4]);
			}else{

				$values['send_when'] = 'PARTICULAR_DATE';

				//time zone change
				$default_scheduled_time = Util::convertTimeToCurrentOrgTimeZone($default_values['scheduled_on']);
				$values['scheduled_on'] = explode(' ' , $default_scheduled_time );
				$values['date']	= $values['scheduled_on'][0];
				$values['time'] = explode(':', $values['scheduled_on'][1]);
				$values['hours'] = $values['time'][0];
				$values['minutes'] = $values['time'][1];
			}

			if( $default_values['type'] == 'CALL_TASK' || $default_values['type'] == 'CALL_TASK_REMINDER' ){
				$msg = "<b>"._campaign("Subject :")."</b> ".$values['subject']."<br/><br/>";
				if( $values['description'] )
					$msg .= "<b>"._campaign("Description :")."</b> ".$values['description']."<br/><br/>";
				$values['message'] = $msg."<b>"._campaign("Message :")."</b><br/> ".$values['message'];
			}
			
		}else{

			$values['message'] = _campaign("Dear ")."{{fullname}}, {"._campaign("type the rest of your message here")."}" ;
			$values['send_when'] = _campaign("IMMEDIATE");

			//cron default values to be put in
			$values['cron_day'] = '*';
			$values['cron_week'] = '*';
			$values['cron_month'] = '*';

			$values['hours'] = '10';
			$values['minutes'] = '00';
			$values['cron_hours'] = '10';
			$values['cron_minutes'] = '00';
			$values['date'] =  date( 'Y-m-d' );
		}
	
		return  $values;
	}

	public function getMessageDefaultArguments( $message_id ) {
		$default_arguments = null;
		
		if( $message_id ) {
			$this->C_BulkMessage->load( $message_id ) ;	
			$default_values = $this->C_BulkMessage->getHash();
			$default_arguments = json_decode( $default_values['default_arguments'] , true );
		}

		return $default_arguments;
	}

	public function getDefaultFieldValue(){

		return $this->campaign_model_extension->getDefaultFieldValue( $this->org_id );
	}

	public function reduceBulkCredits( $group_id ){

		return $this->campaign_model_extension->reduceBulkCredits( $this->org_id, $group_id );
	}

	public function getCampaignIdByGroupId ( $group_id ){

		return $this->campaign_model_extension->getCampaignIdByGroupId( $group_id );
	}


	public function addFieldsToBulkSmsCampaign( $campaign_id,$msg_id,$group_id,$queue_id ){

		$this->campaign_model_extension->addFieldsToBulkSmsCampaign( $campaign_id,$msg_id,
				$group_id, $queue_id );

	}

	public function updateLastSentDateForGroup( $group_id ){

		$this->campaign_model_extension->updateLastSentDateForGroup( $group_id );
	}


	/**
	 * @param unknown_type $campaign_id :
	 * @param unknown_type $group_id : group_id group
	 */
	public function getAudienceGroupByCampaignIdAndGroupId( $campaign_id, $group_id ){

		return $this->campaign_model_extension->getAudienceGroupByCampaignIdAndGroupId( $campaign_id, $group_id );
	}

	/**
	 * @param unknown_type $campaign_id :
	 * @param unknown_type $group_id : group_id group
	 */
	public function getAudienceDetailsByGroupId( $campaign_id, $group_id ){

		return $this->campaign_model_extension->getAudienceDetailsByGroupId( $campaign_id, $group_id );
	}

	public function getReminderIdBytaskId( $id ){

		return $this->campaign_model_extension->getReminderIdBytaskId( $id );

	}

	public function getDateFromRemidner( $id ){

		return $this->campaign_model_extension->getDateFromReminder( $id );


	}

	public function getReminderByReferenceId( $id ){

		return $this->campaign_model_extension->getReminderByReferenceId( $id );
	}

	public function getCampaignsMessageDetails( $campaign_id, $type = 'query', $approved_filter = '' ){

		return $this->campaign_model_extension->getCampaignsMessageDetails( $campaign_id, $type , $approved_filter );
	}

	/**
	 *
	 * @param unknown_type $campaign_id : campaign_id
	 */
	public function load( $campaign_id ){

		$this->campaign_model_extension->load( $campaign_id );
	}

	/**
	 * It is used to generate form for Upload Audience CSV page and also for Sticky group
	 * Common Method use for both
	 * @param $upload_form
	 * @param $sticky_group
	 */

	public function createUploadSubscribersForm( &$upload_form , $sticky_group = false ){


		$csvFile = new FileFieldInput( $upload_form , 'csvFile' , _campaign("Upload Csv File") );
		$csvFile->setHelpText( _campaign('Columns : email or mobile , user_name') );
		$upload_form->addInputField( $csvFile );


		if( !$sticky_group ) {

			$custom_tag = new ButtonFieldInput( $upload_form , 'add_custom_tag' , ' ', _campaign("+ Add Custom Tag"));
			$custom_tag->triggerClickToCloneCustomField( $upload_form->getFormName() );
			$upload_form->addInputField( $custom_tag );

			$custom_field_count = new HiddenFieldInput( $upload_form , 'custom_tag_count' , _campaign("Custom Tag Count") , '0' );
			$upload_form->addInputField( $custom_field_count );
			
		}

		$group_name = new TextFieldInput( $upload_form , 'group_name' , _campaign("Give list name") );
		$group_name->setDefaultValue( ($sticky_group) ? _campaign("Default Manager Group") : _campaign("Default Customer Management Group") );
		$group_name->setValidationRegEx( array('NON_EMPTY') );
		$group_name->setMandatory();
		$upload_form->addInputField( $group_name );

		$confirm = new CheckBoxFieldInput( $upload_form , 'confirm' , _campaign("Confirm") );
		$upload_form->addInputField( $confirm );

	}
		
	private function getFailureUploadAudienceFileHandle($str)
	{
		$file = "";
		$file = $file .$this->upload_audience_path ."failure/". $str . ".csv";
		$file_handle = fopen($file,"a");
		$header_values = "Mobile_Email \t   Name \n";
		fwrite($file_handle, $header_values);

		return $file_handle;
	}

	private function makeUploadAudienceFilePath($group_name,$group_id)
	{	
		$this->logger->debug(" Inside make upload ");
		$file_group = Util::uglify($group_name);
		$file_group_id = Util::uglify($group_id);
		$file_str = $file_group_id."_".$file_group;
		return $file_str;
						
	}

	private function getSuccessUploadAudienceFileHandle($str)
	{
		$file = "";
		$file = $file .$this->upload_audience_path ."success/". $str . ".csv";
		$file_handle = fopen($file,"a");
		$header_values = "Mobile_Email \t   Name \n";
		fwrite($file_handle, $header_values);
		return $file_handle;
	}
	
	/**
	 * Refactored part
	 * 
	 */
	
	public function prepareViaCsv($params, $file, $campaign_id, $upload_type='campaign_users', $import_type = 'mobile')
	{
		try{
			$file_parts = pathinfo( $file['name'] );
			
			if($file_parts['extension'] != 'csv' && $file_parts['extension'] != 'CSV')
			{
				throw new Exception( _campaign("Upload Only CSV File!") );
			}
			else
			{
				$filename = $file['tmp_name'];
				$file_id = $this->prepare($params, $filename, $campaign_id, $upload_type, $import_type);
	
				return $file_id;
			}
		}
		catch(Exception $e)
		{
			$this->logger->error("Caught exception in prepare via csv");
			throw new Exception($e->getMessage());
		}
		
	}
	
	/**
	 * @param unknown $params
	 * @param unknown $file
	 * @param unknown $campaign_id
	 * @param string $type
	 * @param string $import_type
	 * 
	 * @return array of preview data
	 */
	
	function prepare($params , $filename , $campaign_id , $type = 'campaign_users',$import_type = 'mobile' )
	{
//		try
	//	{
			$this->campaign_model_extension->isGroupNameExists( $params['group_name'], $campaign_id );
			//init
			$this->logger->debug("Import type = $import_type");
			$uploader = UploaderFactory::getUploaderClass($import_type);
			$uploader->initColumnMappings($params);
			$uploader->setUploadType($type);
			$group_name = trim($params['group_name']);
			$uploader->setGroupName($group_name);
			$uploader->setCampaignId($campaign_id);
			$uploader->setFileName($filename);
			$token = $params['token_field'];
			$uploader->setCampaignStatus($token);
			
			//purges data into campaign_files_history table
			$file_id = $uploader->purge();

			if($file_id)
			{
				$this->logger->debug("File Id = $file_id");
			}
			else
			{
				$this->logger->debug("Purge data failed. Throwing exception");
				throw new Exception(_campaign("Error purging upload details"));
			}
			//$campaignStatusMgr = new CampaignStatus($file_id);
			
			$uploader->prepare();
			$uploader->validate();
			
			$valid_count = $uploader->getValidRecordsCount();
			$this->logger->debug("madhu test uploader".$valid_count);
			if($type == 'sticky_group' && $valid_count == 0)
			{
				throw new Exception(_campaign("No valid records to import"));
			}		
		//}
//		catch(Exception $e)
	//	{
		//	$this->logger->error("Caught exception in prepare = ".$e->getMessage());
			//throw new Exception("Failed preparing the import");
	//	}			
		//$preview_data = $uploader->preview(100);
		return $file_id;
	}
	
	/**
	 * 
	 * preview 
	 */
	
	public function preview($file_id,$limit = 10)
	{
		$uploader = $this->loadUsingFileDetails($file_id);
		return $uploader->preview($limit);
	}
	
	public function upload($file_id)
	{
		try{
			
			$uploader = $this->loadUsingFileDetails($file_id);
			$group_name = $uploader->getGroupName();
			$campaign_id = $uploader->getCampaignId();
			$type = $uploader->getUploadType();
			$customer_count = $uploader->getValidRecordsCount();
			$provider_type = 'uploadSubscribers';
			$upload_params = $uploader->getParams();
			$this->logger->debug( "@@@HParams uploader " .print_r( $upload_params, true ) );
			
			
			//if test and control is enable then target type = 'ALL' else 'TEST'
			if( isset( $upload_params['question_tnc'] ) && 
				$upload_params['question_tnc'] == 'on' ) {
				
				$target_type = 'ALL';
				$group_name_all = $group_name." ( OverAll ) ";
			} else {
				
				$target_type = 'TEST';
				$group_name_all = $group_name;
			}
			
			$group_id_all = $this->campaign_model_extension->
					insertGroupDetails( $campaign_id , $group_name_all ,
					$type , $customer_count , $target_type );
			
			if( $target_type == 'TEST' )
				$uploader->updateGroupIdByFileId( $file_id, $group_id_all );
			//$group_id = 1;
			$uploader->upload( $group_id_all );
			
			$audience_group_id =
				$this->campaign_model_extension->
				insertAudienceGroups( $campaign_id , $provider_type , $group_id_all );
			$status = $this->CampaignGroup->updateGroupMetaInfo( $group_id_all );
			
			if( $target_type == 'ALL' ) {
				
				//change customer count
				$test_ratio = ( $upload_params['control_group'] > 100 )
					 ? 100 : $upload_params['control_group'] ;
				$control_ratio = 100 - $test_ratio;
				$this->logger->debug( " target type is all so creating test and control
						test ratio $test_ratio and control ratio $control_ratio " );
				$grp_devide = array();
				$group_id_test = $this->campaign_model_extension->
					insertGroupDetails( $campaign_id , $group_name ,
					$type , $customer_count , 'TEST' );
				
				$audience_group_id_test =
					$this->campaign_model_extension->
					insertAudienceGroups( $campaign_id , $provider_type , $group_id_test );
				
				$uploader->updateGroupIdByFileId( $file_id, $group_id_test );
				
				array_push(
				$grp_devide ,
				array( 'group_id' => $group_id_test ,
					'percentage' => $test_ratio ) );
				
				$group_name_control = $group_name." ( Control List ) ";
				$group_id_control = $this->campaign_model_extension->
					insertGroupDetails( $campaign_id , $group_name_control ,
					$type , $customer_count , 'CONTROL' );
				
				$audience_group_id_control =
					$this->campaign_model_extension->
					insertAudienceGroups( $campaign_id , $provider_type , $group_id_control );
				
				array_push(
				$grp_devide ,
				array( 'group_id' => $group_id_control ,
				'percentage' => $control_ratio ) );
				
				$this->testControlRandomGroupCreation(
						$campaign_id , $group_id_all , $grp_devide , $customer_count, false );
				$this->makeFriendship( $group_id_test, $group_id_control, $customer_count );
				$this->CampaignGroup->updateGroupMetaInfo( $group_id_test );
				$this->CampaignGroup->updateGroupMetaInfo( $group_id_control );
			}
			$this->updateReachability($campaign_id,$group_id_all);			
			//$this->generateErrorReport($uploader, $group_id);
		}catch(Exception $e)
		{
			$this->logger->error("Caught exception while uploading. error = ".$e->getMessage());
			throw new Exception($e->getMessage());
			
		}
		return true;
	}
	
	private function makeFriendship( $test_group_id, $control_group_id, $customer_count = -1){
	
		$C_test_group_details = new GroupDetailModel();
		$C_test_group_details->load( $test_group_id );
	
		$C_test_group_details->setPeerGroupID( $control_group_id );
	
		$C_control_group_details = new GroupDetailModel();
		$C_control_group_details->load( $control_group_id );
		$C_control_group_details->setPeerGroupID( $test_group_id );
	
		//update both
		if( $customer_count >=0){
	
			$C_test_group_details->setType( 'CAMPAIGN_USERS' );
			$C_control_group_details->setType( 'CAMPAIGN_USERS' );
	
			$C_test_group_details->setTotalClients( $customer_count );
			$C_control_group_details->setTotalClients( $customer_count );
		}
	
		$C_test_group_details->update( $test_group_id );
		$C_control_group_details->update( $control_group_id );
	}
	
	
	private function generateErrorReport($uploader, $group_id)
	{
		$group_name = $uploader->getGroupName();
		$file_group = Util::uglify($group_name);
		$file_group_id = Util::uglify($group_id);
		$str = $file_group."_".$file_group_id;
		//$campaign_id = $uploader->getCampaignId();
		$error_handle = $this->getFailureUploadAudienceFileHandle($str);
		while(TRUE)
		{
			$error_records = $uploader->getErrorRecords();
			if(empty($error_records))
			{
				break;
			}
			foreach($error_records as $key=>$val)
			{
				$mobile_email = $val['input'];
				$name = $val['name'];
				//$this->logger->debug("ERROR is: ".print_r($error,true));
				$error_line = "$mobile_email,$name";
				$this->logger->debug("Error line : $error_line");
				fwrite( $error_handle , $error_line."\n" );
			}
			unset($error_records);
			unset($error_line);
		}
		fclose($error_handle);
	}
	/**
	 * 
	 * @param unknown $file_id
	 * @return mixed $status array
	 * $status = array(FILE_READ => array(), TEMPDB => array());
	 */
	public function getStatus($token)
	{
		$campaignStatus = new CampaignStatus($token);
		$status = $campaignStatus->get();
		return $status;
	}
	
	public function setStatus($token, $status_key, $status_value)
	{
		$campaignStatus = new CampaignStatus($token);
		$status = $campaignStatus->get();
		$campaignStatus->set($status_key, $status_value);
	}
	
	public function getFileIdFromToken($token)
	{
		$db = new Dbase('campaigns');
		$org_id = $this->org_id;
		$sql = "
				SELECT id FROM campaigns.upload_files_history
				WHERE token = '$token' AND org_id = $org_id
				";
		$file_id = $db->query_scalar($sql);
		return $file_id;
	}
	
	/**
	 * @return AudienceUploader
	 */
	
	private function loadUsingFileDetails($file_id)
	{
		$file_details = $this->getUploadDetails($file_id);
		$upload_type = $file_details['upload_type'];
		$import_type = $file_details['import_type'];
		$campaign_id = $file_details['campaign_id'];
		$group_id = $file_details['group_id'];
		$group_name = $file_details['group_name'];
		$token = $file_details['token'];
		$params = $file_details['params'];
		$json_decode = $params;
		
		$temp_table_name = $file_details['temp_table_name'];
		$this->logger->debug("Import Type = $import_type");
		$uploader = UploaderFactory::getUploaderClass($import_type);
		
		//init
		$uploader->setCampaignId($campaign_id);
		$uploader->setGroupId($group_id);
		$uploader->setGroupName($group_name);
		$uploader->setUploadType($upload_type);
		$uploader->setTempTableName($temp_table_name);
		$params = $uploader->getParams();
		$uploader->initColumnMappings($params);
		$uploader->setCampaignStatus($token);
		$uploader->setParamsJson( $json_decode );
		
		return $uploader;
	}
	
	private function getUploadDetails($file_id)
	{
		$db = new Dbase('campaigns');
		$org_id = $this->org_id;
		$sql = "
				SELECT * FROM campaigns.upload_files_history
				WHERE id = $file_id AND org_id = $org_id
				";
		$result = $db->query($sql);
		return $result[0];
	}
	
	public function getValidRecordsCount($file_id)
	{
		$uploader = $this->loadUsingFileDetails($file_id);
		return $uploader->getValidRecordsCount();
	}
	
	public function getErrorRecordsCount($file_id)
	{
		$uploader = $this->loadUsingFileDetails($file_id);
		return $uploader->getErrorRecordsCount();
	}
	
	public function getDetailsForDownload($file_id)
	{
		$uploader = $this->loadUsingFileDetails($file_id);
		$table = $uploader->getTempTableName();
		$sql = "
				SELECT * FROM $table
				WHERE status = 0
				";
		return array('temp_table'=>$table,'sql'=>$sql,'database'=>'Temp');
	}
	
	/**
	 * This method is used to upload audiences
	 */
	function uploadSubscribers( $params , $filename , $campaign_id , $type = 'campaign_users' ){}

	/**
	 * 
	 * @param unknown_type $batch_emails
	 * @param unknown_type $batch_mobiles
	 * @param unknown_type $batch_custom_tags
	 */
	private function processBatchesForUploadAudience( $batch_emails , 
		$batch_mobiles , $batch_custom_tags , $group_id , $with_custom_tag ){
			
			$this->logger->debug( " Process Batches for Upload Audience " );
			if( count($batch_emails) > 0 )
			{	
				$batch_email = $this->campaign_model_extension->checkUsersByEmail( $batch_emails );
			} 
			
			if( count($batch_mobiles) > 0 )
			{
				$batch_mobile = $this->campaign_model_extension->checkUsersByMobileForBatch( $batch_mobiles );
			}
			
			$this->logger->debug(' Batch Email :'.print_r( $batch_email , true ) );
			$this->logger->debug('@Batch Mobile :'.print_r( $batch_mobile , true ) );
			
			$batch = array();
			if( !empty($batch_email) && !empty($batch_mobile) )
				$batch = $batch_email + $batch_mobile;
			else if ( !empty($batch_email) && empty($batch_mobile) )
				$batch = $batch_email;
			else if ( !empty($batch_mobile) && empty($batch_email) )
				$batch = $batch_mobile;
			
			if( !empty($batch) ){
				$this->logger->debug('@@Batch :'.print_r( $batch , true ) );	
				$final = array();
				foreach( $batch as $key => $value ){
					
					$is_email_exists = (int) $value['is_email_exists'];
					$is_mobile_exists = (int) $value['is_mobile_exists'];
					
					$customer_name = $value['firstname'] . ' ' . $value['lastname'];
					$this->logger->debug( " Customer name " . $customer_name );
					$customer_name =  
						$this->campaign_model_extension->
						database_conn->realEscapeString( $customer_name );
					
					$this->logger->debug( " Customer name " . $customer_name );
					if( $batch_custom_tags[$key] && $with_custom_tag ){
						
						$insert = 
							"( '$group_id' , '".$value['user_id']."' , 
								'$customer_name' , 'customer' , 
								'".$batch_custom_tags[$key]."', 
								$is_mobile_exists, $is_email_exists )";
					}else{
						
						$insert = 
							"( '$group_id' , '".$value['user_id']."' , 
									'$customer_name' , 'customer' , '', 
									$is_mobile_exists, $is_email_exists )";
					}
					array_push( $final , $insert );
				}
				
				return $final;	
			}else{
				return ;
			}
	} 
	
	/**
	 * Selection_filter table is populated by the filter params and is updated
	 * if we have the id of primary key by the customer count
	 * @param interger $id i.e;aud_group_id
	 * @param string $filter_type
	 * @param string $filter_params
	 * @param string $filter_explaination
	 * @param integer $customers ;by default = 0
	 */
	function setSelectionFilter( $id , $filter_type , $filter_params , $filter_explaination = '' , $customers = 0 , $custom_ids = 0 ){

		return $this->campaign_model_extension->setSelectionFilter( $id , $filter_type , $filter_params , $filter_explaination , $customers , $custom_ids );
	}

	/**
	 * Get audience data from campaing id
	 * @param unknown_type $campaign_id
	 */
	public function getGroupDetailsForCampaignId( $campaign_id, $favourite = false, $search_filter = false ){

		return $this->campaign_model_extension->getAudienceDataByCampaignID( $campaign_id, $favourite, $search_filter );
	}

	/**
	 * Get group label by group id
	 */
	public function getGroupLabel( $group_id ){
		return $this->campaign_model_extension->getGroupLabel( $group_id );
	}

	public function getGroupsDetailsByGroupID ( $group_id ){
		
		$params = $this->campaign_model_extension->getCampaignGroupsByGroupId( $group_id , 'query');
		$this->logger->debug( " Group Details Params : " . print_r( $params , true ) );
		return $params;
	}

	public function getVoucherSeriesDetailsByOrg( $vch_series_id ){

		return $this->campaign_model->getVoucherSeriesDetailsByOrg( $vch_series_id );
	}
	
	public function getVoucherSeriesCodeById( $voucher_series_ids ){
	
		return $this->campaign_model->getVoucherSeriesCodeById( $voucher_series_ids );
	}

	/**
	 * get the filter details from audience group id when it is called from template selection
	 *
	 */
	public function getFilterDataByAudienceId( $audience_group_id ){
		return $this->campaign_model_extension->getFilterDetailsByAudienceGroupId( $audience_group_id );
	}
	
	public function getFilterDataByGroupIds($group_ids, $campaign_id) {
		return $this->campaign_model_extension->getFilterDataByGroupIds( $group_ids, $campaign_id );
	}

	/**
	 * Returns the hash map for the campaigns
	 */
	public function getDetails(){

		 return $this->campaign_model_extension->getHash();
	}

	/**
	 * Returns the campaign over view details
	 */
	public function getOverViewDetails( ){

		return $this->campaign_model_extension->getOverViewDetails( );
	}

	/**
	 * get All the campaigns
	 */
	public function getAll(){

		return $this->campaign_model_extension->getAll();
	}

	/**
	 * Checks if the campaign name exists
	 * @param $name
	 * @param $campaign_id
	 */
	private function isNameExists( $name, $campaign_id = false  , $is_ga_name = false ){
		
		if( !$is_ga_name ){
			$count = $this->campaign_model_extension->isNameExists( $name, $campaign_id );
			if( $count > 0 ) throw new Exception( _camp1('Campaign name already exists') );
		}else{
			$count = $this->campaign_model_extension->isGaNameExists( $name , $this->org_id );
			if( $count > 0 ) throw new Exception( _camp1('Campaign GA tracking name already exists') );
		}
	}
	
	/**
	 * Is date range valid
	 *
	 * @param $start_date
	 * @param $end_date
	 */
	private function isDateRangeValid( $start_date, $end_date ){

		if( $end_date < $start_date )
		throw new Exception( _campaign("End Date Has To Be Greater Than Start Date") );
	}

	/**
	 *
	 * CONTRACT(
	 *
	 * 	'name' => $name,
	 *  'start_date' => $start_date,
	 *  'end_date' => $end_date
	 * )
	 *
	 * Who changed the signature to have return id. What kind of code do we write
	 *
	 * @param $campaign_type
	 */
	public function create( $params, $campaign_type , $return_id = false ){

		try{
			
			$this->isNameExists( $params['name'] );

			if( $params['is_ga_enabled'] )
				$this->isNameExists( $params['ga_name'] , false , true );
						
			$this->isDateRangeValid( $params['start_date'], $params['end_date'] );

			$this->campaign_model_extension->setName( $params['name'] );
			$this->campaign_model_extension->setDescription( $params['desc'] );
			$this->campaign_model_extension->setType( $campaign_type );
			$this->campaign_model_extension->setStartDate( $params['start_date'] );
			$this->campaign_model_extension->setEndDate( $params['end_date'] );
			$this->campaign_model_extension->setActive( true );
			$this->campaign_model_extension->setOrgId( $this->org_id );
			$this->campaign_model_extension->setCreated( date( 'Y-m-d H:i:s' ) );
			$this->campaign_model_extension->setCreatedBy( $this->user_id );
			$this->campaign_model_extension->setVoucherSeriesId( -1 );
			$this->campaign_model_extension->setGaName( $params['ga_name'] );
			$this->campaign_model_extension->setGaSourceName( $params['ga_source_name'] );
			$this->campaign_model_extension->setIsGaEnabled( $params['is_ga_enabled'] );
			$this->campaign_model_extension->setCampaignRoiTypeId( $params["campaign_roi_type_id"] );
			$this->campaign_model_extension->setIsTestControlEnabled( $params['is_test_control_enabled'] );
			
			$campaign_id = $this->campaign_model_extension->insert();
		}catch( Exception $e ){

			return $e->getMessage();
		}

		if( $return_id )
			return $campaign_id;
			
		return 'SUCCESS';
	}

	/**
	 *
	 * CONTRACT(
	 *
	 * 	'name' => $name,
	 *  'start_date' => $start_date,
	 *  'end_date' => $end_date,
	 *  'ga_name' => value,
	 *  'ga_source_name' => value
	 * )
	 *
	 * @param $campaign_type
	 */
	public function update( $params, $campaign_id ){

		try{

			$this->isNameExists( $params['name'], $campaign_id );
			$this->isDateRangeValid( $params['start_date'], $params['end_date'] );

			$this->campaign_model_extension->load( $campaign_id );

			if( $params['ga_name'] ){

				$this->campaign_model_extension->setGaName( $params['ga_name'] );
				$this->campaign_model_extension->setGaSourceName( $params['ga_source_name'] );
			}
			
			$this->campaign_model_extension->setName( $params['name'] );
			$this->campaign_model_extension->setDescription( $params['desc'] );
			$this->campaign_model_extension->setStartDate( $params['start_date'] );
			$this->campaign_model_extension->setEndDate( $params['end_date'] );

			if( isset($params['is_test_control_enabled']) ){

				$this->campaign_model_extension->setIsTestControlEnabled( $params['is_test_control_enabled'] );
			}
			$this->campaign_model_extension->update( $campaign_id );
		}catch( Exception $e ){

			return $e->getMessage();
		}

		return 'SUCCESS';
	}


	//////////////////////////////////////////// REMINDER RELATED LOGIC /////////////////////////////////////////////////////////

	/**
	 * Get the conditions for reminders from audience groups of type CAMPAIGN
	 * @param $audience_group_ids
	 * @param $state
	 * @deprecated
	 */
	public function getReminderByCondition( $campaign_id , $state = 'CAMPAIGN' ){

		if( !$result )
			throw new Exception(_campaign("Reminder Details is not available for this campaign"));

		return $result;
	}

	/**
	 * get the conditions for audience group id and
	 */
	public function getMessageQueueDetails( $campaign_id , $group_id ){

		return $this->campaign_model_extension->getMessageQueueDetails( $campaign_id , $group_id );
	}

	/**
	 * Check if the reminder is exist by the audience group id and group id
	 */
	public function isReminderExists( $audience_group_id , $group_id ){

		return $this->campaign_model_extension->isReminderExists( $audience_group_id , $group_id );
	}

	/**
	 * Getting all reminders based on audience group ids and group ids.
	 * @param unknown $audience_group_ids
	 * @param unknown $group_ids
	 * @return Ambigous <multitype:, boolean>
	 */
	public function getAllReminderByIds( $audience_group_ids, $group_ids ){
		
		if( is_array( $audience_group_ids ))
			$audience_group_ids = implode(',',$audience_group_ids );
		
		if( is_array( $group_ids ))
			$group_ids = implode(',', $group_ids );
		
		return $this->campaign_model_extension->getAllRemindersByIds( $audience_group_ids, $group_ids );
	}
	
	/**
	 * Returns the array once passsed the group details
	 *
	 * @param $campaign group users
	 * @param $camp_groups
	 */
	private function getGroupsAsOptions( &$group_details, &$camp_groups, $type = 'customer' ){

		if( !is_array( $group_details ) )
		return array();
			
		foreach( $group_details as $row ){

			if( $type == 'customer' )
				$key = $row['group_label'];
			else
				$key = $row['group_label'] ." ( sticky ) ";

			$camp_groups[$key] = $row['group_id'];
		}

		return $camp_groups;
	}

	/**
	 * Returns the campaign id with customer count
	 *
	 * @param $campaign_id
	 */
	function getGroupsAsOptionByCampaignId( $campaign_id, $inlude_stciky_groups = true ){

		$camp_groups_details = $this->getCampaignGroupsByCampaignIds( $campaign_id );

		$camp_groups = array();
		$camp_groups = $this->getGroupsAsOptions( $camp_groups_details, $camp_groups );

		if( $inlude_stciky_groups ){

			$sticky_users = array();

			//The stciky group has convention of passing campaign id as -20
			$sticky_groups = $this->getCampaignGroupsByCampaignIds( -20 );

			$sticky_users = $this->getGroupsAsOptions( $sticky_groups, $sticky_users, 'sticky' );
			$sticky_users = $this->getGroupsAsOptions( $sticky_groups, $sticky_users, 'sticky_group' );

			if( is_array( $sticky_groups ) )
			$camp_groups = array_merge( $camp_groups , $sticky_users );
		}

		return $camp_groups;
	}

	public function getRevertOptionsForFilters( $audience_group_id , $campaign_id ){

		$result = $this->campaign_model_extension->getRevertOptionsForFilters( $audience_group_id , $campaign_id );


		$list_options = array();
		foreach($result as $row){
			$list_options[$row['time']] = $row['id'];
		}

		return $list_options;
	}

	public function getBulkCampaignDetailsByGroupsIds( $campaign_id , $gd ){
		return $this->campaignsModel->getBulkCampaignDetailsByGroupsIds( $campaign_id , $gd );
	}

	public function revertSelectionFilterSet( $change_id , $audience_group_id , $campaign_id ){

		$this->campaignsModel->revertSelectionFilterSet( $change_id , $audience_group_id , $campaign_id );
	}

	/**
	 *
	 * @param unknown_type $vch_series_id
	 */
	public function getCampaignNameByVchId( $vch_series_id ){

		$campaign_list = $this->campaign_model_extension->getCampaignNameByVchId( $vch_series_id, $this->org_id );

		foreach ( $campaign_list as $campagin ){

			switch (strtolower($campagin['type'])) {
				
				case 'outbound':
					if ( $vch_series_id == $campagin['voucher_series_id'])
						$campaign_name = $campagin['name'];
					break;
				
				case 'referral':
					
					$vch_series_ids = json_decode( $campagin['voucher_series_id'], true );
					if( $vch_series_ids->referee == $vch_series_id || $vch_series_ids->referer == $vch_series_id ){
							
					$campaign_name = $campagin['name'];
					}						
					break;
				
				case 'action':
					$vch_series_ids = json_decode( $campagin['voucher_series_id'], true );
					if($value = in_array( $vch_series_id, $vch_series_ids))
						$campaign_name = $campagin['name'];
					
					break;
				
			}
		}
		
		return $campaign_name;
	
	}
	
	/**
	 *
	 * @param unknown_type $vch_series_id
	 */
	public function getCampaignIdByVchId( $vch_series_id ){

		$campaign_list = $this->campaign_model_extension->getCampaignNameByVchId( $vch_series_id, $this->org_id );

		foreach ( $campaign_list as $campaign ){

			switch (strtolower($campaign['type'])) {
				
				case 'outbound':
					if ( $vch_series_id == $campaign['voucher_series_id'])
						$campaign_id = $campaign['id'];
					break;
				
				case 'referral':
					
					$vch_series_ids = json_decode( $campaign['voucher_series_id'], true );
					if( $vch_series_ids->referee == $vch_series_id || $vch_series_ids->referer == $vch_series_id ){
							
						$campaign_id = $campaign['id'];
					}						
					break;
				
				case 'action':
					$vch_series_ids = json_decode( $campaign['voucher_series_id'], true );
					if($value = in_array( $vch_series_id, $vch_series_ids))
						$campaign_id = $campaign['id'];
					
					break;
				
			}
		}
		
		return $campaign_id;
	
	}

	/**
	 * return all the campaign for a particular organization
	 *
	 */
	public function getCampaignAsOptions( $type = false ){

		$campaigns = $this->campaign_model_extension->getAll();
		$camp_array = array();
		
		if( $type ){
			
			foreach( $campaigns as $camp  ){
				
				if( strtolower( $type ) == strtolower( $camp['type'] ) ){
					$name = $camp['name'];
					$camp_array[$name] = $camp['id'];
				}
			}
		}else{
			
			foreach( $campaigns as $camp  ){
				$name = $camp['name'];
				$camp_array[$name] = $camp['id'];
			}
		}
		
		return $camp_array;
	}

	//////////////////////////////////////////////////////// START MISCELLENEOUS LOGIC///////////////////////////////////////////////////////

	/**
	 * @param int $voucher_series_id
	 */
	public function offlineCouponProcessing( $voucher_series_id ) {

		$results = $this->campaign_model_extension->getCouponRedemptionDetailsForOffline( $voucher_series_id );

		// reset the sales amount, in case this is a repeat processing.
		$previous_id = -1;
		$sales_nextbill = 0;
		$sales_sameday = 0;

		foreach ($results as $row) {
			$id = $row['id'];
			if ($previous_id == -1) $previous_id = $id;
			if ($id != $previous_id) {
				$res = $this->campaign_model_extension->updateCouponRedemptionSales($sales_nextbill, $sales_sameday, $previous_id);
				$sales_nextbill = 0;
				$sales_sameday = 0;
			}
			$sales_sameday += $row['bill_amount'];
			if ($sales_nextbill == 0 && $row['bill_date'] > $row['used_date']) $sales_nextbill = $row['bill_amount'];
		}

		#do the last row
		if ($id) {
			$res = $this->campaign_model_extension->updateCouponRedemptionSales($sales_nextbill, $sales_sameday, $id);
		}

		$table = $this->campaign_model_extension->getCouponRedemptionDetailsForOffline( $voucher_series_id , 'query' );

		return $table;
	}

	/**
	 * //sms_template === message
	 * //email_subject === message
	 * //email_body === subject
	 *
	 * @param $litener_id INT
	 * @param $params CONTRACT(
	 *
	 * 		'event_name' => STRING,
	 * 		'litener_name' => STRING,
	 * 		'zone' => INT,
	 * 		'stores' => CSV,
	 * 		'message' => VARCHAR,
	 * 		'subject' => VARCHAR,
	 * 		'type' => ENUM( SMS, EMAIL, VOUCHER_ISSUAL ),
	 * 		'start_date' => DATE,
	 * 		'end_date' => DATE,
	 * 		'voucher_series_id' => INT,
	 * 		'template_file_id' => INT,
	 * 		'tracker_id' => INT
	 * )
	 */
	public function createListenerForReferrals( $params, $listener_id = false, $condition_id = false, $C_tracker_mgr = false ){

		$listener_params = array();
		$lm = new ListenersMgr( $this->currentorg );

		//init listener
		if( !$listener_id )
		$regn_id = $lm->registerListener( $params['event_name'], $params['listener_name'],
		$this->user_id, $params['voucher_series_id']
		);
		else
		$regn_id = $listener_id;

		if( !$regn_id ) throw new Exception( _campaign("Listeners could not be created "));

		//construct params for the listeners
		if( $params['tracker_id'] )
		$listener_params['tracker_id'] = $params['tracker_id'];
			
		$listener_params['_regn_id'] = $regn_id;
		$listener_params['_event_name'] = $params['event_name'];
		$listener_params['_listener_name'] = $params['listener_name'];
		$listener_params['_execution_condition'] = null;
		$listener_params['execution_order'] = 0;
		$listener_params['zone'] = $params['zone'];
		$listener_params['stores'] = explode( ',' , $params['stores'] );
		$listener_params['listener_start_time'] = $params['start_date'];
		$listener_params['listener_end_time'] = $params['end_date'];

		//The fields in the SmsSending & EmailSending & IssueVoucher Listeners
		//Yeah Yeah I know its pretty bad way but dont want to write a
		//whole new logic when it is goin to deprecate in any cases
		if( $params['type'] == 'SMS' ){

			$listener_params['sms_template'] = $params['message'];
		}elseif( $params['type'] == 'EMAIL'){

			$listener_params['email_body'] = $params['message'];
			$listener_params['email_subject'] = $params['subject'];
			$listener_params['template_file_id'] = $params['template_file_id'];
		}elseif( $params['type'] == 'VOUCHER_ISSUAL' ){

			$listener_params['sms_template'] = $params['message'];
			$listener_params['voucher_series_id'] = $params['voucher_series_id'];
				
		}else{

			throw new Exception( _camp1('Listener type not supported') );
		}

		//create listener
		$status = $lm->processCustomizeForm( false, $listener_params );
		if( !$status ) throw new Exception( _campaign("Listener could not be updated") );

		if( $C_tracker_mgr && $condition_id )
		$C_tracker_mgr->setListenerForCondition( $condition_id, $regn_id );

		return 'SUCCESS';
	}

	public function getLanguageLink($subject){
		$org_controller = new OrganizationController() ;
		$lang_id_hash = $org_controller->getOrgLanguages($this->org_id) ;
		$protocol = "http://" ;
		if(array_key_exists('HTTP_REFERER',$_SERVER) && !empty($_SERVER['HTTP_REFERER'])){
	        $protArr = explode('//',$_SERVER['HTTP_REFERER']) ;
	        $protocol = $protArr[0]."//" ;
		}
		$url = $protocol.$_SERVER['SERVER_NAME'].'/creative_assets/template_resolver/message_template_resolver.php' ;

		$pattern = "/{{Link_to_(.*)}}/U" ;
		//this pattern will also work : "/{{language_template__(.*?)}}/"
		$matches = array() ;
		preg_match_all($pattern, $subject, $matches) ;
		$this->logger->debug("the matched patterns are : ".print_r($matches,true)) ;
		$matches_complete = $matches[0] ;
		$matches_substring = $matches[1] ;
		$module = "OUTBOUND" ;
		foreach($matches_complete as $key=>$value){
	        if(isset($matches_substring[$key])){
	        	$lang_id = array_search($matches_substring[$key], $lang_id_hash) ;
	        	if(!$lang_id){
	        		continue ;
	        	}
	        	$lang_id_b64 = base64_encode($lang_id) ;
                $replaced_str = $url.'?utrack={{user_id_b64}}&mtrack={{outbox_id_b64}}__'.$module.'&langtrack='.$lang_id_b64.'__'.$this->org_id ;
                $subject = str_ireplace($value,$replaced_str,$subject) ;
	        }
		}
		return $subject ;
	}

	/**
	 * Replace php tags which is replaced and sent to the 
	 * msging in the desired format 
	 */
	public function replacePhpTags( $subject ){
		
		global $campaign_cfg;
				
		$view_url = "{{domain}}/business_controller/campaigns/emails/links/view.php?utrack={{user_id_b64}}&mtrack={{outbox_id_b64}}";
				
		$view_url = Util::templateReplace( $view_url , array('domain'=>$campaign_cfg['track-url-prefix']['view-in-browser'] ) );
		
		$link = '<a href="'.$view_url.'" style = "text-decoration: underline;color: #369;" target="_blank">View it in your browser</a>';
		
		$tags = array(
						'adv' => '<ADV>',
				        'view_in_browser' => $link 
		);
		$subject = $this->getLanguageLink($subject) ;
		return Util::templateReplace( $subject, $tags );
	}	

	/**
	 * To view the queued msg or email we need
	 * to process some selected fields.
	 *
	 * Otherwise original is retained.
	 *
	 * @param unknown_type $params
	 * CONTRACT(
	 *
	 * 	'send_when' : Type of selections ENUM [ IMMEDIATE | PARTICULAR_DATE | SCHEDULE ]
	 * 	'hours' : hour selection
	 * 	'minutes' : minute selection
	 * 	'cron_day' : cron days
	 * 	'cron_week' : cron weeks
	 * 	'cron_month' : cron months
	 * )
	 */
	public function getProcessedParams( $params ){

		if( $params['send_when'] == 'IMMEDIATE' ) {

			$params['date_field'] = date( 'Y-m-d ' );
			$params['hours'] = date( 'H' );
			$params['minutes'] = date( 'i' );
		}

		$params['cron_day'] = implode( ',', $params['cron_day'] );
		$params['cron_week'] = implode( ',', $params['cron_week'] );
		$params['cron_month'] = implode( ',', $params['cron_month'] );

		return $params;
	}

	//////////////////////////////////////////////////////////// Charting Related Logic ///////////////////////////////////////////////////////////

	/**
	 * @param $tracker_params CONTRACT(
	 *
	 * 	'start_date' => VALUE [ start date ],
	 * 	'end_date' => VALUE [ goes in tracker formulation for start ]
	 * 	'stores' => VALUE [ The stores for which the trackers will be executed ]
	 * 	'zone' => VALUE [ The zone for which the trackers will be executed ]
	 * 	'period_days' => VALUE [ Number of days to track for ]
	 * 	'threshold' => VALUE [ Minimum number of redemption required to trigger tracker ]
	 * 	'message' => [ The message to shoot out for SMS/EMAIL ]
	 * 	'subject' => [ The subject to shoot out for EMAIL ]
	 * 	'template_file_id' => [ File Id To Be Used For Email ]
	 * 	'type' => [ SMS | EMAIL ]
	 * )
	 *
	 * @param $tracker_id
	 */
	public function addReferralTracker( $tracker_params, $tracker_id = false ){

		$issue_voucher_listener_for_tracker = false;
		$this->logger->info( 'The tracker params : '.
		print_r( $tracker_params, true ).' tracker id passed : '.$tracker_id
		);

		//get the campaign details
		$campaign_details = $this->getDetails();

		//get vouchet series id
		$voucher_series = json_decode( $campaign_details['voucher_series_id'], true );
		$referer_series_id = ( int ) $voucher_series['referer'];
		$referee_series_id = ( int ) $voucher_series['referee'];

		if( !$referer_series_id )
		throw new Exception( _camp1('No Referer Series Is Attached'));
			
		//Step 1 : Create the tracker manager with referral event
		$C_tracker_mgr = new TrackersMgr( $this->currentorg );

		//Event : CampaignReferralRedemptionsTracker
		$params['entity'] = 'num_redemptions';
		$params['max_success_signal'] = '1000';
		$params['expires_on'] = $tracker_params['end_date'];
		$params['tracker_name'] = 'CampaignReferralRedemptionsTracker';
		$params['custom_name'] = $campaign_details['name'].' -- Referral Event';

		//The tracker on which tracker will be triggered
		$cust_params['exec_for_voucher_series_id'] = $referer_series_id;

		$this->logger->info( 'Adding/Updating Tracker With Params: '.print_r( $params, true ) );

		//create the tracker.
		if( $tracker_id ){

			$C_tracker_mgr->loadById( $tracker_id );

			$status =
			$C_tracker_mgr->updateTracker($params['max_success_signal'],
			$cust_params , $params['custom_name'], $params['expires_on']
			);
		}else{

			$tracker_id =
			$C_tracker_mgr->addTracker( $params['entity'], $params['tracker_name'],
			$params['max_success_signal'], $cust_params, $params['custom_name'],
			$params['expires_on'], $params['send_milestone'],
			$params['milestone_not_found_template']
			);

			$C_tracker_mgr->loadById( $tracker_id );
		}

		if( !$tracker_id ) throw new Exception( _campaign("The Tracker data could not be uploaded!!!") );
		$this->logger->info( 'Tracker Updated Successfully ' );

		//tracker_id is event_reference_id for the
		// TrackerExecutingListener For With Event CampaignRefereeRedeemEvent
		$C_listener_mgr = new ListenersMgr( $this->currentorg );

		$listeners = $C_listener_mgr->getRegisteredListeners( $tracker_id, CampaignRefereeRedeemEvent, TrackerExecutingListener );
		$this->logger->info( 'Listener attached with the tracker : '.print_r( $listeners, true ) );

		//if already a listener is attached to the tracker
		//the listener id is loaded so that listener can be edited
		if( count( $listeners ) > 0 ){

			$listener_id = $listeners[0]['id'];
			$this->logger->info( 'Listener Id : '.$listener_id );
		}

		$listener_params['event_name'] = CampaignRefereeRedeemEvent;
		$listener_params['listener_name'] = TrackerExecutingListener;
		$listener_params['stores'] = $tracker_params['stores'];
		$listener_params['zone'] = $tracker_params['zone'];
		$listener_params['tracker_id'] = $tracker_id;
		$listener_params['start_date'] = $tracker_params['start_date'];
		$listener_params['end_date'] = $tracker_params['end_date'];
		$listener_params['voucher_series_id'] = $tracker_id;
		$listener_params['type'] = $tracker_params['type'];

		//add or edit the TrackerExecutingListener for the event
		//CampaignRefereeRedeemEvent
		$this->logger->info( 'Creating Listener With Params : '.print_r( $listener_params, true ) );
		$this->createListenerForReferrals( $listener_params, $listener_id );

		//agg_func : SUM
		//operator : '>='
		//threshold : threshold
		//period_days : period_days
		$condition_params['rank'] = 0;
		$condition_params['agg_func'] = 'SUM';
		$condition_params['operator'] = '>=';
		$condition_params['min_threshold_check'] = 0;
		$condition_params['success_signal_limit'] = 1;
		$condition_params['threshold'] = $tracker_params['threshold'];
		$condition_params['period_days'] = $tracker_params['period_days'];

		//check if a condition has already been applied
		//at max two condition might be there check the EmailSendingListener/SmsSendingListener

		if( $tracker_params['type'] == 'SMS' && strpos( $tracker_params['message'], '{{voucher_code}}' ) ){

			$listener_name = 'IssueVoucherListener';

			if( !$referee_series_id ){

				throw new Exception( _campaign("No Referee Series Attached To Issue Voucher For Referers") );
			}

			$issue_voucher_listener_for_tracker = true;
			$listener_params['type'] = 'VOUCHER_ISSUAL';
			$listener_params['voucher_series_id'] = $referee_series_id;

		}elseif( $tracker_params['type'] == 'SMS' ){

			$listener_name = 'SmsSendingListener';

		}elseif( $tracker_params['type'] == 'EMAIL' ){

			$listener_name = 'EmailSendingListener';
		}

		$tracker_conditions = $C_tracker_mgr->getTrackerConditionByTrackerId();
		$condition_id = false;
		foreach( $tracker_conditions as $tc ){

			$listener_data =
			$C_listener_mgr->getRegisteredListeners( $tc['id'], 'TrackerSuccessEvent', $listener_name );

			if( count( $listener_data ) > 0 ){

				$condition_id = $tc['id'];
				break;
			}
		}

		$condition_id_insert =
		$C_tracker_mgr->processTrackerConditionForm( false, $condition_id, $condition_params );

		if( !$condition_id )
		$condition_id = $condition_id_insert;

		//get the condition attached to tracker
		$tracker_condition_details = $C_tracker_mgr->getConditions( $condition_id );

		//It gives back the listener id if something is attached to tracker conditions
		$listener_id = ( int ) $tracker_condition_details['listener_id'];
		$this->logger->info( 'Listener attached with the tracker condition id : '.$condition_id.' listener id : '.$listener_id );

		//If the voucher code is configured the issue voucher listener
		//will be added
		$listener_params['event_name'] = TrackerSuccessEvent;
		$listener_params['listener_name'] = $listener_name;
		$listener_params['stores'] = $tracker_params['stores'];
		$listener_params['zone'] = $tracker_params['zone'];
		$listener_params['start_date'] = $tracker_params['start_date'];
		$listener_params['end_date'] = $tracker_params['end_date'];
		$listener_params['voucher_series_id'] = $condition_id;
		$listener_params['type'] = ( $issue_voucher_listener_for_tracker )?( 'VOUCHER_ISSUAL' ):( $tracker_params['type'] );

		//other parameters
		$listener_params['message'] = $tracker_params['message'];
		$listener_params['subject'] = $tracker_params['subject'];
		$listener_params['template_file_id'] = $tracker_params['template_file_id'];

		//add the voucher series id in here.

		//add or edit the TrackerExecutingListener for the event
		//CampaignRefereeRedeemEvent
		$this->logger->info( 'Creating Final Listener With Params : '.print_r( $listener_params, true ) );
		$this->createListenerForReferrals( $listener_params, $listener_id, $condition_id, $C_tracker_mgr );

		//update the tracker condition with the listener id
		$this->logger->info( '...DONE...' );
	}

	/**
	 *
	 * @param $group_id
	 * @param $devide_grp_array
	 * @param $total_customer
	 */
	public function testControlRandomGroupCreation( $campaign_id , $group_id , 
			$devide_grp_array , $total, $mapping_required = true, $user_type = 'customer' ){

		$start_id = -1;
		$batch_size = 5000;
		$label = $this->campaign_model->getGroupLabel( $group_id );
		$C_parent_group_handler = new CampaignGroupBucketHandler($group_id);
		$count_array = array();

		$group_handler = array( );
		$db = new Dbase('msging');
		while( $total > 0 ){

			$details = 
				$C_parent_group_handler->getCustomerListByLimit($start_id, $batch_size);
			$max = count( $details );
			if( $max < 1 ) break;

			$start_id = $details[$max-1]['id'];
			shuffle( $details );

			$col = 0;
			$grp_row = 1;
			$success = true;
			foreach( $devide_grp_array as $grp_dump ){

				$batch_group_id = $grp_dump['group_id'];
				if( !isset( $group_handler[$batch_group_id] ) ){
					
					$group_handler[$batch_group_id] = 
						new CampaignGroupBucketHandler($batch_group_id);
				}
								
				$batch_data = array();
				$rowcount = 1;
				$devide_cust = $grp_dump['percentage'];

				$count = round( ($max * $devide_cust) / 100 );

				for( $row = 0 ; $row < $count ; $row++,$col++,$rowcount++ ){
				
					if( $details[$col]['user_id'] ){
				
						$insert_subscribers =
						'(
								"'.$grp_dump['group_id'].'",
								"'.$details[$col]['user_id'].'",
								"'.$db->realEscapeString( $details[$col]['customer_name'] ).'",
								"'.$details[$col]['user_type'].'",
								"'.$db->realEscapeString( $details[$col]['custom_tags'] ).'",
								"'.$details[$col]['is_mobile_exists'].'",
								"'.$details[$col]['is_email_exists'].'"
							 )';
				
						array_push($batch_data, $insert_subscribers);
					}
				
					if( count( $batch_data ) > 0 && $rowcount == $count || $rowcount % 5000 == 0 ){
				
						$insert_users = implode(',', $batch_data);
						$batch_data = array();
				
						$success =
							$this->campaign_model_extension->
							addSubscriberInGroupInBatches( $insert_users, false,
									false, $group_handler[$batch_group_id], $user_type );
						
						$count_array[$grp_row] += $rowcount;
					}
				}
				
				if( !$success )
					throw new Exception(_campaign("Group Id :")." ".
							$grp_dump['group_id'] ." "._campaign("is not successfully dumped. Please try again."));

				$grp_row++;
			}
			$total -= $max;
		}

		foreach( $devide_grp_array as $grp_dump ){

			$this->campaign_model_extension->updateGroupMetaInfo( $grp_dump['group_id'], $group_id,
			 $grp_dump['percentage'] );
			$this->updateReachability($campaign_id,$grp_dump['group_id']);
		}
		
		if( !$mapping_required ) return;
		
		$grp_row = 1;
		foreach( $devide_grp_array as $grp_dump ){

			$provider_type = 'test_control';

			$audience_group_id = 
				$this->campaign_model_extension->insertAudienceGroups( $campaign_id , 
						$provider_type , $grp_dump['group_id'] );

			//insert in selection filter for customer admin details
			$group_desc = addslashes( _campaign("Split From ")."{{css_start}}$label{{css_end}} 
							with $grp_dump[percentage]%"." "._campaign("probability."));
			
			$this->setSelectionFilter( $audience_group_id , 
					'test_control' , ' ' , $group_desc, $count_array[$grp_row] );
			$grp_row++;
		}
	}

	
	public function getRunningCampaigns()
	{
		$sql = "SELECT name, id FROM campaigns_base WHERE org_id = $this->org_id AND start_date < NOW() AND end_date > NOW() AND active = 1";
		$db = new Dbase('campaigns');
		$l = $db->query($sql);
		$list = array();
		foreach($l as $row)
		{
			$list[$row['name']]  = $row['id'];
		}
		
		return $list;
	}
	
	/**
	 * To Get Archived Group List for particular campaign.
	 * @param unknown_type $campaign_id
	 */
	public function getArchiveGroupDetailsForCampaignId( $campaign_id = false ){
		
		return $this->campaign_model->getArchiveGroupDetailsForCampaignId( $campaign_id );
	}
	
	
	/**
	* Calls uploadSubscriber for a file that is uploaded via ftp 
	*/
	public function ftpUpload( $params )
	{
		$this->logger->debug( ' Inside ftpUpload ' . print_r( $params , true ) );
		$org_id = $params[ 'org_id' ];
		$campaign_id = $params[ 'campaign_id' ];
		$user_id = $params[ 'last_updated_by' ];
		$download_id = $params[ 'id' ];
		$status = $params[ 'status' ];
		$group_name = $params[ 'group_name' ];
		$file_name = $params[ 'file' ];
		$import_type = $params['import_type'];
		$this->logger->debug( ' Ftp Audience upload status : ' . $status );
		
		if( $status == 'COPIED' )
		{
		
			$append_org_id = $org_id;
			
			if( $org_id == 0)
			{
				$append_org_id = 'zero'	;				
			}
			$path = Util::templateReplace($this->ftp_copy_path, array( 'org-id' => $append_org_id ,
																	   'campaign-id' => $campaign_id , 
																	   'group-name' => $group_name ) ) ;
			$path = $path . "/"	. $file_name ;
				
			$this->logger->debug( " File Path from inside ftp upload : " . $path );
			if($path)
			{
				try{
					$custom_tags = $params[ 'custom_tags' ];

					$custom_tags = json_decode( $custom_tags , true );
					//$number_of_tags = count( $custom_tags );
					$number_of_tags = $params[ 'custom_tag_count' ];
					//$params[ 'custom_tag_count' ] = $number_of_tags ;



					if( $number_of_tags != 0 )
					{
						for( $i = 1 ; $i <= $number_of_tags ; $i++ )
						{
							$key = 'custom_tag_'.$i;
							$params[ $key ] = ( $custom_tags[ $key ] );
						}
					}
					//$status = $this->uploadSubscribers( $params , $path , $campaign_id , 'campaign_users' );
					$file_id = $this->prepare($params, $path, $campaign_id,'campaign_users',$import_type);
					$this->upload($file_id);
					$this->logger->debug("Ftp Upload subscriber status : " . $status );

					return $status;
						

				}
				catch(Exception $e){
					$this->logger->debug( "Ftp Upload Exception : " . $e->getMessage() ) ;
					throw $e ;
				}
			}
			
		}	 				
	}
	
	public function ftpDbInsert( $params , $campaign_id , $org_id , $user_id )
	{ 
		$this->logger->debug( ' Ftp Insert ' );
		$group_name = $params[ 'group_name' ] ;
		$group_name = Util::uglify( $group_name );
		$params[ 'group_name' ] = $group_name ;
		$this->logger->debug( ' FTP values : ' . print_r( $params , true ) );
		$number_of_tags = $params[ 'custom_tag_count' ];
    		
    	$custom_tag = array();
    	$custom_tags = 0;
    	if( $number_of_tags != 0 )
    	{
    		for( $i = 1 ; $i <= $number_of_tags ; $i++ )
    		{
				$key = 'custom_tag_'.$i;
				$custom_tag[ $key ] = ( $params[ $key ] );
			}
			
			$custom_tags = json_encode( $custom_tag );
    	}
    	
    	$params[ 'custom_tags' ] = $custom_tags;
		$status = $this->campaign_model->insertFtpDb( $params , $campaign_id , $org_id , $user_id );
		return $status;
	}
	
	
	
	public function ftpConnect( $org_id , $passive = false )
	{
		$settings = $this->campaign_model_extension->getFtpSettings( $org_id );
		$this->logger->debug( " Ftp Settings : " . print_r( $settings , true ) );
		$server_name = $settings[0][ 'server_name' ];
		$port = $settings[0][ 'port' ];
		$user_name = $settings[0][ 'user_name' ];
		$password = $settings[0][ 'password' ];
		$password = base64_decode( $password );
		$this->logger->debug( " Ftp Settings again : " . $server_name . " : " . $port . " : " . $user_name . " : " .  $password );
		try
		{
			$this->FtpManager = new FTPManager( $server_name , $port , $user_name , $password , $passive );
		}catch( Exception $e )
		{
			$this->logger->debug( " Ftp connect could not connect " . $e->getMessage() );
			throw $e;
		}
		
	}
	
	/**
	 * Copies file from ftp server to local server
	 * @param $params
	 */
	public function getFtpFile( $params )
	{
		$org_id = $params[ 'org_id' ];
		$campaign_id = $params[ 'campaign_id' ]; 
		$user_id = $params[ 'last_updated_by' ];
		$folder = $params[ 'folder' ];
		$file_name = $params[ 'file' ];
		$group_name = $params[ 'group_name' ];
		$custom_tags = $params[ 'custom_tags' ];
		
		$this->ftpConnect( $org_id , true ); //Change passive mode here
				
		$this->logger->debug( " Ftp Params Values from Get Ftp File : " . print_r( $params , true ) );
		$this->logger->debug( " Custom Tags : " . print_r( $custom_tags , true ) );
		
		/* Change HERE if you want to change path to get the file */
				
		/*$file_path = "org_" . $org_id 
					. "/campaign_" . $campaign_id . "/" . $folder ; */
		$file_path = $folder ;
		
		$this->logger->debug( " Current Directory to change to : " . $file_path );
		
		/* change current directory before copying */
		$cur_dir = $this->FtpManager->setCurrentDir( $file_path ) ;
		
		$this->logger->debug( " Changed Current Directory to :  " . $cur_dir );
		
		
		/* path to put file from ftp */
		$append_org_id = $org_id ;
		
		if( $org_id == 0 )
		{
			$append_org_id = "zero";
		}

		$copy_path = Util::templateReplace($this->ftp_copy_path, array( 'org-id' => $append_org_id ,
																	   'campaign-id' => $campaign_id , 
																	   'group-name' => $group_name ) ) ;
		
		 /* Set transfer mode to ASCII */
		
		$this->FtpManager->setMode();
		
		$this->logger->debug( " File Path : " . $copy_path . " : " . $file_name );
		
		$status = $this->FtpManager->get( $file_name , $copy_path );
		
		return $status;
	}
	
	/**
	 * update bulk sms credit for organization
	 */
	public function updateBulkSMSCredit( $credit ){

		try{
	
			$this->logger->debug('@@@INSIDE BULK UPDATE' );
			//Loading organization sms bulk credit. 
			$this->org_sms_credit_model->load( $this->org_id );
			
			$bulk_credit = $this->org_sms_credit_model->getBulkSmsCredits();
			$this->logger->debug('@@@BHAVESH');
			
			if( !$bulk_credit ){
				
				$this->logger->debug('@@@GOOGLE'.$this->org_id.$this->user_id);
				$this->org_sms_credit_model->setOrgId( $this->org_id );
				$this->org_sms_credit_model->setValueSmsCredits( 0 );
				$this->org_sms_credit_model->setBulkSmsCredits( $credit );
				$this->org_sms_credit_model->setUserCredits( 0 );
				$this->org_sms_credit_model->setCreatedBy( $this->user_id );
				$this->org_sms_credit_model->setLastUpdatedBy( $this->user_id );
				$this->org_sms_credit_model->setLastUpdated( date('Y-m-d H:i:s') );
				return  $this->org_sms_credit_model->insertWithId();
				
			}else{
	
				$this->logger->debug('@@@DATA LOADED' );

        	                //Setting new Bulk credit.
        	                $new_credit = $bulk_credit + $credit;
                	        $this->org_sms_credit_model->setBulkSmsCredits( $new_credit );
        	        
                	        //Updating new Credit.
	                        return $this->org_sms_credit_model->update( $this->org_id );  
			}					

		}catch( Exception $e ){
			return $e->getMessage();
		}
	}
	
	/**
	 * getting bulk sms credit for the campaign home display.
	 */
	public function getBulkSmsCredit(){
		
		try {
			$this->org_sms_credit_model->load( $this->org_id );
			return $this->org_sms_credit_model->getBulkSmsCredits();		
		}catch( Exception $e ){
			$this->logger->debug( " Exception in getting bulk sms credit " . $e->getMessage() );
			return 1000000;
		}
				
	}
	
	/**
	 * Getting campaign data.
	 * @param unknown_type $limit
	 * @param unknown_type $where_filter
	 */
	public function getCampaignData( $limit, $where_filter ){
		
		return $this->campaign_model_extension->getCurrentCampaign( $limit, $where_filter );
	}
	
	/**
	 * Getting data with where condition for campaign data table.
	 * @param unknown_type $where
	 */
	public function getCampaignDataWithWhere( $where ){
	
		return $this->campaign_model_extension->getCurrentCampaignWithWhere( $where );
	}
	
	
	/**
	 * Returns the message details given group id
	 */
	public function getMsgDetailsByGroupId( $group_id ){

		return $this->campaign_model->getMsgDetailsByGroupId( $group_id );
	}
	
	/**
	 * Propogates the exception
	 * @param unknown_type $group_label
	 */
	public function isGroupNameExists( $group_label, $campaign_id ){
		
		$this->campaign_model_extension->isGroupNameExists( $group_label, $campaign_id );
	}
	
	public function getFtpFileStatus( $campaign_id )
	{
		$status_values = $this->campaign_model_extension->getFtpFileStatus( $campaign_id );
		
		$this->logger->debug( " Ftp File Status : " . print_r( $status_values , true ) );
		
		return $status_values;
	}
	
	public function selectFromFtp( $status )
	{
		$params = $this->campaign_model_extension->selectFromFtp( $status);
		
		return $params;
	}
	/*
	 *  status[OPEN , COPYING , COPIED , PROCESSING , EXECUTED , ERROR ]
	 */
	public function setFtpStatus( $status , $id )
	{
		$status = $this->campaign_model_extension->setFtpStatus( $status , $id );
		
		return $status ;
	}
	
	public function ftpFileExists( $file_name , $folder_name , $org_id )
	{
		try
		{ 
			$this->ftpConnect( $org_id , true ); // <---- THIS IS THE ONLY CHANGE
		}
		catch( Exception $e )
		{
			$this->logger->debug( " Could not connect in fto file exists" . $e->getMessage() );
			throw $e;
		}
				
		$status = $this->FtpManager->ftpFileExists( $file_name , $folder_name );
		
		$this->logger->debug( " Ftp Exist status : " . $status );
		return $status ;
		
	}
	
	public function getGroupsByCampaignId( $campaign_id )
	{
		return $this->campaign_model_extension->getGroupsByCampaignId( $campaign_id );
	}
	
	public function getOverallEmailGroup( $campaign_id , $limit )
	{
		return $this->CampaignGroup->getOverallEmailGroup( $campaign_id , $limit );
	}
	
	public function getUserEmailCount( $campaign_id , $group_id , $limit, $sql = false)
	{
		return $this->CampaignGroup->getUserEmailCount( 
									$campaign_id , $group_id , $limit, $sql );
	}
	
	public function getUserDateEmailCount( $campaign_id, $start_date, $end_date, $limit )
	{
		return $this->CampaignGroup->getUserDateEmailCount(
							$campaign_id, $start_date, $end_date, $limit );
	}
	
	public function getOverallEmailLinkGroup( $campaign_id , $org_id , $limit )
	{
		return $this->CampaignGroup->getOverallEmailLinkGroup( $campaign_id , $org_id , $limit );
	}
	
	public function getUserEmailLinkCount( $campaign_id, $org_id, $limit, $sql = false )
	{
		return $this->CampaignGroup->getUserEmailLinkCount(
										$campaign_id, $org_id, $limit, $sql );
	}
	
	public function getEmailLinkUserStats( $campaign_id, $org_id, $limit, $sql = false )
	{
		return $this->CampaignGroup->getEmailLinkUserStats( 
										$campaign_id, $org_id, $limit, $sql );
	}
	
	public function getEmailStatsForBarChart( $campaign_id , $org_id )
	{
		$limit = " LIMIT 10 ";
		return $this->CampaignGroup->getEmailStatsForBarChart( $campaign_id , $org_id , $limit );
		
	}
	
	public function getLinksForBarChart( $campaign_id , $org_id )
	{
		$limit = " LIMIT 10 ";
		return $this->CampaignGroup->getLinksForBarChart( $campaign_id , $org_id , $limit );
	}
	
	public function getOverallEmailForPieChart( $campaign_id , $org_id )
	{
		return $this->CampaignGroup->getOverallEmailForPieChart( $campaign_id , $org_id );
	}
	
	public function getSkippedUsersBarChart( $campaign_id , $org_id )
	{
		$limit = " LIMIT 10 ";
		return $this->CampaignGroup->getSkippedUsersBarChart( $campaign_id , $org_id , $limit );
	}
	
	public function getVoucherSeriesDetails($vs_id){
		$this->logger->debug("in get voucher series details with voucher series : ".$vs_id) ;
		if(empty($vs_id) || $vs_id <= 0){
			return array() ;
		}

		$getCouponConfigRequest = new luci_GetCouponConfigRequest() ;
		$getCouponConfigRequest->requestId = Util::getServerUniqueRequestId() ;
		$getCouponConfigRequest->orgId = $this->org_id ;
		$getCouponConfigRequest->couponSeriesId = $vs_id ;

		$luciClient = new LuciSdk\LuciClient() ;
		$result = array() ;

		try{
			$arrCouponSeries = $luciClient->getCouponConfiguration($getCouponConfigRequest) ;	

			if(count($arrCouponSeries) == 1){
				$couponSeriesDetails = $arrCouponSeries[0] ;
				foreach ($couponSeriesDetails as $key => $value) {
					$result[$key] = $value ;		
				}
				$this->logger->debug("coupon series configuration from luci : ".print_r($arrCouponSeries,true)) ;
				return $result ;	
			}else{
				return array() ;
			}			
		}catch(luci_LuciThriftException $ex){
			$this->logger->error("error occured trying to get configuration with error code : ".$ex->errorCode." message : ".$ex->errorMsg) ;	
			throw new Exception($ex->errorMsg, $ex->errorCode);
		}catch(Exception $ex){
			$this->logger->error("error occured trying to get configuration with error code : ".$ex->getCode()." message : ".$ex->getMessage()) ;	
			throw new Exception($ex->getMessage(), $ex->getCode());
		}
	}

	/**
	 * Getting Voucher Series Details. for outbound and action.
	 * if it is outbound campaign it will return description and num_of_issued
	 * and num_of_redeemed if campaign is action based it will return only num_of_issued
	 * and num_of_redeemed.
	 * @param unknown_type $voucher_id
	 */
	public function getVoucherSeriesDetailsByVoucherId( $voucher_id , $status = false){
		
		$description = '';
		
		if( is_array( $voucher_id ) )
			$voucher_id = implode(',', $voucher_id);
		else
			$description = ', description ';
		
		if(strcmp($status, "from_voucher_table") == 0){
			return $this->campaign_model->getVoucherSeriesDetails
											(
												$voucher_id ,
												$description
												) ;
		}else{
			return $this->campaign_model->getVoucherSeriesDetailsByVoucherId
											( 
											    $voucher_id , 
												$description 
											);	
		}
		
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $group_id
	 */
	public function changeFavouriteTypeForGroup( $group_id ){
		
		$this->campaign_model_extension->changeFavouriteTypeForGroup( $group_id );
	}
	
	/**
	 * 
	 * @param unknown_type $campaign_id
	 */
	public function getOrgIdByCampaignId( $campaign_id ){
		
		$this->campaign_model_extension->load( $campaign_id );
		return $this->campaign_model_extension->getOrgId();
	}
	
	public function getVoucherSeriesDetailsByCampaignId( $campaign_id ){
		
		$this->campaign_model_extension->load( $campaign_id );
		$voucher_id = $this->campaign_model_extension->getVoucherSeriesId();
		$vch_id = json_decode( $voucher_id );

		return  $this->campaign_model_extension->getVoucherSeriesDetailsByVoucherId( implode(',', $vch_id ) );
	}
	
	public function getVoucherSeriesIdsByCampaignId($campaign_id)
	{
		$this->campaign_model_extension->load( $campaign_id );
		$voucher_id = $this->campaign_model_extension->getVoucherSeriesId();
		$vch_id = json_decode( $voucher_id );
		
		return  implode(',', $vch_id );
	}
	
	/**
	 * 
	 * Upload Audience Paste Audience List Upload Function.
	 * @param unknown_type $params
	 */
	
	public function pasteAudienceList($params, $campaign_id, $type='campaign_users',$return_group_id = false)
	{
		try{
			$this->campaign_model_extension->isGroupNameExists( $params['group_name'], $campaign_id );
		}catch( Exception $e ){
			throw new Exception( $e->getMessage(), 111 );	
		}
		$data = explode("\n",$params['csv_content']);
		$header = explode(',',$data[0]);

		$final_data = array();
		for( $i = 0; $i < count($data); $i++ ){
			array_push($final_data, explode(',' , $data[$i] ));
		}
		
		if( count( $final_data ) < 1 )
			throw new Exception( _campaign("Data not inserted!"));
		$paste_type = $params['user_type'];
		$this->logger->debug("orion: paste_type = $paste_type");
		$this->logger->debug("orion: ".json_encode($params));
		if( count( $data ) > 50 )
			throw new Exception( _campaign("Maximum 50 customer allowed to upload.") );
		
		$col_mapping['email_mobile'] = 0 ; 
		$col_mapping['name'] = 1 ; 	
		$number_of_tags = $params['custom_tag_count'];
		
		//upload custom tags to processed data
		for( $i = 1 ; $i <= $number_of_tags ; $i++ ){
			$custom_field_col = $i;
			$key = 'custom_tag_'.$i;
			$col_mapping[$key] = $custom_field_col+1;
		}
		$prepared_data = array();
		foreach($final_data as $final_data_row){
			foreach($col_mapping as $col_name=>$index){
				$prepared_data_row[$col_name] = $final_data_row[$index];
			}
			array_push($prepared_data,$prepared_data_row);
		}

		$file_id = $this->preparePasteList($params, $prepared_data, $campaign_id, $type, $paste_type);

		$group_id = $this->uploadPasteList($file_id);
		if( $return_group_id ) 
			return $group_id; 
		else 
			return true;;

	}
	/** 
	 * Prepare paste list data and check for all valid email and email
	 *	And create a temp table using AudienceUploader
	*/
	public function preparePasteList($params,$prepared_paste_list_data, $campaign_id,$type = 'campaign_users',$import_type = 'mobile')
	{
	
		$this->logger->debug("Import type = $import_type");
		$uploader = UploaderFactory::getUploaderClass($import_type);
		$uploader->initColumnMappings($params);
		$uploader->setUploadType($type);
		$group_name = trim($params['group_name']);
		$uploader->setGroupName($group_name);
		$uploader->setCampaignId($campaign_id);
		$token = $params['token_field'];
		$uploader->setCampaignStatus($token);
		
		//purges data into campaign_files_history table
		$file_id = $uploader->purge();

		if($file_id)
		{
			$this->logger->debug("File Id = $file_id");
		}
		else
		{
			$this->logger->debug("Purge data failed. Throwing exception");
			throw new Exception(_campaign("Error creating Temp Table"));
		}
		$uploader->prepare($prepared_paste_list_data);
		$uploader->validate();
		
		$valid_count = $uploader->getValidRecordsCount();
		if($valid_count == 0)
		{
			throw new Exception(_campaign("No valid records to import"));
		}
		return $file_id;
	}

	/**
	* Upload paste list data using temp table.
	*/
	public function uploadPasteList($file_id){
		try{
			$uploader = $this->loadUsingFileDetails($file_id);
			$group_name = $uploader->getGroupName();
			$campaign_id = $uploader->getCampaignId();
			$type = $uploader->getUploadType();
			$customer_count = $uploader->getValidRecordsCount();
			$group_id = $this->campaign_model_extension->insertGroupDetails( $campaign_id , $group_name , $type ,$customer_count);
			$uploader->upload($group_id);
			$provider_type = 'uploadSubscribers';
			
			$audience_group_id =
			$this->campaign_model_extension->
			insertAudienceGroups( $campaign_id , $provider_type , $group_id );
			$status = $this->CampaignGroup->updateGroupMetaInfo( $group_id );
			$this->updateReachability($campaign_id,$group_id);
		}catch(Exception $e)
		{
			$this->logger->error("Caught exception while uploading. error = ".$e->getMessage());
			throw new Exception($e->getMessage());
			
		}
		
		return $group_id;
	}

	public function pasteAudienceListOld( $params , $campaign_id , $type = 'campaign_users' , $return_group_id = false ){
		
		global $currentorg;
		
		$data = explode("\n" , $params['csv_content'] );
		$header = explode(',', $data[0] );
		
		$final_data = array();
		for( $i = 1; $i < count($data); $i++ ){
			array_push($final_data, explode(',' , $data[$i] ));
		}
		
		if( count( $final_data ) < 1 )
			throw new Exception( _campaign("Data not inserted!"));

		if( strtolower( $header[0] ) != 'email' && strtolower( $header[0] ) != 'mobile' )
			throw new Exception( _campaign("Incorrect header names,it should be email or mobile,name!"));
	
		try{
			$this->campaign_model_extension->isGroupNameExists( $params['group_name'], $campaign_id );
		}catch( Exception $e ){
			throw new Exception( $e->getMessage(), 111 );	
		}
		
		if( count( $data ) > 51 )
			throw new Exception( _campaign("Maximum 50 customer allowed to upload.") );
								
		$col_mapping['name'] = 1 ; //stripslashes(trim($params['user_name']));
		$col_mapping['email_mobile'] = 0 ; //$params['email_mobile'];
			
		$number_of_tags = $params['custom_tag_count'];
		
		//upload custom tags to processed data
		for( $i = 1 ; $i <= $number_of_tags ; $i++ ){
			$custom_field_col = $i;
			$key = 'custom_tag_'.$i;
			$col_mapping[$key] = $custom_field_col+1;
		}
		
		$group_created_flag = 0;
		$batch_data_email = array();
		$batch_data_mobile = array();
		$group_name = $params['group_name'];
		$total_customer = count( $data );
		$C_campaign_group_handler = '';
		
		$country_array = $currentorg->getCountryDetailsDumpForMobileCheck();
		
		foreach ( $final_data as $row ){
						
			$email_mobile = trim( $row[0] );
			list($first_name, $last_name) = Util::parseName( $row[1] );
			
			//$first_name = $this->campaign_model_extension->database_conn->realEscapeString( $first_name );
			//$last_name = $this->campaign_model_extension->database_conn->realEscapeString( $last_name );
			
			$pwd = substr( $email_mobile , -4 );
			$passwordHash = md5( $pwd );
			$original_email_mobile=$email_mobile;
			$this->logger->debug('@@@'.$email_mobile);
				
			if ( Util::checkEmailAddress( $email_mobile ) ){
	
				$insert_users = "('$this->org_id','$email_mobile','$first_name','$last_name','$passwordHash')";
									
				array_push( $batch_data_email , $insert_users );
				$this->logger->debug( 'Inside Email Address' );
				$this->logger->debug('@@@@WITHIN CHECK EMAIL ADDRESS');
					
				if( !$group_created_flag ){
						
					$group_id = 
						$this->campaign_model_extension->
							insertGroupDetails( $campaign_id , $group_name , $type, $total_customer );
					
					if( !$group_id )
						throw new Exception( _campaign("Group could not be created!") );
								
					$C_campaign_group_handler = new CampaignGroupBucketHandler($group_id);
					$group_created_flag = 1;
				}
			}
				
			if( Util::checkMobileNumberNew( $email_mobile , $country_array ) ){
								
				$insert_users = "('$this->org_id','$email_mobile','$first_name','$last_name','$passwordHash')";
				
				array_push( $batch_data_mobile , $insert_users );
				$this->logger->debug('@@@@WITHIN CHECK MOBILE ');
						
				if( !$group_created_flag )
				{
					$group_id = 
						$this->campaign_model_extension->
						insertGroupDetails( $campaign_id , $group_name , $type, $total_customer );
					
					if( !$group_id )
						throw new Exception( _campaign("Group could not be created!") );
								
					$C_campaign_group_handler = new CampaignGroupBucketHandler($group_id);
					$group_created_flag = 1;
				}
			}else{
						
				unset( $row['email_mobile'] );
				$this->logger->debug('@@@@WITHIN ELSE ');
			}
			
			$auth = Auth::getInstance();
			
			if( count($batch_data_email) > 0 ){
				
				$auth->registerAutomaticallyByEmailInBatches( implode(',', $batch_data_email) );
				$this->logger->debug( " Registered 10k email people " );
			}
				
			if( count($batch_data_mobile) > 0 ){
	
				$auth->registerAutomaticallyByMobileInBatches( implode(',', $batch_data_mobile) );
				$this->logger->debug( " Registered 10k mobile people " );
			}
			$batch_data_email = array();
			$batch_data_mobile = array();
		}
				
		$rowcount = 0;
		$batch_data = array();
		$batch_emails = array();
	 	$batch_mobiles = array();
		$batch_custom_tags = array();
					
		foreach ($final_data as $row) {
				 
			$rowcount++;
			 $email_mobile = trim( $row[0] );
					
			 if ( Util::checkEmailAddress( $email_mobile ) )
			 	array_push( $batch_emails , $email_mobile );
			 else if( Util::checkMobileNumberNew( $email_mobile , $country_array ) )
			 	array_push( $batch_mobiles , $email_mobile );
			 else{
				if( $rowcount < $max )
				continue; //# skip invalid email or skip invalid mobile
			 }
			 
			 if( $number_of_tags >= 1 ){
			 	
				//Get out the custom tags
				$custom_tags = array();
				for( $i = 1 ; $i <= $number_of_tags ; $i++ ){


					$key = 'custom_tag_'.$i;
					$custom_tags[$key] = ( string ) stripcslashes( trim( $row[1+$i] ) );
				}
				
				$custom_tags_filter = json_encode( $custom_tags );
				$custom_tags_filter = 
					$this->campaign_model_extension->
					database_conn->realEscapeString( $custom_tags_filter );
				
				if( $email_mobile )
					$batch_custom_tags[$email_mobile] = $custom_tags_filter;
			}
		}
		
		$is_custom_tag = false;
		if( $number_of_tags >= 1 )
				$is_custom_tag = true;
					
		$this->logger->debug('@@Custom Tags :'.print_r( $batch_custom_tags , true ) );

			
		$batch_data = 
			$this->processBatchesForUploadAudience( $batch_emails , 
				$batch_mobiles , $batch_custom_tags , $group_id , $is_custom_tag );
		
		if( empty( $batch_data ) )
			throw new Exception(_campaign("Invalid data inserted!"));
		
		//now add this user to the campaign group users table
		$this->logger->debug( " Batch data returned from processBatches " . print_r( $batch_data , true ) );
		$batch_count = count( $batch_data );
		$insert_users = implode(',', $batch_data);
		
						
		$success = 
			$this->campaign_model_extension->
			addSubscriberInGroupInBatches( $insert_users, false, false, $C_campaign_group_handler );
							

		if( $batch_count )
			$user_count += $batch_count;
		
		$this->logger->debug( " User Count : " . $user_count );
		$batch_emails = array();
		$batch_mobiles = array();
		$batch_custom_tags = array();
		$batch_data = array();
		$row = false;
	
		if( $user_count > 0){
			
			$msg = _campaign("List created successfully with $user_count customers");
			$this->logger->debug( $msg ); 
			//store group_id as json
			$json = json_encode(array('group_id' => "$group_id"));

			$this->logger->debug( " Upload Subscribers Group Id : " . $group_id . " Json " . $json );
			$provider_type = 'uploadSubscribers';
				
			$audience_group_id = 
				$this->campaign_model_extension->
				insertAudienceGroups( $campaign_id , $provider_type , $group_id );

			$status = $this->CampaignGroup->updateGroupMetaInfo( $group_id );
			
		}else
			throw new Exception(_campaign("Please check your list again. It seems records may be invalid."));
		
		if( $return_group_id ) 
			return $group_id; 
		else 
			return $msg;
	}	
	
	public function uploadAudienceViaCSV( $file , $params , $campaign_id ){

		$this->campaign_model_extension->isGroupNameExists( $params['group_name'], $campaign_id );
		
		$file_parts = pathinfo( $file['name'] );
		
		if($file_parts['extension'] != 'csv' && $file_parts['extension'] != 'CSV'){
				throw new Exception( _campaign("Upload Only CSV File!") );
		}else{
			$filename = $file['tmp_name'];
			try{
					$status = $this->uploadSubscribers( 
														$params , 
														$filename , 
														$campaign_id, 
														'campaign_users' 
													  );
			$this->updateReachability($campaign_id,$group_id);
				return $status;
			}catch(Exception $e){
				throw new Exception($e->getMessage, 111);
				//$this->publishOn( 'iframe_refresh', array( 'refresh' => false, 'flash' => $this->getFlashMessage() ) );
			}
		}
	}
	
	public function uploadAudienceViaFtp( $params, $campaign_id ){
		
			$file_name = $params[ 'ftpfile' ];
   			$folder_name = $params[ 'ftpfolder' ];
   			$could_not_connect = false;
   			
   			/* START HERE  check ftp_nlist */
   			
   			try{
   				$file_exists = $this->ftpFileExists( $file_name , $folder_name , $this->org_id );
   			}catch( Exception $e ){
   				$this->logger->debug( " upload audience widget could not connect " . $e->getMessage() );
   				$file_exists = false;
   				$could_not_connect = true ;
   			}
	  		
   			if( $file_exists ){
   				
   				$group_name = $params[ 'group_name' ];
   				$status = false;
   				$this->logger->debug( " Group Name in Ftp : " . $group_name );
   				try{
   					$this->campaign_model_extension->isGroupNameExists( $group_name, $campaign_id ); //spaces are not replaced by underscores in group details
   					
   					$group_name = Util::uglify( $group_name ); //spaces are underscores in ftp audiences upload
   					
   					$this->campaign_model_extension->isFtpGroupNameExists( $group_name );
   					
   					$this->logger->debug( " Succesful check " );
   					$status = true ;
   				}
   				catch( Exception $e ){
   					$this->logger->debug( " Unsuccessful " . $e->getMessage() );
   					throw new Exception( $e->getMessage() , 111 );
   					$status = false ;
   				}
   				if( $status )
   					$status = $this->ftpDbInsert
   									 ( 
   										$params , 
   										$campaign_id , 
   										$this->org_id , 
   										$this->user_id
   									);
  				return $status;
   			}else{
   				if( !$could_not_connect )
   					throw new Exception( _campaign("Sorry your file does not exist in your folder .") , 111 );
   				else
   					throw new Exception( _campaign("Could not connect to ftp server") , 111 );
   			}
	}

	/**
	 * 
	 * Retrieving list of supported social platforms.
	 */
	public function getSupportedSocial(){
		
		return $this->campaign_model_extension->getSupportedSocialPlatform();
	}

	/**
	 *getting supported social platform info for org. 
	 */
	public function getSupportedSocialPlatform(){

		$this->org_model->load( $this->org_id );
		
		$social_data = $this->org_model->getHash();
		
		$this->logger->debug('@@@Organization detail hash'.print_r( $social_data , true ));
		
		$social = json_decode( $social_data['social_platforms'] ,true );
		
		$this->logger->debug('@@@Social platforms array'.print_r( $social , true ));
		
		return $social;
	}

	public function doesCampaignNeedsAuthorization(){
		$val = $this->ConfigManager->getKey('CONF_CAMPAIGN_STRICT_ACTIVE_CHECK_ENABLED');
		$this->logger->debug("campaign strict active check enabled key".$val);
		return $val;
	}
	
	public function hasRuleBeenDisabled($rule_id){
		$val = $this->ConfigManager->getKeyForEntity('CONF_DVS_RULE_DISABLED',$rule_id);
		$this->logger->debug("the rule ".$rule_id."was disabled before ".$val);
		return $val;
	}
	
	public function setRuleDisabledStatus($rule_id){
		$kvalue['entity_id'] = $rule_id ;
		$kvalue['value'] = true ;
		$kvalue['scope'] = 'RULE';
		$val = $this->ConfigManager->setKeyValue('CONF_DVS_RULE_DISABLED',$kvalue);
		$this->logger->debug("the rule ".$rule_id."is disabled now and status is".$val);
		return $val;
	}
	

	public function isCouponSeriesUser(){

		if(!$this->security_manager){
			include_once 'helper/SecurityManager.php';
			$this->security_manager = new SecurityManager();
		}
		$hasAccess = $this->security_manager->hasAccess("xaja","create_update_advance_coupon","campaign",true,false) ;
		$this->logger->debug("iscouponseriesuser ".$hasAccess);
		return $hasAccess ;
	}


	public function isAuthorizeUser(){

		if(!$this->security_manager){
			include_once 'helper/SecurityManager.php';
			$this->security_manager = new SecurityManager();
		}
		
		$hasAccess = $this->security_manager->hasAccess("campaign","Reports","campaign/rules/basic_config",true,true);
		$this->logger->debug("isauthorizeuser ".$hasAccess);
		return $hasAccess;
	}

	public function isConfigureUser(){

		if(!$this->security_manager){
			include_once 'helper/SecurityManager.php';
			$this->security_manager = new SecurityManager();
		}
		
		$hasAccess=$this->security_manager->hasAccess("campaign","CreateNewCampaign","campaign/rules/basic_config",true,true);
		$this->logger->debug("isconfigureuser ".$hasAccess);
		return $hasAccess;

	}

	public function isGlobalUser(){
			
		$hasAccess= ($this->isConfigureUser() 
							&& $this->isCouponSeriesUser() 
							&& $this->isAuthorizeUser()) ;
		$this->logger->debug("isGlobalUser ".$hasAccess);
		return $hasAccess;
	
	}
	
	public function isBouncebackCampaignAuthorized($campaign_id = -1){
		
		if(!$this->doesCampaignNeedsAuthorization()) {
			$this->logger->debug("campaign authorize status: false since campaign config key not enabled");
			return false;
		}
		
		if(!$this->rule_client){
			include_once 'thrift/ruleservice.php';
			$this->rule_client = new RuleServiceThriftClient();
		}

		$all_rulesets = $this->rule_client->getConfiguredRulesetsByContextId($this->org_id, false, 'DVS_ENDPOINT', $campaign_id);
		foreach ( $all_rulesets as $ruleset){
			$rules = $ruleset->rules;
			foreach ($rules as $rule){
				if($rule->isActive){
					$this->logger->debug("campaign authorize status: true since one of the rule is authorized for campaign_id ".$campaign_id);
					return true;
				}
			}
		}
		
		$this->logger->debug("campaign authorize status: false since no rule is authorized for campaign_id".$campaign_id);
		return false;
	}

	/**
	 * 
	 * Check if campaign is expired or not.
	 * @param unknown_type $campaign_id
	 * @throws InvalidInputException
	 */
	public function isCampaignExpired( $campaign_id ){
		
		$this->campaign_model_extension->load( $campaign_id );

		//check if campaign is already expired
		$end_date = $this->campaign_model_extension->getEndDate();
		$campaign_end_date = $this->campaign_model_extension->getEndDate( );
		$campaign_end_date = Util::convertTimeFromCurrentOrgTimeZone($campaign_end_date) ;
		$campagn_end_date_timestamp = strtotime($campaign_end_date) ;
		$current_timestamp = time( );
		
		if( $current_timestamp > $campagn_end_date_timestamp )
			return _campaign("Your campaign expired on")." ".date( 'M d,Y', strtotime($end_date)).". "._campaign("Please modify end date to re-schedule the campaign.");
		
		//check if campaign is active or not
		$campaign_start_date = $this->campaign_model_extension->getStartDate();
		$campagn_start_date = Util::convertTimeFromCurrentOrgTimeZone($campaign_start_date) ;
		$campagn_start_date_timestamp = strtotime($campagn_start_date) ;
		$current_timestamp = time( );
		
		if( $current_timestamp < $campagn_start_date_timestamp )
			return _campaign("Your campaign has not started yet").".". 
					_campaign("It will be active from")." ".date( 'M d,Y', strtotime($campaign_start_date));
								
		return false;
	}

	public function getCrossOrgVouchers($org_id){
		return $this->C_coupon_series_manager->getCouponSeriesBySourceOrg($org_id);
	}

	public function getAllNcaEnabledOrgs() {
		$sql = "SELECT `id`, `name` FROM `masters`.`organizations` ".
		"WHERE is_active = 1 ";
		$org_list = ShardedDbase::queryAllShards('masters', $sql);
		$this->logger->debug("returning list of active orgs for nca");
		return $org_list;
		/*
		$key = "CONF_VOUCHER_ORG_NCA_ENABLED";
		$sql = "SELECT `ckv`.`org_id`, `ckv`.`value` ".
				"FROM  `config_key_values` ckv ".
				"JOIN  `config_keys` ck ON ckv.key_id = ck.id ".
				"WHERE ck.name = '$key' AND ckv.is_valid = 1 ";
		$org_list = ShardedDbase::queryAllShards('masters', $sql);

		$this->logger->debug("key ids".print_r($org_list,true));
		$enabled_org_ids = array();
		foreach($org_list as $org_row) {
			$this->logger->debug("org_row".$org_row);
				
			if($org_row['value']) {
			$this->logger->debug("org_row pushing".print_r($org_row,true));
				array_push($enabled_org_ids, $org_row['org_id']);
			} else {
				$this->logger->debug("org_row pushing no".$org_row->value);
			}
		}
		$nca_enabled_orgs = OrganizationLoader::loadMultipleOrgs($enabled_org_ids);
		
		$this->logger->debug("returning nca org ids".print_r($enabled_org_ids,true)."nca enabled orgs: ".print_r($nca_enabled_orgs,true));
		return $nca_enabled_orgs;
		*/
	}
	
   public function convertDatetoMillis($date){
		
		$timeInMillis = strtotime($date);
		if($timeInMillis == -1 || !$timeInMillis )
		{
			throw new Exception(_campaign("Cannot convert")." "."'$date'"." "._campaign("to timestamp"), -1, null);
		}
		$timeInMillis = $timeInMillis * 1000;
		return $timeInMillis;
	}
	
	/**
	 * 
	 * Sending Test Email And SMS to group.
	 * @param unknown_type $params
	 * @param unknown_type $campaign_id
	 * @throws Exception
	 */
	public function PrepareAndSendMessages( $params , $campaign_id , $msg_type ){
				
		$users_email = array();
		$params['send_when'] = 'IMMEDIATE';
		$params['camp_group'] = $params['list'];
		$send_campaign_id = $campaign_id;
		try{
			$params['message'] = rawurldecode( $params['message'] );
			$params['subject'] = rawurldecode( $params['subject'] );
			
			if( $params['choose_list'] ){
					$status = $this->sendTestMessages( $campaign_id, $params , $msg_type );
			}else{
				
				$params['custom_tag_count'] = 0;
				
				if( !$params['on_off'] ){
					$params['group_name'] = 'test_group_'.strtotime( Util::getCurrentDateTime() );
					$type = 'test_group';
					$campaign_id = -30;
				}else{
					$type = 'sticky_group';
					$campaign_id = -20;
					if( !$params['group_name'] )
						throw new Exception( _campaign("Please provide appropiate list name!") );
				} 
				
				$group_id = $this->pasteAudienceList( $params, $campaign_id , $type, true );
				$this->logger->debug('@@@Group created with group id:'.$group_id );
				$params['camp_group'] = $group_id;
									
				$status = $this->sendTestMessages( $send_campaign_id, $params , $msg_type );
			}
		}catch ( Exception $e ){
			
			$this->logger->debug( '@@@ERROR:'.$e->getMessage() );
			throw new Exception( $e->getMessage() );
		}
		return $status;
	}

	
	/**
	 * 
	 * Sending Test Email And SMS.
	 * @param unknown_type $campaign_id
	 * @param unknown_type $params
	 * @param unknown_type $type
	 * @throws Exception
	 */
	public function sendTestMessages($campaign_id , $params , $blast_type = 'EMAIL'){
		
		 //validating data
        $C_bulk_message = new BulkMessage();
        $C_bulk_message->setCampaignId( $campaign_id );
        $C_bulk_message->setGroupId( $params['camp_group'] );
        $C_bulk_message->setScheduledType( $params['send_when'] );
        $C_bulk_message->setMessage( $params['message'] );
        $C_bulk_message->setParams( $params );
        $C_bulk_message->setDefaultArguments( $params["default_arguments"] );
        $C_bulk_message->setType( $blast_type );
        $C_bulk_message->setApproved(1);
        $C_bulk_validator = BulkMessageValidatorFactory::getValidator( BulkMessageTypes::valueOf( $blast_type ) );
       
        $queue_id = $C_bulk_validator->validate( $C_bulk_message , ValidationStep::$QUEUE );
			
		$this->logger->debug('@@@QUEUE_ID:'.$queue_id );	        
		
		$this->logger->debug('@@@pram test for perview of bulk sender:'.print_r($params,true) ); 
        
        $json_params = json_encode( $params );

        $C_bulk_message->load( $queue_id );
		$C_bulk_message->setParams( $json_params );
	        
        include_once 'business_controller/campaigns/message/impl/BulkMessageSenderFactory.php';

        $C_bulk_sender = BulkMessageSenderFactory::getSender( BulkMessageTypes::valueOf( $blast_type ) );     
       return $C_bulk_sender->send( $C_bulk_message );
	}
	
	/**
	 * CONTRACT(
	 * 	'campaign_id' => INT
	 * 	'msg_id' => INT
	 * )
	 * 
	 * @param unknown $params
	 * @param unknown $queue_type
	 * @param ValidationStep $validation_step
	 */
	public function queueMessage( $params, BulkMessageTypes $queue_type, ValidationStep $validation_step ){
		
		include_once 'helper/db_manager/TransactionManager.php';
		try{

			//start transaction
			$C_transaction_manager = new TransactionManager();
			$C_transaction_manager->beginTransaction();
			
			$this->logger->debug('@@In validate '.$params['campaign_id']);
			$C_bulk_validator = BulkMessageValidatorFactory::getValidator( $queue_type );
			
			$C_message = $C_bulk_validator->prepareMessage($params, $validation_step);
			$C_bulk_validator->validate( $C_message , $validation_step );
			
			//commit transaction
			$C_transaction_manager->commitTransaction();
			
			return $C_message;
				
		}catch ( Exception $e ){

			//roll back transaction
			$C_transaction_manager->rollbackTransaction();
			$this->logger->debug( "ROLLING BACK : Exception Was Thrown While Queuing msg".$e->getMessage() );
			throw new RuntimeException( $e->getMessage( ) );
		}
	}
	
	/**
	 *
	 * It will return hash array with the bulk message details
	 * @param int $msg_id
	 */
	public function getBulkMessageDetails( $msg_id ){
	
		$this->C_BulkMessage->load( $msg_id );
		
		return $this->C_BulkMessage;
	}
	
	/**
	 * depending on the type of campaign returns the date
	 * @param unknown_type $data
	 * CONTRACT(
	 *
	 * 	'send_when' : Type of selections ENUM [ IMMEDIATE | PARTICULAR_DATE | SCHEDULE ]
	 * 	'hours' : hour selection
	 * 	'minutes' : minute selection
	 * 	'cron_day' : cron days
	 * 	'cron_week' : cron weeks
	 * 	'cron_month' : cron months
	 * )
	 */
	public function getDateBySendingTypeForBulkBlast( $data, $readable = true ){
	
		$send_type = $data['send_when'];
		$this->logger->info( 'Send When : '.$send_type );
		switch( $send_type ){
	
			case 'SCHEDULE' :
	
				return 'RECURRING';
	
			case 'PARTICULAR_DATE' :
	
				if( $readable )
					$date = date( 'dS M Y' , strtotime( $data['date_field'] ) );
				else
					$date = $data['date_field'];
					
				$this->logger->info( 'date selected '.$date );
				//time details
				$hour = $data['hours'];
				$minute = $data['minutes'];
				$seconds = date( 's' );
	
				$calculated_date = $date.' '.$hour.':'.$minute.':'.$seconds;
				$this->logger->info( 'date calculated '.$calculated_date );
	
				return $calculated_date;
	
			case 'IMMEDIATE' :
	
				return date( 'Y-m-d H:i:s' );
		}
	}

	/**
	 * 
	 * It will return the supported tags for the BulkMessage msg_type 
	 * like SMS,EMAIL,STORE_TASK
	 * @param string $msg_type
	 */
	public function getSupportedTagsByType($msg_type = 'SMS', $campaign_id = false, $voucher_reminder_tags=false, $params=false){
		
		$C_bulk_validator = BulkMessageValidatorFactory::getValidator(
											BulkMessageTypes::valueOf($msg_type));
		
		return $C_bulk_validator->getTags($campaign_id, $voucher_reminder_tags, $params);
	}
	
	/**
	 * @param unknown $campaign_id
	 */
	public function getMsgingUsageReport( $campaign_id ){

		return $this->campaign_model_extension->getMsgingUsageReport( $campaign_id );
	}
	
	/**
	 * get email list for campaign overview
	 * @param unknown_type $campaign_id
	 */
	public function getEmailCampaignList( $campaign_id ){
		
		return $this->campaign_model_extension->getEmailCampaignList( $campaign_id );
	}
	
	/**
	 * It returns the info for email overview report
	 * @param unknown_type $campaign_id
	 */
	public function getEmailOverviewDetails( $campaign_id , $message_id ){
		
		return $this->campaign_model_extension->getEmailOverviewDetails( $campaign_id , 
						$message_id );
	}
	
	/**
	 * returns all active campaigns for all org
	 */
	public function getActiveCampaignForAllOrg(){
		
		return $this->campaign_model_extension->getActiveCampaignForAllOrg();
	}

	public function getInboxSkippedMessageCount($start_date, $end_date, $campaign_id, $org_id) {
		return $this->campaign_model_extension->getInboxSkippedMessageCount(date('Y-m-d',($start_date/1000)),
		 date('Y-m-d', ($end_date/1000)), $campaign_id, $org_id);
	}
	
	public function getOrgSkippedMessageCount($start_date, $end_date, $org_id) {
		return $this->campaign_model_extension->getOrgSkippedMessageCount(date('Y-m-d',($start_date/1000)),
		 date('Y-m-d', ($end_date/1000)), $org_id);
	}
	
	public function getControlGroupsByCampaignID( $campaign_id,$favourite, $search_filter ){
		return $this->campaign_model_extension->getControlGroupsByCampaignID( $campaign_id ,$favourite, $search_filter );
		
	}
	
	/**
	 * Get Campaign Report data for Forward to friend log.
	 * @param unknown $campaign_id
	 * @return Ambigous <multitype:, boolean>
	 */
	public function GetForwardToFriendsDataByCampaignId( $campaign_id , $message_id, $sql = false ){
		return $this->campaign_model->getForwardToFriendByCampaignId( 
							$campaign_id ,$message_id, $this->org_id, $sql );
	}

	/**
	 * Returns Campaign Email as Option With Group Label Appended to it.
	 * @param unknown $campaign_id
	 * @return unknown
	 */
	public function getCampaignEmailAsOption( $campaign_id ){
		
		$all_groups = $this->campaign_model_extension->getGroupsByCampaignId( $campaign_id );
		
		$groups = array();
		foreach ( $all_groups as $group ){
			$groups[$group['group_id']] = $group['group_label'];
		}	
		
		$message_list = $this->campaign_model_extension->getEmailCampaignListAsOptions( $campaign_id );

		$email_options = array();
		foreach ( $message_list as $message  ){
			$key = $message['subject'].'--'.$groups[$message['categoryIds']];
			$email_options[ $key ] = $message['messageId'];
		}
		
		return $email_options;
	}
	
	/**
	 * Getting all the email forwarded by particular sender.
	 * @param unknown $outbox_id
	 * @param unknown $campaign_id
	 */
	public function getForwardedByOutBoxId( $sender , $campaign_id ){
		return $this->campaign_model_extension->getForwardedByOutBoxId( $sender, $campaign_id, $this->org_id );
	}
	
	/**
	 *
	 * It will update voucher series for all campaign type
	 * @param int $campaign_id
	 * @param string $end_date
	 */
	public function updateCouponSeriesExpiryDateByCampaignId( $campaign_id ){
	
		$this->load( $campaign_id );
	
		$campaign_type = $this->campaign_model_extension->getType();
		$voucher_series = $this->campaign_model_extension->getVoucherSeriesId();
		$start_date = $this->campaign_model_extension->getStartDate();
		$end_date = $this->campaign_model_extension->getEndDate();
	
		if( empty( $voucher_series ) || $voucher_series == -1 )
			return;
	
		$voucher_series_ids = array();
		if( $campaign_type == 'action' ){
			$voucher_series_ids = json_decode( $voucher_series );
		}else if( $campaign_type == 'referral' ){
			$voucher_series = json_decode( $voucher_series , true );
			if( $voucher_series['referee'] )
				array_push( $voucher_series_ids , $voucher_series['referee'] );
			if( $voucher_series['referer'] )
				array_push( $voucher_series_ids , $voucher_series['referer'] );
		}else{
			$voucher_series_ids = array( $voucher_series );
		}
	
		foreach ( $voucher_series_ids as $voucher_series_id ){
	
			$this->C_coupon_series_manager->loadById( $voucher_series_id );
			$series_details = $this->C_coupon_series_manager->getDetails();
			$series_details = $this->getCouponSeriesExpiryParams(
					$campaign_id , $series_details );
			$series_details['store_ids_json'] = json_decode($series_details['store_ids_json'], true);
			$series_details['mutual_exclusive_series_ids'] =  json_decode($series_details['mutual_exclusive_series_ids'], true); 
			$series_details['redeem_stores'] =  $series['redeem_at_store'] ;
			$this->C_coupon_series_manager->updateDetails( $series_details );
		}
	}
	
	/**
	 *
	 * It sets the coupon series expiry parameters
	 * @param int $campaign_id
	 * @param array $series_details
	 */
	public function getCouponSeriesExpiryParams( $campaign_id , $series_details ){
	
		$this->load($campaign_id);
		$end_date = $this->campaign_model_extension->getEndDate();
		$series_details['valid_till_date'] = $end_date;
		return $series_details;
	}
	
	/**
	 * 
	 * It gives loyalty groups for the particular campaign
	 * @param int $campaign_id
	 * @return array('group_label'=>'group_id')
	 */
	public function getLoyaltyGroupsByCampaignId( $campaign_id ){
		
		$group_details = 
			$this->campaign_model_extension->getCampaignGroupsAllDetailsByCampaignIds(
					$campaign_id );

		$this->logger->debug('@@Loyalty Groups : '.print_r( $group_details , true ) );
		
		if( !is_array( $group_details ) )
			return array();
			
		$camp_groups = array();
		foreach( $group_details as $row ){
		
			if( strtoupper($row['type']) == 'LOYALTY' 
					&&
				strtoupper($row['target_type']) == 'TEST' ){
				
			$key = $row['group_label'];
			$camp_groups[$key] = $row['group_id'];
		}
		}
		return $camp_groups;
	}
	
	public function getCampaignNamesById( $campaign_ids ) {
		return $this->campaign_model_extension->getCampaignNamesById( $campaign_ids);
	}

	public function getCampaignNamesByIdAllShards( $campaign_ids ) {
		return $this->campaign_model_extension->getCampaignNamesByIdAllShards( $campaign_ids);
	}
	
	/**
	 * 
	 * Campaign Tracker Details
	 * @param int $org_id
	 * @throws Exception
	 */
	public function getCampaignTrackerDetails( $org_id , $start_date , $end_date ){
		
		$result = $this->campaign_model_extension->getCampaignTrackingDetails( 
						$org_id , $start_date , $end_date );
		
		if( empty( $result ) )
			throw new Exception(_campaign("No bulk campaigns sent for this selected period"));
			
// 		$outbox_summary = $this->getCampaignTrackerSummaryFromOutBoxes( $org_id );

// 		if( empty( $outbox_summary ) )
// 			throw new Exception('No Bulk campaigns sent for this selected period');

// 		$new_result = array();
// 		foreach( $result as $values ){
			
// 			foreach( $outbox_summary as $summary ){
				
// 				if( $values['guid'] == $summary['guid'] ){
// 					$values['message_text'] = $summary['messageText'];
// 					$values['message_sent_time_stamp'] = date("F j, Y g:i:s a" , strtotime($summary['sendTime']) );
// 					$values['total_audience'] = $summary['numDeliveries'];
// 					array_push( $new_result , $values );
// 				}
// 			}
// 		}
		
		$nsadmin_summary = $this->getCampaignTrackerSummaryFromNsadmin( $org_id );
		
		if( empty( $nsadmin_summary ) ){
			throw new Exception(_campaign("No bulk campaigns sent from nsadmin for this selected period"));
		}
		
		$final_result = array();
		foreach( $result as $values ){
			
			if( !empty( $nsadmin_summary[$values['intouch_campaign_id']]['credits_used'] ) ){
				unset( $values['guid'] );
				$values['credit_used_for_entire_campaign'] =
				$nsadmin_summary[$values['intouch_campaign_id']]['credits_used'];
				array_push( $final_result , $values );
			}
		}
		return $final_result;
	}
	
	/**
	 *
	 * It will fetch the campaign summary data from nsadmin
	 * @param int $org_id
	 * @param string $message_class
	 * @return Ambigous <NULL, string>
	 */
	private function getCampaignTrackerSummaryFromNsadmin( $org_id , $message_class = 'SMS' ) {
	
		include_once 'thrift/nsadmin.php';
		$C_nsadmin = new NSAdminThriftClient();
	
		$select_criteria = array();
		array_push($select_criteria, SummarySelectCriteria::CAMPAIGN_ID);
		array_push($select_criteria, SummarySelectCriteria::DATE);
		array_push($select_criteria, SummarySelectCriteria::MESSAGE_STATUS);
	
		$summary_params = array();
		//$summary_params['message_class'] = $message_class;
		$summary_params['sending_org_id'] = $org_id;
		$summary_params['select_fields'] = $select_criteria;
	
		$result = $C_nsadmin->getNSAdminMessageSummary($summary_params);
		
		$sent_statuses = array ('DELIVERED','SENT');
	
		$summary = array();
		foreach ($result as $k => $v ) {
			$fields = explode("--", $k);
			$campaign_id = $fields[0];
			$date = $fields[1];
			$status = $fields[2];
			$success = in_array($status, $sent_statuses)? true : false;
			$key = $campaign_id;
			if($campaign_id > 0) {
	
				if( !$success )
					continue;
				if(!array_key_exists($key, $summary)) {
					$summary[$key] = array("credits_used" => $success ? $v : 0);
				} else {
					if ($success) {
						$summary[$key]['credits_used'] += $v;
					}
				}
			}
		}
		return $summary;
	}
	
	/**
	 *
	 * It will fetch the campaign summary data from nsadmin
	 * @param int $org_id
	 * @param string $message_class
	 * @return Ambigous <NULL, string>
	 */
	private function getCampaignTrackerSummaryFromOutBoxes( $org_id , $message_class = 'SMS' ) {
	
		include_once 'thrift/msging.php';
		
		$C_msging = new MsgingThriftClient();
	
		$result = $C_msging->getOutboxesDetails($org_id,$message_class);
		
		return $result;
	}
	
	public function prepareHtmlForCustomTags( $tags ){
		
		foreach ( $tags as $key => $value ){
						
			if( is_array( $value ) ){
				$str .= "<li class='parent_tags_menu' id='ptags__".Util::uglify($key)."' >$key<i class='icon-plus float-right' id='submenu_icon__".Util::uglify($key)."' ></i>";
				$str .= "<ul id='tags_submenu__".Util::uglify($key)."' class='parent_email_sub_tag hide'>";
				foreach( $value as $k => $v ){
		
					if( is_array( $v ) ){
		
						$str .= "<li class='parent_tags_menu2' id='ptags2__".Util::uglify($k)."' >$k<i class='icon-plus float-right' id='submenu_icon2__".Util::uglify($k)."' ></i>";
						$str .= "<ul id='tags_submenu2__".Util::uglify($k)."' class='parent_email_sub_tag2 hide'>";
						foreach( $v as $k2 => $v2 ){
							$str .= "<li id='tag2__$v2' class='email_tags_edit' >$k2<i class='icon-circle-arrow-right float-right'></i></li>";
						}
						$str .= "</ul></li>";
					}
					else
						$str .= "<li id='tag__$v' class='email_tags_edit' >$k<i class='icon-circle-arrow-right float-right'></i></li>";
				}
				$str .= "</ul></li>";
			}
			else
				$str .= "<li id='tag__$value' class='email_tags_edit' >$key<i class='icon-circle-arrow-right float-right'></i></li>";
		}
		return $str;
	}
	
	/**
	 * get expired campaigns
	 * @param unknown $days
	 */
	public function getExpiredCampaignForHealthDashboard( $days = false ){
	
		return $this->campaign_model_extension->getExpiredCampaignForHealthDashboard( $days );
	}
	
	/**
	 * Getting Campaign coupon series combinations for creative assets.
	 * @return multitype:unknown mixed
	 */
	public function getCampaignCouponSeriesAsOption(){
		
		$campaigns = $this->campaign_model_extension->getAll();
		$camp_array = array();

		
		$coupon = $this->C_coupon_series_manager->
							C_voucher_series_model_extension->
								getCouponSeriesAsOptionsWithExpiryCheck();
		
		$series = array();
		foreach ( $coupon as $row ){
			$series[$row['id']] = $row['description'];
		}
		
		$final_coupon_series = array();
		
		foreach( $campaigns as $camp  ){

				if( !$this->isCampaignExpired( $camp['id'] )){
					
					$this->campaign_model_extension->load( $camp['id'] );
					$voucher_series = json_decode( $this->campaign_model_extension->getVoucherSeriesId() , true );
					$this->logger->debug('@@@Voucher Series ids:-'.print_r( $voucher_series , true ));
					$campaign_type = $this->campaign_model_extension->getType();
					$voucher_series_ids = array();

					if( $voucher_series != -1 ){
						
						if( $campaign_type == 'action' ){
							$voucher_series_ids = $voucher_series ;
						}else if( $campaign_type == 'referral' ){
							$voucher_series_ids =  $voucher_series ;
							/*if( $voucher_series['referee'] )
								array_push( $voucher_series_ids , $voucher_series['referee'] );
							if( $voucher_series['referer'] )
								array_push( $voucher_series_ids , $voucher_series['referer'] );*/
						}else if( $campaign_type == 'timeline'){
							$voucher_series_ids =  $voucher_series ;
						}else{
							$voucher_series_ids = array( $voucher_series );
						}
	
						$name = $camp['name'];
						
						foreach ( $voucher_series_ids as $voucher_series_id ){
							if( isset( $series[$voucher_series_id] ) )
								$final_coupon_series[$name."--".$series[$voucher_series_id]] = $voucher_series_id;
						}
					}
				}
		}
		$this->logger->debug('@@Camp Array:-'.print_r( $final_coupon_series , true ));
		
		return $final_coupon_series;
	}
	
	/**
	 * CONTRACT(
	 * 	'message' => string
	 * 	'subject' => string
	 * )
	 *
	 * @param unknown $params
	 * @param unknown $queue_type
	 * @param ValidationStep $validation_step
	 */
	public function validateTags( $params, BulkMessageTypes $queue_type, ValidationStep $validation_step ){
	
		try{
	
			$this->logger->debug('@@In validate '.print_r($params,true));
			$C_bulk_validator = BulkMessageValidatorFactory::getValidator( $queue_type );
	
			$C_message = $C_bulk_validator->prepareMessage($params, $validation_step);
			$C_bulk_validator->validate( $C_message , $validation_step );
	
			return $C_message;
	
		}catch ( Exception $e ){
			throw new RuntimeException( $e->getMessage( ) );
		}
	}
	
	/**
	 * @param $type - campaign type
	 * @return all active campaign by type
	 */
	public function getActiveCampaignsByTypeForOrg($type){
		
		return $this->campaign_model_extension->getActiveCampaignsByTypeForOrg( 
														$type, $this->org_id );
	}
	
	/**
	 * add campaign mapping out/dvs to referral
	 * @param unknown $campaign_id
	 * @param unknown $ref_camp_id
	 */
	public function addCampaignMapping( $campaign_id, $ref_camp_id ){
		
		$this->campaign_model_extension->addCampaignMapping(
								$campaign_id, $ref_camp_id );
	}
	
	/**
	 * get referral to other campaign mapping
	 * @param unknown $campaign_id
	 */
	public function getCampaignMapping( $campaign_id ){

		return $this->campaign_model_extension->getCampaignMapping( $campaign_id );
	}
	
	/**
	 * removes a voucher series id from campaign
	 * now used for referral campaign
	 * @param unknown $campaign_id
	 * @param unknown $voucher_id
	 */
	public function removeVoucherFromCampaign($campaign_id, $voucher_id){
		
		$this->campaign_model->load($campaign_id);
		$series = $this->campaign_model->getVoucherSeriesId();
		$this->logger->debug('series details: '.print_r($series, true));

		$series = json_decode($series, true);
		$arr = array();
		foreach ($series as $id){
			if($id != $voucher_id)
				array_push($arr, $id);
		}
		
		$series = json_encode($arr, true);
		$this->logger->debug('set series details: '.print_r($series, true));
		$this->campaign_model->setVoucherSeriesId($series);
		$this->campaign_model->update($campaign_id);
	}
	
	/**
	 * return the referral campaign which are expiring on given date
	 * @param unknown $date
	 */
	public function getReferralCampaignExpiringByDate($date){
		
		$exp = date('Y-m-d', strtotime($date));
		return $this->campaign_model_extension->getReferralCampaignExpiringByDate($exp);
	}
	
	public function getCampaignDetailByOrgId($org_id, $start_date_range = false , $end_date_range = false) {
		
		$this->logger->debug("date range is" . $start_date_range ."\t end" .$end_date_range);
		
		$campaign_array = $this->campaign_model_extension
					->getCampaignDetail ( $org_id, $start_date_range, $end_date_range );
			
		$this->logger->debug ( "Campaign Array is :" . print_r ( $campaign_array, true ) );
		
		return $campaign_array;
	}
	
	public function getCampaignNameByIdArray($campaign_id_array) {
		
		
		$campaign_id_csv = implode( ",", $campaign_id_array );
		$campaign_name_array = $this->campaign_model_extension->getCampaignNameByCampaignIdCSV( $campaign_id_csv );
		
		$this->logger->debug( "campaign name array is " . print_r ( $campaign_name_array, true ) );
		
		return $campaign_name_array;
	}
	
	public function queueCampaignUserReport( $message_id, $org_level_report ) {
		
		$this->logger->debug( "Downloading Users Report " );
		try {
				
			$C_veneno_bucket_handler = new VenenoDataDetailsHandler( $message_id );
				
			if( $C_veneno_bucket_handler->getBucketId() == null) {
		
				$this->logger->debug( "Returned bucket id is null" );
				
				return -1;
			}
		} catch (Exception $e) {
				
			$this->logger->debug( "exception in VenenoData Detail Handler" .$e->getMessage() );
			return -1;
		}
		
		$sent_sql = $C_veneno_bucket_handler->getsentUsersQuery( $org_level_report );
		$skipped_sql = $C_veneno_bucket_handler->getskippedUsersQuery( $org_level_report );
	
		$report_type = "Campaign Users";
		$options ['message_id'] = $message_id;
		$options ['report_type'] = $report_type;
	
		$file_name = "{{message_id}}_{{report_type}}";
		$file_name = Util::templateReplace( $file_name, $options );
		
		$query_array['sent_query'] = $sent_sql;
		$query_array['skipped_query'] = $skipped_sql;
		$query_array = json_encode( $query_array );
		
		try {
	
			$params = array(
					"queries" => " $query_array ",
					"type" => "MULTIPLE_QUERIES",
					"database" => "veneno_data_details",
					"file_name" => "$file_name",
					"limit" => '5000',
					'module' => 'campaign',
					'action' => "$report_type Report"
			);
	
			$this->C_download_manager->queue( $params, $params ['module'], $params ['action'] );
			$this->logger->debug( 'The Report Was Successfully Queued.
										The Notification will be sent to your email id once done' );
			return 1;
		} catch ( Exception $e ) {
				
			$this->logger->debug( "Exception occurred while downloading " . $e->getMessage () );
			return 0;
		}
	}
	
	
	/**
	 *
	 * Sending Test Email And SMS to group.
	 * @param unknown_type $params
	 * @param unknown_type $campaign_id
	 * @throws Exception
	 */
	public function PrepareAndSendMessagesV2( $params , $campaign_id , $msg_type ){
	
		$users_email = array();
		$params['send_when'] = 'IMMEDIATE';
		$send_campaign_id = $campaign_id;
		try{
			
			$params['message'] = rawurldecode( $params['message'] );
			$params['subject'] = rawurldecode( $params['subject'] );
			if( $msg_type == 'SMS' ){
				$params['message'] = "TEST ".$params['message'];
				$params['user_type'] = "1";
			}else{
				$params['subject'] = "TEST ".$params['subject'];
				$params['user_type'] = "2";
			}
			if( $params['list_type'] == 'sticky'){
				$status = $this->sendTestMessages( $campaign_id, $params , $msg_type );
			}else{
	
				$params['custom_tag_count'] = 0;
	
				if( !$params['save_as_sticky'] ){
					$params['group_name'] = 'test_group_'.strtotime( Util::getCurrentDateTime() );
					$type = 'test_group';
					$campaign_id = -30;
				}else{
					$type = 'sticky_group';
					$campaign_id = -20;
					if( !$params['group_name'] )
						throw new Exception( _campaign("Please provide appropiate list name!") );
				}
	
				$group_id = $this->pasteAudienceList( $params, $campaign_id , $type, true );
				$this->logger->debug('@@@Group created with group id:'.$group_id );
				$params['camp_group'] = $group_id;	
				$status = $this->sendTestMessages( $send_campaign_id, $params , $msg_type );
			}
		}catch ( Exception $e ){
				
			$this->logger->debug( '@@@ERROR:'.$e->getMessage() );
			throw new Exception( $e->getMessage() );
		}
		return $status;
	}
	
	public function tagBuilderForSmsTag( $option ){
		
		foreach( $option as $key => $value ){
				
			$template_option = '';
			$template_option = "'$key': { ";
			$desc_option = array();
			foreach( $value as $name => $desc ){
		
				if( $name == 'subtags' ){
						
					$sub_template_array = array();
						
					foreach( $desc as $desc_key => $desc_value ){
		
						$sub_template_option = '';
						$sub_template_option = "'$desc_key': { ";
						$sub_desc_option = array();
		
						foreach( $desc_value as $k => $v ){
		
							if( $k == 'subtags2' ){
		
								$sub_template_array_l2 = array();
									
								foreach( $v as $desc_key_l2 => $desc_value_l2 ){
		
									$sub_template_option_l2 = '';
									$sub_template_option_l2 = "'$desc_key_l2': { ";
									$sub_desc_option_l2 = array();
		
									foreach( $desc_value_l2 as $k_l2 => $v_l2 ){
											
										array_push( $sub_desc_option_l2, " $k_l2 : '$v_l2' " );
									}
									$sub_desc_option_l2 = implode( ',', $sub_desc_option_l2 );
									$sub_template_option_l2 .=	$sub_desc_option_l2 . "}";
		
									array_push( $sub_template_array_l2, $sub_template_option_l2 );
								}
		
								$sub_str_l2 = "";
								$sub_str_l2 = "'subtags2' : { ";
								$subitems_l2 = implode( ',', $sub_template_array_l2 );
								$sub_str_l2 .= $subitems_l2."}";
								array_push( $sub_desc_option ,  $sub_str_l2 );
							}else{
								array_push( $sub_desc_option, " $k : '$v' " );
							}
						}
						$sub_desc_option = implode( ',', $sub_desc_option );
						$sub_template_option .=	$sub_desc_option . "}";
						array_push( $sub_template_array, $sub_template_option );
					}
					$sub_str = "";
					$sub_str = "'subtags' : { ";
					$subitems = implode( ',', $sub_template_array );
					$sub_str .= $subitems."}";
					array_push( $desc_option ,  $sub_str );
				}else{
					array_push( $desc_option, " $name : '$desc' " );
				}
			}
			$desc_option = implode( ',', $desc_option );
				
			$template_option .=	$desc_option . "}";
				
			array_push( $template_array, $template_option );
		}
		
		$option = implode( ',', $template_array );
		
		return $option;
	}

	public function getWhereClauseForCampaignType($type,$campaign_type){
		$now= Util::convertTimeToCurrentOrgTimeZone();
		
		switch ( $type ){
				
			case 'Live' :
				$filter = " AND ( `end_date` >= '$now' AND `start_date` <= '$now' )";
				break;
					
			case 'Lapsed' :
				$filter = " AND ( `end_date` < '$now' )";
				break;
				
			case 'Upcoming':
				$filter = " AND ( `start_date` > '$now' )";
				break;
	
			default:
				$filter = " AND ( `end_date` < $now )";
				break;
		}
	
		switch ( $campaign_type ){
				
			case 'action' : $filter .=" AND ( cb.type = 'action' )";
			break;
	
			case 'outbound' : $filter .= " AND ( cb.type = 'outbound' )";
			break;
	
			case 'referral' : $filter .= " AND ( cb.type = 'referral' )";
			break;
				
			case 'survey' : $filter .= " AND ( cb.type = 'survey' )";
			break;
			
			case 'timeline' : $filter .= " AND ( cb.type = 'timeline' )";
			break;
	
			default: ;
		}
	
		return $filter;
	}
	
	public function getLimitFilter($display_start,$display_end){
	
		$limit = "";
		
		if ( isset( $display_start ) &&
				$display_end != '-1' ){
	
			$limit_filter = " LIMIT $display_start , $display_end ";
		}
	
		$limit_start = $display_start;
		$limit_end = $display_end;
	
		$limit_key = "table_campaign_home_css_152aeba4c8c5a8";
		$C_memcache_mgr = MemcacheMgr::getInstance();
		$this->logger->debug( "check 123 ".$limit_key);
	
		try{
				
			$limit = json_decode(
					$C_memcache_mgr->get( $limit_key ),
					true );
			$this->logger->debug('@@@RCD identifier Limit:-'.print_r( $limit,true));
		}catch( Exception $e ){
			$this->logger->debug( '@@@An Error occured while getting rcd_identifier Limit :-'.$e->getMessage());
		}
	
		if( $limit ){
				
			try{
	
				foreach ( $limit['params'] as $val ){
					$key = $this->container[$val];
					$limit_key .= '__'.$key;
				}
	
				$this->logger->debug('@@@NEW MEMCACHE KEY :-'.$limit_key);
				$limit = json_decode( $C_memcache_mgr->get( $limit_key ) , true );
	
				if( $this->container['is_first_call'] )
					$limit['start_limit'] = 0;
	
				$start_limit = $display_start;
				$end_limit = $display_end;
				$limit['start_limit'] += $end_limit;
				$json = json_encode( $limit );
	
				$this->logger->debug('@@@Next Limit:-'.$json);
	
				$C_memcache_mgr->set( $limit_key, $json, 1200 );
	
			}catch( Exception $e ){
	
				$start_limit = $limit['start_limit'];
				$end_limit = $limit['end_limit'];
				$limit['start_limit'] += $end_limit;
				$json = json_encode( $limit );
	
				$this->logger->debug('@@@Set New Limit:-'.$json);
				$C_memcache_mgr->set( $limit_key, $json, 1200 );
				$this->logger->debug('@@@ERROR:-'.$e->getMessage());
			}
			$limit_filter = " LIMIT $start_limit, $end_limit";
		}
		return $limit_filter;
	}
	
	public function getWhereClauseFilter($search ){
	
		$where = "";
		if ( isset( $search ) && $search != "" ){
			$where = " AND (";
			$where .= " `name` LIKE '%".$search."%' ";
			$where .= ')';
		}
		//put a loop to create the search
		return $where;
	}
	
	public function createTableContentForNewHomePage( $where_filter, $search_filter , $limit_filter ){
		
		global $sw_config;

		//To get Campaign Details for ALL Campaigns
		$results =
			$this->campaign_model_extension->
				getDataForNewHomePage( $this->org_id, $where_filter, $search_filter , $limit_filter );
		
		$table_content = array();
		
		//View Page Url
		$url = new UrlCreator();
		$url->setNameSpace('campaign/v3/base');
		$url->setPage( 'CampaignOverview' );
		
		//Coupon Url
		$c_url = new UrlCreator();
		$c_url->setNameSpace( 'campaign/v2/coupons' );
		$c_url->setPage( 'CreateOutBoundCoupons' );
		
		//customer list url
		$cust_url = new UrlCreator();
		$cust_url->setNameSpace( 'campaign/audience' );
		$cust_url->setPage( 'Home' );
		
		//Reports url
		$r_url = new UrlCreator();
		$r_url->setNameSpace( 'campaign/roi' );
		$r_url->setPage( 'Home' );
		
		$action = new UrlCreator();
		$action->setNameSpace( 'campaign/trackers' );
		$action->setPage( 'Home' );
		
		$ref_msg = new UrlCreator();
		$ref_msg->setNameSpace('campaign/v2/referral');
		$ref_msg->setPage('ReferralMessages');
		
		$ref_rs = new UrlCreator();
		$ref_rs->setNameSpace('campaign/v2/referral');
		$ref_rs->setPage('RewardSettings');
		
		$ref_coupon = new UrlCreator();
		$ref_coupon->setNameSpace('campaign/v2/referral');
		$ref_coupon->setPage('ReferralCoupon');
		
		$survey_overview = new UrlCreator();
		$survey_overview->setNameSpace('campaign/v2/csat');
		$survey_overview->setPage('SurveyDashboard');
						
		foreach( $results as $key => $value ){
			
			$actions = array();
			
			$view_url = $url->generateUrl( false , false );

			$c_url->setPageParams( array( 'campaign_id' => $value['id'] ) );
			$coupon_url = $c_url->generateUrl( false );

			$cust_url->setPageParams( array( 'campaign_id' => $value['id' ] ) );
			$cust_list = $cust_url->generateUrl( false );

			$r_url->setPageParams( array( 'campaign_id' => $value['id'] ) );
			$report_url = $r_url->generateUrl( false );

			$action->setPageParams( array( 'campaign_id' => $value['id'] ) );
			$action_url = $action->generateUrl( false );

			$ref_msg->setPageParams( array('campaign_id' => $value['id']) );
			$msg_url = $ref_msg->generateUrl( false );

			$ref_rs->setPageParams( array('campaign_id' => $value['id']) );
			$reward_url = $ref_rs->generateUrl( false );

			$ref_coupon->setPageParams( array('campaign_id' => $value['id']) );
			$rc_url = $ref_coupon->generateUrl( false );

			$survey_overview->setPageParams( array('campaign_id' => $value['id'], 'tab' => 'overview') );
			$survey_url = $survey_overview->generateUrl( false );

			$survey_overview->setPageParams( array('campaign_id' => $value['id'], 'tab' => 'forms') );
			$survey_forms = $survey_overview->generateUrl( false );

			if( $value['campaign_type'] == 'action' ){
				
				$view_url = "/campaign/rules/basic_config/NewRule?campaign_id=".$value['id']."&mode=create";
				$report_url = "/campaign/rules/basic_config/Reports?campaign_id=".$value['id'];
				$action_url =  "/campaign/rules/basic_config/NewRule?campaign_id=".$value['id']."&mode=create";
				$coupon_url = "/campaign/v2/coupons/CreateBounceBackCoupons?campaign_id=".$value['id'];

				//checking for the new DVS UI switch from region config
				if( $sw_config["intouch"]["arya_switch_enabled"] ){

					$new_url = "/campaign/DvsHome?id=".$value['id'];

					$view_url = $new_url;
					$report_url = $new_url;
					$action_url =  $new_url;
					$coupon_url = $new_url;
				}

				if($this->isCouponSeriesUser() && !$this->isGlobalUser()){
					$view_url = $coupon_url;
				}

				$actions[_campaign("View")] = $view_url;
				$actions[_campaign("Reports")] = $report_url;
				$actions[_campaign("Actions")] = $action_url;
				$actions[_campaign("Coupons")] = $coupon_url;
				$value["base_url"] = $view_url;
			}else if( $value['campaign_type'] == 'referral' ){
				
				$actions[_campaign("Messages")] = $msg_url;
				$actions[_campaign("Rewards")] = $reward_url;
				$actions[_campaign("Coupons")] = $rc_url;
				$value["base_url"] = $msg_url;
			}else if( $value['campaign_type'] == 'survey' ){
				
				$actions[_campaign("Overview")] = $survey_url;
				$actions[_campaign("Forms")] = $survey_forms;
				$value["base_url"] = $survey_url;
			}else if( $value['campaign_type'] == 'timeline' ){
				$value["base_url"] = "/campaign/timeline/TimelineCampaignOverview#timeline/campaign/".$value["id"]."/".$value["campaign_name"];
				$actions[_campaign("Reports")] = "/campaign/timeline/TimelineCampaignOverview#timeline/reports/".$value["id"]."/".$value["campaign_name"];
				$actions[_campaign("Coupons")] = "/campaign/timeline/TimelineCampaignOverview#timeline/view-coupon/".$value["id"]."/".$value["campaign_name"];
			}else{
				
				$actions[_campaign("Reports")] = $view_url."#reports/".$value["id"];
				$actions[_campaign("List")] = $view_url."#recipient/".$value["id"];
				$actions[_campaign("Coupons")] = $view_url."#view-coupon/".$value["id"];
				$value["base_url"] = "/campaign/v3/base/CampaignOverview#campaign/".$value["id"];
			}
			
			$value["campaign_name"] = $value["campaign_name"];
			$value[ 'start_date' ] =I18nUtil::convertDateToLocale($value[ 'start_date' ], IntlDateFormatter::LONG, IntlDateFormatter::NONE);
			$value[ 'end_date' ] = I18nUtil::convertDateToLocale($value[ 'end_date' ], IntlDateFormatter::LONG, IntlDateFormatter::NONE);
			$value[ 'description' ] = !empty($value[ 'description' ]) ? 
				_campaign("Coupon Details:")." ".Util::beautify( $value[ 'description' ] ) : _campaign("No coupons attached") ;

			$value[ 'description' ] .= "<br/>";
			
			if( $value['campaign_type'] == 'referral' ){
				
				$C_ref_controller = new ReferralCampaignController();
				$res = $C_ref_controller->getReferralCampaignMetaDetails( $value['id'] );
				$token = $res['token'];
				$value[ 'description' ] .= "<span>"._campaign("Token:")." ".$token."</span>";
				if( $res['default_at_pos'] == 1 )
					$value[ 'description' ] .= "<br/><span>"._campaign("Default for POS")."</span>";
				if( $res['base_url'] )
					$value[ 'description' ] .= ", <span>"._campaign("Online Campaign")."</span>";
				if( $res['incentivise_type']  == 'FINAL' )
					$value[ 'description' ] .= ", <span>"._campaign("Incentivize Type: EOC")."</span>";

			}else if( $value['campaign_type'] == 'survey' ){
				
				$C_survey_controller = new SurveyController();
				$res = $C_survey_controller->getSurveyDetails( $value['id'] );
				$total_forms = count($res['survey_forms']);
				$total_response = intval($res['number_of_responses']);
				$overall_nps = $res['overall_nps_score'];
				
				$value[ 'description' ] = '<span>'.$total_forms." "._campaign("Forms Attached").'</span>';
				$value[ 'description' ] .= "<br/>".'<span>'._campaign("Total responses:")." ".'</span>
						<span class="font-grey">'.$total_response.'</span>'.
						'<span>'._campaign("Overall NPS:")." ".'</span><span class="font-grey">'.$overall_nps.'</span>';
			}else if( $value['campaign_type'] == 'timeline' ){
				
				$value[ 'description' ] = $value['number_of_vs_attached'] > 0 ? $value['number_of_vs_attached']._campaign("Coupon series attached") : _campaign("No coupon series attached");
				$value[ 'description' ] .= "<br/>";
			}else if( $value['campaign_type'] == 'outbound' ){
				
				$groups = GroupDetailModel::getAllByCampaignId( $value['id'] , $this->org_id );
				
				$value["no_of_audience_list"] = count($groups);
				
				
				if( count($groups) > 0 ){
					$value[ 'description' ] .= (($value["no_of_audience_list"]>1) ?
						$value["no_of_audience_list"]." "._campaign("recipient lists created,") : $value["no_of_audience_list"]." "._campaign("recipient list created,"));
				}else{
					$value[ 'description' ] .= _campaign("No recipient lists created").'<br/>';
				}
				
				$queue_messages = $this->C_BulkMessage->loadByCampaignID( 
						$this->org_id , $value['id'] );
				
				if( !empty( $queue_messages ) ){
					$sent_email_count = 0;
					$sent_sms_count = 0;
					$sent_call_count = 0;
					$schedule_email_count = 0;
					$schedule_sms_count = 0;
					$schedule_call_count = 0;
					$schedule_lbl="";
					$sent_lbl="";
					foreach ( $queue_messages as $msg_key => $msg_value ){
						
						if( $msg_value["Approved"] == 0 )continue;
						
						if( $msg_value["scheduled_type"] == "IMMEDIATELY" 
								&&
							$msg_value["status"] != "SENT" 
						) continue;
						
						$type = $msg_value["type"];
						
						if( $msg_value["scheduled_type"] == "SCHEDULED" ){

							if ( strtolower( $type ) == 'email' || strtolower( $type ) == 'email_reminder'){
								$schedule_email_count++;
							}elseif( strtolower( $type ) == 'call_task' || strtolower( $type ) == 'call_task_reminder' ){
								$schedule_call_count++;
							}else{
								$schedule_sms_count++;
							}
							continue;
						}else if( $msg_value["scheduled_type"] == "PARTICULAR_DATE" ){

							if( $msg_value["status"] == "OPEN" ){
								if ( strtolower( $type ) == 'email' || strtolower( $type ) == 'email_reminder'){
									$schedule_email_count++;
								}elseif( strtolower( $type ) == 'call_task' || strtolower( $type ) == 'call_task_reminder' ){
									$schedule_call_count++;
								}else{
									$schedule_sms_count++;
								}
								continue;
							}
						}
						
						if ( strtolower( $type ) == 'email' || strtolower( $type ) == 'email_reminder'){
							$sent_email_count++;
						}elseif( strtolower( $type ) == 'call_task' || strtolower( $type ) == 'call_task_reminder' ){
							$sent_call_count++;
						}else{
							$sent_sms_count++;
						}
					}
					
					if( $schedule_email_count > 0 )
						$schedule_lbl .= $schedule_email_count._campaign("Email");
						
					if( $schedule_sms_count > 0 ){
						if( $schedule_lbl )
							$schedule_lbl .= ", ";
						$schedule_lbl .= $schedule_sms_count._campaign("Texts");
					}
						
					if( $schedule_call_count > 0 ){
						if( $schedule_lbl )
							$schedule_lbl .= ", ";
						$schedule_lbl .= $schedule_call_count._campaign("Call Tasks");
					}
						
					if( $sent_email_count > 0 )
						$sent_lbl .= $sent_email_count._campaign("Email");
					
					if( $sent_sms_count > 0 ){
						if( $sent_lbl )
							$sent_lbl .= ", ";
						$sent_lbl .= $sent_sms_count._campaign("Texts");
					}
					
					if( $sent_call_count > 0 ){
						if( $sent_lbl )
							$sent_lbl .= ", ";
						$sent_lbl .= $sent_call_count._campaign("Call Tasks");
					}
					
					if( empty($sent_lbl) && empty($schedule_lbl) )
						$value[ 'description' ] .= _campaign("No messages sent")."<br/>";
					else{
						$sent_lbl = !empty($sent_lbl) ? _campaign("Sent")." ".$sent_lbl : "";
						$schedule_lbl =	!empty($schedule_lbl) ? _campaign("Scheduled")." ".$schedule_lbl : "";
					
						$value[ 'description' ] .= $sent_lbl."<br/>".$schedule_lbl;
					}
				}else{
					$value[ 'description' ] .= _campaign("No messages sent")."<br/>";
				}
				
				$mapp = $this->getCampaignMapping( $value['id'] );
				$this->logger->info('mapping info : '.print_r($res, true));
					
				if( !empty($mapp) ){
				
					$ref_id = $mapp['ref_campaign_id'];
					$this->load( $ref_id );
					$name = $this->campaign_model->getName();
					if( strlen( $name ) > 33 )
						$name = substr( $name, 0 , 30 ).'...';
					
					$value[ 'description' ] .= _campaign("For Referral:").$name;
				}
			}
			
			if( !empty( $actions ) )
				$value["actions"] = $actions;
			
			array_push( $table_content , $value );
		}
		
		$this->logger->debug( "@Result:".print_r( $table_content , true ) );
		
		return $table_content;
	}
	
	/**
	 * gettting custom sender details for organization
	 * @param unknown $org_id
	 */
	public function getCustomSenderDetails( $org_id ){
		
		$custom_sender = new CustomSenderModel();
		$custom_sender->load( $org_id );
		$sender = $custom_sender->getHash();
		
		return $sender;
	}
	
	/**
	 * update custom sender details.
	 * @param unknown $org_id
	 * @param unknown $sender_label
	 * @param unknown $sender_email
	 */
	public function updateCustomSender( $org_id , $sender_label , $sender_email ){
		
		$custom_sender = new CustomSenderModel();
		$custom_sender->load( $org_id );
		
		$custom_sender->setSenderLabel( rawurldecode( $sender_label ));
		$custom_sender->setSenderEmail( rawurldecode( $sender_email ));
		
		$result = $custom_sender->update( $org_id );
		
		return $result;
	}

	/**
	 * adding subject to subjetc index table for auto suggest
	 * @param unknown $subject
	 */
	public function addsubject( $subject ){
		if( $subject ){
			$subject = addslashes( stripslashes( $subject ) );
			return $this->campaign_model_extension->addSubjectToIndex( $subject );
		}
		
		return false;
	}
	
	public function getSubjectListByOrg( $subject ){
		return $this->campaign_model_extension->getSubjectList( $subject , $this->org_id );
	}	

	/**
	 * add campaign mapping otbound to survey
	 * @param unknown $campaign_id
	 * @param unknown $ref_camp_id
	 */
	public function addSurveyCampaignMapping($campaign_id, $ref_camp_id){
		
		$this->campaign_model_extension->addSurveyCampaignMapping( $campaign_id, 
																$ref_camp_id );
	}
	
	/**
	 * add campaign mapping outbound to recommendation plan
	 * @param unknown $campaign_id
	 * @param unknown $reco_plan_id
	 */
	public function addRecoCampaignMapping($campaign_id, $reco_plan_id){
	
		$this->campaign_model_extension->addRecoCampaignMapping( $campaign_id,
				$reco_plan_id );
	}
	
	/**
	 * get survey to other campaign mapping
	 * @param unknown $campaign_id
	 */
	public function getSurveyCampaignMapping($campaign_id){

		return $this->campaign_model_extension->getSurveyCampaignMapping($campaign_id);
	}

	public function getSurveyFormsByOutboundCampaignId( $campaign_id , $form_id = false ){

    	try{

    		$C_survey_controller = new SurveyController();
    		$C_survey_controller->checkCampaignExpiry( $campaign_id );

    		$res = $this->getSurveyCampaignMapping( $campaign_id );
			$this->logger->debug('survey map details: '.print_r($res, true));
			$details = array();
			if(!empty($res)){
				$ref_id = $res['ref_campaign_id'];
				//check expiry of survey campaign
				$C_survey_controller->checkCampaignExpiry( $ref_id );
				$form_details = $C_survey_controller->getSurveyformUrl( $ref_id );
				$this->logger->debug('survey map details: '.print_r($form_details, true));
				if( !empty( $form_details["data"] ) ){
					$brand_logo = $form_details["logo"];
					$form_details = $form_details["data"];
					foreach ($form_details as $detail) {
						if( $detail["is_published"] == 1 ){
							$links = json_decode( $detail["fs_link"] , true );
							$detail["preview_url"] = $links["preview_url"];
							if( $form_id && ( $form_id == $detail["form_id"] ) ){
								$detail["brand_logo"] = $brand_logo;
								return $detail;
							}
							array_push( $details , $detail );
						}
					}
				}
				$this->logger->debug('survey form details: '.print_r($details, true));
				return $details;
			}
    	}catch( Exception $e ){
    		$this->logger->error("@While getting survey forms:".$e->getMessage());
    		return array();
    	}
    }
    
    public function getCampaignsByMessageType( $org_id, $message_type, $start_range = false,
    		 $end_range = false ) {
    	
    	$this->logger->debug( "@@@FLR getcampaignwith by message type 
    			 org_id $org_id start range = $start_range  end range
    			 $end_range type is " .print_r( $message_type, true ) );
    	
    	if( $message_type == "call_task" )
    		$type = array('CALL_TASK_REMINDER', 'CALL_TASK');
    	else if( $message_type == "email" )
    		$type = array( 'EMAIL', 'EMAIL_REMINDER' );
    	else if( $message_type == "sms" )
    		$type = array( 'SMS', 'SMS_REMINDER' );
    	else if( $message_type == "wechat" )
    		$type = array( 'WECHAT', 'WECHAT_REMINDER' );
    	else if( $message_type == "mobilepush" )
    		$type = array( 'MOBILEPUSH', 'MOBILEPUSH_REMINDER' );
    	else
    		$type = array();// by default ALL
    	
   		$type_csv = "'".implode( "','", $type )."'";
    	
    	$campaigns = $this->campaign_model_extension->
    		getCampaignsByMessageType( 
    			$org_id, $type_csv, $start_range, $end_range );
    	
    	return $campaigns;
    }
    
    public function getCampaignsByDateRange( $org_id, $start_range, $end_range ){
    	
    	$this->logger->debug( "@@@FLR getcampaignbyrange org_id $org_id start range = $start_range  end range
    			$end_range type is " .print_r( $message_type, true ) );
    	 
    	$campaigns = $this->campaign_model_extension->getCampaignDetail( $org_id, $start_range, $end_range );
    	    	 
    	return $campaigns;
    }
    
    public function getVoucherSeriesAsOptionsByCampaignIds( $campaign_ids ){
    	
    	$this->logger->debug( "@@@FLR getVoucherSeriesIds by campaignIds ".print_r( $campaign_ids ) );
    	$series = $this->campaign_model_extension->getVoucherSeriesIdsByCampaigns( $campaign_ids, $this->org_id );
    	$series_ids = array();
    	foreach( $series as $series_json ){

    		if( $series_json['voucher_series_id'] != -1 ){
    			
    			$series_decoded = json_decode( $series_json['voucher_series_id'], true ) ?
    				json_decode( $series_json['voucher_series_id'], true ) : array( $series_json['voucher_series_id'] ) ;
    			if( $series_decoded['referer'] || $series_decoded['referee'] ){
    				
    				$series_decoded = array( $series_decoded['referer'], $series_decoded['referee'] );
    			}
    			if( !is_array( $series_decoded ) )
    				$series_decoded = array( $series_decoded );
    			$series_ids = array_merge( $series_ids, $series_decoded );
    		}
    	}
    	$this->logger->debug( "Final Series Ids array ".print_r( $series_ids, true ) );
    	return $series_ids;
    }
    
    public function getVoucherSeriesDescriptionByIds( $voucher_series_id ) {
    	
    	if( is_array( $voucher_series_id ) )
    		$voucher_series_id = implode( ",", $voucher_series_id );
    	
    	return $this->campaign_model_extension->
    			getVoucherSeriesDescriptionByIds( $voucher_series_id );
    }
    
    public function getTotalRevenueByVoucherSeriesId( $voucher_series_id ) {
    	
    	return $this->campaign_model_extension->
    			getTotalRevenueByVoucherSeriesId( $voucher_series_id, $this->org_id );
    }
    
    public function getIssuedVoucherCountByVoucherSeries( $vouchers_series_ids ) {
    	
    	if( is_array( $vouchers_series_ids ) )
    		$vouchers_series_ids = implode( ",", $vouchers_series_ids );
    	
    	return $this->campaign_model_extension->
		    	getIssuedVoucherCountByVoucherSeries( $vouchers_series_ids,
		    	$this->org_id );
    }
    
    public function getRedemVoucherByVoucherSeries( $voucher_series_ids ) {
    	
    	if( is_array( $voucher_series_ids ) )
    		$voucher_series_ids = implode( ",", $voucher_series_ids );
    	
    	return $this->campaign_model_extension->
    			getRedemVoucherByVoucherSeries( $voucher_series_ids,
    			$this->org_id );
    }
    
    public function getRedeemerCountByVoucherSeries( $voucher_series_ids ) {
    	    	
    	if( is_array( $voucher_series_ids ) )
    		$voucher_series_ids = implode( ",", $voucher_series_ids );
    	 
    	return $this->campaign_model_extension->
    			getRedeemerCountByVoucherSeries( $voucher_series_ids,
    			$this->org_id );
    }
    
    public function queueReminderMessage( $params, BulkMessageTypes $queue_type, ValidationStep $validation_step ){
    
    	include_once 'helper/db_manager/TransactionManager.php';
    	try{
    
    		//start transaction
    		$C_transaction_manager = new TransactionManager();
    		$C_transaction_manager->beginTransaction();
    			
    		$this->logger->debug('@@In validate '.$params['campaign_id']);
    		$C_bulk_validator = BulkMessageValidatorFactory::getValidator( $queue_type );
    			
    		$C_message = $C_bulk_validator->prepareMessage($params, $validation_step);
    		$msg_id = $C_bulk_validator->validate( $C_message , $validation_step );
    			
    		//commit transaction
    		$C_transaction_manager->commitTransaction();
    			
    		return $msg_id;
    
    	}catch ( Exception $e ){
    
    		//roll back transaction
    		$C_transaction_manager->rollbackTransaction();
    		$this->logger->debug( "ROLLING BACK : Exception Was Thrown While Queuing msg".$e->getMessage() );
    		throw new RuntimeException( $e->getMessage( ) );
    	}
    }
    
    public function getDetailedNotSentReport( $message_id ){
    	$C_veneno_bucket_handler = new VenenoDataDetailsHandler( $message_id );
    	return $C_veneno_bucket_handler->getNotSentSplitByMessageId($message_id);
    }
    
    public function getRecoProductAttribPlan (){    	
    	
    	$all_Plans_name_id = array();    	
    	try {
    		$C_recommendation_controller = new RengineController();    	
    		$reco_plans = $C_recommendation_controller->getPlan();
    		$this->logger->debug('reco form tags : obtained plans for the org'.print_r($reco_plans, true));
    	
    		foreach ($reco_plans as $plan){
    			$rec_Constraints=$plan->recommendationConstraints;
     			foreach ($rec_Constraints as $constraint){
 				if($constraint->constraintType == ConstraintType::ONLY_SUCH_PRODUCT_ATTRIBUTES){
    					$all_Plans_name_id = array_merge($all_Plans_name_id, array($plan->name=>$plan->id));
    					break;
    				}
    			}
    		}
    	} catch ( Exception $e ){
    		$this->logger->debug("Exception in getting recommendation plans :".print_r( $e, true) );
    	}
        
    	$this->logger->debug('reco form tags : displaying all plans and ids - ' . print_r($all_Plans_name_id,true));    
    	return $all_Plans_name_id;
    
    }
    
    public function getRecoProductAttribPlanId( $campaign_id ){
    	
    	return $this->campaign_model_extension->getRecoCampaignMapping( $campaign_id );
    		
    }
    
    public function getRecoProductAttribPlanObj( $reco_plan_id ) {
    	
    	$plan = false;
    	try {
    		$C_recommendation_controller = new RengineController();
    		$reco_plans = $C_recommendation_controller->getPlan();
    		$this->logger->debug('reco form tags : obtained plans for the org'.print_r($reco_plans, true));
    		foreach ($reco_plans as $plan){
    			if ($plan->id == $reco_plan_id)
    				return $plan;
    		}
    		
    	} catch ( Exception $e ){
    		$this->logger->debug("Exception in getting recommendation plans :".print_r( $e, true) );
    	}
    	
    	return $plan;
    	
    	
    }
    
    public function updateRecoProductAtrribPlan ( $campaign_id, $reco_plan_id, $num_of_recos, $size_of_attrs ) {
    	
    	return $this->campaign_model_extension->updateRecoPlanDetails( $campaign_id, $reco_plan_id, $num_of_recos, $size_of_attrs );
    }
    
    public function getRecoProductAttribDetails ( $campaign_id, $plan_id ) {
    	 
    	return $this->campaign_model_extension->getRecoProductAttribDetails ( $campaign_id, $plan_id ) ;
    }

    public function getOUCustomSenderDetails($org_id,$entities_id,$user_type){
    	$OuCustomSenderModel = new OuCustomSenderModel();
    	$entity_ids= implode(',', $entities_id);
    	return $OuCustomSenderModel->getOuDetailsByEntityIdList($org_id,$entity_ids,$user_type);
       }

    public function getSenderDetails($msg_type = null,$account_id = null){

    	if( $msg_type == 'WECHAT' ) {
    		
    		include_once "business_controller/wechat/WeChatAccountController.php";

    		$C_WechatCtrl = new WeChatAccountController( $this->org_id );
    		
    		$result = $C_WechatCtrl->get_account_by_id( $account_id );
   			
			return $result;
    	}
    	elseif ($msg_type == 'PUSH') {
    		include_once 'business_controller/ChannelController.php';
    		$channelController = new ChannelController();
    		$accountConfig = $channelController->getConfigByAccountId($account_id);
    		$accountDetail = $channelController->getAccountDetailByID($account_id);
    		$this->logger->debug("account configuration detail".print_r($accountConfig,true)."account detail".print_r($accountDetail,true));
    		$account_detail['account_id'] = $account_id;
    		$account_detail['account_name'] =$accountDetail[0]['account_name']; 
    		$result = $accountConfig + $account_detail;
    		$this->logger->debug("getSenderDetails for mobile push".print_r($result,true));
    		return $result;
    	}
    	else {

	    	$C_admin_user = new AdminUserController();
	    	
	    	$admin_user_type = $C_admin_user->getAdminUserType();
	    	$entities_id = $C_admin_user->getRefList();


	    	$this->logger->debug("Admin user type $admin_user_type and entities_id: ".print_r($entities_id,true));

	    	switch($admin_user_type){
	    		case "CONCEPT":	return  $this->getOUCustomSenderDetails($this->org_id,$entities_id,"CONCEPT");
	    						break;
	    		default : return array($this->getCustomSenderDetails($this->org_id));
	    					break;	
	    		
	    	}
	    }
    }

    public  function isPerformanceEnabled(){
    	$this->logger->debug("isPerformanceEnabled initialized for an org");
    	$db = new Dbase('conquest_cube_management');
    	$sql = "SELECT COUNT( * ) FROM `conquest_reports`.`m_org_disabled_categories` 
    		WHERE `org_id` = $this->org_id AND `category_id` = 5";
    	$res = $db->query_scalar($sql);
    	$this->logger->debug("isPerformanceEnabled result from sql");
       	if($res>0)
    		return false;
    	else 
    		return true;
    }

    /**
	 * This function is used to tag the entities with zone or concept
	 * type like COUPON_SERIES, OUTBOUND_MSG, OUTBOUND_LIST, OUTBOUND_CAMP
	 */
    public function tagCampaignEntityByType( $type, $ref_id, $entity_type, $entity_ids ){
		
    	$this->logger->debug("@Tag Entity: ".$type."-".$ref_id."-".$entity_type."-".$entity_ids);

    	$this->campaign_model_extension->tagCampaignEntityByType(
    		$this->org_id, $type, $ref_id, $entity_type, $entity_ids, $this->user_id );

    	$this->logger->debug("@End Tag Entity: ".$type."-".$ref_id."-".$entity_type."-".$entity_ids);
    }

    /**
	 * This function is used to get tagged entities by type and ref_id
	 */
    public function getTaggedCampaignEntity( $type, $ref_id, $entity_type = "CONCEPT" ){

    	$this->logger->debug("@get Tagged Entity: ".$type."-".$ref_id);

    	$result = $this->campaign_model_extension->getTaggedCampaignEntity( 
    		$this->org_id, $type, $ref_id, $entity_type );

    	$this->logger->debug("@End get Tagged Entity: ".$type."-".$ref_id.":".print_r( $result, true));

    	return $result;
    }

    public function getEntityFromSenderDetail( $type , $sender_detail ){

    	$C_admin_user = new AdminUserController();
    	
    	$admin_user_type = $C_admin_user->getAdminUserType();
    	$entities_id = $C_admin_user->getRefList();

    	$this->logger->debug("Admin user type $admin_user_type and entities_id: ".print_r($entities_id,true));

    	if( $admin_user_type != "ORG" ){

    		$s_details = $this->getOUCustomSenderDetails($this->org_id,$entities_id,$admin_user_type);

    		if( count($s_details) > 0 ){

    			$this->logger->debug("OU details found:".print_r( $s_details,true));

    			foreach( $s_details as $sdetail ){

					if( $sdetail["sender_email"] == $sender_detail && $type == 'EMAIL' ){
						$entity_id = $sdetail["entity_id"];
						$entity_type = $sdetail["entity_type"];
						break;
    				}

    				if( $sdetail["sender_gsm"] == $sender_detail && $type == 'SMS' ){
						$entity_id = $sdetail["entity_id"];
						$entity_type = $sdetail["entity_type"];
						break;
    				}
				}
    		}else{
    			$this->logger->debug("No OU details found");
    			return array($admin_user_type,-1);
    		}

    		$this->logger->debug("OU details found:".$entity_id."-".$entity_type);

    		return array($entity_type,$entity_id);
    	}else{

    		return array("ORG",$this->org_id);
    	}
    }

    public function tagCampaignEntity($concept_id,$campaign_id){
    	$this->logger->debug('@@@Concept Id: '.$concept_id.' Campaign Id: '.$campaign_id);
    	if(count($concept_id)>0){
    		$this->tagCampaignEntityByType( 'CAMPAIGN', $campaign_id, 'CONCEPT', $concept_id );
    	}
    }

    public function tagCouponSeries( $voucher_series_id , $redeemed_store_ids ){

		include_once 'model_extension/class.OrgEntityModelExtension.php';
		$OrgEntityModelExtension = new OrgEntityModelExtension();

		$redeemed_store_ids = implode(",", $redeemed_store_ids);

		$store_ids = 
			$OrgEntityModelExtension->getParentsById( $redeemed_store_ids, 'STORE', $this->org_id );
		$store_ids = array_values( array_unique($store_ids) );
		$this->logger->debug("Tag Concepts Store Ids:". print_r($store_ids,true));

		$store_ids = implode(",", $store_ids);

		$concept_ids = 
			$OrgEntityModelExtension->getParentsById( $store_ids, 'CONCEPT', $this->org_id );
		$concept_ids = array_values( array_unique($concept_ids) );
		$this->logger->debug("Tag Concepts :". print_r($concept_ids,true));

		if( count( $concept_ids ) > 0 )
			$this->tagCampaignEntityByType( 'COUPON_SERIES', $voucher_series_id, 'CONCEPT', json_encode($concept_ids) );

		$zone_ids = 
			$OrgEntityModelExtension->getParentsById( $store_ids, 'ZONE', $this->org_id );
		$zone_ids = array_values( array_unique($zone_ids) );
		$this->logger->debug("Tag Zones :". print_r($zone_ids,true));

		if( count( $zone_ids ) > 0 )
			$this->tagCampaignEntityByType( 'COUPON_SERIES', $voucher_series_id, 'ZONE', json_encode($zone_ids) );
	}

	public function saveSecondaryTemplates($params,$message_id,$module="OUTBOUND",$msg_type="EMAIL"){
		$all_templates = $params['all_templates'] ;
		$sql = "INSERT INTO `msging`.`msg_secondary_templates` (org_id,module,ref_id,msg_type,msg_body,lang_id,default_params,is_deleted) VALUES " ;
		$insert_values = array() ;
		$db = new Dbase('msging');
		foreach ($all_templates as $key => $value) {
			$lang_id = $key ;
			$msg_body = rawurldecode($value['template_data']['html_content'] );
			$msg_body = $this->replacePhpTags($msg_body) ;
			$msg_body = addslashes($msg_body) ;
			$default_params = array() ;
			$default_params['is_preview_generated'] =  $value['template_data']['is_preview_generated'] ;
			$default_params['preview_url'] = $value['template_data']['preview_url'] ;
			$default_params['is_favourite'] = $value['template_data']['is_favourite'] ;
			$default_params['is_drag_drop'] = $value['template_data']['is_drag_drop'] ;
			$default_params['drag_drop_id'] = $value['template_data']['drag_drop_id'] ;
			$default_params['scope'] = $value['template_data']['scope'] ;
			$default_params['tag'] = $value['template_data']['tag'] ;
			$default_params['is_default'] = $value['template_data']['is_default'] ;
			$is_deleted = isset($value['template_data']['is_deleted']) ? $value['template_data']['is_deleted'] : 0 ;

			$default_json = json_encode($default_params) ;
			$this->logger->debug("encoded json is : ".$default_json) ;
			if(!empty($value['template_data']['secondary_template_id']) && $value['template_data']['secondary_template_id']>0){
				$secondary_template_id = $value['template_data']['secondary_template_id'] ;
				$update_sql =  " UPDATE `msging`.`msg_secondary_templates` SET msg_body='$msg_body' , default_params='$default_json' , is_deleted=$is_deleted WHERE id= $secondary_template_id" ;
				$db->update($update_sql) ;
			}else{
				$insert_values[] = "($this->org_id,'$module',$message_id,'$msg_type','$msg_body',$lang_id,'$default_json' , $is_deleted)" ;
			}
			
		}
		$insert_str = implode(",",$insert_values)	 ;
		$sql .=	$insert_str ;	
		if(!empty($insert_values)){
			$this->logger->debug("sql query fired is ") ;
			$db->insert($sql);
		}else{
			$this->logger->debug("insert values are empty") ;
		}

	}

	public function removeSecondaryTemplate($params,$message_id,$module="OUTBOUND",$msg_type="EMAIL"){
		$this->logger->debug("the params passed are : ".print_r($params,true)) ;
		$db = new Dbase('msging');
		if(!empty($params['secondary_template_id']) && $params['secondary_template_id']>0){
			$secondary_template_id = $params['secondary_template_id'] ;
			$sql = "UPDATE `msging`.`msg_secondary_templates` SET is_deleted=1 WHERE id= $secondary_template_id" ;			
			$db->update($sql) ;
		}else{
			$this->logger->debug("secondary_template_id not passed") ;
		}
		
	}
	
	public function getCampaignDailyDigestData(){
		
		$daily_digest  = array();
		$campaigns_sent_today = $this->getCampaignDetailsSentToday();
		$campaigns_scheduled_tomo = $this->getCampaignDetailsScheduledTomo();
		$expiring_campaigns = $this->getExpiringCampaigns();
		
		$this->logger->debug("campaigns_sent_today " .print_r($campaigns_sent_today, true));
		$this->logger->debug("campaigns_scheduled_tomo " .print_r($campaigns_scheduled_tomo, true));
		$this->logger->debug("expiring_campaigns " .print_r($expiring_campaigns,true));
		
		foreach ($campaigns_sent_today as $org_id => $campaigns ){
			if(!$daily_digest[$org_id]){
				$daily_digest[$org_id] = array();
			}
			if(!$daily_digest[$org_id]['campaigns_sent_today']){
				$daily_digest[$org_id]['campaigns_sent_today'] = array();
			}
			array_push( $daily_digest[$org_id]['campaigns_sent_today'], $campaigns);
		}


		foreach ($campaigns_scheduled_tomo as $org_id => $campaigns ){
			if(!$daily_digest[$org_id]){
				$daily_digest[$org_id] = array();
			}
			if(!$daily_digest[$org_id]['campaigns_scheduled_tomo']){
				$daily_digest[$org_id]['campaigns_scheduled_tomo'] = array();
			}
			array_push( $daily_digest[$org_id]['campaigns_scheduled_tomo'], $campaigns);
		}

		foreach ($expiring_campaigns as $org_id => $campaigns ){
			if(!$daily_digest[$org_id]){
				$daily_digest[$org_id] = array();
			}
			if(!$daily_digest[$org_id]['expiring_campaigns']){
				$daily_digest[$org_id]['expiring_campaigns'] = array();
			}
			array_push( $daily_digest[$org_id]['expiring_campaigns'], $campaigns);
		}
		
		$this->logger->debug("getCampaignDailyDigestData end : ".print_r($daily_digest, true));
		return $daily_digest;
	}

	private function getCampaignDetailsSentToday(){
		
		$this->logger->debug("inside campaign details sent today");
		$C_veneno_handler = new VenenoDetailsHandler();

		$start_time = date("Y-m-d");
		$end_time = date('Y-m-d',  strtotime(' +1 day'));

		$result = $C_veneno_handler->getCampaignSentInfo($start_time, $end_time );
		$this->logger->debug("campaign sent today count : ".count($result));
		$this->logger->debug("campaign sent today ".print_r($result,true));
		
		$data = array();

		$org_promotion_ids = array();
		$org_voucher_series = array();

		foreach ($result as $row ){
			$r = array();
			$org_id = $row['org_id'];
			$r['org_id'] = $org_id;
			$campaign = $this->campaign_model_extension->getCampaignByIdAndOrdId($row['campaign_id'], $org_id);
			$r['campaign_name'] =  $campaign[0]['name'];
			$r['campaign_id'] = $row['campaign_id'];
			$r['message'] = $row['message'];
			$r['targeted_list_size'] = $row['recipient_list_count'];
			$r['start_date'] = $row['campaign_start_time'];
			$r['end_date'] = $row['campaign_end_time'];
			$r['message_id'] = $row['message_id'];
			$communication_type = $row['communication_type'];
			$guid = $row['guid'];
			$r['sender_info'] = "";

			$this->logger->debug("delivery count:".$row['delivered_count']);
			if( $row['delivered_count'] != 0 && $row['recipient_list_count'] != 0)
			{
				$delivery_rate_percent = ($row['delivered_count']/$row['recipient_list_count'])*100;
				$this->logger->debug("delivery ratio:".$delivery_rate_percent);
				$delivery_rate = $delivery_rate_percent."%";
			}
			else{
				$delivery_rate = 0;
				$delivery_rate = round($delivery_rate,2)."%";
			}
			$this->logger->debug("delivery rate final:".$delivery_rate);
			$r['delivery_rate'] = $delivery_rate;

			$message_properties = json_decode($row['message_properties'],true);
			$this->logger->debug("messgae properties object:".print_r($message_properties,true));	
			//Sender details : Name and Mobile 	
			$this->logger->debug("communication type:".$communication_type);
			$sender_info = array();
			$sender_html = "";
			$this->logger->debug("guid:".$guid);

			$msg_default_arguments = $this->getDefaultArgumentsByGuIdMsgIdCampaignId($org_id, $row['campaign_id'], $guid);
			$this->logger->debug("msging_default_arguments:".print_r($msg_default_arguments,true));
			$msging_default_arguments = json_decode($msg_default_arguments['0']['default_arguments'],true);
			$this->logger->debug("msging_default_arguments final:".print_r($msging_default_arguments,true));

			if( $communication_type == 'SMS' )
			{
				$sender_info['Name'] = $message_properties['sender_gsm'];
				$sender_info['Mobile'] = $message_properties['sender_cdma'];	
				
			}
			else if ( $communication_type == 'EMAIL')
			{
				$sender_info['Sender Label'] = $message_properties['sender_label'];
				$sender_info['Sender Email ID'] = $message_properties['sender_email'];
				$sender_info['Reply to Label'] = $msging_default_arguments['replyto_label'];
				$sender_info['Reply to Email ID'] = $message_properties['sender_reply_to'];		
			}

			$this->logger->debug("sender info array:".print_r($sender_info,true));
			if ( count($sender_info) > 0 )
			{
				$sender_html = "<ul>";
				foreach( $sender_info as $key => $value )
				{
					$sender_html .= "<li>$key: $value</li>";
				}
				$sender_html .= "</ul>";
			}
			$this->logger->debug("sender html:".$sender_html);
			$r['sender_info'] = $sender_html;


			$voucher_series_id = $message_properties['voucher_series'];
			
			if(!$voucher_series_id || $voucher_series_id == -1){
				$r['voucher_series_id'] = -1;
				$r['coupons_issued'] = 0;
			}else{
				$r['voucher_series_id'] = $voucher_series_id;
				if ( strpos( $r['message'], '{{voucher}}') !== false  )
				{
					$r['coupons_issued'] = $row['message_sent_count'];	
				}
				else
				{
					$r['coupons_issued'] = 0;
				}
				
				if(!$org_voucher_series[$org_id]){
					$org_voucher_series[$org_id] =array();
				}
				array_push($org_voucher_series[$org_id], $voucher_series_id);
			}
			
			$default_arguments = json_decode($row['default_arguments'], true);
			$promotion_id =  $default_arguments['promotion_id'];
			$r['promotion_id'] = $promotion_id > 0 ? $promotion_id : -1;
			$this->logger->debug("promotion id : " .$promotion_id);
				
			if( $r['promotion_id'] > 0 ){
				if(!$org_promotion_ids[$org_id]){
					$org_promotion_ids[$org_id] = array();
				}
				array_push( $org_promotion_ids[$org_id], $r['promotion_id'] );
			}
			
			array_push( $campaign_ids, $row['campaign_id']);
			
			$r['communication_type'] = $row['communication_type'];
			$r['points_issued'] = 0; 
			$r['coupon_series_name'] = ""; 
				
			if( !$data[$org_id] ){
				$data[$org_id] = array();
			}
			$this->logger->debug("pushing " .print_r($r,  true));
			$this->logger->debug("before data " .print_r($data,  true));
			array_push($data[$org_id], $r);
			$this->logger->debug("after data " .print_r($data,  true));
				
		}

		$org_voucher_series_pair = array();
		foreach ($org_voucher_series as $org_id => $voucher_series_ids ){
			foreach ($voucher_series_ids as $voucher_series_id )
				array_push( $org_voucher_series_pair, "(". $org_id.",".$voucher_series_id.")");
		}
		
		if(count($org_voucher_series_pair)<=0){
			return $data;
		}
		
		$org_vs_voucher_series_id_csv = implode(",", $org_voucher_series_pair);
		if( count( $org_voucher_series ) > 0 ){
			$voucher_series = $this->campaign_model_extension->getVoucherSeriesNamesByIds($org_vs_voucher_series_id_csv);
		}

		$this->logger->debug("org promotion ids :".print_r($org_promotion_ids,  true));
		
		$promotions = array();
		if( count( $org_promotion_ids ) > 0 ){

			$promotions_data = $this->peb->getPromotionData($org_promotion_ids, $this->convertDatetoMillis($start_time),
					$this->convertDatetoMillis($end_time));

			$this->logger->debug("promotions : ".print_r($promotions_data, true));
			foreach ($promotions_data as $promotion_data){

				$org_id = $promotion_data->orgId;
				if( !$promotions[$org_id] ){
					$promotions[$org_id] = array();
				}

				$promotions[$org_id][$promotion_data->id] = $promotion_data->totalAllocatedPointsInDateRange;
			}
		}

		$this->logger->debug("promotions :".print_r($promotions,true));
		
		
		foreach ($data as  $org_id => &$campaigns ){
	
			$this->logger->debug("sent today org:".$org_id." campaign :".print_r($campaign,true));
			foreach ($campaigns as  &$campaign ){
				
				$campaign['points_issued'] = $promotions[$org_id][$campaign['promotion_id']] ? $promotions[$org_id][$campaign['promotion_id']] : 0;
				$campaign['coupon_series_name'] = $voucher_series[$org_id][$campaign['voucher_series_id']] ? $voucher_series[$org_id][$campaign['voucher_series_id']] : "-";
			}
		}

		$this->logger->debug("campaign details sent today end : ".print_r($data, true));
		return $data;

	}

	private function getCampaignDetailsScheduledTomo(){
		
		global $currentorg;
		$this->logger->debug("inside campaign details scheculed tomorrow");

		$start_time = date('Y-m-d',  strtotime(' +1 day'));
		$end_time = date('Y-m-d',  strtotime(' +2 day'));

		$result = $this->campaign_model_extension->getOutboundCampaignDetailsScheduled($start_time, $end_time);
		$this->logger->debug("campaign to be sent tomo count : ".count($result));
		
		$sch_client = new CronSchedulerThriftClient();
		
		$start_date = $this->convertDatetoMillis($start_time);
		$end_date = $this->convertDatetoMillis($end_time);
		$tasks = $sch_client->getTaskIdsSchedueledBetweenDates($start_date, $end_date);
		
		$this->logger->debug("campaign tasks ::".print_r($tasks, true));
		if( count($tasks) > 0 ){
			$task_id_csv = implode(",", $tasks);
			$campaign_reminders = $this->campaign_model_extension->loadMessagesByTaskIds($task_id_csv);
			$org_messages = array();
			foreach ($campaign_reminders as $campaign_reminder){
				$org_message = "(".$campaign_reminder['org_id'].",".$campaign_reminder['refrence_id'].")";
				array_push($org_messages, $org_message);
			}
			$this->logger->debug("org_messages : ".print_r($org_messages, true));
			
			if(count($org_messages)> 0){
				$recurring_campaigns = $this->campaign_model_extension->getRecurringOutboundCampaignDetailByMessageIdAndOrgId($org_messages);
			}
			
			foreach ($recurring_campaigns as &$recurring_campaign){
				foreach ($campaign_reminders as $campaign_reminder){
					if($campaign_reminder['org_id'] == $recurring_campaign['org_id'] &&
						$campaign_reminder['refrence_id'] == $recurring_campaign['message_id'] ){
						$recurring_campaign['scheduled_time'] = $this->getCronFrequencyInStr($campaign_reminder['frequency']);
					}
				}	
			}
			
			$this->logger->debug("recurring campaings to be run tomo :".print_r($recurring_campaigns, true));
			$result = array_merge($result, $recurring_campaigns);
		}
		
		$data = array();
		foreach ($result as $row){
			$r = array();
			$org_id = $row['org_id'];
			$currentorg = new OrgProfile($org_id);
			$campaign = $this->campaign_model_extension->getCampaignByIdAndOrdId($row['campaign_id'], $org_id);
			$r['campaign_name'] =  $campaign[0]['name'];
			$r['campaign_id'] = $row['campaign_id'];
			$message_id = $row['message_id'];
			$params = json_decode($row['params'], true);
			$type  = str_replace("_REMINDER", "", $row['type']);
			$r['message'] = $params ? ( $type == "EMAIL" ? $params['subject'] : $params['message'] ) : "";
			$r['scheduled_send_time'] = $row['scheduled_time'];
			$r['org_id'] = $org_id;
			$r['communication_type'] = $type;
			$r['sender_info'] = ""; 

			$campaign_end_date = $campaign[0]['end_date'];
			$campaign_end_date_timestamp = strtotime( $campaign_end_date );
			$current_date_timestamp = time( );
			if( $current_date_timestamp > $campaign_end_date_timestamp ){
				$this->logger->debug("campaign ".$row['campaign_id']." expired on $campaign_end_date." );
				continue;
			}
			
			$campaign_start_date = $campaign[0]['start_date'];
			$campaign_start_date_timestamp = strtotime( $campaign_start_date );
			$current_start_timestamp = time( );
			if( $current_start_timestamp < $campaign_start_date_timestamp ){
				$this->logger->debug("campaign ".$row['campaign_id']." campaign has not started yet  $campaign_end_date." );
				continue;
			}
			
			$default_arguments = json_decode($row['default_arguments'], true);
			
			$r['pending_actions'] = ""; 

			$obj = new EntityHealthTracker();
			$daily_digest_result = $obj->process( 'CAMPAIGN_NOTIFY' , $fin = array('type' => 'precheck','queue_id' => $message_id, 'is_daily_digest' => '1' , 'org_id' => $org_id ) );

			$this->logger->debug("daily digest pending actions array:".print_r($daily_digest_result,true));
			if( is_null($daily_digest_result) || empty($daily_digest_result) || !array_key_exists("precheck", $daily_digest_result ) )
			{
				$r['pending_actions'] = "";
			}
			else
			{
				$pending_actions = implode(",", $daily_digest_result['precheck']);
				$r['pending_actions'] = $pending_actions;
			}
			$this->logger->debug("final pending actions:".$r['pending_actions']);

			//Sender details : Name and Mobile 	
			$this->logger->debug("message type:".$type);
			$sender_info = array();
			$sender_html = "";
			$this->logger->debug("default_arguments object:".print_r($default_arguments,true));
			if( $type == 'SMS' )
			{
				$sender_info['Name'] = $default_arguments['sender_gsm'];
				$sender_info['Mobile'] = $default_arguments['sender_cdma'];	
				
			}
			else if ( $type == 'EMAIL')
			{
				$sender_info['Sender Label'] = $default_arguments['sender_label'];
				$sender_info['Sender Email ID'] = $default_arguments['sender_email'];
				$sender_info['Reply to label'] = $default_arguments['replyto_label'];
				$sender_info['Reply to Email ID'] = $default_arguments['sender_reply_to'];		
			}

			$this->logger->debug("sender info array :".print_r($sender_info,true));

			if ( count($sender_info) > 0 )
			{
				$sender_html = "<ul>";
				foreach( $sender_info as $key => $value )
				{
					$sender_html .= "<li>$key: $value</li>";
				}
				$sender_html .= "</ul>";
			}
			$this->logger->debug("sender html :".$sender_html);
			$r['sender_info'] = $sender_html;

			array_push($org_campaigns, array("org_id" => $org_id, "campaign_id" => $row['campaign_id']));
			if( !$data[$org_id] ){
				$data[$org_id] = array();
			}

			array_push($data[$org_id], $r);
		}
		
		$this->logger->debug("campaign details scheculed tomorrow end : ".print_r($data, true));
		return $data;
	}

	private function getExpiringCampaigns(){
		
		$this->logger->debug("inside expiring campaign details");
		$data = array();
		$from_date = date('Y-m-d',  strtotime(' +1 day'));
		$to_date = date('Y-m-d',  strtotime(' +8 day'));
		$campaigns = $this->campaign_model_extension->getExpiringCampaigns($from_date, $to_date);
		
		$this->logger->debug("expiring campaign count : ".count($campaigns));

		if( !$campaigns  ){
			return;
		}
			
		$org_campaigns = array();
		$org_voucher_series = array();
		foreach ( $campaigns as $c ){
			$campaign = array();
			$org_id = $c['org_id'];
			$campaign_id = $c['id'];
				
			$campaign['campaign_id'] = $campaign_id;
			$campaign['campaign_name'] = $c['name'];
			$campaign['expiry_date'] = $c['end_date'];
			$campaign['age'] = $c['age'];
			$campaign['org_id'] = $org_id;
			if(!$c['voucher_series_id'] || $c['voucher_series_id'] == -1){
				$c['voucher_series_id'] = -1;
			}else{
				$campaign['voucher_series_id'] = $c['voucher_series_id'];
				if(!$org_voucher_series[$org_id]){
					$org_voucher_series[$org_id] =array();
				}
				array_push($org_voucher_series[$org_id], $c['voucher_series_id']);
			}

			$campaign['coupon_series_name'] = "";
			$campaign['coupons_issued'] = "";
			$campaign['coupons_redeemed'] = "";
			$campaign['points_issued'] = ""; // TODO

			
			$org_campaign_str = "(" .$org_id. "," . $campaign_id. ")";
			array_push($org_campaigns, $org_campaign_str);
			$data[$org_id][$campaign_id] = $campaign;
		}

		$org_voucher_series_pair = array();
		foreach ($org_voucher_series as $org_id => $voucher_series_ids ){
			foreach ($voucher_series_ids as $voucher_series_id )
			array_push( $org_voucher_series_pair, "(". $org_id.",".$voucher_series_id.")");
		}
		
		$this->logger->debug("org coucher series pair ".print_r($org_voucher_series_pair,true));
		
		if(count($org_voucher_series_pair)<=0){
			return $data;
		}

		$org_vs_voucher_series_id_csv = implode(",", $org_voucher_series_pair);
		
		$this->logger->debug("org_vs_voucher_series_id_csv ".print_r($org_vs_voucher_series_id_csv,true));
		
		$coupon_series = $this->campaign_model_extension->getVoucherSeriesNamesByIds($org_vs_voucher_series_id_csv);
		$coupons_issued = $this->campaign_model_extension->getVoucherIssuedCount($org_vs_voucher_series_id_csv);
		$coupons_redeemed = $this->campaign_model_extension->getVoucherRedeemedCount($org_vs_voucher_series_id_csv);
		
		//======
		$this->logger->debug("org campaigns count".count($org_campaigns));
		$campaign_points = array();
		if(count($org_campaigns) > 0 ){

			$start_time = "1990-01-01";
			$end_time = "2050-01-01";
			$org_promotion_ids = array();
			$messages =  $this->campaign_model_extension->getMessagesByCampaignId($org_campaigns);
			$org_campaign_message_promotion_map = array();
			foreach ($messages as $row){
				$org_id = $row['org_id'];
				$campaign_id = $row['campaign_id'];
				$message_id = $row['id'];
				$default_arguments = json_decode($row['default_arguments'], true);
				$promotion_id =  $default_arguments['promotion_id'];
			
				if( $promotion_id > 0 ){
					
					if(!$org_campaign_message_promotion_map[$org_id]){
						$org_campaign_message_promotion_map[$org_id] = array();
					}
						
					if(!$org_campaign_message_promotion_map[$org_id][$campaign_id]){
						$org_campaign_message_promotion_map[$org_id][$campaign_id] = array();
					}
						
					if(!$org_campaign_message_promotion_map[$org_id][$campaign_id][$message_id]){
						$org_campaign_message_promotion_map[$org_id][$campaign_id][$message_id] = array();
					}
			
					if(!$org_promotion_ids[$org_id]){
						$org_promotion_ids[$org_id] = array();
					}
			
					array_push($org_campaign_message_promotion_map[$org_id][$campaign_id][$message_id], $promotion_id);
					array_push( $org_promotion_ids[$org_id], $promotion_id );
				}
			}
			
			$this->logger->debug("org promotion ids :".print_r($org_promotion_ids,  true));
			$this->logger->debug("org_campaign_message_promotion_map :".
					print_r($org_campaign_message_promotion_map,  true));
			
			$promotions = array();
			if( count( $org_promotion_ids ) > 0 ){
			
				$promotions_data = $this->peb->getPromotionData($org_promotion_ids, $this->convertDatetoMillis($start_time),
						$this->convertDatetoMillis($end_time));
			
				$this->logger->debug("promotions : ".print_r($promotions_data, true));
				foreach ($promotions_data as $promotion_data){
			
					$org_id = $promotion_data->orgId;
					if( !$promotions[$org_id] ){
						$promotions[$org_id] = array();
					}
			
					$promotions[$org_id][$promotion_data->id] = $promotion_data->totalAllocatedPointsInDateRange;
				}
			}
			
			$this->logger->debug("promotion points :".print_r($promotions, true));
			$this->logger->debug("org_campaign_message_promotion_map".print_r($org_campaign_message_promotion_map, true));
			foreach ($org_campaign_message_promotion_map as $org_id => $campaign_message_promotion_map){
				foreach ($campaign_message_promotion_map as $campaign_id => $message_promotion_map){
					foreach ($message_promotion_map as $message_id => $promotion_ids){
						foreach ($promotion_ids as $promotion_id){
							$this->logger->debug("org_id : $org_id, campaing_id $campaign_id, message_id : $message_id, promotion_id : $promotion_id ".
									"promotion points : ".$promotions[$org_id][$promotion_id]);
							if(!$campaign_points[$org_id]){
								$campaign_points[$org_id] = array();
								$campaign_points[$org_id][$campaign_id] = 0;
							}
							$campaign_points[$org_id][$campaign_id] += $promotions[$org_id][$promotion_id] ?
									$promotions[$org_id][$promotion_id] : 0;
						}
					}
				}
			}
				
		}
		
		$this->logger->debug("campaings points :".print_r($campaign_points, true));
		//======

		foreach ($data as $org_id => &$campaigns){
			foreach ($campaigns as &$campaign) {
				$campaign['coupon_series_name'] = $coupon_series[$org_id][$campaign['voucher_series_id']] ? 
												$coupon_series[$org_id][$campaign['voucher_series_id']] : "-";
				$campaign['coupons_issued'] = $coupons_issued[$org_id][$campaign['voucher_series_id']] ?
												$coupons_issued[$org_id][$campaign['voucher_series_id']] : 0;
				$campaign['coupons_redeemed'] = $coupons_redeemed[$org_id][$campaign['voucher_series_id']] ?
												$coupons_redeemed[$org_id][$campaign['voucher_series_id']] : 0;
				$campaign['points_issued'] = $campaign_points[$org_id][$campaign['campaign_id']] ?
												$campaign_points[$org_id][$campaign['campaign_id']] : 0;
			}
		}
		
		$this->logger->debug("expiring campaign details end :".print_r($data, true));

		return $data;
	}
	
	public function updatePrecheckProcessingLog($id, $org_id, $campaign_id, $message_id, $status, $error,
    		$start_date, $end_date){
		return $this->campaign_model_extension->updatePrecheckProcessingLog($id, $org_id, $campaign_id, $message_id, $status, $error,
				$start_date, $end_date);
	}
	
	public function getPrecheckStatus($org_id, $campaign_id, $message_id, $start_date){
		return $this->campaign_model_extension->getPrecheckStatus($org_id, $campaign_id, $message_id, $start_date);
	}
	
	private function getCronFrequencyInStr( $frequency ){
		include_once "helper/CronMgr.php";
		$cron_mgr = new CronMgr();
		$frequency_array = explode(' ',$frequency);
		$cron_minutes = $frequency_array[0];
		$cron_hours = $frequency_array[1];
		// modify time w.r.t. current org time zone
		$changed_time = Util::convertTimeToCurrentOrgTimeZone("$cron_hours:$cron_minutes");
		$this->logger->debug("converted time:".$changed_time);
		$frequency_array[0] = date( 'i' , strtotime( $changed_time ) );
		$frequency_array[1] = date( 'H' , strtotime( $changed_time) );
		$converted_frequency = implode(' ',$frequency_array);
		$read_frequency	= $cron_mgr->getFrequencyExplainationNew( $converted_frequency );
		$this->logger->debug("frequency explanation:".$read_frequency);
		$final_schedule_string = str_replace("*th","All ",$read_frequency);
		$this->logger->debug("final frequency explanation:".$final_schedule_string);
		return $final_schedule_string;
		
	}

	public function getDefaultArgumentsByGuIdMsgIdCampaignId($org_id, $campaign_id, $guid)
	{
		return $this->campaign_model_extension->getMessageDefaultArgumentsByGuid($org_id, $campaign_id, $guid);
	}
	//updating approve bulk msg in msg queue

	public function updateBulkMsgApproveDetails($msg_id){
		$this->logger->debug("updateBulkMsgApproveDetails:".$msg_id);
		 $C_bulk_message = new BulkMessage();
		 $C_bulk_message->load( $msg_id );
		 $C_bulk_message->setApproved( 0 );
		 $C_bulk_message->setApprovedBy( 0 );
		 $C_bulk_message->update( $msg_id );
	}
}

?>
