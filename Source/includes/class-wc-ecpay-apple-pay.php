<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Ecpay_Apple_Pay class.
 *
 * @extends WC_Gateway_Stripe
 */
class WC_Ecpay_Apple_Pay extends WC_Gateway_Ecpay {
	/**
	 * This Instance.
	 *
	 * @var
	 */
	private static $_this;

	/**
	 * Statement Description
	 *
	 * @var
	 */
	public $statement_descriptor;

	/**
	 * Check if we capture the transaction immediately.
	 *
	 * @var bool
	 */
	public $capture;

	/**
	 * Do we accept Apple Pay?
	 *
	 * @var bool
	 */
	public $apple_pay;

	/**
	 * Apple Pay button style.
	 *
	 * @var bool
	 */
	public $apple_pay_button;

	/**
	 * Apple Pay button language.
	 *
	 * @var bool
	 */
	public $apple_pay_button_lang;

	/**
	 * Is test mode active?
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * Logging enabled?
	 *
	 * @var bool
	 */
	public $logging;

	/**
	 * Should we store the users credit cards?
	 *
	 * @var bool
	 */
	public $saved_cards;

	/**
	 * Publishable key credentials.
	 *
	 * @var bool
	 */
	public $publishable_key;

	/**
	 * Is shipping enabled?
	 *
	 * @var bool
	 */
	public $is_shipping_enabled;

	/**
	 * Constructor. 
	 * @access public
	 */
	public function __construct() {
		self::$_this = $this;

		$gateway_settings = get_option( 'woocommerce_ecpay_settings', '' );

		$this->ecpay_pay        	= ( ! empty( $gateway_settings['enabled'] ) && $gateway_settings['enabled'] === 'yes') ? true : false;
		$this->apple_pay_button 	= ! empty( $gateway_settings['ecpay_apple_pay_button'] ) 	? $gateway_settings['ecpay_apple_pay_button'] : 'black';
		$this->apple_pay_key_path 	= ! empty( $gateway_settings['ecpay_apple_pay_key_path'] ) 	? $gateway_settings['ecpay_apple_pay_key_path'] : '';
		$this->apple_pay_crt_path 	= ! empty( $gateway_settings['ecpay_apple_pay_crt_path'] ) 	? $gateway_settings['ecpay_apple_pay_crt_path'] : '';
		$this->apple_pay_key_pass	= ! empty( $gateway_settings['ecpay_apple_pay_key_pass'] ) 	? $gateway_settings['ecpay_apple_pay_key_pass'] : '';
		$this->apple_display_name	= ! empty( $gateway_settings['ecpay_apple_display_name'] ) 	? $gateway_settings['ecpay_apple_display_name'] : '';

		$this->MerchantID		= ! empty( $gateway_settings['ecpay_merchant_id'] ) ? $gateway_settings['ecpay_merchant_id'] : '';
		$this->HashKey 			= ! empty( $gateway_settings['ecpay_hash_key'] ) 	? $gateway_settings['ecpay_hash_key']    : '';
		$this->HashIV			= ! empty( $gateway_settings['ecpay_hash_iv'] ) 	? $gateway_settings['ecpay_hash_iv']     : '';
		$this->ecpay_test_mode 	= $this->isTestMode($this->MerchantID);

		$this->init();
	}

	/**
	 * Initialize.
	 *
	 */
	public function init() {
		// If ECPay Pay is not enabled no need to proceed further.
		if ( ! $this->ecpay_pay ) {
			return;
		}

		include_once( 'ECPay.Payment.Applepay.php' );	// 載入 APPLE PAY SDK
		
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		// 測試APPLE PAY
		add_action( 'admin_footer', array( $this, 'ajax_apple_pay_ca_test' ) ); 		 // 前端送出
		add_action( 'wp_ajax_apple_pay_ca_test', array( $this, 'curl_apple_ca_test' ) ); // 後端接收

		// Apple pay驗證廠商是否有註冊
		add_action( 'wp_ajax_apple_pay_check_vendor', array( $this, 'curl_apple_pay_check_vendor' ));		  // 後端接收
		add_action( 'wp_ajax_nopriv_apple_pay_check_vendor', array( $this, 'curl_apple_pay_check_vendor' ) ); // 後端接收

		// Apple Pay 送出交易
		add_action( 'wp_ajax_apple_pay_check_out', array( $this, 'curl_apple_pay_check_out' ));			// 後端接收
		add_action( 'wp_ajax_nopriv_apple_pay_check_out', array( $this, 'curl_apple_pay_check_out' ) );	// 後端接收
	}

