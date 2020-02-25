<?php
/**
 * 後台 - 訂單明細頁無效訂單提示
 */

defined('ECPAY_PAYMENT_PLUGIN_PATH') || exit;

?>

<!-- template -->
<p class="form-field form-field-wide" style="color:red;">※ <?php echo $msg; ?> </p>

<script>
    alert("<?php echo $msg; ?>");
</script>