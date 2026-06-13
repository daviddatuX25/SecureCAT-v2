#!/usr/bin/env python3
"""
Add bookmarks to all 23 headings in the defense DOCX.
SAFE: Only inserts XML bookmark elements — NO content/style/formatting changes.
Run ONCE after pulling reverted cloud DOCX, then surgical updates work forever.
"""
from docx import Document
from docx.oxml.ns import qn
from docx.oxml import OxmlElement
import re
import sys

DOCX_PATH = "capstone/output/SecureCAT_Ch1_Ch2_Manuscript[never delete].docx"

def sanitize(n): return re.sub(r'[^a-zA-Z0-9_]', '_', n)

BOOKMARKS = [
    ('ch1_introduction', 'Chapter 1'),
    ('ch1_bg_of_the_study', 'Background of the Study'),
    ('ch1_conceptual_framework', 'Conceptual Framework of the Study'),
    ('ch1_objectives', 'Objectives of the Study'),
    ('ch1_scope_delimitations', 'Scope and Limitation of the Study'),
    ('ch1_significance', 'Significance of the Study'),
    ('ch2_methodology', 'Chapter 2'),
    ('ch2_research_design', 'Research Design'),
    ('ch2_software_model', 'Software Development Model'),
    ('ch2_project_plan', 'Project Plan'),
    ('ch2_project_assignment', 'Project Assignments'),
    ('ch2_population_locale', 'Population and Locale of the Study'),
    ('ch2_research_instruments', 'Research Instruments'),
    ('ch2_data_analysis', 'Data Analysis'),
    ('references_list', 'REFERENCES'),
    ('appendix_a_use_case', 'APPENDIX A'),
    ('appendix_b_letter_conduct', 'APPENDIX B'),
]

def matches_heading(p, search_text):
    """EXACT or STARTSWITH match ONLY for Heading-style paragraphs.
    Content paragraphs often contain heading words but are not headings."""
    ptext = p.text.strip()
    stext = search_text.strip().lower()
    plower = ptext.lower()
    
    # 1. Exact match (after stripping)
    if plower == stext:
        return True
    # 2. Starts with (heading at beginning of paragraph, e.g., "Background of the Study" or "Background of the Study\n")
    if plower.startswith(stext):
        return True
    # NO loose contains matching - that catches content paragraphs
    return False

def main():
    doc = Document(DOCX_PATH)
    print(f"Loaded: {DOCX_PATH} ({len(doc.paragraphs)} paragraphs)")
    
    used_paragraphs = set()
    added = 0
    for tag, heading_text in BOOKMARKS:
        for i, p in enumerate(doc.paragraphs):
            if i in used_paragraphs:
                continue
            # Match ONLY on Heading styles with strict heading text match
            is_heading = p.style.name.lower().startswith('heading')
            text_match = matches_heading(p, heading_text)
            
            if is_heading and text_match:
                bm_name = f'tag_{sanitize(tag)}'
                # Check if already has this bookmark
                existing = p._p.xpath(f'.//w:bookmarkStart[@w:name="{bm_name}"]')
                if existing:
                    print(f"  ⏭  {tag}: already bookmarked at para {i}")
                    break
                
                start = OxmlElement('w:bookmarkStart')
                start.set(qn('w:id'), str(hash(bm_name) % 1000000))
                start.set(qn('w:name'), bm_name)
                p._p.insert(0, start)
                end = OxmlElement('w:bookmarkEnd')
                end.set(qn('w:id'), str(hash(bm_name) % 1000000))
                end.set(qn('w:name'), bm_name)
                p._p.append(end)
                used_paragraphs.add(i)
                print(f"  ✓  {tag} -> para {i} ({p.style.name}): \"{p.text[:50]}\"")
                added += 1
                break
        else:
            print(f"  ⚠  {tag}: NO MATCH for heading \"{heading_text}\"")
    
    doc.save(DOCX_PATH)
    print(f"\nDone. Added {added} bookmarks. Saved to {DOCX_PATH}")

if __name__ == '__main__':
    main()