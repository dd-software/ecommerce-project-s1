<?php require_once '../../config/database.php';
if(!isAdmin()) redirect('/ecommerce/modulos/auth/login.php');
$db=getDB();
$tp=$db->query("SELECT COUNT(*) c FROM productos")->fetch_assoc()['c'];
$tpe=$db->query("SELECT COUNT(*) c FROM pedidos")->fetch_assoc()['c'];
$tu=$db->query("SELECT COUNT(*) c FROM usuarios")->fetch_assoc()['c'];
$tv=$db->query("SELECT COALESCE(SUM(total),0) c FROM pedidos WHERE estado!='cancelado'")->fetch_assoc()['c'];
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Admin</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><style>.s{background:#1a1a2e;min-height:100vh;color:white;position:fixed;width:220px}.s a{color:rgba(255,255,255,.7);padding:10px 15px;display:block;text-decoration:none}.s a:hover,.s a.ac{background:rgba(255,255,255,.1);color:white}.m{margin-left:220px;padding:20px}</style></head><body>
<div class="s p-3"><h5 class="text-white mb-3">Admin</h5><a href="dashboard.php" class="ac">📊 Dashboard</a><a href="productos.php">📦 Productos</a><a href="pedidos.php">🧾 Pedidos</a><a href="inventario.php">📋 Inventario</a><a href="/ecommerce/">← Sitio</a></div>
<div class="m"><h2>Dashboard</h2><div class="row g-3 mb-4"><div class="col-md-3"><div class="card p-3 text-center"><small>Productos</small><h3><?=$tp?></h3></div></div><div class="col-md-3"><div class="card p-3 text-center"><small>Pedidos</small><h3><?=$tpe?></h3></div></div><div class="col-md-3"><div class="card p-3 text-center"><small>Usuarios</small><h3><?=$tu?></h3></div></div><div class="col-md-3"><div class="card p-3 text-center"><small>Ventas</small><h3>$<?=number_format($tv,0)?></h3></div></div></div></div></body></html>
