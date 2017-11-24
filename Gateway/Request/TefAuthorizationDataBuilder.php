<?php
namespace Ebanx\Payments\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;

class TefAuthorizationDataBuilder implements BuilderInterface
{
    /**
     * @param array $buildSubject
     * @return mixed
     */
    public function build(array $buildSubject)
    {
        return [
            'type' => 'tef',
            'bankCode' => 'bradesco',
        ];
    }
}
