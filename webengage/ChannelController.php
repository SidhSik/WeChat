<?php
include_once 'model_extension/class.ChannelModelExtension.php';
include_once 'helper/CurlManager.php';

class ChannelController extends BaseController{

	protected $logger;
	private $channelModel;
	public function __construct(){
		global $logger,$currentorg;
		$this->logger = $logger;
		$this->channelModel = new ChannelModelExtension();
	}

	//this will return list channels from `masters`.`channels` table
	public function getChannelList($id = false){
		$results = $this->channelModel->getChannelList($id);
		$this->logger->debug("@@@CHANNEL:".print_r($results,true));
		return $results;
	}

	public function getAccounts( $channel_id ){
		$this->logger->debug("@@@CHANNEL ACCOUNT:".$channel_id);
		$results = $this->channelModel->getAccounts($channel_id);
		$this->logger->debug("@@@CHANNEL ACCOUNT:".print_r($results,true));
		return $results;
	}
	public function getAccountIdByName($channel_name){
		$this->logger->debug("@@@CHANNEL NAME:".$channel_name);
		$results = $this->channelModel->getAccountIdByName($channel_name);
		$this->logger->debug("@@@ACCOUNT ID:".print_r($results,true));
		return $results;
	}

	public function getAccountDetailByID($id){
		$this->logger->debug("@@@CHANNEL NAME:".$id);
		$results = $this->channelModel->getAccountDetailByID($id);
		$this->logger->debug("@@@ACCOUNT ID:".print_r($results,true));
		return $results;

	}

	public function getConfigKeyByChannelId($channelId,$accountId){		
		$results = $this->channelModel->getConfigKeysByChannelId($channelId,$accountId);
		return $results;
	}

	public function addAccount($channelId , $params){
		
		// $result = $this->channelModel->addChannelMapping($channelId);
		$accountId = $this->channelModel->addAccount( $channelId,$params['name'] );
		if( $accountId ){
			$this->logger->debug("@@@CHANNELID:".$channelId);
			$this->logger->debug("@@@CHANNELID ACCOUNT:".$channelId);
			$channelName = $this->getChannelList($channelId);
            if( $channelName[0]['name'] == 'PUSH')
                $webEngageCampaignId = $this->makeWebEngageCall($params,$accountId,$channelId);
			return array('1'=>$this->updateConfigKeyValue($channelId,$accountId,$params),
						'2'=>$webEngageCampaignId);
		}
	}

	public function updateConfigKeyValue($channelId,$accountId,$params){
		$this->logger->debug("@@@CHACCOUNT ADD PARAMS :".$channelId);
		$this->logger->debug("@@@CHACCOUNT ADD PARAMS :".$accountId);
		$this->logger->debug("@@@CHACCOUNT ADD PARAMS :".print_r($params,true));
		$valParams = array();
	 	
	 	foreach ($params['mapping'] as $key => $value) {	 		
	 		$valParams[$value] = $params[Util::uglify($key)];
	 	}
	 	$this->logger->debug("@@@CHACCOUNT Value :".print_r($valParams,true));
	 	$result =  $this->channelModel->addConfigKeyVal($accountId,$valParams);
	 	$this->logger->debug("@@@CHACCOUNT RESULT :".$result);
	 	$channelName = $this->getChannelList($channelId);
            if( $channelName[0]['name'] == 'PUSH')
                $this->makeWebEngageCall($params,$accountId,$channelId);
	 	if( $result && $params['pageaccesstoken'] ){
	 		$curl = new CurlManager();
	 		$fbUrl = "https://graph.facebook.com/v2.6/me?access_token=";
	 		$fbUrl .= $params['pageaccesstoken'];
	 		$this->logger->debug("@@@@FBURL:".$fbUrl);
	 		$response = json_decode($curl->get($fbUrl,false),true);	 		
	 		$this->logger->debug("@@@@FBRESULT:".print_r($response,true));
	 		$this->channelModel->updatePageID($channelId,$accountId,$response['id']);
	 	}
	 	return $result;
	}

	public function getConfigByAccountId($accountId){
		if( !$accountId )
			return false;
		return $this->channelModel->getConfigByAccountId($accountId);
	}

	public function updateConfigByKeyName($accountId,$channelId,$key,$val){
		return $this->channelModel->updatePageID($accountId,$channelId,$val,$key);
	}
	private function makeWebEngageCall($params,$accountId,$channelId){
       $license_code = $params['licensecode'];
       $access_token = $params['accesstoken'];
       $account_name = $params['name'];
       $this->logger->debug("@@@@DATA:".print_r($params,true));
       $url = "https://api.webengage.com/v1/accounts/$license_code/campaigns";
       $headers = array("Content-Type:application/json","authorization: bearer $access_token");
       $curl = new CurlManager();
       $data = array("title" => $account_name,"type" => "PUSH_NOTIFICATION");
       $this->logger->debug("@@@@DATA:".json_encode($data));
       $response = json_decode($curl->doRequest('POST',$url,json_encode($data),$headers),true);
       if($response['response']['data']['id'] ){
       		$result = $this->channelModel->updatePageID($channelId,$accountId,$response['response']['data']['id'],'trans_campaign_id');
       		$this->logger->debug("@@@@DATA:".print_r($result,true));
       }
       $this->logger->debug("@@@@DATA:".print_r($response,true));
       return $response['response']['data']['id'];
    }
}
?>
