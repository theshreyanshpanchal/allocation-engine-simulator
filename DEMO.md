# Demo script — Allocation Engine Simulator

A short guide for the screen share. Read the **You say** lines slowly, in plain
English. Short sentences. Pause after each one.

## What this is

A small working demo of one rule from Project Atlas: when several franchise
stores can sell the same part to the same buyer, which store is shown as the
main offer?

This is not the whole marketplace. It is the one piece that is different from
every other marketplace.

## The one idea to get across

> The cheapest store does not automatically win.
> Stores that can serve the buyer share the customers, based on how well they perform.
> A price limit stops any store being too far above the cheapest.
> Every decision is recorded, so we can explain it later.

## Before you start

- App running at **http://127.0.0.1:8000**
- Run `php artisan atlas:reset --force` once, so the history starts clean
- Browser full screen
- During the demo, use the **Reload / Re-evaluate** button on the page — not the
  browser refresh button

---

## The walkthrough (about 5 minutes)

### 1. The starting picture

Open the dashboard.

**You say:** "This is the simulator. We have three stores: A, B and C. Each has a
performance score out of five. Store A scores five. Store B four. Store C three."

**You say:** "Because of those scores, the target is: Store A gets about 42
percent of customers. Store B about 33. Store C about 25. The stronger the
store, the bigger its share."

Point at the target numbers, and at the featured store (Store A right now).

### 2. The price limit

Point at the slider. It is set to 10 percent.

**You say:** "This slider is the price safety limit. The cheapest offer here is
470. At 10 percent, any store priced up to about 517 is allowed in. All three
stores are inside that range. So all three compete."

### 3. Run the customers through

Set the number to 1,000. Click **Simulate purchases**.

**You say:** "Now we send one thousand pretend customers through the rule."

When the charts appear:

**You say:** "The bars show the target next to what actually happened. They match
closely. The line chart shows it settling onto the target as more customers
arrive. This is on purpose. It is not random luck."

### 4. Make the price limit strict

Drag the slider down to **2 percent**.

**You say:** "Now the limit is tight. Only stores priced very close to 470 are
allowed. Store A and Store B are now too expensive. Only Store C is left."

Click **Simulate purchases** again.

**You say:** "With only one store allowed, it gets everything. Notice the score
did not matter here. Price decided."

### 5. Set the limit to zero

Drag the slider to **0 percent**.

**You say:** "At zero, only the very cheapest store can ever win. The system
shows a warning, because the business needs to know this switches off the fair
sharing."

Show the "Keep 0%" and "Return to previous value" buttons. Then set it back to
**10 percent**.

### 6. Change where the buyer lives

In the buyer postcode box, type **08000-000**.

**You say:** "Now the buyer is in a different area. Only Store C covers this
area. Store A and Store B drop out before price or score are even looked at.
They simply cannot deliver here."

Now try **99999-999**.

**You say:** "And if no store covers the area, the system says so clearly,
instead of guessing."

Set it back to **01310-100**.

### 7. Show the audit

Go to **Allocation Logs**. Click any decision.

**You say:** "Every decision is saved. For this one we can see every store, their
price, their score, the lowest price, the price limit, who was excluded and why,
and who won. There is a plain-English explanation and a technical one."

**You say:** "The client asked for this to be auditable, not a black box. This
is that."

### 8. Show it stays stable

Go back to the dashboard. Click **Reload / Re-evaluate** a few times.

**You say:** "If the same customer comes back later in the same visit, they see
the same featured store. Not a new one every time. That was a specific
requirement."

---

## If you have extra time

- **Scenario Playbook** — eleven ready-made examples, each showing one part of
  the rule on its own.
- **Stores** and **Products** pages — the background data, with a plain-language
  glossary of every field.

## If something looks wrong

- Numbers frozen or odd → click **Reload / Re-evaluate**, not the browser refresh.
- Want a clean slate → run `php artisan atlas:reset --force`, then reload the page.

## Quick answers if asked

**Why not just pick the cheapest?**
The stores are all one brand's franchises. A price war between them hurts the
business. So they share customers by performance instead.

**Is the sharing random?**
No. It is worked out so the real numbers track the target over time. Same
inputs give the same result.

**What is the score based on?**
Late deliveries, cancellations and complaints, over the last 30 days. Nothing
else. Not size, not how long they have been a partner.

**Can a store buy its way to the top?**
No. Price only decides whether a store is allowed in. After that, only the
performance score sets the share.

**What is this not?**
It is a focused demo of one rule, with sample data. It is not the catalog,
cart, checkout or seller panel. Those are the main build.
