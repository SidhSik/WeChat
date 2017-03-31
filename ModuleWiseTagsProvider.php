<?php
include_once 'creative_assets/controller/SupportedTagList.php';

/**
* {{tag}} provider class for module based 
* like campaign, points_engine etc.
* @author bhavesh
*
*/ 
class ModuleWiseTagsProvider{

public static function getTags( SupportedTagList $type , array $tags = array() ){

	global $logger;
	$logger->debug('@@Inside Change module wise tag provider');
	
	switch ( $type ){
		
		case SupportedTagList::$CAMPAIGN_EMAIL :
		return self::getCampaignEmailTags();
		break;

		case SupportedTagList::$POINTS_ENGINE:
		return self::getPointsEngineSupportedTags();
		break;

		case SupportedTagList::$HTML_INSERTS :
		return self::getHtmlInsertsTags();
		break;

		case SupportedTagList::$CAMPAIGN_SMS :
		return self::getCampaignSmsTags();
		break;

		case SupportedTagList::$REFERRAL :
		return self::getReferralSupportedTags();
		break;

		case SupportedTagList::$CALL_TASK :
		return self::getCallTaskTags();
		break;

		case SupportedTagList::$REISSUAL :
		return self::getReissualReminderTags();

		case SupportedTagList::$RESEND_COUPON :
		return self::getResendCouponTags();				

		case SupportedTagList::$CLOUDCHERRY:
		return self::getCloudCherryTags($tags);
		
		case SupportedTagList::$WECHAT:
		return self::getWeChatTags();

		case SupportedTagList::$WECHAT_DVS:
		return self::getWeChatDvsTags();

		case SupportedTagList::$WECHAT_LOYALTY:
		return self::getWeChatLoyaltyTags();

		case SupportedTagList::$WECHAT_OUTBOUND:
		return self::getWeChatOutboundTags();

		case SupportedTagList::$MOBILEPUSH:
		return self::getMobilePushTags();

		case SupportedTagList::$CAPILLARY_CC:
		return self::getCapillaryCCTags();

		case SupportedTagList::$DVS_SMS:
		return self::getDvsSmsTags();

		case SupportedTagList::$DVS_EMAIL:
		return self::getDvsEmailTags();

		case SupportedTagList::$EBILL:
			return self::getEBillTags();
	}
}

private static function getResendCouponTags(){
	global $currentorg;
	
	$customFieldClass = new CustomFields();
	$result = $customFieldClass->getCustomFields( $currentorg->org_id, 'query_hash', 'name', 'name', 'loyalty_registration' );
	
	$custom_field_tags = array();
	
	if( $result ){

		$custom_field_tags['custom_field'] = array( 'name' => _campaign('Custom Fields'),
			'desc' => _campaign('Custom Fields of the Organization') );

		$custom_fields = array();
		foreach( $result as $key => $value ){

			$custom_fields = array_merge( $custom_fields, array( 'custom_field.'.$key =>
				array( 'name'    => $value,
					'desc'    => $value ) ) );
		}
		$custom_field_tags['custom_field']['subtags'] = $custom_fields;
	}
	
	$tags = array(
		'customer' => array( 'name' => _campaign('Customer') ,
			'subtags' =>
			array(
				'first_name' => array( 'name' => _campaign('First Name'), 'desc' => _campaign('First Name Of The Loyalty Customers') ),
				'last_name' => array( 'name' => _campaign('Last Name'), 'desc' => _campaign('Last Name Of The Loyalty Customers')),
				'fullname' => array( 'name' => _campaign('Full Name'), 'desc' => _campaign('Full Name Of The Customers') ),
				'custom_field' => array( 'name' => _campaign('Custom Fields') , 'desc' => '' ,
					'subtags2' => $custom_fields )
				)
		),
'coupons' => array( 'name' => _campaign('Coupons'),
'subtags' =>
array(
	'voucher' => array( 'name' => _campaign('Voucher'), 'desc' => _campaign('Voucher That Would Be Issued To The Customer From Attached Series') ),
	'valid_days_from_create' => array( 'name' => _campaign('Days Until Expiry') , 'desc' => _campaign('Days Until Voucher Expires') ),
	"valid_till_date" =>
	array(
		'name' => _campaign('Coupon Expiry Date'),
		'desc' => _campaign('Voucher Valid Till Date') ,
		'subtags2' =>
		array(
			'valid_till_date.FORMAT_1' => array( 'name' => _campaign('mm/dd/yyyy'), 'desc' => _campaign('Replace with mm/dd/yyyy') ),
			'valid_till_date.FORMAT_2' => array( 'name' => _campaign('dd/mm/yyyy'), 'desc' => _campaign('Replace with dd/mm/yyyy') ),
			'valid_till_date.FORMAT_3' => array( 'name' => _campaign('yyyy-mm-dd'), 'desc' => _campaign('Replace with dd-mm-yyyy') ),
			'valid_till_date.FORMAT_4' => array( 'name' => _campaign('mm/dd/yy'), 'desc' => _campaign('Replace with mm/dd/yy') ),
			'valid_till_date.FORMAT_5' => array( 'name' => _campaign('dd Mon yyyy'), 'desc' => _campaign('Replace with dd Month(3 letters) yyyy') ),
			'valid_till_date.FORMAT_6' => array( 'name' => _campaign('Day, Mon dd, yy'), 'desc' => _campaign('Replace with Day, Mon dd, yy') ),
			'valid_till_date.FORMAT_7' => array( 'name' => _campaign('dd.mm.yy'), 'desc' => _campaign('Replace with dd.mm.yy') ),
			'valid_till_date.FORMAT_8' => array( 'name' => _campaign('dd Mon'), 'desc' => _campaign('Replace with dd Month(3 letters)') )
			)
		),
	)
), 
'adv' => array( 'name' => _campaign('ADV'), 'desc' => _campaign('Replace with ADV tag') )
);

return $tags;

}

public function getCloudCherryTags(array $cc_tags = array()){
	//add 		"cap_nps" => "NPS",
	//"cap_productcategory" => "Product category", later
	foreach ($cc_tags AS $tag) {
		if(strpos($tag, 'cap_') === 0){
			$tags[$tag] = $tag ;
		}
	}
	unset($tags['cap_user_id']);
	return $tags;
}

public function getCustomFieldsMap(){
	
	global $currentorg;
	
	$customFieldClass = new CustomFields();
	
	$result = $customFieldClass->getCustomFieldsByScope($currentorg->org_id, LOYALTY_CUSTOM_REGISTRATION,true);
	$custom_field_tags = array();
	
	if( $result ){
		foreach( $result as $field ){
			$field_name = 'lreg_customfield__'.$field['name'] ;
			$custom_field_tags[$field_name] = $field['label'] ;
		}
	}
	
	return $custom_field_tags ;
}

public function getCapillaryCCTags(){
	//add "nps" => "NPS" later
	//"productcategory" => "Product category",
	
	$tags = array(
			"mobile" => _campaign("Mobile Number"),
			"email" => _campaign("Email Address"),
			"firstname" => _campaign("First Name"),
			"lastname" => _campaign("Second Name"),
			"tillid" => _campaign("Till ID"),
			"orgid" => _campaign("Org ID"),
			"purchasevolume" => _campaign("Purchase Volume"),
			"purchasefrequency" => _campaign("No of Visits"),
			"transactionid" => _campaign("Bill ID")
	);
	
	$custom_field_tags = self::getCustomFieldsMap();
	
	$tags = array_merge($custom_field_tags,$tags);
		
	return $tags;
	
}


private function getWeChatDvsTags(){
	return self::getDvsEmailTags();
}
private function getWeChatLoyaltyTags(){
	return self::getWeChatTags();
}
private function getWeChatOutboundTags(){
	$tags = array(

			_campaign('First Name') => '{{first_name}}',
			_campaign('Last Name') => '{{last_name}}',
			_campaign('Full Name') => '{{fullname}}',
			_campaign('Email') => '{{customer_email}}',
			_campaign('Slab Name') => '{{slab_name}}',
			_campaign('Store Name')	=>	'{{store_name}}'

		);
	return $tags;
}


private function getWeChatTags(){
	
	$tags = array(
		
			_campaign('First Name') => '{{first_name}}',
			_campaign('Last Name') => '{{last_name}}',
			_campaign('Full Name') => '{{full_name}}',
			_campaign('Email') => '{{customer_email}}',
			_campaign('Slab Name') => '{{slab_name}}',
			_campaign('Slab Expiry Date') => '{{slab_expiry_date}}',
			
		
			_campaign('Mobile Number') => '{{mobile_number}}',
			_campaign('Email Id') => '{{email_id}}',
			_campaign('External Id') => '{{external_id}}',
			_campaign('Loyalty Points') => '{{current_points}}',
			
		
			_campaign('Loyalty Points (in)') => '{{current_points_currency}}',
			_campaign('Lifetime Points') => '{{cumulative_points}}',
			_campaign('Lifetime Points (in)') => '{{cumulative_points_currency}}',
			_campaign('Lifetime Purchases') => '{{cumulative_purchases}}',
			_campaign('Store Mobile Number') => '{{store_number_mobile}}',
						
					
			_campaign('Store Number Landline') => '{{store_number_landline}}',
							
			_campaign('Store Address')	=>	'{{store_address}}',
			_campaign('Store Name')	=>	'{{store_name}}',
			_campaign('Transaction Date')	=>	'{{bill_date}}',
			_campaign('Transaction Gross Amount')	=>	'{{bill_gross_amount}}',
			_campaign('Transaction Discount')	=>	'{{bill_discount}}',
			_campaign('Transaction Number')	=>	'{{bill_number}}',
			_campaign('Initial slab name')	=>	'{{initial_slab_name}}',
			_campaign('Transaction Amount')=>	'{{bill_amount}}',
					
			_campaign('Tracked Value') => '{{tracked_value}}', 
			_campaign('Current Aggregate') => '{{current_aggregate}}',
			_campaign('Points on Event') => '{{points_on_event}}',
			_campaign('Points on Event (in)') => '{{points_on_event_currency}}',
			_campaign('Voucher Code') => '{{voucher_code}}',
			_campaign('Voucher Expiry Date') => '{{voucher_expiry_date}}',
			_campaign('N/A') => 'N/A',
							);

return $tags;
}

/**
 * Returns supported campaign mobile push tags.
 */
private function getMobilePushTags(){
	
	global $currentorg;
	
	$customFieldClass = new CustomFields();
	
	$result = $customFieldClass->getCustomFields( $currentorg->org_id, 
		'query_hash', 'name', 
		'name', 'loyalty_registration' 
		);
	$custom_field_tags = array();
	
	if( $result ){
		foreach( $result as $key => $value ){
			$custom_field_tags[$key] = '{{custom_field.'.$key.'}}';
		}
	}
	$custom_tags_in_format[_campaign('Custom Fields')] = $custom_field_tags;
	
	$tags = array(
		_campaign('Customer') =>array( 
			_campaign('First Name') => '{{first_name}}',
			_campaign('Last Name') => '{{last_name}}',
			_campaign('Full Name') => '{{fullname}}',
			_campaign('Email') => '{{customer_email}}',
			_campaign('Loyalty Points') => '{{loyalty_points}}',
			_campaign('Slab Name') => '{{slab_name}}',
			_campaign('Custom Fields') => $custom_field_tags
			),
		_campaign('Store') =>array(
			_campaign('Name') => '{{store_name}}',
			_campaign('Mobile') => '{{store_number}}',
			_campaign('Landline') => '{{store_land_line}}',
			_campaign('Email') => '{{store_email}}',
			_campaign('External ID') => '{{store_external_id}}',
			_campaign('External ID 1') => '{{store_external_id_1}}',
			_campaign('External ID 2') => '{{store_external_id_2}}',
			_campaign('Address') => '{{store_address}}',
			_campaign('Email Template Fields') => array(
				_campaign('Name') => '{{email_store_name}}',
				_campaign('Email') => '{{email_email}}',
				_campaign('Store Template Mobile') => '{{email_mobile}}',
				_campaign('Landline') => '{{email_land_line}}',
				_campaign('Address') => '{{email_address}}',
				_campaign('Extra') => '{{email_extra}}',
				_campaign('Extra 1')=> '{{email_extra_1}}',
				)
			),
		_campaign('Coupon') => array(
			_campaign('Issue Coupon') => '{{voucher}}' ,
				_campaign('Days Until Expiry') => '{{valid_days_from_create}}',
					_campaign('Expiry Date Formats') =>
						array(
							_campaign('mm/dd/yyyy')	=>	'{{valid_till_date.FORMAT_1}}',
							_campaign('dd/mm/yyyy')	=>	'{{valid_till_date.FORMAT_2}}',
							_campaign('yyyy-mm-dd')	=>	'{{valid_till_date.FORMAT_3}}',
							_campaign('mm/dd/yy')	=>	'{{valid_till_date.FORMAT_4}}',
							_campaign('dd Mon yyyy')	=>	'{{valid_till_date.FORMAT_5}}',
							_campaign('Day, Mon dd, yy')	=>	'{{valid_till_date.FORMAT_6}}',
							_campaign('dd.mm.yy'	)=>	'{{valid_till_date.FORMAT_7}}',
							_campaign('dd Mon')=>	'{{valid_till_date.FORMAT_8}}'
							),
						),
				_campaign('Points') => array( 
						_campaign('Number of points') => '{{promotion_points}}',
							_campaign('Points Expiry Date Formats') =>
								array(
									_campaign('mm/dd/yyyy')	=>	'{{promotion_points_expiring_on.FORMAT_1}}',
									_campaign('dd/mm/yyyy')	=>	'{{promotion_points_expiring_on.FORMAT_2}}',
									_campaign('yyyy-mm-dd')	=>	'{{promotion_points_expiring_on.FORMAT_3}}',
									_campaign('mm/dd/yy')	=>	'{{promotion_points_expiring_on.FORMAT_4}}',
									_campaign('dd Mon yyyy')	=>	'{{promotion_points_expiring_on.FORMAT_5}}',
									_campaign('Day, Mon dd, yy')	=>	'{{promotion_points_expiring_on.FORMAT_6}}',
									_campaign('dd.mm.yy')	=>	'{{promotion_points_expiring_on.FORMAT_7}}',
									_campaign('dd Mon')=>	'{{promotion_points_expiring_on.FORMAT_8}}'
									)
								));

if( count( $custom_field_tags ) < 1 )
unset( $tags[_campaign('Customer')][_campaign('Custom Fields')] );

return $tags;
}


/**
 * Returns supported campaign email tags.
 */
private function getCampaignEmailTags(){
	
	global $currentorg;
	
	$customFieldClass = new CustomFields();
	
	$result = $customFieldClass->getCustomFields( $currentorg->org_id, 
		'query_hash', 'name', 
		'name', 'loyalty_registration' 
		);
	$custom_field_tags = array();
	
	if( $result ){
		foreach( $result as $key => $value ){
			$custom_field_tags[$key] = '{{custom_field.'.$key.'}}';
		}
	}
	$custom_tags_in_format[_campaign('Custom Fields')] = $custom_field_tags;
	
	$tags = array(
		_campaign('Customer') =>array( 
			_campaign('First Name') => '{{first_name}}',
			_campaign('Last Name') => '{{last_name}}',
			_campaign('Full Name') => '{{fullname}}',
			_campaign('Email') => '{{customer_email}}',
			_campaign('Loyalty Points') => '{{loyalty_points}}',
			_campaign('Slab Name') => '{{slab_name}}',
			_campaign('Custom Fields') => $custom_field_tags
			),
		_campaign('Store') =>array(
			_campaign('Name') => '{{store_name}}',
			_campaign('Mobile') => '{{store_number}}',
			_campaign('Landline') => '{{store_land_line}}',
			_campaign('Email') => '{{store_email}}',
			_campaign('External ID') => '{{store_external_id}}',
			_campaign('External ID 1') => '{{store_external_id_1}}',
			_campaign('External ID 2') => '{{store_external_id_2}}',
			_campaign('Address') => '{{store_address}}',
			_campaign('Email Template Fields') => array(
				_campaign('Name') => '{{email_store_name}}',
				_campaign('Email') => '{{email_email}}',
				_campaign('Store Template Mobile') => '{{email_mobile}}',
				_campaign('Landline') => '{{email_land_line}}',
				_campaign('Address') => '{{email_address}}',
				_campaign('Extra') => '{{email_extra}}',
				_campaign('Extra 1')=> '{{email_extra_1}}',
				)
			),
		_campaign('Coupon') => array(
			_campaign('Issue Coupon') => '{{voucher}}' ,
				_campaign('Days Until Expiry') => '{{valid_days_from_create}}',
					_campaign('Expiry Date Formats') =>
						array(
							_campaign('mm/dd/yyyy')	=>	'{{valid_till_date.FORMAT_1}}',
							_campaign('dd/mm/yyyy')	=>	'{{valid_till_date.FORMAT_2}}',
							_campaign('yyyy-mm-dd')	=>	'{{valid_till_date.FORMAT_3}}',
							_campaign('mm/dd/yy')	=>	'{{valid_till_date.FORMAT_4}}',
							_campaign('dd Mon yyyy')	=>	'{{valid_till_date.FORMAT_5}}',
							_campaign('Day, Mon dd, yy')	=>	'{{valid_till_date.FORMAT_6}}',
							_campaign('dd.mm.yy'	)=>	'{{valid_till_date.FORMAT_7}}',
							_campaign('dd Mon')=>	'{{valid_till_date.FORMAT_8}}'
							),
						),
					_campaign('Custom') =>array(
						_campaign('Custom Tag 1') => '{{custom_tag_1}}',
						_campaign('Custom Tag 2') => '{{custom_tag_2}}',
						_campaign('Custom Tag 3') => '{{custom_tag_3}}',
						_campaign('Custom Tag 4') => '{{custom_tag_4}}',
						_campaign('Custom Tag 5') => '{{custom_tag_5}}',
						),
					_campaign('Group Tags') => array(
						_campaign('Group Tag 1') => '{{group_tag_1}}',
						_campaign('Group Tag 2') => '{{group_tag_2}}',
						_campaign('Group Tag 3') => '{{group_tag_3}}',
						_campaign('Group Tag 4') => '{{group_tag_4}}',
						_campaign('Group Tag 5') => '{{group_tag_5}}'
						),
					_campaign('Points') => array( 
						_campaign('Number of points') => '{{promotion_points}}',
							_campaign('Points Expiry Date Formats') =>
								array(
									_campaign('mm/dd/yyyy')	=>	'{{promotion_points_expiring_on.FORMAT_1}}',
									_campaign('dd/mm/yyyy')	=>	'{{promotion_points_expiring_on.FORMAT_2}}',
									_campaign('yyyy-mm-dd')	=>	'{{promotion_points_expiring_on.FORMAT_3}}',
									_campaign('mm/dd/yy')	=>	'{{promotion_points_expiring_on.FORMAT_4}}',
									_campaign('dd Mon yyyy')	=>	'{{promotion_points_expiring_on.FORMAT_5}}',
									_campaign('Day, Mon dd, yy')	=>	'{{promotion_points_expiring_on.FORMAT_6}}',
									_campaign('dd.mm.yy')	=>	'{{promotion_points_expiring_on.FORMAT_7}}',
									_campaign('dd Mon')=>	'{{promotion_points_expiring_on.FORMAT_8}}'
									)
								),

					_campaign('Link to other Language') => array(),
							_campaign('Advertisement') => '{{adv}}', _campaign('User Id') => '{{user_id}}',
							_campaign('Unsubscribe') => '{{unsubscribe}}',
							_campaign('Subscribe') => '{{subscribe}}',
							_campaign('View In Browser') => '{{view_in_browser}}'
							);

if( count( $custom_field_tags ) < 1 )
unset( $tags[_campaign('Customer')][_campaign('Custom Fields')] );

return $tags;
}

/**
 * returns the points engine related email tags.
 */
private function getPointsEngineSupportedTags(){
	$tags = array(	
		_campaign('Customer') =>array( _campaign('First Name') => '{{first_name}}',
			_campaign('Last Name') =>	'{{last_name}}',
			_campaign('Full Name') => '{{full_name}}',
			_campaign('Slab Name') => '{{slab_name}}'
			),
		_campaign('Store') => array( _campaign('Store Name') => '{{store_name}}',
			_campaign('Store Address') => '{{store_address}}'
			),
		_campaign('Loyalty Points') => '{{current_points}}',
		_campaign('Lifetime Purchases') =>	'{{cumulative_purchases}}',
		_campaign('Lifetime Points') => '{{cumulative_points}}',
		_campaign('Points on event') => '{{points_on_event}}',
		_campaign('User id b64') => '{{user_id_b64}}',
		_campaign('Unsubscribe') => '{{unsubscribe}}'
		);
	return $tags;
}

/**
 * returns creative assets html inserts.
 */
private function getHtmlInsertsTags(){
	
	$tags = array( 
		_campaign('Discount') => '{{discount}}',
		_campaign('Voucher code') => '{{voucher}}',
		_campaign('Validity/expiry date') => '{{valid_till_date.FORMAT_1}}'
		);
	
	return $tags;
}

private function getCampaignSmsTags(){
	global $currentorg;
	
	$customFieldClass = new CustomFields();
	$result = $customFieldClass->getCustomFields( $currentorg->org_id, 'query_hash', 'name', 'name', 'loyalty_registration' );
	
	$custom_field_tags = array();
	
	if( $result ){

		$custom_field_tags['custom_field'] = array( 'name' => _campaign('Custom Fields'),
			'desc' => _campaign('Custom Fields of the Organization') );

		$custom_fields = array();
		foreach( $result as $key => $value ){

			$custom_fields = array_merge( $custom_fields, array( 'custom_field.'.$key =>
				array( 'name'    => $value,
					'desc'    => $value ) ) );
		}
		$custom_field_tags['custom_field']['subtags'] = $custom_fields;
	}
	
	$tags = array(
		'customer' => array( 'name' => _campaign('Customer') ,
			'subtags' =>
			array(
				'first_name' => array( 'name' => _campaign('First Name'), 'desc' => _campaign('First Name Of The Loyalty Customers') ),
				'last_name' => array( 'name' => _campaign('Last Name'), 'desc' => _campaign('Last Name Of The Loyalty Customers')),
				'fullname' => array( 'name' => _campaign('Full Name'), 'desc' => _campaign('Full Name Of The Customers') ),
				'loyalty_points' => array( 'name' => _campaign('Loyalty Points'), 'desc' => _campaign('Current Loyalty Points') ),
				'slab_name' => array( 'name' => _campaign('Slab Name'), 'desc' => _campaign('Customer Slab Name') ),
				'custom_field' => array( 'name' => _campaign('Custom Fields') , 'desc' => '' ,
					'subtags2' => $custom_fields )
				)
		),
	'store_tags' => array( 'name' => _campaign('Store'),
		'subtags' =>
		array(
			'store_name' => array( 'name' => _campaign('Name'), 'desc' => _campaign('Store Name Of The Store Where The Customer Was Registered') ),
			'store_number' => array( 'name' => _campaign('Mobile Number'), 'desc' => _campaign('Store Number Of The Store Where The Customer Was Registered') ),
			'store_email' => array( 'name' => _campaign('Email'), 'desc' => _campaign('Store Email Of The Store Where The Customer Was Registered') ),
			'store_land_line' => array( 'name' => _campaign('Land Line'), 'desc' => _campaign('Store Landline Of The Store Where The Customer Was Registered') ),
			'store_external_id' => array( 'name' => _campaign('External ID'), 'desc' => _campaign('Store External ID Of The Store Where The Customer Was Registered') ),
			'store_external_id_1' => array( 'name' => _campaign('External ID 1'), 'desc' => _campaign('Store External ID 1 Of The Store Where The Customer Was Registered') ),
			'store_external_id_2' => array( 'name' => _campaign('External ID 2'), 'desc' => _campaign('Store External ID 2 Of The Store Where The Customer Was Registered') ),
			/*'store_address' => array( 'name' => 'Store Address', 'desc' => 'Store Address Of The Store Where The Customer Was Registered' ),*/
			'store_templates' =>
			array( 'name' => _campaign('Store Templates'), 'desc' => '',
				'subtags2' => array(
					'sms_store_name' => array( 'name' => _campaign('Name') , 'desc' => _campaign('SMS Store Template Name') ),
					'sms_email' => array( 'name' => _campaign('EMAIL') , 'desc' => _campaign('SMS Store Template Email') ),
					'sms_mobile' => array( 'name' => _campaign('Mobile') , 'desc' => _campaign('SMS Store Template Mobile') ),
					'sms_land_line' => array( 'name' => _campaign('Landline') , 'desc' => _campaign('SMS Store Template Landline') ),
					'sms_address' => array( 'name' => _campaign('Address') , 'desc' => _campaign('SMS Store Template Address') ),
					'sms_extra' => array( 'name' => _campaign('Extra') , 'desc' => _campaign('SMS Store Template Extra') ),
					)
				)
			)
),
'custom_tags' => array( 'name' => _campaign('Custom'),
'subtags' =>
array(
	'custom_tag_1' => array( 'name' => _campaign('1st Custom Tag'), 'desc' => _campaign('1st Coulumn That Was Uploaded From The CSV') ),
	'custom_tag_2' => array( 'name' => _campaign('2nd Custom Tag'), 'desc' => _campaign('2nd Coulumn That Was Uploaded From The CSV') ),
	'custom_tag_3' => array( 'name' => _campaign('3rd Custom Tag'), 'desc' => _campaign('3rd Coulumn That Was Uploaded From The CSV') ),
	'custom_tag_4' => array( 'name' => _campaign('4th Custom Tag'), 'desc' => _campaign('4th Coulumn That Was Uploaded From The CSV') ),
	'custom_tag_5' => array( 'name' => _campaign('5th Custom Tag'), 'desc' => _campaign('5th Coulumn That Was Uploaded From The CSV') ),
	'custom_tag_6' => array( 'name' => _campaign('6th Custom Tag'), 'desc' => _campaign('6th Coulumn That Was Uploaded From The CSV') ),
	)
),
'coupons' => array( 'name' => _campaign('Coupons'),
'subtags' =>
array(
	'voucher' => array( 'name' => _campaign('Voucher'), 'desc' => _campaign('Voucher That Would Be Issued To The Customer From Attached Series') ),
	'valid_days_from_create' => array( 'name' => _campaign('Days Until Expiry') , 'desc' => _campaign('Days Until Voucher Expires') ),
	"valid_till_date" =>
	array(
		'name' => _campaign('Coupon Expiry Date'),
		'desc' => _campaign('Voucher Valid Till Date') ,
		'subtags2' =>
		array(
			'valid_till_date.FORMAT_1' => array( 'name' => _campaign('mm/dd/yyyy'), 'desc' => _campaign('Replace with mm/dd/yyyy') ),
			'valid_till_date.FORMAT_2' => array( 'name' => _campaign('dd/mm/yyyy'), 'desc' => _campaign('Replace with dd/mm/yyyy') ),
			'valid_till_date.FORMAT_3' => array( 'name' => _campaign('yyyy-mm-dd'), 'desc' => _campaign('Replace with dd-mm-yyyy') ),
			'valid_till_date.FORMAT_4' => array( 'name' => _campaign('mm/dd/yy'), 'desc' => _campaign('Replace with mm/dd/yy') ),
			'valid_till_date.FORMAT_5' => array( 'name' => _campaign('dd Mon yyyy'), 'desc' => _campaign('Replace with dd Month(3 letters) yyyy') ),
			'valid_till_date.FORMAT_6' => array( 'name' => _campaign('Day, Mon dd, yy'), 'desc' => _campaign('Replace with Day, Mon dd, yy') ),
			'valid_till_date.FORMAT_7' => array( 'name' => _campaign('dd.mm.yy'), 'desc' => _campaign('Replace with dd.mm.yy') ),
			'valid_till_date.FORMAT_8' => array( 'name' => _campaign('dd Mon'), 'desc' => _campaign('Replace with dd Month(3 letters)') )
			)
		),
	)
), 
_campaign('Points') => array( 
'name'=>_campaign('Points'),
'subtags'=>
array(
	'promotion_points'=>array('name'=>_campaign('Number of points'),'desc'=>_campaign('Number of points')),
	'points_expiry_date_formats' =>
	array(
		'name'=> _campaign('Points Expiry Date Formats') ,
		'desc' => 	_campaign('Points Expiry Date Formats') ,
		'subtags2'=>
		array(
			'promotion_points_expiring_on.FORMAT_1'=>array('name'=>_campaign('mm/dd/yyyy'),'desc'=>_campaign('Replace with mm/dd/yyyy')),
			'promotion_points_expiring_on.FORMAT_2'=>array('name'=>_campaign('dd/mm/yyyy'),'desc'=>_campaign('Replace with dd/mm/yyyy')),
			'promotion_points_expiring_on.FORMAT_3'=>array('name'=>_campaign('yyyy-mm-dd'),'desc'=>_campaign('Replace with yyyy-mm-dd')),
			'promotion_points_expiring_on.FORMAT_4'=>array('name'=>_campaign('mm/dd/yy'),'desc'=>_campaign('Replace with mm/dd/yy')),
			'promotion_points_expiring_on.FORMAT_5'=>array('name'=>_campaign('dd Mon yyyy'),'desc'=>_campaign('Replace with dd Mon yyyy')),
			'promotion_points_expiring_on.FORMAT_6'=>array('name'=>_campaign('Day, Mon dd, yy'),'desc'=>_campaign('Replace with Day, Mon dd, yy')),
			'promotion_points_expiring_on.FORMAT_7'=>array('name'=>_campaign('dd.mm.yy'),'desc'=>_campaign('Replace with dd.mm.yy')),
			'promotion_points_expiring_on.FORMAT_8'=>array('name'=>_campaign('dd Mon'),'desc'=>_campaign('Replace with dd Mon'))
			)
		)
	)
),
'adv' => array( 'name' => _campaign('ADV'), 'desc' => _campaign('Replace with ADV tag') ),
'optout' => array( 'name' => _campaign('optout'), 'desc' => _campaign('Opt out tag') )
);

if( count( $custom_fields ) < 1 )
unset( $tags['customer']['subtags']['custom_field'] );
return $tags;

}

private function getReissualReminderTags(){
global $currentorg;

$customFieldClass = new CustomFields();
$result = $customFieldClass->getCustomFields( $currentorg->org_id, 'query_hash', 'name', 'name', 'loyalty_registration' );

$custom_field_tags = array();

if( $result ){
	
	$custom_field_tags['custom_field'] = array( 'name' => _campaign('Custom Fields'),
		'desc' => _campaign('Custom Fields of the Organization') );
	
	$custom_fields = array();
	foreach( $result as $key => $value ){

		$custom_fields = array_merge( $custom_fields, array( 'custom_field.'.$key =>
			array( 'name'    => $value,
				'desc'    => $value ) ) );
	}
	$custom_field_tags['custom_field']['subtags'] = $custom_fields;
}

$tags = array(
	'customer' => array( 'name' => _campaign('Customer') ,
		'subtags' =>
		array(
			'first_name' => array( 'name' => _campaign('First Name'), 'desc' => _campaign('First Name Of The Loyalty Customers')),
			'last_name' => array( 'name' => _campaign('Last Name'), 'desc' => _campaign('Last Name Of The Loyalty Customers')),
			'fullname' => array( 'name' => _campaign('Full Name'), 'desc' => _campaign('Full Name Of The Customers') ),
			'loyalty_points' => array( 'name' => _campaign('Loyalty Points'), 'desc' => _campaign('Current Loyalty Points') ),
			'slab_name' => array( 'name' => _campaign('Slab Name'), 'desc' => _campaign('Customer Slab Name') ),
			'custom_field' => array( 'name' => _campaign('Custom Fields') , 'desc' => '' ,
				'subtags2' => $custom_fields )
			)
		),
	'store_tags' => array( 'name' => _campaign('Store'),
		'subtags' =>
		array(
			'store_name' => array( 'name' => _campaign('Name'), 'desc' => _campaign('Store Name Of The Store Where The Customer Was Registered' )),
			'store_number' => array( 'name' => _campaign('Mobile Number'), 'desc' => _campaign('Store Number Of The Store Where The Customer Was Registered' )),
			'store_email' => array( 'name' => _campaign('Email'), 'desc' => _campaign('Store Email Of The Store Where The Customer Was Registered' )),
			'store_land_line' => array( 'name' => _campaign('Land Line'), 'desc' => _campaign('Store Landline Of The Store Where The Customer Was Registered' )),
			'store_external_id' => array( 'name' => _campaign('External ID'), 'desc' => _campaign('Store External ID Of The Store Where The Customer Was Registered' )),
			'store_external_id_1' => array( 'name' => _campaign('External ID 1'), 'desc' => _campaign('Store External ID 1 Of The Store Where The Customer Was Registered' )),
			'store_external_id_2' => array( 'name' => _campaign('External ID 2'), 'desc' => _campaign('Store External ID 2 Of The Store Where The Customer Was Registered' )),
			/*'store_address' => array( 'name' => 'Store Address', 'desc' => 'Store Address Of The Store Where The Customer Was Registered' ),*/
			'store_templates' =>
			array( 'name' => _campaign('Store Templates'), 'desc' => '',
				'subtags2' => array(
					'sms_store_name' => array( 'name' => _campaign('Name') , 'desc' => _campaign('SMS Store Template Name' )),
					'sms_email' => array( 'name' => _campaign('EMAIL') , 'desc' => _campaign('SMS Store Template Email') ),
					'sms_mobile' => array( 'name' => _campaign('Mobile') , 'desc' => _campaign('SMS Store Template Mobile') ),
					'sms_land_line' => array( 'name' => _campaign('Landline') , 'desc' => _campaign('SMS Store Template Landline')),
					'sms_address' => array( 'name' => _campaign('Address') , 'desc' => _campaign('SMS Store Template Address') ),
					'sms_extra' => array( 'name' => _campaign('Extra') , 'desc' => _campaign('SMS Store Template Extra') ),
					)
				)
			)
),
'custom_tags' => array( 'name' => _campaign('Custom'),
'subtags' =>
array(
	'custom_tag_1' => array( 'name' => _campaign('1st Custom Tag'), 'desc' => _campaign('1st Coulumn That Was Uploaded From The CSV') ),
	'custom_tag_2' => array( 'name' => _campaign('2nd Custom Tag'), 'desc' => _campaign('2nd Coulumn That Was Uploaded From The CSV') ),
	'custom_tag_3' => array( 'name' => _campaign('3rd Custom Tag'), 'desc' => _campaign('3rd Coulumn That Was Uploaded From The CSV') ),
	'custom_tag_4' => array( 'name' => _campaign('4th Custom Tag'), 'desc' => _campaign('4th Coulumn That Was Uploaded From The CSV') ),
	'custom_tag_5' => array( 'name' => _campaign('5th Custom Tag'), 'desc' => _campaign('5th Coulumn That Was Uploaded From The CSV') ),
	'custom_tag_6' => array( 'name' => _campaign('6th Custom Tag'), 'desc' => _campaign('6th Coulumn That Was Uploaded From The CSV') ),
	)
),
'coupons' => array( 'name' => _campaign('Coupons'),
'subtags' =>
array(
	'voucher_code' => array( 'name' => _campaign('Voucher'), 'desc' => _campaign('Voucher That Would Be Issued To The Customer From Attached Series') ),
	'valid_days_from_create' => array( 'name' => _campaign('Days Until Expiry') , 'desc' => _campaign('Days Until Voucher Expires') ),
	"valid_till_date" =>
	array(
		'name' => _campaign('Coupon Expiry Date'),
		'desc' => _campaign('Voucher Valid Till Date' ),
		'subtags2' =>
		array(
			'valid_till_date.FORMAT_1' => array( 'name' => _campaign('mm/dd/yyyy'), 'desc' => _campaign('Replace with mm/dd/yyyy') ),
			'valid_till_date.FORMAT_2' => array( 'name' => _campaign('dd/mm/yyyy'), 'desc' => _campaign('Replace with dd/mm/yyyy') ),
			'valid_till_date.FORMAT_3' => array( 'name' => _campaign('yyyy-mm-dd'), 'desc' => _campaign('Replace with dd-mm-yyyy') ),
			'valid_till_date.FORMAT_4' => array( 'name' => _campaign('mm/dd/yy'), 'desc' => _campaign('Replace with mm/dd/yy') ),
			'valid_till_date.FORMAT_5' => array( 'name' => _campaign('dd Mon yyyy'), 'desc' => _campaign('Replace with dd Month(3 letters) yyyy') ),
			'valid_till_date.FORMAT_6' => array( 'name' => _campaign('Day, Mon dd, yy'), 'desc' => _campaign('Replace with Day, Mon dd, yy') ),
			'valid_till_date.FORMAT_7' => array( 'name' => _campaign('dd.mm.yy'), 'desc' => _campaign('Replace with dd.mm.yy') ),
			'valid_till_date.FORMAT_8' => array( 'name' => _campaign('dd Mon'), 'desc' => _campaign('Replace with dd Month(3 letters)') )
			)
		),
	)
),
'adv' => array( 'name' => _campaign('ADV'), 'desc' => _campaign('Replace with ADV tag') )
);

if( count( $custom_fields ) < 1 )
unset( $tags['customer']['subtags']['custom_field'] );
return $tags;

}

/**
 *
 * It will get the html content for the supported tags(points engine,campaign)
 * @param tags
 */
public function getHtmlTemplateForTags($tags){
	foreach ( $tags as $key => $value ){

		if( is_array( $value ) ){

			$str .= "<li class='parent_tags_menu' id='ptags__".Util::uglify($key)."' >
			<i class='color-Med-Gray icon-caret-right' id='submenu_icon__".Util::uglify($key)."' ></i> $key";
			$str .= "<ul id='tags_submenu__".Util::uglify($key)."' class='parent_email_sub_tag hide'>";
			foreach( $value as $k => $v ){
				
				if( is_array( $v ) ){

					$str .= "<li class='parent_tags_menu2' id='ptags2__".Util::uglify($k)."' >
					<i class='color-Med-Gray icon-caret-right' id='submenu_icon2__".Util::uglify($k)."' ></i> $k";
					$str .= "<ul id='tags_submenu2__".Util::uglify($k)."' class='parent_email_sub_tag2 hide'>";

					foreach( $v as $k2 => $v2 ){
						$str .= "<li id='tag2__$v2' class='msg_tags_edit' >$k2<i class='color-Green icon-chevron-right float-right'></i></li>";
					}

					$str .= "</ul></li>";
				}
				else
					$str .= "<li id='tag__$v' class='msg_tags_edit' >$k
				<i class='color-Green icon-chevron-right float-right'></i></li>";
			}
			$str .= "</ul></li>";
		}
		else
			$str .= "<li id='tag__$value' class='msg_tags_edit custom-tag-margin' >$key
		<i class='color-Green icon-chevron-right float-right'></i></li>";
	}

	return $str;
}

private function getReferralSupportedTags(){
	
	$tags = array(
		_campaign('Referrer Code') => '{{referrer_unique_code}}',
		_campaign('Referrer Url') => '{{referrer_unique_url}}',
		_campaign('Invitee Name') => '{{invitee_name}}',
		_campaign('Invitee Identifier') => '{{invitee_identifier}}',
		_campaign('Referrer Full Name') => '{{referrer_name}}',
		_campaign('Referrer First Name') => '{{referrer_first_name}}',
		_campaign('Unsubscribe') => '{{unsubscribe}}'
		);
	return $tags;
}

private function getCallTaskTags(){
	global $currentorg;
	
	$customFieldClass = new CustomFields();
	$result = $customFieldClass->getCustomFields( $currentorg->org_id, 'query_hash', 'name', 'name', 'loyalty_registration' );
	
	$custom_field_tags = array();
	
	if( $result ){

		foreach( $result as $key => $value ){

			$custom_field_tags[$key] = '{{custom_field.'.$key.'}}';
		}
	}
	
	$custom_tags_in_format['Custom Fields'] = $custom_field_tags;

	$tags = array(
		_campaign('Customer') =>
		array(  _campaign('First Name') => '{{first_name}}',
			_campaign('Last Name') => '{{last_name}}',
			_campaign('Full Name') => '{{fullname}}',
			_campaign('Loyalty Points') => '{{loyalty_points}}',
			_campaign('Slab Name') => '{{slab_name}}',
			_campaign('Mobile') => '{{customer_mobile}}',
			_campaign('Custom Fields' )=> $custom_field_tags
			)
		);

	
	return $tags;
}

public function getDvsSmsTags(){
	
	$tags = array('Customer' => array( 'name' => _campaign('Customer') ,
		'subtags' =>
		array(
			'cust_name' => array( 'name' => _campaign('Customer Name'), 'desc' => _campaign('Name Of The Customer'))
			)
	),
	'Voucher' => array( 'name' => _campaign('Voucher') ,
		'subtags' =>
		array(
			'voucher_code' => array( 'name' => _campaign('Voucher Code'), 'desc' => _campaign('Voucher code')),
			'voucher_expiry_date' => array( 'name' => _campaign('Voucher Expiry Date'), 'desc' => _campaign('Expiry date of Voucher') )
			)
	));
	return $tags;
}

public function getDvsEmailTags(){

	$tags = array(
			_campaign('Customer Name') => '{{cust_name}}',
			_campaign('Voucher Code') => '{{voucher_code}}',
			_campaign('Voucher Expiry Date') => '{{voucher_expiry_date}}',
			_campaign('Unsubscribe') => '{{unsubscribe}}'
		);
	return $tags;
}

public function getEBillTags(){

	$custom_field = new CustomFields();
	$tags = array();
	$tags = array(
			_campaign('Customer') =>array( _campaign('First Name') => '{{first_name}}',
					_campaign('Last Name') =>	'{{last_name}}',
					_campaign('Full Name') => '{{fullname}}',
					_campaign('Customer ID') => '{{customer_id}}',
					_campaign('Mobile') => '{{mobile}}',
					_campaign('Email') => '{{email}}',
					_campaign('Slab Name') => '{{slab_name}}',
					_campaign('Slab Number') => '{{slab_number}}',
					_campaign('External Id') => '{{external_id}}',
					_campaign('Loyalty Points') => '{{current_points}}',
					_campaign('Lifetime Purchases') =>	'{{lifetime_purchases}}',
					_campaign('Lifetime Points') => '{{lifetime_points}}',
					_campaign('Number of Visits') => '{{num_of_visits}}',
					_campaign('Number of Bills') => '{{num_of_bills}}',
					_campaign('Number of Bills Today') => '{{num_of_bills_today}}',
					_campaign('Number of Bills n days') => '{{num_of_bills_n_days}}',
					_campaign('Average Spend Per Visit') => '{{avg_spend_per_visit}}'
			),
			_campaign('Store') => array( _campaign('Store Name') => '{{store_name}}',
					_campaign('Store First Name') => '{{store_firstname}}',
					_campaign('Store Last Name') => '{{store_lastname}}',
					_campaign('Store Contact') => '{{store_contact}}',
					_campaign('Store Description') => '{{store_description}}',
					_campaign('Store ID') => '{{store_id}}'
			),
			_campaign('Transaction') => array(_campaign('Transaction Points') => '{{bill_points}}',
					_campaign('Transaction Number') => '{{bill_number}}',
					_campaign('Transaction Amount') => '{{bill_amount}}',
					_campaign('Transaction Discount') => '{{bill_discount}}',
					_campaign('Transaction Gross Amount') => '{{bill_gross_amount}}',
					_campaign('Transaction Diff Gross Discount') => '{{bill_diff_gross_discount}}',
					_campaign('Transaction Diff Amount Discount') => '{{bill_diff_amount_discount}}',
					_campaign('Transaction Date') => '{{date}}',
					_campaign('Transaction Notes') => '{{notes}}',
					_campaign('Transaction ID') => '{{loyalty_log_id}}'
			),
	);
	//registration custom fields
	$custom_fields = $custom_field->getCustomFieldAttrsAsOption($org_id, 'loyalty_registration');
	foreach ( $custom_fields as $k => $v ){
		$registration_tags[$k] = '{{custom_field__'.$k.'}}';
	}
	if($registration_tags)
		$tags = array_merge($tags,array('Registration custom fields' => $registration_tags));

	//transactional custom fields
	$custom_fields = $custom_field->getCustomFieldAttrsAsOption($org_id, 'loyalty_transaction');
	foreach ( $custom_fields as $k => $v ){
		$transaction_tags[$k] = '{{custom_field__'.$k.'}}';
	}
	if($transaction_tags)
		$tags = array_merge($tags,array('Transaction custom fields' => $transaction_tags));

	//store custom fields
	$custom_fields = $custom_field->getCustomFieldAttrsAsOption($org_id, 'store_custom_fields');
	foreach ( $custom_fields as $k => $v ){
		$store_tags[$k] = '{{custom_field__'.$k.'}}';
	}
	if($store_tags)
		$tags = array_merge($tags,array('Store custom fields' => $store_tags));

	return $tags;
}

}
?>
