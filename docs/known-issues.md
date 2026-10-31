# Known issues and recommendations

These were found during the CiviCRM 6.16 / PHP 8.3 review (October 2026) and
were deliberately **not** changed in 1.1.0, either because they need a design
decision or because they would change how payments are recorded. Numbers come
from the local copy of the FOIS database.

## Behaviour

1. **$0 contributions when nothing is due.** If a parent has no waiting
   children the page shows "No Child Available" but can still be submitted,
   creating a $0 contribution with no memberships (two seen on page 259:
   78586, 86378). Fixed in 1.1.1: the submit button is disabled and
   `hook_civicrm_validateForm` refuses the form when no child is waiting.
2. **Processing depends on the thank-you page.** Memberships, line items and
   groups/tags are only created when the parent's browser renders the
   thank-you page in the same session. If that never happens (browser closed,
   off-site processor not returning) the payment is recorded but the children
   are not processed. Recommendation: move processing to a payment-completion
   point (e.g. `hook_civicrm_post` on the contribution or an
   `civi.order.complete` listener) and store the child list on the
   contribution instead of the session.
3. **Test-mode signups create live memberships.** Fixed in 1.1.1: in
   test-drive mode (`action=preview`) the page lists the children's test
   (`is_test = 1`) memberships and the test payment creates or renews test
   memberships only, without groups or tags. To test a signup, the children
   need test Pending memberships; the webform creates live ones.
4. **Discount sets need a date.** A set with neither start nor end date never
   applies; this is easy to miss when entering a permanent discount.
5. **Family discounts are not combined.** Financial aid and subsidy are both
   calculated on the sub-total. Fixed in 1.1.1: together they no longer take
   the total below zero; the second is capped at what is left (see
   [fees and discounts](fees-and-discounts.md#family-discounts)).
6. **Match condition operator is ignored.** The "Membership Match Condition"
   always filters with `=`.
7. **Group checks ignore smart groups.** The financial-aid and special
   discount groups are checked against the parent's static group memberships
   only.
8. **Groups/tags are only ever added.** A child stays in the partial-payment
   group/tag after the last installment.
9. **Receipt table needs the session.** Receipts sent outside the signup
   session (webhooks, scheduled jobs, "resend receipt") do not get the
   membership table. The table also includes the page's JavaScript block,
   which email clients ignore.
10. **Settings layout.** Six discount sets are stored but only five are shown;
    a newly selected membership type needs one save before its fee table
    appears.

## Configuration found on FOIS

* **Domain 12** has membership type 48 selected without a fee rule. Its
  waiting memberships are charged 0; 1.1.0 logs a warning
  ("no fee rule configured for membership type 48") instead of PHP warnings.
  Either remove the type or set its Default Fee.
* Domain 1's *Contributions - Receipt (on-line)* template does not contain the
  membership table snippet, so receipts there never showed it.

## Accounting

* **No financial items on older contributions' lines.** Before 1.1.1 the
  line items the extension adds to the signup contribution had no
  `civicrm_financial_item` rows (core's own line, which would have them, is
  suppressed), so income-by-account reports and bookkeeping exports miss
  them. 1.1.1 creates them for single payments recorded on the thank-you page
  (see [accounting](memberships-and-accounting.md#financial-records));
  existing contributions were not changed.
* **Signups completed on 6.16 before 1.1.1 have no payment.** Between the
  CiviCRM 6.16 upgrade and 1.1.1, a Completed single-payment signup has no
  payment transaction (only the processor fee). To list them:

  ```sql
  SELECT c.id, c.total_amount, c.receive_date
  FROM civicrm_contribution c
  WHERE c.contribution_status_id = 1 AND c.total_amount > 0
    AND c.contribution_page_id IN (/* configured signup pages */)
    AND NOT EXISTS (
      SELECT 1 FROM civicrm_entity_financial_trxn eft
      INNER JOIN civicrm_financial_trxn ft ON ft.id = eft.financial_trxn_id
      WHERE eft.entity_table = 'civicrm_contribution'
        AND eft.entity_id = c.id AND ft.is_payment = 1);
  ```

  Each can be repaired with `CRM_Memberships_Utils::recordMissingPayment($id)`
  (records the payment against the contribution's payment method; agree
  with whoever reconciles FOIS accounts first).
* **Historical discount lines point at a membership.** Before 1.1.0 the
  discount lines were saved with `entity_table = 'civicrm_contribution'` but
  `entity_id` = the last child's membership ID. 1.1.0 fixes new ones; existing
  rows were not changed (578 lines on 571 contributions, 2021-08-02 →
  2025-05-24). To list them:

  ```sql
  SELECT li.id, li.contribution_id, li.entity_id, li.label, li.line_total
  FROM civicrm_line_item li
  WHERE li.entity_table = 'civicrm_contribution'
    AND li.entity_id <> li.contribution_id;
  ```

  A correction would be `UPDATE civicrm_line_item SET entity_id =
  contribution_id WHERE …` on the same condition — check first that every row
  is one of this extension's discount lines.
* **Installment rounding.** Installments are rounded up to the cent, so the
  installments can total slightly more than the family total (by design).

## Code

* **Hard-coded price set.** `getPriceFees()` uses price set ID 2 and
  `makeLineItemArray2()` the name `default_membership_type_amount`; both are
  the reserved "Membership Amount" set on FOIS, but should use one lookup.
* **Every contribution runs a lookup.** `hook_civicrm_post` loads every
  created/edited contribution to check its page; cheap, but it could check
  the page ID from the object first.
* **API3 throughout.** Works on 6.16; migrate to API4 when the code is next
  reworked.
* **Settings without metadata.** The configuration is one array per domain
  under a dynamic setting name, so it is invisible to the Settings API
  metadata and exports.
* **Legacy wizard.** `civicrm/members/signup` uses the deprecated
  `Contribution.transact` API (logs a deprecation on 6.16) and only needs
  `access CiviCRM`. Remove it if unused (see [integration](integration.md#legacy-signup-wizard)).

## Outside this extension

* The FOIS core patch `civicrm_membership_type_per_domain.patch` limits
  `CRM_Member_BAO_MembershipType::getAllMembershipTypes()` to the current
  domain, but the shared "Membership Amount" price set has options for every
  domain's types. Loading that price set's metadata (e.g. rendering a receipt
  for a contribution with membership lines, or `Payment.create` for an
  installment) raises three PHP warnings per foreign type in
  `CRM/Financial/BAO/Order.php`; where warnings are turned into errors the
  payment stops. `civicrm_membership_type_other_domain.patch` makes
  `getMembershipType()` fall back to every domain's types for an ID that is
  not in the current domain (listings stay per domain).
