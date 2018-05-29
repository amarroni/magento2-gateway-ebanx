<?php
namespace DigitalHub\Ebanx\Gateway\Response\Argentina\CreditCard;

use Magento\Payment\Gateway\Response\HandlerInterface;

class RefundHandler implements HandlerInterface
{
    protected $_logger;
    protected $ebanxHelper;

    public function __construct(
        \DigitalHub\Ebanx\Helper\Data $ebanxHelper,
        \DigitalHub\Ebanx\Logger\Logger $logger
    )
    {
        $this->ebanxHelper = $ebanxHelper;
        $this->_logger = $logger;

        $this->_logger->info('RefundHandler :: __construct');
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);
        $payment = $payment->getPayment();

        $this->_logger->info('RefundHandler :: handle');

        $payment->setIsTransactionPending(false);
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
    }
}