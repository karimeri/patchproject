<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Request\SilentOrder;

use Magento\Cybersource\Gateway\Helper\SilentOrderHelper;
use Magento\Framework\Config\ScopeInterface;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Framework\ObjectManager\TMap;
use Magento\Framework\ObjectManager\TMapFactory;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class SignCompositeDecorator
 */
class SignCompositeDecorator implements BuilderInterface
{
    const SIGNED_FIELD_NAMES = 'signed_field_names';

    const UNSIGNED_FIELD_NAMES = 'unsigned_field_names';

    const SIGNED_DATE_TIME = 'signed_date_time';

    const SIGNATURE = 'signature';

    const SIGNED_DATE_TIME_FORMAT = "Y-m-d\TH:i:s\Z";

    /**
     * Unsigned fields
     *
     * @var array
     */
    static private $unsignedFields = [
        CcDataBuilder::CARD_TYPE,
        CcDataBuilder::CARD_NUMBER,
        CcDataBuilder::CARD_EXPIRY_DATE,
        CcDataBuilder::CARD_CVN
    ];

    /**
     * @var \Magento\Framework\Intl\DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var BuilderInterface[] | TMap
     */
    private $builders;

    /**
     * @var ScopeInterface
     */
    private $scope;

    /**
     * @param \Magento\Framework\Intl\DateTimeFactory $dateTimeFactory
     * @param ConfigInterface $config
     * @param array $builders
     * @param TMapFactory $tmapFactory
     * @param ScopeInterface $scope
     */
    public function __construct(
        DateTimeFactory $dateTimeFactory,
        ConfigInterface $config,
        array $builders,
        TMapFactory $tmapFactory,
        ScopeInterface $scope
    ) {
        $this->dateTimeFactory = $dateTimeFactory;
        $this->config = $config;

        $this->builders = $tmapFactory->create(
            [
                'array' => $builders,
                'type' => \Magento\Payment\Gateway\Request\BuilderInterface::class
            ]
        );
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
        $signedFields = [];
        $unsignedFields = [];

        $result = [];
        foreach ($this->builders as $builder) {
            // @TODO implement exceptions catching
            $result = array_merge($result, $builder->build($buildSubject));
        }

        foreach ($result as $field => $value) {
            if (in_array($field, self::$unsignedFields)) {
                $unsignedFields[$field] = $value;
                continue;
            }
            $signedFields[$field] = $value;
        }

        $dateTime = $this->dateTimeFactory->create('now', new \DateTimeZone('GMT'));

        $signedFields[self::SIGNED_DATE_TIME] = $dateTime->format(self::SIGNED_DATE_TIME_FORMAT);

        if ($unsignedFields) {
            $signedFields[self::UNSIGNED_FIELD_NAMES] = implode(',', array_keys($unsignedFields));
        }

        $signedFields[self::SIGNED_FIELD_NAMES] = '';
        $signedFields[self::SIGNED_FIELD_NAMES] = implode(',', array_keys($signedFields));

        $result = array_merge($signedFields, $unsignedFields);

        $paymentDO = SubjectReader::readPayment($buildSubject);
        $storeId = $paymentDO->getOrder()->getStoreId();
        $areaPrefix = $this->getAreaPrefix($storeId);

        $result[self::SIGNATURE] = SilentOrderHelper::signFields(
            $signedFields,
            $this->config->getValue($areaPrefix . 'secret_key', $storeId)
        );

        $result['store_id'] = $storeId;

        return $result;
    }

    /**
     * Returns config key area prefix.
     *
     * @param int|null $storeId
     * @return string
     */
    private function getAreaPrefix(int $storeId = null): string
    {
        $area = $this->scope->getCurrentScope();
        $isMultidomain = $this->config->getValue('is_multidomain', $storeId);

        return ($area === 'adminhtml' && $isMultidomain) ? 'admin_' : '';
    }
}
