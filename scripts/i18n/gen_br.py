# -*- coding: utf-8 -*-
"""Generate lang/pt-BR.php from lang/en.php.

Walks en.php line by line and swaps each value for its Portuguese one, keeping
the file's order, grouping and section comments. Generating rather than
hand-writing means the three files cannot drift apart in structure, and any key
without a translation is reported rather than silently dropped.
"""
import io, os, re, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

import br1, br2, br3, br4, br5
BR = {}
for mod in (br1, br2, br3, br4, br5):
    for k, v in mod.BR.items():
        if k in BR:
            print("  !! duplicate key across batches: %s" % k)
        BR[k] = v

BASE = r"C:\Users\MF AW\merelyadream.com"
src = io.open(os.path.join(BASE, 'lang', 'en.php'), encoding='utf-8').read()

def php_quote(v):
    """Quote a value for PHP.

    Must go double-quoted when the value carries a \\n escape: PHP only turns
    \\n into a newline inside double quotes, and inside single quotes it would
    render as the literal two characters. Several confirm() dialogs rely on
    those newlines, so getting this wrong shows "\\n\\n" to the user.
    """
    has_nl = '\\n' in v
    if not has_nl and "'" not in v:
        return "'" + v.replace('\\', '\\\\') + "'"
    out = v.replace('\\', '\\\\')      # escape every backslash…
    out = out.replace('\\\\n', '\\n')  # …then put the newline escapes back
    out = out.replace('"', '\\"').replace('$', '\\$')
    return '"' + out + '"'

KEYLINE = re.compile(r"^(\s*)'([a-z_]+(?:\.[a-z_0-9]+)+)'(\s*)=>\s*(?:\"(?:[^\"\\]|\\.)*\"|'(?:[^'\\]|\\.)*')\s*,?\s*$")
INLINE  = re.compile(r"'([a-z_]+(?:\.[a-z_0-9]+)+)'(\s*)=>\s*(?:\"(?:[^\"\\]|\\.)*\"|'(?:[^'\\]|\\.)*')\s*,")

out, missing, done = [], [], 0
for line in src.split('\n'):
    m = KEYLINE.match(line)
    if m:
        indent, key, pad = m.group(1), m.group(2), m.group(3)
        if key in BR:
            out.append("%s'%s'%s=> %s," % (indent, key, pad, php_quote(BR[key]))); done += 1
        else:
            missing.append(key); out.append(line)
        continue
    # lines holding several key/value pairs (the month/day rows)
    if INLINE.search(line):
        def sub(mm):
            global done
            k = mm.group(1)
            if k in BR:
                done += 1
                return "'%s'%s=> %s," % (k, mm.group(2), php_quote(BR[k]))
            missing.append(k)
            return mm.group(0)
        out.append(INLINE.sub(sub, line))
        continue
    out.append(line)

txt = '\n'.join(out)

# Swap the English file's own header note for a Portuguese-specific one.
txt = re.sub(r"/\*\*.*?\*/",
"""/**
 * lang/pt-BR.php — Brazilian Portuguese.
 *
 * GENERATED from lang/en.php (scratchpad/gen_br.py) so the key order and the
 * section grouping match it exactly. Edit values here freely; if you add a NEW
 * key, add it to en.php and da.php too — the parity check is what keeps the
 * three honest.
 *
 * TRANSLATOR'S NOTE — please read before "fixing" these:
 * The product's own nouns (Dream, Vision, Mood, Trip, board) and the English
 * film-industry words Brazilian crews already use (shot, shot list, B-roll,
 * timelapse, golden hour, mood board, brand, tag) are LEFT IN ENGLISH on
 * purpose, exactly as in the Danish file. Ordinary interface language is
 * translated. Roles are left as viewer / editor / co-owner / delegate because
 * they name a permission level the app stores in English.
 *
 * NOT reviewed by a native speaker yet. The keys that matter most commercially
 * are lp.* (landing page) and pr.* (pricing) — those decide whether someone
 * signs up, and they are the ones to have read first.
 */""", txt, count=1, flags=re.S)

dst = os.path.join(BASE, 'lang', 'pt-BR.php')
io.open(dst, 'w', encoding='utf-8', newline='\n').write(txt)

print("translated: %d" % done)
print("untranslated (left English): %d" % len(missing))
for k in missing:
    print("   ", k)
