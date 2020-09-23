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
		)
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
	)
);
