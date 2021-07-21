var CWTPFW;

(function ($) {

	CWTPFW = {

		FORM: false,
		AddCard: false,

		Init: function () {

			var intAppID = CWTPFW_vars.app_id;

			if ($('form.woocommerce-checkout').length === 1 || $('form#order_review').length === 1) {
				this.Form = $('form.woocommerce-checkout');
				if ($('form#order_review').length === 1) this.Form = $('form#order_review');

			} else if ($('form#add_payment_method').length === 1) {
				this.Form = $('form#add_payment_method');
				this.AddCard = true;

			} else {
				return;
			}

			TPDirect.setupSDK(~~intAppID, CWTPFW_vars.app_key, CWTPFW_vars.environment);

			this.Form.on('click', '#place_order', function (e) {
				return CWTPFW.FormHandler();
			});

			/*===當輸入的信用卡資料有誤時===*/
			$('body').on('cw-tappay-error', {}, function (e, response) {
				CWTPFW.ErrorHandler(response);
			});

			/*===當顯示任何 WooCommerce checkout error 時===*/
			$(document.body).on('checkout_error', {}, function () {
				$('#cwtpfw_token').remove();
			});

		},

		ErrorHandler: function (strMessage) {
			$('.woocommerce_error, .woocommerce-error, .woocommerce-message, .woocommerce_message').remove();
			$('#cwtpfw-card-number').closest('p').before('<ul class="woocommerce_error woocommerce-error"><li>' + strMessage + '</li></ul>');

			$('#cwtpfw_token').remove();

			CWTPFW.Form.unblock();
		},

		CollectData: function () {

			var stdData = {
				'number': $('#cwtpfw-card-number').val().replace(/\s+/g, ''),
				'cvc': $('#cwtpfw-card-cvc').val(),
				'expirymonth': $('#cwtpfw-card-expiry-month').val(),
				'expiryyear': $('#cwtpfw-card-expiry-year').val()
			};

			stdData.expiryyear = stdData.expiryyear.toString().slice(-2);

			CWTPFW.Form.block({
				message: null,
				overlayCSS: {
					background: '#FFF',
					opacity: 0.6
				}
			});

			return stdData;
		},

		FormHandler: function () {

			var stdData;

			if (($('#payment_method_cwtpfw').is(':checked') && ($('#wc-cwtpfw-payment-token-new').length === 0 || $('#wc-cwtpfw-payment-token-new').is(':checked'))) 
			|| CWTPFW.AddCard === true) {
				stdData = CWTPFW.CollectData();
				TPDirect.card.createToken(stdData.number, stdData.expirymonth, stdData.expiryyear, stdData.cvc, CWTPFW.Response);
				return false;
			}

			return true;
		},

		AjaxPost: function (data, callback) {

			$.ajax({
				type: 'POST',
				data: data,
				dataType: 'json',
				url: CWTPFW_vars.ajaxurl,

			}).always(function (response) {
				//console.log('always', response);

			}).done(function (response) {
				//console.log('done', response);
				if (typeof callback == 'function') callback(response);

			}).fail(function (response, textStatus, errorThrown) {
				console.log('fail', response);

			});

		},

		Response: function (result) {

			var data;

			if (result.status === 0) {

				CWTPFW.Form.append('<input type="hidden" id="cwtpfw_token" name="cwtpfw_token" value="' + result.card.prime + '" />')
					.submit();

				//CWTPFW.Form.unblock();
			} else {

				data = { 'action': 'RequestResponseText', 'msg': result.msg };

				CWTPFW.AjaxPost(data, function (json) {
					//$('body').trigger('cw-tappay-error', result);
					$('body').trigger('cw-tappay-error', json.msg);
				});

				//CWTPFW.Form.submit();

			}

		},

	};

	$(document).ready(function () {
		CWTPFW.Init();
	});

}(jQuery));
