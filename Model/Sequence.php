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

namespace BluePrint3D\DateOrderNumbers\Model;

use Magento\Framework\DB\Sequence\SequenceInterface;

/**
 * Drop-in replacement for Magento\SalesSequence\Model\Sequence.
 */
class Sequence implements SequenceInterface
{
    /**
     * @var NumberGenerator
     */
    private $generator;

    /**
     * @var string
     */
    private $entityType;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var string|null
     */
    private $current;

    /**
     * @param NumberGenerator $generator
     * @param string $entityType
     * @param int $storeId
     */
    public function __construct(NumberGenerator $generator, string $entityType, int $storeId)
    {
        $this->generator = $generator;
        $this->entityType = $entityType;
        $this->storeId = $storeId;
    }

    /**
     * @inheritDoc
     */
    public function getNextValue()
    {
        $this->current = $this->generator->generate($this->entityType, $this->storeId);
        return $this->current;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentValue()
    {
        return $this->current;
    }
}
