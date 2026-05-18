import json
from graphify.detect import detect
from pathlib import Path

# Scope to just app source - not vendor/node_modules
dirs_to_scan = [
    'app',
    'resources/js',
    'resources/views',
    'routes',
    'database',
    'config',
    'tests',
    'docs',
]

all_results = {
    'files': {},
    'total_files': 0,
    'total_words': 0,
    'skipped_sensitive': [],
}

for d in dirs_to_scan:
    p = Path(d)
    if not p.exists():
        continue
    result = detect(p)
    for cat, flist in result.get('files', {}).items():
        if cat not in all_results['files']:
            all_results['files'][cat] = []
        all_results['files'][cat].extend(flist)
    all_results['total_files'] += result.get('total_files', 0)
    all_results['total_words'] += result.get('total_words', 0)
    all_results['skipped_sensitive'].extend(result.get('skipped_sensitive', []))

with open('graphify-out/.graphify_detect.json', 'w', encoding='utf-8') as f:
    json.dump(all_results, f, ensure_ascii=False)

total = all_results['total_files']
words = all_results['total_words']
print(f"Corpus (scoped): {total} files · ~{words:,} words")
for cat, flist in all_results['files'].items():
    if flist:
        print(f"  {cat}: {len(flist)} files")
