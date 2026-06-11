document.addEventListener('DOMContentLoaded',()=>{updateCartCount();
document.querySelectorAll('.add-to-cart').forEach(b=>b.addEventListener('click',function(){
let c=JSON.parse(localStorage.getItem('cart')||'[]');let id=this.dataset.id,n=this.dataset.name,p=parseFloat(this.dataset.price);
let i=c.find(x=>x.id==id);if(i)i.qty++;else c.push({id,n,p,qty:1});
localStorage.setItem('cart',JSON.stringify(c));updateCartCount();this.textContent='✓';setTimeout(()=>this.textContent='Agregar',1000);}));});
function updateCartCount(){let c=JSON.parse(localStorage.getItem('cart')||'[]');let t=c.reduce((s,i)=>s+i.qty,0);let e=document.getElementById('cart-count');if(e)e.textContent=t;}
function getCart(){return JSON.parse(localStorage.getItem('cart')||'[]');}
function clearCart(){localStorage.removeItem('cart');updateCartCount();}
