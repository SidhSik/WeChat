<?php
/*
*
* -------------------------------------------------------
* CLASSNAME:        BulkMessage
* GENERATION DATE:  21.12.2012
* CREATED BY:       Prakhar Verma ( P.V )
* FOR MYSQL TABLE:  message_queue
* FOR MYSQL DB:     msging
*
*/

//**********************
// CLASS DECLARATION
//**********************

class BulkMessage
{


	// **********************
	// ATTRIBUTE DECLARATION
	// **********************

	protected $id;   // KEY ATTR. WITH AUTOINCREMENT

	protected $guid;
	protected $type;
	protected $org_id;
	protected $campaign_id;
	protected $group_id;
	protected $scheduled_on;
	protected $scheduled_by;
	protected $last_updated_on;
	protected $approved_by;
	protected $default_arguments;
	protected $status;
	protected $scheduled_type;
	protected $params;
	protected $Approved;

	private $hours;
	private $minutes;
	private $day;
	private $month;
	private $week;
	private $message;
	private $cron_hours;
	private $cron_minutes;
	private $org_credits;
	private $date_field;
	private $extra_params;
	private $subject;
	private $description;
	private $store_type;
	
	protected $database; // Instance of class database

	protected $table = 'message_queue';
	private $current_org_id;
	
	//**********************
	// CONSTRUCTOR METHOD
	//**********************

	function BulkMessage()
	{	
		global $currentorg;
		$this->current_org_id = $currentorg->org_id;
		
		$this->database = new Dbase( 'msging' );

	}


	// **********************
	// GETTER METHODS
	// **********************


	function getId()
	{	
		return $this->id;
	}

	function getGuid()
	{	
		return $this->guid;
	}

	function getType()
	{	
		return $this->type;
	}

	function getOrgId()
	{	
		return $this->org_id;
	}

	function getCampaignId()
	{	
		return $this->campaign_id;
	}

	function getGroupId()
	{	
		return $this->group_id;
	}

	function getScheduledOn()
	{	
		return $this->scheduled_on;
	}

	function getScheduledBy()
	{	
		return $this->scheduled_by;
	}

	function getLastUpdatedOn()
	{	
		return $this->last_updated_on;
	}

	function getApprovedBy()
	{	
		return $this->approved_by;
	}

	function getDefaultArguments()
	{	
		return $this->default_arguments;
	}

	function getStoreType()
	{	
		return $this->store_type;
	}

	function getStatus()
	{	
		return $this->status;
	}

	function getScheduledType()
	{	
		return $this->scheduled_type;
	}

	function getReminderScheduledType(){

		if( strtoupper( $this->scheduled_type ) == 'SCHEDULED' )
			return 'SCHEDULE';
		else
			return $this->scheduled_type;
	}
	
	function getParams()
	{	
		return $this->params;
	}

	function getReminderParams(){

		return 
			array(
				'send_when' => $this->getReminderScheduledType(),
				'date_field' => $this->getDateField(),
				'hours' => $this->getHours(),
				'minutes' => $this->getMinutes(),
				'cron_day' => $this->getDay(),
				'cron_month' => $this->getMonth(),
				'cron_week' => $this->getWeek(),
				'cron_hours' => $this->getCronHours(),
				'cron_minutes' => $this->getCronMinutes()	
			);
	}
	
	function getApproved()
	{	
		return $this->Approved;
	}
	
	function getHours(){
		return $this->hours;
	}
	
	function getMinutes(){
		return $this->minutes;
	}
	
	function getDay(){
		return $this->day;
	}
	
	function getMonth(){
		return $this->month;
	}
	
	function getWeek(){
		return $this->week;
	}
	
	function getMessage(){
		return $this->message;
	}
	
	function getCronHours(){
		return $this->cron_hours;
	}
	
	function getCronMinutes(){
		return $this->cron_minutes;
	}
	
	function getOrgCredits(){
		return $this->org_credits;
	}
	
	function getDateField(){
		return $this->date_field;
	}
	
	function getExtraParams(){
		return $this->extra_params;
	}
	
	function getSubject(){
		return $this->subject;		
	}
	
	function getDescription(){
		return $this->description;
	}
	// **********************
	// SETTER METHODS
	// **********************


	function setId( $id )
	{
		$this->id =  $id;
	}

	function setGuid( $guid )
	{
		$this->guid =  $guid;
	}

