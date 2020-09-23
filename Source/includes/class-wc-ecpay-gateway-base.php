<?php
if (! defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Ecpay_Base extends WC_Payment_Gateway
{
    /**
     * 新增結帳 Actions
     *
     * @return void
     */
    protected function add_checkout_actions()
    {
        // 付款結果頁
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

        // 自訂轉導綠界付款頁
        add_action('ecpay_redirect_payment_center', array($this, 'ecpay_redirect_payment_center'));

        // 付款回應處理
        add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'receive_response'));

        // "返回商店"感謝頁
        add_action('woocommerce_thankyou_ecpay', array($this, 'thankyou_page'));
    }

    /**
     * 付款結果頁 Action
     *
     * @param  int $order_id
     * @return void
     */
    public function receipt_page($order_id)
    {
        if (has_action('woocommerce_api_ecpay_get_cvs_map')) {
            do_action('woocommerce_api_ecpay_get_cvs_map', $order_id);
        }
        do_action('ecpay_redirect_payment_center', $order_id);
    }
}
