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
		)
	)
);
