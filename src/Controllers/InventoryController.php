<?php
declare(strict_types=1);

namespace Src\Controllers;

use Src\Core\Response;
use Src\Core\AuthMiddleware;
use Src\Models\Inventory;

class InventoryController {
    private Inventory $inventoryModel;

    public function __construct() {
        $this->inventoryModel = new Inventory();
    }

    /**
     * GET /api/inventory/alerts
     * Restringido a rol 'admin'
     */
    public function alerts(): void {
        AuthMiddleware::authorize(['admin']);
        
        $alerts = $this->inventoryModel->getAlerts();
        Response::json(['data' => $alerts]);
    }
}
