<?php
define("CREATIVE_ASSETS_DIR_PATH", __DIR__ );
define("CREATIVE_ASSETS_TEMP_DIR_PATH", "/mnt/creative_assets_tmp" );
define("CREATIVE_ASSETS_FILE_PUT_CONTENTS_ATOMIC_MODE", 0777);
define("CREATIVE_ASSETS_ZIP_TEMP_DIR_PATH", "/mnt/creative_assets_tmp/zip_upload" );
include_once 'creative_assets/model/class.Template.php';
include_once 'creative_assets/controller/CreativeAssetTypes.php';
include_once 'creative_assets/controller/CreativeAssetsFactory.php';
include_once 'helper/simple_html_dom.php';
include_once 'creative_assets/controller/CreativeAssetFileServiceHandler.php';

/**
 * This will expose all the required methods of creative assets to outside world 
 * @author nayan
 */
class CreativeAssetsManager{
	
	private $logger;
	private $C_database;
	
	public function __construct(){
		global $logger;
		$this->logger = $logger;
		$this->C_database = new Dbase('creative_assets');
		//@chmod(CREATIVE_ASSETS_TEMP_DIR_PATH, CREATIVE_ASSETS_FILE_PUT_CONTENTS_ATOMIC_MODE);
	}
	
	public function processTemplate( $template_name , $file_path , $org_id , $uploaded_by , $asset_type = 'HTML' , $file_contents = false , $ref_id = -1 , $template_id = false , $tag = 'GENERAL' , $scope = 'ORG', $is_favourite = false, $drag_drop_id=false, $base_template_id=-1, $language_id=-1 , $is_deleted = 0){
		
		include_once 'helper/db_manager/TransactionManager.php';
		try{

			//start transaction
			
			$C_transaction_manager = new TransactionManager();
			$C_transaction_manager->beginTransaction();
			
			$C_template = $this->prepareTemplateDetails( $ref_id ,
					$template_name, $file_path, $org_id, $uploaded_by, $asset_type , $file_contents , $template_id , $tag , $scope, $is_favourite, $drag_drop_id, $base_template_id , $language_id , $is_deleted);
			
			$C_asset = CreativeAssetsFactory::getAssetByType( CreativeAssetTypes::valueOf( $asset_type ) );
			
			$C_asset->validate( $C_template );
			
			$C_asset->process( $C_template );
			
			//commit transaction
			$C_transaction_manager->commitTransaction();
			
			return $C_template;
			
		}catch( Exception $e ){

			//roll back transaction
			$C_transaction_manager->rollbackTransaction();
			$this->logger->error( "ROLLING BACK : Exception Was Thrown While processing ".$asset_type." template : ".$e->getMessage() );
			throw new RuntimeException( $e->getMessage( ) );
		}
	}
	
	public function deleteTemplate( $template_id , $org_id , $ref_id , $asset_type = 'HTML' ){
	
		include_once 'helper/db_manager/TransactionManager.php';
		try{
		
			//start transaction
			$C_transaction_manager = new TransactionManager();
			$C_transaction_manager->beginTransaction();
			
			$this->checkTemplateExistForOrg($template_id, $org_id, $ref_id);
			//$this->checkTemplateByRefId( $org_id , $ref_id , $template_id );
			
			$C_asset = CreativeAssetsFactory::getAssetByType( CreativeAssetTypes::valueOf( $asset_type ) );
		
			$C_asset->delete( $org_id , $template_id , $ref_id );
		
			//commit transaction
			$C_transaction_manager->commitTransaction();
		
		}catch( Exception $e ){
		
			//roll back transaction
			$C_transaction_manager->rollbackTransaction();
			$this->logger->error( "ROLLING BACK : Exception Was Thrown While deleting ".$asset_type." template : ".$e->getMessage() );
			throw new RuntimeException( $e->getMessage( ) );
		}
	}
	
	public function setFavouriteTemplate($is_favourite, $template_id, $org_id, $ref_id, $asset_type = 'HTML'){
		include_once 'helper/db_manager/TransactionManager.php';
		try{
		
			//start transaction
			$C_transaction_manager = new TransactionManager();
			$C_transaction_manager->beginTransaction();
			$this->checkTemplateExistForOrg($template_id, $org_id, $ref_id);
			
			$C_asset = CreativeAssetsFactory::getAssetByType( CreativeAssetTypes::valueOf( $asset_type ) );
		
			$status = $C_asset->setFavourite( $is_favourite, $template_id, $asset_type, $org_id, $ref_id);
		
			//commit transaction
			$C_transaction_manager->commitTransaction();
		
		}catch( Exception $e ){
		
			//roll back transaction
			$C_transaction_manager->rollbackTransaction();
			$this->logger->error( "ROLLING BACK : Exception Was Thrown While setting template favourite ".$asset_type." template : ".$e->getMessage() );
			throw new RuntimeException( $e->getMessage( ) );
		}

	}
	
	private function prepareTemplateDetails( 
			$ref_id = -1 , $template_name , $file_path , 
			$org_id , $uploaded_by , $asset_type , $file_contents = false , 
			$template_id = false , $tag = 'GENERAL' , $scope = 'ORG', $is_favourite = false, $drag_drop_id =false, $base_template_id=false, $language_id=-1 , $is_deleted = 0){

		$C_template = new Template();
		if($ref_id == -1){
			$C_template->setIsDefault(1);
		}
		if( $template_id ){
			$C_template->load( $template_id );
			$is_favourite = $C_template->getIsFavourite();
		}
		$C_template->setTemplateName($template_name);
		$C_template->setTag( $tag );
		$C_template->setScope( $scope );
		$C_template->setRefId( $ref_id );
		$C_template->setIsFavourite($is_favourite);
		$C_template->setLastUpdatedBy( $uploaded_by );
		$C_template->setLastUpdatedOn( date('Y-m-d H:i:s') );
		$C_template->setOrgId( $org_id );
		$C_template->setTemplateFilePath( $file_path );
		if($drag_drop_id)
			$C_template->setDragDropId($drag_drop_id);
		if( $file_contents )
			$C_template->setTemplateFileContents( $file_contents );
		if($base_template_id)
			$C_template->setBaseTemplateId( $base_template_id ) ;

		$C_template->setLanguageId( $language_id ) ;
		$C_template->setIsDeleted( $is_deleted ) ;

		return $C_template;
	}
	
	public function getTemplateTypesAsOption(){
	
		$sql = " SELECT id,name FROM `creative_assets`.`template_types` ";
	
		$template_types =
			$this->C_database->query_hash( $sql , 'name' , 'id' );

		$this->logger->debug("template_types_option".print_r($template_types,true));
		
		return $template_types;
	}
	
	public function getChannelTypesAsOption(){
	
		$sql = " SELECT id,type FROM `user_management`.`comm_channels`
					WHERE `is_valid` = '1' ";
	
		$channel_types =
			$this->C_database->query_hash( $sql , 'type' , 'id' );
		
		return $channel_types;
	}
	
	public function getSupportedChannelsAsOptions( $asset_type = 'HTML' ){

		try{
		
			$C_asset = CreativeAssetsFactory::getAssetByType( CreativeAssetTypes::valueOf( $asset_type ) );
		
			$supported_channels = $C_asset->getSupportedChannels();
		
		}catch( Exception $e ){
			throw new RuntimeException( $e->getMessage( ) );
		}
		
		return $supported_channels;
	}
	
	public function setTemplateChannelMapping( $org_id, $ref_id, 
			$template_id, $channel_type , $updated_by ){
	
		include_once 'helper/db_manager/TransactionManager.php';
		try{
	
			//start transaction
			$C_transaction_manager = new TransactionManager();
			$C_transaction_manager->beginTransaction();
			
			$C_template = new Template();
			$C_template->load( $template_id );
			
			$template_type_id = $C_template->getTemplateTypeId();
			
			$channel_types = $this->getChannelTypesAsOption();
			$channel_id = $channel_types[ strtoupper( $channel_type ) ];
			
			$this->checkTemplateByRefId( $org_id , $ref_id , $template_id );
			
			$map_id =
				$this->checkChannelIsAlreadyAssigned(
					$org_id, $template_type_id, $ref_id, $channel_id, $template_id);
			
			if( $map_id ){
				$this->deleteChannelMappingByMapid( $map_id );	
			}
			
			$this->addTemplateChannelMapping(
					$org_id, $template_type_id, $ref_id, $template_id, $channel_id, $updated_by);

			if( $ref_id != -1 )
				$this->setReissualText( $org_id , $ref_id , $channel_type );
			
			//commit transaction
			$C_transaction_manager->commitTransaction();
			
		}catch( Exception $e ){
			//roll back transaction
			$this->logger->error('@@@Exception:'.$e->getMessage());
			$C_transaction_manager->rollbackTransaction();
			throw new RuntimeException( $e->getMessage( ) );
		}
	}
	
