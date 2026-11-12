# Date Order Numbers for Magento 2

Replaces Magento's `000000123` order numbers with readable, date-based ones such as `20261112-001`.
The count restarts every day, in the store's own timezone.

## Features
- **Sequential** (`20261112-001`, `-002` ...) or **random** (`20261112-4827`) numbers. Random hides how many orders you take each day and never repeats within a day.
- **Orders, invoices, shipments and credit memos** can share one number (order `20261112-003` gets invoice `20261112-003`; a second partial invoice gets `20261112-003-2`), or each can have its own daily counter.
- Optional **prefix per document type** (e.g. `FW-`, `INV-`), date format, separator and number length.
- Configured **per store view**, so one store can use it while another keeps Magento's defaults.
- Safe under load: numbers are issued with a single atomic database statement.
- A basket that reserved yesterday's number and pays after midnight is re-numbered with the day it was placed.

## Install
```bash
composer require blueprint3d/module-date-order-numbers
bin/magento module:enable BluePrint3D_DateOrderNumbers
bin/magento setup:upgrade
```
Then enable it under **Stores > Configuration > Sales > Date Order Numbers** (it ships switched off).

## Good to know
- Only new orders and documents are affected. Existing numbers never change.
- Invoices, shipments and credit memos of orders placed before you enabled the module keep Magento's normal numbering.
- Numbers are limited to 32 characters by Magento (`increment_id`).
- Payment, shipping and marketplace integrations that read the order number get the new value automatically.

## Support
support@blueprint3d.dev
