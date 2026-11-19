const role = document.body.dataset.role || 'staff';
const state = {products:[],sales:[],orders:[],purchases:[],alerts:[],today_sales:0};
const $ = id => document.getElementById(id);
const money = n => 'KSh ' + Number(n || 0).toLocaleString('en-KE',{maximumFractionDigits:2});
const esc = value => String(value ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function toast(message){const t=$('toast');if(!t)return;t.textContent=message;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2600);}
async function api(action, data=null){
 const options=data===null?{}:{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)};
 const response=await fetch(`api.php?action=${encodeURIComponent(action)}`,options);
 const result=await response.json();
 if(!response.ok)throw new Error(result.error||'Request failed.');
 return result;
}
function showView(view){
 document.querySelectorAll('.view').forEach(v=>v.classList.toggle('active-view',v.id===view));
 document.querySelectorAll('.nav').forEach(b=>b.classList.toggle('active',b.dataset.view===view));
 const names={dashboard:'Dashboard',sales:'Sales',inventory:'Inventory',purchases:'Purchases',orders:'Remote Orders',alerts:'SMS Alerts'};
 $('page-title').textContent=names[view]||'Dashboard';
}
document.querySelectorAll('[data-view]').forEach(b=>b.addEventListener('click',()=>showView(b.dataset.view)));
document.querySelectorAll('[data-go]').forEach(b=>b.addEventListener('click',()=>showView(b.dataset.go)));
document.querySelectorAll('[data-roles]').forEach(el=>{const allowed=el.dataset.roles.split(',');if(!allowed.includes(role))el.remove();});
function fillProducts(id){const s=$(id);if(!s)return;const old=s.value;s.innerHTML=state.products.map(p=>`<option value="${p.id}">${esc(p.name)} — ${money(p.price)} (stock: ${p.qty})</option>`).join('');if(state.products.some(p=>String(p.id)===old))s.value=old;}
function render(){
 $('today-sales').textContent=money(state.today_sales);
 $('stock-count').textContent=state.products.reduce((a,p)=>a+Number(p.qty),0).toLocaleString();
 $('pending-count').textContent=state.orders.filter(o=>o.status==='Pending').length;
 $('low-count').textContent=state.products.filter(p=>p.qty<=5).length;
 $('recent-sales').innerHTML=state.sales.slice(0,5).map(s=>`<tr><td><strong>${esc(s.receipt)}</strong></td><td>${esc(s.product)}</td><td>${money(s.total)}</td><td>${esc(s.payment)}</td></tr>`).join('')||'<tr><td colspan="4" class="empty">No sales recorded yet.</td></tr>';
 const low=state.products.filter(p=>p.qty<=5);
 $('stock-watch').innerHTML=low.map(p=>`<div class="watch-item"><div><div class="watch-name">${esc(p.name)}</div><div class="watch-price">${money(p.price)} per unit</div></div><span class="pill low">${p.qty} left</span></div>`).join('')||'<div class="empty">No low-stock products.</div>';
 $('sales-table').innerHTML=state.sales.map(s=>`<tr><td>${esc(s.receipt)}</td><td>${esc(s.time)}</td><td>${esc(s.product)}</td><td>${s.qty}</td><td>${money(s.total)}</td><td>${esc(s.payment)}</td><td>${esc(s.staff)}</td></tr>`).join('')||'<tr><td colspan="7" class="empty">No sales recorded.</td></tr>';
 const q=($('inventory-search')?.value||'').toLowerCase();
 $('inventory-table').innerHTML=state.products.filter(p=>p.name.toLowerCase().includes(q)).map(p=>`<tr><td>${esc(p.name)}</td><td>${money(p.price)}</td><td>${p.qty}</td><td><span class="pill ${p.qty<=5?'low':'good'}">${p.qty<=5?'Low stock':'In stock'}</span></td><td>${['admin','manager'].includes(role)?`<button class="secondary adjust" data-id="${p.id}" data-delta="-1">−</button> <button class="secondary adjust" data-id="${p.id}" data-delta="1">+</button>`:'—'}</td></tr>`).join('')||'<tr><td colspan="5" class="empty">No matching products.</td></tr>';
 $('orders-table').innerHTML=state.orders.map(o=>`<tr><td>${esc(o.orderNo)}</td><td>${esc(o.customer)}<br><span class="muted">${esc(o.phone)}</span></td><td>${esc(o.product)}</td><td>${o.qty}</td><td>${esc(o.notes||'—')}</td><td>${['admin','manager','staff'].includes(role)?`<select class="status-select" data-id="${o.id}">${['Pending','Processing','Completed','Cancelled'].map(x=>`<option ${o.status===x?'selected':''}>${x}</option>`).join('')}</select>`:esc(o.status)}</td></tr>`).join('')||'<tr><td colspan="6" class="empty">No remote orders yet.</td></tr>';
 $('purchases-table').innerHTML=state.purchases.map(p=>`<tr><td>${esc(p.created_at)}</td><td>${esc(p.product)}</td><td>${p.qty}</td><td>${esc(p.supplier)}</td><td>${money(p.cost)}</td><td>${money(p.total)}</td></tr>`).join('')||'<tr><td colspan="6" class="empty">No purchases recorded.</td></tr>';
 $('alerts-list').innerHTML=state.alerts.map(a=>`<div class="alert-item"><div class="alert-symbol">✉</div><div><strong>${esc(a.title)}</strong><p>${esc(a.message)}</p><small>${esc(a.recipient_phone||'')} · ${esc(a.status||'Queued')} · ${esc(a.time)}</small></div></div>`).join('')||'<div class="empty">No notification records yet.</div>';
 ['sale-product','order-product','purchase-product'].forEach(fillProducts);updateSaleTotal();
 document.querySelectorAll('.adjust').forEach(b=>b.addEventListener('click',async()=>{try{await api('adjust_stock',{product_id:Number(b.dataset.id),delta:Number(b.dataset.delta)});await load();toast('Stock updated.');}catch(e){toast(e.message);}}));
 document.querySelectorAll('.status-select').forEach(s=>s.addEventListener('change',async()=>{try{await api('order_status',{id:Number(s.dataset.id),status:s.value});await load();toast('Order status updated.');}catch(e){toast(e.message);}}));
}
function updateSaleTotal(){const p=state.products.find(p=>p.id===Number($('sale-product').value));$('sale-total').textContent=money((p?.price||0)*Number($('sale-quantity').value||0));}
async function load(){try{Object.assign(state,await api('data'));render();}catch(e){toast(e.message);}}
$('sale-product').addEventListener('change',updateSaleTotal);$('sale-quantity').addEventListener('input',updateSaleTotal);
$('sale-form').addEventListener('submit',async e=>{e.preventDefault();try{await api('sale',{product_id:Number($('sale-product').value),quantity:Number($('sale-quantity').value),payment:$('sale-payment').value});e.target.reset();$('sale-quantity').value=1;await load();toast('Sale saved and stock updated.');}catch(err){toast(err.message);}});
$('show-product-form')?.addEventListener('click',()=>$('product-form-panel').classList.toggle('hidden'));
$('cancel-product')?.addEventListener('click',()=>$('product-form-panel').classList.add('hidden'));
$('product-form')?.addEventListener('submit',async e=>{e.preventDefault();try{await api('product',{name:$('product-name').value.trim(),price:Number($('product-price').value),quantity:Number($('product-qty').value)});e.target.reset();$('product-form-panel').classList.add('hidden');await load();toast('Product added.');}catch(err){toast(err.message);}});
$('inventory-search')?.addEventListener('input',render);
$('purchase-form')?.addEventListener('submit',async e=>{e.preventDefault();try{await api('purchase',{product_id:Number($('purchase-product').value),quantity:Number($('purchase-quantity').value),supplier:$('purchase-supplier').value.trim(),unit_cost:Number($('purchase-cost').value)});e.target.reset();await load();toast('Purchase saved and stock increased.');}catch(err){toast(err.message);}});
$('order-form')?.addEventListener('submit',async e=>{e.preventDefault();try{const result=await api('public_order',{customer:$('customer-name').value.trim(),phone:$('customer-phone').value.trim(),product_id:Number($('order-product').value),quantity:Number($('order-quantity').value),notes:$('order-notes').value.trim()});e.target.reset();await load();toast(result.message);}catch(err){toast(err.message);}});
load();
