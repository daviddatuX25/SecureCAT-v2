#!/usr/bin/env python3
"""
Core MD → DOCX converter with tag-based surgical updates.
Skips Mermaid/diagram auto-generation (diagrams are human tasks).
Uses template table formatting (no left/vertical middle borders).
"""
import argparse
import hashlib
import json
import re
import sys
from datetime import datetime, timezone
from pathlib import Path
from docx import Document
from docx.oxml.ns import qn
from docx.oxml import OxmlElement
from docx.shared import Pt, Inches, Emu
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH

# ======== MANUSCRIPT PARSING ========

def parse_manuscript(md_path: Path) -> tuple[dict, dict]:
    """Parse manuscript MD for META, TAGs, UPDATE, REMOVE blocks."""
    content = md_path.read_text(encoding='utf-8')
    
    # Parse META tag
    meta = {}
    meta_match = re.search(r'<!--\s*META:\s*(.*?)\s*-->', content)
    if meta_match:
        for part in meta_match.group(1).split():
            if '=' in part:
                k, v = part.split('=', 1)
                meta[k] = v.strip('"')
    
    # Parse TAGs with UPDATE/REMOVE blocks
    tags = {}
    tag_pattern = re.compile(r'<!--\s*TAG:\s*(\S+)\s*-->')
    tag_positions = [(m.start(), m.group(1)) for m in tag_pattern.finditer(content)]
    
    for i, (pos, tag_name) in enumerate(tag_positions):
        end_pos = tag_positions[i + 1][0] if i + 1 < len(tag_positions) else len(content)
        section_content = content[pos:end_pos]
        
        update_match = re.search(
            r'<!--\s*UPDATE:START\s*-->(.*?)<!--\s*UPDATE:END\s*-->',
            section_content, re.DOTALL
        )
        update_content = update_match.group(1).strip() if update_match else None
        
        remove_match = re.search(
            r'<!--\s*REMOVE:START\s*-->(.*?)<!--\s*REMOVE:END\s*-->',
            section_content, re.DOTALL
        )
        remove_content = remove_match.group(1).strip() if remove_match else None
        
        section_no_tag = re.sub(r'<!--\s*TAG:\s*\S+\s*-->\s*', '', section_content, count=1)
        heading_match = re.search(r'^(#{1,4})\s+(.+)$', section_no_tag, re.MULTILINE)
        heading = heading_match.group(2).strip() if heading_match else tag_name
        heading_level = len(heading_match.group(1)) if heading_match else 2
        
        raw_content = section_no_tag
        raw_content = re.sub(r'<!--\s*UPDATE:START\s*-->.*?<!--\s*UPDATE:END\s*-->', '', raw_content, flags=re.DOTALL)
        raw_content = re.sub(r'<!--\s*REMOVE:START\s*-->.*?<!--\s*REMOVE:END\s*-->', '', raw_content, flags=re.DOTALL)
        raw_content = raw_content.strip()
        
        tags[tag_name] = {
            'heading': heading,
            'level': heading_level,
            'update': update_content,
            'remove': remove_content,
            'raw_content': raw_content
        }
    
    return meta, tags

# ======== DOCX HELPERS ========

def sanitize_bookmark(name: str) -> str:
    return re.sub(r'[^a-zA-Z0-9_]', '_', name)

def get_or_create_bookmark(doc: Document, tag_name: str, heading_text: str, heading_level: int) -> str:
    bookmark_name = f"tag_{sanitize_bookmark(tag_name)}"
    
    # Find heading and create bookmark there
    target_idx = None
    for i, p in enumerate(doc.paragraphs):
        if p.style.name.startswith("Heading") and heading_text.lower() in p.text.lower():
            target_idx = i
            break
    
    if target_idx is None:
        for i, p in enumerate(doc.paragraphs):
            if heading_text.lower() in p.text.lower():
                target_idx = i
                break
    
    if target_idx is not None:
        create_bookmark_at_paragraph(doc.paragraphs[target_idx], bookmark_name)
        return bookmark_name
    
    # Fallback: create at end
    create_bookmark_at_paragraph(doc.paragraphs[-1], bookmark_name)
    return bookmark_name
    
    target_idx = None
    for i, p in enumerate(doc.paragraphs):
        if p.style.name.startswith('Heading') and heading_text.lower() in p.text.lower():
            target_idx = i
            break
    
    if target_idx is not None:
        create_bookmark_at_paragraph(doc.paragraphs[target_idx], bookmark_name)
        return bookmark_name
    
    create_bookmark_at_paragraph(doc.paragraphs[-1], bookmark_name)
    return bookmark_name

