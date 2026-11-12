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
use DateTimeInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Builds the next document number, e.g. FW-20261112-004 or 20261112-4827.
 *
 * Sequential numbers come from one atomic upsert per store/type/day, so two
 * simultaneous orders can never receive the same number and the count restarts
 * automatically on a new (store-local) day. Random numbers are claimed with an
 * INSERT IGNORE against a unique key, so they never repeat within a day either.
 */
class NumberGenerator
{
    private const ATTEMPTS_PER_LENGTH = 40;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param Config $config
     * @param TimezoneInterface $timezone
     * @param ResourceConnection $resource
     */
    public function __construct(Config $config, TimezoneInterface $timezone, ResourceConnection $resource)
    {
        $this->config = $config;
        $this->timezone = $timezone;
        $this->resource = $resource;
    }

    /**
     * Issue the next number for an entity type.
     *
     * @param string $entityType order, invoice, shipment or creditmemo
     * @param int $storeId
     * @param DateTimeInterface|null $at Moment to number for (store timezone); defaults to now. Used by tests.
     * @return string
     */
    public function generate(string $entityType, int $storeId, ?DateTimeInterface $at = null): string
    {
        $date = $at ?? $this->timezone->scopeDate($storeId, null, true);
        $dateKey = $date->format('Ymd');

        if ($this->config->getMode($storeId) === Mode::RANDOM) {
            $core = $this->claimRandom($entityType, $storeId, $dateKey);
        } else {
            $core = str_pad(
                (string)$this->nextCounter($entityType, $storeId, $dateKey),
                $this->config->getCounterPadding($storeId),
                '0',
                STR_PAD_LEFT
            );
        }

        return $this->head($entityType, $storeId, $date) . $core;
    }

    /**
     * Everything before the counter, e.g. "FW-20261112-". Used to spot stale reservations.
     *
     * @param string $entityType
     * @param int $storeId
     * @param DateTimeInterface|null $at
     * @return string
     */
    public function head(string $entityType, int $storeId, ?DateTimeInterface $at = null): string
    {
        $date = $at ?? $this->timezone->scopeDate($storeId, null, true);
        return $this->config->getPrefix($entityType, $storeId)
            . $date->format($this->config->getDateFormat($storeId))
            . $this->config->getSeparator($storeId);
    }

    /**
     * Atomically take the next counter value for the day.
     *
     * @param string $entityType
     * @param int $storeId
     * @param string $dateKey
     * @return int
     */
    private function nextCounter(string $entityType, int $storeId, string $dateKey): int
    {
        $connection = $this->resource->getConnection('sales');
        $table = $this->resource->getTableName('blueprint3d_order_number_counter', 'sales');
        $connection->query(
            "INSERT INTO {$table} (store_id, entity_type, number_date, counter) VALUES (?, ?, ?, LAST_INSERT_ID(1))"
            . ' ON DUPLICATE KEY UPDATE counter = LAST_INSERT_ID(counter + 1)',
            [$storeId, $entityType, $dateKey]
        );
        return (int)$connection->fetchOne('SELECT LAST_INSERT_ID()');
    }

    /**
     * Claim an unused random number for the day.
     *
     * @param string $entityType
     * @param int $storeId
     * @param string $dateKey
     * @return string
     */
    private function claimRandom(string $entityType, int $storeId, string $dateKey): string
    {
        $connection = $this->resource->getConnection('sales');
        $table = $this->resource->getTableName('blueprint3d_order_number_used', 'sales');
        $length = $this->config->getRandomLength($storeId);

        while (true) {
            for ($attempt = 0; $attempt < self::ATTEMPTS_PER_LENGTH; $attempt++) {
                $value = str_pad((string)random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
                $statement = $connection->query(
                    "INSERT IGNORE INTO {$table} (store_id, entity_type, number_date, value) VALUES (?, ?, ?, ?)",
                    [$storeId, $entityType, $dateKey, $value]
                );
                if ($statement->rowCount() === 1) {
                    return $value;
                }
            }
            // This length is getting crowded today: widen it rather than loop forever.
            $length = min($length + 1, 15);
        }
    }
}
