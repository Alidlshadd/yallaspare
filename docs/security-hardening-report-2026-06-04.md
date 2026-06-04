# YallaSpare Security Hardening Report

Date: 2026-06-04

## Executive Summary

The confirmed high-priority findings from the audit were remediated or mitigated without UI changes. The fixes focus on authorization boundaries, shared checkout/payment logic, refund controls, short-lived Sanctum tokens, webhook SSRF prevention, PII-safe logging, queue reauthorization, and an application-level mitigation for the Laravel email CRLF advisory.

Production readiness is improved, but launch remains conditional on completing the vendor framework upgrade path for CVE-2026-48019 because Composer still reports the upstream Laravel advisory for Laravel 10.

## Fixed Or Mitigated Findings

| Priority | Finding | Root Cause | Secure Replacement | Test Coverage |
| --- | --- | --- | --- | --- |
| 1 | Web admin privilege escalation | Non-super admins with user-management permission could submit `super_admin` as a role. | Super-admin assignment or modification now requires an existing super admin and logs blocked attempts. | `SecurityHardeningRegressionTest::test_user_manager_cannot_promote_self_to_super_admin` |
| 2 | Mobile checkout architecture drift | Mobile checkout duplicated order logic and missed shared side effects. | Mobile checkout and buy-now now use `CheckoutService`; online methods start through `PaymentService`. | `test_mobile_checkout_uses_shared_checkout_side_effects`, mobile checkout/buy-now suites |
| 3 | Mobile admin authentication | Admin-like mobile endpoints trusted user role/permission alone. | Personal access tokens must include the `admin:mobile` ability; ordinary mobile login tokens do not receive it. | `test_mobile_login_token_cannot_call_admin_api_without_admin_mobile_ability`, `MobileAdminSecurityTest` |
| 4 | Refund workflow abuse | Refund status could be set without finance authorization or paid-order validation. | Refunded transitions require `finance.manage`, row locks, paid order status, and positive in-bounds refund amount. | `test_refund_requires_finance_permission_and_paid_order` |
| 5 | Payment workflow abuse | Manual payment status edits were available to order managers and lacked locking. | Manual payment edits require `finance.manage` and run inside a locked transaction. | `test_manual_payment_update_requires_finance_permission` |
| 6 | Sanctum token security | Mobile tokens were effectively long-lived bearer credentials. | Sanctum now has a 14-day default expiration and `ysp_` token prefix; issued mobile tokens include explicit abilities. | `test_mobile_login_token_cannot_call_admin_api_without_admin_mobile_ability` |
| 7 | Webhook SSRF | Notification webhooks accepted arbitrary URLs. | Webhooks require HTTPS, DNS resolution, and public non-reserved IP targets. Logs store only recipient hashes. | Covered by existing communication and security regression checks; manual review required for real DNS environments. |
| 8 | PII logging | Email/SMS/WhatsApp notification logs included raw recipients and message content. | Logs use SHA-256 recipient hashes and omit message bodies from queued/log fallback entries. | Existing email outbox hash tests plus source review. |
| 9 | Queue security | Queued privileged mail jobs could outlive admin permission changes. | Broadcast jobs reauthorize initiating admins at execution time; queue connections use `after_commit`. | `test_email_broadcast_job_revalidates_admin_permission`, `AdminEmailPageTest` |
| 10 | Laravel CVE-2026-48019 | Laravel framework email validation is affected upstream. | Added global CRLF rejection for email-like request fields as defense-in-depth. Composer still reports the vendor advisory until framework upgrade. | `test_email_crlf_payload_is_rejected_before_validation` |

## Additional Hardening

- Order status changes moved into `OrderStatusService` with row locking, transition validation, stock restore, status history, and audit logging.
- Inventory stock changes moved into `InventoryAdjustmentService` with row locking, non-negative stock-out enforcement, inventory movement records, and audit logging.
- Coupon usage increments now lock coupon rows before enforcing usage limits and creating usage records.
- Payment provider webhooks now fail closed in production when webhook secrets are missing.
- Product reviews now require a paid delivered order, not just delivery status.
- Payment provider transaction identifiers now have database unique indexes for replay/idempotency protection.

## Verification Results

- `php artisan test`: passed, 294 tests, 1066 assertions.
- Targeted affected suite: passed, 53 tests, 208 assertions.
- New security regression suite: passed, 7 tests, 21 assertions.
- `npm audit --audit-level=moderate`: passed, 0 vulnerabilities.
- `git diff --check`: passed.
- `composer audit --no-interaction`: failed only on `laravel/framework` CVE-2026-48019. Application-level mitigation is present, but the upstream advisory remains visible because the project is on Laravel 10.

## Production Readiness Score

Current score: 82/100.

Launch decision: conditionally not approved for a high-risk public launch until the Laravel framework CVE is resolved by a supported upgrade path or formally accepted with compensating controls.

## Deployment Checklist

1. Back up production database.
2. Review existing `payments` data for duplicate provider IDs before running the new unique-index migration.
3. Run `php artisan migrate --force`.
4. Set `SANCTUM_TOKEN_EXPIRATION=20160` or a stricter value.
5. Keep ordinary mobile login tokens without `admin:mobile`; issue admin mobile tokens only after a fresh step-up challenge.
6. Configure all payment webhook secrets in production; missing secrets now fail closed.
7. Verify notification webhook URLs are HTTPS and resolve only to public IP addresses.
8. Run `php artisan config:clear && php artisan route:clear && php artisan view:clear`.
9. Run `php artisan test` in CI before deployment.
10. Plan Laravel/PHP upgrade work to eliminate CVE-2026-48019 at the vendor level.
