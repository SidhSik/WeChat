<?php
include_once 'base_model/campaigns/class.BulkMessage.php';
include_once 'helper/scheduler/CampaignGroupCallBack.php';
include_once 'helper/scheduler/HealthDashboardCallBack.php';
include_once 'services/applications/impl/applications/ApplicationTypes.php';
include_once 'services/applications/impl/applications/ApplicationFactory.php';
include_once 'services/applications/impl/features/types/campaigns/CampaignServiceFeatureTypeImpl.php';
include_once 'base_model/class.OrgDetails.php';
include_once 'business_controller/health_dashboard/HealthDashboardController.php';
include_once 'ui/widget/base/WidgetFactory.php';
include_once 'business_controller/emf/BounceBackController.php';
include_once 'business_controller/campaigns/library/VenenoDataDetailsHandler.php';
include_once 'base_model/campaigns/class.CommunicationDetailsService.php';
include_once 'creative_assets/CreativeAssetsManager.php';
include_once 'creative_assets/model/class.Template.php';
/**
 * The campaign level ajax service support
 * 
 * @author nayan
 */
class CampaignAjaxService extends BaseAjaxService{
	
	private $OrgController;
	private $C_campaign_controller;
	private $campaign_model;
	private $client;
	private $emf_client;
	private $C_config_mgr;
	private $C_assets_manager;
	private $C_couponSeriesManager;
	private $C_bounceback_controller;
	private $C_veneno_bucket_handler;
		
	public function __construct( $type, $params = null ){
		
		global $url_version, $currentorg;
		
		parent::__construct( $type, $params );
		
		$url_version = '1.0.0.1';
		
		//To load the cheetah's organizational model extension
		$org_id = $currentorg->org_id;
		$currentorg = new OrganizationModelExtension();
		$currentorg->load( $org_id );
		
		$this->OrgController = new OrganizationController();
		$this->C_campaign_controller = new CampaignController();
		$this->campaign_model = new CampaignBaseModel();
		$this->C_couponSeriesManager = new CouponSeriesManager();
		$this->C_bounceback_controller = new BounceBackController();
		$this->C_config_mgr = new ConfigManager();
		$this->C_assets_manager = new CreativeAssetsManager();
	}
	
	public function process(){
		
		$this->logger->debug( 'Checking For Type : ' . $this->type );
		
		switch ( $this->type ){
			
			case 'zones' :
				
				$this->logger->debug( 'Fetching Actions For :' .print_r($this->params,true) );
				
				$this->getStoresByZones( $this->params );
				
				break;
				
			case 'sub_type_list' :
				
				$this->logger->debug( 'Fetching Actions For :' .$this->params[0] );
				
				$this->getSubTypeByType( $this->params[0] );
				
				break;

			case 'campaign_load':
                          
                   $this->logger->debug( 'Fetching Actions For :' .$this->params[0] );
                          
                   $this->loadCampaignData();
                          
                   break;

			case 'msg_preview':
				
					$this->logger->debug( 'Fetching Actions For :' .$this->params[0] );
				
					$this->getPreview( $this->params );
				
					break;
					
		      case 'spam_status':
					
					$this->logger->debug( 'Fetching Actions For :' .$this->params[0] );
					
					$this->getSpamStatus();
					
					break;
		
	  	      case 'renew_credit':
					
					$this->logger->debug( 'Checking For Type : ' . $this->type );
			
					$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
					
					$this->renewCredit( $this->params[0] );
					break;
					
		case 'update_campaign':
					$this->logger->debug( 'Checking For Type : ' . $this->type );
							
					$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
				
					$this->updateCampaignName();
					
					break;
					
		case 'update_campaign_date':
					$this->logger->debug( 'Checking For Type : ' . $this->type );
				
					$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
					
					$this->updateCampaignDate( $this->params );
					
					break;
					
		case 'selection_filter':
					$this->logger->debug( 'Checking For Type : ' . $this->type );
							
					$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
							
					$this->ProcessFilterForm( $this->params[0] );
							
					break;
					
		case 'process_select_msg':
					
					$this->logger->debug( 'Checking For Type : ' . $this->type );
				
					$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
				
					$this->processSelectMessage();
				
					break;
					
		case 'process_edit_message':
					$this->logger->debug( 'Checking For Type : ' . $this->type );
					
					$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
					
					$this->processEditMessage();
					
					break;
		case 'audience':
					
					$this->logger->debug( 'Checking For Type : ' . $this->type );
					
					$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
					
					$this->audienceTableDataLoad();
					
					break;

		case 'changeFavourite' :
			
					$this->logger->debug( 'Change Favourite Type For Group : '. $_GET['group_id'] );

					$this->C_campaign_controller->changeFavouriteTypeForGroup( $_GET['group_id'] );
							
					break;

		case 'process_review':
			
					$this->logger->debug( 'Checking For Type : ' . $this->type );
					
					$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
					
					$this->processReviewAndSend();
					
					break;
					
		case 'process_sms_review':
			
					$this->logger->debug( 'Checking For Type : ' . $this->type );
					
					$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
					
					$this->processSMSReviewAndSend();
					
					break;
					
		case 'process_customer_review':
			
					$this->logger->debug( 'Checking For Type : ' . $this->type );
					
					$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
					
					$this->processCustomerReviewAndSend();
					
					break;
					
		case 'queue_message':
			
					$this->logger->debug('@@@INSIDE QUEUE MESSAGE');
				
					$this->processQueueMessage();
				
					break;

		case 'edit_msg_flow':
			
					$this->logger->debug( 'Checking For Type : ' . $this->type );
					
					$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
					
					$this->renderMesssageForEdit( $this->params );
					
					break;
					
		case 'edit_sms_flow':
			
					$this->logger->debug( 'Checking For Type : ' . $this->type );
					
					$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
					
					$this->renderSMSForEdit( $this->params );
					
					break;
					
		case 'edit_customer_flow':
			
					$this->logger->debug( 'Checking For Type : ' . $this->type );
					
					$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
					
					$this->renderCustomerForEdit( $this->params );
					
					break;
					
		case 'campaign_messages':
			
				   $this->logger->debug( 'Fetching Actions For :' .$this->params[0] );
                          
                   $this->loadCampaignMessages();
                          
                   break;
                   
		case 'delete_file':
					
					$this->logger->debug('@@@Inside Delete file' );
					
					$this->deleteEmailTemplateFiles();
				
					break;
					
		case 'delete_template':
			
					$this->logger->debug('@@@Inside Delete Template' );
					
					$this->deleteEmailTemplate();
					
					break;
					
		case 'add_remove_sticky_groups':

				$sticky = WidgetFactory::getWidget( 'campaign::v2::audience::AddRemoveSubscriberWidget' , $_REQUEST['form'] );
				$sticky->initArgs( $_GET['campaign_id'] , $_GET['group_id'] );
				$sticky->init();
				$sticky->process();

				break;
				
		case 'queue_sms' :
				
				$this->logger->debug( 'Checking For Type : ' . $this->type );
					
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
				
				$this->processQueueSMS();
				
				break;
				
		case 'queue_customer' :
				
				$this->logger->debug( 'Checking For Type : ' . $this->type );
					
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
				
				$this->processQueueCustomer();
				
				break;
				
		case 'campaign_tasks' :
			
				$this->logger->debug( 'Fetching Actions For :' .$this->params[0] );
                          
                $this->loadStoreTasks();
                          
                break;
                
		case 'requeue_msgs' :
			
				$this->logger->debug( 'Checking For Type : ' . $this->type );
					
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
				
				$this->requeueCampaignMessage( $this->params );
				
				break;
				
		case 'generate_tasks_entries' :
			
				$this->logger->debug( 'Checking For Type : ' . $this->type );
					
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
				
				$this->generateStoreTaskEntries( $this->params );
				
				break;
				
		case 'refresh_template_list' :
			
				$this->logger->debug( 'Checking For Type : ' . $this->type );
					
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
			
				$this->refreshTemplateList($this->params);
				
				break;
				
		case 'scheduler_status' :
			
				$this->logger->debug( 'Checking For Type : ' . $this->type );
					
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
			
				$this->schedulerStatus( $this->params );
				
				break;
				
		case 'create_update_quick_coupon' :
			
				$this->logger->debug( 'Checking For Type : ' . $this->type );
					
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params , true ) );
			
				$this->addOrUpdateQuickCoupon();
				
				break;
				
		case 'load_outbound_coupons' :
			
