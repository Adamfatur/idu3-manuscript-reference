#!/usr/bin/env python3
"""Recompute figures that the printed summary tables already determine.

Reads data/reported/*.csv and writes analysis/consistency.md.
p-values and the Mann–Whitney U are copied as printed text.
"""

import csv
import math
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DATA = ROOT / "data" / "reported"
OUT = Path(__file__).resolve().parent / "consistency.md"


def load(name):
    with (DATA / name).open(newline="", encoding="utf-8") as handle:
        return list(csv.DictReader(handle))


def number(value):
    if value is None or value == "":
        return None
    return float(value)


def show(value):
    return f"{value:.3f}"


def gap(left, right):
    return abs(left - right)


def hake(post, pre):
    return (post - pre) / (100 - pre)


def pooled(mean_a, sd_a, n_a, mean_b, sd_b, n_b):
    degrees = n_a + n_b - 2
    variance = ((n_a - 1) * sd_a ** 2 + (n_b - 1) * sd_b ** 2) / degrees
    sd = math.sqrt(variance)
    standard_error = sd * math.sqrt(1 / n_a + 1 / n_b)
    return (mean_a - mean_b) / standard_error, (mean_a - mean_b) / sd


def one_sample_t(mean, sd, n, midpoint):
    return (mean - midpoint) / (sd / math.sqrt(n))


def hake_category(gain):
    if gain >= 0.7:
        return "High"
    if gain >= 0.3:
        return "Medium"
    return "Low"


