"""Dry run: build sample repositories from the template and inspect them. No Docker needed."""
import importlib.util, json, os, re, subprocess, sys, tempfile, unittest

REPO = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
SCRIPT = os.path.join(REPO, "scripts", "new-repo.py")
TEMPLATE = os.path.join(REPO, "template")
EM_DASH = chr(0x2014)
TOKEN_RE = re.compile(r"\{\{[A-Z][A-Z0-9_]*\}\}")

spec = importlib.util.spec_from_file_location("new_repo", SCRIPT)
new_repo = importlib.util.module_from_spec(spec)
spec.loader.exec_module(new_repo)


def make(target, extra=(), name="Sample Product", slug="sample-product"):
    return subprocess.run(
        [sys.executable, SCRIPT, "--name", name, "--slug", slug, "--pitch", "One line.",
         "--target", target, "--date", "2026-11-19", *extra],
        capture_output=True, text=True)


def all_paths(root):
    for base, dirs, files in os.walk(root):
        dirs[:] = [d for d in dirs if d != ".git"]
        for name in dirs + files:
            yield os.path.join(base, name)


def file_texts(root):
    for path in all_paths(root):
        if os.path.isfile(path):
            try:
                with open(path, encoding="utf-8") as f:
                    yield path, f.read()
            except UnicodeDecodeError:
                continue


def read(path):
    with open(path, encoding="utf-8") as f:
        return f.read()


