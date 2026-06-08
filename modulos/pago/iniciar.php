<?php require_once '../../config/database.php';
if(!isLoggedIn()) redirect('/ecommerce/modulos/auth/login.php');
$total = floatval($_GET['total'] ?? 0);
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Pago — Ecommerce UCT</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light">
<div class="container mt-5"><div class="row justify-content-center"><div class="col-md-6"><div class="card p-4 text-center">
<h3>💳 Pasarela de Pago</h3><h2 class="text-primary my-4">$<?=number_format($total,0)?></h2>
<p class="text-muted">Integración con Transbank Webpay Plus</p>
<div class="alert alert-info">Ambiente de pruebas — No se realizarán cobros reales</div>
<form method="post" action="confirmar.php"><input type="hidden" name="total" value="<?=$total?>">
<button class="btn btn-success btn-lg w-100 py-3">Pagar con Webpay</button></form>
<a href="/ecommerce/modulos/carrito/ver.php" class="btn btn-outline-secondary mt-2">← Volver</a>
</div></div></div></div></body></html>
