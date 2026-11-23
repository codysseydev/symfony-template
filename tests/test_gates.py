"""Gate run: generate repositories whose namespaces sort before Doctrine, between Framework and
Symfony, and after Tests, then run the whole .factory/recipe.yml (setup, five checks, teardown) on each.

Needs Docker and about a minute per repository, so it only runs when SYMFONY_TEMPLATE_GATES=1:
    SYMFONY_TEMPLATE_GATES=1 python3 -m unittest tests.test_gates -v"""
import os, re, subprocess, sys, tempfile, time, unittest

REPO = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
SCRIPT = os.path.join(REPO, "scripts", "new-repo.py")

CASES = [
    ("Aardvark Works", "aardvark-works", "Clients,Packs"),
    ("Session Pack Tracker", "session-pack-tracker", "Clients,Packs,Bookings"),
    ("Zebra Desk", "zebra-desk", "Tickets,Agents"),
]


def parse_recipe(path):
    """Reads the restricted recipe shape: sections, `- name:` lines and one-line `run:` lines."""
    steps, section, name = [], None, None
    with open(path, encoding="utf-8") as f:
        for line in f.read().splitlines():
            if re.fullmatch(r"(setup|checks|teardown):", line):
                section = line[:-1]
            elif m := re.fullmatch(r"  - name: (.+)", line):
                name = m.group(1)
            elif m := re.fullmatch(r"    run: (.+)", line):
                steps.append((section, name, m.group(1)))
    return steps


@unittest.skipUnless(os.environ.get("SYMFONY_TEMPLATE_GATES") == "1", "set SYMFONY_TEMPLATE_GATES=1 to run the Docker gate run")
class GateRunTest(unittest.TestCase):
    def run_case(self, name, slug, modules):
        with tempfile.TemporaryDirectory() as tmp:
            repo = os.path.join(tmp, slug)
            cmd = [sys.executable, SCRIPT, "--name", name, "--slug", slug, "--pitch", "Template self-test.", "--target", repo]
            if modules:
                cmd += ["--modules", modules]
            made = subprocess.run(cmd, capture_output=True, text=True)
            self.assertEqual(made.returncode, 0, made.stdout + made.stderr)
            failures, table = [], []
            try:
                for section, step, command in parse_recipe(os.path.join(repo, ".factory", "recipe.yml")):
                    if section == "teardown":
                        continue
                    start = time.time()
                    r = subprocess.run(command, shell=True, cwd=repo, capture_output=True, text=True)
                    table.append(f"{slug} {section}/{step}: exit {r.returncode} in {time.time() - start:.1f}s")
                    if r.returncode != 0:
                        failures.append(f"{section}/{step}\n{(r.stdout + r.stderr)[-2000:]}")
                        if section == "setup":
                            break
            finally:
                for section, step, command in parse_recipe(os.path.join(repo, ".factory", "recipe.yml")):
                    if section == "teardown":
                        subprocess.run(command, shell=True, cwd=repo, capture_output=True, text=True)
                # vendor/ is owned by the container user on some hosts; remove it through Docker-free chmod
                subprocess.run(["chmod", "-R", "u+w", repo], capture_output=True)
            print("\n".join(table))
            self.assertEqual(failures, [], "\n".join(failures))

    def test_aardvark_works_namespace_sorts_before_doctrine(self):
        self.run_case(*CASES[0])

    def test_session_pack_tracker_namespace_sorts_between_framework_and_symfony(self):
        self.run_case(*CASES[1])

    def test_zebra_desk_namespace_sorts_after_tests(self):
        self.run_case(*CASES[2])


if __name__ == "__main__":
    unittest.main()
