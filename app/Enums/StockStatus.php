<?php

namespace App\Enums;

enum StockStatus: string
{
    case IN_STOCK = 'in_stock';
    case OUT_OF_STOCK = 'out_of_stock';
    case ON_BACKORDER = 'on_backorder';

    public function label(): string
    {
        return match($this) {
            self::IN_STOCK => 'In Stock',
            self::OUT_OF_STOCK => 'Out of Stock',
            self::ON_BACKORDER => 'On Backorder',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::IN_STOCK => 'green',
            self::OUT_OF_STOCK => 'red',
            self::ON_BACKORDER => 'yellow',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}