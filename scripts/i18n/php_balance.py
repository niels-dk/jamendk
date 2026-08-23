# -*- coding: utf-8 -*-
"""Structural check for any PHP file, since there is no `php -l` on the dev box.

Only what is INSIDE <?php … ?> is examined. That matters: a first version of
this checked the whole file and reported false errors on any view containing
CSS or a URL, because `#12161f` looks like a PHP `#` comment and `https://`
looks like a `//` comment — both swallow the rest of the line, taking a closing
brace with them. HTML, CSS and JS braces are not PHP's to balance anyway.

Within PHP it tracks quoted strings with backslash escapes, skips comments, and
verifies strings close and brackets/braces balance. Catches the class of
mistake a scripted edit makes: a splice landing inside a string, or a
replacement dropping a closing brace.

    python scripts/i18n/php_balance.py controllers/media.php views/login.php
"""
import io, sys

def check(path):
    src = io.open(path, encoding='utf-8').read()
    n = len(src)
    i = line = 0
    line = 1
    depth = brace = 0
    errs = []
    in_php = False

    while i < n:
        if not in_php:
            # Outside PHP: find the next opening tag, counting newlines on the way.
            j = src.find('<?', i)
            if j < 0:
                line += src.count('\n', i, n)
                break
            line += src.count('\n', i, j)
            i = j + (5 if src.startswith('<?php', j) else (3 if src.startswith('<?=', j) else 2))
            in_php = True
            continue

        c = src[i]
        if c == '\n':
            line += 1; i += 1; continue
        if src.startswith('?>', i):
            in_php = False; i += 2; continue
        if src.startswith('//', i) or (c == '#' and not src.startswith('#[', i)):
            # Runs to end of line OR to ?>, whichever comes first — PHP ends a
            # one-line comment at the closing tag.
            nl = src.find('\n', i)
            cl = src.find('?>', i)
            j = min(x for x in (nl, cl, n) if x >= 0)
            i = j
            continue
        if src.startswith('/*', i):
            j = src.find('*/', i + 2)
            if j < 0:
                errs.append((line, 'unterminated block comment')); break
            line += src.count('\n', i, j); i = j + 2; continue
        if c in ("'", '"'):
            q, j, closed = c, i + 1, False
            while j < n:
                if src[j] == '\\':
                    j += 2; continue
                if src[j] == '\n':
                    line += 1
                if src[j] == q:
                    closed = True; break
                j += 1
            if not closed:
                errs.append((line, 'unterminated %s-quoted string' % q)); break
            i = j + 1; continue
        if c in '([':
            depth += 1
        elif c in ')]':
            depth -= 1
            if depth < 0:
                errs.append((line, 'unbalanced closing bracket')); break
        elif c == '{':
            brace += 1
        elif c == '}':
            brace -= 1
        i += 1

    if depth:
        errs.append((line, 'brackets unbalanced at EOF (%d)' % depth))
    if brace:
        errs.append((line, 'braces unbalanced at EOF (%d)' % brace))
    return errs

if __name__ == '__main__':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    ok = True
    for p in sys.argv[1:]:
        errs = check(p)
        if errs:
            ok = False
            print("  !! %s" % p)
            for ln, m in errs:
                print("       line %d: %s" % (ln, m))
        else:
            print("  ok %s" % p)
    sys.exit(0 if ok else 1)
