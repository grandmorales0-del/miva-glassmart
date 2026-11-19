<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GlassMart | Sales & Stock Monitor</title><link rel="stylesheet" href="style.css">
</head>
<body data-role="<?= htmlspecialchars($user['role']) ?>">
  <aside class="sidebar">
    <div class="brand"><span class="brand-mark">G</span><div><strong>GLASSMART</strong><small>Sales & Stock Monitor</small></div></div>
    <div class="nav-label">WORKSPACE</div>
    <button class="nav active" data-view="dashboard"><span>Dashboard</span></button>
    <button class="nav" data-view="sales" data-roles="admin,manager,staff">↗ <span>Record a Sale</span></button>
    <button class="nav" data-view="inventory" data-roles="admin,manager,staff"><span>Inventory</span></button>
    <button class="nav" data-view="purchases" data-roles="admin,manager"><span>Purchases</span></button>
    <button class="nav" data-view="orders"><span>Remote Orders</span></button>
    <button class="nav" data-view="alerts" data-roles="admin,manager"><span>SMS Alerts</span></button>
    <div class="sidebar-bottom"><div class="online-dot"></div><div><strong>Signed in</strong><small><?= htmlspecialchars(ucfirst($user['role'])) ?> account</small></div></div>
  </aside>
  <main class="main">
    <header class="topbar"><div><p class="eyebrow">GLASSMART / BUSINESS SYSTEM</p><h1 id="page-title">Dashboard</h1></div>
      <div class="user-chip"><div class="avatar"><?= htmlspecialchars(strtoupper(substr($user['full_name'],0,1))) ?></div><div><strong><?= htmlspecialchars($user['full_name']) ?></strong><small><?= htmlspecialchars(ucfirst($user['role'])) ?> · <a href="logout.php">Sign out</a></small></div></div>
    </header>
    <section class="view active-view" id="dashboard">
      <div class="welcome"><div><p class="eyebrow light">BUSINESS OVERVIEW</p><h2>Good day, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?> 👋</h2><p>Here’s what’s happening at your shop today.</p></div><button class="primary" data-go="sales" data-roles="admin,manager,staff">＋ Record sale</button></div>
      <div class="stats">
        <article class="stat-card"><div class="stat-head"><span>Today's sales</span><span class="stat-icon green"></span></div><strong id="today-sales">KSh 0</strong><small class="muted">From shared records</small></article>
        <article class="stat-card"><div class="stat-head"><span>Items in stock</span><span class="stat-icon blue"></span></div><strong id="stock-count">0</strong><small class="muted">Across all products</small></article>
        <article class="stat-card"><div class="stat-head"><span>Pending orders</span><span class="stat-icon amber"></span></div><strong id="pending-count">0</strong><small class="muted">Awaiting staff action</small></article>
        <article class="stat-card"><div class="stat-head"><span>Low-stock items</span><span class="stat-icon red"></span></div><strong id="low-count">0</strong><small class="muted">At or below 5 units</small></article>
      </div>
      <div class="content-grid"><article class="panel"><div class="panel-title"><div><h3>Recent sales</h3><p>Latest recorded transactions</p></div><button class="text-button" data-go="sales">View all</button></div><div class="table-wrap"><table><thead><tr><th>Receipt</th><th>Product</th><th>Amount</th><th>Payment</th></tr></thead><tbody id="recent-sales"></tbody></table></div></article>
      <article class="panel"><div class="panel-title"><div><h3>Stock watch</h3><p>Items that may need restocking</p></div><button class="text-button" data-go="inventory">Inventory</button></div><div id="stock-watch" class="stock-watch"></div></article></div>
      <div class="notice"><span>ℹ</span><div><strong>Connected system</strong><p>Records are stored in the GlassMart MySQL database and shared according to each account’s permissions.</p></div></div>
    </section>
    <section class="view" id="sales"><div class="section-intro"><div><h2>Record a sale</h2><p>Record a customer transaction and update stock automatically.</p></div></div>
      <div class="form-panel panel"><form id="sale-form" class="form-grid"><label>Product<select id="sale-product" required></select></label><label>Quantity<input id="sale-quantity" type="number" min="1" value="1" required></label><label>Payment method<select id="sale-payment"><option>Cash</option><option>M-Pesa</option><option>Bank transfer</option><option>Other</option></select></label><div class="form-total"><span>Estimated total</span><strong id="sale-total">KSh 0</strong></div><button class="primary" type="submit">Save sale</button></form></div>
      <article class="panel page-panel"><div class="panel-title"><div><h3>Sales history</h3><p>Transactions recorded in the system</p></div></div><div class="table-wrap"><table><thead><tr><th>Receipt</th><th>Time</th><th>Product</th><th>Qty</th><th>Total</th><th>Payment</th><th>Staff</th></tr></thead><tbody id="sales-table"></tbody></table></div></article>
    </section>
    <section class="view" id="inventory"><div class="section-intro"><div><h2>Inventory management</h2><p>Keep stock quantities and selling prices up to date.</p></div><button class="primary" id="show-product-form" data-roles="admin,manager">＋ Add product</button></div>
      <article class="panel product-form-panel hidden" id="product-form-panel"><h3>Add a product</h3><form id="product-form" class="form-grid"><label>Product name<input id="product-name" required></label><label>Price (KSh)<input id="product-price" type="number" min="1" required></label><label>Opening quantity<input id="product-qty" type="number" min="0" required></label><div class="form-actions"><button type="button" class="secondary" id="cancel-product">Cancel</button><button class="primary" type="submit">Save product</button></div></form></article>
      <article class="panel page-panel"><div class="panel-title"><div><h3>Product stock</h3><p>Stock balances from the database</p></div><input class="search" id="inventory-search" placeholder="Search products..."></div><div class="table-wrap"><table><thead><tr><th>Product</th><th>Unit price</th><th>In stock</th><th>Status</th><th>Adjust quantity</th></tr></thead><tbody id="inventory-table"></tbody></table></div></article>
    </section>
    <section class="view" id="purchases" data-roles="admin,manager"><div class="section-intro"><div><h2>Purchase records</h2><p>Record stock received from suppliers.</p></div></div>
      <article class="panel"><form id="purchase-form" class="form-grid"><label>Product<select id="purchase-product" required></select></label><label>Quantity received<input id="purchase-quantity" type="number" min="1" required></label><label>Supplier name<input id="purchase-supplier" required></label><label>Unit cost (KSh)<input id="purchase-cost" type="number" min="0" step="0.01" required></label><button class="primary" type="submit">Save purchase</button></form></article>
      <article class="panel page-panel"><h3>Purchase history</h3><div class="table-wrap"><table><thead><tr><th>Date</th><th>Product</th><th>Qty</th><th>Supplier</th><th>Unit cost</th><th>Total</th></tr></thead><tbody id="purchases-table"></tbody></table></div></article>
    </section>
    <section class="view" id="orders"><div class="section-intro"><div><h2>Remote & online orders</h2><p>Orders submitted online appear here for staff processing.</p></div></div>
      <article class="panel"><h3>Place a customer order</h3><form id="order-form" class="form-grid order-grid"><label>Customer name<input id="customer-name" required></label><label>Phone number<input id="customer-phone" required placeholder="07xx xxx xxx"></label><label>Product<select id="order-product" required></select></label><label>Quantity<input id="order-quantity" type="number" min="1" value="1" required></label><label class="full-width">Delivery location / instructions<textarea id="order-notes" rows="2"></textarea></label><button class="primary" type="submit">Submit order</button></form></article>
      <article class="panel page-panel"><div class="panel-title"><div><h3>Orders received</h3><p>Update order status as staff process them</p></div></div><div class="table-wrap"><table><thead><tr><th>Order</th><th>Customer</th><th>Product</th><th>Qty</th><th>Instructions</th><th>Status</th></tr></thead><tbody id="orders-table"></tbody></table></div></article>
    </section>
    <section class="view" id="alerts" data-roles="admin,manager"><div class="section-intro"><div><h2>SMS alerts</h2><p>Notification history for the owner and manager.</p></div></div><article class="panel page-panel"><div id="alerts-list" class="alerts-list"></div></article></section>
    <footer>© 2026 GlassMart · Sales, Stock, Purchases & Remote Orders <span>PHP + MySQL</span></footer>
  </main><div id="toast" class="toast" role="status"></div><script src="app.js"></script>
</body></html>
