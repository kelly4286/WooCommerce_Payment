<?php

include_once(plugin_dir_path( __FILE__ ) . 'class-wc-ecpay-gateway-base.php');

/**
 * 訂單新增備註 mail 通知信 (0:關閉/1:啟用)
 */
abstract class ECPay_OrderNoteEmail
{
    const PAYMENT_METHOD                 = 1; // 付款方式
    const PAYMENT_RESULT_CREDIT          = 1; // 付款結果-信用卡
    const PAYMENT_RESULT_WEB_ATM         = 1; // 付款結果-WebATM
    const PAYMENT_INFO_ATM               = 1; // 取號結果-ATM
    const PAYMENT_RESULT_ATM             = 1; // 付款結果-信用卡
    const PAYMENT_INFO_CVS_AND_BARCODE   = 1; // 取號結果-超商代碼/超商條碼
    const PAYMENT_RESULT_CVS_AND_BARCODE = 1; // 付款結果-超商代碼/超商條碼
    const PAYMENT_RESULT_EXCEPTION       = 1; // 付款結果-錯誤訊息
    const CONFIRM_ORDER                  = 1; // 訂單完成
    const CANCEL_ORDER                   = 1; // 訂單取消
}

/**
 * 綠界科技 - 一般付款
 */
class WC_Gateway_Ecpay extends WC_Gateway_Ecpay_Base
{
    public $ecpay_test_mode;
    public $ecpay_merchant_id;
    public $ecpay_hash_key;
    public $ecpay_hash_iv;
    public $ecpay_choose_payment;
    public $ecpay_payment_methods;
    public $helper;
    public $genHtml;

