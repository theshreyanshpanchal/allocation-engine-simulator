# Recording script — Allocation Engine Simulator walkthrough

This is written to be **read out loud**, not skimmed. It's broken into short lines on
purpose — read a line, let it land, move to the next one. Don't worry about matching it
word for word. Read it a couple of times first so it's in your head, then talk over the
screen the way you'd explain it to someone sitting next to you.

Anything in a `[bracket]` is a **screen action**, not something you say.

Total run time if you talk at a normal pace: **6–7 minutes**.

---

## Before you hit record

- [ ] App running at `http://127.0.0.1:8000`
- [ ] Run `php artisan atlas:reset --force` once — clean history, no leftover runs on screen
- [ ] Browser maximized, zoom at 100%, no other tabs/notifications visible
- [ ] Close Slack/email — anything that could pop a notification mid-recording
- [ ] Test your mic levels for 10 seconds before the real take
- [ ] Remember: use **Reload / Re-evaluate** on the page during the demo, never the
      browser's own refresh — a real refresh resets the slider and postcode back to
      defaults

---

## 1. Open

[Screen: dashboard loaded, nothing clicked yet]

"Hey — thanks for making time to watch this.

This is a working demo of one specific rule from Atlas — the part that decides which
store gets shown first when more than one franchise can sell the same part to the same
customer.

It's not the full marketplace. I'm not going to show you cart, checkout, or the seller
panel — that's the main build, and it's a separate track. This is just the one piece
that's actually different from a normal marketplace, so I wanted to walk you through it
on its own before it's buried inside everything else.

Should take about six or seven minutes. Let's get into it."

## 2. The starting picture

[Point at the three stores on the dashboard]

"So here's the setup. Three stores — A, B, and C — all selling the same part, a front
brake disc.

Each one has a performance score out of five. Store A is a five. Store B is a four.
Store C is a three. That score is based on things like late deliveries, cancellations,
complaints — over the last thirty days. Nothing else. Not how big the store is, not how
long they've been a partner.

[Point at the target share numbers]

Because of those scores, the system works out a target: Store A should get roughly 42
percent of customers. Store B around 33. Store C around 25.

So the better a store performs, the bigger slice it gets. That's the whole idea in one
sentence, really — the best-performing store gets fed more customers, automatically."

## 3. The price limit

[Point at the tolerance slider — currently 10%]

"Now, scores alone can't be the whole story, because in theory a store could just let
its service slip and still get customers if it happened to score well once. So there's
a second control — a price limit.

Right now it's set to 10 percent. The cheapest price on this part is 470. At 10 percent,
any store priced up to about 517 is still allowed to compete. All three of our stores
fall inside that — so right now, all three are in the running."

## 4. Run the customers through

[Set the count to 1,000. Click "Simulate purchases".]

"Let's actually test it. I'll send a thousand simulated customers through this rule and
see what happens.

[Charts render]

Okay — look at the bars. That's target next to what actually happened, side by side.
They're close. Really close.

And this line chart here — that's showing the split settling in as more customers come
through. It smooths out and locks onto the target line. That's not luck, and it's not
random — it's designed to converge exactly like that."

## 5. Tighten the price limit

[Drag slider down to 2%]

"Now let's make that price limit strict. I'll drag it down to 2 percent.

At that point, only a store priced very close to 470 is allowed to compete at all.
Store A and Store B are now priced outside that window — they're too expensive to
qualify. Store C is the only one left standing.

[Click "Simulate purchases" again]

So watch what happens when I run it again — Store C takes everything. A hundred
percent. And notice — the performance score didn't even come into it this time. Once
there's only one eligible store, price is the only thing that mattered."

## 6. Push it to zero

[Drag slider to 0%]

"One more step — let's push that limit all the way to zero.

At zero tolerance, only the single cheapest store can ever win, full stop. And you'll
notice the system actually throws up a warning here — because dropping to zero switches
off the fair-sharing behavior entirely, and that's a big enough deal that the business
needs to be told, not just let it happen quietly.

[Show "Keep 0%" / "Return to previous value" buttons]

It gives you the option to keep it or back out. I'll back out.

[Set back to 10%]

Back to 10 percent — our normal, sensible setting."

## 7. Change the buyer's location

[Type 08000-000 into the postcode field]

"Different angle now — instead of price, let's change *where* the buyer is.

I'll put in a postcode from a different part of the map.

Only Store C actually services this area. Store A and Store B drop out completely —
and this happens *before* price or score is even looked at. They're not being
outcompeted, they physically can't deliver here.

[Try 99999-999]

And if I go somewhere no store covers at all —

the system just says so, plainly. It doesn't guess, and it doesn't force a match that
isn't real.

[Set back to 01310-100]

Let's put that back to our normal test address."

## 8. The audit trail

[Navigate to Allocation Logs, click into any decision]

"This next part matters a lot, honestly — probably more than the numbers do.

Every single decision this system makes gets saved. If I open one up, I can see every
store that was in the running, their price, their score, what the lowest price was,
what the price limit worked out to, who got excluded and the exact reason why, and who
ultimately won.

And there's two versions of the explanation — one written in plain English, and one
that's more technical, for anyone who wants to dig in.

You asked for this to be something you could explain to a store owner or point to in a
dispute — not a black box. This is that. Nothing here is a mystery after the fact."

## 9. It doesn't flip-flop

[Back to dashboard, click Reload / Re-evaluate a few times]

"Last thing. If the same customer comes back a few minutes later — same visit — they
should keep seeing the same featured store. Not a different one every time they refresh.

[Click Reload / Re-evaluate two or three times]

I'll hit re-evaluate a few times here — watch the featured store. It's not moving. That
was a specific requirement, and it holds."

## 10. Wrap-up

"So, quick recap — cheapest doesn't automatically win. Stores that can actually serve
the customer split that traffic based on how well they've been performing. Price only
decides who's *allowed* to compete, not who wins outright. And every single decision is
logged and explainable.

There's a couple of extra things I didn't cover here — there's a Scenario Playbook with
eleven ready-made examples if you want to poke at edge cases on your own time, and the
Stores and Products pages have a plain-language glossary if any of the fields aren't
obvious.

That's it for this piece. If anything's unclear or you want to see a different angle on
it, just let me know and I'll record a follow-up — happy to go deeper on any one part of
this."

---

## If a take goes wrong mid-recording

Don't restart from scratch every time. Just pause, say the line again, and cut it in
editing — or if you're recording straight through with no edit pass, run:

```
php artisan atlas:reset --force
```

then reload the page and start clean from Step 2.

## Quick answers, in case you want to fold them in or save them for a follow-up

- **Why not just pick the cheapest?** They're all franchises of the same brand — a price
  war between them hurts the business as a whole. Sharing by performance avoids that.
- **Is the split random?** No — it's calculated so the real numbers track the target
  over time. Same inputs, same result, every time.
- **Can a store buy its way to the top?** No. Price only decides if a store's allowed to
  compete. After that, only the score decides the share.
