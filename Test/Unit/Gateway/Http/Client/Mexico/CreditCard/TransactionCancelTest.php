<?php
namespace DigitalHub\Ebanx\Test\Unit\Gateway\Http\Client\Mexico\CreditCard;

use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Gateway\Http\TransferInterface;
use DigitalHub\Ebanx\Gateway\Http\Client\Mexico\CreditCard\TransactionCancel;

class TransactionCancelTest extends \PHPUnit\Framework\TestCase
{
    public const SANDBOX_INTEGRATION_KEY = '09347509347509347504975037';
    public $fakeEbanxFacade;

    public function testPlaceRequestSuccess()
    {
        \Mockery::close(); // to "re-overload" classes

        $expectedRequest = [
            'payment_hash' => '053497503497093450359475'
        ];

        $expectedResponse = [
            'cancel_result' => [
                'status' => 'SUCCESS'
            ]
        ];

        // DI constructor args
        $context = $this->getMockBuilder(\Magento\Framework\Model\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $encryptor = $this->getMockBuilder(\Magento\Framework\Encryption\EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder(\DigitalHub\Ebanx\Logger\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ebanxHelper = $this->getMockBuilder(\DigitalHub\Ebanx\Helper\Data::class)
            ->setMethods(['getConfigData'])
            ->disableOriginalConstructor()
            ->getMock();
        $storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data = [];

        // methods
        $fakeStoreMock = new \Magento\Framework\DataObject();
        $fakeStoreMock->setData([
            'base_currency_code' => 'USD'
        ]);

        $storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($fakeStoreMock);

        $ebanxHelper->expects($this->any())
            ->method('getConfigData')
            ->withConsecutive(
                ['digitalhub_ebanx_global', 'live_integration_key'],
                ['digitalhub_ebanx_global', 'sandbox_integration_key'],
                ['digitalhub_ebanx_global', 'sandbox'],
                ['digitalhub_ebanx_global/cc', 'max_installments'],
                ['digitalhub_ebanx_global/cc', 'min_installment_value']
            )
            ->willReturnOnConsecutiveCalls(null, self::SANDBOX_INTEGRATION_KEY, 1, 12, 10);

        $fakeEbanxFacade = \Mockery::mock('overload:Ebanx\Benjamin\Facade');
        $fakeEbanxFacade->shouldReceive('addConfig')
            ->withAnyArgs()
            ->andReturn($fakeEbanxFacade);

        $transactionClient = new TransactionCancel($context, $encryptor, $logger, $ebanxHelper, $storeManager, $data);

        $fakeEbanxCancel = \Mockery::mock('overload:Ebanx\Benjamin\Services\CancelPayment');

        $fakeEbanxCancel->shouldReceive('request')
            ->with($expectedRequest['payment_hash'])
            ->once()
            ->andReturn([
                'status' => 'SUCCESS'
            ])
            ->byDefault();

        $fakeEbanxFacade->shouldReceive('cancelPayment')
            ->once()
            ->andReturn($fakeEbanxCancel);

        $transferObject = $this->getMockBuilder(TransferInterface::class)->getMock();
        $transferObject->expects($this->any())
            ->method('getBody')
            ->willReturn($expectedRequest);

        $this->assertEquals(
            $expectedResponse,
            $transactionClient->placeRequest($transferObject)
        );
    }

    public function testPlaceRequestException()
    {
        \Mockery::close(); // to "re-overload" classes

        $expectedRequest = [
            'payment_hash' => '053497503497093450359475'
        ];

        $expectedResponse = [
            'error' => 'Error Message'
        ];

        // DI constructor args
        $context = $this->getMockBuilder(\Magento\Framework\Model\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $encryptor = $this->getMockBuilder(\Magento\Framework\Encryption\EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder(\DigitalHub\Ebanx\Logger\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ebanxHelper = $this->getMockBuilder(\DigitalHub\Ebanx\Helper\Data::class)
            ->setMethods(['getConfigData'])
            ->disableOriginalConstructor()
            ->getMock();
        $storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data = [];

        // methods
        $fakeStoreMock = new \Magento\Framework\DataObject();
        $fakeStoreMock->setData([
            'base_currency_code' => 'USD'
        ]);

        $storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($fakeStoreMock);

        $ebanxHelper->expects($this->any())
            ->method('getConfigData')
            ->withConsecutive(
                ['digitalhub_ebanx_global', 'live_integration_key'],
                ['digitalhub_ebanx_global', 'sandbox_integration_key'],
                ['digitalhub_ebanx_global', 'sandbox'],
                ['digitalhub_ebanx_global/cc', 'max_installments'],
                ['digitalhub_ebanx_global/cc', 'min_installment_value']
            )
            ->willReturnOnConsecutiveCalls(null, self::SANDBOX_INTEGRATION_KEY, 1, 12, 10);

        $fakeEbanxFacade = \Mockery::mock('overload:Ebanx\Benjamin\Facade');
        $fakeEbanxFacade->shouldReceive('addConfig')
            ->withAnyArgs()
            ->andReturn($fakeEbanxFacade);

        $transactionClient = new TransactionCancel($context, $encryptor, $logger, $ebanxHelper, $storeManager, $data);

        $fakeEbanxCancel = \Mockery::mock('overload:Ebanx\Benjamin\Services\CancelPayment');

        $fakeEbanxFacade->shouldReceive('cancelPayment')
            ->once()
            ->andReturn($fakeEbanxCancel);

        $fakeEbanxCancel->shouldReceive('request')
            ->with($expectedRequest['payment_hash'])
            ->once()
            ->andThrow(\Exception::class, 'Error Message')
            ->byDefault();

        $transferObject = $this->getMockBuilder(TransferInterface::class)->getMock();
        $transferObject->expects($this->any())
            ->method('getBody')
            ->willReturn($expectedRequest);

        $this->assertEquals(
            $expectedResponse,
            $transactionClient->placeRequest($transferObject)
        );
    }
}
