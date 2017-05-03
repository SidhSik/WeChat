<?php 
include_once 'creative_assets/CreativeAssetsManager.php';
include_once 'creative_assets/controller/ModuleWiseTagsProvider.php';
include_once 'creative_assets/EdmManager.php';
include_once 'business_controller/wechat/WeChatAccountController.php';
include_once 'helper/wechat/class.QxunApiHelper.php';

class CreativeAssestsAjaxService extends BaseAjaxService{
	
	private $C_assets;
	public function __construct( $type, $params = null ){
	
		global $url_version, $currentorg;
	
		parent::__construct( $type, $params );
	
		$url_version = '1.0.0.1';
	
		//To load the cheetah's organizational model extension
		$org_id = $currentorg->org_id;
		$currentorg = new OrganizationModelExtension();
		$currentorg->load( $org_id );
	}
	
	public function process(){
		$this->logger->debug( '@@@Checking For Type : ' . $this->type );

		switch ( $this->type ){
			
			case 'get_html_thumbnail':
				$this->logger->debug('@@@Inside get_html_thumbnail');
				$this->getThumbnailContent();
				break;
			
			case 'msg_preview':
				$this->logger->debug('@@@Inside Message Preview');
				$this->getMessagePreview();
				break;
				
			case 'delete_template':
				
				 $this->logger->debug('@@@inside delete template');
				 $this->deleteTemplate();
				 break;

			case 'copy' :
				$this->logger->debug('@@@inside copy to particular reference');
				$this->copyToCouponSeries();
				break;
				
			case 'make_general':
				
				$this->logger->debug('@@@Inside Make general');
				$this->makeGeneral();
				break;
				
			case 'reload_assets_home':
				 $this->logger->debug('@@@Inside Reload assets homre with reference id');
				 $this->reloadAssetsHome();
				 break;
				 
			case 'channel_set':
				 $this->logger->debug('@@@Inside Channel set');
				 $this->setChannel();
				 break;
			
			case 'channel_unset':
				 $this->logger->debug('@@@Inside Channel unset');
				 $this->unsetChannel();
				 break;

			case 'get_default_channel':
				$this->logger->debug('@@@Get Default channel template');
				$this->getDefaultTemplate();
				break;

			case 'reload_image':
				$this->logger->debug('@@@Get reload image widget');
				$this->reloadImageWidget();
				break;

			case 'upload_img':
                   $this->logger->debug('@@@Image Upload');
                   $this->UploadImage();
                   break;

           case 'edit_template':
                   $this->logger->debug('@@@Edit Template Widget');
                   $this->editTemplateRender();
                   break;

           case 'create_edit_template':
                   $this->logger->debug('@@@Create Or Edit Template Process Widget');
                   $this->CreateOrEditTemplate();
                   break;

           case 'reload_email_home':
                   $this->logger->debug('@@@Reload Email Home Widget');
                   $this->reloadEmailHome();
                   break;

           case 'get_tags_list':
               	   $this->logger->debug('@@@Reload Email Home Widget');
                   $this->getTagList($this->params);
                   break;

			case 'upload_using_iframe':
				$this->logger->debug('@@@Inside Get Zip Upload Html');
				$this->zipUploadSubmit();
				break;

			case 'show_more':
				$this->logger->debug( '@@show more:'.print_r( $_REQUEST , true ) );
				$this->showMoreTemplates( $_REQUEST );
				break;

			case 'get_html_templates':
				$this->logger->debug('@@show more:'.print_r( $_REQUEST , true ));
				$this->getHtmlTemplates();
				break;

			case 'get_default_html_templates':
				$this->getDefaultTemplates();
				$this->logger->debug('');
				break;

			case 'get_wechat_content_template':
				$this->logger->debug('Create Wechat mapping templates');
				$this->getWeChatContentTemplates();
				break;

			case 'get_wechat_dvs_tags':
				$this->logger->debug('Get Wechat Dvs tags');
				$this->getWeChatDvsTags();
				break;

			case 'get_wechat_loyalty_tags':
				$this->logger->debug('Get Wechat Loyalty tags');
				$this->getWeChatLoyaltyTags();
				break;

			case 'get_wechat_outbound_tags':
				$this->logger->debug('Get Wechat Outbound tags');
				$this->getWeChatOutboundTags();
				break;

			case 'get_all_mobile_push_templates':
				$this->logger->debug('get all mobile push templates');
			    $this->getAllMobilePushTemplates();
			    break; 
			case 'get_mobile_push_templates_by_accountid':
				  $this->logger->debug('get all mobile push templates by accountId');
				  $account_id = $_GET['account_id'];

			    $this->getAllMobilePushTemplates($account_id);
			    break;	
    

			case 'get_all_wechat_templates':
			    $this->getAllWeChatTemplates();
			    $this->logger->debug('get all wechat templates');
			    break;

			case 'get_wechat_content_by_org':
				$this->getWeChatContentByOrg();
				$this->logger->debug('get all wechat org content');
				break;

		    case 'get_all_wechat_single_image_templates':
			    $this->getAllWeChatSingleImageTemplates();
			    $this->logger->debug('get all wechat Single Image templates');
			    break;

			case 'get_all_wechat_multi_image_templates':
			    $this->getAllWeChatMultiImageTemplates();
			    $this->logger->debug('get all wechat Multi Image templates');
			    break;

			case 'get_wechat_content':
				$this->getWeChatContent();
				$this->logger->debug('get wechat Template function called');
				break;

			case 'save_wechat_template':
				$this->logger->debug('Save wechat Template function called');
				$this->saveWeChatTemplate();
				break;	
			case 'save_mobile_push_template':
				$this->logger->debug('Save mobile push Template function called');
				$this->saveMobilePushTemplate();
				break;	

			case 'save_html_template':
				$this->saveHTMLTemplate();
				$this->logger->debug('Save Template function called');
				break;

			case 'set_favourite_template':
				$this->setFavouriteTemplate();
				$this->logger->debug('');
				break;

			case 'delete_html_template':
				$this->deleteHTMLTemplate();
				$this->logger->debug('');
				break;

			case 'get_html_content':
				$this->getHtmlContent();
				$this->logger->debug('');
				break;

			case 'upload_image':
				$this->uploadImageTemplate();
				break;

			case 'get_edm_token':
				$this->getEdmToken();
				$this->logger->debug('');
				break;

			case 'get_all_tags':
				$this->getAllTags();
				break;

			case 'initial_creative_data':
				$this->initialCreativeAssetsData();
				break;

			case 'initial_social_data':
				$this->initialSocialData();
				break;
			case 'initial_mobile_push_data':
				$this->initialMobilePushData();
				break;			
			case 'get_default_template_id':
				$this->getDefaultTemplateId();
				break;

			case 'get_multi_lang_template':
				$this->getSecondaryTemplates();
				break;

			case 'save_single_image_broadcast':
				$this->processSingleImageBroadcastTemplate();
				break;

			case 'save_multi_image_broadcast':
				$this->processMultiImageBroadcastTemplate();
				break;

			case 'get_wechat_templates':
				$this->logger->debug('@@show more:'.print_r( $_REQUEST , true ));
				$this->getWeChatTemplatesForDisplay();
				break;

			case 'save_sms_template':
				$this->logger->debug('@@save sms template:'.print_r( $_REQUEST , true ));
				$this->saveSMSTemplate();
				break;
			case 'get_secondary_ios_cta':
				$this->logger->debug('@@get secondary_template_ios_cta:'.print_r( $_GET['mobile_push_account'], true ));
				 $mobile_push_account = $_GET['mobile_push_account'];
				$this->getSecondaryIOS($mobile_push_account);
				break;		

			default:
				$this->logger->debug('@@@Invalida type passed');
		}
	}

