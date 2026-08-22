# Translation tooling

Three checks and one generator. None of this runs on the server — it is
development tooling, kept in the repo so the next translation does not have to
rediscover the traps.

## Checks — run these before committing a language change

```
python scripts/i18n/check_langs.py
python scripts/i18n/php_lint.py
```

`check_langs.py` verifies, across `en` / `da` / `pt-BR`:

- **parity** — every key exists in every file
- **quoting** — no unterminated string
- **placeholders** — every key called as `t('x', ['n' => …])` still contains
  `:n` in *all* languages. A translation that drops one renders a sentence
  with a hole in it and nothing else notices.
- **JS placeholders** — the handful of keys whose `:done` / `:who` are
  substituted in JavaScript rather than by `t()`
- **escape sequences** — a value that is a newline in English must still be a
  newline elsewhere. PHP expands `\n` only inside **double** quotes; in single
  quotes it is the two literal characters. Several `confirm()` dialogs depend
  on those newlines, and getting it wrong shows `\n\n` to the user. This check
  exists because the pt-BR generator got it wrong on the first pass.
- **stray non-Latin** — Cyrillic `к` and Greek `ο` are visually identical to
  their Latin twins. One slipped into the Danish file once.

`php_lint.py` stands in for `php -l`, which is not available on the dev
machine: it tokenises each language file and checks strings close and brackets
balance. A parse error in a language file is a white screen on every page for
that language only.

## Regenerating pt-BR

`lang/pt-BR.php` is **generated** from `lang/en.php` so the key order and
section grouping cannot drift:

```
python scripts/i18n/gen_br.py
```

Translations live in `br1.py` … `br5.py` as plain dicts. Editing
`lang/pt-BR.php` by hand works too, but the next regeneration overwrites it —
put lasting changes in the `br*.py` dicts.

## Adding a language

1. Add it to `I18n::LANGUAGES` in `app/i18n.php` (a `cc` for the flag; add the
   SVG to `I18n::flag()` if that country is not drawn yet).
2. Create `lang/<code>.php`. Match the filename's case exactly — the server is
   Linux and `pt-BR.php` is not `pt-br.php`.
3. Check `users.lang` is wide enough. It is `VARCHAR(5)`, which fits `pt-BR`
   exactly and would silently truncate something like `zh-Hant`.
4. Add the language to `LANGS` in `check_langs.py` and run both checks.

## Conventions

The product's own nouns (Dream, Vision, Mood, Trip, board) and the English
film-industry words crews already use (shot, shot list, B-roll, timelapse,
golden hour, mood board, brand, tag) stay English in every language. Ordinary
interface language is translated. Role names stay English because they name a
permission level the app stores in English.

Values may contain inline `<strong>` / `<a>` markup, and those keys are printed
with `t()` rather than `te()`. Long prose is keyed a paragraph at a time, never
split around its own link — word order moves between languages.
