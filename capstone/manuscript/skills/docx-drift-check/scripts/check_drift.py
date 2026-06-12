#!/usr/bin/env python3
"""
docx-drift-check core script.
Compares master DOCX SHA256 with manuscript MD META tag.
Exit codes: 0=PASS, 1=DRIFT_DETECTED, 2=MISSING_DOCX, 3=NO_META, 4=PARSE_ERROR
"""
import argparse
import hashlib
import re
import sys
from pathlib import Path
from docx import Document

def parse_meta(md_path: Path) -> dict:
    """Extract META tag from manuscript MD."""
    try:
        content = md_path.read_text(encoding='utf-8')
    except Exception as e:
        print(f"❌ PARSE_ERROR: Cannot read {md_path}: {e}", file=sys.stderr)
        return {}
    
    meta_match = re.search(r'<!--\s*META:\s*(.*?)\s*-->', content)
    if not meta_match:
        return {}
    
    meta = {}
    for part in meta_match.group(1).split():
        if '=' in part:
            k, v = part.split('=', 1)
            meta[k] = v.strip('"')
    return meta

def compute_docx_sha256(docx_path: Path) -> str:
    """Compute SHA256 of DOCX file."""
    try:
        return hashlib.sha256(docx_path.read_bytes()).hexdigest()
    except Exception as e:
        print(f"❌ PARSE_ERROR: Cannot hash {docx_path}: {e}", file=sys.stderr)
        return ""

def extract_docx_text(docx_path: Path) -> str:
    """Extract plain text from DOCX for diff hint."""
    try:
        doc = Document(docx_path)
        return '\n'.join(p.text for p in doc.paragraphs)
    except Exception:
        return ""

def main():
    parser = argparse.ArgumentParser(description="Check DOCX drift against manuscript META tag")
    parser.add_argument('--md', required=True, help="Path to manuscript MD")
    parser.add_argument('--docx', required=True, help="Path to master DOCX")
    parser.add_argument('--verbose', action='store_true', help="Verbose output")
    args = parser.parse_args()
    
    md_path = Path(args.md)
    docx_path = Path(args.docx)
    
    # Parse META
    meta = parse_meta(md_path)
    expected_sha = meta.get('docx-sha256')
    sync_version = meta.get('docx-sync-version', 'unknown')
    
    if not expected_sha:
        print(f"❌ NO_META: No docx-sha256 in MD META tag")
        print(f"   Last sync version: {sync_version}")
        print(f"   Recommend: run full rebuild then update META")
        return 3
    
    # Check DOCX exists
    if not docx_path.exists():
        print(f"❌ MISSING_DOCX: {docx_path} does not exist")
        print(f"   Last sync: {sync_version}")
        return 2
    
    # Compute actual SHA
    actual_sha = compute_docx_sha256(docx_path)
    if not actual_sha:
        return 4
    
    # Compare
    if actual_sha == expected_sha:
        print(f"✅ PASS: DOCX matches META (synced {sync_version})")
        if args.verbose:
            print(f"   SHA256: {actual_sha}")
        return 0
    
    # DRIFT DETECTED
    print(f"❌ DRIFT_DETECTED: DOCX has unwatched changes")
    print(f"   Expected SHA256: {expected_sha} (from MD META, synced {sync_version})")
    print(f"   Actual SHA256:   {actual_sha}")
    
    # Diff hint
    current_text = extract_docx_text(docx_path)
    print(f"   Current DOCX length: {len(current_text)} chars")
    print(f"   Sections likely changed: (need git snapshot to pinpoint)")
    print(f"   Resolution: accept-cloud | keep-local | merge | snapshot-cloud")
    
    return 1

if __name__ == '__main__':
    sys.exit(main())
