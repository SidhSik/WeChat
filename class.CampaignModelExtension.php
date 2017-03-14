<?php

include_once 'helper/Timer.php';

include_once 'base_model/campaigns/class.CampaignBase.php';
include_once 'base_model/campaigns/class.AudienceGroup.php';
include_once 'model_extension/campaigns/class.CampaignGroupModelExtension.php';
include_once 'helper/memory_joiner/impl/MemoryJoinerFactory.php';
include_once 'helper/memory_joiner/impl/MemoryJoinerType.php';
include_once 'helper/ShardedDbase';
/**
 *
 * @author Ankit Govil
 *

 *This class extends the Audience Group Model
 *
 */
class CampaignModelExtension extends CampaignBaseModel{

	private $C_group_detail;
	private $AudienceGroup;
	private $database_users;
	private $database_msging;
	private $database_camp;
	private $current_org_id;
	private $current_user_id;
	private $database_masters;
	private $timer_insert;
	private $timer_update;
	private $temp_table_name;
	private $campaign_base_model;
	private $memory_joiner;
	
	public $database_conn;
	
	
	/**
	 * CONSTRUCTOR
	 */
	public function __construct( ){

		global $currentorg, $currentuser;
		
		parent::CampaignBaseModel();
		
		$this->AudienceGroup = new AudienceGroupModel();
		$this->timer_insert = new Timer( "timer_insert" );
		$this->timer_update = new Timer( "timer_update" );
		
		$this->C_group_detail = new CampaignGroupModelExtension( );
		$this->current_org_id = $currentorg->org_id;
		$this->current_user_id = $currentuser->user_id;

		//apply testing bit in here later on
		$this->database_msging = 'msging';
		$this->database_camp = 'campaigns';
		$this->database_users = 'user_management';
		$this->database_masters = 'masters';
		
		$this->database_conn = $this->database;
		$this->memory_joiner = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ORG_ENTITY);
	}
	
	
	/*
	 * loyalty user details based on user id
	 */
	
	public function getLoyaltyDetailsForUserID($user_id){

		
		return $this->database->query_firstrow("SELECT * 
												FROM `$this->database_users`.`loyalty` WHERE publisher_id = $this->current_org_id 
												AND user_id = $user_id");
	}

	public function getAllCampainRunningStatus(){
		
		$sql = "
				SELECT 
					SUM( CASE WHEN ( end_date >= DATE( NOW() ) ) THEN 1 ELSE 0 END ) AS active,
					SUM( CASE WHEN ( end_date < DATE( NOW() ) ) THEN 1 ELSE 0 END ) AS inactive,
					SUM( CASE WHEN ( type = 'outbound' ) THEN 1 ELSE 0 END ) AS outbound_campaigns,
					SUM( CASE WHEN ( type = 'action' ) THEN 1 ELSE 0 END ) AS action_campaigns
				FROM campaigns_base
				WHERE org_id = $this->current_org_id AND type IN ( 'outbound', 'action', 'referral' )
				";	
		
		return $this->database->query_firstrow( $sql );
	}
	
	public function getCampainRunningStatus( $campaign_type ){
		
		$sql = "
				SELECT 
					SUM( CASE WHEN ( end_date >= DATE( NOW() ) AND start_date <= DATE( NOW() ) ) THEN 1 ELSE 0 END ) AS active,
					SUM( CASE WHEN ( end_date < DATE( NOW() ) ) THEN 1 ELSE 0 END ) AS inactive,
					SUM( CASE WHEN ( start_date > DATE( NOW() ) ) THEN 1 ELSE 0 END ) AS forthcoming
				FROM campaigns_base
				WHERE org_id = $this->current_org_id AND type IN ( $campaign_type )
				";	
		
		return $this->database->query_firstrow( $sql );
	}
	
	/**
	 * 
	 * @param $vch_series_id
	 */
	public function getCampaignNameByVchId ( $vch_series_id, $org_id ){
		$sql = "SELECT `id`, `name`, `type`, `voucher_series_id` FROM campaigns_base WHERE `voucher_series_id` LIKE '%$vch_series_id%' 
				AND `org_id`= '$org_id' ";

		return $this->database->query( $sql );
		
	}
	
	/*
	 *get Details of the user
	 *
	 */
	
	public function getUserDetailsByGroup( $group_id, $channel_type ){

		$C_campaign_group_handler = new CampaignGroupBucketHandler( $group_id );
		return $C_campaign_group_handler->getPreviewList( $channel_type );
	}
	

	/**
	 * 
	 * @param $vch_series_id: voucher series for which the coupons redeemed/issued are to be obtained
	 */
	function getVoucherSeriesDetailsByOrg( $vch_series_id ){
		
		$sql = "SELECT description, num_issued, num_redeemed FROM `$this->database_camp`.voucher_series 
				WHERE org_id = $this->current_org_id  
				AND `id` = $vch_series_id ";
		
		return $this->database->query_firstrow( $sql );
	}
	
	/**
	 * 
	 * @param csv of $voucher_series_ids
	 * @return resultobject
	 */
	function getVoucherSeriesCodeById( $voucher_series_ids ){
	
		if(is_array($voucher_series_ids))
			$voucher_series_ids=implode(",",$voucher_series_ids);
		
		$voucher_codes="";
		$sql = "SELECT id,series_code FROM `$this->database_camp`.voucher_series
		WHERE org_id = $this->current_org_id
		AND `id` IN ($voucher_series_ids) ";
		
		 
		$res=$this->database->query( $sql );
		
		foreach($res as $row)
		{
			$voucher_codes.=$row['series_code'].",";
		}
		return rtrim($voucher_codes,",");
	}
	
	/**
	 * @param unknown_type $group_ids: string csv values of groupIds
	 */
	public function getGroupDetailsbyGroupIds( $group_ids ){
		
		return CampaignGroupModelExtension::getByIds( $group_ids );			
	}
		
	/**
	 * 
	 * @param $campaignIds
	 * @param $type : @deprecated
	 */
	public function getCampaignGroupsByCampaignIds( $campaign_ids, $type = 'query' ){

		return CampaignGroupModelExtension::getAllByCampaignId( $campaign_ids, $this->current_org_id );
	}
	
	/**
	 * Returns the count for the group
	 * 
	 * @param unknown_type $group_id
	 */
	public function getCountForGroup( $group_id ){
		
		$C_campaign_group_model_extension = new CampaignGroupModelExtension();
		$C_campaign_group_model_extension->load( $group_id );
		return $C_campaign_group_model_extension->getCustomerCount();
	}

	
	public function updateGroupMetaInfo( $group_id, $parent_group_id = false, $percentage = false ){

		$C_campaign_group_model_extension = new CampaignGroupModelExtension();
		$C_campaign_group_model_extension->updateGroupMetaInfo( $group_id, $parent_group_id, $percentage );
	}
	
	
	public function getTypesOfCampaign( $org_id ){
		
		$sql = "
			SELECT `type`, `id` 
			FROM `campaigns`.`campaigns_base` 
			WHERE org_id = $org_id	
			";
		
		return $this->database->query( $sql );
	}
	
	/**
	 * 
	 * @param unknown_type $org_id
	 * @param unknown_type $where_filter
	 * @param unknown_type $search_filter
	 * @return Ambigous <multitype:, boolean>
	 */
	public function getDataForHomePage( $org_id, $where_filter, $search_filter , $limit_filter = false ){
		
		$groups = GroupDetailModel::getAllByOrgID($org_id, array('TEST'));
		$campaign_details = array();
		
		//getting the customer groups for all campaign ids
		foreach( $groups as $group_details ){
			
			$campaign_id = $group_details['campaign_id'];
			if( !key_exists( $campaign_id, $campaign_details ) ){
				
				$campaign_details[$campaign_id]['audience'] = 
					$group_details['group_label'];
				
				$campaign_details[$campaign_id]['audience_count'] = 
					( $group_details['customer_count'] ) ? ( $group_details['customer_count'] ) : ( 0 );
				
				$campaign_details[$campaign_id]['audience_overall'] =
					( $group_details['customer_count'] ) ? ( $group_details['customer_count'] ) : ( 0 );
				
				continue;
			}
			
			$campaign_details[$campaign_id]['audience'] .= ",".
				$group_details['group_label'];
			$campaign_details[$campaign_id]['audience_count'] .= ",". 
				 $group_details['customer_count'];
			$campaign_details[$campaign_id]['audience_overall'] +=
				 $group_details['customer_count'];
		}
		
		//if search filter is applied donot set limit. 
		if( $search_filter )
			$limit_filter = '';
		
		//Extract campaign and voucher data for all campaigns
		$sql = "
				SELECT 	
						cb.`id` ,				
						cb.`id` AS campaign_id , 
						cb.`name` AS campaign_name , 
       					cb.`type` AS campaign_type ,
						cb.`start_date` , 
						cb.`end_date` ,         
       					vs.`id` AS first_voucher_series_id,
        				vs.`description` ,        			
        				COUNT(vs.`id`) AS number_of_vs_attached,                                                
						SUM(vs.`num_issued`) AS total_issued,                                              
						SUM(vs.`num_redeemed`) AS total_redeemed 
										           
				FROM  `campaigns`.`campaigns_base` AS cb
				LEFT OUTER JOIN `campaigns`.`voucher_series` AS vs ON cb.`id` = vs.`campaign_id`           
				WHERE cb.`org_id` = $org_id $where_filter $search_filter
				GROUP BY cb.`id` ORDER BY end_date DESC $limit_filter
				";
		
		$result = $this->database->query( $sql );
		//get the customer lists in case of outbound campaigns
		for( $i = 0; $i < count($result) ; $i++ ){
			
			if($result[$i]['campaign_type'] == "outbound" ){

				$campaign_id = $result[$i]['campaign_id'];
			
				$result[$i]['audience'] = 
					$campaign_details[$campaign_id]['audience'];
			
				$result[$i]['audience_count'] = 
					$campaign_details[$campaign_id]['audience_count'];
			
				$result[$i]['audience_overall'] =
					$campaign_details[$campaign_id]['audience_overall'];
			}
			
		}
		
		$this->logger->debug( "CAMPAIGN home table homepage result".$sql." the result is".print_r($result, true) );
		return $result;
	}
	
	/*
	 * referral group by campaign id
	 */
	public function getReferralsGroupsByCampaignIds($campaign_id, $type = 'query'){
		
		$sql = "SELECT `group_id` 
				FROM `referral_groups`
				WHERE `campaign_id` = '$campaign_id' ";
		
		$group_ids_array = $this->database->query($sql);
		$group_ids = array();
				
		foreach($group_ids_array as $gid)
			array_push($group_ids, $gid['group_id']);
		
		$group_ids = Util::joinForSql($group_ids);
		
		return $this->getReferralsGroupsByGroupId($group_ids, $type);
	}
	
	/*
	 * referral groups details by group ids
	 */
	public function getReferralsGroupsByGroupId($group_ids, $type = 'query'){
	 	
		$sql = "SELECT `rg`.`group_id`,`rg`.`group_label`,count(*) AS customer_count " .
		" FROM `referral_group_users` AS `rgu`" .
		" JOIN `referral_groups` `rg` ON `rg`.`group_id` = `rgu`.`group_id`" .
		" WHERE `rg`.`group_id` IN ($group_ids) " .
		" GROUP BY `rg`.`group_id`";

		if($type == 'query')
			$response = $this->database->query($sql);
		elseif($type == 'query_table')
			$response = $this->database->query_table($sql);
		elseif($type == 'query_firstrow')
			$response = $this->database->query_firstrow($sql);
		
		return $response;
	 }	
	
	/**
	 * insert into messaging default values
	 * @param $values
	 */
	function insertMsgingDefaultValues($values){
		
		
		$sql = "
			INSERT INTO `msging_default_values`(`org_id`,`field_name`,`field_value`)
			VALUES $values
			ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`)
		";
		
		return $this->database->update($sql);
	}
	 
	 
	 /**
	  * 
	  * @param $msg_id: message id 
	  */
	 public function getMessageScheduleType( $msg_id ){
	 	
	 	$sql = "SELECT scheduled_type 
	 			FROM `$this->database_msging`.`message_queue` AS `mq` 
	 			WHERE `mq`.`id` = $msg_id";

	 	return $this->database->query_scalar($sql);
	 	
	 }
	 
	 
	/*
	 * queued messages from message queue
	 */
	 public function getQueuedMessages( $org_id, $status, $type){
		
	 	// get out the group id get label for each group separately 
	 	//and construct the response before returning
		$sql = 
			"SELECT  m.`id` as id, cb.`name` as name, 
				m.`group_id`, m.`message_text`, m.`default_arguments`
						
			FROM `$this->database_msging`.`message_queue` m
			JOIN `campaigns`.`campaigns_base` cb ON ( cb.`id` = m.`campaign_id` )
			WHERE m.org_id = $org_id AND `status` = '$status' AND `m`.`type` = '$type' ";
		
		$message_details = $this->database->query( $sql );
		
		$response = array( );
		foreach( $message_details as $message ){
			
			$response = $message;	
			unset( $response['group_id'] );
			$response['group_label'] = $response[$message['group_id']];
		}
		
		return $response;
	}
	
	
	/**
	 * @param $id: message queue id for retriving details
	 */
	public function getQueuedMessageDetailsById( $id ){
		
		$sql = "SELECT 	`id`, `guid`, `campaign_id`, `group_id`, `params`, `default_arguments`, `status`, 
						`Approved` AS `approved` , `type`, `scheduled_type`, `scheduled_on`
						FROM `$this->database_msging`.`message_queue` 
						WHERE `id` = $id";
		
		return $this->database->query_firstrow( $sql );
	}
	
	/*
	 * get the email details based on the id
	 */
	public function getQueuedEmailDetailsById($id){
		
		$sql = "SELECT `campaign_id`, `group_id`, `message_text`,`message_subject`, `template_id`,`default_arguments`, `status` , `params`
						FROM `$this->database_msging`.`message_queue` WHERE `id` = $id";
		
		return $this->database->query_firstrow( $sql );
	}
	

	/*
	 * gives the groups by group id
	 */
	 public function getCampaignGroupsByGroupId( $group_ids, $type = 'query' ){
	 	
	 	return CampaignGroupModelExtension::getByIds( $group_ids );
	 }
	 
	/**
	 * 
	 * @param $org_id
	 * @param $campaign_id
	 * @param $group_id
	 * @param $sent_msg
	 * @param $default_arguments
	 */  
	public function noninsertIntoMessageQueue($org_id, $campaign_id,$group_id,$sent_msg,$default_arguments, $guid){
		
    
		$sent_msg = mysql_escape_string($sent_msg);
    
    
		$default_arguments = mysql_escape_string($default_arguments);
  
		$sql = "INSERT INTO $this->database_msging.`message_queue` 
					  (`guid` ,`org_id`, `campaign_id`, `group_id`, `message_text`,
					  `time`, `default_arguments`, `status`)
					  VALUES
					  (	'$guid', $org_id, $campaign_id, $group_id, '$sent_msg', 
					  NOW(), '$default_arguments', 'OPEN', 'SMS')";
		
		return $this->database->insert( $sql );
	}
	
	/**
	 * 
	 * @param $campaign_id
	 * @param $group_id
	 */
	 
	public function checkSendTimebyId($campaign_id,$group_id){
		
		$sql="select TIMESTAMPDIFF(SECOND,`m`.`sent_date`,now()) AS `t` 
			  FROM `msging`.`bulksms_campaign` AS `m` where `m`.`campaign_id`=".$campaign_id." 
			  AND `m`.`group_id`=".$group_id ." having `t` <= 300";
		
		$check_last_sendtime=$this->databaseb->query( $sql );
		
	}
	
	/*
	 * get the default field value 
	 */
	
	public function getDefaultFieldValue( $org_id ){
		
		
		$sql = "
		
			SELECT `field_name`,`field_value`
			FROM `$this->database_camp`.`msging_default_values`
			WHERE `org_id` = $org_id
		";

		return $this->database->query( $sql );
	}
	
	/*
	 * reduce credits from the organizations
	 */
	
	public function reduceBulkCredits( $org_id, $group_id){
		
		$group_details = $this->getCampaignGroupsByGroupId($group_id,'query_firstrow');
		
		$customer_count = $group_details['customer_count'];
		
		return $this->reduceBulkCreditsByTotalCustomerCount( $org_id, $customer_count );

	}
	
	
	/*
	 * Reduce the customer credits by customer count
	 */
	public function reduceBulkCreditsByTotalCustomerCount( $org_id, $customer_count ){
		
		$C_org_sms_credits = new OrgSmsCreditModel();
		$C_org_sms_credits->load( $org_id );
		
		$bulk_sms_credits = ( $C_org_sms_credits->getBulkSmsCredits() - $customer_count );
		
		$this->logger->debug('@@Reduce Bulk Credits Org ID :'.$org_id.' , From : '.$C_org_sms_credits->getBulkSmsCredits() .' To : '.$bulk_sms_credits );
		
		$this->logger->debug('@@Customer Count : '.$customer_count);
		
		$C_org_sms_credits->setBulkSmsCredits( $bulk_sms_credits );
		
		$C_org_sms_credits->update( $org_id );
		
// 		$sql = "
		
// 			UPDATE `user_management`.`org_sms_credits` AS `o`
// 			SET `bulk_sms_credits` = `bulk_sms_credits` - '$customer_count'
// 			WHERE `org_id` = '$org_id'
// 		";
		
// 		return $this->database->update( $sql );
	}
	
	public function getReminderByReferenceId( $id ){
			
		$sql = " SELECT `id` FROM `$this->database_users`.`reminder` 
					 WHERE `refrence_id` = $id 
					 AND org_id = $this->current_org_id AND reminder_type = 'CAMPAIGN'" ;

		return $this->database->query_scalar( $sql );
		
	}
	
	/**
	 *change status of the message 
	 * @param unknown_type $message_id
	 */
	public function reQueueMessage( $message_id ){
		
		$sql = "UPDATE `$this->database_msging`.`message_queue` 
				SET `status` = 'OPEN'  
				WHERE `id` = $message_id ";
		
		$this->database->update( $sql );
	}
	
	/**
	 * change the approve status of message
	 * @param $message_id
	 */
	public function approveMessage( $message_id , $user_id ){
		
		$sql = "UPDATE `$this->database_msging`.`message_queue` 
				SET `Approved` = 1 , `approved_by` = $user_id ,
					`last_updated_on` = NOW()
				WHERE `id` = $message_id ";

		$this->database->update( $sql );
		
	}
	
	/**
	 * @param $campaign_id
	 * @param $group_id
	 */
	public function getMessageCount( $campaign_id , $group_id){

		$sql = "
				SELECT `numDeliveries`
				FROM `$this->database_msging`.`bulksms_campaign` as bc
				JOIN `$this->database_msging`.outboxes as o ON (o.messageId = bc.msg_id)
				WHERE `bc`.`campaign_id` = $campaign_id AND   `bc`.`group_id` = $group_id
				";
		
		return $this->database->query_scalar( $sql );
	}
	
	/**
	 * get campaign details as who created the particular campaign with some
	 * limited details
	 * @param int $campaign_id
	 */
	//TODO 
	public function getCampaignsMessageDetails($campaign_id, $type , $approved_filter ){
		
		$groups = GroupDetailModel::getAllByCampaignId( $campaign_id );
		$stick_groups = GroupDetailModel::getAllByCampaignId( -20 );//move through typed interface
		
		//TODO : remove code repitition
		$campaign_group_details = array();
		foreach( $groups as $group_details ){
				
			$group_id = $group_details['group_id'];
			$campaign_group_details[$group_id]['group_label'] = 
					$group_details['group_label'];
		}

		foreach( $stick_groups as $group_details ){
		
			$group_id = $group_details['group_id'];
			$campaign_group_details[$group_id]['group_label'] =
			$group_details['group_label'];
		}
		
		$filter = '';
		$campaign_filter = '' ;
		if( $campaign_id != -1){
			$campaign_filter = "AND `cb`.`id` = $campaign_id" ;
		}
		if( $approved_filter ){
			$filter = "AND `mq`.`approved` = 1 ORDER BY `mq`.`last_updated_on` DESC LIMIT 10";
		}
		
		$sql =
				"SELECT
				`mq`.group_id,`mq`.`type`,`mq`.`params`,
				`mq`.`type`, `mq`.`scheduled_on`, `mq`.`scheduled_type`,
				`mq`.`approved`,
				`mq`.`scheduled_by` As Created_by,
				`mq`.`status`, `mq`.`id`
				FROM `$this->database_camp`.campaigns_base AS `cb`
				JOIN `$this->database_msging`.`message_queue` AS mq ON (`mq`.`campaign_id` = `cb`.`id` AND `mq`.`org_id` = `cb`.`org_id` )
				WHERE  `cb`.`org_id` = $this->current_org_id $campaign_filter $filter ";
		
		$result = $this->database->query( $sql );
		
		for( $i = 0; $i < count($result) ; $i++ ){
		
			$group_id = $result[$i]['group_id'];
			$result[$i]['group_label'] =
				$campaign_group_details[$group_id]['group_label'];
		}
		
		$key_map = array( _campaign("Created_by") => "{{ joiner_concat(title,first_name,last_name) }}" );
		$admin_user = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ADMIN_USER );
		$result = $admin_user->prepareReport( $result, $key_map );
		
		return $result;
	}
	
	/**
	 * loads the message details
	 * @param unknown_type $message_id
	 */
	public function messageDetails( $message_id ){
		
		$sql = "SELECT * FROM `$this->database_msging`.`message_queue` WHERE `id` = $message_id ";

		return $this->database->query_firstrow( $sql );
		
	}
	/*
	 * get campaign id by group id
	 */
	public function getCampaignIdByGroupId( $group_id ){
		
		$C_campaign_group_model_extension = new CampaignGroupModelExtension();
		$C_campaign_group_model_extension->load( $group_id );
		
		return $C_campaign_group_model_extension->getCampaignId( );		
	}
	
	/*
	 * updates the status of the queued messages
	 */
	
	public function updateStatusOfQueuedMessages($id, $status){
		
		$sql = "UPDATE `$this->database_msging`.`message_queue`
						SET `status` = '$status'
						WHERE `id` = $id";

		return $this->database->update( $sql );
	}
	
	/**
	 * 
	 * populate bulksms_campaign table tp introduce relation between campaign
	 * and the msg sent to the customers
	 * @param int $campaign_id
	 * @param int $msg_id
	 * 
	 */
	public function addFieldsToBulkSmsCampaign($campaign_id,$msg_id,$group_id,$queue_id){
		
		$this->database->insert("INSERT INTO `msging`.`bulksms_campaign`(`campaign_id`,`org_id`,`msg_id`,`group_id`,`queue_id`) 
					VALUES ('$campaign_id','$this->current_org_id','$msg_id','$group_id','$queue_id')");

	}
	
	/**
	 * update last_msg_sent for group
	 */
	public function updateLastSentDateForGroup($group_id){

		$C_campaign_group_model_extension = new CampaignGroupModelExtension();
		$C_campaign_group_model_extension->load( $group_id );
		$C_campaign_group_model_extension->setLastSentDate( date( 'Y-m-d H:i:s' ) );
		
		return $C_campaign_group_model_extension->update( $group_id );
	}

	/**
	 * get the campaign over view details
	 */
	public function getOverViewDetails( ){
		
		$sql = "
		
			SELECT 
				SUM( CASE WHEN `active` = 0 THEN 1 ELSE 0 END ) AS `inactive`,
				SUM( CASE WHEN `active` = 1 THEN 1 ELSE 0 END ) AS `active`
			FROM `campaigns_base`
			WHERE `org_id` = '$this->current_org_id' 
		";
		
		return $this->database->query( $sql );
	}
	

//////////////////////////////////////    Upload CSV Related Quries ///////////////////////////////////////////////////
	
	/**
	 * Checks If group name already exists
	 *
	 * Judwa yahan allowed nahi hai bidu!!!
	 *
	 * @param unknown_type $group_label
	 */
	public function isGroupNameExists( $group_label, $campaign_id ){

		$is_exists = GroupDetailModel::isGroupNameExists($group_label, $campaign_id);
		
		if( $is_exists )
			throw new Exception( _campaign("List name already exists") );
	}
	
	public function isFtpGroupNameExists( $group_name )
	{
		$sql = " 
				SELECT COUNT( * ) 
				from `campaigns`.`ftp_audience_upload` 
				WHERE org_id = $this->current_org_id AND `group_name` = '$group_name'
				";
		$cnt =  $this->database->query_scalar( $sql );
		
		if( $cnt )
			throw new Exception( _campaign("Group name already exists") );
		
	}
	
	/**
	 * insert group_details 
	 * @param integer $campaign_id
	 * 
	 * @param string $label
	 */
	public function insertGroupDetails( $campaign_id, $label, 
			$type = 'campaign_users', $customer_count = 0, $target_type = 'TEST' ){
		
		
		$campaign_details = $this->getCampaignsBaseById( $campaign_id );
		
		$C_campaign_group_model_extension = new CampaignGroupModelExtension();
		
		$C_campaign_group_model_extension->setOrgId( $this->current_org_id );	
		$C_campaign_group_model_extension->setCampaignId( $campaign_id );
		$C_campaign_group_model_extension->setGroupLabel( $label );
		$C_campaign_group_model_extension->setCreatedDate( date( 'Y-m-d' ) );
		$C_campaign_group_model_extension->setLastSentDate( date( 'Y-m-d' ) );
		$C_campaign_group_model_extension->setType( $type );
		$C_campaign_group_model_extension->setCreatedBy( $this->current_user_id );
		$C_campaign_group_model_extension->setCustomerCount( $customer_count );
		$C_campaign_group_model_extension->setTargetType( $target_type );
		
		return $C_campaign_group_model_extension->insert( );
	}
	
	/**
	 * get campaign_base details by providing campaign_id
	 * @param integer $campaign_id
	 */
	public function getCampaignsBaseById( $campaign_id , $type='query' )
	{
		$sql = "SELECT * FROM `$this->database_camp`.`campaigns_base` WHERE `id` = $campaign_id";
		
		if ($type == 'query_table')
			return $this->database->query_table($sql);
			
		if ($type == 'firstrow')
			 return $this->database->query_firstrow($sql);
			 
		return $this->database->query($sql);
	}
	
	/**
	 * Insert all the subscribers in batches
	 * @param array of all insert batch
	 */
	public function addSubscriberInGroupInBatches( 
			&$batch_data, $user_id = false, $group_id = false, $C_group_handler = false, $user_type = 'customer' ){

		if( !$C_group_handler && $group_id )
			$C_group_handler = new CampaignGroupBucketHandler($group_id);
				
		if( $C_group_handler ){
			
			$exist = $C_group_handler->isUserExistsInBucket( $user_id );
			
			$this->logger->debug("Is the user already in the list and active? ". $exist['is_active'] );
					
			$insert_id = $C_group_handler->insertInBatches($batch_data, $user_type);
		}else 
			throw new Exception( _campaign("Please provide the group id") );

		if( $user_id && $group_id && $insert_id ){
					
				$C_campaign_group_model_extension = new CampaignGroupModelExtension();
				$C_campaign_group_model_extension->load( $group_id );
				$params = $C_campaign_group_model_extension->getParams();
				$customer_count = $C_campaign_group_model_extension->getCustomerCount();
				$total_clients = $C_campaign_group_model_extension->getTotalClients();
				$params_decoded = json_decode( $params, true );
						
				$insert_exists = $C_group_handler->isUserExistsInBucket( $user_id );
				$email_count = 0;
				$mobile_count = 0;
				//Case 1 - if its a new user 
				//Case 2 - if the user already exists but has been inactivated	
				if ( sizeof($exist) == 0 || $exist['is_active'] == 0) {
					
					$customer_count += 1;
					$total_clients += 1;					
					$email_count = $insert_exists['is_email_exists'];
					$mobile_count = $insert_exists['is_mobile_exists'];
					
					$params_decoded['email'] = $params_decoded['email'] + $email_count;
					$params_decoded['mobile'] = $params_decoded['mobile'] + $mobile_count;
								
				}  //Case 3 - if the user already exists and is active, 
				  //but email or mobile info is being added to the same
				 elseif ( $exist['is_active'] == 1) {
					
					if( (!$exist['is_email_exists']) && $insert_exists['is_email_exists'] )
						$email_count = 1;
						
					if( (!$exist['is_mobile_exists']) && $insert_exists['is_mobile_exists'] )
						$mobile_count = 1;
					$params_decoded['email'] = $params_decoded['email'] + $email_count;
					$params_decoded['mobile'] = $params_decoded['mobile'] + $mobile_count;
				}
				
				$params = json_encode( $params_decoded );				
				$C_campaign_group_model_extension->setParams( $params );
				$C_campaign_group_model_extension->setCustomerCount( $customer_count );
				$C_campaign_group_model_extension->setTotalClients( $total_clients  );								
				$C_campaign_group_model_extension->update( $group_id );
			
		}
		return $insert_id;
	
    }
	
	/**
	 * insert into audience group by following parameters
	 * @param integer $campaign_id
	 * @param string $provider_type
	 * @param JSONstring $json
	 *
	 */
	public function insertAudienceGroups( $campaign_id , $provider_type , $json ){
	
		$this->AudienceGroup->setCampaignId( $campaign_id );
		$this->AudienceGroup->setOrgId( $this->current_org_id );
		$this->AudienceGroup->setAudienceProvider( $provider_type );
		$this->AudienceGroup->setParams( $json );
	
		return $this->AudienceGroup->insert();
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
			
		$sql = " INSERT INTO `$this->database_camp`.`selection_filter`
					(`audience_group_id`,`org_id`,`params`,`filter_type`,`filter_explaination`,`no_of_customers`,`custom_ids`) 
				VALUES 
					('$id','$this->current_org_id','$filter_params','$filter_type',
						'$filter_explaination',$customers,'".addslashes( $custom_ids )."')";
					
		return $this->database->insert( $sql );
	}
	
	/////////////////////////////////////////////////  VOUCHER UPLOAD RELATED QURIES //////////////////////////////////////////////////
	
	/**
	 * Get External ID
	 * @param array $mobiles
	 */
	public function getExternalId( array $mobiles ){
		$mobile_card_mapping = array();
		
		$sql = "SELECT l.`external_id` as external_id, u.`mobile` as mobile 
				FROM `$this->database_users`.users u JOIN `user_management`.loyalty l ON u.id = l.user_id
				AND u.org_id = l.publisher_id
				WHERE u.mobile IN (".Util::joinForSql($mobiles).")
				AND l.external_id IS NOT NULL 
				AND u.org_id =".$this->org_id;
		
		$result = $this->database->query( $sql );
		
		foreach($result as $row){
			$mobile_card_mapping[$row['mobile']] = $row['external_id']; 
		}
		
		return $mobile_card_mapping;
	}
	
	/**
	 * Check if the voucherCodeExists.
	 * @param $voucherCode
	 * @param $org_id
	 */
	
	public function voucherCodeExists( array $voucherCodes , $org_id ) {
		
		$voucher_codes_sql = Util::joinForSql($voucherCodes);

		$sql = " SELECT voucher_code FROM `$this->database_camp`.`voucher` WHERE voucher_code IN ($voucher_codes_sql) AND org_id = $org_id ";
		
		$result = $this->database->query_firstcolumn( $sql );
		return $result;
	}
	
	/**
	 *  Update Voucher series with number of issued voucher
	 * @param unknown_type $count_uploaded
	 * @param unknown_type $vs
	 */
	public function updateVoucherSeries( $count_uploaded , $vs ){
		$vid = $vs->id;
		$sql = "UPDATE `$this->database_camp`.`voucher_series` SET `num_issued` = `num_issued` + $count_uploaded WHERE `id` = $vid";
		$this->database->update( $sql );
	}
	
	/**
	 * Vouchers Batch Insertion
	 * @param unknown_type $sql
	 */
	public function vouchersBatchInsert( $batch_data ){
		
		$sql =  "INSERT IGNORE INTO `$this->database_camp`.`voucher` 
					(`org_id`, `voucher_code`, `pin_code`, `created_date`, `issued_to`, `current_user`, `voucher_series_id`, `created_by`) 
				VALUES 
					$batch_data
				";
		
		return $this->database->insert( $sql );
	}
	
	/**
	 * Check Users existance with mobile numbers
	 */
	public function checkUsersByMobile( $mobile_array ){
		
		$mobile_array_sql = Util::joinForSql( $mobile_array );
		
		$sql = " SELECT id , mobile FROM `$this->database_users`.`users` 
					WHERE org_id = $this->current_org_id 
						AND mobile IN ($mobile_array_sql) ";
		
                //$db = new Dbase('users', TRUE);
		return $this->database->query( $sql );
	}
	
	/**
	 * Check Users existance with mobile numbers
	 */
	public function checkUsersByMobileForBatch( $mobile_array ){
		
		$mobile_array_sql = Util::joinForSql( $mobile_array );
		
		$sql = " SELECT 
					id AS 'user_id' , 
					mobile AS 'email_mobile' , 
					firstname , 
					lastname , 
					CASE 
						WHEN ( 
								`mobile` IS NOT NULL AND `mobile` != '' ) 
							THEN 1 
							ELSE 0 
						END AS is_mobile_exists,
					CASE 
						WHEN ( `email` IS NOT NULL AND `email` != '' ) 
							THEN 1 
							ELSE 0 
						END AS is_email_exists 
					
					FROM `$this->database_users`.`users` 
					WHERE org_id = $this->current_org_id 
						AND mobile IN ($mobile_array_sql) ";
		
		return $this->database->query_hash( $sql , 'email_mobile', 
				array( 'user_id' , 'firstname' , 'lastname', 'is_email_exists', 'is_mobile_exists' ) );
	}

	/**
	 * Check Users existance with mobile numbers
	 */
	public function checkUsersByExternalIdForBatch( $external_array ){
		
		$external_array_sql = Util::joinForSql( $external_array );
		
		$sql = " SELECT  `user_id` , `external_id`
					FROM user_management.loyalty 
					WHERE publisher_id = $this->current_org_id 
						AND external_id IN ( $external_array_sql ) ";
		
		return $this->database->query_hash( $sql, 'external_id', 'user_id' );
	}
	
	/**
	 * Check Users existance with mobile numbers
	 */
	public function checkUsersByEmail( $email_array ){
		
		$email_array_sql = Util::joinForSql( $email_array );
		
		$sql = " SELECT 
					id AS 'user_id' , 
					email AS 'email_mobile' , 
					firstname , 
					lastname ,
					CASE 
						WHEN ( 
								`mobile` IS NOT NULL AND `mobile` != '' ) 
							THEN 1 
							ELSE 0 
						END AS is_mobile_exists,
					CASE 
						WHEN ( `email` IS NOT NULL AND `email` != '' ) 
							THEN 1 
							ELSE 0 
						END AS is_email_exists 
				FROM `$this->database_users`.`users` 
				WHERE org_id = $this->current_org_id AND email IN ($email_array_sql) ";
		
		return $this->database->query_hash( $sql , 'email_mobile' , 
				array( 'user_id' , 'firstname' , 'lastname', 'is_email_exists', 'is_mobile_exists' ) );
	}
	
	/**
	 * check users by user_ids
	 * @param unknown_type $user_ids
	 */
	public function getUserIdsByIds( $user_ids ){

		$user_ids = Util::joinForSql( $user_ids );
		
		$sql = "
				SELECT `id` AS 'user_id' 
				FROM `$this->database_users`.`users`
				WHERE `id` IN ( $user_ids ) AND `org_id` = '$this->current_org_id'
		";
		
		return $this->database->query_hash( $sql , 'user_id' , 'user_id' );
	}
	

	public function getCustomTag( $user_id, $group_id, 
			CampaignGroupBucketHandler $C_campaign_group_handler ){
		
		return $C_campaign_group_handler->fetchCustomTag( $user_id );
	}
	
	/**
	 * fetches audience group id from ids
	 * 
	 * @param $campaign_id
	 * @param $group
	 */
	public function getAudienceGroupByCampaignIdAndGroupId( $campaign_id, $group_id  ){
		
		$sql = "SELECT `id`
				FROM `$this->database_camp`.`audience_groups` 
				WHERE `campaign_id` = '$campaign_id' AND `params` = $group_id AND `org_id` = $this->current_org_id ";
		
		return $this->database->query_scalar($sql);
	}
	
	/**
	 * fetches audience group id from ids
	 * 
	 * @param $campaign_id
	 * @param $group
	 */
	public function getAudienceDetailsByGroupId( $campaign_id, $group_id ){
		
		$sql = "SELECT *
				FROM `$this->database_camp`.`audience_groups` 
				WHERE `campaign_id` = '$campaign_id' AND `params` = $group_id AND `org_id` = $this->current_org_id ";
		
		return $this->database->query_firstrow($sql);
	}
	
	/**
	 *  insert messages email queue
	 * 
	 * 
	 * @param $campaign_id
	 * @param $group_id
	 * @param $sent_msg
	 * @param $subject
	 * @param $template_id
	 * @param $default_arguments
	 */
	public function insertIntoMessageQueue( $type, $campaign_id, $group_id,  
		$scheduled_by, $schedule_type, $schedule_on, $default_arguments, $params, $guid ){
		
	    $default_arguments = mysql_escape_string($default_arguments);
	   	$params = mysql_escape_string( $params );
	  
		$sql = "INSERT INTO $this->database_msging.`message_queue` 
				( `guid` ,`type`,`org_id`, `campaign_id`, `group_id`,  `scheduled_on`, `scheduled_by`, `last_updated_on`, 
				`default_arguments`, `status`,`scheduled_type`,`params`)
					VALUES
				( '$guid', '$type', $this->current_org_id, '$campaign_id', '$group_id', '$schedule_on', 
					'$scheduled_by', NOW(), '$default_arguments', 'OPEN', '$schedule_type', '$params')";
		
		return $this->database->insert( $sql );
	}
	
	/**
	 * @param unknown $messageId
	 * @return Ambigous <boolean, multitype:, unknown>
	 */
	public function deleteQueuedMessage( $messageId ){
		
		$sql = "
				DELETE FROM $this->database_msging.`message_queue` WHERE id = $messageId
				";
		
		return $this->database->update($sql);
	}
	
	/**
	 *  update messages queue
	 * 
	 * 
	 * @param $campaign_id
	 * @param $group_id
	 * @param $sent_msg
	 * @param $subject
	 * @param $template_id
	 * @param $default_arguments
	 */
	public function updateMessageQueue( $message_id, $type, $campaign_id, $group_id,  $scheduled_by, $schedule_type, $schedule_on, $default_arguments, $params ){
		
    $default_arguments = mysql_escape_string($default_arguments);
   	$params = mysql_escape_string( $params );
  
	$sql = "UPDATE $this->database_msging.`message_queue` 
			SET	  `scheduled_on` = '$schedule_on', 
				 `scheduled_by` = '$scheduled_by', `last_updated_on` = NOW(), 
				 `default_arguments` = '$default_arguments', 
				 `scheduled_type` = '$schedule_type' ,
				 `params` = '$params' 
			WHERE `id` = '$message_id' AND `org_id` = '$this->current_org_id' ";


	return $this->database->update( $sql );
		
	}
	
	/*
	 * fetches group id and reminder id
	 * 
	 */
	
	public function getReminderIdBytaskId( $id){
			
		$sql = " SELECT id,group_id  FROM `$this->database_users`.`reminder` 
					 WHERE scheduler_task_id = $id 
					 AND org_id = $this->current_org_id AND reminder_type = 'CAMPAIGN'";
		
		return $this->database->query( $sql );
		
	}
	
	/*
	 * get the frequency from the reminder table
	 */
	
	public function getDateFromReminder( $id ){
		
		$sql = " SELECT `frequency` FROM `$this->database_users`.`reminder` 
					 WHERE `refrence_id` = $id 
					 AND org_id = $this->current_org_id 
		 			 AND reminder_type = 'CAMPAIGN'" ;

		return $this->database->query_scalar( $sql );

	}
	
/*
	 * get the task id from the reminder table
	 */
	
	public function getReminderTaskIdAndState( $queue_id , $group_id , $audience_group_id ){
		
		$sql = " SELECT `state` , `scheduler_task_id` FROM `$this->database_users`.`reminder` 
					 WHERE `refrence_id` = '$queue_id' 
					 AND `group_id` = '$group_id'
					 AND `audience_group_ids` = '$audience_group_id'
					 AND `org_id` = '$this->current_org_id' 
					 AND `reminder_type` = 'CAMPAIGN' " ;
		
		return $this->database->query_firstrow( $sql );
	}
	
	/**
	 * @deprecated
	 * get Email queued messages 
	 */
	public function getQueuedEmailMessages( $status, $type){

		return;
	}	
	
	/**
	 * get audience filter details by campaign id
	 */
	public function getAudienceDataByCampaignID( $campaign_id, $favourite = false, $search_filter = false ){
		
		//TODO : passing search string over thrift. I know its not cool.
		//but something I have to live with for now
		//only test type is needed. We would ignore ALL & CONTROL
		
		$group_type = array( 'TEST' );
		$groups = 
			GroupDetailModel::getAllByCampaignId($campaign_id, 
					$this->current_org_id, $favourite, $search_filter, $group_type);
		
		$group_ids = array();
		foreach( $groups as $group_details ){

			array_push( $group_ids, $group_details['group_id'] );
		}
		
		$group_ids_string = Util::joinForSql( $group_ids ) ;
		$sql = " 
					SELECT id, audience_provider, ag.params
					FROM `$this->database_camp`.`audience_groups` AS ag
					WHERE ag.campaign_id = $campaign_id AND params IN ( $group_ids_string )
					AND `org_id` = $this->current_org_id ";
		
		return $this->database->query($sql);
	}
	
	/**
	 * get group label by group id
	 */
	public function getGroupLabel( $group_id ){
		
		$C_campaign_group_model_extension = new CampaignGroupModelExtension();
		$C_campaign_group_model_extension->load( $group_id );
		return $C_campaign_group_model_extension->getGroupLabel();
	}

	/**
	 * get All the filters details by audience group id
	 */
	public function getFilterDetailsByAudienceGroupId( $audience_group_id ){
		
		if( is_array( $audience_group_id ))
			$audience_group_ids = implode(',', $audience_group_id);
		else
			$audience_group_ids = $audience_group_id;
		
		if( $audience_group_ids == "" )
			return ; 
		
		$sql = "SELECT * from `$this->database_camp`.`selection_filter` 
				WHERE `org_id`=$this->current_org_id
				AND `audience_group_id` IN ( $audience_group_ids )  
				";
		
		return $this->database->query($sql);
	}
	
	public function getFilterDataByGroupIds($group_ids, $campaign_id) {

		$group_ids_string = Util::joinForSql( $group_ids ) ;

		$sql = "SELECT ag.params AS group_ids, sf.audience_group_id, sf.filter_explaination, sf.no_of_customers
		FROM  `$this->database_camp`.`audience_groups` ag,  `$this->database_camp`.`selection_filter` sf
		WHERE ag.id = sf.audience_group_id
		AND ag.campaign_id = $campaign_id
		AND ag.`org_id` = $this->current_org_id
		AND ag.params
		IN ( $group_ids_string )" ;

		return $this->database->query($sql);
	}
	

	/**
	 * get the all campaign details
	 */
	public function getAll( ){
		
		$cache_key = /*CacheKeysPrefix::$campaign.'_GET_ALL_ORG_ID_'.$this->current_org_id;*/
            		"o".$this->current_org_id."_".
					CacheKeysPrefix::$campaign.'_GET_ALL_ORG_ID_'.$this->current_org_id;
		$ttl = CacheKeysTTL::$campaign;
		try{
			
			$json_result  = $this->mem_cache_manager->get( $cache_key );
			$this->logger->debug("INSIDE GET ALL CAMPAIGN DETAILS");
			//$this->logger->debug("INSIDE GET ALL".print_r($json_result,true));
			$result = json_decode( $json_result, true );
		}catch( Exception $e ){

			try{
				
				$sql = "
				
					SELECT 
						`id`,
						CASE WHEN `active` = 0 THEN 0 ELSE 1 END AS `active_status`,
						name, type, created, `start_date`, `end_date`, 
						CASE WHEN `voucher_series_id` = -1 THEN 0 ELSE 1 END AS `issue_voucher`
					FROM `campaigns_base`
					WHERE `org_id` = '$this->current_org_id' 
				";
				
				$result =  $this->database->query( $sql );

				$json_result = json_encode( $result );
				$this->logger->debug("INSIDE GET ALL CAMPAIGN DETAILS");
				//$this->logger->debug("INSIDE GET ALL1".print_r($json_result,true));
				$this->mem_cache_manager->set( $cache_key, $json_result, $ttl );
			}catch( Exception $inner_e ){
				global $logger;
				$logger->error( $inner_e->getMessage() );
			}
		}
		
		return $result;
	}
	
	/**
	 * Does name exists
	 * @param unknown_type $name
	 */
	public function isNameExists( $name, $campaign_id = false ){

		$campaign_id = ( int ) $campaign_id;
		$sql = "
				SELECT COUNT( * )
				FROM `$this->table`
				WHERE `name` = '$name' AND `org_id` = '$this->current_org_id' AND `id` != $campaign_id
		";

		return $this->database->query_scalar( $sql );
	}	
	
	/**
	 * get details of message
	 * @param unknown_type $campaign_id
	 * @param unknown_type $group_id
	 * @param unknown_type $outtype
	 */
	
	public function getMessageQueueDetails( $campaign_id , $group_id , $outtype = 'query' ){
		
		$org_id = $this->org_id;
			
		$sql = " SELECT *
					FROM `$this->database_msging`.`message_queue` AS `mu` 
					WHERE  `mu`.`org_id` = '$org_id' 
						AND `mu`.`group_id` = '$group_id'
						AND `mu`.`campaign_id` = '$campaign_id' 
				";
		
		if( $outtype == 'table' )
			return $this->database->query_table( $sql );

		return $this->database->query( $sql );
	}
	
	/**
	 * Check reminder is exists for the audience_group_id and group_id for particular org
	 */
	public function isReminderExists( $audience_group_id , $group_id ){
		
		$sql = " SELECT  *
				 	FROM `$this->database_users`.`reminder` AS `r`
				 WHERE  `r`.`org_id` = '$this->current_org_id' AND `r`.`audience_group_ids` = $audience_group_id AND `r`.`group_id` = $group_id 
				";
		       
		return $this->database->query( $sql );
	}
	
	/**
	 * Getting reminder details for all the audience groups at once.
	 * @param unknown $audience_group_ids
	 * @param unknown $group_ids
	 */
	public function getAllRemindersByIds( $audience_group_ids , $group_ids ){
	
		$sql = "SELECT * FROM `$this->database_users`.`reminder` as `r`
			    WHERE `r`.`org_id` = '$this->current_org_id'
				AND `r`.`audience_group_ids` IN ( $audience_group_ids )
				AND `r`.`group_id` IN ( $group_ids )
			";
		return $this->database->query( $sql );
	}
	
	///////////////////////////////////////////////////////// ROI RALATED Queries Start From Here ////////////////////////////////////////////////////
	
	public function getRevertOptionsForFilters( $audience_group_id , $campaign_id ){

		$sql = "SELECT id, time FROM `$this->database_camp`.`filter_change_log`				
				WHERE `audience_group_id` = $audience_group_id AND `campaign_id` = $campaign_id
				AND `org_id` = $this->current_org_id AND `status` = 0";
		
		$result = $this->database->query($sql);
		return $result;	
	}

	/**
	 * @deprecated
	 * @param unknown $change_id
	 * @param unknown $audience_group_id
	 * @param unknown $campaign_id
	 */
	public function revertSelectionFilterSet( $change_id , $audience_group_id , $campaign_id ){
		return true;
	}
	
	/**
	 * @param $group_id
	 */
	public function getGroupDetails( $group_id ){
		
		$C_campaign_group_model_extension = new CampaignGroupModelExtension();
		$C_campaign_group_model_extension->load( $group_id );
		return $C_campaign_group_model_extension->getHash( );
	}

	/**
	 * get Details for offline processing
	 * @param int $voucher_series_id
	 */
	function getCouponRedemptionDetailsForOffline( $voucher_series_id , $type = 'query' )
	{
		$org_id = $this->current_org_id;
		
		$sql = " SELECT `vr`.`id`, `ll`.`bill_amount`, `ll`.`date` as `bill_date`, `vr`.`used_date` "
             . " FROM `$this->database_camp`.`voucher_redemptions` AS `vr` "
             . " JOIN `$this->database_camp`.`voucher` AS `v` ON ( `vr`.`voucher_id` = `v`.`voucher_id` ) "
             . " JOIN `$this->database_users`.`loyalty` AS `l` ON ( `l`.`publisher_id` = `v`.`org_id` AND `l`.`user_id` = `vr`.used_by )"
             . " JOIN `$this->database_users`.`loyalty_log` AS `ll` ON ( `ll`.`org_id` = `l`.`publisher_id` AND  `ll`.`user_id` = `l`.`user_id` ) "
             . " WHERE DATE(`ll`.`date`) = DATE(`vr`.`used_date`) AND `v`.`org_id` = '$org_id' AND `v`.`voucher_series_id` = '$voucher_series_id' "
             . " ORDER BY `vr`.`id` ASC";
              
		 if ($type == 'query_table')
			return $this->database->query_table($sql);
					 
		return $this->database->query($sql);
	}
	
	/**
	 * update the voucher redemption sales providing sales on the next bill and
	 * sameday sales and voucher redemption id
	 * @param  $sales_nextbill
	 * @param  $sales_sameday
	 * @param  $voucher_redemption_id
	 * 
	 */
	function updateCouponRedemptionSales($sales_nextbill, $sales_sameday, $id)
	{
		$sql = "UPDATE `$this->database_camp`.`voucher_redemptions` SET sales_nextbill = $sales_nextbill, sales_sameday = $sales_sameday WHERE id = $id";
				
		return $this->database->update($sql);
	}
	
	/**
	 * gives sum of sales by giving vsch_series_id on each voucher_id
	 * @param int $vch_series_id
	 */
	function getCouponRedemptionSumsByCouponSeriesId( $voucher_series_id , $type = 'query')
	{
		$org_id = $this->current_org_id;
		
		$sql = "SELECT vr.voucher_id, SUM(sales_nextbill) as nextbill, SUM(sales_sameday) as sameday "
		." FROM `$this->database_camp`.`voucher_redemptions` vr "
		. "JOIN `$this->database_camp`.`voucher` v ON v.voucher_id = vr.voucher_id "
		. "WHERE v.voucher_series_id = $voucher_series_id AND v.org_id = '$org_id' GROUP BY voucher_id";
		
		if ($type == 'query_table')
			return $this->database->query_table($sql);
					 
		return $this->database->query($sql);
	}
	
	/**
	 * Get the coupon redemption details
	 * @param unknown_type $coupon_series_id
	 * @param unknown_type $type
	 */
	public function getCouponRedemptionDetails( $coupon_series_id , $type = 'query' , $filter = array() )
	{
		$org_id = $this->current_org_id;

		$primary_key='';
		if($type == 'query')
			$primary_key = '{{where}}';
			
		$date_filter = '';
		$series_filter = '';
		if( $filter['date_start'] && $filter['date_end'] ){
			$date_start = $filter['date_start'] ;
			$date_end = $filter['date_end'] ;
			$date_filter = " AND `vr`.`used_date` Between  '$date_start' AND '$date_end' " ;
			
			if( $filter['series_selected'] != false ){
				$series_filter = " AND vr.`voucher_series_id` IN (".Util::joinForSql( $filter['series_selected'] ).") ";	
			} 
		}
			
//		$sql = "SELECT TRIM(CONCAT(eup_r.firstname, ' ', eup_r.lastname)) AS customer_name, 
//					eup_r.user_id AS customer_user_id, eup_r.mobile AS customer_mobile, v.voucher_code, 
//					vr.used_date AS used_on, vs.description,u_rs.username AS used_at_store, 
//					IF( LENGTH(vr.bill_number) = 0 OR vr.bill_number IS NULL, 'Not Entered', vr.bill_number) AS bill_no, 
//					vr.bill_amount as bill_amount,
//					ll.bill_gross_amount, ll.bill_discount, 
//					( SELECT MAX(ls.date) FROM user_management.loyalty_log ls WHERE ls.org_id = v.org_id AND ls.user_id = vr.used_by and ls.date < vr.used_date ) AS prev_bill_on, 
//					vr.sales_nextbill, vr.sales_sameday "  
//				." FROM `$this->database_camp`.`voucher_redemptions` vr "					
//				." JOIN `$this->database_camp`.`voucher` v ON vr.voucher_id = v.voucher_id AND v.voucher_series_id = $coupon_series_id AND `v`.`test` = '0'"
//				." JOIN `$this->database_camp`.`voucher_series` vs ON `v`.`voucher_series_id` = `vs`.`id` "
//				." LEFT JOIN `$this->database_users`.`loyalty_log` ll ON ll.org_id = v.org_id AND ll.user_id = vr.used_by AND ll.bill_number = vr.bill_number "
//				." JOIN `$this->database_users`.`extd_user_profile` eup_r ON vr.used_by = eup_r.user_id AND eup_r.org_id = v.org_id "					
//				." JOIN `$this->database_users`.`stores` u_rs ON u_rs.store_id = vr.used_at_store "
//				." WHERE v.org_id = $org_id ";			
				
		$sql = "SELECT vr.id, TRIM(CONCAT(eup_r.firstname, ' ', eup_r.lastname)) AS customer_name, 
					eup_r.id AS customer_user_id, eup_r.mobile AS customer_mobile, v.voucher_code, 
					vr.used_date AS used_on, vs.description,u_rs.code AS used_at_store,u_rs.`id` AS used_at_store_id, 
					IF( LENGTH(vr.bill_number) = 0 OR vr.bill_number IS NULL, 'Not Entered', vr.bill_number) AS bill_no, 
					vr.bill_amount as bill_amount,
					ll.bill_gross_amount, ll.bill_discount, 
					( SELECT MAX(ls.date) FROM user_management.loyalty_log ls WHERE ls.org_id = v.org_id AND ls.user_id = vr.used_by and ls.date < vr.used_date ) AS prev_bill_on, 
					vr.sales_nextbill, vr.sales_sameday "  
				." FROM `$this->database_camp`.`voucher_redemptions` vr "					
				." JOIN `$this->database_camp`.`voucher` v ON vr.voucher_id = v.voucher_id AND v.voucher_series_id = $coupon_series_id AND `v`.`test` = '0'"
				." JOIN `$this->database_camp`.`voucher_series` vs ON `v`.`voucher_series_id` = `vs`.`id` "
				." LEFT JOIN `$this->database_users`.`loyalty_log` ll ON ll.org_id = v.org_id AND ll.user_id = vr.used_by AND ll.bill_number = vr.bill_number "
				." JOIN `$this->database_users`.`users` eup_r ON vr.used_by = eup_r.id AND eup_r.org_id = v.org_id "					
				." JOIN `$this->database_masters`.`org_entities` u_rs ON u_rs.id = vr.used_at_store "
				." WHERE v.org_id = $org_id $date_filter $series_filter $primary_key";
				
		if ($type == 'query_table')
			return array( $sql , $this->database->query( $sql ) , false);
					 
		return array($sql,false,'vr.id');
	}

	/**
	 * Get the coupon redemption details
	 * @param unknown_type $coupon_series_id
	 * @param unknown_type $type
	 */
	public function getCouponRedemptionCustomDetails( $coupon_series_id , $type = 'query', $filter = '' )
	{

		if($filter['date_start'] && $filter['date_end']){
			$date_start = $filter['date_start'] ;
			$date_end = $filter['date_end'] ;
			$date_filter = " AND `vr`.`used_date` Between  '$date_start' AND '$date_end' " ;
		} 
			
		$org_id = $this->current_org_id;

		$sql = "SELECT vs.description,
					TRIM(CONCAT(usr.firstname, ' ', usr.lastname)) AS customer_name, 
					usr.mobile AS customer_mobile, v.voucher_code, 
					vr.used_date AS used_on, u_rs.code AS used_at_store, 
					vr.bill_amount as bill_amount"  
				." FROM `$this->database_camp`.`voucher_redemptions` vr "					
				." JOIN `$this->database_camp`.`voucher` v ON vr.voucher_id = v.voucher_id AND v.voucher_series_id = $coupon_series_id AND `v`.`test` = '0'"
				." JOIN `$this->database_camp`.`voucher_series` vs ON `v`.`voucher_series_id` = `vs`.`id` "
				." JOIN `$this->database_users`.`users` usr ON vr.used_by = usr.id AND usr.org_id = v.org_id "					
				." JOIN `$this->database_masters`.`org_entities` u_rs ON u_rs.id = vr.used_at_store "
				." WHERE v.org_id = $org_id $date_filter ";			
		
		if ($type == 'query_table')
			return $this->database->query_table( $sql , 'redeem_details');
		return array($sql,$this->database->query( $sql ));
	}
	
	/**
	 * Get the coupon redemption details
	 * @param unknown_type $coupon_series_id
	 * @param unknown_type $type
	 */
	public function getCouponRedemptionDetailsOverView( $filter )
	{
	
		if($filter['date_start'] && $filter['date_end']){
			$date_start = $filter['date_start'] ;
			$date_end = $filter['date_end'] ;
			$date_filter = " AND `vr`.`used_date` Between  '$date_start' AND '$date_end' " ;
		} 
		
		
		$org_id = $this->current_org_id;

		$sql = "SELECT  
					vs.id,
					vs.description,  
					SUM( vr.bill_amount ) AS `total_sale`,
					COUNT( vr.id ) AS `total_redemption`,
					COUNT( DISTINCT vr.used_by ) AS `unique_customers`,
					COUNT( DISTINCT vr.used_at_store ) AS `unique_stores`,
					vs.valid_till_date AS `expiry_date`					 
				 FROM `campaigns`.`voucher_redemptions` vr 					
				 JOIN `campaigns`.`voucher` v ON vr.voucher_id = v.voucher_id AND `v`.`test` = '0'
				 JOIN `campaigns`.`voucher_series` vs ON `v`.`voucher_series_id` = `vs`.`id` 
				 WHERE v.org_id = $org_id $date_filter  
				 GROUP BY `vs`.`id`";			
		
		return array($sql,$this->database->query( $sql ));
	}
	
	/**
	 * gives the Coupon details vy voucher id
	 * @param bool $issued_vouchers_export_xls
	 * @param int $vid
	 */
	function getCouponDetails( $coupon_series_id , $type = 'query' )
	{
		$org_id = $this->current_org_id;
			
		$primary_key='';
		if($type == 'query')
			$primary_key = '{{where}}';
			
		$sql = "SELECT 
					DISTINCT v.voucher_id, v.voucher_code, v.created_date, 
					IFNULL(TRIM(CONCAT(u1.firstname, ' ', u1.lastname)), 'NONLOYALTYCUSTOMER') AS `issued_to`, 
					u1.id AS issued_to_user_id, u1.mobile AS issued_to_mobile, 
					vs.description, v.created_by as code, v.created_by as admin_user_id "
			." FROM `$this->database_camp`.`voucher` v "
			." LEFT OUTER JOIN `$this->database_users`.`users` u1 ON `u1`.`id` = `v`.`issued_to` AND `v`.`org_id` = `u1`.`org_id` " 
			//." LEFT OUTER JOIN `$this->database_users`.`extd_user_profile` eu1 ON `eu1`.`user_id` = `v`.`issued_to` AND `v`.`org_id` = `eu1`.`org_id` "
			." JOIN `$this->database_camp`.`voucher_series` vs ON `v`.`voucher_series_id` = `vs`.`id` "
			//." JOIN `$this->database_masters`.`org_entities` s ON `s`.`id` = `v`.`created_by`  "
			." WHERE `v`.`voucher_series_id` = '$coupon_series_id' AND `v`.`org_id` = $org_id AND `v`.`test` = '0' $primary_key";
		
		//add limit for view
		$with_limit_sql .= $sql . ' LIMIT 500';
		
		$key_map = array("code" => "code", "admin_user_id" => "id");
		$joiner_type = "ORG_ENTITY";
		if ($type == 'query_table') {
			$results = $this->database->query( $with_limit_sql );
			return array( $sql , $this->memory_joiner->prepareReport($results, $key_map ) , false, false, array() );
		}

		
		return array( $sql , false , 'v.voucher_id' , $joiner_type, $key_map);
	}
	
/**
	 * gives the campaign reffereal details for referrer and which store it was
	 * referred on. by voucher id
	 * @param bool $campaign_referrals_export_xls
	 * @param int $vid
	 * @param string $type
	 */
	function getCampaignReferralDetails( $coupon_series_id , $type = 'query' )
	{
		$org_id = $this->current_org_id;
		
		$sql = "SELECT TRIM(CONCAT(u_r.firstname, ' ', u_r.lastname)) AS referrer_name, 
						u_r.id AS referrer_user_id, u_r.mobile AS referrer_mobile,
						u_s.code AS referred_at_store, c.referee_name, c.referee_mobile, 
						c.referee_email, v.voucher_code, c.created_on AS referral_date, c.num_reminders, 
						IFNULL(c.last_reminded, 'No reminders sent') AS last_reminded_on, 
						IFNULL( l.joined, 'Not Joined Yet') AS referee_joined_status "
			." FROM `$this->database_camp`.`campaign_referrals` c "
			." JOIN `$this->database_camp`.`voucher` v ON `v`.`org_id` = `c`.`org_id` AND `v`.`voucher_id` = `c`.`voucher_id`"
			." JOIN `$this->database_users`.`users` u_r ON `u_r`.`id` = `c`.`referrer_id` AND `u_r`.`org_id` = `c`.`org_id` "
			." JOIN `$this->database_masters`.`org_entities` u_s ON `u_s`.`id` = `c`.`store_id` AND `u_s`.`org_id` = `c`.`org_id` "
			." LEFT JOIN `$this->database_users`.`loyalty` l ON `c`.`org_id` = `l`.`publisher_id` AND `c`.`referee_id` = `l`.`user_id` "
			." WHERE `c`.`voucher_series_id` = $coupon_series_id AND `c`.`org_id` = $org_id";
		
		if ($type == 'query_table')
			return $this->database->query_table($sql);
					 
		return $this->database->query($sql);
	}
	
	/**
	 * sets vouchers as test vouchers
	 * @param array $voucher_id
	 */

	public function updateCouponAsTestVouchers( array $values ){
		
		$values = implode($values,",");
		
		$sql = " UPDATE `$this->database_camp`.`voucher` SET `test` = '1' WHERE `voucher_id` IN ($values)";
		
		$this->database->update($sql);
		
	}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	

/**
	 * filters by zone or store
	 * @param $store_selected
	 * @param  $zone_filter
	 */
	public function store_zone_sel($store_selected,$zone_filter,$allias = '`cr`'){ 
		$am = new AdministrationModule();
		return (($store_selected != NULL)? $am->getModifiedStoreFilter($store_selected,"$allias.`store_id`") : $am->getModifiedZoneFilter('z.store_id', $zone_filter));
	}
	
	/**
	 * @deprecated
	 * @param unknown_type $campaign_id
	 */
	private function getGroupIdForDVSGroup( $campaign_id ){}
	
	/**
	 * delete subscriber from particular group
	 * * @param unknown_type $group_id
	 * @param unknown_type $user_id
	 */
	public function removeCustomerBYUserId( $group_id , $user_id ){

		$C_campaign_group_handler = new CampaignGroupBucketHandler($group_id);
		return $C_campaign_group_handler->removeCustomerFromList($user_id);
	}
	
	/*
	 * get template list as with details for org_id
	 */
	public function getTemplateList(){
		
		$sql = "
				SELECT gt.`name` , gtf.`filter_explaination`, gtf.`filter_type`
				FROM `$this->database_camp`.`group_templates` gt
				JOIN `$this->database_camp`.`group_template_filters` gtf 
					 ON gt.`org_id` = gtf.`org_id` AND gt.`id` = gtf.`group_template_id`   
				WHERE gtf.`org_id`= '$this->current_org_id' 
		";

		return $this->database->query( $sql );
	}
	
	/**
	 * Get archive group details campaign wise
	 */
	public function getArchiveGroupDetailsForCampaignId( $campaign_id = false ){

		return CampaignGroupModelExtension::getAllArchivedGroupDetails( $this->current_org_id, $campaign_id );
	}
	
	/**
	 * run backport of groups from archive
	 * @param $group_id
	 */
	public function groupBackportFromArchive( $group_id ){
		
		return $this->C_group_detail->backPortArchivedGroupToCGU( $group_id );
	}
	
	/**
	 * 
	 * @param unknown_type $campaign_id
	 * @param unknown_type $group_ids
	 * @param unknown_type $msg_ids
	 */
	public function updateDeliveryStatus( $group_ids, $msg_ids ){
		
		return $this->C_group_detail->updateDeliveryStatusToCgu( $group_ids, $msg_ids );		
	}
	
	/**
	 * It will returns bulk messages details by queue id
	 * @param unknown_type $queue_id
	 */
	public function getBulkMessagesDetailsByQueueId( $campaign_id , $queue_id , $group_id ){
		
		$sql = "
				SELECT `msg_id`
				FROM `$this->database_msging`.`bulksms_campaign`
				WHERE `org_id` = '$this->current_org_id'
				 		AND `campaign_id` = '$campaign_id'
					 	AND `group_id` = '$group_id'
					 	AND `queue_id` = '$queue_id'
			   ";
		
		return $this->database->query_firstrow( $sql );			
	}
	
	/**
	 * TODO : have no clue what this is used for
	 * Returns the campaign status
	 */
	public function getCampaignStatus( $campaign_id ){
		
		return true;
	}
	
	/**
	 * Getting live campaign for campaign home page.
	 * @param unknown_type $limit
	 * @param unknown_type $where_filter
	 */
	public function getCurrentCampaign( $limit, $where_filter ){
		
		$sql = "SELECT 
						`id`,
						CASE WHEN `active` = 0 THEN 0 ELSE 1 END AS `active_status`,
						name, type, created, `start_date`, `end_date`, 
						CASE WHEN `voucher_series_id` = -1 THEN 0 ELSE 1 END AS `issue_voucher`
					FROM `campaigns_base`
					WHERE `org_id` = '$this->current_org_id' $where_filter 
					$limit";
		
		$data = $this->database->query( $sql );
		
		$sql = 'SELECT COUNT(id) FROM campaigns_base';
		
		$count = $this->database->query_scalar( $sql );
		
		return array( 'data' => $data , 'count' => $count );
	}
	
	/**
	 * getting campaign for campaign data table.
	 * @param unknown_type $where
	 */
	public function getCurrentCampaignWithWhere( $where ){
		
		$sql = "SELECT
						`id`,
					CASE WHEN `active` = 0 THEN 0 ELSE 1 END AS `active_status`,
						name, type, created, `start_date`, `end_date`,
					CASE WHEN `voucher_series_id` = -1 THEN 0 ELSE 1 END AS `issue_voucher`
					FROM `campaigns_base`
					WHERE `org_id` = '$this->current_org_id' $where";
		
		return $this->database->query($sql);
	}
	
	
	/**
	 * 
	 * @param $group_id
	 */
	public function getMsgDetailsByGroupId( $group_id ){
		
		$sql = "
				SELECT `messageId`, `messageText`, `sendTime`
				FROM `$this->database_msging`.`outboxes` AS `o`
				JOIN `$this->database_msging`.`bulksms_campaign` AS `bc` ON 
					( `messageId` = `msg_id` )
				WHERE `o`.`publisherId` = '$this->current_org_id' 
					AND `type` = 'EMAIL' AND `categoryIds` = $group_id
		";
		
		return $this->database->query( $sql );
	}
	/** 
	* Returns status from ftp audience upload 
	*/
	public function getFtpValues( $download_id )
	{
		$sql = " SELECT *
			 FROM `$this->database_camp`.`ftp_audience_upload`
			 WHERE id = '$download_id' " ;
		return $this->database->query_scalar( $sql );
	}
		
	public function selectFromFtp( $status )
	{
		$sql = " 
				SELECT * 
				FROM `ftp_audience_upload`
				WHERE 
				`status` = '$status' 
				LIMIT 1 " ;
		$params = $this->database->query_firstrow( $sql ) ;
		
		return $params;
	}
	public function setFtpStatus( $status , $id )
	{
		$sql = "
				UPDATE 
				`ftp_audience_upload`
				SET 
				`status` = '$status' ,
				`last_updated_time` = NOW()
				WHERE 
				`id` = $id 
				" ;
		
		$status = $this->database->update( $sql ) ;
		
		return $status ;
	}
	
	public function insertFtpDb( $params , $campaign_id , $org_id , $user_id )
	{
		$file_name = $params[ 'ftpfile' ] ;
		$folder = $params[ 'ftpfolder' ] ;
		$confirm = $params[ 'confirm' ];
		$group_name = $params[ 'group_name' ];
		$custom_tags = $params[ 'custom_tags' ];
		$custom_tag_count = $params['custom_tag_count'];
		$import_type = $params['user_type'];
		$status = 'OPEN' ;  
		
		$sql = " INSERT INTO 
				`$this->database_camp` . `ftp_audience_upload` 
				( `org_id` ,
				  `campaign_id` , 
				  `group_name` ,
				  `folder` ,
				  `file` ,
				  `custom_tags`,
				  `status` ,
				  `confirm` ,
				  `last_updated_by` ,
				  `last_updated_time`,
				  `custom_tag_count`,
				  `import_type`
				   )
				 VALUES
				 (  $org_id ,
					$campaign_id ,
				   '$group_name' ,
				   '$folder' ,
				   '$file_name' ,
				   '$custom_tags' ,
				   '$status' ,
				   '$confirm' ,
				   '$user_id' ,
				   NOW(),
				   $custom_tag_count,
				   '$import_type'
				    ) ";
		return $this->database->insert( $sql );
	}
	
	public function getFtpSettings( $org_id )
	{
		$sql = " SELECT
				`server_name` , 
				`port` , 
				`user_name` , 
				`password`
				 FROM `$this->database_camp`.`ftp_settings` 
				 WHERE org_id = '$org_id' " ;
		return $this->database->query( $sql );
	}
	
	public function FtpSettingsInsert( $ftp_server , $ftp_port , $ftp_username , $ftp_password , $user_id , $org_id )
	{
		$sql = " INSERT INTO 
				 `$this->database_camp`.`ftp_settings` 
				 ( 	`org_id` , 
				 	`server_name` ,
				 	 `port` ,
				 	 `user_name` ,
				 	 `password` ,
				 	 `last_updated_by` ,
				 	 `last_updated_time`
				 )
				 VALUES
				 (	'$org_id' ,
				 	'$ftp_server' ,
				 	'$ftp_port' ,
				 	'$ftp_username' ,
				 	'$ftp_password' ,
				 	'$user_id' ,
				 	NOW() 
				 )
				 ON DUPLICATE KEY 
				 UPDATE 
				 `server_name` = '$ftp_server' ,
				 `port` = '$ftp_port' ,
				 `user_name` = '$ftp_username' ,
				 `password` = '$ftp_password' ,
				 `last_updated_by` = '$user_id' ,
				 `last_updated_time` = NOW()
				  ";
		return $this->database->insert( $sql );
				 	   	
	}
	
	public function getFtpFileStatus( $campaign_id )
	{
		$sql = " SELECT 
				`group_name` ,
				`folder` ,
				`file` ,
				`status` ,
				fau.last_updated_by AS first_name,
				fau.last_updated_by AS last_name
				FROM  `campaigns`.`ftp_audience_upload` AS fau
				WHERE `campaign_id` = '$campaign_id'
				" ;
		
		$result =  $this->database->query( $sql );
		$key_map = array( "first_name" => "first_name" , "last_name" => "last_name" );
		
		$admin_user = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ADMIN_USER );
		$result = $admin_user->prepareReport( $result, $key_map );
		
		return $result;
	}
	
	public function getGroupsByCampaignId( $campaign_id )
	{
		return GroupDetailModel::getAllByCampaignId( $campaign_id, $this->current_org_id );
	}
	
	/**
	 * Voucher Series Details.
	 * @param unknown_type $voucher_id
	 */
	public function getVoucherSeriesDetailsByVoucherId( $voucher_id , $description ){

		if( empty( $voucher_id ) )
			return array();
		
		$org_id = $this->current_org_id;
		$sql = "SELECT SUM(`num_issued`) AS 'NUM_OF_ISSUED' , 
					   SUM(`num_redeemed`) AS 'NUM_OF_REDEEMED' $description  
			    FROM `voucher_series` WHERE `id` IN ( $voucher_id ) AND `org_id` = $org_id";
		return $this->database->query( $sql );
		
	}
	
	/**
	 * Voucher Series Details.
	 * @param unknown_type $voucher_id
	 */
	public function getVoucherSeriesDetails( $voucher_id , $description ){
		$voucher_series_details = array() ;
		$desc_arr = explode(",", $description) ;
		if( empty( $voucher_id ) )
			return $voucher_series_details;
		
		$org_id = $this->current_org_id;
		$sql = "SELECT count(v.`voucher_id`) AS 'NUM_OF_ISSUED' $description FROM `voucher_series` AS vs LEFT JOIN `voucher` AS v  
				ON ( vs.`org_id` = v.`org_id` AND vs.`id` = v.`voucher_series_id` )
				WHERE vs.`id` IN ($voucher_id) AND vs.`org_id` = $org_id " ;
		$result = $this->database->query( $sql ) ;
		$voucher_series_details[0]['NUM_OF_ISSUED'] = $result[0]['NUM_OF_ISSUED'] ;
		if(!empty($desc_arr[1])){
			$desc = trim($desc_arr[1]) ;
			$voucher_series_details[0][$desc] = $result[0][$desc] ;	
		}
		
		$sql = "SELECT count(*) AS 'NUM_OF_REDEEMED' FROM `voucher_redemptions` 
				WHERE `org_id` = $org_id AND `voucher_series_id` IN ($voucher_id) " ;
		$result = $this->database->query( $sql );		
		$voucher_series_details[0]['NUM_OF_REDEEMED'] = $result[0]['NUM_OF_REDEEMED'] ;
		$this->logger->debug("voucher series details : ".print_r($voucher_series_details,true)) ;
		return $voucher_series_details ;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $group_id
	 */
	public function changeFavouriteTypeForGroup( $group_id ){
		
		$C_campaign_group_model_extension = new CampaignGroupModelExtension();
		$C_campaign_group_model_extension->load( $group_id );
		$fav = $C_campaign_group_model_extension->getIsFavourite();
		
		$C_campaign_group_model_extension->setIsFavourite( !$fav );
		$C_campaign_group_model_extension->update( $group_id );
		
		$peer_id = $C_campaign_group_model_extension->getPeerGroupID();
		if( $peer_id != 0 ){
			$C_campaign_group_model_extension->load( $peer_id );
			$fav = $C_campaign_group_model_extension->getIsFavourite();
			$C_campaign_group_model_extension->setIsFavourite( !$fav );
			$C_campaign_group_model_extension->update( $peer_id );
		}
	}
	
	/**
	 * get campaign details as who created the particular campaign with some details for ajax table
	 * @param int $campaign_id
	 */
	public function getCampaignsMessageDetailsForAjaxTable( $where_filter, $search_filter, $campaign_id ){
		
		$groups = 
			GroupDetailModel::getAllByCampaignId($campaign_id, 
				$this->current_org_id, false, $search_filter);
		$stick_groups = 
			GroupDetailModel::getAllByCampaignId( -20, 
				$this->current_org_id, false, $search_filter );//move through typed interface
		
		//TODO : remove code repitition
		$group_ids = array();
		$campaign_group_details = array();
		foreach( $groups as $group_details ){
		
			$group_id = $group_details['group_id'];
			
			array_push( $group_ids, $group_id );
			$campaign_group_details[$group_id]['group_label'] =
			$group_details['group_label'];
		}
		
		
		foreach( $stick_groups as $group_details ){
		
			$group_id = $group_details['group_id'];
			
			array_push( $group_ids, $group_id );
			$campaign_group_details[$group_id]['group_label'] =
			$group_details['group_label'];
		}
		
		if( count( $group_ids ) > 0 ){
			
			$group_ids_csv = Util::joinForSql( $group_ids );
			$group_id_filter = " AND `mq`.`group_id` IN ( $group_ids_csv )";
		}
		
		$sql = 
			"SELECT 
				`mq`.group_id,`mq`.`type`,`mq`.`params`, 
				`mq`.`type`, `mq`.`scheduled_on`, `mq`.`scheduled_type`, 
				`mq`.`approved`, 
				`mq`.`scheduled_by` AS `Created_by`, 
				`mq`.`status`, `mq`.`id`
			FROM `$this->database_camp`.campaigns_base AS `cb`
			JOIN `$this->database_msging`.`message_queue` AS mq ON (`mq`.`campaign_id` = `cb`.`id` AND `mq`.`org_id` = `cb`.`org_id` )
			WHERE  `cb`.`org_id` = $this->current_org_id $where_filter $group_id_filter ";
		$this->logger->debug( "pv__".$sql );
		$result = $this->database->query( $sql );
		
		for( $i = 0; $i < count($result) ; $i++ ){
		
			$group_id = $result[$i]['group_id'];
			$result[$i]['group_label'] =
			$campaign_group_details[$group_id]['group_label'];
		}
		
		$key_map = array( "Created_by" => "{{ joiner_concat(title,first_name,last_name) }}");
		$admin_user = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ADMIN_USER );
		$result = $admin_user->prepareReport($result, $key_map );
		
		return $result;
	}
	
	/*
	 * Used in store task CustomerStoreTask Class
	 */
	public static function getCampaignIdByOrgid( $org_id , $type = 'query' ){
		
		$db = new Dbase( 'campaigns' );
		
		$sql = "SELECT 
						`id`,`name` 
					FROM `campaigns_base` 
				WHERE `org_id`= '$org_id' AND `active` = 1 ORDER BY `id` DESC";
		
		if($type == 'query'){ 
			
			$res = $db->query( $sql );
		}
		
		foreach ($res as $row)
			$campaign[$row['id']] = $row['name'];
		
		return $campaign;
	}

	/**
	 * 
	 * Used in CustomerStoreTask
	 * @param unknown_type $org_id
	 * @param unknown_type $campaign_id
	 */	
	public static function getAudienceGroupsAsOptionsForCampaign( $org_id , $campaign_id)
	{
		$db = new Dbase( 'msging' );
		
		if(!($campaign_id > 1))
			return array();
			
		$campGroupsSql = GroupDetailModel::getAllByCampaignId($campaign_id, $org_id);

		$campGroups = array();
		foreach($campGroupsSql as $row)
		{
			$key = $row['group_label'] ."Total Custumer:->".$row['customer_count'];
			$campGroups[$key] = $row['group_id'];
		}
		$campGroups['Selected None By default'] = -1;
		
		return $campGroups;
	}
	
	public function getVoucherSeriesDescAndValidTillDate($voucher_series_ids){
		$sql = 
			"SELECT `id` , `description` ,  `valid_till_date` 
     		 FROM campaigns.`voucher_series`
			 WHERE  `id` 
			 IN ( $voucher_series_ids ) 
			 AND org_id = $this->current_org_id";
		
		return $this->database->query( $sql );
		
	}
	
	public function getVoucherSeriesProperties( $voucher_series_ids ){
		$sql = 
			"SELECT `id`,`tag`,`discount_type`,`discount_value`
     		 FROM campaigns.`voucher_series`
			 WHERE  `id` 
			 IN ( $voucher_series_ids ) 
			 AND org_id = $this->current_org_id";
		
		return $this->database->query( $sql );
		
	}
	
	public function getVoucherSeriesPropertiesByVoucherSeriesId( $voucher_series_id ){
		$sql = 
			"SELECT `id`,`tag`,`discount_on`, `discount_value`, `discount_code`,`sms_template`,`discount_type`
     		 FROM campaigns.`voucher_series`
			 WHERE `id` = $voucher_series_id
			 AND org_id = $this->current_org_id";
		
		return $this->database->query( $sql );
		
	}
	
	public function createCampaign($name, $org_id, $type, $start_date, $end_date, $created_by, $active, $created){
	
		$this->logger->debug( "Inside createCampaign >> redirecting to base model insert." );
	
		$campaign_base_model = new CampaignBaseModel();
		$campaign_base_model->setName( $name );
		$campaign_base_model->setOrgId( $org_id );
		$campaign_base_model->setType( $type );
		$campaign_base_model->setStartDate( $start_date );
		$campaign_base_model->setEndDate( $end_date );
		$campaign_base_model->setCreatedBy( $created_by );
		$campaign_base_model->setActive( $active );
	
		return $campaign_base_model->insert();
	}
	
	/**
	 * 
	 * It will delete the campaign by id
	 */
	public function deleteCampaignById( $campaign_id ){

		$sql = "DELETE FROM campaigns.campaigns_base 
					WHERE `org_id` = '$this->current_org_id' AND `id` = '$campaign_id'";
		
		$this->logger->debug( "Inside Delete Campaign sql : ".$sql );
		
		$this->database->update( $sql );
	}
	
	public function getSocialPlatformForOrg(){
		
		$sql = "SELECT sp.`id`,sp.`platform`, sp.`logo_url`,osp.`site_url` 
				FROM masters.`supported_social_platforms` sp 
				JOIN masters.`org_social_platforms` osp 
				ON sp.`id` = osp.`social_platform_id` WHERE `org_id` = $this->current_org_id";
		
		return $this->database->query( $sql );
	}
	
	public function getSupportedSocialPlatform(){
		
		$sql = "SELECT * FROM masters.`supported_social_platforms`";
		
		return $this->database->query( $sql );
	}
	
	public function getVoucherDetails( $voucher_series_id , $voucher_code , $org_id ){
		include_once 'helper/coupons/CouponManager.php' ;
		
		$voucher_select_filter = "";
		$voucher_where_filter = "";
		$voucher_join_filter = "";

		$coupon_manager = new CouponManager() ;

		if( $voucher_code ){
			//use coupon created date to get valid days for voucher expiry
			$coupon_manager->loadByCode( $voucher_code ) ;
			//get approx valid days for voucher expiry
			$valid_days_from_create = $coupon_manager->getValidDaysForStrategy() ;
			$voucher_select_filter = " v.issued_to, 
									   v.created_date, 
									   v.voucher_code, ";
		 	$voucher_where_filter =  " AND v.org_id = $org_id AND v.voucher_code = '$voucher_code'";
		 	$voucher_join_filter =   " JOIN campaigns.voucher as v   
				 	  				   ON (	vs.org_id = v.org_id 
				 	  				   AND vs.id = v.voucher_series_id  )
		 						    ";
		}
		else{
			//get approx valid days for voucher expiry
			$valid_days_from_create = $coupon_manager->getValidDaysForStrategy( $voucher_series_id ) ;
		}
		
		$sql = "
					  SELECT  $voucher_select_filter   						
					 		 vs.valid_till_date,
							 $valid_days_from_create AS valid_days_from_create 
					  FROM campaigns.voucher_series as vs
					  $voucher_join_filter 
					  WHERE vs.id = $voucher_series_id 
					 	 $voucher_where_filter
				";
		
		return $this->database->query_firstrow( $sql );
	}
	
	/**
	 * @deprecated
	 * @param unknown $campaign_id
	 * 
	 * support for older reports
	 */
	public function getMsgingUsageReport( $campaign_id ){

		$group_details =
			GroupDetailModel::getAllDetailsByCampaignId($campaign_id, $this->current_org_id);
		$group_ids = array();
		if( count( $group_details ) < 0 ){
			return array();
		} 
		
		$group_details_map = array();
		foreach( $group_details as $group ){
			
			array_push( $group_ids, $group['group_id'] );
			$group_details_map[$group['group_id']] = $group;
		}
		
		$group_ids_csv = implode( ",", $group_ids );
		
		$sql = " SELECT 
					o.`messageId`,o.type, '' AS `customer_list` ,o.messageText AS subject, 
					'' AS customer_count, '' AS channel, o.`numDeliveries` AS processed_count, 
					numDeliveries AS delivered_count, 0 AS skipped_count, o.status AS running_state, 
					o.createdTime AS queued_time, o.categoryIds
				 FROM $this->database_msging.`outboxes` AS o
				 WHERE `categoryIds` IN ( $group_ids_csv )
				 GROUP BY o.`messageId`";
		
		$result = $this->database->query($sql);

		$msg_ids = array();
		foreach( $result as &$outbox ){
			
			$group_detail = $group_details_map[$outbox['categoryIds']];
			
			$params_array = json_decode( $group_detail['params'], true );
			$email_count = $params_array['email'];
			$mobile_count = $params_array['mobile'];
			
			$outbox['channel'] = "EMAIL ($email_count) MOBILE($mobile_count)";
			$outbox['customer_list'] = $group_detail['group_label'];
			$outbox['customer_count'] = $group_detail['customer_count'];
			
			unset($outbox['categoryIds']);
			array_push( $msg_ids, $outbox['messageId']);
		}
		
		$msg_ids_csv = implode( ",", $msg_ids );
		
		$sql = "SELECT COUNT( * ) AS skipped_count, outbox_id
				FROM $this->database_msging.inbox_skipped
				WHERE org_id = $this->current_org_id AND outbox_id IN ( $msg_ids_csv ) AND last_updated_on >= DATE(NOW())
				GROUP BY outbox_id
				";
		$skipped_entries = $this->database->query_hash($sql, 'outbox_id', 'skipped_count' );
		$this->logger->debug( "pv_data".print_r( $skipped_entries, true ) );
		foreach( $result as &$outbox ){
			
			$outbox['skipped_count'] = ( int ) $skipped_entries[$outbox['messageId']];
			unset($outbox['messageId']);
		}
				
		$this->logger->debug( "pv_data".print_r( $result, true ) );
		return $result;
	}
	
	/**
	 * returns email(subject) list for campaign id
	 * @param unknown_type $campaign_id
	 */
	public function getEmailCampaignList( $campaign_id ){
		
		$sql = "SELECT  ob.`messageId`, ob.messageText AS subject
				
				FROM  `msging`.`email_stats` AS es
		
				JOIN  `msging`.`outboxes` AS ob ON ( es.group_id = ob.categoryIds 
					AND es.msg_id = ob.messageId )
				WHERE es.campaign_id =  '$campaign_id'
				";
		
		return $this->database->query_hash( $sql, 'subject' , 'messageId' );
	}
	/**
	 * return overview of email message sent for a campaign
	 * @param unknown_type $campaign_id
	 * @param unknown_type $msg_id
	 */
	public function getEmailOverviewDetails( $campaign_id , $msg_id ){
		
		$group_details = GroupDetailModel::getAllDetailsByCampaignId($campaign_id, 
								$this->current_org_id);
		$group_ids = array();
		if( count( $group_details ) < 0 ){
			return array();
		}
		
		$group_details_map = array();
		foreach( $group_details as $group ){
				
			array_push( $group_ids, $group['group_id'] );
			$group_details_map[$group['group_id']] = $group;
		}
		
		$sql = "SELECT  `es`.`read_count` as opened, `ob`.`messageId`, `ob`.`messageText` AS subject, 
						`ob`.`numDeliveries` AS delivered_count, `es`.`group_id`, `el`.`url` , 
						SUM(`el`.`clicks`) as clicks	
				FROM  `msging`.`email_stats` AS `es`

				JOIN  `msging`.`outboxes` AS `ob` ON ( `es`.`group_id` = `ob`.`categoryIds` 
															AND `es`.`msg_id` = `ob`.`messageId` ) 
				JOIN  `msging`.`email_links_redirection` AS `el` 
						ON ( `el`.`message_id` = `es`.`msg_id` )
				WHERE `es`.`campaign_id` =  '$campaign_id' AND `es`.`msg_id` = '$msg_id' 
       
        		GROUP BY `es`.`msg_id`,`el`.`url`
			";
		
		$result = $this->database->query($sql);

		foreach( $result as &$outbox ){
			
			$group_details = $group_details_map[$outbox['group_id']];
			
			$outbox['sent_count'] = $group_details['params'];
			$outbox['group_label'] = $group_details['group_label'];
		}
		
		$this->logger->debug( "over view data".print_r( $result, true ) );
		return $result;
	}
	
	/**
	 * get active campaigns for all org
	 */
	public function getActiveCampaignForAllOrg(){
		
		$sql =
			"SELECT `id`, `org_id` , `name`, `type`, `created`, 
					`start_date`, `end_date`, `active` 
			FROM `campaigns_base`
			WHERE `active` = 1
				AND `end_date` >= DATE(NOW())
			";
		
		return ShardedDbase::queryAllShards($this->database_camp, $sql, true );
	}
	
	public function getInboxSkippedMessageCount($start_date, $end_date, $campaign_id, $org_id) {
		$group_details =
			GroupDetailModel::getAllDetailsByCampaignId($campaign_id, $org_id);
		$group_ids = array();
		if( count( $group_details ) <= 0 ){
			return 0;
		} 
		foreach( $group_details as $group ){
			array_push( $group_ids, $group['group_id'] );
		}
		
		$group_ids_csv = implode( ",", $group_ids );
		$sql = " SELECT 
					o.`messageId`, 0 AS skipped_count
				 FROM $this->database_msging.`outboxes` AS o
				 WHERE `categoryIds` IN ( $group_ids_csv )
				 GROUP BY o.`messageId`";
		$result = $this->database->query($sql);
		$msg_ids = array();
		foreach( $result as &$outbox ){
			array_push( $msg_ids, $outbox['messageId']);
		}
		$msg_ids_csv = implode( ",", $msg_ids );
		
		$sql = "SELECT COUNT( * ) AS skipped_count, outbox_id
				FROM $this->database_msging.inbox_skipped
				WHERE org_id = $org_id AND outbox_id IN ( $msg_ids_csv ) 
				AND last_updated_on >= '$start_date 00:00:00' AND last_updated_on <= '$end_date 23:59:59' 
				GROUP BY outbox_id
				";
		$skipped_entries = $this->database->query_hash($sql, 'outbox_id', 'skipped_count' );
		$skipped_counts = array();
		
		foreach( $result as &$outbox ){
			array_push($skipped_counts, ( int ) $skipped_entries[$outbox['messageId']]);
			unset($outbox['messageId']);
		}
		return array_sum($skipped_counts);
	}
	
	public function getControlGroupsByCampaignID( $campaign_id , $favourite, $search_filter ){
		
		$group_type = array( 'CONTROL' );
		
		$group_details =
			GroupDetailModel::getAllByCampaignId($campaign_id, 
					$this->current_org_id, $favourite, $search_filter, $group_type);
		$groups = array();
		
		foreach( $group_details as $group ){
			$groups[ $group['group_id'] ] = $group;	
		}
		
		$this->logger->debug( " get control groups " . print_r( $groups , true ) );
		
		return $groups;
	}
	
	/**
	 * Get Report data of forward to friends email.
	 * @param unknown $campaign_id
	 */
	public function getForwardToFriendByCampaignId( $campaign_id, $message_id,
													$org_id, $is_sql = false ){
		
		$sql = "SELECT ff.`id`,
					   ff.`user_id`, 
					   ff.`outbox_id`,	
					   ff.campaign_id,
					   ff.nsadmin_id,
					   ff.message,
					   ff.receiver,
					   ff.sender,
					   ff.receiver_name,
					   ff.token,
					   ff.sent_time,
					   count( ff.`user_id` ) 'count' 
				FROM $this->database_msging.`forward_to_friend_log` AS ff
				JOIN $this->database_msging .`outboxes` AS ob
				ON ff.`outbox_id` = ob.`messageId`
				AND ff.`org_id` = ob.`publisherId` 
				WHERE ff.`campaign_id` = '$campaign_id'
				AND ff.`org_id` = $org_id
				AND ff.`outbox_id` = '$message_id'
				GROUP BY ff.`user_id`, 
						 ff.`outbox_id`,
						 ff.`org_id`,
						 ff.`campaign_id`,
						 ff.`sender`,
						 ff.`receiver` 
				ORDER BY count DESC 
			";
		
		if( $is_sql )
			return $sql;
		
		$sql .= ' LIMIT 0,100';
		return $this->database->query( $sql );
	}
	
	public function getOrgSkippedMessageCount($start_date, $end_date, $org_id) {	
		$sql = "SELECT COUNT(*) as count
			FROM $this->database_msging.inbox_skipped 
			WHERE org_id = ". $org_id ." AND last_updated_on >= '". $start_date ." 00:00:00' AND last_updated_on <= '". $end_date." 23:59:59'";
		$result = $this->database->query($sql);
		
		return $result;
	}
	
	/**
	 * returns email(subject) list for campaign id
	 * @param unknown_type $campaign_id
	 */
	public function getEmailCampaignListAsOptions( $campaign_id ){
	
		$sql = "SELECT  ob.`categoryIds` , ob.`messageId`, ob.messageText AS subject
				FROM  `$this->database_msging`.`email_stats` AS es
				JOIN  `$this->database_msging`.`outboxes` AS ob ON ( es.group_id = ob.categoryIds
				AND es.msg_id = ob.messageId )
				WHERE es.campaign_id =  '$campaign_id'
		";
		
		return $this->database->query( $sql );
	}
	
	public function getForwardedByOutBoxId( $sender , $campaign_id , $org_id ){
		
		$sql = "SELECT * FROM `$this->database_msging`.`forward_to_friend_log`
				WHERE `sender` = '$sender' 
				AND `campaign_id` = '$campaign_id' 
				AND `org_id` = $org_id";
		
		return $this->database->query( $sql );
	}
	
	/**
	 * returns the temp table name for a given id selected and the type of the table
	 * @param unknown_type $id
	 * @param unknown_type $audience_or_coupon	 
	 */
	
	public function getTempTableNameFromId($id,$audience_or_coupon){
		
		if(strcmp($audience_or_coupon,"audience") == 0){
				
			$sql = "SELECT temp_table_name AS ttn
			FROM `campaigns`.`upload_files_history` AS `ufh`
			WHERE ufh.`id` = $id " ;
				
		}
		else{
				
			$sql = "SELECT temp_table_name AS ttn
			FROM `campaigns`.`coupon_upload_history` AS `cuh`
			WHERE cuh.`id` = $id " ;
				
		}
		
		$fetch = 	$this->database->query( $sql );
		return $fetch[0]['ttn'];
		
	}
	/**
	 * returns all the files uploaded for a given campaign id between the start and the end date provided
	 * @param unknown_type $campaign_id
	 * @param unknown_type $org_id
	 * @param unknown_type $start_date
	 * @param unknown_type $end_date
	 */
	public function getUploadFileList( $campaign_id,$audience_or_coupon ){	 
	    
		if(strcmp($audience_or_coupon,"audience") == 0){
				$sql = "SELECT id,campaign_id,group_name,upload_type,import_type,added_on 
		        	FROM `campaigns`.`upload_files_history` AS `ufh` 
					WHERE ufh.`campaign_id` = $campaign_id 
					";
		}
		else{
				$sql = "SELECT id,campaign_id,vsid AS `coupon_series_id`,import_type,added_on
					FROM `campaigns`.`coupon_upload_history` AS `cuh`
					WHERE cuh.`campaign_id` = $campaign_id
			";
		}
			
		return $this->database->query( $sql );
	}
	
	/**
	 * returns 10 error fields from the Temp db for the selected id from upload_files_history table
	 * @param unknown_type $id	
	 */
	public function getUploadErrorTable($id,$audience_or_coupon) {		
		
		$fetch_table_name =  $this->getTempTableNameFromId($id,$audience_or_coupon);
		if(strcmp($audience_or_coupon,"audience") == 0){
		$sql = "SELECT *
					FROM $fetch_table_name AS `ufh`
					WHERE ufh.`status` = 0
					LIMIT 10 " ;
		}
		else {
			$sql = "SELECT id,status,error,mobile,pin_code,user_id,voucher_code AS `coupon_code`,fin_voucher_code AS `fin_coupon_code`,external_id
			FROM $fetch_table_name AS `ufh`
			WHERE ufh.`status` = 0
			LIMIT 10 " ;
		}
		return	$this->database->query( $sql );
	
	}
	
	/**
	 * returns summary of errors from the Temp db for the selected id from upload_files_history table
	 * @param unknown_type $id
	 */
	public function getUploadErrorSummary($id,$audience_or_coupon) {
		
		$fetch_table_name =  $this->getTempTableNameFromId($id,$audience_or_coupon);
	
		$sql = "SELECT error, COUNT(status) AS count 
		FROM $fetch_table_name AS `ufh`
		WHERE ufh.`status` = 0
		GROUP BY error " ;
		
		
		return	$this->database->query( $sql );
	
	}
	
	/**
	 *
	 * @param $campaignIds
	 * @param $type : @deprecated
	 */
	public function getCampaignGroupsAllDetailsByCampaignIds( $campaign_ids, $type = 'query' ){
	
		return CampaignGroupModelExtension::getAllDetailsByCampaignId( $campaign_ids, $this->current_org_id );
	}
	
	public function getCampaignNamesById($campaign_ids) {
		$sql = "SELECT `id`, `name` FROM campaigns_base WHERE id IN (".
				implode(', ', $campaign_ids) .") ORDER BY id DESC";
		return $this->database->query_hash($sql, 'id', 'name');
	}

	public function getCampaignNamesByIdAllShards($campaign_ids) {
		$sql = "SELECT `id`, `name` FROM campaigns_base WHERE id IN (".
				implode(', ', $campaign_ids) .") ORDER BY id DESC";
		$result =  ShardedDbase::queryAllShards($this->database_camp, $sql, true);
		$ret_results = array();
		foreach ($result as $value) {
			$ret_results[$value["id"]] = $value["name"];
		};
		$this->logger->debug("getCampaignNamesByIdAllShards ".print_r($ret_results,true));
		return $ret_results;
	}
	
	private function getGroupLevelDetails( 
			$campaign_id, $org_id, $group_ids = array() ){

		$this->logger->debug( 'Group ID array passed : ' . 
				print_r( $group_ids, true ) );
		
		$selection_filter_sql =
		"
			SELECT
				GROUP_CONCAT( sf.filter_type ) AS 'filter_type',
				GROUP_CONCAT( sf.filter_explaination ) as 'explanation',
				ag.campaign_id, ag.params AS group_id
			FROM  `campaigns`.`selection_filter` AS sf
			JOIN  `campaigns`.`audience_groups` AS ag ON
				( campaign_id = {{CAMPAIGN_ID}} AND
				sf.audience_group_id = ag.id AND
				sf.org_id = ag.org_id )
			WHERE 	`ag`.`params` IN ( {{GROUP_ID_CSV}} ) AND sf.org_id = $org_id
			GROUP BY `sf`.`audience_group_id`
		";
		
		/*$group_details = GroupDetailModel::getAllByCampaignId( $campaign_id );*/
		$group_details = GroupDetailModel::getAllByIds( $group_ids );
		$this->logger->debug( 
				"Tracking : group details from css : " . print_r( $group_details, true ) );
		
		$filter_details = array();
		$group_id_array = array();
		foreach( $group_details AS $key => $group_detail_value ) {
		
			$group_detail['group_id'] = $group_detail_value['group_id'];
			$group_detail['audience_group'] = $group_detail_value['group_label'];
		
			if( strtoupper( $group_detail_value['type'] ) != 'LOYALTY' ){
					
				$group_detail['filter_type'] = '--';
				$group_detail['explanation'] = _campaign('All registered customers');
			} else{
				
				array_push( $group_id_array, $group_detail['group_id'] );
				continue;
			}
			
			$filter_details[$group_detail['group_id']] = $group_detail;
		}
		
		if ( !empty( $group_id_array ) ) {
			
			$group_id_csv = implode( ',', $group_id_array );
			$selection_filter_sql = Util::templateReplace( $selection_filter_sql, 
				array (
					'CAMPAIGN_ID' => $campaign_id,
					'GROUP_ID_CSV' => $group_id_csv 
				) );
			$selection_filter_result = $this->database->query( $selection_filter_sql );
			
			foreach( $selection_filter_result AS $key => $sf_result ) {
				
				$detail = array ();
                $detail = $filter_details[$sf_result['group_id']];
                $detail['filter_type'] = $sf_result['filter_type'];
				$detail['explanation'] = $sf_result['explanation'];
				$filter_details[$sf_result['group_id']] = $detail;
			}
		}
		
		return $filter_details;
	}
	
	private function getMessageLevelDetails( $org_id, $start_date, $end_date ) {
		
		$message_level_details =
			VenenoDataDetailsHandler::getAllMessagesByCampaignID( 
					$org_id, false, $start_date, $end_date );
		
		$message_details = array();
		foreach( $message_level_details AS $key => $message_level_detail ){
				
			$detail = array();
			$fetched_cd_id = $message_level_detail['id'];
			$detail['total_audience'] = $message_level_detail['expected_delivery_count'];
			$detail['message_text'] = $message_level_detail['subject'];
			$detail['campaign_id'] = $message_level_detail['campaign_id'];
			$detail['group_id'] = $message_level_detail['recipient_list_id'];
			$detail['received_time'] = $message_level_detail['recieved_time'];
				
			$message_details[$fetched_cd_id] = $detail;
		}
		
		return $message_details;
	}
	
	private function sanitizeGroupDetails( $group_id, $group_details = array() ) {
		
		$msg_grp_details = array();
		$msg_grp_details['group_id'] = $group_id;
		$msg_grp_details['audience_group'] =
			( $group_details['audience_group'] )?
			( $group_details['audience_group'] ):
			"--";
		$msg_grp_details['filter_type'] =
			( $group_details['filter_type'] )?
			( $group_details['filter_type'] ):
			"--";
		$msg_grp_details['explanation'] =
			( $group_details['explanation'] )?
			( $group_details['explanation'] ):
			"--";
		
		return $msg_grp_details;
	}

	private function getCampaignLevelDetails( $campaign_ids = array(), $org_id ) {
		
		$campaign_id_csv = implode( ',', $campaign_ids );
		
		$campaigns_sql =
		"
			SELECT
				cb.name as campaign_name,
				cb.created_by as campaign_created_by,
				'Bulk' as campaign_type,
				DATE_FORMAT(cb.`start_date`,'%b %d, %Y') as 'campaign_start_date',
				DATE_FORMAT(cb.`end_date`,'%b %d, %Y') as 'campaign_end_date',
				cb.id as intouch_campaign_id,
				CASE WHEN cb.`voucher_series_id` = -1 THEN '--' ELSE cb.`voucher_series_id` END as coupon_series_id
			FROM `campaigns`.`campaigns_base` cb
			WHERE cb.type IN ( 'outbound' ) AND cb.id IN ( $campaign_id_csv )
				AND cb.org_id = '$org_id' ";
		
		$campaigns_result = 
			$this->database->query_hash( 
					$campaigns_sql, 'intouch_campaign_id', 
					array( 	'campaign_name', 'campaign_created_by', 'campaign_type',
							'campaign_start_date', 'campaign_end_date', 'intouch_campaign_id', 
							'coupon_series_id' ) );
		
		return $campaigns_result;
	}
	
	/**
	 * 
	 * Campaign Tracker Details
	 */
	public function getCampaignTrackingDetails( $org_id , $start_date , $end_date ){
		
		$campaign_id_details = array();
		$overall_campaign_details = array();
		
		$message_details = 
			$this->getMessageLevelDetails( $org_id, $start_date, $end_date );
		
		$this->logger->debug( "Tracking : message details : " . print_r( $message_details, true ) );
		
		if( empty( $message_details ) )
			return array();
		
		$campaign_group_array = array();
		foreach ( $message_details AS $key => $details ) {
			
			$cid = $details['campaign_id'];
			if ( !array_key_exists( $cid, $campaign_group_array ) )
				$campaign_group_array[$cid] = array();
			array_push( $campaign_group_array[$cid], $details['group_id'] );
		}
		
		$group_details = array();
		foreach ( $campaign_group_array AS $cid => $group_id_array ) {
			
			$fetched_group_details = 
				$this->getGroupLevelDetails( $cid, $org_id, array_unique ( $group_id_array ) );
			$this->logger->debug( 
					"Tracking : group details in loop : " . print_r( $fetched_group_details, true ) );
			$group_details =  $group_details + $fetched_group_details;
		}
		
		$this->logger->debug( "Tracking : group details : " . print_r( $group_details, true ) );
		
		$campaign_ids = array_keys( $campaign_group_array );
		$campaign_details = $this->getCampaignLevelDetails( $campaign_ids, $org_id );
		
		$this->logger->debug( "Tracking : campaign details : " . print_r( $campaign_details, true ) );
		
		foreach ( $message_details AS $cd_id => $cd_details ) {
				
				$msg_grp_details = 
					$this->sanitizeGroupDetails( 
							$cd_details['group_id'], $group_details[$cd_details['group_id']] );
				
				$temp = array();
				$temp = array_merge( 
							array_merge( 
									$campaign_details[$cd_details['campaign_id']], 
									$msg_grp_details 
						), $cd_details );
				if ( !empty( $temp ) )
					array_push( $overall_campaign_details, $temp );
				unset( $temp );
		}
		
		$this->logger->debug( "Tracking : overall campaign details : " . 
				print_r( $overall_campaign_details, true ) );
		
		//CONCAT(au.first_name,' ',au.last_name) 
		$key_map = array("campaign_created_by" => "{{ joiner_concat(first_name,last_name) }}");
		$admin_user = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ADMIN_USER );
		$result = $admin_user->prepareReport( $overall_campaign_details, $key_map );

		$this->logger->debug( "Tracking : results being passed : " .
				print_r( $result, true ) );
		
		return $result;
	}
	
	public function getExpiredCampaignForHealthDashboard( $days ){
		
		$gap = date('Y-m-d',strtotime('-'.$days.' days',strtotime(date('Y-m-d'))));
		
		$sql ="SELECT `id`, `org_id` , `name`, `type`, `created`,".
				"`start_date`, `end_date`, `active`".
				"FROM `campaigns_base`".
				"WHERE `end_date` < DATE(NOW()) AND `end_date` >= '$gap'";

		// Need to query all shards
		return ShardedDbase::queryAllShards($this->database_camp, $sql, true);
		//return $this->database->query( $sql );
	}
	
	public function getActiveCampaignsByTypeForOrg( $type, $org_id ){
		
		$sql ="SELECT `id`, `name` FROM `campaigns`.`campaigns_base` ".
				"WHERE `active`=1 AND `type`='$type' AND `org_id`= $org_id ".
				"AND `end_date` >= DATE(NOW()) ";
		
		return $this->database->query_hash($sql, 'name', 'id');
	}
	
	public function addCampaignMapping( $id, $ref_id ){
		
		$sql = "INSERT INTO `campaigns`.`referral_mapping` ".
				"( `org_id`, `campaign_id`, `ref_campaign_id` ) ".
				"VALUES( '$this->current_org_id', '$id', '$ref_id' )";
		
		$id = $this->database->insert($sql);
	}
	
	public function getCampaignMapping( $campaign_id ){
		
		$sql = "SELECT * FROM `campaigns`.`referral_mapping` ".
				"WHERE `campaign_id` = $campaign_id AND `org_id` = $this->current_org_id ";
		
		return $this->database->query_firstrow($sql);
	}

	public function addSurveyCampaignMapping($id, $ref_id){
		
		$sql = "INSERT INTO `campaigns`.`survey_mapping` ".
				"(`org_id`,`campaign_id`, `ref_campaign_id`) ".
				"VALUES('$this->current_org_id','$id', '$ref_id')";
		
		$id = $this->database->insert($sql);
	}
	
	public function getSurveyCampaignMapping($campaign_id){
		
		$sql = "SELECT * FROM `campaigns`.`survey_mapping` ".
				"WHERE `campaign_id` = $campaign_id AND `org_id` = $this->current_org_id ";
		
		return $this->database->query_firstrow($sql);
	}
	
	/**
	 * check if ga name is exist or not.
	 */
	public function isGaNameExists( $ga_name , $org_id ){
		
		$sql = "SELECT * from `campaigns`.`campaigns_base` 
				WHERE `ga_name` = '$ga_name' AND `org_id` = '$org_id'
				AND `is_ga_enabled` = 1";
		
		return $this->database->query_scalar( $sql ); 
	}

	public function getReferralCampaignExpiringByDate($date){
		
		$sql = "SELECT * FROM `campaigns`.`campaigns_base` ".
				"WHERE `type` = 'referral' AND DATE(`end_date`) = '$date'";
		
		return $this->database->query($sql);
	}
	
	public function getCampaignDetail($org_id, $start_date_range, $end_date_range) {
		
		$this->logger->debug("date range is final" . $start_date_range ."\t end" .$end_date_range);
		if (($start_date_range != null) and ($end_date_range != null)) {
			
			$where_clause = "		
							AND   cb.`start_date`  BETWEEN
								  '$start_date_range' AND '$end_date_range'
						   ";
		}
		
		$sql = "
					SELECT  cb.`id` AS `id`,
							cb.`name` AS `campaign_name`,
							cb.`start_date`
					FROM    `campaigns`.`campaigns_base` AS cb
					WHERE   cb.`org_id` = $org_id
							$where_clause
				";
		
		$this->logger->debug("date range is" .$sql);
		
		return $this->database->query( $sql );
	}
	
	public function getCampaignNameByCampaignIdCSV( $campaign_id_csv ) {
		
		$sql = "
				SELECT  cb.`id` AS `campaign_id`,
					    cb.`name` AS `campaign_name`
				FROM 
						`campaigns`.`campaigns_base` AS cb
				WHERE
						cb.`id` IN ( $campaign_id_csv )
				";
		return $this->database->query_hash($sql, 'campaign_id', 'campaign_name');
	}
	
	/**
	 *
	 * @param unknown_type $org_id
	 * @param unknown_type $where_filter
	 * @param unknown_type $search_filter
	 * @return Ambigous <multitype:, boolean>
	 */
	public function getDataForNewHomePage( $org_id, $where_filter, $search_filter , $limit_filter = false ){
	
		//if search filter is applied donot set limit.
		if( $search_filter )
			$limit_filter = '';
	
		//Extract campaign and voucher data for all campaigns
		$sql = "
		SELECT
		cb.`id` ,
		cb.`id` AS campaign_id ,
		cb.`name` AS campaign_name ,
		cb.`type` AS campaign_type ,
		cb.`start_date` ,
		cb.`end_date` ,
		vs.`id` AS first_voucher_series_id,
		vs.`description` ,
		COUNT(vs.`id`) AS number_of_vs_attached,
		SUM(vs.`num_issued`) AS total_issued,
		SUM(vs.`num_redeemed`) AS total_redeemed
		 
		FROM  `campaigns`.`campaigns_base` AS cb
		LEFT OUTER JOIN `campaigns`.`voucher_series` AS vs ON cb.`id` = vs.`campaign_id`
		WHERE cb.`org_id` = $org_id $where_filter $search_filter
		GROUP BY cb.`id` ORDER BY cb.`id` DESC $limit_filter
		";
	
		$result = $this->database->query( $sql );
		
		$this->logger->debug( "CAMPAIGN home table homepage result".$sql." the result is".print_r($result, true) );
		
		return $result;
	}
	public function fetchWeChatAccounts($org_id) {
		$sql = "SELECT * FROM masters.wechat_account_configuration WHERE org_id = '$org_id' && is_active = 1";

		$rows = $this->database->query($sql);
		
		return $rows;
	}
	/**
	 * get campaign details as who created the particular campaign with some details
	 * @param int $campaign_id
	 */
	public function getCampaignsMessageDetailsByCampaignId( $org_id , $campaign_id ){
	
		$groups =
		GroupDetailModel::getAllByCampaignId($campaign_id,
				$org_id, false, false);
		$stick_groups =
		GroupDetailModel::getAllByCampaignId( -20,
				$org_id, false, false );//move through typed interface
	
		$group_ids = array();
		$campaign_group_details = array();
		foreach( $groups as $group_details ){
	
			$group_id = $group_details['group_id'];
				
			array_push( $group_ids, $group_id );
			$campaign_group_details[$group_id]['group_label'] =
			$group_details['group_label'];
		}
	
		foreach( $stick_groups as $group_details ){
	
			$group_id = $group_details['group_id'];
				
			array_push( $group_ids, $group_id );
			$campaign_group_details[$group_id]['group_label'] =
			$group_details['group_label'];
		}
	
		if( count( $group_ids ) > 0 ){
				
			$group_ids_csv = Util::joinForSql( $group_ids );
			$group_id_filter = " AND `mq`.`group_id` IN ( $group_ids_csv )";
		}
	
		$sql =
			"SELECT
			`mq`.group_id,`mq`.`type`,`mq`.`params`,`mq`.`default_arguments`,
			`mq`.`type`, `mq`.`scheduled_on`, `mq`.`scheduled_type`,
			`mq`.`approved`,
			`mq`.`scheduled_by` AS `Created_by`,
			`mq`.`status`, `mq`.`id`,`mq`.`guid`,`mq`.`last_updated_on`,
			`mq`.`approved_by` AS `Approved_by`
			FROM `$this->database_camp`.campaigns_base AS `cb`
			JOIN `$this->database_msging`.`message_queue` AS mq ON (`mq`.`campaign_id` = `cb`.`id` AND `mq`.`org_id` = `cb`.`org_id` )
			WHERE  `cb`.`org_id` = '$org_id' AND `cb`.`id` = '$campaign_id' $group_id_filter ORDER BY `mq`.`id` DESC";
		
		$this->logger->debug( "pv__".$sql );
		
		$result = $this->database->query( $sql );
	
		for( $i = 0; $i < count($result) ; $i++ ){
	
			$group_id = $result[$i]['group_id'];
				$result[$i]['group_label'] =
					$campaign_group_details[$group_id]['group_label'];
		}
		
		$key_map = array( "Created_by" => "{{ joiner_concat(title,first_name,last_name) }}",
							"Approved_by" => "{{ joiner_concat(title,first_name,last_name) }}"
						);
		
		$admin_user = MemoryJoinerFactory::getJoinerByType( MemoryJoinerType::$ADMIN_USER );
		$result = $admin_user->prepareReport($result, $key_map );
		return $result;
	}
	
	public function addSubjectToIndex( $subject ){
		
		$sql = "INSERT INTO `$this->database_msging`.`msg_subject_index`(`org_id` ,`subject`)
				VALUES($this->org_id,'$subject')";
		
		return $this->database->insert( $sql );
	}
	
	public function getSubjectList( $subject , $org_id ){
		
		$sql = "SELECT `id`,`subject` FROM `$this->database_msging`.`msg_subject_index`
				WHERE `org_id` = $org_id AND `subject` LIKE '%$subject%'";

		return $this->database->query( $sql );
	}
	
	public function createListForDuplication( $org_id, $type_filter, $where_filter,
			$search_filter , $limit_filter = false ){
	
		if( $search_filter )
			$limit_filter = '';
	
		$sql = "
		SELECT cb.`id`, cb.`id` AS campaign_id, cb.`name` AS campaign_name,
		cb.`start_date`, cb.`end_date`
		FROM  `campaigns`.`campaigns_base` AS cb
		WHERE cb.`org_id` = $org_id AND cb.type = 'outbound' $where_filter $search_filter
		ORDER BY cb.`id` DESC $limit_filter";
				
		$this->logger->debug( "CAMPAIGN dup list result sql ".print_r($sql, true) );
			
		$result = $this->database->query( $sql );
		
		/*Filter out only campaigns which contain lists */
		$group_details = new GroupDetailModel();
		$list_results = array();
		
		foreach($result as $res){
			$group_results = $group_details->getAllByCampaignId($res['campaign_id']);
			if(!empty($group_results)){
				array_push($list_results,$res);
			}
		}
	
		$this->logger->debug( "CAMPAIGN dup list result set is".print_r($list_results, true) );
				
		return $list_results;
	}
	
	public function getCallTaskForCampaign( $campaign_id, $org_id ) {
		 
		$sql = "SELECT mq.`id`, mq.`params`
				FROM `$this->database_msging`.`message_queue` AS mq
				WHERE mq.`org_id` = $org_id
				AND mq.`campaign_id` IN ( $campaign_id )
				AND mq.`type` IN ('CALL_TASK_REMINDER', 'CALL_TASK') ";
		
		return $this->database->query( $sql );
	}
	
	public function getTaskIdForMsgIds( $message_id_csv, $org_id ) {
		
		$sql = "SELECT `task_id`
				FROM `$this->database_users`.`task_campaign_mapping`
				WHERE `msg_id` IN ( $message_id_csv )";
		
		return $this->database->query( $sql );
	}
	
	public function getCampaignsByMessageType( $org_id, $type,
			$start_date, $end_date ) {
		
		$this->logger->debug( "date range is getCampaignsByMessageType 
				 $start_date_range  end $end_date_range" );
		
		if ( ( $start_date != null ) and ( $end_date != null ) ) {
			
			$where_clause = "AND cb.`start_date`  BETWEEN
								'$start_date' AND '$end_date'";
		}
		
		if( $type != "" )
			$type_condition = 
				"AND mq.`type` IN ( $type )";
		
		$sql = "SELECT cb.`id` AS `id`,
					   cb.`name` AS `campaign_name`,
					   cb.`start_date`
				FROM campaigns.`campaigns_base` AS cb
				JOIN msging.`message_queue` AS mq
				ON mq.`campaign_id` = cb.`id`
				WHERE cb.`org_id` = $org_id
				$type_condition
				$where_clause";
		
		$this->logger->debug( "final sql is" .$sql );
		
		return $this->database->query( $sql );
	}
	
	public function getMessagesByCampaign(
			$campaign_ids, $message_type, $org_id ) {
		
		$sql = "SELECT `ob`.`messageId` AS `id`, 
				`ob`.`messageText` AS `subject`
				FROM `msging`.`outboxes` AS ob
				JOIN `msging`.`group_details` AS gds
				ON `ob`.`categoryIds` = `gds`.`group_id`
				WHERE `ob`.`publisherId` = $org_id
				AND `ob`.`type` = '$message_type'
				AND `gds`.`campaign_id` IN ( $campaign_ids )";
		
		$this->logger->debug( "final sql is $sql" );
		
		return $this->database->query_hash( $sql, 'id', 'subject' );
	}
	
	public function getMessagesByCampaignId( $org_campaigns = array() ) {
	
		$this->logger->debug("org_campaings".print_r($org_campaigns,true));
		$org_campaign_str = "";
		if( count($org_campaigns) > 1 ){
			$org_campaign_str .= "( " . implode(",", $org_campaigns) . " )";
		}else{
			$org_campaign_str = $org_campaigns[0];
		}
		$sql = "SELECT * FROM msging.message_queue 
				WHERE (org_id, campaign_id) IN $org_campaign_str";
	
		$this->logger->debug( "final sql is $sql" );
	
		return ShardedDbase::queryAllShards("msging", $sql);
	}

	public function getMessageCampaignIdHash( $message_ids, $org_id ) {
		
		$sql = "SELECT `ob`.`messageId` AS `id`,
			    `gds`.`campaign_id` AS `campaign_id`
				FROM `msging`.`outboxes` AS ob
				JOIN `msging`.`group_details` AS gds
				ON `ob`.`categoryIds` = `gds`.`group_id`
				WHERE `ob`.`publisherId` = $org_id 
				AND `ob`.`messageId` IN ( $message_ids )";
		
		$this->logger->debug( "sql is $sql" );
		
		return  $this->database->query_hash( $sql, 'id', 'campaign_id' );
	}
	
	public function getMessageSubjectById( $message_ids, $org_id ) {
		
		$sql = "SELECT `ob`.`messageId` AS `id`, 
				`ob`.`messageText` AS `subject`
				FROM `msging`.`outboxes` AS ob
				WHERE `ob`.`publisherId` = $org_id
				AND `ob`.`messageId` IN ( $message_ids )";
		
		return $this->database->query_hash( $sql, 'id', 'subject' );
		
	}
	
	public function getRoiTypes(){

		$sql = "SELECT `desc` as 'name',`id` FROM `campaigns`.`campaign_roi_types`";
		
		return $this->database->query_hash($sql, 'name', 'id');
	}
	
	public function getVoucherSeriesIdsByCampaigns( $campaign_ids, $org_id ){
		
		$campign_ids_string = implode( ",", $campaign_ids );
		
		$sql = "SELECT `voucher_series_id`
				FROM campaigns.`campaigns_base` AS cb
				WHERE cb.`org_id` = $org_id 
					AND cb.`id` IN ( $campign_ids_string )";
		
		$this->logger->debug( "sql is $sql" );
		return  $this->database->query( $sql );
	}
	
	public function getVoucherSeriesDescriptionByIds( $voucher_ids ) {
		
		$sql =" SELECT `id`, `description`
				FROM campaigns.`voucher_series`
				WHERE  `id`
				IN ( $voucher_ids )
				AND org_id = $this->current_org_id";
		
		return $this->database->query_hash( $sql, 'id', 'description' );
	}
	
	public function getTotalRevenueByVoucherSeriesId( $vouche_series_id, 
				$org_id ) {
		
		$sql = "SELECT SUM( `ll`.`bill_amount` ) AS `total_revenue`
				FROM `campaigns`.`voucher_redemptions` AS `vr`
				JOIN `user_management`.`loyalty_log` AS `ll` 
				ON `vr`.`org_id` = `ll`.`org_id` 
				AND `vr`.`used_by` = `ll`.`user_id`
				AND `vr`.`used_at_store` = `ll`.`entered_by`
				AND `vr`.`bill_number` = `ll`.`bill_number`
				WHERE `vr`.`org_id` = $org_id
				AND `vr`.`voucher_series_id` = $vouche_series_id
				GROUP BY `vr`.`used_at_store`";
		
		return  $this->database->query_scalar( $sql );
	}
	
	public function getIssuedVoucherCountByVoucherSeries( $voucher_series_ids,
				$org_id ) {
		
		$sql = "SELECT COUNT(`voucher_id`) AS `count` , `voucher_series_id`
				FROM `campaigns`.`voucher`
				WHERE `org_id` = $org_id
				AND `voucher_series_id`
				IN ( $voucher_series_ids )
				GROUP BY `voucher_series_id`";
		
		return $this->database->query( $sql );
	}
	
	public function getRedemVoucherByVoucherSeries( $voucher_series_ids,
				$org_id ) {
		
		$sql = "SELECT COUNT( `id` ) AS `count`, `voucher_series_id`
				FROM `campaigns`.`voucher_redemptions`
				WHERE `org_id` = $org_id
				AND `voucher_series_id`
				IN ( $voucher_series_ids )
				GROUP BY `voucher_series_id`";
		
		return $this->database->query( $sql );
	}
	
	public function getRedeemerCountByVoucherSeries( $voucher_series_ids,
			 $org_id ) {
		
		$sql = "SELECT COUNT( DISTINCT (
				`used_by`
				) ) AS `Redeemer` , `voucher_series_id`
				FROM `voucher_redemptions`
				WHERE `org_id` = $org_id
				AND `voucher_series_id`
				IN ( $voucher_series_ids )
				GROUP BY `voucher_series_id`";
		
		return $this->database->query( $sql );
	}
	
	public function addRecoCampaignMapping( $campaign_id,
			$reco_plan_id ) {
		
		$sql = "INSERT INTO `campaigns`.`recommendation_mapping` ".
				"(`org_id`,`campaign_id`, `recommendation_plan_id`) ".
				"VALUES('$this->current_org_id','$campaign_id', '$reco_plan_id')";
		
		$id = $this->database->insert($sql);
	}
	
	public function getRecoCampaignMapping( $campaign_id ){
		
		$plan_id = false;
		$sql = "SELECT `recommendation_plan_id` 
				FROM `campaigns`.`recommendation_mapping`
				WHERE `org_id` = '$this->current_org_id' 
				AND `campaign_id` = '$campaign_id' ";	

		$id = $this->database->query($sql);
		
		foreach($id as $reco_plan){
			if(!empty($reco_plan)) {	
			$plan_id = $reco_plan['recommendation_plan_id'];
			break;
			}
		}
		return $plan_id;
	}
	
	public function updateRecoPlanDetails( $campaign_id, $reco_plan_id, $num_of_recos, $size_of_attrs ) {
		
		$sql = "UPDATE `campaigns`.`recommendation_mapping`
				SET `num_of_recommendations` = '$num_of_recos' ,
				`num_of_attributes` = $size_of_attrs 
				WHERE `org_id` = '$this->current_org_id' 
				AND `campaign_id` = '$campaign_id'
				AND `recommendation_plan_id` = '$reco_plan_id' " ;
		
		return $this->database->update($sql);
	}
	
	public function getRecoProductAttribDetails ( $campaign_id, $plan_id ) {
		
		$sql = "SELECT `num_of_recommendations`, `num_of_attributes` 
				FROM `campaigns`.`recommendation_mapping` 
				WHERE `org_id` = '$this->current_org_id'
				AND `campaign_id` = '$campaign_id'
				AND `recommendation_plan_id` = '$plan_id' ";
		
		return $this->database->query( $sql );
	}

	//returns all the approved messages for a given campaign
	public function getApprovedMsgDetailsByCampaignId($campaign_id){

		$sql = "SELECT  params
	 			FROM `$this->database_msging`.`message_queue` 
	 			WHERE `org_id` = $this->current_org_id AND `campaign_id` = $campaign_id AND `Approved` = 1 ";

	 	return $this->database->query($sql);	
	}
	
	/**
	 * This function is used to tag the entities with zone or concept
	 * type like COUPON_SERIES, OUTBOUND_MSG, OUTBOUND_LIST, OUTBOUND_CAMP
	 */
	public function tagCampaignEntityByType(
		$org_id, $type, $ref_id, $entity_type, $entity_ids, $entered_by){
				
		$sql = "
			INSERT INTO `campaigns`.`camp_entity_ou_mapping` 
				( `org_id`, `type`, `ref_id`, `entity_type`, `entity_ids`, `entered_by` )
			VALUES 
				( '".$org_id."' ,'".$type."' ,'".$ref_id."' ,'".$entity_type."' ,'".$entity_ids."' ,'".$entered_by."')
			ON DUPLICATE KEY UPDATE `entity_ids` = VALUES(`entity_ids`), `entered_by` = VALUES(`entered_by`)
		";
		
		return $this->database->update($sql);
	}

	/**
	 * This function is used to get tagged entities by type and ref_id
	 */
	public function getTaggedCampaignEntity( $org_id, $type, $ref_id, $entity_type = 'CONCEPT' ){
				
		$sql = "
			SELECT 
				`entity_type`, `entity_ids`, `entered_by` 
			FROM `campaigns`.`camp_entity_ou_mapping`
				WHERE `org_id` = '$org_id' AND `type` = '$type' AND `ref_id` = '$ref_id' AND `entity_type` = '$entity_type'
		";
		
		return $this->database->query_firstrow($sql);
	}

	public function getObjectiveElements() {
        $sql = "SELECT 
        		omd1.id AS id_omd1,
        		omd1.objective_type AS objective_type_omd1,
                omd1.objective_parent_id AS pid_omd1,
                omd1.help_text AS help_text_omd1,
                omd1.input_type as input_type_omd1,
                omd2.id AS id_omd2,
                omd2.objective_type AS objective_type_omd2,
                omd2.objective_parent_id AS pid_omd2,
                omd2.help_text AS help_text_omd2,
                omd2.input_type as input_type_omd2
                FROM campaigns.objective_meta_details omd1, campaigns.objective_meta_details omd2
                WHERE omd2.objective_parent_id = omd1.id AND omd1.is_active=1 AND omd2.is_active=1 
                ORDER BY pid_omd1";
        return $this->database->query($sql);
    }

    public function getGenericIncentives() {
        $sql = "SELECT igd.id, igd.generic_objective_type as got, igd.help_text 
                FROM campaigns.incentive_generic_meta_details igd 
                WHERE igd.is_active=1";
        $result = $this->database->query($sql);
        
        return $this->incentiveGenericJSONParser($result);
    }

    private function incentiveJSONParser($result) {
        $inc_elems = array();
        foreach($result as $row) {
            $prop = array('type' => $row["incentive_type"],
                          'label' => $row["label"],
                          'children' => array());
            $inc_elems[$row['id']] = $prop;
        }
        return $inc_elems;
    }

    private function incentiveGenericJSONParser($result) {
        $gen_elems = array();
        foreach ($result as $row) {
            $gen_elems[$row["id"]] = array('type' => $row["got"],
                                           'help' => $row["help_text"]);
        }

        return $gen_elems;
    }

    public function getIncentiveElements() {
        $sql = "SELECT imd.id, imd.incentive_type, imd.label 
                FROM campaigns.incentive_meta_details imd";
        $result = $this->database->query($sql);
        $json_result = $this->incentiveJSONParser($result);
        $gen_elems = $this->getGenericIncentives();
        foreach($json_result as $j => $i) {
            if($i['type'] == "GENERIC")
                $json_result[$j]['children'] = $gen_elems;
        }

        return $json_result;
    }

    public function addObjectiveMappings($objective, $org_id, $campaign_id) {
        global $currentuser;
        $user_id = $currentuser->user_id;
        $sql =  "INSERT INTO campaigns.objective_mapping ( org_id, objective_type_id,
                campaign_id,updated_by,is_active,created_date) VALUES ( '$org_id',
                '$objective','$campaign_id','$user_id',1,NOW())";
        $this->database->insert($sql);
    }

    private function getObjectiveMetadata() {
        $sql = "SELECT * FROM campaigns.objective_meta_details";
        $result = $this->database->query($sql);

        $json_result = array();
        foreach ($result as $row) {
            $json_result[(int)$row['id']] = $row;
        }

        return $json_result;
    }

    public function getObjectivesFromDB($camp_id, $org_id) {
        $sql = "SELECT om.objective_type_id from campaigns.objective_mapping om 
                WHERE om.org_id='$org_id' AND om.campaign_id='$camp_id'";
        $this->logger->debug("@@@Getting objective mapping from db " . $sql);
        $result_selected = $this->database->query_scalar($sql);

        $result_all_objectives = $this->getObjectiveMetadata();
        $result = array("selected_obj" => $result_selected,
                        "obj_metadata" => $result_all_objectives);

        return $result;
    }

    public function updateObjectiveMappings($objective, $org_id, $camp_id) {
        global $currentuser;
        $uid = $currentuser->user_id;
        $sql = "UPDATE campaigns.objective_mapping om
                SET om.objective_type_id='$objective', om.updated_by='$uid'
                WHERE om.org_id='$org_id' AND om.campaign_id = '$camp_id'";

        $this->logger->debug("@@@Update objective mapping in db " . $sql);
        $this->database->update($sql);
    }

    private function updateMessageIdIncMapping($inc_mapping_id, $org_id, $camp_id, $message_id) {
    	$sql =  "UPDATE campaigns.incentive_mapping im SET
        		 im.message_queue_id='$message_id' 
        		 WHERE 
        		 im.id='$inc_mapping_id' AND
        		 im.org_id='$org_id' AND
        		 im.campaign_id='$camp_id'";

        $this->logger->debug("update message id for points mapping in db " . $sql);
        $this->database->update($sql);
    }

    public function addIncentiveMappings($metadata_mappings, $org_id, $camp_id) {
        global $currentuser;
        $uid = $currentuser->user_id;
        $selected_incentive = $metadata_mappings['selected_incentive'];
        $selected_generic = $metadata_mappings['selected_generic'];
        $message_id = $metadata_mappings['message_id'];
        $voucher_series_id = $metadata_mappings['voucher_series_id'];
		$points_prop_id = $metadata_mappings['points_prop_id'];
        $incentive_prop_id = NULL;
        // Switch to find if the incentive was coupons or points
        switch ($metadata_mappings['selected_incentive']) {
            case 'none':
                return;
            case '1':
            	if($metadata_mappings['inc_mapping_id'] != "none") {
            		$incentive_prop_id = $points_prop_id;	
            	}
            	if(empty($incentive_prop_id)) {
            		$points_sql = "SELECT points_properties_id from campaigns.campaigns_base WHERE 
            				   id=$camp_id";
            		$incentive_prop_id = $this->database->query_scalar($points_sql);
            	}
				break;
            case '2':
				// In case of coupons
                $incentive_prop_id = $voucher_series_id;
                break;
            case '3':
				// In case of points
                $incentive_prop_id = $selected_generic;
                break;
            default:
                return;
        }

        $sql =  "INSERT INTO campaigns.incentive_mapping ( org_id, incentive_type_id, incentive_properties_id,
                campaign_id,message_queue_id, created_date, last_updated) VALUES ( '$org_id',
                '$selected_incentive', '$incentive_prop_id', '$camp_id', '$message_id', NOW(), NOW())";
        
		$this->logger->debug("@@@Add incentive mapping : mapping_sql = " . $sql);
        $map_id = $this->database->insert($sql);
        $this->logger->debug("@@@Add incentive mapping : map_id = " . $map_id);
    }

    public function updateIncentiveMappings($metadata_mappings, $org_id, $camp_id) {
        global $currentuser;
        $uid = $currentuser->user_id;
        $selected_incentive = $metadata_mappings['selected_incentive'];
        $selected_generic = $metadata_mappings['selected_generic'];
        $message_id = $metadata_mappings['message_id'];
        $voucher_series_id = $metadata_mappings['voucher_series_id'];
        $incentive_prop_id = NULL;

        // Switch to find if the incentive was coupons or points
        switch ($metadata_mappings['selected_incentive']) {
            case 'none':
            	$selected_incentive = "-1";
            	$incentive_prop_id = "-1";
                break;
            case '1':
				$points_sql = "SELECT points_properties_id from campaigns.campaigns_base WHERE 
            				   id=$camp_id";
            	$incentive_prop_id = $this->database->query_scalar($points_sql);
                break;
            case '2':
				// In case of coupons
                $incentive_prop_id = $voucher_series_id;
                break;
            case '3':
				// In case of points
                $incentive_prop_id = $selected_generic;
                break;
            default:
                return;
        }
        $this->logger->debug("incentive_prop_id = " . $incentive_prop_id);
        $this->logger->debug("cool incentive model");
        $sql =  "UPDATE campaigns.incentive_mapping im SET
        		 im.incentive_type_id='$selected_incentive',
        		 im.incentive_properties_id='$incentive_prop_id'
        		 WHERE 
        		 im.org_id='$org_id' AND
        		 im.campaign_id='$camp_id' AND
        		 im.message_queue_id='$message_id'";

        $this->logger->debug("update_incentive_sql = " . $sql);
        $this->logger->debug("mapping_sql = " . $sql);
        $map_id = $this->database->update($sql);
        $this->logger->debug("map_id = " . $map_id);
    }

    public function checkIncentiveExists($org_id, $camp_id, $message_id) {
        $sql = "SELECT COUNT(*) FROM campaigns.incentive_mapping im 
                WHERE 
                im.org_id='$org_id' AND
                im.campaign_id='$camp_id' AND
                im.message_queue_id='$message_id'";

        $this->logger->debug("camp_update_check_sql: " . $sql);
        return $this->database->query_scalar($sql);
    }

    public function checkObjExists($org_id, $camp_id) {
        $sql = "SELECT COUNT(*) FROM campaigns.objective_mapping om 
                WHERE 
                om.org_id='$org_id' AND
                om.campaign_id='$camp_id'";

        $this->logger->debug("check_obj_exists_sql_2: " . $sql);
        return $this->database->query_scalar($sql);
    }

    public function getIncentiveSelection($org_id, $camp_id, $message_id) {
		$sql = "SELECT im.incentive_type_id, im.incentive_properties_id FROM 
				campaigns.incentive_mapping im WHERE 
				im.org_id='$org_id' AND
				im.campaign_id='$camp_id' AND
				im.message_queue_id='$message_id'";
	
		$this->logger->debug("previous inc selection query: " . $sql);
        $previous_incentive = $this->database->query_firstrow($sql);
		if(is_null($previous_incentive))
            return false;
        return $previous_incentive;
    }

    public function fetchPreviousInc($org_id, $camp_id, $message_id) {
        $sql = "SELECT * FROM campaigns.incentive_mapping im 
                WHERE 
                im.org_id='$org_id' AND
                im.campaign_id='$camp_id' AND
                im.message_queue_id='$message_id'";

        $this->logger->debug("camp_update_found_sql: " . $sql);
        $result = $this->database->query($sql);
        $this->logger->debug("prev_inc_result_1: " . print_r($result, true));

        return $result[0]["incentive_type_id"];
    }

    public function fetchPreviousObj($org_id, $camp_id) {
        $sql = "SELECT * FROM campaigns.objective_mapping om 
                WHERE 
                om.org_id='$org_id' AND
                om.campaign_id='$camp_id'";

        $this->logger->debug("camp_update_found_sql_1: " . $sql);
        $result = $this->database->query($sql);
        $this->logger->debug("prev_obj_result_4: " . print_r($result, true));
        $this->logger->debug("@@@objective_type_id: " . $result[0]["objective_type_id"]);

        return $result[0]["objective_type_id"];
    }

    public function addCampaignTags($campaign_tags) {
    	global $currentuser;
		$user_id = $currentuser->user_id;
		$org_id = $campaign_tags['org_id'];
		$campaign_id = $campaign_tags['campaign_id'];
		$tags = $campaign_tags['tags'];

		$sql = "INSERT INTO campaigns.campaign_tags (org_id, campaign_id, updated_by, 
				tags, created_date) VALUES ('$org_id', '$campaign_id', '$user_id', 
				'$tags', NOW())";

		return $this->database->insert($sql);
    }
    
    public function getOutboundCampaignDetailsScheduled($start_date, $end_date){
    	
    	$sql = "SELECT mq.id AS message_id, cb.id AS campaign_id,
	    			mq.org_id AS org_id, cb.name AS campaign_name, 
	    			mq.type AS type, mq.params AS params, 
	    			mq.default_arguments AS default_arguments,
	    			mq.scheduled_on as scheduled_time,
	    			mq.approved as authorized
    			FROM msging.`message_queue` mq
    			JOIN campaigns.campaigns_base cb 
    			ON mq.campaign_id = cb.id
	    			AND mq.org_id = cb.org_id
	    			AND cb.type =  \"outbound\"
	    			AND mq.status = \"OPEN\"
    			WHERE mq.scheduled_on >=  \"$start_date\"
    				AND mq.scheduled_on <  \"$end_date\"";
    	
    	return ShardedDbase::queryAllShards("campaigns", $sql);
    }	
    
    public function getRecurringOutboundCampaignDetailByMessageIdAndOrgId($org_messages){
    	$org_message_csv  = "";
    	if(count($org_messages) == 1){
    		$org_message_csv = "(". $org_messages[0] . ")";
    	}else{
    		$org_message_csv = "(".implode(",", $org_messages).")";
    	}
    	
    	$sql = "SELECT mq.id AS message_id, cb.id AS campaign_id,
					mq.org_id AS org_id, cb.name AS campaign_name, 
					mq.type AS type, mq.params AS params, 
					mq.default_arguments AS default_arguments,
					mq.scheduled_on as scheduled_time,
					mq.approved as authorized
				FROM msging.`message_queue` mq
				JOIN campaigns.campaigns_base cb 
				ON mq.campaign_id = cb.id
					AND mq.org_id = cb.org_id
					AND cb.type =  \"outbound\"
					AND mq.status = \"OPEN\"
				WHERE (mq.org_id, mq.id) in $org_message_csv
				AND scheduled_on = \"0000-00-00 00:00:00\"";
    	
    	$this->logger->debug("getRecurringOutboundCampaignDetailByMessageIdAndOrgId sql ".$sql);;
    	return ShardedDbase::queryAllShards("campaigns", $sql);
    }
    
    public function getCampaignByIdAndOrdId( $campaign_id, $org_id ){
    	$sql = "SELECT * FROM campaigns.campaigns_base 
    			WHERE org_id = $org_id and id = $campaign_id";
    	return ShardedDbase::queryForOrg($org_id, "campaigns", $sql);
    }
    
    public function getExpiringCampaigns( $from_date, $to_date ){

    	$sql = "	SELECT id, name, org_id, start_date, 
    					end_date,  voucher_series_id, DATEDIFF(now(), start_date) as age
			    	FROM campaigns.campaigns_base cb
			    	WHERE cb.end_date > \"$from_date\"
			    	AND cb.end_date <= \"$to_date\" 
    				AND type =  \"outbound\"";
    	$this->logger->debug("getExpiringCampaigns sql ".$sql);
    	
    	return ShardedDbase::queryAllShards('campaigns', $sql);
    	
    }
    
    public function getVoucherSeriesNamesByIds($org_vs_voucher_series_id_csv){
    	$sql = "	SELECT   id as voucher_series_id, description as voucher_series_name, org_id
			    	FROM campaigns.voucher_series
			    	WHERE (org_id,id) IN ( $org_vs_voucher_series_id_csv )";
    	$this->logger->debug("getVoucherSeriesNamesByIds sql ".$sql);
    	
    	$result = ShardedDbase::queryAllShards('campaigns', $sql);
    	
    	$data = array();
    	foreach ($result as $r){
    		
    		if(!$data[$r['org_id']]){
    			$data[$r['org_id']] = array();
    		}
    		$data[$r['org_id']][$r['voucher_series_id']] = $r['voucher_series_name'];
    	}
    	return $data;
    }
    
    public function getVoucherIssuedCount( $org_vs_voucher_series_id_csv ){
    	
    	$sql = "	SELECT   voucher_series_id, count(*) as  voucher_issued, org_id
				    FROM campaigns.voucher
				    WHERE (org_id,voucher_series_id) IN ( $org_vs_voucher_series_id_csv )
    				GROUP BY org_id, voucher_series_id ";
    	
    	$this->logger->debug("getVoucherIssuedCount sql ".$sql);
    	
    	$result = ShardedDbase::queryAllShards('campaigns', $sql);
    	 
    	$data = array();
    	foreach ($result as $r){
    	
    		if(!$data[$r['org_id']]){
    			$data[$r['org_id']] = array();
    		}
    		$data[$r['org_id']][$r['voucher_series_id']] = $r['voucher_issued'];
    	}
    	return $data;
    }
    
    public function getVoucherRedeemedCount( $org_vs_voucher_series_id_csv ){
    	
    	$sql = "	SELECT   voucher_series_id, count(*) as  voucher_redeemed, org_id
				    	FROM campaigns.voucher_redemptions
				    	WHERE (org_id,voucher_series_id) IN ( $org_vs_voucher_series_id_csv )
				    	GROUP BY org_id, voucher_series_id ";
    	$this->logger->debug("getVoucherRedeemedCount sql ".$sql);
    	
    	$result = ShardedDbase::queryAllShards('campaigns', $sql);
    	
    	$data = array();
    	foreach ($result as $r){
    		 
    		if(!$data[$r['org_id']]){
    			$data[$r['org_id']] = array();
    		}
    		$data[$r['org_id']][$r['voucher_series_id']] = $r['voucher_redeemed'];
    	}
    	return $data;
    }
    
    public function updatePrecheckProcessingLog($id, $org_id, $campaign_id, $message_id, $status, $error,
    		$start_date, $end_date){
    	 
    	if( $id > 0 ){
    		$sql = "SELECT id FROM msging.precheck_processing_log
    				WHERE id = $id AND org_id = $org_id";

    		$precheck_log = $this->database->query_scalar($sql);
    	}else{
    		$sql = "SELECT id FROM msging.precheck_processing_log
    				WHERE org_id = $org_id AND campaign_id = $campaign_id  
    				AND message_id = $message_id AND status = \"POSTPONED\"
    				AND DATE(start_date) = \"".date( 'Y-m-d', strtotime( $start_date ))."\" 
    				ORDER BY id DESC limit 1";
    		
    		
    		$precheck_log = $this->database->query_scalar($sql);
    	}
    	 
    	if(!$precheck_log){
    		$insert_sql = "INSERT INTO `msging`.`precheck_processing_log`
				    		(`org_id`, `message_id`, `campaign_id`, `start_date` )
				    		VALUES ( $org_id, $message_id, $campaign_id,  \"$start_date\" );";
    		
    		return $this->database->insert($insert_sql);
    	}else{
    		$update_sql = "UPDATE `msging`.`precheck_processing_log`
				    		SET end_date = \"$end_date\",
				    		status = \"$status\",
				    		error = \"$error\"
				    		WHERE id = $precheck_log AND org_id = $org_id";

    		$this->database->update($update_sql);
    		return $precheck_log;
    	}
    }
    
    public function getPrecheckStatus($org_id, $campaign_id, $message_id, $start_date){
    	
    		$sql = "SELECT status FROM msging.precheck_processing_log
    		WHERE org_id = $org_id AND campaign_id = $campaign_id
    		AND message_id = $message_id AND start_date > \"$start_date\"
    		ORDER BY id DESC limit 1";
    	
	    	return $this->database->query_scalar($sql);
    }
    
    public function loadMessagesByTaskIds($task_id_csv){
    	global $logger;
    	try{
    	
	    	$sql = "SELECT org_id, refrence_id, frequency  FROM user_management.`reminder`
	    	WHERE scheduler_task_id in ($task_id_csv) AND `reminder_type` = \"CAMPAIGN\"
	    	AND state= \"RUNNING\"";
	    
	    	$logger->debug("loadByTaskIds sql :".$sql);
	    
	    	$result = ShardedDbase::queryAllShards("users", $sql);
	    	return $result;
    	}catch(Exception $e){
    		$this->logger->debug("Exception caught ".$e->getMessage());
    		return array();
    	}
    }

    //get Message Default arguments based on org_id, campaign_id and guid
	  public function getMessageDefaultArgumentsByGuid( $org_id, $campaign_id, $guid ){
	 	$quoted_guid = "'".$guid."'";
	 	$this->logger->debug("guid quoted:".$quoted_guid);
	 	
	 	$sql = "SELECT `mq`.`default_arguments`
	 			FROM `$this->database_msging`.`message_queue` AS `mq` 
	 			WHERE `mq`.`guid` = $quoted_guid AND `mq`.`campaign_id` = $campaign_id AND `mq`.`org_id` = $org_id";

	 	$this->logger->debug("getMessageDefaultArgumentsByGuid sql : ".$sql);		
	 	return ShardedDbase::queryForOrg($org_id, "msging", $sql);
	 }
	 
    
}
?>
