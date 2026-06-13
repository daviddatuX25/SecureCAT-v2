import re

with open('/home/user/Projects/SecureCAT-v2/capstone/SecureCAT_Ch1_Ch2_Manuscript.md', 'r') as f:
    lines = f.readlines()

# Match em-dashes (Unicode — and –) and double hyphens (--)
em_dash_pattern = re.compile(r'—|–|--')

findings = []
in_mermaid = False

for i, line in enumerate(lines, 1):
    stripped = line.strip()
    if stripped.startswith('```'):
        in_mermaid = not in_mermaid
        continue
    if in_mermaid:
        continue
    if stripped.startswith('<!--') and stripped.endswith('-->'):
        continue

    clean_line = re.sub(r'<!--.*?-->', '', line)
    clean_line = re.sub(r'-->', '', clean_line)

    matches = em_dash_pattern.findall(clean_line)
    if matches:
        # Exclude table borders (e.g. |---| or ---)
        if re.match(r'^[\s|:-]+$', stripped):
            continue
        # Exclude en-dashes in number ranges like 80.3 – 100 or 2026-06-02
        # Let's check matches carefully: if we have "80.3 – 100", that's a number range en-dash.
        # We only want to flag true narrative em-dashes.
        prose_matches = []
        for m in re.finditer(r'—|–|--', clean_line):
            start = max(0, m.start() - 10)
            end = min(len(clean_line), m.end() + 10)
            ctx = clean_line[start:end]
            # Check if it looks like a range: digits/spaces on both sides
            if re.search(r'\d\s*[–-]\s*\d', ctx):
                continue
            prose_matches.append(m.group(0))
            
        if prose_matches:
            findings.append((i, line.strip()))

with open('/home/user/Projects/SecureCAT-v2/capstone/scratch/em_dash_audit.txt', 'w') as f:
    f.write(f"Total prose em-dashes found: {len(findings)}\n\n")
    for line_no, text in findings:
        f.write(f"Line {line_no:03d}: {text}\n")

print(f"Audit completed. Total prose em-dashes found: {len(findings)}")
