import json

data = json.load(open('graphify-out/graph.json', encoding='utf-8'))

# Check for svelte files
svelte = [n for n in data.get('nodes', []) if 'svelte' in str(n.get('source_file', '')).lower()]
js = [n for n in data.get('nodes', []) if n.get('source_file', '').endswith('.js')]
php = [n for n in data.get('nodes', []) if n.get('source_file', '').endswith('.php')]
blade = [n for n in data.get('nodes', []) if 'blade' in str(n.get('source_file', '')).lower()]

print(f"Svelte nodes: {len(svelte)}")
print(f"JS nodes:     {len(js)}")
print(f"PHP nodes:    {len(php)}")
print(f"Blade nodes:  {len(blade)}")

if svelte:
    print("\nSample Svelte nodes:")
    for n in svelte[:10]:
        print(f"  {n.get('label', n['id'])} <- {n.get('source_file', '?')}")
else:
    print("\nNo Svelte files captured - .svelte is not in Graphify's tree-sitter supported extensions")
    print("Supported code: .py .ts .js .go .rs .java .c .cpp .rb .cs .kt .scala .php")