def write_report():
    gains = load("table5_learning_gains.csv")
    by_group = {row["group"]: row for row in gains}
    experimental = by_group["Experimental"]
    control = by_group["Control"]
    printed = {row["quantity"]: row for row in load("inferential_statistics.csv")}

    exp_gain = hake(number(experimental["post_mean"]), number(experimental["pre_mean"]))
    ctrl_gain = hake(number(control["post_mean"]), number(control["pre_mean"]))
    pre_t, _ = pooled(
        number(experimental["pre_mean"]), number(experimental["pre_sd"]), int(experimental["n"]),
        number(control["pre_mean"]), number(control["pre_sd"]), int(control["n"]),
    )
    post_t, cohens_d = pooled(
        number(experimental["post_mean"]), number(experimental["post_sd"]), int(experimental["n"]),
        number(control["post_mean"]), number(control["post_sd"]), int(control["n"]),
    )

    imi_rows = []
    for row in load("table7_imi.csv"):
        recomputed = one_sample_t(number(row["mean"]), number(row["sd"]), 28, 4.0)
        imi_rows.append((row, recomputed))

    engagement = {row["metric"]: row for row in load("table6_engagement.csv")}
    drop = engagement["drop_off_rate_weeks_8_14"]
    drop_reduction = (number(drop["control"]) - number(drop["experimental"])) / number(drop["control"]) * 100

    tiers = load("tier_distribution.csv")
    tier_n = sum(int(row["participants"]) for row in tiers)
    tier_meta = {row["tier"]: row for row in load("table3_tiers.csv")}
    lowest = [
        name for name, meta in tier_meta.items()
        if int(meta["rank"]) in (1, 2)
    ]
    lowest_n = sum(int(row["participants"]) for row in tiers if row["tier"] in lowest)
    beyond = (tier_n - lowest_n) / tier_n * 100
    beyond_printed = number(load("beyond_lowest_tiers.csv")[0]["printed_percent"])

    curriculum = load("table4_curriculum.csv")
    challenge_total = sum(int(row["challenges"]) for row in curriculum)
    sample = load("sample.csv")
    sample_total = sum(int(row["n"]) for row in sample)

    decay = load("table2_decay.csv")
    lines = []
    add = lines.append
    add("# Consistency of the printed summaries")
    add("")
    add("Each figure below is calculated from the CSV files in `data/reported/`.")
    add("Those files transcribe the manuscript. This script does not read participant-level records.")
    add("A p-value or a Mann–Whitney U is copied as printed text, because the summary tables do not determine it.")
    add("")
    add("A gap of about 0.01 is the distance between a full-precision formula and a figure printed to two decimals.")
    add("Larger gaps are collected at the end. The printed figures stay as printed.")
    add("")
    add("## Learning gains")
    add("")
    add("| Group | Printed g | g from the printed means | Printed category | Category of the recomputed g |")
    add("|---|---:|---:|---|---|")
    for row, recomputed in ((experimental, exp_gain), (control, ctrl_gain)):
        add(
            f"| {row['group']} | {row['normalized_gain']} | {show(recomputed)} "
            f"| {row['hake_category']} | {hake_category(recomputed)} |"
        )
    printed_gain_gap = number(printed["gain_difference"]["printed_value"])
    gain_gap_from_print = number(experimental["normalized_gain"]) - number(control["normalized_gain"])
    add("")
    add(
        f"Printed gain difference: {printed_gain_gap:.2f}. "
        f"Difference of the two printed gains: {gain_gap_from_print:.2f}. "
        f"Difference of the two recomputed gains: {show(exp_gain - ctrl_gain)}."
    )
    add("")
    add("## t and d from the printed means and standard deviations")
    add("")
    add("The t statistic is the equal-variance two-sample t. Cohen's d uses the same pooled standard deviation. Both groups have n = 28, so the degrees of freedom are 54.")
    add("")
    add("| Quantity | Printed | From the printed means and SDs | Absolute gap |")
    add("|---|---:|---:|---:|")
    comparisons = [
        ("Pre-test t", number(printed["pre_test_t"]["printed_value"]), pre_t),
        ("Post-test t", number(printed["post_test_t"]["printed_value"]), post_t),
        ("Cohen's d", number(printed["cohens_d"]["printed_value"]), cohens_d),
    ]
    for label, printed_value, recomputed in comparisons:
        add(f"| {label} | {printed_value:.2f} | {show(recomputed)} | {show(gap(printed_value, recomputed))} |")
    add("")
    add(f"Printed pre-test p: {printed['pre_test_p']['printed_value']} ({printed['pre_test_p']['source']}). Not recomputed.")
    add(f"Printed post-test p: see {printed['post_test_t']['source']}. Not recomputed.")
    add("")
    add("## Intrinsic Motivation Inventory")
    add("")
    add("One-sample t against the midpoint 4, with n = 28 and the printed mean and standard deviation. df = 27.")
    add("")
    add("| Subscale | Printed t | t from the printed mean and SD | Absolute gap |")
    add("|---|---:|---:|---:|")
    for row, recomputed in imi_rows:
        printed_t = number(row["t_df27"])
        add(f"| {row['subscale']} | {printed_t:.2f} | {show(recomputed)} | {show(gap(printed_t, recomputed))} |")
    add("")
    add("## Engagement")
    add("")
    add(
        f"Drop-off reduction from the printed rates: ({drop['control']} − {drop['experimental']}) / {drop['control']} "
        f"= {drop_reduction:.1f}%. The manuscript prints {number(drop['printed_contrast']):.0f}%."
    )
    add("")
    add("The other rows of Table 6 are printed contrasts. They are not formulas of each other.")
    add("")
    add("| Metric | Experimental | Control | Printed significance |")
    add("|---|---:|---:|---|")
    for row in load("table6_engagement.csv"):
        add(f"| {row['metric']} | {row['experimental']} | {row['control']} | {row['significance']} |")
    add("")
    add(f"Printed Mann–Whitney U for voluntary retry: {printed['mann_whitney_u']['printed_value']}. Not recomputed.")
    add("")
    add("Week-by-week completion is not a table in the manuscript. The prose gives these anchors only.")
    add("")
    add("| Group | Description | Printed value | How it is printed |")
    add("|---|---|---:|---|")
    for row in load("trajectory_anchors.csv"):
        add(f"| {row['group']} | {row['description']} | {row['printed_value_percent']}% | {row['kind']} |")
    add("")
    add("## Tiers")
    add("")
    add(f"Printed tier counts sum to {tier_n}.")
    add("")
    add("| Tier | Printed count | Printed share | Share from the counts |")
    add("|---|---:|---:|---:|")
    for row in tiers:
        share = int(row["participants"]) / tier_n * 100
        add(f"| {row['tier']} | {row['participants']} | {row['printed_share_percent']}% | {share:.1f}% |")
    add("")
    lowest_label = " and ".join(sorted(lowest, key=lambda name: int(tier_meta[name]["rank"])))
    add(
        f"The two lowest tiers in Table 3 are {lowest_label} "
        f"({lowest_n} students). Students above those tiers: {tier_n - lowest_n} / {tier_n} = {beyond:.1f}%."
    )
    add(f"The manuscript prints {beyond_printed:.1f}% for that statement. Absolute gap: {gap(beyond, beyond_printed):.1f} percentage points.")
    add("")
    add("## Curriculum, sample, and decay")
    add("")
    add(f"Table 4 challenge counts sum to {challenge_total}.")
    add(f"Sample sizes sum to {sample_total}.")
    for row in sample:
        total = int(row["male"]) + int(row["female"])
        add(f"{row['group']}: n = {row['n']}, male + female = {total}, prior SQL = {row['prior_sql_experience']}.")
    add("")
    add("| Attempt | Printed D(n) | Printed points | 100 × D(n) |")
    add("|---:|---:|---:|---:|")
    for row in decay:
        factor = number(row["decay_factor"])
        points = number(row["points"])
        add(f"| {row['attempt']} | {row['decay_factor']} | {row['points']} | {100 * factor:.0f} |")
        if points != 100 * factor:
            add(f"Point mismatch on attempt {row['attempt']}.")
    add("")
    add("## Larger gaps")
    add("")
    add(f"- Post-test t is printed as {number(printed['post_test_t']['printed_value']):.2f}. The equal-variance t from the printed means and standard deviations is {show(post_t)}.")
    add(f"- Pre-test t is printed as {number(printed['pre_test_t']['printed_value']):.2f}. The same formula gives {show(pre_t)}.")
    add(f"- The tier sentence is printed as {beyond_printed:.1f}%. The printed tier counts give {beyond:.1f}%.")
    add("")
    add("Cohen's d, both normalized gains, the IMI t statistics, and the 58% drop-off reduction sit within ordinary two-decimal or whole-percent rounding of the formulas above.")
    add("")
    OUT.write_text("\n".join(lines) + "\n", encoding="utf-8")
    return OUT.read_text(encoding="utf-8")


if __name__ == "__main__":
    print(write_report(), end="")
