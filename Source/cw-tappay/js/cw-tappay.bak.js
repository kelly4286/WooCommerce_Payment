var CWTAPPAY;

(function($){

	CWTAPPAY={

		ID				:CWTAPPAY_vars.id, 
		current		:CWTAPPAY_vars.current, 
		AppID			:CWTAPPAY_vars.app_id, 
		AppKey		:CWTAPPAY_vars.app_key, 
		ENV				:CWTAPPAY_vars.environment, 
		Fields		:CWTAPPAY_vars.fields, 
		GetPrime	:false, 
		Form			:false, 

		AddCard		:false, 
		PayAgain	:false, 

		Init:function(){

			var arrRequired=['my-account', 'checkout'];

			if(arrRequired.indexOf(this.current)<0)return;

			if($('form.woocommerce-checkout').length===1){
				this.Form=$('form.woocommerce-checkout');

			}else if($('form#order_review').length===1){
				this.Form			=$('form#order_review');
				this.PayAgain	=true;

			}else if($('form#add_payment_method').length===1){
				this.Form			=$('form#add_payment_method');
				this.AddCard	=true;

			}else{
				return;
			}

			this.Form.on('click', '#place_order', function(e){
				var intResult;

				//e.preventDefault();

				$(this).attr('disabled', 'disabled');

				intResult=CWTAPPAY.FormHandler();

				if(intResult){
					$(this).removeAttr('disabled');
				}

				return intResult;

			});

			/*
			 * 2019.12.26
			 * 先確認 #cwtpfw_payment-data 欄位是否存在，避免 JS 錯誤
			 */
			if($('#cwtpfw_payment-data').length===0)return;

			TPDirect.setupSDK(~~this.AppID, this.AppKey, this.ENV);

			if(this.current=='my-account'){
				CWTAPPAY.LoadPaymentField();

			}else{
				$(document.body).on('change', {}, function(){
					CWTAPPAY.LoadPaymentField();
				});

				if(this.PayAgain){
					$('#wc-cwtpfw-payment-token-new').click().trigger('click');
					//CWTAPPAY.LoadPaymentField();
				}
			}

			$(document.body).on('checkout_error', {}, this.ErrorHandler);
			//$(document).on(CWTAPPAY.ID+'_error-result', this.ErrorHandler);

		}, 

		LoadPaymentField:function(){

			var CardViewStyle=this.GetStyle();

			if(this.Fields=='3'){
				if($('#card-number').find('iframe').length>0)return;
				TPDirect.card.setup(CardViewStyle);

			}else{
				if($('#'+CWTAPPAY.ID+'_iframe').find('iframe').length>0)return;
				TPDirect.card.setup('#'+CWTAPPAY.ID+'_iframe', CardViewStyle);
			}

			TPDirect.card.onUpdate(function(update){
				CWTAPPAY.GetPrime=update.canGetPrime;
				if(update.canGetPrime===true){
					//console.log('update', update);
				}
			});
		}, 

		GetStyle:function(){
			var CardViewStyle;
			if(this.Fields=='3'){
				CardViewStyle={
					fields:{
						number:{
							// css selector
							element			:'#card-number', 
							placeholder	:'**** **** **** ****'}, 
						expirationDate:{
							// DOM object
							element			:document.getElementById('card-expiration-date'), 
							placeholder	:'MM / YY'}, 
						ccv:{
							element			:'#card-ccv', 
							placeholder	:'CCV'}}, 
					styles:{
						// Style all elements
						'input':{
							'color':'#333'}, 

						// Styling ccv field
						'input.cvc':{'font-size':'14px'}, 

						// Styling expiration-date field
						'input.expiration-date':{'font-size':'14px'}, 

						// Styling card-number field
						'input.card-number':{'font-size':'14px'}, 

						// style focus state
						':focus':{'color':'#333'}, 

						// style valid state
						'.valid':{'color':'green'}, 

						// style invalid state
						'.invalid':{'color':'red'}, 

						// Media queries
						// Note that these apply to the iframe, not the root window.
						'@media screen and (max-width:400px)':{
							'input':{
								'color':'#999'}}}
				};

			}else{
				CardViewStyle={
					color				:'rgb(0,0,0)', 
					//fontSize		:'19px', 
					fontSize		:'1em', 
					lineHeight	:'24px', 
					fontWeight	:'300', 
					errorColor:	'red', 
					placeholderColor: ''};
			}
			return CardViewStyle;
		}, 

		FormHandler:function(B){

			var PaymentToken;
			var strResult;
			var intSinglePayment	=false, 
					strErrorResult		=false;

			var ReadyToPay=function(){

				TPDirect.card.getPrime(function(result){

					$('#place_order').removeAttr('disabled');

					if(result.status!==0){
						CWTAPPAY.InputErrorResult(result);

					}else{
						strResult=encodeURIComponent(JSON.stringify(result, null, 4));

						CWTAPPAY.Form.append('<input type="hidden" id="'+CWTAPPAY.ID+'_result" name="'+CWTAPPAY.ID+'_result" value="'+strResult+'" />')
							.submit();

						return false;
					}
				});
			};

			if($('#'+CWTAPPAY.ID+'_error').length>0)$('#'+CWTAPPAY.ID+'_error').remove();

			if(this.AddCard){
				if($('input[name="payment_method"]').length>1){
					PaymentToken=$('input[name="payment_method"]:checked');

				}else{
					PaymentToken=$('#payment_method_'+this.ID);
					if(PaymentToken.length===1){
						intSinglePayment=true;
						PaymentToken.prop('checked', true);
					}
				}

			}else{

				if($('input[name="payment_method"]:checked').val()!=this.ID){
					//$('#place_order').removeAttr('disabled');
					return true; // 當使用者未選擇 TapPay 付款，直接 submit
				}

				PaymentToken=$('input[name="wc-'+this.ID+'-payment-token"]:checked');

				if($('input[name="wc-'+CWTAPPAY.ID+'-payment-token"]').length===0){
					intSinglePayment=true;
					CWTAPPAY.Form.append('<input type="hidden" id="wc-'+CWTAPPAY.ID+'-payment-token" name="wc-'+CWTAPPAY.ID+'-payment-token" value="new" />');
				}
			}

			if($('#payment_method_'+this.ID).prop('checked')||intSinglePayment){

				if(this.AddCard){ // my-account add card
					if(this.GetPrime){
						ReadyToPay();

					}else{
						strErrorResult='請確認您輸入的信用卡號是否正確';
						//this.InputErrorResult('請確認您輸入的信用卡號是否正確');
					}

				}else{ // checkout
					if(PaymentToken.length===0){
						//this.InputErrorResult('請輸入信用卡資料');
						if(this.GetPrime){
							ReadyToPay();

						}else{
							strErrorResult='請確認您輸入的信用卡號是否正確';
							//this.InputErrorResult('請確認您輸入的信用卡號是否正確');
						}


					}else{
						if(PaymentToken.val()=='new'){ // pay by prime
							if(this.GetPrime){
								ReadyToPay();

							}else{
								strErrorResult='請確認您輸入的信用卡號是否正確';
								//this.InputErrorResult('請確認您輸入的信用卡號是否正確');
							}

						}else{ // pay by token
							return true;
						}
					}
				}

				if(strErrorResult){
					this.InputErrorResult(strErrorResult);

					$('#place_order').removeAttr('disabled');
					$(document).trigger(CWTAPPAY.ID+'_error-result', [strErrorResult]);
				}

				return false;
			}

			return true;
		}, 

		InputErrorResult:function(result){
			var strHTML			='', 
					strMessage	='';

			var DisplayError=function(){
				strHTML='<div id="'+CWTAPPAY.ID+'_error" class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"><ul class="woocommerce-error" role="alert"><li>'+strMessage+'</li></ul></div>';

				//$('div.payment_method_'+CWTAPPAY.ID).find('> p').after(strHTML);
				$('div.payment_method_'+CWTAPPAY.ID).prepend(strHTML);
			};

			if(typeof result=='string'){
				strMessage=result;
			}else{
				strMessage=result.msg;
			}

			DisplayError();
		}, 

		ErrorHandler:function(e){ // after .woocommerce-NoticeGroup > ul.woocommerce-error show
			var I=CWTAPPAY.Form.find('input#'+CWTAPPAY.ID+'_result');
			if(I.length>0)I.remove();
		}, 

	};

	$(document).ready(function(){
		CWTAPPAY.Init();
	});

}(jQuery));