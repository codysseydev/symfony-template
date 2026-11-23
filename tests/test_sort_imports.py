"""Unit tests for the import sorter in new-repo.py (mirrors PHP CS Fixer's ordered_imports)."""
import importlib.util, os, unittest

REPO = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
spec = importlib.util.spec_from_file_location("new_repo", os.path.join(REPO, "scripts", "new-repo.py"))
new_repo = importlib.util.module_from_spec(spec)
spec.loader.exec_module(new_repo)
sort = new_repo.sort_imports

HEAD = "<?php\n\ndeclare(strict_types=1);\n\nnamespace X;\n\n"


class SortImportsTest(unittest.TestCase):
    def test_namespace_after_symfony(self):
        before = HEAD + "use Zebra\\Health\\Thing;\nuse Symfony\\Component\\Routing\\Route;\n\nfinal class A {}\n"
        after = HEAD + "use Symfony\\Component\\Routing\\Route;\nuse Zebra\\Health\\Thing;\n\nfinal class A {}\n"
        self.assertEqual(sort(before), after)

    def test_namespace_before_doctrine(self):
        before = HEAD + "use Doctrine\\ORM\\Mapping as ORM;\nuse Aardvark\\Health\\Thing;\n"
        after = HEAD + "use Aardvark\\Health\\Thing;\nuse Doctrine\\ORM\\Mapping as ORM;\n"
        self.assertEqual(sort(before), after)

    def test_namespace_between_framework_and_symfony(self):
        before = HEAD + "use Symfony\\Component\\Routing\\Route;\nuse Session\\Health\\Thing;\nuse Framework\\Bus\\QueryBus;\n"
        after = HEAD + "use Framework\\Bus\\QueryBus;\nuse Session\\Health\\Thing;\nuse Symfony\\Component\\Routing\\Route;\n"
        self.assertEqual(sort(before), after)

    def test_namespace_after_tests(self):
        before = HEAD + "use Zebra\\Health\\Thing;\nuse Tests\\Architecture\\ProductModules;\n"
        after = HEAD + "use Tests\\Architecture\\ProductModules;\nuse Zebra\\Health\\Thing;\n"
        self.assertEqual(sort(before), after)

    def test_case_insensitive_and_backslash_as_space(self):
        # "Foo Bar" (Foo\\Bar) sorts before "FooBar" because a space sorts before a letter.
        before = HEAD + "use FooBar;\nuse Foo\\Bar;\nuse foo\\Aaa;\n"
        after = HEAD + "use foo\\Aaa;\nuse Foo\\Bar;\nuse FooBar;\n"
        self.assertEqual(sort(before), after)

    def test_underscore_sorts_before_lowercase_letters(self):
        # strcasecmp lowercases first, so "_" (0x5F) sorts before "a" (0x61).
        before = HEAD + "use A\\Ab;\nuse A\\A_b;\n"
        after = HEAD + "use A\\A_b;\nuse A\\Ab;\n"
        self.assertEqual(sort(before), after)

    def test_groups_are_class_then_function_then_const(self):
        before = HEAD + "use function A\\fn;\nuse B\\Klass;\nuse const C\\VALUE;\nuse A\\Other;\n"
        after = HEAD + "use A\\Other;\nuse B\\Klass;\nuse function A\\fn;\nuse const C\\VALUE;\n"
        self.assertEqual(sort(before), after)

    def test_an_alias_does_not_take_part_in_the_order(self):
        before = HEAD + "use B\\Thing as A;\nuse A\\Thing as Z;\n"
        after = HEAD + "use A\\Thing as Z;\nuse B\\Thing as A;\n"
        self.assertEqual(sort(before), after)

    def test_blank_lines_inside_a_run_keep_their_place(self):
        before = HEAD + "use B\\X;\n\nuse A\\X;\n"
        self.assertEqual(sort(before), HEAD + "use A\\X;\n\nuse B\\X;\n")

    def test_separate_blocks_sort_independently(self):
        before = "use B\\X;\nuse A\\X;\n\nclass K {}\n\nuse D\\X;\nuse C\\X;\n"
        self.assertEqual(sort(before), "use A\\X;\nuse B\\X;\n\nclass K {}\n\nuse C\\X;\nuse D\\X;\n")

    def test_non_imports_are_left_alone(self):
        text = HEAD + "final class K\n{\n    use Z\\Trait;\n    use A\\Trait;\n\n    public function f(): void\n    {\n        $x = static function () use ($y) {};\n    }\n}\n"
        self.assertEqual(sort(text), text)

    def test_sorted_input_is_unchanged_and_idempotent(self):
        text = HEAD + "use A\\X;\nuse B\\Y;\n"
        self.assertEqual(sort(text), text)
        self.assertEqual(sort(sort(text)), sort(text))


if __name__ == "__main__":
    unittest.main()
