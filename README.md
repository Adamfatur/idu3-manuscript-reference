# iDu 3.0 manuscript reference

Reference code and printed summary tables for the iDu 3.0 manuscript.

The code follows Section 3: the persona prompt (Listing 1), table prefixing and blocked DDL (Listing 2), decay points and the seven tiers (Listing 3, Tables 2 and 3), and reward allocation (Algorithm 1). The CSV files transcribe the summary figures printed in Sections 4 and 5. `analysis/check_reported_figures.py` recomputes the quantities those summaries already determine, including Hake gain, the equal-variance t, Cohen's d, the IMI one-sample t, the drop-off reduction, tier shares, and the curriculum total.

This repository stops at those printed summaries. It has no pre-test file, post-test file, attempt log, or IMI response file. p-values and the Mann–Whitney U are stored as printed text. A summary table does not determine them, so the script does not recalculate them.

`idu3-sql-evaluation` is a separate repository: an anonymized export from one class. Nothing in this repository is derived from that export.

## Layout

```
code/app/Services/AffectivePersona.php     Listing 1 prompt, temperature 0.1, maxOutputTokens 2048
code/app/Services/SandboxSQLService.php    Listing 2 prefix and blocked DDL
code/app/Helpers/TierHelper.php            Listing 3 decay points and tier titles
code/app/Services/RewardAllocator.php      Algorithm 1
data/reported/                             Printed tables and counts
analysis/check_reported_figures.py         Writes analysis/consistency.md
```

The manuscript names Laravel 12, PHP 8.2, MySQL 8.0, and Google Gemini 2.0 Flash. `AffectivePersona` records that model name and the request body. It does not call the API, and the manuscript does not print an API model slug.

On the fast path, Algorithm 1 approves a query whose output matches and whose known tables match, then applies decay. That branch does not set feedback text. A query error, or an LLM rejection, returns score 0 and does not move the tier. Decay factors are defined for attempts 1, 2, and 3.

## Run

```bash
php tests/reference_test.php
python3 analysis/check_reported_figures.py
python3 tests/test_repository.py
```

PHP 8.2 or newer, and Python 3.9 or newer. No third-party packages.

## Printed figures the formulas do not return

`analysis/consistency.md` records the arithmetic. The printed figures are left unchanged. In the initial check:

- Post-test t is printed as 7.21. The equal-variance t from the printed means and standard deviations (81.2±10.3 and 59.4±13.6, n = 28) is 6.762.
- Pre-test t is printed as 0.58. The same formula gives 0.543.
- The tier sentence is printed as 71.4%. Beginner 4 and Apprentice 2 out of 28 give 78.6% above those two tiers.

Cohen's d 1.80, gains 0.72 and 0.41, the four IMI t statistics, and the 58% drop-off reduction sit within ordinary rounding of the same formulas.

## License

MIT. See `LICENSE`.