class NewRepoTest(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        self.repo = os.path.join(self.tmp.name, "sample-product")

    def build(self, extra=("--no-git",)):
        r = make(self.repo, extra)
        self.assertEqual(r.returncode, 0, r.stdout + r.stderr)
        return r

    def test_1_no_tokens_left(self):
        self.build(["--modules", "Orders,Billing", "--no-git"])
        for path in all_paths(self.repo):
            self.assertIsNone(TOKEN_RE.search(os.path.basename(path)), path)
        for path, text in file_texts(self.repo):
            self.assertIsNone(TOKEN_RE.search(text), path)

    def test_2_module_list_has_the_modules(self):
        self.build(["--modules", "Orders,Billing", "--no-git"])
        text = read(os.path.join(self.repo, "modules.php"))
        self.assertIn("'namespace' => 'SampleProduct',", text)
        for module in ("Health", "Orders", "Billing"):
            self.assertIn(f"        '{module}',\n", text)
            folder = os.path.join(self.repo, "SampleProduct", module)
            self.assertTrue(os.path.isfile(os.path.join(folder, f"{module.lower()}-services.php")), module)
            self.assertTrue(os.path.isfile(os.path.join(folder, f"{module.lower()}-routes.php")), module)
            self.assertTrue(os.path.isdir(os.path.join(folder, "Migrations")), module)
        test = os.path.join(self.repo, "SampleProduct", "Orders", "Tests", "OrdersModuleTest.php")
        self.assertIn("namespace SampleProduct\\Orders\\Tests;", read(test))
        self.assertIn("'module.orders'", read(test))
        self.assertIn("'module.orders', true", read(os.path.join(self.repo, "SampleProduct", "Orders", "orders-services.php")))

    def test_3_default_module_list_is_health_only(self):
        self.build()
        text = read(os.path.join(self.repo, "modules.php"))
        self.assertEqual(re.findall(r"^        '(\w+)',$", text, re.M), ["Health"])

    def test_4_namespace_override(self):
        self.build(["--namespace", "Acme", "--no-git"])
        self.assertTrue(os.path.isdir(os.path.join(self.repo, "Acme", "Health")))
        self.assertIn('"Acme\\\\": "Acme/"', read(os.path.join(self.repo, "composer.json")))

    def test_5_composer_json_valid_with_quotes_in_pitch(self):
        r = make(self.repo, ["--no-git"], name="Quote Co")
        self.assertEqual(r.returncode, 0, r.stderr)
        shutil_target = os.path.join(self.tmp.name, "q")
        r = subprocess.run([sys.executable, SCRIPT, "--name", "Q", "--slug", "q", "--pitch", 'Say "hi" \\ there.',
                            "--target", shutil_target, "--no-git"], capture_output=True, text=True)
        self.assertEqual(r.returncode, 0, r.stderr)
        composer = json.loads(read(os.path.join(shutil_target, "composer.json")))
        self.assertEqual(composer["description"], 'Say "hi" \\ there.')
        self.assertEqual(composer["name"], "q/q")

    def test_6_lock_hash_matches_composer_json(self):
        self.build()
        composer = json.loads(read(os.path.join(self.repo, "composer.json")))
        lock = json.loads(read(os.path.join(self.repo, "composer.lock")))
        self.assertEqual(lock["content-hash"], new_repo.content_hash(composer))

    def test_7_recipe_shape(self):
        self.build()
        text = read(os.path.join(self.repo, ".factory", "recipe.yml"))
        lines = text.splitlines()
        self.assertEqual(lines[0], "version: 1")
        section = None
        checks = []
        for line in lines[1:]:
            if re.fullmatch(r"(setup|checks|teardown):", line):
                section = line[:-1]
            elif m := re.fullmatch(r"  - name: ([a-z-]+)", line):
                if section == "checks":
                    checks.append(m.group(1))
            else:
                self.assertRegex(line, r"    run: \S.*", line)
                self.assertIn("-p sample-product-factory -f docker-compose.factory.yml", line)
        self.assertEqual(checks, ["cs", "arkitect", "phpstan", "rector", "tests"])
        factory = read(os.path.join(self.repo, "docker-compose.factory.yml"))
        self.assertNotRegex(factory, r"^\s*(ports|container_name):", "factory compose must not bind ports or name containers")

    def test_8_no_private_leftovers(self):
        self.build()
        pattern = re.compile(r"codyssey|statamic|lemon|zernio|cloudflare", re.I)
        for path in all_paths(self.repo):
            self.assertIsNone(pattern.search(os.path.relpath(path, self.repo)), path)
        for path, text in file_texts(self.repo):
            if path.endswith("composer.lock"):
                continue
            self.assertIsNone(pattern.search(text), path)
        for private in (".env", "auth.json", "patches.lock.json"):
            self.assertFalse(os.path.exists(os.path.join(self.repo, private)), private)

    def test_9_no_em_dash(self):
        self.build()
        # The text files (.ai, .claude, openspec, docs, CLAUDE.md, AGENTS.md) have their own author
        # and carry upstream OpenSpec wording, so they are out of scope here.
        skip = {".ai", ".claude", "openspec", "docs", "CLAUDE.md", "AGENTS.md"}
        for root in (self.repo, TEMPLATE, os.path.join(REPO, "module-template"), os.path.join(REPO, "scripts")):
            for path in all_paths(root):
                if os.path.relpath(path, root).split(os.sep)[0] in skip:
                    continue
                self.assertNotIn(EM_DASH, os.path.basename(path), path)
                if os.path.isfile(path):
                    try:
                        text = read(path)
                    except UnicodeDecodeError:
                        continue
                    self.assertNotIn(EM_DASH, text, path)

    def test_10_git_first_commit(self):
        self.build(())
        log = subprocess.run(["git", "log", "--format=%s"], cwd=self.repo, capture_output=True, text=True)
        self.assertEqual(log.stdout.strip(), "Create the Sample Product repo from the Symfony template")

    def test_11_no_git_flag(self):
        self.build()
        self.assertFalse(os.path.exists(os.path.join(self.repo, ".git")))

    def test_12_refuses_non_empty_target(self):
        os.makedirs(self.repo)
        with open(os.path.join(self.repo, "keep.txt"), "w") as f:
            f.write("mine")
        r = make(self.repo)
        self.assertEqual(r.returncode, 2)
        self.assertEqual(os.listdir(self.repo), ["keep.txt"])

    def test_13_bad_input_exits_2(self):
        for extra, kwargs in ((["--modules", "orders"], {}), (["--modules", "Orders,orders"], {}),
                              (["--modules", "Health"], {}), (["--modules", "Framework"], {}),
                              (["--modules", "Symfony"], {}), (["--modules", "Doctrine"], {}), (["--modules", "Var"], {}),
                              (["--namespace", "Doctrine"], {}), (["--namespace", "Vendor"], {}),
                              (["--date", "yesterday"], {}), ([], {"slug": "Bad Slug"}),
                              ([], {"name": 'Has "quote"'}), ([], {"name": "Bob's Desk"}), ([], {"name": "Fish & Chips"}),
                              ([], {"name": "A<B"}), ([], {"name": "A>B"}),
                              ([], {"slug": "a-"}), ([], {"slug": "a--b"}), (["--namespace", "Framework"], {})):
            target = os.path.join(self.tmp.name, "bad")
            r = make(target, extra, **kwargs)
            self.assertEqual(r.returncode, 2, (extra, kwargs, r.stderr))
            self.assertFalse(os.path.exists(target), (extra, kwargs))

    def test_14_template_keeps_sibling_paths_out_of_scope(self):
        # The text files are written by another author; the generator must carry them over.
        self.build()
        for rel in ("CLAUDE.md", "AGENTS.md", ".claude", ".ai", "openspec", "docs"):
            self.assertTrue(os.path.exists(os.path.join(self.repo, rel)), rel)
        self.assertTrue(os.path.islink(os.path.join(self.repo, ".claude", "rules")))
        self.assertEqual(os.readlink(os.path.join(self.repo, ".claude", "rules")), "../.ai/rules")

    def test_16_workflows_sit_in_the_generated_repo_only(self):
        self.build()
        for name in ("cs", "arkitect", "phpstan", "rector", "phpunit"):
            self.assertTrue(os.path.isfile(os.path.join(self.repo, ".github", "workflows", f"{name}.yml")), name)
        self.assertFalse(os.path.exists(os.path.join(REPO, ".github")), "the template repo itself has no workflows")

    def test_17_no_laravel_leftovers(self):
        self.build()
        pattern = re.compile(r"Illuminate|artisan|Pint|Eloquent|Laravel|Larastan", re.I)
        skip = {".ai", ".claude/skills/openspec-apply-change", ".claude/skills/openspec-archive-change",
                ".claude/skills/openspec-explore", ".claude/skills/openspec-propose", ".claude/skills/openspec-sync-specs",
                "composer.lock"}
        for path, text in file_texts(self.repo):
            rel = os.path.relpath(path, self.repo)
            if any(rel == k or rel.startswith(k + os.sep) for k in skip):
                continue
            self.assertIsNone(pattern.search(text), rel)

    def test_18_module_services_and_routes_are_imported_from_modules_php(self):
        self.build(["--modules", "Orders", "--no-git"])
        for name in ("services", "routes"):
            text = read(os.path.join(self.repo, "config", f"{name}.php"))
            self.assertIn("modules.php", text)
            self.assertIn(f"-{name}.php", text)

    def test_15_template_gitignore_ignores_worktrees(self):
        self.assertIn("/.claude/worktrees", read(os.path.join(TEMPLATE, ".gitignore")))


if __name__ == "__main__":
    unittest.main()
