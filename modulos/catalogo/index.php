<?php require_once '../../config/database.php'; $db=getDB();
$cat=$_GET['categoria']??null;$q=$_GET['q']??'';
$sql="SELECT p.*,c.nombre as cn FROM productos p LEFT JOIN categorias c ON p.categoria_id=c.id WHERE p.activo=1";
if($cat)$sql.=" AND p.categoria_id=".intval($cat);
if($q)$sql.=" AND p.nombre LIKE '%".$db->real_escape_string($q)."%'";
$ps=$db->query($sql." ORDER BY p.created_at DESC");
$cats=$db->query("SELECT * FROM categorias ORDER BY nombre");
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Catálogo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"></head><body>
<?php include '../../includes/navbar.php'; ?><div class="container py-4"><h2>Catálogo</h2>
<form class="row mb-4"><div class="col-md-8"><input name="q" class="form-control" placeholder="Buscar..." value="<?=htmlspecialchars($q)?>"></div><div class="col-md-4"><select name="categoria" class="form-select" onchange="this.form.submit()"><option value="">Todas</option>
<?php while($c=$cats->fetch_assoc()):?><option value="<?=$c['id']?>" <?=$cat==$c['id']?'selected':''?>><?=htmlspecialchars($c['nombre'])?></option><?php endwhile;?></select></div></form>
<div class="row g-3"><?php while($p=$ps->fetch_assoc()):?><div class="col-md-3"><div class="card h-100">
<img src="/ecommerce/<?=$p['imagen']?>" class="card-img-top" style="height:180px;object-fit:cover" onerror="this.src='/ecommerce/assets/img/default.png'">
<div class="card-body"><span class="badge bg-secondary"><?=htmlspecialchars($p['cn']??'Sin categ')?></span><h5><?=htmlspecialchars($p['nombre'])?></h5><h5 class="text-primary">$<?=number_format($p['precio'],0)?></h5><small>Stock: <?=$p['stock']?></small>
<div class="mt-2"><a href="detalle.php?id=<?=$p['id']?>" class="btn btn-outline-primary btn-sm">Ver</a>
<?php if($p['stock']>0):?><button class="btn btn-primary btn-sm add-to-cart" data-id="<?=$p['id']?>" data-name="<?=htmlspecialchars($p['nombre'])?>" data-price="<?=$p['precio']?>">Agregar</button><?php endif;?></div></div></div></div><?php endwhile;?></div></div>
<?php include '../../includes/footer.php'; ?></body></html>
