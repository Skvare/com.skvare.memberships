# Configuration

## Settings page

**Administer » CiviMember » Custom Membership Signup Setting**
(`civicrm/admin/memberships/setting`, requires `administer CiviCRM`).

Settings are saved for the current domain only, in the setting
`memberships_config_<domainId>`. Each FOIS site therefore has its own
configuration.

| Field | Key | Used for |
| --- | --- | --- |
| Online Contribution Page (required, multiple) | `memberships_contribution_page_id` | Pages that run the custom signup. Every other page is left alone. |
| Relationship type (required, multiple) | `memberships_relationships` | Which related contacts are listed. The label describes the logged-in user: "Parent of" (`<id>_b_a`) lists the user's children; "Child of" (`<id>_a_b`) lists their parents; non-directional types list both sides. All FOIS sites use "Parent of". |
| Membership Type (required, multiple) | `memberships_membership_types` | Only memberships of these types are listed and charged. Each selected type gets a fee table (see below). |
| Filter on Membership Status | `memberships_membership_allowed_status` | Statuses of memberships that are waiting to be paid (FOIS: Pending). |
| Add Contact to Group on Full one time payment | `memberships_group_full_paid` | Group for each child when a non-recurring payment is Completed. |
| Add Contact to Group on partial payment | `memberships_group_partial_paid` | Group for each child when a recurring (installment) payment is Completed. |
| Tag Contact on Full one time payment | `memberships_tag_full_paid` | Tag, same rule as the full-payment group. |
| Tag Contact on partial payment | `memberships_tag_partial_paid` | Tag, same rule as the partial-payment group. |
| Special Discount Group / amount / type | `memberships_special_discount_group`, `_amount`, `_type` | A "Subsidy" discount for parents in the group: percent (1) or fixed amount (2). |
| Financial assistance/discount: group, field for amount, field for type | `memberships_financial_discount_group`, `_discount_amount`, `_discount_type` | An "Approved Financial Aid" discount for parents in the group. Amount and type are read from the parent's fields (custom fields); a type value of `fixed_amount` means a fixed amount, anything else a percentage. |
| JCC Field Name | `memberships_jcc_field` | If the parent's answer in this field is Yes, the JCC prices are used. For a custom field the answer's label is compared, so a Yes/No field (1/0) and a field with Yes and No options (e.g. 1/2) both work; any other answer, No or empty, gives the normal prices. |
| Membership Match Condition (field, operator, value) | `memberships_type_field`, `_operator`, `_condition` | Extra filter on the waiting memberships. Only equality is applied (`field = value`); the operator is not used. |
| Sort Child using Siblings Number Field | `memberships_siblings_number` | Orders children by this field (ascending, empty last) before numbering them Child 1, 2, … |

The form also carries a few keys used only by the legacy wizard
(`memberships_financial_type_id`, `memberships_payment_processor`,
`memberships_is_recur*`, `memberships_recur_frequency_unit`); their fields
are hidden in the template.

### Fee tables per membership type

A fieldset is shown for each **saved** membership type. After adding a type to
"Membership Type", save once and the fee table appears.

* **Default Fee** (`regular`): the full price. Leave it blank only for types
  you do not sell; a blank or missing fee is charged as 0 (a warning is
  logged).
* **Discount sets 1–5**: name, start date, end date, and prices for Child 1,
  2, 3 and "4 and more", with a JCC row and a Sibling Discount row. The child
  and JCC values are the **price to charge**, not an amount off. Sibling values
  are an amount taken off that price. Leave a cell empty for "no discount".

How these combine is described in [fees and discounts](fees-and-discounts.md).

## Contribution page

1. Create a contribution page (not a membership page) with **one** fixed
   amount option. The extension replaces the amount with the family total.
2. Pick the financial type you want on the contribution; it is also used for
   discount lines when a child's price option cannot be found.
3. To allow installments, enable recurring contributions on the page with a
   recurring-capable processor and turn on "installments". On the **Amounts**
   tab set **Maximum Offer installments** (2–12; shown when a live
   recurring-capable processor exists). Parents then pick 2…N installments.
   If it is not set, 3 is the maximum.
4. Select the page in the extension settings.

Anonymous visitors to a configured page get a "please log in / create an
account" message instead of the form (`templates/AccessDeniedMembership.tpl`).

## Price set

Line items use the reserved "Membership Amount" price set
(`default_membership_type_amount`; the code also looks it up by ID 2). Each
membership type you sell needs an active option in it; the option label is
used in the line item label ("Child Name: Membership - <option label>").

## Receipt template

The membership table can be added to the online receipt. Add this inside the
receipt table, above the line items, in each domain's *Contributions -
Receipt (on-line)* template:

```smarty
{if $custom_membership_signup and $membershipDetails}
  <tr><td colspan="2">{$membershipDetails nofilter}</td></tr>
{/if}
```

The table comes from the parent's browser session, so it is only included
when the receipt is sent during the signup itself (not for receipts sent
later, e.g. by a payment processor webhook).
