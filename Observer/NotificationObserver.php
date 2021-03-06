<?php
namespace DigitalHub\Ebanx\Observer;

class NotificationObserver implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct
    (
        \DigitalHub\Ebanx\Helper\Data $ebanxHelper,
        \DigitalHub\Ebanx\Logger\Logger $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction
    )
    {
        $this->_ebanxHelper = $ebanxHelper;
        $this->_logger = $logger;
        $this->_storeManager = $storeManager;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
    }

	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		$transactionData = $observer->getData('transaction_data');
        $hash = $transactionData->getHash();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $transactionSearch = $objectManager->create('\Magento\Sales\Api\Data\TransactionSearchResultInterface');
        $transactionSearch->addFieldToFilter('txn_id', $hash);

        if(count($transactionSearch->getItems())){
            $transaction = $transactionSearch->getFirstItem();

            $order = $objectManager->create('\Magento\Sales\Model\Order')->load($transaction->getOrderId());

            // Get Payment Details
            $ebanxConfig = new \Ebanx\Benjamin\Models\Configs\Config([
                'integrationKey' => $this->_ebanxHelper->getConfigData('digitalhub_ebanx_global', 'live_integration_key'),
                'sandboxIntegrationKey' => $this->_ebanxHelper->getConfigData('digitalhub_ebanx_global', 'sandbox_integration_key'),
                'isSandbox' => (int)$this->_ebanxHelper->getConfigData('digitalhub_ebanx_global', 'sandbox'),
                'baseCurrency' => $this->_storeManager->getStore()->getBaseCurrencyCode(),
            ]);
            $paymentResult = EBANX($ebanxConfig)->paymentInfo()->findByHash($hash);

            // Invoice if status is CO (Confirmed)
            if($paymentResult && $paymentResult['payment'] && $paymentResult['payment']['status'] == 'CO'){
                // Payment Confirmed
                if($order->canInvoice()) {
                    $invoice = $this->_invoiceService->prepareInvoice($order);
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                    $invoice->register();
                    $invoice->save();
                    $transactionSave = $this->_transaction
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());
                    $transactionSave->save();
                    $order->addStatusHistoryComment(__('Invoice #'.$invoice->getIncrementId().' created automatically'))
                        ->setIsCustomerNotified(false)
                        ->save();
                }
            }

            // Cancel if status is CA (Cancelled)
            if($paymentResult && $paymentResult['payment'] && $paymentResult['payment']['status'] == 'CA'){
                // Payment Canceled
                if($order->canCancel()) {
                    $order->cancel()->save();
                    $order->addStatusHistoryComment(__('Order cancelled automatically'))
                        ->setIsCustomerNotified(false)
                        ->save();
                }
            }

        } else {
            throw new \Exception('Transaction not found with hash: ' . $hash);
        }

		return $this;
	}
}
