<?php require_once '../../config/database.php'; ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Carrito</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"></head><body><?php include '../../includes/navbar.php'; ?><div class="container py-4"><h2>Carrito</h2><div id="cc"></div></div>
<script>let c=JSON.parse(localStorage.getItem('cart')||'[]');let h='',t=0;
if(!c.length)h='<div class="text-center py-5"><i class="bi bi-cart-x display-1 text-muted"></i><h4>Carrito vacío</h4><a href="/ecommerce/modulos/catalogo/index.php" class="btn btn-primary mt-2">Ir al catálogo</a></div>';
else{h='<div class="table-responsive"><table class="table bg-white rounded shadow-sm"><thead><tr><th>Producto</th><th>Precio</th><th>Cant</th><th>Subtotal</th><th></th></tr></thead><tbody>';
c.forEach((it,i)=>{let s=it.p*it.qty;t+=s;h+=`<tr><td>${it.n}</td><td>$${it.p.toLocaleString()}</td><td><input type="number" value="${it.qty}" min="1" class="form-control qi" style="width:80px" data-i="${i}"></td><td>$${s.toLocaleString()}</td><td><button class="btn btn-sm btn-outline-danger ri" data-i="${i}">×</button></td></tr>`;});
h+=`</tbody><tfoot><tr class="table-dark"><td colspan="3" class="fw-bold">Total</td><td class="fw-bold fs-5">$${t.toLocaleString()}</td></tr></tfoot></table></div>`;
h+=<?=isLoggedIn()?'"<a href=\'/ecommerce/modulos/checkout/index.php\' class=\'btn btn-success btn-lg\'>Proceder al Checkout</a>"':'\"<div class=\'alert alert-info\'>Debes <a href=\'/ecommerce/modulos/auth/login.php\'>iniciar sesión</a> para comprar.</div>"'?>;
h+=' <button class="btn btn-outline-warning btn-lg" onclick="clearCart();location.reload()">Vaciar</button>';}
document.getElementById('cc').innerHTML=h;
document.querySelectorAll('.qi').forEach(inp=>inp.addEventListener('change',function(){let i=this.dataset.i;c[i].qty=parseInt(this.value)||1;localStorage.setItem('cart',JSON.stringify(c));location.reload();}));
document.querySelectorAll('.ri').forEach(b=>b.addEventListener('click',function(){c.splice(this.dataset.i,1);localStorage.setItem('cart',JSON.stringify(c));location.reload();}));</script>
<?php include '../../includes/footer.php'; ?></body></html>
