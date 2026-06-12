#!/usr/bin/env python3
"""
Reverse sync: Extract content from master DOCX and update manuscript MD.
Uses bold-formatted paragraphs as heading detection (template-specific).
"""
import hashlib
import re
from datetime import datetime, timezone
from pathlib import Path
from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH

def is_heading(p) -> bool:
    """Detect heading: bold + (centered OR short text matching known patterns)."""
    is_bold = any(r.bold for r in p.runs if r.bold)
    is_center = p.alignment == WD_ALIGN_PARAGRAPH.CENTER
    text = p.text.strip()
    
    # Known heading patterns
    heading_patterns = [
        'Chapter 1', 'Chapter 2', 'INTRODUCTION', 'METHODOLOGY',
        'Background of the Study', 'Conceptual Framework', 'IPO',
        'Objectives', 'Scope', 'Limitation', 'Importance', 'Significance',
        'Research Design', 'Software', 'Project Plan', 'Gantt',
        'Project Assignment', 'Population', 'Locale', 'Instrument',
        'Data Analysis', 'Appendix', 'Use Case', 'Letter', 'References',
        'Figure', 'Table', 'General Objective'
    ]
    
    matches_pattern = any(pattern.lower() in text.lower() for pattern in heading_patterns)
    is_short = len(text) < 80
    
    return is_bold and (is_center or matches_pattern or is_short)

def extract_docx_sections(docx_path: Path) -> dict:
    doc = Document(docx_path)
    sections = {}
    current_tag = None
    current_heading = None
    current_content = []
    
    # Find first heading to start
    started = False
    
    for p in doc.paragraphs:
        text = p.text.strip()
        if not text:
            continue
        
        if is_heading(p):
            # Save previous section
            if current_tag and current_content:
                sections[current_tag] = {
                    'heading': current_heading,
                    'content': '\n'.join(current_content).strip()
                }
            
            # Start new section
            current_heading = text
            current_tag = heading_to_tag(text)
            current_content = []
            started = True
        elif started:
            current_content.append(text)
    
    # Save last section
    if current_tag and current_content:
        sections[current_tag] = {
            'heading': current_heading,
            'content': '\n'.join(current_content).strip()
        }
    
    return sections

def heading_to_tag(heading: str) -> str:
    h = heading.lower()
    
    # Chapter markers
    if h == 'chapter 1': return 'ch1-introduction'
    if h == 'introduction': return 'ch1-introduction'
    if 'background' in h: return 'ch1-bg-of-the-study'
    if 'conceptual framework' in h or 'ipo' in h: return 'ch1-conceptual-framework'
    if 'objective' in h: return 'ch1-objectives'
    if 'scope' in h and 'limitation' in h: return 'ch1-scope-delimitations'
    if 'significance' in h or 'importance' in h: return 'ch1-significance'
    
    if h == 'chapter 2': return 'ch2-methodology'
    if h == 'methodology': return 'ch2-methodology'
    if 'research design' in h: return 'ch2-research-design'
    if 'software' in h or 'aidlc' in h: return 'ch2-software-model'
    if 'project plan' in h or 'gantt' in h: return 'ch2-project-plan'
    if 'project assignment' in h: return 'ch2-project-assignment'
    if 'population' in h and 'locale' in h: return 'ch2-population-locale'
    if 'instrument' in h or 'sus' in h or 'nasa' in h or 'tlx' in h: return 'ch2-research-instruments'
    if 'data analysis' in h or 'k-means' in h: return 'ch2-data-analysis'
    
    if 'appendix a' in h or 'use case' in h: return 'appendix-a-use-case'
    if 'appendix b' in h or ('letter' in h and 'conduct' in h): return 'appendix-b-letter-conduct'
    
    if 'references' in h: return 'references-list'
    if 'figure' in h or 'table' in h:
        return 'figure-table-' + re.sub(r'[^a-z0-9]+', '-', h).strip('-')
    
    return 'section-' + re.sub(r'[^a-z0-9]+', '-', h).strip('-')

def update_manuscript_md(md_path: Path, sections: dict, docx_sha256: str):
    lines = []
    
    # META tag
    meta_str = f'docx-sync-version="{datetime.now(timezone.utc).isoformat()}" docx-sha256="{docx_sha256}"'
    lines.append(f'<!-- META: {meta_str} -->')
    lines.append('')
    
    # Ordered tags
    tag_order = [
        'ch1-introduction', 'ch1-bg-of-the-study', 'ch1-conceptual-framework',
        'ch1-objectives', 'ch1-scope-delimitations', 'ch1-significance',
        'ch2-methodology', 'ch2-research-design', 'ch2-software-model',
        'ch2-project-plan', 'ch2-project-assignment', 'ch2-population-locale',
        'ch2-research-instruments', 'ch2-data-analysis',
        'appendix-a-use-case', 'appendix-b-letter-conduct', 'references-list'
    ]
    
    for tag in tag_order:
        if tag in sections:
            sec = sections[tag]
            lines.append(f'<!-- TAG: {tag} -->')
            lines.append(f"## {sec['heading']}")
            lines.append('')
            if sec['content']:
                lines.append(sec['content'])
            lines.append('')
            lines.append(f'<!-- UPDATE:START -->')
            lines.append(f'[Content synchronized from cloud DOCX — review and edit here]')
            lines.append(f'<!-- UPDATE:END -->')
            lines.append('')
    
    # Add any extra sections not in order
    for tag, sec in sections.items():
        if tag not in tag_order and not tag.startswith('figure-table-'):
            lines.append(f'<!-- TAG: {tag} -->')
            lines.append(f"## {sec['heading']}")
            lines.append('')
            if sec['content']:
                lines.append(sec['content'])
            lines.append('')
            lines.append(f'<!-- UPDATE:START -->')
            lines.append(f'[Content synchronized from cloud DOCX]')
            lines.append(f'<!-- UPDATE:END -->')
            lines.append('')
    
    new_content = '\n'.join(lines).strip() + '\n'
    md_path.write_text(new_content, encoding='utf-8')
    print(f"✅ Updated manuscript MD: {md_path}")

def main():
    docx_path = Path('capstone/output/SecureCAT_Ch1_Ch2_Manuscript.docx')
    md_path = Path('capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md')
    
    docx_sha256 = hashlib.sha256(docx_path.read_bytes()).hexdigest()
    print(f"Cloud DOCX SHA256: {docx_sha256}")
    
    sections = extract_docx_sections(docx_path)
    print(f"Extracted {len(sections)} sections:")
    for tag, sec in sections.items():
        print(f"  {tag}: {sec['heading']} ({len(sec['content'])} chars)")
    
    update_manuscript_md(md_path, sections, docx_sha256)
    
    print(f"\n✅ Reverse sync complete!")
    print(f"   META tag: SHA256={docx_sha256}")
    print(f"   {len(sections)} TAGged sections")
    return 0

if __name__ == '__main__':
    import sys
    sys.exit(main())
