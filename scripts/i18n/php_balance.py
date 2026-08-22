# -*- coding: utf-8 -*-
"""Structural check for any PHP file, since there is no `php -l` on the dev box.

Tokenises properly — tracks quoted strings with backslash escapes and skips
comments — then verifies strings close and brackets/braces balance. Catches the
class of mistake a scripted edit makes: a splice that lands inside a string, or
a replacement that drops a closing brace.

    python scripts/i18n/php_balance.py controllers/media.php [...]
"""
import io, sys

def check(path):
    src = io.open(path, encoding='utf-8').read()
    i, n, line = 0, len(src), 1
    depth = brace = 0
    errs = []
    while i < n:
        c = src[i]
        if c == '\n':
            line += 1; i += 1; continue
        if src.startswith('//', i) or (c == '#' and not src.startswith('#[', i)):
            j = src.find('\n', i); i = n if j < 0 else j; continue
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
