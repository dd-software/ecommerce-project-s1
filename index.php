<?php require_once 'config/database.php'; $db=getDB(); ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Ecommerce UCT</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"></head><body>
<?php include 'includes/navbar.php'; ?>
<div class="bg-primary text-white py-5"><div class="container py-3"><h1 class="display-5 fw-bold">Ecommerce UCT</h1><p class="lead">Plataforma de comercio electrónico — Proyecto Integrador</p><a href="modulos/catalogo/index.php" class="btn btn-light btn-lg">Ver Catálogo</a></div></div>
<div class="container py-4"><h3 class="mb-4">Productos Destacados</h3><div class="row g-3">
<?php $r=$db->query("SELECT * FROM productos WHERE destacado=1 AND activo=1 LIMIT 6");while($p=$r->fetch_assoc()): ?>
<div class="col-md-4"><div class="card h-100"><img src="<?=$p['imagen']?>" class="card-img-top" style="height:200px;object-fit:cover" onerror="this.src='assets/img/default.png'"><div class="card-body"><h5><?=htmlspecialchars($p['nombre'])?></h5><p class="text-muted small"><?=substr(htmlspecialchars($p['descripcion']),0,80)?>...</p><h5 class="text-primary">$<?=number_format($p['precio'],0)?></h5><a href="modulos/catalogo/detalle.php?id=<?=$p['id']?>" class="btn btn-outline-primary">Ver</a></div></div></div>
<?php endwhile; ?></div></div><?php include 'includes/footer.php'; ?></body></html>
