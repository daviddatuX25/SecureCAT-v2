"""Full Graphify pipeline for SecureCAT-v2 (scoped to app source)."""
import json
import sys
import os
from pathlib import Path

os.chdir(r"D:\Projects\SecureCAT-v2")

# Save python interpreter
Path('graphify-out/.graphify_python').write_text(sys.executable, encoding='utf-8')
Path('graphify-out/.graphify_root').write_text(str(Path('.').resolve()), encoding='utf-8')

# ── Step 1: Scoped detection ──
print("=" * 60)
print("STEP 1: Detecting files (scoped to app source)...")
print("=" * 60)

from graphify.detect import detect

dirs_to_scan = ['app', 'resources/js', 'resources/views', 'routes', 'database', 'config', 'tests', 'docs']
all_results = {'files': {}, 'total_files': 0, 'total_words': 0, 'skipped_sensitive': []}

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

Path('graphify-out/.graphify_detect.json').write_text(json.dumps(all_results, ensure_ascii=False), encoding='utf-8')

print(f"Corpus: {all_results['total_files']} files · ~{all_results['total_words']:,} words")
for cat, flist in all_results['files'].items():
    if flist:
        print(f"  {cat}: {len(flist)} files")

# ── Step 2: AST extraction (code files) ──
print()
print("=" * 60)
print("STEP 2: AST extraction (code files)...")
print("=" * 60)

from graphify.extract import collect_files, extract

code_files = []
for f in all_results.get('files', {}).get('code', []):
    fp = Path(f)
    if fp.is_dir():
        code_files.extend(collect_files(fp))
    else:
        code_files.append(fp)

if code_files:
    ast_result = extract(code_files, cache_root=Path('.'))
    Path('graphify-out/.graphify_ast.json').write_text(
        json.dumps(ast_result, indent=2, ensure_ascii=False), encoding='utf-8'
    )
    print(f"AST: {len(ast_result['nodes'])} nodes, {len(ast_result['edges'])} edges")
else:
    ast_result = {'nodes': [], 'edges': [], 'input_tokens': 0, 'output_tokens': 0}
    Path('graphify-out/.graphify_ast.json').write_text(
        json.dumps(ast_result, ensure_ascii=False), encoding='utf-8'
    )
    print("No code files - skipping AST")

# ── Step 3: Skip semantic (no LLM key needed for code-only) ──
# Write empty semantic file - AST covers code structure
Path('graphify-out/.graphify_semantic.json').write_text(
    json.dumps({'nodes': [], 'edges': [], 'hyperedges': [], 'input_tokens': 0, 'output_tokens': 0}, ensure_ascii=False),
    encoding='utf-8'
)

# ── Step 4: Merge AST + semantic ──
print()
print("=" * 60)
print("STEP 3: Merging extractions...")
print("=" * 60)

ast_data = json.loads(Path('graphify-out/.graphify_ast.json').read_text(encoding='utf-8'))
sem_data = json.loads(Path('graphify-out/.graphify_semantic.json').read_text(encoding='utf-8'))

seen = {n['id'] for n in ast_data['nodes']}
merged_nodes = list(ast_data['nodes'])
for n in sem_data['nodes']:
    if n['id'] not in seen:
        merged_nodes.append(n)
        seen.add(n['id'])

merged = {
    'nodes': merged_nodes,
    'edges': ast_data['edges'] + sem_data['edges'],
    'hyperedges': sem_data.get('hyperedges', []),
    'input_tokens': 0,
    'output_tokens': 0,
}
Path('graphify-out/.graphify_extract.json').write_text(
    json.dumps(merged, indent=2, ensure_ascii=False), encoding='utf-8'
)
print(f"Merged: {len(merged_nodes)} nodes, {len(merged['edges'])} edges")

# ── Step 5: Build graph, cluster, analyze ──
print()
print("=" * 60)
print("STEP 4: Building graph + clustering + analysis...")
print("=" * 60)

from graphify.build import build_from_json
from graphify.cluster import cluster, score_all
from graphify.analyze import god_nodes, surprising_connections, suggest_questions
from graphify.report import generate
from graphify.export import to_json

extraction = json.loads(Path('graphify-out/.graphify_extract.json').read_text(encoding='utf-8'))
detection = json.loads(Path('graphify-out/.graphify_detect.json').read_text(encoding='utf-8'))

G = build_from_json(extraction)
print(f"Graph built: {G.number_of_nodes()} nodes, {G.number_of_edges()} edges")

if G.number_of_nodes() == 0:
    print("ERROR: Graph is empty!")
    sys.exit(1)

communities = cluster(G)
cohesion = score_all(G, communities)
tokens = {'input': 0, 'output': 0}
gods = god_nodes(G)
surprises = surprising_connections(G, communities)

# Auto-label communities
labels = {}
for cid, members in communities.items():
    member_labels = [G.nodes[m].get('label', m) for m in members if m in G.nodes][:5]
    labels[cid] = f"Community {cid}"

questions = suggest_questions(G, communities, labels)

report = generate(G, communities, cohesion, labels, gods, surprises, detection, tokens, '.', suggested_questions=questions)
Path('graphify-out/GRAPH_REPORT.md').write_text(report, encoding='utf-8')
to_json(G, communities, 'graphify-out/graph.json')

analysis = {
    'communities': {str(k): v for k, v in communities.items()},
    'cohesion': {str(k): v for k, v in cohesion.items()},
    'gods': gods,
    'surprises': surprises,
    'questions': questions,
}
Path('graphify-out/.graphify_analysis.json').write_text(
    json.dumps(analysis, indent=2, ensure_ascii=False), encoding='utf-8'
)
Path('graphify-out/.graphify_labels.json').write_text(
    json.dumps({str(k): v for k, v in labels.items()}, ensure_ascii=False), encoding='utf-8'
)

print(f"Communities: {len(communities)}")
print(f"God nodes: {len(gods)}")

# ── Step 6: Generate HTML visualization ──
print()
print("=" * 60)
print("STEP 5: Generating HTML visualization...")
print("=" * 60)

try:
    import subprocess
    subprocess.run([sys.executable, '-m', 'graphify', 'export', 'html'], check=True, cwd=r"D:\Projects\SecureCAT-v2")
    print("HTML graph exported to graphify-out/graph.html")
except Exception as e:
    print(f"HTML export via CLI failed ({e}), trying direct export...")
    try:
        from graphify.export import to_html
        to_html(G, communities, labels, 'graphify-out/graph.html')
        print("HTML graph exported to graphify-out/graph.html")
    except Exception as e2:
        print(f"HTML export failed: {e2}")
        print("graph.json and GRAPH_REPORT.md are still available")

# ── Done ──
print()
print("=" * 60)
print("DONE! Outputs in graphify-out/")
print("=" * 60)
print(f"  graph.html       - interactive graph (open in browser)")
print(f"  GRAPH_REPORT.md  - audit report with god nodes & connections")
print(f"  graph.json       - raw graph data for queries")
print()
print("Top God Nodes:")
for g in gods[:10]:
    print(f"  - {g.get('label', g.get('id', '?'))} (degree: {g.get('degree', '?')})")
