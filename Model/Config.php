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

use BluePrint3D\DateOrderNumbers\Model\Config\Source\Mode;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Reads and sanitises the module settings (store scope).
 */
class Config
{
    private const PATH = 'blueprint3d_order_numbers/';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Is date-based numbering switched on for this store?
     *
     * @param int $storeId
     * @return bool
     */
    public function isEnabled(int $storeId): bool
    {
        return (bool)$this->value('general/enabled', $storeId);
    }

    /**
     * Sequential or random.
     *
     * @param int $storeId
     * @return string
     */
    public function getMode(int $storeId): string
    {
        return $this->value('general/mode', $storeId) === Mode::RANDOM ? Mode::RANDOM : Mode::SEQUENTIAL;
    }

    /**
     * PHP date() format, restricted to date letters and simple separators.
     *
     * @param int $storeId
     * @return string
     */
    public function getDateFormat(int $storeId): string
    {
        $format = preg_replace('/[^YymndjMF_.\/-]/', '', (string)$this->value('general/date_format', $storeId));
        return $format === '' || $format === null ? 'Ymd' : $format;
    }

    /**
     * Text between the date and the number. Never empty.
     *
     * @param int $storeId
     * @return string
     */
    public function getSeparator(int $storeId): string
    {
        $separator = $this->clean((string)$this->value('general/separator', $storeId));
        return $separator === '' ? '-' : $separator;
    }

    /**
     * Digits to zero-pad the daily counter to.
     *
     * @param int $storeId
     * @return int
     */
    public function getCounterPadding(int $storeId): int
    {
        return max(1, min(8, (int)$this->value('general/counter_padding', $storeId)));
    }

    /**
     * Digits in the random part.
     *
     * @param int $storeId
     * @return int
     */
    public function getRandomLength(int $storeId): int
    {
        return max(3, min(8, (int)$this->value('general/random_length', $storeId)));
    }

    /**
     * Should invoices/shipments/credit memos reuse the order number?
     *
     * @param int $storeId
     * @return bool
     */
    public function isSharingNumbers(int $storeId): bool
    {
        return (bool)$this->value('general/share_numbers', $storeId);
    }

    /**
     * Prefix for an entity type (order, invoice, shipment, creditmemo).
     *
     * @param string $entityType
     * @param int $storeId
     * @return string
     */
    public function getPrefix(string $entityType, int $storeId): string
    {
        return $this->clean((string)$this->value('prefixes/' . $entityType, $storeId));
    }

    /**
     * Read one setting in store scope.
     *
     * @param string $path
     * @param int $storeId
     * @return mixed
     */
    private function value(string $path, int $storeId)
    {
        return $this->scopeConfig->getValue(self::PATH . $path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Keep only characters that are safe in a document number.
     *
     * @param string $text
     * @return string
     */
    private function clean(string $text): string
    {
        return (string)preg_replace('/[^A-Za-z0-9_.\-]/', '', $text);
    }
}
