# Signup flow

## What the parent sees

1. **Main page.** Logged-in parents see:
   * contacts that already have an active membership (not charged again),
   * the children who will be processed, with type, discount, sibling
     discount and fee,
   * sub-total, additional discounts (financial aid / subsidy) and total,
   * an installments drop-down if the page allows recurring payments, with
     a "divide $X and pay $Y" helper.

   The page amount is set to the family total. If no child is waiting for
   payment the table says "No Child Available for Membership Signup." (the
   form can still be submitted, see [known issues](known-issues.md)). If
   discounts bring the total to 0.00 or below, a warning is shown under the
   table and the form can't be submitted (see
   [fees and discounts](fees-and-discounts.md#family-discounts)).
2. **Confirm page.** The same table and the amount being charged now.
3. **Thank-you page.** The same table. While this page is built the
   memberships, line items and groups/tags are processed.

Anonymous visitors see a login/registration prompt instead of the form.

## Hook sequence

| Step | Hook / place | What happens |
| --- | --- | --- |
| 1 | `hook_civicrm_preProcess` (Main) | For a configured page, resets the signup session values and sets `membership_custom_signup` = TRUE and `membership_custom_signup_page` = page ID. For any other contribution page sets the flag to FALSE. |
| 2 | `hook_civicrm_alterTemplateFile` (Main) | Anonymous user on a configured page → `AccessDeniedMembership.tpl` with the CMS login URL. |
| 3 | `hook_civicrm_buildAmount` (Main, logged in) | Builds the related-contact list, active members and waiting members; calculates fees and family discounts; stores them in the session; sets every option on the page to the total. If discounts bring the total to 0.00 or below, sets the warning `membershipsNoAmountDue` (template and form). |
| 4 | `hook_civicrm_buildForm` (Main) | Adds `Preview.tpl` (tables). With `membershipsNoAmountDue` set, disables the submit button. If the page is recurring, replaces `installments` with a 2…N select and adds `RecuringHelp.tpl`. |
| 4a | `hook_civicrm_validateForm` (Main) | With `membershipsNoAmountDue` set, refuses the submission with that message. |
| 5 | `hook_civicrm_postProcess` (Main) | With installments chosen, sets the amount to the per-installment amount (rounded **up** to the cent). |
| 6 | `hook_civicrm_buildForm` (Confirm) | Adds `Preview.tpl`. |
| 7 | Core Confirm (FOIS-patched) | Charges the amount calculated by the extension instead of the page amount. |
| 8 | `hook_civicrm_pre` (Contribution create/edit) | For the signup page's own contribution only: drops core's default line item (`skipLineItem`), so the extension can add one line per child. |
| 9 | `hook_civicrm_post` (Contribution) | Groups/tags for members linked to the contribution (see [accounting](memberships-and-accounting.md#groups-and-tags)). |
| 10 | `hook_civicrm_postProcess` (Confirm) | Saves the new contribution ID in the session (`family_contributionID`). |
| 11 | `hook_civicrm_alterMailParams` | Adds the membership table to the online receipt (if the template has the snippet). |
| 12 | `hook_civicrm_buildForm` (ThankYou) | Adds `Preview.tpl`; processes memberships and line items for `family_contributionID` (and records the payment of a Completed contribution, see [accounting](memberships-and-accounting.md#financial-records)), applies groups/tags, then clears `family_contributionID` so a reload does nothing. |

## When memberships are processed

Processing on the thank-you page happens only if the contribution is:

* **Completed**, or
* **Pending and pay-later**, or
* **Pending with a recurring contribution** (first installment not yet
  confirmed by the processor).

Otherwise nothing is created, and reloading the thank-you page later tries
again. A contribution that already has membership line items is never
processed twice.

## Session keys

| Key | Set in | Content |
| --- | --- | --- |
| `membership_custom_signup` | preProcess | TRUE while on a configured page; FALSE after visiting another contribution page. Read by the FOIS core Confirm patch. |
| `membership_custom_signup_page` | preProcess | ID of the configured page. Read by the FOIS core Confirm patch. |
| `membershipTobWithContact` | buildAmount | Children to process, with fee details. |
| `otherDiscounts` | buildAmount | Family discount lines. |
| `originalTotalAmount` | buildAmount | Sub-total before family discounts. |
| `family_contributionID` | Confirm postProcess | Contribution to process on the thank-you page; cleared once processed. |
