<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');
function respond($data, int $status=200): never { http_response_code($status); echo json_encode($data); exit; }
function body(): array { $v=json_decode(file_get_contents('php://input'), true); return is_array($v)?$v:$_POST; }
function need(array $roles): void { requireRole($roles); }
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$user = currentUser();
try {
 if ($action === 'public_order' && $method === 'POST') {
   $d=body(); $name=trim($d['customer']??''); $phone=trim($d['phone']??''); $pid=(int)($d['product_id']??0); $qty=(int)($d['quantity']??0); $notes=trim($d['notes']??'');
   if (!$name || !$phone || $pid<1 || $qty<1) respond(['error'=>'Please complete all required order details.'],422);
   $p=$pdo->prepare("SELECT id,name FROM products WHERE id=? AND status='Active'"); $p->execute([$pid]); $product=$p->fetch();
   if (!$product) respond(['error'=>'Selected product is unavailable.'],404);
   $pdo->prepare("INSERT INTO orders(order_no,customer_name,phone,product_id,quantity,notes,status,source) VALUES(?,?,?,?,?,?, 'Pending','Online')")
       ->execute(['ORD-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(2))),$name,$phone,$pid,$qty,$notes]);
   respond(['ok'=>true,'message'=>'Your order has been received. The shop will contact you.']);
 }
 need(['admin','manager','staff']);
 if ($action==='data' && $method==='GET') {
   $today=$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE DATE(created_at)=CURDATE()")->fetchColumn();
   $products=$pdo->query("SELECT id,name,selling_price AS price,quantity AS qty FROM products WHERE status='Active' ORDER BY name")->fetchAll();
   $sales=$pdo->query("SELECT s.id,s.receipt_no AS receipt,s.created_at AS time,p.name AS product,s.quantity AS qty,s.total_amount AS total,s.payment_method AS payment,u.full_name AS staff FROM sales s JOIN products p ON p.id=s.product_id JOIN users u ON u.id=s.user_id ORDER BY s.id DESC LIMIT 100")->fetchAll();
   $orders=$pdo->query("SELECT o.id,o.order_no AS orderNo,o.customer_name AS customer,o.phone,p.name AS product,o.quantity AS qty,o.notes,o.status FROM orders o JOIN products p ON p.id=o.product_id ORDER BY o.id DESC LIMIT 100")->fetchAll();
   $purchases=$pdo->query("SELECT pu.created_at,p.name AS product,pu.quantity AS qty,pu.supplier_name AS supplier,pu.unit_cost AS cost,pu.total_cost AS total FROM purchases pu JOIN products p ON p.id=pu.product_id ORDER BY pu.id DESC LIMIT 100")->fetchAll();
   $alerts=$pdo->query("SELECT id,title,message,created_at AS time,status FROM sms_notifications ORDER BY id DESC LIMIT 100")->fetchAll();
   respond(['products'=>$products,'sales'=>$sales,'orders'=>$orders,'purchases'=>$purchases,'alerts'=>$alerts,'today_sales'=>(float)$today,'role'=>$user['role']]);
 }
 if ($action==='sale' && $method==='POST') {
   need(['admin','manager','staff']); $d=body(); $pid=(int)($d['product_id']??0); $qty=(int)($d['quantity']??0); $payment=trim($d['payment']??'Cash');
   if($qty<1) respond(['error'=>'Enter a valid quantity.'],422);
   $pdo->beginTransaction();
   $q=$pdo->prepare("SELECT id,name,selling_price,quantity FROM products WHERE id=? AND status='Active' FOR UPDATE");$q->execute([$pid]);$p=$q->fetch();
   if(!$p || $p['quantity']<$qty){$pdo->rollBack();respond(['error'=>'Product not found or insufficient stock.'],422);}
   $total=(float)$p['selling_price']*$qty;$receipt='GM-'.date('ymdHis').'-'.random_int(10,99);
   $pdo->prepare("UPDATE products SET quantity=quantity-? WHERE id=?")->execute([$qty,$pid]);
   $pdo->prepare("INSERT INTO sales(receipt_no,product_id,user_id,quantity,unit_price,total_amount,payment_method) VALUES(?,?,?,?,?,?,?)")->execute([$receipt,$pid,$user['id'],$qty,$p['selling_price'],$total,$payment]);
   $pdo->commit();
   createAlerts($pdo,'Sale recorded',"$receipt: $qty x {$p['name']}; total KSh ".number_format($total,2));
   respond(['ok'=>true,'message'=>'Sale saved and stock updated.']);
 }
 if ($action==='product' && $method==='POST') {
   need(['admin','manager']);$d=body();$name=trim($d['name']??'');$price=(float)($d['price']??0);$qty=(int)($d['quantity']??0);
   if(!$name||$price<=0||$qty<0)respond(['error'=>'Enter valid product details.'],422);
   $pdo->prepare("INSERT INTO products(name,selling_price,quantity) VALUES(?,?,?)")->execute([$name,$price,$qty]);
   respond(['ok'=>true,'message'=>'Product added.']);
 }
 if ($action==='purchase' && $method==='POST') {
   need(['admin','manager']);$d=body();$pid=(int)($d['product_id']??0);$qty=(int)($d['quantity']??0);$supplier=trim($d['supplier']??'');$cost=(float)($d['unit_cost']??0);
   if($pid<1||$qty<1||!$supplier||$cost<0)respond(['error'=>'Enter valid purchase details.'],422);
   $pdo->beginTransaction();$pdo->prepare("INSERT INTO purchases(product_id,quantity,supplier_name,unit_cost,total_cost,user_id) VALUES(?,?,?,?,?,?)")->execute([$pid,$qty,$supplier,$cost,$qty*$cost,$user['id']]);
   $pdo->prepare("UPDATE products SET quantity=quantity+? WHERE id=?")->execute([$qty,$pid]);$pdo->commit();
   respond(['ok'=>true,'message'=>'Purchase saved and stock increased.']);
 }
 if ($action==='adjust_stock' && $method==='POST') {
   need(['admin','manager']);$d=body();$pid=(int)($d['product_id']??0);$delta=(int)($d['delta']??0);
   $q=$pdo->prepare("UPDATE products SET quantity=quantity+? WHERE id=? AND quantity+?>=0");$q->execute([$delta,$pid,$delta]);
   if(!$q->rowCount())respond(['error'=>'Stock adjustment failed. Check the product and quantity.'],422);
   respond(['ok'=>true,'message'=>'Stock updated.']);
 }
 if ($action==='order_status' && $method==='POST') {
   need(['admin','manager','staff']);$d=body();$id=(int)($d['id']??0);$status=$d['status']??'';
   if(!in_array($status,['Pending','Processing','Completed','Cancelled'],true))respond(['error'=>'Invalid order status.'],422);
   $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status,$id]);respond(['ok'=>true,'message'=>'Order status updated.']);
 }
 if ($action==='alerts' && $method==='GET') { need(['admin','manager']); respond($pdo->query("SELECT id,title,message,created_at AS time,status FROM sms_notifications ORDER BY id DESC LIMIT 100")->fetchAll()); }
 respond(['error'=>'Unknown action.'],404);
} catch(Throwable $e) {
 if($pdo->inTransaction())$pdo->rollBack();
 error_log($e->getMessage()); respond(['error'=>'The request could not be completed. Check the server error log.'],500);
}
function createAlerts(PDO $pdo,string $title,string $message): void {
 $recipients=$pdo->query("SELECT phone FROM users WHERE role IN ('admin','manager') AND status='Active' AND phone IS NOT NULL AND phone<>''")->fetchAll(PDO::FETCH_COLUMN);
 foreach($recipients as $phone) {
   // This records the SMS request. Connect an SMS provider before claiming that a text was delivered.
   $pdo->prepare("INSERT INTO sms_notifications(title,message,recipient_phone,status) VALUES(?,?,?,'Queued')")->execute([$title,$message,$phone]);
 }
}
