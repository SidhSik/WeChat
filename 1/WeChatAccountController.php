<?php

include_once 'model_extension/wechat/class.WeChatAccountModelExtension.php';
include_once 'helper/wechat/class.QxunApiHelper.php';
include_once 'creative_assets/CreativeAssetsManager.php';
include_once 'business_controller/campaigns/OutboundController.php';
include_once 'creative_assets/model/class.Template.php';

/*
	Author: Someshwar Dash
	This is the WeChatAccountController Class
	Methods:	1) add_account : Add a new WeChat account for the org
				2) edit_account: Edit a WeChat account
				3) delete_account: Delete a WeChat account
				4) get_all_accounts_by_org: Fetches all added WeChat accounts by org_id
				5) get_account_by_id: Fetches a WeChat account by id (DB id)
				6) get_brand_info_qxun
				7) get_fans_open_ids
				8) get_fans_detail
				9) send_template_message
				10) get_template_message_send_status
				11) get_template_list_by_org
				12) get_all_wechat_templates_from_db
				13) create_single_picture_text_message
*/

class WeChatAccountController extends BaseController{

	private $C_wechat_account_model;
	private $C_qxun_api_helper;
	private $org_id_passed;
	private $C_assets;
	private $C_outbound_controller;

	public function __construct($org_id){
		
		global $url_version,$currentorg;

		parent::__construct();
		
		//To ensure org_id is not empty when it is coming from external source
		if($org_id==''){
			$org_id = $currentorg->org_id;
		}
		
		//To load the cheetah's organizational model extension for external calls
		$url_version = '1.0.0.1';
		$currentorg = new OrganizationModelExtension();
		$currentorg->load( $org_id );
		$this->org_id_passed = $org_id;
		$this->C_wechat_account_model = new WeChatAccountModelExtension();
		$this->C_qxun_api_helper = new QxunApiHelper();
		
	}

	public function add_account($accountname,$appid,$appsecret,$wechatappid,$wechatappsecret,$originalid,$serviceaccounturl,$brandid,$userid){

		$result = $this->C_wechat_account_model->insertAccountInDB(
			$accountname,
			$this->org_id_passed,
			$appid,
			$appsecret,
			$wechatappid,
			$wechatappsecret,
			$originalid,
			$serviceaccounturl,
			$brandid,
			$userid
			);

		return $result;
	}

	public function edit_account($id,$accountname,$appid,$appsecret,$wechatappid,$wechatappsecret,$originalid,$serviceaccounturl,$userid){

		$result = $this->C_wechat_account_model->editAccountInDB(
			$id,
			$accountname,
			$appid,
			$appsecret,
			$wechatappid,
			$wechatappsecret,
			$originalid,
			$serviceaccounturl,
			$userid
			);

		return $result;
	}

	public function delete_account($id,$userid){

		$result = $this->C_wechat_account_model->deleteAccountFromDB($id,$userid);
		$this->logger->debug("WeChat Account Deleted With id: $id");
		return $result;
	}

	public function get_account_details_by_original_id($original_id){
		
		$response = $this->C_wechat_account_model->getAccountDetailsByOriginalId($this->org_id_passed , $original_id);
		if($response!=null && isset($response)){
			$this->logger->debug("WeChat Accounts Details".print_r($response,true));
			return $response[0];
		}else
			return false;

	}

	public function get_cap_id_to_original_id_map_by_org(){
		$result = $this->C_wechat_account_model->getAllCapIdToOriginalIdMapByOrg($this->org_id_passed);
		$this->logger->debug("All WeChat Accounts: ".print_r($result,true));
		$capIdToOriginalIdMap = array();
		if($result!=null && isset($result)){
			foreach ($result as $row) {
				$capIdToOriginalIdMap[$row['id']] = $row['original_id'];
			}
		}
		else
			$capIdToOriginalIdMap[0] = '0';
		$this->logger->debug("capIdToOriginalIdMap: ".print_r($capIdToOriginalIdMap,true));
		return $capIdToOriginalIdMap;
	}

