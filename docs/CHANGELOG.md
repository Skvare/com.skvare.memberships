# Changelog

## 1.1.1 — unreleased

### Fixed

* **PHP warnings on the signup page.** `makeLineItemArray2()` read
  `membership_type_id` of every option in the default membership price set,
  which API3 leaves out when an option has none ("Undefined array key"), and
  `memberships_civicrm_buildAmount()` read the page's financial type from
  `_values`, which is not loaded yet when CiviCRM 6.x first calls the hook
  ("Trying to access array offset on null"); it now uses
  `getContributionPageValue()`.
* **Completed signups had no payment.** On CiviCRM 6.16 the processor
  completes the signup contribution while it has no line items, so core
  records no payment for it (only the processor fee); removing
  `recordFinancialAccounts()` in 1.1.0 left the contribution Completed with
  nothing paid. After adding the line items the extension now records the
  payment with `Payment.create` when a Completed contribution has none, which
  also creates and allocates the line items' financial items. Contributions
  that already have a payment are not changed.
* **Family discounts could take the total below zero.** Financial aid and
  subsidy were each taken off the sub-total, so together they could exceed
  it; the page showed a negative total and core refused the payment
  ("Contribution can not be less than zero"). Each discount is now capped at
  what is left, so the total stops at 0.00 and the signup completes without
  a payment. Installments are not offered on a 0.00 total.
* **$0 contributions when no child was waiting.** The page said "No Child
  Available for Membership Signup." but could still be submitted, creating a
  $0 contribution with no memberships. The submit button is now disabled and
  the form is refused.

## 1.1.0 — 2026-10-22

CiviCRM 6.16 / PHP 8.3 review. Requires CiviCRM 6.16+.

### Fixed

* **Thank-you page reload re-processed the signup.** Each reload renewed the
  children's memberships again (extending end dates) and added another set of
  line items. A contribution is now processed once.
* **Duplicate payment transaction.** Adding the line items called
  `recordFinancialAccounts()` again, which on CiviCRM 6.16 recorded a second
  payment transaction for Completed contributions. The call was removed
  (but see 1.1.1: for the signup page's own contributions it was the only
  payment).
* **Line items removed from unrelated contributions.** After visiting the
  signup page, any contribution created or edited in the same browser session
  (event registration, back-office entry) lost its line items. Only the
  signup page's own contribution is affected now.
* **Discount lines pointed at a membership.** Financial aid / subsidy lines
  are `civicrm_contribution` lines but stored the last child's membership ID as
  `entity_id`; they now store the contribution ID. Existing rows are unchanged
  (see [known issues](known-issues.md#accounting)).
* **Membership table missing from receipts.** The receipt hook relied on
  parameters CiviCRM 6 no longer passes (`groupName`,
  `contributionPageId`); it now matches the `contribution_online_receipt`
  workflow and looks up the page.
* **"Child of" style relationship selections** queried the wrong side of the
  relationship. "Parent of" selections (all current configurations) behave as
  before.
* **Fresh installs** create the `is_recur_installments_number` column again
  (the civix conversion removed `sql/auto_install.sql`); install and upgrade
  skip it if it already exists.
* PHP 8 errors and warnings: a blank Default Fee no longer causes a
  `TypeError`; a selected membership type without a fee rule logs one warning
  instead of PHP warnings; missing session discounts, empty selections and
  unmatched price fields are handled; totals are rounded to cents (financial
  aid produced amounts like `0.5800000000000409`).
* Membership type lists are no longer cut at 25 rows (API3 default limit).

### Changed

* CiviCRM 6 port (branch `civicrm_6_16`): membership creation no longer uses
  the removed `CRM_Member_BAO_Membership::processMembership()`; the
  ContributionPage field is declared through `civi.entity.fields` instead of
  `fields_callback`; Smarty 5 template directory handling.
* civix upgraded to format 26.10.3: mixins (`menu-xml`, `smarty-v2`,
  `scan-classes`), `CRM_Extension_Upgrader_Base`, generated hook boilerplate
  removed, PSR-0 class loading declared in `info.xml`.
* Deprecated APIs replaced: `assign_by_ref()`, `get_template_vars()`,
  `CiviCRM_API3_Exception` / `API_Exception`, float input to brick/money.
* Removed debug logging of every contribution ID on the Confirm page and the
  unused `setVar()` dynamic properties on the core Main form.
* `getPriceFees()` binds its parameter instead of interpolating it into SQL.
* `info.xml`: version 1.1.0, stable, CiviCRM 6.16, PHP 8.2/8.3, Smarty 5,
  project URLs. README rewritten; documentation added in `docs/`.
* Permission `parent_signup_child` declared with label and description
  (CiviCRM 6 format).

## 1.0 — 2021-06-28

Initial version (sibling discounts, installments, FOIS signup flow).
