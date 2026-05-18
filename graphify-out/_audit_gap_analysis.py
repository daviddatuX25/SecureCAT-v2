"""Analyze graphify graph.json to find all controller mutating methods and cross-reference with AuditService usage."""
import json, os, re, sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ROOT = r"d:\Projects\SecureCAT-v2"

# 1. Load graphify graph
with open(os.path.join(ROOT, "graphify-out", "graph.json")) as f:
    graph = json.load(f)

# Extract all controller method nodes
controller_methods = []
for node in graph["nodes"]:
    src = node.get("source_file", "")
    label = node.get("label", "")
    if "Controller" in src and label.startswith(".") and label.endswith("()"):
        controller_methods.append({
            "file": src,
            "method": label[1:-2],  # strip leading . and trailing ()
            "id": node["id"],
        })

# 2. Scan actual PHP controller files for AuditService usage
controllers_dir = os.path.join(ROOT, "app", "Http", "Controllers")

# Mutating HTTP verbs/patterns that should be logged
MUTATING_PATTERNS = re.compile(
    r'^\s*public\s+function\s+('
    r'store|create|update|destroy|delete|accept|dismiss|reject|'
    r'publish|unpublish|cancel|start|complete|reopen|release|unrelease|'
    r'assign|remove|bulk|resend|import|confirm|finalize|clear|'
    r'storeAdmin|updateAdmin|bulkAccept|bulkDismiss|bulkReopen|'
    r'storeOrUpdate|releaseBulk|releaseAll|removeApplicant|assignApplicants'
    r')\s*\(',
    re.IGNORECASE
)

# Find all PHP files in controllers
php_files = []
for dirpath, _, filenames in os.walk(controllers_dir):
    for fn in filenames:
        if fn.endswith(".php"):
            php_files.append(os.path.join(dirpath, fn))

results = []
for filepath in sorted(php_files):
    rel = os.path.relpath(filepath, ROOT)
    with open(filepath, "r", encoding="utf-8") as f:
        content = f.read()
    
    has_audit_import = "AuditService" in content
    
    # Find all public methods
    method_pattern = re.compile(r'public\s+function\s+(\w+)\s*\(')
    methods = method_pattern.findall(content)
    
    # For each method, check if it's mutating and if it has audit logging
    for method in methods:
        if method in ('__construct', 'middleware', 'authorize'):
            continue
        
        # Check if this is a mutating method (writes/changes data)
        is_mutating = bool(re.match(
            r'(store|update|destroy|delete|accept|dismiss|reject|'
            r'publish|unpublish|cancel|start|complete|reopen|release|unrelease|'
            r'assign|remove|bulk|resend|import|confirm|finalize|clear|'
            r'storeAdmin|updateAdmin|bulkAccept|bulkDismiss|bulkReopen|'
            r'storeOrUpdate|releaseBulk|releaseAll|removeApplicant|assignApplicants|'
            r'save|reset|toggle|enable|disable|activate|deactivate|upload|'
            r'markAttendance|logSubmission|bulkSubmit|recordAttendance|'
            r'chat|send)',
            method, re.IGNORECASE
        ))
        
        if not is_mutating:
            continue
        
        # Extract the method body (rough approximation)
        method_start = content.find(f"function {method}(")
        if method_start == -1:
            continue
        
        # Find the method body by counting braces
        brace_start = content.find("{", method_start)
        if brace_start == -1:
            continue
        
        depth = 0
        method_body = ""
        for i in range(brace_start, min(brace_start + 5000, len(content))):
            ch = content[i]
            if ch == '{':
                depth += 1
            elif ch == '}':
                depth -= 1
                if depth == 0:
                    method_body = content[brace_start:i+1]
                    break
        
        has_audit_call = "AuditService" in method_body or "audit" in method_body.lower()
        has_log_call = "Log::" in method_body or "\\Log::" in method_body
        
        results.append({
            "file": rel,
            "method": method,
            "has_audit": has_audit_call,
            "has_log": has_log_call,
            "has_any_logging": has_audit_call or has_log_call,
        })

