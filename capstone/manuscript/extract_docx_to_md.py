#!/usr/bin/env python3
"""
Extract DOCX content to Markdown using bookmark boundaries.
Preserves existing MD structure: TAG/UPDATE blocks, annotations, META.
"""
from docx import Document
from docx.oxml.ns import qn
from pathlib import Path
import re
import json

DOCX_PATH = "capstone/output/SecureCAT_Ch1_Ch2_Manuscript[never delete].docx"
MD_PATH = "capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md"

def get_bookmarks(doc):
    """Get all bookmarked paragraphs with their tag names and heading info."""
    bookmarks = []
    for i, p in enumerate(doc.paragraphs):
        bookmarks_start = p._p.xpath('.//w:bookmarkStart')
        for bm in bookmarks_start:
            name = bm.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}name')
            if name and name.startswith('tag_'):
                tag = name[4:]  # remove 'tag_' prefix
                bookmarks.append({
                    'index': i,
                    'tag': tag,
                    'style': p.style.name,
                    'text': p.text.strip(),
                    'full_text': p.text  # keep original for extraction
                })
    return bookmarks

def extract_section_content(doc, start_idx, end_idx):
    """Extract content between two paragraph indices (exclusive of start, inclusive of content)."""
    content_parts = []
    for i in range(start_idx + 1, end_idx):
        if i >= len(doc.paragraphs):
            break
        p = doc.paragraphs[i]
        text = p.text.strip()
        if text:
            content_parts.append(text)
    return '\n\n'.join(content_parts)

def extract_all_sections(doc):
    """Extract all tagged sections from DOCX."""
    bookmarks = get_bookmarks(doc)
    sections = {}
    
    # Sort by index
    bookmarks.sort(key=lambda b: b['index'])
    
    for i, bm in enumerate(bookmarks):
        start = bm['index']
        # End is next bookmark's index or end of doc
        end = bookmarks[i + 1]['index'] if i + 1 < len(bookmarks) else len(doc.paragraphs)
        
        content = extract_section_content(doc, start, end)
        sections[bm['tag']] = {
            'heading': bm['text'],
            'level': int(re.search(r'(\d+)', bm['style']).group(1)) if re.search(r'(\d+)', bm['style']) else 2,
            'content': content
        }
    
    return sections

def main():
    doc = Document(DOCX_PATH)
    
    # Extract bookmarked sections
    sections = extract_all_sections(doc)
    
    print(f"Found {len(sections)} bookmarked sections:")
    for tag, data in sections.items():
        preview = data['content'][:100] + '...' if len(data['content']) > 100 else data['content']
        print(f"  {tag}: level={data['level']}, heading='{data['heading']}', content_len={len(data['content'])}, preview='{preview}'")
    
    # Save for inspection
    out_path = Path("capstone/manuscript/extracted_from_docx.json")
    json.dump(sections, out_path.open('w'), indent=2)
    print(f"\nSaved FULL content to {out_path}")
    
    return sections

if __name__ == '__main__':
    main()