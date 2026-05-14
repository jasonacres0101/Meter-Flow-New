<?php

namespace App\Services;

use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    public function addCompanyStock(StockProduct $product, int $quantity, ?User $user = null, ?string $notes = null): StockBalance
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        return DB::transaction(function () use ($product, $quantity, $user, $notes) {
            $balance = $this->balance($product, null);
            $balance->increment('quantity', $quantity);

            StockMovement::create([
                'company_id' => $product->company_id,
                'stock_product_id' => $product->id,
                'movement_type' => StockMovement::TYPE_ADD,
                'quantity' => $quantity,
                'created_by_user_id' => $user?->id,
                'notes' => $notes,
            ]);

            return $balance->refresh();
        });
    }

    public function transferCompanyStockToSite(StockProduct $product, int $siteId, int $quantity, ?User $user = null, ?string $notes = null): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        DB::transaction(function () use ($product, $siteId, $quantity, $user, $notes) {
            $companyBalance = $this->balance($product, null);

            if ($companyBalance->quantity < $quantity) {
                throw new InvalidArgumentException('Not enough company stock available to move.');
            }

            $siteBalance = $this->balance($product, $siteId);
            $companyBalance->decrement('quantity', $quantity);
            $siteBalance->increment('quantity', $quantity);

            StockMovement::create([
                'company_id' => $product->company_id,
                'stock_product_id' => $product->id,
                'from_site_id' => null,
                'to_site_id' => $siteId,
                'movement_type' => StockMovement::TYPE_TRANSFER_TO_SITE,
                'quantity' => $quantity,
                'created_by_user_id' => $user?->id,
                'notes' => $notes,
            ]);
        });
    }

    private function balance(StockProduct $product, ?int $siteId): StockBalance
    {
        return StockBalance::firstOrCreate(
            [
                'stock_product_id' => $product->id,
                'site_id' => $siteId,
            ],
            [
                'company_id' => $product->company_id,
                'quantity' => 0,
            ],
        );
    }
}
