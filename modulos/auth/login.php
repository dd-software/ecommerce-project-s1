<?php require_once '../../config/database.php';
if(isLoggedIn()) redirect('/ecommerce/');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $e=trim($_POST['email']);$p=$_POST['password'];$db=getDB();
    $r=$db->query("SELECT id,nombre,password_hash,rol,activo FROM usuarios WHERE email='".$db->real_escape_string($e)."'");
    if($u=$r->fetch_assoc()){if($u['activo']&&password_verify($p,$u['password_hash'])){$_SESSION['user_id']=$u['id'];$_SESSION['user_nombre']=$u['nombre'];$_SESSION['user_rol']=$u['rol'];auditar($u['id'],'login','usuarios');redirect('/ecommerce/');}}
    $error='Email o contraseña incorrectos.';
}
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Login</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container"><div class="row justify-content-center mt-5"><div class="col-md-4"><div class="card p-4 shadow"><h3 class="fw-bold mb-3">Iniciar Sesión</h3><?php if($error):?><div class="alert alert-danger"><?=$error?></div><?php endif;?><form method="post"><div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div><div class="mb-3"><label class="form-label">Contraseña</label><input name="password" type="password" class="form-control" required></div><button class="btn btn-primary w-100">Ingresar</button></form><p class="mt-3 text-center"><a href="registro.php">Crear cuenta</a></p></div></div></div></div></body></html>
