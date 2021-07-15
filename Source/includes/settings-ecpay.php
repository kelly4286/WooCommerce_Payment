<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters( 'wc_ecpay_payment_settings',
	array(
		'enabled' => array(
			'title' 	=> $this->tran( 'Enable/Disable' ),
			'type' 		=> 'checkbox',
			'label' 	=> $this->tran( 'Enable' ),
			'default' 	=> 'no'
		),
		'title' => array(
			'title' 	  => $this->tran( 'Title' ),
			'type' 		  => 'text',
			'description' => $this->tran( 'This controls the title which the user sees during checkout.' ),
			'default' 	  => $this->tran( 'ECPay' ),
			'desc_tip'    => true,
		),
		'description' => array(
			'title' 	  => $this->tran( 'Description' ),
			'type' 		  => 'textarea',
			'description' => $this->tran( 'This controls the description which the user sees during checkout.' ),
			'desc_tip'    => true,
		),
		'ecpay_merchant_id' => array(
			'title' 	=> $this->tran( 'Merchant ID' ),
			'type' 		=> 'text',
			'default' 	=> '2000132'
		),
		'ecpay_hash_key' => array(
			'title' 	=> $this->tran( 'Hash Key' ),
			'type' 		=> 'text',
			'default' 	=> '5294y06JbISpM5x9'
		),
		'ecpay_hash_iv' => array(
			'title' 	=> $this->tran( 'Hash IV' ),
			'type' 		=> 'text',
			'default' 	=> 'v77hoKGq4kWxNNIS'
		),
		'ecpay_payment_methods' => array(
			'type' 		=> 'ecpay_payment_methods',
		),
		'apple_pay_advanced' => array(
			'title'       => $this->tran( 'Apple Pay設定' ),
			'type'        => 'title',
			'description' => '',
		),
		'apple_pay_check_button' => array(
			'title'       => '<button type="button" id="apple_pay_ca_test">' . $this->tran( '測試憑證' ) . '</button>' ,
			'type'        => 'title',
			'description' => '',
		),
		'ecpay_apple_pay_key_path' => array(
			'title'		  => $this->tran( 'key憑證路徑' ),
			'type' 		  => 'text',
			'description' => $this->tran( 'Apple Pay 憑證安裝絕對路徑，請勿安裝在public目錄中以防憑證遭竊' ),
			'default' 	  => '/etc/httpd/ca/path/',
			'desc_tip'    => true,
		),
		'ecpay_apple_pay_crt_path' => array(
			'title'	      => $this->tran( 'crt憑證路徑' ),
			'type' 		  => 'text',
			'description' => $this->tran( 'Apple Pay 憑證安裝絕對路徑，請勿安裝在public目錄中以防憑證遭竊' ),
			'default' 	  => '/etc/httpd/ca/path/',
			'desc_tip'    => true,
		),
		'ecpay_apple_pay_key_pass' => array(
			'title'		  => $this->tran( '憑證密碼' ),
			'type' 		  => 'password',
			'description' => $this->tran( 'Apple Pay 憑證密碼' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'ecpay_apple_display_name' => array(
			'title'	      => $this->tran( '註冊名稱' ),
			'type' 		  => 'text',
			'description' => $this->tran( 'Apple Pay 註冊名稱' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		/*
		,'ecpay_apple_pay_button' => array(
			'title'       	=> $this->tran( 'Apple Pay Button Style' ),
			'label'       	=> $this->tran( 'Button Style' ),
			'type'        	=> 'select',
			'description' 	=> $this->tran( 'Select the button style you would like to show.' ),
			'default'     	=> 'black',
			'desc_tip'    	=> true,
			'options'     	=> array(
				'black' => $this->tran( 'Black' ),
				'white' => $this->tran( 'White' ),
			),
		),
		*/

		'sandbox' => array(
			'title'			=> '測試模式',
			'type'			=> 'checkbox',
			'label'			=> '啟用', 
			'description'	=> '',
			'default'		=> '',
			'desc_tip'		=> false
		), 
		'method_ecpay' => array(
			'title'			=> '綠界收款',
			'type'			=> 'checkbox',
			'label'			=> '啟用', 
			'description'	=> '',
			'default'		=> '',
			'desc_tip'		=> false
		), 
		'method_allpay' => array(
			'title'			=> '歐付寶收款',
			'type'			=> 'checkbox',
			'label'			=> '啟用', 
			'description'	=> '',
			'default'		=> '',
			'desc_tip'		=> false
		), 
		'order_button_text' => array(
			'title'			=> '結帳按鈕文字',
			'type'			=> 'text',
			'description'	=> '',
			'default'		=> '歐付寶線上刷卡',
			'desc_tip'		=> false
		),
		'status_change'	=> array(
			'title'			=> '訂單狀態',
			'type'			=> 'select',
			'description'	=> '成功付款或成功建立訂單後的訂單狀態',
			'options'		=> array(
				'processing'	=> '處理中', 
				'completed'		=> '已完成'
			),
			'default'		=> 'processing',
			'class'			=> 'wc-enhanced-select',
			'desc_tip'		=> false
		),
		'process_payment_lock' => array(
			'title'			=> '同訂單再次刷卡間隔',
			'type'			=> 'number',
			'default'		=> '0', 
			'description'	=> '鎖定同訂單再次刷卡的區隔時間，單位秒，0 為不限制',
			'desc_tip'		=> false
		),
	)
);