	public function unSetTemplateChannelMapping( $org_id, $ref_id, 
			$template_id, $channel_type ){
	
		include_once 'helper/db_manager/TransactionManager.php';
		try{
	
			//start transaction
			$C_transaction_manager = new TransactionManager();
			$C_transaction_manager->beginTransaction();
			
			$C_template = new Template();
			$C_template->load( $template_id );
			
			$template_type_id = $C_template->getTemplateTypeId();
			
			$channel_types = $this->getChannelTypesAsOption();
			$channel_id = $channel_types[ strtoupper( $channel_type ) ];
			
			$this->checkTemplateByRefId( $org_id , $ref_id , $template_id );
			
			$map_id = $this->checkForChannelAvailability(
					$org_id, $ref_id, $channel_id, $template_type_id, $template_id);
			
			if( empty($map_id) ){
				throw new Exception(_campaign("Atleast one")." <b>'".strtolower($channel_type)."'</b>"._campaign(" channel should be selected for the coupon series"));	
			}
			
			$this->deleteTemplateChannelMapping(
					$org_id, $template_type_id, $ref_id, $template_id, $channel_id);
	
			if( $ref_id != -1 )
				$this->setReissualText( $org_id , $ref_id , $channel_type );
			
			//commit transaction
			$C_transaction_manager->commitTransaction();
			
		}catch( Exception $e ){
			//roll back transaction
			$this->logger->error('@@@Exception:'.$e->getMessage());
			$C_transaction_manager->rollbackTransaction();
			throw new RuntimeException( $e->getMessage( ) );
		}
	}
	
