<?php
declare(strict_types=1);

/**
 * Header del panel de administración
 */
?>
<header class="admin-header bg-white shadow-sm px-4 py-3 d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <button class="btn btn-light d-md-none me-3" id="sidebarToggle">
            <i class="bi bi-list fs-4"></i>
        </button>
        <h4 class="mb-0 fw-semibold text-dark"><?= $pageTitle ?? 'Dashboard' ?></h4>
    </div>
    
    <div class="d-flex align-items-center gap-3">
        <div class="position-relative">
            <button class="btn btn-light rounded-circle p-2 position-relative">
                <i class="bi bi-bell fs-5 text-muted"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                    <span class="visually-hidden">Alertas</span>
                </span>
            </button>
        </div>
        
        <div class="d-none d-md-block text-end">
            <span class="d-block fw-medium text-dark lh-sm"><?= e($adminUser['nombre'] ?? 'Administrador') ?></span>
            <span class="badge bg-primary bg-opacity-10 text-primary small">Admin</span>
        </div>
    </div>
</header>