	public function get_all_accounts_by_org(){

		$result = $this->C_wechat_account_model->getAllAccountsByOrg($this->org_id_passed);
		// $result = $this->C_wechat_account_model->getAllAccountsByOrg(780);
		$this->logger->debug("WeChat All Accounts ".print_r($result,true));
		return $result;
	}

	public function get_account_by_id($id){

		$result = $this->C_wechat_account_model->getAccountById($id);
		return $result;
	}

	public function get_brand_info_qxun($app_id,$app_secret,$original_id){

		$result = $this->C_qxun_api_helper->getBrandInfo($app_id,$app_secret,$original_id);
		return $result;

	}

	public function get_fans_open_ids($app_id,$app_secret,$original_id){

		$result = $this->C_qxun_api_helper->getFansOpenIds($app_id,$app_secret,$original_id,$this->org_id_passed);
		return $result;

	}

	public function get_fans_detail($app_id,$app_secret,$original_id){

		$result = $this->C_qxun_api_helper->getFansDetail($app_id,$app_secret,$original_id,$this->org_id_passed,'');
		$this->logger->debug("WeChat Get Fans Details: ".print_r($result,true));
		return $result;

	}

	public function send_template_message($app_id,$app_secret,$original_id){

		list($result,$url) = $this->C_qxun_api_helper->sendTemplateMessage($app_id,$app_secret,$original_id,$this->org_id_passed,'');
		return array($result,$url);

	}

	public function get_template_message_send_status($app_id,$app_secret,$original_id) {

		list($result,$url) = $this->C_qxun_api_helper->getTemplateMessageSendStatus($app_id,$app_secret,$original_id);
		return array($result,$url);

	}

	public function get_template_list_by_org($org_id,$account_id){
		$result = $this->C_wechat_account_model->getAllAccountsByOrg($org_id);
		foreach ($result as $key => $value){
			if($result[$key]["id"] == $account_id){
				$position = $key;
				$this->logger->debug("WeChat Account Position $position");
				break;
			}
		}
		if($result){
			$app_id = $result[$position]["app_id"];
			$app_secret = $result[$position]["app_secret"];
			$original_id = $result[$position]["original_id"];


			$info = $this->C_qxun_api_helper->getBrandInfo($app_id,$app_secret,$original_id);
			$temp = json_decode($info,true);
			$template_list = $temp['Data']['SelectedTemplates']['template_list'];

			$this->logger->debug("WeChat Get Template List By Org First".print_r($template_list,true));

			return $template_list;

		}
		else {
			return false;	//Error Message: No Accounts mapped under specified org_id
		}
		
	}

	public function getAllWeChatTemplates($org_id , $asset_type = 'HTML' , $scope = 'WECHAT', $tag = 'GENERAL' , $account_id = -20 ){
 			if(empty($org_id) && $org_id < 0){
 				$org_id = $this->orgid_passed;
 			}
			$this->C_assets = new CreativeAssetsManager();

 			$wechat_templates = $this->C_assets->getAllTemplates($org_id,'WECHAT_TEMPLATE',$scope,'GENERAL', $account_id);
 			
 			foreach($wechat_templates as $k => $v){
 				$wechat_templates[$k]['html_content'] = $wechat_templates[$k]['content'] ;
 				//$wechat_templates[$k]['tags'] =  $this->getAllWechatTags();
 				$wechat_templates[$k]['name'] = $wechat_templates[$k]['template_name'] ;
 				unset($wechat_templates[$k]['content']);
 				unset($wechat_templates[$k]['template_name']);
 				if($wechat_templates[$k]['is_favourite']=="0"){
 					$wechat_templates[$k]['is_favourite'] = false;
 				}else{
 					$wechat_templates[$k]['is_favourite'] = true;
 				}
 			}

 			$this->logger->debug("WeChat Get All WeChat Templates".print_r($wechat_templates,true));
 			
 			return $wechat_templates;
 			

 	}
 	//fetch wechat acc id per org
 	public function getWeChatAccounts() {
 			$this->C_outbound_controller = new OutboundController();
 			$wechat_acc_id = $this->C_outbound_controller->getWeChatAccounts( $this->org_id_passed);
			$this->logger->debug("WeChat Get All WeChat Account Id".print_r($wechat_acc_id,true));

			return $wechat_acc_id;
 	}