	private function addTemplateChannelMapping( $org_id , $template_type_id , $ref_id , $template_id , $channel_id , $updated_by ){
	
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
	
	private function deleteTemplateChannelMapping( $org_id , $template_type_id , $ref_id , $template_id , $channel_id ){
	
		$sql = "DELETE FROM `creative_assets`.`template_channel_mapping`
					WHERE template_id = '$template_id' AND template_type_id = '$template_type_id'
				AND org_id = '$org_id' AND ref_id = '$ref_id' AND channel_id = '$channel_id' ";
	
		$status = $this->C_database->update( $sql );
	
		if( !$status )
			throw new Exception(_campaign('Error occured while deleting template channel mapping'));
	}
	
	private function checkChannelIsAlreadyAssigned( $org_id , $template_type_id , $ref_id , $channel_id , $template_id ){
	
		$temp_filter = " AND tcm.`template_id` != '$template_id' ";
		
		$channels = $this->getChannelTypesAsOption();
		$channels = array_flip( $channels );
		
		$sql = " SELECT tcm.id FROM `creative_assets`.`template_channel_mapping` tcm
					WHERE tcm.`org_id` = '$org_id' AND tcm.`template_type_id` = '$template_type_id'
						AND tcm.`ref_id` = '$ref_id' AND tcm.`channel_id` = '$channel_id' $temp_filter ";
		
		if( $channels[ $channel_id ] == 'CLIENT' ){
			
			$sql = " SELECT tcm.id FROM `creative_assets`.`template_channel_mapping` tcm
						WHERE tcm.`org_id` = '$org_id' AND tcm.`ref_id` = '$ref_id' 
					 AND tcm.`channel_id` = '$channel_id' $temp_filter ";
		}
		
		return $this->C_database->query_scalar( $sql );
	}
	
	private function deleteChannelMappingByMapid( $id ){
	
		$sql = "DELETE FROM `creative_assets`.`template_channel_mapping`
					WHERE id = '$id' ";
	
		$status = $this->C_database->update( $sql );
	
		if( !$status )
			throw new Exception(_campaign("Error occured while deleting template channel mapping"));
	}
	
	public function setAsDefaultTemplate( $org_id , $template_id , $ref_id , $updated_by ){
		
		include_once 'helper/db_manager/TransactionManager.php';
		try{
		
			//start transaction
			$C_transaction_manager = new TransactionManager();
			$C_transaction_manager->beginTransaction();
				
			$C_template = new Template();
			$C_template->load( $template_id );
			
			$C_template->setOrgId( $org_id );
			$C_template->setRefId( $ref_id );
			$C_template->setLastUpdatedBy( $updated_by );
			$C_template->setLastUpdatedOn( date('Y-m-d H:i:s') );
			
			$template_type_id = $C_template->getTemplateTypeId();
			$template_types = $this->getTemplateTypesAsOption();
			$template_types = array_flip( $template_types );
			$asset_type = $template_types[ $template_type_id ];
			
			$C_asset = CreativeAssetsFactory::getAssetByType( CreativeAssetTypes::valueOf( $asset_type ) );
		
			$C_asset->makeDefault( $C_template );
		
			//commit transaction
			$C_transaction_manager->commitTransaction();
			
			return $C_template;
				
		}catch( Exception $e ){
			//roll back transaction
			$this->logger->error('@@@Exception:'.$e->getMessage());
			$C_transaction_manager->rollbackTransaction();
			throw new RuntimeException( $e->getMessage( ) );
		}
	}
	
	public function copyTemplateToOtherReference( $org_id , $template_id , $updated_by , $ref_id = -1 ){
		
		include_once 'helper/db_manager/TransactionManager.php';
		try{
		
			//start transaction
			$C_transaction_manager = new TransactionManager();
			$C_transaction_manager->beginTransaction();
		
			$C_template = new Template();
			$C_template->load( $template_id );
			
			$C_template->setOrgId( $org_id );
			$C_template->setRefId( $ref_id );
			$C_template->setLastUpdatedBy( $updated_by );
			$C_template->setLastUpdatedOn( date('Y-m-d H:i:s') );
				
			$template_type_id = $C_template->getTemplateTypeId();
			$template_types = $this->getTemplateTypesAsOption();
			$template_types = array_flip( $template_types );
			$asset_type = $template_types[ $template_type_id ];
			
			$C_asset = CreativeAssetsFactory::getAssetByType( CreativeAssetTypes::valueOf( $asset_type ) );
		
			$C_asset->copyToReference( $C_template );
		
			//commit transaction
			$C_transaction_manager->commitTransaction();
				
			return $C_template;
		
		}catch( Exception $e ){
			//roll back transaction
			$this->logger->error('@@@Exception:'.$e->getMessage());
			$C_transaction_manager->rollbackTransaction();
			throw new RuntimeException( $e->getMessage( ) );
		}
	}
	
	public function getDetailsByTemplateId( $template_id ){
		
		$C_template = new Template();
		$C_template->load( $template_id );
		
		$types = $this->getTemplateTypesAsOption();
		$types = array_flip( $types );
		
		$asset_type = $types[$C_template->getTemplateTypeId()];
		
		$C_asset = CreativeAssetsFactory::getAssetByType( 
				CreativeAssetTypes::valueOf( $asset_type ) );
		
		return $C_asset->preview( $C_template );
	}
	
	public function getAllTemplates( $org_id , $asset_type = 'HTML' , $scope = 'ORG', $tag = 'GENERAL' , $account_id = -20 ){
		// $org_id = 780;
	
		$types = $this->getTemplateTypesAsOption();
	
		$template_type_id = $types[ strtoupper($asset_type) ];
		
		$this->logger->debug('template_type_id'.print_r($template_type_id,true));

		$where_filter = '';
		if( $tag )
			$where_filter = "AND t.tag = '$tag'";
		
		$sql = " SELECT t.id as template_id,t.template_name,t.file_service_params,t.is_preview_generated,t.tag,
				t.scope AS scope,t.is_favourite AS is_favourite
					FROM `creative_assets`.`templates` t
					JOIN `creative_assets`.`org_templates` ot
						ON ot.org_id = '$org_id' AND t.id = ot.template_id AND ot.`ref_id` = '$account_id'
				WHERE t.`template_type_id` = '$template_type_id' AND t.`is_deleted` = '0' 
					AND t.`scope` = '$scope' $where_filter ORDER BY t.`last_updated_on` DESC ";		

		$result = $this->C_database->query( $sql );
		$this->logger->debug("@@#######sikricreativeassetsmanager".print_r( $result , true ) );
		$this->logger->debug('template_type_result'.print_r($result[0],true));
		$template_list = array();
		if( !empty($result) ){
								
			foreach( $result as $key => $data ){
				$templates = array();
				$templates['template_id'] = $data['template_id'];
				$templates['template_name'] = $data['template_name'];
				$templates['tag'] = $data['tag'];
				$templates['is_preview_generated'] = $data['is_preview_generated'];

				$templates['is_favourite'] = $data['is_favourite'];
				$templates['scope'] = $data['scope'];
				$file_service_params = $data['file_service_params'];
				$file_service_params = json_decode( $file_service_params , true );
				$this->logger->debug('file_service_params'.print_r($file_service_params,true));
				$templates['file_size'] = $file_service_params['file_size'];
				
				if( $asset_type === 'TEXT' )
					$templates['content'] = rawurldecode( $file_service_params['text_content'] );
				else if( $asset_type === 'HTML' ){
					$templates['content'] = _campaign("Preview is being generated.")." <br/>"._campaign("Please Refresh this page after 2 minutes to see the preview!");
					if( $data['is_preview_generated'] == 1 )
						$templates['content'] = $file_service_params['preview_http_url'];
				}else if( $asset_type === 'IMAGE' ){
					$templates['content'] = $file_service_params['file_http_url'];
					$templates['preview_url'] = $file_service_params['preview_http_url'];
				}else if( $asset_type === 'WECHAT_TEMPLATE' ){
					$templates['content'] = rawurldecode( $file_service_params['content'] );
					$templates['templates1'][0] = ($file_service_params);
					$templates['file_service_params'] = ($file_service_params);
					//$templates['content'] = rawurldecode( $file_service_params['preview'] );
				} else if( $asset_type === 'WECHAT_SINGLE_TEMPLATE'  || $asset_type === 'WECHAT_MULTI_TEMPLATE') {
					$templates['content'] = $file_service_params;
				}
				elseif ($asset_type==='MOBILEPUSH_TEMPLATE' || $asset_type==='MOBILEPUSH_IMAGE') {
					$templates['content'] = $file_service_params;
					
				}
				else {
					throw new Exception(_campaign("Invalid creative asset type passed").$asset_type);
				}
				array_push( $template_list , $templates );
			}
		}
		$this->logger->debug('template_type_id_list'.print_r($template_list,true));
		return $template_list;
	}


	public function getAllTemplatesCreativeAssets( $org_id , $asset_type = 'HTML' , $scope = 'ORG', $tag = 'GENERAL' , $account_id = -20 ){
		// $org_id = 780;
	
		if(strcasecmp("wechat_dvs",$scope)==0 || strcasecmp("wechat_loyalty",$scope)==0){
			return $this->getAllTemplates($org_id, $asset_type, $scope, $tag, $account_id);
		}

		$types = $this->getTemplateTypesAsOption();
	
		$template_type_id = $types[ strtoupper($asset_type) ];
		
		$this->logger->debug('template_type_id'.print_r($template_type_id,true));

		$where_filter = '';
		if( $tag )
			$where_filter = "AND t.tag = '$tag'";
		
		$sql = " SELECT t.id as template_id,t.template_name,t.file_service_params,t.is_preview_generated,t.tag,
				t.scope AS scope,t.is_favourite AS is_favourite
					FROM `creative_assets`.`templates` t
					JOIN `creative_assets`.`org_templates` ot
						ON ot.org_id = '$org_id' AND t.id = ot.template_id AND ot.`ref_id` = '$account_id'
				WHERE t.`template_type_id` = '$template_type_id' AND t.`is_deleted` = '0' 
					$where_filter ORDER BY t.`last_updated_on` DESC ";		

		$result = $this->C_database->query( $sql );
		$this->logger->debug("@@#######sikricreativeassetsmanager".print_r( $result , true ) );
		$this->logger->debug('template_type_result'.print_r($result[0],true));
		$template_list = array();
		if( !empty($result) ){
								
			foreach( $result as $key => $data ){
				$templates = array();
				$templates['template_id'] = $data['template_id'];
				$templates['template_name'] = $data['template_name'];
				$templates['tag'] = $data['tag'];
				$templates['is_preview_generated'] = $data['is_preview_generated'];

				$templates['is_favourite'] = $data['is_favourite'];
				$templates['scope'] = $data['scope'];
				$file_service_params = $data['file_service_params'];
				$file_service_params = json_decode( $file_service_params , true );
				$this->logger->debug('file_service_params'.print_r($file_service_params,true));
				$templates['file_size'] = $file_service_params['file_size'];
				
				if( $asset_type === 'TEXT' )
					$templates['content'] = rawurldecode( $file_service_params['text_content'] );
				else if( $asset_type === 'HTML' ){
					$templates['content'] = _campaign("Preview is being generated.")." <br/>"._campaign("Please Refresh this page after 2 minutes to see the preview!");
					if( $data['is_preview_generated'] == 1 )
						$templates['content'] = $file_service_params['preview_http_url'];
				}else if( $asset_type === 'IMAGE' ){
					$templates['content'] = $file_service_params['file_http_url'];
					$templates['preview_url'] = $file_service_params['preview_http_url'];
				}else if( $asset_type === 'WECHAT_TEMPLATE' ){
					$templates['content'] = rawurldecode( $file_service_params['content'] );
					$templates['templates1'][0] = ($file_service_params);
					$templates['file_service_params'] = ($file_service_params);
					//$templates['content'] = rawurldecode( $file_service_params['preview'] );
				} else if( $asset_type === 'WECHAT_SINGLE_TEMPLATE'  || $asset_type === 'WECHAT_MULTI_TEMPLATE') {
					$templates['content'] = $file_service_params;
				}
				elseif ($asset_type==='MOBILEPUSH_TEMPLATE' || $asset_type==='MOBILEPUSH_IMAGE') {
					$templates['content'] = $file_service_params;
					
				}
				else {
					throw new Exception(_campaign("Invalid creative asset type passed").$asset_type);
				}
				array_push( $template_list , $templates );
			}
		}
		$this->logger->debug('template_type_id_list'.print_r($template_list,true));
		return $template_list;
	}
	
	/**
	 * returning all templates as options
	 * @param unknown $org_id
	 * @param string $asset_type
	 * @throws Exception
	 * @return multitype:
	 */
	public function getAllTemplatesAsOptions( $org_id , $asset_type = 'HTML' , $scope = 'ORG' ){
	
		$types = $this->getTemplateTypesAsOption();
	
		$template_type_id = $types[ strtoupper($asset_type) ];
	
		$sql = " SELECT t.id as template_id,t.template_name
				 FROM `creative_assets`.`templates` t
				 JOIN `creative_assets`.`org_templates` ot
				 ON ot.org_id = '$org_id' AND t.id = ot.template_id AND ot.`ref_id` = '-20'
				 WHERE t.`template_type_id` = '$template_type_id' AND t.`is_deleted` = '0' 
					AND t.`scope` = '$scope' ORDER BY t.`last_updated_on` DESC ";
	
		$result = $this->C_database->query_hash($sql, 'template_name', 'template_id');
		return $result;
	}
	
	/**
	 * 
	 * returning all templates as options by tag
	 * @param unknown $org_id
	 * @param string $asset_type
	 * @param string $tag
	 * @return Ambigous <NULL, multitype:unknown >
	 */
	public function getAllTemplatesByTagAsOptions( $org_id , $asset_type = 'HTML' , $tag = 'BASIC' , $scope = 'ORG' ){
	
		$types = $this->getTemplateTypesAsOption();
	
		$template_type_id = $types[ strtoupper($asset_type) ];
	
		$sql = " SELECT t.id as template_id,t.template_name
				 FROM `creative_assets`.`templates` t
				 WHERE t.`template_type_id` = '$template_type_id' AND t.tag = '$tag' 
				 	AND t.`is_deleted` = '0' AND t.`scope` = '$scope' 
				 ORDER BY t.`last_updated_on` DESC ";
	
		$result = $this->C_database->query_hash( $sql , 'template_name' , 'template_id' );
	
		return $result;
	}
	
	public function getAllOrgCouponTemplates( $org_id , $ref_id = -1 , $asset_type = 'TEXT' , $scope = 'COUPON_SERIES' ){
	
		$asset_type = strtoupper($asset_type);
		
		$types = $this->getTemplateTypesAsOption();
		$channels = $this->getSupportedChannelsAsOptions( $asset_type );
		$template_type_id = $types[ $asset_type ];
		
		$ref_filter = " AND ot.`ref_id` = '$ref_id' ";
		if( $ref_id == -1 )
			$ref_filter = " AND ( ot.`ref_id` = '$ref_id' OR t.`is_default` = '1' ) ";
		
		$sql = " SELECT t.id as template_id,t.template_name,t.file_service_params,t.is_preview_generated,t.tag
					FROM `creative_assets`.`templates` t
				JOIN `creative_assets`.`org_templates` ot
					ON ot.org_id = '$org_id' AND t.id = ot.template_id
				WHERE t.`template_type_id` = '$template_type_id' 
					AND t.`is_deleted` = '0' AND t.`scope` = '$scope' $ref_filter ORDER BY t.`last_updated_on` DESC";
		
		$result = $this->C_database->query( $sql );
		
		$template_list = array();
		if( !empty($result) ){
			$template_ids = array();
			foreach( $result as $key => $data ){
				$templates = array();
				$templates['template_id'] = $data['template_id'];
				$templates['template_name'] = $data['template_name'];
				$templates['tag'] = $data['tag'];
				
				$file_service_params = $data['file_service_params'];
				$file_service_params = json_decode( $file_service_params , true );
		
				$templates['file_size'] = $file_service_params['file_size'];
				
				if( $asset_type === 'TEXT' )
					$templates['content'] = rawurldecode( $file_service_params['text_content'] );
				else if( $asset_type === 'HTML' ){
					$templates['content'] = _campaign("Preview is being generated.")." <br/>"._campaign("Please Refresh this page after 2 minutes to see the preview!");
					if( $data['is_preview_generated'] )
						$templates['content'] = $file_service_params['preview_http_url'];
				}else if( $asset_type === 'IMAGE' ){
					$templates['content'] = $file_service_params['file_http_url'];
					$templates['preview_url'] = $file_service_params['preview_http_url'];
				}else{
					throw new Exception(_campaign("Invalid creative asset type passed"));
				}
				array_push( $template_ids , $data['template_id'] );
				array_push( $template_list , $templates );
			}
		}
		
		if( empty($template_list) ){
			$this->logger->debug("No ".strtolower($asset_type)." coupon templates found");
			$templates = $this->getChannelDefaultTemplatesByAssetType($org_id,$ref_id,$asset_type);
			return $templates;
		}
		
		$sql = " SELECT tcm.template_id,tcm.channel_id
					FROM `creative_assets`.`template_channel_mapping` tcm
				 WHERE tcm.org_id = '$org_id' AND tcm.template_id IN ('".implode( "','" , $template_ids ) ."') 
					AND tcm.ref_id = '$ref_id' AND tcm.template_type_id = '$template_type_id' ";
		
		$result = $this->C_database->query( $sql );
		
		$channels_set = $channels;
		
		if( !empty($result) ){
			$cnt = 0;
			foreach( $template_list as $key => $data ){
				
				$template_channels = array();
				foreach( $result as $in_key => $in_data ){
					
					if( $data['template_id'] == $in_data['template_id'] ){
						foreach( $channels as $key1 => $value ){
							if( $value == $in_data['channel_id'] ){
								$template_channels[$key1] = true;
								unset( $channels_set[$key1] );
							}
							else{
								if( !isset($template_channels[$key1]) )
									$template_channels[$key1] = false;
							}
							$template_list[$cnt]['channels'] = $template_channels;
						}
					}
				}
				$cnt++;
			}
		}
		
		if( !empty($channels_set) && $ref_id != -1 ){
			$template = $this->getChannelDefaultTemplatesByAssetType($org_id,$ref_id,$asset_type,$channels_set);
			if( !empty($template) ){
				foreach( $template as $data ){
					array_push( $template_list , $data );
				}
			}
		}
		
		$template_list = $this->reorderTemplatesByDefaultSettings( $template_list );
		
		return $template_list;
	}
	
	public function getTemplateDetailsByChannelType( $org_id , $ref_id = -1 , $asset_type = 'HTML' , $channel_type = 'EMAIL' , $scope = 'COUPON_SERIES' ){
		
		$channels = $this->getChannelTypesAsOption();
		$channel_id = $channels[ strtoupper($channel_type) ];
		
		$template_types = $this->getTemplateTypesAsOption();
		$template_type_id = $template_types[ strtoupper($asset_type) ];
		
		$filter = " AND tcm.ref_id = '$ref_id' ";
		if( $ref_id == -1 )
			$filter = " AND ( tcm.`ref_id` = '-1' OR t.`is_default` = '1' ) ";
		
		$sql = " SELECT t.id as template_id
					FROM `creative_assets`.`template_channel_mapping` tcm
				 JOIN `creative_assets`.`templates` t 
					ON t.id = tcm.template_id AND t.template_type_id = tcm.template_type_id
				 JOIN `creative_assets`.`org_templates` ot 
					ON ot.org_id = tcm.org_id AND ot.template_id = tcm.template_id AND ot.ref_id = tcm.ref_id
				 WHERE tcm.`template_type_id` = '$template_type_id' 
					AND tcm.`channel_id` = '$channel_id' AND tcm.org_id = '$org_id' 
					AND t.`is_deleted` = '0' AND t.`scope` = '$scope' $filter ";

		$template_id = $this->C_database->query_scalar( $sql );
		
		if( $ref_id != -1 && empty( $template_id ) ){
				
			$sql = " SELECT t.id as template_id
			FROM `creative_assets`.`template_channel_mapping` tcm
			JOIN `creative_assets`.`templates` t
			ON t.id = tcm.template_id AND t.template_type_id = tcm.template_type_id
			JOIN `creative_assets`.`org_templates` ot
			ON ot.org_id = tcm.org_id AND ot.template_id = tcm.template_id AND ot.ref_id = tcm.ref_id
			WHERE tcm.`template_type_id` = '$template_type_id' 
				AND tcm.`channel_id` = '$channel_id' AND tcm.org_id = '$org_id' 
				AND t.`is_deleted` = '0' AND t.`scope` = '$scope' 
				AND ( tcm.`ref_id` = '-1' OR t.`is_default` = '1' )";
		
			$template_id = $this->C_database->query_scalar( $sql );
		}
		
		$contents = array();
		if( !empty($template_id) ){
			$contents = $this->getDetailsByTemplateId( $template_id );
			$contents['template_id'] = $template_id;
		}
		return $contents;
	}
	
	public function getTemplateByChannelsPreview( $org_id , $ref_id = -1 , $asset_type = 'HTML' , $channel_type = 'EMAIL' , $scope = 'COUPON_SERIES' ){
	
		$channels = $this->getChannelTypesAsOption();
		$channel_id = $channels[ strtoupper($channel_type) ];
	
		$template_types = $this->getTemplateTypesAsOption();
		$template_type_id = $template_types[ strtoupper($asset_type) ];
		$template_types = array_flip( $template_types );
		
		$filter = " AND tcm.ref_id = '$ref_id' ";
		if( $ref_id == -1 )
			$filter = " AND ( tcm.`ref_id` = '-1' OR t.`is_default` = '1' ) ";
		
		$sql = " SELECT t.id as template_id
					FROM `creative_assets`.`template_channel_mapping` tcm
				JOIN `creative_assets`.`templates` t
					ON t.id = tcm.template_id AND t.template_type_id = tcm.template_type_id
				JOIN `creative_assets`.`org_templates` ot
					ON ot.org_id = tcm.org_id AND ot.template_id = tcm.template_id AND ot.ref_id = tcm.ref_id
				WHERE tcm.`template_type_id` = '$template_type_id' 
					AND tcm.`channel_id` = '$channel_id' AND tcm.org_id = '$org_id' 
					AND t.`is_deleted` = '0' AND t.`scope` = '$scope' $filter ";
			
		$template_id = $this->C_database->query_scalar( $sql );
		
		if( $ref_id != -1 && empty( $template_id ) ){
			
			$sql = " SELECT t.id as template_id
						FROM `creative_assets`.`template_channel_mapping` tcm
					JOIN `creative_assets`.`templates` t
						ON t.id = tcm.template_id AND t.template_type_id = tcm.template_type_id
					JOIN `creative_assets`.`org_templates` ot
						ON ot.org_id = tcm.org_id AND ot.template_id = tcm.template_id
					WHERE tcm.`template_type_id` = '$template_type_id' 
						AND tcm.`channel_id` = '$channel_id' AND tcm.org_id = '$org_id' 
						AND t.`is_deleted` = '0' AND t.`scope` = '$scope' 
						AND  tcm.`ref_id` = '-1' AND t.`is_default` = '1' ";
				
			$template_id = $this->C_database->query_scalar( $sql );
		}
	
		$templates = array();
		if( !empty($template_id) ){
			
			$C_template = new Template();
			$C_template->load( $template_id );
			
			$templates['template_id'] = $C_template->getId();
			$templates['template_name'] = $C_template->getTemplateName();
			$templates['tag'] = $C_template->getTag();
			
			
			$file_service_params = $C_template->getFileServiceParams();
			$file_service_params = json_decode( $file_service_params , true );
			
			$templates['file_size'] = $file_service_params['file_size'];
			
			$template_type_id = $C_template->getTemplateTypeId();
			$asset_type = $template_types[ $template_type_id ];
			
			if( $asset_type === 'TEXT' )
				$templates['content'] = rawurldecode( $file_service_params['text_content'] );
			else if( $asset_type === 'HTML' ){
				$templates['content'] = _campaign("Preview is being generated.")." <br/>"._campaign("Please Refresh this page after 2 minutes to see the preview!");
				$templates["is_preview_generated"] = $C_template->getIsPreviewGenerated();
				if( $C_template->getIsPreviewGenerated() == 1 )
					$templates['content'] = $file_service_params['preview_http_url'];
			}else if( $asset_type === 'IMAGE' ){
				$templates['content'] = $file_service_params['file_http_url'];
			}
		}
		return $templates;
	}
	
	private function getChannelDefaultTemplatesByAssetType( $org_id , $ref_id , $asset_type , $channels = array() ){
		
		$asset_type = strtoupper($asset_type);
		
		$types = $this->getTemplateTypesAsOption();
		$template_type_id = $types[ $asset_type ];
		
		if( empty( $channels ) )
			$channels = $this->getSupportedChannelsAsOptions( $asset_type );
		
		$templates = array();
		$cnt = 0;
		$template_ids = array();
		foreach( $channels as $key => $value ){
			
			$template = $this->getTemplateByChannelsPreview( $org_id , -1 , $asset_type , $key );
			if( !empty($template) ){
				
				if( ( $ref_id != -1 ) && ( $key == 'CLIENT' ) ){
					$result = 
						$this->checkForClientChannelAvailability(
								$org_id,$ref_id,$template['template_id']);
					
					if( !empty( $result ) )
						continue;
				}
				
				if( in_array($template['template_id'],$template_ids) ){
					$templates[$cnt]['channels'][$key] = true;
					continue;
				}
				$template['channels'][$key] = true;
				array_push( $templates , $template );
				array_push( $template_ids , $template['template_id'] );
				$this->logger->debug("@@Template1:".print_r( $templates , true ) );
			}
		}
		return $templates;
	}
	
	private function checkForChannelAvailability( $org_id , $ref_id , $channel_id , $template_type_id , $template_id ){
		
		$temp_filter = " AND tcm.`template_id` != '$template_id' ";
		
		$ref_filter = " AND tcm.`ref_id` = '$ref_id' ";
		if( $ref_id != -1 )
			$ref_filter = " AND tcm.`ref_id` IN ( '-1','$ref_id' ) ";

		$channels = $this->getChannelTypesAsOption();
		$channels = array_flip( $channels );
		
		$sql = " SELECT tcm.id FROM `creative_assets`.`template_channel_mapping` tcm
					WHERE tcm.`org_id` = '$org_id' AND tcm.`template_type_id` = '$template_type_id'
				 AND tcm.`channel_id` = '$channel_id' $ref_filter $temp_filter ";
		
		if( $channels[ $channel_id ] == 'CLIENT' ){
				
			$sql = " SELECT tcm.id FROM `creative_assets`.`template_channel_mapping` tcm
						WHERE tcm.`org_id` = '$org_id' AND tcm.`channel_id` = '$channel_id' 
						$ref_filter $temp_filter ";
		}
		
		return $this->C_database->query_scalar( $sql );
	}
	
	private function checkForClientChannelAvailability( $org_id , $ref_id , $template_id ){
	
		
		$temp_filter = " AND tcm.`template_id` != '$template_id' ";
	
		$ref_filter = " AND tcm.`ref_id` = '$ref_id' ";
	
		$channels = $this->getChannelTypesAsOption();
		$channel_id = $channels['CLIENT'];
		
		$sql = " SELECT tcm.id FROM `creative_assets`.`template_channel_mapping` tcm
					WHERE tcm.`org_id` = '$org_id' AND tcm.`channel_id` = '$channel_id'
				 $ref_filter $temp_filter ";
	
		return $this->C_database->query_scalar( $sql );
	}
	
	/**
	 * 
	 * It creates all the three types global templates for the org
	 * @param int $org_id
	 * @param int $created_by
	 */
	public function createGlobalCouponTemplates( $org_id , $created_by ){
		
		include_once 'helper/db_manager/TransactionManager.php';
	
		try{
			$this->logger->debug('@@@In Create Global Coupon Templates');
			//start transaction
			$C_transaction_manager = new TransactionManager();
			$C_transaction_manager->beginTransaction();
		
			$asset_types = $this->getTemplateTypesAsOption();
			$asset_types = array_keys( $asset_types );
			 
			foreach( $asset_types as $key => $asset_type ){
				
				$C_asset = CreativeAssetsFactory::getAssetByType( CreativeAssetTypes::valueOf( $asset_type ) );
				$C_asset->createGlobalCouponTemplates( $org_id , $created_by );
			}
			
			//commit transaction
			$C_transaction_manager->commitTransaction();
				
			$this->logger->debug('@@@Finish Create Global Coupon Templates');
			
		}catch( Exception $e ){
			//roll back transaction
			$this->logger->error('@@@Create Global Coupon Templates:'.$e->getMessage());
			$C_transaction_manager->rollbackTransaction();
			throw new RuntimeException( $e->getMessage( ) );
		}
	}
	
	/**
	 * 
	 * It will set the reissual text in coupon series sms_template field
	 * @param int $ref_id
	 * @param string $sms_template
	 */
	public function setReissualText( $org_id , $ref_id , $channel_type ){
		
		if( $channel_type != 'RE_ISSUAL_TEXT' ){
			return;	
		}
		
		$this->logger->debug('@@Start setting reissual text for ref_id:'.$ref_id.' and org_id:'.$org_id);
		
		$template = $this->getTemplateByChannelsPreview( 
						$org_id , $ref_id , 'TEXT' , 'RE_ISSUAL_TEXT' );

		if( !empty( $template ) ){
			
			$sms_template = $template['content'];

			include_once 'helper/coupons/CouponSeriesManager.php';
			$C_coupon_series = new CouponSeriesManager();
			$C_coupon_series->loadById( $ref_id );
			
			$details = $C_coupon_series->getDetails();
			
			$details['sms_template'] = addslashes( $sms_template );
			
			$C_coupon_series->updateDetails( $details );
		}
		
		$this->logger->debug('@@End');
	}
	
	/**
	 * 
	 * It checks if the global coupons templates are available for the org 
	 * @param int $org_id
	 */
	public function globalTemplatesExists( $org_id , $created_by ){
		
		$sql = " SELECT count(*)
					FROM `creative_assets`.`templates` t
				 JOIN `creative_assets`.`org_templates` ot
					ON ot.template_id = t.id
				 WHERE t.`is_deleted` = '0' AND t.`scope` = 'COUPON_SERIES' 
					AND ot.org_id = '$org_id' AND ot.ref_id = '-1' ";
		
		$templates = $this->C_database->query_scalar( $sql );
		
		if( $templates > 0 ){
			$this->logger->debug('Global Templates are already there for org :'.$org_id);
			return true;
		}
		
		try{
			$this->createGlobalCouponTemplates( $org_id , $created_by );
			return true;
		}catch(Exception $e){
			$this->logger->error('@@Global Default Templates Error:'.$e->getMessage());
			return false;
		}
	}
	
	private function checkTemplateByRefId( $org_id , $ref_id , $template_id ){
		
		if( $ref_id != -1 ){

			$sql = " SELECT t.id as template_id
			FROM `creative_assets`.`templates` t
			JOIN `creative_assets`.`org_templates` ot
			ON ot.org_id = '$org_id' AND t.id = ot.template_id AND ot.`ref_id` = '$ref_id'
			WHERE t.`id` = '$template_id' ";
			
			$result = $this->C_database->query_scalar( $sql );
			
			if( empty( $result ) )
				throw new Exception(_campaign("This type of operation is not allowed on global template."));
		}
	}
	
	/**
	 * 
	 * It will reorder the list before sending it to the dashboard
	 * @param array $template_list
	 */
	private function reorderTemplatesByDefaultSettings( $template_list ){
		
		$final_list = array();
		if( !empty( $template_list ) ){
			$rest_list = array();
			foreach( $template_list as $key => $template ){
				if( isset($template['channels']) )
					array_push( $final_list , $template );
				else
					array_push( $rest_list , $template );
			}
			$final_list = array_merge( $final_list , $rest_list );
		}
		return $final_list;
	}
	
	//creating html template using zip folder which contains an html files and images map to each other.
	public function createTemplateFromZip( $file_path , $params ){
		
			global $currentorg,$currentuser;

			$html_template = '';
			$zip = new ZipArchive();
			$image_to_url_mapping = array();
			$path = CREATIVE_ASSETS_ZIP_TEMP_DIR_PATH;
			$valid_exts = array( "jpg", "gif", "png","bmp","jpeg" );
			
			$replaced_html = $this->validateZipFileForHtmlUpload( $file_path );
			
			if( $zip->open( $file_path )){
		
				$zip->extractTo( $path );
				$img_replace_array = array();
		
				for( $i = 0; $i < $zip->numFiles; $i++ ){
			
					$info = $zip->statIndex( $i );
					$ext = end( explode( "." , basename( $info['name'] ) ) );
					$file_name = $path.'/'.$info['name'];
					$folder = explode( '/', $info['name'] );
					$original_name = $info['name'];
					
// 					@chmod( $path.'/'.$folder[0] , CREATIVE_ASSETS_FILE_PUT_CONTENTS_ATOMIC_MODE );
			
					if( in_array( $ext,$valid_exts ) && !is_dir( $file_name )){
						$timestamp = strtotime( date('Y-m-d H:i:s'));
						$info = pathinfo( $file_name );
						$new_filename = $info['filename'].'__'.$timestamp.'.'.$info['extension'];

// 						@chmod( $file_name , CREATIVE_ASSETS_FILE_PUT_CONTENTS_ATOMIC_MODE );
						try{
						
							$path_info = pathinfo( $original_name );
							$time = strtotime( date('Y-m-d H:i:s'));
							$image_name = $path_info['filename'].'__'.$time.'.'.$path_info['extension'];
							
							if( !isset( $image_to_url_mapping[$info['basename']] )){
								$template = $this->processTemplate(
																	$image_name,
																	$file_name,
																	$currentorg->org_id ,
																	$currentuser->user_id,
																	'IMAGE' ,
																	false ,
																	$params['ref_id'],
																	$params['temp_id'],
																	'GENERAL',
																	$params['scope']
															);
								
								$json_data = json_decode( $template->getFileServiceParams() , true);
								$image_to_url_mapping[$info['basename']] = $json_data['file_http_url'];
							}
						}catch( Exception $e ){
							$this->logger->debug('@@@EXCEPTION:'.$e->getMessage());
							//throw new Exception( $e->getMessage() );
						}
					}elseif ( $ext == 'html' || $ext == 'htm' && !is_dir( $file_name ) ){
						$this->logger->debug('@@@Html template file found :-'.$file_name );
						if( !$html_template )
							$html_template = file_get_contents( $file_name );
					}
					@unlink( $file_name );
				}
				@rmdir( $path.'/'.$folder[0] );
				$zip->close();
		}else
			throw new Exception(_campaign("Invalid file type added! Please Give a proper zip file"));
		
		//replacing all image src that contains images of zip file with mapping array constructed;
		foreach ( $image_to_url_mapping as $key => $value ){
			$replaced_html = str_ireplace( "{{".$key."}}", $value, $replaced_html );
		}
		
		$this->logger->debug('@@@IMAGE: FILE CONTENT:-'.$replaced_html);
		
		return $replaced_html;
	}
	
	//Validating zip file for the html zip upload
	private function validateZipFileForHtmlUpload( $file_path ){
		
		$zip = new ZipArchive();
		$extension_array = array();
		$path = CREATIVE_ASSETS_ZIP_TEMP_DIR_PATH;
		
		if( $zip->open( $file_path )){
			$zip->extractTo( $path );
			for( $i = 0; $i < $zip->numFiles; $i++ ){
				$info = $zip->statIndex( $i );
				$file_name = $path.'/'.$info['name'];

				if( !is_dir( $file_name ) ){
					$valid_exts = array( "jpg", "gif", "png","bmp","jpeg" );
					$ext = strtolower( end( explode( "." , basename( $info['name'] ) ) ));
					$extension_array[] = $ext;
					
					if( $ext == 'html' || $ext == 'html' ){
						if( !$file_content )
							$file_content = file_get_contents( $file_name );
					}elseif(in_array( $ext, $valid_exts )){
						$img_name = explode('/', $info['name']);
						$actual_name = $img_name[count($img_name) - 1];
						$zip_img[$actual_name] = $info['name'];
					}
				}
			}
			$zip->close();
		}
		
		$html = str_get_html( $file_content );
		$this->logger->debug('@@@FILE CONTENT:-'.$html);
		foreach($html->find('img') as $element){
			$pathinfo = pathinfo( $element->src );
			$file_path_exists = $path.DIRECTORY_SEPARATOR.$zip_img[$pathinfo['basename']];
			if( $zip_img[$pathinfo['basename']] ){
				if( file_exists( $file_path_exists ) ){
					$element->src = "{{".$pathinfo['basename']."}}";
				}
			}
		}
		$this->logger->debug('@@@FILE CONTENT:-'.$html);
		
		$valid_exts = array( "jpg", "gif", "png","bmp","jpeg" );
		$html_exts = array( "html", "htm");
		
		$img_flag = false;
		$html_flag = false;
		
		foreach ( $extension_array as $key => $value ){
			
			$this->logger->debug('@@@EXTENSION:-'.$value);
			
			if( in_array($value, $valid_exts ))
				$img_flag = true;
			
			if( in_array($value, $html_exts ))
				$html_flag = true;	
		}
		
		if( !$html_flag )
			throw new Exception(_campaign("Zip File does not contain HTML file."));
		
		if( !$img_flag )
			throw new Exception(_campaign("Zip File does not contain images file."));
		
		$html = preg_replace('/<!--(.|\s)*?-->/', '', $html);
		
		return $html;
	}
	
	/**
	 * getting org template by tag
	 * @param unknown $tag
	 */
	public function getTemplateByTag( $tag , $asset_type = 'HTML' , $org_id = false ){
		
		$types = $this->getTemplateTypesAsOption();
		$template_type_id = $types[ strtoupper($asset_type) ];
		
		$filter = "";
		if( $org_id ){
			$filter = "JOIN `creative_assets`.`org_templates` ot
				ON ot.org_id = '$org_id' AND t.id = ot.template_id";
		}

		$sql = "SELECT t.id , t.template_name,t.tag,t.file_service_params,t.is_preview_generated
				FROM `creative_assets`.templates t $filter		
				WHERE t.tag='$tag' AND t.template_type_id='$template_type_id' AND t.`is_deleted` = '0'
				ORDER BY t.`last_updated_on` DESC
			";
		
		$result = $this->C_database->query( $sql );
		
		$data_array = array();
		foreach ( $result as $row ){
			$params = json_decode( $row['file_service_params'] , true );
			$preview_url = ( $params['preview_http_url'] ) ? $params['preview_http_url'] 
								: _campaign("Preview is being generated.")." <br/>"._campaign("Please Refresh this page after 5 minutes to see the preview!");
			
			if( $tag == 'SMS_TEXT' || $tag == 'CALL_TASK_TEXT' || $tag=='DVS_SMS_TEXT')
				$preview_url = rawurldecode( $params['text_content'] );
			
			array_push($data_array, array( 'template_id' => $row['id'] , 
										   'template_name' => $row['template_name'],
										   'content' => $preview_url,
										   'is_preview_generated' => $row['is_preview_generated']
										 )
					  );
		}
		return $data_array;
	}
	
	public function generatePreviewUrl( $content, $is_secure = false ){
		
		$file_path = CREATIVE_ASSETS_TEMP_DIR_PATH;
		$file_name = uniqid('preview_html__');
		$file_name = $file_path.'/'.$file_name;
		
		if (!($f = @fopen($file_name, 'wb'))) {
			trigger_error("file_put_contents_atomic() : error writing temporary file '$temp'", E_USER_WARNING);
			throw new Exception(_campaign("Error while writing temporary file"));
		}else{
			
			fwrite($f, $content);
			fclose($f);
			@chmod($file_name, CREATIVE_ASSETS_FILE_PUT_CONTENTS_ATOMIC_MODE);
			
			$this->logger->debug('@@@Preview File Name:-'.$file_name);
			$params = CreativeAssetFileServiceHandler::addTemplateToFileService( $file_name, $is_secure );
		}
		
		$this->logger->debug('@@@PARAMS:-'.print_r( $params , true ));
		return $params;
	}

	public function getTemplateForPreviewGeneration(){
		
		$sql = " 
				SELECT t.id as template_id, `ot`.`org_id`
				FROM `creative_assets`.`templates` t
				JOIN `org_templates` AS `ot` ON ( `ot`.`template_id` = `t`.`id` )	
						WHERE t.`template_type_id` = '2' AND t.`is_deleted` = '0'
							AND t.is_preview_generated = '0' ORDER BY t.`last_updated_on` DESC LIMIT 5";

		return ShardedDbase::queryAllShards('creative_assets', $sql);
	}

	public function getAllOrgCouponTemplatesWithLimit( $org_id , $ref_id = -1 , $asset_type = 'TEXT' , $scope = 'COUPON_SERIES' , $start_limit = 0 , $limit = 5 , $search = false ){
	
		$asset_type = strtoupper($asset_type);
		
		$types = $this->getTemplateTypesAsOption();
		$channels = $this->getSupportedChannelsAsOptions( $asset_type );
		$template_type_id = $types[ $asset_type ];
		
		$where = "";
		if ( isset( $search ) && $search != false ){
			$where = " AND (";
			$where .= " t.template_name LIKE '%".$search."%' ";
			$where .= ')';
		}

		$ref_filter = " AND ot.`ref_id` = '$ref_id' ";
		if( $ref_id == -1 )
			$ref_filter = " AND ( ot.`ref_id` = '$ref_id' OR t.`is_default` = '1' ) ";
		
		$sql = " SELECT t.id as template_id,t.template_name,t.file_service_params,t.is_preview_generated,t.tag
					FROM `creative_assets`.`templates` t
				JOIN `creative_assets`.`org_templates` ot
					ON ot.org_id = '$org_id' AND t.id = ot.template_id
				WHERE t.`template_type_id` = '$template_type_id' 
					AND t.`is_deleted` = '0' AND t.`scope` = '$scope' $ref_filter $where ORDER BY t.`last_updated_on` DESC LIMIT $start_limit,$limit";
		
		$result = $this->C_database->query( $sql );
		
		$template_list = array();

		if( $start_limit > 0 && empty( $result ) ){
			return $template_list;
		}
		
		if( !empty($result) ){
			$template_ids = array();
			foreach( $result as $key => $data ){
				$templates = array();
				$templates['template_id'] = $data['template_id'];
				$templates['template_name'] = $data['template_name'];
				$templates['tag'] = $data['tag'];
				
				$file_service_params = $data['file_service_params'];
				$file_service_params = json_decode( $file_service_params , true );
		
				$templates['file_size'] = $file_service_params['file_size'];
				
				if( $asset_type === 'TEXT' )
					$templates['content'] = rawurldecode( $file_service_params['text_content'] );
				else if( $asset_type === 'HTML' ){
					$templates['content'] = _campaign("Preview is being generated.")." <br/>"._campaign("Please Refresh this page after 2 minutes to see the preview!");
					if( $data['is_preview_generated'] )
						$templates['content'] = $file_service_params['preview_http_url'];
				}else if( $asset_type === 'IMAGE' ){
					$templates['content'] = $file_service_params['file_http_url'];
					$templates['preview_url'] = $file_service_params['preview_http_url'];
				}else if( $asset_type === 'WECHAT' ){
					$templates['content'] = rawurldecode( $file_service_params['text_content'] );
				}else{
					throw new Exception(_campaign("Invalid creative asset type passed"));
				}
				array_push( $template_ids , $data['template_id'] );
				array_push( $template_list , $templates );
			}
		}		

		if( empty($template_list) ){
			$this->logger->debug("No ".strtolower($asset_type)." coupon templates found");
			$templates = $this->getChannelDefaultTemplatesByAssetType($org_id,$ref_id,$asset_type);
			return $templates;
		}
		
		$sql = " SELECT tcm.template_id,tcm.channel_id
					FROM `creative_assets`.`template_channel_mapping` tcm
				 WHERE tcm.org_id = '$org_id' AND tcm.template_id IN ('".implode( "','" , $template_ids ) ."') 
					AND tcm.ref_id = '$ref_id' AND tcm.template_type_id = '$template_type_id' ";
		
		$result = $this->C_database->query( $sql );
		
		$channels_set = $channels;
		
		if( !empty($result) ){
			$cnt = 0;
			foreach( $template_list as $key => $data ){
				
				$template_channels = array();
				foreach( $result as $in_key => $in_data ){
					
					if( $data['template_id'] == $in_data['template_id'] ){
						foreach( $channels as $key1 => $value ){
							if( $value == $in_data['channel_id'] ){
								$template_channels[$key1] = true;
								unset( $channels_set[$key1] );
							}
							else{
								if( !isset($template_channels[$key1]) )
									$template_channels[$key1] = false;
							}
							$template_list[$cnt]['channels'] = $template_channels;
						}
					}
				}
				$cnt++;
			}
		}
		
		if( !empty($channels_set) && $ref_id != -1 ){
			$template = $this->getChannelDefaultTemplatesByAssetType($org_id,$ref_id,$asset_type,$channels_set);
			if( !empty($template) ){
				foreach( $template as $data ){
					array_push( $template_list , $data );
				}
			}
		}
		
		$template_list = $this->reorderTemplatesByDefaultSettings( $template_list );
		
		return $template_list;
	}

	public function getAllTemplatesWithLimit( $org_id , $asset_type = 'HTML' , $scope = 'ORG' ,$account_id, $start_limit = 0 , $limit = 5 , $search = false, $is_favourite = false ){
		$account_id = ($account_id) ? $account_id : -20;
		$types = $this->getTemplateTypesAsOption();
	
		$template_type_id = $types[ strtoupper($asset_type) ];
		
		$where = "";
		
		if ( isset( $search ) && $search != false ){
			$where = " AND (";
			$where .= " t.template_name LIKE '%".$search."%' ";
			$where .= ')';
		}
		

		$where_filter = '';
		if( $tag )
			$where_filter = "AND t.tag = '$tag'";
		
		if($is_favourite){
			$where_filter .= "AND t.is_favourite = '$is_favourite'";
		}
		
		/*$sql = " SELECT t.id as template_id,t.template_name,t.file_service_params,t.is_preview_generated,t.tag, t.scope, t.is_favourite
					FROM `creative_assets`.`templates` t
					JOIN `creative_assets`.`org_templates` ot
						ON ot.org_id = '$org_id' AND t.id = ot.template_id AND ot.`ref_id` = '-20'
				WHERE t.`template_type_id` = '$template_type_id' AND t.`is_deleted` = '0' 
					AND t.`scope` = '$scope' $where_filter $where ORDER BY t.`last_updated_on` DESC LIMIT $start_limit,$limit";*/

		$sql = "SELECT a.template_id,IFNULL(b.linked_templates,0) linked_templates,a.template_name,
					b.secondary_template_group,b.lang_id_group,a.file_service_params,a.is_preview_generated,
					a.tag, a.scope, a.is_favourite
					FROM
					( 
					SELECT t.id as template_id,t.template_name,t.file_service_params,t.is_preview_generated,
					t.tag, t.scope, t.is_favourite,t.last_updated_on
					FROM `creative_assets`.`templates` t
					JOIN 
					`creative_assets`.`org_templates` ot
					ON ot.org_id = '$org_id' 
						AND t.id = ot.template_id 
						AND ot.`ref_id` = '$account_id'
					WHERE t.`template_type_id` = '$template_type_id' 
						AND t.`is_deleted` = '0' 
						AND t.`scope` = '$scope'
						AND t.parent_id = -1 $where_filter $where 
				) as a 
				LEFT JOIN 
				(

					SELECT parent_id, COUNT( parent_id ) linked_templates, 
					GROUP_CONCAT( template_id ORDER BY template_id SEPARATOR  ',' ) secondary_template_group, 
					GROUP_CONCAT( lang_id ORDER BY template_id SEPARATOR  ',') lang_id_group
					FROM  `creative_assets`.`templates` tpl 
					JOIN  
					`creative_assets`.`org_templates` org_tpl 
					ON ( tpl.id = org_tpl.template_id ) 
					WHERE parent_id <> -1
						AND is_deleted =0
					GROUP BY parent_id
					) as b
				ON a.template_id = b.parent_id
			    ORDER BY last_updated_on DESC LIMIT $start_limit,$limit
			    ";
		$this->logger->debug('ashish : ' . $sql);
		$result = $this->C_database->query( $sql );
		
		$template_list = array();
		if( !empty($result) ){
								
			foreach( $result as $key => $data ){
				$templates = array();
				$templates['template_id'] = $data['template_id'];
				$templates['template_name'] = $data['template_name'];
				$templates['tag'] = $data['tag'];
				$templates['is_preview_generated'] = $data['is_preview_generated'];
				$templates['scope'] = $data['scope'];
				$templates['is_favourite'] = $data['is_favourite'];
				$templates['linked_templates'] = $data['linked_templates']+1;
				$templates['secondary_template_group'] = $data['secondary_template_group'];
				$templates['lang_id_group'] = $data['lang_id_group'];

				$file_service_params = $data['file_service_params'];
				$file_service_params = json_decode( $file_service_params , true );
				$templates['file_size'] = $file_service_params['file_size'];
				
				if( $asset_type === 'TEXT' )
					$templates['content'] = rawurldecode( $file_service_params['text_content'] );
				else if( $asset_type === 'HTML' ){
					$templates['content'] = _campaign("Preview is being generated.")." <br/>"._campaign("Please Refresh this page after 2 minutes to see the preview!");
					if( $data['is_preview_generated'] == 1 )
						$templates['content'] = $file_service_params['preview_http_url'];
					if($file_service_params['drag_drop_id'])
						$templates['drag_drop_id'] = $file_service_params['drag_drop_id'];
				}else if( $asset_type === 'IMAGE' ){
					$templates['content'] = $file_service_params['file_http_url'];
					$templates['preview_url'] = $file_service_params['preview_http_url'];
					$templates['file_size'] = $file_service_params['file_size'];
				} else if( $asset_type === 'WECHAT_TEMPLATE' ){
					$templates['content'] = rawurldecode( $file_service_params['text_content'] );
				} else if( $asset_type === 'WECHAT_SINGLE_TEMPLATE' ) {
					$templates['content'] = $file_service_params;
				}
				else{
					throw new Exception(_campaign("Invalid creative asset type passed"));
				}
				array_push( $template_list , $templates );
			}
		}
		return $template_list;
	}

	public function getShowMoreTemplates( $org_id , $type , $ref_id , $version , $start_limit ){

		$template_list = array();
		
		switch( $type ){

				case 'HTML':
					$template_list = $this->getAllTemplatesWithLimit( $org_id , 'HTML' , 'ORG' , $start_limit , 3 );
					$C_asset = CreativeAssetsFactory::getAssetByType( CreativeAssetTypes::valueOf( 'HTML' ) );
					return $C_asset->getShowMoreTemplates( $template_list , $ref_id , $version , $type );
					
				case 'IMAGE':
					$template_list = $this->getAllTemplatesWithLimit( $org_id , 'IMAGE' , 'ORG' , $start_limit , 15 );
					$C_asset = CreativeAssetsFactory::getAssetByType( CreativeAssetTypes::valueOf( 'IMAGE' ) );
					return $C_asset->getShowMoreTemplates( $template_list , $ref_id , $version , $type );

				case 'COUPON-HTML':
					$template_list = $this->getAllOrgCouponTemplatesWithLimit( $org_id , $ref_id , 'HTML' , 'COUPON_SERIES' , $start_limit , 3 );
					$C_asset = CreativeAssetsFactory::getAssetByType( CreativeAssetTypes::valueOf( 'HTML' ) );
					return $C_asset->getShowMoreCouponTemplates( $template_list , $ref_id , $version );

				case 'COUPON-TEXT':
					$template_list = $this->getAllOrgCouponTemplatesWithLimit( $org_id , $ref_id , 'TEXT' , 'COUPON_SERIES' , $start_limit , 3 );
					$C_asset = CreativeAssetsFactory::getAssetByType( CreativeAssetTypes::valueOf( 'TEXT' ) );
					return $C_asset->getShowMoreCouponTemplates( $template_list , $ref_id , $version );
					
				case 'COUPON-IMAGE':
					$template_list = $this->getAllOrgCouponTemplatesWithLimit( $org_id , $ref_id , 'IMAGE' , 'COUPON_SERIES' , $start_limit , 3 );
					$C_asset = CreativeAssetsFactory::getAssetByType( CreativeAssetTypes::valueOf( 'IMAGE' ) );
					return $C_asset->getShowMoreCouponTemplates( $template_list , $ref_id , $version );
			}
	}
	
	public function checkTemplateExistForOrg($template_id, $org_id, $ref_id=false){
		$ref_condition = '';
		if($ref_id)
			$ref_condition = "AND ot.ref_id = '$ref_id'";
		$sql = " SELECT t.id FROM `creative_assets`.`templates` t 
					INNER JOIN `creative_assets`.`org_templates` ot ON t.id = ot.template_id 
					WHERE t.id = '$template_id' AND ot.org_id = '$org_id' $ref_condition";
		$result = $this->C_database->query_scalar($sql);
		if(!$result){
			throw new Exception(_campaign("Template doesn't belong to this organization"));
		}
	}
	
	public function getDefaultTemplates(){
		$sql = "SELECT * FROM `creative_assets`.`templates` WHERE tag = 'EDM_DEFAULT' AND `is_deleted` = 0 ORDER BY `templates`.`template_name` ASC";
		$result = $this->C_database->query($sql);
		$templates = array();
		$is_edm_enabled = true;
		$C_edm_manager = new EdmManager();
		$edm_user_id = $C_edm_manager->getEdmUserId();
		if($edm_user_id){
			$is_edm_enabled = true;
		}else {
			$is_edm_enabled = false;
		}


		foreach ($result as $row){ 
			$template[ 'template_id' ] = $row['id'];
	 		$template[ 'name' ] = $row['template_name'];
	 		$template[ 'html_content' ] = "";
 			$template[ 'is_preview_generated' ] = $row['is_preview_generated'];
 			$file_service_params = json_decode( $row['file_service_params'], true);
 			if($template[ 'is_preview_generated' ])	 			
 				$template[ 'preview_url' ] = $file_service_params['preview_http_url'];
 			else
 				$template[ 'preview_url' ] = "";
 			if($row['is_favourite'])
 				$template[ 'is_favourite' ] = true;
 			else
 				$template[ 'is_favourite' ] = false;
 			
			if($file_service_params['drag_drop_id'] && $row['tag'] == 'EDM_DEFAULT' && $is_edm_enabled){
				$template['is_drag_drop'] = true;
				$template['drag_drop_id'] = $file_service_params['drag_drop_id'];
		 	} else{
		 		$template['is_drag_drop'] = false;
				$template['drag_drop_id'] = '';
		 	}
 			
 			$template['scope'] = $row['scope'];
 			$template['tag'] = $row['tag'];
 			$template['is_default'] = true;
			
 			$templates[] = $template;
		}
		return $templates;
	}

	public function uploadImage($file){
		global $currentuser, $currentorg;
		$user_id = $currentuser->user_id;
		$org_id = $currentorg->org_id;
		try{
			$this->logger->debug('sambhav Image Upload On Upload button click'.print_r($file,true));

			$temp_name = $file['tmp_name'];
			$image_name = $file['name'];
			$temp_name = $this->generateFileFromUrl( $temp_name, $image_name);
			
			$valid_exts = array( "jpg", "gif", "png","bmp","jpeg" , "zip");
			$path_info = pathinfo( $image_name );

			$ext = $path_info['extension'];
			if( !in_array( $ext,$valid_exts ) )
				throw new Exception(_campaign('File you have upload is not valid image file!'));

			
			$time = strtotime( date('Y-m-d H:i:s'));
			$image_name = $path_info['filename'].'__'.$time.'.'.$path_info['extension'];
			
			$this->logger->debug("@@@File Temp Name $temp_name File Name is $image_name");

			$result = $this->processTemplate( 
				$image_name, 
				$temp_name, 
				$org_id , 
				$user_id , 
				'IMAGE' , 
				false , 
				-20,
				"",
				'GENERAL',
				'ORG'
			);

			if( $result ){
				$image_info = array();
				$image_info['name'] = $result->getTemplateName();
				$image_info['template_id'] = $result->getId();
				$image_info['is_favourite'] = $result->getIsFavourite();
				$image_info['tag'] = $result->getTag();
				$image_info['scope'] = $result->getScope();
				$image_info['is_preview_generated'] = $result->getIsPreviewGenerated();
				$file_service_params = json_decode($result->getFileServiceParams(),true);
				$image_info['preview_url'] = $file_service_params['preview_http_url'];
				$image_info['image_url'] = $file_service_params['file_http_url'];
				$image_info['public_url'] = $file_service_params['public_url'];
				$image_info['file_size'] = round($file_service_params['file_size']/1024);	
			}
			else{
				throw new Exception(_campaign('An error occurred while uploading an image'));
			}
			return $image_info;

		}catch( Exception $e ){
			$this->logger->debug("");	
			throw new Exception($e->getMessage());
		}	
		
	}


	/**
	 * generate file from the url.
	 * @param unknown $url
	 */
	private function generateFileFromUrl( $url, $image_name ){
		if( $url ){
			 
			$file = file_get_contents( $url );
			
			if( $file ){ 
				$directory = CREATIVE_ASSETS_TEMP_DIR_PATH.DIRECTORY_SEPARATOR."images".DIRECTORY_SEPARATOR;
				$this->logger->debug('@@@Found Directory :'.$directory);
				$valid_exts = array( "jpg", "gif", "png","bmp","jpeg" );
				$ext = end( explode( "." , strtolower( basename( $image_name ) ) ) );
				if( in_array( $ext,$valid_exts ) ){
					$ext = $this->getImageExtension( $image_name );
					$newfile = $directory . basename( $url ).'.'.$ext;
					file_put_contents( $newfile , $file );
					@chmod( $newfile , CREATIVE_ASSETS_FILE_PUT_CONTENTS_ATOMIC_MODE );
					return $newfile;
				}else 
					throw new Exception( _campaign('Invalid file type. Please try another file.') );
			}else 
				throw new Exception( _campaign('Could not locate the file:').' '.$url);
		}else
			throw new Exception(_campaign('Invalid URL entered. Please try again.') );
	}
	
	private function getImageExtension( $image_name ){
		return end( explode( "." , strtolower( basename( $image_name ) ) ) );
	}

	//function accepts an array and returns the QR code array respectively
	public function getQRcodeTemplates( $tags ){

		if(empty($tags)){
			$this->logger->debug('tag name:'.print_r($tag_name,true));
			return $tags;
		}

		global $campaign_cfg;
		$template_list = array();
		foreach($tags as $tag_key=>$tag_value){

			$this->logger->debug('tags'.$tag_key." ".$tag_value);

			$view_url = "{{domain}}/intouchqrservice.php?cqr=".$tag_key;

    		$this->logger->debug('view_url'.$view_url);
    	
    		$view_url = Util::templateReplace( $view_url , 
    			array('domain'=>$campaign_cfg['track-url-prefix']['view-in-browser'] ) );
    		//view_url = http://devint.capillary.in/intouchqrservice.php?cqr={{voucher}}

			$templates = array(
			               "template_name"=>$tag_value,
			               "tag"=>"GENERAL",
			               "is_preview_generated"=>"true",
			               "scope"=>"ORG",
			               "is_favourite"=>true,
						   "content"=>$view_url,
						   "preview_url"=>$view_url);

			array_push( $template_list , $templates );
		}
		return $template_list;
	}

	public function getTemplateByParentId($org_id , $parent_template_id , $scope="ORG" , $asset_type="HTML"){
		$sql = "SELECT * FROM `creative_assets`.`templates` AS t , `creative_assets`.`org_templates` AS ot
				WHERE `t`.`parent_id`=$parent_template_id AND `ot`.`template_id`=`t`.`id` AND `ot`.`org_id`=$org_id
				AND `t`.`scope`='$scope' AND `t`.`is_deleted`=0" ;

		$result = $this->C_database->query( $sql );		
		$templates = array() ;
		$i = 0 ;
		foreach ($result as $key => $data) {
			$template = array();
			$template['template_id'] = $data['template_id'];
			$template['name'] = $data['template_name'];
			$template['tag'] = $data['tag'];
			$template['is_preview_generated'] = $data['is_preview_generated'];
			$template['scope'] = $data['scope'];
			$template['is_favourite'] = $data['is_favourite'];
			$file_service_params = $data['file_service_params'];
			$file_service_params = json_decode( $file_service_params , true );
	
			$template['file_size'] = $file_service_params['file_size'];
			$template['is_default'] = true ;
			if(!$data['is_default'])
				$template['is_default'] = false;			
			$template['base_template_id'] = $data['parent_id'];			
			$template['ref_id'] = $data['ref_id'] ;
            $template['language_id'] = $data['lang_id'] ;    
            $template['is_multi_lang'] = 1 ;        
			
			$template['content'] = "No content to display" ;
			if( $data['is_preview_generated'] == 1 )
				$template['content'] = $file_service_params['preview_http_url'];
			if($file_service_params['drag_drop_id']){
				$template['drag_drop_id'] = $file_service_params['drag_drop_id'];
				$template['is_drag_drop'] = true ;
			}

			$templates[] = $template ;
		}

		$this->logger->debug("the templates are : ".print_r($templates,true));
		return $templates;
	}

	public function saveMultiImageBroadcastTemplate($templateData,$template_id = false) {
		global $currentuser, $currentorg;
		$user_id = $currentuser->user_id;

		return $this->processTemplate($templateData['TemplateName'], $templateData, $currentorg->org_id, $user_id ,'WECHAT_MULTI_TEMPLATE', false, $templateData['AccountId'], $template_id, 'GENERAL','WECHAT');
	}

	public function saveSingleImageBroadcastTemplate($templateData,$template_id=null) {
		global $currentuser, $currentorg;
		$user_id = $currentuser->user_id;
		return $this->processTemplate($templateData['template_name'], $templateData, $currentorg->org_id, $user_id, 'WECHAT_SINGLE_TEMPLATE', false, $templateData['AccountId'], $template_id, 'GENERAL', 'WECHAT');
	 }

	public function getSecondaryIOS($licenseCode){	
	include_once 'business_controller/webeEngage/WebEngageController.php';	
	$web_engage_controller = new WebEngageController();
	$res = $web_engage_controller->getSecondaryIOS($licenseCode);
	$this->logger->debug("creative assets getSecondaryIOS".print_r($res,true));
	return $res;
	} 

}
?>
