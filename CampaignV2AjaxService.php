<?php
include_once 'base_model/campaigns/class.BulkMessage.php';
include_once 'helper/scheduler/CampaignGroupCallBack.php';
include_once 'services/applications/impl/applications/ApplicationTypes.php';
include_once 'services/applications/impl/applications/ApplicationFactory.php';
include_once 'services/applications/impl/features/types/campaigns/CampaignServiceFeatureTypeImpl.php';
include_once 'base_model/class.OrgDetails.php';
include_once 'business_controller/health_dashboard/HealthDashboardController.php';
include_once 'ui/widget/base/WidgetFactory.php';
include_once 'business_controller/emf/BounceBackController.php';
include_once 'business_controller/campaigns/library/VenenoDataDetailsHandler.php';
include_once 'base_model/campaigns/class.CommunicationDetailsService.php';
include_once 'business_controller/campaigns/OutboundController.php';
include_once 'business_controller/campaigns/surveys/SurveyController.php';
include_once 'helper/coupons/CouponSeriesManager.php';
include_once 'base_model/campaigns/class.GroupDetailService.php';
include_once 'business_controller/OrganizationController.php';
include_once 'helper/simple_html_dom.php';
include_once 'thrift/conquestdata.php';
include_once 'business_controller/campaigns/CampaignMetadataController.php';
include_once 'business_controller/EntityController.php';

/**
 * The campaign v2 level ajax service support
 * creating this file to expose all the methods for backbone campaign
 * @author nayan
 */
class CampaignV2AjaxService extends BaseAjaxService{

	private $OrgController;
	private $C_campaign_controller;
	private $campaign_model;
	private $client;
	private $current_org;
	private $emf_client;
	private $C_config_mgr;
	private $C_coupon_series;
	private $C_ref_controller;
	private $C_org_controller;
	private $C_outbound_controller;
	private $C_couponSeriesManager;
	private $C_bounceback_controller;
	private $C_veneno_bucket_handler;
	private $C_survey_controller;
	private $C_conquest_data_service;
	private $C_lifecyle_controller;
	private $C_metadata_controller;

	public function __construct( $type, $params = null ){

		global $url_version, $currentorg, $currentuser;

		parent::__construct( $type, $params );
		
		$url_version = '1.0.0.1';
		Auth::session_force_end();
		
		//To load the cheetah's organizational model extension
		$org_id = $currentorg->org_id;
		$currentorg = new OrganizationModelExtension();
		$currentorg->load( $org_id );

		$this->current_org = $currentorg;

		$this->OrgController = new OrganizationController();
		$this->C_campaign_controller = new CampaignController();
		$this->campaign_model = new CampaignBaseModel();
		$this->C_couponSeriesManager = new CouponSeriesManager();
		$this->C_bounceback_controller = new BounceBackController();
		$this->C_config_mgr = new ConfigManager();
		$this->C_outbound_controller = new OutboundController();
		$this->C_ref_controller = new ReferralCampaignController();
		$this->C_coupon_series = new CouponSeriesManager();
		$this->C_GroupDetailModel=new GroupDetailModel();
		$this->C_org_controller = new OrganizationController();
		$this->C_survey_controller = new SurveyController();
		$this->C_conquest_data_service = new ConquestDataThriftClient();
		$this->C_metadata_controller = new CampaignMetadataController();
		//$this->reach_controller = new ReachabilityController();
	}

	//check the campaign validity if not of same campaign throw exception
	public function isValidCampaign(){

		if( !isset( $_GET['campaign_id'] ) ) return true;

		$campaign_id = $_GET['campaign_id'];
		$this->C_campaign_controller->load($campaign_id);
		$hash = $this->C_campaign_controller->getDetails();

		if( $this->org_id != $hash['org_id'] )
			return false;

		return true;
	}

	public function process(){

		$this->logger->debug( 'Checking For Type : ' . $this->type );

		switch ( $this->type ){

			//this method is called from header of each campaign :)
			case 'get_campaign_details':

				//check the validity of campaign
				if( !$this->isValidCampaign() ){

					$this->data['istatus'] = 'fail';
					$this->data['error_msg'] = _campaign("Campaign selected doesn't belong to this organization");
					return;
				}

				$this->getCampaignDetails();
				break;

			case 'get_campaign_data' :
				$this->getCampaignData($this->params);
				break;
			case 'get_performance' :
				$this->logger->debug('In Performamce view data');
				$this->getPerformance();
				break;
			case 'get_org_credits':
				$this->getOrgSmsCredits();
				break;
			case 'get_sticky_data':
				$this->getStickyData();
				break;
			case 'add_remove_customer':
				$this->addRemoveCustomer($this->params);
				break;
			case 'overview' :
				$this->getOverviewDetails();
				break;
			case 'create_campaign':
				$this->createNewCampaign();
				break;
			case 'update_campaign':
				$this->updateCampaignDetails();
				break;
			case 'validate_stores':
				$this->validateStores();
				break;
			case 'create_coupon':
				$this->createNewCouponSeries();
				break;
			case 'get_auth_details':
				$this->getMessageDetailsForAuthorization();
				break;
			case 'approve_msg':
				$this->approveOutboundMessage();
				break;
			case 'reject_msg':
				$this->rejectOutboundMessage();
				break;
			case 'get_queued_msgs':
				$this->getOutboundQueuedMessages();
				break;
			case 'edit_coupon':
				$this->editCouponDetails();
				break;
			case 'recipient':
				$this->logger->debug('@@@Inside Recipient Ajax Service');
				$this->getRecipient();
				break;
			case 'recipientv2':
				$this->logger->debug('@@@Inside Recipient Ajax Service');
				$this->getRecipientv2();
				break;
		    case 'changeFavourite' :
				$this->logger->debug( 'Change Favourite Type For Group : '. $_GET['group_id'] );
				$this->C_campaign_controller->changeFavouriteTypeForGroup( $_GET['group_id'] );
				break;
			case 'update_advance_coupon':
				$this->updateCouponDetails();
				break;

			case 'group_details':
				$this->groupDetails($this->params);
				break;
			case 'campaign_list':
				$this->getCampaignList($this->params);
				break;
			case 'duplicate_list':
				$this->duplicateCustomerList( $this->params );
				break;
			case 'refresh_list':
				$this->refreshDuplicateList( $this->params );
				break;
			case 'retry_schedule':
				$this->retryReportSchedule( $this->params );
				break;
			case 'disable_schedule':
				$this->disableScheduledReport( $this->params );
				break;
			case 'get_survey_form':
				$this->getSurveyFormHtml();
				break;
			case 'download_email_reports' :
				$this->downloadEmailReports();
				break;
			case 'is_campaign_exist':
				$this->checkCampaignExistForOrg();
				break;
			case 'get_coupon_details':
				$this->getCouponSeriesDetails();
				break;
			case 'get_brands':
				$this->getBrands();
				break;
			case 'get_categories':
				$this->getCategories();
				break;
			case 'get_selected_categories' :
				$this->getSelectedCategories();
				break;
			case 'save_coupon_validity':
				$this->addCouponProductValue();
				break;
			case 'save_coupon_validity_sku':
				$this->addCouponSKUProductValue();
				break;
			case 'get_coupon_prouct_details':
				$this->getCouponProductDetails();
				break;
			case 'get_selected_skus':
				$this->getSelectedSku();
				break;
			case 'get_stores_redeem_info' ;
				$this->getStoresRedeemInfo() ;
				break ;		
			case 'cache_coupon' :
				$this->cacheCoupon() ;
				break ;	
			case 'download_sample_csv' :
			$this->logger->debug("In Upload Audience Page Download Sample File");	
			$this->downloadSampleCSVFile();
			default:
				$this->logger->debug('@@@Invalid Type Passed');
		}
	}


	private function getCampaignData($params){

		$this->logger->debug( "dashboard campaigns data" );

		$where_filter =
			$this->C_outbound_controller->getWhereClauseForCampaignType(
					$params[0],$params[1] );

		$limit_filter =
			$this->C_outbound_controller->getLimitFilter(
					$params[2],$params[3] );

		$search_filter =
			$this->C_outbound_controller->getWhereClauseFilter( $params[4] );

		$campaign_data =
			$this->C_outbound_controller->
				createTableContentForNewHomePage(
						$where_filter, $search_filter , $limit_filter );

		$this->data['listitem'] = $campaign_data;
	}

	private function getPerformance(){
		include_once "business_controller/campaigns/reports/embedded/impl/EmbeddedROIReportsProvider.php" ;
    	include_once 'business_controller/campaigns/reports/embedded/api/ROIReportsTypes.php';
    	$embdReprtObj = new EmbeddedROIReportsProvider();
    	$iframeSrc = $embdReprtObj->getReport( ROIReportsTypes::$OVERALL_PERFORMANCE,array());
		$this->logger->debug("campaignV2ajaxservice : overall perfomance source ".$iframeSrc);
		$this->data['url'] = $iframeSrc;
		$this->data['info'] = 'success';
	}

	private function getOrgSmsCredits(){

		$method = $_SERVER['REQUEST_METHOD'];

		if( $method == 'GET'){

			$credit = $this->C_campaign_controller->getBulkSmsCredit();
			$this->logger->debug('bulk credit value: '.$credit);
			$credit = $credit?$credit:0;
			$this->data['sms_credit'] = $credit;
			$this->data['email_credit'] = 0;
		}
		else if($method == 'POST'){

			$this->logger->debug('@@@Inside Renew Credit@@@');
			$credit = json_decode(file_get_contents('php://input'), true);

			$credit = $this->sanitizeRequest ( $credit );
			
			$this->logger->debug('save credit value: '.print_r($credit, true));

			$health = new HealthDashboardController();
			$params['bulk_credits'] = $credit['sms_credit'];
			$params['value_credits'] = 0;

			try{
				$status = $health->sendMailForCreditsRequest( $params );
				if( $status ){
					$this->data['istatus'] = 'success';
					$this->data['credit'] = $credit;
				}
			}catch( Exception $e ){
				$this->data['istatus'] = 'fail';
				$this->data['error_msg'] = $e->getMessage();
			}
		}
	}


