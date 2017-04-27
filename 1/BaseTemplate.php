<?php
include_once 'creative_assets/model/class.Template.php';

abstract class BaseTemplate{
	
	protected $org_id;
	protected $logger;
	protected $C_database;
	protected $channel_types;
	protected $template_types;
	private $C_resize_image;
	
	public function __construct(){

		global $logger;
		
		$this->logger = $logger;
		$this->C_database = new Dbase('creative_assets');
		
		$this->template_types = $this->getTemplateTypesAsOption();
		$this->channel_types = $this->getChannelTypesAsOption();
	}
	
	abstract public function validate( Template $C_template );
	
	abstract public function preview( Template $C_template );
	
	abstract public function process( Template &$C_template );
	
	abstract public function makeDefault( Template &$C_template );
	
	abstract public function createGlobalCouponTemplates( $org_id , $created_by );
	
	abstract public function copyToReference( Template &$C_template );
	
	abstract public function getSupportedChannels();

	abstract public function prepareData( Template &$C_template );
	
	protected function copyTemplateToReference( Template &$C_template ){
		
		$C_template->setId(false);
		
		$template_name = $C_template->getTemplateName();
		
		$template_name .= "__copy_".time();
		
		$C_template->setTemplateName( $template_name );
		
		$this->checkForDuplicateTemplateName( $C_template );
		
		$template_id = $C_template->insert();
		
		if( !$template_id )
			throw new Exception(_campaign("Error occured while inserting template"));
		
		$C_template->setId( $template_id );
		
		$org_id = $C_template->getOrgId();
		$ref_id = $C_template->getRefId();
		
		$this->insertOrgTemplateMapping( $org_id , $template_id , $ref_id );
	}
	
	protected function setAsDefaultTemplate( Template $C_template ){
		
		$C_template->setIsDefault( 1 );
		
		$status = $C_template->update( $C_template->getId() );
		
		if( !$status )
			throw new Exception(_campaign("Error occured while making the template as default template"));
	}
	
	/*
	 * It will add template to file service first and then add mapping into templates table
	 */
	protected function add( Template &$C_template ){
				
		$template_id = $C_template->insert();
		
		if( !$template_id )
			throw new Exception(_campaign("Error occured while inserting template"));
		
		$C_template->setId( $template_id );
		
		$org_id = $C_template->getOrgId();
		$ref_id = $C_template->getRefId();
		$language_id = $C_template->getLanguageId();
		$this->insertOrgTemplateMapping( $org_id , $template_id , $ref_id , $language_id);
	}
	
	/*
	 * It will update template to file service first and then update mapping into templates table
	*/
	protected function update( Template &$C_template ){
		
		$template_id = $C_template->update( $C_template->getId() );
		
		if( !$template_id )
			throw new Exception(_campaign("Error occured while updating template"));
	}
	
	/*
	 * It will delete template from file service first and then delete mapping from templates table
	*/
	public function delete( $org_id = false , $template_id = false , $ref_id = false ){
		
		if( empty( $template_id ) && empty( $org_id ) && empty( $ref_id ) )
			throw new Exception(_campaign("org id and template id should be required for deleting template"));
		
		$this->logger->debug('@@@Delete');
		
		$C_template = new Template();
		$C_template->load( $template_id );
		$C_template->setOrgId( $org_id );
		$C_template->setRefId( $ref_id );
		
		//it will check for any channel assigned or not before deletion of coupon html and text
		$this->checkTemplateChannelBeforeDelete( $C_template );
		
		$status = $C_template->delete( $template_id );
		
		if( !$status )
			throw new Exception(_campaign("Error occured while deleting template"));
		
		$sql = "DELETE FROM `creative_assets`.`org_templates`
					WHERE `org_id` = '$org_id' AND `template_id` = '$template_id'";
		
		$status = $this->C_database->update( $sql );
		
		if( !$status )
			throw new Exception(_campaign("Error occured while deleting org template mapping"));

		//it will delete only coupon templates channel mapping
		if( $ref_id != -20 ){

			$sql = "DELETE FROM `creative_assets`.`template_channel_mapping`
			WHERE template_id = '$template_id' AND org_id = '$org_id'";
			
			$status = $this->C_database->update( $sql );
			
			if( !$status )
				throw new Exception(_campaign("Error occured while deleting template channel mapping"));
		}
	}
	
	/*
	 * It will set template favourite
	*/
	public function setFavourite( $is_favourite, $template_id, $asset_type, $org_id, $ref_id){
	
		if( empty( $template_id ) && empty( $org_id ) && empty( $ref_id ) )
			throw new Exception(_campaign('org id and template id should be required for deleting template'));
	
		$this->logger->debug('@@@Delete');
	
		$C_template = new Template();
		$C_template->load( $template_id );
		$C_template->setIsFavourite($is_favourite);
		
		$status = $C_template->update( $template_id );
		return $status;
	}
	