def create_bookmark_at_paragraph(paragraph, bookmark_name: str):
    start = OxmlElement('w:bookmarkStart')
    start.set(qn('w:id'), str(hash(bookmark_name) % 1000000))
    start.set(qn('w:name'), bookmark_name)
    paragraph._p.insert(0, start)
    end = OxmlElement('w:bookmarkEnd')
    end.set(qn('w:id'), str(hash(bookmark_name) % 1000000))
    end.set(qn('w:name'), bookmark_name)
    paragraph._p.append(end)

def find_section_paragraphs(doc: Document, bookmark_name: str, tag_data: dict) -> tuple[int, int]:
    start_idx = -1
    for i, p in enumerate(doc.paragraphs):
        if p._p.xpath(f'.//w:bookmarkStart[@w:name="{bookmark_name}"]'):
            start_idx = i
            break
    
    if start_idx == -1:
        for i, p in enumerate(doc.paragraphs):
            if p.style.name.startswith('Heading') and tag_data['heading'].lower() in p.text.lower():
                start_idx = i
                break
    
    if start_idx == -1:
        return (-1, -1)
    
    current_level = 0
    for p in doc.paragraphs[start_idx:start_idx+1]:
        if p.style.name.startswith('Heading'):
            try:
                current_level = int(p.style.name[-1])
            except:
                current_level = 2
    
    end_idx = len(doc.paragraphs)
    for i in range(start_idx + 1, len(doc.paragraphs)):
        p = doc.paragraphs[i]
        if p.style.name.startswith('Heading'):
            try:
                level = int(p.style.name[-1])
            except:
                level = 2
            if level <= current_level:
                end_idx = i
                break
    
    return (start_idx, end_idx)

def delete_paragraphs(doc: Document, start_idx: int, end_idx: int, remove_text: str = None):
    if start_idx < 0 or end_idx <= start_idx:
        return
    
    to_delete = []
    for i in range(start_idx + 1, end_idx):
        p = doc.paragraphs[i]
        if remove_text:
            if remove_text.strip() in p.text:
                to_delete.append(i)
        else:
            to_delete.append(i)
    
    for i in reversed(to_delete):
        p = doc.paragraphs[i]
        p._element.getparent().remove(p._element)

def insert_section_content(doc: Document, start_idx: int, end_idx: int, new_content: str, heading_level: int):
    if start_idx < 0:
        start_idx = len(doc.paragraphs) - 1
    
    delete_paragraphs(doc, start_idx, end_idx)
    insert_at = start_idx + 1
    
    lines = new_content.split('\n')
    for line in lines:
        line = line.strip()
        if not line:
            continue
        if line.startswith('#'):
            level = len(line) - len(line.lstrip('#'))
            text = line.lstrip('# ').strip()
            p = doc.add_paragraph(text, style=f'Heading {min(level, 4)}')
        else:
            p = doc.add_paragraph(line, style='Normal')
        move_paragraph_after(p, doc.paragraphs[insert_at])
        insert_at += 1

def move_paragraph_after(paragraph, after_paragraph):
    after_paragraph._p.addnext(paragraph._p)

