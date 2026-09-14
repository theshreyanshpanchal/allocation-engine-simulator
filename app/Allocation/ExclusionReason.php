<?php

namespace App\Allocation;

/**
 * Canonical reason strings for why a store did not win an allocation.
 * Shared by the engine, the audit records and the explanation service so
 * the wording never drifts.
 */
final class ExclusionReason
{
    public const INACTIVE = 'Store is inactive';

    public const OUT_OF_STOCK = 'Out of stock';

    public const NOT_SERVICEABLE = 'Outside service area';

    public const PRICE_OUTSIDE_TOLERANCE = 'Price outside tolerance';

    public const NOT_SELECTED = 'Eligible, but another store was selected';
}
