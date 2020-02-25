<?php

class ECPayPaymentGenerateHtml
{
    /**
     * Display the form when chooses ECPay payment
     *
     * @param  array $data
     * @return void
     */
    public function show_ecpay_payment_fields($data)
    {
        // 宣告參數
        $payment_options = $data['payment_options'];
        $ecpay_payment_methods = $data['ecpay_payment_methods'];

        // Html
        $szHtml  = '';

        $szHtml .= $this->tran('Payment Method') . ' : ';
        $szHtml .= '<select name="ecpay_choose_payment">';
        foreach ($ecpay_payment_methods as $payment_method => $value) {
            if (in_array($payment_method, $payment_options)) {
                $szHtml .= '<option value="' . esc_attr($payment_method) . '">';
                $szHtml .=    esc_html($value);
                $szHtml .= '</option>';
            }
        }
        $szHtml .= '</select>';

        return $szHtml;
    }

    /**
     * ApplePay 付款按鈕 Html
     *
     * @param  array $data
     * @return void
     */
    public function show_applepay_button($data)
    {
        // 宣告參數
        $apple_pay_button = $data['apple_pay_button'];
        $apple_pay_button_lang = $data['apple_pay_button_lang'];

        ?>
        <!-- Html -->
        <div class="apple-pay-button-wrapper">
            <button class="apple-pay-button" id="apple-pay-button" style="display: none;" lang="<?php echo esc_attr( $apple_pay_button_lang ); ?>" style="-webkit-appearance: -apple-pay-button; -apple-pay-button-type: buy; -apple-pay-button-style: <?php echo esc_attr( $apple_pay_button ); ?>;" ></button>
        </div>
        <?php
    }

    /**
     * 後台 - 定期定額設定頁 Html
     *
     * @param  array $ecpay_dca
     * @return void
     */
    public function show_ecpay_dca_html($data)
    {
        // 宣告參數
        $ecpay_dca = $data['ecpay_dca'];

        ?>
        <!-- Html -->
        <tr valign="top">
            <th scope="row" class="titledesc"><?php echo $this->tran('ECPay Paid Automatically Details'); ?></th>
            <td class="forminp" id="ecpay_dca">
                <table class="widefat wc_input_table sortable" cellspacing="0" style="width: 600px;">
                    <thead>
                        <tr>
                            <th class="sort">&nbsp;</th>
                            <th><?php echo $this->tran('Peroid Type'); ?></th>
                            <th><?php echo $this->tran('Frequency'); ?></th>
                            <th><?php echo $this->tran('Execute Times'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="accounts">
                        <?php
                            if (
                                sizeof($ecpay_dca) === 1
                                && $ecpay_dca[0]["periodType"] === ''
                                && $ecpay_dca[0]["frequency"] === ''
                                && $ecpay_dca[0]["execTimes"] === ''
                            ) {
                                // 初始預設定期定額方式
                                $ecpay_dca = [
                                    [
                                        'periodType' => "Y",
                                        'frequency' => "1",
                                        'execTimes' => "6",
                                    ],
                                    [
                                        'periodType' => "M",
                                        'frequency' => "1",
                                        'execTimes' => "12",
                                    ],
                                ];
                            }

                            $i = -1;
                            if ( is_array($ecpay_dca) ) {
                                foreach ( $ecpay_dca as $dca ) {
                                    $i++;
                                    echo '<tr class="account">
                                        <td class="sort"></td>
                                        <td><input type="text" class="fieldPeriodType" value="' . esc_attr( $dca['periodType'] ) . '" name="periodType[' . $i . ']" maxlength="1" required /></td>
                                        <td><input type="number" class="fieldFrequency" value="' . esc_attr( $dca['frequency'] ) . '" name="frequency[' . $i . ']"  min="1" max="365" required /></td>
                                        <td><input type="number" class="fieldExecTimes" value="' . esc_attr( $dca['execTimes'] ) . '" name="execTimes[' . $i . ']"  min="2" max="999" required /></td>
                                    </tr>';
                                }
                            }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4">
                                <a href="#" class="add button"><?php echo $this->tran('add'); ?></a>
                                <a href="#" class="remove_rows button"><?php echo $this->tran('remove'); ?></a>
                            </th>
                        </tr>
                    </tfoot>
                </table>
                <p class="description"><?php echo $this->tran('Don\'t forget to save modify.'); ?></p>
                <p id="fieldsNotification" style="display: none;">
                    <?php echo $this->tran('ECPay paid automatically details has been repeatedly, please confirm again.'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     *
     *
     * @param  array $data
     * @return void
     */
    public function show_ecpay_dca_payment_fields($data)
    {
        // 宣告變數
        $ecpay_dca  = $data['ecpay_dca'];
        $cart_total = $data['cart_total'];
        $periodTypeMethod = [
            'Y' => ' ' . $this->tran('year'),
            'M' => ' ' . $this->tran('month'),
            'D' => ' ' . $this->tran('day')
        ];

        // Html
        $szHtml  = '';
        $szHtml .= '<select id="ecpay_dca_payment" name="ecpay_dca_payment">';
        $szHtml .= '    <option>------</option>';
        foreach ($ecpay_dca as $dca) {
            $option = sprintf(
                $this->tran('NT$ %d / %s %s, up to a maximun of %s'),
                $cart_total,
                $dca['frequency'],
                $periodTypeMethod[$dca['periodType']],
                $dca['execTimes']
            );
            $szHtml .= $this->generate_option($dca['periodType'] . '_' . $dca['frequency'] . '_' . $dca['execTimes'], $option);
        }
        $szHtml .= '</select>';

        $szHtml .= '<div id="ecpay_dca_show"></div>';
        $szHtml .= '<hr style="margin: 12px 0px;background-color: #eeeeee;">';
        $szHtml .= '<p style="font-size: 0.8em;color: #c9302c;">';
        $szHtml .= '你將使用<strong>綠界科技定期定額信用卡付款</strong>，請留意你所購買的商品為<strong>非單次扣款</strong>商品。';
        $szHtml .= '</p>';

        return $szHtml;
    }

    /**
     * Translate the content
     * @param  string $content translate target
     * @return string translate result
     */
    private function tran($content)
    {
        return __($content, 'ecpay');
    }

    /**
     * Generate Select Option
     *
     * @param  string $value
     * @param  string $data
     * @return string $szHtml
     */
    private function generate_option($value, $data)
    {
        $szHtml  = '';
        $szHtml .= '<option value="' . esc_attr($value) . '">';
        $szHtml .=      esc_html($data);
        $szHtml .= '</option>';

        return $szHtml;
    }
}