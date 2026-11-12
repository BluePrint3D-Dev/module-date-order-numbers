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

namespace BluePrint3D\DateOrderNumbers\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Mode implements OptionSourceInterface
{
    public const SEQUENTIAL = 'sequential';
    public const RANDOM = 'random';

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::SEQUENTIAL, 'label' => __('Sequential (20261112-001, 20261112-002 ...)')],
            ['value' => self::RANDOM, 'label' => __('Random (20261112-4827, 20261112-0391 ...)')],
        ];
    }
}
