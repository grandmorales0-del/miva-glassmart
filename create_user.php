<?php
// Run from a browser while setting up, then DELETE this file.
// Example: create_user.php?key=ChangeThisSetupKey
$setupKey = 'ChangeThisSetupKey';
if (($_GET['key'] ?? '') !== $setupKey) { http_response_code(403); exit('Forbidden'); }
require_once __DIR__.'/db.php';
$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $name=trim($_POST['name']??'');$email=trim($_POST['email']??'');$phone=trim($_POST['phone']??'');$password=$_POST['password']??'';$role=$_POST['role']??'';
 if(!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<10||!in_array($role,['admin','manager','staff','customer'],true))$message='Enter valid details. Password must be at least 10 characters.';
 else{$q=$pdo->prepare("INSERT INTO users(full_name,email,phone,password_hash,role) VALUES(?,?,?,?,?)");$q->execute([$name,$email,$phone,password_hash($password,PASSWORD_DEFAULT),$role]);$message='Account created. Delete create_user.php now.';}
}
?><!doctype html><html><head><meta charset="utf-8"><title>Create GlassMart account</title><link rel="stylesheet" href="style.css"></head><body class="auth-page"><main class="auth-card"><h1>Create account</h1><?php if($message)echo '<p>'.htmlspecialchars($message).'</p>';?><form method="post" class="auth-form"><label>Full name<input name="name" required></label><label>Email<input type="email" name="email" required></label><label>Phone (for SMS)<input name="phone"></label><label>Password (minimum 10 characters)<input type="password" name="password" minlength="10" required></label><label>Role<select name="role"><option value="admin">Admin</option><option value="manager">Manager</option><option value="staff">Staff</option><option value="customer">Customer</option></select></label><button class="primary">Create account</button></form></main></body></html>
