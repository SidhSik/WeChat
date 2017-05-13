<?php
include_once 'ui/widget/base/FormWidget.php';
include_once 'business_controller/ChannelController.php';

class AddNewAccountWidget extends FormWidget{

	private $channelId;
	private $accountId;
	private $configKeyData;
	private $accountInfo;
	private $channelController;
	private $EntityController;
	private $OrganizationController;

	public function __construct(){
		parent::__construct("addaccount",'Add New Account');
		$this->channelController = new ChannelController();
	}

	public function initArgs($channelId = false , $accountId = false){
		$this->channelId = $channelId;
		$this->accountId = $accountId;
	}

	public function init(){
		$this->OrganizationController = new OrganizationController();
		$this->EntityController = $this->OrganizationController->StoreTillController;
	}

	public function loadData(){		
		if( $this->accountId ){
			$this->accountInfo = $this->channelController->getAccounts($this->channelId,$this->accountId);	
		}
		
		$this->configKeyData  = $this->channelController->getConfigKeyByChannelId($this->channelId,$this->accountId);
		$this->logger->debug("@@@CHANNEL:".print_r($this->configKeyData,true));
		$this->entities = $this->EntityController->getAll();
		$this->logger->debug("@@@entities madhu:".print_r($this->entities,true));

	}

	public function addFields(){
		$this->logger->debug("@@@configKeyData:".print_r($this->configKeyData,true));
		$text = new TextFieldInput($this,'name',"Account Name ");	
		if( $this->accountId ){
			$accountInfo = $this->channelController->getAccountDetailByID($this->accountId);
			$text->setDefaultValue($accountInfo[0]['account_name']);
			$text->setAttrs(array("readonly"=>true));
		}
		$this->addInputField($text);
		foreach ($this->configKeyData as $key => $value) {
			if( $value['display_order'])
				  $field = $this->getField($value);
				  if( $field )				  
			      	$this->addInputField($field);
		}
	}

	public function processSubmit(){
		$configKeyValue = array();
		foreach ($this->configKeyData as $key => $value) {
			$configKeyValue[$value['key']] = $value['id'];
		}
		$this->params['mapping'] = $configKeyValue;		
		if( !$this->accountId ){
			$result = $this->channelController->addAccount($this->channelId,$this->params);
			if($result){
				$this->setFlashMessage("Account Added Successfully!");
			}else{
				$this->setErrorMsg("Error while adding account!");
			}
		}
		else{
			$result = $this->channelController->updateConfigKeyValue($this->channelId,$this->accountId,$this->params);
			if( $result){
				$this->setFlashMessage("Config Key Value Added Successfully!");
			}else{
				$this->setErrorMsg("Error while adding config key value!");
			}
		}


	}

	public function postSubmitHook(){}

	private function getField($row){
		switch ($row['key_type']) {			
			case 'string':
			case 'number';
				$field = new TextFieldInput($this,Util::uglify($row['key']),Util::beautify($row['label']));
				if( $this->accountId  )
					$field->setDefaultValue($row['value']);
				$this->addInputField($field);
				return $field;
			case 'bool':				
							
					$field = new CheckBoxFieldInput($this,$row['key'],$row['label']);
					$this->logger->debug("@@@madhu check".$val);
					if( $this->accountId )
						$field->setDefaultValue($row['value']);					
					$this->addInputField($field);
								
				return $field;								
			case 'list':
				$this->logger->debug("madhu default_value");
				$channel_id = $this->channelController->getAccountIdByName('PUSH');
				$default_value = json_decode($row['default_value'],true);
				if($this->channelId == $channel_id[0]['id'])
					$default_value = $this->setDefaultValueList($row);
				$field = new SelectFieldInput($this,Util::uglify($row['key']),Util::beautify($row['label']),$default_value);
				if( $this->accountId )
					$field->setDefaultValue($row['value']);
				return $field;
		}
	}
	public function setDefaultValueList($row){
		switch ($row['key']) {
			case 'validTill':
				$default_value = $this->getStoreTillList();
				break;
			case 'loginIdentifierType':
				$default_value = array('mobile','email','intouchuserid');
				break;
			default:
				$default_value = json_decode($row['default_value'],true);
				break;
		}
		return $default_value;
	}
	public function getStoreTillList(){
		foreach( $this->entities as $entity_details ){
			$code = $entity_details['code'];
			$entity_options[$code] = $entity_details['code'];
		}
		$this->logger->debug("madhu default_value2".print_r($entity_options,true));
		return $entity_options;
	}
	
}
?>
