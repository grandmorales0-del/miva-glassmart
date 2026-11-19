GLASSMART SALES, STOCK, PURCHASES & ONLINE ORDERS SYSTEM
=======================================================

This package connects the existing GlassMart dashboard design to PHP and MySQL.

FEATURES
- Role-based sign-in: admin, manager, staff and customer account types.
- Shared MySQL records instead of browser localStorage.
- Dashboard totals, sales history and low-stock monitoring.
- Sales recording with automatic stock deduction.
- Inventory and stock adjustments.
- Supplier purchase records with automatic stock increase.
- Online order page at order.php.
- Remote order status management.
- SMS notification queue records for active admin/manager phone numbers.


- Add CSRF protection, rate limiting, audit logs and a proper administrator-only
  user-management screen before public production deployment.
- The system creates queued SMS records only; configure an SMS provider separately.
