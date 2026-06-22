<?php
declare(strict_types=1);

/**
 * Sidebar del panel de administración
 */
?>
<nav id="sidebar" class="sidebar bg-dark text-white shadow">
    <div class="sidebar-header p-4 d-flex align-items-center border-bottom border-secondary border-opacity-25">
        <i class="bi bi-shop text-primary fs-3 me-2"></i>
        <h4 class="mb-0 fw-bold">Admin<span class="text-primary">Panel</span></h4>
    </div>
    
    <div class="sidebar-menu p-3">
        <ul class="nav flex-column gap-2">
            <li class="nav-item">
                <a class="nav-link text-white opacity-75 <?= isActivePage('dashboard') ?>" href="<?= getBackendPath() ?>/views/dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Inicio
                </a>
            </li>
            
            <li class="nav-item mt-3 mb-1">
                <small class="text-uppercase text-muted fw-bold px-3">Gestión</small>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-white opacity-75 <?= isActivePage('productos') ?>" href="<?= getBackendPath() ?>/views/productos.php">
                    <i class="bi bi-box-seam me-2"></i> Productos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white opacity-75 <?= isActivePage('categorias') ?>" href="<?= getBackendPath() ?>/views/categorias.php">
                    <i class="bi bi-tags me-2"></i> Categorías
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white opacity-75 <?= isActivePage('pedidos') ?>" href="<?= getBackendPath() ?>/views/pedidos.php">
                    <i class="bi bi-cart-check me-2"></i> Pedidos
                </a>
            </li>
            
            <li class="nav-item mt-3 mb-1">
                <small class="text-uppercase text-muted fw-bold px-3">Administración</small>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-white opacity-75 <?= isActivePage('usuarios') ?>" href="<?= getBackendPath() ?>/views/usuarios.php">
                    <i class="bi bi-people me-2"></i> Usuarios
                </a>
            </li>
        </ul>
    </div>
    
    <div class="sidebar-footer mt-auto p-4 border-top border-secondary border-opacity-25">
        <div class="d-flex align-items-center mb-3 px-2">
            <div class="bg-primary bg-opacity-25 text-primary rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="bi bi-person-fill fs-5"></i>
            </div>
            <div class="overflow-hidden">
                <h6 class="mb-0 text-truncate"><?= e($adminUser['nombre'] ?? 'Admin') ?></h6>
                <small class="text-muted text-truncate d-block"><?= e($adminUser['email'] ?? '') ?></small>
            </div>
        </div>
        <a href="<?= getBackendPath() ?>/auth/logout.php" class="btn btn-outline-danger w-100 btn-sm">
            <i class="bi bi-box-arrow-left me-1"></i> Cerrar Sesión
        </a>
        
        <div class="mt-3 text-center">
            <a href="<?= getBasePath() ?>/" class="text-muted text-decoration-none small" target="_blank">
                <i class="bi bi-arrow-up-right-square me-1"></i> Ver Tienda
            </a>
        </div>
    </div>
</nav>