	protected function checkForDuplicateTemplateName( Template $C_template ){
	
		$id = $C_template->getId();
		$template_name = $C_template->getTemplateName();
		$template_type_id = $C_template->getTemplateTypeId();
		$org_id = $C_template->getOrgId();
		$scope = $C_template->getScope();
		
		$id_filter = "";
		if( $id )
			$id_filter = " AND t.`id` != '$id' ";
		
		$sql = "SELECT t.`template_name` FROM `creative_assets`.`templates` t 
					JOIN `creative_assets`.`org_templates` ot 
						ON ot.`org_id` = '$org_id' AND ot.`template_id` = t.`id`
					WHERE t.`template_type_id` = '$template_type_id' 
						AND t.`template_name` = '$template_name' AND t.`is_deleted` = '0' AND t.`scope` = '$scope' $id_filter";
	
		$template = $this->C_database->query_scalar( $sql );
	
		if( $template )
			throw new Exception(_campaign('Duplicate template name exists for this template type'));
	}
	
	protected function insertOrgTemplateMapping( $org_id , $template_id , $ref_id , $language_id=-1){

		$sql = "INSERT INTO `creative_assets`.`org_templates`(`org_id`,`template_id`,`ref_id`,`lang_id`) 
					VALUES( $org_id , $template_id , $ref_id , $language_id)";
		
		$status = $this->C_database->insert( $sql );
		
		if( !$status )
			throw new Exception(_campaign("Error occured while adding org template mapping"));
	}
	
	private function getTemplateTypesAsOption(){
		
		$sql = " SELECT id,name FROM `creative_assets`.`template_types` ";
		
		$this->template_types = 
			$this->C_database->query_hash( $sql , 'name' , 'id' );
		
		return $this->template_types;
	}
	
	private function getChannelTypesAsOption(){
	
		$sql = " SELECT id,type FROM `user_management`.`comm_channels` 
					WHERE `is_valid` = '1' ";
	
		$this->channel_types = 
			$this->C_database->query_hash( $sql , 'type' , 'id' );
		
		return $this->channel_types;
	}
	
	private function deleteOrgTemplateMapping( $org_id , $template_id ){
		
		$mapping = $this->getOrgTemplateMappingDetails( $org_id , $template_id );
		
		if( empty( $mapping ) )
			throw new Exception(_campaign('Template does not exists'));
		
		$sql = "DELETE FROM `creative_assets`.`org_templates` 
					WHERE `org_id` = '$org_id' AND `template_id` = '$template_id'";
		
		$status = $this->C_database->update( $sql );
		
		if( !$status )
			throw new Exception(_campaign("Error occured while deleting template mapping into organization"));
	}
	
	private function getOrgTemplateMappingDetails( $org_id , $template_id , $ref_id = -1 ){
	
		$sql = " SELECT id,template_id FROM `creative_assets`.`org_templates` 
					WHERE `org_id` = '$org_id' AND `template_id` = '$template_id' AND `ref_id` = '$ref_id' ";

		return $this->C_database->query_scalar($sql);
	}
	
	private function makeOrgTemplateAsDefault( $org_id , $template_id , $ref_id = -1 ){
	
		$sql = " SELECT id,template_id FROM `creative_assets`.`org_templates`
		WHERE `org_id` = '$org_id' AND `template_id` = '$template_id' AND `ref_id` = '$ref_id' ";
	
		return $this->C_database->query_scalar($sql);
	}
	
	protected function createTempFile( Template &$C_template ){
	
		$this->logger->debug("@@Create temporary file start");
		$file_contents = $C_template->getTemplateFileContents();
	
		if( !empty( $file_contents ) ){
	
			$temp = CREATIVE_ASSETS_TEMP_DIR_PATH . DIRECTORY_SEPARATOR . 'creative__'.uniqid('temp');
			if (!($f = @fopen($temp, 'wb'))) {
				trigger_error("file_put_contents_atomic() : error writing temporary file '$temp'", E_USER_WARNING);
				throw new Exception(_campaign("Error while writing temporary file"));
			}
	
			fwrite($f, $file_contents);
			fclose($f);
	
			$this->logger->debug("@@Temp File name : ".$temp);
			
			$C_template->setTemplateFilePath( $temp );
			
			@chmod($temp, CREATIVE_ASSETS_FILE_PUT_CONTENTS_ATOMIC_MODE);
		}
		$this->logger->debug("@@Create temporary file finish");
	}
	
