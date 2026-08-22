# -*- coding: utf-8 -*-
"""Three-way language checks: parity, quoting, placeholders, stray scripts."""
import io, re, sys, glob, os
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
os.chdir(r"C:\Users\MF AW\merelyadream.com")

LANGS = ['en', 'da', 'pt-BR']
FILES = {l: 'lang/%s.php' % l for l in LANGS}

# 1. quoting — an unterminated string is a fatal on every page for that language
bad = 0
for l, f in FILES.items():
    for n, line in enumerate(io.open(f, encoding='utf-8'), 1):
        ls = line.strip()
        if ls.startswith(('*', '/*', '//', '#')) or not ls:
            continue
        stripped = re.sub(r"'(?:[^'\\]|\\.)*'", "Q", line.rstrip('\n'))
        stripped = re.sub(r'"(?:[^"\\]|\\.)*"', "Q", stripped)
        if "'" in stripped or '"' in stripped:
            print("  !! %s:%d unbalanced quote: %s" % (f, n, ls[:60])); bad += 1
print("quote check:", "CLEAN" if not bad else "%d PROBLEM(S)" % bad)

# 2. parity
vals = {}
for l, f in FILES.items():
    vals[l] = dict(re.findall(r"'([a-z_]+\.[a-z_0-9]+)'\s*=>\s*(.+?),?\s*$",
                              io.open(f, encoding='utf-8').read(), re.M))
base = set(vals['en'])
print("\nparity:")
for l in LANGS:
    miss = sorted(base - set(vals[l]))
    extra = sorted(set(vals[l]) - base)
    print("  %-6s %4d keys  missing %s  extra %s" %
          (l, len(vals[l]), miss or 'none', extra or 'none'))

# 3. placeholders present in every language for every parameterised call
calls = {}
for f in glob.glob('views/**/*.php', recursive=True) + glob.glob('app/*.php') + glob.glob('controllers/*.php'):
    parts = f.replace('\\', '/').split('/')
    if any(' - Copy' in p for p in parts) or 'old' in parts:
        continue
    s = io.open(f, encoding='utf-8').read()
    for m in re.finditer(r"\bt[e]?\(\s*'([a-z_]+\.[a-z_0-9]+)'\s*,\s*\[(.*?)\]\s*\)", s, re.S):
        calls.setdefault(m.group(1), set()).update(re.findall(r"'([a-z_]+)'\s*=>", m.group(2)))
prob = 0
for key, names in sorted(calls.items()):
    for l in LANGS:
        v = vals[l].get(key)
        if v is None:
            print("  !! %s absent from %s" % (key, l)); prob += 1; continue
        for n in names:
            if (':' + n) not in v:
                print("  !! %s [%s] lost :%s" % (key, l, n)); prob += 1
print("\nplaceholders:", "CLEAN (%d parameterised keys x %d langs)" % (len(calls), len(LANGS))
      if not prob else "%d PROBLEM(S)" % prob)

# 4. placeholders the JS re-substitutes by hand
JSKEYS = {'trip.shots_captured': [':done', ':total'], 'trip.musts': [':done', ':total'],
          'trip.waiting_sync': [':n'], 'basics.expires': [':date'],
          'adm.confirm_verify': [':who'], 'adm.transfer_ask': [':who'],
          'adm.transfer_deact': [':who'], 'adm.transfer_done': [':n', ':to'],
          'adm.confirm_block': [':who'], 'adm.confirm_del': [':who']}
prob2 = 0
for key, marks in JSKEYS.items():
    for l in LANGS:
        v = vals[l].get(key, '')
        for mk in marks:
            if mk not in v:
                print("  !! %s [%s] lost %s" % (key, l, mk)); prob2 += 1
print("JS placeholders:", "CLEAN" if not prob2 else "%d PROBLEM(S)" % prob2)

# 4b. escape sequences must survive translation.
# PHP expands \n only inside DOUBLE quotes; a single-quoted 'a\nb' is the two
# literal characters. So a value that is a newline in English must still be a
# newline in every other language, not the visible text "\n".
def php_decode(raw):
    raw = raw.strip().rstrip(',').strip()
    if len(raw) < 2:
        return raw
    q, body = raw[0], raw[1:-1]
    out, i = [], 0
    while i < len(body):
        ch = body[i]
        if ch == '\\' and i + 1 < len(body):
            nxt = body[i + 1]
            if q == "'":
                out.append(nxt if nxt in ("'", '\\') else ch + nxt)
            else:
                out.append({'n': '\n', 't': '\t', 'r': '\r',
                            '"': '"', '\\': '\\', '$': '$'}.get(nxt, ch + nxt))
            i += 2; continue
        out.append(ch); i += 1
    return ''.join(out)

prob3 = 0
for key, v_en in vals['en'].items():
    want = php_decode(v_en).count('\n')
    if not want:
        continue
    for l in LANGS[1:]:
        got = php_decode(vals[l].get(key, "''")).count('\n')
        if got != want:
            print("  !! %s [%s] has %d newline(s), English has %d" % (key, l, got, want))
            prob3 += 1
print("escape sequences:", "CLEAN" if not prob3 else "%d PROBLEM(S)" % prob3)

# 5. stray Cyrillic/Greek that looks Latin
for l, f in FILES.items():
    s = io.open(f, encoding='utf-8').read()
    strays = sorted(set(re.findall(r'[\u0370-\u03ff\u0400-\u04ff]', s)))
    print("%-6s stray non-Latin: %s" % (l, strays or 'NONE'))