				$this->logger->debug( 'Checking For Type : ' . $this->type );
					
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params , true ) );
                          
                $this->loadCouponsData();
                          
                break;
                
        case 'load_bounceback_coupons' :
			
				$this->logger->debug( 'Checking For Type : ' . $this->type );
					
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params , true ) );
                          
                $this->loadBouncebackCouponsData();
                          
                break;

		case 'edit_outbound_coupon' :
			
				$this->logger->debug( 'Checking For Type : ' . $this->type );
					
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params , true ) );
                          
                $this->showCouponForEdit( $this->params );
                          
                break;
                
        case 'edit_bounceback_coupon' :
			
				$this->logger->debug( 'Checking For Type : ' . $this->type );
					
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params , true ) );
                          
                $this->showBouncebackCouponForEdit( $this->params );
                          
                break;
                
		case 'create_update_advance_coupon' :
			
				$this->logger->debug( 'Checking For Type : ' . $this->type );
					
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params , true ) );
				
				$this->addOrUpdateAdvanceCoupon();
				
				break;
				
		case 'save_social_url':

				$this->logger->debug( 'Checking For Type : ' . $this->type );
					
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params , true ) );
				
				$this->saveSupportedSocialUrlForOrg();
				
				break;
				
		case 'refresh_customer_list' :
			
				$this->logger->debug( 'Checking For Type : ' . $this->type );
					
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
			
				$this->refreshCustomerList();
				
				break;
		
		case 'get_campaign_reports':
				$this->logger->debug( 'Checking For Type : ' . $this->type );
				$this->logger->debug( '@@Fetching Actions For :' .print_r( $this->params , true ) );
				$this->getCampaignReports( $this->params );
				break;
			
		case 'download_campaign_report':
				$this->logger->debug( '@@fetching action for : '.print_r( $this->params , true ) );
				$this->DownloadCampaignReport( $this->params );
				break;

		case 'filter_email_report':
				$this->logger->debug( '@@fetching action for : '.print_r( $this->params , true ) );
				$this->FilterEmailReport( $this->params );
				break;

		case 'open_sticky_popup':

			$this->logger->debug( 'Checking For Type : ' . $this->type );
					
			$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
		
			$this->openStickyList( $this->params );
			
			break;
				
		case 'email_reports' :
			
				$this->getEmailReports( $this->params );
				break;
		
		case 'get_embedded_report':
			$this->logger->debug( '@@fetching action for : '.print_r( $this->params , true ) );
			$this->getEmbeddedReport($this->params);
			break;
			
		case 'roi_report' : 
				$this->logger->debug( '@@fetching action for : '.print_r( $this->params , true ) );
				$this->roiReport( $this->params );
				break;

		case 'delivery_report' : 
				$this->logger->debug( '@@fetching action for : '.print_r( $this->params , true ) );
				$this->deliveryReport( $this->params );
				break;		
				
		case 'show_upload_status':

				$this->logger->debug('Checking For Type :'.$this->type );

				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );

				$this->returnUploadingFileStatus();

				break;
		
		case 'get_email_overview' :
				$this->logger->debug( '@@fetching action for : '.print_r( $this->params[1] , true ) );
				$this->getEmailOverview( $this->params );
				break;
				
		case 'get_campaign_running_details' :
				$this->logger->debug( '@@fetching action for : getCampaignRunningDetails ');
				$this->getCampaignRunningDetails();
				break;
				
		case 'get_customer_task' :
				$this->logger->debug( '@@fetching action for customer task : '.print_r( $this->params , true ) );
				$this->getCustomerTasks( $this->params );
				break;
				
		case 'get_task_form' :
				$this->logger->debug( '@@fetching action for customer task : '.print_r( $this->params , true ) );
				$this->filterCustomerTask( $this->params );
				break;
		
		case 'view_task_entries' :
				$this->logger->debug( '@@fetching action for customer entries : '.print_r( $this->params , true ) );
				$this->viewCustomerTaskEntries();
				break;
				
		case 'get_message_preview' :
			
				$this->logger->debug( '@@@Fetching message for preview.'.print_r( $this->params , true ) );
				$this->getMessageForPreviewById();
				break;
				
		case 'process_select_call_msg':
						
				$this->logger->debug( 'Checking For Type : ' . $this->type );
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
				$this->processCallTaskSelectMessage();
				break;
				
		case 'process_group_call':
			
				$this->logger->debug( 'Checking For Type : ' . $this->type );
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
				$this->processCallTaskGroupSelect();
				break;

		case 'queue_call_task':
						
				$this->logger->debug( 'Checking For Type : ' . $this->type );
				$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
				$this->processQueueCallTask();
				break;
				
		case 'edit_call_task_flow':
				
			$this->logger->debug( 'Checking For Type : ' . $this->type );
				
			$this->logger->debug( 'Fetching Actions For :' .print_r( $this->params,true) );
				
			$this->renderCallTaskForEdit( $this->params );
				
			break;
				

		case 'download_sent'  :
				$this->logger->debug( "In Sent Downloads" );		
				$this->downloadSentReport();
				
				break;
				
		case 'summary_report_change_page' :
			
				$this->logger->debug( "In summary Report change page" );
				$this->summaryReportChangePage();
				break;
			
		case 'summary_report_nsadmin_campaign' :
			
				$this->logger->debug( "In Summary Report by Campaign Id for NSADMIN " );
				$this->summaryReportCampaignId();
				break;
				
		case 'nsadmin_date_range_report' :
				$this->bulkDateRangeReport();
				break;		
		
		case 'summary_report_auto_refresh' :
				$this->bulkReportAutoRefresh();
				break;	
				
	}
}
	
	private function getStoresByZones( $zones ){
		
		$this->logger->debug( " $zones check_karle"	 );
		$stores = $this->OrgController->ZoneController->getChildersByTypeAsOption( $zones , 'TILL' );//$am->getStoresByZones( $zones );
		$this->createSelectString( $stores );
	}
	
	private function getSubTypeByType( $type ){
		
		switch( $type ){
			
			case 'custom' :
				
				$cf = new CustomFields();
				$custom_fields = $cf->getCustomFieldsByScope( $this->org_id, 'loyalty_registration', false );
				$option = array();
				foreach( $custom_fields as $cf ){
					
					$option[$cf['name']] = $cf['id'];
				}
				
				$this->createSelectString( $option );
				break;
				
			case 'transaction' :
				
				$option[_campaign("Based On Joined Date")] = 'joined';
				$option[_campaign("Based On Last Transaction Date")] = 'last_transaction';
			
				$this->createSelectString( $option );
				break;
				
			case 'product' :
				
				$am = new AdministrationModule();
				
				$option = $am->inventory->getAttributesAsOptions();
				$this->createSelectString( $option );	
				break;
				
			case 'bill' :
				
				$option[_campaign("Current Bill Binding")] = 'current';
				$option[_campaign("Last Bill Binding")] = 'last_bill';
			
				$this->createSelectString( $option );			
				break;
		}
	}
	
	/**
	 * preview on left hand side selected for campaign message
	 * @param int template_id
	 */
	private function getPreview( $params ){
			
		$template_id = $params[0];
		$category = $params[1];
		$this->logger->debug( '@@@HTML Template Id:'.$template_id );
		
		global $currentorg;
		
		$stored = new StoredFiles( $currentorg );
		
		$org_id = false;
		if( strtolower( $category ) == 'basic' || strtolower( $category ) == 'advanced' ){
			$org_id = '-20';
		}
			
		$result = $stored->retrieveContents( $template_id , $org_id );
		$this->logger->debug( '@@@HTML Template Content:'.print_r($result,true) );
		
		if( $template_id == -1 )
			$result['file_contents'] = "{{unsubscribe}}";
		else{
			
			$message = stripcslashes( $result['file_contents'] );
			$find_unsubscribe = '{{unsubscribe}}';
			$pos = strpos( $message , $find_unsubscribe );
			if( $pos === false ){
				$result['file_contents'] .= "{{unsubscribe}}";
			}
		}
			
		$this->data['info'] = htmlspecialchars_decode( stripcslashes( $result['file_contents'] ));
	}
	
	/**
	 * Returns Spam status of email.
	 */
	private function getSpamStatus(){
		
		$this->logger->debug( '@@@Start Spam Score Process' );
		
		$email_body = html_entity_decode( $_REQUEST['html_content'] );
		$subject = rawurldecode( $_REQUEST['subject'] );
		
		$email_options = array( 'email_body' => $email_body , 'subject' => $subject );
		
		$this->logger->debug( '@@@EMAIL_OPTIONS:'.print_r( $email_options , true ) );

		include_once 'business_controller/campaigns/SpamChecker.php';

		$C_spam_checker = new SpamChecker();
		$response = $C_spam_checker->checkScore( $email_options );
		
		$this->logger->debug( '@@@ Ajax Response :'.print_r( $response , true ) );
		
		$this->logger->debug( '@@@ Spam Value :'.print_r( $response , true ) );
		
		if( $response['score'] ){
			
			$this->logger->debug( '@@@ Spam Widget :'.print_r( $response , true ) );
			$spam_widget = WidgetFactory::getWidget( 'campaign::v2::messages::SpamCheckerWidget' );
	        $spam_widget->init();
	        $spam_widget->initArgs( $response );
	        $spam_widget->process();
	
	        $this->data['spam_html'] = rawurlencode( $spam_widget->render( true ) );
	        $this->data['score'] = $response['score'];
	        $this->logger->debug( '@@@ End Spam Widget ' );
		}
	}

	/**
	* Ajax Table for Campaign.
	*/
	private function loadCampaignData(){

                $this->logger->debug( '@@@Inside Campaign Load Data'.print_r($_GET,true));

                $campaign_table_widget = WidgetFactory::getWidget( 'campaign::v2::base::CampaignHomeTableHandlingWidget' , array( $_GET ));
                $campaign_table_widget->init();
                $campaign_table_widget->process();

                $this->data = $campaign_table_widget->getResponse();
				
                //$this->logger->debug( '@@@Campaign_JSON'.print_r( $this->data , true) );
    }
    
    /**
	* Ajax Table for Campaign Messages.
	*/
    private function loadCampaignMessages(){
    	
    	$this->logger->debug( '@@@Inside Campaign Messages Load Data'.print_r($_GET,true));

        $msgs_table_widget = WidgetFactory::getWidget( 'campaign::v2::messages::CampaignMessagesTableHandlingWidget' , array( $_GET ));
        $msgs_table_widget->init();
        $msgs_table_widget->process();

        $this->data = $msgs_table_widget->getResponse();

        //$this->logger->debug( '@@@Campaign_Msgs_JSON'.print_r( $this->data , true) );
    }

	/**
	 * To renew SMS Credit for the organization
	 * @param unknown_type $credit
	 */
	public function renewCredit( $credit ){
		
		$this->logger->debug('@@@Inside Renew Credit@@@');
		$this->logger->debug('@@@BMGBulk Credit To Renew '.print_r( $_GET , true ));
	
		//$status = $this->C_campaign_controller->updateBulkSMSCredit( $credit );
		$health = new HealthDashboardController();
		$params['bulk_credits'] = $_GET['sms_credit'];
		$params['value_credits'] = $_GET['email_credit'];
		
		try{
			
			$status = $health->sendMailForCreditsRequest( $params );
			if( $status ){
				$this->data['info'] = 'success';
				$this->data['credit'] = $credit;
			}else
				$this->data['info'] = 'fail';
				
		}catch( Exception $e ){
			$this->data['info'] = 'fail';
			$this->data['message'] = $e->getMessage();			
		}
	}
	
	/**
	 * update Campaign Name.
	 * @param unknown_type $campaign_name
	 */
	private function updateCampaignName(){
			
			$this->logger->debug('@@@REQUEST'.print_r( $_REQUEST , true ) );
			
			$campaign_id = $_REQUEST['campaign_id'];
			$campaign_name = $_REQUEST['c_name'];
			$campaign_desc = $_REQUEST['c_desc'];
			$start_date = $_REQUEST['start_date'];
			$end_date = $_REQUEST['end_date']." 23:59:59";
			$test_control=$_REQUEST['isTestControlEnabled'];
			if(isset($_REQUEST['isTestControlEnabled'])){
				
				if($test_control=="false"){
					$this->logger->debug('ashish naam');
					$test_control=0;
				}
				else{
					$this->logger->debug('ashish naam bada');
					$test_control=1;
				}
				$params['is_test_control_enabled']=$test_control;
				$this->logger->debug('amol updated'.print_r($test_control,true));
			}
			$this->campaign_model->load( $campaign_id );
			$campaign_type = $this->campaign_model->getType();
			$this->logger->debug('@@@Campaign Name is Updating');

			if( $this->campaign_model->getOrgId() != $this->org_id ){
				$this->data['info'] = ORG_CHANGED_MESSAGE;
				return;
			}
				
			$params['name'] = $campaign_name;
			$params['start_date'] = $start_date;
			$params['end_date'] = $end_date;
			$params['desc'] = addslashes( Util::valueOrDefault( $campaign_desc , $this->campaign_model->getDescription() ) );
				
			$status = $this->C_campaign_controller->update( $params, $campaign_id );
			
			$this->data['info'] = $status;
			
			//Updating Task dates when campaign dates will be updated
			if( $status == "SUCCESS" ){
				
				$this->C_campaign_controller->updateCouponSeriesExpiryDateByCampaignId( 
						$campaign_id );
				
				include_once 'tasks/impl/CallTask.php';
				$C_task = new CallTask();
				$C_task->updateDates( $campaign_id , $params );
			}
			
			// TODO
			if( $status == "SUCCESS" && $campaign_type == 'action' ){
				
				$this->C_bounceback_controller->updateRuleSetDates( $campaign_id, $start_date, $end_date);
				$this->C_bounceback_controller->reconfigureOrganization();
			}
			
			if( $campaign_type == 'referral' ){
					
				include_once 'business_controller/ReferralCampaignController.php';
				$C_ref_controller = new ReferralCampaignController();
				$C_ref_controller->updateReferralRuleSetDates( 
									$campaign_id, $start_date, $end_date );
				$C_ref_controller->updateVoucherSeriesValidTillDate(
													$campaign_id, $end_date );
				$C_ref_controller->reconfigureOrganization();
			}
			
			//Returning updated value.
			$this->data['campaign_name'] = Util::beautify( $campaign_name );
			$this->data['start_date'] = $start_date;
			$this->data['end_date'] = $end_date;
			
			$this->logger->info('$$$DATA'.print_r($this->data,true));
	}
	
	/**
	 * updation of campaign Date.
	 *
	 * CONTRACT(
	 * 	$params = array( params[0] = campaign_id
	 * 					 params[1] = start date
	 * 					 params[2] = end date
	 * )
	 */
	private function updateCampaignDate( $params ){
	
	
		$params1 = array();
		$this->logger->debug('@@@Campaign Date is Updating');
		$this->campaign_model->load( $params[0] );
		$params_to_update['name'] = $this->campaign_model->getName();
		$params_to_update['desc'] = $this->campaign_model->getDescription();
		$params_to_update['start_date'] = $params[1];
		$params_to_update['end_date'] = $params[2];
	
		$status = $this->C_campaign_controller->update( $params_to_update, $params[0] );
	
		if( $status == 'SUCCESS' ){
			$this->data['info'] = 'SUCCESS';
			$this->data['date'] = 'From : '.$params[1].' <br/> To :'.$params[2];
			$this->data['from_date'] = date('F d, Y',strtotime($params[1]));
			$this->data['to_date'] = date('F d, Y',strtotime($params[2]));
		}
		else
			$this->data['info'] = 'fail';
	}
	
	/**
	 * passing form id
	 * @param unknown_type $form_id
	 */
	private function ProcessFilterForm( $form_id ){
	
		$this->logger->debug('@@@ Form Processing@@@'.$form_id);
		$this->data['info'] = 'SUCCESS';
	}
		
	/**
	 * Processing First Step of Campaign Email Message. 
	 */
	private function processSelectMessage(){

		$this->logger->debug('@@@Process Select Message@@@ ');
		
		global $prefix,$js,$js_version;
		
		$html_content = $_REQUEST['template_html'];
		
		$select_widget = WidgetFactory::getWidget( 'campaign::v2::messages::EditMessageWidget' );
		$select_widget->init();
		$select_widget->initArgs( $_REQUEST['campaign_id'] , $_REQUEST['message_id'] , $html_content );
		$select_widget->process();
		
		//$html = "<script type='text/javascript' src='$prefix/js/fckeditor_new/fckeditor.js$js_version'></script>";		
		$html = "<script type='text/javascript' src='$prefix/js/ckeditor/ckeditor.js$js_version'></script>";
		$html .= $js->addCKRichtextEditor('edit_template','832px','375');
		
		$html .= $select_widget->render( true );
		
		$this->logger->debug('@@@Process Select HTML@@@ ');
		
		$this->data['info'] = $html;
	}
	
	/**
	 * Processing Second Step of Campaign Message.
	 */
	private function processEditMessage(){
		
		try{
			$subject = $_REQUEST['subject'];
			$html_content = $_REQUEST['html_content'];
			
			$edit_widget = WidgetFactory::getWidget( 'campaign::v2::messages::DeliveryInstructionWidget' );
			$edit_widget->init();
			$edit_widget->initArgs( $_GET['campaign_id'] , $_GET['message_id'] , $html_content , $subject  );
			$edit_widget->process();
			$html = $this->getScriptsHtml();
			$html .= $this->getJSForProcessEditMsg();
			$html .= $edit_widget->render( true );
			$this->data['info'] = $html;
			
		}catch( Exception $e ){
			$this->data['error'] = $e->getMessage();
		}
	}
	
	public function audienceTableDataLoad(){

				$this->logger->debug( '@@@Inside Campaign Audience Data'.print_r($_GET,true));
				
                $campaign_table_widget = 
                		WidgetFactory::getWidget( 'campaign::v2::audience::CampaignAudienceFilterHandlingWidget' , 
                				array( $_GET ) );
                
                $campaign_table_widget->initArgs( $_GET['campaign_id'], $_GET['favourite'] );
                	
                $this->logger->debug('@@@BHAVESH pv');
                
                $campaign_table_widget->init();
                
                $campaign_table_widget->process();
				
                $this->data = $campaign_table_widget->getResponse();

                //$this->logger->debug( '@@@Campaign_JSON'.print_r( $this->data , true) );

	}
	
	/**
	 * 
	 * It will process the email review and send widget
	 */
	private function processReviewAndSend(){
		
		global $prefix,$js,$js_version,$campaign_cfg;
		
		$campaign_id = $_REQUEST['campaign_id'];
		$message_id = $_REQUEST['message_id'];
		$subject = $_REQUEST['subject'];
		$html_content = $_REQUEST['html_content'];
		
		$this->logger->debug( '@@@Delivery1 Start' );
		
		$email_widget = WidgetFactory::getWidget('campaign::v2::messages::DeliveryInstructionWidget');
		$email_widget->initArgs( $campaign_id , $message_id , $html_content , $subject  );
		$email_widget->init();
		$email_widget->process();
		
		$this->logger->debug( '@@@Delivery2 Finish' );
		
		if( $this->data['info'] == 'success' ){

			$data_params = json_decode( $this->data['data_params'] , true );
			
			$data_params['message'] = $html_content;
			
			if( empty( $message_id ) )
				$data_params['message'] = $this->getFTFAndViewBrowserHtml( $html_content );
			
			$data_params['subject'] = $subject;
			
			$outbound_email_widget = WidgetFactory::getWidget('campaign::v2::messages::ReviewAndSendWidget');
			$outbound_email_widget->initArgs( $campaign_id, json_encode( $data_params ) , $message_id );
			$outbound_email_widget->init();
			$outbound_email_widget->process();
			
			$html = "<script type='text/javascript' src='$prefix/js/ckeditor/ckeditor.js$js_version'></script>";
			
			$scripts = $js->getOnLoadValues();
			$html .= "<script type='text/javascript'>
							$scripts
						 </script>";
							
			$html .= $outbound_email_widget->render( true );
			
			$this->data['review_send'] = rawurlencode( $html );
		}
		
		$this->logger->debug( '@@@Preview And Send Finish' );
	}
	
	/**
	 * 
	 * It will process and save the configured email setttings
	 */
	private function processQueueMessage(){
		
		$outbound_email_widget = WidgetFactory::getWidget( 'campaign::v2::messages::ReviewAndSendWidget' );
		$outbound_email_widget->initArgs( $_GET['campaign_id'] , false , $_GET['message_id'] );
		$outbound_email_widget->init();
		$outbound_email_widget->process();
		
		if( $this->data['info'] == 'success' ){
			
			$template_selection = WidgetFactory::getWidget( 'campaign::v2::messages::TemplateSelectionWidget' );
			$template_selection->init();
			$template_selection->process();
			
			$this->data['template_selection'] = $template_selection->render( true );
		}
		
		$this->logger->debug( '@@@Review And Send Process Finish' );
	}
	
	/**
	 * 
	 * It will renders the email message in edit mode
	 * @param unknown_type $params
	 */
	private function renderMesssageForEdit( $params ){
		
		global $prefix,$js,$js_version;
		
		$this->logger->debug( '@@@Render Edit Process Start Params : '.print_r( $params , true ) );
		
		$campaign_id = $params[0];
		$msg_id = $params[1];
		
		$C_message = new BulkMessage();
		$C_message->load( $msg_id );
		
		$campaign_id = $C_message->getCampaignId();
		$this->C_campaign_controller->load( $campaign_id );
		$org_id = $this->C_campaign_controller->campaign_model_extension->getOrgId();
		
		if( $this->org_id != $org_id ){
			$this->data['error'] = _campaign("You have changed the organization in another tab. Please refresh the page to reflect the changes.");
			return;
		}
		
		if( $C_message->getStatus() == 'SENT' ){
			$this->data['error'] = _campaign("Message is already sent. You are not allowed to edit this message after sending.");
			return;
		}
		
		//Step 1 : Render EditMsgWidget
		$select_widget = WidgetFactory::getWidget( 'campaign::v2::messages::EditMessageWidget' );
		$select_widget->init();
		$select_widget->initArgs( $campaign_id , $msg_id );
		$select_widget->process();
		
		$html = "<script type='text/javascript' src='$prefix/js/ckeditor/ckeditor.js$js_version'></script>";
		$html .= $js->addCKRichtextEditor('edit_template','832px','375');
		
// 		$html = "<script type='text/javascript' src='$prefix/js/fckeditor_new/fckeditor.js$js_version'></script>";
// 		$html .= $js->addFCKRichtextEditor('edit_template','832px','375');
		
		$html .= $select_widget->render( true );
		
		$this->logger->debug('@@@Process Edit HTML@@@ ');
		
		$this->data['info_edit_msg'] = $html;
		
		//Step 2 : Render Delivery Intructions
		$html = '';
		$this->logger->info('@@@After Edit Message Rendering' );
		
		$edit_widget = WidgetFactory::getWidget( 'campaign::v2::messages::DeliveryInstructionWidget' );
		$edit_widget->init();
		$edit_widget->initArgs( $campaign_id , $msg_id );
		$edit_widget->process();
		
		$scripts = $js->getOnLoadValues();
		$html .= "<script type='text/javascript'>
					$scripts
				 </script>";
		
		$html .= "
				<script type='text/javascript'>
				
					$('#delivery__cron_day').closest('tr').hide()
					
					$('#delivery__send_when').change(function(){
						var value = $('#delivery__send_when').val();

						if( value == 'PARTICULAR_DATE' ){
							 $('#delivery__minutes').closest('tr').show();
						}else{
							$('#delivery__minutes').closest('tr').hide();
						}
					});
					
					$('#delivery__send_when').trigger('change');
					
					$('#delivery__send_when').change(function(){
						var value = $('#delivery__send_when').val();

						if( value == 'SCHEDULE' ){
							 $('#delivery__cron_day').closest('tr').show();
							
						}else{
							$('#delivery__cron_day').closest('tr').hide();
						}
					});
					$('#delivery__send_when').trigger('change');
					
					$('.pencil_footer').click(function(){

						$('#delivery__signature_value').removeAttr('readonly');							
						$('.save_footer').removeClass('hide');
						$('.pencil_footer').addClass('hide');
					});
					
					$('.save_footer').click(function(){

						$('#delivery__signature_value').attr('readonly','readonly');							
						$('.save_footer').addClass('hide');
						$('.pencil_footer').removeClass('hide');	
					});
				</script>
				";
		
		$html .= $edit_widget->render( true );
		
		//$this->logger->info('@@@Delivery Html '.$html);
		
		$this->data['info_delivery'] = $html;
		
		$this->logger->debug( '@@@Render Edit Process Finish' );
	}
	
	/**
	 * 
	 * Delete image file for email template.
	 */
	public function deleteEmailTemplateFiles(){
		
		$file_id = $_GET['file_id'];
		$campaign_email = new CampaignEmailManager();
		$result = $campaign_email->getFileIdById( $file_id );
		$this->deleteEmailTemplate( $result[0]['file_id'] );
	}
	
	/**
	 * 
	 * Delete Email Template.
	 */
	public function deleteEmailTemplate( $file_id ){
		
		if( !$file_id )
			$file_id = $_GET['file_id'];
			
		$file_controller = new FileController();
		$file_controller->deleteFile( $file_id );
		$this->data['info'] = 'SUCCESS';
	}
	
	/**
	 * 
	 * Renders the SMS widget for review and send
	 */
	private function processSMSReviewAndSend(){
		
		global $prefix,$js;
		
		$campaign_id = $_REQUEST['campaign_id'];
		$message_id = $_REQUEST['message_id'];
		$msg_content = $_REQUEST['msg_content'];
		
		$this->logger->debug( '@@@SMS Select Process Start' );
		
		$sms_widget = WidgetFactory::getWidget('campaign::v2::messages::OutBoundSMSWidget');
		$sms_widget->initArgs( $campaign_id , $message_id );
		$sms_widget->init();
		$sms_widget->process();
		
		$this->logger->debug( '@@@SMS Select Process Finish' );
		
		if( $this->data['info'] == 'success' ){
			$this->logger->debug( '@@@SMS Select Process Finish Success' );
			$sms_review_widget = WidgetFactory::getWidget('campaign::v2::messages::OutBoundSMSReviewWidget');
			$sms_review_widget->initArgs( $campaign_id , $message_id , $this->data['data_params'] );
			$sms_review_widget->init();
			$sms_review_widget->process();
			
			$html = $sms_review_widget->render( true );
			
			$this->data['review_send'] = rawurlencode( $html );
		}
		
		$this->logger->debug( '@@@SMS Preview And Send Finish' );
	}
	
	/**
	 * 
	 * Renders the customer widget for review and send
	 */
	private function processCustomerReviewAndSend(){
		
		global $prefix,$js;

		$task_id = $_REQUEST['task_id'];
		$campaign_id = $_REQUEST['campaign_id'];
		$message_id = $_REQUEST['message_id'];
		$msg_content = $_REQUEST['msg_content'];
		
		$this->logger->debug( '@@@Customer Select Process Start' );
		
		$customer_widget = WidgetFactory::getWidget('campaign::v2::tasks::CustomerWidget');
		$customer_widget->initArgs( $campaign_id , $task_id , $message_id );
		$customer_widget->init();
		$customer_widget->process();
		
		$this->logger->debug( '@@@Customer Select Process Finish' );
		
		if( $this->data['info'] == 'success' ){

			$customer_review_widget = WidgetFactory::getWidget('campaign::v2::tasks::CustomerReviewWidget');
			$customer_review_widget->initArgs( $campaign_id , $this->data['data_params'] );
			$customer_review_widget->init();
			$customer_review_widget->process();
			
			$html = $customer_review_widget->render( true );
			
			$this->data['review_send'] = rawurlencode( $html );
		}
		
		$this->logger->debug( '@@@Customer Preview And Send Finish' );
	}
	
	/**
	 * 
	 * It will process and save the configured email setttings
	 */
	private function processQueueSMS(){
		
		global $prefix,$js;
		
		$outbound_sms_widget = WidgetFactory::getWidget( 'campaign::v2::messages::OutBoundSMSReviewWidget' );
		$outbound_sms_widget->initArgs( $_GET['campaign_id'] , $_GET['message_id'] );
		$outbound_sms_widget->init();
		$outbound_sms_widget->process();
		
		if( $this->data['info'] == 'success' ){
			
			$sms_selection = WidgetFactory::getWidget( 'campaign::v2::messages::OutBoundSMSWidget' );
			$sms_selection->init();
			$sms_selection->initArgs( $_GET['campaign_id'] );
			$sms_selection->process();
			
			$scripts = $js->getOnLoadValues();
		
			$html = "<script type='text/javascript'>
					$scripts
				 </script>";
		
			$html .= "
				<script type='text/javascript'>
				
					$('#sms_settings__cron_day').closest('tr').hide()
					
					$('#sms_settings__send_when').change(function(){
						var value = $('#sms_settings__send_when').val();

						if( value == 'PARTICULAR_DATE' ){
							 $('#sms_settings__minutes').closest('tr').show();
						}else{
							$('#sms_settings__minutes').closest('tr').hide();
						}
					});
					
					$('#sms_settings__send_when').trigger('change');
					
					$('#sms_settings__send_when').change(function(){
						var value = $('#sms_settings__send_when').val();

						if( value == 'SCHEDULE' ){
							 $('#sms_settings__cron_day').closest('tr').show();
							
						}else{
							$('#sms_settings__cron_day').closest('tr').hide();
						}
					});
					$('#sms_settings__send_when').trigger('change');
					
				</script>
				";
		
			$this->data['sms_selection'] = $html.$sms_selection->render( true );
		}
		
		$this->logger->debug( '@@@SMS Review And Send Process Finish' );
	}
	
	/**
	 * 
	 * It will process and save the configured email setttings
	 */
	private function processQueueCustomer(){
		
		global $prefix,$js;
		
		$customer_widget = WidgetFactory::getWidget( 'campaign::v2::tasks::CustomerReviewWidget' );
		$customer_widget->initArgs( $_GET['campaign_id'] , $_GET['task_id'] , $_GET['message_id'] );
		$customer_widget->init();
		$customer_widget->process();
		
		if( $this->data['info'] == 'success' ){
			
			$customer_selection = WidgetFactory::getWidget( 'campaign::v2::tasks::CustomerWidget' );
			$customer_selection->init();
			$customer_selection->initArgs( $_GET['campaign_id'] );
			$customer_selection->process();
			
			$scripts = $js->getOnLoadValues();
		
			$html = "<script type='text/javascript'>
					$scripts
				 </script>";
			
			$this->data['customer_selection'] = $html.$customer_selection->render( true );
		}
		
		$this->logger->debug( '@@@Customer Review And Send Process Finish' );
	}
	
	/**
	 * 
	 * It will render the email message in edit mode
	 * @param unknown_type $params
	 */
	private function renderSMSForEdit( $params ){
		
		global $prefix,$js;
		
		$this->logger->debug( '@@@Render SMS Edit Process Start Params : '.print_r( $params , true ) );
		
		$campaign_id = $params[0];
		$msg_id = $params[1];
		
		$C_message = new BulkMessage();
		$C_message->load( $msg_id );
		
		$campaign_id = $C_message->getCampaignId();
		$this->C_campaign_controller->load( $campaign_id );
		$org_id = $this->C_campaign_controller->campaign_model_extension->getOrgId();
		
		if( $this->org_id != $org_id ){
			$this->data['error'] = _campaign("You have changed the organization in another tab. Please refresh the page to reflect the changes.");
			return;
		}
		
		if( $C_message->getStatus() == 'SENT' ){
			$this->data['error'] = _campaign("Message is already sent. You are not allowed to edit this message after sending.");
			return;
		}
		
		//Step 1 : Render EditMsgWidget
		$select_widget = WidgetFactory::getWidget( 'campaign::v2::messages::OutBoundSMSWidget' );
		$select_widget->init();
		$select_widget->initArgs( $campaign_id , $msg_id );
		$select_widget->process();
		
		$scripts = $js->getOnLoadValues();
		
		$html = "<script type='text/javascript'>
					$scripts
				 </script>";
		
		$html .= "
				<script type='text/javascript'>
				
					$('#sms_settings__cron_day').closest('tr').hide()
					
					$('#sms_settings__send_when').change(function(){
						var value = $('#sms_settings__send_when').val();

						if( value == 'PARTICULAR_DATE' ){
							 $('#sms_settings__minutes').closest('tr').show();
						}else{
							$('#sms_settings__minutes').closest('tr').hide();
						}
					});
					
					$('#sms_settings__send_when').trigger('change');
					
					$('#sms_settings__send_when').change(function(){
						var value = $('#sms_settings__send_when').val();

						if( value == 'SCHEDULE' ){
							 $('#sms_settings__cron_day').closest('tr').show();
							
						}else{
							$('#sms_settings__cron_day').closest('tr').hide();
						}
					});
					$('#sms_settings__send_when').trigger('change');
					
				</script>
				";
		
		$html .= $select_widget->render( true );
		
		//$this->logger->debug('@@@Process SMS Edit HTML@@@ '.$html);
		
		$this->data['info_sms_msg'] = $html;
		
		$this->logger->debug( '@@@Render SMS Edit Process Finish' );
	}
	
	/**
	 * 
	 * It renders the store task in edit mode
	 * @param unknown_type $params
	 */
	private function renderCustomerForEdit( $params ){
		
		global $prefix,$js;
		
		$this->logger->debug( '@@@Render Customer Edit Process Start Params : '.print_r( $params , true ) );
		
		$campaign_id = $params[0];
		$task_id = $params[1];
		$msg_id = $params[2];
		
		//Step 1 : Render EditMsgWidget
		$select_widget = WidgetFactory::getWidget( 'campaign::v2::tasks::CustomerWidget' );
		$select_widget->init();
		$select_widget->initArgs( $campaign_id , $task_id , $msg_id );
		$select_widget->process();
		
		$scripts = $js->getOnLoadValues();
		
		$html = "<script type='text/javascript'>
					$scripts
				 </script>";
		
		$html .= $select_widget->render( true );
		
		$this->logger->debug('@@@Process Customer Edit HTML@@@ '.$html);
		
		$this->data['info_sms_msg'] = $html;
		
		$this->logger->debug( '@@@Render Customer Edit Process Finish' );
	}
	
	/**
	* Ajax Table for Store Tasks.
	*/
    private function loadStoreTasks(){
    	
    	$this->logger->debug( '@@@Inside Store Tasks Load Data : '.print_r($_GET,true) );

        $tasks_table_widget = WidgetFactory::getWidget( 'campaign::v2::tasks::StoreTasksTableHandlingWidget' , array( $_GET ) );
        $tasks_table_widget->init();
        $tasks_table_widget->process();

        $this->data = $tasks_table_widget->getResponse();

        //$this->logger->debug( ' @@@Store_Tasks_JSON : '.print_r( $this->data , true ) );
    }

    /**
     * 
     * It will requeue the campaign message by msg id
     */
    private function requeueCampaignMessage( $params ){
    	
    	$this->logger->debug( '@@@Inside Requeue Messages : '.print_r($params,true) );
    	
    	$msg_id = $params[1];
    	
    	$C_message = new BulkMessage();
    	$C_message->load( $msg_id );
    	
    	$blast_type = strtoupper( $C_message->getType() );

    	include_once 'business_controller/campaigns/message/api/BulkMessageTypes.php';
    	include_once 'business_controller/campaigns/message/impl/BulkMessageSenderFactory.php';
    	
    	try{
    		
    		$C_bulk_sender = BulkMessageSenderFactory::getSender( BulkMessageTypes::valueOf( $blast_type ) );
    		$C_bulk_sender->reQueue( $C_message );
    		
    		$this->data['info'] = 'success';
    	}catch(Exception $e){
    		
    		$this->data['error'] = $e->getMessage();
    	}
    	$this->logger->debug( '@@@Requeue Messages Finish: ' );
    }
    
 	/**
     * 
     * It will generate store task entries for the particular task
     */
    private function generateStoreTaskEntries( $params ){
    	
    	$this->logger->debug( '@@@Inside Generate Tasks : '.print_r($params,true) );
    	
    	$task_id = $params[1];
    	
    	$C_store_task_manager = new StoreTasksMgr();
    	
    	$numTaskEntries = $C_store_task_manager->createStoreTaskEntries( $task_id );
    	
    	$status = _campaign("Created")." $numTaskEntries "._campaign("Entries for Task Id : ")."$task_id";
    	
    	$this->data['info_status'] = $status;
    	
    	//$this->data['info'] = $numTaskEntries > 0 ? 'success' : 'Store Task is not Present to generate entries';
    	
    	$this->logger->debug( '@@@Generate Tasks Finish: ' );
    }
    
    /**
     * 
     * Refresh the Template List and return li
     */
    private function refreshTemplateList($params){
    	
    	$this->logger->debug( '@@@In Refresh Template List: '.print_r($params, true) );
    	if( $params[0] == 'referral'){
			
    		$email_template_list = 
    			$this->C_assets_manager->getAllTemplatesAsOptions( $this->org_id, 'HTML', 'REFERRAL');
    		$email_template_list = array( 'Referral' => $email_template_list );
    	}else{
	    	
    		$C_email_templates_service_provider = 
				ApplicationFactory::getApplicationByCode( ApplicationType::CAMPAIGN );
			$email_template_list = 
				$C_email_templates_service_provider->getData( CampaignServiceFeatureTypeImpl::$EMAIL_TEMPLATES );
    	}
    	
		$html = '';
		
    	foreach( $email_template_list as $category => $templates ){
			
			foreach( $templates as $key => $value ){
			
				$file_name = str_ireplace('EmailTemplate-', '' , $key );
				$file_name = explode('.html', $file_name );
				
				$nn_name = trim(ucwords(str_replace('-', ' ', trim($file_name[0]))));
				$nn_name = trim(ucwords(str_replace('_', ' ', trim($nn_name))));
					
				$html .= '<li class="clickable-temp" 
							  id="li_'.$value.'" value="'.$value.'" 
							  temp_name="'.strtolower($file_name[0]).'"
							  category="'.Util::beautify( $category ).'">
							'.$nn_name.'</li>';
			}
		}
		
		$this->data['info_temp_list'] = rawurlencode( $html );
		
		$this->logger->debug( ' @@@Finish Refresh Template List ' );
    }
    
    /**
     * 
     * Scheduler Status change for particular msg_id
     * @param unknown_type $params
     */
    private function schedulerStatus( $params ){
    	
    	$this->logger->debug( '@@@In Scheduler Status For Msg Id : '.$params[1] );
    	
    	$campaign_id = $params[0];
    	$msg_id = $params[1];
    	$state = $params[2];
    	
    	try{

			$reminder_id = $this->C_campaign_controller->getReminderByReferenceId( $msg_id );
			$health = new HealthDashboardController();
			$health_reminder_id = $health->getReminderByReferenceId( $msg_id );
	
			if( $reminder_id && $health_reminder_id ){
	
				$reminder = new Reminder( $this->org_id , $reminder_id );
				$campaign_call_back = new CampaignGroupCallBack( $reminder );
				$status = $campaign_call_back->stateChange( $state );
				$this->logger->debug("reminder state in ajax service:".$state);

				$health_reminder = new Reminder( $this->org_id , $health_reminder_id );
				$health_call_back = new HealthDashboardCallBack( $health_reminder );
				$health_status = $health_call_back->stateChange( $state );
				$this->logger->debug("reminder state of health in ajax service:".$state);
				
				$this->data['info_status'] = _campaign("Scheduler is ")."$state "._campaign("Successfully");
			}else{
				$this->data['info_status'] = _campaign(" Reminder is not available ");
			}
			$this->data['info'] = "success";
			
		}catch( Exception $e ){
			$this->data['error'] = $e->getMessage();
		}
    	$this->logger->debug( '@@@Finish Scheduler Status For Msg Id : '.$params[1] );
    }
    
    /**
     * It will create or update coupon in quick flow
     * @author nayan
     */
    private function addOrUpdateQuickCoupon(){
    	
    	global $prefix,$js;
    	
    	$this->logger->debug( '@@@Start Add Or Update Quick Coupon' );
		
		$C_coupon_create = WidgetFactory::getWidget( 'campaign::v2::coupons::BasicCouponCreateWidget' );
		$C_coupon_create->init();
		$C_coupon_create->initArgs( 'quick' , $_REQUEST['campaign_id'] , $_REQUEST['update'] );
		$C_coupon_create->process();
		
		$this->logger->debug( '@@@End Add Or Update Quick Coupon' );
    }
    
    /**
     * It will load coupons for campaign
     * @author nayan
     */
    private function loadCouponsData(){
    	
    	$this->logger->debug( '@@@Inside Coupons Load Data'.print_r($_GET,true) );

        $campaign_table_widget = WidgetFactory::getWidget( 'campaign::v2::coupons::CouponsHomeTableHandlingWidget' , array( $_GET ) );
        $campaign_table_widget->init();
        $campaign_table_widget->process();

        $this->data = $campaign_table_widget->getResponse();

        //$this->logger->debug( '@@@Campaign_JSON'.print_r( $this->data , true) );
    }
    
    
    /**
     * It will load coupons for bounce back campaign 
     * @author shilpa.pai
     */
    private function loadBouncebackCouponsData(){
    	
    	$this->logger->debug( '@@@Inside Bounceback Coupons Load Data'.print_r($_GET,true) );

        $campaign_table_widget = WidgetFactory::getWidget( 'campaign::v2::coupons::BouncebackCouponsHomeTableHandlingWidget' , array( $_GET ) );
        $campaign_table_widget->init();
        $campaign_table_widget->process();

        $this->data = $campaign_table_widget->getResponse();

    }

    /**
     * It will give the html for the edit flow of coupon
     * and also internally decide respective of service pack
     * @author nayan
     */
    private function showCouponForEdit( $params ){
    	
    	global $js;
    	
    	$campaign_id = $params[0];
    	$voucher_series_id = $params[1];
    	
    	$this->logger->debug( '@@@Start Show Coupon For Edit' );
    	
    	$C_outbound_features_provider = 
				ApplicationFactory::getApplicationByCode( ApplicationType::CAMPAIGN );
		
		$features = $C_outbound_features_provider->getData( 
				CampaignServiceFeatureTypeImpl::$OUTBOUND_CAMPAIGN_FEATURES );
		
    	if( $features['outbound_coupon'] == 'advanced' ){
    		
			$coupon_form = WidgetFactory::getWidget( 'campaign::v2::coupons::AdvancedCreateWidget' );
			$coupon_form->init();
			$coupon_form->initArgs( 'advanced', $campaign_id, $voucher_series_id, false );
			$coupon_form->process();
    	}else{
    		
    		$coupon_form = WidgetFactory::getWidget( 'campaign::v2::coupons::BasicCouponCreateWidget' , true );
			$coupon_form->init();
			$coupon_form->initArgs( 'quick', $campaign_id, false );
			$coupon_form->process();	
    	}
			
		$scripts = $js->getOnLoadValues();
		$html = "<script type='text/javascript'>
						$scripts
				  </script>
				  <script type='text/javascript' src='/js/on_off_button.js'></script>
                  <script type='text/javascript' src='/js/campaign/campaign_handling_v2.js'></script>
				";
							
		$html .= $coupon_form->render( true );
			
		$this->data['edit_coupon_html'] = rawurlencode( $html );
			
		$this->logger->debug( '@@@End Add Or Update Coupon' );
    }
    
	/**
     * It will give the html for the edit flow of bounceback coupon
     * @author shilpa.pai
     */
    private function showBouncebackCouponForEdit( $params ){
    	
    	global $js;
    	
    	$campaign_id = $params[0];
    	$voucher_series_id = $params[1];
    	
    	$this->logger->debug( '@@@Start Show Bounceback Coupon For Edit' );
    	
    	$coupon_form = WidgetFactory::getWidget( 'campaign::v2::coupons::AdvancedCreateWidget' );
		$coupon_form->init();
		$coupon_form->initArgs( 'advanced', $campaign_id, $voucher_series_id, false );
		$coupon_form->process();

		$scripts = $js->getOnLoadValues();
		$html = "<script type='text/javascript'>
						$scripts
				  </script>
				  <script type='text/javascript' src='/js/on_off_button.js'></script>
                  <script type='text/javascript' src='/js/campaign/campaign_handling_v2.js'></script>
				";
							
		$html .= $coupon_form->render( true );
			
		$this->data['edit_coupon_html'] = rawurlencode( $html );
			
		$this->logger->debug( '@@@End Show Bounceback Coupon For Edit' );
    }
    
	/**
     * It will create or update coupon in quick flow
     * @author nayan
     */
    private function addOrUpdateAdvanceCoupon(){
    	
    	global $prefix,$js;
    	
    	$this->logger->debug( '@@@Start Add Or Update Advance Coupon' .print_r( $_REQUEST ,true));
		
		$advanced_form = WidgetFactory::getWidget( 'campaign::v2::coupons::AdvancedCreateWidget' );
		$advanced_form->init();
		$advanced_form->initArgs( 'advanced', $_REQUEST['campaign_id'], $_REQUEST['voucher_id'], false );
		$advanced_form->process();
		
		$this->logger->debug( '@@@End Add Or Update Advance Coupon' );
    }
    
	private function convertDatetoMillis($date)
	{
		$timeInMillis = strtotime($date);
		if($timeInMillis == -1 || !$timeInMillis )
		{
			throw new Exception(_campaign("Cannot convert")." '$date' to "._campaign("timestamp"), -1, null);
		}
		$timeInMillis = $timeInMillis * 1000;
		return $timeInMillis;
	}
	
	/**
	 * 
	 * Saving social platform url into org_details.
	 */
	private function saveSupportedSocialUrlForOrg(){
		
		$this->logger->debug( '$$$ INSIDE SUPPORTED SOCIAL $$$$' );

		$url = $_GET['myurl'];
		$id = $_GET['platform'];
		
		$org_detail = new OrgDetailsModel();
		$org_detail->load( $this->org_id );
		$social_data = $org_detail->getHash();
		$social_icon = json_decode( $social_data['social_platforms'] , true );
		$social_icon[$id] = $url;
	
		$final_url = json_encode( $social_icon , true );
		
		$this->logger->debug('@@@SOCIAL'.$final_url);

		$org_detail->setSocialPlatforms( $final_url );
		$status = $org_detail->update( $this->org_id );
		
		if( $status ){
			
				//delete memcache key for active and inactive org 
				$C_mem_cache_manager = MemcacheMgr::getInstance();
				$cache_key = 'o'.$this->org_id.'_'.CacheKeysPrefix::$orgProfileKey.'BASE_LOAD_ARRAY_'.$this->org_id;
				
				try{
					$C_mem_cache_manager->delete( $cache_key );
					$this->logger->debug( '@@ Memcache Org inactive key deleted successfully' );
				}catch( Exception $e ){
					$this->logger->error( 'Key '.$key.' Could Not Be Deleted .'.$e->getMessage() );
				}
				$this->data['status'] = 'SUCCESS';
		}
		else 
			$this->data['status'] = 'FAILED';			
	}
	
	/**
     * 
     * Refresh the customer List and return option
     */
    private function refreshCustomerList(){
    	
		$campaign_id = $_GET['campaign_id'];
		$source = $_GET['source'];

		$campaign_data = $this->C_campaign_controller->
								getCampaignGroupsByCampaignIdAsOptions( $campaign_id );
		
		$camp_groups = array();
		
		$group_list_seprator = "<optgroup label='Campaign Lists'>";
		$group1 = $this->C_campaign_controller->getCampaignGroupsByCampaignIds( $campaign_id );
		
		foreach ( $group1 as $row ){
			$group_list_seprator .= "<option value ='".$row['group_id']."'>".$row['group_label']."</option>"; 
		}
		
		$group_list_seprator .="</optgroup>";

		$group_list_seprator .= "<optgroup label='Sticky Lists'>";
		$group2 = $this->C_campaign_controller->getCampaignGroupsByCampaignIds( '-20' );
		
		foreach ($group2 as $row ){
			$group_list_seprator .= "<option value ='". $row['group_id']."'>".$row['group_label']."</option>";
		}
		$group_list_seprator .= "</optgroup>";
		$this->data['info_customer_list'] = rawurlencode( $group_list_seprator );
		
		$this->logger->debug('@@@OPTIONS:'.$group_list_seprator);
		
		$group_ids = array();
		foreach( $campaign_data as $key => $value ){
			array_push( $group_ids , $value );
		}
		
		$batch_group = implode(',', $group_ids);
		$groupDetails = $this->C_campaign_controller->getGroupsDetailsByGroupID( $batch_group );
		
		$email_help = '';
		
		foreach( $groupDetails as $gd ){
			
			$params_number = json_decode( $gd['params'], true );
			$email_addresses = $params_number['email'];
			$mobile_count = $params_number['mobile'];
			
			if( !$email_addresses )
				$email_addresses = 0;
			
			if( !$mobile_count )
				$mobile_count = 0;

			if( $source ){
				
				$email_help .= "<span id='call_task__help_value_".$gd['group_id']."'
						class='call_task_help hide' mobile_count='$mobile_count'>
						<span class='margin-right'>"._campaign("Customer Count : ").$gd['customer_count']."</span>
						<span>"._campaign("Call Task will be generated for : ")."$mobile_count "._campaign("(Test Group)")."</span></span>";
				$this->data['call_info_customer_count'] = rawurlencode( "<small>".$email_help."</small>" );
			}else{
				$email_help .= "<span id='email_help_value_".$gd['group_id']."'
				class='email_help hide' email_address='$email_addresses'>"._campaign("Email Addresses : ")."$email_addresses</span>";
				$this->data['info_customer_count'] = rawurlencode( "<small>".$email_help."</small>" );
			}
		}
		
		$this->logger->debug( ' @@@Finish Refresh Customer List ' );
    }

    /**
     * to get the html for different types of reports
     * @param unknown_type $params
     */
    private function getCampaignReports( $params ){
    	
    	global $js;
    	
    	$report_type = $params[0];
    	$campaign_id = $params[1];    	
    	
    	switch ( $report_type ){
    		
    		case 'common_roi': 
		    			$this->logger->debug('report type '.$report_type.'. Campaign id '.$campaign_id );
		    			$C_roi = WidgetFactory::getWidget( 'campaign::v2::reports::CampaignRoiReportsWidget' );
						$C_roi->init();
						$C_roi->process();
						$html = $C_roi->render(true);
						$start = $params[2];
						$end = $params[3];
						$roi_name = $params[4];
						$this->getEmailReports( $campaign_id , $report_type , $start , $end , $roi_name );
    				break;
    		case 'test_control':
    					$this->logger->debug('report type '.$report_type.'. Campaign id '.$campaign_id );
    					$C_roi = WidgetFactory::getWidget( 'campaign::v2::reports::CampaignRoiReportsWidget' );
    					$C_roi->init();
    					$C_roi->process();
    					$html = $C_roi->render(true);
    					$start = $params[2];
						$end = $params[3];
						$roi_name = $params[4];
						$this->getEmailReports( $campaign_id , $report_type , $start , $end , $roi_name );
    				break;
    		case 'voucher_summary':
    					$this->logger->debug('report type '.$report_type.'. Campaign id '.$campaign_id );
    					$C_roi = WidgetFactory::getWidget( 'campaign::v2::reports::CampaignRoiReportsWidget' );
    					$C_roi->init();
    					$C_roi->process();
    					$html = $C_roi->render(true);
    					$start = $params[2];
						$end = $params[3];
						$roi_name = $params[4];
						$this->getEmailReports( $campaign_id , $report_type , $start , $end , $roi_name );
    				break;
    		case 'voucher_summary_dvs':
    					$this->logger->debug('report type '.$report_type.'. Campaign id '.$campaign_id );
    					$C_roi = WidgetFactory::getWidget( 'campaign::v2::reports::CampaignRoiReportsWidget' );
    					$C_roi->init();
    					$C_roi->process();
    					$html = $C_roi->render(true);
    					$start = $params[2];
						$end = $params[3];
						$roi_name = $params[4];
						$interval = $params[5];
						$this->getEmailReports( $campaign_id , $report_type , $start , $end , $roi_name , $interval );
    				break;
    		case 'coupon_redemption' :
		    			$this->logger->debug('report type '.$report_type.'. Campaign id '.$campaign_id );
		    			$redemption = WidgetFactory::getWidget( 'campaign::v2::reports::CouponRedemptionWidget' );
						$redemption->init();
						$redemption->initArgs( $campaign_id );
						$redemption->process();
						$html = $redemption->render(true);
		    		break;
    		case 'open_rate' : 
    					$this->logger->debug('report type '.$report_type.'.  Campaign id '.$campaign_id );
		    			$open_rate = WidgetFactory::getWidget( 'campaign::v2::reports::EmailOpenRateReportWidget' );
						$open_rate->init();
						$open_rate->initArgs( $campaign_id );
						$open_rate->process();
						$html .= $open_rate->render(true);
	    			break;
    		case 'click_rate' : 
		    			$this->logger->debug('report type '.$report_type.'. Campaign id '.$campaign_id );
		    			$click_rate = WidgetFactory::getWidget( 'campaign::v2::reports::EmailClickRateReportWidget' );
		    			$click_rate->init();
		    			$click_rate->initArgs( $campaign_id );
		    			$click_rate->process();
		    			$html = $click_rate->render(true);
    				break;
    		case 'messaging' : 
    					$this->logger->debug( 'report type '.$report_type.'. Campaign id '.$campaign_id );
    					$C_report = WidgetFactory::getWidget( 'campaign::v2::reports::MessagingReportWidget' );
    					$C_report->init();
    					$C_report->initArgs( $campaign_id );
    					$C_report->process();
    					if( $C_report->isDataAvailable() ){
    						$html = "<script type='text/javascript'>".$js->getOnLoadValues()."</script>";
    						$html .= $C_report->render( true );
    					}else{
    						$html = "<h3 class='head-report'>"._campaign("Communication Report")."</h3>
    								 <h4 class='empty-msg'>"._campaign("No messages sent")."</h4>";
    					}
    				break;
    				
    		case 'forward' :
	    			$this->logger->debug( 'report type '.$report_type.'. Campaign id '.$campaign_id );
	    			$C_report = WidgetFactory::getWidget( 'campaign::v2::reports::ForwardToFriendsLandingWidget' );
	    			$C_report->init();
	    			$C_report->initArgs( $campaign_id , $_GET['message_id']);
	    			$C_report->process();
	    			
	    			$html = "<script type='text/javascript'>".$js->getOnLoadValues()."</script>";
    				$html .= $C_report->render( true );
    				break;
    				
    		case 'audience_upload_summary' :
    			$this->logger->debug( 'report type '.$report_type.'. Campaign id '.$campaign_id );
    			$C_report = WidgetFactory::getWidget( 'campaign::v2::reports::UploadSummaryTableWidget' );
    			$C_report->init();
    			$audience_or_coupon = "audience";
    			$C_report->initArgs( $campaign_id,$audience_or_coupon );
    			$C_report->process();
    			$html = "<script type='text/javascript'>".$js->getOnLoadValues()."</script>";
    			$html .= $C_report->render( true );
    			break;
    			
    		case 'coupon_upload_summary' :
    			$this->logger->debug( 'report type '.$report_type.'. Campaign id '.$campaign_id );
    			$C_report = WidgetFactory::getWidget( 'campaign::v2::reports::UploadSummaryTableWidget' );
    			$C_report->init();
    			$audience_or_coupon = "coupon";
    			$C_report->initArgs( $campaign_id ,$audience_or_coupon);
    			$C_report->process();
    			$html = "<script type='text/javascript'>".$js->getOnLoadValues()."</script>";
    			$html .= $C_report->render( true );
    			break;
    			
    			
    	    case 'error_summary' :
    	    	$this->logger->debug( 'report type '.$report_type.'. Campaign id '.$campaign_id );
    	    	$id_selected = $params[2];
    	    	$audience_or_coupon = $params[3];
    	    	$C_report = WidgetFactory::getWidget( 'campaign::v2::reports::UploadSummaryTableWidget' );
    	    	$C_report->init();
    	    	$C_report->initArgs( $campaign_id,$audience_or_coupon );
    	    	$C_report->process();
    	    	$html = "<script type='text/javascript'>".$js->getOnLoadValues()."</script>";
    	    	$html = $C_report->render( true );
    	    	$C_report = WidgetFactory::getWidget( 'campaign::v2::reports::UploadErrorLogTableWidget' );
    	    	$C_report->init();
    	    	$C_report->initArgs( $id_selected ,$audience_or_coupon);
    	    	$C_report->process();
    	    	$html .= "<script type='text/javascript'>".$js->getOnLoadValues()."</script>";
    	    	$html .= $C_report->render( true );
    	    	$C_report = WidgetFactory::getWidget( 'campaign::v2::reports::UploadErrorLogSummaryWidget' );
    	    	$C_report->init();
    	    	$C_report->initArgs( $id_selected ,$audience_or_coupon);
    	    	$C_report->process();
    	    	$html .= "<script type='text/javascript'>".$js->getOnLoadValues()."</script>";
    	    	$html .= $C_report->render( true );
     			break;
     			
    	    case 'summary_report' :
    	    	$start_limit = 0;
				$this->logger->debug( "start_limit is :" . $start_limit );
				$this->logger->debug( "report type :" . $report_type . "Campaign id is :" . $campaign_id );
				$C_report = WidgetFactory::getWidget( 'campaign::v2::reports::CreateSummaryHomeWidget' );
				$C_report->init();
				$C_report->initArgs( $start_limit, $campaign_id );
				$C_report->process();
				$html = "<script type='text/javascript'>" . $js->getOnLoadValues() . "</script>";
				// $html .= "<script type='text/javascript' src='/js/campaign/veneno_report_summary.js'></script>";
				$html .= $C_report->render( true );
				break;
    		
	    }
	    
// 	    $this->logger->debug( 'html : '.$html );
	    $this->data['info'] = 'success';
	    $this->data['html'] = rawurlencode( $html );
    }

    /**
    * Opening sticky list popup on click of add /remove link.
    */
    private function openStickyList( $params ){

    	$this->logger->debug('@@@Request parmas:'.print_r( $_REQUEST , true ) );
    	
    	$campaign_id = $_REQUEST['campaign_id'];
    	$group_id = $_REQUEST['group_id'];
    	$op = $_REQUEST['action'];
		
    	if( $campaign_id ){

    		$this->campaign_model->load( $campaign_id );
			if( $this->campaign_model->getOrgId() != $this->org_id ){
				$this->data['error'] = ORG_CHANGED_MESSAGE;
				$this->data['status'] = 'failed';
				return;
			}
    	}
    	
    	$widget = WidgetFactory::getWidget( 'campaign::v2::audience::AddRemoveSubscriberWidget' );
    	$widget->initArgs( $campaign_id , $group_id , $op );
    	$widget->init();
    	$widget->process();

    	$html = $widget->render(true);
    	
    	$this->data['info'] = $html;
    	$this->data['status'] = 'SUCCESS';
    }
    
    private function DownloadCampaignReport( $params ){
    	
    	$report_type = $params[0];
    	$campaign_id = $params[1];
    	
    	$down_widget = WidgetFactory::getWidget( 'campaign::v2::reports::DownloadCampaignReportsWidget' );
    	$down_widget->init();
    	
    	if ( $report_type == 'customer_list' )
    		$down_widget->initArgs( $report_type , $campaign_id , 1 , $params[2] );
    	else if ( $report_type == 'date_range' )
    		$down_widget->initArgs( $report_type , $campaign_id , 1 , $params[2] , $params[3] );
    	else	
    		$down_widget->initArgs( $report_type , $campaign_id , 1 );
    	
    	$down_widget->process();
    }
    
    private function DownloadEmailReport( $params ){
    	 
    	$report_type = $params[0];
    	$child_type = $params[1];
    	$campaign_id = $params[2];
    	 
		$down_widget = WidgetFactory::getWidget( 'campaign::v2::reports::DownloadEmailReportsWidget' );
    	$down_widget->init();
    	$down_widget->initArgs( $report_type , $child_type , $campaign_id );
    }
    
    private function FilterEmailReport( $params ){
    	
    	global $js;
    	
    	$campaign_id = $params[0];
    	$type = $params[1];
    	
    	$this->logger->debug('@@type '.$type.'.  Campaign id '.$campaign_id );
    	
    	if( $type == 'list' || $type == 'date' ){
    		
	    	$open_rate = WidgetFactory::getWidget( 'campaign::v2::reports::EmailOpenRateReportWidget' );
	    	$open_rate->init();
	    	if( $type == 'list' ){
	    		$open_rate->initArgs( $campaign_id , $params[2] );
	    	}
	    	if( $type == 'date' ){
	    		$open_rate->initArgs( $campaign_id , false , $params[2] , $params[3] );
	    	}
	    	$open_rate->process();
	    	
	    	$script = "<script type='text/javascript'>".$js->getOnLoadValues()."</script>";
	    	$html = $open_rate->render(true);
	    	$html = $script.$html;
	    	
	    	$this->data['info'] = 'success';
	    	$this->logger->debug( '@@html : '.$html );
	    	$this->data['html'] = rawurlencode( $html );
    	}
    	else if ( $type == 'user' || $type == 'per_user' ){
			    			
    		$click_rate = WidgetFactory::getWidget( 'campaign::v2::reports::EmailClickRateReportWidget' );
    		$click_rate->init();
   			$click_rate->initArgs( $campaign_id , $type );
    		$click_rate->process();
    		$html .= $click_rate->render(true);
    		
    		$this->data['info'] = 'success';
    		$this->logger->debug( '@@html : '.$html );
    		$this->data['html'] = rawurlencode( $html );
    	}
    }
    	    
    private function getEmailReports( $campaign_id , $report_type , $start_date = '' , $end_date = '' , $roi_name = '' , $interval = 5 ){
    	
    	$this->logger->debug( '@@Email Reports' );
    	
    	include_once 'conquest/CampaignROI/embedded/impl/EmbeddedROIReportsProvider.php';
    	include_once 'conquest/CampaignROI/embedded/api/ROIReportsTypes.php';
    	
    	$this->C_campaign_controller->load($campaign_id);
    	$campaign_name = $this->C_campaign_controller->campaign_model_extension->getName();

    	if ( $start_date == '' ){
    		$start_date = $this->C_campaign_controller->campaign_model_extension->getStartDate();
    		$start_date = new DateTime( $start_date );
    		$start_date = $start_date->format( 'Y-m-d' );
    	}
    	if ( $end_date == '' ){
    		$end_date = $this->C_campaign_controller->campaign_model_extension->getEndDate();
    		$end_date = new DateTime( $end_date );
    		$end_date = $end_date->format( 'Y-m-d' );
    	}

    	$vocher_id = $this->C_campaign_controller->campaign_model_extension->getVoucherSeriesId();
    	$this->C_couponSeriesManager->loadById( $vocher_id );
    	$details = $this->C_couponSeriesManager->getDetails();
    	$voucher_desc = $details['description'];
    	
    	$C_embedded_roi_reports = new EmbeddedROIReportsProvider();
    	$this->logger->debug( '@@fetching roi report : '.$start_date.'  '.$end_date );
    	
    	switch ( $report_type ){
			    		
    		case 'common_roi' : 
    				$params = array( 'camp_name' => $roi_name , 'start_date' => $start_date , 'end_date' => $end_date );
    				$url = $C_embedded_roi_reports->getReport( ROIReportsTypes::$OVERALL , $params );
    				break;
    		case 'test_control' :
	    			$params = array( 'camp_name' => $roi_name , 'start_date' => $start_date , 'end_date' => $end_date );
	    			$url = $C_embedded_roi_reports->getReport( ROIReportsTypes::$TEST_CONTROL , $params );
    				break;
    		case 'voucher_summary' :
    				$params = array( 'voucher_desc' => $voucher_desc , 'voucher_id' => $vocher_id , 
    																'start_date' => $start_date , 'end_date' => $end_date );
	    			$url = $C_embedded_roi_reports->getReport( ROIReportsTypes::$VOUCHER_SUMMARY , $params );
    				break;
    		case 'voucher_summary_dvs' :
	    			$params = array( 'voucher_desc' => $voucher_desc , 'voucher_id' => $vocher_id , 
	    							'int_cnt'  => $interval , 'start_date' => $start_date , 'end_date' => $end_date );
	    			$url = $C_embedded_roi_reports->getReport( ROIReportsTypes::$VOUCHER_SUMMARY_DVS , $params );
    				break;
    		case 'sms_channel' :
    				$params = array( 'camp_name' => $roi_name , 'start_date' => $start_date , 'end_date' => $end_date );
    				$url = $C_embedded_roi_reports->getReport( ROIReportsTypes::$SMS_CHANNEL , $params );
    				break;
    		case 'email_channel' :
    				$params = array( 'camp_name' => $roi_name , 'start_date' => $start_date , 'end_date' => $end_date );
    				$url = $C_embedded_roi_reports->getReport( ROIReportsTypes::$EMAIL_CHANNEL , $params );
    				break;
    	}
    	
    	$this->data['info_url'] = $url;
    	
    	$this->logger->debug( '@@Finish Email Reports' );
    }
    /*
     * @deprecated
     * 
     */
    public function roiReport( $params ){
    	
    	include_once "business_controller/campaigns/reports/embedded/impl/EmbeddedROIReportsProvider.php" ;
    	include_once 'business_controller/campaigns/reports/embedded/api/ROIReportsTypes.php';
    	include_once 'base_model/campaigns/class.CampaignBase.php' ;

    	$report_type = $params[0];
    	$campaign_id = $params[1];
    	
    	$this->logger->debug('@@report type '.$report_type.'. Campaign id '.$campaign_id );
    	//$tagReplaceParams is the array containing params to replace the tag
    	$campObj =  new CampaignBaseModel() ;
    	$campObj->load($campaign_id) ;
    	$campName = $campObj->getName() ;
    	$tagReplaceParams = array('camp_name'=>$campName);
    	$embdReprtObj = new EmbeddedROIReportsProvider() ;
    	$iframeSrc = $embdReprtObj->getReport(ROIReportsTypes::$EMBEDDED,$tagReplaceParams) ;

    	$this->logger->debug("campaignajaxservice : iframe source ".$iframeSrc) ;
    	$this->data['info'] = 'success';
    	$this->data['url'] =  $iframeSrc ;
    }
    /*
     * @deprecated
     */

    public function deliveryReport( $params ){
    	
    	include_once "business_controller/campaigns/reports/embedded/impl/EmbeddedROIReportsProvider.php" ;
    	include_once 'business_controller/campaigns/reports/embedded/api/ROIReportsTypes.php';
    	include_once 'base_model/campaigns/class.CampaignBase.php' ;

    	$report_type = $params[0];
    	$campaign_id = $params[1];
    	
    	$this->logger->debug('@@report type '.$report_type.'. Campaign id '.$campaign_id );
    	//$tagReplaceParams is the array containing params to replace the tag
    	$campObj =  new CampaignBaseModel() ;
    	$campObj->load($campaign_id) ;
    	$campName = $campObj->getName() ;
    	$tagReplaceParams = array('camp_name'=>$campName);
    	$embdReprtObj = new EmbeddedROIReportsProvider() ;
    	$iframeSrc = $embdReprtObj->getReport(ROIReportsTypes::$DELIVERY,$tagReplaceParams) ;

    	$this->logger->debug("campaignajaxservice : iframe source ".$iframeSrc) ;
    	$this->data['info'] = 'success';
    	$this->data['url'] =  $iframeSrc ;
    }
    
    public function getEmbeddedReport( $params ){
    	
    	include_once "business_controller/campaigns/reports/embedded/impl/EmbeddedROIReportsProvider.php" ;
    	include_once 'business_controller/campaigns/reports/embedded/api/ROIReportsTypes.php';
    	include_once 'base_model/campaigns/class.CampaignBase.php' ;

    	$report_type = $params[0];
    	$campaign_id = $params[1];
    	
    	$this->logger->debug('@@report type '.$report_type.'. Campaign id '.$campaign_id );
    	//$tagReplaceParams is the array containing params to replace the tag
    	$campObj =  new CampaignBaseModel() ;
    	$campObj->load($campaign_id) ;
    	$campName = $campObj->getName() ;
    	$embdReprtObj = new EmbeddedROIReportsProvider();
    	
    	switch ($report_type){
    		case 'delivery_report':
    			$tagReplaceParams = array('camp_name'=>$campName);
    			$iframeSrc = $embdReprtObj->getReport( ROIReportsTypes::$DELIVERY, $tagReplaceParams );
    			break;
    		case 'performance_report':
    			$tagReplaceParams = array('camp_id'=>$campaign_id,'camp_name'=>$campName);
    			$iframeSrc = $embdReprtObj->getReport( ROIReportsTypes::$PERFORMANCE,$tagReplaceParams );
    			break;
    		case 'roi_report':
    			$tagReplaceParams = array('camp_name'=>$campName);
    			$iframeSrc = $embdReprtObj->getReport( ROIReportsTypes::$EMBEDDED, $tagReplaceParams );
    			break;
    			
    	}
    	
    	$this->logger->debug("campaignajaxservice : iframe source ".$iframeSrc) ;
    	$this->data['info'] = 'success';
    	$this->data['url'] =  $iframeSrc ;
    }
    
    private function getEmailOverview( $params ){

    	$campaign_id = $params[0];
    	$message_id = $params[1];
    	
    	$this->logger->debug( 'redrawing overview widget for message id : '.$message_id );
    	
    	$C_overview = WidgetFactory::getWidget( 'campaign::v2::reports::CampaignOverviewWidget' );
    	$C_overview->init();
    	$C_overview->initArgs( $campaign_id , $message_id );
		$C_overview->process();
		$html = $C_overview->render( true );
		$this->logger->debug( '@@html: '.print_r( $html , true ));
		$this->data['html'] = rawurlencode( $html );
		$this->data['info'] = 'success';
    }
    
    private function getCampaignRunningDetails(){
    	
    	$campaign_type = $_POST['campaign_type'];
    	$this->logger->debug( 'getting live campaign and lapsed campaign count : ' );
    	$result = $this->C_campaign_controller->getCampaignRunningStatus( $campaign_type );
    	$this->logger->debug( 'getting live campaign and lapsed campaign count : '. print_r( $result, true ));
    	
    	$this->data['active'] = $result['active'] != null ? $result['active'] : 0 ;
    	$this->data['inactive'] = $result['inactive'] != null ? $result['inactive'] :0 ; 
    	$this->data['forthcoming'] = $result['forthcoming'] != null ? $result['forthcoming'] :0 ;
    }
    
    private function getCustomerTasks( $params ){
    	
    	global $js;
    	$campaign_id = $params[0];
    	
    	$C_task = WidgetFactory::getWidget( 'campaign::v2::reports::CustomerTaskSelectionWidget' );
    	$C_task->init();
    	$C_task->initArgs( $campaign_id );
    	$C_task->process();
    	
    	if( $C_task->isDataAvailable() ){
    		$html = "<script type='text/javascript'>".$js->getOnLoadValues()."</script>";
    		$html .= $C_task->render( true );
    	}else{
    		$html = "<h3 class='head-report'>"._campaign("Customer Task Report")."</h3>
    				<h4 class='empty-msg'>"._campaign("No report available for this campaign")."</h4>";
    	}
    	
    	$this->logger->debug( 'html : '.$html );
    	$this->data['info'] = 'success';
    	$this->data['html'] = rawurlencode( $html );
    }
    
    private function filterCustomerTask( $params ){
    	
    	global $js,$prefix,$js_version,$css_version;
    	$task_id = $params[0];
    	 
    	$C_filter = WidgetFactory::getWidget( 'campaign::v2::reports::TaskEntriesSelectionWidget' );
    	$C_filter->init();
    	$C_filter->initArgs( $task_id );
    	$C_filter->process();
    	$html = "<script type='text/javascript'>".$js->getOnLoadValues()."</script>";
    	$html .= "<script type='text/javascript' src='$prefix/js/on_off_button.js$js_version'></script>";
    	$html .= "<link rel='stylesheet' href='$prefix/style/css/campaign.css$css_version' type='text/css' />";
    	$html .= $C_filter->render( true );
    	$this->logger->debug( 'task entries html : '.$html );
    	$this->data['info'] = 'success';
    	$this->data['html'] = rawurlencode( $html ); 
    }
    
    private function viewCustomerTaskEntries(){
    	
    	global $js;
    	$task_fields = $_GET['attrs'];
		$task_fields = json_decode( $task_fields , true );
		$this->logger->debug( 'customer task process values '.print_r( $task_fields , true ) );
    	
    	$C_filter = WidgetFactory::getWidget( 'campaign::v2::reports::ViewTaskEntriesWidget' );
    	$C_filter->init();
    	$C_filter->initArgs( $task_fields );
    	$C_filter->process();
    	
    	$html = "<script type='text/javascript'>".$js->getOnLoadValues()."</script>";
    	$html .= $C_filter->render( true );
    	$this->logger->debug( 'task entries html : '.$html );
    	
    	$html .= '<button id="modify_task_filter" class="btn btn-warning modify-filter">'._campaign("Change filter").'</button>';
    	$this->data['info'] = 'success';
    	$this->data['html'] = rawurlencode( $html );
    	
    }
    
    private function getFTFAndViewBrowserHtml( $html_content ){
    	
    	global $campaign_cfg;
    	
    	$view_url = "{{domain}}/business_controller/campaigns/emails/links/view.php?utrack={{user_id_b64}}&mtrack={{outbox_id_b64}}";
    	
    	$view_url = Util::templateReplace( $view_url , array('domain'=>$campaign_cfg['track-url-prefix']['view-in-browser'] ) );
    	
    	//check if forward to friend is enabled or not
    	$is_ftf_enabled = $this->C_config_mgr->getKey( "CONF_CAMPAIGN_FTF_ENABLED" );

    	//Disabling Forward to Friend for now for some security reasons
		$is_ftf_enabled = false;
		
    	if( $is_ftf_enabled ){
    		$frwd_token_key = base64_encode( base64_encode('{{frwd_tok}}___ftf_name') );
    		$ftf_url = "{{domain}}/business_controller/campaigns/emails/links/view.php?utrack={{user_id_b64}}&mtrack={{outbox_id_b64}}&ftf=true&frwd_tok=$frwd_token_key";
    		$ftf_url = Util::templateReplace( $ftf_url , array('domain'=>$campaign_cfg['track-url-prefix']['view-in-browser'] ) );
    	}
    		
    	$style = $is_ftf_enabled ? 'style = "margin-top:3em;"' : 'style = ""';
    		
    	$is_view_enabled = $this->C_config_mgr->getKey( "CONF_CAMPAIGN_VIEW_IN_BROWSER_ENABLED" );
    	
    	if( $is_view_enabled ){

    		$view_in_browser_msg = '<center '.$style.'>
										'._campaign("If you have difficulties viewing this mail, click").'
										<a href="'.$view_url.'" style = "text-decoration: underline;color: #369;" target="_blank">'._campaign("here").'</a><br/>
									</center>';
    		
    		$html_content = $view_in_browser_msg.$html_content;
    	}
    		
    	if( $is_ftf_enabled ){
    	
    		$ftf_in_browser_msg = '<center><a href="'.$ftf_url.'" style = "text-decoration: underline;color: green;" target="_blank">'._campaign("Forward to a Friend!").'</a></center><br/>';
    		$html_content .= $ftf_in_browser_msg;
    	}
    	
    	return $html_content;
    }
    
    /**
     * Getting message html for preview in message table on view button click.
     */
    public function getMessageForPreviewById(){
    	
    	$this->logger->debug('@@@MESSAGE_ID:-'.print_r($_GET,true));
    	
    	$message_id = $_GET['message_id'];

    	$message_details = $this->C_campaign_controller->getDefaultValuesbyMessageId( $message_id );

    	$this->data['secondary_templates'] = array() ;
    	if( !$message_details )
    			$message = _campaign("No Preview Available");
    	else {
    		$message = rawurlencode( $message_details['message'] );
    		if(!empty($message_details['secondary_templates'])){
    			foreach ($message_details['secondary_templates'] as $key => $value) {
    				$this->data['languages'][$key]['lang_name'] = $value['language_name'] ;
    				$this->data['languages'][$key]['is_base_template'] = 0 ;
    				if($value['is_base_template']){
    					$this->data['languages'][$key]['is_base_template'] = 1 ;
    					continue ;
    				}
    				$message_details['secondary_templates'][$key]['msg_body']= rawurlencode($value['msg_body'])  ;		    		
	    		}			    		
    		}    		
    	}
    	if($message_details['msg_type'] == 'WECHAT_MULTI_TEMPLATE') {
			$C_template = new Template();
			$message_details['singlePicTemplates'] = array();
			if($message_details['TemplateIds']) {
				$templateIds = explode(',', $message_details['TemplateIds']);
				foreach($templateIds as $templateId) {
					$this->logger->debug("@@templateId : ". $templateId );
					$C_template->load( $templateId );
					$singlePicData = json_decode( $C_template->getFileServiceParams() , true );
			 		array_push($message_details['singlePicTemplates'], $singlePicData);
				}
			}
    	}
    	if($message_details['msg_type'] == 'WECHAT_TEMPLATE') {
    		$C_template = new Template();
    		$C_template->load( $message_details['template_id'] );
    		$wechat_fileserviceparams = json_decode( $C_template->getFileServiceParams() , true );
    		$message_details['templateData'] = $wechat_fileserviceparams;
    	}
		if($message_details['msg_type'] == 'WECHAT_SINGLE_TEMPLATE') {
    		$C_template = new Template();
    		$C_template->load( $message_details['template_id'] );
			$singlePicData = json_decode( $C_template->getFileServiceParams() , true );
			$message_details['templateData'] = $singlePicData;
    	}
    	$this->data['default_arguments'] = $message_details;
    	$this->data['secondary_templates'] = $message_details['secondary_templates'] ;
    	$this->data['html'] = $message;
    }
    
    /**
     * 
     * Enter description here ...
     */
    private function processCallTaskSelectMessage(){

    	global $prefix,$js,$js_version,$campaign_cfg;
    	
    	$campaign_id = $_REQUEST['campaign_id'];
    	$message_id = $_REQUEST['message_id'];
    	
    	$this->logger->debug( '@@@Call Task Select Message Start' );
    	
    	$urlCreator = new UrlCreator();
    	$urlCreator->setNameSpace( 'campaign/v2/messages' );
    	$urlCreator->setPage( 'CallTask' );
    	$urlCreator->setPageParams( array( 'msg_id' => $message_id , 'campaign_id' => $campaign_id ) );
    	$url = $urlCreator->generateUrl();
    		
    	$callTaskMsg = WidgetFactory::getWidget( 'campaign::v2::tasks::CallTaskMessageWidget' );
    	$callTaskMsg->initArgs( $campaign_id, $message_id, $url );
    	$callTaskMsg->init();
    	$callTaskMsg->process();
    	
    	$this->logger->debug( '@@@Call Task Select Message Finish' );
    	
    	if( $this->data['info'] == 'success' ){
    	
    		$data_params = $this->data['data_params'];
    			
    		$delivWidget = WidgetFactory::getWidget( 'campaign::v2::tasks::CallTaskWidget' );
    		$delivWidget->initArgs( $campaign_id, $message_id, $url );
    		$delivWidget->init();
    		$delivWidget->process();
    			
    		$html .= $this->getScriptsHtml();
    			
    		$html .= $delivWidget->render( true );
    			
    		$this->data['group_html'] = rawurlencode( $html );
    	}
    	
    	$this->logger->debug( '@@@processCallTaskSelectMessage Finish' );
    }
    
    private function processCallTaskGroupSelect(){

    	global $prefix,$js,$js_version,$campaign_cfg;
    	
    	$campaign_id = $_REQUEST['campaign_id'];
    	$message_id = $_REQUEST['message_id'];
    	
    	$urlCreator = new UrlCreator();
    	$urlCreator->setNameSpace( 'campaign/v2/messages' );
    	$urlCreator->setPage( 'CallTask' );
    	$urlCreator->setPageParams( array( 'msg_id' => $message_id , 'campaign_id' => $campaign_id ) );
    	$url = $urlCreator->generateUrl();
    	
    	$this->logger->debug( '@@@Call Task Group Selection Start' );
    	
    	$delivWidget = WidgetFactory::getWidget( 'campaign::v2::tasks::CallTaskWidget' );
    	$delivWidget->initArgs( $campaign_id, $message_id, $url );
    	$delivWidget->init();
    	$delivWidget->process();
    	
    	$this->logger->debug( '@@@Call Task Group Selection Finish' );
    	
    	if( $this->data['info'] == 'success' ){
    	
    		$data_params = $this->data['data_params'];
    			
    		$reviewAndSendWidget = WidgetFactory::getWidget( 'campaign::v2::tasks::CallTaskReviewAndSendWidget' );
    		$reviewAndSendWidget->initArgs( $campaign_id, $message_id, $url );
    		$reviewAndSendWidget->init();
    		$reviewAndSendWidget->process();
    			
    		$html .= $this->getScriptsHtml();
    			
    		$html .= $reviewAndSendWidget->render( true );
    			
    		$this->data['review_send'] = rawurlencode( $html );
    	}
    	
    	$this->logger->debug( '@@@Preview And Send Finish' );
    }
    
    /**
     *
     * It will process and save the configured email setttings
     */
    private function processQueueCallTask(){
    
    	$campaign_id = $_REQUEST['campaign_id'];
    	$message_id = $_REQUEST['message_id'];

    	$urlCreator = new UrlCreator();
    	$urlCreator->setNameSpace( 'campaign/v2/messages' );
    	$urlCreator->setPage( 'CallTask' );
    	$urlCreator->setPageParams( array( 'msg_id' => $message_id , 'campaign_id' => $campaign_id ) );
    	$url = $urlCreator->generateUrl();
    	
    	$reviewAndSendWidget = WidgetFactory::getWidget( 'campaign::v2::tasks::CallTaskReviewAndSendWidget' );
    	$reviewAndSendWidget->initArgs( $campaign_id, $message_id, $url );
    	$reviewAndSendWidget->init();
    	$reviewAndSendWidget->process();
    	
    	if( $this->data['info'] == 'success' ){

    		$callTaskMsg = WidgetFactory::getWidget( 'campaign::v2::tasks::CallTaskMessageWidget' );
    		$callTaskMsg->initArgs( $campaign_id, false, $url );
    		$callTaskMsg->init();
    		$callTaskMsg->process();
    		$html = $this->getScriptsHtml();
    		$html .= $callTaskMsg->render( true );
    		$this->data['message_selection'] = $html; 
    	}
    
    	$this->logger->debug( '@@@Call Task Review And Send Process Finish' );
    }
    
    private function renderCallTaskForEdit( $params ){
    	
    	global $prefix,$js,$js_version;
    	
    	$this->logger->debug( '@@@Render Edit Process Start Params : '.print_r( $params , true ) );
    	
    	$campaign_id = $params[0];
    	$msg_id = $params[1];
    	
    	$urlCreator = new UrlCreator();
    	$urlCreator->setNameSpace( 'campaign/v2/messages' );
    	$urlCreator->setPage( 'CallTask' );
    	$urlCreator->setPageParams( array( 'msg_id' => $msg_id , 'campaign_id' => $campaign_id ) );
    	$url = $urlCreator->generateUrl();
    	
    	$C_message = new BulkMessage();
    	$C_message->load( $msg_id );
    	
    	$campaign_id = $C_message->getCampaignId();
    	$this->C_campaign_controller->load( $campaign_id );
    	$org_id = $this->C_campaign_controller->campaign_model_extension->getOrgId();
    	
    	if( $this->org_id != $org_id ){
    		$this->data['error'] = _campaign("You have changed the organization in another tab. Please refresh the page to reflect the changes.");
    		return;
    	}
    	
    	if( $C_message->getStatus() == 'SENT' ){
    		$this->data['error'] = _campaign("Call Task is already sent. You are not allowed to edit this call task after sending.");
    		return;
    	}
    	
    	//Step 1 : Render EditMsgWidget
    	$callTaskMsg = WidgetFactory::getWidget( 'campaign::v2::tasks::CallTaskMessageWidget' );
    	$callTaskMsg->initArgs( $campaign_id, $msg_id, $url );
    	$callTaskMsg->init();
    	$callTaskMsg->process();
    	
    	$html = $this->getScriptsHtml();
    	$html .= $callTaskMsg->render( true );
    	
    	$this->logger->debug('@@@Process Edit HTML@@@ '.$html);
    	
    	$this->data['info_edit_msg'] = $html;
    	
    	//Step 2 : Render Delivery Intructions
    	$html = '';
    	$this->logger->info('@@@After Edit Message Rendering' );
    	$delivWidget = WidgetFactory::getWidget( 'campaign::v2::tasks::CallTaskWidget' );
    	$delivWidget->initArgs( $campaign_id, $msg_id, $url );
    	$delivWidget->init();
    	$delivWidget->process();
    	
    	$html .= $this->getScriptsHtml();
    	$html .= $callTaskMsg->render( true );
    	
    	$html .= "
    	<script type='text/javascript'>
    	
					$('#call_task_delivery__cron_day').closest('tr').hide()
			
					$('#call_task_delivery__send_when').change(function(){
						var value = $('#call_task_delivery__send_when').val();
    	
						if( value == 'PARTICULAR_DATE' ){
							 $('#call_task_delivery__minutes').closest('tr').show();
						}else{
							$('#call_task_delivery__minutes').closest('tr').hide();
						}
					});
			
					$('#call_task_delivery__send_when').trigger('change');
			
					$('#call_task_delivery__send_when').change(function(){
						var value = $('#call_task_delivery__send_when').val();
    	
						if( value == 'SCHEDULE' ){
							 $('#call_task_delivery__cron_day').closest('tr').show();
				
						}else{
							$('#call_task_delivery__cron_day').closest('tr').hide();
						}
					});
					$('#call_task_delivery__send_when').trigger('change');
			
					</script>
				";
    	
		$html .= $delivWidget->render( true );
    	
    	$this->data['info_delivery'] = $html;
    	
    	$this->logger->debug( '@@@Render Edit Process Finish' );
    }
    
    
	public function downloadSentReport() {
		
		$message_id = $_GET ['msg_id'];
		$org_level_report = $_GET['org_level_report'];
		
		$this->logger->debug( "In Download Users Report message Id :" . $message_id .
    			"org level report is " .$org_level_report );
		
		$queue_rtn = $this->C_campaign_controller->queueCampaignUserReport( $message_id,
					 $org_level_report );
		
		if ( $queue_rtn == -1 ) 
			$this->data ['error']=_campaign("Could not download report,system-issue contact capillary.");
		else if ( $queue_rtn == 1 ) {
			$this->data ['msg'] = _campaign("The report is successfully queued.The notification will be sent to your email id once done");
		} else 
			$this->data ['error'] = _campaign("Error occurred while queuing report.");
	}
    
	public function summaryReportChangePage() {
		
		$limit = $_GET ['start_limit'];
		$campaign_id = $_GET ['campaign_id'];
		$start_date = $_GET['start_date'];
		$end_date = $_GET['end_date'];
		$this->logger->debug( "start_limit is :" . $limit );
		$this->logger->debug( "@@@ Campaign id is :" . $campaign_id ."\t start_range" .$start_date ."\tend_date" .$end_date );
		
		$C_report = WidgetFactory::getWidget( 'campaign::v2::reports::CreateSummaryHomeWidget' );
		$C_report->init();
		if( $start_date == -1 ) {
			$this->logger->debug( "@@@ start date is -1" );
			$C_report->initArgs( $limit, $campaign_id );
		} else	
			$C_report->initArgs( $limit, $campaign_id, '', '', '', $start_date, $end_date );
		$C_report->process();
		$html = $this->getScriptsHtml();
		$html .= $C_report->render( true );
// 		$this->logger->debug( '@@@HMTL:-' . $html );
		$this->data ['html'] = $html;
	}
	
	public function summaryReportCampaignId() {
		
		$this->logger->debug( "In Summary Report By Campaign Id" );
		$start_limit = 0;
		$campaign_id = $_GET ['campaign_id'];
		$start_date = $_GET ['start_date'];
		$end_date = $_GET ['end_date'];
		$this->logger->debug( "@@@startDate " .$start_date. " end date" .$end_date );
		$this->logger->debug( "start_limit is :" . $start_limit );
		$this->logger->debug( "report type :" . $report_type . "Campaign id is :" . $campaign_id );
		$C_report = WidgetFactory::getWidget( 'campaign::v2::reports::CreateSummaryHomeWidget' );
		$C_report->init();
		$C_report->initArgs( $start_limit, $campaign_id, '', '', '', $start_date, $end_date );
		$C_report->process();
		$html = $this->getScriptsHtml();
		$html .= $C_report->render( true );
		//$this->logger->debug( "@@@HTML is :" . $html );
		$this->data ['html'] = $html;
	}
	
	public function bulkDateRangeReport() {
		$this->logger->debug( "In Bulk Summary Report By Campaign Id" );
		$start_limit = 0;
		$this->logger->debug( "start_limit is :" . $start_limit );
		$this->logger->debug( "report type :" . $report_type . "Campaign id is :" . $campaign_id );
		$C_report = WidgetFactory::getWidget( 'campaign::v2::reports::CreateSummaryHomeWidget' );
		$C_report->init();
		$C_report->initArgs( $start_limit, '', '', '','', $_POST ['startDate'], $_POST ['endDate'] );
		$C_report->process();
		$html = $this->getScriptsHtml();
		$html .= $C_report->render( true );
// 		$this->logger->debug( "HTML is :" . $html );
		$this->data ['html'] = $html;
	}
	
	
	public function bulkReportAutoRefresh() {
		
		Auth::session_force_end();
		
		$this->logger->debug( "In bulkReportRefresh" );
		$message_id = $_GET ['message_id'];
		$limit = 1;
		$campaign_id = 1;
		$total_msg = $_GET ['total_msg'];
		$campaign_name_flag = $_GET ['campaign_name_flag'];
		$C_report = WidgetFactory::getWidget( 'campaign::v2::reports::CreateSummaryHomeWidget' );
		$C_report->init();
		$C_report->initArgs( $limit, $campaign_id, $message_id, $total_msg, $campaign_name_flag );
		$C_report->process();
		//$html = $this->getScriptsHtml();
		$html = $C_report->render( true );
		$this->logger->debug( '@@@HMTL::-' . $html );
		$this->data ['html'] = $html;
	}
    
    
    private function getJSForProcessEditMsg(){
    	
    	$html = "
						<script type='text/javascript'>
		
							$('#delivery__cron_day').closest('tr').hide()
    	
							$('#delivery__send_when').change(function(){
								var value = $('#delivery__send_when').val();
		
								if( value == 'PARTICULAR_DATE' ){
									 $('#delivery__minutes').closest('tr').show();
								}else{
									$('#delivery__minutes').closest('tr').hide();
								}
							});
    	
							$('#delivery__send_when').trigger('change');
    	
							$('#delivery__send_when').change(function(){
								var value = $('#delivery__send_when').val();
		
								if( value == 'SCHEDULE' ){
									 $('#delivery__cron_day').closest('tr').show();
			
								}else{
									$('#delivery__cron_day').closest('tr').hide();
								}
							});
							$('#delivery__send_when').trigger('change');
    	
							$('.pencil_footer').click(function(){
		
								$('#delivery__signature_value').removeAttr('readonly');
								$('.save_footer').removeClass('hide');
								$('.pencil_footer').addClass('hide');
							});
    	
							$('.save_footer').click(function(){
		
								$('#delivery__signature_value').attr('readonly','readonly');
								$('.save_footer').addClass('hide');
								$('.pencil_footer').removeClass('hide');
							});
						</script>
						";
    	
    	return $html;
    }
}
?>