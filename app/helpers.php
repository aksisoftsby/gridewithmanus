<?php

/**
 * Global status badge HTML helper (panel + publik).
 */
if (!function_exists('statusBadge')) {
    function statusBadge(string $status): string
    {
        $status = strtoupper((string) $status);
        $map = [
            'SEARCHING_DRIVER' => 'yellow', 'DRIVER_ACCEPTED' => 'blue', 'DRIVER_ARRIVING' => 'blue',
            'DRIVER_ARRIVED' => 'indigo', 'TRIP_STARTED' => 'indigo', 'COMPLETED' => 'emerald',
            'CANCELLED' => 'red', 'FAILED' => 'red', 'PENDING' => 'yellow', 'PAID' => 'emerald',
            'SUCCESS' => 'emerald', 'ACTIVE' => 'emerald', 'INACTIVE' => 'gray', 'PICKED_UP' => 'indigo',
            'DELIVERED' => 'emerald', 'ACCEPTED' => 'blue', 'REJECTED' => 'red', 'REFUND' => 'red',
            'PENDING_PICKUP' => 'yellow', 'PENDING_DELIVERY' => 'blue', 'ON_DELIVERY' => 'indigo',
            'WAITING' => 'yellow', 'PROCESSING' => 'blue', 'OPEN' => 'yellow', 'IN_PROGRESS' => 'blue',
            'RESOLVED' => 'emerald', 'CLOSED' => 'gray', 'DELETED' => 'red',
        ];
        $colors = [
            'emerald' => 'bg-emerald-100 text-emerald-700',
            'blue'    => 'bg-blue-100 text-blue-700',
            'indigo'  => 'bg-indigo-100 text-indigo-700',
            'yellow'  => 'bg-yellow-100 text-yellow-700',
            'red'     => 'bg-red-100 text-red-700',
            'gray'    => 'bg-gray-100 text-gray-600',
        ];
        $cls = $colors[$map[$status] ?? 'gray'] ?? $colors['gray'];
        return '<span class="px-2.5 py-1 text-xs font-semibold rounded-full ' . $cls . '">'
            . ucfirst(strtolower(str_replace('_', ' ', $status))) . '</span>';
    }
}
