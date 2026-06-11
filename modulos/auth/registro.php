<?php require_once '../../config/database.php';
if(isLoggedIn()) redirect('/ecommerce/');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $n=trim($_POST['nombre']);$a=trim($_POST['apellido']);$e=trim($_POST['email']);$p=$_POST['password'];
    if($n&&$a&&$e&&$p){
        $db=getDB();$ch=$db->prepare("SELECT id FROM usuarios WHERE email=?");$ch->bind_param('s',$e);$ch->execute();
        if($ch->get_result()->num_rows>0) $error='Email ya registrado.';
        else{$h=password_hash($p,PASSWORD_DEFAULT);$db->query("INSERT INTO usuarios(nombre,apellido,email,password_hash) VALUES('".$db->real_escape_string($n)."','".$db->real_escape_string($a)."','$e','$h')");
            auditar($db->insert_id,'registro','usuarios');$_SESSION['user_id']=$db->insert_id;$_SESSION['user_nombre']=$n;$_SESSION['user_rol']='cliente';redirect('/ecommerce/');}
    }else $error='Todos los campos obligatorios.';
}
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Registro</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container"><div class="row justify-content-center mt-5"><div class="col-md-5"><div class="card p-4 shadow"><h3 class="fw-bold mb-3">Crear Cuenta</h3><?php if($error):?><div class="alert alert-danger"><?=$error?></div><?php endif;?>
<form method="post"><div class="row"><div class="col-md-6 mb-3"><label class="form-label">Nombre</label><input name="nombre" class="form-control" required></div><div class="col-md-6 mb-3"><label class="form-label">Apellido</label><input name="apellido" class="form-control" required></div></div><div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div><div class="mb-3"><label class="form-label">Contraseña</label><input name="password" type="password" class="form-control" required></div><button class="btn btn-primary w-100">Registrarse</button></form><p class="mt-3 text-center"><a href="login.php">¿Ya tienes cuenta?</a></p></div></div></div></div></body></html>
