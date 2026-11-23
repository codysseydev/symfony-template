#!/usr/bin/env python3
"""Keep template/.ai/rules/ in line with the Kanon references in codysseydev/codyssey-skills.

Each rule copy is a `paths:` frontmatter block, a blank line, then the body of its source file.
scripts/rules-manifest.json lists every copy: its file name, its source path inside
codyssey-skills and its globs. Globs may hold {{PRODUCT_NAMESPACE}}; tokens stay as written.

    python3 scripts/sync-rules.py            write every copy
    python3 scripts/sync-rules.py --check    write nothing, exit 1 when a copy drifted

Source of the references, first match wins:
    --skills <dir>, then the KANON_SKILLS_DIR environment variable, then a shallow clone of
    https://github.com/codysseydev/codyssey-skills (main) in .cache/codyssey-skills, fetched and
    reset to origin/main on every run."""
import argparse, json, os, subprocess, sys

REPO = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
MANIFEST = os.path.join(os.path.dirname(os.path.abspath(__file__)), "rules-manifest.json")
RULES = os.path.join(REPO, "template", ".ai", "rules")
CACHE = os.path.join(REPO, ".cache", "codyssey-skills")
SKILLS_URL = "https://github.com/codysseydev/codyssey-skills"


def render(paths, body):
    head = "---\npaths:\n" + "".join(f'  - "{p}"\n' for p in paths) + "---\n\n"
    return head + body


def load_manifest(path=MANIFEST):
    with open(path, encoding="utf-8") as f:
        return json.load(f)["rules"]


def git(*args, cwd=None):
    subprocess.run(["git", *args], cwd=cwd, check=True, stdout=subprocess.DEVNULL)


def skills_dir(arg):
    given = arg or os.environ.get("KANON_SKILLS_DIR")
    if given:
        return os.path.abspath(given)
    if os.path.isdir(os.path.join(CACHE, ".git")):
        git("fetch", "--quiet", "--depth", "1", "origin", "main", cwd=CACHE)
        git("reset", "--quiet", "--hard", "FETCH_HEAD", cwd=CACHE)
    else:
        os.makedirs(os.path.dirname(CACHE), exist_ok=True)
        git("clone", "--quiet", "--depth", "1", "--branch", "main", SKILLS_URL, CACHE)
    return CACHE


def read(path):
    with open(path, encoding="utf-8") as f:
        return f.read()


def sync(skills, rules_dir=RULES, manifest=None, check=False):
    """Returns the list of problems. Without `check`, also writes the copies that differ."""
    rules = load_manifest() if manifest is None else manifest
    problems = []
    for rule in rules:
        source = os.path.join(skills, rule["source"])
        target = os.path.join(rules_dir, rule["file"])
        if not os.path.isfile(source):
            problems.append(f"{rule['file']}: source {rule['source']} not found in {skills}")
            continue
        want = render(rule["paths"], read(source))
        have = read(target) if os.path.isfile(target) else None
        if have == want:
            continue
        problems.append(f"{rule['file']}: " + ("missing" if have is None else "differs from " + rule["source"]))
        if not check:
            os.makedirs(rules_dir, exist_ok=True)
            with open(target, "w", encoding="utf-8", newline="") as f:
                f.write(want)
    known = {r["file"] for r in rules} | {"index.md"}
    if os.path.isdir(rules_dir):
        for name in sorted(os.listdir(rules_dir)):
            if name not in known:
                problems.append(f"{name}: in template/.ai/rules/ but not in the manifest")
    return problems


def main(argv=None):
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--check", action="store_true", help="write nothing, exit 1 when a copy drifted")
    ap.add_argument("--skills", help="path to a codyssey-skills checkout")
    args = ap.parse_args(argv)
    problems = sync(skills_dir(args.skills), check=args.check)
    if args.check:
        if problems:
            print("Rule copies drifted. Run: python3 scripts/sync-rules.py", file=sys.stderr)
            print("\n".join(problems), file=sys.stderr)
            return 1
        print("Rule copies match codyssey-skills.")
        return 0
    for p in problems:
        print("updated " + p if "not in the manifest" not in p and "not found" not in p else "WARNING " + p)
    print(f"{len(load_manifest())} rule copies in line with codyssey-skills.")
    return 1 if any("not in the manifest" in p or "not found" in p for p in problems) else 0


if __name__ == "__main__":
    sys.exit(main())
