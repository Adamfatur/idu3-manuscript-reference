# Consistency of the printed summaries

Each figure below is calculated from the CSV files in `data/reported/`.
Those files transcribe the manuscript. This script does not read participant-level records.
A p-value or a Mann–Whitney U is copied as printed text, because the summary tables do not determine it.

A gap of about 0.01 is the distance between a full-precision formula and a figure printed to two decimals.
Larger gaps are collected at the end. The printed figures stay as printed.

## Learning gains

| Group | Printed g | g from the printed means | Printed category | Category of the recomputed g |
|---|---:|---:|---|---|
| Experimental | 0.72 | 0.722 | High | High |
| Control | 0.41 | 0.411 | Medium | Medium |

Printed gain difference: 0.31. Difference of the two printed gains: 0.31. Difference of the two recomputed gains: 0.311.

## t and d from the printed means and standard deviations

The t statistic is the equal-variance two-sample t. Cohen's d uses the same pooled standard deviation. Both groups have n = 28, so the degrees of freedom are 54.

| Quantity | Printed | From the printed means and SDs | Absolute gap |
|---|---:|---:|---:|
| Pre-test t | 0.58 | 0.543 | 0.037 |
| Post-test t | 7.21 | 6.762 | 0.448 |
| Cohen's d | 1.80 | 1.807 | 0.007 |

Printed pre-test p: 0.563 (p = 0.563). Not recomputed.
Printed post-test p: see t(54) = 7.21, p < 0.001. Not recomputed.

## Intrinsic Motivation Inventory

One-sample t against the midpoint 4, with n = 28 and the printed mean and standard deviation. df = 27.

| Subscale | Printed t | t from the printed mean and SD | Absolute gap |
|---|---:|---:|---:|
| Interest/Enjoyment | 10.84 | 10.835 | 0.005 |
| Perceived Competence | 8.21 | 8.199 | 0.011 |
| Effort/Importance | 6.65 | 6.640 | 0.010 |
| Pressure/Tension | -5.30 | -5.292 | 0.008 |

## Engagement

Drop-off reduction from the printed rates: (22.3 − 9.4) / 22.3 = 57.8%. The manuscript prints 58%.

The other rows of Table 6 are printed contrasts. They are not formulas of each other.

| Metric | Experimental | Control | Printed significance |
|---|---:|---:|---|
| module_completion_rate | 89.3 | 61.7 | p<.001 |
| mean_attempts_per_challenge | 1.82 | 1.34 | p<.01 |
| drop_off_rate_weeks_8_14 | 9.4 | 22.3 | Δ=58% |
| voluntary_retry | 78.6 | 44.2 | U=287.5; p<.001 |
| time_on_task | 12.4 | 7.8 | p<.001 |

Printed Mann–Whitney U for voluntary retry: 287.5. Not recomputed.

Week-by-week completion is not a table in the manuscript. The prose gives these anchors only.

| Group | Description | Printed value | How it is printed |
|---|---|---:|---|
| Experimental | completion at the start of the semester | 92% | approximate |
| Experimental | completion on every week of the semester | 83% | above this value |
| Control | completion at the start of the semester | 75% | approximate |
| Control | completion at week 14 | 28% | approximate |

## Tiers

Printed tier counts sum to 28.

| Tier | Printed count | Printed share | Share from the counts |
|---|---:|---:|---:|
| Intermediate | 14 | 50% | 50.0% |
| Advanced | 6 | 21.4% | 21.4% |
| Beginner | 4 | 14.3% | 14.3% |
| Apprentice | 2 | 7.1% | 7.1% |
| Expert | 1 | 3.6% | 3.6% |
| Elite | 1 | 3.6% | 3.6% |
| Legendary | 0 | 0% | 0.0% |

The two lowest tiers in Table 3 are Beginner and Apprentice (6 students). Students above those tiers: 22 / 28 = 78.6%.
The manuscript prints 71.4% for that statement. Absolute gap: 7.2 percentage points.

## Curriculum, sample, and decay

Table 4 challenge counts sum to 28.
Sample sizes sum to 56.
Experimental: n = 28, male + female = 28, prior SQL = none.
Control: n = 28, male + female = 28, prior SQL = none.

| Attempt | Printed D(n) | Printed points | 100 × D(n) |
|---:|---:|---:|---:|
| 1 | 1.00 | 100 | 100 |
| 2 | 0.90 | 90 | 90 |
| 3 | 0.75 | 75 | 75 |

## Larger gaps

- Post-test t is printed as 7.21. The equal-variance t from the printed means and standard deviations is 6.762.
- Pre-test t is printed as 0.58. The same formula gives 0.543.
- The tier sentence is printed as 71.4%. The printed tier counts give 78.6%.

Cohen's d, both normalized gains, the IMI t statistics, and the 58% drop-off reduction sit within ordinary two-decimal or whole-percent rounding of the formulas above.

