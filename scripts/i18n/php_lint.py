# -*- coding: utf-8 -*-
"""Stand-in for `php -l` on a language file, since there is no PHP binary here.

Tokenises the array body properly rather than eyeballing it: walks the file
character by character, tracking whether we are inside a single- or
double-quoted string and honouring backslash escapes, then checks that
  - every string closes
  - brackets balance
  - each entry looks like  'key' => <string> ,
A parse error in one of these files is a white screen for every page in that
language, so this is worth more than a regex.
"""
import io, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

def lint(path):
    s = io.open(path, encoding='utf-8').read()
    i, n = 0, len(s)
    line = 1
    depth = 0
    errs = []
    entries = 0
    while i < n:
        c = s[i]
        if c == '\n':
            line += 1; i += 1; continue
        # comments
        if s.startswith('//', i) or s.startswith('#', i):
            j = s.find('\n', i)
            i = n if j < 0 else j
            continue
        if s.startswith('/*', i):
            j = s.find('*/', i + 2)
            if j < 0:
                errs.append((line, 'unterminated block comment')); break
            line += s.count('\n', i, j); i = j + 2
            continue
        # strings
        if c in ("'", '"'):
            q = c; j = i + 1; closed = False
            while j < n:
                if s[j] == '\\':
                    j += 2; continue
                if s[j] == '\n':
                    line += 1
                if s[j] == q:
                    closed = True; break
                j += 1
            if not closed:
                errs.append((line, 'unterminated %s string' % ('single' if q == "'" else 'double')))
                break
            i = j + 1
            continue
        if c in '[(':
            depth += 1
        elif c in '])':
            depth -= 1
            if depth < 0:
                errs.append((line, 'unbalanced closing bracket')); break
        elif s.startswith('=>', i):
            entries += 1; i += 2; continue
        i += 1
    if depth != 0:
        errs.append((line, 'brackets unbalanced at EOF (depth %d)' % depth))
    if 'return' not in s.split('\n')[-40:][0] and '<?php' not in s[:20]:
        errs.append((0, 'no opening <?php'))
    return entries, errs

ok = True
for f in ['lang/en.php', 'lang/da.php', 'lang/pt-BR.php']:
    entries, errs = lint(r"C:\Users\MF AW\merelyadream.com" + '\\' + f.replace('/', '\\'))
    if errs:
        ok = False
        print("  !! %s" % f)
        for ln, msg in errs:
            print("       line %d: %s" % (ln, msg))
    else:
        print("  ok %-18s %d entries, strings and brackets balanced" % (f, entries))
print("\nlint:", "CLEAN" if ok else "PROBLEMS")
