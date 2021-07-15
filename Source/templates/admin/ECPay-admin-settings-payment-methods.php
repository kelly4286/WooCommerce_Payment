<?php
/**
 * 後台 - 設定頁付款方式區塊
 */

defined('ECPAY_PAYMENT_PLUGIN_PATH') || exit;

?>

<!-- template -->
<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="woocommerce_ecpay_ecpay_payment_methods">付款方式</label>
    </th>
    <td class="forminp" id="<?php echo esc_attr($id .'_payment_options');?>">
        <table class="shippingrows widefat" cellspacing="0">
            <tbody>
            <?php
                // 付款方式
                foreach ($ecpay_payment_methods as $key => $value) {
                    // 判斷是否勾選
                    $checked = '';
                    if (in_array($key, $payment_options)) {
                        $checked = 'checked';
                    }
            ?>
                <tr class="option-tr">
                    <td>
                        <input type="checkbox" name="<?php echo esc_attr($key);?>" value="<?php echo esc_attr($key);?>" <?php echo $checked;?> ><?php echo $value;?>
                    </td>
                </tr>
            <?php }?>
            </tbody>
        </table>
    </td>
</tr>