    public function __construct()
    {
        $this->id = 'ecpay';
        $this->method_title = $this->tran('ECPay');
        $this->method_description = $this->tran('ECPay is the most popular payment gateway for online shopping in Taiwan');
        $this->has_fields = true;

        # Load the form fields
        $this->init_form_fields();

        # Load the administrator settings
        $this->init_settings();

        $this->title                 = $this->get_option('title');
        $this->description           = $this->get_option('description');
        $this->ecpay_merchant_id     = $this->get_option('ecpay_merchant_id');
        $this->ecpay_hash_key        = $this->get_option('ecpay_hash_key');
        $this->ecpay_hash_iv         = $this->get_option('ecpay_hash_iv');

        # Load the helper
        $this->helper = ECPay_PaymentCommon::getHelper();
        $this->helper->setMerchantId($this->ecpay_merchant_id);
        $this->ecpay_test_mode = ($this->helper->isTestMode($this->ecpay_merchant_id)) ? 'yes' : 'no';

        # Load ECPay.Payment.Html
        $this->genHtml = ECPay_PaymentCommon::genHtml();

        # Get the payment methods        
        $ecpay_payment_methods = array();
        foreach($this->helper->ecpayPaymentMethods as $ecpayPaymentMethods) {
            $ecpay_payment_methods[$ecpayPaymentMethods] = $this->get_payment_desc($ecpayPaymentMethods);
        }
        $this->ecpay_payment_methods = $ecpay_payment_methods;
        $this->get_payment_options();

        # Register a action to save administrator settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_payment_options'));

        $this->add_checkout_actions();
        $this->add_get_plugin_info_filters();

        # 訂單明細頁
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'action_woocommerce_admin_order_status_cancel'));

    }

    /**
     * 後台 - 載入參數設定欄位
     */
    public function init_form_fields()
    {
        $this->form_fields = include( untrailingslashit( plugin_dir_path( ECPAY_PAYMENT_MAIN_FILE ) ) . '/includes/settings-ecpay.php' );
    }

    /**
     * Display the form when chooses ECPay payment
     */
    public function payment_fields()
    {
        if ( is_checkout() || is_wc_endpoint_url( 'order-pay' ) ) {
            // 產生 Html
            $data = array(
                'payment_options' => $this->payment_options,
                'ecpay_payment_methods' => $this->ecpay_payment_methods
            );
            echo $this->genHtml->show_ecpay_payment_fields($data);
        } else {
            echo $this->tran('Duplicate payment is not supported, please try to place the order once again.');
        }
        if (!empty($this->description)) {
            echo $this->helper->addNextLine($this->description);
        }
    }

    /**
     * 後台-付款方式區塊
     */
    function generate_ecpay_payment_methods_html()
    {
        ob_start();

        // 產生 Html
        $args = [
            'id' => $this->id,
            'payment_options' => $this->payment_options,
            'ecpay_payment_methods' => $this->ecpay_payment_methods,
            'ecpay_payment_methods_special' => $this->helper->ecpayPaymentMethodsSpecial
        ];
        wc_get_template('admin/ECPay-admin-settings-payment-methods.php', $args, '', ECPAY_PAYMENT_PLUGIN_PATH . 'templates/');

        return ob_get_clean();
    }

    /**
     * 後台-更新付款方式
     */
    function process_admin_payment_options()
    {
        $options = array();
        if (isset($this->ecpay_payment_methods) === true) {
            foreach ($this->ecpay_payment_methods as $key => $value) {
                if (array_key_exists($key, $_POST)) $options[] = $key ;
            }
        }

        update_option($this->id . '_payment_options', $options);
        $this->get_payment_options();
    }

    /**
     * 取得當前開啟的付款方式
     */
    function get_payment_options()
    {
        global $wp;
        $setupOptions = array_filter((array) get_option($this->id . '_payment_options'));

        $order_items = null;
        if (isset($wp->query_vars['order-pay']) && absint($wp->query_vars['order-pay']) > 0) {
            // 在獨立的付款頁面進行結帳
            $order_id = absint($wp->query_vars['order-pay']);
            $order = wc_get_order($order_id);

            if ($order != null) {
                $items = $order->get_items();
                foreach ($items as $order_item) {
                    $product_id = $order_item->get_product_id();
                    $order_items[] = $product_id;
                }
            }
        } elseif (WC()->cart != null) {
            // 在一般付款頁面結帳
            foreach (WC()->cart->get_cart() as $key => $value) {
                $order_items[] = $value['product_id'];
            }
        } 
        
        if ($order_items == null) {
            // Admin 設定頁面查詢目前有哪些選項打開
            $this->payment_options = $setupOptions; 
            return;
        }

        $use_all = false;
        if (count($order_items) > 5) {
            $use_all = false;
        } else {
            foreach ($order_items as $product_id) {
                // 只有 product type = service 才能使用分期付款
                $product = wc_get_product($product_id);
                if ($product->get_type() === 'service') {
                    $use_all = true;
                }
            }
        }
        $this->payment_options = array_filter(
            $setupOptions, 
            function ($str) use ($use_all) {
                if ($use_all) return true;

                // 將分期付款排除
                if (str_contains($str, "Credit_")) {
                    return false;
                }
                return true;
            }
        );
    }

    /**
     * Check the payment method and the chosen payment
     */
    public function validate_fields()
    {

        $choose_payment = sanitize_text_field($_POST['ecpay_choose_payment']);
        $payment_desc = $this->get_payment_desc($choose_payment);
        if ($_POST['payment_method'] == $this->id && !empty($payment_desc)) {
            $this->ecpay_choose_payment = $choose_payment;
            return true;
        } else {
            $this->ECPay_add_error($this->tran( $this->helper->msg['invalidPayment']) . $payment_desc);
            return false;
        }
    }

    /**
     * Process the payment
     */
    public function process_payment($order_id)
    {
        # Update order status
        $order = new WC_Order($order_id);
        $order->update_status('pending', $this->tran('Awaiting ECPay payment'));
        # Set the ECPay payment type to the order note
        $order->add_order_note($this->ecpay_choose_payment, ECPay_OrderNoteEmail::PAYMENT_METHOD);

        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    public function order_received($order_id)
    {
		$intHasChanged = get_post_meta($order_id, '_'.$this->id.'_order-status_has_changed', true);
		if ($intHasChanged) return;

		$order = new WC_Order($order_id);
		$strOrderStatus = $order->get_status();
		if ($strOrderStatus != 'pending' && $strOrderStatus != 'on-hold') return;

		if ($this->get_option('status_change') != $strOrderStatus) {
			$order->update_status($this->get_option('status_change'));
			update_post_meta($order_id, '_'.$this->id.'_order-status_has_changed', 1);
		}
	}

    /**
     * Process the callback
     */
    public function receive_response($order_id)
    {
        $result_msg = '1|OK';
        $order = null;
        try {
            # Retrieve the check out result
            $data = array(
                'hashKey' => $this->ecpay_hash_key,
                'hashIv'=> $this->ecpay_hash_iv,
            );
            $ecpay_feedback = $this->helper->getValidFeedback($data);

            if (count($ecpay_feedback) < 1) {
                throw new Exception('Get ECPay feedback failed.');
            } 
            
            # Get the cart order id
            $cart_order_id = $ecpay_feedback['MerchantTradeNo'];
            if ($this->ecpay_test_mode == 'yes') {
                $cart_order_id = substr($ecpay_feedback['MerchantTradeNo'], 12);
            }

            # Get the cart order amount
            $order = new WC_Order($cart_order_id);
            $cart_amount = $order->get_total();

            # Check the amounts
            $ecpay_amount = $ecpay_feedback['TradeAmt'];
            if (round($cart_amount) != $ecpay_amount) {
                throw new Exception('Order ' . $cart_order_id . ' amount are not identical.');
            } 
            
            # Set the common comments
            $comments = sprintf(
                $this->tran('Payment Method : %s<br />Trade Time : %s<br />'),
                esc_html($ecpay_feedback['PaymentType']),
                esc_html($ecpay_feedback['TradeDate'])
            );

            # Set the getting code comments
            $return_code = esc_html($ecpay_feedback['RtnCode']);
            $return_message = esc_html($ecpay_feedback['RtnMsg']);
            $get_code_result_comments = sprintf(
                $this->tran('Getting Code Result : (%s)%s'),
                $return_code,
                $return_message
            );

            # Set the payment result comments
            $payment_result_comments = sprintf(
                "ECpay 回傳刷卡成功\r\n交易狀態: %s\r\n交易訊息: %s\r\n交易編號: %s\r\n訂單金額: %s\r\n付款時間: %s",
                $ecpay_feedback['RtnCode'],
                $ecpay_feedback['RtnMsg'],
                $ecpay_feedback['MerchantTradeNo'],
                $ecpay_feedback['TradeAmt'],
                $ecpay_feedback['TradeDate']
            );

            # Set the fail message
            $fail_msg = sprintf('Order %s Exception.(%s: %s)', $cart_order_id, $return_code, $return_message);

            # Get ECPay payment method
            $ecpay_payment_method = $this->helper->getPaymentMethod($ecpay_feedback['PaymentType']);

            # Set the order comments

            switch($ecpay_payment_method) {
                case ECPay_PaymentMethod::Credit:
                    if ($return_code != 1 and $return_code != 800) {
                        throw new Exception($fail_msg);
                    } 
                    
                    if (!$this->is_order_complete($order)) {
                        $this->confirm_order($order, $payment_result_comments, $ecpay_feedback);

                        // 增加ECPAY付款狀態
                        add_post_meta( $order->id, 'ecpay_payment_tag', 1, true);

                    } else {
                        # The order already paid or not in the standard procedure, do nothing
                        //throw new Exception('The order already paid or not in the standard procedure ' . $cart_order_id . '.');
                        $nEcpay_Payment_Tag = get_post_meta($order->id, 'ecpay_payment_tag', true);
                        if($nEcpay_Payment_Tag == 0) {
                            $order->add_order_note($payment_result_comments, ECPay_OrderNoteEmail::PAYMENT_RESULT_CREDIT);
                            add_post_meta( $order->id, 'ecpay_payment_tag', 1, true);
                        }
                    }
                    break;
                case ECPay_PaymentMethod::WebATM:
                case ECPay_PaymentMethod::GooglePay:
                    if ($return_code != 1 and $return_code != 800) {
                        throw new Exception($fail_msg);
                    } 
                    
                    if (!$this->is_order_complete($order)) {
                        $this->confirm_order($order, $payment_result_comments, $ecpay_feedback);

                        // 增加ECPAY付款狀態
                        add_post_meta( $order->id, 'ecpay_payment_tag', 1, true);
                    } else {
                        # The order already paid or not in the standard procedure, do nothing
                        // throw new Exception('The order already paid or not in the standard procedure ' . $cart_order_id . '.');
                        $nEcpay_Payment_Tag = get_post_meta($order->id, 'ecpay_payment_tag', true);
                        if ($nEcpay_Payment_Tag == 0) {
                            $order->add_order_note($payment_result_comments, ECPay_OrderNoteEmail::PAYMENT_RESULT_WEB_ATM);
                            add_post_meta( $order->id, 'ecpay_payment_tag', 1, true);
                        }
                    }
                    break;
                case ECPay_PaymentMethod::ATM:
                    if ($return_code != 1 and $return_code != 2 and $return_code != 800) {
                        throw new Exception($fail_msg);
                    } 
                    
                    if ($return_code == 2) {
                        # Set the getting code result
                        $comments .= $this->get_order_comments($ecpay_feedback);
                        $comments .= $get_code_result_comments;
                        $order->add_order_note($comments, ECPay_OrderNoteEmail::PAYMENT_INFO_ATM);

                        // 紀錄付款資訊提供感謝頁面使用
                        add_post_meta( $order->id, 'payment_method', 'ATM', true);
                        add_post_meta( $order->id, 'BankCode', sanitize_text_field($ecpay_feedback['BankCode']), true);
                        add_post_meta( $order->id, 'vAccount', sanitize_text_field($ecpay_feedback['vAccount']), true);
                        add_post_meta( $order->id, 'ExpireDate', sanitize_text_field($ecpay_feedback['ExpireDate']), true);
                    } else {
                        if (!$this->is_order_complete($order)) {
                            $this->confirm_order($order, $payment_result_comments, $ecpay_feedback);

                            // 增加ECPAY付款狀態
                            add_post_meta( $order->id, 'ecpay_payment_tag', 1, true);

                        } else {
                            # The order already paid or not in the standard procedure, do nothing
                            // throw new Exception('The order already paid or not in the standard procedure ' . $cart_order_id . '.');
                            $nEcpay_Payment_Tag = get_post_meta($order->id, 'ecpay_payment_tag', true);
                            if ($nEcpay_Payment_Tag == 0) {
                                $order->add_order_note($payment_result_comments, ECPay_OrderNoteEmail::PAYMENT_RESULT_ATM);
                                add_post_meta( $order->id, 'ecpay_payment_tag', 1, true);
                            }
                        }
                    }
                    break;
                case ECPay_PaymentMethod::CVS:
                case ECPay_PaymentMethod::BARCODE:
                    if ($return_code != 1 and $return_code != 800 and $return_code != 10100073) {
                        throw new Exception($fail_msg);
                    }
                    
                    if ($return_code == 10100073) {
                        # Set the getting code result
                        $comments .= $this->get_order_comments($ecpay_feedback);
                        $comments .= $get_code_result_comments;
                        $order->add_order_note($comments, ECPay_OrderNoteEmail::PAYMENT_INFO_CVS_AND_BARCODE);

                        if ($ecpay_payment_method == ECPay_PaymentMethod::CVS ) {
                            // 紀錄付款資訊提供感謝頁面使用
                            add_post_meta( $order->id, 'payment_method', 'CVS', true);
                            add_post_meta( $order->id, 'PaymentNo', sanitize_text_field($ecpay_feedback['PaymentNo']), true);
                            add_post_meta( $order->id, 'ExpireDate', sanitize_text_field($ecpay_feedback['ExpireDate']), true);
                        }
                    } else {
                        if (!$this->is_order_complete($order)) {
                            $this->confirm_order($order, $payment_result_comments, $ecpay_feedback);

                            // 增加ECPAY付款狀態
                            add_post_meta( $order->id, 'ecpay_payment_tag', 1, true);
                        } else {
                            # The order already paid or not in the standard procedure, do nothing
                            // throw new Exception('The order already paid or not in the standard procedure ' . $cart_order_id . '.');
                            $nEcpay_Payment_Tag = get_post_meta($order->id, 'ecpay_payment_tag', true);
                            if ($nEcpay_Payment_Tag == 0) {
                                $order->add_order_note($payment_result_comments, ECPay_OrderNoteEmail::PAYMENT_RESULT_CVS_AND_BARCODE);
                                add_post_meta( $order->id, 'ecpay_payment_tag', 1, true);
                            }
                        }
                    }
                    break;
                default:
                    throw new Exception('Invalid payment method of the order ' . $cart_order_id . '.');
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            if (!empty($order)) {
                $comments .= sprintf($this->tran('Failed To Pay<br />Error : %s<br />'), $error);
                $order->add_order_note($comments);
            }

            # Set the failure result
            $result_msg = '0|' . $error;
        }
        wp_redirect( $this->get_return_url($order) );
        exit;
    }


    # Custom function

    /**
     * Translate the content
     * @param  string   translate target
     * @return string   translate result
     */
    private function tran($content, $domain = 'ecpay')
    {
        if ($domain == 'ecpay') {
            return __($content, 'ecpay');
        } else {
            return __($content, 'woocommerce');
        }
    }

    /**
     * Get the payment method description
     * @param  string   payment name
     * @return string   payment method description
     */
    private function get_payment_desc($payment_name)
    {
        $payment_desc = array(
            'Credit'    => $this->tran('Credit'),
            'Credit_3'  => $this->tran('Credit(3 Installments)'),
            'Credit_6'  => $this->tran('Credit(6 Installments)'),
            'Credit_12' => $this->tran('Credit(12 Installments)'),
            'Credit_18' => $this->tran('Credit(18 Installments)'),
            'Credit_24' => $this->tran('Credit(24 Installments)'),
            'UnionPay'  => $this->tran('UnionPay'),
            'WebATM'    => $this->tran('WEB-ATM'),
            'ATM'       => $this->tran('ATM'),
            'CVS'       => $this->tran('CVS'),
            'BARCODE'   => $this->tran('BARCODE')
        );

        return $payment_desc[$payment_name];
    }

    /**
     * Check if the order status is complete
     * @param  object   order
     * @return boolean  is the order complete
     */
    private function is_order_complete($order)
    {
        $status = '';
        $status = (method_exists($order,'get_status') == true ) ? $order->get_status() : $order->status;

        if ($status == 'pending') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get the order comments
     * @param  array    ECPay feedback
     * @return string   order comments
     */
    public function get_order_comments($ecpay_feedback)
    {
        $comments = array(
            'ATM' =>
                sprintf(
                    $this->tran('Bank Code : %s<br />Virtual Account : %s<br />Payment Deadline : %s<br />'),
                    esc_html($ecpay_feedback['BankCode']),
                    esc_html($ecpay_feedback['vAccount']),
                    esc_html($ecpay_feedback['ExpireDate'])
                ),
            'CVS' =>
                sprintf(
                    $this->tran('Trade Code : %s<br />Payment Deadline : %s<br />'),
                    esc_html($ecpay_feedback['PaymentNo']),
                    esc_html($ecpay_feedback['ExpireDate'])
                ),
            'BARCODE' =>
                sprintf(
                    $this->tran('Payment Deadline : %s<br />BARCODE 1 : %s<br />BARCODE 2 : %s<br />BARCODE 3 : %s<br />'),
                    esc_html($ecpay_feedback['ExpireDate']),
                    esc_html($ecpay_feedback['Barcode1']),
                    esc_html($ecpay_feedback['Barcode2']),
                    esc_html($ecpay_feedback['Barcode3'])
                )
        );
        $payment_method = $this->helper->getPaymentMethod($ecpay_feedback['PaymentType']);

        return $comments[$payment_method];
    }

    /**
     * Complete the order and add the comments
     * @param  object   order
     */
    public function confirm_order($order, $comments, $ecpay_feedback)
    {
        $order->add_order_note($comments, ECPay_OrderNoteEmail::CONFIRM_ORDER);

        $order->payment_complete();

        // 加入信用卡後四碼，提供電子發票開立使用 v1.1.0911
        if (isset($ecpay_feedback['card4no']) && !empty($ecpay_feedback['card4no']))
        {
            add_post_meta( $order->get_id(), 'card4no', sanitize_text_field($ecpay_feedback['card4no']), true);
        }

        // 自動開立發票
        $this->auto_invoice($order->get_id(), $ecpay_feedback);
    }

    /**
     * Output for the order received page.
     *
     * @param int $order_id
     */
    public function thankyou_page( $order_id )
    {

        $this->payment_details( $order_id );

    }

    /**
     * Get payment details and place into a list format.
     *
     * @param int $order_id
     */
    private function payment_details( $order_id = '' )
    {
        $account_html = '';
        $has_details = false ;
        $a_has_details = array();

        $payment_method = get_post_meta($order_id, 'payment_method', true);

        switch ($payment_method) {
            case ECPay_PaymentMethod::CVS:
                $PaymentNo = get_post_meta($order_id, 'PaymentNo', true);
                $ExpireDate = get_post_meta($order_id, 'ExpireDate', true);

                $a_has_details = array(
                    'PaymentNo' => array(
                        'label' => $this->tran( 'PaymentNo' ),
                        'value' => $PaymentNo
                    ),
                    'ExpireDate' => array(
                        'label' => $this->tran( 'ExpireDate' ),
                        'value' => $ExpireDate
                    )
                );

                $has_details = true ;
                break;

            case ECPay_PaymentMethod::ATM:
                $BankCode = get_post_meta($order_id, 'BankCode', true);
                $vAccount = get_post_meta($order_id, 'vAccount', true);
                $ExpireDate = get_post_meta($order_id, 'ExpireDate', true);

                $a_has_details = array(
                    'BankCode' => array(
                        'label' => $this->tran( 'BankCode' ),
                        'value' => $BankCode
                    ),
                    'vAccount' => array(
                        'label' => $this->tran( 'vAccount' ),
                        'value' => $vAccount
                    ),
                    'ExpireDate' => array(
                        'label' => $this->tran( 'ExpireDate' ),
                        'value' => $ExpireDate
                    )
                );

                $has_details = true ;
                break;
        }

        $account_html .= '<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">' . PHP_EOL;

        foreach ($a_has_details as $field_key => $field ) {
            $account_html .= '<li class="' . esc_attr( $field_key ) . '">' 
            . wp_kses_post( $field['label'] ) . ': <strong>' 
            . wp_kses_post( wptexturize( $field['value'] ) ) . '</strong></li>' . PHP_EOL ;
        }

        $account_html .= '</ul>';

        if ( $has_details ) {
            echo '<section><h2>' . $this->tran( 'Payment details' ) . '</h2>' . PHP_EOL . $account_html . '</section>';
        }
    }

    /**
     * 無效訂單狀態更新
     *
     * @return void
     */
    public function action_woocommerce_admin_order_status_cancel()
    {
        try {
            global $post;

            // 訂單編號
            $order_id = $post->ID;

            // 是否反查過訂單
            $is_expire = get_post_meta($order_id, '_ecpay_payment_is_expire', true);

            if ($is_expire === $this->helper->isExpire['no']) {

                // 取得傳入資料
                $order                      = wc_get_order($order_id);
                $order_status               = $order->get_status();                                                // 訂單狀態
                $payment_method             = $order->get_payment_method();                                        // 付款方式
                $date_created               = $order->get_date_created()->getTimestamp();                          // 訂單建立時間
                $ecpay_payment_method       = get_post_meta($order_id, '_ecpay_payment_method', true);             // 綠界付款方式
                $stage_payment_order_prefix = get_post_meta($order_id, '_ecpay_payment_stage_order_prefix', true); // 測試訂單編號前綴
                $hold_stock_minutes         = empty(get_option('woocommerce_hold_stock_minutes')) ? 0 : get_option('woocommerce_hold_stock_minutes'); // 取得保留庫存時間

                // 組合傳入資料
                $data = array(
                    'hashKey'            => $this->ecpay_hash_key,
                    'hashIv'             => $this->ecpay_hash_iv,
                    'orderId'            => $order_id,
                    'holdStockMinute'    => $hold_stock_minutes,
                    'orderStatus'        => $order_status,
                    'paymentMethod'      => $payment_method,
                    'ecpayPaymentMethod' => $ecpay_payment_method,
                    'createDate'         => $date_created,
                    'stageOrderPrefix'   => $stage_payment_order_prefix,
                );
                $feedback = $this->helper->expiredOrder($data);

                // 交易失敗
                if (isset($feedback['TradeStatus']) && $feedback['TradeStatus'] == $this->helper->tradeStatusCodes['emptyPaymentMethod']) {
                    // 更新訂單狀態/備註
                    $order->add_order_note($this->tran( $this->helper->msg['unpaidOrder'], 'woocommerce' ), ECPay_OrderNoteEmail::CANCEL_ORDER);
                    $order->update_status('cancelled');
                    update_post_meta($order_id, '_ecpay_payment_is_expire', $this->helper->isExpire['yes'] );

                    // 提示
                    $args = [
                        'msg' => $this->tran('The order has changed, please refresh your browser.')
                    ];
                    wc_get_template('admin/ECPay-admin-order-expire.php', $args, '', ECPAY_PAYMENT_PLUGIN_PATH . 'templates/');
                }
            }
        } catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 記錄目前成功付款次數
     *
     * @param  integer $order_id            訂單編號
     * @param  array   $total_success_times 付款次數
     * @return void
     */
    private function note_success_times($order_id, $total_success_times)
    {
        $nTotalSuccessTimes = ( isset($total_success_times) && ( empty($total_success_times) || $total_success_times == 1 ))  ? '' :  $total_success_times;
        update_post_meta($order_id, '_total_success_times', $nTotalSuccessTimes );
    }

    /**
     * 自動開立發票
     *
     * @param  integer $order_id
     * @return void
     */
    private function auto_invoice($order_id, $ecpay_feedback)
    {
        // call invoice model
        $invoice_active_ecpay   = 0 ;

        // 取得目前啟用的外掛
        $active_plugins = (array) get_option( 'active_plugins', array() );

        // 加入其他站點啟用的外掛
        $active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

        // 判斷ECPay發票模組是否有啟用
        foreach ($active_plugins as $key => $value) {
            if ((strpos($value, '/woocommerce-ecpayinvoice.php') !== false)) {
                $invoice_active_ecpay = 1;
            }
        }

        // 自動開立發票
        if ($invoice_active_ecpay == 1) {
            $aConfig_Invoice = get_option('wc_ecpayinvoice_active_model') ;

            // 記錄目前成功付款到第幾次
            $this->note_success_times($order_id, $ecpay_feedback['TotalSuccessTimes']);

            if (isset($aConfig_Invoice) && $aConfig_Invoice['wc_ecpay_invoice_enabled'] == 'enable' && $aConfig_Invoice['wc_ecpay_invoice_auto'] == 'auto' ) {
                do_action('ecpay_auto_invoice', $order_id, $ecpay_feedback['SimulatePaid']);
            }
        }
    }

    /**
     * Add a WooCommerce error message
     * @param string $error_message
     */
    private function ECPay_add_error($error_message)
    {
        wc_add_notice(esc_html($error_message), 'error');
    }
    
    /**
     * 組出給綠界 API 的商品項目敘述
     */
    public function get_items_str($order) 
    {
		$str = false;

		foreach ($order->get_items() as $key => $value) {
			if (!$str) {
				$str = '';
			} else {
				$str .= '#';
			}
			$str .= $value['name'].' x '.$value['qty'].' '.$value['line_total'];
		}
	    return $str;
	}

    /**
     * 轉導綠界付款頁
     *
     * @param int $order_id
     * @return void
     */
    public function ecpay_redirect_payment_center($order_id)
    {
        # Clean the cart
        global $woocommerce;
        $woocommerce->cart->empty_cart();

        // 撈取訂單資訊
        $order = new WC_Order($order_id);
        $notes = $order->get_customer_order_notes();

        // 儲存訂單資訊
        $stage_order_prefix = $this->helper->getMerchantOrderPrefix();
        $data = array(
            'ecpay_test_mode'    => $this->ecpay_test_mode,
            'order_id'           => $order_id,
            'notes'              => count($notes) > 0 ? $notes[0] : "",
            'stage_order_prefix' => $stage_order_prefix,
            'is_expire'          => $this->helper->isExpire['no'],
        );
        ECPay_PaymentCommon::ecpay_save_payment_order_info($data);
        
        try {
            # Get the chosen payment and installment
            $notes = $order->get_customer_order_notes();
            $choose_payment = isset($notes[0]) ? $notes[0]->comment_content : '';

            $data = array(
                'choosePayment'     => $choose_payment,
                'hashKey'           => $this->ecpay_hash_key,
                'hashIv'            => $this->ecpay_hash_iv,
                'returnUrl'         => $this->get_return_url($order),
                'orderResultUrl'    => add_query_arg('wc-api', 'WC_Gateway_Ecpay', home_url('/').'?order_id='.$order_id),
                'periodReturnURL'   => add_query_arg('wc-api', 'WC_Gateway_Ecpay_DCA', home_url('/')),
                'clientBackUrl'     => $this->get_return_url($order),
                'orderId'           => $order->get_id(),
                'total'             => $order->get_total(),
                'itemName'          => $this->get_items_str($order),
                'cartName'          => 'woocommerce',
                'currency'          => $order->get_currency(),
                'needExtraPaidInfo' => 'Y',
            );

            $this->helper->checkout($data);
            exit;
        } catch(Exception $e) {
            $this->ECPay_add_error($e->getMessage());
        }
    }

    /**
     * 新增取得模組資訊 Filters
     *
     * @return void
     */
    private function add_get_plugin_info_filters()
    {
        $filters = array(
            'ecpay_is_payment_enabled',
            'ecpay_get_payment_plugin_version',
        );
        $parent = $this;
        array_walk($filters, function ($value) use ($parent) {
            add_filter($value, array($parent, $value));
        });
    }

    /**
     * 檢查金流模組是否啟用
     *
     * @return bool
     */
    public function ecpay_is_payment_enabled()
    {
        $enabled = false;
        try {
            if (!property_exists($this, 'id')) {
                throw new Exception('Property "id" does not exist!');
            }

            $setting = get_option( 'woocommerce_' . $this->id . '_settings', '' );
            if (empty($setting)) {
                throw new Exception('Payment settings is empty!');
            }

            if (!isset($setting['enabled'])) {
                throw new Exception('Payment settings "enabled" is empty!');
            }

            $enabled = $setting['enabled'];
        } catch (Exception $e) {

        }

        return $enabled;
    }

    /**
     * 取得金流模組版本
     *
     * @return string
     */
    public function ecpay_get_payment_plugin_version()
    {
        $version = '';
        if (defined('ECPAY_PAYMENT_PLUGIN_VERSION')) {
            $version = ECPAY_PAYMENT_PLUGIN_VERSION;
        }

        return $version;
    }
}

/**
 * 金流共用功能
 */
class ECPay_PaymentCommon
{
    /**
     * 取得Helper
     * @return object
     */
    public static function getHelper()
    {
        $helper = new ECPayPaymentHelper();

        # 設定時區
        $helper->setTimezone(static::getTimezone());

        # 設定訂單狀態
        $helper->setOrderStatus(static::getOrderStatus());

        return $helper;
    }

    /**
     * 產生 Html
     * @return object
     */
    public static function genHtml()
    {
        $genHtml = new ECPayPaymentGenerateHtml();
        return $genHtml;
    }

    /**
     * 取得時區
     *
     * @return array
     */
    public static function getTimezone()
    {
        $timezone = (get_option('timezone_string') === '') ? date_default_timezone_get() : get_option('timezone_string');

        return $timezone;
    }

    /**
     * 訂單狀態
     *
     * @return array
     */
    public static function getOrderStatus()
    {
        $data = array(
            'Pending'    => 'pending',
            'Processing' => 'processing',
            'OnHold'     => 'on-hold',
            'Cancelled'  => 'cancelled',
            'Ecpay'      => 'ecpay',
        );

        return $data;
    }

    /**
     * 儲存訂單資訊
     * @param  integer $order_id 訂單編號
     * @return void
     */
    public static function ecpay_save_payment_order_info($data)
    {
        // 儲存測試模式訂單編號前綴
        $stage_order_prefix = isset($data['stage_order_prefix']) ? $data['stage_order_prefix'] : '' ;
        add_post_meta($data['order_id'], '_ecpay_payment_stage_order_prefix', sanitize_text_field($stage_order_prefix), true);

        // 儲存付款方式
        $notes_comment_content = isset($data['notes']) ? $data['notes']->comment_content : '' ;
        add_post_meta($data['order_id'], '_ecpay_payment_method', sanitize_text_field($notes_comment_content), true);

        // 是否做過訂單反查檢查，預設'N'(否)
        add_post_meta($data['order_id'], '_ecpay_payment_is_expire', sanitize_text_field($data['is_expire']), true);
    }
}
?>
