import re

with open('/home/user/Projects/SecureCAT-v2/capstone/SecureCAT_Ch1_Ch2_Manuscript.md', 'r') as f:
    text = f.read()

# Find parenthetical citations: (Name, 202x) or (Name & Name, 202x) or (Name et al., 202x)
citations = re.findall(r'\(([^)]+?, \d{4})\)', text)
for c in sorted(set(citations)):
    print(c)

print("\n--- Narrative Citations ---")
# Find narrative citations: Name (202x)
narrative = re.findall(r'\b([A-Z][a-zA-Z]+(?:\s+and\s+[A-Z][a-zA-Z]+)?(?:\s+et\s+al\.)?)\s+\((\d{4})\)', text)
for n in sorted(set(narrative)):
    print(n)
