import json
from graphify.detect import detect
from pathlib import Path

result = detect(Path('.'))

with open('graphify-out/.graphify_detect.json', 'w', encoding='utf-8') as f:
    json.dump(result, f, ensure_ascii=False)

total = result.get('total_files', 0)
words = result.get('total_words', 0)
files = result.get('files', {})

print(f"Corpus: {total} files · ~{words:,} words")
for cat, flist in files.items():
    if flist:
        print(f"  {cat}: {len(flist)} files")

if result.get('skipped_sensitive'):
    print(f"  skipped: {len(result['skipped_sensitive'])} sensitive files")