	// 測試憑證
	public function ajax_apple_pay_ca_test() { 
		?>
			<script type="text/javascript">	
				var $ = jQuery.noConflict();

				$( document ).ready(function() {

					$("#apple_pay_ca_test").click(function(){
				        var data = {
						'action': 'apple_pay_ca_test'
					};

					$.blockUI({ message: null }); 
					$.post(ajaxurl, data, function(response) {
						setTimeout($.unblockUI, 1);
						alert(response);
					});
				    });
				});

			</script>
		<?php
	}

	// 送出憑證測試
	public function curl_apple_ca_test() {
		global $woocommerce, $post, $wpdb;

		try
		{
			$sMsg = '' ;

			$ecpay_apple_pay = new Ecpay_ApplePay ;
			$ecpay_apple_pay->ServiceURL = 'https://apple-pay-gateway-cert.apple.com/paymentservices/startSession';
			$ecpay_apple_pay->Verify_Vendor['displayName']  = $this->apple_display_name;
			$ecpay_apple_pay->Verify_Vendor['crt_path']     = $this->apple_pay_crt_path;
			$ecpay_apple_pay->Verify_Vendor['key_path']     = $this->apple_pay_key_path;
			$ecpay_apple_pay->Verify_Vendor['key_password'] = $this->apple_pay_key_pass;

			if ($ecpay_apple_pay->Verify_Vendor_Test() == 'yes') {
				echo esc_html($ecpay_apple_pay->Verify_Vendor_Test());
			} else {
				echo esc_html($ecpay_apple_pay->Verify_Vendor());
			}
		}
		catch (Exception $e)
		{
			// 例外錯誤處理。
			$sMsg = $e->getMessage();
			echo $sMsg;
		}

		wp_die(); // this is required to terminate immediately and return a proper response

		exit;
	}

