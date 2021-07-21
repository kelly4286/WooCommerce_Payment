<?php
defined('ABSPATH') || exit();
?>

<div id="<?php echo $strHandlerID;?>_payment-data">

	<fieldset id="wc-<?php echo esc_attr($strHandlerID);?>-cc-form" class='wc-credit-card-form wc-payment-form'>

		<!--Tappay iframe-->
		<div id="<?php echo $strHandlerID;?>_iframe"></div>
		<!--Tappay iframe-->

		<?php if ($intShowSavedCard):?>
		<!--Tappay save card-->
		<label for="<?php echo $strHandlerID;?>_remember">
			<input type="checkbox" id="<?php echo $strHandlerID;?>_remember" name="<?php echo $strHandlerID;?>_remember"<?php echo $strChecked;?> value="1" />
			<span>
				<svg x="0px" y="0px" width="23px" height="23px" viewBox="0 0 23 23">
					<polyline points="16.609,8.252 10.113,14.749 6.391,11.026 "/>
					<circle cx="11.5" cy="11.5" r="10"/>
				</svg>
				存入帳號
			</span>
		</label>
		<!--Tappay save card-->
		<?php endif;?>

		<label>
			<div style="margin-top: 10px; margin-bottom: 10px">信用卡分期</div>
			<select style="margin-bottom: 0em;" name="<?php echo $strHandlerID;?>_instalment" id="<?php echo $strHandlerID;?>_instalment">
				<option value="0">一次付清</option>
				<option value="3">3期0利率</option>
				<option value="6">6期0利率</option>
			</select>
		</label>

	</fieldset>
</div>