def apply_updates(doc: Document, tags: dict, update_tags: list = None) -> dict:
    if update_tags is None:
        update_tags = list(tags.keys())
    
    tag_map = {}
    
    for tag_name in update_tags:
        if tag_name not in tags:
            print(f"⚠️  Tag '{tag_name}' not found in manuscript")
            continue
        
        tag_data = tags[tag_name]
        bookmark_name = get_or_create_bookmark(doc, tag_name, tag_data['heading'], tag_data['level'])
        start_idx, end_idx = find_section_paragraphs(doc, bookmark_name, tag_data)
        
        tag_map[tag_name] = {
            'bookmark': bookmark_name,
            'start_idx': start_idx,
            'end_idx': end_idx,
            'heading': tag_data['heading'],
            'level': tag_data['level']
        }
        
        if tag_data['remove'] and start_idx >= 0:
            delete_paragraphs(doc, start_idx, end_idx, tag_data['remove'])
            print(f"  REMOVED content for {tag_name}")
        
        if tag_data['update'] and start_idx >= 0:
            insert_section_content(doc, start_idx, end_idx, tag_data['update'], tag_data['level'])
            print(f"  UPDATED content for {tag_name}")
        
        if not tag_data['update'] and not tag_data['remove']:
            print(f"  No changes for {tag_name}")
    
    return tag_map

# ======== TABLE FORMATTING (Template Style) ========

def format_table_template_style(table):
    """Apply template table style: no left/vertical middle borders, centered alignment."""
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    
    # Remove all borders first, then add only needed ones
    tbl = table._tbl
    tblPr = tbl.tblPr if tbl.tblPr is not None else OxmlElement('w:tblPr')
    
    # Remove existing borders
    for borders in tblPr.xpath('.//w:tblBorders'):
        tblPr.remove(borders)
    
    # Add borders: only top, bottom, and horizontal inside (no left/right/vertical middle)
    borders = OxmlElement('w:tblBorders')
    for edge in ['top', 'bottom', 'insideH']:
        element = OxmlElement(f'w:{edge}')
        element.set(qn('w:val'), 'single')
        element.set(qn('w:sz'), '4')  # 0.5pt
        element.set(qn('w:space'), '0')
        element.set(qn('w:color'), '000000')
        borders.append(element)
    # insideV, left, right are intentionally omitted
    tblPr.append(borders)
    
    # Header row bold
    for row in table.rows:
        for cell in row.cells:
            for paragraph in cell.paragraphs:
                paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
                for run in paragraph.runs:
                    run.font.size = Pt(11)
                    run.font.name = 'Times New Roman'
    
    # First row (header) bold
    if table.rows:
        for cell in table.rows[0].cells:
            for paragraph in cell.paragraphs:
                for run in paragraph.runs:
                    run.bold = True

def create_table_from_markdown(doc: Document, markdown_table: str):
    """Create a table from markdown pipe syntax with template formatting."""
    lines = [l.strip() for l in markdown_table.strip().split('\n') if l.strip()]
    if len(lines) < 2:
        return
    
    # Parse header
    header_cells = [c.strip() for c in lines[0].split('|') if c.strip()]
    num_cols = len(header_cells)
    
    # Parse rows
    rows_data = []
    for line in lines[2:]:  # Skip separator line
        cells = [c.strip() for c in line.split('|') if c.strip()]
        if len(cells) == num_cols:
            rows_data.append(cells)
    
    table = doc.add_table(rows=1 + len(rows_data), cols=num_cols)
    table.style = 'Table Grid'
    
    # Header
    for i, cell_text in enumerate(header_cells):
        cell = table.rows[0].cells[i]
        cell.text = cell_text
    
    # Data rows
    for r, row_data in enumerate(rows_data):
        for c, cell_text in enumerate(row_data):
            cell = table.rows[r + 1].cells[c]
            cell.text = cell_text
    
    format_table_template_style(table)
    return table

# ======== HIGH-LEVEL OPERATIONS ========

def full_rebuild(template_path: Path, tags: dict, output_path: Path):
    if template_path.exists():
        doc = Document(template_path)
        for p in list(doc.paragraphs):
            p._element.getparent().remove(p._element)
    else:
        doc = Document()
    
    for tag_name, tag_data in tags.items():
        p = doc.add_paragraph(tag_data['heading'], style=f'Heading {tag_data["level"]}')
        create_bookmark_at_paragraph(p, f"tag_{sanitize_bookmark(tag_name)}")
        
        content = tag_data['update'] or tag_data['raw_content']
        if content:
            insert_section_content(doc, len(doc.paragraphs) - 1, len(doc.paragraphs), content, tag_data['level'])
    
    doc.save(output_path)
    print(f"✅ Full rebuild saved to {output_path}")

