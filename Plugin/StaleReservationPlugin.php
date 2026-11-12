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
use Magento\Quote\Model\Quote;

/**
 * A basket reserves its order number early. If the customer pays after midnight
 * (or the settings changed), drop the old reservation so the order is numbered
 * with the day it was actually placed.
 */
class StaleReservationPlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var NumberGenerator
     */
    private $generator;

    /**
     * @param Config $config
     * @param NumberGenerator $generator
     */
    public function __construct(Config $config, NumberGenerator $generator)
    {
        $this->config = $config;
        $this->generator = $generator;
    }

    /**
     * Drop a reserved order number that no longer matches today's date.
     *
     * @param Quote $subject
     * @return null
     */
    public function beforeReserveOrderId(Quote $subject)
    {
        $storeId = (int)$subject->getStoreId();
        $reserved = (string)$subject->getReservedOrderId();
        if ($reserved !== '' && $this->config->isEnabled($storeId)
            && strpos($reserved, $this->generator->head('order', $storeId)) !== 0
        ) {
            $subject->setReservedOrderId(null);
        }
        return null;
    }
}