	//TODO: NOT COMPLETE
	public function create_single_picture_text_message(){

		list($result,$url) = $this->C_qxun_api_helper->createSinglePictureTextMessage($app_id,$app_secret,$original_id);
		return array($result,$url);

	}

	/**
	 * 
	 * @param array $file_service_params 
	 */
	public function getPreviewForWeChatTemplate($file_service_params){
		$this->logger->debug("getPreviewForWeChatTemplate madhu:: ".print_r($file_service_params,true));

		 $preview_params['Title']=$file_service_params['Title'];
		 $preview_params['content']=$file_service_params['content'];
		 $preview_params['Url']=$file_service_params['Url'];

		 	foreach ($file_service_params['Data'] as $key_Data => $value) {
		 		$key=$key_Data.".DATA";
				 	if (strpos($preview_params['content'], $key) !== false) {
				   		foreach ($value as $k =>$val) {
				    	if($k=='Value')
				    	$preview_params['content']=str_replace("{{".$key."}}",$val,$preview_params['content']);
				    	$preview_params['content']=str_replace(array("\r\n", "\n\r", "\r", "\n"),'<br />',$preview_params['content']);
				    	$this->logger->debug("getPreviewForWeChatTemplate content:: ".print_r($preview_params['content'],true));

				    }
				    
				}
		 	}

		 return $preview_params;
	}

	/**
	 * 
	 * @param get array $file_service_params for particular template id
	 */

	/*public function getwechatTemplateJson($temp_id)
	{
		$get_all_temp=$this->getAllWeChatTemplates();
		foreach($get_all_temp as $val){
	
		if($val['template_id'] == $temp_id){
			$this->logger->debug("preview template json:: ".print_r($val,true));
			return $val['file_service_params'];
		}
	}
	}*/
	public function getwechatTemplateJson($temp_id)
	{
			$this->logger->debug("preview template id:: ".$temp_id);
			$C_template = new Template();
			$C_template->load( $temp_id );
			$get_temp=$C_template->getHash();
			$this->logger->debug("preview template madhu:: ".print_r($get_temp['file_service_params'],true));
			$file_service_params =json_decode($get_temp['file_service_params'], true);
			return $file_service_params;

	}



	public function getWechatMsg($templat_list)
	{
		$wechat_msg_key =array('TemplateId','OpenId','Title','BrandId','Url','TopColor','OriginalId','Data');
		$wechat_msg_json=array();
		foreach ($templat_list as $key => $value) {
			foreach ($wechat_msg_key as  $wechatvalue) {
				if($key==$wechatvalue)
				$wechat_msg_json[$wechatvalue]=$value;
			}
			$this->logger->debug("getWechatMsg content1:: ".print_r($wechat_msg_json,true));
		}
		return $wechat_msg_json;
	}
	public function getWechatDetailJSON($wechat_id)
	{
	$this->logger->debug("Inside wechat template id".print_r( $wechat_id, true) );
			$prop_values =  array() ;

				$prop_values['wechat_id'] =$wechat_id;
				$wechat_template=$this->getwechatTemplateJson($wechat_id);
				$prop_values['wechat_template'] =$this->getWechatMsg($wechat_template);
				$prop_values['wechat_brandId']=$prop_values['wechat_template']['BrandId'];
				$prop_values['wechat_originalId']=$prop_values['wechat_template']['OriginalId'];
			
	
			$this->logger->debug("Inside wechat template".print_r( $prop_values, true) );
			return $prop_values;

	}

}

?>