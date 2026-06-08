<?php
define('DB_HOST','localhost'); define('DB_NAME','ecommerce_db'); define('DB_USER','root'); define('DB_PASS','');
define('SITE_NAME','Ecommerce UCT'); define('SITE_URL','http://localhost/ecommerce');
function getDB(){static $c=null;if($c===null){$c=new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);if($c->connect_error)die("Error DB");$c->set_charset('utf8mb4');}return $c;}
function auditar($uid,$a,$t,$d=''){$db=getDB();$ip=$_SERVER['REMOTE_ADDR']??'unknown';$db->query("INSERT INTO auditoria(usuario_id,accion,tabla_afectada,descripcion,ip) VALUES($uid,'$a','$t','".$db->real_escape_string($d)."','$ip')");}
session_start(); date_default_timezone_set('America/Santiago');
function isLoggedIn(){return isset($_SESSION['user_id']);}
function isAdmin(){return ($_SESSION['user_rol']??'')==='admin';}
function redirect($u){header("Location:$u");exit;}
