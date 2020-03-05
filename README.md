綠界科技 WooCommerce 金流模組
===============
<p align="center">
    <img alt="Last Release" src="https://img.shields.io/github/release/ECPay/WooCommerce_Payment.svg">
</p>

* 綠界科技金流外掛套件(以下簡稱外掛套件)，提供合作特店以及個人會員使用開放原始碼商店系統時，無須自行處理複雜的檢核，直接透過安裝設定外掛套件，便可以較快速的方式介接綠界科技的金流系統。

* 收款方式清單：
	* 信用卡(一次付清、分期付款、定期定額)
	* 網路ATM
	* ATM櫃員機
	* 超商代碼
	* 超商條碼
	* Apple Pay

* 注意事項：
	* 若須同時使用物流模組，除了更新金流模組外，物流模組也請同步更新才能正常使用。
	* 本模組訂單扣庫存數量是在付款完成後才進行扣除，所以如果付款方式為非即時完成，例如：超商代碼、ATM，庫存會於消費者實際繳款後才扣除。限量商品請避免使用非即時金流收款。


目錄
-----------------
* [支援版本](#支援版本)
* [綠界科技ApplePay系統介接相關資訊](#綠界科技ApplePay系統介接相關資訊)
	1. [須具備條件](#須具備條件)
	2. [測試準備項目](#測試準備項目)
	3. [廠商管理後台測試環境](#廠商管理後台測試環境)
	4. [正式環境金鑰取得資訊](#正式環境金鑰取得資訊)
	5. [客戶環境支援資訊](#客戶環境支援資訊)
* [WooCommerce安裝準備事項](#WooCommerce安裝準備事項)
* [套件安裝](#套件安裝)
* [設定與功能項目](#設定與功能項目)
	1. [綠界科技](#綠界科技)
	2. [綠界科技定期定額](#綠界科技定期定額)
	3. [ApplePay](#ApplePay)
* [ApplePay 開發者前置準備說明](#ApplePay開發者前置準備說明)
* [APPLE 開發者帳號申請說明](#APPLE開發者帳號申請說明)
* [技術支援](#技術支援)
* [參考資料](#參考資料)
* [附錄](#附錄)
	1. [測試串接參數](#測試串接參數)
	2. [curl SSL版本](#curl_SSL版本)
	3. [個人資料交換(.p12)轉換(linux環境)](#)
* [版權宣告](#版權宣告)



支援版本
-----------------
| Wordpress  | WooCommerce | PHP |
| :---------: | :----------: | :----------: |
| 4.5.3 / 4.6.1 / 4.7.3 / 4.8.0 ~ 4.8.2 / 5.2.1 / 5.3.2 | 3.1.0 ~ 3.1.2 / 3.2.0 / 3.4.6 / 3.6.4 / 3.9.2 | 5.6 以上 |



綠界科技ApplePay系統介接相關資訊
-----------------
#### 須具備條件
1. 需成為綠界的特約商店 (申請時間約10-15天)
1. APPLE僅接受銷售實體商品業者(非虛擬商品如遊戲點數)
1. 商店必須有APPLE的開發者帳號(個人/公司)
    (公司帳號申請時間約一個月,APPLE收年費為USD100)
1. APPLE PAY的付款交易須自備SSL加密環境
1. APPLE PAY交易環境必須支援Transport Layer Security (TLS) 1.2 ，包含消費者手機環境及廠商伺服器環境

#### 測試準備項目
請在 [測試環境申請註冊會員](https://member-stage.ecpay.com.tw/MemberReg/MemberRegister?back=N)
完成後請提供下列資訊：
1. 申請綠界測試環境會員編號
1. 申請APPLE PAY的MerchantID
1. APPLE PAY交易憑証的加密密碼(必須設置密碼)
1. APPLE PAY交易憑証(Apple Pay Payment Processing)
(請匯出P12格式並壓縮zip檔)
1. APPLE PAY交易憑証的到期日
1. 商店驗證事件取得merchantSession中的PaymentToken物件(須消費者付款指紋認証後才會取得，提供時無須經過AES加密的資料)
1. 建立付款請求物件的交易金額

將上述資料寄件至`techsupport@ecpay.com.tw`信箱，我們將由專人進行測試相關設定作業後，會通知廠商串接信用授權串接服務測試。

#### 廠商管理後台測試環境
https://vendor-stage.ecpay.com.tw
此網站可提供：
1. 查詢ecpay訂單
1. 測試環境金鑰取得資訊

`系統開發管理` -> `系統介接設定` 取得介接 `HashKey` 及 `HashIV` 資訊。

※ 注意：Apple Pay台灣尚未開放平台商審核使用，申請串接時一個店家須申請一個APPLE PAY 的MerchantID及憑證。

#### 正式環境金鑰取得資訊
請登入[廠商後台](https://vendor.ecpay.com.tw/) -> `系統開發管理` -> `系統介接設定` 取得介接 `HashKey` 及 `HashIV` 資訊。

#### 客戶環境支援資訊
* 目前Apple Pay支援
	1. iPhone 6之後機種 (包含iPhone SE)、iPad Air 2、iPad mini 3與iPad Pro系列
	1. Apple Watch系列
	1. 2012年以後推出的Mac與MacBook系列

* 注意事項

	僅有搭載NFC近場感應機能的iPhone與Apple Watch可透過零售通路刷卡機付款，包含iPad系列、Mac系列機種僅能在App內，或是透過Safari瀏覽器在網頁服務中使用Apple Pay付款。


WooCommerce安裝準備事項
-----------------
無論您使用Windows、Linux或任何伺服器，在安裝本外掛套件前，請先確定該伺服器是否支援PHP的curl模組。

#### 注意事項

* 外掛套件僅支援 UTF8 語系版本的PHP商店系統。
* 如果您使用的PHP是利用AppServ軟體架設在Windows的環境，請參考以下說明將curl模組掛上：
> 1. 在WINDOWS的目錄下找到php.ini這個檔。
> 2. 使用文字編輯器(UltraEdit、EmEdit或其他)開啟php.ini檔案。
> 3. 找到extension=php_curl.dll這一行，將前面的分號移除，並儲存檔案。
> 4. 重新啟動Apache伺服器。
> 5. 若仍然無法啟動curl模組，可以按照下面步驟嘗試修正：
> 		* 在..\AppServ\php5\下找到libeay32.dll及ssleay32.dll。
> 		* 在..\AppServ\php5\ext\下找到php_curl.dll。
> 		* 將上述三個檔案複製到%windir%/system32下。
> 		* 重新啟動Apache伺服器即可。

套件安裝
-----------------
#### 解壓縮套件檔
將下載的套件檔解壓縮，解壓縮完成後中會有一份壓縮檔「ecpay.zip」，用來上傳的外掛套件。

#### 上傳模組
`購物車後台` -> `外掛(Plugins)` -> `安裝外掛(Add New)` -> `上傳外掛(Upload Plugin)` -> `選擇檔案(選擇壓縮檔「ecpay_shipping.zip」)`-> `立即安裝(Install Now)`。

#### 啟用模組
安裝完成後，畫面會顯示是否安裝成功，若安裝成功會出現`啟用外掛(Active Plugin)`的按鈕，按下`啟用外掛(Active Plugin)`後即可開始使用外掛套件。



設定與功能項目
-----------------

#### 綠界科技
##### 設定路徑
* `購物車後台` -> `WooCommerce` -> `設定(Settings)` -> `結帳(Checkout)` -> `綠界科技(ECPay)`。

##### 必要參數
* 特店編號(Merchant ID)
* 金鑰(Hash Key)
* 向量(Hash IV)

##### 注意事項
* 請注意 Hash Key 與 Hash IV 內容不可包含空白。
* 本外掛套件提供測試模式的設定，用於連接到ECPay提供客戶的介接環境，請勿在正式營運的環境中開啟測試模式。
* 本外掛套件於ECPay廠商後台按下模擬付款按鈕後，是會變更訂單狀態為已付款的，請勿在正式營運的環境中點選模擬付款。

#### 綠界科技定期定額
##### 設定路徑
* `購物車後台` -> `WooCommerce` -> `設定(Settings)` -> `結帳(Checkout)` -> `綠界科技定期定額(ECPay Paid Automatically)`。

#### ApplePay
##### 設定路徑
* `購物車後台` -> `WooCommerce` -> `設定(Settings)` -> `結帳(Checkout)` -> `綠界科技(ECPay)`。

##### 注意事項
* 請先完成Apple Pay 相關設定步驟，請參考[ApplePay 開發者前置準備說明](#ApplePay開發者前置準備說明)。
* curl SSL Version需要使用openssl。
* key 憑證路徑與crt憑證路徑請勿放在網頁公用區域，以防憑證遭竊取風險。
* key與crt憑證是透過個人資訊交換 (.p12)，請使用openssl進行轉換。


ApplePay開發者前置準備說明
-----------------
* 請參考文件 [ECPay - Apple Pay介接技術文件](https://www.ecpay.com.tw/Content/files/ecpay_APPLEPAY.pdf) [2. ApplePay 開發者前置準備說明]



APPLE開發者帳號申請說明
-----------------
* 請參考文件 [ECPay - Apple Pay介接技術文件](https://www.ecpay.com.tw/Content/files/ecpay_APPLEPAY.pdf) [9. APPLE 開發者帳號申請說明]

技術支援
-----------------
綠界技術服務工程師信箱: techsupport@ecpay.com.tw



參考資料
-----------------
* [ECPay - Apple Pay介接技術文件](https://www.ecpay.com.tw/Content/files/ecpay_APPLEPAY.pdf)
* [ECPay - 全方位金流介接技術文件](https://www.ecpay.com.tw/Content/files/ecpay_011.pdf)



附錄
-----------------

#### 測試串接參數

|  欄位名稱 | 欄位內容  |
| :------------: | :------------: |
|  特店編號(MerchantID) | 2000132 |
|  介接 HashKey |  5294y06JbISpM5x9 |
|  介接 HashIV |  v77hoKGq4kWxNNIS |

#### curl_SSL版本
* SSL Version 要為 OpenSSL

#### 個人資料交換(.p12)轉換(linux環境)
* `openssl pkcs12 -in ApplePayMerchantIdentity_and_privatekey.p12 -out ApplePay.crt.pem -clcerts -nokeys`
* `openssl pkcs12 -in ApplePayMerchantIdentity_and_privatekey.p12 -out ApplePay.key.pem -nocerts`



版權宣告
-----------------
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html