	/**
	 * 
	 * It checks if any channel attached to other template under the same reference before delete 
	 * @param Template $C_template
	 * @throws Exception
	 * @return boolean
	 */
	private function checkTemplateChannelBeforeDelete( Template $C_template ){
	
		//check client channel for coupon image 
		$status = $this->checkClientChannelBeforeDelete( $C_template );
		if( $status )
			return true;
		
		$channels = $this->channel_types;
		$channels = array_flip( $channels );
		
		$ref_id = $C_template->getRefId();
		$template_id = $C_template->getId();
		$template_type_id = $C_template->getTemplateTypeId();
		$org_id = $C_template->getOrgId();
		
		$ref_filter = " AND tcm.`ref_id` = '$ref_id' ";
		if( $ref_id != -1 )
			$ref_filter = " AND tcm.`ref_id` IN ( '-1','$ref_id' ) ";
		
		$sql = " SELECT tcm.id,tcm.channel_id 
					FROM `creative_assets`.`template_channel_mapping` tcm
				WHERE tcm.`org_id` = '$org_id' AND tcm.`template_type_id` = '$template_type_id'
					AND tcm.`template_id` = '$template_id' $ref_filter ";
		
		$result = $this->C_database->query_hash( $sql , 'id' , 'channel_id' );
		
		if( empty($result) ){
			$this->logger->debug('@@No channel attached with this template');
			return true;
		}
		
		$channel_ids = array_values( $result );
		
		$client_channel_id = $this->channel_types['CLIENT'];
		
		foreach( $channel_ids as $channel_id ){
			
			$temp_filter = " AND tcm.`template_id` != '$template_id' ";
			
			$sql = " SELECT tcm.id FROM `creative_assets`.`template_channel_mapping` tcm
			WHERE tcm.`org_id` = '$org_id' AND tcm.`template_type_id` = '$template_type_id'
			AND tcm.`channel_id` = '".$value['channel_id']."' $ref_filter $temp_filter ";
			
			$result = $this->C_database->query_scalar( $sql );
			
			$channel_type = $channels[$channel_id];
			if( empty( $result ) )
				throw new Exception(_campaign("Atleast one ")."<b>'".strtolower($channel_type)."'</b>"._campaign(" channel should be selected for the coupon series before deleting this template"));
		}
	}
	
	/**
	 *
	 * It checks if client channel attached to other template under the same reference before delete
	 * @param Template $C_template
	 * @throws Exception
	 * @return boolean
	 */
	private function checkClientChannelBeforeDelete( Template $C_template ){
	
		$channel_id = $this->channel_types['CLIENT'];
		
		$channels = $this->channel_types;
		$channels = array_flip( $channels );
		
		$ref_id = $C_template->getRefId();
		$template_id = $C_template->getId();
		$template_type_id = $C_template->getTemplateTypeId();
		$org_id = $C_template->getOrgId();
	
		if( ( $ref_id == -1 ) 
				&& 
			( $template_type_id == $this->template_types['IMAGE'] ) ){
			
			$this->logger->debug('@@It is the general type of coupon image');
			return true;
		}
		
		$sql = " SELECT tcm.id
					FROM `creative_assets`.`template_channel_mapping` tcm
				 WHERE tcm.`org_id` = '$org_id' 
					AND tcm.`template_id` = '$template_id' 
					AND tcm.`channel_id` = '$channel_id' ";
	
		$result = $this->C_database->query_scalar( $sql );
	
		if( empty($result) ){
			$this->logger->debug('@@No client channel attached with this template');
			return false;
		}
	
		$temp_filter = " AND tcm.`template_id` != '$template_id' ";
			
		$html_type_id = $this->template_types['HTML'];
		$image_type_id = $this->template_types['IMAGE'];
		
		if( ( $template_type_id == $image_type_id ) || ( $template_type_id == $html_type_id ) ){

			$ref_filter = " AND tcm.`ref_id` = '$ref_id' ";
			if( $ref_id != -1 ){
				$ref_filter = " AND tcm.`ref_id` IN ( '-1','$ref_id' ) ";
			}
			$sql = " SELECT tcm.id FROM `creative_assets`.`template_channel_mapping` tcm
						WHERE tcm.`org_id` = '$org_id'
						AND tcm.`template_type_id` IN ('$html_type_id','$image_type_id')
						AND tcm.`channel_id` = '$channel_id'
						$ref_filter $temp_filter ";
			
			$result = $this->C_database->query( $sql );
				
			$channel_type = $channels[$channel_id];
			
			if( empty( $result ) )
				throw new Exception("Atleast one <b>'".strtolower($channel_type)."'</b> channel should be selected for the coupon series before deleting this template");
		}
		
		return false;
	}
	
	protected function getGlobalTemplates( $template_type_id ){
		
		$sql = " SELECT * from `templates` WHERE `scope` = 'GLOBAL' 
					AND `template_type_id` = '$template_type_id' ";
		
		return $this->C_database->query( $sql );
	}
	
	protected function addTemplateChannelMapping( 
			$org_id , $template_type_id , $ref_id , $template_id , $channel_id , $updated_by ){
	
		$sql = "INSERT INTO `creative_assets`.`template_channel_mapping`
		(
		template_id,
		template_type_id,
		org_id,
		ref_id,
		channel_id,
		last_updated_by,
		last_updated_on
		)
		VALUES
		(
		'$template_id',
		'$template_type_id',
		'$org_id',
		'$ref_id',
		'$channel_id',
		'$updated_by',
		NOW()
		)ON DUPLICATE KEY UPDATE
		`last_updated_by` = VALUES(`last_updated_by`),
		`last_updated_on` = VALUES(`last_updated_on`)";
	
		$status = $this->C_database->insert( $sql );
	
		if( !$status )
			throw new Exception(_campaign("Error occured while inserting template channel mapping"));
	}
	
	
}
?>