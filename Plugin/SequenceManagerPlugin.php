<?php
/**
 * Copyright (c) 2026 BluePrint3D Ltd. All rights reserved.
 *
 * This software is provided free of charge for personal or commercial use.
 * Resale, redistribution, or sublicensing of this source code, modified or
 * unmodified, for direct financial gain is strictly prohibited.
 *
 * @author    BluePrint3D Ltd <support@blueprint3d.dev>
 * @copyright 2026 BluePrint3D Ltd (Company No. 13473806)
 * @license   Custom Proprietary EULA (See LICENSE.txt)
 */
declare(strict_types=1);

namespace BluePrint3D\DateOrderNumbers\Plugin;

use BluePrint3D\DateOrderNumbers\Model\Config;
use BluePrint3D\DateOrderNumbers\Model\NumberGenerator;
use BluePrint3D\DateOrderNumbers\Model\SequenceFactory;
use Magento\SalesSequence\Model\Manager;

/**
 * Hands out our date-based sequence instead of core's for enabled stores.
 */
class SequenceManagerPlugin
{
    public const TYPES = ['order', 'invoice', 'shipment', 'creditmemo'];

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SequenceFactory
     */
    private $sequenceFactory;

    /**
     * @param Config $config
     * @param SequenceFactory $sequenceFactory
     */
    public function __construct(Config $config, SequenceFactory $sequenceFactory)
    {
        $this->config = $config;
        $this->sequenceFactory = $sequenceFactory;
    }

    /**
     * Swap in the date-based sequence where it applies.
     *
     * @param Manager $subject
     * @param callable $proceed
     * @param string $entityType
     * @param int|string $storeId
     * @return mixed
     */
    public function aroundGetSequence(Manager $subject, callable $proceed, $entityType, $storeId)
    {
        $storeId = (int)$storeId;
        if (!in_array($entityType, self::TYPES, true) || !$this->config->isEnabled($storeId)) {
            return $proceed($entityType, $storeId);
        }
        // With sharing on, documents are numbered from their order by ShareDocumentNumber.
        // If that could not (e.g. a legacy order), core numbering is the safe fallback.
        if ($entityType !== 'order' && $this->config->isSharingNumbers($storeId)) {
            return $proceed($entityType, $storeId);
        }
        return $this->sequenceFactory->create([
            'entityType' => $entityType,
            'storeId' => $storeId,
        ]);
    }
}
