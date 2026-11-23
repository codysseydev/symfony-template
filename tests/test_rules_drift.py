"""Rule copies: template/.ai/rules/ must match the Kanon references in codysseydev/codyssey-skills.

The drift test runs `scripts/sync-rules.py --check` against current codyssey-skills main (or the
checkout in KANON_SKILLS_DIR) and needs network access when no checkout is given. The unit tests
use a fake skills directory and need neither.
    python3 -m unittest tests.test_rules_drift -v"""
import importlib.util, json, os, re, subprocess, sys, tempfile, unittest

REPO = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
SCRIPT = os.path.join(REPO, "scripts", "sync-rules.py")
MANIFEST = os.path.join(REPO, "scripts", "rules-manifest.json")
INDEX = os.path.join(REPO, "template", ".ai", "rules", "index.md")

spec = importlib.util.spec_from_file_location("sync_rules", SCRIPT)
sync_rules = importlib.util.module_from_spec(spec)
spec.loader.exec_module(sync_rules)


class RulesDriftTest(unittest.TestCase):
    def test_copies_match_codyssey_skills(self):
        run = subprocess.run([sys.executable, SCRIPT, "--check"], capture_output=True, text=True)
        self.assertEqual(0, run.returncode, run.stdout + run.stderr)

    def test_index_has_one_row_per_manifest_entry(self):
        with open(MANIFEST, encoding="utf-8") as f:
            files = [r["file"] for r in json.load(f)["rules"]]
        with open(INDEX, encoding="utf-8") as f:
            rows = re.findall(r"\| \.ai/rules/([^ |]+) \|", f.read())
        self.assertEqual(sorted(files), sorted(rows), "index.md rows and manifest entries differ")
        self.assertEqual(len(rows), len(set(rows)), "index.md lists a rule file twice")


class SyncRulesTest(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        self.skills = os.path.join(self.tmp.name, "skills")
        self.rules = os.path.join(self.tmp.name, "rules")
        os.makedirs(os.path.join(self.skills, "kanon"))
        os.makedirs(self.rules)
        self.manifest = [
            {"file": "a.md", "source": "kanon/a.md", "paths": ["{{PRODUCT_NAMESPACE}}/**", "Framework/**"]},
            {"file": "b.md", "source": "kanon/b.md", "paths": ["tests/**"]},
        ]
        self.write(self.skills, "kanon/a.md", "# A\n\nBody A.\n")
        self.write(self.skills, "kanon/b.md", "# B\n\nBody B.\n")
        self.write(self.rules, "index.md", "# Index\n")
        for r in self.manifest:
            with open(os.path.join(self.skills, r["source"]), encoding="utf-8") as f:
                body = f.read()
            self.write(self.rules, r["file"], sync_rules.render(r["paths"], body))

    def write(self, root, name, text):
        with open(os.path.join(root, name), "w", encoding="utf-8", newline="") as f:
            f.write(text)

    def check(self):
        return sync_rules.sync(self.skills, self.rules, self.manifest, check=True)

    def test_clean(self):
        self.assertEqual([], self.check())

    def test_frontmatter_shape(self):
        text = open(os.path.join(self.rules, "a.md"), encoding="utf-8").read()
        self.assertEqual('---\npaths:\n  - "{{PRODUCT_NAMESPACE}}/**"\n  - "Framework/**"\n---\n\n# A\n\nBody A.\n', text)

    def test_missing_copy(self):
        os.remove(os.path.join(self.rules, "b.md"))
        self.assertEqual(["b.md: missing"], self.check())

    def test_changed_body(self):
        self.write(self.skills, "kanon/a.md", "# A\n\nNew body.\n")
        problems = self.check()
        self.assertEqual(1, len(problems))
        self.assertTrue(problems[0].startswith("a.md: differs"))

    def test_changed_globs(self):
        self.manifest[1]["paths"] = ["tests/**", "database/**"]
        problems = self.check()
        self.assertEqual(1, len(problems))
        self.assertTrue(problems[0].startswith("b.md: differs"))

    def test_stray_file(self):
        self.write(self.rules, "extra.md", "# Extra\n")
        self.assertEqual(["extra.md: in template/.ai/rules/ but not in the manifest"], self.check())

    def test_check_writes_nothing(self):
        self.write(self.skills, "kanon/a.md", "# A\n\nNew body.\n")
        target = os.path.join(self.rules, "a.md")
        with open(target, encoding="utf-8") as f:
            before = f.read()
        self.check()
        with open(target, encoding="utf-8") as f:
            self.assertEqual(before, f.read())

    def test_sync_repairs_drift_and_missing_copy(self):
        self.write(self.skills, "kanon/a.md", "# A\n\nNew body.\n")
        os.remove(os.path.join(self.rules, "b.md"))
        sync_rules.sync(self.skills, self.rules, self.manifest)
        self.assertEqual([], self.check())

    def test_missing_source_is_reported(self):
        os.remove(os.path.join(self.skills, "kanon", "b.md"))
        problems = self.check()
        self.assertEqual(1, len(problems))
        self.assertTrue(problems[0].startswith("b.md: source kanon/b.md not found"))


if __name__ == "__main__":
    unittest.main()
