<?php
include_once('svc_mgrs/NSAdminServiceManager.php');
include_once('SubscriptionService/thrift/subscriptionservice.php');

class NSAdminThriftClient extends BaseThriftClient
{

    private $cb_client;
    private $file_handles;
    private $C_subscription_client;
    private $serverReqId;
    
    //inbox id used in multiple create objects
    protected $inbox_id=0;

	function __construct() {
		parent::__construct();

		$this->logger->debug("nsadmin: " . get_include_path());

		$this->serverReqId  = &$_SERVER['UNIQUE_ID'];
		$this->include_file('nsadmin/nsadmin_types.php');
		$this->include_file('nsadmin/NSAdminService.php');
		$recvtimeout = $GLOBALS['cfg']['srv']['nsadmin']['recvtimeout'];
		try {
			$this->get_client('nsadmin', $recvtimeout);
		} catch (Exception $e) {
			$this->logger->error("Exception caught while trying to connect");
		}

        $this->cb_client = new NSAdminServiceManager();
        $this->file_handles = array();
        $this->C_subscription_client = new SubscriptionServiceThriftClient();
	}

	public function getStatus() {
		try {

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client("getStatus");
            }
			# Send using NSADMIN
			$this->transport->open();
			$ret = $this->client->getStatus();
			$this->transport->close();
		} catch (TException $te) {
			$this->logger->error("Exception: ".$te->getMessage());

			//$this->transport->close();
			return false;
		} catch(Exception $e) {
			$this->logger->error("Exception: ".$e->getMessage());
			try {
				$this->transport->close();
			} catch (Exception $e) {
				//do nothing
			}
			return false;
		}
		return $ret;
	}

	/**
	 *
	 * @param $type The type from - enum ('SMS', 'EMAIL', 'IM')
	 * @param $to
	 * @param $msg
	 * @param $sender_org_id
	 * @param $priority
	 * @param $gsm_sender
	 * @param $cdma_sender
	 * @return unknown_type
	 */
	private function createMessageObject(
	$type, $to, $msg, $sender_org_id = 0, $priority, $gsm_sender, $cdma_sender,
	$truncate, $scheduled_time, $cc, $body, $sender_label, $replyto_email, $gateway = false, $is_immediate = false, $attached_file_id = array(),
	$tags = array(), $campaignId = -1, $ndnc = false, $context_id = -1, $org_timestamp = '', $inbox_id=null
	){
		$m = new nsadmin_Message();
		$m->clientId = NSADMIN_CLIENT_ID;
		$m->message = $msg;
		$m->messageClass = strtoupper(trim($type));
        $m->receiver = $to;

        /*
        if($sender_org_id === 569){
            $m->sender = '"'.$sender_label.'" <'.$replyto_email.'>';
        }else{
        */
        $m->sender = '"' . $sender_label. '"' . $replyto_email;
        //}

        $m->sendingOrgId = $sender_org_id;
		$m->priority = strtoupper(trim($priority));
		$m->gsmSenderId = $gsm_sender;
		$m->cdmaSenderId = $cdma_sender;
		$m->truncate = $truncate;
		$m->ccList = $cc;
		$m->body = $body;
		$m->scheduledTimestamp = strtotime($scheduled_time) * 1000;
		$m->gateway = $gateway;
		$m->immediate = $is_immediate;
		$m->attachmentId = $attached_file_id;
		$m->campaignId = $campaignId;
		$m->tags = $tags;
        $m->ndnc = $ndnc;
        $m->clientContextId = $context_id;
        $m->originTimestamp = $org_timestamp;
        if($inbox_id)
        	$m->inboxId=$inbox_id;
        if(count($this->file_handles) > 0)
        {
        	$m->fileHandle = $this->file_handles;
        }
		return $m;
	}


	public function createSummaryCriteria($summary_criteria)
	{

		$summary = new nsadmin_SummaryCriteria();
		$summary_criteria['sending_org_id'] != null	|| $summary_criteria['sending_org_id']== 0 ?
			$summary->sendingOrgId = $summary_criteria['sending_org_id'] : null;
		$summary_criteria['message_priority'] !=null ?
			$summary->messagePriority = strtoupper(trim($summary_criteria['message_priority'])): null;
		$summary_criteria['gateway'] != null ? $summary->gateway = $summary_criteria['gateway'] : null;
		$summary_criteria['from_date'] != null ? $summary->fromDate = $summary_criteria['from_date'] : null;
		$summary_criteria['to_date'] != null ? $summary->toDate = $summary_criteria['to_date'] : null;
		$summary_criteria['message_class'] != null ?
			$summary->messageClass = strtoupper(trim($summary_criteria['message_class'])) : null;
		$summary_criteria['message_status'] != null ? $summary->messageStatus = $summary_criteria['message_status'] : null;
		$summary_criteria['campaign_id'] != null || $summary_criteria['campaign_id'] == 0 ?
			$summary->campaignId = $summary_criteria['campaign_id'] : null;
		$summary->selectFields = $summary_criteria['select_fields'];
		$this->logger->debug("summary criteria:".print_r($summary, true));
		return $summary;
	}


    public function makeSendMessageCall($to, $from_org_id, $message, $priority, $sender_gsm, $sender_cdma,
                                        $truncate, $scheduled_time, $gateway, $is_immediate, $tags = array(), $ndnc = false,
                                        $campaign_id = -1, $context_id = -1, $org_timestamp = ''
    )
    {

        $m = $this->createMessageObject('SMS', $to, $message, $from_org_id, $priority, $sender_gsm, $sender_cdma, $truncate,
                                        $scheduled_time, '', '', '', '', $gateway, $is_immediate, array(-1), $tags, $campaign_id,
                                        $ndnc, $context_id, $org_timestamp
                                       );

		try {

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("makeSendMessageCall", $m);
            }

			# Send using NSADMIN
			$this->transport->open();
			$this->logger->debug("nsadmin client: " . get_class($this->client));
			$ret = $this->client->sendMessage($m);
			$this->transport->close();
		} catch (TException $te) {
			$this->logger->error("NSAdmin Exception: ".$te->getMessage());

			//$this->transport->close();
			return false;
		} catch(Exception $e) {
			$this->logger->error("NSAdmin Exception: ".$e->getMessage());
			try {
				$this->transport->close();
			} catch (Exception $e) {
				//do nothing
			}
			return false;
		}
		return $ret;
	}

	public function makeSendMultipleMessagesCall($msgs){

		try {
            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("makeSendMultipleMessagesCall", $msgs);
            }
			# Send using NSADMIN
			$this->transport->open();


			$this->logger->debug("nsadmin client: " . get_class($this->client));

			$ret = $this->client->sendMultipleMessages($msgs);
			$this->transport->close();
		} catch (TException $te) {
			$this->logger->error("NSAdmin Exception: ".$te->getMessage());

			//$this->transport->close();
			return false;
		} catch(Exception $e) {
			$this->logger->error("NSAdmin Exception: ".$e->getMessage());
			try {
				$this->transport->close();
			} catch (Exception $e) {
				//do nothing
			}
			return false;
		}
		return $ret;
	}

    public function doSendEmailMessage($to, $cc, $from_org_id, $message, $body, $priority, $truncate, $sender_label, $replyto_email, $attached_file_id = array(), $tags = array(), $schedule_time, $store_timestamp='') {

        if(empty($attached_file_id)){
            $attached_file_id = array(-1);
        }
		$m = $this->createMessageObject('EMAIL', $to, $message, $from_org_id, $priority, '', '', $truncate, $schedule_time, $cc, $body, $sender_label, $replyto_email, '', 0, $attached_file_id, $tags, '', '', '', $store_timestamp);

		try {

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("doSendEmailMessage", $m);
            }
			# Send using NSADMIN
			$this->transport->open();


			$this->logger->debug("nsadminattached id: " . $m->attachmentId);
			$ret = $this->client->sendMessage($m);
			$this->transport->close();
		} catch (TException $te) {
			$this->logger->error("Exception: ".$te->getMessage());

			//$this->transport->close();
			return false;
		} catch(Exception $e) {
			$this->logger->error("Exception: ".$e->getMessage());
			try {
				$this->transport->close();
			} catch (Exception $e) {
				//do nothing
			}
			return false;
		}
		return $ret;
	}

	/**
	 * Replaces Sender ID
	 * @param $to
	 * @param $from_org_id
	 * @param $message
	 * @param $priority
	 * @param $truncate
	 * @return unknown_type
	 */
    private function sendMessage($to, $from_org_id, $message, $priority = 'DEFAULT', $truncate, $scheduled_time, $gateway, $is_immediate, $tags, $ndnc, $campaign_id,
                                 $context_id, $org_timestamp
                                ) {

		# Find Sender
		$db = new Dbase('users');
		$sql = "SELECT * FROM custom_sender WHERE org_id = '$from_org_id'";
		$row = $db->query_firstrow($sql);
		$gsm = Util::valueOrDefault($row['sender_gsm'], "CAPILLARY");
		$cdma = Util::valueOrDefault($row['sender_cdma'], "919874400500");
		# Create object

		$this->logger->debug("nsadmin gsm: $gsm");
		$this->logger->debug("nsadmin cdma:  $cdma");

        return $this->makeSendMessageCall($to, $from_org_id, $message, $priority, $gsm, $cdma,
                                         $truncate, $scheduled_time, $gateway, $is_immediate, $tags, $ndnc, $campaign_id, $context_id, $org_timestamp);
	}

	/**
	 * Creates a message object that NSADmin accepts
	 * @param $priority enum ('DEFAULT', 'HIGH');
	 */
	function sendSMS($to, $from_org_id, $message, $priority = 'DEFAULT', $truncate = false, $scheduled_time = '',
	                 $override_gateway = false, $is_immediate = false, $tags = array(), $ndnc = 0, $campaign_id = -1,
                     $context_id = -1, $org_timestamp = ''
                    ) {
		//error_reporting(E_ALL);
		Util::checkMobileNumber($to);
		$scheduledTime_8601 = Util::serializeInto8601($scheduled_time);
		$this->logger->debug("nsadmin scheduled8601: $scheduledTime_8601 . " . strtotime($scheduledTime_8601));
		$gateway = '';
		if($override_gateway){
			$gateway = Util::getOverrideGateway();
		}

        $ret = $this->sendMessage($to, $from_org_id, $message, $priority, $truncate, $scheduledTime_8601, $gateway,
                                  $is_immediate, $tags, $ndnc, $campaign_id, $context_id, $org_timestamp);
		$this->logger->debug("Sent SMS: $ret $message");
		return $ret;
	}


	// handle sender_gsm sender_cdma beforehand
	function createMessageObjectForMultipleSMS($to, $from_org_id, $message, $sender_gsm='CAPILLARY', $sender_cdma='919874400500', $priority = 'DEFAULT', $truncate = false, $scheduled_time = '',
	$override_gateway = false, $is_immediate = false, $tags = array(), $campaign_id = -1, $inbox_id=null){

		Util::checkMobileNumber($to);
		$scheduledTime_8601 = Util::serializeInto8601($scheduled_time);
		$gateway = '';
		if($override_gateway){
			$gateway = Util::getOverrideGateway();
		}
		
		if(!$inbox_id)
			$inbox_id=++$this->inbox_id;
		
		return $this->createMessageObject('SMS', $to, $message, $from_org_id, $priority, $sender_gsm, $sender_cdma, $truncate, $scheduled_time, '', '', '', '', $gateway,
		$is_immediate, array(), $tags, $campaign_id, false, -1, '', $inbox_id	);
	}


	/**
	 * Sends out a bulk SMS. Implodes the list of senders into a String separated by commas. This is always sent with Default Priority
	 * @param $to
	 * @param $from_org_id
	 * @param $message
	 * @return unknown_type
	 */
	function sendBulkSMS($to_list, $from_org_id, $message) {
		$numbers = array();
		if (!is_array($to_list)) {
			$to_list = array($to_list);
		}
		foreach ($to_list as $to) {
			array_push($numbers, $to);
		}
		$to = implode(', ', $numbers);

		$ret = $this->sendMessage($to, $from_org_id, $message, 'DEFAULT');

		$this->logger->debug("Bulk Message Queued");
		return $ret;
	}


	/**
	 * Creates a message object that NSADmin accepts
	 * @param $priority enum ('DEFAULT', 'HIGH');
	 */
	function sendEmail($to, $cc, $from_org_id, $message, $body, $priority = 'DEFAULT', $truncate = false, $attached_file_id = array() , $tags = array(), $schedule_time, $store_timestamp='')
	{
		//Util::checkEmailAddress($to);
		# Find Sender
		$db = new Dbase('users');
		$sql = "SELECT * FROM custom_sender WHERE org_id = '$from_org_id'";
		$row = $db->query_firstrow($sql);
		$sender_label = Util::valueOrDefault($row['sender_label'], "Capillary Intouch");
        $replyto_email = Util::valueOrDefault($row['replyto_email'], "noreply@intouch-mailer.com");
        $sender_email = Util::valueOrDefault($row['sender_email'] , "noreply@intouch-mailer.com");

        $email = "<$sender_email>, <$replyto_email>";
        # Create object
        #
		$ret  = $this->doSendEmailMessage($to, $cc, $from_org_id, $message, $body, $priority, $truncate, $sender_label, $email, $attached_file_id, $tags, $schedule_time, $store_timestamp);
		if($ret !== false)
		$this->logger->debug("Sent Email");
		return $ret;
	}


	/**
	 * Creates a message object that NSADmin accepts
	 * @param $priority enum ('DEFAULT', 'HIGH');
	 */
	function getMessageDistribution($to, $from_org_id, $message, $priority = 'DEFAULT', $tags = array(), $campaign_id = -1)
	{
		$db = new Dbase('users');
		$sql = "SELECT * FROM custom_sender WHERE org_id = '$from_org_id'";
		$row = $db->query_firstrow($sql);
		$gsm = Util::valueOrDefault($row['sender_gsm'], "CAPILLARY");
		$cdma = Util::valueOrDefault($row['sender_cdma'], "919874400500");

		$m = $this->createMessageObject('SMS', $to, $message, $from_org_id, $priority, $gsm, $cdma, false, $scheduled_time, '',
										'', '', '', '', '', array(), $tags, $campaign_id);

		try {

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getMessageDistribution", $m);
            }
			# Send using NSADMIN

			$this->logger->debug("starting distribution");

			$this->transport->open();
			$ret = $this->client->chooseGateways($m);
			$this->logger->debug("***  return: " . print_r($ret, true));

			$this->transport->close();
		} catch (TException $te) {
			$this->logger->error("NSAdmin Exception: ".$te->getMessage());
			throw new Exception("Error in NSAdmin: " . $te->getMessage(), -500);
		} catch(Exception $e) {
			$this->logger->error("NSAdmin Exception: ".$e->getMessage());
			try {
				$this->transport->close();
			} catch (Exception $e) {
				//do nothing
			}
			return false;
		}
		return $ret;

	}


	/**
		This will reload only the configs of the gateways
		**/
	public function reloadConfig() {
		try {
            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("reloadConfig");
            }

			# Send using NSADMIN
			$this->transport->open();
			$this->logger->debug("nsadmin: sending thrift call for reloadConfig");
			$ret = $this->client->reloadConfig();
			$this->transport->close();
		} catch (TException $te) {
			$this->logger->error("nsadmin TException in reloadConfig: ". $te->getMessage());

			//$this->transport->close();
			return false;
		} catch(Exception $e) {
			$this->logger->error("nsadmin Exception in reloadConfig: ". $e->getMessage());
			try {
				$this->transport->close();
			} catch (Exception $e) {
				//do nothing
			}
			return false;
		}
		return true;
	}

	/**
		This will reload the gateway information as well
		**/
	public function reloadGateways() {
		try {

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("reloadGateways");
            }

			# Send using NSADMIN
			$this->transport->open();
			$this->logger->debug("nsadmin sending thrift call for reloadGateways");
			$ret = $this->client->reloadGateways();
			$this->transport->close();
		} catch (TException $te) {
			$this->logger->error("nsadmin : TException in reloadGateways: ".$te->getMessage());

			//$this->transport->close();
			return false;
		} catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in reloadGateways: ".$e->getMessage());
			try {
				$this->transport->close();
			} catch (Exception $e) {
				//do nothing
			}
			return false;
		}
		return true;
	}

	/**
	 * Message Delivery notification method
	 * @author
	 */
	public function reportMessageDelivered( $msgRefId, $receiver, $status, $response ){

		try{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("reportMessageDelivered", array($msgRefId, $receiver, $status, $response));
            }

			$this->transport->open();

			$this->logger->debug("nsadmin client: " . get_class($this->client));
			$this->logger->debug("adding the message report, $msgRefId, $receiver, $status, $response");
			$this->client->reportMessageDelivered($msgRefId, $receiver, $status, $response);

			$this->transport->close();

		}catch(Exception $e){
			$this->logger->error('Error :'.$e->getMessage());
			$this->transport->close();
		}
	}

	public function reportMessageDeliveredById( $msgId, $receiver, $status, $response ){

		try{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("reportMessageDeliveredById", array($msgId, $receiver, $status, $response));
            }

			$this->transport->open();

			$this->logger->debug("nsadmin client: " . get_class($this->client));
			$this->logger->debug("adding the message report, $receiver, $status, $response");
			$this->client->reportMessageDeliveredById($msgId, $receiver, $status, $response);

			$this->transport->close();

		}catch(Exception $e){
			$this->logger->error('Error :'.$e->getMessage());
			$this->transport->close();
		}
	}


	public function getNSAdminMessageSummary($summary_criteria)
	{
		$msg_summary_criteria = $this->createSummaryCriteria($summary_criteria);
		try {

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getNSAdminMessageSummary", $summary_criteria);
            }

			# Send using NSADMIN
			$this->transport->open();
			$summary = $this->client->getSummary($msg_summary_criteria);
			$this->logger->debug("result: message summary".print_r($summary, true));
			$this->transport->close();
			return $summary;
		} catch (TException $te) {
			$this->logger->error("Exception: ".$te->getMessage());
			return null;
		} catch(Exception $e) {
			$this->logger->error("Exception: ".$e->getMessage());
			try {
				$this->transport->close();
			} catch (Exception $e) {
			}
			return null;
		}
	}

	public function addCredits( $org_id, $value_credits, $bulk_credits, $user_credits, $added_by, $last_updated_by, $last_updated_at )
	{
		try
		{
			$credit = $this->createOrgCreditDetails($org_id, $value_credits, $bulk_credits, $user_credits, $added_by, $last_updated_by, $last_updated_at);


            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("addCredits", $credit);
            }

			$this->transport->open();
			$result = $this->client->addCredits( $credit);
			$this->logger->debug("result summary".print_r($result , true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in addCredits: ".$e->getMessage());
			$this->transport->close();
		}
		return $result;


	}

	public function getCreditDetails( $orgId )
	{
		$NUM_OF_ATTEMPTS = 5;
		$attempts = 0;
		//will retry in case we get a thrift exception
		do 	{
			try	{
				if($GLOBALS['cfg']['mock_mode'] == true){
					return $this->cb_client->handleMock("getCreditDetails", $orgId);
				}

				$this->transport->open();
				$result = $this->client->getCreditDetails( $orgId);
				$this->logger->debug("credit result summary".print_r($result , true));
				$this->transport->close();
			}
			catch(Exception $e) {
				$this->logger->error("nsadmin : Exception in getCreditDetails: ".$e->getMessage()." will rety again.");
				$this->transport->close();
				$attempts++;
				sleep(5);
				continue;
			}

			break;

		} while($attempts < $NUM_OF_ATTEMPTS);
		
		return $result;
	}

    public function getMessagesByReceiver($org_id , $data)
    {
    	try
    	{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getMessagesByReceiver", array($org_id, $data));
            }

    		$this->transport->open();
    		$result = $this->client->getMessagesByReceiver( $org_id ,$data);
			$this->logger->debug("result summary".print_r($result , true));
    		$this->transport->close();
    	}
    	catch(Exception $e) {
    		$this->logger->error("nsadmin : Exception in getMessagesByReceiver: ".$e->getMessage());
    		$this->transport->close();
    	}
    	return $result;
    }

	public function createOrgCreditDetails( $org_id, $value_credits, $bulk_credits, $user_credits,
			$added_by, $last_updated_by, $last_updated_at)
	{

		$credit = new nsadmin_OrgCreditDetails();
		$org_id != null ? $credit->orgId = $org_id :null;
		$value_credits !=null ? $credit->valueCredits = $value_credits: null;
		$bulk_credits != null ? $credit->bulkCredits = $bulk_credits : null;
		$user_credits != null ? $credit->userCredits = $user_credits : null;
		$added_by != null ? $credit->addedBy = $added_by : null;
		$last_updated_by != null ? $credit->lastUpdatedBy = $last_updated_by : null;
		$last_updated_at != null ? $credit->lastUpdatedAt = $last_updated_at : null;

		return $credit;
	}

	public function createOrgStatusDetails($org_id, $active, $updated_by, $updated_at, $add_custom_sender=2) {
		$org_status_details = new nsadmin_OrgStatusDetails();
		$org_id != null ? $org_status_details->orgId = $org_id :null;
		$org_status_details->active = $active;
		$updated_by != null ? $org_status_details->updatedBy = $updated_by : null;
		$updated_at != null ? $org_status_details->updatedAtTimestamp = $updated_at : null;
		$org_status_details->customSenderAdded = $add_custom_sender;
		return $org_status_details;
	}

	public function getGatewayList()
	{
		try
		{
            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getGatewayList");
            }

			$this->transport->open();
			$result = $this->client->getActiveGatewayShortNames();
			$this->logger->debug("result summary".print_r($result , true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in getGatewayList: ".$e->getMessage());
			$this->transport->close();

		}
		return $result;

	}

	public function getGatewaysForOrg($org_id)
	{
		try
		{
			$org = array();
			array_push($org ,$org_id );

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getGatewayList", $org);
            }

			$this->transport->open();
			$result = $this->client->getValidOrgGateways($org);
			$this->logger->debug("result summary".print_r($result,true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in getGatewaysForOrg: ".$e->getMessage());
		 	$this->transport->close();
		}
		return $result;
	}

	public function getGatewayDetails($gateway_id)
	{
		try
		{
			$ids = array();
			array_push($ids ,$gateway_id);

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getGatewayDetails", $ids);
            }

			$this->transport->open();
			$result = $this->client->getOrgGatewaysById($ids);

			$this->logger->debug("result summary".print_r($result,true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in getGatewayDetails: ".$e->getMessage());
		 	$this->transport->close();
		}
		return $result;
	}

	public function createOrgGateWay( $id,$org_id, $gateway, $weight, $campaign_id,
		$effective_Start_Timestamp, $effective_End_Timestamp, $added_By , $added_On_Time_Stamp , $is_default ,
			 $valid, $service_ip='')
	{

		$org_gateway = new nsadmin_OrgGateway();
		$id !=null?$org_gateway->id = $id :-1;
		$org_id != null ? $org_gateway->orgId = $org_id :-1;
		$gateway !=null ? $org_gateway->gateway = $gateway: '';
		$weight != null ? $org_gateway->weight = $weight : 0;

		if($campaign_id == null)
		{
			$org_gateway->campaignId = -1;
		}
		else {
			$org_gateway->campaignId = $campaign_id;
		}
		$effective_Start_Timestamp != null ? $org_gateway->effectiveStartTimestamp = $effective_Start_Timestamp : 1;
		$effective_End_Timestamp != null ? $org_gateway->effectiveEndTimestamp = $effective_End_Timestamp : 1;
		$added_By != null ? $org_gateway->addedBy = $added_By : -1;
		$added_On_Time_Stamp != null ? $org_gateway->addedOnTimeStamp = $added_On_Time_Stamp : 1;
		$is_default != null ? $org_gateway->isdefault = $is_default : 0;
		$valid != null ? $org_gateway->valid = $valid : 1;
		$org_gateway->messagePriority = 'BULK';
        $org_gateway->serviceIp = $service_ip;
		return $org_gateway;
	}

	public function createGateWay( $host_name,$short_name, $full_name, $user_name, $password,
		$connection_Properties, $service_Url, $status_Check_Url , $message_Class, $message_priority,  
			$channel_Count , $status_check_type, $status , $properties)
	{
		$gateway = new nsadmin_Gateway();
		$host_name !=null?$gateway->hostName = $host_name :'';
		$short_name != null ? $gateway->shortName = $short_name :'';
		$full_name !=null ? $gateway->fullName = $full_name: '';
		$user_name != null ? $gateway->username = $user_name : '';
		$password != null ? $gateway->password = $password : '';
		$connection_Properties != null ? $gateway->connectionProperties = $connection_Properties : "{}";
		$service_Url != null ? $gateway->serviceUrl = $service_Url : '';
		$status_Check_Url != null ? $gateway->statusCheckUrl = $status_Check_Url : '';
		$status_check_type != null ? $gateway->statusCheckType = $status_check_type : '';
		$message_Class != null ? $gateway->messageClass = $message_Class : '';
		$message_priority != null ? $gateway->messagePriority = $message_priority : '';
		$channel_Count != null ? $gateway->channelCount = $channel_Count : '';
		$status != null ? $gateway->status = $status : '';
		$properties != null ? $gateway->properties =  $properties : '';
		return $gateway;
	}

	public function addOrgGateways($org_id, $gateway, $weight, $effective_Start_Timestamp, 
			$effective_End_Timestamp, $campaign_id, $added_by, $added_On_Time_Stamp, 
			$valid, $is_default = FALSE, $service_ip='')
	{
		try
		{
			$org_gateways = array();
			$effective_Start_Timestamp = intval(strtotime($effective_Start_Timestamp))*1000;
			$effective_End_Timestamp = intval(strtotime($effective_End_Timestamp))*1000;
			$added_On_Time_Stamp = intval(strtotime($added_On_Time_Stamp,10))*1000;

			if(!is_null($campaign_id)){
				foreach($campaign_id as $campaign)
				{
					$org_gateway = $this->createOrgGateWay($id,$org_id, $gateway, $weight, $campaign,
							$effective_Start_Timestamp, $effective_End_Timestamp, $added_by, 
							$added_On_Time_Stamp, $is_default, $valid, $service_ip);
					$this->logger->debug("oorg_gateway1: " . print_r($org_gateway, true));
					array_push($org_gateways,$org_gateway);
				}
			}			
			else{
				$org_gateway = $this->createOrgGateWay($id,$org_id, $gateway, $weight, $campaign,
						$effective_Start_Timestamp, $effective_End_Timestamp, $added_by, $added_On_Time_Stamp,
						$is_default, $valid, $service_ip);
				$this->logger->debug("oorg_gateway2: " . print_r($org_gateway, true));
				array_push($org_gateways,$org_gateway);
			}				
              
            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("addOrgGateways", $org_gateways);
            }

			$this->transport->open();
			$this->logger->debug("oorg_gateways: " . print_r($org_gateways, true));
			$result = $this->client->addOrgGateways($org_gateways);
			$this->logger->debug("result summary".print_r($result,true));
			$this->transport->close();
		} catch (NSAdminException $ne) {
			$this->logger->error("NSAdminException " .$ne->getMessage());
			$this->transport->close();
		} catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in addOrgGateways: ".$e->getMessage());
		 	$this->transport->close();
		}
		return $result;
	}

	public function disableOrgGatewayByOrgId($org_id ,$priority)
	{
		try
		{
            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("disableOrgGatewayByOrgId", array($org_id, $priority));
            }

			$this->transport->open();
			$result = $this->client->disableOrgGatewayByOrgId($org_id ,array());
			$this->logger->debug("result summary".print_r($result,true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in disableOrgGatewayByOrgId: ".$e->getMessage());
		 	$this->transport->close();
		}
		return $result;
	}

	public function disableGateway($gateway_id)
	{
		try
		{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("disableGateway", array($gateway_id));
            }

			$this->transport->open();
			$result = $this->client->disableOrgGatewaysById( $gateway_id);
			$this->logger->debug("result summary".print_r($summary,true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in disableGateway: ".$e->getMessage());
		 	$this->transport->close();
		}
		return $result;
	}

	public function manageGateway($hostname, $shortname, $fullname, $username, $password, $conn_props,
		$server_url, $status_url, $message_class, $message_priority, $channel_count, 
			$status_check_type, $status,$properties, $edit = FALSE)
	{
		try
		{
			$gateway = $this->createGateWay($hostname,$shortname, $fullname, $username, $password,
				$conn_props, $server_url, $status_url ,$message_class ,$message_priority, $channel_count,  
					$status_check_type, $status ,$properties ,$edit);

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("manageGateway", $gateway);
            }

			$this->transport->open();
			$result = $this->client->addGateway($gateway);
			$this->logger->debug("result summary".print_r($result,true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in manageGateway: ".$e->getMessage());
		 	$this->transport->close();
		}
		return $result;

	}

	public function getGatewayProperties($gateway_id = -1)
	{
		try
		{
            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getGatewayProperties", $gateway_id);
            }

			$this->transport->open();
			$result = $this->client->getGateways($gateway_id);			
			$this->logger->debug("result summary".print_r($result,true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in getGatewayProperties: ".$e->getMessage());
			$this->transport->close();
		}
		return $result;
	}
	
	
	public function getGatewayProperty($short_name = "")
	{
		try
		{
			if($GLOBALS['cfg']['mock_mode'] == true){
				return $this->cb_client->handleMock("getGatewayProperty", $gateway_id);
			}
	
			$this->transport->open();
			$result = $this->client->getGateway($short_name);
			$this->logger->debug("result summary".print_r($result,true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in getGatewayProperty: ".$e->getMessage());
			$this->transport->close();
		}
		return $result;
	}

	public function getAllValidOrgGateways($priority)
	{
		try
		{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getAllValidOrgGateways", $priority);
            }

			$this->transport->open();
			$result = $this->client->getAllValidOrgGateways($priority);
			$this->logger->debug("result summary".print_r($result,true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in getAllValidOrgGateways: ".$e->getMessage());
			$this->transport->close();
		}
		return $result;
	}

	public function getMessageLogs($org_id , $class = 'SMS' , $mobile)
	{
		try
		{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getMessageLogs", array($org_id , $class , $mobile));
            }

			$this->transport->open();
			$result = $this->client->getMessageLogs($org_id , $class , $mobile);
			$this->logger->debug("result summary".print_r($result,true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in getMessageLogs: ".$e->getMessage());
			$this->transport->close();
		}
		return $result;
	}

	public function getSentMessages($org_id, $class , $start_date, $end_date, $priority, $limit)
	{
		try
		{
			$start_date = strtotime($start_date)*1000;
			$end_date = strtotime($end_date)*1000;

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getSentMessages", array($org_id, $class , $start_date, $end_date, $priority, $limit));
            }

			$this->transport->open();
			$result = $this->client->getSentMessages($org_id, $class , $start_date, $end_date, $priority, $limit);
			$this->logger->debug("result summary".print_r($result , true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in getSentMessages: ".$e->getMessage());
			$this->transport->close();
		}
		return $result;
	}

	public function getSentEmails($org_id,$start_date,$end_date, $priority, $limit)
	{
		try
		{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getSentEmails", array($org_id,$start_date,$end_date, $priority, $limit));
            }

			$this->transport->open();
			$result = $this->client->getSentEmails($org_id,$start_date,$end_date, $priority, $limit);
			$this->logger->debug("result summary".print_r($result , true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in getSentEmails: ".$e->getMessage());
			$this->transport->close();
		}
		return $result;
	}

	public function updateMessageStatus($org_id,$status, $campaign_id, $sent_time, $end_sent_time, $priority)
	{
		try
		{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("updateMessageStatus", array($org_id, $status,$campaign_id, $sent_time, $end_sent_time, $priority));
            }

			$this->transport->open();
			$result = $this->client->updateMessageStatus($org_id, $status,$campaign_id, $sent_time, $end_sent_time, $priority);
			$this->logger->debug("result summary".print_r($result , true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in updateMessageStatus: ".$e->getMessage());
			$this->transport->close();
		}
		return $result;
	}


	private function createSendingRule($groupby)
	{
			switch ($groupby) {
				case 'ALL' :
						$group_by =  SendingRule::ALL;
					break;
				case 'NOBULK' :
						$group_by = SendingRule::NOBULK;
					break;
				case 'NOPERSONALIZED' :
						$group_by = SendingRule::NOPERSONALIZED;
					break;
				case 'NONE' :
						$group_by = SendingRule::NONE;
					break;
				case 'UNSUBSCRIBE' :
						$group_by = SendingRule::UNSUBSCRIBE;
					break;
				case 'NULL' :
						$group_by = SendingRule::NULL;
					break;

		}
		return $group_by;
	}

	public function getSmsSendingRule($mobile, $orgId)
	{
		try
		{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getSmsSendingRule", array($mobile, $orgId));
            }

			$this->transport->open();
			//$result = $this->client->getSmsSendingRule($mobile, $orgId);
			$result = $this->C_subscription_client->getSendingRule(
					$orgId, $mobile, "SMS" );
			$this->logger->debug("result summary".print_r($result , true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in getSmsSendingRule: ".$e->getMessage());
			$this->transport->close();
		}
		return $this->createSendingRule( $result );
	}

	public function getEmailSendingRule($email, $orgId)
	{
		try
		{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getEmailSendingRule", array($email, $orgId));
            }

			$this->transport->open();
			//$result = $this->client->getEmailSendingRule($email, $orgId);
			$result = $this->C_subscription_client->getSendingRule(
					$orgId, $email, "EMAIL" );
			$this->logger->debug("result summary".print_r($result , true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in getEmailSendingRule: ".$e->getMessage());
			$this->transport->close();
		}
		return $this->createSendingRule( $result );
	}

	public function addSmsSendingRule($mobile, $orgId,  $rule)
	{

		try
		{
			$rules = $this->createSendingRule( $rule );

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("addSmsSendingRule", array($mobile, $orgId,  $rules));
            }

			$this->transport->open();
			//$result = $this->client->addSmsSendingRule($mobile, $orgId,  $rules);
			$this->C_subscription_client->processSendingRule(
					$orgId, $mobile, "SMS", $rule );
			$this->logger->debug("result summary".print_r($result , true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in addSmsSendingRule: ".$e->getMessage());
			try {
				$this->transport->close();
			} catch(Exception $exp){

			}
			return false;
		}
		return true;

	}

	public function addEmailSendingRule($email, $orgId,  $rule)
	{
		try
		{
			$rules = $this->createSendingRule( $rule);

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("addEmailSendingRule", array($email, $orgId,  $rules));
            }

			$this->transport->open();
			//$result = $this->client->addEmailSendingRule($email, $orgId,  $rules);
			$result = $this->C_subscription_client->processSendingRule(
							$orgId, $email, "EMAIL", $rule );
			$this->logger->debug("result summary".print_r($result , true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in addEmailSendingRule: ".$e->getMessage());
			try {
				$this->transport->close();
			} catch(Exception $exp){

			}
			return false;
		}
		return true;

	}

	public function getMessagesById( $messageIds )
	{
		try
		{
			$msg_ids = array();
			foreach($messageIds as $id)
			{
				array_push($msg_ids,$id);
			}

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getMessagesById", array($msg_ids));
            }

			$this->transport->open();
			$result = $this->client->getMessagesById( $msg_ids );
			$this->logger->debug("result summary".print_r($result , true));
			$this->transport->close();
		}
		catch(Exception $e){
			$this->logger->error("nsadmin : Exception in addEmailSendingRule: " .$e->getMessage());
			$this->transport->close();
		}
		return $result;
	}

	public function getCreditsLog( $org_id )
	{
		try
		{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getCreditsLog", $org_id);
            }

			$this->transport->open();
			$result = $this->client->getCreditsLog( $org_id );
			$this->logger->debug("result summary".print_r($result , true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in getCreditsLog: ".$e->getMessage());
			$this->transport->close();
		}
		return $result;
	}

	public function getMessageSendError( $message_id) {
		try
		{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getMessageSendError", $message_id);
            }

			$this->transport->open();
			$result = $this->client->getMessageSendError( $message_id );
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception while getting the message's send error: ".$e->getMessage());
			$this->transport->close();

		}
		return $result;
	}

	public function getMsgSummary($select_criteria, $start_time, $end_time) {
		try
		{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getMsgSummary", array($select_criteria, $start_time, $end_time));
            }

			$this->transport->open();
			$result = $this->client->getMsgSummary($select_criteria, $start_time, $end_time);
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception while getting the message summary: ".$e->getMessage());
			$this->transport->close();
		}
		return $result;
	}

	public function getSummaryAvailableCampaigns($org_id) {
	try
		{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("getSummaryAvailableCampaigns", $org_id);
            }

			$this->transport->open();
			$result = $this->client->getSummaryAvailableCampaigns($org_id);
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception while getting the summary available messages: ".$e->getMessage());
			$this->transport->close();
		}
		return $result;
	}

	/**
	 * Resubscribe using email.
	 *
	 */
	 public function resubscribeToEmail( $email , $org_id ){

		   try{

               if($GLOBALS['cfg']['mock_mode'] == true){
                   return $this->cb_client->handleMock("resubscribeToEmail", array( $email , $org_id));
               }

                $this->transport->open();
                //$result = $this->client->resubscribeToEmail( $email , $org_id );
                $result = $this->C_subscription_client->subscribeUser(
                		$org_id, $email, "EMAIL", "USER", "NONE", "ALL" , 
                		null , null , "NSADMIN RESUBSCRIBE TO EMAIL" );
                $this->logger->info("nsadmin email resubscribe result : ".print_r( $result , true ) );
			    $this->transport->close();

		   }catch( Exception $e ){
			       $this->logger->error("nsadmin : Exception while resubscribe
			       								   by email : ".$e->getMessage());
				   $this->transport->close();
			}
			return $result;
	}

	/**
	 * update Sending Rule
	 */
	public function updateSendingRule( $old_receiver , $new_receiver , $org_id , $type ){

		try{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("updateSendingRule", array( $old_receiver , $new_receiver , $org_id , $type));
            }

			$this->transport->open();
			//$result = $this->client->updateSendingRule( $old_receiver , $new_receiver , $org_id , $type );
			$result = $this->C_subscription_client->changeTargetValue(
					$org_id, $old_receiver, $new_receiver, $type );
			$this->logger->info("nsadmin update sending rules result : ".print_r( $result , true ) );
			$this->transport->close();

		}catch( Exception $e ){

			$this->logger->error("nsadmin : Exception while updating
			       								   sending rule : ".$e->getMessage());
			$this->transport->close();
		}
		return $result;
	}

	public function updateSubscriptionStatus($payload)
	{
		$this->logger->debug("nsadmin updateSubscriptionStatus payload got. count : ".count($payload));
		$sendingRules=array();
		foreach($payload as $pl)
		{
			if(!isset($pl['org_id']))
				$pl['org_id']=0;
			$sr=new nsadmin_SendingRuleDetails();
			$sr->receiver=$pl['receiver'];
			$sr->orgId=$pl['org_id'];
			switch(strtoupper($pl['sending_rule']))
			{
				case 'ALL':
					$sr->rule=SendingRule::ALL;
					break;
				case 'NOBULK':
					$sr->rule=SendingRule::NOBULK;
					break;
				case 'NOPERSONALIZED':
					$sr->rule=SendingRule::NOPERSONALIZED;
					break;
				case 'NONE':
					$sr->rule=SendingRule::NONE;
					break;
				case 'UNSUBSCRIBE':
					$sr->rule=SendingRule::UNSUBSCRIBE;
					break;
			}
			switch(strtoupper($pl['message_class']))
			{
				case 'EMAIL':
					$sr->messageClass=MessageClass::EMAIL;
					break;
				case 'SMS':
					$sr->messageClass=MessageClass::SMS;
					break;
			}

			$sendingRules[]=$sr;
		}

		$this->logger->debug("sendingRules objects created. count : ".count($sendingRules));

		$this->logger->info("starting thrift call to do the subscription update");
		try{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("resubscribeToEmail", array( $old_receiver , $new_receiver , $org_id , $type));
            }

			$this->transport->open();
			//$result = $this->client->updateSendingRules($sendingRules);
			$result = $this->C_subscription_client->processSendingRules($sendingRules);
			$this->transport->close();
			$this->logger->debug("thrift call successful. nsadmin returned ".var_export($result,true));

		}catch(Exception $e){
			$this->logger->error("nsadmin: Exception when updating subscription status".$e->getMessage());
			$this->transport->close();
		}

		$this->logger->info("returning from nsadmin client");

		return $result;

	}

	/**
	 * Resubscribe using sms.
	 *
	 */
	public function resubscribeToSms( $mobile , $org_id ){

		try{

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("resubscribeToSms", array( $mobile , $org_id));
            }

			$this->transport->open();
			//$result = $this->client->resubscribeToSms( $mobile , $org_id );
			$result = $this->C_subscription_client->subscribeUser(
					$org_id, $mobile, "SMS", "USER", "NONE", "ALL" ,
					null , null , "NSADMIN RESUBSCRIBE TO SMS" );
			$this->logger->info("nsadmin sms resubscribe result : ".print_r( $result , true ) );
			$this->transport->close();

		}catch( Exception $e ){
			$this->logger->error("nsadmin : Exception while resubscribe
			       								   by email : ".$e->getMessage());
			$this->transport->close();
		}
		return $result;
	}

	public function whitelistEmailIds($email_ids) {
		try {

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("whitelistEmailIds", $email_ids);
            }

			$this->transport->open();
			$this->logger->info("Whitelisting the email ids : " . print_r($email_ids , true ));
			$result = $this->client->whitelistEmailIds($email_ids);
			$this->transport->close();
		} catch( Exception $e ){
			$this->logger->error("nsadmin : Exception while whitlisting email ids: ".$e->getMessage());
			$this->transport->close();
			return false;
		}
		return $result;
	}

	public function setWhitelistingGatewayForOrg($org_id, $short_name) {
		try {

            if($GLOBALS['cfg']['mock_mode'] == true){
                return $this->cb_client->handleMock("setWhitelistingGatewayForOrg", array($org_id, $short_name));
            }

			$this->transport->open();
			$this->logger->info("Setting $short_name as email whitlisting gateway for org $org_id");
			$result = $this->client->setWhitelistingGateway($org_id, $short_name);
			$this->transport->close();
		} catch( Exception $e ){
			$this->logger->error("nsadmin : Exception while setting whitlisting gateway ".$e->getMessage());
			$this->transport->close();
			return false;
		}
		return $result;
	}

	public function updateOrgStatus($org_id, $active, $updated_by, $updated_at, $add_custom_sender=2) {
		try {
			$org_status_details = $this->createOrgStatusDetails($org_id, $active, $updated_by, $updated_at, $add_custom_sender);
			$this->transport->open();
			$result = $this->client->updateOrgStatus($org_status_details);
			$this->logger->debug("result :: ".print_r($result , true));
			$this->transport->close();
		} catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in updateOrgStatus: ".$e->getMessage());
			$this->transport->close();
			return false;
		}
		return $result;
	}
	
	public function getEmailGatewayConfig($short_name) 
	{
		try {
			$this->transport->open();
			$result = $this->client->getEmailGatewayConfig($short_name);
			$this->logger->debug("email gateway result :: ".print_r($result , true));
			$this->transport->close();
			return $result;
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in getEmailGatewayConfig: ".$e->getMessage());
			$this->transport->close();
			return null;
		}
	}
	
	public function setFileHandles($file_handles)
	{
		$this->file_handles = $file_handles;
	}

	//domain gateway methods

	public function getDomainPropertiesByOrg($org_id)
	{
		try {
			$this->transport->open();
			$result = $this->client->getDomainPropertiesByOrg($org_id);
			$this->transport->close();
			return $result;
		}
		catch(Exception $e) {
			$this->logger->error("getDomainPropertiesByOrg nsadmin exception ".$e->getTraceAsString() );
			$this->transport->close();
			throw $e;
		}

	}

	public function getDomainPropertiesByID($id)
	{
		try {
			$this->transport->open();
			$result = $this->client->getDomainPropertiesByID($id);
			$this->transport->close();
			return $result;
		}
		catch(Exception $e) {
			$this->logger->error("getDomainPropertiesByID nsadmin exception ".$e->getTraceAsString() );
			$this->transport->close();
			throw $e;
		}

	}

	public function insertDomainProperties($domainProperties)
	{
		try {
			$this->transport->open();
			$this->client->insertDomainProperties($domainProperties);
			$this->transport->close();
			$this->logger->debug("insertDomainProperties successful");
		}
		catch(nsadmin_NSAdminException $e) {
			$this->logger->error("insertDomainProperties nsadmin exception ".$e->getTraceAsString());
			$this->transport->close();
			throw new Exception($e->what);
		}
	}

	public function updateDomainProperties($domainProperties)
	{
		try {
			$this->transport->open();
			$this->client->updateDomainProperties($domainProperties);
			$this->transport->close();
			$this->logger->debug("updateDomainProperties successful");
		}
		catch(nsadmin_NSAdminException $e) {
			$this->logger->error("updateDomainProperties nsadmin exception ".$e->getTraceAsString());
			$this->transport->close();
			throw new Exception($e->what);
		}
	}

	public function disableDomainProperties($id, $orgId)
	{
		try {
			$this->transport->open();
			$this->client->disableDomainProperties($id, $orgId);
			$this->transport->close();
			$this->logger->debug("disableDomainProperties successful");
		}
		catch(nsadmin_NSAdminException $e) {
			$this->logger->error("disableDomainProperties nsadmin exception ".$e->getTraceAsString());
			$this->transport->close();
			throw new Exception($e->what);
		}
	}

	public function getGatewayFactoryConfigs()
	{
		try {
			$this->transport->open();
			$result = $this->client->getGatewayFactoryConfigs();
			$this->transport->close();
			return $result;
		}
		catch(Exception $e) {
			$this->logger->error("getGatewayFactoryConfigs   nsadmin exception ".$e->getTraceAsString() );
			$this->transport->close();
			throw $e;
		}

	}

	public function getDomainPropertiesGatewayMapByOrg($orgId, $type = 'EMAIL')
	{

		try {

			switch ($type) {

				case 'EMAIL':
						$messageClass = MessageClass::EMAIL;
					break;
				
				case 'ANDROID':
						$messageClass = MessageClass::ANDROID;
					break;

				case 'IOS':
						$messageClass = MessageClass::IOS;
					break;		
			}

			$this->transport->open();
			$domainGatewayMaps = $this->client->getDomainPropertiesGatewayMapByOrg($orgId, $messageClass);			
			$this->transport->close();
			return $domainGatewayMaps;
		}
		catch(Exception $e) {
			$this->logger->error("getDomainPropertiesGatewayMapByOrg nsadmin exception ".$e->getTraceAsString() );
			$this->transport->close();
			throw $e;
		}
	}

	public function saveDomainGatewayMap($gatewayMap)
	{
		try {
			$this->transport->open();
			$this->client->saveDomainPropertiesGatewayMap($gatewayMap);
			$this->transport->close();
			$this->logger->debug("savedDomainGatewayMap successful");
			return true;
		}
		catch(nsadmin_NSAdminException $e) {
			$this->logger->error("savedDomainGatewayMap nsadmin exception ".$e->getTraceAsString());
			$this->transport->close();
			throw new Exception($e->what);
		}
	}

	/**
	 * validate domain gateway mapping manually
	 */
	public function validateMap($domainPropGatewayMapId, $triggeredBy)
	{
		try {
			$this->transport->open();
			$result = $this->client->validateDomain($domainPropGatewayMapId, $triggeredBy);
			$this->transport->close();
			$this->logger->debug("validateMap ".print_r($result,true));
			return $result;
		}
		catch(Exception $e) {
			$this->logger->error("validateDomain nsadmin exception ".$e->getTraceAsString() );
			$this->transport->close();
			throw $e;
		}

	}
	
	public function disableDomainPropertiesGatewayMap($domainPropGatewayMapId)
	{
		try {
			$this->transport->open();
			$this->client->disableDomainPropertiesGatewayMap($domainPropGatewayMapId);
			$this->transport->close();
			$this->logger->debug("disableDomainPropertiesGatewayMap ".$domainPropGatewayMapId);
			return $result;
		}
		catch(nsadmin_NSAdminException $e) {
			$this->logger->error("disableDomainPropertiesGatewayMap nsadmin exception ".$e->getTraceAsString());
			$this->transport->close();
			throw new Exception($e->what);
		}
	}

	public function getPostfixHeaderChecks() {
		try {
			$this->transport->open();
			$result = $this->client->getHeaderChecks();
			$this->logger->debug("get postfix gateway header checks list :: ".print_r($result , true));
			$this->transport->close();
			return $result;
		}catch(Exception $ex) {
			$this->logger->error("nsadmin : Exception in getPostfixHeaderChecks: ".$ex->getMessage());
			$this->transport->close();
			return null;
		}
	}

	public function savePostfixHeaderChecks($hc_params, $user_id) {
		$serverReqId = $this->serverReqId;
		$this->logger->debug("hc_params_thrift: " . print_r($hc_params, true) . "user ID: " . $user_id . "request ID: " . $serverReqId);
		try {
			$this->transport->open();
			$result = $this->client->updatePostfixHeaderChecks($hc_params, $user_id, $serverReqId);
			$this->logger->debug("save postfix gateway result :: ".print_r($result , true));
			$this->transport->close();
			return $result;
		}catch(Exception $ex) {
			$this->logger->error("nsadmin : Exception in savePostfixHeaderChecks: ".$ex->getMessage());
			$this->transport->close();
			return null;
		}
	}

	public function getPostfixGateways() {
		try {
			$this->transport->open();
			$result = $this->client->getGatewayPasswords();
			$this->logger->debug("get postfix gateway password list :: ".print_r($result , true));
			$this->transport->close();
			return $result;
		}catch(Exception $ex) {
			$this->logger->error("nsadmin : Exception in getPostfixGateways: ".$ex->getMessage());
			$this->transport->close();
			return null;
		}	
	}

	public function savePostfixPasswords($gp_params, $user_id) {
		$serverReqId = $this->serverReqId;
		$this->logger->debug("updatePostfixGatewayPassword thrift: " . print_r($gp_params, true));
		try {
			$this->transport->open();
			$result = $this->client->updatePostfixGatewayPassword($gp_params, $user_id, $serverReqId);
			$this->logger->debug("save postfix gateway result :: ".print_r($result , true));
			$this->transport->close();
			return $result;
		}catch(Exception $ex) {
			$this->logger->error("nsadmin : Exception in savePostfixPasswords: ".$ex->getMessage());
			$this->transport->close();
			return null;
		}
	}

	public function getDomainPropertiesGatewayMapByID($id) {

		try {
			$this->transport->open();
			$result = $this->client->getDomainPropertiesGatewayMapByID($id);
			$this->logger->debug("thrift getDomainPropertiesGatewayMapByID :: ".print_r($result , true));
			$this->transport->close();
			return $result;
		}catch(Exception $ex) {
			$this->logger->error("nsadmin : thrift getDomainPropertiesGatewayMapByID: ".$ex->getMessage());
			$this->transport->close();
			return null;
		}
	}
	
	public function getOrgGatewaysByCampaign($campaigns)
	{
		try
		{
			$this->transport->open();
			$result = $this->client->getOrgGatewaysByCampaign($this->createCamapignRequests($campaigns), 
					 Util::getServerUniqueRequestId());
			$this->logger->debug("nsadmin getOrgGatewaysByCampaign".print_r($result,true));
			$this->transport->close();
		}
		catch(Exception $e) {
			$this->logger->error("nsadmin : Exception in getOrgGatewaysByCampaign: ".$e->getMessage());
			$this->transport->close();
		}
		return $result;
	}

	private function createCamapignRequests($campaigns){

		$campaign_requests = array();
		foreach ($campaigns as $campaign) {
			$campaign_request =  new nsadmin_CampaignRequest();
			$campaign_request->orgId = $campaign['org_id'];
			$campaign_request->campaignId = $campaign['campaign_id'];
			array_push($campaign_requests, $campaign_request);
		}

		return  $campaign_requests;
	}

	public function addDomainGatewayMapForMobilePush($orgId,$licensecode,$authToken,$campaignId,$variationId){
		try{
			$this->transport->open();
			$result = $this->client->addDomainGatewayMapForMobilePush($orgId,$licensecode,$authToken,$campaignId,$variationId);
			$this->logger->debug("nsadmin addDomainGatewayMapForMobilePush".print_r($result,true));
			$this->transport->close();
		}
		catch(Exception $e){
			$this->logger->error("nsadmin : Exception in addDomainGatewayMapForMobilePush: ".$e);
		}
		return $result;
	}

}
?>
