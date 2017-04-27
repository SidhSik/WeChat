<?php

/*
	Author: Someshwar Dash
	WeChatAccountModelExtension Class: Do all database operations related to WeChat Account here
	Methods:	1) insertAccountInDB
				2) editAccountInDB
				3) deleteAccountFromDB
				4) getAllAccountsByOrg
				5) getAccountById
*/

class WeChatAccountModelExtension{

	private $db;
	private $logger;

	public function WeChatAccountModelExtension(){
		$this->db = new Dbase('masters');
		global $logger;
		$this->logger = $logger;
	}

	public function insertAccountInDB(	$account_name,
										$org_id,
										$app_id,
										$app_secret,
										$wechat_app_id,
										$wechat_app_secret, 
										$original_id, 
										$service_account_url,
										$brand_id,
										$user_id){

		$sql = "SELECT * 
				FROM  wechat_account_configuration
				WHERE app_id =  '".$app_id."'
				AND app_secret =  '".$app_secret."'
				AND original_id =  '".$original_id."'
				AND is_active = 1";
		$result = $this->db->query($sql);
		$affected_rows = $this->db->getAffectedRows();

		if($affected_rows == 0){

			$sql = "INSERT INTO wechat_account_configuration(account_name,org_id,app_id,app_secret,wechat_app_id,wechat_app_secret,original_id,service_account_url,brand_id,is_active,created_by,created_on,last_updated_by,last_updated_on) 
			VALUES ('".$account_name."',$org_id,'".$app_id."','".$app_secret."','".$wechat_app_id."','".$wechat_app_secret."','".$original_id."','".$service_account_url."','".$brand_id."',1,$user_id,NOW(),$user_id,NOW())
			ON DUPLICATE KEY UPDATE 
			account_name = '".$account_name."',
			org_id = $org_id,
			app_id = '".$app_id."',
			app_secret = '".$app_secret."',
			wechat_app_id = '".$wechat_app_id."',
			wechat_app_secret = '".$wechat_app_secret."',
			original_id = '".$original_id."',
			service_account_url = '".$service_account_url."',
			brand_id = '".$brand_id."',
			is_active = 1,
			created_by = $user_id,
			created_on = NOW(),
			last_updated_by = $user_id,
			last_updated_on = NOW()
			";
			$result = $this->db->insert($sql);
			return $result;
		} else {
			return false;
		}

	}

	public function editAccountInDB(	$id,
										$account_name,
										$app_id,
										$app_secret,
										$wechat_app_id,
										$wechat_app_secret, 
										$original_id, 
										$service_account_url,
										$user_id ){

		$sql = "UPDATE wechat_account_configuration SET account_name = '".$account_name."', app_id = '".$app_id."', app_secret = '".$app_secret."', wechat_app_id = '".$wechat_app_id."', wechat_app_secret = '".$wechat_app_secret."', original_id = '".$original_id."', service_account_url = '".$service_account_url."', is_active = 1, last_updated_by = $user_id, last_updated_on = NOW() WHERE id = $id";
		$result = $this->db->update($sql);
		return $result;

	}

	public function deleteAccountFromDB($id,$user_id){

		$sql = "UPDATE wechat_account_configuration SET is_active = 0, last_updated_by = $user_id, last_updated_on = NOW() WHERE id = $id";
		$result = $this->db->update($sql);
		return $result;
	}

	public function getAllCapIdToOriginalIdMapByOrg($org_id){
		$sql = "SELECT id,original_id FROM wechat_account_configuration WHERE org_id=$org_id AND is_active = 1";
		$result = $this->db->query($sql);
		$result_count = $this->db->getAffectedRows();

		if($result_count > 0)
			return $result;
		else
			return false;
	}

	public function getAllAccountsIdByOrg($org_id){

		$sql = "SELECT id FROM wechat_account_configuration WHERE org_id=$org_id AND is_active = 1";
		$result = $this->db->query($sql);
		$result_count=$this->db->getAffectedRows();

		if($result_count > 0){
			return $result;
		} else {
			return false;
		}
		
	}

	public function getAccountDetailsByOriginalId($org_id , $original_id){

		$sql = "SELECT id,account_name,org_id,app_id,app_secret,wechat_app_id,wechat_app_secret,original_id,service_account_url,brand_id,is_active FROM wechat_account_configuration WHERE org_id=$org_id AND is_active = 1 AND original_id='$original_id'";
		$result = $this->db->query($sql);
		$result_count=$this->db->getAffectedRows();

		if($result_count > 0){
			return $result;
		} else {
			return false;
		}
	}

	public function getAllAccountsByOrg($org_id){

		$sql = "SELECT id,account_name,org_id,app_id,app_secret,wechat_app_id,wechat_app_secret,original_id,service_account_url,brand_id,is_active,created_by,created_on,last_updated_by,last_updated_on FROM wechat_account_configuration WHERE org_id=$org_id AND is_active = 1";
		$result = $this->db->query($sql);
		$result_count=$this->db->getAffectedRows();

		if($result_count > 0){
			return $result;
		} else {
			return false;
		}
		
	}

	public function getAccountById($id){

		$sql = "SELECT id,account_name,org_id,app_id,app_secret,wechat_app_id,wechat_app_secret,original_id,service_account_url,brand_id,is_active,created_by,created_on,last_updated_by,last_updated_on FROM wechat_account_configuration WHERE id=$id";
		$result = $this->db->query_firstrow($sql);
		$result_count=$this->db->getAffectedRows();
		return $result;
	}
}

?>
