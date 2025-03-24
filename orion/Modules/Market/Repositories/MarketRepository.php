<?php

namespace Orion\Modules\Market\Repositories;

use Orion\Modules\Market\Models\Market;

readonly class MarketRepository
{
    // Add repository logic here
    public function getMarketData()
    {
        return Market::with('resource')
        ->orderBy('id', 'asc')
        ->get();
    }

    public function getMarketItem($id)
    {
        return Market::findOrFail($id);
    }

    public function decreaseStock(int $marketItemId, int $quantity)
    {
        $marketItem = $this->getMarketItem($marketItemId);
        $marketItem->stock -= $quantity;
        $marketItem->save();
        
        return $marketItem;
    }
    
    public function increaseStock(int $marketItemId, int $quantity)
    {
        $marketItem = $this->getMarketItem($marketItemId);
        $marketItem->stock += $quantity;
        $marketItem->save();
        
        return $marketItem;
    }
}
