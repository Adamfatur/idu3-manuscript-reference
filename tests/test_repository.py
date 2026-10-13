#!/usr/bin/env python3
"""Checks the reference code and the summary-table transcription."""

import csv
import subprocess
import sys
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
BANNED_HEADERS = {
    "participant",
    "student_id",
    "email",
    "nim",
    "pre_score",
    "post_score",
    "imi_item",
    "feedback",
    "query",
}


class RepositoryTest(unittest.TestCase):
    def test_reference_code(self):
        completed = subprocess.run(
            ["php", "tests/reference_test.php"],
            cwd=ROOT,
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(completed.returncode, 0, completed.stderr + completed.stdout)
        self.assertIn("All reference checks passed", completed.stdout)

    def test_summary_check_keeps_printed_figures(self):
        completed = subprocess.run(
            [sys.executable, "analysis/check_reported_figures.py"],
            cwd=ROOT,
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        report = (ROOT / "analysis" / "consistency.md").read_text(encoding="utf-8")
        self.assertIn("7.21", report)
        self.assertIn("6.762", report)
        self.assertIn("71.4%", report)
        self.assertIn("78.6%", report)
        inferential = ROOT / "data" / "reported" / "inferential_statistics.csv"
        with inferential.open(encoding="utf-8", newline="") as handle:
            rows = list(csv.DictReader(handle))
        posted = {row["quantity"]: row["printed_value"] for row in rows}
        self.assertEqual(posted["post_test_t"], "7.21")
        self.assertEqual(posted["pre_test_t"], "0.58")

    def test_reported_tables_have_no_participant_columns(self):
        for path in (ROOT / "data" / "reported").glob("*.csv"):
            with path.open(newline="", encoding="utf-8") as handle:
                reader = csv.reader(handle)
                header = [cell.strip().lower() for cell in next(reader)]
            self.assertTrue(set(header).isdisjoint(BANNED_HEADERS), path.name)


if __name__ == "__main__":
    unittest.main()
