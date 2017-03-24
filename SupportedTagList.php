<?php 
/**
 * Class for list of available tags.
 * @author bhavesh
 * 
 */
class SupportedTagList extends enum{
	
	public static $CAMPAIGN_EMAIL = NULL;
	public static $POINTS_ENGINE = NULL;
	public static $HTML_INSERTS = NULL;
	public static $CAMPAIGN_SMS = NULL;
	public static $REFERRAL = NULL;
	public static $REISSUAL = NULL;
	public static $RESEND_COUPON = NULL;
	public static $CALL_TASK = NULL;
	public static $CLOUDCHERRY = NULL;
	public static $CAPILLARY_CC = NULL;
	public static $WECHAT = NULL;
	public static $DVS_SMS = NULL;
	public static $DVS_EMAIL = NULL;
	public static $MOBILEPUSH = NULL;
	public static  $EBILL = NULL;
	public static  $WECHAT_DVS = NULL;
	public static  $WECHAT_LOYALTY = NULL;
	public static  $WECHAT_OUTBOUND = NULL;

	public function __construct( $value ){
		parent::__construct($value);
	}
	
	public static function init(){
		
		self::$CAMPAIGN_EMAIL = new SupportedTagList( 'CAMPAIGN_EMAIL' );
		self::$POINTS_ENGINE = new SupportedTagList( 'POINTS_ENGINE' );
		self::$HTML_INSERTS = new SupportedTagList( 'HTML_INSERTS' );
		self::$CAMPAIGN_SMS = new SupportedTagList( 'CAMPAIGN_SMS' );
		self::$REFERRAL = new SupportedTagList( 'REFERRAL' );
		self::$REISSUAL = new SupportedTagList( 'REISSUAL' );
		self::$RESEND_COUPON = new SupportedTagList( 'RESEND_COUPON' );
		self::$CALL_TASK = new SupportedTagList( 'CALL_TASK' );
		self::$CLOUDCHERRY = new SupportedTagList( 'CLOUDCHERRY' );
		self::$CAPILLARY_CC = new SupportedTagList( 'CAPILLARY_CC' );
		self::$WECHAT = new SupportedTagList( 'WECHAT' );
		self::$DVS_SMS = new SupportedTagList( 'DVS_SMS' );
		self::$DVS_EMAIL = new SupportedTagList( 'DVS_EMAIL' );
		self::$MOBILEPUSH = new SupportedTagList( 'MOBILEPUSH' );
		self::$EBILL = new SupportedTagList('EBILL');
		self::$WECHAT_DVS = new SupportedTagList('WECHAT_DVS');
		self::$WECHAT_LOYALTY = new SupportedTagList('WECHAT_LOYALTY');
		self::$WECHAT_OUTBOUND = new SupportedTagList('WECHAT_OUTBOUND');


		parent::$map = array( 
							  'CAMPAIGN_EMAIL' => self::$CAMPAIGN_EMAIL , 
							  'POINTS_ENGINE' => self::$POINTS_ENGINE,
							  'HTML_INSERTS' => self::$HTML_INSERTS,
							  'CAMPAIGN_SMS' => self::$CAMPAIGN_SMS,
							  'REFERRAL' => self::$REFERRAL,
							  'CALL_TASK' => self::$CALL_TASK,
							  'REISSUAL' => self::$REISSUAL,
							  'RESEND_COUPON' => self::$RESEND_COUPON,
							  'CLOUDCHERRY' => self::$CLOUDCHERRY,
							  'CAPILLARY_CC' => self::$CAPILLARY_CC,
							  'WECHAT' => self::$WECHAT,
							  'DVS_SMS' => self::$DVS_SMS,
							  'DVS_EMAIL' => self::$DVS_EMAIL,
							  'MOBILEPUSH' => self::$MOBILEPUSH,
							  'WECHAT_DVS' => self::$WECHAT_DVS,
							  'WECHAT_LOYALTY' => self::$WECHAT_LOYALTY,
							  'WECHAT_OUTBOUND' => self::$WECHAT_OUTBOUND
							);
	}
	
	public static function valueOf( $value ){
		
		return new SupportedTagList( $value );
	}
}

SupportedTagList::init();
?>