	function setType( $type )
	{
		$this->type =  $type;
	}

	function setOrgId( $org_id )
	{
		$this->org_id =  $org_id;
	}

	function setCampaignId( $campaign_id )
	{
		$this->campaign_id =  $campaign_id;
	}

	function setGroupId( $group_id )
	{
		$this->group_id =  $group_id;
	}

	function setScheduledOn( $scheduled_on )
	{
		$this->scheduled_on =  $scheduled_on;
	}

	function setScheduledBy( $scheduled_by )
	{
		$this->scheduled_by =  $scheduled_by;
	}

	function setLastUpdatedOn( $last_updated_on )
	{
		$this->last_updated_on =  $last_updated_on;
	}

	function setApprovedBy( $approved_by )
	{
		$this->approved_by =  $approved_by;
	}

	function setDefaultArguments( $default_arguments )
	{
		$this->default_arguments =  $default_arguments;
	}

	function setStatus( $status )
	{
		$this->status =  $status;
	}

	function setStoreType( $store_type )
	{	
		$store_type = ( !$store_type )?( 'registered_store' ):( $store_type );
		$this->store_type = $store_type;
	}

	function setScheduledType( $scheduled_type )
	{
		$this->scheduled_type =  $scheduled_type;
	}

	function setParams( $params )
	{
		$this->params =  $params;
	}

	function setApproved( $Approved )
	{
		$this->Approved =  $Approved;
	}

	function setHours( $hours ){
		
		$this->hours = $hours;
	}
	
	function setMinutes( $minutes ){
		
		$this->minutes = $minutes;
	}
	
	function setDay( $day ){
		
		$this->day = $day;
	}
	
	function setMonth( $month ){
		
		$this->month = $month;
	}
	
	function setWeek( $week ){
		
		$this->week = $week;
	}
	
	function setMessage( $message ){
		
		$this->message = $message;
	}
	
	function setCronHours( $cron_hours ){
		
		$this->cron_hours = $cron_hours;
	}
	
	function setCronMinutes( $cron_minutes ){
		
		$this->cron_minutes = $cron_minutes;
	}
	
	function setOrgCredits( $org_credits ){
		
		$this->org_credits = $org_credits;
	}
	
	function setDateField( $date_field ){
		
		$this->date_field = $date_field;
	}
	
	function setExtraParams( $params ){
		
		$this->extra_params = $params;
	}
	
	function setSubject( $subject ){
		$this->subject = $subject;
	}
	
	function setDescription( $description ){
		$this->description = $description;
	}
	
	// **********************
	// SELECT METHOD / LOAD
	// **********************

	function load( $id )
	{

		$sql =  "SELECT * FROM message_queue WHERE id = $id AND org_id = $this->current_org_id";
		$result =  $this->database->query( $sql );
		
		$ObjectTransformer = DataTransformerFactory::getDataTransformerClass( 'Object' );
		$row = $ObjectTransformer->doTransform( $result[0] );
	
		$this->id = $row->id;
		$this->guid = $row->guid;
		$this->type = $row->type;
		$this->org_id = $row->org_id;
		$this->campaign_id = $row->campaign_id;
		$this->group_id = $row->group_id;
		$this->scheduled_on = $row->scheduled_on;
		$this->scheduled_by = $row->scheduled_by;
		$this->last_updated_on = $row->last_updated_on;
		$this->approved_by = $row->approved_by;
		$this->default_arguments = $row->default_arguments;
		$this->status = $row->status;
		$this->scheduled_type = $row->scheduled_type;
		$this->params = $row->params;
		$this->Approved = $row->approved;
	}
	
	function loadByCampaignID( $org_id, $campaign_id ){
	
		if( !$campaign_id ) return;
	
		$sql =  " SELECT * FROM message_queue
		WHERE org_id = '$org_id' AND campaign_id = '$campaign_id' ";
		$result =  $this->database->query( $sql );
	
		return $result;
	}
	
	// **********************
	// INSERT
	// **********************

