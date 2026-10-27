# Memberships and accounting

Processing runs once per contribution from the thank-you page
(`CRM_Memberships_Helper::processMembership()`), see
[signup flow](signup-flow.md#when-memberships-are-processed).

## Memberships

For each child, `CRM_Memberships_Utils::createMemberships()` works on the
waiting membership found on the main page (or, failing that, the child's
membership of the same type):

| Situation | Result |
| --- | --- |
| Payment pending (pay-later or first installment pending) | The membership is saved with its current status and dates; status calculation is skipped (`skipStatusCal`), so it stays Pending until the payment completes. |
| Membership is Pending or Cancelled | Saved with its current status and dates (no renewal). For a completed payment core then recalculates the status from the dates. |
| Membership is in another status (e.g. Expired, Grace, Current) | Renewed for one term: status fixed if stale, new end date from the membership type, join date kept, start date kept for current members. |
| No membership found | A new one: Pending if the payment is pending, otherwise dates for one term from the membership type. |

A membership activity is recorded as Scheduled (pending payment) or
Completed. Each membership is linked to the contribution with a
MembershipPayment record.

When a pending payment later completes, core's order-completion handling
(`Civi\Membership\OrderCompleteSubscriber`, FOIS-patched by
`membership_no_end_date_extend.patch`) updates the memberships linked
through the `civicrm_membership` line items described below.

## Line items

Core's default line item is suppressed for the signup contribution; the
extension adds:

| Line | entity_table / entity_id | Amount | Financial type |
| --- | --- | --- | --- |
| One per child: "Child Name: Membership - <price option>" | `civicrm_membership` / the child's membership | The child's fee | From the child's price option in the "Membership Amount" price set |
| One per family discount | `civicrm_contribution` / the contribution | Negative discount | Same as the member lines (page financial type if none) |

With installments the line items carry the **full** amounts while each
installment contribution is for the installment amount. Later installments
are created by the payment processor from the recurring contribution.

## Financial records

Core records no payment when the processor completes the signup
contribution: the extension has removed its line items (`hook_civicrm_pre`),
and core skips the payment of a Pending -> Completed change without line
items (CRM-15296). Only the processor fee, if any, is recorded.

After adding the line items, `CRM_Memberships_Utils::recordMissingPayment()`
records the payment with `Payment.create` if the contribution is Completed
and has no payment yet: one payment for the contribution total, to the
processor's financial account, allocated to financial items it creates for
the extension's line items (Paid). `Payment.create` also adds a "Payment"
activity. A contribution that already has a payment is not changed, so a
payment is never recorded twice.

Pending contributions (pay-later, first installment) get no payment here;
their line items get financial items when the payment is recorded later with
`Payment.create` (core creates any that are missing).

## Groups and tags

`CRM_Memberships_Utils::addContactToGroupTag()` runs on every contribution
create/edit (`hook_civicrm_post`) and on the thank-you page. For
contributions from a configured page, for each member linked through
MembershipPayment:

| Contribution | Group | Tag |
| --- | --- | --- |
| Completed, part of a recurring series | Partial-payment group | Partial-payment tag |
| Completed, not recurring | Full-payment group | Full-payment tag |
| Anything else | — | — |

Contacts are only added, never removed (e.g. from the partial group after
the last installment). Failures are logged and do not stop the payment.
