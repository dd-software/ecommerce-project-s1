<?php require_once '../../config/database.php';
if(!isLoggedIn()) redirect('/ecommerce/modulos/auth/login.php');
$db=getDB();
if($_SERVER['REQUEST_METHOD']==='POST'){
    $total=floatval($_POST['total']);$uid=$_SESSION['user_id'];
    $cart=json_decode($_POST['cart_data']??'[]',true);
    if($cart){
        $db->begin_transaction();
        try{
            $db->query("INSERT INTO pedidos(usuario_id,total,estado) VALUES($uid,$total,'pagado')");
            $pid=$db->insert_id;
            foreach($cart as $item){
                $db->query("INSERT INTO detalle_pedido(pedido_id,producto_id,cantidad,precio_unitario) VALUES($pid,{$item['id']},{$item['qty']},{$item['p']})");
                $db->query("UPDATE productos SET stock=stock-{$item['qty']} WHERE id={$item['id']} AND stock>={$item['qty']}");
                $db->query("INSERT INTO movimientos_inventario(producto_id,tipo,cantidad,motivo,pedido_id) VALUES({$item['id']},'salida',{$item['qty']},'Venta #$pid',$pid)");
            }
            auditar($uid,'crear_pedido','pedidos',"Pedido #$pid por $$total");
            $db->commit();
        }catch(Exception $e){$db->rollback();}
    }
}
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>¡Pago Exitoso!</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light">
<div class="container mt-5"><div class="row justify-content-center"><div class="col-md-6"><div class="card p-4 text-center">
<h2>✅ ¡Pago Confirmado!</h2><p class="lead">Pedido #<?=$pid??'?'?> registrado</p><p>Total: $<?=number_format($total??0,0)?></p>
<a href="/ecommerce/" class="btn btn-primary">Volver al inicio</a></div></div></div></div>
<script>localStorage.removeItem('cart');</script></body></html>
