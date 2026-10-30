# Fees and discounts

All calculation happens in `CRM_Memberships_Helper::prepareMemberList()` and
`getMembershipFee()` when the main page is built.

## Child numbering

Children are numbered in the order they are listed (by the "Siblings Number"
field if configured, otherwise relationship order). Numbering **starts after
the children who already have an active membership**: with one active
sibling, the first child to pay is "Child 2". Child 4 and later all use the
"Child 4 and more" column.

## Price per child

For the child's membership type the extension starts from the **Default Fee**
and walks the discount sets in order (1 → 5). The first set that has a value
for this child number *and* whose dates cover today wins:

* If the parent is a JCC member (JCC field answered Yes) and the set has a JCC
  value for this child number, that JCC value is used.
* Otherwise the set's "Child N" value is used.

Dates:

| Start | End | Applies when |
| --- | --- | --- |
| set | set | start ≤ now ≤ end |
| set | empty | now ≥ start |
| empty | set | now ≤ end |
| empty | empty | **never** — a set needs at least one date |

The chosen value is the price for that child; "Discount" shown on the page is
`Default Fee − price`.

## Sibling discount

Separately, the first set with a "Sibling" value for this child number and
valid dates gives a sibling discount, which is subtracted from the price:

```
fee = price − sibling discount
```

## Family discounts

After the per-child fees are added up (the **sub-total**), up to two
discounts are applied, each calculated on the sub-total:

| Discount | Condition | Amount |
| --- | --- | --- |
| Approved Financial Aid | Parent is in the financial-aid group and has values in both the amount and type fields | Type field `fixed_amount` → amount off; anything else → percent off |
| Subsidy | Parent is in the special-discount group | Configured amount, percent (1) or fixed (2) |

Each becomes a negative line on the contribution, labelled e.g.
"Approved Financial Aid ($ 360.00)" or "Subsidy (10% on $ 1,200.00)". The
group test uses the parent's static group memberships (smart groups are not
included). The total is rounded to cents.

Together the discounts never take the total below zero: each one is capped at
what is left of the sub-total, financial aid first. With a sub-total of 200,
financial aid of 150 (fixed) and a subsidy of 100 (fixed), the lines are
−150 and −50 and the total is **0.00**; the subsidy line keeps its label
"Subsidy ($ 100.00)".

A signup with a total of 0.00 (e.g. 100% financial aid) needs no payment:
core asks for no card details and completes the contribution without
calling the processor. The main page says so under the table ("Your
discounts cover the full fee; there is nothing to pay, so no card details
are needed."). Instalments are not offered on a 0.00 total: the
checkbox is disabled, and a submission that ticks it anyway is refused.

## Installments

When the parent chooses N installments, the amount charged per installment is
`total ÷ N` rounded **up** to the cent (`roundupMoneyForInstallment()`), so N
installments can add up to a few cents more than the total.

## Worked example

Membership type "Returning", Default Fee 710. Discount set 1: start
1 Aug, end 30 Sep, Child 1 = 650, Child 2 = 600, Sibling Child 2 = 25.
Today is 15 Sep. One sibling already has an active membership; two children
are waiting; the parent has financial aid of 300 (fixed).

| Child | Number | Price | Sibling | Fee |
| --- | --- | --- | --- | --- |
| Roni | 2 | 600 | 25 | 575 |
| Amit | 3 | 710 (no Child 3 value in set 1) | 0 | 710 |

Sub-total 1285, financial aid −300, total **985**. With 6 installments the
parent pays 164.17 per month (985 ÷ 6 = 164.1666… rounded up).
