<?php
include_once 'creative_assets/assets/BaseTemplate.php';
include_once 'creative_assets/controller/CreativeAssetFileServiceHandler.php';
/**
 * all the operations related to text templates is handled by this class
 * @author nayan
 */
class WeChatTemplate extends BaseTemplate{

	private $template_type_id;
	
	public function __construct($asset_type){
		
		parent::__construct();
		$this->template_type_id = $this->template_types[$asset_type]; 
	}
	
	public function getSupportedChannels(){
	
		return array( 'WECHAT_TEMPLATE' => $this->template_types['WECHAT_TEMPLATE'],
					  'WECHAT_SINGLE_TEMPLATE' => $this->template_types['WECHAT_SINGLE_TEMPLATE'],
					  'WECHAT_MULTI_TEMPLATE' => $this->template_types['WECHAT_MULTI_TEMPLATE']
		 			);
	}

	public function setTemplateType( $type ){
		$this->template_type_id = $this->template_types[$type]; 
	}
	
	public function validate( Template $C_template ){
	
		$this->logger->debug('@@Wechat Template Validate Method Start');
		$C_template->setTemplateTypeId( $this->template_type_id );
		$this->checkForDuplicateTemplateName( $C_template );
		$this->logger->debug('@@Text Template Validate Method Finish');
	}
	
	public function process( Template &$C_template ){
	
		$this->logger->debug('@@Wechat Template Validate Method Start');
		
		$template_id = $C_template->getId();
		//$this->checkForDuplicateWeChatTemplateName( $C_template );
		$C_template->setIsPreviewGenerated( 1 );
		
		$this->logger->debug('@@WECHAT Template process Method End'.$template_id);
		
		$this->prepareData( $C_template );

		if( empty( $template_id ) ){
			
			$C_template->setTemplateTypeId( $this->template_type_id );
			$this->add( $C_template );
		}else{

			$this->update( $C_template );
		}

		$this->logger->debug('@@Text Template process Method End');
	}
	
	public function makeDefault( Template &$C_template ){
	
		$this->logger->debug('@@Text Template make default Method Start');
		$this->setAsDefaultTemplate($C_template);
		$this->logger->debug('@@Text Template make default Method Finish');
	}
	
	public function copyToReference( Template &$C_template ){
	
		$this->logger->debug('@@Text Template copy reference Method Start');
		$C_template->setTemplateTypeId( $this->template_type_id );
		$this->copyTemplateToReference($C_template);
		$this->logger->debug('@@Text Template copy reference Method Finish');
	}
	
	/*preview template*/
	public function preview( Template $C_template ){
		
		$file_service_params = $C_template->getFileServiceParams();
		$file_service_params = json_decode( $file_service_params , true );
		
		$contents = rawurldecode( $file_service_params['text_content'] );
		
		$params = array( 'template_name' => $C_template->getTemplateName(),
				'content' => $contents,
				'tag' => $C_template->getTag() );
		
		return $params;
	}
	
	public function prepareData( Template &$C_template ){

		//$this->checkForDuplicateTemplateName( $C_template );

		$this->logger->debug('Inside Prepare Data of WeChatTemplate :'.$C_template->getTemplateTypeId());
		
		$fs_details = addslashes( $C_template->getTemplateFileContents() );
		$supportedChannels = $this->getSupportedChannels();
		if($C_template->getTemplateTypeId()==$supportedChannels['WECHAT_SINGLE_TEMPLATE'] || $C_template->getTemplateTypeId()==$supportedChannels['WECHAT_MULTI_TEMPLATE']) {
		  $fs_details = addslashes( json_encode($C_template->getTemplateFilePath()) );
		}
		$this->logger->debug('Inside saving wechat template'.print_r($fs_details,true));
				
		$C_template->setFileServiceParams( $fs_details );	
	}

	/**
	 * It will create the global coupon wechat Templates for the org
	 */
	public function createGlobalCouponTemplates( $org_id , $created_by ){}

	public function getMultiPicTemplatesForDisplay( &$templates ){

		if( !empty( $templates ) ){

			foreach ( $templates as &$template ) {

				$templateIds = explode(",", $template["content"]["TemplateIds"]);

				$singlePicTemps = array();

				foreach ( $templateIds as $tempId ) {
					array_push($singlePicTemps, $this->getTemplateData( $tempId ));
				}

				$template["singlePicData"] = $singlePicTemps;
			}
		}
	}

	private function getTemplateData( $template_id ){

		$C_template = new Template();
		$C_template->load( $template_id );

		$result = json_decode( $C_template->getFileServiceParams() , true );
		$result['template_id'] = $template_id;
		return $result;
	}
}
?>