	function processMultiImageBroadcastTemplate() {
	  //fetch Data from REQUEST
	  $this->templateData = array(
	  	'TemplateName' => $_REQUEST['TemplateName'],
	  	'TemplateIds' => $_REQUEST['TemplateIds'],
	  	'ArticleIds' => $_REQUEST['ArticleIds'],
	  	'url' => $_REQUEST['url'],
	  	'AppId' => $_REQUEST['AppId'],
	  	'AppSecret'=> $_REQUEST['AppSecret'],
	  	'OrignalId' => $_REQUEST['OrignalId'],
	  	'BrandId' => $_REQUEST['BrandId'],
	  	'AccountId' => $_REQUEST['AccountId']
	  	);

	  $qXunHandler = new QxunApiHelper();

	  $cap_auth = $qXunHandler->generateCapAuth($this->templateData['AppId'],$this->templateData['AppSecret'],$this->templateData['OrignalId']);

	  $url = 'http://capillary.qxuninfo.com/weixinApi/api/Massmsg/CreateMultiMedia';

	  $http_header = array('Accept: application/json','Content-Type: application/json;charset=utf-8','cap_auth: '.$cap_auth.'');

	  $this->logger->debug("WeChat Cap Auth: ".print_r($cap_auth,true));

	  $params = array(
	  	'BrandId' => $this->templateData['BrandId'],
	  	'Title' => $this->templateData['template_name'],
	  	'ArticleIds' => $this->templateData['ArticleIds']
		);
	  $params = json_encode($params);

	  $response = $qXunHandler->request('POST', $url, $http_header,$params);

	  $response = json_decode($response);

	  $this->logger->debug("WeChat Qxun Response: ".print_r($params,true) . "###" . print_r($response,true));

	  $this->templateData['qXunTemplateId'] = $response->Data;

	  $this->C_assets = new CreativeAssetsManager();
	  $status = $this->C_assets->saveMultiImageBroadcastTemplate($this->templateData,$_REQUEST['template_id']);
	  if($status) {
	  	$this->data = true;
	  } else {
		$this->data = false;
	  }
	}

	function processSingleImageBroadcastTemplate() {
		global $currentuser;
		  //fetch Data from REQUEST
		  $this->templateData = array(
		  	'title' => $_REQUEST['title'],
		  	'image' => $_REQUEST['image'],
		  	'summary' => $_REQUEST['summary'],
		  	'content' => $_REQUEST['content'],
		  	'url' => $_REQUEST['url'],
		  	'template_name' => $_REQUEST['name'],
		  	'AppId' => $_REQUEST['AppId'],
		  	'AppSecret'=> $_REQUEST['AppSecret'],
		  	'OrignalId' => $_REQUEST['OrignalId'],
		  	'BrandId' => $_REQUEST['BrandId'],
		  	'AccountId' => $_REQUEST['AccountId']
		  	);

		  $qXunHandler = new QxunApiHelper();

		  $cap_auth = $qXunHandler->generateCapAuth($this->templateData['AppId'],$this->templateData['AppSecret'],$this->templateData['OrignalId']);

		  $url = 'http://capillary.qxuninfo.com/weixinApi/api/Massmsg/CreateSingleMedia';

		  $http_header = array('Accept: application/json','Content-Type: application/json;charset=utf-8','cap_auth: '.$cap_auth.'');

		  $this->logger->debug("WeChat Cap Auth: ".print_r($cap_auth,true));

		  $params = array(
		  	'BrandId' => $this->templateData['BrandId'],
		  	'Title' => $this->templateData['title'],
		  	'Description' => $this->templateData['summary'],
		  	'Content' => urldecode($this->templateData['content']),
		  	'CoverImg' => $this->templateData['image'],
		  	'DisplayCover' => '1'
			);
		  $params = json_encode($params);

		  $response = $qXunHandler->request('POST', $url, $http_header,$params);

		  $response = json_decode($response);

		  $this->logger->debug("WeChat Qxun Response: ".print_r($params,true) . "###" . print_r($response,true));

		  $this->templateData['qXunTemplateId'] = $response->Data;

		  $this->C_assets = new CreativeAssetsManager();
		  $status = $this->C_assets->saveSingleImageBroadcastTemplate($this->templateData,$_REQUEST['template_id']);
		  if($status) {
		  	$this->data = true;
		  } else {
			$this->data = false;
		  }
	}
	/**
	* Getting content for thumbnail html content.
	*/
	private function getThumbnailContent(){

		$ref_id = $_GET['ref_id'];
		$this->logger->debug('@@@Inside Get Thumbnail Method'.print_r( $_GET , true));
		$this->C_assets = new CreativeAssetsManager();
		$template_list = $this->C_assets->getAllOrgCouponTemplates( $this->org_id , $ref_id , 'HTML' );

		foreach ($template_list as $row ) {
			$details = $this->C_assets->getDetailsByTemplateId( $row['template_id'] );
			$file_content[$row['template_id']] = stripcslashes( $details['content'] );
		}
		$this->data['file_content'] = $file_content;
	}
	
	/**
	 * gettting preview for template selected
	 */
	private function getMessagePreview(){
		
		$template_id = $_GET['template_id'];
		$this->C_assets = new CreativeAssetsManager();
		$C_template = new Template();
		
		if( $template_id === "undefined" || $template_id == '' ){
			 $this->data['error'] = _campaign("Coupon html is not available for this organization.");
			return;
		}

		if( $template_id == -1 ){
			$this->data['info'] =  "{{unsubscribe}}";
			return;
		}

		try{
			$C_template->load( $template_id );
	        $this->logger->debug('@@@Inside message preview method template id '.$template_id);
	        $content = $this->C_assets->getDetailsByTemplateId( $template_id );
	        $this->data['info'] =  stripcslashes( $content['content'] );
		}catch (Exception $e){
			$this->data['error'] = $e->getMessage();
		}
	}
	
	/**
	 * delete template based on id passed.
	 */
	private function deleteTemplate(){
		
		try{
			$this->C_assets = new CreativeAssetsManager();
			$template_id = $_GET['temp_id'];
			$type = $_GET['type'];
			$ref_id = $_GET['ref_id'];

			$this->logger->debug('@@@Inside delete template method template id :-'.$template_id.'Reference Id is'.$ref_id.' And Type is '.$type);
			$this->C_assets->deleteTemplate( $template_id, $this->org_id ,$ref_id, $type );
		}catch( Exception $e ){
			$this->data['error'] = $e->getMessage();
		}
	}
	
	/**
	 * copy particular reference to another coupon sereis.
	 */
	private function copyToCouponSeries(){
		global $currentuser;
		
		try{
			
			$this->C_assets = new CreativeAssetsManager();
			$ref_id = $_GET['ref_id'];
			$temp_id = $_GET['temp_id'];
			
			$this->logger->debug("@@@Inside copy to coupon sereis method reference id $ref_id And Template id $temp_id");
			
			$this->C_assets->copyTemplateToOtherReference( $this->org_id, $temp_id,  $currentuser->user_id , $ref_id );
			
		}catch( Exception $e ){
			$this->logger->debug('@@@An Error occurred while copying template'.$e->getMessage());
			$this->data['error'] = $e->getMessage();
		}
	}
	
	/**
	 * move a tempalte to general;
	 */
	private function makeGeneral(){
		global $currentuser;
		
		try{
			
			$this->C_assets = new CreativeAssetsManager();
			$temp_id = $_GET['temp_id'];
			$this->logger->debug('@@@Inside move template to general options template id '.$temp_id);
			$this->C_assets->setAsDefaultTemplate( $this->org_id, $temp_id, -1, $currentuser->user_id );
			
		}catch( Exception $e ){
			$this->data['error'] = $e->getMessage();
		}
	}
	