# Report
print("=" * 90)
print("SECURECAT-V2 AUDIT LOGGING GAP ANALYSIS (via Graphify + Static Analysis)")
print("=" * 90)

# Group by file
from collections import defaultdict
by_file = defaultdict(list)
for r in results:
    by_file[r["file"]].append(r)

missing_count = 0
logged_count = 0
partial_count = 0

print("\n🔴 MISSING AUDIT LOGGING (mutating actions with NO logging):")
print("-" * 90)
for f in sorted(by_file.keys()):
    missing = [r for r in by_file[f] if not r["has_any_logging"]]
    if missing:
        print(f"\n  📁 {f}")
        for r in missing:
            print(f"     ❌ {r['method']}()")
            missing_count += 1

print(f"\n\n🟡 PARTIAL LOGGING (has Log:: but NOT AuditService):")
print("-" * 90)
for f in sorted(by_file.keys()):
    partial = [r for r in by_file[f] if r["has_log"] and not r["has_audit"]]
    if partial:
        print(f"\n  📁 {f}")
        for r in partial:
            print(f"     ⚠️  {r['method']}() — uses Log:: only (not in audit trail)")
            partial_count += 1

print(f"\n\n✅ PROPERLY AUDITED (uses AuditService):")
print("-" * 90)
for f in sorted(by_file.keys()):
    audited = [r for r in by_file[f] if r["has_audit"]]
    if audited:
        print(f"\n  📁 {f}")
        for r in audited:
            print(f"     ✅ {r['method']}()")
            logged_count += 1

print(f"\n\n{'=' * 90}")
print(f"SUMMARY")
print(f"{'=' * 90}")
print(f"  ✅ Properly audited (AuditService):  {logged_count}")
print(f"  ⚠️  Partial (Log:: only):             {partial_count}")
print(f"  ❌ Missing entirely:                  {missing_count}")
print(f"  📊 Total mutating actions:            {logged_count + partial_count + missing_count}")
coverage = (logged_count / (logged_count + partial_count + missing_count) * 100) if (logged_count + partial_count + missing_count) > 0 else 0
print(f"  📈 Audit coverage:                    {coverage:.1f}%")

# Also check services for mutating operations
print(f"\n\n{'=' * 90}")
print(f"SERVICE-LAYER AUDIT CHECK")
print(f"{'=' * 90}")
services_dir = os.path.join(ROOT, "app", "Services")
for fn in sorted(os.listdir(services_dir)):
    if not fn.endswith(".php") or fn == "AuditService.php":
        continue
    fpath = os.path.join(services_dir, fn)
    with open(fpath, "r", encoding="utf-8") as f:
        content = f.read()
    has_audit = "AuditService" in content
    # Check for DB writes
    has_writes = any(kw in content for kw in ["->create(", "->update(", "->delete(", "->save(", "::create(", "DB::"])
    if has_writes and not has_audit:
        print(f"  ⚠️  {fn} — performs DB writes but has no AuditService usage")
    elif has_writes and has_audit:
        print(f"  ✅ {fn} — has AuditService")

# Check PortalAuthController specifically
print(f"\n\n{'=' * 90}")
print(f"PORTAL AUTH CONTROLLER CHECK")
print(f"{'=' * 90}")
portal_auth = os.path.join(ROOT, "app", "Http", "Controllers", "PortalAuthController.php")
with open(portal_auth, "r", encoding="utf-8") as f:
    content = f.read()
method_pattern = re.compile(r'public\s+function\s+(\w+)\s*\(')
methods = method_pattern.findall(content)
for m in methods:
    if m == '__construct':
        continue
    method_start = content.find(f"function {m}(")
    brace_start = content.find("{", method_start)
    depth = 0
    method_body = ""
    for i in range(brace_start, min(brace_start + 5000, len(content))):
        ch = content[i]
        if ch == '{':
            depth += 1
        elif ch == '}':
            depth -= 1
            if depth == 0:
                method_body = content[brace_start:i+1]
                break
    has_audit = "AuditService" in method_body or "audit" in method_body.lower()
    symbol = "✅" if has_audit else "❌"
    print(f"  {symbol} {m}()")