	function insert()
	{

		$this->id = ""; // clear key for autoincrement
		
		$this->default_arguments = mysql_escape_string( $this->default_arguments );
		$this->params = mysql_escape_string( $this->params );

		$sql =  "

			INSERT INTO message_queue 
			( 
				guid,
				type,
				org_id,
				campaign_id,
				group_id,
				scheduled_on,
				scheduled_by,
				last_updated_on,
				approved_by,
				default_arguments,
				status,
				scheduled_type,
				params,
				Approved 
			) 
			VALUES 
			( 
				'$this->guid',
				'$this->type',
				'$this->org_id',
				'$this->campaign_id',
				'$this->group_id',
				'$this->scheduled_on',
				'$this->scheduled_by',
				'$this->last_updated_on',
				'$this->approved_by',
				'$this->default_arguments',
				'$this->status',
				'$this->scheduled_type',
				'$this->params',
				'$this->Approved' 
			)";
		
		$result = $this->id = $this->database->insert( $sql );
		
		$changes = $this->getChangesToTrack('insert');
		$this->raiseEvent( $changes , $result );
		
		return $result;
	}
	
	// **********************
	// INSERT With Id
	// **********************


	function insertWithId()
	{

		$this->default_arguments = mysql_escape_string( $this->default_arguments );
		$this->params = mysql_escape_string( $this->params );
		
		$sql =  "

			INSERT INTO message_queue 
			( 
				id,
				guid,
				type,
				org_id,
				campaign_id,
				group_id,
				scheduled_on,
				scheduled_by,
				last_updated_on,
				approved_by,
				default_arguments,
				status,
				scheduled_type,
				params,
				Approved 

			) 

			VALUES 
			( 
				'$this->id',
				'$this->guid',
				'$this->type',
				'$this->org_id',
				'$this->campaign_id',
				'$this->group_id',
				'$this->scheduled_on',
				'$this->scheduled_by',
				'$this->last_updated_on',
				'$this->approved_by',
				'$this->default_arguments',
				'$this->status',
				'$this->scheduled_type',
				'$this->params',
				'$this->Approved' 

			)";
		
		return $this->database->update( $sql );


	}
	
	
	/**
	*
	*@param $id
	*/
	function update( $id )
	{

		$default_arguments = mysql_escape_string( $this->default_arguments );
		$params = mysql_escape_string( $this->params );
		
		$sql = " 
			UPDATE message_queue 
			SET  
				guid = '$this->guid',
				type = '$this->type',
				org_id = '$this->org_id',
				campaign_id = '$this->campaign_id',
				group_id = '$this->group_id',
				scheduled_on = '$this->scheduled_on',
				scheduled_by = '$this->scheduled_by',
				last_updated_on = '$this->last_updated_on',
				approved_by = '$this->approved_by',
				default_arguments = '$default_arguments',
				status = '$this->status',
				scheduled_type = '$this->scheduled_type',
				params = '$params',
				Approved = '$this->Approved' 
			WHERE id = $id 
			AND org_id = $this->current_org_id ";

		return $result = $this->database->update($sql);

	}
	
	function delete( $id ){
		
		$sql = " DELETE FROM `message_queue` WHERE id = $id ";
		
		return $this->database->update($sql);
	}

	/**
	*
	*Returns the hash array for the object
	*
	*/
	function getHash(){

		$hash = array();
 
		$hash['id'] = $this->id;
		$hash['guid'] = $this->guid;
		$hash['type'] = $this->type;
		$hash['org_id'] = $this->org_id;
		$hash['campaign_id'] = $this->campaign_id;
		$hash['group_id'] = $this->group_id;
		$hash['scheduled_on'] = $this->scheduled_on;
		$hash['scheduled_by'] = $this->scheduled_by;
		$hash['last_updated_on'] = $this->last_updated_on;
		$hash['approved_by'] = $this->approved_by;
		$hash['default_arguments'] = $this->default_arguments;
		$hash['status'] = $this->status;
		$hash['scheduled_type'] = $this->scheduled_type;
		$hash['params'] = $this->params;
		$hash['Approved'] = $this->Approved;


		return $hash;
	}
	
	//health dashboard tracker
	private function getChangesToTrack( $type ){
		
		if( $type == 'insert' ){
			
			$changes = $this->getHash();
		}
		return $changes;
	}
	
	private function raiseEvent( $data , $id ){
		
		global $cfg;
		if($cfg['health_dashboard'] == 'disabled')
			return;
		
		if( $id == -1 )
			$fin['scheduler_failure'] = $data;
		else 
			$fin['scheduler_success'] = $data;
		
		$obj  = new EntityHealthTracker();
		$obj->process( 'CAMPAIGN' , $fin );
	}
	
} // class : end

?>