	private function getStickyData(){

		$data=$this->C_campaign_controller->campaign_model->getCampaignGroupsByCampaignIds( -20 );

		for($i=0;$i<count($data);$i++){
			$id=$data[$i]["group_id"];
			$data[$i]["html"]="<span class='cu-add'><a href=#addcustomer/$id id=customer$id>"._campaign("Add Customer")."</a></span>
						        <span class='cu-remove'><a href=#removecustomer/$id id=r-customer$id>"._campaign("Remove")."</a></span>
						        <span class='cu-group'><a href=#addgroup/$id id=g-customer$id>"._campaign("Add Group Tags")."</a></span>";
		}
		$this->data['aaData']=$data;

	}

	private function addRemoveCustomer($param){
		$mobile = $param[0];
		$email = $param[1];
		$operation = $param[2];
		$group_id = $param[3];
		$campaign_id = $param[4];

		list( $first_name , $last_name ) = Util::parseName( $param[5] );
		$first_name = addslashes( $first_name );
		$last_name =  addslashes( $last_name );
		
		try{
			//Checking for if mobile or email id is valid or not.
			if( $mobile ){
				if( !Util::checkMobileNumber( $mobile ) )
					throw new Exception( _campaign("Please add valid Mobile Number") );
				else
					$user = UserProfile::getByMobile( $mobile );
			}else if( $email ){
				if( !Util::checkEmailAddress( $email ) )
					throw new Exception( _campaign("Please add valid Email Address") );
				else
					$user = UserProfile::getByEmail( $email );
			}else
				throw new Exception(_campaign(" Please Enter Mobile or Email to ").$operation._campaign("subscriber") );

			//Checking for operation to perform add or remove
			if( $operation == 'remove' ){

				if( $user->user_id ){
					$status = $this->C_campaign_controller->campaign_model->removeCustomerBYUserId( $group_id , $user->user_id );
					if( !$status )
						throw new Exception( _campaign("Customer does not exist in the list!") );
				}else
					throw new Exception( _campaign("Customer does not exist in the list!") );

			}else if( $operation == 'add' ){
				
				if( !empty($mobile) && !Util::checkMobileNumber( $mobile ) ){
					throw new Exception( _campaign("Please add valid Mobile Number") );
				}
					
				if( !empty($email) && !Util::checkEmailAddress( $email ) ){
					throw new Exception( _campaign("Please add valid Email Address") );
				}
					
				//Add new Subscriber
				$auth = Auth::getInstance();
				if( $mobile ){
					$auth->registerAutomaticallyByMobile( $this->current_org , $mobile , $first_name , $last_name , $email );
					$user = UserProfile::getByMobile( $mobile );
				}
				else{

					$auth->registerAutomaticallyByEmail( $this->current_org , $email , $first_name , $last_name , $mobile );
					$user = UserProfile::getByEmail( $email );
				}
				$this->logger->debug($user->user_id);
				if( $group_id > 0 && $user->user_id > 0 ){
					$this->logger->debug('--came inside');
					$user_name = $user->first_name .' '. $user->last_name;

					$is_mobile_exists = ( strlen( $user->mobile ) > 0 )?( 1 ):( 0 );
					$is_email_exists = ( strlen( $user->email ) > 0 )?( 1 ):( 0 );
					$custom_tags = json_encode( array( 'custom_tag_1' => rawurldecode($param[6]) ,
							'custom_tag_2' => rawurldecode($param[7]) ) );

					$tags = addslashes( $custom_tags );
					$user_name = addslashes( $user_name );
					$insert_user = "('$group_id', '$user->user_id', '$user_name', 'customer',
					'$tags' , $is_mobile_exists , $is_email_exists
					)";

					$this->logger->debug('@@@in campaign->> 1');
					$status = $this->C_campaign_controller->campaign_model->
					addSubscriberInGroupInBatches(
						$insert_user,
						$user->user_id,
						$group_id);
					if( !$status )
						throw  new Exception(_campaign("Customer already exists in the list !"));
				}
			}

			}catch( Exception $e ){
			$this->data['error'] = $e->getMessage();
		}

	}

	private function getOverviewDetails(){

		$this->logger->debug("@@Campaign Overview Details");

		$campaign_id = $_GET["campaign_id"];

		$details = $this->C_outbound_controller->getOverviewDetailsByCampaignId( $campaign_id );

		$this->data["camp_details"] = $details;
		$this->data["camp_details"]['reachability'] = $this->C_outbound_controller->getReachableCustomer($campaign_id , ALL);
		$this->data['weChat_accounts'] = $this->C_outbound_controller->getWeChatAccounts( $this->org_id);

		$this->data['mobilepush_accounts'] = $this->C_outbound_controller->getMobilePushAccounts();
		
		$obj_elems = $this->C_metadata_controller->getObjectives();
		$this->data['obj_elems'] = json_encode( $obj_elems, true );

		$objective_md = $this->C_metadata_controller->getObjectiveMapping($campaign_id);
		$this->data['obj_md'] = $objective_md;

		$inc_elems = $this->C_metadata_controller->getIncentives();
		$this->data['inc_elems'] = json_encode( $inc_elems, true );
	}

	private function createNewCampaign(){

		$method = $_SERVER['REQUEST_METHOD'];

		if( $method == 'GET'){

			$ref_list = $this->C_campaign_controller->getActiveCampaignsByTypeForOrg('referral');
			if( empty( $ref_list ) )
				$ref_list = array( _campaign("No referral campaign available") => -1 );

			$survey_list = $this->C_campaign_controller->getActiveCampaignsByTypeForOrg('survey');
			if( empty( $survey_list ) )
				$survey_list = array( _campaign("No survey campaign available") => -1 );
			
			$reco_plan_list = $this->C_campaign_controller->getRecoProductAttribPlan();
			$this->logger->debug(_campaign("reco form tags : got plans - ").print_r($reco_plan_list,true));
			
			if( empty( $reco_plan_list) )
				$reco_plan_list = array( _campaign("No recommendation plans available") => -1);
			
			$report_types = $this->C_outbound_controller->getRoiTypes();
			if( empty( $report_types ) )
				$report_types = array( _campaign('ONETIME') => 1 );
			else{
				$new_types = array();
				foreach( $report_types as $key => $val ){
					$new_types[ _campaign($key) ] = $val;
				}
				$report_types = $new_types;
			}

			$survey_type = $this->C_config_mgr->getKey( CAMPAIGN_CONFIG_SURVEY_TYPE );
			$survey_type = explode(',',$survey_type);
			if( !$survey_type )
				$survey_type = array( 'ONLINE' => 'ONLINE' );
			
			$cc_survey_enabled  = $this->C_config_mgr->getKey( CONF_CLOUDCHERRY_SURVEY_ENBALE );
			
			if($cc_survey_enabled) {
				$survey_type['CLOUDCHERRY'] = 'CLOUDCHERRY' ;
			}
			
			$this->logger->debug('ref active campaigns: '.print_r($ref_list, true) );
			$this->data['ref_list'] = json_encode( $ref_list, true );
			$this->logger->debug('survey active campaigns: '.print_r($survey_list, true) );
			$this->data['survey_list'] = json_encode( $survey_list, true );
			$this->logger->debug('survey types: '.print_r($survey_type, true) );
			$this->data['survey_type'] = json_encode( $survey_type, true );
			$this->logger->debug('roi types: '.print_r($report_types, true) );
			$this->data['roi_report_type'] = json_encode( $report_types, true );
			$this->data['c_org_id'] = $this->org_id;
			$this->data['reco_plan_list'] = json_encode( $reco_plan_list, true );
			$this->logger->debug('reco form tags : recommendation plans: '.print_r($reco_plan_list, true) );

			// Default time is 9am to 9pm
			$this->data['c_min_sms_hour'] = "9";
			$this->data['c_max_sms_hour'] = "21";
			if(isset($this->current_org->min_sms_hour)) {
				$this->data['c_min_sms_hour'] = $this->current_org->min_sms_hour;
			}
			
			if(isset($this->current_org->max_sms_hour)) {
				$this->data['c_max_sms_hour'] = $this->current_org->max_sms_hour;
			}
			
			$this->data['is_schedule'] = 0;
			$res = $this->C_outbound_controller->getCampaignScheduleMetadata( $this->org_id );
			if( $res->isSchedulingEnabled )
				$this->data['is_schedule'] = 1;

			$ui_elements = $this->C_metadata_controller->getObjectives();
			$this->data['ui_elements'] = json_encode( $ui_elements, true );

			$is_ou_enabled = $this->C_org_controller->isOuEnabled( $this->org_id );
			$this->logger->debug('For org id : '.$this->org_id.' ou enabled : '.$is_ou_enabled);
			if($is_ou_enabled){
				$org_units = $this->C_org_controller->ConceptController->getOrgUnitsDetails();
				if($org_units)
					$this->data['org_units'] = json_encode($org_units, true);
			}
		}
		else if($method == 'POST'){

			include_once 'helper/db_manager/TransactionManager.php';
			try{
				$this->logger->debug('start create new campaign');

				//start transaction
				$C_transaction_manager = new TransactionManager();
				$C_transaction_manager->beginTransaction();

				$post_params = json_decode(file_get_contents('php://input'), true);
				$form_data = $post_params['camp_data'];
				$camp_data = array();
				parse_str($form_data, $camp_data);

				$this->logger->debug('@@@camp data params: '.print_r($camp_data,true));

				$camp_data = $this->sanitizeRequest( $camp_data );

				$camp_data['campaign_name'] = trim($camp_data['campaign_name']);
				$camp_data['campaign_desc'] = trim($camp_data['campaign_desc']);
				$camp_data['campaign_objective'] = trim($camp_data['campaign_objective']);
				$camp_data['campaign_incentive'] = trim($camp_data['campaign_incentive']);
				$camp_data['campaign_tags'] = trim($camp_data['campaign_tags']);
				$camp_data['ou_selected_id'] = trim($camp_data['ou_selected_id']);

				if( $camp_data['c_org_id'] != $this->org_id ){
					throw new Exception(ORG_CHANGED_MESSAGE);
				}
				$this->logger->debug('@@Quick Creation Params : '.print_r( $camp_data , true ) );

				if( $camp_data['campaign_type'] != 'timeline' ){
					
					if( strtotime($camp_data['cnew_start_date']) >= strtotime($camp_data['cnew_end_date']) ){
						throw new Exception(_campaign("Invalid date range given!"));
					}
				}

				$campaign_type = $camp_data['campaign_type'];
				$camp_data['campaign_name'] = preg_replace('/\s+/', ' ',$camp_data['campaign_name']);

				if( empty( $camp_data['campaign_name'] ) ){
					throw new Exception(_campaign("Campaign name should not be blank."));
				}

				if( ( $camp_data['roi_report_type'] == "" ) && !isset( $camp_data['roi_report_type'] ) )
					$camp_data['roi_report_type'] = 1;

				if( $campaign_type == 'outbound' ){

					if( $camp_data['is_ga_enabled'] == 0 ){
						$camp_data['ga_name'] = '';
						$camp_data['ga_source_name'] = '';
					}

					if( $camp_data['is_test_control_enabled'] == 'on' ) $camp_data['is_test_control_enabled'] = 1;
					else $camp_data['is_test_control_enabled'] = 0;

					$ou_name = '';
					if(isset($camp_data['ou_selected_id']) && $camp_data['ou_selected_id']!=null && $camp_data['ou_selected_id'] != 'undefined'){
                            $entities = $this->OrgController->ConceptController->getByIds( $camp_data['ou_selected_id'] );
                            if(isset($entities) && $entities!=null) 
                                $ou_name = $entities[0]['name'].' - ';
                    } 


					$params = array( 'camp_type' => 'outbound',
							'start_date' => date('Y-m-d H:i:s', strtotime($camp_data['cnew_start_date'])),
							'end_date' => date('Y-m-d', strtotime($camp_data['cnew_end_date'])).' 23:59:59',
							'name' => $ou_name.$camp_data['campaign_name'],
							'desc' => addslashes( $camp_data['campaign_desc'] ),
							'is_ga_enabled' => $camp_data['is_ga_enabled'],
							'ga_name' => $camp_data['ga_name'],
							'ga_source_name' => $camp_data['ga_source_name'],
							'is_test_control_enabled' => $camp_data['is_test_control_enabled'],
							'campaign_roi_type_id' => $camp_data['roi_report_type']
					);

					$campaign_id = $this->C_campaign_controller->create( $params, 'outbound' , true );
					$this->logger->debug('@@Status : '.$campaign_id);

					if(isset($camp_data['ou_selected_id']) && $camp_data['ou_selected_id']!=null && $camp_data['ou_selected_id'] != 'undefined'){
						if($this->org_id==$camp_data['ou_selected_id'])
							$camp_data['ou_selected_id'] = -1;
						$this->C_campaign_controller->tagCampaignEntity($camp_data['ou_selected_id'],$campaign_id);
					}

					$metadata_mappings = array("org_id" => $this->org_id,
											   "campaign_id" => $campaign_id,
											   "campaign_objective" => $camp_data['campaign_objective']);
					$this->C_metadata_controller->addObjMappings($metadata_mappings);

					$campaign_tags = array("org_id" => $this->org_id,
										   "campaign_id" => $campaign_id,
										   "tags" => $camp_data['campaign_tags']);
					$this->C_metadata_controller->addCampaignTags($campaign_tags);

					if( !is_numeric( $campaign_id ) ){
						throw new Exception( $campaign_id );
					}

					if( $camp_data['isRefCamp'] == 'on' ) $camp_data['isRefCamp'] = 1;
					else $camp_data['isRefCamp'] = 0;

					if($camp_data['isRefCamp'] == 1 && $camp_data['referral_campaigns'] != -1)
						$this->C_campaign_controller->addCampaignMapping(
							$campaign_id, $camp_data['referral_campaigns'] );

					if( $camp_data['isSurveyCamp'] == 'on' ) $camp_data['isSurveyCamp'] = 1;
					else $camp_data['isSurveyCamp'] = 0;

					if($camp_data['isSurveyCamp'] == 1 && $camp_data['survey_campaigns'] != -1)
						$this->C_outbound_controller->addSurveyCampaignMapping($campaign_id,
								$camp_data['survey_campaigns']);
					
					if( $camp_data['isRecoCamp'] == 'on' ) $camp_data['isRecoCamp'] = 1;
					else $camp_data['isRecoCamp'] = 0;
					
					if($camp_data['isRecoCamp'] == 1 && $camp_data['reco_campaigns'] != -1)
						$this->C_outbound_controller->addRecoCampaignMapping($campaign_id,
								$camp_data['reco_campaigns']);
					
					if($camp_data['report_schedule'] == 'on')
						$res = $this->C_outbound_controller->createCampaignReportSchedule( 
													$campaign_id );

					$this->logger->debug('@@End Quick Creation Campaign : '.$campaign_id );

					$this->data['istatus'] = "success";
					$this->data['campaign_id'] = $campaign_id;
				}

				if( $campaign_type == 'survey' ){

					$params = array(
							'start_date' => date('Y-m-d H:i:s', strtotime($camp_data['cnew_start_date'])),
							'end_date' => date('Y-m-d', strtotime($camp_data['cnew_end_date'])).' 23:59:59',
							'name' => $camp_data['campaign_name'],
							'desc' => addslashes( $camp_data['campaign_desc'] ),
							'survey_type' => $camp_data['survey_type'],
							'brand_logo' => $camp_data['brand_logo']
					);

					$this->logger->debug('survey submit params: '.print_r($params, true));
					$campaign_id = $this->C_survey_controller->create($params);
					$metadata_mappings = array("org_id" => $this->org_id,
											   "campaign_id" => $campaign_id,
											   "campaign_objective" => $camp_data['campaign_objective']);
					$this->C_metadata_controller->addObjMappings($metadata_mappings);
					$this->data['istatus'] = "success";
					$this->data['campaign_id'] = $campaign_id;
				}

				if( $campaign_type == 'referral' ){

					if( $camp_data['is_test_control_enabled_for_referral'] == 'on' ){ 
						$camp_data['is_test_control_enabled_for_referral'] = 1;
					}
					else{ 
						$camp_data['is_test_control_enabled_for_referral'] = 0;
					}
					$params = array( 'camp_type' => 'referral',
							'start_date' => date('Y-m-d H:i:s', strtotime($camp_data['cnew_start_date'])),
							'end_date' => date('Y-m-d', strtotime($camp_data['cnew_end_date'])).' 23:59:59',
							'name' => $camp_data['campaign_name'],
							'desc' => addslashes( $camp_data['campaign_desc']),
							'is_test_control_enabled' => $camp_data['is_test_control_enabled_for_referral']
					);
					$this->logger->debug('process submit params: '.print_r( $params , true ) );

					if( $camp_data['online'] == 'on' ) $camp_data['online'] = 1;
					else $camp_data['online'] = 0;

					if ( $camp_data['online'] == '0' )
						$microsite = '';
					else{
						$microsite = $camp_data['microsite'];
						$microsite = filter_var($microsite, FILTER_VALIDATE_URL);
						if( !$microsite ){
							throw new Exception(_campaign("Entered link is not a valid url"));
						}
					}

					$campaign_id = $this->C_campaign_controller->create( $params, 'referral' , true );
					$metadata_mappings = array("org_id" => $this->org_id,
											   "campaign_id" => $campaign_id,
											   "campaign_objective" => $camp_data['campaign_objective']);
					$this->C_metadata_controller->addObjMappings($metadata_mappings);
					$this->logger->debug('@@Status : '.$campaign_id);

					if( !is_numeric( $campaign_id ) ){
						throw new Exception( $campaign_id );
					}

					if( $camp_data['defaultPos'] == 'on' ) $camp_data['defaultPos'] = 1;
					else $camp_data['defaultPos'] = 0;

					if( $camp_data['invite_loyalty'] == 'on' ) $camp_data['invite_loyalty'] = 1;
					else $camp_data['invite_loyalty'] = 0;

					if($camp_data['defaultPos'] == 1){
						$this->C_ref_controller->EditDefaultForPOS();
						$msg = $this->C_ref_controller->addReferralconfig($camp_data['refer_type']);
						if($msg){
							throw new Exception( $msg );
						}
					}

					$this->C_ref_controller->create($campaign_id, $camp_data['incentivize'],
							'unicode', $camp_data['defaultPos'], $microsite, $camp_data['invite_loyalty']);
					$this->logger->debug('@@End of referral campaign creation: '.$campaign_id);
					$this->data['istatus'] = "success";
					$this->data['campaign_id'] = $campaign_id;
				}
				
				if( $campaign_type == 'timeline' ){
					
					include_once 'business_controller/campaigns/timeline/LifeCycleCampaignController.php';
					$this->C_lifecyle_controller = new LifeCycleCampaignController();
					$this->logger->debug( "@@Start of Lifecylce Campaign creation" );
					
					if( $camp_data['is_ga_enabled'] == 0 ){
						$camp_data['ga_name'] = '';
						$camp_data['ga_source_name'] = '';
					}
					
					if( $camp_data['is_test_control_enabled'] == 'on' ) 
						$camp_data['is_test_control_enabled'] = 0;
					else 
						$camp_data['is_test_control_enabled'] = 1;
					
					$params = array(
							'name' => $camp_data['campaign_name'],
							'start_minute' => $camp_data['timeline_start_minute'],
							'end_minute' => $camp_data['timeline_end_minute'],
							'desc' => addslashes( $camp_data['campaign_desc'] ),
							'is_ga_enabled' => $camp_data['is_ga_enabled'],
							'ga_name' => $camp_data['ga_name'],
							'ga_source_name' => $camp_data['ga_source_name'],
							'is_test_control_enabled' => $camp_data['is_test_control_enabled'],
							'campaign_roi_type_id' => $camp_data['roi_report_type']
							);
					$campaign_id = $this->C_lifecyle_controller->createLifeCycleCampaign( $params );
					$metadata_mappings = array("org_id" => $this->org_id,
											   "campaign_id" => $campaign_id,
											   "campaign_objective" => $camp_data['campaign_objective']);
					$this->C_metadata_controller->addObjMappings($metadata_mappings);
					$this->logger->debug( "@@End of Lifecylce Campaign creation" );
					$this->data['istatus'] = 'success';
					$this->data['campaign_id'] = $campaign_id;
				}

				$C_transaction_manager->commitTransaction();

			}catch( Exception $e ){

				//roll back transaction
				$C_transaction_manager->rollbackTransaction();
				$this->logger->error( "ROLLING BACK : Exception Was Thrown While creating campaign : ".$e->getMessage() );
				$this->data['error_msg'] = $e->getMessage();
				$this->data['istatus'] = 'error';
			}
		}
	}

	/**
	 * update Campaign details.
	 */
	private function updateCampaignDetails(){

		$this->logger->debug('@@@REQUEST'.print_r( $_REQUEST , true ) );

		$campaign_id = $_REQUEST['campaign_id'];
		$campaign_name = $_REQUEST['campaign_name'];
		$ga_name = $_REQUEST['ga_name'];
		$ga_source_name = $_REQUEST['ga_source_name'];
		
		$start_date = date( "Y-m-d H:i:s" ,strtotime( $_REQUEST['u_starting_date'] ) );
		$end_date = date( "Y-m-d" ,strtotime( $_REQUEST['u_end_date'] ) ).' 23:59:59';

		//If there is atleast 1 authorised message for this campaign
		if( $this->C_outbound_controller->checkAuthorisedMessageByType($campaign_id,"campaign") ){
			$this->data['info'] = _campaign("Campaign editing is not permissible once at least one message authorisation has been done") ;	
			return;
		}

		$metadata_mappings = array("org_id" => $this->org_id,
								   "campaign_id" => $campaign_id,
								   "campaign_objective" => trim($_REQUEST['campaign_objective']));
		$this->logger->debug("going to update obj map");
		$this->C_metadata_controller->updateObjMappings($metadata_mappings);

		$this->campaign_model->load( $campaign_id );
		$campaign_type = $this->campaign_model->getType();
		$old_end_date = $this->campaign_model->getEndDate();
		$this->logger->debug('@@@Campaign Name is Updating');

		if( $this->campaign_model->getOrgId() != $this->org_id ){
			$this->data['info'] = ORG_CHANGED_MESSAGE;
			return;
		}

		$params['end_date'] = $end_date;
		$params['start_date'] = $start_date;
		$params['ga_name'] = trim( $ga_name );
		$params['name'] = trim($campaign_name);
		$params['ga_source_name'] = trim( $ga_source_name );
		$params['desc'] = addslashes( $this->campaign_model->getDescription() );

		$status = $this->C_outbound_controller->update( $params, $campaign_id );

		$this->data['info'] = $status;

		//Updating Task dates when campaign dates will be updated
		if( $status == "SUCCESS" ){

			$this->C_outbound_controller->updateCouponSeriesExpiryDateByCampaignId(
					$campaign_id );
			
			if( $old_end_date != $end_date ){
				$time_stamp = strtotime( $end_date ) * 1000;
				$status = $this->C_outbound_controller->createCampaignReportSchedule(
																	$campaign_id );
				$res = $this->C_outbound_controller->updateCampaignReportSchedule(
										$this->org_id, $campaign_id, $time_stamp );
				$this->logger->debug('restart schedule rslt: '.print_r($status, true));
			}
			
			include_once 'tasks/impl/CallTask.php';
			$C_task = new CallTask();
			$C_task->updateDates( $campaign_id , $params );
		}

		if( $status == "SUCCESS" && $campaign_type == 'action' ){

			$this->C_bounceback_controller->updateRuleSetDates( $campaign_id, $start_date, $end_date);
			$this->C_bounceback_controller->reconfigureOrganization();
		}

		//Returning updated value.
		$this->data['campaign_name'] = $campaign_name;
		$this->data['start_date'] = date('M d Y',strtotime( $start_date ));
		$this->data['end_date'] = date('M d Y',strtotime( $end_date ));
		$this->data['ga_name'] = $ga_name;
		$this->data['ga_source_name'] = $ga_source_name;
		$this->data['end_date'] = date('M d Y',strtotime( $end_date ));

		$this->logger->info('$$$DATA'.print_r($this->data,true));
	}

	private function allocatePoints($post_params){
		include_once "model_extension/campaigns/class.IncentiveModelExtension.php" ;
		include_once "base_model/campaigns/class.IncentiveBase.php" ;

		$incentiveModel = new IncentiveModelExtension() ;

		$incentiveModel->setAllocationStrategyId($post_params["points_data"]["alloc_strategy_id"]) ;
		$incentiveModel->setExpiryStrategyId($post_params["points_data"]["exit_strategy_id"]) ;
		$incentiveModel->setProgramId($post_params["points_data"]["program_id"]);
		$incentiveModel->setCampaignId($post_params["points_data"]["campaign_id"]);
		$incentiveModel->setIncentiveTypeId( $incentiveModel->getIncentiveTypeId('POINTS') ) ;
		$ret_id = $incentiveModel->insert() ;

		return $ret_id;
	}

	private function createNewCouponSeries(){

		$method = $_SERVER['REQUEST_METHOD'];
		if( $method == 'GET' ){

			$campaign_id = $_REQUEST['campaign_id'];
			$message_id = $_REQUEST['message_id'];
			$currency = $this->C_org_controller->getBaseCurrencyForOrg();
			$this->logger->debug('base currency: '.$currency);
			$details = $this->C_outbound_controller->getVoucherSeriesDetailsForCoupon( $campaign_id );
			$details["points_info"] = $this->C_outbound_controller->getPointsDetails( $campaign_id ,$message_id);
			$this->data["camp_details"] = $details;

			$this->data['c_org_id'] = $this->org_id;

			$vs_id = $details['voucher_series_id'];
			if( $vs_id != -1 ){
				$content = $this->C_outbound_controller->setCouponDetailsHtml($vs_id);
				$this->data['c_title'] = rawurlencode( $content['title'] );
				$this->data['c_body'] = rawurlencode( $content['body'] );
			}
			$currency ? $currency : 'NA';
			$this->data['currency'] = $currency;
			$this->data['istatus'] = 'success';

		}else if( $method == 'PUT' ){

			$post_params = json_decode(file_get_contents('php://input'), true);
			
			if(array_key_exists("points_data", $post_params)){
				$campaign_id = $post_params["points_data"]["campaign_id"];
				$this->logger->debug("camp_id_createNewCouponSeries : " . $campaign_id);
				$post_params = $this->sanitizeRequest( $post_params );

				$points_properties_id = $this->allocatePoints($post_params) ;
				$this->logger->debug("points_prop_id new " . $points_properties_id);
				$this->logger->debug("params for points are : ".print_r($post_params,true)) ;
				if($points_properties_id){
					$this->data['istatus'] = 'success';
					$this->data['inc_mapping_id'] = $points_properties_id;

					$this->C_campaign_controller->campaign_model->load( $campaign_id );
					$this->C_campaign_controller->campaign_model->setPointsPropertiesId( $points_properties_id );
					$this->C_campaign_controller->campaign_model->update( $campaign_id );	
				}
				else{
					$this->data['istatus'] = 'error';
					$this->data['msg'] = _campaign("Could not save points");
				}
			}
			else{
				$campaign_id = $_REQUEST['campaign_id'];
				$form_data = $post_params['coupon_data'];
				$camp_data = array();
				parse_str($form_data, $params);

				$params = $this->sanitizeRequest( $params );
				$this->logger->debug('coupon post params :'.print_r($params, true));
				try{
					if( $params['coupon_org_id'] != $this->org_id ){
						throw new Exception(ORG_CHANGED_MESSAGE);
					}

					if( $tparams['discount_value'] < 0 )
						throw new Exception( _campaign("Discount value must be positive integer") );

					if( $this->params['discount_value'] > 100 && $this->params['discount_type'] == 'PERC' )
						throw new Exception( _campaign("Discount percentage must be less than 100") );

					$params['do_not_resend_existing_voucher'] = true;
					$params['sync_to_client'] = false;
					$params['valid_with_discounted_item'] = false;
					
					$params = $this->C_campaign_controller->getCouponSeriesExpiryParams( $campaign_id, $params );
					$params['info'] = preg_replace('/\s+/', ' ',$params['info']);
					$params['campaign_id'] = $campaign_id;

					$this->logger->debug("coupon params save : ".print_r( $params , true ) );
					$status = $this->C_coupon_series->prepareAndCreate( $params );
					$series_id = $this->C_coupon_series->C_voucher_series_model_extension->getId();
					$redeem_at_store = $this->C_coupon_series->C_voucher_series_model_extension->getRedeemAtStore();
										
					if( $status != 'SUCCESS' )
						throw new Exception( $status );

					$this->C_campaign_controller->campaign_model->load( $campaign_id );
					$this->C_campaign_controller->campaign_model->setVoucherSeriesId( $series_id );
					$this->C_campaign_controller->campaign_model->update( $campaign_id );
					$this->data['coupon_series_id'] = $series_id;
					
					//tag coupon series
					$this->C_campaign_controller->tagCouponSeries( $series_id , json_decode( $redeem_at_store , true ) );

					$this->data['istatus'] = 'success';

				}catch( Exception $e ){

					$this->data['istatus'] = 'error';
					$this->data['error_msg'] = $e->getMessage();
				}
			}
			
		}
	}

	/**
	 * Its used to show the details of message while authorization
	 */
	private function getMessageDetailsForAuthorization(){

		try {
			include_once 'business_controller/NSAdminController.php';
			
			$nsadmin_controller = new NSAdminController();
			
			$this->logger->debug( "@@Authorize Message Details Start: ".print_r( $_REQUEST , true ) );
			$msg_id = $_REQUEST["message_id"];
			$messages = $this->C_outbound_controller->getMessageDetailsForAuthorization( $msg_id );
			$this->logger->debug( "@@Authorize Message Details Start: ".print_r( $_REQUEST , true ) );
			$details = $this->C_outbound_controller->getCheckListDetails( $msg_id );
			$details["messages"] = $messages;
			if ($msg_id) {
				$message_details = $this->C_campaign_controller->getDefaultValuesbyMessageId ( $msg_id );
				if($details['type'] == "email") {
					$message_default_args = $this->C_campaign_controller->getMessageDefaultArguments( $msg_id );
					$domain_gateway_config = $nsadmin_controller->formSelectedDomGatewayConfig($message_default_args);
					$details['domain_gateway_config'] = $domain_gateway_config;
				}
				$this->logger->debug('message-details'.print_r($message_details['group_id'],true));
				$group_id_param = $message_details['group_id'];
				$campaign_id = $message_details['campaign_id'];
			}
			$channel = ALL;
			$group_info = $this->C_outbound_controller->getReachableCustomer($campaign_id,$channel,$group_id_param);
			$details["item_data"] = $group_info[$group_id_param];
			$this->logger->debug( "@@Authorize Messages: ".print_r( $details , true ) );
			$this->data["auth_details"] = $details;
			$this->logger->debug( "@@Authorize Message Details End" );
		} catch (Exception $e) {
			
			$this->data["error_msg"] = $e->getMessage();
			$this->logger->debug( "@@Exception in authorize message detail" .$e->getMessage() );
		}
	}

	/**
	 * It is used to approve the outbound message
	 */
	private function approveOutboundMessage(){
		try{
			include_once 'services/app_features/FeatureValidatorFactory.php' ;
			include_once 'services/app_features/FeatureTag.php' ;

			$this->logger->debug( " @@Approve Message Start: ".print_r( $_REQUEST , true ) );
			$message_id = $_REQUEST["message_id"];

			$validator = FeatureValidatorFactory::getValidator(FeatureTag::CAMPAIGN_MSG_AUTH) ;
			$validator->load(array("message_id"=>$message_id)) ;
			if( !$validator->isPermissible() ){
				$this->data['approve_error'] = _campaign("You don't have sufficient permission to perform this action") ;
				return ;
			}
			
			$C_message = $this->C_outbound_controller->getBulkMessageDetails( $message_id );
			$org_id = $C_message->getOrgId();
			$message = $C_message->getMessage();

			$this->logger->debug( " @@Approve Message org_id: $org_id " );
			
			$time_now = date( "Y-m-d H:i:s" );
			$scheduled_type = $C_message->getScheduledType();
			
			if( $org_id == null )
				$this->data["approve_org_error"] = _campaign("Message selected doesn't belong to this organization");
			elseif( $scheduled_type == "PARTICULAR_DATE" && $C_message->getScheduledOn() < $time_now ) {
				
				$this->logger->debug( "Message scheduled time" .$C_message->getScheduledOn() .
						"Time now " . $time_now );
				$this->data["approve_org_error"] = _campaign("You can't authorize message since message scheduled time is expired.");
			} else {
				$status = $this->C_outbound_controller->approveMessage( $message_id );
				$this->data["approve_info"] = $status;
			}
		}catch(Exception $e){
			$this->logger->debug( "@@Approve Message Error: ".$e->getMessage() );
			$this->data["approve_error"] = $e->getMessage();
		}
	}

	/**
	 * It is used to reject the outbound message
	 */
	private function rejectOutboundMessage(){

		try{
			$this->logger->debug( "@@Reject Message Start: ".print_r( $_REQUEST , true ) );
			$message_id = $_REQUEST["message_id"];
			$status = $this->C_outbound_controller->rejectMessage( $message_id );
			$this->data["reject_info"] = $status;
		}catch(Exception $e){
			$this->logger->debug( "@@Reject Message Error: ".$e->getMessage() );
			$this->data["reject_error"] = $e->getMessage();
		}
	}

	private function getOutboundQueuedMessages(){

		$this->logger->debug("@@Campaign Queued Messages");

		$campaign_id = $_GET["campaign_id"];
		$msgs = array();
		$this->C_outbound_controller->getMessagesOverview( $campaign_id , $msgs );
		$this->data["camp_messages"] = $msgs;
	}

	private function editCouponDetails(){

		global $js, $prefix, $css_version, $js_version;
		$method = $_SERVER['REQUEST_METHOD'];

		if( $method == 'GET' ){

			$campaign_id = rawurldecode( $_REQUEST['campaign_id'] );
			$vs_id = rawurldecode( $_REQUEST['voucher_id'] );
			$this->logger->debug( 'edit coupon details: '.$campaign_id );
			
			$this->data['status'] = array("isSuccess" => true , "message" => "") ;
			
			//if there is atleast 1 authorised message containing {{voucher}} tag in message
			if( $this->C_outbound_controller->checkAuthorisedMessageByType($campaign_id , "coupon") ){
				$this->data['status']['isSuccess'] =  false ;
				$this->data['status']['message'] = _campaign("Coupon editing is not permissible once at least one coupon based message authorisation has been done") ;
				return ;
			}

			$C_outbound_features_provider =
				ApplicationFactory::getApplicationByCode( ApplicationType::CAMPAIGN );

			$features = $C_outbound_features_provider->getData(
					CampaignServiceFeatureTypeImpl::$OUTBOUND_CAMPAIGN_FEATURES );

			if( $features['outbound_coupon'] == 'advanced' ){

				$coupon_form = WidgetFactory::getWidget( 'campaign::v2::coupons::AdvancedCreateWidget' );
				$coupon_form->init();
				$coupon_form->initArgs( 'advanced', $campaign_id, $vs_id, false );
				$coupon_form->process();
			}else{

				$coupon_form = WidgetFactory::getWidget( 'campaign::v2::coupons::BasicCouponCreateWidget' , true );
				$coupon_form->init();
				$coupon_form->initArgs( 'quick', $campaign_id, false );
				$coupon_form->process();
			}

			$scripts = $js->getOnLoadValues();
			$html = "<script type='text/javascript'>$scripts</script>
				  <script type='text/javascript' src='/js/on_off_button.js'></script>
                  <script type='text/javascript' src='/js/campaign/campaign_handling_v2.js'></script>";

			$html .= $coupon_form->render( true );

			$this->data['edit_coupon_html'] = rawurlencode( $html );
			$this->logger->debug( '@@@End Add Or Update Coupon' );
		}
	}

	/**
	 * get Recipient list for particular campaign.
	 */
	private function getRecipient(){

		$campaign_id = $_GET['campaign_id'];
		$is_favourite = $_GET['is_favourite'];

		$this->logger->debug('@@@Inside GetRecipient Params:'.print_r( $_GET ,  true ));

		$audience_data = $this->C_campaign_controller->
							getGroupDetailsForCampaignId( $campaign_id , $is_favourite );

		$this->logger->debug('@@@Inside audience Params:'.print_r($audience_data,true));

		$control_groups = $this->C_campaign_controller->
								getControlGroupsByCampaignID( $campaign_id , $is_favourite );

		$this->logger->debug('@@@Inside control Params:'.print_r($control_groups,true));
		//If there is no audience data then return

		if( !$audience_data ){
				
			$this->data['recipient_list'] = array();
			$this->data['is_favourite'] = ( $is_favourite ) ? $is_favourite : false;
				
			$conquest_enabled = $this->C_conquest_data_service->isConquestEnabled( $this->org_id );
			$this->data['is_conquest'] = $conquest_enabled;
			return;
		}
		
		$group_ids = array();
		$audience_group_ids = array();
		$sticky_array = array();
		//$this->logger->debug('@@@audience_data:   '.print_r($audience_data,true));
		foreach ( $audience_data as $row ){
			array_push( $group_ids, $row['params']);
			array_push( $audience_group_ids , $row['id'] );
		}
		$this->logger->debug('@@@audience_group_ids:  '.print_r($audience_group_ids,true));
		$this->logger->debug('@@@group_ids:  '.print_r($group_ids,true));
		$audience_filter_details =
			$this->C_campaign_controller->getFilterDataByAudienceId( $audience_group_ids );
		$this->logger->debug('@@@audience_filter_details:'.print_r($audience_filter_details,true));
		$filter_details = array();
		foreach ( $audience_filter_details as $audience_filter_detail ){
			
			if( !is_array( $filter_details[$audience_filter_detail['audience_group_id']]) ){
				$filter_details[$audience_filter_detail['audience_group_id']] = array();
			}
			array_push( $filter_details[$audience_filter_detail['audience_group_id']], $audience_filter_detail);
		}
		
		$all_group_details = $this->C_campaign_controller->getGroupDetailsbyGroupIds( $group_ids );
		$this->logger->debug('@@@ALL Group Details :'.print_r( $all_group_details, true ));
		$this->logger->debug('@@@ALL filter Details :'.print_r( $filter_details, true ));
		
		$all_groups = array();
		foreach ( $all_group_details as $row ){
			$all_groups[$row['group_id']] = $row;
		}

		foreach( $audience_data as $row ) {
			$group_id = $row['params'];
			$group_details = $all_groups[$group_id];
			
			if( $group_id ){
				if( !isset( $group_details['group_id'] ) ) continue;
				$auto_generated_group = 'auto_gen_expiry_reminder_group_';
				if(strncmp($group_details['group_label'],$auto_generated_group,strlen($auto_generated_group))===0)
					continue;
				
				$peer_group_id = $group_details['peer_group_id'];
				if( $peer_group_id )
					$control_group_details = $control_groups[ $peer_group_id ];
				
				$params_number = json_decode( $group_details['params'], true );

				if( $peer_group_id ){

					$count = $group_details['total_clients'];
					$test_users = $group_details['customer_count'];
					$control_users = (int) $control_group_details['customer_count'];
					//If Both the test and controll user are zero then total client must be zero.
					if( $test_users == 0 && $control_users == 0 )
						$count = 0;

					$sticky_array[$group_id]['count'] = $count;
					$sticky_array[$group_id]['test'] = $test_users;
					$sticky_array[$group_id]['control'] = $control_users;
					$sticky_array[$group_id]['type'] = $group_details['type'];
				}else{
					
					$count = $group_details['customer_count'];
					$sticky_array[$group_id]['count'] = $count;
					$sticky_array[$group_id]['type'] = $group_details['type'];
				}

				$sticky_array[$group_id] =
					$this->getSelectionFilterDetails($filter_details, $sticky_array[$group_id], $row['id'] );
				$sticky_array[$group_id]['campaign_id'] = $campaign_id;
				$sticky_array[$group_id]['group_label'] = $group_details['group_label'];
				$sticky_array[$group_id]['email'] = $params_number['email'] ? $params_number['email'] : 0;
				$sticky_array[$group_id]['mobile'] = $params_number['mobile'] ? $params_number['mobile'] : 0;
				$sticky_array[$group_id]['is_favourite'] = $group_details['is_favourite'];
				$sticky_array[$group_id]['id'] = $group_id;

				$sticky_array[$group_id]['type'] = $group_details['type'];
			}
		}
		$this->logger->debug('@@@Group List:-'.print_r( $sticky_array , true ));
		$this->data['recipient_list'] = $sticky_array;
		$this->data['is_favourite'] = ( $is_favourite ) ? $is_favourite : false;
		
		$conquest_enabled = $this->C_conquest_data_service->isConquestEnabled( $this->org_id );
		$this->data['is_conquest'] = $conquest_enabled;
	}

	private function getRecipientv2(){

		$campaign_id = $_GET['campaign_id'];
		$channel = $_GET['channel_type'];
		//$is_favourite = $_GET['is_favourite'];
		$channel = 'ALL';

		$this->logger->debug('@@@Inside GetRecipientv2 Params:'.print_r( $_GET ,  true ));

		$reach_response_mode = $this->C_outbound_controller->getReachableCustomer($campaign_id,$channel);

		//$this->logger->debug('@@@Group List:-'.print_r( $reach_response_mode , true ));

		if( !$reach_response_mode){
			$this->data['recipient_list'] = array();
		}
		else{
			$this->data['recipient_list'] = $reach_response_mode;
		}
		
		//$this->data['is_favourite'] = ( $is_favourite ) ? $is_favourite : false;
		
		$conquest_enabled = $this->C_conquest_data_service->isConquestEnabled( $this->org_id );
		$this->data['is_conquest'] = $conquest_enabled;	
	}

	private function getSelectionFilterDetails( $filter_details, $group_details, $audience_group_id ){
		
		$group_details['name'] = array( );
		if( strtolower( $group_details['type'] ) == 'loyalty' || strtolower( $group_details['type'] ) == 'non_loyalty'  ){

			if( count( $filter_details[$audience_group_id] ) < 1 ){
				
				array_push( $group_details['name'], _campaign("All registered customers") );
			}
			
			foreach( $filter_details[$audience_group_id] as $filter_detail ){
				
				array_push( $group_details['name'], $filter_detail['filter_explaination']." ( ".$filter_detail['no_of_customers']." ) " );
			}
		
		}else{
		
			if( $filter_details[$audience_group_id][0]['filter_type'] == 'test_control' ){
		
				$name = Util::templateReplace( $filter_details[$audience_group_id][0]['filter_explaination'],
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
	
	private function updateCouponDetails(){

		$this->logger->debug( 'Start Update Advance Coupon' .print_r( $_REQUEST ,true));

		$advanced_form = WidgetFactory::getWidget( 'campaign::v2::coupons::AdvancedCreateWidget' );
		$advanced_form->init();
		$advanced_form->initArgs( 'advanced', $_REQUEST['campaign_id'], $_REQUEST['voucher_id'], false );
		$advanced_form->process();

		$this->logger->debug( 'End Update Advance Coupon' );
	}

	private function validateStores()
	{

			$storename_string = base64_decode(rawurldecode($_POST['file']));
			$storename_string = trim($storename_string);
			$storenames = !empty($storename_string) ? explode(",",$storename_string) : array() ;
			$this->logger->debug("inside validate stores storename info:".print_r( $storenames, true ));
			$stores =$this->C_org_controller->StoreTillController->getAll();
			$storeinfo = array();	
			$storeinfo_flipped = array();
			if( !empty($stores) ){
				foreach( $stores as $res )
				{
					$storeinfo[$res['name']] = $res['id'];
				}
			}

			$count_store=count($storenames);
			$this->logger->debug("inside validate stores ".$count_store);	
			$this->logger->debug("list of stores ".print_r($storeinfo, true));	

			$store = array();
			$invalid_stores = array();
			$cnt=0;
			
			if(  $count_store > 0 ) {
				for($i=0;$i<count($storenames);$i++){
					if(array_key_exists(trim($storenames[$i]), $storeinfo)){
						array_push($store, trim($storenames[$i]));
					}else{
						array_push($invalid_stores,trim($storenames[$i]));
						$cnt++;
					}
				}

			}
			$store = array_unique($store);
			$this->logger->debug("list of valid stores ".print_r($store, true));

			$invalid_stores = array_unique($invalid_stores);
			$this->logger->debug("inside validate invalid stores array: ".print_r($invalid_stores,true));		
			$this->data['invalid_count']=$cnt;
			$this->data['invalid_stores']=implode("," , $invalid_stores);
			$this->data['valid_stores']=$store;
			$this->logger->debug("inside data array: ".print_r($this->data,true));	

	}

	private function getCampaignDetails(){

		$campaign_id = $_GET["campaign_id"];
		$this->logger->debug("@@Campaign Details Start :".$campaign_id);
		$this->C_outbound_controller->load( $campaign_id );
		$this->data["c_details"] =
			$this->C_outbound_controller->campaign_model_extension->getHash();
		$this->logger->debug("@@Campaign Details End");
	}

	private function getCampaignList( $params ){

		$this->logger->debug( "duplicate list campaigns data" );

		if( $params[2] == 12){
			$params[2] = 1;
			$params[3] += 1;
		}else
			$params[2] += 1;

		$from = $params[1].'-'.$params[0].'-01';
		$to = $params[3].'-'.$params[2].'-01';
		$where = $this->C_outbound_controller->getDateFilter($from, $to);
		$limit = '';

		$search = $this->C_outbound_controller->getWhereClauseFilter( $params[4] );

		$list = $this->C_outbound_controller->createListForDuplication( $type,
				$where, $search, $limit );

		$this->data['listitem'] = $list;
	}

	private function duplicateCustomerList( $params ){

		$this->logger->debug('duplicate_params'.$params);
		$list_type = $params[3];
		if( $list_type == 'uploads' )
			$status = $this->C_outbound_controller->duplicateUploadedList( $params );
		else if( $list_type == 'filters' ){
			$ids = $this->C_outbound_controller->duplicateFilterList( $params );
			if( is_array( $ids ) ){
				$this->data['new_groups'] = json_encode( $ids, true );
				$status = 'success';
			}else{
				$status = $ids;
			}
		}
		$this->logger->debug('duplicate list result: '.print_r($status, true));
		
		if( $status == 'success' ){
			$this->data['status'] = 'success';
			$this->data['info'] = _campaign("Duplicate list created");
		}else{
			$this->data['info'] = $status;
			$this->data['status'] = 'failure';
		}
	}
	
	private function groupDetails($params){
    	$method = $_SERVER['REQUEST_METHOD'];
        $this->logger->debug("method" .$method);
                /**$group = json_decode(file_get_contents('php://input'), true);
                $this->logger->debug( '@@@ groups' .print_r( $group ,true));
                $this->logger->debug( '@@@ groups' .print_r( rawurldecode($group["groupdata"]) ,true));
                $tags=explode("&", rawurldecode($group["groupdata"]));
                foreach ($tags as $key => $value) {
                        $tagData=explode("=", $value);
                        $group_tags[$tagData[0]]=$tagData[1];
                }
                $this->logger->debug( '@@@ Group tags' .print_r( $params[0] ,true));*/
        $this->logger->debug( '@@@ Group tags' .print_r( $_POST ,true));
        $this->logger->debug( '@@@ Group tags' .print_r( $_GET ,true));
        if($method == "POST"){
        	$this->C_GroupDetailModel->load($_GET['id']);
            $gp_tags = $_POST;
            foreach( $gp_tags as $key => $value ) 
            	$group_tags[$key] = $value;
             
            $this->logger->debug( 'Group tags -->' .print_r( $group_tags ,true));
            $this->C_GroupDetailModel->setGroupTags($group_tags);
            $bool=$this->C_GroupDetailModel->update($_GET['id']);
            $this->data['listitem'] = $bool;
        }
        if($method == "GET"){
        	$this->C_GroupDetailModel->load($params[0]);
            $data=$this->C_GroupDetailModel->getGroupTags();
            ksort( $data );
            $this->data['listitem'] = $data;
        }
    }

	private function refreshDuplicateList( $params ){
		
		$status = $this->C_outbound_controller->refreshDuplicateList( $params );
		$this->logger->debug('resfresh list result: '.$status );
		
		if( $status == 'success' )
			$this->data['status'] = 'success';
		else {
			$this->data['status'] = 'failed';
			$this->data['error_msg'] = $status;
		}
	}
	
	//------campaign automated report related functions-----
	private function retryReportSchedule( $params ){

		$campaign_id = $_REQUEST['campaign_id'];
		$this->logger->debug('retry report schedule: '.$campaign_id ); 
		$res = $this->C_outbound_controller->createCampaignReportSchedule( $campaign_id );
		$this->logger->debug('retry report schedule result: '.$res );
		
		if( $res == 'success' ){
			$this->data['status'] = 'success';
			$this->data['info'] = _campaign("Report successfully scheduled");
		}else{
			$this->data['status'] = 'failed';
			$this->data['info'] = $res;
		}
	}
		
	private function disableScheduledReport( $params ){
	
		$org_id = $this->current_org->org_id;
		$campaign_id = $_REQUEST['campaign_id'];
		$this->logger->debug('disable report schedule: '.$campaign_id );
		$res = $this->C_outbound_controller->inactivateCampaignReportSchedule(
														$campaign_id, $org_id );
		if( $res === false ){
			$this->data['status'] = 'failed';
			$this->data['info'] = _campaign("Error while disabling report");
		}
	}	

	private function getSurveyFormHtml(){

		$form_id = $_GET['form_id'];
		$campaign_id = $_GET['campaign_id'];
		$this->logger->debug( '@@@Survey Form Id:'.print_r( $_GET , true ) );

		$details = $this->C_campaign_controller->getSurveyFormsByOutboundCampaignId( 
			$campaign_id , $form_id );

		$this->logger->debug( '@@@Survey Form Details:'.print_r( $details , true ) );

		if( empty( $details ) ){
			$this->data['info'] = "";
			return;
		}

		$downloadFsUrl = json_decode( $details["fs_link"] , true );
		$content = file_get_contents( $downloadFsUrl["main"] );

		$survey_logo = "";
		if( $details["brand_logo"] ){
			$survey_logo = '<img width="130px" height="40px" 
					  			 class="csat-brand-logo" 
					  			 border="0" src="'.$details["brand_logo"].'">';	
		}
		
		$options = array( "csat-brand-logo" => $survey_logo );
		$content = Util::templateReplace( $content , $options );

		// Create a DOM object
		$html = new simple_html_dom();
		// Load HTML from a survey form html string
		$html->load( $content );

		foreach($html->find('form') as $element){
			$element->action = $details["content"];
			$element->target = "_blank";
			$element->onsubmit = "return window.confirm("._campaign("You are submitting information to an external page.")."'\n"._campaign("Are you sure?")."');";
		}

		foreach ($html->find('.csat-live-error') as $node){
        	$node->outertext = '';
    	}

    	$content = $html->save();

		$this->data['info'] = htmlspecialchars_decode( stripcslashes( $content ));
	}

	private function downloadEmailReports(){

		$params = $_REQUEST;
		$this->logger->debug('start of downloading report params: '.print_r($params, true));
		
		include_once 'business_controller/campaigns/DownloadCampaignReportsHandler.php';
		$C_report_handler = new DownloadCampaignReportsHandler();
		$res = $C_report_handler->downloadEmailReports( $params );
		
		if( $res ){
			$this->data['status'] = 'success';
			$this->data['msg'] = _campaign("The report is successfully queued.The notification will be sent to your email id once done");
		}else{
			$this->data['status'] = 'fail';
			$this->data['err_msg'] = _campaign("Error occurred while queuing report");
		}
	}
	
	private function checkCampaignExistForOrg() {
		
		$this->logger->debug( '@@@FLR campaign id passed is ' .$_GET['campaign_id'] );
		$campaign_id = $_GET['campaign_id'];
		
		$this->C_campaign_controller->load( $campaign_id );
		$details = $this->C_campaign_controller->getDetails( );
		
		$this->logger->debug( "@@@FLR $this->org_id camp exist  " . print_r( $details, true ) );
		
		if( $details['org_id'] == $this->org_id ) {
			
			$this->data['success'] = "SUCCESS";
		} else {
			
			$this->data['info'] = _campaign("Selected campaign doesn't belong to this organization.");
			$this->data['success'] = "FAILURE";
		}
	}

	private function downloadSampleCSVFile()
	{
		$import_type = $_GET['user_type'];
		$num_custom_tags = $_GET['num_custom_tags'];
		if($import_type == 'mobile'){
			$data = array(array('mobile','name'),array('9012345678','John KC'),array('9012345679','Doe Young'),array( '9012345670','Sarah Karm'));
			$filename = 'SampleMobile';	
		}
		elseif($import_type == 'email'){
			$data = array(array('email','name'),array('john@capillary.com','John KC'),array('doe@capillary.com','Doe Young'),array( 'sarah@capillary.com','Sarah Karm'));
			$filename = 'SampleEmail';
		}
		elseif($import_type == 'userid'){
			$data = array(array('userid','name'),array('1','John KC'),array('2','Doe Young'),array( '3','Sarah Karm'));
			$filename = 'SampleUserid';
		}
		elseif($import_type == 'externalid'){
			$data = array(array('externalid','name'),array('ABCDS112323','John KC'),array('VHSHDJD9992','Doe Young'),array( 'VBHJJKK9983','Sarah Karm'));
			$filename = 'SampleExternalid';
		}
		
		if($num_custom_tags>0)
		{
			for($j=0;$j<$num_custom_tags;$j++){
				$data[0][] = "custom_tag_".($j+1);
			}
			for($i=1;$i<count($data);$i++){
				for($j=0;$j<$num_custom_tags;$j++){
						$data[$i][] = "value".($j+1);
				}
			}
		}

		ob_end_clean();
		header("Content-type: text/csv");
		header("Cache-Control: no-store, no-cache");
		header('Content-Disposition: attachment; filename="'.$filename.'.csv"');
	
		$stream = fopen ('php://output', 'w');
	
		foreach ($data as $record)
		{
			fputs($stream, implode($record,',')."\n");
		}	

		fclose($stream);
		die();
	}
	
	private function getCouponSeriesDetails(){

		$campaign_id = $_REQUEST['campaign_id'];
		$message_id = $_REQUEST['message_id'];
		$currency = $this->C_org_controller->getBaseCurrencyForOrg();
		$this->logger->debug('base currency: '.$currency);
		$this->logger->debug("memory peak usage : ".memory_get_peak_usage()) ;	
		$this->logger->debug("memory peak real usage : ".memory_get_peak_usage(true)) ;
		$this->logger->debug("memory usage : ".memory_get_usage()) ;
		$this->logger->debug("memory real usage : ".memory_get_usage(true)) ;
		$this->logger->debug("before getVoucherSeriesDetailsForCoupon") ;
		$details = $this->C_outbound_controller->getVoucherSeriesDetailsForCoupon( $campaign_id );
		$this->logger->debug("memory peak usage : ".memory_get_peak_usage()) ;	
		$this->logger->debug("memory peak real usage : ".memory_get_peak_usage(true)) ;
		$this->logger->debug("memory usage : ".memory_get_usage()) ;
		$this->logger->debug("memory real usage : ".memory_get_usage(true)) ;
		$this->logger->debug("after getVoucherSeriesDetailsForCoupon") ;
		$details["points_info"] = $this->C_outbound_controller->getPointsDetails( $campaign_id ,$message_id);
		$this->logger->debug("memory peak usage : ".memory_get_peak_usage()) ;	
		$this->logger->debug("memory peak real usage : ".memory_get_peak_usage(true)) ;
		$this->logger->debug("memory usage : ".memory_get_usage()) ;
		$this->logger->debug("memory real usage : ".memory_get_usage(true)) ;
		$this->logger->debug("after getPointsDetails") ;
		$this->data["camp_details"] = $details;

		$this->data['c_org_id'] = $this->org_id;

		$vs_id = $details['voucher_series_id'];
		if( $vs_id != -1 ){
			$this->logger->debug("memory peak usage : ".memory_get_peak_usage()) ;	
			$this->logger->debug("memory peak real usage : ".memory_get_peak_usage(true)) ;
			$this->logger->debug("memory usage : ".memory_get_usage()) ;
			$this->logger->debug("memory real usage : ".memory_get_usage(true)) ;
			$this->logger->debug("before setCouponDetailsHtml") ;			
			$content = $this->C_outbound_controller->setCouponDetailsHtml($vs_id);
			$this->logger->debug("memory peak usage : ".memory_get_peak_usage()) ;	
			$this->logger->debug("memory peak real usage : ".memory_get_peak_usage(true)) ;
			$this->logger->debug("memory usage : ".memory_get_usage()) ;
			$this->logger->debug("memory real usage : ".memory_get_usage(true)) ;
			$this->logger->debug("after setCouponDetailsHtml") ;			
			$this->data['c_title'] = rawurlencode( $content['title'] );
			$this->data['c_body'] = rawurlencode( $content['body'] );
		}
		$currency ? $currency : 'NA';
		$this->data['currency'] = $currency;
		$this->data['istatus'] = 'success';

		$inc_elems = $this->C_metadata_controller->getIncentives();
		$previous_incentive = $this->C_metadata_controller->getIncentiveSelection($this->org_id, 
																		  		  $campaign_id,
																		  		  $message_id);
		$this->data['previous_incentive'] = json_encode( $previous_incentive, true );
		$this->data['inc_elems'] = json_encode( $inc_elems, true );
	}
	private function getBrands(){
		include_once 'helper/coupons/CouponProductManager.php';
		$voucher_series_id = $_GET['voucher_series_id'];
		$C_coupon_product_manager = new CouponProductManager();
		$items = $C_coupon_product_manager->getProductBrandValues('root',$voucher_series_id);
		$this->data["items"] = $items["items"];
	}
	
	private function getCategories(){
		include_once 'helper/coupons/CouponProductManager.php';
		$product_id = $_GET['product_id'];
		$C_coupon_product_manager = new CouponProductManager();
		$categories = $C_coupon_product_manager->getProductCategoryChildValues($product_id);
		$this->data["parent_id"] = $categories['parent_id'];
		$this->data["members"] = $categories['members'];
	}
	
	private function getSelectedCategories(){
		include_once 'helper/coupons/CouponProductManager.php';
		$voucher_series_id = $_GET['voucher_series_id'];
		$C_coupon_product_manager = new CouponProductManager();
		$selected = $C_coupon_product_manager->getSelectedProductCategoryHierarchy($voucher_series_id);
		$this->data["selected"] = $selected["selected"];
		$this->data["count"] = $selected["count"];
	}
	
	private function addCouponProductValue(){
		include_once 'helper/coupons/CouponProductManager.php';
		$voucher_series_id = $_GET['voucher_series_id'];
		$this->logger->debug("@@S_Coupon post added ".print_r($_POST,true));
		$params_encoded = $_POST['params'];
		$params = json_decode($params_encoded,true);
		$this->logger->debug("@@S_Coupon params added ".print_r($params,true));
		$coupon_brand_configs = $params["brand"];
		$coupon_category_configs = $params["category"];
		$this->logger->debug("@@S_Coupon brands added ".print_r($coupon_brand_configs,true));
		$this->logger->debug("@@S_Coupon category added ".print_r($coupon_category_configs,true));
		$C_coupon_product_manager = new CouponProductManager();
		$product_name_id_hash = $C_coupon_product_manager->getCouponProductNameIdHash(array('BRAND','CATEGORY','SKU'));
		$brand_coupon_product_id = $product_name_id_hash['BRAND'];
		$category_coupon_product_id = $product_name_id_hash['CATEGORY'];
		$sku_coupon_product_id = $product_name_id_hash['SKU'];
		$C_coupon_product_manager->inValidateExistingCouponProductValue( $voucher_series_id, $sku_coupon_product_id, $this->org_id );
		$C_coupon_product_manager->addCouponProductValues( $voucher_series_id, $brand_coupon_product_id, $coupon_brand_configs,true);
		$C_coupon_product_manager->addCouponProductValues( $voucher_series_id, $category_coupon_product_id, $coupon_category_configs );
		$validity = $C_coupon_product_manager->isProductSelected($voucher_series_id);
		$this->data["voucher_validity"] =  $validity;
	}

	private function getCouponProductDetails(){
		include_once 'helper/coupons/CouponProductManager.php';
		$vs_id = $_GET['voucher_series_id'];
		$C_coupon_product_manager = new CouponProductManager();
		$brand_result = $C_coupon_product_manager->getProductBrandValues('root',$vs_id);
		$brands = $brand_result["items"];
		$selected_brands = array();
		foreach($brands as $brand){
			if($brand["selected"])
				$selected_brands[] = $brand["name"];
		}
		$categories_result = $C_coupon_product_manager->getSelectedProductCategoryHierarchy($vs_id);
		$categories = $categories_result["selected"];
		$selected_categories = array();
		foreach($categories as $category){
			$cat = array();
			$cat["name"] = $category["category"]["name"];
			$cat["parents"] = array();
			foreach($category["parents"] as $p){
				$cat["parents"][] = $p["name"];
			}
			$selected_categories[] = $cat;
		}

		$selected_skus = $C_coupon_product_manager->getSelectedSkuProductValue($vs_id);
		$this->logger->debug("@@Coupon product value ".print_r(array("get_brand"=>$brands,"get_category"=>$categories), true));
		$this->logger->debug("@@Coupon product value ".print_r(array("brand"=>$selected_brands,"category"=>$selected_categories, "sku"=>$selected_skus), true));
		$this->data["brand"] = $selected_brands;
		$this->data["category"] = $selected_categories;
		$this->data["sku"] = $selected_skus;
	}

	private function addCouponSKUProductValue(){
		include_once 'helper/coupons/CouponProductManager.php';
		$C_coupon_product_manager = new CouponProductManager();
		try{
			$limit = 20;
			$voucher_series_id = $_GET['voucher_series_id'];
			$this->logger->debug("@@S_Coupon post added ".print_r($_POST,true));
			$sku = $_POST["sku_values"];
			$this->logger->debug("@@S_Coupon raw sku".$sku);
			
			$sku_values = array();
			if(strlen($sku)>0){
				$sku = str_replace(",", "\n", $sku);
				$sku_values = explode("\n", str_replace("\r", "", $sku));
			}
			$sku_values = array_map("trim", $sku_values);
			$sku_values = array_unique($sku_values);
			$this->logger->debug("@@S_Coupon refined sku values ".print_r($sku_values,true));
			if(count($sku_values)> $limit){
				throw new Exception("Size limit exceeded", 1);
			}
			$result = $C_coupon_product_manager->saveSkuProductValue($voucher_series_id, $sku_values );
		} catch (Exception $e){
			$this->data["error"] = $e->getMessage();
		}
		$validity = $C_coupon_product_manager->isProductSelected($voucher_series_id);
		$this->data["voucher_validity"] =  $validity;

	}

	private function getSelectedSku(){
		include_once 'helper/coupons/CouponProductManager.php';
		$voucher_series_id = $_GET['voucher_series_id'];
		$C_coupon_product_manager = new CouponProductManager();
		$selected_skus = $C_coupon_product_manager->getSelectedSkuProductValue($voucher_series_id);
		$this->logger->debug("@@Coupon product value ".print_r(array("sku"=>$selected_skus), true));
		$this->data["selected"] = $selected_skus;
		$this->data["count"] = count($selected_skus);
	}

	private function getSuperNodes($result){
		$parent_child_mapping = array() ;
		$superNodes = array() ;
		$this->logger->debug("the result passed is : ".print_r($result,true)) ;
		foreach ($result as $key => $value) {
			$parent_child_mapping[$value['child_entity_id']] = array() ;
			$parent_child_mapping[$value['child_entity_id']]['parent_id'] = $value['parent_entity_id'] ;
			$parent_child_mapping[$value['child_entity_id']]['parent_code'] = $value['code'] ;
		}
		$this->logger->debug("parent child mapping is : ".print_r($parent_child_mapping,true)) ;
		foreach ($parent_child_mapping as $key => $value) {
			$tempSuperNode = $value['parent_id'] ;
			$tempChildNode = $key ;
			while(true){				
				if(isset($parent_child_mapping[$tempSuperNode])){
					$tempChildNode = $tempSuperNode ;
					$tempSuperNode = $parent_child_mapping[$tempSuperNode]['parent_id'] ;
				}else{
					if(!isset( $superNodes[$tempSuperNode])){
						$superNodes[$tempSuperNode] = $parent_child_mapping[$tempChildNode]['parent_code'] ;
					}
					break ;
				}
			}
		}
		$this->logger->debug("the super nodes are : ".print_r($superNodes,true)) ;
		return $superNodes ;
	}

	private function getParentEntityHierarchy($parent_entity_type,$child_entity_type , $user_type , &$all_zones_concepts){
		$entityController = new EntityController($parent_entity_type) ;
		$result = $entityController->getChildrenEntitiesByType($parent_entity_type , $child_entity_type , $user_type) ;
		$superNodes = $this->getSuperNodes($result) ;
		$node = array() ;
		foreach ($result as $key => $value) {
			$node[$value['child_entity_id']] = $value ;
			array_push($all_zones_concepts,$value['child_entity_id'] ) ;
			if( !isset($node[$value['parent_entity_id']]) && isset($superNodes[$value['parent_entity_id']])){
				array_push($all_zones_concepts,$value['parent_entity_id'] ) ;
				$node[$value['parent_entity_id']] = new stdClass() ;
				$node[$value['parent_entity_id']]->child_code = $superNodes[$value['parent_entity_id']] ;
				$node[$value['parent_entity_id']]->child_entity_id = $value['parent_entity_id'] ;
				$node[$value['parent_entity_id']]->child_entity_type = $child_entity_type ;
				$node[$value['parent_entity_id']]->code = "" ;
				$node[$value['parent_entity_id']]->id = -1 ;
				$node[$value['parent_entity_id']]->parent_entity_id = -1 ;
				$node[$value['parent_entity_id']]->type = $parent_entity_type ;					
			}			
		}
		$this->logger->debug("node is : ".print_r($node,true)) ;
		return $node ;
	}

	private function getStoresRedeemInfo(){
		include_once 'business_controller/EntityController.php' ;
		include_once 'business_controller/AdminUserController.php';
		include_once 'helper/coupons/CouponSeriesManager.php' ;
		
		$adminUserController = new AdminUserController();
		$type = $adminUserController->getAdminUserType();
		$concepts = array()	;
		$zones = array() ;
		$all_zones_concepts = array() ;
		$this->logger->debug("the type of user is : ".$type) ;
		switch($type){
			case "ORG" :
				$zones = $this->getParentEntityHierarchy('ZONE','ZONE',$type , $all_zones_concepts) ;
				$concepts = $this->getParentEntityHierarchy('CONCEPT','CONCEPT',$type , $all_zones_concepts) ;
				break ;
			case "CONCEPT" :	
				$concepts = $this->getParentEntityHierarchy('CONCEPT','CONCEPT',$type, $all_zones_concepts) ;
				$zones = array() ;
				break ;
			case "ZONE"	:
				$zones = $this->getParentEntityHierarchy('ZONE','ZONE',$type, $all_zones_concepts) ;
				$concepts = array()	;
				break ;
			default	:
				$concepts = array()	;
				$zones = array() ;										 
		}
		$coupon_series_manager = new CouponSeriesManager() ;
		$coupon_series_manager->loadById($_GET['voucher_series_id']) ;
		$redeem_at_store = json_decode($coupon_series_manager->C_voucher_series_model_extension->getRedeemAtStore() , true) ;

		$entityController = new EntityController('TILL') ;
		$temp_tills = $entityController->getEntityDetails($redeem_at_store) ;
		$selected_tills = array() ;
		foreach($temp_tills as $value){
			$selected_tills[$value['id']] = array('id'=>$value['id'] , 'till_code'=>$value['code']) ;
		}

		$stores = $this->OrgController->ConceptController->
						getChildrensByTypeFromParent( $all_zones_concepts , 'STORE' ) ;

		$zone_concept_to_store_mapping = array() ;
		$zone_concept_ids = array()	;			 
		foreach($stores as $value){

			if(isset($zone_concept_to_store_mapping[$value['child_entity_id']])){
				$zone_concept_to_store_mapping[$value['child_entity_id']] .= ",".$value['parent_entity_id'] ;
			}else{
				$zone_concept_to_store_mapping[$value['child_entity_id']] = $value['parent_entity_id'] ;				
			}
		}

		$tills = $this->OrgController->
							StoreController->getChildrensByTypeFromParent( array_keys($zone_concept_to_store_mapping) , 'TILL' ) ;	

		$arr_tills = array() ;	

		foreach ($tills as $value) {
			if(isset($arr_tills[$value['child_entity_id']])){
				$arr_tills[$value['child_entity_id']]['parent_id'] .= ",".$zone_concept_to_store_mapping[$value['parent_entity_id']] ;
			}else{
				$arr_tills[$value['child_entity_id']] = array('id'=>$value['child_entity_id'] , 'parent_id'=>$zone_concept_to_store_mapping[$value['parent_entity_id']] , 'parent_entity_type'=>$value['parent_entity_type'] , 'till_code'=>$value['code']) ;
			}
		}							
		$this->data['zone_hierarchy'] = $zones ;
		$this->data['concept_hierarchy'] = $concepts ;
		$this->data['all_tills'] = $arr_tills ;
		$this->data['selected_stores'] = $selected_tills ;

		$this->logger->debug("zones are : ".print_r($zone,true)," concepts are : ".print_r($concept,true)) ;
	}

	private function cacheCoupon(){
		require_once 'helper/AsyncCouponCache.php' ;
		require_once 'helper/SecurityManager.php' ;
		global $currentuser ;
		$allowed_grp_name = "couponCacheGroup" ;
		$user_id = $currentuser->user_id ;
		$security_manager = new SecurityManager() ;
		$this->logger->debug("inside cache coupon function voucher series id : ".$_POST['voucher_series_id']) ;
		$group_ids = array() ;
		$group_ids = $security_manager->getExistingGroupsUsingUserId($user_id) ;
		$is_allowed = false ;
		
		foreach($group_ids as $grp_id=>$val){
			if(strcmp($allowed_grp_name,$val['group_name']) == 0){
				$this->logger->debug("the current user is allowed to cache coupons") ;
				$is_allowed = true ;
				break ;
			}
		}
		if(!empty($_POST['voucher_series_id']) && $is_allowed){
			$async_coupon = new AsyncCouponCache() ;
			$async_coupon->cacheCoupons($_POST['voucher_series_id']) ;
			$this->logger->debug("async is started") ;
		}else{
			$this->logger->debug("cache coupons not allowed") ;
		}
		$this->data["cache_coupon"] = array() ;
	}

}
?>