def compute_docx_sha256(docx_path: Path) -> str:
    return hashlib.sha256(docx_path.read_bytes()).hexdigest()

def update_manuscript_meta(md_path: Path, new_meta: dict):
    content = md_path.read_text(encoding='utf-8')
    meta_str = ' '.join(f'{k}="{v}"' for k, v in new_meta.items())
    new_content = re.sub(
        r'<!--\s*META:.*?-->',
        f'<!-- META: {meta_str} -->',
        content
    )
    if '<!-- META:' not in content:
        new_content = f'<!-- META: {meta_str} -->\n\n{new_content}'
    md_path.write_text(new_content, encoding='utf-8')

def save_tag_map(tag_map: dict, json_path: Path):
    data = {
        'version': 1,
        'generated': datetime.now(timezone.utc).isoformat(),
        'tags': {k: {kk: vv for kk, vv in v.items() if kk != 'paragraph_indices'} for k, v in tag_map.items()}
    }
    json_path.write_text(json.dumps(data, indent=2))

def load_tag_map(json_path: Path) -> dict:
    if json_path.exists():
        return json.loads(json_path.read_text())
    return {}

# ======== CLI ========

def main():
    parser = argparse.ArgumentParser(description="MD → DOCX converter with tag-based surgical updates. Skips Mermaid/diagrams (human tasks). Template table formatting.")
    parser.add_argument('--md', required=True, help="Manuscript MD path")
    parser.add_argument('--template', help="Template DOCX path (for full rebuild)")
    parser.add_argument('--docx', help="Master DOCX path (for surgical update)")
    parser.add_argument('--output', required=True, help="Output DOCX path")
    parser.add_argument('--full-rebuild', action='store_true', help="Full rebuild from template")
    parser.add_argument('--update-tags', nargs='+', help="Specific tags to update")
    parser.add_argument('--check-drift', action='store_true', help="Check drift only")
    args = parser.parse_args()
    
    md_path = Path(args.md)
    output_path = Path(args.output)
    
    meta, tags = parse_manuscript(md_path)
    print(f"Parsed {len(tags)} tags from {md_path}")
    
    if args.check_drift:
        if not args.docx:
            print("❌ --docx required for drift check")
            return 2
        docx_path = Path(args.docx)
        if not docx_path.exists():
            print("❌ DOCX not found")
            return 2
        current_sha = compute_docx_sha256(docx_path)
        expected_sha = meta.get('docx-sha256')
        if expected_sha and current_sha == expected_sha:
            print(f"✅ PASS: DOCX matches META")
            return 0
        else:
            print(f"❌ DRIFT: expected {expected_sha}, got {current_sha}")
            return 1
    
    if args.full_rebuild:
        if not args.template:
            print("❌ --template required for full rebuild")
            return 2
        full_rebuild(Path(args.template), tags, output_path)
    else:
        if not args.docx:
            print("❌ --docx required for surgical update")
            return 2
        docx_path = Path(args.docx)
        
        if 'docx-sha256' in meta:
            current_sha = compute_docx_sha256(docx_path) if docx_path.exists() else ""
            if current_sha and current_sha != meta['docx-sha256']:
                print(f"⚠️  DRIFT DETECTED: DOCX SHA256 mismatch!")
                print(f"   Expected: {meta['docx-sha256']}")
                print(f"   Actual:   {current_sha}")
                return 1
        
        from docx import Document
        if docx_path.exists():
            doc = Document(docx_path)
        elif args.template:
            doc = Document(Path(args.template))
        else:
            doc = Document()
        
        tag_map = apply_updates(doc, tags, args.update_tags)
        doc.save(output_path)
        save_tag_map(tag_map, output_path.parent / 'tag_map.json')
    
    new_sha = compute_docx_sha256(output_path)
    new_meta = {
        'docx-sync-version': datetime.now(timezone.utc).isoformat(),
        'docx-sha256': new_sha
    }
    update_manuscript_meta(md_path, new_meta)
    print(f"✅ Done. DOCX: {output_path}, SHA256: {new_sha}")
    return 0

if __name__ == '__main__':
    sys.exit(main())
