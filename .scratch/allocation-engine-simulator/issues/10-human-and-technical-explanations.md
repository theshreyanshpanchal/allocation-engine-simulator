# 10: Human & technical explanations

**What to build:** Every allocation decision can explain itself two ways: a plain-English paragraph a non-technical person understands, and a structured technical breakdown a developer can check line by line. Both appear on the dashboard's "Why did this store win?" panel and on the decision detail screen.

**Blocked by:** 05, 09.

**Status:** done

- [x] An `AllocationExplanationService` takes a decision (or the live dashboard evaluation) and returns both explanation forms.
- [x] Business explanation: names the eligible stores, the active tolerance, why excluded stores were excluded (spec §38 wording for price exclusion), and why the winner's score gives it its share.
- [x] Technical explanation: eligibility list, lowest price, tolerance, price ceiling, per-store price-guard pass/fail, scores, total score, target-weight fractions, current allocation state, selected store.
- [x] The dashboard "Why did this store win?" panel renders both for the current single evaluation.
- [x] The decision detail screen (ticket 09) renders both for that stored decision.
- [x] For a fully-excluded store the explanation states the price, lowest eligible price, ceiling, and that it was excluded by the price guard.
- [x] Wording is generated from the recorded inputs, so it stays consistent with the audited numbers.

## Comments

- `AllocationExplanationService` has two entry points — `forEvaluation(AllocationEvaluation, ?SellerEvaluation $winner)` and `forDecision(AllocationDecision)` — that normalise into one fact list and share a private `build()`, so live and stored explanations read identically.
- Returns `AllocationExplanation` (`business` paragraph, `technicalLines` list of label/value pairs → `technicalText()`, `perSellerNotes` keyed by store id). Business text adapts to: multi-participant (score split, "highest score of N", target %), single participant ("only store inside the configured X% tolerance"), and winnerless (uses the evaluation's own reason). Price-guard exclusions get the spec §38 sentence; pre-price exclusions say "excluded before price was considered: …".
- Technical block mirrors spec §37: Eligibility / Lowest price / Tolerance / Price ceiling / per-store Price-guard verdicts / Scores / Total score / Target weights (`5 / 12 = 41.7%`) / Selected.
- Added `money()` helper. New `x-explanation` component (business paragraph + `<details>` technical `<pre>`), used by the dashboard "Why did this store win?" panel (Section G) and the decision detail page ("Why this decision"). Excluded seller cards on the detail page now show the §38 note instead of the bare reason.
- Coverage: `tests/Feature/AllocationExplanationTest.php` — 7 tests (default both-registers, §38 price wording + 2 perSellerNotes, pre-price exclusion note, winnerless fallback, stored-decision consistency, dashboard panel render, detail-page render). Full suite 76 passed.
