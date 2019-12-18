<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Request\SilentOrder;

use Magento\Framework\Config\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class MerchantDataBuilder
 */
class MerchantDataBuilder implements BuilderInterface
{
    const ACCESS_KEY = 'access_key';

    const PROFILE_ID = 'profile_id';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var ScopeInterface
     */
    private $scope;

    /**
     * @param ConfigInterface $config
     * @param ScopeInterface $scope
     */
    public function __construct(ConfigInterface $config, ScopeInterface $scope)
    {
        $this->config = $config;
        $this->scope = $scope;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws LocalizedException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $storeId = $paymentDO->getOrder()->getStoreId();
        $areaPrefix = $this->getAreaPrefix($storeId);

        return [
            self::ACCESS_KEY => $this->config->getValue(
                $areaPrefix . self::ACCESS_KEY,
                $storeId
            ),
            self::PROFILE_ID => $this->config->getValue(
                $areaPrefix . self::PROFILE_ID,
                $storeId
            )
        ];
    }

    /**
     * Returns config key area prefix.
     *
     * @param int $storeId
     * @return string
     */
    private function getAreaPrefix(?int $storeId = null): string
    {
        $area = $this->scope->getCurrentScope();
        $isMultidomain = $this->config->getValue('is_multidomain', $storeId);

        return ($area === 'adminhtml' && $isMultidomain) ? 'admin_' : '';
    }
}
