<?php require_once '../../config/database.php';
if(!isLoggedIn()) redirect('/ecommerce/modulos/auth/login.php');
$msg=''; $db=getDB();
if($_SERVER['REQUEST_METHOD']==='POST'){
    $direccion=trim($_POST['direccion']); $notas=trim($_POST['notas']??'');
    $cart=json_decode($_POST['cart_data'],true);
    if(!$cart||empty($cart)) $msg='<div class="alert alert-warning">Carrito vacío.</div>';
    else {
        $total=0; foreach($cart as $item) $total+=$item['p']*$item['qty'];
        $db->begin_transaction();
        try {
            $db->query("INSERT INTO pedidos(usuario_id,total,direccion_envio,notas) VALUES({$_SESSION['user_id']},$total,'".$db->real_escape_string($direccion)."','".$db->real_escape_string($notas)."')");
            $pid=$db->insert_id;
            foreach($cart as $item){
                $db->query("INSERT INTO detalle_pedido(pedido_id,producto_id,cantidad,precio_unitario) VALUES($pid,{$item['id']},{$item['qty']},{$item['p']})");
                $db->query("UPDATE productos SET stock=stock-{$item['qty']} WHERE id={$item['id']} AND stock>={$item['qty']}");
            }
            auditar($_SESSION['user_id'],'crear_pedido','pedidos',"Pedido #$pid");
            $db->commit();
            $msg='<div class="alert alert-success"><h4>Pedido #'.$pid.' confirmado</h4><p>Total: $'.number_format($total,0).'</p><a href="/ecommerce/" class="btn btn-primary">Volver</a></div>';
            echo '<script>localStorage.removeItem("cart");</script>';
        }catch(Exception $e){$db->rollback();$msg='<div class="alert alert-danger">Error: '.$e->getMessage().'</div>';}
    }
}
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Checkout</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"></head><body><?php include '../../includes/navbar.php'; ?>
<div class="container py-4"><h2>Checkout</h2><?=$msg?:''?>
<?php if(!$msg||strpos($msg,'confirmado')===false): ?><form method="post"><input type="hidden" name="cart_data" id="cd"><div class="mb-3"><label>Dirección de envío</label><textarea name="direccion" class="form-control" required></textarea></div><div id="cs"></div><button class="btn btn-success btn-lg w-100" id="cb">Confirmar Pedido</button></form><?php endif; ?></div>
<script>let c=JSON.parse(localStorage.getItem('cart')||'[]');let t=c.reduce((s,i)=>s+i.p*i.qty,0);
document.getElementById('cd').value=JSON.stringify(c);document.getElementById('cs').innerHTML='<div class="card p-3 mb-3"><h5>'+c.length+' productos</h5><h3 class="text-primary">$'+t.toLocaleString()+'</h3></div>';
if(!c.length)document.getElementById('cb').disabled=true;</script>
<?php include '../../includes/footer.php'; ?></body></html>