	/**
	 * Check if SSL is enabled and notify the user
	 */
	public function admin_notices() {
		
		// Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected.
		if ( ( function_exists( 'wc_site_is_https' ) && ! wc_site_is_https() ) && ( 'no' === get_option( 'woocommerce_force_ssl_checkout' ) && ! class_exists( 'WordPressHTTPS' ) ) ) {
			echo '<div class="error stripe-ssl-message"><p>' . sprintf( __( 'Apple Pay is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate', 'ecpay' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '</p></div>';
		}
	}


	/**
	 * 驗證廠商是否有註冊
	 */
	public function curl_apple_pay_check_vendor() {
		
		global $woocommerce, $post, $wpdb;

		try
		{
			$sMsg = '' ;

			$ecpay_apple_pay = new Ecpay_ApplePay ;
			$ecpay_apple_pay->ServiceURL = 'https://apple-pay-gateway-cert.apple.com/paymentservices/startSession';
			$ecpay_apple_pay->Verify_Vendor['displayName']  = $this->apple_display_name;
			$ecpay_apple_pay->Verify_Vendor['crt_path']     = $this->apple_pay_crt_path;
			$ecpay_apple_pay->Verify_Vendor['key_path']     = $this->apple_pay_key_path;
			$ecpay_apple_pay->Verify_Vendor['key_password'] = $this->apple_pay_key_pass;

			if ($ecpay_apple_pay->Verify_Vendor_Test() == 'yes') {
				$sReturn_Msg = $ecpay_apple_pay->Verify_Vendor_Test();
			} else {
				$sReturn_Msg = $ecpay_apple_pay->Verify_Vendor();
			}

			echo esc_html($sReturn_Msg) ;
		}
		catch (Exception $e)
		{
			// 例外錯誤處理。
			$sMsg = $e->getMessage();
			echo $sMsg;
		}

		wp_die(); // this is required to terminate immediately and return a proper response

		exit;
	}

	/**
	 * 送出授權交易
	 */
	public function curl_apple_pay_check_out() {

		global $woocommerce, $post, $wpdb;

		$sPayment = isset($_POST['payment'])	? stripslashes(sanitize_text_field($_POST['payment']))	: '123456789abcdefgABCDEFG!@#$%^&*()' ;
		$order_id = isset($_POST['order_id'])	? sanitize_text_field($_POST['order_id'])	: '' ;

		$order = new WC_Order($order_id);

		try
		{
			$sMsg = '' ;
			$aMsg_Return = '';

			$ecpay_apple_pay = new Ecpay_ApplePay ;

			// 測試模式
			if ($this->ecpay_test_mode == 'yes')
			{
				$ecpay_apple_pay->ServiceURL = 'https://payment-stage.ecpay.com.tw/ApplePay/CreateServerOrder/V2';
				$ecpay_apple_pay->Send['MerchantTradeNo'] = date('YmdHis').$order_id;
				$aNext_Step['SimulatePaid'] = 0 ;

				$order->add_order_note($sPayment);

			} else {
				$ecpay_apple_pay->ServiceURL = 'https://payment.ecpay.com.tw/ApplePay/CreateServerOrder/V2';
				$ecpay_apple_pay->Send['MerchantTradeNo'] = $order_id;
				$aNext_Step['SimulatePaid'] = 0 ;
			}

			// 蒐集參數
			$ecpay_apple_pay->MerchantID 		= $this->MerchantID ;
			$ecpay_apple_pay->HashKey 			= $this->HashKey ;
			$ecpay_apple_pay->HashIV 			= $this->HashIV ;

			$ecpay_apple_pay->Send['MerchantTradeDate'] = date('Y/m/d H:i:s');
			$ecpay_apple_pay->Send['TotalAmount'] 		= $order->get_total() ;
			$ecpay_apple_pay->Send['CurrencyCode'] 		= 'TWD' ;
			$ecpay_apple_pay->Send['ItemName'] 		    = __('A Package Of Online Goods', 'ecpay');
			$ecpay_apple_pay->Send['PlatformID'] 		= '' ;
			$ecpay_apple_pay->Send['TradeDesc'] 		= 'wordpress applepay v1.0.10601' ;
			$ecpay_apple_pay->Send['PaymentToken'] 		= $sPayment ;
			$ecpay_apple_pay->Send['TradeType'] 		= 2 ;

			$aMsg_Return = $ecpay_apple_pay->Check_Out();
 			
			if( isset($aMsg_Return['RtnCode']) && $aMsg_Return['RtnCode'] == 1)
			{	
				//  異動訂單作業
				$comments = esc_html(print_r($aMsg_Return, true));
				if ($order->get_status() == 'pending') {
					$this->confirm_order($order, $comments, $aNext_Step) ;
				} else {
					# The order already paid or not in the standard procedure, do nothing
				}
			}
			else
			{
				$comments = esc_html(print_r($aMsg_Return, true));
				$order->add_order_note($comments);
			}

			echo json_encode($aMsg_Return);
		}
		catch (Exception $e)
		{
			// 例外錯誤處理。
			$sMsg = $e->getMessage();
			echo $sMsg ;
		}

		wp_die(); // this is required to terminate immediately and return a proper response

		exit;
	}

	/**
     * Check test mode by merchant id
     * @param string $merchantId Merchant ID
     * @return bool
     */
    public function isTestMode($merchantId = '')
    {
		$stageMerchantIds = array('2000132', '2000214');
        return in_array($merchantId, $stageMerchantIds) ? 'yes' : 'no';
    }
}

new WC_Ecpay_Apple_Pay();
