#!/usr/bin/env python3
"""
md-updates-docx core script - thin wrapper calling md_to_docx.py functions.
"""
import argparse
import sys
from pathlib import Path

# Add manuscript dir to path for md_to_docx import
sys.path.insert(0, str(Path(__file__).parent.parent.parent))

try:
    from md_to_docx import (
        parse_manuscript, build_tag_map, apply_updates, 
        compute_docx_sha256, update_manuscript_meta, full_rebuild
    )
except ImportError as e:
    print(f"❌ Import error: {e}", file=sys.stderr)
    print("   md_to_docx.py not yet implemented or has errors", file=sys.stderr)
    sys.exit(1)

def main():
    parser = argparse.ArgumentParser(description="Surgically update master DOCX from manuscript MD")
    parser.add_argument('--md', required=True, help="Path to manuscript MD")
    parser.add_argument('--docx', required=True, help="Path to master DOCX")
    parser.add_argument('--template', required=True, help="Path to template DOCX")
    parser.add_argument('--update-tags', nargs='+', help="Specific tags to update (default: all)")
    parser.add_argument('--full-rebuild', action='store_true', help="Full rebuild from template")
    args = parser.parse_args()
    
    md_path = Path(args.md)
    docx_path = Path(args.docx)
    template_path = Path(args.template)
    
    # Parse manuscript
    meta, tags = parse_manuscript(md_path)
    print(f"Parsed {len(tags)} tags from {md_path}")
    
    # Full rebuild mode
    if args.full_rebuild:
        print("Full rebuild from template...")
        full_rebuild(template_path, tags, docx_path)
    else:
        # Surgical update mode
        # Pre-flight: verify DOCX matches META
        if 'docx-sha256' in meta:
            current_sha = compute_docx_sha256(docx_path)
            if current_sha != meta['docx-sha256']:
                print(f"⚠️  DRIFT DETECTED: DOCX SHA256 mismatch!")
                print(f"   Expected: {meta['docx-sha256']}")
                print(f"   Actual:   {current_sha}")
                print("   Run docx-drift-check skill first to resolve.")
                return 1
        
        # Load or create master DOCX
        from docx import Document
        if docx_path.exists():
            doc = Document(docx_path)
        else:
            doc = Document(template_path)
        
        # Build tag map
        tag_map = build_tag_map(doc, tags)
        
        # Determine which tags to update
        update_tags = args.update_tags if args.update_tags else list(tags.keys())
        
        # Apply updates
        apply_updates(doc, tag_map, tags, update_tags)
        
        # Save updated DOCX
        doc.save(docx_path)
    
    # Update META in MD
    new_sha = compute_docx_sha256(docx_path)
    from datetime import datetime, timezone
    new_meta = {
        'docx-sync-version': datetime.now(timezone.utc).isoformat(),
        'docx-sha256': new_sha
    }
    update_manuscript_meta(md_path, new_meta)
    print(f"✅ Updated {docx_path} and META tag in {md_path}")
    print(f"   New SHA256: {new_sha}")

if __name__ == '__main__':
    main()
