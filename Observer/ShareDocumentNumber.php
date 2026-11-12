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

namespace BluePrint3D\DateOrderNumbers\Observer;

use BluePrint3D\DateOrderNumbers\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Shipment;

/**
 * Gives a new invoice / shipment / credit memo the number of its order.
 * A second document for the same order gets "-2", "-3" ... on the end.
 */
class ShareDocumentNumber implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param Config $config
     * @param ResourceConnection $resource
     */
    public function __construct(Config $config, ResourceConnection $resource)
    {
        $this->config = $config;
        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        $document = $observer->getEvent()->getData('data_object');
        if (!$document || $document->getEntityId() || $document->getIncrementId()) {
            return;
        }
        [$type, $table] = $this->describe($document);
        if ($type === null) {
            return;
        }
        $order = $document->getOrder();
        $storeId = (int)($document->getStoreId() ?: ($order ? $order->getStoreId() : 0));
        if (!$order || !$order->getId() || !$this->config->isEnabled($storeId)
            || !$this->config->isSharingNumbers($storeId)
        ) {
            return;
        }

        $orderNumber = (string)$order->getIncrementId();
        // Legacy orders (plain digits, no separator) keep core numbering for their documents.
        if (strpos($orderNumber, $this->config->getSeparator($storeId)) === false) {
            return;
        }
        $orderPrefix = $this->config->getPrefix('order', $storeId);
        $core = ($orderPrefix !== '' && strpos($orderNumber, $orderPrefix) === 0)
            ? substr($orderNumber, strlen($orderPrefix))
            : $orderNumber;
        $base = $this->config->getPrefix($type, $storeId) . $core;

        $connection = $this->resource->getConnection('sales');
        $tableName = $this->resource->getTableName($table, 'sales');
        $count = (int)$connection->fetchOne(
            "SELECT COUNT(*) FROM {$tableName} WHERE order_id = ?",
            [(int)$order->getId()]
        );

        for ($n = $count + 1; $n < $count + 50; $n++) {
            $candidate = $n > 1 ? $base . '-' . $n : $base;
            $taken = (int)$connection->fetchOne(
                "SELECT COUNT(*) FROM {$tableName} WHERE increment_id = ? AND store_id = ?",
                [$candidate, $storeId]
            );
            if ($taken === 0) {
                $document->setIncrementId($candidate);
                return;
            }
        }
    }

    /**
     * Work out which document type and sales table an object belongs to.
     *
     * @param object $document
     * @return array [entity type, sales table] or [null, null]
     */
    private function describe($document): array
    {
        if ($document instanceof Invoice) {
            return ['invoice', 'sales_invoice'];
        }
        if ($document instanceof Shipment) {
            return ['shipment', 'sales_shipment'];
        }
        if ($document instanceof Creditmemo) {
            return ['creditmemo', 'sales_creditmemo'];
        }
        return [null, null];
    }
}