	private function reloadAssetsHome(){
		
		$ref_id = $_GET['ref_id'];
		$version = $_GET['version'];

		$this->logger->debug('@@@inside reload assets home 
							  method reference id '.$ref_id.' And version is '.$version );

		if( $version )
			$default = WidgetFactory::getWidget( 'campaign::assets::v2::CreativeAssetsHomeWidget' , $version );	
		else
			$default = WidgetFactory::getWidget( 'campaign::assets::CreativeAssetsHomeWidget' );
		
		$default->initArgs( $ref_id );
		$default->init();
		$default->process();
		$html = $default->render( true );
		
		$script = $this->getScriptsHtml();
		$this->data['coupon'] = $script.$html;
	}
	
	private function setChannel(){
		global $currentuser;
		
		$this->logger->debug('@@@Inside set channel params are :-'.print_r( $_GET , true ));
		try{
			
			$this->C_assets = new CreativeAssetsManager();
			$this->C_assets->setTemplateChannelMapping( 
														$this->org_id, 
														$_GET['ref_id'], 
														$_GET['temp_id'], 
														$_GET['channel_type'], 
														$currentuser->user_id
													  );
		}catch( Exception $e ){
			$this->logger->debug('@@@An Error occurred '.$e->getMessage());
			$this->data['error'] = $e->getMessage();
		}
	}
	
	/**
	 * 
	 */
	private function unsetChannel(){
		global $currentuser;
		
		$this->logger->debug('@@@Inside unset channel params are :-'.print_r( $_GET , true ));
		try{
				
			$this->C_assets = new CreativeAssetsManager();
			$this->C_assets->unSetTemplateChannelMapping(
															$this->org_id,
															$_GET['ref_id'],
															$_GET['temp_id'],
															$_GET['channel_type']
														);
		}catch( Exception $e ){
			$this->logger->debug('@@@An Error occurred '.$e->getMessage());
			$this->data['error'] = $e->getMessage();
		}
	}
	
	/**
	 * getting default template for coupon series
	 */
	private function getDefaultTemplate(){
		
		$asset_type = $_GET['asset_type'];
		$type = $_GET['type'];
		$ref_id = $_GET['ref_id'];
		$this->logger->debug('@@@Inside Get Default Template type is '.$type.' And Reference Id is '.$ref_id);
		try{
			
			$this->C_assets = new CreativeAssetsManager();
			
			if( $ref_id != -1 && $type == 'CLIENT' ){
				$asset_type = 'IMAGE';	
			}
			
			$data = $this->C_assets->getTemplateByChannelsPreview( $this->org_id , $ref_id , $asset_type , $type );
			
			if( empty( $data ) && $ref_id != -1 && $type == 'CLIENT' ){
				$data = $this->C_assets->getTemplateByChannelsPreview( $this->org_id , $ref_id , 'HTML' , $type );
			}
			
			$this->data['content'] = $data['content'];
			
		}catch( Exception $e ){
			$this->logger->debug('@@@An Error Occured '.$e->getMessage());
			$this->data['error'] = $e->getMessage();
		}
	}

	private function reloadImageWidget(){

		$this->logger->debug('@@@inside reload image gallery widget'.print_r( $_GET , true ));
		$ref_id = $_GET['ref_id'];
		$version = $_GET['version'];
		$type = $_GET['type'];

		if( $type == 'undefined' || !$type ) $type = false;
		if( $version )
			$default = WidgetFactory::getWidget( 'campaign::assets::v2::ImageGalleryWidget' , $version );
		else
			$default = WidgetFactory::getWidget( 'campaign::assets::ImageGalleryWidget' );

		$default->initArgs( $type , $ref_id );
		$default->init();
		$default->process();

		$html = $default->render( true );
		
		$script = $this->getScriptsHtml();
		$this->data['html'] = $script.$html;		
	}
	
	/**
	 * upload image functionality.
	 */
	private function UploadImage(){
             $this->logger->debug('@@@Inside Upload Image Widget'.print_r( $_POST ,true));
             $default = WidgetFactory::getWidget( 'campaign::assets::ImageGalleryWidget' , array( $_POST ));
             $default->init();
	         $default->process();
	}
	
	/**
	 * edit email templates html .
	 */
	 private function editTemplateRender(){
	
	      global $js,$js_version,$prefix;
	      $this->logger->debug('@@@Inside edit template render params '.print_r( $_GET , true ));
	      $type = $_GET['type'];
	      $reference_id = $_GET['reference_id'];
		  $template_id = $_GET['template_id'];
		
		  $default = WidgetFactory::getWidget( 'campaign::assets::v2::CreateHtmlTemplateWidget');
		  $default->init();
          $default->initArgs( 'edit' , $reference_id , $template_id );
          $default->process();

          $html = "<script type='text/javascript' src='$prefix/js/ckeditor/ckeditor.js$js_version'></script>";
          $html .= $default->render( true );
          $script = $this->getScriptsHtml();
          $this->data['html'] = $script.$html;
	 }
	
	 /**
      * Create new Html template or edit a template using ajax.
      */
     private function CreateOrEditTemplate(){
 	        
 	         $this->logger->debug('@@@Inside Create Or Edit Template Widget'.print_r( $_GET , true ));
 	         $this->logger->debug('@@@POST DATA :-'.print_r( $_POST , true ));
 			
 	         $type = $_GET['type'];
 	         $ref_id = $_GET['ref_id'];
 	         $temp_id = $_GET['temp_id'];
 	         $scope = $_GET['scope'];
 			 
 	         $default = WidgetFactory::getWidget( 'campaign::assets::v2::CreateHtmlTemplateWidget' , array( $_POST ));
 	         $default->init();
  	         $default->initArgs( $type, $ref_id , $temp_id ,false,false,$scope);
// 	         $default->initArgs( $type, $ref_id , $temp_id );
 	         $default->process();
 	   }
	 	
 	  /**
 	  * Returns the html of Email Templates home;
 	  */
 	  private function reloadEmailHome(){
 		       $this->logger->debug('@@@Inside Reload Email Home Widget:'.$_GET["version"]);
 		       $default = WidgetFactory::getWidget( 'campaign::assets::v2::EmailTemplatesHomeWidget',$_GET["version"]);
 		       $default->init();
 		       $default->process();
 		       $html = $default->render(true);
 		       $script = $this->getScriptsHtml();
			   $this->data['html'] = $script.$html;
 	}
 	private function getTagList($params){

 		if($params["module"]=="Points Engine")
 			$tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$POINTS_ENGINE);
 		else if( $params["module"]=="Referral")
 			$tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$REFERRAL);
 		else
 			$tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$CAMPAIGN_EMAIL);
 		
 		$str=ModuleWiseTagsProvider::getHtmlTemplateForTags($tags);
		$this->data['tags']=$str;
 	}

 	/**
 	 * zip file upload for html containing images and html inside it upload submit.
 	 */
 	private function zipUploadSubmit(){
 		
 		$params['ref_id'] = $_POST['zip_upload_html__ref_id'];
 		$params['temp_id'] = $_POST['zip_upload_html__temp_id'];
 		$params['scope'] = 'ORG';
 		
 		$files = $_FILES['zip_upload_html__zip_upload'];
		
 		$this->logger->debug('@@@FILE:-'.print_r( $files , true ));
 		
 		$ext = end( explode( "." , strtolower( basename( $files['name'] ) ) ) );
 			
 		try{
 			
 			if( $ext != 'zip' )
 				$this->data['error'] = _campaign("Invalid file type added! Please Give a proper zip file");	
 			else{
 				
		 		$creative_assets = new CreativeAssetsManager();
		 		
		 		$template = $creative_assets->createTemplateFromZip( $files['tmp_name'], $params );
		 		
		 		$this->logger->debug('@@@TEMPLATE:-'.$template);
		 		
		 		$this->data['html_template'] = $template;
 			}
 		}catch(Exception $e ){
 			$this->data['error'] = $e->getMessage();
 		}
 	}

 	/**
 	* It is used to show more templates
 	*/
 	private function showMoreTemplates( $params ){

 		$this->logger->debug("Inside show more method");
 		
 		$type = $params["type"];
 		$start_limit = $params["start_limit"];
 		$ref_id = $params["ref_id"];
 		$version = $params["version"];

 		$C_assets = new CreativeAssetsManager();
 		$this->data["template"] = $C_assets->getShowMoreTemplates( 
 			$this->org_id , $type , $ref_id , $version , $start_limit );
 		
 		$this->logger->debug("Finish show more method");
 	}
 	
 	/**
 	 *  To get html templates
 	 */
 	
 	private function getHtmlTemplates (){
 		
 		$this->C_assets = new CreativeAssetsManager();
 		
 		$start = $_GET['start'];
 		$limit = $_GET['limit'];
 		$account_id = -20;
 		
 		if( isset( $_GET['favourite'] ) ) {
 			$is_favourite = $_GET['favourite'] ? true:false;
 		}
 		
 		if( isset( $_GET['search'] ) ) {
 			$search = $_GET['search'];		
 		}
 	
 		if($search) {
 			$is_search = $search;
 		} else {
 			$is_search = false;
 		}

 		$is_edm_enabled = true;
		$C_edm_manager = new EdmManager();
		$edm_user_id = $C_edm_manager->getEdmUserId();
		if($edm_user_id){
			$is_edm_enabled = true;
		}else {
			$is_edm_enabled = false;
		}

 		$template_type = 'HTML';
 		if(isset($_GET['template_type'])){
 			$template_type = strtoupper($_GET['template_type']);
 		}

 		$template_scope = 'ORG';
 		if($template_type !== 'IMAGE'){
	 		if(isset($_GET['scope'])){
	 				$template_scope = $_GET['scope'];
	 		}
	 	}
 		
 		$this->logger->debug("sambhav: start is $start and limit is $limit");
 		 		
 		$templates = $this->C_assets->getAllTemplatesWithLimit($this->org_id, $template_type, $template_scope, $account_id, $start, $limit, $is_search, $is_favourite);
 		$qrtemplates = array() ;
 		if($template_type == 'IMAGE' && $is_favourite==true && $is_search==false){
 			$qr = array("{{voucher}}"=>"Coupon QR code");
			$qrtemplates = $this->C_assets->getQRcodeTemplates( $qr );
 			$templates = array_merge($qrtemplates,$templates);
 			$this->logger->debug('templates'.print_r($templates,true));	
 		}

 		$templates_collection = array();
	 		foreach ( $templates as $template ) {
	 			$n_template = array();
	 			$n_template[ 'template_id' ] = $template['template_id'];
	 			$n_template[ 'name' ] = $template['template_name'];
	 			$n_template[ 'html_content' ] = "";
	 			$n_template[ 'is_preview_generated' ] = $template['is_preview_generated'] ? true : false;	 			
	 			if($n_template[ 'is_preview_generated' ]){
	 				$n_template[ 'preview_url' ] = $template['content'];
	 			} else {
	 				$n_template[ 'preview_url' ] = "";
	 			}

	 			$n_template[ 'is_favourite' ] = $template['is_favourite'] ? true : false;
	 			if($n_template[ 'is_favourite' ]==true){
	 				$n_template=array_merge($n_template,$qrtemplates);
	 			}
	 			if($template[ 'tag' ] ==='EDM' && $template['drag_drop_id'] && $is_edm_enabled){
	 				$n_template['is_drag_drop'] = true;
	 				$n_template['drag_drop_id'] = $template['drag_drop_id'];
	 			} else {
	 				$n_template['is_drag_drop'] = false;
	 				$n_template['drag_drop_id'] = "";
	 			}
	 			if($template_type == 'IMAGE') {
	 				$n_template[ 'preview_url' ] = $template['preview_url'];
	 				$n_template['image_url'] = $template['content'];
	 				$n_template['file_size'] = round($template['file_size']/1024);
	 			}
	 			if($template_scope == 'WECHAT' ) {
					$n_template['title'] =  $template['content']['title'];
					$n_template['image'] = $template['content']['image'];
					$n_template['summary'] = $template['content']['summary'];
					$n_template['link'] =  $template['content']['link'];
					$n_template['qXunTemplateId'] =  $template['content']['qXunTemplateId'];
					$n_template['content'] =  $template['content']['content'];
	 			}
	 			$n_template['scope'] = $template['scope'];
	 			$n_template['tag'] = $template['tag'];
	 			$n_template[ 'linked_templates' ] = $template['linked_templates'];
	 			$n_template[ 'secondary_template_group' ] = $template['secondary_template_group'];
				$n_template[ 'lang_id_group' ] = $template['lang_id_group'];
	 			$template_collection[] = $n_template;
	 		}

 		$this->data['templates'] = $template_collection;
 		if(count($template_collection)< $limit){
 			$completed = true;
 		}
 		else{
 			$completed = false;
 		}
 		$this->data['completed'] = $completed;
 	}
 	
 	private function setFavouriteTemplate(){
 		$this->C_assets = new CreativeAssetsManager();
 		$ref_id = $_POST['account_id'] ? $_POST['account_id'] : -20;
 		$template_id = $_POST['template_id'];
 		$is_favourite = $_POST['is_favourite'];
 		try{
 			$this->C_assets->setFavouriteTemplate($is_favourite, $template_id, $this->org_id, $ref_id, 'HTML');
 			if($is_favourite)
 				$this->data['success'] = _campaign("Template has added to Favourites");
 			else 
 				$this->data['success'] = _campaign("Template has removed from Favourites");
 		} catch ( Exception $e ) {
 			$this->data['error'] = $e->getMessage();
 		}
 	}
 	
 	private function deleteHTMLTemplate() {
 		$this->C_assets = new CreativeAssetsManager();
 		$template_id = $_POST['template_id'];
 		$template_type = $_POST['template_type'];
 		
 		$ref_id = $_POST['account_id'] ? $_POST['account_id'] : -20;
 		if($template_type=='wechat' || $template_type=='mobile_push'){
 			$template_type = 'TEXT' ;
 		}else {
 			$template_type = 'HTML' ;
 		}
 		try{
 			$this->C_assets->deleteTemplate( $template_id , $this->org_id , $ref_id , $template_type);
 			$this->data['success'] = _campaign("Template has been deleted");
 		}catch ( Exception $e ){
 			$this->data['error'] = $e->getMessage();
 		}
 	}
 	
 	private function saveHTMLTemplate(){
 		global $currentorg, $currentuser;
 		$this->C_assets = new CreativeAssetsManager();
 		$params = $_POST;
 		$template_name = $params['name'];
 		$file_path = false;
 		$org_id = $this->org_id;
 		$uploaded_by = $currentuser->user_id;
 		$asset_type = 'HTML';
 		$ref_id = -20;
 		if( $params['template_id'] ) {
 			$template_id = $params['template_id'];
 		}
 		else {
 			$template_id = false;
 		}
 		if($params['is_drag_drop'] =="true" && $params['drag_drop_id']){
 			$tag = 'EDM';
 			$drag_drop_id = $params['drag_drop_id'];
 		} else {
 			$tag = 'GENERAL';
 			$drag_drop_id = false;
 		}
 		
 		$scope = $params['scope'];
 		$file_content = $params['html_content'];
 		if($scope=='EBILL') {
 			$tag = 'GENERAL';
 		} else {
 			$content = $this->sanitizeRequest(
                    array('html_content' => html_entity_decode($params['html_content'],ENT_COMPAT,'UTF-8'))
                );
        	$file_content = htmlentities($content['html_content'],ENT_COMPAT,'UTF-8');
 		}

 		if(isset($params['is_favourite']) && $params['is_favourite']=="true"){
 			$is_favourite = 1;
 		} else{
 			$is_favourite = 0;
 		}

 		$base_template_id = -1 ;
 		if(isset($params['is_multi_lang']) && $params['is_multi_lang']){
 			$base_template_id = $params['base_template_id'] ;
 		}

 		$language_id = -1 ;
 		if(isset($params['language_id']))
 			$language_id = $params['language_id'] ;

 		if(isset($params['is_deleted']))
 			$is_deleted = $params['is_deleted'] ;

 		try{
	 		$C_template = $this->C_assets->processTemplate( $template_name , $file_path , $org_id , $uploaded_by , $asset_type, $file_content, $ref_id , $template_id, $tag, $scope, $is_favourite, $drag_drop_id ,$base_template_id, $language_id , $is_deleted);
	 		$this->data['template_id'] = $C_template->getId();
	 		if($template_id){
	 			$this->data['success'] = _campaign('Template').' "'.$template_name.'" '._campaign('updated');
	 		} else {
	 			$this->data['success'] = _campaign('Template').' "'.$template_name.'" '._campaign('saved');
	 		}
 		} catch ( Exception $e ) {
 			$this->data['error'] = $e->getMessage();
 		}
 		
 	}

 	private function getWeChatContentByOrg(){
 		$this->data['weChatOrgData'] = $this->getWeChatAccounts($this->org_id);
 	}

 	private function getWeChatContentTemplates(){
 		$this->logger->debug('Inside Mapping Wechat Templates');

		$this->C_assets = new CreativeAssetsManager();
 		//$wechat_templates = $this->C_assets->getAllTemplates($this->org_id,'TEXT','WECHAT');
		$this->getAllWeChatTemplates();
		$finalArr = array();
		// $org_id = 780;
		// $this->wechat_account_controller = new WeChatAccountController(org_id);
		$this->wechat_account_controller = new WeChatAccountController($this->org_id);
		$service = $this->wechat_account_controller->get_all_accounts_by_org();
        $this->logger->debug('getWeChatContentTemplates: BrandId'.$service[0]['brand_id'].
                                                'OriginalId: '.$service[0]['original_id']);

        $response = $this->wechat_account_controller->get_template_list_by_org($this->org_id,$service[0]['id']);
        $this->logger->debug( "WeChat Response ".print_r($response,true) );
        $arr = $response;
		foreach ($arr as  $row)
		{
			if(sizeof($this->data['templates'])==0){
				$str = $row['content'];
				$tsr = array();
				$tag = array();
				$tsr = (explode("{{",$str));
				for($i=0;$i<sizeof($tsr);$i++){
					if(empty($tsr[$i])!=1){
						if(strpos($tsr[$i],"}}")>0){
								$tmp = explode("}}",$tsr[$i]);
								array_push($tag,$tmp[0]);
								}
						}
				}
				$tagarr = array();
				for($i=0;$i<sizeof($tag);$i++){
					//print_r($str[$i]);
					$tag[$i] = str_replace('.DATA', '', $tag[$i]);
					$tagarr[$tag[$i]] = array(
					"Value"=>"",
					"Color"=>"#00000"
					);
				}
				$temp = array(
					"TemplateId"=>$row['template_id'],
					"OpenId"=>"{{wechat_open_id}}",
					"OriginalId"=>$service[0]['original_id'],
					"Title"=>$row['title'],
					"Tag"=>$tag,
					"BrandId"=>$service[0]['brand_id'],
					"Url"=>"{{wechat_service_acc_url}}",
					"TopColor"=>"#000000",
					"Data"=>$tagarr,
					"preview"=>"",
					"content"=>$row['content']
					);
				array_push($finalArr,$temp);
			}
			else{
				$count = 0;
				foreach($this->data['templates'] as  $col ){
					//if template Id is already mapped then skip it
					if($row['template_id'] == $col['templates1']['0']['TemplateId']) {
						//skip , this is already mapped template
						break;
					}
					else {
						$count = $count +1;
					}
					$this->logger->debug('size_of_array'.print_r($count,true));
					if($count == sizeof($this->data['templates'])){
						$str = $row['content'];
						$tsr = array();
						$tag = array();
						$tsr = (explode("{{",$str));
						for($i=0;$i<sizeof($tsr);$i++){
							if(empty($tsr[$i])!=1){
								if(strpos($tsr[$i],"}}")>0){
										$tmp = explode("}}",$tsr[$i]);
										array_push($tag,$tmp[0]);
										}
								}
						}
						$tagarr = array();
						for($i=0;$i<sizeof($tag);$i++){
							//print_r($str[$i]);
							$tag[$i] = str_replace('.DATA', '', $tag[$i]);
							$tagarr[$tag[$i]] = array(
							"Value"=>"",
							"Color"=>"#00000"
							);
						}
						$temp = array(
							"TemplateId"=>$row['template_id'],
							"OpenId"=>"{{wechat_open_id}}",
							"OriginalId"=>$service[0]['original_id'],
							"Title"=>$row['title'],
							"Tag"=>$tag,
							"BrandId"=>$service[0]['brand_id'],
							"Url"=>"{{wechat_service_acc_url}}",
							"TopColor"=>"#000000",
							"Data"=>$tagarr,
							"preview"=>"",
							"content"=>$row['content']
							);
						array_push($finalArr,$temp);
					}

				}
			}
			
		}
		$this->logger->debug('finalArr'.print_r($finalArr,true));
		$this->getAllTags();
 		$this->data['templates1'] = $finalArr;
 	}
 	
 	private function getAllWeChatTemplateMessages( $return = false ) {
 		$this->C_assets = new CreativeAssetsManager();

 	// 	$wechat_templates = $this->C_assets->getAllTemplates(
		// 	$this->org_id,'WECHAT_TEMPLATE','WECHAT', 'GENERAL', 
		// 	$_REQUEST['account_id']
		// );
		$wechat_templates = $this->C_assets->getAllTemplates(
			$this->org_id,'WECHAT_TEMPLATE','wechat_outbound', 'GENERAL', 
			$_REQUEST['account_id']
		);
		foreach($wechat_templates as $k => $v) {
			$wechat_templates[$k]['title'] = $wechat_templates[$k]['content']['title'];
			$wechat_templates[$k]['image'] = $wechat_templates[$k]['content']['image'];
			$wechat_templates[$k]['summary'] = $wechat_templates[$k]['content']['summary'];
			$wechat_templates[$k]['link'] = $wechat_templates[$k]['content']['link'];
			if($wechat_templates[$k]['is_favourite']=="0") {
				$wechat_templates[$k]['is_favourite'] = false;
			} else {
				$wechat_templates[$k]['is_favourite'] = true;
			}
		}

		$this->logger->debug("sikri all wechat template messages".print_r($wechat_templates,true));

		if( $return )
			return $wechat_templates;

		$this->data['templates'] = $wechat_templates;

 	}

 	private function getAllWeChatMultiImageTemplates( $return = false ) {

		$this->C_assets = new CreativeAssetsManager();

		$wechat_templates = $this->C_assets->getAllTemplates(
			$this->org_id,'WECHAT_MULTI_TEMPLATE','WECHAT', 'GENERAL', 
			$_REQUEST['account_id']
		);
		foreach($wechat_templates as $k => $v) {
			if($wechat_templates[$k]['is_favourite']=="0") {
				$wechat_templates[$k]['is_favourite'] = false;
			} else {
				$wechat_templates[$k]['is_favourite'] = true;
			}
		}
		include_once 'creative_assets/assets/WeChatTemplate.php';
		$C_wechat_template = new WechatTemplate();
		$C_wechat_template->getMultiPicTemplatesForDisplay(
				$wechat_templates );

		$this->logger->debug("@@Get MultiPic Templates:".print_r( $wechat_templates , true ) );

		if( $return )
			return $wechat_templates;

		$this->data['templates'] = $wechat_templates;
 	}

 	private function getAllWeChatSingleImageTemplates( $return = false ) {
 		
		$this->C_assets = new CreativeAssetsManager();

		$wechat_templates = $this->C_assets->getAllTemplates($this->org_id,'WECHAT_SINGLE_TEMPLATE','WECHAT','GENERAL',$_REQUEST['account_id']);
		foreach($wechat_templates as $k => $v) {
			$wechat_templates[$k]['title'] = $wechat_templates[$k]['content']['title'];
			$wechat_templates[$k]['image'] = $wechat_templates[$k]['content']['image'];
			$wechat_templates[$k]['summary'] = $wechat_templates[$k]['content']['summary'];
			$wechat_templates[$k]['link'] = $wechat_templates[$k]['content']['link'];
			if($wechat_templates[$k]['is_favourite']=="0") {
				$wechat_templates[$k]['is_favourite'] = false;
			} else {
				$wechat_templates[$k]['is_favourite'] = true;
			}
		}

		$this->logger->debug("all wechat list".print_r($wechat_templates,true));

		if( $return )
			return $wechat_templates;

		$this->data['templates'] = $wechat_templates;
 	}

 	private function getAllWeChatTemplates(){

		$this->C_assets = new CreativeAssetsManager();
		$params = $_GET;
		$scope = $params['scope'];
		$acc_id = $_GET['account_id'];
		if(strcasecmp("wechat_dvs", $scope)==0 || strcasecmp("wechat_loyalty", $scope)==0){
			$wechat_templates = $this->C_assets->getAllTemplates($this->org_id,'WECHAT_TEMPLATE',$scope, 'GENERAL', $acc_id);
			foreach($wechat_templates as $k => $v) {
				$wechat_templates[$k]['html_content'] = $wechat_templates[$k]['content'] ;
				$wechat_templates[$k]['tags'] =  $this->getAllWechatTags();
				$wechat_templates[$k]['name'] = $wechat_templates[$k]['template_name'] ;
				unset($wechat_templates[$k]['content']);
				unset($wechat_templates[$k]['template_name']);
				if($wechat_templates[$k]['is_favourite']=="0"){
					$wechat_templates[$k]['is_favourite'] = false;
				}else{
					$wechat_templates[$k]['is_favourite'] = true;
				}
			}
		}else {
			$wechat_templates = $this->C_assets->getAllTemplatesCreativeAssets($this->org_id,'WECHAT_TEMPLATE','WECHAT', 'GENERAL', $_REQUEST['account_id']);
			foreach($wechat_templates as $k => $v){
				$wechat_templates[$k]['html_content'] = $wechat_templates[$k]['content'] ;
				$wechat_templates[$k]['tags'] =  $this->getAllWechatTags();
				$wechat_templates[$k]['name'] = $wechat_templates[$k]['template_name'] ;
				unset($wechat_templates[$k]['content']);
				unset($wechat_templates[$k]['template_name']);
				if($wechat_templates[$k]['is_favourite']=="0"){
					$wechat_templates[$k]['is_favourite'] = false;
				}else{
					$wechat_templates[$k]['is_favourite'] = true;
				}
			}
		}

		$this->logger->debug("all wechat list".print_r($wechat_templates,true));
		
		$this->data['templates'] = $wechat_templates;
 	}

 	private function getWeChatContent(){
 	 	
 		$template_id = $_GET['template_id'];
	
 		$this->logger->debug('inside getwechatcontent template id '.$template_id);
 		try{
 			
 			$this->C_assets = new CreativeAssetsManager();

 			//Default empty JSON
 			if( $template_id == -1 ) {
 				$this->data['html_content'] =  '{}';
 				return;
 			}
 			
 			$content = $this->C_assets->getDetailsByTemplateId( $template_id );
 			$this->data['html_content'] =  htmlspecialchars_decode(stripcslashes( $content['content'] ));
 		
 		} catch ( Exception $e ) {
 			$this->data['error'] = $e->getMessage();
 		} 		
 	}

 	private function saveWeChatTemplate(){

 		global $currentorg, $currentuser;
 		$this->C_assets = new CreativeAssetsManager();
 		$params = $_POST;
 		$this->logger->debug('Inside save wechat template: '.print_r($params,true));
 		$this->logger->debug('Inside save wechat template: '.print_r($params['file_service_params']['Title'],true));
 		$template_name = $params['file_service_params']['Title'].'-'.$params['file_service_params']['TemplateId'];
 		if($template_name) {
 			$template_name = trim($template_name," ");
 			if(!$template_name) {
 				$this->data['error'] = _campaign("template name cannot have all whitespaces");
 				return;
 			}
 		}else {
 			$this->data['error'] = _campaign("please provide template name");
 			return;
 		}
 		$this->logger->debug('checking value whether its empty'.print_r($params,true));
 		foreach ($params['file_service_params']['Data'] as $row) {
			$this->logger->debug('checking value whether its empty'.print_r($row['Value'],true));
 			if(empty($row['Value']) || strcasecmp( "Capillary Tags",$row['Value'])==0 ){
 				$this->data['error'] = _campaign("Please fill the required fields");
 				return;
 			}
 		}

 		$org_id = $this->org_id;
 		$uploaded_by = $currentuser->user_id;
 		$asset_type = 'WECHAT_TEMPLATE';
 		$file_content = json_encode($params['file_service_params']);
 		//$file_content = json_decode($file_content,true);
 		$ref_id = $params['AccountId'];
 		if( $params['template_id'] ) {
 			$template_id = $params['template_id'];
 		}
 		else {
 			$template_id = false;
 		}
 		if($params['is_drag_drop'] =="true" && $params['drag_drop_id']){
 			$tag = 'EDM';
 			$drag_drop_id = $params['drag_drop_id'];
 		} else {
 			$tag = 'GENERAL';
 			$drag_drop_id = false;
 		}
 		
 		if($params['scope']){
 			$scope = $params['scope'];
 		}else {
 			$scope = 'ORG';
 		}
 		

 		if(isset($params['is_favourite']) && $params['is_favourite']=="true"){
 			$is_favourite = 1;
 		} else{
 			$is_favourite = 0;
 		}
 		$this->logger->debug('Inside_file_content: '.print_r($file_content,true));
 		if($file_content) {		
 			
 		}else{
 			$this->data['error'] = _campaign("JSON template cannot be empty");
 				return;
 		}

 		try{
 			$this->logger->debug("inside save we chat template ".$template_name.$file_path.$org_id.$uploaded_by.$asset_type.$file_content.$ref_id.$template_id.$tag.$scope.$is_favourite.$drag_drop_id);
	 		$C_template = $this->C_assets->processTemplate( $template_name , $file_path , $org_id , $uploaded_by , $asset_type, $file_content, $ref_id , $template_id, $tag, $scope, $is_favourite, $drag_drop_id );
	 		$this->data['template_id'] = $C_template->getId();
	 		if($template_id && $template_id != -1){
	 			$this->data['success'] = _campaign('Template').' "'.$template_name.'" '._campaign('updated');
	 		} else {
	 			$this->data['success'] = _campaign('Template').' "'.$template_name.'" '._campaign('saved');
	 		}
 		} catch ( Exception $e ) {
 			$this->data['error'] = $e->getMessage();
 		} 		
 	}
 	
 	private function getHtmlContent(){
 		
 		$template_id = $_GET['template_id'];
 		$default_template = $_GET['default'];
 		
 		$this->logger->debug('@@@Inside message preview method template id '.$template_id);
 		try{
 			$this->C_assets = new CreativeAssetsManager();
 			
 			if( $template_id == -1 ) {
 				$this->data['html'] =  "<html><head></head><body>{{unsubscribe}}</body></html>";
 				return;
 			}
 			
 			$content = $this->C_assets->getDetailsByTemplateId( $template_id );
 			$this->logger->debug('@@@Inside message preview method content template id db'.print_r($content,true));
 			$this->data['html'] =  htmlspecialchars_decode(stripcslashes( $content['content'] ));
			$this->logger->debug('@@@Inside message preview method content template id decoded data'.print_r($this->data['html'],true));		
 		} catch ( Exception $e ) {
 			$this->data['error'] = $e->getMessage();
 		} 		
 	}
 	
 	private function getDefaultTemplates(){
 		$this->C_assets = new CreativeAssetsManager();
        $templates = $this->C_assets->getDefaultTemplates();
        $this->data['templates'] = $templates;
        $this->data['completed'] = true;
 	}
 	

 	private function getEdmToken(){
		$C_edm_manager = new EdmManager();
		$result = $C_edm_manager->getEdmToken($_POST["userId"]);
		echo $result;
		die();
 	}

 	private function uploadImageTemplate(){

 		$this->C_asset = new CreativeAssetsManager();
 		$this->logger->debug("uploadImageTemplate called");
 		$file = $_FILES['upload_image'];
 		$this->logger->debug("file info ".print_r($file,true));
 		//image size validation
 		if(isset($_GET['maxSize']) && ($file['size'] > $_GET['maxSize'])) {
 			$this->data['error'] = _campaign('File size must be less than ') .$_GET['maxSize'] . ' '._campaign('bytes');
 			return false;
 		}
 		if(isset($_GET['validExtensions'])) {
 			$fileExtension = array_pop(explode('/',$file['type']));
 			if(stripos($_GET['validExtensions'],$fileExtension) === false) {
	 			$this->data['error'] = _campaign('Only valid extension are :').' ' .$_GET['validExtensions'];
	 			return false;
 			}
 		}
		try{
			$result = $this->C_asset->uploadImage($file);
			$this->data['info'] = json_encode($result);
		} catch(Exception $e){
			$this->data['error'] = $e->getMessage();
		}
 	}

 	private function getScopeAvailable(){

 		//check if ebill templates is enabled or not
		$C_config_mgr = new ConfigManager();
    	$is_ebill_enabled = $C_config_mgr->getKey( "CONF_CAMPAIGN_EBILL_TEMPLATES_ENABLED" );
    	$scopes = array(
    				_campaign('Bulk Campaigns') => 'ORG', 
    				_campaign('Referral') => 'REFERRAL',
    				_campaign('Points Engine') => 'POINTSENGINE',
    				_campaign('Bounceback') => 'DVS'
    			);
    	if($is_ebill_enabled){
    		$scopes['Ebill'] = 'EBILL';
    	}
    	$this->data['scopes'] = $scopes;
 	}

 	private function getWeChatDvsTags(){
 		$dvs_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$WECHAT_DVS);
 		$tags = array('DVS'=>$dvs_tags);
 		$this->logger->debug("@@@sikri wechat dvs tags json". print_r($dvs_tags,true));
 		$this->data['tags']=$tags;
 	}
 	private function getWeChatLoyaltyTags(){
 		$loyalty_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$WECHAT_LOYALTY);
 		$tags = array('LOYALTY'=>$loyalty_tags);
 		$this->logger->debug("@@@sikri wechat loyalty tags json". print_r($dvs_tags,true));
 		$this->data['tags']=$tags;
 	}
 	private function getWeChatOutboundTags(){
 		$outbound_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$WECHAT_OUTBOUND);
 		$tags = array('OUTBOUND'=>$outbound_tags);
 		$this->logger->debug("@@@sikri wechat outbound tags json". print_r($dvs_tags,true));
 		$this->data['tags']=$tags;
 	}

 	private function getAllWechatTags(){
 		
 		//TODO add different tags for wechat
		$points_engine_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$POINTS_ENGINE);
		$referral_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$REFERRAL);
		$campaign_email_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$CAMPAIGN_EMAIL);
		$we_chat_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$WECHAT);
		$dvs_email_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$DVS_EMAIL);
		$outbound_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$WECHAT_OUTBOUND);

		$tags = array('POINTSENGINE'=>$points_engine_tags, 'REFERRAL' => $referral_tags, 'ORG'=>$campaign_email_tags,'WECHAT'=>$we_chat_tags,'DVS'=>$dvs_email_tags, 'OUTBOUND'=>$outbound_tags);
		$C_config_mgr = new ConfigManager();
    	$is_ebill_enabled = $C_config_mgr->getKey( "CONF_CAMPAIGN_EBILL_TEMPLATES_ENABLED" );
		if($is_ebill_enabled){
			$tags['EBILL'] = $campaign_email_tags; 
		}
		$this->logger->debug("sambhav tags json", print_r($tags,true));
		return $tags;
		//$this->data['tags']=$tags;
 	}

 	private function getAllTags(){
 		
 		//TODO add different tags for wechat
		$points_engine_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$POINTS_ENGINE);
		$referral_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$REFERRAL);
		$campaign_email_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$CAMPAIGN_EMAIL);
		$we_chat_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$WECHAT);
		$dvs_email_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$DVS_EMAIL);
		$mobile_push_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$MOBILEPUSH);
		$ebill_tags =  ModuleWiseTagsProvider::getTags( SupportedTagList::$EBILL);
		$outbound_tags = ModuleWiseTagsProvider::getTags( SupportedTagList::$WECHAT_OUTBOUND);

		$tags = array('POINTSENGINE'=>$points_engine_tags, 'REFERRAL' => $referral_tags, 'ORG'=>$campaign_email_tags,'WECHAT'=>$we_chat_tags,'DVS'=>$dvs_email_tags, 'MOBILEPUSH' => $mobile_push_tags, 'OUTBOUND'=>$outbound_tags);
		$C_config_mgr = new ConfigManager();
    	$is_ebill_enabled = $C_config_mgr->getKey( "CONF_CAMPAIGN_EBILL_TEMPLATES_ENABLED" );
		if($is_ebill_enabled){
			$tags['EBILL'] = $ebill_tags;
		}
		$this->logger->debug("sambhav tags json", json_encode($tags));
		$this->data['tags']=$tags;
 	}

 	private function getEdmUser(){

 		$C_edm_manager = new EdmManager();
		$edm_user_id = $C_edm_manager->getEdmUserId();
		$this->data['edm_user_id'] = $edm_user_id;
 	}

 	private function initialCreativeAssetsData(){

 		include_once 'business_controller/OrganizationController.php' ;
        $org_controller = new OrganizationController() ;
        $this->data['base_language'] = $org_controller->getDefaultLanguageId() ;
        $this->data['language'] = $org_controller->getOrgLanguages() ;
 		$this->getScopeAvailable();
 		$this->getAllTags();
 		$this->getEdmUser();
 	}

 	private function initialSocialData(){

 		//TODO susmitha make it associative array
		//If any thing comes apart from here need to add here
 		$scopes = array(
 			_campaign('Template Messages') => 'WECHAT_TEMPLATE',
 			_campaign('Single Image Broadcast') => 'WECHAT_SINGLE_TEMPLATE',
 			_campaign('Multi Image Broadcast') => 'WECHAT_MULTI_TEMPLATE'
 		);

 		$this->data['weChatAccounts'] = $this->getWeChatAccounts($this->org_id);

		$this->getAllTags();

 		$this->data['scopes'] = $scopes ;
 	}
 	
 	private function getWeChatAccounts( $org_id ) {

 		$C_wechat_account_controller = new WeChatAccountController( $org_id );

		return $C_wechat_account_controller->get_all_accounts_by_org();
 	}

 	private function getDefaultTemplateId(){

 		//$this->logger->debug("edm_check default template id".print_r($_POST,true));
 		$data = $_POST;
 		$C_edm_manager = new EdmManager();
 		try{
			$edm_template_id = $C_edm_manager->getDefaultTemplateId($data);
			$this->logger->debug("edm_check edm_template_id $edm_template_id");
			$this->data['template_id'] = $edm_template_id;
			$this->data['success'] = _campaign("Template successfully created on edm");
		} catch(Exception $e) {
			$this->data['error'] = $e->getMessage();
		}
		
 	}

 	private function getSecondaryTemplates(){

 		$this->logger->debug("inside getSecondaryTemplates") ;
 		$this->C_assets = new CreativeAssetsManager();		
 		$parent_template_id = $_GET['parent_template_id'] ;
 		$scope = $_GET['scope']?$_GET['scope']:"ORG" ;
 		$this->data['templates'] = $this->C_assets->getTemplateByParentId($this->org_id , $parent_template_id , $scope ) ;
 	}

 	private function getWeChatTemplatesForDisplay(){

 		$start = $_GET['start'];
 		$limit = $_GET['limit'];
 		$account_id = $_GET['account_id'];
 		
 		if( isset( $_GET['favourite'] ) ) {
 			$is_favourite = $_GET['favourite'] ? true:false;
 		}
 		
 		if( isset( $_GET['search'] ) ) {
 			$search = $_GET['search'];		
 		}
 	
 		if($search) {
 			$is_search = $search;
 		} else {
 			$is_search = false;
 		}

 		$this->logger->debug("sambhav: start is $start and limit is $limit");
 		 		
 		$template_collection = $this->getAllWeChatSingleImageTemplates( true );

 		$multi_templates_collection = $this->getAllWeChatMultiImageTemplates( true );

 		$template_message_collection = $this->getAllWeChatTemplateMessages( true );

 		$this->data['templates'] = array( 
 			"template_messages" => $template_message_collection,
 			"single" => $template_collection , 
 			"multi" => $multi_templates_collection );

 		if(count($template_collection)< $limit){
 			$completed = true;
 		}
 		else{
 			$completed = false;
 		}
 		$this->data['completed'] = $completed;
 	}

 	private function saveSMSTemplate(){

 		global $currentorg, $currentuser;
 		$this->C_assets = new CreativeAssetsManager();
 		$params = $_POST;
 		$template_name = $params['name'];
 		$file_path = false;
 		$org_id = $this->org_id;
 		$uploaded_by = $currentuser->user_id;
 		$asset_type = 'TEXT';
 		$scope = 'COUPON_SERIES';
 		$file_content = $params['content'];
 		$ref_id = -20;
 		if( $params['template_id'] ) {
 			$template_id = $params['template_id'];
 		}
 		else {
 			$template_id = false;
 		}

 		$tag = $params['tag'];

 		try{
	 		$C_template = $this->C_assets->processTemplate( 
	 			$template_name , $file_path , 
	 			$org_id , $uploaded_by , 
	 			$asset_type, $file_content, 
	 			$ref_id , $template_id, 
	 			$tag, $scope
	 		);
	 		$this->data['template_id'] = $C_template->getId();
	 		if($template_id){
	 			$this->data['success'] = _campaign('Template').' "'.$template_name.'" '._campaign('updated');
	 		} else {
	 			$this->data['success'] = _campaign('Template').' "'.$template_name.'" '._campaign('saved');
	 		}
 		} catch ( Exception $e ) {
 			$this->data['error'] = $e->getMessage();
 		}
 		
 	}

 	private function initialMobilePushData(){
 		include_once 'business_controller/ChannelController.php';
 		$channelController = new ChannelController();
		$scopes = array();
 		$scopes = array(_campaign('Text Template')=>'MOBILEPUSH_TEMPLATE', _campaign('Image Template') => 'MOBILEPUSH_IMAGE');
 		$channel_id = $channelController->getAccountIdByName('PUSH');
 		$this->logger->debug("inside initialMobilePushData madhu".print_r($channel_id,true)) ;
 		$this->data['mobilePushAccounts'] = $channelController->getAccounts($channel_id[0]['id']);
 		$this->logger->debug("inside initialPushData".print_r($this->data['pushAccounts'],true)) ;
		$this->getAllTags();
 		$this->data['scopes'] = $scopes ;
 	}

 	private function saveMobilePushTemplate(){
 		global $currentorg, $currentuser;
 		$this->logger->debug('Inside save mobile push template: '.print_r($_REQUEST,true));
 		$this->C_assets = new CreativeAssetsManager();
 		$params = $_REQUEST;
 		$org_id = $this->org_id;
 		$uploaded_by = $currentuser->user_id;
 		$asset_type = $params['template_scope'];
 		$ref_id = $params['accountId'];
 		$tag = 'GENERAL';
 		if( $params['template_id'] ) {
 			$template_id = $params['template_id'];
 		}
 		else {
 			$template_id = false;
 		}
 		$scope = $params['scope'];
 		if(isset($params['is_favourite']) && $params['is_favourite']=="true"){
 			$is_favourite = 1;
 		} else{
 			$is_favourite = 0;
 		}
 		$drag_drop_id = 0;
 		$file_content = json_encode($params['content'],true);

 		try{
 		$this->logger->debug('Inside save mobile push template file_content: '.print_r($file_content,true));
 		$template_name = $params['templateName'];
 		$C_template = $this->C_assets->processTemplate( $template_name , $file_path , $org_id , $uploaded_by , $asset_type, $file_content, $ref_id , $template_id, $tag, $scope, $is_favourite, $drag_drop_id );
 		$this->data['template_id'] = $C_template->getId();
	 		if($template_id && $template_id != -1){
	 			$this->data['success'] = _campaign('Template').' "'.$template_name.'" '._campaign('updated');
	 		} else {
	 			$this->data['success'] = _campaign('Template').' "'.$template_name.'" '._campaign('saved');
	 		}
 		}  catch ( Exception $e ) {
 			$this->data['error'] = $e->getMessage();
 		}
 		
 	}

 	private function getAllMobilePushTemplates($accountId = false){
 		$this->logger->debug("hello madhu check".print_r($_REQUEST,true));
 		if($accountId)
 			$accountId = $accountId;
 		else
 			$accountId = $_REQUEST['account_id'];

 		$this->C_assets = new CreativeAssetsManager();
 		$mobile_push_templates = $this->C_assets->getAllTemplates($this->org_id,$_REQUEST['scope'],'PUSH', 'GENERAL', $accountId);

 		foreach($mobile_push_templates as $k => $v){
			$mobile_push_templates[$k]['html_content'] = $mobile_push_templates[$k]['content'] ;
			$mobile_push_templates[$k]['tags'] =  $this->getAllTags();
			$mobile_push_templates[$k]['name'] = $mobile_push_templates[$k]['template_name'] ;
			unset($mobile_push_templates[$k]['content']);
			unset($mobile_push_templates[$k]['template_name']);
			if($mobile_push_templates[$k]['is_favourite']=="0"){
				$mobile_push_templates[$k]['is_favourite'] = false;
			}else{
				$mobile_push_templates[$k]['is_favourite'] = true;
			}
		}

 		$this->logger->debug("all mobile push list".print_r($mobile_push_templates,true));
 		$this->data['templates'] = $mobile_push_templates;

 	}
 	private function getSecondaryIOS($mobile_push_account){
		include_once 'business_controller/ChannelController.php';
		$channelController = new ChannelController();
		$accountConfig = $channelController->getConfigByAccountId($mobile_push_account);
		$this->logger->debug("madhu test ios".print_r($accountConfig,true));
		
		$this->C_assets = new CreativeAssetsManager();
 		$secondary_cta = $this->C_assets->getSecondaryIOS($accountConfig['licenseCode']); 
 		$this->logger->debug("get Secondary IOS CTA".print_r($secondary_cta,true));
 		$this->data['secondary_cta'] = $secondary_cta;

 	}
 
}
?>
