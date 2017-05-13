<?php
include_once 'ui/page/IFramePage.php';
include_once 'ui/widget/base/WidgetFactory.php';

class AccountAddPage extends IFramePage{

	private $channelId;
	private $accountId;
	
	public function __construct(){
		
		global $js_version, $css_version;

		parent::__construct();
		
		$script = 
			"<link rel='stylesheet' href='$prefix/style/campaign/campaign_media_ui.css$css_version' type='text/css' />
			<script type='text/javascript' src='/js/campaign/deeplink.js$js_version'></script>";

		$this->includeRequiredScripts( $script );
	}

	public function initArgs( $channelId = false , $accountId = false){
	
		$this->channelId = $channelId;
		$this->accountId = $accountId;
	}

	public function loadWidgets(){
	
		$add = WidgetFactory::getWidget("org::config::AddNewAccountWidget");
		$add->initArgs($this->channelId,$this->accountId);
		
		$this->callWidget($add);
	}
}
?>